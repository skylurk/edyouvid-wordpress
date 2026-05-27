<?php
if ( ( ! class_exists( 'LearnDash_Retake_Quiz_Binary_Selector_Quizzes' ) ) && ( class_exists( 'Learndash_Binary_Selector_Posts' ) ) ) {
    /**
     * Class for LearnDash Retake Quiz binary Selector Quizzes.
     */
    class LearnDash_Retake_Quiz_Binary_Selector_Quizzes extends Learndash_Binary_Selector_Posts {

        /**
         * Public constructor for class
         *
         * @param array $args Array of arguments for class.
         */
        public function __construct( $args = array() ) {

            $this->selector_class = get_class( $this );

            $defaults = array(
                'quiz_id'           => 0,
                'post_type'          => learndash_get_post_type_slug('quiz'),
                'html_title'         => '<h3>' . sprintf(
                    // translators: placeholder: courses.
                        esc_html_x( '%s', 'Quizzes label', 'ld_retake_quiz' ),
                        LearnDash_Custom_Label::get_label( 'quizzes' )
                    ) . '</h3>',
                'html_id'            => 'learndash_retake_quiz_quizzes',
                'html_class'         => 'learndash_retake_quiz_quizzes',
                'html_name'          => 'learndash_retake_quiz_quizzes',
                'search_label_left'  => sprintf(
                // translators: placeholder: courses.
                    esc_html_x( 'Search All %s', 'Search All Quizzes Label', 'ld_retake_quiz' ),
                    LearnDash_Custom_Label::get_label( 'quizzes' )
                ),
                'search_label_right' => sprintf(
                // translators: placeholder: courses.
                    esc_html_x( 'Search Selected %s', 'Search Selected Quizzes Label', 'ld_retake_quiz' ),
                    LearnDash_Custom_Label::get_label( 'quizzes' )
                ),
            );

            $args = wp_parse_args( $args, $defaults );

            $args['html_id']   = $args['html_id'] . '-' . $args['quiz_id'];
            $args['html_name'] = $args['html_name'] . '[' . $args['quiz_id'] . ']';

            parent::__construct( $args );
        }
    }
}