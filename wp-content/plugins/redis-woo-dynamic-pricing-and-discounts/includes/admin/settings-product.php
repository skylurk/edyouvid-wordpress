<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Admin_Settings_Product {
	protected $settings;
	protected $woo_currency_symbol, $woo_countries, $woo_users_role, $woo_order_status;
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), PHP_INT_MAX );
		add_action( 'admin_init', array( $this, 'save_settings' ), 99 );
	}
	public function admin_menu() {
		add_menu_page(
			esc_html__( 'Pricing & Discount', 'redis-woo-dynamic-pricing-and-discounts' ),
			esc_html__( 'Pricing & Discount', 'redis-woo-dynamic-pricing-and-discounts' ),
			'manage_woocommerce',
			'viredis-product_pricing',
			array( $this, 'settings_callback' ),
			'dashicons-money-alt',
			2 );
		add_submenu_page(
			'viredis-product_pricing',
			esc_html__( 'Product Pricing', 'redis-woo-dynamic-pricing-and-discounts' ),
			esc_html__( 'Product Pricing', 'redis-woo-dynamic-pricing-and-discounts' ),
			'manage_woocommerce',
			'viredis-product_pricing',
			array( $this, 'settings_callback' )
		);
	}
	public function save_settings() {
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
		if ( $page !== 'viredis-product_pricing' ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( ! isset( $_POST['_viredis_settings_product'] ) || ! wp_verify_nonce( wc_clean($_POST['_viredis_settings_product']), '_viredis_settings_product_action' ) ) {
			return;
		}
		if ( isset( $_POST['viredis-save'] ) ) {
			global $viredis_settings;
			if ( ! $viredis_settings ) {
				$viredis_settings = get_option( 'viredis_params', array() );
			}
			$arg      = array();
			$map_arg1 = array(
				'pd_enable',
				'pd_price_type',
				'pd_limit_discount',
				'pd_limit_discount_value',
				'pd_limit_discount_type',
				'pd_apply_rule',
				'pd_cart_display_price',
				'pd_display_price',
				'pd_change_price_on_single',
				'pd_change_price_on_list',
				'pd_on_sale_badge',
				'pd_pricing_table',
				'pd_pricing_table_position',
				'pd_pricing_table_discount_value',
				'pd_dynamic_price',
				'pd_dynamic_price_position',
			);
			$map_arg2 = array(
				'pd_pages_change_price_on_list',
				'pd_id',
				'pd_active',
				'pd_apply',
				'pd_day',
				'pd_from',
				'pd_from_time',
				'pd_to',
				'pd_to_time',
				'pd_type',
				'pd_basic_price',
				'pd_basic_type',
				'pd_bulk_qty_base',
				'pd_bulk_qty_range',
				'pd_rule1_type',
				'pd_rule1_is_sale',
				'pd_rule1_pd_price_min',
				'pd_rule1_pd_price_max',
				'pd_rule1_pd_visibility',
				'pd_rule1_pd_include',
				'pd_rule1_pd_exclude',
				'pd_rule1_cats_include',
				'pd_rule1_cats_exclude',
				'pd_cart_rule_type',
				'pd_cart_rule_subtotal_min',
				'pd_cart_rule_subtotal_max',
				'pd_cart_rule_qty_item_min',
				'pd_cart_rule_qty_item_max',
				'pd_cart_rule_item_include',
				'pd_cart_rule_item_exclude',
				'pd_cart_rule_cats_include',
				'pd_cart_rule_cats_exclude',
				'pd_cart_rule_tag_include',
				'pd_cart_rule_tag_exclude',
				'pd_cart_rule_coupon_include',
				'pd_cart_rule_coupon_exclude',
				'pd_cart_rule_billing_country_include',
				'pd_cart_rule_billing_country_exclude',
				'pd_cart_rule_shipping_country_include',
				'pd_cart_rule_shipping_country_exclude',
				'pd_user_rule_type',
				'pd_user_rule_logged',
				'pd_user_rule_user_role_include',
				'pd_user_rule_user_role_exclude',
				'pd_user_rule_user_include',
				'pd_user_rule_user_exclude',
				'pd_user_rule_order_status',
				'pd_user_rule_order_count',
				'pd_user_rule_order_total',
				'pd_user_rule_last_order',
				'pd_user_rule_product_include',
				'pd_user_rule_product_exclude',
				'pd_user_rule_cats_include',
				'pd_user_rule_cats_exclude',
			);
			$map_arg3 = array(
				'pd_name',
			);
			$map_arg4 = array(
				'pd_pricing_table_title',
				'pd_dynamic_price_title',
			);
			foreach ( $map_arg1 as $item ) {
				$arg[ $item ] = isset( $_POST[ $item ] ) ? sanitize_text_field( wp_unslash( $_POST[ $item ] ) ) : '';
			}
			foreach ( $map_arg2 as $item ) {
				$arg[ $item ] = isset( $_POST[ $item ] ) ? villatheme_sanitize_fields( $_POST[ $item ] ) : array();
				if ( in_array( $item, array( 'pd_rule1_type', 'pd_user_rule_type', 'pd_cart_rule_type' ) ) ) {
					$arg[ $item ] = array_map( 'array_unique', $arg[ $item ] );
				}
			}
			foreach ( $map_arg3 as $item ) {
				$arg[ $item ] = isset( $_POST[ $item ] ) ? villatheme_sanitize_kses( $_POST[ $item ] ) : array();
			}
			foreach ( $map_arg4 as $item ) {
				$arg[ $item ] = isset( $_POST[ $item ] ) ? wp_kses_post( wp_unslash( $_POST[ $item ] ) ) : '';
			}
			$arg = wp_parse_args( $arg, $viredis_settings );
			if ( is_plugin_active( 'wp-fastest-cache/wpFastestCache.php' ) ) {
				$cache = new WpFastestCache();
				$cache->deleteCache( true );
			}
			update_option( 'viredis_params', $arg );
			$remove_cache = false;
			foreach ( $map_arg3 as $item ) {
				if ( $arg[ $item ] !== ( $viredis_settings[ $item ] ?? array() ) ) {
					$remove_cache = true;
					break;
				}
			}
			if ( ! $remove_cache ) {
				foreach ( $map_arg2 as $item ) {
					if ( $arg[ $item ] !== ( $viredis_settings[ $item ] ?? array() ) ) {
						$remove_cache = true;
						break;
					}
				}
			}
			if ( $remove_cache ) {
				VIREDIS_Pricing_Table::delete_all();
				VIREDIS_DATA::set_data_prefix();
			}
			$viredis_settings = $arg;
		}
	}
	public function settings_callback() {
        $tabs = [
                'rule' => esc_html__( 'Discount Rules', 'redis-woo-dynamic-pricing-and-discounts' ),
                'settings' => esc_html__( 'Settings', 'redis-woo-dynamic-pricing-and-discounts' ),
        ];
        $tab_active = array_keys($tabs)[0]??'';
		$this->settings = VIREDIS_DATA::get_instance( true );
		?>
        <div class="wrap<?php echo esc_attr( is_rtl() ? ' viredis-rtl' : '' ); ?>">
            <h2><?php esc_html_e( 'Product Pricing Settings', 'redis-woo-dynamic-pricing-and-discounts' ); ?></h2>
            <div class="vi-ui raised">
                <form class="vi-ui form" method="post">
					<?php
					wp_nonce_field( '_viredis_settings_product_action', '_viredis_settings_product' );
					?>
                    <div class="vi-ui top tabular vi-ui-main attached menu">
                        <?php
                        foreach ($tabs as $k => $v){
                            echo wp_kses_post(sprintf('<a class="item%s" data-tab="%s">%s</a>', $k === $tab_active ?' active': '', $k, $v));
                        }
                        ?>
                    </div>
                    <div class="vi-ui bottom attached tab segment viredis-tab-wrap-general" data-tab="settings">
                        <table class="form-table">
							<?php
							$pd_enable               = $this->settings->get_params( 'pd_enable' );
							$pd_price_type           = $this->settings->get_params( 'pd_price_type' );
							$pd_limit_discount       = $this->settings->get_params( 'pd_limit_discount' );
							$pd_limit_discount_value = $this->settings->get_params( 'pd_limit_discount_value' );
							$pd_limit_discount_type  = $this->settings->get_params( 'pd_limit_discount_type' );
							$pd_apply_rule           = $this->settings->get_params( 'pd_apply_rule' );
							?>
                            <tr>
                                <th>
                                    <label for="viredis-pd_enable-checkbox"><?php esc_html_e( 'Enable', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" name="pd_enable" id="viredis-pd_enable" value="<?php echo esc_attr( $pd_enable ); ?>">
                                        <input type="checkbox" id="viredis-pd_enable-checkbox" <?php checked( $pd_enable, 1 ); ?>>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="viredis-pd_price_type">
										<?php esc_html_e( 'Base price to apply discount', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="pd_price_type" id="viredis-pd_price_type"
                                            class="vi-ui fluid dropdown viredis-pd_price_type">
                                        <option value="regular" <?php selected( $pd_price_type, 'regular' ) ?>>
											<?php esc_html_e( 'Regular price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="sale" <?php selected( $pd_price_type, 'sale' ) ?>>
											<?php esc_html_e( 'Sale price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                    </select>
                                    <p class="description">
	                                    <?php esc_html_e( 'The initial price to apply the discount of products. Use regular price if no sale price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="viredis-pd_limit_discount-checkbox">
										<?php esc_html_e( 'Enable limit discount per product', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" name="pd_limit_discount" id="viredis-pd_limit_discount" value="<?php echo esc_attr( $pd_limit_discount ); ?>">
                                        <input type="checkbox" id="viredis-pd_limit_discount-checkbox" class="viredis-pd_limit_discount-checkbox" <?php checked( $pd_limit_discount, 1 ); ?>>
                                    </div>
                                    <p class="description">
	                                    <?php esc_html_e( 'Limit the maximum acceptable discount per product', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr class="viredis-pd_limit_discount-enable <?php echo esc_attr( $pd_limit_discount ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_limit_discount_value">
										<?php esc_html_e( 'Maximum discount', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="field">
                                        <div class="vi-ui right action labeled input">
                                            <input type="number" name="pd_limit_discount_value"
                                                   id="viredis-pd_limit_discount_value" min="0" step="0.01" max="<?php echo esc_attr( $pd_limit_discount_type ? '' : '100' ); ?>"
                                                   data-allow_empty="1"
                                                   placeholder="<?php esc_attr_e( 'Leave blank to not limit this', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                                   value="<?php echo esc_attr( $pd_limit_discount_value ) ?>">
                                            <select name="pd_limit_discount_type" id="viredis-pd_limit_discount_type"
                                                    class="vi-ui dropdown viredis-pd_limit_discount_type">
                                                <option value="1" <?php selected( $pd_limit_discount_type, '1' ) ?>>
													<?php /* translators: %s: currency symbol */
                                                    printf( esc_html__( 'Fixed product price(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html($this->woo_currency_symbol) ); ?>
                                                </option>
                                                <option value="0" <?php selected( $pd_limit_discount_type, '0' ) ?>>
													<?php esc_html_e( 'Discount product price(%)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="viredis-pd_apply_rule">
										<?php esc_html_e( 'If multi pricing rule matched', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="pd_apply_rule" id="viredis-pd_apply_rule" class="vi-ui fluid dropdown viredis-pd_apply_rule">
                                        <option value="0" <?php selected( $pd_apply_rule, 0 ) ?>>
											<?php esc_html_e( 'Apply all rules', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="1" <?php selected( $pd_apply_rule, 1 ) ?>>
	                                        <?php esc_html_e( 'Apply first matched rule', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                    </select>
                                    <p class="description">
	                                    <?php esc_html_e( 'Consider the applicable rule if there are multiple qualifying rules, apply all the rules or apply the first rule found', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </p>
                                </td>
                            </tr>
                            <?php
                            $pd_cart_display_price = $this->settings->get_params( 'pd_cart_display_price' );
                            ?>
                            <table class="form-table">
                                <tr>
                                    <th>
                                        <label for="viredis-pd_cart_display_price">
                                            <?php esc_html_e( 'Display price on cart', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <select name="pd_cart_display_price" id="viredis-pd_cart_display_price" class="vi-ui fluid dropdown viredis-pd_cart_display_price">
                                            <option value="new_price" <?php selected( $pd_cart_display_price, 'new_price' ); ?>>
                                                <?php esc_html_e( 'Only discount price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                            </option>
                                            <option value="regular_price" <?php selected( $pd_cart_display_price, 'regular_price' ); ?>>
                                                <?php esc_html_e( 'Both regular price and discount price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                            </option>
                                            <option value="base_price" <?php selected( $pd_cart_display_price, 'base_price' ); ?>>
                                                <?php esc_html_e( 'Both base price and discount price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </table>
                        <?php
                        $pd_display_price                = $this->settings->get_params( 'pd_display_price' );
                        $pd_change_price_on_single       = $this->settings->get_params( 'pd_change_price_on_single' );
                        $pd_change_price_on_list         = $this->settings->get_params( 'pd_change_price_on_list' );
                        $pd_pages_change_price_on_list   = $this->settings->get_params( 'pd_pages_change_price_on_list' );
                        $pd_on_sale_badge                = $this->settings->get_params( 'pd_on_sale_badge' );
                        $pd_pricing_table                = $this->settings->get_params( 'pd_pricing_table' );
                        $pd_pricing_table_title          = $this->settings->get_params( 'pd_pricing_table_title' );
                        $pd_pricing_table_position       = $this->settings->get_params( 'pd_pricing_table_position' );
                        $pd_pricing_table_discount_value = $this->settings->get_params( 'pd_pricing_table_discount_value' );
                        $pd_dynamic_price                = $this->settings->get_params( 'pd_dynamic_price' );
                        ?>
                        <div class="vi-ui yellow message">
                            <?php esc_html_e( 'These options below allow changing how product price displays after applying the discount on your site. Displaying discount price is relative, may confuse customers as prices change continuously. Besides, applying display price options may affect the site speed.', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                        </div>
                        <table class="form-table">
                            <tr>
                                <th>
                                    <label for="viredis-pd_display_price">
                                        <?php esc_html_e( 'Change display price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="pd_display_price" id="viredis-pd_display_price" class="vi-ui fluid dropdown viredis-pd_display_price">
                                        <option value="0" <?php selected( $pd_display_price, 0 ); ?>>
                                            <?php esc_html_e( 'Not change', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="new_price" <?php selected( $pd_display_price, 'new_price' ); ?>>
                                            <?php esc_html_e( 'Only discount price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="regular_price" <?php selected( $pd_display_price, 'regular_price' ); ?>>
                                            <?php esc_html_e( 'Both regular price and discount price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="base_price" <?php selected( $pd_display_price, 'base_price' ); ?>>
                                            <?php esc_html_e( 'Both base price and discount price', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="viredis-change-display-price-enable <?php echo esc_attr( $pd_display_price ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_change_price_on_single-checkbox">
                                        <?php esc_html_e( 'Change display price on single product page', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" name="pd_change_price_on_single" class="viredis-pd_change_price_on_single" value="<?php echo esc_attr( $pd_change_price_on_single ); ?>">
                                        <input type="checkbox" id="viredis-pd_change_price_on_single-checkbox"
                                               class="viredis-pd_change_price_on_single-checkbox" <?php checked( $pd_change_price_on_single, 1 ); ?>>
                                    </div>
                                </td>
                            </tr>
                            <tr class="viredis-change-display-price-enable viredis-pd_change_price_on_single-enable <?php echo esc_attr( $pd_display_price && $pd_change_price_on_single ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_dynamic_price-checkbox">
                                        <?php esc_html_e( 'Display dynamic price on single product', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" class="viredis-pd_dynamic_price" name="pd_dynamic_price" value="<?php echo esc_attr( $pd_dynamic_price ); ?>">
                                        <input type="checkbox" id="viredis-pd_dynamic_price-checkbox" class="viredis-pd_dynamic_price-checkbox" <?php checked( $pd_dynamic_price, 1 ); ?>>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Display discount price respectively with current quantity on single product page', 'redis-woo-dynamic-pricing-and-discounts' ); ?></p>
                                </td>
                            </tr>
                            <tr class="viredis-change-display-price-enable <?php echo esc_attr( $pd_display_price ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_change_price_on_list-checkbox">
                                        <?php esc_html_e( 'Change display price on product list', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" name="pd_change_price_on_list" class="viredis-pd_change_price_on_list" value="<?php echo esc_attr( $pd_change_price_on_list ); ?>">
                                        <input type="checkbox" id="viredis-pd_change_price_on_list-checkbox" class="viredis-pd_change_price_on_list-checkbox" <?php checked( $pd_change_price_on_list, 1 ); ?>>
                                    </div>
                                </td>
                            </tr>
                            <tr class="viredis-change-display-price-enable viredis-pd_change_price_on_list-enable <?php echo esc_attr( $pd_display_price && $pd_change_price_on_list ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_pages_change_price_on_list">
                                        <?php esc_html_e( 'Assign page to change display price on product list', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="pd_pages_change_price_on_list[]" id="viredis-pd_pages_change_price_on_list"
                                            class="vi-ui dropdown fluid viredis-pd_pages_change_price_on_list" multiple>
                                        <?php
                                        $assign_page = array(
                                                '' => esc_html__('All pages', 'redis-woo-dynamic-pricing-discounts'),
                                                'is_woocommerce' => esc_html__('WooCommerce pages', 'redis-woo-dynamic-pricing-discounts'),
                                                'is_shop' => esc_html__('Shop page', 'redis-woo-dynamic-pricing-discounts'),
                                                'is_product_category' => esc_html__('Product category page', 'redis-woo-dynamic-pricing-discounts'),
                                                'is_product' => esc_html__('Single product page', 'redis-woo-dynamic-pricing-discounts'),
                                        );
                                        foreach ($assign_page as $k => $v){
                                            printf('<option value="%s" %s>%s</option>', esc_attr($k), selected(in_array($k, $pd_pages_change_price_on_list), true), esc_html($v));
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('Allow changing display price on the special page. Leave blank to change display price on all pages.', 'redis-woo-dynamic-pricing-discounts'); ?></p>
                                </td>
                            </tr>
                            <tr class="viredis-change-display-price-enable viredis-pd_on_sale_badge-enable <?php echo esc_attr( $pd_display_price && $pd_display_price !== 'new_price' ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_on_sale_badge-checkbox">
                                        <?php esc_html_e( 'Display sale badge', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" name="pd_on_sale_badge" class="viredis-pd_on_sale_badge" value="<?php echo esc_attr( $pd_on_sale_badge ); ?>">
                                        <input type="checkbox" id="viredis-pd_on_sale_badge-checkbox" class="viredis-pd_on_sale_badge-checkbox" <?php checked( $pd_on_sale_badge, 1 ); ?>>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Display sale badge if the displayed price is changed', 'redis-woo-dynamic-pricing-and-discounts' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="viredis-pd_pricing_table-checkbox">
                                        <?php esc_html_e( 'Display pricing table on the single product page', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox">
                                        <input type="hidden" class="viredis-pd_pricing_table" name="pd_pricing_table" value="<?php echo esc_attr( $pd_pricing_table ); ?>">
                                        <input type="checkbox" id="viredis-pd_pricing_table-checkbox" class="viredis-pd_pricing_table-checkbox" <?php checked( $pd_pricing_table, 1 ); ?>>
                                    </div>
                                    <p class="description">
                                        <?php
                                        esc_html_e( 'Display discount pricing table with respective quantity, matched with quantity range set in the rule', 'redis-woo-dynamic-pricing-and-discounts' );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                            <tr class="viredis-pd_pricing_table-enable <?php echo esc_attr( $pd_pricing_table ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_pricing_table_title">
                                        <?php esc_html_e( 'Pricing table title', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" name="pd_pricing_table_title" class="viredis-pd_pricing_table_title"
                                           id="viredis-pd_pricing_table_title" value="<?php echo esc_attr( $pd_pricing_table_title ); ?>">
                                </td>
                            </tr>
                            <tr class="viredis-pd_pricing_table-enable <?php echo esc_attr( $pd_pricing_table ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_pricing_table_position">
                                        <?php esc_html_e( 'Pricing table position', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="pd_pricing_table_position" id="viredis-pd_pricing_table_position" class="vi-ui fluid dropdown viredis-pd_pricing_table_position">
                                        <option value="before_atc" <?php selected( $pd_pricing_table_position, 'before_atc' ) ?>>
                                            <?php esc_html_e( 'Before add to cart button', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="after_atc" <?php selected( $pd_pricing_table_position, 'after_atc' ) ?>>
                                            <?php esc_html_e( 'After add to cart button', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="before_meta" <?php selected( $pd_pricing_table_position, 'before_meta' ) ?>>
                                            <?php esc_html_e( 'Before product meta', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="after_meta" <?php selected( $pd_pricing_table_position, 'after_meta' ) ?>>
                                            <?php esc_html_e( 'After product meta', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="after_summary" <?php selected( $pd_pricing_table_position, 'after_summary' ) ?>>
                                            <?php esc_html_e( 'After product summary', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="viredis-pd_pricing_table-enable <?php echo esc_attr( $pd_pricing_table ? '' : 'viredis-hidden' ); ?>">
                                <th>
                                    <label for="viredis-pd_pricing_table_discount_value">
                                        <?php esc_html_e( 'Discount value column of Pricing table', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <select name="pd_pricing_table_discount_value" id="viredis-pd_pricing_table_discount_value" class="vi-ui fluid dropdown viredis-pd_pricing_table_discount_value">
                                        <option value="percentage_discount" <?php selected( $pd_pricing_table_discount_value, 'percentage_discount' ) ?>>
                                            <?php esc_html_e( 'Percentage discount price (%)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </option>
                                        <option value="fixed_discount" <?php selected( $pd_pricing_table_discount_value, 'fixed_discount' ) ?>>
                                            <?php /* translators: %s: currency symbol */
                                            printf( esc_html__( 'Fixed discount price (%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html($this->woo_currency_symbol) ); ?>
                                        </option>
                                        <option value="fixed_price" <?php selected( $pd_pricing_table_discount_value, 'fixed_price' ) ?>>
                                            <?php /* translators: %s: currency symbol */
                                            printf( esc_html__( 'Fixed price (%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html($this->woo_currency_symbol) ); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="vi-ui bottom attached tab segment active viredis-tab-wrap-rule" data-tab="rule">
                        <div class="field viredis-pricing-rule-wrap">
							<?php
							$pd_id = $this->settings->get_params( 'pd_id' );
							if ( $pd_id && is_array( $pd_id ) && count( $pd_id ) ) {
								foreach ( $pd_id as $i => $id ) {
									$pd_name   = $this->settings->get_current_setting( 'pd_name', $i );
									$pd_active = $this->settings->get_current_setting( 'pd_active', $i );
									$pd_apply  = $this->settings->get_current_setting( 'pd_apply', $i );
									?>
                                    <div class="vi-ui fluid styled accordion viredis-accordion-wrap" data-rule_id="<?php echo esc_attr( $id ); ?>">
                                        <div class="viredis-accordion-info">
                                            <i class="expand arrows alternate icon viredis-accordion-move"></i>
                                            <div class="vi-ui toggle checkbox checked viredis-pd_active-wrap" data-tooltip="<?php esc_attr_e( 'Active', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                                                <input type="hidden" name="pd_active[]" class="viredis-pd_active"
                                                       value="<?php echo esc_attr( $pd_active ); ?>"/>
                                                <input type="checkbox" class="viredis-pd_active-checkbox" <?php checked( $pd_active, 1 ) ?>>
                                            </div>
                                            <h4><span class="viredis-accordion-name"><?php echo esc_html( $pd_name ); ?></span></h4>
                                            <span class="viredis-accordion-action">
                                                <span class="viredis-accordion-clone" data-tooltip="<?php esc_attr_e( 'Clone', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                                                    <i class="clone icon"></i>
                                                </span>
                                                <span class="viredis-accordion-remove" data-tooltip="<?php esc_attr_e( 'Remove', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                                                    <i class="times icon"></i>
                                                </span>
					                        </span>
                                        </div>
                                        <div class="title <?php echo esc_attr( $pd_active ? 'active' : '' ); ?>">
                                            <i class="dropdown icon"></i>
											<?php esc_html_e( 'General', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content <?php echo esc_attr( $pd_active ? 'active' : '' ); ?>">
                                            <div class="field">
                                                <div class="field">
                                                    <label><?php esc_html_e( 'Name', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <input type="hidden" class="viredis-pd_id viredis-rule-id" name="pd_id[]" value="<?php echo esc_attr( $id ); ?>">
                                                    <input type="text" class="viredis-pd_name" name="pd_name[]" value="<?php echo esc_attr( $pd_name ); ?>">
                                                </div>
                                                <div class="field">
                                                    <label><?php esc_html_e( 'If enable "Apply all rules", select to treat this rule as one of the following', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <select name="pd_apply[]" class="vi-ui fluid dropdown viredis-pd_apply">
                                                        <option value="2" <?php selected( $pd_apply, 2 ) ?>>
	                                                        <?php esc_html_e( 'Combine with all the other rules at the same time', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                        </option>
                                                        <option value="1" <?php selected( $pd_apply, 1 ) ?>>
	                                                        <?php esc_html_e( 'Ignore this rule if another is applying', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                        </option>
                                                        <option value="0" <?php selected( $pd_apply, 0 ) ?>>
															<?php esc_html_e( 'Override all other rules', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                        </option>
                                                    </select>
                                                </div>
	                                            <?php
	                                            $pd_type                     = $this->settings->get_current_setting( 'pd_type', $i );
	                                            $pd_basic_price              = $this->settings->get_current_setting( 'pd_basic_price', $i );
	                                            $pd_basic_type               = $this->settings->get_current_setting( 'pd_basic_type', $i );
	                                            $pd_bulk_qty_base            = $this->settings->get_current_setting( 'pd_bulk_qty_base', $i );
	                                            ?>
                                                <div class="equal width fields">
                                                    <div class="field">
                                                        <label><?php esc_html_e( 'Types of rule', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                        <select name="pd_type[]" class="vi-ui fluid dropdown viredis-pd_type">
                                                            <option value="basic" <?php selected( $pd_type, 'basic' ) ?>>
					                                            <?php esc_html_e( 'Basic - Discount exact amount you enter', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                            </option>
                                                            <option value="bulk_qty" <?php selected( $pd_type, 'bulk_qty' ) ?>>
					                                            <?php esc_html_e( 'Bulk pricing based on product quantity in cart', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="field viredis-pd_type-wrap viredis-pd_type-basic <?php echo esc_attr( $pd_type === 'basic' ? '' : 'viredis-hidden' ) ?>">
                                                        <label><?php esc_html_e( 'Price', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                        <div class="vi-ui right action labeled input">
                                                            <input type="number" min="0" max="<?php echo esc_attr( $pd_basic_type ? '' : '100' ); ?>" step="0.01"
                                                                   name="pd_basic_price[]" class="viredis-pd_basic_price" value="<?php echo esc_attr( $pd_basic_price ); ?>">
                                                            <select name="pd_basic_type[]" class="vi-ui fluid dropdown viredis-product-price-type viredis-pd_basic_type">
                                                                <option value="0" <?php selected( $pd_basic_type, 0 ) ?>>
						                                            <?php esc_html_e( 'Percentage discount price(%)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                                </option>
                                                                <option value="1" <?php selected( $pd_basic_type, 1 ) ?>>
						                                            <?php /* translators: %s: currency symbol */
						                                            printf( esc_html__( 'Fixed discount price(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html( $this->woo_currency_symbol ) ); ?>
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="field viredis-pd_type-wrap viredis-pd_type-bulk_qty <?php echo esc_attr( $pd_type === 'bulk_qty' ? '' : 'viredis-hidden' ) ?>">
                                                        <label><?php esc_html_e( 'Quantities of', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                        <select name="pd_bulk_qty_base[]" class="vi-ui fluid dropdown viredis-pd_bulk_qty_base">
                                                            <option value="all" <?php selected( $pd_bulk_qty_base, 'all' ) ?>>
					                                            <?php esc_html_e( 'All cart items', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                            </option>
                                                            <option value="product_category" <?php selected( $pd_bulk_qty_base, 'product_category' ) ?>>
					                                            <?php esc_html_e( 'All cart items in the same category', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                            </option>
                                                            <option value="product" <?php selected( $pd_bulk_qty_base, 'product' ) ?>>
					                                            <?php esc_html_e( 'Current product', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="field viredis-pd_type-wrap viredis-pd_type-bulk_qty <?php echo esc_attr( $pd_type === 'bulk_qty' ? '' : 'viredis-hidden' ) ?>">
                                                    <div class="field">
                                                        <h5 class="vi-ui header dividing viredis-pd_bulk_qty_range-title">
				                                            <?php esc_html_e( 'Quantity Range', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                        </h5>
                                                        <div class="field viredis-pd_bulk_qty_range-content-wrap">
				                                            <?php
				                                            $pd_bulk_qty_range      = $this->settings->get_current_setting( 'pd_bulk_qty_range', $id, array(
					                                            'from'  => array( 0 ),
					                                            'to'    => array( '' ),
					                                            'type'  => array( '0' ),
					                                            'price' => array( '10' ),
				                                            ) );
				                                            $pd_bulk_qty_range_form = $pd_bulk_qty_range['from'] ?? array();
				                                            if ( $pd_bulk_qty_range_form && is_array( $pd_bulk_qty_range_form ) && count( $pd_bulk_qty_range_form ) ) {
					                                            foreach ( $pd_bulk_qty_range_form as $range_index => $range_form ) {
						                                            $arg = array(
							                                            'from'  => $range_form,
							                                            'to'    => $pd_bulk_qty_range['to'][ $range_index ],
							                                            'type'  => $pd_bulk_qty_range['type'][ $range_index ],
							                                            'price' => $pd_bulk_qty_range['price'][ $range_index ],
						                                            );
						                                            wc_get_template( 'admin-product-price.php',
							                                            array(
								                                            'index'               => $id,
								                                            'woo_currency_symbol' => $this->woo_currency_symbol,
								                                            'params'              => $arg,
								                                            'type'                => 'bulk_qty',
							                                            ),
							                                            '',
							                                            VIREDIS_TEMPLATES );
					                                            }
				                                            }
				                                            ?>
                                                            <span class="vi-ui positive mini button viredis-pd_bulk_qty_range-add-range-btn">
                                                                <?php esc_html_e( 'Add Range', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="title">
                                            <i class="dropdown icon"></i>
											<?php esc_html_e( 'Conditions of Product', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content">
                                            <div class="field">
                                                <div class="field viredis-rule-wrap-wrap viredis-pd_rule-wrap-wrap viredis-pd_rule1-wrap-wrap">
                                                    <div class="field viredis-rule-wrap viredis-pd-rule-wrap viredis-pd_rule1-condition-wrap">
	                                                    <?php
	                                                    $pd_rule1_type  = $this->settings->get_current_setting( 'pd_rule1_type', $id, array() );
	                                                    $pd_rule1_check = $pd_rule1_type && is_array( $pd_rule1_type ) && count( $pd_rule1_type );
	                                                    if ( $pd_rule1_check ) {
		                                                    foreach ( $pd_rule1_type as $item_type ) {
			                                                    $arg = array();
			                                                    if ( $item_type === 'pd_price' ) {
				                                                    $arg['pd_price_min'] = $this->settings->get_current_setting( 'pd_rule1_pd_price_min', $id, 0 );
				                                                    $arg['pd_price_max'] = $this->settings->get_current_setting( 'pd_rule1_pd_price_max', $id, '' );
			                                                    } else {
				                                                    $arg[ $item_type ] = $this->settings->get_current_setting( 'pd_rule1_' . $item_type, $id, '' );
			                                                    }
			                                                    wc_get_template( 'admin-product-rule.php',
				                                                    array(
					                                                    'index'               => $id,
					                                                    'woo_currency_symbol' => $this->woo_currency_symbol,
					                                                    'prefix'              => 'pd_rule1_',
					                                                    'params'              => $arg,
					                                                    'type'                => $item_type,
				                                                    ),
				                                                    '',
				                                                    VIREDIS_TEMPLATES );
		                                                    }
	                                                    }
	                                                    ?>
                                                    </div>
                                                    <span class="vi-ui positive mini button viredis-add-condition-btn viredis-pd_rule1-add-condition"
                                                          data-rule_type="pd" data-rule_prefix="pd_rule1_">
                                                        <?php esc_html_e( 'Add Conditions(AND)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="title">
                                            <i class="dropdown icon"></i>
											<?php esc_html_e( 'Conditions of Cart', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content">
                                            <div class="field viredis-rule-wrap-wrap viredis-pd_cart_rule-wrap-wrap">
												<?php
												$pd_cart_rule_type = $this->settings->get_current_setting( 'pd_cart_rule_type', $id, array() );
												?>
                                                <div class="field viredis-rule-wrap  viredis-cart-rule-wrap viredis-pd_cart_rule-wrap">
													<?php
													if ( $pd_cart_rule_type && is_array( $pd_cart_rule_type ) && count( $pd_cart_rule_type ) ) {
														foreach ( $pd_cart_rule_type as $item_type ) {
															$arg                     = array();
															$pd_cart_rule_loop_check = false;
															switch ( $item_type ) {
																case 'cart_subtotal':
																	$arg['subtotal_min'] = $this->settings->get_current_setting( 'pd_cart_rule_subtotal_min', $id, 0 );
																	$arg['subtotal_max'] = $this->settings->get_current_setting( 'pd_cart_rule_subtotal_max', $id, '' );
																	break;
																case 'qty_item':
																	$arg['qty_item_min'] = $this->settings->get_current_setting( 'pd_cart_rule_qty_item_min', $id, 0 );
																	$arg['qty_item_max'] = $this->settings->get_current_setting( 'pd_cart_rule_qty_item_max', $id, '' );
																	break;
																default:
																	$arg[ $item_type ] = $this->settings->get_current_setting( 'pd_cart_rule_' . $item_type, $id, array() );
															}
															if ( ! $pd_cart_rule_loop_check ) {
																wc_get_template( 'admin-cart-rule.php',
																	array(
																		'index'               => $id,
																		'woo_currency_symbol' => $this->woo_currency_symbol,
																		'woo_countries'       => $this->woo_countries,
																		'prefix'              => 'pd_cart_rule_',
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
                                                <span class="vi-ui positive mini button viredis-add-condition-btn viredis-pd_cart_rule-add-condition"
                                                      data-rule_type="cart" data-rule_prefix="pd_cart_rule_">
                                                        <?php esc_html_e( 'Add Conditions(AND)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="title">
                                            <i class="dropdown icon"></i>
											<?php esc_html_e( 'Conditions of Customer', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content">
                                            <div class="field viredis-rule-wrap-wrap viredis-pd_user_rule-wrap-wrap">
                                                <div class="field viredis-rule-wrap viredis-user-rule-wrap viredis-pd_user_rule-wrap">
													<?php
													$pd_user_rule_type = $this->settings->get_current_setting( 'pd_user_rule_type', $id, array() );
													if ( $pd_user_rule_type && is_array( $pd_user_rule_type ) && count( $pd_user_rule_type ) ) {
														$condition_prefix = 'pd_user_rule_';
														foreach ( $pd_user_rule_type as $item_type ) {
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
                                                <span class="vi-ui positive mini button viredis-add-condition-btn viredis-pd_user_rule-add-condition"
                                                      data-rule_type="user" data-rule_prefix="pd_user_rule_">
                                                        <?php esc_html_e( 'Add Conditions(AND)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="title">
                                            <i class="dropdown icon"></i>
		                                    <?php esc_html_e( 'Conditions of Date & Time', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                        </div>
                                        <div class="content">
		                                    <?php
		                                    $pd_from      = $this->settings->get_current_setting( 'pd_from', $i );
		                                    $pd_from_time = $this->settings->get_current_setting( 'pd_from_time', $i );
		                                    $pd_to        = $this->settings->get_current_setting( 'pd_to', $i );
		                                    $pd_to_time   = $this->settings->get_current_setting( 'pd_to_time', $i );
		                                    $pd_day       = $this->settings->get_current_setting( 'pd_day', $id, array() );
		                                    ?>
                                            <div class="field">
                                                <label><?php esc_html_e( 'Days', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                <select name="pd_day[<?php echo esc_attr( $id ); ?>][]"
                                                        data-redis_name_default="pd_day[{index_default}][]"
                                                        class="vi-ui fluid dropdown viredis-pd_day" multiple>
                                                    <option value=""><?php esc_html_e( 'Every day of the week', 'redis-woo-dynamic-pricing-and-discounts' ); ?></option>
                                                    <option value="0" <?php selected( in_array( '0', $pd_day ), true ) ?>>
					                                    <?php esc_html_e( 'Sunday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="1" <?php selected( in_array( '1', $pd_day ), true ) ?>>
					                                    <?php esc_html_e( 'Monday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="2" <?php selected( in_array( '2', $pd_day ), true ) ?>>
					                                    <?php esc_html_e( 'Tuesday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="3" <?php selected( in_array( '3', $pd_day ), true ) ?>>
					                                    <?php esc_html_e( 'Wednesday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="4" <?php selected( in_array( '4', $pd_day ), true ) ?>>
					                                    <?php esc_html_e( 'Thursday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="5" <?php selected( in_array( '5', $pd_day ), true ) ?>>
					                                    <?php esc_html_e( 'Friday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                    <option value="6" <?php selected( in_array( '6', $pd_day ), true ) ?>>
					                                    <?php esc_html_e( 'Saturday', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="equal width fields">
                                                <div class="field">
                                                    <label><?php esc_html_e( 'From', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <div class="equal width fields">
                                                        <div class="field">
                                                            <input type="date" name="pd_from[]" class="viredis-pd_from viredis-condition-date-from" value="<?php echo esc_attr( $pd_from ) ?>">
                                                        </div>
                                                        <div class="field">
                                                            <input type="time" name="pd_from_time[]" class="viredis-pd_from_time" value="<?php echo esc_attr( $pd_from_time ) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="field">
                                                    <label><?php esc_html_e( 'To', 'redis-woo-dynamic-pricing-and-discounts' ); ?></label>
                                                    <div class="equal width fields">
                                                        <div class="field">
                                                            <input type="date" name="pd_to[]" class="viredis-pd_to viredis-condition-date-to" value="<?php echo esc_attr( $pd_to ) ?>">
                                                        </div>
                                                        <div class="field">
                                                            <input type="time" name="pd_to_time[]" class="viredis-pd_to_time" value="<?php echo esc_attr( $pd_to_time ) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
									<?php
								}
							}
							?>
                        </div>
                        <div class="field viredis-rule-new-wrap viredis-pricing-rule-new-wrap viredis-hidden">
                            <div class="viredis-pd-condition-new-wrap">
								<?php
								wc_get_template( 'admin-product-rule.php',
									array(
										'woo_currency_symbol' => $this->woo_currency_symbol,
									),
									'',
									VIREDIS_TEMPLATES );
								?>
                            </div>
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
				<?php
				do_action( 'villatheme_support_redis-woo-dynamic-pricing-and-discounts' );
				?>
            </div>
        </div>
		<?php
	}
	public function admin_enqueue_scripts() {
		if ( isset( $_REQUEST['_viredis_settings_product'] ) && ! wp_verify_nonce( wc_clean( $_REQUEST['_viredis_settings_product'] ), '_viredis_settings_product_action' ) ) {
			return;
		}
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
		if ( $page !== 'viredis-product_pricing' ) {
			return;
		}
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
			array( 'select2', 'viredis-admin-rule', 'viredis-product_pricing' ),
			array( 'select2.min.js', 'admin-rule.js', 'admin-pd-pricing.js' ),
			array( array( 'jquery' ), array( 'jquery', 'jquery-ui-sortable' ), array( 'jquery', 'jquery-ui-sortable' ) )
		);
		$this->woo_currency_symbol = get_woocommerce_currency_symbol();
		$woo_countries             = new WC_Countries();
		$this->woo_countries       = $woo_countries->__get( 'countries' );
		$this->woo_users_role      = wp_roles()->roles;
		$this->woo_order_status    = wc_get_order_statuses();
	}
}