<?php
/**
 * Plugin Name: Learndash Refresher History Learndash Refresher History Premium
 * Description:       This plugin enables admin to set expiration period for courses and keeps users history for refreshed courses.
 * Version:           1.0.1
 * Update URI: https://api.freemius.com
 * Author:            WPExperts
 * Author URI:        https://wpexperts.io/
 * Text Domain:       ldrh
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define plugin constants
 */
define('LDRH_VERSION', '1.0.1');
define('LDRH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LDRH_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * The plugin core
 */
global $ea_fs;
require LDRH_PLUGIN_PATH . 'includes/ldrh-freemius.php';
if (ea_fs()->can_use_premium_code()) {
	require LDRH_PLUGIN_PATH . 'includes/ldrh-core.php';
	// Instantiate plugin
	$ldrh_core = LDRH_Core::getInstance();
}
/**
 * Activation and Deactivation hooks
 */
register_activation_hook(__FILE__, 'ldrh_activation');
register_deactivation_hook(__FILE__, 'ldrh_deactivation');

function ldrh_activation() {
    require_once LDRH_PLUGIN_PATH . 'includes/ldrh-activator.php';
    LDRH_Activator::activate();
}

function ldrh_deactivation() {
    require_once LDRH_PLUGIN_PATH . 'includes/ldrh-deactivator.php';
    LDRH_Deactivator::deactivate();
}