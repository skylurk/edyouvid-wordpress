<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class SendReminderEmails
 * @package uncanny_ceu
 */
class SendReminderEmails {

	/**
	 * class constructor
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'maybe_send_reminder_emails' ) );
	}

	/**
	 * Send reminder emails, maybe
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function maybe_send_reminder_emails() {

		// Get rollover date
		$roll_over_date = get_option( 'ceu_rollover_date', false );
		//var_dump($roll_over_date);die;
		if ( $roll_over_date && '' !== $roll_over_date ) {
			$rolled_over_date = \DateTime::createFromFormat( 'd\/m', $roll_over_date );
			if ( $rolled_over_date && $rolled_over_date->format( 'd\/m' ) === $roll_over_date ) {
				$rolled_over_date->setTime( 0, 0, 0 );
				$roll_over_date = $rolled_over_date;
			} else {
				return null;
			}

		} // If roll over date not set, then reminders don't get sent out
		else {
			return null;
		}
	
		// Check if we are sending out reminders
		$send_email_reminders = get_option( 'send_email_reminders', 'off' );
		if ( 'on' !== $send_email_reminders ) {
			return null;
		}

		// Check current date
		$current_date = new \DateTime( 'NOW' );

		// Get days before rollover date for reminder
		$days_before_rollover_date_for_reminder = get_option( 'days_before_rollover_date_for_reminder', 0 );
		if ( $days_before_rollover_date_for_reminder && '' !== $days_before_rollover_date_for_reminder ) {
			$days_before_rollover_date_for_reminder = absint( $days_before_rollover_date_for_reminder );
		} // If days before roll over date for reminder is  not set, then reminders don't get sent out
		else {
			return null;
		}

		// Compute day that emails are to be sent out on
		$date_to_be_sent = $roll_over_date;
		$date_to_be_sent->sub( new \DateInterval( 'P' . $days_before_rollover_date_for_reminder . 'D' ) );

		// If today is the day or the day has passed, maybe send out emails
		if ( $date_to_be_sent->format( 'U' ) <= $current_date->format( 'U' ) ) {


			// Did they already get sent out
			$sent_on = $date_to_be_sent->format( 'Y_m_d' );

			$ceu_reminders_sent = get_option( 'ceu_reminders_sent_' . $sent_on, 'no' );

			if ( 'yes' === $ceu_reminders_sent ) {
				// reminders already sent
				return null;
			}

			// Send out reminders!
			$this->send_emails();
			update_option( 'ceu_reminders_sent_' . $sent_on, 'yes' );

		}

		return null;
	}

	/**
	 * Send reminder emails
	 *
	 * @since 1.0
	 */
	public function send_emails() {

		// Get user data that has specific data for this email
		$users = $this->get_user_data();

		// Get subject
		$default_subject        = __( 'You Have Not Earned Enough #ceu_plural', 'uncanny-ceu' );
		$reminder_email_subject = get_option( 'reminder_email_subject', $default_subject );

		// Get Body
		$default_body        = __( "Hi #first_name,\r\nThis is a friendly reminder that you must earn #credits_required #ceu_plural before #rollover_date. You currently have #earned_since_rollover #ceu_plural.", 'uncanny-ceu' );
		$reminder_email_body = get_option( 'reminder_email_body', $default_body );

		$credit_designation_label_plural = Utilities::credit_designation_label('plural', __( 'CEUs', 'uncanny-ceu' ) );

		$credit_designation_label_single = Utilities::credit_designation_label( 'singular', __( 'CEU', 'uncanny-ceu' ) );

		$roll_over_date = get_option( 'ceu_rollover_date', false );
		$roll_over      = '';

		if ( $roll_over_date && '' !== $roll_over_date ) {
			$roll_over_date = \DateTime::createFromFormat( 'd\/m', $roll_over_date );
			$roll_over_date->setTime( 0, 0, 0 );
			$roll_over = $roll_over_date->format( 'F d, Y' );
		}

		// Loop user
		foreach ( $users as $ID => $user ) {

			if ( isset( $user['email'] ) ) {
				if ( ! is_email( $user['email'] ) ) {
					// email is not valid... move on
					continue;
				}
			} else {
				// email is not set... move on
				continue;
			}

			$highest_credit_required = 0;

			// Highest Credit required
			// Both individual and group based requirements are set
			if ( isset( $user['ceu_required_personal'] ) && isset( $user['highest_group_credit_required'] ) ) {
				if ( $user['ceu_required_personal'] < $user['highest_group_credit_required'] ) {
					$highest_credit_required = $user['highest_group_credit_required'];
				} else {
					$highest_credit_required = $user['ceu_required_personal'];
				}
			} // Ony individual requirement is set
			elseif ( isset( $user['ceu_required_personal'] ) ) {
				$highest_credit_required = $user['ceu_required_personal'];
			} // Ony group requirement is set
			elseif ( isset( $user['highest_group_credit_required'] ) ) {
				$highest_credit_required = $user['ceu_required_personal'];
			}

			if ( 0 == $highest_credit_required ) {
				continue;
			}

			$replacements = array(
				'#user_email'            => $user['email'],
				'#first_name'            => ( isset( $user['first_name'] ) ) ? $user['first_name'] : '',
				'#last_name'             => ( isset( $user['last_name'] ) ) ? $user['last_name'] : '',
				'#credits_required'      => $highest_credit_required,
				'#ceu_earned'            => ( isset( $user['ceu_earned_expired'] ) && isset( $user['ceu_earned_current'] ) ) ? ( $user['ceu_earned_expired'] + $user['ceu_earned_current'] ) : '',
				'#earned_since_rollover' => ( isset( $user['ceu_earned_current'] ) ) ? $user['ceu_earned_current'] : '',
				'#rollover_date'         => $roll_over,
				'#ceu_plural'            => $credit_designation_label_plural,
				'#ceu_single'            => $credit_designation_label_single
			);

			if ( (int) $replacements['#earned_since_rollover'] >= (int) $highest_credit_required ) {
				continue;
			}

			// parse subject values
			$subject = $reminder_email_subject;

			foreach ( $replacements as $replacement_hash => $replacements_value ) {
				$subject = str_replace( $replacement_hash, $replacements_value, $subject );
			}

			// Parse body values
			$body = $reminder_email_body;
			foreach ( $replacements as $replacement_hash => $replacements_value ) {
				$body = str_replace( $replacement_hash, $replacements_value, $body );
			}


			// send email
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			wp_mail( $user['email'], $subject, wpautop( $body ), $headers );

		}

		return null;
	}

