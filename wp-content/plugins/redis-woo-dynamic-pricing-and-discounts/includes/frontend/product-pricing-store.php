<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Frontend_Product_Pricing_Store {
	public static $cache = array();
	public static function enqueue_scripts() {
		if ( ! is_single() || ! is_product() ) {
			return;
		}
		$suffix = WP_DEBUG ? '' : 'min.';
		if ( ! wp_style_is( 'viredis-single', 'registered' ) ) {
			wp_register_style( 'viredis-single', VIREDIS_CSS . 'frontend-single.' . $suffix . 'css', array(), VIREDIS_VERSION );
		}
		if ( ! wp_script_is( 'viredis-single', 'registered' ) ) {
			wp_register_script( 'viredis-single', VIREDIS_JS . 'frontend-single.' . $suffix . 'js', array( 'jquery' ), VIREDIS_VERSION, false );
			wp_localize_script( 'viredis-single', 'viredis_single', array(
				'wc_ajax_url'          => WC_AJAX::get_endpoint( "%%endpoint%%" ),
				'nonce'          => wp_create_nonce('viredis_nonce'),
				'pd_dynamic_price'     => VIREDIS_Frontend_Product::$pd_dynamic_price ? 1 : '',
				'product_content_wrap' => apply_filters( 'viredis_product_content_wrap', '.summary' ),
			) );
		}
		if ( VIREDIS_Frontend_Product::$pd_dynamic_price ) {
			if ( ! wp_style_is( 'viredis-single' ) ) {
				wp_enqueue_style( 'viredis-single' );
			}
			if ( ! wp_script_is( 'viredis-single' ) ) {
				wp_enqueue_script( 'viredis-single' );
			}
		}
	}
	public static function viredis_get_dynamic_price_html() {
		$result     = array(
			'status'     => 'error',
			'price_html' => '',
		);
		if (!check_ajax_referer('viredis_nonce','viredis_nonce', false)){
			$result['message'] = 'miss role';
			wp_send_json($result);
		}
		$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';
		$qty        = isset( $_POST['product_id'] ) ? (float) sanitize_text_field( $_POST['qty'] ) : 0;
		if ( ! $product_id || ! $qty ) {
			$result['message'] = 'not found product or qty';
			wp_send_json( $result );
		}
		$product       = wc_get_product( $product_id );
		$price         = $product->get_price();
		$current_price = self::get_price( $price, $product, $qty );
		if ( $price == $current_price && isset(self::$cache['dynamic_price_html'][$product_id][$price]) ) {
			$result['message'] = 'not change';
			wp_send_json( $result );
		}
		if ( !isset(self::$cache['dynamic_price_html']) ) {
			self::$cache['dynamic_price_html']=[];
		}
		if ( !isset(self::$cache['dynamic_price_html'][$product_id]) ) {
			self::$cache['dynamic_price_html'][$product_id]=[];
		}
		$current_price_html   = VIREDIS_Frontend_Product::get_price_html( $current_price, $price, VIREDIS_Frontend_Product::$cache['display_prices_type']['product'] ?? 0, $product );
		$current_price_html   .= $product->get_price_suffix();
		$price_html           = apply_filters( 'viredis_get_current_price_html', $current_price_html, $product_id, $qty, $product );
		$result['status']     = 'success';
		$result['price_html'] = self::$cache['dynamic_price_html'][$product_id][$price] = $price_html;
		wp_send_json( $result );
	}
	public static function variation_get_pricing_table_html( $variation_data, $product, $variation ) {
		ob_start();
		self::get_pricing_table_html( $variation );
		$html                                    = ob_get_clean();
		$variation_data['viredis_pricing_table'] = $html ?: sprintf( '<div class="viredis-pricing-table-wrap viredis-hidden"></div>' );
		return $variation_data;
	}
	public static function get_pricing_table_html( $product = null ) {
		if ( ! $product ) {
			global $product;
		}
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}
		if ( ! wp_style_is( 'viredis-single' ) ) {
			wp_enqueue_style( 'viredis-single' );
		}
		if ( ! wp_script_is( 'viredis-single' ) ) {
			wp_enqueue_script( 'viredis-single' );
		}
		switch ( $product->get_type() ) {
			case 'grouped':
				break;
			case 'variable':
				printf( '<div class="viredis-pricing-table-wrap viredis-hidden"></div>' );
				break;
			default:
				$product_id = $product->get_id();
				if ( ! isset( self::$cache['rules'][ $product_id ] ) ) {
					self::$cache['rules'][ $product_id ] = VIREDIS_Frontend_Product::get_rules( $product_id, $product );
				}
				$rules = self::$cache['rules'][ $product_id ];
				if ( empty( $rules ) ) {
					break;
				}
				$table_rules = array();
				foreach ( $rules as $rule_id => $params ) {
					$type = $params['prices']['type'] ?? '';
					if ( $type === 'bulk_qty' && ! empty( $params['prices']['name'] ) && ! empty( $params['prices']['bulk_qty_range']['from'] ) ) {
						$table_rules[ $rule_id ] = array(
							'title'  => $params['prices']['name'],
							'ranges' => $params['prices']['bulk_qty_range']
						);
					}
				}
				if ( empty( $table_rules ) ) {
					break;
				}
				if ( isset( self::$cache['old_prices'][ $product_id ] ) ) {
					$price = self::$cache['old_prices'][ $product_id ];
				} else {
					$price = $product->get_price();
				}
				wc_get_template( 'viredis-pricing-table-html.php',
					array(
						'price'         => $price,
						'rules'         => $table_rules,
						'discount_type' => VIREDIS_Frontend_Product::$settings->get_params( 'pd_pricing_table_discount_value' ),
						'table_title'   => VIREDIS_Frontend_Product::$settings->get_params( 'pd_pricing_table_title' ),
						'product'       => $product
					),
					'redis-woo-dynamic-pricing-and-discounts' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR,
					VIREDIS_TEMPLATES );
		}
	}
	public static function product_is_on_sale( $on_sale, $product ) {
//		if ( ! $product || ! is_a( $product, 'WC_Product' ) || isset( $product->viredis_cart_item ) ) {
		if ( ! $product || ! is_a( $product, 'WC_Product' ) || $product->get_meta('viredis_cart_item') ) {
			return $on_sale;
		}
		$product_id      = $product->get_id();
		$is_product_page = VIREDIS_Frontend_Product::$cache['is_product_page'] ?? 0;
		$parent_id       = wp_get_post_parent_id( $product_id ) ?: 0;
		if ( $product_id != $is_product_page && ( ! $parent_id || $parent_id != $is_product_page ) && empty( VIREDIS_Frontend_Product::$cache['is_product_list'] ) ) {
			return $on_sale;
		}
		if ( isset( self::$cache['on_sale'][ $product_id ] ) ) {
			return self::$cache['on_sale'][ $product_id ];
		}
		if ( isset( self::$cache[ 'check-on-sale-' . $product_id ] ) ) {
			return $on_sale;
		}
		self::$cache[ 'check-on-sale-' . $product_id ] = true;
		if ( self::may_be_apply_to_product( $product ) ) {
			if ( isset( self::$cache['old_prices'][ $product_id ] ) ) {
				$price = self::$cache['old_prices'][ $product_id ];
			} else {
				$price = $product->get_price();
			}
			$current_price = self::$cache['prices'][ $product_id ][ $price ] ?? self::get_price( $price, $product, self::get_dynamic_qty( $product_id, $product ) );
			if ( $current_price != $price ) {
				$on_sale = true;
			}
		} elseif ( $product->get_type() === 'variable' ) {
			self::get_variable_price( $product_id, $product );
			if ( ! empty( self::$cache['change_display_price'][ $product_id ] ) ) {
				$on_sale = true;
			}
		}
		self::$cache['on_sale'][ $product_id ] = apply_filters( 'viredis_woocommerce_product_is_on_sale', $on_sale, $product );
		unset( self::$cache[ 'check-on-sale-' . $product_id ] );
		return $on_sale;
	}
	public static function get_variable_price( $product_id, $product ) {
//		if ( ! $product_id || ! $product || ! is_a( $product, 'WC_Product' ) || isset( $product->viredis_cart_item ) ) {
		if ( ! $product_id || ! $product || ! is_a( $product, 'WC_Product' ) || $product->get_meta('viredis_cart_item') ) {
			return;
		}
		if ( isset( self::$cache['variation_prices'][ $product_id ]['old'] ) ) {
			$variation_prices = self::$cache['variation_prices'][ $product_id ]['old'];
		} else {
			$old_variation_prices = $product->get_variation_prices( true );
			if ( apply_filters( 'viredis_variation_prices_format_decimal', class_exists( 'WOOMULTI_CURRENCY' ) ) ) {
				$variation_prices = array();
				$price_decimal    = wc_get_price_decimals();
				foreach ( $old_variation_prices as $price_type => $price_arr ) {
					if ( $price_type !== 'price' ) {
						$variation_prices[ $price_type ] = $price_arr;
						continue;
					}
					$temp = array();
					foreach ( $price_arr as $key => $value ) {
						$temp[ $key ] = floatval( $value ? wc_format_decimal( $value, $price_decimal ) : $value );
					}
					$variation_prices[ $price_type ] = $temp;
				}
			} else {
				$variation_prices = $old_variation_prices;
			}
			self::$cache['variation_prices'][ $product_id ]['old'] = $variation_prices;
		}
		$old_prices = $variation_prices['price'] ?? array();
		if ( empty( $old_prices ) ) {
			return;
		}
		if ( isset( self::$cache['variation_prices'][ $product_id ]['new'] ) ) {
			$prices = self::$cache['variation_prices'][ $product_id ]['new'];
		} else {
			$prices = array();
			foreach ( $old_prices as $variation_id => $variation_price ) {
				if ( ! $variation_id ) {
					continue;
				}
				$variation = wc_get_product( $variation_id );
				if ( isset( self::$cache['old_prices'][ $variation_id ] ) ) {
					$price = self::$cache['old_prices'][ $variation_id ];
				} else {
					$price = $variation->get_price();
				}
				$prices[ $variation_id ] = self::$cache['prices'][ $variation_id ][ $price ] ?? (float) VIREDIS_Frontend_Product::product_get_price_tax( $variation, self::get_price( $price, $variation, 1 ), 1 );
			}
			asort( $prices );
			self::$cache['variation_prices'][ $product_id ]['new'] = $prices;
		}
		if ( empty( $prices ) ) {
			return;
		}
		if ( ! isset( self::$cache['change_display_price'][ $product_id ] ) && $prices != $old_prices ) {
			self::$cache['change_display_price'][ $product_id ] = 1;
		}
		if ( isset( self::$cache[ 'check-on-sale-' . $product_id ] ) ) {
			return;
		}
		$min_price     = current( $prices );
		$max_price     = end( $prices );
		$old_min_price = current( $old_prices );
		$old_max_price = end( $old_prices );
		$regular_price = $variation_prices['regular_price'] ?? array();
		if ( empty( $regular_price ) ) {
			return;
		}
		$min_reg_price = current( $regular_price );
		$max_reg_price = end( $regular_price );
		if ( $old_min_price != $min_price || $old_max_price != $max_price ) {
			$display_type = VIREDIS_Frontend_Product::$cache['display_prices_type']['product'] ?? 0;
			if ( $min_price !== $max_price ) {
				$price_html = wc_format_price_range( $min_price, $max_price );
			} elseif ( ! empty( self::$cache['change_display_price'][ $product_id ] ) && $min_reg_price === $max_reg_price ) {
				$price_html = wc_format_sale_price( wc_price( VIREDIS_Frontend_Product::$pd_base_price_type === 'sale' ? $old_max_price : $max_reg_price ), wc_price( $min_price ) );
			} else {
				$price_html = wc_price( $min_price );
			}
			$old_price_html = self::$cache[ 'get-price-html-' . $product_id ] ?? $product->get_price_html();
			if ( $display_type && $display_type !== 'new_price' ) {
				$price_html = $min_reg_price === $max_reg_price ? $price_html : wc_format_sale_price( $old_price_html, $price_html );
			}
			self::$cache['price_html'][ $product_id ] = apply_filters( 'viredis_get_price_html', $price_html, $old_price_html, $product );
		}
	}
	public static function get_variable_price_html( $price_html, $product ) {
//		if ( ! $price_html || ! $product || ! is_a( $product, 'WC_Product' ) || isset( $product->viredis_cart_item ) ) {
		if ( ! $price_html || ! $product || ! is_a( $product, 'WC_Product' ) || $product->get_meta('viredis_cart_item') ) {
			return $price_html;
		}
		$product_id      = $product->get_id();
		$is_product_page = VIREDIS_Frontend_Product::$cache['is_product_page'] ?? 0;
		if ( $product_id != $is_product_page && empty( VIREDIS_Frontend_Product::$cache['is_product_list'] ) ) {
			return $price_html;
		}
		if ( isset( self::$cache['price_html'][ $product_id ] ) ) {
			return self::$cache['price_html'][ $product_id ];
		}
		if ( isset( self::$cache[ 'get-price-html-' . $product_id ] ) ) {
			return $price_html;
		}
		self::$cache[ 'get-price-html-' . $product_id ] = $price_html;
		self::get_variable_price( $product_id, $product );
		if ( isset( self::$cache['price_html'][ $product_id ] ) ) {
			$price_html = self::$cache['price_html'][ $product_id ];
		} else {
			self::$cache['price_html'][ $product_id ] = $price_html;
		}
		unset( self::$cache[ 'get-price-html-' . $product_id ] );
		return $price_html;
	}
	public static function get_price_html( $price_html, $product ) {
//		if ( ! $price_html || ! $product || ! is_a( $product, 'WC_Product' ) || isset( $product->viredis_cart_item ) || ! self::may_be_apply_to_product( $product ) ) {
		if ( ! $price_html || ! $product || ! is_a( $product, 'WC_Product' ) || $product->get_meta('viredis_cart_item') || ! self::may_be_apply_to_product( $product ) ) {
			return $price_html;
		}
		$product_id      = $product->get_id();
		$is_product_page = VIREDIS_Frontend_Product::$cache['is_product_page'] ?? 0;
		$parent_id       = wp_get_post_parent_id( $product_id ) ?: 0;
		if ( $product_id != $is_product_page && ( ! $parent_id || $parent_id != $is_product_page ) && empty( VIREDIS_Frontend_Product::$cache['is_product_list'] ) ) {
			return $price_html;
		}
		if ( isset( self::$cache['price_html'][ $product_id ] ) ) {
			return self::$cache['price_html'][ $product_id ];
		}
		if ( isset( self::$cache[ 'get-price-html-' . $product_id ] ) ) {
			return $price_html;
		}
		if ( isset( self::$cache['old_prices'][ $product_id ] ) ) {
			$price = self::$cache['old_prices'][ $product_id ];
		} else {
			$price = $product->get_price();
		}
		self::$cache[ 'get-price-html-' . $product_id ] = true;
		$current_price                                  = self::$cache['prices'][ $product_id ][ $price ] ?? self::get_price( $price, $product, self::get_dynamic_qty( $product_id, $product ) );
		if ( $price == $current_price ) {
			$current_price_html = $price_html;
		} else {
			$current_price_html = VIREDIS_Frontend_Product::get_price_html( $current_price, $price, VIREDIS_Frontend_Product::$cache['display_prices_type']['product'] ?? 0, $product );
			$current_price_html .= $product->get_price_suffix();
		}
		$price_html = self::$cache['price_html'][ $product_id ] = apply_filters( 'viredis_get_price_html', $current_price_html, $price_html, $product );
		unset( self::$cache[ 'get-price-html-' . $product_id ] );
		return $price_html;
	}
	public static function get_dynamic_qty( $product_id, $product ) {
		if ( ! $product_id || ! VIREDIS_Frontend_Product::$pd_dynamic_price ) {
			return 0;
		}
		$is_product_page = VIREDIS_Frontend_Product::$cache['is_product_page'] ?? 0;
		if ( ! $is_product_page ) {
			return 0;
		}
		$parent_id = wp_get_post_parent_id( $product_id ) ?: 0;
		if ( $product_id != $is_product_page && $parent_id != $is_product_page ) {
			return 0;
		}

		if ( isset( $_REQUEST['viredis_nonce'] ) && ! wp_verify_nonce( wc_clean( $_REQUEST['viredis_nonce'] ), 'viredis_nonce' ) ) {
			return 0;
		}

		return apply_filters( 'viredis_get_dynamic_qty', isset( $_POST['quantity'] ) ? wc_stock_amount( wc_clean(wp_unslash( $_POST['quantity'] )) ) : $product->get_min_purchase_quantity(), $product_id );
	}
	public static function get_price( $price, $product, $product_qty = 0 ) {
		if ( ! $price || ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return $price;
		}
		$product_id = $product->get_id();
		if ( isset( self::$cache['prices'][ $product_id ][ $price ] ) ) {
			return self::$cache['prices'][ $product_id ][ $price ];
		}
		if ( isset( self::$cache[ 'get-price-' . $product_id ] ) ) {
			return $price;
		}
		self::$cache[ 'get-price-' . $product_id ] = true;
		self::$cache['old_prices'][ $product_id ]  = $price;
		if ( ! isset( self::$cache['rules'][ $product_id ] ) ) {
			self::$cache['rules'][ $product_id ] = VIREDIS_Frontend_Product::get_rules( $product_id, $product, $product_qty );
		}
		$rules = self::$cache['rules'][ $product_id ];
		if ( empty( $rules ) ) {
			return self::$cache['prices'][ $product_id ][ $price ] = $price;
		}
		$current_price                                  = VIREDIS_Frontend_Product::get_current_prices( $price, $product_id, $product, $rules, $product_qty );
		self::$cache['prices'][ $product_id ][ $price ] = $current_price;
		unset( self::$cache[ 'get-price-' . $product_id ] );
		return self::$cache['prices'][ $product_id ][ $price ];
	}
	public static function may_be_apply_to_product( $product ) {
		if ( ! $product || ! is_a( $product, 'WC_Product' ) || in_array( $product->get_type() ?? '', apply_filters( 'viredis_get_price_without_product_type', [ 'variable', 'grouped' ] ) ) ) {
			return false;
		}
		return true;
	}
}