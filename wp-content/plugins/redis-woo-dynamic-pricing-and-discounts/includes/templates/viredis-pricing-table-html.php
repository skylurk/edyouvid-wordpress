<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! $price || ! $product || ! is_a( $product, 'WC_Product' ) || empty( $rules ) ) {
	return;
}
$head_title    = apply_filters( 'viredis-pricing-table-head-title', esc_html__( 'Title', 'redis-woo-dynamic-pricing-and-discounts' ) );
$head_qty      = apply_filters( 'viredis-pricing-table-head-qty', esc_html__( 'Quantity', 'redis-woo-dynamic-pricing-and-discounts' ) );
$head_discount = apply_filters( 'viredis-pricing-table-head-discount', esc_html__( 'Discount', 'redis-woo-dynamic-pricing-and-discounts' ) );
?>
<div class="viredis-pricing-table-wrap">
    <table class="viredis-pricing-table">
		<?php
		if ( apply_filters( 'viredis-pricing-table-head-enable', true ) ) {
			?>
            <thead>
			<?php
			if ( ! empty( $table_title ) ) {
				printf( '<tr class="viredis-pricing-table-tr viredis-pricing-table-tr-head"><th colspan="3" class="viredis-pricing-table-th viredis-pricing-table-table-title">%s</th></tr>', wp_kses_post( $table_title ) );
			}
			?>
            <tr class="viredis-pricing-table-tr viredis-pricing-table-tr-head">
                <th class="viredis-pricing-table-th viredis-pricing-table-th-title">
					<?php echo esc_html( $head_title ); ?>
                </th>
                <th class="viredis-pricing-table-th viredis-pricing-table-th-qty">
					<?php echo esc_html( $head_qty ); ?>
                </th>
                <th class="viredis-pricing-table-th viredis-pricing-table-th-discount">
					<?php echo esc_html( $head_discount ); ?>
                </th>
            </tr>
            </thead>
			<?php
		}
		?>
        <tbody>
		<?php
		$price_decimal = wc_get_price_decimals();
		foreach ( $rules as $rule_id => $params ) {
			$title    = $params['title'] ?? '';
			$qty_from = $params['ranges']['from'] ?? array();
			if ( empty( $qty_from ) || !is_array($qty_from) ) {
				continue;
			}
			foreach ( $qty_from as $i => $from ) {
				?>
                <tr class="viredis-pricing-table-tr">
					<?php
					if ( ! $i ) {
						printf( '<td rowspan="%s" class="viredis-pricing-table-td viredis-pricing-table-td-title">%s</td>', esc_attr( count( $qty_from ) ), esc_attr( $title ) );
					}
					?>
                    <td class="viredis-pricing-table-td viredis-pricing-table-td-qty">
						<?php
						$to = $params['ranges']['to'][ $i ] ?? '';
						if ( $to ) {
							if ($from == $to){
                                echo esc_html($from);
							}else{
								printf( '%s - %s', esc_html( $from ), esc_html( $to ) );
							}
						} else {
							printf( '%s+', esc_html( $from ) );
						}
						?>
                    </td>
                    <td class="viredis-pricing-table-td viredis-pricing-table-td-discount">
						<?php
						$discount_value     = $params['ranges']['price'][ $i ] ?? 0;
						$rule_discount_type = $params['ranges']['type'][ $i ] ?? 0;
						switch ( $discount_type ) {
							case 'percentage_discount':
								if ( $rule_discount_type ) {
									$discount = VIREDIS_Frontend_Product::get_fixed_discount_value( $rule_discount_type, $discount_value, $price );
									$discount = wc_format_decimal( $discount * 100 / $price, $price_decimal );
									echo esc_html( $discount . '%' );
								} else {
									echo esc_html( $discount_value . '%' );
								}
								break;
							case 'fixed_discount':
								$discount = VIREDIS_Frontend_Product::get_fixed_discount_value( $rule_discount_type, $discount_value, $price );
								printf( '%s', wc_price( $discount ) );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								break;
							case 'fixed_price':
								$discount = VIREDIS_Frontend_Product::get_fixed_discount_value( $rule_discount_type, $discount_value, $price );
								$discount = $price > $discount ? $price - $discount : 0;
								printf( '%s', wc_price( $discount ) );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								break;
						}
						?>
                    </td>
                </tr>
				<?php
			}
		}
		?>
        </tbody>
    </table>
</div>
