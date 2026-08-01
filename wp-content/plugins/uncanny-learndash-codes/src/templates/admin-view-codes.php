<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wrap uo-ulc-admin uncannyowl-default-design">
	<div class="ulc uncannyowl-default-design">
		<?php

		// Add admin header and tabs.
		$tab_active = 'uncanny-learndash-codes';
		require Config::get_template( 'admin-header.php' );

		?>

		<div class="ulc__admin-content">
			<div id="page_coupon_stat">
				<div class="uncannyowl-card">
					<h2></h2> <!-- LearnDash notice will be shown here -->
					<div class="uncannyowl-card-header">
						<?php
						// Display heading based on whether we're viewing groups or specific group codes
						if ( ! SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) {
							// Main groups listing - show "View Code Batches" heading
							echo '<h2 class="wp-heading-inline">' . esc_html__( 'View Code Batches', 'uncanny-learndash-codes' ) . '</h2>';
						} else {
							if ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) && 'all' === SharedFunctionality::ulc_filter_input( 'group_id' ) ) {
								// Specific group codes - show "View Generated Codes" heading
								echo '<h2 class="wp-heading-inline">' . esc_html__( 'View All Generated Codes', 'uncanny-learndash-codes' ) . '</h2>';
							} else {
								// Specific group codes - show "View Generated Codes" heading
								echo '<h2 class="wp-heading-inline">' . esc_html__( 'View Generated Codes', 'uncanny-learndash-codes' ) . '</h2>';
							}
						}
						?>
						<div class="uo-codes-buttons">
							<?php
							// Display back button when viewing codes for a specific group
							if ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) {
								printf(
									'<a href="%s" class="uncannyowl-btn uncannyowl-btn--primary">%s</a>',
									remove_query_arg(
										array(
											'group_id',
											'orderby',
											'order',
										)
									),
									esc_html__( 'View Code Batches', 'uncanny-learndash-codes' )
								);
							} else {
								// Display "View All Codes" button on main page
								global $wpdb;
								if ( $wpdb->get_var( "SELECT COUNT(code) FROM $wpdb->prefix" . Config::$tbl_codes ) > 0 ) {
									printf(
										'<a href="%s" class="uncannyowl-btn uncannyowl-btn--primary">%s</a>',
										add_query_arg(
											array( 'group_id' => 'all' ),
											remove_query_arg(
												array(
													'orderby',
													'order',
												)
											)
										),
										__( 'View All Codes', 'uncanny-learndash-codes' )
									);
								}
							}
							?>
						</div>
					</div>	<!--END uncannyowl-card-header -->
					
					<div class="uo-codes-list">

					</div> <!-- uo-codes-list -->

					<?php 
					// Display top navigation (bulk actions, search form, etc.) outside the scrollable container
					$table->display_tablenav( 'top' );
					?>
					<div class="uncannyowl-table-container <?php echo SharedFunctionality::ulc_filter_has_var( 'group_id' ) ? 'uo-codes-view-codes' : 'uo-codes-view-groups'; ?>">
						<?php 
						// Display only the table content inside the scrollable container
						$table->display();
						?>
					</div>
					<?php 
					// Display bottom navigation (pagination) outside the scrollable container
					$table->display_tablenav( 'bottom' );
					?>
					
					<style>
					/* Hide any duplicate pagination that might appear inside the scrollable container */
					.uncannyowl-table-container .tablenav {
						display: none !important;
					}
					/* Strikethrough for cancelled codes */
					.code-cancelled {
						text-decoration: line-through;
						opacity: 0.7;
					}
					</style>
				</div>
			</div>
		</div>
	</div>
</div>
