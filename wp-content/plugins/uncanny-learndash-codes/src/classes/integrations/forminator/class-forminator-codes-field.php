<?php

namespace uncanny_learndash_codes;

/**
 *
 */
class Forminator_Codes_Field_Handler extends Config {
	/**
	 * Store the ID value of coupon code
	 *
	 * @var $coupon_id
	 */
	private static $coupon_id;

	/**
	 *
	 */
	public function __construct() {
		add_filter(
			'forminator_custom_form_submit_errors',
			array(
				$this,
				'forminator_custom_form_submit_errors_func',
			),
			99,
			3
		);

		add_action(
			'forminator_custom_form_submit_before_set_fields',
			array(
				$this,
				'forminator_custom_form_submit_before_set_fields_func',
			),
			99,
			3
		);
		add_action(
			'forminator_cform_user_registered',
			array(
				$this,
				'forminator_cform_user_registered_func',
			),
			99,
			4
		);
	}

	/**
	 * @param $entry
	 * @param $form_id
	 * @param $submitted_data
	 *
	 * @return void
	 */
	public function forminator_custom_form_submit_before_set_fields_func( $entry, $form_id, $submitted_data ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( empty( $submitted_data ) ) {
			return;
		}
		$user_id = wp_get_current_user()->ID;

		$value = $this->get_uncanny_codes_field( $submitted_data );

		$this->process_code_redemption( $user_id, $value );
	}

	/**
	 * @param $user_id
	 * @param $custom_form
	 * @param $entry
	 * @param $password
	 *
	 * @return void
	 */
	public function forminator_cform_user_registered_func( $user_id, $custom_form, $entry, $password ) {

		if ( null === $user_id ) {
			return;
		}
		$input_id = '';
		if ( isset( $custom_form->fields ) ) {
			$fields = $custom_form->fields;
			foreach ( $fields as $field ) {
				/** @var \Forminator_Form_Field_Model $field */
				if ( 'uncanny_codes' !== $field->input_type ) {
					continue;
				}
				$input_id = $field->slug;
				break;
			}
		}

		if ( isset( $entry->meta_data[ $input_id ] ) ) {
			$value = sanitize_text_field( $entry->meta_data[ $input_id ]['value'] );
		}

		$this->process_code_redemption( $user_id, $value );
	}

	/**
	 * @param $user_id
	 * @param $entry
	 *
	 * @return void
	 */
	public function process_code_redemption( $user_id, $value ) {
		if ( empty( $value ) ) {
			return;
		}
		$coupon_id = SharedFunctionality::maybe_validate_coupon_code( $value );
		if ( null !== $coupon_id && is_numeric( $coupon_id ) ) {
			update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'Forminator', 'uncanny-learndash-codes' ) );
			$result = Database::set_user_to_coupon( $user_id, $coupon_id );
			LearnDash::set_user_to_course_or_group( $user_id, $result );

			do_action( 'ulc_user_redeemed_code', $user_id, $coupon_id, $result, 'forminator' );
		}
	}

	/**
	 * @param $errors
	 * @param $form_id
	 * @param $form_data
	 *
	 * @return mixed
	 */
	public function forminator_custom_form_submit_errors_func( $errors, $form_id, $form_data ) {
		if ( empty( $form_data ) ) {
			return $errors;
		}
		$value = $this->get_uncanny_codes_field( $form_data );
		if ( empty( $value ) ) {
			return $errors;
		}
		$coupon_id = SharedFunctionality::maybe_validate_coupon_code( $value );
		if ( ! is_numeric( $coupon_id ) ) {
			$name              = $this->get_field_id( $form_data );
			$errors[][ $name ] = $coupon_id;
		}

		return $errors;
	}

	/**
	 * @param $fields
	 *
	 * @return bool
	 */
	private function get_uncanny_codes_field( $fields ) {
		foreach ( $fields as $field ) {
			if ( 'uncanny_codes' !== $field['field_array']['input_type'] ) {
				continue;
			}

			return $field['value'];
		}

		return '';
	}

	/**
	 * @param $fields
	 *
	 * @return mixed|string
	 */
	private function get_field_id( $fields ) {
		foreach ( $fields as $field ) {
			if ( 'uncanny_codes' !== $field['field_array']['input_type'] ) {
				continue;
			}

			return $field['name'];
		}

		return '';
	}
}
