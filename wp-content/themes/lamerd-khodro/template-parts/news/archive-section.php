<?php
/**
 * News archive layout: uniform card grid plus sidebar.
 *
 * Used by the posts page (/news/) and by category, tag and date archives so
 * the listing looks the same everywhere.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_query = $GLOBALS['wp_query'];
$lk_term  = get_search_query();
$lk_total = (int) $lk_query->found_posts;
?>
<section class="lk-section lk-news-archive">
	<div class="lk-container lk-news-layout">
		<div class="lk-news-layout__main" data-lk-news-region>
			<div class="lk-news-status<?php echo $lk_term ? ' is-visible' : ''; ?>" data-lk-news-status role="status" aria-live="polite">
				<p class="lk-news-status__text" data-lk-news-status-text>
					<?php
					if ( $lk_term ) {
						echo esc_html(
							sprintf(
								'%s نتیجه برای «%s»',
								lk_num( $lk_total ),
								$lk_term
							)
						);
					}
					?>
				</p>
				<button class="lk-news-status__clear" type="button" data-lk-news-clear>نمایش همه مطالب</button>
			</div>

			<div class="lk-news-grid lk-news-grid--archive" data-lk-news-grid>
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/news/card', null, array( 'post' => get_post() ) );
					endwhile;
				endif;
				?>
			</div>

			<div class="lk-news-layout__pagination" data-lk-news-pagination<?php echo have_posts() ? '' : ' hidden'; ?>>
				<?php lk_pagination( $lk_query ); ?>
			</div>

			<div class="lk-news-layout__empty" data-lk-news-empty<?php echo have_posts() ? ' hidden' : ''; ?>>
				<?php
				get_template_part(
					'template-parts/components/empty-data',
					null,
					array(
						'title'      => 'مطلبی پیدا نشد',
						'text'       => 'عبارت دیگری را جستجو کنید یا به فهرست همهٔ مطالب برگردید.',
						'link_label' => 'همهٔ اخبار و مقالات',
						'link_url'   => lk_blog_url(),
					)
				);
				?>
			</div>
		</div>

		<aside class="lk-news-layout__aside">
			<?php get_template_part( 'template-parts/news/sidebar' ); ?>
		</aside>
	</div>
</section>
