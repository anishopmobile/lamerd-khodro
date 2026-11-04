<?php
/**
 * Admin AJAX endpoints.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Ajax
 */
class LKPE_Ajax {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_lkpe_sync_now', array( __CLASS__, 'sync' ) );
		add_action( 'wp_ajax_lkpe_snapshot', array( __CLASS__, 'snapshot' ) );
	}

	/**
	 * Shared guard.
	 *
	 * @return void
	 */
	private static function guard() {
		check_ajax_referer( 'lkpe_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی مجاز نیست.' ), 403 );
		}
	}

	/**
	 * Manual synchronisation.
	 *
	 * @return void
	 */
	public static function sync() {
		self::guard();

		$result = LKPE_Sync::run( 'manual' );

		if ( empty( $result['success'] ) && empty( $result['status'] ) ) {
			wp_send_json_error( $result, 409 );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Create today's snapshot.
	 *
	 * @return void
	 */
	public static function snapshot() {
		self::guard();

		$rows = LKPE_Prices::create_snapshot();

		wp_send_json_success(
			array(
				'message' => 'عکس لحظه‌ای امروز ثبت شد: ' . number_format_i18n( $rows ) . ' ردیف.',
			)
		);
	}
}
