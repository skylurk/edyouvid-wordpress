<?php

namespace uncanny_learndash_groups;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class WoocommerceLicenseRefund
 *
 * @package uncanny_learndash_groups
 */
class WoocommerceLicenseRefund {

	/**
	 * @var
	 */
	public $order_id;

	/**
	 * @var
	 */
	public $order;

	/**
	 * @var
	 */
	public $group_id;

	/**
	 *
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_refunded', array( $this, 'refund_trash_group' ), 20, 1 );
		add_action( 'woocommerce_refund_created', array( $this, 'woocommerce_refund_created_func' ), 20, 2 );

		// Quantities refunded v/s available seats validation
		add_action( 'woocommerce_create_refund', array( $this, 'woocommerce_create_refund_func' ), 20, 2 );
	}

	/**
	 * @param $order_id
	 */
	public function refund_trash_group( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$group_id = $order->get_meta( SharedFunctions::$linked_group_id_meta, true );

		if ( empty( $group_id ) ) {
			return;
		}

		if ( true !== apply_filters( 'ulgm_refund_trash_group', $this->trash_group_on_refund(), $group_id, $order_id ) ) {
			return;
		}

		$data = array(
			'ID'          => $group_id,
			'post_status' => 'draft',
		);

		wp_update_post( $data );

		wp_trash_post( $group_id );

		if ( true === apply_filters( 'ulgm_refund_trash_group_add_order_note', true, $order_id ) ) {

			$group_title = get_the_title( $group_id );

			$order->add_order_note(
				sprintf( esc_html__( '#%1$d %2$s linked to the order was moved to trash after the refund.', 'uncanny-learndash-groups' ), $group_id, $group_title ), //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
				false
			);
		}
	}

	/**
	 * @param $refund_id
	 * @param $args
	 *
	 * @return void
	 */
	public function woocommerce_refund_created_func( $refund_id, $args ) {

		if ( ! $refund_id ) {
			return;
		}

		$license_items = $this->is_refunding_a_license( $args );

		// No license found
		if ( empty( $license_items ) ) {
			return;
		}

		$total_seats = ulgm()->group_management->seat->total_seats( $this->group_id );

		foreach ( $license_items as $item ) {

			$seats_to_remove = $item['qty'];

			$result = ulgm()->group_management->seat->remove_seats( $seats_to_remove, $this->group_id );

			$message = $result['message'];

			if ( ! empty( $args['reason'] ) ) {
				$message .= ' ' . __( 'Reason: ', 'uncanny-learndash-groups' ) . $args['reason'];
			}

			$this->order->add_order_note( $message );
		}
	}

	/**
	 * @param $item_id
	 *
	 * @return mixed
	 */
	public function get_product_id( $item_id ) {

		$order = $this->order;

		foreach ( $order->get_items() as $item ) {
			if ( $item_id !== $item->get_id() ) {
				continue;
			}

			return $item->get_product_id();
		}
	}

	/**
	 * @param $order_id
	 *
	 * @return false|int|mixed
	 */
	public function get_real_order_id( $order_id ) {

		// Check if there's a valid order ID
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return $order_id;
		}

		if ( wcs_order_contains_subscription( $order, array( 'parent', 'renewal' ) ) ) {

			$subscriptions = wcs_get_subscriptions_for_order(
				wcs_get_objects_property( $order, 'id' ),
				array(
					'order_type' => array(
						'parent',
						'renewal',
					),
				)
			);
			foreach ( $subscriptions as $subscription ) {
				$order_id = $subscription->get_parent_id();
				break;
			}
		}

		return $order_id;
	}

	/**
	 * @param $refund_id
	 * @param $args
	 *
	 * @return array
	 */
	public function is_refunding_a_license( $args ) {

		if ( true !== $this->reduce_the_qty_on_refund() ) {
			return array();
		}

		$original_id = $args['order_id'];
		$order_id    = $this->get_real_order_id( $original_id );

		if ( ! $order_id ) {
			return array();
		}

		$this->order_id = $order_id;

		// Check if there's a valid order ID
		$order = wc_get_order( $original_id );

		$this->order = $order;

		if ( ! $order instanceof \WC_Order ) {
			return array();
		}

		// Check if there's a group linked to the order
		$group_id = $order->get_meta( SharedFunctions::$linked_group_id_meta, true );

		if ( empty( $group_id ) ) {
			return array();
		}

		$this->group_id = $group_id;

		// Allow users to skip some groups
		if ( true !== apply_filters( 'ulgm_reduce_qty_on_refund', $this->reduce_the_qty_on_refund(), $group_id ) ) {
			return array();
		}

		$line_items = $args['line_items'];

		if ( empty( $line_items ) ) {
			return array();
		}

		$license_items = array();

		foreach ( $line_items as $line_item_id => $line_item_details ) {

			$product_id = $this->get_product_id( $line_item_id );

			if ( ! $this->is_a_license( $product_id ) ) {
				continue;
			}

			$line_item_details['product_id'] = $product_id;

			$license_items[ $line_item_id ] = $line_item_details;
		}

		return $license_items;
	}

	/**
	 * @param $refund
	 * @param $args
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function woocommerce_create_refund_func( $refund, $args ) {

		$license_items = $this->is_refunding_a_license( $args );

		if ( empty( $license_items ) ) {
			return;
		}

		$available_seats = ulgm()->group_management->seat->available_seats( $this->group_id );
		$total_seats     = ulgm()->group_management->seat->total_seats( $this->group_id );

		$per_seat_text_single = strtolower( SharedFunctions::get_per_seat_text( 1 ) );
		$per_seat_text_plural = strtolower( SharedFunctions::get_per_seat_text( 2 ) );

		foreach ( $license_items as $item ) {

			$seats_to_remove = absint( $item['qty'] );

			// Full refund
			if ( absint( $seats_to_remove ) === absint( $total_seats ) ) {
				continue;
			}

			// Partial refund
			if ( $seats_to_remove > $available_seats ) {
				throw new \Exception(
					sprintf(
						esc_html__( //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
							'Uncanny Groups - Refunded %1$s (%3$d) are more than the available %1$s (%2$d) in the group. Please remove at least %4$s user(s) to increase the available %5$s count to process the refund correctly.',
							'uncanny-learndash-groups'
						),
						$per_seat_text_plural,
						$available_seats,
						$seats_to_remove,
						$seats_to_remove - $available_seats,
						$per_seat_text_single
					)
				);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function reduce_the_qty_on_refund() {
		return 'yes' === get_option( 'ulgm_reduce_seats_on_order_refund', 'no' );
	}

	/**
	 * @return bool
	 */
	public function trash_group_on_refund() {
		return 'yes' === get_option( 'ulgm_trash_linked_group', 'no' );
	}

	/**
	 * @param $product_id
	 *
	 * @return bool
	 */
	public function is_a_license( $product_id ) {

		return SharedFunctions::is_group_licensed_product( $product_id );
	}
}
