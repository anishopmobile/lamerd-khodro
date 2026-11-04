<?php
/**
 * Print-ready PDF export view.
 *
 * @var array $dataset
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

$site_name = get_bloginfo( 'name' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<title><?php echo esc_html( $dataset['title'] ); ?> — <?php echo esc_html( $site_name ); ?></title>
	<style>
		@page { size: A4 landscape; margin: 12mm; }
		* { box-sizing: border-box; }
		body {
			font-family: Tahoma, "Segoe UI", sans-serif;
			color: #14222f;
			margin: 0;
			padding: 24px;
			background: #f4f6f8;
			font-size: 11px;
		}
		.sheet {
			background: #fff;
			padding: 20px 24px;
			max-width: 1180px;
			margin: 0 auto;
		}
		.head {
			display: flex;
			justify-content: space-between;
			align-items: flex-end;
			border-bottom: 3px solid #0a1f33;
			padding-bottom: 10px;
			margin-bottom: 14px;
		}
		h1 { font-size: 20px; margin: 0 0 4px; }
		.brand { font-size: 13px; color: #5b6b7a; }
		.meta { font-size: 10px; color: #5b6b7a; text-align: left; line-height: 1.8; }
		table { width: 100%; border-collapse: collapse; }
		thead th {
			background: #0a1f33;
			color: #fff;
			font-weight: 600;
			padding: 7px 6px;
			text-align: right;
			font-size: 10px;
		}
		tbody td { padding: 6px; border-bottom: 1px solid #e2e8ef; font-size: 10px; }
		tbody tr:nth-child(even) { background: #f7f9fb; }
		.num { font-variant-numeric: tabular-nums; unicode-bidi: isolate; }
		.neg { color: #b02a20; }
		.pos { color: #0f7a55; }
		footer {
			margin-top: 14px;
			border-top: 1px solid #dde5ed;
			padding-top: 8px;
			font-size: 9px;
			color: #5b6b7a;
			display: flex;
			justify-content: space-between;
		}
		.toolbar {
			max-width: 1180px;
			margin: 0 auto 12px;
			display: flex;
			gap: 8px;
			justify-content: flex-end;
		}
		.toolbar button {
			font-family: inherit;
			background: #f26522;
			color: #fff;
			border: 0;
			padding: 9px 18px;
			border-radius: 6px;
			cursor: pointer;
			font-size: 12px;
		}
		@media print {
			body { background: #fff; padding: 0; }
			.toolbar { display: none; }
			.sheet { max-width: none; padding: 0; }
			thead { display: table-header-group; }
			tr { page-break-inside: avoid; }
		}
	</style>
</head>
<body>
	<div class="toolbar">
		<button type="button" onclick="window.print()">چاپ / ذخیره به‌صورت PDF</button>
	</div>

	<div class="sheet">
		<div class="head">
			<div>
				<h1><?php echo esc_html( $dataset['title'] ); ?></h1>
				<div class="brand"><?php echo esc_html( $site_name ); ?> — گزارش مدیریتی قیمت خودرو</div>
			</div>
			<div class="meta">
				تاریخ گزارش: <?php echo esc_html( LK_Format::jalali( current_time( 'timestamp' ), 'long' ) ); ?><br />
				تعداد ردیف: <?php echo esc_html( LK_Format::number( count( $dataset['rows'] ) ) ); ?><br />
				واحد: تومان
			</div>
		</div>

		<table>
			<thead>
				<tr>
					<?php foreach ( $dataset['columns'] as $column ) : ?>
						<th><?php echo esc_html( $column['label'] ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $dataset['rows'] ) ) : ?>
					<tr><td colspan="<?php echo esc_attr( count( $dataset['columns'] ) ); ?>">داده‌ای برای این بازه ثبت نشده است.</td></tr>
				<?php else : ?>
					<?php foreach ( $dataset['rows'] as $row ) : ?>
						<tr>
							<?php foreach ( $dataset['columns'] as $column ) : ?>
								<?php
								$value = $row[ $column['key'] ] ?? null;
								$class = '';
								if ( in_array( $column['type'], array( 'int', 'float' ), true ) ) {
									$class = 'num';
									if ( null !== $value && is_numeric( $value ) && (float) $value < 0 && in_array( $column['key'], array( 'change_amount', 'diff', 'diff_percent', 'change_percent' ), true ) ) {
										$class .= ' neg';
									}
								}
								?>
								<td class="<?php echo esc_attr( $class ); ?>">
									<?php
									if ( null === $value || '' === $value ) {
										echo '—';
									} elseif ( in_array( $column['type'], array( 'int', 'float' ), true ) && is_numeric( $value ) ) {
										echo esc_html( ( 'int' === $column['type'] ) ? LK_Format::price( (int) $value ) : LK_Format::change_percent( (float) $value ) );
									} else {
										echo esc_html( $value );
									}
									?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<footer>
			<span>منبع: کافه خودرو — https://cofekhodro.ir</span>
			<span>تولیدشده در <?php echo esc_html( $site_name ); ?> — <?php echo esc_html( home_url( '/' ) ); ?></span>
		</footer>
	</div>
</body>
</html>
