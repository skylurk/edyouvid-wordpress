<?php

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class AdminMenu
 *
 * This class should only be used to inherit classes
 *
 * @package uncanny_ceu
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
	 * @var null
	 */
	public static $license = null;

	/**
	 * @var string
	 */
	public $license_check_key = 'uncanny_ceu_license_check';

	/**
	 * Licensing constructor.
	 */
	public function __construct() {
		include __DIR__ . '/EDD_SL_Plugin_Updater.php';

		// Create sub-page for EDD licensing
		$this->page_name   = __( 'Licensing', 'uncanny-ceu' );
		$this->page_slug   = 'uncanny-ceu';
		$this->parent_slug = 'uncanny-ceu-report';
		$this->store_url   = 'https://licensing.uncannyowl.com/';
		$this->item_name   = 'Uncanny Continuing Education Credits';
		$this->item_id     = 11141;
		$this->author      = 'Uncanny Owl';

		$this->error = $this->set_defaults();

		add_action( 'admin_init', array( $this, 'plugin_updater' ), 0 );
		add_action( 'admin_init', array( $this, 'activate_license' ) );
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'uo_notify_admin_of_license_expiry_ceu', array( $this, 'admin_notices_for_expiry' ) );
		add_action( 'admin_notices', array( $this, 'show_expiry_notice' ) );
		add_action( 'admin_notices', array( $this, 'uo_ceu_remind_to_add_license_notice_func' ) );

		//Add license notice
		add_action( 'ceu_activation_after', array( $this, 'add_cron_to_show_notice' ) );

		add_filter( 'http_request_args', array( $this, 'modify_get_version_http_request_args' ), 12, 2 );

		//add_action( 'uo_ceu_remind_to_add_license', array( $this, 'uo_ceu_remind_to_add_license_func' ) );
		add_action(
			'after_plugin_row',
			array(
				$this,
				'plugin_row',
			),
			10,
			3
		);

		add_action( 'admin_init', array( $this, 'clear_license' ), 99 );
	}

	/**
	 * @return void
	 */
	public function clear_license() {

		if ( ! isset( $_GET['clear_license'] ) ) {
			return;
		}

		if ( 'true' !== esc_attr( $_GET['clear_license'] ) ) {
			return;
		}

		if ( wp_verify_nonce( esc_attr( $_GET['wpnonce'] ), 'uncanny-owl' ) ) {
			delete_option( 'ceu_license_key' );
			delete_option( 'ceu_license_status' );
			delete_option( 'ceu_license_expiry' );
			delete_option( 'ceu_license_expiry_notice' );
			delete_transient( $this->license_check_key );
			self::$license = null;

			add_action( 'admin_notices', array( $this, 'admin_notices_on_clear_license' ) );
		}
	}

	/**
	 * @return void
	 */
	public function admin_notices_on_clear_license() {
		echo '<div class="notice notice-success is-dismissible">
		<h4>' . __( 'License cleared', 'uncanny-ceu' ) . '</h4>
		</div>';
	}

	/**
	 * @param $plugin_name
	 * @param $plugin_data
	 * @param $status
	 */
	public function plugin_row( $plugin_name, $plugin_data, $status ) {
		if ( $plugin_name !== 'uncanny-continuing-education-credits/uncanny-continuing-education-credits.php' ) {
			return;
		}
		$slug           = 'uncanny-ceu';
		$license_key    = self::get_license_key();
		$license_status = self::get_license_status();

		$message = '';


		if ( 'expired' === $license_status ) {
			$message .= sprintf(
				_x(
					'Your license for %s has expired. Click %s to renew.',
					'Your license has expired. Please renew %s license to get instant access to updates and support.',
					'uncanny-ceu'
				),
				'<strong>Uncanny Continuing Education Credits</strong>',
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license_key . '&download_id=1377&utm_medium=uo_ceu&utm_campaign=plugins_page',
					__( 'here', 'uncanny-ceu' )
				)
			);
		} elseif ( empty( $license_key ) || ( 'valid' !== $license_status && 'expired' !== $license_status ) ) {
			$message .= sprintf(
				__( "%s your copy of %s to get access to automatic updates and support. Don't have a license key? Click %s to buy one.", 'uncanny-ceu' ),
				sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=uncanny-ceu' ), __( 'Register', 'uncanny-ceu' ) ),
				'<strong>Uncanny Continuing Education Credits</strong>',
				sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.uncannyowl.com/downloads/uncanny-continuing-education-credits/?utm_medium=uo_ceu&utm_campaign=license_page#pricing', __( 'here', 'uncanny-ceu' ) )
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
		if ( ! wp_get_scheduled_event( 'uo_ceu_remind_to_add_license' ) ) {
			// remind in two weeks to add license
			wp_schedule_single_event( time() + ( ( 3600 * 24 ) * 7 ), 'uo_ceu_remind_to_add_license' );
		}
	}

	/**
	 *
	 */
	public function uo_ceu_remind_to_add_license_notice_func() {
		if ( wp_get_scheduled_event( 'uo_ceu_remind_to_add_license' ) ) {
			return;
		}
		$license_key    = self::get_license_key();
		$license_status = self::get_license_status();
		if ( isset( $_GET['page'] ) && 'uncanny-ceu' === esc_attr( $_GET['page'] ) ) {
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
					__( "%s your copy of %s to get access to automatic updates and support. Don't have a license key? Click %s to buy one.", 'uncanny-ceu' ),
					sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=uncanny-ceu' ), __( 'Register', 'uncanny-ceu' ) ),
					'<strong>Uncanny Continuing Education Credits</strong>',
					sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.uncannyowl.com/downloads/uncanny-continuing-education-credits/?utm_medium=uo_ceu&utm_campaign=admin_header#pricing', __( 'here', 'uncanny-ceu' ) )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function admin_notices_for_expiry() {
		$license_data = $this->check_license();
	}

	/**
	 *
	 */
	public function show_expiry_notice() {
		$status = self::get_license_status();
		if ( isset( $_GET['page'] ) && 'uncanny-ceu' === esc_attr( $_GET['page'] ) ) {
			return;
		}
		if ( empty( $status ) ) {
			return;
		}
		if ( 'expired' !== $status ) {
			return;
		}
		?>
		<div class="notice notice-error <?php if ( ! $this->is_uo_plugin_page() ) { ?>is-dismissible<?php } ?>">
			<p>
				<?php
				$license = self::get_license_key();
				printf(
					_x(
						'Your license for %s has expired. Click %s to renew.',
						'Your license has expired. Please renew %s license to get instant access to updates and support.',
						'uncanny-ceu'
					),
					'<strong>Uncanny Continuing Education Credits</strong>',
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						'https://www.uncannyowl.com/checkout/?edd_license_key=' . $license . '&download_id=1377&utm_medium=uo_ceu&utm_campaign=admin_header',
						__( 'here', 'uncanny-ceu' )
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
		if ( isset( $_GET['page'] ) && preg_match( '/uncanny\-ceu/', $_GET['page'] ) ) {
			return true;
		}

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
			$this->page_name = 'Licensing';
		}

		if ( null === $this->page_slug ) {
			$this->page_slug = 'uncanny-ceu';
		}

		if ( null === $this->parent_slug ) {
			$this->parent_slug = false;
		}

		if ( null === $this->store_url ) {
			return __( 'Error: Licensed plugin store URL not set.', 'uncanny-ceu' );
		}

		if ( null === $this->item_name ) {
			return __( 'Error: Licensed plugin item name not set', 'uncanny-ceu' );
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
			<p><?php printf( __( 'There may be an issue with the configuration of %s.', 'uncanny-ceu' ), Utilities::get_plugin_name() ); ?>
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

		// No License key found. Bailout.
		if( false === $license_key ) {
			return;
		}

		// setup the updater
		new EDD_SL_Plugin_Updater(
			$this->store_url . '?plugin=' . rawurlencode( UNCANNY_CEUS_ITEM_NAME ) . '&version=' . UNCANNY_CEUS_VERSION,
			UNCANNY_CEUS_FILE,
			array(
				'version'   => UNCANNY_CEUS_VERSION,
				'license'   => $license_key,
				'item_name' => UNCANNY_CEUS_ITEM_NAME,
				'item_id'   => UNCANNY_CEUS_ITEM_ID,
				'author'    => $this->author,
				'beta'      => $this->beta,
			)
		);

	}

	/**
	 * Sub-page out put
	 *
	 * @since    1.0.0
	 */
	public function license_page() {

		$license      = self::get_license_key();
		$status       = self::get_license_status(); // $license_data->license will be either "valid", "invalid", "expired", "disabled"
		$license_data = $this->check_license();

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
			$license_css_classes[] = 'ucec-license--active';
		}

		// Set links.
		$where_to_get_my_license = 'https://www.uncannyowl.com/plugin-frequently-asked-questions/?utm_medium=uo_ceu&utm_campaign=license_page#licensekey';
		$buy_new_license         = 'https://www.uncannyowl.com/downloads/uncanny-continuing-education-credits/?utm_medium=uo_ceu&utm_campaign=license_page#pricing';
		$knowledge_base          = menu_page_url( 'uncanny-ceu-kb', false );

		include Utilities::get_template( 'admin-license.php' );
	}

	/**
	 * API call to activate License
	 *
	 * @since    1.0.0
	 */
	public function activate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST['ceu_license_activate'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'ceu_nonce', 'ceu_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		// Save license key
		$license = esc_attr( trim( $_POST['ceu_license_key'] ) );

		if ( empty( $license ) ) {
			$base_url = admin_url( 'admin.php?page=' . $this->page_slug );
			$redirect = add_query_arg(
				array(
					'sl_activation' => 'false',
					'msg'           => urlencode( __( 'Please enter a valid license code and click "Activate now".', 'uncanny-ceu' ) ),
				),
				$base_url
			);

			wp_safe_redirect( $redirect ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit();
		}

		update_option( 'ceu_license_key', $license );
		self::$license = $license;

		$license_data = $this->licensing_call( 'activate-license' );

		if ( $license_data ) {
			update_option( 'ceu_license_status', $license_data->license );
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
		if ( ! isset( $_POST['ceu_license_deactivate'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'ceu_nonce', 'ceu_nonce' ) ) {
			return;
		}

		$license_data = $this->licensing_call( 'deactivate-license' );

		if ( 'deactivated' === $license_data->license ) {
			delete_option( 'ceu_license_status' );
			delete_transient( $this->license_check_key );
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
			wp_enqueue_script( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.js' ), [ 'jquery' ], Utilities::get_version(), true );
			wp_enqueue_style( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.css' ), [], Utilities::get_version() );
		}

	}


	/**
	 * This is a means of catching errors from the activation method above and displaying it to the customer
	 *
	 * @since    1.0.0
	 */
	public function admin_notices() {

		if ( isset( $_GET['page'] ) && $this->page_slug === esc_attr( $_GET['page'] ) ) {

			if ( isset( $_GET['sl_activation'] ) && ! empty( esc_html( $_GET['msg'] ) ) ) {

				switch ( $_GET['sl_activation'] ) {

					case 'false':

						$message = urldecode( wp_kses( $_GET['msg'], [] ) );

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
							<h3><?php _e( 'License is activated.', 'uncanny-ceu' ); ?></h3>
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
		if ( isset( $_GET['sl_activation'] ) || isset( $_GET['deactivated'] ) || isset( $_GET['activated'] ) ) {
			return;
		}

		$response = get_transient( $this->license_check_key );

		if ( ! empty( $response ) && ! empty( self::get_license_key() ) ) {
			$license_data = $response;
		} else {
			$license_data = $this->licensing_call( 'check-license' );
		}

		if ( empty( $license_data ) ) {
			return;
		}

		// this license is still valid
		if ( $license_data->license == 'valid' ) {
			if ( 'lifetime' !== $license_data->expires ) {
				update_option( 'ceu_license_expiry', $license_data->expires );
			} else {
				update_option( 'ceu_license_expiry', date( 'Y-m-d H:i:s', mktime( 12, 59, 59, 12, 31, 2099 ) ) );
			}

			if ( 'lifetime' !== $license_data->expires ) {
				$expire_notification = new \DateTime( $license_data->expires, wp_timezone() );
				update_option( 'ceu_license_expiry_notice', $expire_notification );
				if ( wp_get_scheduled_event( 'uo_notify_admin_of_license_expiry_ceu' ) ) {
					wp_unschedule_hook( 'uo_notify_admin_of_license_expiry_ceu' );
				}
				// 1 hour after the license is schedule to expire.
				wp_schedule_single_event( $expire_notification->getTimestamp() + 3600, 'uo_notify_admin_of_license_expiry_ceu' );

			}
			wp_unschedule_hook( 'uo_ceu_remind_to_add_license' );
		}

		return $license_data;
	}

	/**
	 * @param $endpoint
	 *
	 * @return bool|mixed|void|null
	 */
	public function licensing_call( $endpoint = 'check-license' ) {
		// retrieve the license from the database
		$license = self::get_license_key();

		if ( empty( $license ) ) {
			delete_transient( $this->license_check_key );

			return false;
		}

		$data = array(
			'license' => $license,
			'item_id' => UNCANNY_CEUS_ITEM_ID,
			'url'     => home_url(),
		);


		$previous_endpoint = $endpoint;

		$current_status = self::get_license_status();

		if ( empty( $current_status ) && 'check-license' === $endpoint && ! filter_has_var( INPUT_GET, 'sl_activation' ) ) {
			$endpoint = 'activate-license';
		}

		// Convert array to JSON and then encode it with Base64
		$encoded_data = base64_encode( wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		// Call the custom API.
		$url = $this->store_url . $endpoint . '?plugin=' . rawurlencode( UNCANNY_CEUS_ITEM_NAME ) . '&version=' . UNCANNY_CEUS_VERSION;

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

		if ( ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) && 'check-license' !== $previous_endpoint ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = sprintf( __( 'There was an issue in activating your license. Error: %s', 'uncanny-ceu' ), wp_remote_retrieve_body( $response ) );
			}

			$base_url = admin_url( 'admin.php?page=' . $this->page_slug );
			$redirect = add_query_arg(
				array(
					'sl_activation' => 'false',
					'msg'           => rawurlencode( $message ),
				),
				$base_url
			);

			wp_redirect( $redirect ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit();
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data ) {
			update_option( 'ceu_license_status', $license_data->license );
		}

		set_transient( $this->license_check_key, $license_data, DAY_IN_SECONDS );

		return $license_data;
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
			if ( isset( $request['body']['item_id'] ) && $this->item_id === (int) $request['body']['item_id'] ) {
				$request['headers']['X-UO-Destination'] = 'uo';
			}
		}

		return $request;
	}

	/**
	 * @return string
	 */
	public static function get_license_key() {
		if ( defined( 'UNCANNY_CEUS_LICENSE_KEY' ) && ! empty( UNCANNY_CEUS_LICENSE_KEY ) ) {
			return UNCANNY_CEUS_LICENSE_KEY;
		}

		if ( ! empty( self::$license ) ) {
			return self::$license;
		}

		// retrieve the license from the database
		$license = trim( get_option( 'ceu_license_key', '' ) );

		if ( empty( $license ) ) {
			return false;
		}

		self::$license = $license;

		return self::$license;
	}

	/**
	 * @return false|mixed|null
	 */
	public static function get_license_status() {
		return get_option( 'ceu_license_status', '' );
	}
}

AdminPage::$licensing_class = new Licensing();
