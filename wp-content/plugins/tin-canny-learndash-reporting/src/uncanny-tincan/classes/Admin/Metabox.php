<?php

namespace UCTINCAN\Admin;

/**
 * Metabox
 */
class Metabox {
	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		add_action( 'save_post', array( $this, 'save_metabox' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_custom_meta_boxes' ) );
		add_action( 'admin_init', array( $this, 'migrate_meta_keys' ), PHP_INT_MAX ); // Hook to perform migration
	}

	/**
	 * Fetches the values of specific meta keys and returns them as an array.
	 *
	 * @param int $post_id The ID of the post.
	 * @return array The meta values as an associative array.
	 */
	public static function get_meta_values( $post_id ) {
		$meta_keys = array(
			'restrict-mark-complete'        => 'uo_tc_restrict_mark_complete',
			'protect-scorm-tin-can-modules' => 'uo_tc_protect_modules',
			'completion-condition'          => 'uo_tc_completion_condition',
		);

		$meta_values = array();

		foreach ( $meta_keys as $legacy_key => $new_key ) {
			$value = get_post_meta( $post_id, $new_key, true );
			if ( ! empty( $value ) ) {
				$meta_values[ $legacy_key ] = $value;
			}
		}

		return $meta_values;
	}

	/**
	 * Add custom meta boxes
	 */
	public function add_custom_meta_boxes() {
		$tc_restricted_types = array( 'sfwd-lessons', 'sfwd-topic' );

		if ( class_exists( '\uncanny_pro_toolkit\OnePageCourseStep' ) ) {
			$active_classes = get_option( 'uncanny_toolkit_active_classes', array() );
			if ( ! empty( $active_classes ) && is_array( $active_classes ) && array_key_exists( 'uncanny_pro_toolkit\OnePageCourseStep', $active_classes ) ) {
				$tc_restricted_types[] = 'sfwd-courses';
			}
		}

		foreach ( $tc_restricted_types as $post_type ) {
			$this->set_metabox( $post_type );
		}
	}

	/**
	 * Set Metabox
	 *
	 * @param  string $post_type The current post type.
	 *
	 * @since  1.0.0
	 */
	private function set_metabox( $post_type ) {
		$restricted_types = array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-courses' );

		$option = get_option( SnC_TEXTDOMAIN );

		if ( ! $option ) {
			$nonce_protection = '1';
		} elseif ( ! isset( $option['nonceProtection'] ) ) {
			$nonce_protection = '1';
		} else {
			$nonce_protection = $option['nonceProtection'];
		}
		// If "Capture Tin Can and SCORM data" is disabled
		$is_capture_enabled = get_option( 'show_tincan_reporting_tables', 'yes' );
		if ( $is_capture_enabled == 'no' && '1' !== $nonce_protection ) {
			return;
		}
		$args = array(
			'public'   => true,
			'_builtin' => true,
		);

		$output     = 'names';
		$operator   = 'or';
		$post_types = get_post_types( $args, $output, $operator );
		if ( in_array( $post_type, $restricted_types, true ) || ( '1' === $nonce_protection && in_array( $post_type, $post_types, true ) ) ) {
			add_meta_box(
				'tincanny_settings',
				__( 'Tin Canny Settings', 'uncanny-learndash-reporting' ),
				array( $this, 'render_metabox' ),
				$post_type,
				'normal',
				'high',
				array( 'post_type' => $post_type )
			);
		}
	}

	/**
	 * Render the metabox
	 */
	public function render_metabox( $post, $metabox ) {
		wp_nonce_field( 'tincanny_settings_save', 'tincanny_settings_nonce' );

		$restricted_types = array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-courses' );

		if ( in_array( $post->post_type, $restricted_types ) ) {
			$this->render_restricted_type_fields( $post );
		}

		$this->render_protection_fields( $post );
	}

	/**
	 * @param $post
	 *
	 * @return void
	 */
	private function render_restricted_type_fields( $post ) {
		$mark_complete        = get_post_meta( $post->ID, 'uo_tc_restrict_mark_complete', true );
		$completion_condition = get_post_meta( $post->ID, 'uo_tc_completion_condition', true );
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="restrict_mark_complete"><?php _e( 'Restrict Mark Complete', 'uncanny-learndash-reporting' ); ?></label>
				</th>
				<td>
					<select name="restrict_mark_complete" id="restrict_mark_complete" class="regular-text">
						<option value="Use Global Setting" <?php selected( $mark_complete, 'Use Global Setting' ); ?>><?php _e( 'Use Global Setting', 'uncanny-learndash-reporting' ); ?></option>
						<option value="no" <?php selected( $mark_complete, 'no' ); ?>><?php _e( 'Always enabled', 'uncanny-learndash-reporting' ); ?></option>
						<option value="yes" <?php selected( $mark_complete, 'yes' ); ?>><?php _e( 'Disabled until complete', 'uncanny-learndash-reporting' ); ?></option>
						<option value="hide" <?php selected( $mark_complete, 'hide' ); ?>><?php _e( 'Hidden until complete', 'uncanny-learndash-reporting' ); ?></option>
						<option value="remove" <?php selected( $mark_complete, 'remove' ); ?>><?php _e( 'Hidden and autocomplete', 'uncanny-learndash-reporting' ); ?></option>
						<option value="autoadvance" <?php selected( $mark_complete, 'autoadvance' ); ?>><?php _e( 'Hidden and autoadvance', 'uncanny-learndash-reporting' ); ?></option>
					</select>
					<p class="description"><?php _e( 'Choose whether or not the Mark Complete button will be disabled until users complete all Tin Can modules on the page', 'uncanny-learndash-reporting' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="completion_condition"><?php _e( 'Completion Condition', 'uncanny-learndash-reporting' ); ?></label>
				</th>
				<td>
					<input type="text" name="completion_condition" id="completion_condition" class="regular-text" value="<?php echo esc_attr( $completion_condition ); ?>" />
					<p class="description"><?php _e( 'Comma separated Tin Canny verb(s). For result, you can enter the condition like <code>result > 80.</code>', 'uncanny-learndash-reporting' ); ?></p>
				</td>
			</tr>
		</tbody>
		</table>
		<?php
	}

	/**
	 * @param $post
	 *
	 * @return void
	 */
	private function render_protection_fields( $post ) {
		$protect_modules = get_post_meta( $post->ID, 'uo_tc_protect_modules', true );
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="protect_modules"><?php _e( 'Protect SCORM/Tin Can Modules?', 'uncanny-learndash-reporting' ); ?></label>
				</th>
				<td>
					<select name="protect_modules" id="protect_modules" class="regular-text">
						<option value="Use Global Setting" <?php selected( $protect_modules, 'Use Global Setting' ); ?>><?php _e( 'Use Global Setting', 'uncanny-learndash-reporting' ); ?></option>
						<option value="yes" <?php selected( $protect_modules, 'yes' ); ?>><?php _e( 'Yes', 'uncanny-learndash-reporting' ); ?></option>
						<option value="no" <?php selected( $protect_modules, 'no' ); ?>><?php _e( 'No', 'uncanny-learndash-reporting' ); ?></option>
					</select>
				</td>
			</tr>
		</tbody>
		</table>
		<?php
	}

	/**
	 * Save metabox data
	 */
	public function save_metabox( $post_id ) {
		if ( ! isset( $_POST['tincanny_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['tincanny_settings_nonce'], 'tincanny_settings_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['restrict_mark_complete'] ) ) {
			update_post_meta( $post_id, 'uo_tc_restrict_mark_complete', sanitize_text_field( $_POST['restrict_mark_complete'] ) );
		}

		if ( isset( $_POST['completion_condition'] ) ) {
			update_post_meta( $post_id, 'uo_tc_completion_condition', sanitize_text_field( $_POST['completion_condition'] ) );
		}

		if ( isset( $_POST['protect_modules'] ) ) {
			update_post_meta( $post_id, 'uo_tc_protect_modules', sanitize_text_field( $_POST['protect_modules'] ) );
		}
	}

	/**
	 * Migrate old meta keys to new meta keys
	 */
	public function migrate_meta_keys() {
		global $wpdb;

		if ( ! empty( get_option( 'uo_tc_WE_meta_migrated_171' ) ) ) {
			return;
		}

		// Get all post IDs and meta values where meta_key is '_WE-meta_'
		$results = $wpdb->get_results(
		"
		SELECT post_id, meta_value
		FROM {$wpdb->postmeta}
		WHERE meta_key = '_WE-meta_'
		"
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				$post_id    = $row->post_id;
				$meta_value = maybe_unserialize( $row->meta_value );

				if ( isset( $meta_value['restrict-mark-complete'] ) && empty( get_post_meta( $post_id, 'uo_tc_restrict_mark_complete', true ) ) ) {
					update_post_meta( $post_id, 'uo_tc_restrict_mark_complete', sanitize_text_field( $meta_value['restrict-mark-complete'] ) );
				}

				if ( isset( $meta_value['completion-condition'] ) && empty( get_post_meta( $post_id, 'uo_tc_completion_condition', true ) ) ) {
					update_post_meta( $post_id, 'uo_tc_completion_condition', sanitize_text_field( $meta_value['completion-condition'] ) );
				}

				if ( isset( $meta_value['protect-scorm-tin-can-modules'] ) && empty( get_post_meta( $post_id, 'uo_tc_protect_modules', true ) ) ) {
					update_post_meta( $post_id, 'uo_tc_protect_modules', sanitize_text_field( $meta_value['protect-scorm-tin-can-modules'] ) );
				}

				// Optionally delete the old meta key
				// delete_post_meta( $post_id, '_WE-meta_' );
			}
		}

		add_option( 'uo_tc_WE_meta_migrated_171', time(), null, 'yes' );
	}
}
