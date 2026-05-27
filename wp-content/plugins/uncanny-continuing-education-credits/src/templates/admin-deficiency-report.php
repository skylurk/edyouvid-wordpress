<?php

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$roll_over_date     = get_option( 'ceu_rollover_date', false );
$roll_over_date     = \DateTime::createFromFormat( 'd\/m', $roll_over_date );
$has_roll_over_date = $roll_over_date != false;

$credit_designation_label_plural = Utilities::credit_designation_label( 'plural', __( 'CEUs', 'uncanny-ceu' ) );
$is_ld_active                    = defined( 'LEARNDASH_VERSION' );
?>

<div class="wrap">
	<div class="ucec ucec-report">
		<?php

		// Add admin header and tabs
		$tab_active = 'uncanny-deficiency-report';
		require Utilities::get_template( 'admin-header.php' );

		?>

		<?php if ( $has_roll_over_date ) { ?>

			<?php

			// Format date
			$roll_over_date_formatted = $roll_over_date->format( 'F d, Y' );

			// Set date to 0
			$roll_over_date->setTime( 0, 0, 0 );

			?>

			<div class="ucec-report-toolbar">
				<div class="ucec-report-filters">
					<form method="GET">
						<input type="hidden" value="<?php echo $_GET['page']; ?>" name="page">

						<div class="ucec-report-filter ucec-report-filter--rollover-date">
							<div class="ucec-report-filter__fake-field">
								<div class="ucec-report-rollover-date__data">
									<?php printf( __( 'Rollover date: %s', 'uncanny-ceu' ), $roll_over_date_formatted ); ?>
								</div>
								<div class="ucec-report-rollover-date__kb">
									<a href="https://www.uncannyowl.com/knowledge-base/setup-continuing-education-credits-plugin/#Rollover_Dates"
									   target="_blank" class="ucec-report-rollover-date-kb-icon"></a>
								</div>
							</div>
						</div>

						<div class="ucec-report-filter ucec-report-filter--users">
							<select name="user" id="ucec-users" class="ucec-report-select ucec-report-filter__select">
								<?php echo BackendDeficiencyReport::get_users_dropdown(); ?>
							</select>
						</div>
						<div class="ucec-report-filter ucec-report-filter--groups">
							<select name="group" id="ucec-groups" class="ucec-report-select ucec-report-filter__select">
								<?php echo BackendDeficiencyReport::get_groups_dropdown(); ?>
							</select>
						</div>

						<div class="ucec-report-filter ucec-report-filter--submit">
							<button type="submit" class="ucec-btn ucec-btn--primary">
								<?php _e( 'Filter', 'uncanny-ceu' ); ?>
							</button>
						</div>
					</form>
				</div>
				<div class="ucec-report-actions">
					<div id="ucec-report-export-csv" class="ucec-btn ucec-btn--primary">
						<?php _e( 'Export CSV', 'uncanny-ceu' ); ?>
					</div>
				</div>
			</div>

			<div class="ucec-report-results">

				<?php if ( BackendDeficiencyReport::$users_ceus ) { ?>

					<table id="ucec-deficiency-report" class="ucec-report-results__table"
						   data-csvfilename="<?php printf( '%s - %s', __( 'Deficiency Report', 'uncanny-ceu' ), date( 'Y-m-j g i A' ) ); ?>">
						<thead class="ucec-report-results-table__thead">
						<tr class="ucec-report-results-table__tr">
							<th class="ucec-report-header-cell ucec-report-header-cell--user">
								<?php _e( 'User', 'uncanny-ceu' ); ?>
							</th>
							<th class="ucec-report-header-cell">
								<?php printf( __( 'Required %s', 'uncanny-ceu' ), $credit_designation_label_plural ); ?>
							</th>
							<th class="ucec-report-header-cell">
								<?php printf( __( 'Earned %s', 'uncanny-ceu' ), $credit_designation_label_plural ); ?>
							</th>
						</tr>
						</thead>
						<tbody class="ucec-report-results-table__tbody">
						<?php foreach ( BackendDeficiencyReport::$users_ceus as $entry ) { ?>

							<?php
							if ( isset( $entry['highest_group_credit_required'] ) && isset( $entry['ceu_required_personal'] ) && isset( $entry['ceu_earned'] ) ) {
								if ( $entry['ceu_required_personal'] < $entry['highest_group_credit_required'] ) {
									if ( $entry['ceu_earned'] >= $entry['highest_group_credit_required'] ) {
										continue;
									}
								} else {
									if ( isset( $entry['ceu_required_personal'] ) && $entry['ceu_earned'] >= $entry['ceu_required_personal'] ) {
										continue;
									}
								}
							}

							?>

							<tr class="ucec-report-results-table__tr">
								<td class="ucec-report-body-cell ucec-report-body-cell--user">
									<?php

									/**
									 * We're doing this so DataTables uses this when sorting instead of the avatar's URL
									 */

									?>

									<div class="ucec-report-cell-user">
										<?php if ( true === apply_filters( 'uo_ceu_show_avatar_in_reports', false ) ) { ?>
											<div class="ucec-report-cell-user__avatar">
												<img src="<?php echo $entry['avatar']; ?>"
													 alt="<?php printf( __( "%s's avatar", 'uncanny-ceu' ), $entry['first_name'] ); ?>"
													 class="ucec-report-cell-user__avatar-img">
											</div>
										<?php } ?>
										<div class="ucec-report-cell-user__info">
											<div class="ucec-report-cell-user__name">
												<a href="<?php echo get_edit_user_link( $entry['user_id'] ); ?>"
												   target="_blank"
												   class="ucec-report-external-link"><?php echo $entry['first_name']; ?><?php echo $entry['last_name']; ?></a>
											</div>
											<div class="ucec-report-cell-user__email">
												<?php echo $entry['email']; ?>
											</div>
										</div>
									</div>
								</td>
								<td class="ucec-report-body-cell">
									<?php
									if ( $is_ld_active ) {
										if ( isset( $entry['ceu_required_personal'] ) && isset( $entry['highest_group_credit_required'] ) ) {
											if ( $entry['ceu_required_personal'] < $entry['highest_group_credit_required'] ) {
												echo $entry['highest_group_credit_required'];
											} else {
												echo $entry['ceu_required_personal'];
											}
										}
									} elseif ( isset( $entry['ceu_required_personal'] ) ) {
										echo esc_html( $entry['ceu_required_personal'] );
									}

									?>
								</td>
								<td class="ucec-report-body-cell">
									<?php echo $entry['ceu_earned']; ?>
								</td>
							</tr>

						<?php } ?>
						</tbody>
					</table>

				<?php } else { ?>

					<?php
					if ( ! isset( $_GET['user'] ) ) {
						echo '<div class="ucec-report-results__no-results">
							<div class="ucec-report-results-no-results__text">
								' . __( 'Please select a user or group to display results.', 'uncanny-ceu' ) . '
							</div>
						</div>';
					} else {
						echo '<div class="ucec-report-results__no-results">
							<div class="ucec-report-results-no-results__icon"></div>
							<div class="ucec-report-results-no-results__text">
								' . __( 'No Results', 'uncanny-ceu' ) . '
							</div>
						</div>';
					}
					?>
				<?php } ?>
			</div>

		<?php } else { ?>
			<div class="uo-admin-section">
				<div class="uo-admin-block">
					<?php

					$settings_page_url = add_query_arg(
						array(
							'page' => 'uncanny-ceu',
						),
						admin_url( 'admin.php' )
					);

					printf(
						__( 'This report requires that a Rollover Date be set from the %s.', 'uncanny-ceu' ),
						'<a href="' . $settings_page_url . '">' . __( 'Uncanny CEUs Settings page', 'uncanny-ceu' ) . '</a>'
					);

					?>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
