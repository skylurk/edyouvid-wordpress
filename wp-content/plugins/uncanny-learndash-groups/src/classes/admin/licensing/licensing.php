<?php

namespace uncanny_learndash_groups;

if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 *
 */
define( 'UNCANNY_GROUPS_LICENSE_KEY', '' );

/**
 * Class AdminMenu
 *
 * This class should only be used to inherit classes
 *
 * @package uncanny_learndash_groups
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
	 * @var int|null
	 */
	public $item_id = null;

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
	 * @var bool|string|null
	 */
	public $error = null;

	/**
	 * @var string
	 */
	public $license = '';
	/**
	 * @var string
	 */
	public $license_check_key = 'ulgm_license_check';

	/**
	 * Licensing constructor.
	 */
	public function __construct() {
		// Initialize licensing on a late init hook.
		add_action( 'init', array( $this, 'init_licensing' ), PHP_INT_MAX );
	}

	public function init_licensing() {

		include __DIR__ . '/EDD_SL_Plugin_Updater.php';

		// Create sub-page for EDD licensing
		$this->page_name   = __( 'Licensing', 'uncanny-learndash-groups' );
		$this->page_slug   = 'uncanny-groups';
		$this->parent_slug = 'uncanny-groups';
		$this->store_url   = 'https://licensing.uncannyowl.com/';
		$this->item_name   = 'Uncanny LearnDash Groups';
		$this->item_id     = 13839;
		$this->author      = 'Uncanny Owl';
		$this->license     = '';
		$this->error       = $this->set_defaults();

		if ( true !== $this->error ) {

			// Create an admin notices with the error
			add_action( 'admin_notices', array( $this, 'licensing_setup_error' ) );

		} else {

			add_action( 'admin_init', array( $this, 'plugin_updater' ), 0 );
			add_action( 'admin_menu', array( $this, 'license_menu' ), 199 );
			add_action( 'admin_init', array( $this, 'activate_license' ) );
			add_action( 'admin_init', array( $this, 'deactivate_license' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'uo_notify_admin_of_license_expiry_groups', array( $this, 'admin_notices_for_expiry' ) );
			add_action( 'admin_notices', array( $this, 'show_expiry_notice' ) );
			add_action( 'admin_notices', array( $this, 'uo_remind_to_add_license_notice_func' ) );
			add_action( 'admin_init', array( $this, 'clear_license' ) );
			//Add license notice
			add_action( 'ulgm_activation_after', array( $this, 'add_cron_to_show_notice' ) );
			add_action( 'uo_remind_to_add_license', array( $this, 'uo_remind_to_add_license_func' ) );

			add_filter( 'http_request_args', array( $this, 'modify_get_version_http_request_args' ), 12, 2 );

			add_action(
				'after_plugin_row',
				array(
					$this,
					'plugin_row',
				),
				10,
				3
			);
		}
	}

	/**
	 * @return void
	 */
	public function clear_license() {
		if ( ulgm_filter_has_var( 'clear_license' ) && 'true' === ulgm_filter_input( 'clear_license' ) && wp_verify_nonce( ulgm_filter_input( 'wpnonce' ), 'uncanny-owl' ) ) {
			delete_option( 'ulgm_license_key' );
			delete_option( 'ulgm_license_status' );
			delete_option( 'ulgm_license_expiry' );
			delete_option( 'ulgm_license_expiry_notice' );
			delete_transient( $this->license_check_key );

			add_action( 'admin_notices', array( $this, 'admin_notices_on_clear_license' ) );
		}
	}

	/**
	 * @return void
	 */
	public function admin_notices_on_clear_license() {
		echo '<div class="notice notice-success is-dismissible">
		<h4>' . __( 'License cleared', 'uncanny-learndash-groups' ) . '</h4>
		</div>';
	}

	/**
	 * @param $plugin_name
	 * @param $plugin_data
	 * @param $status
	 */
	public function plugin_row( $plugin_name, $plugin_data, $status ) {
		if ( $plugin_name !== 'uncanny-learndash-groups/uncanny-learndash-groups.php' ) {
			return;
		}
		$slug           = 'uncanny-learndash-groups';
		$license_key    = self::get_license_key();
		$license_status = get_option( 'ulgm_license_status', '' );

		$message = '';

		if ( 'expired' === $license_status ) {
			$message .= sprintf(
				_x(
					'Your license for %1$s has expired. Click %2$s to renew.',
					'Your license has expired. Please renew %s license to get instant access to updates and support.',
					'uncanny-learndash-groups'
				),
				'<strong>Uncanny Groups for LearnDash</strong>',
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license_key . '&download_id=1377&utm_medium=uo_groups&utm_campaign=plugins_page',
					__( 'here', 'uncanny-learndash-groups' )
				)
			);
		} elseif ( empty( $license_key ) || ( 'valid' !== $license_status && 'expired' !== $license_status ) ) {
			$message .= sprintf(
				__( "%1\$s your copy of %2\$s to get access to automatic updates and support. Don't have a license key? Click %3\$s to buy one.", 'uncanny-learndash-groups' ),
				sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=uncanny-groups' ), __( 'Register', 'uncanny-learndash-groups' ) ),
				'<strong>Uncanny Groups</strong>',
				sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.uncannyowl.com/downloads/uncanny-learndash-groups/?utm_medium=uo_groups&utm_campaign=license_page#pricing', __( 'here', 'uncanny-learndash-groups' ) )
			);
		}

		if ( ! empty( $message ) ) {
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

			echo '<tr class="plugin-update-tr' . $active_class . '" id="' . $slug . '-update" data-slug="' . $slug . '" data-plugin="' . $plugin_name . '">';
			echo '<td colspan="' . $colspan . '" class="plugin-update colspanchange">';
			echo '<div class="update-message notice inline notice-warning notice-alt">';
			echo '<p>';
			echo $message;
			echo '</p></div></td></tr>';

			// Apply the class "update" to the plugin row to get rid of the ugly border.
			echo "
				<script type='text/javascript'>
					jQuery('#$slug-update').prev('tr').addClass('update');
				</script>
				";
		}
	}

	/**
	 *
	 */
	public function add_cron_to_show_notice() {
		if ( ! wp_get_scheduled_event( 'uo_remind_to_add_license' ) ) {
			// remind in two weeks to add license
			wp_schedule_single_event( time() + ( ( 3600 * 24 ) * 7 ), 'uo_remind_to_add_license' );
		}
	}

	/**
	 *
	 */
	public function uo_remind_to_add_license_notice_func() {
		if ( wp_get_scheduled_event( 'uo_remind_to_add_license' ) ) {
			return;
		}
		$license_key    = self::get_license_key();
		$license_status = get_option( 'ulgm_license_status', '' );
		if ( ulgm_filter_has_var( 'page' ) && 'uncanny-groups' === ulgm_filter_input( 'page' ) ) {
			return;
		}
		if ( ! empty( $license_key ) && ( 'valid' !== $license_status || 'expired' !== $license_status ) ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					__( "%1\$s your copy of %2\$s to get access to automatic updates and support. Don't have a license key? Click %3\$s to buy one.", 'uncanny-learndash-groups' ),
					sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=uncanny-groups' ), __( 'Register', 'uncanny-learndash-groups' ) ),
					'<strong>Uncanny Groups</strong>',
					sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.uncannyowl.com/downloads/uncanny-learndash-groups/?utm_medium=uo_groups&utm_campaign=admin_header#pricing', __( 'here', 'uncanny-learndash-groups' ) )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add Licensing menu and sub-page
	 *
	 * @since    1.0.0
	 */
	public function license_menu() {

		$parent_slug = 'uncanny-groups-create-group';
		//Create a sub menu page
		//      add_submenu_page(
		//          $parent_slug,
		//          $this->page_name,
		//          __( 'License activation', 'uncanny-learndash-groups' ),
		//          'manage_options',
		//          $this->page_slug,
		//          array(
		//              $this,
		//              'license_page',
		//          )
		//      );
	}

	/**
	 *
	 */
	public function admin_notices_for_expiry() {
		if ( ulgm_filter_has_var( 'sl_activation' ) ) {
			return;
		}
		if ( ulgm_filter_has_var( 'activated' ) ) {
			return;
		}
		if ( ulgm_filter_has_var( 'deactivated' ) ) {
			return;
		}
		$this->check_license();
	}

	/**
	 *
	 */
	public function show_expiry_notice() {
		$status = get_option( 'ulgm_license_status', '' );
		if ( ulgm_filter_has_var( 'page' ) && 'uncanny-groups' === ulgm_filter_input( 'page' ) ) {
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
				$license = self::get_license_key();
				printf(
					_x(
						'Your license for %1$s has expired. Click %2$s to renew.',
						'Your license has expired. Please renew %s license to get instant access to updates and support.',
						'uncanny-learndash-groups'
					),
					'<strong>Uncanny Groups for LearnDash</strong>',
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377&utm_medium=uo_groups&utm_campaign=admin_header',
						__( 'here', 'uncanny-learndash-groups' )
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * @return bool
	 */
	public function is_uo_plugin_page() {
		if ( ulgm_filter_has_var( 'page' ) && preg_match( '/uncanny\-groups/', ulgm_filter_input( 'page' ) ) ) {
			return true;
		}
		// License page
		//      if ( ulgm_filter_has_var( 'page' ) && preg_match( '/uncanny\-learndash\-groups/', ulgm_filter_input( 'page' ) ) ) {
		//          return true;
		//      }

		return false;
	}

	/**
	 * Set all the defaults for the plugin licensing
	 *
	 * @return bool|string True if success and error message if not
	 * @since    1.0.0
	 * @access   private
	 *
	 */
	private function set_defaults() {

		if ( null === $this->page_name ) {
			$this->page_name = 'Uncannny Groups Licensing';
		}

		if ( null === $this->page_slug ) {
			$this->page_slug = 'ulgm-licensing';
		}

		if ( null === $this->parent_slug ) {
			$this->parent_slug = false;
		}

		if ( null === $this->store_url ) {
			return __( 'Error: Licensed plugin store URL not set.', 'uncanny-learndash-groups' );
		}

		if ( null === $this->item_id ) {
			return __( 'Error: Licensed plugin item id not set', 'uncanny-learndash-groups' );
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
			<p><?php printf( __( 'There may be an issue with the configuration of %s.', 'uncanny-learndash-groups' ), Utilities::get_plugin_name() ); ?>
				<br><?php echo $this->error; ?></p>
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
		$license_key = self::get_license_key();

		// setup the updater
		new EDD_SL_Plugin_Updater(
			$this->store_url,
			UNCANNY_GROUPS_PLUGIN_FILE,
			array(
				'version' => UNCANNY_GROUPS_VERSION,
				'license' => $license_key,
				'item_id' => $this->item_id,
				'author'  => $this->author,
				'beta'    => $this->beta,
			)
		);
	}

	/**
	 * Sub-page out put
	 *
	 * @since    1.0.0
	 */
	public function license_page() {
		if ( ! ulgm_filter_has_var( 'sl_activation' ) && ! ulgm_filter_has_var( 'deactivated' ) ) {
			$license_data = $this->check_license();
		}

		$status  = '';
		$license = self::get_license_key();

		if ( isset( $license_data->license ) ) {
			$status = $license_data->license;
		}

		// Check if license is not set
		if ( empty( $license ) ) {
			$license_data = array();
			$status       = '';
		}

		// Check license status
		$license_is_active = ( 'valid' === $status ) ? true : false;

		// CSS Classes
		$license_css_classes = array();

		if ( $license_is_active ) {
			$license_css_classes[] = 'ulgm-license--active';
		}

		// Set links.
		$where_to_get_my_license = 'https://www.uncannyowl.com/plugin-frequently-asked-questions/?utm_medium=uo_groups&utm_campaign=license_page#licensekey';
		$buy_new_license         = 'https://www.uncannyowl.com/downloads/uncanny-learndash-groups/?utm_medium=uo_groups&utm_campaign=license_page';
		$knowledge_base          = menu_page_url( 'uncanny-groups-kb', false );

		include __DIR__ . '/admin-license.php';
	}

	/**
	 * API call to activate License
	 *
	 * @since    1.0.0
	 */
	public function activate_license() {

		// listen for our activate button to be clicked
		if ( ! ulgm_filter_has_var( 'ulgm_license_activate', INPUT_POST ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'ulgm_nonce', 'ulgm_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		// Save license key
		if ( ulgm_filter_has_var( 'ulgm_license_key', INPUT_POST ) ) {
			$license = sanitize_text_field( trim( ulgm_filter_input( 'ulgm_license_key', INPUT_POST ) ) );
			update_option( 'ulgm_license_key', $license );
			$this->license = $license;
		} else {
			$this->license = self::get_license_key();
		}

		$license_data = $this->licensing_call( 'activate-license' );

		if ( $license_data ) {
			update_option( 'ulgm_license_status', $license_data->license );
		}

		$url = add_query_arg(
			array(
				'activated' => time(),
			),
			admin_url( 'admin.php?page=' . $this->page_slug )
		);

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * API call to de-activate License
	 *
	 * @since    1.0.0
	 */
	public function deactivate_license() {

		// listen for our activate button to be clicked
		if ( ! ulgm_filter_has_var( 'ulgm_license_deactivate', INPUT_POST ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'ulgm_nonce', 'ulgm_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		delete_transient( $this->license_check_key );

		$license_data = $this->licensing_call( 'deactivate-license' );

		if ( 'deactivated' === $license_data->license ) {
			delete_option( 'ulgm_license_status' );
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


	/**
	 * Load Scripts that are specific to the admin page
	 *
	 * @param string $hook Admin page being loaded
	 *
	 * @since 1.0
	 *
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
			wp_enqueue_style( 'ulgm-backend', Utilities::get_asset( 'backend', 'bundle.min.css' ), array(), Utilities::get_version() );
		}
	}


	/**
	 * This is a means of catching errors from the activation method above and displaying it to the customer
	 *
	 * @since    1.0.0
	 */
	public function admin_notices() {

		if ( ulgm_filter_has_var( 'page' ) && $this->page_slug == ulgm_filter_input( 'page' ) ) {

			if ( ulgm_filter_has_var( 'sl_activation' ) && ! empty( ulgm_filter_input( 'message' ) ) ) {

				switch ( ulgm_filter_input( 'sl_activation' ) ) {

					case 'false':
						$message = urldecode( esc_html__( wp_kses( ulgm_filter_input( 'message' ), array() ), 'uncanny-learndash-groups' ) );

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
							<p><?php _e( 'License is activated.', 'uncanny-learndash-groups' ); ?></p>
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
	public function check_license() {
		$response = get_transient( $this->license_check_key );

		if ( ! empty( $response ) ) {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		} else {
			$license_data = $this->licensing_call( 'check-license' );
		}

		if ( ! isset( $license_data->license ) ) {
			return array();
		}
		// this license is still valid
		if ( 'valid' === $license_data->license ) {
			update_option( 'ulgm_license_status', $license_data->license );
			if ( 'lifetime' !== $license_data->expires ) {
				update_option( 'ulgm_license_expiry', $license_data->expires );
			} else {
				update_option( 'ulgm_license_expiry', date( 'Y-m-d H:i:s', mktime( 12, 59, 59, 12, 31, 2099 ) ) );
			}

			if ( 'lifetime' !== $license_data->expires ) {
				$expire_notification = new \DateTime( $license_data->expires, wp_timezone() );
				update_option( 'ulgm_license_expiry_notice', $expire_notification );
				if ( wp_get_scheduled_event( 'uo_notify_admin_of_license_expiry_groups' ) ) {
					wp_unschedule_hook( 'uo_notify_admin_of_license_expiry_groups' );
				}
				// 1 hour after the license is schedule to expire.
				wp_schedule_single_event( $expire_notification->getTimestamp() + 3600, 'uo_notify_admin_of_license_expiry_groups' );

			}
			wp_unschedule_hook( 'uo_remind_to_add_license' );
		}

		return $license_data;
	}


	/**
	 * @param $endpoint
	 *
	 * @return bool|object
	 */
	public function licensing_call( $endpoint = 'check-license' ) {
		$license = self::get_license_key();

		if ( empty( $license ) ) {
			return (object) array();
		}

		$current_status = get_option( 'ulgm_license_status', '' );

		$previous_endpoint = $endpoint;

		if ( empty( $current_status ) && 'check-license' === $endpoint && ! ulgm_filter_has_var( 'sl_activation' ) ) {
			$endpoint = 'activate-license';
		}

		$data = array(
			'license' => $license,
			'item_id' => $this->item_id,
			'url'     => home_url(),
		);

		// Convert array to JSON and then encode it with Base64
		$encoded_data = base64_encode( wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$url = $this->store_url . $endpoint . '?plugin=' . rawurlencode( $this->item_name ) . '&version=' . UNCANNY_GROUPS_VERSION;

		// Call the custom API.
		$response = wp_remote_post(
			$url,
			array(
				'timeout'   => 15,
				'body'      => '',
				'headers'   => array(
					'X-UO-Licensing'   => $encoded_data,
					'X-UO-Destination' => 'uo',
				),
				'sslverify' => true,
			)
		);

		if ( ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) && 'check-license' !== $previous_endpoint ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = sprintf( __( 'There was an issue in activating your license. Error: %s', 'uncanny-learndash-groups' ), wp_remote_retrieve_body( $response ) ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
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

		if ( 'deactivate-license' !== $endpoint ) {
			set_transient( $this->license_check_key, $response, DAY_IN_SECONDS );
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
			if ( isset( $request['body']['item_id'] ) && (int) $this->item_id === (int) $request['body']['item_id'] ) {
				$request['headers']['X-UO-Destination'] = 'uo';
			}
		}

		return $request;
	}

	/**
	 * @return string
	 */
	public static function get_license_key() {
		if ( defined( 'UNCANNY_GROUPS_LICENSE_KEY' ) && ! empty( UNCANNY_GROUPS_LICENSE_KEY ) ) {
			return UNCANNY_GROUPS_LICENSE_KEY;
		}
		$license = trim( get_option( 'ulgm_license_key' ) );

		if ( empty( $license ) ) {
			return '';
		}

		return $license;
	}
}
