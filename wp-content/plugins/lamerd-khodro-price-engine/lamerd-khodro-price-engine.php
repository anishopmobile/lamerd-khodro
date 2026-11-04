<?php
/**
 * Plugin Name:       Lamerd Khodro Price Engine
 * Plugin URI:        https://lamerdkhodro.ir
 * Description:       موتور قیمت خودرو لامرد خودرو: دریافت، اعتبارسنجی، ذخیره دائمی، تاریخچه، تحلیل، گزارش و خروجی قیمت خودروها.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Requires Plugins:  lamerd-khodro-core
 * Author:            Lamerd Khodro
 * Text Domain:       lamerd-khodro
 * Domain Path:       /languages
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

define( 'LKPE_VERSION', '1.0.0' );
define( 'LKPE_FILE', __FILE__ );
define( 'LKPE_PATH', plugin_dir_path( __FILE__ ) );
define( 'LKPE_URL', plugin_dir_url( __FILE__ ) );
define( 'LKPE_DB_VERSION', '1.0.0' );

require_once LKPE_PATH . 'includes/class-lkpe-installer.php';
require_once LKPE_PATH . 'includes/class-lkpe-source.php';
require_once LKPE_PATH . 'includes/class-lkpe-validator.php';
require_once LKPE_PATH . 'includes/class-lkpe-brand-resolver.php';
require_once LKPE_PATH . 'includes/class-lkpe-vehicles.php';
require_once LKPE_PATH . 'includes/class-lkpe-prices.php';
require_once LKPE_PATH . 'includes/class-lkpe-repository.php';
require_once LKPE_PATH . 'includes/providers/interface-lkpe-provider.php';
require_once LKPE_PATH . 'includes/providers/class-lkpe-provider-cafekhodro.php';
require_once LKPE_PATH . 'includes/class-lkpe-sync.php';
require_once LKPE_PATH . 'includes/class-lkpe-reports.php';
require_once LKPE_PATH . 'includes/class-lkpe-export.php';
require_once LKPE_PATH . 'includes/class-lkpe-admin.php';
require_once LKPE_PATH . 'includes/class-lkpe-ajax.php';
require_once LKPE_PATH . 'includes/class-lkpe-cron.php';
require_once LKPE_PATH . 'includes/functions.php';

/**
 * Boot the engine.
 *
 * @return void
 */
function lkpe_boot() {
	load_plugin_textdomain( 'lamerd-khodro', false, dirname( plugin_basename( LKPE_FILE ) ) . '/languages' );

	if ( ! class_exists( 'LK_Registry' ) || ! class_exists( 'LK_Fields' ) ) {
		add_action( 'admin_notices', 'lkpe_missing_core_notice' );
		return;
	}

	LKPE_Installer::maybe_upgrade();

	LK_Registry::set( 'price_repository', new LKPE_Repository() );

	LKPE_Admin::init();
	LKPE_Ajax::init();
	LKPE_Cron::init();

	/**
	 * Engine fully loaded; other modules may register providers.
	 */
	do_action( 'lkpe_loaded' );
}
add_action( 'plugins_loaded', 'lkpe_boot', 10 );

/**
 * Dependency notice.
 *
 * @return void
 */
function lkpe_missing_core_notice() {
	echo '<div class="notice notice-error"><p>برای کارکرد «مدیریت قیمت خودرو»، افزونه «Lamerd Khodro Core» باید فعال باشد.</p></div>';
}

/**
 * Activation.
 *
 * @return void
 */
function lkpe_activate() {
	LKPE_Installer::install();
	LKPE_Installer::seed_source();
	LKPE_Cron::schedule();
}
register_activation_hook( __FILE__, 'lkpe_activate' );

/**
 * Deactivation: keep data, remove scheduled event.
 *
 * @return void
 */
function lkpe_deactivate() {
	LKPE_Cron::unschedule();
	delete_option( 'lkpe_sync_lock' );
}
register_deactivation_hook( __FILE__, 'lkpe_deactivate' );

/**
 * WP-CLI command.
 *
 * @return void
 */
function lkpe_register_cli() {
	if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
		return;
	}
	WP_CLI::add_command(
		'lkpe sync',
		static function ( $args, $assoc_args ) {
			$result = LKPE_Sync::run( 'cli' );
			if ( ! empty( $result['success'] ) ) {
				WP_CLI::success( wp_json_encode( $result, JSON_UNESCAPED_UNICODE ) );
				return;
			}
			WP_CLI::error( $result['message'] ?? 'همگام‌سازی ناموفق بود.' );
		}
	);
	WP_CLI::add_command(
		'lkpe snapshot',
		static function () {
			$count = LKPE_Prices::create_snapshot();
			WP_CLI::success( 'Snapshot rows: ' . (int) $count );
		}
	);
}
add_action( 'cli_init', 'lkpe_register_cli' );
