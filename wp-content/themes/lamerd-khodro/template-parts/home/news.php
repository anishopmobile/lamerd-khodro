<?php
/**
 * Home: latest articles.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_news = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $lk_news->have_posts() ) {
	return;
}

$lk_index = 0;
?>
<section class="lk-section">
	<div class="lk-container">
		<?php
		lk_section_head(
			'۰۵',
			'اخبار و مقالات',
			'تحلیل بازار، راهنمای خرید و مطالب آموزشی درباره خودرو.',
			array(
				'label' => 'همه مطالب',
				'url'   => lk_blog_url(),
			)
		);
		?>

		<div class="lk-news-grid">
			<?php
			while ( $lk_news->have_posts() ) :
				$lk_news->the_post();
				get_template_part(
					'template-parts/news/card',
					null,
					array(
						'post'     => get_post(),
						'featured' => 0 === $lk_index,
					)
				);
				++$lk_index;
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
