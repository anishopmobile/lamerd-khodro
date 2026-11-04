<?php
/**
 * Data export: CSV, XLSX and print-ready PDF.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Export
 */
class LKPE_Export {

	/**
	 * Build a dataset.
	 *
	 * @param string $type    current|history|movers.
	 * @param array  $filters Filters.
	 * @return array{title:string,columns:array,rows:array,filters:array}
	 */
	public static function dataset( $type = 'current', $filters = array() ) {
		global $wpdb;

		$range = LKPE_Reports::resolve_range(
			$filters['range'] ?? '30',
			$filters['from'] ?? '',
			$filters['to'] ?? ''
		);

		$brand = isset( $filters['brand'] ) && $filters['brand'] ? (array) $filters['brand'] : array();
		$brand = array_filter( array_map( 'sanitize_title', $brand ) );

		if ( 'history' === $type ) {
			$table = LKPE_Installer::table( 'price_history' );
			$where  = array( 'h.recorded_at BETWEEN %s AND %s' );
			$params = array( $range['from'] . ' 00:00:00', $range['to'] . ' 23:59:59' );

			if ( $brand ) {
				$placeholders = implode( ',', array_fill( 0, count( $brand ), '%s' ) );
				$where[]      = "v.post_id IN (SELECT tr.object_id FROM {$wpdb->term_relationships} tr
					INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
					WHERE tt.taxonomy = 'lk_brand' AND t.slug IN ($placeholders))";
				$params = array_merge( $params, $brand );
			}

			$sql = "SELECT h.recorded_at, v.name, v.brand, h.factory_price, h.market_price,
					h.change_amount, h.change_percent, h.record_type, h.data_status, h.source
				FROM $table h LEFT JOIN " . LKPE_Installer::table( 'vehicles' ) . " v ON v.id = h.vehicle_id
				WHERE " . implode( ' AND ', $where ) . '
				ORDER BY h.recorded_at DESC LIMIT 50000';

			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

			$columns = array(
				array( 'key' => 'recorded_at', 'label' => 'زمان ثبت', 'type' => 'text' ),
				array( 'key' => 'name', 'label' => 'نام خودرو', 'type' => 'text' ),
				array( 'key' => 'brand', 'label' => 'برند', 'type' => 'text' ),
				array( 'key' => 'factory_price', 'label' => 'قیمت کارخانه', 'type' => 'int' ),
				array( 'key' => 'market_price', 'label' => 'قیمت بازار', 'type' => 'int' ),
				array( 'key' => 'change_amount', 'label' => 'تغییر', 'type' => 'int' ),
				array( 'key' => 'change_percent', 'label' => 'درصد تغییر', 'type' => 'float' ),
				array( 'key' => 'record_type', 'label' => 'نوع ثبت', 'type' => 'text' ),
				array( 'key' => 'data_status', 'label' => 'وضعیت داده', 'type' => 'text' ),
				array( 'key' => 'source', 'label' => 'منبع', 'type' => 'text' ),
			);

			return array(
				'title'   => 'تاریخچه قیمت — ' . $range['label'],
				'columns' => $columns,
				'rows'    => array_map( 'wp_unslash', (array) $rows ),
				'filters' => $range,
			);
		}

		if ( 'movers' === $type ) {
			$items   = LKPE_Reports::movers( $range['from'], $range['to'], $brand, 'all' );
			$rows    = array_map(
				static function ( $item ) {
					return array(
						'name'         => $item['title'],
						'brand'        => $item['brand'],
						'first_price'  => $item['first_price'],
						'last_price'   => $item['last_price'],
						'diff'         => $item['diff'],
						'diff_percent' => $item['diff_percent'],
					);
				},
				$items
			);

			return array(
				'title'   => 'تغییرات قیمت — ' . $range['label'],
				'columns' => array(
					array( 'key' => 'name', 'label' => 'نام خودرو', 'type' => 'text' ),
					array( 'key' => 'brand', 'label' => 'برند', 'type' => 'text' ),
					array( 'key' => 'first_price', 'label' => 'قیمت ابتدای بازه', 'type' => 'int' ),
					array( 'key' => 'last_price', 'label' => 'قیمت پایان بازه', 'type' => 'int' ),
					array( 'key' => 'diff', 'label' => 'تغییر', 'type' => 'int' ),
					array( 'key' => 'diff_percent', 'label' => 'درصد تغییر', 'type' => 'float' ),
				),
				'rows'    => $rows,
				'filters' => $range,
			);
		}

