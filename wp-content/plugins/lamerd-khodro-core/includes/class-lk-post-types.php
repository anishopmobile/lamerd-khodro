<?php
/**
 * Vehicle custom post type.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Post_Types
 */
class LK_Post_Types {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
	}

	/**
	 * Register the vehicle post type.
	 *
	 * @return void
	 */
	public static function register() {
		$labels = array(
			'name'                  => 'خودروها',
			'singular_name'         => 'خودرو',
			'menu_name'             => 'خودروها',
			'name_admin_bar'        => 'خودرو',
			'add_new'               => 'افزودن خودرو',
			'add_new_item'          => 'افزودن خودرو جدید',
			'new_item'              => 'خودرو جدید',
			'edit_item'             => 'ویرایش خودرو',
			'view_item'             => 'مشاهده خودرو',
			'view_items'            => 'مشاهده خودروها',
			'all_items'             => 'همه خودروها',
			'search_items'          => 'جستجوی خودرو',
			'not_found'             => 'خودرویی یافت نشد.',
			'not_found_in_trash'    => 'خودرویی در زباله‌دان نیست.',
			'featured_image'        => 'تصویر شاخص خودرو',
			'set_featured_image'    => 'انتخاب تصویر شاخص',
			'remove_featured_image' => 'حذف تصویر شاخص',
			'use_featured_image'    => 'استفاده به‌عنوان تصویر شاخص',
			'archives'              => 'آرشیو خودروها',
			'attributes'            => 'ویژگی‌ها',
			'insert_into_item'      => 'درج در خودرو',
			'uploaded_to_this_item' => 'بارگذاری‌شده برای این خودرو',
			'filter_items_list'     => 'فیلتر فهرست خودروها',
			'items_list_navigation' => 'پیمایش فهرست خودروها',
			'items_list'            => 'فهرست خودروها',
		);

		register_post_type(
			'lk_vehicle',
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'menu_position'      => 26,
				'menu_icon'          => 'dashicons-car',
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'        => 'vehicles',
				'rewrite'            => array(
					'slug'       => 'vehicles',
					'with_front' => false,
				),
				'show_in_rest'       => true,
				'rest_base'          => 'lk-vehicles',
				'taxonomies'         => array( 'lk_brand' ),
			)
		);
	}
}
