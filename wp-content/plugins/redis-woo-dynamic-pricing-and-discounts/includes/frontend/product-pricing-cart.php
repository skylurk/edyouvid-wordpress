<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Frontend_Product_Pricing_Cart {
	public static $cache = array();
	public static function viredis_woocommerce_get_price( $price, $product ) {
		if ( ! $price || ! $product ||!is_a($product ,'WC_Product') ) {
			return $price;
		}
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) ) {
			return $price;
		}
		if ( ! empty( $product->get_meta('viredis_cart_item') ) ) {
			$price = self::get_price( $product->get_meta('viredis_cart_item'), $price, $product );
		}
		return $price;
	}
	public static function viredis_mark_as_cart_item( $cart_item_data ) {
		if (!is_a($cart_item_data['data'] ,'WC_Product')){
			return $cart_item_data;
		}
		$cart_item_data['data']->update_meta_data('viredis_cart_item', $cart_item_data['key']??'');
//		$cart_item_data['data']->viredis_cart_item = $cart_item_data['key']??'';
		if ( isset( $cart_item_data['quantity']) ) {
//			$cart_item_data['data']->viredis_cart_item_qty = $cart_item_data['quantity'] ?? 1;
			$cart_item_data['data']->update_meta_data('viredis_cart_item_qty', $cart_item_data['quantity'] ?? 1);
		}
		if ( isset( $cart_item_data['viredis_pricing'] ) ) {
//			$cart_item_data['data']->viredis_pricing = $cart_item_data['viredis_pricing'];
			$cart_item_data['data']->update_meta_data('viredis_pricing', $cart_item_data['viredis_pricing']);
		}
		return $cart_item_data;
	}
	public static function get_current_currency( ) {
		$curcy ='';
		if (class_exists('WOOMULTI_CURRENCY_Data')){
			$curcy_data = WOOMULTI_CURRENCY_Data::get_ins();
		}elseif(class_exists('WOOMULTI_CURRENCY_F_Data')){
			$curcy_data = WOOMULTI_CURRENCY_F_Data::get_ins();
		}
		if (!empty($curcy_data) && $curcy_data->get_enable()){
			$curcy = $curcy_data->get_current_currency();
		}
		return apply_filters('viredis_get_current_currency',$curcy);
	}
	public static function get_price( $viredis_cart_item, $price, $product ) {
		if ( ! $viredis_cart_item || ! $price || ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return $price;
		}
//		if (isset(VIREDIS_Frontend_Cart::$cache['get_cart_discount'])){
//			return $price;
//		}
		$curcy = self::get_current_currency();
		$prices_key = $viredis_cart_item;
//		$viredis_cart_item_qty = WC()->cart->get_cart_item($viredis_cart_item)['data']->viredis_cart_item_qty ??'';
//		if (!empty($viredis_cart_item_qty)){
//			$prices_key .= '_qty_'.$viredis_cart_item_qty;
//		}
		if ( isset( self::$cache['prices'][ $prices_key ][ $curcy ] )&& !has_filter('viredis_change_3rd_plugin_price')) {
			return self::$cache['prices'][ $prices_key ][ $curcy ];
		}
		if ( isset( self::$cache[ 'get-price-' . $viredis_cart_item ] ) ) {
			return $price;
		}
		self::$cache[ 'get-price-' . $viredis_cart_item ] = true;
		if (  ! isset( self::$cache['old_prices'][ $prices_key ][$curcy] )) {
			self::$cache['old_prices'][ $prices_key ][$curcy] = $price;
		}
		$price                                            = self::$cache['old_prices'][ $prices_key ][$curcy];
		$product_id                                       = $product->get_id();
		if ( ! isset( self::$cache['rules'][ $viredis_cart_item ] ) ) {
			self::$cache['rules'][ $viredis_cart_item ] = VIREDIS_Frontend_Product::get_rules( $product_id, $product );
		}
		$rules = self::$cache['rules'][ $viredis_cart_item ];
		if ( empty( $rules ) ) {
			return self::$cache['prices'][ $prices_key ][ $curcy ] = $price;
		}
		$current_price = VIREDIS_Frontend_Product::get_current_prices( $price, $product_id, $product, $rules );
		unset( self::$cache[ 'get-price-' . $viredis_cart_item ] );
		return self::$cache['prices'][ $prices_key ][ $curcy ] = $current_price;
	}
	public static function viredis_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
		if ( ! $price_html || ! $cart_item || ! $cart_item_key ) {
			return $price_html;
		}
		$curcy = self::get_current_currency();
		if ( isset( self::$cache['cart_prices'][ $cart_item_key ][$curcy] ) ) {
			return self::$cache['cart_prices'][ $cart_item_key ][$curcy];
		}
		if ( isset( self::$cache[ 'get-price-html-' . $cart_item_key ] ) ) {
			return $price_html;
		}
		self::$cache[ 'get-price-html-' . $cart_item_key ] = true;
		if ( isset(self::$cache['old_prices'][ $cart_item_key ][$curcy], self::$cache['prices'][ $cart_item_key ][ $curcy ] ) ) {
			$current_price      = self::$cache['prices'][ $cart_item_key ][ $curcy];
			$current_price_html = VIREDIS_Frontend_Product::get_price_html( $current_price, self::$cache['old_prices'][ $cart_item_key ][$curcy], VIREDIS_Frontend_Product::$cache['display_prices_type']['cart'] ?? 0, $cart_item['data'] ?? '' );
			return self::$cache['cart_prices'][ $cart_item_key ][$curcy] = apply_filters( 'viredis_cart_item_get_price', $current_price_html, $price_html, $cart_item, $cart_item_key );
		}
		unset( self::$cache[ 'get-price-html-' . $cart_item_key ] );
		return $price_html;
	}
}