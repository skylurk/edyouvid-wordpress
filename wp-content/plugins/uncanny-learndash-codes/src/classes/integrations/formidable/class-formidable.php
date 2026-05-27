<?php

namespace uncanny_learndash_codes;

use FrmEntry;
use FrmField;

/**
 * Class Formidable
 * @package uncanny_learndash_codes
 */
class Formidable extends Config {
	/**
	 * Store the ID value of coupon code
	 *
	 * @var $coupon_id
	 */
	private static $coupon_id;

	/**
	 * Formidable constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 999 );
	}

	/**
	 * Add hookable actions of Formidable
	 */
	public function plugins_loaded() {
		add_filter( 'frm_available_fields', array( $this, 'add_uncanny_code_field' ), 99 );
		add_filter( 'frm_get_field_type_class', array( $this, 'add_uncanny_code_field_type' ), 99, 2 );
		add_filter( 'frm_validate_field_entry', array( $this, 'validate_uncanny_code' ), 99, 3 );

		add_action( 'frm_display_added_fields', array( $this, 'show_the_admin_field' ), 99 );
		add_action( 'frm_after_create_entry', array( $this, 'redeem_uncanny_code' ), 30, 2 );
	}

	/**
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function add_uncanny_code_field( $fields ) {
		$fields['uncanny_code'] = array(
			'name' => esc_html__( 'Uncanny Code', 'uncanny-learndash-codes' ),
			// the key for the field and the label,.
			'icon' => 'frm_icon_font frm_price_tags_icon',
		);

		return $fields;
	}

	/**
	 * @param $class
	 * @param $type
	 *
	 * @return mixed|string
	 */
	public function add_uncanny_code_field_type( $class, $type ) {
		if ( 'uncanny_code' === $type ) {
			$class = '\uncanny_learndash_codes\FrmFieldUncannyCode';
		}

		return $class;
	}

	/**
	 * @param $errors
	 * @param $posted_field
	 * @param $field_value
	 *
	 * @return mixed
	 */
	public function validate_uncanny_code( $errors, $posted_field, $field_value ) {

		if ( 'uncanny_code' !== (string) $posted_field->type ) {
			return $errors;
		}
		// Load default error messages.
		Config::load_default_messages();

		if ( empty( $field_value ) ) {
			$errors[ 'field' . $posted_field->id ] = esc_html__( 'Please enter valid code.', 'uncanny-learndash-codes' );

			return $errors;
		}

		// Check if existing coupon or not!
		$coupon_id = SharedFunctionality::maybe_validate_coupon_code( $field_value );
		if ( null !== $coupon_id && is_numeric( $coupon_id ) ) {
			self::$coupon_id = intval( $coupon_id );
		} else {
			$errors[ 'field' . $posted_field->id ] = Config::$invalid_code;
		}

		return $errors;
	}

	/**
	 * @param $entry_id
	 * @param $form_id
	 */
	public function redeem_uncanny_code( $entry_id, $form_id ) {
		$entry = FrmEntry::getOne( $entry_id, true );

		if ( ! isset( $entry->metas ) ) {
			return;
		}

		$user_id = wp_get_current_user()->ID;

		if ( absint( $entry->user_id ) !== absint( $user_id ) && 0 !== absint( $entry->user_id ) ) {
			$user_id = $entry->user_id;
		}
		if ( ! is_numeric( $user_id ) ) {
			return;
		}
		foreach ( $entry->metas as $k => $v ) {
			$value      = $v;
			$field_info = FrmField::getOne( $k );

			if ( ! $field_info ) {
				continue;
			}
			if ( 'uncanny_code' !== (string) $field_info->type ) {
				continue;
			}

			$coupon_id = Database::is_coupon_available( $value );
			if ( is_numeric( intval( $coupon_id ) ) ) {
				update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'Formidable', 'uncanny-learndash-codes' ) );
				$result = Database::set_user_to_coupon( $user_id, $coupon_id );
				LearnDash::set_user_to_course_or_group( $user_id, $result );

				do_action( 'ulc_user_redeemed_code', $user_id, $coupon_id, $result, 'formidable' );
			}
		}
	}
}
