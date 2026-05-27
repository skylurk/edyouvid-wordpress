<?php
/*
 * Plugin Name:         Uncanny Codes
 * Description:         Generate, track and sell codes that can be redeemed for access, membership and more in 50+ plugins and apps
 * Author:              Uncanny Owl
 * Author URI:          https://www.uncannyowl.com/
 * Plugin URI:          https://www.uncannyowl.com/downloads/uncanny-learndash-codes/
 * Text Domain:         uncanny-learndash-codes
 * Domain Path:         /languages
 * License:             GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Version:             4.4.0.1
 * Requires at least:   5.3
 * Requires PHP:        7.4
 */

use uncanny_learndash_codes\Config;
use uncanny_learndash_codes\SharedFunctionality;

/**
 * Define Uncanny Codes Version
 */
define( 'UNCANNY_LEARNDASH_CODES_VERSION', '4.4.0.1' );

/**
 * Define Uncanny Codes Database Version
 */
define( 'UNCANNY_LEARNDASH_CODES_DB_VERSION', '4.2' );

/**
 * Base file
 */
define( 'UO_CODES_FILE', __FILE__ );

// On first activation, upgrades, create or update Database.
register_activation_hook( UO_CODES_FILE, 'ulc_create_upgrade_db_tables' );

/**
 * Create or update database
 */
function ulc_create_upgrade_db_tables() {
	uncanny_learndash_codes\Database::create_tables();
}

// Allow Translations to be loaded.
add_action( 'plugins_loaded', 'uncanny_learndash_codes_text_domain' );

/**
 * All translation to be added
 */
function uncanny_learndash_codes_text_domain() {
	load_plugin_textdomain( 'uncanny-learndash-codes', false, basename( dirname( UO_CODES_FILE ) ) . '/languages/' );
}

// Plugins Configurations File.
require_once dirname( UO_CODES_FILE ) . '/src/classes/class-shared-functionality.php';
$shared = SharedFunctionality::get_instance();
require_once dirname( UO_CODES_FILE ) . '/src/config.php';
$config = Config::get_instance();
// Load all plugin classes(functionality).
require_once dirname( UO_CODES_FILE ) . '/src/boot.php';
$boot = '\uncanny_learndash_codes\Boot';
$ulc  = new $boot();

require_once __DIR__ . '/src/notifications/notifications.php';

/**
 * In-plugin notifications.
 *
 * @since 4.3
 */
if ( class_exists( '\Uncanny_Owl\Notifications' ) ) {

	$notifications = new \Uncanny_Owl\Notifications();

	// On activate, persists/update `uncanny_owl_over_time_uncanny-codes`.
	register_activation_hook(
		__FILE__,
		function () {
			update_option( 'uncanny_owl_over_time_uncanny-codes', array( 'installed_date' => time() ), false );
		}
	);

	// Initiate the Notifications handler, but only load once.
	if ( false === \Uncanny_Owl\Notifications::$loaded ) {

		$notifications::$loaded = true;

		add_action( 'admin_init', array( $notifications, 'init' ) );

	}
}
