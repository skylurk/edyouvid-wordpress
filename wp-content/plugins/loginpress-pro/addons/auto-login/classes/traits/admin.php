<?php
/**
 * LoginPress AutoLogin Admin Trait.
 *
 * Contains admin functionality for auto-login features.
 *
 * @package LoginPress
 * @category Core
 * @author WPBrigade
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_AutoLogin_Admin_Trait' ) ) {

	/**
	 * LoginPress AutoLogin Admin Trait.
	 *
	 * @since 3.0.0
	 * @version 6.1.0
	 */
	trait LoginPress_AutoLogin_Admin_Trait {

		/**
		 * Load CSS and JS files at admin side on loginpress-settings page only.
		 *
		 * @param string $hook The page ID.
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function admin_scripts( $hook ) {

			if ( 'toplevel_page_loginpress-settings' !== $hook ) {
				return;
			}

			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );

			wp_enqueue_style( 'loginpress_autologin_stlye', plugins_url( '../../assets/css/style.css', __FILE__ ), array(), LOGINPRESS_PRO_VERSION );
			wp_enqueue_style( 'loginpress_datatables_style', LOGINPRESS_PRO_DIR_URL . 'assets/css/jquery.dataTables.min.css', array(), LOGINPRESS_PRO_VERSION );
			wp_enqueue_script( 'loginpress_datatables_js', LOGINPRESS_PRO_DIR_URL . 'assets/js/jquery.dataTables.min.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );

			wp_enqueue_style( 'loginpress_data_tables_responsive_autologin', LOGINPRESS_PRO_DIR_URL . 'assets/css/rowReorder.dataTables.min.css', array(), LOGINPRESS_PRO_VERSION );
			wp_enqueue_style( 'loginpress_data_tables_fixedColumns_order', LOGINPRESS_PRO_DIR_URL . 'assets/css/fixedColumns.dataTables.min.css', array(), LOGINPRESS_PRO_VERSION );
			wp_enqueue_script( 'loginpress_data_tables_responsive_autologin_row', LOGINPRESS_PRO_DIR_URL . 'assets/js/dataTables.rowReorder.min.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );
			wp_enqueue_script( 'loginpress_data_tables_js_fixedColumns', LOGINPRESS_PRO_DIR_URL . 'assets/js/dataTables.fixedColumns.min.js', array( 'jquery' ), LOGINPRESS_PRO_VERSION, false );
		}

		/**
		 * Adding a tab for AutoLogin at LoginPress Settings Page.
		 *
		 * @param array $loginpress_tabs Rest of the settings tabs of LoginPress.
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return array AutoLogin tab.
		 */
		public function loginpress_autologin_tab( $loginpress_tabs ) {

			$autologin_tab = array(
				array(
					'id'         => 'loginpress_autologin',
					'title'      => __( 'Auto Login', 'loginpress-pro' ),
					'sub-title'  => __( 'No More Manual Login', 'loginpress-pro' ),
					/* Translators: %1$s The line break tag. */
					'desc'       => sprintf( __( '%1$sThe Auto Login add-on for LoginPress allows administrators to generate unique URLs for specific users who do not need to enter a password to access the site.%2$s', 'loginpress-pro' ), '<p>', '</p>' ),
					'video_link' => 'M2M3G2TB9Dk',
				),
			);

			$loginpress_pro_templates = array_merge( $loginpress_tabs, $autologin_tab );

			return $loginpress_pro_templates;
		}

		/**
		 * Array of the Setting Fields for AutoLogin.
		 *
		 * @param array $setting_array Settings fields of free version.
		 * @since  1.0.0
		 * @version 6.1.0
		 * @return array AutoLogin settings fields.
		 */
		public function loginpress_autologin_settings_array( $setting_array ) {

			$_autologin_settings = array(
				array(
					'name'  => 'loginpress_autologin',
					'label' => __( 'Search Username', 'loginpress-pro' ),
					'desc'  => __( 'Username for making a login magic link for a specific user.', 'loginpress-pro' ),
					'type'  => 'autologin',
				),
			);
			$_autologin_settings = array(
				'loginpress_autologin' => $_autologin_settings,
			);

			return( array_merge( $_autologin_settings, $setting_array ) );
		}

		/**
		 * A callback function that will show a search field under AutoLogin tab.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return string HTML of Username Search.
		 */
		public function loginpress_autologin_callback() {

			$html = '<input type="text" name="loginpress_autologin_search" id="loginpress_autologin_search" value="" placeholder="' . esc_html__( ' Type Username...', 'loginpress-pro' ) . '" />';

			return $html;
		}

		/**
		 * A callback function that will show search result under the search field.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function autologin_script_content() {

			/**
			 * Check to apply the script only on the LoginPress Settings page.
			 *
			 * @since 1.0.9
			 */
			if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'loginpress-settings' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}
			$html = apply_filters( 'loginpress_auto_login_after_description', '' );

			$loginpress_popup = '<div class="loginpress-edit-popup-container" style="display: none;" data-for="NULL"></div>';

			$html .= $loginpress_popup .
			'<div class="row-per-page" style="display: flex; align-items: center; font-size: 14px;">
				<span style="margin-right: 8px;">' . esc_html__( 'Show Entries', 'loginpress-pro' ) . '</span>
				<select id="loginpress_autologin_users_select" class="selectbox" style="width: 100px;">
					<option value="10">10</option>
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="100">100</option>
				</select>
			</div>
			<table id="loginpress_autologin_users" class="loginpress_autologin_users">
			<thead><tr>
			<th class="loginpress_user_id">' . esc_html__( 'User ID', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_userName">' . esc_html__( 'Username', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_email">' . esc_html__( 'Email', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_url">' . esc_html__( 'Auto login URL', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_status">' . esc_html__( 'Status', 'loginpress-pro' ) . '</th>
			<th class="loginpress_action">' . esc_html__( 'Action', 'loginpress-pro' ) . '</th>
			</tr></thead><tfoot><tr>
			<th class="loginpress_user_id">' . esc_html__( 'User ID', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_userName">' . esc_html__( 'Username', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_email">' . esc_html__( 'Email', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_url">' . esc_html__( 'Auto login URL', 'loginpress-pro' ) . '</th>
			<th class="loginpress_log_status">' . esc_html__( 'Status', 'loginpress-pro' ) . '</th>
		<th class="loginpress_action">' . esc_html__( 'Action', 'loginpress-pro' ) . '</th>
		</tr></tfoot><tbody><span class="autologin-sniper"></span></tbody></table>';
			echo $html; // phpcs:ignore
		}

		/**
		 * A callback function that will show search result under the search field.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function autologin_script_html() {

			check_ajax_referer( 'loginpress-user-autologin-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			/**
			 * Check to apply the script only on the LoginPress Settings page.
			 *
			 * @since 1.0.9
			 */

			if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'loginpress-settings' ) {
				return;
			}

			$user_query = new WP_User_Query(
				array(
					'meta_key' => 'loginpress_autologin_user',  // @codingStandardsIgnoreLine.
				)
			);

			$autologin_user = $user_query->get_results();
			$html           = '';
			// Check for results.
			if ( ! empty( $autologin_user ) ) {
				// loop through each user.
				foreach ( $autologin_user as $user ) {
					// get all the user's data.
					$user_info          = get_userdata( $user->ID ); // Get User Information.
					$user_meta          = get_user_meta( $user->ID, 'loginpress_autologin_user', true ); // get autologin user meta.
					$link_state         = 'enable' === $user_meta['state'] ? 'disable' : 'enable'; // Get User Link state.
					$is_disabled        = 'enable' === $user_meta['state'] ? '' : 'autologin-disabled'; // Is link disbaled?.
					$disable_field      = 'enable' === $user_meta['state'] ? '' : 'disabled'; // Disable Field.
					$expired            = ''; // Is expired .
					$not_approved       = ''; // Is not approved.
					$disabled           = $link_state; // Link state.
					$expired_class      = ''; // Expired Class.
					$is_expire          = ( 'unchecked' === $user_meta['expire'] ) ? '' : 'checked'; // Lifetime or not.
					$cog_disabled       = 'enable' === $link_state ? 'disabled' : ''; // State Disabling.
					$user_meta_status   = get_user_meta( $user->ID, 'loginpress_user_verification', true ); // Get user verification.
					$loginpress_options = get_option( 'loginpress_setting' ); // LoginPress settings.
					$is_expired         = ''; // Has expired.
					$clicks             = ''; // Total clicks.
					$total_clicks       = isset( $user_meta['link_count'] ) ? intval( $user_meta['link_count'] ) : 0;
					$lefted_clicks      = isset( $user_meta['link_click'] ) ? intval( $user_meta['link_click'] ) : 0;
					if ( empty( $is_expire ) ) {
						$expiration     = ( ! empty( $user_meta['duration'] ) ) ? $user_meta['duration'] : gmdate( 'Y-m-d' );
						$now            = time(); // or your date as well.
						$your_date      = strtotime( $expiration );
						$date_diff      = $your_date - $now;
						$remaining_days = intval( ceil( $date_diff / ( 60 * 60 * 24 ) ) );

						if ( isset( $loginpress_options['enable_user_verification'] ) && 'on' === $loginpress_options['enable_user_verification'] && 'inactive' === $user_meta_status ) {
							$expired       = '<span class="loginpress-autologin-remain-notice loginpress-autologin-remain-notice-red">' . esc_html__( 'User Not Approved', 'loginpress-pro' ) . '</span>';
							$not_approved  = 'disabled';
							$expired_class = 'autologin-expired';
							$is_disabled   = 'disabled';
							$disable_field = 'disabled';

						} elseif ( gmdate( 'Y-m-d' ) <= $expiration && ( isset( $user_meta['state'] ) && 'enable' === $user_meta['state'] ) ) {
							/* translators: Days Left. */
							$remain  = 1 === $remaining_days ? sprintf( __( '%1$s Day Left', 'loginpress-pro' ), $remaining_days ) : sprintf( __( '%1$s Days Left', 'loginpress-pro' ), $remaining_days );
							$expired = '<span class="loginpress-autologin-remain-notice">' . esc_html( $remain ) . '</span>';
							// If last day is left, gmdate() puts -0 for the last day.
							if ( -0 === $remaining_days ) {

								$expired = '<span class="loginpress-autologin-remain-notice loginpress-autologin-remain-notice-last-day">' . esc_html__( 'Last Day', 'loginpress-pro' ) . '</span>';
							}
						} elseif ( 'enable' !== $user_meta['state'] && ( '' === $user_meta_status || 'active' === $user_meta_status ) ) { // If Link is not disabled and user is verified.
							$expired = '<span class="loginpress-autologin-remain-notice loginpress-autologin-remain-notice-red">' . esc_html__( 'Disabled', 'loginpress-pro' ) . '</span>';

						} elseif ( gmdate( 'Y-m-d' ) > $expiration ) {
							$expired       = '<span class="loginpress-autologin-remain-notice-red">' . esc_html__( 'Link Expired', 'loginpress-pro' ) . '</span>';
							$expired_class = 'autologin-expired';
							$is_expired    = 'disabled';
						}
					} elseif ( 'enable' !== $user_meta['state'] && ( '' === $user_meta_status || 'active' === $user_meta_status ) ) { // If Link is not disabled and user is verified.
							$expired = '<span class="loginpress-autologin-remain-notice loginpress-autologin-remain-notice-red">' . esc_html__( 'Disabled', 'loginpress-pro' ) . '</span>';

					} elseif ( 'inactive' !== $user_meta_status ) {
						$expired = '<span class="loginpress-autologin-remain-notice">' . esc_html__( 'Lifetime', 'loginpress-pro' ) . '</span>';

					} elseif ( isset( $loginpress_options['enable_user_verification'] ) && 'on' === $loginpress_options['enable_user_verification'] && 'inactive' === $user_meta_status ) {
						$expired       = '<span class="loginpress-autologin-remain-notice loginpress-autologin-remain-notice-red">' . esc_html__( 'User Not Approved', 'loginpress-pro' ) . '</span>';
						$not_approved  = 'disabled';
						$expired_class = 'autologin-expired';
						$is_disabled   = 'disabled';
						$disable_field = 'disabled';

					}
					if ( $total_clicks > 0 && 'checked' !== $user_meta['unlimited_click'] ) {
						$remaining = sprintf(
						// translators: Clicks used.
							__( '%1$s/%2$s Clicks Used', 'loginpress-pro' ),
							$lefted_clicks,
							$total_clicks
						);
						if ( $total_clicks === $lefted_clicks ) {
							$clicks = '</br><span class="loginpress-clicks-remain-notice loginpress-clicks-remain-notice-red">' . esc_html( $remaining ) . '</span>';
						} else {
							$clicks = '</br><span class="loginpress-clicks-remain-notice">' . esc_html( $remaining ) . '</span>';
						}
					} else {
						$clicks = '</br><span class="loginpress-clicks-remain-notice">' . esc_html__( 'Unlimited Clicks', 'loginpress-pro' ) . '</span>';
					}
					$html .= '<tr class="' . esc_attr( $expired_class . ' ' . $is_disabled ) . '" id="loginpress_user_id_' . esc_attr( $user->ID ) . '" data-autologin="' . esc_attr( $user->ID ) . '"><td><div class="lp-tbody-cell">' . esc_html( $user_info->ID ) . '</div></td><td class="loginpress_user_name"><div class="lp-tbody-cell">' . esc_html( $user_info->user_login ) . '</div></td><td class="loginpress_user_email"><div class="lp-tbody-cell">' . esc_html( $user_info->user_email ) . '</div></td><td class="loginpress_autologin_code"><div class="lp-tbody-cell"><span class="autologin-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span>
					<input type="text" class="loginpress-autologin-code" dir="rtl" value="' . esc_url( home_url() . '/?loginpress_code=' . $user_meta['code'] ) . '" readonly>					
					<div class="copy-email-icon-wrapper autologin-copy-code">
						<svg class="autologin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_27_294)">
						<path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"/>
						<path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"/>
						</g>
						<defs>
						<clipPath id="clip0_27_294">
						<rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"/>
						</clipPath>
						</defs>
						</svg>
					</div>
					<span class="loginpress-autologin-email-sent" >Email Sent </span>
					<span class="loginpress-autologin-remain-notice" data-attr="data-dayleft">' . LoginPress_AutoLogin_Utilities::sanitize_html( $expired ) . ' </span></div></td>
					<td class="loginpress_user_status" data-attr="data-dayleft"><div class="lp-tbody-cell">' . LoginPress_AutoLogin_Utilities::sanitize_html( $expired . $clicks ) . '</div></td>
					<td class="loginpress_autologin_actions"><div class="lp-tbody-cell">
					<button type="button" class="button loginpress-del-link" value="' . esc_attr( __( 'Delete', 'loginpress-pro' ) ) . '" id="loginpress_delete_link" /></button>
					<div class="loginpress-action-list-menu-wrapper">
					<div class="loginpress-action-menu-burger-wrapper"><span class="loginpress-action-menu-burger-open-icon dashicons dashicons-menu-alt2"></span>
					<span class="loginpress-action-menu-burger-close-icon dashicons dashicons-no-alt"></span></div>
					<ul class="action-menu-list">
					<li><input type="button" class="button loginpress-new-link" value="' . esc_attr( __( 'New Link', 'loginpress-pro' ) ) . '" id="loginpress_create_new_link" ' . esc_attr( $disable_field ) . '/></li>
					<li><input type="button" class="button loginpress-autologin-duration" value="' . esc_attr( __( 'Link Duration', 'loginpress-pro' ) ) . '" ' . esc_attr( $disable_field ) . '/></li>
					<li><input type="button" class="button loginpress-autologin-link-count" value="' . esc_attr( __( 'Link Count', 'loginpress-pro' ) ) . '" ' . esc_attr( $disable_field ) . '/></li>
					<li><input type="button" class="button loginpress-autologin-state ' . esc_attr( $link_state ) . '" data-state="' . esc_attr( $link_state ) . '" value="' . esc_attr( $disabled ) . '"' . esc_attr( $not_approved ) . ' ' . esc_attr( $is_expired ) . ' /></li>
					<li><input type="button" class="button loginpress-autologin-email-settings" value="' . esc_attr( __( 'Email To Multiple', 'loginpress-pro' ) ) . '" ' . esc_attr( $cog_disabled ) . ' ' . esc_attr( $is_expired ) . ' ' . esc_attr( $disable_field ) . ' /></span></li>
					<li><input type="button" class="button loginpress-autologin-email" value="' . esc_attr( __( 'Email User', 'loginpress-pro' ) ) . '" ' . esc_attr( $disable_field ) . ' ' . esc_attr( $is_expired ) . ' /></li>
					</ul> 
					</div> 
					</div></td></tr>';
				}
			} else {
				wp_die();
			}

			wp_send_json_success( array( 'html' => $html ) ); // @codingStandardsIgnoreLine.
		}

		/**
		 * Initialize link count updater.
		 *
		 * Checks if the user has a valid login link.
		 * Checks if the link has reached its maximum clicks.
		 * If the link has reached its maximum clicks, log the user out and redirect to the login page with an error.
		 * If the link has not reached its maximum clicks, update the link click count and log the user in.
		 *
		 * @return void
		 * @since 4.0.0
		 */
		public function init_link_count_updater() {

			if ( isset( $_GET['loginpress_code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$loginpress_code = sanitize_text_field( wp_unslash( $_GET['loginpress_code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$args = array(
					'fields'     => 'ids', // Only fetch user IDs instead of full objects.
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							'relation' => 'AND',
							array(
								'key'     => 'loginpress_autologin_user',
								'value'   => wp_json_encode( $loginpress_code ),
								'compare' => 'LIKE',
							),
					),
				);

				$user_ids = get_users( $args );

				if ( ! empty( $user_ids ) ) {
					$user_id = $user_ids[0];
					$meta    = get_user_meta( $user_id, 'loginpress_autologin_user', true );
					if ( isset( $meta['unlimited_click'] ) && 'checked' !== $meta['unlimited_click'] ) {

						if ( isset( $meta['code'] ) && $meta['code'] === $loginpress_code ) {

							$click_count = isset( $meta['link_click'] ) ? intval( $meta['link_click'] ) : 0;
							$max_clicks  = isset( $meta['link_count'] ) ? intval( $meta['link_count'] ) : 0;

							if ( $max_clicks > 0 && $click_count < $max_clicks ) {
								++$click_count;

								update_user_meta(
									$user_id,
									'loginpress_autologin_user',
									array_merge(
										$meta,
										array(
											'link_count' => $max_clicks,
											'link_click' => $click_count,
										)
									)
								);
									wp_set_auth_cookie( $user_id );

							} else {
								wp_logout();
								wp_safe_redirect( home_url( 'wp-login.php?loginpress_error=expired' ) );
								exit;
							}
						}
					}
				}
			}

			/**
			 * Filter to change the click counts.
			 */
			$args = apply_filters(
				'loginpress_autologin_link_count',
				array(
					'user_id'         => '', // Default: Update all users.
					'max_clicks'      => '', // Default: No limit.
					'unlimited_click' => 'unchecked', // Default: Not unlimited.
				)
			);
			if ( isset( $args['user_id'] ) && $args['max_clicks'] > 0 ) {
				self::loginpress_autologin_update_clicks( $args );
			}
		}
	}
}
