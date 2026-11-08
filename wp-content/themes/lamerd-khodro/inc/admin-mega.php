<?php
/**
 * Brand editor screen for the header mega menu.
 *
 * Every parent brand of the `lk_brand` taxonomy can group its vehicles in tabs.
 * The tabs are its sub-brands (مدیران خودرو → ام وی ام / فونیکس / اکستریم), so
 * adding or removing a tab is adding or removing a brand, and each tab keeps
 * its own ordered vehicle list in term meta. Because the list lives in term
 * meta rather than in the vehicle's own terms, editing a vehicle in the admin
 * can never empty a tab.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Vehicles offered in a picker: the brand's own plus every sub-brand's.
 *
 * @param int $term_id Brand term ID.
 * @return array<int,string> Vehicle ID => title.
 */
function lk_mega_admin_vehicles( $term_id ) {
	$ids = get_posts(
		array(
			'post_type'      => 'lk_vehicle',
			'posts_per_page' => 300,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => 'lk_brand',
					'field'    => 'term_id',
					'terms'    => (int) $term_id,
				),
			),
		)
	);

	$out = array();
	foreach ( $ids as $id ) {
		$out[ (int) $id ] = get_the_title( $id );
	}

	return $out;
}

/**
 * Selected vehicles of a term, including any that left the brand meanwhile.
 *
 * @param WP_Term                   $term    Tab term.
 * @param array<int,string>         $choices Vehicles of the brand.
 * @return array<int,string> ID => title, ordered as saved.
 */
function lk_mega_admin_selected( WP_Term $term, $choices ) {
	$saved = get_term_meta( $term->term_id, LK_MEGA_VEHICLES_META, true );
	$saved = is_array( $saved ) ? array_map( 'absint', $saved ) : array();
	$out   = array();

	foreach ( $saved as $id ) {
		if ( isset( $choices[ $id ] ) ) {
			$out[ $id ] = $choices[ $id ];
			unset( $choices[ $id ] );
			continue;
		}

		$title = get_the_title( $id );
		if ( $title ) {
			$out[ $id ] = $title . ' (خارج از این برند)';
		}
	}

	return $out;
}

/**
 * Print the vehicle picker of one tab.
 *
 * @param string            $field_name Input name (supports [] arrays).
 * @param array<int,string> $choices    Vehicle ID => title.
 * @param int[]             $selected   Selected vehicle IDs.
 * @return void
 */
