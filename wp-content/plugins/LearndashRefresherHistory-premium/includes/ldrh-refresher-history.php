<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('LDRH_Refresher_History')) {

    class LDRH_Refresher_History {

        public function __construct() {
            add_action('admin_menu', array($this, "ldrh_add_refresher_history_page"), 125);
        }

        function ldrh_add_refresher_history_page() {
            add_submenu_page('learndash-lms', __('Refresher History', 'ldrh'), __('Refresher History', 'ldrh'), 'ldrh_manage_reports', 'ldrh_refresher_history', array($this, 'ldrh_refresher_history_callback'));
        }

        function ldrh_refresher_history_callback() {
            
            $current_page = $_GET['paged'] ? (int) $_GET['paged'] : 1;
            $users_per_page = 10;//get_option('ldmv2_users_per_page') ? (int) get_option('ldmv2_users_per_page') : 10;
            
            $course_args = array(
                'post_type' => 'sfwd-courses',
                'showposts' => -1
            );
            
            $user_query_args = array(
                'number' => $users_per_page,
                'paged' => $current_page,
                'orderby' => 'display_name',
                'order' => 'ASC',
            );
            // get users by current user role
            $currentUser = wp_get_current_user();
            if ($currentUser && in_array('group_leader', (array) $currentUser->roles)) {
                $users_ids = ldrh_get_leader_groups_users($currentUser->ID);
                $user_query_args['include'] = $users_ids;
                
                $courses_ids = learndash_get_groups_courses_ids($currentUser->ID);
                $course_args['post__in'] = $courses_ids;
            }
            
            $courses = get_posts($course_args);

            $user_query = new WP_User_Query($user_query_args);
            $users = $user_query->results;
            $total_users = $user_query->get_total(); // How many users we have in total (beyond the current page)
            $num_pages = ceil($total_users / $users_per_page);
            ?>
            <h2><?php _e('Refresher History', 'ldrh') ?></h2>

            <table class="widefat" id="ldrh_refresher_history_report">
                <thead>
                    <tr>
                        <th><?php _e('User', 'ldrh') ?></th>
                        <th><?php _e('Course', 'ldrh') ?></th>
                        <th><?php _e('Date Of Completion', 'ldrh') ?></th>
                        <th><?php _e('History List', 'ldrh') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(count($users)){
                        foreach ($users as $user) {
                            $record = $this->ldrh_get_user_history_record($user, $courses);
                            ?>
                            <tr>
                                <td><?php echo $record['user']; ?></td>
                                <td><?php echo $record['courses']; ?></td>
                                <td><?php echo $record['completion']; ?></td>
                                <td><?php echo $record['link']; ?></td>
                            </tr>
                            <?php
                        }
                    }else{
                    ?>
                        <tr><td colspan="4"><?php _e('No records available', 'ldrh') ?></td></tr>
                    <?php    
                    }
                    ?>
                </tbody>
            </table>
            <div class="matrix-pagination-container">
                <?php
                // Previous page
                if ($current_page > 1) {
                    $paginationArgs = array('paged' => $current_page - 1);

                    echo '<a href="' . add_query_arg($paginationArgs) . '"><< Previous Page</a>';
                }

                // Next page
                if ($current_page < $num_pages) {
                    $paginationArgs = array('paged' => $current_page + 1);

                    echo '<a href="' . add_query_arg($paginationArgs) . '">Next Page >></a>';
                }
                ?>
            </div>
            <?php
        }
        
        private function ldrh_get_user_history_record($user, $courses) {
            $record = array('user' => ldrh_get_user_name($user), 'courses' => '---', 'completion' => '---', 'link' => '---');
            $firstCourse = null;
            $counter = 0;
            if(count($courses)){
                $select = '<select class="user_courses_dropdown">';
                foreach($courses as $course){
                    if($counter == 0){
                        $firstCourse = $course;
                    }
                    
                    $select .= '<option id="'.$user->ID.'_'.$course->ID.'">'.$course->post_title.'</option>';
                    
                    $counter++;
                }
                $select .= '</select>';
                
                $record['courses'] = $select;
            }
            
            if($firstCourse){
                $course_completed = get_user_meta($user->ID, 'ldrh_completion_history_' . $firstCourse->ID, true);
                
                if(is_array($course_completed) && count($course_completed)){
                    $historyLastElement = end($course_completed);
                    reset($course_completed);
                    
                    $completionDate = new DateTime('@' . $historyLastElement);
                    $record['completion'] = $completionDate->format('d-m-Y');
                    $record['link'] = '<a href="'.admin_url('admin.php').'?page=ldrh_refresher_user_history&userId='.$user->ID.'&courseId='.$firstCourse->ID.'" class="button button-primary">'. __('View History', 'ldrh').'</a>';
                }
            }
            
            return $record;
        }

    }

    $ldrh_refresher_history = new LDRH_Refresher_History();
}

