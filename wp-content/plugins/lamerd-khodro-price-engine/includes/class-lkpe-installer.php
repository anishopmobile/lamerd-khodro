<?php
/**
 * Database installer and migrations.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Installer
 */
class LKPE_Installer {

	/**
	 * Table name helper.
	 *
	 * @param string $name Short name without prefix.
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'lkp_' . $name;
	}

	/**
	 * Create or upgrade tables.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$vehicles = self::table( 'vehicles' );
		$prices   = self::table( 'current_prices' );
		$history  = self::table( 'price_history' );
		$sources  = self::table( 'price_sources' );
		$logs     = self::table( 'sync_logs' );
		$snaps    = self::table( 'price_snapshots' );

		$sql = array();

		$sql[] = "CREATE TABLE $vehicles (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source varchar(32) NOT NULL DEFAULT 'cafekhodro',
			source_vehicle_id varchar(64) NOT NULL,
			post_id bigint(20) unsigned DEFAULT NULL,
			name varchar(191) NOT NULL,
			name_normalized varchar(191) NOT NULL,
			brand varchar(100) DEFAULT NULL,
			source_group varchar(100) DEFAULT NULL,
			production_stopped tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY source_vehicle (source,source_vehicle_id),
			KEY name_normalized (name_normalized),
			KEY brand (brand),
			KEY post_id (post_id)
		) $charset;";

		$sql[] = "CREATE TABLE $prices (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			vehicle_id bigint(20) unsigned NOT NULL,
			factory_price bigint(20) DEFAULT NULL,
			market_price bigint(20) DEFAULT NULL,
			change_amount bigint(20) DEFAULT NULL,
			change_percent decimal(8,2) DEFAULT NULL,
			previous_market_price bigint(20) DEFAULT NULL,
			declared_at datetime DEFAULT NULL,
			fetched_at datetime NOT NULL,
			data_status varchar(20) NOT NULL DEFAULT 'valid',
			source varchar(32) NOT NULL DEFAULT 'cafekhodro',
			PRIMARY KEY  (id),
			UNIQUE KEY vehicle_source (vehicle_id,source),
			KEY fetched_at (fetched_at),
			KEY data_status (data_status)
		) $charset;";

		$sql[] = "CREATE TABLE $history (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			vehicle_id bigint(20) unsigned NOT NULL,
			factory_price bigint(20) DEFAULT NULL,
			market_price bigint(20) DEFAULT NULL,
			change_amount bigint(20) DEFAULT NULL,
			change_percent decimal(8,2) DEFAULT NULL,
			recorded_at datetime NOT NULL,
			source varchar(32) NOT NULL DEFAULT 'cafekhodro',
			record_type varchar(20) NOT NULL DEFAULT 'change',
			data_status varchar(20) NOT NULL DEFAULT 'valid',
			source_declared_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY vehicle_recorded (vehicle_id,recorded_at),
			KEY recorded_at (recorded_at),
			KEY record_type (record_type)
		) $charset;";

		$sql[] = "CREATE TABLE $sources (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(32) NOT NULL,
			name varchar(191) NOT NULL,
			api_url varchar(255) NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			settings longtext DEFAULT NULL,
			last_success_at datetime DEFAULT NULL,
			last_error_at datetime DEFAULT NULL,
			last_error text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset;";

		$sql[] = "CREATE TABLE $logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			started_at datetime NOT NULL,
			finished_at datetime DEFAULT NULL,
			duration decimal(8,2) DEFAULT NULL,
			vehicles_fetched int(11) NOT NULL DEFAULT 0,
			vehicles_new int(11) NOT NULL DEFAULT 0,
			prices_changed int(11) NOT NULL DEFAULT 0,
			prices_unchanged int(11) NOT NULL DEFAULT 0,
			errors_count int(11) NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'running',
			trigger_type varchar(20) NOT NULL DEFAULT 'manual',
			message text DEFAULT NULL,
			details longtext DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY started_at (started_at)
		) $charset;";

		$sql[] = "CREATE TABLE $snaps (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			snapshot_date date NOT NULL,
			vehicle_id bigint(20) unsigned NOT NULL,
			factory_price bigint(20) DEFAULT NULL,
			market_price bigint(20) DEFAULT NULL,
			change_amount bigint(20) DEFAULT NULL,
			change_percent decimal(8,2) DEFAULT NULL,
			data_status varchar(20) NOT NULL DEFAULT 'valid',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY date_vehicle (snapshot_date,vehicle_id),
			KEY snapshot_date (snapshot_date)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( 'lkpe_db_version', LKPE_DB_VERSION, false );
	}

	/**
	 * Run migrations when the stored version is behind.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$stored = get_option( 'lkpe_db_version' );
		if ( LKPE_DB_VERSION !== $stored ) {
			self::install();
			self::seed_source();
			delete_transient( 'lkpe_stats_cache' );
		}
	}

	/**
	 * Ensure the default source row exists.
	 *
	 * @return void
	 */
	public static function seed_source() {
		global $wpdb;
		$table = self::table( 'price_sources' );
		$now   = current_time( 'mysql' );

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE slug = %s", 'cafekhodro' ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $exists ) {
			return;
		}

		$wpdb->insert(
			$table,
			array(
				'slug'       => 'cafekhodro',
				'name'       => 'کافه خودرو',
				'api_url'    => 'https://cofekhodro.ir/wp-json/cafekhodro/v1',
				'is_active'  => 1,
				'settings'   => wp_json_encode(
					array(
						'table_id' => 1,
						'unit'     => 'toman',
						'attribution' => array(
							'name' => 'کافه خودرو',
							'url'  => 'https://cofekhodro.ir',
						),
					)
				),
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}
}
