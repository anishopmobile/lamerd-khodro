<?php
/**
 * Front page: editorial automotive layout.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/home/hero' );
get_template_part( 'template-parts/home/sale-listings' );
get_template_part( 'template-parts/home/price-board' );

if ( lk_toggle( 'show_brands' ) ) {
	get_template_part( 'template-parts/home/brands' );
}

get_template_part( 'template-parts/home/latest' );

if ( lk_toggle( 'show_movers' ) ) {
	get_template_part( 'template-parts/home/movers' );
}

if ( lk_toggle( 'show_compare' ) ) {
	get_template_part( 'template-parts/home/compare-cta' );
}

if ( lk_toggle( 'show_news' ) ) {
	get_template_part( 'template-parts/home/news' );
}

if ( lk_toggle( 'show_reviews' ) ) {
	get_template_part( 'template-parts/home/reviews' );
}

if ( lk_toggle( 'show_sales' ) ) {
	get_template_part( 'template-parts/home/sales' );
}

get_footer();
