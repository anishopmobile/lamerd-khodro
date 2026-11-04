<?php
/**
 * Generic archive (categories, tags, dates).
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
?>

<section class="lk-section">
	<div class="lk-container">
		<?php if ( have_posts() ) : ?>
			<div class="lk-news-grid lk-news-grid--archive">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/news/card', null, array( 'post' => get_post() ) );
				endwhile;
				?>
			</div>

			<?php lk_pagination( $GLOBALS['wp_query'] ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/components/empty-data' ); ?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
