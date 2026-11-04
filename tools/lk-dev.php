<?php
/**
 * Development CLI for the Lamerd Khodro project.
 *
 * Not part of the distributed theme/plugins. Usage:
 *   php tools/lk-dev.php status
 *   php tools/lk-dev.php activate
 *   php tools/lk-dev.php permalinks
 *   php tools/lk-dev.php sync
 *   php tools/lk-dev.php seed-content
 *   php tools/lk-dev.php errors
 *
 * @package LamerdKhodro\Tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 'CLI only.' );
}

require_once dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/theme.php';

$command = $argv[1] ?? 'status';

switch ( $command ) {
	case 'status':
		echo 'Site: ' . get_option( 'siteurl' ) . PHP_EOL;
		echo 'Theme: ' . get_stylesheet() . PHP_EOL;
		echo 'Core active: ' . ( is_plugin_active( 'lamerd-khodro-core/lamerd-khodro-core.php' ) ? 'yes' : 'no' ) . PHP_EOL;
		echo 'Engine active: ' . ( is_plugin_active( 'lamerd-khodro-price-engine/lamerd-khodro-price-engine.php' ) ? 'yes' : 'no' ) . PHP_EOL;
		echo 'Vehicles: ' . wp_count_posts( 'lk_vehicle' )->publish . PHP_EOL;
		echo 'Brands: ' . wp_count_terms( array( 'taxonomy' => 'lk_brand', 'hide_empty' => false ) ) . PHP_EOL;
		echo 'Posts: ' . wp_count_posts( 'post' )->publish . PHP_EOL;
		echo 'Pages: ' . wp_count_posts( 'page' )->publish . PHP_EOL;
		break;

	case 'activate':
		$result = activate_plugin( 'lamerd-khodro-core/lamerd-khodro-core.php' );
		if ( is_wp_error( $result ) ) {
			echo 'Core error: ' . $result->get_error_message() . PHP_EOL;
		} else {
			echo 'Core activated.' . PHP_EOL;
		}
		if ( is_dir( WP_PLUGIN_DIR . '/lamerd-khodro-price-engine' ) ) {
			$result = activate_plugin( 'lamerd-khodro-price-engine/lamerd-khodro-price-engine.php' );
			if ( is_wp_error( $result ) ) {
				echo 'Engine error: ' . $result->get_error_message() . PHP_EOL;
			} else {
				echo 'Engine activated.' . PHP_EOL;
			}
		}
		if ( wp_get_theme( 'lamerd-khodro' )->exists() ) {
			switch_theme( 'lamerd-khodro' );
			echo 'Theme switched.' . PHP_EOL;
		}
		break;

	case 'permalinks':
		global $wp_rewrite;
		update_option( 'permalink_structure', '/%postname%/' );
		update_option( 'blog_public', 1 );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules( true );
		echo 'Permalinks set to /%postname%/' . PHP_EOL;
		break;

	case 'sync':
		if ( class_exists( 'LKPE_Sync' ) ) {
			$result = LKPE_Sync::run( 'cli' );
			echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL;
		} else {
			echo 'Price engine not loaded.' . PHP_EOL;
		}
		break;

	case 'seed-content':
		if ( function_exists( 'lk_seed_site_content' ) ) {
			lk_seed_site_content();
			echo 'Content seeded.' . PHP_EOL;
		} else {
			echo 'Seeder unavailable (activate the theme first).' . PHP_EOL;
		}
		break;

	case 'errors':
		$log = WP_CONTENT_DIR . '/debug.log';
		if ( file_exists( $log ) ) {
			echo file_get_contents( $log ); // phpcs:ignore
		} else {
			echo 'No debug.log' . PHP_EOL;
		}
		break;

	case 'db':
		global $wpdb;
		foreach ( $wpdb->get_col( 'SHOW TABLES' ) as $table ) {
			if ( false !== strpos( $table, 'lkp_' ) ) {
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM `$table`" ); // phpcs:ignore
				echo $table . ': ' . $count . PHP_EOL;
			}
		}
		break;

	default:
		echo 'Unknown command.' . PHP_EOL;
}
