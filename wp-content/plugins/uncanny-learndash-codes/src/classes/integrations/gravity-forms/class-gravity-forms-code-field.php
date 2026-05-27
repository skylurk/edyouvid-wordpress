<?php

namespace uncanny_learndash_codes;

use GFAddOn;
use GFAPI;

/**
 * Class GravtiyFormsCodeField
 * @package uncanny_learndash_codes
 */
class GravityFormsCodeField extends Config {
	/**
	 * @var
	 */
	public static $form_id;
	/**
	 * @var
	 */
	public static $coupon_id;

	/**
	 * GravityFormsCodeField constructor.
	 */
	public function __construct() {
		add_action( 'gform_loaded', array( $this, 'load' ), 5 );
		add_action( 'gform_loaded', array( $this, 'handle_gravity_forms' ), 20 );
	}

	/**
	 * @param $user_id
	 */
	public static function gf_user_register( $user_id ) {
		if ( intval( self::$coupon_id ) && SharedFunctionality::ulc_filter_has_var( 'gform_submit', INPUT_POST ) ) {
			update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'Gravity Forms', 'uncanny-learndash-codes' ) );
			$result = Database::set_user_to_coupon( $user_id, self::$coupon_id );
			LearnDash::set_user_to_course_or_group( $user_id, $result );

			do_action( 'ulc_user_redeemed_code', $user_id, self::$coupon_id, $result, 'gravityforms' );
		}
	}

	/**
	 * @param $user_id
	 * @param $user_data
	 * @param $entry_meta
	 */
	public static function gf_user_register_activation( $user_id, $user_data, $entry_meta ) {
		$code_field = self::get_code_from_entry( $entry_meta['id'] );
		if ( false === $code_field ) {
			return;
		}
		self::redeem_code( $code_field, $user_id );
	}

	/**
	 *
	 */
	public function load() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once self::get_include( 'class-gf-code-field-add-on.php' );

		GFAddOn::register( 'GFCodeFieldAddOnCodes' );
	}

	/**
	 *
	 */
	public function handle_gravity_forms() {
		// Add Custom Validation for Code Redemption Field.
		add_filter( 'gform_validation', array( $this, 'custom_validation' ) );
		// Registration completed.
		add_action( 'user_register', array( __CLASS__, 'gf_user_register' ), 15 );
		//add_action( 'gform_activate_user', array( __CLASS__, 'gf_user_register_activation' ), 15, 3 );
		add_action( 'gform_after_submission', array( __CLASS__, 'gform_after_submission_func' ), 99, 2 );
		add_action( 'gform_user_registered', array( __CLASS__, 'gf_user_registered' ), 10, 3 );
	}

	/**
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	public function custom_validation( $validation_result ) {
		$form              = $validation_result['form'];
		$code_redeem_field = false;
		$field_id          = 0;

		if ( $form['fields'] ) {
			foreach ( $form['fields'] as $field ) {
				if ( 'uncanny_enrollment_code' === $field->type ) {
					$field_id          = $field->id;
					$code_redeem_field = true;
					$code_redemption   = rgpost( 'input_' . $field->id );
					break;
				}
			}
		}

		// ------------------------------------------ //

		if ( $code_redeem_field && ! empty( $code_redemption ) ) {
			$coupon_id = SharedFunctionality::maybe_validate_coupon_code( $code_redemption );
			if ( null !== $coupon_id && is_numeric( $coupon_id ) ) {
				self::$coupon_id = intval( $coupon_id );
			} else {
				$validation_result['is_valid'] = false;
				foreach ( $form['fields'] as &$field ) {
					if ( $field_id === $field->id ) {
						$field->failed_validation  = true;
						$field->validation_message = $coupon_id;
						break;
					}
				}
			}
		}

		$validation_result['form'] = $form;

		return $validation_result;
	}

	/**
	 * @param $entry
	 * @param $form
	 */
	public static function gform_after_submission_func( $entry, $form ) {
		// only run for logged in users
		if ( ! is_user_logged_in() ) {
			return;
		}
		$code_redeem_field = false;
		$code_redemption   = null;
		foreach ( $form['fields'] as $field ) {
			if ( 'uncanny_enrollment_code' !== $field->type ) {
				continue;
			}
			$code_redeem_field = true;
			$code_redemption   = rgar( $entry, (string) $field->id );
			break;
		}
		if ( false === $code_redeem_field ) {
			return;
		}
		if ( empty( $code_redemption ) ) {
			return;
		}
		$user_id   = wp_get_current_user()->ID;
		$coupon_id = Database::is_coupon_available( $code_redemption );

		if ( true !== apply_filters( 'ulc_redeem_code_for_current_logged_in_user', true, $entry, $form, $user_id, $code_redemption, $coupon_id ) ) {
			return;
		}

		if ( is_numeric( intval( $coupon_id ) ) ) {
			update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'Gravity Forms', 'uncanny-learndash-codes' ) );
			$result = Database::set_user_to_coupon( $user_id, $coupon_id );
			LearnDash::set_user_to_course_or_group( $user_id, $result );

			do_action( 'ulc_user_redeemed_code', $user_id, $coupon_id, $result, 'gravityforms' );
		}

	}

	/**
	 * @param $user_id
	 * @param $feed
	 * @param $entry
	 *
	 * @return void
	 */
	public static function gf_user_registered( $user_id, $feed, $entry ) {
		$code_field = self::get_code_from_entry( $entry['id'] );
		if ( false === $code_field ) {
			return;
		}
		self::redeem_code( $code_field, $user_id );
	}

	/**
	 * @param $entry_id
	 *
	 * @return false|mixed|string
	 */
	public static function get_code_from_entry( $entry_id ) {
		$entry    = GFAPI::get_entry( $entry_id );
		$form     = GFAPI::get_form( $entry['form_id'] );
		$field_id = 0;
		if ( $form['fields'] ) {
			foreach ( $form['fields'] as $field ) {
				if ( 'uncanny_enrollment_code' === $field->type ) {
					$field_id = $field->id;
					break;
				}
			}
		}
		$code_redemption = gform_get_meta( $entry_id, $field_id );
		if ( false === $code_redemption ) {
			return false;
		}

		return $code_redemption;
	}

	/**
	 * @param $code_redemption
	 * @param $user_id
	 *
	 * @return void
	 */
	public static function redeem_code( $code_redemption, $user_id ) {
		$coupon_id = Database::is_coupon_available( $code_redemption );
		if ( is_numeric( intval( $coupon_id ) ) ) {
			update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'Gravity Forms', 'uncanny-learndash-codes' ) );
			$result = Database::set_user_to_coupon( $user_id, $coupon_id );
			LearnDash::set_user_to_course_or_group( $user_id, $result );

			do_action( 'ulc_user_redeemed_code', $user_id, $coupon_id, $result, 'gravityforms' );
		}
	}
}
