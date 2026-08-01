<?php
/**
 * CA Framework - Required Plugin Class
 *
 * Manages required plugin dependencies with install/activate buttons.
 * Automatically hides plugins that are already active.
 *
 * @package CA_Framework
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CA_Framework_Required_Plugin' ) ) {

    /**
     * Required Plugin Class
     */
    class CA_Framework_Required_Plugin {

        /**
         * Plugin slug
         *
         * @var string
         */
        private $plugin_slug;
        private $plugin_basename;

        /**
         * List of required plugins
         *
         * @var array
         */
        private $plugins = array();

        /**
         * Constructor
         *
         * @param string $plugin_slug Parent plugin identifier.
         * @param array  $plugins     List of required plugin configurations.
         */
        public function __construct( $plugin_slug, $plugins = array(), $plugin_basename = null ) {
            $this->plugin_slug   = $plugin_slug;
            $this->plugins       = $plugins;
            $this->plugin_basename = $plugin_basename;
        }

        //get current this plugin info from plugin_slug
        public function get_plugin_info() {
            //installed plugins
            $installed_plugins = get_plugins();
            return isset( $installed_plugins[ $this->plugin_basename ] ) ? $installed_plugins[ $this->plugin_basename ] : array();
        }

        /**
         * Show the required plugins notice.
         *
         * @return $this
         */
        public function show() {
            add_action( 'admin_notices', array( $this, 'render' ) );
            return $this;
        }

        /**
         * Render on a specific admin page (callback).
         */
        public function render_section() {
            $this->render( true );
        }

        /**
         * Get plugin status.
         *
         * @param string $plugin_path Plugin path (e.g., 'woocommerce/woocommerce.php').
         * @return string 'active', 'installed', or 'not_installed'
         */
        public static function get_plugin_status( $plugin_path ) {
            if ( ! function_exists( 'is_plugin_active' ) ) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            if ( is_plugin_active( $plugin_path ) ) {
                return 'active';
            }

            $installed = get_plugins();
            if ( isset( $installed[ $plugin_path ] ) ) {
                return 'installed';
            }

            return 'not_installed';
        }

        /**
         * Render required plugins notice.
         *
         * @param bool $as_section Render as section (no notice wrapper).
         */
        public function render( $as_section = false ) {
            if ( ! current_user_can( 'install_plugins' ) ) {
                return;
            }

            $pending_plugins = array();

            foreach ( $this->plugins as $plugin ) {
                $plugin = wp_parse_args( $plugin, array(
                    'name'        => '',
                    'slug'        => '',
                    'path'        => '',
                    'description' => '',
                    'icon'        => '',
                    'required'    => true,
                ) );

                $status = self::get_plugin_status( $plugin['path'] );

                // Skip already active plugins
                if ( 'active' === $status ) {
                    continue;
                }

                $plugin['status'] = $status;
                $pending_plugins[] = $plugin;
            }

            if ( empty( $pending_plugins ) ) {
                return;
            }

            if ( ! $as_section ) {
                echo '<div class="notice ca-fw-notice ca-fw-required-notice">';
            }

            $plugin_info = $this->get_plugin_info();
            // dd($plugin_info);
            /**
             * Debugging information for required plugins
             * array(15) {
  ["Name"]=>
  string(29) "Product Table for WooCommerce"
  ["PluginURI"]=>
  string(92) "https://wooproducttable.com/pricing/?utm_source=WPT+Plugin+Dashboard&utm_medium=Free+Version"
  ["Version"]=>
  string(5) "6.0.3"
  ["Description"]=>
  string(251) "(WooProductTable - woo product table) WooCommerce product table plugin helps you to display your products in a searchable table layout with filters. Boost conversions & sales. Woo Product Table is best for Wholesale. wooproducttable, woo-product-table"
  ["Author"]=>
  string(18) "CodeAstrology Team"
  ["AuthorURI"]=>
  string(84) "https://wooproducttable.com/?utm_source=WPT+Plugin+Dashboard&utm_medium=Free+Version"
  ["TextDomain"]=>
  string(17) "woo-product-table"
  ["DomainPath"]=>
  string(11) "/languages/"
  ["Network"]=>
  bool(false)
  ["RequiresWP"]=>
  string(3) "6.2"
  ["RequiresPHP"]=>
  string(0) ""
  ["UpdateURI"]=>
  string(0) ""
  ["RequiresPlugins"]=>
  string(11) "woocommerce"
  ["Title"]=>
  string(29) "Product Table for WooCommerce"
  ["AuthorName"]=>
  string(18) "CodeAstrology Team"
}
             */
            ?>
            <div class="ca-fw-plugins-wrap ca-fw-required-plugins">
                <div class="ca-fw-plugins-header">
                    <span class="dashicons dashicons-warning"></span>
                    <h3>Required Plugins for <?php echo esc_html( $plugin_info['Name'] ); ?> </h3>
                    <p><?php echo esc_html( 'The following plugins are required for full functionality:' ); ?></p>
                </div>
                <div class="ca-fw-plugins-grid">
                    <?php foreach ( $pending_plugins as $plugin ) : ?>
                        <div class="ca-fw-plugin-card" data-plugin-slug="<?php echo esc_attr( $plugin['slug'] ); ?>" data-plugin-path="<?php echo esc_attr( $plugin['path'] ); ?>">
                            <?php if ( ! empty( $plugin['icon'] ) ) : ?>
                                <div class="ca-fw-plugin-icon">
                                    <img src="<?php echo esc_url( $plugin['icon'] ); ?>" alt="<?php echo esc_attr( $plugin['name'] ); ?>">
                                </div>
                            <?php else : ?>
                                <div class="ca-fw-plugin-icon ca-fw-plugin-icon-placeholder">
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                </div>
                            <?php endif; ?>
                            <div class="ca-fw-plugin-info">
                                <h4><?php echo esc_html( $plugin['name'] ); ?></h4>
                                <?php if ( ! empty( $plugin['description'] ) ) : ?>
                                    <p><?php echo esc_html( $plugin['description'] ); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="ca-fw-plugin-action">
                                <?php if ( 'not_installed' === $plugin['status'] ) : ?>
                                    <button class="ca-fw-btn ca-fw-btn-install ca-fw-btn-primary" data-action="install" data-slug="<?php echo esc_attr( $plugin['slug'] ); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php esc_html_e( 'Install', 'flavor-jelee' ); ?>
                                    </button>
                                <?php elseif ( 'installed' === $plugin['status'] ) : ?>
                                    <button class="ca-fw-btn ca-fw-btn-activate ca-fw-btn-success" data-action="activate" data-path="<?php echo esc_attr( $plugin['path'] ); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e( 'Activate', 'flavor-jelee' ); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="ca-fw-plugins-footer">
                    <p>
                        <?php echo esc_html( 'Need help? Check the documentation or contact support.' ); ?>
                                <!-- Plugins Download link support link -->
                    | <a href="<?php echo esc_url( $plugin_info['PluginURI'] ?? '#' ); ?>" target="_blank"><?php echo esc_html( 'Plugin URI' ); ?></a>
                    | <a href="<?php echo esc_url( $plugin_info['AuthorURI'] ?? '#' ); ?>" target="_blank"><?php echo esc_html( 'Author URI' ); ?></a>
                </p>

                </div>
            </div>
            <?php
            if ( ! $as_section ) {
                echo '</div>';
            }
        }
    }
}
