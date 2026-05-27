<?php

namespace uncanny_learndash_groups;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WoocommerceLicense
 *
 * @package uncanny_learndash_groups
 */
class WoocommerceLicense {

	/**
	 * @var bool
	 */
	public $deduct_prices = false;
	/**
	 * @var array
	 */
	public static $product_price = array();

	/**
	 * WoocommerceLicense constructor.
	 */
	public function __construct() {
		add_filter(
			'product_type_selector',
			array(
				$this,
				'add_license_product',
			),
			12
		);

		add_filter(
			'product_type_options',
			array(
				$this,
				'add_virtual_and_downloadable_checks',
			)
		);

		add_action( 'admin_head', array( $this, 'woo_admin_head' ) );

		add_action(
			'delete_user',
			array(
				$this,
				'reset_seat_for_deleted_user',
			),
			999
		);

		add_action(
			'plugins_loaded',
			array(
				$this,
				'load_new_product_type',
			),
			1
		);

		add_action(
			'plugins_loaded',
			array(
				$this,
				'woocommerce_order_again_button',
			)
		);

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 200 );

		add_action(
			'woocommerce_thankyou',
			array(
				$this,
				'woocommerce_redirect_to_group',
			),
			1
		);

		add_action(
			'admin_enqueue_scripts',
			array(
				$this,
				'license_custom_js',
			),
			999
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'add_minimal_woocommerce_style' ), 999 );

		add_action(
			'uo_new_group_purchased',
			array(
				$this,
				'uo_new_group_purchased_func',
			),
			10,
			2
		);

		add_action(
			'pre_get_posts',
			array(
				$this,
				'hide_license_product_from_searches',
			)
		);
		// WP No index
		add_action(
			'wp_robots',
			array(
				$this,
				'manually_add_noindex_to_licenses',
			),
			PHP_INT_MAX
		);

		// add redirect check
		add_action(
			'template_redirect',
			array(
				$this,
				'maybe_redirect_to_shop',
			)
		);
	}

	/**
	 *
	 */
	public function plugins_loaded() {
		add_filter(
			'post_row_actions',
			array(
				$this,
				'remove_row_actions',
			),
			1010,
			2
		);

		add_filter(
			'woocommerce_checkout_cart_item_quantity',
			array(
				$this,
				'woocommerce_cart_item_qty_func',
			),
			99,
			3
		);

		add_filter(
			'woocommerce_get_price_html',
			array(
				$this,
				'change_product_price_display',
			),
			70,
			2
		);
		add_filter(
			'woocommerce_cart_item_price',
			array(
				$this,
				'change_product_cart_item_price',
			),
			99,
			3
		);
		add_filter(
			'woocommerce_product_get_price',
			array(
				$this,
				'return_custom_price',
			),
			9,
			2
		);
		add_filter(
			'woocommerce_product_data_tabs',
			array(
				$this,
				'custom_product_tabs',
			)
		);
		add_filter(
			'woocommerce_product_data_tabs',
			array(
				$this,
				'hide_attributes_data_panel',
			)
		);

		add_filter(
			'woocommerce_cart_item_thumbnail',
			array(
				$this,
				'woocommerce_cart_item_thumbnail_func',
			),
			99,
			3
		);
		add_filter(
			'woocommerce_cart_item_permalink',
			array(
				$this,
				'woocommerce_cart_item_permalink_func',
			),
			99,
			3
		);

		add_filter(
			'woocommerce_order_item_permalink',
			array(
				$this,
				'woocommerce_order_item_permalink',
			),
			99,
			3
		);

		/*Message Filter*/
		add_filter(
			'wc_add_to_cart_message_html',
			array(
				$this,
				'custom_add_to_cart_message_html',
			),
			10,
			3
		);

		/************************************/
		add_action(
			'woocommerce_checkout_process',
			array(
				$this,
				'process_group_name',
			),
			30
		);

		add_action(
			'woocommerce_license_add_to_cart',
			array(
				$this,
				'woocommerce_simple_add_to_cart',
			),
			30
		);

		add_action(
			'woocommerce_product_data_panels',
			array(
				$this,
				'license_options_product_tab_content',
			)
		);

		add_action(
			'woocommerce_order_status_completed',
			array(
				$this,
				'process_license_odr_complete',
			),
			99
		);

		add_action(
			'woocommerce_process_product_meta_license',
			array(
				$this,
				'save_license_option_field',
			)
		);

		add_action(
			'woocommerce_process_product_meta_variable_license',
			array(
				$this,
				'save_license_option_field',
			)
		);

		add_action(
			'woocommerce_admin_order_data_after_billing_address',
			array(
				$this,
				'grp_admin_ordr_billing_address',
			)
		);

		add_action( 'woocommerce_after_order_notes', array( $this, 'add_group_name_field_checkout' ) );

		/* New Functionality to add courses with licenses*/
		if ( 'yes' === get_option( 'add_courses_as_part_of_license', 'no' ) ) {
			add_action(
				'woocommerce_add_to_cart',
				array(
					$this,
					'woocommerce_add_to_cart_func',
				),
				999
			);
			add_action(
				'woocommerce_before_calculate_totals',
				array(
					$this,
					'deduct_courses_in_cart',
				),
				29,
				1
			);
		}

		///////////////
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'group_name_item_meta' ), 1, 4 );

		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'get_license_group_name' ), 1, 2 );
		// Display note in cart and checkout pages as cart item data - Optional
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_item_group_name' ), 20, 2 );

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			array(
				$this,
				'checkout_table_formatted_meta_data',
			),
			20,
			2
		);

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_group_name_field' ) );

		add_action(
			'woocommerce_after_cart_item_name',
			array(
				$this,
				'add_editable_group_name_field_in_cart',
			),
			10,
			2
		);

		add_action(
			'woocommerce_update_cart_action_cart_updated',
			array(
				$this,
				'save_custom_cart_item_data',
			),
			20,
			1
		);
	}

	/**
	 * @return void
	 */
	public function add_minimal_woocommerce_style() {
		if ( is_checkout() || is_cart() ) {
			wp_register_style( 'minimal_woocommerce', plugins_url( 'src/assets/legacy/frontend/css/woocommerce.css', UNCANNY_GROUPS_PLUGIN_FILE ), '', Utilities::get_version() );
			wp_enqueue_style( 'minimal_woocommerce' );
		}

		if ( is_product() || is_cart() ) { // Ensure CSS is only added on product pages
			$custom_css = "
			.group-name-field {
				margin-bottom: 15px;
			}
			.group-name-field label {
				display: block;
				margin-bottom: 5px;
				font-weight: bold;
			}
			.group-name-field input[type='text'] {
				width: 100%;
				padding: 8px;
				border: 1px solid #ccc;
				border-radius: 4px;
			}";
			$custom_css = apply_filters( 'ulgm_woocommerce_custom_css', $custom_css );
			wp_add_inline_style( 'woocommerce-inline', $custom_css ); // Add to existing WooCommerce styles
		}
	}

	/**
	 * @param $query
	 */
	public function hide_license_product_from_searches( $query ) {
		if ( $query->is_search() && $query->is_main_query() ) {
			$tax_query = $query->get( 'tax_query', array() );

			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'exclude-from-catalog',
				'operator' => 'NOT IN',
			);

			$query->set( 'tax_query', $tax_query );

		}
	}

	/**
	 *
	 */
	public function woocommerce_simple_add_to_cart() {
		wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

	/**
	 * @param $message
	 * @param $products
	 * @param $show_qty
	 *
	 * @return string
	 */
	public function custom_add_to_cart_message_html( $message, $products, $show_qty ) {
		$override = get_option( 'ulgm_add_to_cart_message', '' );
		if ( ! empty( $override ) && ulgm_filter_has_var( 'add-seats' ) ) {
			$titles = array();
			$count  = 0;

			if ( ! is_array( $products ) ) {
				$products = array( $products => 1 );
				$show_qty = false;
			}

			if ( ! $show_qty ) {
				$products = array_fill_keys( array_keys( $products ), 1 );
			}
			$found_license = false;
			foreach ( $products as $product_id => $qty ) {
				/* translators: %s: product name */
				$product = wc_get_product( $product_id );
				if ( $product instanceof \WC_Product && $product->is_type( 'license' ) ) {
					$found_license = true;
					break;
				}
			}

			if ( ! $found_license ) {
				return $message;
			}

			foreach ( $products as $product_id => $qty ) {
				/* translators: %s: product name */
				$titles[] = ( $qty > 1 ? absint( $qty ) . ' &times; ' : '' ) . apply_filters( 'woocommerce_add_to_cart_item_name_in_quotes', sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), strip_tags( get_the_title( $product_id ) ) ), $product_id );
				$count    += $qty;
			}

			$titles = array_filter( $titles );

			// The custom message is just below
			$added_text = sprintf( str_replace( '{{product}}', '%s', $override ), wc_format_list_of_items( $titles ) );

			// Output success messages
			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				$return_to = apply_filters( 'woocommerce_continue_shopping_redirect', wc_get_raw_referer() ? wp_validate_redirect( wc_get_raw_referer(), false ) : wc_get_page_permalink( 'shop' ) );
				$message   = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( $return_to ), esc_html__( 'Continue shopping', 'woocommerce' ), esc_html( $added_text ) );
			} else {
				$message = sprintf( '%s', esc_html( $added_text ) );
			}
		}

		return $message;
	}

	/**
	 *
	 */
	public function woo_admin_head() {
		if ( ulgm_filter_has_var( 'post' ) && ! empty( ulgm_filter_input( 'post' ) ) ) {
			if ( has_term( 'license', 'product_type', absint( ulgm_filter_input( 'post' ) ) ) ) {
				?>
				<style>
					#delete-action {
						display: none;
					}
				</style>
				<?php
			}
		}
	}

	/**
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function remove_row_actions( $actions, $post ) {
		if ( 'product' === get_post_type() ) {
			if ( has_term( 'license', 'product_type', $post ) ) {
				unset( $actions['trash'] );
			}
		}

		return $actions;
	}

	/**
	 * @param $permalink
	 * @param $item
	 * @param $order
	 *
	 * @return string
	 */
	public function woocommerce_order_item_permalink( $permalink, $item, $order ) {

		$product = wc_get_product( $item['product_id'] );
		if ( ! ( $product instanceof \WC_Product ) ) {
			return $permalink;
		}
		if ( strpos( $product->get_slug(), 'license' ) ) {
			return '';
		} else {
			return $permalink;
		}
	}

	/**
	 *
	 */
	public function woocommerce_order_again_button() {
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
	}

	/**
	 *
	 */
	public function modify_woo_cart_css() {
		if ( is_cart() || is_checkout() ) {
			wp_register_style( 'ulgm-frontend', Utilities::get_asset( 'frontend', 'bundle.min.css' ), array(), Utilities::get_version() );
			$user_colors = Utilities::user_colors();
			wp_add_inline_style( 'ulgm-frontend', $user_colors );
			wp_enqueue_style( 'ulgm-frontend', $user_colors );
		}
	}

	/**
	 * @param $permalink
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return bool
	 */
	public function woocommerce_cart_item_qty_func( $qty, $cart_item, $cart_item_key ) {
		if ( empty( $cart_item ) ) {
			return $qty;
		}
		$product = $cart_item['data'];
		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'license' ) ) {
			return $qty;
		}
		if ( true === apply_filters( 'ulgm_show_per_seat_with_qty', true, $cart_item, $cart_item_key ) ) {
			$per_seat_text = get_option( 'ulgm_per_seat_text_plural', 'Seats' );
			$qty           = apply_filters(
				'ulgm_per_seat_text_with_qty',
				sprintf(
					'%s&nbsp;%s',
					$qty,
					$per_seat_text
				),
				$qty,
				$per_seat_text
			);
		}

		return $qty;
	}

	/**
	 * @param $permalink
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return bool
	 */
	public function woocommerce_cart_item_permalink_func( $permalink, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item ) ) {
			$product = $cart_item['data'];
			if ( $product instanceof \WC_Product && $product->is_type( 'license' ) ) {
				return false;
			}
		}

		return $permalink;
	}

	/**
	 * @param $product_get_image
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return bool
	 */
	public function woocommerce_cart_item_thumbnail_func( $product_get_image, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item ) ) {
			$product = $cart_item['data'];
			if ( $product instanceof \WC_Product && $product->is_type( 'license' ) ) {
				return apply_filters( 'ulgm_license_product_thumbnail_in_cart', false, $product_get_image, $product );
			}
		}

		return $product_get_image;
	}

	/**
	 *
	 */
	public function add_hidden_field_css() {
		if ( is_cart() ) {
			?>
			<style>
				.woocommerce table.shop_table th {
					padding: 5px 7.5px;
				}

				th.product-remove {
					width: 7%;
					min-width: 10%;
				}

				th.product-name {
					width: 46%;
					min-width: 10%;
				}

				th.product-price {
					width: 18%;
					min-width: 10%;

				}

				th.product-quantity {
					width: 15%;
					min-width: 10%;
				}

				th.product-subtotal {
					width: 15%;
					min-width: 10%;
				}

				th.product-thumbnail,
				td.product-thumbnail {
					display: none !important;
				}
			</style>
			<?php
		}
		if ( is_checkout() ) {
			?>
			<style>
				.form-uo-hidden {
					display: none !important;
				}
			</style>
			<?php
		}
	}

	/**
	 * @param $product_id
	 *
	 * @return bool
	 */
	public function check_if_courses_in_subscription_items( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! ( $product instanceof \WC_Product ) ) {
			return false;
		}
		if ( class_exists( 'WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product->get_id() ) ) {
			$courses = get_post_meta( $product->get_id(), SharedFunctions::$license_meta_field, true );
			if ( ! empty( $courses ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 */
	public function add_front_scripts() {
		wp_enqueue_script( 'ulgm-frontend', Utilities::get_asset( 'frontend', 'bundle.min.js' ), array( 'jquery' ), Utilities::get_version(), true );
	}

	/**
	 *
	 */
	public function load_new_product_type() {
		if ( class_exists( '\WC_Product' ) ) {
			include_once Utilities::get_include( 'woocommerce/wc_product_license.php' );
			new \WC_Product_License( 'license' );
		}
	}


	/**
	 * @param $types
	 *
	 * @return mixed
	 */
	public function add_license_product( $types ) {

		// Key should be exactly the same as in the class product_type parameter
		$types['license'] = __( 'LearnDash group license' );

		return $types;
	}

	/**
	 * Show pricing fields for license product.
	 */
	public function license_custom_js() {
		if ( SharedFunctions::load_backend_bundles() ) {
			wp_enqueue_script( 'ulgm-backend', Utilities::get_asset( 'backend', 'bundle.min.js' ), array( 'jquery' ), Utilities::get_version(), true );
		}
	}

	/**
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function custom_product_tabs( $tabs ) {
		$tabs['license'] = array(
			'label'    => sprintf( __( 'License %s(s)', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'course' ) ),
			'target'   => 'license_options',
			'priority' => 100,
			'class'    => array(
				'show_if_license',
				'show_if_variable_license',
				'show_if_subscription',
			),
		);

		//$tabs = array_merge( $tabs, $new_tab );

		return $tabs;
	}


	/**
	 *
	 */
	public function license_options_product_tab_content() {
		global $post;
		$license = $this->list_course_products();
		?>
		<div id='license_options' class='panel woocommerce_options_panel'>
			<style>
				.woocommerce_options_panel ._ulgm_license_field .description {
					display: block;
					width: 50%;
					clear: both
				}
			</style>
			<div class='options_group show_if_license show_if_subscription'>
				<?php
				$kb = sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.uncannyowl.com/knowledge-base/creating-pre-configured-learndash-group-licenses/?utm_medium=uo_groups&utm_campaign=edit_product_page#Creating_a_pre-configured_LearnDash_Group_License_Product_Subscription_payments', __( 'this knowledge base article', 'uncanny-learndash-groups' ) );
				$this->woocommerce_wp_select_multiple(
					array(
						'id'          => SharedFunctions::$license_meta_field,
						'name'        => '_license[]',
						'label'       => sprintf( __( 'Linked %s', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'courses' ) ),
						'description' => sprintf( __( 'If the box above is empty, first create at least one LearnDash Group %1$s product. See %2$s for details.', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'course' ), $kb ),
						'options'     => $license,
					)
				);
				?>
			</div>

		</div>
		<?php
	}


	/**
	 * @param $post_id
	 */
	public function save_license_option_field( $post_id ) {
		if ( ulgm_filter_has_var( '_license', INPUT_POST ) ) {
			update_post_meta( $post_id, SharedFunctions::$license_meta_field, ulgm_filter_input_array( '_license', INPUT_POST ) );
		} else {
			delete_post_meta( $post_id, SharedFunctions::$license_meta_field );
		}
	}

	/**
	 * @return array
	 */
	public function list_course_products() {
		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => 999,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'courses',
				),
			),
		);
		$posts      = get_posts( $query_args );
		$license    = array();
		if ( $posts ) {
			foreach ( $posts as $post ) {
				$license[ $post->ID ] = get_the_title( $post->ID );
			}
		}

		return $license;
	}


	/**
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function hide_attributes_data_panel( $tabs ) {
		// Other default values for 'attribute' are; general, inventory, shipping, linked_product, variations, advanced
		//$tabs['general']['class'][]        = 'show_if_license show_if_variable_license';
		$tabs['attribute']['class'][]      = 'hide_if_license hide_if_variable_license';
		$tabs['linked_product']['class'][] = 'hide_if_license hide_if_variable_license';
		$tabs['variations']['class'][]     = 'hide_if_license hide_if_variable_license';
		$tabs['shipping']['class'][]       = 'hide_if_license hide_if_variable_license';

		return $tabs;
	}

	/**
	 * @param $field
	 */
	public function woocommerce_wp_select_multiple( $field ) {
		global $thepostid, $post, $woocommerce;
		$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['value']         = isset( $field['value'] ) ? $field['value'] : ( get_post_meta( $thepostid, $field['id'], true ) ? get_post_meta( $thepostid, $field['id'], true ) : array() );

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="' . esc_attr( $field['class'] ) . '" multiple="multiple">';

		foreach ( $field['options'] as $key => $value ) {

			echo '<option value="' . esc_attr( $key ) . '" ' . ( in_array( $key, $field['value'] ) ? 'selected="selected"' : '' ) . '>' . esc_html( $value ) . '</option>';

		}

		echo '</select> ';

		if ( ! empty( $field['description'] ) ) {

			if ( isset( $field['desc_tip'] ) && false !== $field['desc_tip'] ) {
				echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . esc_url( WC()->plugin_url() ) . '/assets/images/help.png" height="16" width="16" />';
			} else {
				echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
			}
		}
		echo '</p>';
	}

	/**
	 *
	 * @param $order_id
	 */
	public function process_license_odr_complete( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! $order->has_status( 'completed' ) ) {
			return;
		}

		$linked_group_id = (int) $order->get_meta( '_ulgm_linked_group_id', true );
		if ( 0 < $linked_group_id ) {
			return; // Bailout -- Group was already created for this order.
		}

		$user = $order->get_user();
		if ( ! $user instanceof \WP_User ) {
			return;
		}
		$user_id = $user->ID;

		//New Method
		$transient  = maybe_unserialize( $order->get_meta( '_ulgm_user_' . $user_id . '_order', true ) );
		$transient2 = maybe_unserialize( $order->get_meta( '_ulgm_user_buy_courses_' . $user_id . '_order', true ) );
		if ( $transient || $transient2 ) {
			return;
		}

		//Fall back < 3.7 Groups
		$transient  = SharedFunctions::get_transient_cache( '_ulgm_user_' . $user_id . '_order', $user_id, true );
		$transient2 = SharedFunctions::get_transient_cache( '_ulgm_user_buy_courses_' . $user_id . '_order', $user_id, true );
		if ( $transient || $transient2 ) {
			return;
		}

		$line_items = $order->get_items( 'line_item' );
		if ( ! $line_items ) {
			return;
		}

		// If order contains a subscription, bail
		if ( class_exists( '\WC_Subscription', false ) && wcs_order_contains_subscription( $order ) ) {
			return;
		}

		/** @var \WC_Order_Item $item */
		foreach ( $line_items as $item ) {

			// Let Subscription class handle it
			if ( class_exists( 'WC_Subscriptions_Product', false ) && \WC_Subscriptions_Product::is_subscription( $item->get_product() ) ) {
				continue;
			}

			if ( self::is_license_or_subscription_license( $item->get_product() ) ) {

				$_quantity = absint( $item->get_quantity() );

				$product_id = absint( $item->get_product_id() );

				$courses = SharedFunctions::get_courses_from_license_product( $product_id );

				$group_title = $item->meta_exists( 'ulgm_group_name' ) ? $item->get_meta( 'ulgm_group_name' ) : '';

				if ( empty( $group_title ) ) {
					$group_title = $this->set_group_name_from_order_id( $order_id );
					$item->update_meta_data( 'ulgm_group_name', $group_title );
					$order->update_meta_data( 'ulgm_group_name_' . $product_id, $group_title );
				} else {
					$group_title = $this->set_group_name_from_order_id( $order_id, $group_title );
				}

				$args = array(
					'order_id'    => $order_id,
					'product_id'  => $product_id,
					'qty'         => $_quantity,
					'user'        => $user,
					'courses'     => $courses,
					'group_title' => $group_title,
				);

				$this->generate_learndash_group( $args );
			}
		}
	}

	/**
	 * @param $args
	 *
	 * @return void
	 */
	public function generate_learndash_group( $args ) {
		$order_id       = $args['order_id'];
		$order          = wc_get_order( $order_id );
		$product_id     = $args['product_id'];
		$qty            = $args['qty'];
		$user           = $args['user'];
		$user_id        = $user->ID;
		$courses_linked = $args['courses'];
		$group_title    = $args['group_title'];

		if ( 'yes' === get_post_meta( $product_id, '_uo_custom_buy_product', true ) ) {
			wp_update_post(
				array(
					'ID'          => $product_id,
					'post_author' => apply_filters( 'ulgm_custom_license_post_author', $user->ID, get_current_user_id(), 'license-purchase' ),
				)
			);
		}

		if ( ! user_can( $user, 'group_leader' ) && ! user_can( $user, 'administrator' ) ) {
			$user->add_role( 'group_leader' );
		}

		$ld_group_args = apply_filters(
			'ulgm_insert_group',
			array(
				'post_type'    => 'groups',
				'post_status'  => 'publish',
				'post_title'   => $group_title,
				'post_content' => '',
				'post_author'  => apply_filters( 'ulgm_custom_group_post_author', $user->ID, get_current_user_id(), 'license-purchase' ),
			)
		);

		do_action( 'ulgm_before_license_group_is_inserted', $ld_group_args, $this );

		$group_id = wp_insert_post( $ld_group_args );

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$type = 'groups';

			// Get the translation id (trid)
			$trid = 0;

			// Set the desired language
			$language_code = apply_filters( 'wpml_current_language', 'en' );

			// Update the post language info
			$language_args = array(
				'element_id'           => $group_id,
				'element_type'         => 'post_' . $type,
				'trid'                 => $trid,
				'language_code'        => $language_code,
				'source_language_code' => null,
			);

			do_action( 'wpml_set_element_language_details', $language_args );
		}

		$courses_linked = apply_filters( 'ulgm_license_group_courses_linked_in_order', $courses_linked, $group_id, $this );

		if ( $courses_linked ) {
			foreach ( $courses_linked as $course_id ) {
				$course_id = apply_filters( 'ulgm_license_group_linked_course_id', $course_id, $group_id, $this );

				ld_update_course_group_access( (int) $course_id, (int) $group_id, false );

				$transient_key = 'learndash_course_groups_' . $course_id;

				delete_transient( $transient_key );
			}
		}

		$price_type = apply_filters( 'ulgm_new_group_license_order_price_type', 'closed', $group_id );

		update_post_meta( $group_id, '_ld_price_type', $price_type );

		update_post_meta(
			$group_id,
			'_groups',
			array(
				'groups_group_price_type' => $price_type,
			)
		);

		//$qty = $_quantity;
		update_post_meta( $group_id, '_ulgm_total_seats', $qty );

		$attr = array(
			'user_id'    => $user_id,
			'order_id'   => $order_id,
			'product_id' => $product_id,
			'group_id'   => $group_id,
			'group_name' => $group_title,
			'qty'        => $qty,
		);

		$codes         = ulgm()->group_management->generate_random_codes( $qty );
		$code_group_id = ulgm()->group_management->add_code_group( $attr );
		$code_group_id = ulgm()->group_management->add_codes( $attr, $codes, $code_group_id );

		//update_post_meta( $order_id, SharedFunctions::$code_group_id_meta_key, $code_group_id );
		$order->update_meta_data( SharedFunctions::$linked_group_id_meta, $group_id );
		$order->add_meta_data( SharedFunctions::$linked_group_id_meta . '_' . $group_id, $group_id );
		$order->add_meta_data( SharedFunctions::$code_group_id_meta_key . '_' . $group_id, $code_group_id );
		$order->add_meta_data( SharedFunctions::$group_name_field . '_' . $group_id, $group_title );

		$order->save();

		update_post_meta( $group_id, '_ulgm_total_seats', $qty );
		update_post_meta( $group_id, '_ulgm_version', Utilities::get_version() );
		update_post_meta( $group_id, '_ulgm_license_product_id', $product_id );
		update_post_meta( $group_id, SharedFunctions::$code_group_id_meta_key, $code_group_id );

		if ( 'yes' === get_post_meta( $product_id, '_uo_custom_buy_product', true ) ) {
			update_post_meta( $group_id, '_uo_custom_group', 'yes' );
		}

		// This is user is the creator or the group and cannot be remove on the frontend group management only
		//update_post_meta( $group_id, 'locked_admin_group_leader', $user->ID );
		ld_update_leader_group_access( $user->ID, $group_id );

		//Setting group leader as a member of the group & using 1 code / qty for it.
		if ( 'yes' !== get_option( 'do_not_add_group_leader_as_member', 'no' ) ) {
			Group_Management_Helpers::add_existing_user( array( 'user_email' => $user->user_email ), true, $group_id, $order_id, SharedFunctions::$redeem_status, false, false, false, true );
		}

		SharedFunctions::remove_transient_cache( 'all' );

		do_action( 'uo_new_group_purchased', $group_id, $user->ID );
		do_action( 'ulgm_after_license_group_is_inserted', $group_id, $ld_group_args, $this );
	}


	/**
	 * @param $order_id
	 */
	public function woocommerce_redirect_to_group( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_id    = $order->get_user_id();
		$transient  = maybe_unserialize( $order->get_meta( '_ulgm_user_' . $user_id . '_order', true ) );
		$transient2 = maybe_unserialize( $order->get_meta( '_ulgm_user_buy_courses_' . $user_id . '_order', true ) );

		if ( $transient || $transient2 ) {
			return;
		}

		if ( ! $order->has_status( 'completed' ) ) {
			return;
		}

		$line_items = $order->get_items( 'line_item' );
		if ( ! $line_items ) {
			return;
		}

		$continue = false; //Added to remove force redirect unless user overrides by following filter
		$continue = apply_filters( 'uo_redirect_after_checkout', $continue, $order_id );
		if ( true === $continue && ! is_admin() ) {
			$url = SharedFunctions::get_group_management_page_id( true );
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * @param $cart_object
	 *
	 * @return mixed
	 */
	public function woo_license_price_update( $cart_object ) {
		$total    = 0;
		$products = $cart_object->cart_contents;
		if ( $products ) {
			foreach ( $products as $prod ) {
				$id      = $prod['product_id'];
				$product = wc_get_product( $id );
				if ( $product instanceof \WC_Product && $product->is_type( 'license' ) ) {
					$linked_courses = get_post_meta( $id, SharedFunctions::$license_meta_field, true );
					if ( $linked_courses ) {
						foreach ( $linked_courses as $course ) {
							$course_product = wc_get_product( $course );
							if ( $course_product ) {
								$price = SharedFunctions::get_custom_product_price( $course_product );
								$total = floatval( $total ) + floatval( $price );
							}
						}
					}
					$prod['data']->price = $total;
				}
			}
		}

		return $cart_object;
	}


	/**
	 * @param $price
	 * @param \WC_Product $product
	 *
	 * @return int
	 */
	public function return_custom_price( $price, \WC_Product $product ) {

		if ( $product instanceof \WC_Product && $product->is_type( 'license' ) ) {
			if ( empty( $price ) || 0 === $price ) {
				$get_price = SharedFunctions::get_custom_product_price( $product, true );
				$product->set_price( $get_price );
				$product->set_regular_price( $get_price );
				$product->save();
				WoocommerceBuyCourses::clear_caches( $product );
				$price = $get_price;
			}
		}

		return $price;
	}

	/**
	 * @param $price
	 * @param $product
	 *
	 * @return string
	 */
	public function change_product_price_display( $price, $product ) {
		if ( $product instanceof \WC_Product && $product->is_type( 'license' ) ) {
			$price = $this->add_per_seat_text( $price, $product );
		}

		return $price;
	}

	/**
	 * @param $price
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return string
	 */
	public function change_product_cart_item_price( $price, $cart_item, $cart_item_key ) {
		if ( ! empty( $cart_item ) ) {
			if ( is_array( $cart_item ) ) {
				$cart_item = $cart_item['data'];
			}

			if ( $cart_item instanceof \WC_Product && $cart_item->is_type( 'license' ) ) {
				$raw_price = wc_price( get_post_meta( $cart_item->get_id(), '_price', true ) );
				$price     = $this->add_per_seat_text( $raw_price, $cart_item );
			}
		}

		return $price;
	}

	/**
	 * @param $price
	 * @param \WC_Product $product
	 *
	 * @return mixed|void
	 */
	public function add_per_seat_text( $price, \WC_Product $product ) {

		// Value can be saved on settings page
		$per_seat_text  = get_option( 'ulgm_per_seat_text', 'Seat' );
		$per_group_text = esc_html__( 'Group', 'uncanny-learndash-groups' );
		//$price         = wc_price( SharedFunctions::get_custom_product_price( $product ) );

		if ( WoocommerceMinMaxQuantity::is_fixed_price_set_for_the_license( $product->get_id() ) ) {
			$price = preg_replace( '/\<\/bdi\>\<\/span\>/', "</bdi> &#47; $per_group_text</span>", $price );
		} else {
			$price = preg_replace( '/\<\/bdi\>\<\/span\>/', "</bdi> &#47; $per_seat_text</span>", $price );
		}

		return apply_filters( 'ulgm_per_seat_text', $price, $product, $per_seat_text );
	}

	/**
	 * @throws \Exception
	 */
	public function woocommerce_add_to_cart_func() {
		$user_id    = wp_get_current_user()->ID;
		$transient  = SharedFunctions::get_transient_cache( '_ulgm_user_' . $user_id . '_order', $user_id );
		$transient2 = SharedFunctions::get_transient_cache( '_ulgm_user_buy_courses_' . $user_id . '_order', $user_id );

		// Not modifying a group
		if ( ! $transient && ! $transient2 ) {
			if ( sizeof( \WC()->cart->get_cart() ) > 0 ) {
				foreach ( \WC()->cart->get_cart() as $cart_item_key => $values ) {
					$_product            = $values['data'];
					$this->deduct_prices = false;
					if ( $_product instanceof \WC_Product && $_product->is_type( 'license' ) ) {
						$found      = true;
						$product_id = $_product->get_id();
						if ( $found && $product_id > 0 ) {
							$courses = get_post_meta( $product_id, SharedFunctions::$license_meta_field, true );
							if ( ! empty( $courses ) ) {
								foreach ( $courses as $course ) {
									if ( ! $this->woo_in_cart( $course ) ) {
										\WC()->cart->add_to_cart( $course, 1 );
									}
								}
							}
							$this->deduct_prices = true;
						}
					}
				}
			}
		}

		if ( $transient2 ) {
			if ( sizeof( \WC()->cart->get_cart() ) > 0 ) {
				foreach ( \WC()->cart->get_cart() as $cart_item_key => $values ) {
					$_product            = $values['data'];
					$this->deduct_prices = false;
					if ( $_product instanceof \WC_Product && $_product->is_type( 'license' ) ) {
						$found      = true;
						$product_id = $_product->get_id();
						if ( $found && $product_id > 0 ) {
							$courses = get_post_meta( $product_id, SharedFunctions::$license_meta_field . '_new', true );
							//$courses = get_post_meta( $product_id, SharedFunctions::$license_meta_field, true );
							if ( ! empty( $courses ) ) {
								foreach ( $courses as $course ) {
									if ( ! $this->woo_in_cart( $course ) ) {
										\WC()->cart->add_to_cart( $course, 1 );
									}
								}
							}
							$this->deduct_prices = true;
						} else {
							$this->deduct_prices = false;
						}
					}
				}
			}
		}
	}

	/**
	 * @param \WC_Cart $cart
	 */
	public function deduct_courses_in_cart( \WC_Cart $cart ) {
		$user_id    = wp_get_current_user()->ID;
		$transient  = SharedFunctions::get_transient_cache( '_ulgm_user_' . $user_id . '_order', $user_id );
		$transient2 = SharedFunctions::get_transient_cache( '_ulgm_user_buy_courses_' . $user_id . '_order', $user_id );
		// No modification
		if ( ! $transient && ! $transient2 ) {
			if ( count( $cart->cart_contents ) > 0 ) {
				foreach ( $cart->cart_contents as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( $_product instanceof \WC_Product && $_product->is_type( 'license' ) ) {
						$product_id = $_product->get_id();
						$courses    = get_post_meta( $product_id, SharedFunctions::$license_meta_field, true );
						foreach ( $cart->cart_contents as $c_item_key => $_values ) {
							$_product = $_values['data'];
							if ( is_array( $courses ) && $_product instanceof \WC_Product && $_product->is_type( 'courses' ) && in_array( $_product->get_id(), $courses ) ) {
								$_product->set_price( 0 );
							}
						}
					}
				}
			}
		}
		// Add courses
		if ( $transient2 ) {
			if ( count( $cart->cart_contents ) > 0 ) {
				foreach ( $cart->cart_contents as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( $_product instanceof \WC_Product && $_product->is_type( 'license' ) ) {
						$product_id = $_product->get_id();
						$courses    = get_post_meta( $product_id, SharedFunctions::$license_meta_field, true );
						foreach ( $cart->cart_contents as $c_item_key => $_values ) {
							$_product = $_values['data'];
							if ( $_product instanceof \WC_Product && $_product->is_type( 'courses' ) && in_array( $_product->get_id(), $courses ) ) {
								$_product->set_price( 0 );
							}
						}
						$courses = get_post_meta( $product_id, SharedFunctions::$license_meta_field . '_new', true );
						foreach ( $cart->cart_contents as $c_item_key => $_values ) {
							$_product = $_values['data'];
							if ( $_product instanceof \WC_Product && $_product->is_type( 'courses' ) && in_array( $_product->get_id(), $courses ) ) {
								$_product->set_price( 0 );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param $product_id
	 *
	 * @return bool
	 */
	public function woo_in_cart( $product_id ) {
		//global $woocommerce;

		foreach ( \WC()->cart->get_cart() as $key => $val ) {
			$_product = $val['data'];

			if ( absint( $product_id ) === absint( $_product->get_id() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 *
	 */
	public function process_group_name() {

		if ( WoocommerceModifyGroup::is_modifying_license() ) {
			return;
		}

		if ( false === apply_filters( 'ulgm_group_name_required', true ) ) {
			return;
		}

		foreach ( \WC()->cart->get_cart() as $key => $val ) {
			$product_id = $val['product_id'];
			$product    = wc_get_product( $product_id );

			if ( false === self::is_license_or_subscription_license( $product ) ) {
				continue;
			}

			$per_seat_text = get_option( 'ulgm_per_seat_text', 'Seat' );

			if ( ! ulgm_filter_has_var( 'ulgm_group_name_' . $key, INPUT_POST ) || empty( ulgm_filter_input( 'ulgm_group_name_' . $key, INPUT_POST ) ) ) {
				wc_add_notice( sprintf( '%s %s x %d %s(s)', esc_html__( 'Please enter group name for:', 'uncanny-learndash-groups' ), $product->get_name(), $val['quantity'], $per_seat_text ), 'error' );
			}
		}
	}

	/**
	 * @return bool
	 */
	public function do_show_group_name_field() {

		$has_license_product = $this->check_if_license_product_in_cart();
		if ( class_exists( '\uncanny_learndash_groups\WoocommerceLicenseSubscription' ) ) {
			$has_license_subscription = WoocommerceLicenseSubscription::check_if_course_subscription_in_cart();
		} else {
			$has_license_subscription = array( 'status' => false );
		}

		// No license / subscription license found. Bail
		if ( ! isset( $has_license_subscription['status'] ) && ! isset( $has_license_product['status'] ) ) {
			return false;
		}

		// No license / subscription license found. Bail
		if ( false === $has_license_product['status'] && false === $has_license_subscription['status'] ) {
			return false;
		}

		/**
		 * If user is not logged in, show field
		 */
		if ( ! is_user_logged_in() ) {
			return true;
		}

		if ( true === $has_license_subscription['status'] && function_exists( 'wcs_cart_contains_resubscribe' ) ) {
			$resub_exists = wcs_cart_contains_resubscribe( WC()->cart );
			// Re-subscription found. Bail
			if ( ! empty( $resub_exists ) ) {
				return false;
			}
		}

		$show_field = true;
		$user_id    = wp_get_current_user()->ID;
		$transient  = SharedFunctions::get_transient_cache( '_ulgm_user_' . $user_id . '_order', $user_id );
		$transient2 = SharedFunctions::get_transient_cache( '_ulgm_user_buy_courses_' . $user_id . '_order', $user_id );
		if ( $transient || $transient2 ) {
			// User is adding new seats / courses. No need to show field
			$show_field = false;
		}

		return $show_field;
	}

	/**
	 * @param int $order_id
	 * @param bool $from_changes
	 * @param \WC_Order $order
	 *
	 * @return string
	 */
	public function set_group_name_from_order_id( $order_id, $group_name = '' ) {
		$order = wc_get_order( $order_id );

		if ( '' === $group_name && $order ) {
			$group_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ' ' . $order->get_billing_company();
		}

		//update_post_meta( $order_id, SharedFunctions::$group_name_field, sanitize_text_field( $group_name ) );

		return apply_filters( 'ulgm_group_name', $group_name, $order );
	}

	/**
	 * @param \WC_Order $order
	 */
	public function grp_admin_ordr_billing_address( $order ) {
		$order_id        = $order->get_id();
		$parent_order_id = $order->get_meta( 'parent_order_id', true );
		if ( ! empty( $parent_order_id ) && is_numeric( $parent_order_id ) ) {
			$order_id = $parent_order_id;
		} else {
			// may be order id exists in another key
			$user = $order->get_customer_id();
			$k    = "_ulgm_user_buy_courses_{$user}_order";
			$meta = $order->get_meta( $k, true );
			if ( $meta && isset( $meta['order_details']['order_id'] ) && is_numeric( $meta['order_details']['order_id'] ) ) {
				$order_id = absint( $meta['order_details']['order_id'] );
			}
		}
		$ld_group_id = SharedFunctions::get_ld_group_id_from_order_id( $order_id );
		if ( ! empty( $ld_group_id ) && is_numeric( $ld_group_id ) ) {
			$group_name = get_the_title( $ld_group_id );
			$edit_link  = get_edit_post_link( $ld_group_id );
			$group_name = "<a href='{$edit_link}' title='" . esc_html__( 'Linked LearnDash group', 'uncanny-learndash-groups' ) . "'>{$group_name}</a>";
		} else {
			$group_name = $order->get_meta( SharedFunctions::$group_name_field, true );
		}
		printf( '<p><strong>%s:</strong><br />%s</p>', esc_html__( 'LearnDash group name', 'uncanny-learndash-groups' ), $group_name );
	}


	/**
	 * @return array
	 */
	public function check_if_license_product_in_cart() {
		$items  = \WC()->cart->get_cart();
		$return = array( 'status' => false );
		if ( $items ) {
			foreach ( $items as $item ) {
				if ( isset( $item['data'] ) && ! empty( $item['data'] ) ) {
					$product = $item['data'];
					if ( ! ( $product instanceof \WC_Product ) ) {
						continue;
					}
					if ( $product->is_type( 'license' ) ) {
						$return = array(
							'status'     => true,
							'product_id' => $product->get_id(),
						);
						break;
					}
				}
			}
		}
		return $return;
	}

	/**
	 * @param $order_id
	 *
	 * @return array
	 */
	public function check_if_license_product_in_order( $order_id ) {
		$order  = wc_get_order( $order_id );
		$items  = $order->get_items();
		$return = array( 'status' => false );
		if ( $items ) {
			/** @var \WC_Order_Item_Product $item */
			foreach ( $items as $item ) {
				$product = $item->get_product();
				if ( ! ( $product instanceof \WC_Product ) ) {
					continue;
				}
				if ( $product->is_type( 'license' ) ) {
					$return = array(
						'status'     => true,
						'product_id' => $product->get_id(),
					);
					break;
				}
			}
		}
		return $return;
	}

	/**
	 * @param $product_id
	 *
	 * @return bool
	 */
	public function check_if_license_product_in_items( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! ( $product instanceof \WC_Product ) ) {
			return false;
		}
		if ( $product->is_type( 'license' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @param $user_id
	 */
	public function reset_seat_for_deleted_user( $user_id ) {
		$learndash_groups = learndash_get_users_group_ids( $user_id, true );
		if ( $learndash_groups ) {
			foreach ( $learndash_groups as $group_id ) {
				Group_Management_Helpers::remove_user_from_group( $user_id, $group_id );
			}
		}
	}

	/**
	 * @param $group_id
	 * @param $group_leader_id
	 */
	public function uo_new_group_purchased_func( $group_id, $group_leader_id ) {
		// Send New group purchase email, for extra validation we are sending in the user id and getting user data from WP because there may be filters
		$send_email = true;
		if ( 'yes' !== get_option( 'ulgm_send_new_group_purchase_email', 'yes' ) ) {
			$send_email = false;
		}

		if ( $send_email ) {
			// Add group leader/Create group email subject
			$ulgm_send_new_group_purchase_email_subject = SharedVariables::ulgm_new_group_purchase_email_subject();

			// Add group leader/Create group email subject
			$ulgm_send_new_group_purchase_email_body = SharedVariables::ulgm_new_group_purchase_email_body();

			$group_title = get_the_title( $group_id );
			// Send Welcome email, for extra validation we are sending in the user id and getting user data from WP because there may be filters
			Group_Management_Helpers::send_new_group_purchase_email( $group_leader_id, $ulgm_send_new_group_purchase_email_subject, $ulgm_send_new_group_purchase_email_body, $group_title, $group_id );
		}
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function add_virtual_and_downloadable_checks( $options ) {

		if ( isset( $options['virtual'] ) ) {
			$options['virtual']['wrapper_class'] = $options['virtual']['wrapper_class'] . ' show_if_license show_if_courses';
		}

		if ( isset( $options['downloadable'] ) ) {
			$options['downloadable']['wrapper_class'] = $options['downloadable']['wrapper_class'] . ' show_if_license show_if_courses';
		}

		return $options;
	}

	/**
	 * @param $robots
	 *
	 * @return mixed
	 */
	public function manually_add_noindex_to_licenses( $robots ) {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return $robots;
		}
		if ( 'product' !== $post->post_type ) {
			return $robots;
		}
		$product = wc_get_product( $post->ID );
		if ( ! $product instanceof \WC_Product ) {
			return $robots;
		}
		if ( ! $product->is_type( 'license' ) ) {
			return $robots;
		}
		if ( empty( get_post_meta( $post->ID, '_uo_custom_buy_product', true ) ) ) {
			return $robots;
		}
		$robots['noindex']  = true;
		$robots['nofollow'] = true;

		// Check if another plugin is adding the `index` and `follow` directives
		if ( isset( $robots['index'] ) ) {
			$robots['index'] = false;
		}

		if ( isset( $robots['follow'] ) ) {
			$robots['follow'] = false;
		}

		return $robots;
	}

	/**
	 * @param $product
	 * @param bool $include_name
	 * @param bool $return
	 *
	 * @return string|array
	 */
	public static function fetch_courses_from_license( $product, $include_name = false, $return = false ) {
		if ( ! ( $product instanceof \WC_Product ) ) {
			return '';
		}
		$product_name = $product->get_name();
		$course_names = array();
		$courses      = get_post_meta( $product->get_id(), SharedFunctions::$license_meta_field, true );
		$courses_new  = get_post_meta( $product->get_id(), SharedFunctions::$license_meta_field . '_new', true );
		if ( ! empty( $courses_new ) ) {
			foreach ( $courses_new as $new ) {
				$course_names[] = get_the_title( $new );
			}
		} elseif ( ! empty( $courses ) ) {
			foreach ( $courses as $c ) {
				$course_names[] = get_the_title( $c );
			}
		}
		if ( empty( $course_names ) ) {
			return '';
		}
		if ( $return ) {
			return $course_names;
		}
		$learn_dash_labels = new \LearnDash_Custom_Label();
		$course_label      = $learn_dash_labels::get_label( 'courses' );
		if ( ! empty( $course_names ) ) {
			$courses = '<ul class="ulgm courses-assigned">';
			foreach ( $course_names as $n ) {
				$courses .= '<li>' . $n . '</li>';
			}
			$courses .= '</ul>';
		}
		$return = sprintf(
			'<p class="ulgm courses-assigned-heading"><strong>%s:</strong> %s</p>',
			$course_label,
			$courses
		);
		if ( true === $include_name ) {
			$return = sprintf(
				'<p class="ulgm courses-assigned-heading"><strong>%s in %s:</strong> %s</p>',
				$course_label,
				$product_name,
				$courses
			);
		}
		return $return;
	}

	/**
	 * Grab Group Name from the product ID and store it in the order item meta
	 *
	 * @param $cart_item_data
	 * @param $product_id
	 *
	 * @return mixed
	 * @version 4.0
	 * @author Saad S.
	 *
	 */
	public function get_license_group_name( $cart_item_data, $product_id ) {

		if ( WoocommerceModifyGroup::is_modifying_license() ) {
			return $cart_item_data;
		}

		if ( ! $product_id ) {
			return $cart_item_data;
		}

		$group_name = get_post_meta( $product_id, '_group_name', true );

		// Maybe in the $_POST
		if ( ulgm_filter_has_var( 'ulgm_group_name', INPUT_POST ) && ! empty( ulgm_filter_input( 'ulgm_group_name', INPUT_POST ) ) ) {
			$group_name = esc_attr( ulgm_filter_input( 'ulgm_group_name', INPUT_POST ) );
		}

		if ( empty( $group_name ) ) {
			return $cart_item_data;
		}

		$cart_item_data['ulgm_group_name'] = $group_name;

		$courses = self::fetch_courses_from_license( wc_get_product( $product_id ), false, true );
		if ( is_array( $courses ) ) {
			$cart_item_data['ulgm_courses'] = join( ', ', $courses );
		} else {
			$cart_item_data['ulgm_courses'] = $courses;
		}
		$cart_item_data['unique_key'] = md5( microtime() . rand() );

		return $cart_item_data;
	}

	/**
	 * @param $cart_item_data
	 * @param $cart_item
	 *
	 * @return array
	 */
	public function display_item_group_name( $cart_item_data, $cart_item ) {

		if ( WoocommerceModifyGroup::is_modifying_license() ) {
			return $cart_item_data;
		}

		if ( isset( $cart_item['ulgm_group_name'] ) && ! is_cart() ) {
			$cart_item_data[] = array(
				'name'  => sprintf( __( '%s Name', 'uncanny-learndash-groups' ), \LearnDash_Custom_Label::get_label( 'group' ) ),
				'value' => isset( $cart_item['ulgm_group_name'] ) ? $cart_item['ulgm_group_name'] : '',
			);
		}

		$product_id = $cart_item['product_id'];
		$product    = wc_get_product( $product_id );

		if ( ! ( $product instanceof \WC_Product ) ) {
			return $cart_item_data;
		}

		if ( 'license' !== $product->get_type() ) {
			return $cart_item_data;
		}

		//if ( isset( $cart_item['ulgm_courses'] ) && ! empty( $cart_item['ulgm_courses'] ) ) {
		if (
			true === apply_filters( 'ulgm_woocommerce_hide_course_title_in_product', false, null, null ) ||
			true === apply_filters( 'ulgm_woocommerce_hide_course_title_in_subscription', false, '', null, null )
		) {
			return $cart_item_data;
		}

		$courses = self::fetch_courses_from_license( $product, false, true );

		$value = '';
		if ( ! empty( $courses ) && is_array( $courses ) ) {
			$value = join( ', ', $courses );
		}

		$cart_item_data[] = array(
			'name'  => \LearnDash_Custom_Label::get_label( 'courses' ),
			'value' => empty( $cart_item['ulgm_courses'] ) ? $value : $cart_item['ulgm_courses'],
		);

		//}

		return $cart_item_data;
	}

	/**
	 * @param \WC_Order_Item_Product $item
	 * @param $cart_item_key
	 * @param $values
	 * @param \WC_Order $order
	 */
	public function group_name_item_meta( $item, $cart_item_key, $values, $order ) {

		$product = $item->get_product();

		if ( false === self::is_license_or_subscription_license( $product ) ) {
			return;
		}

		$group_name = '';

		$key = 'ulgm_group_name_' . $cart_item_key;

		// The $_POST['ulgm_group_name'] should contain group names indexed by cart item keys
		if ( ulgm_filter_has_var( $key, INPUT_POST ) && ! empty( ulgm_filter_input( $key, INPUT_POST ) ) ) {
			$new_group_name = esc_attr( sanitize_text_field( ulgm_filter_input( $key, INPUT_POST ) ) );

			$group_name = $new_group_name;
		}

		// Group name is empty, generate from the order
		if ( ! empty( trim( $group_name ) ) ) {
			$item->update_meta_data( 'ulgm_group_name', $group_name );
		}

		if ( isset( $values['ulgm_courses'] ) ) {
			$item->update_meta_data( 'ulgm_courses', sanitize_text_field( $values['ulgm_courses'] ) );
		}
	}

	/**
	 * @param $formatted_meta
	 * @param $order
	 *
	 * @return mixed
	 */
	public function checkout_table_formatted_meta_data( $formatted_meta, $order ) {
		$ulgm_group_name_mods    = false;
		$ulgm_group_courses_mods = false;
		$j                       = 1;

		if ( ! $formatted_meta ) {
			return $formatted_meta;
		}

		foreach ( $formatted_meta as $fk => $fm ) {

			if ( 'ulgm_group_name' === $fm->key && ! $ulgm_group_name_mods ) {
				unset( $fm->display_key );
				unset( $fm->display_value );
				$fm->display_key      = sprintf( '%s %s', \LearnDash_Custom_Label::get_label( 'group' ), _x( 'Name', '"Name" in Group Name on checkout', 'uncanny-learndash-groups' ) );
				$fm->display_value    = $fm->value;
				$ulgm_group_name_mods = true;
				++$j;
			}

			if ( 'ulgm_courses' === $fm->key && ! $ulgm_group_courses_mods ) {
				if (
					true === apply_filters( 'ulgm_woocommerce_hide_course_title_in_product', false, null, null ) ||
					true === apply_filters( 'ulgm_woocommerce_hide_course_title_in_subscription', false, '', null, null )
				) {
					unset( $formatted_meta[ $fk ] );
					continue;
				}

				$fm->display_key         = \LearnDash_Custom_Label::get_label( 'courses' );
				$fm->display_value       = $fm->value;
				$ulgm_group_courses_mods = true;
				++$j;
			}
		}

		return $formatted_meta;
	}

	/**
	 * @return void
	 */
	public function add_group_name_field() {

		if ( WoocommerceModifyGroup::is_modifying_license() ) {
			return;
		}

		if ( apply_filters( 'ulgm_hide_group_name_field_on_product_page', false ) ) {
			return;
		}

		if ( 'yes' === get_option( 'ulgm_hide_group_name_fields_on_product_page', 'no' ) ) {
			return;
		}

		global $product;

		if ( false === self::is_license_or_subscription_license( $product ) ) {
			return;
		}

		echo '<div class="group-name-field">';
		echo '<label for="ulgm_group_name">' . apply_filters( 'ulgm_group_name_text', esc_attr__( 'Group Name', 'uncanny-learndash-groups' ) ) . '</label>';
		echo '<input type="text" id="ulgm_group_name" name="ulgm_group_name" />';
		echo '</div>';
	}

	/**
	 * @param $cart_item
	 * @param $cart_item_key
	 *
	 * @return void
	 */
	public function add_editable_group_name_field_in_cart( $cart_item, $cart_item_key ) {

		$editable = true;
		if ( 'yes' === get_option( 'ulgm_hide_group_name_fields_on_cart_page', 'no' ) ) {
			$editable = false;
		}

		if ( WoocommerceModifyGroup::is_modifying_license() ) {
			$editable = false;
		}

		$product_id = $cart_item['product_id'];
		$product    = wc_get_product( $product_id );

		if ( false === self::is_license_or_subscription_license( $product ) ) {
			return;
		}

		$group_name = $cart_item['ulgm_group_name'] ?? '';

		if ( is_user_logged_in() ) {
			$user_id   = wp_get_current_user()->ID;
			$transient = SharedFunctions::get_transient_cache( '_ulgm_user_' . $user_id . '_order', $user_id );

			if ( ! empty( $transient ) && isset( $transient['group_id'] ) ) {
				$group_name = get_the_title( $transient['group_id'] );
			}
		}

		if ( $editable ) {
			echo '<div class="group-name-field">';
			echo '<label for="group_name_' . esc_attr( $cart_item_key ) . '">' . esc_attr__( 'Group Name:', 'uncanny-learndash-groups' ) . '</label>';
			echo '<input type="text" id="group_name_' . esc_attr( $cart_item_key ) . '" name="cart[' . esc_attr( $cart_item_key ) . '][ulgm_group_name]" value="' . esc_attr( $group_name ) . '"/>';
			echo '</div>';

			return;
		}

		if ( empty( $group_name ) ) {
			return;
		}

		echo '<p>';
		echo esc_attr__( 'Group Name: ', 'uncanny-learndash-groups' );
		echo esc_attr( $group_name );
		echo '</p>';
	}

	/**
	 * @return void
	 */
	public function is_modifying_license() {
	}

	/**
	 * @param $checkout
	 *
	 * @return void
	 */
	public function add_group_name_field_checkout( $checkout ) {

		if ( WoocommerceModifyGroup::is_modifying_license() ) {
			return;
		}

		if ( apply_filters( 'ulgm_woocommerce_hide_group_name_fields', false ) ) {
			return;
		}

		if ( 'yes' === get_option( 'ulgm_hide_edit_group_name_fields', 'no' ) ) {
			return;
		}

		$has_license_product = $this->check_if_license_product_in_cart();
		if ( class_exists( '\uncanny_learndash_groups\WoocommerceLicenseSubscription' ) ) {
			$has_license_subscription = WoocommerceLicenseSubscription::check_if_course_subscription_in_cart();
		} else {
			$has_license_subscription = array( 'status' => false );
		}

		// No license / subscription license found. Bail
		if ( ! isset( $has_license_subscription['status'] ) && ! isset( $has_license_product['status'] ) ) {
			return;
		}

		// No license / subscription license found. Bail
		if ( false === $has_license_product['status'] && false === $has_license_subscription['status'] ) {
			return;
		}

		echo apply_filters( 'ulgm_woocommerce_group_name_heading', '<div id="group_name_checkout_field"><h3>' . __( 'Group Name(s)', 'uncanny-learndash-groups' ) . '</h3>' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$required = apply_filters( 'ulgm_group_name_required', true );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product_id = $cart_item['product_id'];
			$product    = wc_get_product( $product_id );
			if ( false === self::is_license_or_subscription_license( $product ) ) {
				continue;
			}

			$custom_buy = get_post_meta( $product_id, '_uo_custom_buy_product', true );

			if ( 'yes' === $custom_buy ) {
				$classes = array( 'ulgm-woo-group-settings form-row-wide form-uo-hidden' );
			} else {
				$classes = array( 'ulgm-woo-group-settings form-row-wide' );
			}
			// Value can be saved on settings page
			$per_seat_text = get_option( 'ulgm_per_seat_text', 'Seat' );

			woocommerce_form_field(
				'ulgm_group_name_' . $cart_item_key,
				apply_filters(
					'ulgm_group_name_args',
					array(
						'type'        => 'text',
						'class'       => $classes,
						'label'       => apply_filters( 'ulgm_group_name_text', sprintf( '%s: %s x %d %s(s)', __( 'Group Name for', 'uncanny-learndash-groups' ), $cart_item['data']->get_title(), $cart_item['quantity'], $per_seat_text ) ),
						'placeholder' => apply_filters( 'ulgm_group_name_placeholder', __( 'Enter group name', 'uncanny-learndash-groups' ) ),
						'required'    => $required,
						'default'     => $cart_item['ulgm_group_name'] ?? '',
						'description' => false === $required ? __( 'If left blank, the group name will be [First name] [Last name] - [Company name].', 'uncanny-learndash-groups' ) : __( 'Enter your group name.', 'uncanny-learndash-groups' ),
					)
				),
				$checkout->get_value( 'ulgm_group_name_' . $cart_item_key )
			);
		}

		echo '</div>';
	}

	/**
	 * @param $cart_updated
	 *
	 * @return void
	 */
	public function save_custom_cart_item_data( $cart_updated ) {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			if ( isset( $_POST['cart'][ $cart_item_key ]['ulgm_group_name'] ) ) {
				$group_name = sanitize_text_field( $_POST['cart'][ $cart_item_key ]['ulgm_group_name'] );

				WC()->cart->cart_contents[ $cart_item_key ]['ulgm_group_name'] = $group_name;
			}
		}
		WC()->cart->set_session();
	}

	/**
	 * @param $product
	 *
	 * @return bool
	 */
	public static function is_license_or_subscription_license( $product ) {
		if ( ! ( $product instanceof \WC_Product ) ) {
			return false;
		}

		if ( 'license' !== $product->get_type() && empty( get_post_meta( $product->get_id(), SharedFunctions::$license_meta_field, true ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Maybe redirect users from restricted license products to shop page
	 *
	 * @return void
	 */
	public static function maybe_redirect_to_shop() {
		// Bail early if not on a single product page
		if ( ! self::is_single_product_page() ) {
			return;
		}

		global $post;

		// Bail early if the _ulgm_license meta key is not set
		if ( ! self::has_ulgm_license_meta( $post->ID ) ) {
			return;
		}

		// Get the WooCommerce shop page URL
		$shop_page_url = self::get_shop_page_url();

		// Bail early if the shop page URL is not set
		if ( ! $shop_page_url ) {
			return;
		}

		// Redirect to the shop page
		wp_safe_redirect( $shop_page_url );
		exit;
	}

	/**
	 * Check if current page is single product
	 *
	 * @return bool
	 */
	private static function is_single_product_page() {
		return is_singular( 'product' );
	}

	/**
	 * Check if product has ulgm license meta
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private static function has_ulgm_license_meta( $post_id ) {

		$is_custom_license = get_post_meta( $post_id, '_uo_custom_buy_product', true );

		if ( 'yes' === $is_custom_license ) {
			return true;
		}

		return false;
	}

	/**
	 * Get WooCommerce shop page URL
	 *
	 * @return string|false
	 */
	private static function get_shop_page_url() {
		$shop_page_id = wc_get_page_id( 'shop' );

		return $shop_page_id ? get_permalink( $shop_page_id ) : false;
	}
}
