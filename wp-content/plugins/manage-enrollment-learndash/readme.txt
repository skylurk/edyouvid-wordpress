=== Manage Enrollment for LearnDash ===
Contributors: liveaspankaj
Donate link:
Tags: learndash, grassblade, manual, enrollment, enroll, unenroll, bulk
Requires at least: 4.0
Tested up to: 6.2.2
Stable tag: 1.0
Requires PHP: 5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage Enrollment for LearnDash, lets you enroll users to courses or groups in bulk. You can select users from a given UI, paste a list of users or use the CSV to bulk upload your selected courses, groups and users for processing.

== Description ==

Manage Enrollment for LearnDash, lets you enroll users to courses or groups in bulk. You can select users from a given UI, paste a list of users or use the CSV to bulk upload your selected courses, groups and users for processing.

**Bulk enroll existing users:**
- You can use it for single enrollment as well as for **bulk enrollment** of hundreds of users.
- You can also upload a CSV file with user_id, user_email, user_login, course_id, and group_id columns.
- Quickly list all the users, groups, or courses, and then process them in bulk in any order you want.

**Bulk create users and enroll:**
- This plugin allows you to create users in bulk and enroll them into courses or groups at the same time. Required [Import Users from CSV](https://wordpress.org/plugins/import-users-from-csv/) plugin.
- You can also choose to send a notification to the new users and to display passwords on user login.
- Create CSV file with the following columns, user_login, user_email, user_pass, first_name, last_name, display_name and role.

**Tracking:**
- If you have an LRS, you can see tracking data, including the user id and name of the admin who processed the enrollments.

**Requirements to use this plugin:**
To use this plugin you need these two plugins:
1. [LearnDash LMS](https://www.nextsoftwaresolutions.com/r/learndash/wp_mel_plugin_page)
2. [GrassBlade xAPI Companion](https://www.nextsoftwaresolutions.com/grassblade-lrs-experience-api/)

**Related Plugins:**
- [Autocomplete LearnDash Lessons and Topics](https://wordpress.org/plugins/autocomplete-learndash/)
- [Visibility Control for LearnDash](https://wordpress.org/plugins/visibility-control-for-learndash/)


== Installation ==

This section describes how to install the plugin and get it working.


1. Upload the plugin files to the `/wp-content/plugins/manual-enrollment-learndash` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to WP ADMIN > GrassBlade > Manual Completions for LearnDash or (WP ADMIN > LearnDash > Manual Completions)


== Frequently Asked Questions ==


== Screenshots ==

1. Enrollment page
2. Checking status and perform an action
3. Create New users and enroll
4. CSV Upload Format

== Changelog ==

= 1.3 =
* Improvement: User search is changed for select2 to own code to improve performance for large websites.
* Feature: Added Total count

= 1.2 =
* Fixed: Bulk unenrollment is enrolling the users after first 10 users.

= 1.1 =
* Fixed: Compatibility issue with LearnDash ProPanel

= 1.0 =
* Initial Commit
