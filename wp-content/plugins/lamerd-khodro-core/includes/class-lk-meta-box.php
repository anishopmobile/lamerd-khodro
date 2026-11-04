<?php
/**
 * Vehicle specification meta box.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Meta_Box
 */
class LK_Meta_Box {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
		add_action( 'save_post_lk_vehicle', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public static function add() {
		add_meta_box(
			'lk_vehicle_specs',
			'مشخصات خودرو',
			array( __CLASS__, 'render' ),
			'lk_vehicle',
			'normal',
			'high'
		);

		add_meta_box(
			'lk_vehicle_side',
			'وضعیت اطلاعات',
			array( __CLASS__, 'render_side' ),
			'lk_vehicle',
			'side',
			'default'
		);
	}

	/**
	 * Load assets on the vehicle editor.
	 *
	 * @param string $hook Admin hook.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'lk_vehicle' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'lk-core-admin', LK_URL . 'assets/css/core-admin.css', array(), LK_VERSION );
		wp_enqueue_script( 'lk-core-admin', LK_URL . 'assets/js/core-admin.js', array( 'jquery' ), LK_VERSION, true );
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render( $post ) {
		wp_nonce_field( 'lk_vehicle_meta', 'lk_vehicle_meta_nonce' );
		$groups = LK_Fields::groups();
		?>
		<div class="lk-metabox" dir="rtl">
			<div class="lk-metabox__nav" role="tablist">
				<?php $i = 0; ?>
				<?php foreach ( $groups as $group_key => $group ) : ?>
					<button type="button"
						class="lk-metabox__tab<?php echo 0 === $i ? ' is-active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
						data-lk-tab="<?php echo esc_attr( $group_key ); ?>">
						<?php echo esc_html( $group['label'] ); ?>
					</button>
					<?php ++$i; ?>
				<?php endforeach; ?>
			</div>

			<div class="lk-metabox__panels">
				<?php $i = 0; ?>
				<?php foreach ( $groups as $group_key => $group ) : ?>
					<div class="lk-metabox__panel<?php echo 0 === $i ? ' is-active' : ''; ?>"
						id="lk-panel-<?php echo esc_attr( $group_key ); ?>"
						data-lk-panel="<?php echo esc_attr( $group_key ); ?>"
						role="tabpanel">
						<div class="lk-metabox__grid">
							<?php foreach ( $group['fields'] as $key => $field ) : ?>
								<?php self::render_field( $key, $field, $post->ID ); ?>
							<?php endforeach; ?>
						</div>
					</div>
					<?php ++$i; ?>
				<?php endforeach; ?>
			</div>
			<p class="lk-metabox__note">
				فیلدهای خالی در سایت نمایش داده نمی‌شوند. قیمت‌ها به‌صورت خودکار از موتور قیمت دریافت می‌شوند و در این فرم وارد نمی‌شوند.
			</p>
		</div>
		<?php
	}

	/**
	 * Render one field control.
	 *
	 * @param string $key     Field key.
	 * @param array  $field   Definition.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	private static function render_field( $key, $field, $post_id ) {
		$name  = 'lk[' . $key . ']';
		$id    = 'lk-field-' . $key;
		$value = LK_Fields::get( $post_id, $key );
		$wide  = in_array( $field['type'], array( 'textarea', 'media' ), true ) ? ' lk-field--wide' : '';
		?>
		<div class="lk-field<?php echo esc_attr( $wide ); ?>">
			<label class="lk-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>

			<?php if ( 'select' === $field['type'] ) : ?>
				<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
					<option value="">— انتخاب کنید —</option>
					<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( (string) $value, (string) $option_value ); ?>>
							<?php echo esc_html( $option_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php elseif ( 'textarea' === $field['type'] ) : ?>
				<textarea name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" rows="<?php echo esc_attr( $field['rows'] ?? 4 ); ?>"><?php echo esc_textarea( is_array( $value ) ? '' : (string) $value ); ?></textarea>
			<?php elseif ( 'media' === $field['type'] ) : ?>
				<div class="lk-media" data-lk-gallery>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>"
						value="<?php echo esc_attr( implode( ',', is_array( $value ) ? $value : array() ) ); ?>" />
					<div class="lk-media__preview" data-lk-gallery-preview>
						<?php
						$ids = is_array( $value ) ? $value : array();
						foreach ( $ids as $attachment_id ) {
							$thumb = wp_get_attachment_image( (int) $attachment_id, 'thumbnail' );
							if ( $thumb ) {
								echo '<span class="lk-media__item" data-id="' . esc_attr( $attachment_id ) . '">' . $thumb . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
							}
						}
						?>
					</div>
					<button type="button" class="button" data-lk-gallery-add>افزودن تصویر</button>
					<button type="button" class="button-link" data-lk-gallery-clear>پاک کردن گالری</button>
				</div>
			<?php else : ?>
				<input type="<?php echo 'number' === $field['type'] ? 'number' : 'text'; ?>"
					name="<?php echo esc_attr( $name ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( is_array( $value ) ? '' : (string) $value ); ?>"
					<?php if ( 'number' === $field['type'] ) : ?>
						step="<?php echo esc_attr( $field['step'] ?? '1' ); ?>"
						<?php if ( isset( $field['min'] ) ) : ?>min="<?php echo esc_attr( $field['min'] ); ?>"<?php endif; ?>
						<?php if ( isset( $field['max'] ) ) : ?>max="<?php echo esc_attr( $field['max'] ); ?>"<?php endif; ?>
					<?php endif; ?>
					<?php if ( ! empty( $field['placeholder'] ) ) : ?>placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"<?php endif; ?>
					<?php if ( in_array( $key, array( 'name_en', 'country', 'website' ), true ) ) : ?>dir="ltr"<?php endif; ?> />
			<?php endif; ?>

			<?php if ( ! empty( $field['unit'] ) ) : ?>
				<span class="lk-field__unit"><?php echo esc_html( $field['unit'] ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $field['hint'] ) ) : ?>
				<span class="lk-field__hint"><?php echo esc_html( $field['hint'] ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Side meta box with data provenance.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public static function render_side( $post ) {
		$source    = (string) get_post_meta( $post->ID, '_lk_source', true );
		$source_id = (string) get_post_meta( $post->ID, '_lk_source_id', true );
		$synced    = (string) get_post_meta( $post->ID, '_lk_last_synced', true );
		$views     = (int) get_post_meta( $post->ID, '_lk_views', true );
		$price     = function_exists( 'lk_get_price' ) ? lk_get_price( $post->ID ) : null;
		?>
		<ul class="lk-side">
			<li><span>کامل‌بودن مشخصات</span><strong><?php echo esc_html( LK_Format::digits( LK_Fields::completion( $post->ID ) ) ); ?>٪</strong></li>
			<li><span>منبع اطلاعات</span><strong dir="ltr"><?php echo esc_html( $source ? $source : 'دستی' ); ?></strong></li>
			<?php if ( $source_id ) : ?>
				<li><span>شناسه منبع</span><strong dir="ltr"><?php echo esc_html( $source_id ); ?></strong></li>
			<?php endif; ?>
			<?php if ( $synced ) : ?>
				<li><span>آخرین همگام‌سازی</span><strong><?php echo esc_html( LK_Format::jalali( strtotime( $synced ), 'datetime' ) ); ?></strong></li>
			<?php endif; ?>
			<li><span>بازدید</span><strong><?php echo esc_html( LK_Format::number( $views ) ); ?></strong></li>
			<?php if ( $price ) : ?>
				<li><span>قیمت بازار</span><strong><?php echo $price['market_price'] ? esc_html( LK_Format::price( $price['market_price'] ) . ' تومان' ) : 'ناموجود'; ?></strong></li>
			<?php endif; ?>
		</ul>
		<?php
	}

	/**
	 * Persist submitted values.
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
		if ( ! isset( $_POST['lk_vehicle_meta_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['lk_vehicle_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'lk_vehicle_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['lk'] ) && is_array( $_POST['lk'] ) ? wp_unslash( $_POST['lk'] ) : array();
		LK_Fields::save( $post_id, $raw );
	}
}
