/**
 * JavaScript for hCaptcha integration
 *
 * @since 4.0.0
 * @author WPBrigade
 */

jQuery( document ).ready(
	function ($) {
		let hCaptchaSolved = false; // Flag to track if hCaptcha is solved
		let currentForm    = null;     // Track the current form being submitted

		// Callback for when hCaptcha is solved
		function onSubmit(token) {
			if (currentForm) {
				const responseField = currentForm.querySelector( "textarea[name='h-captcha-response']" );
				if (responseField) {
					responseField.value = token;  // Set the hCaptcha token
					hCaptchaSolved      = true;       // Mark hCaptcha as solved
					// Ensure default form submission works
					// Find the submit button and trigger a click instead of calling submit()
					if (currentForm.id !== 'edd_purchase_form') {
						const submitButton = currentForm.querySelector( '[type="submit"]' );
						if (submitButton) {
							submitButton.click(); // Simulate a real user click
							return true;
						}

						currentForm.submit(); // If no button is found, fall back to form submission
					} else {
						currentForm.submit();
					}
				}
			}
		}

		// Declare the selectors array
		let selectors = [];

		// Add forms based on conditions
		if (enabled_form.hcaptcha_login === 'login_form') {
			selectors.push( '#loginform' ); // Add the login form
		}
		if (enabled_form.hcaptcha_lost === 'lostpassword_form') {
			selectors.push( '#lostpasswordform' ); // Add the lost password form
		}
		if (enabled_form.hcaptcha_reg === 'register_form') {
			selectors.push( '#registerform' ); // Add the register form
		}
		if (enabled_form && enabled_form.hcaptcha_woo_log === 'woo_log') {
			selectors.push( '.woocommerce-form-login' ); // Add the register form
		}
		if (enabled_form && enabled_form.hcaptcha_woo_reg === 'woo_reg') {
			selectors.push( '.woocommerce-form-register' ); // Add the register form
		}
		if (enabled_form && enabled_form.hcaptcha_woo_co === 'woo_co') {
			selectors.push( '.woocommerce-checkout' ); // Add the register form
		}
		if (enabled_form && enabled_form.hcaptcha_bp_signup === 'bp_signup') {
			selectors.push( '#signup-form' ); // Add the register form
		}
		if (enabled_form && enabled_form.hcaptcha_edd_log === 'edd') {
			selectors.push( '#edd-blocks-form__login' ); // login block
			selectors.push( '#edd_login_form' ); // login shortcode
			selectors.push( '#edd-blocks-form__register' ); // register block
			selectors.push( '#edd_register_form' ); // register shortcode
		}
		if (enabled_form && enabled_form.hcaptcha_ld === 'ld') {
			selectors.push( '#learndash_registerform' ); // Add the register form
		}
		if (enabled_form && enabled_form.hcaptcha_llms_log === 'llms') {
			selectors.push( '.llms-login' ); // Add the register form
		}
		if (enabled_form && enabled_form.hcaptcha_llms_reg === 'llms_reg') {
			selectors.push( '.llms-new-person-form' ); // Add the register form
		}

		selectors.forEach(
			selector => {
            const forms = document.querySelectorAll( selector ); // Handle multiple forms
            forms.forEach(
                    form => {
					if (selector === '.llms-new-person-form') {
						const submitButton = form.querySelector( '[type="submit"]' );
						if (submitButton) {
							submitButton.addEventListener(
                                'click',
                                function (event) {
                                    if ( ! hCaptchaSolved) {
                                        event.preventDefault(); // Stop button click => no form submit
                                        currentForm = form;
                                        hcaptcha.execute();
                                    }
                                }
							);
						}
					}
                    form.addEventListener(
							"submit",
							function (event) {
								if ( ! hCaptchaSolved) {
									event.preventDefault(); // Prevent form submission
									currentForm = form;    // Track the current form
									hcaptcha.execute();    // Trigger hCaptcha validation
								}
							}
						);
                    }
				);
			}
		);

		// Expose `onSubmit` globally for hCaptcha callback
		window.onSubmit = onSubmit;

		// Handle EDD purchase form specifically
		$( '#edd_purchase_form' ).on(
			'submit',
			function (event) {
				if ( ! hCaptchaSolved) {
					event.preventDefault();
					currentForm = this;

					// Find the hCaptcha container in the form
					const hcaptchaContainer = $( this ).find( '.h-captcha' )[0];
					if (hcaptchaContainer) {
						// Get the widget ID from data attribute
						hcaptchaWidgetId = hcaptchaContainer.getAttribute( 'data-hcaptcha-widget-id' );

						// Execute hCaptcha
						hcaptcha.execute( hcaptchaWidgetId );
						const submitBtn = $( currentForm ).find( '[type="submit"]' );
						submitBtn.prop( 'disabled', false ).val( 'Free Download' );
						$( '.edd-loading-ajax' ).hide();
					} else {
						// console.error('hCaptcha container not found in form');
						this.submit(); // Fallback to regular submission
					}
				}
			}
		);

	}
);
