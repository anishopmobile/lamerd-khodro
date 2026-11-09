<?php
/**
 * Price repository: the only place that reads price tables for display.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Repository
 */
class LKPE_Repository implements LK_Price_Repository_Interface {

	/**
	 * Cache of vehicle price arrays for this request.
	 *
	 * @var array<int,array|null>
	 */
	private static $price_cache = array();

	/**
	 * {@inheritDoc}
	 */
	public function get_vehicle_price( $post_id ) {
		$post_id = (int) $post_id;
		if ( array_key_exists( $post_id, self::$price_cache ) ) {
			return self::$price_cache[ $post_id ];
		}

		$vehicle = LKPE_Vehicles::get_by_post( $post_id );
		if ( ! $vehicle ) {
			self::$price_cache[ $post_id ] = null;
			return null;
		}

		$row = LKPE_Prices::get_current( (int) $vehicle['id'] );
		if ( ! $row ) {
			self::$price_cache[ $post_id ] = null;
			return null;
		}

		$price = $this->format_price( $row, $vehicle );

		self::$price_cache[ $post_id ] = $price;
		return $price;
	}

	/**
	 * Normalise a DB price row into the public price shape.
	 *
	 * @param array $row     Price row.
	 * @param array $vehicle Vehicle row.
	 * @return array
	 */
	private function format_price( $row, $vehicle = array() ) {
		$fetched_ts = $row['fetched_at'] ? strtotime( $row['fetched_at'] ) : 0;

		return array(
			'post_id'              => isset( $vehicle['post_id'] ) ? (int) $vehicle['post_id'] : 0,
			'market_price'         => null !== $row['market_price'] && '' !== $row['market_price'] ? (int) $row['market_price'] : null,
			'factory_price'        => null !== $row['factory_price'] && '' !== $row['factory_price'] ? (int) $row['factory_price'] : null,
			'change_amount'        => null !== $row['change_amount'] && '' !== $row['change_amount'] ? (int) $row['change_amount'] : null,
			'change_percent'       => null !== $row['change_percent'] ? (float) $row['change_percent'] : null,
			'previous_market_price' => ! empty( $row['previous_market_price'] ) ? (int) $row['previous_market_price'] : null,
			'fetched_at'           => $row['fetched_at'],
			'fetched_ts'           => $fetched_ts,
			'declared_at'          => $row['declared_at'],
			'data_status'          => $row['data_status'] ? $row['data_status'] : 'valid',
			'is_stale'             => $this->is_stale( $fetched_ts ),
			'source'               => $row['source'],
			'production_stopped'   => ! empty( $vehicle['production_stopped'] ),
		);
	}

	/**
	 * Whether a price is older than the freshness threshold.
	 *
	 * @param int $timestamp Fetched timestamp.
	 * @return bool
	 */
	private function is_stale( $timestamp ) {
		if ( ! $timestamp ) {
			return true;
		}
		$interval = (int) get_option( 'lkpe_sync_interval', 60 );
		$limit    = max( 3 * HOUR_IN_SECONDS, 2 * $interval * MINUTE_IN_SECONDS );
		return ( time() - $timestamp ) > $limit;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_prices( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'page'            => 1,
				'per_page'        => 20,
				'search'          => '',
				'brand'           => array(),
				'body_type'       => array(),
				'class'           => array(),
				'year_from'       => 0,
				'year_to'         => 0,
				'price_min'       => 0,
				'price_max'       => 0,
				'min'             => 0,
				'max'             => 0,
				'availability'    => '',
				'sort'            => 'price_desc',
				'only_with_price' => true,
				'post_ids'        => array(),
			)
		);

		// API surface uses min/max for the same filter.
		if ( ! $args['price_min'] && ! empty( $args['min'] ) ) {
			$args['price_min'] = absint( $args['min'] );
		}
		if ( ! $args['price_max'] && ! empty( $args['max'] ) ) {
			$args['price_max'] = absint( $args['max'] );
		}

		$page     = max( 1, (int) $args['page'] );
		$per_page = max( 1, min( 100, (int) $args['per_page'] ) );

		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices   = LKPE_Installer::table( 'current_prices' );

		$where  = array( '1=1' );
		$params = array();

		if ( $args['only_with_price'] ) {
			$where[] = 'p.market_price IS NOT NULL';
		}

