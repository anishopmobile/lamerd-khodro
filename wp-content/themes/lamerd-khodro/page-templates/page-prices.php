<?php
/**
 * Template Name: قیمت روز خودرو
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

$lk_filters = function_exists( 'lk_read_price_filters' ) ? lk_read_price_filters( $_GET ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$lk_result  = function_exists( 'lk_get_prices' ) ? lk_get_prices( $lk_filters ) : array( 'items' => array(), 'total' => 0, 'pages' => 0, 'page' => 1 );
$lk_stats   = function_exists( 'lk_get_stats' ) ? lk_get_stats() : array();
$lk_brands  = function_exists( 'lk_get_brands' ) ? lk_get_brands( 10, true ) : array();

get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => get_the_title(),
		'text'  => 'قیمت بازار و قیمت کارخانه خودروهای داخلی و وارداتی، همراه با تغییرات و وضعیت داده. قیمت‌ها از دیتابیس داخلی لامرد خودرو خوانده می‌شوند.',
		'meta'  => function_exists( 'lk_update_note' ) ? '' : '',
	)
);
?>

<section class="lk-section lk-section--tight">
	<div class="lk-container">

		<?php if ( ! empty( $lk_stats['with_price'] ) ) : ?>
			<div class="lk-stats">
				<div class="lk-stat lk-stat--plain">
					<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['with_price'] ) ); ?></span>
					<span class="lk-stat__label">خودرو با قیمت ثبت‌شده</span>
				</div>
				<div class="lk-stat lk-stat--up lk-stat--plain">
					<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['up'] ) ); ?></span>
					<span class="lk-stat__label">افزایش قیمت</span>
				</div>
				<div class="lk-stat lk-stat--down lk-stat--plain">
					<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['down'] ) ); ?></span>
					<span class="lk-stat__label">کاهش قیمت</span>
				</div>
				<div class="lk-stat lk-stat--plain">
					<span class="lk-stat__value"><?php echo esc_html( lk_num( $lk_stats['flat'] ) ); ?></span>
					<span class="lk-stat__label">بدون تغییر</span>
				</div>
			</div>
		<?php endif; ?>

		<form class="lk-filters" method="get" action="<?php echo esc_url( get_permalink() ); ?>" data-lk-price-filters>
			<div class="lk-filters__search">
				<label class="screen-reader-text" for="lk-price-search">جستجوی خودرو</label>
				<?php echo lk_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="search" id="lk-price-search" name="search" value="<?php echo esc_attr( $lk_filters['search'] ?? '' ); ?>" placeholder="نام خودرو یا برند" />
			</div>

			<div class="lk-filters__field">
				<label for="lk-price-brand">برند</label>
				<select id="lk-price-brand" name="brand">
					<option value="">همه برندها</option>
					<?php foreach ( $lk_brands as $lk_brand ) : ?>
						<option value="<?php echo esc_attr( $lk_brand->slug ); ?>" <?php selected( in_array( $lk_brand->slug, (array) ( $lk_filters['brand'] ?? array() ), true ) ); ?>>
							<?php echo esc_html( $lk_brand->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="lk-filters__field">
				<label for="lk-price-sort">ترتیب</label>
				<select id="lk-price-sort" name="sort">
					<?php foreach ( lk_sort_options() as $lk_key => $lk_label ) : ?>
						<option value="<?php echo esc_attr( $lk_key ); ?>" <?php selected( $lk_filters['sort'] ?? 'price_desc', $lk_key ); ?>>
							<?php echo esc_html( $lk_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="lk-filters__field">
				<label for="lk-price-availability">وضعیت تولید</label>
				<select id="lk-price-availability" name="availability">
					<option value="">همه</option>
					<option value="available" <?php selected( $lk_filters['availability'] ?? '', 'available' ); ?>>در حال تولید</option>
					<option value="stopped" <?php selected( $lk_filters['availability'] ?? '', 'stopped' ); ?>>متوقف‌شده</option>
				</select>
			</div>

			<div class="lk-filters__field lk-filters__field--range">
				<label>محدوده قیمت (میلیون تومان)</label>
				<div class="lk-filters__range">
					<input type="number" name="price_min" min="0" step="10" placeholder="از" value="<?php echo esc_attr( ( $lk_filters['price_min'] ?? 0 ) ? (int) ( $lk_filters['price_min'] / 1000000 ) : '' ); ?>" />
					<span aria-hidden="true">—</span>
					<input type="number" name="price_max" min="0" step="10" placeholder="تا" value="<?php echo esc_attr( ( $lk_filters['price_max'] ?? 0 ) ? (int) ( $lk_filters['price_max'] / 1000000 ) : '' ); ?>" />
				</div>
			</div>

			<div class="lk-filters__actions">
				<button type="submit" class="lk-btn lk-btn--primary">اعمال فیلتر</button>
				<a class="lk-filters__reset" href="<?php echo esc_url( get_permalink() ); ?>">حذف فیلترها</a>
			</div>
		</form>

		<?php if ( $lk_brands ) : ?>
			<div class="lk-chips" aria-label="فیلتر سریع برند">
				<a class="lk-chip <?php echo empty( $lk_filters['brand'] ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_permalink() ); ?>">همه</a>
				<?php foreach ( $lk_brands as $lk_brand ) : ?>
					<a class="lk-chip <?php echo in_array( $lk_brand->slug, (array) ( $lk_filters['brand'] ?? array() ), true ) ? 'is-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( 'brand', $lk_brand->slug, get_permalink() ) ); ?>">
						<?php echo esc_html( $lk_brand->name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="lk-results-bar">
			<div class="lk-results-bar__count">
				<span data-lk-price-count><?php echo esc_html( sprintf( '%s خودرو', lk_num( (int) $lk_result['total'] ) ) ); ?></span>
				<?php if ( function_exists( 'lk_update_note' ) ) : ?>
					<?php lk_update_note(); ?>
				<?php endif; ?>
			</div>

			<div class="lk-results-bar__actions">
				<button type="button" class="lk-chipbtn" data-lk-print>
					<?php echo lk_icon( 'download', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					چاپ / PDF
				</button>
			</div>
		</div>

		<div data-lk-price-results>
			<?php if ( ! empty( $lk_result['items'] ) ) : ?>
				<?php
				get_template_part(
					'template-parts/price/table',
					null,
					array(
						'items'   => $lk_result['items'],
						'compact' => false,
						'ajax'    => true,
					)
				);
				?>
			<?php else : ?>
				<?php
				get_template_part(
					'template-parts/components/empty-data',
					null,
					array(
						'title' => 'خودرویی با این فیلترها پیدا نشد',
						'text'  => 'فیلترها را تغییر دهید یا همه قیمت‌ها را ببینید.',
						'link_label' => 'حذف فیلترها',
						'link_url'   => get_permalink(),
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div data-lk-price-pagination class="lk-pagination-wrap">
			<?php echo lk_render_ajax_pagination( (int) $lk_result['page'], (int) $lk_result['pages'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<div class="lk-price-note">
			<p>
				<?php echo esc_html( lk_opt( 'price_note' ) ); ?>
			</p>
			<?php lk_attribution_line(); ?>
		</div>

		<?php
		$lk_content = get_post()->post_content;
		if ( $lk_content ) :
			?>
			<div class="lk-prose lk-prose--below"><?php the_content(); ?></div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
