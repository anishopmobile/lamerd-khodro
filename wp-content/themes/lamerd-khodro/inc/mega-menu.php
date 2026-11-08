<?php
/**
 * Header mega menu: brand shortcuts and the cars of every featured brand.
 *
 * Panels are rendered server-side so the menu works without JavaScript and
 * always mirrors the vehicle catalog (name + photo), never a price snapshot.
 *
 * A brand that has child terms in the `lk_brand` taxonomy (for example
 * مدیران خودرو → ام وی ام / فونیکس / اکستریم) gets one tab per child term, so
 * any other menu item can be grouped the same way just by adding sub-brands;
 * no template change is needed.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Term meta keys owned by the mega menu.
 *
 * The list of vehicles per tab lives in term meta (not in the post terms), so
 * editing a vehicle in the admin can never empty a tab by accident.
 */
const LK_MEGA_ORDER_META    = '_lk_mega_order';
const LK_MEGA_VISIBLE_META  = '_lk_mega_visible';
const LK_MEGA_TAB_META      = '_lk_mega_tab';
const LK_MEGA_VEHICLES_META = '_lk_mega_vehicles';

/**
 * Photo URL of one vehicle for the mega menu cards.
 *
 * @param int    $post_id Vehicle ID.
 * @param string $size    Attachment size.
 * @return string Empty string when the vehicle has no featured image.
 */
function lk_mega_vehicle_image( $post_id, $size = 'medium_large' ) {
	$url = get_the_post_thumbnail_url( (int) $post_id, $size );
	if ( ! $url ) {
		$url = get_the_post_thumbnail_url( (int) $post_id, 'medium' );
	}
	return $url ? $url : '';
}

/**
 * Shared placeholder for vehicles without a photo.
 *
 * @return string
 */
function lk_mega_placeholder() {
	return get_theme_file_uri( 'assets/img/vehicle-placeholder.svg' );
}

/**
 * Cards of one brand term, up to $limit vehicles.
 *
 * One card per distinct photo comes first so a panel is not filled with eight
 * copies of the same model; the rest fill the remaining slots.
 *
 * @param int   $term_id Brand term ID.
 * @param int   $limit   Maximum number of cards.
 * @param int[] $exclude Vehicle IDs to leave out of the query.
 * @return array<int,array{id:int,title:string,url:string,image:string}>
 */
function lk_mega_items( $term_id, $limit = 8, $exclude = array() ) {
	$args = array(
		'post_type'      => 'lk_vehicle',
		'posts_per_page' => 60,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => 'lk_brand',
				'field'    => 'term_id',
				'terms'    => (int) $term_id,
			),
		),
	);

	if ( $exclude ) {
		$args['post__not_in'] = array_map( 'intval', $exclude );
	}

	$ids = get_posts( $args );

	return lk_mega_build_items( $ids, $limit );
}

/**
 * Cards built from a fixed, ordered list of vehicle IDs.
 *
 * Used by tabs whose vehicles were picked in the admin; unpublished or
 * deleted vehicles are skipped and the saved order is kept.
 *
 * @param int[] $ids   Vehicle IDs in display order.
 * @param int   $limit Maximum number of cards.
 * @return array<int,array{id:int,title:string,url:string,image:string}>
 */
