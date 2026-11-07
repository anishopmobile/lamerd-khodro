<?php
/**
 * Front-end: plan cards above the sale listings and the AJAX request form.
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end integration.
 */
class LKP_Frontend {

	/**
	 * Whether the section has been printed (assets may only load once).
	 *
	 * @var bool
	 */
	private static $rendered = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'lk_listings_before', array( __CLASS__, 'render_section_contained' ) );
		add_action( 'lk_home_after_hero', array( __CLASS__, 'render_section' ) );
		add_shortcode( 'lk_plans', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Print the section above the listings archive.
	 *
	 * @return void
	 */
	public static function render_section() {
		self::render();
	}

	/**
	 * Same section where the theme already wraps content in .lk-container.
	 *
	 * @return void
	 */
	public static function render_section_contained() {
		self::render( true, false );
	}

	/**
	 * Shortcode wrapper.
	 *
	 * @return string
	 */
	public static function shortcode() {
		return self::render( false );
	}

	/**
	 * Render the plans section.
	 *
	 * @param bool $echo Print or return.
	 * @param bool $wrap Wrap the content in the theme's .lk-container.
	 * @return string
	 */
	public static function render( $echo = true, $wrap = true ) {
		if ( self::$rendered ) {
			return '';
		}

		$plans = LKP_Plans::public_plans( 12 );
		$cards = array();

		foreach ( $plans as $plan ) {
			$state = LKP_Plans::state( $plan->ID );
			$dates = array(
				'active' => LKP_Plans::timestamp( $plan->ID, 'active_ts' ),
				'start'  => LKP_Plans::timestamp( $plan->ID, 'start_ts' ),
				'end'    => LKP_Plans::timestamp( $plan->ID, 'end_ts' ),
			);

			$deadline = 0;
			if ( 'live' === $state ) {
				$deadline = $dates['end'];
			} elseif ( 'future' === $state ) {
				$deadline = $dates['start'];
			}

			$cards[] = array(
				'id'          => $plan->ID,
				'title'       => get_the_title( $plan ),
				'company'     => LKP_Plans::company( $plan->ID ),
				'description' => wp_strip_all_tags( strip_shortcodes( (string) $plan->post_content ) ),
				'state'       => $state,
				'label'       => LKP_Plans::state_label( $state ),
				'images'      => LKP_Plans::images( $plan->ID ),
				'dates'       => $dates,
				'deadline'    => $deadline,
				'can_request' => 'live' === $state,
			);
		}

		self::$rendered = true;
		self::enqueue_assets();

		return lkp_template(
			'plans-section',
			array(
				'cards' => $cards,
				'wrap'  => (bool) $wrap,
			),
			$echo
		);
	}

	/**
	 * Assets for the section.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( self::$rendered ) {
			self::enqueue_assets();
		}
	}

	/**
	 * Register and localise assets.
	 *
	 * @return void
	 */
	private static function enqueue_assets() {
		if ( wp_script_is( 'lkp-front', 'enqueued' ) ) {
			return;
		}

		wp_enqueue_style( 'lkp-front', LKP_URL . 'assets/css/front.css', array(), LKP_VERSION );
		wp_enqueue_script( 'lkp-front', LKP_URL . 'assets/js/front.js', array(), LKP_VERSION, true );

		wp_localize_script(
			'lkp-front',
			'LKP',
			array(
				'restUrl'  => esc_url_raw( rest_url( 'lamerd-plans/v1/requests' ) ),
				'nonce'    => wp_create_nonce( 'lkp_submit_request' ),
				'maxFiles' => LKP_Requests::MAX_FILES,
				'maxSize'  => LKP_Files::DOCUMENT_MAX,
				'strings'  => array(
					'sending'      => 'در حال ارسال…',
					'submit'       => 'تأیید و ارسال اطلاعات',
					'genericError' => 'ارسال درخواست انجام نشد. دوباره تلاش کنید.',
					'networkError' => 'ارتباط با سامانه برقرار نشد. اتصال اینترنت را بررسی کنید.',
					'fileType'     => 'فقط تصاویر JPG و PNG مجاز است.',
					'fileSize'     => 'حجم هر تصویر باید کمتر از ۵ مگابایت باشد.',
					'fileCount'    => 'حداکثر ۸ تصویر می‌توانید انتخاب کنید.',
					'needDocs'     => 'بارگذاری تصاویر مدارک الزامی است.',
					'expired'      => 'مهلت ثبت‌نام این طرح به پایان رسید.',
					'close'        => 'بستن',
				),
			)
		);
	}
}
