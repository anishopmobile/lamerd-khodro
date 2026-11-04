<?php
/**
 * Brand archive.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_term  = get_queried_object();
$lk_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$lk_logo  = function_exists( 'lk_brand_logo' ) ? lk_brand_logo( $lk_term->term_id ) : '';

$lk_query = class_exists( 'LK_Query' ) ? LK_Query::vehicles(
	array(
		'posts_per_page' => 12,
		'paged'          => $lk_paged,
		'brand'          => array( $lk_term->slug ),
		'sort'           => 'price_desc',
	)
) : new WP_Query(
	array(
		'post_type'      => 'lk_vehicle',
		'posts_per_page' => 12,
		'paged'          => $lk_paged,
		'tax_query'      => array(
			array(
				'taxonomy' => 'lk_brand',
				'field'    => 'term_id',
				'terms'    => $lk_term->term_id,
			),
		),
	)
);

$lk_posts = function_exists( 'lk_query_with_prices' ) ? lk_query_with_prices( $lk_query ) : $lk_query->posts;
?>

<header class="lk-brandhero">
	<div class="lk-brandhero__pattern" aria-hidden="true"></div>
	<div class="lk-container">
		<?php lk_breadcrumb(); ?>
		<div class="lk-brandhero__row">
			<div class="lk-brandhero__identity">
				<span class="lk-brandhero__logo">
					<?php if ( $lk_logo ) : ?>
						<img src="<?php echo esc_url( $lk_logo ); ?>" alt="<?php echo esc_attr( $lk_term->name ); ?>" width="80" height="80" />
					<?php else : ?>
						<span class="lk-monogram lk-monogram--lg" aria-hidden="true"><?php echo esc_html( lk_monogram( $lk_term->name ) ); ?></span>
					<?php endif; ?>
				</span>
				<div>
					<h1 class="lk-brandhero__title"><?php echo esc_html( $lk_term->name ); ?></h1>
					<p class="lk-brandhero__meta"><?php echo esc_html( sprintf( '%s خودرو در بانک اطلاعات', lk_num( (int) $lk_term->count ) ) ); ?></p>
				</div>
			</div>

			<div class="lk-brandhero__actions">
				<a class="lk-btn lk-btn--primary" href="<?php echo esc_url( add_query_arg( 'brand', $lk_term->slug, lk_page_url( 'prices' ) ) ); ?>">قیمت‌های این برند</a>
				<a class="lk-btn lk-btn--ghost-light" href="<?php echo esc_url( lk_page_url( 'brands' ) ); ?>">همه برندها</a>
			</div>
		</div>

		<?php if ( $lk_term->description ) : ?>
			<p class="lk-brandhero__desc"><?php echo esc_html( $lk_term->description ); ?></p>
		<?php endif; ?>
	</div>
</header>

<section class="lk-section">
	<div class="lk-container">
		<?php if ( $lk_posts ) : ?>
			<div class="lk-grid lk-grid--4 lk-grid--vehicles">
				<?php foreach ( $lk_posts as $lk_post ) : ?>
					<?php get_template_part( 'template-parts/vehicle/card', null, array( 'post' => $lk_post ) ); ?>
				<?php endforeach; ?>
			</div>

			<?php lk_pagination( $lk_query ); ?>
		<?php else : ?>
			<?php
			get_template_part(
				'template-parts/components/empty-data',
				null,
				array(
					'title'      => 'خودرویی برای این برند ثبت نشده است',
					'text'       => 'با تکمیل بانک اطلاعات، خودروهای این برند در همین صفحه نمایش داده می‌شوند.',
					'link_label' => 'همه خودروها',
					'link_url'   => get_post_type_archive_link( 'lk_vehicle' ),
				)
			);
			?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
