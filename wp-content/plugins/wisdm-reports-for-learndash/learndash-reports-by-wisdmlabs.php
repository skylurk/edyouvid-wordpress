<?php
/**
 * Plugin Name:       LearnDash LMS - Reports Free
 * Plugin URI:        https://wordpress.org/plugins/wisdm-reports-for-learndash/
 * Description:       Reports By LearnDash
 * Version:           1.8.2.2
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            LearnDash
 * Author URI:        https://www.learndash.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       learndash-reports-by-wisdmlabs
 * Domain Path:       /languages
 *
 * @package learndash-reports-by-wisdmlabs
 */

if ( ! defined( 'WRLD_REPORTS_FILE' ) ) {
	define( 'WRLD_REPORTS_FILE', __FILE__ );
}

if ( ! defined( 'WRLD_PLUGIN_VERSION' ) ) {
	define( 'WRLD_PLUGIN_VERSION', '1.8.2.2' );
}

if ( ! defined( 'WRLD_RECOMENDED_LDRP_PLUGIN_VERSION' ) ) {
	define( 'WRLD_RECOMENDED_LDRP_PLUGIN_VERSION', '1.8.2' );
}

// Constant for text domain.
if ( ! defined( 'WRLD_REPORTS_PATH' ) ) {
	define( 'WRLD_REPORTS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'WRLD_REPORTS_SITE_URL' ) ) {
	/**
	 * The constant CSP_PLUGIN_SITE_URL contains the url path to the plugin directory
	 * eg. https://example.com/wp-content/plugins/block-sample/
	 */
	define( 'WRLD_REPORTS_SITE_URL', untrailingslashit( plugins_url( '/', WRLD_REPORTS_FILE ) ) );
}

if ( ! defined( 'WRLD_COURSE_TIME_FREQUENCY' ) ) {
	/**
	 * This constant defines the frequency at which the activity time is being saved in database.
	 */
	define( 'WRLD_COURSE_TIME_FREQUENCY', 30 ); // Define in seconds.
}

if ( ! defined( 'WRLD_COURSE_SESSION_TIMEOUT' ) ) {
	/**
	 * This constant defines the active session timeout for the current activity time-tracking.
	 */
	define( 'WRLD_COURSE_SESSION_TIMEOUT', 30 * MINUTE_IN_SECONDS ); // Define in seconds.
}

require_once 'includes/functions.php';

function generate_quiz_attempts( $new_id, $old_id ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query.
	$usertime = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ld_time_entries WHERE user_id = %d",
			$old_id
		),
		ARRAY_A
	);

	if ( ! empty( $usertime ) ) {
		foreach ( $usertime as &$time ) {
			unset( $time['id'] );
			$time['user_id'] = $new_id;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table query.
			$new_usertime = $wpdb->insert(
				$wpdb->prefix . 'ld_time_entries',
				$time
			);
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
	$usermeta = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->usermeta} WHERE user_id = %d",
			$old_id
		),
		ARRAY_A
	);

	if ( ! empty( $usermeta ) ) {
		foreach ( $usermeta as &$meta ) {
			unset( $meta['umeta_id'] );
			$meta['user_id'] = $new_id;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom query and up-to-date data.
			$new_usermeta = $wpdb->insert(
				$wpdb->usermeta,
				$meta
			);
		}
	}

	$user_activity_table      = LDLMS_DB::get_table_name( 'user_activity' );
	$user_activity_meta_table = LDLMS_DB::get_table_name( 'user_activity_meta' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
	$activities = $wpdb->get_results(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe custom table.
			"SELECT * FROM {$user_activity_table} WHERE user_id = %d",
			$old_id
		),
		ARRAY_A
	);
	$new_activity_meta_ids = array();
	if ( ! empty( $activities ) ) {
		foreach ( $activities as &$activity ) {
			$old_activity_id = $activity['activity_id'];
			unset( $activity['activity_id'] );
			$activity['user_id'] = $new_id;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
			$new_activity = $wpdb->insert(
				LDLMS_DB::get_table_name( 'user_activity' ),
				$activity
			);

			$new_activity_id = $wpdb->insert_id;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
			$activities_meta = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe custom table.
					"SELECT * FROM {$user_activity_meta_table} WHERE activity_id = %d",
					$old_activity_id
				),
				ARRAY_A
			);

			if ( ! empty( $activities_meta ) ) {
				foreach ( $activities_meta as &$activity_meta ) {
					unset( $activity_meta['activity_meta_id'] );
					$activity_meta['activity_id'] = $new_activity_id;

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
					$new_activity_meta = $wpdb->insert(
						$user_activity_meta_table,
						$activity_meta
					);

					$new_activity_meta_ids[] = $wpdb->insert_id;
				}
			}
		}
	}

	$quiz_statistics_ref_table = LDLMS_DB::get_table_name( 'quiz_statistic_ref' );
	$quiz_statistics_table     = LDLMS_DB::get_table_name( 'quiz_statistic' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
	$statistic_refs = $wpdb->get_results(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe custom table.
			"SELECT * FROM {$quiz_statistics_ref_table} WHERE user_id = %d",
			$old_id
		),
		ARRAY_A
	);

	if ( ! empty( $statistic_refs ) ) {
		foreach ( $statistic_refs as $statistic_ref ) {
			$old_statistic_ref_id = $statistic_ref['statistic_ref_id'];
			unset( $statistic_ref['statistic_ref_id'] );
			$statistic_ref['user_id'] = $new_id;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
			$new_statistic_ref_id = $wpdb->insert(
				$quiz_statistics_ref_table,
				$statistic_ref
			);
			$new_statistic_ref_id = $wpdb->insert_id;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
			$statistics = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe custom table.
					"SELECT * FROM {$quiz_statistics_table} WHERE statistic_ref_id = %d",
					$old_statistic_ref_id
				),
				ARRAY_A
			);
			if ( ! empty( $statistics ) ) {
				foreach ( $statistics as &$statistic ) {
					$statistic['statistic_ref_id'] = $new_statistic_ref_id;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
					$new_statistics = $wpdb->insert(
						$quiz_statistics_table,
						$statistic
					);
				}
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
			$usermeta = $wpdb->get_row(
				$wpdb->prepare(
				"SELECT * FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = '_sfwd-quizzes'",
					$new_id
				),
				ARRAY_A
			);
			if ( ! empty( $usermeta ) ) {
				$meta_value = maybe_unserialize( $usermeta['meta_value'] );
				foreach ( $meta_value as &$value ) {
					if ( $value['statistic_ref_id'] == $old_statistic_ref_id ) {
						$value['statistic_ref_id'] = $new_statistic_ref_id;
					}
				}
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Update usermeta value.
				$usermeta['meta_value'] = serialize( $meta_value );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
				$new_usermeta = $wpdb->update(
					$wpdb->usermeta,
					$usermeta,
					array( 'umeta_id' => $usermeta['umeta_id'] )
				);
			}

			$new_activity_meta_ids_sql = implode( ',', $new_activity_meta_ids );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
			$activities_meta = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe variables.
					"SELECT * FROM {$user_activity_meta_table} WHERE activity_meta_id IN ( {$new_activity_meta_ids_sql} ) AND activity_meta_key = 'statistic_ref_id' AND activity_meta_value = %d",
					$old_statistic_ref_id
				),
				ARRAY_A
			);
			if ( ! empty( $activities_meta ) ) {
				foreach ( $activities_meta as &$activity_meta ) {
					$activity_meta['activity_meta_value'] = $new_statistic_ref_id;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
					$new_activity_meta = $wpdb->update(
						$user_activity_meta_table,
						$activity_meta,
						array( 'activity_meta_id' => $activity_meta['activity_meta_id'] )
					);
				}
			}
		}
	}
}

