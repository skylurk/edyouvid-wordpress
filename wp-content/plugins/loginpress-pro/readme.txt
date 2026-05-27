Hi there, We at WPBrigade welcomes you to our premium users family.

== Frequently Asked Questions ==

= How to Install or Use LoginPress Pro? =

LoginPress Pro is a premium plugin which works if you have installed Free version already. So, first install our Free version from wordpress.org https://wordpress.org/plugins/loginpress/ and then install the Pro package.

If you have free version install already, then no need to install it again. Just Install Pro version and you are good to go.

LoginPress Pro version extends the Premium functionality to our Core version of LoginPress which is Free.

= Step-by-step instructions on How to Upgrade from existing Free version to Pro =

1. You have installed and setup Free version already.
2. Upload the Pro version.
3. Pro features will be enabled automatically.
4. You don't need to setup Free version options again.
5. Setup Pro features like Google fonts, Google reCaptcha, Choose themes etc


= Where is my license key and Pro version files? =

You should get 2 emails from us within 1 minute (otherwise contact us). The first one is an email with license keys and download links.

The second email will have login details for your account, where you can see your orders and download links, find license codes for Plugin activation and open new support requests etc

== Support ==
If you have any problem, please send an email to us at support@wpbrigade.com


== Current Pro Version ==

== Changelog ==

= 6.1.2 – 2026-01-26 =
* Enhancement: Administrators can now extend expired auto-login links (by duration or usage limit) instead of recreating them.
* Bugfix: Fixed Auto Login role-based redirect issue.
* Bugfix: Delete user_meta when "Remove Settings On Uninstall For Pro" option is selected from settings.

= 6.1.1 – 2026-01-09 =
* New Feature: Added social login settings for WordPress comments.
* Enhancement: Added compatibility for LoginPress Redirects with WPML languages.
* Bugfix: Improved auto-detection of the current username when adding it as a placeholder in the blacklist settings.
* Bugfix: Fixed issues with Limit Login Attempts and Social Login settings on fresh installations.
* Bugfix: Handling login redirects when the role name contains more than one word.
* Bugfix: Resolved login redirects conflicts with LearnDash.
* Bugfix: Fixed SQL syntax error in social login provider update process.
* Compatibility: Ensuring compatibility with existing roles and permissions.

= 6.1.0 – 2025-12-09 =
* Major update: Applied coding standards across the entire codebase.
* Enhancement: Improved add-ons layout management by adding the `loginpress_excluded_addons` filter.
* Enhancement: Optimized and cleaned up the codebase for better performance and maintainability.
* Enhancement: Reduced overall plugin file size for faster loading.
* Enhancement: Updated the LoginPress branded icon in the WordPress dashboard menu.
* Enhancement: Integrate core features with LoginPress - Widget Add-On.
* Enhancement: Added a filter `loginpress_social_login_can_register` to allow custom control over user registration during social login.
* Enhancement: Added an action `loginpress_social_login_registered` that fires after a user successfully registers through social login.
* Bugfix: Fixed several language issues in JavaScript strings.
* Bugfix: Fixed several issues with the LoginPress Customizer live preview.
* Bugfix: Resolved social login issues on subfolder installations.
* Bugfix: Resolved Add-Ons installations issue specific from v6.0.2.
* Compatibility: Compatible with WordPress 6.9

= 6.0.2 – 2025-11-10 =
* Bugfix: Social login styling issue on integration forms.
* Bugfix: Limit Login Attempt and social login tables not found on first install.
* Bugfix: EDD limit login attempts error message display issue.
* Enhancement: Optimized Login Widget asset loading - scripts now only load when widget is present on the page.

= 6.0.1 – 2025-11-01 =
* Bugfix: Fixed Google reCAPTCHA v3 integration on WooCommerce registration form.
* Bugfix: Fixed default values configuration for Limit Login Attempt Add-On.

