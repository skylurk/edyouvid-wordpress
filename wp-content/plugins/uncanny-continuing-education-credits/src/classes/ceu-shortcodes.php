<?php

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Class CeuCapabilities
 * @package uncanny_ceu
 */
class CeuShortcodes {

	/**
	 * class constructor
	 *
	 */
	function __construct() {

		// Shortcode for CEU value that cab be earned for course
		add_shortcode( 'uo_ceu_available', array( $this, 'uo_ceu_available' ) );

		// Shortcode for CEU value earned per course
		add_shortcode( 'uo_ceu_earned', array( $this, 'uo_ceu_earned' ) );

		// Shortcode for CEU value earned cumulatively
		add_shortcode( 'uo_ceu_total', array( $this, 'uo_ceu_total' ) );

		// Shortcode for CEU value earned cumulatively on or after roll over date
		add_shortcode( 'uo_ceu_total_rollover', array( $this, 'uo_ceu_total_rollover' ) );

		// Retrieve Certificate CEU trigger description
		add_shortcode( 'uo_ceu_certificate_description', array( $this, 'uo_ceu_certificate_description' ) );

		// Retrieve the number of days until roll over
		add_shortcode( 'uo_ceu_days_remaining', array( $this, 'uo_ceu_days_remaining' ) );

		// Retrieve the difference between required credits and earned credits
		add_shortcode( 'uo_ceu_credits_remaining', array( $this, 'uo_ceu_credits_remaining' ) );

	}


	/**
	 *
	 * Get the CEU value for a given course
	 *
	 * @param array $atts
	 *
	 * @return Int $value CEU Value
	 *
	 */
	public function uo_ceu_available( $atts ) {

		if ( ! function_exists( 'learndash_get_course_id' ) ) {
			return 0;
		}

		$atts = shortcode_atts(
			array(
				'course-id' => '',
			),
			$atts,
			'uo_ceu_available'
		);

		$course_id = absint( $atts['course-id'] );

		// Course ID was passed
		if ( 0 !== $course_id ) {

			$course_object = get_post( $course_id );
			if ( is_object( $course_object ) ) {
				// Verify that ID passed was actually a course
				if ( 'sfwd-courses' !== $course_object->post_type ) {
					// ID passed was not a course, detect course id
					$course_id = (int) learndash_get_course_id( $course_object );
				}
			}
		}

		if ( 0 === $course_id ) {
			global $post;

			if ( is_object( $post ) && $post instanceof \WP_Post ) {

				if ( 'sfwd-courses' !== $post->post_type ) {
					// Detect course id
					$course_id = (int) learndash_get_course_id( $post );
				} else {
					$course_id = $post->ID;
				}
			}
		}

		if ( 0 === (int) $course_id ) {
			// Couldn't get a proper course
			return '';
		}

		$ceu_value = get_post_meta( (int) $course_id, 'ceu_value', true );

		if ( '' === $ceu_value ) {
			$ceu_value = 0;
		}

		return $ceu_value;
	}

