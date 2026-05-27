<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$index               = $index ?? '';
$index               = $index ?: '{index}';
$prefix              = $prefix ?? '';
$prefix              = $prefix ?: '{prefix}';
$params              = isset($params) && is_array($params) ? $params : array();
$type                = $type ?? 'is_sale';
$woo_currency_symbol = $woo_currency_symbol ?? get_woocommerce_currency_symbol();
$conditions          = array(
	'is_sale'       => esc_html__( 'Is sale product', 'redis-woo-dynamic-pricing-and-discounts' ),
	'pd_price'      => esc_html__( 'Product price', 'redis-woo-dynamic-pricing-and-discounts' ),
	'pd_visibility' => esc_html__( 'Product visibility', 'redis-woo-dynamic-pricing-and-discounts' ),
	'pd_include'    => esc_html__( 'Include products', 'redis-woo-dynamic-pricing-and-discounts' ),
	'pd_exclude'    => esc_html__( 'Exclude products', 'redis-woo-dynamic-pricing-and-discounts' ),
	'cats_include'  => esc_html__( 'Include categories', 'redis-woo-dynamic-pricing-and-discounts' ),
	'cats_exclude'  => esc_html__( 'Exclude categories', 'redis-woo-dynamic-pricing-and-discounts' ),
);
$is_sale             = $params['is_sale'] ?? 0;
$is_sale             = $is_sale ?: 0;
$pd_price_min        = $params['pd_price_min'] ?? 0;
$pd_price_min        = $pd_price_min ?: 0;
$pd_price_max        = $params['pd_price_max'] ?? '';
$pd_visibility       = $params['pd_visibility'] ?? 'visible';
$pd_include          = $params['pd_include'] ?? array();
$pd_exclude          = $params['pd_exclude'] ?? array();
$cats_include        = $params['cats_include'] ?? array();
$cats_exclude        = $params['cats_exclude'] ?? array();
$name_condition_type = $prefix . 'type[' . $index . '][]';
$name_is_sale        = $prefix . 'is_sale[' . $index . ']';
$name_pd_price_min   = $prefix . 'pd_price_min[' . $index . ']';
$name_pd_price_max   = $prefix . 'pd_price_max[' . $index . ']';
$name_pd_visibility  = $prefix . 'pd_visibility[' . $index . ']';
$name_pd_include     = $prefix . 'pd_include[' . $index . '][]';
$name_pd_exclude     = $prefix . 'pd_exclude[' . $index . '][]';
$name_cats_include   = $prefix . 'cats_include[' . $index . '][]';
$name_cats_exclude   = $prefix . 'cats_exclude[' . $index . '][]';
?>
<div class="vi-ui placeholder segment viredis-condition-wrap-wrap viredis-pd-condition-wrap-wrap">
    <div class="fields">
        <div class="field viredis-condition-move">
            <i class="expand arrows alternate icon"></i>
        </div>
        <div class="four wide field">
            <select class="vi-ui fluid dropdown viredis-condition-type viredis-pd-condition-type"
                    data-redis_name="<?php echo esc_attr( $name_condition_type ) ?>"
                    data-redis_name_default="{prefix_default}type[{index_default}][]"
                    data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                    name="<?php echo esc_attr( $name_condition_type ) ?>">
	            <?php
	            foreach ( $conditions as $condition_k => $condition_v ) {
		            printf( '<option value="%s" %s >%s</option>', esc_attr( $condition_k ), selected( $type, $condition_k ), esc_html( $condition_v ) );
	            }
	            ?>
            </select>
        </div>
        <div class="thirteen wide field viredis-condition-value-wrap-wrap">
            <div class="field viredis-condition-wrap viredis-pd-condition-wrap viredis-condition-is_sale-wrap <?php echo esc_attr( $type === 'is_sale' ? '' : 'viredis-hidden' ); ?>">
                <select class="vi-ui fluid dropdown viredis-pd-condition-is_sale"
                        name="<?php echo esc_attr( $type === 'is_sale' ? $name_is_sale : '' ); ?>"
                        data-redis_name_default="{prefix_default}is_sale[{index_default}]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_is_sale ) ?>">
                    <option value="1" <?php selected( $is_sale, 1 ); ?>>
	                    <?php esc_html_e( 'Yes', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                    <option value="0" <?php selected( $is_sale, 0 ); ?>>
	                    <?php esc_html_e( 'No', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-pd-condition-wrap viredis-condition-pd_price-wrap <?php echo esc_attr( $type === 'pd_price' ? '' : 'viredis-hidden' ); ?>">
                <div class="equal width fields">
                    <div class="field">
                        <div class="vi-ui  left action labeled input viredis-input-range-wrap">
                            <div class="vi-ui label viredis-basic-label">
	                            <?php /* translators: %s: currency symbol */
                                printf( esc_html__( 'Min(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_attr( $woo_currency_symbol ) ) ?>
                            </div>
                            <input type="number" min="0" step="0.01"
                                   name="<?php echo esc_attr( $type === 'pd_price' ? $name_pd_price_min : '' ); ?>"
                                   data-redis_name="<?php echo esc_attr( $name_pd_price_min ) ?>"
                                   data-redis_name_default="{prefix_default}pd_price_min[{index_default}]"
                                   data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                   class="viredis-pd-condition-pd_price_min viredis-condition-value" value="<?php echo esc_attr( $pd_price_min ) ?>">
                            <div class="vi-ui label viredis-basic-label">
	                            <?php /* translators: %s: currency symbol */
                                printf( esc_html__( 'Max(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_attr( $woo_currency_symbol ) ) ?>
                            </div>
                            <input type="number" min="0" step="0.01"
                                   name="<?php echo esc_attr( $type === 'pd_price' ? $name_pd_price_max : '' ); ?>"
                                   data-redis_name="<?php echo esc_attr( $name_pd_price_max ) ?>"
                                   data-redis_name_default="{prefix_default}pd_price_max[{index_default}]"
                                   data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                                   data-allow_empty="1"
                                   placeholder="<?php esc_attr_e( 'Leave blank to not limit this.', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                   class="viredis-pd-condition-pd_price_max viredis-condition-value" value="<?php echo esc_attr( $pd_price_max ) ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="field viredis-condition-wrap viredis-pd-condition-wrap viredis-condition-pd_visibility-wrap <?php echo esc_attr( $type === 'pd_visibility' ? '' : 'viredis-hidden' ); ?>">
                <select class="vi-ui fluid dropdown viredis-pd-condition-pd_visibility"
                        name="<?php echo esc_attr( $type === 'pd_visibility' ? $name_pd_visibility : '' ); ?>"
                        data-redis_name_default="{prefix_default}pd_visibility[{index_default}]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_pd_visibility ) ?>">
                    <option value="visible" <?php selected( $pd_visibility, 'visible' ) ?>>
	                    <?php esc_html_e( 'Shop and search results', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                    <option value="catalog" <?php selected( $pd_visibility, 'catalog' ) ?>>
	                    <?php esc_html_e( 'Shop only', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                    <option value="search" <?php selected( $pd_visibility, 'search' ) ?>>
	                    <?php esc_html_e( 'Search results only', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                    <option value="hidden" <?php selected( $pd_visibility, 'hidden' ) ?>>
	                    <?php esc_html_e( 'Hidden', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                    </option>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-pd-condition-wrap viredis-condition-pd_include-wrap <?php echo esc_attr( $type === 'pd_include' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-product viredis-pd-condition-pd_include viredis-condition-value"
                        data-type_select2="product"
                        name="<?php echo esc_attr( $type === 'pd_include' ? $name_pd_include : '' ); ?>"
                        data-redis_name_default="{prefix_default}pd_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_pd_include ) ?>" multiple>
	                <?php
	                if ( $pd_include && is_array( $pd_include ) && count( $pd_include ) ) {
		                foreach ( $pd_include as $pd_id ) {
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
            <div class="field viredis-condition-wrap viredis-pd-condition-wrap viredis-condition-pd_exclude-wrap <?php echo esc_attr( $type === 'pd_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-product viredis-pd-condition-pd_exclude viredis-condition-value"
                        data-type_select2="product"
                        name="<?php echo esc_attr( $type === 'pd_exclude' ? $name_pd_exclude : '' ); ?>"
                        data-redis_name_default="{prefix_default}pd_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_pd_exclude ) ?>" multiple>
	                <?php
	                if ( $pd_exclude && is_array( $pd_exclude ) && count( $pd_exclude ) ) {
		                foreach ( $pd_exclude as $pd_id ) {
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
            <div class="field viredis-condition-wrap viredis-pd-condition-wrap viredis-condition-cats_include-wrap <?php echo esc_attr( $type === 'cats_include' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-category viredis-pd-condition-cats_include viredis-condition-value"
                        data-type_select2="category"
                        name="<?php echo esc_attr( $type === 'cats_include' ? $name_cats_include : '' ); ?>"
                        data-redis_name_default="{prefix_default}cats_include[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_cats_include ) ?>" multiple>
	                <?php
	                if ( $cats_include && is_array( $cats_include ) && count( $cats_include ) ) {
		                foreach ( $cats_include as $cats_id ) {
			                $term = get_term( $cats_id );
			                if ( $term ) {
				                printf( '<option value="%s" selected>%s</option>', esc_attr( $cats_id ), esc_attr( $term->name ) );
			                }
		                }
	                }
	                ?>
                </select>
            </div>
            <div class="field viredis-condition-wrap viredis-pd-condition-wrap viredis-condition-cats_exclude-wrap <?php echo esc_attr( $type === 'cats_exclude' ? '' : 'viredis-hidden' ); ?>">
                <select class="viredis-search-select2 viredis-search-category viredis-pd-condition-cats_exclude viredis-condition-value"
                        data-type_select2="category"
                        name="<?php echo esc_attr( $type === 'cats_exclude' ? $name_cats_exclude : '' ); ?>"
                        data-redis_name_default="{prefix_default}cats_exclude[{index_default}][]"
                        data-redis_prefix="<?php echo esc_attr( $prefix ); ?>"
                        data-redis_name="<?php echo esc_attr( $name_cats_exclude ) ?>" multiple>
	                <?php
	                if ( $cats_exclude && is_array( $cats_exclude ) && count( $cats_exclude ) ) {
		                foreach ( $cats_exclude as $cats_id ) {
			                $term = get_term( $cats_id );
			                if ( $term ) {
				                printf( '<option value="%s" selected>%s</option>', esc_attr( $cats_id ), esc_attr( $term->name ) );
			                }
		                }
	                }
	                ?>
                </select>
            </div>
        </div>
        <div class="field viredis-revmove-condition-btn-wrap">
            <span class="viredis-revmove-condition-btn viredis-pd_rule1-revmove-condition" data-tooltip="<?php esc_html_e( 'Remove', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                 <i class="times icon"></i>
            </span>
        </div>
    </div>
</div>