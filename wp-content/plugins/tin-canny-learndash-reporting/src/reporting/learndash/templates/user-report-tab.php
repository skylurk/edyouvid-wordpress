<?php
namespace uncanny_learndash_reporting;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="uo-admin-reporting-tab-single" id="userReportTab" style="display: block">

	<?php do_action( 'tincanny_reporting_user_report_after_begin' ); ?>
	<?php if( false === TinCannyShortcode::$is_independent_shortcode || is_admin() ){  ?>
	<div id="reporting-user-navigation" class="reporting-breadcrumbs">
		<ul class="reporting-breadcrumbs-items">
			<li class="reporting-breadcrumbs-item reporting-breadcrumbs-item--guide">
				<span><?php _e( 'Tin Canny Reports', 'uncanny-learndash-reporting' ); ?></span>
			</li>
			<li id="user-navigate-link" class="reporting-breadcrumbs-item reporting-breadcrumbs-item--visible reporting-breadcrumbs-item--current">
				<span>
					<?php _e( 'User Report', 'uncanny-learndash-reporting' ); ?>
				</span>
			</li>
			<li id="userCourseDisplayName" class="reporting-breadcrumbs-item"></li>
			<li id="userCourseSingleTitle" class="reporting-breadcrumbs-item"></li>
		</ul>
	</div>
	<?php } ?>

	<div class="reporting-section" id="usersOverviewContainer">
		<div class="reporting-metabox">

	        <div class="reporting-dashboard-col-heading" id="usersOverviewTableHeading">
	        	<?php esc_html_e( 'Users', 'uncanny-learndash-reporting' ); ?>
	        </div>
	        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding">
	            <table id="usersOverviewTable" class="display responsive reporting-table" width="100%">
					<tbody>
						<tr>
							<td class="reporting-table__loading-cell">
								<div class="reporting-dashboard-status reporting-dashboard-status--loading">
									<div class="reporting-dashboard-status__icon"></div>
									<div class="reporting-dashboard-status__text">
										<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
									</div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
	        </div>
		</div>
	</div>

	<div class="reporting-user-overview">
		<div class="reporting-section" id="singleUserProfileContainer" style="display: none;">
			<div class="reporting-metabox">
				<div class="reporting-dashboard-col-heading" id="singleUserProfileHeading">
					<?php esc_html_e( 'Profile', 'uncanny-learndash-reporting' ); ?>
				</div>
				<div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-min-height">
					<div class="reporting-user-card">
						<div class="reporting-user-card__avatar" id="singleUserProfileAvatar"></div>
						<div class="reporting-user-card__content">
							<div class="reporting-user-card__name" id="singleUserProfileDisplayName"></div>
							<div class="reporting-user-card__email" id="singleUserProfileEmail"></div>
							<div class="reporting-user-card__id" id="singleUserProfileID"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="reporting-section" id="userSingleOverviewContainer" style="display: none;">
			<div class="reporting-metabox">
		        <div class="reporting-dashboard-col-heading" id="usersSingleOverviewTableHeading">
		        	<?php esc_html_e( 'Overview', 'uncanny-learndash-reporting' ); ?>
		        </div>
		        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
		        	<div class="reporting-metabox--hide-table-top reporting-metabox--table-one-result">
			            <table id="userSingleOverviewTable" class="display responsive reporting-table" width="100%">
							<tbody>
								<tr>
									<td class="reporting-table__loading-cell">
										<div class="reporting-dashboard-status reporting-dashboard-status--loading">
											<div class="reporting-dashboard-status__icon"></div>
											<div class="reporting-dashboard-status__text">
												<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
		        	</div>
		        </div>
			</div>
		</div>

		<div class="reporting-section" id="userSingleCourseProgressSummaryContainer" style="display: none;">
			<div class="reporting-metabox">
				<div class="reporting-dashboard-col-heading" id="userSingleCourseStartedDate">
					<?php esc_html_e( 'Start date', 'uncanny-learndash-reporting' ); ?>: <span id="courseStartedDateValue"></span>
				</div>
		        <div class="reporting-dashboard-col-heading" id="userSingleCourseProgressSummaryTableHeading">
		        	<?php esc_html_e( 'Progress Summary', 'uncanny-learndash-reporting' ); ?>
		        </div>
		        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
		        	<div class="reporting-metabox--hide-table-top reporting-metabox--table-one-result">
			            <table id="userSingleCourseProgressSummaryTable" class="display responsive reporting-table" width="100%">
							<tbody>
								<tr>
									<td class="reporting-table__loading-cell">
										<div class="reporting-dashboard-status reporting-dashboard-status--loading">
											<div class="reporting-dashboard-status__icon"></div>
											<div class="reporting-dashboard-status__text">
												<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
		        	</div>
		        </div>
			</div>
		</div>
	</div>

	<div class="reporting-section" id="userSingleCoursesOverviewContainer" style="display: none;">
		<div class="reporting-metabox">
	        <div class="reporting-dashboard-col-heading" id="userSingleCoursesOverviewTableHeading">
	        	<?php
				echo esc_html(
					/* Translators: 1:Course label*/
					sprintf( __( "User's %1\$s List", 'uncanny-learndash-reporting' ), $template_data['labels']['course'] )
				);
				?>
	        </div>
	        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
	            <table id="userSingleCoursesOverviewTable" class="display responsive reporting-table reporting-table-selectable" width="100%">
					<tbody>
						<tr>
							<td class="reporting-table__loading-cell">
								<div class="reporting-dashboard-status reporting-dashboard-status--loading">
									<div class="reporting-dashboard-status__icon"></div>
									<div class="reporting-dashboard-status__text">
										<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
									</div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
	        </div>
		</div>
	</div>

	<div class="reporting-section reporting-user-course-overview">
		<div class="reporting-user-course-overview__tabs">
			<div id="userSingleCourseProgressMenuContainer" style="display: none;">
				<h2 id="userSingleCourseProgressMenuHeading" style="display: none"></h2>
				<ul class="reporting-single-course-progress-tabs" id="userSingleCourseProgressMenu">
					<li class="reporting-single-course-progress-tabs__item" id="menuLessons">
						<?php echo esc_html( $template_data['labels']['lessons'] ); ?>
					</li>
					<li class="reporting-single-course-progress-tabs__item" id="menuTopics">
						<?php echo esc_html( $template_data['labels']['topics'] ); ?>
					</li>
					<li class="reporting-single-course-progress-tabs__item" id="menuQuizzes">
						<?php echo esc_html( $template_data['labels']['quizzes'] ); ?>
					</li>
					<li class="reporting-single-course-progress-tabs__item" id="menuAssignments">
						<?php echo esc_html( $template_data['labels']['assignments'] ); ?>
					</li>
					<?php $show_tin_can_menu = is_admin() || ( isset( $tincanny_settings['enableTinCanReportFrontEnd'] ) && 1 === (int) $tincanny_settings['enableTinCanReportFrontEnd'] ); ?>
					<?php if ( $show_tin_can_menu ) { ?>
						<li class="reporting-single-course-progress-tabs__item" <?php echo \uncanny_learndash_reporting\TinCannyShortcode::$tincan_show; ?> id="menuTinCan">
							<?php _e( 'Tin Can', 'uncanny-learndash-reporting' ); ?>
						</li>
					<?php } ?>
				</ul>
			</div>
		</div>
		<div class="reporting-user-course-overview__content">
			<div id="userSingleCourseLessonsContainer" style="display: none;">
				<div class="reporting-metabox">
			        <div class="reporting-dashboard-col-heading" id="userSingleCourseLessonsTableHeading">
			        	<?php echo $template_data['labels']['lessons']; ?>
			        </div>
			        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
			            <table id="userSingleCourseLessonsTable" class="display responsive reporting-table reporting-table-selectable" width="100%">
							<tbody>
								<tr>
									<td class="reporting-table__loading-cell">
										<div class="reporting-dashboard-status reporting-dashboard-status--loading">
											<div class="reporting-dashboard-status__icon"></div>
											<div class="reporting-dashboard-status__text">
												<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
			        </div>
				</div>
			</div>

			<div id="userSingleCourseTopicsContainer" style="display: none;">
				<div class="reporting-metabox">
			        <div class="reporting-dashboard-col-heading" id="userSingleCourseTopicsTableHeading">
			        	<?php echo $template_data['labels']['topics']; ?>
			        </div>
			        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
				        <table id="userSingleCourseTopicsTable" class="display responsive reporting-table reporting-table-selectable" width="100%">
							<tbody>
								<tr>
									<td class="reporting-table__loading-cell">
										<div class="reporting-dashboard-status reporting-dashboard-status--loading">
											<div class="reporting-dashboard-status__icon"></div>
											<div class="reporting-dashboard-status__text">
												<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
			        </div>
				</div>
			</div>

			<div id="userSingleCourseQuizzesContainer" style="display: none;">
				<div class="reporting-metabox">
			        <div class="reporting-dashboard-col-heading" id="userSingleCourseQuizzesTableHeading">
			        	<?php echo $template_data['labels']['quizzes']; ?>
			        </div>
			        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
				        <table id="userSingleCourseQuizzesTable" class="display responsive reporting-table reporting-table-selectable" width="100%">
							<tbody>
								<tr>
									<td class="reporting-table__loading-cell">
										<div class="reporting-dashboard-status reporting-dashboard-status--loading">
											<div class="reporting-dashboard-status__icon"></div>
											<div class="reporting-dashboard-status__text">
												<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
			        </div>
				</div>
			</div>

			<div id="userSingleCourseAssignmentsContainer" style="display: none;">
				<div class="reporting-metabox">
			        <div class="reporting-dashboard-col-heading" id="userSingleCourseAssignmentsTableHeading">
			        	<?php echo $template_data['labels']['assignments']; ?>
			        </div>
			        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
				        <table id="userSingleCourseAssignmentsTable" class="display responsive reporting-table reporting-table-selectable" width="100%">
							<tbody>
								<tr>
									<td class="reporting-table__loading-cell">
										<div class="reporting-dashboard-status reporting-dashboard-status--loading">
											<div class="reporting-dashboard-status__icon"></div>
											<div class="reporting-dashboard-status__text">
												<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
			        </div>
				</div>
			</div>

			<div id="userSingleCourseTinCanContainer" style="display: none;">
				<div class="reporting-metabox">
			        <div class="reporting-dashboard-col-heading" id="userSingleCourseTinCanTableHeading">
			        	<?php _e( 'Tin Can', 'uncanny-learndash-reporting' ); ?>
			        </div>
			        <div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding reporting-dashboard-col-content--no-min-height">
				        <table id="userSingleCourseTinCanTable" class="display responsive reporting-table reporting-table-selectable" width="100%">
							<tbody>
								<tr>
									<td class="reporting-table__loading-cell">
										<div class="reporting-dashboard-status reporting-dashboard-status--loading">
											<div class="reporting-dashboard-status__icon"></div>
											<div class="reporting-dashboard-status__text">
												<?php _e( 'Loading', 'uncanny-learndash-reporting' ); ?>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
			        </div>
				</div>
			</div>
		</div>
	</div>

	<?php do_action( 'tincanny_reporting_user_report_before_end' ); ?>

</div>
