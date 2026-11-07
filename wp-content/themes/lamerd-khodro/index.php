<?php
/**
 * Blog index / posts page.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_posts_page = (int) get_option( 'page_for_posts' );
$lk_title      = $lk_posts_page ? get_the_title( $lk_posts_page ) : 'اخبار و مقالات';

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => $lk_title,
		'text'  => 'اخبار بازار خودرو، راهنمای خرید و مطالب آموزشی؛ بدون خبر ساختگی و بدون داده تأییدنشده.',
	)
);

get_template_part( 'template-parts/news/archive-section' );

get_footer();
