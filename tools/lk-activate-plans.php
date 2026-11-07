<?php
/**
 * Activate the Lamerd Khodro Plans plugin from the command line.
 *
 * Usage: php tools/lk-activate-plans.php
 */

require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'lamerd-khodro-plans/lamerd-khodro-plans.php';

if ( is_plugin_active( $plugin ) ) {
	echo "already active\n";
	exit( 0 );
}

$result = activate_plugin( $plugin );

if ( is_wp_error( $result ) ) {
	echo 'error: ' . $result->get_error_message() . "\n";
	exit( 1 );
}

echo "activated\n";
