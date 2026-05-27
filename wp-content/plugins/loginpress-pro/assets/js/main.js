jQuery(document).ready( function($) {

  	$("#loginpress-license").on('click', function(e) {

		e.preventDefault();
		var loginpress_license = $("#license_key").val();
		$.ajax({
			url: loginpressLicense.ajaxurl,
			nonce: loginpressLicense.license_nonce,
			type: 'post',
			data: 'loginpress_license=' + loginpress_license +
				'&action=loginpress_activate_license',
			success: function() {
			},
			error: function() {
			}
		}); // end ajax.
  	});
	  $(document).on('change', '#loginpress_autologin_users_select', function(){
		$('[name="loginpress_autologin_users_length"]').val($(this).val());
		$('[name="loginpress_autologin_users_length"]').trigger('change');
	});
	
	$(document).on('change', '#loginpress_login_redirect_users_select', function(){
		$('[name="loginpress_login_redirect_users_length"]').val($(this).val());
		$('[name="loginpress_login_redirect_users_length"]').trigger('change');
	});
	
	$(document).on('change', '#loginpress_login_redirect_roles_select', function(){
		$('[name="loginpress_login_redirect_roles_length"]').val($(this).val());
		$('[name="loginpress_login_redirect_roles_length"]').trigger('change');
	});
	
	$(document).on('change', '#loginpress_limit_login_log_select', function(){
		$('[name="loginpress_limit_login_log_length"]').val($(this).val());
		$('[name="loginpress_limit_login_log_length"]').trigger('change');
	});
	
	$(document).on('change', '#loginpress_limit_login_whitelist_select', function(){
		$('[name="loginpress_limit_login_whitelist_length"]').val($(this).val());
		$('[name="loginpress_limit_login_whitelist_length"]').trigger('change');
	});
	
	$(document).on('change', '#loginpress_limit_login_blacklist_select', function(){
		$('[name="loginpress_limit_login_blacklist_length"]').val($(this).val());
		$('[name="loginpress_limit_login_blacklist_length"]').trigger('change');
	});
	
	$("#deactivate-loginpress").on('click', function(e) {

		e.preventDefault();
		
		$.ajax({
			url: loginpressLicense.ajaxurl,
			nonce: loginpressLicense.license_nonce,
			type: 'post',
			data: 'action=loginpress_deactivate_license',
			success: function() {
			},
			error: function() {
			}
		}); // end ajax.
	});
  /**
   * Enabled/Disabled CAPTCHAs.
   * 
   * @since 4.0.0
   */
  function loginpressCaptchasEnabled() {
	var captchasEnabled = $('tr.enable_captchas [type="checkbox"]' ).is(':checked') ? 1 : 0;

	function loginpressCaptchasType() {
		var $submit_btn  = $('#loginpress_captcha_settings #submit');
		var catpchasType = $('tr.captchas_type select').val();
		var recpchasType = $('tr.recaptcha_type select').val();
		var v2_robot_verified = $('tr.v2_robot_verified input').val();
		var hcaptcha_verified = $('tr.hcaptcha_verified input').val();
		var turnstile_verified = $('.cf-input-container svg title:contains("success")').length > 0;

		$('tr.captchas_type').show();

		if ( catpchasType == 'type_recaptcha' ) {
			hidehcaptcha();
			hideturnstile();
			loginpressReCaptchatype();
			if ( recpchasType == 'v2-robot' && v2_robot_verified == 'on' ) {
				$submit_btn.prop('disabled', false);
			} else if ( recpchasType == 'v2-robot' && v2_robot_verified != 'on' ) {
				$submit_btn.prop('disabled', true);
			}
		}
		if ( catpchasType == 'type_hcaptcha' ) {
			hideRecatchaSettings();
			hideturnstile();
			loginpressShowhcaptchaSettings();
			if ( hcaptcha_verified == 'on' ) {
				$submit_btn.prop('disabled', false);
			} else if ( hcaptcha_verified != 'on' ) {
				$submit_btn.prop('disabled', true);
			}
		}
		if ( catpchasType == 'type_cloudflare' ) {
			hideRecatchaSettings();
			hidehcaptcha();
			loginpressShowcfTurnstile();
			if (turnstile_verified == true) {
				$submit_btn.prop('disabled', false);
			} else if (turnstile_verified != true) {
				$submit_btn.prop('disabled', true);
			}
		}
		
	}

	if ( captchasEnabled === 1 ) {
		loginpressCaptchasType();
			
	} else {
		$('tr.captchas_type').hide();
		hideRecatchaSettings();
		hidehcaptcha();
		hideturnstile();
	}
	$('#wpb-loginpress_captcha_settings\\[enable_captchas\\]').on('change', function() {
		loginpressCaptchasEnabled();
		var $submit_btn = $('#loginpress_captcha_settings #submit');
		if(!$(this).is(':checked')){
			$submit_btn.prop('disabled', false);
		}
	});
	$('tr.captchas_type select').on('change', function() {
        loginpressCaptchasType();
	});
	$('tr.captchas_type select').each(function() {
        // Store the initial value of the select element
        $(this).attr('data-previous', $(this).val());
    });
	
	$('tr.recaptcha_type select').on( 'change' , function() {
		loginpressReCaptchatype();
	});

	$('tr.hcaptcha_type select').on( 'change' , function() {
		loginpressShowhcaptchaSettings();
	});
  }

  /**
   * Show/Hide fields based on selected reCaptcha type.
   *
   *
   * @since 4.0.0
   */
  function loginpressReCaptchatype() {
	  
	$('tr.recaptcha_type').show();
	var recaptchaType = $('tr.recaptcha_type select').val();
	if ( recaptchaType == 'v2-robot' ) {

		$('tr.captcha_theme').show();
		$('tr.captcha_language').show();
		$('tr.site_key').show();
		$('tr.secret_key').show();
		$('tr.site_key_v3').hide();
		$('tr.secret_key_v3').hide();
		$('tr.good_score').hide();
		$('#wpb-loginpress_captcha_settings\\[captcha_enable\\]\\[woocommerce_login_form\\]').closest('label').show().next('br').show();
		$('#wpb-loginpress_captcha_settings\\[captcha_enable\\]\\[woocommerce_register_form\\]').closest('label').show().next('br').show();
		$('#wpb-loginpress_captcha_settings\\[captcha_enable\\]\\[comment_form_defaults\\]').closest('label').show().next('br').show();
	}


	if ( recaptchaType == 'v3' ) {

		$('tr.site_key_v3').show();
		$('tr.secret_key_v3').show();
		$('tr.good_score').show();
		$('tr.site_key').hide();
		$('tr.secret_key').hide();
		$('tr.captcha_theme').hide();
		$('tr.captcha_language').hide();
		$('#wpb-loginpress_captcha_settings\\[captcha_enable\\]\\[woocommerce_login_form\\]').closest('label').show().next('br').show();
		$('#wpb-loginpress_captcha_settings\\[captcha_enable\\]\\[woocommerce_register_form\\]').closest('label').show().next('br').show();
		$('#wpb-loginpress_captcha_settings\\[captcha_enable\\]\\[comment_form_defaults\\]').closest('label').show().next('br').show();
	}
	$('tr.captcha_enable').show();
  }

  /**
   * Displays hCaptcha settings fields based on the selected hCaptcha type.
   *
   * @since 4.0.0
   */
  function loginpressShowhcaptchaSettings() {
	$('tr.enable_hcaptcha').show();
	$('tr.hcaptcha_type').show();
	$('tr.hcaptcha_site_key').show();
	$('tr.hcaptcha_secret_key').show();
	$('tr.hcaptcha_theme').show();
	$('tr.hcaptcha_language').show();
	$('tr.hcaptcha_enable').show();

	var hcaptchaType = $('tr.hcaptcha_type select').val();
	if ( hcaptchaType == 'invisible' ) {
		$('#wpb-loginpress_captcha_settings\\[hcaptcha_enable\\]\\[woocommerce_login_form\\]').closest('label').hide().next('br').hide();
		$('#wpb-loginpress_captcha_settings\\[hcaptcha_enable\\]\\[woocommerce_register_form\\]').closest('label').hide().next('br').hide();
		$('#wpb-loginpress_captcha_settings\\[hcaptcha_enable\\]\\[comment_form_defaults\\]').closest('label').hide().next('br').hide();
	} else {
		$('#wpb-loginpress_captcha_settings\\[hcaptcha_enable\\]\\[woocommerce_login_form\\]').closest('label').show().next('br').show();
		$('#wpb-loginpress_captcha_settings\\[hcaptcha_enable\\]\\[woocommerce_register_form\\]').closest('label').show().next('br').show();
		$('#wpb-loginpress_captcha_settings\\[hcaptcha_enable\\]\\[comment_form_defaults\\]').closest('label').show().next('br').show();
	}
	
  }

  /**
   * Displays Cloudflare Turnstile settings fields.  
   */
  function loginpressShowcfTurnstile() {
	$('tr.site_key_cf').show();
	$('tr.secret_key_cf').show();
	$('tr.captcha_enable_cf').show();
	$('tr.cf_theme').show();
	// $('tr.validate_cf').show();
  }

  /**
   * Displays Google reCaptcha settings fields.  
   */
  function hideRecatchaSettings() {
	  $('tr.enable_repatcha').hide();
	  $('tr.recaptcha_type').hide();
	  $('tr.site_key').hide();
	  $('tr.secret_key').hide();
	  $('tr.validate_v2_keys').hide();
	  $('tr.captcha_theme').hide();
	  $('tr.captcha_language').hide();
	  $('tr.captcha_enable').hide();
	  $('tr.woo_captcha_enable').hide();
	  $('tr.good_score').hide();
	  $('tr.site_key_v3').hide();
	  $('tr.secret_key_v3').hide();
  }

  function hidehcaptcha() {
	  $('tr.hcaptcha_enable').hide();
	  $('tr.enable_hcaptcha').hide();
	  $('tr.hcaptcha_type').hide();
	  $('tr.hcaptcha_site_key').hide();
	  $('tr.hcaptcha_secret_key').hide();
	  $('tr.validate_hcaptcha_keys').hide();
	  $('tr.hcaptcha_theme').hide();
	  $('tr.hcaptcha_language').hide();
  }
  function hideturnstile() {
	$('tr.site_key_cf').hide();
	$('tr.secret_key_cf').hide();
	$('tr.captcha_enable_cf').hide();
	$('tr.cf_theme').hide();
	$('tr.validate_cf').hide();
  }
  
  const site_key_svg     = $('tr.site_key input[type="text"]');
  const secret_key_svg   = $('tr.secret_key input[type="text"]');
  const site_hcaptcha_svg = $('tr.hcaptcha_site_key input[type="text"]');
  const secret_hcaptcha_svg = $('tr.hcaptcha_secret_key input[type="text"]');
  if (site_key_svg.val() === "") {
	$('tr.site_key svg').hide();
  }
  if (secret_key_svg.val() === "") {
	$('tr.secret_key svg').hide();
  }
  if (site_hcaptcha_svg.val() === "") {
	$('tr.hcaptcha_site_key svg').hide();
  }
  if (secret_hcaptcha_svg.val() === "") {
	$('tr.hcaptcha_secret_key svg').hide();
  }

  var $site_key = $('input[name="loginpress_captcha_settings[site_key]"]');
  var $secret_key = $('input[name="loginpress_captcha_settings[secret_key]"]');
  var $v3_site = $('tr.site_key_v3 input');
  var $v3_secret = $('tr.secret_key_v3 input');
  var $submit_btn = $('#loginpress_captcha_settings#submit');
  function submit_button_prop() {
	var recaptchaType = $('tr.recaptcha_type select').val();
	if ( (!$site_key.val() || !$secret_key.val()) && recaptchaType == 'v2-robot' ) {
		$submit_btn.prop('disabled', true);
	}
	if ( (!$v3_site.val() || !$v3_secret.val()) && recaptchaType == 'v3' ) {
		$submit_btn.prop('disabled', true);
	}
  }

  $(document).on('click', '[href]', function () {
	var check = false;
    const hrefValue = $(this).attr('href'); // Get the href attribute value
	if ($submit_btn.prop('disabled')){
		check = true;
	}
    if (hrefValue === "#loginpress_setting") {
        $submit_btn.prop('disabled', false);
    } else if (hrefValue === "#loginpress_autologin") {
        $submit_btn.prop('disabled', false);
    } else if (hrefValue === "#loginpress_login_redirects") {
        $submit_btn.prop('disabled', false);
    } else if (hrefValue === "#loginpress_limit_login_attempts") {
        $submit_btn.prop('disabled', false);
    } else if (hrefValue === "#loginpress_hidelogin") {
		$submit_btn.prop('disabled', false);
    }  else if (hrefValue === "#loginpress_social_logins") {
        $submit_btn.prop('disabled', false);
    } else if (hrefValue === "#loginpress_captcha_settings") {
		if(check){
			$submit_btn.prop('disabled', true);
		}
		else{
			$submit_btn.prop('disabled', false);
		}
		submit_button_prop();
	}
  });

  $('#loginpress_pro_license-tab').parent().on('click', function(){
	  // Simulate a mouse click:
	  window.location.href = loginpressLicense.admin_url + "/admin.php?page=loginpress-license";

  });

  // Add class for unapproved user row.
  $('.submitapprove').parent().parent().parent().parent().addClass('loginpress-unapproved-user-row');
  // Add class for approved user row.
  $('.submitunapprove').parent().parent().parent().parent().addClass('loginpress-approved-user-row');
	if(!$('#wpb-loginpress_captcha_settings\\[enable_captchas\\]').is(':checked')){
	    $submit_btn = $('#loginpress_captcha_settings #submit');
		$submit_btn.prop('disabled', true);
    }
	loginpressCaptchasEnabled();
	submit_button_prop();

   let lp_currentCaptchaScript = null;

   // Function to load a CAPTCHA script dynamically
   function loginpress_pro_loadCaptchaScript(url) {
	   return new Promise((resolve, reject) => {
		   // Remove any previously loaded CAPTCHA script
		   if (lp_currentCaptchaScript) {
			   document.head.removeChild(lp_currentCaptchaScript);
			   lp_currentCaptchaScript = null;
		   }

		   // Create a new script element
		   const script = document.createElement('script');
		   script.src = url;
		   script.async = true;
		   script.onload = resolve;
		   script.onerror = reject;

		   // Append the script to the document head
		   document.head.appendChild(script);
		   lp_currentCaptchaScript = script;
	   });
   }

   function loginpress_preloadCaptchaScript() {
		const selectedCaptcha = $('tr.captchas_type select').val();
		// Determine the URL for the selected CAPTCHA type
		let captchaUrl = '';
		switch (selectedCaptcha) {
			case 'type_recaptcha':
				captchaUrl = loginpressLicense.recaptchaUrl;
				break;
			case 'type_hcaptcha':
				captchaUrl = loginpressLicense.hcaptchaUrl;
				break;
		}

		if ( captchaUrl !== '' ) {
			// Load the selected CAPTCHA script
			loginpress_pro_loadCaptchaScript(captchaUrl)
			.then(() => {
				// CAPTCHA script loaded successfully
				})
			.catch(() => {
				// console.error(`Failed to load ${selectedCaptcha} script:`, error);
			});
		}
   }
	   

   // Event listener for the dropdown change
	$('tr.captchas_type select, #wpb-loginpress_captcha_settings\\[enable_captchas\\]').on('change', function () {
		loginpress_preloadCaptchaScript();
   });

   var captchasEnabled = $('tr.enable_captchas [type="checkbox"]' ).is(':checked') ? 1 : 0;

    if ( captchasEnabled === 1 ) {
		loginpress_preloadCaptchaScript();
    }

} );
