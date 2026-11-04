<?php
/**
 * 404 page.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="lk-notfound">
	<div class="lk-notfound__pattern" aria-hidden="true"></div>
	<div class="lk-container lk-notfound__inner">
		<span class="lk-notfound__code" aria-hidden="true">۴۰۴</span>
		<h1 class="lk-notfound__title">این صفحه پیدا نشد</h1>
		<p class="lk-notfound__text">ممکن است نشانی اشتباه باشد یا صفحه حذف شده باشد. از میان مسیرهای زیر ادامه دهید یا خودروی موردنظر را جستجو کنید.</p>

		<form class="lk-notfound__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="lk-404-search">جستجو</label>
			<?php echo lk_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<input type="search" id="lk-404-search" name="s" placeholder="نام خودرو یا مطلب…" />
			<button type="submit" class="lk-btn lk-btn--primary">جستجو</button>
		</form>

		<ul class="lk-notfound__links">
			<li><a href="<?php echo esc_url( lk_page_url( 'prices' ) ); ?>">قیمت روز خودرو</a></li>
			<li><a href="<?php echo esc_url( lk_page_url( 'vehicles' ) ); ?>">بانک اطلاعات خودرو</a></li>
			<li><a href="<?php echo esc_url( lk_page_url( 'brands' ) ); ?>">برندها</a></li>
			<li><a href="<?php echo esc_url( lk_page_url( 'compare' ) ); ?>">مقایسه خودرو</a></li>
			<li><a href="<?php echo esc_url( lk_blog_url() ); ?>">اخبار و مقالات</a></li>
		</ul>
	</div>
</section>
<?php
get_footer();
