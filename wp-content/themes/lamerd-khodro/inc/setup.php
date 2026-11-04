<?php
/**
 * Theme setup, menus, image sizes and initial site structure.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme supports.
 *
 * @return void
 */
function lk_theme_setup() {
	load_theme_textdomain( 'lamerd-khodro', LK_THEME_PATH . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-logo', array( 'height' => 64, 'width' => 220, 'flex-height' => true, 'flex-width' => true ) );

	register_nav_menus(
		array(
			'primary' => 'منوی اصلی',
			'footer'  => 'منوی فوتر',
			'legal'   => 'منوی پایین صفحه',
		)
	);

	add_image_size( 'lk-card', 760, 460, true );
	add_image_size( 'lk-wide', 1200, 640, true );

	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 820;
	}
}
add_action( 'after_setup_theme', 'lk_theme_setup' );

/**
 * Widget areas.
 *
 * @return void
 */
function lk_theme_widgets() {
	register_sidebar(
		array(
			'name'          => 'ستون کنار مقاله',
			'id'            => 'sidebar-article',
			'description'   => 'ابزارک‌های کنار مطالب',
			'before_widget' => '<section id="%1$s" class="lk-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="lk-widget__title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'lk_theme_widgets' );

/**
 * Reading settings sensible defaults.
 *
 * @return void
 */
function lk_theme_content_defaults() {
	add_filter(
		'excerpt_length',
		static function () {
			return 28;
		}
	);
	add_filter(
		'excerpt_more',
		static function () {
			return ' …';
		}
	);
}
add_action( 'after_setup_theme', 'lk_theme_content_defaults' );

/**
 * Body classes.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function lk_theme_body_classes( $classes ) {
	$classes[] = 'lk-body';

	if ( is_front_page() ) {
		$classes[] = 'lk-home';
	}

	if ( ! is_active_sidebar( 'sidebar-article' ) ) {
		$classes[] = 'lk-no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'lk_theme_body_classes' );

/**
 * Menu fallback: primary links even before a menu is assigned.
 *
 * @return void
 */
function lk_theme_menu_fallback( $args = array() ) {
	$location = is_array( $args ) ? ( $args['theme_location'] ?? 'primary' ) : 'primary';
	$list_class = 'footer' === $location ? 'lk-footer__list' : 'lk-nav__list';

	$links = array(
		array( 'خانه', home_url( '/' ) ),
		array( 'قیمت روز خودرو', lk_page_url( 'prices' ) ),
		array( 'بانک اطلاعات خودرو', lk_page_url( 'vehicles' ) ),
		array( 'برندها', lk_page_url( 'brands' ) ),
		array( 'مقایسه خودرو', lk_page_url( 'compare' ) ),
		array( 'اخبار و مقالات', lk_blog_url() ),
	);

	echo '<ul class="' . esc_attr( $list_class ) . '">';
	foreach ( $links as $link ) {
		echo '<li class="menu-item"><a href="' . esc_url( $link[1] ) . '">' . esc_html( $link[0] ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Find a page URL by a stable slug, with graceful fallback.
 *
 * @param string $key prices|vehicles|compare|brands.
 * @return string
 */
function lk_page_url( $key ) {
	$map = array(
		'prices'   => 'prices',
		'vehicles' => 'vehicles',
		'compare'  => 'compare',
		'brands'   => 'brands',
	);

	$slug = $map[ $key ] ?? '';

	if ( 'vehicles' === $slug ) {
		$page = get_page_by_path( 'vehicles' );
		if ( ! $page ) {
			return get_post_type_archive_link( 'lk_vehicle' );
		}
	}

	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/**
 * Blog page URL.
 *
 * @return string
 */
function lk_blog_url() {
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page ) {
		$url = get_permalink( $posts_page );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/' );
}

/**
 * Activate theme: create pages, menus and initial (non-fabricated) content.
 *
 * @return void
 */
function lk_theme_activate() {
	lk_create_pages_and_menu();
	lk_seed_site_content();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'lk_theme_activate' );

/**
 * Create the page skeleton and menus once.
 *
 * @return void
 */
function lk_create_pages_and_menu() {
	$pages = array(
		'home'      => array( 'خانه', '', 'default' ),
		'prices'    => array( 'قیمت روز خودرو', '', 'page-templates/page-prices.php' ),
		'brands'    => array( 'برندهای خودرو', '', 'page-templates/page-brands.php' ),
		'compare'   => array( 'مقایسه خودرو', '', 'page-templates/page-compare.php' ),
		'news'      => array( 'اخبار و مقالات', '', 'default' ),
		'about'     => array( 'درباره لامرد خودرو', '', 'default' ),
		'contact'   => array( 'تماس با ما', '', 'default' ),
		'terms'     => array( 'قوانین و حریم خصوصی', '', 'default' ),
	);

	$created = array();

	foreach ( $pages as $key => $config ) {
		$existing = get_page_by_path( $key );
		if ( $existing ) {
			$created[ $key ] = (int) $existing->ID;
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $config[0],
				'post_name'   => $key,
				'post_content' => lk_default_page_content( $key ),
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			$created[ $key ] = (int) $page_id;
			if ( $config[2] && 'default' !== $config[2] ) {
				update_post_meta( $page_id, '_wp_page_template', $config[2] );
			}
		}
	}

	if ( ! empty( $created['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $created['home'] );
	}
	if ( ! empty( $created['news'] ) ) {
		update_option( 'page_for_posts', $created['news'] );
	}

	$primary = get_option( 'lk_primary_menu_id' );
	if ( ! $primary || ! wp_get_nav_menu_object( $primary ) ) {
		$menu_id = wp_create_nav_menu( 'منوی اصلی لامرد خودرو' );
		if ( ! is_wp_error( $menu_id ) ) {
			$items = array(
				array( 'خانه', home_url( '/' ), 0 ),
				array( 'قیمت روز خودرو', isset( $created['prices'] ) ? get_permalink( $created['prices'] ) : '', 0 ),
				array( 'بانک اطلاعات خودرو', get_post_type_archive_link( 'lk_vehicle' ), 0 ),
				array( 'برندها', isset( $created['brands'] ) ? get_permalink( $created['brands'] ) : '', 0 ),
				array( 'مقایسه خودرو', isset( $created['compare'] ) ? get_permalink( $created['compare'] ) : '', 0 ),
				array( 'اخبار و مقالات', isset( $created['news'] ) ? get_permalink( $created['news'] ) : '', 0 ),
			);

			foreach ( $items as $item ) {
				if ( ! $item[1] ) {
					continue;
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'  => $item[0],
						'menu-item-url'    => $item[1],
						'menu-item-status' => 'publish',
					)
				);
			}

			$locations            = get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			update_option( 'lk_primary_menu_id', $menu_id, false );
		}
	}

	$footer_id = (int) get_option( 'lk_footer_menu_id' );
	if ( ! $footer_id || ! wp_get_nav_menu_object( $footer_id ) ) {
		$footer_id = wp_create_nav_menu( 'منوی فوتر لامرد خودرو' );
		if ( ! is_wp_error( $footer_id ) ) {
			$footer_items = array(
				array( 'قیمت روز خودرو', isset( $created['prices'] ) ? get_permalink( $created['prices'] ) : '' ),
				array( 'بانک اطلاعات خودرو', get_post_type_archive_link( 'lk_vehicle' ) ),
				array( 'مقایسه خودرو', isset( $created['compare'] ) ? get_permalink( $created['compare'] ) : '' ),
				array( 'برندها', isset( $created['brands'] ) ? get_permalink( $created['brands'] ) : '' ),
				array( 'درباره لامرد خودرو', isset( $created['about'] ) ? get_permalink( $created['about'] ) : '' ),
				array( 'تماس با ما', isset( $created['contact'] ) ? get_permalink( $created['contact'] ) : '' ),
				array( 'قوانین و حریم خصوصی', isset( $created['terms'] ) ? get_permalink( $created['terms'] ) : '' ),
			);

			foreach ( $footer_items as $footer_item ) {
				if ( ! $footer_item[1] ) {
					continue;
				}
				wp_update_nav_menu_item(
					$footer_id,
					0,
					array(
						'menu-item-title'  => $footer_item[0],
						'menu-item-url'    => $footer_item[1],
						'menu-item-status' => 'publish',
					)
				);
			}

			$locations            = get_theme_mod( 'nav_menu_locations', array() );
			$locations['footer']  = $footer_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			update_option( 'lk_footer_menu_id', $footer_id, false );
		}
	}

	if ( ! get_option( 'lk_pages_created' ) ) {
		update_option( 'lk_pages_created', 1, false );
	}
}

/**
 * Default content for core pages (site information only).
 *
 * @param string $key Page key.
 * @return string
 */
function lk_default_page_content( $key ) {
	switch ( $key ) {
		case 'about':
			return '<h2>لامرد خودرو چیست؟</h2>
<p>لامرد خودرو یک پلتفرم تخصصی خودرو است که با هدف شفاف‌کردن بازار خودرو ایران راه‌اندازی شده است. در این سایت قیمت روز خودروهای داخلی و وارداتی، مشخصات فنی ساختاریافته، تاریخچه تغییرات قیمت و امکان مقایسه خودروها در یک رابط فارسی و راست‌چین ارائه می‌شود.</p>
<h2>اصول ما</h2>
<ul><li>قیمت‌ها تنها از منبع مشخص و با درج نام منبع منتشر می‌شوند.</li><li>هیچ قیمت ساختگی یا تخمینی به‌عنوان قیمت بازار نمایش داده نمی‌شود.</li><li>تاریخچه قیمت از زمان فعال‌شدن سامانه ثبت و حفظ می‌شود و قابل حذف نیست.</li><li>مشخصات فنی فقط در صورت ثبت مستند نمایش داده می‌شوند؛ فیلد خالی به‌معنای اطلاعات ناموجود است.</li></ul>
<h2>توسعه آینده</h2>
<p>معماری این پلتفرم برای افزودن سرویس‌های بیشتر در حوزه خودرو و اتصال اپلیکیشن موبایل در آینده طراحی شده است.</p>';

		case 'contact':
			return '<h2>در ارتباط باشید</h2>
<p>برای ارسال پیشنهاد، گزارش خطا در اطلاعات خودروها یا همکاری با لامرد خودرو از راه‌های ارتباطی زیر استفاده کنید.</p>
<p>اطلاعات تماس و شبکه‌های اجتماعی از بخش «سفارشی‌سازی ← لامرد خودرو ← اطلاعات تماس» قابل تنظیم است.</p>';

		case 'terms':
			return '<h2>قوانین استفاده</h2>
<p>استفاده از اطلاعات این سایت آزاد است، اما بازنشر داده‌های قیمت بدون درج منبع مجاز نیست. منبع قیمت‌ها «کافه خودرو» است و لینک آن در صفحات قیمت نمایش داده می‌شود.</p>
<h2>حریم خصوصی</h2>
<p>این سایت برای ارائه اطلاعات نیازی به ثبت‌نام ندارد و اطلاعات شخصی کاربران را جمع‌آوری نمی‌کند. شمارنده بازدید خودروها به‌صورت تجمیعی و بدون شناسه شخصی ذخیره می‌شود. برای محدودسازی درخواست‌های غیرعادی، نشانی IP فقط به‌صورت موقت و هش‌شده برای محدودیت نرخ استفاده می‌شود.</p>
<h2>دقت اطلاعات</h2>
<p>قیمت‌ها توسط منبع اعلام می‌شوند و ممکن است با قیمت معاملات واقعی تفاوت داشته باشند. لامرد خودرو مسئولیتی در قبال تصمیم‌های مالی کاربران بر اساس این اطلاعات ندارد.</p>';

		case 'prices':
			return '';
		default:
			return '';
	}
}

/**
 * Seed categories and a small number of genuine site articles.
 *
 * No market prices or news are fabricated here: these are factual guides
 * written for the site itself.
 *
 * @return void
 */
function lk_seed_site_content() {
	if ( get_option( 'lk_content_seeded' ) ) {
		return;
	}

	$categories = array(
		'اخبار خودرو'        => 'car-news',
		'اخبار داخلی'        => 'domestic-news',
		'اخبار خارجی'        => 'foreign-news',
		'ایران خودرو'        => 'iran-khodro',
		'سایپا'              => 'saipa',
		'خودروهای وارداتی'   => 'imported-cars',
		'خودروهای چینی'      => 'chinese-cars',
		'شرایط فروش'         => 'sales-conditions',
		'قیمت خودرو'         => 'car-prices',
		'بررسی خودرو'        => 'car-reviews',
		'فناوری خودرو'       => 'car-technology',
		'صنعت خودرو'         => 'car-industry',
	);

	$term_ids = array();
	foreach ( $categories as $name => $slug ) {
		$term = term_exists( $slug, 'category' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
		if ( ! is_wp_error( $term ) ) {
			$term_ids[ $slug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
		}
	}

	$posts = array(
		array(
			'title'    => 'لامرد خودرو آغاز به کار کرد؛ قیمت شفاف، داده ساختاریافته',
			'category' => 'car-news',
			'slug'     => 'lamerd-khodro-launch',
			'excerpt'  => 'پلتفرم تخصصی لامرد خودرو با تمرکز بر قیمت روز خودرو، مشخصات فنی ساختاریافته و تاریخچه دائمی قیمت راه‌اندازی شد.',
			'content'  => '<p>لامرد خودرو به‌عنوان یک پلتفرم تخصصی خودرو شروع به کار کرد. هدف این سایت، ارائه اطلاعات شفاف و قابل اتکا از بازار خودرو ایران است: قیمت کارخانه و بازار، میزان تغییر، وضعیت تولید و امکان بررسی مشخصات فنی خودروها.</p>
<h2>چه چیزی متفاوت است؟</h2>
<p>در لامرد خودرو، قیمت‌ها در دیتابیس داخلی ذخیره می‌شوند و نمایش سایت به هیچ API خارجی وابسته نیست. اگر منبع اطلاعات در دسترس نباشد، آخرین قیمت معتبر با برچسب زمانی نمایش داده می‌شود و داده قبلی حذف نمی‌شود.</p>
<p>تاریخچه قیمت از زمان فعال‌شدن سامانه ثبت می‌شود؛ بنابراین نمودارها فقط داده واقعی ثبت‌شده را نشان می‌دهند و هیچ داده تاریخی بازسازی‌شده‌ای تولید نمی‌شود.</p>
<h2>گام بعدی</h2>
<p>بانک اطلاعات خودرو به‌تدریج با مشخصات فنی مستند تکمیل می‌شود. تا زمانی که مشخصات یک خودرو ثبت نشده باشد، آن بخش خالی می‌ماند و اطلاعات حدسی نمایش داده نمی‌شود.</p>',
		),
		array(
			'title'    => 'چگونه جدول قیمت روز خودرو را درست بخوانیم؟',
			'category' => 'car-prices',
			'slug'     => 'how-to-read-price-table',
			'excerpt'  => 'قیمت کارخانه، قیمت بازار، اختلاف و درصد تغییر چهار عدد کلیدی هستند که باید با هم خوانده شوند.',
			'content'  => '<p>در جدول قیمت لامرد خودرو چهار عدد اصلی وجود دارد: قیمت کارخانه، قیمت بازار، اختلاف بازار و کارخانه و درصد تغییر نسبت به به‌روزرسانی قبلی. هر یک از این اعداد معنی متفاوتی دارند.</p>
<h2>قیمت کارخانه</h2>
<p>قیمتی است که سازنده یا عرضه‌کننده اعلام می‌کند. برای برخی خودروها این عدد اعلام نمی‌شود؛ در این حالت به‌جای صفر، عبارت «ناموجود» نمایش داده می‌شود تا با قیمت واقعی اشتباه گرفته نشود.</p>
<h2>قیمت بازار</h2>
<p>قیمتی که در بازار آزاد برای خودروی صفر مطرح است. این عدد می‌تواند با قیمت کارخانه فاصله زیادی داشته باشد؛ ستون «اختلاف» همین فاصله را نشان می‌دهد.</p>
<h2>درصد تغییر</h2>
<p>تغییر نسبت به آخرین به‌روزرسانی ثبت‌شده است، نه نسبت به ابتدای سال. برای مشاهده روند بلندمدت، از نمودار تاریخچه در صفحه هر خودرو استفاده کنید. اگر داده منبع با قیمت سازگار نباشد، تغییر نمایش داده نمی‌شود و وضعیت «نیازمند بررسی» ثبت می‌گردد.</p>
<h2>وضعیت تولید</h2>
<p>خودروهای متوقف‌شده معمولاً رفتار قیمتی متفاوتی دارند. این وضعیت از منبع دریافت می‌شود و در جدول قابل فیلتر است.</p>',
		),
		array(
			'title'    => 'راهنمای مقایسه خودرو؛ از قیمت تا امکانات',
			'category' => 'car-reviews',
			'slug'     => 'vehicle-comparison-guide',
			'excerpt'  => 'مقایسه درست خودرو فقط مقایسه قیمت نیست؛ پیشرانه، ابعاد، مصرف سوخت و امکانات باید در کنار هم دیده شوند.',
			'content'  => '<p>صفحه مقایسه لامرد خودرو امکان مقایسه دو تا چهار خودرو را در یک جدول فراهم می‌کند. برای اینکه نتیجه مقایسه گمراه‌کننده نباشد، به این نکات توجه کنید.</p>
<h2>۱. قیمت را در بستر خودش ببینید</h2>
<p>اختلاف قیمت بازار و کارخانه برای هر خودرو متفاوت است. خودرویی با قیمت بازار بالاتر ممکن است اختلاف کمتری با قیمت کارخانه داشته باشد.</p>
<h2>۲. مشخصات فنی را با واحد یکسان بخوانید</h2>
<p>حجم موتور، قدرت، گشتاور، مصرف سوخت و ابعاد با واحدهای مشخص نمایش داده می‌شوند. اگر مشخصات یک خودرو ثبت نشده باشد، در جدول «—» دیده می‌شود؛ این یعنی داده موجود نیست، نه اینکه خودرو فاقد آن ویژگی است.</p>
<h2>۳. امکانات را مورد‌به‌مورد بررسی کنید</h2>
<p>فهرست امکانات ایمنی و رفاهی به‌صورت متنی ثبت می‌شود. برای مقایسه دقیق، تعداد و نوع امکانات را با هم بسنجید و به تفاوت نام‌گذاری تجاری توجه کنید.</p>
<h2>۴. تفاوت‌ها را ببینید</h2>
<p>در جدول مقایسه، مقادیر متفاوت و بهترین مقدار عددی مشخص می‌شوند تا انتخاب سریع‌تر شود. با این حال، بهترین عدد همیشه بهترین انتخاب برای شما نیست؛ اولویت‌های خودتان را در نظر بگیرید.</p>',
		),
	);

	foreach ( $posts as $post ) {
		if ( get_page_by_path( $post['slug'], OBJECT, 'post' ) ) {
			continue;
		}
		$cat_id = $term_ids[ $post['category'] ] ?? 0;
		wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $post['title'],
				'post_name'    => $post['slug'],
				'post_excerpt' => $post['excerpt'],
				'post_content' => $post['content'],
				'post_category' => $cat_id ? array( $cat_id ) : array(),
			)
		);
	}

	update_option( 'lk_content_seeded', 1, false );
}

/**
 * Drop the "بایگانی‌ها:" prefix from archive document titles.
 *
 * @param string $prefix Prefix.
 * @return string
 */
function lk_archive_title_prefix( $prefix ) {
	return '';
}
add_filter( 'get_the_archive_title_prefix', 'lk_archive_title_prefix' );
