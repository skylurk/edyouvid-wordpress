<?php

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CeuMultiCourse
 * @package uncanny_ceu
 */
class CeuMultiCourse {
	public $meta_key = 'uncanny-certificate-courses';

	public $ceu_crud;

	/**
	 * UncannyCoursesCertificate constructor.
	 */
	public function __construct() {
		$this->ceu_crud = CEU_CRUD::get_instance();

		add_action( 'admin_init', array( $this, 'save_email_settings' ), 20 );
		if ( 'yes' === get_option( 'uncanny-ceu-multiple-certs-enabled', 'no' ) ) {
			add_action( 'load-post.php', array( $this, 'uncanny_certificate_meta_boxes_setup' ) );
			add_action( 'load-post-new.php', array( $this, 'uncanny_certificate_meta_boxes_setup' ) );
			add_action( 'learndash_course_completed', array( $this, 'generate_course_certificate' ), 101 );
		}
	}


	/**
	 * @return null
	 */
	public function save_email_settings() {

		/* Verify the nonce before proceeding. */
		if ( ! isset( $_POST['uncanny_email_settings_nonce'] ) || ! wp_verify_nonce( $_POST['uncanny_email_settings_nonce'], 'uncanny-ceu' ) ) {
			return null;
		}

		$checked = ( isset( $_POST['uncanny_certificate_do_not_store'] ) ) ? $_POST['uncanny_certificate_do_not_store'] : 'no';
		update_option( 'uncanny-ceu-multiple-do-not-store-certs', $checked );

		$checked = ( isset( $_POST['uncanny_certificate_notify_site_admin'] ) ) ? $_POST['uncanny_certificate_notify_site_admin'] : 'no';
		update_option( 'uncanny-ceu-multiple-notify-admin', $checked );

		$checked = ( isset( $_POST['uncanny_certificate_notify_group_leader'] ) ) ? $_POST['uncanny_certificate_notify_group_leader'] : 'no';
		update_option( 'uncanny-ceu-multiple-notify-group-leader', $checked );

		$checked = ( isset( $_POST['uncanny_certificate_email_enabled'] ) ) ? $_POST['uncanny_certificate_email_enabled'] : 'no';
		update_option( 'uncanny-ceu-multiple-certs-enabled', $checked );

		$checked = ( isset( $_POST['uncanny_ceu_certificate_email_enabled'] ) ) ? $_POST['uncanny_ceu_certificate_email_enabled'] : 'no';
		update_option( 'uncanny-ceu-credits-cert-enabled', $checked );

		$email_subject = esc_html( $_POST['uncanny_certificate_email_subject'] );
		$email_message = esc_html( $_POST['uncanny_ceu_multiple_certs_message'] );
		update_option( 'uncanny-ceu-multiple-certs-subject', $email_subject );
		update_option( 'uncanny-ceu-multiple-certs-message', $email_message );
	}

	/**
	 *
	 */
	public function options_menu_page_output() {

		//$this->localize_filter_globalize_text();

		include Utilities::get_template( 'admin-email-settings.php' );

	}

