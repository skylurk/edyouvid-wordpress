<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

/**
 *
 */
class Boot extends toolkit\Config {

	public static $license_check_key = 'uo_license_check_cache';

	/**
	 * class constructor
	 */
	public function __construct() {

		/* Licensing */

		// URL of store powering the plugin
		define( 'UO_STORE_URL', 'https://licensing.uncannyowl.com/' );

		// Store download name/title
		define( 'UO_ITEM_NAME', 'Uncanny LearnDash Toolkit Pro' );

		define( 'UO_ITEM_ID', 1377 );
		// the name of the settings page for the license input to be displayed
		$compare_version = version_compare( UNCANNY_TOOLKIT_VERSION, '3.7', '<=' );

		if ( $compare_version ) {
			define( 'UO_LICENSE_PAGE', 'uncanny-toolkit-license' );
		} else {
			define( 'UO_LICENSE_PAGE', 'uncanny-toolkit' );
		}


		global $uncanny_pro_toolkit;

		if ( ! isset( $uncanny_pro_toolkit ) ) {
			$uncanny_pro_toolkit = new \stdClass();
		}

		// We need to check if spl auto loading is available when activating plugin
		// Plugin will not activate if SPL extension is not enabled by throwing error
		if ( ! extension_loaded( 'SPL' ) ) {
			$spl_error = esc_html__( 'Please contact your hosting company to update to php version 5.3+ and enable spl extensions.', 'uncanny-pro-toolkit' );
			trigger_error( $spl_error, E_USER_ERROR );
		}

		spl_autoload_register( array( __CLASS__, 'auto_loader' ) );

		// Class Details:  Add Class to Admin Menu page
		$classes = self::get_active_classes();

		// Import Gutenberg Blocks
		require_once UNCANNY_TOOLKIT_PRO_PATH . 'src/blocks/blocks.php';
		new Blocks( UNCANNY_TOOLKIT_PRO_PREFIX, UNCANNY_TOOLKIT_PRO_VERSION, $classes );

		if ( $classes ) {
			foreach ( self::get_active_classes() as $class ) {

				// Some wp installs remove slashes during db calls, being extra safe when comparing DB vs php values
				if ( strpos( $class, '\\' ) === false ) {
					$class = str_replace( 'pro_toolkit', 'pro_toolkit\\', $class );
				}

				$class_namespace = explode( '\\', $class );

				if ( class_exists( $class ) && __NAMESPACE__ === $class_namespace[0] ) {
					new $class();
				}
			}
		}

		// include updater
		$updater = self::get_include( 'EDD_SL_Plugin_Updater.php', __FILE__ );
		include_once $updater;

		add_action( 'admin_init', array( __CLASS__, 'uo_plugin_updater' ), 0 );
		add_action( 'admin_menu', array( __CLASS__, 'uo_license_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'uo_activate_license' ) );
		add_action( 'admin_init', array( __CLASS__, 'uo_deactivate_license' ) );
		add_action( 'admin_init', array( __CLASS__, 'clear_license' ) );
		add_action( 'admin_notices', array( __CLASS__, 'uo_admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'uo_license_css' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'uo_enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'uo_enqueue_backend_assets' ) );

		// Register the endpoint to hide the "Try Automator"
		add_action( 'rest_api_init', array( __CLASS__, 'try_automator_rest_register' ) );
		// Evaluate the visibility of the "Try Automator" item
		add_filter(
			'ult_admin_sidebar_try_automator_add',
			array(
				__CLASS__,
				'try_automator_evaluate_visibility',
			),
			100
		);
		// Modify the inner html of the "Try Automator! admin item"
		add_filter( 'ult_admin_sidebar_try_automator_inner_html', array( __CLASS__, 'try_automator_add_x_icon' ) );

		add_filter( 'http_request_args', array( __CLASS__, 'modify_get_version_http_request_args' ), 12, 2 );

		add_action( 'ult_before_directory_actions', array( __CLASS__, 'add_pro_license_box' ), 10, 1 );
	}

	/**
	 * @return void
	 */
	public static function uo_license_css() {
		$style_url = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/legacy/backend/css/license.css';
		wp_enqueue_style( 'uo-license-css', $style_url, false, UNCANNY_TOOLKIT_PRO_VERSION );
	}


	/**
	 * @return void
	 */
	public static function uo_plugin_updater() {

		// retrieve our license key from the DB
		$license_key = self::get_license_key();

		// setup the updater
		new EDD_SL_Plugin_Updater(
			UO_STORE_URL,
			UO_FILE,
			array(

				'version'   => UNCANNY_TOOLKIT_PRO_VERSION,
				// current version number
				'license'   => $license_key,
				// license key (used get_option above to retrieve from DB)
				'item_id'   => UO_ITEM_ID,
				// name of this plugin
				'item_name' => UO_ITEM_NAME,
				// name of this plugin
				'author'    => 'Uncanny Owl',
				// author of this plugin
				'beta'      => false,
			)
		);
	}

	// Licence options page

	/**
	 * @return void
	 */
	public static function uo_license_menu() {
		$version = version_compare( UNCANNY_TOOLKIT_VERSION, '3.7', '<=' );
		if ( ! $version ) {
			return;
		}
		add_submenu_page(
			'uncanny-toolkit',
			'Uncanny Pro License Activation',
			'License Activation',
			'manage_options',
			UO_LICENSE_PAGE,
			array(
				__CLASS__,
				'uo_license_page',
			)
		);
	}

	/**
	 * @return bool
	 */
	public static function is_defined_license_key() {
		if ( defined( 'UNCANNY_TOOLKIT_PRO_LICENSE_KEY' ) && ! empty( UNCANNY_TOOLKIT_PRO_LICENSE_KEY ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public static function should_check_license() {

		if ( self::is_defined_license_key() ) {
			return true;
		}

		if ( isset( $_GET['sl_activation'] ) ) {
			return false;
		}
		if ( isset( $_GET['deactivated'] ) ) {
			return false;
		}

		$current_status = get_option( 'uo_license_status', '' );
		if ( empty( $current_status ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return false|void
	 */
	public static function uo_license_page( $from_module = false ) {

		if ( self::should_check_license() ) {

			$license_data = self::licensing_call( 'check-license' );

			if ( 'valid' !== $license_data->license ) {
				// $license_data->license will be either "valid", "invalid", "expired", "disabled", or "inactive"
				update_option( 'uo_license_status', $license_data->license );
			}

			update_option( 'uo_license_check', $license_data->license );

			// Get data
			$status        = $license_data->license;
			$license_check = $license_data->license;
		} else {
			$status        = get_option( 'uo_license_status' );
			$license_check = get_option( 'uo_license_check' );
		}

		// retrieve the license from the database
		$license = self::get_license_key();
		if ( empty( $license ) ) {
			delete_option( 'uo_license_status' );
			delete_option( 'uo_license_check' );
		}

		// Check license status
		$license_is_active = $status == 'valid' ? true : false;

		// CSS Classes
		$css_classes = array();

		if ( $license_is_active ) {
			$css_classes[] = 'uo-license--active';
		}

		// Set links. Add UTM parameters at the end of each URL
		$where_to_get_my_license = 'https://www.uncannyowl.com/plugin-frequently-asked-questions/#licensekey';
		$buy_new_license         = 'https://www.uncannyowl.com/downloads/uncanny-learndash-toolkit-pro/';
		$knowledge_base          = menu_page_url( 'uncanny-toolkit-kb', false );

		if ( class_exists( '\uncanny_learndash_toolkit\Config' ) && method_exists( '\uncanny_learndash_toolkit\Config', 'utm_parameters' ) ) {
			$where_to_get_my_license = \uncanny_learndash_toolkit\Config::utm_parameters( $where_to_get_my_license, 'license-activation', 'where-to-get-license' );
			$buy_new_license         = \uncanny_learndash_toolkit\Config::utm_parameters( $buy_new_license, 'license-activation', 'buy-license-button' );
		}

		include_once __DIR__ . '/includes/admin-license.php';
	}

	/**
	 * @return void
	 */
	public static function uo_enqueue_frontend_assets() {
		$assets_url = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/dist/';

		$uncanny_toolkit_pro = array(
			'restURL' => esc_url_raw( rest_url() . 'uo_toolkit/v1/' ),
			'nonce'   => \wp_create_nonce( 'wp_rest' ),
		);

		wp_register_script( 'ultp-frontend', $assets_url . 'frontend/bundle.min.js', array( 'jquery' ), UNCANNY_TOOLKIT_PRO_VERSION );
		wp_localize_script( 'ultp-frontend', 'UncannyToolkitPro', $uncanny_toolkit_pro );
		wp_enqueue_script( 'ultp-frontend' );

		wp_enqueue_style( 'ultp-frontend', $assets_url . 'frontend/bundle.min.css', false, UNCANNY_TOOLKIT_PRO_VERSION );
	}

	/**
	 * @return void
	 */
	public static function uo_enqueue_backend_assets() {
		$assets_url = plugins_url( basename( dirname( UO_FILE ) ) ) . '/src/assets/dist/';

		$uncanny_toolkit_pro = array(
			'restURL' => esc_url_raw( rest_url() . 'uo_pro/v1/' ),
			'nonce'   => \wp_create_nonce( 'wp_rest' ),
			'i18n'    => array(
				'all' => esc_attr__( 'All', 'uncanny-pro-toolkit' ),
			),
		);

		wp_enqueue_style( 'ultp-backend', $assets_url . 'backend/bundle.min.css', false, UNCANNY_TOOLKIT_PRO_VERSION );

		wp_register_script( 'ultp-backend', $assets_url . 'backend/bundle.min.js', array( 'jquery' ), UNCANNY_TOOLKIT_PRO_VERSION );

		wp_localize_script( 'ultp-backend', 'UncannyToolkitPro', $uncanny_toolkit_pro );
		wp_enqueue_script( 'ultp-backend' );
	}

	/**
	 * Filters the variable that defines the visibility of the
	 * "Try Automator" item
	 *
	 * @since 3.5.4
	 */
	public static function try_automator_evaluate_visibility( $visible ) {
		// Check if the item is visible
		// If it's not, don't override the variable
		if ( true === $visible ) {
			// Check if the user clicked the "X" button before
			$visibility_option = get_option( '_uncanny_toolkit_try_automator_visibility' );

			// Check if the user chose to hide it
			if ( $visibility_option == 'hide-forever' ) {
				$visible = false;
			}
		}

		return $visible;
	}

	/**
	 * Filters the inner html of the "Try Automator" admin sidebar item
	 * to add the X icon to hide the item
	 *
	 * @param String $inner_html The inner HTML
	 *
	 * @return String             The inner HTML, modified.
	 * @since 3.5.4
	 */
	public static function try_automator_add_x_icon( $inner_html ) {
		// Add the "X" icon
		$inner_html .= '<span class="ultp-sidebar-featured-item__close" id="ultp-sidebar-try-automator-close" ult-tooltip-admin="' . esc_attr__( 'Hide forever', 'uncanny-pro-toolkit' ) . '"><span class="ultp-sidebar-featured-item__close-icon"></span></span>';

		return $inner_html;
	}

	/**
	 * Registers a REST API endpoint to change the visibility of the
	 * "Try Automator" item
	 *
	 * @since 3.5.4
	 */
	public static function try_automator_rest_register() {
		register_rest_route(
			'uo_pro/v1',
			'/try-automator-visibility/',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'try_automator_rest_callback' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);
	}

	/**
	 * Rest API callback for saving user selection for hiding the "Try Automator" item.
	 *
	 * @param object $request
	 *
	 * @return object
	 * @since 3.5.4
	 */
	public static function try_automator_rest_callback( $request ) {
		// check if its a valid request.
		$data = $request->get_params();
		if ( isset( $data['action'] ) && ( 'hide-forever' === $data['action'] || 'hide-forever' === $data['action'] ) ) {
			update_option( '_uncanny_toolkit_try_automator_visibility', $data['action'] );

			return new \WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new \WP_REST_Response( array( 'success' => false ), 200 );
	}

	/**
	 * @return void
	 */
	public static function uo_activate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST['uo_license_activate'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'uo_nonce', 'uo_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		// Save license key
		$license = $_POST['uo_license_key'] ?? '';

		if ( ! empty( $license ) ) {
			update_option( 'uo_license_key', $license );
		}

		$license_data = self::licensing_call( 'activate-license', true );

		if ( $license_data ) {
			update_option( 'uo_license_status', $license_data->license );
		}

		$url = add_query_arg(
			array(
				'activated' => time(),
			),
			admin_url( 'admin.php?page=' . UO_LICENSE_PAGE )
		);

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * @return void
	 */
	public static function uo_deactivate_license() {

		// listen for our activate button to be clicked
		if ( ! isset( $_POST['uo_license_deactivate'] ) ) {
			return;
		}

		// run a quick security check
		if ( ! check_admin_referer( 'uo_nonce', 'uo_nonce' ) ) {
			return;
		} // get out if we didn't click the Activate button

		$license_data = self::licensing_call( 'deactivate-license', true );

		if ( 'deactivated' === $license_data->license || 'failed' === $license_data->license ) {
			delete_option( 'uo_license_status' );
			delete_option( 'uo_license_check' );
			delete_transient( self::$license_check_key );
		}

		$url = add_query_arg(
			array(
				'deactivated' => time(),
			),
			admin_url( 'admin.php?page=' . UO_LICENSE_PAGE )
		);

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * This is a means of catching errors from the activation method above and displaying it to the customer
	 */
	public static function uo_admin_notices() {
		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( UO_LICENSE_PAGE !== sanitize_text_field( $_GET['page'] ) ) {
			return;
		}

		if ( ! isset( $_GET['sl_activation'] ) ) {
			return;
		}

		if ( ! empty( $_GET['message'] ) ) {

			switch ( $_GET['sl_activation'] ) {

				case 'false':
					$message = urldecode( esc_html__( wp_kses( $_GET['message'], array() ), 'uncanny-pro-toolkit' ) );

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
						<p><?php _e( 'License is activated.', 'uncanny-pro-toolkit' ); ?></p>
					</div>
					<?php
					break;
			}
		}
	}

	/**
	 *
	 *
	 * @static
	 *
	 * @param $class
	 */
	private static function auto_loader( $class ) {

		// Remove Class's namespace eg: my_namespace/MyClassName to MyClassName
		$class = str_replace( 'uncanny_pro_toolkit', '', $class );
		$class = str_replace( '\\', '', $class );

		// First Character of class name to lowercase eg: MyClassName to myClassName
		$class_to_filename = lcfirst( $class );

		// Split class name on upper case letter eg: myClassName to array( 'my', 'Class', 'Name')
		$split_class_to_filename = preg_split( '#([A-Z][^A-Z]*)#', $class_to_filename, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		if ( 1 <= count( $split_class_to_filename ) ) {
			// Split class name to hyphenated name eg: array( 'my', 'Class', 'Name') to my-Class-Name
			$class_to_filename = implode( '-', $split_class_to_filename );
		}
		// Create file name that will be loaded from the classes directory eg: my-Class-Name to my-class-name.php
		$file_name = 'classes/' . strtolower( $class_to_filename ) . '.php';
		if ( file_exists( dirname( __FILE__ ) . '/' . $file_name ) ) {
			include_once $file_name;
		}

	}

	/**
	 * @param string $file_name File name must be prefixed with a \ (foreword slash)
	 * @param mixed $file (false || __FILE__ )
	 *
	 * @return string
	 */
	public static function get_pro_include( $file_name, $file = false ) {

		if ( false === $file ) {
			$file = __FILE__;
		}

		$asset_uri = dirname( $file ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . $file_name;

		return $asset_uri;
	}


	/**
	 * @param string $endpoint
	 * @param bool $force
	 *
	 * @return bool|object
	 */
	public static function licensing_call( $endpoint = 'check-license', $force = false ) {
		$license = self::get_license_key();

		if ( empty( $license ) ) {
			return false;
		}

		$current_status = get_option( 'uo_license_status', '' );

		if ( empty( $current_status ) && 'check-license' === $endpoint ) {
			$endpoint = 'activate-license';
		}

		// Check transient cache
		$license_data = get_transient( self::$license_check_key );
		if ( false !== $license_data && false === $force ) {
			return $license_data;
		}

		$previous_endpoint = $endpoint;

		if ( empty( $current_status ) && 'check-license' === $endpoint ) {
			$endpoint = 'activate-license';
		}

		$data = array(
			'license' => $license,
			'item_id' => UO_ITEM_ID,
			'url'     => home_url(),
		);

		// Convert array to JSON and then encode it with Base64
		$encoded_data = base64_encode( wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$url = UO_STORE_URL . $endpoint . '?plugin=' . rawurlencode( UO_ITEM_NAME ) . '&version=' . UNCANNY_TOOLKIT_PRO_VERSION;

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
				$message = sprintf( __( 'There was an issue in activating your license. Error: %s', 'uncanny-pro-toolkit' ), wp_remote_retrieve_body( $response ) ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			}

			delete_option( 'uo_license_status' );
			delete_option( 'uo_license_check' );
			delete_transient( self::$license_check_key );

			$base_url = admin_url( 'admin.php?page=' . UO_LICENSE_PAGE );
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

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		set_transient( self::$license_check_key, $license_data, DAY_IN_SECONDS );

		return $license_data;
	}

	/**
	 * @return string
	 */
	public static function get_license_key() {
		if ( defined( 'UNCANNY_TOOLKIT_PRO_LICENSE_KEY' ) && ! empty( UNCANNY_TOOLKIT_PRO_LICENSE_KEY ) ) {
			return UNCANNY_TOOLKIT_PRO_LICENSE_KEY;
		}

		$license = trim( get_option( 'uo_license_key' ) );

		if ( empty( $license ) ) {
			return '';
		}

		return $license;
	}

	/**
	 * @return void
	 */
	public static function clear_license() {
		if ( isset( $_GET['clear_license'] ) && 'true' === $_GET['clear_license'] && wp_verify_nonce( $_GET['wpnonce'], 'uncanny-owl' ) ) {
			delete_option( 'uo_license_key' );
			delete_option( 'uo_license_status' );
			delete_option( 'uo_license_check' );

			add_action( 'admin_notices', array( __CLASS__, 'admin_notices_on_clear_license' ) );
		}
	}

	/**
	 * @return void
	 */
	public static function admin_notices_on_clear_license() {
		echo '<div class="notice notice-success is-dismissible">
		<h4>' . __( 'License cleared', 'uncanny-pro-toolkit' ) . '</h4>
		</div>';
	}

	/**
	 * @param $request
	 * @param $url
	 *
	 * @return array
	 */
	public static function modify_get_version_http_request_args( $request, $url ) {
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
			if ( isset( $request['body']['item_id'] ) && (int) UO_ITEM_ID === (int) $request['body']['item_id'] ) {
				$request['headers']['X-UO-Destination'] = 'uo';
			}
		}

		return $request;
	}

	/**
	 * @param $modules
	 *
	 * @return void
	 */
	public static function add_pro_license_box( $modules ) {
		self::uo_license_page( true );
	}
}
