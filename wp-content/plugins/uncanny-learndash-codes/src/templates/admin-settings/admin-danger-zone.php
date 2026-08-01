<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<div class="uncannyowl-admin-section uncannyowl-admin-section--danger">
	<div class="uncannyowl-admin-header">
		<div class="uncannyowl-admin-title"><?php esc_html_e( 'Danger zone', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uncannyowl-admin-block">
		<div class="uncannyowl-admin-form">
			<div class="uncannyowl-admin-field uncannyowl-admin-field--danger">
				<div class="uncannyowl-admin-field__title"><?php esc_html_e( 'Reset data in database', 'uncanny-learndash-codes' ); ?></div>
				<div class="uncannyowl-admin-field__description">
					<?php esc_html_e( 'This will permanently delete all redemption codes, user data, and tracking information from the database.', 'uncanny-learndash-codes' ); ?>
				</div>
				
				<div class="uncannyowl-error-banner">
					<div class="uncannyowl-error-banner__title"><?php esc_html_e( 'Warning', 'uncanny-learndash-codes' ); ?></div>
					<div class="uncannyowl-error-banner__message">
						<?php esc_html_e( 'This action will delete all data including tracking of codes and users. Please download CSV before attempting this.', 'uncanny-learndash-codes' ); ?>
					</div>
				</div>
				
				<a href="<?php echo add_query_arg( array( 'mode' => 'reset' ), remove_query_arg( array( 'redirect_nonce' ) ) ); ?>"
				   onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete all data?', 'uncanny-learndash-codes' ); ?>');"
				   class="uncannyowl-btn uncannyowl-btn--danger uncannyowl-btn--sm"><?php esc_html_e( 'Reset database', 'uncanny-learndash-codes' ); ?></a>
			</div>
			
			<div class="uncannyowl-admin-field uncannyowl-admin-field--danger">
				<div class="uncannyowl-admin-field__title"><?php esc_html_e( 'Delete database tables', 'uncanny-learndash-codes' ); ?></div>
				<div class="uncannyowl-admin-field__description">
					<?php esc_html_e( 'This will completely remove all database tables and deactivate the plugin.', 'uncanny-learndash-codes' ); ?>
				</div>
				
				<div class="uncannyowl-error-banner">
					<div class="uncannyowl-error-banner__title"><?php esc_html_e( 'Critical Warning', 'uncanny-learndash-codes' ); ?></div>
					<div class="uncannyowl-error-banner__message">
						<?php esc_html_e( 'This action will delete all data including tracking of codes and users. Please download CSV before attempting this. This will deactivate Uncanny Redemption Codes.', 'uncanny-learndash-codes' ); ?>
					</div>
				</div>
				
				<a href="<?php echo add_query_arg( array( 'mode' => 'destroy' ), remove_query_arg( array( 'redirect_nonce' ) ) ); ?>"
				   onclick="return confirm('Are you sure you want to delete database tables?');"
				   class="uncannyowl-btn uncannyowl-btn--danger uncannyowl-btn--sm"><?php esc_html_e( 'Delete database', 'uncanny-learndash-codes' ); ?></a>
			</div>
		</div>
	</div>
</div>
