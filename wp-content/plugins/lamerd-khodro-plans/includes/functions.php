<?php
/**
 * Template helpers.
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render a plugin template with arguments.
 *
 * @param string $template Template file name without extension.
 * @param array  $args     Variables extracted for the template.
 * @param bool   $echo     Print instead of returning.
 * @return string
 */
function lkp_template( $template, $args = array(), $echo = true ) {
	$template = preg_replace( '/[^a-z0-9-]/', '', (string) $template );
	$file     = LKP_PATH . 'templates/' . $template . '.php';

	if ( ! is_readable( $file ) ) {
		return '';
	}

	ob_start();
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	extract( $args, EXTR_SKIP );
	include $file;
	$html = ob_get_clean();

	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	return $html;
}

/**
 * Number of plans in a given state.
 *
 * @param string $state State key.
 * @return int
 */
function lkp_count_state( $state ) {
	$counts = LKP_Plans::counts();
	return (int) ( $counts[ $state ] ?? 0 );
}

/**
 * Persian digits helper.
 *
 * @param string|int $value Value.
 * @return string
 */
function lkp_fa( $value ) {
	return LKP_Jalali::persian_digits( $value );
}

/**
 * Countdown text (server-side initial value; JS keeps it live).
 *
 * @param int $deadline Unix timestamp.
 * @return string
 */
function lkp_countdown_text( $deadline ) {
	$remaining = max( 0, (int) $deadline - time() );
	$days      = intdiv( $remaining, DAY_IN_SECONDS );
	$remaining = $remaining % DAY_IN_SECONDS;
	$hours     = intdiv( $remaining, HOUR_IN_SECONDS );
	$remaining = $remaining % HOUR_IN_SECONDS;
	$minutes   = intdiv( $remaining, MINUTE_IN_SECONDS );
	$seconds   = $remaining % MINUTE_IN_SECONDS;

	return lkp_fa( $days ) . ' روز ' . lkp_fa( sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds ) );
}
