<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Frontend_Product {
	public static $settings, $cache = array();
	public static $pd_display_price, $pd_maximum_discount, $pd_base_price_type, $pd_dynamic_price;
	public function __construct() {
		self::$settings = VIREDIS_DATA::get_instance();
		if ( ! self::$settings->get_params( 'pd_enable' ) ) {
			return;
		}
		$enable = false;
		$rule_active = self::$settings->get_params('pd_active');
		if (is_array($rule_active) && !empty($rule_active)){
			foreach ($rule_active as $status){
				if (!empty($status)){
					$enable = true;
					break;
				}
			}
		}
//		if (!$enable){
//			return;
//		}
		self::$pd_display_price                        = self::$settings->get_params( 'pd_display_price' );
		self::$cache['display_prices_type']['cart']    = self::$settings->get_params( 'pd_cart_display_price' );
		self::$cache['display_prices_type']['product'] = self::$pd_display_price;
		self::$cache['pd_on_sale_badge']               = self::$settings->get_params( 'pd_on_sale_badge' );
		self::$pd_base_price_type                      = self::$settings->get_params( 'pd_price_type' ) ?: 'sale';
		if ( self::$settings->get_params( 'pd_limit_discount' ) ) {
			self::$pd_maximum_discount = array(
				'type'  => self::$settings->get_params( 'pd_limit_discount_type' ),
				'value' => self::$settings->get_params( 'pd_limit_discount_value' )
			);
		}
		self::$pd_dynamic_price = self::$pd_display_price && self::$settings->get_params( 'pd_change_price_on_single' ) && self::$settings->get_params( 'pd_dynamic_price' );
		// set new price of cart item
		add_filter( 'woocommerce_add_cart_item', array( 'VIREDIS_Frontend_Product_Pricing_Cart', 'viredis_mark_as_cart_item' ), PHP_INT_MAX, 1 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( 'VIREDIS_Frontend_Product_Pricing_Cart', 'viredis_mark_as_cart_item' ), PHP_INT_MAX, 1 );
		add_filter( 'woocommerce_product_get_price', array( 'VIREDIS_Frontend_Product_Pricing_Cart', 'viredis_woocommerce_get_price' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( 'VIREDIS_Frontend_Product_Pricing_Cart', 'viredis_woocommerce_get_price' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_cart_item_price', array( 'VIREDIS_Frontend_Product_Pricing_Cart', 'viredis_cart_item_price' ), PHP_INT_MAX, 3 );
		add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'viredis_remove_session' ), PHP_INT_MAX, 2 );
		// set new price html of product
		if ( self::$pd_display_price ) {
			add_filter( 'wmc_frontend_extra_params', array( $this, 'wmc_frontend_extra_params' ),10,1 );
			add_action( 'wmc_get_products_price_ajax_handle_before', array( $this, 'wmc_get_products_price_ajax_handle_before' ) );
			add_action( 'wp', array( $this, 'display_price_on_this_page' ) );
			add_filter( 'woocommerce_get_price_html', array( 'VIREDIS_Frontend_Product_Pricing_Store', 'get_price_html' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_variable_price_html', array( 'VIREDIS_Frontend_Product_Pricing_Store', 'get_variable_price_html' ), PHP_INT_MAX, 2 );
			if ( self::$cache['pd_on_sale_badge'] && in_array( self::$cache['display_prices_type']['product'], [ 'base_price', 'regular_price' ] ) ) {
				add_filter( 'woocommerce_product_is_on_sale', array( 'VIREDIS_Frontend_Product_Pricing_Store', 'product_is_on_sale' ), PHP_INT_MAX, 2 );
			}
		}
		if ( self::$settings->get_params( 'pd_pricing_table' ) ) {
			$positions      = array(
				'before_atc'    => 'woocommerce_before_add_to_cart_form',
				'after_atc'     => 'woocommerce_after_add_to_cart_form',
				'before_meta'   => 'woocommerce_product_meta_start',
				'after_meta'    => 'woocommerce_product_meta_end',
				'after_summary' => 'woocommerce_after_single_product_summary',
			);
			$table_position = self::$settings->get_params( 'pd_pricing_table_position' );
			$table_hook     = apply_filters( 'viredis_pricing_table_position', $positions[ $table_position ] ?? 'woocommerce_before_add_to_cart_form' );
			add_action( $table_hook, array( 'VIREDIS_Frontend_Product_Pricing_Store', 'get_pricing_table_html' ) );
			add_filter( 'woocommerce_available_variation', array( 'VIREDIS_Frontend_Product_Pricing_Store', 'variation_get_pricing_table_html' ), 10, 3 );
			add_action( 'wp_enqueue_scripts', array( 'VIREDIS_Frontend_Product_Pricing_Store', 'enqueue_scripts' ) );
		}
		if ( self::$pd_dynamic_price ) {
			add_action( 'wp_enqueue_scripts', array( 'VIREDIS_Frontend_Product_Pricing_Store', 'enqueue_scripts' ) );
			$ajax_events = array(
				'viredis_get_dynamic_price_html' => array(
					'nopriv' => true,
					'class'  => 'VIREDIS_Frontend_Product_Pricing_Store',
				),
			);
			self::add_ajax_events( $ajax_events );
		}
	}
	public static function add_ajax_events( $ajax_events = array() ) {
		foreach ( $ajax_events as $ajax_event => $params ) {
			$nopriv = $params['nopriv'] ?? '';
			$class  = $params['class'] ?? __CLASS__;
			add_action( 'wp_ajax_' . $ajax_event, array( $class, $ajax_event ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_' . $ajax_event, array( $class, $ajax_event ) );
				// WC AJAX can be used for frontend ajax requests
				add_action( 'wc_ajax_' . $ajax_event, array( $class, $ajax_event ) );
			}
		}
	}

	public static function get_price_html( $price, $old_price, $type, $product ) {
		if ( ! $product || ! is_a( $product, 'WC_Product' ) || ( $price == $old_price ) ) {
			return wc_price( self::product_get_price_tax( $product, $old_price, 1 ) );
		}
		switch ( $type ) {
			case 'base_price':
				$price_html = wc_format_sale_price( wc_price( self::product_get_price_tax( $product, $old_price, 1 ) ), wc_price( self::product_get_price_tax( $product, $price, 1 ) ) );
				break;
			case 'new_price':
				$price_html = wc_price( self::product_get_price_tax( $product, $old_price, 1 ) );
				break;
			case 'regular_price':
				$price_html = wc_format_sale_price( wc_price( self::product_get_price_tax( $product, $product->get_regular_price(), 1 ) ), wc_price( self::product_get_price_tax( $product, $price, 1 ) ) );
				break;
		}
		return $price_html ?? wc_price( self::product_get_price_tax( $product, $old_price, 1 ) );
	}

	public static function get_qty_in_cart( $type = 'all', $product_id = 0, $product_qty = 0 ) {
		if ( ! isset( WC()->cart ) || WC()->cart->is_empty() ) {
			return $product_qty;
		}
		$wc_cart = WC()->cart;
		$result  = $product_qty;
		switch ( $type ) {
			case 'all':
				$result += $wc_cart->get_cart_contents_count();
				break;
			case 'product_category':
				if ( ! $product_id ) {
					break;
				}
				$cate_ids = wc_get_product_cat_ids( $product_id );
				foreach ( $wc_cart->get_cart() as $cart_item ) {
					$product_id_t = $cart_item['product_id'];
					$cate_ids_t   = wc_get_product_cat_ids( $product_id_t );
					if ( count( array_intersect( $cate_ids_t, $cate_ids ) ) ) {
						$result += (int) $cart_item['quantity'] ?? 0;
					}
				}
				break;
			case 'product':
				if ( ! $product_id ) {
					break;
				}
				foreach ( $wc_cart->get_cart() as $cart_item ) {
					$variation_id = $cart_item['variation_id'] ?? 0;
					$product_id_t = $cart_item['product_id'];
					if ( $product_id_t == $product_id || $product_id == $variation_id ) {
						$result += (int) $cart_item['quantity'];
					}
				}
				break;
		}
		return $result;
	}

	public static function get_fixed_bulk_discount_value( $base_price, $product_id, $bulk_qty_base, $bulk_qty_range = array(), $product_qty = 0 ) {
		if ( ! $base_price || ! $product_id || empty( $bulk_qty_range ) ) {
			return 0;
		}
		$qty_from = $bulk_qty_range['from'] ?? array();
		if ( empty( $qty_from ) ) {
			return 0;
		}
		$qty_in_cart = apply_filters('viredis_get_product_qty_in_cart',self::get_qty_in_cart( $bulk_qty_base, $product_id, $product_qty ), $product_id, $product_qty);
		if ( ! $qty_in_cart ) {
			return 0;
		}
		$discount_type = $discount_value = '';
		$index         = $pre = null;
		foreach ( $qty_from as $i => $from ) {
			$from = (int) $from;
			$to   = $bulk_qty_range['to'][ $i ] ?? '';
			if ( $from && $from > $qty_in_cart ) {
				continue;
			}
			if ( is_numeric( $to ) && $to < $qty_in_cart ) {
				$pre = $i;
				continue;
			}
			$index = $i;
			break;
		}
		if ( $index !== null ) {
			$discount_type  = $bulk_qty_range['type'][ $index ] ?? 0;
			$discount_value = $bulk_qty_range['price'][ $index ] ?? 0;
		}
		return self::get_fixed_discount_value( $discount_type, $discount_value, $base_price );
	}
	public static function get_fixed_discount_value( $type, $discount, $base_price ) {
		if ( ! $discount || ! $base_price ) {
			return 0;
		}
		if ( $type ) {
			$discount = apply_filters( 'viredis_change_3rd_plugin_price', $discount );
		} else {
			$discount = $discount * $base_price / 100;
		}
		return (float) $discount;
	}
	public static function get_current_prices( $price, $product_id, $product, $rules = array(), $product_qty = 0 ) {
		if ( ! $price || ! $product_id || ! $product || ! is_a( $product, 'WC_Product' ) || empty( $rules ) ) {
			return $price;
		}
		$current_discounts = array();
		$base_price        = self::$pd_base_price_type === 'sale' ? $price : $product->get_regular_price();
		$base_price        = apply_filters( 'viredis_get_base_price', $base_price, $price, $product, self::$pd_base_price_type );
		foreach ( $rules as $rule_id => $params ) {
			$prices_info = $params['prices'] ?? array();
			if ( empty( $prices_info ) ) {
				continue;
			}
			$type = $prices_info['type'] ?? 'basic';
			if ( $type === 'basic' ) {
				$basic_type     = $prices_info['basic_type'] ?? 0;
				$basic_value    = $prices_info['basic_price'] ?? 0;
				$discount_value = self::get_fixed_discount_value( $basic_type, $basic_value, $base_price );
			} else {
				$bulk_qty_base  = $prices_info['bulk_qty_base'] ?? 'all';
				$bulk_qty_range = $prices_info['bulk_qty_range'] ?? array();
				$discount_value = self::get_fixed_bulk_discount_value( $base_price, $product_id, $bulk_qty_base, $bulk_qty_range, $product_qty );
			}
			$current_discounts[ $rule_id ] = apply_filters( 'viredis_get_discount_value', $discount_value, $price, $product, $product_qty, $rule_id, $rules );
		}
		self::$cache['current_discounts'][ $product->viredis_cart_item ?? $product_id ][ $price ] = $current_discounts;
		if ( empty( $current_discounts ) ) {
			return $price;
		}
		$current_discount = array_sum( $current_discounts );
		$maximum_discount = ! empty( self::$pd_maximum_discount['value'] ) ? (float) self::$pd_maximum_discount['value'] : '';
		if ( is_numeric( $maximum_discount ) ) {
			$maximum_discount_type = self::$pd_maximum_discount['type'] ?? 1;
			$maximum_discount      = apply_filters( 'viredis_get_maximum_discount_value', self::get_fixed_discount_value( $maximum_discount_type, $maximum_discount, $price ), self::$pd_maximum_discount, $price, $product );
			$current_discount      = $current_discount > $maximum_discount ? $maximum_discount : $current_discount;
		}
		$price_decimal = wc_get_price_decimals();
		$current_price = $base_price > $current_discount ? $base_price - $current_discount : 0;
		$current_price = apply_filters( 'viredis_get_price', $current_price ? wc_format_decimal( $current_price, $price_decimal ) : $current_price, $price, $product, $rules, $product_qty );
		return apply_filters('viredis_get_current_price',$current_price,$price,$product_id,$product,$rules, $product_qty);
	}

	public static function may_be_apply_to_cart( $rule_id, $conditions, $product = null, $product_id = 0, $product_qty = 0, $is_cart = false ) {
		if ( has_filter( 'viredis_may_be_apply_to_cart' ) ) {
			$check =  apply_filters('viredis_may_be_apply_to_cart', 'check',$rule_id, $conditions, $product, $product_id, $product_qty, $is_cart );
			if ($check !== 'check'){
				return $check;
			}
		}
		if ( empty( $conditions ) ) {
			return true;
		}
		if ( ! $is_cart && ( ! $rule_id || ! $product_id || ! $product || ! is_a( $product, 'WC_Product' ) ) ) {
			return false;
		}
		$wc_cart       = WC()->cart;
		$wc_cart_empty = ! $wc_cart || $wc_cart->is_empty();
		$result        = true;
		$wc_cart_data  = $wc_cart_empty ? array() : $wc_cart->get_cart();
		foreach ( $conditions as $type => $params ) {
			switch ( $type ) {
				case 'cart_subtotal':
					$subtotal_min = $params['subtotal_min'] ?? 0;
					$subtotal_min = apply_filters( 'viredis_condition_get_cart_subtotal_min', $subtotal_min ? apply_filters( 'viredis_change_3rd_plugin_price', (float) $subtotal_min ) : 0, $rule_id, $params );
					$subtotal_max = $params['subtotal_max'] ?? '';
					$subtotal_max = apply_filters( 'viredis_condition_get_cart_subtotal_max', $subtotal_max ? apply_filters( 'viredis_change_3rd_plugin_price', (float) $subtotal_max ) : '', $rule_id, $params );
					if ( ! $subtotal_min && ! $subtotal_max ) {
						break;
					}
					if ( $wc_cart_empty && ! $product_qty ) {
						$result = false;
						break;
					}
					if ( $is_cart ) {
						if ( ! isset( self::$cache['cart_subtotal']['cart'] ) ) {
							self::$cache['cart_subtotal']['cart'] = apply_filters( 'viredis_condition_get_cart_subtotal', self::get_cart_subtotal( $wc_cart, $wc_cart_empty ), true );
						}
						$wc_cart_subtotal = self::$cache['cart_subtotal']['cart'] ?: 0;
					} else {
						if ( ! isset( self::$cache['cart_subtotal']['product'] ) ) {
							self::$cache['cart_subtotal']['product'] = apply_filters( 'viredis_condition_get_cart_subtotal', self::get_cart_subtotal( $wc_cart, $wc_cart_empty ), false );
						}
						$wc_cart_subtotal = self::$cache['cart_subtotal']['product'] ?: 0;
					}
					$wc_cart_subtotal = $wc_cart_subtotal ? (float) $wc_cart_subtotal : 0;
					$wc_cart_subtotal += $product_qty ? (float) apply_filters( 'viredis_condition_get_cart_product_price', self::product_get_price_tax( $product, $product->get_price(), $product_qty ), $product_id, $product_qty, $product, $rule_id, $conditions ) : 0;
					if ( $subtotal_min && $subtotal_min > $wc_cart_subtotal ) {
						$result = false;
						break;
					}
					if ( is_numeric( $subtotal_max ) && $subtotal_max < $wc_cart_subtotal ) {
						$result = false;
					}
					break;
				case 'qty_item':
					$qty_item_min = $params['qty_item_min'] ?? 0;
					$qty_item_min = $qty_item_min ? (int) $qty_item_min : 0;
					$qty_item_max = $params['qty_item_max'] ?? '';
					$qty_item_max = $qty_item_max ? (int) $qty_item_max : '';
					if ( ! $qty_item_min && ! $qty_item_max ) {
						break;
					}
					if ( $wc_cart_empty && ! $product_qty ) {
						$result = false;
						break;
					}
					$wc_cart_qty_item = ( $wc_cart_empty ? 0 : $wc_cart->get_cart_contents_count() ) + $product_qty;
					if ( $qty_item_min && $qty_item_min > $wc_cart_qty_item ) {
						$result = false;
						break;
					}
					if ( $qty_item_max && $qty_item_max < $wc_cart_qty_item ) {
						$result = false;
					}
					break;
				case 'item_include':
					if ( is_array( $params ) && count( $params ) ) {
						$result = false;
						if ( $product_id && in_array( $product_id, $params ) ) {
							$result = true;
							break;
						}
						if ( $wc_cart_empty ) {
							break;
						}
						foreach ( $wc_cart_data as $cart_item ) {
							$product_id = $cart_item['product_id'] ?? 0;
							if ( $product_id && in_array( $product_id, $params ) ) {
								$result = true;
								break;
							}
							$variation_id = $cart_item['variation_id'] ?? 0;
							if ( $variation_id && in_array( $variation_id, $params ) ) {
								$result = true;
								break;
							}
						}
					}
					break;
				case 'item_exclude':
					if ( is_array( $params ) && count( $params ) ) {
						if ( $product_id && in_array( $product_id, $params ) ) {
							$result = false;
							break;
						}
						if ( $wc_cart_empty ) {
							break;
						}
						foreach ( $wc_cart_data as $cart_item ) {
							$product_id_t = $cart_item['product_id'] ?? 0;
							if ( $product_id_t && in_array( $product_id, $params ) ) {
								$result = false;
								break;
							}
							$variation_id = $cart_item['variation_id'] ?? 0;
							if ( $variation_id && in_array( $variation_id, $params ) ) {
								$result = false;
								break;
							}
						}
					}
					break;
				case 'cats_include':
					if ( is_array( $params ) && count( $params ) ) {
						$result = false;
						if ( ! $is_cart && $product_id ) {
							$cats_id = wc_get_product_cat_ids( $product_id );
							if ( is_array( $cats_id ) && count( $cats_id ) && count( array_intersect( $cats_id, $params ) ) ) {
								$result = true;
								break;
							}
						}
						if ( $wc_cart_empty ) {
							break;
						}
						foreach ( $wc_cart_data as $cart_item ) {
							$product_id_t = $cart_item['product_id'];
							$cats_id      = wc_get_product_cat_ids( $product_id_t );
							if ( is_array( $cats_id ) && count( $cats_id ) && count( array_intersect( $cats_id, $params ) ) ) {
								$result = true;
								break;
							}
						}
					}
					break;
				case 'cats_exclude':
					if ( is_array( $params ) && count( $params ) ) {
						if ( ! $is_cart && $product_id ) {
							$cats_id = wc_get_product_cat_ids( $product_id );
							if ( is_array( $cats_id ) && count( $cats_id ) && count( array_intersect( $cats_id, $params ) ) ) {
								$result = false;
								break;
							}
						}
						if ( $wc_cart_empty ) {
							break;
						}
						foreach ( $wc_cart_data as $cart_item ) {
							$product_id_t = $cart_item['product_id'];
							$cats_id      = wc_get_product_cat_ids( $product_id_t );
							if ( is_array( $cats_id ) && count( $cats_id ) && count( array_intersect( $cats_id, $params ) ) ) {
								$result = false;
								break;
							}
						}
					}
					break;
				case 'tag_include':
					if ( is_array( $params ) && count( $params ) ) {
						$result = false;
						if ( ! $is_cart && $product_id ) {
							$tags = get_the_terms( $product_id, 'product_tag' );
							if ( ! empty( $tags ) ) {
								$tags_id = array();
								foreach ( $tags as $tag ) {
									$tags_id[] = $tag->term_id;
								}
								if ( ! empty( $tags_id ) && count( array_intersect( $tags_id, $params ) ) ) {
									$result = true;
									break;
								}
							}
						}
						if ( $wc_cart_empty ) {
							break;
						}
						foreach ( $wc_cart_data as $cart_item ) {
							$product_id_t = $cart_item['product_id'];
							$tags         = get_the_terms( $product_id_t, 'product_tag' );
							if ( empty( $tags ) ) {
								continue;
							}
							$tags_id = array();
							foreach ( $tags as $tag ) {
								$tags_id[] = $tag->term_id;
							}
							if ( ! empty( $tags_id ) && count( array_intersect( $tags_id, $params ) ) ) {
								$result = true;
								break;
							}
						}
					}
					break;
				case 'tag_exclude':
					if ( is_array( $params ) && count( $params ) ) {
						if ( ! $is_cart && $product_id ) {
							$tags = get_the_terms( $product_id, 'product_tag' );
							if ( ! empty( $tags ) ) {
								$tags_id = array();
								foreach ( $tags as $tag ) {
									$tags_id[] = $tag->term_id;
								}
								if ( ! empty( $tags_id ) && count( array_intersect( $tags_id, $params ) ) ) {
									$result = false;
									break;
								}
							}
						}
						if ( $wc_cart_empty ) {
							break;
						}
						foreach ( $wc_cart_data as $cart_item ) {
							$product_id_t = $cart_item['product_id'];
							$tags         = get_the_terms( $product_id_t, 'product_tag' );
							if ( empty( $tags ) ) {
								continue;
							}
							$tags_id = array();
							foreach ( $tags as $tag ) {
								$tags_id[] = $tag->term_id;
							}
							if ( ! empty( $tags_id ) && count( array_intersect( $tags_id, $params ) ) ) {
								$result = false;
								break;
							}
						}
					}
					break;
				case 'coupon_include':
					if ( is_array( $params ) && count( $params ) ) {
						if ( $wc_cart_empty ) {
							$result = false;
							break;
						}
						if ( empty( $coupons ) ) {
							$coupons = $wc_cart->get_applied_coupons();
						}
						if ( empty( $coupons ) ) {
							$result = false;
							break;
						}
						$coupons = array_map( 'strtolower', $coupons );
						if ( ! count( array_intersect( $coupons, $params ) ) ) {
							$result = false;
						}
					}
					break;
				case 'coupon_exclude':
					if ( is_array( $params ) && count( $params ) ) {
						if ( $wc_cart_empty ) {
							break;
						}
						if ( empty( $coupons ) ) {
							$coupons = $wc_cart->get_applied_coupons();
						}
						if ( empty( $coupons ) ) {
							break;
						}
						$coupons = array_map( 'strtolower', $coupons );
						if ( count( array_intersect( $coupons, $params ) ) ) {
							$result = false;
						}
					}
					break;
				case 'billing_country_include':
					if ( is_array( $params ) && count( $params ) ) {
						if ( empty( $billing_country ) ) {
							$wc_checkout     = $wc_checkout ?? WC_Checkout::instance();
							$billing_country = $wc_checkout->get_value( 'billing_country' );
						}
						if ( ! $billing_country || ! in_array( $billing_country, $params ) ) {
							$result = false;
						}
					}
					break;
				case 'billing_country_exclude':
					if ( is_array( $params ) && count( $params ) ) {
						if ( empty( $billing_country ) ) {
							$wc_checkout     = $wc_checkout ?? WC_Checkout::instance();
							$billing_country = $wc_checkout->get_value( 'billing_country' );
						}
						if ( $billing_country && in_array( $billing_country, $params ) ) {
							$result = false;
						}
					}
					break;
				case 'shipping_country_include':
					if ( is_array( $params ) && count( $params ) ) {
						if ( empty( $shipping_country ) ) {
							$wc_checkout      = $wc_checkout ?? WC_Checkout::instance();
							$shipping_country = $wc_checkout->get_value( 'shipping_country' );
						}
						if ( ! $shipping_country || ! in_array( $shipping_country, $params ) ) {
							$result = false;
						}
					}
					break;
				case 'shipping_country_exclude':
					if ( is_array( $params ) && count( $params ) ) {
						if ( empty( $shipping_country ) ) {
							$wc_checkout      = $wc_checkout ?? WC_Checkout::instance();
							$shipping_country = $wc_checkout->get_value( 'shipping_country' );
						}
						if ( $shipping_country && in_array( $shipping_country, $params ) ) {
							$result = false;
						}
					}
					break;
			}
			if ( ! $result ) {
				break;
			}
		}
		return $result;
	}
	public static function get_cart_subtotal( $wc_cart, $wc_cart_empty ) {
		if ( ! $wc_cart || $wc_cart_empty ) {
			return 0;
		}
		$result = 0;
		foreach ( $wc_cart->get_cart() as $cart_item ) {
			$result += (float) self::product_get_price_tax( $cart_item['data'], $cart_item['data']->get_price(), $cart_item['quantity'] ?? 1 );
		}
		return apply_filters( 'viredis_get_cart_subtotal', $result );
	}
	public function viredis_remove_session( $customer_id, $data ) {
		WC()->session->__unset( 'viredis_may_be_apply_to_user' );
		WC()->session->__unset( 'viredis_may_be_apply_to_user_cart' );
	}
	public static function may_be_apply_to_user( $rule_id, $conditions = array() , $is_cart=false) {
		if ( has_filter( 'viredis_may_be_apply_to_user' ) ) {
			$check =  apply_filters('viredis_may_be_apply_to_user', 'check',$rule_id, $conditions, $is_cart);
			if ($check !== 'check'){
				return $check;
			}
		}
		if ( ! $rule_id || !WC()->session ) {
			return false;
		}
		if ( empty( $conditions ) ) {
			return true;
		}
		if ( isset( self::$cache['may_be_apply_to_user'][ $rule_id ] ) ) {
			return self::$cache['may_be_apply_to_user'][ $rule_id ];
		}
		$session_name = 'viredis_may_be_apply_to_user'. $is_cart ?'_cart':'';
		$session_cache        = WC()->session->get($session_name , array() );
		$session_cache_prefix = $session_cache['prefix'] ?? '';
		$prefix               = VIREDIS_DATA::get_data_prefix($is_cart ? 'cart' :'');
		if ( $prefix && $session_cache_prefix !== $prefix ) {
			$session_cache = array( 'prefix' => $prefix );
		}
		if ( ! empty( $session_cache[ $rule_id ] ) && ( $session_cache[ $rule_id ]['conditions'] ?? array() === $conditions ) ) {
			return self::$cache['may_be_apply_to_user'][ $rule_id ] = $session_cache[ $rule_id ]['status'] ?? false;
		}
		$result = true;
		global $current_user;
		$order_status   = $conditions['order_status'] ?? '';
		$orders_check   = array();
		$is_logged_user = is_user_logged_in();
		foreach ( $conditions as $type => $params ) {
			switch ( $type ) {
				case 'logged':
					if ( $params && ! $is_logged_user ) {
						$result = false;
						break;
					}
					if ( ! $params && $is_logged_user ) {
						$result = false;
					}
					break;
				case 'user_role_include':
					if ( is_array( $params ) && count( $params ) && ! count( array_intersect( self::get_user_allcaps($current_user, $current_user->ID), $params ) ) ) {
						$result = false;
					}
					break;
				case 'user_role_exclude':
					if ( is_array( $params ) && count( $params ) && count( array_intersect(self::get_user_allcaps($current_user, $current_user->ID), $params ) ) ) {
						$result = false;
					}
					break;
				case 'user_include':
					if ( is_array( $params ) && count( $params ) && ! in_array( $current_user->ID, $params ) ) {
						$result = false;
					}
					break;
				case 'user_exclude':
					if ( is_array( $params ) && count( $params ) && in_array( $current_user->ID, $params ) ) {
						$result = false;
					}
					break;
				case 'order_status':
					if ( ! empty( $orders_check ) ) {
						break;
					}
					if ( $orders_check === false ) {
						$result = false;
						break;
					}
					$args           = array(
						'order_count',
						'order_total',
						'last_order',
						'product_include',
						'product_exclude',
						'cats_include',
						'cats_exclude',
					);
					$check_continue = false;
					foreach ( $args as $item ) {
						if ( isset( $conditions[ $item ] ) ) {
							$check_continue = true;
							break;
						}
					}
					if ( $check_continue ) {
						break;
					}
					if ( is_array( $orders_check ) && ! count( $orders_check ) ) {
						$orders_check  = self::get_order_query( $order_status, $current_user->ID );
					}
					if ( $orders_check === false ) {
						$result = false;
					}
					break;
				case 'order_count':
					$params_from = $params['from'] ?? array();
					if ( $params_from && is_array( $params_from ) && count( $params_from ) ) {
						foreach ( $params_from as $type_k => $type_v ) {
							$params_to = $params['to'][ $type_k ] ?? '';
							if ( $params_to && $type_v && strtotime( $type_v ) > strtotime( $params_to ) ) {
								continue;
							}
							$params_min = $params['min'][ $type_k ] ?? 0;
							$params_max = $params['max'][ $type_k ] ?? '';
							$params_min = floatval( $params_min ?: 0 );
							$params_max = $params_max ? floatval( $params_max ) : '';
							if ( $params_max === '' && ! $params_min ) {
								continue;
							}
							if ( is_numeric( $params_max ) && $params_max < $params_min ) {
								continue;
							}
							$tmp_orders = self::get_order_query( $order_status, $current_user->ID, $type_v, $params_to );
							if ( empty( $tmp_orders ) ) {
								$result = false;
								break;
							}
							$order_count = is_array($tmp_orders) ? count($tmp_orders):0;
							if ( $params_min && $params_min > $order_count ) {
								$result = false;
								break;
							}
							if ( is_numeric( $params_max ) && $params_max < $order_count ) {
								$result = false;
								break;
							}
						}
					}
					break;
				case 'order_total':
					$params_from = $params['from'] ?? array();
					if ( $params_from && is_array( $params_from ) && count( $params_from ) ) {
						foreach ( $params_from as $type_k => $type_v ) {
							$params_to = $params['to'][ $type_k ] ?? '';
							if ( $params_to && $type_v && strtotime( $type_v ) > strtotime( $params_to ) ) {
								continue;
							}
							$params_min = $params['min'][ $type_k ] ?? 0;
							$params_max = $params['max'][ $type_k ] ?? '';
							$params_min = floatval( $params_min ?: 0 );
							$params_max = $params_max ? floatval( $params_max ) : '';
							if ( $params_max === '' && ! $params_min ) {
								continue;
							}
							if ( is_numeric( $params_max ) && $params_max < $params_min ) {
								continue;
							}
							$tmp_orders = self::get_order_query( $order_status, $current_user->ID, $type_v, $params_to );
							if ( empty( $tmp_orders ) || !is_array($tmp_orders)) {
								$result = false;
								break;
							}
							$order_total = 0;
							foreach ($tmp_orders as $tmp_order){
								$order       = wc_get_order($tmp_order );
								$order_total += $order->get_total( 'edit' );
							}
							if ( $params_min && $params_min > $order_total ) {
								$result = false;
								break;
							}
							if ( is_numeric( $params_max ) && $params_max < $order_total ) {
								$result = false;
								break;
							}
						}
					}
					break;
				case 'last_order':
					$params_type = $params['type'] ?? '';
					$params_date = $params['date'] ?? '';
					if ( ! $params_type || ! $params_date ) {
						break;
					}
					if ( $orders_check === false ) {
						$result = false;
						break;
					}
					if ( $params_type === 'before' ) {
						$tmp_orders = self::get_order_query( $order_status, $current_user->ID, '', $params_date, '', '00:00:00' );
						if ( empty($tmp_orders) ) {
							$result = false;
							break;
						}
						$tmp_orders = self::get_order_query( $order_status, $current_user->ID, $params_date );
						if ( !empty($tmp_orders) ) {
							$result = false;
						}
					} else {
						$tmp_orders = self::get_order_query( $order_status, $current_user->ID, $params_date );
						if ( empty($tmp_orders) ) {
							$result = false;
						}
					}
					break;
				case 'product_include':
					if ( ! is_array( $params ) || ! count( $params ) ) {
						break;
					}
					if ( $orders_check === false ) {
						break;
					}
					if ( is_array( $orders_check ) && ! count( $orders_check ) ) {
						$orders_check = $orders = self::get_order_query( $order_status, $current_user->ID );
					} else {
						$orders = $orders_check;
					}
					$result = false;
					if ( is_array( $orders ) && count( $orders ) ) {
						foreach ( $orders as $order ) {
							$items = $order->get_items();
							if ( empty( $items ) ) {
								continue;
							}
							foreach ( $items as $item ) {
								$variation_id = $item->get_variation_id() ?? 0;
								if ( $variation_id && in_array( $variation_id, $params ) ) {
									$result = true;
									break;
								}
								$product_id = $item->get_product_id();
								if ( in_array( $product_id, $params ) ) {
									$result = true;
									break;
								}
							}
							if ( $result ) {
								break;
							}
						}
					}
					break;
				case 'product_exclude':
					if ( ! is_array( $params ) || ! count( $params ) ) {
						break;
					}
					if ( $orders_check === false ) {
						break;
					}
					if ( is_array( $orders_check ) && ! count( $orders_check ) ) {
						$orders_check = $orders = self::get_order_query( $order_status, $current_user->ID );
					} else {
						$orders = $orders_check;
					}
					if ( is_array( $orders ) && count( $orders ) ) {
						foreach ( $orders as $order ) {
							$items = $order->get_items();
							if ( empty( $items ) ) {
								continue;
							}
							foreach ( $items as $item ) {
								$variation_id = $item->get_variation_id() ?? 0;
								if ( $variation_id && in_array( $variation_id, $params ) ) {
									$result = false;
									break;
								}
								$product_id = $item->get_product_id();
								if ( in_array( $product_id, $params ) ) {
									$result = false;
									break;
								}
							}
							if ( ! $result ) {
								break;
							}
						}
					}
					break;
				case 'cats_include':
					if ( ! is_array( $params ) || ! count( $params ) ) {
						break;
					}
					if ( $orders_check === false ) {
						break;
					}
					if ( is_array( $orders_check ) && ! count( $orders_check ) ) {
						$orders_check = $orders = self::get_order_query( $order_status, $current_user->ID );
					} else {
						$orders = $orders_check;
					}
					$result = false;
					if ( is_array( $orders ) && count( $orders ) ) {
						foreach ( $orders as $order ) {
							$items = $order->get_items();
							if ( empty( $items ) ) {
								continue;
							}
							foreach ( $items as $item ) {
								$product_id = $item->get_product_id();
								$cate_ids   = wc_get_product_cat_ids( $product_id );
								if ( ! empty( $cate_ids ) && count( array_intersect( $cate_ids, $params ) ) ) {
									$result = true;
									break;
								}
							}
							if ( $result ) {
								break;
							}
						}
					}
					break;
				case 'cats_exclude':
					if ( ! is_array( $params ) || ! count( $params ) ) {
						break;
					}
					if ( $orders_check === false ) {
						break;
					}
					if ( is_array( $orders_check ) && ! count( $orders_check ) ) {
						$orders_check = $orders = self::get_order_query( $order_status, $current_user->ID );
					} else {
						$orders = $orders_check;
					}
					if ( is_array( $orders ) && count( $orders ) ) {
						foreach ( $orders as $order ) {
							$items = $order->get_items();
							if ( empty( $items ) ) {
								continue;
							}
							foreach ( $items as $item ) {
								$product_id = $item->get_product_id();
								$cate_ids   = wc_get_product_cat_ids( $product_id );
								if ( ! empty( $cate_ids ) && count( array_intersect( $cate_ids, $params ) ) ) {
									$result = false;
									break;
								}
							}
							if ( ! $result ) {
								break;
							}
						}
					}
					break;
			}
			if ( ! $result ) {
				break;
			}
		}
		$session_cache[ $rule_id ] = array(
			'conditions' => $conditions,
			'status'     => $result ? 1 : 0,
		);
		WC()->session->set( $session_name, $session_cache );
		return self::$cache['may_be_apply_to_user'][ $rule_id ] = $result;
	}
	public static function get_user_allcaps($user, $use_id){
		$result = array();
		if (isset(self::$cache['user_allcaps'][$use_id])){
			return self::$cache['user_allcaps'][$use_id];
		}
		if (!$user || !is_a($user, 'WP_User')){
			return $result;
		}
		$caps= $user->allcaps;
		foreach ($caps as $k => $v){
			if ($v){
				$result[]= $k;
			}
		}
		return self::$cache['user_allcaps'][$use_id] = $result;
	}
	public static function get_order_query( $order_status, $current_user_id, $start_date = '', $end_date = '', $start_time = '00:00:00', $end_time = '23:59:59' ) {
		$order_status = empty( $order_status ) ? array_keys( wc_get_order_statuses() ) : $order_status;
		$order_query          = array(
			'post_status'    => $order_status,
			'posts_per_page' => - 1,
			'orderby'        => 'date',
			'meta_query'     => array(// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
			),
		);
		if ($current_user_id){
			$order_query['customer'] = $current_user_id;
		} elseif (is_user_logged_in()){
			$order_query['customer'] = get_current_user_id();
		}else{
			if (check_ajax_referer( 'update-order-review', 'security' ,false) && !empty($_POST['post_data'])){
				parse_str( wp_unslash($_POST['post_data']), $post_data );
				$billing_email = $post_data['billing_email']??'';
			}else{
				$billing_email = WC()->checkout()->get_value( 'billing_email' );
			}
			$order_query['billing_email'] = $billing_email;
		}
		if (empty($order_query['customer']) && empty($order_query['billing_email'])){
			return false;
		}

		if ($start_date){
			$order_query['date_after'] = $start_date . ' ' . $start_time;
		}
		if ($end_date){
			$order_query['date_before'] = $end_date . ' ' . $end_time;
		}
		$orders = wc_get_orders($order_query);
		return empty( $orders ) ? false : $orders;
	}
	public static function may_be_apply_to_time( $conditions = array() ) {
		if ( empty( $conditions ) ) {
			return false;
		}
		$days = $conditions['days'] ?? array();
		if ( isset( self::$cache['current_day'] ) ) {
			$current_day = self::$cache['current_day'];
		} else {
			$current_day = self::$cache['current_day'] = gmdate( 'w' );
		}
		if ( is_array( $days ) && count( $days ) && ! in_array( $current_day, $days ) ) {
			return false;
		}
		$now   = current_time( 'timestamp' );
		$start = $conditions['start'] ?? '';
		$end   = $conditions['end'] ?? '';
		if ( $start && floatval( $start ) > $now ) {
			return false;
		}
		if ( $end && floatval( $end ) < $now ) {
			return false;
		}
		return true;
	}
	public static function may_be_apply_to_product( $product_id, $product, $conditions, $rule_id ) {
		if ( ! $product_id || ! $product || ! is_a( $product, 'WC_Product' ) || ! $rule_id ) {
			return false;
		}
		if ( empty( $conditions ) ) {
			return true;
		}
		$result = true;
		foreach ( $conditions as $type ) {
			switch ( $type ) {
				case 'is_sale':
					$params  = self::$settings->get_current_setting( 'pd_rule1_' . $type, $rule_id, '' );
					$is_sale = $product->is_on_sale();
					if ( $params && ! $is_sale ) {
						$result = false;
						break;
					}
					if ( ! $params && $is_sale ) {
						$result = false;
					}
					break;
				case 'pd_price':
					if ( in_array( $product->get_type(), [ 'variable' ] ) ) {
						break;
					}
					$price_min = floatval( self::$settings->get_current_setting( 'pd_rule1_pd_price_min', $rule_id, 0 ) ?: 0 );
					$price_max = self::$settings->get_current_setting( 'pd_rule1_pd_price_max', $rule_id, '' );
					$price_max = $price_max ? floatval( $price_max ) : '';
					$pd_price  = apply_filters( 'viredis_condition_get_product_price', $product->get_price( 'edit' ), $product );
					if ( ! is_numeric( $pd_price ) ) {
						$result = false;
						break;
					}
					$pd_price = floatval( $pd_price );
					if ( $pd_price < $price_min ) {
						$result = false;
						break;
					}
					if ( is_numeric( $price_max ) && $price_max < $pd_price ) {
						$result = false;
						break;
					}
					break;
				case 'pd_visibility':
					$params = self::$settings->get_current_setting( 'pd_rule1_' . $type, $rule_id, '' );
					if ( empty( $params ) ) {
						break;
					}
					if (  $product->get_catalog_visibility() !== $params ) {
						$result = false;
					}
					break;
				case 'pd_include':
					$params = self::$settings->get_current_setting( 'pd_rule1_' . $type, $rule_id, '' );
					if ( is_array( $params ) && ! in_array( $product_id, $params ) ) {
						$parent_id = $parent_id ?? ( wp_get_post_parent_id( $product_id ) ?: 0 );
						if ( $parent_id && in_array( $parent_id, $params ) ) {
							break;
						}
						$variation_ids = $variation_ids ?? $product->get_children();
						if ( is_array( $variation_ids ) && count( array_intersect( $variation_ids, $params ) ) ) {
							break;
						}
						$result = false;
					}
					break;
				case 'pd_exclude':
					$params    = self::$settings->get_current_setting( 'pd_rule1_' . $type, $rule_id, '' );
					$parent_id = $parent_id ?? ( wp_get_post_parent_id( $product_id ) ?: 0 );
					if ( is_array( $params ) && ( in_array( $product_id, $params ) || ( $parent_id && in_array( $parent_id, $params ) ) ) ) {
						$result = false;
					}
					break;
				case 'cats_include':
					$params    = self::$settings->get_current_setting( 'pd_rule1_' . $type, $rule_id, '' );
					$parent_id = $parent_id ?? ( wp_get_post_parent_id( $product_id ) ?: 0 );
					$cats_id   = $cats_id ?? wc_get_product_cat_ids( $parent_id ?: $product_id );
					if ( is_array( $params ) && is_array( $cats_id ) && ! count( array_intersect( $cats_id, $params ) ) ) {
						$result = false;
					}
					break;
				case 'cats_exclude':
					$params    = self::$settings->get_current_setting( 'pd_rule1_' . $type, $rule_id, '' );
					$parent_id = $parent_id ?? ( wp_get_post_parent_id( $product_id ) ?: 0 );
					$cats_id   = $cats_id ?? wc_get_product_cat_ids( $parent_id ?: $product_id );
					if ( is_array( $params ) && is_array( $cats_id ) && count( array_intersect( $cats_id, $params ) ) ) {
						$result = false;
					}
					break;
			}
			if ( ! $result ) {
				break;
			}
		}
		return $result;
	}
	public static function save_rule_by_pd_id( $product_id, &$rules ) {
		if ( ! $product_id || empty( $rules ) ) {
			return;
		}
		if ( ! isset( $rules['pd_id'] ) ) {
			$rules['pd_id'] = $product_id;
		}
		if ( ! isset( $rules['parent_id'] ) ) {
			$rules['parent_id'] = wp_get_post_parent_id( $product_id ) ?: 0;
		}
		if ( ! isset( $rules['prices'] ) ) {
			$rules['prices'] = wp_json_encode( array() );
		}
		$rules['prices']          = wp_json_encode( $rules['prices'] ?? array() );
		$rules['time_conditions'] = wp_json_encode( $rules['time_conditions'] ?? array() );
		$rules['pd_conditions']   = wp_json_encode( $rules['pd_conditions'] ?? array() );
		$rules['cart_conditions'] = wp_json_encode( $rules['cart_conditions'] ?? array() );
		$rules['user_conditions'] = wp_json_encode( $rules['user_conditions'] ?? array() );
		if ( empty( VIREDIS_Pricing_Table::get_rule_by_pd_id( $product_id ) ) ) {
			VIREDIS_Pricing_Table::insert( $product_id, $rules );
		} else {
			VIREDIS_Pricing_Table::update( $product_id, array(
				'prices'          => $rules['prices'],
				'time_conditions' => $rules['time_conditions'],
				'pd_conditions'   => $rules['pd_conditions'],
				'cart_conditions' => $rules['cart_conditions'],
				'user_conditions' => $rules['user_conditions']
			) );
		}
	}
	public static function set_rule( $product_id, $product ) {
		if ( ! $product_id || ! $product ) {
			return false;
		}
		if ( $parent_id = wp_get_post_parent_id( $product_id ) ) {
			if ( isset( self::$cache['rules'][ $parent_id ] ) ) {
				$available_rules = self::$cache['rules'][ $parent_id ];
			} else {
				$available_rules                    = VIREDIS_Pricing_Table::get_rule_by_pd_id( $parent_id ) ?? self::set_rule( $parent_id, wc_get_product( $parent_id ) );
				self::$cache['rules'][ $parent_id ] = $available_rules;
			}
			if ( empty( $available_rules ) ) {
				return false;
			}
			$pd_conditions   = villatheme_json_decode( $available_rules['pd_conditions'] ?? '{}' );
			$time_conditions = villatheme_json_decode( $available_rules['time_conditions'] ?? '{}' );
			$user_conditions = villatheme_json_decode( $available_rules['user_conditions'] ?? '{}' );
			$cart_conditions = villatheme_json_decode( $available_rules['cart_conditions'] ?? '{}' );
			$prices          = villatheme_json_decode( $available_rules['prices'] ?? '{}' );
			if ( is_array( $pd_conditions ) && count( $pd_conditions ) ) {
				$rules = array(
					'pd_id'           => $product_id,
					'parent_id'       => $parent_id ?? 0,
					'prices'          => array(),
					'time_conditions' => array(),
					'cart_conditions' => array(),
					'user_conditions' => array(),
				);
				foreach ( $pd_conditions as $rule_id => $conditions ) {
					if ( ! empty( $conditions ) && ! self::may_be_apply_to_product( $product_id, $product, $conditions, $rule_id ) ) {
						continue;
					}
					$rules['pd_conditions'][ $rule_id ]   = $conditions;
					$rules['time_conditions'][ $rule_id ] = $time_conditions[ $rule_id ] ?? array();
					$rules['user_conditions'][ $rule_id ] = $user_conditions[ $rule_id ] ?? array();
					$rules['cart_conditions'][ $rule_id ] = $cart_conditions[ $rule_id ] ?? array();
					$rules['prices'][ $rule_id ]          = $prices[ $rule_id ] ?? array();
				}
			} else {
				$rules = array(
					'pd_id'           => $product_id,
					'parent_id'       => $parent_id,
					'prices'          => $prices,
					'time_conditions' => $time_conditions,
					'pd_conditions'   => $pd_conditions,
					'cart_conditions' => $cart_conditions,
					'user_conditions' => $user_conditions,
				);
			}
			self::save_rule_by_pd_id( $product_id, $rules );
			return $rules;
		}
		$available_rule_ids = self::$settings->get_params( 'pd_id' );
		if ( empty( $available_rule_ids ) ) {
			return false;
		}
		$rules = array(
			'pd_id'           => $product_id,
			'parent_id'       => 0,
			'prices'          => array(),
			'time_conditions' => array(),
			'pd_conditions'   => array(),
			'cart_conditions' => array(),
			'user_conditions' => array(),
		);
		foreach ( $available_rule_ids as $i => $id ) {
			if ( ! self::$settings->get_current_setting( 'pd_active', $i, '' ) ) {
				continue;
			}
			$pd_rules = self::$settings->get_current_setting( 'pd_rule1_type', $id, array() );
			if ( ! empty( $pd_rules ) && ! self::may_be_apply_to_product( $product_id, $product, $pd_rules, $id ) ) {
				continue;
			}
			$rules['pd_conditions'][ $id ] = $pd_rules;
			$prices                        = array(
				'name'  => self::$settings->get_current_setting( 'pd_name', $i ),
				'apply' => self::$settings->get_current_setting( 'pd_apply', $i, '1' ),
				'type'  => $pd_type = self::$settings->get_current_setting( 'pd_type', $i, 'basic' ),
			);
			if ( $pd_type === 'basic' ) {
				$prices['basic_price'] = self::$settings->get_current_setting( 'pd_basic_price', $i, 0 );
				$prices['basic_type']  = self::$settings->get_current_setting( 'pd_basic_type', $i, '0' );
			} else {
				$prices['bulk_qty_base']            = self::$settings->get_current_setting( 'pd_bulk_qty_base', $i, 'all' );
				$prices['bulk_qty_range']           = self::$settings->get_current_setting( 'pd_bulk_qty_range', $id, array() );
			}
			$rules['prices'][ $id ]          = $prices;
			$pd_from                         = self::$settings->get_current_setting( 'pd_from', $i );
			$pd_to                           = self::$settings->get_current_setting( 'pd_to', $i );
			$from                            = $pd_from ? strtotime( $pd_from ) + villatheme_convert_time( self::$settings->get_current_setting( 'pd_from_time', $i ) ) : '';
			$to                              = $pd_to ? strtotime( $pd_to ) + villatheme_convert_time( self::$settings->get_current_setting( 'pd_to_time', $i ) ) : '';
			$rules['time_conditions'][ $id ] = array(
				'days'  => self::$settings->get_current_setting( 'pd_day', $id, array() ),
				'start' => $from,
				'end'   => $to,
//				'from_date'=>self::$settings->get_current_setting( 'pd_from', $i ),
//				'from_time'=>self::$settings->get_current_setting( 'pd_from_time', $i ),
//				'to_date'=>self::$settings->get_current_setting( 'pd_to', $i ),
//				'to_time'=>self::$settings->get_current_setting( 'pd_from_time', $i ),
			);
			$pd_cart_rule_type               = self::$settings->get_current_setting( 'pd_cart_rule_type', $id, array() );
			$cart_condition                  = array();
			if ( ! empty( $pd_cart_rule_type ) ) {
				foreach ( $pd_cart_rule_type as $type ) {
					switch ( $type ) {
						case 'cart_subtotal':
							$cart_condition[ $type ] = array(
								'subtotal_min' => self::$settings->get_current_setting( 'pd_cart_rule_subtotal_min', $id, 0 ),
								'subtotal_max' => self::$settings->get_current_setting( 'pd_cart_rule_subtotal_max', $id, '' )
							);
							break;
						case 'count_item':
							$cart_condition[ $type ] = array(
								'count_item_min' => self::$settings->get_current_setting( 'pd_cart_rule_count_item_min', $id, 0 ),
								'count_item_max' => self::$settings->get_current_setting( 'pd_cart_rule_count_item_max', $id, '' )
							);
							break;
						case 'qty_item':
							$cart_condition[ $type ] = array(
								'qty_item_min' => self::$settings->get_current_setting( 'pd_cart_rule_qty_item_min', $id, 0 ),
								'qty_item_max' => self::$settings->get_current_setting( 'pd_cart_rule_qty_item_max', $id, '' )
							);
							break;
						default:
							$cart_condition[ $type ] = self::$settings->get_current_setting( 'pd_cart_rule_' . $type, $id, array() );
					}
				}
			}
			$rules['cart_conditions'][ $id ] = $cart_condition;
			$pd_user_rule_type               = self::$settings->get_current_setting( 'pd_user_rule_type', $id, array() );
			$user_condition                  = array();
			if ( ! empty( $pd_user_rule_type ) ) {
				foreach ( $pd_user_rule_type as $type ) {
					$user_condition[ $type ] = self::$settings->get_current_setting( 'pd_user_rule_' . $type, $id, $type === 'logged' ? '' : array() );
				}
			}
			$rules['user_conditions'][ $id ] = $user_condition;
		}
		self::save_rule_by_pd_id( $product_id, $rules );
		return $rules;
	}
	public static function get_rules( $product_id, $product, $product_qty = 0 ) {
		if ( ! $product_id || ! $product ) {
			return false;
		}
		$rules                               = self::$cache['rules'][ $product_id ] ?? VIREDIS_Pricing_Table::get_rule_by_pd_id( $product_id ) ?? self::set_rule( $product_id, $product );
		self::$cache['rules'][ $product_id ] = $rules;
		if ( empty( $rules ) ) {
			return false;
		}
		$available_rules = $available_rule_ids = array();
		$prices          = villatheme_json_decode( $rules['prices'] ?? '{}' );
		if ( empty( $prices ) ) {
			return false;
		}
		$time_conditions = villatheme_json_decode( $rules['time_conditions'] ?? '{}' );
		if ( is_array( $time_conditions ) && count( $time_conditions ) ) {
			foreach ( $time_conditions as $rule_id => $conditions ) {
				if ( ! isset( self::$cache['may_be_apply_to_time'][ $rule_id ] ) ) {
					self::$cache['may_be_apply_to_time'][ $rule_id ] = self::may_be_apply_to_time( $conditions );
				}
				$may_be_apply_to_time = self::$cache['may_be_apply_to_time'][ $rule_id ];
				if ( ! $may_be_apply_to_time ) {
					continue;
				}
				$available_rule_ids[] = $rule_id;
			}
			if ( empty( $available_rule_ids ) ) {
				return false;
			}
		}
		$user_conditions = villatheme_json_decode( $rules['user_conditions'] ?? '{}' );
		if ( is_array( $user_conditions ) && count( $user_conditions ) ) {
			foreach ( $user_conditions as $rule_id => $conditions ) {
				if ( ! in_array( $rule_id, $available_rule_ids ) ) {
					continue;
				}
				if ( ! self::may_be_apply_to_user( $rule_id, $conditions ) ) {
					$index = array_search( $rule_id, $available_rule_ids );
					unset( $available_rule_ids[ $index ] );
					$available_rule_ids = array_values( $available_rule_ids );
				}
				if ( empty( $available_rule_ids ) ) {
					break;
				}
			}
			if ( empty( $available_rule_ids ) ) {
				return false;
			}
		}
		$cart_conditions = villatheme_json_decode( $rules['cart_conditions'] ?? '{}' );
		if ( is_array( $cart_conditions ) && count( $cart_conditions ) ) {
			foreach ( $cart_conditions as $rule_id => $conditions ) {
				if ( ! in_array( $rule_id, $available_rule_ids ) ) {
					continue;
				}
				if ( ! self::may_be_apply_to_cart( $rule_id, $conditions, $product, $product_id, $product_qty ) ) {
					$index = array_search( $rule_id, $available_rule_ids );
					unset( $available_rule_ids[ $index ] );
					$available_rule_ids = array_values( $available_rule_ids );
				}
				if ( empty( $available_rule_ids ) ) {
					break;
				}
			}
			if ( empty( $available_rule_ids ) ) {
				return false;
			}
		}
		$apply_rule_type = self::$settings->get_params( 'pd_apply_rule' );
		foreach ( $available_rule_ids as $rule_id ) {
			if ( empty( $prices[ $rule_id ] ) ) {
				continue;
			}
			if ( $apply_rule_type && ! empty( $available_rules ) ) {
				break;
			}
			$temp  = array(
				'prices'          => $prices[ $rule_id ],
				'time_conditions' => $time_conditions[ $rule_id ] ?? array(),
				'user_conditions' => $user_conditions[ $rule_id ] ?? array(),
				'cart_conditions' => $cart_conditions[ $rule_id ] ?? array(),
			);
			$apply = $prices[ $rule_id ]['apply'] ?? '1';
			if ( ! empty( $available_rules ) ) {
				if ( ! $apply ) {
					$available_rules = array( $rule_id => $temp );
					break;
				} elseif ( $apply === '1' ) {
					continue;
				}
			} else {
				if ( ! $apply || $apply === '1' ) {
					$available_rules = array( $rule_id => $temp );
					break;
				}
			}
			if ( $apply === '2' ) {
				$available_rules[ $rule_id ] = $temp;
			}
		}
		return $available_rules;
	}

	public static function include_tax() {
		if ( isset( self::$cache['include_tax'] ) ) {
			return self::$cache['include_tax'];
		}
		return self::$cache['include_tax'] = 'incl' === get_option( 'woocommerce_tax_display_shop' );
	}

	public static function product_get_price_tax( $product, $price, $qty = 1 ) {
		return self::include_tax() ? wc_get_price_including_tax( $product, array( 'qty' => $qty, 'price' => $price, ) ) :
			wc_get_price_excluding_tax( $product, array( 'qty' => $qty, 'price' => $price, ) );
	}

	public function display_price_on_this_page() {
		if ( isset( self::$cache['is_product_page'], self::$cache['is_product_list'] ) ) {
			return;
		}
		if ( ! isset( self::$cache['is_product_page'] ) ) {
			self::$cache['is_product_page'] = is_product() && self::$settings->get_params( 'pd_change_price_on_single' ) ? get_the_ID() : false;
		}
		if ( ! isset( self::$cache['is_product_list'] ) ) {
			$pd_change_price_on_list = self::$settings->get_params( 'pd_change_price_on_list' );
			$assign_page             = $pd_change_price_on_list ? self::$settings->get_params( 'pd_pages_change_price_on_list' ) : array();
			if (!empty($assign_page)){
				self::$cache['is_product_list'] = false;
				foreach ($assign_page as $check){
					if (function_exists($check) && $check()){
						self::$cache['is_product_list'] = true;
						break;
					}
				}
			}
			self::$cache['is_product_list'] = self::$cache['is_product_list'] ?? ( $pd_change_price_on_list ? true : false );
		}
	}
	public function wmc_get_products_price_ajax_handle_before(){
		if (!wp_doing_ajax()){
			return;
		}
		if ( isset( $_REQUEST['viredis_nonce'] ) && ! wp_verify_nonce( wc_clean( $_REQUEST['viredis_nonce'] ), 'viredis_nonce' ) ) {
			return;
		}
		if (isset($_POST['extra_params']['redis_is_product_page'])){
			self::$cache['is_product_page'] = (int) sanitize_text_field($_POST['extra_params']['redis_is_product_page']) ?:0;
		}
		if (isset($_POST['extra_params']['redis_is_product_page'])){
			self::$cache['is_product_list'] = sanitize_text_field($_POST['extra_params']['redis_is_product_list']);
		}
	}
	public function wmc_frontend_extra_params($arg){
		$arg['redis_is_product_page'] = self::$cache['is_product_page'] ?? '';
		$arg['redis_is_product_list'] = !empty(self::$cache['is_product_list']) ? 1 : '';
		return $arg;
	}
}