	/**
	 *
	 * Get the CEU value earned for a given course
	 *
	 * @param array $atts
	 *
	 * @return Int $value CEU Value
	 *
	 */
	public function uo_ceu_earned( $atts ) {

		$atts = shortcode_atts(
			array(
				'course-id'      => '',
				'user-id'        => '',
				'since-rollover' => 'all',
			),
			$atts,
			'uo_ceu_available'
		);

		$course_id = absint( $atts['course-id'] );

		if ( absint( $atts['user-id'] ) ) {
			$user_id = absint( $atts['user-id'] );
		} else {
			$user_id = get_current_user_id();
		}


		if ( ! $user_id ) {
			return __( '0', 'uncanny-ceu' );
		}

		// Course ID was passed
		if ( 0 !== $course_id ) {

			$course_object = get_post( $course_id );

			// Verify that ID passed was actually a course
			if ( 'sfwd-courses' !== $course_object->post_type ) {
				// ID passed was not a course, reset course id
				$course_id = 0;
			}

		}

		if ( 0 === $course_id ) {
			global $post;
			if ( 'sfwd-courses' !== $post->post_type ) {

				// Couldn't get a proper course
				return __( '0', 'uncanny-ceu' );
			} else {
				$course_id = $post->ID;
			}
		}

		$user_meta = get_user_meta( $user_id );

		// Store amount credits earned, a user accumulates credits for multiple course completions
		$earned_course_credits        = '';
		$earned_course_credits_all    = '';
		$earned_course_credits_before = '';
		$earned_course_credits_after  = '';

		$roll_over_date = Utilities::rollover_date_as_unix();

		// Loop through all meta to find possible credits earned
		foreach ( $user_meta as $meta_key => $meta_value ) {

			// Catch any earned credit
			if ( false !== strpos( $meta_key, 'ceu_earned_' ) ) {

				// Break meta key down into variables... Pattern ceu_earned_{date earned}_{course id}
				$meta_key_vars   = explode( '_', $meta_key );
				$ceu_date_earned = absint( $meta_key_vars[2] ); // Unix timestamp
				$ceu_course_id   = absint( $meta_key_vars[3] ); // Post ID
				$ceu_earned      = (float) $meta_value[0];

				// CEU value did not validate as a real number, most likely the course meta was not set
				if ( ! $ceu_earned ) {
					continue;
				}

				if ( $ceu_course_id === $course_id ) {

					if ( '' === $earned_course_credits_all ) {
						$earned_course_credits_all = $ceu_earned;
					} else {
						$earned_course_credits_all += $ceu_earned;
					}

					if ( $roll_over_date ) {

						if ( $ceu_date_earned && $ceu_date_earned > (int) $roll_over_date->format( 'U' ) ) {
							// before roll over
							if ( '' === $earned_course_credits_before ) {
								$earned_course_credits_before = $ceu_earned;
							} else {
								$earned_course_credits_before += $ceu_earned;
							}

						} else {
							// after roll over
							if ( '' === $earned_course_credits_after ) {
								$earned_course_credits_after = $ceu_earned;
							} else {
								$earned_course_credits_after += $ceu_earned;
							}
						}

					} else {
						if ( '' === $earned_course_credits ) {
							$earned_course_credits = $ceu_earned;
						} else {
							$earned_course_credits += $ceu_earned;
						}
					}
				}
			}
		}

		if ( 'all' === $atts['since-rollover'] ) {
			if ( '' === $earned_course_credits_all ) {
				$earned_course_credits_all = __( '0', 'uncanny-ceu' );
			}

			return $earned_course_credits_all;
		}

		// Rollover not active. Return all earned credits
		if ( ! $roll_over_date ) {

			if ( '' === $earned_course_credits ) {
				$earned_course_credits = __( '0', 'uncanny-ceu' );
			}

			return $earned_course_credits;
		}

		// Rollover active. Return credits after rollover
		if ( $roll_over_date && 'yes' === $atts['since-rollover'] ) {

			if ( '' === $earned_course_credits_after ) {
				$earned_course_credits_after = __( '0', 'uncanny-ceu' );
			}

			return $earned_course_credits_after;
		}

		// Rollover active. Return all earned credits
		if ( $roll_over_date && 'no' === $atts['since-rollover'] ) {

			if ( '' === $earned_course_credits ) {
				$earned_course_credits = __( '0', 'uncanny-ceu' );
			}

			return $earned_course_credits;
		}

		return __( '0', 'uncanny-ceu' );
	}