= 6.0.0 – 2025-10-16 =
* Roar to 6.0: Converted all plugin settings to React for a faster, more modern admin experience.
* Bugfix: Fixed issue with Hide login feature, the LoginPress customizer theme switching option.
* Bugfix: Resolved Login Redirects issue with User Switching plugin.
* Bugfix: Fixed Social Login with Apple authentication issue.
* Bugfix: Fixed password strength error message display on login widget.
* New Feature: Auto-enable all Add-Ons upon LoginPress Pro activation.
* New Feature: Limit Login Attempts module enhancements:
    * Added custom error message support for limit login attempt errors.
    * Added custom email notifications for limit login attempt errors.
    * Introduced new "Status" column under “Attempt Details” table, showing Success, Failed, or Locked attempts.
    * Added Limit Concurrent feature — restricts the number of simultaneous sessions per user to prevent account sharing or suspicious activity.
    * Added controls to manage REST API endpoints: Disable User Endpoints, Disable App Login Endpoints, and Disable Application Passwords.
* New Feature: Added a custom font control in the LoginPress customizer for fully personalized login page typography.
* New Feature: Introduced an option to allow crawler access when "Force Login" is enabled on the site.
* New Feature: Enhanced security by adding a new option to require admin users to login before accessing the site through social platforms.
* New Feature: Added Microsoft Tenant ID option to the Microsoft Social Login settings.
* New Feature: Added ability to restrict specific domains during Social Login.
* New Feature: Added option to allow crawlers to access the site when force login is enabled.
* New Feature: Added LifterLMS redirect options in the Redirects tab for customized LMS user flows.
* Enhancement: Improved AutoLogin search — now admins can search users by email in addition to username.
* Enhancement: Added admin verification for Google reCaptcha v3 to implement before enabling for users.
* Improvement: Optimized and cleaned codebase for better performance and maintainability.

= 5.0.2 – 2025-07-17 =
* Bugfix: Important security update addressing WordPress.com authentication vulnerability.

= 5.0.1 – 2025-07-10 =
* Bugfix: Fixed Google reCAPTCHA V3 returning error issue.
* Bugfix: Fixed wp_loaded hook loading issue when LearnDash is active - now properly restricted to login page only.
* Bugfix: Resolved deprecated and renamed Google Fonts compatibility issues.
* Enhancement: Added new filter `loginpress_before_turnstile_validation` for custom validation handling.

= 5.0.0 – 2025-07-03 =
* New Feature: Integrations - Introducing new Integrations section that allows LoginPress to work effortlessly with other powerful WordPress plugins.
				* WooCommerce
				* Easy Digital Downloads
				* BuddyPress
				* BuddyBoss
				* LearnDash
				* Lifter LMS
* New Feature: Social Login Add-On - Introducing new social login providers in LoginPress.
				* Login with Amazon
				* Login with Twitch
				* Login with Spotify
				* Login with Disqus
				* Login with Pinterest
				* Login with Reddit
* New Feature: Enhanced security with a password strength meter now applied to the LoginPress widget.
* New Feature: Enhanced social login providers to the LoginPress widget.
* Enhancement: Enhanced Google Fonts support with a comprehensive collection of 1,849 fonts to provide users with more customization choices.
* Enhancement: Updated language files for Arabic, Dutch, Italian, and French to ensure better translations and localization.
* Bugfix: CSS Conflict in FireFox browser concerning reCaptchas.
* Compatibility: Compatible with WordPress 6.8

= 4.0.2 – 2025-04-18 =
* Bugfix: Turnstile captcha error message on page load.
* Bugfix: Provider "WordPress.org" screen issue reported in 4.0.1
* Compatibility: Compatible with WordPress 6.8

= 4.0.1 – 2025-04-18 =
* Bugfix: Fixed hCaptcha validation script callback issue.
* Bugfix: Updated `Redirect URI` path for each social provider setting.
* Bugfix: Improved RTL styling for social provider settings.
* Compatibility: Compatible with WordPress 6.8

= 4.0.0 – 2025-03-10 =
* New Feature: Dashboard UI/UX Refresh - Introducing a sleek new dashboard design for LoginPress pages.
* New Feature: Captchas - Introducing new Captchas in LoginPress.
				* Google reCaptcha
				* hCaptcha
				* Cloudflare Turnstile Captcha
* New Feature: Social Login Add-On - OAuth 2.0 configuration for Twitter login.
* New Feature: Social Login Add-On - Drag & drop option to change the buttons position.
* New Feature: Social Login Add-On - Introducing new social login providers in LoginPress.
				* Login with GitHub
				* Login with Discord
				* Login with Apple
				* Login with WordPress
