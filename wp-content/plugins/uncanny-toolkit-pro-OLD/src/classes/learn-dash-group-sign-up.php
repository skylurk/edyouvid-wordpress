<?php

namespace uncanny_pro_toolkit;

use uncanny_learndash_groups\SharedFunctions;
use uncanny_learndash_groups\Utilities;
use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class LearnDashGroupSignUp
 *
 * @package uncanny_pro_toolkit
 */
class LearnDashGroupSignUp extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );

		if ( wp_doing_ajax() ) {
			//Fire this event when user is registered with Gravity Forms AJAX feed
			add_action( 'gform_user_registered', array( $this, 'add_to_group_on_ajax_gf_user_registered' ), 10, 4 );
		}
	}

	/*
	 * Initialize frontend actions and filters
	 */
	/**
	 * @return void
	 */
	public static function run_frontend_hooks() {
		if ( true === self::dependants_exist() ) {
			//Updated Groups Custom Post Type
			//add_filter( 'register_post_type_args', array( __CLASS__, 'learndash_post_args_groups' ), 10, 2 );
			add_shortcode( 'uo_group_status', array( __CLASS__, 'group_status' ) );
			add_shortcode( 'uo_group_login', array( __CLASS__, 'groups_login_form' ) );
			add_shortcode( 'uo_group_organization', array( __CLASS__, 'group_org_details' ) );

			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'uo_group_sign_up_style' ), 99 );

			//Related to adding Meta Boxes
			add_action( 'load-post.php', array( __CLASS__, 'add_metabox' ) );
			add_action( 'load-post-new.php', array( __CLASS__, 'add_metabox' ) );

			//Single template for Groups
			//add_filter( 'single_template', array( __CLASS__, 'add_group_single_template' ) );

			//Only fire if Join this Group button is clicked
			if ( isset( $_POST ) && filter_has_var( INPUT_POST, 'uncanny_pro_toolkit_join_group_nonce' ) ) {
				add_action( 'init', array( __CLASS__, 'uncanny_pro_toolkit_join_group' ), 99 );
			}

			if ( defined( 'THEME_MY_LOGIN_VERSION' ) ) {
				add_action( 'init', array( __CLASS__, 'tml_add_fields' ), 10 );
			}

			//Only fire if default registration is used
			if ( isset( $_POST ) && filter_has_var( INPUT_POST, 'uncanny_group_signup_register_nonce' ) ) {
				add_action( 'init', array( __CLASS__, 'uncanny_group_signup_add_new_member' ) );
			}
			//Fire this event when user is registered to assign them proper group
			add_action( 'user_register', array( __CLASS__, 'add_to_group_on_user_registered' ), 10, 4 );
			//Fire this even if user is already a member and logged in from page to assign group ( after login ).
			add_action( 'wp_login', array( __CLASS__, 'add_to_group_on_login' ), 10, 2 );
			//Apply validation before registration.
			add_filter( 'registration_errors', array( __CLASS__, 'validate_on_group_registration' ), 10, 3 );
			// Apply conditional filter for hidding LD content
			$uo_hide_ld_content = self::get_page_link_settings( 'uo_hide_native_learndash_content' );
			if ( 'on' === $uo_hide_ld_content ) {
				add_filter( 'learndash_content', array( __CLASS__, 'restrict_learndash_content' ), 10, 2 );
			}

			// Register signup URL.
			add_action( 'init', array( __CLASS__, 'register_group_signup_permalink' ), 10, 1 );
			add_action( 'query_vars', array( __CLASS__, 'register_group_signup_query_vars' ), 99, 1 );

			// Handle Groups signup template.
			add_filter( 'template_include', array( __CLASS__, 'register_signup_template' ), 99, 1 );
			add_filter( 'body_class', array( __CLASS__, 'signup_body_class' ), 99, 2 );

			// Add Signup URL link.
			add_filter( 'post_row_actions', array( __CLASS__, 'render_signup_url_row_action' ), 2000, 2 );
			add_filter( 'page_row_actions', array( __CLASS__, 'render_signup_url_row_action' ), 2000, 2 );

			if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
				add_filter( 'ulgm_signup_url', array( __CLASS__, 'wpml_ulgm_signup_url' ), 2000, 2 );
				add_filter( 'ulgm_admin_signup_link', array( __CLASS__, 'wpml_ulgm_admin_signup_link' ), 2000, 3 );
			}
		}
	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
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
	 * @param $setting_name
	 *
	 * @return string
	 */
	public static function get_page_link_settings( $setting_name ) {
		return self::get_settings_value( $setting_name, __CLASS__ );
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'group-registration';

		$class_title = esc_attr__( 'Group Registration', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/group-sign-up/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_attr__( 'Allow users to add themselves directly to LearnDash Groups on registration by assigning each group a unique registration URL. Users can also change or add groups themselves by visiting group URLs.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-user-plus"></i><span class="uo_pro_text">PRO</i>';

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
	 * @param $class_title
	 *
	 * @return array
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array(

			array(
				'type'       => 'radio',
				'label'      => 'Allow users to join multiple LearnDash Groups?',
				'radio_name' => 'uo_groups_limitation',
				'radios'     => array(
					array(
						'value' => 'yes',
						'text'  => 'Yes',
					),
					array(
						'value' => 'no',
						'text'  => 'No',
					),
				),
			),

			array(
				'type'        => 'checkbox',
				'label'       => 'Hide native LearnDash content (LearnDash 3.2+)',
				'option_name' => 'uo_hide_native_learndash_content',
			),

			array(
				'type'        => 'text',
				'label'       => 'Existing Group Member Link Text',
				'option_name' => 'uo_groups_page_link_text',
			),

			array(
				'type'        => 'text',
				'label'       => 'Existing Member URL for Link',
				'option_name' => 'uo_groups_page_link',
			),

			array(
				'type'        => 'text',
				'label'       => 'Redirect URL After Registration',
				'option_name' => 'uo_groups_redirect_link_after_signup',
			),

			array(
				'type'        => 'text',
				'label'       => 'Rename Organization Details Label',
				'option_name' => 'uo_groups_organization_details',
				'placeholder' => 'Organization Info',
			),

			array(
				'type'        => 'text',
				'label'       => 'Rename Organization Name Label',
				'option_name' => 'uo_groups_organization_details_name',
				'placeholder' => 'Organization Name',
			),

			array(
				'type'        => 'text',
				'label'       => 'Rename Organization Contact Label',
				'option_name' => 'uo_groups_organization_details_contact',
				'placeholder' => 'Organization Contact',
			),

			array(
				'type'        => 'text',
				'label'       => 'Rename Organization Email Label',
				'option_name' => 'uo_groups_organization_details_email',
				'placeholder' => 'Organization Email',
			),

			array(
				'type'        => 'text',
				'label'       => 'Rename Organization Phone Label',
				'option_name' => 'uo_groups_organization_details_phone',
				'placeholder' => 'Organization Phone',
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
	 *
	 */
	public static function uo_group_sign_up_style() {
		global $wp_query;

		if ( self::is_ulgm_signup()  ) {
			wp_enqueue_style( 'group-sign-up', plugins_url( '/assets/legacy/frontend/css/group-sign-up.css', dirname( __FILE__ ) ), array(), '1.5.0' );
		}

	}

	/**
	 * Add single-group.php to display admin or sign up page
	 *
	 * @param $single_template
	 *
	 * @return string
	 */
	public static function add_group_single_template( $single_template ) {
		global $post;

		if ( 'groups' === $post->post_type ) {
			$single_template = self::get_template( 'single-group.php', dirname( dirname( __FILE__ ) ) . '/src' );
			$single_template = apply_filters( 'uo_single_group_template', $single_template );
		}

		return $single_template;

	}

	/**
	 * Updating Groups Custom Post Type
	 *
	 * @param $args
	 * @param $post_type
	 *
	 * @return mixed
	 */
	public static function learndash_post_args_groups( $args, $post_type ) {
		if ( 'groups' === $post_type ) {
			$args['rewrite']            = array(
				'slug'       => 'sign-up',
				'with_front' => false,
			);
			$args['publicly_queryable'] = true;

			if( ! isset( $args['supports'] ) ){
				$args['supports'] = array();
			}

			$args['supports']           = array_merge( (array) $args['supports'], array( 'slug' ) );
			$args['public']             = true;

			flush_rewrite_rules();
		}

		return $args;
	}

	/**
	 *
	 * This action is fired if User is logged &
	 * is not a member of this group.
	 */
	public static function uncanny_pro_toolkit_join_group() {
		if ( false !== wp_verify_nonce( filter_input( INPUT_POST, 'uncanny_pro_toolkit_join_group_nonce' ), 'uncanny_pro_toolkit_join_group' ) ) {
			$success_case = self::set_users_group( absint( filter_input( INPUT_POST, 'uncanny_pro_toolkit_join_group_id' ) ) );
			$errors       = self::uncanny_group_signup_errors()->get_error_messages();

			// only create the user in if there are no errors
			if ( empty( $errors ) ) {
				wp_safe_redirect( filter_input( INPUT_POST, '_wp_http_referer' ) . '&joined=true&msg=' . $success_case . '&_wpnonce=' . wp_create_nonce( current_time( 'timestamp' ) ) );
				exit;
			}
		};
	}

	/**
	 * Set the group that a user belongs to
	 * Removes them from all other groups ( if single option is selected )
	 *
	 * @param $new_group_id
	 * @param null $user_id
	 */
	public static function set_users_group( $new_group_id, $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$groups      = array();
		$limitations = self::get_settings_value( 'uo_groups_limitation', __CLASS__, 'yes' );
		$user_groups = learndash_get_users_group_ids( $user_id );

		// check if groups installed and in new group there is any seat available.
		$seat_check            = true;
		$success_case          = 1;
		$code                  = false;
		$is_group_seats_active = false;
		if ( function_exists( 'ulgm' ) ) {
			$code_group_id = ulgm()->group_management->seat->get_code_group_id( $new_group_id );
			if ( $code_group_id ) {
				$is_group_seats_active = true;
				$remaining_seats       = ulgm()->group_management->seat->remaining_seats( $new_group_id );
				if ( 0 === $remaining_seats ) {
					$seat_check = false;
				} else {
					$code = ulgm()->group_management->get_sign_up_code_from_group_id( $new_group_id );
				}
			}
		}
		if ( false === $seat_check ) {
			self::uncanny_group_signup_errors()->add( 'group_join_error', esc_html__( 'Sorry, the group you are trying to join is full.', 'uncanny-pro-toolkit' ) );

			return false;
		}

		//new group has seat management enabled, with available seat
		if ( 'no' === $limitations && ! empty( $user_groups ) ) { //SET ONLY 1 GROUP
			// remove all groups if any
			foreach ( $user_groups as $user_group ) {
				// Both groups have seat management enabled
				// OR
				// Previous group has seat management enabled
				if ( function_exists( 'ulgm' ) ) {
					$code_group_id = ulgm()->group_management->seat->get_code_group_id( $user_group );

					if ( null === $code_group_id ) {
						continue;
					}
					if ( ! self::is_user_group_inprogress( $user_id, $user_group ) ) {
						$old_group_code = ulgm()->group_management->get_user_code( $user_id, $user_group );
						ulgm()->group_management->remove_sign_up_code( $old_group_code, $user_group, true );
						ld_update_group_access( $user_id, $user_group, true );
						$success_case = 2;
					} else {
						self::uncanny_group_signup_errors()->add( 'group_join_error', esc_html__( 'Sorry, you are not allowed to switch groups.', 'uncanny-pro-toolkit' ) );

						return false;
					}
				} else {
					ld_update_group_access( $user_id, $user_group, true );
				}
			}
		} elseif ( 'yes' === $limitations ) { //SET MULTIPLE GROUPS!
			$groups = $user_groups;
		}

		$groups[] = $new_group_id;

		if ( ! $is_group_seats_active || empty( $code ) ) {
			// $code empty is fallback
			ld_update_group_access( $user_id, $new_group_id );
			self::set_unset_learndash_transient( $groups, $user_id );

			return $success_case;
		}

		$status = SharedFunctions::$not_started_status;
		if ( ! class_exists( '\uncanny_learndash_groups\Database' ) ) {
			include_once Utilities::get_include( 'database.php' );
		}
		ulgm()->group_management->set_user_to_code( $user_id, $code, $status );
		update_user_meta( $user_id, 'uo_code_status', $code );
		ulgm()->group_management->set_user_to_group( $user_id, $new_group_id );
		self::set_unset_learndash_transient( $groups, $user_id );

		return $success_case;
	}

	/**
	 * Used for tracking error messages
	 *
	 * @return \WP_Error
	 */
	public static function uncanny_group_signup_errors() {
		static $wp_error; // Will hold global variable safely

		return isset( $wp_error ) ? $wp_error : ( $wp_error = new \WP_Error( null, null, null ) );
	}

	/**
	 * @param $user_id
	 * @param $group_id
	 *
	 * @return bool
	 */
	public static function is_user_group_inprogress( $user_id, $group_id ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		if ( empty( $group_id ) ) {
			return false;
		}

		global $wpdb;

		$group_courses = learndash_group_enrolled_courses( $group_id );
		if ( empty( $group_courses ) ) {
			return false;
		}

		// In-progress
		$q_in_progress = "
						SELECT a.post_id as course_id, user_id
						FROM {$wpdb->prefix}learndash_user_activity a
						WHERE a.activity_type = 'course'
						AND a.activity_completed = 0
						AND ( a.activity_started != 0 || a.activity_updated != 0)
						AND user_id = {$user_id}
						";

		$in_progress = $wpdb->get_results( $q_in_progress );

		if ( empty( $in_progress ) ) {
			return false;
		}

		$in_progress_rearranged = array();

		foreach ( $in_progress as $progress ) {
			$in_progress_rearranged[ (int) $progress->user_id ][ (int) $progress->course_id ] = true;
		}
		// Default progress
		$in_progress = false;

		foreach ( $group_courses as $course_id ) {

			if ( isset( $in_progress_rearranged[ (int) $user_id ][ (int) $course_id ] ) ) {
				$in_progress = true;
			}
		}

		return $in_progress;
	}

	/**
	 * @since 1.1.1 | Hack to delete & set LearnDash's Transient cache so that access to courses are immediate
	 *
	 * @param $groups
	 * @param $user_id
	 */
	private static function set_unset_learndash_transient( $groups, $user_id ) {
		$transient_key         = 'learndash_user_groups_' . $user_id;
		$transient_key_courses = 'learndash_user_courses_' . $user_id;
		delete_transient( $transient_key );
		delete_transient( $transient_key_courses );
		set_transient( $transient_key, $groups, MINUTE_IN_SECONDS );

	}

	/**
	 * If user is registered using any of the methods, Gravity Forms / TML / Default
	 * Fire this function to add on user registration completed
	 *
	 * @param $user_id
	 */
	public static function add_to_group_on_user_registered( $user_id ) {
		if ( isset( $_REQUEST['gid'] ) && absint( $_REQUEST['gid'] ) ) {
			self::set_users_group( absint( $_REQUEST['gid'] ), $user_id );
			// in case user registered by TML or GF
			// Update Ticket#9720- GF unable to add custom meta values because of redirect at 301-306
			if ( ! filter_has_var( INPUT_POST, 'uncanny_group_signup_user_login' ) && ! isset( $_REQUEST['gform_submit'] ) ) {
				$errors = self::uncanny_group_signup_errors()->get_error_messages();
				// Redirect the user in if there are no errors
				if ( empty( $errors ) ) {
					// log the new user in
					$frontEndLogin = \uncanny_learndash_toolkit\Config::get_settings_value( 'uo_frontendloginplus_needs_verifcation', 'FrontendLoginPlus' );
					if ( empty( $frontEndLogin ) || 'on' !== $frontEndLogin ) {
						wp_set_auth_cookie( $user_id );
						wp_set_current_user( $user_id );
					}
					$redirect_url = self::get_page_link_settings( 'uo_groups_redirect_link_after_signup' );
					if ( ! empty( $redirect_url ) ) {
						if ( strpos( $redirect_url, '?' ) !== false ) {
							$redirect_url .= '&' . $_REQUEST['key'] . '&registered';
						} else {
							$redirect_url .= '?' . $_REQUEST['key'] . '&registered';
						}
						wp_redirect( $redirect_url );
					} else {
						$redirect_url = get_permalink();
						if ( strpos( $redirect_url, '?' ) !== false ) {
							$redirect_url .= '&' . $_REQUEST['key'] . '&registered';
						} else {
							$redirect_url .= '?' . $_REQUEST['key'] . '&registered';
						}
						wp_redirect( $redirect_url );
					}
					exit;
				}
			}
		}
	}

	/**
	 * Add to group if user used login form on group sign up page
	 *
	 * @param $user_login
	 * @param string $user
	 */
	public static function add_to_group_on_login( $user_login, $user = '' ) {
		if ( empty( $user ) ) {
			$user = get_user_by( 'login', $user_login );
		}
		if ( filter_has_var( INPUT_POST, 'group_id' ) ) {
			self::set_users_group( filter_input( INPUT_POST, 'group_id' ), $user->ID );
		}

	}

	/**
	 * Default Group Registration Form
	 */
	public static function groups_register_form() {
		// check to make sure user registration is enabled
		//$registration_enabled = get_option( 'users_can_register' );
		//@version 3.3 Ticket#9851
		$registration_enabled = true;

		?>
		<div class="uncanny_group_signup_form-container">
			<?php

			// only show the registration form if allowed
			if ( $registration_enabled ) {
				// show any error messages after form submission
				self::uncanny_group_signup_show_error_messages();
				$form_template = self::get_template( 'group-registration-form.php', dirname( dirname( __FILE__ ) ) . '/src' );
				$form_template = apply_filters( 'uo_group_registration_form_template', $form_template );
				include $form_template;

			} else {
				?>
				<div class="uncanny_group_signup_form-container__error">
					<?php
					echo esc_attr__( 'User registration is not enabled. Contact Site Administrator.', 'uncanny-pro-toolkit' );
					?>
				</div>
				<?php
			}

			?>
		</div>
		<?php
	}

	/**
	 * function to catch all errors for default registration form
	 */
	public static function uncanny_group_signup_show_error_messages() {
		if ( $codes = self::uncanny_group_signup_errors()->get_error_codes() ) {
			echo '<div class="uncanny_group_signup_errors">';
			// Loop error codes and display errors
			foreach ( $codes as $code ) {
				$message = self::uncanny_group_signup_errors()->get_error_message( $code );
				echo '<span class="error"><strong>' . esc_html__( 'Error', 'uncanny-pro-toolkit' ) . '</strong>: ' . $message . '</span><br/>';
			}
			echo '</div>';
		}
	}

	/**
	 * Registration form is submit
	 */
	public static function uncanny_group_signup_add_new_member() {
		if ( filter_has_var( INPUT_POST, 'uncanny_group_signup_user_login' ) && wp_verify_nonce( filter_input( INPUT_POST, 'uncanny_group_signup_register_nonce' ), 'uncanny_group_signup-register-nonce' ) ) {

			do_action( 'uo_group_signup_before_submission');

			$user_login   = sanitize_text_field( filter_input( INPUT_POST, 'uncanny_group_signup_user_login' ) );
			$user_email   = sanitize_email( filter_input( INPUT_POST, 'uncanny_group_signup_user_email' ) );
			$user_first   = sanitize_text_field( filter_input( INPUT_POST, 'uncanny_group_signup_user_first' ) );
			$user_last    = sanitize_text_field( filter_input( INPUT_POST, 'uncanny_group_signup_user_last' ) );
			$user_pass    = filter_input( INPUT_POST, 'uncanny_group_signup_user_pass' );
			$pass_confirm = filter_input( INPUT_POST, 'uncanny_group_signup_user_pass_confirm' );

			if ( username_exists( $user_login ) ) {
				// Username already registered
				self::uncanny_group_signup_errors()->add( 'username_unavailable', esc_html__( 'Username already taken', 'uncanny-pro-toolkit' ) );
			}
			if ( ! validate_username( $user_login ) ) {
				// invalid username
				self::uncanny_group_signup_errors()->add( 'username_invalid', esc_html__( 'Invalid username', 'uncanny-pro-toolkit' ) );
			}
			if ( $user_login == '' ) {
				// empty username
				self::uncanny_group_signup_errors()->add( 'username_empty', esc_html__( 'Please enter a username', 'uncanny-pro-toolkit' ) );
			}
			if ( ! is_email( $user_email ) ) {
				//invalid email
				self::uncanny_group_signup_errors()->add( 'email_invalid', esc_html__( 'Invalid email', 'uncanny-pro-toolkit' ) );
			}
			if ( email_exists( $user_email ) ) {
				//Email address already registered
				self::uncanny_group_signup_errors()->add( 'email_used', esc_html__( 'Email already registered', 'uncanny-pro-toolkit' ) );
			}
			if ( $user_pass == '' ) {
				// passwords do not match
				self::uncanny_group_signup_errors()->add( 'password_empty', esc_html__( 'Please enter a password', 'uncanny-pro-toolkit' ) );
			}
			if ( $user_pass != $pass_confirm ) {
				// passwords do not match
				self::uncanny_group_signup_errors()->add( 'password_mismatch', esc_html__( 'Passwords do not match', 'uncanny-pro-toolkit' ) );
			}

			if ( class_exists( '\uncanny_learndash_groups\SharedFunctions' ) ) {
				if ( isset( $_REQUEST['gid'] ) && absint( $_REQUEST['gid'] ) ) {
					$new_group_id = absint( $_REQUEST['gid'] );
				}
				if ( filter_has_var( INPUT_POST, 'group_id' ) ) {
					$new_group_id = absint( filter_input( INPUT_POST, 'group_id' ) );
				}
				$code_group_id = get_post_meta( $new_group_id, '_ulgm_code_group_id', true );
				if ( $code_group_id ) {
					$remaining_seats = \uncanny_learndash_groups\SharedFunctions::remaining_seats( $new_group_id );
					if ( 0 === $remaining_seats ) {
						self::uncanny_group_signup_errors()->add( 'group_join_error', esc_html__( 'Sorry, the group you are trying to join is full.', 'uncanny-pro-toolkit' ) );
					}
				}
			}

			$errors = self::uncanny_group_signup_errors()->get_error_messages();
			$errors = apply_filters( 'uo_group_signup_errors', $errors, self::uncanny_group_signup_errors() );

			// only create the user in if there are no errors
			if ( empty( $errors ) ) {

				$new_user_id = wp_insert_user(
					array(
						'user_login'      => $user_login,
						'user_pass'       => $user_pass,
						'user_email'      => $user_email,
						'first_name'      => $user_first,
						'last_name'       => $user_last,
						'user_registered' => date( 'Y-m-d H:i:s' ),
						'role'            => get_option( 'uo_groups_default_user_role', get_option( 'default_role', 'subscriber' ) ),
					)
				);
				if ( $new_user_id ) {
					// send an email to the admin alerting them of the registration
					$notification_type = apply_filters( 'uo_group_signup_notification_type', '', $new_user_id );
					wp_new_user_notification( $new_user_id, null, $notification_type );

					// log the new user in
					$frontEndLogin = \uncanny_learndash_toolkit\Config::get_settings_value( 'uo_frontendloginplus_needs_verifcation', 'FrontendLoginPlus' );
					if ( empty( $frontEndLogin ) || 'on' !== $frontEndLogin ) {
						wp_set_auth_cookie( $new_user_id );
						wp_set_current_user( $new_user_id, $user_login );
					}

					$errors = self::uncanny_group_signup_errors()->get_error_messages();

					// only create the user in if there are no errors
					if ( empty( $errors ) ) {
						$redirect_url = self::get_page_link_settings( 'uo_groups_redirect_link_after_signup' );
						if ( ! empty( $redirect_url ) ) {
							wp_redirect( $redirect_url . '?' . $_REQUEST['key'] . '&registered' );
						} else {
							wp_redirect( get_permalink() . '?' . $_REQUEST['key'] . '&registered' );
						}
						exit;
					}
				}
			}
		}
	}

	/**
	 * Show Login Form
	 */
	public static function groups_login_form( $context = '' ) {
		if( true !== apply_filters( 'uo_show_groups_login_form', true, $context ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			ob_start();
			esc_html_e( 'You\'re already signed in! We hope you\'re enjoying our courses.', 'uncanny-pro-toolkit' );
			$page_link = self::get_page_link_settings( 'uo_groups_page_link' );
			$page_text = self::get_page_link_settings( 'uo_groups_page_link_text' );
			if ( self::is_url( $page_link ) ) {
				printf( '<br /><a href="%s">%s</a>', esc_url( $page_link ), esc_html( $page_text ) );
			}

			return ob_get_clean();
		} else {
			ob_start();
			$form_template = self::get_template( 'groups-login-form.php', dirname( dirname( __FILE__ ) ) . '/src' );
			$form_template = apply_filters( 'uo_group_login_form_template', $form_template );
			include $form_template;

			return ob_get_clean();
		}
	}

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function is_url( $string ) {
		$domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component //! IDN

		return ( preg_match( "~^(https?)://($domain?\\.)+$domain(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i", $string, $match ) ? strtolower( $match[1] ) : '' ); //! restrict path, query and fragment characters
	}

	/**
	 * Show the Organization Details
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function group_org_details( $atts ) {
		$atts = shortcode_atts(
			array(
				'group_id' => '',
			),
			$atts,
			'uo_group_organization'
		);

		if ( '' === $atts['group_id'] ) {
			$group_id = 0;
			if ( isset($_REQUEST['gid']) ) {
				$group_id = absint( $_REQUEST['gid'] );
			}
		} else {
			$group_id = absint( $atts['group_id'] );
		}

		return self::_group_org_details( $group_id );
	}

	/**
	 * creates the org detail string
	 *
	 * @param $group_id
	 *
	 * @return string
	 */
	private static function _group_org_details( $group_id ) {
		$meta   = get_post_meta( $group_id );
		$prefix = 'uncanny_group_organization_';
		$out    = $before = $after = '';

		//
		$logo = '';
		if ( has_post_thumbnail( $group_id ) ) {
			$logo = get_the_post_thumbnail( $group_id, 'full' );
			$logo = str_replace( 'class="', 'class="uo_white_label_logo ', $logo );
		}

		$default_label_main    = esc_html__( 'Organization Details', 'uncanny-pro-toolkit' );
		$default_label_name    = esc_html__( 'Name', 'uncanny-pro-toolkit' );
		$default_label_contact = esc_html__( 'Contact', 'uncanny-pro-toolkit' );
		$default_label_email   = esc_html__( 'Email', 'uncanny-pro-toolkit' );
		$default_label_phone   = esc_html__( 'Phone', 'uncanny-pro-toolkit' );

		$modify_label_main    = self::get_settings_value( 'uo_groups_organization_details', __CLASS__ );
		$modify_label_name    = self::get_settings_value( 'uo_groups_organization_details_name', __CLASS__ );
		$modify_label_contact = self::get_settings_value( 'uo_groups_organization_details_contact', __CLASS__ );
		$modify_label_email   = self::get_settings_value( 'uo_groups_organization_details_email', __CLASS__ );
		$modify_label_phone   = self::get_settings_value( 'uo_groups_organization_details_phone', __CLASS__ );

		if ( ! empty( $modify_label_main ) ) {
			$default_label_main = stripslashes( $modify_label_main );
		}
		if ( ! empty( $modify_label_name ) ) {
			$default_label_name = stripslashes( $modify_label_name );
		}
		if ( ! empty( $modify_label_contact ) ) {
			$default_label_contact = stripslashes( $modify_label_contact );
		}
		if ( ! empty( $modify_label_email ) ) {
			$default_label_email = stripslashes( $modify_label_email );
		}
		if ( ! empty( $modify_label_phone ) ) {
			$default_label_phone = stripslashes( $modify_label_phone );
		}

		$before .= '<aside id="uo-widgets" class=sidebar-orgnization-details clr">';
		$before .= '<div class="clr widget">';
		$before .= $logo;
		$before .= '<div class="widget-title">' . $default_label_main . '</div>';
		$out    .= '<ul class="' . $prefix . 'org_details">';
		if ( isset( $meta[ $prefix . 'name' ][0] ) ) {
			$out .= sprintf( '<li><strong>%s</strong>: %s</li>', $default_label_name, esc_html( $meta[ $prefix . 'name' ][0] ) );
		}
		if ( isset( $meta[ $prefix . 'contact' ][0] ) ) {
			$out .= sprintf( '<li><strong>%s</strong>: %s</li>', $default_label_contact, esc_html( $meta[ $prefix . 'contact' ][0] ) );
		}
		if ( isset( $meta[ $prefix . 'email' ][0] ) ) {
			$out .= sprintf( '<li><strong>%s</strong>: <a href="mailto:%2$s">%2$s</a></li>', $default_label_email, esc_html( $meta[ $prefix . 'email' ][0] ) );
		}
		if ( isset( $meta[ $prefix . 'phone' ][0] ) ) {
			$out .= sprintf( '<li><strong>%s</strong>: <a href="tel:%2$s">%2$s</a></li>', $default_label_phone, esc_html( $meta[ $prefix . 'phone' ][0] ) );
		}
		$out   .= '</ul>';
		$after .= '</div>';
		$after .= '</aside>';
		if ( substr_count( $out, '<li>' ) > 0 ) {
			$out = $before . $out . $after;
		}

		return $out;
	}

	/**
	 * wraps the org's details block for shortcode [uncanny_group_status]
	 *
	 * @return string
	 */
	public static function group_status() {

		//$out = sprintf( '<h2>%s</h2>', esc_attr__( 'My Group(s)', 'uncanny-pro-toolkit' ) );
		$out = '';

		$user_groups = learndash_get_users_group_ids( get_current_user_id() );
		if ( empty( $user_groups ) ) {
			$out .= sprintf(
				'<p>%s</p>',
				esc_html__(
					'You are not currently a member of any group. If you believe this is an error, please request the registration URL from your organization contact.',
					'uncanny-pro-toolkit'
				)
			);
		} else {
			if ( is_array( $user_groups ) ) {
				foreach ( $user_groups as $user_group ) {
					$out .= self::_group_org_details( $user_group );
				}
			}
		}

		return $out;
	}

	/**
	 *
	 */
	public static function add_metabox() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'metabox' ) );
		add_action( 'save_post', array( __CLASS__, 'uncanny_save_group_metabox' ), 10, 2 );
	}

	/**
	 * adds a metabox to the groups page
	 */
	public static function metabox() {

		add_meta_box(
			'uncanny-learndash-group-sign-up-box',
			esc_html__( 'Organization Details', 'uncanny-pro-toolkit' ),
			array( __CLASS__, 'uncanny_groups_meta_box' ),
			'groups',
			'side',
			'high'
		);
	}

	/**
	 * @param $object
	 * @param $box
	 */
	public static function uncanny_groups_meta_box( $object, $box ) {
		?>

		<?php wp_nonce_field( basename( __FILE__ ), 'uncanny_group_nonce' ); ?>
		<table class="form-table">
			<tr>
				<td>
					<input type="text" class="widefat" name="uncanny_group_organization_name"
						   id="uncanny_group_organization_name"
						   value="<?php echo esc_attr( get_post_meta( $object->ID, 'uncanny_group_organization_name', true ) ); ?>"
						   placeholder="Organization Name"
					/><br/> <span class="description">(optional)</span>
					<hr/>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" class="widefat" name="uncanny_group_organization_contact"
						   id="uncanny_group_organization_contact"
						   value="<?php echo esc_attr( get_post_meta( $object->ID, 'uncanny_group_organization_contact', true ) ); ?>"
						   placeholder="Organization Contact"
					/><br/> <span class="description">(optional)</span>
					<hr/>
				</td>
			</tr>
			<tr>
				<td>
					<input type="email" class="widefat" name="uncanny_group_organization_email"
						   id="uncanny_group_organization_email"
						   value="<?php echo esc_attr( get_post_meta( $object->ID, 'uncanny_group_organization_email', true ) ); ?>"
						   placeholder="Organization Email"
					/><br/> <span class="description">(optional)</span>
					<hr/>
				</td>
			</tr>
			<tr>
				<td>
					<input type="tel" class="widefat" name="uncanny_group_organization_phone"
						   id="uncanny_group_organization_phone"
						   value="<?php echo esc_attr( get_post_meta( $object->ID, 'uncanny_group_organization_phone', true ) ); ?>"
						   placeholder="Organization Phone"
					/><br/> <span class="description">(optional)</span>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * @param $post_id
	 * @param $post
	 *
	 * @return mixed
	 */
	public static function uncanny_save_group_metabox( $post_id, $post ) {

		/* Verify the nonce before proceeding. */
		if ( ! filter_has_var( INPUT_POST, 'uncanny_group_nonce' ) || ! wp_verify_nonce( filter_input( INPUT_POST, 'uncanny_group_nonce' ), basename( __FILE__ ) ) ) {
			return $post_id;
		}

		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );

		/* Check if the current user has permission to edit the post. */
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		/* Get the meta key. */
		$meta_keys = array(
			'uncanny_group_organization_name',
			'uncanny_group_organization_contact',
			'uncanny_group_organization_email',
			'uncanny_group_organization_phone',
		);
		foreach ( $meta_keys as $meta_key ) {
			$new_meta_value      = ( isset( $_POST[ $meta_key ] ) ? esc_html( $_POST[ $meta_key ] ) : '' );
			$existing_meta_value = get_post_meta( $post_id, $meta_key, true );

			if ( $new_meta_value && '' === $existing_meta_value ) {
				add_post_meta( $post_id, $meta_key, $new_meta_value, true );
			} elseif ( $new_meta_value && $new_meta_value !== $existing_meta_value ) {
				update_post_meta( $post_id, $meta_key, $new_meta_value );
			} elseif ( '' === $new_meta_value && $existing_meta_value ) {
				delete_post_meta( $post_id, $meta_key, $existing_meta_value );
			}
		}
	}

	/**
	 * check if the current user is a member of the current group
	 * Show a join button if not
	 */
	public static function check_group_membership() {
		$user_id  = get_current_user_id();
		$group_id = get_the_ID();
		$meta     = get_user_meta( $user_id, 'learndash_group_users_' . $group_id, true );
		if ( ! empty( $meta ) ) {
			return;
		}
		ob_start();
		$form_template = self::get_template( 'learndash-group-join-form.php', dirname( dirname( __FILE__ ) ) . '/src' );
		$form_template = apply_filters( 'uo_group_join_form_template', $form_template );
		include $form_template;
		echo ob_get_clean();
	}

	/**
	 * Added hook for pre validation of group seats.
	 *
	 * @param object $errors
	 * @param string $sanitized_user_login
	 * @param string $user_email
	 *
	 * @return object
	 */
	public static function validate_on_group_registration( $errors, $sanitized_user_login, $user_email ) {
		if ( class_exists( '\uncanny_learndash_groups\SharedFunctions' ) ) {
			if ( isset( $_REQUEST['gid'] ) && absint( $_REQUEST['gid'] ) ) {
				$new_group_id = absint( $_REQUEST['gid'] );
			}
			if ( filter_has_var( INPUT_POST, 'group_id' ) ) {
				$new_group_id = absint( filter_input( INPUT_POST, 'group_id' ) );
			}
			$code_group_id = get_post_meta( $new_group_id, '_ulgm_code_group_id', true );
			if ( $code_group_id ) {
				$remaining_seats = \uncanny_learndash_groups\SharedFunctions::remaining_seats( $new_group_id );
				if ( 0 === $remaining_seats ) {
					$errors->add( 'group_join_error', esc_html__( 'Sorry, the group you are trying to join is full.', 'uncanny-pro-toolkit' ) );
				}
			}
		}

		return $errors;
	}

	/**
	 * Adding support for TML register form.
	 */
	public static function tml_add_fields() {
		tml_add_form_field(
			'register',
			'gid',
			array(
				'type'     => 'hidden',
				'value'    => tml_get_request_value( 'gid', 'any' ),
				'id'       => 'gid',
				'priority' => 5,
			)
		);
	}

	/**
	 * Restrict learndash content on .
	 */
	public static function restrict_learndash_content( $content, $post ) {
		if ( ! is_user_logged_in() ) {
			if ( learndash_get_post_type_slug( 'group' ) === $post->post_type ) {
				return '<style>.learndash-wrapper .ld-item-list,.learndash-wrapper .ld-course-status.ld-course-status-not-enrolled{display:none;}</style>' . $content;
			}
		}

		return $content;
	}

	/**
	 * Add Groups Signup URL.
	 */
	public static function register_group_signup_permalink(){
		do_action( 'ulgm_before_register_group_signup_permalink' );
		add_rewrite_rule( '^sign-up/([^/]+)/?$', 'index.php?ulgm_group_slug=$matches[1]&ulgm_signup=1', 'top' );
		do_action( 'ulgm_after_register_group_signup_permalink' );

		// @todo: We need a way to run this for this specific module once the module gets initialized for the first time or gets initialized or re-enabled.
		flush_rewrite_rules();
	}

	public static function register_group_signup_query_vars( $query_vars ){
		$query_vars[] = 'ulgm_signup';
		$query_vars[] = 'ulgm_group_slug';
		return $query_vars;
	}

	/**
	 * Registers Groups Signup page template.
	 */
	public static function register_signup_template( $template ){
		global $wp;
        if ( get_query_var('ulgm_signup') && get_query_var('ulgm_group_slug') ) {

        	$ulgm_group_slug = sanitize_text_field( wp_unslash( get_query_var('ulgm_group_slug') ) );

            if ( !empty( trim($ulgm_group_slug) ) ) {
                $template = self::get_template( 'single-group.php', dirname( dirname( __FILE__ ) ) . '/src' );
				$template = apply_filters( 'uo_single_group_template', $template );
            }
        }
        return $template;
	}

	/**
	 * Displays a signup URL in the Groups table for the user who has access.
	 */
	public static function render_signup_url_row_action( $actions, $post ){

		 if ( "groups" === (string) $post->post_type ){

		 	if( current_user_can( 'manage_options' ) ){
	        	$ulgm_signup_url = sprintf(
				'<a href="%s">%s</a>',
					site_url( 'sign-up/' . $post->post_name . '/' ),
					esc_html__( 'Signup URL', 'uncanny-pro-toolkit' )
				);

				$actions['ulgm_signup_url'] = apply_filters( 'ulgm_signup_url', $ulgm_signup_url, $post );
	    	}
	    }
	    return $actions;
	}

	public static function signup_body_class( $classes, $class ){
		if ( self::is_ulgm_signup()  ) {
			$index = array_search( 'blog', $classes, true );
			if( false !== $index ){
				unset($classes[$index]);
			}

			$classes[] = 'single-groups';
		}
		return $classes;
	}

	private static function is_ulgm_signup(){
		global $wp_query;

		if ( isset($wp_query->query['ulgm_signup']) && isset($wp_query->query['ulgm_group_slug']) ) {
			return true;
		}

		return false;
	}

	public static function wpml_ulgm_admin_signup_link($ulgm_admin_signup_link, $post, $group_key){
		global $sitepress;

		$admin_signup_url = '';
		if ( 1 === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) {
			$home_url = apply_filters( 'wpml_home_url', get_option( 'home' ) );
			$home_url = trim($home_url,'/');
			$url = $home_url . '/sign-up/' . $post->post_name . '/';
			$admin_signup_url = add_query_arg( array(
				'gid' => $post->ID,
			), $url )  . '&' . $group_key;;
		} else if ( 3 === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) {
			$current_language = icl_get_current_language();

			$url = site_url( 'sign-up/' . $post->post_name . '/' );
			$admin_signup_url = add_query_arg( array(
				'lang' => $current_language,
				'gid' => $post->ID,
			), $url )  . '&' . $group_key;;
		}

		if( '' === $admin_signup_url ) {
			$admin_signup_url = $ulgm_admin_signup_link;
		}

		return $admin_signup_url;
	}

	public static function wpml_ulgm_signup_url($ulgm_signup_url, $post){
		global $sitepress;

		$signup_url = '';
		if ( 1 === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) {
			$home_url = apply_filters( 'wpml_home_url', get_option( 'home' ) );
			$home_url = trim($home_url,'/');
			$signup_url = $home_url . '/sign-up/' . $post->post_name . '/';
		} else if ( 3 === (int) $sitepress->get_setting( 'language_negotiation_type' ) ) {
			$current_language = icl_get_current_language();

			$url = site_url( 'sign-up/' . $post->post_name . '/' );
			$signup_url = add_query_arg( array(
				'lang' => $current_language
			), $url );
		}

		if( '' === $signup_url ) {
			$signup_url = $ulgm_signup_url;
		}

		$ulgm_signup_url = sprintf(
		'<a href="%s">%s</a>',
			$signup_url,
			esc_html__( 'Signup URL', 'uncanny-pro-toolkit' )
		);

		return $ulgm_signup_url;
	}

	public function add_to_group_on_ajax_gf_user_registered( $user_id, $feed, $entry, $password ) {

		$source_url = rgar( $entry, 'source_url' );

		$query_params = $this->extract_query_params( $source_url );

		if ( empty( $query_params['gid'] ) ) {
			return;
		}

		self::set_users_group( absint( $query_params['gid'] ), $user_id );
	}

	public function extract_query_params( $url ) {

		$query_string = parse_url( $url, PHP_URL_QUERY );

		parse_str( $query_string, $query_params );

		return $query_params;
	}
}
