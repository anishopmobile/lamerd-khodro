<?php
/**
 * Current price, history and snapshot persistence.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Prices
 */
class LKPE_Prices {

	/**
	 * Store a new observation and return what changed.
	 *
	 * @param int    $vehicle_id Vehicle row ID.
	 * @param int    $post_id    CPT post ID.
	 * @param array  $data       Validated price data.
	 * @param string $source     Source slug.
	 * @return array{changed:bool,new:bool,history:int}
	 */
	public static function observe( $vehicle_id, $post_id, $data, $source = 'cafekhodro' ) {
		global $wpdb;

		$table    = LKPE_Installer::table( 'current_prices' );
		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE vehicle_id = %d AND source = %s", $vehicle_id, $source ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);

		$now = current_time( 'mysql' );

		$new = array(
			'vehicle_id'     => (int) $vehicle_id,
			'factory_price'  => $data['factory_price'],
			'market_price'   => $data['market_price'],
			'change_amount'  => $data['change_amount'],
			'change_percent' => $data['change_percent'],
			'declared_at'    => $data['declared_at'],
			'fetched_at'     => $now,
			'data_status'    => $data['data_status'],
			'source'         => $source,
		);

		$result = array(
			'changed' => false,
			'new'     => false,
			'history' => 0,
		);

		if ( ! $existing ) {
			$new['previous_market_price'] = null;
			$wpdb->insert( $table, $new ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result['new']     = true;
			$result['changed'] = true;

			if ( null !== $data['market_price'] || null !== $data['factory_price'] ) {
				self::add_history( $vehicle_id, $data, 'initial', $source );
				++$result['history'];
			}
		} else {
			$market_changed  = (int) ( $existing['market_price'] ?? 0 ) !== (int) ( $data['market_price'] ?? 0 );
			$factory_changed = (int) ( $existing['factory_price'] ?? 0 ) !== (int) ( $data['factory_price'] ?? 0 );

			$previous_market = $market_changed ? $existing['market_price'] : $existing['previous_market_price'];

			$update = array(
				'factory_price'        => $data['factory_price'],
				'market_price'         => $data['market_price'],
				'change_amount'        => $data['change_amount'],
				'change_percent'       => $data['change_percent'],
				'previous_market_price' => $previous_market,
				'declared_at'          => $data['declared_at'],
				'fetched_at'           => $now,
				'data_status'          => $data['data_status'],
			);

			$wpdb->update( $table, $update, array( 'id' => (int) $existing['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			$result['changed'] = $market_changed || $factory_changed;

			if ( $market_changed ) {
				self::add_history( $vehicle_id, $data, 'market_change', $source );
				++$result['history'];
			}
			if ( $factory_changed ) {
				self::add_history( $vehicle_id, $data, 'factory_change', $source );
				++$result['history'];
			}
		}

		// Denormalised mirrors used for sorting and filtering in WP_Query.
		if ( $post_id ) {
			if ( null === $data['market_price'] ) {
				delete_post_meta( $post_id, '_lk_market_price' );
			} else {
				update_post_meta( $post_id, '_lk_market_price', (int) $data['market_price'] );
			}
			if ( null === $data['factory_price'] ) {
				delete_post_meta( $post_id, '_lk_factory_price' );
			} else {
				update_post_meta( $post_id, '_lk_factory_price', (int) $data['factory_price'] );
			}
			if ( null === $data['change_percent'] ) {
				delete_post_meta( $post_id, '_lk_change_percent' );
			} else {
				update_post_meta( $post_id, '_lk_change_percent', (float) $data['change_percent'] );
			}
		}

		return $result;
	}

	/**
	 * Append an immutable history row.
	 *
	 * @param int    $vehicle_id Vehicle ID.
	 * @param array  $data       Price data.
	 * @param string $type       Record type.
	 * @param string $source     Source slug.
	 * @return int Inserted ID.
	 */
	public static function add_history( $vehicle_id, $data, $type, $source = 'cafekhodro' ) {
		global $wpdb;

		$wpdb->insert(
			LKPE_Installer::table( 'price_history' ),
			array(
				'vehicle_id'         => (int) $vehicle_id,
				'factory_price'      => $data['factory_price'],
				'market_price'       => $data['market_price'],
				'change_amount'      => $data['change_amount'],
				'change_percent'     => $data['change_percent'],
				'recorded_at'        => current_time( 'mysql' ),
				'source'             => $source,
				'record_type'        => $type,
				'data_status'        => $data['data_status'],
				'source_declared_at' => $data['declared_at'],
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * Daily snapshot (idempotent per day).
	 *
	 * @param string|null $date Y-m-d, defaults to today.
	 * @return int Rows affected.
	 */
	public static function create_snapshot( $date = null ) {
		global $wpdb;

		$date    = $date ? $date : current_time( 'Y-m-d' );
		$snaps   = LKPE_Installer::table( 'price_snapshots' );
		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices  = LKPE_Installer::table( 'current_prices' );
		$now     = current_time( 'mysql' );

		$sql = $wpdb->prepare(
			"INSERT INTO $snaps (snapshot_date, vehicle_id, factory_price, market_price, change_amount, change_percent, data_status, created_at)
			SELECT %s, v.id, p.factory_price, p.market_price, p.change_amount, p.change_percent, p.data_status, %s
			FROM $vehicles v INNER JOIN $prices p ON p.vehicle_id = v.id
			ON DUPLICATE KEY UPDATE factory_price = VALUES(factory_price), market_price = VALUES(market_price),
			change_amount = VALUES(change_amount), change_percent = VALUES(change_percent), data_status = VALUES(data_status)",
			$date,
			$now
		); // phpcs:ignore WordPress.DB.PreparedSQL

		$affected = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		return (int) $affected;
	}

	/**
	 * Current price row for a vehicle row ID.
	 *
	 * @param int $vehicle_id Vehicle row ID.
	 * @return array|null
	 */
	public static function get_current( $vehicle_id ) {
		global $wpdb;
		$table = LKPE_Installer::table( 'current_prices' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE vehicle_id = %d", $vehicle_id ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Count current price rows.
	 *
	 * @return int
	 */
	public static function count() {
		global $wpdb;
		$table = LKPE_Installer::table( 'current_prices' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	/**
	 * Count history rows.
	 *
	 * @return int
	 */
	public static function history_count() {
		global $wpdb;
		$table = LKPE_Installer::table( 'price_history' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
