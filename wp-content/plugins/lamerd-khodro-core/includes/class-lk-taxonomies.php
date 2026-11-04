<?php
/**
 * Brands and vehicle classification taxonomies.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Taxonomies
 */
class LK_Taxonomies {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 6 );

		add_action( 'lk_brand_add_form_fields', array( __CLASS__, 'add_fields' ) );
		add_action( 'lk_brand_edit_form_fields', array( __CLASS__, 'edit_fields' ), 10, 2 );
		add_action( 'created_lk_brand', array( __CLASS__, 'save_fields' ) );
		add_action( 'edited_lk_brand', array( __CLASS__, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'init', array( __CLASS__, 'maybe_seed_terms' ), 20 );
	}

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 */
	public static function register() {
		register_taxonomy(
			'lk_brand',
			array( 'lk_vehicle' ),
			array(
				'labels'            => array(
					'name'          => 'برندها',
					'singular_name' => 'برند',
					'menu_name'     => 'برندها',
					'add_new_item'  => 'افزودن برند',
					'edit_item'     => 'ویرایش برند',
					'search_items'  => 'جستجوی برند',
					'all_items'     => 'همه برندها',
					'not_found'     => 'برندی یافت نشد.',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rest_base'         => 'lk-brands',
				'rewrite'           => array(
					'slug'       => 'brand',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'lk_body_type',
			array( 'lk_vehicle' ),
			array(
				'labels'            => array(
					'name'          => 'نوع بدنه',
					'singular_name' => 'نوع بدنه',
					'menu_name'     => 'نوع بدنه',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'body-type',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'lk_class',
			array( 'lk_vehicle' ),
			array(
				'labels'            => array(
					'name'          => 'کلاس خودرو',
					'singular_name' => 'کلاس خودرو',
					'menu_name'     => 'کلاس خودرو',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'class',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Brand term meta keys.
	 *
	 * @return array<string,string>
	 */
	public static function brand_meta_keys() {
		return array(
			'name_en' => 'text',
			'country' => 'text',
			'founded' => 'text',
			'website' => 'url',
			'logo_id' => 'int',
		);
	}

	/**
	 * Read brand meta.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $key     Key without prefix.
	 * @return mixed
	 */
	public static function brand_meta( $term_id, $key ) {
		return get_term_meta( $term_id, 'lk_brand_' . $key, true );
	}

	/**
	 * Logo URL for a brand.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $size    Image size.
	 * @return string
	 */
	public static function brand_logo( $term_id, $size = 'medium' ) {
		$logo_id = (int) self::brand_meta( $term_id, 'logo_id' );
		if ( $logo_id ) {
			$url = wp_get_attachment_image_url( $logo_id, $size );
			if ( $url ) {
				return $url;
			}
		}
		return '';
	}

	/**
	 * Add form fields.
	 *
	 * @return void
	 */
	public static function add_fields() {
		wp_nonce_field( 'lk_brand_meta', 'lk_brand_meta_nonce' );
		?>
		<div class="form-field">
			<label for="lk_brand_name_en">نام انگلیسی</label>
			<input type="text" name="lk_brand_name_en" id="lk_brand_name_en" value="" />
		</div>
		<div class="form-field">
			<label for="lk_brand_country">کشور سازنده</label>
			<input type="text" name="lk_brand_country" id="lk_brand_country" value="" />
		</div>
		<div class="form-field">
			<label for="lk_brand_founded">سال تأسیس</label>
			<input type="text" name="lk_brand_founded" id="lk_brand_founded" value="" dir="ltr" />
		</div>
		<div class="form-field">
			<label for="lk_brand_website">وب‌سایت رسمی</label>
			<input type="url" name="lk_brand_website" id="lk_brand_website" value="" dir="ltr" />
		</div>
		<div class="form-field">
			<label>لوگوی برند</label>
			<input type="hidden" name="lk_brand_logo_id" id="lk_brand_logo_id" value="" />
			<button type="button" class="button" data-lk-media="lk_brand_logo_id">انتخاب لوگو</button>
			<div class="lk-brand-logo-preview" data-preview="lk_brand_logo_id"></div>
		</div>
		<?php
	}

	/**
	 * Edit form fields.
	 *
	 * @param WP_Term $term Term.
	 * @return void
	 */
	public static function edit_fields( $term ) {
		wp_nonce_field( 'lk_brand_meta', 'lk_brand_meta_nonce' );
		$logo_id = (int) self::brand_meta( $term->term_id, 'logo_id' );
		$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
		?>
		<tr class="form-field">
			<th scope="row"><label for="lk_brand_name_en">نام انگلیسی</label></th>
			<td><input type="text" name="lk_brand_name_en" id="lk_brand_name_en" value="<?php echo esc_attr( self::brand_meta( $term->term_id, 'name_en' ) ); ?>" dir="ltr" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lk_brand_country">کشور سازنده</label></th>
			<td><input type="text" name="lk_brand_country" id="lk_brand_country" value="<?php echo esc_attr( self::brand_meta( $term->term_id, 'country' ) ); ?>" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lk_brand_founded">سال تأسیس</label></th>
			<td><input type="text" name="lk_brand_founded" id="lk_brand_founded" value="<?php echo esc_attr( self::brand_meta( $term->term_id, 'founded' ) ); ?>" dir="ltr" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="lk_brand_website">وب‌سایت رسمی</label></th>
			<td><input type="url" name="lk_brand_website" id="lk_brand_website" value="<?php echo esc_url( self::brand_meta( $term->term_id, 'website' ) ); ?>" dir="ltr" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label>لوگوی برند</label></th>
			<td>
				<input type="hidden" name="lk_brand_logo_id" id="lk_brand_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
				<button type="button" class="button" data-lk-media="lk_brand_logo_id">انتخاب لوگو</button>
				<button type="button" class="button-link" data-lk-media-clear="lk_brand_logo_id">حذف</button>
				<div class="lk-brand-logo-preview" data-preview="lk_brand_logo_id">
					<?php if ( $logo ) : ?>
						<img src="<?php echo esc_url( $logo ); ?>" alt="" />
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term meta.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public static function save_fields( $term_id ) {
		if ( ! isset( $_POST['lk_brand_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lk_brand_meta_nonce'] ) ), 'lk_brand_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$fields = array(
			'name_en'  => 'sanitize_text_field',
			'country'  => 'sanitize_text_field',
			'founded'  => 'sanitize_text_field',
			'website'  => 'esc_url_raw',
			'logo_id'  => 'absint',
		);

		foreach ( $fields as $key => $callback ) {
			$input = 'lk_brand_' . $key;
			if ( ! isset( $_POST[ $input ] ) ) {
				continue;
			}
			$value = call_user_func( $callback, wp_unslash( $_POST[ $input ] ) );
			if ( '' === $value || 0 === $value ) {
				delete_term_meta( $term_id, 'lk_brand_' . $key );
			} else {
				update_term_meta( $term_id, 'lk_brand_' . $key, $value );
			}
		}
	}

	/**
	 * Admin assets for the brand media picker.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function admin_assets( $hook ) {
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'lk_brand' !== $screen->taxonomy ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'lk-core-admin', LK_URL . 'assets/css/core-admin.css', array(), LK_VERSION );
		wp_enqueue_script( 'lk-core-admin', LK_URL . 'assets/js/core-admin.js', array( 'jquery' ), LK_VERSION, true );
	}

	/**
	 * Seed structural vocabularies once (body types and classes only).
	 *
	 * These are taxonomy terms, not content or prices.
	 *
	 * @return void
	 */
	public static function maybe_seed_terms() {
		if ( '1' !== (string) get_option( 'lk_terms_seeded' ) ) {
			$body_types = array(
				'سدان'      => 'sedan',
				'هاچبک'     => 'hatchback',
				'شاسی‌بلند' => 'suv',
				'کراس‌اوور' => 'crossover',
				'وانت'      => 'pickup',
				'ون'        => 'van',
				'کوپه'      => 'coupe',
			);
			foreach ( $body_types as $name => $slug ) {
				if ( ! term_exists( $slug, 'lk_body_type' ) ) {
					wp_insert_term( $name, 'lk_body_type', array( 'slug' => $slug ) );
				}
			}

			$classes = array(
				'اقتصادی'   => 'economy',
				'متوسط'     => 'mid',
				'لوکس'      => 'luxury',
				'تجاری'     => 'commercial',
			);
			foreach ( $classes as $name => $slug ) {
				if ( ! term_exists( $slug, 'lk_class' ) ) {
					wp_insert_term( $name, 'lk_class', array( 'slug' => $slug ) );
				}
			}

			update_option( 'lk_terms_seeded', '1', false );
		}
	}
}
