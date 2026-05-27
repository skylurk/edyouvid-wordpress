<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Group Expiration Class
 *
 * @package uncanny_pro_toolkit
 */

namespace uncanny_pro_toolkit;

use uncanny_learndash_toolkit as toolkit;

defined( 'WPINC' ) || exit;

/**
 * Class learnDashGroupExpiration
 */
class learnDashGroupExpiration extends toolkit\Config implements toolkit\RequiredFunctions {

	/**
	 * Whether to send email
	 *
	 * @var string
	 */
	private static $send_email;

	/**
	 * Whether to send emails to group leaders only.
	 *
	 * @var string
	 */
	private static $send_email_group_leader_only;

	/**
	 * No. of days before expiry to send emails.
	 *
	 * @var string
	 */
	private static $send_email_before_days;


	/**
	 * Site name.
	 *
	 * @var string
	 */
	private static $site_name;

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action(
			'plugins_loaded',
			array(
				__CLASS__,
				'run_frontend_hooks',
			)
		);
		add_action( 'init', array( __CLASS__, 'activate' ) );
		self::$send_email                   = self::get_settings_value( 'uncanny-group-expiry-send-email', __CLASS__ );
		self::$send_email_group_leader_only = self::get_settings_value( 'uncanny-group-expiry-send-email-group-leader-only', __CLASS__ );
		self::$send_email_before_days       = self::get_settings_value( 'uncanny-group-expiry-send-email-days', __CLASS__, 7 );

