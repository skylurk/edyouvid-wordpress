<?php
namespace WRLDAdmin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'HelpPage' ) ) {

	/**
	 * Class for showing tabs of WRLD.
	 */
	class HelpPage {

		public function __construct() {
			if ( is_rtl() ) {
				wp_enqueue_style( 'wrld_admin_dashboard_contentainer_style', WRLD_REPORTS_SITE_URL . '/includes/admin/dashboard/assets/css/content-page.rtl.css', array(), WRLD_PLUGIN_VERSION );
			} else {
				wp_enqueue_style( 'wrld_admin_dashboard_contentainer_style', WRLD_REPORTS_SITE_URL . '/includes/admin/dashboard/assets/css/content-page.css', array(), WRLD_PLUGIN_VERSION );
			}
		}

		public static function render() {
			?>
			<div class='wrld-dashboard-page-container'>
				<?php
					self::content_main();
					self::content_sidebar();
				?>
			</div>
			<?php
		}

		public static function content_main() {
			?>
				<div class='wrld-dashboard-page-content'>
					<div class='wrld-help-page-section'>
					<?php
						self::get_question_answer_collaps();
					?>
					</div>
				</div>
			<?php
		}

		public static function content_sidebar() {
			?>
				<div class='wrld-dashboard-page-sidebar'>
					<?php self::sidebar_block_upgrade(); ?>
					<?php self::sidebar_block_connect(); ?>
				</div>
			<?php
		}

		public static function sidebar_block_upgrade() {
			if ( defined( 'LDRP_PLUGIN_VERSION' ) ) {
				return '';
			}
			?>
				<div class='wrld-sidebar-block'>
					<div class='wrld-sidebar-block-head'>
						<div class='wrld-sidebar-head-icon'>
							<span class='upgrade-icon'></span>
						</div>
						<div class='wrld-sidebar-head-text'>
							<span><?php esc_html_e( 'Upgrade your FREE LearnDash LMS - Reports  Plugin to PRO!', 'learndash-reports-by-wisdmlabs' ); ?></span>
						</div>
					</div>
					<div class='wrld-sidebar-body'>
						<div class='wrld-sidebar-body-text'>
							<span><?php esc_html_e( 'Click the button below to upgrade your FREE LearnDash LMS - Reports Plugin to PRO!', 'learndash-reports-by-wisdmlabs' ); ?></span>
						</div>
						<a href="https://www.learndash.com/reports-by-learndash" target='__blank'>
							<button class='wrld-sidebar-body-button'><?php esc_html_e( 'Upgrade to PRO', 'learndash-reports-by-wisdmlabs' ); ?></button>
						</a>
					</div>
				</div>
			<?php
		}

		public static function sidebar_block_connect() {
			?>
			<div class='wrld-sidebar-block'>
					<div class='wrld-sidebar-block-head'>
						<div class='wrld-sidebar-head-icon'>
							<span class='contact-icon'></span>
						</div>
						<div class='wrld-sidebar-head-text'>
							<span><?php esc_html_e( 'Connect with us', 'learndash-reports-by-wisdmlabs' ); ?></span>
						</div>
					</div>
					<div class='wrld-sidebar-body'>
						<div class='wrld-sidebar-body-text'>
							<span><?php esc_html_e( 'Shoot us an email at ', 'learndash-reports-by-wisdmlabs' ); ?></span>
							<span><a href='mailto:support@learndash.com'><strong>support@learndash.com</strong></a></span>
							<span><?php esc_html_e( ' and we would be delighted to help you out.', 'learndash-reports-by-wisdmlabs' ); ?></span>
						</div>
						</div>
					</div>
				</div>
			<?php
		}



		public static function get_question_answer_collaps() {
			$help_articles = array(
				array(
					'title' => __( 'Documentation', 'learndash-reports-by-wisdmlabs' ),
					'link'  => 'https://www.learndash.com/support/docs/add-ons/reports-for-learndash/',
				),
			);
			?>
			<div class='wrld-section-head'>
				<div class='help-icon'></div>
				<div class='wrld-section-head-text'><span class='text'><?php esc_html_e( 'Need help?', 'learndash-reports-by-wisdmlabs' ); ?></span></div>
			</div>
			<div class='wrld-section-subhead'>
				<div class='wrld-section-subhead-text'>
					<?php esc_html_e( 'Refer the following links from the documentation of the plugin:', 'learndash-reports-by-wisdmlabs' ); ?>
				</div>
			</div>
			<ul class='wrld-help-link-wrapper'>
			<?php
			foreach ( $help_articles as $article ) {
				?>
					<li>
						<a class='wrld-help-page-links'  target="__blank" href="<?php echo esc_attr( $article['link'] ); ?>">
							<span><?php echo esc_html( $article['title'] ); ?></span>
						</a>
					</li>
				<?php
			}
			?>
			</ul>
			<?php
		}

		/**
		 * Output the changelog section.
		 *
		 * @deprecated 1.8.2 The method is no longer used.
		 */
		public static function get_help_changelog_section() {
			_deprecated_function( __METHOD__, '1.8.2' );
		}
	}
}
