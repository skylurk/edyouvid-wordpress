<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Billing {

	const FLW_TOKENIZED_URL = 'https://api.flutterwave.com/v3/tokenized-charges';
	const FLW_VERIFY_URL    = 'https://api.flutterwave.com/v3/transactions/%d/verify';

	// ── Gateway config ──────────────────────────────────────────────────────

	private static function get_secret_key(): string {
		$s = get_option( 'woocommerce_tbz_rave_settings', array() );
		return ( ( $s['testmode'] ?? 'no' ) === 'yes' )
			? ( $s['test_secret_key'] ?? '' )
			: ( $s['live_secret_key'] ?? '' );
	}

	public static function get_public_key(): string {
		$s = get_option( 'woocommerce_tbz_rave_settings', array() );
		return ( ( $s['testmode'] ?? 'no' ) === 'yes' )
			? ( $s['test_public_key'] ?? '' )
			: ( $s['live_public_key'] ?? '' );
	}

	// ── Saved card helpers ───────────────────────────────────────────────────

	public static function get_leader_token( int $user_id ): ?\WC_Payment_Token {
		if ( ! function_exists( 'WC_Payment_Tokens::get_customer_tokens' ) && ! class_exists( 'WC_Payment_Tokens' ) ) {
			return null;
		}
		$tokens = \WC_Payment_Tokens::get_customer_tokens( $user_id, 'tbz_rave' );
		if ( empty( $tokens ) ) {
			return null;
		}
		foreach ( $tokens as $token ) {
			if ( $token->is_default() ) {
				return $token;
			}
		}
		return reset( $tokens );
	}

	public static function get_leader_token_string( int $user_id ): string {
		$token = self::get_leader_token( $user_id );
		return $token ? $token->get_token() : '';
	}

	public static function has_saved_card( int $user_id ): bool {
		return ! empty( self::get_leader_token_string( $user_id ) );
	}

	public static function get_card_display( int $user_id ): ?array {
		$token = self::get_leader_token( $user_id );
		if ( ! $token || ! ( $token instanceof \WC_Payment_Token_CC ) ) {
			return null;
		}
		return array(
			'last4'     => $token->get_last4(),
			'brand'     => $token->get_card_type(),
			'exp_month' => $token->get_expiry_month(),
			'exp_year'  => $token->get_expiry_year(),
			'token_id'  => $token->get_id(),
		);
	}

	public static function remove_saved_card( int $user_id ): void {
		if ( ! class_exists( 'WC_Payment_Tokens' ) ) {
			return;
		}
		$tokens = \WC_Payment_Tokens::get_customer_tokens( $user_id, 'tbz_rave' );
		foreach ( $tokens as $token ) {
			\WC_Payment_Tokens::delete( $token->get_id() );
		}
	}

	// ── Card setup — verify Flutterwave transaction and save token ───────────

	public static function verify_and_save_token( int $txn_id, int $user_id ): array {
		$secret_key = self::get_secret_key();
		if ( ! $secret_key ) {
			return array( 'success' => false, 'error' => 'Payment gateway not configured.' );
		}

		$url      = sprintf( self::FLW_VERIFY_URL, $txn_id );
		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $secret_key,
			),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! $body || ( $body->status ?? '' ) !== 'success' ) {
			return array( 'success' => false, 'error' => $body->message ?? 'Verification failed.' );
		}

		$txn = $body->data;

		if ( ( $txn->status ?? '' ) !== 'successful' ) {
			return array( 'success' => false, 'error' => 'Transaction was not successful.' );
		}

		$token_code     = $txn->card->token ?? '';
		$customer_email = $txn->customer->email ?? '';

		if ( ! $token_code ) {
			return array( 'success' => false, 'error' => 'No card token returned. Please pay by card (not M-PESA) to save card details.' );
		}

		$card_token = "{$token_code}###{$customer_email}";

		// Replace any existing saved card for this user.
		self::remove_saved_card( $user_id );

		$expiry_parts = explode( '/', $txn->card->expiry ?? '01/99' );
		$exp_month    = $expiry_parts[0];
		$exp_year     = $expiry_parts[1] ?? '2099';
		if ( strlen( $exp_year ) === 2 ) {
			$exp_year = '20' . $exp_year;
		}

		$token = new \WC_Payment_Token_CC();
		$token->set_token( $card_token );
		$token->set_gateway_id( 'tbz_rave' );
		$token->set_card_type( $txn->card->type ?? 'card' );
		$token->set_last4( $txn->card->last_4digits ?? '****' );
		$token->set_expiry_month( $exp_month );
		$token->set_expiry_year( $exp_year );
		$token->set_user_id( $user_id );
		$token->set_default( true );
		$token->save();

		return array(
			'success' => true,
			'last4'   => $txn->card->last_4digits ?? '****',
			'brand'   => $txn->card->type ?? 'card',
		);
	}

	// ── Off-session charge ───────────────────────────────────────────────────

	/**
	 * Fire a tokenized charge against a leader's saved card.
	 *
	 * @return array { success: bool, txn_ref: string, txn_id: string, amount: float, error: string }
	 */
	public static function charge(
		int $user_id,
		float $amount,
		string $currency,
		string $description
	): array {
		$token_string = self::get_leader_token_string( $user_id );
		if ( ! $token_string ) {
			return array( 'success' => false, 'error' => 'No saved card on file.' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array( 'success' => false, 'error' => 'User not found.' );
		}

		$secret_key = self::get_secret_key();
		if ( ! $secret_key ) {
			return array( 'success' => false, 'error' => 'Payment gateway not configured.' );
		}

		if ( strpos( $token_string, '###' ) !== false ) {
			[ $token_code, $email ] = explode( '###', $token_string, 2 );
		} else {
			$token_code = $token_string;
			$email      = $user->user_email;
		}

		$tx_ref  = 'GLD|' . $user_id . '|' . uniqid();

		$body = array(
			'token'      => $token_code,
			'email'      => $email,
			'currency'   => $currency,
			'amount'     => round( $amount, 2 ),
			'tx_ref'     => $tx_ref,
			'firstname'  => $user->first_name ?: $user->display_name,
			'lastname'   => $user->last_name ?: '',
			'narration'  => $description,
		);

		$response = wp_remote_post( self::FLW_TOKENIZED_URL, array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $secret_key,
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'error' => $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! $data || ( $data->status ?? '' ) !== 'success' || ( $data->data->status ?? '' ) !== 'successful' ) {
			return array( 'success' => false, 'error' => $data->message ?? 'Charge failed.' );
		}

		return array(
			'success' => true,
			'txn_ref' => $data->data->tx_ref,
			'txn_id'  => (string) $data->data->id,
			'amount'  => (float) $data->data->charged_amount,
		);
	}

	// ── Proration ────────────────────────────────────────────────────────────

	public static function get_per_seat_price( int $group_id ): float {
		$sub = GLD_Subscription::get_for_group( $group_id );
		return $sub ? (float) ( $sub->per_seat_price ?? 0.0 ) : 0.0;
	}

	/**
	 * Returns the prorated amount to charge for a new seat added today.
	 * Based on days remaining in the group's subscription period.
	 * Returns 0.0 if per_seat_price is not set.
	 */
	public static function calculate_prorated_amount( int $group_id ): float {
		$sub = GLD_Subscription::get_for_group( $group_id );
		if ( ! $sub ) {
			return 0.0;
		}

		$per_seat = (float) ( $sub->per_seat_price ?? 0.0 );
		if ( $per_seat <= 0 ) {
			return 0.0;
		}

		$today  = new DateTime( date( 'Y-m-d' ) );
		$start  = new DateTime( $sub->start_date );
		$expiry = new DateTime( $sub->expiry_date );

		// Expired subscription: charge a full year.
		if ( $expiry <= $today ) {
			return $per_seat;
		}

		$total_days     = max( 1, (int) $start->diff( $expiry )->days );
		$days_remaining = max( 0, (int) $today->diff( $expiry )->days );

		$prorated = round( ( $days_remaining / $total_days ) * $per_seat, 2 );

		return max( 1.0, $prorated ); // minimum KES 1
	}

	// ── Credit wallet ────────────────────────────────────────────────────────

	public static function get_credit_balance( int $group_id ): float {
		$sub = GLD_Subscription::get_for_group( $group_id );
		return $sub ? (float) ( $sub->credit_balance ?? 0.0 ) : 0.0;
	}

	public static function add_credit( int $group_id, float $amount ): void {
		if ( $amount <= 0 ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . GLD_Subscription::TABLE;
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET credit_balance = credit_balance + %f WHERE group_id = %d",
			$amount, $group_id
		) );
	}

	/**
	 * Apply available credit towards an amount.
	 * Reduces credit_balance by the amount used.
	 *
	 * @return array { owed: float, credit_used: float }
	 */
	public static function apply_credit( int $group_id, float $amount_needed ): array {
		$credit = self::get_credit_balance( $group_id );
		$used   = min( $credit, $amount_needed );
		$owed   = round( $amount_needed - $used, 2 );

		if ( $used > 0 ) {
			global $wpdb;
			$table = $wpdb->prefix . GLD_Subscription::TABLE;
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$table} SET credit_balance = credit_balance - %f WHERE group_id = %d",
				$used, $group_id
			) );
		}

		return array( 'owed' => $owed, 'credit_used' => round( $used, 2 ) );
	}

	/**
	 * Calculate how much credit to award when a user is removed mid-subscription.
	 * Based on the unused portion of their most recent seat charge.
	 */
	public static function calculate_removal_credit( int $group_id, int $user_id ): float {
		$charge = GLD_Seat_Charges_DB::get_for_user_in_group( $user_id, $group_id );
		if ( ! $charge ) {
			return 0.0;
		}

		$today      = new DateTime( date( 'Y-m-d' ) );
		$period_end = new DateTime( $charge->period_end );

		if ( $period_end <= $today ) {
			return 0.0;
		}

		$period_start   = new DateTime( $charge->period_start );
		$total_days     = max( 1, (int) $period_start->diff( $period_end )->days );
		$days_remaining = (int) $today->diff( $period_end )->days;

		$credit = round( ( $days_remaining / $total_days ) * (float) $charge->amount, 2 );

		return max( 0.0, $credit );
	}
}
