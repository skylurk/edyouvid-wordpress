<?php

/**
 * This class defines all core code for the plugin.
 */

if (!class_exists('LDRH_Core')) {
    
    class LDRH_Core {
        
        static $instance = false;
        
        public static function getInstance() {
            if (!self::$instance){
		self::$instance = new self;
            }
            
            return self::$instance;
	}

        private function __construct() {
            add_action('plugins_loaded', array($this, 'plugin_dependencies'));
	}
        
        public function plugin_dependencies() {
            if (in_array('sfwd-lms/sfwd_lms.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                $this->includes();
            }else{
                // show notice if Learndash plugin is not active
                add_action('admin_notices', array($this, 'ldrh_inactive_plugin_notice'));
            }
        }

        /**
         * Add Plugin Include Files
         */
        private function includes() {
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-functions.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-course-meta.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-settings.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-ajax.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-cron.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-shortcodes.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-refresher-history.php');
            include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-refresher-user-history.php');
            //include_once(LDRH_PLUGIN_PATH . '/includes/ldrh-refresher-report.php');
        }
        
        public function ldrh_inactive_plugin_notice() {
            ?>
            <div id="message" class="error"><p><?php printf(__('Learndash Refresher History requires Learndash LMS to be installed and active!', 'ldrh')); ?></p></div>
            <?php
        }

    }
    
}