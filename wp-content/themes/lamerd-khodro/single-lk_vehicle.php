<?php
/**
 * Single vehicle.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lk_post_id     = get_the_ID();
	$lk_brand       = function_exists( 'lk_vehicle_brand' ) ? lk_vehicle_brand( $lk_post_id ) : null;
	$lk_status      = function_exists( 'lk_production_status' ) ? lk_production_status( $lk_post_id ) : '';
	$lk_specs       = function_exists( 'lk_vehicle_specs' ) ? lk_vehicle_specs( $lk_post_id ) : array();
	$lk_gallery     = function_exists( 'lk_vehicle_gallery' ) ? lk_vehicle_gallery( $lk_post_id ) : array();
	$lk_thumb       = get_the_post_thumbnail_url( $lk_post_id, 'lk-wide' );
	$lk_gallery_all = array();
	if ( $lk_thumb ) {
		$lk_gallery_all[] = $lk_thumb;
	}
	foreach ( $lk_gallery as $lk_attachment_id ) {
		$lk_url = wp_get_attachment_image_url( $lk_attachment_id, 'lk-wide' );
		if ( $lk_url ) {
			$lk_gallery_all[] = $lk_url;
		}
	}
	?>

	<article class="lk-vehicle">

		<header class="lk-vhero">
			<div class="lk-vhero__pattern" aria-hidden="true"></div>
			<div class="lk-container">
				<?php lk_breadcrumb(); ?>

				<div class="lk-vhero__grid">
					<div class="lk-vhero__main">
						<div class="lk-vhero__meta">
							<?php if ( $lk_brand ) : ?>
								<a class="lk-vhero__brand" href="<?php echo esc_url( get_term_link( $lk_brand ) ); ?>"><?php echo esc_html( $lk_brand->name ); ?></a>
							<?php endif; ?>
							<?php if ( $lk_status ) : ?>
								<span class="lk-status <?php echo false !== mb_strpos( $lk_status, 'متوقف' ) ? 'is-stopped' : 'is-active'; ?>"><?php echo esc_html( $lk_status ); ?></span>
							<?php endif; ?>
						</div>

						<h1 class="lk-vhero__title"><?php the_title(); ?></h1>

						<p class="lk-vhero__lead"><?php echo esc_html( has_excerpt() ? get_the_excerpt() : 'قیمت بازار، وضعیت تولید و تاریخچه تغییرات این خودرو را در لامرد خودرو ببینید. برای مقایسه با خودروهای دیگر، آن را به فهرست مقایسه اضافه کنید.' ); ?></p>

						<div class="lk-vhero__quick">
							<?php
							$lk_quick = array(
								'year'          => 'سال تولید',
								'engine_type'   => 'موتور',
								'gearbox_type'  => 'گیربکس',
								'body_type'     => 'بدنه',
							);
							foreach ( $lk_quick as $lk_key => $lk_label ) :
								$lk_value = function_exists( 'lk_vehicle_spec' ) ? lk_vehicle_spec( $lk_post_id, $lk_key ) : '';
								if ( ! $lk_value ) {
									continue;
								}
								?>
								<div class="lk-quickfact">
									<span class="lk-quickfact__label"><?php echo esc_html( $lk_label ); ?></span>
									<span class="lk-quickfact__value"><?php echo esc_html( $lk_value ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<?php get_template_part( 'template-parts/vehicle/price-panel', null, array( 'post_id' => $lk_post_id ) ); ?>
				</div>
			</div>
		</header>

		<div class="lk-container lk-vehicle__body">

			<section class="lk-panel lk-gallery-panel">
				<div class="lk-panel__head">
					<h2 class="lk-panel__title">تصاویر</h2>
				</div>
				<?php if ( $lk_gallery_all ) : ?>
					<div class="lk-gallery">
						<figure class="lk-gallery__main">
							<img src="<?php echo esc_url( $lk_gallery_all[0] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" width="960" height="540" />
						</figure>
						<?php if ( count( $lk_gallery_all ) > 1 ) : ?>
							<div class="lk-gallery__thumbs">
								<?php foreach ( array_slice( $lk_gallery_all, 1, 6 ) as $lk_index => $lk_image ) : ?>
									<button type="button" class="lk-gallery__thumb" data-lk-gallery-src="<?php echo esc_url( $lk_image ); ?>" aria-label="تصویر <?php echo esc_attr( lk_num( $lk_index + 2 ) ); ?>">
										<img src="<?php echo esc_url( $lk_image ); ?>" alt="" loading="lazy" width="160" height="100" />
									</button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="lk-gallery__placeholder">
						<span class="lk-monogram lk-monogram--xl" aria-hidden="true"><?php echo esc_html( lk_monogram( get_the_title() ) ); ?></span>
						<span class="lk-gallery__placeholder-text">تصویری برای این خودرو ثبت نشده است</span>
					</div>
				<?php endif; ?>
			</section>

			<section class="lk-panel lk-chart-panel" id="price-history">
				<div class="lk-panel__head">
					<div>
						<h2 class="lk-panel__title">روند قیمت</h2>
						<p class="lk-panel__sub">قیمت بازار بر اساس رکوردهای ثبت‌شده در دیتابیس لامرد خودرو</p>
					</div>
					<div class="lk-range" role="group" aria-label="بازه زمانی نمودار">
						<button type="button" class="lk-range__btn" data-lk-chart-range="7">۱ هفته</button>
						<button type="button" class="lk-range__btn is-active" data-lk-chart-range="30">۱ ماه</button>
						<button type="button" class="lk-range__btn" data-lk-chart-range="90">۳ ماه</button>
						<button type="button" class="lk-range__btn" data-lk-chart-range="365">۱ سال</button>
						<button type="button" class="lk-range__btn" data-lk-chart-range="all">همه</button>
					</div>
				</div>

				<div class="lk-chart" data-lk-chart-wrap data-vehicle="<?php echo esc_attr( $lk_post_id ); ?>">
					<canvas data-lk-chart aria-label="نمودار روند قیمت" role="img" height="300"></canvas>
					<div class="lk-chart__state" data-lk-chart-state>
						<span class="lk-chart__spinner" aria-hidden="true"></span>
						<span>در حال بارگذاری نمودار…</span>
					</div>
				</div>

				<p class="lk-chart__note">
					تاریخچه قیمت از زمان فعال‌شدن سامانه در لامرد خودرو ثبت می‌شود؛ داده‌های پیش از راه‌اندازی بازسازی نمی‌شوند.
					برای رسم نمودار به حداقل دو ثبت معتبر نیاز است.
				</p>
			</section>

			<section class="lk-panel lk-specs-panel">
				<div class="lk-panel__head">
					<h2 class="lk-panel__title">مشخصات فنی</h2>
					<?php if ( $lk_specs ) : ?>
						<span class="lk-panel__sub"><?php echo esc_html( sprintf( '%s گروه مشخصات', lk_num( count( $lk_specs ) ) ) ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $lk_specs ) : ?>
					<div class="lk-specs">
						<?php foreach ( $lk_specs as $lk_group ) : ?>
							<section class="lk-specgroup">
								<h3 class="lk-specgroup__title"><?php echo esc_html( $lk_group['label'] ); ?></h3>
								<dl class="lk-specgroup__list">
									<?php foreach ( $lk_group['fields'] as $lk_field ) : ?>
										<div class="lk-specrow">
											<dt><?php echo esc_html( $lk_field['label'] ); ?></dt>
											<dd><?php echo esc_html( $lk_field['display'] ); ?></dd>
										</div>
									<?php endforeach; ?>
								</dl>
							</section>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="lk-specs__empty">مشخصات فنی این خودرو هنوز در بانک اطلاعات ثبت نشده است. تا زمان ثبت داده مستند، هیچ مشخصه‌ای حدس زده نمی‌شود.</p>
				<?php endif; ?>
			</section>

			<?php if ( get_the_content() ) : ?>
				<section class="lk-panel lk-prose">
					<h2 class="lk-panel__title">درباره این خودرو</h2>
					<?php the_content(); ?>
				</section>
			<?php endif; ?>

			<?php
			$lk_related = new WP_Query(
				array(
					'post_type'      => 'lk_vehicle',
					'posts_per_page' => 4,
					'post__not_in'   => array( $lk_post_id ),
					'no_found_rows'  => true,
					'tax_query'      => $lk_brand ? array(
						array(
							'taxonomy' => 'lk_brand',
							'field'    => 'term_id',
							'terms'    => $lk_brand->term_id,
						),
					) : array(),
				)
			);

			if ( $lk_related->have_posts() ) :
				$lk_related_posts = function_exists( 'lk_query_with_prices' ) ? lk_query_with_prices( $lk_related ) : $lk_related->posts;
				?>
				<section class="lk-panel lk-related">
					<div class="lk-panel__head">
						<h2 class="lk-panel__title">خودروهای مرتبط</h2>
						<?php if ( $lk_brand ) : ?>
							<a class="lk-textlink" href="<?php echo esc_url( get_term_link( $lk_brand ) ); ?>">همه خودروهای <?php echo esc_html( $lk_brand->name ); ?></a>
						<?php endif; ?>
					</div>
					<div class="lk-grid lk-grid--4">
						<?php foreach ( $lk_related_posts as $lk_post ) : ?>
							<?php get_template_part( 'template-parts/vehicle/card', null, array( 'post' => $lk_post ) ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</article>

	<?php
endwhile;

get_footer();
