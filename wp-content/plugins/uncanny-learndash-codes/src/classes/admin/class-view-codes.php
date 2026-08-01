<?php

namespace uncanny_learndash_codes;

use WP_List_Table;

/**
 * Class ViewCodes
 * @package uncanny_learndash_codes
 */
class ViewCodes extends WP_List_Table {
	/**
	 * @var mixed|string
	 */
	private $group_id;

	/**
	 * ViewCodes constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		parent::__construct();
		$this->group_id = isset( $args['group_id'] ) ? $args['group_id'] : 'all';
		
		// Add notice filtering for redemption codes pages
		add_action( 'admin_head', array( __CLASS__, 'maybe_filter_non_redemption_codes_admin_notices' ), PHP_INT_MAX );
		add_action( 'admin_print_scripts', array( __CLASS__, 'maybe_filter_non_redemption_codes_admin_notices' ), PHP_INT_MAX );
	}

	/**
	 *
	 */
	public function prepare_items() {
		if ( empty( $this->group_id ) && SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) {
			$this->group_id = SharedFunctionality::ulc_filter_input( 'group_id' );
		}

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$orderby = ( SharedFunctionality::ulc_filter_has_var( 'orderby' ) ) ? SharedFunctionality::ulc_filter_input( 'orderby' ) : '';
		$order   = ( SharedFunctionality::ulc_filter_has_var( 'order' ) ) ? SharedFunctionality::ulc_filter_input( 'order' ) : '';

		$paged       = SharedFunctionality::ulc_filter_has_var( 'paged' ) ? SharedFunctionality::ulc_filter_input( 'paged' ) : 1;
		$searched    = SharedFunctionality::ulc_filter_has_var( 's' ) ? SharedFunctionality::ulc_filter_input( 's' ) : '';
		$this->items = Database::get_coupons( $this->group_id, $paged, $orderby, $order, $searched );

		$this->set_pagination_args(
			array(
				'total_items' => Database::get_num_coupons( $this->group_id, $searched ),
				'per_page'    => 50,
			)
		);
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return apply_filters(
			'ulc_view_codes_columns',
			array(
				'cb'            => '<input type="checkbox" />',
				'coupon'        => esc_html__( 'Code', 'uncanny-learndash-codes' ),
				'user_nicename' => esc_html__( 'Redeemed user', 'uncanny-learndash-codes' ),
				'used_date'     => esc_html__( 'Redeemed date', 'uncanny-learndash-codes' ),
				'expire_date'   => esc_html__( 'Expiry date', 'uncanny-learndash-codes' ),
				'actions'       => esc_html__( 'Action', 'uncanny-learndash-codes' ),
			)
		);
	}


	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 *
	 */
	public function no_items() {
		esc_html_e( 'No codes found.', 'uncanny-learndash-codes' );
	}

	/**
	 *
	 */
	public function display_rows() {
		foreach ( $this->items as $coupon ) {
			echo "\n\t";
			echo $this->single_row( $coupon );
		}
	}

