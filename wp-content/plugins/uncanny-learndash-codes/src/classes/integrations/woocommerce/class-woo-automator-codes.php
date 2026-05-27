<?php

namespace uncanny_learndash_codes;

use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WC_Product_Automator_Codes;
use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Class Automator_Codes
 * @package uncanny_learndash_codes
 */
class Automator_Codes extends Config {
	/**
	 * @var string
	 */
	public $product_type = 'automator_codes';

	/**
	 * Sample constructor.
	 */
	public function __construct() {

		if ( SharedFunctionality::is_active( 'woocommerce' ) ) {
			add_filter( 'product_type_selector', array( $this, 'selector_add_codes_type' ), 111 );
			add_filter( 'woocommerce_product_get_stock_quantity', array( $this, 'custom_get_stock_quantity' ), 100, 2 );
			add_filter(
				'woocommerce_quantity_input_max',
				array(
					$this,
					'woocommerce_quantity_input_max_callback',
				),
				10,
				2
			);

			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'wc_qty_add_to_cart_validation' ), 99, 3 );
			add_filter(
				'woocommerce_update_cart_validation',
				array(
					$this,
					'max_num_qty_allowed_in_cart',
				),
				10,
				4
			);
			add_filter( 'woocommerce_email_attachments', array( $this, 'attach_to_wc_emails' ), 10, 3 );
			add_filter( 'product_type_options', array( $this, 'add_virtual_and_downloadable_checks' ) );
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'modify_product_data_tabs' ) );

			add_action( 'init', array( $this, 'load_new_product_type' ), 11 );
			add_action( 'woocommerce_automator_codes_add_to_cart', array( $this, 'custom_product_add_to_cart' ), 11 );
			//add_action( 'woocommerce_single_product_summary', array( $this, 'custom_product_add_to_cart' ), 11 );
			//add_action( 'woocommerce_single_product_summary', array( $this, 'show_out_of_stock' ), 11 );
			add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts_product' ), 9999 );
			add_action( 'woocommerce_product_options_pricing', array( $this, 'add_advanced_pricing' ) );
			add_action(
				'woocommerce_process_product_meta_' . $this->product_type,
				array(
					$this,
					'save_option_field',
				),
				999
			);

			add_action( 'woocommerce_checkout_order_processed', array( $this, 'assign_codes' ), 12, 3 );
			add_action( 'automator_pro_woocommerce_order_created', array( $this, 'automator_order_processed' ), 12, 4 );

			add_action(
				'woocommerce_order_details_after_order_table',
				array(
					get_called_class(),
					'woocommerce_order_details_after_order_table',
				),
				1
			);

			add_action(
				'init',
				function () {
					if ( apply_filters( 'ulc_wc_email_include_codes_table', true ) ) {
						// Check whether we have to add the codes table before or after the order table
						$position = apply_filters( 'ulc_wc_email_codes_table_position_order_table', 'after' );

						$email_codes_table_hook = 'woocommerce_email_after_order_table';
						if ( $position === 'before' ) {
							$email_codes_table_hook = 'woocommerce_email_before_order_table';
						}

						add_action(
							$email_codes_table_hook,
							array( get_called_class(), 'codes_table_after_order_table' ),
							10,
							1
						);
					}
				}
			);

			add_action( 'woocommerce_order_refunded', array( $this, 'disable_codes_on_refund' ), 20, 1 );

			if ( 1 === (int) get_option( Config::$uncanny_codes_settings_autocomplete, 0 ) ) {
				add_filter(
					'woocommerce_payment_complete_order_status',
					array(
						$this,
						'autocomplete_codes_order',
					),
					999,
					2
				);
			}

			// Add Hook into WooCommerce to show Order Codes
			add_action(
				'add_meta_boxes',
				array( $this, 'uncanny_codes_add_order_codes_meta_box' ),
				999,
				2
			);
		}

		add_action( 'init', array( $this, 'generate_order_csv' ) );
	}

	/**
	 *
	 */
	public function load_new_product_type() {
		include_once self::get_include( 'wc_product_automator_codes.php' );
		new WC_Product_Automator_Codes();
	}

	/**
	 * @param $types
	 *
	 * @return mixed
	 */
	public function selector_add_codes_type( $types ) {
		// Key should be exactly the same as in the class product_type parameter.
		$types[ $this->product_type ] = esc_html__( 'Codes for Uncanny Automator', 'uncanny-learndash-codes' );

		return $types;

	}

	/**
	 * @param $max
	 * @param $product
	 *
	 * @return int
	 */
	public function woocommerce_quantity_input_max_callback( $max, $product ) {
		if ( $product->is_type( $this->product_type ) ) {
			$codes_available = SharedFunctionality::get_available_codes_by_group_id( $product->get_id() );
			if ( $codes_available > 0 ) {
				$max = $codes_available;
			}
		}

		return $max;
	}

	/**
	 * @param $passed
	 * @param $product_id
	 * @param $quantity
	 *
	 * @return false|mixed
	 */
	public function wc_qty_add_to_cart_validation( $passed, $product_id, $quantity ) {
		$product = wc_get_product( $product_id );
		if ( false === $product || ! $product->is_type( $this->product_type ) ) {
			return $passed;
		}
		$product_max = SharedFunctionality::get_available_codes_by_group_id( $product_id );

		if ( $quantity > $product_max ) {
			// Set to false.
			$passed = false;
			// Display a message.
			wc_add_notice( sprintf( esc_html__( "You can't have more than %1\$d items in cart of %2\$s", 'uncanny-learndash-codes' ), $product_max, get_the_title( $product_id ) ), 'error' );
		}

		return $passed;
	}

	/**
	 * @param $passed
	 * @param $cart_item_key
	 * @param $values
	 * @param $updated_quantity
	 *
	 * @return false|mixed
	 */
	public function max_num_qty_allowed_in_cart( $passed, $cart_item_key, $values, $updated_quantity ) {

		if ( isset( $values['product_id'] ) ) {
			$product_id = absint( $values['product_id'] );
			if ( ! wc_get_product( $product_id )->is_type( $this->product_type ) ) {
				return $passed;
			}
			$product_max = SharedFunctionality::get_available_codes_by_group_id( $product_id );
			if ( $updated_quantity > $product_max ) {
				$passed = false;
				// Display a message.
				wc_add_notice( sprintf( esc_html__( "You can't have more than %1\$d items in cart of %2\$s", 'uncanny-learndash-codes' ), $product_max, get_the_title( $product_id ) ), 'error' );
			}
		}

		return $passed;
	}

	/**
	 * Fix missing Add to cart.
	 */
	public function custom_product_add_to_cart() {
		global $product;
		if ( empty( $product->get_price() ) ) {
			if ( absint( $product->get_price() ) < 0 ) {
				return;
			}
		}
		// Make sure it's our custom product type.
		if ( $this->product_type === $product->get_type() ) {
			$codes_available = SharedFunctionality::get_available_codes_by_group_id( $product->get_id() );
			if ( $codes_available > 0 ) {
				if ( $product && $product->is_type( $this->product_type ) ) {
					do_action( 'woocommerce_before_add_to_cart_button' );
					$html = '<form action="' . esc_url( $product->add_to_cart_url() ) . '" class="cart" method="post" enctype="multipart/form-data">';
					$html .= woocommerce_quantity_input(
						array(
							'max_value' => $codes_available,
							'min_value' => 1,
						),
						$product,
						false
					);
					$html .= '<button type="submit" class="button alt">' . esc_html( $product->add_to_cart_text() ) . '</button>';
					$html .= '</form>';
				}
				echo apply_filters( 'ulc_modify_add_to_cart', $html );
				do_action( 'woocommerce_after_add_to_cart_button' );

				$display      = __( 'In stock', 'woocommerce' );
				$stock_amount = $codes_available;

				switch ( get_option( 'woocommerce_stock_format' ) ) {
					case 'low_amount':
						if ( $stock_amount <= wc_get_low_stock_amount( $product ) ) {
							/* translators: %s: stock amount */
							$display = sprintf( __( 'Only %s left in stock', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product ) );
						}
						break;
					case '':
						/* translators: %s: stock amount */
						$display = sprintf( __( '%s in stock', 'woocommerce' ), wc_format_stock_quantity_for_display( $stock_amount, $product ) );
						break;
				}

				if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
					$display .= ' ' . __( '(can be backordered)', 'woocommerce' );
				}
				?>
				<p class="stock instock"><?php echo $display; ?></p>
				<?php
			}
		}
	}


	/**
	 * Fix missing Add to cart.
	 */
	public function show_out_of_stock() {
		global $product;

		// Make sure it's our custom product type.
		if ( $this->product_type === $product->get_type() ) {
			if ( SharedFunctionality::get_available_codes_by_group_id( $product->get_id() ) <= 0 ) {
				do_action( 'woocommerce_before_stock_status' );
				?>
				<p class="stock outofstock"><?php esc_html_e( 'Out of stock', 'uncanny-learndash-codes' ); ?></p>

				<?php
				do_action( 'woocommerce_after_stock_status' );
			}
		}
	}

	/**
	 *
	 */
	public function admin_print_scripts_product() {
		global $current_screen;

		// Check if it's creating or editing a WooCommerce product
		if ( 'edit' !== (string) $current_screen->base || 'edit-product' !== (string) $current_screen->id ) {
			return;
		}

		?>

		<script>

			{
				// Iterate products
				document.querySelectorAll('tr.type-product').forEach($product => {
					// Check if it's an Codes for Automator product
					if ($product.querySelector('.product_type').innerText !== '<?php echo $this->product_type; ?>') {
						return;
					}

					// Get the product ID
					const productID = $product.getAttribute('id').replace('post-', '');

					document.body.insertAdjacentHTML('beforeEnd', `
					<style>

					#edit-${productID}.inline-edit-row .manage_stock_field,
					#edit-${productID}.inline-edit-row .stock_fields,
					#edit-${productID}.inline-edit-row .stock_status_field,
					#edit-${productID}.inline-edit-row .backorder_field {
						display: none !important;
					}

					</style>
				`);
				});
			}

		</script>

		<?php
	}

	/**
	 * Add the pricing
	 * @return void
	 */
	public function add_advanced_pricing() {
		?>
		<div class='options_group show_if_<?php echo $this->product_type; ?>'>
			<?php

			global $wpdb;
			$tbl_groups = $wpdb->prefix . Config::$tbl_groups;
			if ( SharedFunctionality::ulc_filter_has_var( 'post' ) && SharedFunctionality::ulc_filter_has_var( 'action' ) && 'edit' === SharedFunctionality::ulc_filter_input( 'action' ) ) {
				if ( true === apply_filters( 'ulc_automator_product_limit_issue_max_count', true, $this ) ) {
					$sql = $wpdb->prepare( "SELECT * FROM $tbl_groups WHERE code_for=%s AND issue_max_count=%d AND (product_id=%d OR product_id=%d) ORDER BY `name` ASC", 'automator', 1, 0, SharedFunctionality::ulc_filter_input( 'post', INPUT_GET, FILTER_SANITIZE_NUMBER_INT ) );
				} else {
					$sql = $wpdb->prepare( "SELECT * FROM $tbl_groups WHERE code_for=%s AND (product_id=%d OR product_id=%d) ORDER BY `name` ASC", 'automator', 0, SharedFunctionality::ulc_filter_input( 'post', INPUT_GET, FILTER_SANITIZE_NUMBER_INT ) );
				}
			} else {
				if ( true === apply_filters( 'ulc_automator_product_limit_issue_max_count', true, $this ) ) {
					$sql = $wpdb->prepare( "SELECT * FROM $tbl_groups WHERE code_for=%s AND issue_max_count=%d AND product_id=%d ORDER BY `name` ASC", 'automator', 1, 0 );
				} else {
					$sql = $wpdb->prepare( "SELECT * FROM $tbl_groups WHERE code_for=%s AND product_id=%d ORDER BY `name` ASC", 'automator', 0 );
				}
			}
			$batches = $wpdb->get_results( $sql );
			$options = array();
			if ( $batches ) {
				foreach ( $batches as $batch ) {
					$options[ (string) $batch->ID ] = ( empty( $batch->name ) ) ? 'ID: ' . $batch->ID : $batch->name;
				}
			}

			woocommerce_wp_select(
				array(
					'label'   => esc_html__( 'Code batch:', 'uncanny-learndash-codes' ),
					'id'      => 'codes_group_name',
					'options' => $options,
				)
			);

			?>
			<p id="codes_group_error" style="text-align: center;border: 1px orange solid;display: none">
				<i></i>
			</p>
			<?php

			woocommerce_wp_text_input(
				array(
					'id'                => 'codes_group_total',
					'label'             => esc_html__( 'Total codes:', 'uncanny-learndash-codes' ),
					'value'             => '',
					'style'             => '',
					'custom_attributes' => array(
						'disabled' => 'disabled',
					),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'                => 'codes_group_available',
					'label'             => esc_html__( 'Available codes:', 'uncanny-learndash-codes' ),
					'value'             => '',
					'style'             => '',
					'custom_attributes' => array(
						'disabled' => 'disabled',
					),
				)
			);

			?>
			<button id="codes_group_edit" type="button" style="margin: 5px 0 20px 10px;"
					data-group="0"
					data-href="<?php echo admin_url( 'admin.php?page=uncanny-learndash-codes-create&edit=true&group_id=' ); ?>"
					class="button button-primary"><?php echo esc_html__( 'Edit Code Batch', 'uncanny-learndash-codes' ); ?>
			</button>

		</div>

		<?php
	}

	/**
	 * Save the custom fields.
	 *
	 * @param $product_id
	 */
	public function save_option_field( $product_id ) {
		if ( did_action( 'woocommerce_process_product_meta_' . $this->product_type ) > 1 || did_action( 'woocommerce_process_product_meta' ) > 1 ) {
			return;
		}
		$product = wc_get_product( $product_id );

		// only update automator_codes product type.
		if ( $this->product_type !== $product->get_type() ) {
			return;
		}

		// only update if codes group value is passed.
		if ( SharedFunctionality::ulc_filter_has_var( 'codes_group_name', INPUT_POST ) && absint( SharedFunctionality::ulc_filter_input( 'codes_group_name', INPUT_POST ) ) ) {

			// Get the current codes group.
			$current_codes_group = absint( SharedFunctionality::get_codes_group_id_by_product( $product_id ) );
			$codes_group_name    = absint( SharedFunctionality::ulc_filter_input( 'codes_group_name', INPUT_POST ) );

			// Update the codes group value.
			update_post_meta( $product_id, 'codes_group_name', SharedFunctionality::ulc_filter_input( 'codes_group_name', INPUT_POST ) );
			global $wpdb;
			$table = $wpdb->prefix . Config::$tbl_groups;
			if ( absint( $current_codes_group ) !== absint( $codes_group_name ) ) {
				$sql = $wpdb->prepare( "UPDATE {$table} SET product_id=0 WHERE ID=%d AND product_id=%d", $current_codes_group, $product_id );
				$wpdb->query( $sql );
			}
			// Get the stock quantity.
			$stock = $product->get_stock_quantity();

			// Update the stock quantity manually if the set value is bull or if the codes group association changed.
			if ( false === is_numeric( $stock ) || $current_codes_group !== absint( SharedFunctionality::ulc_filter_input( 'codes_group_name', INPUT_POST ) ) ) {

				$sql = $wpdb->prepare( "UPDATE {$table} SET product_id=%d WHERE ID=%d", $product_id, $codes_group_name );
				$wpdb->query( $sql );
				$codes_available = SharedFunctionality::get_available_codes_by_group_id( $product_id, $codes_group_name );
				if ( $codes_available > 0 ) {
					// Updating the stock quantity.
					$product->set_stock_quantity( $codes_available );
					$product->set_stock_status();
				} else {
					$product->set_stock_quantity( 0 );
					$product->set_stock_status( 'outofstock' );
				}
				$product->save();
				wc_delete_product_transients( $product_id );
			}
		}
	}

	/**
	 * @param $attachments
	 * @param $email_id
	 * @param $order
	 *
	 * @return mixed
	 */
	public function attach_to_wc_emails( $attachments, $email_id, $order ) {

		// Is valid WC Order?
		if ( ! $order instanceof WC_Order ) {
			return $attachments; //bailout.
		}

		$order_id    = $order->get_id();
		$order_codes = Database::get_codes_usage_by_order_id( $order_id );
		// Make sure to only add attachments if the order has codes
		if ( empty( $order_codes ) ) {
			return $attachments;
		}

		if (
			( 'customer_processing_order' === $email_id || 'customer_completed_order' === $email_id ) &&
			apply_filters( 'ulc_attach_csv_to_wc_emails', true, $email_id, $order )
		) {
			$attachments = $this->generate_csv( $order->get_id() );
		}

		return $attachments;
	}

	/**
	 * @param $order_id
	 *
	 * @return array
	 */
	public function generate_csv( $order_id ) {
		$order          = wc_get_order( $order_id );
		$items          = $order->get_items();
		$file_paths     = array();
		$directory_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads/uncanny-codes';
		if ( ! file_exists( $directory_path ) ) {
			mkdir( $directory_path, 0755, true );
		}
		// create .htaccess to deny everyone to read the directory/file directly.
		if ( apply_filters( 'uncanny_codes_htaccess_folder_protection', true ) && ! file_exists( "{$directory_path}/.htaccess" ) ) {
			$f_open = fopen( "{$directory_path}/.htaccess", 'w' );
			fwrite( $f_open, 'deny from all' );
			fclose( $f_open );
		}

		$file_name = sprintf( esc_html__( 'Codes-for-Order %d', 'uncanny-learndash-codes' ), $order_id );
		$file_name = sanitize_file_name( apply_filters( 'uncanny-codes-filter-csv-filename', $file_name, $order ) );

		// Save file path separately.
		$file_path = $directory_path . DIRECTORY_SEPARATOR . $file_name;
		// check File permission for writing content.
		$file_path = "{$file_path}.csv";
		$this->render_csv( $order_id, $file_path );
		$file_paths[] = $file_path;

		return $file_paths;
	}

	/**
	 * @param        $order_id
	 * @param string $mode
	 * @param string $filename
	 *
	 * @return false|resource
	 */
	public function render_csv( $order_id, $mode = 'download', $filename = '' ) {
		global $wpdb;
		$table  = $wpdb->prefix . Config::$tbl_codes;
		$table2 = $wpdb->prefix . Config::$tbl_groups;
		$table3 = $wpdb->prefix . Config::$tbl_codes_usage;
		$sql    = "SELECT c.code, g.product_id, u.user_id, u.date_redeemed AS used_date
FROM {$table} c
    LEFT JOIN $table2 g
        ON c.code_group = g.ID
    LEFT JOIN $table3 u
        ON c.ID = u.code_id
WHERE c.order_id={$order_id}";

		$codes = $wpdb->get_results( $sql );

		if ( ! empty( $codes ) ) {
			if ( 'download' === (string) $mode ) {
				$mode = 'php://output';
				header( 'Content-Type: application/csv' );
				header( "Content-Disposition: attachment; filename={$filename}.csv" );
				header( 'Pragma: no-cache' );
			}
			// create file.
			$f_open = fopen( $mode, 'w' );
			fputcsv(
				$f_open,
				array(
					esc_html__( 'Codes', 'uncanny-learndash-codes' ),
					esc_html__( 'Product', 'uncanny-learndash-codes' ),
					esc_html__( 'Used Date', 'uncanny-learndash-codes' ),
				)
			);
			foreach ( $codes as $code ) {
				// add row to file.
				$display = '';
				if ( ! empty( $code->used_date ) && ! empty( $code->user_id ) ) {
					$user       = get_user_by( 'ID', $code->user_id );
					$first_name = $user->first_name;
					$last_name  = $user->last_name;
					if ( empty( $first_name ) && empty( $last_name ) ) {
						$display = $user->user_email;
					} else {
						$display = "{$first_name} {$last_name}";
					}
					$display = sprintf( '%s (%s)', $display, date_i18n( 'Y-m-d', strtotime( $code->used_date ) ) );
				}
				fputcsv( $f_open, array( $code->code, get_the_title( $code->product_id ), $display ) );
			}
			fclose( $f_open );
		}
	}


	/**
	 * @param \WC_Order $order
	 */
	public static function codes_table_after_order_table( WC_Order $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$text_align = is_rtl() ? 'right' : 'left';
		$order_id   = $order->get_id();
		if ( 0 === absint( $order_id ) || empty( $order_id ) ) {
			return;
		}

		$order_codes = Database::get_codes_usage_by_order_id( $order_id, false );
		if ( empty( $order_codes ) ) {
			return;
		}
		$statuses = apply_filters(
			'ulc_woocommerce_allowed_order_statuses_for_codes',
			array(
				'processing',
				'completed',
			),
			$order
		);

		if ( ! in_array( $order->get_status(), $statuses, true ) ) {
			return;
		}
		?>
		<div style="margin-bottom: 40px;">
			<table class="td" cellspacing="0" cellpadding="6"
				   style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
				<thead>
				<tr>
					<th class="td" scope="col"
						style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Codes', 'uncanny-learndash-codes' ); ?></th>
					<th class="td" scope="col"
						style="text-align:<?php echo esc_attr( $text_align ); ?>;">
						<?php esc_html_e( 'Product', 'uncanny-learndash-codes' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $order_codes as $code ) :
					$product_name = ( ! empty( $code->product_id ) ) ? get_the_title( $code->product_id ) : '';
					?>
					<tr class="order_item">
						<td class="td"
							style="color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word;">
							<?php echo $code->code; ?>
						</td>
						<td class="td"
							style="color: #636363; border: 1px solid #e5e5e5; padding: 12px; text-align: left; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
							<?php
							echo $product_name;
							?>
						</td>
					</tr>
					<?php
				endforeach;
				?>
				<?php
				global $wpdb;
				$table  = $wpdb->prefix . Config::$tbl_codes;
				$table2 = $wpdb->prefix . Config::$tbl_groups;

				$total = $wpdb->get_var(
					"SELECT COUNT(c.code)
FROM {$table} c
    LEFT JOIN $table2 g
        ON c.code_group = g.ID
WHERE c.order_id={$order_id}"
				);
				$total = $total - 200;
				if ( $total > 0 ) {
					?>
					<tr>
						<td colspan="2">
							.<br/>.<br/>.<br/><?php echo sprintf( '%d %s', $total, esc_html__( 'more codes.', 'uncanny-learndash-codes' ) ); ?>
						</td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * @param $order
	 * @param $user_id
	 * @param $args
	 * @param $_this
	 *
	 * @return void
	 */
	public function automator_order_processed( $order, $user_id, $args, $_this ) {
		$order_id = $order->get_id();
		$this->assign_codes( $order_id, array(), $order );
	}

	/**
	 * @param $order_id
	 * @param $posted_data
	 * @param $order
	 */
	public function assign_codes( $order_id, $posted_data, $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// Get order items.
		$items = $order->get_items();
		/** @var WC_Order_Item_Product $item */
		global $wpdb;
		$tbl_groups = $wpdb->prefix . Config::$tbl_groups;
		$tbl_codes  = $wpdb->prefix . Config::$tbl_codes;
		$tbl_usage  = $wpdb->prefix . Config::$tbl_codes_usage;
		foreach ( $items as $item ) {
			// Get an instance of the WC_Product Object from the WC_Order_Item_Product.
			$product = $item->get_product();

			if ( ! $product->is_type( $this->product_type ) ) {
				continue;
			}
			$qty          = $item->get_quantity();
			$codes_group  = get_post_meta( $product->get_id(), 'codes_group_name', true );
			$product_id   = $product->get_id();
			$product_name = $product->get_title();

			if ( ! is_numeric( $codes_group ) ) {
				$order->add_order_note( sprintf( esc_html__( 'Code group not set for %1$s (#%2$d)', 'uncanny-learndash-codes' ), $product_name, $product_id ) );
				continue;
			}
			$sql   = $wpdb->prepare( "SELECT * FROM {$tbl_groups} WHERE ID =%d", $codes_group );
			$batch = $wpdb->get_row( $sql );
			if ( empty( $batch ) ) {
				$order->add_order_note( sprintf( esc_html__( 'Code group (#%1$d) associated with %2$s (#%3$d) does not exist anymore.', 'uncanny-learndash-codes' ), $codes_group, $product_name, $product_id ) );
				continue;
			}
			//Check if codes already assigned for the order
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(`code`) FROM {$tbl_codes} WHERE order_id=%d AND code_group = %d", $order_id, $codes_group ) );
			if ( absint( $existing ) === absint( $qty ) ) {
				continue;
			}

			// assign a code to an order ID.
			$sql = $wpdb->prepare( "UPDATE {$tbl_codes} SET order_id=%d WHERE code_group=%d AND order_id=%d AND ID NOT IN (SELECT code_id FROM {$tbl_usage}) ORDER BY RAND() LIMIT %d", $order_id, $codes_group, 0, $qty );
			$wpdb->query( $sql );
			$sql   = $wpdb->prepare( "SELECT `code` FROM {$tbl_codes} WHERE order_id=%d LIMIT %d", $order_id, $qty );
			$code  = $wpdb->get_col( $sql );
			$total = count( $code );
			if ( count( $code ) > 100 ) {
				$num  = count( $code );
				$code = array_splice( $code, 100 );
				$code = join( ',', $code ) . sprintf( esc_html__( 'and %d more.', 'uncanny-learndash-codes' ), $num );
			} else {
				$code = join( ',', $code );
			}
			$order->add_order_note( sprintf( esc_html__( '%1$dx%2$s codes added to the order', 'uncanny-learndash-codes' ), $total, $product->get_name() ) );
		}
	}

	/**
	 * @param \WC_Order $order
	 */
	public static function woocommerce_order_details_after_order_table( WC_Order $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		global $wpdb;
		$order_id    = $order->get_id();
		$order_codes = Database::get_codes_usage_by_order_id( $order_id, is_account_page() );
		if ( empty( $order_codes ) ) {
			return;
		}
		$statuses = apply_filters(
			'ulc_woocommerce_allowed_order_statuses_for_codes',
			array(
				'processing',
				'completed',
			),
			$order
		);

		if ( ! in_array( $order->get_status(), $statuses, true ) ) {
			return;
		}
		?>
		<h2 class="woocommerce-order-details__title">
			<?php esc_html_e( 'Purchased Codes', 'uncanny-learndash-codes' ); ?>
			<?php if ( empty( $order->get_refunds() ) ) { ?>
				<div class="uncanny-codes-download-csv">
					<a href="<?php echo sprintf( '%s?ulc-order-csv=true&order_id=%d&nonce=%s', site_url(), $order_id, wp_create_nonce( Config::get_project_name() ) ); ?>"
					   class="btn button btn-primary"><?php echo esc_html__( 'Download CSV', 'uncanny-learndsah-codes' ); ?></a>
				</div>
			<?php } ?>
		</h2>
		<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">

			<thead>
			<tr>
				<th class="woocommerce-table__product-name product-code"><?php esc_html_e( 'Code', 'woocommerce' ); ?></th>
				<th class="woocommerce-table__product-name product-name">
					<?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="woocommerce-table__product-table product-used-date"><?php esc_html_e( 'Used Date', 'woocommerce' ); ?></th>
			</tr>
			</thead>

			<tbody>
			<?php
			foreach ( $order_codes as $code ) {
				$used_date    = ( ! empty( $code->used_date ) ) ? $code->used_date : esc_html__( 'Not Used', 'uncanny-learndash-codes' );
				$product_name = ( ! empty( $code->product_id ) ) ? get_the_title( $code->product_id ) : '';
				?>
				<tr>
					<td<?php echo ! empty( $order->get_refunds() ) ? ' style="text-decoration:line-through;"' : ''; ?>><?php echo $code->code; ?></td>
					<td><?php echo $product_name; ?></td>
					<td>
						<?php
						$user_id = $code->user_id;
						if ( is_numeric( $user_id ) && intval( '-1' ) !== intval( $user_id ) ) {
							$date       = $used_date;
							$user       = get_user_by( 'ID', $user_id );
							$first_name = $user->first_name;
							$last_name  = $user->last_name;
							if ( empty( $first_name ) && empty( $last_name ) ) {
								$display = $user->user_email;
							} else {
								$display = "{$first_name} {$last_name}";
							}
							echo sprintf( '%s (%s)', $display, date_i18n( 'Y-m-d', strtotime( $date ) ) );
						} elseif ( intval( '-1' ) === intval( $user_id ) ) {
							echo esc_html__( 'Order refunded', 'uncanny-learndash-codes' );
						} else {

							echo $used_date;
						}
						?>
					</td>
				</tr>

				<?php
			}
			if ( ! is_account_page() ) {
				$table  = $wpdb->prefix . Config::$tbl_codes;
				$table2 = $wpdb->prefix . Config::$tbl_groups;

				$total = $wpdb->get_var(
					"SELECT COUNT(c.code)
FROM {$table} c
    LEFT JOIN $table2 g
        ON c.code_group = g.ID
WHERE c.order_id={$order_id}"
				);
				$total = $total - 200;
				if ( $total > 0 ) {
					?>
					<tr>
						<td colspan="2">
							.<br/>.<br/>.<br/><?php echo sprintf( '%d %s', $total, esc_html__( 'more codes.', 'uncanny-learndash-codes' ) ); ?>
						</td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param $rows
	 * @param $order
	 *
	 * @return mixed
	 */
	public function get_order_item_totals( $rows, $order ) {

		global $wpdb;

		$order_id = $order->get_id();

		$table = $wpdb->prefix . Config::$tbl_codes;
		$sql   = "SELECT code FROM {$table} WHERE order_id={$order_id}";

		$order_codes = $wpdb->get_col( $sql );
		if ( $order_codes ) {
			foreach ( $order_codes as $code ) {
				$rows[ 'automator_codes_' . $code ] = array(
					'label' => esc_html__( 'Code:', 'uncanny-learndash-codes' ),
					'value' => $code,
				);
			}
		}

		return $rows;
	}

	/**
	 * @param             $value
	 * @param WC_Product $product
	 *
	 * @return mixed|string|null
	 */
	public function custom_get_stock_quantity( $value, WC_Product $product ) {
		if ( $product->is_type( $this->product_type ) ) {
			$product_id          = $product->get_id();
			$current_codes_group = absint( SharedFunctionality::get_codes_group_id_by_product( $product_id ) );
			// Update the stock quantity manually if the set value is bull or if the codes group association changed.
			if ( ! empty( $current_codes_group ) && is_numeric( $current_codes_group ) ) {

				$codes_available = SharedFunctionality::get_available_codes_by_group_id( $product_id, $current_codes_group );
				if ( $codes_available > 0 ) {
					$value = wc_stock_amount( $codes_available );
				} else {
					$value = 0;
				}
			}
		}

		return $value;
	}

	/**
	 * @param $status
	 * @param $order_id
	 *
	 * @return string
	 */
	public function autocomplete_codes_order( $status, $order_id ) {
		if ( null === $order_id ) {
			return $status;
		}

		// Order is not in processing status, bail.
		if ( 'processing' !== (string) $status ) {
			return $status;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return $status;
		}

		$line_items = $order->get_items();
		// No products found, bail.
		if ( ! $line_items ) {
			return $status;
		}
		$uo_code_products = array();
		$other_products   = array();
		/**
		 * @var WC_Order_Item_Product $item
		 */
		foreach ( $line_items as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$product    = $item->get_product();
			// check if the item type is license.
			if ( $product->is_type( $this->product_type ) ) {
				$uo_code_products[ $product_id ] = $product->get_type();
			} else {
				// check if product is virtual or downloadable regardless of the product type.
				if ( ! $product->get_virtual() && ! $product->get_downloadable() ) {
					// The product is not virtual.
					$other_products[ $product_id ] = $product->get_type();
				}
			}
		}

		// No license product found in order, bail.
		if ( empty( $uo_code_products ) ) {
			return $status;
		}

		// Other product type found which is not virtual type in order, bail.
		if ( ! empty( $other_products ) ) {
			return $status;
		}

		// Seems good to mark order complete.
		return 'completed';
	}

	/**
	 *
	 */
	public function generate_order_csv() {
		if ( SharedFunctionality::ulc_filter_has_var( 'ulc-order-csv' ) ) {
			$order_id = absint( SharedFunctionality::ulc_filter_input( 'order_id' ) );
			$nonce    = SharedFunctionality::ulc_filter_input( 'nonce' );
			if ( wp_verify_nonce( $nonce, Config::get_project_name() ) ) {
				$file_name = sprintf( esc_html__( 'Codes for Order %d', 'uncanny-learndash-codes' ), $order_id );
				$file_name = apply_filters( 'uncanny-codes-filter-csv-filename', $file_name, $order_id );
				$this->render_csv( $order_id, 'download', $file_name );
				die();
			}
		}
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function add_virtual_and_downloadable_checks( $options ) {
		if ( isset( $options['virtual'] ) ) {
			$options['virtual']['wrapper_class'] = $options['virtual']['wrapper_class'] . ' show_if_' . $this->product_type;
		}

		if ( isset( $options['downloadable'] ) ) {
			$options['downloadable']['wrapper_class'] = $options['downloadable']['wrapper_class'] . ' show_if_' . $this->product_type;
		}

		return $options;
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function modify_product_data_tabs( $options ) {
		if ( isset( $options['inventory'] ) ) {
			$options['inventory']['class'][] = 'show_if_' . $this->product_type;
		}
		if ( isset( $options['shipping'] ) ) {
			$options['shipping']['class'][] = 'hide_if_' . $this->product_type;
		}
		if ( isset( $options['linked_product'] ) ) {
			$options['linked_product']['class'][] = 'hide_if_' . $this->product_type;
		}
		if ( isset( $options['attribute'] ) ) {
			$options['attribute']['class'][] = 'hide_if_' . $this->product_type;
		}
		if ( isset( $options['variations'] ) ) {
			$options['variations']['class'][] = 'hide_if_' . $this->product_type;
		}
		if ( isset( $options['advanced'] ) ) {
			$options['advanced']['class'][] = 'hide_if_' . $this->product_type;
		}

		return $options;
	}

	/**
	 * If order is refunded, set all other unused codes
	 * to refunded. We are going to set user_id as -1 and
	 * show user details as "Order refunded".
	 *
	 * @param $order_id
	 */
	public function disable_codes_on_refund( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$items = $order->get_items();
		if ( ! $items ) {
			return;
		}
		$order_codes = Database::get_codes_usage_by_order_id( $order_id, true );
		if ( empty( $order_codes ) ) {
			return;
		}

		global $wpdb;
		foreach ( $order_codes as $code ) :
			$user_id = $code->user_id;
			$code_id = $code->ID;
			if ( empty( $user_id ) ) {
				$wpdb->insert(
					$wpdb->prefix . Config::$tbl_codes_usage,
					array(
						'code_id'       => $code_id,
						'user_id'       => - 1,
						'date_redeemed' => current_time( 'mysql' ),
					),
					array(
						'%d',
						'%d',
						'%s',
					)
				);
			}
		endforeach;
	}

	/**
	 * @return void
	 */
	public function uncanny_codes_add_order_codes_meta_box( $wc_screen_id, $wc_order ) {
		if ( class_exists( CustomOrdersTableController::class ) && function_exists( 'wc_get_container' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$screen_id = wc_get_page_screen_id( 'shop-order' );
		} else {
			$screen_id = 'shop_order';
		}

		if ( $wc_screen_id != $screen_id ) {
			return;
		}

		add_meta_box(
			'uncanny_codes_order_meta_box',
			esc_html__( 'Order Codes', 'uncanny-learndash-codes' ),
			array( $this, 'uncanny_codes_order_meta_box_func' ),
			$screen_id,
			'side',
			'default' // Priority
		);
	}

	/**
	 * @param $post
	 *
	 * @return void
	 */
	public function uncanny_codes_order_meta_box_func( $post ) {

		$order_id    = $post->ID;
		$order_codes = Database::get_codes_usage_by_order_id( $order_id );

		if ( ! empty( $order_codes ) ) {
			echo '<ul>';
			foreach ( $order_codes as $code ) {
				echo sprintf( '<li><code>%s</code></li>', $code->code );
			}
			echo '</ul>';
			echo sprintf( 'Total codes: %d', count( $order_codes ) );
		} else {
			echo '<p>No codes found for this order.</p>';
		}
	}
}
