<?php
/*
 * Plugin Name:         Uncanny Continuing Education Credits
 * Description:         Track CPD/CEU credits for your LearnDash courses.
 * Author:              Uncanny Owl
 * Author URI:          https://www.uncannyowl.com/
 * Plugin URI:          https://www.uncannyowl.com/uncanny-continuing-education-credits/
 * Text Domain:         uncanny-ceu
 * Domain Path:         /languages
 * License:             GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Version:             5.0
 * Requires at least:   5.4
 * Requires PHP:        7.4
 */

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'UNCANNY_CEUS_VERSION', '5.0' );
define( 'UNCANNY_CEUS_DB_VERSION', '4.1' );
define( 'UNCANNY_CEUS_ITEM_NAME', 'Uncanny Continuing Education Credits' );
define( 'UNCANNY_CEUS_ITEM_ID', 11141 );
define( 'UNCANNY_CEUS_FILE', __FILE__ );
define( 'UNCANNY_CEUS_DIR', __DIR__ );

/**
 * Class UncannyLearnDashGroupManagement
 *
 * @package uncanny_learndash_group_management
 */
class InitializePlugin {

	/**
	 * The plugin name
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $plugin_name = UNCANNY_CEUS_ITEM_NAME;

	/**
	 * The plugin name acronym
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $plugin_prefix = 'ceu';

	/**
	 * The plugin version number
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	public static $plugin_version = UNCANNY_CEUS_VERSION;

	/**
	 * The plugin database version number
	 *
	 * @since    3.4
	 * @access   public
	 * @var      string
	 */
	public static $ceu_db_version = UNCANNY_CEUS_DB_VERSION;

	/**
	 * The full path and filename
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	public static $path_to_plugin_file = __FILE__;

	/**
	 * Allows the debugging scripts to initialize and log them in a file
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $log_debug_messages = false;

	/**
	 * The instance of the class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Object
	 */
	private static $instance = null;

	/**
	 * class constructor
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'uncanny_ceu_text_domain' ) );

		// Load Utilities
		$this->initialize_utilities();

		// Load Configuration
		$this->initialize_config();

		$this->initialize_crud();

		// Load the plugin files
		$this->boot_plugin();

	}

	/**
	 * Creates singleton instance of class
	 *
	 * @return InitializePlugin $instance The InitializePlugin Class
	 * @since 1.0.0
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize Static singleton class that has shared function and variables that can be used anywhere in WP
	 *
	 * @since 1.0.0
	 */
	private function initialize_utilities() {

		include_once dirname( __FILE__ ) . '/src/utilities.php';
		Utilities::set_date_time_format();

	}

	private function initialize_crud() {

		include_once dirname( __FILE__ ) . '/src/ceu-crud.php';
		CEU_CRUD::get_instance();
	}

	/**
	 * Initialize Static singleton class that configures all constants, utilities variables and handles activation/deactivation
	 *
	 * @since 1.0.0
	 */
	private function initialize_config() {

		include_once dirname( __FILE__ ) . '/src/config.php';
		$config_instance = Config::get_instance();

		$plugin_name = apply_filters( $this->plugin_prefix . '_plugin_name', $this->plugin_name );

		$config_instance->configure_plugin_before_boot( $plugin_name, $this->plugin_prefix, UNCANNY_CEUS_VERSION, UNCANNY_CEUS_FILE, $this->log_debug_messages );

	}

	/**
	 * Initialize Static singleton class auto loads all the files needed for the plugin to work
	 *
	 * @since 1.0.0
	 */
	private function boot_plugin() {
		// Add settings link on plugin page
		$uncanny_learndash_ceu_plugin_basename = plugin_basename( __FILE__ );

		add_filter(
			'plugin_action_links_' . $uncanny_learndash_ceu_plugin_basename,
			array(
				$this,
				'uncanny_ceu_plugin_settings_link',
			)
		);

		include_once dirname( __FILE__ ) . '/src/boot.php';
		Boot::get_instance();

	}

	// Allow Translations to be loaded
	public function uncanny_ceu_text_domain() {
		load_plugin_textdomain( 'uncanny-ceu', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	function uncanny_ceu_plugin_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=uncanny-ceu' ) . '">' . __( 'Settings', 'uncanny-ceu' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}
}

require_once __DIR__ . '/src/learndash-plugins-page/learndash-plugins-page.php';

// Let's run it
InitializePlugin::get_instance();

/**
 * Load notifications.
 */
require_once __DIR__ . '/src/notifications/notifications.php';

if ( class_exists( '\Uncanny_Owl\Notifications' ) ) {

	$notifications = new \Uncanny_Owl\Notifications();

	// On activate, persists/update `uncanny_owl_over_time_uncanny-ceu`.
	register_activation_hook(
		__FILE__,
		function () {
			update_option( 'uncanny_owl_over_time_uncanny-ceu', array( 'installed_date' => time() ), false );
		}
	);

	// Initiate the Notifications handler, but only load once.
	if ( false === \Uncanny_Owl\Notifications::$loaded ) {

		$notifications::$loaded = true;

		add_action( 'admin_init', array( $notifications, 'init' ) );

	}
}

require_once __DIR__ . '/src/install-automator/install-automator.php';

new \uncanny_ceu\Install_Automator();
