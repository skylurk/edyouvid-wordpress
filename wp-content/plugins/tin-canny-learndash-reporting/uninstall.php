<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if we should delete data on uninstall
$delete_data = get_option( 'tincanny_delete_data_on_uninstall', 'no' );
$delete_data = 'yes' === $delete_data;

if ( ! $delete_data ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'uotincan_reporting',
	$wpdb->prefix . 'uotincan_resume',
	$wpdb->prefix . 'snc_file_info',
	$wpdb->prefix . 'snc_post_relationship',
	$wpdb->prefix . 'tbl_reporting_api_user_id',
);

foreach ( $tables as $table ) {
	$wpdb->query( 'DROP TABLE IF EXISTS `' . $table . '`' );
}

// Delete all Tin Canny options
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%tincanny%'" );

// Function to safely delete a directory using WP_Filesystem
function delete_tincanny_directory( $dir ) {
	global $wp_filesystem;
	
	if ( ! $wp_filesystem || ! $wp_filesystem->exists( $dir ) ) {
		return false;
	}
	
	return $wp_filesystem->rmdir( $dir, true );
}

// Delete Tin Canny upload directory
$upload_dir = wp_upload_dir()['basedir'] . '/uncanny-snc';
if ( is_dir( $upload_dir ) ) {
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	delete_tincanny_directory( $upload_dir );
}

// If multisite, clean up network-wide data
if ( is_multisite() ) {
	// Get all blog IDs
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		
		// Delete blog-specific tables
		foreach ( $tables as $table ) {
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $table );
		}
		
		// Delete blog-specific options
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '%%tincanny%%' ) );
		
		// Delete blog-specific transients
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '%%_transient%%tincanny%%' ) );
		
		// Delete blog-specific upload directory
		$blog_upload_dir = wp_upload_dir()['basedir'] . '/uncanny-snc';
		if ( is_dir( $blog_upload_dir ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			delete_tincanny_directory( $blog_upload_dir );
		}
		
		restore_current_blog();
	}
	
	// Delete network-wide options
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", '%%tincanny%%' ) );
}

