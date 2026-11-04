<?php
/**
 * Synchronisation pipeline.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Sync
 */
class LKPE_Sync {

	/**
	 * Lock option name.
	 */
	const LOCK_OPTION = 'lkpe_sync_lock';

	/**
	 * Lock lifetime in seconds.
	 */
	const LOCK_TTL = 900;

	/**
	 * Run a full synchronisation.
	 *
	 * @param string $trigger manual|cron|cli.
	 * @return array
	 */
	public static function run( $trigger = 'manual' ) {
		$source = LKPE_Source::get( 'cafekhodro' );
		if ( ! $source ) {
			return array(
				'success' => false,
				'message' => 'منبع اطلاعات تعریف نشده است.',
			);
		}
		if ( empty( $source['is_active'] ) ) {
			return array(
				'success' => false,
				'message' => 'منبع اطلاعات غیرفعال است.',
			);
		}

		if ( ! self::lock() ) {
			return array(
				'success' => false,
				'message' => 'همگام‌سازی دیگری در حال اجراست. لطفاً بعداً تلاش کنید.',
			);
		}

		$started = microtime( true );
		$log_id  = self::start_log( $trigger );

		$counts = array(
			'fetched'   => 0,
			'new'       => 0,
			'changed'   => 0,
			'unchanged' => 0,
			'errors'    => 0,
			'history'   => 0,
		);
		$errors   = array();
		$warnings = 0;
		$status   = 'failed';
		$message  = '';
		$details  = array();

		global $wpdb;
		$transaction = false;

		try {
			$table_id = (int) LKPE_Source::setting( 'cafekhodro', 'table_id', 1 );
			$provider = self::provider( 'cafekhodro' );
			$table    = $provider->fetch_table( $table_id );

			$details['table']      = $table['title'] ?? '';
			$details['updated_at'] = $table['updated_at'] ?? '';
			$details['declared_at'] = $table['declared_at'] ?? null;

			$declared = ! empty( $table['declared_at'] ) ? $table['declared_at'] : null;

			$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$transaction = true;

			foreach ( $table['cars'] as $row ) {
				++$counts['fetched'];

				$validated = LKPE_Validator::car( $row );
				if ( ! $validated['ok'] ) {
					++$counts['errors'];
					$errors[] = ( isset( $row['id'] ) ? '#' . $row['id'] . ': ' : '' ) . implode( ' ', $validated['errors'] );
					continue;
				}
				$warnings += count( $validated['warnings'] );

				$data                 = $validated['data'];
				$data['declared_at']  = $declared;

				$brand = LKPE_Brand_Resolver::resolve( $data['source_group'], $data['name'] );

				$vehicle = LKPE_Vehicles::upsert( $data, $brand );
				if ( ! $vehicle ) {
					++$counts['errors'];
					$errors[] = '#' . $data['source_vehicle_id'] . ': ذخیره خودرو ناموفق بود.';
					continue;
				}

				if ( ! empty( $vehicle['is_new'] ) ) {
					++$counts['new'];
				}

				$post_id = LKPE_Vehicles::ensure_post( $vehicle, $brand );

				$observation = LKPE_Prices::observe( $vehicle['id'], $post_id, $data );
				$counts['history'] += (int) $observation['history'];

				if ( $observation['changed'] ) {
					if ( ! $observation['new'] ) {
						++$counts['changed'];
					}
				} else {
					++$counts['unchanged'];
				}
			}

			LKPE_Prices::create_snapshot();

			$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$transaction = false;

			$status = $counts['fetched'] > 0 && 0 === $counts['errors'] ? 'success' : ( $counts['fetched'] > 0 ? 'partial' : 'failed' );
			$message = sprintf(
				'دریافت %1$s خودرو، %2$s خودروی جدید، %3$s قیمت تغییرکرده، %4$s خطا.',
				number_format_i18n( $counts['fetched'] ),
				number_format_i18n( $counts['new'] ),
				number_format_i18n( $counts['changed'] ),
				number_format_i18n( $counts['errors'] )
			);

			if ( 'failed' !== $status ) {
				LKPE_Source::record_success( 'cafekhodro' );
			} else {
				LKPE_Source::record_error( 'cafekhodro', $message );
			}
		} catch ( Throwable $e ) {
			if ( $transaction ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			$status  = 'failed';
			$message = 'همگام‌سازی ناموفق بود: ' . $e->getMessage();
			$errors[] = $e->getMessage();
			LKPE_Source::record_error( 'cafekhodro', $e->getMessage() );
		} finally {
			self::unlock();
		}

		$duration = round( microtime( true ) - $started, 2 );

		self::finish_log(
			$log_id,
			array(
				'finished_at'       => current_time( 'mysql' ),
				'duration'          => $duration,
				'vehicles_fetched'  => $counts['fetched'],
				'vehicles_new'      => $counts['new'],
				'prices_changed'    => $counts['changed'],
				'prices_unchanged'  => $counts['unchanged'],
				'errors_count'      => $counts['errors'],
				'status'            => $status,
				'message'           => $message,
				'details'           => wp_json_encode(
					array(
						'errors'        => array_slice( $errors, 0, 50 ),
						'warnings'      => $warnings,
						'history_rows'  => $counts['history'],
						'table'         => $details,
					),
					JSON_UNESCAPED_UNICODE
				),
			)
		);

		delete_transient( 'lkpe_stats_cache' );

		return array(
			'success'  => 'failed' !== $status,
			'status'   => $status,
			'message'  => $message,
			'counts'   => $counts,
			'duration' => $duration,
			'errors'   => array_slice( $errors, 0, 10 ),
		);
	}

	/**
	 * Resolve a provider instance.
	 *
	 * @param string $id Provider id.
	 * @return LKPE_Provider
	 * @throws Exception When unknown.
	 */
	public static function provider( $id ) {
		$providers = apply_filters(
			'lkpe_providers',
			array(
				'cafekhodro' => 'LKPE_Provider_CafeKhodro',
			)
		);

		if ( ! isset( $providers[ $id ] ) || ! class_exists( $providers[ $id ] ) ) {
			throw new Exception( 'منبع درخواستی پشتیبانی نمی‌شود.' );
		}

		return new $providers[ $id ]();
	}

	/**
	 * Acquire the sync lock.
	 *
	 * @return bool
	 */
	private static function lock() {
		$now      = time();
		$existing = get_option( self::LOCK_OPTION );
		if ( is_array( $existing ) && isset( $existing['time'] ) && ( $now - (int) $existing['time'] ) < self::LOCK_TTL ) {
			return false;
		}
		if ( false !== $existing ) {
			delete_option( self::LOCK_OPTION );
		}

		return add_option(
			self::LOCK_OPTION,
			array(
				'time' => $now,
				'by'   => get_current_user_id(),
			),
			'',
			false
		);
	}

	/**
	 * Release the lock.
	 *
	 * @return void
	 */
	private static function unlock() {
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * Create a running log row.
	 *
	 * @param string $trigger Trigger type.
	 * @return int Log ID.
	 */
	private static function start_log( $trigger ) {
		global $wpdb;
		$wpdb->insert(
			LKPE_Installer::table( 'sync_logs' ),
			array(
				'started_at'   => current_time( 'mysql' ),
				'status'       => 'running',
				'trigger_type' => in_array( $trigger, array( 'manual', 'cron', 'cli' ), true ) ? $trigger : 'manual',
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * Finalise a log row.
	 *
	 * @param int   $log_id Log ID.
	 * @param array $data   Final data.
	 * @return void
	 */
	private static function finish_log( $log_id, $data ) {
		if ( ! $log_id ) {
			return;
		}
		global $wpdb;
		$wpdb->update( LKPE_Installer::table( 'sync_logs' ), $data, array( 'id' => (int) $log_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
