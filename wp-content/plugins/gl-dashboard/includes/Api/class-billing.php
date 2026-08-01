<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Billing {

	public function register_routes(): void {

		// Card management.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/card', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_card' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'remove_card' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			),
		) );

		// Card setup — verify a completed Flutterwave transaction and save the token.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/card-setup', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'setup_card' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id'     => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'txn_id' => array( 'required' => true, 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
			),
		) );

		// Billing history + credit balance.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/billing', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_billing' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
			),
		) );
	}

	// ── GET /groups/{id}/card ────────────────────────────────────────────────

	public function get_card( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$leader_id = get_current_user_id();
		$card      = GLD_Billing::get_card_display( $leader_id );

		return new WP_REST_Response( array(
			'has_card' => $card !== null,
			'card'     => $card,
		), 200 );
	}

	// ── DELETE /groups/{id}/card ─────────────────────────────────────────────

	public function remove_card( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		GLD_Billing::remove_saved_card( get_current_user_id() );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	// ── POST /groups/{id}/card-setup ─────────────────────────────────────────

	public function setup_card( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$txn_id    = (int) $request['txn_id'];
		$leader_id = get_current_user_id();

		$result = GLD_Billing::verify_and_save_token( $txn_id, $leader_id );

		if ( ! $result['success'] ) {
			return new WP_REST_Response( array( 'message' => $result['error'] ), 422 );
		}

		// Refund the small card-verification charge — it was only needed to tokenize the card.
		GLD_Billing::refund_transaction( $txn_id );

		// First-time setup (not a card replacement) activates the group at the current global price.
		if ( ! GLD_Subscription::get_for_group( $group_id ) ) {
			$product_id = (int) get_option( 'gld_access_product_id', 0 );
			if ( ! $product_id ) {
				return new WP_REST_Response( array(
					'message' => 'Card saved, but no active plan is configured yet. Please contact the administrator.',
				), 422 );
			}

			GLD_Subscription::upsert(
				$group_id,
				$leader_id,
				0,
				$product_id,
				get_the_title( $product_id ),
				array(),
				date( 'Y-m-d' ),
				date( 'Y-m-d', strtotime( '+1 year' ) ),
				GLD_Billing::get_global_seat_price()
			);
			GLD_Subscription::grant_group_access( $group_id );

			// Grant the group access to whatever courses the Active Plan includes.
			$course_ids = GLD_Billing::get_active_plan_course_ids();
			if ( ! empty( $course_ids ) ) {
				learndash_set_group_enrolled_courses( $group_id, $course_ids );
			}
		}

		return new WP_REST_Response( array(
			'success' => true,
			'card'    => GLD_Billing::get_card_display( $leader_id ),
		), 200 );
	}

	// ── GET /groups/{id}/billing ─────────────────────────────────────────────

	public function get_billing( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$charges = GLD_Seat_Charges_DB::get_for_group( $group_id );

		$rows = array();
		foreach ( $charges as $c ) {
			$rows[] = array(
				'id'           => (int) $c->id,
				'user_name'    => $c->user_name ?? 'Unknown',
				'user_email'   => $c->user_email ?? '',
				'amount'       => (float) $c->amount,
				'currency'     => $c->currency,
				'period_start' => $c->period_start,
				'period_end'   => $c->period_end,
				'flw_txn_ref'  => $c->flw_txn_ref,
				'status'       => $c->status,
				'charged_at'   => $c->charged_at,
			);
		}

		$sub = GLD_Subscription::get_for_group( $group_id );

		return new WP_REST_Response( array(
			'charges'        => $rows,
			'credit_balance' => GLD_Billing::get_credit_balance( $group_id ),
			'per_seat_price' => GLD_Billing::get_per_seat_price( $group_id ),
			'currency'       => get_woocommerce_currency(),
			'user_count'     => count( learndash_get_groups_user_ids( $group_id ) ),
			'status'         => GLD_Subscription::status_label( $sub ),
			'expiry_date'    => $sub->expiry_date ?? null,
		), 200 );
	}
}
