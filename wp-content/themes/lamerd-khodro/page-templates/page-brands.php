<?php
/**
 * Template Name: برندهای خودرو
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_brands = function_exists( 'lk_get_brands' ) ? lk_get_brands( 0, false ) : array();

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => get_the_title(),
		'text'  => 'برندهای حاضر در بانک اطلاعات لامرد خودرو؛ برای دیدن خودروها و قیمت‌ها روی هر برند بزنید.',
		'meta'  => '<span class="lk-pagehero__badge">' . esc_html( sprintf( '%s برند', lk_num( count( $lk_brands ) ) ) ) . '</span>',
	)
);
?>

<section class="lk-section">
	<div class="lk-container">
		<?php if ( $lk_brands ) : ?>
			<ul class="lk-brands lk-brands--page">
				<?php foreach ( $lk_brands as $lk_brand ) : ?>
					<?php $lk_logo = function_exists( 'lk_brand_logo' ) ? lk_brand_logo( $lk_brand->term_id ) : ''; ?>
					<li class="lk-brand-tile lk-brand-tile--lg">
						<a href="<?php echo esc_url( get_term_link( $lk_brand ) ); ?>">
							<span class="lk-brand-tile__logo">
								<?php if ( $lk_logo ) : ?>
									<img src="<?php echo esc_url( $lk_logo ); ?>" alt="<?php echo esc_attr( $lk_brand->name ); ?>" loading="lazy" width="64" height="64" />
								<?php else : ?>
									<span class="lk-monogram" aria-hidden="true"><?php echo esc_html( lk_monogram( $lk_brand->name ) ); ?></span>
								<?php endif; ?>
							</span>
							<span class="lk-brand-tile__name"><?php echo esc_html( $lk_brand->name ); ?></span>
							<span class="lk-brand-tile__count"><?php echo esc_html( lk_num( $lk_brand->count ) ); ?> خودرو</span>
							<span class="lk-brand-tile__arrow" aria-hidden="true"><?php echo lk_icon( 'arrow-left', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<?php get_template_part( 'template-parts/components/empty-data' ); ?>
		<?php endif; ?>

		<?php if ( get_the_content() ) : ?>
			<div class="lk-prose lk-prose--below"><?php the_content(); ?></div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
