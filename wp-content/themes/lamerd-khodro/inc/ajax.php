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

/**
 * Format a price for the instant-search dropdown.
 *
 * @param int|null $amount Amount in toman.
 * @return string
 */
function lk_search_price_text( $amount ) {
	if ( null === $amount || '' === $amount || ! class_exists( 'LK_Format' ) ) {
		return '';
	}
	return LK_Format::price_short( (int) $amount );
}

/**
 * Image (or monogram) data for one instant-search row.
 *
 * @param int    $post_id Post ID.
 * @param string $title   Row title.
 * @return array{thumb:string,initial:string}
 */
function lk_search_thumb( $post_id, $title ) {
	$thumb = get_the_post_thumbnail_url( (int) $post_id, 'thumbnail' );

	return array(
		'thumb'   => $thumb ? $thumb : '',
		'initial' => function_exists( 'lk_monogram' ) ? lk_monogram( $title ) : '',
	);
}

/**
 * AJAX: grouped instant search (vehicles, brands, sale listings).
 *
 * @return void
 */
function lk_ajax_live_search() {
	check_ajax_referer( 'lk_public', 'nonce', false );

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
	$term = trim( $term );

	if ( mb_strlen( $term, 'UTF-8' ) < 2 ) {
		wp_send_json_success(
			array(
				'term'   => $term,
				'groups' => array(),
			)
		);
	}

	$groups = array();

	/* Brand matches first: a brand name also means "show me its models". */
	$brands = get_terms(
		array(
			'taxonomy'   => 'lk_brand',
			'hide_empty' => true,
			'number'     => 3,
			'name__like' => $term,
		)
	);
	$brands = is_wp_error( $brands ) ? array() : $brands;

	/* Vehicles ---------------------------------------------------------- */
	if ( function_exists( 'lk_get_prices' ) ) {
		$found = array();

		$result = lk_get_prices(
			array(
				'search'   => $term,
				'per_page' => 5,
				'sort'     => 'price_desc',
			)
		);
		foreach ( (array) $result['items'] as $item ) {
			$found[ (int) $item['post_id'] ] = $item;
		}

		if ( $brands && count( $found ) < 5 ) {
			foreach ( $brands as $brand ) {
				$extra = lk_get_prices(
					array(
						'brand'    => $brand->slug,
						'per_page' => 5,
						'sort'     => 'price_desc',
					)
				);
				foreach ( (array) $extra['items'] as $item ) {
					$found[ (int) $item['post_id'] ] = $item;
					if ( count( $found ) >= 5 ) {
						break;
					}
				}
				if ( count( $found ) >= 5 ) {
					break;
				}
			}
		}

		$items = array();
		foreach ( array_slice( $found, 0, 5, true ) as $item ) {
			$thumb   = lk_search_thumb( $item['post_id'], $item['title'] );
			$items[] = array(
				'title'   => $item['title'],
				'meta'    => $item['brand'],
				'price'   => lk_search_price_text( $item['market_price'] ),
				'url'     => $item['url'],
				'thumb'   => $thumb['thumb'],
				'initial' => $thumb['initial'],
			);
		}

		if ( $items ) {
			$groups[] = array(
				'type'  => 'vehicles',
				'label' => 'خودروها',
				'all'   => array(
					'label' => 'همه خودروها',
					'url'   => add_query_arg( 'search', $term, lk_page_url( 'vehicles' ) ),
				),
				'items' => $items,
			);
		}
	}

	/* Brands ------------------------------------------------------------ */
	if ( $brands ) {
		$items = array();
		foreach ( $brands as $brand ) {
			$link = get_term_link( $brand );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$items[] = array(
				'title'   => $brand->name,
				'meta'    => sprintf( '%s خودرو', lk_num( (int) $brand->count ) ),
				'price'   => '',
				'url'     => $link,
				'thumb'   => '',
				'initial' => function_exists( 'lk_monogram' ) ? lk_monogram( $brand->name ) : '',
			);
		}

		if ( $items ) {
			$groups[] = array(
				'type'  => 'brands',
				'label' => 'برندها',
				'all'   => array(
					'label' => 'همه برندها',
					'url'   => lk_page_url( 'brands' ),
				),
				'items' => $items,
			);
		}
	}

	/* Sale listings ------------------------------------------------------ */
	if ( post_type_exists( 'lk_listing' ) ) {
		$listings = new WP_Query(
			array(
				'post_type'           => 'lk_listing',
				'post_status'         => 'publish',
				's'                   => $term,
				'posts_per_page'      => 3,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$items = array();
		foreach ( $listings->posts as $listing ) {
			$price = function_exists( 'lk_listing_field' ) ? (int) lk_listing_field( $listing->ID, 'price', 0 ) : 0;
			$city  = function_exists( 'lk_listing_field' ) ? (string) lk_listing_field( $listing->ID, 'city', '' ) : '';
			$thumb = lk_search_thumb( $listing->ID, $listing->post_title );

			$items[] = array(
				'title'   => get_the_title( $listing ),
				'meta'    => $city && function_exists( 'lk_listing_label' ) ? lk_listing_label( 'cities', $city ) : 'آگهی فروش',
				'price'   => $price ? lk_search_price_text( $price ) : '',
				'url'     => get_permalink( $listing ),
				'thumb'   => $thumb['thumb'],
				'initial' => $thumb['initial'],
			);
		}

		if ( $items ) {
			$groups[] = array(
				'type'  => 'listings',
				'label' => 'آگهی‌های فروش',
				'all'   => array(
					'label' => 'همه آگهی‌ها',
					'url'   => get_post_type_archive_link( 'lk_listing' ),
				),
				'items' => $items,
			);
		}
	}

	wp_send_json_success(
		array(
			'term'   => $term,
			'groups' => $groups,
			'allUrl' => home_url( '/?s=' . rawurlencode( $term ) ),
		)
	);
}
add_action( 'wp_ajax_lk_live_search', 'lk_ajax_live_search' );
add_action( 'wp_ajax_nopriv_lk_live_search', 'lk_ajax_live_search' );
