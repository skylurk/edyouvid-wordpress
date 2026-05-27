<?php
/**
 * @package Link Preview
 */
/*
Plugin Name: Phee's Link Preview
Plugin URI: http://www.filipstepanov.com/link-preview-project/wordpress-plugin/
Description: Link preview web service with basic link information (favicon, title, image, description)
Version: 1.6.7
Author: Filip Stepanov
Author URI: http://www.filipstepanov.com
*/

if ( !function_exists( 'add_action' ) || !defined('ABSPATH')) exit;

define('LINK_PREVIEW', 1);

define('LINKPREVIEW_URL', plugins_url('', __FILE__));
define('LINKPREVIEW_PATH', plugin_dir_path(__FILE__));
define('LINKPREVIEW_REL_PATH', dirname(plugin_basename(__FILE__)).'/');
define('LINKPREVIEW_STATIC_VIEW', plugin_dir_path(__FILE__).'/view/static.php');
define('LINKPREVIEW_STATIC_VIEW_RTL', plugin_dir_path(__FILE__).'/view/static.php');
define('LINKPREVIEW_TOOLTIP', plugin_dir_path(__FILE__).'/view/tooltip.php');
define('LINKPREVIEW_ADMIN', plugin_dir_path(__FILE__).'/view/admin.php');
define('LINKPREVIEW_VERSION', 'v1.6.5');

include_once(LINKPREVIEW_PATH.'includes/admin.php');
include_once(LINKPREVIEW_PATH.'includes/class.php');
include_once(LINKPREVIEW_PATH.'includes/tooltip.php');

function linkpreview_enqueue_style() {
    if (is_rtl()) wp_enqueue_style( 'linkpreview', LINKPREVIEW_URL.'/css/style-rtl.css', false );
    else wp_enqueue_style( 'linkpreview', LINKPREVIEW_URL.'/css/style.css', false );
}
if (!is_admin()) add_action( 'wp_enqueue_scripts', 'linkpreview_enqueue_style' );