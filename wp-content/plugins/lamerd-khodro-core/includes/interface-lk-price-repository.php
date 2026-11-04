<?php
/**
 * Contract for price data providers.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Interface LK_Price_Repository_Interface
 *
 * Implemented by the Price Engine plugin and registered in LK_Registry.
 */
interface LK_Price_Repository_Interface {

	/**
	 * Price summary for one vehicle.
	 *
	 * @param int $post_id Vehicle post ID.
	 * @return array|null
	 */
	public function get_vehicle_price( $post_id );

	/**
	 * Query price table.
	 *
	 * Supported args: page, per_page, search, brand (array of slugs), body_type,
	 * class, year_from, year_to, price_min, price_max, availability,
	 * sort (price_asc|price_desc|change_desc|change_asc|name|updated|newest),
	 * only_with_price (bool), post_ids (array).
	 *
	 * @param array $args Query args.
	 * @return array{items:array,total:int,pages:int,page:int}
	 */
	public function get_prices( $args = array() );

	/**
	 * History points for a vehicle.
	 *
	 * @param int    $post_id Vehicle post ID.
	 * @param string $from    Y-m-d.
	 * @param string $to      Y-m-d.
	 * @return array<int,array>
	 */
	public function get_history( $post_id, $from, $to );

	/**
	 * Aggregate change stats.
	 *
	 * @param array $args Optional filters.
	 * @return array
	 */
	public function get_change_stats( $args = array() );

	/**
	 * Last successful sync log row.
	 *
	 * @return array|null
	 */
	public function get_last_sync();

	/**
	 * Biggest movers.
	 *
	 * @param string $direction 'up' or 'down'.
	 * @param int    $limit     Count.
	 * @return array<int,array>
	 */
	public function get_top_movers( $direction = 'up', $limit = 5 );
}
