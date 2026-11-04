<?php
/**
 * Reporting queries (all read from the local database).
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Reports
 */
class LKPE_Reports {

	/**
	 * Report presets.
	 *
	 * @return array<string,string>
	 */
	public static function presets() {
		return array(
			'today' => 'امروز',
			'7'     => '۷ روز گذشته',
			'30'    => '۳۰ روز گذشته',
			'90'    => '۹۰ روز گذشته',
			'365'   => 'یک سال گذشته',
		);
	}

	/**
	 * Resolve a preset or custom range into dates.
	 *
	 * @param string $preset Preset key.
	 * @param string $from   Y-m-d.
	 * @param string $to     Y-m-d.
	 * @return array{from:string,to:string,label:string,preset:string}
	 */
	public static function resolve_range( $preset = '30', $from = '', $to = '' ) {
		$presets = self::presets();
		$now     = current_time( 'timestamp' );

		if ( 'custom' === $preset && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			if ( $from > $to ) {
				list( $from, $to ) = array( $to, $from );
			}
			return array(
				'from'   => $from,
				'to'     => $to,
				'label'  => 'بازه انتخابی',
				'preset' => 'custom',
			);
		}

		if ( ! isset( $presets[ $preset ] ) ) {
			$preset = '30';
		}

		$days = 'today' === $preset ? 0 : (int) $preset;

		return array(
			'from'   => gmdate( 'Y-m-d', $now - ( $days * DAY_IN_SECONDS ) ),
			'to'     => gmdate( 'Y-m-d', $now ),
			'label'  => $presets[ $preset ],
			'preset' => $preset,
		);
	}

	/**
	 * Per-vehicle change inside a range (first vs last observation).
	 *
	 * @param string $from     Y-m-d.
	 * @param string $to       Y-m-d.
	 * @param array  $brand    Brand slugs.
	 * @param string $direction up|down|all.
	 * @param int    $limit    Max rows (0 = all).
	 * @return array
	 */
	public static function movers( $from, $to, $brand = array(), $direction = 'all', $limit = 0 ) {
		global $wpdb;

		$vehicles = LKPE_Installer::table( 'vehicles' );
		$history  = LKPE_Installer::table( 'price_history' );

		$where  = array( "h.recorded_at BETWEEN %s AND %s", "h.market_price IS NOT NULL", "h.data_status <> 'suspect'" );
		$params = array( $from . ' 00:00:00', $to . ' 23:59:59' );

		$brand = array_filter( array_map( 'sanitize_title', (array) $brand ) );
		if ( $brand ) {
			$placeholders = implode( ',', array_fill( 0, count( $brand ), '%s' ) );
			$where[]      = "v.post_id IN (SELECT tr.object_id FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
				WHERE tt.taxonomy = 'lk_brand' AND t.slug IN ($placeholders))";
			$params = array_merge( $params, $brand );
		}

		$where_sql = implode( ' AND ', $where );

		$sql = "SELECT v.id AS vehicle_id, v.post_id, v.name, v.brand,
				( SELECT h1.market_price FROM $history h1
					WHERE h1.vehicle_id = v.id AND h1.recorded_at BETWEEN %s AND %s AND h1.market_price IS NOT NULL AND h1.data_status <> 'suspect'
					ORDER BY h1.recorded_at ASC LIMIT 1 ) AS first_price,
				( SELECT h2.market_price FROM $history h2
					WHERE h2.vehicle_id = v.id AND h2.recorded_at BETWEEN %s AND %s AND h2.market_price IS NOT NULL AND h2.data_status <> 'suspect'
					ORDER BY h2.recorded_at DESC LIMIT 1 ) AS last_price,
				( SELECT MIN(h3.recorded_at) FROM $history h3 WHERE h3.vehicle_id = v.id AND h3.recorded_at BETWEEN %s AND %s ) AS first_at
			FROM $vehicles v
			WHERE EXISTS ( SELECT 1 FROM $history h WHERE h.vehicle_id = v.id AND $where_sql )";

