<?php
/**
 * Price panel for a vehicle page.
 *
 * @param array $args { @type int $post_id }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_post_id = (int) ( $args['post_id'] ?? get_the_ID() );
$lk_price   = function_exists( 'lk_get_price' ) ? lk_get_price( $lk_post_id ) : null;
$lk_status  = $lk_price['data_status'] ?? '';
?>
<aside class="lk-pricecard" aria-label="قیمت خودرو">
	<?php if ( $lk_price && null !== $lk_price['market_price'] ) : ?>
		<div class="lk-pricecard__head">
			<span class="lk-pricecard__label">قیمت بازار</span>
			<?php if ( 'suspect' === $lk_status ) : ?>
				<span class="lk-status is-suspect">نیازمند بررسی</span>
			<?php elseif ( ! empty( $lk_price['is_stale'] ) ) : ?>
				<span class="lk-status is-stale">قیمت قدیمی</span>
			<?php else : ?>
				<span class="lk-status is-active">به‌روز</span>
			<?php endif; ?>
		</div>

		<p class="lk-pricecard__market">
			<?php echo esc_html( LK_Format::price( $lk_price['market_price'] ) ); ?>
			<span class="lk-pricecard__unit">تومان</span>
		</p>

		<div class="lk-pricecard__change">
			<?php echo lk_change_badge( $lk_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="lk-pricecard__change-label">نسبت به دریافت قبلی</span>
		</div>

		<dl class="lk-pricecard__rows">
			<div>
				<dt>قیمت کارخانه</dt>
				<dd><?php echo null !== $lk_price['factory_price'] ? esc_html( LK_Format::price( $lk_price['factory_price'] ) ) . ' تومان' : '<span class="lk-muted">ناموجود</span>'; ?></dd>
			</div>
			<div>
				<dt>اختلاف با کارخانه</dt>
				<dd>
					<?php
					if ( null !== $lk_price['factory_price'] ) {
						$lk_gap = (int) $lk_price['market_price'] - (int) $lk_price['factory_price'];
						echo esc_html( LK_Format::price( $lk_gap ) ) . ' تومان';
					} else {
						echo '<span class="lk-muted">—</span>';
					}
					?>
				</dd>
			</div>
			<div>
				<dt>آخرین دریافت</dt>
				<dd><?php echo ! empty( $lk_price['fetched_ts'] ) ? esc_html( lk_jalali( $lk_price['fetched_ts'], 'datetime' ) ) : '—'; ?></dd>
			</div>
			<div>
				<dt>منبع</dt>
				<dd><?php echo wp_kses_post( lk_source_link() ); ?></dd>
			</div>
		</dl>

		<div class="lk-pricecard__actions">
			<button type="button" class="lk-btn lk-btn--primary lk-btn--block" data-lk-compare-id="<?php echo esc_attr( $lk_post_id ); ?>" data-lk-compare-title="<?php echo esc_attr( get_the_title( $lk_post_id ) ); ?>">
				<?php echo lk_icon( 'compare', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				افزودن به مقایسه
			</button>
			<a class="lk-textlink" href="#price-history">
				مشاهده روند قیمت
				<?php echo lk_icon( 'arrow-down', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	<?php else : ?>
		<div class="lk-pricecard__head">
			<span class="lk-pricecard__label">قیمت بازار</span>
			<span class="lk-status is-stale">ثبت نشده</span>
		</div>
		<p class="lk-pricecard__missing">برای این خودرو قیمت معتبری در دیتابیس ثبت نشده است. تا زمان دریافت داده از منبع، هیچ قیمتی نمایش داده نمی‌شود.</p>
		<div class="lk-pricecard__actions">
			<button type="button" class="lk-btn lk-btn--ghost lk-btn--block" data-lk-compare-id="<?php echo esc_attr( $lk_post_id ); ?>" data-lk-compare-title="<?php echo esc_attr( get_the_title( $lk_post_id ) ); ?>">
				<?php echo lk_icon( 'compare', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				افزودن به مقایسه
			</button>
		</div>
	<?php endif; ?>
</aside>
