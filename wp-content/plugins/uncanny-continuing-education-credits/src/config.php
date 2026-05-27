<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * This class is used to run any configurations before the plugin is initialized
 *
 * @package    uncanny_ceu
 * @subpackage uncanny_ceu/config
 * @author     Uncanny Owl
 */
class Config {


	/**
	 * The instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Boot
	 */
	private static $instance = null;

	/**
	 * Creates singleton instance of class
	 *
	 * @return Config $instance The UncannyLearnDashGroupManagement Class
	 * @since 1.0.0
	 *
	 */
	public static function get_instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the class and setup its properties.
	 *
	 * @param string $plugin_name The name of the plugin
	 * @param string $prefix The variable used to prefix filters and actions
	 * @param string $version The version of this plugin
	 * @param string $file The main plugin file __FILE__
	 * @param bool $debug Whether debug log in php and js files are enabled
	 *
	 * @since    1.0.0
	 *
	 */
	public function configure_plugin_before_boot( $plugin_name, $prefix, $version, $file, $debug ) {


		$this->define_constants( $plugin_name, $prefix, $version, $file, $debug );

		do_action( 'ceu_define_constants_after' );

		register_activation_hook( Utilities::get_plugin_file(), array(
			$this,
			'activation',
		) );

		register_deactivation_hook( Utilities::get_plugin_file(), array(
			$this,
			'deactivation',
		) );

		add_action( 'admin_init', array(
			$this,
			'uncanny_ceu_plugin_redirect',
		) );

		do_action( 'ceu_config_setup_after' );

	}

	/**
	 *
	 * This action is documented in includes/class-plugin-name-deactivator.php
	 *
	 * @param string $plugin_name The name of the plugin
	 * @param string $prefix Variable used to prefix filters and actions
	 * @param string $version The version of this plugin.
	 * @param string $plugin_file The main plugin file __FILE__
	 * @param string $debug_mode Whether debug log in php and js files are
	 *     enabled
	 *
	 * @since    1.0.0
	 * @access   private
	 *
	 */
	private function define_constants( $plugin_name, $prefix, $version, $plugin_file, $debug_mode ) {


		// Set and define version
		if ( ! defined( strtoupper( $prefix ) . '_PLUGIN_NAME' ) ) {
			define( strtoupper( $prefix ) . '_PLUGIN_NAME', $plugin_name );
			Utilities::set_plugin_name( $plugin_name );
		}

		// Set and define version
		if ( ! defined( strtoupper( $prefix ) . '_VERSION' ) ) {
			define( strtoupper( $prefix ) . '_VERSION', $version );
			Utilities::set_version( $version );
		}

		// Set and define prefix
		if ( ! defined( strtoupper( $prefix ) . '_PREFIX' ) ) {
			define( strtoupper( $prefix ) . '_PREFIX', $prefix );
			Utilities::set_prefix( $prefix );
		}

		// Set and define the main plugin file path
		if ( ! defined( $prefix . '_FILE' ) ) {
			define( strtoupper( $prefix ) . '_FILE', $plugin_file );
			Utilities::set_plugin_file( $plugin_file );
		}

		// Set and define debug mode
		if ( ! defined( $prefix . '_DEBUG_MODE' ) ) {
			define( strtoupper( $prefix ) . '_DEBUG_MODE', $debug_mode );
			Utilities::set_debug_mode( $debug_mode );
		}

		// Set and define the server initialization time
		if ( ! defined( $prefix . '_SERVER_INITIALIZATION' ) ) {
			$time = current_time( 'timestamp' );
			define( strtoupper( $prefix ) . '_SERVER_INITIALIZATION', $time );
			Utilities::set_plugin_initialization( $time );
		}

	}


	/**
	 * The code that runs during plugin activation.
	 *
	 * @since    1.0.0
	 */
	public function activation() {

		do_action( 'ceu_activation_before' );
		self::initialize_db();

		// On first activation, redirect to toolkit license page
		update_option( 'ceu_activation_redirect', 'yes' );

		do_action( 'ceu_activation_after' );

	}


	/**
	 * @return void
	 */
	public static function initialize_db() {

		include_once 'database.php';
		$db_version = get_option( 'ceu_database_version', null );
		if ( null !== $db_version && (string) InitializePlugin::$ceu_db_version === (string) $db_version ) {
			// bail. No db upgrade needed!
			return;
		}

		Database::create_tables();
	}

	/**
	 * If we are hitting admin right after activation redirect to the licensing
	 * page
	 */
	function uncanny_ceu_plugin_redirect() {

		if ( 'yes' === get_option( 'ceu_activation_redirect', 'no' ) ) {

			update_option( 'ceu_activation_redirect', 'no' );

			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_redirect( admin_url( 'admin.php?page=uncanny-ceu' ) );
			}
		}
	}


	/**
	 * The code that runs during plugin deactivation.
	 *
	 * @since    1.0.0
	 */
	function deactivation() {

		do_action( 'ceu_deactivation_before' );

		do_action( 'ceu_deactivation_after' );

	}

}
