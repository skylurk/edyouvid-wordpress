<?php

namespace uncanny_learndash_codes;

use WP_List_Table;

/**
 * Class ViewGroups
 * @package uncanny_learndash_codes
 */
class ViewGroups extends WP_List_Table {
	/**
	 * @var
	 */
	public $site_id;

	/**
	 * ViewGroups constructor.
	 *
	 * @param array $args
	 */
	public function __construct() {
		parent::__construct();
		
		// Add notice filtering for redemption codes pages
		add_action( 'admin_head', array( __CLASS__, 'maybe_filter_non_redemption_codes_admin_notices' ), PHP_INT_MAX );
		add_action( 'admin_print_scripts', array( __CLASS__, 'maybe_filter_non_redemption_codes_admin_notices' ), PHP_INT_MAX );
	}

	/**
	 * @param string $searched
	 */
	public function prepare_items( $searched = '' ) {
		global $wpdb;
		if ( $wpdb->get_var( "SELECT COUNT(code) FROM $wpdb->prefix" . Config::$tbl_codes ) > 0 ) {
			$paged                 = $this->get_pagenum();
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$orderby               = SharedFunctionality::ulc_filter_has_var( 'orderby' ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'orderby' ) ) : 'issue_date';
			$order                 = SharedFunctionality::ulc_filter_has_var( 'order' ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 'order' ) ) : 'DESC';
			$paged                 = SharedFunctionality::ulc_filter_has_var( 'paged' ) ? SharedFunctionality::ulc_filter_input( 'paged', INPUT_GET, FILTER_SANITIZE_NUMBER_INT ) : 1;
			$searched              = SharedFunctionality::ulc_filter_has_var( 's' ) ? sanitize_text_field( SharedFunctionality::ulc_filter_input( 's' ) ) : '';

