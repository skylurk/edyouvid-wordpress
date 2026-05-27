<?php

# Constants
if ( ! defined( 'UCTINCAN_PLUGIN_NAME' ) ) {
	$basename = trim( dirname( plugin_basename( __FILE__ ) ), '/' );
	if ( ! is_dir( WP_PLUGIN_DIR . '/' . $basename ) ) {
		$basename = explode( '/', $basename );
		$basename = array_pop( $basename );
	}

	define( 'UCTINCAN_PLUGIN_NAME', $basename );
}

if ( ! defined( 'UCTINCAN_PLUGIN_FILE_NAME' ) ) {
	define( 'UCTINCAN_PLUGIN_FILE_NAME', basename( __FILE__ ) );
}

if ( ! defined( 'UCTINCAN_PLUGIN_DIR' ) ) {
	define( 'UCTINCAN_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . UCTINCAN_PLUGIN_NAME . '/' );
}

if ( ! defined( 'UCTINCAN_PLUGIN_URL' ) ) {
	define( 'UCTINCAN_PLUGIN_URL', plugins_url() . '/' . UCTINCAN_PLUGIN_NAME . '/' );
}

# Ensure XXS functions are loaded.
if ( ! function_exists( 'ultc_filter_input' ) ) {
	require_once dirname( UCTINCAN_PLUGIN_DIR ) . '/includes/tin-canny-xss-functions.php';
}

# Initialize
//require_once UCTINCAN_PLUGIN_DIR . 'autoload.php';
new UCTINCAN\Init();
