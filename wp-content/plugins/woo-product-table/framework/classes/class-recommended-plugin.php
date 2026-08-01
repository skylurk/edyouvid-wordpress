<?php
/**
 * CA Framework - Recommended Plugin Class
 *
 * Displays recommended plugins with modern card-based UI.
 *
 * @package CA_Framework
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CA_Framework_Recommended_Plugin' ) ) {

    /**
     * Recommended Plugin Class
     */
    class CA_Framework_Recommended_Plugin {

        /**
         * Plugin slug
         *
         * @var string
         */
        private $plugin_slug;

        /**
         * Dismiss ID
         *
         * @var string
         */
        private $dismiss_id;

        /**
         * List of recommended plugins
         *
         * @var array
         */
        private $plugins = array();

        /**
         * Whether dismiss is disabled (for show_on_hook rendering)
         *
         * @var bool
         */
        private $no_dismiss = false;

        /**
         * Constructor
         *
         * @param string $plugin_slug Parent plugin identifier.
         * @param array  $plugins     List of recommended plugin configurations.
         */
        public function __construct( $plugin_slug, $plugins = array(), $dismiss_id = '' ) {
            $this->plugin_slug = $plugin_slug;
            $this->plugins     = $plugins;
            $this->dismiss_id  = empty( $dismiss_id ) ? $this->plugin_slug . '_recommended' : $dismiss_id;
        }

        /**
         * Show in admin notices (with dismiss).
         *
         * @return $this
         */
        public function show() {
            add_action( 'admin_notices', array( $this, 'render' ) );
            return $this;
        }

        /**
         * Display on a specific action hook WITHOUT dismiss button.
         *
         * Can be chained: ->show_on_hook('hook_a')->show_on_hook('hook_b')
         *
         * @param string $hook     Action hook name.
         * @param int    $priority Hook priority.
         * @return $this
         */
        public function show_on_hook( $hook, $priority = 10 ) {
            add_action( $hook, array( $this, 'render_no_dismiss' ), $priority );
            return $this;
        }

        /**
         * Render as a standalone section (e.g., inside a settings page).
         */
        public function render_section() {
            $this->render( true );
        }

        /**
         * Render without dismiss (for show_on_hook).
         */
        public function render_no_dismiss() {
            $this->no_dismiss = true;
            $this->render( true );
            $this->no_dismiss = false;
        }

        /**
         * Render the recommended plugins.
         *
         * @param bool $as_section Whether to render as a section (no notice wrapper).
         */
        public function render( $as_section = false ) {
            if ( ! current_user_can( 'install_plugins' ) ) {
                return;
            }

            $dismiss_id = $this->dismiss_id;

            // Only check dismiss for normal show() (not show_on_hook)
            if ( ! $this->no_dismiss && CA_Framework_Dismiss_Handler::is_dismissed( $dismiss_id ) ) {
                return;
            }

            $display_plugins = array();

            foreach ( $this->plugins as $plugin ) {
                $plugin = wp_parse_args( $plugin, array(
                    'name'        => '',
                    'slug'        => '',
                    'path'        => '',
                    'description' => '',
                    'icon'        => '',
                    'url'         => '',
                ) );

                $status = CA_Framework_Required_Plugin::get_plugin_status( $plugin['path'] );

                // Skip active plugins
                if ( 'active' === $status ) {
                    continue;
                }

                $plugin['status'] = $status;
                $display_plugins[] = $plugin;
            }

            if ( empty( $display_plugins ) ) {
                return;
            }

            $show_dismiss = ! $this->no_dismiss;

            if ( ! $as_section ) {
                echo '<div class="notice ca-fw-notice ca-fw-recommended-notice">';
            }
            ?>
            <div class="ca-fw-plugins-wrap ca-fw-recommended-plugins" data-dismiss-id="<?php echo esc_attr( $dismiss_id ); ?>">
                <div class="ca-fw-plugins-header">
                    <span class="dashicons dashicons-star-filled"></span>
                    <h3><?php esc_html_e( 'Recommended Plugins', 'flavor-jelee' ); ?></h3>
                    <p><?php esc_html_e( 'Enhance your experience with these recommended plugins:', 'flavor-jelee' ); ?></p>
                    <?php if ( $show_dismiss && ! $as_section ) : ?>
                        <button class="ca-fw-dismiss-btn ca-fw-dismiss" data-dismiss-id="<?php echo esc_attr( $dismiss_id ); ?>" data-dismiss-type="permanent" title="<?php esc_attr_e( 'Dismiss', 'flavor-jelee' ); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="ca-fw-plugins-grid">
                    <?php foreach ( $display_plugins as $plugin ) : ?>
                        <div class="ca-fw-plugin-card ca-fw-recommended-card" data-plugin-slug="<?php echo esc_attr( $plugin['slug'] ); ?>" data-plugin-path="<?php echo esc_attr( $plugin['path'] ); ?>">
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
                                    <button class="ca-fw-btn ca-fw-btn-install ca-fw-btn-outline" data-action="install" data-slug="<?php echo esc_attr( $plugin['slug'] ); ?>">
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
            </div>
            <?php
            if ( ! $as_section ) {
                echo '</div>';
            }
        }
    }
}
