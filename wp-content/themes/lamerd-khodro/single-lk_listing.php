<?php
/**
 * Single sale listing.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lk_id        = get_the_ID();
	$lk_price     = (int) lk_listing_field( $lk_id, 'price', 0 );
	$lk_year      = (int) lk_listing_field( $lk_id, 'year', 0 );
	$lk_km        = (int) lk_listing_field( $lk_id, 'km', 0 );
	$lk_color     = (string) lk_listing_field( $lk_id, 'color', '' );
	$lk_gearbox   = (string) lk_listing_field( $lk_id, 'gearbox', '' );
	$lk_city      = (string) lk_listing_field( $lk_id, 'city', '' );
	$lk_condition = (string) lk_listing_field( $lk_id, 'condition', 'used' );
	$lk_seller    = (string) lk_listing_field( $lk_id, 'seller', '' );
	$lk_phone     = (string) lk_listing_field( $lk_id, 'phone', '' );
	$lk_vehicle   = (int) lk_listing_field( $lk_id, 'vehicle', 0 );
	$lk_featured  = lk_listing_is_featured( $lk_id );
	$lk_thumb     = get_the_post_thumbnail_url( $lk_id, 'lk-wide' );
	$lk_tel       = preg_replace( '/[^0-9+]/', '', $lk_phone );

	$lk_facts = array(
		'سال ساخت'  => $lk_year ? LK_Format::digits( $lk_year ) : '',
		'کارکرد'    => $lk_km ? LK_Format::number( $lk_km ) . ' کیلومتر' : '',
		'گیربکس'    => lk_listing_label( 'gearboxes', $lk_gearbox ),
		'رنگ'       => $lk_color,
		'وضعیت'     => lk_listing_label( 'conditions', $lk_condition ),
		'شهر'       => lk_listing_label( 'cities', $lk_city ),
	);
	?>

	<article class="lk-listing">

		<header class="lk-vhero">
			<div class="lk-vhero__pattern" aria-hidden="true"></div>
			<div class="lk-container">
				<?php lk_breadcrumb(); ?>

				<div class="lk-vhero__grid">
					<div class="lk-vhero__main">
						<div class="lk-vhero__meta">
							<?php if ( $lk_featured ) : ?>
								<span class="lk-badge-featured">فروش ویژه</span>
								<span class="lk-timer" data-lk-countdown data-until="<?php echo esc_attr( lk_listing_featured_until( $lk_id ) ); ?>">
									<?php echo lk_icon( 'clock', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span data-lk-countdown-text><?php echo esc_html( lk_listing_time_left( $lk_id ) ); ?></span>
								</span>
							<?php endif; ?>
							<?php if ( $lk_city ) : ?>
								<span class="lk-vhero__brand"><?php echo esc_html( lk_listing_label( 'cities', $lk_city ) ); ?></span>
							<?php endif; ?>
						</div>

						<h1 class="lk-vhero__title"><?php the_title(); ?></h1>

						<p class="lk-vhero__lead">
							<?php
							echo esc_html(
								has_excerpt()
									? get_the_excerpt()
									: 'آگهی فروش این خودرو در لامرد خودرو ثبت شده است. مشخصات، قیمت و شماره تماس فروشنده را در همین صفحه ببینید.'
							);
							?>
						</p>

						<div class="lk-vhero__quick">
							<?php foreach ( $lk_facts as $lk_label => $lk_value ) : ?>
								<?php if ( ! $lk_value ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<div class="lk-quickfact">
									<span class="lk-quickfact__label"><?php echo esc_html( $lk_label ); ?></span>
									<span class="lk-quickfact__value"><?php echo esc_html( $lk_value ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<aside class="lk-listing__deal" aria-label="قیمت و تماس">
						<span class="lk-listing__deal-label">قیمت فروش</span>
						<div class="lk-listing__deal-price">
							<?php if ( $lk_price > 0 ) : ?>
								<?php echo lk_money( $lk_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php else : ?>
								<span class="lk-money lk-money--empty">قیمت توافقی</span>
							<?php endif; ?>
						</div>

						<?php if ( $lk_seller ) : ?>
							<p class="lk-listing__seller">فروشنده: <strong><?php echo esc_html( $lk_seller ); ?></strong></p>
						<?php endif; ?>

						<?php if ( $lk_tel ) : ?>
							<a class="lk-btn lk-btn--accent lk-btn--block" href="tel:<?php echo esc_attr( $lk_tel ); ?>">
								<?php echo lk_icon( 'phone', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								تماس با فروشنده
							</a>
							<p class="lk-listing__phone" dir="ltr"><?php echo esc_html( $lk_phone ); ?></p>
						<?php else : ?>
							<p class="lk-listing__deal-note">شماره تماس برای این آگهی ثبت نشده است.</p>
						<?php endif; ?>

						<?php if ( $lk_vehicle && 'lk_vehicle' === get_post_type( $lk_vehicle ) ) : ?>
							<a class="lk-btn lk-btn--ghost lk-btn--block" href="<?php echo esc_url( get_permalink( $lk_vehicle ) ); ?>">
								قیمت روز و مشخصات این خودرو
							</a>
						<?php endif; ?>

						<p class="lk-listing__code">کد آگهی: <?php echo esc_html( LK_Format::digits( $lk_id ) ); ?></p>
					</aside>
				</div>
			</div>
		</header>

		<div class="lk-container lk-listing__body">

			<section class="lk-panel lk-listing__gallery-panel">
				<div class="lk-panel__head">
					<h2 class="lk-panel__title">تصویر خودرو</h2>
				</div>
				<?php if ( $lk_thumb ) : ?>
					<figure class="lk-listing__photo">
						<img src="<?php echo esc_url( $lk_thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" width="960" height="540" />
					</figure>
				<?php else : ?>
					<div class="lk-gallery__placeholder">
						<span class="lk-monogram lk-monogram--xl" aria-hidden="true"><?php echo esc_html( lk_monogram( get_the_title() ) ); ?></span>
						<span class="lk-gallery__placeholder-text">فروشنده تصویری برای این آگهی ثبت نکرده است</span>
					</div>
				<?php endif; ?>
			</section>

			<section class="lk-panel">
				<div class="lk-panel__head">
					<h2 class="lk-panel__title">مشخصات آگهی</h2>
				</div>
				<dl class="lk-listing__facts">
					<?php foreach ( $lk_facts as $lk_label => $lk_value ) : ?>
						<div class="lk-listing__fact">
							<dt><?php echo esc_html( $lk_label ); ?></dt>
							<dd><?php echo $lk_value ? esc_html( $lk_value ) : 'ثبت نشده'; ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</section>

			<?php if ( get_the_content() ) : ?>
				<section class="lk-panel lk-prose">
					<h2 class="lk-panel__title">توضیحات فروشنده</h2>
					<?php the_content(); ?>
				</section>
			<?php endif; ?>

			<div class="lk-listing__safety">
				<?php echo lk_icon( 'check', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p>لامرد خودرو آگهی را منتشر می‌کند و در معامله واسطه نیست. پیش از پرداخت، خودرو، اسناد و تعهدات آن را حضوری بررسی کنید.</p>
			</div>

			<?php
			$lk_related = new WP_Query(
				array(
					'post_type'      => 'lk_listing',
					'posts_per_page' => 3,
					'post__not_in'   => array( $lk_id ),
					'no_found_rows'  => true,
				)
			);

			if ( $lk_related->have_posts() ) :
				?>
				<section class="lk-panel">
					<div class="lk-panel__head">
						<h2 class="lk-panel__title">سایر آگهی‌ها</h2>
						<a class="lk-textlink" href="<?php echo esc_url( get_post_type_archive_link( 'lk_listing' ) ); ?>">همه آگهی‌ها</a>
					</div>
					<div class="lk-listing-grid">
						<?php
						while ( $lk_related->have_posts() ) :
							$lk_related->the_post();
							get_template_part( 'template-parts/listing/card', null, array( 'post' => get_post() ) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</article>

	<?php
endwhile;

get_footer();
