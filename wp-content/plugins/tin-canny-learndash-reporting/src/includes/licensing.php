<?php

namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class AdminMenu
 *
 * This class should only be used to inherit classes
 *
 * @package uncanny_learndash_reporting
 */
class Licensing {

	/**
	 * The name of the licensing page
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $page_name = null;

	/**
	 * The slug of the licensing page
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $page_slug = null;

	/**
	 * The slug of the parent that the licensing page is organized under
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $parent_slug = null;

	/**
	 * The URL of store powering the plugin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $store_url = null;

	/**
	 * The Author of the Plugin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $item_name = null;
	/**
	 * @var null
	 */
	public $item_id = null;
	/**
	 * @var null
	 */
	public $license = null;

	/**
	 * The Author of the Plugin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $author = null;

	/**
	 * Is this a beta version release
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string
	 */
	public $beta = null;

	/**
	 * Error
	 *
	 * @var
	 */
	public $error;

	/**
	 * Create Plugin admin menus, pages, and sub-pages
	 *
	 * @since 1.0.0
	 */
	public function add_licensing() {

		$this->error = $this->set_defaults();

		if ( true !== $this->error ) {

			// Create an admin notices with the error
			add_action( 'admin_notices', array( $this, 'licensing_setup_error' ) );

		} else {

			// include updater
			$updater = Config::get_include( 'EDD_SL_Plugin_Updater.php' );

			include_once $updater;

			add_action( 'admin_init', array( $this, 'plugin_updater' ), 0 );
			add_action( 'admin_menu', array( $this, 'license_menu' ), 60 );
			add_action( 'admin_init', array( $this, 'activate_license' ), 50 );
			add_action( 'admin_init', array( $this, 'deactivate_license' ), 100 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_notices', array( $this, 'show_expiry_notice' ) );
			add_action( 'admin_notices', array( $this, 'tincanny_remind_to_add_license_notice_func' ) );
			add_action( 'uo_notify_admin_of_license_expiry_tincanny', array( $this, 'admin_notices_for_expiry' ) );
			//Add license notice
			add_action(
				'after_plugin_row',
				array(
					$this,
					'plugin_row',
				),
				10,
				3
			);

			add_filter( 'http_request_args', array( $this, 'modify_get_version_http_request_args' ), 12, 2 );
		}
	}

	/**
	 * Expiry notice
	 */
	public function admin_notices_for_expiry() {
		if ( ultc_filter_has_var( 'sl_activation' ) ) {
			return;
		}
		if ( ultc_filter_has_var( 'activated' ) ) {
			return;
		}
		if ( ultc_filter_has_var( 'deactivated' ) ) {
			return;
		}
		$license_data = $this->check_license( true );
	}

	/**
	 * Show expiry notice
	 */
	public function show_expiry_notice() {
		$status = get_option( 'uo_reporting_license_status' ); // $license_data->license will be either "valid", "invalid", "expired", "disabled"
		if ( 'uncanny-reporting-license-activation' !== ultc_get_filter_var( 'page', '' ) ) {
			return;
		}
		if ( empty( $status ) ) {
			return;
		}
		if ( 'expired' !== $status ) {
			return;
		}
		?>
		<div class="notice notice-error
		<?php
		if ( ! $this->is_uo_plugin_page() ) {
			?>
			is-dismissible<?php } ?>">
			<p>
				<?php
				echo $this->expiry_message();
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * License inactive notice
	 */
	public function tincanny_remind_to_add_license_notice_func() {
		$license_key    = get_option( 'uo_reporting_license_key' );
		$license_status = get_option( 'uo_reporting_license_status' ); // $license_data->license will be either "valid", "invalid", "expired", "disabled"
		if ( 'uncanny-reporting-license-activation' !== ultc_get_filter_var( 'page', '' ) ) {
			return;
		}
		if ( ! empty( $license_key ) && ( 'valid' !== $license_status || 'expired' !== $license_status ) ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				echo $this->expiry_message();
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * After Plugin Row license notice
	 *
	 * @param $plugin_name
	 * @param $plugin_data
	 * @param $status
	 */
	public function plugin_row( $plugin_name, $plugin_data, $status ) {
		if ( 'tin-canny-learndash-reporting/tin-canny-learndash-reporting.php' !== $plugin_name ) {
			return;
		}
		$slug    = 'uncanny-learndash-reporting';
		$message = $this->expiry_message();

		if ( empty( $message ) ) {
			return;
		}
		if ( is_network_admin() ) {
			$active_class = is_plugin_active_for_network( $plugin_name ) ? ' active' : '';
		} else {
			$active_class = is_plugin_active( $plugin_name ) ? ' active' : '';
		}

		// Get the columns for this table so we can calculate the colspan attribute.
		$screen  = get_current_screen();
		$columns = get_column_headers( $screen );

		// If something went wrong with retrieving the columns, default to 3 for colspan.
		$colspan = ! is_countable( $columns ) ? 3 : count( $columns );

		$html = '<tr class="plugin-update-tr' . $active_class . '" id="' . $slug . '-update" data-slug="' . $slug . '" data-plugin="' . $plugin_name . '">';
		$html .= '<td colspan="' . $colspan . '" class="plugin-update colspanchange">';
		$html .= '<div class="update-message notice inline notice-warning notice-alt">';
		$html .= '<p>';
		$html .= $message;
		$html .= '</p></div></td></tr>';

		// Apply the class "update" to the plugin row to get rid of the ugly border.
		$html .= "
				<script type='text/javascript'>
					jQuery('#$slug-update').prev('tr').addClass('update');
				</script>
				";

		echo $html;
	}

	/**
	 * Generate the expiry message
	 *
	 * @return string
	 */
	public function expiry_message() {
		$license_data   = $this->check_license();
		$license_key    = get_option( 'uo_reporting_license_key' );
		$license_status = get_option( 'uo_reporting_license_status' ); // $license_data->license will be either "valid", "invalid", "expired", "disabled"
		$license_expiry = get_option( 'uo_reporting_license_expiry' );
		$message        = '';
		$renew_link     = sprintf(
			'<a href="%s" target="_blank">%s</a>',
			$this->store_url . 'checkout/?edd_license_key=' . $license_key . '&download_id=4113&utm_medium=uo_tincanny&utm_campaign=plugins_page',
			__( 'Renew now', 'uncanny-learndash-reporting' )
		);

		if ( 'expired' === $license_status ) {
			$message .= sprintf(
			// translators: %1$s is the plugin name, %2$s is the expiry date, %3$s is the renew link
				_x(
					'Your license for %1$s has expired on %2$s. %3$s to continue to receive updates and support.',
					'License expiry notice',
					'uncanny-learndash-reporting'
				),
				'<strong>Tin Canny Reporting for LearnDash</strong>',
				date( 'F d, Y', strtotime( $license_expiry ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$renew_link
			);
		} elseif ( empty( $license_key ) || ( 'valid' !== $license_status && 'expired' !== $license_status ) ) {
			$message .= sprintf(
				__( "%1\$s your copy of %2\$s to get access to automatic updates and support. Don't have a license key? Click %3\$s to buy one.", 'uncanny-learndash-reporting' ),
				sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=uncanny-reporting-license-activation' ), __( 'Activate', 'uncanny-learndash-reporting' ) ),
				'<strong>Tin Canny Reporting for LearnDash</strong>',
				sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.uncannyowl.com/downloads/tin-canny-reporting/?utm_medium=uo_tincanny&utm_campaign=license_page#pricing', __( 'here', 'uncanny-learndash-reporting' ) )
			);
		}

		return $message;
	}

	/**
	 * Check if the current page is a UO plugin page
	 *
	 * @return bool
	 */
	public function is_uo_plugin_page() {

		return false;
	}

	/**
	 * Set all the defaults for the plugin licensing
	 *
	 * @return bool|string True if success and error message if not
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_defaults() {

		if ( null === $this->page_name ) {
			$this->page_name = strtoupper( Config::get_prefix() ) . ' Licensing';
		}

		if ( null === $this->page_slug ) {
			$this->page_slug = Config::get_prefix() . '-licensing';
		}

		if ( null === $this->parent_slug ) {
			$this->parent_slug = false;
		}

		if ( null === $this->store_url ) {
			return 'Error: Licensed plugin store URL not set.';
		}

		if ( null === $this->item_name ) {
			return 'Error: Licensed plugin item name not set';
		}

		if ( null === $this->author ) {
			$this->author = 'Uncanny Owl';
		}

		if ( null === $this->beta ) {
			$this->beta = false;
		}

		return true;

	}

	/**
	 * Admin Notice to notify that the needed licencing variables have not been set
	 *
	 * @since    1.0.0
	 */
	public function licensing_setup_error() {

		?>
		<div class="notice notice-error is-dismissible">
			<p>There may be an issue with the configuration of Tin Canny Reporting for
				LearnDash.<br><?php echo $this->error; ?></p>
		</div>
		<?php

	}

	/**
	 * Calls the EDD SL Class
	 *
	 * @since    1.0.0
	 */
	public function plugin_updater() {

		// retrieve our license key from the DB
		$license_key = trim( get_option( 'uo_reporting_license_key' ) );

		// setup the updater
		new EDD_SL_Plugin_Updater(
			$this->store_url . '?plugin=' . rawurlencode( UO_REPORTING_ITEM_NAME ) . '&version=' . UNCANNY_REPORTING_VERSION,
			UO_REPORTING_FILE,
			array(
				'version'   => UNCANNY_REPORTING_VERSION,
				'license'   => $license_key,
				'item_name' => $this->item_name,
				'author'    => $this->author,
				'beta'      => $this->beta,
			)
		);

	}

	/**
	 * Add Licensing menu and sub-page
	 *
	 * @since    1.0.0
	 */
	public function license_menu() {

		$parent_slug = 'uncanny-learnDash-reporting';
		// Create a menu page if there is no parent slug
		if ( $this->parent_slug ) {
			//Create a sub menu page
			add_submenu_page(
				$parent_slug,
				$this->page_name,
				'License activation',
				'manage_options',
				$this->page_slug,
				array(
					$this,
					'license_page',
				)
			);
		}

	}

	/**
	 * Sub-page out put
	 *
	 * @since    1.0.0
	 */
	public function license_page() {

		$license = get_option( 'uo_reporting_license_key' );
		$status  = get_option( 'uo_reporting_license_status' ); // $license_data->license will be either "valid", "invalid", "expired", "disabled"

		// Get existing license data for template display
		$license_data = get_option( 'uo_reporting_license_data' );

		// Use transient caching - only check license once every 60 minutes if already active
		$transient_key = 'uo_reporting_license_check_' . md5( $license );
		$should_check_license = false;

		if ( 'valid' === $status ) {
			$last_check = get_transient( $transient_key );
			if ( false === $last_check ) {
				$should_check_license = true;
			}
		}

		// Only check license if:
		// 1. We didn't just activate or deactivate it, AND
		// 2. The license is already active, AND
		// 3. Transient has expired (60 minutes)
		if ( ! ultc_filter_has_var( 'sl_activation' ) && ! ultc_filter_has_var( 'deactivated' ) && $should_check_license ) {
			$fresh_license_data = $this->check_license( true );
			if ( $fresh_license_data ) {
				$license_data = $fresh_license_data;
				update_option( 'uo_reporting_license_data', $license_data );
			}
			// Set transient for 60 minutes
			set_transient( $transient_key, time(), HOUR_IN_SECONDS );
		}

		// Check license status
		// Check license status
		$license_is_active = ( 'valid' === $status ) ? true : false;

		// CSS Classes
		$license_css_classes = array();

		if ( $license_is_active ) {
			$license_css_classes[] = 'tclr-license--active';
		}

		// Set links.
		$where_to_get_my_license = 'https://www.uncannyowl.com/plugin-frequently-asked-questions/?utm_medium=uo_tincanny&utm_campaign=license_page#licensekey';
		$buy_new_license         = 'https://www.uncannyowl.com/downloads/tin-canny-reporting/?utm_medium=uo_tincanny&utm_campaign=license_page#pricing';
		$knowledge_base          = menu_page_url( 'uncanny-tincanny-kb', false );

		include Config::get_template( 'admin-license.php' );
	}

	/**
	 * API call to activate License
	 *
	 * @since    1.0.0
	 */
	public function activate_license() {

		// listen for our activate button to be clicked
		if ( ultc_filter_has_var( 'uo_reporting_license_activate', INPUT_POST ) ) {

			// run a quick security check
			if ( ! check_admin_referer( Config::get_prefix() . '_nonce', Config::get_prefix() . '_nonce' ) ) {
				return;
			} // get out if we didn't click the Activate button

			// Get license key
			$license = ultc_get_filter_var( 'uo_reporting_license_key', '', INPUT_POST );
			if ( empty( $license ) ) {
				return;
			}

			// Save license key
			update_option( 'uo_reporting_license_key', $license );

			// retrieve the license from the database
			$this->license = trim( $license );
			$license_data  = $this->licensing_call( 'activate-license' );

			if ( $license_data ) {
				update_option( 'uo_reporting_license_status', $license_data->license );
				delete_option( 'uo_reporting_license_last_checked' );
				
				// Save complete license data for template display
				update_option( 'uo_reporting_license_data', $license_data );

				$url = add_query_arg(
					array(
						'activated' => time(),
					),
					admin_url( 'admin.php?page=' . $this->page_slug )
				);

				wp_safe_redirect( $url );
				exit;
			}
		}
	}

	/**
	 * API call to de-activate License
	 *
	 * @since    1.0.0
	 */
	public function deactivate_license() {

		// listen for our activate button to be clicked
		if ( ultc_filter_has_var( 'uo_reporting_license_deactivate', INPUT_POST ) ) {

			// run a quick security check
			if ( ! check_admin_referer( Config::get_prefix() . '_nonce', Config::get_prefix() . '_nonce' ) ) {
				return;
			} // get out if we didn't click the Activate button

			// retrieve the license from the database
			$license_data = $this->licensing_call( 'deactivate-license' );

			// $license_data->license will be either "deactivated" or "failed"
			if ( 'deactivated' === $license_data->license ) {
				$license = get_option( 'uo_reporting_license_key' );
				
				// Clear all license-related data
				delete_option( 'uo_reporting_license_status' );
				delete_option( 'uo_reporting_license_last_checked' );
				delete_option( 'uo_reporting_license_data' );
				
				// Clear transient cache
				$transient_key = 'uo_reporting_license_check_' . md5( $license );
				delete_transient( $transient_key );
			}

			$url = add_query_arg(
				array(
					'deactivated' => time(),
				),
				admin_url( 'admin.php?page=' . $this->page_slug )
			);

			wp_safe_redirect( $url );
			exit();
		}
	}


	/**
	 * Load Scripts that are specific to the admin page
	 *
	 * @param string $hook Admin page being loaded
	 *
	 * @since 1.0
	 */
	public function admin_scripts( $hook ) {

		/*
		 * Only load styles if the page hook contains the pages slug
		 *
		 * Hook can be either the toplevel_page_{page_slug} if its a parent  page OR
		 * it can be {parent_slug}_pag_{page_slug} if it is a sub page.
		 * Lets just check if our page slug is in the hook.
		 */
		if ( strpos( $hook, $this->page_slug ) !== false ) {
			// Load Styles for Licensing page located in general plugin styles
			wp_enqueue_style( 'tincanny-admin-tincanny-report-tab', Config::get_src_assets_dist_css_url( 'admin.tincan.report.tab' ), array(), UNCANNY_REPORTING_VERSION );
		}
	}


	/**
	 * This is a means of catching errors from the activation method above and displaying it to the customer
	 *
	 * @since    1.0.0
	 */
	public function admin_notices() {
		if ( ultc_get_filter_var( 'page', '' ) === $this->page_slug ) {
			$activation = ultc_get_filter_var( 'sl_activation', 'not-set' );
			$message    = ultc_get_filter_var( 'message', '' );

			if ( 'not-set' !== $activation && ! empty( $message ) ) {

				switch ( $activation ) {

					case 'false':
						$message = urldecode( $message );

						?>
						<div class="notice notice-error">
							<h3><?php echo $message; ?></h3>
						</div>
						<?php

						break;

					case 'true':
					default:
						?>
						<div class="notice notice-success">
							<p><?php esc_html_e( 'License is activated.', 'uncanny-learndash-reporting' ); ?></p>
						</div>
						<?php
						break;

				}
			}
		}
	}

	/**
	 * API call to check if License key is valid
	 *
	 * The updater class does this for you. This function can be used to do something custom.
	 *
	 * @since    1.0.0
	 */
	public function check_license( $force_check = false ) {

		if ( isset( $_GET['sl_activation'] ) ) {
			return;
		}
		
		// Skip license checking if we just activated or deactivated the license
		if ( ultc_filter_has_var( 'activated' ) ) {
			return;
		}
		if ( ultc_filter_has_var( 'deactivated' ) ) {
			return;
		}
		
		$last_checked = get_option( 'uo_reporting_license_last_checked' );
		if ( ! empty( $last_checked ) && false === $force_check ) {
			$datediff = time() - $last_checked;
			if ( $datediff < DAY_IN_SECONDS ) {
				return null;
			}
		}

		if ( true === $force_check ) {
			delete_option( 'uo_reporting_license_last_checked' );
		}

		$license = trim( get_option( 'uo_reporting_license_key' ) );
		if ( empty( $license ) ) {
			return new \stdClass();
		}

		$license_data = $this->licensing_call();
		$transient_key = 'uo_reporting_license_check_' . md5( $license );

		if ( 'valid' === $license_data->license ) {
			update_option( 'uo_reporting_license_status', $license_data->license );
			// Save complete license data for template display
			update_option( 'uo_reporting_license_data', $license_data );
			
			$expires = 'lifetime' !== $license_data->expires ? $license_data->expires : date( 'Y-m-d H:i:s', mktime( 12, 59, 59, 12, 31, 2099 ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			update_option( 'uo_reporting_license_expiry', $expires );

			if ( 'lifetime' !== $license_data->expires ) {
				$expire_notification = new \DateTime( $license_data->expires, wp_timezone() );
				update_option( 'uo_reporting_license_expiry_notice', $expire_notification );
				// 1 hour after the license is schedule to expire.
				wp_schedule_single_event( $expire_notification->getTimestamp() + 3600, 'uo_notify_admin_of_license_expiry_tincanny' );

			}
		} else {
			update_option( 'uo_reporting_license_status', $license_data->license );
			// Save license data even if not valid (for error display)
			update_option( 'uo_reporting_license_data', $license_data );
			// Clear transient cache when license is not valid so it checks more frequently
			delete_transient( $transient_key );
			// this license is no longer valid
		}
		update_option( 'uo_reporting_license_last_checked', time() );

		return $license_data;
	}

	/**
	 * @param $endpoint
	 *
	 * @return bool|mixed|void|null
	 */
	public function licensing_call( $endpoint = 'check-license' ) {
		if ( empty( $this->license ) ) {

			// retrieve the license from the database
			$license = trim( get_option( 'uo_reporting_license_key' ) );

			if ( empty( $license ) ) {
				return false;
			}

			$this->license = $license;
		}

		$data = array(
			'license' => $this->license,
			'item_id' => $this->item_id,
			'url'     => home_url(),
		);

		// Convert array to JSON and then encode it with Base64
		$encoded_data = base64_encode( wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		// Call the custom API.
		$url = $this->store_url . $endpoint . '?plugin=' . rawurlencode( UO_REPORTING_ITEM_NAME ) . '&version=' . UNCANNY_REPORTING_VERSION;

		$response = wp_remote_post(
			$url,
			array(
				'timeout'   => 10,
				'body'      => '',
				'headers'   => array(
					'X-UO-Licensing'   => $encoded_data,
					'X-UO-Destination' => 'uo',
				),
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = sprintf( __( 'There was an issue in activating your license. Error: %s', 'uncanny-learndash-reporting' ), wp_remote_retrieve_body( $response ) );
			}

			$base_url = admin_url( 'admin.php?page=' . $this->page_slug );
			$redirect = add_query_arg(
				array(
					'sl_activation' => 'false',
					'message'       => rawurlencode( $message ),
				),
				$base_url
			);

			wp_safe_redirect( $redirect );
			exit();
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * @param $request
	 * @param $url
	 *
	 * @return array
	 */
	public function modify_get_version_http_request_args( $request, $url ) {
		// Parse the URL to check if it's the target domain
		$parsed_url = wp_parse_url( $url );

		if ( isset( $parsed_url['host'] ) && 'licensing.uncannyowl.com' === $parsed_url['host'] ) {
			// Check if the body contains 'edd_action' and it's set to 'check_license'
			if ( isset( $request['body']['edd_action'] ) && 'get_version' === $request['body']['edd_action'] ) {
				$data = array();
				// Convert body parameters to headers
				foreach ( $request['body'] as $key => $value ) {
					$data[ $key ] = $value;
				}

				// Convert array to JSON and then encode it with Base64
				$encoded_data                         = base64_encode( wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$request['headers']['X-UO-Licensing'] = $encoded_data;
			}

			// Add `uo` destination for UO plugins
			if ( isset( $request['body']['item_id'] ) && UO_REPORTING_ITEM_ID === (int) $request['body']['item_id'] ) {
				$request['headers']['X-UO-Destination'] = 'uo';
			}
		}

		return $request;
	}
}
