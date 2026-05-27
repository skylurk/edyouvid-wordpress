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
$type                = $type ?? 'logged';
$woo_currency_symbol = $woo_currency_symbol ?? get_woocommerce_currency_symbol();
if ( empty( $woo_users_role ) ) {
	$woo_users_role = wp_roles()->roles;
}
if ( empty( $woo_order_status ) ) {
	$woo_order_status = wc_get_order_statuses();
}
$conditions             = array(
	'Customer\'s Information' => array(
		'logged'            => esc_html__( 'Is logged in user', 'redis-woo-dynamic-pricing-and-discounts' ),
		'user_role_include' => esc_html__( 'Include user role', 'redis-woo-dynamic-pricing-and-discounts' ),
		'user_role_exclude' => esc_html__( 'Exclude user role', 'redis-woo-dynamic-pricing-and-discounts' ),
		'user_include'      => esc_html__( 'Include user', 'redis-woo-dynamic-pricing-and-discounts' ),
		'user_exclude'      => esc_html__( 'Exclude user', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
	'Order History'           => array(
		'order_status' => esc_html__( 'Order Status', 'redis-woo-dynamic-pricing-and-discounts' ),
		'order_count'  => esc_html__( 'Order Count', 'redis-woo-dynamic-pricing-and-discounts' ),
		'order_total'  => esc_html__( 'Order Total', 'redis-woo-dynamic-pricing-and-discounts' ),
		'last_order'   => esc_html__( 'Has Last Order', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
	'Purchased Products'      => array(
		'product_include' => esc_html__( 'Include Purchased Products', 'redis-woo-dynamic-pricing-and-discounts' ),
		'product_exclude' => esc_html__( 'Exclude Purchased Products', 'redis-woo-dynamic-pricing-and-discounts' ),
		'cats_include'    => esc_html__( 'Include Purchased Categories', 'redis-woo-dynamic-pricing-and-discounts' ),
		'cats_exclude'    => esc_html__( 'Exclude Purchased Categories', 'redis-woo-dynamic-pricing-and-discounts' ),
	),
);
$logged                 = $params['logged'] ?? 0;
$user_role_include      = isset($params['user_role_include']) && is_array($params['user_role_include']) ? $params['user_role_include'] : array();
$user_role_exclude      = isset($params['user_role_exclude']) && is_array($params['user_role_exclude']) ? $params['user_role_exclude'] : array();
$user_include           = $params['user_include'] ?? array();
$user_exclude           = $params['user_exclude'] ?? array();
$order_status           = isset($params['order_status']) && is_array($params['order_status']) ? $params['order_status'] : array();
$order_count_from       = $params['order_count_from'] ?? '';
$order_count_to         = $params['order_count_to'] ?? '';
$order_count_min        = $params['order_count_min'] ?? 0;
$order_count_min        = $order_count_min ?: 0;
$order_count_max        = $params['order_count_max'] ?? '';
$order_total_from       = $params['order_total_from'] ?? '';
$order_total_to         = $params['order_total_to'] ?? '';
$order_total_min        = $params['order_total_min'] ?? 0;
$order_total_min        = $order_total_min ?: 0;
$order_total_max        = $params['order_total_max'] ?? '';
$last_order_type        = $params['last_order_type'] ?? '';
$last_order_date        = $params['last_order_date'] ?? '';
$product_include        = $params['product_include'] ?? array();
$product_exclude        = $params['product_exclude'] ?? array();
$cats_include           = $params['cats_include'] ?? array();
$cats_exclude           = $params['cats_exclude'] ?? array();
$name_condition_type    = $prefix . 'type[' . $index . '][]';
$name_logged            = $prefix . 'logged[' . $index . ']';
$name_user_role_include = $prefix . 'user_role_include[' . $index . '][]';
$name_user_role_exclude = $prefix . 'user_role_exclude[' . $index . '][]';
$name_user_include      = $prefix . 'user_include[' . $index . '][]';
$name_user_exclude      = $prefix . 'user_exclude[' . $index . '][]';
$name_order_status      = $prefix . 'order_status[' . $index . '][]';
$name_order_count_from  = $prefix . 'order_count[' . $index . '][from][]';
$name_order_count_to    = $prefix . 'order_count[' . $index . '][to][]';
$name_order_count_min   = $prefix . 'order_count[' . $index . '][min][]';
$name_order_count_max   = $prefix . 'order_count[' . $index . '][max][]';
$name_order_total_from  = $prefix . 'order_total[' . $index . '][from][]';
$name_order_total_to    = $prefix . 'order_total[' . $index . '][to][]';
$name_order_total_min   = $prefix . 'order_total[' . $index . '][min][]';
$name_order_total_max   = $prefix . 'order_total[' . $index . '][max][]';
$name_last_order_type   = $prefix . 'last_order[' . $index . '][type]';
$name_last_order_date   = $prefix . 'last_order[' . $index . '][date]';
$name_product_include   = $prefix . 'product_include[' . $index . '][]';
$name_product_exclude   = $prefix . 'product_exclude[' . $index . '][]';
$name_cats_include      = $prefix . 'cats_include[' . $index . '][]';
$name_cats_exclude      = $prefix . 'cats_exclude[' . $index . '][]';
?>
<div class="vi-ui placeholder segment viredis-condition-wrap-wrap viredis-user-condition-wrap-wrap">
    <div class="fields">
        <div class="field viredis-condition-move">
            <i class="expand arrows alternate icon"></i>
        </div>
        <div class="four wide field">
            <select name="<?php echo esc_attr( $name_condition_type ); ?>"
                    data-redis_name="<?php echo esc_attr( $name_condition_type ); ?>"
                    data-redis_name_default="{prefix_default}type[{index_default}][]"
                    data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                    class="vi-ui fluid dropdown viredis-condition-type viredis-user-condition-type">
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
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-logged-wrap <?php echo esc_attr( $type === 'logged' ? '' : 'viredis-hidden' ); ?>">
                <select class="vi-ui fluid dropdown viredis-user-condition-logged"
                        name="<?php echo esc_attr( $type === 'logged' ? $name_logged : '' ); ?>"
                        data-redis_name_default="{prefix_default}logged[{index_default}]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_logged ) ?>">
                    <option value="0" <?php selected( $logged, 0 ) ?>>
						<?php esc_html_e( 'No', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                    <option value="1" <?php selected( $logged, 1 ) ?>>
						<?php esc_html_e( 'Yes', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-user_role_include-wrap <?php echo esc_attr( $type === 'user_role_include' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-user-role viredis-user-condition-user_role_include viredis-condition-value"
                        data-type_select2="user_role"
                        name="<?php echo esc_attr( $type === 'user_role_include' ? $name_user_role_include : '' ); ?>"
                        data-redis_name_default="{prefix_default}user_role_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_user_role_include ) ?>" multiple>
					<?php
					if ( $woo_users_role && is_array( $woo_users_role ) && count( $woo_users_role ) ) {
						foreach ( $woo_users_role as $k => $v ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( in_array( $k, $user_role_include ), true ), esc_html( $v['name'] ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-user_role_exclude-wrap <?php echo esc_attr( $type === 'user_role_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-user-role viredis-user-condition-user_role_exclude viredis-condition-value"
                        data-type_select2="user_role"
                        name="<?php echo esc_attr( $type === 'user_role_exclude' ? $name_user_role_exclude : '' ); ?>"
                        data-redis_name_default="{prefix_default}user_role_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_user_role_exclude ) ?>" multiple>
					<?php
					if ( $woo_users_role && is_array( $woo_users_role ) && count( $woo_users_role ) ) {
						foreach ( $woo_users_role as $k => $v ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( in_array( $k, $user_role_exclude ), true ), esc_html( $v['name'] ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-user_include-wrap <?php echo esc_attr( $type === 'user_include' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-user viredis-user-condition-user_include viredis-condition-value"
                        data-type_select2="user"
                        name="<?php echo esc_attr( $type === 'user_include' ? $name_user_include : '' ); ?>"
                        data-redis_name_default="{prefix_default}user_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_user_include ) ?>" multiple>
					<?php
					if ( $user_include && is_array( $user_include ) && count( $user_include ) ) {
						foreach ( $user_include as $user_id ) {
							$user = get_user_by( 'id', $user_id );
							if ( $user ) {
								printf( '<option value="%s" selected>%s</option>', esc_attr( $user_id ), esc_html( $user->display_name ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-user_exclude-wrap <?php echo esc_attr( $type === 'user_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-user viredis-user-condition-user_exclude viredis-condition-value"
                        data-type_select2="user"
                        name="<?php echo esc_attr( $type === 'user_exclude' ? $name_user_exclude : '' ); ?>"
                        data-redis_name_default="{prefix_default}user_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_user_exclude ) ?>" multiple>
					<?php
					if ( $user_exclude && is_array( $user_exclude ) && count( $user_exclude ) ) {
						foreach ( $user_exclude as $user_id ) {
							$user = get_user_by( 'id', $user_id );
							if ( $user ) {
								printf( '<option value="%s" selected>%s</option>', esc_attr( $user_id ), esc_html( $user->display_name ) );
							}
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-order_status-wrap <?php echo esc_attr( $type === 'order_status' ? '' : 'viredis-hidden' ); ?>">
                <select class="vi-ui fluid dropdown viredis-user-condition-order_status"
                        name="<?php echo esc_attr( $type === 'order_status' ? $name_order_status : '' ); ?>"
                        data-redis_name_default="{prefix_default}order_status[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_order_status ) ?>" multiple>
					<?php
					if ( $woo_order_status && is_array( $woo_order_status ) && count( $woo_order_status ) ) {
						foreach ( $woo_order_status as $k => $v ) {
							printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( in_array( $k, $order_status ), true ), esc_html( $v ) );
						}
					}
					?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-order_count-wrap <?php echo esc_attr( $type === 'order_count' ? '' : 'viredis-hidden' ); ?>">
                <div class="field viredis-condition-field-wrap">
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui left action labeled input viredis-input-range-wrap">
                                <div class="vi-ui label viredis-basic-label"><?php esc_html_e( 'From', 'redis-woo-dynamic-pricing-and-discounts' ); ?></div>
                                <input type="date" class="viredis-user-condition-order_count_from viredis-condition-value viredis-condition-date-from"
                                       name="<?php echo esc_attr( $type === 'order_count' ? $name_order_count_from : '' ); ?>"
                                       data-redis_name_default="{prefix_default}order_count_from[{index_default}][]"
                                       min="2020-06-05"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_count_from ); ?>"
                                       value="<?php echo esc_attr( $order_count_from ); ?>">
                                <div class="vi-ui label viredis-basic-label"><?php esc_html_e( 'To', 'redis-woo-dynamic-pricing-and-discounts' ); ?></div>
                                <input type="date" class="viredis-user-condition-order_count_to viredis-condition-value viredis-condition-date-to"
                                       name="<?php echo esc_attr( $type === 'order_count' ? $name_order_count_to : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_count_to ); ?>"
                                       data-redis_name_default="{prefix_default}order_count_to[{index_default}][]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       value="<?php echo esc_attr( $order_count_to ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui left action labeled input viredis-input-range-wrap">
                                <div class="vi-ui label viredis-basic-label"><?php esc_html_e( 'Min', 'redis-woo-dynamic-pricing-and-discounts' ); ?></div>
                                <input type="number" class="viredis-user-condition-order_count_min viredis-condition-value" min="0" step="1"
                                       name="<?php echo esc_attr( $type === 'order_count' ? $name_order_count_min : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_count_min ); ?>"
                                       data-redis_name_default="{prefix_default}order_count_min[{index_default}][]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       value="<?php echo esc_attr( $order_count_min ); ?>">
                                <div class="vi-ui label viredis-basic-label"><?php esc_html_e( 'Max', 'redis-woo-dynamic-pricing-and-discounts' ); ?></div>
                                <input type="number" class="viredis-user-condition-order_count_max viredis-condition-value" min="0" step="1"
                                       name="<?php echo esc_attr( $type === 'order_count' ? $name_order_count_max : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_count_max ); ?>"
                                       data-redis_name_default="{prefix_default}order_count_max[{index_default}][]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       data-allow_empty="1"
                                       placeholder="<?php esc_attr_e( 'Leave blank to not limit this.', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                       value="<?php echo esc_attr( $order_count_max ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-order_total-wrap <?php echo esc_attr( $type === 'order_total' ? '' : 'viredis-hidden' ); ?>">
                <div class="field viredis-condition-field-wrap">
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui left action labeled input viredis-input-range-wrap">
                                <div class="vi-ui label viredis-basic-label"><?php esc_html_e( 'From', 'redis-woo-dynamic-pricing-and-discounts' ); ?></div>
                                <input type="date" class="viredis-user-condition-order_total_from viredis-condition-value viredis-condition-date-from"
                                       name="<?php echo esc_attr( $type === 'order_total' ? $name_order_total_from : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_total_from ); ?>"
                                       data-redis_name_default="{prefix_default}order_total[{index_default}][from][]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       value="<?php echo esc_attr( $order_total_from ); ?>">
                                <div class="vi-ui label viredis-basic-label"><?php esc_html_e( 'To', 'redis-woo-dynamic-pricing-and-discounts' ); ?></div>
                                <input type="date" class="viredis-user-condition-order_total_to viredis-condition-value viredis-condition-date-to"
                                       name="<?php echo esc_attr( $type === 'order_total' ? $name_order_total_to : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_total_to ); ?>"
                                       data-redis_name_default="{prefix_default}order_total[{index_default}][to][]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       value="<?php echo esc_attr( $order_total_to ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui left action labeled input viredis-input-range-wrap">
                                <div class="vi-ui label viredis-basic-label"><?php /* translators: %s: currency symbol */
                                    printf( esc_html__( 'Min(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_attr( $woo_currency_symbol ) ); ?></div>
                                <input type="number" class="viredis-user-condition-order_total_min viredis-condition-value" min="0" step="1"
                                       name="<?php echo esc_attr( $type === 'order_total' ? $name_order_total_min : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_total_min ); ?>"
                                       data-redis_name_default="{prefix_default}order_total[{index_default}][min][]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       value="<?php echo esc_attr( $order_total_min ); ?>">
                                <div class="vi-ui label viredis-basic-label"><?php /* translators: %s: currency symbol */
                                    printf( esc_html__( 'Max(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_attr( $woo_currency_symbol ) ); ?></div>
                                <input type="number" class="viredis-user-condition-order_total_max viredis-condition-value" min="0" step="1"
                                       name="<?php echo esc_attr( $type === 'order_total' ? $name_order_total_max : '' ); ?>"
                                       data-redis_name="<?php echo esc_attr( $name_order_total_max ); ?>"
                                       data-redis_name_default="{prefix_default}order_total[{index_default}][max][]"
                                       data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                       data-allow_empty="1"
                                       placeholder="<?php esc_attr_e( 'Leave blank to not limit this.', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                       value="<?php echo esc_attr( $order_total_max ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-last_order-wrap <?php echo esc_attr( $type === 'last_order' ? '' : 'viredis-hidden' ); ?>">
                <div class="vi-ui left action labeled input">
                    <select name="<?php echo esc_attr( $type === 'last_order' ? $name_last_order_type : '' ); ?>"
                            data-redis_name="<?php echo esc_attr( $name_last_order_type ); ?>"
                            data-redis_name_default="{prefix_default}last_order[{index_default}][type]"
                            data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                            class="vi-ui dropdown viredis-user-condition-last_order_type">
                        <option value="before" <?php selected( $last_order_type, 'before' ) ?>>
	                        <?php esc_html_e( 'Before', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                        </option>
                        <option value="after" <?php selected( $last_order_type, 'after' ) ?>>
	                        <?php esc_html_e( 'After', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                        </option>
                    </select>
                    <input type="date" class="viredis-user-condition-last_order_date viredis-condition-value"
                           name="<?php echo esc_attr( $type === 'last_order' ? $name_last_order_date : '' ); ?>"
                           data-redis_name="<?php echo esc_attr( $name_last_order_date ); ?>"
                           data-redis_name_default="{prefix_default}last_order[{index_default}][date]"
                           data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                           value="<?php echo esc_attr( $last_order_date ); ?>">
                </div>
            </div>
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-product_include-wrap <?php echo esc_attr( $type === 'product_include' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'product_include' ? $name_product_include : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_product_include ); ?>"
                        data-redis_name_default="{prefix_default}product_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="product"
                        class="viredis-search-select2 viredis-search-product viredis-user-condition-product_include viredis-condition-value" multiple>
					<?php
					if ( $product_include && is_array( $product_include ) && count( $product_include ) ) {
						foreach ( $product_include as $pd_id ) {
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
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-product_exclude-wrap <?php echo esc_attr( $type === 'product_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'product_exclude' ? $name_product_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_product_exclude ); ?>"
                        data-redis_name_default="{prefix_default}product_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="product"
                        class="viredis-search-select2 viredis-search-product viredis-user-condition-product_exclude viredis-condition-value" multiple>
					<?php
					if ( $product_exclude && is_array( $product_exclude ) && count( $product_exclude ) ) {
						foreach ( $product_exclude as $pd_id ) {
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
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-cats_include-wrap <?php echo esc_attr( $type === 'cats_include' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'cats_include' ? $name_cats_include : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_cats_include ); ?>"
                        data-redis_name_default="{prefix_default}cats_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="category"
                        class="viredis-search-select2 viredis-search-category viredis-user-condition-cats_include viredis-condition-value" multiple>
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
            <div class="field viredis-condition-wrap viredis-user-condition-wrap viredis-condition-cats_exclude-wrap <?php echo esc_attr( $type === 'cats_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select name="<?php echo esc_attr( $type === 'cats_exclude' ? $name_cats_exclude : '' ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_cats_exclude ); ?>"
                        data-redis_name_default="{prefix_default}cats_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-type_select2="category"
                        class="viredis-search-select2 viredis-search-category viredis-user-condition-cats_exclude viredis-condition-value" multiple>
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
        </div>
        <div class="field viredis-revmove-condition-btn-wrap">
             <span class="viredis-revmove-condition-btn viredis-pd_user_rule-revmove-condition"
                   data-tooltip="<?php esc_html_e( 'Remove', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                 <i class="times icon"></i>
             </span>
        </div>
    </div>
</div>
