<?php
/**
 * Conditional asset loading (no CDN, no jQuery on the front end).
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asset version based on file modification time.
 *
 * @param string $relative Relative path inside the theme.
 * @return string
 */
function lk_asset_version( $relative ) {
	$file = LK_THEME_PATH . $relative;
	return file_exists( $file ) ? (string) filemtime( $file ) : LK_THEME_VERSION;
}

/**
 * Front-end assets.
 *
 * @return void
 */
function lk_enqueue_assets() {
	$styles = array( 'tokens', 'base', 'layout', 'components', 'pages', 'responsive' );

	foreach ( $styles as $index => $handle ) {
		$deps = array();
		if ( $index > 0 ) {
			$deps[] = 'lk-' . $styles[ $index - 1 ];
		}
		wp_enqueue_style(
			'lk-' . $handle,
			LK_THEME_URI . '/assets/css/' . $handle . '.css',
			$deps,
			lk_asset_version( '/assets/css/' . $handle . '.css' )
		);
	}

	wp_enqueue_script(
		'lk-app',
		LK_THEME_URI . '/assets/js/app.js',
		array(),
		lk_asset_version( '/assets/js/app.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_enqueue_script(
		'lk-search',
		LK_THEME_URI . '/assets/js/search.js',
		array( 'lk-app' ),
		lk_asset_version( '/assets/js/search.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	$needs_price_table = is_page_template( 'page-templates/page-prices.php' )
		|| is_post_type_archive( 'lk_vehicle' )
		|| is_page_template( 'page-templates/page-vehicles.php' )
		|| is_tax( 'lk_brand' );

	if ( $needs_price_table ) {
		wp_enqueue_script(
			'lk-price-table',
			LK_THEME_URI . '/assets/js/price-table.js',
			array( 'lk-app' ),
			lk_asset_version( '/assets/js/price-table.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_post_type_archive( 'lk_listing' ) || is_singular( 'lk_listing' ) || is_front_page() ) {
		wp_enqueue_script(
			'lk-listing',
			LK_THEME_URI . '/assets/js/listing.js',
			array( 'lk-app' ),
			lk_asset_version( '/assets/js/listing.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_page_template( 'page-templates/page-compare.php' ) ) {
		wp_enqueue_script(
			'lk-compare',
			LK_THEME_URI . '/assets/js/compare.js',
			array( 'lk-app' ),
			lk_asset_version( '/assets/js/compare.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_singular( 'lk_vehicle' ) || is_page_template( 'page-templates/page-compare.php' ) ) {
		wp_enqueue_script(
			'lk-chartjs',
			LK_THEME_URI . '/assets/js/chart.umd.min.js',
			array(),
			'4.4.4',
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_enqueue_script(
			'lk-vehicle-chart',
			LK_THEME_URI . '/assets/js/vehicle-chart.js',
			array( 'lk-app', 'lk-chartjs' ),
			lk_asset_version( '/assets/js/vehicle-chart.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	wp_localize_script(
		'lk-app',
		'LK',
		array(
			'restUrl'   => esc_url_raw( rest_url( 'lamerd/v1/' ) ),
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => function_exists( 'lk_public_nonce' ) ? lk_public_nonce() : '',
			'homeUrl'   => home_url( '/' ),
			'pricesUrl' => lk_page_url( 'prices' ),
			'compareUrl' => lk_page_url( 'compare' ),
			'isRtl'     => true,
			'strings'   => array(
				'searching'    => 'در حال جستجو…',
				'noResults'    => 'نتیجه‌ای یافت نشد.',
				'error'        => 'خطا در دریافت اطلاعات. دوباره تلاش کنید.',
				'loading'      => 'در حال بارگذاری…',
				'compareEmpty' => 'دو تا چهار خودرو برای مقایسه انتخاب کنید.',
				'compareLimit' => 'حداکثر چهار خودرو قابل مقایسه است.',
				'compareFull'  => 'این خودرو به فهرست مقایسه اضافه شد.',
				'compareRemove' => 'از فهرست مقایسه حذف شد.',
				'chartEmpty'   => 'داده کافی برای رسم نمودار ثبت نشده است.',
				'copyLink'     => 'پیوند مقایسه کپی شد.',
				'noChartData'  => 'برای این بازه داده‌ای ثبت نشده است.',
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lk_enqueue_assets' );

/**
 * Preload the primary font and set a theme colour.
 *
 * @return void
 */
function lk_head_assets() {
	$font = LK_THEME_URI . '/assets/fonts/Vazirmatn-Regular.woff2';
	?>
	<link rel="preload" href="<?php echo esc_url( $font ); ?>" as="font" type="font/woff2" crossorigin />
	<meta name="theme-color" content="#0A1F33" />
	<?php
}
add_action( 'wp_head', 'lk_head_assets', 1 );
