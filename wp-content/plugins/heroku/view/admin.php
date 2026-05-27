<?php
if(!defined('LINK_PREVIEW')) return;
?>
<div class="wrap">
	<h2>Link Preview settings</h2>
	<div class="lp-credits">
		<h3>Link Preview <?php echo LINKPREVIEW_VERSION;?></h3>
		<div class="inside">
			<div>
				<h4>Need support?</h4>
				<p>If you need support or have any problems with this plugin, feel free to contact me on <a href="http://www.filipstepanov.com/link-preview-project/wordpress-plugin/" target="_blank">website</a></p>
			</div>
			<hr/>
			<div>
				<h4>Link preview network</h4>
				<p>For more information about Link Preview web service please go to this <a href="http://www.linkpreview.net" target="_blank">link</a></p>
			</div>
			<div>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="FBS5CXP6BJ4PY">
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
			</div>
		</div>
	</div>
	<form class="linkpreview-form" method="POST" action="">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_local_curl]">Obtain remote data locally (without API):</label>
				</th>
				<td>
					<input type="checkbox" name="lp_postdata[linkpreview_local_curl]" <?php if ($linkpreview_local_curl == "on") echo 'checked'; ?>>
					<span class="description">It is recommended to have CURL installed and enabled on local server</span><br/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_api_key]">API key:</label>
				</th>
				<td>
					<input type="text" name="lp_postdata[linkpreview_api_key]" value="<?php echo $linkpreview_api_key; ?>"/>
					<a class="button button-default" href="http://www.linkpreview.net/request" target="_blank">Request a key</a>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_cache_time]">Cache time (minutes):</label>
				</th>
				<td>
					<input step="1" min="0" type="number" name="lp_postdata[linkpreview_cache_time]" value="<?php echo $linkpreview_cache_time; ?>"/>
					<span class="description">Enter 0 to disable caching (not recommended)</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_max_url_size]">Maximum URL size allowed:</label>
				</th>
				<td>
					<input step="1" min="0" type="number" name="lp_postdata[linkpreview_max_url_size]" value="<?php echo $linkpreview_max_url_size; ?>"/>
					<span class="description">URL's larger than this will not be used</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_favicon]">Show favicon if available:</label>
				</th>
				<td>
					<input type="checkbox" name="lp_postdata[linkpreview_favicon]" <?php if ($linkpreview_favicon == "on") echo 'checked'; ?>>
					<span class="description">If the icon does not exist default will be used</span><br/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_tooltip_enable]">Enable tooltips:</label>
				</th>
				<td>
					<input type="checkbox" name="lp_postdata[linkpreview_tooltip_enable]" <?php if ($linkpreview_tooltip_enable == "on") echo 'checked'; ?>>
					<span class="description">Options below will be affected by this selection</span><br/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_filter_out]">Exclude url's that ends with:</label>
				</th>
				<td>
					<input size="50" type="text" name="lp_postdata[linkpreview_filter_out]" value="<?php echo $linkpreview_filter_out; ?>"/>
					<span class="description">Use commas between extensions</span><br/>
					<code><b>default: </b>png,jpg,jpeg,gif,tiff,tif,pdf,zip,rar,7z,txt,doc,docx</code>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_exclude_class]">Exclude url's with class:</label>
				</th>
				<td>
					<input size="50" type="text" name="lp_postdata[linkpreview_exclude_class]" value="<?php echo $linkpreview_exclude_class; ?>"/>
					<span class="description">Use commas between extensions</span><br/>
					<code><b>default: </b>btn,button,image</code>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_javascript]">Tooltip JS:</label>
				</th>
				<td>
					<select id="lp_postdata[linkpreview_javascript]" name="lp_postdata[linkpreview_javascript]">
						<option <?php if ($linkpreview_javascript == 'tooltipster') echo 'selected="selected"'; ?> value="tooltipster">Tooltipster</option>
						<option <?php if ($linkpreview_javascript == 'jquery_ui_tooltip') echo 'selected="selected"'; ?> value="jquery_ui_tooltip">jQuery UI Tooltip</option>
					</select>
					<span class="description">All tooltips require jQuery JavaScript library to run</span>
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							var selectedTooltip = $( "select[id='lp_postdata[linkpreview_javascript]']" ).val();
							$('.'+selectedTooltip+'_theme').show();
							$( "select[id='lp_postdata[linkpreview_javascript]']" ).change(function() {
								if ($( this ).val() == 'tooltipster') {
									$('.tooltipster_theme').show();
									$('.jquery_ui_tooltip_theme').hide();
								}
								if ($( this ).val() == 'jquery_ui_tooltip') {
									$('.tooltipster_theme').hide();
									$('.jquery_ui_tooltip_theme').show();
								}
							});
						});
					</script>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="lp_postdata[linkpreview_tooltipster_theme]">Tooltip theme:</label>
				</th>
				<td>
					<div class="jquery_ui_tooltip_theme" style="display:none;">
						<select id="lp_postdata[linkpreview_jquery_ui_theme]" name="lp_postdata[linkpreview_jquery_ui_theme]">
							<option <?php if ($linkpreview_jquery_ui_theme == 'default') echo 'selected'; ?> value="default">Default</option>
						</select>
						<span class="description">jQuery UI Tooltip theme</span>
					</div>
					<div class="tooltipster_theme" style="display:none;">
						<select id="lp_postdata[linkpreview_tooltipster_theme]" name="lp_postdata[linkpreview_tooltipster_theme]">
							<option <?php if ($linkpreview_tooltipster_theme == 'default') echo 'selected'; ?> value="default">Default</option>
							<option <?php if ($linkpreview_tooltipster_theme == 'light') echo 'selected'; ?> value="light">Light</option>
							<option <?php if ($linkpreview_tooltipster_theme == 'shadow') echo 'selected'; ?> value="shadow">Shadow</option>
							<option <?php if ($linkpreview_tooltipster_theme == 'noir') echo 'selected'; ?> value="noir">Noir</option>
							<option <?php if ($linkpreview_tooltipster_theme == 'punk') echo 'selected'; ?> value="punk">Punk</option>
						</select>
						<span class="description">Tooltipster theme</span>
					</div>
				</td>
			</tr>
		</table>
		<hr/>
		<p>
			<b>Shortcode</b><br/>
			You can also use shortcode to display static element in content: <br/>
			<pre>[link_preview]http://www.google.com[/link_preview]</pre>
		</p>
		<p class="submit">
			<input id="update_settings" name="update_settings" id="submit" class="button button-primary" type="submit" value="Save Changes" name="submit">
			<input id="reset_settings" name="reset_settings" class="button button-default" type="submit" value="Reset to defaults" name="submit">
		</p>
	</form>
</div>