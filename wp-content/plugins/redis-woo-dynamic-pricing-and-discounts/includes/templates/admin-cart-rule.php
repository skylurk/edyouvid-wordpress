<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$item_index          = $item_index ?? '';
$item_index          = $item_index ?: '{item_index}';
$index               = $index ?? '';
$index               = $index ?: '{index}';
$prefix              = $prefix ?? '';
$prefix              = $prefix ?: '{prefix}';
$params              = isset($params) && is_array($params) ? $params : array();
$type                = $type ?? 'cart_subtotal';
$woo_currency_symbol = $woo_currency_symbol ?? get_woocommerce_currency_symbol();
if ( empty( $woo_countries ) ) {
	$woo_countries = new WC_Countries();
	$woo_countries = $woo_countries->__get( 'countries' );
}
$conditions                    = array(
	'Cart Total'       => array(
		'cart_subtotal' => esc_html__( 'Cart Subtotal', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
	'Cart Items'       => array(
		'qty_item'     => esc_html__( 'Total Quantity of Cart Items', 'redis-woo-dynamic-pricing-and-discounts' ),
		'item_include' => esc_html__( 'Include Cart Items', 'redis-woo-dynamic-pricing-and-discounts' ),
		'item_exclude' => esc_html__( 'Exclude Cart Items', 'redis-woo-dynamic-pricing-and-discounts' ),
		'cats_include' => esc_html__( 'Include Cart Items by Categories', 'redis-woo-dynamic-pricing-and-discounts' ),
		'cats_exclude' => esc_html__( 'Exclude Cart Items by Categories', 'redis-woo-dynamic-pricing-and-discounts' ),
		'tag_include'  => esc_html__( 'Include Cart Items by Tags', 'redis-woo-dynamic-pricing-and-discounts' ),
		'tag_exclude'  => esc_html__( 'Exclude Cart Items by Tags', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
	'Applied Coupon'   => array(
		'coupon_include' => esc_html__( 'Include Coupon', 'redis-woo-dynamic-pricing-and-discounts' ),
		'coupon_exclude' => esc_html__( 'Exclude Coupon', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
	'Billing Address'  => array(
		'billing_country_include' => esc_html__( 'Include Billing Countries', 'redis-woo-dynamic-pricing-and-discounts' ),
		'billing_country_exclude' => esc_html__( 'Exclude Billing Countries', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
	'Shipping Address' => array(
		'shipping_country_include' => esc_html__( 'Include Shipping Countries', 'redis-woo-dynamic-pricing-and-discounts' ),
		'shipping_country_exclude' => esc_html__( 'Exclude Shipping Countries', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
);
$subtotal_min                  = $params['subtotal_min'] ?? 0;
$subtotal_min                  = $subtotal_min ?: 0;
$subtotal_max                  = $params['subtotal_max'] ?? '';
$count_item_min                = $params['count_item_min'] ?? 0;
$count_item_min                = $count_item_min ?: 0;
$count_item_max                = $params['count_item_max'] ?? '';
$qty_item_min                  = $params['qty_item_min'] ?? 0;
$qty_item_min                  = $qty_item_min ?: 0;
$qty_item_max                  = $params['qty_item_max'] ?? '';
$item_include                  = $params['item_include'] ?? array();
$item_exclude                  = $params['item_exclude'] ?? array();
$cats_include                  = $params['cats_include'] ?? array();
$cats_exclude                  = $params['cats_exclude'] ?? array();
$tag_include                   = $params['tag_include'] ?? array();
$tag_exclude                   = $params['tag_exclude'] ?? array();
$coupon_include                = $params['coupon_include'] ?? array();
$coupon_exclude                = $params['coupon_exclude'] ?? array();
$billing_country_include       = isset($params['billing_country_include']) && is_array($params['billing_country_include']) ? $params['billing_country_include'] : array();
$billing_country_exclude       = isset($params['billing_country_exclude']) && is_array($params['billing_country_exclude']) ? $params['billing_country_exclude'] : array();
$shipping_country_include      = isset($params['shipping_country_include']) && is_array($params['shipping_country_include']) ?$params['shipping_country_include'] : array();
$shipping_country_exclude      = isset($params['shipping_country_exclude']) && is_array($params['shipping_country_exclude']) ?$params['shipping_country_exclude'] : array();
$name_condition_type           = $prefix . 'type[' . $index . '][]';
$name_subtotal_min             = $prefix . 'subtotal_min[' . $index . ']';
$name_subtotal_max             = $prefix . 'subtotal_max[' . $index . ']';
$name_count_item_min           = $prefix . 'count_item_min[' . $index . ']';
$name_count_item_max           = $prefix . 'count_item_max[' . $index . ']';
$name_qty_item_min             = $prefix . 'qty_item_min[' . $index . ']';
$name_qty_item_max             = $prefix . 'qty_item_max[' . $index . ']';
$name_item_include             = $prefix . 'item_include[' . $index . '][]';
$name_item_exclude             = $prefix . 'item_exclude[' . $index . '][]';
$name_cats_include             = $prefix . 'cats_include[' . $index . '][]';
$name_cats_exclude             = $prefix . 'cats_exclude[' . $index . '][]';
$name_attr_include             = $prefix . 'attr_include[' . $index . '][]';
$name_attr_exclude             = $prefix . 'attr_exclude[' . $index . '][]';
$name_tag_include              = $prefix . 'tag_include[' . $index . '][]';
$name_tag_exclude              = $prefix . 'tag_exclude[' . $index . '][]';
$name_coupon_include           = $prefix . 'coupon_include[' . $index . '][]';
$name_coupon_exclude           = $prefix . 'coupon_exclude[' . $index . '][]';
$name_billing_country_include  = $prefix . 'billing_country_include[' . $index . '][]';
$name_billing_country_exclude  = $prefix . 'billing_country_exclude[' . $index . '][]';
$name_shipping_country_include = $prefix . 'shipping_country_include[' . $index . '][]';
$name_shipping_country_exclude = $prefix . 'shipping_country_exclude[' . $index . '][]';
?>
<div class="vi-ui placeholder segment viredis-condition-wrap-wrap viredis-cart-condition-wrap-wrap">
    <div class="fields">
        <div class="field viredis-condition-move">
            <i class="expand arrows alternate icon"></i>
        </div>
        <div class="four wide field">
            <select class="vi-ui fluid dropdown viredis-condition-type viredis-cart-condition-type"
                    data-redis_name="<?php echo esc_attr( $name_condition_type ) ?>"
                    data-redis_name_default="{prefix_default}type[{index_default}][]"
                    data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                    name="<?php echo esc_attr( $name_condition_type ) ?>">
				<?php
				foreach ( $conditions as $condition_group => $condition_arg ) {
					?>
                    <optgroup label="<?php esc_attr_e( $condition_group );// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>">
						<?php
						foreach ( $condition_arg as $condition_k => $condition_v ) {
							printf( '<option value="%s" %s >%s</option>', esc_attr( $condition_k ), selected( $type, $condition_k ), esc_html( $condition_v ) );
						}
						?>
                    </optgroup>
					<?php
				}
				?>
            </select>
        </div>
        <div class="thirteen wide field viredis-condition-value-wrap-wrap">
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-cart_subtotal-wrap <?php echo esc_attr( $type === 'cart_subtotal' ? '' : 'viredis-hidden' ); ?>">
                <div class="equal width fields">
                    <div class="field">
                        <div class="vi-ui  left action labeled input viredis-input-range-wrap">
                            <div class="vi-ui label viredis-basic-label">
	                            <?php /* translators: %s: currency symbol */
                                printf( esc_html__( 'Min(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html( $woo_currency_symbol ) ) ?>
                            </div>
                            <input type="number" min="0" step="0.01"
                                   name="<?php echo esc_attr( $type === 'cart_subtotal' ? $name_subtotal_min : '' ); ?>"
                                   data-redis_name="<?php echo esc_attr( $name_subtotal_min ) ?>"
                                   data-redis_name_default="{prefix_default}subtotal_min[{index_default}]"
                                   data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                   class="viredis-cart-condition-subtotal_min viredis-condition-value" value="<?php echo esc_attr( $subtotal_min ) ?>">
                            <div class="vi-ui label viredis-basic-label">
	                            <?php /* translators: %s: currency symbol */
                                printf( esc_html__( 'Max(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html( $woo_currency_symbol ) ) ?>
                            </div>
                            <input type="number" min="0" step="0.01"
                                   name="<?php echo esc_attr( $type === 'cart_subtotal' ? $name_subtotal_max : '' ); ?>"
                                   data-redis_name="<?php echo esc_attr( $name_subtotal_max ) ?>"
                                   data-redis_name_default="{prefix_default}subtotal_max[{index_default}]"
                                   data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                   data-allow_empty="1"
                                   placeholder="<?php esc_attr_e( 'Leave blank to not limit this.', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                   class="viredis-cart-condition-subtotal_max viredis-condition-value" value="<?php echo esc_attr( $subtotal_max ) ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-count_item-wrap <?php echo esc_attr( $type === 'count_item' ? '' : 'viredis-hidden' ); ?>">
                <div class="field">
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui left action labeled input viredis-input-range-wrap">
                                <div class="vi-ui label viredis-basic-label">
									<?php esc_html_e( 'Min', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                </div>
                                <input type="number" min="0" step="1" class="viredis-cart-condition-count_item_min viredis-condition-value"
                                       name="<?php echo esc_attr( $type === 'count_item' ? $name_count_item_min : '' ); ?>"
                                       data-redis_name_default="{prefix_default}count_item_min[{index_default}]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_count_item_min ); ?>" value="<?php echo esc_attr( $count_item_min ); ?>">
                                <div class="vi-ui label viredis-basic-label">
									<?php esc_html_e( 'Max', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                </div>
                                <input type="number" min="1" step="1" class="viredis-cart-condition-count_item_max viredis-condition-value"
                                       name="<?php echo esc_attr( $type === 'count_item' ? $name_count_item_max : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_count_item_max ); ?>"
                                       data-redis_name_default="{prefix_default}count_item_max[{index_default}]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       data-allow_empty="1"
                                       placeholder="<?php esc_attr_e( 'Leave blank to not limit this.', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                       value="<?php echo esc_attr( $count_item_max ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-qty_item-wrap <?php echo esc_attr( $type === 'qty_item' ? '' : 'viredis-hidden' ); ?>">
                <div class="field">
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui left action labeled input viredis-input-range-wrap">
                                <div class="vi-ui label viredis-basic-label">
									<?php esc_html_e( 'Min', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                </div>
                                <input type="number" min="0" step="1" class="viredis-cart-condition-qty_item_min viredis-condition-value"
                                       name="<?php echo esc_attr( $type === 'qty_item' ? $name_qty_item_min : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_qty_item_min ); ?>"
                                       data-redis_name_default="{prefix_default}qty_item_min[{index_default}]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       value="<?php echo esc_attr( $qty_item_min ); ?>">
                                <div class="vi-ui label viredis-basic-label">
									<?php esc_html_e( 'Max', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                </div>
                                <input type="number" min="1" step="1" class="viredis-cart-condition-qty_item_max viredis-condition-value"
                                       name="<?php echo esc_attr( $type === 'qty_item' ? $name_qty_item_max : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_qty_item_max ); ?>"
                                       data-redis_name_default="{prefix_default}qty_item_max[{index_default}]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       data-allow_empty="1"
                                       placeholder="<?php esc_attr_e( 'Leave blank to not limit this.', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                       value="<?php echo esc_attr( $qty_item_max ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-item_include-wrap <?php echo esc_attr( $type === 'item_include' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-product viredis-cart-condition-item_include viredis-condition-value"
                        data-type_select2="product"
                        name="<?php echo esc_attr( $type === 'item_include' ? $name_item_include : '' ); ?>"
                        data-redis_name_default="{prefix_default}item_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_item_include ) ?>" multiple>
					<?php
					if ( $item_include && is_array( $item_include ) && count( $item_include ) ) {
						foreach ( $item_include as $pd_id ) {
							$product = wc_get_product( $pd_id );
							if ( $product ) {
								$product_title = $product->get_formatted_name();
								if ( strpos( $product_title, '(#' . $pd_id . ')' ) === false ) {
									$product_title .= '(#' . $pd_id . ')';
								}
								printf( '<option value="%s" selected>%s</option>', esc_attr( $pd_id ), esc_attr( $product_title ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-item_exclude-wrap <?php echo esc_attr( $type === 'item_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'item_exclude' ? $name_item_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_item_exclude ) ?>"
                        data-redis_name_default="{prefix_default}item_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="product"
                        class="viredis-search-select2 viredis-search-product viredis-cart-condition-item_exclude viredis-condition-value" multiple>
					<?php
					if ( $item_exclude && is_array( $item_exclude ) && count( $item_exclude ) ) {
						foreach ( $item_exclude as $pd_id ) {
							$product = wc_get_product( $pd_id );
							if ( $product ) {
								$product_title = $product->get_formatted_name();
								if ( strpos( $product_title, '(#' . $pd_id . ')' ) === false ) {
									$product_title .= '(#' . $pd_id . ')';
								}
								printf( '<option value="%s" selected>%s</option>', esc_attr( $pd_id ), esc_attr( $product_title ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-cats_include-wrap <?php echo esc_attr( $type === 'cats_include' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'cats_include' ? $name_cats_include : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_cats_include ) ?>"
                        data-redis_name_default="{prefix_default}cats_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="category"
                        class="viredis-search-select2 viredis-search-category viredis-cart-condition-cats_include viredis-condition-value" multiple>
					<?php
					if ( $cats_include && is_array( $cats_include ) && count( $cats_include ) ) {
						foreach ( $cats_include as $cart_id ) {
							$term = get_term( $cart_id );
							if ( $term ) {
								printf( '<option value="%s" selected>%s</option>', esc_attr( $cart_id ), esc_attr( $term->name ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-cats_exclude-wrap <?php echo esc_attr( $type === 'cats_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'cats_exclude' ? $name_cats_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_cats_exclude ) ?>"
                        data-redis_name_default="{prefix_default}cats_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="category"
                        class="viredis-search-select2 viredis-search-category viredis-cart-condition-cats_exclude viredis-condition-value" multiple>
					<?php
					if ( $cats_exclude && is_array( $cats_exclude ) && count( $cats_exclude ) ) {
						foreach ( $cats_exclude as $cart_id ) {
							$term = get_term( $cart_id );
							if ( $term ) {
								printf( '<option value="%s" selected>%s</option>', esc_attr( $cart_id ), esc_attr( $term->name ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-tag_include-wrap <?php echo esc_attr( $type === 'tag_include' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'tag_include' ? $name_tag_include : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_tag_include ) ?>"
                        data-redis_name_default="{prefix_default}tag_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="tag"
                        class="viredis-search-select2 viredis-search-tag viredis-cart-condition-tag_include viredis-condition-value" multiple>
					<?php
					if ( $tag_include && is_array( $tag_include ) && count( $tag_include ) ) {
						foreach ( $tag_include as $tag_id ) {
							$term = get_term( $tag_id );
							if ( $term ) {
								printf( '<option value="%s" selected>%s</option>', esc_attr( $tag_id ), esc_attr( $term->name ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-tag_exclude-wrap <?php echo esc_attr( $type === 'tag_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'tag_exclude' ? $name_tag_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_tag_exclude ) ?>"
                        data-redis_name_default="{prefix_default}tag_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="tag"
                        class="viredis-search-select2 viredis-search-tag viredis-cart-condition-tag_exclude viredis-condition-value" multiple>
					<?php
					if ( $tag_exclude && is_array( $tag_exclude ) && count( $tag_exclude ) ) {
						foreach ( $tag_exclude as $tag_id ) {
							$term = get_term( $tag_id );
							if ( $term ) {
								printf( '<option value="%s" selected>%s</option>', esc_attr( $tag_id ), esc_attr( $term->name ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-coupon_include-wrap <?php echo esc_attr( $type === 'coupon_include' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'coupon_include' ? $name_coupon_include : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_coupon_include ) ?>"
                        data-redis_name_default="{prefix_default}coupon_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="coupon"
                        class="viredis-search-select2 viredis-search-coupon viredis-cart-condition-coupon_include viredis-condition-value" multiple>
					<?php
					if ( $coupon_include && is_array( $coupon_include ) && count( $coupon_include ) ) {
						foreach ( $coupon_include as $coupon_code ) {
							printf( '<option value="%s" selected>%s</option>', esc_attr( $coupon_code ), esc_html( strtoupper( $coupon_code ) ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-coupon_exclude-wrap <?php echo esc_attr( $type === 'coupon_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'coupon_exclude' ? $name_coupon_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_coupon_exclude ) ?>"
                        data-redis_name_default="{prefix_default}coupon_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="coupon"
                        class="viredis-search-select2 viredis-search-coupon viredis-cart-condition-coupon_exclude viredis-condition-value" multiple>
					<?php
					if ( $coupon_exclude && is_array( $coupon_exclude ) && count( $coupon_exclude ) ) {
						foreach ( $coupon_exclude as $coupon_code ) {
							printf( '<option value="%s" selected>%s</option>', esc_attr( $coupon_code ), esc_html( strtoupper( $coupon_code ) ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-billing_country_include-wrap <?php echo esc_attr( $type === 'billing_country_include' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'billing_country_include' ? $name_billing_country_include : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_billing_country_include ) ?>"
                        data-redis_name_default="{prefix_default}billing_country_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="country"
                        class="viredis-search-select2 viredis-search-country viredis-cart-condition-billing_country_include viredis-condition-value" multiple>
					<?php
					if ( $woo_countries && is_array( $woo_countries ) && count( $woo_countries ) ) {
						foreach ( $woo_countries as $country_id => $country_name ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $country_id ), selected( in_array( $country_id, $billing_country_include ), true ), esc_html( $country_name ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-billing_country_exclude-wrap <?php echo esc_attr( $type === 'billing_country_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'billing_country_exclude' ? $name_billing_country_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_billing_country_exclude ) ?>"
                        data-redis_name_default="{prefix_default}billing_country_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="country"
                        class="viredis-search-select2 viredis-search-country viredis-cart-condition-billing_country_exclude viredis-condition-value" multiple>
					<?php
					if ( $woo_countries && is_array( $woo_countries ) && count( $woo_countries ) ) {
						foreach ( $woo_countries as $country_id => $country_name ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $country_id ), selected( in_array( $country_id, $billing_country_exclude ), true ), esc_html( $country_name ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-shipping_country_include-wrap <?php echo esc_attr( $type === 'shipping_country_include' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'shipping_country_include' ? $name_shipping_country_include : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_shipping_country_include ) ?>"
                        data-redis_name_default="{prefix_default}shipping_country_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="country"
                        class="viredis-search-select2 viredis-search-country viredis-cart-condition-shipping_country_include viredis-condition-value" multiple>
					<?php
					if ( $woo_countries && is_array( $woo_countries ) && count( $woo_countries ) ) {
						foreach ( $woo_countries as $country_id => $country_name ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $country_id ), selected( in_array( $country_id, $shipping_country_include ), true ), esc_html( $country_name ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-cart-condition-wrap viredis-condition-shipping_country_exclude-wrap <?php echo esc_attr( $type === 'shipping_country_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'shipping_country_exclude' ? $name_shipping_country_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_shipping_country_exclude ) ?>"
                        data-redis_name_default="{prefix_default}shipping_country_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="country"
                        class="viredis-search-select2 viredis-search-country viredis-cart-condition-shipping_country_exclude viredis-condition-value" multiple>
					<?php
					if ( $woo_countries && is_array( $woo_countries ) && count( $woo_countries ) ) {
						foreach ( $woo_countries as $country_id => $country_name ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $country_id ), selected( in_array( $country_id, $shipping_country_exclude ), true ), esc_html( $country_name ) );
						}
					}
					?>
                </select>
            </div>
        </div>
        <div class="field viredis-revmove-condition-btn-wrap">
             <span class="viredis-revmove-condition-btn viredis-pd_cart_rule-revmove-condition"
                   data-tooltip="<?php esc_html_e( 'Remove', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                 <i class="times icon"></i>
             </span>
        </div>
    </div>
</div>