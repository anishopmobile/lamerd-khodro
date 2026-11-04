<?php
/**
 * Home: review articles.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_category = get_category_by_slug( 'car-reviews' );

if ( ! $lk_category ) {
	return;
}

$lk_reviews = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 2,
		'cat'                 => $lk_category->term_id,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $lk_reviews->have_posts() ) {
	return;
}
?>
<section class="lk-section lk-section--surface">
	<div class="lk-container">
		<?php
		lk_section_head(
			'۰۶',
			'بررسی خودرو',
			'تجربه‌ها و راهنماهای کاربردی برای انتخاب بهتر.',
			array(
				'label' => 'همه بررسی‌ها',
				'url'   => get_category_link( $lk_category ),
			)
		);
		?>

		<div class="lk-news-grid lk-news-grid--pair">
			<?php
			while ( $lk_reviews->have_posts() ) :
				$lk_reviews->the_post();
				get_template_part( 'template-parts/news/card', null, array( 'post' => get_post() ) );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
