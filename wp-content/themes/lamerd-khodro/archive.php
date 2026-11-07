<?php
/**
 * Generic article archive (categories, tags, dates, authors).
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_description = get_the_archive_description();

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => wp_strip_all_tags( get_the_archive_title() ),
		'text'  => wp_strip_all_tags( $lk_description ),
	)
);

get_template_part( 'template-parts/news/archive-section' );

get_footer();