function lk_mega_admin_picker( $field_name, $choices, $selected ) {
	?>
	<select
		class="lk-mega-pick"
		name="<?php echo esc_attr( $field_name ); ?>[]"
		multiple="multiple"
		size="9"
	>
		<?php foreach ( $choices as $id => $title ) : ?>
			<option value="<?php echo esc_attr( $id ); ?>" <?php selected( in_array( (int) $id, $selected, true ) ); ?>>
				<?php echo esc_html( $title ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Fields on the "add brand" form.
 *
 * @return void
 */
function lk_mega_admin_add_fields() {
	?>
	<div class="form-field">
		<label for="lk_mega_order">ترتیب در نوار برندهای هدر</label>
		<input type="number" step="1" min="0" name="lk_mega_order" id="lk_mega_order" value="" />
		<p>عدد کوچک‌تر جلوتر می‌آید. خالی بگذارید تا بر اساس تعداد خودرو مرتب شود.</p>
	</div>
	<div class="form-field">
		<label>
			<input type="checkbox" name="lk_mega_visible" value="1" checked="checked" />
			نمایش در نوار برندهای هدر
		</label>
	</div>
	<?php
	wp_nonce_field( 'lk_mega_term_save', 'lk_mega_nonce' );
}

/**
 * Fields on the "edit brand" form.
 *
 * @param WP_Term $term Term being edited.
 * @return void
 */
function lk_mega_admin_edit_fields( $term ) {
	wp_nonce_field( 'lk_mega_term_save', 'lk_mega_nonce' );

	$is_parent = 0 === (int) $term->parent;
	$parent    = $is_parent ? $term : get_term( (int) $term->parent, 'lk_brand' );
	$choices   = false === $is_parent ? lk_mega_admin_vehicles( (int) $parent->term_id ) : array();
	$selected  = $is_parent ? array() : array_keys( lk_mega_admin_selected( $term, $choices ) );
	$order     = get_term_meta( $term->term_id, LK_MEGA_ORDER_META, true );
	$visible   = get_term_meta( $term->term_id, $is_parent ? LK_MEGA_VISIBLE_META : LK_MEGA_TAB_META, true );
	?>
	<tr class="form-field">
		<th scope="row">
			<label for="lk_mega_visible">
				<?php echo $is_parent ? 'نمایش در نوار برندهای هدر' : 'نمایش به‌عنوان تب'; ?>
			</label>
		</th>
		<td>
			<label>
				<input
					type="checkbox"
					name="lk_mega_visible"
					id="lk_mega_visible"
					value="1"
					<?php checked( '0' !== $visible ); ?>
				/>
				<?php echo $is_parent ? 'این برند در نوار بالای سایت دیده شود' : 'این زیربرند به‌صورت یک تب در منوی برند نمایش داده شود'; ?>
			</label>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row">
			<label for="lk_mega_order"><?php echo $is_parent ? 'ترتیب برند در نوار هدر' : 'ترتیب تب'; ?></label>
		</th>
		<td>
			<input
				type="number"
				step="1"
				min="0"
				name="lk_mega_order"
				id="lk_mega_order"
				value="<?php echo esc_attr( '' === $order ? '' : (int) $order ); ?>"
			/>
			<p class="description">عدد کوچک‌تر جلوتر می‌آید. خالی بگذارید تا بر اساس تعداد خودرو مرتب شود.</p>
		</td>
	</tr>

	<?php if ( ! $is_parent ) : ?>
		<tr class="form-field">
			<th scope="row"><label for="lk_mega_vehicles">خودروهای این تب</label></th>
			<td>
				<?php lk_mega_admin_picker( 'lk_mega_vehicles', $choices, $selected ); ?>
				<p class="description">
					هر چه انتخاب کنید همان‌ها در این تب نمایش داده می‌شوند و ترتیب نمایش همان ترتیب فهرست است.
					اگر هیچ خودرویی انتخاب نکنید، خودروهایی که به این زیربرند تخصیص داده شده‌اند نمایش داده می‌شوند.
				</p>
			</td>
		</tr>
	<?php endif; ?>

	<?php if ( $is_parent ) : ?>
		<?php
		$children = get_terms(
			array(
				'taxonomy'   => 'lk_brand',
				'parent'     => (int) $term->term_id,
				'hide_empty' => false,
			)
		);
		$children = is_wp_error( $children ) ? array() : $children;
		$choices  = lk_mega_admin_vehicles( (int) $term->term_id );

		usort(
			$children,
			static function ( $a, $b ) {
				$order_a = get_term_meta( $a->term_id, LK_MEGA_ORDER_META, true );
				$order_b = get_term_meta( $b->term_id, LK_MEGA_ORDER_META, true );
				$order_a = '' === $order_a ? 9999 : (int) $order_a;
				$order_b = '' === $order_b ? 9999 : (int) $order_b;

				return $order_a === $order_b ? strcmp( $a->name, $b->name ) : $order_a <=> $order_b;
			}
		);
		?>
		<tr class="form-field">
			<th scope="row">تب‌های منوی این برند</th>
			<td>
				<?php if ( $children ) : ?>
					<table class="widefat striped lk-mega-tabs-table">
						<thead>
							<tr>
								<th>عنوان تب</th>
								<th>نمایش</th>
								<th>ترتیب</th>
								<th>خودروهای تب</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $children as $child ) : ?>
								<?php
								$child_order    = get_term_meta( $child->term_id, LK_MEGA_ORDER_META, true );
								$child_visible  = get_term_meta( $child->term_id, LK_MEGA_TAB_META, true );
								$child_selected = lk_mega_admin_selected( $child, (array) $choices );
								?>
								<tr>
									<td>
										<input
											type="text"
											class="widefat"
											name="lk_mega_tabs[<?php echo esc_attr( $child->term_id ); ?>][name]"
											value="<?php echo esc_attr( $child->name ); ?>"
										/>
										<a href="<?php echo esc_url( get_edit_term_link( $child->term_id, 'lk_brand' ) ); ?>">ویرایش زیربرند</a>
									</td>
									<td>
										<input
											type="checkbox"
											name="lk_mega_tabs[<?php echo esc_attr( $child->term_id ); ?>][enabled]"
											value="1"
											<?php checked( '0' !== $child_visible ); ?>
										/>
									</td>
									<td>
										<input
											type="number"
											step="1"
											min="0"
											style="width:5.5em"
											name="lk_mega_tabs[<?php echo esc_attr( $child->term_id ); ?>][order]"
											value="<?php echo esc_attr( '' === $child_order ? '' : (int) $child_order ); ?>"
										/>
									</td>
									<td>
										<?php
										lk_mega_admin_picker(
											'lk_mega_tabs[' . $child->term_id . '][vehicles]',
											$child_selected + $choices,
											array_keys( $child_selected )
										);
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p class="description">
						برای برداشتن یک تب، تیک «نمایش» آن را بردارید؛ خود زیربرند و خودروهایش حذف نمی‌شوند.
					</p>
				<?php else : ?>
					<p class="description">هنوز زیربرندی برای این برند ثبت نشده است.</p>
				<?php endif; ?>

				<div class="form-field">
					<label for="lk_mega_new_tab">افزودن تب جدید</label>
					<input type="text" name="lk_mega_new_tab" id="lk_mega_new_tab" value="" class="regular-text" />
					<p class="description">
						نام تب را بنویسید (مثلاً «ام وی ام») تا زیربرند ساخته شود؛ بعد از ذخیره، خودروهای همان تب را از جدول بالا انتخاب کنید.
					</p>
				</div>
			</td>
		</tr>
	<?php endif; ?>
	<style>
		.lk-mega-tabs-table th { text-align: right; }
		.lk-mega-tabs-table td { vertical-align: top; padding: 10px 8px; }
		.lk-mega-pick { width: 100%; min-width: 15em; }
	</style>
	<?php
}

/**
 * Save the mega menu fields of a brand or sub-brand.
 *
 * @param int $term_id Term ID.
 * @return void
 */
function lk_mega_admin_save( $term_id ) {
	if ( ! isset( $_POST['lk_mega_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['lk_mega_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'lk_mega_term_save' ) ) {
		return;
	}

	$term = get_term( (int) $term_id, 'lk_brand' );
	if ( ! $term || is_wp_error( $term ) || ! current_user_can( 'edit_term', $term->term_id ) ) {
		return;
	}

	$is_parent = 0 === (int) $term->parent;

	// Brand visibility, or tab visibility for a sub-brand.
	$visible_key = $is_parent ? LK_MEGA_VISIBLE_META : LK_MEGA_TAB_META;
	if ( isset( $_POST['lk_mega_visible'] ) ) {
		update_term_meta( $term->term_id, $visible_key, '1' );
	} else {
		update_term_meta( $term->term_id, $visible_key, '0' );
	}

	// Position in the header bar (parent) or among the tabs (sub-brand).
	$order = isset( $_POST['lk_mega_order'] ) ? sanitize_text_field( wp_unslash( $_POST['lk_mega_order'] ) ) : '';
	if ( '' === $order ) {
		delete_term_meta( $term->term_id, LK_MEGA_ORDER_META );
	} else {
		update_term_meta( $term->term_id, LK_MEGA_ORDER_META, absint( $order ) );
	}

	// Vehicles picked for a sub-brand's own screen.
	if ( ! $is_parent ) {
		lk_mega_admin_save_vehicles( $term->term_id, isset( $_POST['lk_mega_vehicles'] ) ? wp_unslash( $_POST['lk_mega_vehicles'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return;
	}

	// Rows of the tabs table.
	$rows = isset( $_POST['lk_mega_tabs'] ) ? wp_unslash( $_POST['lk_mega_tabs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( is_array( $rows ) ) {
		foreach ( $rows as $child_id => $row ) {
			$child = get_term( absint( $child_id ), 'lk_brand' );
			if ( ! $child || is_wp_error( $child ) || (int) $child->parent !== (int) $term->term_id ) {
				continue;
			}

			if ( is_array( $row ) ) {
				update_term_meta( $child->term_id, LK_MEGA_TAB_META, empty( $row['enabled'] ) ? '0' : '1' );

				$child_order = isset( $row['order'] ) ? sanitize_text_field( $row['order'] ) : '';
				if ( '' === $child_order ) {
					delete_term_meta( $child->term_id, LK_MEGA_ORDER_META );
				} else {
					update_term_meta( $child->term_id, LK_MEGA_ORDER_META, absint( $child_order ) );
				}

				$name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
				if ( '' !== $name && $name !== $child->name ) {
					wp_update_term( $child->term_id, 'lk_brand', array( 'name' => $name ) );
				}

				lk_mega_admin_save_vehicles( $child->term_id, isset( $row['vehicles'] ) ? $row['vehicles'] : array() );
			}
		}
	}

	// A new tab is a new sub-brand.
	$new_tab = isset( $_POST['lk_mega_new_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['lk_mega_new_tab'] ) ) : '';
	if ( '' !== $new_tab ) {
		$created = wp_insert_term(
			$new_tab,
			'lk_brand',
			array(
				'parent' => (int) $term->term_id,
			)
		);

		if ( ! is_wp_error( $created ) ) {
			$siblings = get_terms(
				array(
					'taxonomy'   => 'lk_brand',
					'parent'     => (int) $term->term_id,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			$next = is_wp_error( $siblings ) ? 1 : count( $siblings );
			update_term_meta( $created['term_id'], LK_MEGA_ORDER_META, $next );
			update_term_meta( $created['term_id'], LK_MEGA_TAB_META, '1' );
		}
	}
}

/**
 * Store the picked vehicles of one tab.
 *
 * An empty list deletes the meta so the tab falls back to the vehicles that are
 * actually assigned to the sub-brand.
 *
 * @param int   $term_id Tab term ID.
 * @param mixed $raw     Raw request value.
 * @return void
 */
function lk_mega_admin_save_vehicles( $term_id, $raw ) {
	$ids = array();

	if ( is_array( $raw ) ) {
		foreach ( $raw as $id ) {
			$id = absint( $id );
			if ( $id && 'lk_vehicle' === get_post_type( $id ) ) {
				$ids[ $id ] = $id;
			}
		}
	}

	if ( $ids ) {
		update_term_meta( $term_id, LK_MEGA_VEHICLES_META, array_values( $ids ) );
	} else {
		delete_term_meta( $term_id, LK_MEGA_VEHICLES_META );
	}
}

add_action( 'lk_brand_add_form_fields', 'lk_mega_admin_add_fields' );
add_action( 'lk_brand_edit_form_fields', 'lk_mega_admin_edit_fields' );
add_action( 'created_lk_brand', 'lk_mega_admin_save' );
add_action( 'edited_lk_brand', 'lk_mega_admin_save' );
