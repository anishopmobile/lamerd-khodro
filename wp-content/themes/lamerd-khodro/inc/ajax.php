<?php
/**
 * Front-end AJAX endpoints: price table refresh and comparison data.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Convert a price filter value (entered in million Toman) to Toman.
 *
 * Values of 100000 or more are treated as already being in Toman.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function lk_price_input_to_toman( $value ) {
	$value = absint( $value );
	if ( $value > 0 && $value < 100000 ) {
		$value *= 1000000;
	}
	return $value;
}

/**
 * Read and sanitize the price filter payload.
 *
 * @param array $source Raw source ($_GET or $_POST).
 * @return array
 */
function lk_read_price_filters( $source ) {
	$brand = array();
	if ( isset( $source['brand'] ) ) {
		$brand = is_array( $source['brand'] ) ? $source['brand'] : explode( ',', (string) $source['brand'] );
	}

	$filters = array(
		'page'         => max( 1, (int) ( $source['page'] ?? $source['pg'] ?? 1 ) ),
		'per_page'     => 12,
		'search'       => sanitize_text_field( wp_unslash( $source['search'] ?? '' ) ),
		'brand'        => array_filter( array_map( 'sanitize_title', $brand ) ),
		'sort'         => sanitize_key( $source['sort'] ?? 'price_desc' ),
		'price_min'    => lk_price_input_to_toman( $source['price_min'] ?? 0 ),
		'price_max'    => lk_price_input_to_toman( $source['price_max'] ?? 0 ),
		'availability' => sanitize_key( $source['availability'] ?? '' ),
	);

	$allowed_sorts = array_keys( lk_sort_options() );
	if ( ! in_array( $filters['sort'], $allowed_sorts, true ) ) {
		$filters['sort'] = 'price_desc';
	}

	if ( ! in_array( $filters['availability'], array( 'available', 'stopped' ), true ) ) {
		$filters['availability'] = '';
	}

	return $filters;
}

/**
 * Render the table rows for a result set.
 *
 * @param array $items Items.
 * @return string
 */
function lk_render_price_rows( $items ) {
	ob_start();
	foreach ( $items as $item ) {
		get_template_part( 'template-parts/price/row', null, $item );
	}
	return (string) ob_get_clean();
}

/**
 * Render the mobile cards for a result set.
 *
 * @param array $items Items.
 * @return string
 */
function lk_render_price_cards( $items ) {
	ob_start();
	foreach ( $items as $item ) {
		get_template_part( 'template-parts/price/card', null, $item );
	}
	return (string) ob_get_clean();
}

/**
 * Render pagination markup (works without JS, enhanced by JS).
 *
 * @param int $current Current page.
 * @param int $pages   Total pages.
 * @return string
 */
function lk_render_ajax_pagination( $current, $pages ) {
	if ( $pages <= 1 ) {
		return '';
	}

	$base = remove_query_arg( array( 'pg', 'page' ) );
	$out  = '<nav class="lk-pagination" aria-label="صفحه‌بندی قیمت‌ها">';
	$from = max( 1, $current - 2 );
	$to   = min( $pages, $current + 2 );

	$make = static function ( $page, $label, $class = '', $disabled = false ) use ( $base ) {
		if ( $disabled ) {
			return '<span class="lk-page-btn is-disabled">' . esc_html( $label ) . '</span>';
		}
		return sprintf(
			'<a class="lk-page-btn %s" href="%s" data-page="%d">%s</a>',
			esc_attr( $class ),
			esc_url( add_query_arg( 'pg', $page, $base ) ),
			(int) $page,
			esc_html( $label )
		);
	};

	$out .= $make( max( 1, $current - 1 ), 'قبلی', '', $current <= 1 );

	for ( $i = $from; $i <= $to; $i++ ) {
		$out .= $make( $i, lk_num( $i ), $i === $current ? 'is-current' : '' );
	}

	$out .= $make( min( $pages, $current + 1 ), 'بعدی', '', $current >= $pages );
	$out .= '</nav>';

	return $out;
}

/**
 * AJAX: price table results.
 *
 * @return void
 */
function lk_ajax_price_results() {
	check_ajax_referer( 'lk_public', 'nonce', false );

	if ( ! function_exists( 'lk_get_prices' ) ) {
		wp_send_json_error( array( 'message' => 'اطلاعات قیمت در دسترس نیست.' ), 503 );
	}

	$filters = lk_read_price_filters( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
	$result  = lk_get_prices( $filters );
	$stats   = lk_get_stats();

	wp_send_json_success(
		array(
			'rows'       => lk_render_price_rows( $result['items'] ),
			'cards'      => lk_render_price_cards( $result['items'] ),
			'pagination' => lk_render_ajax_pagination( (int) $result['page'], (int) $result['pages'] ),
			'total'      => (int) $result['total'],
			'pages'      => (int) $result['pages'],
			'page'       => (int) $result['page'],
			'countLabel' => sprintf( '%s خودرو', lk_num( (int) $result['total'] ) ),
			'stats'      => $stats,
		)
	);
}
add_action( 'wp_ajax_lk_price_results', 'lk_ajax_price_results' );
add_action( 'wp_ajax_nopriv_lk_price_results', 'lk_ajax_price_results' );

/**
 * AJAX: comparison payload.
 *
 * @return void
 */
function lk_ajax_compare() {
	check_ajax_referer( 'lk_public', 'nonce', false );

	if ( ! class_exists( 'LK_Compare' ) ) {
		wp_send_json_error( array( 'message' => 'مقایسه در دسترس نیست.' ), 503 );
	}

	$ids = array_filter( array_map( 'absint', (array) ( $_POST['ids'] ?? array() ) ) );
	$ids = array_values( array_unique( $ids ) );

	if ( count( $ids ) < LK_Compare::MIN || count( $ids ) > LK_Compare::MAX ) {
		wp_send_json_error( array( 'message' => 'دو تا چهار خودرو انتخاب کنید.' ), 400 );
	}

	$data = LK_Compare::rows( $ids );

	if ( ! empty( $data['error'] ) ) {
		wp_send_json_error( array( 'message' => $data['error'] ), 400 );
	}

	ob_start();
	get_template_part( 'template-parts/compare/table', null, $data );
	$html = (string) ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_lk_compare', 'lk_ajax_compare' );
add_action( 'wp_ajax_nopriv_lk_compare', 'lk_ajax_compare' );
