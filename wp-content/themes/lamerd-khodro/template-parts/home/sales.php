<?php
/**
 * Home: sales conditions list.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_category = get_category_by_slug( 'sales-conditions' );

if ( ! $lk_category ) {
	return;
}

$lk_sales = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => 4,
		'cat'                 => $lk_category->term_id,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $lk_sales->have_posts() ) {
	return;
}
?>
<section class="lk-section">
	<div class="lk-container lk-sales">
		<div class="lk-sales__aside">
			<span class="lk-section-head__index" aria-hidden="true">۰۷</span>
			<h2 class="lk-sales__title">شرایط فروش</h2>
			<p class="lk-sales__text">اطلاعیه‌های فروش و طرح‌های اعلام‌شده خودروسازان، همراه با تاریخ انتشار.</p>
			<a class="lk-textlink" href="<?php echo esc_url( get_category_link( $lk_category ) ); ?>">
				همه اطلاعیه‌ها
				<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>

		<ul class="lk-sales__list">
			<?php
			while ( $lk_sales->have_posts() ) :
				$lk_sales->the_post();
				?>
				<li>
					<a href="<?php the_permalink(); ?>">
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( lk_jalali( get_post_timestamp(), 'short' ) ); ?></time>
						<span class="lk-sales__headline"><?php the_title(); ?></span>
						<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</li>
			<?php endwhile; wp_reset_postdata(); ?>
		</ul>
	</div>
</section>
