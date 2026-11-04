<?php
/**
 * Source vehicle registry and CPT mapping.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Vehicles
 */
class LKPE_Vehicles {

	/**
	 * Per-request map of post_id => vehicle row.
	 *
	 * @var array<int,array>
	 */
	private static $by_post = array();

	/**
	 * Insert or update a source vehicle row.
	 *
	 * @param array $data  Validated data.
	 * @param array $brand Brand definition.
	 * @param string $source Source slug.
	 * @return array|null Vehicle row.
	 */
	public static function upsert( $data, $brand, $source = 'cafekhodro' ) {
		global $wpdb;

		$table = LKPE_Installer::table( 'vehicles' );
		$now   = current_time( 'mysql' );

		$existing = self::get_by_source( $source, $data['source_vehicle_id'] );

		$row = array(
			'source'             => $source,
			'source_vehicle_id'  => $data['source_vehicle_id'],
			'name'               => $data['name'],
			'name_normalized'    => $data['name_normalized'],
			'brand'              => $brand['name'],
			'source_group'       => $data['source_group'],
			'production_stopped' => $data['production_stopped'] ? 1 : 0,
			'updated_at'         => $now,
		);

		if ( $existing ) {
			$wpdb->update( $table, $row, array( 'id' => (int) $existing['id'] ), null, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row['id']      = (int) $existing['id'];
			$row['post_id'] = $existing['post_id'] ? (int) $existing['post_id'] : 0;
			$row['is_new']  = false;
			$row['previous_name'] = $existing['name'];
		} else {
			$row['created_at'] = $now;
			$wpdb->insert( $table, $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row['id']      = (int) $wpdb->insert_id;
			$row['post_id'] = 0;
			$row['is_new']  = true;
			$row['previous_name'] = '';
		}

		return $row['id'] ? $row : null;
	}

	/**
	 * Fetch a vehicle row by source identity.
	 *
	 * @param string $source    Source slug.
	 * @param string $source_id Source vehicle ID.
	 * @return array|null
	 */
	public static function get_by_source( $source, $source_id ) {
		global $wpdb;
		$table = LKPE_Installer::table( 'vehicles' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE source = %s AND source_vehicle_id = %s", $source, (string) $source_id ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Fetch a vehicle row by CPT post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null
	 */
	public static function get_by_post( $post_id ) {
		$post_id = (int) $post_id;
		if ( isset( self::$by_post[ $post_id ] ) ) {
			return self::$by_post[ $post_id ];
		}

		global $wpdb;
		$table = LKPE_Installer::table( 'vehicles' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE post_id = %d", $post_id ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		self::$by_post[ $post_id ] = $row;
		return $row;
	}

	/**
	 * Ensure a CPT post exists for a source vehicle row.
	 *
	 * @param array $vehicle Vehicle row (must contain id, name, post_id, production_stopped, source, source_vehicle_id).
	 * @param array $brand   Brand definition.
	 * @return int Post ID (0 on failure).
	 */
	public static function ensure_post( $vehicle, $brand ) {
		$post_id = isset( $vehicle['post_id'] ) ? (int) $vehicle['post_id'] : 0;

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || 'lk_vehicle' !== $post->post_type ) {
				$post_id = 0;
			}
		}

		if ( ! $post_id ) {
			$found = get_posts(
				array(
					'post_type'      => 'lk_vehicle',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_lk_source_uid', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $vehicle['source'] . ':' . $vehicle['source_vehicle_id'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'no_found_rows'  => true,
				)
			);
			if ( $found ) {
				$post_id = (int) $found[0];
			}
		}

		if ( ! $post_id ) {
			$created = wp_insert_post(
				array(
					'post_type'   => 'lk_vehicle',
					'post_status' => 'publish',
					'post_title'  => $vehicle['name'],
					'post_content' => '',
				),
				true
			);
			if ( is_wp_error( $created ) ) {
				return 0;
			}
			$post_id = (int) $created;
		} elseif ( ! empty( $vehicle['previous_name'] ) && $vehicle['previous_name'] !== $vehicle['name'] ) {
			$post = get_post( $post_id );
			if ( $post && $post->post_title !== $vehicle['name'] ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $vehicle['name'],
					)
				);
			}
		}

		$status = $vehicle['production_stopped'] ? 'stopped' : 'in_production';
		update_post_meta( $post_id, '_lk_source', $vehicle['source'] );
		update_post_meta( $post_id, '_lk_source_id', (string) $vehicle['source_vehicle_id'] );
		update_post_meta( $post_id, '_lk_source_uid', $vehicle['source'] . ':' . $vehicle['source_vehicle_id'] );
		update_post_meta( $post_id, '_lk_production_status', $status );
		update_post_meta( $post_id, '_lk_source_stopped', $vehicle['production_stopped'] ? '1' : '0' );
		update_post_meta( $post_id, '_lk_last_synced', current_time( 'mysql' ) );

		$term_id = LKPE_Brand_Resolver::term_id( $brand );
		if ( $term_id ) {
			wp_set_object_terms( $post_id, array( $term_id ), 'lk_brand', false );
		}

		global $wpdb;
		$wpdb->update(
			LKPE_Installer::table( 'vehicles' ),
			array( 'post_id' => $post_id ),
			array( 'id' => (int) $vehicle['id'] ),
			array( '%d' ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		self::$by_post[ $post_id ] = array_merge( $vehicle, array( 'post_id' => $post_id ) );

		return $post_id;
	}

	/**
	 * Count mapped vehicles.
	 *
	 * @return int
	 */
	public static function count() {
		global $wpdb;
		$table = LKPE_Installer::table( 'vehicles' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
}
