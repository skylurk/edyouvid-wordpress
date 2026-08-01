<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Auth {

	public static function register(): void {
		add_action( 'wp_ajax_nopriv_gld_register', array( __CLASS__, 'handle_register' ) );
		add_action( 'wp_ajax_nopriv_gld_login',    array( __CLASS__, 'handle_login' ) );
		// Also handle already-logged-in users submitting the form (edge case).
		add_action( 'wp_ajax_gld_register', array( __CLASS__, 'handle_register' ) );
		add_action( 'wp_ajax_gld_login',    array( __CLASS__, 'handle_login' ) );

		// Redirect logged-in group leaders away from auth pages.
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_leader' ) );
	}

	// ── Registration ─────────────────────────────────────────────────────────

	public static function handle_register(): void {
		check_ajax_referer( 'gld_auth', 'nonce' );

		$first  = sanitize_text_field( $_POST['first_name'] ?? '' );
		$last   = sanitize_text_field( $_POST['last_name']  ?? '' );
		$email  = sanitize_email(      $_POST['email']      ?? '' );
		$org    = sanitize_text_field( $_POST['org_name']   ?? '' );
		$pass   =                      $_POST['password']   ?? '';
		$pass2  =                      $_POST['password2']  ?? '';

		if ( ! $first || ! $email || ! $org || ! $pass ) {
			wp_send_json_error( array( 'message' => 'Please fill in all required fields.' ) );
		}
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
		}
		if ( email_exists( $email ) ) {
			wp_send_json_error( array( 'message' => 'An account with this email already exists. Please sign in instead.' ) );
		}
		if ( strlen( $pass ) < 8 ) {
			wp_send_json_error( array( 'message' => 'Password must be at least 8 characters.' ) );
		}
		if ( $pass !== $pass2 ) {
			wp_send_json_error( array( 'message' => 'Passwords do not match.' ) );
		}

		// ── Create WP user ────────────────────────────────────────────────
		$base     = sanitize_user( strstr( $email, '@', true ), true ) ?: 'user';
		$username = $base;
		while ( username_exists( $username ) ) {
			$username = $base . '_' . wp_rand( 100, 9999 );
		}

		$user_id = wp_create_user( $username, $pass, $email );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}

		$wp_user = new WP_User( $user_id );
		$wp_user->set_role( 'group_leader' );
		wp_update_user( array(
			'ID'           => $user_id,
			'first_name'   => $first,
			'last_name'    => $last,
			'display_name' => trim( "$first $last" ),
		) );

		// ── Create LearnDash group ─────────────────────────────────────────
		$group_id = wp_insert_post( array(
			'post_title'  => $org,
			'post_type'   => 'groups',
			'post_status' => 'publish',
			'post_author' => $user_id,
		) );

		if ( is_wp_error( $group_id ) || ! $group_id ) {
			wp_delete_user( $user_id );
			wp_send_json_error( array( 'message' => 'Could not create your group. Please try again.' ) );
		}

		// Assign user as group leader (sets learndash_group_leaders_{id} user meta).
		ld_update_leader_group_access( $user_id, $group_id, false );

		// ── Auto-login ────────────────────────────────────────────────────
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		wp_send_json_success( array(
			'redirect' => add_query_arg( 'gld_welcome', '1', self::dashboard_url() ),
		) );
	}

	// ── Login ─────────────────────────────────────────────────────────────────

	public static function handle_login(): void {
		check_ajax_referer( 'gld_auth', 'nonce' );

		$email    = sanitize_email( $_POST['email']    ?? '' );
		$pass     =                 $_POST['password'] ?? '';
		$remember = ! empty(        $_POST['remember'] );

		if ( ! $email || ! $pass ) {
			wp_send_json_error( array( 'message' => 'Email and password are required.' ) );
		}

		$wp_user = get_user_by( 'email', $email );
		if ( ! $wp_user ) {
			// Generic message — don't reveal whether the email exists.
			wp_send_json_error( array( 'message' => 'Invalid email or password.' ) );
		}

		$result = wp_signon( array(
			'user_login'    => $wp_user->user_login,
			'user_password' => $pass,
			'remember'      => $remember,
		), is_ssl() );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email or password.' ) );
		}

		$is_leader = in_array( 'group_leader',  $result->roles, true );
		$is_admin  = in_array( 'administrator', $result->roles, true );

		if ( ! $is_leader && ! $is_admin ) {
			wp_logout();
			wp_send_json_error( array( 'message' => 'This portal is for group leaders only. Please use the main site to log in.' ) );
		}

		wp_send_json_success( array( 'redirect' => self::dashboard_url() ) );
	}

	// ── Redirect already-logged-in leaders away from auth pages ──────────────

	public static function maybe_redirect_leader(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! GLD_Access::is_leader() ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		$content = $post->post_content;
		if ( has_shortcode( $content, 'gl_login' ) || has_shortcode( $content, 'gl_register' ) ) {
			wp_safe_redirect( self::dashboard_url() );
			exit;
		}
	}

	// ── URL helpers ───────────────────────────────────────────────────────────

	public static function dashboard_url(): string {
		return self::find_page_url( 'gl_dashboard' ) ?? home_url( '/dashboard/' );
	}

	public static function login_url(): string {
		return self::find_page_url( 'gl_login' ) ?? home_url( '/group-login/' );
	}

	public static function register_url(): string {
		return self::find_page_url( 'gl_register' ) ?? home_url( '/group-register/' );
	}

	private static function find_page_url( string $shortcode ): ?string {
		global $wpdb;
		$page = $wpdb->get_row( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_status = 'publish'
			   AND post_type   = 'page'
			   AND post_content LIKE %s
			 LIMIT 1",
			'%[' . $shortcode . ']%'
		) );
		return $page ? get_permalink( (int) $page->ID ) : null;
	}
}
