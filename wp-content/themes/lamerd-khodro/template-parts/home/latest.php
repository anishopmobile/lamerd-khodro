<?php
/**
 * Home: latest vehicles.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lk_latest_vehicles' ) ) {
	return;
}

$lk_query = lk_latest_vehicles( 8 );

if ( ! $lk_query->have_posts() ) {
	return;
}

$lk_posts = lk_query_with_prices( $lk_query );
?>
<section class="lk-section lk-section--surface">
	<div class="lk-container">
		<?php
		lk_section_head(
			'۰۳',
			'تازه‌های بانک اطلاعات خودرو',
			'خودروهایی که به‌تازگی به فهرست اضافه شده‌اند؛ با قیمت ثبت‌شده و وضعیت تولید.',
			array(
				'label' => 'همه خودروها',
				'url'   => lk_page_url( 'vehicles' ),
			)
		);
		?>

		<div class="lk-grid lk-grid--4">
			<?php foreach ( $lk_posts as $lk_post ) : ?>
				<?php get_template_part( 'template-parts/vehicle/card', null, array( 'post' => $lk_post ) ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
