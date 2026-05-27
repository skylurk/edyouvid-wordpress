<?php

// On first activation, redirect to reporting settings page if min php version is met
register_activation_hook( UO_REPORTING_FILE, 'uncanny_learndash_reporting_plugin_activate' );

/**
 * @return void
 */
function uncanny_learndash_reporting_plugin_activate() {

	// Set which roles will need access to reporting
	$set_role_for_reporting = array( 'group_leader', 'administrator' );

	// Loop through all roles that need the reporting capability added
	foreach ( $set_role_for_reporting as $role ) {

		// Get the role class instance
		$group_leader_role = get_role( $role );

		if ( ! $group_leader_role ) {
			continue;
		}

		// Add the reporting capability to the role
		$group_leader_role->add_cap( apply_filters( 'uo_tincanny_reporting_capability', 'tincanny_reporting' ) );

	}

	update_option( 'uncanny_learndash_reporting_plugin_do_activation_redirect', 'yes' );
}


// Show admin notices for minimum versions of PHP, WordPress, and LearnDash
add_action( 'admin_notices', 'uo_reporting_learndash_version_notice' );

function uo_reporting_learndash_version_notice() {

	global $wp_version;

	//Minimum versions
	$wp         = '5.4';
	$php        = '7.4';
	$learn_dash = '4.2';

	// Set LearnDash version
	$learn_dash_version = 0;
	if ( defined( 'LEARNDASH_VERSION' ) ) {
		$learn_dash_version = LEARNDASH_VERSION;
	}

	// Get current screen
	$screen = get_current_screen();

	if ( ! version_compare( PHP_VERSION, '5.3', '>=' ) && ( isset( $screen ) && 'plugins.php' === $screen->parent_file ) ) {

		// Show notice if php version is less than 5.3 and the current admin page is plugins.php
		$version = $php;
		$current = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;

		?>
		<div class="notice notice-error">
			<h3>
				<?php
				echo sprintf(
					esc_html__( 'The %1$s requires PHP version %2$s or higher (5.6 or higher is recommended).Because you are using unsupported version of PHP (%3$s), the Reporting plugin will not initialize. Please contact your hosting company to upgrade to PHP 5.6 or higher.', 'uncanny-learndash-reporting' ),
					'Uncanny LearnDash Reporting',
					$version,
					$current
				);
				?>
			</h3>
		</div>
		<?php

	} elseif ( version_compare( $wp_version, $wp, '<' ) && ( isset( $_REQUEST['page'] ) && 'uncanny-learnDash-reporting' === $_REQUEST['page'] ) ) {

		// Show notice if WP version is less than 4.0 and the current page is the Reporting settings page
		$flag    = 'WordPress';
		$version = $wp;
		$current = $wp_version;

		?>
		<!-- No Notice Style below WordPress -->
		<style>
			.notice-error {
				border-left-color: #dc3232 !important;
			}

			.notice {
				background: #fff;
				border-left: 4px solid #fff;
				-webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
				box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
				margin: 5px 15px 2px;
				padding: 1px 12px;
			}
		</style>
		<div class="notice notice-error">
			<h3>
				<?php
				echo sprintf(
					esc_html__( 'The %1$s plugin requires %2$s version %3$s or greater. Your current version is %4$s.', 'uncanny-learndash-reporting' ),
					'Uncanny LearnDash Reporting',
					$flag,
					$version,
					$current
				);
				?>
			</h3>
		</div>
		<?php

	} elseif ( ! version_compare( $learn_dash_version, $learn_dash, '>=' ) && ( isset( $_REQUEST['page'] ) && 'uncanny-learnDash-reporting' === $_REQUEST['page'] ) ) {

		// Show notice if LearnDash is less than 2.1 and the current page is the Reporting settings page
		if ( 0 !== $learn_dash_version ) {

			?>
			<div class="notice notice-error">
				<h3>
					<?php
					echo sprintf(
						esc_html__( 'Uncanny LearnDash Reporting requires LearnDash version %1$s or higher to work properly. Please make sure you have LearnDash version %2$s or higher installed. Your current version is: %3$s', 'uncanny-learndash-reporting' ),
						$learn_dash,
						$learn_dash,
						$learn_dash_version
					);
					?>
				</h3>
			</div>
			<?php

		} elseif ( ! class_exists( 'SFWD_LMS' ) ) {

			?>
			<div class="notice notice-error">
				<h3>
					<?php
					echo sprintf(
						esc_html__( 'Uncanny LearnDash reporting requires LearnDash version %1$s or higher to work properly. Please make sure you have LearnDash version %2$s or higher installed.', 'uncanny-learndash-reporting' ),
						$learn_dash,
						$learn_dash
					);
					?>
				</h3>
			</div>
			<?php

		}
	}
}

