(function ($) {

	$(
		function () {
			const activeAddons = loginpressProData.activeAddons;

			// Example: Run JS only if 'social-login' addon is active
			if (activeAddons.includes( "login-redirects" )) {
				let isAjaxCalled = false;
				function loginpress_fetchData(){
					var _nonce = loginpress_redirect.user_nonce;
					if ( ! isAjaxCalled) {
						$.ajax(
							{
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'loginpress_login_redirect_script',
									security: _nonce,
								},
								success: function (response) {
									// $('#loginpress_login_redirects').append(response);
									isAjaxCalled = true;
									var rows     = $( response );

									// Iterate through each <tr> and separate users and roles
									rows.each(
										function () {
											var $row = $( this );

											// Check if it's a user redirect row
											if ($row.attr( 'data-login-redirects-user' )) {
												// Add row to redirect_users DataTable
												redirect_users.row.add( $( this ).get( 0 ) ); // `draw(false)` prevents the entire table from redrawing multiple times
											}

											// Check if it's a role redirect row
											if ($row.attr( 'data-login-redirects-role' )) {
												// Add row to redirect_roles DataTable
												redirect_roles.row.add( $( this ).get( 0 ) );
											}
										}
									);
									redirect_users.draw();
									redirect_roles.draw();

									// loginpress_draw_users();
									// loginpress_draw_roles();
								},
								error: function (xhr, status, error) {
									console.error( 'AJAX Error:', error );
								}
							}
						);
					}
				}
				function loginpress_draw_users(){
					redirect_users = $( '#loginpress_login_redirect_users' ).DataTable(
						{
							"dom": 'fl<"loginpress_table_wrapper"t>ip',
							// autoWidth: false,
							// responsive: true,
							"fnDrawCallback": function (oSettings) {
								if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).hide();
								} else {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).show();
								}
							},
							"oLanguage": {
								"sSearch": loginpress_redirect.translate[4],
							}
						}
					);
				}
				const intervalId = setInterval(
					function () {
						if ($( '#loginpress_login_redirects' ).is( ':visible' )) {
							loginpress_fetchData();
							clearInterval( intervalId );  // Stop further calls
						}
					},
					500
				);
				function loginpress_draw_roles(){
					redirect_roles = $( '#loginpress_login_redirect_roles' ).DataTable(
						{
							"dom": 'fl<"loginpress_table_wrapper"t>ip',
							"columnDefs": [ {
								"targets": 0,
								"orderable": false
							} ],
							"fnDrawCallback": function (oSettings) {
								if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).hide();
								} else {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).show();
								}
							},
							"oLanguage": {
								"sSearch": loginpress_redirect.translate[4],
							},
							"initComplete": function (settings, json) {
								$( '.dataTables_filter input' ).attr( 'placeholder', loginpress_redirect.translate[5] );
							}
						}
					);
				}

				loginpress_draw_users();
							loginpress_draw_roles();

				$( '[href="#loginpress_login_redirect_roles"]' ).on(
					'click',
					function () {
						setTimeout(
							function () {
								redirect_roles.draw();
							},
							400
						);
					}
				);
				$( '#loginpress_login_redirect_roles_wrapper' ).hide();

				// loginpress redirects tabs.
				$( '.loginpress-redirects-tab' ).on(
					'click',
					function (event) {

						event.preventDefault();

						var target = $( this ).attr( 'href' );
						$( target ).show().siblings( 'table' ).hide();
						$( this ).addClass( 'loginpress-redirects-active' ).siblings().removeClass( 'loginpress-redirects-active' );

						if ( target == '#loginpress_login_redirect_users' ) {
							$( '#loginpress_redirect_user_search' ).show();
							$( '#loginpress_redirect_role_search' ).hide();
							$( '[for="loginpress_login_redirects[login_redirects]"]' ).html( loginpress_redirect.translate[0] );
							$( '.login_redirects .description' ).html( loginpress_redirect.translate[1] );
							$( '#loginpress_login_redirect_roles_wrapper' ).hide();
							$( '#loginpress_login_redirect_users_wrapper' ).show();
						}

						if ( target == '#loginpress_login_redirect_roles' ) {
							$( '#loginpress_redirect_role_search' ).show();
							$( '#loginpress_redirect_user_search' ).hide();
							$( '[for="loginpress_login_redirects[login_redirects]"]' ).html( loginpress_redirect.translate[2] );
							$( '.login_redirects .description' ).html( loginpress_redirect.translate[3] );
							$( '#loginpress_login_redirect_users_wrapper' ).hide();
							$( '#loginpress_login_redirect_roles_wrapper' ).show();
						}
					}
				);

				// Apply ajax on click new button.
				$( document ).on(
					"click",
					".loginpress-user-redirects-update",
					function (event) {

						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var id     = tr.attr( "data-login-redirects-user" );
						var logout = tr.find( '.loginpress_logout_redirects_url input[type=text]' ).val();
						var login  = tr.find( '.loginpress_login_redirects_url input[type=text]' ).val();
						var _nonce = loginpress_redirect.user_nonce;

						$.ajax(
							{
								url : ajaxurl,
								type: 'POST',
								data: {
									action  : 'loginpress_login_redirects_update',
									security: _nonce,
									login   : login,
									logout  : logout,
									id      : id
								},
								beforeSend: function () {
									tr.find( '.login-redirects-sniper' ).show();
									tr.find( '.loginpress-user-redirects-update' ).attr( "disabled", "disabled" );
								},
								success: function ( response ) {
									tr.find( '.login-redirects-sniper' ).hide();
									tr.find( '.loginpress-user-redirects-update' ).removeAttr( "disabled" );
									tr.find( '.loginpress_login_redirect_users' ).html( response );
								}
							}
						); // !Ajax.
					}
				); // !click .loginpress-user-redirects-update.

				// Apply ajax on click delete button.
				$( document ).on(
					"click",
					".loginpress-user-redirects-delete",
					function (event) {

						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var id     = tr.attr( "data-login-redirects-user" );
						var _nonce = loginpress_redirect.user_nonce;

						$.ajax(
							{

								url : ajaxurl,
								type: 'POST',
								data: {
									action  : 'loginpress_login_redirects_delete',
									security: _nonce,
									id      : id
								},
								beforeSend: function () {
									tr.find( '.loginpress_login_redirect_users' ).html( '' );
									tr.find( '.login-redirects-sniper' ).show();
									tr.find( '.loginpress-user-redirects-update' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-user-redirects-delete' ).attr( "disabled", "disabled" );
								},
								success: function ( response ) {
									// $( '#loginpress_redirects_user_id_' + id ).remove();
									redirect_users.row( '#loginpress_redirects_user_id_' + id ).remove().draw();
								}
							}
						); // !Ajax.
					}
				); // !click .loginpress-user-redirects-delete.

				// Apply ajax on click new button.
				$( document ).on(
					"click",
					".loginpress-redirects-role-update",
					function (event) {

						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var role   = tr.attr( "data-login-redirects-role" );
						var logout = tr.find( '.loginpress_logout_redirects_url input[type=text]' ).val();
						var login  = tr.find( '.loginpress_login_redirects_url input[type=text]' ).val();
						var _nonce = loginpress_redirect.role_nonce;

						$.ajax(
							{

								url : ajaxurl,
								type: 'POST',
								data: {
									action  : 'loginpress_login_redirects_role_update',
									security: _nonce,
									login   : login,
									logout  : logout,
									role    : role,
								},
								beforeSend: function () {
									tr.find( '.login-redirects-sniper' ).show();
									tr.find( '.loginpress-redirects-role-update' ).attr( "disabled", "disabled" );
								},
								success: function ( response ) {
									tr.find( '.login-redirects-sniper' ).hide();
									tr.find( '.loginpress-redirects-role-update' ).removeAttr( "disabled" );
									tr.find( '.loginpress_login_redirect_roles' ).html( response );
								}
							}
						); // !Ajax.
					}
				); // !click .loginpress-redirects-role-update.

				// Apply ajax on click delete button.
				$( document ).on(
					"click",
					".loginpress-redirects-role-delete",
					function (event) {

						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var role   = tr.attr( "data-login-redirects-role" );
						var _nonce = loginpress_redirect.role_nonce;

						$.ajax(
							{

								url : ajaxurl,
								type: 'POST',
								data: {
									action  : 'loginpress_login_redirects_role_delete',
									security: _nonce,
									role    : role,
								},
								beforeSend: function () {
									tr.find( '.loginpress_login_redirect_roles' ).html( '' );
									tr.find( '.login-redirects-sniper' ).show();
									tr.find( '.loginpress-redirects-role-update' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-redirects-role-delete' ).attr( "disabled", "disabled" );
								},
								success: function ( response ) {
									redirect_roles.row( '#loginpress_redirects_role_' + role ).remove().draw();
								}
							}
						); // !Ajax.
					}
				); // !click .loginpress-redirects-role-delete.
			}

		}
	);
})( jQuery ); // This invokes the function above and allows us to use '$' in place of 'jQuery' in our code.
