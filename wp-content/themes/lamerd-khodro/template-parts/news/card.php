<?php
/**
 * Article card.
 *
 * @param array $args { @type WP_Post $post, @type bool $featured }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_post = $args['post'] ?? get_post();
if ( ! $lk_post ) {
	return;
}

$lk_featured = ! empty( $args['featured'] );
$lk_cats     = get_the_category( $lk_post->ID );
$lk_cat      = $lk_cats ? $lk_cats[0] : null;
$lk_thumb    = get_the_post_thumbnail_url( $lk_post->ID, $lk_featured ? 'lk-wide' : 'lk-card' );
?>
<article class="lk-acard <?php echo $lk_featured ? 'lk-acard--featured' : ''; ?>">
	<a class="lk-acard__media" href="<?php echo esc_url( get_permalink( $lk_post ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( $lk_thumb ) : ?>
			<img src="<?php echo esc_url( $lk_thumb ); ?>" alt="" loading="lazy" />
		<?php else : ?>
			<span class="lk-acard__pattern">
				<span class="lk-acard__mono"><?php echo esc_html( lk_monogram( $lk_post->post_title ) ); ?></span>
			</span>
		<?php endif; ?>
		<?php if ( $lk_cat ) : ?>
			<span class="lk-acard__tag"><?php echo esc_html( $lk_cat->name ); ?></span>
		<?php endif; ?>
	</a>

	<div class="lk-acard__body">
		<div class="lk-acard__meta">
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $lk_post ) ); ?>"><?php echo esc_html( lk_jalali( get_post_timestamp( $lk_post ), 'long' ) ); ?></time>
			<span class="lk-acard__dot" aria-hidden="true"></span>
			<span><?php echo esc_html( lk_num( max( 1, (int) ceil( mb_strlen( wp_strip_all_tags( $lk_post->post_content ), 'UTF-8' ) / 1200 ) ) ) ); ?> دقیقه مطالعه</span>
		</div>

		<h3 class="lk-acard__title">
			<a href="<?php echo esc_url( get_permalink( $lk_post ) ); ?>"><?php echo esc_html( get_the_title( $lk_post ) ); ?></a>
		</h3>

		<p class="lk-acard__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $lk_post ), 26, '…' ) ); ?></p>

		<a class="lk-acard__more" href="<?php echo esc_url( get_permalink( $lk_post ) ); ?>">
			ادامه مطلب
			<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</div>
</article>
