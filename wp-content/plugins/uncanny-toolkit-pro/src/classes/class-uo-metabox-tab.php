<?php
/**
 * UO_Metabox_Tab — restore WP 7.0 classic-metabox visibility on LearnDash screens.
 *
 * WP 7.0 renders `normal`/`advanced` metaboxes inside a collapsible "Meta Boxes"
 * pane that defaults closed (edit-post.js: isLegacy=isDevicePreview → hidden=!isOpen).
 * This helper:
 *   1. Appends a dedicated LearnDash header tab carrying this plugin's metabox IDs
 *      (filter: learndash_header_tab_menu), so the boxes get a clear home.
 *   2. Seeds the editor preference `metaBoxesMainIsOpen=true` (only when unset) so
 *      the pane is open by default — required because LD's JS sets display:block on
 *      the postbox but cannot override the `hidden` ancestor pane.
 *
 * Visual-only: no DOM moves, no save-path changes. Identical across Uncanny plugins;
 * only $config differs. Spec: docs/superpowers/specs/2026-05-28-wp7-metabox-tabs-design.md
 *
 * @package uncanny_pro_toolkit
 */

namespace uncanny_pro_toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

final class UO_Metabox_Tab {

	/**
	 * Guard against double-registration if booted more than once.
	 *
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Post-type => [ 'tab_id' => string, 'tab_label' => string, 'metaboxes' => string[] ].
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Boot once. Safe to call repeatedly.
	 *
	 * @param array $config Keyed by post type.
	 *
	 * @return void
	 */
	public static function boot( array $config ) {
		if ( self::$booted || empty( $config ) ) {
			return;
		}
		self::$booted = true;
		new self( $config );
	}

	/**
	 * @param array $config Keyed by post type.
	 */
	private function __construct( array $config ) {
		$this->config = $config;
		add_filter( 'learndash_header_tab_menu', array( $this, 'register_tab' ), 999, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'force_open_pane' ) );
	}

	/**
	 * Append this plugin's tab (carrying its metabox IDs) to the LearnDash header tabs.
	 *
	 * @param array  $tabs             LD header tabs.
	 * @param string $menu_tab_key     Unused.
	 * @param string $screen_post_type Current post type.
	 *
	 * @return array
	 */
	public function register_tab( $tabs, $menu_tab_key, $screen_post_type ) {
		// Only the single post-edit screen (post.php / post-new.php, base 'post') renders
		// metaboxes. LD applies this filter on list screens too (edit.php, base 'edit'),
		// where these boxes do not exist — so without this guard the tab leaks onto the
		// All-<post-type> listing page.
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base ) {
			return $tabs;
		}

		// Skip unrelated post types AND any entry with no metaboxes — never render an
		// empty tab. NOTE: LD builds tabs on in_admin_header, BEFORE add_meta_boxes, so
		// we cannot detect which boxes are actually registered here; contributors must
		// gate their config on feature/module availability.
		if ( ! is_array( $tabs ) || empty( $this->config[ $screen_post_type ]['metaboxes'] ) ) {
			return $tabs;
		}

		$conf   = $this->config[ $screen_post_type ];
		$tabs[] = array(
			'id'                  => $conf['tab_id'],
			'name'                => $conf['tab_label'],
			'metaboxes'           => array_values( $conf['metaboxes'] ),
			'showDocumentSidebar' => 'false',
		);

		return $tabs;
	}

	/**
	 * Seed metaBoxesMainIsOpen=true (only if undefined) on the configured block-editor
	 * screens, so the WP 7.0 Meta Boxes pane is open by default. Never overrides a user
	 * who has deliberately collapsed it.
	 *
	 * @param string $hook Current admin page hook.
	 *
	 * @return void
	 */
	public function force_open_pane( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! isset( $this->config[ $screen->post_type ] ) ) {
			return;
		}

		// Classic editor has no pane to open.
		if ( method_exists( $screen, 'is_block_editor' ) && ! $screen->is_block_editor() ) {
			return;
		}

		// Attach to wp-edit-post: guarantees wp.data, wp.domReady and the preferences
		// store are all present. Silently no-ops if the handle isn't registered.
		wp_add_inline_script(
			'wp-edit-post',
			"wp.domReady(function(){"
			. "var p=wp.data.select('core/preferences');"
			. "if(p&&undefined===p.get('core/edit-post','metaBoxesMainIsOpen')){"
			. "wp.data.dispatch('core/preferences').set('core/edit-post','metaBoxesMainIsOpen',true);"
			. "}});"
		);
	}
}
