<?php
/**
 * Article card.
 *
 * Every card is the same size: one cover ratio, one title clamp and one
 * intro budget, so the grid never turns into a patchwork.
 *
 * @param array $args { @type WP_Post $post }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_post = $args['post'] ?? get_post();
if ( ! $lk_post ) {
	return;
}

$lk_cats  = get_the_category( $lk_post->ID );
$lk_cat   = $lk_cats ? $lk_cats[0] : null;
$lk_intro = lk_news_excerpt( $lk_post );
$lk_url   = get_permalink( $lk_post );
?>
<article class="lk-acard">
	<a class="lk-acard__media" href="<?php echo esc_url( $lk_url ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail( $lk_post ) ) : ?>
			<?php
			echo get_the_post_thumbnail(
				$lk_post,
				'lk-card',
				array(
					'loading' => 'lazy',
					'alt'     => '',
				)
			);
			?>
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
			<span><?php echo esc_html( lk_num( lk_news_reading_time( $lk_post ) ) ); ?> دقیقه مطالعه</span>
		</div>

		<h3 class="lk-acard__title">
			<a href="<?php echo esc_url( $lk_url ); ?>"><?php echo esc_html( get_the_title( $lk_post ) ); ?></a>
		</h3>

		<?php if ( '' !== $lk_intro['text'] ) : ?>
			<p class="lk-acard__excerpt"><?php echo esc_html( $lk_intro['text'] ); ?></p>
		<?php endif; ?>

		<a class="lk-acard__more" href="<?php echo esc_url( $lk_url ); ?>">
			<?php echo $lk_intro['truncated'] ? 'ادامه مطلب' : 'مشاهده مطلب'; ?>
			<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</div>
</article>
