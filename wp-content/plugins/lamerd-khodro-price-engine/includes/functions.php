<?php
/**
 * Engine helpers used by admin views.
 *
 * @package LamerdKhodro\PriceEngine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin URL for an engine page.
 *
 * @param string $page Page slug.
 * @param array  $args Query args.
 * @return string
 */
function lkpe_admin_url( $page = 'lkpe', $args = array() ) {
	$args = array_merge( array( 'page' => $page ), $args );
	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * Pagination markup for admin tables.
 *
 * @param int   $page  Current page.
 * @param int   $pages Total pages.
 * @param array $args  Query args to keep.
 * @return string
 */
function lkpe_pagination( $page, $pages, $args = array() ) {
	if ( $pages <= 1 ) {
		return '';
	}

	$out   = '<div class="tablenav"><div class="tablenav-pages lkpe-pagination">';
	$page  = max( 1, (int) $page );
	$pages = (int) $pages;

	if ( $page > 1 ) {
		$out .= '<a class="button" href="' . esc_url( lkpe_admin_url( $args['__page'] ?? 'lkpe', array_merge( $args, array( 'paged' => $page - 1 ) ) ) ) . '">قبلی</a>';
	}

	$out .= '<span class="lkpe-pagination__info">صفحه ' . esc_html( LK_Format::digits( $page ) ) . ' از ' . esc_html( LK_Format::digits( $pages ) ) . '</span>';

	if ( $page < $pages ) {
		$out .= '<a class="button" href="' . esc_url( lkpe_admin_url( $args['__page'] ?? 'lkpe', array_merge( $args, array( 'paged' => $page + 1 ) ) ) ) . '">بعدی</a>';
	}

	$out .= '</div></div>';

	return $out;
}

/**
 * Status badge.
 *
 * @param string $status Status key.
 * @return string
 */
function lkpe_status_badge( $status ) {
	$map = array(
		'valid'      => array( 'معتبر', 'lkpe-badge--ok' ),
		'incomplete' => array( 'ناقص', 'lkpe-badge--warn' ),
		'suspect'    => array( 'نیازمند بررسی', 'lkpe-badge--danger' ),
		'success'    => array( 'موفق', 'lkpe-badge--ok' ),
		'partial'    => array( 'نیمه‌موفق', 'lkpe-badge--warn' ),
		'failed'     => array( 'ناموفق', 'lkpe-badge--danger' ),
		'running'    => array( 'در حال اجرا', 'lkpe-badge--info' ),
	);

	$item  = $map[ $status ] ?? array( $status, '' );
	return '<span class="lkpe-badge ' . esc_attr( $item[1] ) . '">' . esc_html( $item[0] ) . '</span>';
}

/**
 * Brand terms for filters.
 *
 * @return WP_Term[]
 */
function lkpe_brands() {
	return function_exists( 'lk_get_brands' ) ? lk_get_brands() : array();
}

/**
 * Toman display for admin tables.
 *
 * @param int|null $amount Amount.
 * @return string
 */
function lkpe_price_text( $amount ) {
	if ( null === $amount || '' === $amount ) {
		return '—';
	}
	return '<span class="lkpe-price" dir="rtl">' . esc_html( LK_Format::price( $amount ) ) . '</span> <small>تومان</small>';
}

/**
 * Change display for admin tables.
 *
 * @param int|null   $amount  Change amount.
 * @param float|null $percent Change percent.
 * @param string     $status  Data status.
 * @return string
 */
function lkpe_change_text( $amount, $percent, $status = 'valid' ) {
	if ( 'suspect' === $status ) {
		return '<span class="lkpe-muted">ثبت نشده</span>';
	}
	if ( null === $amount && null === $percent ) {
		return '<span class="lkpe-muted">—</span>';
	}

	$class = 'lkpe-change';
	if ( null !== $amount ) {
		$class .= $amount > 0 ? ' is-up' : ( $amount < 0 ? ' is-down' : ' is-flat' );
	}

	$out = '<span class="' . esc_attr( $class ) . '">';
	if ( null !== $amount ) {
		$out .= LK_Format::change_amount( $amount );
	}
	if ( null !== $percent && null !== $amount ) {
		$out .= ' <small>(' . LK_Format::change_percent( $percent ) . ')</small>';
	} elseif ( null !== $percent ) {
		$out .= LK_Format::change_percent( $percent );
	}
	$out .= '</span>';

	return $out;
}

/**
 * Notice from query args or transient.
 *
 * @return void
 */
function lkpe_render_notices() {
	$notice = isset( $_GET['lkpe_notice'] ) ? sanitize_key( wp_unslash( $_GET['lkpe_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$messages = array(
		'settings_saved' => array( 'success', 'تنظیمات ذخیره شد.' ),
		'source_saved'   => array( 'success', 'تنظیمات منبع ذخیره شد.' ),
		'synced'         => array( 'success', 'همگام‌سازی انجام شد.' ),
		'snapshot'       => array( 'success', 'عکس لحظه‌ای ثبت شد.' ),
		'not_allowed'    => array( 'error', 'دسترسی مجاز نیست.' ),
	);

	$result = get_transient( 'lkpe_sync_result_' . get_current_user_id() );
	if ( $result ) {
		delete_transient( 'lkpe_sync_result_' . get_current_user_id() );
		$class = ! empty( $result['success'] ) ? 'success' : 'error';
		echo '<div class="notice notice-' . esc_attr( $class ) . ' is-dismissible"><p>' . esc_html( $result['message'] ?? '' ) . '</p>';
		if ( ! empty( $result['errors'] ) ) {
			echo '<ul class="lkpe-error-list">';
			foreach ( array_slice( $result['errors'], 0, 5 ) as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
	}

	if ( $notice && isset( $messages[ $notice ] ) ) {
		list( $type, $text ) = $messages[ $notice ];
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}
}
