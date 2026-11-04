<?php
/**
 * Maps source groups and names to brand taxonomy terms.
 *
 * The upstream "group" is the supplying company, not always the brand, so the
 * "سایر خودروها" bucket is resolved from the model name.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Brand_Resolver
 */
class LKPE_Brand_Resolver {

	/**
	 * Resolved term cache for this request.
	 *
	 * @var array<string,int>
	 */
	private static $terms = array();

	/**
	 * Group map: source group => brand definition.
	 *
	 * @return array
	 */
	private static function group_map() {
		return array(
			'ایران خودرو'    => array( 'name' => 'ایران خودرو', 'name_en' => 'Iran Khodro', 'slug' => 'iran-khodro', 'country' => 'ایران' ),
			'سایپا'          => array( 'name' => 'سایپا', 'name_en' => 'Saipa', 'slug' => 'saipa', 'country' => 'ایران' ),
			'مدیران خودرو'   => array( 'name' => 'مدیران خودرو', 'name_en' => 'Modiran Khodro', 'slug' => 'modiran-khodro', 'country' => 'ایران' ),
			'بهمن موتور'     => array( 'name' => 'بهمن موتور', 'name_en' => 'Bahman Motor', 'slug' => 'bahman-motor', 'country' => 'ایران' ),
			'کرمان موتور'    => array( 'name' => 'کرمان موتور', 'name_en' => 'Kerman Motor', 'slug' => 'kerman-motor', 'country' => 'ایران' ),
		);
	}

	/**
	 * Keyword map for the "other cars" bucket. Order matters.
	 *
	 * @return array
	 */
	private static function keyword_map() {
		return array(
			'toyota'    => array(
				'keywords' => array( 'تویوتا', 'لندکروز', 'پرادو', 'رافور', 'کمری', 'کرولا' ),
				'name'     => 'تویوتا',
				'name_en'  => 'Toyota',
				'slug'     => 'toyota',
				'country'  => 'ژاپن',
			),
			'kia'       => array(
				'keywords' => array( 'کیا', 'اسپورتیج' ),
				'name'     => 'کیا',
				'name_en'  => 'Kia',
				'slug'     => 'kia',
				'country'  => 'کره جنوبی',
			),
			'hyundai'   => array(
				'keywords' => array( 'هیوندای', 'سوناتا', 'توسان', 'النترا' ),
				'name'     => 'هیوندای',
				'name_en'  => 'Hyundai',
				'slug'     => 'hyundai',
				'country'  => 'کره جنوبی',
			),
			'changan'   => array(
				'keywords' => array( 'چانگان' ),
				'name'     => 'چانگان',
				'name_en'  => 'Changan',
				'slug'     => 'changan',
				'country'  => 'چین',
			),
			'lamari'    => array(
				'keywords' => array( 'لاماری' ),
				'name'     => 'لاماری',
				'name_en'  => 'Lamari',
				'slug'     => 'lamari',
				'country'  => 'ایران',
			),
			'farda'     => array(
				'keywords' => array( 'فردا' ),
				'name'     => 'فردا',
				'name_en'  => 'Farda',
				'slug'     => 'farda',
				'country'  => 'ایران',
			),
			'lucano'    => array(
				'keywords' => array( 'لوکانو' ),
				'name'     => 'لوکانو',
				'name_en'  => 'Lucano',
				'slug'     => 'lucano',
				'country'  => 'ایران',
			),
			'mazda'     => array(
				'keywords' => array( 'مزدا' ),
				'name'     => 'مزدا',
				'name_en'  => 'Mazda',
				'slug'     => 'mazda',
				'country'  => 'ژاپن',
			),
			'hongqi'    => array(
				'keywords' => array( 'هونگچی' ),
				'name'     => 'هونگچی',
				'name_en'  => 'Hongqi',
				'slug'     => 'hongqi',
				'country'  => 'چین',
			),
			'haval'     => array(
				'keywords' => array( 'هاول' ),
				'name'     => 'هاول',
				'name_en'  => 'Haval',
				'slug'     => 'haval',
				'country'  => 'چین',
			),
			'byd'       => array(
				'keywords' => array( 'byd', 'سانگ' ),
				'name'     => 'بی‌وای‌دی',
				'name_en'  => 'BYD',
				'slug'     => 'byd',
				'country'  => 'چین',
			),
			'beijing'   => array(
				'keywords' => array( 'بیجینگ' ),
				'name'     => 'بیجینگ',
				'name_en'  => 'Beijing',
				'slug'     => 'beijing',
				'country'  => 'چین',
			),
			'tivan'     => array(
				'keywords' => array( 'تیسان' ),
				'name'     => 'تیسان',
				'name_en'  => 'Tivan',
				'slug'     => 'tivan',
				'country'  => 'ایران',
			),
			'mvm'       => array(
				'keywords' => array( 'ام‌وی‌ام', 'mvm' ),
				'name'     => 'ام‌وی‌ام',
				'name_en'  => 'MVM',
				'slug'     => 'mvm',
				'country'  => 'ایران',
			),
			'fownix'    => array(
				'keywords' => array( 'فونیکس' ),
				'name'     => 'فونیکس',
				'name_en'  => 'Fownix',
				'slug'     => 'fownix',
				'country'  => 'ایران',
			),
		);
	}

