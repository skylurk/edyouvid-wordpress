<?php
/**
 * Displays a lesson.
 *
 * Available Variables:
 *
 * $course_id       : (int) ID of the course
 * $course      : (object) Post object of the course
 * $course_settings : (array) Settings specific to current course
 * $course_status   : Course Status
 * $has_access  : User has access to course or is enrolled.
 *
 * $courses_options : Options/Settings as configured on Course Options page
 * $lessons_options : Options/Settings as configured on Lessons Options page
 * $quizzes_options : Options/Settings as configured on Quiz Options page
 *
 * $user_id         : (object) Current User ID
 * $logged_in       : (true/false) User is logged in
 * $current_user    : (object) Currently logged in user object
 *
 * $quizzes         : (array) Quizzes Array
 * $post            : (object) The lesson post object
 * $topics      : (array) Array of Topics in the current lesson
 * $all_quizzes_completed : (true/false) User has completed all quizzes on the lesson Or, there are no quizzes.
 * $lesson_progression_enabled  : (true/false)
 * $show_content    : (true/false) true if lesson progression is disabled or if previous lesson is completed.
 * $previous_lesson_completed   : (true/false) true if previous lesson is completed
 * $lesson_settings : Settings specific to the current lesson.
 *
 * @since 3.0
 *
 * @package LearnDash\Lesson
 */

$in_focus_mode = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' );

add_filter( 'comments_array', 'learndash_remove_comments', 1, 2 ); ?>

