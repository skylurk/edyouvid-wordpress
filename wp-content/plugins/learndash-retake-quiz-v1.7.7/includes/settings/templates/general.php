<?php
/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get options from the database for LD Retake Quiz features.
$enable_disable_options = get_option( 'lrtq_enable_disable_features' );

// Retrieve the delay duration for retaking the quiz.
$enable_ld_retakequiz_delay = isset( $enable_disable_options['ld_retakequiz_delay'] ) ? $enable_disable_options['ld_retakequiz_delay'] : 0;

// Retrieve the type of delay duration for retaking the quiz (default: Minutes).
$enable_ld_retakequiz_delay_type = isset( $enable_disable_options['ld_retakequiz_delay_type'] ) ? $enable_disable_options['ld_retakequiz_delay_type'] : "Minutes";

// Retrieve the retake limit for the quiz (if any).
$enable_ld_retake_limit = isset( $enable_disable_options['ld_retakequiz_retake_limit'] ) ? $enable_disable_options['ld_retakequiz_retake_limit'] : '';

// Check if debug logging for LD Retake Quiz is enabled.
$ld_retakequiz_debug_log = isset( $enable_disable_options['ld_retakequiz_debug_log'] ) ? $enable_disable_options['ld_retakequiz_debug_log'] : '';


