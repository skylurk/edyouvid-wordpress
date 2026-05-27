<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VIREDIS_Admin_product {
	public function __construct() {
		$hook_action = array(
			'woocommerce_after_product_object_save',
			'woocommerce_before_delete_product',
			'woocommerce_before_delete_product_variation',
		);
		foreach ( $hook_action as $action ) {
			add_action( $action, array( $this, 'delete_cache_pricing' ), 10, 1 );
		}
	}
	public function delete_cache_pricing( $id ) {
		if (is_a($id, 'WC_Product')){
			$id = $id->get_id();
		}
		$parent_id = wp_get_post_parent_id( $id );
		VIREDIS_Pricing_Table::delete( $parent_id ?: $id );
		VIREDIS_Pricing_Table::delete_by_parent_id( $parent_id );
	}
}