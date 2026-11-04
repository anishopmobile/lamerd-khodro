<?php
/**
 * Home: price board.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_items = function_exists( 'lk_get_prices' ) ? lk_get_prices(
	array(
		'per_page' => 8,
		'sort'     => 'price_desc',
	)
) : array( 'items' => array() );
?>
<section class="lk-section lk-section--surface lk-board-section" id="prices">
	<div class="lk-container">
		<?php
		lk_section_head(
			'۰۱',
			'قیمت روز بازار',
			'جدول قیمت بازار و کارخانه خودروهای داخلی و وارداتی، همراه با درصد تغییر و وضعیت داده.',
			array(
				'label' => 'جدول کامل قیمت‌ها',
				'url'   => lk_page_url( 'prices' ),
			)
		);
		?>

		<?php if ( ! empty( $lk_items['items'] ) ) : ?>
			<div class="lk-board">
				<?php
				get_template_part(
					'template-parts/price/table',
					null,
					array(
						'items'   => $lk_items['items'],
						'compact' => true,
						'ajax'    => false,
					)
				);
				?>
				<footer class="lk-board__foot">
					<?php lk_attribution_line(); ?>
					<a class="lk-textlink" href="<?php echo esc_url( lk_page_url( 'prices' ) ); ?>">
						مشاهده همه خودروها با فیلتر برند و قیمت
						<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</footer>
			</div>
		<?php else : ?>
			<?php get_template_part( 'template-parts/components/empty-data' ); ?>
		<?php endif; ?>
	</div>
</section>
