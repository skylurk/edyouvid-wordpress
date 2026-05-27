<?php
/**
 * @author   WooNinjas
 * @category Admin
 * @package  Learndash_Retake_Quiz_Options/Plugin Options
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the License class
if ( file_exists( LEARNDASH_RETAKEQUIZ_DIR  . 'license/LRTQ_License.php' ) ) {
    require_once LEARNDASH_RETAKEQUIZ_DIR  . 'license/LRTQ_License.php';
}

/**
 * Class Learndash_Retake_Quiz_Options
 */
class Learndash_Retake_Quiz_Options {

    private $license_class;

    /**
     * Hook in tabs.
     */
    public function __construct () {

        $this->page_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'license'; 
        add_action( 'admin_menu', [ $this, 'ld_retake_quiz_edd_menu' ] );
        add_filter ( 'admin_footer_text', [ $this, 'remove_footer_admin' ] );
        add_action ( 'admin_notices', [ $this, 'save_settings' ] );
        $this->license_class = new LRTQ_License();
    }

    /**
     * Save Plugin's Settings
     */
    public function save_settings() {

	    $is_settings_page = isset($_GET['page']) && $_GET['page'] === 'ld-retake-quiz-options';

        if( $is_settings_page ) {
            if( current_user_can( 'manage_options' ) ) {

                if( ! empty( $_POST ) && isset( $_POST['save_lrtq_general_settings'] ) && check_admin_referer( 'lrtq_general_settings', 'lrtq_general_settings_field' ) ) {

                    $enable_disable_options  = get_option( 'lrtq_enable_disable_features' );
                    $general_settings = array();
                    $general_settings['ld_retakequiz_allow_courses'] = ( isset( $_POST['ld_retakequiz_allow_courses'] ) && $_POST['ld_retakequiz_allow_courses'] == 'on' ) ? $_POST['ld_retakequiz_allow_courses'] : '';
                     
                    $general_settings['ld_retakequiz_course'] = isset( $_POST['ld_retakequiz_course'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_course'] ) ) : array();
                    $general_settings['ld_retakequiz_lesson'] = isset( $_POST['ld_retakequiz_lesson'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_lesson'] ) ) : array();
                    $general_settings['ld_retakequiz_topic'] = isset( $_POST['ld_retakequiz_topic'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_topic'] ) ) : array();


                    if( $general_settings['ld_retakequiz_allow_courses'] == 'on' ) {

                        $general_settings['ld_retakequiz_group'] = isset( $_POST['ld_retakequiz_group'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_group'] ) ) : array();
                        
                         $general_settings['ld_retakequiz_categories'] = isset( $_POST['ld_retakequiz_categories'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_categories'] ) ) : array();
                        $general_settings['ld_retakequiz_tags'] = isset( $_POST['ld_retakequiz_tags'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_tags'] ) ) : array();

                         $general_settings['ld_retakequiz_exclude_groups'] = isset( $_POST['ld_retakequiz_exclude_groups'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_exclude_groups'] ) ) : array();
                       
                    }

                    $general_settings['ld_retakequiz_quizes'] = isset( $_POST['ld_retakequiz_quizes'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_quizes'] ) ) : array();
                   
                    $general_settings['ld_retakequiz_exclude_quizzes'] = isset( $_POST['ld_retakequiz_exclude_quizzes'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_exclude_quizzes'] ) ) : array();
                   
                    $general_settings['ld_retakequiz_exclude_users'] = isset( $_POST['ld_retakequiz_exclude_users'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ld_retakequiz_exclude_users'] ) ) : array();
                    

                    update_option( 'lrtq_general_settings', $general_settings );

                    $class = "notice notice-success";
                    $message = __( 'Settings Updated.', 'ld_retake_quiz' );
                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
                }

                // Check if the form is submitted and the nonce is valid
                if ( ! empty( $_POST ) && isset( $_POST['save_lrtq_feature_settings'] ) && check_admin_referer( 'lrtq_enable_disable_settings', 'lrtq_enable_disable_settings_field' ) ) {

                    // Initialize an empty array to store the form data
                    $enable_disable = array();

                    // Check if retake quizzes are allowed
                    $enable_disable['enable_allow_retake_quiz'] = ( isset( $_POST['ld_retakequiz_allow_retake_quiz'] ) && $_POST['ld_retakequiz_allow_retake_quiz'] == 'on' ) ? 'on' : '';

                    // Check if delay for retaking quizzes is allowed
                    $enable_disable['enable_allow_delay_quiz'] = ( isset( $_POST['ld_retake_allow_quiz_delays'] ) && $_POST['ld_retake_allow_quiz_delays'] == 'on' ) ? 'on' : '';

                    // If delay for retaking quizzes is enabled, get delay duration and type
                    if ( isset( $enable_disable['enable_allow_delay_quiz'] ) && $enable_disable['enable_allow_delay_quiz'] == 'on' ) {
                        $enable_disable['ld_retakequiz_delay'] = isset( $_POST['ld_retakequiz_delay'] ) ? sanitize_text_field( $_POST['ld_retakequiz_delay'] ) : 0;
                        $enable_disable['ld_retakequiz_delay_type'] = isset( $_POST['ld_retakequiz_delay_type'] ) ? sanitize_text_field( $_POST['ld_retakequiz_delay_type'] ) : 'Minutes';
                    }

                    // Check if deleting previous quiz attempts is allowed
                    $enable_disable['delete_previous_quiz_attempt'] = ( isset( $_POST['ld_retake_delete_previous_quiz_attempt'] ) && $_POST['ld_retake_delete_previous_quiz_attempt'] == 'on' ) ? 'on' : '';

                    // Check if negative marking is allowed
                    $enable_disable['enable_allow_negative_marking'] = ( isset( $_POST['ld_retake_negative_marking'] ) && $_POST['ld_retake_negative_marking'] == 'on' ) ? 'on' : '';

                    // Check if retake limit is allowed
                    $enable_disable['enable_allow_retake_limit'] = ( isset( $_POST['ld_retake_allow_retake_limit'] ) && $_POST['ld_retake_allow_retake_limit'] == 'on' ) ? 'on' : '';

                    // Get retake limit value
                    $enable_disable['ld_retakequiz_retake_limit'] = isset( $_POST['ld_retakequiz_retake_limit'] ) ? sanitize_text_field( $_POST['ld_retakequiz_retake_limit'] ) : 0;

                    // Check if retake logs are allowed
                    $enable_disable['enable_allow_retake_logs'] = ( isset( $_POST['ld_retake_allow_retake_logs'] ) && $_POST['ld_retake_allow_retake_logs'] == 'on' ) ? 'on' : '';

                    // Check if debug logs are enabled
                    $enable_disable['ld_retakequiz_debug_log'] = ( isset( $_POST['ld_retakequiz_debug_log'] ) && $_POST['ld_retakequiz_debug_log'] == 'on' ) ? 'on' : '';

                    // Check if random sequence for retake is allowed
                    $enable_disable['enable_ld_retake_allow_random_sequence'] = ( isset( $_POST['ld_retake_allow_random_sequence'] ) && $_POST['ld_retake_allow_random_sequence'] == 'on' ) ? 'on' : '';

                    // Update options in the database with the form data
                    update_option( 'lrtq_enable_disable_features', $enable_disable );

                    // Display a success message
                    $class = "notice notice-success";
                    $message = __( 'Settings Updated.', 'ld_retake_quiz' );
                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
                }


                // Delete quiz retake data
                if( ! empty( $_POST ) 
                    && isset( $_POST['save_delete_retake_quiz_data'] ) 
                    && check_admin_referer( 'delete_retake_quiz_data', 'delete_retake_quiz_data_field' ) ) {
                    
                    $this->ld_delete_quiz_retake_data_settings( $_POST );
                }

                if ( isset( $_POST['_wpnonce'] ) && check_admin_referer('quiz_completion_settings')
                    && wp_verify_nonce( $_POST['_wpnonce'], 'quiz_completion_settings' )
                ) {

                    $form_fields_default = array( 'quiz_completion_message' => NULL );
                    $form_fields = array();

                    foreach ( $form_fields_default as $key => $value ) {
                        if( array_key_exists( $key, $_POST ) ) {
                            $form_fields[$key] = stripslashes( esc_html( $_POST[$key] ) );
                        }
                    }

                    $form_fields = wp_parse_args($form_fields, $form_fields_default);
                    $form_fields['enable_allow_completion_message'] = ( isset( $_POST[ 'ld_retakequiz_completion_message' ] ) && $_POST[ 'ld_retakequiz_completion_message' ] == 'on' ) ? $_POST[ 'ld_retakequiz_completion_message' ] : '';

                    $form_fields['allowed_retake_limit_message'] = isset( $_POST[ 'allowed_retake_limit_message' ] ) ? $_POST[ 'allowed_retake_limit_message' ] : '';

                    update_option( 'ld_rtq_quiz_completion_setting', $form_fields );

                    $class = "notice notice-success";
                    $message = __( 'Settings Updated.', 'ld_retake_quiz' );
                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
                }
            }
        }
    }

    function ld_delete_quiz_retake_data_settings( $post ){
        
        $delete_data_by_user = isset( $_POST['delete_data_by_user'] ) ? sanitize_text_field( $_POST['delete_data_by_user'] ) : '';
        $delete_data_by_quiz = isset( $_POST['delete_data_by_quiz'] ) ? sanitize_text_field( $_POST['delete_data_by_quiz'] ) : '';
        $delete_specific_users_data = isset( $_POST['learndash_course_users'] ) ? $this->parse_selector_data( $_POST['learndash_course_users'] ) : '';
        $delete_specific_quizzes_data = isset( $_POST['learndash_retake_quiz_quizzes'] ) ? $this->parse_selector_data( $_POST['learndash_retake_quiz_quizzes'] ) : '';

        $get_users_args = array(
            'fields'        => array( 'ID' ),
            'meta_key'      => '_ld_quiz_retake_correct_q',
            'meta_value'    => '',
            'meta_compare'  => '>'
        );

        // CASES: QUIZ RETAKE DELETION
        // =================================================================
        // if : Delete all quiz retake data for all users
        // elseif : Delete specific quiz retake data for specific users selected
        // elseif : Delete all quiz retake data for specific users selected
        // elseif : Delete specific quiz retake data for all
        // ===================================================================
        
        global $wpdb;
        
        
        if( $delete_data_by_user == 'all' && $delete_data_by_quiz == 'all' ) {
            $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => '_ld_quiz_retake_correct_q' ) );
            $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => '_ld_quiz_retake_wrong_q' ) );
            $wpdb->delete( $wpdb->usermeta, array( 'meta_key' => '_ld_quiz_retake_user_attempts' ) );
            $wpdb->get_results( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_ld_quiz_retake_user_attempts_count%'" );

        } elseif ( $delete_data_by_user != 'all' && $delete_data_by_quiz == 'all' ) {
            $users = $delete_specific_users_data;
            if( $delete_data_by_quiz == 'all' && ! empty( $users ) ) {
                foreach ( $users as $user ) {
                    if( $user !== NULL ) {
                        $meta_user_id = $user;
                        delete_user_meta( $meta_user_id, '_ld_quiz_retake_correct_q' );
                        delete_user_meta( $meta_user_id, '_ld_quiz_retake_wrong_q' );
                        delete_user_meta( $meta_user_id, '_ld_quiz_retake_user_attempts' );
                        $wpdb->get_results( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_ld_quiz_retake_user_attempts_count%' AND user_id = {$meta_user_id}" );
                    }
                }
            }
        } elseif ( $delete_data_by_user != 'all' && $delete_data_by_quiz != 'all' ) {
            $data_to_update = array();
            $users = $delete_specific_users_data;

            if( ! empty( $users ) ) {
                foreach ( $users as $user ) {
                    $meta_user_id = $user;
                    $correct_answers    = get_user_meta( $meta_user_id, '_ld_quiz_retake_correct_q', true );
                    $wrong_answers      = get_user_meta( $meta_user_id, '_ld_quiz_retake_wrong_q', true );

                    if( ! empty( $correct_answers ) ) {
                        $data_to_update[$meta_user_id]['correct']   = $correct_answers;
                    }

                    if( ! empty( $wrong_answers ) ) {
                        $data_to_update[$meta_user_id]['wrong']     = $wrong_answers;
                    }
                }
            }
            
            if( ! empty( $data_to_update ) ) {
                foreach ( $data_to_update as $user_id => &$data ) {
                    
                    // Get user quiz attempt data
                    $user_quiz_attempts = $wpdb->get_results( "SELECT * FROM {$wpdb->usermeta} 
                        WHERE meta_key = '_ld_quiz_retake_user_attempts' AND user_id = {$user_id}" ); 
                    
                    foreach ( $delete_specific_quizzes_data as $quiz_post_id ) {
                        $quiz_meta = get_post_meta( $quiz_post_id, "_sfwd-quiz", true );
                        $quiz_pro_id = absint( $quiz_meta["sfwd-quiz_quiz_pro"] );
                        $delete_retake_count_key = '_ld_quiz_retake_user_attempts_count_quiz_' . $quiz_post_id;

                        if ( isset( $data['correct'][$quiz_pro_id] ) ) {
                            unset( $data['correct'][$quiz_pro_id] );
                        }

                        if ( isset( $data['wrong'][$quiz_pro_id] ) ) {
                            unset( $data['wrong'][$quiz_pro_id] );
                        }
                        
                        // Delete quiz attempt data
                        foreach( $user_quiz_attempts as $attempts ) {
                            $attempt_quiz_data  = unserialize( $attempts->meta_value );
                            $attempt_quiz_id    = $attempt_quiz_data['quiz_id'];
                            $umeta_id = $attempts->umeta_id;
                            if( $attempt_quiz_id == $quiz_post_id ) {
                                $wpdb->get_results( "DELETE FROM {$wpdb->usermeta} 
                                    WHERE meta_key = '_ld_quiz_retake_user_attempts' 
                                    AND user_id  = {$user_id} 
                                    AND umeta_id = {$umeta_id}" 
                                ); 
                            } 
                        }
                        
                        // Delete attempts count
                        $wpdb->get_results( "DELETE FROM {$wpdb->usermeta} 
                        WHERE meta_key = '{$delete_retake_count_key}' AND user_id = {$user_id}" );
                    }
                    update_user_meta( $user_id, '_ld_quiz_retake_correct_q', $data['correct'] );
                    update_user_meta( $user_id, '_ld_quiz_retake_wrong_q', $data['wrong'] );
                }
            }            
        } elseif ( $delete_data_by_user == 'all' && $delete_data_by_quiz != 'all' ) {
            error_log( 'testing3' );
            $data_to_update = array();
            $users = $wpdb->get_results( "SELECT * FROM $wpdb->users" );
            foreach( $users as $user ){
                $user_id = $user->ID;
                $correct_answers    = get_user_meta( $user_id, '_ld_quiz_retake_correct_q', true );
                $wrong_answers      = get_user_meta( $user_id, '_ld_quiz_retake_wrong_q', true );

                if( ! empty( $correct_answers ) ) {
                    $data_to_update[$user_id]['correct']   = $correct_answers;
                }

                if( ! empty( $wrong_answers ) ) {
                    $data_to_update[$user_id]['wrong']     = $wrong_answers;
                }
            }
            
            if( ! empty( $data_to_update ) ) {
                foreach ( $data_to_update as $user_id => &$data ) {
                    foreach ( $delete_specific_quizzes_data as $quiz_post_id ) {
                        $quiz_meta      = get_post_meta( $quiz_post_id, "_sfwd-quiz", true );
                        $quiz_pro_id    = absint( $quiz_meta["sfwd-quiz_quiz_pro"] );

                        if ( isset( $data['correct'][$quiz_pro_id] ) ) {
                            unset( $data['correct'][$quiz_pro_id] );
                        }

                        if ( isset( $data['wrong'][$quiz_pro_id] ) ) {
                            unset( $data['wrong'][$quiz_pro_id] );
                        }

                        $delete_retake_count_key = '_ld_quiz_retake_user_attempts_count_quiz_' . $quiz_post_id;
                        $wpdb->get_results( "DELETE FROM {$wpdb->usermeta} 
                        WHERE meta_key = '{$delete_retake_count_key}' AND user_id = {$user_id}" );
                    }
                    update_user_meta( $user_id, '_ld_quiz_retake_correct_q', $data['correct'] );
                    update_user_meta( $user_id, '_ld_quiz_retake_wrong_q', $data['wrong'] );
                    // delete_user_meta( $user_id, '_ld_quiz_retake_user_attempts', true );
                }
            }
        }

        $class = "notice notice-success";
        $message = __( 'LearnDash Retake Quiz data deleted successfully.', 'ld_retake_quiz' );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    
    }

    public  function parse_selector_data( $data ) {
        if( ! empty( $data ) ) {
            $data = (array) json_decode( stripslashes( $data[0] ) );
        }
        return $data;
    }

    public function get_license_class() {
        return $this->license_class;
    }

    /**
     * Setting notification
     */
    public function save_settings_notification() {
        $class = 'ld_retake-notice hidden notice notice-success is-dismissible';
        $message = __( 'Settings Updated.', 'ld_retake_quiz' );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }

    /**
     * Add plugin's menu
     */
    public function ld_retake_quiz_edd_menu() {
		$ld_menu_user_cap = LDPR_ADMIN_CAPABILITY_CHECK;
        add_submenu_page(
            'learndash-lms',
            __( 'Retake Quiz', 'ld_retake_quiz' ),
            __( 'Retake Quiz', 'ld_retake_quiz' ),
            $ld_menu_user_cap,
            'ld-retake-quiz-options',
            [ $this, 'ld_retake_quiz_options' ]
        );
        
    }

    /**
     * Setting page data
     */
    public function ld_retake_quiz_options() {
        ?>
        <div id="wrap" class="ld_retake-settings-wrapper">
            <div id="icon-options-general" class="icon32"></div>
            <h1 class="ls-retake-quizz-title"><?php echo __( 'Learndash Retake Quiz Settings', 'ld_retake_quiz' ); ?></h1>

            <div class="ls-retake-quizz-nav-wrappr">
                <?php
                $ld_retake_settings_sections = $this->ld_retake_get_setting_sections();
                foreach( $ld_retake_settings_sections as $key => $ld_retake_settings_section ) {
                    ?>
                    <a href="?page=ld-retake-quiz-options&tab=<?php echo $key; ?>"
                       class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ((count($ld_retake_settings_sections) == 1 && $key == 'retake_quiz_logs') ? 'nav-tab-active' : '' ); ?>">
                        <i class="fa <?php echo $ld_retake_settings_section['icon']; ?>" aria-hidden="true"></i>
                        <?php _e( $ld_retake_settings_section['title'], 'ld_retake_quiz' ); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
            <div class="ls-retake-quizz-pnl">
            <?php
            foreach( $ld_retake_settings_sections as $key => $ld_retake_settings_section ) {
                if( $this->page_tab == $key ) {
                    include( 'templates/' . $key . '.php' );
                } elseif( count($ld_retake_settings_sections) == 1 && $key == 'retake_quiz_logs' ) {
                    include( 'templates/' . $key . '.php' );
                }
            }
            ?>
            </div>
        </div>
        <?php
    }

    /**
     * Retrieve LearnDash Retake Quiz Settings Sections.
     *
     * @return array An array of setting sections.
     */
    public function ld_retake_get_setting_sections() {
        // Check if the current user has the capability to manage options.
        if (current_user_can('manage_options')) {
            $ld_retake_settings_sections = array(
                'license' => array(
                    'title' => __('License Option', 'ld_retake_quiz'),
                    'icon'  => '',
                ),
                'general' => array(
                    'title' => __('General Settings', 'ld_retake_quiz'),
                    'icon'  => '',
                ),
            );

            // Get the enable/disable options.
            $enable_disable_options = get_option('lrtq_enable_disable_features');

            // Check if retaking quizzes is enabled and the user has the capability to manage options.
            if (
                isset($enable_disable_options['enable_allow_retake_quiz']) &&
                $enable_disable_options['enable_allow_retake_quiz'] === 'on'
            ) {
                $ld_retake_settings_sections['features'] = array(
                    'title' => __('Retake Settings', 'ld_retake_quiz'),
                    'icon'  => '',
                );

                $ld_retake_settings_sections['quiz_completion'] = array(
                    'title' => __('Quiz Completion', 'ld_retake_quiz'),
                    'icon'  => '',
                );

                $ld_retake_settings_sections['delete_retake_quiz_data'] = array(
                    'title' => __('Delete Retake Quiz Data', 'ld_retake_quiz'),
                    'icon'  => '',
                );
            }

            // Check if retake quiz logs are enabled and the user has the capability to manage options.
            if (
                isset($enable_disable_options['enable_allow_retake_logs']) &&
                $enable_disable_options['enable_allow_retake_logs'] === 'on'
            ) {
                $ld_retake_settings_sections['retake_quiz_logs'] = array(
                    'title' => __('Retake Quiz Logs', 'ld_retake_quiz'),
                    'icon'  => '',
                );
            }

            // Check if debug logs are enabled and the user has the capability to manage options.
            if (
                isset($enable_disable_options['ld_retakequiz_debug_log']) &&
                $enable_disable_options['ld_retakequiz_debug_log'] === 'on'
            ) {
                $ld_retake_settings_sections['debug-logs'] = array(
                    'title' => __('Debug Logs', 'ld_retake_quiz'),
                    'icon'  => '',
                );

                $ld_retake_settings_sections['system-info'] = array(
                    'title' => __('System Information', 'ld_retake_quiz'),
                    'icon'  => '',
                );
            }

            // Apply filters and return the sections.
            return apply_filters('ld_retake_settings_sections', $ld_retake_settings_sections);
        }

        // Return an empty array if the user cannot manage options.
        return array();
    }


    /**
     * Add footer branding
     *
     * @param $footer_text
     * @return mixed
     */
    public function remove_footer_admin ( $footer_text ) {
        if( isset( $_GET['page'] ) && ( $_GET['page'] == 'ld-retake-quiz-options' ) ) {
            _e( 'Fueled by <a href="http://www.wordpress.org" target="_blank">WordPress</a> | developed and designed by <a href="https://wooninjas.com" target="_blank">The WooNinjas</a></p>');
        } else {
            return $footer_text;
        }
    }
}
$GLOBALS['Learndash_Retake_Quiz_Options'] = new Learndash_Retake_Quiz_Options();