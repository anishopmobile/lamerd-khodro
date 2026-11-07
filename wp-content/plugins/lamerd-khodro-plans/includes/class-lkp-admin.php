<?php
/**
 * Admin screens: plan editor, request list and private file delivery.
 *
 * @package LamerdKhodro\Plans
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin integration.
 */
class LKP_Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . LKP_Plans::POST_TYPE, array( __CLASS__, 'save_plan' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_post_lkp_document', array( __CLASS__, 'serve_document' ) );
		add_action( 'admin_post_lkp_status', array( __CLASS__, 'handle_status' ) );
		add_action( 'admin_post_lkp_export', array( __CLASS__, 'export_csv' ) );

		add_filter( 'manage_' . LKP_Plans::POST_TYPE . '_posts_columns', array( __CLASS__, 'plan_columns' ) );
		add_action( 'manage_' . LKP_Plans::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'plan_column' ), 10, 2 );
		add_filter( 'manage_' . LKP_Requests::POST_TYPE . '_posts_columns', array( __CLASS__, 'request_columns' ) );
		add_action( 'manage_' . LKP_Requests::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'request_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_filter( 'redirect_post_location', array( __CLASS__, 'redirect_notice' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'request_filters' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_requests' ) );
	}

	/**
	 * Meta boxes on both screens.
	 *
	 * @return void
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'lkp_plan_details',
			'اطلاعات طرح',
			array( __CLASS__, 'render_plan_box' ),
			LKP_Plans::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'lkp_request_details',
			'مشخصات متقاضی و مدارک',
			array( __CLASS__, 'render_request_box' ),
			LKP_Requests::POST_TYPE,
			'normal',
			'high'
		);

		remove_meta_box( 'slugdiv', LKP_Plans::POST_TYPE, 'normal' );
		remove_meta_box( 'slugdiv', LKP_Requests::POST_TYPE, 'normal' );
	}

	/**
	 * Plan editor meta box.
	 *
	 * @param WP_Post $post Plan.
	 * @return void
	 */
	public static function render_plan_box( $post ) {
		wp_nonce_field( 'lkp_plan_meta', 'lkp_nonce' );

		$keys     = LKP_Plans::meta_keys();
		$company  = (string) get_post_meta( $post->ID, $keys['company'], true );
		$active   = LKP_Plans::is_enabled( $post->ID );
		$images   = LKP_Plans::image_ids( $post->ID );
		$state    = LKP_Plans::state( $post->ID );
		$dates    = array(
			'active' => (int) get_post_meta( $post->ID, $keys['active_ts'], true ),
			'start'  => (int) get_post_meta( $post->ID, $keys['start_ts'], true ),
			'end'    => (int) get_post_meta( $post->ID, $keys['end_ts'], true ),
		);
		?>
		<div class="lkp-box">
			<div class="lkp-grid lkp-grid--two">
				<label class="lkp-field">
					<span>شرکت سازنده</span>
					<input type="text" name="lkp_company" value="<?php echo esc_attr( $company ); ?>" maxlength="190" placeholder="مثلاً ایران خودرو">
				</label>
				<div class="lkp-field lkp-field--static">
					<span>وضعیت فعلی</span>
					<strong class="lkp-badge lkp-badge--<?php echo esc_attr( $state ); ?>"><?php echo esc_html( LKP_Plans::state_label( $state ) ); ?></strong>
					<small>از تاریخ‌ها و کلید نمایش در سایت محاسبه می‌شود.</small>
				</div>
			</div>

			<p class="lkp-hint">عنوان طرح (بخش بالا) همان «مدل و تیپ خودرو» است؛ توضیحات ویرایشگر هم متن معرفی طرح برای متقاضی است.</p>

			<div class="lkp-switch-row">
				<label class="lkp-switch">
					<input type="checkbox" name="lkp_active" value="1" <?php checked( $active ); ?>>
					<span></span>
				</label>
				<div>
					<b>نمایش طرح در سایت</b>
					<small>با خاموش‌کردن، طرح با برچسب «غیرفعال» نمایش داده می‌شود و امکان ثبت درخواست ندارد.</small>
				</div>
			</div>

			<fieldset class="lkp-card">
				<legend>زمان‌بندی طرح (شمسی)</legend>
				<div class="lkp-grid lkp-grid--three">
					<?php
					$fields = array(
						'active' => array( 'زمان فعال شدن طرح', '00:00' ),
						'start'  => array( 'شروع ثبت‌نام', '00:00' ),
						'end'    => array( 'پایان ثبت‌نام', '23:59' ),
					);
					foreach ( $fields as $key => $info ) :
						$ts    = $dates[ $key ];
						$value = $ts > 0 ? LKP_Jalali::date_string( $ts ) : '';
						$time  = $ts > 0 ? LKP_Jalali::time_string( $ts ) : $info[1];
						?>
						<div class="lkp-field">
							<span><?php echo esc_html( $info[0] ); ?></span>
							<input
								type="text"
								class="lkp-date"
								name="lkp_<?php echo esc_attr( $key ); ?>_date"
								value="<?php echo esc_attr( $value ); ?>"
								placeholder="۱۴۰۵/۰۶/۰۵"
								inputmode="numeric"
								autocomplete="off"
								data-lkp-date
							>
							<div class="lkp-time">
								<input type="time" name="lkp_<?php echo esc_attr( $key ); ?>_time" value="<?php echo esc_attr( $time ); ?>">
								<span>ساعت</span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="lkp-hint">روی فیلد تاریخ کلیک کنید تا تقویم شمسی باز شود؛ تایپ دستی هم پذیرفته می‌شود. تاریخ‌ها به‌صورت خودکار به زمان سیستم تبدیل و همین‌جا اعتبارسنجی می‌شوند.</p>
			</fieldset>

			<div class="lkp-card">
				<div class="lkp-card__head">
					<div>
						<h3>تصاویر طرح</h3>
						<small>چند تصویر انتخاب کنید؛ تصویر اول تصویر اصلی کارت است.</small>
					</div>
					<button type="button" class="button button-secondary" data-lkp-media>انتخاب تصاویر</button>
				</div>
				<input type="hidden" name="lkp_images" value="<?php echo esc_attr( implode( ',', $images ) ); ?>" data-lkp-images>
				<div class="lkp-media-grid" data-lkp-media-grid>
					<?php
					foreach ( $images as $index => $image_id ) :
						$thumb = wp_get_attachment_image_url( $image_id, 'thumbnail' );
						if ( ! $thumb ) {
							continue;
						}
						?>
						<figure class="lkp-media-item" data-id="<?php echo esc_attr( (string) $image_id ); ?>">
							<img src="<?php echo esc_url( $thumb ); ?>" alt="">
							<button type="button" class="lkp-media-remove" data-lkp-remove aria-label="حذف تصویر">×</button>
							<?php if ( 0 === $index ) : ?>
								<figcaption>تصویر اصلی</figcaption>
							<?php else : ?>
								<button type="button" class="lkp-media-main" data-lkp-main>اصلی کن</button>
							<?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
				<p class="lkp-empty" data-lkp-media-empty <?php echo $images ? 'hidden' : ''; ?>>هنوز تصویری انتخاب نشده است.</p>
			</div>

			<?php
			$file_boxes = array(
				'circular' => array(
					'title' => 'بخشنامهٔ طرح',
					'hint'  => 'PDF یا تصویر؛ حداکثر ۱۰ مگابایت. در سایت زیر زمان باقی‌ماندهٔ کارت نمایش داده می‌شود و قابل دریافت است.',
				),
				'catalog'  => array(
					'title' => 'کاتالوگ محصول',
					'hint'  => 'PDF یا تصویر؛ حداکثر ۱۰ مگابایت. در سایت زیر زمان باقی‌ماندهٔ کارت نمایش داده می‌شود و قابل دریافت است.',
				),
			);
			foreach ( $file_boxes as $kind => $box ) :
				$file = LKP_Plans::file( $post->ID, $kind );
				?>
				<div class="lkp-card">
					<div class="lkp-card__head">
						<div>
							<h3><?php echo esc_html( $box['title'] ); ?></h3>
							<small>در پوشهٔ خصوصی ذخیره می‌شود و از طریق لینک امن کارت طرح در سایت دریافت می‌شود.</small>
						</div>
					</div>

					<?php if ( ! empty( $file['stored'] ) ) : ?>
						<p class="lkp-file">
							<a href="<?php echo esc_url( LKP_Plans::file_url( $post->ID, $kind ) ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $file['name'] ? $file['name'] : $box['title'] ); ?>
							</a>
							<?php if ( ! empty( $file['size'] ) ) : ?>
								<span><?php echo esc_html( size_format( (int) $file['size'] ) ); ?></span>
							<?php endif; ?>
							<label class="lkp-file__remove">
								<input type="checkbox" name="lkp_<?php echo esc_attr( $kind ); ?>_remove" value="1">
								حذف فایل فعلی
							</label>
						</p>
					<?php endif; ?>

					<input type="file" name="lkp_<?php echo esc_attr( $kind ); ?>_file" accept="application/pdf,image/jpeg,image/png">
					<p class="lkp-hint"><?php echo esc_html( $box['hint'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Request detail box (read only).
	 *
	 * @param WP_Post $post Request.
	 * @return void
	 */
	public static function render_request_box( $post ) {
		$plan_id = (int) LKP_Requests::get( $post->ID, 'plan_id' );
		$plan    = $plan_id ? get_post( $plan_id ) : null;
		$status  = LKP_Requests::status( $post->ID );
		$docs    = LKP_Requests::documents( $post->ID );
		$statuses = LKP_Requests::statuses();

		$rows = array(
			'نام'                  => LKP_Requests::get( $post->ID, 'name' ),
			'نام خانوادگی'         => LKP_Requests::get( $post->ID, 'lastname' ),
			'شماره تماس'           => LKP_Requests::get( $post->ID, 'phone' ),
			'موبایل به نام متقاضی' => LKP_Requests::get( $post->ID, 'mobile_owner' ),
			'کد پستی'              => LKP_Requests::get( $post->ID, 'postal' ),
			'شماره شبا'            => LKP_Requests::get( $post->ID, 'shaba' ),
			'آدرس'                 => LKP_Requests::get( $post->ID, 'address' ),
			'کد پیگیری'            => LKP_Requests::get( $post->ID, 'tracking' ),
			'زمان ثبت'             => LKP_Jalali::format( get_post_time( 'U', true, $post ), true ),
		);
		?>
		<div class="lkp-box">
			<div class="lkp-grid lkp-grid--two">
				<div class="lkp-field lkp-field--static">
					<span>طرح</span>
					<strong>
						<?php if ( $plan ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $plan->ID ) ); ?>"><?php echo esc_html( $plan->post_title ); ?></a>
						<?php else : ?>
							طرح حذف شده است
						<?php endif; ?>
					</strong>
					<?php if ( $plan ) : ?>
						<small><?php echo esc_html( LKP_Plans::company( $plan->ID ) ); ?></small>
					<?php endif; ?>
				</div>
				<div class="lkp-field lkp-field--static">
					<span>وضعیت بررسی</span>
					<strong class="lkp-badge lkp-badge--<?php echo esc_attr( 'new' === $status ? 'new' : ( 'reviewed' === $status ? 'live' : 'disabled' ) ); ?>"><?php echo esc_html( $statuses[ $status ] ?? 'جدید' ); ?></strong>
					<small>
						<?php
						$links = array();
						foreach ( $statuses as $key => $label ) {
							if ( $key === $status ) {
								continue;
							}
							$links[] = '<a href="' . esc_url( self::status_url( $post->ID, $key ) ) . '">' . esc_html( $label ) . '</a>';
						}
						echo wp_kses_post( implode( ' · ', $links ) );
						?>
					</small>
				</div>
			</div>

			<table class="lkp-table">
				<tbody>
					<?php foreach ( $rows as $label => $value ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td <?php echo in_array( $label, array( 'شماره تماس', 'موبایل به نام متقاضی', 'کد پستی', 'شماره شبا', 'کد پیگیری' ), true ) ? 'dir="ltr"' : ''; ?>>
								<?php echo esc_html( $value ? $value : '—' ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="lkp-card">
				<h3>مدارک بارگذاری‌شده</h3>
				<?php if ( $docs ) : ?>
					<ul class="lkp-docs">
						<?php foreach ( $docs as $index => $doc ) : ?>
							<li>
								<a href="<?php echo esc_url( LKP_Requests::document_url( $post->ID, $index ) ); ?>" target="_blank" rel="noopener">
									<?php echo esc_html( $doc['name'] ?? 'مدرک' ); ?>
								</a>
								<span><?php echo esc_html( size_format( (int) ( $doc['size'] ?? 0 ) ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="lkp-empty">مدرکی بارگذاری نشده است.</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * URL that switches a request status.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $status     Status.
	 * @return string
	 */
	public static function status_url( $request_id, $status ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'lkp_status',
					'request'  => (int) $request_id,
					'status'   => $status,
				),
				admin_url( 'admin-post.php' )
			),
			'lkp_status_' . (int) $request_id
		);
	}

	/**
	 * Persist plan fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public static function save_plan( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || ! current_user_can( LKP_CAP ) ) {
			return;
		}

		if ( ! isset( $_POST['lkp_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lkp_nonce'] ) ), 'lkp_plan_meta' ) ) {
			return;
		}

		$keys    = LKP_Plans::meta_keys();
		$company = isset( $_POST['lkp_company'] ) ? sanitize_text_field( wp_unslash( $_POST['lkp_company'] ) ) : '';
		update_post_meta( $post_id, $keys['company'], $company );

		$active = isset( $_POST['lkp_active'] ) ? '1' : '0';
		update_post_meta( $post_id, $keys['active'], $active );

		$images = isset( $_POST['lkp_images'] ) ? (string) wp_unslash( $_POST['lkp_images'] ) : '';
		$ids    = array_filter( array_map( 'absint', explode( ',', $images ) ) );
		update_post_meta( $post_id, $keys['images'], LKP_Plans::sanitize_ids( $ids ) );

		$timestamps = array();
		foreach ( array( 'active', 'start', 'end' ) as $part ) {
			$date_field = 'lkp_' . $part . '_date';
			$time_field = 'lkp_' . $part . '_time';
			$date       = isset( $_POST[ $date_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $date_field ] ) ) : '';
			$time       = isset( $_POST[ $time_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $time_field ] ) ) : '00:00';

			$timestamps[ $part ] = LKP_Jalali::to_timestamp( $date, $time );
		}

		// Only store a schedule when the whole trio is valid, so a bad edit
		// cannot leave an inverted or half-saved window behind.
		$date_ok = $timestamps['active'] > 0 && $timestamps['start'] > 0 && $timestamps['end'] > 0
			&& $timestamps['end'] > $timestamps['start'];

		if ( $date_ok ) {
			foreach ( $timestamps as $part => $timestamp ) {
				update_post_meta( $post_id, $keys[ $part . '_ts' ], $timestamp );
			}
		}

		// Circular and catalog uploads / removals.
		foreach ( array( 'circular', 'catalog' ) as $kind ) {
			$remove = isset( $_POST[ 'lkp_' . $kind . '_remove' ] );
			$upload = isset( $_FILES[ 'lkp_' . $kind . '_file' ] ) && is_array( $_FILES[ 'lkp_' . $kind . '_file' ] )
				&& UPLOAD_ERR_NO_FILE !== (int) $_FILES[ 'lkp_' . $kind . '_file' ]['error'];

			if ( $remove ) {
				$current = LKP_Plans::file( $post_id, $kind );
				if ( ! empty( $current['stored'] ) ) {
					LKP_Files::delete( (string) $current['stored'] );
				}
				update_post_meta( $post_id, $keys[ $kind ], array() );
			}

			if ( $upload ) {
				$file = LKP_Files::store( $_FILES[ 'lkp_' . $kind . '_file' ], $kind ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( is_wp_error( $file ) ) {
					set_transient( 'lkp_notice_' . get_current_user_id(), array( 'type' => 'error', 'text' => $file->get_error_message() ), 60 );
				} else {
					$current = LKP_Plans::file( $post_id, $kind );
					if ( ! empty( $current['stored'] ) ) {
						LKP_Files::delete( (string) $current['stored'] );
					}
					update_post_meta( $post_id, $keys[ $kind ], $file );
				}
			}
		}

		if ( ! $date_ok ) {
			set_transient( 'lkp_notice_' . get_current_user_id(), array( 'type' => 'error', 'text' => 'تاریخ‌های طرح معتبر نیست: هر سه تاریخ شمسی را کامل وارد کنید و پایان ثبت‌نام باید بعد از شروع آن باشد.' ), 60 );
			set_transient( 'lkp_notice_dates_' . $post_id, 1, 60 );
		}

		update_post_meta( $post_id, '_lkp_updated_at', time() );
	}

	/**
	 * Append the notice flag to the post redirect.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $post_id  Post ID.
	 * @return string
	 */
	public static function redirect_notice( $location, $post_id ) {
		if ( LKP_Plans::POST_TYPE !== get_post_type( $post_id ) ) {
			return $location;
		}

		if ( get_transient( 'lkp_notice_dates_' . $post_id ) ) {
			delete_transient( 'lkp_notice_dates_' . $post_id );
			$location = add_query_arg( 'lkp_dates', '0', $location );
		}

		return $location;
	}

	/**
	 * Admin notices.
	 *
	 * @return void
	 */
	public static function notices() {
		$notice = get_transient( 'lkp_notice_' . get_current_user_id() );
		if ( $notice && is_array( $notice ) ) {
			delete_transient( 'lkp_notice_' . get_current_user_id() );
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( 'error' === $notice['type'] ? 'error' : 'success' ),
				esc_html( (string) $notice['text'] )
			);
		}
	}

	/**
	 * Admin assets.
	 *
	 * @param string $hook Current screen hook.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_plan    = LKP_Plans::POST_TYPE === $screen->post_type;
		$is_request = LKP_Requests::POST_TYPE === $screen->post_type;

		if ( ! $is_plan && ! $is_request ) {
			return;
		}

		wp_enqueue_style( 'lkp-admin', LKP_URL . 'assets/css/admin.css', array(), LKP_VERSION );

		if ( ! $is_plan ) {
			return;
		}

		wp_enqueue_media();

		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_style( 'lkp-datepicker', LKP_URL . 'assets/vendor/persian-datepicker.min.css', array(), '1.2.0' );
			wp_enqueue_script( 'lkp-persian-date', LKP_URL . 'assets/vendor/persian-date.min.js', array(), '1.1.0', true );
			wp_enqueue_script( 'lkp-datepicker', LKP_URL . 'assets/vendor/persian-datepicker.min.js', array( 'jquery', 'lkp-persian-date' ), '1.2.0', true );
			wp_enqueue_script( 'lkp-admin', LKP_URL . 'assets/js/admin.js', array( 'jquery', 'lkp-datepicker' ), LKP_VERSION, true );

			wp_localize_script(
				'lkp-admin',
				'LKP_ADMIN',
				array(
					'noImage' => LKP_URL . 'assets/img/no-image.svg',
					'strings' => array(
						'mediaTitle'  => 'انتخاب تصاویر طرح',
						'mediaButton' => 'افزودن به طرح',
						'primary'     => 'تصویر اصلی',
						'makePrimary' => 'اصلی کن',
						'remove'      => 'حذف تصویر',
						'empty'       => 'هنوز تصویری انتخاب نشده است.',
					),
				)
			);
		}
	}

	/**
	 * Plan list columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function plan_columns( $columns ) {
		$out = array(
			'cb'        => $columns['cb'] ?? '<input type="checkbox" />',
			'lkp_thumb' => 'تصویر',
			'title'     => 'مدل و تیپ خودرو',
			'lkp_company' => 'شرکت',
			'lkp_window'  => 'بازهٔ ثبت‌نام',
			'lkp_state'   => 'وضعیت',
			'lkp_media'   => 'تصاویر / بخشنامه',
			'date'        => 'تاریخ ثبت',
		);

		return $out;
	}

	/**
	 * Plan column output.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function plan_column( $column, $post_id ) {
		switch ( $column ) {
			case 'lkp_thumb':
				printf(
					'<img class="lkp-thumb" src="%s" alt="" width="64" height="44">',
					esc_url( LKP_Plans::primary_image( $post_id ) )
				);
				break;

			case 'lkp_company':
				echo esc_html( LKP_Plans::company( $post_id ) ?: '—' );
				break;

			case 'lkp_window':
				$start = LKP_Plans::timestamp( $post_id, 'start_ts' );
				$end   = LKP_Plans::timestamp( $post_id, 'end_ts' );
				if ( $start <= 0 || $end <= 0 ) {
					echo '—';
					break;
				}
				echo '<span dir="ltr">' . esc_html( LKP_Jalali::date_string( $start ) . ' → ' . LKP_Jalali::date_string( $end ) ) . '</span>';
				break;

			case 'lkp_state':
				$state = LKP_Plans::state( $post_id );
				printf(
					'<span class="lkp-badge lkp-badge--%1$s">%2$s</span>',
					esc_attr( $state ),
					esc_html( LKP_Plans::state_label( $state ) )
				);
				break;

			case 'lkp_media':
				$images   = count( LKP_Plans::image_ids( $post_id ) );
				$circular = LKP_Plans::circular( $post_id );
				$catalog  = LKP_Plans::catalog( $post_id );
				echo esc_html( LKP_Jalali::persian_digits( $images ) . ' تصویر' );
				echo $circular ? ' · <span class="dashicons dashicons-media-document" title="بخشنامه دارد"></span>' : '';
				echo $catalog ? ' · <span class="dashicons dashicons-book" title="کاتالوگ دارد"></span>' : '';
				break;
		}
	}

	/**
	 * Request list columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function request_columns( $columns ) {
		return array(
			'cb'           => $columns['cb'] ?? '<input type="checkbox" />',
			'title'        => 'متقاضی',
			'lkp_plan'     => 'طرح',
			'lkp_phone'    => 'موبایل',
			'lkp_status'   => 'وضعیت',
			'lkp_docs'     => 'مدارک',
			'date'         => 'زمان ثبت',
		);
	}

	/**
	 * Request column output.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function request_column( $column, $post_id ) {
		switch ( $column ) {
			case 'lkp_plan':
				$plan = get_post( (int) LKP_Requests::get( $post_id, 'plan_id' ) );
				echo $plan ? esc_html( $plan->post_title ) : '—';
				break;

			case 'lkp_phone':
				echo '<span dir="ltr">' . esc_html( (string) LKP_Requests::get( $post_id, 'mobile_owner' ) ) . '</span>';
				break;

			case 'lkp_status':
				$status   = LKP_Requests::status( $post_id );
				$statuses = LKP_Requests::statuses();
				printf(
					'<span class="lkp-badge lkp-badge--%1$s">%2$s</span>',
					esc_attr( 'new' === $status ? 'new' : ( 'reviewed' === $status ? 'live' : 'disabled' ) ),
					esc_html( $statuses[ $status ] ?? 'جدید' )
				);
				break;

			case 'lkp_docs':
				$count = count( LKP_Requests::documents( $post_id ) );
				echo esc_html( LKP_Jalali::persian_digits( $count ) . ' فایل' );
				break;
		}
	}

	/**
	 * Row actions for status changes and export.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( LKP_Requests::POST_TYPE !== $post->post_type || ! current_user_can( LKP_CAP ) ) {
			return $actions;
		}

		$status = LKP_Requests::status( $post->ID );
		if ( 'reviewed' !== $status ) {
			$actions['lkp_reviewed'] = '<a href="' . esc_url( self::status_url( $post->ID, 'reviewed' ) ) . '">علامت‌گذاری بررسی‌شده</a>';
		}
		if ( 'rejected' !== $status ) {
			$actions['lkp_rejected'] = '<a href="' . esc_url( self::status_url( $post->ID, 'rejected' ) ) . '">رد درخواست</a>';
		}

		return $actions;
	}

	/**
	 * Export button above the request list.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function request_filters( $post_type ) {
		if ( LKP_Requests::POST_TYPE !== $post_type ) {
			return;
		}

		$status = isset( $_GET['lkp_status'] ) ? sanitize_key( wp_unslash( $_GET['lkp_status'] ) ) : '';
		$labels = LKP_Requests::statuses();
		?>
		<select name="lkp_status">
			<option value="">همهٔ وضعیت‌ها</option>
			<?php foreach ( $labels as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="button">فیلتر</button>
		<?php
		$export = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'lkp_export',
				),
				admin_url( 'admin-post.php' )
			),
			'lkp_export'
		);
		?>
		<a class="button" href="<?php echo esc_url( $export ); ?>">خروجی CSV</a>
		<?php
	}

	/**
	 * Filter the request list by status.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public static function filter_requests( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( LKP_Requests::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$status = isset( $_GET['lkp_status'] ) ? sanitize_key( wp_unslash( $_GET['lkp_status'] ) ) : '';
		if ( $status && array_key_exists( $status, LKP_Requests::statuses() ) ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'   => LKP_Requests::meta_keys()['status'],
						'value' => $status,
					),
				)
			);
		}
	}

	/**
	 * Status change handler.
	 *
	 * @return void
	 */
	public static function handle_status() {
		$request_id = isset( $_GET['request'] ) ? absint( $_GET['request'] ) : 0;
		$status     = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';

		if ( ! $request_id || ! current_user_can( LKP_CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'lkp_status_' . $request_id );

		LKP_Requests::set_status( $request_id, $status );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type' => LKP_Requests::POST_TYPE,
					'lkp_done'  => 'status',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Stream a private applicant document.
	 *
	 * @return void
	 */
	public static function serve_document() {
		$request_id = isset( $_GET['request'] ) ? absint( $_GET['request'] ) : 0;
		$index      = isset( $_GET['index'] ) ? absint( $_GET['index'] ) : 0;

		if ( ! $request_id || ! current_user_can( LKP_CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'lkp_document_' . $request_id . '_' . $index );

		$docs = LKP_Requests::documents( $request_id );
		if ( ! isset( $docs[ $index ]['stored'] ) ) {
			wp_die( 'فایل یافت نشد.', '', array( 'response' => 404 ) );
		}

		LKP_Files::stream( (string) $docs[ $index ]['stored'], (string) ( $docs[ $index ]['name'] ?? '' ) );
	}

	/**
	 * CSV export of every request.
	 *
	 * @return void
	 */
	public static function export_csv() {
		if ( ! current_user_can( LKP_CAP ) ) {
			wp_die( 'دسترسی مجاز نیست.', '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'lkp_export' );

		$requests = get_posts(
			array(
				'post_type'      => LKP_Requests::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="lkp-requests-' . gmdate( 'Ymd-His' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv(
			$out,
			array( 'کد پیگیری', 'نام', 'نام خانوادگی', 'طرح', 'شرکت', 'موبایل', 'موبایل به نام متقاضی', 'کد پستی', 'شبا', 'آدرس', 'وضعیت', 'تعداد مدارک', 'زمان ثبت' )
		);

		$statuses = LKP_Requests::statuses();
		foreach ( $requests as $request ) {
			$plan = get_post( (int) LKP_Requests::get( $request->ID, 'plan_id' ) );
			fputcsv(
				$out,
				array(
					LKP_Requests::get( $request->ID, 'tracking' ),
					LKP_Requests::get( $request->ID, 'name' ),
					LKP_Requests::get( $request->ID, 'lastname' ),
					$plan ? $plan->post_title : '',
					$plan ? LKP_Plans::company( $plan->ID ) : '',
					LKP_Requests::get( $request->ID, 'mobile_owner' ),
					LKP_Requests::get( $request->ID, 'phone' ),
					LKP_Requests::get( $request->ID, 'postal' ),
					LKP_Requests::get( $request->ID, 'shaba' ),
					LKP_Requests::get( $request->ID, 'address' ),
					$statuses[ LKP_Requests::status( $request->ID ) ] ?? '',
					count( LKP_Requests::documents( $request->ID ) ),
					LKP_Jalali::format( get_post_time( 'U', true, $request ), true ),
				)
			);
		}

		fclose( $out );
		exit;
	}
}
