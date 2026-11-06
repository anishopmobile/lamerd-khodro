<?php
/**
 * Seed featured images for the shortcut brands used by the header mega menu.
 *
 * Run from the project root: php tools/lk-seed-vehicle-images.php [--dry-run]
 *
 * Photos come from Wikimedia Commons (free licences, credited in the attachment
 * description) or, when a model has no free photo, from the reference catalog.
 * Images are imported once and reused by every trim that matches the model.
 *
 * @package LamerdKhodro\Tools
 */

require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$dry_run = in_array( '--dry-run', $argv, true );
$agent   = 'LamerdKhodroDev/1.0 (local seeding)';

/**
 * Model definitions: brand slug => [ match regex => source ].
 *
 * Source keys:
 *  wiki: English Wikipedia article title with a lead image.
 *  ref:  Reference catalog product search term.
 *  label: Attachment title.
 */
$models = array(
	'iran-khodro'     => array(
		'/^تارا/u'      => array( 'wiki' => 'IKCO Tara', 'label' => 'تارا' ),
		'/^دنا/u'       => array( 'wiki' => 'IKCO Dena', 'label' => 'دنا' ),
		'/^سورن/u'      => array( 'wiki' => 'IKCO Soren', 'label' => 'سورن' ),
		'/^207/u'       => array( 'wiki' => 'Peugeot 207', 'label' => '۲۰۷' ),
		'/^آریسان/u'    => array( 'wiki' => 'IKCO Arisun', 'label' => 'آریسان' ),
		'/^هایما/u'     => array( 'wiki' => 'Haima S5', 'label' => 'هایما' ),
	),
	'saipa'           => array(
		'/^شاهین/u'     => array( 'wiki' => 'Saipa Shahin', 'label' => 'شاهین' ),
		'/^سهند/u'      => array( 'ref' => 'سهند', 'label' => 'سهند' ),
		'/^کوییک/u'     => array( 'wiki' => 'Saipa Quik', 'label' => 'کوییک' ),
		'/^ساینا/u'     => array( 'wiki' => 'Saipa Saina', 'label' => 'ساینا' ),
		'/^اطلس/u'      => array( 'wiki' => 'Saipa Atlas', 'label' => 'اطلس' ),
		'/^چانگان/u'    => array( 'wiki' => 'Changan CS35', 'label' => 'چانگان' ),
		'/^سیتروئن/u'   => array( 'wiki' => 'Citroën C3', 'label' => 'سیتروئن C3' ),
	),
	'modiran-khodro'  => array(
		'/^تیگو 9/u'    => array( 'wiki' => 'Chery Tiggo 9', 'label' => 'تیگو ۹' ),
		'/^تیگو 7/u'    => array( 'wiki' => 'Chery Tiggo 7', 'label' => 'تیگو ۷' ),
		'/^تیگو 8/u'    => array( 'wiki' => 'Chery Tiggo 8', 'label' => 'تیگو ۸' ),
		'/^آریزو/u'     => array( 'wiki' => 'Chery Arrizo 5', 'label' => 'آریزو' ),
		'/^ایکس 33/u'   => array( 'ref' => 'MVM X33', 'label' => 'MVM X33' ),
		'/^ایکس 55/u'   => array( 'ref' => 'MVM X55', 'label' => 'MVM X55' ),
		'/^ایکس 77/u'   => array( 'ref' => 'MVM X77', 'label' => 'MVM X77' ),
		'/^فونیکس/u'    => array( 'ref' => 'Fownix FX', 'label' => 'فونیکس' ),
	),
	'bahman-motor'    => array(
		'/^دیگنیتی/u'   => array( 'ref' => 'دیگنیتی Prime', 'label' => 'دیگنیتی' ),
		'/^فیدلیتی/u'   => array( 'ref' => 'فیدلیتی XB1', 'label' => 'فیدلیتی' ),
		'/^رسپکت/u'     => array( 'ref' => 'ریسپکت Prime 2', 'label' => 'رسپکت' ),
		'/^ون اینرودز/u' => array( 'ref' => 'اینرودز', 'label' => 'اینرودز' ),
	),
);