function generate_user( $user_id ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
	$user = $wpdb->get_row(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe wpdb prefix.
			"SELECT * FROM {$wpdb->prefix}users WHERE ID = %d",
			$user_id
		),
		ARRAY_A
	);

	unset( $user['ID'] );
	// $last_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->prefix}users DESC LIMIT 1;" );
	// $user['ID'] = $last_id + 1;
	$user['user_login'] .= bin2hex( random_bytes( 6 ) );
	$user['user_email'] .= bin2hex( random_bytes( 6 ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query and up-to-date data.
	$insert_id = $wpdb->insert(
		$wpdb->users,
		$user
	);
	generate_quiz_attempts( $wpdb->insert_id, $user_id );
}

function data_generator_script() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only for conditional logic.
	if ( empty( $_GET['data'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only for conditional logic.
	if ( ! isset( $_GET['user_id'] ) || ! isset( $_GET['user_count'] ) ) {
		return;
	}

	$page_size = 100;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No authorized action or DB query is done here.
	$user_count = sanitize_text_field( wp_unslash( $_GET['user_count'] ) );
	$next       = false;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Conditional logic.
	if ( isset( $_GET['page'] ) ) {
		$pages = ceil( $user_count / $page_size );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No authorized action or DB query is done here.
		$page = (int) sanitize_text_field( wp_unslash( $_GET['page'] ) );
		if ( $page > $pages ) {
			return;
		} elseif ( $page < $pages ) {
			$next = true;
		}
	} else {
		$page  = 1;
		$pages = ceil( $user_count / $page_size );
		if ( $page > $pages ) {
			return;
		} elseif ( $page < $pages ) {
			$next = true;
		}
	}
	if ( $user_count > $page_size ) {
		$user_count = $page_size;
	}

	echo "<progress value='" . esc_attr( $page ) . "' max='" . esc_attr( $pages ) . "'></progress>";
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No authorized action or DB query is done here.
	$template_user = sanitize_text_field( wp_unslash( $_GET['user_id'] ) );

	for ( $i = 0; $i < $user_count; $i++ ) {
		generate_user( $template_user );
	}
	if ( $next ) {
		// wp_redirect(add_query_arg( array( 'data' => true, 'user_id' => $template_user, 'user_count' => $_GET['user_count'], 'page' => ++$page ), home_url() ));
		$url = add_query_arg(
			array(
				'data'       => true,
				'user_id'    => $template_user,
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No authorized action or DB query is done here.
				'user_count' => sanitize_text_field( wp_unslash( $_GET['user_count'] ) ),
				'page'       => ++$page,
			),
			home_url()
		);

		echo "<a href='" . esc_url( $url ) . "'> Process next </a>";
		wp_die();
	}
}
// add_action( 'init', 'data_generator_script' );
