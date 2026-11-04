<?php
/**
 * Sync screen.
 *
 * @var array $logs
 * @var int $paged
 * @var int|false $next_run
 * @var int $interval
 * @var array|null $last
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$cron_command = '*/' . (int) $interval . ' * * * * cd ' . ABSPATH . ' && php wp-cli.phar lkpe sync --quiet';
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">همگام‌سازی</h1>

	<?php lkpe_render_notices(); ?>

	<div class="lkpe-grid-2">
		<section class="lkpe-panel">
			<h2>اجرای دستی</h2>
			<p>
				دریافت اطلاعات از منبع، اعتبارسنجی، ذخیره در دیتابیس و ثبت تاریخچه.
				هم‌زمان فقط یک عملیات اجرا می‌شود.
			</p>

			<p class="lkpe-actions">
				<button type="button" class="button button-primary" data-lkpe-sync>دریافت اطلاعات</button>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lkpe-inline-form">
					<?php wp_nonce_field( 'lkpe_run_sync' ); ?>
					<input type="hidden" name="action" value="lkpe_run_sync" />
					<input type="hidden" name="redirect" value="<?php echo esc_attr( lkpe_admin_url( 'lkpe-sync' ) ); ?>" />
					<button class="button">اجرای بدون JavaScript</button>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="lkpe-inline-form">
					<?php wp_nonce_field( 'lkpe_snapshot_now' ); ?>
					<input type="hidden" name="action" value="lkpe_snapshot_now" />
					<button class="button" type="submit">ثبت عکس لحظه‌ای امروز</button>
				</form>
			</p>

			<div data-lkpe-sync-result class="lkpe-sync-result" hidden></div>

			<?php if ( $last ) : ?>
				<p class="lkpe-muted">
					آخرین دریافت موفق: <?php echo esc_html( LK_Format::jalali( $last['finished_ts'], 'datetime' ) ); ?>
					— <?php echo esc_html( $last['message'] ); ?>
				</p>
			<?php endif; ?>
		</section>

		<section class="lkpe-panel">
			<h2>زمان‌بندی</h2>
			<p>
				بازه فعلی: هر <?php echo esc_html( LK_Format::number( $interval ) ); ?> دقیقه<br />
				اجرای بعدی WP-Cron: <?php echo $next_run ? esc_html( LK_Format::jalali( $next_run, 'datetime' ) ) : 'زمان‌بندی نشده'; ?>
			</p>
			<p><a class="button" href="<?php echo esc_url( lkpe_admin_url( 'lkpe-settings' ) ); ?>">تغییر بازه همگام‌سازی</a></p>

			<h3>اتصال Cron واقعی هاست</h3>
			<p class="lkpe-muted">
				برای تضمین اجرا حتی بدون بازدید کاربر، دستور زیر را در Cron هاست قرار دهید
				(و در صورت نیاز <code dir="ltr">DISABLE_WP_CRON</code> را فعال کنید).
			</p>
			<pre class="lkpe-code" dir="ltr"><?php echo esc_html( $cron_command ); ?></pre>
		</section>
	</div>

	<h2 class="lkpe-section-title">گزارش عملیات</h2>

	<table class="widefat striped lkpe-table">
		<thead>
			<tr>
				<th>شروع</th>
				<th>پایان</th>
				<th>وضعیت</th>
				<th>دریافت</th>
				<th>جدید</th>
				<th>تغییر</th>
				<th>بدون تغییر</th>
				<th>خطا</th>
				<th>مدت</th>
				<th>نوع</th>
				<th>جزئیات</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $logs['items'] ) ) : ?>
				<tr><td colspan="11" class="lkpe-empty">هنوز عملیاتی ثبت نشده است.</td></tr>
			<?php else : ?>
				<?php foreach ( $logs['items'] as $log ) : ?>
					<?php
					$details = $log['details'] ? json_decode( $log['details'], true ) : array();
					$errors  = is_array( $details ) && ! empty( $details['errors'] ) ? $details['errors'] : array();
					?>
					<tr>
						<td><?php echo esc_html( LK_Format::jalali( strtotime( $log['started_at'] ), 'datetime' ) ); ?></td>
						<td><?php echo $log['finished_at'] ? esc_html( LK_Format::jalali( strtotime( $log['finished_at'] ), 'datetime' ) ) : '—'; ?></td>
						<td><?php echo wp_kses_post( lkpe_status_badge( $log['status'] ) ); ?></td>
						<td><?php echo esc_html( LK_Format::number( $log['vehicles_fetched'] ) ); ?></td>
						<td><?php echo esc_html( LK_Format::number( $log['vehicles_new'] ) ); ?></td>
						<td><?php echo esc_html( LK_Format::number( $log['prices_changed'] ) ); ?></td>
						<td><?php echo esc_html( LK_Format::number( $log['prices_unchanged'] ) ); ?></td>
						<td><?php echo esc_html( LK_Format::number( $log['errors_count'] ) ); ?></td>
						<td><?php echo esc_html( LK_Format::number( $log['duration'] ) ); ?></td>
						<td><?php echo esc_html( 'cron' === $log['trigger_type'] ? 'زمان‌بندی' : ( 'cli' === $log['trigger_type'] ? 'CLI' : 'دستی' ) ); ?></td>
						<td>
							<?php if ( $errors ) : ?>
								<details>
									<summary><?php echo esc_html( LK_Format::number( count( $errors ) ) ); ?> خطا</summary>
									<ul class="lkpe-error-list">
										<?php foreach ( $errors as $error ) : ?>
											<li><?php echo esc_html( $error ); ?></li>
										<?php endforeach; ?>
									</ul>
								</details>
							<?php else : ?>
								<span class="lkpe-muted">—</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php echo wp_kses_post( lkpe_pagination( $paged, $logs['pages'], array( '__page' => 'lkpe-sync' ) ) ); ?>
</div>
