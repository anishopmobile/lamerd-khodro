<?php
/**
 * Vehicle query builder and archive filters.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Query
 */
class LK_Query {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'archive_filters' ) );
	}

	/**
	 * Normalise request args into a WP_Query argument set.
	 *
	 * @param array $args Input.
	 * @return array
	 */
	public static function build( $args = array() ) {
		$defaults = array(
			'posts_per_page' => 12,
			'paged'          => 1,
			'sort'           => 'newest',
			'brand'          => array(),
			'body_type'      => array(),
			'class'          => array(),
			'year_from'      => 0,
			'year_to'        => 0,
			'price_min'      => 0,
			'price_max'      => 0,
			'availability'   => '',
			'search'         => '',
			'ids'            => array(),
			'exclude'        => array(),
		);
		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'           => 'lk_vehicle',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 50, (int) $args['posts_per_page'] ) ),
			'paged'               => max( 1, (int) $args['paged'] ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);

		$tax_query = array();

		foreach ( array(
			'lk_brand'     => 'brand',
			'lk_body_type' => 'body_type',
			'lk_class'     => 'class',
		) as $taxonomy => $arg_key ) {
			$values = $args[ $arg_key ];
			if ( ! is_array( $values ) ) {
				$values = array_filter( explode( ',', (string) $values ) );
			}
			$values = array_filter( array_map( 'sanitize_title', $values ) );
			if ( $values ) {
				$tax_query[] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $values,
					'include_children' => true,
				);
			}
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( $tax_query ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$meta_query = array();

		$year_from = (int) $args['year_from'];
		$year_to   = (int) $args['year_to'];
		if ( $year_from && $year_to && $year_from > $year_to ) {
			list( $year_from, $year_to ) = array( $year_to, $year_from );
		}
		if ( $year_from ) {
			$meta_query[] = array(
				'key'     => '_lk_year',
				'value'   => $year_from,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $year_to ) {
			$meta_query[] = array(
				'key'     => '_lk_year',
				'value'   => $year_to,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		$price_min = (int) $args['price_min'];
		$price_max = (int) $args['price_max'];
		if ( $price_min || $price_max ) {
			$meta_query[] = array(
				'key'     => '_lk_market_price',
				'value'   => array( $price_min, $price_max ? $price_max : PHP_INT_MAX ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}

		if ( 'stopped' === $args['availability'] ) {
			$meta_query[] = array(
				'key'   => '_lk_production_status',
				'value' => 'stopped',
			);
		} elseif ( 'available' === $args['availability'] ) {
			$meta_query[] = array(
				'key'     => '_lk_production_status',
				'value'   => 'stopped',
				'compare' => '!=',
			);
		}

		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
		}
		if ( $meta_query ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		if ( $args['search'] ) {
			$ids = self::find_vehicle_ids( $args['search'], 200 );
			$query_args['post__in'] = $ids ? $ids : array( 0 );
		}

		if ( $args['ids'] ) {
			$query_args['post__in'] = array_map( 'absint', (array) $args['ids'] );
			$query_args['orderby']  = 'post__in';
		}

		if ( $args['exclude'] ) {
			$query_args['post__not_in'] = array_map( 'absint', (array) $args['exclude'] );
		}

		switch ( $args['sort'] ) {
			case 'price_asc':
				$query_args['meta_key'] = '_lk_market_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'ASC';
				break;
			case 'price_desc':
				$query_args['meta_key'] = '_lk_market_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				break;
			case 'change_desc':
				$query_args['meta_key'] = '_lk_change_percent'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				break;
			case 'change_asc':
				$query_args['meta_key'] = '_lk_change_percent'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'ASC';
				break;
			case 'name':
				$query_args['orderby'] = 'title';
				$query_args['order']   = 'ASC';
				break;
			case 'popular':
				$query_args['meta_key'] = '_lk_views'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				break;
			case 'newest':
			default:
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
		}

		/**
		 * Filter the final vehicle WP_Query args.
		 *
		 * @param array $query_args Args.
		 * @param array $args       Original args.
		 */
		return apply_filters( 'lk_vehicle_query_args', $query_args, $args );
	}

	/**
	 * Run a vehicle query.
	 *
	 * @param array $args Args.
	 * @return WP_Query
	 */
	public static function vehicles( $args = array() ) {
		return new WP_Query( self::build( $args ) );
	}

	/**
	 * Normalised LIKE search returning vehicle IDs.
	 *
	 * Handles Persian/Arabic variants and ZWNJ by generating a few patterns.
	 *
	 * @param string $search Search term.
	 * @param int    $limit  Max IDs.
	 * @return int[]
	 */
	public static function find_vehicle_ids( $search, $limit = 100 ) {
		global $wpdb;

		$normalized = LK_Format::normalize_text( $search );
		if ( '' === $normalized ) {
			return array();
		}

		$base    = str_replace( ' ', '%', $normalized );
		$variants = array_unique(
			array(
				$base,
				str_replace( 'ی', 'ي', $base ),
				str_replace( 'ک', 'ك', $base ),
				str_replace( array( 'ی', 'ک' ), array( 'ي', 'ك' ), $base ),
			)
		);

		$like = array();
		$params = array();
		foreach ( $variants as $variant ) {
			$like[]   = 'post_title LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $variant ) . '%';
		}

		// A single prepare() call: literal % wildcards are only restored once per
		// query, so splitting the statement across multiple prepare() calls would
		// leave placeholder hashes in the SQL.
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'lk_vehicle' AND post_status = 'publish' AND (" . implode( ' OR ', $like ) . ')';
		$sql .= " OR ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_lk_name_en' AND meta_value LIKE %s)";
		$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		$sql     .= ' LIMIT %d';
		$params[] = max( 1, (int) $limit );

		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery

		return array_map( 'absint', $ids );
	}

	/**
	 * Related vehicles (same brand first, then same body type).
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit   Count.
	 * @return WP_Query
	 */
	public static function related( $post_id, $limit = 4 ) {
		$brands = wp_get_object_terms( $post_id, 'lk_brand', array( 'fields' => 'slugs' ) );
		$args   = array(
			'posts_per_page' => $limit,
			'exclude'        => array( $post_id ),
			'sort'           => 'price_asc',
		);
		if ( ! is_wp_error( $brands ) && $brands ) {
			$args['brand'] = $brands;
		}
		$query = self::vehicles( $args );

		if ( ! $query->have_posts() ) {
			$query = self::vehicles(
				array(
					'posts_per_page' => $limit,
					'exclude'        => array( $post_id ),
				)
			);
		}

		return $query;
	}

	/**
	 * Popular vehicles by stored view counter.
	 *
	 * @param int $limit Count.
	 * @return WP_Query
	 */
	public static function popular( $limit = 4 ) {
		return self::vehicles(
			array(
				'posts_per_page' => $limit,
				'sort'           => 'popular',
			)
		);
	}

	/**
	 * Latest vehicles.
	 *
	 * @param int $limit Count.
	 * @return WP_Query
	 */
	public static function latest( $limit = 8 ) {
		return self::vehicles(
			array(
				'posts_per_page' => $limit,
				'sort'           => 'newest',
			)
		);
	}

	/**
	 * Apply public archive filters to the main query.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public static function archive_filters( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( ! $query->is_post_type_archive( 'lk_vehicle' ) && ! $query->is_tax( array( 'lk_brand', 'lk_body_type', 'lk_class' ) ) ) {
			return;
		}

		$query->set( 'posts_per_page', 24 );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$year_from = isset( $_GET['year_from'] ) ? absint( $_GET['year_from'] ) : 0;
		$year_to   = isset( $_GET['year_to'] ) ? absint( $_GET['year_to'] ) : 0;
		$price_min = isset( $_GET['price_min'] ) ? absint( LK_Format::latin_digits( wp_unslash( $_GET['price_min'] ) ) ) : 0;
		$price_max = isset( $_GET['price_max'] ) ? absint( LK_Format::latin_digits( wp_unslash( $_GET['price_max'] ) ) ) : 0;
		$sort      = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'newest';
		$available = isset( $_GET['availability'] ) ? sanitize_key( wp_unslash( $_GET['availability'] ) ) : '';
		// phpcs:enable

		$meta_query = (array) $query->get( 'meta_query' );

		if ( $year_from ) {
			$meta_query[] = array( 'key' => '_lk_year', 'value' => $year_from, 'compare' => '>=', 'type' => 'NUMERIC' );
		}
		if ( $year_to ) {
			$meta_query[] = array( 'key' => '_lk_year', 'value' => $year_to, 'compare' => '<=', 'type' => 'NUMERIC' );
		}
		if ( $price_min || $price_max ) {
			$meta_query[] = array(
				'key'     => '_lk_market_price',
				'value'   => array( $price_min, $price_max ? $price_max : PHP_INT_MAX ),
				'compare' => 'BETWEEN',
				'type'    => 'NUMERIC',
			);
		}
		if ( 'stopped' === $available ) {
			$meta_query[] = array( 'key' => '_lk_production_status', 'value' => 'stopped' );
		} elseif ( 'available' === $available ) {
			$meta_query[] = array( 'key' => '_lk_production_status', 'value' => 'stopped', 'compare' => '!=' );
		}

		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$sorts = array(
			'price_asc'    => array( '_lk_market_price', 'ASC' ),
			'price_desc'   => array( '_lk_market_price', 'DESC' ),
			'change_desc'  => array( '_lk_change_percent', 'DESC' ),
			'change_asc'   => array( '_lk_change_percent', 'ASC' ),
			'name'         => array( 'title', 'ASC' ),
			'popular'      => array( '_lk_views', 'DESC' ),
		);

		if ( isset( $sorts[ $sort ] ) ) {
			list( $key, $order ) = $sorts[ $sort ];
			if ( 'title' === $key ) {
				$query->set( 'orderby', 'title' );
			} else {
				$query->set( 'meta_key', $key ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query->set( 'orderby', 'meta_value_num' );
			}
			$query->set( 'order', $order );
		}

		if ( isset( $_GET['s'] ) && $_GET['s'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ids = self::find_vehicle_ids( sanitize_text_field( wp_unslash( $_GET['s'] ) ), 200 );
			$query->set( 's', '' );
			$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		}
	}
}
