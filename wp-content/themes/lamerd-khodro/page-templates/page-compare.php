<?php
/**
 * Template Name: مقایسه خودرو
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_ids_raw = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_ids     = array_values( array_filter( array_map( 'absint', explode( ',', $lk_ids_raw ) ) ) );
$lk_ids     = array_slice( array_unique( $lk_ids ), 0, 4 );
$lk_data    = ( $lk_ids && class_exists( 'LK_Compare' ) ) ? LK_Compare::rows( $lk_ids ) : null;

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => get_the_title(),
		'text'  => 'دو تا چهار خودرو را انتخاب کنید تا قیمت، مشخصات پیشرانه، ابعاد و امکانات را کنار هم ببینید.',
	)
);
?>

<section class="lk-section lk-section--tight">
	<div class="lk-container">

		<div class="lk-cmp-builder" data-lk-compare-builder data-max="4">
			<div class="lk-cmp-builder__head">
				<h2 class="lk-cmp-builder__title">خودروهای انتخابی</h2>
				<span class="lk-cmp-builder__hint" data-lk-compare-hint>حداقل ۲ و حداکثر ۴ خودرو</span>
			</div>

			<div class="lk-cmp-slots" data-lk-compare-slots>
				<?php if ( $lk_data && ! empty( $lk_data['vehicles'] ) ) : ?>
					<?php foreach ( $lk_data['vehicles'] as $lk_vehicle ) : ?>
						<span class="lk-cmp-slot" data-id="<?php echo esc_attr( $lk_vehicle['id'] ); ?>" data-title="<?php echo esc_attr( $lk_vehicle['title'] ); ?>">
							<span class="lk-cmp-slot__name"><?php echo esc_html( $lk_vehicle['title'] ); ?></span>
							<a class="lk-cmp-slot__remove" href="<?php echo esc_url( add_query_arg( 'ids', implode( ',', array_diff( $lk_ids, array( $lk_vehicle['id'] ) ) ), get_permalink() ) ); ?>" aria-label="<?php echo esc_attr( 'حذف ' . $lk_vehicle['title'] ); ?>">×</a>
						</span>
					<?php endforeach; ?>
				<?php else : ?>
					<span class="lk-cmp-slot lk-cmp-slot--placeholder">هنوز خودرویی انتخاب نشده است</span>
				<?php endif; ?>
			</div>

			<div class="lk-cmp-picker">
				<label class="lk-cmp-picker__label" for="lk-cmp-search">افزودن خودرو</label>
				<div class="lk-cmp-picker__field">
					<?php echo lk_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<input type="search" id="lk-cmp-search" placeholder="نام خودرو را بنویسید؛ مثلاً سورن یا لندکروز" autocomplete="off" data-lk-compare-search />
					<span class="lk-cmp-picker__spinner" data-lk-compare-spinner hidden>…</span>
				</div>
				<div class="lk-cmp-suggest" data-lk-compare-suggest hidden></div>
			</div>

			<div class="lk-cmp-actions">
				<button type="button" class="lk-btn lk-btn--primary" data-lk-compare-run>مقایسه کن</button>
				<button type="button" class="lk-chipbtn" data-lk-compare-clear>پاک‌کردن انتخاب‌ها</button>
				<button type="button" class="lk-chipbtn" data-lk-compare-share><?php echo lk_icon( 'external', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> کپی پیوند مقایسه</button>
				<button type="button" class="lk-chipbtn" data-lk-print><?php echo lk_icon( 'download', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> چاپ / PDF</button>
			</div>
		</div>

		<div class="lk-cmp-results" data-lk-compare-results aria-live="polite">
			<?php if ( $lk_data && ! empty( $lk_data['rows'] ) ) : ?>
				<?php get_template_part( 'template-parts/compare/table', null, $lk_data ); ?>
			<?php else : ?>
				<?php
				get_template_part(
					'template-parts/components/empty-data',
					null,
					array(
						'title' => 'برای شروع، خودروها را انتخاب کنید',
						'text'  => $lk_data && ! empty( $lk_data['error'] ) ? $lk_data['error'] : 'با جستجوی نام خودرو، آن را به فهرست مقایسه اضافه کنید. نتیجه در همین صفحه ساخته می‌شود.',
					)
				);
				?>
			<?php endif; ?>
		</div>

		<?php if ( get_the_content() ) : ?>
			<div class="lk-prose lk-prose--below"><?php the_content(); ?></div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
