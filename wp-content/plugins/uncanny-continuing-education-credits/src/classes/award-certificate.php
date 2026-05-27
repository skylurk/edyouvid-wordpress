<?php

namespace uncanny_ceu;

/**
 * Class AwardCertificate
 * @package uncanny_ceu
 */
class AwardCertificate {

	/**
	 * @var string
	 */
	public $save_path;
	/**
	 * @var
	 */
	public $save_time;
	/**
	 * default
	 */
	public $current_custom_course = null;

	/**
	 * @var \uncanny_ceu\CEU_CRUD|null
	 */
	public $ceu_crud;

	/**
	 * class constructor
	 *
	 */
	public function __construct() {

		$this->ceu_crud = CEU_CRUD::get_instance();

		$this->save_path = WP_CONTENT_DIR . '/uploads/ceu-certs/';

		if ( ! file_exists( $this->save_path ) ) {
			mkdir( $this->save_path, 0755, true );
		}

		add_action( 'template_redirect', array( $this, 'template_redirect_access' ), 9, 0 );

		add_action( 'uo_course_completion_time', array( $this, 'uo_course_completion_time_func' ), 10, 3 );
		add_action( 'learndash_course_completed', array( $this, 'learndash_course_completed' ), 30, 1 );
		//      add_action( 'uo_ceu_scheduled_learndash_course_completed', array(
		//          $this,
		//          'learndash_before_course_completed'
		//      ), 30, 2 );

	}

	//  /**
	//   * @param $atts
	//   */
	//  public function schedule_learndash_before_course_completed( $atts ) {
	//      $pass_args = [
	//          $atts['user']->ID,
	//          $atts['course']->ID
	//      ];
	//
	//      wp_schedule_single_event( time() + 20, 'uo_ceu_scheduled_learndash_course_completed', $pass_args );
	//  }

	/**
	 * @param $time
	 * @param $course_id
	 * @param $user_id
	 */
	public function uo_course_completion_time_func( $time, $course_id, $user_id ) {
		if ( ! empty( $time ) ) {
			$this->save_time = $time;
		} else {
			$this->save_time = ! empty( $this->ceu_crud->get_usermeta( $user_id, 'course_completed_' . $course_id, true ) ) ? $this->ceu_crud->get_usermeta( $user_id, 'course_completed_' . $course_id, true ) : time();
		}

		return $this->save_time;
	}

	/**
	 * @param $user_id
	 * @param $course_id
	 * @param $is_manual_ceu
	 * @param $data
	 *
	 * @return object|void
	 * @deprecated 3.1
	 */
	public function learndash_before_course_completed( $user_id, $course_id, $is_manual_ceu, $data ) {

		$user = new \WP_User( $user_id );

		$course_data = $data = array(
			'user'             => $user,
			'course'           => null,
			'course_completed' => 0,
			'custom_course'    => $data['customCourse'],
			'custom_date'      => $data['date'],
			'custom_ceus'      => $data['ceus'],
			'custom_creation'  => true,
		);

		return $this->learndash_course_completed( $course_data );

	}

