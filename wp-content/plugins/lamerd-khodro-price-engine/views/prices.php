<?php
/**
 * Current prices screen.
 *
 * @var array $result
 * @var int $paged
 * @var string $search
 * @var string $brand
 * @var string $status
 * @var string $sort
 * @var array $stats
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$export_args = array(
	'type'  => 'current',
	'range' => '30',
);
if ( $brand ) {
	$export_args['brand'] = array( $brand );
}
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">
		آخرین قیمت‌ها
		<span class="lkpe-title__actions">
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'lkpe_export', 'format' => 'csv' ), $export_args ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>">خروجی CSV</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'lkpe_export', 'format' => 'xlsx' ), $export_args ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>">خروجی Excel</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'lkpe_export', 'format' => 'pdf' ), $export_args ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>" target="_blank" rel="noopener">خروجی PDF</a>
		</span>
	</h1>

	<?php lkpe_render_notices(); ?>

	<div class="lkpe-strip">
		<span>معتبر: <strong><?php echo esc_html( LK_Format::number( $stats['with_price'] ) ); ?></strong></span>
		<span>افزایشی: <strong class="lkpe-up"><?php echo esc_html( LK_Format::number( $stats['up'] ) ); ?></strong></span>
		<span>کاهشی: <strong class="lkpe-down"><?php echo esc_html( LK_Format::number( $stats['down'] ) ); ?></strong></span>
		<span>بدون تغییر: <strong><?php echo esc_html( LK_Format::number( $stats['flat'] ) ); ?></strong></span>
		<span>ناموجود: <strong><?php echo esc_html( LK_Format::number( $stats['no_price'] ) ); ?></strong></span>
		<span>نیازمند بررسی: <strong><?php echo esc_html( LK_Format::number( $stats['suspect'] ) ); ?></strong></span>
	</div>

	<form method="get" class="lkpe-filters">
		<input type="hidden" name="page" value="lkpe-prices" />
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="جستجوی نام خودرو…" />

		<select name="brand">
			<option value="">همه برندها</option>
			<?php foreach ( lkpe_brands() as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $brand, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="sort">
			<?php foreach ( array( 'price_desc' => 'بیشترین قیمت', 'price_asc' => 'کمترین قیمت', 'change_desc' => 'بیشترین افزایش', 'change_asc' => 'بیشترین کاهش', 'name' => 'حروف الفبا', 'updated' => 'تازه‌ترین دریافت' ) as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>

		<button class="button">اعمال</button>
		<a class="button-link" href="<?php echo esc_url( lkpe_admin_url( 'lkpe-prices' ) ); ?>">حذف فیلترها</a>
	</form>

	<table class="widefat striped lkpe-table">
		<thead>
			<tr>
				<th>خودرو</th>
				<th>برند</th>
				<th>کارخانه</th>
				<th>بازار</th>
				<th>اختلاف</th>
				<th>تغییر</th>
				<th>وضعیت</th>
				<th>زمان دریافت</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $result['items'] ) ) : ?>
				<tr><td colspan="8" class="lkpe-empty">قیمتی ثبت نشده است. همگام‌سازی را اجرا کنید.</td></tr>
			<?php else : ?>
				<?php foreach ( $result['items'] as $item ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_post_link( $item['post_id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></td>
						<td><?php echo esc_html( $item['brand'] ? $item['brand'] : '—' ); ?></td>
						<td><?php echo wp_kses_post( lkpe_price_text( $item['factory_price'] ) ); ?></td>
						<td><strong><?php echo wp_kses_post( lkpe_price_text( $item['market_price'] ) ); ?></strong></td>
						<td><?php echo wp_kses_post( lkpe_price_text( $item['gap'] ) ); ?></td>
						<td><?php echo wp_kses_post( lkpe_change_text( $item['change_amount'], $item['change_percent'], $item['data_status'] ) ); ?></td>
						<td><?php echo wp_kses_post( lkpe_status_badge( $item['data_status'] ) ); ?></td>
						<td>
							<?php echo esc_html( LK_Format::jalali( $item['fetched_ts'], 'datetime' ) ); ?>
							<?php if ( ! empty( $item['is_stale'] ) ) : ?>
								<span class="lkpe-badge lkpe-badge--warn">کهنه</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php echo wp_kses_post( lkpe_pagination( $paged, $result['pages'], array( '__page' => 'lkpe-prices', 's' => $search, 'brand' => $brand, 'sort' => $sort ) ) ); ?>

	<p class="lkpe-attribution">منبع: <a href="https://cofekhodro.ir" target="_blank" rel="noopener">کافه خودرو</a></p>
</div>
