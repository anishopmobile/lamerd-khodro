<?php
/**
 * Site header: utility bar, search row and brand mega bar.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_mega_brands = function_exists( 'lk_mega_brands' ) ? lk_mega_brands( 4 ) : array();
$lk_mega_all    = function_exists( 'lk_mega_all_brands' ) ? lk_mega_all_brands() : array();
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

	<div class="lk-head" id="lk-head">

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

				<div class="lk-hsearch" id="lk-hsearch" data-lk-hsearch>
					<form class="lk-hsearch__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php echo lk_icon( 'search', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<label class="screen-reader-text" for="lk-search-input">جستجوی خودرو، برند یا آگهی</label>
						<input
							type="search"
							id="lk-search-input"
							name="s"
							placeholder="جستجوی خودرو، برند یا آگهی…"
							autocomplete="off"
							role="combobox"
							aria-autocomplete="list"
							aria-expanded="false"
							aria-controls="lk-search-results"
							data-lk-search-input
						/>
						<button type="submit" class="lk-hsearch__submit" aria-label="جستجو">
							<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</button>
					</form>
					<div class="lk-hsearch__panel" id="lk-search-results" role="listbox" aria-label="نتایج جستجو" data-lk-search-results hidden></div>
				</div>

				<div class="lk-header__actions">
					<button type="button" class="lk-iconbtn lk-hsearch-toggle" data-lk-search-toggle aria-controls="lk-hsearch" aria-expanded="false" aria-label="جستجو">
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

		<div class="lk-brandbar" data-lk-mega>
			<div class="lk-container lk-brandbar__inner">
				<nav class="lk-brandbar__nav" aria-label="منوی اصلی">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'menu_class'     => 'lk-nav__list',
							'container'      => false,
							'fallback_cb'    => 'lk_theme_menu_fallback',
							'depth'          => 1,
						)
					);
					?>
				</nav>

				<?php if ( $lk_mega_brands ) : ?>
					<div class="lk-brandbar__brands">
						<span class="lk-brandbar__label">برندهای خودرو</span>
						<div class="lk-brandbar__popular">
							<?php foreach ( $lk_mega_brands as $lk_brand ) : ?>
								<?php
								$lk_shortcut = get_term_link( $lk_brand['term'] );
								$lk_shortcut = is_wp_error( $lk_shortcut ) ? lk_page_url( 'brands' ) : $lk_shortcut;
								?>
								<a
									class="lk-brandbar__brand"
									href="<?php echo esc_url( $lk_shortcut ); ?>"
									data-lk-mega-trigger="<?php echo esc_attr( $lk_brand['term']->slug ); ?>"
									aria-expanded="false"
									aria-controls="lk-mega-panel-<?php echo esc_attr( $lk_brand['term']->slug ); ?>"
								>
									<?php echo esc_html( $lk_brand['term']->name ); ?>
									<?php echo lk_icon( 'chevron', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							<?php endforeach; ?>
						</div>
						<a
							class="lk-brandbar__brand lk-brandbar__brand--all"
							href="<?php echo esc_url( lk_page_url( 'brands' ) ); ?>"
							data-lk-mega-trigger="all"
							aria-expanded="false"
							aria-controls="lk-mega-panel-all"
						>
							همه برندها
							<?php echo lk_icon( 'chevron', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $lk_mega_brands ) : ?>
				<div class="lk-mega" id="lk-mega" data-lk-mega-panels hidden>
					<?php foreach ( $lk_mega_brands as $lk_brand ) : ?>
						<?php
						$lk_term = $lk_brand['term'];
						$lk_link = get_term_link( $lk_term );
						$lk_link = is_wp_error( $lk_link ) ? '' : $lk_link;
						?>
						<section
							class="lk-mega__panel"
							id="lk-mega-panel-<?php echo esc_attr( $lk_term->slug ); ?>"
							data-lk-mega-panel="<?php echo esc_attr( $lk_term->slug ); ?>"
							aria-label="<?php echo esc_attr( $lk_term->name ); ?>"
							hidden
						>
							<div class="lk-container lk-mega__inner">
								<div class="lk-mega__aside">
									<span class="lk-mega__logo" aria-hidden="true"><?php echo esc_html( lk_monogram( $lk_term->name ) ); ?></span>
									<span class="lk-mega__title"><?php echo esc_html( $lk_term->name ); ?></span>
									<p class="lk-mega__meta"><?php echo esc_html( sprintf( '%s خودرو', lk_num( (int) $lk_brand['total'] ) ) ); ?></p>
									<?php if ( $lk_link ) : ?>
										<a class="lk-mega__all" href="<?php echo esc_url( $lk_link ); ?>">
											مشاهده همه
											<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
									<?php endif; ?>
								</div>

								<?php if ( ! empty( $lk_brand['tabs'] ) ) : ?>
									<?php $lk_panel = 'lk-mega-' . $lk_term->slug; ?>
									<div class="lk-mega__tabs" data-lk-tabs>
										<div class="lk-mega__tablist" role="tablist" aria-label="<?php echo esc_attr( sprintf( 'گروه‌های %s', $lk_term->name ) ); ?>">
											<?php foreach ( $lk_brand['tabs'] as $lk_i => $lk_tab ) : ?>
												<?php $lk_tab_id = $lk_panel . '-tab-' . $lk_tab['term']->slug; ?>
												<button
													type="button"
													class="lk-mega__tab"
													role="tab"
													id="<?php echo esc_attr( $lk_tab_id ); ?>"
													aria-controls="<?php echo esc_attr( $lk_tab_id . '-pane' ); ?>"
													aria-selected="<?php echo 0 === $lk_i ? 'true' : 'false'; ?>"
													tabindex="<?php echo 0 === $lk_i ? '0' : '-1'; ?>"
												>
													<?php echo esc_html( empty( $lk_tab['more'] ) ? $lk_tab['term']->name : 'سایر مدل‌ها' ); ?>
													<span class="lk-mega__tab-count"><?php echo esc_html( lk_num( (int) $lk_tab['total'] ) ); ?></span>
												</button>
											<?php endforeach; ?>
										</div>
										<div class="lk-mega__panes">
											<?php foreach ( $lk_brand['tabs'] as $lk_i => $lk_tab ) : ?>
												<?php
												$lk_tab_id   = $lk_panel . '-tab-' . $lk_tab['term']->slug;
												$lk_tab_link = get_term_link( $lk_tab['term'] );
												$lk_tab_link = is_wp_error( $lk_tab_link ) ? '' : $lk_tab_link;
												$lk_tab_name = empty( $lk_tab['more'] ) ? $lk_tab['term']->name : 'سایر مدل‌ها';
												?>
												<div
													class="lk-mega__pane"
													role="tabpanel"
													id="<?php echo esc_attr( $lk_tab_id . '-pane' ); ?>"
													aria-labelledby="<?php echo esc_attr( $lk_tab_id ); ?>"
													tabindex="0"
												>
													<div class="lk-mega__pane-head">
														<span class="lk-mega__pane-title"><?php echo esc_html( $lk_tab_name ); ?></span>
														<span class="lk-mega__pane-meta"><?php echo esc_html( sprintf( '%s خودرو', lk_num( (int) $lk_tab['total'] ) ) ); ?></span>
														<?php if ( $lk_tab_link ) : ?>
															<a class="lk-mega__pane-all" href="<?php echo esc_url( $lk_tab_link ); ?>" aria-label="<?php echo esc_attr( sprintf( 'مشاهده همه خودروهای %s', $lk_tab_name ) ); ?>">
																مشاهده همه
																<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
															</a>
														<?php endif; ?>
													</div>
													<ul class="lk-mega__grid">
														<?php lk_mega_cards( $lk_tab['items'] ); ?>
													</ul>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
								<?php else : ?>
									<ul class="lk-mega__grid">
										<?php lk_mega_cards( $lk_brand['items'] ); ?>
									</ul>
								<?php endif; ?>
							</div>
						</section>
					<?php endforeach; ?>

					<section class="lk-mega__panel lk-mega__panel--all" id="lk-mega-panel-all" data-lk-mega-panel="all" aria-label="همه برندها" hidden>
						<div class="lk-container lk-mega__inner">
							<div class="lk-mega__head">
								<div class="lk-mega__intro">
									<span class="lk-mega__title">همه برندها</span>
									<p class="lk-mega__meta"><?php echo esc_html( sprintf( '%s برند فعال', lk_num( count( $lk_mega_all ) ) ) ); ?></p>
								</div>
								<a class="lk-mega__all" href="<?php echo esc_url( lk_page_url( 'brands' ) ); ?>">
									صفحه برندها
									<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							</div>
							<ul class="lk-mega__brands">
								<?php foreach ( $lk_mega_all as $lk_term ) : ?>
									<?php
									$lk_link = get_term_link( $lk_term );
									$lk_link = is_wp_error( $lk_link ) ? '' : $lk_link;
									if ( ! $lk_link ) {
										continue;
									}
									?>
									<li>
										<a class="lk-mega__brand" href="<?php echo esc_url( $lk_link ); ?>">
											<span class="lk-mega__brand-logo" aria-hidden="true"><?php echo esc_html( lk_monogram( $lk_term->name ) ); ?></span>
											<span class="lk-mega__brand-text">
												<span class="lk-mega__brand-name"><?php echo esc_html( $lk_term->name ); ?></span>
												<span class="lk-mega__brand-count"><?php echo esc_html( sprintf( '%s خودرو', lk_num( (int) $lk_term->count ) ) ); ?></span>
											</span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</section>
				</div>
			<?php endif; ?>
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

			<?php if ( $lk_mega_brands ) : ?>
				<div class="lk-drawer__brands">
					<span class="lk-drawer__brands-label">برندهای خودرو</span>
					<ul>
						<?php foreach ( $lk_mega_brands as $lk_brand ) : ?>
							<?php
							$lk_link = get_term_link( $lk_brand['term'] );
							$lk_link = is_wp_error( $lk_link ) ? '' : $lk_link;
							if ( ! $lk_link ) {
								continue;
							}
							?>
							<li><a href="<?php echo esc_url( $lk_link ); ?>"><?php echo esc_html( $lk_brand['term']->name ); ?></a></li>
						<?php endforeach; ?>
						<li><a href="<?php echo esc_url( lk_page_url( 'brands' ) ); ?>">همه برندها</a></li>
					</ul>
				</div>
			<?php endif; ?>

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