		self::$site_name = get_bloginfo( 'name' );
	}

	/*
	 * Initialize frontend actions and filters
	 */
	public static function run_frontend_hooks() {

		if ( true === self::dependants_exist() ) {
			add_shortcode(
				'uo_group_expiration_in',
				array(
					__CLASS__,
					'group_expiration_in',
				)
			);

			/* ADD FILTERS ACTIONS FUNCTION */

			// DatePicker (JS).
			add_action(
				'admin_enqueue_scripts',
				array(
					__CLASS__,
					'enqueue_date_picker',
				)
			);

			// Metabox.
			add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
			add_action( 'save_post', array( __CLASS__, 'save_post' ), 100 );

			// Expire Group.
			add_action( 'uo-expire-group', array( __CLASS__, 'expire_group' ) );

			// Notification E-mail.
			add_action( 'uo-email-group', array( __CLASS__, 'email_group' ) );

			// List.
			add_filter(
				'manage_groups_posts_columns',
				array(
					__CLASS__,
					'tweak_columns',
				),
				20
			);
			add_filter(
				'manage_edit-groups_sortable_columns',
				array(
					__CLASS__,
					'tweak_sortable_columns',
				),
				20
			);

			$expiry_settings = self::get_settings_value( 'uncanny-group-expiry-delete-expired-groups', __CLASS__, 'off' );
			if ( 'on' === $expiry_settings ) {
				//Schedule an action if it's not already scheduled
				if ( ! wp_next_scheduled( 'uo_delete_expired_groups' ) ) {
					wp_schedule_event( time(), 'daily', 'uo_delete_expired_groups' );
				}

				add_action(
					'uo_delete_expired_groups',
					array(
						__CLASS__,
						'uo_delete_expired_groups',
					)
				);
			} else {
				if ( wp_get_schedule( 'uo_delete_expired_groups' ) ) {
					wp_clear_scheduled_hook( 'uo_delete_expired_groups' );
				}
			}

			add_action(
				'manage_groups_posts_custom_column',
				array(
					__CLASS__,
					'tweak_columns_content',
				),
				10,
				2
			);
			add_action(
				'pre_get_posts',
				array(
					__CLASS__,
					'exp_date_orderby',
				)
			);

			// Email Test.
			add_action(
				'wp_ajax_UOLDGE_Email_Test',
				array(
					__CLASS__,
					'email_test',
				)
			);

			// shortcode.
			add_shortcode(
				'uo_group_expiration_date',
				array(
					__CLASS__,
					'uo_group_expiration_date',
				)
			);

			// Fix to override course expiry with group expiry
			add_filter(
				'ld_course_access_expires_on',
				array(
					__CLASS__,
					'ld_course_access_expires_on_func',
				),
				99,
				3
			);
		}

	}


	/**
	 * @return void
	 */
	public static function uo_delete_expired_groups() {
		$args = array(
			'post_type'      => 'groups',
			'post_status'    => 'any',
			'posts_per_page' => '-1',
			'meta_query'     => array(
				array(
					'key'     => 'uo-expiration-date-expired',
					'compare' => 'EXISTS',
				),
			),
		);

		$all_groups = new \WP_Query( $args );
		$today      = time();
		$older      = get_option( 'uncanny-group-expiry-expire-groups-older-days', 365 );

		if ( $all_groups->have_posts() ) :
			while ( $all_groups->have_posts() ) :
				$all_groups->the_post();
				$expiry_date = get_post_meta( get_the_ID(), 'uo-expiration-date-expired', true );
				$diff        = $today - ( $older * 86400 );
				if ( $today > strtotime( $expiry_date ) && $diff >= $older ) {
					wp_trash_post( get_the_ID() );
				}
			endwhile;
		endif;
	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * @return boolean || string Return either true or name of function or
	 *     plugin
	 */
	public static function dependants_exist() {

		/* Checks for LearnDash */ global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		// Return true if no dependency or dependency is available.
		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {
		$module_id = 'group-expiration';

		$class_title = esc_html__( 'Group Expiration', 'uncanny-pro-toolkit' );

		$kb_link = 'http://www.uncannyowl.com/knowledge-base/learndash-group-expiration/';

		/* Sample Simple Description with shortcode */
		$class_description = esc_html__( 'Set expiration dates for LearnDash groups so that course enrolment for the group is removed on the specified day. Reminder emails can be sent to users advising them of group expiration.', 'uncanny-pro-toolkit' );

		/* Icon as fontawesome icon */
		$class_icon = '<i class="uo_icon_pro_fa uo_icon_fa fa fa-calendar-times-o"></i><span class="uo_pro_text">PRO</span>';

		$category = 'learndash';
		$type     = 'pro';

		return array(
			'id'               => $module_id,
			'title'            => $class_title,
			'type'             => $type,
			'category'         => $category,
			'kb_link'          => $kb_link,
			// OR set as null not to display
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @param string $class_title Class Name.
	 *
	 * @return array|string Return either false or settings html modal
	 */
	public static function get_class_settings( $class_title ) {

		// Create options.
		$options = array(
			array(
				'type'       => 'radio',
				'label'      => esc_html__( 'Send Expiry Email', 'uncanny-pro-toolkit' ),
				'radio_name' => 'uncanny-group-expiry-send-email',
				'radios'     => array(
					array(
						'value' => 'yes',
						'text'  => 'Yes',
					),
					array(
						'value' => 'no',
						'text'  => 'No',
					),
				),
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Send expiry email to Group Leader only', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-group-expiry-send-email-group-leader-only',
			),
			array(
				'type'        => 'text',
				'label'       => 'Send email ____ days before expiration',
				'option_name' => 'uncanny-group-expiry-send-email-days',
			),
			array(
				'type'       => 'html',
				'label'      => '',
				'inner_html' => '<hr /><h4>Countdown shortcode settings</h4>',
			),
			array(
				'type'        => 'text',
				'label'       => esc_attr__( 'Text displayed when one day remaining', 'uncanny-pro-toolkit' ),
				'placeholder' => esc_attr__( 'Group access expires in 1 day', 'uncanny-pro-toolkit' ),
				'option_name' => 'group_days_expiry_text_singular',
			),
			array(
				'type'        => 'text',
				'label'       => esc_attr__( 'Text displayed when multiple days remaining', 'uncanny-pro-toolkit' ),
				'placeholder' => esc_attr__( 'Group access expires in %%days%% days', 'uncanny-pro-toolkit' ),
				'description' => esc_attr__( 'Use the token %%days%% to output the number of days remaining.', 'uncanny-pro-toolkit' ),
				'option_name' => 'group_days_expiry_text_plural',
			),
			array(
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Trash groups after the expiry date', 'uncanny-pro-toolkit' ),
				'option_name' => 'uncanny-group-expiry-delete-expired-groups',
			),
			array(
				'type'        => 'text',
				'label'       => 'Trash groups ____ days after expiration',
				'option_name' => 'uncanny-group-expiry-expire-groups-older-days',
				'default'     => 365,
			),
		);

		// Build html.
		$html = self::settings_output(
			array(
				'class'   => __CLASS__,
				'title'   => $class_title,
				'options' => $options,
			)
		);

		return $html;
	}

	/**
	 * Create new role on activation.
	 */
	public static function activate() {
		add_role(
			'archived',
			__( 'Archived', 'uncanny-pro-toolkit' ),
			array(
				'read' => true,
			)
		);
	}

	/**
	 * Send test email
	 */
	public static function email_test() {
		$group_id = filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );

		if ( empty( $group_id ) ) {
			echo esc_html__( 'Group ID is not set.', 'uncanny-pro-toolkit' );
			die();
		}

		$admin_email     = get_option( 'admin_email' );
		$blog_name       = get_bloginfo( 'name' );
		$email_title     = self::get_email_title( $group_id );
		$email_body      = self::get_email_body( $group_id );
		$expiration_date = get_post_meta( $group_id, 'uo-expiration-date', true );
		$group_name      = get_the_title( $group_id );

		$email_body = str_ireplace(
			array(
				'%LearnDash Group Name%',
				'%expiration date%',
			),
			array(
				$group_name,
				$expiration_date,
			),
			$email_body
		);

		$user      = wp_get_current_user();
		$user_name = $user->display_name;
		$headers   = array();
		$headers[] = 'Content-type: text/html; charset=UTF-8';
		$headers[] = "From: {$blog_name} <{$admin_email}>";

		$message = str_ireplace( '%display name%', $user_name, $email_body );
		$message = nl2br( stripcslashes( $message ) );
		$message = wpautop( $message );
		$subject = 'Test - ' . $email_title;
		$result  = wp_mail( $user->user_email, $subject, $message, $headers );
		if ( $result ) {
			echo esc_html__( 'Test message successfully sent to ', 'uncanny-pro-toolkit' ) . $user->user_email . '.';
		} else {
			echo esc_html__( 'Failed!', 'uncanny-pro-toolkit' );
		}
		die();
	}

	/**
	 * Modify table column.
	 *
	 * @param array $columns Default columns.
	 *
	 * @return mixed
	 */
	public static function tweak_columns( $columns ) {
		$columns['exp_date'] = esc_html__( 'Expiration Date', 'uncanny-pro-toolkit' );

		return $columns;
	}

	/**
	 * Modify sortable columns.
	 *
	 * @param array $columns Default sortable columns.
	 *
	 * @return mixed
	 */
	public static function tweak_sortable_columns( $columns ) {
		$columns['exp_date'] = 'exp_date';

		return $columns;
	}

	/**
	 * Modify row content for custom expiration column.
	 *
	 * @param string $column Column identifier.
	 * @param int $post_id Post ID of Group.
	 */
	public static function tweak_columns_content( $column, $post_id ) {
		if ( 'exp_date' === $column ) {
			self::print_expiry_email_info( $post_id );
		}
	}

	/**
	 * Render expiry email settings.
	 *
	 * @param int $post_id Current group's post ID.
	 */
	private static function print_expiry_email_info( $post_id ) {
		$system_time = time();
		$wp_time     = current_time( 'timestamp' );
		$offset      = $system_time - $wp_time;

		$expire_schedule = wp_next_scheduled( 'uo-expire-group', array( (int) $post_id ) );
		$email_schedule  = wp_next_scheduled( 'uo-expiration-date', array( (int) $post_id ) );

		$expire_schedule = ( $expire_schedule ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expire_schedule - $offset ) : false;
		$email_schedule  = ( $email_schedule ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $email_schedule - $offset ) : false;

		$expired_date = get_post_meta( $post_id, 'uo-expiration-date-expired', true );
		$emailed_date = get_post_meta( $post_id, 'uo-email-group-sent', true );

		$message = array();

		if ( $expired_date ) {
			$__expired_on = date_i18n( get_option( 'date_format' ), strtotime( $expired_date ) );
			$__expired_on = '<code>' . esc_attr( $__expired_on ) . '</code>';
			$string       = sprintf( // translators: date string.
				__( 'Access Expired on %s', 'uncanny-pro-toolkit' ),
				$__expired_on
			);

		} elseif ( $expire_schedule ) {
			$__scheduled_on = '<code>' . esc_attr( $expire_schedule ) . '</code>';
			$string         = sprintf( // translators: date string.
				__( 'Access Expires on %s', 'uncanny-pro-toolkit' ),
				$__scheduled_on
			);
		}

		if ( ! empty( $string ) ) {
			?>
			<p><?php echo wp_kses( $string, array( 'code' => array() ) ); ?></p>
			<?php
		}

		$string = '';

		if ( $emailed_date ) {
			$__mailed_on = date_i18n( get_option( 'date_format' ), strtotime( $emailed_date ) );
			$__mailed_on = '<code>' . esc_attr( $__mailed_on ) . '</code>';
			$string      = sprintf( // translators: date string.
				esc_attr__( 'Email sent on %s', 'uncanny-pro-toolkit' ),
				$__mailed_on
			);
		} elseif ( $email_schedule ) {
			$__mail_on = '<code>' . $email_schedule . '</code>';
			$string    = sprintf( // translators: date string.
				esc_attr__( 'Email scheduled on %s', 'uncanny-pro-toolkit' ),
				$__mail_on
			);
		}

		if ( ! empty( $string ) ) {
			?>
			<p>
				<?php echo wp_kses( $string, array( 'code' => array() ) ); ?>
			</p>
			<?php
		}
	}

	/**
	 * Set orderby parameter of expire query.
	 *
	 * @param WP_Query $query The current query.
	 */
	public static function exp_date_orderby( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'exp_date' === $orderby ) {
			$query->set( 'meta_key', 'uo-expiration-date' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Enqueue Date Picker
	 */
	public static function enqueue_date_picker() {
		global $pagenow, $wp_styles;

		if ( ( 'post-new.php' === $pagenow && filter_has_var( INPUT_GET, 'post_type' ) && 'groups' === filter_input( INPUT_GET, 'post_type' ) ) || ( 'post.php' === $pagenow && filter_has_var( INPUT_GET, 'action' ) && 'edit' === filter_input( INPUT_GET, 'action' ) ) ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-datepicker-ext', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css' );
		}
	}

	/**
	 * Add metaboxes.
	 */
	public static function add_meta_box() {
		add_meta_box(
			'expiration_date',
			__( 'Expiration Date', 'uncanny-pro-toolkit' ),
			array(
				__CLASS__,
				'expiration_date',
			),
			'groups'
		);
		if ( 'yes' === self::$send_email ) {
			add_meta_box(
				'expiration_email',
				__( 'Expiration Email', 'uncanny-pro-toolkit' ),
				array(
					__CLASS__,
					'expiration_email',
				),
				'groups'
			);
		}
	}

	/**
	 * Setup expiration date.
	 *
	 * @param WP_Post $post Group Post object.
	 */
	public static function expiration_date( $post ) {

		$test_checked = get_post_meta( $post->ID, 'uo-is-test', true ) ? 'checked="checked"' : '';
		$exp_date     = get_post_meta( $post->ID, 'uo-expiration-date', true );
		if ( ! empty( $exp_date ) ) {
			$exp_date = date( 'm/d/Y', strtotime( $exp_date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_dat
		} else {
			$exp_date = '';
		}
		?>
		<p style="clear:left"><input type="text" id="exp-date" name="exp-date" readonly
									 value="<?php echo esc_attr( $exp_date ); ?>"/><br/>
			<?php esc_html_e( 'Enter the dates in MM/DD/YYYY format.', 'uncanny-pro-toolkit' ); ?></p>
		<?php
		self::print_expiry_email_info( $post->ID );

		?>
		<script>
			jQuery(document).ready(function () {
				jQuery('#exp-date').datepicker({
					dateFormat: 'mm/dd/yy'
				}).on('keyup', function(e) {
				    if( 8 === e.which || 46 === e.which ) {
				        jQuery('#exp-date')
				    	.datepicker('setDate', null)
				    	.datepicker("refresh");
				    }
				});
			})
		</script>
		<?php
	}

	/**
	 * Saves post
	 *
	 * @param int $post_id Post ID of group.
	 */
	public static function save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post_type = filter_input( INPUT_POST, 'post_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $post_type ) ) {
			return;
		}

		# Test Mode
		$is_test = filter_input( INPUT_POST, 'is_test' );

		if ( ! empty( $is_test ) ) {
			update_post_meta( $post_id, 'uo-is-test', $is_test );
		}
		$email_title = filter_input( INPUT_POST, 'uc_exp_email_title' );
		$email_title = empty( $email_title ) ? '' : $email_title;
		$email_body  = filter_input( INPUT_POST, 'uc_exp_email_body' );
		$email_body  = empty( $email_body ) ? '' : $email_body;

		if ( 'yes' === self::$send_email ) {

			update_post_meta( $post_id, 'uo-expiration-email-title', $email_title );
			update_post_meta( $post_id, 'uo-expiration-email-body', $email_body );
		}

		if ( learndash_group_enrolled_courses( $post_id ) ) {
			$user_ids = learndash_get_groups_user_ids( $post_id );

			foreach ( $user_ids as $user_id ) {
				$user = get_user_by( 'id', $user_id );

				if ( 'archived' === $user->roles[0] ) {
					wp_update_user(
						array(
							'ID'   => $user_id,
							'role' => 'subscriber',
						)
					);
				}
			}
		}

		$expiry_date = filter_input( INPUT_POST, 'exp-date' );

		// Not changed => End.
		if ( ! empty( $expiry_date ) && ( get_post_meta( $post_id, 'uo-expiration-date', true ) === $expiry_date ) && ! $is_test ) {
			return;
		}
		if ( empty( $expiry_date ) ) {
			delete_post_meta( $post_id, 'uo-expiration-date' );
			wp_clear_scheduled_hook( 'uo-expire-group', array( $post_id ) );
			wp_clear_scheduled_hook( 'uo-email-group', array( $post_id ) );
		} else {
			// Save.
			$exp_date = date( 'Y-m-d', strtotime( $expiry_date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			update_post_meta( $post_id, 'uo-expiration-date', $exp_date );

			// Reset Schedule.
			wp_clear_scheduled_hook( 'uo-expire-group', array( $post_id ) );
			wp_clear_scheduled_hook( 'uo-email-group', array( $post_id ) );

			if ( empty( $expiry_date ) ) {
				return;
			}

			# Milestones
			/*$system_time = current_time( 'timestamp' );
			$wp_time     = current_time( 'timestamp' );
			$offset      = $system_time - $wp_time;*/
			$local_dt = new \DateTime();
			$local_dt->setTimezone( wp_timezone() );
			$offset = $local_dt->getOffset();
			$offset = $offset * - 1;

			$timestamp = strtotime( $expiry_date . 'T00:00:00' ) + $offset;
			// Send expiry email 7 days before!
			$email = $timestamp - 60 * 60 * 24 * self::$send_email_before_days;

			$now = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

			if ( $is_test ) {
				wp_schedule_single_event( $now + 60, 'uo-expire-group', array( $post_id ) );
				delete_post_meta( $post_id, 'uo-expiration-date-expired' );

				if ( 'yes' === self::$send_email ) {
					wp_schedule_single_event( $now + 30, 'uo-email-group', array( $post_id ) );
					delete_post_meta( $post_id, 'uo-email-group-sent' );
				}
			} else {
				if ( $email && $now <= $email && 'yes' === self::$send_email ) {
					wp_schedule_single_event( $email, 'uo-email-group', array( $post_id ) );
					delete_post_meta( $post_id, 'uo-email-group-sent' );
				}

				if ( $timestamp && $now <= $timestamp ) {
					wp_schedule_single_event( $timestamp, 'uo-expire-group', array( $post_id ) );
					delete_post_meta( $post_id, 'uo-expiration-date-expired' );
				}
			}
		}
	}

	/**
	 * Save Group Settings.
	 *
	 * @param int $post_id Post ID of group.
	 */
	public static function expire_group( $post_id ) {
		if ( true === apply_filters( 'uo_ld_expire_group_remove_courses', true, $post_id ) ) {
			if ( function_exists( 'learndash_group_enrolled_courses' ) ) {
				$group_enrolled_courses = learndash_group_enrolled_courses( $post_id, true );

				if ( empty( $group_enrolled_courses ) ) {
					wp_clear_scheduled_hook( 'uo-expire-group', array( $post_id ) );
					wp_clear_scheduled_hook( 'uo-email-group', array( $post_id ) );

					return;
				}

				foreach ( $group_enrolled_courses as $course_id ) {
					ld_update_course_group_access( $course_id, $post_id, true );
				}
			}
		}
		update_post_meta( $post_id, 'uo-expiration-date-expired', current_time( get_option( 'date_format' ) . ' H:i:s' ) );

		wp_clear_scheduled_hook( 'uo-expire-group', array( $post_id ) );
		wp_clear_scheduled_hook( 'uo-email-group', array( $post_id ) );
	}

	/**
	 * Send Expiry email.
	 *
	 * @param int $group_id Post ID of group.
	 */
	public static function email_group( $group_id ) {
		if ( 'yes' === self::$send_email && function_exists( 'learndash_group_enrolled_courses' ) ) {
			if ( 'on' === self::$send_email_group_leader_only ) {
				$user_ids = learndash_get_groups_administrator_ids( $group_id );
				$user_ids = array_unique( $user_ids );
			} else {
				$user_ids = array_merge( learndash_get_groups_user_ids( $group_id ), learndash_get_groups_administrator_ids( $group_id ) );
				$user_ids = array_unique( $user_ids );
			}
			if ( $user_ids ) {

				$email_title     = self::get_email_title( $group_id );
				$email_body      = self::get_email_body( $group_id );
				$expiration_date = get_post_meta( $group_id, 'uo-expiration-date', true );
				$group_name      = get_the_title( $group_id );

				$email_body = str_ireplace(
					array(
						'%LearnDash Group Name%',
						'%expiration date%',
					),
					array(
						$group_name,
						$expiration_date,
					),
					$email_body
				);

				$headers   = array();
				$headers[] = 'Content-type: text/html; charset=UTF-8';
				$headers[] = 'From: ' . get_bloginfo( 'name' ) . ' <' . get_bloginfo( 'admin_email' ) . '>';
				$headers   = apply_filters( 'uo_ld_expire_group_email_headers', $headers );
				foreach ( $user_ids as $user_id ) {
					$group_progress = learndash_get_user_group_progress( $group_id, $user_id );
					if ( true === apply_filters( 'uo_ld_expire_group_do_not_send_email_to_user', false, $user_id, $group_id, $group_progress ) ) {
						continue;
					}
					$user      = get_userdata( $user_id );
					$email     = $user->user_email;
					$user_name = $user->display_name;

					$message = str_ireplace( '%display name%', $user_name, $email_body );
					$message = nl2br( stripcslashes( $message ) );
					$message = wpautop( $message );
					$message = apply_filters( 'uo_ld_expire_group_email_message', $message, $user, $group_id );
					$sub     = apply_filters( 'uo_ld_expire_group_email_subject', $email_title, $user, $group_id );

					wp_mail( $email, $sub, $message, $headers );
				}
			}
		}

		update_post_meta( $group_id, 'uo-email-group-sent', current_time( get_option( 'date_format' ) . ' H:i:s' ) );
		wp_clear_scheduled_hook( 'uo-email-group', array( $group_id ) );
	}

	/**
	 * Shortcode callback function.
	 *
	 * @param array $atts SHortcode attributes.
	 *
	 * @return string
	 */
	public static function uo_group_expiration_date( $atts ) {
		$atts = shortcode_atts(
			array(
				'pre-text' => '',
				'group_id' => '',
			),
			$atts,
			'uo_group_expiration_date'
		);

		$pre_text = '';
		// Backward compability for the pre-text attribute.
		// Check if the user defined the pre-text attribute, but didn't set a value in the fields.
		if ( ! empty( $atts['pre-text'] ) ) {
			// In that case add the %days% argument
			// We won't worry about the order since this solution was already working for the user.
			$pre_text = $atts['pre-text'] . ' ';
		}

		if ( ! empty( $atts['group_id'] ) ) {
			$group_id = $atts['group_id'];
			$user_id  = get_current_user_id();

			if ( empty( $user_id ) ) {
				return '';
			}

			$expiration_date = get_post_meta( $group_id, 'uo-expiration-date', true );
			if ( ! empty( $expiration_date ) ) {
				$string = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $expiration_date ) );

				return $pre_text . $string;
			}
		} else {
			$user_id = get_current_user_id();

			if ( empty( $user_id ) ) {
				return '';
			}
			// get user's groups.
			$user_groups_id = learndash_get_users_group_ids( $user_id );

			if ( ! empty( $user_groups_id ) ) {
				$final_exp_date = '';
				foreach ( $user_groups_id as $group_id ) {
					$expiration_date = get_post_meta( $group_id, 'uo-expiration-date', true );
					if ( ! empty( $final_exp_date ) && ! empty( $expiration_date ) ) {
						return '';
					}

					if ( ! empty( $expiration_date ) ) {
						$final_exp_date = $expiration_date;
					}
				}

				if ( ! empty( $final_exp_date ) ) {
					$string = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $final_exp_date ) );

					return $pre_text . $string;
				}
			}
		}

		return '';
	}

	/**
	 * Display expiration email.
	 *
	 * @param WP_Post $post Post object for the group.
	 */
	public static function expiration_email( $post ) {

		?>
		<p>This email will be sent to all group
			members <?php echo esc_html( self::$send_email_before_days ); ?>days
			before the
			group expiry date.</p>
		<input type="text" name="uc_exp_email_title"
			   value="<?php echo esc_attr( self::get_email_title( $post->ID ) ); ?>"
			   id="uc_exp_email_title"
			   style="width:100%; font-size:20px; margin-bottom:15px;"/>

		<textarea name="uc_exp_email_body" id="uc_exp_email_body"
				  style="width:100%; height:300px; font-size:14px; padding:10px;"><?php echo esc_textarea( self::get_email_body( $post->ID ) ); ?></textarea>

		<br/>
		<b>Available Variables:</b>
		<p>%Display Name%<br/>
			%LearnDash Group Name%<br/>
			%Expiration Date%</p>
		<br/>

		<!--<a href="#" id="email_test">Test Email</a> <span id="test_result"></span>-->
		<button id="email_test">Test Email</button> <span
			id="test_result"></span>

		<script>
			jQuery('#email_test').click(function (e) {
				e.preventDefault()

				var data = {
					'action': 'UOLDGE_Email_Test',
					'post_id': <?php echo esc_attr( $post->ID ); ?>
				}

				jQuery.post(ajaxurl, data, function (response) {
					jQuery('#test_result').html(response)
				})
			})
		</script>
		<?php
	}

	/**
	 * Render expiration message shortcode.
	 *
	 * @param array $attr Shortcode attributes.
	 *
	 * @return string
	 */
	public static function group_expiration_in( $attr ) {

		$attributes = shortcode_atts(
			array(
				'pre-text' => '',
				'group-id' => '', // deprecated
				'group_id' => '', // standardized
			),
			$attr
		);

		$current_user_id = get_current_user_id();
		$group_id        = absint( $attributes['group-id'] );
		if ( ! empty( $attributes['group_id'] ) ) {
			$group_id = absint( $attributes['group_id'] );
		}

		$expiry_dates = array();
		if ( empty( $group_id ) ) {
			$group_ids = learndash_get_users_group_ids( $current_user_id, true );
			if ( ! $group_ids ) {
				return '';
			}

			foreach ( $group_ids as $g_id ) {
				$expiration_date = get_post_meta( $g_id, 'uo-expiration-date', true );
				if ( empty( $expiration_date ) ) {
					continue;
				}

				$expiry_dates[] = $g_id;
			}
		}

		// No expiry dates found.
		if ( empty( $expiry_dates ) && empty( $group_id ) ) {
			return '';
		}

		// More then one expiry date group found and group ID is not set.
		if ( count( $expiry_dates ) > 1 && empty( $group_id ) ) {
			return '';
		}

		if ( empty( $group_id ) && is_array( $expiry_dates ) ) {
			$group_id = array_shift( $expiry_dates );
		}

		if ( empty( $group_id ) ) {
			return '';
		}

		// Get expiration date.
		$group_access_up_to = get_post_meta( $group_id, 'uo-expiration-date', true );

		if ( empty( $group_access_up_to ) ) {
			return '';
		}
		$group_access_up_to = strtotime( $group_access_up_to );

		if ( learndash_is_admin_user( $current_user_id ) || learndash_is_group_leader_user( $current_user_id ) || learndash_is_user_in_group( $current_user_id, $group_id ) ) {

			return self::get_string( $attributes, $group_access_up_to );
		}

		return '';
	}

	/**
	 * Get shortcode string
	 *
	 * @param array $attributes Shortcode attributes.
	 * @param string $group_access_up_to Group access upto date.
	 *
	 * @since 0.0.0
	 */
	public static function get_string( $attributes, $group_access_up_to ) {
		// Set default text.
		$text = (object) array(
			'singular' => esc_attr__( 'Group Access Expires in 1 Day', 'uncanny-pro-toolkit' ),
			// translators: number of days.
			'plural'   => esc_attr__( 'Group Access Expires in %s Days', 'uncanny-pro-toolkit' ),
		);

		// Get fields
		$text_singular_field = self::get_settings_value( 'group_days_expiry_text_singular', __CLASS__ );
		$text_plural_field   = self::get_settings_value( 'group_days_expiry_text_plural', __CLASS__ );

		// Overwrite the default values with the one in the fields, but only if those are defined.
		$text->singular = ! empty( $text_singular_field ) ? $text_singular_field : $text->singular;
		$text->plural   = ! empty( $text_plural_field ) ? $text_plural_field : $text->plural;

		// Replace the %days% argument with an %s.
		// We need to do this so we can use sprintf to insert the value.
		$text->plural = str_replace( '%days%', '%s', $text->plural );

		// Backward compability for the pre-text attribute.
		// Check if the user defined the pre-text attribute, but didn't set a value in the fields.
		if ( ! empty( $attributes['pre-text'] ) && ( empty( $text_singular_field ) && empty( $text_plural_field ) ) ) {
			// In that case add the %days% argument
			// We won't worry about the order since this solution was already working for the user.
			$text->singular = $attributes['pre-text'] . ' ' . esc_attr__( '1 Day', 'uncanny-pro-toolkit' );
			$text->plural   = $attributes['pre-text'] . ' %s ' . esc_attr__( 'Days', 'uncanny-pro-toolkit' );
		}

		$current_time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		$amount_seconds_between = $group_access_up_to - $current_time;

		$amount_days_between = floor( $amount_seconds_between / 60 / 60 / 24 );

		if ( 0 > $amount_days_between ) {
			return '';
		}

		if ( 1 === $amount_days_between ) {
			return $text->singular;
		} else {
			return sprintf( $text->plural, $amount_days_between );
		}

	}

	/**
	 * Get the subject/title of the expiry email.
	 *
	 * @param int $group_id Post ID of the group.
	 *
	 * @since 0.0.0
	 */
	public static function get_email_title( $group_id ) {

		// check if something's stored in meta.
		$title = get_post_meta( $group_id, 'uo-expiration-email-title', true );

		// return a default value if it's empty.
		if ( empty( $title ) ) {
			return sprintf( // translators: Site Name.
				esc_html__( '%s Course Access Expiring', 'uncanny-pro-toolkit' ),
				self::$site_name
			);
		}

		return $title;
	}

	/**
	 * Get the body of the expiry email.
	 *
	 * @param int $group_id Post ID of the group.
	 *
	 * @since 0.0.0
	 */
	public static function get_email_body( $group_id ) {

		// check if something's stored in meta.
		$body = get_post_meta( $group_id, 'uo-expiration-email-body', true );

		// return a default value if it's empty.
		if ( empty( $body ) ) {
			return sprintf( // translators: Site Name.
				esc_html__( "Hi %%Display Name%%,\n\nThis is a courtesy email to let you know that your access to %s as part of the %%LearnDash Group Name%% group is expiring on %%Expiration Date%%. Your access to %s courses will be removed on that date.", 'uncanny-pro-toolkit' ), // phpcs:ignore WordPress.WP.I18n.UnorderedPlaceholdersText
				self::$site_name,
				self::$site_name
			);
		}

		return $body;

	}

	/**
	 * @param $course_access_upto
	 * @param $course_id
	 * @param $user_id
	 *
	 * @return mixed
	 */
	public static function ld_course_access_expires_on_func( $course_access_upto, $course_id, $user_id ) {
		// check if user has any groups
		$groups = learndash_get_users_group_ids( $user_id, true );
		// check if course is part of a group
		$course_groups = learndash_get_course_groups( $course_id, true );
		if ( empty( $groups ) || empty( $course_groups ) ) {
			return $course_access_upto;
		}
		// check if course is not part of user groups
		$matched_groups = array_intersect( $groups, $course_groups );
		if ( empty( $matched_groups ) ) {
			return $course_access_upto;
		}
		foreach ( $matched_groups as $group_id ) {
			$group_courses = learndash_group_enrolled_courses( $group_id, true );
			if ( empty( $group_courses ) ) {
				continue;
			}
			if ( ! in_array( $course_id, $group_courses, true ) ) {
				continue;
			}
			$expiration_date = get_post_meta( $group_id, 'uo-expiration-date', true );
			if ( ! empty( $expiration_date ) ) {
				$system_time        = time();
				$wp_time            = current_time( 'timestamp' ); //phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
				$offset             = $system_time - $wp_time;
				$course_access_upto = apply_filters( 'uo_ld_course_access_expires_on', strtotime( $expiration_date ) + $offset, $user_id, $course_id, $group_id );
			}
			break;
		}

		return $course_access_upto;
	}

}
