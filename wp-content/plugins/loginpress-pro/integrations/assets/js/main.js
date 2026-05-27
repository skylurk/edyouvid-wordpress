jQuery(document).ready(function($) {
	
    $('#integration-go-back-button').hide();
    
    const integrations = [
        { name: 'WooCommerce', class: 'woocommerce', description: loginpress_integration_data.plugins.woocommerce.description, logo: 'woocommerce.svg', target: '.enable_captcha_woo, .enable_social_woo_lf, .enable_social_woo_rf, .enable_social_woo_co', link: 'https://loginpress.pro/doc/loginpress-woocommerce-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=woocommerce' },
        { name: 'Easy Digital Downloads', class: 'edd', description: loginpress_integration_data.plugins.edd.description, logo: 'edd.svg', target: '.enable_captcha_edd, .enable_social_edd_lf, .enable_social_edd_rf, .enable_social_edd_co', link: 'https://loginpress.pro/doc/loginpress-easy-digital-downloads-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=edd' },
        { name: 'BuddyPress', class: 'buddypress', description: loginpress_integration_data.plugins.buddypress.description, logo: 'buddypress.svg', target: '.enable_captcha_bp, .enable_social_login_links_bp', link: 'https://loginpress.pro/doc/loginpress-buddypress-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=buddypress' },
        { name: 'BuddyBoss', class: 'buddyboss', description: loginpress_integration_data.plugins.buddyboss.description, logo: 'buddyboss.svg', target: '.enable_captcha_bb, .enable_social_login_links_bb', link: 'https://loginpress.pro/doc/loginpress-buddyboss-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=buddyboss' },
        { name: 'LifterLMS', class: 'lifterlms', description: loginpress_integration_data.plugins.lifterlms.description, logo: 'lifterlms.svg', target: '.enable_captcha_llms, .enable_social_llms_lf, .enable_social_llms_rf, .enable_social_llms_co', link: 'https://loginpress.pro/doc/loginpress-lifterlms-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=lifterlms' },
        { name: 'LearnDash', class: 'learndash', description: loginpress_integration_data.plugins.learndash.description, logo: 'learndash.svg', target: '.enable_captcha_ld, .enable_social_ld_lf, .enable_social_ld_rf, .enable_social_ld_qf', link: 'https://loginpress.pro/doc/loginpress-learndash-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=learndash' },
    ];

    // Select main container for integrations
    const integrationsContainer = $('#loginpress_integration_settings');
    if (!integrationsContainer.length) {
        return;
    }

    // Create inner div for integration management
    if (!$('#loginpress-integration-management').length) {
        integrationsContainer.find('.form-table tbody').prepend(
            `<tr>
                <td colspan="2" style="padding-inline: 0;">
                    <span id="integration-go-back-button" class="go-back-button">
                       <svg width="20" height="14" viewBox="0 0 20 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.84626 0.250834C7.50119 -0.0836112 6.9419 -0.0836112 6.59682 0.250834L0.260737 6.39178C0.239204 6.41265 0.218776 6.43459 0.199452 6.4576C0.193379 6.46455 0.18841 6.47204 0.182336 6.47954C0.170742 6.49452 0.159147 6.50897 0.148105 6.52449C0.140375 6.53572 0.13375 6.5475 0.126573 6.55873C0.118843 6.57104 0.111113 6.58335 0.103936 6.59619C0.0967581 6.60903 0.0906849 6.62188 0.0846117 6.63525C0.0785384 6.64756 0.0724651 6.65933 0.066944 6.67164C0.0608707 6.68555 0.0564538 6.69947 0.0514848 6.71338C0.0470678 6.72569 0.0420989 6.738 0.0382341 6.7503C0.033265 6.76582 0.0299523 6.78134 0.0260875 6.79632C0.0233269 6.80809 0.020014 6.81933 0.0172534 6.83111C0.0128365 6.85144 0.010076 6.87177 0.00786752 6.89211C0.00676329 6.8996 0.00565919 6.90656 0.00455496 6.91405C-0.00151832 6.9713 -0.00151832 7.0291 0.00455496 7.08635C0.00510708 7.08956 0.00565912 7.09277 0.00621124 7.09598C0.00897182 7.1206 0.0128366 7.14522 0.0178056 7.1693C0.019462 7.17625 0.0216704 7.18321 0.0233267 7.19017C0.0282958 7.2105 0.0332649 7.23083 0.0393381 7.25063C0.0415466 7.25759 0.0443071 7.26455 0.0470677 7.27204C0.0536931 7.2913 0.0608707 7.3111 0.0691525 7.33036C0.071913 7.33625 0.0746737 7.3416 0.0774343 7.34749C0.0868203 7.36729 0.0962062 7.38709 0.106696 7.40635C0.108905 7.4101 0.111665 7.41384 0.113874 7.41759C0.125468 7.43792 0.138167 7.45826 0.151418 7.47806C0.152522 7.47966 0.153626 7.48127 0.154731 7.48287C0.185097 7.52622 0.21988 7.56742 0.259081 7.60541L6.59793 13.749C6.77019 13.916 6.99656 14 7.22237 14C7.44819 14 7.67455 13.9165 7.84682 13.749C8.19189 13.4146 8.19189 12.8725 7.84682 12.5381L3.0158 7.85584H19.1166C19.6047 7.85584 20 7.4727 20 6.99967C20 6.52663 19.6047 6.14349 19.1166 6.14349H3.0158L7.84626 1.46126C8.19134 1.12735 8.19134 0.585279 7.84626 0.250834Z" fill="#2B3D54"/>
                        </svg> ${loginpress_integration_translations.back}</span>
					<div class="loginpress-integration-head-wrapper"></div>
                    <div id="loginpress-integration-management">
                        <div id="integration-cards-container" class="loginpress-integration-container" style="display: flex; flex-wrap: wrap;"></div>
                        <div id="integration-settings-container" style="display: none;">
                            <div id="integration-settings-content">
                                <h2 id="integration-name"></h2>
                                <div id="integration-specific-settings"></div>
                            </div>
							
                        </div>
                    </div>
                </td>
            </tr>`
        );
    }
	$(`<tr class="loginpress_info_integration"><td colspan="2"><h4 class="loginpress-provider-logo">${loginpress_integration_translations.helpCenter}</h4><p>${loginpress_integration_translations.followGuide} <span class="loginpress-provider-name">WooCommerce</span> Integration:<br><a href="https://loginpress.pro/docs/social-login-integration/woocommerce/" target="_blank">WooCommerce ${loginpress_integration_translations.integrationGuide}</a></p></td></tr>`).appendTo('#loginpress_integration_settings .form-table tbody');
    const integrationCardsContainer = $('#integration-cards-container');
    const integrationSettingsContainer = $('#integration-settings-container');
    const integrationBackButton = $('#integration-go-back-button');

    integrationBackButton.hide();

    // Populate integration cards
    if (!integrationCardsContainer.children().length) {
        integrations.forEach(integration => {
			const status = loginpress_integration_data.plugins[integration.class].status;
            const card = `
                <div class="loginpress-integration-card" data-integration="${integration.class}" data-target="${integration.target}" data-link="${integration.link}">
                    <div class="loginpress-integration-head">
                        <img src="${loginpress_script.plugin_url}/loginpress/img/${integration.logo}" alt="${integration.name}">
                    </div>
                    <div class="loginpress-integration-body">
                        <h3>${integration.name}</h3>
                        <p>${integration.description}</p>
                    </div>
                    <div class="loginpress-integration-foot">
					${
						status === 'active'
						  ? `<span class="loginpress-integration-button">${loginpress_integration_translations.configure}</span>`
						  : status === 'learn-more'
						  ? `<a href="https://loginpress.pro/doc/loginpress-${integration.class=='edd'?'easy-digital-downloads':integration.class}-integration/?utm_source=loginpress-pro&utm_medium=integrations&utm_campaign=user-guide&utm_content=${integration.class}" target="_blank" class="loginpress-integration-button">${loginpress_integration_translations.learnMore}<svg width="15" height="17" viewBox="0 0 15 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.875 5.36621L13.875 9.36621L9.875 13.3662" stroke="currentColor" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.875 9.36621L13.875 9.36621" stroke="currentColor" stroke-width="1.2" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg></a>`
						  : `<span class="message warning">${loginpress_integration_translations.messageFirst} ${integration.name !== 'Easy Digital Downloads' ? integration.name : 'EDD'} ${loginpress_integration_translations.messageLast}.</span>`
					  }
                    </div>
                </div>`;
            integrationCardsContainer.append(card);
        });
    }
	function loginPressUpdateHttpReferer(integrationClass) {
		const refererInput = jQuery('input[name="_wp_http_referer"]');
		if (refererInput.length > 0) {
			let refererValue = refererInput.val(); // Current value
			const url = new URL(window.location.origin + refererValue);
	  
			// Update the integration parameter
			if (integrationClass) {
				url.searchParams.set('integration', integrationClass);
			} else {
				url.searchParams.delete('integration');
			}
	  
			// Update the hidden field's value
			refererInput.val(url.pathname + url.search);
		}
	  }
    // Event: Clicking an integration card
    $('span.loginpress-integration-button').on('click', function () {
        integrationBackButton.show();
		$('.loginpress-provider-name').html($(this).closest('.loginpress-integration-card').find('h3').text());
		$('.loginpress_info_integration a').html('How to Integrate '+ $(this).closest('.loginpress-integration-card').find('h3').text() + ' with LoginPress Pro');
		$('.loginpress_info_integration a').attr('href', $(this).closest('.loginpress-integration-card').attr('data-link'));
        const integrationClass = $(this).closest('.loginpress-integration-card').data('integration');
		loginPressUpdateHttpReferer(integrationClass);
        $('#integration-settings-container').show();
        $('#integration-cards-container').hide();

        let integrationName = integrations.find(integration => integration.class === integrationClass)?.name || '';
        $('#integration-name').text(integrationName);
		$('.loginpress-integration-head-wrapper').html(`<img src="${loginpress_script.plugin_url}/loginpress/img/${integrationClass}.svg" alt="${integrationName}">`);
        // $('#integration-specific-settings').html(`<p>Settings for ${integrationName} will go here.</p>`);
        $('.enable_captcha_woo, .enable_social_woo, .enable_captcha_ld, .enable_social_ld, .enable_captcha_llms, .enable_social_login_links_lifterlms').hide();
        var target = $(this).closest('.loginpress-integration-card').data('target');
        $(target).show();
        $('#loginpress_integration_settings input[type="submit"]').show();
        const url = new URL(window.location.href);
        url.searchParams.set('integration', integrationClass);
        window.history.pushState({}, '', url);
		$('.loginpress_info_integration').show();
		if(integrationClass === 'woocommerce') {
			$('.enable_social_woo_lf:has(input[type="checkbox"]:checked) + .social_position_woo_lf').show();
			$('.enable_social_woo_rf:has(input[type="checkbox"]:checked) + .social_position_woo_rf').show();
			$('.enable_social_woo_co:has(input[type="checkbox"]:checked) + .social_position_woo_co').show();
		}else if(integrationClass === 'edd') {
			$('.enable_social_edd_lf:has(input[type="checkbox"]:checked) + .social_position_edd_lf').show();
			$('.enable_social_edd_rf:has(input[type="checkbox"]:checked) + .social_position_edd_rf').show();
			$('.enable_social_edd_co:has(input[type="checkbox"]:checked) + .social_position_edd_co').show();
		}else if(integrationClass === 'buddypress') {
			$('.enable_social_login_links_bp:has(input[type="checkbox"]:checked) + .social_position_bp').show();
		}else if(integrationClass === 'buddyboss') {
			$('.enable_social_login_links_bb:has(input[type="checkbox"]:checked) + .social_position_bb').show();
		}else if(integrationClass === 'lifterlms') {
			$('.enable_social_llms_lf:has(input[type="checkbox"]:checked) + .social_position_llms_lf').show();
			$('.enable_social_llms_rf:has(input[type="checkbox"]:checked) + .social_position_llms_rf').show();
			$('.enable_social_llms_co:has(input[type="checkbox"]:checked) + .social_position_llms_co').show();
		}else if(integrationClass === 'learndash') {
			$('.enable_social_ld_lf:has(input[type="checkbox"]:checked) + .social_position_ld_lf').show();
			$('.enable_social_ld_rf:has(input[type="checkbox"]:checked) + .social_position_ld_rf').show();
			$('.enable_social_ld_qf:has(input[type="checkbox"]:checked) + .social_position_ld_qf').show();
		}
    });
	

    // Check URL for integration parameter on page load
    const urlParams = new URLSearchParams(window.location.search);
    const integrationParam = urlParams.get('integration');
    if (integrationParam) {
        const integrationCard = $(`.loginpress-integration-card[data-integration="${integrationParam}"]`);
        if (integrationCard.length) {
            integrationCard.find('span.loginpress-integration-button').trigger('click');
        }
    }

    // Event: Clicking back button
    integrationBackButton.on('click', function () {
		$('.loginpress-integration-head-wrapper').html('');
        $('#integration-settings-container').hide();
        $('#integration-cards-container').show();
        $('.enable_captcha_woo, .enable_social_woo_lf, .social_position_woo_lf, .enable_social_woo_rf, .social_position_woo_rf, .enable_social_woo_co, .social_position_woo_co, .enable_captcha_ld, .enable_social_ld_lf, .social_position_ld_lf, .enable_social_ld_rf, .social_position_ld_rf, .enable_social_ld_qf, .social_position_ld_qf, .enable_captcha_llms, .enable_social_llms_lf, .social_position_llms_lf, .enable_social_llms_rf, .social_position_llms_rf, .enable_social_llms_co, .social_position_llms_co, .enable_captcha_bp, .enable_social_login_links_bp, .social_position_bp, .enable_captcha_bb, .enable_social_login_links_bb, .social_position_bb, .enable_captcha_edd, .enable_social_edd_lf, .social_position_edd_lf, .enable_social_edd_rf, .social_position_edd_rf, .enable_social_edd_co, .social_position_edd_co').hide();
        integrationBackButton.hide();
        $('#loginpress_integration_settings input[type="submit"]').hide();
        const url = new URL(window.location.href);
        url.searchParams.delete('integration');
        window.history.pushState({}, '', url);
		$('.loginpress_info_integration').hide();
    });

});
(function($){

    $(function(){
        if (loginpressProData.activeAddons && loginpressProData.activeAddons.includes('login-redirects')) {
            redirect_group = $('#loginpress_login_redirect_learndash_groups').DataTable({
                "dom": 'fl<"loginpress_table_wrapper"t>ip',
                "fnDrawCallback": function(oSettings) {
                    if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
                        $(oSettings.nTableWrapper).find('.dataTables_paginate').hide();
                    } else {
                        $(oSettings.nTableWrapper).find('.dataTables_paginate').show();
                    }
                },
                "oLanguage": {
                    "sSearch": loginpress_redirect_learndash.translate[1],
                },
                "initComplete": function(settings, json) {
                    $('.dataTables_filter input').attr('placeholder', loginpress_redirect_learndash.translate[2]);
                }
            });
            $('[href="#loginpress_login_redirect_learndash_groups"]').on('click', function () {
                setTimeout(function(){
                    redirect_group.draw();
                }, 400);
            });

            $('#loginpress_redirect_role_search').hide();
            $('#loginpress_login_redirect_learndash_groups_wrapper').hide();
            $('#loginpress_redirect_learndash_group_search').hide();
            $('.loginpress-redirects-tab').on( 'click', function(event) {

                event.preventDefault();

                var target = $(this).attr('href');
                $(target).show().siblings('table').hide();
                $(this).addClass('loginpress-redirects-active').siblings().removeClass('loginpress-redirects-active');

                if( target == '#loginpress_login_redirect_users' ) {
                    $('.row-per-page').show();
                    $('#loginpress_redirect_learndash_group_search').hide();
                    $('#loginpress_login_redirect_learndash_groups_wrapper').hide();
                }
        
                if( target == '#loginpress_login_redirect_roles' ) {
                    $('.row-per-page').show();
                    $('#loginpress_redirect_learndash_group_search').hide();
                    $('#loginpress_login_redirect_learndash_groups_wrapper').hide();
                }
        
                if( target == '#loginpress_login_redirect_learndash_groups' ) {
                    $('#loginpress_redirect_learndash_group_search').show();
                    $('#loginpress_redirect_role_search').hide();
                    $('#loginpress_redirect_user_search').hide();
                    $('.row-per-page').hide();
                    $('[for="loginpress_login_redirects[login_redirects]"]').html(loginpress_redirect_sociallogins.translate[0]);
                    $('.login_redirects .description').html(loginpress_redirect_sociallogins.translate[1]);
                    $('#loginpress_login_redirect_users_wrapper').hide();
                    $('#loginpress_login_redirect_roles_wrapper').hide();
                    $('#loginpress_login_redirect_learndash_groups_wrapper').show();
                }
            });
            

            // Apply ajax on clicking update button of group table row.
            $(document).on( "click", ".loginpress-redirects-group-update", function(event) {

                event.preventDefault();

                var el      = $(this);
                var tr      = el.closest('tr');
                var group    = tr.find( '.group-name-value' ).text();
                var value    = tr.attr( "data-login-redirects-group" );
                var logout  = tr.find( '.loginpress_logout_redirects_url input[type=text]').val();
                var login   = tr.find( '.loginpress_login_redirects_url input[type=text]' ).val();
                var _nonce  = loginpress_redirect_sociallogins.group_nonce;

                $.ajax({

                    url : ajaxurl,
                    type: 'POST',
                    data: {
                        action  : 'loginpress_login_redirects_group_update',
                        security: _nonce,
                        login   : login,
                        logout  : logout,
                        group    : group,
                        priority : 10,
                        value : value,
                    },
                    beforeSend: function() {
                        tr.find( '.login-redirects-sniper' ).show();
                        tr.find( '.loginpress-redirects-group-update' ).attr( "disabled", "disabled" );
                    },
                    success: function( response ) {
                        tr.find( '.login-redirects-sniper' ).hide();
                        tr.find( '.loginpress-redirects-group-update' ).removeAttr( "disabled" );
                        tr.find( '.loginpress_login_redirect_learndash_groups' ).html(response);
                    }
                }); // !Ajax.
            });

            // Apply ajax on click delete button for group table row.
            $(document).on( "click", ".loginpress-redirects-group-delete", function(event) {

                event.preventDefault();

                var el     = $(this);
                var tr     = el.closest('tr');
                var group   = tr.attr( "data-login-redirects-group" );
                var _nonce = loginpress_redirect_sociallogins.group_nonce;

                $.ajax({

                    url : ajaxurl,
                    type: 'POST',
                    data: {
                        action  : 'loginpress_login_redirects_group_delete',
                        security: _nonce,
                        group    : group,
                    },
                    beforeSend: function() {
                        tr.find( '.loginpress_login_redirect_learndash_groups' ).html('');
                        tr.find( '.login-redirects-sniper' ).show();
                        tr.find( '.loginpress-redirects-group-update' ).attr( "disabled", "disabled" );
                        tr.find( '.loginpress-redirects-group-delete' ).attr( "disabled", "disabled" );
                    },
                    success: function( response ) {
                        redirect_group.row( '#loginpress_redirects_group_' + group ).remove().draw();
                    }
                });
            });
        }
		var list = [
			{ key: 'default', value: 'below-with-seprator' },
			{ key: 'below', value: 'below' },
			{ key: 'above', value: 'above' },
			{ key: 'above_separator', value: 'above-with-separtor' },
		];
		$('[for*="wpb-loginpress_integration_settings"]').on('change', 'input[type="radio"]', function () {
			const checkbox = $(this);
			const isChecked = checkbox.is(':checked');
			const key = checkbox.val(); // Assuming the checkbox has a data-key attribute
			const listItem = list.find(item => item.key === key);

			if (listItem) {
				const filePath = '/wp-content/plugins/loginpress-pro/addons/social-login/assets/img/';
				const fileName = isChecked ? listItem.value : 'default';
				const fileUrl = `${filePath}${fileName}.svg`;

				checkbox.closest('tr').find('img').attr('src', fileUrl);
			}
		});
        $('[for*="wpb-loginpress_integration_settings"]').on('mouseleave', function () {
			$('[for*="wpb-loginpress_integration_settings"]').find('input[type="radio"]:checked').each(function () {
				const checkbox = $(this);
				const key = checkbox.val(); // Assuming the checkbox has a data-key attribute
				const listItem = list.find(item => item.key === key);

				if (listItem) {
					const filePath = '/wp-content/plugins/loginpress-pro/addons/social-login/assets/img/';
					let fileName = listItem.value;
					if(key === 'default') {
						fileName = 'below-with-seprator';
					}
					const fileUrl = `${filePath}${fileName}.svg`;
					checkbox.closest('tr').find('img').attr('src', fileUrl);
				}
			});
		}).on('mouseenter', function () {
			const checkbox = $(this).find('input[type="radio"]');
			const key = checkbox.val(); // Assuming the checkbox has a data-key attribute
			const listItem = list.find(item => item.key === key);
			if (listItem) {
				const filePath = '/wp-content/plugins/loginpress-pro/addons/social-login/assets/img/';
				const fileName = key === 'default' ? 'below-with-seprator' : listItem.value;
				const fileUrl = `${filePath}${fileName}.svg`;
				const imgElement = checkbox.closest('tr').find('img');
				imgElement.fadeOut(200, function () {
					setTimeout(function () {
						imgElement.attr('src', fileUrl).fadeIn(200);
					}, 100)
				});
			}
        });
		$('.enable_social_woo_lf input[type="checkbox"], .enable_social_woo_rf input[type="checkbox"], .enable_social_woo_co input[type="checkbox"],.enable_social_edd_lf input[type="checkbox"], .enable_social_edd_rf input[type="checkbox"], .enable_social_edd_co input[type="checkbox"],.enable_social_llms_lf input[type="checkbox"], .enable_social_llms_rf input[type="checkbox"], .enable_social_llms_co input[type="checkbox"],.enable_social_ld_lf input[type="checkbox"], .enable_social_ld_rf input[type="checkbox"], .enable_social_ld_qf input[type="checkbox"], .enable_social_login_links_bp input[type="checkbox"], .enable_social_login_links_bb input[type="checkbox"]').on('change', function () {
			const checkbox = $(this);
			const isChecked = checkbox.is(':checked');
			const target = checkbox.closest('tr').next('tr');
			if (isChecked) {
				target.show();
			} else {
				target.hide();
			}
		});

    
    });


    
})(jQuery);