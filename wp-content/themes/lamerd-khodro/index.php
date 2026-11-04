<?php
/**
 * Blog index / fallback template.
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
?>

<section class="lk-section">
	<div class="lk-container">
		<?php if ( have_posts() ) : ?>
			<div class="lk-news-grid lk-news-grid--archive">
				<?php
				$lk_first = true;
				while ( have_posts() ) :
					the_post();
					get_template_part(
						'template-parts/news/card',
						null,
						array(
							'post'     => get_post(),
							'featured' => $lk_first,
						)
					);
					$lk_first = false;
				endwhile;
				?>
			</div>

			<?php lk_pagination( $GLOBALS['wp_query'] ); ?>
		<?php else : ?>
			<?php
			get_template_part(
				'template-parts/components/empty-data',
				null,
				array(
					'title' => 'مطلبی منتشر نشده است',
					'text'  => 'به‌زودی مطالب تازه در این بخش منتشر می‌شود.',
				)
			);
			?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
