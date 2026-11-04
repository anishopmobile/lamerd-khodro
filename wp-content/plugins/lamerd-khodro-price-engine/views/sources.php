<?php
/**
 * Sources screen.
 *
 * @var array $sources
 * @var int $vehicles
 * @var int $prices
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$source = LKPE_Source::get( 'cafekhodro' );
$table_id = (int) LKPE_Source::setting( 'cafekhodro', 'table_id', 1 );
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">منابع اطلاعات</h1>

	<?php lkpe_render_notices(); ?>

	<div class="lkpe-grid-2">
		<section class="lkpe-panel">
			<h2>منبع فعال</h2>
			<table class="widefat striped lkpe-table">
				<tbody>
					<tr><th>نام منبع</th><td><?php echo esc_html( $source['name'] ?? '—' ); ?></td></tr>
					<tr><th>نشانی API</th><td><code dir="ltr"><?php echo esc_html( $source['api_url'] ?? '' ); ?></code></td></tr>
					<tr><th>وضعیت</th><td><?php echo ! empty( $source['is_active'] ) ? '<span class="lkpe-badge lkpe-badge--ok">فعال</span>' : '<span class="lkpe-badge lkpe-badge--danger">غیرفعال</span>'; ?></td></tr>
					<tr><th>آخرین دریافت موفق</th><td><?php echo ! empty( $source['last_success_at'] ) ? esc_html( LK_Format::jalali( strtotime( $source['last_success_at'] ), 'datetime' ) ) : '—'; ?></td></tr>
					<tr><th>آخرین خطا</th><td>
						<?php if ( ! empty( $source['last_error'] ) ) : ?>
							<span class="lkpe-error-text"><?php echo esc_html( $source['last_error'] ); ?></span>
							<br /><small><?php echo esc_html( LK_Format::jalali( strtotime( $source['last_error_at'] ), 'datetime' ) ); ?></small>
						<?php else : ?>
							<span class="lkpe-muted">خطایی ثبت نشده است.</span>
						<?php endif; ?>
					</td></tr>
					<tr><th>خودروهای نگاشت‌شده</th><td><?php echo esc_html( LK_Format::number( $vehicles ) ); ?></td></tr>
					<tr><th>قیمت‌های جاری</th><td><?php echo esc_html( LK_Format::number( $prices ) ); ?></td></tr>
				</tbody>
			</table>
		</section>

		<section class="lkpe-panel">
			<h2>تنظیمات منبع</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'lkpe_save_source' ); ?>
				<input type="hidden" name="action" value="lkpe_save_source" />

				<p>
					<label for="lkpe-api-url">نشانی API</label>
					<input type="url" id="lkpe-api-url" name="api_url" class="widefat" dir="ltr"
						value="<?php echo esc_attr( $source['api_url'] ?? 'https://cofekhodro.ir/wp-json/cafekhodro/v1' ); ?>" />
				</p>
				<p>
					<label for="lkpe-table-id">شناسه جدول</label>
					<input type="number" id="lkpe-table-id" name="table_id" min="1" value="<?php echo esc_attr( $table_id ); ?>" dir="ltr" />
					<span class="lkpe-muted">شناسه ۱ جدول قیمت خودروهای داخلی است.</span>
				</p>
				<p>
					<label>
						<input type="checkbox" name="is_active" value="1" <?php checked( ! empty( $source['is_active'] ) ); ?> />
						منبع فعال باشد
					</label>
				</p>

				<p><button class="button button-primary">ذخیره تنظیمات منبع</button></p>
			</form>
		</section>
	</div>

	<section class="lkpe-panel lkpe-panel--muted">
		<h2>شرایط استفاده از API</h2>
		<ul class="lkpe-list lkpe-list--plain">
			<li>کلید API لازم نیست.</li>
			<li>محدودیت ۱۲۰ درخواست در دقیقه برای هر IP رعایت می‌شود؛ همگام‌سازی هر بار تنها ۲ درخواست ارسال می‌کند.</li>
			<li>نام منبع و لینک آن در صفحه قیمت، جزئیات خودرو و گزارش‌ها نمایش داده می‌شود.</li>
			<li>خطاهای 429، 5xx و Timeout با تأخیر افزایشی و حداکثر سه تلاش تکرار می‌شوند.</li>
			<li>پاسخ‌های نامعتبر باعث حذف داده‌های قبلی نمی‌شوند.</li>
		</ul>
	</section>

	<section class="lkpe-panel">
		<h2>افزودن منبع جدید</h2>
		<p class="lkpe-muted">
			معماری Provider آماده توسعه است. برای افزودن منبع جدید، یک کلاس با پیاده‌سازی
			<code dir="ltr">LKPE_Provider</code> بسازید و آن را با فیلتر <code dir="ltr">lkpe_providers</code> ثبت کنید.
			منطق دریافت از رابط کاربری مستقل است.
		</p>
	</section>

	<p class="lkpe-attribution">منبع: <a href="https://cofekhodro.ir" target="_blank" rel="noopener">کافه خودرو</a></p>
</div>
