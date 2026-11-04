<?php
/**
 * Vehicle comparison rows.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Compare
 */
class LK_Compare {

	/**
	 * Maximum vehicles in one comparison.
	 */
	const MAX = 4;

	/**
	 * Minimum vehicles in one comparison.
	 */
	const MIN = 2;

	/**
	 * Build comparable rows for a set of vehicles.
	 *
	 * @param int[] $ids Vehicle post IDs.
	 * @return array{vehicles:array,rows:array,error:string}
	 */
	public static function rows( $ids ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
		$ids = array_slice( $ids, 0, self::MAX );

		if ( count( $ids ) < self::MIN ) {
			return array(
				'vehicles' => array(),
				'rows'     => array(),
				'error'    => 'برای مقایسه حداقل دو خودرو انتخاب کنید.',
			);
		}

		$vehicles = array();
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( ! $post || 'lk_vehicle' !== $post->post_type || 'publish' !== $post->post_status ) {
				continue;
			}
			$brands = wp_get_object_terms( $id, 'lk_brand', array( 'fields' => 'names' ) );
			$vehicles[] = array(
				'id'    => $id,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'image' => (string) get_the_post_thumbnail_url( $post, 'medium' ),
				'brand' => ( ! is_wp_error( $brands ) && $brands ) ? $brands[0] : '',
				'price' => lk_get_price( $id ),
			);
		}

		if ( count( $vehicles ) < self::MIN ) {
			return array(
				'vehicles' => $vehicles,
				'rows'     => array(),
				'error'    => 'حداقل دو خودروی منتشرشده برای مقایسه لازم است.',
			);
		}

		$definitions = self::definitions();
		$rows        = array();

		foreach ( $definitions as $group_label => $fields ) {
			$group_rows = array();
			foreach ( $fields as $key => $definition ) {
				$values  = array();
				$raw     = array();
				$display = array();

				foreach ( $vehicles as $vehicle ) {
					$value = self::value_for( $vehicle['id'], $key, $vehicle['price'] );
					$raw[] = $value['raw'];
					$display[] = $value['display'];
					$values[]  = $value;
				}

				$differs = count( array_unique( array_map( 'wp_json_encode', $raw ) ) ) > 1;
				$best    = self::best_index( $raw, $definition['best'] ?? '' );

				$group_rows[] = array(
					'key'        => $key,
					'label'      => $definition['label'],
					'values'     => $display,
					'raw'        => $raw,
					'best_index' => $best,
					'differs'    => $differs,
					'type'       => $definition['type'] ?? 'text',
				);
			}

			if ( $group_rows ) {
				$rows[] = array(
					'group' => $group_label,
					'rows'  => $group_rows,
				);
			}
		}

