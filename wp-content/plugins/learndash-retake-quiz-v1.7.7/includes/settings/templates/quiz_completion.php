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

$db_form_fields  = get_option('ld_rtq_quiz_completion_setting', array('quiz_completion_message' => NULL));

if(empty($db_form_fields['quiz_completion_message'])) {
	$db_form_fields['quiz_completion_message'] = __('You have already completed this quiz. [ld_rtq_back_link]Back[/ld_rtq_back_link]', 'ld_retake_quiz');
}

if( empty( $db_form_fields['allowed_retake_limit_message'] ) ) {
    $db_form_fields['allowed_retake_limit_message'] = __( 'Retake Quiz Attempts reaches limit.', 'ld_retake_quiz');
}

if(!isset($db_form_fields['enable_allow_completion_message'])) {
	$db_form_fields['enable_allow_completion_message'] = '';
}
?>
<div>
    <form method="post" action="">

        <table class="form-table">

            <tbody>
            <tr>
                <th>
                    <label class="qstn-icn"><?php _e( 'Disable Completion Message', 'ld_retake_quiz' ); ?><i class="fas fa-question"></i></label>
                    <p class="description">
                        <?php _e( 'If this option is selected, the "completion message" will not be displayed.', 'ld_retake_quiz' ); ?>
                    </p>
                </th>
                <td>
                    <label for="ld_retakequiz_completion_message">
                        <input type="checkbox" <?php echo $db_form_fields['enable_allow_completion_message'] == 'on' ? 'checked="checked"' : ''; ?>id="ld_retakequiz_completion_message" class="ld-retakequiz-allow-completion-message" name="ld_retakequiz_completion_message"> &nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?>
                    </label>
                    
                </td>
            </tr>
            <tr>
                <th scope="row"><label class="qstn-icn"><?php _e('Quiz Completion Message', 'ld_retake_quiz'); ?><i class="fas fa-question"></i></label>
                <p class="description"><?php echo sprintf( __('This message will be displayed only when <strong>Allow Retake Quizzes</strong> feature is enabled and user has completed the %s.', 'ld_retake_quiz'), learndash_get_custom_label_lower('quiz') );?>
                        <br><strong>[ld_rtq_back_link]Back[/ld_rtq_back_link]:</strong> <?php echo sprintf(__('Render link to previous %s or %s', 'ld_retake_quiz'), learndash_get_custom_label_lower('course'), learndash_get_custom_label_lower('lesson')); ?>
                    </p></th>
                <td >

					<?php wp_editor( html_entity_decode($db_form_fields['quiz_completion_message']), "quiz_completion_message", $settings = array('textarea_rows' => 2) ); ?>
                    
                </td>

            </tr>

            <tr>
                <th scope="row"><label class="qstn-icn"><?php _e( 'Quiz Retake Limit Reached Message', 'ld_retake_quiz' ); ?><i class="fas fa-question"></i></label>
                <p class="description"><?php echo sprintf( __( 'This message will be displayed only when <strong>Allow Retake Quizzes</strong> feature is enabled and Allowed retake limit is reached', 'ld_retake_quiz' ), learndash_get_custom_label_lower('quiz') ); ?>
                        <!-- <br><strong>[ld_rtq_back_link]Back[/ld_rtq_back_link]:</strong> <?php //echo sprintf(__('Render link to previous %s or %s', 'ld_retake_quiz'), learndash_get_custom_label_lower('course'), learndash_get_custom_label_lower('lesson')); ?> -->
                    </p></th>
                <td >

                    <?php wp_editor( html_entity_decode( $db_form_fields['allowed_retake_limit_message']), "allowed_retake_limit_message", $settings = array('textarea_rows' => 2) ); ?>
                    
                </td>

            </tr>
            </tbody>
        </table>


        <div class="submit">
            <input type="submit" name="save_lrtq_quiz_completion_settings" class="button-primary lrtq-addon-btn" value="<?php _e('Update Settings', 'ld_retake_quiz' ); ?>">
        </div>
		<?php wp_nonce_field( 'quiz_completion_settings' ); ?>
    </form>
</div>