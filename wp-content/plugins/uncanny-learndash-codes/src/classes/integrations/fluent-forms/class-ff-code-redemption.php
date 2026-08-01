<?php

namespace uncanny_learndash_codes;

/**
 *
 */
class Fluent_Forms_Code_Redemption extends Config {
	/**
	 * Store the ID value of coupon code
	 *
	 * @var $coupon_id
	 */
	private static $coupon_id;
	/**
	 * @var string
	 */
	private $key = 'uo_codes_field';

	/**
	 * Form Submitted Data
	 *
	 * @var $submtted_data
	 */
	private static $submitted_data;

	/**
	 * Fluent_Forms constructor.
	 */
	public function __construct() {
		add_action(
			'fluentform/loaded',
			function () {
				new FluentFormCodesField();
			}
		);
		add_filter( 'fluentform_validate_input_item_' . $this->key, array( $this, 'validate_input' ), 10, 5 );
		add_action( 'fluentform_before_insert_submission', array( $this, 'redeem_uncanny_code' ), 10, 3 );
		add_action( 'fluentform_user_registration_completed', array( $this, 'user_registration_completed' ), 10, 3 );
	}

	/**
	 * @param $error_message
	 * @param $field
	 * @param $form_data
	 * @param $fields
	 * @param $form
	 *
	 * @return array
	 */
	public function validate_input( $error_message, $field, $form_data, $fields, $form ) {

		$field_name = $field['name'];
		if ( empty( $form_data[ $field_name ] ) ) {
			return $error_message;
		}

		// Load default error messages.
		$field_value = $form_data[ $field_name ]; // This is the user input value
		$coupon_id   = SharedFunctionality::maybe_validate_coupon_code( $field_value );
		if ( null !== $coupon_id && is_numeric( $coupon_id ) ) {
			self::$coupon_id = intval( $coupon_id );
		} else {
			$error_message = $coupon_id;
		}

		return $error_message;
	}


	/**
	 * @param $insert_data
	 * @param $submitted_data
	 * @param $form_data
	 *
	 * @return void
	 */
	public function redeem_uncanny_code( $insert_data, $submitted_data, $form_data ) {

		if ( empty( $submitted_data ) ) {
			return;
		}

		self::$submitted_data = $submitted_data;

		if ( isset( self::$submitted_data['ulc_user_redeemed_code'] ) &&
			1 === (int) self::$submitted_data['ulc_user_redeemed_code']
		) {
			return; // already processed.
		}

		$user_id = isset( $insert_data['user_id'] ) ? absint( $insert_data['user_id'] ) : wp_get_current_user()->ID;

		// User id must be detected for redemption.
		if ( empty( $user_id ) ) {
			return;
		}

		foreach ( $submitted_data as $key => $value ) {
			if ( (string) $key !== (string) $this->key ) {
				continue;
			}
			$coupon_id = self::$coupon_id;
			if ( is_numeric( $coupon_id ) ) {
				update_user_meta( $user_id, Config::$uncanny_codes_tracking, 'Fluent Forms' );
				$result = Database::set_user_to_coupon( $user_id, $coupon_id );
				LearnDash::set_user_to_course_or_group( $user_id, $result );

				self::$submitted_data['ulc_user_redeemed_code'] = 1;
				do_action( 'ulc_user_redeemed_code', $user_id, $coupon_id, $result, 'fluentforms' );
			}
		}
	}


	/**
	 * @param $user_id
	 * @param $feed
	 * @param $entry
	 *
	 * @return void
	 */
	public function user_registration_completed( $user_id, $feed, $entry ) {

		if ( empty( $user_id ) || empty( $feed ) || empty( self::$submitted_data ) ) {
			return;
		}

		if ( isset( self::$submitted_data['ulc_user_redeemed_code'] ) ) {
			return; // already processed.
		}

		self::redeem_uncanny_code(
			array(
				'user_id' => $user_id,
			),
			self::$submitted_data,
			array()
		);
	}
}
