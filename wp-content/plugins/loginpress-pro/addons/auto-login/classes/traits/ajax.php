<?php
/**
 * LoginPress AutoLogin AJAX Trait.
 *
 * Contains AJAX functionality for auto-login features.
 *
 * @package LoginPress
 * @category Core
 * @author WPBrigade
 * @since 6.1.0
 */

if ( ! trait_exists( 'LoginPress_AutoLogin_Ajax_Trait' ) ) {

	/**
	 * LoginPress AutoLogin AJAX Trait.
	 *
	 * @since 3.0.0
	 * @version 6.1.0
	 */
	trait LoginPress_AutoLogin_Ajax_Trait {

		/**
		 * Ajax function that update the user meta after creating autologin code.
		 *
		 * @since   1.0.0
		 * @version 6.1.2
		 * @return void
		 */
		public function autologin_update_user_meta() {

			check_ajax_referer( 'loginpress_autocomplete_search_nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! isset( $_POST['code'] ) || ! isset( $_POST['id'] ) ) {
				return;
			} else {
				$loginpress_code = sanitize_text_field( wp_unslash( $_POST['code'] ) );
				$user_id         = absint( $_POST['id'] );
			}
			$date            = gmdate( 'Y-m-d' );
			$default_date    = gmdate( 'Y-m-d', strtotime( "$date +7 day" ) ); // PHP:  yy-mm-dd.
			$default_expire  = apply_filters( 'loginpress_autologin_default_expiration', $default_date );
			$meta            = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			$emails          = isset( $meta['emails'] ) && ! empty( $meta['emails'] ) ? $meta['emails'] : '';
			$expire          = isset( $meta['expire'] ) && ! empty( $meta['expire'] ) ? $meta['expire'] : 'unchecked';
			$link_count      = isset( $meta['link_count'] ) && ! empty( $meta['link_count'] ) ? $meta['link_count'] : 10;
			$unlimited_click = isset( $meta['unlimited_click'] ) && ! empty( $meta['unlimited_click'] ) ? $meta['unlimited_click'] : 'checked';

			$update_elements = array(
				'state'           => sanitize_text_field( 'enable' ),
				'emails'          => sanitize_text_field( $emails ),
				'code'            => sanitize_text_field( $loginpress_code ),
				'expire'          => 'unchecked', // Reset expire status when creating new link.
				'duration'        => sanitize_text_field( $default_expire ),
				'link_count'      => sanitize_text_field( $link_count ), // Preserve link count settings.
				'unlimited_click' => sanitize_text_field( $unlimited_click ), // Preserve unlimited click setting.
				'link_click'      => 0, // Reset click counter for new link.
			);

			update_user_meta( $user_id, 'loginpress_autologin_user', $update_elements );
			$meta = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			echo esc_url( home_url() . '/?loginpress_code=' . $meta['code'] );
			wp_die();
		}

		/**
		 * Ajax function that emails user for autologin.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_autologin_emailuser() {

			check_ajax_referer( 'loginpress-user-autologin-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}

			$user_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
			if ( empty( $user_id ) && ( ! isset( $_POST['code'] ) || empty( $_POST['code'] ) ) ) {
				return;
			}

			$user   = get_userdata( $user_id );
			$meta   = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			$emails = $user->user_email;
			$code   = home_url() . '/?loginpress_code=' . $meta['code'];

			$blog_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$user_name = isset( $user->first_name ) && ! empty( $user->first_name ) ? $user->first_name : $user->display_name;

			$allowed_html = LoginPress_AutoLogin_Utilities::get_allowed_html();

			/* Translators: The Autologin multi-user email */
			$message  = esc_html__( 'Following is the Auto Login link details for the Blog ', 'loginpress-pro' );
			$message .= ucwords( $blog_name ) . "\n\n";
			$message .= esc_html__( 'User Name: ', 'loginpress-pro' ) . ucwords( $user_name ) . "\n\n";
			$message .= esc_html__( 'User Role: ', 'loginpress-pro' ) . ucwords( implode( ', ', $user->roles ) ) . "\n\n";
			$message .= esc_html__( 'Autologin Link: ', 'loginpress-pro' );
			$message .= esc_url_raw( $code );
			$mail     = wp_mail(
				$emails,
				/* translators: Blog name. */
				esc_html( apply_filters( 'loginpress_autologin_email_subject', sprintf( esc_html_x( '[%s] Auto Login Link', 'Blogname', 'loginpress-pro' ), $blog_name ) ) ),
				wp_kses( apply_filters( 'loginpress_autologin_email_msg', $message, $blog_name, $user_name, $code ), $allowed_html )
			);

			wp_die();
		}

		/**
		 * Ajax function that delete the user meta after click on delete user autologin button.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function autologin_delete_user_meta() {

			check_ajax_referer( 'loginpress-user-autologin-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! isset( $_POST['id'] ) ) {
				return;
			}
			$user_id = absint( $_POST['id'] );
			delete_user_meta( $user_id, 'loginpress_autologin_user' );

			echo esc_html__( 'deleted', 'loginpress-pro' );
		}

		/**
		 * Change the State of the autologin.
		 *
		 * @since 3.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_change_autologin_state() {

			check_ajax_referer( 'loginpress-user-autologin-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! isset( $_POST['state'] ) || ! isset( $_POST['id'] ) ) {
				return;
			}

			$user_id = absint( $_POST['id'] );
			$state   = isset( $_POST['state'] ) ? esc_attr( wp_strip_all_tags( sanitize_text_field( wp_unslash( $_POST['state'] ) ) ) ) : '';
			$meta    = get_user_meta( $user_id, 'loginpress_autologin_user', true );

			if ( 'disable' === $state ) {
				update_user_meta(
					$user_id,
					'loginpress_autologin_user',
					array_merge(
						$meta,
						array(
							'state' => 'disable',
						)
					)
				);

			} else {
				update_user_meta(
					$user_id,
					'loginpress_autologin_user',
					array_merge(
						$meta,
						array(
							'state' => 'enable',
						)
					)
				);

			}

			wp_die();
		}

		/**
		 * Ajax callback populate popup data for expire duration.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_populate_popup_duration() {

			check_ajax_referer( 'loginpress-user-autologin-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! isset( $_POST['id'] ) ) {
				return;
			}
			$user_id    = absint( $_POST['id'] );
			$meta       = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			$is_expire  = isset( $meta['expire'] ) && ( 'unchecked' === $meta['expire'] ) ? '' : 'checked';
			$expiration = isset( $meta['duration'] ) && ( ! empty( $meta['duration'] ) ) ? sanitize_text_field( $meta['duration'] ) : gmdate( 'Y-m-d' );

			$display_date = empty( $is_expire ) ? 'block' : 'none';

			$return = '
			<div class="loginpress-edit-popup loginpress-link-duration-popup">
				<div class="autologin-popup-fields autologin-expire-date-container" style="display:' . esc_attr( $display_date ) . '">
					<label for="autologin-expire-date">' . esc_html__( 'Expiration Date', 'loginpress-pro' ) . '</label>
					<p class="autologin-expire-date_desc">' . esc_html__( 'This Auto Login link will expire on ', 'loginpress-pro' ) . esc_html( $expiration ) . '</p>
					<input type="date" id="autologin-expire-date" class="autologin-expire-date" min="' . esc_attr( gmdate( 'Y-m-d' ) ) . '" value="' . esc_attr( $expiration ) . '" max="2050-01-01" />
				</div>
				<div class="autologin-popup-fields">
					<label for="autologin-never-expire">' . esc_html__( 'Never Expire', 'loginpress-pro' ) . '</label>
					<input id="autologin-never-expire" class="autologin-never-expire" type="checkbox" value="void" ' . esc_attr( $is_expire ) . ' />
				</div>
				<div class="loginpress-auto-login-duration-buttons">
					<button class="button button-primary autologin-close-popup">' . esc_html__( 'Close', 'loginpress-pro' ) . '</button>
					<button class="button button-primary autologin-save-duration">' . esc_html__( 'Done', 'loginpress-pro' ) . '</button>
				</div>
			</div>';

			echo $return; // @codingStandardsIgnoreLine.
			wp_die();
		}

		/**
		 * Ajax callback populate popup data for email to Multiple Users.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_populate_popup_email() {

			check_ajax_referer( 'loginpress-user-autologin-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! isset( $_POST['id'] ) ) {
				return;
			}
			$user_id = absint( $_POST['id'] );
			$meta    = get_user_meta( $user_id, 'loginpress_autologin_user', true );

			$return = '
			<div class="loginpress-edit-popup loginpress-multiple-users-popup">
				<div class="autologin-popup-fields">
					<label for="autologin-emails">' . esc_html__( ' Email Addresses', 'loginpress-pro' ) . ' </label>
					<p class="autologin-multi-emails-desc">' . esc_html__( 'Send Auto Login To Multiple Users', 'loginpress-pro' ) . '</p>
					<input type="text" id="autologin-emails" value="' . esc_attr( $meta['emails'] ) . '" placeholder="' . esc_attr( __( 'Email Address with Commas', 'loginpress-pro' ) ) . '">
					<p class="autologin-multi-emails-desc">' . esc_html__( 'Use comma ( , ) to add more than 1 recipients.', 'loginpress-pro' ) . '</p>
					<p class="autologin_emails_sent">' . esc_html__( 'An Email Has Been Sent', 'loginpress-pro' ) . '</p>
					<p class="loginpress_valid_email">' . esc_html__( 'One of your email is not valid', 'loginpress-pro' ) . ' </p>
					<p class="loginpress_empty_email">' . esc_html__( 'Kindly Enter Recipient Email', 'loginpress-pro' ) . ' </p>
				</div>
				<div class="loginpress-auto-login-duration-buttons">
					<button class="button button-primary autologin-close-popup">' . esc_html__( 'Close', 'loginpress-pro' ) . '</button>
					<button class="button button-primary autologin-save-emails">' . esc_html__( 'Done', 'loginpress-pro' ) . '</button>
				</div>
			</div>';

			echo $return; // @codingStandardsIgnoreLine.
			wp_die();
		}

		/**
		 * Ajax callback populate popup data for Link Count.
		 *
		 * @since   4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_populate_link_count() {

			check_ajax_referer( 'loginpress-user-autologin-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! isset( $_POST['id'] ) ) {
				return;
			}

			$user_id         = absint( $_POST['id'] );
			$meta            = get_user_meta( $user_id, 'loginpress_autologin_user', true );
			$link_count      = isset( $meta['link_count'] ) && ( ! empty( $meta['link_count'] ) ) ? sanitize_text_field( $meta['link_count'] ) : 10;
			$unlimited_click = isset( $meta['unlimited_click'] ) && ( 'unchecked' === $meta['unlimited_click'] ) ? '' : 'checked';
			$display_clicks  = empty( $unlimited_click ) ? 'block' : 'none';
			$return          = '
			<div class="loginpress-edit-popup loginpress-link-count-popup">
				<div class="autologin-popup-fields loginpress-link-count-container" style="display:' . esc_attr( $display_clicks ) . '">
					<label for="autologin-linkcount">' . esc_html__( ' Link Count', 'loginpress-pro' ) . ' </label>
					<p class="autologin-link-count-desc">' . esc_html__( 'This is the number of times the link can be used.', 'loginpress-pro' ) . '</p>
					<input type="text" id="autologin-linkcount" value="' . esc_attr( $link_count ) . '" placeholder="' . esc_attr( __( 'Enter Link Count', 'loginpress-pro' ) ) . '">
					<p class="autologin-link-count-desc">' . esc_html__( 'Enter the number.', 'loginpress-pro' ) . '</p>
					<p class="loginpress_empty_link_count">' . esc_html__( 'Kindly Enter Positive Number greater than 0', 'loginpress-pro' ) . ' </p>
				</div>
				<div class="autologin-popup-fields">
					<label for="autologin-unlimited-click">' . esc_html__( 'Unlimited Clicks', 'loginpress-pro' ) . '</label>
					<input id="autologin-unlimited-click" class="autologin-unlimited-click" type="checkbox" value="void" ' . esc_attr( $unlimited_click ) . ' />

				</div>
				<div class="loginpress-auto-login-duration-buttons">
					<button class="button button-primary autologin-close-popup">' . esc_html__( 'Close', 'loginpress-pro' ) . '</button>
					<button class="button button-primary autologin-save-link-count">' . esc_html__( 'Done', 'loginpress-pro' ) . '</button>
				</div>
			</div>';

			echo $return; // @codingStandardsIgnoreLine.
			wp_die();
		}

		/**
		 * Ajax callback update expire duration.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_update_duration() {

			check_ajax_referer( 'loginpress-autologin-popup-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! isset( $_POST['id'] ) || ! isset( $_POST['never_expire'] ) || ! isset( $_POST['expire_duration'] ) ) {
				return;
			}
			$user_id         = absint( $_POST['id'] );
			$never_expire    = sanitize_text_field( wp_unslash( $_POST['never_expire'] ) );
			$expire_duration = sanitize_text_field( wp_unslash( $_POST['expire_duration'] ) );
			$meta            = get_user_meta( $user_id, 'loginpress_autologin_user', true );

			update_user_meta(
				$user_id,
				'loginpress_autologin_user',
				array_merge(
					$meta,
					array(
						'expire'   => $never_expire,
						'duration' => $expire_duration,
					)
				)
			);

			esc_html( $expire_duration );

			wp_die();
		}

		/**
		 * Ajax callback update user emails.
		 *
		 * @since   1.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_update_email() {

			check_ajax_referer( 'loginpress-autologin-popup-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating, huh!' );
			}
			if ( ! empty( $_POST['id'] ) && ! empty( $_POST['emails'] ) ) {
				$user_id = absint( $_POST['id'] );
				$emails  = sanitize_text_field( wp_unslash( $_POST['emails'] ) );
				$meta    = get_user_meta( $user_id, 'loginpress_autologin_user', true );

				update_user_meta(
					$user_id,
					'loginpress_autologin_user',
					array_merge(
						$meta,
						array(
							'emails' => $emails,
						)
					)
				);

				$this->loginpress_autologin_multiusers_email( $user_id );
			}
			wp_die();
		}

		/**
		 * Ajax callback to update link count.
		 *
		 * @since 4.0.0
		 * @version 6.1.0
		 * @return void
		 */
		public function loginpress_update_link_count() {

			check_ajax_referer( 'loginpress-autologin-popup-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'No cheating' );
			}

			if ( ! isset( $_POST['id'] ) || ! isset( $_POST['link_count'] ) ) {
				return;
			}

			$user_id         = absint( $_POST['id'] );
			$link_count      = absint( $_POST['link_count'] );
			$link_click      = isset( $_POST['link_click'] ) ? absint( $_POST['link_click'] ) : 0;
			$unlimited_click = isset( $_POST['unlimited'] ) ? sanitize_text_field( wp_unslash( $_POST['unlimited'] ) ) : '';
			$meta            = get_user_meta( $user_id, 'loginpress_autologin_user', true );

			update_user_meta(
				$user_id,
				'loginpress_autologin_user',
				array_merge(
					$meta,
					array(
						'link_count'      => $link_count,
						'link_click'      => $link_click,
						'unlimited_click' => $unlimited_click,
					)
				)
			);
			wp_die();
		}
	}
}
