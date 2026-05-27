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
					'per_page'    => 100,
				)
			);
		}
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'group_name'   => esc_html__( 'Code name', 'uncanny-learndash-codes' ),
			'code_for'     => esc_html__( 'Type', 'uncanny-learndash-codes' ),
			'issue_date'   => esc_html__( 'Created', 'uncanny-learndash-codes' ),
			'prefix'       => esc_html__( 'Prefix', 'uncanny-learndash-codes' ),
			'suffix'       => esc_html__( 'Suffix', 'uncanny-learndash-codes' ),
			'paid_unpaid'  => esc_html__( 'LD type', 'uncanny-learndash-codes' ),
			'max_per_code' => esc_html__( 'Max per code', 'uncanny-learndash-codes' ),
			'count'        => esc_html__( 'Codes generated', 'uncanny-learndash-codes' ),
			'expire_date'  => esc_html__( 'Expiry date', 'uncanny-learndash-codes' ),
			'used_count'   => esc_html__( 'Redeemed', 'uncanny-learndash-codes' ),
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
			'issue_date' => array( 'issue_date', true ),
			'code_for'   => array( 'code_for', true ),
		);

		return apply_filters( 'ulc_codes_group_sortable', $columns );
	}

	/**
	 *
	 */
	public function no_items() {
		echo sprintf( '<a href="%s" class="button button-large button-hero">%s</a>', admin_url( 'admin.php?page=uncanny-learndash-codes-create' ), esc_html__( 'Generate new codes', 'uncanny-learndash-codes' ) );
	}


	/**
	 *
	 */
	public function display_rows() {
		foreach ( $this->items as $group ) {
			echo "\n\t";
			echo $this->single_row( $group );
		}
	}

	/**
	 * @param object $group
	 *
	 * @return string
	 */
	public function single_row( $group ) {
		$total_codes_count = SharedFunctionality::ulc_get_issue_count( $group->ID );

		$row             = '<tr>';
		list( $columns ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$row .= '<td>';
			switch ( $column_name ) {

				case 'group_name':
					$row .= ( null === $group->name ) ? absint( $group->ID ) : esc_html( $group->name );
					break;

				case 'code_for':
					$code_for = $group->code_for;
					if ( 'group' === $code_for ) {
						$code_for = esc_html__( 'LearnDash Group', 'uncanny-learndash-codes' );
					}
					if ( 'course' === $code_for ) {
						$code_for = esc_html__( 'LearnDash Course', 'uncanny-learndash-codes' );
					}
					$row .= ucwords( $code_for );
					break;

				case 'paid_unpaid':
					$p_u = $group->paid_unpaid;
					if ( 'paid' === $p_u ) {
						$p_u = esc_html__( 'Woo: Prepaid', 'uncanny-learndash-codes' );
					} elseif ( 'unpaid' === $p_u ) {
						$p_u = esc_html__( 'Woo: Not Prepaid', 'uncanny-learndash-codes' );
					} else {
						$p_u = esc_html__( 'Default', 'uncanny-learndash-codes' );
					}

					$row .= $p_u;
					break;

				case 'prefix':
					$row .= esc_html( $group->prefix );
					break;

				case 'suffix':
					$row .= esc_html( $group->suffix );
					break;

				case 'issue_date':
					$date_format = get_option( 'date_format', 'F j, Y' );
					$row         .= date_i18n( $date_format, strtotime( $group->issue_date ) );

					break;
				case 'expire_date':
					if ( $group->expire_date !== '0000-00-00 00:00:00' ) {
						$date_format = get_option( 'date_format', 'F j, Y' );
						$time_format = get_option( 'time_format', 'g:i a' );
						$row         .= date_i18n( "$date_format $time_format", strtotime( $group->expire_date ) );
					} else {
						$row .= esc_html__( 'Unlimited', 'uncanny-learndash-codes' );
					}
					break;
				case 'used_count':
					$used         = Database::get_group_redeemed_count( $group->ID );
					$issue        = absint( $total_codes_count );
					$max_per_code = (int) $group->issue_max_count;
					if ( 1 === $max_per_code && 'automator' === (string) $group->code_for ) {
						$max_per_code = (int) apply_filters( 'ulc_code_max_usage', $group->issue_max_count, $group->code_for, $group->ID );
					}
					$row .= absint( $used ) . ' / ' . absint( $issue * $max_per_code );
					break;
				case 'cancelled':
					$cancelled = Database::get_group_cancelled_count( $group->ID );
					$row       .= absint( $cancelled );
					break;
				case 'max_per_code':
					$max_per_code = (int) $group->issue_max_count;
					if ( 1 === $max_per_code && 'automator' === (string) $group->code_for ) {
						$max_per_code = (int) apply_filters( 'ulc_code_max_usage', $group->issue_max_count, $group->code_for, $group->ID );
					}
					if ( absint( $group->issue_max_count ) !== absint( $max_per_code ) ) {
						$max_per_code = "$max_per_code " . __( '(using override)', 'uncanny-learndash-codes' );
					}
					$row .= $max_per_code;
					break;

				case 'count':
					$row .= absint( $total_codes_count );
					break;

				case 'action':
					$actions             = array();
					$actions['edit']     = '<a class="button uo-btn-actions" href="' . admin_url( 'admin.php?page=uncanny-learndash-codes-create&edit=true&group_id=' . $group->ID ) . '" uo-tooltip="' . esc_html__( 'Edit', 'uncanny-learndash-codes' ) . '" uo-flow="up"><span class="dashicons dashicons-edit"></span></a>';
					$actions['view']     = '<a class="button uo-btn-actions" href="' . add_query_arg(
						array( 'group_id' => $group->ID ),
						remove_query_arg(
							array(
								'orderby',
								'paged',
								'order',
								's',
							)
						)
					) . '" uo-tooltip="' . esc_html__( 'View', 'uncanny-learndash-codes' ) . '" uo-flow="up"><span class="dashicons dashicons-visibility"></span></a>';
					$actions['download'] = '<a class="button uo-btn-actions" href="' . add_query_arg(
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
					) . '" uo-tooltip="' . esc_html__( 'Download', 'uncanny-learndash-codes' ) . '" uo-flow="up"><span class="dashicons dashicons-download"></span></a>';
					$actions['delete']   = '<a class="button uo-btn-actions uo-btn-delete" href="' . add_query_arg(
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
					) . '" uo-tooltip="' . esc_html__( 'Delete', 'uncanny-learndash-codes' ) . '" uo-flow="up"><span class="dashicons dashicons-trash"></span></a>';

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
	 * @return mixed
	 */
	protected function get_views() {
		global $wpdb;
		if ( $wpdb->get_var( "SELECT COUNT(code) FROM $wpdb->prefix" . Config::$tbl_codes ) > 0 ) {
			$view['view_all'] = sprintf(
				'<a href="%s" class="button">%s</a>',
				add_query_arg(
					array( 'group_id' => 'all' ),
					remove_query_arg(
						array(
							'orderby',
							'order',
						)
					)
				),
				__( 'View all codes', 'uncanny-learndash-codes' )
			);

			return $view;
		}
	}
}
