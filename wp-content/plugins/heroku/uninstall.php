<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();
$options = array(
	'linkpreview_settings',
	'linkpreview_firsttime_setup'
);
foreach ($options as $option) delete_option($option);