	/**
	 * Load Scripts that are specific to the admin page
	 *
	 * @param string $hook Admin page being loaded
	 *
	 * @since 1.0
	 *
	 */
	public function admin_scripts( $hook ) {
		wp_enqueue_script( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.js' ), [ 'jquery' ], Utilities::get_version(), true );
		wp_enqueue_style( 'ucec-admin', Utilities::get_asset( 'backend', 'bundle.min.css' ), [], Utilities::get_version() );
	}

	/**
	 *
	 */
	public function uncanny_certificate_meta_boxes_setup() {
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( $this, 'uncanny_add_certificate_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'uncanny_save_certificate_class_meta' ), 10, 2 );
	}

	/**
	 *
	 */
	public function uncanny_add_certificate_meta_boxes() {

		add_meta_box(
			'uncanny-certificate-courses',      // Unique ID
			esc_html__( 'Award Certificate For Multiple Course Completions', 'uncanny-ceu' ),    // Title
			array( $this, 'uncanny_certificate_class_meta_box' ),   // Callback function
			'sfwd-certificates',         // Admin page (or post type)
			'side',         // Context
			'high'         // Priority
		);
	}

	/**
	 * @param $post
	 */
	public function uncanny_certificate_class_meta_box( $post ) { ?>

		<?php wp_nonce_field( basename( __FILE__ ), 'uncanny_certificate_class_nonce' ); ?>

		<p>
			<label
				for="uncanny-certificate-courses"><?php _e( 'Choose ALL courses that must be completed to award this certificate.', 'uncanny-ceu' ); ?></label>
			<br/>
			<br/>
			<select multiple="multiple" class="widefat" name="uncanny-certificate-courses[]"
					id="uncanny-certificate-courses">
				<!--<option value="0">-- Courses --</option>-->
				<?php
				$courses = get_posts( array(
					'post_type'      => 'sfwd-courses',
					'posts_per_page' => 999,
					'post_status'    => 'publish',
				) );
				if ( $courses ) {
					foreach ( $courses as $course ) {
						$selected_courses = get_post_meta( $post->ID, $this->meta_key, true );
						if ( empty( $selected_courses ) ) {
							$selected_courses = array();
						} else {
							$selected_courses = array_map( 'absint', $selected_courses );
						}
						$selected = in_array( $course->ID, $selected_courses, true ) ? 'selected="selected"' : '';
						?>
						<option
							value="<?php echo $course->ID ?>" <?php echo $selected; ?>><?php echo $course->post_title; ?></option>
						<?php
					}
				}
				?>
			</select>
			<br/>
			<small><i>Press Ctrl to select/unselect multiple courses.</i></small>
		</p>
		<?php
	}

	/**
	 * @param $post_id
	 * @param $post
	 *
	 * @return mixed
	 */
	public function uncanny_save_certificate_class_meta( $post_id, $post ) {

		/* Verify the nonce before proceeding. */
		if ( ! isset( $_POST['uncanny_certificate_class_nonce'] ) || ! wp_verify_nonce( $_POST['uncanny_certificate_class_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );

		/* Check if the current user has permission to edit the post. */
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		/* Get the posted data and sanitize it for use as an HTML class. */
		$new_meta_value = ( isset( $_POST['uncanny-certificate-courses'] ) ? (array) $_POST['uncanny-certificate-courses'] : '' );

		/* Get the meta key. */
		$meta_key = $this->meta_key;

		/* If a new meta value was added and there was no previous value, add it. */
		if ( ! empty( $new_meta_value ) ) {
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		} elseif ( empty( $new_meta_value ) ) {
			delete_post_meta( $post_id, $meta_key );
		}
	}

	/**
	 * @param $course_data
	 *
	 * @return null
	 */
	public function generate_course_certificate( $course_data ) {
		$user_id        = $course_data['user']->ID;
		$user           = new \WP_User( $user_id );
		$course_id      = $course_data['course']->ID;
		$linked_courses = array();

		if ( ! learndash_course_completed( $user_id, $course_id ) ) {
			return;
		}
		$certificates = $this->get_certificate_id_from_course( $course_id );
		if ( empty( $certificates ) ) {
			return null;
		}
		foreach ( $certificates as $key => $cert_id ) {
			$linked_courses = get_post_meta( $cert_id, $this->meta_key, true );
			if ( ! empty( $linked_courses ) ) {
				foreach ( $linked_courses as $c_id ) {
					if ( false === learndash_course_completed( $user_id, $c_id ) ) {
						unset( $certificates[ $key ] );
						break;
					}
				}
			}
		}
		if ( empty( $certificates ) ) {
			return;
		}

		$save_path = WP_CONTENT_DIR . '/uploads/multi-course-certs/'; /* Save Path on Server under Upload */

		if ( ! file_exists( $save_path ) ) {
			mkdir( $save_path, 0755 );
		}

		$print_certs       = array();
		$award_certificate = new AwardCertificate();
		$current_time      = time();
		//If $certificates still not empty
		foreach ( $certificates as $certificate_id ) {
			$args                           = $award_certificate->setup_variables( $certificate_id, $user, $course_id );
			$cert                           = $award_certificate->generate_pdf( $args );
			$print_certs[ $certificate_id ] = $cert;

			$key = "ceu_multicert_{$certificate_id}_course_{$course_id}";

			//add_user_meta( $user->ID, $key, $cert );
			$this->ceu_crud->add_usermeta( $user->ID, $key, $cert );

			//add_user_meta( $user->ID, $key . '_earned', $current_time );
			$this->ceu_crud->add_usermeta( $user->ID, $key . '_earned', $current_time );

			//add_user_meta( $user->ID, 'ceu_multicert_' . $certificate_id . '_earned', $current_time );
			$this->ceu_crud->add_usermeta( $user->ID, 'ceu_multicert_' . $certificate_id . '_earned', $current_time );
		}

		if ( empty( $print_certs ) ) {
			return;
		}

		//add_user_meta( $user->ID, 'uo-ceu-multicert-' . $current_time, $print_certs );
		$this->ceu_crud->add_usermeta( $user->ID, 'uo-ceu-multicert-' . $current_time, $print_certs );

		//add_user_meta( $user->ID, 'uo-ceu-multicert-linked-courses-' . $current_time, $linked_courses );
		$this->ceu_crud->add_usermeta( $user->ID, 'uo-ceu-multicert-linked-courses-' . $current_time, $linked_courses );

		//add_user_meta( $user->ID, 'uo-ceu-multicert-certificates-' . $current_time, $certificates );
		$this->ceu_crud->add_usermeta( $user->ID, 'uo-ceu-multicert-certificates-' . $current_time, $certificates );

		//Send all certificates to user first
		$award_certificate->send_cert_email( $user, $print_certs, $linked_courses );
	}

	/**
	 * @param $course_id
	 *
	 * @return array
	 */
	public function get_certificate_id_from_course( $course_id ) {
		$certificates = get_posts( array(
			'post_type'   => 'sfwd-certificates',
			'post_status' => 'publish',
			'meta_query'  => array(
				'meta_key'   => $this->meta_key,
				'meta_value' => "%$course_id%",
				'compare'    => 'LIKE',
			),
		) );

		if ( ! empty( $certificates ) ) {
			$certs = array();
			foreach ( $certificates as $certificate ) {
				$cert_id        = $certificate->ID;
				$linked_courses = get_post_meta( $cert_id, $this->meta_key, true );
				if ( ! empty( $linked_courses ) ) {
					if ( in_array( $course_id, $linked_courses ) ) {
						$certs[] = $cert_id;
					}
				}
			}

			return $certs;
		}

		return array();

	}
}
