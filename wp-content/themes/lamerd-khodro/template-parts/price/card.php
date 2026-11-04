<?php
/**
 * Price card (mobile / stacked layout).
 *
 * @param array $args Price list item.
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_item = $args;
?>
<article class="lk-pcard">
	<div class="lk-pcard__head">
		<div>
			<h3 class="lk-pcard__title"><a href="<?php echo esc_url( $lk_item['url'] ); ?>"><?php echo esc_html( $lk_item['title'] ); ?></a></h3>
			<span class="lk-pcard__brand"><?php echo esc_html( $lk_item['brand'] ); ?></span>
		</div>
		<?php if ( ! empty( $lk_item['production_stopped'] ) ) : ?>
			<span class="lk-status is-stopped">متوقف</span>
		<?php endif; ?>
	</div>

	<div class="lk-pcard__price">
		<span class="lk-pcard__label">قیمت بازار</span>
		<span class="lk-pcard__value">
			<?php echo null !== $lk_item['market_price'] ? esc_html( LK_Format::price( $lk_item['market_price'] ) ) : '—'; ?>
			<span class="lk-money__unit">تومان</span>
		</span>
		<?php echo lk_change_badge( $lk_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>

	<div class="lk-pcard__grid">
		<div>
			<span class="lk-pcard__label">قیمت کارخانه</span>
			<span class="lk-pcard__cell"><?php echo null !== $lk_item['factory_price'] ? esc_html( LK_Format::price( $lk_item['factory_price'] ) ) : '—'; ?></span>
		</div>
		<div>
			<span class="lk-pcard__label">اختلاف با کارخانه</span>
			<span class="lk-pcard__cell"><?php echo null !== $lk_item['gap'] ? esc_html( LK_Format::price( $lk_item['gap'] ) ) : '—'; ?></span>
		</div>
	</div>

	<a class="lk-pcard__link" href="<?php echo esc_url( $lk_item['url'] ); ?>">
		جزئیات و تاریخچه قیمت
		<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
</article>
