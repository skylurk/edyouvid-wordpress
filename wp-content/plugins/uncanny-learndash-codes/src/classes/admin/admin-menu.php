<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class AdminMenu
 *
 * @package uncanny_learndash_codes
 */
class AdminMenu extends Boot {

	/**
	 * AdminMenu constructor.
	 */
	public function __construct() {
		// Setup Theme Options Page Menu in Admin.
		if ( is_admin() ) {
			add_action(
				'admin_menu',
				array(
					__CLASS__,
					'register_options_menu_page',
				)
			);
			add_action(
				'admin_init',
				array(
					__CLASS__,
					'save_form_settings',
				)
			);
			add_action(
				'admin_head',
				array(
					__CLASS__,
					'hide_screen_options',
				)
			);
			add_action( 'admin_head', array( __CLASS__, 'maybe_filter_non_codes_admin_notices' ), PHP_INT_MAX );
			add_action( 'admin_print_scripts', array( __CLASS__, 'maybe_filter_non_codes_admin_notices' ), PHP_INT_MAX );
		}

	}

	/**
	 * Create Plugin options menu
	 */
	public static function register_options_menu_page() {

		$page_title = 'Uncanny Redemption Codes';
		$menu_title = 'Uncanny Codes';
		$capability = apply_filters( 'ulc_capability', 'manage_options' );
		$menu_slug  = 'uncanny-learndash-codes';
		$function   = array( __CLASS__, 'options_menu_view_codes' );

		$icon_url = SharedFunctionality::get_svg_data_uri();

		// 42 - Above Settings Menu.
		$position = 42;
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		if ( is_numeric( SharedFunctionality::ulc_filter_input( 'group_id' ) ) && 'true' === SharedFunctionality::ulc_filter_input( 'edit' ) ) {
			$sub_menu_title = esc_html__( 'Modify Codes', 'uncanny-learndash-codes' );
		} else {
			$sub_menu_title = esc_html__( 'Generate Codes', 'uncanny-learndash-codes' );
		}

		add_submenu_page(
			$menu_slug,
			esc_html__( 'View Codes', 'uncanny-learndash-codes' ),
			esc_html__( 'View Codes', 'uncanny-learndash-codes' ),
			$capability,
			'uncanny-learndash-codes',
			array(
				__CLASS__,
				'options_menu_view_codes',
			)
		);

		add_submenu_page(
			$menu_slug,
			$sub_menu_title,
			$sub_menu_title,
			$capability,
			'uncanny-learndash-codes-create',
			array(
				__CLASS__,
				'options_menu_page_output',
			)
		);

		add_submenu_page(
			$menu_slug,
			esc_html__( 'Cancel Codes', 'uncanny-learndash-codes' ),
			esc_html__( 'Cancel Codes', 'uncanny-learndash-codes' ),
			$capability,
			'uncanny-learndash-codes-cancel',
			array(
				__CLASS__,
				'options_menu_cancel_codes_page',
			)
		);

		add_submenu_page(
			$menu_slug,
			esc_html__( 'Settings', 'uncanny-learndash-codes' ),
			esc_html__( 'Settings', 'uncanny-learndash-codes' ),
			$capability,
			'uncanny-learndash-codes-settings',
			array(
				__CLASS__,
				'options_menu_settings_page',
			)
		);
	}

	/**
	 * Create Theme Options page
	 */
	public static function options_menu_page_output() {
		global $uncanny_learndash_codes;
		include Config::get_template( 'admin-create-login-codes.php' );
	}

