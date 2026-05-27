<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Woo {

	public static function register(): void {
		add_filter( 'woocommerce_add_cart_item_data',              array( __CLASS__, 'capture_group_in_cart' ),       10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'persist_group_to_order_item' ), 10, 4 );
		add_action( 'woocommerce_order_status_completed',          array( __CLASS__, 'handle_completed_order' ) );
	}

	// Store the gld_group URL param as cart item data for any GLD bundle product.
	public static function capture_group_in_cart( array $cart_item_data, int $product_id ): array {
		if ( get_post_meta( $product_id, '_gld_is_bundle', true ) && ! empty( $_REQUEST['gld_group'] ) ) {
			$cart_item_data['gld_group_id'] = (int) $_REQUEST['gld_group'];
		}
		return $cart_item_data;
	}

	// Copy the group_id from cart item data to the order line item meta.
	public static function persist_group_to_order_item( WC_Order_Item_Product $item, string $cart_item_key, array $values, WC_Order $order ): void {
		if ( ! empty( $values['gld_group_id'] ) ) {
			$item->add_meta_data( '_gld_group_id', (int) $values['gld_group_id'], true );
		}
	}

	// Triggered when an order is marked Complete; create/extend the subscription.
	public static function handle_completed_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$leader_id = (int) $order->get_customer_id();
		if ( ! $leader_id ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();

			// Only process GLD bundle products.
			if ( ! get_post_meta( $product_id, '_gld_is_bundle', true ) ) {
				continue;
			}

			$group_id = (int) $item->get_meta( '_gld_group_id' );
			if ( ! $group_id ) {
				continue;
			}

			// Confirm the buyer actually leads this group.
			$leader_groups = array_map( 'intval', learndash_get_administrators_group_ids( $leader_id ) );
			if ( ! in_array( $group_id, $leader_groups, true ) ) {
				continue;
			}

			$bundle_name = get_the_title( $product_id );
			$course_ids  = (array) get_post_meta( $product_id, '_gld_bundle_courses', true );
			$course_ids  = array_values( array_filter( array_map( 'intval', $course_ids ) ) );

			$start  = date( 'Y-m-d' );
			$expiry = date( 'Y-m-d', strtotime( '+1 year' ) );

			GLD_Subscription::upsert(
				$group_id,
				$leader_id,
				$order_id,
				$product_id,
				$bundle_name,
				$course_ids,
				$start,
				$expiry
			);
			GLD_Subscription::grant_group_access( $group_id );
		}
	}
}
