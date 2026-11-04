<?php
/**
 * Sale listings archive.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_featured_ids = class_exists( 'LK_Listings' ) ? LK_Listings::featured_ids( 6 ) : array();
$lk_total        = (int) wp_count_posts( 'lk_listing' )->publish;

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => 'آگهی‌های فروش خودرو',
		'text'  => 'خودروهای آماده فروش در لامرد و منطقه؛ هر آگهی با مشخصات، قیمت و شماره تماس فروشنده ثبت شده است.',
		'meta'  => '<span class="lk-pagehero__badge">' . esc_html( LK_Format::number( $lk_total ) . ' آگهی' ) . '</span>',
	)
);
?>

<div class="lk-container lk-listings">

	<?php if ( $lk_featured_ids ) : ?>
		<section class="lk-featured-band" aria-labelledby="lk-featured-title">
			<div class="lk-featured-band__head">
				<h2 class="lk-featured-band__title" id="lk-featured-title">
					<?php echo lk_icon( 'clock', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					فروش ویژه
				</h2>
				<p class="lk-featured-band__note">آگهی‌های ویژه تا پایان زمان تایمر در بالای فهرست می‌مانند و سپس خودکار به فهرست عادی برمی‌گردند.</p>
			</div>

			<div class="lk-listing-grid lk-listing-grid--featured">
				<?php
				foreach ( $lk_featured_ids as $lk_featured_id ) :
					$lk_featured_post = get_post( $lk_featured_id );
					if ( ! $lk_featured_post ) {
						continue;
					}
					get_template_part( 'template-parts/listing/card', null, array( 'post' => $lk_featured_post ) );
				endforeach;
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="lk-listings__head">
			<h2 class="lk-listings__title"><?php echo esc_html( $lk_featured_ids ? 'سایر آگهی‌ها' : 'همه آگهی‌ها' ); ?></h2>
			<p class="lk-listings__count"><?php echo esc_html( LK_Format::number( (int) $GLOBALS['wp_query']->found_posts ) . ' آگهی' ); ?></p>
		</div>

		<div class="lk-listing-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/listing/card', null, array( 'post' => get_post() ) );
			endwhile;
			?>
		</div>

		<?php lk_pagination( $GLOBALS['wp_query'] ); ?>
	<?php elseif ( ! $lk_featured_ids ) : ?>
		<?php
		get_template_part(
			'template-parts/components/empty-data',
			null,
			array(
				'title' => 'هنوز آگهی فروشی ثبت نشده است',
				'text'  => 'به‌محض ثبت نخستین آگهی از بخش مدیریت، در همین صفحه منتشر می‌شود. هیچ آگهی نمونه یا ساختگی نمایش داده نمی‌شود.',
			)
		);
		?>
	<?php endif; ?>

	<p class="lk-listings__note">
		لامرد خودرو آگهی‌ها را منتشر می‌کند و واسطه معامله نیست. پیش از هر پرداخت، خودرو و اسناد آن را حضوری بررسی کنید.
	</p>
</div>

<?php
get_footer();
