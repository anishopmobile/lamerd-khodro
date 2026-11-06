<?php
/**
 * Public helper API consumed by the theme and other plugins.
 *
 * Every function degrades gracefully when the Price Engine is inactive.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Price data for a vehicle.
 *
 * @param int $post_id Vehicle post ID.
 * @return array|null
 */
function lk_get_price( $post_id ) {
	$price = LK_Registry::prices()->get_vehicle_price( (int) $post_id );
	if ( ! is_array( $price ) ) {
		return null;
	}

	$defaults = array(
		'market_price'         => null,
		'factory_price'        => null,
		'change_amount'        => null,
		'change_percent'       => null,
		'previous_market_price' => null,
		'fetched_at'           => null,
		'fetched_ts'           => 0,
		'declared_at'          => null,
		'data_status'          => 'valid',
		'is_stale'             => false,
		'source'               => 'cafekhodro',
	);

	$price = wp_parse_args( $price, $defaults );

	if ( ! empty( $price['fetched_at'] ) && empty( $price['fetched_ts'] ) ) {
		$price['fetched_ts'] = strtotime( $price['fetched_at'] );
	}

	return $price;
}

/**
 * Price table query.
 *
 * @param array $args Query args.
 * @return array{items:array,total:int,pages:int,page:int}
 */
function lk_get_prices( $args = array() ) {
	return LK_Registry::prices()->get_prices( (array) $args );
}

/**
 * Price history between two dates.
 *
 * @param int    $post_id Vehicle post ID.
 * @param string $from    Y-m-d.
 * @param string $to      Y-m-d.
 * @return array
 */
function lk_get_history( $post_id, $from, $to ) {
	$rows = LK_Registry::prices()->get_history( (int) $post_id, (string) $from, (string) $to );
	return is_array( $rows ) ? $rows : array();
}

/**
 * Aggregate stats.
 *
 * @param array $args Filters.
 * @return array
 */
function lk_get_stats( $args = array() ) {
	return LK_Registry::prices()->get_change_stats( (array) $args );
}

/**
 * Last successful sync.
 *
 * @return array|null
 */
function lk_get_last_sync() {
	$sync = LK_Registry::prices()->get_last_sync();
	if ( ! is_array( $sync ) ) {
		return null;
	}
	if ( ! empty( $sync['finished_at'] ) ) {
		$sync['finished_ts'] = strtotime( $sync['finished_at'] );
	}
	return $sync;
}

/**
 * Biggest movers.
 *
 * @param string $direction up|down.
 * @param int    $limit     Count.
 * @return array
 */
function lk_get_top_movers( $direction = 'up', $limit = 5 ) {
	return LK_Registry::prices()->get_top_movers( $direction, (int) $limit );
}

/**
 * Whether any price exists at all.
 *
 * @return bool
 */
function lk_has_prices() {
	$stats = lk_get_stats();
	return ! empty( $stats['total'] );
}

/**
 * Format price for display.
 *
 * @param int|null $amount Amount.
 * @return string
 */
function lk_price( $amount ) {
	return LK_Format::price( $amount );
}

/**
 * Persian digits.
 *
 * @param mixed $value Value.
 * @return string
 */
function lk_num( $value ) {
	return LK_Format::digits( $value );
}

/**
 * Jalali date.
 *
 * @param int|string|null $timestamp Timestamp or date string.
 * @param string          $format    Format key.
 * @return string
 */
function lk_jalali( $timestamp = null, $format = 'date' ) {
	return LK_Format::jalali( $timestamp, $format );
}

/**
 * Price freshness label.
 *
 * @param array|null $price Price array.
 * @return string
 */
function lk_price_freshness( $price ) {
	if ( ! $price || empty( $price['fetched_ts'] ) ) {
		return '';
	}
	if ( ! empty( $price['is_stale'] ) ) {
		return 'قیمت ممکن است قدیمی باشد — آخرین دریافت: ' . LK_Format::jalali( $price['fetched_ts'], 'datetime' );
	}
	return 'آخرین به‌روزرسانی: ' . LK_Format::jalali( $price['fetched_ts'], 'datetime' );
}