			$this->items = Database::get_groups( $paged, $orderby, $order, $searched );
			$this->set_pagination_args(
				array(
					'total_items' => Database::get_num_groups( $searched ),
					'per_page'    => 50,
				)
			);
		} else {
			// Initialize items as empty array when there are no codes
			$this->items = array();
		}
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'group_name'   => esc_html__( 'Batch', 'uncanny-learndash-codes' ),
			'code_for'     => esc_html__( 'Type', 'uncanny-learndash-codes' ),
			'timeline'     => esc_html__( 'Dates', 'uncanny-learndash-codes' ),
			'used_count'   => esc_html__( 'Code Usage', 'uncanny-learndash-codes' ),
			'cancelled'    => esc_html__( 'Cancelled', 'uncanny-learndash-codes' ),
			'action'       => esc_html__( 'Actions', 'uncanny-learndash-codes' ),
		);

		return apply_filters( 'ulc_codes_group_columns', $columns );
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		$columns = array(
			'timeline'   => array( 'issue_date', true ),
			'code_for'   => array( 'code_for', true ),
		);

		return apply_filters( 'ulc_codes_group_sortable', $columns );
	}

	/**
	 *
	 */
	public function no_items() {
		echo sprintf( '<a href="%s" class="uncannyowl-btn uncannyowl-btn--primary uncannyowl-btn--lg">%s</a>', admin_url( 'admin.php?page=uncanny-learndash-codes-create' ), esc_html__( 'Generate new codes', 'uncanny-learndash-codes' ) );
	}


	/**
	 *
	 */
	public function display_rows() {
		$row_counter = 0;
		foreach ( $this->items as $group ) {
			echo "\n\t";
			echo $this->single_row( $group, $row_counter );
			$row_counter++;
		}
	}

	/**
	 * @param object $group
	 * @param int $row_index
	 *
	 * @return string
	 */
	public function single_row( $group, $row_index = 0 ) {
		$total_codes_count = SharedFunctionality::ulc_get_issue_count( $group->ID );

		$row             = '<tr>';
		list( $columns ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$row .= '<td class="column-' . $column_name . '">';
			switch ( $column_name ) {

			case 'group_name':
				$row .= ( null === $group->name ) ? absint( $group->ID ) : esc_html( $group->name );
				
				// Add prefix and suffix badges if they exist
				$format_badges = array();
				
				if ( ! empty( $group->prefix ) ) {
					$format_badges[] = '<span class="uncannyowl-badge uncannyowl-badge-primary uncannyowl-badge-xxs uncannyowl-badge-padding-xs mt-sm" data-tooltip="' . esc_attr__( 'Prefix', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm">' . esc_html( $group->prefix ) . '</span>';
				}
				
				if ( ! empty( $group->suffix ) ) {
					$format_badges[] = '<span class="uncannyowl-badge uncannyowl-badge-info uncannyowl-badge-xxs uncannyowl-badge-padding-xs mt-sm" data-tooltip="' . esc_attr__( 'Suffix', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm">' . esc_html( $group->suffix ) . '</span>';
				}
				
				if ( ! empty( $format_badges ) ) {
					$row .= '<br>' . implode( ' ', $format_badges );
				}
					break;

				case 'code_for':
					$code_for = $group->code_for;
					$learndash_type = '';
					
					if ( 'group' === $code_for ) {
						$code_for = esc_html__( 'LearnDash', 'uncanny-learndash-codes' );
						$learndash_type = esc_html__( 'Group', 'uncanny-learndash-codes' );
					}
					if ( 'course' === $code_for ) {
						$code_for = esc_html__( 'LearnDash', 'uncanny-learndash-codes' );
						$learndash_type = esc_html__( 'Course', 'uncanny-learndash-codes' );
					}
					if ( 'automator' === $code_for ) {
						$code_for = esc_html__( 'Automator', 'uncanny-learndash-codes' );
					}
					
					$row .= ucwords( $code_for );
					
					// Add LearnDash type badge (Course/Group)
					if ( ! empty( $learndash_type ) ) {
						$row .= '<br><span class="uncannyowl-badge uncannyowl-badge-primary uncannyowl-badge-xxs uncannyowl-badge-padding-xs mt-sm">' . $learndash_type . '</span>';
					}
					
					// Add payment type badge for LearnDash types
					if ( 'group' === $group->code_for || 'course' === $group->code_for ) {
						$p_u = $group->paid_unpaid;
						if ( 'paid' === $p_u ) {
							$row .= ' <span class="uncannyowl-badge uncannyowl-badge-info uncannyowl-badge-xxs uncannyowl-badge-padding-xs mt-sm">' . esc_html__( 'Prepaid', 'uncanny-learndash-codes' ) . '</span>';
						} elseif ( 'unpaid' === $p_u ) {
							$row .= ' <span class="uncannyowl-badge uncannyowl-badge-info uncannyowl-badge-xxs uncannyowl-badge-padding-xs mt-sm">' . esc_html__( 'Not Prepaid', 'uncanny-learndash-codes' ) . '</span>';
						}
					}
			break;

			case 'timeline':
				$date_format = get_option( 'date_format', 'F j, Y' );
				$created_date = date_i18n( $date_format, strtotime( $group->issue_date ) );
				$row .= '<span data-tooltip="' . esc_attr__( 'Date created', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm">' . $created_date . '</span>';
				
				if ( $group->expire_date !== '0000-00-00 00:00:00' ) {
					$time_format = get_option( 'time_format', 'g:i a' );
					$expire_date = date_i18n( "$date_format $time_format", strtotime( $group->expire_date ) );
					$badge_class = ( strtotime( $group->expire_date ) < current_time( 'timestamp' ) ) ? 'uncannyowl-badge--expired' : 'uncannyowl-badge-warning';
					$row .= '<br><span class="uncannyowl-badge ' . $badge_class . ' uncannyowl-badge-xxs uncannyowl-badge-padding-xs mt-sm" data-tooltip="' . esc_attr__( 'Date expiry', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm">' . $expire_date . '</span>';
				}
			break;
			case 'used_count':
				$used         = Database::get_group_redeemed_count( $group->ID );
				$issue        = absint( $total_codes_count );
				$max_per_code = (int) $group->issue_max_count;
				if ( 1 === $max_per_code && 'automator' === (string) $group->code_for ) {
					$max_per_code = (int) apply_filters( 'ulc_code_max_usage', $group->issue_max_count, $group->code_for, $group->ID );
				}
				$usage_text = absint( $used ) . ' / ' . absint( $issue * $max_per_code );
				$tooltip_text = sprintf(
					esc_html__( 'Total codes: %d | Max uses per code: %d', 'uncanny-learndash-codes' ),
					$issue,
					$max_per_code
				);
				$row .= '<span data-tooltip="' . esc_attr( $tooltip_text ) . '">' . $usage_text . '</span>';
				break;
			case 'cancelled':
				$cancelled = Database::get_group_cancelled_count( $group->ID );
				$row       .= absint( $cancelled );
				break;

			case 'action':
				$actions             = array();
				$actions['edit']     = '<a class="uncannyowl-btn-action uncannyowl-btn uo-btn-edit-code" href="' . admin_url( 'admin.php?page=uncanny-learndash-codes-create&edit=true&group_id=' . $group->ID ) . '" data-tooltip="' . esc_attr__( 'Edit', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm"><span class="dashicons dashicons-edit"></span></a>';
				$actions['view']     = '<a class="uncannyowl-btn-action uo-btn-view-code" href="' . add_query_arg(
					array( 'group_id' => $group->ID ),
					remove_query_arg(
						array(
							'orderby',
							'paged',
							'order',
							's',
						)
					)
				) . '" data-tooltip="' . esc_attr__( 'View', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm"><span class="dashicons dashicons-visibility"></span></a>';
				$actions['download'] = '<a class="uncannyowl-btn-action uo-btn-download-code" href="' . add_query_arg(
					array(
						'group_id' => $group->ID,
						'mode'     => 'download',
					),
					remove_query_arg(
						array(
							'orderby',
							'paged',
							'order',
							's',
						)
					)
				) . '" data-tooltip="' . esc_attr__( 'Download', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm"><span class="dashicons dashicons-download"></span></a>';
				$actions['delete']   = '<a class="uncannyowl-btn-action uncannyowl-btn-action--delete uo-btn-delete-code" href="' . add_query_arg(
					array(
						'group_id' => $group->ID,
						'mode'     => 'delete',
					),
					remove_query_arg(
						array(
							'orderby',
							'paged',
							'order',
							's',
						)
					)
				) . '" data-tooltip="' . esc_attr__( 'Delete', 'uncanny-learndash-codes' ) . '" data-tooltip-size="sm"><span class="dashicons dashicons-trash"></span></a>';

				$row .= implode( ' ', $actions );
				break;
				default:
					$row .= apply_filters( 'ulc_codes_group_row_' . $column_name, $row, $group );
					break;
			}
			$row .= '</td>';
		}

		$row = apply_filters( 'ulc_codes_group_row', $row, $group );
		$row .= '</tr>';

		return $row;
	}

	/**
	 * Override the display method to show only table content (no navigation)
	 */
	public function display() {
		// Check if there are no items
		if ( empty( $this->items ) ) {
			// Display the button outside the table when there are no items
			$this->no_items();
			return;
		}
		
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
	 * Override display_tablenav to include search form in top navigation
	 */
	public function display_tablenav( $which ) {
		// Hide table navigation when there are no items
		if ( empty( $this->items ) ) {
			return;
		}
		
		if ( 'top' === $which ) {
			?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>"  >
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions(); ?>
				</div>
				<div class="uo-codes-heading alignright">
					<form class="uo-codes-search" method="get" action="">
						<input type="hidden" name="page" value="<?php echo esc_attr( SharedFunctionality::ulc_filter_input( 'page' ) ); ?>"/>
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
