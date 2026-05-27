<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;
use uncanny_pro_toolkit\CertificateBuilder;

/**
 * Class CourseCompletionCertificate
 * @package uncanny_pro_toolkit
 */
class CourseCompletionCertificate extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * @var
	 */
	public static $current_time_stamp;
	/**
	 * @var
	 */
	public static $pdf_filename;

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	/**
	 *
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {
			require_once Boot::get_pro_include( 'certificate-builder.php', UO_FILE );
			require_once Boot::get_pro_include( 'tcpdf-certificate-code.php', UO_FILE );
			require_once Boot::get_pro_include( 'uncanny-shortcodes-parser.php', UO_FILE );

			/* ADD FILTERS ACTIONS FUNCTION */
			add_action( 'learndash_course_completed', array(
				__CLASS__,
				'schedule_generate_course_certificate',
			), 20, 1 );


			add_action( 'uo_scheduled_learndash_course_completed', array(
				__CLASS__,
				'generate_course_certificate',
			), 20, 2 );
		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available
		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'email-course-certificates';

		$class_title = esc_attr__( 'Email Course Certificates', 'uncanny-pro-toolkit' );

		//set to null or remove to disable the link to KB
		$kb_link = 'https://www.uncannyowl.com/knowledge-base/send-course-certificates-email/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Sends a copy of certificate earned from course completion and saves certificates on the server.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-file-pdf-o"></i><span class="uo_pro_text">PRO</span>';

		$category = 'learndash';
		$type     = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @static
	 *
	 * @param $class_title
	 *
	 * @return array
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array(

			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Use Cron to send certificate', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-use-cron',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Do not store certificates on server', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-dont-store',
			),
			array(
				'type'       => 'html',
				'inner_html' => 'By default, certificates are stored at: &lt;site root&gt;/wp-content/uploads/course-certificates/',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Send Certificate to Site Admin?', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-admin',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Send Certificate to Group Leader(s)?', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-group-leader',
			),
			array(
				'type'        => 'text',
				'placeholder' => get_bloginfo( 'name' ),
				'label'       => esc_html__( 'From Name', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-from-name',
			),
			array(
				'type'        => 'text',
				'placeholder' => get_bloginfo( 'admin_email' ),
				'label'       => esc_html__( 'From Email', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-from-email',
			),
			array(
				'type'        => 'text',
				'placeholder' => 'jon@doe.com, doe@jon.com',
				'label'       => esc_html__( 'CC Certificate To (comma separated)', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-cc-emails',
			),
			array(
				'type'        => 'text',
				'placeholder' => 'You Earned a Certificate',
				'label'       => esc_html__( 'User Email Subject', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-user-subject-line',
			),
			array(
				'type'        => 'text',
				'placeholder' => '%User% has earned a course certificate',
				'label'       => esc_html__( 'Admin/Group Leader Email Subject', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-subject-line',
			),
			array(
				'type'        => 'textarea',
				'placeholder' => '%User% in %Group Name% has earned a course certificate for completing %Course Name%.',
				'label'       => esc_html__( 'Email Body', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-email-body',
			),
			array(
				'type'        => 'textarea',
				'placeholder' => '%User% in %Group Name% has earned a course certificate for completing %Course Name%.',
				'label'       => esc_html__( 'Email Body &mdash; Admin/Group Leader', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-non-user-email-body',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<strong>Available variables for email subject & body</strong><br /><ul><li><strong>%User%</strong> &mdash; Prints User\'s Display Name</li><li><strong>%User First Name%</strong> &mdash; Prints User\'s First Name</li><li><strong>%User Last Name%</strong> &mdash; Prints User\'s Last Name</li><li><strong>%Group Name%</strong> &mdash; Prints Group Name <!--<em>Only Available for Group Leader</em>--></li><li><strong>%User Email%</strong> &mdash; Prints User Email</li><li><strong>%Course Name%</strong> &mdash; Prints Course Title</li></ul>',
			),
		);

		// Build html.
		$html = self::settings_output(
			array(
				'class'   => __CLASS__,
				'title'   => $class_title,
				'options' => $options,
			)
		);

		return $html;
	}

	/**
	 * @param $atts
	 */
	public static function schedule_generate_course_certificate( $atts ) {
		$pass_args = [
			$atts['user']->ID,
			$atts['course']->ID,
		];
		//Use Cron?
		$use_cron = self::get_settings_value( 'uncanny-course-certificate-use-cron', __CLASS__ );
		if ( ! empty( $use_cron ) && 'on' === $use_cron ) {
			$random_number = rand( 25, 90 );
			$next_run      = strtotime( '+' . $random_number . ' second' );
			wp_schedule_single_event( $next_run, 'uo_scheduled_learndash_course_completed', $pass_args );
		} else {
			self::generate_course_certificate( $atts['user']->ID, $atts['course']->ID );
		}
	}

	/**
	 * @param $user_id
	 * @param $course_id
	 */
	public static function generate_course_certificate( $user_id, $course_id ) {
		$user         = new \WP_User( $user_id );
		$email_params = array(
			'email'   => '',
			'msg'     => '',
			'subject' => '',
		);

		// External override if required. Usage is in CEU historical records & AT for events ending date.
		$current_time             = get_user_meta( $user_id, 'course_completed_' . $course_id, true );
		self::$current_time_stamp = apply_filters( 'uo_course_completion_time', $current_time, $course_id, $user_id );
		// Fallback.
		if ( empty( self::$current_time_stamp ) ) {
			self::$current_time_stamp = ! empty( $current_time ) ? $current_time : current_time( 'timestamp' );
		}

		do_action( 'uo_course_completion_time', self::$current_time_stamp, $course_id, $user_id );
		if ( 'completed' !== learndash_course_status( $course_id, $user_id, true ) ) {
			return;
		}
		$setup_parameters = self::setup_parameters( $course_id, $user_id );

		if ( 1 !== (int) $setup_parameters['print-certificate'] ) {
			return;
		}

		$certificate_post = $setup_parameters['certificate-post'];
		/* Save Path on Server under Upload & allow overwrite */
		$save_path = apply_filters( 'uo_course_certificate_save_path', WP_CONTENT_DIR . '/uploads/course-certificates/' );
		/**
		 * New filter added so that arguments can be passed. Adding arguments
		 * to previous filter above might break sites since
		 * there might be no argument supplied with override function
		 *
		 * @since  3.6.4
		 * @author Saad
		 * @var $save_path
		 */
		$save_path = apply_filters( 'uo_course_certificate_upload_dir', $save_path, $user, $course_id, $certificate_post, self::$current_time_stamp );

		$course_cert_meta = '_uo-course-cert-' . $course_id;

		/* Creating a fileName that is going to be stored on the server. Certificate-QUIZID-USERID-NONCE_String */
		$file_name = sanitize_title( $user->user_email . '-' . $course_id . '-' . $certificate_post . '-' . date( 'Ymd', self::$current_time_stamp ) . '-' . wp_create_nonce( self::$current_time_stamp ) );

		//Allow overwrite of custom filename
		$file_name = apply_filters( 'uo_course_completion_certificate_filename', $file_name, $user, $course_id, $certificate_post, self::$current_time_stamp );
		if ( ! file_exists( $save_path ) && ! mkdir( $save_path, 0755 ) && ! is_dir( $save_path ) ) { // phpcs:ignore
			throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $save_path ) );
		}

		$full_path          = $save_path . $file_name;
		self::$pdf_filename = $full_path;

		//Allow PDF args to be modified
		$generate_pdf_args = apply_filters( 'uo_course_completion_generate_pdf_args', [
			'certificate_post' => $certificate_post,
			'save_path'        => $save_path,
			'user'             => $user,
			'file_name'        => $file_name,
			'parameters'       => $setup_parameters,
		], $course_id, $user_id );

		//External override if certificate is not needed!
		$uo_generate_course_certs = apply_filters( 'uo_generate_course_certificate', true, $generate_pdf_args, $course_id, $user_id );
		if ( ! $uo_generate_course_certs ) {
			return;
		}

		$file = self::generate_pdf( $generate_pdf_args );
		//Allow custom Link to an upload folder
		$http_link = apply_filters( 'uo_course_certificate_http_url', WP_CONTENT_URL . '/uploads/course-certificates/' );
		/**
		 * New filter added so that arguments can be passed. Adding arguments
		 * to previous filter above might break sites since
		 * there might be no argument supplied with override function
		 *
		 * @since  3.6.4
		 * @author Saad
		 * @var $http_link
		 */
		$http_link         = apply_filters( 'uo_course_certificate_url', $http_link, $user, $course_id, $certificate_post, self::$current_time_stamp );
		$http_link_to_file = $http_link . $file_name . '.pdf';
		do_action( 'uo_course_certificate_pdf_url', $http_link_to_file, $course_id, self::$current_time_stamp, $user_id );
		$current_certs = get_user_meta( $user_id, $course_cert_meta, true );

		if ( ! empty( $current_certs ) ) {
			$current_certs[][ self::$current_time_stamp ] = $http_link_to_file;
			update_user_meta( $user_id, $course_cert_meta, $current_certs );
		} else {
			$certs[][ self::$current_time_stamp ] = $http_link_to_file;
			add_user_meta( $user_id, $course_cert_meta, $certs );
		}


		if ( empty( $file ) ) {
			return;
		}

		$email_params['email'] = $user->user_email;

		$is_admin           = self::get_settings_value( 'uncanny-course-certificate-admin', __CLASS__ );
		$is_group_admin     = self::get_settings_value( 'uncanny-course-certificate-group-leader', __CLASS__ );
		$email_message      = self::get_settings_value( 'uncanny-course-certificate-email-body', __CLASS__ );
		$non_user_body      = self::get_settings_value( 'uncanny-course-certificate-non-user-email-body', __CLASS__ );
		$email_subject      = self::get_settings_value( 'uncanny-course-certificate-subject-line', __CLASS__ );
		$email_subject_user = self::get_settings_value( 'uncanny-course-certificate-user-subject-line', __CLASS__ );
		$cc_emails          = self::get_settings_value( 'uncanny-course-certificate-cc-emails', __CLASS__ );

		$email_subject = apply_filters( 'uo_course_certificate_email_subject', $email_subject, $user, $course_id );
		$email_message = apply_filters( 'uo_course_certificate_email_message', $email_message, $user, $course_id );

		$from_name  = self::get_settings_value( 'uncanny-course-certificate-from-name', __CLASS__ );
		$from_email = self::get_settings_value( 'uncanny-course-certificate-from-email', __CLASS__ );
		$from_name  = empty( $from_name ) ? get_bloginfo( 'name' ) : $from_name;
		$from_email = empty( $from_email ) ? get_bloginfo( 'admin_email' ) : $from_email;

		$headers = array( "From:{$from_name} <{$from_email}>" );

		if ( true === strpos( $cc_emails, ',' ) ) {
			$cc_emails = explode( ',', $cc_emails );
		}

		if ( empty( $email_message ) ) {
			$email_message = '%User% has earned a course certificate for completing %Course Name%.';
		}

		if ( empty( $non_user_body ) ) {
			$non_user_body = '%User% has earned a course certificate for completing %Course Name%.';
		}

		if ( empty( $email_subject ) ) {
			$email_subject = '%User% has earned a course certificate';
		}

		if ( empty( $email_subject_user ) ) {
			$email_subject_user = 'You earned a certificate';
		}


		$user_groups = learndash_get_users_group_ids( $user->ID, true );
		$ugroups     = [];
		if ( $user_groups ) {
			foreach ( $user_groups as $gr ) {
				$ugroups[] = html_entity_decode( get_the_title( $gr ) );
			}
		} else {
			$ugroups[] = '';
		}
		$ugroups = join( ', ', $ugroups );

		$email_message = str_ireplace( '%User%', html_entity_decode( $user->display_name ), $email_message );
		$email_message = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $email_message );
		$email_message = str_ireplace( '%User Last Name%', html_entity_decode( $user->last_name ), $email_message );
		$email_message = str_ireplace( '%User Email%', $user->user_email, $email_message );
		$email_message = str_ireplace( '%Course Name%', $setup_parameters['course-name'], $email_message );
		$email_message = str_ireplace( '%Group Name%', $ugroups, $email_message );

		$email_message .= "\n\n";

		if ( is_array( $file ) && isset( $file['error'] ) ) {
			$email_message .= $file['error'];
		} elseif ( apply_filters( 'uo_course_completion_add_certificate_attached', true ) ) {
			/** Commented out so we have full control of message */
			$email_message_certificate_attached_text = apply_filters( 'uo_course_certificate_email_message_certificate_attached_text', esc_attr__( 'Your certificate is attached with this email.', 'uncanny-pro-toolkit' ));
			$email_message .= $email_message_certificate_attached_text;
		}

		$non_user_body = str_ireplace( '%User%', html_entity_decode( $user->display_name ), $non_user_body );
		$non_user_body = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $non_user_body );
		$non_user_body = str_ireplace( '%User Last Name%', html_entity_decode( $user->last_name ), $non_user_body );
		$non_user_body = str_ireplace( '%User Email%', $user->user_email, $non_user_body );
		$non_user_body = str_ireplace( '%Course Name%', $setup_parameters['course-name'], $non_user_body );
		$non_user_body = do_shortcode( stripslashes( $non_user_body ) );

		$email_subject_user = str_ireplace( '%User%', 'You', $email_subject_user );
		$email_subject_user = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $email_subject_user );
		$email_subject_user = str_ireplace( '%User Last Name%', html_entity_decode( $user->last_name ), $email_subject_user );
		$email_subject_user = str_ireplace( '%User Email%', $user->user_email, $email_subject_user );
		$email_subject_user = str_ireplace( '%Group Name%', $ugroups, $email_subject_user );
		$email_subject_user = str_ireplace( '%Course Name%', $setup_parameters['course-name'], $email_subject_user );
		$email_subject_user = do_shortcode( stripslashes( $email_subject_user ) );

		$email_subject = str_ireplace( '%User%', html_entity_decode( $user->display_name ), $email_subject );
		$email_subject = str_ireplace( '%User First Name%', html_entity_decode( $user->first_name ), $email_subject );
		$email_subject = str_ireplace( '%User Last Name%', html_entity_decode( $user->last_name ), $email_subject );
		$email_subject = str_ireplace( '%User Email%', $user->user_email, $email_subject );
		$email_subject = str_ireplace( '%Group Name%', $ugroups, $email_subject );
		$email_subject = str_ireplace( '%Course Name%', $setup_parameters['course-name'], $email_subject );
		$email_subject = do_shortcode( stripslashes( $email_subject ) );

		$email_message = do_shortcode( stripslashes( $email_message ) );
		$email_message = wpautop( $email_message );

		$email_params['msg'] .= $email_message;

		$email_params['subject'] = stripslashes( $email_subject_user );

		//Sending Email To User!
		$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
		if ( $change_content_type ) {
			add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
		}

		$enable_mail_user = apply_filters( 'uo_enable_mail_user_course_certificates', true, $email_params );
		if( true === $enable_mail_user ){
			wp_mail( $email_params['email'], $email_params['subject'], $email_params['msg'], $headers, $file );
		}

		if ( 'on' === $is_admin ) {
			$non_user_body       = str_ireplace( '%Group Name%', $ugroups, $non_user_body );
			$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
			if ( $change_content_type ) {
				add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
			}
			$non_user_body = wpautop( $non_user_body );
			wp_mail( get_bloginfo( 'admin_email' ), $email_subject, $non_user_body, $headers, $file );
		}

		if ( 'on' === $is_group_admin ) {
			$get_leaders       = [];
			$get_course_groups = learndash_get_course_groups( $setup_parameters['course-id'], true );
			$user_groups       = learndash_get_users_group_ids( $user->ID, true );
			if ( ! empty( $get_course_groups ) && ! empty( $user_groups ) ) {
				$results = array_intersect( $get_course_groups, $user_groups );
				if ( $results ) {
					foreach ( $results as $group ) {
						$has_group_leader = learndash_get_groups_administrators( $group, true );
						if ( ! empty( $has_group_leader ) ) {
							foreach ( $has_group_leader as $leader ) {
								if ( learndash_is_group_leader_of_user( $leader->ID, $user->ID ) ) {
									$ll                      = get_user_by( 'ID', $leader->ID );
									$get_leaders[ $group ][] = $ll->user_email;
								}
							}
						}
					}
				}
			}

			if ( ! empty( $get_leaders ) ) {
				foreach ( $get_leaders as $key => $value ) {
					$email_subject = str_ireplace( '%Group Name%', html_entity_decode( get_the_title( $key ) ), $email_subject );
					$non_user_body = str_ireplace( '%Group Name%', html_entity_decode( get_the_title( $key ) ), $non_user_body );
					if ( apply_filters( 'uo_apply_wp_mail_content_type', true ) ) {
						add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
					}
					$non_user_body = wpautop( $non_user_body );
					wp_mail( $value, $email_subject, $non_user_body, $headers, $file );
				}
			}
		}

		if ( ! empty( $cc_emails ) ) {
			$email_message = str_ireplace( '%Group Name%', $ugroups, $email_message );
			if ( apply_filters( 'uo_apply_wp_mail_content_type', true ) ) {
				add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
			}
			$email_message = wpautop( $email_message );
			wp_mail( $cc_emails, $email_subject, $email_message, $headers, $file );
		}

		if ( ( 'on' === self::get_settings_value( 'uncanny-course-certificate-dont-store', __CLASS__ ) ) && file_exists( $file ) ) {
			unlink( $file );
		}

	}

	/**
	 * @param $course_id
	 * @param $user_id
	 *
	 * @return array
	 */
	public static function setup_parameters( $course_id, $user_id ) {
		$meta             = get_post_meta( $course_id, '_sfwd-courses', true );
		$setup_parameters = array();

		$setup_parameters['userID']            = $user_id;
		$setup_parameters['course-id']         = $course_id;
		$setup_parameters['course-name']       = html_entity_decode( get_the_title( $course_id ) );
		$setup_parameters['print-certificate'] = 0;

		if ( is_array( $meta ) && ! empty( $meta ) && key_exists( 'sfwd-courses_certificate', $meta ) && ! empty( $meta['sfwd-courses_certificate'] ) ) {
			//Setting Certificate Post ID
			$setup_parameters['certificate-post'] = $meta['sfwd-courses_certificate'];
		}

		if ( empty( $setup_parameters['certificate-post'] ) ) {
			return $setup_parameters;
		}

		$setup_parameters['print-certificate'] = 1;

		return apply_filters( 'uo_course_completion_setup_parameters', $setup_parameters, $course_id, $user_id, $setup_parameters['certificate-post'] );
	}

	/**
	 * @param $args
	 *
	 * @return string
	 */
	public static function generate_pdf( $args ) {

		$builder = new CertificateBuilder();

		if ( $builder->created_with_builder( $args['certificate_post'] ) ) {
			$pdf = $builder->generate_pdf( $args, 'course' );
		} else {
			$pdf = Tcpdf_Certificate_Code::generate_pdf( $args, 'course' );
		}

		return $pdf;
	}

	/**
	 * @return string
	 */
	public static function mail_content_type() {
		return 'text/html';
	}
}
