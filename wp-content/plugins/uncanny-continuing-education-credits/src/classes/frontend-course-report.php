<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class FrontendCourseReport
 *
 * @package uncanny_ceu
 */
class FrontendCourseReport {

	/**
	 * Check if we have an enhanced report on page.
	 *
	 * @var bool
	 */
	private $has_enhanced_report = false;

	/**
	 * Enhanced shortcode attributes
	 *
	 * @var array
	 */
	private $enhanced_attrs = array();

	/**
	 * Check if we have an unenhanced report on page.
	 *
	 * @var bool
	 */
	private $has_unenhanced_report = false;

	/**
	 * Reference to the CourseReport class
	 *
	 * @var CourseReport
	 */
	protected $course_report = null;

	/**
	 * FrontendCourseReport constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'wp_hooks' ) );
	}

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function wp_hooks() {

		add_shortcode( 'uo_ceu_report', array( $this, 'uo_ceu_report_shortcode' ) );
		add_action( 'wp', array( $this, 'load_custom_wp_style' ) );

	}

	/**
	 * Load custom WP style
	 *
	 * @return void
	 */
	public function load_custom_wp_style() {

		$queried_object = get_queried_object();
		if ( ! $queried_object instanceof \WP_Post ) {
			return;
		}

		// Check for enhanced shortcodes
		$this->check_for_enhanced_credit_report_shortcodes( $queried_object->post_content );

		// Check for enhanced gutenburg blocks
		$this->check_for_enhanced_credit_report_blocks( $queried_object->post_content );

		if ( $this->has_unenhanced_report ) {
			// DataTables
			wp_enqueue_script( 'ucec-datatables', Utilities::get_vendor( 'datatables/datatables.min.js' ), array( 'jquery' ), Utilities::get_version(), true );
			wp_enqueue_style( 'ucec-datatables', Utilities::get_vendor( 'datatables/datatables.min.css' ), array(), Utilities::get_version() );

			// Global assets
			wp_enqueue_script( 'ucec', Utilities::get_asset( 'frontend', 'bundle.min.js' ), array( 'jquery', 'ucec-datatables' ), Utilities::get_version(), true );
			wp_enqueue_style( 'ucec', Utilities::get_asset( 'frontend', 'bundle.min.css' ), array( 'ucec-datatables' ), Utilities::get_version() );
		}

		if ( $this->has_enhanced_report ) {
			// Register enhanced scripts
			$this->course_report_instance()->register_scripts();
			// Maybe filter the mode.
			$mode = isset( $this->enhanced_attrs['mode'] ) ? $this->enhanced_attrs['mode'] : null;
			if ( ! empty( $mode ) || 'global' !== $mode ) {
				$this->course_report_instance()->set_mode( $mode );
			}
			// Maybe filter the columns.
			$columns = isset( $this->enhanced_attrs['columns'] ) ? $this->enhanced_attrs['columns'] : null;
			if ( ! is_null( $columns ) ) {
				$this->course_report_instance()->set_columns( $columns );
			}

			// Set buttons availablity - Add, Edit, Delete.
			$buttons = array(
				'add'    => 'off',
				'edit'   => 'off',
				'delete' => 'off',
			);
			foreach ( $buttons as $key => $value ) {
				if ( isset( $this->enhanced_attrs[ $key ] ) && 'on' === $this->enhanced_attrs[ $key ] ) {
					$buttons[ $key ] = 'on';
				}
			}
			$this->course_report_instance()->set_buttons( $buttons );

			// Enqueue enhanced scripts
			$this->course_report_instance()->enqueue_scripts( 'frontend' );
		}
	}