?>
<div id="lrtq_features_options">
    <form method="post" action="">
        <h2><?php _e( 'General Settings', 'ld_retake_quiz' ); ?></h2>

        <table class="setting-table-wrapper" cellpadding="5" cellspacing="5">
            <tbody>

                <!-- ALLOW RETAKE QUIZZES -->
                <tr>
                    <td valign="top">
                        <span><?php _e( 'Allow Retake Quizzes', 'ld_retake_quiz' ); ?></span>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_allow_retake_quiz">
                            <input type="checkbox" <?php echo $enable_disable_options['enable_allow_retake_quiz'] == 'on' ? 'checked="checked"' : ''; ?>id="ld_retakequiz_allow_retake_quiz" class="ld-retakequiz-allow-retake-quiz" name="ld_retakequiz_allow_retake_quiz"> &nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?>
                        </label>
                    </td>
                </tr>
                <!-- ALLOW QUIZ DELAY -->
                <tr>
                    <td valign="top">
                        <label class="qstn-icn"><?php _e( 'Allow Quiz Delay?', 'ld_retake_quiz' ); ?><i class="fas fa-question"></i></label>
                        
                        <p class="description" style="font-weight: normal;">
                            <?php _e( 'If this option is selected, the individual quiz delay options will start working.', 'ld_retake_quiz' ); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retake_allow_quiz_delays">
                           <input type="checkbox" id="ld_retake_allow_quiz_delays" <?php echo $enable_disable_options['enable_allow_delay_quiz'] == 'on' ? 'checked="checked"' : ''; ?>name="ld_retake_allow_quiz_delays" />&nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?>
                        </label>
                        
                    </td>
                </tr>
    
                <!-- RETAKE DELAY LIMIT -->
                <tr class="ld_retakequiz_delay_box" style="display: none">
                    <td valign="top">
                        <label class="qstn-icn"><?php _e( 'Quiz Retake Delay', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e('Quiz retake delay will stop the user to retake a particular quiz repeatedly for the specified delay.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="maintenance_mode_level_off">
                            <input type="number" min="0" step="1" id="ld_retakequiz_delay" name="ld_retakequiz_delay" value="<?php echo $enable_ld_retakequiz_delay; ?>" >
                            <select id="ld_retakequiz_delay_type" name="ld_retakequiz_delay_type">
                                <option value="Minutes" <?php echo $enable_ld_retakequiz_delay_type == 'Minutes' ? 'selected="selected"' : '';?> ><?php _e('Minute(s)', 'ld_retake_quiz'); ?></option>
                                <option value="Hours" <?php echo $enable_ld_retakequiz_delay_type == 'Hours' ? 'selected="selected"' : ''; ?> ><?php _e('Hour(s)', 'ld_retake_quiz'); ?></option>
                                <option value="Days" <?php echo $enable_ld_retakequiz_delay_type == 'Days' ? 'selected="selected"' : ''; ?> ><?php _e('Day(s)', 'ld_retake_quiz'); ?></option>
                                <option value="Months" <?php echo $enable_ld_retakequiz_delay_type == 'Months' ? 'selected="selected"' : ''; ?> ><?php _e('Month(s)', 'ld_retake_quiz'); ?></option>
                                <option value="Years" <?php echo $enable_ld_retakequiz_delay_type == 'Years' ? 'selected="selected"' : ''; ?> ><?php _e('Year(s)', 'ld_retake_quiz'); ?></option>
                            </select>
                        </label>
                        
                    </td>
                </tr>
    
                <!-- ALLOW RETAKE LIMIT -->
                <tr>
                    <td valign="top">
                        <label class="qstn-icn"><?php _e( 'Allow Retake Limit?', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e( 'If this option is selected, the limit option on quiz retake will start working.', 'ld_retake_quiz' ); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retake_allow_retake_limit">
                           <input type="checkbox" id="ld_retake_allow_retake_limit" <?php echo $enable_disable_options['enable_allow_retake_limit'] == 'on' ? 'checked="checked"' : ''; ?>name="ld_retake_allow_retake_limit" />&nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?>
                        </label>
                        
                    </td>
                </tr>

                <!-- QUIZ RETAKE LIMIT -->
                <tr class="ld_retake_allow_retake_limit_box">
                    <td valign="top">
                        <label class="qstn-icn"><?php _e( 'Quiz Retake Limit (Number of retakes allowed)', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e('Quiz retake limit will restrict the user from retaking a particular quiz when specified limit reaches.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_retake_limit">
                            <input type="number" min="0" step="1" id="ld_retakequiz_retake_limit" name="ld_retakequiz_retake_limit" value="<?php echo $enable_ld_retake_limit; ?>" >
                        </label>
                        
                    </td>
                </tr>
        
                <!-- DELETE PREVIOUS QUIZ ATTEMPTS -->
                <tr>
                    <td valign="top">
                       <label class="qstn-icn"> <?php _e( 'Delete Previous Quiz Attempt ?', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e( 'If this option is selected, than save only last quiz attempt and remove all previous quiz attempts', 'ld_retake_quiz' ); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retake_delete_previous_quiz_attempt">
                           <input type="checkbox" id="ld_retake_delete_previous_quiz_attempt" <?php echo $enable_disable_options['delete_previous_quiz_attempt'] == 'on' ? 'checked="checked"' : ''; ?>name="ld_retake_delete_previous_quiz_attempt" />&nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?>
                        </label>
                        
                    </td>
                </tr>

                <!-- ALLOW NEGATIVE MARKING -->
                <tr>
                    <td valign="top">
                       <label class="qstn-icn"> <?php _e( 'Allow Negative Marking?', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e( 'If this option is selected, a negative points field will be displayed against each question. User will get the negative points if they attempt a question incorrectly.', 'ld_retake_quiz' ); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retake_negative_marking">
                            <input type="checkbox" id="ld_retake_negative_marking" <?php echo $enable_disable_options['enable_allow_negative_marking'] == 'on' ? 'checked="checked"' : ''; ?>name="ld_retake_negative_marking" />&nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?>
                        </label>
                        
                    </td>
                </tr>
               
                <!-- ALLOW RETAKE LOGS -->
                <tr>
                    <td valign="top">
                        <label class="qstn-icn"><?php _e( 'Allow Retake Logs?', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e( 'If this option is selected, the logs feature on quiz retake will start working.', 'ld_retake_quiz' ); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retake_allow_retake_logs">
                           <input type="checkbox" id="ld_retake_allow_retake_logs" <?php echo $enable_disable_options['enable_allow_retake_logs'] == 'on' ? 'checked="checked"' : ''; ?>name="ld_retake_allow_retake_logs" />&nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?>
                        </label>
                        
                    </td>
                </tr>

                <!-- ENABLE DEBUG LOGS -->
                <tr>
                    <td valign="top">
                        <label class="qstn-icn"><?php _e( 'Enable Debug Logs?', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e( 'If this option is enabled, the debug logs will be available for review', 'ld_retake_quiz' ); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_debug_log">
                           <input type="checkbox" id="ld_retakequiz_debug_log" <?php echo $ld_retakequiz_debug_log == 'on' ? 'checked="checked"' : ''; ?> 
                           name="ld_retakequiz_debug_log" &nbsp;<?php _e( 'Yes', 'ld_retake_quiz' ); ?> />
                        </label>
                        
                    </td>
                </tr>

            </tbody>
        </table>

         <!-- Submit Button -->
        <div class="submit">
            <input type="submit" name="save_lrtq_feature_settings" class="button-primary lrtq-addon-btn" value="<?php _e( 'Update Settings', 'ld_retake_quiz' ); ?>">
        </div>

        <!-- Nonce Field for Security -->
        <?php wp_nonce_field( 'lrtq_enable_disable_settings', 'lrtq_enable_disable_settings_field' ); ?>
    </form>
</div>