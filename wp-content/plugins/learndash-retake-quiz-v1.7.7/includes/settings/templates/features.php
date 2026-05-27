<?php
/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$enable_disable_options     = get_option( 'lrtq_enable_disable_features' );
$general_settings           = get_option( 'lrtq_general_settings' );
$ld_settings                = get_option( 'learndash_settings_quizzes_taxonomies', array() );
$ld_quiz_category           = ( isset( $ld_settings['ld_quiz_category'] ) && $ld_settings['ld_quiz_category'] == 'yes' );
$ld_quiz_tag                = ( isset( $ld_settings['ld_quiz_tag'] ) && $ld_settings['ld_quiz_tag'] == 'yes' );

$quiz_parms = array( 
    'numberposts'       => -1,
    'post_type'         => 'sfwd-quiz',
    'suppress_filters'  => true
);
$quizes = get_posts( $quiz_parms );

$selected_group_ids  = isset( $general_settings['ld_retakequiz_group'] ) ? $general_settings['ld_retakequiz_group'] : array();
$selected_course_ids = isset( $general_settings['ld_retakequiz_course'] ) ? $general_settings['ld_retakequiz_course'] : array();
$selected_lesson_ids = isset( $general_settings['ld_retakequiz_lesson'] ) ? $general_settings['ld_retakequiz_lesson'] : array();
$selected_topic_ids  = isset( $general_settings['ld_retakequiz_topic'] ) ? $general_settings['ld_retakequiz_topic'] : array();
$selected_quizes_ids = isset( $general_settings['ld_retakequiz_quizes'] ) ? $general_settings['ld_retakequiz_quizes'] : array();


$selected_exclude_quizes_ids = isset( $general_settings['ld_retakequiz_exclude_quizzes'] ) ? $general_settings['ld_retakequiz_exclude_quizzes'] : array();
$selected_exclude_users_ids  = isset( $general_settings['ld_retakequiz_exclude_users'] ) ? $general_settings['ld_retakequiz_exclude_users'] : array();
$selected_exclude_groups_ids = isset( $general_settings['ld_retakequiz_exclude_groups'] ) ? $general_settings['ld_retakequiz_exclude_groups'] : array();
$ld_retakequiz_categories    = isset( $general_settings['ld_retakequiz_categories'] ) ? $general_settings['ld_retakequiz_categories'] : array();
$ld_retakequiz_tags          = isset( $general_settings['ld_retakequiz_tags'] ) ? $general_settings['ld_retakequiz_tags'] : array();

