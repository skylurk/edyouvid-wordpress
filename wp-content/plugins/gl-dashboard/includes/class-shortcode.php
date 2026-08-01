<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_Shortcode {

	public static function register(): void {
		add_shortcode( 'gl_dashboard', array( __CLASS__, 'render' ) );
		add_shortcode( 'gl_register',     array( __CLASS__, 'render_register' ) );
		add_shortcode( 'gl_login',        array( __CLASS__, 'render_login' ) );
		add_shortcode( 'gl_portal_login', array( __CLASS__, 'render_portal_login' ) );
	}

	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="gld-notice">Please <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to access the Group Leader Dashboard.</p>';
		}

		if ( ! GLD_Access::is_leader() ) {
			return '<p class="gld-notice">You do not have permission to access this dashboard.</p>';
		}

		self::enqueue_assets();

		ob_start();
		include GLD_PLUGIN_DIR . 'templates/dashboard.php';
		return ob_get_clean();
	}

	public static function render_register(): string {
		self::enqueue_auth_assets();
		ob_start();
		include GLD_PLUGIN_DIR . 'templates/register.php';
		return ob_get_clean();
	}

	public static function render_login(): string {
		self::enqueue_auth_assets();
		ob_start();
		include GLD_PLUGIN_DIR . 'templates/login.php';
		return ob_get_clean();
	}

	public static function render_portal_login(): string {
		self::enqueue_auth_assets();
		ob_start();
		include GLD_PLUGIN_DIR . 'templates/portal-login.php';
		return ob_get_clean();
	}

	private static function enqueue_auth_assets(): void {
		wp_enqueue_style(
			'gld-auth',
			GLD_PLUGIN_URL . 'assets/auth.css',
			array(),
			GLD_VERSION
		);
		wp_enqueue_script(
			'gld-auth-alpine',
			GLD_PLUGIN_URL . 'assets/alpine.min.js',
			array(),
			'3.14.1',
			true
		);
		wp_localize_script( 'gld-auth-alpine', 'GLD_AUTH', array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'gld_auth' ),
			'dashUrl'     => GLD_Auth::dashboard_url(),
			'loginUrl'    => GLD_Auth::login_url(),
			'registerUrl' => GLD_Auth::register_url(),
		) );
	}

	private static function enqueue_assets(): void {
		wp_enqueue_style(
			'gld-dashboard',
			GLD_PLUGIN_URL . 'assets/dashboard.css',
			array(),
			GLD_VERSION
		);

		wp_enqueue_script(
			'gld-chartjs',
			GLD_PLUGIN_URL . 'assets/chart.min.js',
			array(),
			'4.4.4',
			true
		);

		// app.js must load BEFORE alpine.min.js so our alpine:init listener
		// is registered before Alpine fires that event during its own load.
		wp_enqueue_script(
			'gld-app',
			GLD_PLUGIN_URL . 'assets/app.js',
			array( 'gld-chartjs' ),
			GLD_VERSION,
			true
		);

		// Flutterwave inline checkout SDK — loaded before app.js so FlutterwaveCheckout() is available.
		wp_enqueue_script( 'flutterwave-inline', 'https://checkout.flutterwave.com/v3.js', array(), null, true );

		// Pass nonce, REST path, and payment config to the frontend.
		// The JS constructs the full URL from window.location.origin so it
		// always matches the actual domain serving the page, even on staging.
		wp_localize_script( 'gld-app', 'GLD', array(
			'restPath'    => '/wp-json/gl-dashboard/v1',
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'flwPublicKey'=> GLD_Billing::get_public_key(),
			'currency'    => get_woocommerce_currency(),
			'siteName'    => get_bloginfo( 'name' ),
		) );

		// Alpine loads last — its alpine:init event fires after app.js has
		// already registered the glDashboard component.
		wp_enqueue_script(
			'gld-alpine',
			GLD_PLUGIN_URL . 'assets/alpine.min.js',
			array( 'gld-app' ),
			'3.14.1',
			true
		);
	}
}
