<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Cron {

	public static function register(): void {
		add_action( 'gld_daily_check', array( __CLASS__, 'run' ) );
		add_action( 'gld_cache_warm',  array( __CLASS__, 'warm_cache' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'gld_daily_check' ) ) {
			// Run daily at midnight site time.
			wp_schedule_event( strtotime( 'tomorrow midnight' ), 'daily', 'gld_daily_check' );
		}
		if ( ! wp_next_scheduled( 'gld_cache_warm' ) ) {
			// Pre-warm progress cache every 5 minutes so group leaders always hit a warm cache.
			wp_schedule_event( time(), 'gld_5min', 'gld_cache_warm' );
		}
	}

	public static function unschedule(): void {
		foreach ( array( 'gld_daily_check', 'gld_cache_warm' ) as $hook ) {
			$ts = wp_next_scheduled( $hook );
			if ( $ts ) {
				wp_unschedule_event( $ts, $hook );
			}
		}
	}

	// ── Cache warmer ─────────────────────────────────────────────────────────
	// Runs every 5 min. Rebuilds progress cache for every group with an active
	// subscription. Skips pages that are already warm, so it's cheap when idle.

	public static function warm_cache(): void {
		$subs = GLD_Subscription::get_all_active();
		foreach ( $subs as $sub ) {
			GLD_Api_Progress::warm_group( (int) $sub->group_id );
		}
	}

	public static function run(): void {
		self::retry_failed_charges();

		$subs  = GLD_Subscription::get_all_active();
		$today = new DateTime( date( 'Y-m-d' ) );

		foreach ( $subs as $sub ) {
			$expiry    = new DateTime( $sub->expiry_date );
			$diff      = $today->diff( $expiry );
			$days_left = ( $expiry >= $today ) ? (int) $diff->days : -(int) $diff->days;
			$notified  = array_filter( explode( ',', $sub->notifications_sent ?? '' ) );

			if ( $days_left < 0 ) {
				GLD_Subscription::revoke_group_access( (int) $sub->group_id );
				GLD_Subscription::set_status( (int) $sub->id, 'expired' );
				self::send_expired_email( (int) $sub->group_id, (int) $sub->leader_id, $sub->expiry_date );
				self::send_admin_notification( (int) $sub->group_id, $sub->expiry_date );

			} elseif ( $days_left <= 1 && ! in_array( '1day', $notified, true ) ) {
				self::send_warning_email( (int) $sub->group_id, (int) $sub->leader_id, 1, $sub->expiry_date );
				GLD_Subscription::mark_notified( (int) $sub->id, '1day' );

			} elseif ( $days_left <= 7 && ! in_array( '7day', $notified, true ) ) {
				self::send_warning_email( (int) $sub->group_id, (int) $sub->leader_id, 7, $sub->expiry_date );
				GLD_Subscription::mark_notified( (int) $sub->id, '7day' );
			}
		}
	}

	// ── Seat charge retry ─────────────────────────────────────────────────────

	private static function retry_failed_charges(): void {
		$max_retries = 3;
		$charges     = GLD_Seat_Charges_DB::get_retryable_failed( $max_retries );

		foreach ( $charges as $charge ) {
			$leader_id = (int) $charge->leader_id;
			$user_id   = (int) $charge->user_id;
			$group_id  = (int) $charge->group_id;

			if ( ! GLD_Billing::has_saved_card( $leader_id ) ) {
				GLD_Seat_Charges_DB::increment_retry( (int) $charge->id );
				continue;
			}

			$user = get_userdata( $user_id );
			$desc = sprintf(
				'Retry — 1 seat — %s — %s',
				get_the_title( $group_id ),
				$user ? $user->display_name : "user #{$user_id}"
			);

			$result = GLD_Billing::charge( $leader_id, (float) $charge->amount, $charge->currency, $desc );

			if ( $result['success'] ) {
				GLD_Seat_Charges_DB::update( (int) $charge->id, array(
					'status'      => 'completed',
					'flw_txn_ref' => $result['txn_ref'] ?? '',
					'flw_txn_id'  => $result['txn_id'] ?? '',
				) );

				// Enrol the user only if they aren't already in the group.
				$members = learndash_get_groups_user_ids( $group_id );
				if ( ! in_array( $user_id, array_map( 'intval', $members ), true ) ) {
					ld_update_group_access( $user_id, $group_id, false );
				}

				if ( $user ) {
					GLD_Notifications::charge_receipt( $leader_id, array(
						'group_id'     => $group_id,
						'user_name'    => $user->display_name,
						'user_email'   => $user->user_email,
						'amount'       => $charge->amount,
						'currency'     => $charge->currency,
						'period_start' => $charge->period_start,
						'period_end'   => $charge->period_end,
						'flw_ref'      => $result['txn_ref'] ?? '',
					) );
				}
			} else {
				GLD_Seat_Charges_DB::increment_retry( (int) $charge->id );
				$new_retry = (int) $charge->retry_count + 1;

				if ( $new_retry >= $max_retries && $user ) {
					// All retries exhausted — update status and notify leader.
					GLD_Seat_Charges_DB::update( (int) $charge->id, array( 'status' => 'failed_final' ) );
					GLD_Notifications::charge_failure_final( $leader_id, array(
						'group_id'  => $group_id,
						'user_name' => $user->display_name,
						'amount'    => $charge->amount,
						'currency'  => $charge->currency,
					) );
				}
			}
		}
	}

	private static function send_warning_email( int $group_id, int $leader_id, int $days, string $expiry_date ): void {
		$leader = get_userdata( $leader_id );
		if ( ! $leader ) {
			return;
		}

		$group_name = get_the_title( $group_id );
		$renew_url  = self::get_renew_url( $group_id );
		$site_name  = get_bloginfo( 'name' );
		$expiry_fmt = date( 'F j, Y', strtotime( $expiry_date ) );
		$day_word   = $days !== 1 ? 'days' : 'day';

		$subject = "[{$site_name}] Your group access expires in {$days} {$day_word}";

		$body = self::email_wrap( "
			<p>Hi {$leader->display_name},</p>
			<p>The annual course access for your group <strong>" . esc_html( $group_name ) . "</strong> expires on <strong>{$expiry_fmt}</strong> — in <strong>{$days} {$day_word}</strong>.</p>
			<p>Renew now to keep your learners' progress uninterrupted.</p>
			<p style='margin-top:24px;'>
				<a href='" . esc_url( $renew_url ) . "' style='background:#4f46e5;color:#fff;padding:12px 24px;border-radius:7px;text-decoration:none;font-weight:600;display:inline-block;'>Renew Annual Access</a>
			</p>
			<p style='margin-top:16px;color:#6b7280;font-size:12px;'>If you have already renewed, please ignore this email.</p>
		", $site_name );

		wp_mail( $leader->user_email, $subject, $body, self::html_headers() );
	}

	private static function send_expired_email( int $group_id, int $leader_id, string $expiry_date ): void {
		$leader = get_userdata( $leader_id );
		if ( ! $leader ) {
			return;
		}

		$group_name = get_the_title( $group_id );
		$renew_url  = self::get_renew_url( $group_id );
		$site_name  = get_bloginfo( 'name' );
		$expiry_fmt = date( 'F j, Y', strtotime( $expiry_date ) );

		$subject = "[{$site_name}] Group access expired — " . $group_name;

		$body = self::email_wrap( "
			<p>Hi {$leader->display_name},</p>
			<p>The annual course access for your group <strong>" . esc_html( $group_name ) . "</strong> expired on <strong>{$expiry_fmt}</strong>.</p>
			<p>Learner access to courses has been paused. Renew your subscription to restore access immediately.</p>
			<p style='margin-top:24px;'>
				<a href='" . esc_url( $renew_url ) . "' style='background:#4f46e5;color:#fff;padding:12px 24px;border-radius:7px;text-decoration:none;font-weight:600;display:inline-block;'>Renew Annual Access</a>
			</p>
		", $site_name );

		wp_mail( $leader->user_email, $subject, $body, self::html_headers() );
	}

	private static function send_admin_notification( int $group_id, string $expiry_date ): void {
		$admin_email = get_option( 'admin_email' );
		$group_name  = get_the_title( $group_id );
		$site_name   = get_bloginfo( 'name' );
		$expiry_fmt  = date( 'F j, Y', strtotime( $expiry_date ) );

		$subject = "[{$site_name}] Group access expired — " . $group_name;
		$message = "The group \"{$group_name}\" (ID: {$group_id}) had its course access expire on {$expiry_fmt}. Learner access has been paused pending renewal.";

		wp_mail( $admin_email, $subject, $message );
	}

	private static function get_renew_url( int $group_id ): string {
		$product_id  = (int) get_option( 'gld_access_product_id', 0 );
		$checkout_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
		$base        = $checkout_id ? get_permalink( $checkout_id ) : home_url( '/checkout/' );
		if ( ! $product_id ) {
			return (string) $base;
		}
		return add_query_arg( array( 'add-to-cart' => $product_id, 'gld_group' => $group_id ), $base );
	}

	private static function email_wrap( string $content, string $site_name ): string {
		$year = date( 'Y' );
		$name = esc_html( $site_name );
		return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f5f6fa;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;"><tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.1);overflow:hidden;">
  <tr><td style="background:#4f46e5;padding:24px 32px;"><span style="color:#fff;font-size:18px;font-weight:700;">' . $name . '</span></td></tr>
  <tr><td style="padding:32px;font-size:14px;color:#111827;line-height:1.7;">' . $content . '</td></tr>
  <tr><td style="padding:16px 32px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;">&copy; ' . $year . ' ' . $name . '. This is an automated message.</td></tr>
</table>
</td></tr></table></body></html>';
	}

	private static function html_headers(): array {
		return array( 'Content-Type: text/html; charset=UTF-8' );
	}
}
