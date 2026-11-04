<?php
/**
 * Small service registry used to decouple the core from optional modules.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Registry
 */
class LK_Registry {

	/**
	 * Registered services.
	 *
	 * @var array<string,mixed>
	 */
	private static $services = array();

	/**
	 * Register a service.
	 *
	 * @param string $key   Service key.
	 * @param mixed  $value Service instance.
	 * @return void
	 */
	public static function set( $key, $value ) {
		self::$services[ $key ] = $value;
	}

	/**
	 * Fetch a service.
	 *
	 * @param string $key     Service key.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		return array_key_exists( $key, self::$services ) ? self::$services[ $key ] : $default;
	}

	/**
	 * Whether a service exists.
	 *
	 * @param string $key Service key.
	 * @return bool
	 */
	public static function has( $key ) {
		return isset( self::$services[ $key ] );
	}

	/**
	 * Current price repository (never null).
	 *
	 * @return LK_Price_Repository_Interface
	 */
	public static function prices() {
		$repo = self::get( 'price_repository' );
		if ( ! $repo instanceof LK_Price_Repository_Interface ) {
			$repo = new LK_Null_Price_Repository();
			self::set( 'price_repository', $repo );
		}
		return $repo;
	}
}
