<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class defines ldrh cronjobs
 */

if (!class_exists('LDRH_Cron')) {

    class LDRH_Cron {

        public function __construct() {
            // cronjob to check if user course needs to be refreshed
            add_action('ldrh_refresher_courses_event', array($this, 'ldrh_refresher_courses_logic_callback'));
        }

        public function ldrh_refresher_courses_logic_callback() {
            set_time_limit(0);
            
            $refreshed = array();
            $groups = array();
            $general_expiration = get_option('ldrh_expiration_period', '12');
            $general_grace = get_option('ldrh_grace_period', '30');
            $send_student_email = get_option('ldrh_email_student', 1);
            
            global $wpdb;

            // get all users in system
            $users = $wpdb->get_results("SELECT * FROM $wpdb->users");
            
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
            
            // loop over users and check courses refresher
            foreach ($users as $u) {
                foreach ($courses as $course) {
                    $course_meta = $courses_meta[$course->ID];
                    // check that user has access for course (assigned to him)
                    if ($course_meta['exp_period'] && $course_meta['grace_period'] && sfwd_lms_has_access($course->ID, $u->ID)) {
                        // get stored data for course in user meta 
                        $courseInfo = get_user_meta($u->ID, 'ldrh_course_info_' . $course->ID, true);
                        
                        if ($courseInfo && is_array($courseInfo)) {
                            foreach ($courseInfo as $key => $value) {
                                if (next($courseInfo) === false) {
                                    if ($value['completion_date']) {
                                        $completionDate = new DateTime('@' . $value['completion_date']);
                                        $expiryDate = clone $completionDate;
                                        $expiryDate->modify('+' . $course_meta['exp_period'] . ' months');
                                        $graceDate = clone $expiryDate;
                                        $graceDate->modify('-' . $course_meta['grace_period'] . ' days');
                                        
                                        // if it is refresher required date then send email to user then clear all user progress in course according to reset type
                                        if (strtotime($graceDate->format('Y-m-d')) < time() && $value['is_email_sent'] == false) {
                                            if($send_student_email){
                                                // send mail to student
                                                $email_args = array();

                                                $email_args['to'] = $u->user_email;
                                                $email_args['course'] = $course->post_title;

                                                $sent = ldrh_send_email('notify_student', $email_args);
                                            }else{
                                                $sent = true;
                                            }
                                            
                                            if($sent){
                                                $value['is_email_sent'] = true;
                                                $courseInfo[$key] = $value;
                                                
                                                //create new array element for the next refresher
                                                $unique = uniqid();
                                                $courseInfo[$unique] = array('completion_date' => '', 'is_email_sent' => false);
                                                update_user_meta($u->ID, 'ldrh_course_info_' . $course->ID, $courseInfo);
                                                
                                                // remove progress
                                                ldrh_remove_course_data($u->ID, $course->ID);
                                                if($course_meta['course_reset'] == 'enrollment'){
                                                    ld_update_course_access($u->ID, $course->ID, true);
                                                }
                                                
                                                // add to array to send to admin 
                                                if(isset($refreshed[$u->ID.'##'.$u->display_name])){
                                                    $refreshed[$u->ID.'##'.$u->display_name][] = $course->post_title;
                                                }else{
                                                    $refreshed[$u->ID.'##'.$u->display_name] = array($course->post_title);
                                                }
                                                
                                                // add to array to send to leaders
                                                $group = ldrh_user_course_belong_same_group($u->ID, $course->ID);
                                                if($group){
                                                    if(isset($groups[$group])){
                                                        $groups[$group][] = array('id' => $u->ID, 'user' => $u->display_name, 'course' => $course->ID);
                                                    }else{
                                                        $groups[$group] = array(array('id' => $u->ID, 'user' => $u->display_name, 'course' => $course->ID));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if(count($refreshed)){
                // send email to admin if enabled
                if(get_option('ldrh_email_admin', 0)) {
                    $admin_email_args = array();

                    $admin_email_args['to'] = get_option('ldrh_admin_email', '');
                    $admin_email_args['table'] = ldrh_get_refresher_table($refreshed);

                    ldrh_send_email('notify_admin', $admin_email_args);
                }
            }
            if(count($groups)){
                // send email to leaders if enabled
                if(get_option('ldrh_email_leaders', 0)) {
                    foreach($groups as $group => $refreshed){
                        // send mail to group leaders
                        $leader_email_args = array();

                        $to = array();
                        $group_leaders = learndash_get_groups_administrators($group);
                        foreach($group_leaders as $group_leader){
                            $to[] = $group_leader->user_email;
                        }
                        $leader_email_args['to'] = $to;
                        $leader_email_args['table'] = ldrh_get_leader_table($refreshed);

                        ldrh_send_email('notify_leaders', $leader_email_args);
                    }
                }
            }
            
        }
    }

    $ldrh_cron = new LDRH_Cron();
}