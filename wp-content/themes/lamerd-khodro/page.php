<?php
/**
 * Standard page.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title' => get_the_title(),
			'text'  => has_excerpt() ? get_the_excerpt() : '',
		)
	);
	?>

	<section class="lk-section">
		<div class="lk-container lk-page__body">
			<div class="lk-prose">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>

			<?php if ( 'contact' === get_post_field( 'post_name' ) ) : ?>
				<?php
				$lk_phone   = lk_opt( 'contact_phone', '' );
				$lk_email   = lk_opt( 'contact_email', '' );
				$lk_address = lk_opt( 'contact_address', '' );
				$lk_hours   = lk_opt( 'contact_hours', '' );
				$lk_socials = lk_social_links();
				?>
				<div class="lk-contact-cards">
					<?php if ( $lk_phone ) : ?>
						<div class="lk-contact-card">
							<span class="lk-contact-card__icon"><?php echo lk_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="lk-contact-card__label">تلفن</span>
							<a class="lk-contact-card__value" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $lk_phone ) ); ?>" dir="ltr"><?php echo esc_html( $lk_phone ); ?></a>
						</div>
					<?php endif; ?>
					<?php if ( $lk_email ) : ?>
						<div class="lk-contact-card">
							<span class="lk-contact-card__icon"><?php echo lk_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="lk-contact-card__label">ایمیل</span>
							<a class="lk-contact-card__value" href="mailto:<?php echo esc_attr( $lk_email ); ?>" dir="ltr"><?php echo esc_html( $lk_email ); ?></a>
						</div>
					<?php endif; ?>
					<?php if ( $lk_address ) : ?>
						<div class="lk-contact-card">
							<span class="lk-contact-card__icon"><?php echo lk_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="lk-contact-card__label">نشانی</span>
							<span class="lk-contact-card__value"><?php echo esc_html( $lk_address ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $lk_hours ) : ?>
						<div class="lk-contact-card">
							<span class="lk-contact-card__icon"><?php echo lk_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="lk-contact-card__label">ساعات پاسخ‌گویی</span>
							<span class="lk-contact-card__value"><?php echo esc_html( $lk_hours ); ?></span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $lk_socials ) : ?>
					<div class="lk-contact-social">
						<span class="lk-contact-social__label">شبکه‌های اجتماعی:</span>
						<ul class="lk-socials">
							<?php foreach ( $lk_socials as $lk_key => $lk_social ) : ?>
								<li>
									<a href="<?php echo esc_url( $lk_social['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $lk_social['label'] ); ?>">
										<?php echo lk_icon( $lk_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>

	<?php
endwhile;

get_footer();
