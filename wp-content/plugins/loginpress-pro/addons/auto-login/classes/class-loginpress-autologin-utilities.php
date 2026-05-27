<?php
/**
 * LoginPress AutoLogin Utilities Class.
 *
 * Contains utility functions for auto-login features.
 *
 * @package LoginPress
 * @category Core
 * @author WPBrigade
 * @since 6.1.0
 */

if ( ! class_exists( 'LoginPress_AutoLogin_Utilities' ) ) :

	/**
	 * LoginPress AutoLogin Utilities Class.
	 *
	 * @since 6.1.0
	 * @version 6.1.0
	 */
	class LoginPress_AutoLogin_Utilities {

		/**
		 * Allowed HTML tags for wp_kses_post with additional security.
		 *
		 * @var array
		 * @since 6.1.0
		 * @version 6.1.0
		 */
		private static $allowed_html = array(
			'a'      => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
				'rel'    => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'i'      => array(),
			'u'      => array(),
			'b'      => array(),
			'p'      => array(),
			'div'    => array(
				'class' => array(),
				'id'    => array(),
			),
			'span'   => array(
				'class' => array(),
				'id'    => array(),
			),
			'img'    => array(
				'src'    => array(),
				'alt'    => array(),
				'width'  => array(),
				'height' => array(),
				'class'  => array(),
			),
		);

		/**
		 * Sanitize HTML content using wp_kses_post with enhanced security.
		 *
		 * @param string $content The content to sanitize.
		 * @since 6.1.0
		 * @version 6.1.0
		 * @return string Sanitized HTML content.
		 */
		public static function sanitize_html( $content ) {
			if ( empty( $content ) ) {
				return '';
			}
			// First use wp_kses_post for basic sanitization.
			$sanitized = wp_kses_post( $content );
			// Apply additional security with our custom allowed HTML.
			$sanitized = wp_kses( $sanitized, self::$allowed_html );
			return $sanitized;
		}

		/**
		 * Get the allowed HTML array for use in other functions.
		 *
		 * @return array Allowed HTML tags and attributes.
		 * @since 6.0.0
		 * @version 6.1.0
		 */
		public static function get_allowed_html() {
			return self::$allowed_html;
		}

		/**
		 * Get the users list and Saved it in footer that will use for autocomplete in search.
		 *
		 * @since 1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_autologin_autocomplete_js() {
			/**
			 * Check to apply the script only on the LoginPress Settings page.
			 *
			 * @since 1.0.9
			 * @version 3.2.0
			 */
			$current_screen = get_current_screen();
			if ( isset( $current_screen->base ) && ( 'toplevel_page_loginpress-settings' !== $current_screen->base ) ) {
				return;
			}
			// filter for to change minimun number of autocomplete characters.
			$min_length = apply_filters( 'loginpress_autocomplete_search_length', 3 );
			?>
			<script type="text/javascript">

				var autologin_table;
				var _nonce = loginpress_pro_local.search_nonce;
				var min_length = <?php echo esc_js( $min_length ); ?>;
				jQuery(document).ready(function($) {
					
					function loginpress_create_new_link() {
						var autoLoginString = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

						var result = "";
						while ( result.length < 30 ) {
							result += autoLoginString.charAt( Math.floor( Math.random() * autoLoginString.length ) );
						}

						return result;
					}
					function loginpress_expiration() {
						<?php
						$date           = gmdate( 'Y-m-d' );
						$default_expire = gmdate( 'Y-m-d', strtotime( "$date +7 day" ) ); // PHP Date style:  yy-mm-dd.
						$expire         = apply_filters( 'loginpress_autologin_default_expiration', $default_expire );
						$now            = time(); // or your date as well.
						$your_date      = strtotime( $expire );
						$date_diff      = $your_date - $now;
						$remaining_days = round( $date_diff / ( 60 * 60 * 24 ) );
						?>
						var result      = <?php echo intval( $remaining_days ); ?>;
						return result;
					}

					var hintMessage = $(`<div id="hintMessage" style="color: #FF0000; padding-top: 10px; display: none;">Enter at least ${min_length} characters</div>`);
					var noUsersMessage = $('<div id="noUsersMessage" style="color: #FF0000; padding-top: 10px; display: none;">No users found</div>');

					$('#loginpress_autologin_search').after(hintMessage);
					$('#loginpress_autologin_search').after(noUsersMessage);

					$('#loginpress_autologin_search').on('input', function() {
						var searchTerm = $(this).val();
						if (searchTerm.length < min_length) {
							$('#hintMessage').show();
						} else {
							$('#hintMessage').hide();
						}
					});
					// Hide hint message on click outside the input field.
					$(document).click(function(event) {
						if (!$(event.target).closest('#loginpress_autologin_search').length) {
							hintMessage.hide();
							noUsersMessage.hide();
						}
					});
					$( 'input[name="loginpress_autologin_search"]' ).autocomplete({
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
									search_for: 'loginpress_autologin',
								},
								success: function(data) {
									var final = data.map(entry => {
										entry.value = entry.username;
										return entry;
									});
									if (final.length === 0) {
										$('#noUsersMessage').show();
										setTimeout(function() {
											$('#noUsersMessage').hide();
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
							var code = loginpress_create_new_link();
							var expiration = loginpress_expiration();
							if ( $( '#loginpress_user_id_' + id ).length == 0 ) {
								$.ajax({
									url: ajaxurl,
									type: 'POST',
									data: 'code=' + code + '&id=' + id + '&action=loginpress_autologin' + '&security=' + _nonce,
									success: function( response ) {
										$('#loginpress_autologin_users .dataTables_empty').hide();
										var get_html = $('<tr id="loginpress_user_id_'+id+'" data-autologin="'+id+'"><td><div class="lp-tbody-cell">'+id+'</div></td><td class="loginpress_user_name"><div class="lp-tbody-cell">'+ui.item.label+'</div></td><td><div class="lp-tbody-cell">'+ui.item.email+'</div></td><td class="loginpress_autologin_code"><div class="lp-tbody-cell"><span class="autologin-sniper"><img src="<?php echo esc_url( LOGINPRESS_DIR_URL . 'img/loginpress-sniper.gif' ); ?>" /></span> <input type="text" class="loginpress-autologin-code" dir="rtl" value="' + response + '"><div class="copy-email-icon-wrapper autologin-copy-code"><svg class="autologin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_27_294)"><path d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z" fill="#869AC1"/><path d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z" fill="#869AC1"/></g><defs><clipPath id="clip0_27_294"><rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)"/></clipPath></defs></svg></div><span class="loginpress-autologin-email-sent">Email Sent</span><span class="loginpress-autologin-create-notice" data-attr="data-dayleft"> '+ expiration +'<?php esc_html_e( ' Days Left', 'loginpress-pro' ); ?> </span></div></td><td class="loginpress_user_status" data-attr="data-dayleft"><div class="lp-tbody-cell"><span class="loginpress-autologin-remain-notice"> '+ expiration +'<?php esc_html_e( ' Days Left', 'loginpress-pro' ); ?></span></br> <span class="loginpress-clicks-remain-notice"><?php esc_html_e( 'Unlimited Clicks', 'loginpress-pro' ); ?></span></div></td><td class="loginpress_autologin_actions"><div class="lp-tbody-cell"><button type="button" class="button loginpress-del-link" value=" <?php esc_html_e( 'Delete', 'loginpress-pro' ); ?>" id="loginpress_delete_link" /></button><div class="loginpress-action-list-menu-wrapper"><div class="loginpress-action-menu-burger-wrapper"><span class="loginpress-action-menu-burger-open-icon dashicons dashicons-menu-alt2"></span><span class="loginpress-action-menu-burger-close-icon dashicons dashicons-no-alt"></span></div><ul class="action-menu-list"><li><input type="button" class="button loginpress-new-link" value="<?php esc_html_e( 'New Link', 'loginpress-pro' ); ?>" id="loginpress_create_new_link"/></li><li><input type="button" class="button loginpress-autologin-duration" value="<?php esc_html_e( 'Link Duration', 'loginpress-pro' ); ?>" /> </li><li><input type="button" class="button loginpress-autologin-link-count" value="<?php esc_html_e( 'Link Count', 'loginpress-pro' ); ?>" /> </li><li><input type="button" class="button loginpress-autologin-state disable" data-state="disable" value="disable" /> </li><li><input type="button" value=" <?php esc_html_e( 'Email To Multiple', 'loginpress-pro' ); ?>"class="loginpress-autologin-email-settings"> <li><input type="button" class="button loginpress-autologin-email" value=" <?php esc_html_e( 'Email User', 'loginpress-pro' ); ?>"></li></span></ul> </div> <div></td></tr>');

										if ( $('#loginpress_user_id_' + id + '').length == 0 ) {

											autologin_table.row.add(get_html[0]).draw();
											let uid = parseInt(id);
											$('#loginpress_user_id_' + id + '').find('td:first-child').addClass('dtfc-fixed-left');
											$('#loginpress_user_id_' + id + '').find('td:last-child').addClass('dtfc-fixed-right');
											$(document).on('click','.loginpress-action-menu-burger-wrapper', function ( uid ) {
												uid.stopPropagation();
												$('#loginpress_autologin_users').attr('data-open','parent-'+ $(this).parent().toggleClass("menu-active").closest('tr').nextAll().length);
												$(this).parent().toggleClass("menu-active").closest('tr').siblings().find('.loginpress-action-list-menu-wrapper').removeClass('menu-active');
												$(this).closest('.loginpress_autologin_actions ').toggleClass("sticky-active").closest('tr').siblings().find('.loginpress_autologin_actions ').removeClass('sticky-active');
												// 
												
												$(this).closest('tr').addClass("list-active").siblings().removeClass('list-active');
											});
										}
									}  // !success.
								}); // !ajax.
							} else {
								$( '.toplevel_page_loginpress-settings .dataTable tbody #loginpress_user_id_' + id ).addClass('loginpress_user_highlighted');
								setTimeout( function() {
									$( '#loginpress_user_id_' + id ).removeClass('loginpress_user_highlighted');
								}, 3000 );
							}
						} // !select.
					});
				});
			</script>
			
			
			<?php
		}
	}

endif;