		$search = trim( (string) $args['search'] );
		if ( '' !== $search ) {
			$normalized = LK_Format::normalize_text( $search );
			$where[]    = '(v.name_normalized LIKE %s OR v.name LIKE %s)';
			$params[]   = '%' . $wpdb->esc_like( $normalized ) . '%';
			$params[]   = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$tax_filters = array(
			'lk_brand'     => $args['brand'],
			'lk_body_type' => $args['body_type'],
			'lk_class'     => $args['class'],
		);
		foreach ( $tax_filters as $taxonomy => $slugs ) {
			if ( is_string( $slugs ) ) {
				$slugs = explode( ',', $slugs );
			}
			$slugs = array_filter( array_map( 'sanitize_title', (array) $slugs ) );
			if ( ! $slugs ) {
				continue;
			}
			$placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
			$where[]      = "v.post_id IN (SELECT tr.object_id FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s AND t.slug IN ($placeholders))";
			$params[] = $taxonomy;
			$params   = array_merge( $params, $slugs );
		}

		$year_from = (int) $args['year_from'];
		$year_to   = (int) $args['year_to'];
		if ( $year_from ) {
			$where[]  = "v.post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_lk_year' AND CAST(meta_value AS UNSIGNED) >= %d)";
			$params[] = $year_from;
		}
		if ( $year_to ) {
			$where[]  = "v.post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_lk_year' AND CAST(meta_value AS UNSIGNED) <= %d)";
			$params[] = $year_to;
		}

		if ( (int) $args['price_min'] > 0 ) {
			$where[]  = 'p.market_price >= %d';
			$params[] = (int) $args['price_min'];
		}
		if ( (int) $args['price_max'] > 0 ) {
			$where[]  = 'p.market_price <= %d';
			$params[] = (int) $args['price_max'];
		}

		if ( 'stopped' === $args['availability'] ) {
			$where[] = 'v.production_stopped = 1';
		} elseif ( 'available' === $args['availability'] ) {
			$where[] = 'v.production_stopped = 0';
		}

		if ( ! empty( $args['post_ids'] ) ) {
			$ids          = array_filter( array_map( 'absint', (array) $args['post_ids'] ) );
			if ( $ids ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$where[]      = "v.post_id IN ($placeholders)";
				$params       = array_merge( $params, $ids );
			}
		}

		$where_sql = implode( ' AND ', $where );

		$base_from = "FROM $vehicles v
			INNER JOIN $prices p ON p.vehicle_id = v.id
			INNER JOIN {$wpdb->posts} w ON w.ID = v.post_id AND w.post_status = 'publish' AND w.post_type = 'lk_vehicle'";

		$order_by = $this->order_sql( $args['sort'] );

		$count_sql = "SELECT COUNT(*) $base_from WHERE $where_sql";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL

		$offset = ( $page - 1 ) * $per_page;

		$sql = "SELECT v.id AS vehicle_id, v.post_id, v.name, v.brand, v.production_stopped, v.source,
				p.factory_price, p.market_price, p.change_amount, p.change_percent,
				p.previous_market_price, p.fetched_at, p.declared_at, p.data_status, p.source AS price_source
			$base_from
			WHERE $where_sql
			ORDER BY $order_by
			LIMIT %d OFFSET %d";

		$query_params = array_merge( $params, array( $per_page, $offset ) );
		$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $query_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		$items = array();
		foreach ( (array) $rows as $row ) {
			$items[] = $this->format_list_item( $row );
		}

		return array(
			'items' => $items,
			'total' => $total,
			'pages' => (int) ceil( $total / $per_page ),
			'page'  => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * ORDER BY clause for a supported sort key.
	 *
	 * @param string $sort Sort key.
	 * @return string
	 */
	private function order_sql( $sort ) {
		switch ( $sort ) {
			case 'price_asc':
				return '(p.market_price IS NULL) ASC, p.market_price ASC, v.name ASC';
			case 'change_desc':
				return 'p.change_percent DESC, p.market_price DESC';
			case 'change_asc':
				return 'p.change_percent ASC, p.market_price DESC';
			case 'name':
				return 'v.name ASC';
			case 'updated':
				return 'p.fetched_at DESC';
			case 'newest':
				return 'v.created_at DESC';
			case 'price_desc':
			default:
				return '(p.market_price IS NULL) ASC, p.market_price DESC, v.name ASC';
		}
	}

	/**
	 * Format a list row.
	 *
	 * @param array $row DB row.
	 * @return array
	 */
	private function format_list_item( $row ) {
		$price = array(
			'post_id'        => (int) $row['post_id'],
			'market_price'   => null !== $row['market_price'] ? (int) $row['market_price'] : null,
			'factory_price'  => null !== $row['factory_price'] ? (int) $row['factory_price'] : null,
			'change_amount'  => null !== $row['change_amount'] ? (int) $row['change_amount'] : null,
			'change_percent' => null !== $row['change_percent'] ? (float) $row['change_percent'] : null,
			'fetched_at'     => $row['fetched_at'],
			'fetched_ts'     => $row['fetched_at'] ? strtotime( $row['fetched_at'] ) : 0,
			'declared_at'    => $row['declared_at'],
			'data_status'    => $row['data_status'],
			'is_stale'       => $this->is_stale( $row['fetched_at'] ? strtotime( $row['fetched_at'] ) : 0 ),
			'source'         => $row['price_source'],
		);

		$brands = get_the_terms( (int) $row['post_id'], 'lk_brand' );
		$brand_name = $row['brand'];
		$brand_slug = '';
		if ( ! is_wp_error( $brands ) && $brands ) {
			$brand_name = $brands[0]->name;
			$brand_slug = $brands[0]->slug;
		}

		return array(
			'vehicle_id'         => (int) $row['vehicle_id'],
			'post_id'            => (int) $row['post_id'],
			'title'              => $row['name'],
			'url'                => get_permalink( (int) $row['post_id'] ),
			'brand'              => $brand_name,
			'brand_slug'         => $brand_slug,
			'production_stopped' => ! empty( $row['production_stopped'] ),
			'source'             => $row['price_source'],
			'market_price'       => $price['market_price'],
			'factory_price'      => $price['factory_price'],
			'gap'                => ( null !== $price['market_price'] && null !== $price['factory_price'] ) ? $price['market_price'] - $price['factory_price'] : null,
			'change_amount'      => $price['change_amount'],
			'change_percent'     => $price['change_percent'],
			'fetched_at'         => $price['fetched_at'],
			'fetched_ts'         => $price['fetched_ts'],
			'declared_at'        => $price['declared_at'],
			'data_status'        => $price['data_status'],
			'is_stale'           => $price['is_stale'],
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_history( $post_id, $from, $to ) {
		global $wpdb;

		$vehicle = LKPE_Vehicles::get_by_post( (int) $post_id );
		if ( ! $vehicle ) {
			return array();
		}

		$table = LKPE_Installer::table( 'price_history' );
		$start = $from . ' 00:00:00';
		$end   = $to . ' 23:59:59';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE vehicle_id = %d AND recorded_at BETWEEN %s AND %s ORDER BY recorded_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL
				(int) $vehicle['id'],
				$start,
				$end
			),
			ARRAY_A
		);

		$rows = (array) $rows;

		// Baseline: last observation before the range keeps the line continuous.
		$baseline = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE vehicle_id = %d AND recorded_at < %s ORDER BY recorded_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL
				(int) $vehicle['id'],
				$start
			),
			ARRAY_A
		);

		if ( $baseline ) {
			array_unshift( $rows, $baseline );
		} else {
			$snapshot_table = LKPE_Installer::table( 'price_snapshots' );
			$baseline       = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $snapshot_table WHERE vehicle_id = %d AND snapshot_date < %s ORDER BY snapshot_date DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL
					(int) $vehicle['id'],
					$from
				),
				ARRAY_A
			);

