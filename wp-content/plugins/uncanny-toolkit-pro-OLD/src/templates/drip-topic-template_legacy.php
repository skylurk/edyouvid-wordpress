<?php

$drip_course = new \uncanny_pro_toolkit\UncannyDripTopicsByGroup();

/**
 * Displays a course
 *
 * Available Variables:
 * $course_id        : (int) ID of the course
 * $course        : (object) Post object of the course
 * $course_settings : (array) Settings specific to current course
 *
 * $courses_options : Options/Settings as configured on Course Options page
 * $topics_options : Options/Settings as configured on Topics Options page
 * $quizzes_options : Options/Settings as configured on Quiz Options page
 *
 * $user_id        : Current User ID
 * $logged_in        : User is logged in
 * $current_user    : (object) Currently logged in user object
 *
 * $course_status    : Course Status
 * $has_access    : User has access to course or is enrolled.
 * $materials        : Course Materials
 * $has_course_content        : Course has course content
 * $topics        : Topics Array
 * $quizzes        : Quizzes Array
 * $topic_progression_enabled    : (true/false)
 * $has_topics        : (true/false)
 * $topic_topics    : (array) topics topics
 *
 * @since 2.1.0
 *
 * @package LearnDash\Course
 */


/**
 * Display course status
 */
?>
<?php if ( $logged_in ) : ?>
    <span id="learndash_course_status">
		<b><?php printf( esc_attr_x( '%s Status:', 'Course Status Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></b> <?php echo $course_status; ?>
		<br/>
	</span>
    <br/>

	<?php
	/**
	 * Filter to add custom content after the Course Status section of the Course template output.
	 * @since 2.3
	 * See https://bitbucket.org/snippets/learndash/7oe9K for example use of this filter.
	 */
	echo apply_filters( 'ld_after_course_status_template_container', '', learndash_course_status_idx( $course_status ), $course_id, $user_id );
	?>

	<?php if ( ! empty( $course_certficate_link ) ) : ?>
        <div id="learndash_course_certificate" class="learndash_course_certificate">
            <a href='<?php echo esc_attr( $course_certficate_link ); ?>' class="btn-blue"
               target="_blank"><?php echo apply_filters( 'ld_certificate_link_label', esc_attr__( 'PRINT YOUR CERTIFICATE', 'learndash' ), $user_id, $post->ID ); ?></a>
        </div>
        <br/>
	<?php endif; ?>
<?php endif; ?>
    <div class="learndash_content"><?php echo $content; ?></div>

<?php if ( ! $has_access ) : ?>
	<?php echo learndash_payment_buttons( $post ); ?>
<?php endif; ?>
<?php if ( isset( $materials ) && ! empty( $materials ) ) : ?>
    <div id="learndash_course_materials" class="learndash_course_materials">
        <h4><?php printf( esc_attr_x( '%s Materials', 'Course Materials Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></h4>
        <p><?php echo $materials; ?></p>
    </div>
<?php endif; ?>

<?php if ( $has_course_content ) : ?>
	<?php
	$show_course_content = true;
	if ( ! $has_access ) :
		if ( $course_meta['sfwd-courses_course_disable_content_table'] == 'on' ) :
			$show_course_content = false;
		endif;
	endif;

	if ( $show_course_content ) :
		?>
        <div id="learndash_course_content" class="learndash_course_content">
            <!--<h4 id="learndash_course_content_title"><?php /*printf( esc_attr_x( '%s Content', 'Course Content Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); */
			?></h4>-->

			<?php
			/**
			 * Display topic list
			 */
			?>
			<?php if ( ! empty( $topics ) ) : ?>

				<?php if ( $has_topics ) : ?>
                <div class="expand_collapse">
                    <a href="#"
                       onClick='jQuery("#learndash_post_<?php echo $course_id; ?> .learndash_topic_dots").slideDown(); return false;'><?php esc_attr_e( 'Expand All', 'learndash' ); ?></a>
                    |
                    <a href="#"
                       onClick='jQuery("#learndash_post_<?php echo esc_attr( $course_id ); ?> .learndash_topic_dots").slideUp(); return false;'><?php esc_attr_e( 'Collapse All', 'learndash' ); ?></a>
                </div>
			<?php if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_topics_listing_main' ) ) { ?>
                <script>
                    jQuery(document).ready(function () {
                        jQuery("#learndash_post_<?php echo $course_id; ?> .learndash_topic_dots").slideDown()
                    })
                </script>
			<?php } ?>
			<?php endif; ?>

                <div id="learndash_topics" class="learndash_topics">

                    <div id="topic_heading">
                        <span><?php echo LearnDash_Custom_Label::get_label( 'topics' ) ?></span>
                        <span class="right"><?php esc_attr_e( 'Status', 'learndash' ); ?></span>
                    </div>

                    <div id="topics_list" class="topics_list">

						<?php foreach ( $topics as $topic ) :
							$access_from = \uncanny_pro_toolkit\UncannyDripTopicsByGroup::get_topic_access_from( $topic['post']->ID, get_current_user_id() );
							$is_not_available = false;
							if ( ! empty( $access_from ) && __( 'Available', 'uncanny-pro-toolkit' ) !== (string) $access_from ) {
								//$access_from                  = \uncanny_pro_toolkit\UncannyDripTopicsByGroup::adjust_for_timezone_difference( $access_from );
								$topic["topic_access_from"] = $access_from;

								$date_format      = get_option( 'date_format' );
								$time_format      = get_option( 'time_format' );
								$date             = \uncanny_pro_toolkit\UncannyDripTopicsByGroup::adjust_for_timezone_difference( $topic['topic_access_from'] );
								$date             = date_i18n( "$date_format $time_format", $date );
								$is_not_available = true;
							} else {
								$topic["topic_access_from"] = '';
							}

							?>
                            <div class='post-<?php echo esc_attr( $topic['post']->ID ); ?> <?php echo esc_attr( $topic['sample'] ); ?>'>

                                <div class="list-count">
									<?php echo $topic['sno']; ?>
                                </div>

                                <h4>
                                    <a class='<?php echo $is_not_available ? 'notavailable' : esc_attr( $topic['status'] ); ?>'
                                       href='<?php echo esc_attr( $topic['permalink'] ); ?>'><?php echo $topic['post']->post_title; ?></a>


									<?php
									/**
									 * Not available message for drip feeding topics
									 */
									?>
									<?php if ( ! empty( $topic['topic_access_from'] ) ) : ?>
										<?php /* ?>
									<small class="notavailable_message">
										<?php echo sprintf( esc_attr__( 'CCC Available on: %s ', 'learndash' ), learndash_adjust_date_time_display( $topic['topic_access_from'] ) ); ?>

									</small>
									<?php */ ?>
										<?php

										SFWD_LMS::get_template(
											'learndash_course_topic_not_available',
											array(
												'user_id'                 => $user_id,
												'course_id'               => learndash_get_course_id( $topic['post']->ID ),
												'topic_id'               => $topic['post']->ID,
												'topic_access_from_int'  => strtotime( $date ),
												'topic_access_from_date' => $date,
												'context'                 => 'course'
											), true
										);
										?>
									<?php endif; ?>


									<?php
									/**
									 * Topic Topics
									 */
									?>
									<?php $topics = @$topic_topics[ $topic['post']->ID ]; ?>

									<?php if ( ! empty( $topics ) ) : ?>
                                        <div id='learndash_topic_dots-<?php echo esc_attr( $topic['post']->ID ); ?>'
                                             class="learndash_topic_dots type-list">
                                            <ul>
												<?php $odd_class = ''; ?>
												<?php foreach ( $topics as $key => $topic ) : ?>
													<?php $odd_class = empty( $odd_class ) ? 'nth-of-type-odd' : ''; ?>
													<?php $completed_class = empty( $topic->completed ) ? 'topic-notcompleted' : 'topic-completed'; ?>
                                                    <li class='<?php echo esc_attr( $odd_class ); ?>'>
													<span class="topic_item">
														<a class='<?php echo esc_attr( $completed_class ); ?>'
                                                           href='<?php echo esc_attr( get_permalink( $topic->ID ) ); ?>'
                                                           title='<?php echo esc_attr( $topic->post_title ); ?>'>
															<span><?php echo $topic->post_title; ?></span>
														</a>
													</span>
                                                    </li>
												<?php endforeach; ?>
                                            </ul>
                                        </div>
									<?php endif; ?>

                                </h4>
                            </div>
						<?php endforeach; ?>

                    </div>
                </div>
				<?php
				if ( isset( $topics_results['pager'] ) ) {
					echo '<div class="navigation" style="clear:both">';
					if ( $topics_results['pager']['paged'] > 1 ) {
						if ( intval( $topics_results['pager']['paged'] ) == 2 ) {
							$prev_link = remove_query_arg( 'ld-topic-page' );
						} else {
							$prev_link = add_query_arg( 'ld-topic-page', intval( $topics_results['pager']['paged'] ) - 1 );
						}
						echo '<div class="alignleft"><a href="' . $prev_link . '">' . esc_attr__( '« Previous', 'learndash' ) . '</a></div>';
					}
					if ( $topics_results['pager']['paged'] < $topics_results['pager']['total_pages'] ) {
						echo '<div class="alignright"><a href="' . add_query_arg( 'ld-topic-page', intval( $topics_results['pager']['paged'] ) + 1 ) . '">' . esc_attr__( 'Next »', 'learndash' ) . '</a></div>';
					}
					echo '</div>';
					echo '<div style="clear:both"></div>';

				}
				?>

			<?php endif; ?>

			<?php
			/**
			 * Display quiz list
			 */
			?>
			<?php if ( ! empty( $quizzes ) ) : ?>
                <div id="learndash_quizzes" class="learndash_quizzes">
                    <div id="quiz_heading">
                        <span><?php echo LearnDash_Custom_Label::get_label( 'quizzes' ) ?></span><span
                                class="right"><?php esc_attr_e( 'Status', 'learndash' ); ?></span>
                    </div>
                    <div id="quiz_list" class=“quiz_list”>

						<?php foreach ( $quizzes as $quiz ) : ?>
                            <div id='post-<?php echo esc_attr( $quiz['post']->ID ); ?>'
                                 class='<?php echo esc_attr( $quiz['sample'] ); ?>'>
                                <div class="list-count"><?php echo $quiz['sno']; ?></div>
                                <h4>
                                    <a class='<?php echo esc_attr( $quiz['status'] ); ?>'
                                       href='<?php echo esc_attr( $quiz['permalink'] ); ?>'><?php echo $quiz['post']->post_title; ?></a>
                                </h4>
                            </div>
						<?php endforeach; ?>

                    </div>
                </div>
			<?php endif; ?>

        </div>
	<?php endif; ?>
<?php endif; ?>