/**
 * CSS class for a price direction.
 *
 * @param array|null $price Price.
 * @return string
 */
function lk_change_class( $price ) {
	if ( ! $price || ! isset( $price['change_amount'] ) || null === $price['change_amount'] || 'suspect' === ( $price['data_status'] ?? '' ) ) {
		return 'is-flat';
	}
	$amount = (int) $price['change_amount'];
	if ( $amount > 0 ) {
		return 'is-up';
	}
	if ( $amount < 0 ) {
		return 'is-down';
	}
	return 'is-flat';
}

/**
 * Specs for a vehicle.
 *
 * @param int  $post_id       Post ID.
 * @param bool $include_empty Include empty fields.
 * @return array
 */
function lk_vehicle_specs( $post_id, $include_empty = false ) {
	return LK_Fields::get_specs( (int) $post_id, $include_empty );
}

/**
 * One spec display value.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Field key.
 * @return string
 */
function lk_vehicle_spec( $post_id, $key ) {
	return LK_Fields::get_display( (int) $post_id, $key );
}

/**
 * Vehicle gallery attachment IDs.
 *
 * @param int $post_id Post ID.
 * @return int[]
 */
function lk_vehicle_gallery( $post_id ) {
	return LK_Fields::get_gallery( (int) $post_id );
}

/**
 * Top-level brands with counts.
 *
 * Sub-brands (child terms such as MVM under مدیران خودرو) are grouped inside
 * their parent brand and are not listed as separate brands.
 *
 * @param int  $limit      Max terms (0 = all).
 * @param bool $hide_empty Hide empty.
 * @return WP_Term[]
 */
