(function ($) {
	'use strict';

	$(
		function () {

			$( '.loginpress-login-widget' ).each(
				function () {

					$( '.loginpress_widget_error' ).remove();
					var widget           = $( this ); // Scope to the specific widget
					var loginForm        = widget.find( '#loginform' );
					var registerForm     = widget.find( '#registerform' );
					var lostPasswordForm = widget.find( '#lostpasswordform' );

					// Hide register and lost password forms initially
					registerForm.hide();
					lostPasswordForm.hide();
					widget.find( '.loginpress_widget_error' ).remove();

					jQuery( document ).ready(
						function () {
							const urlParams = new URLSearchParams( window.location.search );
							jQuery( '.login-link' ).trigger( 'click' );
							if (urlParams.get( 'openLostPassword' ) === '1') {
								jQuery( '.lost_password-link' ).trigger( 'click' ); // Open lost password form
							}
							if (urlParams.get( 'registerform' ) === '1') {
								jQuery( '.register-link' ).trigger( 'click' ); // Open lost password form
							}

						}
					);

					widget.find( '.register-link' ).on(
						'click',
						function (e) {
							e.preventDefault();
							loginForm.hide();              // Hide the login form
							registerForm.show();           // Show the registration form
							widget.find( '.widget-title' ).text( 'Register' ); // Change the title to 'Register'
							widget.find( '.login-link' ).show();  // Show the login link
							widget.find( '.register-link' ).hide(); // Hide the register link
							lostPasswordForm.hide();       // Hide the lost password form
							widget.find( '.lost_password-link' ).show(); // Show the lost password link

						}
					);

					widget.find( '.login-link' ).on(
						'click',
						function (e) {
							e.preventDefault();
							registerForm.hide();           // Hide the registration form
							loginForm.show();              // Show the login form
							widget.find( '.widget-title' ).text( 'Login' ); // Change the title to 'Login'
							widget.find( '.register-link' ).show(); // Show the register link
							widget.find( '.login-link' ).hide();   // Hide the login link
							lostPasswordForm.hide();       // Hide the lost password form
							widget.find( '.lost_password-link' ).show(); // Show the lost password link
						}
					);

					widget.find( '.lost_password-link' ).on(
						'click',
						function (e) {
							e.preventDefault();
							loginForm.hide();              // Hide the login form
							registerForm.hide();           // Hide the registration form
							lostPasswordForm.show();       // Show the lost password form
							widget.find( '.widget-title' ).text( 'Lost Password' ); // Change the title to 'Lost Password'
							widget.find( '.login-link' ).show();  // Show the login link
							widget.find( '.lost_password-link' ).hide(); // Hide the lost password link
							widget.find( '.register-link' ).show(); // Show the register link
						}
					);

					lostPasswordForm.on(
						'submit',
						function (e) {
							$( '.loginpress_widget_success' ).remove();
							e.preventDefault(); // Prevent default form submission
							var el         = $( this );
							var user_login = el.find( 'input[name="user_login"]' ).val(); // Get username or email
							// Get the Captcha response
							var Captcha_Response  = '';
							var isGoogleRecaptcha = false;
							if (el.find( 'input[name="cf-turnstile-response"]' ).length > 0) {
								Captcha_Response = el.find( 'input[name="cf-turnstile-response"]' ).val();
							} else if ($( 'body' ).find( 'textarea[name="h-captcha-response"]' ).length > 0) {
								Captcha_Response = $( 'body' ).find( 'textarea[name="h-captcha-response"]' ).val();
								if ( Captcha_Response == '' ) {
									Captcha_Response = el.find( 'textarea[name="h-captcha-response"]' ).val();
								}
							} else if ($( 'body' ).find( 'textarea[name="g-recaptcha-response"]' ).length > 0) {
								Captcha_Response = $( 'body' ).find( 'textarea[name="g-recaptcha-response"]' ).val();
								if ( Captcha_Response == '' ) {
									Captcha_Response = el.find( 'textarea[name="g-recaptcha-response"]' ).val();
								}
								isGoogleRecaptcha = true; // Mark that Google reCAPTCHA is in use
							}
							// Clear previous errors
							$( '.loginpress_widget_error' ).remove();

							// Basic validation
							if ( ! user_login) {
								el.prepend( '<p class="loginpress_widget_error">' + 'Error: The Username or Email field is empty' + '</p>' );
								return false;
							}

							$.ajax(
								{
									url: loginpress_widget_params.ajaxurl, // WordPress AJAX handler
									data: {
										action: 'loginpress_widget_lost_password_process', // Custom AJAX action for lost password
										user_login: user_login,
										nonce: loginpress_widget_params.lp_widget_nonce,
										captcha_response: Captcha_Response
									},
									type: 'POST',
									success: function (response) {
										if (response.success == 1) {
											// Password reset link sent successfully
											el.prepend( '<p class="loginpress_widget_success">' + response.data.message + '</p>' );
											$( '#lostpasswordform' )
											.find( 'input:not([type="submit"])' )
											.val( '' );
										} else {
											if (response.error) {
												// Error show without captcha verification
												el.prepend( '<p class="loginpress_widget_error">' + response.error + '</p>' );
											} else {
												// Failed to send the reset link, display the error message
												el.prepend( '<p class="loginpress_widget_error">' + response.data.message + '</p>' );
											}
											if (isGoogleRecaptcha) {
												grecaptcha.reset();
												setTimeout(
													function () {

														window.location.href = window.location.href.split( '?' )[0] + '?openLostPassword=1';
													},
													2000
												);
											}
											if (typeof hcaptcha !== 'undefined' && hcaptcha) {
												hcaptcha.reset();
											}
										}

									},
									error: function () {
										// Handle unknown errors
										el.prepend( '<p class="loginpress_widget_error">An unknown error occurred. Please try again.</p>' );
										// Reset Google reCAPTCHA so it can be used again
										if (isGoogleRecaptcha) {
											grecaptcha.reset();
											setTimeout(
												function () {
													window.location.href = window.location.href.split( '?' )[0] + '?openLostPassword=1';
												},
												2000
											);
										}
									}
								}
							);
						}
					);

					registerForm.on(
						'submit',
						function (e) {
							$( '.loginpress_widget_success' ).remove();
							e.preventDefault(); // Prevent default form submission
							var el                = $( this );
							var username          = el.find( 'input[name="user_login"]' ).val();
							var email             = el.find( 'input[name="user_email"]' ).val();
							var pass              = el?.find( 'input[name="user_pass"]' )?.val();
							var user_confirm_pass = el.find( 'input[name="user_confirm_pass"]' ).val();

							// Clear previous errors
							$( '.loginpress_widget_error' ).remove();

							// Basic validation (similar to login form)
							if ( ! username) {
								el.prepend( '<p class="loginpress_widget_error">' + loginpress_widget_params.empty_username + '</p>' );
								return false;
							}
							if ( ! email) {
								el.prepend( '<p class="loginpress_widget_error">' + 'Error: The Email field is empty' + '</p>' );
								return false;
							}
							if ( loginpress_widget_params.enable_pass_strength !== 'off' && loginpress_widget_params.enable_on_form !== 'off' && loginpress_widget_params.enable_reg_pass_field !== 'off' ) {

								if ( loginpress_widget_params.min_length > 0 && el.find( 'input[name="user_pass"]' ).val().length < loginpress_widget_params.min_length ) {
									el.prepend( '<p class="loginpress_widget_error">' + 'Error: Password must be at least ' + loginpress_widget_params.min_length + ' characters long' + '</p>' );
									return false;
								}

								if ( loginpress_widget_params.require_upper_lower !== 'off' && ( ! /[A-Z]/.test( pass ) || ! /[a-z]/.test( pass )) ) {
									el.prepend( '<p class="loginpress_widget_error">' + 'Error: Password must contain at least one uppercase and one lowercase letter' + '</p>' );
									return false;
								}

								if ( loginpress_widget_params.require_special_char !== 'off' && ! /[!@#$%^&*]/.test( pass ) ) {
									el.prepend( '<p class="loginpress_widget_error">' + 'Error: Password must contain at least one special character' + '</p>' );
									return false;
								}

								if ( loginpress_widget_params.integer_no_must !== 'off' && ! /[0-9]/.test( pass ) ) {
									el.prepend( '<p class="loginpress_widget_error">' + 'Error: Password must contain at least one number' + '</p>' );
									return false;
								}
							}

							// Password and confirm password validation if fields are present
							if (el.find( 'input[name="user_pass"]' ).length > 0 && el.find( 'input[name="user_confirm_pass"]' ).length > 0) {
								if ( ! pass) {
									el.prepend( '<p class="loginpress_widget_error">' + 'Error: The Password field is empty' + '</p>' );
									return false;
								}
								if ( ! user_confirm_pass) {
									el.prepend( '<p class="loginpress_widget_error">' + 'Error: The Confirm Password field is empty' + '</p>' );
									return false;
								}
								if (pass !== user_confirm_pass) {
									el.prepend( '<p class="loginpress_widget_error">' + 'Error: Passwords do not match' + '</p>' );
									return false;
								}
							}

							// Get the Captcha response
							var Captcha_Response  = '';
							var isGoogleRecaptcha = false;
							if (el.find( 'input[name="cf-turnstile-response"]' ).length > 0) {
								Captcha_Response = el.find( 'input[name="cf-turnstile-response"]' ).val();
							} else if ($( 'body' ).find( 'textarea[name="h-captcha-response"]' ).length > 0) {
								Captcha_Response = $( '#registerform' ).find( 'textarea[name="h-captcha-response"]' ).val();
								if ( Captcha_Response == '' ) {
									Captcha_Response = $( 'body' ).find( 'textarea[name="h-captcha-response"]' ).val();
								}
							} else if ($( 'body' ).find( 'textarea[name="g-recaptcha-response"]' ).length > 0) {
								Captcha_Response = $( 'body' ).find( 'textarea[name="g-recaptcha-response"]' ).val();
								if ( Captcha_Response == '' ) {
									Captcha_Response = el.find( 'textarea[name="g-recaptcha-response"]' ).val();
								}
								isGoogleRecaptcha = true;
							}

							// Prepare AJAX data
							var ajaxData = {
								action: 'loginpress_widget_register_process', // Custom AJAX action for registration
								user_login: username,
								user_email: email,
							};

							// Include passwords in the AJAX request if they are present and valid
							if (pass && user_confirm_pass && pass === user_confirm_pass) {
								ajaxData['loginpress-reg-pass']   = pass;
								ajaxData['loginpress-reg-pass-2'] = user_confirm_pass;
							}
							ajaxData.nonce            = loginpress_widget_params.lp_widget_nonce;
							ajaxData.captcha_response = Captcha_Response;

							$.ajax(
								{
									url: loginpress_widget_params.ajaxurl, // WordPress AJAX handler
									data: ajaxData,
									type: 'POST',
									success: function (response) {
										// No need to parse response, it's already an object
										if (response.success == 1) {
											// Registration was successful
											el.prepend( '<p class="loginpress_widget_success">' + response.data.message + '</p>' );
											$( '#registerform' )
											.find( 'input:not([type="submit"])' )
											.val( '' );
										} else {
											if (response.error) {
												// Error show without captcha verification
												el.prepend( '<p class="loginpress_widget_error">' + response.error + '</p>' );
											} else {
												// Registration failed, display the specific errors
												el.prepend( '<p class="loginpress_widget_error">' + response.data.message + '</p>' );
											}

											if (isGoogleRecaptcha) {
												grecaptcha.reset();
												setTimeout(
													function () {
														window.location.href = window.location.href.split( '?' )[0] + '?registerform=1';
													},
													2000
												);
											}
											if (typeof hcaptcha !== 'undefined' && hcaptcha) {
												hcaptcha.reset();
											}
										}
									},
									error: function () {
										// Handle unknown errors
										el.prepend( '<p class="loginpress_widget_error">An unknown error occurred. Please try again.</p>' );
										if (isGoogleRecaptcha) {
											grecaptcha.reset();
											setTimeout(
												function () {
													window.location.href = window.location.href.split( '?' )[0] + '?registerform=1';
												},
												2000
											);
										}
									}
								}
							);
						}
					);

					// Ajax Login
					loginForm.submit(
						function (e) {
							e.preventDefault();
							$( '.loginpress_widget_error' ).remove();
							var el       = $( this );
							var user_log = el.find( 'input[name="log"]' ).val();
							var user_pwd = el.find( 'input[name="pwd"]' ).val();
							var remember;
							if ( ! user_log) {

								el.prepend( '<p class="loginpress_widget_error">' + loginpress_widget_params.empty_username + '</p>' );
								return false;
							}
							if ( ! user_pwd) {

								el.prepend( '<p class="loginpress_widget_error">' + loginpress_widget_params.empty_password + '</p>' );
								return false;
							}

							// Check for SSL/FORCE SSL LOGIN
							if (loginpress_widget_params.force_ssl_admin == 1 && loginpress_widget_params.is_ssl == 0) {
								return true;
							}

							if (el.find( 'input[name="rememberme"]:checked' ).length > 0) {
								remember = el.find( 'input[name="rememberme"]:checked' ).val();
							}

							// Get the Captcha response
							var Captcha_Response  = '';
							var isGoogleRecaptcha = false;
							if (el.find( 'input[name="cf-turnstile-response"]' ).length > 0) {
								Captcha_Response = el.find( 'input[name="cf-turnstile-response"]' ).val();
							} else if ($( 'body' ).find( 'textarea[name="h-captcha-response"]' ).length > 0) {
								Captcha_Response = $( this ).find( ' textarea[name="h-captcha-response"]' ).val();
							} else if ($( 'body' ).find( 'textarea[name="g-recaptcha-response"]' ).length > 0) {
								Captcha_Response = $( 'body' ).find( 'textarea[name="g-recaptcha-response"]' ).val();
								if ( Captcha_Response == '' ) {
									Captcha_Response = $( this ).find( '.loginpress_recaptcha_wrapper textarea[name="g-recaptcha-response"]' ).val();
								}
								isGoogleRecaptcha = true;
							}
							$.ajax(
								{
									url: loginpress_widget_params.ajaxurl,
									data: {
										action: 'loginpress_widget_login_process',
										user_login: user_log,
										user_password: user_pwd,
										nonce: loginpress_widget_params.lp_widget_nonce,
										remember: remember,
										redirect_to: el.find( 'input[name="redirect_to"]' ).val(),
										captcha_response: Captcha_Response // Include captcha response
									},
									type: 'POST',
									success: function (response) {
										// Remove everything before the first '{'
										var jsonStartIndex = response.indexOf( '{' );
										if (jsonStartIndex !== -1) {
											response = response.substring( jsonStartIndex );
										}

										try {
											var result = JSON.parse( response );

											if (result.success == 1) {
												window.location = result.redirect;
											} else {
												if (result.invalid_username) {
													el.prepend( '<p class="loginpress_widget_error">' + loginpress_widget_params.invalid_username + '</p>' );
												} else if (result.incorrect_password) {
													el.prepend( '<p class="loginpress_widget_error">' + loginpress_widget_params.invalid_password + '</p>' );
												} else if (result.loginpress_use_email) {
													el.prepend( '<p class="loginpress_widget_error">' + loginpress_widget_params.invalid_email + '</p>' );
												} else if (result.captcha_error) {
													el.prepend( '<p class="loginpress_widget_error">' + result.captcha_error + '</p>' );
												} else if (result.recaptcha_error) {
													el.prepend( '<p class="loginpress_widget_error">' + result.recaptcha_error + '</p>' );
												} else if (result.hcaptcha_error) {
													el.prepend( '<p class="loginpress_widget_error">' + result.hcaptcha_error + '</p>' );
												} else if (result.error) {
													el.prepend( '<p class="loginpress_widget_error">' + result.error + '</p>' );
												} else if (result.loginpress_unapprove_error) {
													el.prepend( '<p class="loginpress_widget_error">' + result.loginpress_unapprove_error + '</p>' );
												} else {
													el.prepend( '<p class="loginpress_widget_error">' + result.llla_error + '</p>' );
												}

												if (result.llla_error !== undefined && result.llla_error.toLowerCase().includes( 'locked' )) {
													setTimeout(
														function () {
															window.location.assign( window.location.href );
														},
														2000
													);
												}

												if (isGoogleRecaptcha) {
													grecaptcha.reset();
													setTimeout(
														function () {
															window.location.assign( window.location.href );
														},
														2000
													);
												}
												if (typeof hcaptcha !== 'undefined' && hcaptcha) {
													hcaptcha.reset();
												}
											}
										} catch (e) {
											console.error( 'Invalid JSON response:', response );
										}
									}, error: function (xhr, status, error) {
										// Handle 403 Forbidden or other errors
										console.log( 'AJAX Error:', status, error );

										if (xhr.status === 403) {
											el.prepend( '<p class="loginpress_widget_error">You are not allowed to access the admin panel.</p>' );
										} else {
											el.prepend( '<p class="loginpress_widget_error">An unexpected error occurred: ' + error + '</p>' );
										}
									}

								}
							);
						}
					);
				}
			);
		}
	);
})( jQuery ); // This invokes the function above and allows us to use '$' in place of 'jQuery' in our code.
