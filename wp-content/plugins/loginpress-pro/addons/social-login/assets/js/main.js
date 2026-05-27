jQuery(document).ready(function($) {
  const activeAddons = loginpressProData.activeAddons;

  // Example: Run JS only if 'social-login' addon is active
  if (activeAddons.includes("social-login")) {
    // Select the actual dropdown element inside the custom-styled select
    const apiVersionDropdown = $('#loginpress_social_logins\\[twitter_api_version\\]');

    // Select the label elements for API Key and Secret Key
    const apiKeyLabel = $('label[for="loginpress_social_logins[twitter_oauth_token]"]');
    const apiSecretLabel = $('label[for="loginpress_social_logins[twitter_token_secret]"]');
    const apiKeyDesc = $('input[name="loginpress_social_logins[twitter_oauth_token]"]').closest('td').find('p.description');
      const apiSecretDesc = $('input[name="loginpress_social_logins[twitter_token_secret]"]').closest('td').find('p.description');

    // Function to update labels based on API version
    function loginPressUpdateTwitterLabels() {
      if (apiVersionDropdown.val() === 'oauth2') {
          apiKeyLabel.text(loginpressProviderData.twitterclientID);
          apiSecretLabel.text(loginpressProviderData.twitterclientSecret);

          apiKeyDesc.text(loginpressProviderData.twitterclientIDDesc);
          apiSecretDesc.text(loginpressProviderData.twitterclientSecretDesc);
      } else {
          apiKeyLabel.text(loginpressProviderData.twitterapiKey);
          apiSecretLabel.text(loginpressProviderData.twitterapiSecret);

          apiKeyDesc.text(loginpressProviderData.twitterapiKeyDesc);
          apiSecretDesc.text(loginpressProviderData.twitterapiSecretDesc);
      }
  }

    // Run on page load
    loginPressUpdateTwitterLabels();

    // Listen for dropdown changes
    apiVersionDropdown.on('change', function () {
        loginPressUpdateTwitterLabels();
    });

    'use strict';
    var apple_secret_val;
    const socialLoginOption = loginpressData.socialLoginOption;
    function loginpress_hide_provider_setting(){
      const classNamesToHide = [
      "go-back-button",
        "enable_social_login_links",
        "social_login_button_label",
        "social_login_shortcode",
        "social_button_styles",
        "social_button_position",
        "facebook",
        "facebook_app_id",
        "facebook_app_secret",
        "facebook_redirect_uri",
        "twitter",
        "twitter_api_version",
        "twitter_oauth_token",
        "twitter_token_secret",
        "twitter_callback_url",
        "gplus",
        "gplus_client_id",
        "gplus_client_secret",
        "gplus_redirect_uri",
        "linkedin",
        "linkedin_client_id",
        "linkedin_client_secret",
        "linkedin_redirect_uri",
        "microsoft",
        "microsoft_app_id",
        "microsoft_app_secret",
        "microsoft_redirect_uri",
        "apple",
        "apple_button_label",
        "apple_service_id",
        "apple_key_id",
        "apple_team_id",
        "apple_p_key",
        "apple_secret",
        "github",
        "github_client_id",
        "github_client_secret",
        "github_redirect_uri",
        "github_app_name",
        "discord",
        "discord_client_id",
        "discord_client_secret",
        "discord_redirect_uri",
        "discord_generated_url",
        "wordpress",
        "wordpress_client_id",
        "wordpress_client_secret",
        "wordpress_redirect_uri",
        "wordpress_button_label",
        "discord_button_label",
        "github_button_label",
        "linkedin_button_label",
        "microsoft_button_label",
        "facebook_button_label",
        "google_button_label",
        "twitter_button_label",
        "facebook_status",
        "apple_status",
        "github_status",
        "wordpress_status",
        "discord_status",
        "microsoft_status",
        "gplus_status",
        "linkedin_status",
        "twitter_status",
        "provider_order",
        "amazon_status",
        "amazon",
        "amazon_button_label",
        "amazon_client_id",
        "amazon_client_secret",
        "amazon_redirect_uri",
        "pinterest_status",
        "pinterest",
        "pinterest_button_label",
        "pinterest_client_id",
        "pinterest_client_secret",
        "pinterest_redirect_uri",
        // "bluesky_status",
        // "bluesky",
        // "bluesky_button_label",
        // "bluesky_client_id",
        // "bluesky_client_secret",
        // "bluesky_redirect_uri",
        "disqus_status",
        "disqus",
        "disqus_button_label",
        "disqus_client_id",
        "disqus_client_secret",
        "disqus_callback_url",
        "reddit_status",
        "reddit",
        "reddit_button_label",
        "reddit_client_id",
        "reddit_client_secret",
        "reddit_redirect_uri",
        "spotify_status",
        "spotify",
        "spotify_button_label",
        "spotify_client_id",
        "spotify_client_secret",
        "spotify_redirect_uri",
        "twitch_status",
        "twitch",
        "twitch_button_label",
        "twitch_client_id",
        "twitch_client_secret",
        "twitch_redirect_uri",
      ];

      // Iterate through the class names and hide the rows
      classNamesToHide.forEach(function (className) {
          const rows = document.querySelectorAll(`tr.${className}`);
          rows.forEach(function (row) {
              row.style.display = "none";
          });
      });
      $('.loginpress-apple-reconfigure').hide();
    }
    $('.nav-tab').on('click', function () {
      var target = $(this).attr('href');
      if (target !== '#loginpress_social_logins') {
        $('.loginpress-apple-reconfigure').hide(); // Hide the reconfigure button
      } else if (target == '#loginpress_social_logins' && $('.apple_service_id').css('display') !== 'none'){
        $('.loginpress-apple-reconfigure').show();
      }
    });
    function loginpress_apple_settings(){
      if ($('#wpb-loginpress_social_logins\\[apple\\]').is(":checked")) {
        if (socialLoginOption.apple_secret && socialLoginOption.apple_secret.trim() !== '') {
          // Show the rows
          if (!$('tr.apple_secret input').val())
          {
            $('tr.apple_secret input').val(apple_secret_val);
          }
          $('tr.apple_service_id, tr.apple_secret').show();
          if (!$('.loginpress-apple-reconfigure').length) {
            $('#loginpress_social_logins').after('<p class="submit"><button class="loginpress-apple-reconfigure button button-primary">Reconfigure</button></p>');
          }
          else{
            $('.loginpress-apple-reconfigure').show();
          }
          // Add the readonly attribute to the input fields in the rows
          $('tr.apple_service_id input, tr.apple_secret input').attr('readonly', true);
          // Make the input field inside the row read-only
          $('#loginpress_social_logins p.submit').hide();
          $('tr.apple_button_label').hide();
          $('tr.apple_key_id').hide();
          $('tr.apple_team_id').hide();
          $('tr.apple_p_key').hide();
        }
        else{
          $('tr.apple_button_label').show();
          $('tr.apple_service_id').show();
          $('tr.apple_key_id').show();
          $('tr.apple_team_id').show();
          $('tr.apple_p_key').show();
          $('tr.apple_secret').hide();
        }
      } else {
        $('#loginpress_social_logins p.submit').show();
        $('tr.apple_button_label').hide();
        $('tr.apple_service_id').hide();
        $('tr.apple_key_id').hide();
        $('tr.apple_team_id').hide();
        $('tr.apple_p_key').hide();
        $('tr.apple_secret').hide();
      }
    }

    function loginpress_facebook_settings(){
      if ($('#wpb-loginpress_social_logins\\[facebook\\]').is(":checked")) {
        $('tr.facebook_button_label').show();
        $('tr.facebook_app_id').show();
        $('tr.facebook_app_secret').show();
        $('tr.facebook_redirect_uri').show();
      } else {
        $('tr.facebook_button_label').hide();
        $('tr.facebook_app_id').hide();
        $('tr.facebook_app_secret').hide();
        $('tr.facebook_redirect_uri').hide();
      }
    }

    function loginpress_twitter_settngs(){ 
      if ($('#wpb-loginpress_social_logins\\[twitter\\]').is(":checked")) {
        $('tr.twitter_button_label').show();
        $('tr.twitter_oauth_token').show();
        $('tr.twitter_callback_url').show();
        $('tr.twitter_token_secret').show();
        $('tr.twitter_api_version').show();
      } else {
        $('tr.twitter_button_label').hide();
        $('tr.twitter_oauth_token').hide();
        $('tr.twitter_token_secret').hide();
        $('tr.twitter_callback_url').hide();
        $('tr.twitter_api_version').hide();
      }
    }
    $('td:has(.provider-container)').attr('colspan', '2');
    $('#loginpress_social_logins input[type="text"], #loginpress_social_logins textarea').on('change input keyup', function(){
    $('#loginpress_social_logins [class$="_status"]:visible').find('button').attr('disabled', true);
    $('#loginpress_social_logins [class$="_status"]:visible').find('[type="hidden"]').val('not verified');

      // Add the note dynamically to the description if not already present
      if (!$('.provider-description .note-message').length) {
          $('.provider-description').append('<div class="note-message"><p><strong>Note:</strong> Save changes to verify the settings.</p></div');
      }

    });
    
    function loginpress_microsoft_settings(){
      if ($('#wpb-loginpress_social_logins\\[microsoft\\]').is(":checked")) {
        $('tr.microsoft_button_label').show();
        $('tr.microsoft_app_id').show();
        $('tr.microsoft_app_secret').show();
        $('tr.microsoft_redirect_uri').show();
      } else {
        $('tr.microsoft_button_label').hide();
        $('tr.microsoft_app_id').hide();
        $('tr.microsoft_app_secret').hide();
        $('tr.microsoft_redirect_uri').hide();
      }
    }

    function loginpress_google_settings(){
      if ($('#wpb-loginpress_social_logins\\[gplus\\]').is(":checked")) {
        $('tr.google_button_label').show();
        $('tr.gplus_client_id').show();
        $('tr.gplus_client_secret').show();
        $('tr.gplus_redirect_uri').show();
      } else {
        $('tr.google_button_label').hide();
        $('tr.gplus_client_id').hide();
        $('tr.gplus_client_secret').hide();
        $('tr.gplus_redirect_uri').hide();
      }
    }

    function loginpress_linkedin_settings(){
      if ($('#wpb-loginpress_social_logins\\[linkedin\\]').is(":checked")) {
        $('tr.linkedin_button_label').show();
        $('tr.linkedin_client_id').show();
        $('tr.linkedin_client_secret').show();
        $('tr.linkedin_redirect_uri').show();
      } else {
        $('tr.linkedin_button_label').hide();
        $('tr.linkedin_client_id').hide();
        $('tr.linkedin_client_secret').hide();
        $('tr.linkedin_redirect_uri').hide();
      }
    }

    $(document).on('click', '.loginpress-apple-reconfigure', function() {
      // Show all fields for editing
      $('tr.apple_button_label').show();
      $('tr.apple_service_id').show();
      $('tr.apple_key_id').show();
      $('tr.apple_team_id').show();
      $('tr.apple_p_key').show();
      $('tr.apple_secret').hide();

      // Remove readonly attribute
      $('tr.apple_service_id input').removeAttr('readonly');
      $('tr.apple_secret input').removeAttr('readonly');
      apple_secret_val = $('tr.apple_secret input').val();
      $('tr.apple_secret input').removeAttr('readonly').val('');
      //changing apple status to not verified
      $('#loginpress_social_logins [class$="apple_status"]:visible').find('[type="hidden"]').val('not verified');
      // Hide "Reconfigure" button and show "Save Changes"
      $(this).hide();
      $('#loginpress_social_logins p.submit').show();
  });

    $("#wpb-loginpress_social_logins\\[apple\\]").on('click', function() {
      loginpress_apple_settings()
    });

    $("#wpb-loginpress_social_logins\\[facebook\\]").on('click', function() {
      loginpress_facebook_settings();
    });

    $("#wpb-loginpress_social_logins\\[twitter\\]").on('click', function() {
      loginpress_twitter_settngs();
    });

    function loginpress_github_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[github\\]').is(":checked")) {
        $('tr.github_button_label').show();
        $('tr.github_client_id').show();
        $('tr.github_client_secret').show();
        $('tr.github_redirect_uri').show();
        $('tr.github_app_name').show();
      } else {
        $('tr.github_button_label').hide();
        $('tr.github_client_id').hide();
        $('tr.github_client_secret').hide();
        $('tr.github_redirect_uri').hide();
        $('tr.github_app_name').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[github\\]").on('click', function () {
      loginpress_github_btn_settings();
    });

    function loginpress_discord_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[discord\\]').is(":checked")) {
        $('tr.discord_button_label').show();
        $('tr.discord_client_id').show();
        $('tr.discord_client_secret').show();
        $('tr.discord_redirect_uri').show();
        $('tr.discord_generated_url').show();
      } else {
        $('tr.discord_button_label').hide();
        $('tr.discord_client_id').hide();
        $('tr.discord_client_secret').hide();
        $('tr.discord_redirect_uri').hide();
        $('tr.discord_generated_url').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[discord\\]").on('click', function () {
      loginpress_discord_btn_settings();
    });

    function loginpress_wordpress_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[wordpress\\]').is(":checked")) {
        $('tr.wordpress_button_label').show();
        $('tr.wordpress_client_id').show();
        $('tr.wordpress_client_secret').show();
        $('tr.wordpress_redirect_uri').show();
      } else {
        $('tr.wordpress_button_label').hide();
        $('tr.wordpress_client_id').hide();
        $('tr.wordpress_client_secret').hide();
        $('tr.wordpress_redirect_uri').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[wordpress\\]").on('click', function () {
      loginpress_wordpress_btn_settings();
    });

    function loginpress_amazon_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[amazon\\]').is(":checked")) {
        $('tr.amazon_button_label').show();
        $('tr.amazon_client_id').show();
        $('tr.amazon_client_secret').show();
        $('tr.amazon_redirect_uri').show();
      } else {
        $('tr.amazon_button_label').hide();
        $('tr.amazon_client_id').hide();
        $('tr.amazon_client_secret').hide();
        $('tr.amazon_redirect_uri').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[amazon\\]").on('click', function () {
      loginpress_amazon_btn_settings();
    });

    function loginpress_pinterest_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[pinterest\\]').is(":checked")) {
        $('tr.pinterest_button_label').show();
        $('tr.pinterest_client_id').show();
        $('tr.pinterest_client_secret').show();
        $('tr.pinterest_redirect_uri').show();
      } else {
        $('tr.pinterest_button_label').hide();
        $('tr.pinterest_client_id').hide();
        $('tr.pinterest_client_secret').hide();
        $('tr.pinterest_redirect_uri').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[pinterest\\]").on('click', function () {
      loginpress_pinterest_btn_settings();
    });

    function loginpress_reddit_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[reddit\\]').is(":checked")) {
        $('tr.reddit_button_label').show();
        $('tr.reddit_client_id').show();
        $('tr.reddit_client_secret').show();
        $('tr.reddit_redirect_uri').show();
      } else {
        $('tr.reddit_button_label').hide();
        $('tr.reddit_client_id').hide();
        $('tr.reddit_client_secret').hide();
        $('tr.reddit_redirect_uri').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[reddit\\]").on('click', function () {
      loginpress_reddit_btn_settings();
    });

    function loginpress_spotify_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[spotify\\]').is(":checked")) {
        $('tr.spotify_button_label').show();
        $('tr.spotify_client_id').show();
        $('tr.spotify_client_secret').show();
        $('tr.spotify_redirect_uri').show();
      } else {
        $('tr.spotify_button_label').hide();
        $('tr.spotify_client_id').hide();
        $('tr.spotify_client_secret').hide();
        $('tr.spotify_redirect_uri').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[spotify\\]").on('click', function () {
      loginpress_spotify_btn_settings();
    });

    function loginpress_twitch_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[twitch\\]').is(":checked")) {
        $('tr.twitch_button_label').show();
        $('tr.twitch_client_id').show();
        $('tr.twitch_client_secret').show();
        $('tr.twitch_redirect_uri').show();
      } else {
        $('tr.twitch_button_label').hide();
        $('tr.twitch_client_id').hide();
        $('tr.twitch_client_secret').hide();
        $('tr.twitch_redirect_uri').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[twitch\\]").on('click', function () {
      loginpress_twitch_btn_settings();
    });

    // function loginpress_bluesky_btn_settings() {
    //   if ($('#wpb-loginpress_social_logins\\[bluesky\\]').is(":checked")) {
    //     $('tr.bluesky_button_label').show();
    //     $('tr.bluesky_client_id').show();
    //     $('tr.bluesky_client_secret').show();
    //     $('tr.bluesky_redirect_uri').show();
    //   } else {
    //     $('tr.bluesky_button_label').hide();
    //     $('tr.bluesky_client_id').hide();
    //     $('tr.bluesky_client_secret').hide();
    //     $('tr.bluesky_redirect_uri').hide();
    //   }
    // }
    // $("#wpb-loginpress_social_logins\\[bluesky\\]").on('click', function () {
    //   loginpress_bluesky_btn_settings();
    // });

    function loginpress_disqus_btn_settings() {
      if ($('#wpb-loginpress_social_logins\\[disqus\\]').is(":checked")) {
        $('tr.disqus_button_label').show();
        $('tr.disqus_client_id').show();
        $('tr.disqus_client_secret').show();
        $('tr.disqus_callback_url').show();
      } else {
        $('tr.disqus_button_label').hide();
        $('tr.disqus_client_id').hide();
        $('tr.disqus_client_secret').hide();
        $('tr.disqus_callback_url').hide();
      }
    }
    $("#wpb-loginpress_social_logins\\[disqus\\]").on('click', function () {
      loginpress_disqus_btn_settings();
    });

    $("#wpb-loginpress_social_logins\\[gplus\\]").on('click', function() {
      loginpress_google_settings();
    });

    $("#wpb-loginpress_social_logins\\[linkedin\\]").on('click', function() {
      loginpress_linkedin_settings();
    });

    $("#wpb-loginpress_social_logins\\[microsoft\\]").on('click', function () {
      loginpress_microsoft_settings();
    });

    $('.loginpress-social-login-tab').on('click', function (event) {
      event.preventDefault();
      if(!$(this).hasClass('loginpress-social-login-active')){

      var target = $(this).attr('href');
      window.localStorage.setItem('loginpress-active', target);

      // Handle active tab styling
      $(this).addClass('loginpress-social-login-active').siblings().removeClass('loginpress-social-login-active');
        // Show/hide content based on the selected tab
        switch (target) {
          case '#loginpress_social_login_settings':
            $('#loginpress_social_logins p.submit').show();
            $('.go-back-button,.provider-logo-block,.loginpress_info').hide();
            loginpress_showSettingsTab();
            break;
          case '#loginpress_social_login_styles':
            $('#loginpress_social_logins p.submit').show();
            $('.go-back-button,.provider-logo-block,.loginpress_info').hide();
            loginpress_showStylesTab();
            break;
          case '#loginpress_social_login_providers':
            loginpress_showProvidersTab();
            $('#provider-cards-container').show();
            
            $('#loginpress-provider-management').addClass('loginpress-social-login');
            sortIt();
            break;
        }
      }
    });
    // Get the active tab from localStorage
    var target = window.localStorage.getItem('loginpress-active');

    // If no active tab is stored in localStorage, set the first tab as the default
    if (!target) {
        target = '#loginpress_social_login_settings'; // Default to the first tab
        window.localStorage.setItem('loginpress-active', target);
    }
      // Handle active tab styling
      $('[href="'+target+'"]').addClass('loginpress-social-login-active').siblings().removeClass('loginpress-social-login-active');
        // Show/hide content based on the selected tab
        switch (target) {
          case '#loginpress_social_login_settings':
            $('#loginpress_social_logins p.submit').show();
            $('.go-back-button,.loginpress_info').hide();
            loginpress_showSettingsTab();
            break;
          case '#loginpress_social_login_styles':
            $('#loginpress_social_logins p.submit').show();
            $('.go-back-button,.loginpress_info').hide();
            loginpress_showStylesTab();
            break;
          case '#loginpress_social_login_providers':
            loginpress_showProvidersTab();
            $('#provider-cards-container').show();
            $('#loginpress-provider-management').addClass('loginpress-social-login');
            break;
        }

  // Separate functions for each tab
  function loginpress_showSettingsTab() {
    loginpress_hide_provider_setting();
    // Ensure submit buttons are visible
    if ($('#loginpress-provider-management').length) {
      $('#loginpress-provider-management').removeClass('loginpress-social-login');
    }
      // Show settings fields 
      $('.enable_social_login_links').closest('tr').show();
      $('.social_login_button_label').closest('tr').show();
      $('.social_login_shortcode').closest('tr').show();
  }

  function loginpress_showStylesTab() {
    loginpress_hide_provider_setting();
    if ($('#loginpress-provider-management').length) {
      $('#loginpress-provider-management').removeClass('loginpress-social-login');
    }
      // Show style fields
      $('.social_button_styles').closest('tr').show();
      $('.social_button_position').closest('tr').show();
  }
    

  function loginpress_showProvidersTab() {
    $('#loginpress_social_logins p.submit').hide();
              $('#go-back-button').hide();
    loginpress_hide_provider_setting();
    if ($('#loginpress-provider-management').length) {
      $('#loginpress-provider-management').addClass('loginpress-social-login');
    
    $('#provider-cards-container').sortable('enable');
    }
    
  let providersOrder = loginpressData?.socialLoginOption?.provider_order || '["facebook", "twitter", "gplus", "linkedin", "microsoft", "apple", "discord", "wordpress", "github", "amazon", "pinterest", "disqus", "reddit", "spotify", "twitch"]';
   
  // Parse the JSON string into an array
  if ( ! Array.isArray(providersOrder) ) {
    providersOrder = JSON.parse(providersOrder);
  }
  
  const providers = window.loginpressProviders;

    // Select main container and initialize
    const socialLoginContainer = $('#loginpress_social_logins');
    if (!socialLoginContainer.length) {
      console.error('#loginpress_social_logins not found.');
      return;
    }

    // Create new inner div for provider management
    if (!$('#loginpress-provider-management').length) {
      socialLoginContainer.find('.form-table tbody').prepend(`
      <tr>
      <td colspan="2">
      <span id="go-back-button" class="go-back-button">
              <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M7.84626 0.250834C7.50119 -0.0836112 6.9419 -0.0836112 6.59682 0.250834L0.260737 6.39178C0.239204 6.41265 0.218776 6.43459 0.199452 6.4576C0.193379 6.46455 0.18841 6.47204 0.182336 6.47954C0.170742 6.49452 0.159147 6.50897 0.148105 6.52449C0.140375 6.53572 0.13375 6.5475 0.126573 6.55873C0.118843 6.57104 0.111113 6.58335 0.103936 6.59619C0.0967581 6.60903 0.0906849 6.62188 0.0846117 6.63525C0.0785384 6.64756 0.0724651 6.65933 0.066944 6.67164C0.0608707 6.68555 0.0564538 6.69947 0.0514848 6.71338C0.0470678 6.72569 0.0420989 6.738 0.0382341 6.7503C0.033265 6.76582 0.0299523 6.78134 0.0260875 6.79632C0.0233269 6.80809 0.020014 6.81933 0.0172534 6.83111C0.0128365 6.85144 0.010076 6.87177 0.00786752 6.89211C0.00676329 6.8996 0.00565919 6.90656 0.00455496 6.91405C-0.00151832 6.9713 -0.00151832 7.0291 0.00455496 7.08635C0.00510708 7.08956 0.00565912 7.09277 0.00621124 7.09598C0.00897182 7.1206 0.0128366 7.14522 0.0178056 7.1693C0.019462 7.17625 0.0216704 7.18321 0.0233267 7.19017C0.0282958 7.2105 0.0332649 7.23083 0.0393381 7.25063C0.0415466 7.25759 0.0443071 7.26455 0.0470677 7.27204C0.0536931 7.2913 0.0608707 7.3111 0.0691525 7.33036C0.071913 7.33625 0.0746737 7.3416 0.0774343 7.34749C0.0868203 7.36729 0.0962062 7.38709 0.106696 7.40635C0.108905 7.4101 0.111665 7.41384 0.113874 7.41759C0.125468 7.43792 0.138167 7.45826 0.151418 7.47806C0.152522 7.47966 0.153626 7.48127 0.154731 7.48287C0.185097 7.52622 0.21988 7.56742 0.259081 7.60541L6.59793 13.749C6.77019 13.916 6.99656 14 7.22237 14C7.44819 14 7.67455 13.9165 7.84682 13.749C8.19189 13.4146 8.19189 12.8725 7.84682 12.5381L3.0158 7.85584H19.1166C19.6047 7.85584 20 7.4727 20 6.99967C20 6.52663 19.6047 6.14349 19.1166 6.14349H3.0158L7.84626 1.46126C8.19134 1.12735 8.19134 0.585279 7.84626 0.250834Z" fill="#2B3D54"/>
  </svg> ${loginpressProviderData.ChooseProvider}</span>

      <div class="provider">
        
      </div>
        <div id="loginpress-provider-management">
          <div id="provider-cards-container" class="loginpress-social-login loginpress-provider-container" style="display: flex;"></div>
          <div id="provider-settings-container" style="display: none;">
            
            <div id="provider-settings-content">
              <h2 id="provider-name"></h2>
              <div id="provider-specific-settings"></div>
            </div>
          </div>
        </div>
      </td>
    </tr>
    <tr class="provider-logo-block">
      <td colspan="2"><div class="provider-logo-top"><img src="${loginPressGlobal.socialDirPath}/assets/img/Facebook.svg" alt="Facebook"></div></td>
    </tr>
      `);
    }
    $('.loginpress-copy-btn').on('click', function(){
    var target = $(this).attr('data-target');
    var uri = $('#'+target).text();
    const textToCopy = uri;

    // Create a temporary input element
    const tempInput = document.createElement('input');
    tempInput.value = textToCopy;
    document.body.appendChild(tempInput);

    // Select and copy the text
    tempInput.select();
    document.execCommand('copy');
    $(this).attr('data-tooltip', 'Copied');
    var el = $(this);
    setTimeout(function(){
      el.attr('data-tooltip', 'Copy')
    }, 1000)
    tempInput.remove()
    });
    $('.loginpress_info td').attr('colspan', '2')

    const cardsContainer = $('#provider-cards-container');
    const settingsContainer = $('#provider-settings-container');
    const backButton = $('#go-back-button');
    const verifyButton = $('.loginpress-verify-provider');
    backButton.hide();
    // Populate provider cards once
    if (!cardsContainer.children().length) {
      providersOrder.forEach(providerKey => {
          const provider = window.loginpressProviders.find(p => p.class === providerKey);
      const loginpressData = window.loginpressData.socialLoginOption;
      const status = loginpressData[providerKey + '_status'];
  let providerStatusClass = '';
  let providerStatusText = '';

          if (provider) {
			// Determine status class and text
			if (status === 'verified' && provider.status === 'on') {
			  providerStatusClass = 'enabled'; // Green
			  providerStatusText = loginpressProviderData.enabled;
			} else if (status !== 'verified' && provider.status === 'on') {
			  providerStatusClass = 'not-verified-button'; // Red
			  providerStatusText = loginpressProviderData.notVerified;
			} else if (provider.status === 'off') {
			  providerStatusClass = 'off'; // Grey
			  providerStatusText = loginpressProviderData.disabled;
			} else if (status === 'yet-to-verify' && provider.status === 'on') {
			  providerStatusClass = 'yet-to-verify-bitton'; // Yellow background for legacy social logins
			  providerStatusText = loginpressProviderData.yetToVerify;
			}
              const card = `
                  <div class="loginpress-provider-card ${provider.soon ? 'loginpress-provider-card-static': ''}" data-provider="${provider.class}">
				  ${!provider.soon ? '<span class="dashicons dashicons-screenoptions"></span>': ''}
                      <div class="loginpress-provider-head">${provider.logo}</div>
                      <div class="loginpress-provider-foot">
                          ${!provider.soon ? `<span class="loginpress-provider-status ${providerStatusClass}">
                ${providerStatusText}
              </span>` : ''}
                          ${!provider.soon ? `<span class="loginpress-provider-button">${loginpressProviderData.configure}</span>` : '<span class="loginpress-provider-comingsoon">Coming Soon</span>'}
                      </div>
                  </div>`;
              cardsContainer.append(card);
          }
      });
      cardsContainer.append($('<div class="loginpress-provider-card loginpress-provider-card-static">' +
        '<div class="loginpress-provider-head">' +
            '<h3>' + loginpressProviderData.lookingproviderheading + '</h3>' +
        '</div>' +
        '<div class="loginpress-provider-foot">' +
            '<p>' + loginpressProviderData.lookingprovidercontent + ' <br>' + loginpressProviderData.lookingproviderbr + '</p>' +
            '<a class="btn" href="https://loginpress.pro/contact/?utm_source=loginpress-pro&utm_medium=social-login-settings&utm_campaign=custom-request" target="_blank">' + loginpressProviderData.ContactUs + '</a>' +
        '</div>' +
    '</div>'));
  }
  $('#loginpress_social_logins input[type="checkbox"][id*="wpb-loginpress_social_logins"]').on("change", function(){
    var is_check = $(this).is(':checked');
    is_check ? $(this).closest('tr').prev('tr:not(.provider-logo-block)').show() : $(this).closest('tr').prev('tr:not(.provider-logo-block)').hide();
  });
    // Event: Clicking a card shows settings
    $('.loginpress-provider-button').on('click', function () {
      backButton.show();
      // verifyButton.show();
      const providerClass = $(this).closest('.loginpress-provider-card').data('provider');
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.set('provider', providerClass);
      const newUrl = window.location.pathname + '?' + urlParams.toString();
      history.pushState(null, '', newUrl);
      loginPressUpdateHttpReferer(providerClass);

    var logoName = jQuery.camelCase(providerClass);
    if(socialLoginOption[logoName] == "on"){
      $('.'+providerClass+'_status').show();
    }else{
      $('.'+providerClass+'_status').hide();
    }
    if(logoName == 'gplus'){
      logoName = 'google';
    }
    $('.'+providerClass).show();
    $('.provider-logo-block').show();
    $(".provider-logo-top").html(`<img src="${loginPressGlobal.socialDirPath}/assets/img/${logoName}.svg" alt="${logoName}">`);

      const providerName = $(this).closest('.loginpress-provider-card').find('h4').text();
      providers.forEach(provider => {
        if(providerClass == provider.class){
          $('.loginpress_info td').html(`<h4 class="loginpress-provider-logo"> ${loginpressProviderData.HelpCenter} </h4> ${provider.description}`);
        }
      });
      verifyButton.addClass(providerClass);
      // Switch visibility
      cardsContainer.hide();
      // settingsContainer.show();
      $('#loginpress_social_logins p.submit,.loginpress_info').show();

      // Show provider-specific rows
      // $(`.${providerClass}`).closest('tr').show();
      if($(this).closest('.loginpress-provider-card').find('.loginpress-provider-status.on').length > 0){
        $(`.${providerClass}`).nextAll(`[class^="${providerClass}"]`).closest('tr').show();
      }
      
      if(providerClass == 'apple'){
        loginpress_apple_settings();
      }
      else if(providerClass == 'facebook'){
        loginpress_facebook_settings();
      }
      else if(providerClass == 'twitter'){
        loginpress_twitter_settngs();
      }
      else if(providerClass == 'microsoft'){
        loginpress_microsoft_settings();
      }
      else if(providerClass == 'gplus'){
        loginpress_google_settings();
      }
      else if(providerClass == 'linkedin'){
        loginpress_linkedin_settings();
      }
      else if(providerClass == 'github'){
        loginpress_github_btn_settings();
      }
      else if(providerClass == 'discord'){
        loginpress_discord_btn_settings();
      }
      else if(providerClass == 'wordpress'){
        loginpress_wordpress_btn_settings();
      }
      else if(providerClass == 'amazon'){
        loginpress_amazon_btn_settings();
      }
      else if(providerClass == 'pinterest'){
        loginpress_pinterest_btn_settings();
      }
      // else if(providerClass == 'bluesky'){
      //   loginpress_bluesky_btn_settings();
      // }
      else if(providerClass == 'disqus'){
        loginpress_disqus_btn_settings();
      }
      else if(providerClass == 'reddit'){
        loginpress_reddit_btn_settings();
      }
      else if(providerClass == 'spotify'){
        loginpress_spotify_btn_settings();
      }
      else if(providerClass == 'twitch'){
        loginpress_twitch_btn_settings();
      }
    let isAnyFieldEmpty = true;
    if($('[class*="' + providerClass + '"]:not([class$="button_label"]) input[type="text"]:visible').length > 0){
      $('[class*="' + providerClass + '"]:not([class$="button_label"]) input[type="text"]:visible').each(function() {
        if (!$(this).val().trim()) {  // Check if the input value is empty or only whitespace
          isAnyFieldEmpty = false;
          return false;  // Break the loop once an empty field is found
        }
      });
    }else{
      isAnyFieldEmpty = false;
    }
    
    if (isAnyFieldEmpty) {
      $('[data-provider="'+providerClass+'_status"]').prop('disabled', false);
    } else {
      $('[data-provider="'+providerClass+'_status"]').prop('disabled', true);
    }

      verifyButton.on('click', function () {
        const classList = verifyButton.attr('class');
        const classArray = classList.split(/\s+/); // Split into an array of class names
        const provider = classArray[classArray.length - 1];
        const redirectUrl = `${window.location.origin}/?lpsl_login_id=${provider}_login&verification=1`;
    
        // Open a popup tab
        const newTab = window.open(redirectUrl, '_blank', 'width=600,height=600');
    
      
      window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) {
            console.warn('Origin mismatch:', event.origin);
            return;
        }

        const data = event.data;
        if (data === 'verified') {
            // Send AJAX request to update provider verification
          jQuery.ajax({
            url: ajaxurl,  
            type: 'POST',
            data: {
                action: 'loginpress_update_verification',
                provider: provider,
                security: loginpress_social_ajax.nonce 
            },
            success: function(response) {
              if (response.success) {
                location.reload(); // Reload the page on success
              }
            },
            error: function(error) {
                console.error('AJAX Error:', error);
            }
        });
        } 
    });
    });
    
    });

    // Event: Clicking back button
    backButton.on('click', function () {
      settingsContainer.hide();
      cardsContainer.show();
      verifyButton.attr('class', 'button button-primary loginpress-verify-provider');
      loginpress_hide_provider_setting();
      $('#loginpress_social_logins p.submit, .loginpress_info, .loginpress-apple-reconfigure').hide();
      backButton.hide();
    $('.provider-logo-block').hide();

      // Remove the 'provider' parameter from the URL without reloading
      const url = new URL(window.location);
      url.searchParams.delete('provider');
      history.replaceState(null, '', url.toString());

    });


  }
  var order;
  function sortIt() {
    $('#provider-cards-container').sortable({
      placeholder: "sortable-placeholder loginpress-provider-card",
      items: ".loginpress-provider-card:not(.loginpress-provider-card-static)",  // Prevent sorting for the last element
      start: function(event, ui) {
      ui.placeholder.height(ui.helper.outerHeight());  // Ensure the placeholder matches the dragged item's height
      },
      update: function(event, ui) {
		order = $(this).sortable('toArray', { attribute: 'data-provider' });
		// Filter out providers from loginpressProvidersExtra
		var orderString = JSON.stringify(order);
		$('#loginpress_social_logins [class$="provider_order"]').find('[type="hidden"]').val(orderString);
		  // AJAX request to save the order
		  $.ajax({
		url: ajaxurl,  // Ensure this variable is correctly defined
		type: 'POST',
		data: {
		action: 'loginpress_save_social_login_order',  // Action name for server-side handler
		loginpress_provider_order: order,  // Pass the filtered order array
		security: loginpress_social_ajax.nonce  // Nonce for security
		},
		success: function(response) {
		},
		error: function(xhr, status, error) {
		console.error('Error saving order:', error);
		}
		  });
      }
    });
    }
    
    // Initialize sortable functionality
    sortIt();
    
    
    $('#copy-shortcode-button').on('click', function(){
    copyShortCode();
    $(this).attr('data-tooltip', 'Copied');
    var el = $(this);
    setTimeout(function(){
      el.attr('data-tooltip', 'Copy');
    }, 1000);
    });
    function copyShortCode() {
      var copyText = document.getElementById("loginpress-shortcode");
      copyText.select();
      document.execCommand("Copy");
    }
  }
});
jQuery(window).on('load',function(){
  const urlParams = new URLSearchParams(window.location.search);
  const provider = urlParams.get('provider');

  if (provider) {
      // Wait for a slight delay to ensure elements are available
      setTimeout(function () {
          const targetButton = jQuery(`.loginpress-provider-card[data-provider="${provider}"] .loginpress-provider-button`);
          if (targetButton.length > 0) {
              targetButton.trigger('click');
          }
      }, 500); // Delay of 500ms to ensure elements are rendered
  }
})

// Function to update the _wp_http_referer hidden input
function loginPressUpdateHttpReferer(providerClass) {
  const refererInput = jQuery('input[name="_wp_http_referer"]');
  if (refererInput.length > 0) {
      let refererValue = refererInput.val(); // Current value
      const url = new URL(window.location.origin + refererValue);

      // Update the provider parameter
      if (providerClass) {
          url.searchParams.set('provider', providerClass);
      } else {
          url.searchParams.delete('provider');
      }

      // Update the hidden field's value
      refererInput.val(url.pathname + url.search);
  }
}
