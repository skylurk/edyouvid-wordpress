<?php

/**
 * Abort if this file is accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

class Validate_Quiz {
	
	public function __construct( ) {
            
    }

    /**
	 * Returns the quiz categories
	 *
	 * @param $quiz_id
     * @return $quiz_categories
	 */
    public function get_quiz_categories( $quiz_id ) {
        
        $ld_settings = get_option( 'learndash_settings_quizzes_taxonomies');
        $quiz_categories = array();
        
        if( isset( $ld_settings['wp_post_category'] ) && $ld_settings['wp_post_category'] == 'yes' ) {
            
            $term_list = wp_get_post_terms( $quiz_id , 'category', array("fields" => "ids"));
            
            if( is_array( $term_list ) && count( $term_list ) ) {
                foreach( $term_list as $cat) { 
                    if ( $cat instanceof WP_Term )
                        $quiz_categories[] = $cat->term_id;
                    else
                        $quiz_categories[] = $cat;
                }
            }    
        }
         
        if( isset( $ld_settings['ld_quiz_category'] ) && $ld_settings['ld_quiz_category'] == 'yes' ) {
            if( taxonomy_exists( 'ld_quiz_category' ) ){
                
                $term_list = wp_get_post_terms( $quiz_id , 'ld_quiz_category', array( "fields" => "ids" ) );
                
                if( is_array( $term_list ) && count( $term_list ) ) {
                    foreach( $term_list as $cat) { 
                        if ( $cat instanceof WP_Term )
                        $quiz_categories[] = $cat->term_id;
                    else
                        $quiz_categories[] = $cat;
                    }
                }
            }
        }
        
        return $quiz_categories;
    }
    
    /**
	 * Returns the quiz tags
	 *
	 * @param $quiz_id
     * @return $quiz_tags
	 */
    public function get_quiz_tags( $quiz_id ) {
        
        $ld_settings = get_option( 'learndash_settings_quizzes_taxonomies');
        $quiz_tags = array();
        if( isset( $ld_settings['wp_post_tag'] ) && $ld_settings['wp_post_tag'] == 'yes' ) {
            
            $term_list = wp_get_post_terms( $quiz_id , 'post_tag', array("fields" => "ids"));
            $term_list = get_the_tags( $quiz_id );

            if( is_array( $term_list ) && count( $term_list ) ) {
                foreach( $term_list as $cat) { 
                    if ( $cat instanceof WP_Term )
                        $quiz_tags[] = $cat->term_id;
                    else
                        $quiz_tags[] = $cat;
                }
            }
        }
            
        if( isset( $ld_settings['ld_quiz_tag'] ) && $ld_settings['ld_quiz_tag'] == 'yes' ) {
            if( taxonomy_exists( 'ld_quiz_tag' ) ){
                
                $term_list = wp_get_post_terms( $quiz_id , 'ld_quiz_tag', array("fields" => "ids"));

                if( is_array( $term_list ) && count( $term_list ) ) {
                    foreach( $term_list as $cat) { 
                        
                        if ( $cat instanceof WP_Term )
                            $quiz_tags[] = $cat->term_id;
                        else
                            $quiz_tags[] = $cat;
                    }    
                }
            }
        }

        return $quiz_tags;
    }

    /**
	 * Returns the quiz tags
	 *
	 * @param $quiz_id
     * @return $quiz_tags
	 */

    public function check_validity( $quiz_id ) {

        $general_settings = get_option( 'lrtq_general_settings' );
        $enabled_features = get_option( 'lrtq_enable_disable_features' );
        $ld_retakequiz_courses           = isset( $general_settings['ld_retakequiz_course'] ) ? $general_settings['ld_retakequiz_course'] : array();
        $ld_retakequiz_lessons           = isset( $general_settings['ld_retakequiz_lesson'] ) ? $general_settings['ld_retakequiz_lesson'] : array();
        $ld_retakequiz_topics            = isset( $general_settings['ld_retakequiz_topic'] ) ? $general_settings['ld_retakequiz_topic'] : array();
        $ld_retakequiz_quizzes           = isset( $general_settings['ld_retakequiz_quizes'] ) ? $general_settings['ld_retakequiz_quizes'] : array();
        $ld_retakequiz_categories        = isset( $general_settings['ld_retakequiz_categories'] ) ? $general_settings['ld_retakequiz_categories'] : array();
        $ld_retakequiz_tags              = isset( $general_settings['ld_retakequiz_tags'] ) ? $general_settings['ld_retakequiz_tags'] : array();
        $ld_retakequiz_exclude_quizzes   = isset( $general_settings['ld_retakequiz_exclude_quizzes'] ) ? $general_settings['ld_retakequiz_exclude_quizzes'] : array();
        $ld_retakequiz_groups            = isset( $general_settings['ld_retakequiz_group'] ) ? $general_settings['ld_retakequiz_group'] : array();
        $ld_retakequiz_exclude_groups    = isset( $general_settings['ld_retakequiz_exclude_groups'] ) ? $general_settings['ld_retakequiz_exclude_groups'] : array();
        $ld_retakequiz_exclude_users     = isset( $general_settings['ld_retakequiz_exclude_users'] ) ? $general_settings['ld_retakequiz_exclude_users'] : array();
        $ld_retakequiz_delay             = isset( $enabled_features['ld_retakequiz_delay'] ) ? $enabled_features['ld_retakequiz_delay'] : 0;
        $ld_retakequiz_delay_type        = isset( $enabled_features['ld_retakequiz_delay_type'] ) ? $enabled_features['ld_retakequiz_delay_type'] : 'Minutes';
        $ld_retake_negative_marking      = isset( $enabled_features['enable_allow_negative_marking'] ) ? $enabled_features['enable_allow_negative_marking'] : '';

        $is_retake_quiz_enabled = ( isset($enabled_features['enable_allow_retake_quiz']) && $enabled_features['enable_allow_retake_quiz'] == 'on' );

        if(!$is_retake_quiz_enabled) {
            return true;
        }

        $lesson_id                       = learndash_get_lesson_id( $quiz_id );
        $course_id                       = learndash_get_course_id( $quiz_id );

        $in_course = true;
        $in_lesson = true;
        $in_topic = true;
        $in_quiz = true;
        $in_category = true;
        $in_tag = true;
        $is_excluded_quiz = false;
        $is_excluded_group = false;
        $is_specific_group = false;
        $is_excluded_user = false;

        $course_exists = false;
        $lesson_exists = false;
        $topic_exists = false;
        $quiz_exists = false;
        $category_exists = false;
        $tag_exists = false;
        $exclude_quiz_exists = false;
        $specific_group_exists = false;
        $exclude_group_exists = false;
        $exclude_user_exists = false;

        /**
         * Return false if quiz do not blongs to selected courses list
         */
        if( is_array( $ld_retakequiz_courses ) && count( $ld_retakequiz_courses ) > 0 ) {
            $course_exists = true;
            if( ! in_array( $course_id, $ld_retakequiz_courses ) ) {
                $in_course = false;
            }
        }
        
        /**
         * Return false if quiz do not blongs to selected lessons list
         */
        if( is_array( $ld_retakequiz_lessons ) && count( $ld_retakequiz_lessons ) > 0 ) {
            $lesson_exists = true;

            if( ! in_array( $lesson_id, $ld_retakequiz_lessons ) ) {
                $in_lesson = false;

            }
           
            //Check if quiz is assign to lesson's topic instead of lesson
            if(!$in_lesson && in_array( $lesson_id, $ld_retakequiz_lessons )) {

                $quizzes = learndash_get_lesson_quiz_list($lesson_id, null, $course_id);
                foreach ($quizzes as $quiz) {

                    if ($quiz_id == $quiz['post']->ID) {
                        $in_lesson = true;
                        // print_r($quizzes);
                        break;
                    }
                }
            }

             // var_dump( $in_lesson );
        }

        /**
         * Return false if quiz do not blongs to selected topics list
         */
        if( is_array( $ld_retakequiz_topics ) && count( $ld_retakequiz_topics ) > 0 ) {

            $topic_exists = true;

            $not_exists = true;
            foreach( $ld_retakequiz_topics as $topic_id ) {
                $topic_quiz_list = learndash_get_lesson_quiz_list( $topic_id, null, $course_id ); 
                foreach( $topic_quiz_list as $item ) {
                    $post = $item[ 'post' ];
                    if( $quiz_id == $post->ID ) {
                        $not_exists = false;
                    }
                }
            }

            if( $not_exists ) {
                $in_topic = false;
            }
        }

        /**
         * Return false if quiz do not blongs to selected quizzes list
         */
        if( is_array( $ld_retakequiz_quizzes ) && count( $ld_retakequiz_quizzes ) > 0 ) {
            $quiz_exists = true;
            if( ! in_array( $quiz_id, $ld_retakequiz_quizzes ) ) {
                $in_quiz = false;
            }
        }

        /**
         * Return false if quiz do not blongs to selected categories list
         */
        $quiz_categories = $this->get_quiz_categories( $quiz_id );
        if( is_array( $ld_retakequiz_categories ) && count( $ld_retakequiz_categories ) > 0 ) {
            $category_exists = true;
            $not_exists = true;
            foreach( $ld_retakequiz_categories as $cat_id ) {
                if( in_array( $cat_id, $quiz_categories ) ) {
                    $not_exists = false;
                }
            }

            if( $not_exists ) {
                $in_category = false;
            }
        }

        /**
         * Return false if quiz do not blongs to selected tags list
         */
        $quiz_tags = $this->get_quiz_tags( $quiz_id );
        if( is_array( $ld_retakequiz_tags ) && count( $ld_retakequiz_tags ) > 0 ) {
            $tag_exists = true;
            $not_exists = true;
            foreach( $ld_retakequiz_tags as $cat_id ) {
                if( in_array( $cat_id, $quiz_tags ) ) {
                    $not_exists = false;
                }
            }

            if( $not_exists ) {
                $in_tag = false;
            }
        }
        
        /**
         * Return false if user is in excluded list
         */
        $user_id = get_current_user_id();
        if( is_array( $ld_retakequiz_exclude_users ) && count( $ld_retakequiz_exclude_users ) > 0 ) {
            $exclude_user_exists = true;
            if( in_array( $user_id, $ld_retakequiz_exclude_users ) ) {
                $is_excluded_user = true;
            }
        }

        /**
         * Return false if quiz is in excluded list
         */
        if( is_array( $ld_retakequiz_exclude_quizzes ) && count( $ld_retakequiz_exclude_quizzes ) > 0 ) {
            $exclude_quiz_exists = true;
            if( in_array( $quiz_id, $ld_retakequiz_exclude_quizzes ) ) {
                $is_excluded_quiz = true;
            }
        }

        if( is_array( $ld_retakequiz_exclude_groups ) && count( $ld_retakequiz_exclude_groups ) > 0 ) {
            $exclude_group_exists = true;
            foreach( $ld_retakequiz_exclude_groups as $exclude_group_id ) {
                $group_quiz_ids = learndash_get_group_course_quiz_ids( $exclude_group_id );
                foreach( $group_quiz_ids as $group_quiz_id ) {
                    if( $quiz_id == $group_quiz_id ) {
                        $is_excluded_group = true;
                    }
                }
            }
        }


        if( is_array( $ld_retakequiz_groups ) && count( $ld_retakequiz_groups ) > 0 ) {
            $specific_group_exists = true;
            foreach( $ld_retakequiz_groups as $group_id ) {
                $group_quiz_ids = learndash_get_group_course_quiz_ids( $group_id );
                foreach( $group_quiz_ids as $group_quiz_id ) {
                    if( $quiz_id !== $group_quiz_id ) {
                        return false;
                    }
                }
            }
        }


      

        $is_allow_course = ( isset($general_settings['ld_retakequiz_allow_courses']) && $general_settings['ld_retakequiz_allow_courses'] == 'on' );

        if( $course_exists && !$in_course ) {
            return false;
        }

        if( $lesson_exists && !$in_lesson ) {
            return false;
        }

        if( $topic_exists && !$in_topic ) {
            return false;
        }
        
        if( $is_allow_course ) {
            if( $category_exists && !$in_category ) {
                return false;
            }

            if( $tag_exists && !$in_tag ) {
                return false;
            }

            if( $exclude_group_exists && $is_excluded_group ) {
                return false;
            }

            if( $specific_group_exists && $is_specific_group ) {
                return false;
            }
        }

        if( $quiz_exists && !$in_quiz ) {
            return false;
        }

        if( $exclude_quiz_exists && $is_excluded_quiz ) {
            return false;
        }

        if( $exclude_user_exists && $is_excluded_user ) {
            return false;
        }

        return true;
    }
}