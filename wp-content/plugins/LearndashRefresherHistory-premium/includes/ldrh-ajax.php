<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class defines all ajax callbacks in the plugin.
 */

if (!class_exists('LDRH_Ajax')) {

    class LDRH_Ajax {

        public function __construct() {
            // get Course info dynamically     
            add_action('wp_ajax_ldrh_get_course_info_dynamically', array($this, 'ldrh_get_course_info_dynamically_callback'));
            
            // get user course history
            add_action('wp_ajax_ldrh_get_user_course_history', array($this, 'ldrh_get_user_course_history_callback'));
            
            // migration
            add_action('wp_ajax_ldrh_migration', array($this, 'ldrh_migration_callback'));
        }
        
        public function ldrh_get_course_info_dynamically_callback() {
            $u_id = $_POST['u_id'];
            $course_id = $_POST['course_id'];
            $record_course_info = '';
            $completion = '---';
            $link = '---';
            if($course_id){
                $course_completed = get_user_meta($u_id, 'ldrh_completion_history_' . $course_id, true);
                
                if(is_array($course_completed) && count($course_completed)){
                    $historyLastElement = end($course_completed);
                    reset($course_completed);
                    
                    $completionDate = new DateTime('@' . $historyLastElement);
                    $completion = $completionDate->format('d-m-Y');
                    $record_course_info .= '<td>' . $completion . '</td>';
                    $link = '<a href="'.admin_url('admin.php').'?page=ldrh_refresher_user_history&userId='.$u_id.'&courseId='.$course_id.'" class="button button-primary">'. __('View History', 'ldrh').'</a>';
                    $record_course_info .= '<td>' . $link . '</td>';
                }else{
                    $record_course_info .= '<td>' . $completion . '</td>';
                    $record_course_info .= '<td>' . $link . '</td>';
                }
            }

            print_r(json_encode(array('status' => true, 'html' => $record_course_info)));
            
            wp_die(); // this is required to terminate immediately and return a proper respons
        }
        
        public function ldrh_get_user_course_history_callback() {
            $user_id = intval($_POST['user_id']);
            $course_id = intval($_POST['course_id']);
            $html = '';
            
            if($user_id && $course_id){
                $course_completed = get_user_meta($user_id, 'ldrh_completion_history_' . $course_id, true);
                if(is_array($course_completed) && count($course_completed)){
                    $html = '<table>';

                    $html .= '<thead>';
                    $html .= '<tr>';
                    $html .= '<th>'.__('Date Of Completion', 'ldrh').'</th>';
                    $html .= '<th>'.__('Certificate Link', 'ldrh').'</th>';
                    $html .= '</tr>';
                    $html .= '</thead>';

                    $html .= '<tbody>';
                    foreach($course_completed as $index => $timestamp){
                        $time = new DateTime('@' . $timestamp);
                        $html .= '<tr>';
                        $html .= '<td>';
                        $html .= '<span>'.$time->format('d-m-Y').'</span>';
                        $html .= '</td>';
                        $html .= '<td>';
                        $cert_link = ldrh_get_course_certificate_link($course_id, $user_id);
                        if($cert_link){
                            $html .= '<a href="'.$cert_link.'&cert_index='.$index.'" class="button button-primary" target="_blank">'.__('Open Certificate', 'ldrh').'</a>';
                        }else{
                            $html .= 'N/A';
                        }
                        $html .= '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</tbody>';

                    $html .= '</table>';
                }else{
                    $html = '<p>'.__('No history found.', 'ldrh').'</p>';
                }
            }else{
                $html = '<p>'.__('No history found', 'ldrh').'</p>';
            }
            
            print_r(json_encode(array('status' => true, 'html' => $html)));
            
            wp_die(); // this is required to terminate immediately and return a proper respons
        }
        
        public function ldrh_migration_callback() {
            set_time_limit(0);
            
            $offset = intval($_POST['offset']);
            $no_of_rows = 5;
            $per_ajax = $no_of_rows - 1;
            $end_ajax = $offset + $per_ajax;
            
            $general_expiration = get_option('ldrh_expiration_period', '12');
            $general_grace = get_option('ldrh_grace_period', '30');
            
            // get all expiration courses
            $courses_meta = array();
            $args = array(
                'post_type'      => 'sfwd-courses',
                'meta_key'       => 'ldrh_is_course_refreshed',
                'meta_value'     => '1',
                'posts_per_page' => -1
            );
            $courses_query = new WP_Query( $args );
            $courses = $courses_query->get_posts();
            
            foreach ($courses as $course) {
                $course_meta = array();
                
                $courseMetasArr = get_post_meta($course->ID);
                $course_meta['exp_period'] = isset($courseMetasArr['ldrh_expiration_period'][0]) && $courseMetasArr['ldrh_expiration_period'][0] ? $courseMetasArr['ldrh_expiration_period'][0] : $general_expiration;
                $course_meta['grace_period'] = isset($courseMetasArr['ldrh_grace_period'][0]) && $courseMetasArr['ldrh_grace_period'][0] ? $courseMetasArr['ldrh_grace_period'][0] : $general_grace;
                $course_meta['course_reset'] = isset($courseMetasArr['ldrh_course_reset'][0]) ? $courseMetasArr['ldrh_course_reset'][0] : "progress";
                
                $courses_meta[$course->ID] = $course_meta;
            }
            
            $user_query_args = array(
                'number' => $no_of_rows,
                'offset' => $offset - 1,
                'orderby' => 'display_name',
                'order' => 'ASC',
            );
            $user_query = new WP_User_Query($user_query_args);
            $users = $user_query->results;
            $total_users = $user_query->get_total(); // How many users we have in total (beyond the current page)
            
            // loop over users and check courses refresher
            foreach ($users as $u) {
                foreach ($courses as $course) {
                    $course_meta = $courses_meta[$course->ID];
                    // check that user has access for course (assigned to him)
                    if ($course_meta['exp_period'] && $course_meta['grace_period'] && sfwd_lms_has_access($course->ID, $u->ID)) {
                        $time = get_user_meta($u->ID, 'course_completed_' . $course->ID, true);
                        
                        if($time){
                            $unique = uniqid();
                            $courseInfo = array();
                            $courseInfo[$unique] = array('completion_date' => $time, 'is_email_sent' => false);
                            update_user_meta($u->ID, 'ldrh_course_info_' . $course->ID, $courseInfo);
                            
                            $course_completed = array();
                            $course_completed[$unique] = $time;
                            
                            update_user_meta($u->ID, 'ldrh_completion_history_' .$course->ID, $course_completed);
                        }
                    }
                }
            }
            
            if($total_users > $end_ajax){
                $status = true;
                $message = '';
                $offset = $end_ajax + 1;
                $completed = round(($end_ajax/$total_users)*100, 2).'%';
            }else{
                $status = false;
                update_option('ldrh_migrate', 1);
                $message = __('Migration process finished successfully', 'ldrh');
                $offset = 0;
                $completed = '100%';
            }
            
            print_r(json_encode(array('status' => $status, 'message' => $message, 'offset' => $offset, 'completed' => $completed)));

            wp_die(); // this is required to terminate immediately and return a proper respons
        }
    }

    $ldrh_ajax = new LDRH_Ajax();
}