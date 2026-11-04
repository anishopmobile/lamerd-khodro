<?php
/**
 * Scheduling for automatic synchronisation.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Cron
 */
class LKPE_Cron {

	/**
	 * Cron hook.
	 */
	const HOOK = 'lkpe_sync_event';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'ensure_schedule' ) );
	}

	/**
	 * Allowed intervals in minutes.
	 *
	 * @return int[]
	 */
	public static function allowed_intervals() {
		return array( 15, 30, 60, 180, 1440 );
	}

	/**
	 * Current interval.
	 *
	 * @return int
	 */
	public static function interval() {
		$interval = (int) get_option( 'lkpe_sync_interval', 60 );
		return in_array( $interval, self::allowed_intervals(), true ) ? $interval : 60;
	}

	/**
	 * Register custom schedules.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public static function schedules( $schedules ) {
		foreach ( self::allowed_intervals() as $minutes ) {
			$schedules[ 'lkpe_' . $minutes ] = array(
				'interval' => $minutes * MINUTE_IN_SECONDS,
				'display'  => 'لامرد خودرو: هر ' . number_format_i18n( $minutes ) . ' دقیقه',
			);
		}
		return $schedules;
	}

	/**
	 * Ensure the event exists and matches the selected interval.
	 *
	 * @return void
	 */
	public static function ensure_schedule() {
		$current  = wp_get_schedule( self::HOOK );
		$expected = 'lkpe_' . self::interval();

		if ( $current === $expected ) {
			return;
		}

		wp_clear_scheduled_hook( self::HOOK );
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, $expected, self::HOOK );
	}

	/**
	 * Schedule the event.
	 *
	 * @param bool $force Reschedule even if present.
	 * @return void
	 */
	public static function schedule( $force = false ) {
		if ( ! $force && wp_next_scheduled( self::HOOK ) ) {
			return;
		}

		wp_clear_scheduled_hook( self::HOOK );
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'lkpe_' . self::interval(), self::HOOK );
	}

	/**
	 * Clear the event.
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Cron callback.
	 *
	 * @return void
	 */
	public static function run() {
		LKPE_Sync::run( 'cron' );
	}

	/**
	 * Next run timestamp.
	 *
	 * @return int|false
	 */
	public static function next_run() {
		return wp_next_scheduled( self::HOOK );
	}
}
