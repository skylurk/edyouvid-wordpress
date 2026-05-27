<?php
/**
 * Services : H5P and Storyline
 *
 * @package    Tin Canny Reporting for LearnDash
 * @subpackage TinCan Module
 * @author     Uncanny Owl
 * @since      1.0.0
 * @todo       descriptions
 */

namespace UCTINCAN;

use uncanny_learndash_reporting\Config;

if ( ! defined( 'UO_ABS_PATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

class Services {
	use Modules;

	private static $snc_upload_dir;

	public function __construct() {
		if ( ! is_admin() ) {
			add_action( 'wp', array( $this, 'activate_mark_complete_control' ) );
		}

		# Ajax (Storyline)
		add_action( 'wp_ajax_Check Storyline Completion', array( $this, 'ajax_check_slide_completion' ) );
		add_action( 'wp_ajax_Check Captivate Completion', array( $this, 'ajax_check_slide_completion' ) );
		add_action( 'wp_ajax_Check iSpring Completion', array( $this, 'ajax_check_slide_completion' ) );
		add_action( 'wp_ajax_Check ArticulateRise Completion', array( $this, 'ajax_check_slide_completion' ) );
		add_action( 'wp_ajax_Check ArticulateRise2017 Completion', array( $this, 'ajax_check_slide_completion' ) );

		add_action( 'wp_ajax_Check H5P Completion', array( $this, 'ajax_check_h5p_completion' ) );
		/* add Presenter360 tin can format */
		add_action( 'wp_ajax_Check Presenter360 Completion', array( $this, 'ajax_check_slide_completion' ) );
		/* END Presenter360 */
		/* add Lectora tin can format */
		add_action( 'wp_ajax_Check Lectora Completion', array( $this, 'ajax_check_slide_completion' ) );
		/* END Lectora */
		/* add Scorm tin can format */
		add_action( 'wp_ajax_Check Scorm Completion', array( $this, 'ajax_check_slide_completion' ) );
		add_action( 'wp_ajax_Check Tincan Completion', array( $this, 'ajax_check_slide_completion' ) );
		/* END Scorm */
		add_action( 'wp_ajax_uncanny-snc-mark-completed', array( $this, 'mark_content_completed' ) );
	}

	// wp
	public function activate_mark_complete_control() {
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		$post_option   = ( is_object( $post ) ) ? \UCTINCAN\Admin\Metabox::get_meta_values( $post->ID ) : array();
		$global_option = get_option( 'disable_mark_complete_for_tincan', 'yes' );

		if ( '' === $post_option || ! is_array( $post_option ) ) {
			$post_option = array();
		}

		// Post Option Default : Use Global Setting
		if ( ! isset( $post_option['restrict-mark-complete'] ) ) {
			$post_option['restrict-mark-complete'] = 'Use Global Setting';
		}

		$post_option['restrict-mark-complete'] = strtolower( $post_option['restrict-mark-complete'] );
		switch ( $post_option['restrict-mark-complete'] ) {
			case 'use global setting':
				if ( 'no' === $global_option ) {
					return;
				}
				break;

			case 'no':
				return;
		}

		// If "Capture Tin Can and SCORM data" is disabled
		$is_capture_enabled = get_option( 'show_tincan_reporting_tables', 'yes' );
		if ( 'no' === $is_capture_enabled ) {
			return;
		}

		// trait: Modules
		if ( ! $this->prepare_modules() ) {
			return;
		}

		// Check if the Tin Canny Gutenberg Block is being used
		// First create variable where we're going to save the result
		$tincanny_gutenberg_block_is_being_used = false;

		// Check if Gutenberg exists (just in case the user
		// is using WP < 5.0 )
		if ( function_exists( 'has_blocks' ) && function_exists( 'parse_blocks' ) ) {
			// Check if the post content has blocks
			if ( has_blocks( $post->post_content ) ) {
				// Get all the blocks
				$blocks                                 = parse_blocks( $post->post_content );
				$tincanny_gutenberg_block_is_being_used = self::get_all_inner_block( $blocks, 'tincanny/content' );
				if ( empty( $tincanny_gutenberg_block_is_being_used ) ) {
					$tincanny_gutenberg_block_is_being_used = false;
				}
				if ( ! $tincanny_gutenberg_block_is_being_used ) {
					$tincanny_gutenberg_block_is_being_used = self::get_all_inner_block( $blocks, 'bbapp/h5p' );
					if ( empty( $tincanny_gutenberg_block_is_being_used ) ) {
						$tincanny_gutenberg_block_is_being_used = false;
					}
				}
			}
		}

		global $post;

		if ( is_a( $post, 'WP_Post' ) &&
		(
		has_shortcode( $post->post_content, 'h5p' ) ||
		has_shortcode( $post->post_content, 'vc_snc' ) ||
		$tincanny_gutenberg_block_is_being_used
		)
		) {

			# Extract H5P, Storyline Shortcode
			$this->extract_tincanny_content( $post->post_content );

			# Script
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			# Set js var for completion
			add_action( 'wp_footer', array( $this, 'print_js_completion_vars' ), 100 );

			# Set custom label for mark complete button
			$custom_label = get_option( 'label_mark_complete_for_tincan', '' );
			if ( ! empty( $custom_label ) ) {
				add_filter(
					'learndash_get_label',
					function( $label, $key ) {
						if ( 'button_mark_complete' === $key ) {
							$custom_label = get_option( 'label_mark_complete_for_tincan', '' );
							return $custom_label;
						}

						return $label;
					},
					10,
					2
				);
			}
		}

	}

	public static function get_all_inner_block( $blocks, $block_code ) {
		$block_is_on_page = array();
		foreach ( $blocks as $block ) {
			if ( $block_code === $block['blockName'] ) {
				$block_is_on_page[] = $block;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_block_is_on_page = self::get_all_inner_block( $block['innerBlocks'], $block_code );
				if ( ! empty( $inner_block_is_on_page ) ) {
					$block_is_on_page = array_merge( $block_is_on_page, $inner_block_is_on_page );
				}
			}
		}

		return $block_is_on_page;
	}

	private function get_snc_upload_dir() {
		if ( ! self::$snc_upload_dir ) {
			$wp_upload_dir        = wp_upload_dir();
			self::$snc_upload_dir = $wp_upload_dir['basedir'] . '/' . SnC_UPLOAD_DIR_NAME;
		}

		return self::$snc_upload_dir;
	}

	// Extract H5P and Storyline/Articulate Shortcodes
	// the_content
	public function extract_tincanny_content( $content ) {
		// Check if the page has Tin Canny content
		// Create variable where we're going to save the result
		$has_tincanny_content = false;

		// Check for the shortcode
		// Create variable where we're going to save the result
		$tincanny_shortcode_is_being_used = false;
		preg_match_all( self::$PATTERN_SHORTCODE_ID, $content, $match_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$tincanny_shortcode_is_being_used = ! empty( $match_id[4] );

		// Check if the Tin Canny Gutenberg Block is being used
		// Create variable where we're going to save the result
		$tincanny_gutenberg_block_is_being_used = false;

		// Check if Gutenberg exists (just in case the user
		// is using WP < 5.0 )
		$tincanny_blocks = array();
		$bbapph5p_blocks = array();

		if ( function_exists( 'has_blocks' ) && function_exists( 'parse_blocks' ) ) {
			// Check if the post content has blocks
			if ( has_blocks( $content ) ) {
				// Get all the blocks
				$blocks          = parse_blocks( $content );
				$tincanny_blocks = self::get_all_inner_block( $blocks, 'tincanny/content' );
				if ( ! empty( $tincanny_blocks ) ) {
					// Change value of variable
					$tincanny_gutenberg_block_is_being_used = true;

					// Add this block to the list of Tin Canny blocks
					$tincanny_blocks = $tincanny_blocks;
				}

				$bbapph5p_blocks = self::get_all_inner_block( $blocks, 'bbapp/h5p' );
				if ( ! empty( $bbapph5p_blocks ) ) {
					// Change value of variable
					$tincanny_gutenberg_block_is_being_used = true;

					// Add this block to the list of Tin Canny blocks
					$bbapph5p_blocks = $bbapph5p_blocks;
				}
			}
		}

		// Check if it has either the shortcode or the gutenberg block
		// Otherwise do nothing
		$has_tincanny_content = $tincanny_shortcode_is_being_used || $tincanny_gutenberg_block_is_being_used;

		if ( $has_tincanny_content ) {
			// Shortcode matches
			if ( $tincanny_shortcode_is_being_used ) {
				foreach ( $match_id[4] as $key => $content_id ) {
					if ( empty( $content_id ) || ! is_numeric( $content_id ) ) {
						continue;
					}

					switch ( $match_id[1][ $key ] ) {
						case 'h5p':
							if ( $this->available['H5P'] ) {
								$this->set_h5p_module_info( $content_id );
							}

							break;

						case 'vc_snc':
							if ( $this->available['SnC'] ) {
								$this->set_slide_module_info( $content_id );
							}

							break;
					}
				}
			}

			// Blocks
			foreach ( $tincanny_blocks as $block ) {
				// Check if it has the content ID parameter
				if ( isset( $block['attrs']['contentId'] ) ) {
					// Get content ID
					$content_id = $block['attrs']['contentId'];

					// Set slide info
					$this->set_slide_module_info( $content_id );
				}
			}

			// H5P Blocks
			foreach ( $bbapph5p_blocks as $block ) {
				// Check if it has the content ID parameter
				if ( isset( $block['attrs']['h5p_id'] ) ) {
					// Get content ID
					$content_id = $block['attrs']['h5p_id'];

					// Set slide info
					$this->set_h5p_module_info( $content_id );
				}
			}

			$this->set_completion();
		}
	}

	private function set_h5p_module_info( $id ) {
		global $wpdb;

		$content_table_name = $wpdb->prefix . self::$TABLE_H5P_CONTENTS; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$lib_table_name     = $wpdb->prefix . self::$TABLE_H5P_LIBRARY; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// phpcs:disable WordPress.DB.PreparedSQL
		$query        = $wpdb->prepare(
			"SELECT library.`name` FROM {$content_table_name} contents
			INNER JOIN {$lib_table_name} library ON contents.`library_id` = library.`id`
			WHERE contents.`id` = %s",
			$id
		);
		$library_name = $wpdb->get_var( $query );
		// phpcs:enable WordPress.DB.PreparedSQL

		$this->module_info['H5P']['contents'][ $id ] = $id;
	}

	// todo Check Availability into snc classes
	private function set_slide_module_info( $item_id ) {
		$module = \TINCANNYSNC\Module::get_module( $item_id );

		if ( $module && $module->is_available() ) {
			$module_type = $module->get_type();
			$module_type = 'AR2017' === $module_type ? 'ArticulateRise2017' : $module_type;
			$this->module_info[ $module_type ]['contents'][ $item_id ] = $item_id;
		}
	}

	private function set_completion() {
		global $post;
		$user_id   = get_current_user_id();
		$course_id = false;

		if ( ! $user_id ) {
			return;
		}

		if ( function_exists( 'learndash_get_course_id' ) ) {
			$course_id = learndash_get_course_id( $post->ID );
		}

		$mark_complete_settings = '';
		$global_option          = get_option( 'disable_mark_complete_for_tincan', 'yes' );

		$has_complete_setting = 0;
		$post_meta            = ( $post->ID ) ? \UCTINCAN\Admin\Metabox::get_meta_values( $post->ID ) : array();

		if ( ! empty( $post_meta['completion-condition'] ) ) {
			$has_complete_setting = 1;
		}

		if ( '' === $post_meta ) {
			$post_meta = array();
		}
		// Post Option Default : Use Global Setting
		if ( ! is_array( $post_meta ) || ! isset( $post_meta['restrict-mark-complete'] ) ) {
			$post_meta['restrict-mark-complete'] = 'Use Global Setting';
		}

		$post_meta['restrict-mark-complete'] = strtolower( $post_meta['restrict-mark-complete'] );
		switch ( $post_meta['restrict-mark-complete'] ) {
			case 'use global setting':
				$mark_complete_settings = $global_option;
				break;
			default:
				$mark_complete_settings = $post_meta['restrict-mark-complete'];
				break;
		}
		$is_already_completed = true;
		if ( 'remove' === $mark_complete_settings || 'autoadvance' === $mark_complete_settings ) {
			if ( 'sfwd-lessons' === $post->post_type ) {
				$is_already_completed = learndash_is_lesson_complete( $user_id, $post->ID, $course_id );
			} elseif ( 'sfwd-topic' === $post->post_type ) {
				$is_already_completed = learndash_is_topic_complete( $user_id, $post->ID );
			}
		}
		$database = new Database\Completion();

		// H5P Completion
		foreach ( $this->module_info['H5P']['contents'] as $id ) {
			if ( $database->get_H5P_completion( $id, $course_id, $post->ID, $user_id ) ) {
				if ( $is_already_completed ) {
					$this->module_info['H5P']['complete'][ $id ] = true;
				}
			}
		}

		$module_types = array(
			'Storyline',
			'Captivate',
			'Captivate2017',
			'iSpring',
			'ArticulateRise',
			'ArticulateRise2017',
			/* add Presenter360 tin can format */
			'Presenter360',
			/* END Presenter360 */
			/* add Lectora tin can format */
			'Lectora',
			/* END Lectora */
			'Scorm',
			'Tincan',
		);

		foreach ( $module_types as $type ) {
			foreach ( $this->module_info[ $type ]['contents'] as $id ) {
				$module = \TINCANNYSNC\Module::get_module( $id );

				if ( $module->is_available() ) {
					$module_url = $module->get_url();

					if ( $database->get_completion_by_URL( $module_url, $course_id, $post->ID ) ) {
						if ( $is_already_completed ) {
							$this->module_info[ $type ]['complete'][ $id ] = true;
						}
					}
				}
			}
		}
	}

	# TODO : Course ID
	public function ajax_check_slide_completion() {
		$url = ultc_get_filter_var( 'URL', false, INPUT_POST );
		if ( ! $url ) {
			wp_die();
		}
		$completion = $this->check_slide_completion( $url );
		if ( $completion ) {
			echo $completion; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		wp_die();
	}

	public function check_slide_completion( $url ) {
		$database  = new Database\Completion();
		$course_id = false;
		parse_str( $url, $decoded );
		$global_option = get_option( 'disable_mark_complete_for_tincan', 'yes' );

		$url_converted = remove_query_arg(
			array(
				'endpoint',
				'auth',
				'activity_id',
				'actor',
				'client',
				'tincan',
				'course_id',
				'base_url',
				'nonce',
			),
			$url
		);

		if ( isset( $decoded['course_id'] ) && 0 !== $decoded['course_id'] ) {
			$course_id = $decoded['course_id'];
			$lesson_id = $this->get_lesson_id_from_url( $url );
		} else {
			$lesson_id = $this->get_lesson_id_from_url( $url );
			if ( ! empty( $lesson_id ) ) {
				if ( function_exists( 'learndash_get_course_id' ) ) {
					$course_id = learndash_get_course_id( $lesson_id );
				}
			}
		}

		if ( $database->get_completion_by_URL( $url_converted, $course_id, $lesson_id ) ) {
			$matches   = $this->get_slide_id_from_url( $url_converted );
			$post      = get_post( $lesson_id );
			$post_meta = ( $post->ID ) ? \UCTINCAN\Admin\Metabox::get_meta_values( $post->ID ) : array();
			if ( '' === $post_meta ) {
				$post_meta = array();
			}
			// Post Option Default : Use Global Setting
			if ( ! is_array( $post_meta ) || ! isset( $post_meta['restrict-mark-complete'] ) ) {
				$post_meta['restrict-mark-complete'] = 'Use Global Setting';
			}

			switch ( $post_meta['restrict-mark-complete'] ) {
				case 'Use Global Setting':
					$mark_complete_settings = $global_option;
					break;
				default:
					$mark_complete_settings = $post_meta['restrict-mark-complete'];
					break;
			}
			$is_already_completed = false;

			// Check if its a single page course from Toolkit Pro.
			// If it is true then replace post with course lesson
			if ( class_exists( '\uncanny_pro_toolkit\OnePageCourseStep' ) ) {
				$uncanny_active_classes = get_option( 'uncanny_toolkit_active_classes', array() );
				if ( ! empty( $uncanny_active_classes ) && is_array( $uncanny_active_classes ) && key_exists( 'uncanny_pro_toolkit\OnePageCourseStep', $uncanny_active_classes ) ) {
					$single_page_tutorial = get_post_meta( $post->ID, 'uo_single_page_course', true );
					if ( $single_page_tutorial ) {
						$course_steps = new \LDLMS_Course_Steps( $post->ID );
						$course_steps->load_steps();
						if ( 1 === $course_steps->get_steps_count() ) {
							$lessons     = $course_steps->get_steps( 't' );
							$lesson_id   = $lessons['sfwd-lessons'][0];
							$course_post = $post;
							$post        = get_post( $lesson_id );
						}
					}
				}
			}
			if ( 'remove' === $mark_complete_settings ) {
				$current_user = wp_get_current_user();
				$user_id      = $current_user->ID;
				if ( 'sfwd-lessons' === $post->post_type ) {
					$is_already_completed = learndash_is_lesson_complete( $user_id, $post->ID, $course_id );
				} elseif ( 'sfwd-topic' === $post->post_type ) {
					$is_already_completed = learndash_is_topic_complete( $user_id, $post->ID );
				}
				if ( ! $is_already_completed ) {
					learndash_process_mark_complete( $user_id, $post->ID, false, $course_id, true );
				}
			}
			if ( 'autoadvance' === $mark_complete_settings ) {
				learndash_process_mark_complete( $user_id, $post->ID, false, $course_id, true );
				$previous_link = ultc_get_filter_var( 'lesson_link', '', INPUT_POST );
				$url           = learndash_next_post_link( $previous_link, true, $post );
				if ( $url === $previous_link ) {
					if ( 'sfwd-topic' === $post->post_type ) {
						if ( \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) {
							$course_id = learndash_get_course_id( $post->ID );
							$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
						} else {
							$lesson_id = learndash_get_setting( $post, 'lesson' );
						}
						$url = get_permalink( $lesson_id );
					} else {
						$course_id = learndash_get_course_id( $post );
						$url       = learndash_next_global_quiz( true, null, $course_id );
					}
				}

				if ( ! empty( $url ) ) {
					return wp_json_encode(
						array(
							'redirect_to'        => $url,
							'content_id'         => $matches,
							'completion_matched' => true,
						)
					);
				}
			}

			return $matches[1];
		}

		return false;
	}

	public function ajax_check_h5p_completion() {
		echo $this->check_h5p_completion( ultc_get_filter_var( 'lesson_id', 0, INPUT_POST ) );
		wp_die();
	}

	public function check_h5p_completion( $lesson_id ) {
		$database = new Database\Completion();

		return $database->get_completion_by_lesson_meta( $lesson_id );
	}

	public function check_h5p_completion_( $snc_id, $lesson_id, $user_id = false ) {
		$database = new Database\Completion();

		return $database->get_H5P_completion( $snc_id, false, $lesson_id, $user_id );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'tincanny-hooks', Config::get_src_assets_dist_js_url( 'hooks' ), array( 'jquery' ), UNCANNY_REPORTING_VERSION, false );
		wp_enqueue_script(
			'tincanny-modules',
			Config::get_src_assets_dist_js_url( 'modules' ),
			array(
				'jquery',
				'tincanny-hooks',
			),
			UNCANNY_REPORTING_VERSION,
			false
		);
	}

	public function print_js_completion_vars() {
		global $post;
		$course_id    = false;
		$lesson_id    = $post->ID;
		$current_link = get_permalink( $post->ID );
		if ( function_exists( 'learndash_get_course_id' ) ) {
			$course_id = learndash_get_course_id( $post->ID );
		}

		$mark_complete_settings = '';
		$global_option          = get_option( 'disable_mark_complete_for_tincan', 'yes' );

		$has_complete_setting = 0;
		$post_meta            = ( $post->ID ) ? \UCTINCAN\Admin\Metabox::get_meta_values( $post->ID ) : array();

		if ( ! empty( $post_meta['completion-condition'] ) ) {
			$has_complete_setting = 1;
		}

		if ( '' === $post_meta ) {
			$post_meta = array();
		}
		// Post Option Default : Use Global Setting
		if ( ! is_array( $post_meta ) || ! isset( $post_meta['restrict-mark-complete'] ) ) {
			$post_meta['restrict-mark-complete'] = 'Use Global Setting';
		}

		$post_meta['restrict-mark-complete'] = strtolower( $post_meta['restrict-mark-complete'] );
		switch ( $post_meta['restrict-mark-complete'] ) {
			case 'use global setting':
				$mark_complete_settings = $global_option;
				break;
			default:
				$mark_complete_settings = $post_meta['restrict-mark-complete'];
				break;
		}

		$method_mark_complete_for_tincan = get_option( 'method_mark_complete_for_tincan', 'new' );

		?>

		<script type="text/javascript">
			jQuery(document).ready(function () {
				<?php foreach ( $this->modules as $module ) : ?>

				moduleController.count['<?php echo esc_attr( $module ); ?>'] = <?php echo esc_attr( count( $this->module_info[ $module ]['contents'] ) ); ?>;
				moduleController.completion['<?php echo esc_attr( $module ); ?>'] = JSON.parse('<?php echo wp_json_encode( $this->module_info[ $module ]['complete'] ); ?>');

				<?php endforeach; ?>
				moduleController.hasCompletionSetting = <?php echo esc_attr( $has_complete_setting ); ?>;
				moduleController.markCompleteSettings = '<?php echo esc_attr( $mark_complete_settings ); ?>';
				moduleController.markCompleteLesson = '<?php echo esc_attr( $lesson_id ); ?>';
				moduleController.markCompleteCourse = '<?php echo esc_attr( $course_id ); ?>';
				moduleController.currentLessonLink = '<?php echo esc_attr( $current_link ); ?>';
				moduleController.methodMarkCompleteForTincan = '<?php echo esc_attr( $method_mark_complete_for_tincan ); ?>';
				moduleController.ready();
			});

			var wp_ajax_url = '<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>';
		</script>
		<?php
	}

	/**
	 * Mark content as completed auto.
	 */
	public function mark_content_completed() {

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			wp_die();
		}

		// Validate we have the required data.
		if ( ! ultc_filter_has_var( 'course_id', INPUT_POST ) || ! ultc_filter_has_var( 'lesson_id', INPUT_POST ) || ! ultc_filter_has_var( 'contentType', INPUT_POST ) ) {
			wp_die();
		}

		// If "Capture Tin Can and SCORM data" is disabled
		$is_capture_enabled = get_option( 'show_tincan_reporting_tables', 'yes' );
		if ( 'no' === $is_capture_enabled ) {
			wp_die();
		}

		global $post;
		$post_id              = ultc_filter_input( 'lesson_id', INPUT_POST );
		$course_id            = ultc_filter_input( 'course_id', INPUT_POST );
		$post                 = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$is_already_completed = false;
		if ( 'sfwd-lessons' === $post->post_type ) {
			$is_already_completed = learndash_is_lesson_complete( $user_id, $post_id, $course_id );
		} elseif ( 'sfwd-topic' === $post->post_type ) {
			$is_already_completed = learndash_is_topic_complete( $user_id, $post_id );
		}

		if ( ! $is_already_completed ) {

			learndash_process_mark_complete( $user_id, $post_id, false, $course_id, true );

			if ( 'autoadvance' === ultc_get_filter_var( 'setting_option', '', INPUT_POST ) ) {
				$previous_link = ultc_get_filter_var( 'lesson_link', '', INPUT_POST );
				$url           = learndash_next_post_link( $previous_link, true, $post );

				/*
				 * There is a bug where the learndash_next_post_link() returns the last post type in url of it type
				 * ex. if the current post is the last topic of a lesson then it will return the last topic url
				 * of the lesson
				 * AND
				 * if the current post is the last lesson of the current course then it will return the last lesson url
				 * of the course
				 */
				if ( $url === $previous_link ) {
					// the bug occured... let redirect to the next course if its a lesson or lesson if its a topic
					if ( 'sfwd-topic' === $post->post_type ) {
						if ( \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) {
							$course_id = learndash_get_course_id( $post->ID );
							$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
						} else {
							$lesson_id = learndash_get_setting( $post, 'lesson' );
						}
						$url = get_permalink( $lesson_id );
					} else {
						$course_id = learndash_get_course_id( $post );
						// This function will actually redirect to the course level quiz if there is one or the course if not
						$url = learndash_next_global_quiz( true, null, $course_id );
					}
				}

				if ( ! empty( $url ) ) {
					echo wp_json_encode( array( 'redirect_to' => $url ) );
				}
			}
		}
		die();
	}
}
