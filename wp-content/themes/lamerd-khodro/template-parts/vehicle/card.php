<?php
/**
 * Vehicle card.
 *
 * @param array $args {
 *     @type WP_Post $post  Vehicle post.
 *     @type int     $index Optional index number.
 * }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_post = $args['post'] ?? get_post();
if ( ! $lk_post ) {
	return;
}

$lk_post_id = (int) $lk_post->ID;
$lk_price   = isset( $lk_post->lk_price ) ? $lk_post->lk_price : ( function_exists( 'lk_get_price' ) ? lk_get_price( $lk_post_id ) : null );
$lk_brand   = function_exists( 'lk_vehicle_brand' ) ? lk_vehicle_brand( $lk_post_id ) : null;
$lk_status  = function_exists( 'lk_production_status' ) ? lk_production_status( $lk_post_id ) : '';
$lk_thumb   = get_the_post_thumbnail_url( $lk_post_id, 'lk-card' );
$lk_index   = $args['index'] ?? 0;
?>
<article class="lk-vcard">
	<a class="lk-vcard__media" href="<?php echo esc_url( get_permalink( $lk_post_id ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( $lk_thumb ) : ?>
			<img src="<?php echo esc_url( $lk_thumb ); ?>" alt="" loading="lazy" width="380" height="230" />
		<?php else : ?>
			<span class="lk-vcard__plate">
				<span class="lk-vcard__plate-icon"><?php echo lk_icon( 'car' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php if ( $lk_brand ) : ?>
					<span class="lk-vcard__plate-brand"><?php echo esc_html( $lk_brand->name ); ?></span>
				<?php endif; ?>
				<span class="lk-vcard__plate-note">تصویر ثبت نشده</span>
			</span>
		<?php endif; ?>

		<?php if ( $lk_index ) : ?>
			<span class="lk-vcard__index"><?php echo esc_html( lk_num( $lk_index ) ); ?></span>
		<?php endif; ?>
	</a>

	<div class="lk-vcard__body">
		<div class="lk-vcard__top">
			<?php if ( $lk_brand ) : ?>
				<a class="lk-vcard__brand" href="<?php echo esc_url( get_term_link( $lk_brand ) ); ?>"><?php echo esc_html( $lk_brand->name ); ?></a>
			<?php endif; ?>
			<?php if ( $lk_status ) : ?>
				<span class="lk-status <?php echo false !== mb_strpos( $lk_status, 'متوقف' ) ? 'is-stopped' : 'is-active'; ?>"><?php echo esc_html( $lk_status ); ?></span>
			<?php endif; ?>
		</div>

		<h3 class="lk-vcard__title">
			<a href="<?php echo esc_url( get_permalink( $lk_post_id ) ); ?>"><?php echo esc_html( $lk_post->post_title ); ?></a>
		</h3>

		<div class="lk-vcard__prices">
			<span class="lk-vcard__price">
				<span class="lk-vcard__price-label">بازار</span>
				<?php echo lk_money_short( $lk_price['market_price'] ?? null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
			<?php if ( ! empty( $lk_price['factory_price'] ) ) : ?>
				<span class="lk-vcard__factory">کارخانه: <?php echo esc_html( LK_Format::price_short( $lk_price['factory_price'] ) ); ?></span>
			<?php endif; ?>
		</div>

		<div class="lk-vcard__foot">
			<?php if ( $lk_price ) : ?>
				<?php echo lk_change_badge( $lk_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<span class="lk-change lk-change--empty">قیمت ثبت نشده</span>
			<?php endif; ?>

			<button type="button" class="lk-chipbtn" data-lk-compare-id="<?php echo esc_attr( $lk_post_id ); ?>" data-lk-compare-title="<?php echo esc_attr( $lk_post->post_title ); ?>">
				<?php echo lk_icon( 'plus', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				مقایسه
			</button>
		</div>
	</div>
</article>