			if ( $baseline ) {
				$rows[] = array(
					'recorded_at'    => $baseline['snapshot_date'] . ' 12:00:00',
					'market_price'   => $baseline['market_price'],
					'factory_price'  => $baseline['factory_price'],
					'change_amount'  => $baseline['change_amount'],
					'change_percent' => $baseline['change_percent'],
					'record_type'    => 'snapshot',
					'data_status'    => $baseline['data_status'],
					'source'         => 'cafekhodro',
				);
			}
		}

		$by_day = array();
		foreach ( $rows as $row ) {
			$day = substr( $row['recorded_at'], 0, 10 );
			// Keep the last observation of each day.
			$by_day[ $day ] = array(
				'recorded_at'    => $row['recorded_at'],
				'market_price'   => null !== $row['market_price'] ? (int) $row['market_price'] : null,
				'factory_price'  => null !== $row['factory_price'] ? (int) $row['factory_price'] : null,
				'change_amount'  => null !== $row['change_amount'] ? (int) $row['change_amount'] : null,
				'change_percent' => null !== $row['change_percent'] ? (float) $row['change_percent'] : null,
				'record_type'    => $row['record_type'],
				'data_status'    => $row['data_status'],
				'source'         => $row['source'],
			);
		}

		/*
		 * Daily snapshots fill the days without a price change, so the history has one
		 * observation per day instead of only the days the market moved.
		 */
		$snapshots = LKPE_Installer::table( 'price_snapshots' );
		$daily     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $snapshots WHERE vehicle_id = %d AND snapshot_date BETWEEN %s AND %s ORDER BY snapshot_date ASC", // phpcs:ignore WordPress.DB.PreparedSQL
				(int) $vehicle['id'],
				$from,
				$to
			),
			ARRAY_A
		);

		foreach ( (array) $daily as $snapshot ) {
			$day = substr( $snapshot['snapshot_date'], 0, 10 );
			if ( isset( $by_day[ $day ] ) ) {
				continue;
			}
			$by_day[ $day ] = array(
				'recorded_at'    => $snapshot['snapshot_date'] . ' 12:00:00',
				'market_price'   => null !== $snapshot['market_price'] ? (int) $snapshot['market_price'] : null,
				'factory_price'  => null !== $snapshot['factory_price'] ? (int) $snapshot['factory_price'] : null,
				'change_amount'  => null !== $snapshot['change_amount'] ? (int) $snapshot['change_amount'] : null,
				'change_percent' => null !== $snapshot['change_percent'] ? (float) $snapshot['change_percent'] : null,
				'record_type'    => 'snapshot',
				'data_status'    => $snapshot['data_status'],
				'source'         => 'cafekhodro',
			);
		}

		ksort( $by_day );

		return array_values( $by_day );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_change_stats( $args = array() ) {
		$cached = get_transient( 'lkpe_stats_cache' );
		if ( is_array( $cached ) && empty( $args ) ) {
			return $cached;
		}

		global $wpdb;
		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices   = LKPE_Installer::table( 'current_prices' );

		$row = $wpdb->get_row(
			"SELECT COUNT(*) AS total,
				SUM(CASE WHEN p.market_price IS NOT NULL THEN 1 ELSE 0 END) AS with_price,
				SUM(CASE WHEN p.change_amount > 0 THEN 1 ELSE 0 END) AS up,
				SUM(CASE WHEN p.change_amount < 0 THEN 1 ELSE 0 END) AS down,
				SUM(CASE WHEN p.change_amount = 0 THEN 1 ELSE 0 END) AS flat,
				SUM(CASE WHEN p.data_status = 'suspect' THEN 1 ELSE 0 END) AS suspect
			FROM $vehicles v INNER JOIN $prices p ON p.vehicle_id = v.id",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery

		$stats = array(
			'total'      => (int) ( $row['total'] ?? 0 ),
			'with_price' => (int) ( $row['with_price'] ?? 0 ),
			'up'         => (int) ( $row['up'] ?? 0 ),
			'down'       => (int) ( $row['down'] ?? 0 ),
			'flat'       => (int) ( $row['flat'] ?? 0 ),
			'suspect'    => (int) ( $row['suspect'] ?? 0 ),
			'no_price'   => max( 0, (int) ( $row['total'] ?? 0 ) - (int) ( $row['with_price'] ?? 0 ) ),
			'generated'  => current_time( 'mysql' ),
		);

		if ( empty( $args ) ) {
			set_transient( 'lkpe_stats_cache', $stats, 5 * MINUTE_IN_SECONDS );
		}

		return $stats;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_last_sync() {
		global $wpdb;
		$table = LKPE_Installer::table( 'sync_logs' );
		$row   = $wpdb->get_row( "SELECT * FROM $table WHERE status IN ('success','partial') ORDER BY finished_at DESC LIMIT 1", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( ! $row ) {
			return null;
		}

		$row['vehicles_fetched'] = (int) $row['vehicles_fetched'];
		$row['vehicles_new']     = (int) $row['vehicles_new'];
		$row['prices_changed']   = (int) $row['prices_changed'];
		$row['errors_count']     = (int) $row['errors_count'];
		$row['finished_ts']      = $row['finished_at'] ? strtotime( $row['finished_at'] ) : 0;
		$row['is_stale']         = $this->is_stale( $row['finished_ts'] );

		return $row;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_top_movers( $direction = 'up', $limit = 5 ) {
		global $wpdb;

		$limit    = max( 1, min( 20, (int) $limit ) );
		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices   = LKPE_Installer::table( 'current_prices' );
		$order    = 'down' === $direction ? 'ASC' : 'DESC';

		$sql = "SELECT v.id AS vehicle_id, v.post_id, v.name, v.brand, v.production_stopped,
				p.market_price, p.factory_price, p.change_amount, p.change_percent, p.data_status, p.fetched_at
			FROM $vehicles v
			INNER JOIN $prices p ON p.vehicle_id = v.id
			INNER JOIN {$wpdb->posts} w ON w.ID = v.post_id AND w.post_status = 'publish'
			WHERE p.market_price IS NOT NULL AND p.change_percent IS NOT NULL AND p.change_percent <> 0
				AND p.change_amount IS NOT NULL AND p.change_amount <> 0 AND p.data_status <> 'suspect'
			ORDER BY p.change_percent $order
			LIMIT %d";

		$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		$items = array();

		foreach ( (array) $rows as $row ) {
			$items[] = array(
				'post_id'        => (int) $row['post_id'],
				'title'          => $row['name'],
				'url'            => get_permalink( (int) $row['post_id'] ),
				'brand'          => $row['brand'],
				'market_price'   => (int) $row['market_price'],
				'change_amount'  => null !== $row['change_amount'] ? (int) $row['change_amount'] : null,
				'change_percent' => (float) $row['change_percent'],
				'fetched_at'     => $row['fetched_at'],
				'fetched_ts'     => strtotime( $row['fetched_at'] ),
				'data_status'    => $row['data_status'],
			);
		}

		return $items;
	}
}
