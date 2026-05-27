<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * @var object $generate_codes
 * @var object $learndash_posts
 * @var object $learndash_groups
 */

?>

<div class="uo-admin-header">

	<div class="uo-admin-title">
		<?php
		if ( 'edit' === (string) $generate_codes->mode ) {
			esc_html_e( 'Modify codes', 'uncanny-learndash-codes' );
		} elseif ( 'create' === (string) $generate_codes->mode ) {
			esc_html_e( 'Generate codes', 'uncanny-learndash-codes' );
		}

		?>
	</div>

	<div class="uo-admin-description">
		<?php
		if ( 'edit' === (string) $generate_codes->mode ) {
			printf( __( "Update code parameters in the fields below and the changes will be applied to new redemptions. Any fields not listed below can't be changed; a new batch will have to be created instead. For more information about editing codes, please refer to the <a href='%s' target='_blank'>%s</a>.", 'uncanny-learndash-codes' ), esc_url( 'https://www.uncannyowl.com/article-categories/uncanny-learndash-code' ), esc_html__( 'Knowledge Base articles', 'uncanny-learndash-codes' ) );
		} elseif ( 'create' === (string) $generate_codes->mode ) {
			printf( __( 'Create a new code batch using the wizard below. For assistance creating and editing codes, please refer to our <a href="%s" target="_blank" >%s</a>.', 'uncanny-learndash-codes' ), esc_url( 'https://www.uncannyowl.com/article-categories/uncanny-learndash-code' ), esc_html__( 'Knowledge Base articles', 'uncanny-learndash-codes' ) );
		} ?>
	</div>
</div>
