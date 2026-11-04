<?php
/**
 * Formatting helpers: Persian digits, prices, Jalali dates.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Format
 */
class LK_Format {

	/**
	 * Persian digit map.
	 *
	 * @return array<string,string>
	 */
	public static function digit_map() {
		return array(
			'0' => '۰',
			'1' => '۱',
			'2' => '۲',
			'3' => '۳',
			'4' => '۴',
			'5' => '۵',
			'6' => '۶',
			'7' => '۷',
			'8' => '۸',
			'9' => '۹',
		);
	}

	/**
	 * Convert Latin digits to Persian digits.
	 *
	 * @param string|int|float $value Value.
	 * @return string
	 */
	public static function digits( $value ) {
		return strtr( (string) $value, self::digit_map() );
	}

	/**
	 * Convert Persian/Arabic digits to Latin.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function latin_digits( $value ) {
		$map = array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		);
		return strtr( (string) $value, $map );
	}

	/**
	 * Normalise Persian/Arabic text for search and matching.
	 *
	 * Unifies ي/ى to ی, ك to ک, removes tatweel and ZWNJ, normalises digits.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function normalize_text( $text ) {
		$text = (string) $text;
		$text = self::latin_digits( $text );
		$text = str_replace( array( 'ي', 'ى', 'ك', 'ۀ', 'ة', 'أ', 'إ', 'آ' ), array( 'ی', 'ی', 'ک', 'ه', 'ه', 'ا', 'ا', 'ا' ), $text );
		$text = str_replace( array( "\xE2\x80\x8C", 'ـ', "\xE2\x80\x8F", "\xE2\x80\x8E" ), ' ', $text );
		$text = preg_replace( '/[\x{200B}-\x{200F}\x{FEFF}]/u', ' ', $text );
		$text = preg_replace( '/[\s\x{00A0}]+/u', ' ', $text );
		return trim( mb_strtolower( $text, 'UTF-8' ) );
	}

	/**
	 * Group a number with Persian digits.
	 *
	 * @param int|float|null $number Number.
	 * @return string
	 */
	public static function number( $number ) {
		if ( null === $number || '' === $number ) {
			return '';
		}
		$number = (float) $number;
		$decimals = ( floor( $number ) === $number ) ? 0 : ( ( round( $number, 1 ) === round( $number, 2 ) ) ? 1 : 2 );
		return self::digits( number_format( $number, $decimals, '.', '٫' ) );
	}

	/**
	 * Format a Toman amount. Returns empty string when price is unknown.
	 *
	 * @param int|float|null $amount Amount in Toman.
	 * @return string
	 */
	public static function price( $amount ) {
		if ( null === $amount || '' === $amount || 0 === (int) $amount ) {
			return '';
		}
		return self::digits( number_format( (float) $amount, 0, '.', ',' ) );
	}

	/**
	 * Short price for tight spaces: ۲.۵ میلیارد تومان.
	 *
	 * @param int|float|null $amount Amount in Toman.
	 * @return string
	 */
	public static function price_short( $amount ) {
		if ( null === $amount || '' === $amount || (int) $amount <= 0 ) {
			return '';
		}
		$amount = (float) $amount;
		if ( $amount >= 1000000000 ) {
			$value = round( $amount / 1000000000, 1 );
			return self::digits( rtrim( rtrim( number_format( $value, 1, '.', '' ), '0' ), '.' ) ) . ' میلیارد';
		}
		if ( $amount >= 1000000 ) {
			$value = round( $amount / 1000000, 0 );
			return self::digits( number_format( $value, 0, '.', ',' ) ) . ' میلیون';
		}
		return self::digits( number_format( $amount, 0, '.', ',' ) );
	}

	/**
	 * Signed change amount, e.g. +۱۲۵,۰۰۰,۰۰۰.
	 *
	 * @param int|null $amount Amount.
	 * @return string
	 */
	public static function change_amount( $amount ) {
		if ( null === $amount || '' === $amount ) {
			return '';
		}
		$amount = (int) $amount;
		$prefix = 0 === $amount ? '' : ( $amount > 0 ? '+' : '−' );
		return $prefix . self::digits( number_format( abs( $amount ), 0, '.', ',' ) );
	}