		$range_params = array(
			$from . ' 00:00:00',
			$to . ' 23:59:59',
			$from . ' 00:00:00',
			$to . ' 23:59:59',
			$from . ' 00:00:00',
			$to . ' 23:59:59',
		);

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $range_params, $params ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		$items = array();
		foreach ( (array) $rows as $row ) {
			$first = $row['first_price'];
			$last  = $row['last_price'];

			if ( null === $first || null === $last ) {
				continue;
			}

			$diff = (int) $last - (int) $first;
			if ( 0 === $diff && 'all' !== $direction ) {
				continue;
			}

			$items[] = array(
				'vehicle_id'   => (int) $row['vehicle_id'],
				'post_id'      => (int) $row['post_id'],
				'title'        => $row['name'],
				'brand'        => $row['brand'],
				'url'          => get_permalink( (int) $row['post_id'] ),
				'first_price'  => (int) $first,
				'last_price'   => (int) $last,
				'diff'         => $diff,
				'diff_percent' => $first > 0 ? round( ( $diff / $first ) * 100, 2 ) : null,
				'first_at'     => $row['first_at'],
			);
		}

		usort(
			$items,
			static function ( $a, $b ) use ( $direction ) {
				if ( 'down' === $direction ) {
					return $a['diff'] <=> $b['diff'];
				}
				return $b['diff'] <=> $a['diff'];
			}
		);

		if ( $limit > 0 ) {
			$items = array_slice( $items, 0, (int) $limit );
		}

