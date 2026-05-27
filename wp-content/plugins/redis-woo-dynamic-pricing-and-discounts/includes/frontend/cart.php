<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Frontend_Cart {
	public static $settings, $cache = array();
	public static $maximum_discount, $apply_rule_type;
	public function __construct() {
		self::$settings = VIREDIS_DATA::get_instance();
		if ( ! self::$settings->get_params( 'cart_enable' ) ) {
			return;
		}
		if ( self::$settings->get_params( 'cart_limit_discount' ) ) {
			self::$maximum_discount = array(
				'type'  => self::$settings->get_params( 'cart_limit_discount_type' ),
				'value' => self::$settings->get_params( 'cart_limit_discount_value' )
			);
		}
		self::$apply_rule_type = self::$settings->get_params( 'cart_apply_rule' );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'get_cart_discount' ), PHP_INT_MAX, 1 );
	}
	public function get_cart_discount( $wc_cart ) {
		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
			return;
		}
		if ( ! $wc_cart || $wc_cart->is_empty() ) {
			return;
		}
		if ( isset( self::$cache['get_cart_discount'] ) ) {
			return;
		}
		self::$cache['get_cart_discount'] = true;
		if ( ! isset( self::$cache['rules'] ) ) {
			self::$cache['rules'] = self::get_rules();
		}
		$rules = self::$cache['rules'];
		if ( empty( $rules ) ) {
			unset( self::$cache['get_cart_discount'] );
			return;
		}
		$cart_subtotal     = VIREDIS_Frontend_Product::$cache['cart_subtotal']['cart'] ?? apply_filters( 'viredis_condition_get_cart_subtotal', VIREDIS_Frontend_Product::get_cart_subtotal( $wc_cart, $wc_cart->is_empty() ), true );
		$current_discounts = self::get_current_discount( $rules, $wc_cart, $cart_subtotal );
		if ( empty( $current_discounts ) ) {
			unset( self::$cache['get_cart_discount'] );
			return;
		}
		$maximum_discount = ! empty( self::$maximum_discount['value'] ) ? (float) self::$maximum_discount['value'] : '';
		if ( is_numeric( $maximum_discount ) ) {
			$maximum_discount_type = self::$maximum_discount['type'] ?? 1;
			$maximum_discount      = apply_filters( 'viredis_cart_get_maximum_discount_value', $maximum_discount_type ? $maximum_discount : $maximum_discount * $cart_subtotal / 100, self::$maximum_discount, $cart_subtotal, $wc_cart );
		}
		if ( self::$settings->get_params( 'cart_combine_all_discount' ) ) {
			$current_discount = array_sum( $current_discounts );
			$current_discount = is_numeric( $maximum_discount ) && $current_discount > $maximum_discount ? $maximum_discount : $current_discount;
			$wc_cart->add_fee( self::$settings->get_params( 'cart_combine_all_discount_title' ), ( - 1 ) * $current_discount );
		} else {
			foreach ( $current_discounts as $rule_id => $discount ) {
				if ( is_numeric( $maximum_discount ) && $maximum_discount <= 0 ) {
					break;
				}
				if ( is_numeric( $maximum_discount ) ) {
					$current_discount = $discount > $maximum_discount ? $maximum_discount : $discount;
					$maximum_discount -= $current_discount;
				} else {
					$current_discount = $discount;
				}
				$wc_cart->add_fee( $rules[ $rule_id ]['title'] ?? '', ( - 1 ) * $current_discount );
			}
		}
		unset( self::$cache['get_cart_discount'] );
	}
	public static function get_current_discount( $rules, $wc_cart, $cart_subtotal ) {
		if ( empty( $rules ) || $wc_cart->is_empty() || ! $cart_subtotal ) {
			return false;
		}
		$current_discounts = array();
		foreach ( $rules as $rule_id => $params ) {
			$type           = $params['type'] ?? 0;
			$discount_value = $params['discount_value'] ?? 0;
			$discount_value = VIREDIS_Frontend_Product::get_fixed_discount_value( $type, $discount_value, $cart_subtotal );
			if ( ! $discount_value ) {
				continue;
			}
			$current_discounts[ $rule_id ] = $discount_value;
		}
		if ( empty( $current_discounts ) ) {
			return false;
		}
		return $current_discounts;
	}
	public static function get_rules() {
		$available_rule_ids = self::$settings->get_params( 'cart_id' );
		if ( empty( $available_rule_ids ) ) {
			return false;
		}
		$rules = array();
		foreach ( $available_rule_ids as $i => $id ) {
			if ( ! self::$settings->get_current_setting( 'cart_active', $i, '' ) ) {
				continue;
			}
			if ( ! isset( self::$cache['may_be_apply_to_time'][ $id ] ) ) {
				$from                                       = self::$settings->get_current_setting( 'cart_from', $i );
				$to                                         = self::$settings->get_current_setting( 'cart_to', $i );
				$from                                       = $from ? strtotime( $from ) + villatheme_convert_time( self::$settings->get_current_setting( 'cart_from_time', $i ) ) : '';
				$to                                         = $to ? strtotime( $to ) + villatheme_convert_time( self::$settings->get_current_setting( 'cart_to_time', $i ) ) : '';
				$time_conditions                            = array(
					'days'  => self::$settings->get_current_setting( 'pd_day', $id, array() ),
					'start' => $from,
					'end'   => $to,
				);
				self::$cache['may_be_apply_to_time'][ $id ] = VIREDIS_Frontend_Product::may_be_apply_to_time( $time_conditions );
			}
			if ( ! self::$cache['may_be_apply_to_time'][ $id ] ) {
				continue;
			}
			if ( ! isset( self::$cache['may_be_apply_to_user'][ $id ] ) ) {
				$user_rule_type  = self::$settings->get_current_setting( 'cart_user_rule_type', $id, array() );
				$user_conditions = array();
				if ( ! empty( $user_rule_type ) ) {
					foreach ( $user_rule_type as $type ) {
						$user_conditions[ $type ] = self::$settings->get_current_setting( 'cart_user_rule_' . $type, $id, $type === 'logged' ? '' : array() );
					}
				}
				self::$cache['may_be_apply_to_user'][ $id ] = VIREDIS_Frontend_Product::may_be_apply_to_user( $id, $user_conditions, true );
			}
			if ( ! self::$cache['may_be_apply_to_user'][ $id ] ) {
				continue;
			}
			if ( ! isset( self::$cache['may_be_apply_to_cart'][ $id ] ) ) {
				$cart_rule_type  = self::$settings->get_current_setting( 'cart_cart_rule_type', $id, array() );
				$cart_conditions = array();
				if ( ! empty( $cart_rule_type ) ) {
					foreach ( $cart_rule_type as $type ) {
						switch ( $type ) {
							case 'cart_subtotal':
								$cart_conditions[ $type ] = array(
									'subtotal_min' => self::$settings->get_current_setting( 'cart_cart_rule_subtotal_min', $id, 0 ),
									'subtotal_max' => self::$settings->get_current_setting( 'cart_cart_rule_subtotal_max', $id, '' )
								);
								break;
							case 'count_item':
								$cart_conditions[ $type ] = array(
									'count_item_min' => self::$settings->get_current_setting( 'cart_cart_rule_count_item_min', $id, 0 ),
									'count_item_max' => self::$settings->get_current_setting( 'cart_cart_rule_count_item_max', $id, '' )
								);
								break;
							case 'qty_item':
								$cart_conditions[ $type ] = array(
									'qty_item_min' => self::$settings->get_current_setting( 'cart_cart_rule_qty_item_min', $id, 0 ),
									'qty_item_max' => self::$settings->get_current_setting( 'cart_cart_rule_qty_item_max', $id, '' )
								);
								break;
							default:
								$cart_conditions[ $type ] = self::$settings->get_current_setting( 'cart_cart_rule_' . $type, $id, array() );
						}
					}
				}
				self::$cache['may_be_apply_to_cart'][ $id ] = VIREDIS_Frontend_Product::may_be_apply_to_cart( $id, $cart_conditions, '', '', '', true );
			}
			if ( ! self::$cache['may_be_apply_to_cart'][ $id ] ) {
				continue;
			}
			$temp         = array(
				'title'          => self::$settings->get_current_setting( 'cart_discount_title', $i, '' ),
				'type'           => self::$settings->get_current_setting( 'cart_discount_type', $i, 0 ),
				'discount_value' => self::$settings->get_current_setting( 'cart_discount_value', $i, 0 ),
			);
			$rules[ $id ] = $temp;
			if ( self::$apply_rule_type ) {
				break;
			}
			$apply_type = self::$settings->get_current_setting( 'cart_apply', $i, 1 );
			if ( ! empty( $rules ) ) {
				if ( ! $apply_type ) {
					$rules = array( $id => $temp );
					break;
				} elseif ( $apply_type === '1' ) {
					continue;
				}
			} else {
				if ( ! $apply_type || $apply_type === '1' ) {
					$rules = array( $id => $temp );
					break;
				}
			}
			if ( $apply_type === '2' ) {
				$rules[ $id ] = $temp;
			}
		}
		return $rules;
	}
}