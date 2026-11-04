<?php
/**
 * Vehicles screen.
 *
 * @var array $rows
 * @var int $total
 * @var int $pages
 * @var int $paged
 * @var string $search
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">
		خودروهای ثبت‌شده
		<span class="lkpe-title__actions">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=lk_vehicle' ) ); ?>">افزودن خودرو</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=lk_vehicle' ) ); ?>">مدیریت کامل در خودروها</a>
		</span>
	</h1>

	<?php lkpe_render_notices(); ?>

	<form method="get" class="lkpe-filters">
		<input type="hidden" name="page" value="lkpe-vehicles" />
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="جستجوی نام خودرو…" />
		<button class="button">جستجو</button>
		<span class="lkpe-filters__count"><?php echo esc_html( LK_Format::number( $total ) ); ?> خودرو</span>
	</form>

	<table class="widefat striped lkpe-table">
		<thead>
			<tr>
				<th>نام خودرو</th>
				<th>برند</th>
				<th>شناسه منبع</th>
				<th>قیمت بازار</th>
				<th>تغییر</th>
				<th>وضعیت داده</th>
				<th>وضعیت تولید</th>
				<th>عملیات</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="8" class="lkpe-empty">خودرویی یافت نشد. ابتدا همگام‌سازی را اجرا کنید.</td></tr>
			<?php else : ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td>
							<?php if ( ! empty( $row['post_id'] ) ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( (int) $row['post_id'] ) ); ?>"><?php echo esc_html( $row['name'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $row['name'] ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $row['brand'] ? $row['brand'] : '—' ); ?></td>
						<td><code><?php echo esc_html( $row['source_vehicle_id'] ); ?></code></td>
						<td><?php echo wp_kses_post( lkpe_price_text( $row['market_price'] ) ); ?></td>
						<td><?php echo wp_kses_post( lkpe_change_text( $row['change_amount'], $row['change_percent'], $row['data_status'] ?? 'valid' ) ); ?></td>
						<td><?php echo wp_kses_post( lkpe_status_badge( (string) ( $row['data_status'] ?? 'valid' ) ) ); ?></td>
						<td><?php echo ! empty( $row['production_stopped'] ) ? 'متوقف' : 'فعال'; ?></td>
						<td>
							<?php if ( ! empty( $row['post_id'] ) ) : ?>
								<a href="<?php echo esc_url( get_permalink( (int) $row['post_id'] ) ); ?>" target="_blank" rel="noopener">نمایش</a>
							<?php else : ?>
								<span class="lkpe-muted">بدون برگه</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php echo wp_kses_post( lkpe_pagination( $paged, $pages, array( '__page' => 'lkpe-vehicles', 's' => $search ) ) ); ?>
</div>
