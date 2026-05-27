<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_DATA {
	protected static $instance = null;
	private $default, $params;
	public function __construct() {
		global $viredis_settings;
		if ( ! $viredis_settings ) {
			$viredis_settings = get_option( 'viredis_params', array() );
		}
		$cart_discount = array_merge(
			array(
				'cart_combine_all_discount'       => 0,
				'cart_combine_all_discount_title' => 'Cart Discount',
				'cart_discount_value'             => array( 10 ),
				'cart_discount_type'              => array( 0 ),
				'cart_discount_title'             => array( 'Cart Discount' ),
			),
			$this->discount_rules_params( 'cart_', 'cart_discount' )
		);
		$pd_pricing    = array_merge(
			array(
				'pd_price_type'                   => 'sale',
				'pd_cart_display_price'           => 'base_price',
				'pd_display_price'                => 'base_price',
				'pd_change_price_on_single'       => 1,
				'pd_dynamic_price'                => 0,
				'pd_change_price_on_list'         => 1,
				'pd_pages_change_price_on_list'   => array( 'is_woocommerce' ),
				'pd_on_sale_badge'                => 0,
				'pd_pricing_table'                => 0,
				'pd_pricing_table_title'          => 'Pricing table',
				'pd_pricing_table_position'       => 'before_atc',
				'pd_pricing_table_discount_value' => 'percentage_discount',
				'pd_type'                         => array( 'basic' ),//basic,bulk_qty
				'pd_basic_price'                  => array( '10' ),
				'pd_basic_type'                   => array( '0' ),
				'pd_bulk_qty_base'                => array( 'product' ),//cách tính số sp đã add to cart
				'pd_bulk_qty_range'               => array(
					'product_pricing' => array(
						'from'  => array( 1 ),
						'to'    => array( '' ),
						'type'  => array( '0' ),
						'price' => array( '10' ),
					)
				),
				//rule of products
				'pd_rule1_type'                   => array( 'product_pricing' => array() ),
				'pd_rule1_is_sale'                => array( 'product_pricing' => 0 ),
				'pd_rule1_pd_price_min'           => array( 'product_pricing' => 0 ),
				'pd_rule1_pd_price_max'           => array( 'product_pricing' => '' ),
				'pd_rule1_pd_visibility'          => array( 'product_pricing' => 'visible' ),
				'pd_rule1_pd_include'             => array( 'product_pricing' => array() ),
				'pd_rule1_pd_exclude'             => array( 'product_pricing' => array() ),
				'pd_rule1_cats_include'           => array( 'product_pricing' => array() ),
				'pd_rule1_cats_exclude'           => array( 'product_pricing' => array() ),
			),
			$this->discount_rules_params( 'pd_', 'product_pricing' )
		);
		$this->default = array_merge( $pd_pricing, $cart_discount );
		$this->params  = apply_filters( 'viredis_params', wp_parse_args( $viredis_settings, $this->default ) );
	}
	protected function discount_rules_params( $prefix, $id = '' ) {
		if ( ! $prefix ) {
			return array();
		}
		$id = $id ?: $prefix . current_time( 'timestamp' );
		return array(
			$prefix . 'enable'               => 1,
			$prefix . 'name'                 => array( '10% discount on all products' ),
			$prefix . 'limit_discount'       => 1,
			$prefix . 'limit_discount_value' => 40,
			$prefix . 'limit_discount_type'  => 0,
			$prefix . 'id'                   => array( $id ),
			$prefix . 'apply_rule'           => 1,
			$prefix . 'active'               => array( 0 ),
			$prefix . 'apply'                => array( '1' ),
			$prefix . 'from'                 => array(),
			$prefix . 'from_time'            => array(),
			$prefix . 'to'                   => array(),
			$prefix . 'to_time'              => array(),
			$prefix . 'day'                  => array( array() ),
			//rule of cart
			$prefix . 'cart_rule_type'       => array( $id => array() ),
			$prefix . 'cart_rule_subtotal_min'             => array( $id => 0 ),
			$prefix . 'cart_rule_subtotal_max'             => array( $id => '' ),
			$prefix . 'cart_rule_count_item_min'           => array( $id => 0 ),
			$prefix . 'cart_rule_count_item_max'           => array( $id => '' ),
			$prefix . 'cart_rule_qty_item_min'             => array( $id => 0 ),
			$prefix . 'cart_rule_qty_item_max'             => array( $id => '' ),
			$prefix . 'cart_rule_item_include'             => array( $id => array() ),
			$prefix . 'cart_rule_item_exclude'             => array( $id => array() ),
			$prefix . 'cart_rule_cats_include'             => array( $id => array() ),
			$prefix . 'cart_rule_cats_exclude'             => array( $id => array() ),
			$prefix . 'cart_rule_tag_include'              => array( $id => array() ),
			$prefix . 'cart_rule_tag_exclude'              => array( $id => array() ),
			$prefix . 'cart_rule_coupon_include'           => array( $id => array() ),
			$prefix . 'cart_rule_coupon_exclude'           => array( $id => array() ),
			$prefix . 'cart_rule_billing_country_include'  => array( $id => array() ),
			$prefix . 'cart_rule_billing_country_exclude'  => array( $id => array() ),
			$prefix . 'cart_rule_shipping_country_include' => array( $id => array() ),
			$prefix . 'cart_rule_shipping_country_exclude' => array( $id => array() ),
			//rule of customer
			$prefix . 'user_rule_type'                     => array( $id => array() ),
			$prefix . 'user_rule_logged'                   => array( $id => 0 ),
			$prefix . 'user_rule_user_role_include'        => array( $id => array() ),
			$prefix . 'user_rule_user_role_exclude'        => array( $id => array() ),
			$prefix . 'user_rule_user_include'             => array( $id => array() ),
			$prefix . 'user_rule_user_exclude'             => array( $id => array() ),
			$prefix . 'user_rule_order_status'             => array( $id => array() ),
			$prefix . 'user_rule_order_count'              => array(
				$id => array(
					'from' => array(),
					'to'   => array(),
					'min'  => array(),
					'max'  => array(),
				)
			),
			$prefix . 'user_rule_order_total'              => array(
				$id => array(
					'from' => array(),
					'to'   => array(),
					'min'  => array(),
					'max'  => array(),
				)
			),
			$prefix . 'user_rule_last_order'               => array(
				$id => array(
					'type' => '',
					'date' => '',
				)
			),
			$prefix . 'user_rule_product_include'          => array( $id => array() ),
			$prefix . 'user_rule_product_exclude'          => array( $id => array() ),
			$prefix . 'user_rule_cats_include'             => array( $id => array() ),
			$prefix . 'user_rule_cats_exclude'             => array( $id => array() ),
		);
	}
	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	public static function extend_post_allowed_html() {
		return array_merge( wp_kses_allowed_html( 'post' ), array(
				'input' => array(
					'type'         => 1,
					'id'           => 1,
					'name'         => 1,
					'class'        => 1,
					'placeholder'  => 1,
					'autocomplete' => 1,
					'style'        => 1,
					'value'        => 1,
					'data-*'       => 1,
					'size'         => 1,
				),
				'form'  => array(
					'type'   => 1,
					'id'     => 1,
					'name'   => 1,
					'class'  => 1,
					'style'  => 1,
					'method' => 1,
					'action' => 1,
					'data-*' => 1,
				),
				'style' => array(
					'id'    => 1,
					'class' => 1,
					'type'  => 1,
				),
			)
		);
	}
	public static function get_data_prefix( $type = 'pd' ) {
		$date   = gmdate( "Ymd" );
		$prefix = get_option( 'viredis_' . $type . '_prefix', $date );
		return $prefix . $type . $date;
	}
	public static function set_data_prefix( $type = '', $value = '' ) {
		if ( ! $type ) {
			return;
		}
		update_option( 'viredis_' . $type . '_prefix', $value ?: substr( md5( gmdate( "YmdHis" ) ), 0, 10 ) );
	}
	public static function set( $name = '', $prefix = 'viredis-' ) {
		if ( is_array( $name ) ) {
			return implode( ' ', array_map( array( __CLASS__, 'set' ), $name ) );
		} else {
			return $prefix . $name;
		}
	}
	public function get_default( $name = "" ) {
		if ( ! $name ) {
			return $this->default;
		} elseif ( isset( $this->default[ $name ] ) ) {
			return apply_filters( 'viredis_params_default-' . $name, $this->default[ $name ] );
		} else {
			return false;
		}
	}
	public function get_params( $name = "" ) {
		if ( ! $name ) {
			return $this->params;
		}
		if ( isset( $this->params[ $name ] ) ) {
			return apply_filters( 'viredis_params-' . $name, $this->params[ $name ] );
		}
		return false;
	}
	public function get_current_setting( $name = "", $i = 0, $default = false ) {
		if ( empty( $name ) ) {
			return false;
		}
		if ( $default !== false ) {
			$result = $this->get_params( $name )[ $i ] ?? $default;
		} else {
			$result = $this->get_params( $name )[ $i ] ?? $this->get_default( $name )[0] ?? false;
		}
		return $result;
	}

}