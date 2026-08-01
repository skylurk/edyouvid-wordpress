<?php

namespace uncanny_learndash_codes;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wrap uncannyowl-default-design">

		<?php

		// Add admin header and tabs.
		$tab_active = 'uncanny-codes-plugins';
		require Config::get_template( 'admin-header.php' );

		?>

		<?php

		$product_id = 4909;
		$json       = wp_remote_get( 'https://www.uncannyowl.com/wp-json/uncanny-rest-api/v1/download/' . $product_id );

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
