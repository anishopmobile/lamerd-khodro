<?php
/**
 * Public AJAX endpoints returning data (HTML fragments live in the theme).
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Ajax
 */
class LK_Ajax {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_lk_search', array( __CLASS__, 'search' ) );
		add_action( 'wp_ajax_nopriv_lk_search', array( __CLASS__, 'search' ) );
		add_action( 'wp_ajax_lk_compare_data', array( __CLASS__, 'compare' ) );
		add_action( 'wp_ajax_nopriv_lk_compare_data', array( __CLASS__, 'compare' ) );
	}

	/**
	 * Verify the public nonce and throttle per IP.
	 *
	 * @return bool
	 */
	private static function guard() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'lk_public' ) ) {
			wp_send_json_error( array( 'message' => 'درخواست نامعتبر است.' ), 403 );
		}

		$key   = 'lk_rl_' . md5( lk_client_ip() . '|ajax' );
		$count = (int) get_transient( $key );
		if ( $count > 90 ) {
			wp_send_json_error( array( 'message' => 'تعداد درخواست‌ها زیاد است. کمی بعد تلاش کنید.' ), 429 );
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * Live search across vehicles and posts.
	 *
	 * @return void
	 */
	public static function search() {
		self::guard();

		$term  = isset( $_REQUEST['q'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['q'] ) ) : '';
		$term  = mb_substr( $term, 0, 80 );
		$items = array();

		if ( mb_strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'items' => array(), 'query' => $term ) );
		}

		$vehicle_ids = LK_Query::find_vehicle_ids( $term, 6 );
		foreach ( $vehicle_ids as $id ) {
			$price = lk_get_price( $id );
			$items[] = array(
				'type'  => 'vehicle',
				'id'    => $id,
				'title' => get_the_title( $id ),
				'url'   => get_permalink( $id ),
				'meta'  => $price && $price['market_price'] ? LK_Format::price( $price['market_price'] ) . ' تومان' : 'قیمت ناموجود',
				'label' => 'خودرو',
			);
		}

		$posts = get_posts(
			array(
				'post_type'        => array( 'post' ),
				's'                => $term,
				'posts_per_page'   => 5,
				'post_status'      => 'publish',
				'suppress_filters' => false,
			)
		);
		foreach ( $posts as $post ) {
			$items[] = array(
				'type'  => 'post',
				'id'    => $post->ID,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'meta'  => LK_Format::jalali( get_post_timestamp( $post ) ),
				'label' => 'مقاله',
			);
		}

		$brands = get_terms(
			array(
				'taxonomy'   => 'lk_brand',
				'hide_empty' => true,
				'search'     => $term,
				'number'     => 3,
			)
		);
		if ( ! is_wp_error( $brands ) ) {
			foreach ( $brands as $brand ) {
				$items[] = array(
					'type'  => 'brand',
					'id'    => $brand->term_id,
					'title' => $brand->name,
					'url'   => get_term_link( $brand ),
					'meta'  => LK_Format::number( $brand->count ) . ' خودرو',
					'label' => 'برند',
				);
			}
		}

		wp_send_json_success(
			array(
				'items' => $items,
				'query' => $term,
				'all'   => add_query_arg( 's', $term, home_url( '/' ) ),
			)
		);
	}

	/**
	 * Comparison payload.
	 *
	 * @return void
	 */
	public static function compare() {
		self::guard();

		$ids = isset( $_REQUEST['ids'] ) ? (array) $_REQUEST['ids'] : array();
		$ids = array_filter( array_map( 'absint', $ids ) );

		wp_send_json_success( LK_Compare::rows( $ids ) );
	}
}