		return $items;
	}

	/**
	 * Range summary.
	 *
	 * @param string $from Y-m-d.
	 * @param string $to   Y-m-d.
	 * @param array  $brand Brand slugs.
	 * @return array
	 */
	public static function summary( $from, $to, $brand = array() ) {
		$items = self::movers( $from, $to, $brand, 'all' );

		$up    = 0;
		$down  = 0;
		$flat  = 0;
		$sum   = 0.0;
		$max_up = null;
		$max_down = null;

		foreach ( $items as $item ) {
			if ( $item['diff'] > 0 ) {
				++$up;
			} elseif ( $item['diff'] < 0 ) {
				++$down;
			} else {
				++$flat;
			}

			if ( null !== $item['diff_percent'] ) {
				$sum += $item['diff_percent'];
				if ( null === $max_up || $item['diff_percent'] > $max_up['diff_percent'] ) {
					$max_up = $item;
				}
				if ( null === $max_down || $item['diff_percent'] < $max_down['diff_percent'] ) {
					$max_down = $item;
				}
			}
		}

		$count = count( $items );

		return array(
			'observed'  => $count,
			'up'        => $up,
			'down'      => $down,
			'flat'      => $flat,
			'avg'       => $count ? round( $sum / $count, 2 ) : 0,
			'max_up'    => $max_up,
			'max_down'  => $max_down,
			'from'      => $from,
			'to'        => $to,
		);
	}

	/**
	 * Brand comparison.
	 *
	 * @return array
	 */
	public static function brand_comparison() {
		global $wpdb;

		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices   = LKPE_Installer::table( 'current_prices' );

		$sql = "SELECT t.name AS brand, COUNT(*) AS vehicles,
				AVG(p.market_price) AS avg_market, AVG(p.factory_price) AS avg_factory,
				AVG(p.change_percent) AS avg_change, SUM(p.market_price) AS total_market
			FROM $vehicles v
			INNER JOIN $prices p ON p.vehicle_id = v.id AND p.market_price IS NOT NULL
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = v.post_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'lk_brand'
			INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
			GROUP BY t.term_id
			ORDER BY vehicles DESC, avg_market DESC";

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		return array_map(
			static function ( $row ) {
				return array(
					'brand'        => $row['brand'],
					'vehicles'     => (int) $row['vehicles'],
					'avg_market'   => (int) round( (float) $row['avg_market'] ),
					'avg_factory'  => $row['avg_factory'] ? (int) round( (float) $row['avg_factory'] ) : null,
					'avg_change'   => null !== $row['avg_change'] ? round( (float) $row['avg_change'], 2 ) : null,
					'total_market' => (int) $row['total_market'],
				);
			},
			(array) $rows
		);
	}

	/**
	 * Largest market/factory gaps.
	 *
	 * @param int $limit Rows.
	 * @return array
	 */
	public static function gaps( $limit = 15 ) {
		global $wpdb;

		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices   = LKPE_Installer::table( 'current_prices' );

		$sql = $wpdb->prepare(
			"SELECT v.post_id, v.name, v.brand, p.market_price, p.factory_price,
				(p.market_price - p.factory_price) AS gap
			FROM $vehicles v INNER JOIN $prices p ON p.vehicle_id = v.id
			WHERE p.market_price IS NOT NULL AND p.factory_price IS NOT NULL
			ORDER BY gap DESC LIMIT %d",
			(int) $limit
		); // phpcs:ignore WordPress.DB.PreparedSQL

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		return array_map(
			static function ( $row ) {
				$gap = (int) $row['gap'];
				return array(
					'post_id'       => (int) $row['post_id'],
					'title'         => $row['name'],
					'brand'         => $row['brand'],
					'url'           => get_permalink( (int) $row['post_id'] ),
					'market_price'  => (int) $row['market_price'],
					'factory_price' => (int) $row['factory_price'],
					'gap'           => $gap,
					'gap_percent'   => $row['factory_price'] > 0 ? round( ( $gap / (int) $row['factory_price'] ) * 100, 1 ) : null,
				);
			},
			(array) $rows
		);
	}

	/**
	 * Vehicles without any market change in the range.
	 *
	 * @param string $from  Y-m-d.
	 * @param string $to    Y-m-d.
	 * @param int    $limit Rows.
	 * @return array
	 */
	public static function unchanged( $from, $to, $limit = 50 ) {
		global $wpdb;

		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices   = LKPE_Installer::table( 'current_prices' );
		$history  = LKPE_Installer::table( 'price_history' );

		$sql = $wpdb->prepare(
			"SELECT v.post_id, v.name, v.brand, p.market_price, p.change_amount, p.change_percent, p.fetched_at
			FROM $vehicles v INNER JOIN $prices p ON p.vehicle_id = v.id
			WHERE p.market_price IS NOT NULL
			AND NOT EXISTS (
				SELECT 1 FROM $history h WHERE h.vehicle_id = v.id
				AND h.recorded_at BETWEEN %s AND %s AND h.record_type = 'market_change'
			)
			ORDER BY p.market_price DESC LIMIT %d",
			$from . ' 00:00:00',
			$to . ' 23:59:59',
			(int) $limit
		); // phpcs:ignore WordPress.DB.PreparedSQL

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		return array_map(
			static function ( $row ) {
				return array(
					'post_id'        => (int) $row['post_id'],
					'title'          => $row['name'],
					'brand'          => $row['brand'],
					'url'            => get_permalink( (int) $row['post_id'] ),
					'market_price'   => (int) $row['market_price'],
					'change_amount'  => null !== $row['change_amount'] ? (int) $row['change_amount'] : null,
					'change_percent' => null !== $row['change_percent'] ? (float) $row['change_percent'] : null,
					'fetched_at'     => $row['fetched_at'],
				);
			},
			(array) $rows
		);
	}

	/**
	 * Sync performance statistics.
	 *
	 * @param int $days Lookback days.
	 * @return array
	 */
	public static function sync_performance( $days = 30 ) {
		global $wpdb;

		$logs  = LKPE_Installer::table( 'sync_logs' );
		$since = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( (int) $days * DAY_IN_SECONDS ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT status, COUNT(*) AS runs, SUM(errors_count) AS errors, AVG(duration) AS avg_duration,
					SUM(vehicles_fetched) AS fetched, SUM(prices_changed) AS changed, SUM(vehicles_new) AS new_vehicles,
					MAX(finished_at) AS last_run
				FROM $logs WHERE started_at >= %s GROUP BY status",
				$since
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		$out = array(
			'success' => 0,
			'partial' => 0,
			'failed'  => 0,
			'running' => 0,
			'total'   => 0,
			'errors'  => 0,
			'avg_duration' => 0,
			'last_run' => null,
		);

		foreach ( (array) $rows as $row ) {
			$status = $row['status'];
			$out[ $status ] = (int) $row['runs'];
			$out['total']  += (int) $row['runs'];
			$out['errors'] += (int) $row['errors'];
			if ( null === $out['last_run'] || $row['last_run'] > $out['last_run'] ) {
				$out['last_run'] = $row['last_run'];
			}
			if ( $row['avg_duration'] ) {
				$out['avg_duration'] = round( (float) $row['avg_duration'], 2 );
			}
		}

		return $out;
	}

	/**
	 * Sync log listing.
	 *
	 * @param int $page     Page.
	 * @param int $per_page Per page.
	 * @return array{items:array,total:int,pages:int}
	 */
	public static function logs( $page = 1, $per_page = 20 ) {
		global $wpdb;
		$table    = LKPE_Installer::table( 'sync_logs' );
		$per_page = max( 1, min( 100, (int) $per_page ) );
		$offset   = ( max( 1, (int) $page ) - 1 ) * $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY started_at DESC LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		return array(
			'items' => (array) $rows,
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * History listing for admin with filters.
	 *
	 * @param array $args Filters.
	 * @return array{items:array,total:int,pages:int}
	 */
	public static function history_list( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'page'       => 1,
				'per_page'   => 30,
				'vehicle_id' => 0,
				'record_type' => '',
				'source'     => '',
				'from'       => '',
				'to'         => '',
			)
		);

		$history  = LKPE_Installer::table( 'price_history' );
		$vehicles = LKPE_Installer::table( 'vehicles' );

		$where  = array( '1=1' );
		$params = array();

		if ( $args['vehicle_id'] ) {
			$where[]  = 'h.vehicle_id = %d';
			$params[] = (int) $args['vehicle_id'];
		}
		if ( $args['record_type'] ) {
			$where[]  = 'h.record_type = %s';
			$params[] = sanitize_key( $args['record_type'] );
		}
		if ( $args['source'] ) {
			$where[]  = 'h.source = %s';
			$params[] = sanitize_key( $args['source'] );
		}
		if ( $args['from'] ) {
			$where[]  = 'h.recorded_at >= %s';
			$params[] = $args['from'] . ' 00:00:00';
		}
		if ( $args['to'] ) {
			$where[]  = 'h.recorded_at <= %s';
			$params[] = $args['to'] . ' 23:59:59';
		}

		$where_sql = implode( ' AND ', $where );
		$per_page  = max( 1, min( 100, (int) $args['per_page'] ) );
		$offset    = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM $history h WHERE $where_sql";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL

		$sql = "SELECT h.*, v.name, v.post_id, v.brand FROM $history h
			LEFT JOIN $vehicles v ON v.id = h.vehicle_id
			WHERE $where_sql
			ORDER BY h.recorded_at DESC, h.id DESC
			LIMIT %d OFFSET %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, array( $per_page, $offset ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		return array(
			'items' => (array) $rows,
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Daily count of market changes for the dashboard chart.
	 *
	 * @param int $days Days.
	 * @return array<int,array{date:string,label:string,count:int}>
	 */
	public static function daily_change_counts( $days = 14 ) {
		global $wpdb;

		$table = LKPE_Installer::table( 'price_history' );
		$days  = max( 1, min( 90, (int) $days ) );
		$since = gmdate( 'Y-m-d 00:00:00', current_time( 'timestamp' ) - ( ( $days - 1 ) * DAY_IN_SECONDS ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(recorded_at) AS day, COUNT(*) AS changes FROM $table
				WHERE recorded_at >= %s AND record_type = 'market_change'
				GROUP BY DATE(recorded_at)",
				$since
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ $row['day'] ] = (int) $row['changes'];
		}

		$out = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$timestamp = current_time( 'timestamp' ) - ( $i * DAY_IN_SECONDS );
			$day       = gmdate( 'Y-m-d', $timestamp );
			$out[]     = array(
				'date'  => $day,
				'label' => LK_Format::jalali( $timestamp, 'short' ),
				'count' => $map[ $day ] ?? 0,
			);
		}

		return $out;
	}
}
