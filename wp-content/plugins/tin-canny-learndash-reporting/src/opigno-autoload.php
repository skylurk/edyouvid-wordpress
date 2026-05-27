<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if ( ! function_exists('tincan_register_autoloader') && defined('UO_REPORTING_FILE') ) {
    // If the tincan_register_autoloader function doesn't exist, we can use it to register the autoloader.
    $autoload_file = plugin_dir_path(UO_REPORTING_FILE) . '/vendor/opigno/tincan/autoload.php';
    if ( file_exists( $autoload_file ) ) {
        require_once $autoload_file;
    }
}