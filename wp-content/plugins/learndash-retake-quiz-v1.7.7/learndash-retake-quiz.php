<?php
/**
 * Plugin Name:       LearnDash Retake Quiz
 * Plugin URI:        https://wooninjas.com/downloads/learndash-retake-quiz/
 * Description:       Allow users to reattempt only incorrectly answered questions in subsequent attempts, with other features like retake quiz delay, limit, logs and negative marking on incorrect questions.
 * Version:           1.7.7
 * Requires at least: 5.1
 * Requires PHP:      7.2
 * Author:            WooNinjas
 * Author URI:        https://wooninjas.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ld_retake_quiz
 * Domain Path:       /languages
 */

if ( !defined ( 'ABSPATH' ) ) exit;

register_activation_hook( __FILE__, ['Learndash_Retake_Quiz', 'activation' ] );
register_deactivation_hook( __FILE__, ['Learndash_Retake_Quiz', 'deactivation' ] );

if(ob_get_length() > 0) {
    ob_clean();
}
ob_start();

/**
 * Class Learndash_Retake_Quiz
 */
class Learndash_Retake_Quiz {
    const VERSION = '1.7.7';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Learndash_Retake_Quiz ) ) {
            self::$instance = new self;

            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->hooks();
            self::$instance->upgrade();
            
            
        }

        return self::$instance;
    }

    /**
     * Activation function hook
     *
     * @return void
     */
    public static function activation() {

        if ( ! current_user_can( 'activate_plugins' ) )
            return;


        $enabled_features = get_option( 'lrtq_enable_disable_features' );
        if( empty( $enabled_features ) ) {

            $default_enabled_features = array();
            $default_enabled_features['enable_allow_retake_quiz'] = '';
            $default_enabled_features['enable_allow_delay_quiz'] = '';
            $default_enabled_features['enable_allow_retake_logs'] = '';
            $default_enabled_features['enable_allow_retake_limit'] = '';
            $default_enabled_features['delete_previous_quiz_attempt'] = '';
            $default_enabled_features['enable_allow_negative_marking'] = '';
            $default_enabled_features['ld_retakequiz_delay'] = 0;
            $default_enabled_features['ld_retakequiz_delay_type'] = 'Minutes';

            update_option( 'lrtq_enable_disable_features', $default_enabled_features );
        }

        $general_settings = get_option( 'lrtq_general_settings' );
        if( empty( $general_settings ) ) {

            $default_general_settings = array();
            $default_general_settings['ld_retakequiz_allow_courses'] = '';
            $default_general_settings['ld_retakequiz_course'] = array();
            $default_general_settings['ld_retakequiz_lesson'] = array();
            $default_general_settings['ld_retakequiz_topic'] = array();
            $default_general_settings['ld_retakequiz_quizes'] = array();
            $default_general_settings['ld_retakequiz_categories'] = array();
            $default_general_settings['ld_retakequiz_tags'] = array();
            $default_general_settings['ld_retakequiz_exclude_quizzes'] = array();
            $default_general_settings['ld_retakequiz_exclude_users'] = array();

            update_option( 'lrtq_general_settings', $default_general_settings );
        }
    }

    /**
     * Deactivation function hook
     *
     * @return void
     */
    public static function deactivation() {
        // delete_option( 'ld_retake_quiz_version' );
        // delete_option( 'lrtq_enable_disable_features' );
        // delete_option( 'lrtq_general_settings' );
    }

    /**
     * Upgrade function hook
     *
     * @return void
     */
    public static function upgrade() {
        if ( get_option ( 'ld_retake_quiz_version' ) != self::VERSION ) {
            global $wpdb;
            $table_statistic = LDLMS_DB::get_table_name('quiz_statistic');
            $column_name = 'ld_aq_points';
            $column = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
                DB_NAME, $table_statistic, $column_name
            ) );

            if ( empty( $column ) ) {
                $wpdb->query( "ALTER TABLE {$table_statistic} ADD COLUMN `{$column_name}` INT(10) NOT NULL COMMENT 'LearnDash Advanced Quizzes Points' AFTER `answer_data`; " );
            }

            update_option( 'ld_retake_quiz_version', self::VERSION );
        }
    }

    /**
     * Setup Constants
     */
    private static function setup_constants() {

        /**
         * Directory
         */
        define( 'LEARNDASH_RETAKEQUIZ_DIR', plugin_dir_path ( __FILE__ ) );
        define( 'LEARNDASH_RETAKEQUIZ_DIR_FILE', LEARNDASH_RETAKEQUIZ_DIR . basename ( __FILE__ ) );
        define( 'LEARNDASH_RETAKEQUIZ_INCLUDES_DIR', trailingslashit ( LEARNDASH_RETAKEQUIZ_DIR . 'includes' ) );
        define( 'LEARNDASH_RETAKEQUIZ_TEMPLATES_DIR', trailingslashit ( LEARNDASH_RETAKEQUIZ_DIR . 'templates' ) );
        define( 'LEARNDASH_RETAKEQUIZ_BASE_DIR', plugin_basename(__FILE__));

        /**
         * URLS
         */
        define( 'LEARNDASH_RETAKEQUIZ_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'LEARNDASH_RETAKEQUIZ_ASSETS_URL', trailingslashit ( LEARNDASH_RETAKEQUIZ_URL . 'assets' ) );

        /**
         * Following Constants are for checking the capability of logged in user
         * These Constants are used in the function ldat_menu() to dynamically render submenu
         * According to capability of the logged in user.
         * 
         * Capability Check
         * 
         */
        define( 'LDPR_ADMIN_CAPABILITY_CHECK', 'manage_options');
        define( 'LDPR_GROUP_LEADER_CAPABILITY_CHECK', 'group_leader');
    }

    /**
     * Include Required Files
     */
    private static function includes() {

        if ( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_retake_meta_boxes.php' ) ) {
            require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_retake_meta_boxes.php' );
        }

        if ( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'class-retake-quiz-admin-binary-selector.php' ) ) {
            require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'class-retake-quiz-admin-binary-selector.php' );
        }

        $enabled_features = get_option( 'lrtq_enable_disable_features' );

        $allow_quiz_delays  = $enabled_features['enable_allow_delay_quiz'];
        $negative_marking   = $enabled_features['enable_allow_negative_marking'];
        $allow_retake_quiz  = $enabled_features['enable_allow_retake_quiz'];
       
        if( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'validate_quiz.php' ) ) {
            require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'validate_quiz.php' );
        }

        if( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_quiz_retake_extension.php' ) ) {
            require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_quiz_retake_extension.php' );
        }

        if( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_quiz_retake_statistics_external.php' ) ) {
            require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_quiz_retake_statistics_external.php' );
        }

        if( !class_exists('Learndash_Advanced_Quiz') ) {

            if ( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_quiz_retake_statistics.php' ) ) {
                require_once( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'ld_quiz_retake_statistics.php' );
            }
        }

        if( !empty( $allow_retake_quiz ) && $allow_retake_quiz == 'on' ) {
            if( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'admin_settings.php' ) ) {
                require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'admin_settings.php' );
            }
        }
        
        if( ( !empty( $allow_retake_quiz ) && $allow_retake_quiz == 'on' ) || ( !empty( $allow_quiz_delays ) && $allow_quiz_delays == 'on' ) ) {
            
            if( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'quiz_handler.php' ) ) {
                require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'quiz_handler.php' );
            }

            
        }
        if( file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'settings/options.php' ) ) {
            require_once ( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'settings/options.php' );
        }

        if (file_exists( LEARNDASH_RETAKEQUIZ_DIR . 'license/LRTQ_License_Handler.php' ) ) {
            require_once( LEARNDASH_RETAKEQUIZ_DIR . 'license/LRTQ_License_Handler.php' );
        }

        if (file_exists( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'class-ld-quiz-retake-wp-logger.php' ) ) {
            require_once( LEARNDASH_RETAKEQUIZ_INCLUDES_DIR . 'class-ld-quiz-retake-wp-logger.php' );
        }
    }

    /**
     * Register hooks
     */
    private static function hooks() {

        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'frontend_enqueue_scripts' ] );


        add_filter( 'plugin_action_links_'.LEARNDASH_RETAKEQUIZ_BASE_DIR, [ __CLASS__, 'settings_link' ], 10 ,1 );
    }

    /**
     * Enqueue scripts on admin
     *
     * @param string $hook
     */
    public static function admin_enqueue_scripts( $hook ) {

        $is_settings_page = ( isset($_GET['page']) && $_GET['page'] === 'ld-retake-quiz-options');
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'features';

        if( $is_settings_page ) {
            /**
             * Select2
             */
            wp_enqueue_style( 'ld-retake-quiz-admin-select2-css', LEARNDASH_RETAKEQUIZ_ASSETS_URL . 'css/select2.min.css', null, self::VERSION, null );
            wp_enqueue_script( 'ld-retake-quiz-admin-select2-js', LEARNDASH_RETAKEQUIZ_ASSETS_URL . 'js/select2.min.js', null, self::VERSION, true );
        }
        /**
         * plugin's admin script
         */
        wp_enqueue_script( 'ld-retake-quiz-admin-script', LEARNDASH_RETAKEQUIZ_ASSETS_URL . 'js/ld-retake-quiz-admin-script.js', [ 'jquery' ], self::VERSION, true );

        // wp_localize_script( 'ld-retake-quiz-admin-script', 'lDRetakeQuizVars', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

        wp_localize_script( 'ld-retake-quiz-admin-script', 'myLDRetakeQuizAdminAjax', 
            array( 
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'select_option' => __( 'select an option' , 'ld_retake_quiz') 
            )      
        );

        $delete_retake_quiz_data_page = ( $is_settings_page && $current_tab === 'delete_retake_quiz_data' );

        if($delete_retake_quiz_data_page) {

            wp_enqueue_script(
                'learndash-admin-binary-selector-script',
                LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-binary-selector' . learndash_min_asset() . '.js',
                array('jquery'),
                LEARNDASH_SCRIPT_VERSION_TOKEN,
                true
            );

            wp_enqueue_style(
                'learndash-admin-binary-selector-style',
                LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-binary-selector' . learndash_min_asset() . '.css',
                array(),
                LEARNDASH_SCRIPT_VERSION_TOKEN
            );
            wp_style_add_data('learndash-admin-binary-selector-style', 'rtl', 'replace');
        }

        wp_enqueue_style( 'ld-retake-quiz-admin-style', LEARNDASH_RETAKEQUIZ_ASSETS_URL . 'css/ld-retake-quiz-admin-style.css', [], time(), null );
        wp_enqueue_style('ld-retake-quiz-admin-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    }

    public static function frontend_enqueue_scripts( $hook ) {
        
        if( is_admin() ) {
            return false;
        }
        
        wp_enqueue_style( 'ld-retake-quiz-frontend-style', LEARNDASH_RETAKEQUIZ_ASSETS_URL . 'css/ld-retake-quiz-frontend-style.css', [], self::VERSION, null );

        $ld_retake_quiz_settings = get_option('lrtq_enable_disable_features', array());
        $is_retake_quiz_enabled = ( isset($ld_retake_quiz_settings['enable_allow_retake_quiz']) && $ld_retake_quiz_settings['enable_allow_retake_quiz']  == 'on' ) ? true : false;
        $negative_marking_enabled = ( isset($ld_retake_quiz_settings['enable_allow_negative_marking']) && $ld_retake_quiz_settings['enable_allow_negative_marking']  == 'on' ) ? true : false;

        if( $is_retake_quiz_enabled || $negative_marking_enabled ) {

            if( $is_retake_quiz_enabled ) {
                wp_enqueue_script('ld-retake-quiz-frontend-script', LEARNDASH_RETAKEQUIZ_ASSETS_URL . 'js/ld-retake-quiz-frontend-script.js', array('jquery'), self::VERSION, true);
            }
            
            wp_enqueue_script(
                'wpProQuiz_front_javascript',
                LEARNDASH_RETAKEQUIZ_ASSETS_URL . 'js/wpProQuiz_front.js',
                array('jquery', 'jquery-ui-sortable'),
                self::VERSION,
                true
            );
        }

    }

    /**
     * Add settings link on plugin page
     *
     * @return void
     */
    public static function settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=ld-retake-quiz-options">'. __( 'Settings', 'ld_retake_quiz' ). '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Display admin notifications if dependency not found.
 */
function ld_retake_require_dependency() {
    if( ! is_admin() ) {
        return;
    }

    if ( ! class_exists( 'LDLMS_DB' ) ) {
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        unset($_GET['activate']);
        $class = 'notice is-dismissible error';
        $message = __( '<strong>LearnDash Retake Quiz</strong> requires <a href="https://www.learndash.com" target="_blank">LearnDash LMS</a> plugin to be activated.', 'ld_retake_quiz' );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    }
}

// Schedule the ldrt_wp_logging_prune_routine function to run every week
function schedule_ldrt_wp_logging_prune_routine_callback() {
    if ( ! wp_next_scheduled( 'ldrt_wp_logging_prune_routine' ) ) {
        wp_schedule_event( time(), 'weekly', 'ldrt_wp_logging_prune_routine' );
    }
}

/**
 * @return Learndash_Retake_Quiz|bool
 */
function ld_Retake_Quiz_Main() {
    if ( ! class_exists( 'LDLMS_DB' ) ) {
        add_action( 'admin_notices', 'ld_retake_require_dependency' );
        return false;
    }

    // Hook the scheduling function to the WordPress action
    add_action( 'wp', 'schedule_ldrt_wp_logging_prune_routine_callback' );

    return Learndash_Retake_Quiz::instance();
}

add_action( 'plugins_loaded', 'ld_Retake_Quiz_Main' );
