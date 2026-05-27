<?php

$tincan_opt_per_pages = get_user_meta( get_current_user_id(), 'ucTinCan_per_page', true );
$tincan_opt_per_pages = '' !== $tincan_opt_per_pages ? $tincan_opt_per_pages : 25;

if ( ! is_admin() ) {
	// @todo REVIEW this setup
	// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
	global $hook_suffix;
	$hook_suffix = '';
	if ( isset( $page_hook ) ) {
		$hook_suffix = $page_hook;
	} elseif ( isset( $plugin_page ) ) {
		$hook_suffix = $plugin_page;
	} elseif ( isset( $pagenow ) ) {
		$hook_suffix = $pagenow;
	}
}
