<?php
/**
 * Plugin Name:       Lamerd Khodro Plans
 * Description:       طرح‌های فروش و ثبت درخواست ثبت‌نام خودرو: مدیریت طرح‌ها با تاریخ شمسی، تصاویر، بخشنامه و کاتالوگ، نمایش طرح‌های فعال و غیرفعال در صفحهٔ اصلی و ثبت درخواست با AJAX.
 * Version:           1.1.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Lamerd Khodro
 * Text Domain:       lamerd-khodro-plans
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

define( 'LKP_VERSION', '1.1.1' );
define( 'LKP_FILE', __FILE__ );
define( 'LKP_PATH', plugin_dir_path( __FILE__ ) );
define( 'LKP_URL', plugin_dir_url( __FILE__ ) );

/** Private directory for applicant documents and downloadable plan files (never web readable). */
define( 'LKP_PRIVATE_DIR', WP_CONTENT_DIR . '/lkp-private' );

/** Capability required to manage plans, documents and requests. */
define( 'LKP_CAP', 'lkp_manage_plans' );

require_once LKP_PATH . 'includes/class-lkp-jalali.php';
require_once LKP_PATH . 'includes/class-lkp-files.php';
require_once LKP_PATH . 'includes/class-lkp-plans.php';
require_once LKP_PATH . 'includes/class-lkp-requests.php';
require_once LKP_PATH . 'includes/class-lkp-admin.php';
require_once LKP_PATH . 'includes/class-lkp-frontend.php';
require_once LKP_PATH . 'includes/functions.php';

/**
 * Boot the plugin.
 *
 * @return void
 */
function lkp_boot() {
	LKP_Plans::init();
	LKP_Requests::init();
	LKP_Admin::init();
	LKP_Frontend::init();

	do_action( 'lkp_loaded' );
}
add_action( 'plugins_loaded', 'lkp_boot', 6 );

/**
 * Create the private upload directory and grant the capability on activation.
 *
 * @return void
 */
function lkp_activate() {
	LKP_Files::protect_dir( LKP_PRIVATE_DIR );

	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( LKP_CAP ) ) {
		$role->add_cap( LKP_CAP );
	}

	LKP_Plans::register_post_type();
	LKP_Requests::register_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'lkp_activate' );

/**
 * Flush rewrite rules on deactivation.
 *
 * @return void
 */
function lkp_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'lkp_deactivate' );
