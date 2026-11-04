<?php
/**
 * Vehicle admin list improvements: columns, filters, bulk edit.
 *
 * @package LamerdKhodro\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LK_Admin
 */
class LK_Admin {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'manage_lk_vehicle_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_lk_vehicle_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-lk_vehicle_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filters' ) );
		add_filter( 'parse_query', array( __CLASS__, 'filter_query' ) );
		add_action( 'bulk_edit_custom_box', array( __CLASS__, 'bulk_fields' ), 10, 2 );
		add_action( 'bulk_edit_posts', array( __CLASS__, 'bulk_save' ) );
		add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
	}

	/**
	 * List columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		return array(
			'cb'        => $columns['cb'] ?? '<input type="checkbox" />',
			'title'     => 'نام خودرو',
			'lk_brand'  => 'برند',
			'lk_year'   => 'سال',
			'lk_status' => 'وضعیت تولید',
			'lk_price'  => 'قیمت بازار',
			'lk_specs'  => 'مشخصات',
			'lk_source' => 'منبع',
			'date'      => 'تاریخ',
		);
	}

	/**
	 * Column output.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'lk_brand':
				$terms = get_the_terms( $post_id, 'lk_brand' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$links = array();
					foreach ( $terms as $term ) {
						$url   = add_query_arg(
							array(
								'post_type' => 'lk_vehicle',
								'lk_brand'  => $term->slug,
							),
							admin_url( 'edit.php' )
						);
						$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';
					}
					echo implode( '، ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '—';
				}
				break;

			case 'lk_year':
				$year = LK_Fields::get( $post_id, 'year' );
				echo $year ? esc_html( LK_Format::digits( $year ) ) : '—';
				break;

			case 'lk_status':
				$label = LK_Fields::production_status_label( $post_id );
				echo $label ? esc_html( $label ) : '—';
				break;

			case 'lk_price':
				$price = lk_get_price( $post_id );
				if ( $price && ! empty( $price['market_price'] ) ) {
					echo '<span dir="rtl">' . esc_html( LK_Format::price( $price['market_price'] ) ) . '</span> <small>تومان</small>';
				} else {
					echo '<span class="lk-muted">ناموجود</span>';
				}
				break;

			case 'lk_specs':
				$percent = LK_Fields::completion( $post_id );
				echo '<span class="lk-completion" title="کامل‌بودن مشخصات">' . esc_html( LK_Format::digits( $percent ) ) . '٪</span>';
				break;

			case 'lk_source':
				$source = (string) get_post_meta( $post_id, '_lk_source', true );
				echo $source ? '<span dir="ltr">' . esc_html( $source ) . '</span>' : 'دستی';
				break;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function sortable( $columns ) {
		$columns['lk_year']  = 'lk_year';
		$columns['lk_price'] = 'lk_price';
		return $columns;
	}

	/**
	 * Filter controls above the list.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function filters( $post_type ) {
		if ( 'lk_vehicle' !== $post_type ) {
			return;
		}

		$brand = isset( $_GET['lk_brand'] ) ? sanitize_title( wp_unslash( $_GET['lk_brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_dropdown_categories(
			array(
				'show_option_all' => 'همه برندها',
				'taxonomy'        => 'lk_brand',
				'name'            => 'lk_brand',
				'orderby'         => 'name',
				'selected'        => $brand,
				'value_field'     => 'slug',
				'hierarchical'    => true,
				'hide_empty'      => false,
			)
		);
	}

	/**
	 * Apply admin list filters and ordering.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public static function filter_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'lk_vehicle' !== $query->get( 'post_type' ) ) {
			return;
		}

		$meta_query = (array) $query->get( 'meta_query' );
		$orderby    = $query->get( 'orderby' );

		if ( 'lk_year' === $orderby ) {
			$query->set( 'meta_key', '_lk_year' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'lk_price' === $orderby ) {
			$query->set( 'meta_key', '_lk_market_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_query', array_merge( $meta_query, array( array( 'key' => '_lk_market_price', 'compare' => 'EXISTS' ) ) ) );
		}

		if ( isset( $_GET['lk_status'] ) && '' !== $_GET['lk_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status = sanitize_key( wp_unslash( $_GET['lk_status'] ) );
			if ( in_array( $status, array( 'in_production', 'stopped', 'upcoming' ), true ) ) {
				$meta_query[] = array(
					'key'   => '_lk_production_status',
					'value' => $status,
				);
				$query->set( 'meta_query', $meta_query );
			}
		}
	}

	/**
	 * Fields inside the bulk edit form.
	 *
	 * @param string $column_name Column name.
	 * @param string $post_type   Post type.
	 * @return void
	 */
	public static function bulk_fields( $column_name, $post_type ) {
		if ( 'lk_vehicle' !== $post_type ) {
			return;
		}

		if ( 'lk_brand' === $column_name ) {
			$brands = get_terms(
				array(
					'taxonomy'   => 'lk_brand',
					'hide_empty' => false,
				)
			);
			if ( is_wp_error( $brands ) ) {
				return;
			}
			?>
			<fieldset class="inline-edit-col-left" dir="rtl">
				<div class="inline-edit-col">
					<label class="inline-edit-group">
						<span class="title">برند</span>
						<select name="lk_bulk_brand">
							<option value="">— بدون تغییر —</option>
							<?php foreach ( $brands as $brand ) : ?>
								<option value="<?php echo esc_attr( $brand->term_id ); ?>"><?php echo esc_html( $brand->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
			</fieldset>
			<?php
		}

		if ( 'lk_status' === $column_name ) {
			?>
			<fieldset class="inline-edit-col-right" dir="rtl">
				<div class="inline-edit-col">
					<label class="inline-edit-group">
						<span class="title">وضعیت تولید</span>
						<select name="lk_bulk_status">
							<option value="">— بدون تغییر —</option>
							<option value="in_production">در حال تولید</option>
							<option value="stopped">تولید متوقف شده</option>
							<option value="upcoming">عرضه در آینده</option>
						</select>
					</label>
				</div>
			</fieldset>
			<?php
		}
	}

	/**
	 * Apply bulk edit values.
	 *
	 * @param int[] $post_ids Affected posts.
	 * @return void
	 */
	public static function bulk_save( $post_ids ) {
		if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$brand_raw  = isset( $_REQUEST['lk_bulk_brand'] ) ? absint( wp_unslash( $_REQUEST['lk_bulk_brand'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status_raw = isset( $_REQUEST['lk_bulk_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['lk_bulk_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		foreach ( $post_ids as $post_id ) {
			$post_id = absint( $post_id );
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			if ( $brand_raw ) {
				$term = get_term( $brand_raw, 'lk_brand' );
				if ( $term && ! is_wp_error( $term ) ) {
					wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'lk_brand', false );
				}
			}

			if ( $status_raw && in_array( $status_raw, array( 'in_production', 'stopped', 'upcoming' ), true ) ) {
				update_post_meta( $post_id, '_lk_production_status', $status_raw );
			}
		}
	}

	/**
	 * Notice when the price engine is missing.
	 *
	 * @return void
	 */
	public static function dependency_notice() {
		if ( class_exists( 'LKPE_Plugin' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>افزونه «مدیریت قیمت خودرو» فعال نیست؛ اطلاعات قیمت، تاریخچه و نمودارها در سایت نمایش داده نمی‌شوند.</p></div>';
	}

	/**
	 * Row actions.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( 'lk_vehicle' === $post->post_type ) {
			$actions['lk_view'] = '<a href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">مشاهده در سایت</a>';
		}
		return $actions;
	}
}
