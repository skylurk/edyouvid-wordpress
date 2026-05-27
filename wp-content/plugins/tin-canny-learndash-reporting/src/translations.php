<?php
namespace uncanny_learndash_reporting;

/**
 *
 */
class Translations {
	/**
	 * @return array
	 */
	public static function get_js_localized_strings() {

		// listed as they appear in file order under \plugins\tin-canny-learndash-reporting\src\assets\admin\js\scripts\components\*.js
		return array(
			// charts.js
			'In Progress'                                 => __( 'In Progress', 'uncanny-learndash-reporting' ),
			'Completed'                                   => __( 'Completed', 'uncanny-learndash-reporting' ),
			'Not Started'                                 => __( 'Not Started', 'uncanny-learndash-reporting' ),
			'No Data'                                     => __( 'No Data', 'uncanny-learndash-reporting' ),
			// config.js
			'Are you sure you want to permanently delete all Tin Can data? This cannot be undone.' => __( 'Are you sure you want to permanently delete all Tin Can data? This cannot be undone.', 'uncanny-learndash-reporting' ),
			'Are you sure you want to permanently delete all Bookmark data? This cannot be undone.' => __( 'Are you sure you want to permanently delete all Bookmark data? This cannot be undone.', 'uncanny-learndash-reporting' ),
			// data-object.js
			'%'                                           => __( '%', 'uncanny-learndash-reporting' ),
			// query-string.js
			'Please select your criteria to filter the Tin Can data.' => __( 'Please select your criteria to filter the Tin Can data.', 'uncanny-learndash-reporting' ),
			'Please select your criteria to filter the xAPI Quiz data.' => __( 'Please select your criteria to filter the xAPI Quiz data.', 'uncanny-learndash-reporting' ),
			// tables.js
			'Enrolled'                                    => __( 'Enrolled', 'uncanny-learndash-reporting' ),
			'Avg Time to Complete'                        => __( 'Avg Time to Complete', 'uncanny-learndash-reporting' ),
			'Avg Time Spent'                              => __( 'Avg Time Spent', 'uncanny-learndash-reporting' ),
			'% Complete'                                  => __( '% Complete', 'uncanny-learndash-reporting' ),
			'Certificate Link'                            => __( 'Certificate Link', 'uncanny-learndash-reporting' ),
			'Users Enrolled'                              => __( 'Users Enrolled', 'uncanny-learndash-reporting' ),
			'Average Time to Complete'                    => __( 'Average Time to Complete', 'uncanny-learndash-reporting' ),
			'Average '                                    => __( 'Average ', 'uncanny-learndash-reporting' ),
			'Display Name'                                => __( 'Name', 'uncanny-learndash-reporting' ),
			'First Name'                                  => __( 'First Name', 'uncanny-learndash-reporting' ),
			'Last Name'                                   => __( 'Last Name', 'uncanny-learndash-reporting' ),
			'Username'                                    => __( 'Username', 'uncanny-learndash-reporting' ),
			'Email Address'                               => __( 'Email Address', 'uncanny-learndash-reporting' ),
			'Course Enrolled'                             => __( 'Course Enrolled', 'uncanny-learndash-reporting' ),
			/* translators: %s is the "Course" label */
			'Quiz Average'                                => sprintf( __( '%s Average', 'uncanny-learndash-reporting' ), \LearnDash_Custom_Label::get_label( 'quiz' ) ),
			'Completion Date'                             => __( 'Completion Date', 'uncanny-learndash-reporting' ),
			'Time to Complete'                            => __( 'Time to Complete', 'uncanny-learndash-reporting' ),
			'Time Spent'                                  => __( 'Time Spent', 'uncanny-learndash-reporting' ),
			'Status'                                      => __( 'Status', 'uncanny-learndash-reporting' ),
			/* translators: %s is the "Lesson" label */
			'Associated Lesson'                           => sprintf( __( 'Associated %s', 'uncanny-learndash-reporting' ), \LearnDash_Custom_Label::get_label( 'lesson' ) ),
			'Name'                                        => __( 'Name', 'uncanny-learndash-reporting' ),
			'Date Completed'                              => __( 'Date Completed', 'uncanny-learndash-reporting' ),
			'Assignment Name'                             => __( 'Assignment Name', 'uncanny-learndash-reporting' ),
			'Approval'                                    => __( 'Approval', 'uncanny-learndash-reporting' ),
			'Submitted On'                                => __( 'Submitted On', 'uncanny-learndash-reporting' ),
			'Module'                                      => __( 'Module', 'uncanny-learndash-reporting' ),
			'Target'                                      => __( 'Target', 'uncanny-learndash-reporting' ),
			'Action'                                      => __( 'Action', 'uncanny-learndash-reporting' ),
			'Result'                                      => __( 'Result', 'uncanny-learndash-reporting' ),
			'Date'                                        => __( 'Date', 'uncanny-learndash-reporting' ),
			'There are no activities to report.'          => __( 'There are no activities to report.', 'uncanny-learndash-reporting' ),
			'CSV Export'                                  => __( 'CSV Export', 'uncanny-learndash-reporting' ),
			'Excel Export'                                => __( 'Excel Export', 'uncanny-learndash-reporting' ),
			/* translators: %s is the course label */
			'missingTitleCourseId'                        => _x( '%s ID: ', '%s is the "Course" label', 'uncanny-learndash-reporting' ),
			'Not Complete'                                => __( 'Not Complete', 'uncanny-learndash-reporting' ),
			'Not Approved'                                => __( 'Not Approved', 'uncanny-learndash-reporting' ),
			'Approved'                                    => __( 'Approved', 'uncanny-learndash-reporting' ),
			'Page'                                        => __( 'Page', 'uncanny-learndash-reporting' ),
			'View'                                        => __( 'View', 'uncanny-learndash-reporting' ),
			// tabs.js
			' Summary'                                    => __( ' Summary', 'uncanny-learndash-reporting' ),
			'< Users Overview'                            => __( '< Users Overview', 'uncanny-learndash-reporting' ),
			'< User Overview'                             => __( '< User Overview', 'uncanny-learndash-reporting' ),
			'< Users '                                    => __( '< User\'s ', 'uncanny-learndash-reporting' ),
			' Overview'                                   => __( ' Overview', 'uncanny-learndash-reporting' ),
			'Showing _START_ to _END_ of _TOTAL_ entries' => __( 'Showing _START_ to _END_ of _TOTAL_ entries', 'uncanny-learndash-reporting' ),
			'Showing 0 to 0 of 0 entries'                 => __( 'Showing 0 to 0 of 0 entries', 'uncanny-learndash-reporting' ),
			'(filtered from _MAX_ total entries)'         => __( '(filtered from _MAX_ total entries)', 'uncanny-learndash-reporting' ),
			'Show _MENU_ entries'                         => __( 'Show _MENU_ entries', 'uncanny-learndash-reporting' ),
			'Loading...'                                  => __( 'Loading...', 'uncanny-learndash-reporting' ),
			'Processing...'                               => __( 'Processing...', 'uncanny-learndash-reporting' ),
			'Search:'                                     => __( 'Search:', 'uncanny-learndash-reporting' ),
			'No matching records found'                   => __( 'No matching records found', 'uncanny-learndash-reporting' ),
			'First'                                       => __( 'First', 'uncanny-learndash-reporting' ),
			'Last'                                        => __( 'Last', 'uncanny-learndash-reporting' ),
			'Next'                                        => __( 'Next', 'uncanny-learndash-reporting' ),
			'Previous'                                    => __( 'Previous', 'uncanny-learndash-reporting' ),
			': activate to sort column ascending'         => __( ': activate to sort column ascending', 'uncanny-learndash-reporting' ),
			': activate to sort column descending'        => __( ': activate to sort column descending', 'uncanny-learndash-reporting' ),
			'Detailed Report'                             => __( 'Detailed Report', 'uncanny-learndash-reporting' ),
			'Score'                                       => __( 'Score', 'uncanny-learndash-reporting' ),
			'Order'                                       => __( 'Order', 'uncanny-learndash-reporting' ),
			/* translators: %s is the quiz title */
			'tablesColumnsAvgQuizScore'                   => _x( 'Avg %s Score', '%s is the "Quiz" label', 'uncanny-learndash-reporting' ),
			/* translators: %s is the course title */
			'tablesColumnsCoursesEnrolled'                => _x( '%s Enrolled', '%s is the "Courses" label', 'uncanny-learndash-reporting' ),
			/* translators: %s is the lesson title */
			'tablesColumnsLessonName'                     => _x( '%s Name', '%s is the "Lesson" label', 'uncanny-learndash-reporting' ),
			/* translators: %s is the topic title */
			'tablesColumnsTopicName'                      => _x( '%s Name', '%s is the "Topic" label', 'uncanny-learndash-reporting' ),
			/* translators: %s is the quiz title */
			'tablesColumnsQuizName'                       => _x( '%s Name', '%s is the "Quiz" label', 'uncanny-learndash-reporting' ),
			'tablesColumnsDetails'                        => __( 'Details', 'uncanny-learndash-reporting' ),
			'tablesButtonSeeDetails'                      => __( 'See details', 'uncanny-learndash-reporting' ),
			'tablesSearchPlaceholder'                     => __( 'Search...', 'uncanny-learndash-reporting' ),
			/* translators: %s is the course title */
			'overviewGoToCourseOverview'                  => _x( '%s Overview', '%s is the "Courses" label', 'uncanny-learndash-reporting' ),
			'overviewGoToCourseUserReport'                => __( 'User Report', 'uncanny-learndash-reporting' ),
			'overviewUsers'                               => __( 'Users', 'uncanny-learndash-reporting' ),
			/* translators: %s is the user ID */
			'overviewUserCardId'                          => __( 'ID: %s', 'uncanny-learndash-reporting' ),
			'overviewBoxesTitleRecentActivities'          => __( 'Recent Activities', 'uncanny-learndash-reporting' ),
			'overviewBoxesTitleReports'                   => __( 'Reports', 'uncanny-learndash-reporting' ),
			/* translators: %s is the "Courses" label */
			'overviewBoxesTitleCompletedCourses'          => _x( 'Most Completed %s', '%s is the "Courses" label', 'uncanny-learndash-reporting' ),
			/* translators: %s is the "Courses" label */
			'overviewBoxesReportsCourseReportTitle'       => _x( '%s Report', '%s is the "Course" label', 'uncanny-learndash-reporting' ),
			'overviewBoxesReportsCourseReportDescription' => __( 'A summary-level overview of LearnDash courses and user progress', 'uncanny-learndash-reporting' ),
			'overviewBoxesReportsUserReportTitle'         => __( 'User Report', 'uncanny-learndash-reporting' ),
			'overviewBoxesReportsUserReportDescription'   => __( 'Monitor progress for individual users enrolled in LearnDash courses', 'uncanny-learndash-reporting' ),
			'overviewBoxesReportsTinCanReportTitle'       => __( 'Tin Can Report', 'uncanny-learndash-reporting' ),
			'overviewBoxesReportsTinCanReportDescription' => __( 'Detailed records of user activity in H5P and uploaded modules', 'uncanny-learndash-reporting' ),
			/* translators: %s is the "Courses" label */
			'overviewBoxesReportsCoursesCompletionSeeAll' => _x( 'See all %s', '%s is the "Courses" label', 'uncanny-learndash-reporting' ),
			'overviewBoxesReportsCoursesCompletionNoData' => __( 'No completions registered', 'uncanny-learndash-reporting' ),
			'overviewLoading'                             => __( 'Loading', 'uncanny-learndash-reporting' ),
			'graphNoActivity'                             => __( 'No activity registered', 'uncanny-learndash-reporting' ),
			'graphNoEnrolledUsers'                        => __( 'No enrolled users', 'uncanny-learndash-reporting' ),
			/* translators: %s is the "Courses" label */
			'graphCourseCompletions'                      => _x( '%s Completions', '%s is the "Course" label', 'uncanny-learndash-reporting' ),
			'graphTinCanStatements'                       => __( 'Tin Can Statements', 'uncanny-learndash-reporting' ),
			/* translators: %s is the number of completions */
			'graphTooltipCompletions'                     => _x( '%s completion(s)', '%s is a number', 'uncanny-learndash-reporting' ),
			/* translators: %s is the number of statements */
			'graphTooltipStatements'                      => _x( '%s statement(s)', '%s is a number', 'uncanny-learndash-reporting' ),
			'customizeColumns'                            => _x( 'Customize columns', 'Customize columns', 'uncanny-learndash-reporting' ),
			'hideCustomizeColumns'                        => _x( 'Hide customize columns', 'Customize columns', 'uncanny-learndash-reporting' ),
			'showAll'                                     => __( 'Show all', 'uncanny-learndash-reporting' ),
			'ID'                                          => __( 'ID', 'uncanny-learndash-reporting' ),
		);
	}

