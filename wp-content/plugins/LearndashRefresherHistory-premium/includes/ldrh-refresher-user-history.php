<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('LDRH_Refresher_User_History')) {

    class LDRH_Refresher_User_History {

        public function __construct() {
            add_action('admin_menu', array($this, "ldrh_add_refresher_user_history_page"), 125);
        }

        function ldrh_add_refresher_user_history_page() {
            add_submenu_page(null, __('Refresher User History', 'ldrh'), __('Go To User History', 'ldrh'), 'ldrh_manage_reports', 'ldrh_refresher_user_history', array($this, 'ldrh_refresher_user_history_callback'));
        }

        function ldrh_refresher_user_history_callback() {

            if (isset($_GET['userId']) && ($userId = $_GET['userId']) && ($u = get_userdata($userId)) && isset($_GET['courseId']) && ($courseId = $_GET['courseId']) && ($courseData = get_post($courseId))) {
                ?>
                <h3><?php _e('History for User:', 'ldrh'); ?> <?php echo ldrh_get_user_name($u);?></h3>

                <h4><?php _e('Course:', 'ldrh'); ?> <?php echo $courseData->post_title; ?></h4>

                <table class="widefat" id="ldrh_refresher_user_history_report">
                    <thead>
                        <tr>
                            <th><?php _e('Date Of Completion', 'ldrh') ?></th>
                            <th><?php _e('Certificate Link', 'ldrh') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $course_completed = get_user_meta($u->ID, 'ldrh_completion_history_' . $courseData->ID, true);
                        
                        foreach ($course_completed as $index => $timestamp) {
                            $time = new DateTime('@' . $timestamp);
                            ?>
                            <tr>
                                <td>
                                    <span><?php echo $time->format('d-m-Y'); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $cert_link = ldrh_get_course_certificate_link($courseId, $u->ID);
                                    if($cert_link){
                                    ?>
                                    <a href="<?php echo $cert_link; ?>&cert_index=<?php echo $index; ?>" class="button button-primary" target="_blank"><?php _e('Open Certificate', 'ldrh') ?></a>
                                    <?php }else{ ?>
                                    <?php _e('N/A', 'ldrh') ?>
                                    <?php } ?>
                                </td>

                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
            }
        }

    }

    $ldrh_refresher_user_history = new LDRH_Refresher_User_History();
}