	/**
	 * @param array $course_data An array of course complete data.
	 *
	 * @return object|void
	 */
	public function learndash_course_completed( $course_data ) {

		$current_user          = $course_data['user'];  // User Object
		$current_course        = $course_data['course']; // Post Object
		$course_completed_time = $course_data['course_completed']; // Unix Timestamp from time()

		$manual_creation = (object) array(
			'success' => null,
			'error'   => null,
			'data'    => null,
		);

		$is_manual_creation = false;
		if ( isset( $course_data['custom_creation'] ) && true === $course_data['custom_creation'] ) {
			$is_manual_creation = true;
		}

		// User does not exist
		if ( ! isset( $course_data['user'] ) ) {
			if ( $is_manual_creation ) {
				$manual_creation->success = false;
				$manual_creation->field   = 'user';
				$manual_creation->error   = __( 'User does not exist', 'uncanny-ceu' );

				return $manual_creation;
			} else {
				return;
			}
		}

		// Set course title and CEU value
		if ( $is_manual_creation && null === $current_course ) {

			// Set course and ceu value for manual ceu

			$current_course_title        = $course_data['custom_course'];
			$this->current_custom_course = $current_course_title;

			if ( empty( $current_course_title ) ) {

				$manual_creation->success = false;
				$manual_creation->field   = 'course';
				$manual_creation->error   = sprintf( __( '%1$s does not exist', 'uncanny-ceu' ), \LearnDash_Custom_Label::get_label( 'course' ) );

				return $manual_creation;
			}

			$ceu_value = filter_var( $course_data['custom_ceus'], FILTER_VALIDATE_FLOAT );

			if ( false === $ceu_value || 0 > $ceu_value ) {
				$manual_creation->success = false;
				$manual_creation->field   = 'course';
				$manual_creation->error   = sprintf( __( '%1$s value is not setup for this %2$s', 'uncanny-ceu' ), Utilities::credit_designation_label( 'singular', __( 'CEU', 'uncanny-ceu' ) ), \LearnDash_Custom_Label::get_label( 'course' ) );

				return $manual_creation;
			}
		} else {

			$current_course_title = $current_course->post_title;

			// Set course and ceu value for LD course
			if ( null === $current_course || 'trash' === $current_course->post_status ) {
				if ( $is_manual_creation ) {
					$manual_creation->success = false;
					$manual_creation->field   = 'course';
					$manual_creation->error   = sprintf( __( '%1$s does not exist', 'uncanny-ceu' ), \LearnDash_Custom_Label::get_label( 'course' ) );

					return $manual_creation;
				} else {
					return;
				}
			}

			$ceu_value = get_post_meta( $current_course->ID, 'ceu_value', true );

			// No ceu value for this course, stop here
			if ( empty( $ceu_value ) ) {
				if ( $is_manual_creation ) {
					$manual_creation->success = false;
					$manual_creation->field   = 'course';
					$manual_creation->error   = sprintf( __( '%1$s value is not setup for this %2$s', 'uncanny-ceu' ), Utilities::credit_designation_label( 'singular', __( 'CEU', 'uncanny-ceu' ) ), \LearnDash_Custom_Label::get_label( 'course' ) );

					return $manual_creation;
				} else {
					return;
				}
			}
		}

		// Get course completed on time
		if ( $is_manual_creation ) {
			//$check_date = \DateTime::createFromFormat( 'F d Y, g:i:s a', $manual_data['date'], new \DateTimeZone(get_option('timezone_string')) );
			$check_date = \DateTime::createFromFormat( 'F d Y, g:i:s a', $course_data['custom_date'] );
			if ( false === $check_date ) {
				$manual_creation->success = false;
				$manual_creation->field   = 'date';
				$manual_creation->error   = __( 'Use the calendar to select a date.' . $course_data['custom_date'], 'uncanny-ceu' );

				return $manual_creation;
			}
			$completion_date = $check_date->format( 'U' );
			$this->save_time = $completion_date;
		} elseif ( empty( $this->save_time ) ) {
			$completion_date = time();
		} else {
			$completion_date = $this->save_time;
		}

		//Fallback
		if ( empty( $completion_date ) ) {
			$current_time    = $this->ceu_crud->get_usermeta( $current_user->ID, 'course_completed_' . $current_course->ID, true );
			$completion_date = ! empty( $current_time ) ? $current_time : time();
		}

		if ( null !== $current_course ) {
			$completion_date = apply_filters( 'uo_course_completion_time', $completion_date, $current_course->ID, $current_user->ID );
		}

		// Store course completion CEU data
		if ( $is_manual_creation && null === $current_course ) {
			$course_slug = str_replace( '_', '-', sanitize_title( $current_course_title ) );

			$insert = array(
				'user_id'          => $current_user->ID,
				'custom_course_id' => $course_slug,
				'credits'          => $ceu_value,
				'course_title'     => $current_course_title,
				'date_earned'      => current_time( 'mysql' ),
				'earned_timestamp' => $completion_date,
				'is_manual'        => 1,
			);

			/**
			 * Newer method
			 */
			$ceu_id = $this->ceu_crud->insert( $insert );

			//update_user_meta( $current_user->ID, 'ceu_earned_' . $completion_date . '_manual-ceu-' . $course_slug, $ceu_value );
			$k = 'ceu_earned_' . $completion_date . '_manual-ceu-' . $course_slug;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, $ceu_value );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, $ceu_value );

