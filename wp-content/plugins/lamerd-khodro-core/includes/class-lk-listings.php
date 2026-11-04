<?php
/**
 * Sale listings: post type, editable fields, featured window and admin list.
 *
 * A listing is a user-facing advert for a car for sale. There is no cart,
 * checkout or payment flow: the admin publishes the advert, the front end
 * shows it. "فروش ویژه" is a time-boxed flag; once its timestamp passes the
 * listing silently returns to the normal list.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Listings
 */
class LK_Listings {

	/**
	 * Post type key.
	 */
	const POST_TYPE = 'lk_listing';

	/**
	 * Meta key prefix.
	 */
	const META = '_lk_listing_';

	/**
	 * Meta box nonce action.
	 */
	const NONCE = 'lk_listing_meta';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 6 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'archive_query' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'admin_filters' ) );
		add_action( 'parse_query', array( __CLASS__, 'admin_filter_query' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
	}

	/**
	 * Register the listing post type.
	 *
	 * @return void
	 */
	public static function register() {
		$labels = array(
			'name'                  => 'آگهی‌های فروش',
			'singular_name'         => 'آگهی فروش',
			'menu_name'             => 'آگهی‌های فروش',
			'name_admin_bar'        => 'آگهی فروش',
			'add_new'               => 'افزودن آگهی',
			'add_new_item'          => 'افزودن آگهی فروش',
			'new_item'              => 'آگهی جدید',
			'edit_item'             => 'ویرایش آگهی',
			'view_item'             => 'مشاهده آگهی',
			'view_items'            => 'مشاهده آگهی‌ها',
			'all_items'             => 'همه آگهی‌ها',
			'search_items'          => 'جستجوی آگهی',
			'not_found'             => 'آگهی‌ای ثبت نشده است.',
			'not_found_in_trash'    => 'آگهی‌ای در زباله‌دان نیست.',
			'featured_image'        => 'تصویر خودرو',
			'set_featured_image'    => 'انتخاب تصویر خودرو',
			'remove_featured_image' => 'حذف تصویر خودرو',
			'use_featured_image'    => 'استفاده به‌عنوان تصویر خودرو',
			'archives'              => 'آرشیو آگهی‌ها',
			'filter_items_list'     => 'فیلتر فهرست آگهی‌ها',
			'items_list'            => 'فهرست آگهی‌ها',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'menu_position'      => 27,
				'menu_icon'          => 'dashicons-megaphone',
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'        => 'listings',
				'rewrite'            => array(
					'slug'       => 'listings',
					'with_front' => false,
				),
				'show_in_rest'       => false,
			)
		);
	}

	/**
	 * Known cities for the advert.
	 *
	 * @return array<string,string>
	 */
	public static function cities() {
		$cities = array(
			'lamerd'   => 'لامرد',
			'mehr'     => 'مهر',
			'lar'      => 'لار',
			'gerash'   => 'گراش',
			'khoonj'   => 'خنج',
			'bastak'   => 'بستک',
			'bandar'   => 'بندرلنگه',
			'shiraz'   => 'شیراز',
			'other'    => 'سایر',
		);

		/**
		 * Filter the available cities.
		 *
		 * @param array $cities Slug => label.
		 */
		return (array) apply_filters( 'lk_listing_cities', $cities );
	}

	/**
	 * Gearbox options.
	 *
	 * @return array<string,string>
	 */
	public static function gearboxes() {
		return array(
			'manual'    => 'دستی',
			'automatic' => 'اتوماتیک',
		);
	}

	/**
	 * Condition options.
	 *
	 * @return array<string,string>
	 */
	public static function conditions() {
		return array(
			'used' => 'کارکرده',
			'new'  => 'نو / صفرکیلومتر',
		);
	}

	/**
	 * Read one listing field.
	 *
	 * @param int    $post_id Listing ID.
	 * @param string $key     Field key without prefix (price, year, ...).
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public static function get( $post_id, $key, $default = '' ) {
		$value = get_post_meta( (int) $post_id, self::META . $key, true );
		return ( '' === $value || null === $value ) ? $default : $value;
	}

	/**
	 * Whether the listing is currently in its featured window.
	 *
	 * @param int $post_id Listing ID.
	 * @return bool
	 */
	public static function is_featured( $post_id ) {
		if ( '1' !== (string) self::get( $post_id, 'featured', '0' ) ) {
			return false;
		}
		return (int) self::get( $post_id, 'featured_until', 0 ) > time();
	}

	/**
	 * Featured expiry as a unix timestamp.
	 *
	 * @param int $post_id Listing ID.
	 * @return int
	 */
	public static function featured_until( $post_id ) {
		return (int) self::get( $post_id, 'featured_until', 0 );
	}

	/**
	 * Human readable time left in the featured window.
	 *
	 * @param int $post_id Listing ID.
	 * @return string Empty when not featured.
	 */
	public static function time_left( $post_id ) {
		if ( ! self::is_featured( $post_id ) ) {
			return '';
		}

		$left = self::featured_until( $post_id ) - time();
		$days = (int) floor( $left / DAY_IN_SECONDS );
		$hours = (int) floor( ( $left % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
		$mins = (int) floor( ( $left % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

		if ( $days > 0 ) {
			/* translators: 1: days, 2: hours */
			return sprintf( '%s روز و %s ساعت', LK_Format::digits( $days ), LK_Format::digits( $hours ) );
		}
		if ( $hours > 0 ) {
			/* translators: 1: hours, 2: minutes */
			return sprintf( '%s ساعت و %s دقیقه', LK_Format::digits( $hours ), LK_Format::digits( $mins ) );
		}
		/* translators: %s: minutes */
		return sprintf( '%s دقیقه', LK_Format::digits( max( 1, $mins ) ) );
	}

	/**
	 * IDs of listings whose featured window is still open.
	 *
	 * @param int $limit Maximum IDs (-1 for all).
	 * @return int[]
	 */
	public static function featured_ids( $limit = -1 ) {
		$ids = get_posts(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => (int) $limit,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'AND',
					array(
						'key'   => self::META . 'featured',
						'value' => '1',
					),
					array(
						'key'     => self::META . 'featured_until',
						'value'   => time(),
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * Keep the featured items out of the archive grid so they are not shown twice.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public static function archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( ! $query->is_post_type_archive( self::POST_TYPE ) ) {
			return;
		}

		$featured = self::featured_ids();
		if ( $featured ) {
			$query->set( 'post__not_in', $featured );
		}
		$query->set( 'posts_per_page', 12 );
	}

	/**
	 * Meta boxes.
	 *
	 * @return void
	 */
	public static function meta_boxes() {
		add_meta_box(
			'lk_listing_details',
			'مشخصات آگهی',
			array( __CLASS__, 'render_details' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'lk_listing_featured',
			'فروش ویژه',
			array( __CLASS__, 'render_featured' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Details meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render_details( $post ) {
		wp_nonce_field( self::NONCE, 'lk_listing_meta_nonce' );

		$price     = self::get( $post->ID, 'price' );
		$year      = self::get( $post->ID, 'year' );
		$km        = self::get( $post->ID, 'km' );
		$color     = self::get( $post->ID, 'color' );
		$gearbox   = self::get( $post->ID, 'gearbox' );
		$condition = self::get( $post->ID, 'condition', 'used' );
		$city      = self::get( $post->ID, 'city', 'lamerd' );
		$seller    = self::get( $post->ID, 'seller' );
		$phone     = self::get( $post->ID, 'phone' );
		$vehicle   = (int) self::get( $post->ID, 'vehicle', 0 );

		$vehicles = get_posts(
			array(
				'post_type'      => 'lk_vehicle',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="lk_listing_price">قیمت فروش (تومان) <span class="description">(الزامی)</span></label></th>
				<td>
					<input type="number" min="1" step="1000" class="regular-text" id="lk_listing_price" name="lk_listing[price]" value="<?php echo esc_attr( $price ); ?>" required />
					<p class="description">مبلغ کامل به تومان، بدون جداکننده. مثال: ۸۵۰۰۰۰۰۰۰</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lk_listing_vehicle">خودروی مرتبط</label></th>
				<td>
					<select id="lk_listing_vehicle" name="lk_listing[vehicle]" class="regular-text">
						<option value="0">— بدون ارتباط —</option>
						<?php foreach ( $vehicles as $item ) : ?>
							<option value="<?php echo esc_attr( $item->ID ); ?>" <?php selected( $vehicle, (int) $item->ID ); ?>><?php echo esc_html( $item->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">در صورت انتخاب، آگهی به صفحه قیمت و مشخصات همان خودرو لینک می‌شود.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">مشخصات خودرو</th>
				<td>
					<p>
						<label for="lk_listing_year">سال ساخت (شمسی)</label>
						<input type="number" min="1300" max="1450" id="lk_listing_year" name="lk_listing[year]" value="<?php echo esc_attr( $year ); ?>" class="small-text" />
						<label for="lk_listing_km" style="margin-inline-start:16px">کارکرد (کیلومتر)</label>
						<input type="number" min="0" max="2000000" step="1000" id="lk_listing_km" name="lk_listing[km]" value="<?php echo esc_attr( $km ); ?>" class="small-text" />
					</p>
					<p>
						<label for="lk_listing_color">رنگ</label>
						<input type="text" id="lk_listing_color" name="lk_listing[color]" value="<?php echo esc_attr( $color ); ?>" class="regular-text" />
					</p>
					<p>
						<label for="lk_listing_gearbox">گیربکس</label>
						<select id="lk_listing_gearbox" name="lk_listing[gearbox]">
							<option value="">— انتخاب —</option>
							<?php foreach ( self::gearboxes() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $gearbox, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<label for="lk_listing_condition" style="margin-inline-start:16px">وضعیت</label>
						<select id="lk_listing_condition" name="lk_listing[condition]">
							<?php foreach ( self::conditions() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $condition, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lk_listing_city">شهر</label></th>
				<td>
					<select id="lk_listing_city" name="lk_listing[city]">
						<?php foreach ( self::cities() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $city, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">تماس فروشنده</th>
				<td>
					<p>
						<label for="lk_listing_seller">نام فروشنده</label>
						<input type="text" id="lk_listing_seller" name="lk_listing[seller]" value="<?php echo esc_attr( $seller ); ?>" class="regular-text" />
					</p>
					<p>
						<label for="lk_listing_phone">شماره تماس</label>
						<input type="tel" id="lk_listing_phone" name="lk_listing[phone]" value="<?php echo esc_attr( $phone ); ?>" dir="ltr" class="regular-text" />
					</p>
					<p class="description">شماره تماس در صفحه آگهی به‌صورت لینک تماس نمایش داده می‌شود.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Featured meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render_featured( $post ) {
		$featured = '1' === (string) self::get( $post->ID, 'featured', '0' );
		$until    = self::featured_until( $post->ID );
		$active   = self::is_featured( $post->ID );
		$days     = (int) self::get( $post->ID, 'featured_days', 7 );
		?>
		<p>
			<label>
				<input type="checkbox" name="lk_listing[featured]" value="1" <?php checked( $featured ); ?> />
				<strong>نمایش به‌عنوان فروش ویژه</strong>
			</label>
		</p>
		<p>
			<label for="lk_listing_featured_days">مدت نمایش ویژه (روز)</label>
			<input type="number" min="1" max="365" step="1" id="lk_listing_featured_days" name="lk_listing[featured_days]" value="<?php echo esc_attr( $days ); ?>" class="small-text" />
		</p>
		<p class="description">
			تایمر از زمان هر ذخیره از نو حساب می‌شود. پس از پایان، آگهی خودکار از حالت ویژه خارج و در فهرست عادی نمایش داده می‌شود؛ نیازی به کار دستی نیست.
		</p>

		<?php if ( $until > 0 ) : ?>
			<hr />
			<p>
				<?php if ( $active ) : ?>
					<span class="dashicons dashicons-clock" aria-hidden="true"></span>
					<strong>فعال تا:</strong>
					<?php echo esc_html( LK_Format::jalali( $until, 'datetime' ) ); ?>
					<br />
					<span class="description"><?php echo esc_html( self::time_left( $post->ID ) ); ?> باقی مانده است.</span>
				<?php else : ?>
					<strong>وضعیت:</strong> ویژه به پایان رسیده
					<br />
					<span class="description"><?php echo esc_html( LK_Format::jalali( $until, 'datetime' ) ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Persist the listing fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['lk_listing_meta_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['lk_listing_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['lk_listing'] ) && is_array( $_POST['lk_listing'] ) ? wp_unslash( $_POST['lk_listing'] ) : array();

		$price = isset( $raw['price'] ) ? absint( $raw['price'] ) : 0;
		update_post_meta( $post_id, self::META . 'price', $price );

		$year = isset( $raw['year'] ) ? absint( $raw['year'] ) : 0;
		if ( $year < 1300 || $year > 1450 ) {
			$year = 0;
		}
		update_post_meta( $post_id, self::META . 'year', $year );

		$km = isset( $raw['km'] ) ? absint( $raw['km'] ) : 0;
		update_post_meta( $post_id, self::META . 'km', $km );

		$color = isset( $raw['color'] ) ? sanitize_text_field( $raw['color'] ) : '';
		update_post_meta( $post_id, self::META . 'color', $color );

		$gearbox = isset( $raw['gearbox'] ) ? sanitize_key( $raw['gearbox'] ) : '';
		if ( ! array_key_exists( $gearbox, self::gearboxes() ) ) {
			$gearbox = '';
		}
		update_post_meta( $post_id, self::META . 'gearbox', $gearbox );

		$condition = isset( $raw['condition'] ) ? sanitize_key( $raw['condition'] ) : 'used';
		if ( ! array_key_exists( $condition, self::conditions() ) ) {
			$condition = 'used';
		}
		update_post_meta( $post_id, self::META . 'condition', $condition );

		$city = isset( $raw['city'] ) ? sanitize_key( $raw['city'] ) : 'lamerd';
		if ( ! array_key_exists( $city, self::cities() ) ) {
			$city = 'other';
		}
		update_post_meta( $post_id, self::META . 'city', $city );

		$seller = isset( $raw['seller'] ) ? sanitize_text_field( $raw['seller'] ) : '';
		update_post_meta( $post_id, self::META . 'seller', $seller );

		$phone = isset( $raw['phone'] ) ? sanitize_text_field( $raw['phone'] ) : '';
		$phone = preg_replace( '/[^0-9+\s-]/', '', $phone );
		update_post_meta( $post_id, self::META . 'phone', trim( (string) $phone ) );

		$vehicle = isset( $raw['vehicle'] ) ? absint( $raw['vehicle'] ) : 0;
		if ( $vehicle && 'lk_vehicle' !== get_post_type( $vehicle ) ) {
			$vehicle = 0;
		}
		update_post_meta( $post_id, self::META . 'vehicle', $vehicle );

		$featured = ! empty( $raw['featured'] ) ? '1' : '0';
		update_post_meta( $post_id, self::META . 'featured', $featured );

		$days = isset( $raw['featured_days'] ) ? absint( $raw['featured_days'] ) : 7;
		$days = max( 1, min( 365, $days ) );
		update_post_meta( $post_id, self::META . 'featured_days', $days );

		if ( '1' === $featured ) {
			update_post_meta( $post_id, self::META . 'featured_until', time() + $days * DAY_IN_SECONDS );
		} else {
			update_post_meta( $post_id, self::META . 'featured_until', 0 );
		}
	}

	/**
	 * Admin list columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			if ( 'date' === $key ) {
				$new['lk_price']   = 'قیمت';
				$new['lk_city']    = 'شهر';
				$new['lk_featured'] = 'فروش ویژه';
			}
			$new[ $key ] = $label;
		}

		return $new;
	}

	/**
	 * Admin column output.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'lk_price':
				$price = (int) self::get( $post_id, 'price', 0 );
				if ( $price > 0 ) {
					echo '<span dir="rtl">' . esc_html( LK_Format::price( $price ) ) . '</span> <small>تومان</small>';
				} else {
					echo '<span style="color:#b32d2e">ثبت نشده</span>';
				}
				break;

			case 'lk_city':
				$city  = (string) self::get( $post_id, 'city', '' );
				$cities = self::cities();
				echo esc_html( $cities[ $city ] ?? '—' );
				break;

			case 'lk_featured':
				if ( self::is_featured( $post_id ) ) {
					$until = self::featured_until( $post_id );
					echo '<span class="dashicons dashicons-star-filled" style="color:#f26522" aria-hidden="true"></span> ';
					echo '<strong style="color:#0e7c57">فعال</strong><br /><small>' . esc_html( self::time_left( $post_id ) ) . '</small>';
					echo '<br /><small>' . esc_html( LK_Format::jalali( $until, 'datetime' ) ) . '</small>';
				} else {
					$until = self::featured_until( $post_id );
					echo $until > 0 ? '<span style="color:#8b98a4">پایان‌یافته</span>' : '—';
				}
				break;
		}
	}

	/**
	 * Featured filter above the list.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function admin_filters( $post_type ) {
		if ( self::POST_TYPE !== $post_type ) {
			return;
		}

		$current = isset( $_GET['lk_featured'] ) ? sanitize_key( wp_unslash( $_GET['lk_featured'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<select name="lk_featured">
			<option value="">همه آگهی‌ها</option>
			<option value="active" <?php selected( $current, 'active' ); ?>>فقط فروش ویژه فعال</option>
		</select>
		<?php
	}

	/**
	 * Apply the featured filter.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public static function admin_filter_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		if ( 'active' !== ( $_GET['lk_featured'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = array(
			'key'   => self::META . 'featured',
			'value' => '1',
		);
		$meta_query[] = array(
			'key'     => self::META . 'featured_until',
			'value'   => time(),
			'compare' => '>',
			'type'    => 'NUMERIC',
		);

		$query->set( 'meta_query', $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}

	/**
	 * Row actions.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( self::POST_TYPE === $post->post_type ) {
			$actions['lk_view'] = '<a href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">مشاهده در سایت</a>';
		}
		return $actions;
	}
}
