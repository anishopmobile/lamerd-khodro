<?php
/**
 * Article helpers: excerpt policy, reading time, view counting, popular list.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta key that stores the article view counter.
 */
const LK_NEWS_VIEWS_META = '_lk_views';

/**
 * Word budget for a card intro. Longer intros are trimmed and get a link.
 */
const LK_NEWS_EXCERPT_WORDS = 22;

/**
 * Reading time from the article body, in minutes.
 *
 * @param WP_Post|int|null $post Post.
 * @return int
 */
function lk_news_reading_time( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}

	$chars = mb_strlen( wp_strip_all_tags( (string) $post->post_content ), 'UTF-8' );

	return max( 1, (int) ceil( $chars / 1200 ) );
}

/**
 * Normalise an intro: plain text, no generated ellipsis markers, single spaces.
 *
 * @param string $text Raw intro.
 * @return string
 */
function lk_news_clean_intro( $text ) {
	$text = wp_strip_all_tags( (string) $text );
	$text = str_replace( array( '[…]', '[&hellip;]', '[&#8230;]', '&hellip;' ), '', $text );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	$text = rtrim( $text, "… \t\n\r\0\x0B" );

	return $text;
}

/**
 * Card intro with an automatic cut-off.
 *
 * @param WP_Post|int|null $post  Post.
 * @param int              $words Word budget.
 * @return array{text:string,truncated:bool}
 */
function lk_news_excerpt( $post = null, $words = LK_NEWS_EXCERPT_WORDS ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array(
			'text'      => '',
			'truncated' => false,
		);
	}

	$raw = lk_news_clean_intro( get_the_excerpt( $post ) );

	if ( '' === $raw ) {
		$raw = lk_news_clean_intro( wp_trim_words( (string) $post->post_content, 80, '' ) );
	}

	if ( '' === $raw ) {
		return array(
			'text'      => '',
			'truncated' => false,
		);
	}

	$all   = preg_split( '/\s+/u', $raw );
	$total = is_array( $all ) ? count( $all ) : 0;

	if ( $total <= $words ) {
		return array(
			'text'      => $raw,
			'truncated' => false,
		);
	}

	return array(
		'text'      => implode( ' ', array_slice( $all, 0, $words ) ) . '…',
		'truncated' => true,
	);
}

/**
 * Stored view count for an article.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function lk_news_views( $post_id ) {
	return max( 0, (int) get_post_meta( (int) $post_id, LK_NEWS_VIEWS_META, true ) );
}

/**
 * Count one view of a published article.
 *
 * @return void
 */
function lk_track_news_view() {
	if ( is_admin() || wp_doing_ajax() || ! is_singular( 'post' ) ) {
		return;
	}

	$post_id = (int) get_queried_object_id();
	if ( $post_id <= 0 ) {
		return;
	}

	global $wpdb;

	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s",
			$post_id,
			LK_NEWS_VIEWS_META
		)
	);

	if ( ! $updated ) {
		add_post_meta( $post_id, LK_NEWS_VIEWS_META, 1, true );
	}
}
add_action( 'template_redirect', 'lk_track_news_view' );

/**
 * Articles ordered by views, topped up with the newest ones.
 *
 * @param int   $limit   How many.
 * @param int[] $exclude Post IDs to skip.
 * @return WP_Post[]
 */
function lk_popular_posts( $limit = 5, $exclude = array() ) {
	$limit   = max( 1, (int) $limit );
	$exclude = array_values( array_filter( array_map( 'absint', (array) $exclude ) ) );

	$base = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post__not_in'        => $exclude,
	);

	$popular = new WP_Query(
		$base + array(
			'meta_key' => LK_NEWS_VIEWS_META,
			'orderby'  => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
		)
	);

	$posts = $popular->posts;
	$ids   = wp_list_pluck( $posts, 'ID' );

	if ( count( $posts ) < $limit ) {
		$fill = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $limit - count( $posts ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'post__not_in'        => array_merge( $exclude, $ids ),
			)
		);
		$posts = array_merge( $posts, $fill->posts );
	}

	return $posts;
}
