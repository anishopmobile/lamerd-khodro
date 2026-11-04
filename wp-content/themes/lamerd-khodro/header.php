<?php
/**
 * Site header.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="lk-skip" href="#lk-main">رفتن به محتوای اصلی</a>

<div class="lk-page">

	<div class="lk-topbar">
		<div class="lk-container lk-topbar__inner">
			<div class="lk-topbar__start">
				<span class="lk-topbar__date"><?php echo esc_html( function_exists( 'lk_jalali' ) ? lk_jalali( null, 'date' ) : '' ); ?></span>
				<span class="lk-topbar__sep" aria-hidden="true"></span>
				<?php if ( function_exists( 'lk_get_last_sync' ) && lk_get_last_sync() ) : ?>
					<?php lk_update_note(); ?>
				<?php else : ?>
					<span class="lk-topbar__note">مرجع قیمت و اطلاعات خودرو</span>
				<?php endif; ?>
			</div>
			<div class="lk-topbar__end">
				<a class="lk-topbar__link" href="<?php echo esc_url( lk_page_url( 'about' ) ); ?>">درباره ما</a>
				<a class="lk-topbar__link" href="<?php echo esc_url( lk_page_url( 'contact' ) ); ?>">تماس با ما</a>
				<a class="lk-topbar__link lk-topbar__link--accent" href="<?php echo esc_url( lk_page_url( 'compare' ) ); ?>">
					<?php echo lk_icon( 'compare', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					مقایسه خودرو
				</a>
			</div>
		</div>
	</div>

	<header class="lk-header" id="lk-header">
		<div class="lk-container lk-header__inner">
			<a class="lk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<span class="lk-logo__mark" aria-hidden="true">
					<svg viewBox="0 0 48 48" role="img" focusable="false">
						<rect width="48" height="48" rx="13" fill="currentColor" />
						<path d="M11 33 22.5 14 34 33" fill="none" stroke="#F26522" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
						<path d="M18.5 33 24 23.5 29.5 33" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
				</span>
				<span class="lk-logo__text">
					<span class="lk-logo__name">لامرد خودرو</span>
					<span class="lk-logo__tag">مرجع قیمت و اطلاعات خودرو</span>
				</span>
			</a>

			<nav class="lk-nav" id="lk-nav" aria-label="منوی اصلی">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'lk-nav__list',
						'container'      => false,
						'fallback_cb'    => 'lk_theme_menu_fallback',
						'depth'          => 2,
					)
				);
				?>
			</nav>

			<div class="lk-header__actions">
				<button type="button" class="lk-iconbtn" id="lk-search-toggle" aria-expanded="false" aria-controls="lk-search-panel" aria-label="جستجو">
					<?php echo lk_icon( 'search', 'lk-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>

				<a href="<?php echo esc_url( lk_page_url( 'compare' ) ); ?>" class="lk-compare-btn" aria-label="فهرست مقایسه">
					<?php echo lk_icon( 'compare' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="lk-compare-btn__label">مقایسه</span>
					<span class="lk-compare-btn__count" data-lk-compare-count hidden>۰</span>
				</a>

				<a href="<?php echo esc_url( lk_page_url( 'prices' ) ); ?>" class="lk-btn lk-btn--primary lk-header__cta">قیمت روز</a>

				<button type="button" class="lk-iconbtn lk-menu-toggle" id="lk-menu-toggle" aria-expanded="false" aria-controls="lk-drawer" aria-label="منو">
					<?php echo lk_icon( 'menu', 'lk-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
		</div>
	</header>

	<div class="lk-search-panel" id="lk-search-panel" hidden>
		<div class="lk-container">
			<form class="lk-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="lk-search-form__label" for="lk-search-input">جستجوی خودرو، برند یا مقاله</label>
				<div class="lk-search-form__field">
					<?php echo lk_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<input type="search" id="lk-search-input" name="s" placeholder="مثلاً: تویوتا لندکروز" autocomplete="off" data-lk-search-input />
					<button type="submit" class="lk-btn lk-btn--primary">جستجو</button>
				</div>
			</form>
			<div class="lk-search-results" data-lk-search-results aria-live="polite"></div>
			<div class="lk-search-quick">
				<span class="lk-search-quick__label">دسترسی سریع:</span>
				<a href="<?php echo esc_url( lk_page_url( 'prices' ) ); ?>">قیمت روز خودرو</a>
				<a href="<?php echo esc_url( lk_page_url( 'brands' ) ); ?>">برندها</a>
				<a href="<?php echo esc_url( lk_page_url( 'compare' ) ); ?>">مقایسه خودرو</a>
				<a href="<?php echo esc_url( lk_blog_url() ); ?>">اخبار و مقالات</a>
			</div>
		</div>
	</div>

	<div class="lk-drawer" id="lk-drawer" hidden>
		<div class="lk-drawer__backdrop" data-lk-close-drawer></div>
		<div class="lk-drawer__panel" role="dialog" aria-modal="true" aria-label="منوی موبایل">
			<div class="lk-drawer__head">
				<span class="lk-drawer__title">منو</span>
				<button type="button" class="lk-iconbtn" data-lk-close-drawer aria-label="بستن منو">
					<?php echo lk_icon( 'close', 'lk-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>

			<nav class="lk-drawer__nav" aria-label="منوی موبایل">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'lk-drawer__list',
						'container'      => false,
						'fallback_cb'    => 'lk_theme_menu_fallback',
						'depth'          => 2,
					)
				);
				?>
			</nav>

			<div class="lk-drawer__foot">
				<a class="lk-btn lk-btn--primary lk-btn--block" href="<?php echo esc_url( lk_page_url( 'compare' ) ); ?>">
					<?php echo lk_icon( 'compare' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					مقایسه خودروها
				</a>
				<?php $socials = lk_social_links(); ?>
				<?php if ( $socials ) : ?>
					<ul class="lk-socials lk-socials--inline">
						<?php foreach ( $socials as $key => $social ) : ?>
							<li>
								<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social['label'] ); ?>">
									<?php echo lk_icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<main class="lk-main" id="lk-main">
