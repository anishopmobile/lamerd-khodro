<?php
/**
 * Search results: vehicles first, then articles.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_term         = get_search_query();
$lk_vehicle_hits = ( $lk_term && function_exists( 'lk_search_vehicles' ) ) ? lk_search_vehicles( $lk_term, 8 ) : null;
$lk_has_vehicle  = $lk_vehicle_hits && $lk_vehicle_hits->have_posts();

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => 'جستجو: ' . $lk_term,
		'text'  => have_posts() || $lk_has_vehicle ? 'نتایج جستجو در خودروها و مطالب سایت.' : 'نتیجه‌ای برای این عبارت پیدا نشد. عبارت دیگری را امتحان کنید.',
	)
);
?>

<section class="lk-section">
	<div class="lk-container">

		<?php if ( $lk_has_vehicle ) : ?>
			<div class="lk-search-block">
				<div class="lk-search-block__head">
					<h2 class="lk-panel__title">خودروها</h2>
					<a class="lk-textlink" href="<?php echo esc_url( add_query_arg( 'search', $lk_term, get_post_type_archive_link( 'lk_vehicle' ) ) ); ?>">جستجوی کامل در خودروها</a>
				</div>
				<div class="lk-grid lk-grid--4">
					<?php
					$lk_vehicle_posts = function_exists( 'lk_query_with_prices' ) ? lk_query_with_prices( $lk_vehicle_hits ) : $lk_vehicle_hits->posts;
					foreach ( $lk_vehicle_posts as $lk_post ) :
						get_template_part( 'template-parts/vehicle/card', null, array( 'post' => $lk_post ) );
					endforeach;
					wp_reset_postdata();
					?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="lk-search-block">
				<h2 class="lk-panel__title">مطالب</h2>
				<div class="lk-news-grid lk-news-grid--archive">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/news/card', null, array( 'post' => get_post() ) );
					endwhile;
					?>
				</div>
				<?php lk_pagination( $GLOBALS['wp_query'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! have_posts() && ! $lk_has_vehicle ) : ?>
			<?php
			get_template_part(
				'template-parts/components/empty-data',
				null,
				array(
					'title'      => 'نتیجه‌ای پیدا نشد',
					'text'       => 'می‌توانید از جدول قیمت روز یا فهرست برندها شروع کنید.',
					'link_label' => 'قیمت روز خودرو',
					'link_url'   => lk_page_url( 'prices' ),
				)
			);
			?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