	/**
	 * Shortcode for CEU value earned cumulatively
	 *
	 * @param array $atts
	 *
	 * @return Int $value CEU Value
	 *
	 */
	function uo_ceu_total( $atts ) {

		$atts = shortcode_atts(
			array(
				'user-id' => 0,
			), $atts, 'uo_ceu_total' );

		$user_id = absint( $atts['user-id'] );

		// User ID was passed
		if ( 0 === $user_id ) {

			$user_id = get_current_user_id();

		}

		if ( ! $user_id ) {
			return __( '0', 'uncanny-ceu' );
		}

		$total_earned = 0;

		$user_meta = get_user_meta( $user_id );

		// Loop through all meta to find possible credits earned
		foreach ( $user_meta as $meta_key => $meta_value ) {

			// Catch any earned credit
			if ( false !== strpos( $meta_key, 'ceu_earned_' ) ) {

				$ceu_earned = (float) $meta_value[0];

				// CEU value did not validate as a real number, most likely the course meta was not set
				if ( ! $ceu_earned ) {
					continue;
				}

				$total_earned += $ceu_earned;

			}
		}

		return $total_earned;
	}

	/**
	 * Shortcode for CEU value earned cumulatively after role over date
	 *
	 * @param array $atts
	 *
	 * @return Int $value CEU Value
	 *
	 */
	function uo_ceu_total_rollover( $atts ) {

		$atts = shortcode_atts(
			array(
				'user-id'  => '',
				'rollover' => 'after',
			), $atts, 'uo_ceu_available' );

		$user_id = absint( $atts['user-id'] );

		// User ID was passed
		if ( 0 === $user_id ) {

			$user_id = get_current_user_id();

		}

		if ( ! $user_id ) {
			return __( '0', 'uncanny-ceu' );
		}

		$earned_credits_before = 0;
		$earned_credits_after  = 0;

		$roll_over_date = Utilities::rollover_date_as_unix();

		if ( ! $roll_over_date ) {
			return __( '0', 'uncanny-ceu' );
		}

		$user_meta = get_user_meta( $user_id );

		// Loop through all meta to find possible credits earned
		foreach ( $user_meta as $meta_key => $meta_value ) {

			// Catch any earned credit
			if ( false !== strpos( $meta_key, 'ceu_earned_' ) ) {

				// Break meta key down into variables... Pattern ceu_earned_{date earned}_{course id}
				$meta_key_vars   = explode( '_', $meta_key );
				$ceu_date_earned = absint( $meta_key_vars[2] ); // Unix timestamp
				$ceu_earned      = (float) $meta_value[0];

				// CEU value did not validate as a real number, most likely the course meta was not set
				if ( ! $ceu_earned ) {
					continue;
				}

				if ( $ceu_date_earned && $ceu_date_earned < (int) $roll_over_date->format( 'U' ) ) {
					// before roll over
					$earned_credits_before += $ceu_earned;
				} else {
					// after roll over
					$earned_credits_after += $ceu_earned;
				}
			}
		}

		if ( 'after' === $atts['rollover'] ) {
			return $earned_credits_after;
		}

		if ( 'before' === $atts['rollover'] ) {
			return $earned_credits_before;
		}

		return '';
	}

	/**
	 *
	 * Get the certifcate CEU trigger description
	 *
	 * @param array $atts
	 *
	 * @return Int $value CEU Value
	 *
	 */
	function uo_ceu_certificate_description( $atts ) {

		$atts = shortcode_atts(
			array(
				'certificate-id' => 0,
			), $atts, 'uo_ceu_available' );

		$certificate_id = absint( $atts['certificate-id'] );

		// Certificate ID was passed
		if ( 0 !== $certificate_id ) {

			$certificate_object = get_post( $certificate_id );

			// Verify that ID passed was actually a certificate
			if ( 'sfwd-certificates' !== $certificate_object->post_type ) {
				// ID passed was not a certificate, reset certificate id
				$certificate_id = 0;
			}

		}

		if ( 0 === $certificate_id ) {
			global $post;
			if ( 'sfwd-certificates' !== $post->post_type ) {

				// Couldn't get a proper certificate
				return __( '0', 'uncanny-ceu' );
			} else {
				$certificate_id = $post->ID;
			}
		}

		$certificate_trigger_description = get_post_meta( (int) $certificate_id, 'ceu_cert_description', true );

		if ( '' === $certificate_trigger_description ) {
			$certificate_trigger_description = __( '0', 'uncanny-ceu' );
		}

		return $certificate_trigger_description;
	}

