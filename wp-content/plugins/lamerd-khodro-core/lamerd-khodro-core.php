<?php
/**
 * Plugin Name:       Lamerd Khodro Core
 * Plugin URI:        https://lamerdkhodro.ir
 * Description:       هسته اطلاعات خودرو لامرد خودرو: ساختار خودروها، برندها، مشخصات فنی، خدمات مشترک و REST API. منطق کسب‌وکار مستقل از قالب.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Lamerd Khodro
 * Text Domain:       lamerd-khodro
 * Domain Path:       /languages
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'LK_VERSION', '1.0.0' );
define( 'LK_FILE', __FILE__ );
define( 'LK_PATH', plugin_dir_path( __FILE__ ) );
define( 'LK_URL', plugin_dir_url( __FILE__ ) );
define( 'LK_DB_VERSION', '1.0.0' );

require_once LK_PATH . 'includes/class-lk-registry.php';
require_once LK_PATH . 'includes/interface-lk-price-repository.php';
require_once LK_PATH . 'includes/class-lk-null-price-repository.php';
require_once LK_PATH . 'includes/class-lk-format.php';
require_once LK_PATH . 'includes/class-lk-fields.php';
require_once LK_PATH . 'includes/class-lk-post-types.php';
require_once LK_PATH . 'includes/class-lk-listings.php';
require_once LK_PATH . 'includes/class-lk-taxonomies.php';
require_once LK_PATH . 'includes/class-lk-meta-box.php';
require_once LK_PATH . 'includes/class-lk-admin.php';
require_once LK_PATH . 'includes/class-lk-query.php';
require_once LK_PATH . 'includes/class-lk-compare.php';
require_once LK_PATH . 'includes/class-lk-rest.php';
require_once LK_PATH . 'includes/class-lk-ajax.php';
require_once LK_PATH . 'includes/functions.php';

/**
 * Boot the plugin once all plugins are loaded.
 *
 * @return void
 */
function lk_core_boot() {
	load_plugin_textdomain( 'lamerd-khodro', false, dirname( plugin_basename( LK_FILE ) ) . '/languages' );

	LK_Post_Types::init();
	LK_Listings::init();
	LK_Taxonomies::init();
	LK_Meta_Box::init();
	LK_Admin::init();
	LK_Query::init();
	LK_Rest::init();
	LK_Ajax::init();

	do_action( 'lk_core_loaded' );
}
add_action( 'plugins_loaded', 'lk_core_boot', 5 );

/**
 * Activation: register structures then flush rewrite rules.
 *
 * @return void
 */
function lk_core_activate() {
	LK_Post_Types::register();
	LK_Taxonomies::register();
	LK_Fields::register_meta();
	flush_rewrite_rules();

	if ( ! get_option( 'lk_brand_seed_pending' ) && ! get_option( 'lk_brand_seed_done' ) ) {
		update_option( 'lk_brand_seed_pending', 1, false );
	}
}
register_activation_hook( __FILE__, 'lk_core_activate' );

/**
 * Deactivation.
 *
 * @return void
 */
function lk_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'lk_core_deactivate' );
