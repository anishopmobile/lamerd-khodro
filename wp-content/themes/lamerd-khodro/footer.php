<?php
/**
 * Site footer.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_phone   = lk_opt( 'contact_phone', '' );
$lk_email   = lk_opt( 'contact_email', '' );
$lk_address = lk_opt( 'contact_address', '' );
$lk_hours   = lk_opt( 'contact_hours', '' );
$lk_socials = lk_social_links();
$lk_brands  = function_exists( 'lk_get_brands' ) ? lk_get_brands( 6 ) : array();
?>
	</main>

	<footer class="lk-footer">
		<div class="lk-container lk-footer__grid">
			<div class="lk-footer__brand">
				<a class="lk-logo lk-logo--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>">
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

				<p class="lk-footer__about"><?php echo esc_html( lk_opt( 'footer_about' ) ); ?></p>

				<?php if ( $lk_socials ) : ?>
					<ul class="lk-socials">
						<?php foreach ( $lk_socials as $key => $social ) : ?>
							<li>
								<a href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social['label'] ); ?>">
									<?php echo lk_icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="lk-footer__col">
				<h2 class="lk-footer__title">دسترسی سریع</h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'menu_class'     => 'lk-footer__list',
						'container'      => false,
						'fallback_cb'    => 'lk_theme_menu_fallback',
						'depth'          => 1,
					)
				);
				?>
			</div>

			<div class="lk-footer__col">
				<h2 class="lk-footer__title">برندهای پرجستجو</h2>
				<?php if ( $lk_brands ) : ?>
					<ul class="lk-footer__list">
						<?php foreach ( $lk_brands as $brand ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $brand ) ); ?>"><?php echo esc_html( $brand->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="lk-footer__muted">فهرست برندها پس از فعال‌سازی داده‌ها نمایش داده می‌شود.</p>
				<?php endif; ?>
			</div>

			<div class="lk-footer__col lk-footer__col--contact">
				<h2 class="lk-footer__title">ارتباط با ما</h2>
				<ul class="lk-footer__contact">
					<?php if ( $lk_phone ) : ?>
						<li><?php echo lk_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $lk_phone ) ); ?>" dir="ltr"><?php echo esc_html( $lk_phone ); ?></a></li>
					<?php endif; ?>
					<?php if ( $lk_email ) : ?>
						<li><?php echo lk_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="mailto:<?php echo esc_attr( $lk_email ); ?>" dir="ltr"><?php echo esc_html( $lk_email ); ?></a></li>
					<?php endif; ?>
					<?php if ( $lk_address ) : ?>
						<li><?php echo lk_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $lk_address ); ?></span></li>
					<?php endif; ?>
					<?php if ( $lk_hours ) : ?>
						<li><?php echo lk_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $lk_hours ); ?></span></li>
					<?php endif; ?>
					<?php if ( ! $lk_phone && ! $lk_email && ! $lk_address && ! $lk_hours ) : ?>
						<li><a href="<?php echo esc_url( lk_page_url( 'contact' ) ); ?>">صفحه تماس با ما</a></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>

		<div class="lk-footer__bottom">
			<div class="lk-container lk-footer__bottom-inner">
				<p class="lk-footer__copy">
					<?php
					$copyright = lk_opt( 'footer_copyright', '' );
					if ( $copyright ) {
						echo esc_html( $copyright );
					} else {
						echo esc_html( '© ' . lk_num( (int) lk_jalali( null, 'year' ) ) . ' — ' . get_bloginfo( 'name' ) );
					}
					?>
				</p>

				<form class="lk-footer__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="lk-footer-search">جستجو</label>
					<input type="search" id="lk-footer-search" name="s" placeholder="جستجو در سایت…" />
					<button type="submit" aria-label="جستجو"><?php echo lk_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</form>

				<?php lk_attribution_line(); ?>
			</div>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
