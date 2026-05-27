=== Phee's LinkPreview ===
Contributors: filipstepanov
Donate link: 
Tags: url,link,preview,link preview,linkpreview,tooltip
Requires at least: 3.3
Tested up to: 4.9.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Preview basic SEO link info before clicking it


== Description ==

This is simple and lightweight plugin for WP. It works similar to Facebook's url preview and it's very easy to use.

Plugin uses free LinkPreview API web-service at http://www.linkpreview.net and retrieves basic link SEO data.

Additionally, in dashboard plugin settings you can avoid using API and scrap the link information directly from local web server using CURL or file_get_contents.

All the link info retrieved can be stored in cache to avoid possible performance impact on high traffic websites. Aside other plugin's settings in dashboard, there is Cache time value where you can specify in minutes how long to keep URL's information for future use.

Both LTR and RTL content environments are supported.

Two different plugin features are at your disposal, tooltips and shortcode:

**Tooltip**

Tooltip feature hooks on content load and scans for possible link matches. Each link matched is being modified with additional attributes, so when you hover them, popup is shown.

It's using plugin's JavaScript and WordPress Ajax to deliver content on demand.

**Shortcode**

When used through shortcodes it works the same way, just no hover needed but static content like Facebook's URL preview will be shown.

Shortcode usage example:

[link_preview]http://...[/link_preview]


== Installation ==

1. Upload linkpreview plugin to you WordPress blog (in the `/wp-content/plugins/` directory)
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use it as a tooltip feature, or through shortcodes
4. Rate it if you like it :)


== Frequently asked questions ==
**Is this plugin using any external javascript?**

- You can choose between included WP jquery-ui Tooltip and Tooltipster JS that comes with a plugin

**Does it support RTL**

- yes


== Screenshots ==
1. Shortcode screenshot
2. Tooltips screenshot

== Changelog ==

= 1.6.3a =
* RTL support
* using default url length (128) if not set or 0

= 1.6.4 =
* CURL is no more mandatory to be installed/enabled

= 1.6.5 =
* Fix for converting HTML entities

= 1.6.5a =
* minor change

= 1.6.7 =
* Proper handling of error response (do not cache)

== Upgrade notice ==
Removed CURL to be mandatory for scrapping information