	/**
	 * Collect user data
	 */
	private function get_user_data() {

		global $wpdb;

		$roll_over_date = Utilities::rollover_date_as_unix();

		$query = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = 'group_credit_required'";

		$group_credits_required = $wpdb->get_results( $query );
		$g_credits              = array();

		foreach ( $group_credits_required as $group_credit_required ) {
			$g_credits[ $group_credit_required->post_id ] = $group_credit_required;
		}

		$query = "SELECT ID, user_email FROM $wpdb->users";

		$users = $wpdb->get_results( $query );

		$ceu_completions = array();

		foreach ( $users as $user ) {

			$user_meta = get_user_meta( $user->ID );

			$first_name = $user_meta['first_name'][0];
			$last_name  = $user_meta['last_name'][0];
			$email      = $user->user_email;

			foreach ( $user_meta as $key => $value ) {

				if ( ! isset( $ceu_completions[ $user->ID ] ) ) {
					$ceu_completions[ $user->ID ]                       = array();
					$ceu_completions[ $user->ID ]['first_name']         = $first_name;
					$ceu_completions[ $user->ID ]['last_name']          = $last_name;
					$ceu_completions[ $user->ID ]['email']              = $email;
					$ceu_completions[ $user->ID ]['ceu_earned_current'] = 0;
					$ceu_completions[ $user->ID ]['ceu_earned_expired'] = 0;

				}

				if ( 'individual_credit_required' === $key ) {
					$ceu_completions[ $user->ID ]['ceu_required_personal'] = (float) $value[0];
				}

				if ( false !== strpos( $key, 'ceu_earned' ) ) {

					$x_key = explode( '_', $key );

					$ceu_date_earned = absint( $x_key[2] ); // Unix timestamp


					if ( $ceu_date_earned && $roll_over_date && $ceu_date_earned < (int) $roll_over_date->format( 'U' ) ) {
						// after roll over
						$ceu_completions[ $user->ID ]['ceu_earned_current'] += (float) $value[0];
					} else {
						$ceu_completions[ $user->ID ]['ceu_earned_expired'] += (float) $value[0];
					}
				}

				// Found a group that the user is part of
				if ( false !== strpos( $key, 'learndash_group_users_' ) ) {

					// In some cases the meta value which is the group ID in nullified... not sure why but the user is not in the group anymore
					if ( ! absint( $value[0] ) ) {
						continue;
					}

					// Set group ID
					$group_id = absint( $value[0] );

					// Does group ID have a required credit
					if ( isset( $g_credits[ $group_id ] ) ) {

						// The first credit required will always be the highest
						if ( ! isset( $ceu_completions[ $user->ID ]['highest_group_credit_required'] ) ) {
							$ceu_completions[ $user->ID ]['highest_group_credit_required'] = $g_credits[ $group_id ]->meta_value;;
						} // its already set and we found another, if the new value is higher then lets set that as the highest
						elseif ( $ceu_completions[ $user->ID ]['highest_group_credit_required'] < $g_credits[ $group_id ] ) {
							$ceu_completions[ $user->ID ]['highest_group_credit_required'] = $g_credits[ $group_id ]->meta_value;;
						}
					}
					continue;
				}

			}
		}

		return $ceu_completions;

	}


