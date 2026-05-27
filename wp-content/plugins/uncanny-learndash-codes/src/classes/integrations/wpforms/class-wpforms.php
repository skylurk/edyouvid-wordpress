<?php

namespace uncanny_learndash_codes;

/**
 * Class WPForms
 * @package uncanny_learndash_codes
 */
class WPForms extends Config {
	/**
	 * Store the ID value of coupon code
	 *
	 * @var $coupon_id
	 */
	private static $coupon_id;

	/**
	 * WPForms constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 999 );
	}

	/**
	 * Add hookable actions of WPForms
	 */
	public function plugins_loaded() {
		add_action( 'wpforms_process_validate_uncanny_code', array( $this, 'wpforms_check_if_code_valid' ), 100, 3 );
		add_action( 'wpforms_user_registered', array( $this, 'wpforms_user_registered' ), 100, 3 );
		add_action( 'wpforms_process_complete', array( $this, 'wpforms_process_complete' ), 1000, 4 );
	}

	/**
	 * @param $field_id
	 * @param $field_value
	 * @param $form_data
	 */
	public function wpforms_check_if_code_valid( $field_id, $field_value, $form_data ) {

		// Load default error messages.
		Config::load_default_messages();

		if ( empty( $field_value ) ) {
			wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Please enter valid code.', 'uncanny-learndash-codes' );

			return;
		}

		// Check if existing coupon or not!
		$coupon_id = SharedFunctionality::maybe_validate_coupon_code( $field_value );
		if ( null !== $coupon_id && is_numeric( $coupon_id ) ) {
			self::$coupon_id = intval( $coupon_id );
		} else {
			wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = $coupon_id;

			return;
		}
	}

	/**
	 * Run when user is registered through WPForms
	 *
	 * @param $user_id
	 * @param $fields
	 * @param $form_data
	 * @param $userdata
	 */
	public function wpforms_user_registered( $user_id, $fields, $form_data ) {
		if ( $form_data && isset( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $k => $v ) {

				if ( 'uncanny_code' !== (string) $v['type'] ) {
					continue;
				}

				$field_id = absint( $v['id'] );

				if ( isset( $fields[ $field_id ] ) ) {
					$value = $fields[ $field_id ]['value'];
					// Check if existing coupon or not!
					$coupon_id = Database::is_coupon_available( $value );
					if ( ! is_array( $coupon_id ) && is_numeric( (int) $coupon_id ) ) {
						update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'WPForms', 'uncanny-learndash-codes' ) );
						$result = Database::set_user_to_coupon( $user_id, $coupon_id );
						LearnDash::set_user_to_course_or_group( $user_id, $result );

						do_action( 'ulc_user_redeemed_code', $user_id, $coupon_id, $result, 'wpforms' );
					}
				}
			}
		}
	}

	/**
	 * Run when the entry process is completed by WPForms
	 *
	 * @param $fields
	 * @param $entry
	 * @param $form_data
	 * @param $entry_id
	 */
	public function wpforms_process_complete( $fields, $entry, $form_data, $entry_id ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$user_id = wp_get_current_user()->ID;
		if ( $form_data && isset( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $k => $v ) {

				if ( 'uncanny_code' !== (string) $v['type'] ) {
					continue;
				}

				$field_id = absint( $v['id'] );

				if ( isset( $fields[ $field_id ] ) ) {
					$value = $fields[ $field_id ]['value'];
					// Check if existing coupon or not!
					$coupon_id = Database::is_coupon_available( $value );
					if ( ! is_array( $coupon_id ) && is_numeric( (int) $coupon_id ) ) {
						update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'WPForms', 'uncanny-learndash-codes' ) );
						$result = Database::set_user_to_coupon( $user_id, $coupon_id );
						LearnDash::set_user_to_course_or_group( $user_id, $result );

						do_action( 'ulc_user_redeemed_code', $user_id, $coupon_id, $result, 'wpforms' );
					}
				}
			}
		}
	}
}
