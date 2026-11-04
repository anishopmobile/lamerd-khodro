<?php
/**
 * Reports screen.
 *
 * @var array $range
 * @var string $preset
 * @var string $brand
 * @var array $summary
 * @var array $up
 * @var array $down
 * @var array $brands
 * @var array $gaps
 * @var array $unchanged
 * @var array $performance
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$export_base = array(
	'action' => 'lkpe_export',
	'range'  => $range['preset'],
	'from'   => $range['from'],
	'to'     => $range['to'],
);
if ( $brand ) {
	$export_base['brand'] = array( $brand );
}
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">
		گزارش‌ها
		<span class="lkpe-title__actions">
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $export_base, array( 'type' => 'movers', 'format' => 'xlsx' ) ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>">Excel تغییرات</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $export_base, array( 'type' => 'movers', 'format' => 'csv' ) ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>">CSV تغییرات</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $export_base, array( 'type' => 'history', 'format' => 'xlsx' ) ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>">Excel تاریخچه</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $export_base, array( 'type' => 'movers', 'format' => 'pdf' ) ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>" target="_blank" rel="noopener">PDF</a>
		</span>
	</h1>

	<?php lkpe_render_notices(); ?>

	<form method="get" class="lkpe-filters">
		<input type="hidden" name="page" value="lkpe-reports" />
		<select name="range">
			<?php foreach ( LKPE_Reports::presets() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $preset, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
			<option value="custom" <?php selected( $preset, 'custom' ); ?>>بازه دلخواه</option>
		</select>
		<label>از <input type="date" name="from" value="<?php echo esc_attr( $range['from'] ); ?>" dir="ltr" /></label>
		<label>تا <input type="date" name="to" value="<?php echo esc_attr( $range['to'] ); ?>" dir="ltr" /></label>
		<select name="brand">
			<option value="">همه برندها</option>
			<?php foreach ( lkpe_brands() as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $brand, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button button-primary">اعمال</button>
	</form>

	<p class="lkpe-range-note">
		بازه گزارش: <?php echo esc_html( LK_Format::jalali( strtotime( $range['from'] ), 'long' ) ); ?>
		تا <?php echo esc_html( LK_Format::jalali( strtotime( $range['to'] ), 'long' ) ); ?>
	</p>

	<div class="lkpe-cards">
		<div class="lkpe-card">
			<span class="lkpe-card__label">خودروهای دارای تغییر ثبت‌شده</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $summary['observed'] ) ); ?></strong>
		</div>
		<div class="lkpe-card lkpe-card--up">
			<span class="lkpe-card__label">افزایشی</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $summary['up'] ) ); ?></strong>
		</div>
		<div class="lkpe-card lkpe-card--down">
			<span class="lkpe-card__label">کاهشی</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $summary['down'] ) ); ?></strong>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">بدون تغییر</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::number( $summary['flat'] ) ); ?></strong>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">میانگین تغییر</span>
			<strong class="lkpe-card__value"><?php echo esc_html( LK_Format::change_percent( $summary['avg'] ) ); ?></strong>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">بیشترین افزایش</span>
			<strong class="lkpe-card__value lkpe-card__value--sm">
				<?php if ( $summary['max_up'] ) : ?>
					<?php echo esc_html( LK_Format::change_percent( $summary['max_up']['diff_percent'] ) ); ?>
					<a href="<?php echo esc_url( $summary['max_up']['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $summary['max_up']['title'] ); ?></a>
				<?php else : ?>
					—
				<?php endif; ?>
			</strong>
		</div>
		<div class="lkpe-card">
			<span class="lkpe-card__label">بیشترین کاهش</span>
			<strong class="lkpe-card__value lkpe-card__value--sm">
				<?php if ( $summary['max_down'] ) : ?>
					<?php echo esc_html( LK_Format::change_percent( $summary['max_down']['diff_percent'] ) ); ?>
					<a href="<?php echo esc_url( $summary['max_down']['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $summary['max_down']['title'] ); ?></a>
				<?php else : ?>
					—
				<?php endif; ?>
			</strong>
		</div>
	</div>

	<div class="lkpe-grid-2">
		<section class="lkpe-panel">
			<h2>بیشترین افزایش‌ها</h2>
			<?php if ( ! $up ) : ?>
				<p class="lkpe-muted">در این بازه افزایشی ثبت نشده است.</p>
			<?php else : ?>
				<table class="widefat striped lkpe-table">
					<thead><tr><th>خودرو</th><th>ابتدای بازه</th><th>پایان بازه</th><th>تغییر</th></tr></thead>
					<tbody>
						<?php foreach ( $up as $row ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $row['title'] ); ?></a></td>
								<td><?php echo wp_kses_post( lkpe_price_text( $row['first_price'] ) ); ?></td>
								<td><?php echo wp_kses_post( lkpe_price_text( $row['last_price'] ) ); ?></td>
								<td><?php echo wp_kses_post( lkpe_change_text( $row['diff'], $row['diff_percent'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="lkpe-panel">
			<h2>بیشترین کاهش‌ها</h2>
			<?php if ( ! $down ) : ?>
				<p class="lkpe-muted">در این بازه کاهشی ثبت نشده است.</p>
			<?php else : ?>
				<table class="widefat striped lkpe-table">
					<thead><tr><th>خودرو</th><th>ابتدای بازه</th><th>پایان بازه</th><th>تغییر</th></tr></thead>
					<tbody>
						<?php foreach ( $down as $row ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $row['title'] ); ?></a></td>
								<td><?php echo wp_kses_post( lkpe_price_text( $row['first_price'] ) ); ?></td>
								<td><?php echo wp_kses_post( lkpe_price_text( $row['last_price'] ) ); ?></td>
								<td><?php echo wp_kses_post( lkpe_change_text( $row['diff'], $row['diff_percent'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>
	</div>

	<section class="lkpe-panel">
		<h2>مقایسه برندها</h2>
		<?php if ( ! $brands ) : ?>
			<p class="lkpe-muted">قیمتی برای مقایسه ثبت نشده است.</p>
		<?php else : ?>
			<table class="widefat striped lkpe-table">
				<thead>
					<tr><th>برند</th><th>تعداد خودرو</th><th>میانگین قیمت بازار</th><th>میانگین قیمت کارخانه</th><th>میانگین تغییر</th><th>ارزش کل بازار</th></tr>
				</thead>
				<tbody>
					<?php foreach ( $brands as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['brand'] ); ?></td>
							<td><?php echo esc_html( LK_Format::number( $row['vehicles'] ) ); ?></td>
							<td><?php echo wp_kses_post( lkpe_price_text( $row['avg_market'] ) ); ?></td>
							<td><?php echo wp_kses_post( lkpe_price_text( $row['avg_factory'] ) ); ?></td>
							<td><?php echo esc_html( LK_Format::change_percent( $row['avg_change'] ) ); ?></td>
							<td><?php echo wp_kses_post( lkpe_price_text( $row['total_market'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>

	<div class="lkpe-grid-2">
		<section class="lkpe-panel">
			<h2>بیشترین اختلاف بازار و کارخانه</h2>
			<table class="widefat striped lkpe-table">
				<thead><tr><th>خودرو</th><th>کارخانه</th><th>بازار</th><th>اختلاف</th><th>درصد</th></tr></thead>
				<tbody>
					<?php foreach ( $gaps as $row ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $row['title'] ); ?></a></td>
							<td><?php echo wp_kses_post( lkpe_price_text( $row['factory_price'] ) ); ?></td>
							<td><?php echo wp_kses_post( lkpe_price_text( $row['market_price'] ) ); ?></td>
							<td><?php echo wp_kses_post( lkpe_price_text( $row['gap'] ) ); ?></td>
							<td><?php echo esc_html( null === $row['gap_percent'] ? '—' : LK_Format::digits( $row['gap_percent'] ) . '٪' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="lkpe-panel">
			<h2>خودروهای بدون تغییر در بازه</h2>
			<table class="widefat striped lkpe-table">
				<thead><tr><th>خودرو</th><th>قیمت بازار</th><th>آخرین دریافت</th></tr></thead>
				<tbody>
					<?php if ( ! $unchanged ) : ?>
						<tr><td colspan="3" class="lkpe-empty">موردی ثبت نشده است.</td></tr>
					<?php else : ?>
						<?php foreach ( $unchanged as $row ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $row['title'] ); ?></a></td>
								<td><?php echo wp_kses_post( lkpe_price_text( $row['market_price'] ) ); ?></td>
								<td><?php echo esc_html( LK_Format::jalali( strtotime( $row['fetched_at'] ), 'datetime' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</section>
	</div>

	<section class="lkpe-panel">
		<h2>عملکرد دریافت‌ها (۳۰ روز)</h2>
		<table class="widefat striped lkpe-table">
			<thead><tr><th>تعداد اجرا</th><th>موفق</th><th>نیمه‌موفق</th><th>ناموفق</th><th>خطاها</th><th>میانگین مدت</th><th>آخرین اجرا</th></tr></thead>
			<tbody>
				<tr>
					<td><?php echo esc_html( LK_Format::number( $performance['total'] ) ); ?></td>
					<td><?php echo esc_html( LK_Format::number( $performance['success'] ) ); ?></td>
					<td><?php echo esc_html( LK_Format::number( $performance['partial'] ) ); ?></td>
					<td><?php echo esc_html( LK_Format::number( $performance['failed'] ) ); ?></td>
					<td><?php echo esc_html( LK_Format::number( $performance['errors'] ) ); ?></td>
					<td><?php echo esc_html( LK_Format::number( $performance['avg_duration'] ) ); ?> ثانیه</td>
					<td><?php echo $performance['last_run'] ? esc_html( LK_Format::jalali( strtotime( $performance['last_run'] ), 'datetime' ) ) : '—'; ?></td>
				</tr>
			</tbody>
		</table>
	</section>

	<p class="lkpe-attribution">منبع: <a href="https://cofekhodro.ir" target="_blank" rel="noopener">کافه خودرو</a></p>
</div>
