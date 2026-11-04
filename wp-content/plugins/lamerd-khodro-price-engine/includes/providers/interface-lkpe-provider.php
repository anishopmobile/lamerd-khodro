<?php
/**
 * Price provider contract.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface LKPE_Provider
 */
interface LKPE_Provider {

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Human label.
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Available tables.
	 *
	 * @return array{name:string,url:string,attribution:string,tables:array}
	 */
	public function get_tables();

	/**
	 * Fetch one table.
	 *
	 * @param int|string $table_id Table ID.
	 * @return array{id:int,title:string,unit:string,updated_at:string,cars:array}
	 */
	public function fetch_table( $table_id );
}
