<?php

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Lesson_Group_Drip_Settings' ) ) ) {

	/**
	 * Class LearnDash_Settings_Metabox_Lesson_Group_Drip_Settings
	 */
	class LearnDash_Settings_Metabox_Lesson_Group_Drip_Settings extends \LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-lessons';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-lesson-group-drip-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
			// translators: placeholder: Lesson.
				esc_html_x( '%s Group Drip Settings', 'placeholder: Lesson', 'learndash' ),
				learndash_get_custom_label( 'lesson' )
			);

			$this->settings_section_description = sprintf(
			// translators: placeholder: lessons.
				esc_html_x( 'Controls the timing and way %s can be accessed.', 'placeholder: lessons', 'learndash' ),
				learndash_get_custom_label_lower( 'lessons' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array(
				$this,
				'filter_saved_fields'
			), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'drip_visible_after_specific_date' => 'drip_visible_after_specific_date',
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( true === $this->settings_values_loaded ) {

			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			global $post;
			$groups = get_posts( [
				'post_type'      => 'groups',
				'posts_per_page' => 999,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			] );

			// If any group is not exists, this option will be disabled
			if ( ! $groups ) {
				return '';
			}


			if ( ! isset( $_GET['course_id'] ) ) {

				$lesson_courses = learndash_get_courses_for_step( $post->ID );
				if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' )
				     && (
					     ( isset( $lesson_courses['primary'] ) && ! empty( $lesson_courses['primary'] ) )
					     || ( isset( $lesson_courses['primary'] ) && ! empty( $lesson_courses['secondary'] ) )
				     )
				) {
					echo sprintf(
						_x( 'Please select a %s to manage group drip dates.zz', 'Course  Label', 'uncanny-pro-toolkit' ),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					echo '<br />';
					echo $this->get_course_switch_html( 0 );
				} else {
					$lessons_course_id = learndash_get_course_id( $post );

					if ( ! empty( $lessons_course_id ) ) {
						$item_url = get_edit_post_link( $post->ID );
						$item_url = add_query_arg( 'course_id', $lessons_course_id, $item_url );
						$item_url = add_query_arg( 'currentTab', 'sfwd-lessons-settings', $item_url );

						echo "<a href='$item_url'>";
						echo esc_attr__( 'Please click here to set group drip dates.', 'uncanny-pro-toolkit' );
						echo '</a>';
					} else {
						echo sprintf(
							_x( 'Drip dates can only be added if the %s is associated with a %s.', 'Course  Label', 'uncanny-pro-toolkit' ),
							LearnDash_Custom_Label::get_label( 'lesson' ),
							LearnDash_Custom_Label::get_label( 'course' )
						);
						echo '<br />';
						echo sprintf(
							_x( 'This %s has no %s associations.', 'Course  Label', 'uncanny-pro-toolkit' ),
							LearnDash_Custom_Label::get_label( 'lesson' ),
							LearnDash_Custom_Label::get_label( 'course' )
						);

					}

				}


				return '';
			}

			$note   = sprintf(
				_x( 'Note: A drip date set for a %1$s/Group persists across all %2$s. You cannot set different drip dates for the same %1$s/Group in different %2$s.', '1 Lesson and 2 Course label', 'uncanny-pro-toolkit' ),
				LearnDash_Custom_Label::get_label( 'lesson' ),
				LearnDash_Custom_Label::get_label( 'courses' )
			);
			$reload = esc_attr__( 'Note: Page reloads after Save/Remove date', 'uncanny-pro-toolkit' );
			ob_start();

			?>
            <script>
                function uoAddSwitcher() {
                    jQuery('<div style="clear:both;padding: 15px 0;"><?php echo esc_html( $note ); ?></div>').insertBefore("#uo-ld-group-drip");
                    jQuery('<div style="clear:both; font-weight:bold; padding: 15px 0; text-align:right;"><?php echo $reload ?></div>').insertAfter("#uo-ld-group-drip");
                    jQuery('<div style="display:inline;"><?php echo $this->get_course_switch_html( $_GET["course_id"] ); ?></div>').appendTo("#uo-ld-group-drip_filter");
                    jQuery(".dataTables_filter label").addClass("pull-right");
                };
            </script>
            <table id="uo-ld-group-drip" class="striped celled table" style="width:100%">
                <thead>
                <tr>
                    <th><?php esc_attr_e( 'LearnDash group', 'uncanny-pro-toolkit' ); ?></th>
                    <th><?php esc_attr_e( 'Drip date', 'uncanny-pro-toolkit' ); ?></th>
                    <th><?php esc_attr_e( 'Drip date sort numeric', 'uncanny-pro-toolkit' ); ?></th>
                    <th><?php esc_attr_e( 'Drip date sort Y-M-D', 'uncanny-pro-toolkit' ); ?></th>
                    <th><?php esc_attr_e( 'Action', 'uncanny-pro-toolkit' ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php
				foreach ( $groups as $group ) {
					if ( $group && is_object( $group ) ) {

						$group_has_course_access = learndash_group_has_course( $group->ID, $_GET["course_id"] );
						if ( ! $group_has_course_access ) {
							continue;
						}
						$date = get_post_meta( $post->ID, stripslashes( '\uncanny_pro_toolkit\UncannyDripLessonsByGroup' ) . '-' . $group->ID, true );
						// Add tha ( date ) after group name on selection if exists
						$u_date = '';
						if ( $date ) {
							if ( is_array( $date ) ) {
								$date   = \uncanny_pro_toolkit\UncannyDripLessonsByGroup::reformat_date( $date );
								$u_date = $date;
								$date   = learndash_adjust_date_time_display( $date );
							}

							if ( \uncanny_pro_toolkit\UncannyDripLessonsByGroup::is_timestamp( $date ) ) {
								$u_date = $date;
								$date   = \uncanny_pro_toolkit\UncannyDripLessonsByGroup::adjust_for_timezone_difference( $date );
							}
						}
						$date = absint( $date );
						?>
                        <tr class="uo-ld-group-drip-row" data-group="<?php echo $group->ID ?>"
                            data-post="<?php echo $post->ID; ?>"
                            data-course="<?php echo absint( $_GET['course_id'] ); ?>"
							data-sort="<?php echo ! empty( $u_date ) ? $u_date : ''; ?>>"
                        >
                            <td>
                                <a href="<?php echo get_edit_post_link( $group->ID ) ?>"
                                   target="_blank"><?php echo $group->post_title ?></a>
                            </td>
                            <td>
								<?php

								?>

                                <div class="ld_date_selector">
                                    <span class="screen-reader-text"><?php esc_attr_e( 'Month', 'uncanny-pro-toolkit' ); ?></span>
                                    <select class="ultp-gutenberg-field-select ld_date_mm" name="month">
                                        <option value="">MM</option>
                                        <option value="1"
                                                data-text="Jan"<?php if ( ! empty( $date ) && 1 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '01-Jan', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="2"
                                                data-text="Feb"<?php if ( ! empty( $date ) && 2 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '02-Feb', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="3"
                                                data-text="Mar"<?php if ( ! empty( $date ) && 3 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '03-Mar', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="4"
                                                data-text="Apr"<?php if ( ! empty( $date ) && 4 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '04-Apr', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="5"
                                                data-text="May"<?php if ( ! empty( $date ) && 5 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '05-May', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="6"
                                                data-text="Jun"<?php if ( ! empty( $date ) && 6 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '06-Jun', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="7"
                                                data-text="Jul"<?php if ( ! empty( $date ) && 7 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '07-Jul', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="8"
                                                data-text="Aug"<?php if ( ! empty( $date ) && 8 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '08-Aug', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="9"
                                                data-text="Sep"<?php if ( ! empty( $date ) && 9 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '09-Sep', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="10"
                                                data-text="Oct"<?php if ( ! empty( $date ) && 10 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '10-Oct', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="11"
                                                data-text="Nov"<?php if ( ! empty( $date ) && 11 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '11-Nov', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                        <option value="12"
                                                data-text="Dec"<?php if ( ! empty( $date ) && 12 === absint( date( 'n', $date ) ) ) {
											echo ' selected="selected"';
										} ?>><?php esc_attr_e( '12-Dec', 'uncanny-pro-toolkit' ); ?>
                                        </option>
                                    </select>

                                    <span class="screen-reader-text"><?php esc_attr_e( 'Day', 'uncanny-pro-toolkit' ); ?></span>
                                    <input type="number" placeholder="DD" min="1" max="31"
                                           class="ultp-gutenberg-field-text ld_date_jj"
                                           name="day" value="<?php echo ! empty( $date ) ?  date( 'd', $date ) : '' ?>" size="2" maxlength="2"
                                           autocomplete="off">,

                                    <span class="screen-reader-text"><?php esc_attr_e( 'Year', 'uncanny-pro-toolkit' ); ?></span>
                                    <input type="number" placeholder="YYYY"
                                           min="<?php echo date( 'Y' ) - 3 ?>" max="<?php echo date( 'Y' ) + 15 ?>"
                                           class="ultp-gutenberg-field-text ld_date_aa"
                                           name="year" value="<?php echo ! empty( $date ) ? date( 'Y', $date ) : ''  ?>" size="4" maxlength="4"
                                           step="1" autocomplete="off">
                                    @
                                    <span class="screen-reader-text"><?php esc_attr_e( 'Hour', 'uncanny-pro-toolkit' ); ?></span>
                                    <input type="number" min="0" max="23" placeholder="HH"
                                           class="ultp-gutenberg-field-text ld_date_hh"
                                           name="hour" value="<?php echo ! empty( $date ) ?  date( 'H', $date ) : '' ?>" size="2" maxlength="2"
                                           autocomplete="off">:

                                    <span class="screen-reader-text"><?php esc_attr_e( 'Minute', 'uncanny-pro-toolkit' ); ?></span>
                                    <input type="number" min="0" max="59" placeholder="MN"
                                           class="ultp-gutenberg-field-text ld_date_mn"
                                           name="minute" value="<?php echo ! empty( $date ) ?  date( 'i', $date ) : '' ?>" size="2" maxlength="2"
                                           autocomplete="off">
                                </div>
                            </td>
                            <td><?php echo ! empty( $date ) ? $date : '' ?></td>
                            <td><?php echo ! empty( $date ) ? date( 'Y-m-d', $date ) : '' ?></td>
                            <td class="uo-ld-group-drip-row__actions">
                                <button type="button"
                                        class="uo-ld-group-drip__action uo-ld-group-drip__action--save ultp-gutenberg-btn">
									<?php esc_attr_e( 'Save date', 'uncanny-pro-toolkit' ); ?>
                                </button>

                                <button type="button"
                                        class="uo-ld-group-drip__action uo-ld-group-drip__action--remove ultp-gutenberg-btn">
									<?php esc_attr_e( 'Remove date', 'uncanny-pro-toolkit' ); ?>
                                </button>
                            </td>
                        </tr>
						<?php
					}
				}
				?>
                </tbody>
            </table>

			<?php
			echo ob_get_clean();
		}

		public function get_course_switch_html( $course_post_id ) {

			global $post;

			if ( 'yes' !== LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				$course_name = get_the_title( $course_post_id );

				return '<div style="display: inline-block;padding: 0 20px 0 20px;margin-top: 8px;">' .
				       sprintf(
					       esc_attr_x( '%s: %s', 'Course  Label', 'learndash' ),
					       LearnDash_Custom_Label::get_label( 'course' ),
					       $course_name
				       ) .
				       '</div>';
			}

			$cb_courses = learndash_get_courses_for_step( $post->ID );

			$item_url = get_edit_post_link( $post->ID );
			$html     = '';

			$html .= '<label style="padding-right:15px;">' . sprintf( esc_html_x( 'Switch %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'Course' ) ) . ': ';
			$html .= '<select onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">';
			$html .= '<option value="">' . sprintf( esc_html_x( 'Select a %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'Course' ) ) . '</option>';


			foreach ( $cb_courses as $course_key => $course_set ) {

				foreach ( $course_set as $course_id => $course_title ) {

					$item_url = add_query_arg( 'course_id', $course_id, $item_url );
					$item_url = add_query_arg( 'currentTab', 'sfwd-lessons-settings', $item_url );

					$selected = '';

					if ( $course_id == $course_post_id ) {
						$selected = ' selected="selected" ';
					}

					$html .= '<option ' . $selected . 'value="' . $item_url . '" >' . get_the_title( $course_id ) . '</option>';

				}
			}
			$html .= '</select></label>';

			return $html;
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @param array $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 *
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'lesson' ),
		function ( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Lesson_Group_Drip_Settings'] ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Lesson_Group_Drip_Settings'] = \LearnDash_Settings_Metabox_Lesson_Group_Drip_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		999,
		1
	);

}
