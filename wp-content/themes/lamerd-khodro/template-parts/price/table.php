<?php
/**
 * Price table + mobile cards.
 *
 * @param array $args {
 *     @type array $items   Price items.
 *     @type bool  $compact Hide factory/gap/status columns.
 *     @type bool  $ajax    Add JS hooks.
 * }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_items   = $args['items'] ?? array();
$lk_compact = ! empty( $args['compact'] );
$lk_ajax    = ! empty( $args['ajax'] );
?>
<div class="lk-table-wrap lk-price-table <?php echo $lk_compact ? 'is-compact' : ''; ?>" <?php echo $lk_ajax ? 'data-lk-price-table' : ''; ?>>
	<table class="lk-table">
		<caption class="screen-reader-text">جدول قیمت روز خودرو</caption>
		<thead>
			<tr>
				<th scope="col">خودرو</th>
				<?php if ( ! $lk_compact ) : ?>
					<th scope="col">قیمت کارخانه</th>
				<?php endif; ?>
				<th scope="col">قیمت بازار</th>
				<?php if ( ! $lk_compact ) : ?>
					<th scope="col">اختلاف</th>
				<?php endif; ?>
				<th scope="col">تغییر</th>
				<?php if ( ! $lk_compact ) : ?>
					<th scope="col">وضعیت</th>
				<?php endif; ?>
				<th scope="col"><span class="screen-reader-text">مشاهده</span></th>
			</tr>
		</thead>
		<tbody <?php echo $lk_ajax ? 'data-lk-price-rows' : ''; ?>>
			<?php foreach ( $lk_items as $lk_item ) : ?>
				<?php
				$lk_item['_compact'] = $lk_compact;
				get_template_part( 'template-parts/price/row', null, $lk_item );
				?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="lk-price-cards" <?php echo $lk_ajax ? 'data-lk-price-cards' : ''; ?>>
	<?php foreach ( $lk_items as $lk_item ) : ?>
		<?php get_template_part( 'template-parts/price/card', null, $lk_item ); ?>
	<?php endforeach; ?>
</div>
