<?php
/**
 * Safe no-op price repository used when the Price Engine is inactive.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Null_Price_Repository
 */
class LK_Null_Price_Repository implements LK_Price_Repository_Interface {

	/**
	 * {@inheritDoc}
	 */
	public function get_vehicle_price( $post_id ) {
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_prices( $args = array() ) {
		return array(
			'items' => array(),
			'total' => 0,
			'pages' => 0,
			'page'  => 1,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_history( $post_id, $from, $to ) {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_change_stats( $args = array() ) {
		return array(
			'total'    => 0,
			'up'       => 0,
			'down'     => 0,
			'flat'     => 0,
			'no_price' => 0,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_last_sync() {
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_top_movers( $direction = 'up', $limit = 5 ) {
		return array();
	}
}
