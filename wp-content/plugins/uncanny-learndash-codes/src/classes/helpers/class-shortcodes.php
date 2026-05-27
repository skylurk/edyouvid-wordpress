<?php

namespace uncanny_learndash_codes;

use WP_Error;

/**
 * Class Shortcodes
 *
 * @package uncanny_learndash_codes
 */
class Shortcodes extends Config {

	private static $coupon_id;

	/**
	 * Shortcodes constructor.
	 */
	public function __construct() {

		// Code Redemption for Logged in Users.
		add_shortcode(
			'uo_user_redeem_code',
			array(
				__CLASS__,
				'user_redeem_code_callback',
			)
		);
		add_shortcode(
			'uo_self_remove_access',
			array(
				__CLASS__,
				'remove_from_group_callback',
			)
		);
		add_shortcode(
			'uo_code_registration',
			array(
				__CLASS__,
				'user_code_registration',
			)
		);
		add_action(
			'wp_enqueue_scripts',
			array(
				__CLASS__,
				'enqueue_scripts_styles',
			),
			10,
			2
		);
		add_action(
			'wp_enqueue_scripts',
			array(
				__CLASS__,
				'enqueue_registration_css',
			),
			10,
			2
		);
		add_action(
			'wp_loaded',
			array(
				__CLASS__,
				'redeem_code_callback',
			),
			10
		);
		// Only fire if default registration is used.
		add_action(
			'wp_loaded',
			array(
				__CLASS__,
				'uncanny_learndash_codes_add_new_member',
			),
			999
		);
	}

	/**
	 *
	 */
	public static function enqueue_scripts_styles() {
		global $post;

		$block_is_on_page = false;
		if ( is_a( $post, 'WP_Post' ) && function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $post->post_content );
			foreach ( $blocks as $block ) {
				if ( 'uncanny-learndash-codes/uo-code-registration' === $block['blockName'] || 'uncanny-learndash-codes/uo-user-redeem-code' === $block['blockName'] ) {
					$block_is_on_page = true;
				}
			}
		}

		if ( ! empty( $post->ID ) && has_shortcode( $post->post_content, 'uo_self_remove_access' ) ) {
			wp_enqueue_script( 'uncanny-learndash-codes-mootools-core', Config::get_vendor( 'mootools/mootools-core-1.3.1.js' ), false, '1.3.1' );
			wp_enqueue_script( 'uncanny-learndash-codes-mootools-more', Config::get_vendor( 'mootools/mootools-more-1.3.1.1.js' ), array( 'uncanny-learndash-codes-mootools-core' ), '1.3.1.1' );
			wp_enqueue_script(
				'uncanny-learndash-codes-simple-modal',
				Config::get_vendor( 'simple-modal/simple-modal.min.js' ),
				array(
					'uncanny-learndash-codes-mootools-core',
					'uncanny-learndash-codes-mootools-more',
				),
				'1.3.1.1'
			);
		}

