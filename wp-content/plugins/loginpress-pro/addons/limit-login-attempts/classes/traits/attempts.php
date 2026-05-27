<?php
/**
 * Prevent direct access.
 *
 * @package LoginPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LoginPress Limit Login Attempts Trait.
 *
 * Handles Some helping functions from class-attempts file.
 *
 * @package   LoginPress
 * @subpackage Traits\LimitLogin
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_LimitLogin_Attempts_Trait' ) ) {
	/**
	 * LoginPress Limit Login Attempts Trait.
	 *
	 * Handles the locking of the user.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\LimitLogin
	 * @since 6.1.0
	 */
	trait LoginPress_LimitLogin_Attempts_Trait {
		/**
		 * Callback for error message 'llla_error'.
		 *
		 * @param string $username The username.
		 * @param int    $attempt_type The attempt type (0 for failed, 1 for success).
		 * @since  3.0.0
		 * @version 6.1.0
		 * @return string|null Error message or null.
		 */
		public function limit_query( $username, $attempt_type = 0 ) {

			global $wpdb;
			$current_time = current_time( 'timestamp' ); // Returns floating point with microsecond precision. // @codingStandardsIgnoreLine.
			$gate         = $this->gateway();
			if ( empty( $gate ) ) {
				return null;
			}

			$attempts_allowed  = isset( $this->attempts_settings['attempts_allowed'] ) && intval( $this->attempts_settings['attempts_allowed'] ) !== 0 ? intval( $this->attempts_settings['attempts_allowed'] ) : intval( 4 );
			$lockout_increase  = isset( $this->attempts_settings['lockout_increase'] ) ? $this->attempts_settings['lockout_increase'] : '';
			$minutes_lockout   = isset( $this->attempts_settings['minutes_lockout'] ) && intval( $this->attempts_settings['minutes_lockout'] ) !== 0 ? intval( $this->attempts_settings['minutes_lockout'] ) : intval( 20 );
			$last_attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT `datentime` FROM `{$this->llla_table}` WHERE `ip` = %s ORDER BY `datentime` DESC", $this->ip ) ); // @codingStandardsIgnoreLine.

			if ( 1 === $attempt_type ) {
				if ( ! empty( $username ) ) {
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$this->llla_table} (ip, username, datentime, gateway, login_status) values (%s, %s, %s, %s, %s)", $this->ip, $username, $current_time, $gate, 'Success' ) ); // @codingStandardsIgnoreLine.
				}
			} else {

				if ( $last_attempt_time ) {
					$last_attempt_time = is_object( $last_attempt_time ) || is_array( $last_attempt_time ) ? $last_attempt_time->datentime : $last_attempt_time;
				}

				$lockout_time = $current_time - ( $minutes_lockout * 60 );

				$attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s AND `login_status` != %s", $this->ip, $lockout_time, 'Success' ) ); // @codingStandardsIgnoreLine.
				if ( $attempt_time + 1 < $attempts_allowed ) {
					// 0 Attempts overhead solution.
					$wpdb->query( $wpdb->prepare( "INSERT INTO {$this->llla_table} (ip, username, datentime, gateway, login_status) values (%s, %s, %s, %s, %s)", $this->ip, $username, $current_time, $gate, 'Failed' ) ); // @codingStandardsIgnoreLine.

					return $this->loginpress_attempts_error( $attempt_time );

				} else {
					wp_die( wp_kses_post( $this->loginpress_lockout_error( $last_attempt_time ) ), 403 );

				}
			}
		}

		/**
		 * Lockout error message.
		 *
		 * @param string $last_attempt_time time of the last attempt.
		 * @since  3.0.0
		 * @version 6.1.0
		 * @return string $lockout_message Custom error message.
		 */
		public function loginpress_lockout_error( $last_attempt_time ) {
			global $wpdb;
			$current_time     = time(); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			$minutes_set      = isset( $this->attempts_settings['minutes_lockout'] ) && intval( $this->attempts_settings['minutes_lockout'] ) !== 0 ? intval( $this->attempts_settings['minutes_lockout'] ) : intval( 20 );
			$lockout_window   = $current_time - ( $minutes_set * 60 );
			$attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) && intval( $this->attempts_settings['attempts_allowed'] ) !== 0 ? intval( $this->attempts_settings['attempts_allowed'] ) : intval( 4 );

			// Check if a 'Locked' entry already exists within the lockout window.
			$existing_lock = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->llla_table} WHERE ip = %s AND login_status = %s AND datentime >= %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$this->ip,
					'Locked',
					$lockout_window
				)
			);

			if ( ! $existing_lock ) {
				// No recent locked entry, insert a new one.
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"INSERT INTO {$this->llla_table} (ip, username, datentime, gateway, login_status) VALUES (%s, %s, %s, %s, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$this->ip,
						$this->username,
						$current_time,
						$this->gateway(),
						'Locked'
					)
				);

				$this->lp_send_lockout_notification( $this->username, $this->ip, $this->gateway() );
			}

			$time            = intval( $current_time - $last_attempt_time );
			$count           = (int) ( $time / 60 ) % 60; // Minutes since last attempt.
			$lockout_message = isset( $this->attempts_settings['lockout_message'] ) ? sanitize_text_field( $this->attempts_settings['lockout_message'] ) : '';
			$message         = __( 'You have exceeded the amount of login attempts.', 'loginpress-pro' );

			if ( $count < $minutes_set || 1 === $attempts_allowed ) {

				$remain = empty( $last_attempt_time ) || $count > $minutes_set ? $minutes_set : $minutes_set - $count;
				$remain = 0 === $remain ? 1 : $remain;
				$minute = ( 1 === $remain ) ? 'minute' : 'Minutes';

				if ( empty( $lockout_message ) ) {
					$message = sprintf( // translators: Default lockout message.
						__( '%1$sError:%2$s Too many failed attempts. You are locked out for %3$s %4$s.', 'loginpress-pro' ),
						'<strong>',
						'</strong>',
						$remain,
						$minute
					);
				} else {
					$lockout_message = str_replace( '%TIME%', $remain . ' ' . $minute, $lockout_message );
					$message         = sprintf( // translators: User's lockout message.
						__( '%1$sError:%2$s %3$s', 'loginpress-pro' ),
						'<strong>',
						'</strong>',
						$lockout_message
					);
				}
			}

			return $message;
		}


		/**
		 * LoginPress Limit Login Attempts Time Checker.
		 *
		 * @since  3.0.0
		 * @return bool True if locked out, false otherwise.
		 */
		public function llla_time() {
			if ( is_user_logged_in() ) {
				return;
			}
			global $wpdb;
			$current_time = current_time( 'timestamp' ); // @codingStandardsIgnoreLine.

			$attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) && intval( $this->attempts_settings['attempts_allowed'] ) !== 0 ? intval( $this->attempts_settings['attempts_allowed'] ) : intval( 4 );
			$lockout_increase = isset( $this->attempts_settings['lockout_increase'] ) ? $this->attempts_settings['lockout_increase'] : '';
			$minutes_lockout  = isset( $this->attempts_settings['minutes_lockout'] ) && intval( $this->attempts_settings['minutes_lockout'] ) !== 0 ? intval( $this->attempts_settings['minutes_lockout'] ) : intval( 20 );

			$lockout_time = $current_time - ( $minutes_lockout * 60 );
			if ( ! isset( $this->attempt_time ) || null === $this->attempt_time ) {
				$this->attempt_time = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->llla_table}` WHERE `ip` = %s AND `datentime` > %s AND `whitelist` = 0 AND `login_status` != %s", $this->ip, $lockout_time, 'Success' ) ); // @codingStandardsIgnoreLine.
			}
			// 0 Attempts overhead solution.
			if ( $this->attempt_time < $attempts_allowed ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Attempts error message.
		 *
		 * @param int $count counter.
		 * @return string [Custom error message]
		 * @since 6.0.0
		 */
		public function loginpress_attempts_error( $count ) {
			global $pagenow;
			$attempts_allowed = isset( $this->attempts_settings['attempts_allowed'] ) && intval( $this->attempts_settings['attempts_allowed'] ) !== 0 ? intval( $this->attempts_settings['attempts_allowed'] ) : intval( 4 );

			$remains = $attempts_allowed - $count - 1;

			if ( isset( $this->attempts_settings['attempts_left_message'] ) && ! empty( $this->attempts_settings['attempts_left_message'] ) ) {
				$attempts_message = $this->attempts_settings['attempts_left_message'];
				$attempts_message = str_replace( '%count%', $remains, $attempts_message );
				// Check if the EDD class exists.
				if ( class_exists( 'Easy_Digital_Downloads' ) && isset( $_POST['edd_login_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					// translators: Modify the message without "ERROR" for EDD.
					$attempts_left_message = sprintf( __( ' %1$s', 'loginpress-pro' ),  $attempts_message ); // @codingStandardsIgnoreLine.
				} else {    // translators: Error msg for default forms.
					$attempts_left_message = sprintf( __( '%1$sERROR:%2$s %3$s', 'loginpress-pro' ), '<strong>', '</strong>', $attempts_message );
				}
			} else {
				/* Translators: The attempts. */
				$attempts_left_message = sprintf( __( '%1$sERROR:%2$s You have only %3$s attempts', 'loginpress-pro' ), '<strong>', '</strong>', $remains );

				// Check if the EDD class exists.
				if ( class_exists( 'Easy_Digital_Downloads' ) && isset( $_POST['edd_login_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					// translators: Modify the message without "ERROR" for EDD.
					$attempts_left_message = sprintf( __( 'You have only %3$s attempts remaining.', 'loginpress-pro' ), '<strong>', '</strong>', $remains );
				}
			}

			/**
			 * LoginPress limit Login Attempts Custom Error Message for the specific Attempt.
			 *
			 * @param string $attempt_message The default Limit Login Attempts Error message.
			 * @param int    $count           The number of attempt from the user.
			 * @param int    $remaining       The remaining attempts of the users.
			 *
			 * @since 3.0.0
			 * @return array $llla_attempt_args the modified arguments.
			 */

			$llla_attempt_message = apply_filters( 'loginpress_attempt_error', $attempts_left_message, $count, $remains );
			if ( class_exists( 'Easy_Digital_Downloads' ) && isset( $_POST['edd_login_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( $remains >= 1 ) {
					edd_set_error( 'loginpress-pro', $llla_attempt_message );
				}
			}

			$allowed_html = array(
				'a'      => array(),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'i'      => array(),
			);

			return wp_kses( $llla_attempt_message, $allowed_html );
		}

		/**
		 * Check the gateway.
		 *
		 * @return string
		 * @since  3.0.0
		 * @version 6.1.0
		 */
		public function gateway() {
			/**
			* Apply a filter to allow passing different login gateways (e.g., LifterLMS, LearnDash, etc.).
			*
			* @param string|bool $gateway Default is false. Should be replaced with custom gateway string.
			* @since 5.0.0
			*/
			$gateway_passed = apply_filters( 'loginpress_gateway_passed', false );
			$gateway        = '';
			if ( isset( $_POST['woocommerce-login-nonce'] ) ) {
				wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-login-nonce'] ) ), 'woocommerce-login' );
			}
			if ( isset( $_POST['woocommerce-login-nonce'] ) ) {
				$gateway = esc_html__( 'WooCommerce', 'loginpress-pro' );
			} elseif ( isset( $GLOBALS['wp_xmlrpc_server'] ) && is_object( $GLOBALS['wp_xmlrpc_server'] ) ) {
				$gateway = esc_html__( 'XMLRPC', 'loginpress-pro' );
			} elseif ( isset( $_POST['edd_login_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edd_login_nonce'] ) ), 'edd-login' ) ) { // Check for EDD login form nonce.
				$gateway = esc_html__( 'EDD Login', 'loginpress-pro' );
			} elseif ( $gateway_passed ) {
				$gateway = $gateway_passed;
			} elseif ( isset( $_GET['lpsl_login_id'] ) ) {
				$social_id = sanitize_text_field( wp_unslash( $_GET['lpsl_login_id'] ) );
				if ( 'success_facebook' === $social_id ) {
					$gateway = esc_html__( 'Facebook Login', 'loginpress-pro' );
				} elseif ( 'success_twitter' === $social_id ) {
					$gateway = esc_html__( 'X(twitter) Login', 'loginpress-pro' );
				} elseif ( 'success_gplus' === $social_id ) {
					$gateway = esc_html__( 'Google Login', 'loginpress-pro' );
				} elseif ( 'success_linkedin' === $social_id ) {
					$gateway = esc_html__( 'Linkedin Login', 'loginpress-pro' );
				} elseif ( 'success_microsoft' === $social_id ) {
					$gateway = esc_html__( 'Microsoft Login', 'loginpress-pro' );
				} elseif ( 'success_apple' === $social_id ) {
					$gateway = esc_html__( 'Apple Login', 'loginpress-pro' );
				} elseif ( 'success_github' === $social_id ) {
					$gateway = esc_html__( 'Github Login', 'loginpress-pro' );
				} elseif ( 'success_discord' === $social_id ) {
					$gateway = esc_html__( 'Discord Login', 'loginpress-pro' );
				} elseif ( 'success_wordpress' === $social_id ) {
					$gateway = esc_html__( 'WordPress Login', 'loginpress-pro' );
				}
			} else {
				$gateway = esc_html__( 'WP Login', 'loginpress-pro' );
			}

			return $gateway;
		}

		/**
		 * Get correct remote address.
		 *
		 * @param string $type_name The address type.
		 * @since  3.1.1
		 * @return string The IP address.
		 */
		public function get_address( $type_name = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

			$ip_address = '';
			if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ) );
			} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) && ! empty( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ) );
			} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) && ! empty( $_SERVER['HTTP_FORWARDED'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) );
			} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) && ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			} else {
				$ip_address = 'UNKNOWN';
			}

			return $ip_address;
		}

		/**
		 * Send lockout notification email.
		 *
		 * @param string $username The username that was locked out.
		 * @param string $ip The IP address that was locked out.
		 * @param string $gateway The gateway where the lockout occurred.
		 * @since 6.0.0
		 * @return void
		 */
		public function lp_send_lockout_notification( $username, $ip, $gateway ) {
			if ( ! isset( $this->attempts_settings['enable_lockout_notification'] ) ||
			'on' !== $this->attempts_settings['enable_lockout_notification'] ) {
				return;
			}

			$email_addresses = isset( $this->attempts_settings['notification_email'] ) ?
				$this->attempts_settings['notification_email'] : '';

			if ( empty( $email_addresses ) ) {
				return;
			}

			// Validate and clean email addresses.
			$emails = array_filter(
				array_map(
					function ( $email ) {
						$email = trim( $email );
						return is_email( $email ) ? $email : false;
					},
					explode( ',', $email_addresses )
				)
			);

			// Bail if no valid emails remain.
			if ( empty( $emails ) ) {
				return;
			}
			$subject = isset( $this->attempts_settings['notification_subject'] ) ?
				$this->attempts_settings['notification_subject'] : '%username% is locked out at %sitename%';
			$body    = isset( $this->attempts_settings['notification_body'] ) ?
				$this->attempts_settings['notification_body'] : $this->lp_get_default_email_body();

			// Replace variables.
			$replacements = array(
				'%sitename%' => get_bloginfo( 'name' ),
				'%username%' => $username,
				'%date%'     => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				'%ip%'       => $ip,
				'%gateway%'  => $gateway,
			);

			$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
			$body    = str_replace( array_keys( $replacements ), array_values( $replacements ), $body );

			// Add attempt details to the email body.
			$body .= "\n\n" . $this->lp_get_attempt_details( $ip, $username );

			$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

			wp_mail( implode( ',', $emails ), $subject, $body, $headers );
		}

		/**
		 * Get default email body.
		 *
		 * @since 6.0.0
		 * @return string The default email body.
		 */
		private function lp_get_default_email_body() {
			return __( 'Hello,', 'loginpress-pro' ) . "\n\n" .
				/* translators: 1: Site name, 2: Username, 3: IP address, 4: Date, 5: Gateway */
				sprintf( __( 'At your site %1$s, a user: %2$s was recently locked out from the IP: %3$s on %4$s while trying to log in through %5$s.', 'loginpress-pro' ), '%sitename%', '%username%', '%ip%', '%date%', '%gateway%' ) . "\n\n" .
				__( 'Please visit your dashboard to unlock this user or add them to the blacklist.', 'loginpress-pro' );
		}

		/**
		 * Get attempt details for the email.
		 *
		 * @param string $ip The IP address.
		 * @param string $username The username.
		 * @since 6.0.0
		 * @return string The attempt details.
		 */
		private function lp_get_attempt_details( $ip, $username ) {
			global $wpdb;

			$attempts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT * FROM `{$this->llla_table}` WHERE `ip` = %s AND `username` = %s ORDER BY `datentime` DESC LIMIT 5", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$ip,
					$username
				)
			);

			if ( empty( $attempts ) ) {
				return '';
			}

			$output  = __( 'Attempt Details:', 'loginpress-pro' ) . "\n";
			$output .= "----------------\n";

			foreach ( $attempts as $attempt ) {
				$output .= sprintf(
					/* translators: 1: Date, 2: Gateway, 3: IP, 4: Username */
					__( 'Date: %1$s', 'loginpress-pro' ),
					date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $attempt->datentime )
				) . "\n";
				$output .= sprintf(
					/* translators: %s: Gateway */
					__( 'Gateway: %s', 'loginpress-pro' ),
					esc_html( $attempt->gateway )
				) . "\n";
				$output .= sprintf(
					/* translators: %s: IP */
					__( 'IP: %s', 'loginpress-pro' ),
					esc_html( $attempt->ip )
				) . "\n";
				$output .= sprintf(
					/* translators: %s: Username */
					__( 'Username: %s', 'loginpress-pro' ),
					esc_html( $attempt->username )
				) . "\n\n";
			}

			return $output;
		}

		/** Callback for `wp_login` action hook.
		 *
		 * @param string  $user_login The user login.
		 * @param WP_User $user The user object.
		 *
		 * @since 6.0.0
		 * @version 6.1.0
		 */
		public function llla_login_attempts_wp_login( $user_login, $user ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( isset( $_GET['lpsl_login_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$social_id = sanitize_text_field( wp_unslash( $_GET['lpsl_login_id'] ) ); // phpcs:ignore
				$success   = explode( '_', $social_id );
				if ( 'success' === $success[0] ) {

					$this->limit_query( $user_login, 1 );
					return '';
				}
			}
			$this->limit_query( $user_login, 1 );
		}
		/**
		 * Detects bot behavior based on login failures.
		 *
		 * @param string   $username Attempted username.
		 * @param WP_Error $error    WP_Error object containing failure reason.
		 * @since 6.0.0
		 * @version 6.1.0
		 */
		public function llla_login_attempts_wp_login_failed( $username, $error ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			if ( ! isset( $this->attempts_settings['ip_intelligence'] ) || 'off' === $this->attempts_settings['ip_intelligence'] ) {
				return '';
			}
			global $wpdb;

			$ip         = $this->ip;
			$table_name = $wpdb->prefix . 'loginpress_limit_login_details';
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			$headers    = $this->get_request_headers();

			$is_bot = false;

			// 1. Analyze recent failed login timestamps.
			$recent_attempts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare("SELECT datentime FROM $table_name WHERE ip = %s AND login_status = 'Failed' ORDER BY datentime DESC LIMIT 10", $ip ) // phpcs:ignore
			);

			$recent_attempts_count = count( $recent_attempts );
			if ( $recent_attempts_count >= 2 ) {
				$timestamps = array_reverse( array_map( 'floatval', wp_list_pluck( $recent_attempts, 'datentime' ) ) );
				$intervals  = array();
				for ( $i = 1; $i < $recent_attempts_count; $i++ ) {
					$intervals[] = $timestamps[ $i ] - $timestamps[ $i - 1 ];
				}

				$fast       = array_filter( $intervals, fn( $i ) => $i < 0.3 );
				$consistent = false;

				if ( count( $intervals ) >= 3 ) {
					$avg        = array_sum( $intervals ) / count( $intervals );
					$consistent = array_reduce(
						$intervals,
						fn( $carry, $val ) => $carry && ( abs( $val - $avg ) / $avg < 0.1 ),
						true
					);
				}

				$duration = end( $timestamps ) - reset( $timestamps );

				if ( count( $fast ) >= 2 || $consistent || $duration < 10 ) {
					$is_bot = true;
				}
			}

			// 2. Check User-Agent for headless bots
			if ( empty( $user_agent ) || preg_match( '/(Headless|PhantomJS|python-requests|curl|bot|crawler|scrapy|Go-http-client)/i', $user_agent ) ) {
				$is_bot = true;
			}

			// 3. Check for missing key headers (non-browser requests).

			// Block or blacklist bot.
			if ( $is_bot ) {
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE $table_name SET blacklist = 1 WHERE ip = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$ip
					)
				);

			}
		}

		/**
		 * Get all request headers in lowercase keys.
		 *
		 * @return array
		 * @since 6.0.0
		 * @version 6.1.0
		 */
		private function get_request_headers() {
			$headers = array();

			foreach ( $_SERVER as $key => $value ) {
				if ( str_starts_with( $key, 'HTTP_' ) ) {
					$header_key             = strtolower( str_replace( '_', '-', substr( $key, 5 ) ) );
					$headers[ $header_key ] = sanitize_text_field( wp_unslash( $value ) );
				}
			}

			return $headers;
		}
	}
}
