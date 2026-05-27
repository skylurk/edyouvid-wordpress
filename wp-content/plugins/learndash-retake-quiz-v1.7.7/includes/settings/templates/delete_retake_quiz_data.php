<?php
/**
 * Settings tab Quiz Completion
 */

/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$selected_user_ids = ( isset($_POST['learndash_course_users']) && !empty($_POST['learndash_course_users']) ) ? $_POST['learndash_course_users'] : array();
$selected_quiz_ids = ( isset($_POST['learndash_retake_quiz_quizzes']) && !empty($_POST['learndash_quizzes']) ) ? $_POST['learndash_retake_quiz_quizzes'] : array();
?>
<div>
    
    <form id="delete_retake_quiz_data_form" method="post" action="">
        <h2><?php _e( 'Delete Retake Quiz Data', 'ld_retake_quiz' ); ?></h2>
        <table class="form-table">

            <tbody>
            <tr>
                <th scope="row"><label for="blogname" class="qstn-icn"><?php _e('Delete Users Retake Quiz Data', 'ld_retake_quiz')?><i class="fas fa-question"></i></label>
                    <p class="description"><?php _e('Delete Retake Quiz data for selected users','ld_retake_quiz'); ?></p>
                </th>
                <td><div class="ld-retke-qz-delete"><label><input type="radio" name="delete_data_by_user" value="all" <?php checked(1,1); ?>><?php _e('Delete all users data','ld_retake_quiz'); ?></label>
                    <label><input type="radio" name="delete_data_by_user" value="specific"><?php _e('Delete specific users data','ld_retake_quiz'); ?></label></div>
                </td>
            </tr>
            <tr id="delete_data_by_user_select_row" style="display: none;">
                <th scope="row"><label for="blogname" class="qstn-icn"><?php _e('Select Users to delete Retake Quiz data', 'ld_retake_quiz')?><i class="fas fa-question"></i></label></th>
                <td >
                    <?php
                    $users_binary_args = array(
                        'html_title'            => '',
                        'search_posts_per_page' => 100,
                        'search_label_right' => sprintf('Search Selected %s Users', LearnDash_Custom_Label::get_label('Course')),
                        'selected_ids' => $selected_user_ids
                    );
                    $ld_binary_selector_course_users = new Learndash_Binary_Selector_Course_Users( $users_binary_args );
                    $ld_binary_selector_course_users->show();
                    ?>
                    
                </td>
            </tr>
            <tr id="delete_data_by_quiz_row">
                <th scope="row"><label for="blogname" class="qstn-icn"><?php echo sprintf(__('Delete %s Retake Quiz Data', 'ld_retake_quiz'), strtolower(LearnDash_Custom_Label::get_label('quizzes'))); ?><i class="fas fa-question"></i></label></th>
                <td><div class="ld-retke-qz-delete"><label><input type="radio" name="delete_data_by_quiz" value="all" <?php checked(1,1); ?> ><?php echo sprintf(__('Delete all %s data','ld_retake_quiz'), strtolower(LearnDash_Custom_Label::get_label('quizzes'))); ?></label>
                    <label><input type="radio" name="delete_data_by_quiz" value="specific"><?php echo sprintf(__('Delete specific %s data','ld_retake_quiz'), strtolower(LearnDash_Custom_Label::get_label('quizzes'))); ?></label></div>
                </td>
            </tr>
            <tr id="delete_data_by_quiz_select_row" style="display: none;">
                <th scope="row"><label for="blogname" class="qstn-icn"><?php echo sprintf(__('Select %s to delete Retake Quiz Data', 'ld_retake_quiz'), strtolower(LearnDash_Custom_Label::get_label('quizzes'))); ?><i class="fas fa-question"></i></label></th>
                <td >
                    <?php
                    $users_binary_args = array(
                        'html_title'            => '',
                        'search_posts_per_page' => 100,
                        'selected_ids' => $selected_quiz_ids
                    );
                    $ld_binary_selector_course_users = new LearnDash_Retake_Quiz_Binary_Selector_Quizzes( $users_binary_args );
                    $ld_binary_selector_course_users->show();
                    ?>
                    <p class="description"><?php echo sprintf(__('Delete Retake Quiz data for selected %s', 'ld_retake_quiz'), strtolower(LearnDash_Custom_Label::get_label('quizzes'))); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="submit">
            <input type="submit" name="save_delete_retake_quiz_data" class="button-primary lrtq-addon-btn" value="<?php _e('Delete Retake Quiz Data', 'ld_retake_quiz' ); ?>" data-warning="<?php _e('Are you sure you want to proceed with deleting data?','ld_retake_quiz'); ?>">
        </div>
        <?php wp_nonce_field( 'delete_retake_quiz_data', 'delete_retake_quiz_data_field' ); ?>
    </form>
</div>