function lk_get_brands( $limit = 0, $hide_empty = false ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'lk_brand',
			'parent'     => 0,
			'hide_empty' => $hide_empty,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => $limit ? (int) $limit : 0,
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Brand logo URL.
 *
 * @param int    $term_id Term ID.
 * @param string $size    Size.
 * @return string
 */
function lk_brand_logo( $term_id, $size = 'medium' ) {
	return LK_Taxonomies::brand_logo( (int) $term_id, $size );
}

/**
 * First brand name for a vehicle.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lk_vehicle_brand_name( $post_id ) {
	$terms = get_the_terms( $post_id, 'lk_brand' );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return '';
	}
	return $terms[0]->name;
}

/**
 * First brand term for a vehicle.
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function lk_vehicle_brand( $post_id ) {
	$terms = get_the_terms( $post_id, 'lk_brand' );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return null;
	}
	return $terms[0];
}

/**
 * Vehicle production status label.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lk_production_status( $post_id ) {
	return LK_Fields::production_status_label( (int) $post_id );
}

/**
 * Attach price to each post object in a query.
 *
 * @param WP_Query $query Query.
 * @return array
 */
function lk_query_with_prices( $query ) {
	$items = array();
	foreach ( $query->posts as $post ) {
		$post->lk_price = lk_get_price( $post->ID );
		$items[]        = $post;
	}
	return $items;
}

/**
 * Popular vehicles query.
 *
 * @param int $limit Count.
 * @return WP_Query
 */
function lk_popular_vehicles( $limit = 4 ) {
	return LK_Query::popular( (int) $limit );
}

/**
 * Latest vehicles query.
 *
 * @param int $limit Count.
 * @return WP_Query
 */
function lk_latest_vehicles( $limit = 8 ) {
	return LK_Query::latest( (int) $limit );
}

/**
 * Vehicle search helper.
 *
 * @param string $term  Search term.
 * @param int    $limit Max results.
 * @return WP_Query
 */
function lk_search_vehicles( $term, $limit = 10 ) {
	return LK_Query::vehicles(
		array(
			'search'         => $term,
			'posts_per_page' => $limit,
		)
	);
}

/**
 * Public nonce for AJAX.
 *
 * @return string
 */
function lk_public_nonce() {
	return wp_create_nonce( 'lk_public' );
}

/**
 * Client IP helper (proxy aware, privacy-preserving for rate limiting only).
 *
 * @return string
 */
function lk_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Source attribution markup required by the data provider.
 *
 * @return string
 */
function lk_attribution() {
	$url = 'https://cofekhodro.ir';
	return '<span class="lk-attribution">منبع قیمت‌ها: <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">کافه خودرو</a></span>';
}

/**
 * Data status label.
 *
 * @param string $status Status key.
 * @return string
 */
function lk_status_label( $status ) {
	$map = array(
		'valid'      => 'معتبر',
		'incomplete' => 'ناقص',
		'suspect'    => 'نیازمند بررسی',
	);
	return $map[ $status ] ?? 'نامشخص';
}

/**
 * Sort options used by price and vehicle listings.
 *
 * @return array<string,string>
 */
function lk_sort_options() {
	return array(
		'price_desc'   => 'بیشترین قیمت',
		'price_asc'    => 'کمترین قیمت',
		'change_desc'  => 'بیشترین افزایش',
		'change_asc'   => 'بیشترین کاهش',
		'newest'       => 'جدیدترین',
		'name'         => 'حروف الفبا',
	);
}

/**
 * Increment vehicle views once per session per vehicle.
 *
 * @return void
 */
function lk_track_vehicle_view() {
	if ( is_admin() || ! is_singular( 'lk_vehicle' ) || is_preview() || wp_doing_ajax() ) {
		return;
	}
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	$cookie  = 'lk_seen_' . $post_id;
	if ( isset( $_COOKIE[ $cookie ] ) ) {
		return;
	}

	$views = (int) get_post_meta( $post_id, '_lk_views', true );
	update_post_meta( $post_id, '_lk_views', $views + 1 );

	if ( ! headers_sent() ) {
		setcookie( $cookie, '1', time() + 6 * HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
}
add_action( 'wp', 'lk_track_vehicle_view' );

/**
 * Fallback text when the price engine is missing.
 *
 * @return string
 */
function lk_prices_unavailable_message() {
	return 'اطلاعات قیمت در حال حاضر در دسترس نیست.';
}

/**
 * Read one field of a sale listing.
 *
 * @param int    $post_id Listing ID.
 * @param string $key     price|year|km|color|gearbox|condition|city|seller|phone|vehicle.
 * @param mixed  $default Fallback when empty.
 * @return mixed
 */
function lk_listing_field( $post_id, $key, $default = '' ) {
	return class_exists( 'LK_Listings' ) ? LK_Listings::get( $post_id, $key, $default ) : $default;
}

/**
 * Whether a listing is inside its featured window right now.
 *
 * @param int $post_id Listing ID.
 * @return bool
 */
function lk_listing_is_featured( $post_id ) {
	return class_exists( 'LK_Listings' ) && LK_Listings::is_featured( $post_id );
}

/**
 * Human readable time left for a featured listing.
 *
 * @param int $post_id Listing ID.
 * @return string
 */
function lk_listing_time_left( $post_id ) {
	return class_exists( 'LK_Listings' ) ? LK_Listings::time_left( $post_id ) : '';
}

/**
 * Featured expiry timestamp.
 *
 * @param int $post_id Listing ID.
 * @return int
 */
function lk_listing_featured_until( $post_id ) {
	return class_exists( 'LK_Listings' ) ? LK_Listings::featured_until( $post_id ) : 0;
}

/**
 * Label for a stored listing option value.
 *
 * @param string $group cities|gearboxes|conditions.
 * @param string $value Stored slug.
 * @return string
 */
function lk_listing_label( $group, $value ) {
	if ( ! class_exists( 'LK_Listings' ) ) {
		return '';
	}

	$map = array(
		'cities'     => LK_Listings::cities(),
		'gearboxes'  => LK_Listings::gearboxes(),
		'conditions' => LK_Listings::conditions(),
	);

	return $map[ $group ][ $value ] ?? '';
}
