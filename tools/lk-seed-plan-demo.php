<?php
/**
 * Create one demo registration plan so the section can be reviewed.
 *
 * Usage: php tools/lk-seed-plan-demo.php [--dry-run] [--delete]
 */

require dirname( __DIR__ ) . '/wp-load.php';

$dry    = in_array( '--dry-run', $argv, true );
$delete = in_array( '--delete', $argv, true );

$existing = get_posts(
	array(
		'post_type'      => 'lk_plan',
		'post_status'    => 'any',
		'posts_per_page' => 5,
		'meta_key'       => '_lkp_tracking_demo',
		'meta_value'     => '1',
	)
);

if ( $delete ) {
	foreach ( $existing as $post ) {
		if ( $dry ) {
			echo "would delete #{$post->ID} {$post->post_title}\n";
			continue;
		}
		wp_delete_post( $post->ID, true );
		echo "deleted #{$post->ID}\n";
	}
	exit( 0 );
}

if ( $existing ) {
	echo "demo plan already exists (#{$existing[0]->ID})\n";
	exit( 0 );
}

// Use a real image from the media library when available.
$image_id = 0;
$images   = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image/jpeg',
		'posts_per_page' => 1,
		'orderby'        => 'ID',
		'order'          => 'DESC',
	)
);
if ( $images ) {
	$image_id = (int) $images[0]->ID;
}

$active_ts = LKP_Jalali::to_timestamp( '1405/08/10', '09:00' );
$start_ts  = LKP_Jalali::to_timestamp( '1405/08/15', '00:00' );
$end_ts    = LKP_Jalali::to_timestamp( '1405/09/05', '23:59' );

if ( $dry ) {
	echo "would create demo plan (image #{$image_id}, {$start_ts}..{$end_ts})\n";
	exit( 0 );
}

$post_id = wp_insert_post(
	array(
		'post_type'    => 'lk_plan',
		'post_status'  => 'publish',
		'post_title'   => 'تیگو ۹',
		'post_content' => 'ثبت‌نام طرح فروش فوری و پیش‌فروش؛ شرایط و مدارک لازم در بخشنامهٔ طرح اعلام می‌شود.',
	),
	true
);

if ( is_wp_error( $post_id ) ) {
	echo 'error: ' . $post_id->get_error_message() . "\n";
	exit( 1 );
}

update_post_meta( $post_id, '_lkp_company', 'اکستریم' );
update_post_meta( $post_id, '_lkp_active', '1' );
update_post_meta( $post_id, '_lkp_active_ts', $active_ts );
update_post_meta( $post_id, '_lkp_start_ts', $start_ts );
update_post_meta( $post_id, '_lkp_end_ts', $end_ts );
if ( $image_id ) {
	update_post_meta( $post_id, '_lkp_images', array( $image_id ) );
}
update_post_meta( $post_id, '_lkp_tracking_demo', '1' );

echo "created plan #{$post_id}\n";