* New Feature: Limit Login Attempt Add-On | Delete Log - Introducing new notice to delete log if cross 1000 records.
* New Feature: Limit Login Attempt Add-On | Download Log - Introducing new feature to download log in limit login attempt.
* New Feature: Auto Login Add-On - Introducing new feature to set link counter in auto login.
* Enhancement: Improved RTL styling for all strings within the plugin settings.
* Enhancement: Style the "Generate New Password" CTA based on select template.
* Enhancement: Color Customization - Eye-icon & Remember me checkbox color are now syncs with the button color settings.
* Enhancement: Import/Export Improvements - Enhanced functionality for smoother data handling.
* Enhancement: Uninstallation Optimization - Improved the uninstallation process for better cleanup and efficiency.
* Enhancement: Language File Updates - Updated language files for Arabic, Dutch, and French to ensure better translations and localization.
* Bugfix: PHP Warnings removed.
* Bugfix: Avatar styling issue in Login Widget Add-On.
* Compatibility: Compatible with WordPress 6.7

= 3.3.1 – 2025-01-03 =
* Bugfix: PHP Warning for translation hook.
* Bugfix: Add `user` object as 2nd parameter in `loginpress_unapprove_email` hook.
* Enhancement: Call optimization for the WordPress Hooks.
* Enhancement: Compatible with WordPress 6.7

= 3.3.0 – 2024-11-18 =
* Bugfix: Auto Login `New Link` creation conflict.
* Enhancement: Remove Password tracking from Limit Login Attempts Add-On.
* Enhancement: Refactored and optimized the code for better performance Specially Add-Ons settings.
* Enhancement: Compatible with WordPress 6.7

= 3.2.1 – 2024-10-22 =
* Bugfix: LoginPress Pro Add-ons functionality.
* Enhancement: Refactored and optimized the code for better performance.

= 3.2.0 - 11th October 2024 =
* Bugfix: Google reCaptcha V3 issue.
* Bugfix: Login Redirect Add-On search user issue concerning Auto Login Add-On.
* Bugfix: Corrected the reset email URL link concerning Hide Login Add-On.
* Enhancement: Introducing a filter `loginpress_autocomplete_search_length` to change the auto complete search length based on given value (Default: 3).
* Enhancement: Code Improvements.

= 3.1.3 - 22th August 2024 =
* Enhancement: Update user-search behavior in Auto Login and Login Redirect Add-Ons.
* Enhancement: Code Improvements.
* Enhancement: Compatible with WordPress 6.6.

= 3.1.2 - 12th June 2024 =
* Bugfix: Social Login Addon - Resolved error messages for non-registered users.
* Enhancement: Introducing a filter `loginpress_social_login_without_reg_error_message` to change the default error message for social login.
* Enhancement: Code Improvements.

= 3.1.1 - 3rd June 2024 =
* Bugfix: Widget Addon - Resolved error messages and meta links issues.
* Bugfix: Auto Login Addon - Ensured compatibility with the Approve/Deny feature.
* Bugfix: Limit Login Attempts Addon - Fixed conflict with custom error messages.

= 3.1.0 - 13th May 2024 =
* Bugfix: LoginPress ReCaptcha error message for V2 & v3.
* Bugfix: Bypass reCaptcha on Registration & Lost-Password Forms.
* Bugfix: Specific Pro Templates icon visibility.

= 3.0.2 - 23rd April 2024 =
* Bugfix: Limit Login Attempts was loading all records on settings page load, limited to latest 50 rows now.

= 3.0.1 - 23rd April 2024 =
* Bugfix: Version check missing for addons for 3.0 release.


= 3.0.0 - 23rd April 2024 =

* Roar to 3.0: Introducing a new dashboard UI/UX for LoginPress pages.
* Roar to 3.0: Introducing a new mechanism for LoginPress Add-Ons. All Add-Ons are managed through LoginPress Pro 3.0 instead of a separate plugin for each Add-On. Following following Add-Ons are merged into LoginPress Pro 3.0
				* Auto Login
				* Hide Login
				* Widget Login
				* Social Login
				* Limit Login Attempts
				* Login Redirects
* New Feature: Approve/Deny a user upon Registration by Admin.
* New Feature: New actions in Auto Login Add-On
				* Autologin Copy: Introducing the copy icon thorough that you can easily copy the auto login link.
				* Autologin Duration: Introducing duration feature, by default limit sets for 7 days after that duration, the link will expire.
				* Autologin Status: Introducing Enable/Disable feature to restrict the user access.
				* Autologin Email: Email the autologin link to a certain user.
				* Autologin Multiple Email: Email to Multiple Users, for email the autologin link to the group of users.
				* Optimized Add-On speed and code improvement.
