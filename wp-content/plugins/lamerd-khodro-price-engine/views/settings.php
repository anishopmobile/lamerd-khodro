<?php
/**
 * Settings screen.
 *
 * @var int $interval
 * @var array|null $source
 * @var int $table_id
 * @var int|false $next_run
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$intervals = array(
	15   => 'هر ۱۵ دقیقه',
	30   => 'هر ۳۰ دقیقه',
	60   => 'هر ۱ ساعت (پیشنهادی)',
	180  => 'هر ۳ ساعت',
	1440 => 'روزانه',
);
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">تنظیمات</h1>

	<?php lkpe_render_notices(); ?>

	<div class="lkpe-grid-2">
		<section class="lkpe-panel">
			<h2>همگام‌سازی خودکار</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'lkpe_save_settings' ); ?>
				<input type="hidden" name="action" value="lkpe_save_settings" />

				<p>
					<label for="lkpe-interval">بازه دریافت خودکار</label><br />
					<select id="lkpe-interval" name="interval">
						<?php foreach ( $intervals as $minutes => $label ) : ?>
							<option value="<?php echo esc_attr( $minutes ); ?>" <?php selected( $interval, $minutes ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="lkpe-muted">
					قیمت‌های قدیمی‌تر از دو برابر این بازه (حداقل ۳ ساعت) در سایت به‌عنوان «کهنه» علامت می‌خورند،
					اما همچنان نمایش داده می‌شوند.
				</p>

				<p><button class="button button-primary">ذخیره تنظیمات</button></p>
			</form>
		</section>

		<section class="lkpe-panel">
			<h2>وضعیت سامانه</h2>
			<table class="widefat striped lkpe-table">
				<tbody>
					<tr><th>نسخه وردپرس</th><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
					<tr><th>نسخه PHP</th><td dir="ltr"><?php echo esc_html( PHP_VERSION ); ?></td></tr>
					<tr><th>نسخه دیتابیس افزونه</th><td dir="ltr"><?php echo esc_html( get_option( 'lkpe_db_version', '—' ) ); ?></td></tr>
					<tr><th>نسخه MySQL</th><td dir="ltr"><?php echo esc_html( $GLOBALS['wpdb']->db_version() ); ?></td></tr>
					<tr><th>افزونه Zip (خروجی Excel)</th><td><?php echo class_exists( 'ZipArchive' ) ? 'فعال' : 'غیرفعال'; ?></td></tr>
					<tr><th>اجرای بعدی Cron</th><td><?php echo $next_run ? esc_html( LK_Format::jalali( $next_run, 'datetime' ) ) : 'زمان‌بندی نشده'; ?></td></tr>
					<tr><th>بازه فعلی</th><td>هر <?php echo esc_html( LK_Format::number( $interval ) ); ?> دقیقه</td></tr>
					<tr><th>آدرس منبع</th><td><code dir="ltr"><?php echo esc_html( $source['api_url'] ?? '' ); ?></code></td></tr>
					<tr><th>شناسه جدول منبع</th><td dir="ltr"><?php echo esc_html( $table_id ); ?></td></tr>
				</tbody>
			</table>
		</section>
	</div>

	<section class="lkpe-panel lkpe-panel--muted">
		<h2>اصول داده</h2>
		<ul class="lkpe-list lkpe-list--plain">
			<li>همه قیمت‌ها در دیتابیس داخلی ذخیره می‌شوند و نمایش سایت به API خارجی وابسته نیست.</li>
			<li>قیمت ناموجود با صفر اشتباه گرفته نمی‌شود؛ مقدار ناموجود <code dir="ltr">NULL</code> ذخیره می‌شود.</li>
			<li>رکورد تاریخچه حذف یا بازنویسی نمی‌شود؛ تغییر کارخانه و بازار جداگانه ثبت می‌شود.</li>
			<li>تغییرات ناسازگار منبع با وضعیت «نیازمند بررسی» علامت می‌خورند و نمایش داده نمی‌شوند.</li>
			<li>در صورت خطا یا قطع API، آخرین داده معتبر حفظ و نمایش داده می‌شود.</li>
		</ul>
	</section>

	<p class="lkpe-attribution">منبع: <a href="https://cofekhodro.ir" target="_blank" rel="noopener">کافه خودرو</a></p>
</div>
