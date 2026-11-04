<?php
/**
 * Theme presentation helpers.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a theme option.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function lk_opt( $key, $default = null ) {
	if ( null === $default ) {
		$defaults = lk_theme_defaults();
		$default  = $defaults[ $key ] ?? '';
	}
	return get_theme_mod( 'lk_' . $key, $default );
}

/**
 * Whether vehicle data functions exist.
 *
 * @return bool
 */
function lk_data_ready() {
	return function_exists( 'lk_get_price' ) && post_type_exists( 'lk_vehicle' );
}

/**
 * Price amount markup with isolated direction.
 *
 * @param int|null $amount Amount in Toman.
 * @return string
 */
function lk_money( $amount ) {
	if ( null === $amount || '' === $amount ) {
		return '<span class="lk-money lk-money--empty">ناموجود</span>';
	}
	return '<span class="lk-money">' . esc_html( lk_price( $amount ) ) . '<span class="lk-money__unit">تومان</span></span>';
}

/**
 * Price amount for compact cards.
 *
 * @param int|null $amount Amount.
 * @return string
 */
function lk_money_short( $amount ) {
	if ( null === $amount || '' === $amount ) {
		return '<span class="lk-money lk-money--empty">ناموجود</span>';
	}
	return '<span class="lk-money">' . esc_html( LK_Format::price_short( $amount ) ) . '<span class="lk-money__unit">تومان</span></span>';
}

/**
 * Change badge.
 *
 * @param array|null $price Price array.
 * @return string
 */
function lk_change_badge( $price ) {
	if ( ! $price || 'suspect' === ( $price['data_status'] ?? '' ) ) {
		return '<span class="lk-change lk-change--suspect">—</span>';
	}

	if ( null === ( $price['change_percent'] ?? null ) && null === ( $price['change_amount'] ?? null ) ) {
		return '<span class="lk-change lk-change--empty">بدون تغییر</span>';
	}

	$class = lk_change_class( $price );
	$percent = $price['change_percent'] ?? null;
	$amount  = $price['change_amount'] ?? null;

	$arrow = 'is-up' === $class ? '▲' : ( 'is-down' === $class ? '▼' : '•' );

	$out  = '<span class="lk-change ' . esc_attr( $class ) . '">';
	$out .= '<span class="lk-change__arrow" aria-hidden="true">' . $arrow . '</span>';

	if ( null !== $percent ) {
		$out .= '<span class="lk-change__percent">' . esc_html( LK_Format::change_percent( $percent ) ) . '</span>';
	}

	if ( null !== $amount ) {
		$out .= '<span class="lk-change__amount">' . esc_html( LK_Format::change_amount( $amount ) ) . '</span>';
	}

	$out .= '</span>';

	return $out;
}

/**
 * Vehicle monogram from its name.
 *
 * @param string $name Name.
 * @return string
 */
function lk_monogram( $name ) {
	$name = trim( wp_strip_all_tags( (string) $name ) );
	if ( '' === $name ) {
		return '—';
	}
	$parts = preg_split( '/\s+/u', $name );
	$first = mb_substr( $parts[0], 0, 1, 'UTF-8' );
	$second = isset( $parts[1] ) ? mb_substr( $parts[1], 0, 1, 'UTF-8' ) : '';
	return $first . $second;
}

/**
 * Section heading.
 *
 * @param string $index Index label, e.g. "۰۲".
 * @param string $title Title.
 * @param string $text  Description.
 * @param array  $link  [label, url].
 * @return void
 */
function lk_section_head( $index, $title, $text = '', $link = array() ) {
	?>
	<header class="lk-section-head">
		<div class="lk-section-head__main">
			<span class="lk-section-head__index" aria-hidden="true"><?php echo esc_html( $index ); ?></span>
			<div>
				<h2 class="lk-section-head__title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( $text ) : ?>
					<p class="lk-section-head__text"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! empty( $link['url'] ) ) : ?>
			<a class="lk-section-head__link" href="<?php echo esc_url( $link['url'] ); ?>">
				<?php echo esc_html( $link['label'] ?? 'مشاهده همه' ); ?>
				<span aria-hidden="true">←</span>
			</a>
		<?php endif; ?>
	</header>
	<?php
}

/**
 * Breadcrumb trail.
 *
 * @return void
 */
