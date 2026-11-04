<?php
/**
 * Admin menu, screens and form handlers.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Admin
 */
class LKPE_Admin {

	/**
	 * Capability required for all screens.
	 */
	const CAP = 'manage_options';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_lkpe_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_lkpe_save_source', array( __CLASS__, 'save_source' ) );
		add_action( 'admin_post_lkpe_run_sync', array( __CLASS__, 'run_sync' ) );
		add_action( 'admin_post_lkpe_snapshot_now', array( __CLASS__, 'snapshot' ) );
		add_action( 'admin_post_lkpe_export', array( 'LKPE_Export', 'handle' ) );
	}

	/**
	 * Admin menu.
	 *
	 * @return void
	 */
	public static function menu() {
		add_menu_page(
			'مدیریت قیمت خودرو',
			'مدیریت قیمت خودرو',
			self::CAP,
			'lkpe',
			array( __CLASS__, 'screen_dashboard' ),
			'dashicons-chart-line',
			27
		);

		$pages = array(
			'lkpe'           => array( 'داشبورد', 'screen_dashboard' ),
			'lkpe-vehicles'  => array( 'خودروها', 'screen_vehicles' ),
			'lkpe-prices'    => array( 'آخرین قیمت‌ها', 'screen_prices' ),
			'lkpe-history'   => array( 'تاریخچه قیمت', 'screen_history' ),
			'lkpe-reports'   => array( 'گزارش‌ها', 'screen_reports' ),
			'lkpe-sources'   => array( 'منابع اطلاعات', 'screen_sources' ),
			'lkpe-sync'      => array( 'همگام‌سازی', 'screen_sync' ),
			'lkpe-settings'  => array( 'تنظیمات', 'screen_settings' ),
		);

		foreach ( $pages as $slug => $config ) {
			add_submenu_page(
				'lkpe',
				$config[0],
				$config[0],
				self::CAP,
				$slug,
				array( __CLASS__, $config[1] )
			);
		}
	}

	/**
	 * Assets on engine screens.
	 *
	 * @param string $hook Admin hook.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'lkpe' ) ) {
			return;
		}

		wp_enqueue_style( 'lkpe-admin', LKPE_URL . 'assets/css/admin.css', array(), LKPE_VERSION );
		wp_enqueue_script( 'lkpe-admin', LKPE_URL . 'assets/js/admin.js', array( 'jquery' ), LKPE_VERSION, true );
		wp_localize_script(
			'lkpe-admin',
			'lkpeAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'lkpe_admin' ),
				'strings' => array(
					'syncing'   => 'در حال دریافت اطلاعات از منبع…',
					'syncDone'  => 'همگام‌سازی پایان یافت.',
					'syncError' => 'همگام‌سازی ناموفق بود.',
					'confirm'   => 'این عملیات اطلاعات را از منبع دریافت می‌کند. ادامه می‌دهید؟',
				),
			)
		);
	}

	/**
	 * Render a view with data.
	 *
	 * @param string $view View file name.
	 * @param array  $data Data.
	 * @return void
	 */
	private static function view( $view, $data = array() ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', 403 );
		}

		$file = LKPE_PATH . 'views/' . $view . '.php';
		if ( ! file_exists( $file ) ) {
			echo '<div class="wrap"><p>نمای موردنظر یافت نشد.</p></div>';
			return;
		}

		// Views are plain templates that read their data as local variables.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data, EXTR_SKIP );

		include $file;
	}

	/**
	 * Dashboard screen.
	 *
	 * @return void
	 */
	public static function screen_dashboard() {
		$stats       = lk_get_stats();
		$last_sync   = lk_get_last_sync();
		$performance = LKPE_Reports::sync_performance( 30 );
		$logs        = LKPE_Reports::logs( 1, 8 );
		$movers      = LKPE_Reports::movers(
			gmdate( 'Y-m-d', current_time( 'timestamp' ) - DAY_IN_SECONDS ),
			gmdate( 'Y-m-d', current_time( 'timestamp' ) ),
			array(),
			'down',
			5
		);

		self::view(
			'dashboard',
			array(
				'stats'       => $stats,
				'last_sync'   => $last_sync,
				'performance' => $performance,
				'logs'        => $logs,
				'movers'      => $movers,
				'source'      => LKPE_Source::get( 'cafekhodro' ),
				'vehicle_count' => LKPE_Vehicles::count(),
				'history_count' => LKPE_Prices::history_count(),
				'daily'       => LKPE_Reports::daily_change_counts( 14 ),
				'next_run'    => LKPE_Cron::next_run(),
			)
		);
	}

	/**
	 * Vehicles screen.
	 *
	 * @return void
	 */
	public static function screen_vehicles() {
		global $wpdb;

		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 30;

		$vehicles = LKPE_Installer::table( 'vehicles' );
		$prices   = LKPE_Installer::table( 'current_prices' );

		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $search ) {
			$where[]  = 'v.name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$where_sql = implode( ' AND ', $where );
		$total     = (int) ( $params
			? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $vehicles v WHERE $where_sql", $params ) ) // phpcs:ignore WordPress.DB.PreparedSQL
			: $wpdb->get_var( "SELECT COUNT(*) FROM $vehicles v" ) ); // phpcs:ignore WordPress.DB.PreparedSQL

		$sql = "SELECT v.*, p.market_price, p.factory_price, p.change_amount, p.change_percent, p.data_status, p.fetched_at
			FROM $vehicles v LEFT JOIN $prices p ON p.vehicle_id = v.id
			WHERE $where_sql ORDER BY v.name ASC LIMIT %d OFFSET %d";

		$rows = $wpdb->get_results(
			$wpdb->prepare( $sql, array_merge( $params, array( $per_page, ( $paged - 1 ) * $per_page ) ) ),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL

		self::view(
			'vehicles',
			array(
				'rows'     => (array) $rows,
				'total'    => $total,
				'pages'    => (int) ceil( $total / $per_page ),
				'paged'    => $paged,
				'search'   => $search,
			)
		);
	}

	/**
	 * Current prices screen.
	 *
	 * @return void
	 */
	public static function screen_prices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$brand    = isset( $_GET['brand'] ) ? sanitize_title( wp_unslash( $_GET['brand'] ) ) : '';
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$sort     = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'price_desc';
		// phpcs:enable

		$result = lk_get_prices(
			array(
				'page'            => $paged,
				'per_page'        => 30,
				'search'          => $search,
				'brand'           => $brand ? array( $brand ) : array(),
				'sort'            => $sort,
				'only_with_price' => false,
				'availability'    => '',
			)
		);

		self::view(
			'prices',
			array(
				'result' => $result,
				'paged'  => $paged,
				'search' => $search,
				'brand'  => $brand,
				'status' => $status,
				'sort'   => $sort,
				'stats'  => lk_get_stats(),
			)
		);
	}

	/**
	 * History screen.
	 *
	 * @return void
	 */
	public static function screen_history() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args = array(
			'page'        => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
			'per_page'    => 40,
			'vehicle_id'  => isset( $_GET['vehicle_id'] ) ? absint( $_GET['vehicle_id'] ) : 0,
			'record_type' => isset( $_GET['record_type'] ) ? sanitize_key( wp_unslash( $_GET['record_type'] ) ) : '',
			'from'        => isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '',
			'to'          => isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '',
		);
		// phpcs:enable

		self::view(
			'history',
			array(
				'result' => LKPE_Reports::history_list( $args ),
				'args'   => $args,
			)
		);
	}

	/**
	 * Reports screen.
	 *
	 * @return void
	 */
	public static function screen_reports() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$preset = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '30';
		$from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		$brand  = isset( $_GET['brand'] ) ? sanitize_title( wp_unslash( $_GET['brand'] ) ) : '';
		// phpcs:enable

		$range = LKPE_Reports::resolve_range( $preset, $from, $to );
		$brands = $brand ? array( $brand ) : array();

		self::view(
			'reports',
			array(
				'range'     => $range,
				'preset'    => $preset,
				'brand'     => $brand,
				'summary'   => LKPE_Reports::summary( $range['from'], $range['to'], $brands ),
				'up'        => LKPE_Reports::movers( $range['from'], $range['to'], $brands, 'up', 10 ),
				'down'      => LKPE_Reports::movers( $range['from'], $range['to'], $brands, 'down', 10 ),
				'brands'    => LKPE_Reports::brand_comparison(),
				'gaps'      => LKPE_Reports::gaps( 10 ),
				'unchanged' => LKPE_Reports::unchanged( $range['from'], $range['to'], 20 ),
				'performance' => LKPE_Reports::sync_performance( 30 ),
			)
		);
	}

	/**
	 * Sources screen.
	 *
	 * @return void
	 */
	public static function screen_sources() {
		self::view(
			'sources',
			array(
				'sources' => LKPE_Source::all(),
				'vehicles' => LKPE_Vehicles::count(),
				'prices'   => LKPE_Prices::count(),
			)
		);
	}

	/**
	 * Sync screen.
	 *
	 * @return void
	 */
	public static function screen_sync() {
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		self::view(
			'sync',
			array(
				'logs'     => LKPE_Reports::logs( $paged, 20 ),
				'paged'    => $paged,
				'next_run' => LKPE_Cron::next_run(),
				'interval' => LKPE_Cron::interval(),
				'last'     => lk_get_last_sync(),
			)
		);
	}

	/**
	 * Settings screen.
	 *
	 * @return void
	 */
	public static function screen_settings() {
		self::view(
			'settings',
			array(
				'interval' => (int) get_option( 'lkpe_sync_interval', 60 ),
				'source'   => LKPE_Source::get( 'cafekhodro' ),
				'table_id' => (int) LKPE_Source::setting( 'cafekhodro', 'table_id', 1 ),
				'next_run' => LKPE_Cron::next_run(),
			)
		);
	}

	/**
	 * Save general settings.
	 *
	 * @return void
	 */
	public static function save_settings() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', 403 );
		}
		check_admin_referer( 'lkpe_save_settings' );

		$interval = isset( $_POST['interval'] ) ? absint( $_POST['interval'] ) : 60;
		if ( ! in_array( $interval, LKPE_Cron::allowed_intervals(), true ) ) {
			$interval = 60;
		}

		update_option( 'lkpe_sync_interval', $interval, false );
		LKPE_Cron::schedule( true );

		wp_safe_redirect( lkpe_admin_url( 'lkpe-settings', array( 'lkpe_notice' => 'settings_saved' ) ) );
		exit;
	}

	/**
	 * Save source configuration.
	 *
	 * @return void
	 */
	public static function save_source() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', 403 );
		}
		check_admin_referer( 'lkpe_save_source' );

		$api_url = isset( $_POST['api_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_url'] ) ) : '';
		$active  = ! empty( $_POST['is_active'] ) ? 1 : 0;
		$table   = isset( $_POST['table_id'] ) ? absint( $_POST['table_id'] ) : 1;

		LKPE_Source::update(
			'cafekhodro',
			array(
				'api_url'   => $api_url ? $api_url : 'https://cofekhodro.ir/wp-json/cafekhodro/v1',
				'is_active' => $active,
			),
			array( 'table_id' => max( 1, $table ) )
		);

		delete_transient( 'lkpe_tables_list' );

		wp_safe_redirect( lkpe_admin_url( 'lkpe-sources', array( 'lkpe_notice' => 'source_saved' ) ) );
		exit;
	}

	/**
	 * Run a manual sync from a form post.
	 *
	 * @return void
	 */
	public static function run_sync() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', 403 );
		}
		check_admin_referer( 'lkpe_run_sync' );

		$result = LKPE_Sync::run( 'manual' );
		set_transient( 'lkpe_sync_result_' . get_current_user_id(), $result, 2 * MINUTE_IN_SECONDS );

		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : lkpe_admin_url( 'lkpe-sync' );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Create a snapshot from a form post.
	 *
	 * @return void
	 */
	public static function snapshot() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', 403 );
		}
		check_admin_referer( 'lkpe_snapshot_now' );

		LKPE_Prices::create_snapshot();
		wp_safe_redirect( lkpe_admin_url( 'lkpe-sync', array( 'lkpe_notice' => 'snapshot' ) ) );
		exit;
	}
}
