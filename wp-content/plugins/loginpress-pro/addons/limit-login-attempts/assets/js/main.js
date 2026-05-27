(function ($) {

	'use strict';

	$(
		function () {
			const activeAddons = loginpressProData.activeAddons;

			// Example: Run JS only if 'social-login' addon is active
			if (activeAddons.includes( "limit-login-attempts" )) {
				var loaded = false;
				var _nonce = loginpress_llla.user_nonce;
				// Tracks if the AJAX calls have been made to avoid redundant requests.
				let isAjaxCalled = false;

				// Flags to ensure certain drawing logic happens only once.
				let drawOnce      = true;
				var drawOnceWhite = true;
				var drawOnceBlack = true;
				let bulk_black    = false;
				let bulk_white    = false;

				/**
				 * Function to fetch data via multiple AJAX calls.
				 * It makes three separate AJAX requests to retrieve logs,
				 * blacklist data, and whitelist data, appending the responses
				 * to a specific HTML element.
				 */
				function loginpress_fetchData_limitlogs() {

					// Check if the AJAX requests have already been made.
					if ( ! isAjaxCalled) {
						// First AJAX call to fetch login attempt logs.
						$.ajax(
							{
								url: ajaxurl, // URL for handling AJAX requests in WordPress.
								type: 'POST', // HTTP method to send the request.
								data: {
									action: 'loginpress_limit_login_attempts_log_script', // Action for the log data.
									security: _nonce // Nonce for security validation.
								},
								success: function (response) {
									// Append the response to the target element.
									// $('#loginpress_limit_logs').append(response);
									// $('#loginpress_loader').remove(); // Remove loader on success
									// loginpress_draw_limit();

									var rows = $( response );

									rows.each(
										function () {
											limitTable.row.add( $( this ).get( 0 ) );
										}
									);

									// Redraw the table to reflect the changes
									limitTable.draw();

									isAjaxCalled = true;
								},
								error: function (xhr, status, error) {
									console.error( 'AJAX Error:', error ); // Log any errors in the console.
								}
							}
						);
					}
				}

				function loginpress_fetchData_whitelist(){
					// Third AJAX call to fetch whitelist data.
					return $.ajax(
						{
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'loginpress_limit_login_attempts_whitelist_script', // Action for whitelist.
								security: _nonce
							},
							success: function (response) {
								// $('#loginpress_limit_login_whitelist_wrapper2').append(response); // Append whitelist data.
								// $('#loginpress_loader').remove(); // Remove loader on success
								// loginpress_white_table_draw();

								var rows = $( response );
								rows.each(
									function () {
										whiteTable.row.add( $( this ).get( 0 ) );
									}
								);

								// Redraw the table to reflect the changes
								whiteTable.draw();

								if ( ! bulk_white) {
									$( '[href="#loginpress_limit_login_whitelist"]' ).trigger( 'click' );
								}
							},
							error: function (xhr, status, error) {
								console.error( 'AJAX Error:', error ); // Log errors in the console.
							}
						}
					);
				}

				function loginpress_fetchData_blacklist(){
					// Second AJAX call to fetch blacklist data.
					$.ajax(
						{
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'loginpress_limit_login_attempts_blacklist_script', // Action for blacklist.
								security: _nonce
							},
							success: function (response) {
								// $('#loginpress_limit_login_blacklist_wrapper2').append(response); // Append blacklist data.
								// $('#loginpress_loader').remove(); // Remove loader on success
								// loginpress_black_table_draw();

								var rows = $( response );
								rows.each(
									function () {
										blackTable.row.add( $( this ).get( 0 ) );
									}
								);

								// Redraw the table to reflect the changes
								blackTable.draw();

								if ( ! bulk_black) {
									$( '[href="#loginpress_limit_login_blacklist"]' ).trigger( 'click' );
								}
							},
							error: function (xhr, status, error) {
								console.error( 'AJAX Error:', error ); // Log errors in the console.
							}
						}
					);
				}

				// const loginPressPopUpContainer = $('.llla_remove_all_popup');
				const hidePopUpWindow = function () {
					$( '.llla_remove_all_popup' ).fadeOut();
				}
				$( document ).on(
					'click',
					'.loginpress-edit-overlay',
					function () {
						hidePopUpWindow();
					}
				);
				var bulkIps           = [];
				var limitTable;
				var blackTable;
				var whiteTable;

				/**
				 * Initializes and configures the DataTable for displaying login attempt logs.
				 */
				function loginpress_draw_limit() {
					limitTable = $( '#loginpress_limit_login_log' ).DataTable(
						{
							dom: 'Bfl<"loginpress_table_wrapper"t>ip', // Added 'B' for buttons
							lengthMenu: [10, 25, 50, 75, 100],
							buttons: [
							{
								extend: 'csvHtml5',
								text: loginpress_llla.translate[11],
								className: 'button dt-buttons',
								action: function () {
									var _nonce           = loginpress_llla.csv_nonce;
									window.location.href = ajaxurl + '?action=loginpress_lla_export_csv&security=' + _nonce;
								},
								title: 'Login_Attempts',
								exportOptions: {
									columns: ':visible:not(:first-child)' // Exclude the checkbox column
								}
							}
							],
							initComplete: function () {
							},
							fnDrawCallback: function (oSettings) {
								if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).hide();
								} else {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).show();
								}
							},
							columnDefs: [
							{
								targets: 0,
								searchable: false,
								orderable: false,
								className: 'dt-body-center',
								render: function (data, type, full, meta) {
									return (
									'<div class="lp-tbody-cell"><input type="checkbox" name="id[]" class="llla_inside_check" value="' +
									$( '<div/>' ).text( data ).html() +
									'"></div>'
									);
								}
							}
							],
							order: [[1, 'asc']]
						}
					);

					limitTable.columns.adjust().draw();
					limitTable.buttons().container().insertBefore( $( '#loginpress_limit_logs .bulk_clear' ) );
				}
				loginpress_draw_limit();
				loginpress_black_table_draw();
				loginpress_white_table_draw();
				$( '[href="#loginpress_limit_logs"]' ).on(
					'click',
					function () {
						if (drawOnce) {
							loginpress_fetchData_limitlogs();
							drawOnce = false;
						}
					}
				);
				$( '[href="#loginpress_limit_login_whitelist"]' ).on(
					'click',
					function () {
						if (drawOnceWhite) {
							loginpress_fetchData_whitelist();
							drawOnceWhite = false;
						}
					}
				);
				$( '[href="#loginpress_limit_login_blacklist"]' ).on(
					'click',
					function () {
						if (drawOnceBlack) {
							loginpress_fetchData_blacklist();
							drawOnceBlack = false;
						}
					}
				);

				$( window ).on(
					'resize',
					function () {
						// limitTable.columns.adjust().responsive.recalc();
						// limitTable.column(0).visible( screen.width > 767);
						// limitTable.column(2).visible( screen.width > 766);
						// limitTable.column(3).visible( screen.width > 766);
						// limitTable.column(4).visible( screen.width > 766);
					}
				);

				function loginpress_black_table_draw(){
					blackTable = $( '#loginpress_limit_login_blacklist' ).DataTable(
						{
							"dom": 'fl<"loginpress_table_wrapper"t>ip',
							"fnDrawCallback": function (oSettings) {
								if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).hide();
								} else {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).show();
								}
							}
						}
					);
				}
				function loginpress_white_table_draw(){
					whiteTable = $( '#loginpress_limit_login_whitelist' ).DataTable(
						{
							"dom": 'fl<"loginpress_table_wrapper"t>ip',
							"fnDrawCallback": function (oSettings) {
								if (oSettings._iDisplayLength > oSettings.fnRecordsDisplay()) {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).hide();
								} else {
									$( oSettings.nTableWrapper ).find( '.dataTables_paginate' ).show();
								}
							}
						}
					);
				}

				// Handle click on "Select all" control
				$( document ).on(
					'click',
					'.lla-select-all',
					function () {
						// Get only rows on the current page
						var rows = limitTable.rows( { page: 'current' } ).nodes();

						// Check/uncheck checkboxes for all rows on the current page
						$( 'input[type="checkbox"]', rows ).prop( 'checked', this.checked );

						// Only check the .lla-select-all checkbox on the current page
						$( this ).prop( 'checked', this.checked );
					}
				);

				// Handle click on checkbox to set state of "Select all" control
				$( document ).on(
					'change',
					'#loginpress_limit_login_log tbody input[type="checkbox"]',
					function () {
						// If checkbox is not checked
						var el                = $( '.lla-select-all' ).get( 0 );
						var rows              = limitTable.rows( { page: 'current' } ).nodes();
						var totalCheckboxes   = $( 'input[type="checkbox"]', rows ).length;
						var checkedCheckboxes = $( 'input[type="checkbox"]:checked', rows ).length;

						if ( ! this.checked) {
							// If "Select All" is checked, set it to indeterminate
							if (el && el.checked && ('indeterminate' in el)) {
								el.indeterminate = true;
							}
						} else if (checkedCheckboxes === totalCheckboxes) {
							// If all checkboxes on the current page are checked, check "Select All"
							el.checked       = true;
							el.indeterminate = false;
						}
					}
				);

				/**
				 * Add `llla__bulk-action` class in the rows that has same IP addesses data.
			 *
				 * @return string
				 * @since 2.1.0
				 */
				if ( ! drawOnce) {
					limitTable.$( 'input[type="checkbox"]' ).on(
						'change',
						function () {
							$( this ).closest( 'tr' ).removeClass( 'llla__bulk-action' );
							if (this.checked) {
								bulkIps.push( $( this ).parent().parent().parent().data( 'ip' ) );
								limitTable.$( 'tr.llla__bulk-action' );
								var keyip = $( this ).closest( 'tr' ).attr( 'data-ip' );
								$( '[data-ip="' + keyip + '"]' ).addClass( 'llla__bulk-action' );
							}
						}
					);
				}

				/**
				 * Handle Bulk Action form submission event
			 *
				 * @return void
				 * @since 2.1.0
				 */
				$( document ).on(
					'click',
					'#loginpress_limit_bulk_blacklist_submit',
					function (e) {
						if ( ! whiteTable) {
							bulk_white = true;
							loginpress_fetchData_whitelist();
							drawOnceWhite = false;
						}
						if ( ! blackTable) {
							bulk_black = true;
							loginpress_fetchData_blacklist();
							drawOnceBlack = false;
						}
						const bulkAction = $( '#loginpress_limit_bulk_blacklist' ).val();
						const _nonce     = loginpress_llla.bulk_nonce;
						let $this        = $( this );

						// Iterate over all checkboxes in the table
						limitTable.$( 'input[type="checkbox"]' ).each(
							function () {
								if (this.checked) {
									bulkIps.push( $( this ).parent().parent().parent().data( 'ip' ) );
								}
							}
						);

						// Error Handling Check
						if ('' == bulkAction || '' == bulkIps) {
							if ($( '.llla-bulk-attempts' ).length < 1) {
								$( "#loginpress_limit_bulk_blacklist_submit" ).after( '<div id="no-items-selected" class="message error loginpress-llla-bulk-no-item llla-bulk-attempts"><span>' + loginpress_llla.translate[0] + '</span></div>' );
							}
							setTimeout(
								function () {
									$( 'div#no-items-selected' ).fadeOut();
									$( 'div#no-items-selected' ).remove();

								},
								3000
							);
							return;
						} else {
							$( '.loginpress-llla-bulk-no-item' ).hide();
						}
						// Send Ajax request
						$.ajax(
							{
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'loginpress_attempts_bulk',
									bulk_action: bulkAction,
									bulk_ips: bulkIps,
									security: _nonce,
								},
								beforeSend: function () {

									$( '<div class="loginpress_limit_login_log_message"> Updating .... </div>' ).appendTo( $( '#loginpress_limit_login_log_wrapper' ) );
									$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeIn();
								},
								success: function (response) {
									if ('white_list' == bulkAction || 'black_list' == bulkAction) {
										const updatedIps = Object.values( response.data.updated_ips );
										$( updatedIps ).each(
											function (index, ip) {
												let action   = bulkAction === 'white_list' ? 'whitelist' : 'blacklist';
												let listView = bulkAction.replace( '_list', '' );

												// Create row HTML using stored IP data
												var list_tr = ` < tr id = "loginpress_${listView}list_id_${index}" data - login - ${listView}list - user = "${index}" role = "row" class = "even" >
												< td class = "loginpress_limit_login_${action}_ips" data - ${action} - ip = "${ip}" >
													< div class       = "lp-tbody-cell" > ${ip} < / div >
												< / td >
												< td class            = "loginpress_limit_login_${action}_actions" >
													< div class       = "lp-tbody-cell" >
														< input class = "loginpress-${listView}list-clear button button-primary" type = "button" value = "${loginpress_llla.translate[1]}" >
													< / div >
												< / td >
												< / tr > `;
												var getNode           = $.parseHTML( list_tr );

												if ('white_list' == bulkAction) {
													if (whiteTable) {
														whiteTable.row.add( getNode[0] ).draw();
													} else {
														setTimeout( () => {whiteTable.row.add( getNode[0] ).draw();}, 500 );
													}
												} else if ('black_list' == bulkAction) {
													if (blackTable) {
														blackTable.row.add( getNode[0] ).draw();
													} else {
														setTimeout( () => {blackTable.row.add( getNode[0] ).draw();}, 500 );
													}
												}
											}
										);
									}
									bulkIps.forEach(
										function (ip) {
											limitTable.rows( '[data-ip="' + ip + '"]' ).remove();
										}
									);

									limitTable.draw();
									$( '.lla-select-all' ).prop( 'checked', false );
									bulkIps = [];
									$( '.loginpress_limit_login_log_message' ).remove();
									setTimeout(
										function () {
											$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeOut();
										},
										5000
									);

								}
							}
						); // !Ajax.

					}
				);

				$( document ).on(
					'click',
					'#loginpress_limit_bulk_attempts_submit',
					function (e) {
						bulkIps = [];

						$( '#loginpress_limit_login_log tbody' ).find( 'tr' ).each(
							function () {
								bulkIps.push( $( this ).data( 'ip' ) );
							}
						);

						if ( $( '#loginpress_limit_login_log tbody' ).find( 'tr .dataTables_empty' ).length ) {
							e.stopPropagation();
							e.preventDefault();
							if ($( '.llla-bulk-attempts' ).length < 1 ) {

								$( "#loginpress_limit_bulk_attempts_submit" ).after( '<div id="no-ip-found" class="message error loginpress-llla-bulk-no-item llla-attempts-log"><span>' + loginpress_llla.translate[10] + '</span></div>' );
							}
							setTimeout(
								function () {
									$( '.loginpress-llla-bulk-no-item' ).fadeOut();
									$( 'div.loginpress-llla-bulk-no-item' ).remove();

								},
								3000
							);
						} else {
							$( '.loginpress-edit-attempts-popup-containers' ).show();
						}
						bulkIps = [];

					}
				);

				$( document ).on(
					"click",
					".loginpress_confirm_remove_all_attempts",
					function (event) {

						const _nonce = loginpress_llla.bulk_nonce;

						$.ajax(
							{
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'loginpress_clear_all_attempts',
									security: _nonce,
								},
								beforeSend: function () {

									$( '<div class="loginpress_limit_login_log_message"> Updating .... </div>' ).appendTo( $( '#loginpress_limit_login_log' ) );
									$( '#loginpress_limit_login_log_message' ).fadeIn();

								},
								success: function (response) {

								}
							}
						);
						limitTable.clear().draw();
						$( '.loginpress-edit-attempts-popup-containers' ).hide();
						setTimeout(
							function () {
								$( '.loginpress_limit_login_log_message' ).fadeOut();
							},
							700
						);
						hidePopUpWindow();
						bulkIps = [];
					}
				);

				/**
				 * Handle Bulk Action form submission event
			 *
				 * @return void
				 * @since 2.1.0
				 */
				$( document ).on(
					'click',
					'#loginpress_limit_bulk_blacklists_submit',
					function (e) {
						bulkIps = [];

						$( '#loginpress_limit_login_blacklist' ).find( 'tr td.loginpress_limit_login_blacklist_ips' ).each(
							function () {
								bulkIps.push( $( this ).data( 'blacklist-ip' ) );
							}
						);

						if ( bulkIps.length === 0 ) {
							e.stopPropagation();
							e.preventDefault();
							if ($( '.llla-bulk-bl' ).length < 1) {

								$( "#loginpress_limit_bulk_blacklists_submit" ).after( '<div id="no-ip-found" class="message error loginpress-llla-bulk-no-item llla-bulk-bl"><span>' + loginpress_llla.translate[9] + '</span></div>' );
							}
							setTimeout(
								function () {
									$( '.loginpress-llla-bulk-no-item' ).fadeOut();
									$( 'div.loginpress-llla-bulk-no-item' ).remove();

								},
								3000
							);
						} else {

							$( '.loginpress-edit-black-popup-containers' ).show();
						}
					}
				);

				$( document ).on(
					"click",
					".loginpress_confirm_remove_all_blacklist",
					function (event) {
						const _nonce = loginpress_llla.bulk_nonce;
						$.ajax(
							{
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'loginpress_clear_all_blacklist',
									security: _nonce,
								},
								beforeSend: function () {

									$( '<div class="loginpress_limit_login_log_message"> Updating .... </div>' ).appendTo( $( '#loginpress_limit_login_blacklist' ) );
									$( '#loginpress_limit_login_log_message' ).fadeIn();

								},
								success: function (response) {
								}
							}
						);
						blackTable.clear().draw();
						$( '.loginpress-edit-black-popup-containers' ).show();
						setTimeout(
							function () {
								$( '.loginpress_limit_login_log_message' ).fadeOut();
							},
							700
						);
						hidePopUpWindow();
						bulkIps = [];
					}
				);

				/**
				 * Handle Bulk Action form submission event
			 *
				 * @return void
				 * @since 2.1.0
				 */
				$( document ).on(
					'click',
					'#loginpress_limit_bulk_whitelists_submit',
					function (e) {
						bulkIps = [];
						$( '#loginpress_limit_login_whitelist' ).find( 'tr td.loginpress_limit_login_whitelist_ips' ).each(
							function () {
								bulkIps.push( $( this ).data( 'whitelist-ip' ) );
							}
						);

						if ( bulkIps.length === 0 ) {
							$( '.loginpress-llla-bulk-no-item' ).remove();
							$( "#loginpress_limit_bulk_whitelists_submit" ).after( '<div id="no-ip-found" class="message error loginpress-llla-bulk-no-item"><span>' + loginpress_llla.translate[8] + '</span></div>' );

							setTimeout(
								function () {
									$( '.loginpress-llla-bulk-no-item' ).fadeOut();
									$( 'div.loginpress-llla-bulk-no-item' ).remove();

								},
								3000
							);
						} else {
							$( '.loginpress-edit-white-popup-containers' ).show();
						}

					}
				);

				$( document ).on(
					"click",
					".loginpress_confirm_remove_all_whitelist",
					function (event) {

						const _nonce = loginpress_llla.bulk_nonce;

						$.ajax(
							{
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'loginpress_clear_all_whitelist',
									security: _nonce,
								},
								beforeSend: function () {
									$( '<div class="loginpress_limit_login_log_message"> Updating .... </div>' ).appendTo( $( '#loginpress_limit_login_whitelist' ) );
									$( '#loginpress_limit_login_log_message' ).fadeIn();
								},
								success: function (response) {

								}
							}
						);
						whiteTable.clear().draw();
							$( '.loginpress-edit-whitelist-popup-containers' ).show();
						setTimeout(
							function () {
								$( '.loginpress_limit_login_log_message' ).fadeOut();
							},
							700
						);
						hidePopUpWindow();
						bulkIps = [];
					}
				);
				$( document ).on(
					"click",
					".limit-login-attempts-close-popup",
					function (event) {
						$( '.llla_remove_all_popup' ).fadeOut();
					}
				);

				// Handle LoginPress - Limit Login Attemps tabs.
				$( '.loginpress-limit-login-tab' ).on(
					'click',
					function (event) {

						event.preventDefault();

						var target = $( this ).attr( 'href' );
						$( target ).show().siblings( 'table' ).hide();
						$( this ).addClass( 'loginpress-limit-login-active' ).siblings().removeClass( 'loginpress-limit-login-active' );

						if ('#loginpress_limit_login_settings' == target) { // Settings Tab.
							$( '#loginpress_limit_logs' ).hide();
							$( '#loginpress_limit_login_whitelist_wrapper2' ).hide();
							$( '#loginpress_limit_login_blacklist_wrapper2' ).hide();
							$( '#loginpress_limit_login_attempts .form-table' ).show();
							$( '#loginpress_limit_login_attempts .submit' ).show();
						}

						if ('#loginpress_limit_logs' == target) { // Attempts Log Tab.
							$( '#loginpress_limit_logs' ).show();
							$( '#loginpress_limit_login_whitelist_wrapper2' ).hide();
							$( '#loginpress_limit_login_blacklist_wrapper2' ).hide();
							$( '#loginpress_limit_login_attempts .form-table' ).hide();
							$( '#loginpress_limit_login_attempts .submit' ).hide();
						}

						if ('#loginpress_limit_login_whitelist' == target) { // Whitelist Tab.
							$( '#loginpress_limit_logs' ).hide();
							$( '#loginpress_limit_login_whitelist_wrapper2' ).show();
							$( '#loginpress_limit_login_whitelist_wrapper2' ).css( "position", "relative" );
							$( '#loginpress_limit_login_whitelist_wrapper2' ).show();
							$( '#loginpress_limit_login_blacklist_wrapper2' ).hide();
							$( '#loginpress_limit_login_attempts .form-table' ).hide();
							$( '#loginpress_limit_login_attempts .submit' ).hide();
						}

						if ('#loginpress_limit_login_blacklist' == target) { // Blacklist Tab.
							$( '#loginpress_limit_logs' ).hide();
							$( '#loginpress_limit_login_whitelist_wrapper2' ).hide();
							$( '#loginpress_limit_login_blacklist_wrapper2' ).show();
							$( '#loginpress_limit_login_blacklist_wrapper2' ).css( "position", "relative" );
							$( '#loginpress_limit_login_attempts .form-table' ).hide();
							$( '#loginpress_limit_login_attempts .submit' ).hide();
						}
					}
				);

				// Apply ajax on click attempts tab whitelist button.
				$( document ).on(
					"click",
					"input.loginpress-attempts-whitelist",
					function (event) {
						$( '.loginpress_llla_loader_inner' ).show();
						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var id     = tr.attr( "data-login-attempt-user" );
						var ip     = el.closest( 'tr' ).attr( "data-ip" );
						var _nonce = loginpress_llla.user_nonce;

						$.ajax(
							{

								url: ajaxurl,
								type: 'POST',
								data: 'id=' + id + '&ip=' + ip + '&action=loginpress_attempts_whitelist' + '&security=' + _nonce,
								beforeSend: function () {

									// tr.find( '.loginpress_autologin_code p' ).html('');
									tr.find( '.autologin-sniper' ).show();
									tr.find( '.loginpress-attempts-unlock' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-attempts-whitelist' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-attempts-blacklist' ).attr( "disabled", "disabled" );
								},
								success: function (response) {
									$( '#loginpress_limit_login_whitelist .dataTables_empty' ).remove();
									var white_list_ip = $( '#loginpress_attempts_id_' + id ).find( '.lg_attempts_ip' ).text();
									$( '#loginpress_attempts_id_' + id ).find( 'td' ).eq( 2 ).find( '.attempts-sniper' ).remove();
									var white_list_user = $( '#loginpress_attempts_id_' + id ).find( 'td' ).eq( 2 ).html();

									var whitelist_tr = '<tr id="loginpress_whitelist_id_' + id + '" data-login-whitelist-user="' + id + '" role="row" class="even">' +
									'<td class="loginpress_limit_login_whitelist_ips" data-whitelist-ip="' + white_list_ip + '">' +
									'<div class="lp-tbody-cell">' + white_list_ip + '</div>' +
									'</td>' +
									'<td class="loginpress_limit_login_whitelist_actions">' +
									'<div class="lp-tbody-cell">' +
									'<button class="loginpress-whitelist-clear button button-primary" type="button" value="' + loginpress_llla.translate[1] + '">Clear</button>' +
									'</div>' +
									'</td>' +
									'</tr>';

									// Remove data from limit attempts table.
									var row     = limitTable.row( el.parents( 'tr' ) );
									var getNode = $.parseHTML( whitelist_tr );
									if ( ! drawOnceWhite) {
										whiteTable.row.add( getNode[0] ).draw();
									}
									row.remove();
									// Add data to white_table.

									var ip = el.closest( 'tr' ).attr( "data-ip" );
									limitTable.rows( '[data-ip="' + ip + '"]' ).remove().draw( false );
									$( '.loginpress_llla_loader_inner' ).hide();
									if ($( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).length == 0) {

										$( '<div class="loginpress_limit_login_log_message"><span>' + loginpress_llla.translate[2] + '(<em>' + ip + '</em>) ' + loginpress_llla.translate[3] + ' </span></div>' ).appendTo( $( '#loginpress_limit_login_log_wrapper' ) );
										$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeIn();
										setTimeout(
											function () {
												$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeOut();
											},
											900
										);
									} else {
										$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).children( 'span' ).html( '' + loginpress_llla.translate[2] + '(<em>' + ip + '</em>) ' + loginpress_llla.translate[3] + '' );
										$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeIn();
										setTimeout(
											function () {
												$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeOut();
											},
											900
										);
									}
								}
							}
						); // !Ajax.

					}
				); // !click .loginpress-attempts-whitelist.

				// Apply ajax on click attempts tab blacklist button.
				$( document ).on(
					"click",
					"input.loginpress-attempts-blacklist",
					function (event) {
						$( '.loginpress_llla_loader_inner' ).show();

						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var id     = tr.attr( "data-login-attempt-user" );
						var ip     = el.closest( 'tr' ).attr( "data-ip" );
						var _nonce = loginpress_llla.user_nonce;

						$.ajax(
							{

								url: ajaxurl,
								type: 'POST',
								data: 'id=' + id + '&ip=' + ip + '&action=loginpress_attempts_blacklist' + '&security=' + _nonce,
								beforeSend: function () {

									// tr.find( '.loginpress_autologin_code p' ).html('');
									tr.find( '.autologin-sniper' ).show();
									tr.find( '.loginpress-attempts-unlock' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-attempts-whitelist' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-attempts-blacklist' ).attr( "disabled", "disabled" );
								},
								success: function (response) {

									$( '#loginpress_limit_login_blacklist .dataTables_empty' ).remove();
									var blacklist_ip = $( '#loginpress_attempts_id_' + id ).find( '.lg_attempts_ip' ).text();
									$( '#loginpress_attempts_id_' + id ).find( 'td' ).eq( 2 ).find( '.attempts-sniper' ).remove();
									var blacklist_user = $( '#loginpress_attempts_id_' + id ).find( 'td' ).eq( 2 ).html();

									var blacklist_tr = '<tr id="loginpress_blacklist_id_' + id + '" data-login-blacklist-user="' + id + '" role="row" class="even">' +
									'<td class="loginpress_limit_login_blacklist_ips" data-blacklist-ip="' + blacklist_ip + '">' +
									'<div class="lp-tbody-cell">' + blacklist_ip + '</div>' +
									'</td>' +
									'<td class="loginpress_limit_login_blacklist_actions">' +
									'<div class="lp-tbody-cell">' +
									'<button class="loginpress-blacklist-clear button button-primary" type="button" value="Clear">Clear</button>' +
									'</div>' +
									'</td>' +
									'</tr>';

									// Remove data from limit attemps table.
									var row     = limitTable.row( el.parents( 'tr' ) );
									var getNode = $.parseHTML( blacklist_tr );
									if ( ! drawOnceBlack) {
										blackTable.row.add( getNode[0] ).draw();
									}
									row.remove();

									// Add data to black_table.

									// blackTable.row.add(getNode[0]).draw();
									var ip = el.closest( 'tr' ).attr( "data-ip" );
									limitTable.rows( '[data-ip="' + ip + '"]' ).remove().draw( false );
									$( '.loginpress_llla_loader_inner' ).hide();

									if ($( '.loginpress_limit_login_log_message' ).length == 0) {
										$( '<div class="loginpress_limit_login_log_message"><span>' + loginpress_llla.translate[2] + '(<em>' + ip + '</em>) ' + loginpress_llla.translate[4] + '</span></div>' ).appendTo( $( '#loginpress_limit_login_log_wrapper' ) );
										$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeIn();
										setTimeout(
											function () {
												$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeOut();
											},
											500
										);
									} else {
										$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).children( 'span' ).html( '' + loginpress_llla.translate[2] + '(<em>' + ip + '</em>) ' + loginpress_llla.translate[4] + '' );
										$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeIn();
										setTimeout(
											function () {
												$( '#loginpress_limit_login_log_wrapper .loginpress_limit_login_log_message' ).fadeOut();
											},
											500
										);
									}
								}
							}
						); // !Ajax.

					}
				); // !click .loginpress-attempts-blacklist.

				// Apply ajax on click attempts tab unlock button.
				$( document ).on(
					"click",
					".loginpress-attempts-unlock",
					function (event) {
						$( '.loginpress_llla_loader_inner' ).show();

						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var id     = tr.attr( "data-login-attempt-user" );
						var ip     = el.closest( 'tr' ).attr( "data-ip" );
						var _nonce = loginpress_llla.user_nonce;

						$.ajax(
							{

								url: ajaxurl,
								type: 'POST',
								data: 'id=' + id + '&ip=' + ip + '&action=loginpress_attempts_unlock' + '&security=' + _nonce,
								beforeSend: function () {
									// tr.find( '.loginpress_autologin_code p' ).html('');
									tr.find( '.autologin-sniper' ).show();
									tr.find( '.loginpress-attempts-unlock' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-attempts-whitelist' ).attr( "disabled", "disabled" );
									tr.find( '.loginpress-attempts-blacklist' ).attr( "disabled", "disabled" );
								},
								success: function (response) {
									$( '.loginpress_llla_loader_inner' ).hide();

									var ip = el.closest( 'tr' ).attr( "data-ip" );
									limitTable.rows( '[data-ip="' + ip + '"]' ).remove().draw( false );
								}
							}
						); // !Ajax.

					}
				); // !click .loginpress-attempts-unlock.

				// Apply ajax on click whitelist tab clear button.
				$( document ).on(
					"click",
					".loginpress-whitelist-clear",
					function (event) {

						event.preventDefault();

						var el     = $( this );
						var tr     = el.closest( 'tr' );
						var ip     = tr.children( 'td:first-child' ).data( 'whitelist-ip' );
						var _nonce = loginpress_llla.user_nonce;

						$.ajax(
							{

								url: ajaxurl,
								type: 'POST',
								data: 'ip=' + ip + '&action=loginpress_whitelist_clear' + '&security=' + _nonce,
								beforeSend: function () {
									// tr.find( '.loginpress_autologin_code p' ).html('');
									tr.find( '.autologin-sniper' ).show();
									tr.find( '.loginpress-whitelist-clear' ).attr( "disabled", "disabled" );
								},
								success: function (response) {
									var row = whiteTable.row( el.parents( 'tr' ) )
									.remove()
									.draw( false );
								}
							}
						); // !Ajax.

					}
				); // !click .loginpress-whitelist-clear.

				// Apply ajax on click blacklist tab clear button.
				$( document ).on(
					"click",
					".loginpress-blacklist-clear",
					function (event) {

						event.preventDefault();

						var el = $( this );
						var tr = el.closest( 'tr' );
						var ip = tr.children( 'td:first-child' ).data( 'blacklist-ip' );

						var _nonce = loginpress_llla.user_nonce;
						$.ajax(
							{

								url: ajaxurl,
								type: 'POST',
								data: 'ip=' + ip + '&action=loginpress_blacklist_clear' + '&security=' + _nonce,
								beforeSend: function () {
									// tr.find( '.loginpress_autologin_code p' ).html('');
									tr.find( '.autologin-sniper' ).show();
									tr.find( '.loginpress-blacklist-clear' ).attr( "disabled", "disabled" );
								},
								success: function (response) {
									var row = blackTable.row( el.parents( 'tr' ) )
									.remove()
									.draw( false );
									// blackTable.rows('.seleted').remove().draw(false);
								}
							}
						); // !Ajax.

					}
				); // !click .loginpress-whitelist-clear.

				// Block "+", "-" in input fields.
				$( '#loginpress_limit_login_attempts .form-table input[type="number"]' ).on(
					'keypress',
					function (evt) {
						if (evt.which != 8 && evt.which != 0 && evt.which < 48 || evt.which > 57) {
							evt.preventDefault();
						}
					}
				);
				$( '#loginpress_limit_login_attempts .form-table input[type="text"]' ).on(
					'keydown',
					function (evt) {
						if (evt.keyCode == 13) {
							evt.preventDefault();
						}
					}
				);
				$( document ).on(
					"submit",
					"#loginpress_limit_login_attempts form",
					function (event) {
						$( '.ip_add_remove input[type="text"]' ).val( '' );
					}
				);

				$( document ).on(
					'click',
					'.add_white_list , .add_black_list',
					function () {

						var ip = $( '.ip_add_remove input[type="text"]' ).val();
						// Remove all rows in the limit table with the matching IP
						if (limitTable) {
							limitTable.rows(
								function (idx, data, node) {
									// Assuming the IP is in the second column (index 1), change if necessary
									return $( node ).data( 'ip' ) === ip;
								}
							).remove().draw();
						}
						var _security = loginpress_llla.manual_ip_cta;
						var action    = $( this ).data( 'action' );

						$( '.ip_add_remove td .message' ).remove();

						if ('' == ip) {
							$( '.ip_add_remove td' ).append( '<p class="message error"> <span>' + loginpress_llla.translate[6] + '</span> </p>' );
							$( '.ip_add_remove td .error' ).delay( 5000 ).fadeOut( 500 );
							return false;
						} else {
							var special_ips = ['255.255.255.255', '0.0.0.0'];
							if ( special_ips.includes( ip ) ) {
								$( '.ip_add_remove td' ).append( '<p class="message error"> <span>' + loginpress_llla.translate[7] + '</span> </p>' );
								$( '.ip_add_remove td .error' ).delay( 5000 ).fadeOut( 500 );
								return false;
							}
						}

						if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test( ip )) {
							$( '.ip_add_remove td .message' ).remove();
						} else {
							$( '.ip_add_remove td' ).append( '<p class="message error"> <span>' + loginpress_llla.translate[5] + '</span> </p>' );
							$( '.ip_add_remove td .error' ).delay( 5000 ).fadeOut( 500 );
							return false;
						}

						var request_data = {
							'security': _security,
							'ip_action': action,
							'ip': ip,
							'action': 'loginpress_white_black_list_ip',
						};
						if ($( '.lla-spinner' ).length <= 1) {
							$( '.lla-spinner' ).css( 'display', 'inline-block' );
						}
						$.ajax(
							{

								url: ajaxurl,
								type: 'POST',
								data: request_data,
								beforeSend: function () {
									$( '.ip_add_remove button' ).attr( 'disabled', true );
									// Show the spinner only if it's hidden
								},
								success: function (res) {
									$( '.ip_add_remove button' ).attr( 'disabled', false );
									if (res.success) {
										$( '.lla-spinner' ).css( 'display', 'none' );
										$( '.ip_add_remove td' ).append( '<p class="message success"><span>' + res.data.message + '</span></p>' );
										$( '.ip_add_remove td .success' ).delay( 5000 ).fadeOut( 500 );
										refreshIpList( 'white_list', _security );
										refreshIpList( 'black_list', _security );
									} else {
										$( '.ip_add_remove td' ).append( '<p class="message error"><span>' + res.data.message + '</span></p>' );
										$( '.ip_add_remove td .error' ).delay( 5000 ).fadeOut( 500 );
									}
								}
							}
						); // !Ajax.

					}
				);

				/**
				 * Get and update list of ip.
				 *
				 * @since 1.3.0
				 * @param {string} list name on list to update
				 */
				function refreshIpList(list, _security) {

					var request_data = {
						'security': _security,
						'action': 'loginpress_' + list + '_records',
					};
					$.ajax(
						{

							url: ajaxurl,
							type: 'POST',
							data: request_data,
							success: function (res) {
								let tableWhiteList = '#loginpress_limit_login_whitelist';
								let tableBlackList = '#loginpress_limit_login_blacklist';
								if (res.success) {
									if (list == 'white_list') {
										if ( ! drawOnceWhite) {
											whiteTable.clear();
											whiteTable.rows.add( $( res.data.tbody ) ).draw();
											whiteTable.draw();
										}
									}

									if (list == 'black_list') {
										// $(tableBlackList).find('tbody').html(res.data.tbody);
										if ( ! drawOnceBlack) {
											blackTable.clear();
											blackTable.rows.add( $( res.data.tbody ) ).draw();
											blackTable.draw();
										}

									}
									$( '.ip_add_remove button' ).attr( 'disabled', false );
									$( '.lla-spinner' ).hide();
								} else {

									if (list == 'white_list') {
										let tableWhiteList = '#loginpress_limit_login_whitelist';
										// $(tableWhiteList).DataTable();
										if (whiteTable) {
											whiteTable.clear().draw();}
									}

									if (list == 'black_list') {
										let tableBlackList = '#loginpress_limit_login_blacklist';
										if (blackTable) {
											blackTable.clear().draw();}
										// $(tableBlackList).DataTable();

									}
								}
								// var table = jQuery('#loginpress_limit_login_whitelist').dataTable()
								// table.fnClearTable()
							}
						}
					); // !Ajax.
				}
			}
		}
	);
})( jQuery );
