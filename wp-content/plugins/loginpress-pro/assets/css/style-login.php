<?php
/**
 * LoginPress Pro - Login Page Style Generator
 *
 * This file generates dynamic CSS and JavaScript for the custom login page styling.
 * It handles Google Fonts integration, reCAPTCHA scaling, and responsive design
 * for the LoginPress Pro plugin's login page customization.
 *
 * @package LoginPress Pro
 * @subpackage Assets
 * @since 1.0.0
 * @version 5.0.1
 *
 * @author WPBrigadeance
 */

$loginpress_pro_array = (array) get_option( 'loginpress_customization' );

/**
 * Retrieve a specific option key from the LoginPress Pro array.
 *
 * @param string $loginpress_key The key to retrieve from the array.
 * @param array  $loginpress_pro_array The array of LoginPress Pro options.
 * @return mixed|null The value of the key if it exists, null otherwise.
 * @since 1.0.0
 */
function loginpress_pro_get_option_key( $loginpress_key, $loginpress_pro_array ) {

	if ( array_key_exists( $loginpress_key, $loginpress_pro_array ) ) {

		return $loginpress_pro_array[ $loginpress_key ];
	}
}

$loginpress_google_font = loginpress_pro_get_option_key( 'google_font', $loginpress_pro_array );

/**
* Register custom fonts.
*/
if ( $loginpress_google_font ) {

	$json_file       = file_get_contents( LOGINPRESS_PRO_ROOT_PATH . '/fonts/google-web-fonts.txt' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$json_font       = json_decode( $json_file );
	$json_font_array = $json_font->items;

	$font_array = array();
	foreach ( $json_font_array as $key ) {

		$loginpress_get_font = $loginpress_google_font === $key->family ? $loginpress_google_font : false;
		if ( $loginpress_get_font ) :
			$font_array[] = $key;
endif;
	}

	if ( ! empty( $font_array ) ) {
		// Font was found.
		$loginpress_font_name = $font_array[0]->family;
		$font_weights         = $font_array[0]->variants;
		$font_weight          = implode( ',', $font_weights );
		$subsets              = $font_array[0]->subsets;
		$subset               = implode( ',', $subsets );

		$font_families   = array();
		$font_families[] = "{$loginpress_font_name}:{$font_weight}";

		$query_args = array(
			'family' => rawurlencode( implode( '|', $font_families ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
			'subset' => rawurlencode( $subset ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
		);

		$fonts_url            = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
		$loginpress_fonts_url = esc_url_raw( $fonts_url );

	} else {
		// Font not found in current list — fallback.
		$loginpress_font_name = 'sans-serif'; // Fallback.
		$loginpress_fonts_url = ''; // No external font.
	}
}

if ( $loginpress_google_font ) { ?>
	<link href="<?php echo esc_url( $loginpress_fonts_url ); // phpcs:ignore ?>" rel='stylesheet'>
	<style type="text/css">
		body{
			font-family: <?php echo esc_attr( '"' . $loginpress_font_name . '"' ); ?> !important;
		}
		.wp-core-ui #login .wp-generate-pw,
		.login input[type="submit"], .login form .input, .login input[type="text"] {
			font-family: <?php echo esc_attr( '"' . $loginpress_font_name . '"' ); ?> !important;
		}
	</style>

<?php } ?>