			//update_user_meta( $current_user->ID, 'ceu_date_' . $completion_date . '_manual-ceu-' . $course_slug, $completion_date );
			$k = 'ceu_date_' . $completion_date . '_manual-ceu-' . $course_slug;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, $completion_date );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, $completion_date );

			//update_user_meta( $current_user->ID, 'ceu_title_' . $completion_date . '_manual-ceu-' . $course_slug, $current_course_title );
			$k = 'ceu_title_' . $completion_date . '_manual-ceu-' . $course_slug;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, $current_course_title );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, $current_course_title );

			//update_user_meta( $current_user->ID, 'ceu_course_' . $completion_date . '_manual-ceu-' . $course_slug, 'manual-ceu' );
			$k = 'ceu_course_' . $completion_date . '_manual-ceu-' . $course_slug;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, 'manual-ceu' );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, 'manual-ceu' );

			do_action( 'ceus_after_updated_user_ceu_record', $current_user, $is_manual_creation, $completion_date, 'manual-ceu', $current_course_title, $course_slug, $ceu_value );

		} else {

			$insert = array(
				'user_id'          => $current_user->ID,
				'course_id'        => $current_course->ID,
				'credits'          => $ceu_value,
				'course_title'     => $current_course->post_title,
				'date_earned'      => current_time( 'mysql' ),
				'earned_timestamp' => $completion_date,
				'is_manual'        => 0,
			);

			/**
			 * Newer method
			 */
			$ceu_id = $this->ceu_crud->insert( $insert );

			//update_user_meta( $current_user->ID, 'ceu_earned_' . $completion_date . '_' . $current_course->ID, $ceu_value );
			$k = 'ceu_earned_' . $completion_date . '_' . $current_course->ID;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, $ceu_value );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, $ceu_value );

			//update_user_meta( $current_user->ID, 'ceu_date_' . $completion_date . '_' . $current_course->ID, $completion_date );
			$k = 'ceu_date_' . $completion_date . '_' . $current_course->ID;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, $completion_date );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, $completion_date );

			//update_user_meta( $current_user->ID, 'ceu_title_' . $completion_date . '_' . $current_course->ID, $current_course->post_title );
			$k = 'ceu_title_' . $completion_date . '_' . $current_course->ID;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, $current_course->post_title );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, $current_course->post_title );

			//update_user_meta( $current_user->ID, 'ceu_course_' . $completion_date . '_' . $current_course->ID, $current_course->ID );
			$k = 'ceu_course_' . $completion_date . '_' . $current_course->ID;
			$this->ceu_crud->update_usermeta( $current_user->ID, $k, $current_course->ID );
			// Keep new record
			$this->ceu_crud->insert_usermeta( $ceu_id, $k, $current_course->ID );

			do_action( 'ceus_after_updated_user_ceu_record', $current_user, $is_manual_creation, $completion_date, $current_course->ID, $current_course->post_title, $current_course->post_name, $ceu_value );

		}

		// Get Certificate Triggers
		global $wpdb;

		if ( $is_manual_creation && null === $current_course ) {
			$query = "SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key = 'ceu_cert_after_earned' AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'sfwd-certificates' AND post_status = 'publish')";
		} else {
			$query = "SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key = 'ceu_cert_after_earned' OR meta_key = 'ceu_cert_after_course_completions' AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'sfwd-certificates' AND post_status = 'publish')";
		}

		$ceu_certificate_triggers = $wpdb->get_results( $query );

		// If there are no trigger move on
		if ( empty( $ceu_certificate_triggers ) ) {
			return;
		}

		// Create a workable data
		$ceu_certificate_triggers = $this->normalize_triggers( $ceu_certificate_triggers );

		if ( ! $is_manual_creation && null !== $current_course ) {
			// Maybe Trigger certificate for Course Completions
			$this->maybe_trigger_course_completions_certificate( $current_user, $current_course->ID, $completion_date, $ceu_certificate_triggers );
			$earned_certificate = apply_filters( 'ucec_trigger_ceu_earned_certificate', true, $current_user, $current_course->ID, $completion_date, $ceu_certificate_triggers );
			if ( $earned_certificate ) {
				// Maybe Trigger certificate for CEU Completions
				$this->maybe_trigger_ceu_earned_certificate( $current_user, $current_course->ID, $completion_date, $ceu_certificate_triggers );
			}
		} else {
			$earned_certificate = apply_filters( 'ucec_trigger_ceu_earned_certificate', true, $current_user, 0, $completion_date, $ceu_certificate_triggers );
			if ( $earned_certificate ) {
				// Maybe Trigger certificate for CEU Completions
				$this->maybe_trigger_ceu_earned_certificate( $current_user, 0, $completion_date, $ceu_certificate_triggers );
			}
		}

		if ( $is_manual_creation ) {
			$manual_creation->success = true;
			$manual_creation->error   = 'certs sent';
			$manual_creation->data    = array( $ceu_value, $completion_date, $current_course_title, 'manual-ceu' );

			return $manual_creation;
		}
	}

	/**
	 * @param $current_user
	 * @param $current_course_id
	 * @param $date
	 * @param $ceu_certificate_triggers
	 */
	public function maybe_trigger_course_completions_certificate( $current_user, $current_course_id, $date, $ceu_certificate_triggers ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT meta_value from {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
			$current_user->ID,
			'ceu_course_%'
		);

		$meta_values = $wpdb->get_col( $sql );
		if ( empty( $meta_values ) ) {
			return;
		}
		$meta_values          = array_filter( $meta_values );
		$completed_course_ids = array_filter( $meta_values, 'is_int' );

		foreach ( $ceu_certificate_triggers['ceu_cert_after_course_completions'] as $certificate_id => $trigger ) {

			/*
			 * $trigger is all course ids in an array that need to be completed
			 * $completed_course_ids is all the course in an array to have been completed
			 *
			 * array instersect returns all the course ids that match between the courses that need to be compeleted and one that are completed
			 * if the amount of matched course ids are equal to the amount of $triggers(course that need to be completed) are eqal then the met the criteria
			 */
			if ( count( $trigger ) === count( array_intersect( $trigger, $completed_course_ids ) ) ) {

				$ceu_certificate_earned = $this->ceu_crud->get_usermeta( $current_user->ID, 'ceu_cert_earned_' . $certificate_id, true );

				// Certificate was not earned before, lets award it
				if ( '' === $ceu_certificate_earned ) {

					// Store time certificate was earned on
					//update_user_meta( $current_user->ID, 'ceu_cert_earned_' . $certificate_id, $date );
					$this->ceu_crud->update_usermeta( $current_user->ID, 'ceu_cert_earned_' . $certificate_id, $date );
					// Fetch any existing matching record
					$ceu_id = $this->ceu_crud->get_ceu_id( $current_user->ID, $date, $current_course_id );
					// Insert cert record
					$this->ceu_crud->insert_cert( $ceu_id, $certificate_id, $date, 0, $current_course_id );

					if ( 'yes' === get_option( 'uncanny-ceu-credits-cert-enabled', 'no' ) ) {
						$args = $this->setup_variables( $certificate_id, $current_user, $current_course_id );
						$cert = $this->generate_pdf( $args );
						$this->send_cert_email( $current_user, $cert, $current_course_id );

						//add_user_meta( $current_user->ID, 'uo-ceu-cert-' . $certificate_id, $cert );
						CEU_CRUD::get_instance()->add_usermeta( $current_user->ID, 'uo-ceu-cert-' . $certificate_id, $cert );
					}
				}
			}
		}
	}

	/**
	 * @param $current_user
	 * @param $current_course_id
	 * @param $date
	 * @param $ceu_certificate_triggers
	 */
	public function maybe_trigger_ceu_earned_certificate( $current_user, $current_course_id, $date, $ceu_certificate_triggers ) {
		global $wpdb;

		$sql         = $wpdb->prepare(
			"SELECT meta_value from {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
			$current_user->ID,
			'ceu_earned_%'
		);
		$meta_values = $wpdb->get_col( $sql );
		if ( empty( $meta_values ) ) {
			return;
		}
		$meta_values  = array_filter( $meta_values );
		$total_earned = array_sum( $meta_values );

		foreach ( $ceu_certificate_triggers['ceu_cert_after_earned'] as $certificate_id => $trigger ) {

			$ceu_needed = (float) $trigger;

			// Amount of CEUs is not set correctly
			if ( ! (float) $ceu_needed ) {
				continue;
			}

			if ( $total_earned >= $ceu_needed ) {

				$ceu_certificate_earned = $this->ceu_crud->get_usermeta( $current_user->ID, 'ceu_cert_earned_' . $certificate_id, true );

				// Certificate was not earned before, lets award it
				if ( '' === $ceu_certificate_earned ) {

					// Store time certificate was earned on
					//update_user_meta( $current_user->ID, 'ceu_cert_earned_' . $certificate_id, $date );
					$this->ceu_crud->update_usermeta( $current_user->ID, 'ceu_cert_earned_' . $certificate_id, $date );
					// Fetch any existing matching record
					$ceu_id = $this->ceu_crud->get_ceu_id( $current_user->ID, $date, $current_course_id );
					// Insert cert record
					$this->ceu_crud->insert_cert( $ceu_id, $certificate_id, $date, 0, $current_course_id );

					if ( 'yes' === get_option( 'uncanny-ceu-credits-cert-enabled', 'no' ) ) {
						$args = $this->setup_variables( $certificate_id, $current_user, $current_course_id );
						$cert = $this->generate_pdf( $args );
						$this->send_cert_email( $current_user, $cert, $current_course_id );

						//update_user_meta( $current_user->ID, 'uo-ceu-cert-' . $certificate_id, $cert );
						$this->ceu_crud->update_usermeta( $current_user->ID, 'uo-ceu-cert-' . $certificate_id, $cert );
					}
				}
			}
		}
	}

	/**
	 * Set Post ID as object key
	 *
	 * @param array $triggers
	 *
	 * @return array $new_ceu_cetificate_triggers
	 */
	private function normalize_triggers( $triggers ) {

		$new_triggers = array();

		// trigger based on course completions
		$new_triggers['ceu_cert_after_course_completions'] = array();

		// trigger based on CEUs earned
		$new_triggers['ceu_cert_after_earned'] = array();

		foreach ( $triggers as $trigger ) {

			if ( 'ceu_cert_after_course_completions' === $trigger->meta_key ) {

				$course_ids                                                             = array_map( 'floatval', json_decode( $trigger->meta_value ) );
				$new_triggers['ceu_cert_after_course_completions'][ $trigger->post_id ] = $course_ids;
			}

			if ( 'ceu_cert_after_earned' === $trigger->meta_key ) {
				$new_triggers['ceu_cert_after_earned'][ $trigger->post_id ] = (float) $trigger->meta_value;
			}
		}

		return $new_triggers;

	}

	/**
	 *  Add certificate redirect
	 *
	 */
	public function template_redirect_access() {

		if ( isset( $_GET['ceu_access_key'] ) && ! empty( $_GET['ceu_access_key'] ) ) {
			$access_key = $_GET['ceu_access_key'];
		} else {
			// This is not a CEU url, move on wih WordPress
			return;
		}

		global $post;

		/**
		 * Added check to ensure $post is not empty
		 *
		 * @since 2.3.0.3
		 */
		if ( empty( $post ) ) {
			return;
		}

		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}

		$post_type = $post->post_type;
		$post_id   = $post->ID;

		if ( empty( $post_type ) || empty( $post_id ) ) {
			return;
		}

		if ( $post_type == 'sfwd-certificates' ) {

			$access_vars = SharedFunctions::decrypt( $access_key, wp_salt() );

			$access_vars = explode( '-', $access_vars );

			// Did the access key provide a User ID
			if ( isset( $access_vars[0] ) && absint( $access_vars[0] ) ) {
				$user_id = absint( $access_vars[0] );
			} else {
				// User ID was not in access key
				$this->set_404();

				return;

			}

			$user = get_user_by( 'ID', $user_id );

			// User does not exist anymore
			if ( ! $user ) {
				$this->set_404();

				return;
			}

			// Did the access key provide an award type
			if ( isset( $access_vars[1] ) && $access_vars[1] === 'ceu_cert_earned' ) {
				$award = $access_vars[1];
			} else {
				// Award type was not in access key
				$this->set_404();

				return;
			}

			$earned_certificate = '';

			if ( 'ceu_cert_earned' === $award ) {
				$earned_certificate = $this->ceu_crud->get_usermeta( $user->ID, 'ceu_cert_earned_' . $post_id, true );
			}

			// User has not earned the certificate
			if ( empty( $earned_certificate ) ) {
				$this->set_404();

				return;
			}

			/**
			 * Use LD Certificate to PDF creation to genderate PDF
			 */

			// Set set the user that the PDF will be generated for
			wp_set_current_user( $user->ID );

			// Load conversion code
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/ld-convert-post-pdf.php';

			// Set header so we don't generate a 404
			status_header( 200 );

			// Create the PDF
			post2pdf_conv_post_to_pdf();

			// ALL GOOD :)
			die();

		} else {
			// Access Key what user on a page that wasn't a certificate post type
			$this->set_404();

			return;
		}

	}

	/**
	 * If the url does not validate, redirect to WP 404 page
	 */
	private function set_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}

	/**
	 * Send email notification of earned certificate
	 *
	 * @param object $current_user
	 * @param int|array $certificate
	 * @param int $courses
	 */
	public function send_cert_email( $current_user, $certificate, $courses = 0 ) {

		$course_names = array();

		if ( ! $certificate ) {
			return;
		}
		if ( empty( get_option( 'uncanny-ceu-multiple-certs-subject', '' ) ) ) {
			$email_subject = __( 'You earned a Certificate', 'uncanny-ceu' );
		} else {
			$email_subject = get_option( 'uncanny-ceu-multiple-certs-subject' );
		}

		if ( empty( get_option( 'uncanny-ceu-multiple-certs-subject', '' ) ) ) {
			$email_message = __( "Congratulations, you earned a certificate!\r\nYour certificate is attached to this email.", 'uncanny-ceu' );
		} else {
			$email_message = get_option( 'uncanny-ceu-multiple-certs-message' );
		}

		if ( ! empty( $courses ) && is_array( $courses ) ) {
			foreach ( $courses as $l_id ) {
				$course_names[] = get_the_title( $l_id );
			}
		} else {

			if ( absint( $courses ) ) {
				$course_names = array( get_the_title( $courses ) );
			} else {
				if ( ! empty( $this->current_custom_course ) ) {
					$course_names = array( $this->current_custom_course );
				}
				$course_names = array();
			}
		}

		$email_message = str_ireplace( '%User%', $current_user->display_name, $email_message );
		$email_message = str_ireplace( '%User First Name%', $current_user->first_name, $email_message );
		$email_message = str_ireplace( '%User Last Name%', $current_user->last_name, $email_message );
		$email_message = str_ireplace( '%User Email%', $current_user->user_email, $email_message );
		$email_message = str_ireplace( '%Courses%', join( ', ', $course_names ), $email_message );

		$email_subject = str_ireplace( '%User%', $current_user->display_name, $email_subject );
		$email_subject = str_ireplace( '%User First Name%', $current_user->first_name, $email_subject );
		$email_subject = str_ireplace( '%User Last Name%', $current_user->last_name, $email_subject );
		$email_subject = str_ireplace( '%User Email%', $current_user->user_email, $email_subject );
		$email_subject = str_ireplace( '%Group Name%', '', $email_subject );
		$email_subject = str_ireplace( '%Courses%', join( ', ', $course_names ), $email_subject );

		//Sending email to user first
		$user_mail_headers = apply_filters( 'uo_user_mail_headers', array() );
		wp_mail( $current_user->user_email, $email_subject, $email_message, $user_mail_headers, $certificate );

		//Now let's see if we want to send email to admin and group leader
		$is_admin       = get_option( 'uncanny-ceu-multiple-notify-admin', 'no' );
		$is_group_admin = get_option( 'uncanny-ceu-multiple-notify-group-leader', 'no' );

		$email_subject = str_ireplace( 'You earned', $current_user->first_name . ' earned', $email_subject );

		if ( 'yes' === (string) $is_admin ) {
			$admin_mail_headers = apply_filters( 'uo_admin_mail_headers', array() );
			wp_mail( get_bloginfo( 'admin_email' ), $email_subject, $email_message, $admin_mail_headers, $certificate );
		}

		if ( 'yes' === (string) $is_group_admin ) {
			$get_leaders = array();
			$user_groups = learndash_get_users_group_ids( $current_user->ID, true );
			if ( ! empty( $user_groups ) ) {
				foreach ( $user_groups as $group ) {
					$has_group_leader = learndash_get_groups_administrators( $group, true );

					if ( ! empty( $has_group_leader ) ) {
						foreach ( $has_group_leader as $leader ) {
							if ( learndash_is_group_leader_of_user( $leader->ID, $current_user->ID ) ) {
								$ll                      = get_user_by( 'ID', $leader->ID );
								$get_leaders[ $group ][] = $ll->user_email;
							}
						}
					}
				}
			}

			//self::trace_logs( $get_leaders, 'Get Leaders', 'pdf' );
			if ( ! empty( $get_leaders ) ) {
				foreach ( $get_leaders as $key => $value ) {
					$email_message = str_ireplace( '%Group Name%', get_the_title( $key ), $email_message );
					$email_subject = str_ireplace( '%Group Name%', get_the_title( $key ), $email_subject );
					//$email     = join( ', ', $value );

					$group_leader_mail_headers = apply_filters( 'uo_group_leader_mail_headers', array() );
					wp_mail( $value, $email_subject, $email_message, $group_leader_mail_headers, $certificate );

				}
			}

			if ( ! is_array( $certificate ) ) {
				return;
			}
			//Store sent certificate to user meta and/or if do not is active, delete file from server
			foreach ( $certificate as $cert_key => $p_certs ) {
				if ( 'no' === get_option( 'uncanny-ceu-multiple-do-not-store-certs', 'no' ) ) {
					//update_user_meta( $current_user->ID, 'uo-ceu-cert-' . $cert_key, $p_certs );
					$this->ceu_crud->update_usermeta( $current_user->ID, 'uo-ceu-cert-' . $cert_key, $p_certs );
				} else {
					if ( file_exists( $p_certs ) ) {
						unlink( $p_certs );
					}
				}
			}
		}
	}

	/**
	 * @param $certificate_id
	 * @param $current_user
	 * @param $course_id
	 *
	 * @return array
	 */
	public function setup_variables( $certificate_id, $current_user, $course_id ) {
		$timestamp = current_time( 'timestamp' );
		$cert_name = "$certificate_id-" . sanitize_title( $current_user->user_email ) . '-' . $timestamp;
		$cert_name = apply_filters( 'ucec_pdf_file_name', $cert_name, $certificate_id, $current_user, $timestamp );

		return array(
			'certificate_post' => $certificate_id,
			'save_path'        => $this->save_path,
			'user'             => $current_user,
			'file_name'        => sanitize_title( $cert_name ),
			'parameters'       => array(
				'userID'            => $current_user->ID,
				'course-id'         => ( 0 !== $course_id ) ? $course_id : $certificate_id,
				'course-name'       => get_the_title( $course_id ),
				'print-certificate' => 1,
			),
		);
	}

	/**
	 * @param $args
	 *
	 * @return string
	 */
	public function generate_pdf( $args ) {

		require_once Utilities::get_include( 'certificate-builder.php' );

		$builder = new CertificateBuilder();

		if ( $builder->created_with_builder( $args['certificate_post'] ) ) {

			$pdf = $builder->generate_pdf( $args, 'course' );

			if ( is_array( $pdf ) ) { //If LD certificate builder plugin is off
				return '';
			}

			return $pdf;

		}

		require_once Utilities::get_include( 'tcpdf-certificate-code.php' );

		return Tcpdf_Certificate_Code::generate_pdf( $args );
	}

}
