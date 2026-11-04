<?php
/**
 * Price history screen.
 *
 * @var array $result
 * @var array $args
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$type_labels = array(
	'initial'        => 'ثبت اولیه',
	'market_change'  => 'تغییر قیمت بازار',
	'factory_change' => 'تغییر قیمت کارخانه',
	'snapshot'       => 'عکس لحظه‌ای',
);

$export_args = array(
	'action' => 'lkpe_export',
	'type'   => 'history',
	'range'  => 'custom',
	'from'   => $args['from'],
	'to'     => $args['to'],
);
?>
<div class="wrap lkpe-wrap" dir="rtl">
	<h1 class="lkpe-title">
		تاریخچه قیمت
		<span class="lkpe-title__actions">
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $export_args, array( 'format' => 'csv' ) ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>">خروجی CSV</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $export_args, array( 'format' => 'xlsx' ) ), admin_url( 'admin-post.php' ) ), 'lkpe_export' ) ); ?>">خروجی Excel</a>
		</span>
	</h1>

	<?php lkpe_render_notices(); ?>

	<p class="lkpe-muted">
		تاریخچه از زمان فعال‌شدن سامانه ثبت می‌شود. رکوردهای تاریخی حذف یا بازنویسی نمی‌شوند؛
		تغییر قیمت کارخانه و بازار جداگانه ثبت می‌گردد.
	</p>

	<form method="get" class="lkpe-filters">
		<input type="hidden" name="page" value="lkpe-history" />
		<label>از تاریخ <input type="date" name="from" value="<?php echo esc_attr( $args['from'] ); ?>" dir="ltr" /></label>
		<label>تا تاریخ <input type="date" name="to" value="<?php echo esc_attr( $args['to'] ); ?>" dir="ltr" /></label>
		<select name="record_type">
			<option value="">همه انواع</option>
			<?php foreach ( $type_labels as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $args['record_type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button">اعمال</button>
		<a class="button-link" href="<?php echo esc_url( lkpe_admin_url( 'lkpe-history' ) ); ?>">بازنشانی</a>
	</form>

	<table class="widefat striped lkpe-table">
		<thead>
			<tr>
				<th>زمان ثبت</th>
				<th>خودرو</th>
				<th>نوع ثبت</th>
				<th>قیمت کارخانه</th>
				<th>قیمت بازار</th>
				<th>تغییر</th>
				<th>وضعیت داده</th>
				<th>منبع</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $result['items'] ) ) : ?>
				<tr><td colspan="8" class="lkpe-empty">رکوردی در این بازه ثبت نشده است.</td></tr>
			<?php else : ?>
				<?php foreach ( $result['items'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( LK_Format::jalali( strtotime( $row['recorded_at'] ), 'datetime' ) ); ?></td>
						<td>
							<?php if ( ! empty( $row['post_id'] ) ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( (int) $row['post_id'] ) ); ?>"><?php echo esc_html( $row['name'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $row['name'] ? $row['name'] : '—' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $type_labels[ $row['record_type'] ] ?? $row['record_type'] ); ?></td>
						<td><?php echo wp_kses_post( lkpe_price_text( $row['factory_price'] ) ); ?></td>
						<td><?php echo wp_kses_post( lkpe_price_text( $row['market_price'] ) ); ?></td>
						<td><?php echo wp_kses_post( lkpe_change_text( $row['change_amount'], $row['change_percent'], $row['data_status'] ) ); ?></td>
						<td><?php echo wp_kses_post( lkpe_status_badge( $row['data_status'] ) ); ?></td>
						<td><?php echo esc_html( $row['source'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php
	echo wp_kses_post(
		lkpe_pagination(
			$args['page'],
			$result['pages'],
			array(
				'__page'      => 'lkpe-history',
				'from'        => $args['from'],
				'to'          => $args['to'],
				'record_type' => $args['record_type'],
			)
		)
	);
	?>
</div>
