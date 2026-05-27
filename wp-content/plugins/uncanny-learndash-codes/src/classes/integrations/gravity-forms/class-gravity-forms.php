<?php

namespace uncanny_learndash_codes;

use GF_Fields;

/**
 * Class GravityForms
 * @package uncanny_learndash_codes
 */
class GravityForms extends Config {
	/**
	 * @var
	 */
	private static $coupon_id;
	/**
	 * @var
	 */
	private static $form_id;
	/**
	 * @var
	 */
	private $redirect_to;

	/**
	 * GravityForms constructor.
	 */
	public function __construct() {
		if ( class_exists( 'GFFormsModel' ) ) {

			// Add Code Redemption Field to Gravity Forms.
			add_filter( 'gform_pre_render', array( __CLASS__, 'add_code_field' ) );
			add_filter( 'gform_pre_validation', array( __CLASS__, 'add_code_field' ) );
			add_filter( 'gform_pre_submission_filter', array( __CLASS__, 'add_code_field' ) );

			// Add Custom Validation for Code Redemption Field.
			add_filter( 'gform_validation', array( __CLASS__, 'custom_validation' ) );

			add_filter( 'manage_users_columns', array( __CLASS__, 'manage_users_columns' ) );
			add_action( 'manage_users_custom_column', array( __CLASS__, 'manage_users_custom_column' ), 10, 3 );

			// Registration completed.
			add_action( 'user_register', array( __CLASS__, 'gf_user_register' ), 15 );
			add_action( 'gform_activate_user', array( __CLASS__, 'gf_user_register_activation' ), 15, 3 );
		}
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public static function manage_users_columns( $columns ) {
		$columns['user_prefix'] = esc_html__( 'Prefix', 'uncanny-learndash-codes' );

		return $columns;
	}

	/**
	 * @param $value
	 * @param $column_name
	 * @param $user_id
	 *
	 * @return mixed
	 */
	public static function manage_users_custom_column( $value, $column_name, $user_id ) {
		$user_prefix = get_user_meta( $user_id, Config::$uncanny_codes_user_prefix_meta, true );

		if ( 'user_prefix' === $column_name ) {
			return $user_prefix;
		}

		return $value;
	}

	/**
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	public static function custom_validation( $validation_result ) {
		if ( is_multisite() ) {
			self::$form_id = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_settings_gravity_forms, 0 );
		} else {
			self::$form_id = get_option( Config::$uncanny_codes_settings_gravity_forms, 0 );
		}

		$form = $validation_result['form'];
		if ( absint( self::$form_id ) !== absint( $form['id'] ) ) {
			return $validation_result;
		}
		$code_redemption = rgpost( 'input_99' );
		if ( empty( $code_redemption ) ) {
			return $validation_result;
		}
		// Check if existing coupon or not!
		$coupon_id = SharedFunctionality::maybe_validate_coupon_code( $code_redemption );
		if ( null !== $coupon_id && is_numeric( $coupon_id ) ) {
			self::$coupon_id = intval( $coupon_id );
		} else {
			$validation_result['is_valid'] = false;
			foreach ( $form['fields'] as &$field ) {

				// NOTE: replace 1 with the field you would like to validate.
				if ( 99 === $field->id ) {
					$field->failed_validation  = true;
					$field->validation_message = $coupon_id;
				}
			}
		}

		$validation_result['form'] = $form;

		return $validation_result;
	}

	/**
	 * @param $form
	 *
	 * @return mixed
	 */
	public static function add_code_field( $form ) {
		if ( is_multisite() ) {
			self::$form_id = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_settings_gravity_forms, 0 );
		} else {
			self::$form_id = get_option( Config::$uncanny_codes_settings_gravity_forms, 0 );
		}
		if ( intval( self::$form_id ) === intval( $form['id'] ) ) {
			$add_custom_field = true;
			if ( $form['fields'] ) {
				foreach ( $form['fields'] as $field ) {
					if ( 'uncanny_enrollment_code' === $field->type ) {
						$add_custom_field = false;
						break;
					}
				}
			}
			if ( $add_custom_field ) {
				if ( is_multisite() ) {
					$mandatory   = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_settings_gravity_forms_mandatory, false );
					$label       = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_settings_gravity_forms_label, esc_html__( 'Enter Registration Code', 'uncanny-learndash-codes' ) );
					$error       = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_settings_gravity_forms_error, esc_html__( 'This field is mandatory', 'uncanny-learndash-codes' ) );
					$placeholder = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_settings_gravity_forms_placeholder, esc_html__( 'Enter Code', 'uncanny-learndash-codes' ) );
				} else {
					$mandatory   = get_option( Config::$uncanny_codes_settings_gravity_forms_mandatory, false );
					$label       = get_option( Config::$uncanny_codes_settings_gravity_forms_label, esc_html__( 'Enter Registration Code', 'uncanny-learndash-codes' ) );
					$error       = get_option( Config::$uncanny_codes_settings_gravity_forms_error, esc_html__( 'Enter Code', 'uncanny-learndash-codes' ) );
					$placeholder = get_option( Config::$uncanny_codes_settings_gravity_forms_placeholder, esc_html__( 'Enter Code', 'uncanny-learndash-codes' ) );
				}

				if ( 1 === intval( $mandatory ) ) {
					$mandatory = true;
				}

				$props       = array(
					'id'           => 99,
					'label'        => $label,
					'adminLabel'   => $label,
					'type'         => 'text',
					'size'         => 'large',
					'isRequired'   => $mandatory,
					'placeholder'  => $placeholder,
					'noDuplicates' => false,
					'formId'       => $form['id'],
					'pageNumber'   => 1,
					'errorMessage' => $error,
				);
				$form_fields = array();

				foreach ( $form['fields'] as $key => $value ) {
					$form_fields[] = $value['id'];
				}
				if ( ! in_array( 99, $form_fields, true ) ) {
					$field = GF_Fields::create( $props );
					array_push( $form['fields'], $field );
				}
			}
		}

		return $form;

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
		$code_redemption = gform_get_meta( $entry_meta['entry_id'], 99 );
		if ( false !== $code_redemption ) {
			$coupon_id = Database::is_coupon_available( $code_redemption );
			if ( is_numeric( intval( $coupon_id ) ) ) {
				update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'Gravity Forms', 'uncanny-learndash-codes' ) );
				$result = Database::set_user_to_coupon( $user_id, $coupon_id );
				LearnDash::set_user_to_course_or_group( $user_id, $result );

				do_action( 'ulc_user_redeemed_code', $user_id, self::$coupon_id, $result, 'gravityforms' );
			}
		}
	}

}
