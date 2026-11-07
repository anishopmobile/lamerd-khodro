<?php
/**
 * Plan post type and data access.
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plans (طرح‌های ثبت‌نام).
 */
class LKP_Plans {

	const POST_TYPE = 'lk_plan';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
	}

	/**
	 * Register the plan post type.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => 'طرح‌های ثبت‌نام',
			'singular_name'      => 'طرح ثبت‌نام',
			'menu_name'          => 'طرح‌های ثبت‌نام',
			'add_new'            => 'افزودن طرح',
			'add_new_item'       => 'افزودن طرح جدید',
			'edit_item'          => 'ویرایش طرح',
			'new_item'           => 'طرح جدید',
			'view_item'          => 'مشاهدهٔ طرح',
			'search_items'       => 'جستجوی طرح‌ها',
			'not_found'          => 'طرحی یافت نشد.',
			'not_found_in_trash' => 'طرحی در زباله‌دان نیست.',
			'all_items'          => 'همهٔ طرح‌ها',
			'item_published'     => 'طرح ذخیره شد.',
			'item_updated'       => 'طرح به‌روزرسانی شد.',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'description'        => 'طرح‌های فروش و ثبت‌نام خودرو',
				'public'             => false,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_admin_bar'  => false,
				'show_in_rest'       => false,
				'menu_position'      => 26,
				'menu_icon'          => 'dashicons-car',
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'supports'           => array( 'title', 'editor', 'revisions' ),
				'rewrite'            => false,
				'query_var'          => false,
				'delete_with_user'   => false,
			)
		);
	}

	/**
	 * Meta keys used by a plan.
	 *
	 * @return array<string,string>
	 */
	public static function meta_keys() {
		return array(
			'company'   => '_lkp_company',
			'active'    => '_lkp_active',
			'active_ts' => '_lkp_active_ts',
			'start_ts'  => '_lkp_start_ts',
			'end_ts'    => '_lkp_end_ts',
			'images'    => '_lkp_images',
			'circular'  => '_lkp_circular',
			'catalog'   => '_lkp_catalog',
		);
	}

	/**
	 * Register plan meta with sanitisation and capability checks.
	 *
	 * @return void
	 */
	public static function register_meta() {
		$keys  = self::meta_keys();
		$auth  = array( __CLASS__, 'can_manage' );
		$entry = array(
			'company'   => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
			'active'    => array( 'type' => 'string', 'sanitize_callback' => array( __CLASS__, 'sanitize_flag' ) ),
			'active_ts' => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
			'start_ts'  => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
			'end_ts'    => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
			'images'    => array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize_ids' ) ),
			'circular'  => array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize_file' ) ),
			'catalog'   => array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize_file' ) ),
		);

		foreach ( $entry as $key => $args ) {
			register_post_meta(
				self::POST_TYPE,
				$keys[ $key ],
				array_merge(
					$args,
					array(
						'single'            => true,
						'show_in_rest'      => false,
						'auth_callback'     => $auth,
					)
				)
			);
		}
	}

	/**
	 * Only plan managers may read or write plan meta.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( LKP_CAP );
	}

	/**
	 * Sanitize a boolean flag stored as "1"/"0".
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_flag( $value ) {
		return ( '1' === (string) $value || true === $value || 'on' === $value ) ? '1' : '0';
	}

	/**
	 * Sanitize a list of attachment IDs.
	 *
	 * @param mixed $value Raw value.
	 * @return int[]
	 */
	public static function sanitize_ids( $value ) {
		$ids = array();
		foreach ( (array) $value as $item ) {
			$id = absint( $item );
			if ( $id && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Sanitize a stored private file descriptor.
	 *
	 * @param mixed $value Raw value.
	 * @return array<string,mixed>
	 */
	public static function sanitize_file( $value ) {
		if ( ! is_array( $value ) || empty( $value['stored'] ) ) {
			return array();
		}

		return array(
			'stored' => basename( (string) $value['stored'] ),
			'name'   => sanitize_text_field( (string) ( $value['name'] ?? 'فایل طرح' ) ),
			'mime'   => sanitize_text_field( (string) ( $value['mime'] ?? '' ) ),
			'size'   => absint( $value['size'] ?? 0 ),
		);
	}

	/**
	 * Plan company (سازنده).
	 *
	 * @param int $plan_id Plan ID.
	 * @return string
	 */
	public static function company( $plan_id ) {
		return (string) get_post_meta( (int) $plan_id, self::meta_keys()['company'], true );
	}

	/**
	 * Timestamp of a plan field.
	 *
	 * @param int    $plan_id Plan ID.
	 * @param string $field active_ts|start_ts|end_ts.
	 * @return int
	 */
	public static function timestamp( $plan_id, $field ) {
		$keys = self::meta_keys();
		if ( ! isset( $keys[ $field ] ) ) {
			return 0;
		}

		return (int) get_post_meta( (int) $plan_id, $keys[ $field ], true );
	}

	/**
	 * Whether the plan is explicitly enabled for the site.
	 *
	 * @param int $plan_id Plan ID.
	 * @return bool
	 */
	public static function is_enabled( $plan_id ) {
		$value = get_post_meta( (int) $plan_id, self::meta_keys()['active'], true );
		return '0' !== (string) $value;
	}

	/**
	 * Current state of a plan.
	 *
	 * @param int $plan_id Plan ID.
	 * @return string disabled|future|live|expired
	 */
	public static function state( $plan_id ) {
		if ( ! self::is_enabled( $plan_id ) ) {
			return 'disabled';
		}

		$now   = time();
		$start = self::timestamp( $plan_id, 'start_ts' );
		$end   = self::timestamp( $plan_id, 'end_ts' );

		// A plan without a full schedule is never open for registration.
		if ( $start <= 0 || $end <= 0 || $end <= $start ) {
			return 'disabled';
		}

		if ( $now < $start ) {
			return 'future';
		}

		if ( $now > $end ) {
			return 'expired';
		}

		return 'live';
	}

	/**
	 * Persian label for a state.
	 *
	 * @param string $state State key.
	 * @return string
	 */
	public static function state_label( $state ) {
		$labels = array(
			'live'     => 'در حال ثبت‌نام',
			'future'   => 'در انتظار شروع',
			'expired'  => 'پایان‌یافته',
			'disabled' => 'غیرفعال',
		);

		return $labels[ $state ] ?? 'نامشخص';
	}

	/**
	 * Whether the plan currently accepts requests.
	 *
	 * @param int $plan_id Plan ID.
	 * @return bool
	 */
	public static function accepts_requests( $plan_id ) {
		return 'live' === self::state( $plan_id );
	}

	/**
	 * Images of a plan (attachment IDs).
	 *
	 * @param int $plan_id Plan ID.
	 * @return int[]
	 */
	public static function image_ids( $plan_id ) {
		$ids = get_post_meta( (int) $plan_id, self::meta_keys()['images'], true );
		return is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array();
	}

	/**
	 * Image URLs with dimensions, primary first.
	 *
	 * @param int $plan_id Plan ID.
	 * @return array<int,array{id:int,url:string,alt:string}>
	 */
	public static function images( $plan_id ) {
		$out = array();
		foreach ( self::image_ids( $plan_id ) as $id ) {
			$src = wp_get_attachment_image_src( $id, 'large' );
			if ( ! $src ) {
				continue;
			}
			$out[] = array(
				'id'  => $id,
				'url' => (string) $src[0],
				'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			);
		}

		return $out;
	}

	/**
	 * Primary image URL for a plan.
	 *
	 * @param int $plan_id Plan ID.
	 * @return string
	 */
	public static function primary_image( $plan_id ) {
		$images = self::images( $plan_id );
		return $images ? $images[0]['url'] : LKP_URL . 'assets/img/no-image.svg';
	}

	/**
	 * Circular file descriptor.
	 *
	 * @param int $plan_id Plan ID.
	 * @return array<string,mixed>
	 */
	public static function circular( $plan_id ) {
		return self::file( $plan_id, 'circular' );
	}

	/**
	 * Product catalog file descriptor.
	 *
	 * @param int $plan_id Plan ID.
	 * @return array<string,mixed>
	 */
	public static function catalog( $plan_id ) {
		return self::file( $plan_id, 'catalog' );
	}

	/**
	 * Whether a key is a downloadable plan file.
	 *
	 * @param string $kind circular|catalog.
	 * @return bool
	 */
	public static function is_file_kind( $kind ) {
		return in_array( (string) $kind, array( 'circular', 'catalog' ), true );
	}

	/**
	 * Stored descriptor of a plan file.
	 *
	 * @param int    $plan_id Plan ID.
	 * @param string $kind    circular|catalog.
	 * @return array<string,mixed>
	 */
	public static function file( $plan_id, $kind ) {
		if ( ! self::is_file_kind( $kind ) ) {
			return array();
		}

		$keys  = self::meta_keys();
		$value = get_post_meta( (int) $plan_id, $keys[ $kind ], true );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Public download URL for a plan file.
	 *
	 * @param int    $plan_id Plan ID.
	 * @param string $kind    circular|catalog.
	 * @return string
	 */
	public static function file_url( $plan_id, $kind ) {
		$plan_id = (int) $plan_id;

		return add_query_arg(
			array(
				'action'   => 'lkp_plan_file',
				'plan'     => $plan_id,
				'file'     => $kind,
				'_wpnonce' => wp_create_nonce( 'lkp_plan_file_' . $plan_id . '_' . $kind ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Downloadable files of a plan with labels, ready for the front end.
	 *
	 * @param int $plan_id Plan ID.
	 * @return array<int,array{kind:string,label:string,url:string,name:string,size:int}>
	 */
	public static function files( $plan_id ) {
		$out    = array();
		$labels = array(
			'circular' => 'دریافت بخشنامهٔ طرح',
			'catalog'  => 'دریافت کاتالوگ محصول',
		);

		foreach ( $labels as $kind => $label ) {
			$file = self::file( $plan_id, $kind );
			if ( empty( $file['stored'] ) ) {
				continue;
			}

			$out[] = array(
				'kind'  => $kind,
				'label' => $label,
				'url'   => self::file_url( $plan_id, $kind ),
				'name'  => (string) ( $file['name'] ?? $label ),
				'size'  => (int) ( $file['size'] ?? 0 ),
			);
		}

		return $out;
	}

	/**
	 * Plans for the public section: every published plan, including disabled
	 * and finished ones, so visitors see the full picture like the reference UI.
	 *
	 * @param int $limit Maximum number of plans (0 = all).
	 * @return WP_Post[]
	 */
	public static function public_plans( $limit = 12 ) {
		$args = array(
			'post_type'              => self::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit > 0 ? $limit : -1,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
		);

		$query = new WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Dashboard counts for plans.
	 *
	 * @return array<string,int>
	 */
	public static function counts() {
		$counts = array(
			'all'      => 0,
			'live'     => 0,
			'future'   => 0,
			'expired'  => 0,
			'disabled' => 0,
		);

		foreach ( self::public_plans( 0 ) as $plan ) {
			$counts['all']++;
			$state = self::state( $plan->ID );
			if ( isset( $counts[ $state ] ) ) {
				$counts[ $state ]++;
			}
		}

		return $counts;
	}
}
