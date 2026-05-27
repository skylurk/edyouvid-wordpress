<?php

/**
 * Abort if this file is accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

class Learndash_Retake_Quiz_Handler {
    
    private $general_settings;
    private $enabled_features;

    /**
     * Constructor for the LD_Retake_Quiz class.
     */
    public function __construct() {
        // Retrieve general settings and enabled features from options
        $this->general_settings = get_option('lrtq_general_settings');
        $this->enabled_features = get_option('lrtq_enable_disable_features');

        // Check if retake quiz feature is enabled
        $is_retake_quiz_enabled = (isset($this->enabled_features['enable_allow_retake_quiz']) && $this->enabled_features['enable_allow_retake_quiz'] == 'on');
        $ld_delay_quiz_enabled = (isset($this->enabled_features['enable_allow_delay_quiz']) && $this->enabled_features['enable_allow_delay_quiz'] == 'on');
        $ld_retake_quiz_limit_enabled = (isset($this->enabled_features['enable_allow_retake_limit']) && $this->enabled_features['enable_allow_retake_limit'] == 'on');

        if ($is_retake_quiz_enabled) {
            // Add AJAX action for handling retake quiz completion
            add_action('wp_ajax_ld_rq_set_retake_result', array($this, 'ld_retake_quiz_complete'), 10);

            if (!is_admin()) {
                // Add filter to modify quiz queries (only for non-admin pages)
                add_filter('query', array($this, 'ld_retake_modify_quiz_query'), 10, 1);
            }

            // Add shortcode for displaying a back link in quizzes
            add_shortcode('ld_rtq_back_link', [$this, 'ld_retake_back_link_cb']);

            // Add filter to handle random quiz questions
            add_filter('learndash_fetch_quiz_questions', array($this, 'ld_retake_handle_random_quiz'), 10, 4);
        }

        if ($is_retake_quiz_enabled || $ld_delay_quiz_enabled) {
            // Add filter to handle quiz display in content
            add_filter('the_content', array($this, 'ld_retake_handle_quiz_display'), 1111, 1);
        }

        if ($is_retake_quiz_enabled && $ld_retake_quiz_limit_enabled) {
            // Add action to handle quiz retake limits
            add_action('learndash-quiz-before', array($this, 'ld_retake_handle_quiz_retake_limit'), 10, 3);
        }

        // You can uncomment and modify additional filters or actions as needed.
    }


    /**
     * Callback function for the 'ld_rtq_back_link' shortcode.
     *
     * @param array $atts    Shortcode attributes (if any).
     * @param string|null $content The content inside the shortcode (if any).
     * @return string The HTML link to the previous step.
     */
    public function ld_retake_back_link_cb($atts, $content = null) {
        global $post;

        // Get the quiz ID from the current post
        $quiz_id = $post->ID;

        // Get the permalink of the previous step
        $back_link = $this->get_step_permalink($quiz_id);

        // Generate and return the HTML link
        return sprintf('<a href="%s">%s</a>', $back_link, $content);
    }


    /**
     * Quiz ID
     *
     * @param $post_id
     * @return mixed
     */
    public function get_quiz_id ( $post_id ) {
        $quiz_meta = get_post_meta ( $post_id, "_sfwd-quiz", true );
        return $quiz_meta[ "sfwd-quiz_quiz_pro" ];
    }

    /**
     * Quiz ID
     *
     * @param $post_id
     * @return mixed
     */
    public function get_question_post_id ( $question_pro_id ) {
        global $wpdb;
        $results = $wpdb->get_results( 
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='question_pro_id' and meta_value=%d", $question_pro_id)
        );
        if( count( $results ) > 0 ) {
            return $results[0]->post_id;
        }

        return 0;
    }


   /**
     * Get the permalink of the previous step in a course or lesson containing a quiz.
     *
     * @param int $quiz_id The ID of the quiz.
     * @return string|null The permalink of the previous step, or null if not found.
     */
    public function get_step_permalink($quiz_id) {
        // Get the course ID associated with the quiz
        $course_id = learndash_get_course_id($quiz_id);

        // Get the lesson ID associated with the quiz
        $previous_id = learndash_get_lesson_id($quiz_id);

        // If there's no previous lesson, consider the course itself as the previous step
        if (!$previous_id) {
            $previous_id = $course_id;
        }

        // Return the permalink of the previous step
        return learndash_get_step_permalink($previous_id, $course_id);
    }


    /**
     * Overrides the quiz content display based on conditions.
     *
     * @param string $content The original quiz content.
     * @return string The modified quiz content.
     */
    public function ld_retake_handle_quiz_display($content) {
        global $post;

        // Check if we're inside a single post
        if (!empty($post)) {
            $shortcode = 'ld_quiz';
            $quiz_id = 0;
            $pattern = get_shortcode_regex();

            if ( $post->post_type == 'sfwd-quiz' ) {
                $quiz_id = $post->ID;
                $curr_user_id = get_current_user_id();

                if (intval($curr_user_id) > 0) {
                    // Get the permalink for going back to the previous step
                    $back_link = $this->get_step_permalink($quiz_id);

                    // Check if retake quiz is enabled and user is allowed to retake
                    $validate_quiz = new Validate_Quiz();
                    $is_allowed = $validate_quiz->check_validity($quiz_id);
                    $is_retake_quiz_enabled = (isset($this->enabled_features['enable_allow_retake_quiz']) && $this->enabled_features['enable_allow_retake_quiz'] == 'on');
                    $quiz_completion_setting = get_option('ld_rtq_quiz_completion_setting', array('quiz_completion_message' => NULL));
                    $enable_allow_completion_message_option = (isset($quiz_completion_setting['enable_allow_completion_message']) && $quiz_completion_setting['enable_allow_completion_message'] == 'on');

                    if ($is_retake_quiz_enabled && $is_allowed && $enable_allow_completion_message_option != 'on') {
                        
                        if ( $this->is_quiz_completed($curr_user_id, $quiz_id) ) {
                            // Display completion message or default message
                            $content = $quiz_completion_setting['quiz_completion_message'];
                            if (empty($content)) {
                                $content = __('You have already completed this quiz. [ld_rtq_back_link]Back[/ld_rtq_back_link]', 'ld_retake_quiz');
                            }
                            $content = do_shortcode(html_entity_decode($content));
                            return $content;
                        }
                    }

                    // Check if retake quiz limit is enabled
                    $ld_retake_quiz_limit_enabled = (isset($this->enabled_features['enable_allow_retake_limit']) && $this->enabled_features['enable_allow_retake_limit'] == 'on');
                    $ld_retakequiz_retake_limit = $this->enabled_features['ld_retakequiz_retake_limit'] ?? 0;

                    if( intval( $ld_retakequiz_retake_limit ) !== 0 ) {
                        if ($is_retake_quiz_enabled && $is_allowed && $ld_retake_quiz_limit_enabled) {
                            $user_retake_attempts = get_user_meta($curr_user_id, '_ld_quiz_retake_user_attempts', false);
                            $user_attempts_count = get_user_meta($curr_user_id, '_ld_quiz_retake_user_attempts_count_quiz_' . $quiz_id, true);
                            if (isset($user_attempts_count) && !empty($user_attempts_count)) {
                                $user_attempts_count = 0;
                                foreach ($user_retake_attempts as $user_retake_attempt) {
                                    if ($user_retake_attempt['quiz_id'] == $quiz_id) {
                                        $user_attempts_count++;
                                    }
                                }
                                if (intval($ld_retakequiz_retake_limit) == intval($user_attempts_count) || 
                                    intval($ld_retakequiz_retake_limit) < intval($user_attempts_count) )  {
                                    $quiz_retake_msg = get_option('ld_rtq_quiz_completion_setting');
                                    if (isset($quiz_retake_msg['allowed_retake_limit_message'])) {
                                        $allowed_retake_limit_message = $quiz_retake_msg['allowed_retake_limit_message'];
                                        $content = $allowed_retake_limit_message . sprintf(' <a href="%s">Back</a>', $back_link);
                                    } else {
                                        $content = sprintf(__('<span>Retake Quiz Attempts reaches limit.</span> <a href="%s">Back</a>', 'ld_retake_quiz'), $back_link);
                                    }
                                    return $content;
                                }
                            }
                        }
                    }


                    // Check if retake quiz delay is enabled
                    $ld_retake_quiz_delay_enabled = (isset($this->enabled_features['enable_allow_delay_quiz']) && $this->enabled_features['enable_allow_delay_quiz'] == 'on');
                    $ld_retakequiz_delay = isset($this->enabled_features['ld_retakequiz_delay']) ? $this->enabled_features['ld_retakequiz_delay'] : 0;
                    $ld_retakequiz_delay_type = isset($this->enabled_features['ld_retakequiz_delay_type']) ? $this->enabled_features['ld_retakequiz_delay_type'] : null;

                    // Check if retake quiz delay feature is enabled, user is allowed, delay duration is positive, and delay type is set
                    if ($ld_retake_quiz_delay_enabled && $is_allowed && intval($ld_retakequiz_delay) > 0 && !empty($ld_retakequiz_delay_type)) {
                        // Log a testing message to error log

                        // Create a string representing the delay duration and type
                        $str_time = strtolower($ld_retakequiz_delay . ' ' . $ld_retakequiz_delay_type);

                        // Initialize last quiz attempt time
                        $last_quiz_time = 0;

                        // Get user quizzes data
                        $quizzes = get_user_meta($curr_user_id, '_sfwd-quizzes', true);
                        // Check if user has attempted quizzes
                        if (is_array($quizzes) && count($quizzes) > 0) {
                            foreach ($quizzes as $quiz) {
                                // Find the last attempt time for the current quiz
                                if ( intval( $quiz['quiz'] ) == $quiz_id) {
                                    $last_quiz_time = $quiz['completed'];
                                }
                            }

                            // If last quiz attempt time is valid
                            if (intval($last_quiz_time) > 0) {
                                // Calculate the time when the user can retake the quiz
                                $last_quiz_attempt_time = strtotime("+ " . $str_time, $last_quiz_time);
                                $date_quiz_comp = new DateTime();
                                $date_quiz_comp->setTimestamp($last_quiz_attempt_time);

                                // Get current time
                                $curr_time = time();
                                $curr_date = new DateTime();
                                $curr_date->setTimestamp($curr_time);

                                // If the user has to wait, generate an error message
                                if ($last_quiz_attempt_time > $curr_time) {
                                    $sinceThen = $date_quiz_comp->diff($curr_date);
                                    $string_date = '';
                                    if ($sinceThen->y > 0) $string_date .= $sinceThen->y . ' years';
                                    if ($sinceThen->m > 0) $string_date .= " " . $sinceThen->m . ' months';
                                    if ($sinceThen->d > 0) $string_date .= " " . $sinceThen->d . ' days';
                                    if ($sinceThen->h > 0) $string_date .= " " . $sinceThen->h . ' hours';
                                    if ($sinceThen->i > 0) $string_date .= " " . $sinceThen->i . ' minutes';
                                    if ($sinceThen->s > 0) $string_date .= " " . $sinceThen->s . ' seconds';

                                    // Generate the content with the error message and a back link
                                    $content = __('<span class="retaking_quiz_timer">Please wait for ' . $string_date . ' before retaking this quiz.</span> <a href="' . $back_link . '">Back</a>', 'ld_retake_quiz');
                                    
                                    // Return the error message
                                    return $content;
                                }
                            }
                        }
                    }

                }
            }
        }

        // If none of the conditions are met, return the original content
        return $content;
    }


   /**
     * Handles quiz retake limit
     *
     * @param int $quiz_id
     * @param int $course_id
     * @param int $user_id
     */
    function ld_retake_handle_quiz_retake_limit($quiz_id, $course_id, $user_id) {
        // Create an instance of Validate_Quiz
        $validate_quiz = new Validate_Quiz();
        
        // Check if the quiz is valid
        $is_allowed = $validate_quiz->check_validity($quiz_id);

        if ($is_allowed) {
            // Retrieve retake attempts left
            $retake_attempts_left = $this->enabled_features['ld_retakequiz_retake_limit'] ?? 0;
            
            // Get the user's quiz retake attempts count
            $user_attempts_count = get_user_meta($user_id, '_ld_quiz_retake_user_attempts_count_quiz_' . $quiz_id, true);

            if (isset($user_attempts_count) && !empty($user_attempts_count)) {
                $ld_retakequiz_retake_limit = $this->enabled_features['ld_retakequiz_retake_limit'] ?? 0;
                
                // Calculate remaining retake attempts
                if (intval($ld_retakequiz_retake_limit) >= intval($user_attempts_count)) {
                    $retake_attempts_left = intval($ld_retakequiz_retake_limit) - intval($user_attempts_count);
                }
            }

            $quiz_post_settings = learndash_get_setting($quiz_id);

            if (isset($quiz_post_settings['retry_restrictions']) && $quiz_post_settings['retry_restrictions'] == 'on') {
                // Display a message indicating quiz restrictions
                // echo sprintf('<div style="text-align: right; color: red;">%s</div>', __('(This quiz is restricted by LMS)', 'ld_retake_quiz'));
            }

            $ld_retakequiz_retake_limit = $this->enabled_features['ld_retakequiz_retake_limit'] ?? 0;

            if ($ld_retakequiz_retake_limit == 0) {
                // Display unlimited retries message
                echo '<div style="text-align: right;">' . __('Retries left: ', 'ld_retake_quiz') . '<span class="unlmt-bg">' . __('Unlimited', 'ld_retake_quiz') . '</span></div>';
            } else {
                // Display the remaining retake attempts
                echo '<div style="text-align: right;">' . __('Retries left: ', 'ld_retake_quiz') . '<span class="unlmt-bg">' . $retake_attempts_left . '</span></div>';
            }

            // Log data
            LD_Retake_Quiz_WP_Logging::add(
                __('Retake Limit', 'ld_retake_quiz'),
                __($retake_attempts_left . ' Quiz Attempt left for the user: ' . $user_id, 'ld_retake_quiz'),
                $quiz_id, // ID of post where settings are loaded
                'event'
            );
        }
    }


    /**
     * Overrides the quiz random questions
     *
     * @param array   $pro_questions An array of pro quiz question IDs.
     * @param int     $quiz_id       ID of the quiz.
     * @param boolean $random        Whether to fetch questions in random order.
     * @param int     $max           The maximum number of questions to be fetched.
     *
     * @return null|$pro_questions
     */
    public function ld_retake_handle_random_quiz( $pro_questions, $quiz_id, $random, $max ) {
        // $random = false;
        // var_dump($max);
        if( $random ) {
            if ( empty( $user_id ) ) {
                $current_user = wp_get_current_user();
                if ( empty( $current_user->ID ) ) {
                    return null;
                }
                $user_id = $current_user->ID;
            }
            
            $wrong_answers = get_user_meta( $user_id, '_ld_quiz_retake_wrong_q', true );

            if( isset( $wrong_answers[$quiz_id]["question_ids"] ) && ! empty( $wrong_answers[ $quiz_id ][ "question_ids" ] ) ) {
                $wrong_question_ids = $wrong_answers[ $quiz_id ][ "question_ids" ];
            }
                
            if( isset( $wrong_question_ids ) && ! empty( $wrong_question_ids ) ) {

                $wrong_questions = explode( ',', $wrong_question_ids );
                if( count( $wrong_questions ) > 0 ) {
                    $question_mapper = new WpProQuiz_Model_QuestionMapper();
                    foreach( $wrong_questions as $wrong_question ) {
                        $question_pro_object = $question_mapper->fetchById( $wrong_question );
                        $question_post_id = $this->get_question_post_id( $wrong_question );
                        $question_pro_object->setQuestionPostId( $question_post_id );
                        $question_pro_object->setQuizId( absint( $quiz_id ) );
                        $question_pro_object->setTitle( get_post_field( 'post_title', $question_post_id, 'raw' ) );
                        $question_pro_object->setQuestion( get_post_field( 'post_content', $question_post_id, 'raw' ) );
                        $questions[ $question_post_id ] = $question_pro_object;
                        $pro_questions = $questions;
                    }
                }
            }

            // $general_settings = get_option( 'lrtq_enable_disable_features' );
            // if ( ! empty( $pro_questions ) && $general_settings['enable_ld_retake_allow_random_sequence'] == 'on' ) {
            //     shuffle( $pro_questions );
            //     $max = absint( $max );
            //     if ( $max > 0 ) {
            //         $pro_questions = array_slice( $pro_questions, 0, $max, true );
            //     }
            // }
        }
        
        return $pro_questions;
    }

    /**
     * Check if a quiz is already completed for a user.
     *
     * @param int $user_id The ID of the user.
     * @param int $quiz_id The ID of the quiz.
     * @return bool True if the quiz is completed, false otherwise.
     */
    public function is_quiz_completed($user_id, $quiz_id) {
        // Get the course ID associated with the quiz
        $course_id = learndash_get_course_id($quiz_id);

        return learndash_user_quiz_has_completed( $user_id, $quiz_id, $course_id );
    }


    /**
     * Overrides fetchAll() method in WpProQuiz_Model_QuestionMapper
     * 
     * @param $sql
     * @return $sql
     */
    function ld_retake_modify_quiz_query( $sql ) {

        global $wpdb, $post;

       
        /**
         * If On Quiz and fetching Question
         */
        if( isset( $post ) && intval( $post->ID ) > 0 ) {
            $shortcode = 'ld_quiz';

            $pattern = get_shortcode_regex();
            $is_quiz_shortcode_exists=preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches )
                && array_key_exists( 2, $matches )
                && in_array( $shortcode, $matches[2] );


	        if( $post->post_type === 'sfwd-quiz' OR $is_quiz_shortcode_exists ) {
                $shortcode_atts = array_keys($matches[2], $shortcode);
                     // if shortcode has attributes
                     if (!empty($shortcode_atts)) {
                      foreach($shortcode_atts as $att) {
                        preg_match('/id="(\d+)"/', $matches[3][$att], $quiz_id);

                        // fill the id into main array
                        $quiz_id = $quiz_id[1];
                      }
                }
    
                if( is_singular( ) ) {

                    if( strstr( $sql, LDLMS_DB::get_table_name('quiz_question'), true) !== false && ( strstr( $sql, 'id IN', true ) || strstr( $sql, 'quiz_id', true ) ) && ! strstr( $sql, 'c.*', true ) ) {

                        if (empty($quiz_id)) {
                            $quiz_id = $post->ID;
                        }
                        
                        $validate_quiz = new Validate_Quiz();
                        $is_allowed = $validate_quiz->check_validity( $quiz_id );
                        if( $is_allowed ) {
                            
                            $quiz_pro_id = $this->get_quiz_id( $quiz_id );
                            /**
                             * Break quiz query to find the Quiz ID
                             */
                            $type = 'default';
                            if( strstr( $sql, 'quiz_id', true ) ) {
                                $quiz_query = explode( "quiz_id", $sql );
                                $type = 'quiz';
                                $sql_parts = explode( ' ', trim( $quiz_query[1] ) );
                            } else {
                                $quiz_query = explode( "id IN", $sql );
                                $sql_parts = explode(' ', trim( $quiz_query[1] ) );
                            }
                            
                            if(!$quiz_query[0]) {
                                return $sql;
                            }
        
                            if (empty($user_id)) {
                                $current_user = wp_get_current_user();
        
                                if ( empty( $current_user->ID ) ) {
                                    return $sql;
                                }
        
                                $user_id = $current_user->ID;
                            }
                            
                            $wrong_answers = get_user_meta( $user_id, '_ld_quiz_retake_wrong_q' );
                            
                            if( $wrong_answers && isset( $wrong_answers[0] ) ) {
                                $wrong_answers = $wrong_answers[0];
                            } else {
                                return $sql;
                            }

                            if( isset( $wrong_answers[$quiz_pro_id]["question_ids"] ) && ! empty( $wrong_answers[ $quiz_pro_id ][ "question_ids" ] ) ) {
                                $wrong_question_ids = $wrong_answers[ $quiz_pro_id ][ "question_ids" ];
                            }
                            if( $type == 'quiz' ) {
                                
                                if( isset( $wrong_question_ids ) && ! empty( $wrong_question_ids ) ) {

                                    $wrong_questions = explode( ',', $wrong_question_ids );
                                    if( count( $wrong_questions ) > 0 ) {
                                        $sql_parts[0] = " id in (" . $wrong_question_ids .  ") and quiz_id = ";
                                    } else {
                                        $sql_parts[0] = " quiz_id = ";
                                    }
                                    
                                }  else {
                                    $sql_parts[0] = " quiz_id = ";
                                }
                            } else {
                                $question_id = intval( str_replace('(','', str_replace(')','', $sql_parts[0]) ) );
                            
                                if( isset( $wrong_question_ids ) && ! empty( $wrong_question_ids ) ) {
                                    
                                    $wrong_questions = explode( ',', $wrong_question_ids );
                                    if( count( $wrong_questions ) > 0 ) {
                                        if( in_array( $question_id, $wrong_questions ) ) {
                                            $sql_parts[0] = " id in (" . $question_id .  ") ";
                                        } else {
                                            $sql_parts[0] = " id in (\"\") ";
                                        }
                                    } else {
                                        $sql_parts[0] = " id in (" . $question_id .  ") ";
                                    }
                                    
                                }  else {
        
                                    $sql_parts[0] = " id in (" . $question_id .  ") ";
                                }
                            }

                            $sql = trim($quiz_query[0]) . " ". implode( ' ', $sql_parts );

                        }
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * Function that triggers on `wp_pro_quiz_completed_quiz` action when the quiz completes.
     */
    public function ld_retake_quiz_complete() {
        // Check if the user is logged in and get the user ID, otherwise, set it to 0.
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        } else {
            $user_id = 0;
        }

        // Retrieve the quiz ID from the POST data or set it to 0.
        if (isset($_POST['quizId'])) {
            $id = absint($_POST['quizId']);
        } else {
            $id = 0;
        }

        // Retrieve the quiz post ID from the POST data or set it to 0.
        if (isset($_POST['quiz'])) {
            $quiz_post_id = absint($_POST['quiz']);
        } else {
            $quiz_post_id = 0;
        }

        // Verify the nonce to ensure the request is legitimate.
        if (!wp_verify_nonce($_POST['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id)) {
            die();
        }

        // Unpack the JSON-encoded results from the POST data.
        if (isset($_POST['results']) && !empty($_POST['results']) && is_string($_POST['results'])) {
            $_POST['results'] = json_decode(stripslashes($_POST['results']), true);
        }

        $results = $_POST['results'];
        $quizId = $_POST['quizId'];
        $quiz = $_POST['quiz'];

        $wrong_ques = array();
        $correct_ques = array();
        $correct_ques_res = array();

        // Retrieve user's wrong questions, correct questions, and correct responses.
        $w_qs = get_user_meta($user_id, '_ld_quiz_retake_wrong_q', true);
        $wc_qs = get_user_meta($user_id, '_ld_quiz_retake_correct_q', true);
        $wc_qs_res = get_user_meta($user_id, '_ld_quiz_retake_correct_res_q', true);

        if (empty($w_qs)) {
            $w_qs = array();
        }
        if (empty($wc_qs)) {
            $wc_qs = array();
        }
        if (empty($wc_qs_res)) {
            $wc_qs_res = array();
        }

        $my_wc_qs = isset($wc_qs[$quizId]) ? $wc_qs[$quizId] : null;
        $my_wc_qs_res = isset($wc_qs_res[$quizId]) ? $wc_qs_res[$quizId] : null;

        // Process quiz results and categorize them as correct or wrong.
        foreach ($results as $q_id => $res) {
            if (is_array($my_wc_qs) && array_key_exists($q_id, $my_wc_qs) && intval($res['correct']) == 1) {
                $correct_ques[$q_id] = $my_wc_qs[$q_id];
                $correct_ques_res[$q_id] = isset($my_wc_qs_res[$q_id]) ? $my_wc_qs_res[$q_id] : null;
            } else {
                if (array_key_exists('correct', $res)) {
                    if (intval($res['correct']) != 1 && $q_id != 'comp' && !isset($res['data']['graded_id'])) {
                        $wrong_ques[] = intval($q_id);
                    } else {
                        if ($q_id != 'comp') {
                            $data = array('response' => $res['data'], 'question_pro_id' => $q_id, 'question_post_id' => $this->get_question_post_id($q_id));
                            $correct_ques[$q_id] = $data;
                        }
                        $correct_ques_res[$q_id] = $res;
                    }
                }
            }
        }

        // Update user's correct and wrong question data.
        $wc_qs[$quizId] = $correct_ques;
        $wc_qs_res[$quizId] = $correct_ques_res;

        $question_ids = (count($wrong_ques) > 0 ? implode(',', $wrong_ques) : '');

        // Additional processing for excluded questions.

        // Define the key for storing all question pro IDs.
        $all_question_pro_ids_key = '_ld_rq_all_question_pro_ids_' . $quizId;

        if ($question_ids == "comp") {
            $question_ids = 0;
            unset($wc_qs[$quizId]);
        }

        // Store the wrong question IDs.
        $w_qs[intval($_POST['quizId'])] = array('question_ids' => $question_ids);

        if (empty($question_ids)) {
            $wc_qs[$quizId] = array();
        }

        // Update user's quiz attempts and counts.
        $date = current_time('mysql');
        $validate_quiz = new Validate_Quiz();
        $is_allowed = $validate_quiz->check_validity($quiz_post_id);

        if ($is_allowed) {
            add_user_meta($user_id, '_ld_quiz_retake_user_attempts', array('quiz_id' => $quiz_post_id, 'datetime' => $date), false);
            $user_quiz_attempts_count = get_user_meta($user_id, '_ld_quiz_retake_user_attempts_count_quiz_' . $quiz_post_id, true);

            if ($user_quiz_attempts_count) {
                $user_quiz_attempts_count++;
                update_user_meta($user_id, '_ld_quiz_retake_user_attempts_count_quiz_' . $quiz_post_id, $user_quiz_attempts_count);
            } else {
                update_user_meta($user_id, '_ld_quiz_retake_user_attempts_count_quiz_' . $quiz_post_id, 1);
            }
        }

        // Fetch quiz and question details.
        $quizMapper = new WpProQuiz_Model_QuizMapper();
        $quizM = $quizMapper->fetch($quizId);

        if ($quiz_post_id !== absint($quizM->getPostId())) {
            $quizM->setPostId($quiz_post_id);
        }

        $questionMapper = new WpProQuiz_Model_QuestionMapper();

        // If randomize order question subset options are enabled.
        if ($quizM->isShowMaxQuestion() && $quizM->getShowMaxQuestionValue() > 0) {
            $question_count = $quizM->getShowMaxQuestionValue();
            $all_question_pro_ids = get_user_meta($user_id, $all_question_pro_ids_key, true);
            $result_count = isset($results['comp']) ? (count($results) - 1) : count($results);

            if ($result_count === $question_count) {
                if (empty($all_question_pro_ids)) {
                    $all_question_pro_ids = [];
                    foreach ($results as $question_pro_id => $question_attempt) {
                        if ($question_pro_id != 'comp') {
                            $all_question_pro_ids[] = $question_pro_id;
                        }
                        update_user_meta($user_id, $all_question_pro_ids_key, $all_question_pro_ids);
                    }
                }
            }

            $questionModels = [];
            if (!empty($all_question_pro_ids_key)) {
                $questionModels = $questionMapper->fetchById($all_question_pro_ids);
            }
        } else {
            $questionModels = $questionMapper->fetchAll($quizM);
            $question_count = count($questionModels);
            delete_user_meta($user_id, $all_question_pro_ids_key);
        }

        // Calculate correct and wrong answer counts.
        $correct_answers_counts = 0;
        $wrong_answers = array();

        if (isset($w_qs[$quizId]['question_ids'])) {
            $wrong_answers = $w_qs[$quizId]['question_ids'];

            if (!empty($wrong_answers)) {
                $wrong_answers = explode(',', $wrong_answers);
                $wrong_answers_count = count($wrong_answers);
                $correct_answers_counts = $question_count - $wrong_answers_count;
            } else {
                $correct_answers_counts = $question_count;
            }
        }

        // Calculate points data for the user.
        $points_data = $this->get_points_data($questionModels, $wrong_answers);

        // Prepare the results data.
        $results = array();
        $results['total_quiz_questions'] = $question_count;
        $results['total_quiz_correct'] = $correct_answers_counts;
        $results['globalPoints'] = $points_data['total_points'];
        $results['points'] = $points_data['points'];
        $results['result'] = $points_data['percentage'];

        // Check if the update is for a user profile.
        $is_profile_update = (isset($_POST['from'], $_POST['action']) && $_POST['from'] == 'profile' && $_POST['action'] == 'update');

        if (empty($question_ids)) {
            delete_user_meta($user_id, $all_question_pro_ids_key);
        }

        // Update user's correct and wrong question data.
        update_user_meta($user_id, '_ld_quiz_retake_correct_q', $wc_qs);
        update_user_meta($user_id, '_ld_quiz_retake_wrong_q', $w_qs);

        // Log the quiz attempt data.
        LD_Retake_Quiz_WP_Logging::add(
            __('Quiz Attempt', 'ld_retake_quiz'),
            __('Set Retake data for the user: ' . $user_id, 'ld_retake_quiz'),
            0, // ID of post where settings are loaded
            'event'
        );

        if (!$is_profile_update) {
            wp_send_json($results);
        }
    }



    /**
     * Calculate and retrieve points-related data for a quiz.
     *
     * @param array $questionModels An array of question models.
     * @param array $wrong_answers An array of wrong answer IDs.
     * @return array An array containing total points, points remaining, and percentage.
     */
    public function get_points_data($questionModels, $wrong_answers) {
        // Check if negative marking is enabled
        $negative_marking_enabled = isset($this->enabled_features['enable_allow_negative_marking']) && ($this->enabled_features['enable_allow_negative_marking'] == 'on') ? true : false;

        // Check if retake quiz is enabled
        $is_retake_quiz_enabled = isset($this->enabled_features['enable_allow_retake_quiz']) && ($this->enabled_features['enable_allow_retake_quiz'] == 'on') ? true : false;

        // Initialize variables to store points data
        $total_points = 0;
        $points_to_deduct = 0;

        foreach ($questionModels as $questionModel) {
            if ($questionModel instanceof WpProQuiz_Model_Question) {
                $q_id = $questionModel->getId();
                $q_points = $questionModel->getPoints();
                $total_points += $q_points;

                if (!empty($wrong_answers)) {
                    if (in_array(strval($q_id), $wrong_answers)) {
                        $points_to_deduct += $q_points;

                        // Deduct additional points for negative marking if enabled
                        if ($is_retake_quiz_enabled && $negative_marking_enabled) {
                            $neg_points = get_post_meta($questionModel->getQuestionPostId(), '_ld_retake_negative_points', true);
                            $points_to_deduct += intval($neg_points);
                        }
                    }
                }
            }
        }

        // Ensure points are non-negative integers
        $total_points = absint($total_points);
        $points_to_deduct = absint($points_to_deduct);

        // Calculate remaining points and percentage
        $remaining_points = $total_points - $points_to_deduct;
        $percentage = ($remaining_points / $total_points) * 100;
        $percentage = number_format($percentage, 2);

        // Return the points-related data as an array
        return array('total_points' => $total_points, 'points' => $remaining_points, 'percentage' => $percentage);
    }

}

new Learndash_Retake_Quiz_Handler();