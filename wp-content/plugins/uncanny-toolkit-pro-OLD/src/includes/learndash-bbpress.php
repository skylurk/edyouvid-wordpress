<?php

namespace uncanny_pro_toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Learndash_BBPress
 * @package uncanny_pro_toolkit
 */
class Learndash_BBPress extends Boot {

	/**
	 * Learndash_BBPress constructor.
	 */
	function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'ld_display_group_selector' ) );
		add_action( 'save_post_forum', array( $this, 'ld_save_associated_group' ) );
		$this->ld_include_plugin_code();
	}

	/**
	 *
	 */
	public function ld_include_plugin_code() {
		require_once Boot::get_pro_include( 'learndash-bbpress-functions.php', UO_FILE );
		require_once Boot::get_pro_include( 'class-forum-group-widget.php', UO_FILE );
	}

	/**
	 *
	 */
	public function ld_display_group_selector() {

		add_meta_box( 'uo_ld_group_selector', esc_attr__( 'LearnDash Group Forums', 'uncanny-pro-toolkit' ), array(
			$this,
			'ld_display_group_selector_callback',
		), 'forum', 'advanced', 'high' );
	}

	/**
	 *
	 */
	public function ld_display_group_selector_callback() {

		wp_nonce_field( 'uo_ld_bbpress_meta_box', 'uo_ld_bbpress_nonce' );

		$groups            = $this->ld_get_group_list();
		$associated_groups = get_post_meta( get_the_ID(), 'uo_ld_associated_groups', true );
		$limit_post_access = get_post_meta( get_the_ID(), 'uo_ld_post_limit_access', true );
		$allow_forum_view  = get_post_meta( get_the_ID(), 'uo_ld_allow_forum_view', true );

		/* Translators: 1. LearnDash group label */
		$default_message_without_access = sprintf( esc_attr__( 'This forum is restricted to members of the associated %1$s(s).', 'uncanny-pro-toolkit' ), 'Group' );
		$message_without_access         = get_post_meta( get_the_ID(), 'uo_ld_message_without_access', true );

		if ( empty( $message_without_access ) ) {
			$message_without_access = $default_message_without_access;
		}

		$selected = null;
		?>

        <script>
            jQuery(document).ready(function ($) {
                $('#uo_ld_clear_group').click(function (e) {
                    e.preventDefault();
                    $("#ld_group_selector_dd option:selected").each(function () {
                        $(this).removeAttr('selected'); //or whatever else
                    });
                });
            });
        </script>

        <table class="form-table">
            <tbody>
            <tr>
                <td>
                    <label for="ld_group_selector_dd">
                        <strong>
							<?php
							/* Translators: 1. LearnDash group label  */
							echo esc_html( sprintf( esc_attr__( 'Associated %1$s(s)', 'uncanny-pro-toolkit' ), 'Group' ) );
							?>
                            :
                        </strong>
                    </label>
                    <br>
                    <select name='ld_group_selector_dd[]' size="4" id='ld_group_selector_dd' multiple="multiple">
                        <optgroup
                                label="
								<?php
								/* Translators: 1. LearnDash groups label  */
								echo esc_html( sprintf( esc_attr__( 'Select %1$s', 'uncanny-pro-toolkit' ), 'Groups' ) );
								?>
									">
							<?php
							if ( is_array( $groups ) ) {
								foreach ( $groups as $group ) {
									$selected = null;
									if ( is_array( $associated_groups ) && in_array( $group->ID, $associated_groups ) ) {
										$selected = 'selected';
									}
									?>
                                    <option value="<?php echo esc_attr( $group->ID ); ?>" <?php echo esc_attr( $selected ); ?>>
										<?php echo esc_attr( $group->post_title ); ?>
                                    </option>
									<?php
								}
							}
							?>
                        </optgroup>
                    </select>
                    <br>
                    <a href="" id="uo_ld_clear_group" class="button"
                       style="margin-top: 10px;"><?php esc_attr_e( 'Clear All', 'uncanny-pro-toolkit' ); ?></a>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="uo_ld_post_limit_access"><strong><?php esc_attr_e( 'Post Limit Access', 'uncanny-pro-toolkit' ); ?>
                            : </strong></label>
                    <select name="uo_ld_post_limit_access" id="uo_ld_post_limit_access">
                        <option value="all" <?php selected( 'all', $limit_post_access, true ); ?>><?php esc_attr_e( 'All', 'uncanny-pro-toolkit' ); ?></option>
                        <option value="any" <?php selected( 'any', $limit_post_access, true ); ?>><?php esc_attr_e( 'Any', 'uncanny-pro-toolkit' ); ?></option>
                    </select>
                    <p class="desc">
						<?php
						/* Translators: 1. LearnDash groups label */
						echo esc_html( sprintf( esc_attr__( 'If you select ALL, then users must be a member of all of the associated %1$s in order to post.', 'uncanny-pro-toolkit' ), 'Groups' ) );
						?>
                    </p>
                    <p class="desc">
						<?php
						/* Translators: 1. LearnDash groups label */
						echo esc_html( sprintf( esc_attr__( 'If you select ANY, then users only need to be a member of any one of the selected %1$s in order to post.', 'uncanny-pro-toolkit' ), 'Groups' ) );
						?>
                    </p>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="uo_ld_message_without_access"><strong><?php esc_attr_e( 'Message shown to users without access', 'uncanny-pro-toolkit' ); ?>
                            : </strong></label>
                    <br>
                    <textarea cols="100" rows="5"
                              name="uo_ld_message_without_access"><?php echo esc_attr( $message_without_access ); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="uo_ld_allow_forum_view"><strong><?php esc_attr_e( 'Forum View', 'uncanny-pro-toolkit' ); ?>
                            : </strong></label>
                    <br>
                    <input type="hidden" name="uo_ld_allow_forum_view" value="0">
                    <input type="checkbox" name="uo_ld_allow_forum_view"
                           value="1" <?php checked( '1', $allow_forum_view, true ); ?>>&nbsp;<?php esc_attr_e( 'Check this box to allow users that are not members of the associated Group(s) to view forum threads and topics (they will not be able to post replies).', 'uncanny-pro-toolkit' ); ?>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	/**
	 * @param $post_id
	 */
	public function ld_save_associated_group( $post_id ) {
		if ( ! wp_verify_nonce( $_POST['uo_ld_bbpress_nonce'], 'uo_ld_bbpress_meta_box' ) ) {
			return;
		}

		// DELETE Group to forum associations
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_value = %d", $post_id ) );

		// DELETE Forum to Group associations
		delete_post_meta( $post_id, 'uo_ld_associated_groups' );

		if ( isset( $_POST['ld_group_selector_dd'] ) && ! empty( $_POST['ld_group_selector_dd'] ) ) {
			$ld_group_selector_dd = array_filter( $_POST['ld_group_selector_dd'] );
			// Assign groups to forum
			update_post_meta( $post_id, 'uo_ld_associated_groups', $ld_group_selector_dd );
			foreach ( $ld_group_selector_dd as $new_group ) {
				// Assign Forum to Group
				update_post_meta( $new_group, 'uo_ld_associated_forum' . $post_id, $post_id );
			}
		}

		// Save post limit access options
		update_post_meta( $post_id, 'uo_ld_post_limit_access', sanitize_text_field( $_POST['uo_ld_post_limit_access'] ) );
		update_post_meta( $post_id, 'uo_ld_message_without_access', wp_kses_post( $_POST['uo_ld_message_without_access'] ) );
		update_post_meta( $post_id, 'uo_ld_allow_forum_view', sanitize_text_field( $_POST['uo_ld_allow_forum_view'] ) );
	}

	/**
	 * @return int[]|\WP_Post[]
	 */
	public function ld_get_group_list() {
		$args = array(
			'posts_per_page' => 999,
			'post_type'      => 'groups',
			'post_status'    => 'publish',
		);

		$groups = get_posts( $args );

		return $groups;
	}
}

