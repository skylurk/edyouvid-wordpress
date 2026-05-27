<?php
/**
 * LoginPress Login Redirects Utils Trait.
 *
 * Handles Some Utility functions from class-loginpress-login-redirects file.
 *
 * @package   LoginPress
 * @subpackage Traits\LoginRedirects
 * @since 6.1.0
 */

/**
 * Prevent direct access.
 *
 * @package LoginPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! trait_exists( 'LoginPress_Redirects_Utils_Trait' ) ) {
	/**
	 * LoginPress Login Redirects Utils Trait.
	 *
	 * Handles Some Utility functions from class-loginpress-login-redirects file.
	 *
	 * @package   LoginPress
	 * @subpackage Traits\LoginRedirects
	 * @since 6.1.0
	 */
	trait LoginPress_Redirects_Utils_Trait {

		/**
		 * Get the users list and Saved it in footer that will use for autocomplete in search.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_login_redirects_autocomplete_js() {

			/**
			 * Check to apply the script only on the LoginPress Settings page.
			 *
			 * @since 1.1.5
			 * @version 3.2.0
			 */
			$current_screen = get_current_screen();

			if ( isset( $current_screen->base ) && ( 'toplevel_page_loginpress-settings' !== $current_screen->base ) ) {
				return;
			}
			// Filter for to change minimum number of autocomplete characters.
			$min_length = apply_filters( 'loginpress_autocomplete_search_length', 3 );
			?>
			<script type="text/javascript">
				var redirect_roles;
				var redirect_users; 
				var _nonce = loginpress_pro_local.search_nonce;
				var min_length = <?php echo esc_js( $min_length ); ?>;
				jQuery(document).ready(function($) {
					
					var hintMessage = $(`<div class="hintMessage" style="color: #A00300; font-weight: 700; padding-top: 10px; display: none;">Enter at least ${min_length} characters</div>`);
					var noUsersMessage = $('<div class="noUsersMessage" style="color: #A00300; font-weight: 700; padding-top: 10px; display: none;">No users found</div>');
					$('#loginpress_redirect_user_search').after(hintMessage);
					$('#loginpress_redirect_user_search').after(noUsersMessage);

					$('#loginpress_redirect_user_search').on('input', function() {
						var searchTerm = $(this).val();
						if (searchTerm.length < min_length) {
							$('.hintMessage').show();
						} else {
							$('.hintMessage').hide();
						}
					});
					$(document).click(function(event) {
						if (!$(event.target).closest('#loginpress_redirect_user_search').length) {
							hintMessage.hide();
							noUsersMessage.hide();
						}
					});
					$( 'input[name="loginpress_redirect_user_search"]' ).autocomplete({
						source: function(request, response) {
							if (request.term.length < min_length) {
								response([]); 
								return;
							}
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'loginpress_search_users',
									search: request.term,
									security: _nonce,
								},
								success: function(data) {
									var final = data.map(entry => {
										entry.value = entry.username;
										return entry;
									});
									if (final.length === 0) {
										$('.noUsersMessage').show();
										setTimeout(function() {
											$('.noUsersMessage').hide();
										}, 2500);
										
									}
									response(final); 
								},
								error: function(jqXHR, textStatus, errorThrown) {
									console.error("Error:", textStatus, errorThrown);
								}
							});
						},
						minLength: min_length,
						select: function(event, ui) {
							var id = ui.item.id;
							var name  = ui.item.label;
							var email = ui.item.email;
							if ( $( '#loginpress_redirects_user_id_' + id ).length == 0 ) {
								$('#loginpress_login_redirect_users .dataTables_empty').hide();
								var get_html = $('<tr id="loginpress_redirects_user_id_' + id + '" data-login-redirects-user="' + id + '"><td class="dtfc-fixed-left sorting_1" style="left: 0px; position: sticky;"><div class="lp-tbody-cell">' + id + '</td><td class="loginpress_user_name"><div class="lp-tbody-cell">' + name + '</div></td><td ><div class="lp-tbody-cell">' + email + '</div></td><td class="loginpress_login_redirects_url" placeholder="<?php echo esc_attr( esc_html__( 'Enter URL', 'loginpress-pro' ) ); ?>"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_login_redirects_url" placeholder="<?php echo esc_attr( esc_html__( 'Enter URL', 'loginpress-pro' ) ); ?>"/><div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_logout_redirects_url" placeholder="<?php echo esc_attr( esc_html__( 'Enter URL', 'loginpress-pro' ) ); ?>"/></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><button type="button" class="button loginpress-user-redirects-update" ><?php echo esc_html__( 'Update', 'loginpress-pro' ); ?></button> <button type="button" class="button loginpress-user-redirects-delete"></button></div></td></tr>');

								if ( $( '#loginpress_redirects_user_id_' + id ).length == 0 ) {
									redirect_users.row.add(get_html[0]).draw();
									$( '#loginpress_redirects_user_id_'+id ).find('td:first-child').addClass('dtfc-fixed-left');
									$( '#loginpress_redirects_user_id_'+id ).find('td:last-child').addClass('dtfc-fixed-right');

								}

							} else {
								$( '#loginpress_redirects_user_id_'+id ).addClass('loginpress_user_highlighted');
								setTimeout(function(){
									$( '#loginpress_redirects_user_id_'+id ).removeClass('loginpress_user_highlighted');
								}, 3000 );
							}
						} // !select.
					});
				});
			</script>

			<?php

			global $wp_roles;

			$all_roles = $wp_roles->roles;
			foreach ( $all_roles as $k => $value ) {

				$role[ $k ]['role']  = esc_attr( $k );
				$role[ $k ]['label'] = translate_user_role( $value['name'] ); // returns localized name. v1.1.2.
			}
			?>
			<script type="text/javascript">
				jQuery(document).ready( function($) {

					var posts = <?php echo wp_json_encode( array_values( $role ) ); ?>;

					if ( jQuery( 'input[name="loginpress_redirect_role_search"]' ).length > 0 ) {
						jQuery( 'input[name="loginpress_redirect_role_search"]' ).autocomplete({

							source: posts,
							minLength: 1,
							select: function( event, ui ) {

								var name = ui.item.label;
								var role = ui.item.role;
								if ( $( '#loginpress_redirects_role_' + role ).length == 0 ) {

									$('#loginpress_login_redirect_roles .dataTables_empty').hide();
									var get_html = $('<tr id="loginpress_redirects_role_' + role + '" data-login-redirects-role="' + role + '"><td class="dtfc-fixed-left sorting_1" style="left: 0px; position: sticky;"><div class="lp-tbody-cell no-of"></div></td><td class="loginpress_user_name"><div class="lp-tbody-cell">' + name + '</div></td><td class="loginpress_login_redirects_url" placeholder="<?php echo esc_attr( esc_html__( 'Enter URL', 'loginpress-pro' ) ); ?>"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_login_redirects_url" placeholder="<?php echo esc_attr( esc_html__( 'Enter URL', 'loginpress-pro' ) ); ?>"/></div></td><td class="loginpress_logout_redirects_url"><div class="lp-tbody-cell"><span class="login-redirects-sniper"><img src="<?php echo esc_attr( esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ) ); ?>" /></span><input type="text" value="" id="loginpress_logout_redirects_url" placeholder="<?php echo esc_attr( esc_html__( 'Enter URL', 'loginpress-pro' ) ); ?>"/></div></td><td class="loginpress_login_redirects_actions"><div class="lp-tbody-cell"><input type="button" class="button loginpress-redirects-role-update" value="<?php echo esc_html__( 'Update', 'loginpress-pro' ); ?>" /> <input type="button" class="button loginpress-redirects-role-delete" value="<?php echo esc_html__( 'Delete', 'loginpress-pro' ); ?>" /></div></td></tr>');
									if ( $('#loginpress_redirects_role_' + role ).length == 0 ) {
										redirect_roles.row.add(get_html[0]).draw();
										$( '#loginpress_redirects_role_'+role ).find('td:first-child').addClass('dtfc-fixed-left');
										$( '#loginpress_redirects_role_'+role ).find('td:last-child').addClass('dtfc-fixed-right');
									}

								} else {
									$( '#loginpress_redirects_role_' + role ).addClass( 'loginpress_user_highlighted' );
									setTimeout(function(){
										$( '#loginpress_redirects_role_' + role ).removeClass( 'loginpress_user_highlighted' );
									}, 3000 );
								}
							} // !select.
						});
					}
				});
			</script>
			<?php
		}
	}
}
