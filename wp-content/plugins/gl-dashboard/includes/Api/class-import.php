<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Api_Import {

	public function register_routes(): void {
		register_rest_route( 'gl-dashboard/v1', '/groups/(?P<id>\d+)/users/import', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'import_users' ),
			'permission_callback' => array( 'GLD_Access', 'rest_permission' ),
			'args'                => array(
				'id' => array(
					'validate_callback' => function( $v ) { return is_numeric( $v ); },
				),
			),
		) );
	}

	public function import_users( WP_REST_Request $request ): WP_REST_Response {
		$group_id  = (int) $request['id'];
		$leader_id = get_current_user_id();

		if ( ! GLD_Access::leads_group( $group_id ) ) {
			return new WP_REST_Response( array( 'message' => 'Forbidden' ), 403 );
		}

		$files = $request->get_file_params();
		if ( empty( $files['file'] ) || $files['file']['error'] !== UPLOAD_ERR_OK ) {
			$code = $files['file']['error'] ?? -1;
			return new WP_REST_Response( array( 'message' => 'No file received or upload error (code ' . $code . ').' ), 400 );
		}

		$file      = $files['file'];
		$file_path = $file['tmp_name'];
		$mime_type = $file['type'];

		$rows = GLD_CSV_Import::parse( $file_path, $mime_type );

		if ( is_wp_error( $rows ) ) {
			return new WP_REST_Response( array( 'message' => $rows->get_error_message() ), 400 );
		}

		if ( empty( $rows ) ) {
			return new WP_REST_Response( array( 'message' => 'The file contains no data rows.' ), 400 );
		}

		$per_seat = GLD_Billing::get_per_seat_price( $group_id );
		$currency = get_woocommerce_currency();
		$sub      = GLD_Subscription::get_for_group( $group_id );

		// Require a saved card upfront when per-seat billing is active.
		if ( $per_seat > 0 && ! GLD_Billing::has_saved_card( $leader_id ) ) {
			return new WP_REST_Response( array(
				'message'       => 'No payment card on file. Please set up a card in the Billing section before importing.',
				'requires_card' => true,
			), 402 );
		}

		// Build a fast lookup of current group members so we can skip duplicates.
		$existing = array_flip( learndash_get_groups_user_ids( $group_id ) );

		$succeeded = [];
		$skipped   = [];
		$failed    = [];

		foreach ( $rows as $row ) {
			$email = sanitize_email( $row['email'] );

			if ( ! is_email( $email ) ) {
				$failed[] = array(
					'email'  => $row['email'] !== '' ? esc_html( $row['email'] ) : '(blank)',
					'reason' => 'Invalid or missing email address.',
				);
				continue;
			}

			// ── Find or create WP user ────────────────────────────────────
			$user        = get_user_by( 'email', $email );
			$new_account = false;

			if ( ! $user ) {
				// Generate a unique username from the email local-part.
				$base     = sanitize_user( strstr( $email, '@', true ), true );
				$username = $base ?: 'user';
				while ( username_exists( $username ) ) {
					$username = $base . '_' . wp_rand( 100, 9999 );
				}

				$password = wp_generate_password( 16, true, false );
				$user_id  = wp_create_user( $username, $password, $email );

				if ( is_wp_error( $user_id ) ) {
					$failed[] = array(
						'email'  => $email,
						'reason' => 'Could not create account: ' . $user_id->get_error_message(),
					);
					continue;
				}

				// Set name fields if provided.
				$update_args = array( 'ID' => $user_id );
				if ( $row['first'] !== '' ) $update_args['first_name']   = $row['first'];
				if ( $row['last']  !== '' ) $update_args['last_name']    = $row['last'];

				$display = trim( $row['first'] . ' ' . $row['last'] );
				if ( $display !== '' ) $update_args['display_name'] = $display;

				if ( count( $update_args ) > 1 ) {
					wp_update_user( $update_args );
				}

				wp_new_user_notification( $user_id, null, 'user' );

				$user        = get_userdata( $user_id );
				$new_account = true;
			}

			// ── Skip duplicates ───────────────────────────────────────────
			if ( isset( $existing[ $user->ID ] ) ) {
				$skipped[] = array(
					'email'  => $email,
					'name'   => $user->display_name,
					'reason' => 'Already enrolled in this group.',
				);
				continue;
			}

			// ── Per-seat charge ───────────────────────────────────────────
			if ( $per_seat > 0 ) {
				$prorated      = GLD_Billing::calculate_prorated_amount( $group_id );
				$credit        = GLD_Billing::apply_credit( $group_id, $prorated );
				$owed          = $credit['owed'];
				$charge_result = array( 'success' => true );

				if ( $owed > 0 ) {
					$desc          = sprintf(
						'1 seat — %s — %s',
						get_the_title( $group_id ),
						$user->display_name ?: $email
					);
					$charge_result = GLD_Billing::charge( $leader_id, $owed, $currency, $desc );
				}

				if ( ! $charge_result['success'] ) {
					if ( $credit['credit_used'] > 0 ) {
						GLD_Billing::add_credit( $group_id, $credit['credit_used'] );
					}
					$failed[] = array(
						'email'  => $email,
						'name'   => $user->display_name,
						'reason' => 'Payment failed: ' . ( $charge_result['error'] ?? 'Unknown error.' ),
					);
					// Do NOT enrol — leave this user out and continue the loop.
					continue;
				}

				$period_end = $sub ? $sub->expiry_date : date( 'Y-m-d', strtotime( '+1 year' ) );
				GLD_Seat_Charges_DB::insert( array(
					'group_id'     => $group_id,
					'user_id'      => $user->ID,
					'leader_id'    => $leader_id,
					'amount'       => $owed + ( $credit['credit_used'] ?? 0 ),
					'currency'     => $currency,
					'period_start' => date( 'Y-m-d' ),
					'period_end'   => $period_end,
					'flw_txn_ref'  => $charge_result['txn_ref'] ?? '',
					'flw_txn_id'   => $charge_result['txn_id'] ?? '',
					'status'       => 'completed',
				) );
			}

			// ── Enrol ─────────────────────────────────────────────────────
			ld_update_group_access( $user->ID, $group_id, false );
			$existing[ $user->ID ] = true; // prevent double-enrol in same batch

			$succeeded[] = array(
				'email'       => $email,
				'name'        => $user->display_name,
				'new_account' => $new_account,
			);
		}

		return new WP_REST_Response( array(
			'succeeded' => $succeeded,
			'skipped'   => $skipped,
			'failed'    => $failed,
			'totals'    => array(
				'succeeded' => count( $succeeded ),
				'skipped'   => count( $skipped ),
				'failed'    => count( $failed ),
			),
		), 200 );
	}
}
