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
 * @param array $exclude Term IDs to exclude from the query.
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
		$args['tax_query'][] = array(
			'taxonomy' => 'lk_brand',
			'field'    => 'term_id',
			'terms'    => array_map( 'intval', $exclude ),
			'operator' => 'NOT IN',
		);
		// Keep the post itself out of the child terms it belongs to.
		$args['tax_query'][0]['include_children'] = false;
	}

	$ids = get_posts( $args );

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
 * @param WP_Term $brand Parent brand term.
 * @param int     $limit Maximum number of tabs.
 * @return array<int,array{term:WP_Term,items:array,total:int}>
 */
function lk_mega_brand_tabs( WP_Term $brand, $limit = 6 ) {
	$children = get_terms(
		array(
			'taxonomy'   => 'lk_brand',
			'parent'     => (int) $brand->term_id,
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $children ) || ! $children ) {
		return array();
	}

	usort(
		$children,
		static function ( $a, $b ) {
			$order_a = get_term_meta( $a->term_id, '_lk_mega_order', true );
			$order_b = get_term_meta( $b->term_id, '_lk_mega_order', true );
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
		$items = lk_mega_items( $child->term_id );
		if ( ! $items ) {
			continue;
		}

		$tabs[] = array(
			'term'  => $child,
			'items' => $items,
			'total' => (int) $child->count,
		);

		if ( count( $tabs ) >= (int) $limit ) {
			break;
		}
	}

	return $tabs;
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

	// One query for the whole brand tree: a brand is only a tab candidate when it
	// has child terms with vehicles, so plain brands stay cheap.
	$all_terms = get_terms(
		array(
			'taxonomy'   => 'lk_brand',
			'hide_empty' => true,
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

		$tabs = isset( $has_tabs[ (int) $term->term_id ] ) ? lk_mega_brand_tabs( $term ) : array();

		// The bar stays short, but a brand that groups its cars in tabs always
		// gets a trigger so those tabs are reachable from the header.
		if ( ! $tabs && count( $out ) >= $limit ) {
			continue;
		}

		$items = array();
		$total = (int) $term->count;

		if ( $tabs ) {
			// Vehicles kept on the parent brand but outside every sub-brand.
			$child_ids = wp_list_pluck( wp_list_pluck( $tabs, 'term' ), 'term_id' );
			$leftover  = lk_mega_items( $term->term_id, 8, $child_ids );

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
