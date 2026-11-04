<?php
/**
 * Comparison table.
 *
 * @param array $args { @type array $vehicles, @type array $rows }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_vehicles = $args['vehicles'] ?? array();
$lk_rows     = $args['rows'] ?? array();

if ( count( $lk_vehicles ) < 2 ) {
	return;
}

$lk_count = count( $lk_vehicles );
?>
<div class="lk-cmp-tablewrap">
	<table class="lk-cmp-table">
		<caption class="screen-reader-text">جدول مقایسه خودروها</caption>
		<thead>
			<tr>
				<th scope="col" class="lk-cmp-table__label">مشخصه</th>
				<?php foreach ( $lk_vehicles as $lk_vehicle ) : ?>
					<th scope="col" class="lk-cmp-table__car">
						<a class="lk-cmp-table__title" href="<?php echo esc_url( $lk_vehicle['url'] ); ?>"><?php echo esc_html( $lk_vehicle['title'] ); ?></a>
						<span class="lk-cmp-table__brand"><?php echo esc_html( $lk_vehicle['brand'] ); ?></span>
						<?php if ( ! empty( $lk_vehicle['price']['market_price'] ) ) : ?>
							<span class="lk-cmp-table__price"><?php echo esc_html( LK_Format::price_short( $lk_vehicle['price']['market_price'] ) ); ?> تومان</span>
						<?php endif; ?>
					</th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $lk_rows as $lk_group ) : ?>
				<tr class="lk-cmp-table__grouprow">
					<th scope="colgroup" colspan="<?php echo esc_attr( $lk_count + 1 ); ?>"><?php echo esc_html( $lk_group['group'] ); ?></th>
				</tr>
				<?php foreach ( $lk_group['rows'] as $lk_row ) : ?>
					<tr class="<?php echo $lk_row['differs'] ? 'is-diff' : ''; ?>">
						<th scope="row" class="lk-cmp-table__label"><?php echo esc_html( $lk_row['label'] ); ?></th>
						<?php foreach ( $lk_row['values'] as $lk_index => $lk_value ) : ?>
							<td class="<?php echo (int) $lk_row['best_index'] === (int) $lk_index ? 'is-best' : ''; ?>">
								<?php echo '' !== $lk_value ? esc_html( $lk_value ) : '<span class="lk-muted">—</span>'; ?>
								<?php if ( (int) $lk_row['best_index'] === (int) $lk_index ) : ?>
									<span class="lk-cmp-best" title="مقدار بهتر در این ردیف">بهترین</span>
								<?php endif; ?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<p class="lk-cmp-legend">
	<span class="lk-cmp-best">بهترین</span>
	بهترین مقدار عددی در آن ردیف است؛ معیار «بهتر» برای هر مشخصه متفاوت است (قیمت کمتر، قدرت بیشتر، مصرف کمتر).
</p>