		if ( ! empty( $post->ID ) && (
				has_shortcode( $post->post_content, 'uo_user_redeem_code' )
				|| has_shortcode( $post->post_content, 'uo_self_remove_access' )
				|| has_shortcode( $post->post_content, 'uo_code_registration' )
				|| $block_is_on_page
			)
		) {
			wp_enqueue_style( 'uncanny-learndash-codes-backend', Config::get_asset( 'backend', 'bundle.min.css' ), false, UNCANNY_LEARNDASH_CODES_VERSION );
			wp_enqueue_script( 'uncanny-learndash-codes-backend', Config::get_asset( 'backend', 'bundle.min.js' ), array( 'jquery' ), UNCANNY_LEARNDASH_CODES_VERSION );
		}
	}

	public static function enqueue_registration_css() {
		global $post;

		$block_is_on_page = false;
		if ( is_a( $post, 'WP_Post' ) && function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $post->post_content );
			foreach ( $blocks as $block ) {
				if ( 'uncanny-learndash-codes/uo-code-registration' === $block['blockName'] || 'uncanny-learndash-codes/uo-user-redeem-code' === $block['blockName'] ) {
					$block_is_on_page = true;
				}
			}
		}

		if ( ! empty( $post->ID ) && (
				has_shortcode( $post->post_content, 'uo_user_redeem_code' )
				|| has_shortcode( $post->post_content, 'uo_self_remove_access' )
				|| has_shortcode( $post->post_content, 'uo_code_registration' )
				|| $block_is_on_page
			)
		) {
			wp_enqueue_style( 'uncanny-learndash-codes-frontend', Config::get_asset( 'frontend', 'bundle.min.css' ) );
		}
	}


	/**
	 *
	 */
	public static function user_code_registration( $atts ) {

		ob_start();

		$atts = shortcode_atts(
			array(
				'redirect'      => '',
				'code_optional' => 'no',
				'auto_login'    => 'yes',
				'role'          => 'subscriber',
			),
			$atts,
			'uo_code_registration'
		);

		$GLOBALS['atts'] = $atts;

		// only show the registration form if allowed.
		// show any error messages after form submission.
		update_option( 'uncanny-codes-custom-registration-atts', $atts );

		$term_conditions = get_option( Config::$uncanny_codes_settings_term_conditions, '' );

		self::uncanny_learndash_codes_show_error_messages();
		include Config::get_template( 'frontend-user-registration-form.php' );

		return ob_get_clean();
	}

	/**
	 * function to catch all errors for default registration form
	 */
	public static function uncanny_learndash_codes_show_error_messages() {
		if ( $codes = self::uncanny_learndash_codes_errors()
						  ->get_error_codes() ) {
			echo '<div class="uncanny_learndash_codes_errors">';
			// Loop error codes and display errors.
			foreach ( $codes as $code ) {
				$message = self::uncanny_learndash_codes_errors()
							   ->get_error_message( $code );
				echo '<span class="error"><strong>';
				esc_html_e( 'Error', 'uncanny-learndash-codes' );
				echo '</strong>:';
				printf( ' %s', esc_html( $message ) );
				echo ' </span><br />';
				self::uncanny_learndash_codes_errors()->remove( $code );
			}
			echo '</div>';
		}
	}

	/**
	 * @return WP_Error
	 */
	public static function uncanny_learndash_codes_errors() {
		static $wp_error;

		// Will hold global variable safely.

		return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( null, null, null ) );
	}

	/**
	 *
	 */
	public static function uncanny_learndash_codes_add_new_member() {
		if ( ! SharedFunctionality::ulc_filter_has_var( '_uo_nonce', INPUT_POST ) ) {
			return;
		}
		if ( ! wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_uo_nonce', INPUT_POST ), Config::get_project_name() ) ) {
			wp_die( 'Invalid Request' );
		}

		$user_login        = sanitize_user( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_email', INPUT_POST ), false );
		$user_email        = sanitize_email( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_email', INPUT_POST ) );
		$user_first        = esc_attr( sanitize_text_field( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_first', INPUT_POST ) ) );
		$user_last         = esc_attr( sanitize_text_field( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_last', INPUT_POST ) ) );
		$user_pass         = esc_attr( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_pass', INPUT_POST ) );
		$pass_confirm      = esc_attr( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-user_pass_confirm', INPUT_POST ) );
		$code_registration = esc_attr( SharedFunctionality::ulc_filter_input( 'uncanny-learndash-codes-code_registration', INPUT_POST ) );
		$redirect_to       = esc_url( SharedFunctionality::ulc_filter_input( 'redirect_to', INPUT_POST ) );

		$default = array(
			'redirect'      => '',
			'code_optional' => 'no',
			'auto_login'    => 'yes',
			'role'          => 'subscriber',
		);

		if ( is_multisite() ) {
			$options = get_blog_option( get_current_blog_id(), 'uncanny-codes-custom-registration-atts', $default );
		} else {
			$options = get_option( 'uncanny-codes-custom-registration-atts', $default );
		}

		if ( username_exists( $user_login ) ) {
			// Username already registered.
			self::uncanny_learndash_codes_errors()
				->add( 'username_unavailable', esc_html__( 'Username already taken', 'uncanny-learndash-codes' ) );
		}
		if ( ! validate_username( $user_login ) ) {
			// invalid username.
			self::uncanny_learndash_codes_errors()
				->add( 'username_invalid', esc_html__( 'Invalid username', 'uncanny-learndash-codes' ) );
		}
		if ( '' === $user_login ) {
			// empty username.
			self::uncanny_learndash_codes_errors()
				->add( 'username_empty', esc_html__( 'Please enter a username', 'uncanny-learndash-codes' ) );
		}
		if ( ! is_email( $user_email ) ) {
			// invalid email.
			self::uncanny_learndash_codes_errors()
				->add( 'email_invalid', esc_html__( 'Invalid email', 'uncanny-learndash-codes' ) );
		}
		if ( email_exists( $user_email ) ) {
			// Email address already registered.
			self::uncanny_learndash_codes_errors()
				->add( 'email_used', esc_html__( 'Email already registered', 'uncanny-learndash-codes' ) );
		}
		if ( '' === $user_pass ) {
			// passwords do not match.
			self::uncanny_learndash_codes_errors()
				->add( 'password_empty', esc_html__( 'Please enter a password', 'uncanny-learndash-codes' ) );
		}
		if ( $pass_confirm !== $user_pass ) {
			// passwords do not match.
			self::uncanny_learndash_codes_errors()
				->add( 'password_mismatch', esc_html__( 'Passwords do not match', 'uncanny-learndash-codes' ) );
		}
		if ( '' === $code_registration && 'yes' !== $options['code_optional'] ) {
			self::uncanny_learndash_codes_errors()
				->add( 'code_empty', esc_html__( 'Registration Code is empty', 'uncanny-learndash-codes' ) );
		} elseif ( ! empty( $code_registration ) ) {
			$coupon_id = SharedFunctionality::maybe_validate_coupon_code( $code_registration );
			if ( ! is_numeric( $coupon_id ) ) {
				self::uncanny_learndash_codes_errors()
					->add( 'codes_error', $coupon_id );
			} else {
				self::$coupon_id = intval( $coupon_id );
			}
		}
		//	}
		$errors = self::uncanny_learndash_codes_errors()
					  ->get_error_messages();
		// only create the user in if there are no errors.
		if ( empty( $errors ) ) {
			$role        = key_exists( 'role', $options ) ? $options['role'] : get_option( 'default_role', 'subscriber' );
			$new_user_id = wp_insert_user(
				array(
					'user_login'      => $user_login,
					'user_pass'       => $user_pass,
					'user_email'      => $user_email,
					'first_name'      => $user_first,
					'last_name'       => $user_last,
					'user_registered' => date( 'Y-m-d H:i:s' ),
					'role'            => $role,
				)
			);
			if ( $new_user_id ) {
				// send an email to the admin alerting them of the registration.
				wp_new_user_notification( $new_user_id, null, 'admin' );
				// log the new user in.
				if ( intval( self::$coupon_id ) ) {

					update_user_meta( $new_user_id, Config::$uncanny_codes_tracking, 'Custom Registration Form' );

					$result = Database::set_user_to_coupon( $new_user_id, self::$coupon_id );
					LearnDash::set_user_to_course_or_group( $new_user_id, $result );

					do_action( 'ulc_user_redeemed_code', $new_user_id, self::$coupon_id, $result, 'shortcode' );

				}

				if ( 'yes' === $options['auto_login'] ) {
					wp_set_auth_cookie( $new_user_id );
					wp_set_current_user( $new_user_id, $user_login );
				}

				if ( ! empty( $redirect_to ) ) {
					wp_redirect( $redirect_to . '?' . SharedFunctionality::ulc_filter_input( 'key' ) . '&registered' );
				} else {
					wp_redirect( get_permalink() . '?' . SharedFunctionality::ulc_filter_input( 'key' ) . '&registered' );
				}
				exit;
			}
		}
	}

	/**
	 * @return string
	 */
	public static function user_redeem_code_callback( $atts = array() ) {

		$atts = shortcode_atts(
			array(
				'redirect'   => '',
				'check_only' => 'no', // yes|no.
			),
			$atts,
			'uo_user_redeem_code'
		);

		$error      = '';
		$error_type = '';
		$user       = wp_get_current_user();
		$user_id    = $user->ID;
		if ( ! intval( $user_id ) ) {
			return esc_html__( 'Sorry! You are not logged in!', 'uncanny-learndash-codes' );
		}

		if ( ! empty( $_POST ) && ( SharedFunctionality::ulc_filter_has_var( '_wpnonce', INPUT_POST ) && ! wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_wpnonce', INPUT_POST ), Config::get_project_name() ) ) ) {
			$error = esc_html__( 'Sorry your request was not verified. Please try again later. Log out and try again if problem persist.', 'uncanny-learndash-codes' );

			return $error;
		} elseif ( SharedFunctionality::ulc_filter_has_var( '_wpnonce', INPUT_POST ) && wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_wpnonce', INPUT_POST ), Config::get_project_name() ) ) {
			$ukey       = date( 'Ymd' );
			$error      = get_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey, true );
			$error_type = get_user_meta( $user_id, 'ulc_user_error_type_' . $ukey, true );
			if ( ! empty( $error ) ) {
				delete_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey );
				delete_user_meta( $user_id, 'ulc_user_error_type_' . $ukey );
			}
		}

		return self::get_form( $error, $atts, $error_type );
	}

	/**
	 * @param        $error
	 * @param array $atts
	 * @param string $error_type
	 *
	 * @return string
	 */
	public static function get_form( $error, $atts = array(), $error_type = 'notice' ) {
		$redirect   = '';
		$check_only = '';
		if ( isset( $atts['redirect'] ) && ! empty( $atts['redirect'] ) ) {
			$redirect = '<input type="hidden" name="redirect_to" value="' . $atts['redirect'] . '" />';
		}
		if ( isset( $atts['check_only'] ) && 'yes' === (string) $atts['check_only'] ) {
			$check_only = '<input type="hidden" name="check_only" value="yes" />';
		}
		ob_start();
		?>
		<div
			class="uoc uoc-code-redemption form uo uo-redeem"
			id="ulc-code-redemption"
		>
			<form
				name="codeRedeemForm"
				id="codeRedeemForm"
				action=""
				method="POST"
				class="uoc-code-redemption__form"
			>
				<label
					for="coupon_code_only"
					class="uoc-code-redemption__label hidden"
				>
					<?php echo esc_html__( 'Coupon code', 'uncanny-learndash-codes' ); ?>
				</label>

				<input
					name="coupon_code_only"
					id="coupon_code_only"
					type="text"
					value=""
					class="uoc-code-redemption__field medium"
					tabindex="20"
					placeholder="<?php echo esc_html__( 'Enter coupon code', 'uncanny-learndash-codes' ); ?>"
					required="required"/>

				<div
					class="uoc-code-redemption__message uoc-code-redemption__message--<?php echo esc_attr( $error_type ); ?>"
				><?php echo $error; ?></div>

				<div
					class="uoc-code-redemption__submit-container"
				>
					<input
						type="submit"
						value="<?php echo esc_html__( 'Redeem', 'uncanny-learndash-codes' ); ?>"
						class="uoc-code-redemption__submit-button"/>

					<input
						type="hidden"
						name="instance"
						value="codeRedeemForm"/>

					<input
						type="hidden"
						name="action"
						value="redeem-code"/>

					<?php echo $redirect; ?>

					<?php echo $check_only; ?>

					<input
						type="hidden"
						name="_wpnonce"
						value="<?php echo wp_create_nonce( Config::get_project_name() ); ?>"/>
				</div>
			</form>
		</div>
		<?php

		return ob_get_clean();

	}
	/* Code Only Redemption Form
	 *
	 * return string
	 */

	/**
	 * Code Redemption Process before form
	 */
	public static function redeem_code_callback() {
		$error   = '';
		$user    = wp_get_current_user();
		$user_id = $user->ID;
		$ukey    = date( 'Ymd' );
		if ( ! intval( $user_id ) ) {
			return;
		}

		if ( empty( SharedFunctionality::ulc_filter_input( '_wpnonce', INPUT_POST ) ) || ! wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_wpnonce', INPUT_POST ), Config::get_project_name() ) ) {
			return;
		}

		if ( empty( SharedFunctionality::ulc_filter_input( 'coupon_code_only', INPUT_POST ) ) || empty( SharedFunctionality::ulc_filter_input( 'coupon_code_only', INPUT_POST ) ) ) {
			$error = esc_html__( 'Please input the coupon code before clicking redeem', 'uncanny-learndash-codes' );
			update_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey, $error );
			update_user_meta( $user_id, 'ulc_user_error_type_' . $ukey, 'notice' );

			return;
		}
		$check_only = SharedFunctionality::ulc_filter_has_var( 'check_only', INPUT_POST ) && 'yes' === (string) SharedFunctionality::ulc_filter_input( 'check_only', INPUT_POST ) ? true : false;

		$coupon_id = SharedFunctionality::maybe_validate_coupon_code( SharedFunctionality::ulc_filter_input( 'coupon_code_only', INPUT_POST ) );
		if ( ! is_numeric( $coupon_id ) ) {
			$error = $coupon_id;
			update_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey, $error );
			update_user_meta( $user_id, 'ulc_user_error_type_' . $ukey, 'error' );

			return;
		}

		self::$coupon_id = null;
		if ( $check_only ) {
			update_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey, esc_html__( 'Code available', 'uncanny-learndash-codes' ) );
			update_user_meta( $user_id, 'ulc_user_error_type_' . $ukey, 'success' );

			return;
		}
		self::$coupon_id = intval( $coupon_id );

		$is_paid    = Database::is_coupon_paid( sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon_code_only', INPUT_POST ) ) );
		$is_default = Database::is_default_code( sanitize_text_field( SharedFunctionality::ulc_filter_input( 'coupon_code_only', INPUT_POST ) ) );

		if ( false !== $is_default || false !== $is_paid ) {
			update_user_meta( $user_id, Config::$uncanny_codes_tracking, 'User Code Redeemed' );

			$result = Database::set_user_to_coupon( $user_id, self::$coupon_id );
			do_action( 'ulc_user_redeemed_code', $user_id, self::$coupon_id, $result, 'shortcode' );
			LearnDash::set_user_to_course_or_group( $user_id, $result );

			if ( SharedFunctionality::ulc_filter_has_var( 'redirect_to', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'redirect_to', INPUT_POST ) ) ) {
				delete_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey );
				$redirect_to = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'redirect_to', INPUT_POST ) );
				wp_redirect( $redirect_to );
				exit;
			}

			$error = Config::$successfully_redeemed;
			update_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey, $error );
			update_user_meta( $user_id, 'ulc_user_error_type_' . $ukey, 'success' );

			return;
		} else {
			$error = Config::$unpaid_error;
			update_user_meta( $user_id, 'ulc_user_error_type_' . $ukey, 'notice' );
		}

		update_user_meta( $user_id, 'ulc_user_redeemed_code_' . $ukey, $error );
	}

	/**
	 * @return string
	 */
	public static function remove_from_group_callback() {
		$error   = '';
		$user    = wp_get_current_user();
		$user_id = $user->ID;

		if ( ! intval( $user_id ) ) {
			return esc_html__( 'Sorry! You are not logged in!', 'uncanny-learndash-codes' );
		}

		if ( ! empty( $_POST ) && ( SharedFunctionality::ulc_filter_has_var( '_wpnonce', INPUT_POST ) && ! wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_wpnonce', INPUT_POST ), Config::get_project_name() ) ) ) {
			$error = esc_html__( 'Sorry your request was not verified. Please try again later. Log out and try again if problem persist.', 'uncanny-learndash-codes' );

			return $error;
		} elseif ( ! empty( $_POST ) && ( SharedFunctionality::ulc_filter_has_var( '_wp_nonce_removal', INPUT_POST ) && wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_wp_nonce_removal', INPUT_POST ), Config::get_project_name() ) ) ) {
			LearnDash::remove_all_access( $user_id );
			$error = esc_html__( 'Access Removed Successfully!', 'uncanny-learndash-codes' );
		}

		return self::get_removal_form( $error );
	}

	/**
	 * @param $error
	 *
	 * @return string
	 */
	public static function get_removal_form( $error ) {
		$form = '<div class="uo uo-register uo-redeem gform_wrapper dark-form_wrapper" id="theme-my-login">
					<form name="codeRemovalForm" id="codeRemovalForm" action="" method="post" class="dark-form">
						<p class="uo-submit-wrap gform_footer top_label">
							<input type="submit" value="' . esc_html__( 'Remove access to all groups', 'uncanny-learndash-codes' ) . '" id="validate-confirm-removal">
							<input type="hidden" name="instance" value="codeRemovalInstance" />
							<input type="hidden" name="action" value="removal-code" />
							<input type="hidden" name="_wp_nonce_removal" value="' . wp_create_nonce( Config::get_project_name() ) . '"/>
						</p>
						<div class="description">' . $error . '</div>
					</form>
                </div>';
		$form .= '<script>
					jQuery(document).ready(function(){
						$("validate-confirm-removal").addEvent("click", function(e){
						  e.stop();
						  var SM = new SimpleModal({"hideHeader":true, "btn_ok":"Yes", draggable:false});
						      SM.show({
						        "model":"confirm",
					            "callback": function(){
					              jQuery("#codeRemovalForm").submit();
					            },
								"title":"' . esc_html__( 'Confirm Removal', 'uncanny-learndash-codes' ) . '",
						        "contents":"' . esc_html__( 'This action will remove you from all groups, which may remove your course access. Your group access cannot be restored. Are you sure you wish to continue?', 'uncanny-learndash-codes' ) . '"
						      } );
		} );
	} )
</script>';

		return $form;
	}

}