* New Feature: Introducing Google reCaptcha Support for
				* WooCommerce Login Form.
				* WooCommerce Register Form.
				* WordPress Comments Section.
* New Feature: Introducing Microsoft Social Login.
* Enhancement: Replaced the Google PHP SDK with their own proprietary API for the Social Login Add-On.
* Enhancement: Compatibility of Hide Login Add-On with TranslatePress Plugin.
* Enhancement: Update the Google PHP API SDK v2.12.4 for Social Login Add-On.
* Enhancement: Update the Twitter(X) icon.
* Enhancement: Security Enhancements.
* Enhancement: Optimized plugin speed and code improvement.
* Enhancement: UI/UX Enhancements.
* Enhancement: PHP 8.2 Compatibility.
* Enhancement: Compatible with WordPress 6.5.
* Enhancement: Introducing a filter `loginpress_autologin_default_expiration` to change the default expiration limit for the autologin link. 
* Enhancement: Introducing a filter `loginpress_autologin_email_subject` for to change the email subject for the autologin email.
* Enhancement: Introducing a filter `loginpress_autologin_email_msg` for to change the content for the autologin email. 
* Enhancement: Introducing a filter `loginpress_autologin_inactive_error` to change the inactive error message for autologin. 
* Enhancement: Introducing a filter `loginpress_autologin_disable_error` to change the disable error message for autologin. 
* Enhancement: Introducing a filter `loginpress_autologin_expired_error` to change the expired error message for autologin. 
* Enhancement: Introducing a filter `loginpress_autologin_invalid_login_code` to change the invalid autologin code error message.
* Enhancement: Introducing a filter `prevent_loginpress_login_widget_redirect` to prevent the redirection on Login Widget.
* Enhancement: Introducing a filter `loginpress_apply_forcelogin_only_on` to only limit certain pages instead of whole site.

* Bugfix: Fix Google reCaptcha issue with LoginPress Limit Login Attempt Add-On.
* Bugfix: Fix Google reCaptcha V2 Invisible control over the different forms.
* Bugfix: Redirect to homepage page if empty value set for LoginPress Redirects.
* Bugfix: Fix the last attempt issue in LoginPress Limit Login Attempt.

* Compatibility: Resolved transparency issues related to the two-factor plugin, ensuring a seamless experience.
* Compatibility: Hide Login Add-On with TranslatePress Plugin.
* Compatibility: Google reCaptcha with PowerPack Login Plugin.

= 2.5.3 - 13th January, 2023 =
	* Bugfix: Google reCaptcha issue during user registration.
	* Compatibility: Compatible with WordPress 6.1

= 2.5.2 - 24th June, 2022 =
	* Bugfix: Google reCaptcha issue during user registration wth WordPress 6.0.
	* Enhancement: Styled the language selector for premium themes.
	* Compatibility: Compatible with PHP 8.1
	* Compatibility: Compatible with WordPress 6.0

= 2.5.1 - 1st March, 2022 =
	* Bugfix: Download and Install loginpress addons on Agency license.
	* Enhancement: Added a filter `loginpress_premium_theme` to extend the premium themes.
	* Enhancement: Design the Language Switcher dropdown for all themes.

= 2.5.0 - 9th November, 2020 =
	* New Feature: Introducing Google reCAPTCHA V2 Invisible.
	* New Feature: Introducing Google reCAPTCHA V3.
	* Bugfix: Date formate fix on license key deactivation.
	* Compatibility: Compatible with WordPress 5.5

= 2.4.0 - 16th July, 2020 =
	* Bugfix: Video background issue on mobile for several templates.
	* Bugfix: Google reCaptcha issue resolved on "Password Reset" Form.
	* Enhancement: Added a filter `loginpress_prevent_forcelogin` to prevent force login.

= 2.3.3 - 24th June, 2020 =
	* Bugfix: Google reCaptcha server base error.
	* Enhancement: Sort Google Fonts.

= 2.3.2 - 16th January, 2020 =
	* Enhancement: French Language added in LoginPress Pro.

