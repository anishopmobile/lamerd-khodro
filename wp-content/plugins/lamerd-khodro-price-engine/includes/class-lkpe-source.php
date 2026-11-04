<?php
/**
 * Price source registry.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LKPE_Source
 */
class LKPE_Source {

	/**
	 * Request cache.
	 *
	 * @var array<string,array|null>
	 */
	private static $cache = array();

	/**
	 * Get one source with decoded settings.
	 *
	 * @param string $slug Source slug.
	 * @return array|null
	 */
	public static function get( $slug ) {
		if ( array_key_exists( $slug, self::$cache ) ) {
			return self::$cache[ $slug ];
		}

		global $wpdb;
		$table = LKPE_Installer::table( 'price_sources' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE slug = %s", $slug ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( $row ) {
			$row['settings'] = $row['settings'] ? json_decode( $row['settings'], true ) : array();
			if ( ! is_array( $row['settings'] ) ) {
				$row['settings'] = array();
			}
		}

		self::$cache[ $slug ] = $row ? $row : null;
		return self::$cache[ $slug ];
	}

	/**
	 * All sources.
	 *
	 * @return array[]
	 */
	public static function all() {
		global $wpdb;
		$table = LKPE_Installer::table( 'price_sources' );
		$rows  = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return $rows ? $rows : array();
	}

	/**
	 * Read a setting.
	 *
	 * @param string $slug    Source slug.
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function setting( $slug, $key, $default = null ) {
		$source = self::get( $slug );
		if ( ! $source || ! isset( $source['settings'][ $key ] ) ) {
			return $default;
		}
		return $source['settings'][ $key ];
	}

	/**
	 * Update source columns and settings.
	 *
	 * @param string $slug   Source slug.
	 * @param array  $fields Column values.
	 * @param array  $settings Settings to merge.
	 * @return bool
	 */
	public static function update( $slug, $fields, $settings = null ) {
		global $wpdb;
		$table  = LKPE_Installer::table( 'price_sources' );
		$source = self::get( $slug );
		if ( ! $source ) {
			return false;
		}

		$fields['updated_at'] = current_time( 'mysql' );

		if ( null !== $settings ) {
			$merged                  = array_merge( (array) $source['settings'], (array) $settings );
			$fields['settings']      = wp_json_encode( $merged );
		}

		$wpdb->update( $table, $fields, array( 'slug' => $slug ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		unset( self::$cache[ $slug ] );

		return true;
	}

	/**
	 * Record a successful fetch.
	 *
	 * @param string $slug Source slug.
	 * @return void
	 */
	public static function record_success( $slug ) {
		self::update(
			$slug,
			array(
				'last_success_at' => current_time( 'mysql' ),
				'last_error'      => null,
			)
		);
	}

	/**
	 * Record a failed fetch.
	 *
	 * @param string $slug    Source slug.
	 * @param string $message Error message.
	 * @return void
	 */
	public static function record_error( $slug, $message ) {
		self::update(
			$slug,
			array(
				'last_error_at' => current_time( 'mysql' ),
				'last_error'    => mb_substr( wp_strip_all_tags( (string) $message ), 0, 1000 ),
			)
		);
	}
}