	/**
	 *
	 */
	public static function options_menu_view_codes() {

		// Bulk actions are now handled in boot.php

		if ( empty( SharedFunctionality::ulc_filter_input( 'group_id' ) ) && empty( SharedFunctionality::ulc_filter_input( 'mode' ) ) ) {
			// Group View.
			self::display_code_groups();

		} elseif ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) && 'download' === SharedFunctionality::ulc_filter_input( 'mode' ) ) {
			// Download CSV for entire group
			self::handle_group_download();

		} elseif ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) && empty( SharedFunctionality::ulc_filter_input( 'mode' ) ) ) {
			// Coupon View.
			self::display_group_codes();
		}

	}

	/**
	 *
	 */
	public static function display_code_groups() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		include_once 'class-view-groups.php';
		$table = new ViewGroups();
		$table->prepare_items();

		include Config::get_template( 'admin-view-codes.php' );
	}

	/**
	 * Handle download of entire group
	 */
	public static function handle_group_download() {
		$group_id = SharedFunctionality::ulc_filter_input( 'group_id' );

		if ( empty( $group_id ) ) {
			wp_die( esc_html__( 'No group selected for download', 'uncanny-learndash-codes' ) );
		}

		// Generate CSV using existing method
		Boot::generate_csv( 'login_coupon' );
	}

	/**
	 *
	 */
	public static function display_group_codes() {
		if ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) {
			if ( ! class_exists( 'WP_List_Table' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			}
			include_once 'class-view-codes.php';
			$table = new ViewCodes( array( 'group_id' => '' ) );
			$table->prepare_items();

			include Config::get_template( 'admin-view-codes.php' );
		}
	}

	/**
	 * @param $setting
	 * @param $is_multisite
	 * @param $default
	 *
	 * @return false|mixed|void
	 */
	public static function get_settings_value( $setting, $default = '', $is_multisite = false ) {
		if ( $is_multisite ) {
			return get_blog_option( get_current_blog_id(), $setting, $default );
		}

		return get_option( $setting, $default );
	}

	/**
	 *
	 */
	public static function options_menu_cancel_codes_page() {
		include Config::get_template( 'admin-cancel-codes.php' );
	}

	/**
	 *
	 */
	public static function options_menu_settings_page() {
		?>

		<div class="wrap uo-ulc-admin uncannyowl-default-design">
			<div class="ulc uncannyowl-default-design">
				<?php

				// Add admin header and tabs.
				$tab_active = 'uncanny-learndash-codes-settings';
				include Config::get_template( 'admin-header.php' );

				?>

				<div class="ulc__admin-content">

					<h2></h2> <!-- LearnDash notice will be shown here -->

					<!-- Notifications -->

					<?php if ( SharedFunctionality::ulc_filter_has_var( 'saved' ) ) { ?>

						 
							<div class="uncannyowl-alert uncannyowl-alert-success uncannyowl-alert--dismissible">
								<button  type="button" class="uncannyowl-alert-close" aria-label="Close alert">&times;</button>
								<strong>Success!</strong> <?php esc_html_e( 'Settings saved!', 'uncanny-learndash-codes' ); ?>
							</div>



					<?php } elseif ( SharedFunctionality::ulc_filter_has_var( 'force_downloaded' ) ) { ?>


						<div class="uncannyowl-alert uncannyowl-alert-error uncannyowl-alert--dismissible">
							<button type="button" class="uncannyowl-alert-close" aria-label="Close alert">&times;</button>
							<?php esc_html_e( 'Failed to create Theme My Login form! Download', 'uncanny-learndash-codes' ); ?>
							<a href="
														   <?php
															echo add_query_arg(
																array( 'mode' => 'download_file' ),
																remove_query_arg(
																	array(
																		'redirect_nonce',
																		'mode',
																		'saved',
																		'force_downloaded',
																	)
																)
															)
															?>
															">
																register-form.php
															</a>
															<?php esc_html_e( 'and upload to your theme\'s directory ( /wp-content/themes/YOUR-THEME/theme-my-login/ ) via (S)FTP.', 'uncanny-learndash-codes' ); ?>
						</div>
					 

					<?php } ?>

					<div id="registration_form_error" class="uncannyowl-alert uncannyowl-alert-error uncannyowl-alert--dismissible" style="display: none">
					<button  type="button" class="uncannyowl-alert-close" aria-label="Close alert">&times;</button>
   	
					<h4></h4></div>

					<?php Boot::uo_license_page( true ); ?>

					<form method="post" action=""
						  id="uncanny-learndash-codes-form">
						<input type="hidden" name="_wp_http_referer"
							   value="<?php echo admin_url( 'admin.php?page=uncanny-learndash-codes-settings&saved=true' ); ?>"/>
						<input type="hidden" name="_wpnonce"
							   value="<?php echo wp_create_nonce( Config::get_project_name() ); ?>"/>

						<?php
						$is_multisite                = is_multisite();
						$existing                    = self::get_settings_value( Config::$uncanny_codes_settings_gravity_forms, 0, $is_multisite );
						$code_field_mandatory        = self::get_settings_value( Config::$uncanny_codes_settings_gravity_forms_mandatory, 0, $is_multisite );
						$code_field_label            = self::get_settings_value( Config::$uncanny_codes_settings_gravity_forms_label, null, $is_multisite );
						$code_field_error_message    = self::get_settings_value( Config::$uncanny_codes_settings_gravity_forms_error, null, $is_multisite );
						$code_field_placeholder      = self::get_settings_value( Config::$uncanny_codes_settings_gravity_forms_placeholder, null, $is_multisite );
						$group_settings              = self::get_settings_value( Config::$uncanny_codes_settings_multiple_groups, 0, $is_multisite );
						$custom_messages             = self::get_settings_value( Config::$uncanny_codes_settings_custom_messages, null, $is_multisite );
						$term_conditions             = self::get_settings_value( Config::$uncanny_codes_settings_term_conditions, '', $is_multisite );
						$autocomplete_settings       = self::get_settings_value( Config::$uncanny_codes_settings_autocomplete, null, $is_multisite );
						$allow_user_redeem_same_code = self::get_settings_value( Config::$uncanny_codes_same_code_user_redemption, 0, $is_multisite );
						$times_code_can_be_reused    = self::get_settings_value( Config::$uncanny_codes_times_code_can_be_reused, 1, $is_multisite );

						// Uncanny codes.
						include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-codes.php';
						// Gravity Forms.
						include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-gravity-forms.php';
						// LearnDash.
						include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-learndash.php';
						// Terms & Conditions.
						include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-woocommerce.php';
						// Custom Messages.
						include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-custom-messages.php';
						// Terms & Conditions.
						include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-tos.php';
						?>
					</form>

					<?php
					// Terms & Conditions.
					include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-tml.php';
					// Terms & Conditions.
					include_once dirname( UO_CODES_FILE ) . '/src/templates/admin-settings/admin-danger-zone.php';
					?>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 *
	 */
	public static function save_form_settings() {
		if ( SharedFunctionality::ulc_filter_has_var( '_wpnonce', INPUT_POST ) && wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_wpnonce', INPUT_POST ), Config::get_project_name() ) ) {
			if ( SharedFunctionality::ulc_filter_has_var( 'registration_form', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'registration_form', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_settings_gravity_forms, intval( SharedFunctionality::ulc_filter_input( 'registration_form', INPUT_POST ) ) );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'registration-field-mandatory', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'registration-field-mandatory', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_settings_gravity_forms_mandatory, intval( SharedFunctionality::ulc_filter_input( 'registration-field-mandatory', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_settings_gravity_forms_mandatory );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'allow-multiple-group-registration', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'allow-multiple-group-registration', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_settings_multiple_groups, intval( SharedFunctionality::ulc_filter_input( 'allow-multiple-group-registration', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_settings_multiple_groups );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'allow-user-to-redeem-same-code', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'allow-user-to-redeem-same-code', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_same_code_user_redemption, intval( SharedFunctionality::ulc_filter_input( 'allow-user-to-redeem-same-code', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_same_code_user_redemption );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'times_code_can_be_reused', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'times_code_can_be_reused', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_times_code_can_be_reused, intval( SharedFunctionality::ulc_filter_input( 'times_code_can_be_reused', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_times_code_can_be_reused );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'autocomplete-codes-orders', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'autocomplete-codes-orders', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_settings_autocomplete, intval( SharedFunctionality::ulc_filter_input( 'autocomplete-codes-orders', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_settings_autocomplete );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'code_field_label', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'code_field_label', INPUT_POST ) ) ) {
				update_option( Config::$uncanny_codes_settings_gravity_forms_label, sanitize_text_field( SharedFunctionality::ulc_filter_input( 'code_field_label', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_settings_gravity_forms_label );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'code_field_error_message', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'code_field_error_message', INPUT_POST ) ) ) {
				update_option( Config::$uncanny_codes_settings_gravity_forms_error, sanitize_text_field( SharedFunctionality::ulc_filter_input( 'code_field_error_message', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_settings_gravity_forms_error );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'code_field_placeholder', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'code_field_placeholder', INPUT_POST ) ) ) {
				update_option( Config::$uncanny_codes_settings_gravity_forms_placeholder, sanitize_text_field( SharedFunctionality::ulc_filter_input( 'code_field_placeholder', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_settings_gravity_forms_placeholder );
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'invalid-code', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'invalid-code', INPUT_POST ) ) ) {
				$invalid_code = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'invalid-code', INPUT_POST ) );
			} else {
				$invalid_code = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'cancelled-code', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'cancelled-code', INPUT_POST ) ) ) {
				$cancelled_code = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'cancelled-code', INPUT_POST ) );
			} else {
				$cancelled_code = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'expired-code', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'expired-code', INPUT_POST ) ) ) {
				$expired_code = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'expired-code', INPUT_POST ) );
			} else {
				$expired_code = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'already-redeemed', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'already-redeemed', INPUT_POST ) ) ) {
				$already_redeemed = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'already-redeemed', INPUT_POST ) );
			} else {
				$already_redeemed = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'redeemed-maximum', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'redeemed-maximum', INPUT_POST ) ) ) {
				$redeemed_maximum = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'redeemed-maximum', INPUT_POST ) );
			} else {
				$redeemed_maximum = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'successfully-redeemed', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'successfully-redeemed', INPUT_POST ) ) ) {
				$allowed_html          = wp_kses_allowed_html( 'post' );
				$successfully_redeemed = wp_kses( SharedFunctionality::ulc_filter_input( 'successfully-redeemed', INPUT_POST ), $allowed_html );
			} else {
				$successfully_redeemed = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'already-redeemed-course', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'already-redeemed-course', INPUT_POST ) ) ) {
				$already_redeemed_course = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'already-redeemed-course', INPUT_POST ) );
			} else {
				$already_redeemed_course = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'already-redeemed-group', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'already-redeemed-group', INPUT_POST ) ) ) {
				$already_redeemed_group = sanitize_text_field( SharedFunctionality::ulc_filter_input( 'already-redeemed-group', INPUT_POST ) );
			} else {
				$already_redeemed_group = '';
			}

			if ( SharedFunctionality::ulc_filter_has_var( 'uo_codes_term_condition', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'uo_codes_term_condition', INPUT_POST ) ) ) {
				$allowed_html            = wp_kses_allowed_html( 'post' );
				$uo_codes_term_condition = wp_kses( SharedFunctionality::ulc_filter_input( 'uo_codes_term_condition', INPUT_POST ), $allowed_html );

				update_option( Config::$uncanny_codes_settings_term_conditions, $uo_codes_term_condition );
			} else {
				delete_option( Config::$uncanny_codes_settings_term_conditions );
			}

			$settings = array(
				'invalid-code'            => $invalid_code,
				'cancelled-code'          => $cancelled_code,
				'expired-code'            => $expired_code,
				'already-redeemed'        => $already_redeemed,
				'already-redeemed-course' => $already_redeemed_course,
				'already-redeemed-group'  => $already_redeemed_group,
				'redeemed-maximum'        => $redeemed_maximum,
				'successfully-redeemed'   => $successfully_redeemed,
			);

			update_option( Config::$uncanny_codes_settings_custom_messages, $settings );

			wp_safe_redirect( SharedFunctionality::ulc_filter_input( '_wp_http_referer', INPUT_POST ) . '&saved=true&redirect_nonce=' . wp_create_nonce( time() ) );
			exit;
		}

		if ( SharedFunctionality::ulc_filter_has_var( '_tml_wpnonce', INPUT_POST ) && wp_verify_nonce( SharedFunctionality::ulc_filter_input( '_tml_wpnonce', INPUT_POST ), Config::get_project_name() ) ) {
			if ( SharedFunctionality::ulc_filter_has_var( 'tml-replace-registration-form', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'tml-replace-registration-form', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_tml_template_override, intval( SharedFunctionality::ulc_filter_input( 'tml-replace-registration-form', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_tml_template_override );
			}
			if ( SharedFunctionality::ulc_filter_has_var( 'tml-code-required-field', INPUT_POST ) && is_numeric( intval( SharedFunctionality::ulc_filter_input( 'tml-code-required-field', INPUT_POST ) ) ) ) {
				update_option( Config::$uncanny_codes_tml_codes_required_field, intval( SharedFunctionality::ulc_filter_input( 'tml-code-required-field', INPUT_POST ) ) );
			} else {
				delete_option( Config::$uncanny_codes_tml_codes_required_field );
			}

			wp_safe_redirect( SharedFunctionality::ulc_filter_input( '_wp_http_referer', INPUT_POST ) . '&saved=true&redirect_nonce=' . wp_create_nonce( time() ) );
			exit;
		}
	}

	/**
	 * @param $message
	 */
	public static function show_message( $message ) {
		?> 
		<div class="uncannyowl-alert uncannyowl-alert-info uncannyowl-alert--dismissible">
			<button  type="button" class="uncannyowl-alert-close" aria-label="Close alert">&times;</button>
			<?php esc_html_e( $message, 'uncanny-learndash-codes' ); ?>
		</div>
		<?php
	}

	/**
	 * Hide Screen Options tab on Uncanny Codes admin pages
	 */
	public static function hide_screen_options() {
		global $current_screen;
		
		// Check if we're on an Uncanny Codes admin page
		$ulc_pages = array(
			'toplevel_page_uncanny-learndash-codes',
			'uncanny-codes_page_uncanny-learndash-codes-create',
			'uncanny-codes_page_uncanny-learndash-codes-cancel',
			'uncanny-codes_page_uncanny-learndash-codes-settings',
			'uncanny-codes_page_uncanny-codes-kb',
			'uncanny-codes_page_uncanny-codes-plugins',
		);
		
		if ( isset( $current_screen->base ) && in_array( $current_screen->base, $ulc_pages ) ) {
			// Remove screen options meta box
			remove_meta_box( 'screen-options', $current_screen->id, 'normal' );
			remove_meta_box( 'screen-options', $current_screen->id, 'side' );
			remove_meta_box( 'screen-options', $current_screen->id, 'advanced' );
			
			// Hide the screen options link
			echo '<style type="text/css">#screen-options-link-wrap { display: none !important; }</style>';
		}
	}

	/**
	 * Maybe apply notification filters to Uncanny Codes pages.
	 * 
	 * @return void
	 */
	public static function maybe_filter_non_codes_admin_notices() {

		$codes_pages = array(
			'uncanny-learndash-codes',
			'uncanny-learndash-codes-create',
			'uncanny-learndash-codes-cancel',
			'uncanny-learndash-codes-settings',
			'uncanny-codes-kb',
			'uncanny-codes-plugins',
			'uncanny-codes-license-activation',
		);

		// Bail if we're not on an Uncanny Codes screen.
		if ( empty( $_REQUEST['page'] ) || ! in_array( strtolower( $_REQUEST['page'] ), $codes_pages, true ) ) {
			return;
		}

		// Run filter on all admin notices.
		self::filter_non_codes_admin_notices( 'user_admin_notices' );
		self::filter_non_codes_admin_notices( 'admin_notices' );
		self::filter_non_codes_admin_notices( 'all_admin_notices' );
		
		// Remove Automator Pro licensing notices
		self::remove_automator_pro_licensing_notices();
		
	}

	/**
	 * Filter out all notices that are not from Uncanny Codes.
	 * 
	 * @param string $notice_type The type of notice to filter.
	 * 
	 * @return void
	 */
	public static function filter_non_codes_admin_notices( $notice_type ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $notice_type ] ) ) {
			return;
		}

		if ( ! is_array( $wp_filter[ $notice_type ]->callbacks ) ) {
			return;
		}

		// All Uncanny Codes lowercased namespaces.
		$allowed_sources = array(
			'uncanny_learndash_codes',
			'uo_codes',
			'uncanny_owl',
			'ulc',
		);

		foreach ( $wp_filter[ $notice_type ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
					unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
					continue;
				}

				// Determine the source of the notice
                $source = '';
				if ( isset( $arr['function'] ) && is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) ) {
                    $source = strtolower( get_class( $arr['function'][0] ) );
                } elseif ( ! empty( $name ) ) {
                    $source = strtolower( $name );
                }

				// Remove the notice if its source is not in the list of allowed sources
                $allowed = false;
                foreach ( $allowed_sources as $allowed_source ) {
                    if ( strpos( $source, $allowed_source ) !== false) {
                        $allowed = true;
                        break;
                    }
                }

                if ( ! $allowed ) {
                    unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
                }
			}
		}
	}

	/**
	 * Remove all automator_show_internal_admin_notice actions.
	 * 
	 * @return void
	 */
	public static function remove_automator_pro_licensing_notices() {
		// Simply remove all actions from this hook
		remove_all_actions( 'automator_show_internal_admin_notice' );
	}

}
