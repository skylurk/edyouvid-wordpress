<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Subscription {

	public function register_routes(): void {
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/subscription', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_subscription' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
			),
		) );
	}

	public function get_subscription( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$sub     = GLD_Subscription::get_for_group( $group_id );
		$bundles = $this->get_bundles( $group_id, $sub );

		$subscription = $this->format_subscription( $sub, $group_id );

		return new WP_REST_Response( array(
			'subscription' => $subscription,
			'bundles'      => $bundles,
		), 200 );
	}

	private function format_subscription( ?object $sub, int $group_id ): array {
		if ( ! $sub ) {
			return array( 'status' => 'none' );
		}

		$today     = new DateTime( date( 'Y-m-d' ) );
		$expiry    = new DateTime( $sub->expiry_date );
		$diff      = $today->diff( $expiry );
		$days_left = ( $expiry >= $today ) ? (int) $diff->days : -(int) $diff->days;

		if ( $sub->status === 'expired' || $days_left < 0 ) {
			$status = 'expired';
		} elseif ( $days_left <= 14 ) {
			$status = 'expiring';
		} else {
			$status = 'active';
		}

		$course_ids = json_decode( $sub->course_ids ?: '[]', true );
		$courses    = array();
		foreach ( (array) $course_ids as $cid ) {
			$courses[] = array( 'id' => $cid, 'title' => get_the_title( $cid ) );
		}

		return array(
			'status'         => $status,
			'bundle_name'    => $sub->bundle_name,
			'product_id'     => (int) $sub->product_id,
			'expiry_date'    => $sub->expiry_date,
			'start_date'     => $sub->start_date,
			'days_remaining' => max( 0, $days_left ),
			'order_id'       => (int) $sub->order_id,
			'courses'        => $courses,
		);
	}

	private function get_bundles( int $group_id, ?object $sub ): array {
		$all_bundles = GLD_Admin::get_all_bundles();
		$active_pid  = $sub ? (int) $sub->product_id : 0;
		$result      = array();

		foreach ( $all_bundles as $b ) {
			$courses = array();
			foreach ( $b['course_ids'] as $cid ) {
				$courses[] = array( 'id' => $cid, 'title' => get_the_title( $cid ) );
			}
			$result[] = array(
				'id'           => $b['id'],
				'name'         => $b['name'],
				'price'        => $b['price'],
				'price_html'   => $b['price_html'],
				'courses'      => $courses,
				'checkout_url' => $this->build_checkout_url( $b['id'], $group_id ),
				'is_current'   => ( $active_pid === (int) $b['id'] ),
			);
		}
		return $result;
	}

	private function build_checkout_url( int $product_id, int $group_id ): string {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return '';
		}
		$checkout_id = wc_get_page_id( 'checkout' );
		$base        = $checkout_id ? get_permalink( $checkout_id ) : home_url( '/checkout/' );
		return add_query_arg( array( 'add-to-cart' => $product_id, 'gld_group' => $group_id ), $base );
	}
}