/**
 * Run curl and return [ body, http_code, content_type ].
 *
 * @param string $url  Remote URL.
 * @param string $save Optional file path to write the body to.
 * @param string $agent User agent.
 * @return array
 */
function lk_seed_curl( $url, $save = '', $agent = '' ) {
	// escapeshellarg() strips % and ! on Windows, which breaks encoded URLs.
	$quote = static function ( $value ) {
		return '"' . str_replace( '"', '', $value ) . '"';
	};
	$out   = $save ? $quote( $save ) : '-';
	$cmd   = sprintf(
		'curl -s -L --max-time 90 -A %s -o %s -w "%%{http_code}" %s',
		$quote( $agent ),
		$out,
		$quote( $url )
	);
	$code = trim( (string) shell_exec( $cmd ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	return array( 'code' => (int) $code );
}

/**
 * Resize a Wikimedia original to a thumbnail URL.
 *
 * @param string $src Original URL.
 * @param int    $width Thumb width.
 * @return string
 */
function lk_seed_thumb_url( $src, $width = 900 ) {
	$src = preg_replace( '/\?.*$/', '', $src );
	if ( preg_match( '#^https://upload\.wikimedia\.org/wikipedia/[^/]+/(.+)$#', $src, $m ) ) {
		return 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode( basename( $m[1] ) ) . '?width=' . $width;
	}
	return $src;
}

/**
 * Resolve a remote image URL for one source definition.
 *
 * @param array  $source Source definition.
 * @param string $agent  User agent.
 * @return array{url:string,credit:string}|null
 */
function lk_seed_resolve( $source, $agent ) {
	if ( ! empty( $source['wiki'] ) ) {
		$api = 'https://en.wikipedia.org/w/api.php?action=query&format=json&prop=pageimages&piprop=original&redirects=1&titles='
			. rawurlencode( $source['wiki'] );

		for ( $attempt = 1; $attempt <= 4; $attempt++ ) {
			$tmp = wp_tempnam( 'lk-wiki' );
			$res = lk_seed_curl( $api, $tmp, $agent );
			$raw = ( 200 === $res['code'] && file_exists( $tmp ) ) ? file_get_contents( $tmp ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( $tmp && file_exists( $tmp ) ) {
				unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
			$data = json_decode( (string) $raw, true );
			foreach ( (array) ( $data['query']['pages'] ?? array() ) as $page ) {
				if ( ! empty( $page['original']['source'] ) ) {
					return array(
						'url'    => lk_seed_thumb_url( $page['original']['source'] ),
						'alt'    => preg_replace( '/\?.*$/', '', $page['original']['source'] ),
						'credit' => 'ویکی‌پدیا / ویکی‌مدیا کامانز — ' . $page['title'],
					);
				}
			}
			sleep( 2 * $attempt );
		}

		return null;
	}

	if ( ! empty( $source['ref'] ) ) {
		$api = 'https://talashkhodro.com/wp-json/wp/v2/product?per_page=3&_fields=title,featured_media&search='
			. rawurlencode( $source['ref'] );
		$r   = wp_remote_get( $api, array( 'timeout' => 40 ) );
		if ( is_wp_error( $r ) ) {
			return null;
		}
		$rows = json_decode( wp_remote_retrieve_body( $r ), true );
		foreach ( (array) $rows as $row ) {
			if ( empty( $row['featured_media'] ) ) {
				continue;
			}
			$m = wp_remote_get(
				'https://talashkhodro.com/wp-json/wp/v2/media/' . (int) $row['featured_media'],
				array( 'timeout' => 40 )
			);
			if ( is_wp_error( $m ) ) {
				continue;
			}
			$media = json_decode( wp_remote_retrieve_body( $m ), true );
			if ( ! empty( $media['source_url'] ) ) {
				return array(
					'url'    => $media['source_url'],
					'credit' => 'تصویر نمونه از کاتالوگ مرجع',
				);
			}
		}
	}

	return null;
}

/**
 * Download an image and insert it into the media library.
 *
 * @param array  $resolved Remote image info.
 * @param string $label    Attachment title.
 * @param string $agent    User agent.
 * @return int Attachment ID (0 on failure).
 */
function lk_seed_import( $resolved, $label, $agent ) {
	$tmp = wp_tempnam( 'lk-img' );
	$res = lk_seed_curl( $resolved['url'], $tmp, $agent );

	if ( 200 !== $res['code'] || ! file_exists( $tmp ) || filesize( $tmp ) < 4096 ) {
		$resolved['url'] = ! empty( $resolved['alt'] ) ? $resolved['alt'] : $resolved['url'];
		$res = lk_seed_curl( $resolved['url'], $tmp, $agent );
	}

	if ( 200 !== $res['code'] || ! file_exists( $tmp ) || filesize( $tmp ) < 4096 ) {
		if ( $tmp && file_exists( $tmp ) ) {
			unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return 0;
	}

	$ext      = 'jpg';
	$mime_map = array( 'png' => 'png', 'webp' => 'webp', 'jpeg' => 'jpg', 'jpg' => 'jpg' );
	$path     = wp_get_upload_dir()['basedir'] . '/lk-import-' . md5( $resolved['url'] ) . '.jpg';
	if ( ! @copy( $tmp, $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return 0;
	}
	unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	$file = array(
		'name'     => sanitize_file_name( 'lk-' . md5( $resolved['url'] ) . '.' . $ext ),
		'tmp_name' => $path,
	);

	$id = media_handle_sideload( $file, 0, $label . ' — نمونه' );
	if ( is_wp_error( $id ) ) {
		@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return 0;
	}

	wp_update_post(
		array(
			'ID'           => $id,
			'post_title'   => $label,
			'post_content' => $resolved['credit'] . ' — ' . $resolved['url'],
		)
	);

	return (int) $id;
}

$report = array();
$total  = 0;

foreach ( $models as $brand_slug => $rules ) {
	$term = get_term_by( 'slug', $brand_slug, 'lk_brand' );
	if ( ! $term ) {
		$report[] = "برند پیدا نشد: $brand_slug";
		continue;
	}

	$vehicles = get_posts(
		array(
			'post_type'      => 'lk_vehicle',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'lk_brand',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		)
	);

	foreach ( $rules as $pattern => $source ) {
		$matches = array();
		foreach ( $vehicles as $vehicle_id ) {
			if ( ! get_post_thumbnail_id( $vehicle_id ) && preg_match( $pattern, get_the_title( $vehicle_id ) ) ) {
				$matches[] = $vehicle_id;
			}
		}
		if ( ! $matches ) {
			continue;
		}

		$key    = 'lk_seed_' . md5( wp_json_encode( $source ) );
		$cached = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_lk_seed_key', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		if ( $cached ) {
			$attachment_id = (int) $cached[0];
		} else {
			$resolved = lk_seed_resolve( $source, $agent );
			if ( ! $resolved ) {
				$report[] = "تصویر نشد: {$source['label']}";
				continue;
			}
			if ( $dry_run ) {
				$report[] = "[dry] {$source['label']} ← {$resolved['url']}";
				continue;
			}
			$attachment_id = lk_seed_import( $resolved, $source['label'], $agent );
			if ( ! $attachment_id ) {
				$report[] = "دانلود نشد: {$source['label']}";
				continue;
			}
			update_post_meta( $attachment_id, '_lk_seed_key', $key );
			$report[] = "افزودن تصویر: {$source['label']}";
		}

		if ( ! $dry_run ) {
			foreach ( $matches as $vehicle_id ) {
				set_post_thumbnail( $vehicle_id, $attachment_id );
				$total++;
			}
		}
	}
}

echo implode( "
", $report ) . "
";
echo "attached: $total
";
