<?php
/**
 * Validation and normalisation of source payloads.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Validator
 */
class LKPE_Validator {

	/**
	 * Highest plausible price in Toman (1 trillion) to reject corrupt values.
	 */
	const MAX_PRICE = 1000000000000;

	/**
	 * Normalise and validate one car row.
	 *
	 * @param array $row Raw row from provider.
	 * @return array{ok:bool,errors:string[],warnings:string[],data:array}
	 */
	public static function car( $row ) {
		$errors   = array();
		$warnings = array();

		if ( ! is_array( $row ) ) {
			return array(
				'ok'       => false,
				'errors'   => array( 'ردیف نامعتبر است.' ),
				'warnings' => array(),
				'data'     => array(),
			);
		}

		$source_id = isset( $row['id'] ) ? (int) $row['id'] : 0;
		if ( $source_id <= 0 ) {
			$errors[] = 'شناسه خودرو نامعتبر است.';
		}

		$name = isset( $row['name'] ) ? (string) $row['name'] : '';
		$name = trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', $name ) );
		if ( '' === $name ) {
			$errors[] = 'نام خودرو خالی است.';
		}
		if ( mb_strlen( $name, 'UTF-8' ) > 191 ) {
			$name     = mb_substr( $name, 0, 191, 'UTF-8' );
			$warnings[] = 'نام خودرو کوتاه شد.';
		}

		$group = isset( $row['group'] ) ? sanitize_text_field( (string) $row['group'] ) : '';

		$factory = self::price( $row['factory_price'] ?? null, 'factory_price', $warnings );
		$market  = self::price( $row['market_price'] ?? null, 'market_price', $warnings );

		$status = 'valid';
		if ( null === $market ) {
			$status     = 'incomplete';
			$warnings[] = 'قیمت بازار موجود نیست.';
		}

		$change = self::change( $row['change'] ?? null, $market, $warnings );
		if ( ! empty( $change['suspect'] ) ) {
			$status = 'suspect';
		}

		if ( $errors ) {
			return array(
				'ok'       => false,
				'errors'   => $errors,
				'warnings' => $warnings,
				'data'     => array(),
			);
		}

		$data = array(
			'source_vehicle_id' => (string) $source_id,
			'name'              => $name,
			'name_normalized'   => LK_Format::normalize_text( $name ),
			'source_group'      => $group,
			'factory_price'     => $factory,
			'market_price'      => $market,
			'change_amount'     => $change['amount'],
			'change_percent'    => $change['percent'],
			'production_stopped' => ! empty( $row['production_stopped'] ),
			'data_status'       => $status,
		);

		return array(
			'ok'       => true,
			'errors'   => array(),
			'warnings' => $warnings,
			'data'     => $data,
		);
	}

	/**
	 * Validate a price value.
	 *
	 * @param mixed    $value    Raw value.
	 * @param string   $field    Field name for warnings.
	 * @param string[] $warnings Warning collector.
	 * @return int|null
	 */
	private static function price( $value, $field, &$warnings ) {
		if ( null === $value || '' === $value ) {
			return null;
		}
		if ( ! is_numeric( $value ) ) {
			$warnings[] = 'مقدار نامعتبر برای ' . $field . ' نادیده گرفته شد.';
			return null;
		}

		$number = (float) $value;
		if ( $number <= 0 ) {
			// Zero is treated as "unknown", never as a real price.
			return null;
		}
		if ( $number > self::MAX_PRICE ) {
			$warnings[] = 'مقدار غیرعادی برای ' . $field . ' رد شد.';
			return null;
		}

		return (int) round( $number );
	}

	/**
	 * Validate the change block.
	 *
	 * @param mixed    $change  Raw change.
	 * @param int|null $market  Market price.
	 * @param string[] $warnings Warning collector.
	 * @return array{amount:?int,percent:?float,suspect:bool}
	 */
	private static function change( $change, $market, &$warnings ) {
		$out = array(
			'amount'  => null,
			'percent' => null,
			'suspect' => false,
		);

		if ( ! is_array( $change ) ) {
			return $out;
		}

		$amount  = isset( $change['amount'] ) && is_numeric( $change['amount'] ) ? (int) round( (float) $change['amount'] ) : null;
		$percent = isset( $change['percent'] ) && is_numeric( $change['percent'] ) ? (float) $change['percent'] : null;

		if ( null === $market ) {
			return $out;
		}

		if ( null === $amount && null === $percent ) {
			return $out;
		}

		$percent_out_of_range = ( null !== $percent && ( $percent < -100 || $percent > 100 ) );
		$amount_out_of_range  = ( null !== $amount && abs( $amount ) > ( $market * 2 ) );

		if ( $percent_out_of_range || $amount_out_of_range ) {
			$out['suspect'] = true;
			$warnings[]     = 'تغییر قیمت با قیمت فعلی سازگار نیست و به‌عنوان «نیازمند بررسی» علامت خورد.';
			return $out;
		}

		$out['amount']  = $amount;
		$out['percent'] = null === $percent ? null : round( $percent, 2 );

		return $out;
	}
}
