<?php
/**
 * Lamerd Khodro theme bootstrap.
 *
 * Presentation only: every data access goes through the lk_* helper API.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'LK_THEME_VERSION', '1.4.0' );
define( 'LK_THEME_PATH', get_template_directory() );
define( 'LK_THEME_URI', get_template_directory_uri() );

require_once LK_THEME_PATH . '/inc/setup.php';
require_once LK_THEME_PATH . '/inc/enqueue.php';
require_once LK_THEME_PATH . '/inc/icons.php';
require_once LK_THEME_PATH . '/inc/mega-menu.php';
require_once LK_THEME_PATH . '/inc/template-tags.php';
require_once LK_THEME_PATH . '/inc/ajax.php';
require_once LK_THEME_PATH . '/inc/seo.php';
require_once LK_THEME_PATH . '/inc/admin.php';