= 2.3.1 - 20th November, 2019 =
	* Bugfix: Remove typo ";" from login templates.
	* Enhancement: Update admin email verification layout.

= 2.3.0 - 15th November, 2019 =
	* New Feature: Added a new PRO animated template.
	* Compatibility: Compatible with WordPress 5.3
	* Enhancement: Portuguese (Brazil) Language added in LoginPress Pro.

= 2.2.2 - 23rd October, 2019 =
	* Bugfix: Firefox CSS conflict with premium templates.
	* Bugfix: License key deactivation.
	* Enhancement: Added a filter `loginpress_exclude_forcelogin` to exclude the page's (with: ID or slug) from Force Login feature.
	* Enhancement: Italian Language added in LoginPress Pro.

= 2.2.1 - 17th August, 2019 =
	* Bugfix: Download LoginPress Pro - Stuck - when you install LoginPress Pro without Free version.

= 2.2.0 - 10th August, 2019 =
	* Bugfix: RTL Login templates.
	* Enhancement: Important Security update.
	* Enhancement: Update Google reCaptcha supported languages.

= 2.1.5 - 23rd July, 2019 =
	* Bugfix: Login form background issue with Corporate Template CSS issue.
	* Bugfix: SSL issue fix for Add-Ons.

= 2.1.4 - 16th July, 2019 =
	* New Feature: One click Install / Activate / Deactivate Add-Ons from addons page.
	* Bugfix: Persona & Wedding#2 Template CSS issue.
	* Bugfix: Optimized plugin speed and code improvement.
	* Enhancement: Create POEdit file in Pro and all addons.

= 2.1.3 - 9th July, 2019 =
	* Bugfix: Choose language option in Google reCaptcha (enabled).

= 2.1.2 - 8th July, 2019 =
	* Enhancement: Code Improvements.

= 2.1.1 - 10th June, 2019 =
	* New Feature: Add filter for removing the license page.
	* Enhancement: Apply masking on license key.
	* Enhancement: Code Improvements.

= 2.1.0 - 25th April, 2019 =
	* New Feature: Add Background Video, Compatible with free version feature.
	* New Feature: An option to Force users to Login for viewing the FULL site.
	* Enhancement: Code Improvements.

= 2.0.12 - 7th Dec, 2018 =
	* Enhancement: Important Security update.
	* Enhancement: Code Improvements.

= 2.0.11 - 27th June, 2018 =
	* Update: Google reCaptcha library.
	* Bugfix: licensing issue.
	* Enhancement: Code Improvements.

= 2.0.10 - 19th January, 2018 =
	* New Feature: Addons launched with auto updater and auto installer within the plugin addons page.

= 2.0.9 - 8th January, 2018 =
	* New Feature: Launching LoginPress addons - Auto Login, Hide Login, Login Redirects, Social Login, Login Widget.
	* Enhancement: Apply live google fonts on footer text.

= 2.0.8 - 3rd November, 2017 =
	* Enhancement: Validate your license key to enable automatic updates and add-ons support.

= 2.0.7 - 23rd August, 2017 =
	* Enhancement: We updated Free version and This pro is compatible with Free version changes.

= 2.0.6 - 11th July, 2017 =
	* BugFix: Fixed Google reCaptcha Compatibility issue.
	* Enhancement: Google fonts optimization.
	* Improved Code for speed optimization.

= 2.0.5 - 30th June, 2017 =
	* Captcha message alignment issue.
	* Layout issue on special cases like Theme 6 and Theme 10.
	* update option 'customize_presets_settings' to default1 after deactivate pro.

= 2.0.4 - 14th June, 2017 =
	* Compatible with Free version. We added some Promotion in Free version which have to disabled in Pro version.

= 2.0.3 - 9th June, 2017 =
	* Password Hint Text editing.
	* Compatible with 4.8
	* Fixing for reCaptcha conflict with WooCommerce login.

= 2.0.2 - 31st May, 2017 =
	* Important update.

= 2.0.1 - 29th April, 2017 =
	* Background and Colors for pre-defined templates.

= 2.0.0 - 16th April, 2017 =
	* Enhancement: Google Fonts.
	* Enhancement: Google reCaptcha.
	* Enhancement: Code Improvements.

= 1.0.0 - 28th Jan 2017 =
	* Initial Release.

Cheers
Adnan (WPBrigade)
