<div class="reporting-datatable">
	<div class="reporting-datatable__table">
		<div class="reporting-section">
			<div class="reporting-metabox">
				<div class="reporting-dashboard-col-heading" id="coursesOverviewTableHeading">
					<?php echo __( 'Tin Can Data', 'uncanny-learndash-reporting' ); ?>
				</div>
				<div class="reporting-dashboard-col-content reporting-dashboard-col-content--no-padding">
					<div id="tinCanReportContainer" style="position: relative;">
						<table id="tinCanReportTable"
							   class="display responsive reporting-table reporting-table-selectable"
							   style="width: 100%;">
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
						<div class="tincanny-table-overlay" id="tableOverlay">
							<div class="loading-spinner"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