	/**
	 * @param object $coupon
	 *
	 * @return string
	 */
	public function single_row( $coupon ) {
		$codes_batch_id  = absint( $coupon->code_group );
		$r               = '<tr>';
		list( $columns ) = $this->get_column_info();

		$date_format = apply_filters( 'ulc_view_codes_date_format', get_option( 'date_format' ) );
		$time_format = apply_filters( 'ulc_view_codes_time_format', get_option( 'time_format' ) );

		// Optimize: Call get_users_of_code once and reuse the result
		$code_users = Database::get_users_of_code( $coupon->ID, false );

		foreach ( $columns as $column_name => $column_display_name ) {
			$r .= '<td class="column-' . $column_name . '" data-label="' . esc_attr( $column_display_name ) . '">';
			switch ( $column_name ) {
				case 'cb':
					$r .= $this->column_cb( $coupon );
					break;
				case 'coupon':
					// Add strikethrough class if code is cancelled
					$code_class = ( 0 === absint( $coupon->is_active ) ) ? 'code-value code-cancelled' : 'code-value';
					$r .= '<span class="' . $code_class . '" data-tooltip="' . esc_attr__( 'Click to copy', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm">' . esc_html( $coupon->code ) . '</span>';
					
					// Get status badge
					if ( Database::is_coupon_expired( $coupon ) ) {
						$status       = __( 'Expired', 'uncanny-learndash-codes' );
						$status_class = 'uncannyowl-badge--expired';
					} else {
						$status       = __( 'Active', 'uncanny-learndash-codes' );
						$status_class = 'uncannyowl-badge--active';
						if ( 0 === absint( $coupon->is_active ) ) {
							$status       = __( 'Cancelled', 'uncanny-learndash-codes' );
							$status_class = 'uncannyowl-badge--cancelled';
						}
					}
					$status_badge = '<span class="uncannyowl-badge ' . $status_class . ' uncannyowl-badge-padding-sm uncannyowl-badge-xxs">' . $status . '</span>';
					
					// Show status badge and order ID on the same line below the code
					$r .= '<br /><div class="mt-sm">';
					$r .= $status_badge;
					
					// Show order ID if it exists
					if ( 0 !== absint( $coupon->order_id ) ) {
						$order_badge = sprintf( '<span class="uncannyowl-badge uncannyowl-badge-primary uncannyowl-badge-xxs uncannyowl-badge-padding-xs ml-sm"><a href="%s" target="_blank" data-tooltip="%s" data-tooltip-size="sm" style="text-decoration: none; color: inherit;">%s</a></span>', 
							admin_url( 'post.php?post=' . $coupon->order_id . '&action=edit' ),
							esc_attr__( 'Woo Order ID', 'uncanny-learndash-codes' ),
							esc_html__( 'Order: ', 'uncanny-learndash-codes' ) . esc_html( $coupon->order_id )
						);
						$r .= $order_badge;
					}
					$r .= '</div>';
					break;

				case 'source':
					$users = maybe_unserialize( $coupon->user_id );
					if ( $users ) {
						foreach ( $users as $u ) {
							$r .= sprintf( '%s', get_user_meta( $u['user'], Config::$uncanny_codes_tracking, true ) ) . '<br />';
						}
					}
					break;

				case 'used_date':
					if ( $code_users ) {
						foreach ( $code_users as $u ) {

							$num_times_allowed = Database::number_of_times_a_user_allowed_to_redeem( $codes_batch_id );
							if ( $num_times_allowed > 1 ) {
								$dates_raw_result = Database::get_used_dates_of_user_by_code( $coupon->ID, $u->user_id );
								$dates_raw        = array();
								foreach ( $dates_raw_result as $d_r_r ) {
									$dates_raw[] = date_i18n( $date_format, strtotime( $d_r_r ) );
								}
								$r .= sprintf( '%s', implode( '<br />', $dates_raw ) ) . '<br />';
							} else {
								$r .= sprintf( '%s', date_i18n( $date_format, strtotime( $u->date_redeemed ) ) ) . '<br />';
							}
						}
					}
					break;
				case 'expire_date':
					if ( isset( $coupon->code_expire_date ) && $coupon->code_expire_date !== '0000-00-00 00:00:00' ) {
						$r .= date_i18n( "$date_format $time_format", strtotime( $coupon->code_expire_date ) );
					} elseif ( isset( $coupon->expire_date ) && $coupon->expire_date !== '0000-00-00 00:00:00' ) {
						$r .= date_i18n( "$date_format $time_format", strtotime( $coupon->expire_date ) );
					} else {
						$r .= esc_html__( 'Unlimited', 'uncanny-learndash-codes' );
					}
					break;
				case 'user_nicename':
					$display = '';
					if ( $code_users ) {
						foreach ( $code_users as $u ) {
							if ( intval( '-1' ) === intval( $u->user_id ) ) {
								$r .= esc_html__( 'Order refunded', 'uncanny-learndash-codes' );
							} else {
								$first_name = get_user_meta( $u->user_id, 'first_name', true );
								$last_name  = get_user_meta( $u->user_id, 'last_name', true );
								if ( empty( $first_name ) && empty( $last_name ) ) {
									$us = get_user_by( 'id', $u->user_id );
									if ( ! empty( $us ) ) {
										$display = $us->user_email;
									}
								} else {
									$display = "{$first_name} {$last_name}";
								}

								$count_display     = '';
								$num_times_allowed = Database::number_of_times_a_user_allowed_to_redeem( $codes_batch_id );
								if ( $num_times_allowed > 1 ) {
									$count         = Database::get_usage_count_of_user_by_code( $coupon->ID, $u->user_id );
									$times         = sprintf( __( '%1$d/%2$d time(s)', 'uncanny-learndash-codes' ), $count, $num_times_allowed );
									$count_display = " &mdash; $times";
								}

								if ( 0 < (int) $u->user_id && '' === trim( $display ) ) {
									$display = sprintf( __( 'Deleted user (ID: %d)', 'uncanny-learndash-codes' ), (int) $u->user_id );
									$r       .= sprintf( '%s%s', $display, $count_display ) . '<br />';
								} else {
									$r .= sprintf( '<a href="%s">%s</a>%s', admin_url( 'user-edit.php?user_id=' . $u->user_id ), $display, $count_display ) . '<br />';
								}
							}
						}
					}
					break;
				case 'actions':
				// Keep only the edit button for quick single-code editing
				$actions         = array();
				$actions['edit'] = '<a class="uncannyowl-btn-action uncannyowl-btn-action--edit uo-btn-edit-code" href="#" data-code-id="' .
				   $coupon->ID . '" data-tooltip="' . esc_attr__( 'Edit expiry date', 'uncanny-learndash-codes' ) .
				   '" data-tooltip-size="sm"><span class="dashicons dashicons-clock"></span></a>';

					$r .= implode( ' ', $actions );
					break;
				default:
					$r = apply_filters( 'ulc_view_codes_data_row', $r, $coupon );
					break;
			}
			$r .= '</td>';
		}
		$r .= '</tr>';

		return $r;
	}


	/**
	 * @return mixed
	 */
	protected function get_views() {
		// Only show the back button in a separate location
		return array();
	}

	/**
	 * Display the back button above the table
	 */
	public function display_back_button() {
		echo '<div class="back-button-container">';
		printf(
			'<a href="%s" class="uncannyowl-btn uncannyowl-btn--secondary uncannyowl-btn--sm">%s</a>',
			remove_query_arg(
				array(
					'group_id',
					'orderby',
					'order',
				)
			),
			esc_html__( 'View Code Batches', 'uncanny-learndash-codes' )
		);
		echo '</div>';
	}

	/**
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'coupon';
	}

	/**
	 * Get bulk actions dropdown
	 */
	protected function get_bulk_actions() {
		return array(
			'bulk_update_expiry' => __( 'Update Expiry Date', 'uncanny-learndash-codes' ),
			'bulk_replace'       => __( 'Replace Codes', 'uncanny-learndash-codes' ),
			'bulk_cancel'        => __( 'Cancel Codes', 'uncanny-learndash-codes' ),
			'bulk_delete'        => __( 'Delete Codes', 'uncanny-learndash-codes' ),
			'bulk_download'      => __( 'Download Selected', 'uncanny-learndash-codes' ),
		);
	}

	/**
	 * Handle the checkbox column
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="code_ids[]" value="%s" class="code-checkbox" />',
			$item->ID
		);
	}

	/**
	 * Override bulk_actions to apply Uncanny theme styling
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two            = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<div class="uncannyowl-custom-select">';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . '" class="uncannyowl-select-input">';
		echo '<option value="-1" data-placeholder="true">' . __( 'Bulk actions' ) . '</option>';

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
			$icon = $this->get_bulk_action_icon( $name );

			echo '<option value="' . $name . '"' . $class . ' data-icon="' . $icon . '">' . $title . '</option>';
		}

		echo '</select>';
		echo '<div class="uncannyowl-select-arrow">';
		echo '<svg width="12" height="8" viewBox="0 0 12 8" fill="none">';
		echo '<path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
		echo '</svg>';
		echo '</div>';
		echo '</div>';

		submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}

	/**
	 * Get icon for bulk action
	 */
	private function get_bulk_action_icon( $action ) {
		$icons = array(
			'bulk_update_expiry' => 'dashicons-clock',
			'bulk_replace'       => 'dashicons-update',
			'bulk_cancel'        => 'dashicons-no',
			'bulk_delete'        => 'dashicons-trash',
			'bulk_download'      => 'dashicons-download',
		);

		return isset( $icons[ $action ] ) ? $icons[ $action ] : 'dashicons-admin-tools';
	}

	/**
	 * Override the search box to use our custom styling
	 */
	public function search_box( $text, $input_id ) {
		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<input type="submit" id="search-submit" class="uncannyowl-btn" value="<?php echo esc_attr( $text ); ?>" />
		</p>
		<?php
	}

	/**
	 * Override the display method to show only table content (no navigation)
	 */
	public function display() {
		// Only display the table content, navigation is handled separately in template
		?>
		<table class="wp-list-table widefat fixed striped posts">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>
		</table>
		<?php
	}

	/**
	 * Override display_tablenav to include search form in top navigation
	 */
	public function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>" >
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<div class="uo-codes-heading alignright">
					<form class="uo-codes-search" method="get" action="">
						<input type="hidden" name="page" value="<?php echo esc_attr( SharedFunctionality::ulc_filter_input( 'page' ) ); ?>"/>
						<?php if ( SharedFunctionality::ulc_filter_has_var( 'group_id' ) ) { ?>
							<input type="hidden" name="group_id" value="<?php echo esc_attr( SharedFunctionality::ulc_filter_input( 'group_id' ) ); ?>"/>
						<?php } ?>
						<?php $this->search_box( esc_html__( 'Search Codes', 'uncanny-learndash-codes' ), Config::get_project_name() ); ?>
					</form>
				</div>
			</div>
			<?php
		} else {
			// Use default WordPress behavior for bottom navigation
			parent::display_tablenav( $which );
		}
	}

	/**
	 * Maybe apply notification filters to Redemption Codes pages.
	 * 
	 * @return void
	 */
	public static function maybe_filter_non_redemption_codes_admin_notices() {

		$redemption_codes_pages = array(
			'uncanny-learndash-codes',
			'uncanny-learndash-codes-create',
			'uncanny-learndash-codes-settings',
			'uncanny-learndash-codes-help',
		);

		// Bail if we're not on a Redemption Codes screen.
		if ( empty( $_REQUEST['page'] ) || ! in_array( strtolower( $_REQUEST['page'] ), $redemption_codes_pages, true ) ) {
			return;
		}

		// Run filter on all admin notices.
		self::filter_non_redemption_codes_admin_notices( 'user_admin_notices' );
		self::filter_non_redemption_codes_admin_notices( 'admin_notices' );
		self::filter_non_redemption_codes_admin_notices( 'all_admin_notices' );
	}

	/**
	 * Filter out all notices that are not from Redemption Codes.
	 * 
	 * @param string $notice_type The type of notice to filter.
	 * 
	 * @return void
	 */
	public static function filter_non_redemption_codes_admin_notices( $notice_type ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $notice_type ] ) ) {
			return;
		}

		if ( ! is_array( $wp_filter[ $notice_type ]->callbacks ) ) {
			return;
		}

		// All Redemption Codes lowercased namespaces.
		$allowed_sources = array(
			'uncanny_learndash_codes',
			'uncanny_owl',
			'ulc_',
			'uncannyowl',
		);

		foreach ( $wp_filter[ $notice_type ]->callbacks as $priority => $hooks ) {
			foreach ( $hooks as $name => $arr ) {
				if ( is_object( $arr['function'] ) && $arr['function'] instanceof \Closure ) {
					unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
					continue;
				}

				// Determine the source of the notice
                $source = '';
				if ( isset( $arr['function'] ) && is_array( $arr['function'] ) && ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) ) {
                    $source = strtolower( get_class( $arr['function'][0] ) );
                } elseif ( ! empty( $name ) ) {
                    $source = strtolower( $name );
                }

				// Remove the notice if its source is not in the list of allowed sources
                $allowed = false;
                foreach ( $allowed_sources as $allowed_source ) {
                    if ( strpos( $source, $allowed_source ) !== false) {
                        $allowed = true;
                        break;
                    }
                }

                if ( ! $allowed ) {
                    unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
                }
			}
		}
	}

}
