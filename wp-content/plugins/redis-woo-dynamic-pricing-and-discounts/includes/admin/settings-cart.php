<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Admin_Settings_Cart {
	protected $settings;
	protected $woo_currency_symbol, $woo_countries, $woo_users_role, $woo_order_status;
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), PHP_INT_MAX );
		add_action( 'admin_init', array( $this, 'save_settings' ), 99 );
	}
	public function admin_menu() {
		add_submenu_page(
			'viredis-product_pricing',
			esc_html__( 'Cart Discount', 'redis-woo-dynamic-pricing-and-discounts' ),
			esc_html__( 'Cart Discount', 'redis-woo-dynamic-pricing-and-discounts' ),
			'manage_woocommerce',
			'viredis-cart_discount',
			array( $this, 'settings_callback' )
		);
	}
	public function save_settings() {
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
		if ( $page !== 'viredis-cart_discount' ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! isset( $_POST['_viredis_settings_cart'] ) || ! wp_verify_nonce( wc_clean($_POST['_viredis_settings_cart']), '_viredis_settings_cart_action' ) ) {
			return;
		}
		if ( isset( $_POST['viredis-save'] ) ) {
			global $viredis_settings;
			if ( ! $viredis_settings ) {
				$viredis_settings = get_option( 'viredis_params', array() );
			}
			$arg      = array();
			$map_arg1 = array(
				'cart_enable',
				'cart_price_type',
				'cart_limit_discount',
				'cart_limit_discount_value',
				'cart_limit_discount_type',
				'cart_apply_rule',
				'cart_combine_all_discount',
				'cart_combine_all_discount_title',
			);
			$map_arg2 = array(
				'cart_id',
				'cart_active',
				'cart_apply',
				'cart_discount_value',
				'cart_discount_type',
				'cart_day',
				'cart_from',
				'cart_from_time',
				'cart_to',
				'cart_to_time',
				'cart_cart_rule_type',
				'cart_cart_rule_subtotal_min',
				'cart_cart_rule_subtotal_max',
				'cart_cart_rule_qty_item_min',
				'cart_cart_rule_qty_item_max',
				'cart_cart_rule_item_include',
				'cart_cart_rule_item_exclude',
				'cart_cart_rule_cats_include',
				'cart_cart_rule_cats_exclude',
				'cart_cart_rule_tag_include',
				'cart_cart_rule_tag_exclude',
				'cart_cart_rule_coupon_include',
				'cart_cart_rule_coupon_exclude',
				'cart_cart_rule_billing_country_include',
				'cart_cart_rule_billing_country_exclude',
				'cart_cart_rule_shipping_country_include',
				'cart_cart_rule_shipping_country_exclude',
				'cart_user_rule_type',
				'cart_user_rule_logged',
				'cart_user_rule_user_role_include',
				'cart_user_rule_user_role_exclude',
				'cart_user_rule_user_include',
				'cart_user_rule_user_exclude',
				'cart_user_rule_order_status',
				'cart_user_rule_order_count',
				'cart_user_rule_order_total',
				'cart_user_rule_last_order',
				'cart_user_rule_product_include',
				'cart_user_rule_product_exclude',
				'cart_user_rule_cats_include',
				'cart_user_rule_cats_exclude',
			);
			$map_arg3 = array(
				'cart_name',
				'cart_discount_title',
			);
			foreach ( $map_arg1 as $item ) {
				$arg[ $item ] = isset( $_POST[ $item ] ) ? sanitize_text_field( wp_unslash( $_POST[ $item ] ) ) : '';
			}
			foreach ( $map_arg2 as $item ) {
				$arg[ $item ] = isset( $_POST[ $item ] ) ? villatheme_sanitize_fields( $_POST[ $item ] ) : array();
				if ( in_array( $item, array( 'cart_user_rule_type', 'cart_cart_rule_type' ) ) ) {
					$arg[ $item ] = array_map( 'array_unique', $arg[ $item ] );
				}
			}
			foreach ( $map_arg3 as $item ) {
				$arg[ $item ] = isset( $_POST[ $item ] ) ? villatheme_sanitize_kses( $_POST[ $item ] ) : array();
			}
			$arg = wp_parse_args( $arg, $viredis_settings );
			if ( is_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ) ) {
				$cache = new WpFastestCache();
				$cache->deleteCache( true );
			}
			update_option( 'viredis_params', $arg );
			$remove_cache = false;
			foreach ( $map_arg2 as $item ) {
				if ( $arg[ $item ] !== ( $viredis_settings[ $item ] ?? array() ) ) {
					$remove_cache = true;
					break;
				}
			}
			if ( $remove_cache ) {
				VIREDIS_DATA::set_data_prefix('cart');
			}
			$viredis_settings = $arg;
		}
	}
	public function settings_callback() {
		$this->settings = VIREDIS_DATA::get_instance( true );
		?>
        <div class="wrap<?php echo esc_attr( is_rtl() ? ' viredis-rtl' : '' ); ?>">
            <h2><?php esc_html_e( 'Settings Cart Discount', 'redis-woo-dynamic-pricing-and-discounts' ); ?></h2>
            <div class="vi-ui raised">
                <form class="vi-ui form" method="post">
					<?php
					wp_nonce_field( '_viredis_settings_cart_action', '_viredis_settings_cart' );
					?>
                    <div class="vi-ui top tabular vi-ui-main attached menu">
                        <a class="item active" data-tab="general"><?php esc_html_e( 'General', 'redis-woo-dynamic-pricing-and-discounts' ); ?></a>
                        <a class="item" data-tab="rule"><?php esc_html_e( 'Rules', 'redis-woo-dynamic-pricing-and-discounts' ); ?></a>
                    </div>
                    <div class="vi-ui bottom attached tab segment active viredis-tab-wrap-general" data-tab="general">
                        <table class="form-table">
                            <tbody>
							<?php
							$cart_enable                     = $this->settings->get_params( 'cart_enable' );
							$cart_apply_rule                 = $this->settings->get_params( 'cart_apply_rule' );
							$cart_limit_discount             = $this->settings->get_params( 'cart_limit_discount' );
							$cart_limit_discount_value       = $this->settings->get_params( 'cart_limit_discount_value' );
							$cart_limit_discount_type        = $this->settings->get_params( 'cart_limit_discount_type' );
							$cart_combine_all_discount       = $this->settings->get_params( 'cart_combine_all_discount' );
							$cart_combine_all_discount_title = $this->settings->get_params( 'cart_combine_all_discount_title' );
							?>
                            <tr>
                                <th>
                                    <label for="viredis-cart_enable-checkbox"><?php esc_html_e( 'Enable', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" name="cart_enable" id="viredis-cart_enable" value="<?php echo esc_attr( $cart_enable ); ?>">
                                        <input type="checkbox" id="viredis-cart_enable-checkbox" <?php checked( $cart_enable, 1 ); ?>>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="viredis-cart_limit_discount-checkbox">
										<?php esc_html_e( 'Enable limit discount', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" name="cart_limit_discount" id="viredis-cart_limit_discount" value="<?php echo esc_attr( $cart_limit_discount ); ?>">
                                        <input type="checkbox" id="viredis-cart_limit_discount-checkbox" class="viredis-cart_limit_discount-checkbox" <?php checked( $cart_limit_discount, 1 ); ?>>
                                    </div>
                                    <p class="description">
	                                    <?php esc_html_e( 'Limit the maximum discount of an order', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr class="viredis-cart_limit_discount-enable <?php echo esc_attr( $cart_limit_discount ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-cart_limit_discount_value">
										<?php esc_html_e( 'Maximum discount', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="field">
                                        <div class="vi-ui right action labeled input">
                                            <input type="number" name="cart_limit_discount_value" data-allow_empty="1"
                                                   id="viredis-cart_limit_discount_value" min="0" step="0.01" max="<?php echo esc_attr( ! $cart_limit_discount_type ? '100' : '' ); ?>"
                                                   value="<?php echo esc_attr( $cart_limit_discount_value ) ?>">
                                            <select name="cart_limit_discount_type" id="viredis-cart_limit_discount_type"
                                                    class="vi-ui dropdown viredis-cart_limit_discount_type">
                                                <option value="1" <?php selected( $cart_limit_discount_type, '1' ) ?>>
													<?php /* translators: %s: currency symbol */
                                                    printf( esc_html__( 'Fixed cart discount(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_attr( $this->woo_currency_symbol ) ); ?>
                                                </option>
                                                <option value="0" <?php selected( $cart_limit_discount_type, '0' ) ?>>
													<?php esc_html_e( 'Percentage cart discount(%)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="viredis-cart_apply_rule">
										<?php esc_html_e( 'If multi rules matched', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="cart_apply_rule" id="viredis-cart_apply_rule" class="vi-ui fluid dropdown viredis-cart_apply_rule">
                                        <option value="0" <?php selected( $cart_apply_rule, 0 ) ?>>
											<?php esc_html_e( 'Apply all rules', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="1" <?php selected( $cart_apply_rule, 1 ) ?>>
	                                        <?php esc_html_e( 'Apply first matched rule', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                    </select>
                                    <p class="description">
	                                    <?php esc_html_e( 'If many rules qualify, apply all rules or first matched rule', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr class="viredis-cart_combine_all_discount-wrap <?php echo esc_attr( $cart_apply_rule ? 'viredis-hidden' : '' ); ?>">
                                <th>
                                    <label for="viredis-cart_combine_all_discount-checkbox">
										<?php esc_html_e( 'Combine all discounts', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" id="viredis-cart_combine_all_discount" name="cart_combine_all_discount" value="<?php echo esc_attr( $cart_combine_all_discount ); ?>">
                                        <input type="checkbox" id="viredis-cart_combine_all_discount-checkbox" class="viredis-cart_combine_all_discount-checkbox"
											<?php checked( $cart_combine_all_discount, 1 ); ?>>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Display the total of all applicable discounts instead of showing them one at a time', 'redis-woo-dynamic-pricing-and-discounts' ); ?></p>
                                </td>
                            </tr>
                            <tr class="viredis-cart_combine_all_discount-wrap viredis-cart_combine_all_discount-wrap-enable <?php echo esc_attr( $cart_apply_rule || ! $cart_combine_all_discount ? 'viredis-hidden' : '' ); ?>">
                                <th>
                                    <label for="viredis-cart_combine_all_discount_label">
										<?php esc_html_e( 'Combine all discounts title', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" id="viredis-cart_combine_all_discount_tilte" class="viredis-cart_combine_all_discount_title"
                                           name="cart_combine_all_discount_title" value="<?php echo esc_attr( $cart_combine_all_discount_title ); ?>">
                                    <p class="description"><?php esc_html_e( 'The title of the total discount', 'redis-woo-dynamic-pricing-and-discounts' ); ?></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="vi-ui bottom attached tab segment viredis-tab-wrap-rule" data-tab="rule">
                        <div class="field viredis-cart-discount-rule-wrap">
	                        <?php
	                        $cart_ids = $this->settings->get_params( 'cart_id' );
	                        if ( $cart_ids && is_array( $cart_ids ) && count( $cart_ids ) ) {
		                        foreach ( $cart_ids as $i => $id ) {
			                        $cart_name           = $this->settings->get_current_setting( 'cart_name', $i );
			                        $cart_active         = $this->settings->get_current_setting( 'cart_active', $i );
			                        $cart_apply          = $this->settings->get_current_setting( 'cart_apply', $i, 0 );
			                        $cart_discount_value = $this->settings->get_current_setting( 'cart_discount_value', $i, 10 );
			                        $cart_discount_type  = $this->settings->get_current_setting( 'cart_discount_type', $i, 0 );
			                        $cart_discount_title = $this->settings->get_current_setting( 'cart_discount_title', $i, '' );
			                        ?>
                                    <div class="vi-ui fluid styled accordion viredis-accordion-wrap" data-rule_id="<?php echo esc_attr( $id ); ?>">
                                        <div class="viredis-accordion-info">
                                            <i class="expand arrows alternate icon viredis-accordion-move"></i>
                                            <div class="vi-ui toggle checkbox checked viredis-cart_active-wrap" data-tooltip="<?php esc_attr_e( 'Active', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                                                <input type="hidden" name="cart_active[]" class="viredis-cart_active"
                                                       value="<?php echo esc_attr( $cart_active ); ?>"/>
                                                <input type="checkbox" class="viredis-cart_active-checkbox" <?php checked( $cart_active, 1 ) ?>>
                                            </div>
                                            <h4><span class="viredis-accordion-name"><?php echo esc_html( $cart_name ); ?></span></h4>
                                            <span class="viredis-accordion-action">
                                                <span class="viredis-accordion-clone" data-tooltip="<?php esc_attr_e( 'Clone', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                                                    <i class="clone icon"></i>
                                                </span>
                                                <span class="viredis-accordion-remove" data-tooltip="<?php esc_attr_e( 'Remove', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                                                    <i class="times icon"></i>
                                                </span>
					                        </span>
                                        </div>
                                        <div class="title <?php echo esc_attr( $cart_active ? 'active' : '' ); ?>">
                                            <i class="dropdown icon"></i>
	                                        <?php esc_html_e( 'General', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content <?php echo esc_attr( $cart_active ? 'active' : '' ); ?>">
                                            <div class="field">
                                                <div class="field">
                                                    <label><?php esc_html_e( 'Name', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <input type="hidden" class="viredis-cart_id viredis-rule-id" name="cart_id[]" value="<?php echo esc_attr( $id ); ?>">
                                                    <input type="text" class="viredis-cart_name" name="cart_name[]" value="<?php echo esc_attr( $cart_name ); ?>">
                                                </div>
                                                <div class="field">
                                                    <label><?php esc_html_e( 'Discount title', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <input type="text" class="viredis-cart_discount_title" name="cart_discount_title[]" value="<?php echo esc_attr( $cart_discount_title ); ?>">
                                                    <p class="description">
	                                                    <?php esc_html_e( 'The title is on your website', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </p>
                                                </div>
                                                <div class="field">
                                                    <label><?php esc_html_e( 'Discount value', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <div class="vi-ui right action labeled input">
                                                        <input type="number" name="cart_discount_value[]"
                                                               class="viredis-cart_discount_value" min="0" step="0.01" max="<?php echo esc_attr( ! $cart_discount_type ? '100' : '' ); ?>"
                                                               value="<?php echo esc_attr( $cart_discount_value ) ?>">
                                                        <select name="cart_discount_type[]" id="viredis-cart_discount_type"
                                                                class="vi-ui dropdown viredis-cart_discount_type">
                                                            <option value="0" <?php selected( $cart_discount_type, 0 ) ?>>
	                                                            <?php esc_html_e( 'Percentage discount(%)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                            </option>
                                                            <option value="1" <?php selected( $cart_discount_type, 1 ) ?>>
	                                                            <?php /* translators: %s: currency symbol */
                                                                printf( esc_html__( 'Fixed cart discount(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html( $this->woo_currency_symbol ) ); ?>
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="field">
                                                    <label><?php esc_html_e( 'If enable "Apply all rules", select to treat this rule as one of the following', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <select name="cart_apply[]" class="vi-ui fluid dropdown viredis-cart_apply">
                                                        <option value="2" <?php selected( $cart_apply, 2 ) ?>>
	                                                        <?php esc_html_e( 'Combine with all the other rules at the same time', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                        </option>
                                                        <option value="1" <?php selected( $cart_apply, 1 ) ?>>
	                                                        <?php esc_html_e( 'Ignore this rule if another is applying', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                        </option>
                                                        <option value="0" <?php selected( $cart_apply, 0 ) ?>>
	                                                        <?php esc_html_e( 'Override all other rules', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="title">
                                            <i class="dropdown icon"></i>
	                                        <?php esc_html_e( 'Date & Time', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content">
	                                        <?php
	                                        $cart_from      = $this->settings->get_current_setting( 'cart_from', $i );
	                                        $cart_from_time = $this->settings->get_current_setting( 'cart_from_time', $i );
	                                        $cart_to        = $this->settings->get_current_setting( 'cart_to', $i );
	                                        $cart_to_time   = $this->settings->get_current_setting( 'cart_to_time', $i );
	                                        $cart_day       = $this->settings->get_current_setting( 'cart_day', $id, array() );
	                                        ?>
                                            <div class="field">
                                                <label><?php esc_html_e( 'Days', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                <select name="cart_day[<?php echo esc_attr( $id ); ?>][]"
                                                        data-redis_name_default="cart_day[{index_default}][]"
                                                        class="vi-ui fluid dropdown viredis-cart_day" multiple>
                                                    <option value=""><?php esc_html_e( 'Every day of the week', 'redis-woo-dynamic-pricing-and-discounts' ); ?></option>
                                                    <option value="0" <?php selected( in_array( '0', $cart_day ), true ) ?>>
	                                                    <?php esc_html_e( 'Sunday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="1" <?php selected( in_array( '1', $cart_day ), true ) ?>>
	                                                    <?php esc_html_e( 'Monday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="2" <?php selected( in_array( '2', $cart_day ), true ) ?>>
	                                                    <?php esc_html_e( 'Tuesday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="3" <?php selected( in_array( '3', $cart_day ), true ) ?>>
	                                                    <?php esc_html_e( 'Wednesday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="4" <?php selected( in_array( '4', $cart_day ), true ) ?>>
	                                                    <?php esc_html_e( 'Thursday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="5" <?php selected( in_array( '5', $cart_day ), true ) ?>>
	                                                    <?php esc_html_e( 'Friday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="6" <?php selected( in_array( '6', $cart_day ), true ) ?>>
	                                                    <?php esc_html_e( 'Saturday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="equal width fields">
                                                <div class="field">
                                                    <label><?php esc_html_e( 'From', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <div class="equal width fields">
                                                        <div class="field">
                                                            <input type="date" name="cart_from[]" class="viredis-cart_from viredis-condition-date-from" value="<?php echo esc_attr( $cart_from ) ?>">
                                                        </div>
                                                        <div class="field">
                                                            <input type="time" name="cart_from_time[]" class="viredis-cart_from_time" value="<?php echo esc_attr( $cart_from_time ) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="field">
                                                    <label><?php esc_html_e( 'To', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <div class="equal width fields">
                                                        <div class="field">
                                                            <input type="date" name="cart_to[]" class="viredis-cart_to viredis-condition-date-to" value="<?php echo esc_attr( $cart_to ) ?>">
                                                        </div>
                                                        <div class="field">
                                                            <input type="time" name="cart_to_time[]" class="viredis-cart_to_time" value="<?php echo esc_attr( $cart_to_time ) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="title">
                                            <i class="dropdown icon"></i>
	                                        <?php esc_html_e( 'Conditions of Cart', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content">
                                            <div class="field viredis-rule-wrap-wrap viredis-cart_cart_rule-wrap-wrap">
                                                <div class="field viredis-rule-wrap  viredis-cart-rule-wrap viredis-cart_cart_rule-wrap">
	                                                <?php
	                                                $cart_cart_rule_type = $this->settings->get_current_setting( 'cart_cart_rule_type', $id, array() );
	                                                if ( $cart_cart_rule_type && is_array( $cart_cart_rule_type ) && count( $cart_cart_rule_type ) ) {
		                                                foreach ( $cart_cart_rule_type as $item_type ) {
			                                                $arg                       = array();
			                                                $cart_cart_rule_loop_check = false;
			                                                switch ( $item_type ) {
				                                                case 'cart_subtotal':
					                                                $arg['subtotal_min'] = $this->settings->get_current_setting( 'cart_cart_rule_subtotal_min', $id, 0 );
					                                                $arg['subtotal_max'] = $this->settings->get_current_setting( 'cart_cart_rule_subtotal_max', $id, '' );
					                                                break;
				                                                case 'qty_item':
					                                                $arg['qty_item_min'] = $this->settings->get_current_setting( 'cart_cart_rule_qty_item_min', $id, 0 );
					                                                $arg['qty_item_max'] = $this->settings->get_current_setting( 'cart_cart_rule_qty_item_max', $id, '' );
					                                                break;
				                                                default:
					                                                $arg[ $item_type ] = $this->settings->get_current_setting( 'cart_cart_rule_' . $item_type, $id, array() );
			                                                }
			                                                if ( ! $cart_cart_rule_loop_check ) {
				                                                wc_get_template( 'admin-cart-rule.php',
					                                                array(
						                                                'index'               => $id,
						                                                'woo_currency_symbol' => $this->woo_currency_symbol,
						                                                'woo_countries'       => $this->woo_countries,
						                                                'prefix'              => 'cart_cart_rule_',
						                                                'params'              => $arg,
						                                                'type'                => $item_type,
					                                                ),
					                                                '',
					                                                VIREDIS_TEMPLATES );
			                                                }
		                                                }
	                                                }
	                                                ?>
                                                </div>
                                                <span class="vi-ui positive mini button viredis-add-condition-btn viredis-cart_cart_rule-add-condition"
                                                      data-rule_type="cart" data-rule_prefix="cart_cart_rule_">
                                                        <?php esc_html_e( 'Add Conditions(AND)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="title">
                                            <i class="dropdown icon"></i>
	                                        <?php esc_html_e( 'Conditions of Customer', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content">
                                            <div class="field viredis-rule-wrap-wrap viredis-cart_user_rule-wrap-wrap">
                                                <div class="field viredis-rule-wrap viredis-user-rule-wrap viredis-cart_user_rule-wrap">
	                                                <?php
	                                                $cart_user_rule_type = $this->settings->get_current_setting( 'cart_user_rule_type', $id, array() );
	                                                if ( $cart_user_rule_type && is_array( $cart_user_rule_type ) && count( $cart_user_rule_type ) ) {
		                                                $condition_prefix = 'cart_user_rule_';
		                                                foreach ( $cart_user_rule_type as $item_type ) {
			                                                $arg = array();
			                                                if ( in_array( $item_type, array( 'order_count', 'order_total' ) ) ) {
				                                                $item_type_params      = $this->settings->get_current_setting( $condition_prefix . $item_type, $id, array() );
				                                                $item_type_params_from = $item_type_params['from'] ?? array();
				                                                if ( $item_type_params_from && is_array( $item_type_params_from ) && count( $item_type_params_from ) ) {
					                                                foreach ( $item_type_params_from as $qty_item_k => $qty_item_v ) {
						                                                $arg[ $item_type . '_from' ] = $qty_item_v;
						                                                $arg[ $item_type . '_to' ]   = $item_type_params['to'][ $qty_item_k ] ?? '';
						                                                $arg[ $item_type . '_min' ]  = $item_type_params['min'][ $qty_item_k ] ?? 0;
						                                                $arg[ $item_type . '_max' ]  = $item_type_params['max'][ $qty_item_k ] ?? '';
						                                                wc_get_template( 'admin-user-rule.php',
							                                                array(
								                                                'index'               => $id,
								                                                'woo_currency_symbol' => $this->woo_currency_symbol,
								                                                'woo_users_role'      => $this->woo_users_role,
								                                                'woo_order_status'    => $this->woo_order_status,
								                                                'prefix'              => $condition_prefix,
								                                                'params'              => $arg,
								                                                'type'                => $item_type,
							                                                ),
							                                                '',
							                                                VIREDIS_TEMPLATES );
					                                                }
				                                                }
			                                                } elseif ( $item_type === 'last_order' ) {
				                                                $item_type_params            = $this->settings->get_current_setting( $condition_prefix . $item_type, $id, array() );
				                                                $arg[ $item_type . '_type' ] = $item_type_params['type'] ?? '';
				                                                $arg[ $item_type . '_date' ] = $item_type_params['date'] ?? '';
				                                                wc_get_template( 'admin-user-rule.php',
					                                                array(
						                                                'index'               => $id,
						                                                'woo_currency_symbol' => $this->woo_currency_symbol,
						                                                'woo_users_role'      => $this->woo_users_role,
						                                                'woo_order_status'    => $this->woo_order_status,
						                                                'prefix'              => $condition_prefix,
						                                                'params'              => $arg,
						                                                'type'                => $item_type,
					                                                ),
					                                                '',
					                                                VIREDIS_TEMPLATES );
			                                                } else {
				                                                $arg[ $item_type ] = $this->settings->get_current_setting( $condition_prefix . $item_type, $id, '' );
				                                                wc_get_template( 'admin-user-rule.php',
					                                                array(
						                                                'index'               => $id,
						                                                'woo_currency_symbol' => $this->woo_currency_symbol,
						                                                'woo_users_role'      => $this->woo_users_role,
						                                                'woo_order_status'    => $this->woo_order_status,
						                                                'prefix'              => $condition_prefix,
						                                                'params'              => $arg,
						                                                'type'                => $item_type,
					                                                ),
					                                                '',
					                                                VIREDIS_TEMPLATES );
			                                                }
		                                                }
	                                                }
	                                                ?>
                                                </div>
                                                <span class="vi-ui positive mini button viredis-add-condition-btn viredis-cart_user_rule-add-condition"
                                                      data-rule_type="user" data-rule_prefix="cart_user_rule_">
                                                        <?php esc_html_e( 'Add Conditions(AND)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
			                        <?php
		                        }
	                        }
	                        ?>
                        </div>
                        <div class="field viredis-rule-new-wrap viredis-cart-rule-new-wrap viredis-hidden">
                            <div class="viredis-cart-condition-new-wrap">
	                            <?php
	                            wc_get_template( 'admin-cart-rule.php',
		                            array(
			                            'woo_currency_symbol' => $this->woo_currency_symbol,
			                            'woo_countries'       => $this->woo_countries,
		                            ),
		                            '',
		                            VIREDIS_TEMPLATES );
	                            ?>
                            </div>
                            <div class="viredis-user-condition-new-wrap">
	                            <?php
	                            wc_get_template( 'admin-user-rule.php',
		                            array(
			                            'woo_currency_symbol' => $this->woo_currency_symbol,
			                            'woo_users_role'      => $this->woo_users_role,
			                            'woo_order_status'    => $this->woo_order_status,
		                            ),
		                            '',
		                            VIREDIS_TEMPLATES );
	                            ?>
                            </div>
                        </div>
                    </div>
                    <p class="viredis-save-wrap">
                        <button type="button" class="viredis-save vi-ui primary button" name="viredis-save">
							<?php esc_html_e( 'Save', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
		<?php
	}
	public function admin_enqueue_scripts() {
		if ( isset( $_REQUEST['_viredis_settings_cart'] ) && ! wp_verify_nonce( wc_clean( $_REQUEST['_viredis_settings_cart'] ), '_viredis_settings_cart_action' ) ) {
			return;
		}
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( $page === 'viredis-cart_discount' ) {
			VIREDIS_Admin_Settings::remove_other_script();
			VIREDIS_Admin_Settings::enqueue_style(
				array( 'semantic-ui-accordion', 'semantic-ui-button', 'semantic-ui-checkbox', 'semantic-ui-dropdown', 'semantic-ui-form', 'semantic-ui-header', 'semantic-ui-icon' ),
				array( 'accordion.min.css', 'button.min.css', 'checkbox.min.css', 'dropdown.min.css', 'form.min.css', 'segment.min.css', 'icon.min.css' )
			);
			VIREDIS_Admin_Settings::enqueue_style(
				array( 'semantic-ui-input', 'semantic-ui-label', 'semantic-ui-menu', 'semantic-ui-message', 'semantic-ui-popup', 'semantic-ui-segment', 'semantic-ui-tab' ),
				array( 'input.min.css', 'label.min.css', 'menu.min.css', 'message.min.css', 'popup.min.css', 'header.min.css', 'tab.min.css' )
			);
			VIREDIS_Admin_Settings::enqueue_style(
				array( 'transition', 'select2', 'viredis-admin-rule' ),
				array( 'transition.min.css', 'select2.min.css', WP_DEBUG ? 'admin-rule.css' : 'admin-rule.min.css' )
			);
			VIREDIS_Admin_Settings::enqueue_script(
				array( 'semantic-ui-accordion', 'semantic-ui-address', 'semantic-ui-checkbox', 'semantic-ui-dropdown', 'semantic-ui-form', 'semantic-ui-tab', 'transition' ),
				array( 'accordion.min.js', 'address.min.js', 'checkbox.min.js', 'dropdown.min.js', 'form.min.js', 'tab.js', 'transition.min.js' ),
				array( array( 'jquery' ), array( 'jquery' ), array( 'jquery' ), array( 'jquery' ), array( 'jquery' ), array( 'jquery' ), array( 'jquery' ) )
			);
			VIREDIS_Admin_Settings::enqueue_script(
				array( 'select2', 'viredis-admin-rule', 'viredis-cart_discount' ),
				array( 'select2.min.js', 'admin-rule.js', 'admin-cart-discount.js' ),
				array( array( 'jquery' ), array( 'jquery', 'jquery-ui-sortable' ), array( 'jquery', 'jquery-ui-sortable' ) )
			);
			$this->woo_currency_symbol = get_woocommerce_currency_symbol();
			$woo_countries             = new WC_Countries();
			$this->woo_countries       = $woo_countries->__get( 'countries' );
			$this->woo_users_role      = wp_roles()->roles;
			$this->woo_order_status    = wc_get_order_statuses();
		}
	}
}