// Allow Translations to be loaded
add_action( 'init', 'uncanny_learndash_reporting_text_domain' );

function uncanny_learndash_reporting_text_domain() {
	load_plugin_textdomain( 'uncanny-learndash-reporting', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'wp', 'tc_last_known_course' );

function tc_last_known_course() {

	$user = wp_get_current_user();

	if ( is_user_logged_in() ) {

		/* declare $post as global so we get the post->ID of the current page / post */
		global $post;

		/* Limit the plugin to LearnDash specific post types */
		$learn_dash_post_types =
			array(
				'sfwd-courses',
				'sfwd-lessons',
				'sfwd-topic',
				'sfwd-quiz',
				'sfwd-assignment',
			);

		if ( is_singular( $learn_dash_post_types ) ) {
			update_user_meta( $user->ID, 'tincan_last_known_ld_module', $post->ID );
			$course_id = learndash_get_course_id( $post );
			update_user_meta( $user->ID, 'tincan_last_known_ld_course', $course_id );
		}
	}
}

function uncanny_learndash_reporting_plugin_redirect() {

	if ( 'yes' === get_option( 'uncanny_learndash_reporting_plugin_do_activation_redirect', 'no' ) ) {

		update_option( 'uncanny_learndash_reporting_plugin_do_activation_redirect', 'no' );

		if ( ! ultc_filter_has_var( 'activate-multi' ) ) {
			wp_redirect( admin_url( 'admin.php?page=uncanny-reporting-license-activation' ) );
			exit();
		}
	}
}
add_action( 'admin_init', 'uncanny_learndash_reporting_plugin_redirect' );

// Add settings link on plugin page
$uncanny_learndash_reporting_plugin_basename = plugin_basename( UO_REPORTING_FILE );

add_filter( 'plugin_action_links_' . $uncanny_learndash_reporting_plugin_basename, 'uncanny_learndash_reporting_plugin_settings_link' );

/**
 * @param $links
 *
 * @return mixed
 */
function uncanny_learndash_reporting_plugin_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=uncanny-reporting-license-activation' ) . '">' . __( 'Licensing', 'uncanny-learndash-reporting' ) . '</a>';
	array_unshift( $links, $settings_link );
	$settings_link = '<a href="' . admin_url( 'admin.php?page=snc_options' ) . '">' . __( 'Settings', 'uncanny-learndash-reporting' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

$visibility_option = get_option( '_uncanny_tin_canny_try_automator_visibility' );

// Check if the user chose to hide it.
if ( empty( $visibility_option ) ) {
	// Register the endpoint to hide the "Try Automator".
	add_action(
		'rest_api_init',
		function () {
			/**
			 * Method try_automator_rest_register.
			 *
			 * Callback method to action hook `rest_api_init`.
			 *
			 * Registers a REST API endpoint to change the visibility of the "Try Automator" item.
			 *
			 * @since 3.5.4
			 */

			register_rest_route(
				'uncanny_reporting/v1',
				'/try_automator_visibility/',
				array(
					'methods'             => 'POST',
					'callback'            => function ( $request ) {

						// Check if its a valid request.
						$data = $request->get_params();

						if ( isset( $data['action'] ) && ( 'hide-forever' === $data['action'] || 'hide-forever' === $data['action'] ) ) {

							update_option( '_uncanny_tin_canny_try_automator_visibility', $data['action'] );

							return new \WP_REST_Response( array( 'success' => true ), 200 );

						}

						return new \WP_REST_Response( array( 'success' => false ), 200 );

					},
					'permission_callback' => function () {
						return true;
					},
				)
			);
		},
		99
	);
}

add_action( 'wp_initialize_site', 'uncanny_learndash_reporting_multisite_initialze' );
/**
 * Function to apply custom capabilities when a new site is created in a multisite network.
 *
 * @param WP_Site $new_site The new site object.
 */
function uncanny_learndash_reporting_multisite_initialze( $new_site ) {

	// Switch to the new site.
	switch_to_blog( $new_site->blog_id );

	// Run the activation function.
	uncanny_learndash_reporting_plugin_activate();

	// Restore the current site.
	restore_current_blog();
}

/**
 * Check if a specific user has admin capabilities
 *
 * @param int $user_id User ID to check
 * @return bool
 */
function uotc_is_user_admin( $user_id ) {
	return user_can( $user_id, apply_filters( 'uo_tincan_ld_admin_capability_check', 'manage_options' ) );
}

/**
 * Check if current user has admin capabilities
 *
 * @return bool
 */
function uotc_is_current_user_admin() {
	return current_user_can( apply_filters( 'uo_tincan_ld_admin_capability_check', 'manage_options' ) );
}