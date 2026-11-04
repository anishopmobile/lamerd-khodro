<?php
/**
 * Home: featured showcase of sale listings.
 *
 * Only renders when at least one listing is published.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LK_Listings' ) ) {
	return;
}

$lk_featured_ids = LK_Listings::featured_ids( 4 );

$lk_lead_query = new WP_Query(
	array(
		'post_type'           => 'lk_listing',
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post__in'            => $lk_featured_ids ? array( $lk_featured_ids[0] ) : array(),
		'orderby'             => 'date',
		'order'               => 'DESC',
	)
);

if ( ! $lk_lead_query->have_posts() ) {
	$lk_lead_query = new WP_Query(
		array(
			'post_type'           => 'lk_listing',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
}

if ( ! $lk_lead_query->have_posts() ) {
	wp_reset_postdata();
	return;
}

$lk_lead = $lk_lead_query->posts[0];

$lk_side_query = new WP_Query(
	array(
		'post_type'           => 'lk_listing',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'post__not_in'        => array( (int) $lk_lead->ID ),
		'orderby'             => 'date',
		'order'               => 'DESC',
	)
);

$lk_archive = get_post_type_archive_link( 'lk_listing' );
$lk_total   = (int) wp_count_posts( 'lk_listing' )->publish;

$lk_chip = static function ( $text ) {
	if ( '' === trim( (string) $text ) ) {
		return;
	}
	echo '<li>' . esc_html( $text ) . '</li>';
};

$lk_lead_price = (int) lk_listing_field( $lk_lead->ID, 'price', 0 );
$lk_lead_year  = (int) lk_listing_field( $lk_lead->ID, 'year', 0 );
$lk_lead_km    = (int) lk_listing_field( $lk_lead->ID, 'km', 0 );
$lk_lead_gear  = (string) lk_listing_field( $lk_lead->ID, 'gearbox', '' );
$lk_lead_city  = (string) lk_listing_field( $lk_lead->ID, 'city', '' );
$lk_featured   = lk_listing_is_featured( $lk_lead->ID );
$lk_thumb      = get_the_post_thumbnail_url( $lk_lead->ID, 'lk-wide' );
$lk_url        = get_permalink( $lk_lead->ID );
?>
<section class="lk-section lk-hsale" aria-labelledby="lk-hsale-title">
	<div class="lk-container">
		<div class="lk-hsale__head">
			<div class="lk-hsale__intro">
				<span class="lk-hsale__kicker">
					<?php echo lk_icon( 'megaphone', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					آگهی‌های فروش
				</span>
				<h2 class="lk-hsale__title" id="lk-hsale-title">خودروهای آماده فروش</h2>
				<p class="lk-hsale__text">خودروهای واقعی با قیمت، کارکرد و مشخصات ثبت‌شده؛ تماس مستقیم با فروشنده و بدون واسطه.</p>
			</div>
			<a class="lk-hsale__more" href="<?php echo esc_url( $lk_archive ); ?>">
				همه آگهی‌ها
				<span class="lk-hsale__more-count"><?php echo esc_html( LK_Format::digits( $lk_total ) ); ?> آگهی</span>
				<?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>

		<div class="lk-hsale__grid">
			<article class="lk-hsale__lead<?php echo $lk_featured ? ' is-featured' : ''; ?><?php echo $lk_thumb ? ' has-photo' : ''; ?>">
				<a class="lk-hsale__media" href="<?php echo esc_url( $lk_url ); ?>" tabindex="-1" aria-hidden="true">
					<?php if ( $lk_thumb ) : ?>
						<img src="<?php echo esc_url( $lk_thumb ); ?>" alt="" width="1200" height="640" loading="lazy" />
					<?php else : ?>
						<span class="lk-hsale__placeholder">
							<?php echo lk_icon( 'car', 'lk-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span>تصویر ثبت نشده</span>
						</span>
					<?php endif; ?>

					<?php if ( $lk_featured ) : ?>
						<span class="lk-hsale__ribbon">فروش ویژه</span>
						<span class="lk-timer lk-hsale__timer" data-lk-countdown data-until="<?php echo esc_attr( lk_listing_featured_until( $lk_lead->ID ) ); ?>">
							<?php echo lk_icon( 'clock', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span data-lk-countdown-text><?php echo esc_html( lk_listing_time_left( $lk_lead->ID ) ); ?></span>
						</span>
					<?php endif; ?>
				</a>

				<div class="lk-hsale__body">
					<div class="lk-hsale__row">
						<?php if ( $lk_lead_city ) : ?>
							<span class="lk-hsale__city">
								<?php echo lk_icon( 'pin', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo esc_html( lk_listing_label( 'cities', $lk_lead_city ) ); ?>
							</span>
						<?php endif; ?>
						<span class="lk-hsale__badge">تازه‌ترین آگهی</span>
					</div>

					<h3 class="lk-hsale__name">
						<a href="<?php echo esc_url( $lk_url ); ?>"><?php echo esc_html( get_the_title( $lk_lead ) ); ?></a>
					</h3>

					<ul class="lk-hsale__chips">
						<?php
						$lk_chip( $lk_lead_year ? 'مدل ' . LK_Format::digits( $lk_lead_year ) : '' );
						$lk_chip( $lk_lead_km ? LK_Format::number( $lk_lead_km ) . ' کیلومتر' : '' );
						$lk_chip( $lk_lead_gear ? lk_listing_label( 'gearboxes', $lk_lead_gear ) : '' );
						?>
					</ul>

					<div class="lk-hsale__foot">
						<div class="lk-hsale__price-block">
							<span class="lk-hsale__price-label">قیمت فروش</span>
							<?php if ( $lk_lead_price > 0 ) : ?>
								<?php echo lk_money_short( $lk_lead_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php else : ?>
								<span class="lk-money lk-money--empty">قیمت توافقی</span>
							<?php endif; ?>
						</div>
						<a class="lk-btn lk-btn--accent lk-hsale__cta" href="<?php echo esc_url( $lk_url ); ?>">مشاهده آگهی</a>
					</div>
				</div>
			</article>

			<?php if ( $lk_side_query->have_posts() ) : ?>
				<div class="lk-hsale__side">
					<?php
					while ( $lk_side_query->have_posts() ) :
						$lk_side_query->the_post();
						$lk_sid        = get_the_ID();
						$lk_s_price    = (int) lk_listing_field( $lk_sid, 'price', 0 );
						$lk_s_year     = (int) lk_listing_field( $lk_sid, 'year', 0 );
						$lk_s_km       = (int) lk_listing_field( $lk_sid, 'km', 0 );
						$lk_s_city     = (string) lk_listing_field( $lk_sid, 'city', '' );
						$lk_s_featured = lk_listing_is_featured( $lk_sid );
						$lk_s_thumb    = get_the_post_thumbnail_url( $lk_sid, 'lk-card' );
						?>
						<a class="lk-hsale__row-card<?php echo $lk_s_featured ? ' is-featured' : ''; ?>" href="<?php echo esc_url( get_permalink( $lk_sid ) ); ?>">
							<span class="lk-hsale__thumb">
								<?php if ( $lk_s_thumb ) : ?>
									<img src="<?php echo esc_url( $lk_s_thumb ); ?>" alt="" width="380" height="230" loading="lazy" />
								<?php else : ?>
									<span class="lk-hsale__thumb-empty"><?php echo lk_icon( 'car' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php endif; ?>
							</span>

							<span class="lk-hsale__row-body">
								<span class="lk-hsale__row-top">
									<?php if ( $lk_s_featured ) : ?>
										<span class="lk-hsale__row-tag">ویژه</span>
									<?php endif; ?>
									<?php if ( $lk_s_city ) : ?>
										<span class="lk-hsale__row-city"><?php echo esc_html( lk_listing_label( 'cities', $lk_s_city ) ); ?></span>
									<?php endif; ?>
								</span>

								<span class="lk-hsale__row-name"><?php echo esc_html( get_the_title( $lk_sid ) ); ?></span>

								<span class="lk-hsale__row-meta">
									<?php
									if ( $lk_s_year ) {
										echo esc_html( LK_Format::digits( $lk_s_year ) );
									}
									if ( $lk_s_km ) {
										echo ' · ' . esc_html( LK_Format::number( $lk_s_km ) . ' کیلومتر' );
									}
									?>
								</span>

								<span class="lk-hsale__row-price">
									<?php if ( $lk_s_price > 0 ) : ?>
										<?php echo lk_money_short( $lk_s_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php else : ?>
										<span class="lk-money lk-money--empty">قیمت توافقی</span>
									<?php endif; ?>
								</span>
							</span>
						</a>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
wp_reset_postdata();
