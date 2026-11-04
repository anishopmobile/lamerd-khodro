<?php
/**
 * Cafe Khodro public API provider.
 *
 * Docs: https://cofekhodro.ir/api/ — no API key, 120 requests/minute per IP,
 * attribution and a link to the source are required.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Provider_CafeKhodro
 */
class LKPE_Provider_CafeKhodro implements LKPE_Provider {

	/**
	 * Requests allowed per minute by the source.
	 */
	const RATE_LIMIT = 120;

	/**
	 * Safety margin kept below the hard limit.
	 */
	const RATE_SAFE = 110;

	/**
	 * Base API URL.
	 *
	 * @var string
	 */
	private $base;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$source     = LKPE_Source::get( 'cafekhodro' );
		$this->base = $source && ! empty( $source['api_url'] ) ? untrailingslashit( $source['api_url'] ) : 'https://cofekhodro.ir/wp-json/cafekhodro/v1';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_id() {
		return 'cafekhodro';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return 'کافه خودرو';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_tables() {
		$cached = get_transient( 'lkpe_tables_list' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$payload = $this->request( $this->base . '/tables' );

		if ( empty( $payload['tables'] ) || ! is_array( $payload['tables'] ) ) {
			throw new Exception( 'ساختار پاسخ فهرست جدول‌ها معتبر نیست.' );
		}

		$result = array(
			'name'        => isset( $payload['source']['name'] ) ? sanitize_text_field( $payload['source']['name'] ) : 'کافه خودرو',
			'url'         => isset( $payload['source']['url'] ) ? esc_url_raw( $payload['source']['url'] ) : 'https://cofekhodro.ir',
			'attribution' => isset( $payload['source']['attribution'] ) ? sanitize_text_field( $payload['source']['attribution'] ) : 'منبع: کافه خودرو — https://cofekhodro.ir',
			'tables'      => array(),
		);

		foreach ( $payload['tables'] as $table ) {
			$result['tables'][] = array(
				'id'         => isset( $table['id'] ) ? (int) $table['id'] : 0,
				'title'      => isset( $table['title'] ) ? sanitize_text_field( $table['title'] ) : '',
				'updated_at' => isset( $table['updated_at'] ) ? (string) $table['updated_at'] : '',
			);
		}

		set_transient( 'lkpe_tables_list', $result, 6 * HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function fetch_table( $table_id ) {
		$table_id = (int) $table_id;
		if ( $table_id <= 0 ) {
			throw new Exception( 'شناسه جدول نامعتبر است.' );
		}

		$payload = $this->request( $this->base . '/tables/' . $table_id );

		if ( ! isset( $payload['cars'] ) || ! is_array( $payload['cars'] ) ) {
			throw new Exception( 'پاسخ API فاقد فهرست خودروها است.' );
		}

		$updated_raw = isset( $payload['updated_at'] ) ? (string) $payload['updated_at'] : '';

		return array(
			'id'         => isset( $payload['id'] ) ? (int) $payload['id'] : $table_id,
			'title'      => isset( $payload['title'] ) ? sanitize_text_field( $payload['title'] ) : '',
			'unit'       => isset( $payload['unit'] ) ? sanitize_text_field( $payload['unit'] ) : 'toman',
			'updated_at' => $updated_raw,
			'declared_at' => $updated_raw ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $updated_raw ) ), 'Y-m-d H:i:s' ) : null,
			'count'      => isset( $payload['count'] ) ? (int) $payload['count'] : count( $payload['cars'] ),
			'cars'       => $payload['cars'],
		);
	}

	/**
	 * HTTP GET with rate limiting and bounded exponential retries.
	 *
	 * @param string $url URL.
	 * @return array Decoded JSON.
	 * @throws Exception On unrecoverable failure.
	 */
	private function request( $url ) {
		$delays   = array( 2, 5, 10 );
		$attempts = 0;
		$last     = '';

		while ( $attempts < 3 ) {
			++$attempts;
			$this->throttle();

			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 25,
					'redirection' => 3,
					'headers'     => array(
						'Accept'          => 'application/json',
						'User-Agent'      => 'LamerdKhodroPriceEngine/1.0 (+https://lamerdkhodro.ir)',
						'Accept-Language' => 'fa-IR,fa;q=0.9',
					),
				)
			);

			$this->count_request();

			if ( is_wp_error( $response ) ) {
				$last = $response->get_error_message();
			} else {
				$code = (int) wp_remote_retrieve_response_code( $response );
				$body = wp_remote_retrieve_body( $response );

				if ( 200 === $code ) {
					$data = json_decode( $body, true );
					if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
						throw new Exception( 'پاسخ API قابل خواندن نیست (JSON نامعتبر).' );
					}
					return $data;
				}

				$last = 'HTTP ' . $code;

				if ( 429 === $code ) {
					$retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );
					$wait        = $retry_after > 0 && $retry_after < 120 ? $retry_after : 10;
					if ( $attempts < 3 ) {
						sleep( $wait );
						continue;
					}
				} elseif ( $code >= 500 ) {
					// Retry below.
				} else {
					throw new Exception( 'پاسخ نامعتبر از منبع: ' . $last );
				}
			}

			if ( $attempts < 3 ) {
				$wait = $delays[ $attempts - 1 ] ?? 5;
				sleep( $wait );
			}
		}

		throw new Exception( 'دریافت اطلاعات از منبع ناموفق بود: ' . $last );
	}

	/**
	 * Wait if the rolling one-minute window is nearly exhausted.
	 *
	 * @return void
	 */
	private function throttle() {
		$window = get_transient( 'lkpe_rate_window' );
		if ( ! is_array( $window ) || ( time() - (int) $window['start'] ) >= 60 ) {
			return;
		}
		if ( (int) $window['count'] >= self::RATE_SAFE ) {
			sleep( 61 - ( time() - (int) $window['start'] ) );
		}
	}

	/**
	 * Track requests in the current minute.
	 *
	 * @return void
	 */
	private function count_request() {
		$window = get_transient( 'lkpe_rate_window' );
		if ( ! is_array( $window ) || ( time() - (int) $window['start'] ) >= 60 ) {
			$window = array(
				'start' => time(),
				'count' => 0,
			);
		}
		++$window['count'];
		set_transient( 'lkpe_rate_window', $window, 2 * MINUTE_IN_SECONDS );
	}
}