		$result = lk_get_prices(
			array(
				'page'     => 1,
				'per_page' => 100,
				'brand'    => $brand,
				'sort'     => 'price_desc',
			)
		);

		$rows    = array();
		$total   = (int) $result['total'];
		$pages   = max( 1, (int) $result['pages'] );

		for ( $page = 1; $page <= $pages; $page++ ) {
			if ( $page > 1 ) {
				$result = lk_get_prices(
					array(
						'page'     => $page,
						'per_page' => 100,
						'brand'    => $brand,
						'sort'     => 'price_desc',
					)
				);
			}
			foreach ( $result['items'] as $item ) {
				$rows[] = array(
					'name'           => $item['title'],
					'brand'          => $item['brand'],
					'factory_price'  => $item['factory_price'],
					'market_price'   => $item['market_price'],
					'gap'            => $item['gap'],
					'change_amount'  => $item['change_amount'],
					'change_percent' => $item['change_percent'],
					'production'     => $item['production_stopped'] ? 'متوقف' : 'فعال',
					'status'         => lk_status_label( $item['data_status'] ),
					'fetched_at'     => $item['fetched_at'],
				);
			}
		}

		return array(
			'title'   => 'آخرین قیمت‌ها — ' . LK_Format::jalali( current_time( 'timestamp' ), 'long' ),
			'columns' => array(
				array( 'key' => 'name', 'label' => 'نام خودرو', 'type' => 'text' ),
				array( 'key' => 'brand', 'label' => 'برند', 'type' => 'text' ),
				array( 'key' => 'factory_price', 'label' => 'قیمت کارخانه', 'type' => 'int' ),
				array( 'key' => 'market_price', 'label' => 'قیمت بازار', 'type' => 'int' ),
				array( 'key' => 'gap', 'label' => 'اختلاف بازار و کارخانه', 'type' => 'int' ),
				array( 'key' => 'change_amount', 'label' => 'تغییر', 'type' => 'int' ),
				array( 'key' => 'change_percent', 'label' => 'درصد تغییر', 'type' => 'float' ),
				array( 'key' => 'production', 'label' => 'وضعیت تولید', 'type' => 'text' ),
				array( 'key' => 'status', 'label' => 'وضعیت داده', 'type' => 'text' ),
				array( 'key' => 'fetched_at', 'label' => 'زمان دریافت', 'type' => 'text' ),
			),
			'rows'    => $rows,
			'filters' => $range,
			'total'   => $total,
		);
	}

	/**
	 * Handle an export request.
	 *
	 * @return void
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.', 403 );
		}

		check_admin_referer( 'lkpe_export' );

		$format  = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'csv';
		$type    = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'current';
		$filters = array(
			'range' => isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '30',
			'from'  => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
			'to'    => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '',
			'brand' => isset( $_GET['brand'] ) ? array_map( 'sanitize_title', (array) wp_unslash( $_GET['brand'] ) ) : array(),
		);

		if ( ! in_array( $type, array( 'current', 'history', 'movers' ), true ) ) {
			$type = 'current';
		}

		$dataset = self::dataset( $type, $filters );
		$name    = 'lamerd-khodro-' . $type . '-' . gmdate( 'Ymd-His', current_time( 'timestamp' ) );

		if ( 'pdf' === $format ) {
			include LKPE_PATH . 'views/export-pdf.php';
			exit;
		}

		if ( 'xlsx' === $format ) {
			$binary = self::xlsx( $dataset );
			nocache_headers();
			header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
			header( 'Content-Disposition: attachment; filename="' . $name . '.xlsx"' );
			header( 'Content-Length: ' . strlen( $binary ) );
			echo $binary; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $name . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
		fputcsv( $out, wp_list_pluck( $dataset['columns'], 'label' ) );

		foreach ( $dataset['rows'] as $row ) {
			$line = array();
			foreach ( $dataset['columns'] as $column ) {
				$value = $row[ $column['key'] ] ?? '';
				$line[] = is_null( $value ) ? '' : $value;
			}
			fputcsv( $out, $line );
		}
		fclose( $out );
		exit;
	}

	/**
	 * Build an XLSX binary with real numeric cells and RTL sheet.
	 *
	 * @param array $dataset Dataset.
	 * @return string
	 */
	public static function xlsx( $dataset ) {
		$tmp = wp_tempnam( 'lkpe-xlsx' );
		$zip = new ZipArchive();

		if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
			return '';
		}

		$zip->addFromString( '[Content_Types].xml', self::content_types() );
		$zip->addFromString( '_rels/.rels', self::root_rels() );
		$zip->addFromString( 'xl/workbook.xml', self::workbook() );
		$zip->addFromString( 'xl/_rels/workbook.xml.rels', self::workbook_rels() );
		$zip->addFromString( 'xl/styles.xml', self::styles() );
		$zip->addFromString( 'xl/worksheets/sheet1.xml', self::sheet( $dataset ) );
		$zip->close();

		$binary = file_get_contents( $tmp ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData
		@unlink( $tmp );

		return $binary ? $binary : '';
	}

	/**
	 * Escape XML text.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function xml( $value ) {
		return htmlspecialchars( (string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Content types.
	 *
	 * @return string
	 */
	private static function content_types() {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
			. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
			. '<Default Extension="xml" ContentType="application/xml"/>'
			. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
			. '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
			. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
			. '</Types>';
	}

	/**
	 * Root relationships.
	 *
	 * @return string
	 */
	private static function root_rels() {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
			. '</Relationships>';
	}

	/**
	 * Workbook.
	 *
	 * @return string
	 */
	private static function workbook() {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
			. '<sheets><sheet name="قیمت‌ها" sheetId="1" r:id="rId1"/></sheets>'
			. '</workbook>';
	}

	/**
	 * Workbook relationships.
	 *
	 * @return string
	 */
	private static function workbook_rels() {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
			. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
			. '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
			. '</Relationships>';
	}

	/**
	 * Minimal stylesheet.
	 *
	 * @return string
	 */
	private static function styles() {
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
			. '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
			. '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
			. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
			. '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
			. '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
			. '</styleSheet>';
	}

	/**
	 * Sheet XML.
	 *
	 * @param array $dataset Dataset.
	 * @return string
	 */
	private static function sheet( $dataset ) {
		$rows = '';

		$header_cells = '';
		$col_index    = 0;
		foreach ( $dataset['columns'] as $column ) {
			$header_cells .= '<c r="' . self::column_letter( $col_index ) . '1" t="inlineStr" s="1"><is><t>' . self::xml( $column['label'] ) . '</t></is></c>';
			++$col_index;
		}
		$rows .= '<row r="1">' . $header_cells . '</row>';

		$row_number = 1;
		foreach ( $dataset['rows'] as $row ) {
			++$row_number;
			$cells    = '';
			$col_index = 0;

			foreach ( $dataset['columns'] as $column ) {
				$value = $row[ $column['key'] ] ?? null;
				$ref   = self::column_letter( $col_index ) . $row_number;

				if ( null === $value || '' === $value ) {
					$cells .= '<c r="' . $ref . '"/>';
				} elseif ( 'int' === $column['type'] && is_numeric( $value ) ) {
					$cells .= '<c r="' . $ref . '"><v>' . (int) $value . '</v></c>';
				} elseif ( 'float' === $column['type'] && is_numeric( $value ) ) {
					$cells .= '<c r="' . $ref . '"><v>' . (float) $value . '</v></c>';
				} else {
					$cells .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . self::xml( $value ) . '</t></is></c>';
				}
				++$col_index;
			}

			$rows .= '<row r="' . $row_number . '">' . $cells . '</row>';
		}

		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
			. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
			. '<sheetViews><sheetView rightToLeft="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
			. '<sheetFormatPr defaultRowHeight="18"/>'
			. '<cols><col min="1" max="1" width="38" customWidth="1"/><col min="2" max="12" width="20" customWidth="1"/></cols>'
			. '<sheetData>' . $rows . '</sheetData>'
			. '</worksheet>';
	}

	/**
	 * Spreadsheet column letter.
	 *
	 * @param int $index Zero-based column.
	 * @return string
	 */
	private static function column_letter( $index ) {
		$letters = '';
		++$index;
		while ( $index > 0 ) {
			$mod     = ( $index - 1 ) % 26;
			$letters = chr( 65 + $mod ) . $letters;
			$index   = (int) ( ( $index - $mod ) / 26 );
		}
		return $letters;
	}
}
