<?php
/**
 * Microsoft Login initializer class.
 * Ensure script is not executed directly by checking for ABSPATH constant.
 *
 * @package LoginPress Social Login
 * @since 3.0.0
 * @version 6.1.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );


use LoginPress_Social_Login\Microsoft\Auth;
use LoginPress_Social_Login\Microsoft\Models\User;
use LoginPress_Social_Login\Microsoft\Handlers\Session;

/**
 * Class LoginPress_Microsoft
 * Handles Microsoft login functionality.
 *
 * @since 3.0.0
 * @version 6.1.0
 */
class LoginPress_Microsoft {

	/**
	 * Used for authenticating requests.
	 *
	 * @since 3.0.0
	 * @var string Microsoft tenant identifier.
	 */
	private $tenant;

	/**
	 * Unique identifier for the registered application.
	 *
	 * @since 3.0.0
	 * @var string Microsoft client ID.
	 */
	private $client_id;

	/**
	 * Secret key associated with the client ID.
	 *
	 * @since 3.0.0
	 * @var string Microsoft client secret.
	 */
	private $client_secret;

	/**
	 * The URL where Microsoft will redirect after the user grants/denies permission.
	 *
	 * @since 3.0.0
	 * @var string Callback URL for Microsoft login.
	 */
	private $callback;

	/**
	 * The permissions the application is requesting from the user.
	 *
	 * @since 3.0.0
	 * @var array Scopes required for Microsoft login.
	 */
	private $scopes;


	/**
	 * Constructor to initialize class properties.
	 *
	 * @param string $tenant        Microsoft tenant.
	 * @param string $client_id     Microsoft client ID.
	 * @param string $client_secret Microsoft client secret.
	 * @param string $callback      Callback URL for Microsoft login.
	 * @param array  $scopes        Scopes required for Microsoft login.
	 *
	 * @since 3.0.0
	 */
	public function __construct( $tenant, $client_id, $client_secret, $callback, $scopes ) {
		$this->tenant        = $tenant;
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->callback      = $callback;
		$this->scopes        = $scopes;
	}

	/**
	 * Initiates Microsoft login process.
	 *
	 * @return void
	 * @since 3.0.0
	 * @version 6.1.0
	 */
	public function loginpress_microsoft_login() {
		// Create Microsoft Auth instance and redirect to the login URL.
		$microsoft = new Auth( $this->tenant, $this->client_id, $this->client_secret, $this->callback, $this->scopes );
		wp_redirect( $microsoft->getAuthUrl() . 'lpsl_login=microsoft' ); // phpcs:ignore
		exit;
	}

	/**
	 * Handles returning Microsoft user.
	 *
	 * @since 3.0.0
	 * @return mixed User data.
	 */
	public function loginpress_handle_returning_user() {
		// Create Microsoft Auth instance.
		$auth = new Auth( $this->tenant, $this->client_id, $this->client_secret, $this->callback, $this->scopes );

		// Cut-off string used in state parameter.
		$string_cut = 'lpsl';

		// Extract state value from request.
		$request_state = isset( $_REQUEST['state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state         = (int) substr( $request_state, 0, strpos( $request_state, $string_cut ) );

		// Get tokens and set access token.
		$request_code = isset( $_REQUEST['code'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tokens       = $auth->getToken( $request_code, Session::get( 'state' ) );
		$access_token = $tokens->access_token;
		$auth->setAccessToken( $access_token );

		// Create User instance and return user data.
		$user = new User();
		return $user->data;
	}
}
