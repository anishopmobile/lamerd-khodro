<?php
/**
 * Home hero with live market pulse.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_stats  = function_exists( 'lk_get_stats' ) ? lk_get_stats() : array();
$lk_movers = function_exists( 'lk_get_top_movers' ) ? lk_get_top_movers( 'up', 3 ) : array();
$lk_last   = function_exists( 'lk_get_last_sync' ) ? lk_get_last_sync() : null;
$lk_cta    = lk_opt( 'hero_cta_url', '' );
$lk_cta    = $lk_cta ? $lk_cta : lk_page_url( 'prices' );
$lk_title  = lk_opt( 'hero_title', 'قیمت بازار را شفاف ببینید.' );
?>
<section class="lk-hero">
	<div class="lk-hero__glow" aria-hidden="true"></div>
	<div class="lk-container lk-hero__grid">
		<div class="lk-hero__content">
			<p class="lk-eyebrow">
				<span class="lk-eyebrow__dot" aria-hidden="true"></span>
				<?php echo esc_html( lk_opt( 'hero_eyebrow', 'مرجع قیمت و اطلاعات خودرو' ) ); ?>
			</p>

			<h1 class="lk-hero__title"><?php echo esc_html( $lk_title ); ?></h1>

			<p class="lk-hero__lead"><?php echo esc_html( lk_opt( 'hero_text' ) ); ?></p>

			<div class="lk-hero__searchwrap" id="lk-hero-searchwrap" data-lk-hsearch>
				<form class="lk-hero__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="lk-hero-search">جستجوی خودرو</label>
					<div class="lk-hero__field">
						<?php echo lk_icon( 'search', 'lk-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<input
							type="search"
							id="lk-hero-search"
							name="s"
							placeholder="نام خودرو یا برند را جستجو کنید"
							autocomplete="off"
							role="combobox"
							aria-autocomplete="list"
							aria-expanded="false"
							aria-controls="lk-hero-results"
							data-lk-search-input
						/>
						<button type="submit" class="lk-btn lk-btn--accent">جستجو</button>
					</div>
				</form>
				<div class="lk-hero__panel" id="lk-hero-results" role="listbox" aria-label="نتایج جستجو" data-lk-search-results hidden></div>
			</div>

			<ul class="lk-hero__chips">
				<li><a href="<?php echo esc_url( lk_page_url( 'prices' ) ); ?>">قیمت روز خودرو</a></li>
				<li><a href="<?php echo esc_url( lk_page_url( 'vehicles' ) ); ?>">همه خودروها</a></li>
				<li><a href="<?php echo esc_url( lk_page_url( 'compare' ) ); ?>">مقایسه</a></li>
				<li><a href="<?php echo esc_url( lk_page_url( 'brands' ) ); ?>">برندها</a></li>
			</ul>

			<p class="lk-hero__note">
				<?php echo lk_icon( 'check', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( lk_opt( 'hero_note' ) ); ?>
			</p>
		</div>

		<aside class="lk-pulse" aria-label="نبض بازار خودرو">
			<header class="lk-pulse__head">
				<div>
					<span class="lk-pulse__label">نبض بازار</span>
					<span class="lk-pulse__sub">وضعیت قیمت‌های ثبت‌شده</span>
				</div>
				<span class="lk-pulse__live">
					<span class="lk-pulse__live-dot" aria-hidden="true"></span>
					<?php echo $lk_last ? 'دریافت موفق' : 'در انتظار دریافت'; ?>
				</span>
			</header>

			<?php if ( ! empty( $lk_stats['with_price'] ) ) : ?>
				<div class="lk-pulse__stats">
					<div class="lk-stat">
						<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['with_price'] ) ); ?></span>
						<span class="lk-stat__label">خودرو با قیمت</span>
					</div>
					<div class="lk-stat lk-stat--up">
						<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['up'] ) ); ?></span>
						<span class="lk-stat__label">افزایش</span>
					</div>
					<div class="lk-stat lk-stat--down">
						<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['down'] ) ); ?></span>
						<span class="lk-stat__label">کاهش</span>
					</div>
					<div class="lk-stat">
						<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['flat'] ) ); ?></span>
						<span class="lk-stat__label">بدون تغییر</span>
					</div>
				</div>

				<?php if ( $lk_movers ) : ?>
					<div class="lk-pulse__movers">
						<span class="lk-pulse__movers-title">بیشترین افزایش امروز</span>
						<?php foreach ( $lk_movers as $lk_mover ) : ?>
							<a class="lk-pulse__row" href="<?php echo esc_url( $lk_mover['url'] ); ?>">
								<span class="lk-pulse__name"><?php echo esc_html( $lk_mover['title'] ); ?></span>
								<span class="lk-pulse__price"><?php echo esc_html( LK_Format::price_short( $lk_mover['market_price'] ) ); ?></span>
								<span class="lk-change is-up"><?php echo esc_html( LK_Format::change_percent( $lk_mover['change_percent'] ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			<?php else : ?>
				<div class="lk-pulse__empty">
					<p>هنوز قیمتی در دیتابیس ثبت نشده است. پس از نخستین دریافت موفق، جدول قیمت و آمار بازار در همین صفحه نمایش داده می‌شود.</p>
				</div>
			<?php endif; ?>

			<footer class="lk-pulse__foot">
				<a class="lk-btn lk-btn--primary lk-btn--block" href="<?php echo esc_url( $lk_cta ); ?>">
					<?php echo esc_html( lk_opt( 'hero_cta_label', 'مشاهده قیمت روز' ) ); ?>
					<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<?php if ( function_exists( 'lk_get_last_sync' ) && $lk_last ) : ?>
					<p class="lk-pulse__updated">
						آخرین دریافت: <?php echo esc_html( lk_jalali( $lk_last['finished_ts'], 'datetime' ) ); ?>
					</p>
				<?php endif; ?>
			</footer>
		</aside>
	</div>
</section>
