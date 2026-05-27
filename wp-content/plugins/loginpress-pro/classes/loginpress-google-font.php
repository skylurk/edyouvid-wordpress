<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * LoginPress Google Fonts.
 *
 * @package LoginPress Pro
 * @since 1.0.1
 * @version 6.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if WP_Customize_Control does not exsist.
if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return null;
}

/**
 * A class to create a dropdown for all google fonts
 *
 * @access  public
 */
class LoginPress_Google_Fonts extends WP_Customize_Control {

	/**
	 * Font styles.
	 *
	 * @var bool|array Font data array or false.
	 */
	private $fonts = false;

	/**
	 * Class Constructor.
	 *
	 * @param mixed $manager The manager.
	 * @param int   $id The ID.
	 * @param array $args Arguments.
	 * @param array $options The options.
	 * @return void
	 */
	public function __construct( $manager, $id, $args = array(), $options = array() ) {
		$fonts       = apply_filters( 'lognipress_fonts', $this->get_fonts() ); // Deprecated typo.
		$this->fonts = apply_filters( 'loginpress_fonts', $fonts );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Render the content of the category dropdown.
	 *
	 * @return void
	 * @since 2.3.3
	 * @version 6.1.0
	 */
	public function render_content() {

		if ( ! empty( $this->fonts ) ) {
			usort( $this->fonts, $this->arr_sort_objs_by_Key( 'family', 'ACS' ) ); ?>
			<label>
				<span class="customize-category-select-control"><?php echo esc_html( $this->label ); ?></span>
				<select <?php $this->link(); ?>>
				<option value="">-- <?php esc_html_e( 'Default', 'loginpress-pro' ); ?> --</option>
					<?php
					foreach ( $this->fonts as $k => $v ) {
						printf( '<option value="%s" %s>%s</option>', esc_attr( $v->family ), selected( $this->value(), $k, false ), esc_attr( $v->family ) );
					}
					?>
				</select>
			</label>
			<?php
		}
	}

	/**
	 * Get the google fonts from the API or in the cache.
	 *
	 * @return string
	 */
	public function get_fonts() {

		$font_file = LOGINPRESS_PRO_ROOT_PATH . '/fonts/google-web-fonts.txt';
		if ( file_exists( $font_file ) ) {
			$content = json_decode( file_get_contents( $font_file ) ); // @codingStandardsIgnoreLine.
		}

		return $content->items;
	}

	/**
	 * Sorting the array key by obj.
	 *
	 * @param string $key You want to sort.
	 * @param string $order ACS | DESC.
	 * @return mixed
	 * @since 2.3.3
	 */
	public function arr_sort_objs_by_Key( $key, $order = 'DESC' ) {

		return function ( $a, $b ) use ( $key, $order ) {

			// Swap order if necessary.
			if ( 'DESC' === $order ) {
				list($a, $b) = array( $b, $a );
			}

			// Check data type.
			if ( is_numeric( $a->$key ) ) {
				return $a->$key - $b->$key; // compare numeric.
			} else {
				return strnatcasecmp( $a->$key, $b->$key ); // compare string.
			}
		};
	}
}
?>
