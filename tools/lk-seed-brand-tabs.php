<?php
/**
 * Seed the sub-brands used as mega menu tabs.
 *
 * Creates child terms of a brand (for example مدیران خودرو → ام وی ام /
 * فونیکس / اکستریم) and tags the matching vehicles with them. Vehicles keep
 * their parent brand, so brand archives and counts stay correct.
 *
 * Usage: php tools/lk-seed-brand-tabs.php [--dry-run]
 *
 * @package LamerdKhodro\Tools
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/wp-load.php';

$dry_run = in_array( '--dry-run', $argv, true );

/**
 * Tab groups: parent brand slug => child terms => vehicle title keywords.
 */
$groups = array(
	'modiran-khodro' => array(
		array(
			'slug'     => 'mvm',
			'name'     => 'ام وی ام',
			'name_en'  => 'MVM',
			'order'    => 1,
			'keywords' => array( 'ایکس 33', 'ایکس 55', 'ایکس 77', 'آریزو 5', 'آریزو 6 پرو' ),
		),
		array(
			'slug'     => 'fownix',
			'name'     => 'فونیکس',
			'name_en'  => 'Fownix',
			'order'    => 2,
			'keywords' => array( 'فونیکس', 'تیگو 7', 'تیگو 8', 'آریزو 6 (GT)', 'آریزو 8' ),
		),
		array(
			'slug'     => 'xtrim',
			'name'     => 'اکستریم',
			'name_en'  => 'Xtrim',
			'order'    => 3,
			'keywords' => array( 'تیگو 9' ),
		),
	),
);

/**
 * Vehicles of one brand, as id => title.
 *
 * @param int $brand_id Brand term ID.
 * @return array<int,string>
 */
function lk_tools_brand_vehicles( $brand_id ) {
	$ids = get_posts(
		array(
			'post_type'      => 'lk_vehicle',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'lk_brand',
					'field'    => 'term_id',
					'terms'    => (int) $brand_id,
				),
			),
		)
	);

	$out = array();
	foreach ( $ids as $id ) {
		$out[ (int) $id ] = get_the_title( $id );
	}
	return $out;
}

foreach ( $groups as $parent_slug => $tabs ) {
	$parent = get_term_by( 'slug', $parent_slug, 'lk_brand' );
	if ( ! $parent ) {
		echo "! parent brand not found: {$parent_slug}\n";
		continue;
	}

	$vehicles = lk_tools_brand_vehicles( $parent->term_id );
	echo "== {$parent->name} ({$parent->slug}) — " . count( $vehicles ) . " خودرو\n";

	$claimed = array();

	foreach ( $tabs as $tab ) {
		$term = get_term_by( 'slug', $tab['slug'], 'lk_brand' );

		if ( $term && (int) $term->parent !== (int) $parent->term_id ) {
			echo "! slug {$tab['slug']} already used by another term\n";
			continue;
		}

		if ( ! $term ) {
			if ( $dry_run ) {
				echo "  + create tab {$tab['name']}\n";
			} else {
				$created = wp_insert_term(
					$tab['name'],
					'lk_brand',
					array(
						'slug'   => $tab['slug'],
						'parent' => (int) $parent->term_id,
					)
				);
				if ( is_wp_error( $created ) ) {
					echo "  ! {$tab['slug']}: " . $created->get_error_message() . "\n";
					continue;
				}
				$term = get_term( $created['term_id'], 'lk_brand' );
				echo "  + tab {$tab['name']}\n";
			}
		}

		if ( $term ) {
			wp_update_term(
				$term->term_id,
				'lk_brand',
				array(
					'parent' => (int) $parent->term_id,
					'name'   => $tab['name'],
				)
			);
			update_term_meta( $term->term_id, '_lk_mega_order', (int) $tab['order'] );
			if ( ! empty( $tab['name_en'] ) ) {
				update_term_meta( $term->term_id, 'name_en', $tab['name_en'] );
			}
		}

		$matched = array();
		foreach ( $vehicles as $id => $title ) {
			if ( isset( $claimed[ $id ] ) ) {
				continue;
			}
			foreach ( $tab['keywords'] as $keyword ) {
				if ( false !== mb_stripos( $title, $keyword ) ) {
					$matched[ $id ] = $title;
					break;
				}
			}
		}

		echo '  ' . $tab['name'] . ': ' . count( $matched ) . " خودرو\n";

		foreach ( $matched as $id => $title ) {
			$claimed[ $id ] = true;
			echo "    - {$title}\n";

			if ( $dry_run || ! $term ) {
				continue;
			}

			$current = wp_get_object_terms( $id, 'lk_brand', array( 'fields' => 'ids' ) );
			$current = is_wp_error( $current ) ? array() : array_map( 'intval', $current );
			$current[] = (int) $term->term_id;
			$current[] = (int) $parent->term_id;
			wp_set_object_terms( $id, array_values( array_unique( $current ) ), 'lk_brand' );
		}
	}

	$left = array_diff_key( $vehicles, $claimed );
	echo '  بدون تب: ' . count( $left ) . "\n";
	foreach ( $left as $title ) {
		echo "    · {$title}\n";
	}
}

echo $dry_run ? "\n(dry run — nothing written)\n" : "\ndone\n";
