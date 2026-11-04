<?php
/**
 * Home: comparison call to action.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="lk-compare-band">
	<div class="lk-container lk-compare-band__grid">
		<div class="lk-compare-band__content">
			<span class="lk-eyebrow lk-eyebrow--light"><span class="lk-eyebrow__dot" aria-hidden="true"></span> مقایسه هوشمند</span>
			<h2 class="lk-compare-band__title">دو تا چهار خودرو را کنار هم بگذارید.</h2>
			<p class="lk-compare-band__text">قیمت بازار، قیمت کارخانه، مشخصات پیشرانه، ابعاد و امکانات را در یک جدول ببینید و تفاوت‌ها را سریع پیدا کنید.</p>
			<div class="lk-compare-band__actions">
				<a class="lk-btn lk-btn--accent" href="<?php echo esc_url( lk_page_url( 'compare' ) ); ?>">
					شروع مقایسه
					<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a class="lk-btn lk-btn--outline-light" href="<?php echo esc_url( lk_page_url( 'vehicles' ) ); ?>">انتخاب خودرو</a>
			</div>
		</div>

		<div class="lk-compare-band__visual" aria-hidden="true">
			<div class="lk-cmpmock lk-cmpmock--a">
				<span class="lk-cmpmock__mono">LK</span>
				<span class="lk-cmpmock__line"></span>
				<span class="lk-cmpmock__line lk-cmpmock__line--short"></span>
			</div>
			<div class="lk-cmpmock lk-cmpmock--b">
				<span class="lk-cmpmock__mono">LK</span>
				<span class="lk-cmpmock__line"></span>
				<span class="lk-cmpmock__line lk-cmpmock__line--short"></span>
			</div>
			<div class="lk-cmpmock__badge">۴ خودرو · ۳۰ مشخصه</div>
		</div>
	</div>
</section>
