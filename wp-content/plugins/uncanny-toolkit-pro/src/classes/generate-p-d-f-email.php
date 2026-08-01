<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;
use uncanny_pro_toolkit\CertificateBuilder;

/**
 * Class GeneratePDFEmail
 * @package uncanny_pro_toolkit
 */
class GeneratePDFEmail extends toolkit\Config implements toolkit\RequiredFunctions {
	/**
	 * @var
	 */
	public static $current_time_stamp;

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
			add_filter( 'learndash_quiz_email', array( __CLASS__, 'schedule_generate_quiz_certificate' ), 999, 2 );

			add_action( 'uo_scheduled_learndash_quiz_completed', array( __CLASS__, 'uo_scheduled_func' ), 999, 2 );

			add_action( 'learndash_quiz_completed', array( __CLASS__, 'send_pdf_when_quiz_is_completed' ), 10, 2 );

		}

	}

	/**
	 *
	 *
	 * @static
	 * @return mixed
	 */
	public static function dependants_exist() {
		/* Checks for LearnDash */
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		return true;
	}

	/**
	 * @param $user_id
	 * @param $meta_key
	 */
	public static function uo_scheduled_func( $user_id, $meta_key ) {
		$data            = get_user_meta( $user_id, $meta_key, true );
		$data['user_id'] = $user_id;
		delete_user_meta( $user_id, $meta_key );
		self::send_cert_pdf( $data );
	}

	/**
	 * @param $email_params
	 *
	 * @return string|null|void
	 */
	public static function send_cert_pdf( $email_params ) {
		$current_user      = isset( $email_params['user_id'] ) ? new \WP_User( $email_params['user_id'] ) : wp_get_current_user();
		$cc                = '';
		$file              = '';
		$http_link_to_file = '';
		$setup_parameters  = '';
		$current_time      = isset( $email_params['time'] ) ? $email_params['time'] : time();

		if ( isset( $email_params['post'] ) ) {
			$_post = $email_params['post'];
		} elseif ( isset( $_POST ) ) {
			$_post = $_POST;
		}

		if ( isset( $email_params['email_params'] ) ) {
			$email_params = $email_params['email_params'];
		}

		//        the certificate_link function uses the gobal post which is not set for this ajax call
		//        so we have to set it from the $_post values

		if ( ! isset( $_post['quiz'] ) ) {
			return $email_params;
		}
		$post    = get_post( absint( $_post['quiz'] ) );
		$quiz_id = $post->ID;

		/* Setting up variables for PDF by passing Quiz ID ($post->ID) and current logged in user ID */
		$setup_parameters = self::setup_parameters( $post->ID, $current_user->ID, $_post );

		/* IF Print Certificate is allowed ( logic grabbed from Quiz Pro Print Certificate Part ) */
		if ( 1 !== (int) $setup_parameters['print-certificate'] ) {
			return $email_params;
		}

		self::$current_time_stamp = $current_time;

		do_action( 'uo_quiz_completion_time', self::$current_time_stamp, $post->ID );

		$certificate_post = $setup_parameters['certificate-post'];
		$save_path        = apply_filters( 'uo_quiz_certificate_save_path', WP_CONTENT_DIR . '/uploads/user-certificates/' );
		$completion_time  = self::$current_time_stamp;
		$quiz_title       = html_entity_decode( get_the_title( $post->ID ) );
		/* Creating a fileName that is going to be stored on the server. Certificate-QUIZID-USERID-NONCE_String */
		$file_name = sanitize_title( $current_user->ID . '-' . $quiz_title . '-' . wp_create_nonce( $completion_time ) );
		$file_name = apply_filters( 'uo_quiz_completion_certificate_filename', $file_name, $current_user->ID, $quiz_id, $certificate_post, self::$current_time_stamp );

		if ( ! file_exists( $save_path ) && ! mkdir( $save_path, 0755 ) && ! is_dir( $save_path ) ) {
			throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $save_path ) );
		}

		$generate_pdf_args = apply_filters(
			'uo_quiz_completion_generate_pdf_args',
			array(
				'certificate_post' => $certificate_post,
				'save_path'        => $save_path,
				'file_name'        => $file_name,
				'quiz_id'          => $quiz_id,
				'completion_time'  => $completion_time,
				'parameters'       => $setup_parameters,
				'current_user'     => $current_user,
				'user'             => $current_user,
			)
		);

		$uo_generate_quiz_certs = apply_filters( 'uo_generate_quiz_certificate', true, $generate_pdf_args, $quiz_id, $current_user->ID );

		if ( $uo_generate_quiz_certs ) {
			$file = self::generate_pdf( $generate_pdf_args );
		}

		if ( apply_filters( 'uo_quiz_completion_add_certificate_attached', true ) ) {
			$email_params['msg'] .= "\n\n";
			if ( is_array( $file ) && isset( $file['error'] ) ) {
				$email_params['msg'] .= $file['error'];
			} else {
				$email_params['msg'] .= esc_attr__( 'Your certificate is attached with this email.', 'uncanny-pro-toolkit' );
			}
		}

		$certificate_id = $certificate_post;
		$path_on_server = $file;
		$quiz_post      = $post;
		$quiz_results   = $_post['results']['comp'];
		$current_time   = self::$current_time_stamp;
		do_action( 'uo_quiz_certificate', $path_on_server, $quiz_post, $quiz_results, $certificate_id, $current_user, $current_time );

		//$http_link_to_file = WP_CONTENT_URL . '/uploads/user-certificates/' . $file_name . '.pdf';
		$http_link_to_file = apply_filters( 'uo_quiz_certificate_http_url', WP_CONTENT_URL . '/uploads/user-certificates/' . $file_name . '.pdf' );

		do_action( 'uo_quiz_certificate_url', $http_link_to_file, $quiz_post, $quiz_results, $certificate_id, $current_user, $current_time );

		$course_id = $setup_parameters['course-id'];
		$meta_name = '_sfwd-quizzes-pdf-quiz-' . $course_id;

		//Retrieve any existing certificates from USER
		$certs         = array();
		$current_certs = get_user_meta( $current_user->ID, $meta_name, true );

		if ( ! empty( $current_certs ) ) {
			$current_certs[][ self::$current_time_stamp ] = $http_link_to_file;
			update_user_meta( $current_user->ID, $meta_name, $current_certs );
		} else {
			$certs[][ self::$current_time_stamp ] = $http_link_to_file;
			add_user_meta( $current_user->ID, $meta_name, $certs );
		}

		if ( empty( $file ) ) {
			return $email_params;
		}

		$from_name_override  = self::get_settings_value( 'uncanny-course-certificate-from-name', __CLASS__ );
		$from_email_override = self::get_settings_value( 'uncanny-course-certificate-from-email', __CLASS__ );
		$from_name           = empty( $from_name_override ) ? get_bloginfo( 'name' ) : $from_name_override;
		$from_email          = empty( $from_email_override ) ? get_bloginfo( 'admin_email' ) : $from_email_override;
		$headers             = array();

		/* Sending Final Email with Attachment & PDF Link! */
		if ( array_key_exists( 'headers', $email_params ) && ! empty( $email_params['headers'] ) ) {
			$headers = $email_params['headers'];
		} else {
			$headers = array( "From:{$from_name} <{$from_email}>" );
		}

		// if we're overriding name and email.
		if ( ! empty( $from_name_override ) && ! empty( $from_email_override ) ) {
			$headers = array( "From: {$from_name_override} <{$from_email_override}>" );
		}

		$user           = isset( $current_user ) ? $current_user : wp_get_current_user();
		$is_admin       = self::get_settings_value( 'uncanny-pdf-certificate-admin', __CLASS__ );
		$is_group_admin = self::get_settings_value( 'uncanny-pdf-certificate-group-leader', __CLASS__ );
		$group_msg      = self::get_settings_value( 'uncanny-pdf-certificate-email-body', __CLASS__ );
		$group_sub      = self::get_settings_value( 'uncanny-pdf-certificate-subject-line', __CLASS__ );
		$cc             = self::get_settings_value( 'uncanny-pdf-certificate-cc-emails', __CLASS__ );

		if ( true === strpos( $cc, ',' ) ) {
			$cc = explode( ',', $cc );
		}

		if ( empty( $group_msg ) ) {
			$group_msg = "%User% in Group %Group Name% has earned a certificate for completing %Quiz Name%.\r\n\r\nScore: \$result";
		}

		if ( empty( $group_sub ) ) {
			$group_sub = '%User% has earned a certificate';
		}

		$user_groups = learndash_get_users_group_ids( $user->ID, true );
		$ugroups     = array();
		if ( $user_groups ) {
			foreach ( $user_groups as $gr ) {
				$ugroups[] = html_entity_decode( get_the_title( $gr ) );
			}
			$ugroups = implode( ', ', $ugroups );
		} else {
			$ugroups = '';
		}
		$group_msg = str_ireplace( '%First Name%', html_entity_decode( $user->first_name ), $group_msg );
		$group_msg = str_ireplace( '%Last Name%', html_entity_decode( $user->last_name ), $group_msg );
		$group_msg = str_ireplace( '%User%', html_entity_decode( $user->display_name ), $group_msg );
		$group_msg = str_ireplace( '%User Email%', $user->user_email, $group_msg );
		$group_msg = str_ireplace( '%Quiz Name%', $setup_parameters['quiz-name'], $group_msg );
		$group_msg = str_ireplace( '$result', $setup_parameters['result'] . '%', $group_msg );
		$group_msg = str_ireplace( '%Group Name%', $ugroups, $group_msg );
		$group_msg = do_shortcode( stripslashes( $group_msg ) );

		$group_sub = str_ireplace( '%First Name%', html_entity_decode( $user->first_name ), $group_sub );
		$group_sub = str_ireplace( '%Last Name%', html_entity_decode( $user->last_name ), $group_sub );
		$group_sub = str_ireplace( '%User%', html_entity_decode( $user->display_name ), $group_sub );
		$group_sub = str_ireplace( '%User Email%', $user->user_email, $group_sub );
		$group_sub = str_ireplace( '%Group Name%', $ugroups, $group_sub );
		$group_sub = str_ireplace( '%Quiz Name%', $setup_parameters['quiz-name'], $group_sub );
		$group_sub = html_entity_decode( str_ireplace( '$result', $setup_parameters['result'] . '%', $group_sub ) );

		//Sending Email To User!
		$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
		if ( $change_content_type ) {
			add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
		}

		$email_params['msg'] = do_shortcode( stripslashes( $email_params['msg'] ) );
		$email_param_sub     = $email_params['subject'];
		$email_param_sub     = str_ireplace( '$quizname', $setup_parameters['quiz-name'], $email_param_sub );
		$email_param_sub     = str_ireplace( '$userId', $user->ID, $email_param_sub );
		$email_param_sub     = html_entity_decode( str_ireplace( '$username', $user->user_login, $email_param_sub ) );

		$email_param_msg = $email_params['msg'];
		$email_param_msg = str_ireplace( '$quizname', $setup_parameters['quiz-name'], $email_param_msg );
		$email_param_msg = str_ireplace( '$userId', $user->ID, $email_param_msg );
		$email_param_msg = str_ireplace( '$username', $user->user_login, $email_param_msg );
		$email_param_msg = str_ireplace( '$result', $setup_parameters['result'] . '%', $email_param_msg );

		$enable_mail_user = apply_filters( 'uo_enable_mail_user_quiz_certificates', true, $email_params );
		if ( true === $enable_mail_user ) {
			wp_mail( $email_params['email'], $email_param_sub, wpautop( $email_param_msg ), $headers, $file );
		}

		if ( 'on' === $is_admin ) {
			$group_msg           = str_ireplace( '%Group Name%', $ugroups, $group_msg );
			$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
			if ( $change_content_type ) {
				add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
			}
			wp_mail( get_bloginfo( 'admin_email' ), $group_sub, wpautop( $group_msg ), $headers, $file );
		}

		if ( 'on' === $is_group_admin ) {
			$get_leaders       = array();
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
					$group_msg           = str_ireplace( '%Group Name%', html_entity_decode( get_the_title( $key ) ), $group_msg );
					$group_sub           = html_entity_decode( str_ireplace( '%Group Name%', html_entity_decode( get_the_title( $key ) ), $group_sub ) );
					$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
					if ( $change_content_type ) {
						add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
					}
					wp_mail( $value, $group_sub, wpautop( $group_msg ), $headers, $file );

				}
			}
		}

		if ( ! empty( $cc ) ) {
			$group_msg           = str_ireplace( '%Group Name%', $ugroups, $group_msg );
			$change_content_type = apply_filters( 'uo_apply_wp_mail_content_type', true );
			if ( $change_content_type ) {
				add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
			}
			wp_mail( $cc, $group_sub, wpautop( $group_msg ), $headers, $file );
		}

		if ( ( 'on' === self::get_settings_value( 'uncanny-pdf-certificate-dont-store', __CLASS__ ) ) && file_exists( $file ) ) {
			unlink( $file );
		}

		return $email_params;
	}

	/**
	 * @param $id
	 * @param $user_id
	 * @param $get_request_results
	 *
	 * @return array
	 */
	public static function setup_parameters( $id, $user_id, $get_request_results ) {

		$setup_parameters = array();

		$meta = get_post_meta( $id, '_sfwd-quiz' );

		$setup_parameters['userID']    = $user_id;
		$setup_parameters['user']      = get_user_by( 'ID', $user_id );
		$setup_parameters['quiz-id']   = $id;
		$setup_parameters['quiz-name'] = html_entity_decode( get_the_title( $id ) );
		// Get course id from quiz data - $get_request_results.
		$maybe_course_id = key_exists( 'course-id', $get_request_results ) ? $get_request_results['course-id'] : 0;
		// If 'learndash_quiz_completed' is called from \LD_QuizPro::wp_pro_quiz_completed() the course is a WP_Post, else
		// on 'approve_essay' or 'editpost' action, the 'learndash_quiz_completed' is called from learndash_update_quiz_data() the course is an int.
		$setup_parameters['course-id']         = is_a( $maybe_course_id, 'WP_Post' ) ? $maybe_course_id->ID : $maybe_course_id;
		$setup_parameters['print-certificate'] = 0;
		$setup_parameters['timespent']         = $get_request_results['timespent'];
		$setup_parameters['points']            = $get_request_results['results']['comp']['points'];
		$setup_parameters['total-points']      = $get_request_results['results']['comp']['total-points'];
		$setup_parameters['count']             = $get_request_results['count'];

		if ( is_array( $meta ) && ! empty( $meta ) ) {
			$meta = $meta[0];
			if ( is_array( $meta ) && ( ! empty( $meta['sfwd-quiz_certificate'] ) ) ) {
				//Setting Certificate Post ID
				$setup_parameters['certificate-post'] = $meta['sfwd-quiz_certificate'];
				//Setting Course Post ID
				if ( 0 === absint( $setup_parameters['course-id'] ) ) {
					$setup_parameters['course-id'] = $meta['sfwd-quiz_course'];
				}
			}
		}

		if ( empty( $setup_parameters['certificate-post'] ) ) {
			return $setup_parameters;
		}

		$result                = $get_request_results['results']['comp']['result'];
		$certificate_threshold = ( learndash_get_setting( $id, 'threshold' ) * 100 );

		$setup_parameters['result']                = $result;
		$setup_parameters['certificate_threshold'] = $certificate_threshold;

		if ( ( isset( $result ) && $result >= $certificate_threshold ) ) {
			// All Set. User & Quiz good to go to print pdf certificate.
			$setup_parameters['print-certificate'] = 1;
		}

		return apply_filters( 'uo_quiz_completion_setup_parameters', $setup_parameters, $id, $user_id, $setup_parameters['certificate-post'] );
	}

	/**
	 * @param $args
	 *
	 * @return string
	 */
	public static function generate_pdf( $args ) {

		$builder = new CertificateBuilder();

		if ( $builder->created_with_builder( $args['certificate_post'] ) ) {
			$pdf = $builder->generate_pdf( $args, 'quiz' );
		} else {
			$pdf = Tcpdf_Certificate_Code::generate_pdf( $args, 'quiz' );
		}

		return $pdf;
	}

	/**
	 * @param $atts
	 * @param $quiz
	 *
	 * @return string
	 */
	public static function schedule_generate_quiz_certificate( $atts, $quiz ) {

		$pass_args = array(
			'email_params' => $atts,
			'post'         => $_POST,
			'time'         => time(),
		);

		//Pass an empty array to the filter so LD doesn't send its emails.
		return apply_filters( 'uo_quiz_completion_certificate_email_args', array(), $atts, $pass_args );
	}

	/**
	 *
	 * @static
	 * @return mixed
	 */
	public static function get_details() {
		$module_id = 'email-quiz-certificates';

		$class_title = esc_html__( 'Email Quiz Certificates', 'uncanny-pro-toolkit' );

		$kb_link = 'https://www.uncannyowl.com/knowledge-base/send-quiz-certificates-by-email/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Sends a copy of certificates earned from quiz completion and saves certificates on the server.', 'uncanny-pro-toolkit' );

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
	 * @param String
	 *
	 * @return array || string Return either false or settings html modal
	 *
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array(

			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Use Cron to send certificate', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-quiz-certificate-use-cron',
			),
			array(
				'type'       => 'html',
				'inner_html' => sprintf(
					'<h4 style="margin:0">To use this module, email notifications to the learner must be enabled for each quiz that contains a certificate.  To customize the content of this email, <a target="_blank" href="%s">click here</a>.</h4>',
					admin_url( 'admin.php?page=quizzes-options#learndash_settings_quizzes_email_quizzes_email' )
				),
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Do not store certificates on server', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-pdf-certificate-dont-store',
			),
			array(
				'type'       => 'html',
				'inner_html' => 'By default, certificates are going to be stored at: &lt;site root&gt;/wp-content/uploads/user-certificates/',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Send Certificate to Site Admin?', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-pdf-certificate-admin',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Send Certificate to Group Leader(s)?', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-pdf-certificate-group-leader',
			),
			array(
				'type'       => 'html',
				'inner_html' => __( '<h5>Only set <em>From Name</em> <strong>and</strong> <em>From Email</em> fields below, if you wish to override the default quiz email settings.</h5>', 'uncanny-toolkit-pro' ),
			),
			array(
				'type'        => 'text',
				'label'       => esc_html__( 'From Name', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-from-name',
			),
			array(
				'type'        => 'text',
				'label'       => esc_html__( 'From Email', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-course-certificate-from-email',
			),
			array(
				'type'        => 'text',
				'placeholder' => 'jon@doe.com, doe@jon.com',
				'label'       => esc_html__( 'CC Certificate To (comma separated)', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-pdf-certificate-cc-emails',
			),
			array(
				'type'        => 'text',
				'placeholder' => '%User% has earned a certificate',
				'label'       => esc_html__( 'Admin/Group Leader Email Subject', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-pdf-certificate-subject-line',
			),
			array(
				'type'        => 'textarea',
				'placeholder' => "%User% in Group %Group Name% has earned a certificate for completing %Quiz Name%.\r\n\r\nScore: \$result",
				'label'       => esc_html__( 'Admin/Group Leader Email Body', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-pdf-certificate-email-body',
			),
			array(
				'type'       => 'html',
				'inner_html' => '<strong>Available variables for email subject & body</strong><br /><ul><li><strong>%First Name%</strong> &mdash; Prints User First Name</li><li><strong>%Last Name%</strong> &mdash; Prints User Last Name</li><li><strong>%User%</strong> &mdash; Prints User Screen Name</li><li><strong>%Group Name%</strong> &mdash; Prints Group Name <!--<em>Only Available for Group Leader</em>--></li><li><strong>%Quiz Name%</strong> &mdash; Prints Quiz Name</li><li><strong>%User Email%</strong> &mdash; Prints User Email</li><li><strong>$result</strong> &mdash; Prints Quiz result</li></ul>',
			),
		);

		// Build html
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
	 * @return string
	 */
	public static function mail_content_type() {
		return 'text/html';
	}

	/**
	 * @param $quizdata
	 * @param $user
	 *
	 * @return void
	 */
	public static function send_pdf_when_quiz_is_completed( $quizdata, $user ) {

		$args = array(
			'post'    => array(
				'quiz'            => $quizdata['quiz'],
				'quiz-id'         => $quizdata['quiz'],
				'course-id'       => $quizdata['course'],
				'timespent'       => $quizdata['timespent'],
				'completion_time' => $quizdata['completed'],
				'count'           => $quizdata['count'],
				'results'         => array(
					'comp' => array(
						'points'       => $quizdata['points'],
						'total-points' => $quizdata['total_points'],
						'result'       => $quizdata['percentage'],
					),
				),
			),
			'user_id' => $user->ID,
		);

		$ld_quiz_user_email_settings = \LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Quizzes_User_Email' );

		$args['email']   = $user->user_email;
		$args['msg']     = $ld_quiz_user_email_settings['user_mail_message'];
		$args['subject'] = $ld_quiz_user_email_settings['user_mail_subject'];
		$args['headers'] = '';

		if ( self::is_cron_enabled() ) {

			self::schedule_email( $user->ID, $quizdata['quiz'], $args );

			return;
		}

		self::send_cert_pdf( $args );
	}

	/**
	 * @return bool
	 */
	public static function is_cron_enabled() {

		$use_cron = self::get_settings_value( 'uncanny-quiz-certificate-use-cron', __CLASS__ );

		if ( empty( $use_cron ) ) {
			return false;
		}

		if ( 'on' !== $use_cron ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $user_id
	 * @param $quiz_id
	 * @param $args
	 *
	 * @return void
	 */
	public static function schedule_email( $user_id, $quiz_id, $args ) {

		$meta_key = "uo_scheduled_quiz_completed_$quiz_id";

		update_user_meta( $user_id, $meta_key, $args );

		$random_number = wp_rand( 5, 20 );

		$next_run = strtotime( '+' . $random_number . ' second' );

		wp_schedule_single_event(
			$next_run,
			'uo_scheduled_learndash_quiz_completed',
			array(
				$user_id,
				$meta_key,
			)
		);
	}
}
