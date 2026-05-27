<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */

if (!class_exists('LDRH_Deactivator')) {
    
    class LDRH_Deactivator {

        public static function deactivate() {
            // clear cronjob
            wp_clear_scheduled_hook('ldrh_refresher_courses_event');
        }

    }
    
}