<?php

/**
 * Abort if this file is accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Learndash_Retake_Quiz_Admin_Settings {

    private $general_settings;

    /**
     *  Constructor
     */
    public function __construct() {

        add_action ( 'wp_ajax_ld_retakequiz_load_groups',  [ $this, 'ld_retake_quiz_load_groups' ] );
        add_action ( 'wp_ajax_ld_retakequiz_load_courses', [ $this, 'ld_retake_quiz_load_courses' ] );
        add_action ( 'wp_ajax_ld_retakequiz_load_lessons', [ $this, 'ld_retake_quiz_load_lessons' ] );
        add_action ( 'wp_ajax_ld_retakequiz_load_users',   [ $this, 'ld_retake_quiz_load_users' ] );
        add_action ( 'wp_ajax_ld_retakequiz_load_quizzes', [ $this, 'ld_retake_quiz_load_quizzes' ] );
        add_action ( 'wp_ajax_ld_retakequiz_load_topics',  [ $this, 'ld_retake_quiz_load_topics' ] );
        add_action ( 'wp_ajax_ld_retakequiz_clear_logs',   [ $this, 'ld_retakequiz_clear_logs' ] );

        $this->general_settings = get_option( 'lrtq_general_settings', array() );

        /*
        * ==============================================================================
        * COURSE / LESSON PROGRESS RESET ON QUIZ FAILED - SETTINGS FILTERS   
        *===============================================================================
        */
        add_filter( 'learndash_settings_fields', [ $this, 'ld_retake_reset_setting' ], 10, 2 );
        add_filter( 'learndash_metabox_save_fields_learndash-quiz-access-settings', [ $this, 'ld_retake_save_reset_setting' ], 31, 3 );
        add_action( 'learndash_quiz_submitted', [ $this, 'ld_retake_check_if_quiz_failed' ], 10, 2 ); 

    }

    function ld_retake_quiz_load_topics() {

        $course_ids  = isset( $_REQUEST['course_ids'] )  ?  array_map( 'absint', $_REQUEST['course_ids'] ) : '';
        $lessons_ids = isset( $_REQUEST['lessons_ids'] ) ?  array_map( 'absint', $_REQUEST['lessons_ids'] ) : '';
        $search      = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';

        $per_page_count = 10;
        $page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
        if( $page == 1 ){
            $offset = 0;
        } else {
            $offset = ($page-1) * $per_page_count;
        }

        $results = array();
        if( is_array( $course_ids ) && count( $course_ids ) > 0 ) {
            $args = array(
                'post_type'     => 'sfwd-topic',
                'post_status'   => 'publish',
                'orderby'       => 'title',
                'order'         => 'ASC',
                'numberposts'   => $per_page_count,
                'offset'        => $offset,
                's'             => $search
            );
            
            //start From here
            $meta_query = array();
            foreach( $course_ids as $course_id ) {
                if( is_array( $lessons_ids ) && count( $lessons_ids  ) > 0 ) {
                    $meta_query["relation"] = 'OR';
                    foreach( $lessons_ids as $lesson_id ) {
                         if ( ! empty( $lesson_id  ) ) { 
                            $meta_query[] = array(
                                    'key'     => 'lesson_id',
                                    'value'   => intval( $lesson_id ),
                                    'compare' => '=',
                                );
                            }
                    }
                } else {
                    $meta_query["relation"] = 'OR';
                    $lessons  = learndash_get_lesson_list( $course_id );
                    foreach( $lessons as $lesson ) {
                        if ( ! empty( $lesson->ID ) ) { 
                            $meta_query[]=array(
                                'key'     => 'lesson_id',
                                'value'   => intval( $lesson->ID ),
                                'compare' => '=',
                            );
                        }
                    }
                }
            }
           
            $args['meta_query'] = $meta_query;
            $query_result = new WP_Query( $args );
            if( $query_result->have_posts() ){
                while( $query_result->have_posts() ){
                    $query_result->the_post();
                    $results[] = [ "id" =>get_the_ID(),"text"=> get_the_title() ];
                }
            }
        } else {

            $args = array(
                'post_type' => 'sfwd-topic',
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
                'numberposts'=> $per_page_count,
                'offset'=>$offset,
                's'=>$search

            );

            $query_result =new WP_Query($args);
            if($query_result->have_posts()){
                while($query_result->have_posts()){
                    $query_result->the_post();
                    $results[] = [ "id" =>get_the_ID(),"text"=> get_the_title() ];
                }
            }
        }

        $arr=array('data'=> $results,'total_count'=>$query_result->found_posts);
        wp_send_json( $arr );
    
    }

    function ld_retake_quiz_load_quizzes() {

        $course_ids         = isset( $_REQUEST['course_ids'] )          ?  array_map( 'absint', $_REQUEST['course_ids'] ) : '';
        $lessons_ids        = isset( $_REQUEST['lessons_ids'] )         ?  array_map( 'absint', $_REQUEST['lessons_ids'] ) : '';
        $topic_ids          = isset( $_REQUEST['topic_ids'] )           ?  array_map( 'absint', $_REQUEST['topic_ids'] ) : '';
        $category_ids       = isset( $_REQUEST['category_ids'] )        ?  array_map( 'absint', $_REQUEST['category_ids'] ) : '';
        $tag_ids            = isset( $_REQUEST['tag_ids'] )             ?  array_map( 'absint', $_REQUEST['tag_ids'] ) : '';
        $specific_quiz_ids  = isset( $_REQUEST['specific_quiz_ids'] )   ?  array_map( 'absint', $_REQUEST['specific_quiz_ids'] ) : '';

        $search = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';

        $per_page_count = 10;
        $page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
        if( $page == 1 ){
            $offset = 0;
        } else {
            $offset = ( $page - 1 ) * $per_page_count;
        }

        $quiz_query_args = array(
            // 'posts_per_page' => -1,
            //'fields'         => 'ids',
            'post_type'         => learndash_get_post_type_slug( 'quiz' ),
            'post_status'       => 'publish',
            'orderby'           => 'ID',
            'order'             => 'ASC',
            'numberposts'       => $per_page_count,
            'offset'            => $offset,
            's'                 => $search,
            'post__not_in'      => $specific_quiz_ids,

        );

        $meta_query_item = array();
        $meta_query['relation'] = 'OR';

        $is_allow_course = ( isset($this->general_settings['ld_retakequiz_allow_courses']) && $this->general_settings['ld_retakequiz_allow_courses'] == 'on' );


        $results = $lesson_and_topic_ids = array();


        if( ( !empty($course_ids) || !empty($lessons_ids) || !empty($topic_ids) ) ) {

            if( !empty($topic_ids) && is_array($topic_ids) ) {

                foreach ($topic_ids as $topic_id) {
                    // $quizzes = learndash_get_lesson_quiz_list($topic_id);
                    // foreach ($quizzes as $quiz) {
                    //     $results[] = array(
                    //         'id' => $quiz['post']->ID,
                    //         'text' => $quiz['post']->post_title
                    //     );
                    // }

                    if ( ! empty( $topic_id ) ) { 
                        $meta_query[]=array(
                            'key'     => 'lesson_id',
                            'value'   => intval( $topic_id ),
                            'compare' => '=',
                        );
                
                    }
                }

            } elseif( !empty($lessons_ids) && is_array($lessons_ids) ) {

                foreach ($lessons_ids as $lessons_id) {

                    if ( ! empty( $lessons_id ) ) { 
                        $meta_query[]=array(
                            'key'     => 'lesson_id',
                            'value'   => intval( $lessons_id ),
                            'compare' => '=',
                        );
                
                    }

                    // $quizzes = learndash_get_lesson_quiz_list($lessons_id);
                    // foreach ($quizzes as $quiz) {
                    //     $results[] = array(
                    //         'id' => $quiz['post']->ID,
                    //         'text' => $quiz['post']->post_title
                    //     );
                    // }

                    $topics = learndash_get_topic_list($lessons_id);
                    foreach ($topics as $topic) {
                        // $topic_quizzes = learndash_get_lesson_quiz_list($topic->ID, null, $course_id);

                        // foreach ($topic_quizzes as $topic_quiz) {
                        //     $results[] = array(
                        //         'id' => $topic_quiz['post']->ID,
                        //         'text' => $topic_quiz['post']->post_title
                        //     );
                        // }


                        if ( ! empty( $topic->ID ) ) { 
                            $meta_query[]=array(
                                'key'     => 'lesson_id',
                                'value'   => intval( $topic->ID ),
                                'compare' => '=',
                            );
                
                        }
                    }
                }

            } elseif( !empty($course_ids) && is_array($course_ids) ) {

                foreach ($course_ids as $course_id) {
                    if ( ! empty( $course_id ) ) { 
                            $meta_query[]=array(
                                'key'     => 'course_id',
                                'value'   => intval( $course_id),
                                'compare' => '=',
                            );
                
                        }
                    // $quiz_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-quiz' );
                    // if (!empty($quiz_ids)) {
                    //     foreach ($quiz_ids as $quiz_id) {
                    //         $results[] = array(
                    //             'id' => $quiz_id,
                    //             'text' => get_the_title($quiz_id)
                    //         );
                    //     }
                    // }

                }
            }

            $quiz_query_args['meta_query']=$meta_query;


           

             $quiz_query = new WP_Query( $quiz_query_args );

              // print_r($quiz_query);

              

            if ( ( $quiz_query instanceof WP_Query ) && ( property_exists( $quiz_query, 'posts' ) ) ) {
                $quizzes = $quiz_query->posts;

                foreach ($quizzes as $quiz) {
                    $results[] = ["id" => $quiz->ID, "text" => $quiz->post_title];
                }
            }

        } else {

            $quiz_query_args = array(
                'post_type'      => learndash_get_post_type_slug( 'quiz' ),
                // 'posts_per_page' => -1,
                'post_status' => 'publish',
                //'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'numberposts'=> $per_page_count,
                'offset'=>$offset,
                's'=>$search,
                'post__not_in'=>$specific_quiz_ids,


            );

            if (is_array($category_ids) && count( $category_ids  ) > 0) {
                $quiz_query_args['category__in']=$category_ids;
            }

            if (is_array($tag_ids) && count( $tag_ids  ) > 0) {
                $quiz_query_args['tag__in']=$tag_ids;
            }

            $quiz_query = new WP_Query( $quiz_query_args );

            if ( ( $quiz_query instanceof WP_Query ) && ( property_exists( $quiz_query, 'posts' ) ) ) {
                $quizzes = $quiz_query->posts;

                foreach ($quizzes as $quiz) {
                    $results[] = ["id" => $quiz->ID, "text" => $quiz->post_title];
                }
            }
        }

       
        $arr=array('data'=> $results,'total_count'=>$quiz_query->found_posts);
        wp_send_json( $arr );
    }

    
    function ld_retake_quiz_load_groups() {

        $specific_group_ids = isset( $_REQUEST['group_ids'] ) ? array_map( 'absint', $_REQUEST['group_ids'] ) : '';
        $search = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';

        $per_page_count = 10;
        $page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
        if( $page == 1 ){
            $offset = 0;
        } else {
            $offset = ( $page - 1 ) * $per_page_count;
        }

        if( empty( $specific_group_ids ) ) {
            $specific_group_ids = array();
        }

        $results = array();
        $args = array(
            'post_type'     => 'groups',
            'post_status'   => 'publish',
            'orderby'       => 'title',
            'order'         => 'ASC',
            'numberposts'   =>  $per_page_count,
            'offset'        =>  $offset,
            's'             =>  $search,
            'post__not_in'  =>  $specific_group_ids,
        );

        $query_result = new WP_Query( $args );
            if( $query_result->have_posts() ){
                while( $query_result->have_posts() ){
                    $query_result->the_post();
                    $results[] = [ "id" =>get_the_ID(),"text"=> get_the_title() ];
                }
            }

        $arr = array( 'data' => $results,'total_count' => $query_result->found_posts );
        wp_send_json( $arr );

        // $groups = get_posts($groups_args);

        // foreach( $groups as $group ) {
        //     if( in_array($group->ID, $specific_group_ids) ) {
        //         continue;
        //     }
        //     $results[] = [ "id" => $group->ID,"text"=> $group->post_title ];
        // }

        // wp_send_json( $results );
    }

    
    function ld_retake_quiz_load_courses() {
        $group_ids = isset( $_REQUEST['group_ids'] ) ? array_map( 'absint', $_REQUEST['group_ids'] ) : '';
        $search = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';

        $per_page_count = 10;
        $page = isset($_GET['page']) ? absint($_GET['page']) : 1;
        if( $page==1 ){
            $offset = 0;
        } else {
            $offset = ($page-1) * $per_page_count;
        }

        $results = array();
        if( is_array( $group_ids ) && count( $group_ids ) > 0 ) {
            $courses_args = array(
                'post_type' => 'sfwd-courses',
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
                'numberposts'=> $per_page_count,
                'offset'=>$offset,
                's'=>$search
            );

            $meta_query=array();
            $meta_query['relation']='OR';
            foreach( $group_ids as $group_id ) {                    
                    if ( ! empty( $group_id ) ) { 
                        $meta_query[]=array(
                                    'key'     => 'learndash_group_enrolled_' . $group_id,
                                    'compare' => 'EXISTS',
                                );
                
                    }

            }
            // $meta_query=array('relation' => 'OR',$meta_query_item);
            $courses_args['meta_query']=$meta_query;

            $courses =new WP_Query($courses_args);
            if($courses->have_posts()){
                while($courses->have_posts()){
                    $courses->the_post();
                    $results[] = [ "id" =>get_the_ID(),"text"=> get_the_title() ];
                }
            }

        } else {

            $courses_args = array(
                'post_type' => 'sfwd-courses',
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
                'numberposts'=> $per_page_count,
                'offset'=>$offset,
                's'=>$search

            );

            $courses =new WP_Query($courses_args);
            if($courses->have_posts()){
                while($courses->have_posts()){
                    $courses->the_post();
                    $results[] = [ "id" =>get_the_ID(),"text"=> get_the_title() ];
                }
            }
        
        }

        $arr=array('data'=> $results,'total_count'=>$courses->found_posts);
        wp_send_json( $arr );
    }

    
    function ld_retake_quiz_load_lessons() {
        $course_ids = isset( $_REQUEST['course_ids'] ) ? array_map( 'absint', $_REQUEST['course_ids'] ) : '';
        $search = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';

        $per_page_count = 10;
        $page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
        if( $page == 1 ){
            $offset = 0;
        } else {
            $offset = ( $page - 1 ) * $per_page_count;
        }
        $results = array();
        if( is_array( $course_ids ) && count( $course_ids ) > 0 ) {
            $args = array(
                'post_type'     => 'sfwd-lessons',
                'post_status'   => 'publish',
                'orderby'       => 'title',
                'order'         => 'ASC',
                'numberposts'   => $per_page_count,
                'offset'        => $offset,
                's'             => $search

            );

            $meta_query_item=array();
            $meta_query['relation']='OR';
            foreach( $course_ids as $course_id ) {                    
                    if ( ! empty( $course_id ) ) { 
                        $meta_query[] = array(
                            'key'     => 'course_id',
                            'value'   => intval( $course_id ),
                            'compare' => '=',
                        );
                
                    }
            }
            // $meta_query=array('relation' => 'OR',$meta_query_item);
            $args['meta_query'] = $meta_query;
            $query_result = new WP_Query( $args );
            if( $query_result->have_posts() ){
                while( $query_result->have_posts() ){
                    $query_result->the_post();
                    $results[] = [ "id" =>get_the_ID(),"text"=> get_the_title() ];
                }
            }

            // foreach( $course_ids as $course_id ) {
            //     $lessons = learndash_get_lesson_list( $course_id );
            //     foreach( $lessons as $lesson ) {
            //         $results[] = [ "id" => $lesson->ID, "text" => $lesson->post_title ];
            //     }
            // }

        } else {

            $args = array(
                'post_type' => 'sfwd-lessons',
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
                'numberposts'=> $per_page_count,
                'offset'=>$offset,
                's'=>$search

            );

            $query_result =new WP_Query($args);
            if($query_result->have_posts()){
                while($query_result->have_posts()){
                    $query_result->the_post();
                    $results[] = [ "id" =>get_the_ID(),"text"=> get_the_title() ];
                }
            }
        }

        $arr = array( 'data' => $results,'total_count' => $query_result->found_posts );
        wp_send_json( $arr );
    }
    
    function ld_retake_quiz_load_users() {
        $course_ids = isset( $_REQUEST['course_ids'] ) ? array_map( 'absint', $_REQUEST['course_ids'] ) : '';
        $search = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';

        $results = array();
        if( is_array( $course_ids ) && count( $course_ids ) > 0 ) {

            foreach( $course_ids as $course_id ) {
                $course_users = learndash_get_users_for_course( $course_id, array( 'search'=>'*'.$search.'*' ) );

                if ( ! empty( $course_users ) ) {
                    foreach( $course_users->get_results() as $user ) {
                        $user_info = get_userdata( $user );
                        $results[] = [ "id" => $user_info->ID, "text" => $user_info->display_name ];
                    }
                }                
            }
        } else {
            $users = get_users(array('search'=>'*'.$search.'*','role__not_in'=>array('administrator')));
            foreach( $users as $user) {
                $results[] = [ "id" => $user->ID,"text"=> $user->display_name ];
            }
        }

        $arr = array( 'data' => $results );
        wp_send_json( $arr );
    }

    function ld_retakequiz_clear_logs() {
        global $wpdb;
        $table_usermeta = $wpdb->prefix . 'usermeta';
        $table_users = $wpdb->prefix . 'users';
        $results = $wpdb->query( "Delete FROM {$table_usermeta} WHERE `meta_key` = '_ld_quiz_retake_user_attempts'");

        if ( $results ) {
           $respone = [ 'status'=> 'success' ];
        } else{
            $respone = [ 'status' => 'error', 'message' => "Something went wrong" ];
        }
        wp_send_json( $respone );
        
    }

    /*
    * ==============================================================================
    * COURSE / LESSON PROGRESS RESET ON QUIZ FAILED - SETTINGS    
    *===============================================================================
    */
    function ld_retake_reset_setting( $settings, $metabox_key ) {

        if( 'learndash-quiz-access-settings' === $metabox_key ) {

            global $post;

            $saved_settings = learndash_get_setting( $post );
            
            $settings['ld_retake_reset_progress'] = ! isset( $saved_settings['ld_retake_reset_progress'] ) ? '' : $saved_settings['ld_retake_reset_progress'];
        
            $new_field = [
                'ld_retake_reset_progress' => array(
                    'name'      => 'ld_retake_reset_progress',
                    'label'     => esc_html__( 'Course / Lesson / Topic Progress Reset', 'learndash' ),
                    'type'      => 'checkbox-switch',
                    'options'   => array( 'on' => '', ),
                    'help_text'     => sprintf(
                        // translators: placeholder: quiz, quiz.
                        esc_html_x( 'If enable Course / Lesson / Topic will be reset after %1$s failed.', 'placeholder: quiz, quiz', 'learndash' ),
                            learndash_get_custom_label_lower( 'quiz' )
                    ),
                    'value'               => $settings['ld_retake_reset_progress'],
                    'default'             => '',
                    'child_section_state' => ( 'on' === $settings['ld_retake_reset_progress'] ) ? 'open' : 'closed',
                ),
            ];
            $settings = array_merge( $settings, $new_field );
        }
        return $settings;
    }

    /*
    * ==============================================================================
    * COURSE / LESSON PROGRESS RESET ON QUIZ FAILED - SAVE SETTINGS    
    *===============================================================================
    */
    function ld_retake_save_reset_setting( $values = array(), $metabox_key = '', $screen_id = '' ) {

        if ( ( $screen_id === 'sfwd-quiz' ) && ( $metabox_key === 'learndash-quiz-access-settings' ) ) { 

            $post_values = $_POST[ $metabox_key ];
            
            if ( isset( $post_values['ld_retake_reset_progress'] ) ) {
                $values['ld_retake_reset_progress'] = $post_values['ld_retake_reset_progress'];
            } else {
                $values['ld_retake_reset_progress'] = '';
            }
        }
        return $values;
    }

    /*
    * ==============================================================================
    * QUIZ SUMBIT    
    *===============================================================================
    */
    function ld_retake_check_if_quiz_failed( $data, $user ) {
        global $wpdb;

        $quiz_id        = absint( $data['quiz'] );
        $quiz_settings  = get_post_meta( $quiz_id, '_sfwd-quiz', true );
        $quiz_reset     = isset( $quiz_settings['sfwd-quiz_ld_retake_reset_progress'] ) ? $quiz_settings['sfwd-quiz_ld_retake_reset_progress'] : '';
        $quiz_progress     = get_user_meta( absint( $user->ID ), '_sfwd-quizzes', true );
        // Check if quiz reset setting is on 
        if ( 'on' === $quiz_reset ) {

            if( learndash_is_course_shared_steps_enabled() ) {
                
                $course_id = $data['course']->ID;

                // Condition if quiz failed - reset progress
                if ( 1 !== absint( $data['pass'] ) ) {
                    $user_id  = absint( $user->ID );
                    $progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
                    $update_progress = $progress;

                    if ( ! empty( $progress ) && is_array( $progress ) ) {

                        foreach ( $progress as $key => $value ) {

                            if ( $key == $course_id ) {
                                unset( $update_progress[ $key ] );
                            }
                        }
                    }
                    
                    // Reset Quiz Data
                    $new_quiz_progress = $quiz_progress;
                    foreach( $quiz_progress as $k => $q ) {
                        if( $q[ 'quiz' ] === $quiz_id && $q[ 'course' ] === $data[ 'course' ]->ID ) {
                            unset( $new_quiz_progress[ $k ] );

                        }
                    }
                    update_user_meta( $user_id, '_sfwd-course_progress', $update_progress );
                    update_user_meta( $user_id, '_sfwd-quizzes', $new_quiz_progress );
                }

            } else {

                // Associated Lesson / Course
                $course_id = ! empty( get_post_meta( $quiz_id, 'course_id', true ) ) ? get_post_meta( $quiz_id, 'course_id', true ) : 0;
                $lesson_id = ! empty( get_post_meta( $quiz_id, 'lesson_id', true ) ) ? get_post_meta( $quiz_id, 'lesson_id', true ) : 0;
                
                // Condition if quiz failed - reset progress
                if ( 1 !== absint( $data['pass'] ) ) {
                    $user_id  = absint( $user->ID );
                    $progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
                    $update_progress = $progress;

                    if ( ! empty( $progress ) && is_array( $progress ) ) {

                        foreach ( $progress as $key => $value ) {

                            if ( $key == $course_id ) {
                                unset( $update_progress[ $key ] );
                            }
                        }
                    }

                    // Reset Quiz Data
                    $new_quiz_progress = $quiz_progress;
                    foreach( $quiz_progress as $k => $q ) {
                        if( $q[ 'quiz' ] === $quiz_id && $q[ 'course' ] === $data[ 'course' ]->ID ) {
                            unset( $new_quiz_progress[ $k ] );

                        }
                    }

                    update_user_meta( $user_id, '_sfwd-course_progress', $update_progress );
                    update_user_meta( $user_id, '_sfwd-quizzes', $new_quiz_progress );
                    
                }
            }
        }
    }
}
new Learndash_Retake_Quiz_Admin_Settings();