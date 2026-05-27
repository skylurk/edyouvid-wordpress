<?php
if(!defined('LINK_PREVIEW')) return;


$linkpreview_settings = linkpreview_get_settings();
if ($linkpreview_settings["linkpreview_tooltip_enable"] !== 'on') return;

$js = $linkpreview_settings["linkpreview_javascript"];
add_action('wp_head','linkpreview_js_'.$js);

/*
 * Search for links in post content
 */
function scan_linkpreview($content){
	if (!isset($content) || $content == '') return;
	$linkpreview_settings = linkpreview_get_settings();
	$linkPreview = new linkPreview();
	$content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
	$document = new DOMDocument();
	libxml_use_internal_errors(true);
	$document->loadHTML(utf8_decode($content));
	$links = $document->getElementsByTagName('a');
	$i = 1;
	foreach ($links as $link) {
		$link_href = $link->getAttribute('href');
		if (strlen($link_href) > $linkpreview_settings['linkpreview_max_url_size']) continue;
		if ($linkPreview->is_file($link_href)) continue;
		$class = $link->getAttribute('class');
		if ($linkPreview->is_class($class)) continue;
		$link->setAttribute('data-link', "$link_href");
		$link->removeAttribute('title');
		$link->setAttribute('data-button', "LinkPreview");
		$link->setAttribute('id', $i);
		$i++;
	}
	$html = $document->saveHTML();
	$html = html_entity_decode($html);
	return $html;
}
add_filter('the_content', 'scan_linkpreview');

/*
 * Tooltip content
 */
function linkpreview_callback() {
	$linkPreview = new linkPreview();
	$link = $_POST['link'];
	$data = $linkPreview->get_url_data($link);
	if ($data == false) $data->description = __('broken url','linkpreview');
	else include(LINKPREVIEW_TOOLTIP);
	wp_die();
}
add_action( 'wp_ajax_linkpreview', 'linkpreview_callback' );
add_action( 'wp_ajax_nopriv_linkpreview', 'linkpreview_callback' );

/*
 * jQuery UI Tooltip
 */
function linkpreview_js_jquery_ui_tooltip() {
	wp_enqueue_style('jquery-ui-tooltip', LINKPREVIEW_URL.'/css/jquery-ui.min.css' );
	wp_enqueue_style('jquery-ui-tooltip-theme', LINKPREVIEW_URL.'/css/jquery-ui.theme.min.css' );
	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-tooltip");
	?>
	<script type="text/javascript" >
	jQuery(function($) {
		jQuery( document ).tooltip({
			items: '[data-button="LinkPreview"]',
			tooltipClass : "lp-tooltip",
			content: function(callback) {
					var link_href=$(this).data('link')
					var data = {
							action 	: 'linkpreview',
							link	: link_href
							}
					jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>',data, function(response) {
					callback(response);
				});
			}
		})
	});
	</script> 
	<?php
}

/*
 * Tooltipster
 */
function linkpreview_js_tooltipster() {
	$linkpreview_settings = linkpreview_get_settings();
	$theme = $linkpreview_settings['linkpreview_tooltipster_theme'];
	wp_enqueue_script('jquery');
	wp_enqueue_style('tooltipster', LINKPREVIEW_URL.'/js/tooltipster/css/tooltipster.css' );
	if ($theme != 'default') wp_enqueue_style('tooltipster-'.$theme, LINKPREVIEW_URL.'/js/tooltipster/css/themes/tooltipster-'.$theme.'.css' );
	wp_enqueue_script("tooltipster", LINKPREVIEW_URL."/js/tooltipster/js/jquery.tooltipster.min.js", false, "v3.3.0");
	?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		jQuery('[data-button="LinkPreview"]').tooltipster({
			contentAsHTML: true,
			content: '<img src="<?php echo LINKPREVIEW_URL.'/images/loading.gif'?>" class="lp-loading">',
			theme: 'tooltipster-<?php echo $theme;?>',
			position: 'bottom',
			maxWidth: 400,
			functionBefore: function(origin, continueTooltip) {
				continueTooltip();
				var link_href=$(this).data('link')
				if (origin.data('ajax') !== 'cached') {
					jQuery.ajax({
						type: 'POST',
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						data: {
							action 	: 'linkpreview',
							link	: link_href
						},
						success: function(data) {
							origin.tooltipster('content', data).data('ajax', 'cached');
						}
					});
				}
			}
		});
	});
	</script> 
	<?php
}
