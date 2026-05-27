<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class defines ldrh logic code for the plugin.
 */

if (!class_exists('LDRH')) {

    class LDRH {
        
        public function __construct() {
            // add front end style and scripts
            add_action('wp_enqueue_scripts', array($this, 'ldrh_wp_enqueue_assets'));
            
            // add admin style and scripts
            add_action('admin_enqueue_scripts', array($this, 'ldrh_admin_enqueue_assets'));
            
            // assign capabilities to Admin/Group Leader 
            add_action('admin_init', array($this, 'ldrh_add_plugin_capability'));
            
            // keep tracking of user course completion history
            add_action('learndash_before_course_completed', array($this, 'ldrh_add_course_completion_time'), 11, 1);
            
            // override completion time displayed on certificate
            add_filter('learndash_courseinfo', array($this, 'ldrh_certificate_time_callback'), 20, 2);
            
            // override template redirect for certificate functionality
            add_action('template_redirect', array($this, 'ldrh_certificate_redirect_override'), 9);
            
        }
        
        public function ldrh_wp_enqueue_assets($hook) {
            
        }
        
        public function ldrh_admin_enqueue_assets($hook) {
            // assets for general settings page
            if($hook == 'learndash-lms_page_ldrh-settings'){
                wp_enqueue_style('ldrh-jquery-confirm-style', "https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css");
                
                wp_enqueue_script('ldrh-jquery-confirm-script', "https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js", array('jquery'));
                wp_enqueue_script('ldrh-settings-script', LDRH_PLUGIN_URL . 'assets/admin/js/settings_script.js', array('ldrh-jquery-confirm-script'));
                wp_localize_script('ldrh-settings-script', 'variables', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'migrate_title' => __('Migrate', 'ldrh'), 
                    'migrate_message' => __('Are you sure you want to run migration process?', 'ldrh')
                ));
            }
            
            // assets for history page
            if($hook == 'learndash-lms_page_ldrh_refresher_history'){
                wp_enqueue_script('ldrh-history-script', LDRH_PLUGIN_URL . 'assets/admin/js/history_script.js', array('jquery'));
                wp_localize_script('ldrh-history-script', 'variables', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                ));
            }
        }
        
        public function ldrh_add_plugin_capability() {
            // Group Leader capabilities
            $leaderRole = get_role('group_leader');
            $leaderRole->add_cap('ldrh_manage_reports');

            // Admin capabilities
            $adminRole = get_role('administrator');
            $adminRole->add_cap('ldrh_manage_reports');
        }
        
        public function ldrh_add_course_completion_time($data) {
            $user_id = $data['user']->ID;
            $course_id = $data['course']->ID;
            $time = $data['completed_time'];
            
            $is_course_refreshed = get_post_meta($course_id, 'ldrh_is_course_refreshed', true);
            if($is_course_refreshed){
                $last = null;
                $array = null;
                $courseInfo = get_user_meta($user_id, 'ldrh_course_info_' . $course_id, true);

                if ($courseInfo && is_array($courseInfo)) {
                    foreach ($courseInfo as $key => $value) {
                        if (next($courseInfo) === false) {
                            $last = $key;
                            $array = $value;
                        }
                    }

                    if ($last && $array['completion_date'] == '') {
                        $array['completion_date'] = $time;
                        $courseInfo[$last] = $array;
                        update_user_meta($user_id, 'ldrh_course_info_' . $course_id, $courseInfo);
                    }
                } else {
                    $unique = uniqid();
                    $courseInfo = array();
                    $courseInfo[$unique] = array('completion_date' => $time, 'is_email_sent' => false);
                    update_user_meta($user_id, 'ldrh_course_info_' . $course_id, $courseInfo);
                }

                $meta_key = 'ldrh_completion_history_' . $course_id;

                $course_completed = get_user_meta($user_id, $meta_key, true);
                if ($course_completed && is_array($course_completed)) {
                    if($last){
                        $course_completed[$last] = $time;
                    }
                } else {
                    $course_completed = array();
                    $course_completed[$unique] = $time;
                }

                update_user_meta($user_id, $meta_key, $course_completed);
            }
        }
        
        function ldrh_certificate_time_callback($time_formated, $shortcode_atts) {
            if(isset($_GET['cert_index'])){
                $index = $_GET['cert_index'];
                if (isset($shortcode_atts['show']) && $shortcode_atts['show'] == 'completed_on') {
                    $user_history = get_user_meta($shortcode_atts['user_id'], 'ldrh_completion_history_'.$shortcode_atts['course_id'], true);
                    if(isset($user_history[$index])){
                        $time_formated = date_i18n($shortcode_atts['format'], $user_history[$index]);
                    }
                }
            }

            return $time_formated;
        }
        
        public function ldrh_certificate_redirect_override() {
            global $post;
            
            if ( empty( $post ) ) {
                return;
            }

            if ( ! ( $post instanceof WP_Post ) ) {
		return;
            }

            if ( get_query_var( 'post_type' ) ) {
		$post_type = get_query_var( 'post_type' );
            } else {
		if ( ! empty( $post ) ) {
                    $post_type = $post->post_type;
		}
            }

            if ( empty( $post_type ) ) {
		return;
            }
            
            if ('sfwd-certificates' === $post_type) {
		if ( is_user_logged_in() ) {
                    if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			$course_id = intval( $_GET['course_id'] );

			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) {
                            $cert_user_id = intval( $_GET['user'] );
			} else {
                            $cert_user_id = get_current_user_id();
			}

			$view_user_id = get_current_user_id();

			if ( ( isset( $_GET['cert-nonce'] ) ) && ( ! empty( $_GET['cert-nonce'] ) ) ) {
                            if ( wp_verify_nonce( esc_attr( $_GET['cert-nonce'] ), $course_id . $cert_user_id . $view_user_id ) ) {
                                if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( intval( $cert_user_id ) !== intval( $view_user_id ) ) ) {
                                    wp_set_current_user( $cert_user_id );
				}

				/**
				 * Include library to generate PDF
				 */
				require_once LEARNDASH_LMS_PLUGIN_DIR.'includes/ld-convert-post-pdf.php';
				post2pdf_conv_post_to_pdf();
				die();
                            }
			}
                    }
		}
            }
        }
        
    }
    
    $ldrh = new LDRH();
    
}