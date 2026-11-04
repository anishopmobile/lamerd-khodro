<?php
/**
 * Public REST API (lamerd/v1).
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Rest
 */
class LK_Rest {

	/**
	 * Namespace.
	 */
	const NS = 'lamerd/v1';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Route registration.
	 *
	 * @return void
	 */
	public static function register_routes() {
		$public = array( __CLASS__, 'permission' );

		register_rest_route(
			self::NS,
			'/vehicles',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'vehicles' ),
				'permission_callback' => $public,
				'args'                => array(
					'page'      => array( 'sanitize_callback' => 'absint' ),
					'per_page'  => array( 'sanitize_callback' => 'absint' ),
					'search'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'brand'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'body_type' => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'class'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'year_from' => array( 'sanitize_callback' => 'absint' ),
					'year_to'   => array( 'sanitize_callback' => 'absint' ),
					'price_min' => array( 'sanitize_callback' => 'absint' ),
					'price_max' => array( 'sanitize_callback' => 'absint' ),
					'availability' => array( 'sanitize_callback' => 'sanitize_key' ),
					'sort'      => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/vehicles/(?P<id>[A-Za-z0-9\-_]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'vehicle' ),
				'permission_callback' => $public,
				'args'                => array(
					'id' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/vehicles/(?P<id>[A-Za-z0-9\-_]+)/history',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'history' ),
				'permission_callback' => $public,
				'args'                => array(
					'id'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'range' => array( 'sanitize_callback' => 'sanitize_key' ),
					'from'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'to'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/brands',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'brands' ),
				'permission_callback' => $public,
			)
		);

		register_rest_route(
			self::NS,
			'/prices',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'prices' ),
				'permission_callback' => $public,
				'args'                => array(
					'page'     => array( 'sanitize_callback' => 'absint' ),
					'per_page' => array( 'sanitize_callback' => 'absint' ),
					'brand'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'search'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'min'      => array( 'sanitize_callback' => 'absint' ),
					'max'      => array( 'sanitize_callback' => 'absint' ),
					'sort'     => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/compare',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'compare' ),
				'permission_callback' => $public,
				'args'                => array(
					'ids' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/news',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'news' ),
				'permission_callback' => $public,
				'args'                => array(
					'page'     => array( 'sanitize_callback' => 'absint' ),
					'per_page' => array( 'sanitize_callback' => 'absint' ),
					'category' => array( 'sanitize_callback' => 'sanitize_title' ),
					'search'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'search' ),
				'permission_callback' => $public,
				'args'                => array(
					'q' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/meta',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'site_meta' ),
				'permission_callback' => $public,
			)
		);
	}

	/**
	 * Public read permission with per-IP rate limiting.
	 *
	 * @return true|WP_Error
	 */
	public static function permission() {
		$ip   = lk_client_ip();
		$key  = 'lk_rl_' . md5( $ip . '|rest' );
		$data = get_transient( $key );

		if ( ! is_array( $data ) ) {
			$data = array(
				'count' => 0,
				'start' => time(),
			);
		}
		if ( ( time() - (int) $data['start'] ) > MINUTE_IN_SECONDS ) {
			$data = array(
				'count' => 0,
				'start' => time(),
			);
		}

		++$data['count'];
		set_transient( $key, $data, MINUTE_IN_SECONDS * 2 );

		if ( $data['count'] > 60 ) {
			$response = new WP_Error(
				'lk_rate_limited',
				'تعداد درخواست‌ها از حد مجاز بیشتر است.',
				array( 'status' => 429 )
			);
			return $response;
		}

		return true;
	}

	/**
	 * Standard response envelope.
	 *
	 * @param array $data Data.
	 * @param array $meta Meta.
	 * @return WP_REST_Response
	 */
	private static function respond( $data, $meta = array() ) {
		$meta = wp_parse_args(
			$meta,
			array(
				'generated_at' => gmdate( 'c', current_time( 'timestamp' ) ),
				'version'      => 'v1',
			)
		);

		$response = new WP_REST_Response(
			array(
				'data' => $data,
				'meta' => $meta,
			)
		);
		$response->header( 'Cache-Control', 'public, max-age=60' );
		return $response;
	}

	/**
	 * Resolve a vehicle from ID or slug.
	 *
	 * @param string $id ID or slug.
	 * @return WP_Post|null
	 */
	private static function resolve_vehicle( $id ) {
		if ( ctype_digit( (string) $id ) ) {
			$post = get_post( (int) $id );
		} else {
			$post = get_page_by_path( sanitize_title( $id ), OBJECT, 'lk_vehicle' );
		}
		if ( ! $post || 'lk_vehicle' !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		return $post;
	}

	/**
	 * Public vehicle shape.
	 *
	 * @param WP_Post $post       Post.
	 * @param bool    $with_specs Include specs and gallery.
	 * @return array
	 */
	public static function vehicle_data( $post, $with_specs = false ) {
		$brands = wp_get_object_terms( $post->ID, 'lk_brand' );
		$brand  = null;
		if ( ! is_wp_error( $brands ) && $brands ) {
			$brand = array(
				'id'   => $brands[0]->term_id,
				'name' => $brands[0]->name,
				'slug' => $brands[0]->slug,
			);
		}

		$bodies = wp_get_object_terms( $post->ID, 'lk_body_type', array( 'fields' => 'names' ) );

		$data = array(
			'id'                => $post->ID,
			'title'             => get_the_title( $post ),
			'slug'              => $post->post_name,
			'url'               => get_permalink( $post ),
			'brand'             => $brand,
			'body_type'         => ( ! is_wp_error( $bodies ) && $bodies ) ? $bodies[0] : null,
			'year'              => (int) LK_Fields::get( $post->ID, 'year' ) ?: null,
			'production_status' => LK_Fields::production_status_label( $post->ID ),
			'production_stopped' => 'stopped' === LK_Fields::get( $post->ID, 'production_status' ),
			'excerpt'           => get_the_excerpt( $post ),
			'thumbnail'         => get_the_post_thumbnail_url( $post, 'medium_large' ) ?: null,
			'prices'            => lk_get_price( $post->ID ),
			'updated_at'        => gmdate( 'c', get_post_timestamp( $post, 'modified' ) ),
			'updated_at_jalali' => LK_Format::jalali( get_post_timestamp( $post, 'modified' ), 'long' ),
			'views'             => (int) get_post_meta( $post->ID, '_lk_views', true ),
		);

		if ( $with_specs ) {
			$data['specs']    = LK_Fields::get_specs( $post->ID );
			$data['gallery']  = array_map(
				static function ( $attachment_id ) {
					return array(
						'id'  => $attachment_id,
						'url' => wp_get_attachment_image_url( $attachment_id, 'large' ),
						'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
					);
				},
				LK_Fields::get_gallery( $post->ID )
			);
			$data['specs_completion'] = LK_Fields::completion( $post->ID );
		}

		return $data;
	}

	/**
	 * GET /vehicles
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function vehicles( $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 50, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 20 ) ) );

		$args = array(
			'paged'          => $page,
			'posts_per_page' => $per_page,
			'brand'          => $request->get_param( 'brand' ) ? explode( ',', (string) $request->get_param( 'brand' ) ) : array(),
			'body_type'      => $request->get_param( 'body_type' ) ? explode( ',', (string) $request->get_param( 'body_type' ) ) : array(),
			'class'          => $request->get_param( 'class' ) ? explode( ',', (string) $request->get_param( 'class' ) ) : array(),
			'year_from'      => (int) $request->get_param( 'year_from' ),
			'year_to'        => (int) $request->get_param( 'year_to' ),
			'price_min'      => (int) $request->get_param( 'price_min' ),
			'price_max'      => (int) $request->get_param( 'price_max' ),
			'availability'   => (string) $request->get_param( 'availability' ),
			'search'         => (string) $request->get_param( 'search' ),
			'sort'           => (string) ( $request->get_param( 'sort' ) ?: 'newest' ),
		);

		$query = LK_Query::vehicles( $args );
		$items = array();
		foreach ( $query->posts as $post ) {
			$items[] = self::vehicle_data( $post );
		}

		return self::respond(
			$items,
			array(
				'page'     => $page,
				'per_page' => $per_page,
				'total'    => (int) $query->found_posts,
				'pages'    => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * GET /vehicles/{id}
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function vehicle( $request ) {
		$post = self::resolve_vehicle( $request->get_param( 'id' ) );
		if ( ! $post ) {
			return new WP_Error( 'lk_not_found', 'خودرو یافت نشد.', array( 'status' => 404 ) );
		}

		$data = self::vehicle_data( $post, true );

		$similar = LK_Query::related( $post->ID, 4 );
		$data['similar'] = array();
		foreach ( $similar->posts as $related ) {
			$data['similar'][] = array(
				'id'    => $related->ID,
				'title' => get_the_title( $related ),
				'url'   => get_permalink( $related ),
			);
		}

		return self::respond( $data );
	}

	/**
	 * GET /vehicles/{id}/history
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function history( $request ) {
		$post = self::resolve_vehicle( $request->get_param( 'id' ) );
		if ( ! $post ) {
			return new WP_Error( 'lk_not_found', 'خودرو یافت نشد.', array( 'status' => 404 ) );
		}

		$range = (string) ( $request->get_param( 'range' ) ?: '30' );
		$from  = (string) $request->get_param( 'from' );
		$to    = (string) $request->get_param( 'to' );

		if ( $from && $to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			$start = $from;
			$end   = $to;
		} else {
			$days = 'all' === $range ? 3650 : max( 1, min( 3650, (int) $range ) );
			$now  = current_time( 'timestamp' );
			$start = gmdate( 'Y-m-d', $now - ( $days * DAY_IN_SECONDS ) );
			$end   = gmdate( 'Y-m-d', $now );
		}

		if ( $start > $end ) {
			list( $start, $end ) = array( $end, $start );
		}

		$rows   = lk_get_history( $post->ID, $start, $end );
		$labels = array();
		$market = array();
		$factory = array();
		$points = array();
		$valid  = 0;

		foreach ( $rows as $row ) {
			$timestamp = strtotime( $row['recorded_at'] );
			$label     = LK_Format::jalali( $timestamp, 'short' );
			$labels[]  = $label;
			$market[]  = isset( $row['market_price'] ) && null !== $row['market_price'] ? (int) $row['market_price'] : null;
			$factory[] = isset( $row['factory_price'] ) && null !== $row['factory_price'] ? (int) $row['factory_price'] : null;
			if ( null !== $market[ count( $market ) - 1 ] ) {
				++$valid;
			}
			$points[] = array(
				'date'    => gmdate( 'Y-m-d', $timestamp ),
				'jalali'  => $label,
				'time'    => LK_Format::jalali( $timestamp, 'datetime' ),
				'market'  => end( $market ),
				'factory' => end( $factory ),
				'type'    => $row['record_type'] ?? 'change',
				'status'  => $row['data_status'] ?? 'valid',
			);
		}

		$data = array(
			'vehicle_id'   => $post->ID,
			'currency'     => 'toman',
			'range'        => array(
				'from' => $start,
				'to'   => $end,
			),
			'labels'       => $labels,
			'series'       => array(
				'market'  => $market,
				'factory' => $factory,
			),
			'points'       => $points,
			'count'        => count( $points ),
			'insufficient' => $valid < 2,
			'note'         => $valid < 2 ? 'تاریخچه قیمت از زمان فعال‌شدن سامانه در لامرد خودرو ثبت می‌شود؛ برای رسم نمودار به حداقل دو ثبت معتبر نیاز است.' : '',
		);

		return self::respond( $data );
	}

	/**
	 * GET /brands
	 *
	 * @return WP_REST_Response
	 */
	public static function brands() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'lk_brand',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		$items = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$items[] = array(
					'id'      => $term->term_id,
					'name'    => $term->name,
					'name_en' => (string) LK_Taxonomies::brand_meta( $term->term_id, 'name_en' ),
					'slug'    => $term->slug,
					'country' => (string) LK_Taxonomies::brand_meta( $term->term_id, 'country' ),
					'logo'    => LK_Taxonomies::brand_logo( $term->term_id, 'medium' ),
					'count'   => (int) $term->count,
					'url'     => get_term_link( $term ),
				);
			}
		}

		return self::respond( $items );
	}

