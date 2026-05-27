<?php

namespace uncanny_ceu;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wrap">
	<div class="ucec">

		<?php

		// Add admin header and tabs
		$tab_active = 'uncanny-ceu-kb';
		include Utilities::get_template( 'admin-header.php' );

		?>

		<div class="ucec-help">
			<?php

			$kb_category = 'uncanny-ceus-learndash';
			$json        = wp_remote_get( 'https://www.uncannyowl.com/wp-json/uncanny-rest-api/v1/kb/' . $kb_category . '?wpnonce=' . wp_create_nonce( time() ) );
			if ( ! is_wp_error( $json ) ) {
				if ( 200 === wp_remote_retrieve_response_code( $json ) ) {
					$data = json_decode( $json['body'], true );
					if ( $data ) {
						echo $data;
					}
				}
			}

			?>
		</div>

		<?php if ( $license_is_active ) { ?>

			<a href="<?php echo menu_page_url( 'uncanny-ceu-kb', false ) . '&send-ticket=true'; ?>">
				<?php _e( "I can't find the answer to my question.", 'uncanny-ceu' ); ?>
			</a>

		<?php } ?>
	</div>
</div>
