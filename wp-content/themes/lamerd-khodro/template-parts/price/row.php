<?php
/**
 * Price table row (desktop).
 *
 * @param array $args Price list item.
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_item    = $args;
$lk_compact = ! empty( $args['_compact'] );
$lk_status  = $lk_item['data_status'] ?? 'valid';
$lk_stale   = ! empty( $lk_item['is_stale'] );
?>
<tr class="lk-prow">
	<td class="lk-prow__car">
		<a class="lk-prow__main" href="<?php echo esc_url( $lk_item['url'] ); ?>"><?php echo esc_html( $lk_item['title'] ); ?></a>
		<span class="lk-prow__meta">
			<?php echo esc_html( $lk_item['brand'] ); ?>
			<?php if ( ! empty( $lk_item['production_stopped'] ) ) : ?>
				<span class="lk-status is-stopped">متوقف</span>
			<?php endif; ?>
		</span>
	</td>

	<?php if ( ! $lk_compact ) : ?>
		<td class="lk-prow__num">
			<?php echo null !== $lk_item['factory_price'] ? esc_html( LK_Format::price( $lk_item['factory_price'] ) ) : '<span class="lk-muted">—</span>'; ?>
		</td>
	<?php endif; ?>

	<td class="lk-prow__num lk-prow__num--strong">
		<?php echo null !== $lk_item['market_price'] ? esc_html( LK_Format::price( $lk_item['market_price'] ) ) : '<span class="lk-muted">—</span>'; ?>
		<span class="lk-prow__unit">تومان</span>
	</td>

	<?php if ( ! $lk_compact ) : ?>
		<td class="lk-prow__num">
			<?php echo null !== $lk_item['gap'] ? esc_html( LK_Format::price( $lk_item['gap'] ) ) : '<span class="lk-muted">—</span>'; ?>
		</td>
	<?php endif; ?>

	<td class="lk-prow__change"><?php echo lk_change_badge( $lk_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>

	<?php if ( ! $lk_compact ) : ?>
		<td class="lk-prow__status">
			<?php if ( 'suspect' === $lk_status ) : ?>
				<span class="lk-status is-suspect">نیازمند بررسی</span>
			<?php elseif ( $lk_stale ) : ?>
				<span class="lk-status is-stale">قیمت قدیمی</span>
			<?php else : ?>
				<span class="lk-status is-active">معتبر</span>
			<?php endif; ?>
		</td>
	<?php endif; ?>

	<td class="lk-prow__go">
		<a href="<?php echo esc_url( $lk_item['url'] ); ?>" aria-label="<?php echo esc_attr( 'مشاهده ' . $lk_item['title'] ); ?>">
			<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</td>
</tr>
