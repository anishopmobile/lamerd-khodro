<?php
/**
 * Customizer options for the theme.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defaults in one place.
 *
 * @return array<string,mixed>
 */
function lk_theme_defaults() {
	return array(
		'hero_eyebrow'       => 'مرجع قیمت و اطلاعات خودرو',
		'hero_title'         => 'قیمت بازار را شفاف ببینید.',
		'hero_text'          => 'قیمت روز خودروهای داخلی و وارداتی، مشخصات فنی ساختاریافته، تاریخچه تغییرات قیمت و مقایسه دقیق؛ همه در یک مرجع فارسی.',
		'hero_cta_label'     => 'مشاهده قیمت روز',
		'hero_cta_url'       => '',
		'hero_note'          => 'قیمت‌ها از منبع رسمی دریافت و در دیتابیس داخلی ذخیره می‌شوند.',
		'price_note'         => 'قیمت‌ها هر ساعت به‌روزرسانی می‌شوند. آخرین وضعیت دریافت در بالای جدول نمایش داده می‌شود.',
		'contact_phone'      => '',
		'contact_email'      => '',
		'contact_address'    => '',
		'contact_hours'      => '',
		'social_instagram'   => '',
		'social_telegram'    => '',
		'social_whatsapp'    => '',
		'social_x'           => '',
		'social_aparat'      => '',
		'footer_about'       => 'لامرد خودرو مرجع قیمت روز، مشخصات فنی و مقایسه خودروهای داخلی و وارداتی است. داده‌های قیمت از منبع مشخص دریافت و در تاریخچه دائمی نگهداری می‌شوند.',
		'footer_copyright'   => '',
		'show_brands'        => true,
		'show_movers'        => true,
		'show_news'          => true,
		'show_reviews'       => true,
		'show_sales'         => true,
		'show_compare'       => true,
	);
}

/**
 * Register customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 * @return void
 */
function lk_customize_register( $wp_customize ) {
	$defaults = lk_theme_defaults();

	$wp_customize->add_panel(
		'lk_panel',
		array(
			'title'       => 'لامرد خودرو',
			'description' => 'تنظیمات محتوای صفحه اصلی، اطلاعات تماس و بخش‌های سایت.',
			'priority'    => 25,
		)
	);

	$wp_customize->add_section(
		'lk_hero',
		array(
			'title' => 'صفحه اصلی',
			'panel' => 'lk_panel',
		)
	);

	$hero_fields = array(
		'hero_eyebrow'   => array( 'برچسب بالای عنوان', 'text', 'sanitize_text_field' ),
		'hero_title'     => array( 'عنوان اصلی', 'text', 'sanitize_text_field' ),
		'hero_text'      => array( 'توضیح معرفی', 'textarea', 'sanitize_textarea_field' ),
		'hero_cta_label' => array( 'متن دکمه اصلی', 'text', 'sanitize_text_field' ),
		'hero_cta_url'   => array( 'لینک دکمه اصلی', 'url', 'esc_url_raw' ),
		'hero_note'      => array( 'یادداشت اعتماد', 'text', 'sanitize_text_field' ),
		'price_note'     => array( 'توضیح صفحه قیمت', 'textarea', 'sanitize_textarea_field' ),
	);

	foreach ( $hero_fields as $key => $field ) {
		$wp_customize->add_setting(
			'lk_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => $field[2],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'lk_' . $key,
			array(
				'label'   => $field[0],
				'section' => 'lk_hero',
				'type'    => $field[1],
			)
		);
	}

	$wp_customize->add_section(
		'lk_contact',
		array(
			'title' => 'اطلاعات تماس',
			'panel' => 'lk_panel',
		)
	);

	$contact_fields = array(
		'contact_phone'   => array( 'شماره تماس', 'text' ),
		'contact_email'   => array( 'ایمیل', 'text' ),
		'contact_address' => array( 'نشانی', 'textarea' ),
		'contact_hours'   => array( 'ساعات پاسخ‌گویی', 'text' ),
	);

	foreach ( $contact_fields as $key => $field ) {
		$wp_customize->add_setting(
			'lk_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		);
		$wp_customize->add_control(
			'lk_' . $key,
			array(
				'label'   => $field[0],
				'section' => 'lk_contact',
				'type'    => $field[1],
			)
		);
	}

	$wp_customize->add_section(
		'lk_social',
		array(
			'title' => 'شبکه‌های اجتماعی',
			'panel' => 'lk_panel',
		)
	);

	foreach ( array( 'instagram', 'telegram', 'whatsapp', 'x', 'aparat' ) as $network ) {
		$wp_customize->add_setting(
			'lk_social_' . $network,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'lk_social_' . $network,
			array(
				'label'   => 'نشانی ' . $network,
				'section' => 'lk_social',
				'type'    => 'url',
			)
		);
	}

	$wp_customize->add_section(
		'lk_sections',
		array(
			'title'       => 'بخش‌های صفحه اصلی',
			'description' => 'نمایش یا پنهان‌کردن بخش‌ها بدون تغییر کد.',
			'panel'       => 'lk_panel',
		)
	);

	$toggles = array(
		'show_brands'  => 'نوار برندها',
		'show_movers'  => 'بیشترین تغییرات قیمت',
		'show_news'    => 'اخبار و مقالات',
		'show_reviews' => 'بررسی خودرو',
		'show_sales'   => 'شرایط فروش',
		'show_compare' => 'دعوت به مقایسه',
	);

	foreach ( $toggles as $key => $label ) {
		$wp_customize->add_setting(
			'lk_' . $key,
			array(
				'default'           => $defaults[ $key ],
				'sanitize_callback' => 'wp_validate_boolean',
			)
		);
		$wp_customize->add_control(
			'lk_' . $key,
			array(
				'label'   => $label,
				'section' => 'lk_sections',
				'type'    => 'checkbox',
			)
		);
	}

	$wp_customize->add_section(
		'lk_footer',
		array(
			'title' => 'فوتر',
			'panel' => 'lk_panel',
		)
	);

	$wp_customize->add_setting(
		'lk_footer_about',
		array(
			'default'           => $defaults['footer_about'],
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'lk_footer_about',
		array(
			'label'   => 'درباره سایت در فوتر',
			'section' => 'lk_footer',
			'type'    => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'lk_footer_copyright',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'lk_footer_copyright',
		array(
			'label'       => 'متن کپی‌رایت',
			'description' => 'اگر خالی باشد، «© سال — نام سایت» نمایش داده می‌شود.',
			'section'     => 'lk_footer',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'lk_customize_register' );

/**
 * Checkbox helper for templates.
 *
 * @param string $key Setting key.
 * @return bool
 */
function lk_toggle( $key ) {
	$defaults = lk_theme_defaults();
	return (bool) get_theme_mod( 'lk_' . $key, $defaults[ $key ] ?? false );
}