	/**
	 * Get amount of days remainining before roll over
	 *
	 * @param array $atts
	 *
	 * @return Int $value CEU Value
	 *
	 */
	function uo_ceu_days_remaining( $atts ) {

		$atts = shortcode_atts(
			array(
				'user-id' => 0,
			), $atts, 'uo_ceu_days_remaining' );

		$user_id = absint( $atts['user-id'] );

		// User ID was passed
		if ( 0 === $user_id ) {

			$user_id = get_current_user_id();

		}

		if ( ! $user_id ) {
			return __( '', 'uncanny-ceu' );
		}

		$roll_over_date = get_option( 'ceu_rollover_date', false );

		if ( $roll_over_date ) {
			$roll_over_date = \DateTime::createFromFormat( 'd\/m', $roll_over_date );

			if ( $roll_over_date ) {
				$roll_over_date->setTime( 0, 0, 0 );
			}
		} else {
			return __( '', 'uncanny-ceu' );
		}

		$current_date = new \DateTime( 'NOW' );

		$interval = $current_date->diff( $roll_over_date );

		if ( $current_date > $roll_over_date ) {
			$days_remaining = 365 - $interval->days;

			return $days_remaining;

		}

		$days_remaining = $interval->days;

		return $days_remaining;

	}

	/**
	 * Get the amount of credits for succession
	 *
	 * @param array $atts
	 *
	 * @return Int $value CEU Value
	 *
	 */
	function uo_ceu_credits_remaining( $atts ) {

		$atts = shortcode_atts(
			array(
				'user-id' => 0,
			), $atts, 'uo_ceu_days_remaining' );

		$user_id = absint( $atts['user-id'] );

		// User ID was passed
		if ( 0 === $user_id ) {

			$user_id = get_current_user_id();

		}

		if ( ! $user_id ) {
			return '';
		}

		$roll_over_date = Utilities::rollover_date_as_unix();

		if ( ! $roll_over_date ) {
			return '';
		}

		$earned_credits_before = 0;
		$earned_credits_after  = 0;

		$user_meta = get_user_meta( $user_id );

		// Loop through all meta to find possible credits earned
		foreach ( $user_meta as $meta_key => $meta_value ) {

			// Catch any earned credit
			if ( false !== strpos( $meta_key, 'ceu_earned_' ) ) {

				// Break meta key down into variables... Pattern ceu_earned_{date earned}_{course id}
				$meta_key_vars   = explode( '_', $meta_key );
				$ceu_date_earned = absint( $meta_key_vars[2] ); // Unix timestamp
				$ceu_earned      = (float) $meta_value[0];

				// CEU value did not validate as a real number, most likely the course meta was not set
				if ( ! $ceu_earned ) {
					continue;
				}

				if ( $ceu_date_earned && $ceu_date_earned > $roll_over_date->format( 'U' ) ) {
					// before roll over
					$earned_credits_before += $ceu_earned;
				} else {
					// after roll over
					$earned_credits_after += $ceu_earned;
				}


			}
		}

		$individual_credit_required = get_user_meta( $user_id, 'individual_credit_required', true );

		$user_groups = array();
		if ( function_exists( 'learndash_get_users_group_ids' ) ) {
			$user_groups = learndash_get_users_group_ids( $user_id, true );
		}

		if ( empty( $user_groups ) ) {
			$highest_group_credit = 0;
		} else {
			global $wpdb;
			$user_groups          = implode( ',', $user_groups );
			$query                = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'group_credit_required' && post_id IN ($user_groups) ORDER BY  meta_value * 1  DESC LIMIT 1";
			$highest_group_credit = $wpdb->get_var( $query );
		}

		if ( (float) $highest_group_credit > (float) $individual_credit_required ) {
			$total_credits = (float) $highest_group_credit;
		} else {
			$total_credits = (float) $individual_credit_required;
		}


		$credits_remaining = $total_credits - $earned_credits_after;

		if ( 0 > $credits_remaining ) {
			$credits_remaining = __( '0', 'uncanny-ceu' );
		}

		return $credits_remaining;
	}
}
