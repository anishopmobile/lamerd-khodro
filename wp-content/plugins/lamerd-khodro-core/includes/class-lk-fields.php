<?php
/**
 * Structured vehicle fields: one definition powering admin form, saving,
 * validation, REST output and front-end rendering.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Fields
 */
class LK_Fields {

	/**
	 * Meta key prefix.
	 */
	const PREFIX = '_lk_';

	/**
	 * Cached field groups.
	 *
	 * @var array|null
	 */
	private static $groups = null;

	/**
	 * Field groups definition.
	 *
	 * @return array
	 */
	public static function groups() {
		if ( null !== self::$groups ) {
			return self::$groups;
		}

		$yesno = array(
			'1' => 'دارد',
			'0' => 'ندارد',
		);

		self::$groups = array(
			'general'      => array(
				'label'  => 'اطلاعات عمومی',
				'fields' => array(
					'name_en'           => array( 'label' => 'نام انگلیسی', 'type' => 'text', 'placeholder' => 'e.g. Peugeot 207i' ),
					'model'             => array( 'label' => 'مدل', 'type' => 'text' ),
					'year'              => array( 'label' => 'سال تولید (شمسی)', 'type' => 'number', 'min' => 1300, 'max' => 1450 ),
					'country'           => array( 'label' => 'کشور سازنده', 'type' => 'text' ),
					'supplier'          => array( 'label' => 'شرکت عرضه‌کننده', 'type' => 'text' ),
					'production_status' => array(
						'label'   => 'وضعیت تولید',
						'type'    => 'select',
						'options' => array(
							'in_production' => 'در حال تولید',
							'stopped'       => 'تولید متوقف شده',
							'upcoming'      => 'عرضه در آینده',
						),
					),
					'supply_status'     => array(
						'label'   => 'وضعیت عرضه',
						'type'    => 'select',
						'options' => array(
							'available'   => 'عرضه آزاد',
							'limited'     => 'عرضه محدود',
							'preorder'    => 'پیش‌فروش',
							'unavailable' => 'عرضه ندارد',
						),
					),
				),
			),
			'engine'       => array(
				'label'  => 'مشخصات موتور',
				'fields' => array(
					'engine_type'       => array( 'label' => 'نوع موتور', 'type' => 'text', 'placeholder' => 'بنزینی چهار سیلندر' ),
					'displacement'      => array( 'label' => 'حجم موتور', 'type' => 'number', 'unit' => 'سی‌سی', 'min' => 0, 'max' => 10000 ),
					'cylinders'         => array( 'label' => 'تعداد سیلندر', 'type' => 'number', 'min' => 0, 'max' => 16 ),
					'valves'            => array( 'label' => 'تعداد سوپاپ', 'type' => 'number', 'min' => 0, 'max' => 64 ),
					'power_hp'          => array( 'label' => 'حداکثر قدرت', 'type' => 'number', 'unit' => 'اسب بخار', 'min' => 0, 'max' => 3000 ),
					'power_rpm'         => array( 'label' => 'دور موتور در حداکثر قدرت', 'type' => 'number', 'unit' => 'دور در دقیقه', 'min' => 0, 'max' => 20000 ),
					'torque_nm'         => array( 'label' => 'حداکثر گشتاور', 'type' => 'number', 'unit' => 'نیوتون‌متر', 'min' => 0, 'max' => 5000 ),
					'torque_rpm'        => array( 'label' => 'دور موتور در حداکثر گشتاور', 'type' => 'number', 'unit' => 'دور در دقیقه', 'min' => 0, 'max' => 20000 ),
					'fuel_type'         => array(
						'label'   => 'نوع سوخت',
						'type'    => 'select',
						'options' => array(
							'petrol'   => 'بنزینی',
							'diesel'   => 'گازوئیلی',
							'hybrid'   => 'هیبرید',
							'electric' => 'برقی',
							'cng'      => 'دوگانه‌سوز (CNG)',
							'dual'     => 'دوگانه‌سوز',
						),
					),
					'emission_standard' => array( 'label' => 'استاندارد آلایندگی', 'type' => 'text', 'placeholder' => 'یورو ۵' ),
					'turbo'             => array( 'label' => 'توربوشارژر', 'type' => 'select', 'options' => $yesno ),
				),
			),
			'transmission' => array(
				'label'  => 'گیربکس و انتقال قدرت',
				'fields' => array(
					'gearbox_type' => array(
						'label'   => 'نوع گیربکس',
						'type'    => 'select',
						'options' => array(
							'manual'    => 'دستی',
							'automatic' => 'اتوماتیک',
							'cvt'       => 'CVT',
							'dct'       => 'DCT',
							'amt'       => 'AMT',
						),
					),
					'gears'        => array( 'label' => 'تعداد دنده', 'type' => 'number', 'min' => 0, 'max' => 12 ),
					'drivetrain'   => array(
						'label'   => 'نوع انتقال قدرت',
						'type'    => 'select',
						'options' => array(
							'fwd' => 'دیفرانسیل جلو',
							'rwd' => 'دیفرانسیل عقب',
							'awd' => 'چهار چرخ محرک',
						),
					),
				),
			),
			'performance'  => array(
				'label'  => 'عملکرد و مصرف سوخت',
				'fields' => array(
					'accel'         => array( 'label' => 'شتاب صفر تا صد', 'type' => 'number', 'unit' => 'ثانیه', 'min' => 0, 'max' => 60, 'step' => '0.1' ),
					'top_speed'     => array( 'label' => 'حداکثر سرعت', 'type' => 'number', 'unit' => 'کیلومتر بر ساعت', 'min' => 0, 'max' => 500 ),
					'fuel_city'     => array( 'label' => 'مصرف سوخت شهری', 'type' => 'number', 'unit' => 'لیتر در ۱۰۰ کیلومتر', 'min' => 0, 'max' => 100, 'step' => '0.1' ),
					'fuel_road'     => array( 'label' => 'مصرف سوخت جاده‌ای', 'type' => 'number', 'unit' => 'لیتر در ۱۰۰ کیلومتر', 'min' => 0, 'max' => 100, 'step' => '0.1' ),
					'fuel_combined' => array( 'label' => 'مصرف سوخت ترکیبی', 'type' => 'number', 'unit' => 'لیتر در ۱۰۰ کیلومتر', 'min' => 0, 'max' => 100, 'step' => '0.1' ),
					'tank_capacity' => array( 'label' => 'ظرفیت باک', 'type' => 'number', 'unit' => 'لیتر', 'min' => 0, 'max' => 200 ),
				),
			),
			'dimensions'   => array(
				'label'  => 'ابعاد و ظرفیت',
				'fields' => array(
					'length'    => array( 'label' => 'طول', 'type' => 'number', 'unit' => 'میلی‌متر', 'min' => 0, 'max' => 8000 ),
					'width'     => array( 'label' => 'عرض', 'type' => 'number', 'unit' => 'میلی‌متر', 'min' => 0, 'max' => 4000 ),
					'height'    => array( 'label' => 'ارتفاع', 'type' => 'number', 'unit' => 'میلی‌متر', 'min' => 0, 'max' => 4000 ),
					'wheelbase' => array( 'label' => 'فاصله محوری', 'type' => 'number', 'unit' => 'میلی‌متر', 'min' => 0, 'max' => 4000 ),
					'weight'    => array( 'label' => 'وزن', 'type' => 'number', 'unit' => 'کیلوگرم', 'min' => 0, 'max' => 5000 ),
					'trunk'     => array( 'label' => 'حجم صندوق عقب', 'type' => 'number', 'unit' => 'لیتر', 'min' => 0, 'max' => 3000 ),
					'seats'     => array( 'label' => 'ظرفیت سرنشین', 'type' => 'number', 'unit' => 'نفر', 'min' => 0, 'max' => 50 ),
				),
			),
			'features'     => array(
				'label'  => 'امکانات',
				'fields' => array(
					'safety_features'  => array( 'label' => 'امکانات ایمنی', 'type' => 'textarea', 'rows' => 4, 'hint' => 'هر مورد در یک خط' ),
					'comfort_features' => array( 'label' => 'امکانات رفاهی', 'type' => 'textarea', 'rows' => 4, 'hint' => 'هر مورد در یک خط' ),
					'brake_system'     => array( 'label' => 'سیستم ترمز', 'type' => 'text' ),
					'airbags'          => array( 'label' => 'کیسه هوا', 'type' => 'text', 'placeholder' => '۲ عدد' ),
					'adas'             => array( 'label' => 'سیستم‌های کمک راننده', 'type' => 'textarea', 'rows' => 3, 'hint' => 'هر مورد در یک خط' ),
					'infotainment'     => array( 'label' => 'نمایشگر و مالتی‌مدیا', 'type' => 'text' ),
					'audio'            => array( 'label' => 'سیستم صوتی', 'type' => 'text' ),
					'camera'           => array( 'label' => 'دوربین', 'type' => 'text' ),
					'sensors'          => array( 'label' => 'سنسورها', 'type' => 'text' ),
					'interior'         => array( 'label' => 'امکانات داخلی', 'type' => 'textarea', 'rows' => 3, 'hint' => 'هر مورد در یک خط' ),
				),
			),
			'gallery'      => array(
				'label'  => 'گالری تصاویر',
				'fields' => array(
					'gallery' => array( 'label' => 'تصاویر گالری', 'type' => 'media', 'hint' => 'نمای جلو، عقب، جانبی، کابین، داشبورد و جزئیات' ),
				),
			),
		);

		self::$groups = apply_filters( 'lk_vehicle_field_groups', self::$groups );

		return self::$groups;
	}

