<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Admin {

	public static function register(): void {
		add_action( 'admin_menu',              array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_gld_save_bundle',        array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_gld_delete_bundle',      array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_gld_set_active_bundle',  array( __CLASS__, 'handle_set_active' ) );
	}

	public static function add_menu(): void {
		add_menu_page(
			'Course Bundles',
			'Course Bundles',
			'manage_options',
			'gld-bundles',
			array( __CLASS__, 'render_page' ),
			'dashicons-archive',
			58
		);
	}

	// ── Handlers ──────────────────────────────────────────────────────────

	public static function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'gld_save_bundle' );

		$name           = sanitize_text_field( $_POST['gld_bundle_name'] ?? '' );
		$price          = (float) ( $_POST['gld_bundle_price'] ?? 0 );
		$seat_price     = (float) ( $_POST['gld_seat_price'] ?? 0 );
		$course_ids     = array_map( 'absint', (array) ( $_POST['gld_bundle_courses'] ?? array() ) );
		$edit_id        = absint( $_POST['gld_bundle_edit_id'] ?? 0 );

		if ( ! $name || $price < 0 ) {
			wp_safe_redirect( add_query_arg( 'gld_error', 'invalid', admin_url( 'admin.php?page=gld-bundles' ) ) );
			exit;
		}

		if ( $edit_id && get_post_type( $edit_id ) === 'product' && get_post_meta( $edit_id, '_gld_is_bundle', true ) ) {
			// Update existing bundle product.
			$product = wc_get_product( $edit_id );
			if ( $product ) {
				$product->set_name( $name );
				$product->set_regular_price( (string) $price );
				$product->save();
				update_post_meta( $edit_id, '_gld_bundle_courses', $course_ids );
				update_post_meta( $edit_id, '_gld_seat_price', $seat_price );
			}
		} else {
			// Create a new simple WooCommerce product for this bundle.
			$product = new WC_Product_Simple();
			$product->set_name( $name );
			$product->set_regular_price( (string) $price );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'hidden' ); // hidden from shop; used via direct checkout link
			$product_id = $product->save();
			update_post_meta( $product_id, '_gld_is_bundle', 1 );
			update_post_meta( $product_id, '_gld_bundle_courses', $course_ids );
			update_post_meta( $product_id, '_gld_seat_price', $seat_price );
		}

		wp_safe_redirect( add_query_arg( 'gld_saved', '1', admin_url( 'admin.php?page=gld-bundles' ) ) );
		exit;
	}

	public static function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		$product_id = absint( $_GET['product_id'] ?? 0 );
		check_admin_referer( 'gld_delete_bundle_' . $product_id );

		if ( $product_id && get_post_type( $product_id ) === 'product' && get_post_meta( $product_id, '_gld_is_bundle', true ) ) {
			wp_trash_post( $product_id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=gld-bundles' ) );
		exit;
	}

	public static function handle_set_active(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Forbidden' );
		}
		$product_id = absint( $_POST['product_id'] ?? 0 );
		check_admin_referer( 'gld_set_active_bundle_' . $product_id );

		if ( $product_id && get_post_type( $product_id ) === 'product' && get_post_meta( $product_id, '_gld_is_bundle', true ) ) {
			update_option( 'gld_access_product_id', $product_id );
		}

		wp_safe_redirect( add_query_arg( 'gld_active_saved', '1', admin_url( 'admin.php?page=gld-bundles' ) ) );
		exit;
	}

	// ── Render ────────────────────────────────────────────────────────────

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>WooCommerce must be active to use Course Bundles.</p></div></div>';
			return;
		}

		$bundles = self::get_all_bundles();
		$courses = self::get_all_courses();

		// Which bundle is being edited?
		$editing    = null;
		$editing_id = absint( $_GET['edit'] ?? 0 );
		if ( $editing_id ) {
			foreach ( $bundles as $b ) {
				if ( (int) $b['id'] === $editing_id ) {
					$editing = $b;
					break;
				}
			}
		}

		$currency     = get_woocommerce_currency_symbol();
		$saved        = ! empty( $_GET['gld_saved'] );
		$has_error    = ! empty( $_GET['gld_error'] );
		$active_saved = ! empty( $_GET['gld_active_saved'] );
		$active_id    = (int) get_option( 'gld_access_product_id', 0 );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Course Bundles</h1>
			<hr class="wp-header-end">

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Bundle saved successfully.</p></div>
			<?php endif; ?>
			<?php if ( $has_error ) : ?>
				<div class="notice notice-error is-dismissible"><p>Please enter a valid bundle name and price.</p></div>
			<?php endif; ?>
			<?php if ( $active_saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Active plan updated.</p></div>
			<?php endif; ?>
			<?php if ( ! $active_id && ! empty( $bundles ) ) : ?>
				<div class="notice notice-warning"><p>No bundle is marked as the <strong>Active Plan</strong> yet — leaders won't be able to activate their group's access until you set one below.</p></div>
			<?php endif; ?>

			<?php
			$bundles_per_page  = 20;
			$bundles_paged     = max( 1, (int) ( $_GET['bundle_paged'] ?? 1 ) );
			$bundles_total     = count( $bundles );
			$bundles_pages     = max( 1, (int) ceil( $bundles_total / $bundles_per_page ) );
			$bundles_page_rows = array_slice( $bundles, ( $bundles_paged - 1 ) * $bundles_per_page, $bundles_per_page );
			?>
			<?php if ( ! empty( $bundles ) ) : ?>
			<div style="max-width:1000px;max-height:480px;overflow-y:auto;margin-bottom:8px">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:100px">Active Plan</th>
						<th>Bundle Name</th>
						<th style="width:90px">Price/yr</th>
						<th style="width:110px">Per-student</th>
						<th>Courses</th>
						<th style="width:140px">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $bundles_page_rows as $b ) :
						$is_active = $active_id === (int) $b['id'];
					?>
					<tr<?php echo $is_active ? ' style="background:#f0f6fc"' : ''; ?>>
						<td>
							<?php if ( $is_active ) : ?>
								<span style="color:#2271b1;font-weight:600">&#10003; Active</span>
							<?php else : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="gld_set_active_bundle">
									<input type="hidden" name="product_id" value="<?php echo esc_attr( $b['id'] ); ?>">
									<?php wp_nonce_field( 'gld_set_active_bundle_' . $b['id'] ); ?>
									<button type="submit" class="button button-small">Set Active</button>
								</form>
							<?php endif; ?>
						</td>
						<td><strong><?php echo esc_html( $b['name'] ); ?></strong></td>
						<td><?php echo esc_html( $currency . number_format( $b['price'], 2 ) ); ?></td>
						<td><?php echo $b['seat_price'] > 0 ? esc_html( $currency . number_format( $b['seat_price'], 2 ) ) : '—'; ?></td>
						<td style="font-size:12px;color:#666">
							<?php echo esc_html( count( $b['course_ids'] ) . ' course' . ( count( $b['course_ids'] ) !== 1 ? 's' : '' ) ); ?>
							<?php if ( ! empty( $b['course_ids'] ) ) : ?>
								<details style="display:inline-block;margin-left:6px">
									<summary style="cursor:pointer;color:#2271b1">view</summary>
									<ul style="margin:4px 0 0 16px;font-size:12px">
										<?php foreach ( $b['course_ids'] as $cid ) : ?>
											<li><?php echo esc_html( get_the_title( $cid ) ); ?></li>
										<?php endforeach; ?>
									</ul>
								</details>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=gld-bundles&edit=' . $b['id'] ) ); ?>"
							   class="button button-small">Edit</a>
							&nbsp;
							<a href="<?php echo esc_url( wp_nonce_url(
								admin_url( 'admin-post.php?action=gld_delete_bundle&product_id=' . $b['id'] ),
								'gld_delete_bundle_' . $b['id']
							) ); ?>"
							   class="button button-small"
							   onclick="return confirm('Delete this bundle? This cannot be undone.');">Delete</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php if ( $bundles_pages > 1 ) : ?>
				<div style="max-width:1000px;display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:18px;font-size:13px;color:#666">
					<?php if ( $bundles_paged > 1 ) : ?>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'bundle_paged', $bundles_paged - 1 ) ); ?>">&larr; Prev</a>
					<?php else : ?>
						<span class="button button-small" style="opacity:.4;cursor:not-allowed">&larr; Prev</span>
					<?php endif; ?>
					<span>Page <?php echo (int) $bundles_paged; ?> of <?php echo (int) $bundles_pages; ?></span>
					<?php if ( $bundles_paged < $bundles_pages ) : ?>
						<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'bundle_paged', $bundles_paged + 1 ) ); ?>">Next &rarr;</a>
					<?php else : ?>
						<span class="button button-small" style="opacity:.4;cursor:not-allowed">Next &rarr;</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<p style="max-width:1000px;color:#666;font-size:13px;margin-bottom:30px">
				The <strong>Active Plan</strong> is the single plan offered to group leaders going forward — its per-student price is what's charged when a leader adds a learner. Only one bundle can be active at a time; other bundles stay listed for reference (e.g. groups that already purchased them keep their original terms).
			</p>
			<?php else : ?>
				<p style="color:#666;margin-bottom:24px">No bundles yet. Create your first bundle below.</p>
			<?php endif; ?>

			<div style="max-width:900px;background:#fff;border:1px solid #ccc;border-radius:4px;padding:24px">
				<h2 style="margin-top:0"><?php echo $editing ? 'Edit Bundle: ' . esc_html( $editing['name'] ) : 'Create New Bundle'; ?></h2>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="gld_save_bundle">
					<?php wp_nonce_field( 'gld_save_bundle' ); ?>
					<?php if ( $editing ) : ?>
						<input type="hidden" name="gld_bundle_edit_id" value="<?php echo esc_attr( $editing['id'] ); ?>">
					<?php endif; ?>

					<table class="form-table" style="max-width:100%">
						<tr>
							<th scope="row" style="width:160px"><label for="gld_bundle_name">Bundle Name</label></th>
							<td>
								<input type="text" id="gld_bundle_name" name="gld_bundle_name" class="regular-text"
								       value="<?php echo esc_attr( $editing['name'] ?? '' ); ?>" required>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gld_bundle_price">Price / year (<?php echo esc_html( $currency ); ?>) — optional</label></th>
							<td>
								<input type="number" id="gld_bundle_price" name="gld_bundle_price"
								       min="0" step="0.01" style="width:120px"
								       value="<?php echo esc_attr( $editing['price'] ?? '' ); ?>">
								<p class="description">Only charged if a leader renews via the emailed renewal link. Since pricing is now per-student (below), this can be left blank/0 for plans billed purely per seat.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="gld_seat_price">Per-seat price (<?php echo esc_html( $currency ); ?>)</label></th>
							<td>
								<input type="number" id="gld_seat_price" name="gld_seat_price"
								       min="0" step="0.01" style="width:120px"
								       value="<?php echo esc_attr( $editing['seat_price'] ?? '' ); ?>">
								<p class="description">Optional. Each learner added mid-year is charged this amount prorated to the subscription end date. Leave blank to disable per-seat billing.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label>Included Courses</label></th>
							<td>
								<input type="search" id="gld-course-filter" placeholder="Filter courses…"
								       style="width:300px;margin-bottom:10px;padding:6px 10px;border:1px solid #ccc;border-radius:4px">
								<div id="gld-course-list"
								     style="max-height:340px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;padding:8px 12px;display:grid;grid-template-columns:1fr 1fr;column-gap:24px;background:#fafafa">
									<?php
									$selected_courses = $editing['course_ids'] ?? array();
									foreach ( $courses as $cid => $ctitle ) :
									?>
										<label class="gld-course-item"
										       style="display:flex;align-items:baseline;gap:6px;padding:3px 0;font-size:13px;cursor:pointer"
										       data-title="<?php echo esc_attr( strtolower( $ctitle ) ); ?>">
											<input type="checkbox" name="gld_bundle_courses[]"
											       value="<?php echo esc_attr( $cid ); ?>"
											       <?php checked( in_array( $cid, $selected_courses, true ) ); ?>>
											<?php echo esc_html( $ctitle ); ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="description" style="margin-top:6px">
									<span id="gld-selected-count">0</span> course(s) selected.
								</p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<input type="submit" class="button button-primary"
						       value="<?php echo $editing ? 'Save Changes' : 'Create Bundle'; ?>">
						<?php if ( $editing ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=gld-bundles' ) ); ?>"
							   class="button" style="margin-left:8px">Cancel</a>
						<?php endif; ?>
					</p>
				</form>
			</div>
		</div>

		<script>
		(function() {
			var filter  = document.getElementById('gld-course-filter');
			var counter = document.getElementById('gld-selected-count');
			var list    = document.getElementById('gld-course-list');

			function updateCount() {
				counter.textContent = list.querySelectorAll('input:checked').length;
			}

			filter.addEventListener('input', function() {
				var q = this.value.toLowerCase();
				list.querySelectorAll('.gld-course-item').forEach(function(el) {
					el.style.display = (q === '' || el.dataset.title.includes(q)) ? '' : 'none';
				});
			});

			list.addEventListener('change', updateCount);
			updateCount();
		})();
		</script>
		<?php
	}

	// ── Helpers ───────────────────────────────────────────────────────────

	public static function get_all_bundles(): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}
		$posts = get_posts( array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_key'       => '_gld_is_bundle',
			'meta_value'     => 1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$bundles = array();
		foreach ( $posts as $post ) {
			$product    = wc_get_product( $post->ID );
			$course_ids = (array) get_post_meta( $post->ID, '_gld_bundle_courses', true );
			$course_ids = array_filter( array_map( 'intval', $course_ids ) );
			$bundles[]  = array(
				'id'         => $post->ID,
				'name'       => $post->post_title,
				'price'      => (float) $product->get_regular_price(),
				'price_html' => $product->get_price_html(),
				'course_ids' => array_values( $course_ids ),
				'seat_price' => (float) get_post_meta( $post->ID, '_gld_seat_price', true ),
			);
		}
		return $bundles;
	}

	public static function get_all_courses(): array {
		$posts = get_posts( array(
			'post_type'      => learndash_get_post_type_slug( 'course' ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$map = array();
		foreach ( $posts as $p ) {
			$map[ $p->ID ] = $p->post_title;
		}
		return $map;
	}
}
