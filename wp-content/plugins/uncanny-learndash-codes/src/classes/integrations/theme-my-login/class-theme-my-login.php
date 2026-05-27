<?php

namespace uncanny_learndash_codes;

/**
 * Class ThemeMyLogin
 * @package uncanny_learndash_codes
 */
class ThemeMyLogin extends Config {
	/**
	 * @var
	 */
	private static $coupon_id;

	/**
	 * ThemeMyLogin constructor.
	 */
	public function __construct() {
		if ( defined( 'THEME_MY_LOGIN_PATH' ) ) {

			add_filter( 'registration_errors', array( $this, 'tml_registration_errors' ), 10, 3 );
			// Registration completed.
			add_action( 'user_register', array( $this, 'tml_user_register' ), 15 );

			if ( defined( 'THEME_MY_LOGIN_VERSION' ) ) {
				add_action( 'init', array( $this, 'tml_add_fields' ), 10 );
			} else {
				add_filter( 'tml_template_paths', array( $this, 'tml_single_template' ), 99 );
			}
		}
	}

	/**
	 * @param $paths
	 *
	 * @return array
	 */
	public static function tml_single_template( $paths ) {
		if ( is_multisite() ) {
			$tml_template_override = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_tml_template_override );
		} else {
			$tml_template_override = get_option( Config::$uncanny_codes_tml_template_override );
		}

		if ( 1 === intval( $tml_template_override ) ) {
			$paths = array_merge( array( dirname( dirname( __FILE__ ) ) . '/templates/' ), $paths );
		}

		return $paths;

	}

	/**
	 * @param $errors
	 * @param $sanitized_user_login
	 * @param $user_email
	 *
	 * @return mixed
	 */
	public static function tml_registration_errors( $errors, $sanitized_user_login, $user_email ) {

		$code = false;
		if ( SharedFunctionality::ulc_filter_has_var( 'code_registration', INPUT_POST ) ) {
			$code = SharedFunctionality::ulc_filter_input( 'code_registration', INPUT_POST );
		}

		// Check if code is empty.
		if ( empty( $code ) ) {
			// Check if code is required.
			if ( self::is_code_required_for_registration() ) {
				$errors->add( 'code_invalid', '<strong>' . esc_html__( 'Error', 'uncanny-learndash-codes' ) . '</strong>: ' . Config::$invalid_code );
			}
		} else {
			// Validate Code.
			$coupon_id = Database::is_coupon_available( $code );
			if ( is_array( $coupon_id ) ) {
				if ( 'failed' === $coupon_id['result'] ) {
					if ( 'max' === $coupon_id['error'] ) {
						$errors->add( 'code_maximum', '<strong>' . esc_html__( 'Error', 'uncanny-learndash-codes' ) . '</strong>: ' . Config::$redeemed_maximum );
					} elseif ( 'invalid' === $coupon_id['error'] ) {
						$errors->add( 'code_invalid', '<strong>' . esc_html__( 'Error', 'uncanny-learndash-codes' ) . '</strong>: ' . Config::$invalid_code );
					} elseif ( 'expired' === $coupon_id['error'] ) {
						$errors->add( 'code_expired', '<strong>' . esc_html__( 'Error', 'uncanny-learndash-codes' ) . '</strong>: ' . Config::$expired_code );
					}
				}
			} elseif ( is_numeric( $coupon_id ) ) {
				self::$coupon_id = intval( $coupon_id );
			} else {
				self::$coupon_id = null;
				$errors->add( 'code_invalid', '<strong>' . esc_html__( 'Error', 'uncanny-learndash-codes' ) . '</strong>: ' . Config::$invalid_code );
			}
		}

		return $errors;
	}

	/**
	 * @param $user_id
	 */
	public static function tml_user_register( $user_id ) {
		if ( SharedFunctionality::ulc_filter_has_var( 'first_name', INPUT_POST ) ) {
			update_user_meta( $user_id, 'first_name', esc_attr( SharedFunctionality::ulc_filter_input( 'first_name', INPUT_POST ) ) );
		}
		if ( SharedFunctionality::ulc_filter_has_var( 'last_name', INPUT_POST ) ) {
			update_user_meta( $user_id, 'last_name', esc_attr( SharedFunctionality::ulc_filter_input( 'last_name', INPUT_POST ) ) );
		}

		$coupon_id = Database::is_coupon_available( SharedFunctionality::ulc_filter_input( 'code_registration', INPUT_POST ) );
		if ( is_numeric( intval( $coupon_id ) ) && empty( SharedFunctionality::ulc_filter_input( 'gform_submit', INPUT_POST ) ) ) {
			update_user_meta( $user_id, Config::$uncanny_codes_tracking, esc_html__( 'Theme My Login', 'uncanny-learndash-codes' ) );

			$result = Database::set_user_to_coupon( $user_id, self::$coupon_id );
			LearnDash::set_user_to_course_or_group( $user_id, $result );

			do_action( 'ulc_user_redeemed_code', $user_id, self::$coupon_id, $result, 'tml' );
		}

	}

	/**
	 * 7.0+ TML Update
	 */
	public function tml_add_fields() {
		if ( is_multisite() ) {
			$tml_template_override = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_tml_template_override );
		} else {
			$tml_template_override = get_option( Config::$uncanny_codes_tml_template_override );
		}

		if ( 1 === intval( $tml_template_override ) ) {
			tml_add_form_field(
				'register',
				'first_name',
				array(
					'type'        => 'text',
					'value'       => tml_get_request_value( 'first_name', 'post' ),
					'label'       => esc_html__( 'First Name', 'uncanny-learndash-codes' ),
					'id'          => 'first_name',
					'attributes'  => array( 'required' => 'required' ),
					'description' => '',
					'priority'    => 5,
				)
			);
			tml_add_form_field(
				'register',
				'last_name',
				array(
					'type'        => 'text',
					'value'       => tml_get_request_value( 'last_name', 'post' ),
					'label'       => esc_html__( 'Last Name', 'uncanny-learndash-codes' ),
					'id'          => 'last_name',
					'attributes'  => array( 'required' => 'required' ),
					'description' => '',
					'priority'    => 6,
				)
			);

			tml_add_form_field(
				'register',
				'code_registration',
				array(
					'type'        => 'text',
					'value'       => tml_get_request_value( 'code_registration', 'post' ),
					'label'       => esc_html__( 'Registration Code', 'uncanny-learndash-codes' ),
					'id'          => 'code_registration',
					'attributes'  => self::is_code_required_for_registration() ? array( 'required' => 'required' ) : array(),
					'description' => '',
					'priority'    => 15,
				)
			);
		}
	}

	/**
	 * Check if code is required for registration.
	 *
	 * @return bool
	 */
	public static function is_code_required_for_registration() {
		if ( is_multisite() ) {
			$tml_template_required = get_blog_option( get_current_blog_id(), Config::$uncanny_codes_tml_codes_required_field );
		} else {
			$tml_template_required = get_option( Config::$uncanny_codes_tml_codes_required_field );
		}

		return 1 === intval( $tml_template_required );
	}
}
