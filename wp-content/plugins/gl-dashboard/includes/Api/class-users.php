<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Users {

	public function register_routes(): void {
		// List users in group + search site users.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/users', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_users' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
				'args'                => array(
					'id'     => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
					'search' => array( 'type' => 'string', 'default' => '' ),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_user' ),
				'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
				'args'                => array(
					'id'      => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
					'user_id' => array( 'required' => true, 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				),
			),
		) );

		// Create a brand-new WordPress account and add it to the group in one step.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/users/create', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_user' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id'         => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'first_name' => array( 'required' => true ),
				'last_name'  => array( 'default' => '' ),
				'email'      => array( 'required' => true ),
				'password'   => array( 'required' => true ),
			),
		) );

		// Remove a specific user from a group.
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/users/(?P<user_id>\d+)', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'remove_user' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id'      => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'user_id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
			),
		) );

		// Search site users (for the add-user autocomplete).
		register_rest_route( 'gl-dashboard/v1', '/users/search', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'search_users' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'q' => array( 'type' => 'string', 'default' => '' ),
			),
		) );
	}

	public function get_users( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$user_ids = learndash_get_groups_user_ids( $group_id );
		$users    = array();

		foreach ( $user_ids as $user_id ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}
			$users[] = $this->format_user( $user );
		}

		return new WP_REST_Response( $users, 200 );
	}

	public function add_user( WP_REST_Request $request ): WP_REST_Response {
		$group_id  = (int) $request['id'];
		$user_id   = (int) $request['user_id'];
		$leader_id = get_current_user_id();

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		if ( ! get_userdata( $user_id ) ) {
			return new WP_REST_Response( array( 'message' => 'User not found' ), 404 );
		}

		return $this->enroll_with_billing( $group_id, $user_id, $leader_id );
	}

	// ── Create a brand-new WordPress account, then enrol it ──────────────────

	public function create_user( WP_REST_Request $request ): WP_REST_Response {
		$group_id  = (int) $request['id'];
		$leader_id = get_current_user_id();

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$first_name = sanitize_text_field( $request['first_name'] ?? '' );
		$last_name  = sanitize_text_field( $request['last_name']  ?? '' );
		$email      = sanitize_email( $request['email'] ?? '' );
		$password   = (string) ( $request['password'] ?? '' );

		if ( ! $first_name || ! $email || ! $password ) {
			return new WP_REST_Response( array( 'message' => 'Please fill in first name, email, and password.' ), 400 );
		}
		if ( ! is_email( $email ) ) {
			return new WP_REST_Response( array( 'message' => 'Please enter a valid email address.' ), 400 );
		}
		if ( strlen( $password ) < 8 ) {
			return new WP_REST_Response( array( 'message' => 'Password must be at least 8 characters.' ), 400 );
		}
		if ( email_exists( $email ) ) {
			return new WP_REST_Response( array(
				'message'       => 'An account with this email already exists. Use the search box to add them instead.',
				'already_exists'=> true,
			), 409 );
		}

		// ── Billing happens BEFORE the account is created — no card, no charge,
		// no account. This avoids creating an orphaned WP user for a learner who
		// was never actually paid for.
		$per_seat = GLD_Billing::get_per_seat_price( $group_id );
		$charge   = null;

		if ( $per_seat > 0 ) {
			if ( ! GLD_Billing::has_saved_card( $leader_id ) ) {
				return new WP_REST_Response( array(
					'message'       => 'No payment card on file. Please set up a card in the Billing section before adding learners.',
					'requires_card' => true,
				), 402 );
			}

			$prorated = GLD_Billing::calculate_prorated_amount( $group_id );
			$credit   = GLD_Billing::apply_credit( $group_id, $prorated );
			$owed     = $credit['owed'];
			$currency = get_woocommerce_currency();

			$charge_result = array( 'success' => true );
			if ( $owed > 0 ) {
				$desc          = sprintf( '1 seat — %s — %s', get_the_title( $group_id ), trim( "$first_name $last_name" ) ?: $email );
				$charge_result = GLD_Billing::charge( $leader_id, $owed, $currency, $desc );
			}

			if ( ! $charge_result['success'] ) {
				// Nothing was created, so just undo the optimistic credit deduction.
				if ( $credit['credit_used'] > 0 ) {
					GLD_Billing::add_credit( $group_id, $credit['credit_used'] );
				}
				return new WP_REST_Response( array(
					'message' => 'Payment failed: ' . ( $charge_result['error'] ?? 'Unknown error.' ) . '. No account was created — please try again.',
				), 402 );
			}

			$sub    = GLD_Subscription::get_for_group( $group_id );
			$charge = array(
				'amount'       => $owed + ( $credit['credit_used'] ?? 0 ),
				'currency'     => $currency,
				'period_start' => date( 'Y-m-d' ),
				'period_end'   => $sub ? $sub->expiry_date : date( 'Y-m-d', strtotime( '+1 year' ) ),
				'flw_txn_ref'  => $charge_result['txn_ref'] ?? '',
				'flw_txn_id'   => $charge_result['txn_id'] ?? '',
			);
		}

		// ── Payment cleared (or wasn't required) — now create the account ────
		$base     = sanitize_user( strstr( $email, '@', true ), true ) ?: 'user';
		$username = $base;
		while ( username_exists( $username ) ) {
			$username = $base . '_' . wp_rand( 100, 9999 );
		}

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			// Already charged but couldn't create the account — credit the leader back.
			if ( $charge ) {
				GLD_Billing::add_credit( $group_id, $charge['amount'] );
			}
			return new WP_REST_Response( array( 'message' => $user_id->get_error_message() ), 400 );
		}

		wp_update_user( array(
			'ID'           => $user_id,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'display_name' => trim( "$first_name $last_name" ),
		) );

		if ( $charge ) {
			GLD_Seat_Charges_DB::insert( array_merge( $charge, array(
				'group_id'  => $group_id,
				'user_id'   => $user_id,
				'leader_id' => $leader_id,
				'status'    => 'completed',
			) ) );

			GLD_Notifications::charge_receipt( $leader_id, array(
				'group_id'     => $group_id,
				'user_name'    => trim( "$first_name $last_name" ),
				'user_email'   => $email,
				'amount'       => $charge['amount'],
				'currency'     => $charge['currency'],
				'period_start' => $charge['period_start'],
				'period_end'   => $charge['period_end'],
				'flw_ref'      => $charge['flw_txn_ref'],
			) );
		}

		ld_update_group_access( $user_id, $group_id, false );

		$user_data = get_userdata( $user_id );

		return new WP_REST_Response( array(
			'success' => true,
			'user'    => $user_data ? $this->format_user( $user_data ) : null,
		), 200 );
	}

	// ── Shared billing + group-enrolment logic ────────────────────────────────

	private function enroll_with_billing( int $group_id, int $user_id, int $leader_id ): WP_REST_Response {
		// Per-seat billing: only active if per_seat_price > 0 on the subscription.
		$per_seat = GLD_Billing::get_per_seat_price( $group_id );

		if ( $per_seat > 0 ) {
			if ( ! GLD_Billing::has_saved_card( $leader_id ) ) {
				return new WP_REST_Response( array(
					'message'          => 'No payment card on file. Please set up a card in the Billing section before adding learners.',
					'requires_card'    => true,
				), 402 );
			}

			$prorated = GLD_Billing::calculate_prorated_amount( $group_id );
			$credit   = GLD_Billing::apply_credit( $group_id, $prorated );
			$owed     = $credit['owed'];

			$charge_result = array( 'success' => true );

			if ( $owed > 0 ) {
				$sub       = GLD_Subscription::get_for_group( $group_id );
				$currency  = get_woocommerce_currency();
				$user_data = get_userdata( $user_id );
				$desc      = sprintf(
					'1 seat — %s — %s',
					get_the_title( $group_id ),
					$user_data ? $user_data->display_name : "user #{$user_id}"
				);

				$charge_result = GLD_Billing::charge( $leader_id, $owed, $currency, $desc );
			}

			$sub        = GLD_Subscription::get_for_group( $group_id );
			$period_end = $sub ? $sub->expiry_date : date( 'Y-m-d', strtotime( '+1 year' ) );
			$currency   = get_woocommerce_currency();
			$total_amt  = $owed + ( $credit['credit_used'] ?? 0 );

			if ( ! $charge_result['success'] ) {
				// Restore optimistically deducted credit.
				if ( $credit['credit_used'] > 0 ) {
					GLD_Billing::add_credit( $group_id, $credit['credit_used'] );
				}
				// Record the failed charge so the cron can retry.
				GLD_Seat_Charges_DB::insert( array(
					'group_id'     => $group_id,
					'user_id'      => $user_id,
					'leader_id'    => $leader_id,
					'amount'       => $total_amt,
					'currency'     => $currency,
					'period_start' => date( 'Y-m-d' ),
					'period_end'   => $period_end,
					'status'       => 'failed',
				) );
				$user_data = get_userdata( $user_id );
				GLD_Notifications::charge_failure( $leader_id, array(
					'group_id'  => $group_id,
					'user_name' => $user_data ? $user_data->display_name : "User #{$user_id}",
					'user_email'=> $user_data ? $user_data->user_email : '',
					'amount'    => $total_amt,
					'currency'  => $currency,
					'error'     => $charge_result['error'] ?? 'Unknown error.',
				) );
				return new WP_REST_Response( array(
					'message' => 'Payment failed: ' . ( $charge_result['error'] ?? 'Unknown error.' ),
				), 402 );
			}

			// Record the successful charge in the ledger.
			GLD_Seat_Charges_DB::insert( array(
				'group_id'    => $group_id,
				'user_id'     => $user_id,
				'leader_id'   => $leader_id,
				'amount'      => $total_amt,
				'currency'    => $currency,
				'period_start'=> date( 'Y-m-d' ),
				'period_end'  => $period_end,
				'flw_txn_ref' => $charge_result['txn_ref'] ?? '',
				'flw_txn_id'  => $charge_result['txn_id'] ?? '',
				'status'      => 'completed',
			) );

			// Send receipt email.
			$user_data = get_userdata( $user_id );
			if ( $user_data ) {
				GLD_Notifications::charge_receipt( $leader_id, array(
					'group_id'     => $group_id,
					'user_name'    => $user_data->display_name,
					'user_email'   => $user_data->user_email,
					'amount'       => $total_amt,
					'currency'     => $currency,
					'period_start' => date( 'Y-m-d' ),
					'period_end'   => $period_end,
					'flw_ref'      => $charge_result['txn_ref'] ?? '',
				) );
			}
		}

		ld_update_group_access( $user_id, $group_id, false );

		$user_data = get_userdata( $user_id );

		return new WP_REST_Response( array(
			'success' => true,
			'user'    => $user_data ? $this->format_user( $user_data ) : null,
		), 200 );
	}

	public function remove_user( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['id'];
		$user_id  = (int) $request['user_id'];

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		// Award credit for the unused portion of this seat's charge.
		$credit = GLD_Billing::calculate_removal_credit( $group_id, $user_id );
		if ( $credit > 0 ) {
			GLD_Billing::add_credit( $group_id, $credit );
		}

		ld_update_group_access( $user_id, $group_id, true );

		return new WP_REST_Response( array(
			'success'       => true,
			'credit_added'  => $credit,
			'credit_balance'=> GLD_Billing::get_credit_balance( $group_id ),
		), 200 );
	}

	public function search_users( WP_REST_Request $request ): WP_REST_Response {
		$query = sanitize_text_field( $request['q'] );

		if ( strlen( $query ) < 2 ) {
			return new WP_REST_Response( array(), 200 );
		}

		$users = get_users( array(
			'search'         => '*' . $query . '*',
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
			'number'         => 10,
			'fields'         => 'all',
		) );

		$results = array();
		foreach ( $users as $user ) {
			$results[] = $this->format_user( $user );
		}

		return new WP_REST_Response( $results, 200 );
	}

	private function format_user( WP_User $user ): array {
		return array(
			'id'     => $user->ID,
			'name'   => $user->display_name,
			'email'  => $user->user_email,
			'avatar' => get_avatar_url( $user->ID, array( 'size' => 40 ) ),
		);
	}
}
