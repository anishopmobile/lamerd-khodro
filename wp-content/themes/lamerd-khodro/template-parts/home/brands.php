<?php
/**
 * Home: brand strip.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_brands = function_exists( 'lk_get_brands' ) ? lk_get_brands( 14 ) : array();

if ( ! $lk_brands ) {
	return;
}
?>
<section class="lk-section lk-section--tight">
	<div class="lk-container">
		<?php
		lk_section_head(
			'۰۲',
			'برندها',
			'از ایران‌خودرو و سایپا تا برندهای چینی، ژاپنی و وارداتی.',
			array(
				'label' => 'همه برندها',
				'url'   => lk_page_url( 'brands' ),
			)
		);
		?>

		<ul class="lk-brands">
			<?php foreach ( $lk_brands as $lk_brand ) : ?>
				<?php $lk_logo = function_exists( 'lk_brand_logo' ) ? lk_brand_logo( $lk_brand->term_id ) : ''; ?>
				<li class="lk-brand-tile">
					<a href="<?php echo esc_url( get_term_link( $lk_brand ) ); ?>">
						<span class="lk-brand-tile__logo">
							<?php if ( $lk_logo ) : ?>
								<img src="<?php echo esc_url( $lk_logo ); ?>" alt="<?php echo esc_attr( $lk_brand->name ); ?>" loading="lazy" width="56" height="56" />
							<?php else : ?>
								<span class="lk-monogram lk-monogram--sm" aria-hidden="true"><?php echo esc_html( lk_monogram( $lk_brand->name ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="lk-brand-tile__name"><?php echo esc_html( $lk_brand->name ); ?></span>
						<span class="lk-brand-tile__count"><?php echo esc_html( lk_num( $lk_brand->count ) ); ?> خودرو</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
