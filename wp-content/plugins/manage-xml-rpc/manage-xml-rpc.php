<?php
/**
 * Plugin Name: Manage XML-RPC
 * Plugin URI: http://www.brainvire.com
 * Description: Disable XML-RPC for IP-specific control and disable XML-RPC Pingback method.
 * Version: 1.0.2
 * Author: brainvireinfo
 * Author URI: http://www.brainvire.com
 * License: GPL2
 * Text Domain: manage-xml-rpc
 *
 * @package manage-xml-rpc
 */

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define the plugin path.
define( 'MXR_PLUGIN_PATH', plugins_url( __FILE__ ) );

// Check if .htaccess file exists and handle plugin activation accordingly.
register_activation_hook( __FILE__, 'mxr_check_htaccess_on_activation' );

/**
 * Checks if the .htaccess file exists upon plugin activation.
 *
 * This function is hooked to the plugin activation process. When the plugin is activated, it checks for the presence
 * of the .htaccess file in the WordPress root directory. If the file does not exist, the plugin is deactivated to
 * prevent potential issues, and an admin notice is added to inform the user of the missing .htaccess file.
 *
 * @return void
 */
function mxr_check_htaccess_on_activation() {
	// Define the path to the .htaccess file in the WordPress root directory.
	$htaccess_file = ABSPATH . '.htaccess';

	// Check if the .htaccess file does not exist.
	if ( ! file_exists( $htaccess_file ) ) {
		// Create a basic .htaccess file.
		mxr_create_basic_htaccess();

		// Check again if the file was created successfully.
		if ( ! file_exists( $htaccess_file ) ) {
			// Deactivate the plugin if the .htaccess file is still missing.
			deactivate_plugins( plugin_basename( __FILE__ ) );

			// Add an admin notice to inform the user about the missing .htaccess file.
			add_action( 'admin_notices', 'mxr_display_htaccess_missing_notice' );
		}
	}
}

// Check if .htaccess file exists.
add_action( 'admin_init', 'mxr_check_htaccess_file' );

/**
 * Checks if the .htaccess file exists and deactivates the plugin if not.
 *
 * This function is hooked to the `admin_init` action to check if the .htaccess
 * file is missing. If it is missing, the plugin is deactivated and an admin notice
 * is displayed.
 *
 * @return void
 */
