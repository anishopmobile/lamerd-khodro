<?php
/**
 * Dashboard view.
 *
 * @var array $stats
 * @var array|null $last_sync
 * @var array $performance
 * @var array $logs
 * @var array $movers
 * @var array|null $source
 * @var int $vehicle_count
 * @var int $history_count
 * @var array $daily
 * @var int|false $next_run
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$max_daily = 1;
foreach ( $daily as $day ) {
	$max_daily = max( $max_daily, (int) $day['count'] );
}
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">
		داشبورد قیمت خودرو
		<span class="lkpe-title__actions">
			<button type="button" class="button button-primary" data-lkpe-sync>همگام‌سازی دستی</button>
			<a class="button" href="<?php echo esc_url( lkpe_admin_url( 'lkpe-reports' ) ); ?>">گزارش‌ها</a>
		</span>
	</h1>

	<div data-lkpe-sync-result class="lkpe-sync-result" hidden></div>

	<?php lkpe_render_notices(); ?>

	<div class="lkpe-cards">
		<div class="lkpe-card">
			<span class="lkpe-card__label">خودروهای ثبت‌شده</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $vehicle_count ) ); ?></strong>
			<span class="lkpe-card__hint">در بانک اطلاعات و دیتابیس داخلی</span>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">قیمت معتبر</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $stats['with_price'] ) ); ?></strong>
			<span class="lkpe-card__hint">بدون قیمت: <?php echo esc_html( LK_Format::number( $stats['no_price'] ) ); ?></span>
		</div>
		<div class="lkpe-card lkpe-card--up">
			<span class="lkpe-card__label">افزایش قیمت</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $stats['up'] ) ); ?></strong>
			<span class="lkpe-card__hint">بر اساس آخرین به‌روزرسانی</span>
		</div>
		<div class="lkpe-card lkpe-card--down">
			<span class="lkpe-card__label">کاهش قیمت</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $stats['down'] ) ); ?></strong>
			<span class="lkpe-card__hint">بدون تغییر: <?php echo esc_html( LK_Format::number( $stats['flat'] ) ); ?></span>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">آخرین دریافت موفق</span>
			<strong class="lkpe-card__value lkpe-card__value--sm">
				<?php echo $last_sync ? esc_html( LK_Format::jalali( $last_sync['finished_ts'], 'datetime' ) ) : 'هنوز انجام نشده'; ?>
			</strong>
			<span class="lkpe-card__hint">
				<?php if ( $last_sync && ! empty( $last_sync['is_stale'] ) ) : ?>
					<span class="lkpe-badge lkpe-badge--warn">اطلاعات کهنه است</span>
				<?php elseif ( $last_sync ) : ?>
					<span class="lkpe-badge lkpe-badge--ok">به‌روز</span>
				<?php endif; ?>
			</span>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">وضعیت منبع</span>
			<strong class="lkpe-card__value lkpe-card__value--sm">
				<?php echo $source ? esc_html( $source['name'] ) : 'تعریف نشده'; ?>
			</strong>
			<span class="lkpe-card__hint">
				<?php if ( $source && (int) $source['is_active'] ) : ?>
					<span class="lkpe-badge lkpe-badge--ok">فعال</span>
				<?php else : ?>
					<span class="lkpe-badge lkpe-badge--danger">غیرفعال</span>
				<?php endif; ?>
				<?php if ( $source && $source['last_error'] ) : ?>
					<span class="lkpe-error-inline" title="<?php echo esc_attr( $source['last_error'] ); ?>">آخرین خطا</span>
				<?php endif; ?>
			</span>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">رکورد تاریخچه</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $history_count ) ); ?></strong>
			<span class="lkpe-card__hint">ثبت‌های دائمی قیمت</span>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">خطاهای ۳۰ روز</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $performance['errors'] ) ); ?></strong>
			<span class="lkpe-card__hint">
				موفق <?php echo esc_html( LK_Format::number( $performance['success'] ) ); ?> /
				نیمه‌موفق <?php echo esc_html( LK_Format::number( $performance['partial'] ) ); ?> /
				ناموفق <?php echo esc_html( LK_Format::number( $performance['failed'] ) ); ?>
			</span>
		</div>
	</div>

	<div class="lkpe-grid-2">
		<section class="lkpe-panel">
			<h2>تغییرات ۱۴ روز گذشته</h2>
			<?php if ( array_sum( wp_list_pluck( $daily, 'count' ) ) === 0 ) : ?>
				<p class="lkpe-muted">هنوز تغییر قیمتی در تاریخچه ثبت نشده است. پس از اولین همگام‌سازی، تغییرات همین‌جا نمایش داده می‌شوند.</p>
			<?php else : ?>
				<div class="lkpe-bars" role="img" aria-label="نمودار تعداد تغییرات قیمت روزانه">
					<?php foreach ( $daily as $day ) : ?>
						<div class="lkpe-bars__item">
							<span class="lkpe-bars__bar" style="height: <?php echo esc_attr( (int) round( ( $day['count'] / $max_daily ) * 100 ) ); ?>%" title="<?php echo esc_attr( $day['label'] . ': ' . LK_Format::number( $day['count'] ) ); ?>"></span>
							<span class="lkpe-bars__label"><?php echo esc_html( $day['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<section class="lkpe-panel">
			<h2>بیشترین کاهش‌های امروز</h2>
			<?php if ( ! $movers ) : ?>
				<p class="lkpe-muted">داده‌ای برای امروز ثبت نشده است.</p>
			<?php else : ?>
				<ul class="lkpe-list">
					<?php foreach ( $movers as $mover ) : ?>
						<li>
							<a href="<?php echo esc_url( $mover['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $mover['title'] ); ?></a>
							<span class="lkpe-change is-down"><?php echo esc_html( LK_Format::change_percent( $mover['diff_percent'] ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>

	<section class="lkpe-panel">
		<h2>آخرین عملیات همگام‌سازی</h2>
		<?php if ( empty( $logs['items'] ) ) : ?>
			<p class="lkpe-muted">هنوز عملیاتی اجرا نشده است.</p>
		<?php else : ?>
			<table class="widefat striped lkpe-table">
				<thead>
					<tr>
						<th>شروع</th>
						<th>وضعیت</th>
						<th>دریافت‌شده</th>
						<th>جدید</th>
						<th>تغییرکرده</th>
						<th>خطا</th>
						<th>مدت (ثانیه)</th>
						<th>نوع</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs['items'] as $log ) : ?>
						<tr>
							<td><?php echo esc_html( LK_Format::jalali( strtotime( $log['started_at'] ), 'datetime' ) ); ?></td>
							<td><?php echo wp_kses_post( lkpe_status_badge( $log['status'] ) ); ?></td>
							<td><?php echo esc_html( LK_Format::number( $log['vehicles_fetched'] ) ); ?></td>
							<td><?php echo esc_html( LK_Format::number( $log['vehicles_new'] ) ); ?></td>
							<td><?php echo esc_html( LK_Format::number( $log['prices_changed'] ) ); ?></td>
							<td><?php echo esc_html( LK_Format::number( $log['errors_count'] ) ); ?></td>
							<td><?php echo esc_html( LK_Format::number( $log['duration'] ) ); ?></td>
							<td><?php echo esc_html( 'cron' === $log['trigger_type'] ? 'زمان‌بندی' : ( 'cli' === $log['trigger_type'] ? 'خط فرمان' : 'دستی' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><a href="<?php echo esc_url( lkpe_admin_url( 'lkpe-sync' ) ); ?>">مشاهده همه گزارش‌های همگام‌سازی</a></p>
		<?php endif; ?>
	</section>

	<section class="lkpe-panel lkpe-panel--muted">
		<h2>وضعیت زمان‌بندی</h2>
		<p>
			بازه همگام‌سازی: هر <?php echo esc_html( LK_Format::number( LKPE_Cron::interval() ) ); ?> دقیقه —
			اجرای بعدی: <?php echo $next_run ? esc_html( LK_Format::jalali( $next_run, 'datetime' ) ) : 'زمان‌بندی نشده'; ?>
		</p>
		<p class="lkpe-muted">برای اجرای مطمئن‌تر، Cron واقعی هاست را به دستور صفحه «همگام‌سازی» متصل کنید.</p>
	</section>

	<p class="lkpe-attribution">منبع قیمت‌ها: <a href="https://cofekhodro.ir" target="_blank" rel="noopener">کافه خودرو</a> — همه داده‌ها در دیتابیس لامرد خودرو ذخیره می‌شوند.</p>
</div>