	/**
	 * [uo_ceu_report] shortcode
	 *
	 * @param $atts
	 *
	 * @return string - HTML output
	 */
	public function uo_ceu_report_shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'enhanced' => 'off', // Are we going to show the full report or the search form only?
				'columns'  => '', // comma separated with || as separator for label Example: 'user||User,course||Title,date||Date,ceus_earned||CEUs,total||Total'
				'mode'     => null, //'meta', 'json', 'tables', 'latest', 'legacy'
				'add'      => 'off', // Allow adding new CEUs
				'edit'     => 'off', // Allow editing CEUs
				'delete'   => 'off', // Allow deleting CEUs
			),
			$atts,
			'uo_ceu_report'
		);

		// Check if we're going to show the enhanced report
		if ( 'on' === $atts['enhanced'] ) {

			// Add check that user is logged in.
			if ( ! is_user_logged_in() ) {
				return __( 'You must be logged in to view this content.', 'uncanny-ceu' );
			}

			// Validate Admin or Group Leader.
			if ( ! current_user_can( 'manage_options' ) && ! learndash_is_group_leader_user( get_current_user_id() ) ) {
				return __( 'You must be a Group Leader or Administrator to use this report.', 'uncanny-ceu' );
			}

			// TODO - @CURT - REVIEW - Multiple instances of shortcode

			// Reference to the CourseReport class for templates.
			$course_report = $this->course_report_instance();

			// Output the enhanced report.
			ob_start();
			include Utilities::get_template( 'frontend-enhanced-course-report.php' );
			return ob_get_clean();
		}

		// Not enhanced - show original user search form results.

		// Default values of variables that are going to be changed later
		$users           = array();
		$ceu_completions = array();
		$has_results     = false;

		// Get search terms
		$search_terms = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : '';

		// Check if it has search terms
		$has_search_terms = ! empty( $search_terms );

		// If it does then do the search
		if ( $has_search_terms ) {
			// Get global variable $wpdb
			global $wpdb;

			// Sanitize user search
			$user_search = sanitize_text_field( $_REQUEST['search'] );

			// Define query
			$query = "
				SELECT DISTINCT user.ID, user.user_email 
				FROM $wpdb->users user
				JOIN $wpdb->usermeta meta1 ON user.ID = meta1.user_id 
				JOIN $wpdb->usermeta meta2 ON user.ID = meta2.user_id 
				WHERE meta1.meta_key LIKE 'ceu_earned_%'
				AND meta2.meta_key LIKE '%_name'
				AND ( 
				user.display_name like '%$user_search%' || 
				user.user_email like '%$user_search%' || 
				user.user_nicename like '%$user_search%' || 
				user.user_login like '%$user_search%' ||
				meta2.meta_value LIKE '%$user_search%'
			)";

			// Get results
			$users = $wpdb->get_results( $query );

			// Check if it has results
			$has_results = $users != false;

			// Iterate each user
			foreach ( $users as $user ) {
				// Get user meta
				$user_meta = get_user_meta( $user->ID );

				// Get user data
				$user_id         = $user->ID;
				$user_first_name = $user_meta['first_name'][0];
				$user_last_name  = $user_meta['last_name'][0];
				$user_email      = $user->user_email;

				// Set default number of CEUs
				$total_ceus = 0;

				// Iterate each meta to find CEU information
				foreach ( $user_meta as $meta_key => $meta_value ) {
					// Check if it's a CEU's meta
					if ( strpos( $meta_key, 'ceu_' ) !== false ) {
						// Get key
						$key        = explode( '_', $meta_key );
						$collection = array( 'title', 'date', 'course', 'earned' );

						if ( ! in_array( $key[1], $collection ) ) {
							continue;
						}

						$unique_key = $key[2] . '_' . $key[3]; //unique identifier for ceu completion...  {timestamp}_{course ID}
						$value_key  = $key[0] . '_' . $key[1]; // name of data stored... ceu_title  ceu_date  ceu_course  ceu_earned
						$meta_date  = (int) $key[2];

						if ( 'ceu_earned' === $value_key ) {
							$total_ceus += (float) $meta_value[0];
						}

						if ( ! isset( $ceu_completions[ $unique_key ] ) ) {
							$ceu_completions[ $unique_key ]               = array();
							$ceu_completions[ $unique_key ]['user_id']    = $user_id;
							$ceu_completions[ $unique_key ]['first_name'] = $user_first_name;
							$ceu_completions[ $unique_key ]['last_name']  = $user_last_name;
							$ceu_completions[ $unique_key ]['email']      = $user_email;
							$ceu_completions[ $unique_key ]['total_ceus'] = 0;
						}

						$ceu_completions[ $unique_key ][ $value_key ] = $meta_value[0];

						if ( 'ceu_earned' === $value_key ) {
							$ceu_completions[ $unique_key ]['total_ceus'] += $total_ceus;
						}
					}
				}
			}
		}

		// Start output
		ob_start();

		// Include template
		include Utilities::get_template( 'frontend-course-report.php' );

		// Return output
		return ob_get_clean();
	}

	/**
	 * Check if any shortcodes are enhanced.
	 *
	 * @param $content
	 *
	 * @return void
	 */
	private function check_for_enhanced_credit_report_shortcodes( $content ) {

		if ( ! has_shortcode( $content, 'uo_ceu_report' ) ) {
			return;
		}

		// Regular expression to match the shortcode and its attributes
		$pattern = get_shortcode_regex( array( 'uo_ceu_report' ) );
		if ( preg_match_all( '/' . $pattern . '/s', $content, $matches ) ) {
			foreach ( $matches[0] as $shortcode_match ) {
				 // Extract the attributes from the matched shortcode
				 $attrs_string = $matches[3][0];
				 $attrs        = shortcode_parse_atts( $attrs_string );
				if ( $attrs && isset( $attrs['enhanced'] ) && $attrs['enhanced'] === 'on' ) {
					$this->has_enhanced_report = true;
					$this->enhanced_attrs      = $attrs;
				} else {
					$this->has_unenhanced_report = true;
				}
			}
		}
	}

	/**
	 * Check if any blocks are enhanced.
	 *
	 * @param $content
	 *
	 * @return void
	 */
	private function check_for_enhanced_credit_report_blocks( $content ) {

		// We already know need to load JS for both report types.
		if ( $this->has_enhanced_report && $this->has_unenhanced_report ) {
			return;
		}

		if ( ! function_exists( 'has_blocks' ) || ! function_exists( 'parse_blocks' ) ) {
			return;
		}

		if ( ! has_blocks( $content ) ) {
			return;
		}

		$blocks = parse_blocks( $content );
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] == 'uncanny-ceu/uo-ceu-course-report' ) {
				if ( isset( $block['attrs']['enhanced'] ) && $block['attrs']['enhanced'] === 'on' ) {
					$this->has_enhanced_report = true;
					$this->enhanced_attrs      = $block['attrs'];
				} else {
					$this->has_unenhanced_report = true;
				}
			}
		}
	}

	/**
	 * Get the CourseReport instance
	 *
	 * @return CourseReport
	 */
	public function course_report_instance() {
		if ( null === $this->course_report ) {
			$this->course_report = Utilities::get_class_instance( 'CourseReport' );
		}
		return $this->course_report;
	}


}
