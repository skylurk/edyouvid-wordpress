<?php
if(!defined('LINK_PREVIEW')) return;

/**
 * Set defaults for the first run if needed
 */
if (!get_option('linkpreview_firsttime_setup') && get_option('linkpreview_firsttime_setup') !== '1') {
	linkpreview_reset_settings();
	add_option("linkpreview_firsttime_setup", '1');
}

/**
 * Reset settings
 */
function linkpreview_reset_settings() {
	delete_option('linkpreview_settings');
	add_option('linkpreview_settings');
	$default_options = array(
		'linkpreview_local_curl'	=>	'off',
		'linkpreview_cache_time'	=>	'60',
		'linkpreview_api_key'		=>	'f150278',
		'linkpreview_filter_out'	=>	'png,jpg,jpeg,gif,tiff,tif,pdf,zip,rar,7z,txt,doc,docx',
		'linkpreview_max_url_size'	=> 	'128',
		'linkpreview_exclude_class'	=>	'btn,button,img,image',
		'linkpreview_tooltip_enable'=>	'on',
		'linkpreview_favicon'		=>	'on',
		'linkpreview_javascript'	=>	'tooltipster',
		'linkpreview_tooltipster_theme'	=>	'shadow',
		'linkpreview_jquery_ui_theme'	=>	'default'
	);
	$settings = serialize($default_options);
	update_option('linkpreview_settings', $settings);
}

/**
 * Read existing settings
 */
function linkpreview_get_settings() {
	return unserialize(get_option('linkpreview_settings'));
}

/**
 * Add Link Preview settings to dashboard
 */
add_action('admin_menu', 'linkpreview_menu');
function linkpreview_menu() {
	add_submenu_page('options-general.php', 'Link Preview settings', 'Link Preview', 'manage_options', 'link-preview', 'linkpreview_admin_settings' );
}

function linkpreview_admin_settings() {
	if (isset($_POST["update_settings"])) {
		$_POST['lp_postdata']['linkpreview_tooltip_enable'] = (isset($_POST['lp_postdata']['linkpreview_tooltip_enable']) && $_POST['lp_postdata']['linkpreview_tooltip_enable'] == 'on') ? 'on' : 'off';
		$_POST['lp_postdata']['linkpreview_local_curl'] = (isset($_POST['lp_postdata']['linkpreview_local_curl']) && $_POST['lp_postdata']['linkpreview_local_curl'] == 'on') ? 'on' : 'off';
		$_POST['lp_postdata']['linkpreview_favicon'] = (isset($_POST['lp_postdata']['linkpreview_favicon']) && $_POST['lp_postdata']['linkpreview_favicon'] == 'on') ? 'on' : 'off';
		$new_options=array();
		foreach ($_POST['lp_postdata'] as $key=>$val) {
			$new_options[$key] = $val;
		}
		update_option('linkpreview_settings', serialize($new_options));
		echo '<div id="message" class="updated"><h4>Link Preview - settings saved</h4></div>';
	}
	if (isset($_POST["reset_settings"])) {
		linkpreview_reset_settings();
		echo '<div id="message" class="notice"><h4>Link Preview - settings reset</h4></div>';
	}
	$linkpreview_settings = linkpreview_get_settings();
	extract($linkpreview_settings);
	include(LINKPREVIEW_ADMIN);
}

/**
 * Shortcode definition
 */
if (!shortcode_exists('link_preview')) add_shortcode( 'link_preview', 'linkpreview_shortcode' );
function linkpreview_shortcode( $atts, $content = null ) {
	if ($atts) extract($atts);
	$linkPreview = new linkPreview();
	$result = $linkPreview->showtime($content);
	return $result;
}

/**
 * Add admin CSS
 */
add_action ('admin_init', 'linkpreview_style_admin');
function linkpreview_style_admin() {
	wp_enqueue_style("linkpreview-admin", LINKPREVIEW_URL."/css/admin.css", false, LINKPREVIEW_VERSION, "all");
}