	/**
	 * Get User date with meta keys
	 *
	 * @param array $user_keys
	 * @param array $exact_meta_keys
	 * @param array $fuzzy_meta_keys
	 *
	 * @return array
	 */
	function get_users_with_meta( $user_keys = array(), $exact_meta_keys = array(), $fuzzy_meta_keys = array() ) {

		global $wpdb;

		// Collect all possible meta_key values
		$keys = $wpdb->get_col( "SELECT distinct meta_key FROM $wpdb->usermeta" );

		//then prepare the meta keys query as fields which we'll join to the user table fields
		$meta_columns = '';
		foreach ( $keys as $key ) {


			if ( ! empty( $exact_meta_keys ) ) {
				if ( in_array( $key, $exact_meta_keys ) ) {
					$meta_columns .= " MAX(CASE WHEN um1.meta_key = '$key' THEN um1.meta_value ELSE NULL END) AS '$key', \n";
					continue;
				}
			}


			if ( ! empty( $fuzzy_meta_keys ) ) {
				foreach ( $fuzzy_meta_keys as $fuzzy_key ) {
					if ( false !== strpos( $key, $fuzzy_key ) ) {
						$meta_columns .= " MAX(CASE WHEN um1.meta_key = '$key' THEN um1.meta_value ELSE NULL END) AS '$key', \n";
					}
				}

			}


		}

		//then write the main query with all of the regular fields and use a simple left join on user users.ID and usermeta.user_id
		$query = "
SELECT  
    u.ID,
    u.user_login,
    u.user_pass,
    u.user_nicename,
    u.user_email,
    u.user_url,
    u.user_registered,
    u.user_activation_key,
    u.user_status,
    u.display_name,
    " . rtrim( $meta_columns, ", \n" ) . " 
FROM 
    $wpdb->users u
LEFT JOIN 
    $wpdb->usermeta um1 ON (um1.user_id = u.ID)
GROUP BY 
    u.ID";

		$users = $wpdb->get_results( $query, ARRAY_A );

		return array(
			'query'   => $query,
			'results' => $users
		);


	}
}