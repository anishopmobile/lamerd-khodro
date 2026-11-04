<?php
/**
 * SEO output: description, Open Graph, canonical, robots and structured data.
 *
 * Structured data deliberately limited to facts the site actually holds:
 * WebSite, Organization, BreadcrumbList and Article. No vehicle schema is
 * emitted until complete spec data exists.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Best available description for the current view.
 *
 * @return string
 */
function lk_meta_description() {
	$description = '';

	if ( is_front_page() ) {
		$description = get_bloginfo( 'description' );
		if ( ! $description ) {
			$description = 'قیمت روز خودرو، مشخصات فنی، تاریخچه قیمت و مقایسه خودروهای داخلی و وارداتی در لامرد خودرو.';
		}
	} elseif ( is_singular() ) {
		$description = get_the_excerpt();
		if ( ! $description ) {
			$description = wp_strip_all_tags( get_the_content() );
		}
	} elseif ( is_tax( 'lk_brand' ) ) {
		$description = sprintf( 'قیمت روز و مشخصات خودروهای %s در لامرد خودرو؛ مشاهده قیمت بازار، کارخانه و تغییرات قیمت.', single_term_title( '', false ) );
	} elseif ( is_post_type_archive( 'lk_vehicle' ) ) {
		$description = 'فهرست خودروهای داخلی و وارداتی با قیمت روز، وضعیت تولید و مشخصات فنی.';
	} elseif ( is_page_template( 'page-templates/page-prices.php' ) ) {
		$description = 'جدول قیمت روز خودروهای داخلی و وارداتی؛ قیمت بازار، قیمت کارخانه، اختلاف قیمت و درصد تغییر.';
	} elseif ( is_page_template( 'page-templates/page-compare.php' ) ) {
		$description = 'مقایسه دو تا چهار خودرو در قیمت، مشخصات فنی، ابعاد و امکانات.';
	} elseif ( is_archive() ) {
		$description = wp_strip_all_tags( get_the_archive_description() );
		if ( ! $description ) {
			$description = get_the_archive_title() . ' — لامرد خودرو';
		}
	} elseif ( is_search() ) {
		$description = 'نتایج جستجو در لامرد خودرو.';
	}

	$description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $description ) ) );

	if ( mb_strlen( $description, 'UTF-8' ) > 160 ) {
		$description = mb_substr( $description, 0, 157, 'UTF-8' ) . '…';
	}

	return $description;
}

/**
 * Current canonical URL.
 *
 * @return string
 */
function lk_canonical_url() {
	if ( is_singular() ) {
		$canonical = wp_get_canonical_url();
		if ( $canonical ) {
			return $canonical;
		}
	}

	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$link = get_term_link( get_queried_object() );
		return is_wp_error( $link ) ? '' : $link;
	}

	if ( is_post_type_archive() ) {
		return (string) get_post_type_archive_link( get_post_type() );
	}

	if ( is_home() ) {
		return lk_blog_url();
	}

	if ( is_search() ) {
		return get_search_link();
	}

	global $wp;
	return home_url( add_query_arg( array(), $wp->request ? '/' . $wp->request . '/' : '/' ) );
}

/**
 * Head meta.
 *
 * @return void
 */
function lk_seo_head() {
	$description = lk_meta_description();
	$canonical   = lk_canonical_url();
	$title       = wp_get_document_title();
	$image       = '';

	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( get_queried_object_id(), 'lk-wide' );
	}

	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	}

	if ( $canonical && ! is_search() ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
	}

	echo '<meta property="og:locale" content="fa_IR" />' . "\n";
	echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '" />' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	if ( $description ) {
		echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
	}
	if ( $canonical ) {
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
	}
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
	if ( $description ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'lk_seo_head', 3 );

/**
 * Robots directives for thin or duplicate views.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function lk_robots( $robots ) {
	if ( is_search() || is_404() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}

	if ( is_page_template( 'page-templates/page-prices.php' ) || is_post_type_archive( 'lk_vehicle' ) ) {
		$paged = max( (int) get_query_var( 'paged' ), (int) ( $_GET['page'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $paged > 1 || ! empty( $_GET['sort'] ) || ! empty( $_GET['brand'] ) || ! empty( $_GET['search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
	}

	return $robots;
}
add_filter( 'wp_robots', 'lk_robots' );

/**
 * Structured data graph.
 *
 * @return void
 */
function lk_structured_data() {
	$graph = array();

	$organization = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo = wp_get_attachment_image_url( (int) $logo_id, 'full' );
		if ( $logo ) {
			$organization['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			);
		}
	}

	$graph[] = $organization;

	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => home_url( '/#website' ),
		'url'             => home_url( '/' ),
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'inLanguage'      => 'fa-IR',
		'publisher'       => array( '@id' => home_url( '/#organization' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	if ( ! is_front_page() ) {
		$crumbs = array();
		$crumbs[] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'خانه',
			'item'     => home_url( '/' ),
		);

		if ( is_singular( 'lk_vehicle' ) ) {
			$position = 2;
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => 'خودروها',
				'item'     => get_post_type_archive_link( 'lk_vehicle' ),
			);
			$brand = lk_vehicle_brand( get_queried_object_id() );
			if ( $brand ) {
				$link = get_term_link( $brand );
				if ( ! is_wp_error( $link ) ) {
					$crumbs[] = array(
						'@type'    => 'ListItem',
						'position' => $position++,
						'name'     => $brand->name,
						'item'     => $link,
					);
				}
			}
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => get_the_title(),
			);
		} elseif ( is_singular( 'post' ) ) {
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => 'اخبار و مقالات',
				'item'     => lk_blog_url(),
			);
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => get_the_title(),
			);
		} elseif ( is_page() ) {
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => get_the_title(),
			);
		}

		if ( count( $crumbs ) > 1 ) {
			$graph[] = array(
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $crumbs,
			);
		}
	}

	if ( is_singular( 'post' ) ) {
		$graph[] = array(
			'@type'            => 'Article',
			'headline'         => get_the_title(),
			'datePublished'    => get_the_date( DATE_W3C ),
			'dateModified'     => get_the_modified_date( DATE_W3C ),
			'inLanguage'       => 'fa-IR',
			'mainEntityOfPage' => array( '@id' => get_permalink() ),
			'author'           => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			),
			'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		);
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'lk_structured_data', 20 );

/**
 * Preconnect is unnecessary (all assets local); clean up generator noise.
 *
 * @return void
 */
function lk_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'init', 'lk_clean_head' );
