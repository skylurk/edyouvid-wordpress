<?php

/**
 * Abort if this file is accessed directly.
 */
if (!defined('ABSPATH')) {
    exit;
}

class Ld_Retake_Meta_Boxes
{

    /**
     * Constructor.
     */
    public function __construct(){

        $enabled_features = get_option('lrtq_enable_disable_features');
        $this->negative_marking = isset($enabled_features['enable_allow_negative_marking']) ? $enabled_features['enable_allow_negative_marking'] : '';

        if ($this->negative_marking === 'on') {
            add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
            add_action('save_post', array($this, 'save_meta_box'));
        }

        add_action('show_user_profile', array($this, 'extra_user_profile_fields'));
        add_action('edit_user_profile', array($this, 'extra_user_profile_fields'));
        add_action('personal_options_update', array($this, 'save_extra_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_extra_user_profile_fields'));
    }

    /**
     * Save meta box content.
     *
     * @param int $user_id
     */
    public function save_extra_user_profile_fields($user_id){
        global $wpdb;

        if (!current_user_can('manage_options', $user_id)) {
            return false;
        }

        $ld_retake_quiz_del_data = isset($_POST['ld_retake_quiz_del_data']) ? sanitize_text_field($_POST['ld_retake_quiz_del_data']) : '';

        if (isset($ld_retake_quiz_del_data) && $ld_retake_quiz_del_data === 'yes') {
            delete_user_meta($user_id, '_ld_quiz_retake_wrong_q');
            delete_user_meta($user_id, '_ld_quiz_retake_correct_q');
            $wpdb->get_results("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_ld_quiz_retake_user_attempts_count%' AND user_id = {$user_id}");
        }
    }

    /**
     * Display user extra information fields.
     *
     * @param object $user
     */
    public function extra_user_profile_fields($user){

        $user_id = $user->ID;

        if (!current_user_can('manage_options', $user_id)) {
            return false;
        }

        ?>
        <br>
        <h3><?php _e('Permanently Delete Retake Quiz Data', 'ld_retake_quiz'); ?></h3>
        <table>
            <tr>
                <th width="2%">
                    <label for="ld_retake_quiz_del_data">
                        <input type="checkbox" name="ld_retake_quiz_del_data" id="ld_retake_quiz_del_data" value="yes">
                    </label>
                </th>
                <td width="98%">
                    <?php _e('Check and click update profile to permanently delete user LearnDash Retake Quiz data', 'ld_retake_quiz'); ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta box content.
     *
     * @param int $post_id Post ID
     */
    public function save_meta_box($post_id)
    {
        if ( isset( $this->negative_marking ) && $this->negative_marking === 'on' ) {
            $post_tvp = get_post_type($post_id);

            if (trim($post_tvp) === 'sfwd-question') {
                $ld_retake_negative_points = isset( $_POST['ld_retake_negative_points'] ) ? $_POST['ld_retake_negative_points'] : 0;
                update_post_meta($post_id, '_ld_retake_negative_points', $ld_retake_negative_points);
            }
        }
    }

    /**
     * Register Meta Box
     */
    public function register_meta_boxes(){
        if ($this->negative_marking === 'on') {
            add_meta_box('ld-retake-question-meta-box', esc_html__('Negative Marking', 'ld_retake_quiz'), array($this, 'define_negative_marking'), 'sfwd-question', 'side', 'high');
        }
    }

    /**
     * Show form on product post.
     *
     * @param $meta_id
     */
    public function define_negative_marking($meta_id){
        if ($this->negative_marking === 'on') {
            $post_id = isset($_GET['post']) ? $_GET['post'] : 0;
            $ld_retake_negative_points = get_post_meta($post_id, '_ld_retake_negative_points', true);

            if (empty($ld_retake_negative_points)) {
                $ld_retake_negative_points = 0;
            }

            $content  = '';
            $content .= '<table width="100%">';
            $content .= '<tr><td>Points</td></tr>';
            $content .= '<tr><td><input type="number" id="ld_retake_negative_points" name="ld_retake_negative_points" min="0" value="' . $ld_retake_negative_points . '"></td></tr>';
            $content .= '<tr><td>' . esc_html__('These points will be deducted only if the user answers the question incorrectly.', 'ld_retake_quiz') . '</td></tr>';
            $content .= '</table>';

            echo $content;
        }
    }
}

new Ld_Retake_Meta_Boxes();
