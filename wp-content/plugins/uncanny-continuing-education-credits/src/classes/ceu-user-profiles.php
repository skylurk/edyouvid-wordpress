<?php

namespace uncanny_ceu;


class CeuUserProfiles {

	function __construct() {
		//Add Additional field on User Settings page for Xero Client ID
		add_action( 'show_user_profile', array( $this, 'add_additional_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'add_additional_fields' ) );
		add_action( 'personal_options_update', array( $this, 'update_extra_profile_fields' ) );
		add_action( 'edit_user_profile_update',  array( $this, 'update_extra_profile_fields' ) );

	}

	/**
	 * @param $user
	 */
	function add_additional_fields( $user ) {

		$credit_designation_label_plural = Utilities::credit_designation_label('plural', __( 'CEUs', 'uncanny-ceu' ) );

		$roll_over_date = get_option( 'ceu_rollover_date', false );

		?>
        <table class="form-table">
            <tbody>


        <?php

		if ( $roll_over_date &&  '' !== $roll_over_date ) {

		    global $wpdb;

			$user_groups = array();

			if( function_exists('learndash_get_users_group_ids')){
				$user_groups = learndash_get_users_group_ids( $user->ID, true );
			}

			if(empty($user_groups)){
				$highest_group_credit = '';
            }else{
				$user_groups = implode( ',',$user_groups);
				$query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'group_credit_required' && post_id IN ($user_groups) ORDER BY  meta_value * 1  DESC LIMIT 1";
				$highest_group_credit = $wpdb->get_var($query);
            }

		    ?>
            <tr>
                <th>
                    <h3>
                        <label for="individual_credit_required"><?php echo sprintf( __( 'Required %1$s - Personal:', 'uncanny-ceu' ), $credit_designation_label_plural ); ?></label>
                    </h3>
                </th>
                <td>
					<input type="text" id="individual_credit_required" name="individual_credit_required" value="<?php echo get_user_meta( $user->ID, 'individual_credit_required', true); ?>" />
                </td>
            </tr>
            <tr>
                <th>
                    <h3>
                        <label for="group_credit_required"><?php echo sprintf( __( 'Required %1$s - Group:', 'uncanny-ceu' ), $credit_designation_label_plural ); ?></label>
                    </h3>
                </th>
                <td>
                    <?php echo $highest_group_credit; ?>
                </td>
            </tr>
            <?php

		}

		?>


            <tr>
                <th>
                    <h3>
                        <label for="description"><?php echo sprintf( __( '%1$s Earned', 'uncanny-ceu' ), $credit_designation_label_plural ); ?></label>
                    </h3>
                </th>
                <td>
					<?php
					$total_earned = do_shortcode( "[uo_ceu_total user-id='$user->ID']" );
					echo sprintf( __( 'Total Earned: %s', 'uncanny-ceu' ), $total_earned );
					?>
                </td>
				<?php
				/*foreach ( $user_meta as $meta_key => $meta_value ) {
					if ( false !== strpos( $meta_key, 'ceu_title' ) ) {

						echo $meta_value[0] . "<br />";
					}
				}*/
				?>
            </tr>
            <tr>
                <th>
                    <h3>
                        <label for="description"><?php echo sprintf( __( '%1$s Earned Since Rollover', 'uncanny-ceu' ), $credit_designation_label_plural ); ?></label>
                    </h3>
                </th>
                <td>
					<?php
					$total_earned = do_shortcode( "[uo_ceu_total_rollover user-id='$user->ID']" );
					echo sprintf( __( 'Total Earned: %s', 'uncanny-ceu' ), $total_earned );
					?>
                </td>
            </tbody>
        </table>

		<?php

	}

	/**
     * Update User profile meta
	 * @param $user_id
	 */
	function update_extra_profile_fields($user_id) {
		if ( current_user_can('edit_user',$user_id) ){
		    if( isset($_POST['individual_credit_required']) ){
			    update_user_meta($user_id, 'individual_credit_required', $_POST['individual_credit_required']);
            }
        }
	}

	/**
	 * Get CEU data for post
	 *
	 * @param int $course_id
	 * @param array $posts
	 *
	 * @return array|bool
	 */
	private function get_post_title_ceu( $course_id, $posts ) {

		foreach ( $posts as $post ) {
			if ( $post->ID === $course_id ) {
				return array(
					'ID'    => $post->ID,
					'title' => $post->post_title,
					'ceu'   => get_post_meta( $post->ID, 'ceu_value', true )
				);
			}
		}

		return false;
	}
}