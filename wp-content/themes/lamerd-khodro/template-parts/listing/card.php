<?php
/**
 * Sale listing card.
 *
 * @param array $args { @type WP_Post $post }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_post = $args['post'] ?? get_post();
if ( ! $lk_post ) {
	return;
}

$lk_id       = (int) $lk_post->ID;
$lk_price    = (int) lk_listing_field( $lk_id, 'price', 0 );
$lk_year     = (int) lk_listing_field( $lk_id, 'year', 0 );
$lk_km       = (int) lk_listing_field( $lk_id, 'km', 0 );
$lk_gearbox  = (string) lk_listing_field( $lk_id, 'gearbox', '' );
$lk_city     = (string) lk_listing_field( $lk_id, 'city', '' );
$lk_condition = (string) lk_listing_field( $lk_id, 'condition', 'used' );
$lk_featured = lk_listing_is_featured( $lk_id );
$lk_thumb    = get_the_post_thumbnail_url( $lk_id, 'lk-card' );
$lk_url      = get_permalink( $lk_id );
?>
<article class="lk-lcard<?php echo $lk_featured ? ' is-featured' : ''; ?>">
	<a class="lk-lcard__media" href="<?php echo esc_url( $lk_url ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( $lk_thumb ) : ?>
			<img src="<?php echo esc_url( $lk_thumb ); ?>" alt="" loading="lazy" width="380" height="230" />
		<?php else : ?>
			<span class="lk-lcard__placeholder">
				<?php echo lk_icon( 'car', 'lk-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span>تصویر ثبت نشده</span>
			</span>
		<?php endif; ?>

		<?php if ( $lk_featured ) : ?>
			<span class="lk-lcard__ribbon">فروش ویژه</span>
		<?php endif; ?>
	</a>

	<div class="lk-lcard__body">
		<div class="lk-lcard__top">
			<?php if ( $lk_city ) : ?>
				<span class="lk-lcard__city"><?php echo lk_icon( 'pin', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( lk_listing_label( 'cities', $lk_city ) ); ?></span>
			<?php endif; ?>
			<span class="lk-status <?php echo 'new' === $lk_condition ? 'is-active' : 'is-stopped'; ?>"><?php echo esc_html( lk_listing_label( 'conditions', $lk_condition ) ); ?></span>
		</div>

		<h3 class="lk-lcard__title">
			<a href="<?php echo esc_url( $lk_url ); ?>"><?php echo esc_html( $lk_post->post_title ); ?></a>
		</h3>

		<ul class="lk-lcard__specs">
			<?php if ( $lk_year ) : ?>
				<li><?php echo esc_html( LK_Format::digits( $lk_year ) ); ?></li>
			<?php endif; ?>
			<li><?php echo $lk_km ? esc_html( LK_Format::number( $lk_km ) . ' کیلومتر' ) : 'کارکرد ثبت نشده'; ?></li>
			<?php if ( $lk_gearbox ) : ?>
				<li><?php echo esc_html( lk_listing_label( 'gearboxes', $lk_gearbox ) ); ?></li>
			<?php endif; ?>
		</ul>

		<div class="lk-lcard__foot">
			<span class="lk-lcard__price">
				<?php if ( $lk_price > 0 ) : ?>
					<?php echo lk_money_short( $lk_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<span class="lk-money lk-money--empty">قیمت توافقی</span>
				<?php endif; ?>
			</span>

			<?php if ( $lk_featured ) : ?>
				<span class="lk-timer lk-timer--chip" data-lk-countdown data-until="<?php echo esc_attr( lk_listing_featured_until( $lk_id ) ); ?>">
					<?php echo lk_icon( 'clock', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span data-lk-countdown-text><?php echo esc_html( lk_listing_time_left( $lk_id ) ); ?></span>
				</span>
			<?php endif; ?>
		</div>
	</div>
</article>
