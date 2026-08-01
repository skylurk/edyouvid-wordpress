<?php

namespace uncanny_pro_toolkit;

use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use uncanny_learndash_toolkit as toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class RestrictPageAccess
 * @package uncanny_pro_toolkit
 */
class RestrictPageAccess extends toolkit\Config implements toolkit\RequiredFunctions {


	/**
	 * Default lockout message
	 * @var string
	 */
	public static $message = '';

	/**
	 * Default lockout message
	 * @var string
	 */
	public static $page_restricted = false;


	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( __CLASS__, 'run_hooks' ) );
	}

	private static function get_message() {
		if( empty(self::$message) ) {
			self::$message = esc_attr__( 'Access is restricted.', 'uncanny-pro-toolkit' );
		}
		return self::$message;
	}

	/*
	 * Initialize frontend actions and filters
	 *
	 * @since 3.5.3 Added support for restrictions on attachments.
	 */
	public static function run_hooks() {
		if ( true === self::dependants_exist() ) {

			if ( is_admin() ) {
				// Register metabox.
				add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );

				// Save metabox values.
				add_action( 'save_post', array( __CLASS__, 'save_post' ) );

				// Save metadata on attachments too!
				add_action( 'edit_attachment', array( __CLASS__, 'save_post' ) );
			} else {
				// Site lockout
				add_action( 'template_redirect', array( __CLASS__, 'maybe_do_lockout' ), 0 );
			}
		}
	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or plugin
	 *
	 */
	public static function dependants_exist() {
		// Return true if no dependency or dependency is available
		return true;
	}

	/**
	 * Handles site lockout functionality
	 *
	 * @access public
	 * @return void
	 */

	public static function maybe_do_lockout() {

		// admins are never restricted.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// restrictions do not apply to the search query or archives.
		if ( is_search() || ! is_singular() ) {
			return;
		}

		// Single post redirects.
		global $post;

		if ( empty( $post ) ) {
			return;
		}

		if ( $post->ID == 0 ) {
			return;
		}

		$uo_restriction_settings = get_post_meta( $post->ID, 'uo_restriction_settings', true );

		// There are no restriction settings set
		if ( empty( $uo_restriction_settings ) ) {
			return;
		}

		// Restriction settings are off
		if ( ! isset( $uo_restriction_settings['lock_content'] ) || ( isset( $uo_restriction_settings['lock_content'] ) && '1' !== $uo_restriction_settings['lock_content'] ) ) {
			return;
		} else {

			// If user can access, return without doing anything
			if ( self::user_can_access( $uo_restriction_settings ) == true ) {
				return;
			} else {

				// User doesn't have access, check if a redirect is set
				if (
					isset( $uo_restriction_settings['redirect_if_denied'] ) &&
					'1' === $uo_restriction_settings['redirect_if_denied']
				) {

					if ( isset( $uo_restriction_settings['redirect_type'] ) && ! empty( $uo_restriction_settings['redirect_type'] ) ) {

						if ( 'post' === $uo_restriction_settings['redirect_type'] ) {
							// post redirect
							$redirect_post = get_post( $uo_restriction_settings['page_redirect'] );

							if ( ! empty( $redirect_post ) && 'publish' === $redirect_post->post_status ) {
								$redirect = get_permalink( $redirect_post->ID );
								wp_redirect( $redirect );
								exit();
							}
						}

						if ( 'custom_url' === $uo_restriction_settings['redirect_type'] ) {
							// external URL  redirect
							if (
								isset( $uo_restriction_settings['external_redirect'] ) &&
								! empty( $uo_restriction_settings['external_redirect'] )
							) {
								wp_redirect( $uo_restriction_settings['external_redirect'] );
								exit();
							}
						}
					}
				}

				// Stop autocomplete
				self::$page_restricted = true;

				add_filter( 'the_content', array( __CLASS__, 'restricted_content_filter' ) );

				add_filter( 'comments_open', array( __CLASS__, 'turn_off_comments' ), 10, 2 );

				add_filter( 'post_password_required', array( __CLASS__, 'hide_comments' ), 10, 2 );

				add_filter( 'learndash_template_filename', array( __CLASS__, 'hide_learndash_template' ) );

			}
		}
	}

	/**
	 * @param $uo_restriction_settings
	 *
	 * @return bool
	 */
	public static function user_can_access( $uo_restriction_settings ) {

		// User must be  logged in
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Get user object with roles
		$user              = wp_get_current_user();
		$has_role_access   = false;
		$has_course_access = false;
		$has_course_completed = false;
		$has_group_access  = false;

		// Check if a required role is set
		if (
			isset( $uo_restriction_settings['required_role'] ) &&
			is_array( $uo_restriction_settings['required_role'] ) &&
			! empty( $uo_restriction_settings['required_role'] )
		) {

			foreach ( $uo_restriction_settings['required_role'] as $role ) {
				// Does the current user have any of the roles needed
				if ( in_array( $role, (array) $user->roles, false ) ) {
					$has_role_access = true;
					break;
				}
			}

			//  If there is a required role and the current user doesn't have at least one of them
			if ( false === $has_role_access ) {
				// user doesn't have access
				return $has_role_access;
			}
		} else {
			// role is not set so by default they pass the role restriction
			$has_role_access = true;
		}

		// Check if a required course enrollment is set
		if (
			isset( $uo_restriction_settings['required_course'] ) &&
			is_array( $uo_restriction_settings['required_course'] ) &&
			! empty( $uo_restriction_settings['required_course'] )
		) {

			// Get enrolled courses
			$user_enrolled_courses = learndash_user_get_enrolled_courses( $user->ID );

			foreach ( $uo_restriction_settings['required_course'] as $course_id ) {
				// Is the current user enrolled in any of the courses
				if ( in_array( absint( $course_id ), $user_enrolled_courses, false ) ) {
					$has_course_access = true;
					break;
				}
			}

			//  If there is a required course enrollment  and the current user doesn't have at least one of them
			if ( false === $has_course_access ) {
				// user doesn't have access
				return $has_course_access;
			}
		} else {
			$has_course_access = true;
		}

		// Check if a required course completed is set
		if (
			isset( $uo_restriction_settings['completed_course'] ) &&
			is_array( $uo_restriction_settings['completed_course'] ) &&
			! empty( $uo_restriction_settings['completed_course'] )
		) {
			foreach ( $uo_restriction_settings['completed_course'] as $course_id ) {
				$user_completed_courses = learndash_course_completed( $user->ID, $course_id );

				// Is the current user completed any of the courses
				if ( 1 === absint( $user_completed_courses ) ) {
					$has_course_completed = true;
					break;
				}
			}


			//  If there is a required course completion  and the current user doesn't have at least one of them
			if ( false === $has_course_completed ) {

				// user doesn't have access
				return $has_course_completed;
			}
		} else {
			$has_course_completed = true;
		}

		// Check if a required group enrollment is set
		if (
			isset( $uo_restriction_settings['required_group'] ) &&
			is_array( $uo_restriction_settings['required_group'] ) &&
			! empty( $uo_restriction_settings['required_group'] )
		) {

			// Get enrolled groups
			$user_enrolled_groups = learndash_get_users_group_ids( $user->ID );
            // Get user's groups as leader
            $user_leading_groups = learndash_get_administrators_group_ids( $user->ID );

			foreach ( $uo_restriction_settings['required_group'] as $group_id ) {
				// Is the current user enrolled in any of the courses
				if ( in_array( $group_id, $user_enrolled_groups, false ) ) {
					$has_group_access = true;
					break;
				}
				// Is the current user group leader of this group
				if ( ! empty( $user_leading_groups ) && in_array( $group_id, $user_leading_groups ) ) {
					$has_group_access = true;
					break;
				}
			}

			//  If there is a required group enrollment and the current user doesn't have at least one of them
			if ( false === $has_group_access ) {
				// user doesn't have access
				return $has_group_access;
			}
		} else {
			$has_group_access = true;
		}

		// If non of the conditions are false then user has access to page
		if ( $has_role_access && $has_course_access && $has_group_access && $has_course_completed ) {
			return true;
		}

		// This will probably never be hit but its better to not have access by default then to give access accidentally
		return false;
	}

	/**
	 * Outputs "restricted" message on restricted content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public static function restricted_content_filter( $content ) {

		$is_restricted = apply_filters( 'uncanny_toolkit_restricted_content_filter', true, $content );
		
		if( true !== $is_restricted ) {
			return $content;
		}
		
		$message = self::get_settings_value( 'uncanny-restrict-page-access-message', __CLASS__ );
		if ( empty( $message ) ) {
			$message = self::get_message();
		}

		return do_shortcode( nl2br( $message ) );

	}

	/**
	 * Turn off comments if post is restricted and no redirect is specified
	 *
	 * @param $open
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function turn_off_comments( $open, $post_id ) {
		return false;
	}

	/**
	 * Sets the post to be password required so existing comments are hidden
	 *
	 * @param $hide
	 * @param $post
	 *
	 * @return bool
	 */
	public static function hide_comments( $hide, $post ) {
		if ( ! empty( get_comments_number( $post->ID ) ) ) {
			return true;
		}

		return $hide;
	}

	/**
	 * Sets the post to be password required so existing comments are hidden
	 *
	 * @param $hide
	 * @param $post
	 *
	 * @return bool
	 */
	public static function hide_learndash_template( $template_file_name ) {

//	    echo '<pre>';
//	    var_dump($template_file_name);
//	    echo '</pre>';

		if (
			'course/listing.php' === $template_file_name ||
			'lesson/listing.php' === $template_file_name ||
			'topic/listing.php' === $template_file_name ||
			'quiz/listing.php' === $template_file_name
		) {
			// retunring an empty variable will stop the template from loading
			return '';
		}

		if ( 'quiz.php' === $template_file_name ) {
			$message = self::get_settings_value( 'uncanny-restrict-page-access-message', __CLASS__ );
			if ( empty( $message ) ) {
				$message = self::$message;
			}

			echo do_shortcode( nl2br( $message ) );

			return '';
		}

		return $template_file_name;


	}

	/** Save metabox data
	 *
	 * @param $post_id
	 */
	public static function save_post( $post_id ) {

		// Add nonce for security and authentication.
		$nonce_name   = filter_has_var( INPUT_POST, 'uo_restriction_nonce' ) ? filter_input( INPUT_POST, 'uo_restriction_nonce' ) : '';
		$nonce_action = 'uo_restriction_nonce_action';

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Save all restrictions as array to single meta field
		update_post_meta( $post_id, 'uo_restriction_settings', filter_input( INPUT_POST, 'uo_restriction_settings', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY ) );
	}

	/**
	 * Register Meta Box
	 */
	public static function register_meta_box() {

		// Create metabox
		add_meta_box( 'ultp-restrict-access-metabox', esc_html__( 'Restrict Page Access', 'uncanny-pro-toolkit' ), array(
			__CLASS__,
			'meta_box_callback'
		), get_post_types( array( 'public' => true ) ), 'side' );
	}

	/**
	 * Add restriction fields
	 *
	 * @param $meta_id
	 */
	public static function meta_box_callback( $meta_id ) {

		global $post;

		$uo_restriction_settings = get_post_meta( $post->ID, 'uo_restriction_settings', true );

		if ( empty( $uo_restriction_settings ) ) {
			$uo_restriction_settings = array();
		}

		$uo_restriction_settings = shortcode_atts( array(
			'lock_content'       => false,
			'redirect_if_denied' => false,
			'redirect_type'      => '',
			'required_role'      => array(),
			'required_group'     => array(),
			'required_course'    => array(),
			'completed_course'    => array(),
			'external_redirect'  => '',
			'page_redirect'  	 => '',

		), $uo_restriction_settings );

		$settings = $uo_restriction_settings;

		// Check if it's enabled
		$is_enabled = isset( $settings['lock_content'] ) ? checked( $settings['lock_content'], 1, false ) : false;

		// Check if the user wants to redirect the user
		$redirect_if_denied = isset( $settings['redirect_if_denied'] ) ? checked( $settings['redirect_if_denied'], 1, false ) : false;

		// If the user decided to redirect the user if the access is denied the
		// user also selected if it has to be redirect to a page or a custom url
		// Get that value
		$redirect_type = isset( $settings['redirect_type'] ) ? $settings['redirect_type'] : '';

		// Add nonce for security and authentication.
		wp_nonce_field( 'uo_restriction_nonce_action', 'uo_restriction_nonce' );

		// Create array with the classes we're going to add to the metabox
		$metabox_css_classes = [];

		// Add class if it's enabled
		if ( $is_enabled ) {
			$metabox_css_classes[] = 'ultp-restrict-access--enabled';
		}

		// Add class if the redirect is enabled
		if ( $redirect_if_denied ) {
			$metabox_css_classes[] = 'ultp-restrict-access--redirect-if-denied';
		}

		// Add class with the redirect type
		if ( in_array( $redirect_type, [ 'post', 'custom_url' ] ) ) {
			$metabox_css_classes[] = 'ultp-restrict-access--redirect-to-' . $redirect_type;
		}

		?>

        <div id="ultp-restrict-access"
             class="ultp-restrict-access ult <?php echo implode( ' ', $metabox_css_classes ); ?>">
            <div class="ultp-restrict-access-header">
				<?php self::lock_content_checkbox( $post, $settings ); ?>
            </div>

            <div class="ultp-restrict-access-options">
				<?php self::required_role_select( $post, $settings ); ?>
				<?php self::required_course_enrollment_select( $post, $settings ); ?>
				<?php self::required_course_completed_select( $post, $settings ); ?>
				<?php self::required_group_enrollment_select( $post, $settings ); ?>

				<?php self::enable_redirect( $post, $settings ); ?>

                <div class="ultp-restrict-access-options-redirect">
					<?php self::page_redirect_source( $post, $settings ); ?>
					<?php self::page_redirect_select( $post, $settings ); ?>
					<?php self::external_redirect_input( $post, $settings ); ?>
                </div>
            </div>
        </div>

		<?php

	}

	/**
	 * Shows restrict content checkbox
	 *
	 * @access public
	 * @return void
	 */

	public static function lock_content_checkbox( $post, $settings ) {
		$post_type      = get_post_type_object( get_post_type( $post ) );
		$post_type_name = strtolower( $post_type->labels->singular_name );
		$label          = sprintf( esc_attr__( 'Users must be logged in to view this %s', 'uncanny-pro-toolkit' ), $post_type_name );
		$checked        = isset( $settings['lock_content'] ) ? checked( $settings['lock_content'], 1, false ) : false;

		?>

        <div class="ult-form-element">
            <input type="checkbox" id="ultp-restrict-access__enable-restrict-access"
                   name="uo_restriction_settings[lock_content]" value="1" <?php echo $checked; ?>/>
            <label for="ultp-restrict-access__enable-restrict-access">
				<?php echo $label; ?>
            </label>
        </div>

		<?php
	}

	/**
	 * Shows required tags input
	 *
	 * @access public
	 * @return void
	 */

	public static function required_role_select( $post, $settings ) {
		global $wp_roles;
		$label = esc_attr__( 'Required role (optional)', 'uncanny-pro-toolkit' );

		?>

        <div class="ultp-restrict-access__option">
            <div class="ult-form-element">
                <div class="ult-form-element__field">
                    <label for="ultp-restrict-access__roles">
						<?php echo $label; ?>
                    </label>
                    <select id="ultp-restrict-access__roles" multiple="multiple"
                            name="uo_restriction_settings[required_role][]">
						<?php
						if( ! is_array( $settings['required_role'] ) ){
							$settings['required_role'] = array();
						}

						foreach ( $wp_roles->roles as $id => $role ) {
							$selected = '';
							if ( in_array( $id, $settings['required_role'], true ) ) {
								$selected = 'selected="selected"';
							}

							echo '<option value="' . $id . '" ' . $selected . '>' . $role['name'] . '</option >';
						}

						?>
                    </select>
                </div>
            </div>
        </div>

		<?php
	}

	/**
	 * Shows required tags input
	 *
	 * @access public
	 * @return void
	 */

	public static function required_course_enrollment_select( $post, $settings ) {

		$courses = get_posts( [
			'post_type'   => 'sfwd - courses',
			'numberposts' => - 1,
			'post_status' => 'publish',
		] );

		$label = esc_attr__( 'Required course (optional)', 'uncanny-pro-toolkit' );

		?>

        <div class="ultp-restrict-access__option">
            <div class="ult-form-element">
                <div class="ult-form-element__field">
                    <label for="ultp-restrict-access__courses">
						<?php echo $label; ?>
                    </label>
                    <select id="ultp-restrict-access__courses" multiple="multiple"
                            name="uo_restriction_settings[required_course][]">
						<?php
						if( ! is_array( $settings['required_course'] ) ){
							$settings['required_course'] = array();
						}

						foreach ( $courses as $course ) {

							$selected = '';
							if ( in_array( $course->ID, $settings['required_course'], false ) ) {
								$selected = 'selected="selected"';
							}

							echo '<option value="' . $course->ID . '" ' . $selected . ' >' . $course->post_title . '</option >';
						}

						?>
                    </select>
                </div>
            </div>
        </div>

		<?php
	}

	/**
	 * Shows required tags input
	 *
	 * @access public
	 * @return void
	 */

	 public static function required_course_completed_select( $post, $settings ) {

		$courses = get_posts( [
			'post_type'   => 'sfwd - courses',
			'numberposts' => - 1,
			'post_status' => 'publish',
		] );

		$label = esc_attr__( 'Required course completion (optional)', 'uncanny-pro-toolkit' );

		?>

        <div class="ultp-restrict-access__option">
            <div class="ult-form-element">
                <div class="ult-form-element__field">
                    <label for="ultp-restrict-access__courses">
						<?php echo $label; ?>
                    </label>
                    <select id="ultp-restrict-access__courses" multiple="multiple"
                            name="uo_restriction_settings[completed_course][]">
						<?php
						if( ! is_array( $settings['completed_course'] ) ){
							$settings['completed_course'] = array();
						}

						foreach ( $courses as $course ) {

							$selected = '';
							if ( in_array( $course->ID, $settings['completed_course'], false ) ) {
								$selected = 'selected="selected"';
							}

							echo '<option value="' . $course->ID . '" ' . $selected . ' >' . $course->post_title . '</option >';
						}

						?>
                    </select>
                </div>
            </div>
        </div>

		<?php
	}

	/**
	 * Shows required tags input
	 *
	 * @access public
	 * @return void
	 */

	public static function required_group_enrollment_select( $post, $settings ) {

		$groups = get_posts( [
			'post_type'   => 'groups',
			'numberposts' => - 1,
			'post_status' => 'publish',
		] );

		$label = esc_attr__( 'Required group (optional)', 'uncanny-pro-toolkit' );

		?>

        <div class="ultp-restrict-access__option">
            <div class="ult-form-element">
                <div class="ult-form-element__field">
                    <label for="ultp-restrict-access__groups">
						<?php echo $label; ?>
                    </label>
                    <select id="ultp-restrict-access__groups" multiple="multiple"
                            name="uo_restriction_settings[required_group][]">
						<?php
						if( ! is_array( $settings['required_group'] ) ){
							$settings['required_group'] = array();
						}

						foreach ( $groups as $group ) {
							$selected = '';
							if ( in_array( $group->ID, $settings['required_group'], false ) ) {
								$selected = 'selected="selected"';
							}

							echo '<option value = "' . $group->ID . '" ' . $selected . '>' . $group->post_title . '</option > ';
						}
						?>
                    </select>
                </div>
            </div>
        </div>

		<?php
	}

	/**
	 * Show a checkbox so the user can decide
	 * if we must redirect the user if the access
	 * is prevented
	 *
	 * @access public
	 * @return void
	 */

	public static function enable_redirect( $post, $settings ) {
		$label   = esc_attr__( 'Redirect if access is denied', 'uncanny-pro-toolkit' );
		$checked = isset( $settings['redirect_if_denied'] ) ? checked( $settings['redirect_if_denied'], 1, false ) : false;

		?>

        <div class="ultp-restrict-access__option">
            <div class="ult-form-element">
                <input type="checkbox" id="ultp-restrict-access__redirect-if-defined"
                       name="uo_restriction_settings[redirect_if_denied]" value="1" <?php echo $checked; ?>/>
                <label for="ultp-restrict-access__redirect-if-defined">
					<?php echo $label; ?>
                </label>
            </div>
        </div>

		<?php
	}

	/**
	 * Shows a radio field where the user
	 * can select if we should redirect to a
	 * page or a custom URL.
	 *
	 * @access public
	 * @return void
	 */

	public static function page_redirect_source( $post, $settings ) {
		$label = esc_attr__( 'Redirect to', 'uncanny-pro-toolkit' );

		$redirect_to = $settings['redirect_type'];
		$redirect_to = in_array( $redirect_to, [ 'post', 'custom_url' ] ) ? $redirect_to : '';

		?>

        <div class="ultp-restrict-access__option">
            <div class="ult-form-element">
                <div class="ult-form-element__field">
                    <label>
						<?php echo $label; ?>
                    </label>
                    <div class="ult-form-element">
                        <input type="radio" id="ultp-restrict-access__redirect-to-post"
                               name="uo_restriction_settings[redirect_type]" value="post"
                               class="ultp-restrict-access__redirect-to" <?php echo $redirect_to == 'post' ? 'checked' : ''; ?>>
                        <label for="ultp-restrict-access__redirect-to-post">
							<?php esc_attr_e( 'Page' ); ?>
                        </label>
                    </div>
                    <div class="ult-form-element">
                        <input type="radio" id="ultp-restrict-access__redirect-to-custom-url"
                               name="uo_restriction_settings[redirect_type]" class="ultp-restrict-access__redirect-to"
                               value="custom_url" <?php echo $redirect_to == 'custom_url' ? 'checked' : ''; ?>>
                        <label for="ultp-restrict-access__redirect-to-custom-url">
							<?php esc_attr_e( 'Custom URL (include http:// or https://)' ); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

		<?php
	}

	/**
	 * Shows page redirect select
	 *
	 * @access public
	 * @return void
	 */

	public static function page_redirect_select( $post, $settings ) {

		$raw_post_types 	 = get_post_types( array( 'public' => true ) );
		unset( $raw_post_types['attachment'] );

		$post_types      = apply_filters( 'uncanny_toolkit_restrict_access_post_types', $raw_post_types );

		$available_posts = array();

		foreach ( $post_types as $post_type ) {
			$post_type_labels = get_post_type_object( $post_type )->labels;

			$posts = get_posts( array(
				'post_type'      => $post_type,
				'posts_per_page' => 200,
				'orderby'        => 'post_title',
				'order'          => 'ASC'
			) );

			if ( count( $posts ) > 0 ) {
				$available_posts[ $post_type ] = [
					'name'  => $post_type_labels->singular_name,
					'posts' => []
				];
			}

			foreach ( $posts as $post ) {
				$available_posts[ $post_type ]['posts'][ $post->ID ] = $post->post_title;
			}
		}

		$label = esc_attr__( 'Page', 'uncanny-pro-toolkit' );

		?>

        <div class="ultp-restrict-access__option ultp-restrict-access__option--redirect-to-post-option">
            <div class="ult-form-element">
                <div class="ult-form-element__field">
                    <label for="ultp-restrict-access__groups">
						<?php echo $label; ?>
                    </label>
                    <select name="uo_restriction_settings[page_redirect]">
						<?php

						foreach ( $available_posts as $post_type => $data ) {

							echo '<optgroup label = "' . $data['name'] . '" > ';

							foreach ( $available_posts[ $post_type ]['posts'] as $id => $post_name ) {
								echo '<option value = "' . $id . '"' . selected( $id, $settings['page_redirect'], false ) . ' > ' . $post_name . '</option > ';
							}

							echo '</optgroup > ';
						}

						?>
                    </select>
                </div>
            </div>
        </div>

		<?php
	}

	/**
	 * Shows external redirect text input
	 *
	 * @access public
	 * @return void
	 */

	public static function external_redirect_input( $post, $settings ) {
		$label = esc_attr__( 'Custom URL', 'uncanny-pro-toolkit' );

		?>

        <div class="ultp-restrict-access__option ultp-restrict-access__option--redirect-to-custom-url-option">
            <div class="ult-form-element">
                <div class="ult-form-element__field">
                    <label for="ultp-restrict-access__groups">
						<?php echo $label; ?>
                    </label>
                    <input type="text" name="uo_restriction_settings[external_redirect]"
                           value="<?php echo $settings['external_redirect']; ?>"/>
                </div>
            </div>
        </div>

		<?php
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'restrict-page-access';

		$class_title = esc_html__( 'Restrict Page Access', 'uncanny-pro-toolkit' );

		$kb_link = 'https://www.uncannyowl.com/knowledge-base/restrict-page-access/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Restrict access to any page by logged in/out status, course enrollment, group membership, or role. Display a message or automatically redirect users that are denied access.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-book "></i><span class="uo_pro_text">PRO</span>';

		/* Icon as img */
		//icons have variable widths and hieght
		$tags = 'general'; // learndash | general
		$type = 'pro';
		$category = ['learndash', 'wordpress'];

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'tags'             => $tags,
			'kb_link'          => $kb_link, // OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			/*'settings' => false, // OR */
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
				'type'        => 'textarea',
				'placeholder' => self::$message,
				'label'       => esc_html__( 'Access restriction message', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-restrict-page-access-message',
			),
		);
		// Build html
		$html = self::settings_output( array(
			'class'   => __CLASS__,
			'title'   => $class_title,
			'options' => $options,
		) );

		return $html;
	}
}
