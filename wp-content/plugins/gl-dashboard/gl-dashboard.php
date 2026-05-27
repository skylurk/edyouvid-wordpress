<?php
/**
 * Plugin Name: Group Leader Dashboard
 * Description: A clean portal for LearnDash group leaders to view course progress, analytics, and manage users.
 * Version: 1.1.3
 * Author: Custom
 * Text Domain: gl-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GLD_VERSION',    '1.1.3' );
define( 'GLD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GLD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GLD_PLUGIN_DIR . 'includes/class-access.php';
require_once GLD_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once GLD_PLUGIN_DIR . 'includes/class-subscription.php';
require_once GLD_PLUGIN_DIR . 'includes/class-woo.php';
require_once GLD_PLUGIN_DIR . 'includes/class-cron.php';
require_once GLD_PLUGIN_DIR . 'includes/class-admin.php';
require_once GLD_PLUGIN_DIR . 'includes/Api/class-groups.php';
require_once GLD_PLUGIN_DIR . 'includes/Api/class-progress.php';
require_once GLD_PLUGIN_DIR . 'includes/Api/class-analytics.php';
require_once GLD_PLUGIN_DIR . 'includes/Api/class-users.php';
require_once GLD_PLUGIN_DIR . 'includes/Api/class-subscription.php';
require_once GLD_PLUGIN_DIR . 'includes/Api/class-course-stats.php';

// ── Custom cron interval ───────────────────────────────────────────────────
// Must be registered before any wp_schedule_event() calls.
add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['gld_5min'] = array(
		'interval' => 300,
		'display'  => 'Every 5 Minutes (GLD cache warm)',
	);
	return $schedules;
} );

// ── Activation / deactivation ──────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
	GLD_Subscription::install();
	GLD_Cron::schedule();
} );

register_deactivation_hook( __FILE__, function () {
	GLD_Cron::unschedule();
} );

// Run DB install on version bump (handles sites that were already active).
// Also ensures the 5-min cron is scheduled on installs that were already active
// when the cache-warm feature was introduced (activation hook won't re-fire).
add_action( 'plugins_loaded', function () {
	if ( get_option( 'gld_db_version' ) !== GLD_Subscription::DB_VERSION ) {
		GLD_Subscription::install();
	}
	if ( ! wp_next_scheduled( 'gld_cache_warm' ) ) {
		wp_schedule_event( time(), 'gld_5min', 'gld_cache_warm' );
	}
} );

// ── Feature hooks ──────────────────────────────────────────────────────────
add_action( 'init', array( 'GLD_Shortcode', 'register' ) );
add_action( 'init', array( 'GLD_Cron', 'register' ) );

add_action( 'plugins_loaded', function () {
	if ( class_exists( 'WooCommerce' ) ) {
		GLD_Woo::register();
	}
} );

GLD_Admin::register();

// ── Cache busting ──────────────────────────────────────────────────────────
// When a student makes progress, immediately clear the cached response for
// every group they belong to so the next refresh shows live data.
function gld_bust_progress_cache( int $user_id ): void {
	$group_ids = learndash_get_users_group_ids( $user_id );
	foreach ( $group_ids as $gid ) {
		$total = (int) get_transient( GLD_Api_Progress::total_cache_key( $gid ) );
		$pages = $total > 0 ? (int) ceil( $total / GLD_Api_Progress::PER_PAGE ) : 1;
		for ( $p = 1; $p <= $pages; $p++ ) {
			delete_transient( GLD_Api_Progress::page_cache_key( $gid, $p ) );
		}
		delete_transient( GLD_Api_Progress::total_cache_key( $gid ) );
	}
}

add_action( 'learndash_course_completed', function( $data ) {
	$user_id = (int) ( $data['user']->ID ?? 0 );
	if ( $user_id ) gld_bust_progress_cache( $user_id );
} );

add_action( 'learndash_lesson_completed', function( $data ) {
	$user_id = (int) ( $data['user']->ID ?? 0 );
	if ( $user_id ) gld_bust_progress_cache( $user_id );
} );

add_action( 'learndash_topic_completed', function( $data ) {
	$user_id = (int) ( $data['user']->ID ?? 0 );
	if ( $user_id ) gld_bust_progress_cache( $user_id );
} );

// ── REST API ───────────────────────────────────────────────────────────────
add_action( 'rest_api_init', function () {
	( new GLD_Api_Groups() )->register_routes();
	( new GLD_Api_Progress() )->register_routes();
	( new GLD_Api_Analytics() )->register_routes();
	( new GLD_Api_Users() )->register_routes();
	( new GLD_Api_Subscription() )->register_routes();
	( new GLD_Api_CourseStats() )->register_routes();
} );
