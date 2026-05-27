=== LearnDash Retake Quiz ===
Contributors: wooninjas
Tags: learndash, quiz, question, delay, retake, negative marking
Requires at least: 5.1
Tested up to: 6.3.2
Stable tag: 1.7.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This add-on enhance the functionality of LearnDash Quizzes, so that a user can retake the quiz unless all the questions are correctly answered. Only the incorrect questions will show on the retake.

== Description ==

This add-on enhance the functionality of LearnDash Quizzes, so that a user can retake the quiz unless all the questions are correctly answered. Only the incorrect questions will show on the retake.

= Prerequisites: =

* Wordpress
* LearnDash

= Features: =

* Users would be able to retake ony wrong/missed questions upon retaking quizzes
* Allow retake quizzes for specific courses only
* Allow retake quizzes for specific quiz category
* Allow retake quizzes for specific quiz tag
* Allow retake quizzes for specific quizzes
* Option to delay retake quizzes for minutes, hours, days, months and years
* Option to deduct points from users accounts for wrong answers
* Option to exclude quizzes for retaking only wrong questions process
* Option to exclude users from retaking only wrong questions process
* Option to restrict users to reattempt the quizzes once they completed it
* Option to display message to the quiz page when reattempting completed quizzes
* Option to edit/update the completed quiz message
* Option to delete selected users and selected quiz retake data

== Installation ==

Before installation please make sure you have latest LearnDash plugin installed.

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

= Is it necessary to have LearnDash plugin activated to use this add-on?

Yes, you must have LearnDash plugin enabled to use this add-on

== Changelog ==

= 1.7.7 =
* Fix: Resolved issues with deleting retake data.
* Fix: Resolved issues related to quiz retake retries.
* Fix: Resolved issues associated with quiz retake delay.
* Fix: Resolved minor warnings found on the settings page.
* New: Added activity/debug logs to track plugin activities.
* New: Updated plugin settings UI for an improved user experience.

= 1.7.6 =
* Fix: Compatibility issues with latest versions of WordPress and LearnDash.

= 1.7.5 =
* New: Feature to reset course progress on failing quiz.

= 1.7.4 =
* Fix: Result issue on quiz completion.
* Fix: Minor warnings on quiz settings page.
* Fix: Fatal error and warnings on quiz retake.

= 1.7.3 =
* Fix: Plugin translations.
* Fix: Quiz retake delete logs.
* Fix: Quiz retake delete options.
* Fix: Quiz summary indicators issue.
* Fix: Reduced API requests for checking license Validity.
* Fix: Compatibility issues with latest versions of WordPress and LearnDash
* New: Added Option in quiz completion to override retake message.

= 1.7.2 =
* Fix: Quiz progressbar on quiz completion

= 1.7.1 =
* New: Added clear retake logs button
* Fix: Reduce retake settings page load time

= 1.7.0 =
* New: Option to delete previous quiz attempt on retake
* New: Option to exclude certain questions to reappear from second attempt onwards
* Fix: Retake quiz feature not working with "ld_quiz" shortcode
* Fix: Compatibility issues with latest versions of WordPress and LearnDash

= 1.6.2 =
* New: Option to display number of quiz retake/retries left on front-end
* New: Retake report for group admins
* Fix: Quiz complete action hook was not triggering
* Fix: Strings translations
* Fix: Question overview color feedback, and quiz summary

= 1.5 =
* New: Added new option to disallow quiz
* Fix: Revamp quiz complete check issue

= 1.4 =
* New: Option to delete selected users and selected quiz retake data
* Fix: Fixed embedding short-code issue for quiz message
* Fix: Fixed retake reset data option for non admin users

= 1.3 =
* Fix: Made the add-on compatible with WPProQuiz DB table name

= 1.2 =
* New: Restricted users to reattempt the quizzes once they completed it
* New: Displayed message on quiz page when reattempting a completed quiz
* New: Added option to edit/update the quiz completed message
* Fix: Made the add-on compatible with LearnDash version 3.1.1
* Fix: Fixed total question count issue when creating quiz result

= 1.1 =
* New: Made the add-on compatible with LearnDash version 3.0
* New: Added option to enable/disable retake wrong questions, allow negative marking, and retkae quiz delay features separately.
* Fix: Fixed Licensing issues

= 1.0 =
* Initial