<?php

/**
 * Abort if this file is accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}
if (class_exists('LD_QuizPro')) {
    class LD_Quiz_Pro_Retake_Extension {
        
        private $negative_marking = '';

        /**
         * Constructor.
         */
        public  function __construct( ) {
            
            add_action( 'wp_ajax_ld_retake_adv_quiz_pro_ajax', array( $this, 'ld_retake_adv_quiz_pro_ajax' ), 20, 1 );
            add_action( 'wp_ajax_nopriv_ld_retake_adv_quiz_pro_ajax', array( $this, 'ld_retake_adv_quiz_pro_ajax' ), 20, 1 );

            add_filter( 'learndash_quiz_attempts', array( $this,'ld_retake_delete_previous_quiz_attempt_logic' ), 30, 4 );

            // Added exclude question field on quiz settings
            // add_filter( 'learndash_settings_fields', array( $this, 'ld_retake_add_ldrt_exclude_questions_field' ), 30, 2 );

            // add_action( 'save_post', array( $this, 'ld_retake_add_ldrt_exclude_questions_field_saved' ), 30, 3);

            $enabled_features = get_option( 'lrtq_enable_disable_features' );
            $this->negative_marking = isset( $enabled_features['enable_allow_negative_marking'] ) ? $enabled_features['enable_allow_negative_marking'] : '';
        }
        
        /**
         * Overrides the ajax quiz callback
         */
        function ld_retake_adv_quiz_pro_ajax() {

            /**
             * First we unpack the $_POST['results'] string
             */
            if ( ( isset( $_POST['data']['responses'] ) ) && ( !empty( $_POST['data']['responses'] ) ) && ( is_string( $_POST['data']['responses'] ) ) ) {
                $_POST['data']['responses'] = json_decode( stripslashes( $_POST['data']['responses'] ), true );
            }

            $func = isset( $_POST['func'] ) ? $_POST['func'] : '';
            $data = isset( $_POST['data'] ) ? (array)$_POST['data'] : null;

            switch ( $func ) {
                case 'checkAnswers':
                    echo $this->checkAnswers( $data );
                    break;
            }

            exit;
        }

        /**
         * Check answers for submitted quiz
         *
         * @param  array $data Quiz information and answers to be checked
         * @return string  JSON representation of checked answers
         */
        function checkAnswers( $data ) {
            // error_log('Data<pre>'. print_r($data, true) .'</pre>');

            if ( is_user_logged_in() ) {
                $user_id = get_current_user_id();
            } else {
                $user_id = 0;
            }

            if ( isset( $data['quizId'] ) ) {
                $id = absint( $data['quizId'] );
            } else {
                $id = 0;
            }

            if ( isset( $data['quiz'] ) ) {
                $quiz_post_id = absint( $data['quiz'] );
            } else {
                $quiz_post_id = 0;
            }

            if ( ( ! isset( $data['quiz_nonce'] ) ) || ( ! wp_verify_nonce( $data['quiz_nonce'], 'sfwd-quiz-nonce-' . $quiz_post_id . '-' . $id . '-' . $user_id ) ) ) {
                die();
            }

            $view = new WpProQuiz_View_FrontQuiz();
            $quizMapper = new WpProQuiz_Model_QuizMapper();
            $quiz = $quizMapper->fetch( $id );
            if ( $quiz_post_id !== absint( $quiz->getPostId() ) ) {
                $quiz->setPostId( $quiz_post_id );
            }

            $questionMapper = new WpProQuiz_Model_QuestionMapper();
            $categoryMapper = new WpProQuiz_Model_CategoryMapper();
            $formMapper     = new WpProQuiz_Model_FormMapper();

            $questionModels = $questionMapper->fetchAll( $quiz );

            $view->quiz     = $quiz;
            $view->question = $questionModels;
            $view->category = $categoryMapper->fetchByQuiz( $quiz );

            $question_count = count( $questionModels );
            ob_start();
            $quizData = $view->showQuizBox( $question_count );
            ob_get_clean();

            $json    = $quizData['json'];
            $results = array(); 
            $question_index = 0;

            $wc_qs = get_user_meta( $user_id, '_ld_quiz_retake_correct_q', true );
            if( empty( $wc_qs ) ) {
                $wc_qs = array();
            }

            $validate_quiz = new Validate_Quiz();
            $is_allowed = $validate_quiz->check_validity( $quiz_post_id );
                        

            $new_question_array = $data['responses'];
            
            if( isset( $wc_qs[$id] ) && is_array( $wc_qs[$id] ) && $is_allowed ) {
                $correct_ques = $wc_qs[$id];
                if( is_array( $correct_ques ) && count( $correct_ques ) > 0 ) {
                    foreach( $correct_ques as $index => $q ) {
                        $q['older'] = 'yes';
                        $new_question_array[ $index ] = $q;
                    }
                }
            } 

            $enabled_features  = get_option( 'lrtq_enable_disable_features' );
            $is_retake_quiz_enabled = ( isset( $enabled_features['enable_allow_retake_quiz'] ) && $enabled_features['enable_allow_retake_quiz'] == 'on' );

            foreach ( $new_question_array as $question_id => $info ) {
                 if (isset( $questionModel ) ) unset( $questionModel );
                
                foreach ( $questionModels as $questionModel ) {

                    if ( $questionModel->getId() == intval( $question_id ) ) {
                        
                        $neg_points = get_post_meta( $questionModel->getQuestionPostId(), '_ld_retake_negative_points', true );

                        $userResponse = $info['response'];	

                        if ( ( is_array( $userResponse ) ) && ( !empty( $userResponse ) ) ) {
                            foreach ( $userResponse as $key => $value ) {
                                if ( ( $value != 0) && ($value != 1) ) {
                            
                                    if ( $value == "false" ) {
                                        $userResponse[ $key ] = false;
                                    } else if ( $value == "true" ) {
                                        $userResponse[ $key ] = true;
                                    }
                                }
                            }
                        }

                        $questionData           = $json[ $question_id ];
                        $questionData['question_post_id'] = $questionModel->getQuestionPostId();
                        $correct                = false;
                        $points                 = 0;
                        $statisticsData         = new stdClass();
                        $extra                  = array();
                        $extra['type']          = $questionData['type'];
                        $questionData['points'] = isset( $questionData['points'] ) ? $questionData['points'] : $questionData['globalPoints'];
                        
                        $question_index++;
                        $answer_pointed_activated = $questionModel->isAnswerPointsActivated();
                        $question_legacy_sanitize_scheme = apply_filters( 'learndash_quiz_question_legacy_sanitize_scheme', false, $userResponse, $questionData );

                        switch ( $questionData['type'] ) {
                            case 'free_answer':
                                $correct = false;
                                $points  = 0;
                                
                                if( is_array( $userResponse ) ) {
                                    $userResponse = $userResponse[0];
                                }

                                if ( ! $question_legacy_sanitize_scheme && ! is_array( $userResponse ) ) {
                                    $userResponse          = esc_attr( wp_unslash( trim( $userResponse ) ) );
                                    $userResponse_filtered = $userResponse;
                                } else {
                                    $userResponse = stripslashes( trim( $userResponse ) );
                                    $userResponse = $userResponse_filtered;
                                }
                                
                                if ( ( ! empty( $questionData['correct'] ) ) && ( '' !== $userResponse_filtered ) ) {

                                    $format_correct = ! $question_legacy_sanitize_scheme;
                                    
                                    $format_correct = apply_filters( 'learndash_quiz_format_correct_answer', $format_correct, $questionData, $questionModel );

                                    foreach ( $questionData['correct'] as $questionData_idx => $questionData_correct ) {
                                        if ( $format_correct ) {
                                            $questionData_correct_filtered = esc_attr( trim( $questionData_correct ) );
                                        } else {
                                            $questionData_correct_filtered = stripslashes( trim( $questionData_correct ) );
                                        }

                                        if ( apply_filters( 'learndash_quiz_question_free_answers_to_lowercase', true, $questionModel ) ) {
                                            if ( function_exists( 'mb_strtolower' ) ) {
                                                $userResponse_filtered         = mb_strtolower( $userResponse_filtered );
                                                $questionData_correct_filtered = mb_strtolower( $questionData_correct_filtered );
                                            } else {
                                                $userResponse_filtered         = strtolower( $userResponse_filtered );
                                                $questionData_correct_filtered = strtolower( $questionData_correct_filtered );
                                            }
                                        }

                                        if ( $userResponse_filtered == $questionData_correct_filtered ) {
                                            $correct = true;
                                            if ( $questionModel->isAnswerPointsActivated() ) {
                                                if ( isset( $questionData['points'][ $questionData_idx ] ) ) {
                                                    $points = (int) $questionData['points'][ $questionData_idx ];
                                                } else {
                                                    $points = 1;
                                                }
                                            } else {
                                                $points = $questionModel->getPoints();
                                            }
                                            break;
                                        }
                                    }
                                }

                                $points = apply_filters( 'learndash_ques_free_answer_pts', $points, $questionData, $userResponse );
                                $correct = apply_filters( 'learndash_ques_free_answer_correct', $correct, $questionData, $userResponse );
                                
                                if( $this->negative_marking == 'on' && ! $correct && intval( $neg_points ) != 0  && $is_retake_quiz_enabled) {
                                        $points = 0;
                                        $points  -= intval( $neg_points );
                                }

                                $extra['r'] = $userResponse;
                                if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
                                    if ( isset( $questionData['correct'] ) ) {
                                        $extra['c'] = $questionData['correct'];
                                    } else {
                                        $extra['c'] = array();
                                    }
                                }

                                break;
                            case 'multiple':
                                $correct = true;
                                $r       = array();

                                foreach ( $questionData['correct'] as $answerIndex => $correctAnswer ) {

                                    //$checked = $questionData['correct'][ $userResponse[ $answerIndex ] ];


                                    if ( $answer_pointed_activated ){
                                        
                                        /**
                                         * Points are calculated per answer, add up all the points the user marked correctly
                                         */
                                        
                                        /*
                                        if ( ! empty( $correctAnswer ) && ! empty( $userResponse[ $answerIndex ] ) ) {
                                            $r[ $answerIndex ] = true;
                                            $correct = true;
                                            $points += $questionData['points'][ $answerIndex ];
                                        } else {
                                            $r[ $answerIndex ] = false;
                                            $correct = false;
                                        }

                                        //if ( $userResponse != $questionData['correct'] ) {
                                        // $correct = false;
                                        //}

                                        $points = apply_filters( 'learndash_ques_multiple_answer_pts_each', $points, $questionData, $answerIndex, $correctAnswer, $userResponse );
                                        $correct = apply_filters( 'learndash_ques_multiple_answer_correct_each', $correct, $questionData, $answerIndex, $correctAnswer, $userResponse );
                                        */
                                        
                                        
                                        
                                        if ( ( isset( $userResponse[ $answerIndex ] ) ) && ( $userResponse[ $answerIndex ] == $correctAnswer ) ) { 
                                            $r[ $answerIndex ] = $userResponse[ $answerIndex ];
                                            $correct_this_item = true;
                                            
                                            if ( $userResponse[ $answerIndex ] == true )
                                                $points += $questionData['points'][ $answerIndex ];
                                            
                                        } else {
                                            $r[ $answerIndex ] = false;
                                            $correct_this_item = false;
                                        }

                                        if ( has_filter( 'learndash_ques_multiple_answer_pts_each' ) ) {
                                            $points = apply_filters( 'learndash_ques_multiple_answer_pts_each', $points, $questionData, $answerIndex, $correctAnswer, $userResponse );
                                        } else {
                                            /**
                                             * Added logic to subtract points on selected incorrect answers. 
                                             */
                                            if ( $questionData['correct'][ $answerIndex ] == 0 ) {
                                                if ( $correct_this_item == false ) {
                                                    if ( intval( $questionData['points'][ $answerIndex ] ) > 0 ) {
                                                        $points -= intval( $questionData['points'][ $answerIndex ] );
                                                    } //else {
                                                    //	$points -= 1;
                                                    //}
                                                }

                                                end( $questionData['correct'] );
                                                $last_key = key( $questionData['correct'] );

                                                // If we are at the last index and the points is less than zero we keep it from being negative.
                                                if ( ( $last_key == $answerIndex ) && ( $points < 0 ) ) {
                                                    $points = 0;
                                                }
                                            }
                                        }
                                        
                                        $correct_this_item = apply_filters( 'learndash_ques_multiple_answer_correct_each', $correct_this_item, $questionData, $answerIndex, $correctAnswer, $userResponse );
                                        if ( ( $correct_this_item != true ) && ( $correct == true ) )
                                            $correct = false;
 
                                    } else {

                                        /**
                                         * Points are allocated for the entire question if the user selects all the correct answers and none of
                                         * the incorrect answers
                                         *
                                         * if the user selects an answer that is marked as correct, mark the question true and let the
                                         * foreach loop check the next answer
                                         *
                                         * if they select an incorrect answer, or fail to select a correct answer, mark it false and break
                                         * the foreach
                                         *
                                         * we don't want to break the foreach if the user did not select an incorrect answer
                                         */
                                        if ( ! empty( $correctAnswer ) && ! empty( $userResponse[ $answerIndex ] ) ) {
                                            $correct = true;
                                            $r[ $answerIndex ] = true;
                                            $points = $questionData['points'];
                                        } elseif ( empty( $correctAnswer ) && ! empty( $userResponse[ $answerIndex ] ) ) {
                                            $correct = false;
                                            $r[ $answerIndex ] = false;
                                            $points = 0;
                                            break;
                                        } elseif ( ! empty( $correctAnswer ) && empty( $userResponse[ $answerIndex ] ) ) {
                                            $correct = false;
                                            $r[ $answerIndex ] = false;
                                            $points = 0;
                                            break;
                                        }

                                        // See https://bitbucket.org/snippets/learndash/aKdpz for examples of this filter. 
                                        $points = apply_filters( 'learndash_ques_multiple_answer_pts_whole', $points, $questionData, $answerIndex, $correctAnswer, $userResponse );
                                        $correct = apply_filters( 'learndash_ques_multiple_answer_correct_whole', $correct, $questionData, $answerIndex, $correctAnswer, $userResponse );

                                        
                                    }


                                }
                                if( $this->negative_marking == 'on' && ! $correct && intval( $neg_points ) != 0  && $is_retake_quiz_enabled) {
                                
                                    $points  = 0;
                                    $points  -= $neg_points;

                                }
                                $extra['r'] = $userResponse;

                                if ( ! $quiz->isDisabledAnswerMark() ) {
                                    $extra['c'] = $questionData['correct'];
                                }

                                break;

                            case 'single':
                                foreach ( $questionData['correct'] as $answerIndex => $correctAnswer ) {
                                    if ($userResponse[ $answerIndex ] == true) {

                                        if ( ( ( isset( $questionData['diffMode'] ) ) && ( ! empty( $questionData['diffMode'] ) ) ) || ( !empty( $correctAnswer ) ) ) {
                                            //DiffMode or Correct
                                            if ( is_array( $questionData['points'] ) ) {
                                                $points = $questionData['points'][ $answerIndex ];
                                            } else {
                                                $points = $questionData['points'];
                                            }
                                        }

                                        if ( ! empty( $correctAnswer) || ! empty( $questionData['disCorrect'] ) ) {
                                            //Correct
                                            $correct = true;
                                        }
                                
                                        // See https://bitbucket.org/snippets/learndash/aKdpz for examples of this filter. 
                                        $points = apply_filters( 'learndash_ques_single_answer_pts', $points, $questionData, $answerIndex, $correctAnswer, $userResponse );
                                        $correct = apply_filters( 'learndash_ques_single_answer_correct', $correct, $questionData, $answerIndex, $correctAnswer, $userResponse );

                                        
                                
                                    }
                                }
                                
                                if( $this->negative_marking == 'on' && ! $correct && intval( $neg_points ) != 0 && $is_retake_quiz_enabled) {
                                
                                    $points  = 0;
                                    $points  -= $neg_points;

                                }
                                
                                $extra['r'] = $userResponse;

                                if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
                                    $extra['c'] = $questionData['correct'];
                                }
                                break;

                            case 'sort_answer':
                            case 'matrix_sort_answer':
                                $correct                 = true;
                                $questionData['correct'] = LD_QuizPro::datapos_array( $question_id, count( $questionData['correct'] ) );

                                foreach ( $questionData['correct'] as $answerIndex => $answer ) {
                                    if ( ! isset( $userResponse[ $answerIndex ] ) || $userResponse[ $answerIndex ] != $answer ) {
                                        $correct = false;
                                    } else {
                                        if ( is_array( $questionData['points'] ) ) {
                                            $points += $questionData['points'][ $answerIndex ];
                                        }
                                    }

                                    $statisticsData->{$answerIndex} = @$userResponse[ $answerIndex ];
                                }

                                if ( $correct ) {
                                    if ( ! is_array( $questionData['points'] ) ) {
                                        $points = $questionData['points'];
                                    }
                                } else {
                                    $statisticsData = new stdClass();
                                }

                                $extra['r'] = $userResponse;

                                if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
                                    $extra['c'] = $questionData['correct'];
                                } else {
                                    $statisticsData = new stdClass();
                                }
                                if( $this->negative_marking == 'on' && ! $correct && intval( $neg_points ) != 0 && $is_retake_quiz_enabled) {
                                
                                    $points  = 0;
                                    $points  -= $neg_points;

                                }
                                break;

                            case 'cloze_answer':
                                $answerData = array();
                                
                                foreach ( $questionData['correct'] as $answerIndex => $correctArray ) {
                                    $answerData[$answerIndex] = false;
                                                                    
                                    if ( ! isset( $userResponse[ $answerIndex ] ) )
                                        $answerData[$answerIndex] = false;
                                
                                    $userResponse[ $answerIndex ] =  stripslashes( trim( $userResponse[ $answerIndex ] ) );
                                    if ( apply_filters('learndash_quiz_question_cloze_answers_to_lowercase', true ) ) {
                                        if ( function_exists( 'mb_strtolower' ) ) {
                                            $user_answer_formatted = mb_strtolower( $userResponse[ $answerIndex ] );
                                        } else {
                                            $user_answer_formatted = strtolower( $userResponse[ $answerIndex ] );
                                        }
                                    } else {
                                        $user_answer_formatted = $userResponse[ $answerIndex ];
                                    }
                                
                                    $answerData[$answerIndex] = in_array( $user_answer_formatted, $correctArray );
                                    $answerData[$answerIndex] =	apply_filters( 'learndash_quiz_check_answer', $answerData[$answerIndex], $questionData['type'], $userResponse[ $answerIndex ], $correctArray, $answerIndex, $questionModel );
                                    $statisticsData->{$answerIndex} = $answerData[$answerIndex];
                                    
                                    if ( $answerData[$answerIndex] === true ) {
                                        if ( ( $questionModel->isAnswerPointsActivated() ) && ( is_array( $questionData['points'] ) ) ) {
                                            $points += ( int ) $questionData['points'][ $answerIndex ];
                                        } else {
                                            $points = ( int ) $questionData['points'];
                                        }
                                    }
                                }
                                
                                // If we have one wrong answer
                                if ( in_array( false, $answerData ) === true ) {
                                    $correct = false;
                                    
                                    // If we are NOT using individual points and there is at least one wrong answer
                                    // then we clear the points. 
                                    if ( !$questionModel->isAnswerPointsActivated() ) {
                                        $points = 0;
                                    }
                                    
                                } else {
                                    // If all the fields are correct then the points stand and we set the correct to true
                                    $correct = true;
                                }

                                $extra['r'] = $userResponse;

                                if ( ! $quiz->isDisabledAnswerMark() && empty( $questionData['disCorrect'] ) ) {
                                    $extra['c'] = $questionData['correct'];
                                }

                                if( $this->negative_marking == 'on' && ! $correct && intval( $neg_points ) != 0 && $is_retake_quiz_enabled) {
                                
                                    $points  = 0;
                                    $points  -= $neg_points;

                                }
                                break;

                            case 'assessment_answer':
                                $correct = true;
                                $points  = intval( $userResponse );
                                if( is_array($userResponse) && count( $userResponse ) > 0 ) {
                                    $points  = intval( $userResponse[0] );
                                }
                                $extra['r'] = $userResponse;
                                break;

                            case 'essay':
                                $essay_data = $questionModel->getAnswerData();
                                $essay_data = array_shift( $essay_data );

                                switch ( $essay_data->getGradingProgression() ) {
                                    case 'not-graded-none':
                                        $points = 0;
                                        $correct = false;
                                        $extra['graded_status'] = 'not_graded';
                                        break;

                                    case 'not-graded-full':
                                        $points = $essay_data->getPoints();
                                        $correct = false;
                                        $extra['graded_status'] = 'not_graded';
                                        break;

                                    case 'graded-full' :
                                        $points = $essay_data->getPoints();
                                        $correct = true;
                                        $extra['graded_status'] = 'graded';
                                        break;

                                    default:
                                        $points = 0;
                                        $correct = false;
                                        $extra['graded_status'] = 'not_graded';
                                }

                                if( is_array( $userResponse ) ) {
                                    $userResponse = $userResponse['graded_id'];
                                }
                                
                                $essay_id = learndash_add_new_essay_response( $userResponse, $questionModel, $quiz, $data );
                                $extra['graded_id'] = is_array( $essay_id ) ? 0 : $essay_id ;
                                if( $this->negative_marking == 'on' && ! $correct && intval( $neg_points ) != 0 && $is_retake_quiz_enabled) {
                                
                                    $points  = 0;
                                    $points  -= intval( $neg_points );

                                }
                                break;

                            default:
                                break;
                        }

                        if ( ! $quiz->isHideAnswerMessageBox() ) {
                            foreach ( $questionModels as $key => $value ) {
                                if ( $value->getId() == $question_id ) {
                                    if ( $correct || $value->isCorrectSameText() ) {
                                        //$extra['AnswerMessage'] = do_shortcode( apply_filters( 'comment_text', $value->getCorrectMsg() ) );
                                        $extra['AnswerMessage'] = do_shortcode( apply_filters( 'the_content', $value->getCorrectMsg() ) );
                                    } else {
                                        //$extra['AnswerMessage'] = do_shortcode( apply_filters( 'comment_text', $value->getIncorrectMsg() ) );
                                        $extra['AnswerMessage'] = do_shortcode( apply_filters( 'the_content', $value->getIncorrectMsg() ) );
                                    }

                                    break;
                                }
                            }
                        }

                        $extra['possiblePoints'] = $questionModel->getPoints();
                
                        $results[ $question_id ] = array(
                            'pid'=>$questionModel->getQuestionPostId(),
                            'id'=>$questionModel->getId(),
                            'c' => $correct,
                            'p' => $points,
                            's' => $statisticsData,
                            'e' => $extra,
                            'o' => ( array_key_exists( 'older', $info ) && $info['older'] == 'yes' ? 'yes' : 'no' )
                        );
                        
                        break;
                    }
                }
            }
            
            do_action( 'ldadvquiz_answered', $results, $quiz, $questionModels);
            
            $total_points = 0;

            foreach( $results as $r_idx => $result ) {

                if ( ( isset( $result['e'] ) ) && ( !empty( $result['e'] ) ) ) {
                    if ( ( isset( $result['e']['type'] ) ) && ( !empty( $result['e']['type'] ) ) ) {
                        $response_str = '';
                        
                        switch( $result['e']['type'] ) {
                            case 'essay':
                                if ( ( isset( $result['e']['graded_id'] ) ) && ( !empty( $result['e']['graded_id'] ) ) ) {
                                    $response_str = maybe_serialize( array( 'graded_id' => $result['e']['graded_id'] ) );
                                }
                                break;

                            case 'free_answer':
                                if ( ( isset( $result['e']['r'] ) ) && ( !empty( $result['e']['r'] ) ) ) {
                                    
                                    $response_str = maybe_serialize( array( $result['e']['r'] ) );
                                }
                                break;
                            
                            case 'assessment_answer':
                                if ( isset( $result['p'] ) ) {
                                    $response_str = maybe_serialize( array( (string)$result['p'] ) );
                                }
                                break;
                                
                            case 'multiple':
                            case 'single':
                            default:
                                if ( ( isset( $result['e']['r'] ) ) && ( !empty( $result['e']['r'] ) ) ) {
                                    $result_array = array();
                                    foreach( $result['e']['r'] as $ri_idx => $ri ) {
                                        if ( $ri === true )
                                            $ri = 1;
                                        else if ( $ri === false )
                                            $ri = 0;
                                        
                                        $result_array[$ri_idx] = $ri;
                                    }
                                    $response_str = maybe_serialize( $result_array );
                                }
                                break;
                            
                                break;
                        }
                        
                        if ( !empty( $response_str ) ) {
                            $answers_nonce = wp_create_nonce( 'ld_quiz_anonce'. $user_id .'_'. $id .'_'. $quiz_post_id .'_'. $r_idx .'_'. $response_str );
                            $results[$r_idx]['a_nonce'] = $answers_nonce;
                        }
                    }
                }
                
                $points_array = array(
                    'points' => intval( $result['p'] ),
                    'correct' => intval( $result['c'] ),
                    'possiblePoints' => intval( $result['e']['possiblePoints'] )
                );
                if ( $points_array['correct'] === false ) $points_array['correct'] = 0;
                else if ( $points_array['correct'] === true ) $points_array['correct'] = 1;
                $points_str = maybe_serialize( $points_array );
                $points_nonce = wp_create_nonce( 'ld_quiz_pnonce'. $user_id .'_'. $id .'_'. $quiz_post_id .'_'. $r_idx .'_'. $points_str );
                $results[$r_idx]['p_nonce'] = $points_nonce;
            }

            //remove data
            $validate_quiz = new Validate_Quiz();
            $is_allowed = $validate_quiz->check_validity( $quiz_post_id );


            // pass is_allowed in if condition
            $enable_disable_options = get_option( 'lrtq_enable_disable_features' );
            if ( $enable_disable_options['delete_previous_quiz_attempt'] == 'on' && get_current_user_id() && $is_allowed ) {
                $this->ld_retake_remove_prev_quiz_callback( $user_id, $quiz_post_id, 0 );
            }
            return json_encode( $results );
        }


        function ld_retake_remove_quiz_attempts($user_id,$quiz_id,$limit){
            global $wpdb;
            $table_usermeta = $wpdb->prefix . 'usermeta';
            $table_users = $wpdb->prefix . 'users';

            $query = $wpdb->prepare( "SELECT um.* FROM {$table_usermeta} um INNER JOIN {$table_users} u ON u.`ID` = um.`user_id` WHERE um.`meta_key` = '_ld_quiz_retake_user_attempts' AND um.`user_id` IN ({$user_id})");
            $quiz_attempts = $wpdb->get_results( $query, 'ARRAY_A' );
            $remove_retake_logs_ids = array();
            if ( $quiz_attempts ) {
                foreach ( $quiz_attempts as $key => $value ) {
                    $quiz = unserialize( $value['meta_value'] );
                    if ( $quiz['quiz_id'] == $quiz_id ) {
                        $remove_retake_logs_ids[] = $value['umeta_id'];
                    }
                } 

                if ( count( $remove_retake_logs_ids ) > $limit ) {
                    foreach ( $remove_retake_logs_ids as $value ) {

                        if ( $limit == 1 && $remove_retake_logs_ids[count( $remove_retake_logs_ids ) - 1] == $value ) {
                            continue;
                        }
                        delete_metadata_by_mid( 'user', $value );
                    }
                }
            }
        }

        function ld_retake_remove_ld_user_activity_quiz_attempts($user_id,$quiz_id,$limit){
            global $wpdb;
            $sql_str = $wpdb->prepare( "SELECT activity_id,activity_type,post_id as quiz_id FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d", $user_id );
            $proquiz_ids = $wpdb->get_results( $sql_str, 'ARRAY_A' );                    
            if ( ! empty( $proquiz_ids ) ) {
                foreach ( $proquiz_ids as $key => $value ) {   
                    if( $value['quiz_id'] == $quiz_id && $value['activity_type'] == 'quiz' ){
                        if( $limit == 1 && count( $proquiz_ids ) > $limit && $proquiz_ids[count( $proquiz_ids ) - 1]['activity_id'] == $value['activity_id'])
                        continue;
                        $this->ld_retake_remove_user_activity( $value['activity_id'] );
                    }
                }
            }
        }

        function ld_retake_remove_user_activity($activity_id){
            global $wpdb;
            //remove user activity by activity_id 
            $wpdb->query($wpdb->prepare( "DELETE FROM ". LDLMS_DB::get_table_name( 'user_activity' ) ." WHERE activity_id = %d",$activity_id));

            //remove user activity meta data by activity_id 
            $wpdb->query($wpdb->prepare( "DELETE FROM ". LDLMS_DB::get_table_name( 'user_activity_meta' ) ." WHERE activity_id = %d",$activity_id));
        }


        function ld_retake_remove_user_activity_from_usermeta( $user_id, $quiz_id, $limit ){
            $user_quizzes_data = get_user_meta( $user_id, '_sfwd-quizzes', true );
            $user_quiz_data_by_id = array();
            //collect quizzes ids from user_meta
            if ( $user_quizzes_data ) {
                foreach ( $user_quizzes_data as $key => $value ) {
                    if ( $value['quiz'] == $quiz_id ) {
                        $user_quiz_data_by_id[] = $key;
                    }
                }
            }
            if ( count( $user_quiz_data_by_id ) > $limit ) {
                if ( $limit == 1 ) {
                    unset( $user_quiz_data_by_id[count( $user_quiz_data_by_id ) - 1] );
                }
                foreach ( $user_quizzes_data as $key => $value ) {
                    if ( in_array( $key, $user_quiz_data_by_id ) ) {
                        unset( $user_quizzes_data[$key] );
                    }
                }
            }
            update_user_meta( $user_id, '_sfwd-quizzes', $user_quizzes_data );
        }

        // $limit will 0 or 1;
        function ld_retake_remove_prev_quiz_callback( $user_id, $quiz_id, $limit = 0 ){
            // Remove learndash User Activity  And User Activity Meta
            $this->ld_retake_remove_ld_user_activity_quiz_attempts( $user_id, $quiz_id, $limit );

            // Remove learndash Quizzes Stat  From user Meta. Show Data on user prfile page. 
            $this->ld_retake_remove_user_activity_from_usermeta( $user_id, $quiz_id, $limit );

          // Remove LDRT Quiz retake logs
          //  $this->ld_retake_remove_quiz_attempts($user_id,$quiz_id,$limit);
        }



        function ld_retake_delete_previous_quiz_attempt_logic( $attempts_left = 0, $attempts_count = 0, $user_id = 0, $quiz_id = 0 ) {

            $validate_quiz = new Validate_Quiz();
            $is_allowed = $validate_quiz->check_validity( $quiz_id );

            // pass is_allowed in if condition
            $enable_disable_options = get_option( 'lrtq_enable_disable_features' );
            if ( $enable_disable_options['delete_previous_quiz_attempt'] == 'on' && get_current_user_id() && $is_allowed ) {
              $this->ld_retake_remove_prev_quiz_callback($user_id,$quiz_id,1);
            }
            return $attempts_left;
        }


        /*
        * Add exclude question field in quiz settings    
        */
        function ld_retake_add_ldrt_exclude_questions_field( $setting_option_fields = array(), $settings_metabox_key = '' ) {

            $enabled_features  = get_option( 'lrtq_enable_disable_features' );
            $is_retake_quiz_enabled = ( isset( $enabled_features['enable_allow_retake_quiz'] ) && $enabled_features['enable_allow_retake_quiz'] == 'on' );

            if ( 'learndash-quiz-access-settings' === $settings_metabox_key  && $is_retake_quiz_enabled ) {
 
                $post_id = get_the_ID();
                $selected_questions = get_post_meta( $post_id, 'ldrt_exclude_questions', true );
                if ( empty( $selected_questions ) ) {
                    $selected_questions = array();
                }

                $quiz_questions = array();
                $quiz_questions_raw = learndash_get_quiz_questions( $post_id );

                if ( ! empty( $quiz_questions_raw ) ) {
                    foreach ( $quiz_questions_raw as $question_post_id => $question_id ) {
                        $quiz_questions[$question_post_id] = get_the_title( $question_post_id );
                    }
                }

                if ( ! isset( $setting_option_fields['ldrt_exclude_questions_field'] ) ) {
                    $setting_option_fields['ldrt_exclude_questions_field'] = array(
                        'name'      => 'ldrt_exclude_questions_field',
                        'label'     => sprintf( esc_html_x( 'Exclude %s ( Retake ) ', 'placeholder: Quiz', 'learndash' ), learndash_get_custom_label( 'questions' ) ),
                        'type'      => 'multiselect',
                        'value'     => $selected_questions,
                        'options'   => $quiz_questions,
                        'help_text' => sprintf( esc_html_x( 'Exclude these %s from saving correct %s in %s.', 'placeholder: Quiz', 'learndash' ),learndash_get_custom_label( 'questions' ),learndash_get_custom_label( 'questions' ), learndash_get_custom_label_lower( 'quiz' )
                        ),
                    );
                }
            }
            return $setting_option_fields;
        }

        /*
        * Save exclude questions field in quiz settings  
        */
        function ld_retake_add_ldrt_exclude_questions_field_saved( $post_id = 0, $post = null, $update = false ){

            if ( isset( $_POST['learndash-quiz-access-settings'] ) ) {
                if ( isset( $_POST['learndash-quiz-access-settings']['ldrt_exclude_questions_field'] ) ) {
                    $selected_questions = array_map( 'absint', $_POST['learndash-quiz-access-settings']['ldrt_exclude_questions_field'] );
                } else{
                    $selected_questions = array();
                }
                
                // Update ld retake exclude question field
                update_post_meta( $post_id, 'ldrt_exclude_questions', $selected_questions );
            }

        }
    }
    new LD_Quiz_Pro_Retake_Extension();
}





?>