	/**
	 * Flat map of all fields.
	 *
	 * @return array<string,array>
	 */
	public static function all() {
		$fields = array();
		foreach ( self::groups() as $group_key => $group ) {
			foreach ( $group['fields'] as $key => $field ) {
				$field['group']       = $group_key;
				$field['group_label'] = $group['label'];
				$fields[ $key ]       = $field;
			}
		}
		return $fields;
	}

	/**
	 * Single field definition.
	 *
	 * @param string $key Field key.
	 * @return array|null
	 */
	public static function field( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Register post meta for every field.
	 *
	 * @return void
	 */
	public static function register_meta() {
		foreach ( self::all() as $key => $field ) {
			register_post_meta(
				'lk_vehicle',
				self::PREFIX . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => static function ( $value ) use ( $key ) {
						return self::sanitize( $key, $value );
					},
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}

		$extra = array( 'source', 'source_id', 'last_synced' );
		foreach ( $extra as $key ) {
			register_post_meta(
				'lk_vehicle',
				self::PREFIX . $key,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}

		register_post_meta(
			'lk_vehicle',
			self::PREFIX . 'views',
			array(
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Sanitise one field value by its definition.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Raw value.
	 * @return string|array
	 */
	public static function sanitize( $key, $value ) {
		$field = self::field( $key );
		if ( ! $field ) {
			return '';
		}

		$type = $field['type'];

		if ( 'media' === $type ) {
			$ids = is_array( $value ) ? $value : explode( ',', (string) $value );
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
			return array_slice( $ids, 0, 20 );
		}

		if ( 'number' === $type ) {
			$value = LK_Format::latin_digits( (string) $value );
			$value = str_replace( array( ',', '٫', ' ' ), '', $value );
			if ( '' === $value || ! is_numeric( $value ) ) {
				return '';
			}
			$number = (float) $value;
			$min    = isset( $field['min'] ) ? (float) $field['min'] : null;
			$max    = isset( $field['max'] ) ? (float) $field['max'] : null;
			if ( null !== $min && $number < $min ) {
				return '';
			}
			if ( null !== $max && $number > $max ) {
				return '';
			}
			return ( floor( $number ) === $number ) ? (string) (int) $number : (string) round( $number, 2 );
		}

		if ( 'select' === $type ) {
			$value = sanitize_key( (string) $value );
			return isset( $field['options'][ $value ] ) ? $value : '';
		}

		if ( 'textarea' === $type ) {
			return sanitize_textarea_field( (string) $value );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Save a set of raw values for a vehicle.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $raw     Raw values keyed by field key.
	 * @return void
	 */
	public static function save( $post_id, $raw ) {
		foreach ( self::all() as $key => $field ) {
			if ( 'media' !== $field['type'] && ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$value = self::sanitize( $key, $raw[ $key ] ?? '' );
			if ( '' === $value || array() === $value ) {
				delete_post_meta( $post_id, self::PREFIX . $key );
			} else {
				update_post_meta( $post_id, self::PREFIX . $key, $value );
			}
		}
	}

	/**
	 * Get one raw field value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key.
	 * @return string|array
	 */
	public static function get( $post_id, $key ) {
		$value = get_post_meta( $post_id, self::PREFIX . $key, true );
		return is_array( $value ) ? $value : (string) $value;
	}

	/**
	 * Human display for one field.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key.
	 * @return string
	 */
	public static function get_display( $post_id, $key ) {
		$value = self::get( $post_id, $key );
		if ( '' === $value || array() === $value ) {
			return '';
		}
		$field = self::field( $key );
		if ( $field && 'textarea' === $field['type'] ) {
			$lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $value ) ) );
			return implode( '، ', $lines );
		}
		return LK_Format::spec_value( $key, $value );
	}

	/**
	 * Structured specs for a vehicle.
	 *
	 * @param int  $post_id       Post ID.
	 * @param bool $include_empty Include fields without value.
	 * @return array
	 */
	public static function get_specs( $post_id, $include_empty = false ) {
		$out = array();
		foreach ( self::groups() as $group_key => $group ) {
			if ( 'gallery' === $group_key ) {
				continue;
			}
			$fields_out = array();
			foreach ( $group['fields'] as $key => $field ) {
				$display = self::get_display( $post_id, $key );
				if ( '' === $display && ! $include_empty ) {
					continue;
				}
				$raw = self::get( $post_id, $key );
				$fields_out[] = array(
					'key'     => $key,
					'label'   => $field['label'],
					'value'   => ( '' === $raw ) ? null : $raw,
					'display' => $display,
				);
			}
			if ( $fields_out ) {
				$out[ $group_key ] = array(
					'label'  => $group['label'],
					'fields' => $fields_out,
				);
			}
		}
		return $out;
	}

	/**
	 * Gallery attachment IDs.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function get_gallery( $post_id ) {
		$ids = get_post_meta( $post_id, self::PREFIX . 'gallery', true );
		if ( ! is_array( $ids ) ) {
			$ids = array_filter( array_map( 'absint', explode( ',', (string) $ids ) ) );
		}
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}

	/**
	 * Completion percentage of specs (for admin and empty-state UX).
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function completion( $post_id ) {
		$fields = self::all();
		$total  = 0;
		$filled = 0;
		foreach ( $fields as $key => $field ) {
			if ( 'media' === $field['type'] ) {
				continue;
			}
			++$total;
			if ( '' !== self::get( $post_id, $key ) ) {
				++$filled;
			}
		}
		return $total ? (int) round( ( $filled / $total ) * 100 ) : 0;
	}

	/**
	 * Production status label.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function production_status_label( $post_id ) {
		$value = self::get( $post_id, 'production_status' );
		$field = self::field( 'production_status' );
		if ( $value && isset( $field['options'][ $value ] ) ) {
			return $field['options'][ $value ];
		}
		$stopped = get_post_meta( $post_id, self::PREFIX . 'source_stopped', true );
		if ( '1' === (string) $stopped ) {
			return 'تولید متوقف شده';
		}
		return '';
	}
}