function mxr_check_htaccess_file() {
	if ( ! file_exists( ABSPATH . '.htaccess' ) && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
		// Create a basic .htaccess file.
		mxr_create_basic_htaccess();

		// Check again if the file was created successfully.
		if ( ! file_exists( ABSPATH . '.htaccess' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', 'mxr_display_htaccess_missing_notice' );
		}
	}
}

/**
 * Creates a basic .htaccess file with WordPress rules if it does not exist.
 *
 * @return void
 */
function mxr_create_basic_htaccess() {
	$htaccess_content = "## BEGIN WordPress\n";
	$htaccess_content .= "<IfModule mod_rewrite.c>\n";
	$htaccess_content .= "RewriteEngine On\n";
	$htaccess_content .= "RewriteBase /\n";
	$htaccess_content .= "RewriteRule ^index\\.php$ - [L]\n";
	$htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
	$htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
	$htaccess_content .= "RewriteRule . /index.php [L]\n";
	$htaccess_content .= "</IfModule>\n";
	$htaccess_content .= "## END WordPress\n";

	// Create the .htaccess file with the basic content.
	file_put_contents( ABSPATH . '.htaccess', $htaccess_content );
}

/**
 * Displays an admin notice if the .htaccess file is missing.
 *
 * This function is hooked to the `admin_notices` action to display a notice
 * if the .htaccess file is missing.
 *
 * @return void
 */
function mxr_display_htaccess_missing_notice() {
	?>
	<div class="notice notice-error">
	<p><?php echo 'The .htaccess file does not exist. A basic .htaccess file has been created. Please review it and ensure it includes any necessary rules for your site.'; ?></p>
	</div>
	<?php
}

// Create custom plugin settings menu.
add_action( 'admin_menu', 'mxr_create_menu' );

/**
 * Creates the settings menu in the WordPress admin dashboard.
 */
function mxr_create_menu() {
	// Create new top-level menu.
	add_menu_page(
		'XML-RPC Settings',
		'XML-RPC Settings',
		'manage_options',
		'manage_xml_rpc_page',
		'mxr_page_function',
		'dashicons-shield'
	);

	// Call register settings function.
	add_action( 'admin_init', 'mxr_register_settings' );

	// Add settings link to plugin listing.
	add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mxr_add_settings_link' );

	/**
	 * Add settings link.
	 *
	 * @param string $links setting links.
	 */
	function mxr_add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=manage_xml_rpc_page">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	add_action( 'init', 'mxr_disable_ping_onpage_load' );
}

/**
 * Registers the settings for the plugin.
 *
 * @return void
 */
function mxr_register_settings() {
	// Check if .htaccess exists.
	$htaccess_exists = file_exists( ABSPATH . '.htaccess' );

	register_setting(
		'manage-xml-rpc-settings-group',
		'allow_disallow',
		array(
			'type'              => 'string',
			'default'           => 'allow',
			'sanitize_callback' => 'sanitize_key',
		)
	);

	register_setting(
		'manage-xml-rpc-settings-group',
		'allow_disallow_pingback',
		array(
			'type'              => 'string',
			'default'           => 'allow',
			'sanitize_callback' => 'sanitize_key',
		)
	);

	if ( ! $htaccess_exists ) {
		// If .htaccess doesn't exist, disable settings.
		unregister_setting( 'manage-xml-rpc-settings-group', 'allow_disallow' );
		unregister_setting( 'manage-xml-rpc-settings-group', 'allow_disallow_pingback' );
	}
}

/**
 * Flushes the rewrite rules to update .htaccess.
 *
 * @return void
 */
function mxr_flush_rewrites() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
add_action( 'admin_init', 'mxr_flush_rewrites' );

/**
 * Renders the plugin settings page.
 *
 * @return void
 */
function mxr_page_function() {
	$current_user = wp_get_current_user();
	?>
	<div class="wrap">
		<h2>XML-RPC Settings</h2>

		<form method="post" action="options.php">
			<?php settings_fields( 'manage-xml-rpc-settings-group' ); ?>
			<?php do_settings_sections( 'manage-xml-rpc-settings-group' ); ?>
			<?php
				$home_path = get_home_path();
				global $wp_rewrite;
			?>
			<table class="form-table">
						
				<tr valign="top">
					<th scope="row">Disable XML-RPC Pingback: </th>
					<td>
						<input type="checkbox" name="allow_disallow_pingback" value="disallow" <?php echo esc_attr( get_option( 'allow_disallow_pingback' ) ) == 'disallow' ? 'checked' : ''; ?> />
						<span class="description">(recommended) Check this if you want to remove pingback.ping and pingback.extensions.getPingbacks and X-Pingback from HTTP headers.</span>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Disable XML-RPC: </th>
					<td>
						<input type="checkbox" name="allow_disallow" value="disallow" <?php echo esc_attr( get_option( 'allow_disallow' ) ) == 'disallow' ? 'checked' : ''; ?> />
						<span class="description">Only check this if you want to block/disable all XML-RPC requests.</span>
					</td>
				</tr>

			</table>
			
			<?php submit_button(); ?>

			<?php
				// Initialize $rules variable.
				$rules = '';

				add_filter( 'mod_rewrite_rules', 'mxr_htaccess_contents' );
				$existing_rules  = file_get_contents( $home_path . '.htaccess' );

				$new_rules       = mxr_extract_from_array( explode( "\n", $wp_rewrite->mod_rewrite_rules() ), 'Protect XML-RPC' );

				$start = '\# BEGIN Protect XML-RPC';
				$end   = '\# END Protect XML-RPC';
				$htaccess_content = preg_replace( '#(' . $start . ')(.*)(' . $end . ')#si', '$1 ' . $new_rules . ' $3', $existing_rules );

				$update_required = ( $new_rules !== $existing_rules );
				$writable = false;

			if ( ( ! file_exists( $home_path . '.htaccess' ) && is_writable( $home_path ) ) || is_writable( $home_path . '.htaccess' ) ) {
				$writable = true;
			}

			if ( ! $writable && $update_required && 'special_user' === $current_user->user_login ) {
				?>
						<p><?php echo 'Custom message for special user: If your <code>.htaccess</code> file were <a href="https://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all and paste code in .htaccess file.'; ?></p>
						<p><textarea rows="6" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php echo esc_html( $htaccess_content . "\n" . $rules . "\n" . $new_rules . "\n" ); ?></textarea></p>
					<?php
			} elseif ( ! $writable && $update_required ) {
				?>
						<p><?php echo 'If your <code>.htaccess</code> file were <a href="https://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all and paste code in .htaccess file.'; ?></p>
						<p><textarea rows="6" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php echo esc_html( $htaccess_content . "\n" . $rules . "\n" . $new_rules . "\n" ); ?></textarea></p>
					<?php
			}
			?>

		</form>
	</div>
	<?php
}

/**
 * Generates .htaccess rules to disable XML-RPC.
 *
 * @param string $rules Existing .htaccess rules.
 * @return string Updated .htaccess rules.
 */
function mxr_htaccess_contents( $rules ) {
	global $wp_rewrite;
	$home_path = get_home_path();
	$wp_rewrite->flush_rules( false );

	if ( esc_attr( get_option( 'allow_disallow' ) ) == 'disallow' ) {
		$new_rules  = '# BEGIN Protect XML-RPC' . "\n";
		$new_rules .= '<Files "xmlrpc.php">' . "\n";
		$new_rules .= 'Order Deny,Allow' . "\n";
		$new_rules .= 'Deny from all' . "\n";
		$new_rules .= '</Files>' . "\n";
		$new_rules .= '# END Protect XML-RPC' . "\n";
	} else {
		$new_rules = '';
	}

	// Ensure new rules are added below # END WordPress.
	$end_wordpress_marker = '# END WordPress';
	if ( strpos( $rules, $end_wordpress_marker ) !== false ) {
		$rules = str_replace( $end_wordpress_marker, $end_wordpress_marker . "\n" . $new_rules, $rules );
	} else {
		$rules .= "\n" . $new_rules;
	}

	return $rules;
}

add_filter( 'mod_rewrite_rules', 'mxr_htaccess_contents' );

 /**
 * Fallback for disabling the xmlrpc if .htaccess not working
 * 
 * @param string $class The name of the XML-RPC server class.
 * @return string The same class name if conditions doesn't match.
 */
function mxr_disable_wp_xmlrpc($class)
{
	if ( esc_attr( get_option( 'allow_disallow' ) ) == 'disallow' ) {
		http_response_code(403);
		exit("Access Forbidden! You don't have permission to access this file.");
	}
	return $class;
}
add_filter('wp_xmlrpc_server_class', 'mxr_disable_wp_xmlrpc');

/**
 * Extracts a specific section from an array of .htaccess rules.
 *
 * @param array  $input_array Array of .htaccess rules.
 * @param string $marker       Section marker to extract.
 * @return string Extracted rules.
 */
function mxr_extract_from_array( $input_array, $marker ) {
	$result = '' . "\n";

	if ( empty( $input_array ) ) {
		return $result;
	}

	if ( ! empty( $input_array ) ) {
		$state = false;
		foreach ( $input_array as $marker_line ) {
			if ( strpos( $marker_line, '# END ' . $marker ) !== false ) {
				$state = false;
			}
			if ( $state ) {
				$result .= $marker_line . "\n";
			}
			if ( strpos( $marker_line, '# BEGIN ' . $marker ) !== false ) {
				$state = true;
			}
		}
	}
	return $result;
}

/**
 * Disables XML-RPC Pingback methods.
 *
 * @param array $methods List of XML-RPC methods.
 * @return array Filtered list of methods.
 */
function mxr_disable_xmlrpc_pingback( $methods ) {
	unset( $methods['pingback.ping'] );
	unset( $methods['pingback.extensions.getPingbacks'] );
	return $methods;
}

/**
 * Removes the X-Pingback HTTP header.
 *
 * @param array $headers List of HTTP headers.
 * @return array Filtered list of headers.
 */
function mxr_remove_x_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}

/**
 * Disables Pingback methods on page load based on settings.
 *
 * @return void
 */
function mxr_disable_ping_onpage_load() {
	if ( esc_attr( get_option( 'allow_disallow_pingback' ) ) == 'disallow' ) {
		add_filter( 'xmlrpc_methods', 'mxr_disable_xmlrpc_pingback' );
		add_filter( 'wp_headers', 'mxr_remove_x_pingback_header' );
	}
}

add_action( 'init', 'mxr_disable_ping_onpage_load' );
?>
