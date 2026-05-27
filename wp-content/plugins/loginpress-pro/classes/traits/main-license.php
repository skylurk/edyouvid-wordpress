<?php
/**
 * LoginPress Main License Trait file.
 *
 * @package LoginPress
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Main Frontend Trait.
 *
 * Handles Some helping functions from loginpress-main.php file.
 * Handles the License of the plugin.
 *
 * @package   LoginPress
 * @subpackage Traits\Loginpress-main
 * @since   6.1.0
 */

if ( ! trait_exists( 'LoginPress_Main_License_Trait' ) ) {
	/**
	 * LoginPress Main Admin Trait.
	 *
	 * Handles Some helping functions from loginpress-main.php file.
	 * Handles the License of the plugin.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\Loginpress-main
	 * @since   6.1.0
	 */
	trait LoginPress_Main_License_Trait {

		/**
		 * Manage LoginPress Pro license.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public static function manage_license() {

			if ( is_admin() ) {
				$hook = isset( $_GET['page'] ) && ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;
				if ( version_compare( LOGINPRESS_VERSION, '3.0.5', '>=' ) ) {
					$upgrade_object = new LoginPress_Pro_Setup_30( false );
				}
			}

			if ( $hook && ( 'loginpress-settings' === $hook || 'loginpress-help' === $hook || 'loginpress-addons' === $hook ) && ( version_compare( LOGINPRESS_VERSION, '3.0.5', '<=' ) || ! $upgrade_object->is_all_addons_updated() ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=loginpress-setup-30' ) );
			}
			// Creates our settings in the options table.
			register_setting( 'loginpress_pro_license', 'loginpress_pro_license_key' );

			// Listen for our activate button to be clicked.
			if ( isset( $_POST['loginpress_pro_license_activate'] ) && check_ajax_referer( 'loginpress_pro_activate_license_nonce', 'loginpress_pro_activate_license_nonce' ) ) {

				$license_key       = isset( $_POST['loginpress_pro_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['loginpress_pro_license_key'] ) ) : '';
				$registration_data = self::activate_license( $license_key );
			}

			// Listen for our deactivate button to be clicked.
			if ( isset( $_POST['loginpress_pro_license_deactivate'] ) && check_ajax_referer( 'loginpress_pro_deactivate_license_nonce', 'loginpress_pro_deactivate_license_nonce' ) ) {

				$license           = get_option( 'loginpress_pro_license_key' );
				$registration_data = self::deactivate_license( sanitize_text_field( wp_unslash( $license ) ) );
			}
		}

		/**
		 * Try to activate the supplied license on our store.
		 *
		 * @since 6.1.0
		 * @param string $license License key to activate.
		 * @return array
		 */
		public static function activate_license( $license ) {

			$license = trim( $license );

			$result = array(
				'license_key'   => $license,
				'license_data'  => array(),
				'error_message' => '',
			);

			// Data to send in our API request.
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => self::LOGINPRESS_PRODUCT_ID,
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post(
				self::LOGINPRESS_SITE_URL,
				array(
					'sslverify' => false,
					'body'      => $api_params,
				)
			);

			// Make sure the response is not WP_Error.
			if ( is_wp_error( $response ) ) {
				$result['error_message'] = $response->get_error_message() . esc_html__( 'If this error keeps displaying, please contact our support at wpbrigade.com!', 'loginpress-pro' );

				return $result;
			}

			// Make sure the response is OK (200).
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'loginpress-pro' ) . esc_html__( 'An error occurred, please try again. If this error keeps displaying, please contact our support at wpbrigade.com!', 'loginpress-pro' );

				return $result;
			}

			// Get the response data.
			$result['license_data'] = json_decode( wp_remote_retrieve_body( $response ), true );

			// Generate the error message.
			if ( false === $result['license_data']['success'] ) {

				switch ( $result['license_data']['error'] ) {

					case 'expired':
						$result['error_message'] = sprintf(
						/* Translators: Licence key is expired */
							esc_html__( 'Your license key expired on %s.', 'loginpress-pro' ),
                            date_i18n( get_option( 'date_format' ), strtotime( $result['license_data']['expires'], current_time( 'timestamp' ) ) ) // @codingStandardsIgnoreLine.
						);
						break;

					case 'revoked':
						$result['error_message'] = esc_html__( 'Your license key has been disabled.', 'loginpress-pro' );
						break;

					case 'missing':
						$result['error_message'] = esc_html__( 'Your license key is Invalid.', 'loginpress-pro' );
						break;

					case 'invalid':
					case 'site_inactive':
						$result['error_message'] = esc_html__( 'Your license is not active for this URL.', 'loginpress-pro' );
						break;

					case 'item_name_mismatch':
						/* Translators: Licence key is invalid */
						$result['error_message'] = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'loginpress-pro' ), self::LOGINPRESS_PRODUCT_NAME );
						break;

					case 'no_activations_left':
						$result['error_message'] = esc_html__( 'Your license key has reached its activation limit.', 'loginpress-pro' );
						break;

					default:
						$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'loginpress-pro' );
						break;
				}
			}

			update_option( self::REGISTRATION_DATA_OPTION_KEY, $result );

			return $result;
		}

		/**
		 * Try to deactivate the supplied license on our store.
		 *
		 * @since 3.0.0
		 * @param string $license License key to deactivate.
		 * @return array
		 */
		public static function deactivate_license( $license ) {

			$license = trim( $license );

			$result = array(
				'license_key'   => $license,
				'license_data'  => array(),
				'error_message' => '',
			);

			// Data to send in our API request.
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_id'    => self::LOGINPRESS_PRODUCT_ID,
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post(
				self::LOGINPRESS_SITE_URL,
				array(
					'sslverify' => false,
					'body'      => $api_params,
				)
			);

			// Make sure the response is not WP_Error.
			if ( is_wp_error( $response ) ) {
				$result['error_message'] = $response->get_error_message() . esc_html__( 'If this error keeps displaying, please contact our support at wpbrigade.com!', 'loginpress-pro' );

				return $result;
			}

			// Make sure the response is OK (200).
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'loginpress-pro' ) . esc_html__( 'An error occurred, please try again. If this error keeps displaying, please contact our support at wpbrigade.com!', 'loginpress-pro' );

				return $result;
			}

			// Get the response data.
			$result['license_data'] = json_decode( wp_remote_retrieve_body( $response ), true );

			// Generate the error message.
			if ( false === $result['license_data']['success'] ) {

				switch ( $result['license_data']['error'] ) {

					case 'expired':
						$result['error_message'] = sprintf(
						/* Translators: Licence key is expired */
							esc_html__( 'Your license key expired on %s.', 'loginpress-pro' ),
                            date_i18n( get_option( 'date_format' ), strtotime( $result['license_data']['expires'], current_time( 'timestamp' ) ) ) // @codingStandardsIgnoreLine.
						);
						break;

					case 'revoked':
						$result['error_message'] = esc_html__( 'Your license key has been disabled.', 'loginpress-pro' );
						break;

					case 'missing':
						$result['error_message'] = esc_html__( 'Your license key is Invalid.', 'loginpress-pro' );
						break;

					case 'invalid':
					case 'site_inactive':
						$result['error_message'] = esc_html__( 'Your license is not active for this URL.', 'loginpress-pro' );
						break;

					case 'item_name_mismatch':
						/* Translators: Licence key is invalid */
						$result['error_message'] = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'loginpress-pro' ), self::LOGINPRESS_PRODUCT_NAME );
						break;

					case 'no_activations_left':
						$result['error_message'] = esc_html__( 'Your license key has reached its activation limit.', 'loginpress-pro' );
						break;

					default:
						$result['error_message'] = esc_html__( 'An error occurred, please try again.', 'loginpress-pro' );
						break;
				}
			}

			update_option( self::REGISTRATION_DATA_OPTION_KEY, $result );

			return $result;
		}

		/**
		 * Check and get the license data.
		 *
		 * @since 3.0.0
		 * @param string $license The license key.
		 * @return false|array
		 */
		public static function check_license( $license ) {

			$license = trim( $license );

			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license,
				'item_id'    => self::LOGINPRESS_PRODUCT_ID,
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post(
				self::LOGINPRESS_SITE_URL,
				array(
					'sslverify' => false,
					'body'      => $api_params,
				)
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			return json_decode( wp_remote_retrieve_body( $response ), true );
		}

		/**
		 * Get the registration data helper function.
		 *
		 * @since 3.0.0
		 * @return false|array
		 */
		public static function get_registration_data() {
			return get_option( self::REGISTRATION_DATA_OPTION_KEY );
		}

		/**
		 * Is license activated.
		 *
		 * @since 3.0.0
		 * @return bool
		 */
		public static function is_activated() {

			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return false;
			}

			if ( ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Get the License type.
		 *
		 * @since 3.0.0
		 * @return string|false The license type.
		 */
		public static function get_license_type() {

			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return false;
			}

			if ( ! empty( $data['license_data']['success'] ) && ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {

				if ( 1 == $data['license_data']['price_id'] ) { // phpcs:ignore
					return 'Personal';
				}
				if ( 2 == $data['license_data']['price_id'] ) { // phpcs:ignore
					return 'Small Business';
				}
				if ( 3 == $data['license_data']['price_id'] || 6 == $data['license_data']['price_id'] ) { // phpcs:ignore
					return 'Agency';
				}
				if ( 4 == $data['license_data']['price_id'] ) { // phpcs:ignore
					return 'Ultimate';
				}
				if ( 7 == $data['license_data']['price_id'] || 10 == $data['license_data']['price_id'] ) { // phpcs:ignore
					return 'Startup';
				}
			}
		}

		/**
		 * Get the license id.
		 *
		 * @since 3.0.0
		 * @return int|false The license id.
		 */
		public static function get_license_id() {

			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return false;
			}

			if ( ! empty( $data['license_data']['success'] ) && ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {

				return $data['license_data']['price_id'];
			}
		}

		/**
		 * Check if the license is registered (has/had a valid license).
		 *
		 * @since 3.0.0
		 * @return bool
		 */
		public static function is_registered() {

			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return false;
			}

			if ( ! empty( $data['license_data']['success'] ) && ! empty( $data['license_data']['license'] ) && 'valid' === $data['license_data']['license'] ) {
				return true;
			}

			return false;
		}

		/**
		 * Mask on License Key.
		 *
		 * @since 2.1.1
		 * @version 6.1.0
		 * @param string $key License key.
		 * @return string
		 */
		public static function mask_license( $key ) {

			$license_parts  = str_split( $key, 4 );
			$i              = count( $license_parts ) - 1;
			$masked_license = '';

			foreach ( $license_parts as $license_part ) {
				if ( 0 === $i ) {
					$masked_license .= $license_part;
					continue;
				}

				$masked_license .= str_repeat( '&bull;', strlen( $license_part ) ) . '&ndash;';
				--$i;
			}

			return $masked_license;
		}

		/**
		 * Get the registered license key.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public static function get_registered_license_key() {
			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return '';
			}

			if ( empty( $data['license_key'] ) ) {
				return '';
			}

			return $data['license_key'];
		}

		/**
		 * Get the registered license status.
		 *
		 * @since 2.2.2
		 * @version 6.1.0
		 * @return string
		 */
		public static function get_registered_license_status() {
			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return '';
			}

			if ( ! empty( $data['error_message'] ) ) {
				return $data['error_message'];
			}

			switch ( $data['license_data']['license'] ) {
				case 'deactivated':
					$message = sprintf(
						/* Translators: Automatic Deactivated */
						esc_html__( 'Your license key has been deactivated on %s. Please Activate your license key to continue using Automatic Updates and Premium Support.', 'loginpress-pro' ),
                        '<strong>' . date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ) . '</strong>' // @codingStandardsIgnoreLine.
					);
					delete_option( 'loginpress_pro_license_key' );
					return $message;

				case 'revoked':
					$message = esc_html__( 'Your license key has been disabled.', 'loginpress-pro' );
					return $message;
			}

			return $data['license_data']['license'];
		}

		/**
		 * Check, if the registered license has expired.
		 *
		 * @since 3.0.0
		 * @return bool
		 */
		public static function has_license_expired() {
			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return true;
			}

			if ( empty( $data['license_data']['expires'] ) ) {
				return true;
			}

			// If it's a lifetime license, it never expires.
			if ( 'lifetime' === $data['license_data']['expires'] ) {
				return false;
			}

			$now             = new \DateTime();
			$expiration_date = new \DateTime( $data['license_data']['expires'] );

			$is_expired = $now > $expiration_date;

			if ( ! $is_expired ) {
				return false;
			}

			$prevent_check = get_transient( 'loginpress-pro-dont-check-license' );

			if ( $prevent_check ) {
				return true;
			}

			$new_license_data = self::check_license( self::get_registered_license_key() );
			set_transient( 'loginpress-pro-dont-check-license', true, DAY_IN_SECONDS );

			if ( empty( $new_license_data ) ) {
				return true;
			}

			if (
			! empty( $new_license_data['success'] ) &&
			! empty( $new_license_data['license'] ) &&
			'valid' === $new_license_data['license']
			) {
				$new_expiration_date = new \DateTime( $new_license_data['expires'] );

				$new_is_expired = $now > $new_expiration_date;

				if ( ! $new_is_expired ) {
					$data['license_data']['expires'] = $new_license_data['expires'];

					update_option( self::REGISTRATION_DATA_OPTION_KEY, $data );
				}

				return $new_is_expired;
			}

			return true;
		}

		/**
		 * Get license expiration date.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public static function get_expiration_date() {
			$data = self::get_registration_data();

			if ( empty( $data ) ) {
				return '';
			}

			return ( ! empty( $data['license_data']['expires'] ) ) ? $data['license_data']['expires'] : '';
		}

		/**
		 * Delete License options.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public static function del_license_data() {
			delete_option( 'loginpress_pro_license_key' );
			delete_option( self::REGISTRATION_DATA_OPTION_KEY );
		}
	}
}