function lk_mega_items_by_ids( $ids, $limit = 8 ) {
	$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );

	if ( ! $ids ) {
		return array();
	}

	$found = get_posts(
		array(
			'post_type'      => 'lk_vehicle',
			'post__in'       => $ids,
			'posts_per_page' => count( $ids ),
			'orderby'        => 'post__in',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return lk_mega_build_items( $found, $limit );
}

/**
 * Turn vehicle IDs into mega menu cards.
 *
 * One card per distinct photo comes first so a panel is not filled with eight
 * copies of the same model; the rest fill the remaining slots.
 *
 * @param int[] $ids   Vehicle IDs.
 * @param int   $limit Maximum number of cards.
 * @return array<int,array{id:int,title:string,url:string,image:string}>
 */
function lk_mega_build_items( $ids, $limit = 8 ) {
	if ( ! $ids ) {
		return array();
	}

	$limit = max( 1, (int) $limit );
	$items = array();
	$spare = array();
	$seen  = array();

	foreach ( $ids as $id ) {
		$thumb = (int) get_post_thumbnail_id( $id );
		$key   = $thumb ? 'img-' . $thumb : 'txt-' . mb_substr( get_the_title( $id ), 0, 8 );
		$item  = array(
			'id'    => (int) $id,
			'title' => get_the_title( $id ),
			'url'   => get_permalink( $id ),
			'image' => lk_mega_vehicle_image( $id ),
		);

		if ( isset( $seen[ $key ] ) ) {
			$spare[] = $item;
			continue;
		}

		$seen[ $key ] = true;

		if ( count( $items ) < $limit ) {
			$items[] = $item;
		} else {
			$spare[] = $item;
		}
	}

	if ( count( $items ) < $limit ) {
		foreach ( $spare as $item ) {
			$items[] = $item;
			if ( count( $items ) >= $limit ) {
				break;
			}
		}
	}

	return $items;
}

/**
 * Tabs of one brand: its child terms that have vehicles.
 *
 * Tab order follows the `_lk_mega_order` term meta when set (handy for a
 * fixed order like MVM / Fownix / Xtrim), then the number of models.
 *
 * Vehicles of a tab come from `_lk_mega_vehicles` (the list picked in the
 * brand editor). Only when that list is empty the term's own vehicles are
 * used, so a tab works with or without vehicle-to-sub-brand assignments.
 *
 * @param WP_Term $brand Parent brand term.
 * @param int     $limit Maximum number of tabs.
 * @return array<int,array{term:WP_Term,items:array,total:int}>
 */
function lk_mega_brand_tabs( WP_Term $brand, $limit = 6 ) {
	// Not hide_empty: a tab may list its vehicles in term meta while no vehicle
	// is assigned to the sub-brand itself.
	$children = get_terms(
		array(
			'taxonomy'   => 'lk_brand',
			'parent'     => (int) $brand->term_id,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $children ) || ! $children ) {
		return array();
	}

	usort(
		$children,
		static function ( $a, $b ) {
			$order_a = get_term_meta( $a->term_id, LK_MEGA_ORDER_META, true );
			$order_b = get_term_meta( $b->term_id, LK_MEGA_ORDER_META, true );
			// Tabs with an explicit order come first, in that order; the rest follow
			// by number of models. Empty meta must not sort ahead of order 1.
			$order_a = '' === $order_a ? 9999 : (int) $order_a;
			$order_b = '' === $order_b ? 9999 : (int) $order_b;

			if ( $order_a !== $order_b ) {
				return $order_a <=> $order_b;
			}

			return (int) $b->count <=> (int) $a->count;
		}
	);

	$tabs = array();

	foreach ( $children as $child ) {
		if ( '0' === get_term_meta( $child->term_id, LK_MEGA_TAB_META, true ) ) {
			continue;
		}

		$ids   = lk_mega_tab_vehicle_ids( $child );
		$items = lk_mega_build_items( $ids );
		if ( ! $items ) {
			continue;
		}

		$tabs[] = array(
			'term'  => $child,
			'items' => $items,
			'ids'   => $ids,
			// The tab's own vehicle count, not the term count: the term counter
			// drops to zero whenever a vehicle is saved in the admin and its
			// sub-brand assignment is rewritten.
			'total' => count( $ids ),
		);

		if ( count( $tabs ) >= (int) $limit ) {
			break;
		}
	}

	return $tabs;
}

/**
 * Vehicle IDs of one tab, honoring the list saved in the term editor.
 *
 * @param WP_Term $term  Tab term.
 * @return int[]
 */
function lk_mega_tab_vehicle_ids( WP_Term $term ) {
	$saved = get_term_meta( $term->term_id, LK_MEGA_VEHICLES_META, true );
	$ids   = is_array( $saved ) ? array_values( array_filter( array_map( 'absint', $saved ) ) ) : array();

	if ( $ids ) {
		$found = get_posts(
			array(
				'post_type'      => 'lk_vehicle',
				'post__in'       => $ids,
				'posts_per_page' => count( $ids ),
				'orderby'        => 'post__in',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( $found ) {
			return array_map( 'intval', $found );
		}
	}

	// No saved list: fall back to the vehicles assigned to the sub-brand.
	$found = get_posts(
		array(
			'post_type'      => 'lk_vehicle',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'lk_brand',
					'field'    => 'term_id',
					'terms'    => (int) $term->term_id,
				),
			),
		)
	);

	return array_map( 'intval', $found );
}

/**
 * Vehicles of one tab, honoring the list saved in the term editor.
 *
 * @param WP_Term $term  Tab term.
 * @param int     $limit Maximum number of cards.
 * @return array<int,array{id:int,title:string,url:string,image:string}>
 */
function lk_mega_tab_items( WP_Term $term, $limit = 8 ) {
	return lk_mega_build_items( lk_mega_tab_vehicle_ids( $term ), $limit );
}

/**
 * Brands shown as shortcut triggers in the dark header bar.
 *
 * Ordered by the number of published models, skipping placeholder brands that
 * have no useful shortcut value.
 *
 * @param int $limit Maximum number of brands.
 * @return array<int,array{term:WP_Term,items:array,tabs:array,total:int}>
 */
function lk_mega_brands( $limit = 4 ) {
	if ( ! function_exists( 'lk_get_brands' ) ) {
		return array();
	}

	$limit  = max( 1, (int) $limit );
	$brands = lk_get_brands( 0, true );
	$out    = array();

	// The brand editor can put a brand first (or keep it out of the bar).
	usort(
		$brands,
		static function ( $a, $b ) {
			$order_a = get_term_meta( $a->term_id, LK_MEGA_ORDER_META, true );
			$order_b = get_term_meta( $b->term_id, LK_MEGA_ORDER_META, true );
			$order_a = '' === $order_a ? 9999 : (int) $order_a;
			$order_b = '' === $order_b ? 9999 : (int) $order_b;

			if ( $order_a !== $order_b ) {
				return $order_a <=> $order_b;
			}

			return (int) $b->count <=> (int) $a->count;
		}
	);

	// Tabs may list their vehicles in term meta, so a sub-brand with zero
	// assigned posts still makes its parent a tab candidate.
	$all_terms = get_terms(
		array(
			'taxonomy'   => 'lk_brand',
			'hide_empty' => false,
		)
	);

	$has_tabs = array();
	if ( ! is_wp_error( $all_terms ) ) {
		foreach ( $all_terms as $child ) {
			if ( (int) $child->parent > 0 ) {
				$has_tabs[ (int) $child->parent ] = true;
			}
		}
	}

	foreach ( $brands as $term ) {
		if ( false !== mb_stripos( $term->name, 'سایر' ) ) {
			continue;
		}

		if ( '0' === get_term_meta( $term->term_id, LK_MEGA_VISIBLE_META, true ) ) {
			continue;
		}

		$tabs = isset( $has_tabs[ (int) $term->term_id ] ) ? lk_mega_brand_tabs( $term ) : array();

		// The bar stays short, but a brand that groups its cars in tabs always
		// gets a trigger so those tabs are reachable from the header.
		if ( ! $tabs && count( $out ) >= $limit ) {
			continue;
		}

		$items = array();
		$total = (int) $term->count;

		if ( $tabs ) {
			// Vehicles of the parent brand that no tab already lists. Excluding
			// the tab's vehicles (not just its term) keeps a car from showing up
			// twice when its sub-brand assignment was rewritten.
			$tab_ids  = array();
			foreach ( $tabs as $tab ) {
				$tab_ids = array_merge( $tab_ids, isset( $tab['ids'] ) ? $tab['ids'] : wp_list_pluck( $tab['items'], 'id' ) );
			}
			$tab_ids  = array_values( array_unique( array_map( 'intval', $tab_ids ) ) );
			$leftover = lk_mega_items( $term->term_id, 8, $tab_ids );

			if ( $leftover ) {
				$tabs[] = array(
					'term'  => $term,
					'items' => $leftover,
					'total' => count( $leftover ),
					'more'  => true,
				);
			}

			$total = 0;
			foreach ( $tabs as $tab ) {
				$total += (int) $tab['total'];
			}
		} else {
			$items = lk_mega_items( $term->term_id );
			if ( ! $items ) {
				continue;
			}
		}

		$out[] = array(
			'term'  => $term,
			'items' => $items,
			'tabs'  => $tabs,
			'total' => $total,
		);
	}

	return $out;
}

/**
 * Print the vehicle cards of a mega menu grid.
 *
 * @param array $items Items built by lk_mega_items().
 * @return void
 */
function lk_mega_cards( $items ) {
	foreach ( $items as $item ) {
		?>
		<li>
			<a class="lk-mega__card" href="<?php echo esc_url( $item['url'] ); ?>">
				<span class="lk-mega__thumb">
					<img
						src="<?php echo esc_url( $item['image'] ? $item['image'] : lk_mega_placeholder() ); ?>"
						alt=""
						loading="lazy"
						width="300"
						height="225"
					/>
				</span>
				<span class="lk-mega__card-title"><?php echo esc_html( $item['title'] ); ?></span>
			</a>
		</li>
		<?php
	}
}

/**
 * Every brand term, used by the "all brands" mega panel.
 *
 * @return WP_Term[]
 */
function lk_mega_all_brands() {
	return function_exists( 'lk_get_brands' ) ? lk_get_brands( 0, true ) : array();
}
