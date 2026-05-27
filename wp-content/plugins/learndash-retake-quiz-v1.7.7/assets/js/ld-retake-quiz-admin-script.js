(function( $ ) { 'use strict';
    $( document ).ready( function() {
        var LDRQ_Admin = {
            init: function() {
                this.generalScript();

                this.pre_load_groups();
                this.pre_load_courses();
                this.pre_load_lessons();
                this.pre_load_topics();
                this.pre_load_quizzes();
                this.pre_load_exclude_quizzes();
                this.pre_load_exclude_groups();
                this.pre_load_users();
            },

            /**
             * Admin Script
             */
            generalScript: function() {
                $('#ld_retakequiz_exclude_users').attr( 'multiple', true );

                $('.ld-retakequiz-select2-ddl').select2({
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    width: 'element'
                });
            },
            pre_load_courses: function() {
            // var courses = $( '#ld_retakequiz_course' ).select2( 'val' );

                   $('#ld_retakequiz_course').select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var query = {
                          group_ids : groups,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_courses',
                        };

                        // console.log(groups);

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                });

            },
            pre_load_lessons: function() {

                // var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                $('#ld_retakequiz_lesson').select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var query = {
                          course_ids : courses,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_lessons',
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });


                // $('#ld_retakequiz_lesson').on('select2:opening select2:closing', function( event ) {
                //     var $searchfield = $(this).parent().find('.select2-search__field');
                //     $searchfield.prop('disabled', true);
                // });




            },
            pre_load_topics: function() {
                $('#ld_retakequiz_topic').select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var lessons = $( '#ld_retakequiz_lesson' ).select2( 'val' );
                        var topics = $( '#ld_retakequiz_topic' ).select2( 'val' );
                        var query = {
                        course_ids : courses,
                        lessons_ids: lessons,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_topics',
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                });


            },
            pre_load_quizzes: function() {
                $('#ld_retakequiz_quizes').select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var lessons = $( '#ld_retakequiz_lesson' ).select2( 'val' );
                        var topics = $( '#ld_retakequiz_topic' ).select2( 'val' );
                        var categories  =   $( '#ld_retakequiz_categories' ).select2( 'val' );
                        var tags        =   $( '#ld_retakequiz_tags' ).select2( 'val' );
                        var quizzes = $( '#ld_retakequiz_quizes' ).select2( 'val' );
                        var exclude_quizzes = $( '#ld_retakequiz_exclude_quizzes' ).select2( 'val' );
                        var exclude_groups = $( '#ld_retakequiz_exclude_groups' ).select2( 'val' );
                        

                        var query = {
                          action: 'ld_retakequiz_load_quizzes',
                            category_ids : categories,
                            tag_ids : tags,
                            group_ids : groups,
                            course_ids : courses,
                            lessons_ids: lessons,
                            topic_ids: topics,
                             quiz_ids:exclude_quizzes,
                          search: params.term,
                          page: params.page || 1,
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });


            },
            pre_load_exclude_quizzes: function() {
                 $('#ld_retakequiz_exclude_quizzes').select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var lessons = $( '#ld_retakequiz_lesson' ).select2( 'val' );
                        var topics = $( '#ld_retakequiz_topic' ).select2( 'val' );
                        var categories  =   $( '#ld_retakequiz_categories' ).select2( 'val' );
                        var tags        =   $( '#ld_retakequiz_tags' ).select2( 'val' );
                        var quizzes = $( '#ld_retakequiz_quizes' ).select2( 'val' );
                        var exclude_quizzes = $( '#ld_retakequiz_exclude_quizzes' ).select2( 'val' );
                        var exclude_groups = $( '#ld_retakequiz_exclude_groups' ).select2( 'val' );
                        
                        // console.log(quizzes);

                        var query = {
                          action: 'ld_retakequiz_load_quizzes',
                            category_ids : categories,
                            tag_ids : tags,
                            group_ids : groups,
                            course_ids : courses,
                            lessons_ids: lessons,
                            topic_ids: topics,
                            quiz_ids:quizzes,
                          search: params.term,
                          page: params.page || 1,
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });
            },
            pre_load_users: function() {
                $('#ld_retakequiz_exclude_users').select2({
                    minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var query = {
                        course_ids : courses,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_users',
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                });

            },
            pre_load_exclude_groups: function() {                
                // var exclude_groups = $( '#ld_retakequiz_exclude_groups' ).select2( 'val' );

                $('#ld_retakequiz_exclude_groups').select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var query = {
                          action: 'ld_retakequiz_load_groups',
                          group_ids:groups,
                          search: params.term,
                          page: params.page || 1,
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });



            },
            pre_load_groups: function() {

                // var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                

                $('#ld_retakequiz_group').select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var exclude_groups = $( '#ld_retakequiz_exclude_groups' ).select2( 'val' );
                        var query = {
                          action: 'ld_retakequiz_load_groups',
                          group_ids:exclude_groups,
                          search: params.term,
                          page: params.page || 1,
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });

            },
            load_quizzes: function(){
                 $('#ld_retakequiz_quizes').select2( 'destroy' ).empty( ).select2({
                      // minimumInputLength: 3,
                      multiple: true,
                      width: 'auto',
                      placeholder: myLDRetakeQuizAdminAjax.select_option,
                      //     width: 'element'
                      ajax: {
                        url: myLDRetakeQuizAdminAjax.ajax_url,
                        dataType: 'json',
                        delay: 250, // delay in ms while typing when to perform a AJAX search
                        data: function (params) {
                          var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                          var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                          var lessons = $( '#ld_retakequiz_lesson' ).select2( 'val' );
                          var topics = $( '#ld_retakequiz_topic' ).select2( 'val' );
                          var categories  =   $( '#ld_retakequiz_categories' ).select2( 'val' );
                          var tags        =   $( '#ld_retakequiz_tags' ).select2( 'val' );
                          var quizzes = $( '#ld_retakequiz_quizes' ).select2( 'val' );
                          var exclude_quizzes = $( '#ld_retakequiz_exclude_quizzes' ).select2( 'val' );
                          var exclude_groups = $( '#ld_retakequiz_exclude_groups' ).select2( 'val' );
                          

                          var query = {
                            action: 'ld_retakequiz_load_quizzes',
                              category_ids : categories,
                              tag_ids : tags,
                              group_ids : groups,
                              course_ids : courses,
                              lessons_ids: lessons,
                              topic_ids: topics,
                               quiz_ids:exclude_quizzes,
                            search: params.term,
                            page: params.page || 1,
                          };

                          return query;
                        },
                        processResults: function (data, params) {
                          params.page = params.page || 1;
                          var per_page_count = 10;
                           
                          return {
                            results: data.data,
                            pagination: {
                              more: (params.page * per_page_count) < data.total_count
                            }
                          };
                        }
                      }
                    });


                  $('#ld_retakequiz_exclude_quizzes').select2( 'destroy' ).empty( ).select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var lessons = $( '#ld_retakequiz_lesson' ).select2( 'val' );
                        var topics = $( '#ld_retakequiz_topic' ).select2( 'val' );
                        var categories  =   $( '#ld_retakequiz_categories' ).select2( 'val' );
                        var tags        =   $( '#ld_retakequiz_tags' ).select2( 'val' );
                        var quizzes = $( '#ld_retakequiz_quizes' ).select2( 'val' );
                        var exclude_quizzes = $( '#ld_retakequiz_exclude_quizzes' ).select2( 'val' );
                        var exclude_groups = $( '#ld_retakequiz_exclude_groups' ).select2( 'val' );
                        
                        // console.log(quizzes);

                        var query = {
                          action: 'ld_retakequiz_load_quizzes',
                            category_ids : categories,
                            tag_ids : tags,
                            group_ids : groups,
                            course_ids : courses,
                            lessons_ids: lessons,
                            topic_ids: topics,
                            quiz_ids:quizzes,
                          search: params.term,
                          page: params.page || 1,
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });


            }
        };

        LDRQ_Admin.init();

        $( '#ld_retakequiz_allow_courses' ).on( 'change', function() {
            if( $(this).prop( 'checked' ) ) {
                $( this ).parents( 'tr' ).siblings( 'tr.enable_allow_retake_quiz' ).css( 'display', 'table-row' );
            } else {
                $( this ).parents( 'tr' ).siblings( 'tr.enable_allow_retake_quiz' ).css( 'display', 'none' );
            }

        } ).trigger('change');

        // $( '#ld_retakequiz_allow_retake_quiz' ).on( 'change', function() {
        //     if( $(this).prop( 'checked' ) ) {
        //         $( this ).parents( 'tr' ).siblings( 'tr.enable_allow_completion_message' ).css( 'display', 'table-row' );
        //     }else{
        //         $( this ).parents( 'tr' ).siblings( 'tr.enable_allow_completion_message' ).css( 'display', 'none' );
        //     }
        // } ).trigger('change');  



        $( '#ld_retake_allow_quiz_delays' ).on( 'change', function() {
            if( $(this).prop( 'checked' ) ) {
                $( this ).parents( 'tr' ).siblings( 'tr.ld_retakequiz_delay_box' ).css( 'display', 'table-row' );
            }else{
                $( this ).parents( 'tr' ).siblings( 'tr.ld_retakequiz_delay_box' ).css( 'display', 'none' );
            }
        }).trigger('change'); 

         $( '#ld_retake_allow_retake_limit' ).on( 'change', function() {
            if( $(this).prop( 'checked' ) ) {
                $( this ).parents( 'tr' ).siblings( 'tr.ld_retake_allow_retake_limit_box' ).css( 'display', 'table-row' );
            }else{
                $( this ).parents( 'tr' ).siblings( 'tr.ld_retake_allow_retake_limit_box' ).css( 'display', 'none' );
            }
        }).trigger('change');  

        /**
         * Loads courses based on selected groups
         */
        $( '#ld_retakequiz_group' ).on( 'change', function() {
            $( '#ld_retakequiz_course' ).select2( 'destroy' ).empty( ).select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var query = {
                          group_ids : groups,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_courses',
                        };

                        // console.log(groups);

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                });
        });

        /**
         * Loads lessons based on selected courses
         */
        $( '#ld_retakequiz_course' ).on( 'change', function() {

            $( '#ld_retakequiz_lesson' ).select2( 'destroy' ).empty( ).select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var query = {
                          course_ids : courses,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_lessons',
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });

            $( '#ld_retakequiz_exclude_users' ).select2( 'destroy' ).empty( ).select2({
                    minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var query = {
                        course_ids : courses,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_users',
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                });

        });

        /**
         * Load topics based on selected lessons
         */
        $( '#ld_retakequiz_lesson' ).on( 'change', function() {
            $( '#ld_retakequiz_topic' ).select2( 'destroy' ).empty( ).select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var lessons = $( '#ld_retakequiz_lesson' ).select2( 'val' );
                        var topics = $( '#ld_retakequiz_topic' ).select2( 'val' );
                        var query = {
                        course_ids : courses,
                        lessons_ids: lessons,
                          search: params.term,
                          page: params.page || 1,
                          action: 'ld_retakequiz_load_topics',
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                });
            LDRQ_Admin.load_quizzes();

        } );

        $( '#ld_retakequiz_topic' ).on( 'change', function() {
            LDRQ_Admin.load_quizzes();
        } );

        $( '#ld_retakequiz_categories' ).on( 'change', function() {
            LDRQ_Admin.load_quizzes();
        } );

        $( '#ld_retakequiz_tags' ).on( 'change', function() {
            LDRQ_Admin.load_quizzes();
        } );

        $( '#ld_retakequiz_quizes' ).on( 'change', function() {
             $('#ld_retakequiz_exclude_quizzes' ).select2( 'destroy' ).empty( ).select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var courses = $( '#ld_retakequiz_course' ).select2( 'val' );
                        var lessons = $( '#ld_retakequiz_lesson' ).select2( 'val' );
                        var topics = $( '#ld_retakequiz_topic' ).select2( 'val' );
                        var categories  =   $( '#ld_retakequiz_categories' ).select2( 'val' );
                        var tags        =   $( '#ld_retakequiz_tags' ).select2( 'val' );
                        var quizzes = $( '#ld_retakequiz_quizes' ).select2( 'val' );
                        var exclude_quizzes = $( '#ld_retakequiz_exclude_quizzes' ).select2( 'val' );
                        var exclude_groups = $( '#ld_retakequiz_exclude_groups' ).select2( 'val' );
                        
                        // console.log(quizzes);

                        var query = {
                          action: 'ld_retakequiz_load_quizzes',
                            category_ids : categories,
                            tag_ids : tags,
                            group_ids : groups,
                            course_ids : courses,
                            lessons_ids: lessons,
                            topic_ids: topics,
                            quiz_ids:quizzes,
                          search: params.term,
                          page: params.page || 1,
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });
            
        } );

        $( '#ld_retakequiz_group' ).on( 'change', function() {

           $( '#ld_retakequiz_exclude_groups' ).select2( 'destroy' ).empty( ).select2({
                    // minimumInputLength: 3,
                    multiple: true,
                    width: 'auto',
                    placeholder: myLDRetakeQuizAdminAjax.select_option,
                    //     width: 'element'
                    ajax: {
                      url: myLDRetakeQuizAdminAjax.ajax_url,
                      dataType: 'json',
                      delay: 250, // delay in ms while typing when to perform a AJAX search
                      data: function (params) {
                        var groups = $( '#ld_retakequiz_group' ).select2( 'val' );
                        var query = {
                          action: 'ld_retakequiz_load_groups',
                          group_ids:groups,
                          search: params.term,
                          page: params.page || 1,
                        };

                        return query;
                      },
                      processResults: function (data, params) {
                        params.page = params.page || 1;
                        var per_page_count = 10;
                         
                        return {
                          results: data.data,
                          pagination: {
                            more: (params.page * per_page_count) < data.total_count
                          }
                        };
                      }
                    }
                  });;

            } );

        $('#delete_retake_quiz_data_form').submit(function(e) {

            var c = confirm($(this).find('.lrtq-addon-btn').data('warning'));
            return c;
        });

        $('input[name="delete_data_by_user"]').change(function(e) {
           var delete_by_user_val = $(this).val();
           var user_select_row = $('#delete_data_by_user_select_row');

           if( delete_by_user_val == 'specific' ) {
               user_select_row.show();
           } else {
               user_select_row.hide();
           }
        });

        $('input[name="delete_data_by_quiz"]').change(function(e) {
           var delete_by_quiz_val = $(this).val();
           var quiz_select_row = $('#delete_data_by_quiz_select_row');

           if( delete_by_quiz_val == 'specific' ) {
               quiz_select_row.show();
           } else {
               quiz_select_row.hide();
           }
        });
    });


      //Clear Logs

        $('#ldrt_clear_logs').click(function(){
            var c_message=$(this).data('clear_log_message');
            var r = confirm(c_message);
            if (r == true) {
                var data_params = {
                    action: 'ld_retakequiz_clear_logs',
                };

                $.ajax({
                    type : 'post',
                    url : myLDRetakeQuizAdminAjax.ajax_url,
                    data : data_params,
                    dataType: 'json',
                    success: function(response) {
                       if (response.status=='success') {
                        location.reload();
                       }else if(response.status=="error"){
                        alert(response.message);
                       }
                    }
                });
            }
            
        });


jQuery( document ).ready(function() {
        jQuery(".qstn-icn i").click(function(){
      
           jQuery(this).parent('.qstn-icn').next('p.description').fadeToggle();
        });

    });

})( jQuery );