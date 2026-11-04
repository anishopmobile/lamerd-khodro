<?php
/**
 * Inline SVG icon set (local, no icon font, no external requests).
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon key.
 * @param string $class Extra CSS classes.
 * @return string
 */
function lk_icon( $name, $class = '' ) {
	$stroke = 'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"';

	$icons = array(
		'search'      => '<circle cx="11" cy="11" r="7"/><path d="M16.5 16.5 21 21"/>',
		'menu'        => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'       => '<path d="m6 6 12 12M18 6 6 18"/>',
		'compare'     => '<path d="M4 8h11M4 8l3-3M4 8l3 3M20 16H9m11 0-3-3m3 3-3 3"/>',
		'arrow-left'  => '<path d="M19 12H5m6-6-6 6 6 6"/>',
		'arrow-up'    => '<path d="M12 19V5m-6 6 6-6 6 6"/>',
		'arrow-down'  => '<path d="M12 5v14m6-6-6 6-6-6"/>',
		'chart'       => '<path d="M4 20V4M4 20h16M8 16l4-5 3 3 4-6"/>',
		'car'         => '<path d="M4 13l1.8-4.6A2 2 0 0 1 7.7 7h8.6a2 2 0 0 1 1.9 1.4L20 13v5h-2.5M4 13v5h2.5M9 18h6"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/><path d="M4 13h16"/>',
		'check'       => '<path d="m5 13 4 4L19 7"/>',
		'filter'      => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'download'    => '<path d="M12 4v10m-4-4 4 4 4-4M4 19h16"/>',
		'external'    => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
		'phone'       => '<path d="M5 4h4l2 5-2.5 1.5a12 12 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A15 15 0 0 1 4 5a1 1 0 0 1 1-1z"/>',
		'mail'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'         => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'       => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
		'megaphone'   => '<path d="M4 10v4a1 1 0 0 0 1 1h2l6 4V5L7 9H5a1 1 0 0 0-1 1z"/><path d="M17 9a4 4 0 0 1 0 6"/>',
		'chevron'     => '<path d="m6 9 6 6 6-6"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'instagram'   => '<rect x="4" y="4" width="16" height="16" rx="4"/><circle cx="12" cy="12" r="3.4"/><path d="M16.6 7.4h.01"/>',
		'telegram'    => '<path d="M21 4 3 11l6 2 2 6 3-4 5 3z"/><path d="m9 13 10-7"/>',
		'whatsapp'    => '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3z"/><path d="M8.8 8.5c.2-.5.4-.5.7-.5h.6c.2 0 .4 0 .6.5l.7 1.7c.1.2 0 .4-.1.6l-.5.6c-.1.1-.2.3-.1.5a6 6 0 0 0 2.7 2.4c.2.1.4 0 .5-.1l.6-.7c.2-.2.4-.2.6-.1l1.7.8c.4.2.5.3.5.5 0 .8-.6 1.7-1.4 1.9-2.6.7-6.7-1.6-8-5.9-.3-.9-.1-1.7.4-2.2z"/>',
		'x'           => '<path d="M4 4l16 16M20 4 4 20"/>',
		'aparat'      => '<rect x="3" y="4" width="18" height="16" rx="4"/><path d="m10 9 5 3-5 3z"/>',
	);

	$path = $icons[ $name ] ?? $icons['car'];

	return '<svg class="lk-icon ' . esc_attr( $class ) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false" ' . $stroke . '>' . $path . '</svg>';
}
