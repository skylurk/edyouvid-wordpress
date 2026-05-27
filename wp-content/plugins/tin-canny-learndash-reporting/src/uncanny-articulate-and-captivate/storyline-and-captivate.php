<?php

/**
 * Embed Articulate Storyline and Adobe Captivate
 *
 */
# Constants
if ( ! defined( 'SnC_PLUGIN_NAME' ) ) {
	$basename = trim( dirname( plugin_basename( __FILE__ ) ), '/' );
	if ( ! is_dir( WP_PLUGIN_DIR . '/' . $basename ) ) {
		$basename = explode( '/', $basename );
		$basename = array_pop( $basename );
	}

	define( 'SnC_PLUGIN_NAME', $basename );
}

if ( ! defined( 'SnC_PLUGIN_BASE' ) ) {
	define( 'SnC_PLUGIN_BASE', WP_PLUGIN_DIR . '/' . SnC_PLUGIN_NAME . '/' . basename( __FILE__ ) );
}

if ( ! defined( 'SnC_TEXTDOMAIN' ) ) {
	define( 'SnC_TEXTDOMAIN', 'storyline-and-captivate' );
}

if ( ! defined( 'SnC_PLUGIN_DIR' ) ) {
	define( 'SnC_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . SnC_PLUGIN_NAME . '/' );
}

if ( ! defined( 'SnC_PLUGIN_URL' ) ) {
	define( 'SnC_PLUGIN_URL', plugins_url() . '/' . SnC_PLUGIN_NAME . '/' );
}

if ( ! defined( 'SnC_ASSET_URL' ) ) {
	define( 'SnC_ASSET_URL', SnC_PLUGIN_URL . 'assets/' );
}

if ( ! defined( 'SnC_UPLOAD_DIR_NAME' ) ) {
	define( 'SnC_UPLOAD_DIR_NAME', 'uncanny-snc' );
}

if ( ! defined( 'SnC_VERSION_KEY' ) ) {
	define( 'SnC_VERSION_KEY', 'SnC_version' );
}

if ( ! defined( 'SnC_VERSION_NUM' ) ) {
	define( 'SnC_VERSION_NUM', '0.0.1' );
}

# Initialize
//include_once( SnC_PLUGIN_DIR . "autoload.php");
new TINCANNYSNC\Init();
