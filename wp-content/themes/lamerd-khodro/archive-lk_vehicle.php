<?php
/**
 * Vehicle archive: the structured vehicle database.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_paged        = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$lk_brand        = isset( $_GET['brand'] ) ? sanitize_title( wp_unslash( $_GET['brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_sort         = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_availability = isset( $_GET['availability'] ) ? sanitize_key( wp_unslash( $_GET['availability'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_search       = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$lk_sorts = array(
	'newest'      => 'جدیدترین',
	'name'        => 'حروف الفبا',
	'price_desc'  => 'بیشترین قیمت',
	'price_asc'   => 'کمترین قیمت',
	'change_desc' => 'بیشترین افزایش',
	'change_asc'  => 'بیشترین کاهش',
	'popular'     => 'پربازدیدترین',
);
if ( ! isset( $lk_sorts[ $lk_sort ] ) ) {
	$lk_sort = 'newest';
}

$lk_query = class_exists( 'LK_Query' ) ? LK_Query::vehicles(
	array(
		'posts_per_page' => 12,
		'paged'          => $lk_paged,
		'sort'           => $lk_sort,
		'brand'          => $lk_brand ? array( $lk_brand ) : array(),
		'availability'   => $lk_availability,
		'search'         => $lk_search,
	)
) : new WP_Query(
	array(
		'post_type'      => 'lk_vehicle',
		'posts_per_page' => 12,
		'paged'          => $lk_paged,
	)
);

$lk_posts  = function_exists( 'lk_query_with_prices' ) ? lk_query_with_prices( $lk_query ) : $lk_query->posts;
$lk_brands = function_exists( 'lk_get_brands' ) ? lk_get_brands( 0, true ) : array();

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => 'بانک اطلاعات خودرو',
		'text'  => 'فهرست خودروهای داخلی و وارداتی با قیمت ثبت‌شده، وضعیت تولید و مشخصات فنی. برای دیدن جزئیات کامل روی هر خودرو بزنید.',
		'meta'  => '<span class="lk-pagehero__badge">' . esc_html( sprintf( '%s خودرو', lk_num( (int) $lk_query->found_posts ) ) ) . '</span>',
	)
);
?>

<section class="lk-section lk-section--tight">
	<div class="lk-container">
		<form class="lk-filters lk-filters--vehicles" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'lk_vehicle' ) ); ?>">
			<div class="lk-filters__search">
				<label class="screen-reader-text" for="lk-vehicles-search">جستجوی خودرو</label>
				<?php echo lk_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="search" id="lk-vehicles-search" name="search" value="<?php echo esc_attr( $lk_search ); ?>" placeholder="نام خودرو یا برند" />
			</div>

			<div class="lk-filters__field">
				<label for="lk-vehicles-brand">برند</label>
				<select id="lk-vehicles-brand" name="brand">
					<option value="">همه برندها</option>
					<?php foreach ( $lk_brands as $lk_term ) : ?>
						<option value="<?php echo esc_attr( $lk_term->slug ); ?>" <?php selected( $lk_brand, $lk_term->slug ); ?>>
							<?php echo esc_html( $lk_term->name ); ?> (<?php echo esc_html( lk_num( $lk_term->count ) ); ?>)
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="lk-filters__field">
				<label for="lk-vehicles-sort">ترتیب</label>
				<select id="lk-vehicles-sort" name="sort">
					<?php foreach ( $lk_sorts as $lk_key => $lk_label ) : ?>
						<option value="<?php echo esc_attr( $lk_key ); ?>" <?php selected( $lk_sort, $lk_key ); ?>><?php echo esc_html( $lk_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="lk-filters__field">
				<label for="lk-vehicles-availability">وضعیت تولید</label>
				<select id="lk-vehicles-availability" name="availability">
					<option value="">همه</option>
					<option value="available" <?php selected( $lk_availability, 'available' ); ?>>در حال تولید</option>
					<option value="stopped" <?php selected( $lk_availability, 'stopped' ); ?>>متوقف‌شده</option>
				</select>
			</div>

			<div class="lk-filters__actions">
				<button type="submit" class="lk-btn lk-btn--primary">اعمال فیلتر</button>
			</div>
		</form>

		<?php if ( $lk_posts ) : ?>
			<div class="lk-grid lk-grid--4 lk-grid--vehicles">
				<?php foreach ( $lk_posts as $lk_post ) : ?>
					<?php get_template_part( 'template-parts/vehicle/card', null, array( 'post' => $lk_post ) ); ?>
				<?php endforeach; ?>
			</div>

			<?php lk_pagination( $lk_query ); ?>
		<?php else : ?>
			<?php
			get_template_part(
				'template-parts/components/empty-data',
				null,
				array(
					'title'      => 'خودرویی با این مشخصات پیدا نشد',
					'text'       => 'عبارت جستجو یا فیلتر برند را تغییر دهید.',
					'link_label' => 'مشاهده همه خودروها',
					'link_url'   => get_post_type_archive_link( 'lk_vehicle' ),
				)
			);
			?>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
