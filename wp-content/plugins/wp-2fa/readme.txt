=== WP 2FA - Two-factor authentication for WordPress ===
Contributors: Melapress, robert681
Plugin URI: https://melapress.com/wordpress-2fa/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html
Tags: 2FA, two-factor authentication, 2-factor authentication, WordPress authentication, Google Authenticator
Requires at least: 5.5
Tested up to: 7.0
Stable tag: 4.0
Requires PHP: 7.4.0

Get better WordPress login security; add two-factor authentication (2FA) for all your users with this easy-to-use plugin.

== Description ==

### A free and easy-to-use two-factor authentication plugin for WordPress

Add an extra layer of security to your WordPress website login and protect your users. Enable two-factor authentication (2FA), the best protection against password leaks, automated password guessing, and brute force attacks.

Use the WP 2FA plugin to enable two-factor authentication for your WordPress administrator, enforce 2FA for all your website users, or for users with specific roles. This plugin is very easy to use; everything can be configured via wizards with clear instructions, so even non-technical users can set up 2FA without requiring technical assistance.

### 🔒 WP 2FA key plugin features and capabilities
- **Passkeys support** for passwordless logins   
- **Free two-factor authentication (2FA)** for all users  
- **Multiple 2FA methods** supported, including authenticator app (TOTP) and code over email  
- **Developer API** to integrate any alternative 2FA method (WhatsApp, OTP Token, etc.)  
- **Universal 2FA app support** – works with Google Authenticator, Authy, and any TOTP-compatible app  
- **Backup codes** (16 digits) for recovery access  
- **Wizard-driven setup** – no technical knowledge required  
- **2FA policies** to enforce setup with grace periods or instant activation  
- **REST API endpoints** for custom integrations and headless WordPress setups  
- **Dashboard-free setup** – users can configure 2FA without WP admin access  
- **Editable email templates** for full customization  
- **Much more!**