	/**
	 * Signed percent, e.g. ۲.۴٪.
	 *
	 * @param float|null $percent Percent.
	 * @return string
	 */
	public static function change_percent( $percent ) {
		if ( null === $percent || '' === $percent ) {
			return '';
		}
		$percent = (float) $percent;
		$prefix = 0 === (int) $percent && 0.0 === $percent ? '' : ( $percent > 0 ? '+' : '−' );
		$abs = abs( $percent );
		$value = ( floor( $abs ) === $abs ) ? number_format( $abs, 0 ) : number_format( $abs, 2, '.', '' );
		$value = rtrim( rtrim( $value, '0' ), '.' );
		return $prefix . self::digits( $value ) . '٪';
	}

	/**
	 * Convert Gregorian date to Jalali.
	 *
	 * @param int $gy Year.
	 * @param int $gm Month.
	 * @param int $gd Day.
	 * @return array{0:int,1:int,2:int} [year, month, day]
	 */
	public static function gregorian_to_jalali( $gy, $gm, $gd ) {
		$gy = (int) $gy;
		$gm = (int) $gm;
		$gd = (int) $gd;

		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 )
			+ intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];

		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$jy  += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return array( $jy, $jm, $jd );
	}

	/**
	 * Jalali month names.
	 *
	 * @return string[]
	 */
	public static function jalali_months() {
		return array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	}

	/**
	 * Format a timestamp (site timezone) as Jalali date.
	 *
	 * @param int|null    $timestamp Unix timestamp. Defaults to now.
	 * @param string      $format    'date' | 'datetime' | 'short' | 'long' | 'iso'.
	 * @return string
	 */
	public static function jalali( $timestamp = null, $format = 'date' ) {
		if ( null === $timestamp || '' === $timestamp ) {
			$timestamp = time();
		}
		if ( ! is_numeric( $timestamp ) ) {
			$timestamp = strtotime( (string) $timestamp );
		}
		if ( ! $timestamp ) {
			return '';
		}

		$local = getdate( $timestamp + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		list( $jy, $jm, $jd ) = self::gregorian_to_jalali( $local['year'], $local['mon'], $local['mday'] );

		$months = self::jalali_months();
		$month  = $months[ $jm - 1 ] ?? '';
		$time   = sprintf( '%02d:%02d', $local['hours'], $local['minutes'] );

		switch ( $format ) {
			case 'year':
				return self::digits( $jy );
			case 'iso':
				return sprintf( '%04d-%02d-%02d', $jy, $jm, $jd );
			case 'short':
				return self::digits( $jd ) . ' ' . $month;
			case 'long':
				return self::digits( $jd ) . ' ' . $month . ' ' . self::digits( $jy );
			case 'datetime':
				return self::digits( $jd ) . ' ' . $month . ' ' . self::digits( $jy ) . ' — ' . self::digits( $time );
			case 'weekday':
				$weekdays = array( 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه' );
				return $weekdays[ (int) $local['wday'] ];
			case 'date':
			default:
				return self::digits( $jd ) . ' ' . $month . ' ' . self::digits( $jy );
		}
	}

	/**
	 * Human relative time in Persian.
	 *
	 * @param int $timestamp Timestamp.
	 * @return string
	 */
	public static function time_ago( $timestamp ) {
		$diff = time() - (int) $timestamp;
		if ( $diff < 0 ) {
			return 'همین حالا';
		}
		if ( $diff < 60 ) {
			return 'همین حالا';
		}
		if ( $diff < 3600 ) {
			$n = (int) floor( $diff / 60 );
			return self::digits( $n ) . ' دقیقه پیش';
		}
		if ( $diff < 86400 ) {
			$n = (int) floor( $diff / 3600 );
			return self::digits( $n ) . ' ساعت پیش';
		}
		if ( $diff < 2592000 ) {
			$n = (int) floor( $diff / 86400 );
			return self::digits( $n ) . ' روز پیش';
		}
		return self::jalali( $timestamp, 'long' );
	}

	/**
	 * Format a spec value for display.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Raw value.
	 * @return string
	 */
	public static function spec_value( $key, $value ) {
		if ( is_array( $value ) ) {
			$value = implode( '، ', array_filter( array_map( 'trim', $value ) ) );
		}
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		$field = LK_Fields::field( $key );
		$type  = $field['type'] ?? 'text';

		if ( 'select' === $type && isset( $field['options'][ $value ] ) ) {
			$label = $field['options'][ $value ];
		} else {
			$label = $value;
		}

		if ( isset( $field['unit'] ) && '' !== $field['unit'] && in_array( $type, array( 'number' ), true ) ) {
			return self::digits( $label ) . ' ' . $field['unit'];
		}

		if ( 'number' === $type ) {
			return self::digits( $label );
		}

		return $label;
	}
}
