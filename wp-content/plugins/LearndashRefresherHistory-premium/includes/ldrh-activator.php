<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */

if (!class_exists('LDRH_Activator')) {
    
    class LDRH_Activator {

        public static function activate() {
            if (!wp_next_scheduled('ldrh_refresher_courses_event')) {
                $time = 'tomorrow midnight';
                $frequency = 'daily';
                wp_schedule_event(strtotime($time), $frequency, 'ldrh_refresher_courses_event');
            }
        }

    }
    
}