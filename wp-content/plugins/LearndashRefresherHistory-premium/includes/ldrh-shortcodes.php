<?php
if (!defined('ABSPATH'))
    die;

if (!class_exists('LDRH_Shortcodes')) {

    class LDRH_Shortcodes {

        public function __construct() {
            add_shortcode('ldrh_refresher_courses_notifications', array($this, 'ldrh_refresher_courses_notifications_callback'));
            
            add_shortcode('ldrh_user_course_history', array($this, 'ldrh_user_course_history_callback'));
        }

        public function ldrh_refresher_courses_notifications_callback($atts) {
            ob_start();

            wp_enqueue_style('ldrh_history_shortcode_style', LDRH_PLUGIN_URL . 'assets/frontend/css/notification_shortcode.css');
            
            $args = shortcode_atts(array(
                'user_id' => get_current_user_id(),
                'show_lists' => 'all',
                'expiration_title' => __('Expired Courses', 'ldrh'),
                'grace_title' => __('Grace Courses', 'ldrh'),
                'days_left' => 1
                ), $atts);

            $error = false;
            $errorMessage = '';
            if ($args["user_id"]) {
                $user = get_user_by('ID', $args["user_id"]);
                if (!$user) {
                    $error = true;
                    $errorMessage = __('No user found', 'ldrh');
                }
            } else {
                $error = true;
                $errorMessage = __('No user found', 'ldrh');
            }
            ?>
            <div class="learndash-wrapper ldrh_user_notifications_container">
                <?php
                if($error){
                    ?>
                    <p><?php echo $errorMessage; ?></p>    
                    <?php
                }else{
                    $courses = get_posts(array(
                        'post_type' => 'sfwd-courses',
                        'showposts' => -1,
                    ));
                    //$courses = ld_get_mycourses($userId, array('fields' => 'all'));
                    $expiredArray = array();
                    $graceArray = array();
                    $daysArray = array();
                    foreach ($courses as $course) {
                        $info = $this->ldrh_get_course_status($user->ID, $course->ID, false);
                        if ($info['status'] == __('Refresher Overdue', 'ldrh')) {
                            $expiredArray[] = $course;
                        } elseif ($info['status'] == __('Refresher Required', 'ldrh')) {
                            $graceArray[] = $course;
                            $daysArray[] = $info['days'];
                        }
                    }

                    if(count($expiredArray) == 0 && count($graceArray) == 0){
                        ?>
                        <p><?php _e('There is no refresher notification', 'ldrh'); ?></p>    
                        <?php
                    }

                    if ($args["show_lists"] == 'all' || $args["show_lists"] == 'expired') {
                        ?>
                        <div class="ld-item-list">
                            <div class="ld-section-heading">
                                <h3 class="ldrh-notification-title"><?php echo $args["expiration_title"]; ?></h3>
                            </div>
                        <?php
                        if (count($expiredArray)){
                        ?>

                            <div class="ld-item-list-items">
                            <?php
                            foreach ($expiredArray as $expiredCourse) {
                                ?>
                                <div class="ld-item-list-item ld-item-list-item-course">
                                    <div class="ld-item-list-item-preview"><a href="<?php the_permalink($expiredCourse); ?>" class="ld-item-name ldrh-expiration-course-link"><?php echo $expiredCourse->post_title; ?></a></div>
                                </div>
                                <?php
                            }
                            ?>
                            </div>

                        <?php
                        }else{
                        ?>
                        <p><?php _e('There are no expired courses', 'ldrh'); ?></p>    
                        <?php
                        }
                        ?>
                        </div>
                        <?php
                    }

                    if ($args["show_lists"] == 'all' || $args["show_lists"] == 'grace') {
                        ?>
                        <div class="ld-item-list">
                            <div class="ld-section-heading">
                                <h3 class="ldrh-notification-title"><?php echo $args["grace_title"]; ?></h3>
                            </div>
                        <?php
                        if (count($graceArray)){
                        ?>

                            <div class="ld-item-list-items">
                                <?php
                                foreach ($graceArray as $key => $graceCourse) {
                                    ?>
                                    <div class="ld-item-list-item ld-item-list-item-course">
                                        <div class="ld-item-list-item-preview"><a href="<?php the_permalink($graceCourse); ?>" class="ld-item-name ldrh-grace-course-link"><?php echo $graceCourse->post_title; ?></a> <?php if(isset($daysArray[$key]) && $daysArray[$key]){ ?><div class="ld-item-details"><div class="ld-status ld-status-waiting ld-secondary-background"><?php echo $daysArray[$key] . ' ' . __('day(s) to expire.', 'ldrh'); ?></div></div><?php } ?></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        <?php
                        }else{
                        ?>
                        <p><?php _e('There are no courses in grace period', 'ldrh'); ?></p>    
                        <?php    
                        }
                        ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <?php
            return ob_get_clean();
        }

        private function ldrh_get_course_status($userId, $courseId){
            $info = array('status' => '', 'days' => 0);
            $historyLastKey = null;
            $historyLastElement = null;
            $historyArr = get_user_meta($userId, 'ldrh_completion_history_' . $courseId, true);
            if(is_array($historyArr) && count($historyArr)){
                foreach ($historyArr as $key => $value) {
                    if (next($historyArr) === false) {
                        $historyLastKey = $key;
                        $historyLastElement = $value;
                    }
                }
            }
            if($historyLastKey){
                $info['status'] = learndash_course_status($courseId, $userId);
                if ($info['status'] == __('Not Started', 'learndash')) {
                    $expPeriod = get_post_meta($courseId, 'ldrh_expiration_period', true) ? get_post_meta($courseId, 'ldrh_expiration_period', true) : get_option('ldrh_expiration_period', '12');
                    $refPeriod = get_post_meta($courseId, 'ldrh_grace_period', true) ? get_post_meta($courseId, 'ldrh_grace_period', true) : get_option('ldrh_grace_period', '30');
                    $courseInfo = get_user_meta($userId, 'ldrh_course_info_' . $courseId, true);
                    if ($expPeriod && $refPeriod && $courseInfo && is_array($courseInfo) && count($courseInfo) > 0) {
                        if ($historyLastElement) {
                            $completionDate = new DateTime('@' . $historyLastElement);
                            $overdueDate = clone $completionDate;
                            $overdueDate->modify('+' . $expPeriod . ' months');
                            $requiredDate = clone $overdueDate;
                            $requiredDate->modify('-' . $refPeriod . ' days');
                            if (strtotime($overdueDate->format('Y-m-d')) < time()) {
                                $info['status'] = __('Refresher Overdue', 'ldrh');
                            } elseif (strtotime($requiredDate->format('Y-m-d')) < time()) {
                                $info['status'] = __('Refresher Required', 'ldrh');
                                $interval = $requiredDate->diff($overdueDate);
                                $info['days'] = $interval->days;
                            }
                        }
                    }
                }
            }

            return $info;
        }
        
        public function ldrh_user_course_history_callback($atts) {
            ob_start();

            $args = shortcode_atts(array(
                'user_id' => get_current_user_id(),
                'title' => __('Courses History', 'ldrh'),
                'courses_label' => __('Courses', 'ldrh'),
                ), $atts);
            
            $courses = get_posts(array(
                'post_type' => 'sfwd-courses',
                'showposts' => -1,
            ));
            
            wp_enqueue_style('ldrh_history_shortcode_style', LDRH_PLUGIN_URL . 'assets/frontend/css/history_shortcode.css');
                
            wp_enqueue_script('ldrh_history_shortcode_script', LDRH_PLUGIN_URL . 'assets/frontend/js/history_shortcode.js', array('jquery'));
            wp_localize_script('ldrh_history_shortcode_script', 'variables', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
            ));
            ?>
            <div class="learndash-wrapper ldrh_user_history_container">
                <div class="ld-section-heading">
                    <h3 class="ldrh_user_history_title"><?php echo $args["title"]; ?></h3>
                </div>
                <?php if(!$args['user_id']){ ?>
                <p><?php _e('No user found', 'ldrh') ?></p>
                <?php }elseif(count($courses) == 0){ ?>
                <p><?php _e('No courses found', 'ldrh') ?></p>
                <?php }else{ ?>
                <div class="ldrh_filter_container">
                    <label><?php echo $args['courses_label']; ?></label>
                    <select class="ldrh_course_filter" data-user="<?php echo $args['user_id']; ?>">
                        <option value=""><?php _e('Choose Course', 'ldrh') ?></option>
                        <?php foreach($courses as $course){ ?>
                        <option value="<?php echo $course->ID; ?>"><?php echo $course->post_title; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <img class="ldrh_loader" width="30" height="30" src="<?php echo LDRH_PLUGIN_URL; ?>assets/imgs/loader.gif" style="display: none;">
                <div class="ldrh_history_container"></div>
                <?php } ?>
            </div>
            <?php
            return ob_get_clean();
        }

    }

    $ldrh_shortcodes = new LDRH_Shortcodes();
}

