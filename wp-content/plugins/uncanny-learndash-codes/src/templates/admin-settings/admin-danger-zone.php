<?php
/**
 * @since   4.0
 * @author  Saad S.
 */
?>
<div class="uo-admin-section">
	<div class="uo-admin-header">
		<div class="uo-admin-title"><?php esc_html_e( 'Danger zone', 'uncanny-learndash-codes' ); ?></div>
	</div>
	<div class="uo-admin-block">
		<div class="uo-admin-form">
			<div class="uo-admin-field">
				<div
					class="uo-admin-label"><?php esc_html_e( 'Reset data in database', 'uncanny-learndash-codes' ); ?></div>

				<a href="<?php echo add_query_arg( array( 'mode' => 'reset' ), remove_query_arg( array( 'redirect_nonce' ) ) ); ?>"
				   onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete all data?', 'uncanny-learndash-codes' ); ?>');"
				   class="uo-admin-form-submit uo-admin-form-submit-danger"><?php esc_html_e( 'Reset database', 'uncanny-learndash-codes' ); ?></a>

				<div
					class="uo-admin-description"><?php _e( 'This action will <span class="uo-danger">delete</span> all data including tracking of codes and users. Please download CSV before attempting this.', 'uncanny-learndash-codes' ); ?></div>
			</div>

			<div class="uo-admin-field">
				<div
					class="uo-admin-label"><?php esc_html_e( 'Delete database tables', 'uncanny-learndash-codes' ); ?></div>

				<a href="<?php echo add_query_arg( array( 'mode' => 'destroy' ), remove_query_arg( array( 'redirect_nonce' ) ) ); ?>"
				   onclick="return confirm('Are you sure you want to delete database tables?');"
				   class="uo-admin-form-submit uo-admin-form-submit-danger"><?php esc_html_e( 'Delete database', 'uncanny-learndash-codes' ); ?></a>

				<div
					class="uo-admin-description"><?php _e( 'This action will <span class="uo-danger">delete</span> all data including tracking of codes and users. Please download CSV before attempting this. This will deactivate Uncanny Codes.', 'uncanny-learndash-codes' ); ?></div>
			</div>
		</div>
	</div>
</div>
