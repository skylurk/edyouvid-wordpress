<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$index               = $index ?? '';
$index               = $index ?: '{index}';
$woo_currency_symbol = $woo_currency_symbol ?? get_woocommerce_currency_symbol();
$params              = $params ?? array();
switch ( $type ) {
	case 'bulk_qty':
		$from = $params['from'] ?? 0;
		$to          = $params['to'] ?? 0;
		$type        = $params['type'] ?? 0;
		$price       = $params['price'] ?? 10;
		$name_from   = 'pd_bulk_qty_range[' . $index . '][from][]';
		$name_to     = 'pd_bulk_qty_range[' . $index . '][to][]';
		$name_price  = 'pd_bulk_qty_range[' . $index . '][price][]';
		$name_type   = 'pd_bulk_qty_range[' . $index . '][type][]';
		?>
        <div class="vi-ui segment viredis-condition-wrap-wrap viredis-product-price-wrap-wrap viredis-pd_bulk_qty_range-wrap-wrap">
            <div class="fields">
                <div class="sixteen wide field viredis-condition-wrap viredis-product-price-wrap viredis-pd_bulk_qty_range-wrap">
                    <div class="equal width fields">
                        <div class="field">
                            <div class="vi-ui left action labeled input viredis-input-range-wrap">
                                <div class="vi-ui label viredis-basic-label">
						            <?php esc_html_e( 'From', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                </div>
                                <input type="number" step="1" class="viredis-pd_bulk_qty_range-quantity viredis-pd_bulk_qty_range-from"
                                       data-redis_name_default="pd_bulk_qty_range[{index_default}][from][]"
                                       data-redis_prefix="pd_bulk_qty_range"
                                       min="1"
                                       name="<?php echo esc_attr( $name_from ); ?>" value="<?php echo esc_attr( $from ); ?>">
                                <div class="vi-ui label viredis-basic-label">
						            <?php esc_html_e( 'To', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                </div>
                                <input type="number" step="1" class="viredis-pd_bulk_qty_range-quantity viredis-pd_bulk_qty_range-to"
                                       data-redis_name_default="pd_bulk_qty_range[{index_default}][to][]"
                                       data-redis_prefix="pd_bulk_qty_range"
                                       data-allow_empty="1"
                                       placeholder="<?php esc_attr_e( 'Leave blank to not limit this', 'redis-woo-dynamic-pricing-and-discounts' ); ?>"
                                       name="<?php echo esc_attr( $name_to ); ?>" value="<?php echo esc_attr( $to ); ?>">
                            </div>
                        </div>
                        <div class="field">
                            <div class="vi-ui right action labeled input">
                                <div class="vi-ui label viredis-basic-label">
	                                <?php esc_html_e( 'Value', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                </div>
                                <input type="number" step="0.01" min="0" max="<?php echo esc_attr( $type ? '' : '100' ); ?>"
                                       class="viredis-pd_bulk_qty_range-price"
                                       data-redis_name_default="pd_bulk_qty_range[{index_default}][price][]"
                                       data-redis_prefix="pd_bulk_qty_range"
                                       name="<?php echo esc_attr( $name_price ); ?>" value="<?php echo esc_attr( $price ); ?>">
                                <select name="<?php echo esc_attr( $name_type ); ?>"
                                        data-redis_name_default="pd_bulk_qty_range[{index_default}][type][]"
                                        data-redis_prefix="pd_bulk_qty_range" class="vi-ui dropdown viredis-product-price-type viredis-pd_bulk_qty_range-type">
                                    <option value="0" <?php selected( $type, 0 ) ?>>
										<?php esc_html_e( 'Percentage discount price(%)', 'redis-woo-dynamic-pricing-and-discounts' ); ?>
                                    </option>
                                    <option value="1" <?php selected( $type, 1 ) ?>>
	                                    <?php /* translators: %s: currency symbol */
                                        printf( esc_html__( 'Fixed discount price(%s)', 'redis-woo-dynamic-pricing-and-discounts' ), esc_html( $woo_currency_symbol ) ); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="field viredis-product-price-wrap-action">
                    <span class="viredis-product-price-remove viredis-product-price-remove-bulk_qty" data-tooltip="<?php esc_attr_e( 'Remove', 'redis-woo-dynamic-pricing-and-discounts' ); ?>">
                    <i class="times icon"></i>
                </span>
                </div>
            </div>
        </div>
		<?php
		break;
}
?>
