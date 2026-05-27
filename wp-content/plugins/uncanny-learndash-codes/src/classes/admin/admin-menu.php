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
		}

	}

	/**
	 * Create Plugin options menu
	 */
	public static function register_options_menu_page() {

		$page_title = 'Uncanny Codes';
		$menu_title = 'Uncanny Codes';
		$capability = apply_filters( 'ulc_capability', 'manage_options' );
		$menu_slug  = 'uncanny-learndash-codes';
		$function   = array( __CLASS__, 'options_menu_view_codes' );

		$icon_url = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMCAwIDU4MSA2NDAiIHZlcnNpb249IjEuMSIgdmlld0JveD0iMCAwIDU4MSA2NDAiIHhtbDpzcGFjZT0icHJlc2VydmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0ibTUyNi40IDM0LjFjMC42IDUgMSAxMC4xIDEuMyAxNS4xIDAuNSAxMC4zIDEuMiAyMC42IDAuOCAzMC45LTAuNSAxMS41LTEgMjMtMi4xIDM0LjQtMi42IDI2LjctNy44IDUzLjMtMTYuNSA3OC43LTcuMyAyMS4zLTE3LjEgNDEuOC0yOS45IDYwLjQtMTIgMTcuNS0yNi44IDMzLTQzLjggNDUuOS0xNy4yIDEzLTM2LjcgMjMtNTcuMSAyOS45LTI1LjEgOC41LTUxLjUgMTIuNy03Ny45IDEzLjggNzAuMyAyNS4zIDEwNi45IDEwMi44IDgxLjYgMTczLjEtMTguOSA1Mi42LTY4LjEgODguMS0xMjQgODkuNWgtNi4xYy0xMS4xLTAuMi0yMi4xLTEuOC0zMi45LTQuNy0yOS40LTcuOS01NS45LTI2LjMtNzMuNy01MC45LTI5LjItNDAuMi0zNC4xLTkzLjEtMTIuNi0xMzgtMjUgMjUuMS00NC41IDU1LjMtNTkuMSA4Ny40LTguOCAxOS43LTE2LjEgNDAuMS0yMC44IDYxLjEtMS4yLTE0LjMtMS4yLTI4LjYtMC42LTQyLjkgMS4zLTI2LjYgNS4xLTUzLjIgMTIuMi03OC45IDUuOC0yMS4yIDEzLjktNDEuOCAyNC43LTYwLjlzMjQuNC0zNi42IDQwLjYtNTEuM2MxNy4zLTE1LjcgMzcuMy0yOC4xIDU5LjEtMzYuOCAyNC41LTkuOSA1MC42LTE1LjIgNzYuOC0xNy4yIDEzLjMtMS4xIDI2LjctMC44IDQwLjEtMi4zIDI0LjUtMi40IDQ4LjgtOC40IDcxLjMtMTguMyAyMS05LjIgNDAuNC0yMS44IDU3LjUtMzcuMiAxNi41LTE0LjkgMzAuOC0zMi4xIDQyLjgtNTAuOCAxMy0yMC4yIDIzLjQtNDIuMSAzMS42LTY0LjcgNy42LTIxLjEgMTMuNC00Mi45IDE2LjctNjUuM3ptLTI3OS40IDMyOS41Yy0xOC42IDEuOC0zNi4yIDguOC01MC45IDIwLjQtMTcuMSAxMy40LTI5LjggMzIuMi0zNi4yIDUyLjktNy40IDIzLjktNi44IDQ5LjUgMS43IDczIDcuMSAxOS42IDE5LjkgMzcuMiAzNi44IDQ5LjYgMTQuMSAxMC41IDMwLjkgMTYuOSA0OC40IDE4LjZzMzUuMi0xLjYgNTEtOS40YzEzLjUtNi43IDI1LjQtMTYuMyAzNC44LTI4LjEgMTAuNi0xMy40IDE3LjktMjkgMjEuNS00NS43IDQuOC0yMi40IDIuOC00NS43LTUuOC02Ni45LTguMS0yMC0yMi4yLTM3LjYtNDAuMy00OS4zLTE4LTExLjctMzkuNS0xNy02MS0xNS4xeiIgZmlsbD0iIzgyODc4QyIvPjxwYXRoIGQ9Im0yNDIuNiA0MDIuNmM2LjItMS4zIDEyLjYtMS44IDE4LjktMS41LTExLjQgMTEuNC0xMi4yIDI5LjctMS44IDQyIDExLjIgMTMuMyAzMS4xIDE1LjEgNDQuNCAzLjkgNS4zLTQuNCA4LjktMTAuNCAxMC41LTE3LjEgMTIuNCAxNi44IDE2LjYgMzkuNCAxMSA1OS41LTUgMTguNS0xOCAzNC42LTM1IDQzLjUtMzQuNSAxOC4yLTc3LjMgNS4xLTk1LjUtMjkuNS0xLTItMi00LTIuOS02LjEtOC4xLTE5LjYtNi41LTQzIDQuMi02MS4zIDEwLTE3IDI2LjgtMjkuMiA0Ni4yLTMzLjR6IiBmaWxsPSIjODI4NzhDIi8+PC9zdmc+';

		// 42 - Above Settings Menu.
		$position = 42;
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		if ( is_numeric( SharedFunctionality::ulc_filter_input( 'group_id' ) ) && 'true' === SharedFunctionality::ulc_filter_input( 'edit' ) ) {
			$sub_menu_title = esc_html__( 'Modify codes', 'uncanny-learndash-codes' );
		} else {
			$sub_menu_title = esc_html__( 'Generate codes', 'uncanny-learndash-codes' );
		}

		add_submenu_page(
			$menu_slug,
			esc_html__( 'View codes', 'uncanny-learndash-codes' ),
			esc_html__( 'View codes', 'uncanny-learndash-codes' ),
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
			esc_html__( 'Cancel codes', 'uncanny-learndash-codes' ),
			esc_html__( 'Cancel codes', 'uncanny-learndash-codes' ),
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

		if ( empty( SharedFunctionality::ulc_filter_input( 'group_id' ) ) && empty( SharedFunctionality::ulc_filter_input( 'mode' ) ) ) {
			// Group View.
			self::display_code_groups();

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

			?>

			<div class="wrap uo-ulc-admin">
				<div class="ulc">
					<div id="page_coupon_stat">
						<?php
						// Add admin header and tabs.
						$tab_active = 'uncanny-learndash-codes';
						include Config::get_template( 'admin-header.php' );
						?>
						<div class="ulc__admin-content">

							<h2></h2>
							<!-- LearnDash notice will be shown here -->

							<h1 class="wp-heading-inline"><?php echo esc_html__( 'View generated codes', 'uncanny-learndash-codes' ); ?></h1>
							<hr class="wp-header-end">

							<div class="uo-codes-heading">
								<form class="uo-codes-search" method="get"
									  action="">
									<input type="hidden" name="page"
										   value="<?php echo SharedFunctionality::ulc_filter_input( 'page' ); ?>"/>
									<?php if ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) { ?>
										<input type="hidden" name="group_id"
											   value="<?php echo SharedFunctionality::ulc_filter_input( 'group_id' ); ?>"/>
									<?php } ?>
									<?php $table->search_box( esc_html__( 'Search codes', 'uncanny-learndash-codes' ), Config::get_project_name() ); ?>
								</form>
							</div>

							<div class="uo-codes-buttons">
								<?php $table->views(); ?>
							</div>

							<div class="uo-codes-list">
								<?php $table->display(); ?>
							</div>

						</div>
					</div>
				</div>
			</div>
			<?php
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

		<div class="wrap uo-ulc-admin">
			<div class="ulc">
				<?php

				// Add admin header and tabs.
				$tab_active = 'uncanny-learndash-codes-settings';
				include Config::get_template( 'admin-header.php' );

				?>

				<div class="ulc__admin-content">

					<h2></h2> <!-- LearnDash notice will be shown here -->

					<!-- Notifications -->

					<?php if ( SharedFunctionality::ulc_filter_has_var( 'saved' ) ) { ?>

						<div
							class="updated notice"><?php esc_html_e( 'Settings saved!', 'uncanny-learndash-codes' ); ?></div>

					<?php } elseif ( SharedFunctionality::ulc_filter_has_var( 'force_downloaded' ) ) { ?>

						<div class="notice error">
							<?php esc_html_e( 'Failed to create Theme My Login form! Download', 'uncanny-learndash-codes' ); ?>

														   href="
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

					<div class="notice notice-error"
						 id="registration_form_error" style="display: none">
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

			if ( SharedFunctionality::ulc_filter_has_var( 'uo_codes_term_condition', INPUT_POST ) && ! empty( SharedFunctionality::ulc_filter_input( 'uo_codes_term_condition', INPUT_POST ) ) ) {
				$allowed_html            = wp_kses_allowed_html( 'post' );
				$uo_codes_term_condition = wp_kses( SharedFunctionality::ulc_filter_input( 'uo_codes_term_condition', INPUT_POST ), $allowed_html );

				update_option( Config::$uncanny_codes_settings_term_conditions, $uo_codes_term_condition );
			} else {
				delete_option( Config::$uncanny_codes_settings_term_conditions );
			}

			$settings = array(
				'invalid-code'          => $invalid_code,
				'cancelled-code'        => $cancelled_code,
				'expired-code'          => $expired_code,
				'already-redeemed'      => $already_redeemed,
				'redeemed-maximum'      => $redeemed_maximum,
				'successfully-redeemed' => $successfully_redeemed,
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
		<div class="updated notice">
			<?php esc_html_e( $message, 'uncanny-learndash-codes' ); ?>
		</div>
		<?php
	}

}
