<?php
/**
 * Registration requests: storage, validation and the public REST endpoint.
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

/**
 * Requests (درخواست‌های ثبت‌نام).
 */
class LKP_Requests {

	const POST_TYPE = 'lk_plan_request';

	/**
	 * Maximum number of documents per request.
	 */
	const MAX_FILES = 8;

	/**
	 * Maximum requests per IP per hour.
	 */
	const RATE_LIMIT = 8;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register the request post type.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => 'درخواست‌ها',
			'singular_name'      => 'درخواست',
			'menu_name'          => 'درخواست‌های ثبت‌نام',
			'all_items'          => 'درخواست‌ها',
			'edit_item'          => 'جزئیات درخواست',
			'search_items'       => 'جستجوی درخواست',
			'not_found'          => 'درخواستی ثبت نشده است.',
			'not_found_in_trash' => 'درخواستی در زباله‌دان نیست.',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=' . LKP_Plans::POST_TYPE,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-clipboard',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'rewrite'             => false,
				'query_var'           => false,
				'delete_with_user'    => false,
			)
		);
	}

	/**
	 * Request meta keys.
	 *
	 * @return array<string,string>
	 */
	public static function meta_keys() {
		return array(
			'plan_id'      => '_lkp_plan_id',
			'name'         => '_lkp_name',
			'lastname'     => '_lkp_lastname',
			'phone'        => '_lkp_phone',
			'mobile_owner' => '_lkp_mobile_owner',
			'address'      => '_lkp_address',
			'postal'       => '_lkp_postal',
			'shaba'        => '_lkp_shaba',
			'status'       => '_lkp_status',
			'ip_hash'      => '_lkp_ip_hash',
			'files'        => '_lkp_files',
			'tracking'     => '_lkp_tracking',
		);
	}

	/**
	 * Register request meta.
	 *
	 * @return void
	 */
	public static function register_meta() {
		foreach ( self::meta_keys() as $key => $meta_key ) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'type'          => in_array( $key, array( 'plan_id', 'files' ), true ) ? ( 'files' === $key ? 'array' : 'integer' ) : 'string',
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => array( 'LKP_Plans', 'can_manage' ),
				)
			);
		}
	}

	/**
	 * Request statuses.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'new'      => 'جدید',
			'reviewed' => 'بررسی‌شده',
			'rejected' => 'رد‌شده',
		);
	}

	/**
	 * Register the public REST endpoint.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			'lamerd-plans/v1',
			'/requests',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_submit' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle a request submission (AJAX, no page reload).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_submit( WP_REST_Request $request ) {
		$nonce = (string) $request->get_header( 'X-LKP-Nonce' );
		if ( ! $nonce ) {
			$nonce = (string) $request->get_param( '_lkp_nonce' );
		}

		if ( ! wp_verify_nonce( $nonce, 'lkp_submit_request' ) ) {
			return new WP_REST_Response(
				array(
					'ok'    => false,
					'error' => 'invalid_nonce',
					'text'  => 'نشست شما منقضی شده است. صفحه را تازه کنید و دوباره تلاش کنید.',
				),
				403
			);
		}

		// Simple spam guards: honeypot and a minimum time spent on the form.
		$honeypot = (string) $request->get_param( 'website' );
		if ( '' !== trim( $honeypot ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'spam', 'text' => 'ارسال درخواست انجام نشد.' ), 422 );
		}

		$opened = (int) $request->get_param( 'opened_at' );
		if ( $opened > 0 && ( time() - $opened ) < 2 ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'spam', 'text' => 'ارسال درخواست انجام نشد.' ), 422 );
		}

		$plan_id = absint( $request->get_param( 'plan_id' ) );
		$fields  = array(
			'name'         => $request->get_param( 'name' ),
			'lastname'     => $request->get_param( 'lastname' ),
			'phone'        => $request->get_param( 'phone' ),
			'mobile_owner' => $request->get_param( 'mobile_owner' ),
			'address'      => $request->get_param( 'address' ),
			'postal'       => $request->get_param( 'postal' ),
			'shaba'        => $request->get_param( 'shaba' ),
		);

		$files = $request->get_file_params();
		$docs  = self::normalise_files( $files['documents'] ?? array() );

		$result = self::create( $plan_id, $fields, $docs );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'ok'    => false,
					'error' => $result->get_error_code(),
					'text'  => $result->get_error_message(),
				),
				422
			);
		}

		return new WP_REST_Response(
			array(
				'ok'       => true,
				'code'     => $result['tracking'],
				'message'  => 'درخواست شما با موفقیت ثبت شد.',
			),
			201
		);
	}

	/**
	 * Turn a multi-file $_FILES entry into a list of single-file arrays.
	 *
	 * @param array $entry $_FILES entry.
	 * @return array<int,array<string,mixed>>
	 */
	public static function normalise_files( $entry ) {
		if ( ! is_array( $entry ) || ! isset( $entry['name'] ) ) {
			return array();
		}

		if ( ! is_array( $entry['name'] ) ) {
			return array( $entry );
		}

		$out = array();
		foreach ( array_keys( $entry['name'] ) as $index ) {
			$out[] = array(
				'name'     => $entry['name'][ $index ] ?? '',
				'type'     => $entry['type'][ $index ] ?? '',
				'tmp_name' => $entry['tmp_name'][ $index ] ?? '',
				'error'    => $entry['error'][ $index ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $entry['size'][ $index ] ?? 0,
			);
		}

		return $out;
	}

	/**
	 * Validate and store a request.
	 *
	 * @param int   $plan_id Plan post ID.
	 * @param array $fields Submitted fields.
	 * @param array $docs   Uploaded documents.
	 * @return array{id:int,tracking:string}|WP_Error
	 */
	public static function create( $plan_id, $fields, $docs ) {
		$plan = get_post( (int) $plan_id );
		if ( ! $plan || LKP_Plans::POST_TYPE !== $plan->post_type || 'publish' !== $plan->post_status ) {
			return new WP_Error( 'plan_not_found', 'طرح انتخاب‌شده یافت نشد.' );
		}

		$state = LKP_Plans::state( $plan->ID );
		if ( 'disabled' === $state ) {
			return new WP_Error( 'plan_disabled', 'این طرح در حال حاضر غیرفعال است.' );
		}
		if ( 'future' === $state ) {
			return new WP_Error( 'not_started', 'زمان ثبت‌نام این طرح هنوز شروع نشده است.' );
		}
		if ( 'expired' === $state ) {
			return new WP_Error( 'expired', 'مهلت ثبت‌نام این طرح به پایان رسیده است.' );
		}

		$name     = self::clean( $fields['name'] ?? '', 120 );
		$lastname = self::clean( $fields['lastname'] ?? '', 120 );
		$address  = self::clean( $fields['address'] ?? '', 2000 );
		$phone    = self::normalise_phone( $fields['phone'] ?? '' );
		$mobile   = self::normalise_phone( $fields['mobile_owner'] ?? '' );
		$postal   = preg_replace( '/\D+/', '', LKP_Jalali::latin_digits( (string) ( $fields['postal'] ?? '' ) ) );
		$shaba    = strtoupper( str_replace( array( ' ', '-' ), '', LKP_Jalali::latin_digits( (string) ( $fields['shaba'] ?? '' ) ) ) );

		if ( '' === $name || '' === $lastname || '' === $address ) {
			return new WP_Error( 'invalid_input', 'نام، نام خانوادگی و آدرس را کامل وارد کنید.' );
		}

		if ( ! $phone || ! $mobile ) {
			return new WP_Error( 'invalid_phone', 'شمارهٔ تماس و موبایل باید با ۰۹ شروع شوند و ۱۱ رقم باشند.' );
		}

		if ( ! preg_match( '/^\d{10}$/', (string) $postal ) ) {
			return new WP_Error( 'invalid_postal', 'کد پستی باید ۱۰ رقم باشد.' );
		}

		if ( ! preg_match( '/^IR\d{24}$/', $shaba ) ) {
			return new WP_Error( 'invalid_shaba', 'شمارهٔ شبا باید با IR شروع شود و ۲۴ رقم داشته باشد.' );
		}

		if ( ! $docs ) {
			return new WP_Error( 'documents_required', 'بارگذاری تصاویر مدارک الزامی است.' );
		}

		if ( count( $docs ) > self::MAX_FILES ) {
			return new WP_Error( 'too_many_files', 'حداکثر ' . LKP_Jalali::persian_digits( self::MAX_FILES ) . ' تصویر مدارک مجاز است.' );
		}

		$ip      = self::client_ip();
		$ip_hash = $ip ? wp_hash( $ip ) : '';

		if ( $ip_hash && self::rate_limited( $ip_hash ) ) {
			return new WP_Error( 'rate_limited', 'تعداد درخواست‌های شما در مدت کوتاه بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $name . ' ' . $lastname,
				'post_author' => 0,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'server_error', 'ثبت درخواست انجام نشد. دوباره تلاش کنید.' );
		}

		$stored = array();
		foreach ( $docs as $doc ) {
			$file = LKP_Files::store( $doc, 'document' );
			if ( is_wp_error( $file ) ) {
				foreach ( $stored as $saved ) {
					LKP_Files::delete( $saved['stored'] );
				}
				wp_delete_post( $post_id, true );
				return $file;
			}
			$stored[] = $file;
		}

		$keys     = self::meta_keys();
		$tracking = strtoupper( substr( bin2hex( random_bytes( 5 ) ), 0, 8 ) );

		$meta = array(
			'plan_id'      => $plan->ID,
			'name'         => $name,
			'lastname'     => $lastname,
			'phone'        => $phone,
			'mobile_owner' => $mobile,
			'address'      => $address,
			'postal'       => $postal,
			'shaba'        => $shaba,
			'status'       => 'new',
			'ip_hash'      => $ip_hash,
			'files'        => $stored,
			'tracking'     => $tracking,
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $keys[ $key ], $value );
		}

		self::bump_rate_limit( $ip_hash );

		/**
		 * Fires after a registration request is stored.
		 *
		 * @param int   $post_id Request post ID.
		 * @param int   $plan_id Plan post ID.
		 * @param array $meta    Stored fields.
		 */
		do_action( 'lkp_request_created', $post_id, $plan->ID, $meta );

		return array(
			'id'       => (int) $post_id,
			'tracking' => $tracking,
		);
	}

	/**
	 * Trim and normalise a text field.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max   Maximum length.
	 * @return string
	 */
	private static function clean( $value, $max = 500 ) {
		$value = sanitize_textarea_field( wp_unslash( (string) $value ) );
		$value = trim( preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value ) );

		return mb_substr( (string) $value, 0, $max );
	}

	/**
	 * Normalise an Iranian mobile number to 09xxxxxxxxx.
	 *
	 * @param mixed $value Raw value.
	 * @return string Empty string when invalid.
	 */
	public static function normalise_phone( $value ) {
		$digits = preg_replace( '/\D+/', '', LKP_Jalali::latin_digits( (string) $value ) );

		if ( 0 === strpos( (string) $digits, '0098' ) ) {
			$digits = '0' . substr( (string) $digits, 4 );
		} elseif ( 0 === strpos( (string) $digits, '98' ) && strlen( (string) $digits ) > 10 ) {
			$digits = '0' . substr( (string) $digits, 2 );
		} elseif ( 0 === strpos( (string) $digits, '9' ) && 10 === strlen( (string) $digits ) ) {
			$digits = '0' . $digits;
		}

		return preg_match( '/^09\d{9}$/', (string) $digits ) ? (string) $digits : '';
	}

	/**
	 * Visitor IP address.
	 *
	 * @return string
	 */
	public static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Whether this IP already hit the hourly limit.
	 *
	 * @param string $ip_hash Hashed IP.
	 * @return bool
	 */
	private static function rate_limited( $ip_hash ) {
		$key   = 'lkp_rl_' . md5( $ip_hash );
		$count = (int) get_transient( $key );

		return $count >= self::RATE_LIMIT;
	}

	/**
	 * Increase the hourly counter for an IP.
	 *
	 * @param string $ip_hash Hashed IP.
	 * @return void
	 */
	private static function bump_rate_limit( $ip_hash ) {
		if ( ! $ip_hash ) {
			return;
		}

		$key   = 'lkp_rl_' . md5( $ip_hash );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Update a request status.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $status     new|reviewed|rejected.
	 * @return bool
	 */
	public static function set_status( $request_id, $status ) {
		if ( ! array_key_exists( $status, self::statuses() ) ) {
			return false;
		}

		update_post_meta( (int) $request_id, self::meta_keys()['status'], $status );
		return true;
	}

	/**
	 * Read a request field.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $field      Meta key alias.
	 * @return mixed
	 */
	public static function get( $request_id, $field ) {
		$keys = self::meta_keys();
		if ( ! isset( $keys[ $field ] ) ) {
			return '';
		}

		return get_post_meta( (int) $request_id, $keys[ $field ], true );
	}

	/**
	 * Status of a request.
	 *
	 * @param int $request_id Request ID.
	 * @return string
	 */
	public static function status( $request_id ) {
		$status = (string) self::get( $request_id, 'status' );
		return array_key_exists( $status, self::statuses() ) ? $status : 'new';
	}

	/**
	 * Stored documents of a request.
	 *
	 * @param int $request_id Request ID.
	 * @return array<int,array<string,mixed>>
	 */
	public static function documents( $request_id ) {
		$files = self::get( $request_id, 'files' );
		return is_array( $files ) ? $files : array();
	}

	/**
	 * Unique, non-guessable URL for a private document.
	 *
	 * @param int $request_id         Request ID.
	 * @param int $document_index     Document index.
	 * @return string
	 */
	public static function document_url( $request_id, $document_index ) {
		$request_id     = (int) $request_id;
		$document_index = (int) $document_index;

		return add_query_arg(
			array(
				'action'   => 'lkp_document',
				'request'  => $request_id,
				'index'    => $document_index,
				'_wpnonce' => wp_create_nonce( 'lkp_document_' . $request_id . '_' . $document_index ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Unique URL for a plan circular.
	 *
	 * @param int $plan_id Plan ID.
	 * @return string
	 */
	public static function circular_url( $plan_id ) {
		$plan_id = (int) $plan_id;

		return add_query_arg(
			array(
				'action'   => 'lkp_circular',
				'plan'     => $plan_id,
				'_wpnonce' => wp_create_nonce( 'lkp_circular_' . $plan_id ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Delete a request and its documents.
	 *
	 * @param int $request_id Request ID.
	 * @return void
	 */
	public static function delete( $request_id ) {
		foreach ( self::documents( $request_id ) as $file ) {
			LKP_Files::delete( (string) ( $file['stored'] ?? '' ) );
		}

		wp_delete_post( (int) $request_id, true );
	}
}
