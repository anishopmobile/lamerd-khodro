<?php
/**
 * Home: largest price movements.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'lk_get_top_movers' ) ) {
	return;
}

$lk_up   = lk_get_top_movers( 'up', 4 );
$lk_down = lk_get_top_movers( 'down', 4 );

if ( ! $lk_up && ! $lk_down ) {
	return;
}
?>
<section class="lk-section lk-movers">
	<div class="lk-container">
		<?php
		lk_section_head(
			'۰۴',
			'بیشترین تغییرات قیمت',
			'بر پایه درصد تغییر آخرین دریافت؛ رکوردهای مشکوک در این فهرست نمایش داده نمی‌شوند.',
			array(
				'label' => 'جدول کامل',
				'url'   => lk_page_url( 'prices' ),
			)
		);
		?>

		<div class="lk-movers__grid">
			<?php
			$lk_columns = array(
				array(
					'title' => 'بیشترین افزایش',
					'icon'  => 'arrow-up',
					'class' => 'is-up',
					'items' => $lk_up,
				),
				array(
					'title' => 'بیشترین کاهش',
					'icon'  => 'arrow-down',
					'class' => 'is-down',
					'items' => $lk_down,
				),
			);

			foreach ( $lk_columns as $lk_col ) :
				?>
				<div class="lk-movers__col <?php echo esc_attr( $lk_col['class'] ); ?>">
					<h3 class="lk-movers__title">
						<span class="lk-movers__icon"><?php echo lk_icon( $lk_col['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php echo esc_html( $lk_col['title'] ); ?>
					</h3>
					<ul class="lk-movers__list">
						<?php foreach ( $lk_col['items'] as $lk_item ) : ?>
							<li>
								<a href="<?php echo esc_url( $lk_item['url'] ); ?>">
									<span class="lk-movers__name"><?php echo esc_html( $lk_item['title'] ); ?></span>
									<span class="lk-movers__price"><?php echo esc_html( LK_Format::price_short( $lk_item['market_price'] ) ); ?></span>
									<span class="lk-change <?php echo esc_attr( $lk_col['class'] ); ?>">
										<?php echo esc_html( LK_Format::change_percent( $lk_item['change_percent'] ) ); ?>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