	/**
	 * @return array
	 */
	public static function get_i18n_strings() {
		return array(
			'dataNotRight'    => __( 'Data not right?', 'uncanny-learndash-reporting' ),
			/* translators: %s is a link */
			'tryRunning'      => __( 'Try running the %s.', 'uncanny-learndash-reporting' ),
			'updatesLinkText' => __( 'LearnDash Data Upgrades', 'uncanny-learndash-reporting' ),
			'allQuestions'    => __( 'All Questions', 'uncanny-learndash-reporting' ),
			'dropdown'        => array(
				'errorLoading'    => _x( 'The results could not be loaded.', 'Dropdown', 'uncanny-learndash-reporting' ),
				'inputTooLong'    => array(
					'singular' => _x( 'Please delete 1 character', 'Dropdown', 'uncanny-learndash-reporting' ),
					/* translators: %s is a character count */
					'plural'   => _x( 'Please delete %s characters', 'Dropdown', 'uncanny-learndash-reporting' ),
				),
				/* translators: %s is a character count */
				'inputTooShort'   => _x( 'Please enter %s or more characters', 'Dropdown', 'uncanny-learndash-reporting' ),
				'loadingMore'     => _x( 'Loading more results...', 'Dropdown', 'uncanny-learndash-reporting' ),
				'maximumSelected' => array(
					'singular' => _x( 'You can only select 1 item', 'Dropdown', 'uncanny-learndash-reporting' ),
					/* translators: %s is a number of items */
					'plural'   => _x( 'You can only select %s items', 'Dropdown', 'uncanny-learndash-reporting' ),
				),
				'noResults'       => _x( 'No results found', 'Dropdown', 'uncanny-learndash-reporting' ),
				'searching'       => _x( 'Searching...', 'Dropdown', 'uncanny-learndash-reporting' ),
				'removeAllItems'  => _x( 'Remove all items', 'Dropdown', 'uncanny-learndash-reporting' ),
			),
			'tables'          => array(
				'processing'        => _x( 'Processing...', 'Table', 'uncanny-learndash-reporting' ),
				'sSearch'           => _x( 'Search', 'Table', 'uncanny-learndash-reporting' ),
				'searchPlaceholder' => _x( 'Search', 'Table', 'uncanny-learndash-reporting' ),
				/* translators: %s is a number */
				'lengthMenu'        => sprintf( _x( 'Show %s entries', 'Table', 'uncanny-learndash-reporting' ), '_MENU_' ),
				/* translators: Both %1$s and %2$s are numbers */
				'info'              => sprintf( _x( 'Showing page %1$s of %2$s', 'Table', 'uncanny-learndash-reporting' ), '_PAGE_', '_PAGES_' ),
				'infoEmpty'         => _x( 'Showing 0 to 0 of 0 entries', 'Table', 'uncanny-learndash-reporting' ),
				/* translators: %s is a number */
				'infoFiltered'      => sprintf( _x( '(filtered from %s total entries)', 'Table', 'uncanny-learndash-reporting' ), '_MAX_' ),
				'loadingRecords'    => _x( 'Loading', 'Table', 'uncanny-learndash-reporting' ),
				'zeroRecords'       => _x( 'No matching records found', 'Table', 'uncanny-learndash-reporting' ),
				'emptyTable'        => _x( 'No data available in table', 'Table', 'uncanny-learndash-reporting' ),
				'paginate'          => array(
					/* translators: Table pagination */
					'first'    => _x( 'First', 'Table', 'uncanny-learndash-reporting' ),
					/* translators: Table pagination */
					'previous' => _x( 'Previous', 'Table', 'uncanny-learndash-reporting' ),
					/* translators: Table pagination */
					'next'     => _x( 'Next', 'Table', 'uncanny-learndash-reporting' ),
					/* translators: Table pagination */
					'last'     => _x( 'Last', 'Table', 'uncanny-learndash-reporting' ),
				),
				'sortAscending'     => _x( ': activate to sort column ascending', 'Table', 'uncanny-learndash-reporting' ),
				'sortDescending'    => _x( ': activate to sort column descending', 'Table', 'uncanny-learndash-reporting' ),
				'buttons'           => array(
					/* translators: Table button */
					'csvExport' => _x( 'CSV export', 'Table', 'uncanny-learndash-reporting' ),
					/* translators: Table button */
					'pdfExport' => _x( 'PDF export', 'Table', 'uncanny-learndash-reporting' ),
				),
			),
		);
	}
}
