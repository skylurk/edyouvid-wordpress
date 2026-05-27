<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * LoginPress WPML Compatibility Helper.
 *
 * Handles WPML language-aware redirects for LoginPress Login Redirects addon.
 *
 * @package LoginPress
 * @subpackage LoginRedirects
 * @since 6.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LoginPress_WPML_Compatibility' ) ) {

	/**
	 * LoginPress WPML Compatibility Class.
	 *
	 * @package LoginPress
	 * @subpackage LoginRedirects
	 * @since 6.1.1
	 */
	class LoginPress_WPML_Compatibility {

		/**
		 * Cached active languages array.
		 *
		 * @var array|null
		 */

		private static $active_languages = null;
		/**
		 * Cached default directory setting.
		 *
		 * @var bool|null
		 */
		private static $use_directory_for_default = null;

		/**
		 * Get SitePress instance if WPML is active.
		 *
		 * @since 6.1.1
		 * @return SitePress|false SitePress instance or false.
		 */
		private static function loginpress_get_sitepress() {

			global $sitepress;
			return $sitepress ? $sitepress : false;
		}

		/**
		 * Get current language code from WPML.
		 *
		 * @since 6.1.1
		 * @return string|false Language code or false if WPML is not active.
		 */
		public static function loginpress_get_current_language() {
			$sitepress = self::loginpress_get_sitepress();
			return $sitepress ? $sitepress->get_current_language() : false;
		}

		/**
		 * Get default language code from WPML.
		 *
		 * @since 6.1.1
		 * @return string|false Default language code or false if WPML is not active.
		 */
		public static function loginpress_get_default_language() {
			$sitepress = self::loginpress_get_sitepress();
			return $sitepress ? $sitepress->get_default_language() : false;
		}

		/**
		 * Get active languages with caching.
		 *
		 * @since 6.1.1
		 * @return array Active languages array.
		 */
		private static function loginpress_get_active_languages() {
			if ( null !== self::$active_languages ) {
				return self::$active_languages;
			}

			$sitepress              = self::loginpress_get_sitepress();
			self::$active_languages = $sitepress ? $sitepress->get_active_languages() : array();

			return self::$active_languages;
		}

		/**
		 * Check if default language should use directory.
		 *
		 * @since 6.1.1
		 * @return bool True if default language uses directory.
		 */
		private static function loginpress_should_use_directory_for_default() {
			if ( null !== self::$use_directory_for_default ) {
				return self::$use_directory_for_default;
			}

			$sitepress = self::loginpress_get_sitepress();
			if ( ! $sitepress ) {
				self::$use_directory_for_default = false;
				return false;
			}

			$urls_setting                    = $sitepress->get_setting( 'urls' );
			self::$use_directory_for_default = isset( $urls_setting['directory_for_default_language'] )
				? (bool) $urls_setting['directory_for_default_language']
				: false;

			return self::$use_directory_for_default;
		}

		/**
		 * Extract first path segment from URL.
		 *
		 * @param string $url URL to parse.
		 * @since 6.1.1
		 * @return string|false First path segment or false.
		 */
		private static function loginpress_get_first_path_segment( $url ) {
			$url_path = wp_parse_url( $url, PHP_URL_PATH );
			if ( ! $url_path ) {
				return false;
			}

			$path_parts = array_filter( explode( '/', trim( $url_path, '/' ) ) );
			return empty( $path_parts ) ? false : reset( $path_parts );
		}

		/**
		 * Check if URL already contains a language code.
		 *
		 * @param string $url URL to check.
		 * @since 6.1.1
		 * @return bool True if URL contains language code, false otherwise.
		 */
		public static function loginpress_url_has_language_code( $url ) {

			$first_segment = self::loginpress_get_first_path_segment( $url );
			if ( ! $first_segment ) {
				return false;
			}

			$active_languages = self::loginpress_get_active_languages();
			return isset( $active_languages[ $first_segment ] );
		}

		/**
		 * Get language from URL using WPML converter.
		 *
		 * @param string $url URL to check.
		 * @since 6.1.1
		 * @return string|false Language code or false.
		 */
		private static function loginpress_get_language_from_url( $url ) {
			global $wpml_url_converter;

			if ( ! $wpml_url_converter || ! method_exists( $wpml_url_converter, 'get_language_from_url' ) ) {
				return false;
			}

			return $wpml_url_converter->get_language_from_url( $url );
		}

		/**
		 * Build full URL from server variables.
		 *
		 * @since 6.1.1
		 * @return string|false Full URL or false.
		 */
		private static function loginpress_build_current_url() {
			if ( ! isset( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return false;
			}

			$scheme = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https' : 'http'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$uri    = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			return $host ? $scheme . '://' . $host . $uri : false;
		}

		/**
		 * Get current language from request (login URL, referrer, or current language).
		 *
		 * @since 6.1.1
		 * @return string|false Language code or false.
		 */
		private static function loginpress_get_current_language_from_request() {
			// Try REQUEST_URI.
			$current_url = self::loginpress_build_current_url();
			if ( $current_url ) {
				$lang = self::loginpress_get_language_from_url( $current_url );
				if ( $lang ) {
					return $lang;
				}
			}

			// Try HTTP_REFERER.
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$lang    = self::loginpress_get_language_from_url( $referer );
				if ( $lang ) {
					return $lang;
				}
			}

			// Fallback to WPML's current language.
			return self::loginpress_get_current_language();
		}

		/**
		 * Add language prefix to path if needed.
		 *
		 * @param string $path Path to process.
		 * @param string $current_language Current language code.
		 * @param string $default_language Default language code.
		 * @since 6.1.1
		 * @return string Processed path.
		 */
		private static function loginpress_add_language_prefix( $path, $current_language, $default_language ) {
			$path = '/' . ltrim( $path, '/' );

			if ( $current_language !== $default_language || self::loginpress_should_use_directory_for_default() ) {
				$path = '/' . $current_language . $path;
			}

			return $path;
		}

		/**
		 * Process relative URL path.
		 *
		 * @param string $redirect_url Relative path (e.g., /booking).
		 * @param string $current_language Current language code.
		 * @param string $default_language Default language code.
		 * @since 6.1.1
		 * @return string Processed URL.
		 */
		private static function loginpress_process_relative_url( $redirect_url, $current_language, $default_language ) {
			global $wpml_url_converter;

			// Try WPML's built-in method first.
			if ( $wpml_url_converter && method_exists( $wpml_url_converter, 'get_strategy' ) ) {
				$strategy = $wpml_url_converter->get_strategy();
				if ( $strategy && method_exists( $strategy, 'get_home_url_relative' ) ) {
					$converted_path = $strategy->get_home_url_relative( $redirect_url, $current_language );
					if ( $converted_path && strpos( $converted_path, 'http' ) !== 0 ) {
						return home_url( $converted_path );
					}
					if ( $converted_path ) {
						return $converted_path;
					}
				}
			}

			// Fallback: manually add language prefix.
			$path = self::loginpress_add_language_prefix( $redirect_url, $current_language, $default_language );
			return home_url( $path );
		}

		/**
		 * Process absolute URL.
		 *
		 * @param string $redirect_url Absolute URL.
		 * @param string $current_language Current language code.
		 * @param string $default_language Default language code.
		 * @since 6.1.1
		 * @return string Processed URL.
		 */
		private static function loginpress_process_absolute_url( $redirect_url, $current_language, $default_language ) {
			global $wpml_url_converter;

			// Try WPML's built-in converter first.
			if ( $wpml_url_converter && method_exists( $wpml_url_converter, 'convert_url' ) ) {
				$converted_url = $wpml_url_converter->convert_url( $redirect_url, $current_language );
				if ( $converted_url && $converted_url !== $redirect_url ) {
					return $converted_url;
				}
			}

			// Fallback: manually process.
			$parsed_url = wp_parse_url( $redirect_url );
			if ( ! $parsed_url || ! isset( $parsed_url['host'] ) ) {
				return $redirect_url;
			}

			$home_parsed = wp_parse_url( home_url() );
			if ( ! isset( $home_parsed['host'] ) || $parsed_url['host'] !== $home_parsed['host'] ) {
				return $redirect_url;
			}

			// Double-check for language code in path.
			$first_segment = self::loginpress_get_first_path_segment( $redirect_url );
			if ( $first_segment && isset( self::loginpress_get_active_languages()[ $first_segment ] ) ) {
				return $redirect_url;
			}

			$path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';

			// Remove home path if present.
			$home_path = isset( $home_parsed['path'] ) ? rtrim( $home_parsed['path'], '/' ) : '';
			if ( $home_path && strpos( $path, $home_path ) === 0 ) {
				$path = substr( $path, strlen( $home_path ) );
			}

			$path = self::loginpress_add_language_prefix( $path, $current_language, $default_language );

			// Rebuild URL.
			$scheme   = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : 'https://';
			$host     = $parsed_url['host'];
			$port     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
			$query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
			$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

			return $scheme . $host . $port . $home_path . $path . $query . $fragment;
		}

		/**
		 * Convert redirect URL to include current language prefix if needed.
		 *
		 * @param string $redirect_url The redirect URL.
		 * @since 6.1.1
		 * @return string Modified redirect URL with language prefix if applicable.
		 */
		public static function loginpress_wpml_process_redirect_url( $redirect_url ) {

			if ( self::loginpress_url_has_language_code( $redirect_url ) ) {
				return $redirect_url;
			}

			$current_language = self::loginpress_get_current_language_from_request();
			$default_language = self::loginpress_get_default_language();

			if ( ! $current_language ) {
				$current_language = $default_language;
			}

			if ( ! $current_language ) {
				return $redirect_url;
			}

			// Check if relative path.
			if ( '/' === substr( $redirect_url, 0, 1 ) ) {
				return self::loginpress_process_relative_url( $redirect_url, $current_language, $default_language );
			}

			return self::loginpress_process_absolute_url( $redirect_url, $current_language, $default_language );
		}
	}
}