	/**
	 * GET /prices
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function prices( $request ) {
		$args = array(
			'page'     => max( 1, (int) $request->get_param( 'page' ) ),
			'per_page' => min( 50, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 20 ) ) ),
			'brand'    => $request->get_param( 'brand' ) ? explode( ',', (string) $request->get_param( 'brand' ) ) : array(),
			'search'   => (string) $request->get_param( 'search' ),
			'min'      => (int) $request->get_param( 'min' ),
			'max'      => (int) $request->get_param( 'max' ),
			'sort'     => (string) ( $request->get_param( 'sort' ) ?: 'price_desc' ),
		);

		$result = lk_get_prices( $args );

		return self::respond(
			$result['items'],
			array(
				'page'     => $result['page'],
				'per_page' => $args['per_page'],
				'total'    => $result['total'],
				'pages'    => $result['pages'],
				'unit'     => 'toman',
			)
		);
	}

	/**
	 * GET /compare
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function compare( $request ) {
		$ids = array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'ids' ) ) ) );
		$data = LK_Compare::rows( $ids );
		return self::respond( $data );
	}

	/**
	 * GET /news
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function news( $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 50, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 12 ) ) );

		$query_args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'paged'          => $page,
			'posts_per_page' => $per_page,
		);
		if ( $request->get_param( 'category' ) ) {
			$query_args['category_name'] = sanitize_title( (string) $request->get_param( 'category' ) );
		}
		if ( $request->get_param( 'search' ) ) {
			$query_args['s'] = sanitize_text_field( (string) $request->get_param( 'search' ) );
		}

		$query = new WP_Query( $query_args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			$items[] = array(
				'id'            => $post->ID,
				'title'         => get_the_title( $post ),
				'excerpt'       => wp_strip_all_tags( get_the_excerpt( $post ) ),
				'url'           => get_permalink( $post ),
				'date'          => gmdate( 'c', get_post_timestamp( $post ) ),
				'date_jalali'   => LK_Format::jalali( get_post_timestamp( $post ) ),
				'thumbnail'     => get_the_post_thumbnail_url( $post, 'medium_large' ) ?: null,
				'categories'    => $categories,
			);
		}

		return self::respond(
			$items,
			array(
				'page'     => $page,
				'per_page' => $per_page,
				'total'    => (int) $query->found_posts,
				'pages'    => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * GET /search
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function search( $request ) {
		$term = mb_substr( sanitize_text_field( (string) $request->get_param( 'q' ) ), 0, 80 );

		$items = array();
		if ( mb_strlen( $term ) >= 2 ) {
			foreach ( LK_Query::find_vehicle_ids( $term, 10 ) as $id ) {
				$price   = lk_get_price( $id );
				$items[] = array(
					'type'  => 'vehicle',
					'id'    => $id,
					'title' => get_the_title( $id ),
					'url'   => get_permalink( $id ),
					'price' => $price['market_price'] ?? null,
				);
			}
		}

		return self::respond(
			$items,
			array(
				'query' => $term,
			)
		);
	}

	/**
	 * GET /meta
	 *
	 * @return WP_REST_Response
	 */
	public static function site_meta() {
		$last = lk_get_last_sync();

		return self::respond(
			array(
				'name'        => get_bloginfo( 'name' ),
				'description' => get_bloginfo( 'description' ),
				'url'         => home_url( '/' ),
				'lang'        => 'fa-IR',
				'direction'   => 'rtl',
				'last_sync'   => $last,
				'price_unit'  => 'toman',
				'attribution' => array(
					'name' => 'کافه خودرو',
					'url'  => 'https://cofekhodro.ir',
				),
			)
		);
	}
}
