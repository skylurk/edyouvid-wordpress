<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Boot
 * @package uncanny_ceu
 */
class Boot {

	/**
	 * The instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Boot
	 */
	private static $instance = null;

	/**
	 * The directories that are auto loaded and initialized
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array
	 */
	private static $auto_loaded_directories = null;

	/**
	 * class constructor
	 */
	private function __construct() {

		// We need to check if spl auto loading is available when activating plugin
		// Plugin will not activate if SPL extension is not enabled by throwing error
		if ( ! extension_loaded( 'SPL' ) ) {
			trigger_error( esc_html__( 'Please contact your hosting company to update to php version 5.3+ and enable spl extensions.', 'uncanny-ceu' ), E_USER_ERROR );
		}

		spl_autoload_register( array( $this, 'require_class_files' ) );

		// Import Gutenberg Blocks
		require_once( dirname( __FILE__ ) . '/blocks/blocks.php' );
		new Blocks();

		// Initialize all classes in given directories
		$this->auto_initialize_classes();

		// Check if DB needs to be updated
		Config::initialize_db();

		add_action( 'admin_menu', array( __CLASS__, 'add_help_submenu' ), 30 );

		add_action( 'admin_menu', array( __CLASS__, 'add_uncanny_plugins_page' ), 31 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_external_scripts' ) );

		add_action( 'admin_init', array( __CLASS__, 'uo_admin_help_process' ) );

		add_action( 'rest_api_init', array( $this, 'register_try_automator_endpoint' ) );

		add_action( 'admin_print_scripts', array( $this, 'hide_nonuncanny_ceu_warnings' ) );
		add_action( 'admin_head', array( $this, 'hide_nonuncanny_ceu_warnings' ), PHP_INT_MAX );
	}

	/**
	 * Method try_automator_rest_register.
	 *
	 * Callback method to action hook `rest_api_init`.
	 *
	 * Registers a REST API endpoint to change the visibility of the "Try Automator" item.
	 *
	 * @since 3.5.4
	 */
	public function register_try_automator_endpoint() {

		register_rest_route(
			'uncanny_ceu/v1',
			'/try_automator_visibility/',
			array(
				'methods'             => 'POST',
				'callback'            => function ( $request ) {
					$data = $request->get_params(); // Check if its a valid request.
					if ( isset( $data['action'] ) && ( 'hide-forever' === $data['action'] || 'hide-forever' === $data['action'] ) ) {
						update_option( '_ceu_automator_visibility', $data['action'] );

						return new \WP_REST_Response( array( 'success' => true ), 200 );
					}

					return new \WP_REST_Response( array( 'success' => false ), 200 );
				},
				'permission_callback' => function () {
					return true;
				},
			)
		);

	}


	/**
	 * Creates singleton instance of Boot class and defines which directories are auto loaded
	 *
	 * @param array $auto_loaded_directories
	 *
	 * @return Boot
	 * @since 1.0.0
	 *
	 */
	public static function get_instance( $auto_loaded_directories = array( 'classes/' ) ) {

		if ( null === self::$instance ) {

			// Define directories were the auto loader looks for files and initializes them
			self::$auto_loaded_directories = $auto_loaded_directories;

			// Lets boot up!
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * SPL Auto Loader functions
	 *
	 * @param string $class Any
	 *
	 * @since 1.0.0
	 *
	 */
	private function require_class_files( $class ) {

		// Remove Class's namespace eg: my_namespace/MyClassName to MyClassName
		$class = str_replace( __NAMESPACE__, '', $class );
		$class = str_replace( '\\', '', $class );

		// First Character of class name to lowercase eg: MyClassName to myClassName
		$class_to_filename = lcfirst( $class );

		// Split class name on upper case letter eg: myClassName to array( 'my', 'Class', 'Name')
		// 0 means "no limit".
		$split_class_to_filename = preg_split( '#([A-Z][^A-Z]*)#', $class_to_filename, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		if ( 1 <= count( $split_class_to_filename ) ) {
			// Split class name to hyphenated name eg: array( 'my', 'Class', 'Name') to my-Class-Name
			$class_to_filename = implode( '-', $split_class_to_filename );
		}

		// Create file name that will be loaded from the classes directory eg: my-Class-Name to my-class-name.php
		$file_name = strtolower( $class_to_filename ) . '.php';


		// Check each directory
		foreach ( self::$auto_loaded_directories as $directory ) {

			$file_path = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $directory . $file_name;

			// Does the file exist
			if ( file_exists( $file_path ) ) {

				// File found, require it
				require_once( $file_path );

				// You can cannot have duplicate files names. Once the first file is found, the loop ends.
				return;
			}
		}

	}

	/**
	 * Looks through all defined directories and modifies file name to create new class instance.
	 *
	 * @since 1.0.0
	 *
	 */
	private function auto_initialize_classes() {

		// Check each directory
		foreach ( self::$auto_loaded_directories as $directory ) {

			// Get all files in directory
			$files = scandir( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $directory );

			// remove parent directory, sub directory, and silence is golden index.php
			$files = array_diff( $files, array( '..', '.', 'index.php', '.DS_Store' ) );

			// Loop through all files in directory to create class names from file name
			foreach ( $files as $file ) {

				// Remove file extension my-class-name.php to my-class-name
				$file_name = str_replace( '.php', '', $file );

				// Split file name on - eg: my-class-name to array( 'my', 'class', 'name')
				$class_to_filename = explode( '-', $file_name );

				// Make the first letter of each word in array upper case - eg array( 'my', 'class', 'name') to array( 'My', 'Class', 'Name')
				$class_to_filename = array_map( function ( $word ) {
					return ucfirst( $word );
				}, $class_to_filename );

				// Implode array into class name - eg. array( 'My', 'Class', 'Name') to MyClassName
				$class_name = implode( $class_to_filename );

				$class = __NAMESPACE__ . '\\' . $class_name;

				Utilities::set_class_instance( $class_name, new $class );
			}
		}
	}

	/**
	 * Add "Help" submenu
	 */
	public static function add_help_submenu() {
		add_submenu_page(
			'uncanny-ceu-report',
			__( 'Uncanny Continuing Education Credits Support', 'uncanny-ceu' ),
			__( 'Help', 'uncanny-ceu' ),
			'manage_options',
			'uncanny-ceu-kb',
			array( __CLASS__, 'include_help_page' )
		);
	}

	/**
	 * Create "Uncanny Plugins" submenu
	 */
	public static function add_uncanny_plugins_page() {
		add_submenu_page(
			'uncanny-ceu-report',
			__( 'Uncanny LearnDash Plugins', 'uncanny-ceu' ),
			__( 'LearnDash Plugins', 'uncanny-ceu' ),
			'manage_options',
			'uncanny-ceu-plugins',
			array( __CLASS__, 'include_learndash_plugins_page' )
		);
	}

	/**
	 * Include "Help" template
	 */
	public static function include_help_page() {
		include( 'templates/admin-help.php' );
	}

	/**
	 * Include "LearnDash Plugins" template
	 */
	public static function include_learndash_plugins_page() {
		include( 'templates/admin-learndash-plugins.php' );
	}

	/**
	 * Enqueue external scripts from uncannyowl.com
	 */
	public static function enqueue_external_scripts() {
		$pages_to_include = [ 'uncanny-ceu-plugins', 'uncanny-ceu-kb' ];

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $pages_to_include ) ) {
			wp_enqueue_style( 'uncannyowl-core', 'https://uncannyowl.com/wp-content/mu-plugins/uncanny-plugins-core/dist/bundle.min.css', array(), Utilities::get_version() );

			wp_enqueue_script( 'uncannyowl-core', 'https://uncannyowl.com/wp-content/mu-plugins/uncanny-plugins-core/dist/bundle.min.js', array( 'jquery' ), Utilities::get_version() );
		}
	}

	/**
	 * Submit ticket
	 */
	public static function uo_admin_help_process() {
		if ( isset( $_POST['ucec-send-ticket'] ) && check_admin_referer( 'uncanny0w1', 'ucec-send-ticket' ) ) {
			$name        = sanitize_text_field( $_POST['fullname'] );
			$email       = sanitize_email( $_POST['email'] );
			$website     = esc_url_raw( $_POST['website'] );
			$license_key = sanitize_text_field( $_POST['license_key'] );
			$message     = sanitize_textarea_field( $_POST['message'] );
			$siteinfo    = stripslashes( $_POST['siteinfo'] );
			$message     = '<h3>Message:</h3><br/>' . wpautop( $message );
			if ( isset( $_POST['website'] ) && ! empty( sanitize_text_field( $website ) ) ) {
				$message .= '<hr /><strong>Website:</strong> ' . $website;
			}
			if ( isset( $_POST['license_key'] ) && ! empty( sanitize_text_field( $license_key ) ) ) {
				$message .= '<hr /><strong>License:</strong> <a href="https://www.uncannyowl.com/wp-admin/edit.php?post_type=download&page=edd-licenses&s=' . $license_key . '" target="_blank">' . $license_key . '</a>';
			}
			if ( isset( $_POST['site-data'] ) && 'yes' === sanitize_text_field( $_POST['site-data'] ) ) {
				$message = "$message<hr /><h3>User Site Information:</h3><br />{$siteinfo}";
			}

			$to        = 'support.41077.bb1dda3d33afb598@helpscout.net';
			$subject   = esc_html( $_POST['subject'] );
			$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
			$headers[] = 'From: ' . $name . ' <' . $email . '>';
			$headers[] = 'Reply-To:' . $name . ' <' . $email . '>';
			wp_mail( $to, $subject, $message, $headers );
			if ( isset( $_POST['page'] ) ) {
				$url = admin_url( 'admin.php' ) . '?page=' . esc_html( $_POST['page'] ) . '&sent=true&wpnonce=' . wp_create_nonce();
				wp_safe_redirect( $url );
				exit;
			}
		}
	}

	/**
	 * @return void
	 */
	public function hide_nonuncanny_ceu_warnings() {
		$ceu_pages = array(
			'uncanny-ceu-report',
			'uncanny-deficiency-report',
			'uncanny-ceu',
			'uncanny-historical-completions-credits',
			'uncanny-ceu-kb',
			'uncanny-ceu-plugins',
		);

		// Bail if we're not on a uncanny-ceu screen.
		if ( empty( $_REQUEST['page'] ) || ! in_array( strtolower( $_REQUEST['page'] ), $ceu_pages, true ) ) {
			return;
		}

		global $wp_filter;
		if ( ! empty( $wp_filter['user_admin_notices']->callbacks ) && is_array( $wp_filter['user_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['user_admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'uncanny' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( $name, 'uncanny_ceu' ) === false ) {
						unset( $wp_filter['user_admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}

		if ( ! empty( $wp_filter['admin_notices']->callbacks ) && is_array( $wp_filter['admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'uncanny_ceu' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( $name, 'uncanny_ceu' ) === false ) {
						unset( $wp_filter['admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}

		if ( ! empty( $wp_filter['all_admin_notices']->callbacks ) && is_array( $wp_filter['all_admin_notices']->callbacks ) ) {
			foreach ( $wp_filter['all_admin_notices']->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
						unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
						continue;
					}
					if ( is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && strpos( strtolower( get_class( $arr['function'][0] ) ), 'uncanny_ceu' ) !== false ) {
						continue;
					}
					if ( ! empty( $name ) && strpos( $name, 'uncanny_ceu' ) === false ) {
						unset( $wp_filter['all_admin_notices']->callbacks[ $priority ][ $name ] );
					}
				}
			}
		}
	}

}




