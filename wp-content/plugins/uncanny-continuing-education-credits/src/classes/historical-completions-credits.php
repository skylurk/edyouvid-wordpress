<?php

namespace uncanny_ceu;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class UncannyCeuCompletionStatus
 *
 * @package uncanny_ceu
 */
class HistoricalCompletionsCredits {

	static $root_path = 'ceu/v2';

	/**
	 * UncannyCeuCompletionStatus constructor.
	 */
	public function __construct() {

		if ( false === defined( 'LEARNDASH_VERSION' ) ) {
			add_filter( 'uo_ceu_tabs', array(
				$this, 'uo_ceu_update_tabs',
			), 10, 1 );

			return;
		}

		add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array(
			$this, 'load_custom_wp_admin_style',
		) );

	}


	/**
	 * Remove generate ceus tab when Learndash is not active.
	 *
	 * @param $tabs
	 *
	 * @return void
	 */
	public function uo_ceu_update_tabs( $tabs ) {
		unset( $tabs[2] );

		return $tabs;
	}

	/**
	 *
	 */
	public function admin_menu() {

		$page_title  = __( 'Generate Records', 'uncanny-ceu' );
		$menu_title  = __( 'Generate Records', 'uncanny-ceu' );
		$capability  = 'manage_options';
		$menu_slug   = 'uncanny-historical-completions-credits';
		$parent_slug = 'uncanny-ceu-report';
		$function    = array( $this, 'options_menu_page_output' );

		add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	/**
	 * @param $course_id INT
	 *
	 * @return array
	 */
	public static function populate_historical_credits( $course_id ) {

		$credit_designation_label_plural = Utilities::credit_designation_label( 'plural', __( 'credits', 'uncanny-ceu' ) );
		$credit_designation_label        = Utilities::credit_designation_label( 'singular', __( 'credit', 'uncanny-ceu' ) );

		// Validate permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'You do not have permission to generate %s.', 'uncanny-ceu' ), $credit_designation_label_plural ),
			];
		}

		$course_id = absint( $course_id );

		if ( ! $course_id ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'Please choose a %s.', 'uncanny-ceu' ), learndash_get_custom_label_lower( 'course' ) ),
			];
		}

		$course = get_post( $course_id );

		// Validate course
		if ( null === $course || 'sfwd-courses' !== $course->post_type ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'This %s no longer exists.', 'uncanny-ceu' ), learndash_get_custom_label_lower( 'course' ) ),
			];
		}

		$ceu_value       = get_post_meta( $course->ID, 'ceu_value', true );
		$float_ceu_value = filter_var( $ceu_value, FILTER_VALIDATE_FLOAT );

		// Validate CEU value
		if ( ! $float_ceu_value || 0 >= $float_ceu_value ) {
			return [
				'success' => false,
				'message' => sprintf(
					__( 'Sorry, the associated %1$s must have a %2$s value greater than 0.', 'uncanny-ceu' ),
					learndash_get_custom_label_lower( 'course' ),
					$credit_designation_label
				),
			];
		}

		// Get all users that have completed the course and do not have a CEU value
		global $wpdb;

		// Users that have CEU records for the course
		$ceu_assignments = $wpdb->get_results( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key  REGEXP '^ceu_date_.*_{$course->ID}$'" );
		// Users that completed the course
		$ld_course_completions = $wpdb->get_results( "SELECT user_id, meta_value FROM  {$wpdb->usermeta} WHERE meta_key = 'course_completed_{$course->ID}'" );

		// Store user_ids and completion dates for users that dont have a ceu record for the course
		$user_add_ceu_records = [];

		$ceu_assignments_by_ID = [];
		foreach ( $ceu_assignments as $ceu ) {
			$ceu_assignments_by_ID[ $ceu->user_id ] = $ceu->meta_value;
		}

		$q        = "INSERT INTO {$wpdb->usermeta} (user_id, meta_key, meta_value) VALUES ";
		$q_values = [];

		// Escape the course title
		$course->post_title = esc_sql( $course->post_title );

		foreach ( $ld_course_completions as $ld ) {
			if (
				! isset( $ceu_assignments_by_ID[ $ld->user_id ] )
				&& absint( $ld->meta_value )
			) {
				// Used for user facing confirmation message
				$user_add_ceu_records[ $ld->user_id ] = $ld->meta_value;

				// CEU value
				$q_values[] = "({$ld->user_id},'ceu_earned_{$ld->meta_value}_{$course->ID}','{$float_ceu_value}')";
				// CEU date
				$q_values[] = "({$ld->user_id},'ceu_date_{$ld->meta_value}_{$course->ID}','{$ld->meta_value}')";
				// CEU title
				$q_values[] = "({$ld->user_id},'ceu_title_{$ld->meta_value}_{$course->ID}','{$course->post_title}')";
				// CEU ID
				$q_values[] = "({$ld->user_id},'ceu_course_{$ld->meta_value}_{$course->ID}','{$course->ID}')";
			}
		}

		if ( empty( $q_values ) ) {
			return [
				'success' => true,
				'message' => sprintf(
					__( 'Update complete! There were no users updated. All %1$s completions had %2$s records.', 'uncanny-ceu' ),
					learndash_get_custom_label_lower( 'course' ),
					$credit_designation_label
				),
			];
		}

		$final_q = $q . implode( ',', $q_values );

		$results = $wpdb->query( $final_q );

		if ( false == $results ) {
			$error = $wpdb->last_error;

			return [
				'success' => false,
				'message' => sprintf(
					__( 'Sorry, the process failed. Error: %s', 'uncanny-ceu' ),
					$error
				),
			];
		}

		$rest_callback_data = [
			'success' => true,
			'message' => sprintf(
				__( 'Update complete! We added %1$d historical records for this %2$s.', 'uncanny-ceu' ),
				count( $user_add_ceu_records ),
				learndash_get_custom_label_lower( 'course' )
			),
		];

		return $rest_callback_data;
	}

	/**
	 *
	 */
	public function load_custom_wp_admin_style( $hook ) {

		/*
		 * Only load styles if the page hook contains the pages slug
		 *
		 * Hook can be either the toplevel_page_{page_slug} if its a parent  page OR
		 * it can be {parent_slug}_pag_{page_slug} if it is a sub page.
		 * Lets just check if our page slug is in the hook.
		 */

		// Target Licensing page
		if ( strpos( $hook, 'uncanny-historical-completions-credits' ) ) {
			// Select2
			wp_enqueue_script( 'ucec-admin-select2', Utilities::get_vendor( 'select2/select2.min.js' ), [ 'jquery' ], Utilities::get_version(), true );
			wp_enqueue_style( 'ucec-admin-select2', Utilities::get_vendor( 'select2/select2.min.css' ), [], Utilities::get_version() );

			// Global assets
			wp_register_script( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.js' ), [
				'jquery',
				'ucec-admin-select2',
			], Utilities::get_version(), true );

			wp_enqueue_style( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.css' ), [
				'ucec-admin-select2',
			], Utilities::get_version() );

			// API data
			$api_setup = array(
				'api'  => [
					'root'  => esc_url_raw( rest_url() . self::$root_path ),
					'nonce' => \wp_create_nonce( 'wp_rest' ),
				],
				'l10n' => [
					'filters' => [
						'allUsers' => __( 'All users', 'uncanny-ceu' ),
					],
				],
			);

			wp_localize_script( 'ucec-admin', 'UO_CEU_EDITOR', $api_setup );

			wp_enqueue_script( 'ucec-admin' );
		}

	}

	/**
	 *
	 */
	public function options_menu_page_output() {
		include( Utilities::get_template( 'admin-historical-completions-credits.php' ) );
	}

	static function get_courses_dropdown() {
		// Get options
		$options = [];

		$options[] = [
			'value' => 0,
			'text'  => __( 'Choose a course', 'uncanny-ceu' ),
		];

		$ceu_courses = Utilities::get_courses_with_credits();

		foreach ( $ceu_courses as $course ) {
			$options[] = [
				'value' => $course->ID,
				'text'  => $course->post_title,
			];
		}

		// Start HTML
		ob_start();

		// Iterate and create options
		foreach ( $options as $option ) {
			?>
			<option value="<?php echo $option['value']; ?>">
				<?php echo $option['text']; ?>
			</option>
			<?php
		}

		// Get HTML
		$options_html = ob_get_clean();

		// Return HTML
		return $options_html;
	}

}