?>
<div id="lrtq_general_options">
    <form method="post" action="">
        <h2><?php _e( 'Retake Settings', 'ld_retake_quiz' ); ?></h2>
        <table class="setting-table-wrapper" cellpadding="5" cellspacing="5">
            <tbody>            
                <tr>
                    <td valign="top">
                        <span id="ld_retakequiz_course_label" class="qstn-icn"><?php _e( 'Specific Courses', 'ld_retake_quiz' ); ?><i class="fas fa-question"></i></span>
                        <p id="ld_retakequiz_course_description" class="description" style="font-weight: normal;">
                            <?php _e('Choose course(s) to narrow down the allowed quizzes for retake quiz option.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_course" id="ld_retakequiz_course_field">
                            <select id="ld_retakequiz_course" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_course[]">
                                <?php foreach( $selected_course_ids as $course_id) { ?>
                                    <option value="<?php echo $course_id;?>" selected="selected"><?php echo get_the_title( $course_id ) ?></option>
                                <?php } ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>
                <tr >
                    <td valign="top">
                        <span id="ld_retakequiz_lesson_label" class="qstn-icn"><?php _e( 'Specific Lessons', 'ld_retake_quiz' ); ?><i class="fas fa-question"></i></span>
                        <p id="ld_retakequiz_lesson_description" class="description" style="font-weight: normal;">
                            <?php _e('Choose lesson(s) to narrow down the allowed quizzes for retake quiz option.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_lesson" id="ld_retakequiz_lesson_field">
                            <select id="ld_retakequiz_lesson" multiple  class="ld-retakequiz-select2-ddl" name="ld_retakequiz_lesson[]">
                                <?php foreach( $selected_lesson_ids as $lesson_id) { ?>
                                    <option value="<?php echo $lesson_id;?>" selected="selected"><?php echo get_the_title($lesson_id)?></option>
                                <?php } ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        <span id="ld_retakequiz_topic_label" class="qstn-icn"><?php _e( 'Specific Topics', 'ld_retake_quiz' ); ?><i class="fas fa-question"></i></span>
                        <p id="ld_retakequiz_topic_description" class="description">
                            <?php _e('Choose topic(s) to narrow down the allowed quizzes for retake quiz option.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_topic" id="ld_retakequiz_topic_field">
                            <select id="ld_retakequiz_topic" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_topic[]">
                                <?php foreach( $selected_topic_ids as $topic_id) { ?>
                                    <option value="<?php echo $topic_id;?>" selected="selected"><?php echo get_the_title($topic_id)?></option>
                                <?php } ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>
                 <tr>
                    <td valign="top">
                        <label class="qstn-icn">
                        <?php _e( 'Specific Quizzes', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e('Apply retake quiz feature on selected quizzes only, leave it blank to apply on all quizzes.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="maintenance_mode_level_off">
                            <select id="ld_retakequiz_quizes" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_quizes[]">
                                <?php foreach( $selected_quizes_ids as $quiz_id) { ?>
                                    <option value="<?php echo $quiz_id;?>" selected="selected"><?php echo get_the_title($quiz_id)?></option>
                                <?php } ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>
                 <tr>
                    <td valign="top">
                        <label class="qstn-icn">
                        <?php _e( 'Exclude Quizzes', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description" style="font-weight: normal;">
                            <?php _e('Retake quiz option will be disabled for the selected quizzes.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_exclude_quizzes">
                            <select id="ld_retakequiz_exclude_quizzes" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_exclude_quizzes[]">
                                <?php foreach( $selected_exclude_quizes_ids as $quiz_id) { ?>
                                    <option value="<?php echo $quiz_id;?>" selected="selected"><?php echo get_the_title($quiz_id)?></option>
                                <?php } ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        <label class="qstn-icn">
                        <?php _e( 'Exclude Users', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description">
                            <?php _e('Retake quiz option will be disabled for the selected users.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_exclude_users">
                            <select id="ld_retakequiz_exclude_users" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_exclude_users[]">
                                <?php foreach( $selected_exclude_users_ids as $user_id) { ?>
                                    <option value="<?php echo $user_id;?>" selected="selected"><?php echo get_userdata($user_id)->display_name?></option>
                                <?php } ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>
            <?php
            $allow_retake_quiz  = get_option( 'ld_retakequiz_allow_retake_quiz' );
            if( isset( $enable_disable_options['enable_allow_retake_quiz'] ) && $enable_disable_options['enable_allow_retake_quiz'] == 'on' ) { ?>  
                <tr class="enable_allow_retake_quiz">
                    <td valign="top">
                        <span><?php _e( 'Allow Retake Quizzes for specific Groups,Quiz Categories, Quiz Tags', 'ld_retake_quiz' ); ?></span>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_allow_courses">
                            <input type="checkbox" <?php echo $general_settings['ld_retakequiz_allow_courses'] == 'on' ? 'checked="checked"' : ''; ?> id="ld_retakequiz_allow_courses" class="ld-retakequiz-allow-specific-courses" name="ld_retakequiz_allow_courses"> &nbsp;<?php _e('Yes', 'ld_retake_quiz'); ?>
                        </label>
                    </td>
                </tr>
                <tr class="enable_allow_retake_quiz">
                    <td valign="top">
                        <span id="ld_retakequiz_group_label" class="qstn-icn"><?php _e( 'Specific Groups', 'ld_retake_quiz' ); ?><i class="fas fa-question"></i></span>
                        <p id="ld_retakequiz_group_description" class="description">
                            <?php _e('Choose group(s) to narrow down the allowed quizzes for retake quiz option.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_group" id="ld_retakequiz_group_field">
                            <select id="ld_retakequiz_group" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_group[]">
                                <?php foreach($selected_group_ids as $group_id) { ?>
                                    <option value="<?php echo $group_id;?>" selected="selected"><?php echo get_the_title($group_id)?></option>
                                <?php } ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>

                <?php if($ld_quiz_category): ?>
                <tr class="enable_allow_retake_quiz">
                    <td valign="top">
                        <label class="qstn-icn">
                        <?php _e( 'Specific Quiz Categories', 'ld_retake_quiz' ) ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description">
                            <?php _e('Choose quiz category to narrow down the allowed quizzes for retake quiz option.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="maintenance_mode_level_off">
                            <select id="ld_retakequiz_categories" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_categories[]">
                                <?php
                                $ld_settings = get_option( 'learndash_settings_quizzes_taxonomies');
                                if( isset( $ld_settings['wp_post_category'] ) && $ld_settings['wp_post_category'] == 'yes' ) {
                                    $post_cats  = get_terms( 'category', array(
                                            'hide_empty' => false
                                    ) );

                                    foreach( $post_cats as $cat) {
                                        ?>
                                            <option value="<?php echo $cat->term_id;?>" <?php echo in_array( $cat->term_id, $ld_retakequiz_categories ) ? 'selected="selected"' : ''; ?> ><?php echo $cat->name;?></option>
                                        <?php
                                    }
                                }

                                if( isset( $ld_settings['ld_quiz_category'] ) && $ld_settings['ld_quiz_category'] == 'yes' ) {
                                    if( taxonomy_exists( 'ld_quiz_category' ) ){
                                        $quiz_cats = get_terms( 'ld_quiz_category', array(
                                            'hide_empty' => false
                                        ) );

                                        foreach( $quiz_cats as $cat) {
                                            ?>
                                                <option value="<?php echo $cat->term_id;?>" <?php echo in_array( $cat->term_id, $ld_retakequiz_categories ) ? 'selected="selected"' : ''; ?> ><?php echo $cat->name;?></option>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </label>
                        
                    </td>
                </tr>
                <?php endif; ?>
                <?php if($ld_quiz_tag): ?>
                <tr class="enable_allow_retake_quiz">
                    <td valign="top">
                        <label class="qstn-icn">
                        <?php _e( 'Specific Quiz Tags', 'ld_retake_quiz' ) ?>
                        <i class="fas fa-question"></i></label>
                         <p class="description">
                            <?php _e('Choose quiz tags to narrow down the allowed quizzes for retake quiz option.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="maintenance_mode_level_off">
                            <select id="ld_retakequiz_tags" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_tags[]">
                                <?php
                                $ld_settings = get_option( 'learndash_settings_quizzes_taxonomies');
                                if( isset( $ld_settings['wp_post_tag'] ) && $ld_settings['wp_post_tag'] == 'yes' ) {
                                    $post_tags  = get_terms( 'post_tag', array(
                                            'hide_empty' => false
                                    ) );

                                    foreach( $post_tags as $tag) {
                                        ?>
                                            <option value="<?php echo $tag->term_id;?>" <?php echo in_array( $tag->term_id, $ld_retakequiz_tags ) ? 'selected="selected"' : ''; ?> ><?php echo $tag->name;?></option>
                                        <?php
                                    }
                                }

                                if( isset( $ld_settings['ld_quiz_tag'] ) && $ld_settings['ld_quiz_tag'] == 'yes' ) {
                                    if( taxonomy_exists( 'ld_quiz_tag' ) ){
                                        $quiz_tags = get_terms( 'ld_quiz_tag', array(
                                            'hide_empty' => false
                                        ) );

                                        foreach( $quiz_tags as $tag) {
                                            ?>
                                                <option value="<?php echo $tag->term_id;?>" <?php echo in_array( $tag->term_id, $ld_retakequiz_tags ) ? 'selected="selected"' : ''; ?> ><?php echo $tag->name;?></option>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </label>
                       
                    </td>
                </tr>
                <?php endif; ?>
               
            <?php }   
            if( isset( $enable_disable_options['enable_allow_retake_quiz'] ) && $enable_disable_options['enable_allow_retake_quiz'] == 'on' ) { ?>
               
                <tr class="enable_allow_retake_quiz">
                    <td valign="top">
                        <label class="qstn-icn"><?php _e( 'Exclude Groups', 'ld_retake_quiz' ); ?>
                        <i class="fas fa-question"></i></label>
                        <p class="description">
                            <?php _e('Retake quiz option will be disabled for the selected groups.', 'ld_retake_quiz'); ?>
                        </p>
                    </td>
                    <td valign="top">
                        <label for="ld_retakequiz_exclude_groups">
                            <select id="ld_retakequiz_exclude_groups" multiple class="ld-retakequiz-select2-ddl" name="ld_retakequiz_exclude_groups[]">
                                <?php foreach($selected_exclude_groups_ids as $group_id) { ?>
                                    <option value="<?php echo $group_id;?>" selected="selected"><?php echo get_the_title($group_id)?></option>
                                <?php } ?>
                            </select>

                        </label>
                        
                    </td>
                </tr>
                <tr>
                <td> <?php _e( 'Restricted Quizzes for retake by LearnDash Quiz Settings:', 'ld_retake_quiz' ); ?></td>
                <td>
                <?php
                if (isset($quizes) && !empty($quizes)) { 
                    $restriction_check = 0;
                    foreach ($quizes as $quiz) {
                        $quiz_post_settings = learndash_get_setting( $quiz->ID );
                        if ( isset( $quiz_post_settings['retry_restrictions'] ) && $quiz_post_settings['retry_restrictions'] == 'on' ) {
                            $restriction_check++;
                            $back_link = get_post_permalink($quiz->ID);
                            $edit_link = get_edit_post_link($quiz->ID);
                            $edit_link .= '&currentTab=sfwd-quiz-settings';
                            ?>
                                <span>(<b>ID:</b> <?php echo $quiz->ID; ?>) <b><?php echo sprintf('<a href="%s">%s</a>', $back_link, $quiz->post_title); ?></b> | <?php echo sprintf('<a href="%s">%s</a>', $edit_link, 'Edit'); ?></span><br />
                            <?php
                        }
                    }
                }
                ?>
                    <span><?php echo (isset($restriction_check) && $restriction_check > 0) ? '' : 'None'; ?></span>
                </td>
            </tr>
            <tr>
                <td> <?php _e( 'Restart Quiz Button is disabled by LearnDash Quiz Settings for Quizzes:', 'ld_retake_quiz'); ?></td>
                <td>
                <?php
                $disabled_check = 0;
                if (isset($quizes) && !empty($quizes)) {
                   foreach ($quizes as $quiz) {
                        $quiz_post_settings = learndash_get_setting( $quiz->ID );
                        if ( isset( $quiz_post_settings['btnRestartQuizHidden'] ) && $quiz_post_settings['btnRestartQuizHidden'] ) {
                            $disabled_check++;
                            $back_link = get_post_permalink($quiz->ID);
                            $edit_link = get_edit_post_link($quiz->ID);
                            $edit_link .= '&currentTab=sfwd-quiz-settings';
                            ?>
                                <span>(<b>ID:</b> <?php echo $quiz->ID; ?>) <b><?php echo sprintf('<a href="%s">%s</a>', $back_link, $quiz->post_title); ?></b> | <?php echo sprintf('<a href="%s">%s</a>', $edit_link, 'Edit'); ?></span><br />
                            <?php
                        }
                    }
                }
                    
                ?>
                    <span><?php echo ( isset( $disabled_check ) && $disabled_check > 0 ) ? '' : 'None'; ?></span>
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>

        <?php
        if( ( isset( $enable_disable_options['enable_allow_retake_quiz'] ) && $enable_disable_options['enable_allow_retake_quiz'] == 'on' ) || ( isset( $enable_disable_options['enable_allow_delay_quiz'] ) && $enable_disable_options['enable_allow_delay_quiz'] == 'on' ) ) { ?>
        <div class="submit">
            <input type="submit" name="save_lrtq_general_settings" class="button-primary lrtq-addon-btn" value="<?php _e('Update Settings', 'ld_retake_quiz'); ?>">
        </div>
        <?php } else { ?>
            <div class="no-option-notification">
                <?php _e('Settings will be shown only if <b>Allow Retake Quizzes</b> or <b>Allow Quiz Delay</b> features are enabled.', 'ld_retake_quiz'); ?>
            </div>
        <?php }

        wp_nonce_field( 'lrtq_general_settings', 'lrtq_general_settings_field' ); ?>
    </form>
</div>