<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

	<?php
	/**
	 * Action to add custom content before the lesson
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-lesson-before', get_the_ID(), $course_id, $user_id );

	learndash_get_template_part(
		'modules/infobar.php',
		array(
			'context'    => 'lesson',
			'has_access' => $has_access,
			'course_id'  => $course_id,
			'user_id'    => $user_id,
		),
		true
	);

	/**
	 * If the user needs to complete the previous lesson display an alert
	 */
	if ( @$lesson_progression_enabled && ! @$previous_lesson_completed ) :

		$previous_item = learndash_get_previous( $post );

		learndash_get_template_part(
			'modules/messages/lesson-progression.php',
			array(
				'previous_item' => $previous_item,
				'course_id'     => $course_id,
				'context'       => 'topic',
				'user_id'       => $user_id,
			),
			true
		);

	endif;

	if ( $show_content ) :

		/**
		 * Content and/or tabs
		 */

		learndash_get_template_part(
			'modules/tabs.php',
			array(
				'course_id' => $course_id,
				'post_id'   => get_the_ID(),
				'user_id'   => $user_id,
				'content'   => $content,
				'materials' => $materials,
				'context'   => 'lesson',
			),
			true
		);

		/**
		 * Display Lesson Assignments
		 */
		$assignments_exist_fn = function_exists( 'learndash_lesson_hasassignments' ) ? 'learndash_lesson_hasassignments' : 'lesson_hasassignments';
		if ( $assignments_exist_fn( $post ) && ! empty( $user_id ) ) :

			/**
			 * Action to add custom content before the lesson assignment
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-lesson-assignment-before', get_the_ID(), $course_id, $user_id );

			learndash_get_template_part(
				'assignment/listing.php',
				array(
					'course_step_post' => $post,
					'user_id'          => $user_id,
					'course_id'        => $course_id,
				),
				true
			);

			/**
			 * Action to add custom content after the lesson assignment
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-lesson-assignment-after', get_the_ID(), $course_id, $user_id );

		endif;

		/**
		 * Lesson Topics or Quizzes
		 */
		if ( ! empty( $topics ) || ! empty( $quizzes ) ) :

			/**
			 * Action to add custom content before the course certificate link
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-lesson-content-list-before', get_the_ID(), $course_id, $user_id );

			global $post;
			$lesson = array(
				'post' => $post,
			);

			if ( ! has_shortcode( $post->post_content, 'uo_lessons_topics_grid' ) ) {
				$cols         = \uncanny_pro_toolkit\LessonTopicGrid::get_shortcode_default_cols_attr();
				$grid_quizzes = \uncanny_pro_toolkit\LessonTopicGrid::get_shortcode_default_show_quizzes_attr();
				echo do_shortcode( '[uo_lessons_topics_grid lesson_id="' . $post->ID . '"' . $cols . $grid_quizzes . ']' );
				$grid_quizzes = ! empty( $grid_quizzes );
			} else {
				$grid_quizzes = \uncanny_pro_toolkit\LessonTopicGrid::shortcode_has_show_quizzes_set( $post->post_content );
			}

			global $course_pager_results;
			$show_lesson_quizzes = true;
			if ( isset( $course_pager_results[ $post->ID ]['pager'] ) && ! empty( $course_pager_results[ $post->ID ]['pager'] ) ) :
				$show_lesson_quizzes = ( $course_pager_results[ $post->ID ]['pager']['paged'] == $course_pager_results[ $post->ID ]['pager']['total_pages'] ? true : false );
			endif;
			$show_lesson_quizzes = apply_filters( 'learndash-show-lesson-quizzes', $show_lesson_quizzes, $post->ID, $course_id, $user_id );
			$quizzes             = learndash_get_lesson_quiz_list( $post->ID, get_current_user_id(), $course_id );
			$topic_quizzes       = array();
			$topics              = (array) learndash_topic_dots( $post->ID, false, 'array', null, $course_id );

			if ( ! $grid_quizzes && ( ! empty( $quizzes ) || ! empty( $topic_quizzes ) ) && $show_lesson_quizzes ) :
				?>
				<div class="learndash-wrapper">
					<div class="ld-lesson-topic-list">
						<div class="ld-table-list ld-topic-list ld-no-pagination">
							<div class="ld-table-list-header ld-primary-background">

								<div class="ld-table-list-title">
								<span class="ld-item-icon">
									<span class="ld-icon ld-icon-content"></span>
								</span>
									<span class="ld-text">
								<?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); ?>
								</span>
								</div> <!--/.ld-tablet-list-title-->

							</div> <!--/.ld-table-list-header-->
							<div class="ld-table-list-items" id="<?php echo esc_attr( 'ld-topic-list-' . $post->ID ); ?>" data-ld-expand-list>
								<?php
								foreach ( $quizzes as $quiz ) :
									learndash_get_template_part(
										'quiz/partials/row.php',
										array(
											'quiz'      => $quiz,
											'user_id'   => $user_id,
											'course_id' => $course_id,
											'lesson'    => $lesson,
											'context'   => 'lesson',
										),
										true
									);
								endforeach;
								if ( $topic_quizzes && ! empty( $topic_quizzes ) ) :
									foreach ( $topic_quizzes as $quiz ) :
										learndash_get_template_part(
											'quiz/partials/row.php',
											array(
												'quiz'    => $quiz,
												'context' => 'topic',
												'course_id' => $course_id,
												'user_id' => $user_id,
											),
											true
										);
									endforeach;
								endif;
								?>
							</div>
						</div>
					</div>
				</div>
				<?php
			endif;
			/**
			 * Action to add custom content before the course certificate link
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-lesson-content-list-after', get_the_ID(), $course_id, $user_id );

		endif;

	endif; // end $show_content

	/**
	 * Set a variable to switch the next button to complete button
	 *
	 * @var $can_complete [bool] - can the user complete this or not?
	 */
	$can_complete = false;

	if ( $all_quizzes_completed && $logged_in && ! empty( $course_id ) ) :
		$can_complete = apply_filters( 'learndash-lesson-can-complete', true, get_the_ID(), $course_id, $user_id );
	endif;

	learndash_get_template_part(
		'modules/course-steps.php',
		array(
			'course_id'        => $course_id,
			'course_step_post' => $post,
			'user_id'          => $user_id,
			'course_settings'  => isset( $course_settings ) ? $course_settings : array(),
			'can_complete'     => $can_complete,
			'context'          => 'lesson',
		),
		true
	);

	/**
	 * Action to add custom content after the lesson
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-lesson-after', get_the_ID(), $course_id, $user_id );
	?>

</div> <!--/.learndash-wrapper-->