[youtube https://www.youtube.com/watch?v=EbqiphCcwWs]

[Features](https://melapress.com/wordpress-2fa/features/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa) | [Getting Started](https://melapress.com/support/kb/wp-2fa-plugin-getting-started/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa) | [Get the Premium!](https://melapress.com/wordpress-2fa/pricing/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa)
 
### 💎 Upgrade to WP 2FA Premium and get even more benefits

The premium version of WP 2FA comes bundled with even more features to take your WordPress website login security to the next level.

With the premium edition of WP 2FA, you get more 2FA methods, 1-click integration with WooCommerce, trusted devices feature, extensive white labeling capabilities, and much more!

[Check out WP 2FA Premium!](https://melapress.com/wordpress-2fa/pricing/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa)

### Premium features list

- **Everything in the free version**
- **Full white labeling capabilities** to change all text and visuals in the wizards, emails, SMS, and 2FA pages
- **Support for multiple passkeys per user** for flexible passwordless logins
- **Zero-setup email 2FA** that automatically enrolls users without manual configuration
- **YubiKey hardware key support** for enterprise-grade security
- **Additional 2FA methods** such as SMS, email link, and more
- **Trusted devices** so users can log in without 2FA for a configured period
- **Require 2FA on password reset** to strengthen account protection
- **Allow next user login without 2FA** to help recover accounts locked out of authentication
- **One-click WooCommerce integration** to enable 2FA for customers and store admins
- **And much more!**

Refer to the [WP 2FA plugin features and benefits page](https://melapress.com/wordpress-2fa/features/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa) to learn more about the benefits of upgrading to WP 2FA Premium.

## 🛠️ Free and premium support

Support for the free edition of WP 2FA is free on the [WordPress support forums](https://wordpress.org/support/plugin/wp-2fa/). Premium world-class support via one-to-one email is available to the Premium users - [upgrade to premium](https://melapress.com/wordpress-2fa/pricing/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa) to benefit from email support.

For any other queries, feedback, or if you simply want to get in touch with us, please use our [contact form](https://melapress.com/contact/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa).

#### MAINTAINED & SUPPORTED BY MELAPRESS

Melapress develops high-quality WordPress management and security plugins, such as Melapress Login Security, Melapress Role Editor, and WP Activity Log; the #1 user-rated activity log plugin for WordPress.

Browse our list of [WordPress security and administration plugins](https://melapress.com/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa) to see how our plugins can help you better manage and improve the security and administration of your WordPress websites and users.
    
== Installing WP 2FA ==

###From within WordPress

1.  Navigate to ‘Plugins' > 'Add New’
2.  Search for ‘WP 2FA’
3.  Install & activate WP 2FA from your Plugins page
  
###Manually

1.  Download the plugin from the WordPress plugins repository
2.  Unzip the zip file and upload the folder to the '/wp-content/plugins/ directory'
3.  Activate the WP 2FA plugin through the ‘Plugins’ menu in WordPress

## As featured on:

- [WP Beginner](https://www.wpbeginner.com/plugins/how-to-add-two-factor-authentication-for-wordpress/)
- [IsitWP](https://www.isitwp.com/best-wordpress-security-authentication-plugins/)
- [WP Astra](https://wpastra.com/two-factor-authentication-wordpress/)
- [MainWP](https://mainwp.com/how-to-use-the-wp-2fa-plugin-on-your-child-sites/)
- [FixRunner](https://www.fixrunner.com/wordpress-two-factor-authentication/)
- [Inmotion Hosting](https://www.inmotionhosting.com/support/edu/wordpress/plugins/wp-2fa/)
- [WP Marmite](https://wpmarmite.com/en/wordpress-two-factor-authentication/)

== Frequently Asked Questions ==

= Does the plugin send any data to Melapress? =
No, the plugin does not send any data to us whatsoever. The only data we receive is license data from the premium edition of the plugin.

= What 2FA methods are available with the plugin? =
The free edition of WP 2FA includes the following 2FA methods: Authenticator app 2FA and code over email. This allows you to use Google Authenticator OTP The premium edition adds YubiKey, one-click email link, SMS 2FA, and Authy push notifications. 

= How can I integrate two-factor authentication (2FA) into my custom login process or AJAX-based form? =
WP 2FA includes a REST API that allows developers to enable and verify 2FA during custom authentication flows, such as AJAX-based login forms, mobile apps, or headless WordPress websites. Refer to the [REST API in WP 2FA documentation](https://melapress.com/support/kb/wp-2fa-rest-api/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa) for more information.

= How can I ensure I do not get locked out? =
WP 2FA includes backup authentication methods so that if the primary authentication method fails, you and your users can still log in. The free version of the plugin includes backup codes, which can be configured during 2FA configuration or at any point after that from the profile page. The premium edition adds 2FA backup codes over email.

= What happens if I get locked out? =
In the unlikely event that you are unable to supply your 2FA code, there are several steps you can take to gain access to your WordPress dashboard. First, check if there is another administrator who can reset your 2FA. If this is not possible, manually deactivate the plugin, log in without 2FA, re-activate the plugin, and then reconfigure your 2FA. 

=  Does WP 2FA support multi-site networks? = 
Yes, WP 2FA is multisite compatible. The plugin can be activated at the network level. 2FA policies can be enforced on all users, a subsection of users, or per site on the network. It also supports network setups with different domains.

= Does the plugin receive updates? =
We update the plugin fairly regularly to ensure the plugin continues to run in tip-top shape while adding new features from time to time.

= Does the plugin support Google Authenticator? =
Yes, WP 2FA fully supports Google Authenticator on WordPress. [WP 2FA also supports many other 2FA authenticator apps](https://melapress.com/support/kb/wp-2fa-configuring-2fa-apps/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa).

= Can I get support if I get stuck? =
Support for the free edition of the plugin is provided only via the WordPress.org support forums. You can also refer to our [support pages](https://melapress.com/support/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=wp2fa) for all the technical and product documentation.

If you are using the Premium edition, you get direct access to our support team via one-to-one [email support](https://melapress.com/support/submit-ticket/?utm_source=wp+repo&utm_medium=repo+link&utm_campaign=wordpress_org&utm_content=mls).

= How can I report security bugs? =
You can report security bugs through the Patchstack Vulnerability Disclosure Program. Please use this [form](https://patchstack.com/database/vdp/wp-2fa). For more details, please refer to our [Melapress plugins security program](https://melapress.com/plugins-security-program/).

== Screenshots ==

1. The first-time install wizard allows you to set up 2FA on your website and for your users within seconds.
2. The wizards make setting up 2FA very easy, so even non-technical users can set up 2FA without requiring help.
3. Setting up Passkeys is also a straightforward in WP 2FA. The users just have to follow the step by step instructions.
4. You can require users to enable 2FA and also give them a grace period to do so.
5. Users can also use one-time codes via email as a two-factor authentication method.
6. Users can configure and use Passkeys to log in to the website when using WP 2FA.
7. Users can easily manage their Passkeys from their user profile page.
8. You can use policies to require users to instantly set up and use 2FA, so the next time they log in, they will be prompted with this.
9. You can give users a grace period until they configure 2FA. You can also specify what the plugin should do once the grace period is over.
10. It is recommended for all users to also generate backup codes, in case they cannot access the primary device.
11. In the user profile, users only have a few 2FA options, so it is not confusing for them, and everything is self-explanatory.

== Changelog ==

= 4.0 (2027-07-07) =

* **New User Interface (UI)**

	* A completely redesigned plugin interface, offering improved navigation, a more streamlined setup experience, better organization of settings, and a modern design. 

**Important:** Switching to the new interface is a one-way process. New installations use the new interface by default, while upgrades retain the current interface and can switch at any time via a new **Switch to the new WP 2FA Interface** setting under General Settings.

 * **New Features & functionality**

	 * Added an **Export table as CSV** feature in the Reports, allowing administrators to export 2FA status data for analysis and reporting.

 * **Improvements**

	 * The "learn more about backup codes" URL shown on the user profile is now editable via white labeling, allowing brands to replace or remove the default link.
	 * Updated the bundled Select2 library to a more recent and secure version.
	 * Hardened the shortcode `return` parameter handling to use proper URL validation instead of `strip_tags()`, preventing potential URL manipulation.
	 * Updated the SSL check to use wp_die() instead of bare exit() calls for safer request termination.
	 * Refreshed the install wizard copy across all four slides for clearer, more welcoming language and consistent use of the term "secondary 2FA method" instead of "alternative".
	 * Improved the libxml missing extension notice (required for TOTP method) with a clearer, more actionable message directing users to their hosting provider.
	 * Moved the main **2FA code page text*	 * field to the top of the White Labeling → Customize 2FA code page tab,
	 * Shortened the default SMS templates on fresh installs to stay under 160 characters, improving deliverability with strict carriers while remaining Twilio-compliant.
	 * The verification code input on the 2FA login screen is now auto-focused, so users can start typing their code immediately without clicking into the field.
	 * The `{login_code}` placeholder now also works in email subject lines, not only in the email body.
	 * Improved feedback on the 3rd party integrations page: verification modals for Twilio, Clickatell, and all other providers are now consistently styled and show clear error messages when credentials are empty or invalid.
	 * Verified 3rd party integration credentials are now automatically saved on successful validation, so they persist after a page refresh.
	 * Declared WooCommerce compatibility with HPOS, Cart/Checkout Blocks, and the Product Block Editor, so WP 2FA no longer appears as "uncertain" in the WooCommerce compatibility dashboard.
	 * Removed `declare(strict_types=1)` from all files to improve compatibility with WordPress hooks and prevent intermittent TypeError fatals when filters or actions pass loosely-typed values.
	 * Removed a large block of commented-out legacy code from `wp-2fa.php` for cleaner code and easier maintenance.
	 * Fixed inconsistent text domain usage in the deactivation class (was `'textdomain'`, now correctly uses `'wp-2fa'`), ensuring all deactivation strings are translatable.
	 * Updated the deactivation feedback form to version 1.1.
	 * Refactored the plugin's licensing architecture to improve maintainability and support future enhancements.
	 * Removed the Quick Links feature from the plugin, along with its underlying code.
	 * Removed the Survey and Changelog banners from the plugin UI.
	 * Removed an obsolete white labeling option under **Method selection**.
	 * Added a check when enabling the "Use custom logo" option: if no logo has been uploaded, a clear error message is now shown pointing to the 2FA code page design settings.
	 * Removed the  unecessary "Activate free version" button from Premium licensing prompt.
	 * Improved custom CSS handling for buttons on the user profile area, so all WP 2FA buttons consistently pick up plugin styling (or the user's custom CSS overrides) instead of falling back to WordPress defaults inconsistently.
	 * Added help text under all subtitles on **White Labeling → Customize 2FA code page → Edit content**, providing a short description for each rich-text field.
	 * Added missing hint help text on the **Customize setup wizard** page for the Email (HOTP), Yubico, Clickatell, Twilio, and Authy methods, explaining what the hint field controls during 2FA setup.
	 * Added an upgrade modal that explains the benefits of full white labeling when users click locked white labeling options.
	 * Passkeys can now be revoked only by the person who generated them instead of other site administrators.

 * **Bug fixes**

	 * Fixed: The WooCommerce `/my-account/{2fa-endpoint}/` URL returned a 404 after any rewrite rules flush triggered from an admin request (e.g. saving Settings → Permalinks). 
	 * Optimized performance by removing unnecessary front-end checks for an expired event banner.
	 * Fixed: `wp_delete_post` was being called with a post ID of 0 when saving settings under WordPress 6.9, triggering a `_doing_it_wrong()` notice and causing 500 errors on sites using Acorn / Sage.
	 * Fixed: PHP Deprecated `htmlspecialchars(): Passing null to parameter #1` shown inside the SMS template boxes on PHP 8.3 with WordPress 6.9.4+.
	 * Fixed: PHP Deprecated `Automatic conversion of false to array` in the Authy and role settings controller extensions on PHP 8.3 multisite installations.
	 * Fixed: PHP Notice `Function WP_Scripts::add was called incorrectly. The script with the handle "wp_2fa_yubico" was enqueued with dependencies that are not registered: wp2fa-dialog` in WordPress 6.9.1+.
	 * Fixed: The **Locked** user status was not displayed next to a user after their grace period expired and they attempted to log in again.
	 * Fixed: Users who configured 2FA voluntarily (without being enforced by a policy) were shown as **Configured** instead of the correct **Configured (but not required)** status.
	 * Fixed: Users not covered by 2FA enforcement were shown as "User has not logged in yet, 2FA status is unknown" instead of "Not required".
	 * Fixed: Passkeys were always counted as 0 on the Reports page, regardless of how many passkeys had actually been registered.
	 * Fixed: Horizontal scrolling caused by the encryption and enforcement dashboard notices when viewing WP 2FA admin pages.
	 * Fixed: CSS selectors that started with a number (e.g. `#2fa-user-global-configuration`) have been renamed to valid, `wp-2fa`-prefixed selectors for consistency and to work correctly with SCSS/LESS preprocessors.
	 * Fixed: The FlyOut remote configuration fetch is now respectful of user preferences, aligning better with WordPress.org compliance expectations.

 * **Breaking changes**

	 * JSON settings exports created in versions earlier than 4.0 cannot be imported to a version 4.0 install. Recreate the settings export using version 4.0. 
	 * Email template customization is now available as an Enterprise feature. Existing custom email templates will continue to work without interruption.
	
Refer to the complete [plugin changelog](https://melapress.com/support/kb/wp-2fa-plugin-changelog/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=WP2FA&utm_content=plugin+repos+description) for more detailed information about what was new, improved and fixed in previous version updates of WP 2FA.