		return array(
			'vehicles' => $vehicles,
			'rows'     => $rows,
			'error'    => '',
		);
	}

	/**
	 * Field definitions used in comparison tables.
	 *
	 * @return array
	 */
	public static function definitions() {
		return array(
			'مشخصات کلی'  => array(
				'brand'      => array( 'label' => 'برند' ),
				'year'       => array( 'label' => 'سال تولید', 'best' => 'high', 'type' => 'number' ),
				'production' => array( 'label' => 'وضعیت تولید' ),
			),
			'قیمت'        => array(
				'market_price'  => array( 'label' => 'قیمت بازار', 'type' => 'price', 'best' => 'low' ),
				'factory_price' => array( 'label' => 'قیمت کارخانه', 'type' => 'price', 'best' => 'low' ),
				'price_gap'     => array( 'label' => 'اختلاف بازار و کارخانه', 'type' => 'price' ),
			),
			'پیشرانه'     => array(
				'engine_type'  => array( 'label' => 'نوع موتور' ),
				'displacement' => array( 'label' => 'حجم موتور', 'best' => 'none' ),
				'power_hp'     => array( 'label' => 'حداکثر قدرت', 'best' => 'high' ),
				'torque_nm'    => array( 'label' => 'حداکثر گشتاور', 'best' => 'high' ),
				'fuel_type'    => array( 'label' => 'نوع سوخت' ),
				'gearbox_type' => array( 'label' => 'گیربکس' ),
				'gears'        => array( 'label' => 'تعداد دنده', 'best' => 'none' ),
				'drivetrain'   => array( 'label' => 'انتقال قدرت' ),
			),
			'عملکرد'      => array(
				'accel'         => array( 'label' => 'شتاب صفر تا صد', 'best' => 'low' ),
				'top_speed'     => array( 'label' => 'حداکثر سرعت', 'best' => 'high' ),
				'fuel_combined' => array( 'label' => 'مصرف سوخت ترکیبی', 'best' => 'low' ),
				'tank_capacity' => array( 'label' => 'ظرفیت باک', 'best' => 'none' ),
			),
			'ابعاد'       => array(
				'length'    => array( 'label' => 'طول' ),
				'width'     => array( 'label' => 'عرض' ),
				'height'    => array( 'label' => 'ارتفاع' ),
				'wheelbase' => array( 'label' => 'فاصله محوری' ),
				'weight'    => array( 'label' => 'وزن', 'best' => 'low' ),
				'trunk'     => array( 'label' => 'حجم صندوق', 'best' => 'high' ),
				'seats'     => array( 'label' => 'ظرفیت سرنشین', 'best' => 'high' ),
			),
			'امکانات'     => array(
				'airbags'          => array( 'label' => 'کیسه هوا' ),
				'brake_system'     => array( 'label' => 'سیستم ترمز' ),
				'camera'           => array( 'label' => 'دوربین' ),
				'sensors'          => array( 'label' => 'سنسورها' ),
				'safety_features'  => array( 'label' => 'امکانات ایمنی', 'type' => 'list' ),
				'comfort_features' => array( 'label' => 'امکانات رفاهی', 'type' => 'list' ),
				'adas'             => array( 'label' => 'کمک راننده', 'type' => 'list' ),
			),
		);
	}

	/**
	 * Resolve one value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key.
	 * @param array|null $price Price array.
	 * @return array{raw:mixed,display:string}
	 */
	private static function value_for( $post_id, $key, $price ) {
		$none = array(
			'raw'     => null,
			'display' => '—',
		);

		if ( 'brand' === $key ) {
			$brands = wp_get_object_terms( $post_id, 'lk_brand', array( 'fields' => 'names' ) );
			if ( is_wp_error( $brands ) || ! $brands ) {
				return $none;
			}
			return array(
				'raw'     => $brands[0],
				'display' => $brands[0],
			);
		}

		if ( 'production' === $key ) {
			$label = LK_Fields::production_status_label( $post_id );
			return array(
				'raw'     => $label,
				'display' => $label ? $label : '—',
			);
		}

		if ( 'market_price' === $key || 'factory_price' === $key ) {
			$value = $price[ $key ] ?? null;
			return array(
				'raw'     => $value ? (int) $value : null,
				'display' => $value ? LK_Format::price( $value ) . ' تومان' : 'ناموجود',
			);
		}

		if ( 'price_gap' === $key ) {
			$market  = $price['market_price'] ?? null;
			$factory = $price['factory_price'] ?? null;
			if ( ! $market || ! $factory ) {
				return $none;
			}
			$gap = (int) $market - (int) $factory;
			return array(
				'raw'     => $gap,
				'display' => LK_Format::price( abs( $gap ) ) . ' تومان',
			);
		}

		$value = LK_Fields::get( $post_id, $key );
		if ( '' === $value || array() === $value ) {
			return $none;
		}

		$field = LK_Fields::field( $key );
		if ( $field && 'textarea' === $field['type'] ) {
			$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $value ) ) );
			return array(
				'raw'     => count( $lines ),
				'display' => $lines ? implode( '، ', $lines ) : '—',
			);
		}

		if ( 'number' === ( $field['type'] ?? '' ) ) {
			return array(
				'raw'     => (float) $value,
				'display' => LK_Format::spec_value( $key, $value ),
			);
		}

		return array(
			'raw'     => (string) $value,
			'display' => LK_Fields::get_display( $post_id, $key ),
		);
	}

	/**
	 * Index of the best value, or -1 when not applicable.
	 *
	 * @param array  $values Raw values.
	 * @param string $rule   high|low|none.
	 * @return int
	 */
	private static function best_index( $values, $rule ) {
		if ( ! in_array( $rule, array( 'high', 'low' ), true ) ) {
			return -1;
		}

		$numbers = array();
		foreach ( $values as $index => $value ) {
			if ( null !== $value && '' !== $value && is_numeric( $value ) ) {
				$numbers[ $index ] = (float) $value;
			}
		}

		if ( count( $numbers ) < 2 ) {
			return -1;
		}

		$target = 'high' === $rule ? max( $numbers ) : min( $numbers );
		$best   = array_search( $target, $numbers, true );

		// Only winners with a distinct value are highlighted.
		if ( count( array_unique( $numbers ) ) < 2 ) {
			return -1;
		}

		return false === $best ? -1 : (int) $best;
	}
}