function lk_breadcrumb() {
	$items = array(
		array(
			'label' => 'خانه',
			'url'   => home_url( '/' ),
		),
	);

	if ( is_singular( 'lk_vehicle' ) ) {
		$items[] = array(
			'label' => 'خودروها',
			'url'   => get_post_type_archive_link( 'lk_vehicle' ),
		);
		$brand = function_exists( 'lk_vehicle_brand' ) ? lk_vehicle_brand( get_the_ID() ) : null;
		if ( $brand ) {
			$link = get_term_link( $brand );
			if ( ! is_wp_error( $link ) ) {
				$items[] = array(
					'label' => $brand->name,
					'url'   => $link,
				);
			}
		}
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_tax( 'lk_brand' ) ) {
		$items[] = array(
			'label' => 'برندها',
			'url'   => lk_page_url( 'brands' ),
		);
		$items[] = array(
			'label' => single_term_title( '', false ),
			'url'   => '',
		);
	} elseif ( is_post_type_archive( 'lk_vehicle' ) || is_page_template( 'page-templates/page-vehicles.php' ) ) {
		$items[] = array(
			'label' => 'خودروها',
			'url'   => '',
		);
	} elseif ( is_singular( 'lk_listing' ) ) {
		$items[] = array(
			'label' => 'آگهی‌های فروش',
			'url'   => get_post_type_archive_link( 'lk_listing' ),
		);
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_post_type_archive( 'lk_listing' ) ) {
		$items[] = array(
			'label' => 'آگهی‌های فروش',
			'url'   => '',
		);
	} elseif ( is_page() ) {
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_singular( 'post' ) ) {
		$items[] = array(
			'label' => 'اخبار',
			'url'   => lk_blog_url(),
		);
		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_search() ) {
		$items[] = array(
			'label' => 'جستجو: ' . get_search_query(),
			'url'   => '',
		);
	} elseif ( is_archive() ) {
		$items[] = array(
			'label' => wp_strip_all_tags( get_the_archive_title() ),
			'url'   => '',
		);
	}

	echo '<nav class="lk-breadcrumb" aria-label="مسیر صفحه"><ol>';
	foreach ( $items as $item ) {
		if ( $item['url'] ) {
			echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
		} else {
			echo '<li><span aria-current="page">' . esc_html( $item['label'] ) . '</span></li>';
		}
	}
	echo '</ol></nav>';
}

/**
 * Freshness note for the price board.
 *
 * @return void
 */
function lk_update_note() {
	$last = function_exists( 'lk_get_last_sync' ) ? lk_get_last_sync() : null;
	if ( ! $last ) {
		return;
	}

	$stale = ! empty( $last['is_stale'] );
	?>
	<span class="lk-update-note <?php echo $stale ? 'is-stale' : 'is-fresh'; ?>">
		<span class="lk-update-note__dot" aria-hidden="true"></span>
		<?php if ( $stale ) : ?>
			آخرین دریافت موفق: <?php echo esc_html( lk_jalali( $last['finished_ts'], 'datetime' ) ); ?>
		<?php else : ?>
			به‌روزرسانی قیمت‌ها: <?php echo esc_html( lk_jalali( $last['finished_ts'], 'datetime' ) ); ?>
		<?php endif; ?>
	</span>
	<?php
}

/**
 * Pagination for WP_Query or custom arrays.
 *
 * @param WP_Query|array $query   Query or [page, pages].
 * @param array          $classes Extra classes.
 * @return void
 */
function lk_pagination( $query, $classes = array() ) {
	if ( is_array( $query ) ) {
		$current = max( 1, (int) ( $query['page'] ?? 1 ) );
		$pages   = max( 1, (int) ( $query['pages'] ?? 1 ) );
		$base    = $query['base'] ?? '';
	} else {
		$current = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		$pages   = (int) $query->max_num_pages;
		$base    = '';
	}

	if ( $pages <= 1 ) {
		return;
	}

	$links = paginate_links(
		array(
			'total'     => $pages,
			'current'   => $current,
			'type'      => 'array',
			'prev_text' => '→ قبلی',
			'next_text' => 'بعدی ←',
			'base'      => $base ? $base . '%_%' : '',
			'format'    => $base ? '?paged=%#%' : '',
		)
	);

	if ( ! $links ) {
		return;
	}

	echo '<nav class="lk-pagination ' . esc_attr( implode( ' ', (array) $classes ) ) . '" aria-label="صفحه‌بندی">';
	foreach ( $links as $link ) {
		echo wp_kses_post( $link );
	}
	echo '</nav>';
}

/**
 * Attribution markup.
 *
 * @return void
 */
function lk_attribution_line() {
	if ( function_exists( 'lk_attribution' ) ) {
		echo '<p class="lk-attribution">' . wp_kses_post( lk_attribution() ) . '</p>';
	}
}

/**
 * Bare source link (no sentence wrapper).
 *
 * @return string
 */
function lk_source_link() {
	return '<a href="https://cofekhodro.ir" target="_blank" rel="noopener">کافه خودرو</a>';
}

/**
 * Vehicle production status badge.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lk_status_badge( $post_id ) {
	$label = function_exists( 'lk_production_status' ) ? lk_production_status( $post_id ) : '';
	if ( ! $label ) {
		return '';
	}
	$class = false !== mb_strpos( $label, 'متوقف' ) ? 'is-stopped' : 'is-active';
	return '<span class="lk-status ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * Social links configured in the customizer.
 *
 * @return array<string,array>
 */
function lk_social_links() {
	$networks = array(
		'instagram' => array( 'اینستاگرام', 'اینستاگرام' ),
		'telegram'  => array( 'تلگرام', 'تلگرام' ),
		'whatsapp'  => array( 'واتساپ', 'واتساپ' ),
		'x'         => array( 'ایکس', 'X' ),
		'aparat'    => array( 'آپارات', 'آپارات' ),
	);

	$links = array();
	foreach ( $networks as $key => $label ) {
		$url = lk_opt( 'social_' . $key, '' );
		if ( $url ) {
			$links[ $key ] = array(
				'label' => $label[0],
				'url'   => $url,
			);
		}
	}
	return $links;
}