	/**
	 * Resolve the brand definition for a car.
	 *
	 * @param string $group Source group.
	 * @param string $name  Car name.
	 * @return array{name:string,name_en:string,slug:string,country:string}
	 */
	public static function resolve( $group, $name ) {
		$groups = self::group_map();
		if ( isset( $groups[ $group ] ) ) {
			return $groups[ $group ];
		}

		$haystack = LK_Format::normalize_text( $group . ' ' . $name );
		foreach ( self::keyword_map() as $definition ) {
			foreach ( $definition['keywords'] as $keyword ) {
				if ( false !== mb_stripos( $haystack, LK_Format::normalize_text( $keyword ) ) ) {
					return array(
						'name'    => $definition['name'],
						'name_en' => $definition['name_en'],
						'slug'    => $definition['slug'],
						'country' => $definition['country'],
					);
				}
			}
		}

		return array(
			'name'    => 'سایر خودروها',
			'name_en' => 'Other brands',
			'slug'    => 'other-vehicles',
			'country' => '',
		);
	}

	/**
	 * Get or create the brand term.
	 *
	 * @param array $brand Brand definition.
	 * @return int Term ID or 0.
	 */
	public static function term_id( $brand ) {
		$slug = $brand['slug'];
		if ( isset( self::$terms[ $slug ] ) ) {
			return self::$terms[ $slug ];
		}

		$term = get_term_by( 'slug', $slug, 'lk_brand' );
		if ( $term && ! is_wp_error( $term ) ) {
			self::$terms[ $slug ] = (int) $term->term_id;
			return self::$terms[ $slug ];
		}

		$created = wp_insert_term(
			$brand['name'],
			'lk_brand',
			array(
				'slug'        => $slug,
				'description' => '',
			)
		);

		if ( is_wp_error( $created ) ) {
			$existing = get_term_by( 'name', $brand['name'], 'lk_brand' );
			if ( $existing && ! is_wp_error( $existing ) ) {
				self::$terms[ $slug ] = (int) $existing->term_id;
				return self::$terms[ $slug ];
			}
			return 0;
		}

		$term_id = (int) $created['term_id'];
		update_term_meta( $term_id, 'lk_brand_name_en', $brand['name_en'] );
		if ( ! empty( $brand['country'] ) ) {
			update_term_meta( $term_id, 'lk_brand_country', $brand['country'] );
		}

		self::$terms[ $slug ] = $term_id;
		return $term_id;
	}
}
