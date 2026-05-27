<?php
/**
 * LoginPress Learndash Redirects Trait.
 *
 * Contains all the methods related to Login Redirects.
 *
 * @package   LoginPress-Pro
 * @subpackage Traits\Loginpress-Integration
 * @since 6.1.0
 */

/**
 * LoginPress Learndash Redirects Trait file.
 *
 * Prevent direct access.
 *
 * @package LoginPress-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'LoginPress_Learndash_Redirects_Trait' ) ) {
	/**
	 * LoginPress Learndash Redirects Trait.
	 *
	 * Contains all the methods related to Login Redirects.
	 *
	 * @package   LoginPress-Pro
	 * @subpackage Traits\Loginpress-Integration
	 * @since 6.1.0
	 */
	trait LoginPress_Learndash_Redirects_Trait {

		/**
		 * Concatenate HTML for LearnDash group tab in the Login Redirects setting page.
		 *
		 * @param string $html The HTML to be concatenated.
		 * @return string The concatenated HTML.
		 * @since 5.0.0
		 */
		public function html_concatenation_with_learndash_callback( $html ) {
			// translators: Learndash tab in login redirect.
			$html .= sprintf( __( ' %1$sLearnDash Groups%2$s ', 'loginpress-pro' ), '<a href="#loginpress_login_redirect_learndash_groups" class="loginpress-redirects-tab">', '</a>' );
			return $html;
		}

		/**
		 * Login redirects callback.
		 *
		 * @param string $html The HTML to be modified.
		 * @return string The modified HTML.
		 * @since 5.0.0
		 */
		public function loginpress_login_redirects_callback( $html ) {
			$html .= sprintf( '<input type="%1$s" name="%2$s" id="%2$s" value="" placeholder="%3$s" %4$s', 'text', 'loginpress_redirect_learndash_group_search', __( 'Type Group', 'loginpress-pro' ), '/>' );
			return $html;
		}

		/**
		 * A callback function that will show the LearnDash groups table HTML.
		 *
		 * @param string $html The HTML which will be shown on the page.
		 * @return string The modified HTML.
		 * @since 5.0.0
		 */
		public function loginpress_learndash_groups_html_callback( $html ) {

			$html .= '<table id="loginpress_login_redirect_learndash_groups" class="loginpress_login_redirect_learndash_groups">
            <thead><tr>
                <th class="loginpress_log_userName">' . esc_html__( 'Group', 'loginpress-pro' ) . '</th>
                <th class="loginpress_login_redirect">' . esc_html__( 'Login URL', 'loginpress-pro' ) . '</th>
                <th class="loginpress_logout_redirect">' . esc_html__( 'Logout URL', 'loginpress-pro' ) . '</th>
                <th class="loginpress_action">' . esc_html__( 'Action', 'loginpress-pro' ) . '</th>
            </tr></thead>';

			$loginpress_redirect_group = get_option( 'loginpress_redirects_group' );

			if ( ! empty( $loginpress_redirect_group ) ) {

				foreach ( $loginpress_redirect_group as $group => $value ) {

					$html .= '<tr id="loginpress_redirects_group_' . $group . '" data-login-redirects-group="' . $group . '"><td class="loginpress_user_name"><div class="lp-tbody-cell group-name-value">' . $value['name'] . '</div></td><td class="loginpress_login_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr( $value['login'] ) . '" id="loginpress_login_redirects_url"/></div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="' . esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) . '" /></span><input type="text" value="' . esc_attr( $value['logout'] ) . '" id="loginpress_logout_redirects_url"/></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><button type="button" class="button loginpress-redirects-group-update" value="' . esc_html__( 'Update', 'loginpress-pro' ) . '" ></button> <button type="button" class="button loginpress-redirects-group-delete"  value="' . esc_html__( 'Delete', 'loginpress-pro' ) . '" ></button></div></td><input type="hidden" placeholder="10" value="' . esc_attr( $value['priority'] ) . '" id="loginpress_group_order"/></tr>';
				}
			}

			$html .= '</table>';

			return $html;
		}

		/**
		 * Get the users list and save it in footer that will use for autocomplete in search.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_learndash_autocomplete_js() {

			/**
			 * Check to apply the script only on the LoginPress Settings page.
			 *
			 * @since 5.0.0
			 */
			$current_screen = get_current_screen();

			if ( isset( $current_screen->base ) && ( 'toplevel_page_loginpress-settings' !== $current_screen->base ) ) {
				return;
			}

			$groups     = get_posts(
				array(
					'post_type'   => 'groups',
					'post_status' => 'publish',
					'numberposts' => -1,
				)
			);
			$group_data = array();
			foreach ( $groups as $group => $value ) {
				if ( is_object( $value ) ) {
					$group_data[ $group ]['label'] = esc_html( $value->post_title );
					$group_data[ $group ]['value'] = esc_attr( $value->post_name );
				}
			}

			?>
			<script type="text/javascript">
				var redirect_group;
				jQuery(document).ready( function($) {

					var groups = <?php echo wp_json_encode( array_values( $group_data ) ); ?>;
					if ( jQuery( 'input[name="loginpress_redirect_learndash_group_search"]' ).length > 0 ) {
						jQuery( 'input[name="loginpress_redirect_learndash_group_search"]' ).autocomplete({
							
							source: groups,
							minLength: 1,
							select: function( event, ui ) {
								
								var name = ui.item.label;
								var value = ui.item.value;
								if ( $( '#loginpress_redirects_group_' + value ).length == 0 ) {
									$('#loginpress_login_redirect_learndash_groups .dataTables_empty').hide();
									var get_html = $('<tr id="loginpress_redirects_group_' + value + '" data-login-redirects-group="' + value + '"><td class="loginpress_user_name"><div class="lp-tbody-cell group-name-value">' + name + '</div></td><td class="loginpress_login_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_login_redirects_url"/></div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_logout_redirects_url"/></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><input type="button" class="button loginpress-redirects-group-update" value="<?php echo esc_html__( 'Update', 'loginpress-pro' ); ?>" /> <input type="button" class="button loginpress-redirects-group-delete" value="<?php echo esc_html__( 'Delete', 'loginpress-pro' ); ?>" /></div></td><input type="number" style="display:none" placeholder="10" value="" id="loginpress_group_order"/></tr>');

									if ( $('#loginpress_redirects_group_' + value ).length == 0 ) {
										redirect_group.row.add(get_html[0]).draw();
										$( '#loginpress_redirects_group_'+value ).find('td:first-child').addClass('dtfc-fixed-left');
										$( '#loginpress_redirects_group_'+value ).find('td:last-child').addClass('dtfc-fixed-right');
									}
								} else {
									$( '#loginpress_redirects_group_' + value ).addClass( 'loginpress_user_highlighted' );
									setTimeout(function(){
										$( '#loginpress_redirects_group_' + value ).removeClass( 'loginpress_user_highlighted' );
									}, 3000 );
								}
							}
						});	
					}
				});
			</script>
			<?php
		}

		/**
		 * LoginPress redirects LearnDash group.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_login_redirects_update_group() {

			check_ajax_referer( 'loginpress-group-redirects-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'No cheating, huh!', 'loginpress-pro' ) );
			}
			if ( isset( $_POST['logout'] ) && isset( $_POST['login'] ) && isset( $_POST['group'] ) && isset( $_POST['priority'] ) && isset( $_POST['value'] ) ) {
				$loginpress_logout = esc_url_raw( wp_unslash( $_POST['logout'] ) );
				$value             = sanitize_text_field( wp_unslash( $_POST['value'] ) );
				$loginpress_login  = esc_url_raw( wp_unslash( $_POST['login'] ) );
				$group             = sanitize_text_field( wp_unslash( $_POST['group'] ) );
				$priority          = intval( $_POST['priority'] );
				if ( empty( $priority ) || $priority <= 0 ) {
					$priority = 10;
				}
				$check_group = get_option( 'loginpress_redirects_group' );
				$add_group   = array(
					$value => array(
						'login'    => $loginpress_login,
						'logout'   => $loginpress_logout,
						'priority' => $priority,
						'name'     => $group,
					),
				);

				if ( $check_group && ! in_array( $group, $check_group, true ) ) {
					$redirect_groups = array_merge( $check_group, $add_group );
				} else {
					$redirect_groups = $add_group;
				}

				update_option( 'loginpress_redirects_group', $redirect_groups, true );
			}
			wp_die();
		}

		/**
		 * Handles AJAX request to delete a LearnDash group redirect setting.
		 *
		 * Verifies security nonce, checks user capabilities, and removes the specified group
		 * from the 'loginpress_redirects_group' option in the database.
		 *
		 * @since 5.0.0
		 *
		 * @return void
		 */
		public function loginpress_login_redirects_delete_group() {

			check_ajax_referer( 'loginpress-group-redirects-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( isset( $_POST['group'] ) ) {
				$group       = sanitize_text_field( wp_unslash( $_POST['group'] ) );
				$check_group = get_option( 'loginpress_redirects_group' );

				if ( isset( $check_group[ $group ] ) ) {

					$check_group[ $group ] = null;
					$check_group           = array_filter( $check_group );

					update_option( 'loginpress_redirects_group', $check_group, true );
				}
			}
			wp_die();
		}

		/**
		 * Sort by priority.
		 *
		 * @param array $group_a First group for comparison.
		 * @param array $group_b Second group for comparison.
		 * @return int The comparison result.
		 * @since 5.0.0
		 */
		public function loginpress_sort_by_priority( $group_a, $group_b ) {
			if ( $group_a['priority'] === $group_b['priority'] ) {
				return 1;
			} else {
				return $group_b['priority'] <=> $group_a['priority'];
			}
		}

		/**
		 * This updates the 'loginpress_redirects_group' option with the modified value.
		 *
		 * @return void
		 * @since 5.0.0
		 */
		public function loginpress_group_priority_callback() {
			$check_group = get_option( 'loginpress_redirects_group' );

			/**
			 * Filter the group redirects data before saving it to the database.
			 *
			 * Allows developers to modify the 'loginpress_redirects_group' data
			 * before it's persisted to the options table.
			 *
			 * @since 5.0.0
			 *
			 * @param array $check_group Array of redirect groups and their data.
			 */
			$modified_group = apply_filters( 'loginpress_redirects_group_filter', $check_group );
			update_option( 'loginpress_redirects_group', $modified_group );
		}

		/**
		 * This function determines if WordPress's local URL limitation should be bypassed.
		 *
		 * @param string $redirect_to Where to redirect.
		 * @param string $requested_redirect_to Requested redirect.
		 * @param object $user User object.
		 * @return string The redirect URL.
		 * @since 6.0.0
		 */
		public function loginpress_redirects_after_login_ld( $redirect_to, $requested_redirect_to, $user ) {

			$loginpress_redirects = new LoginPress_Set_Login_Redirect();
			if ( isset( $user->ID ) ) {
				$user_redirects_url = $loginpress_redirects->loginpress_redirect_url( $user->ID, 'loginpress_login_redirects_url' );
				$role_redirects_url = LoginPress_Set_Login_Redirect::loginpress_role_redirect( $user );
				$group_redirect_url = get_option( 'loginpress_redirects_group' );

				if ( isset( $user->roles ) && is_array( $user->roles ) ) {

					if ( empty( $user_redirects_url ) && false === $role_redirects_url && ! empty( $group_redirect_url ) ) {

						$groups = get_posts(
							array(
								'post_type'   => 'groups',
								'post_status' => 'publish',
								'numberposts' => -1,
							)
						);

						$group_data = array();
						foreach ( $groups as $group ) {
							$group_name     = $group->post_name;
							$group_id       = $group->ID;
							$user_in_groups = learndash_is_user_in_group( $user->ID, $group_id );
							if ( isset( $group_redirect_url[ $group_name ] ) && $user_in_groups ) {
								$group_data[ $group_name ]['login']    = isset( $group_redirect_url[ $group_name ]['login'] ) && ! empty( $group_redirect_url[ $group_name ]['login'] ) ? $group_redirect_url[ $group_name ]['login'] : $redirect_to;
								$group_data[ $group_name ]['priority'] = $group_redirect_url[ $group_name ]['priority'];
							}
						}
						if ( empty( $group_data ) ) {
							return $redirect_to;
						}
						usort( $group_data, array( $this, 'loginpress_sort_by_priority' ) );
						$highest_priority_group = reset( $group_data );
						$login_url              = $highest_priority_group['login'];

						if ( $loginpress_redirects->is_inner_link( $login_url ) ) {
							return $login_url;
						}

						$loginpress_redirects->loginpress_safe_redirects( $user->ID, $user->name, $user, $login_url );
					}
				} else {
					return $redirect_to;
				}
			}
			return $redirect_to;
		}

		/**
		 * Handles user logout redirects based on priority: user-specific, role-based, or LearnDash group-based settings.
		 *
		 * @return void
		 * @since 6.0.0
		 */
		public function loginpress_redirects_after_logout() {

			$user_id              = get_current_user_id();
			$user                 = wp_get_current_user();
			$loginpress_redirects = new LoginPress_Set_Login_Redirect();
			$user_redirects_url   = $loginpress_redirects->loginpress_redirect_url( $user_id, 'loginpress_login_redirects_url' );
			$role_redirects_url   = LoginPress_Set_Login_Redirect::loginpress_role_redirect( $user );
			$group_redirect_url   = get_option( 'loginpress_redirects_group' );

			if ( 0 !== $user_id || empty( $user_redirects_url ) || false === $role_redirects_url || ! empty( $group_redirect_url ) ) {
				return;
			}

				$groups     = get_posts(
					array(
						'post_type'   => 'groups',
						'post_status' => 'publish',
						'numberposts' => -1,
						'fields'      => 'ids,post_name',
					)
				);
				$group_data = array();

			foreach ( $groups as $group ) {
				$group_name     = $group->post_name;
				$group_id       = $group->ID;
				$user_in_groups = learndash_is_user_in_group( $user_id, $group_id );
				if ( isset( $group_redirect_url[ $group_name ] ) && $user_in_groups ) {
					$group_data[ $group_name ]['logout']   = isset( $group_redirect_url[ $group_name ]['logout'] ) && ! empty( $group_redirect_url[ $group_name ]['logout'] ) ? $group_redirect_url[ $group_name ]['logout'] : '/';
					$group_data[ $group_name ]['priority'] = $group_redirect_url[ $group_name ]['priority'];
				}
			}
			if ( empty( $group_data ) ) {
				wp_safe_redirect( '/' );
				exit;
			}
				usort( $group_data, array( $this, 'loginpress_sort_by_priority' ) );
				$highest_priority_group = reset( $group_data );
				$logout_url             = $highest_priority_group['logout'];
				wp_safe_redirect( $logout_url );
				exit;
		}
	}
}
