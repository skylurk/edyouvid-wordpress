<?php
/**
 * TinCan Content List Table
 *
 * @package    TinCanny
 * @subpackage TinCanny/includes
 * @author     Uncanny Owl
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class TinCan_Content_List_Table
 *
 * @since 1.0.0
 */
class TinCan_Content_List_Table extends \WP_List_Table {

	/**
	 * Site ID
	 *
	 * @var int
	 */
	public $site_id;

	/**
	 * Data
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Count
	 *
	 * @var int
	 */
	public $count;

	/**
	 * Items per page
	 *
	 * @var int
	 */
	public $per_page = 50;

	/**
	 * Columns
	 *
	 * @var array
	 */
	private $columns = array();

	/**
	 * Sortable columns
	 *
	 * @var array
	 */
	private $sortable_columns = array();

	/**
	 * Constructor
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = array() ) {
		$this->per_page = apply_filters( 'tincanny_contents_per_page', $this->per_page );
		parent::__construct();
		
	}

	/**
	 * Magic setter
	 *
	 * @param string $name  Property name.
	 * @param mixed  $value Property value.
	 */
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'column':
				if ( is_array( $value ) ) {
					foreach ( $value as $key => $val ) {
						$key                   = 'ID' === $key ? 'id' : $key;
						$this->columns[ $key ] = $val;
					}
				}
				break;

			case 'sortable_columns':
				if ( is_array( $value ) ) {
					foreach ( $value as $key => $val ) {
						$key                            = 'ID' === $key ? 'id' : $key;
						$this->sortable_columns[ $key ] = array( $key, true );
					}
				}
				break;

			case 'extra_tablenav':
				$this->extra_tablenav = $value;
				break;
		}
	}

	/**
	 * Prepare items
	 */
	public function prepare_items() {
		global $wpdb;
		$paged                 = $this->get_pagenum();
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$search   = '';
		$limit    = '';
		$order_by = '';
		if ( ! empty( ultc_get_filter_var( 'search_key', '' ) ) ) {
			$search = $wpdb->_real_escape( ultc_filter_input( 'search_key' ) );
		}
		$order   = ! empty( ultc_get_filter_var( 'order', '' ) ) ? $wpdb->_real_escape( ultc_filter_input( 'order' ) ) : 'DESC';
		$orderby = ! empty( ultc_get_filter_var( 'orderby', '' ) ) ? $wpdb->_real_escape( ultc_filter_input( 'orderby' ) ) : 'ID';

		if ( ! empty( $orderby ) && ! empty( $order ) ) {
			$order_by = ' ORDER BY ' . $orderby . ' ' . $order;
		}
		if ( ! empty( $paged ) && ! empty( $this->per_page ) ) {
			$offset = ( $paged - 1 ) * $this->per_page;
			$limit  = ' LIMIT ' . (int) $offset . ',' . (int) $this->per_page;
		} else {
			$limit = ' LIMIT 0,' . (int) $this->per_page;
		}
		$data = \TINCANNYSNC\Module_CRUD::get_contents( $search, $limit, $order_by );

		$contents = array();
		if ( ! empty( $data ) ) {
			foreach ( $data as $post ) {
				$path = $post->url; // /wp-content/uploads/uncanny-snc/777/index_lms.html 
				
				// Get size and modified date from database
				$size = $post->size ? round( $post->size / ( 1024 * 1024 ), 2 ) . ' MB' : '<span title="Calculating size..." class="dashicons dashicons-clock"></span>';

				if( $post->size < 1 ) {
					// Schedule a single event to calculate size in 2 seconds
					if( ! wp_next_scheduled( 'tincanny_calculate_folder_size', array( $post->ID ) ) ) {
						wp_schedule_single_event( time() + 1, 'tincanny_calculate_folder_size', array( $post->ID ) );
					}
				}
				
				$modified_date = ( $post->upload_date && $post->upload_date !== '0000-00-00 00:00:00' ) ? date( 'Y-m-d H:i:s', strtotime( $post->upload_date ) ) : '-';

				$src        = site_url( $post->url );
				$src        = apply_filters( 'tincanny_module_url_preview', $src, $post );
				$user       = wp_get_current_user();
				$user_data  = is_a( $user, 'WP_User' ) && isset( $user->data ) ? $user->data : false;
				$user_name  = $user_data && isset( $user_data->display_name ) ? $user_data->display_name : 'Unknown';
				$user_name  = str_replace( array( '"', "'" ), array( '', '' ), $user_name );
				$user_email = $user_data && isset( $user_data->user_email ) ? $user_data->user_email : 'Unknown@anonymous.com';
				$user_email = apply_filters( 'uo_tincanny_actor_mbox', $user_email, $user );

				$args = array(
					'endpoint'    => \UCTINCAN\Init::$endpint_url . '/',
					'auth'        => 'LearnDashId' . $post->ID,
					'actor'       => rawurlencode(
						sprintf(
							'{"name": ["%s"], "mbox": ["mailto:%s"]}',
							$user_name,
							$user_email
						)
					),
					'activity_id' => $src,
					'client'      => $post->type,
					'base_url'    => get_option( 'home' ),
					'nonce'       => wp_create_nonce( 'tincanny-module' ),
					'TB_iframe'   => 'true',
					'width'       => '1000',
					'height'      => '600',
				);

				$src = add_query_arg( $args, $src );

				$content = array(
					'id'          => $post->ID,
					'content'     => $post->content,
					'type'        => $post->type,
					'upload_date' => $modified_date,
					'size'        => $size,
					'raw_size'    => $post->size, // Store raw size for sorting
					'actions'     => '<a href="' . $src . '" class="snc_preview thickbox" data-item_id="' . $post->ID . '" title="Preview"><span class="dashicons dashicons-visibility"></span></a> <a href="#TB_inline?height=150&width=400&inlineId=tclr-replace-content" class="snc_replace_confirm thickbox" data-item_id="' . $post->ID . '" title="Replace"><span class="dashicons dashicons-update"></span></a> <a href="#" class="delete" data-item_id="' . $post->ID . '" title="Delete"><span class="dashicons dashicons-trash"></span></a>',
				);

				$contents[] = $content;
			}
		}

		// Sort by size if requested
		$orderby = ! empty( ultc_get_filter_var( 'orderby', '' ) ) ? ultc_filter_input( 'orderby' ) : 'ID';
		$order = ! empty( ultc_get_filter_var( 'order', '' ) ) ? ultc_filter_input( 'order' ) : 'DESC';

		if ( 'size' === $orderby ) {
			usort( $contents, function( $a, $b ) use ( $order ) {
				if ( $order === 'ASC' ) {
					return $a['raw_size'] <=> $b['raw_size'];
				}
				return $b['raw_size'] <=> $a['raw_size'];
			} );
		}

		$this->items = $contents;

		$count      = \TINCANNYSNC\Module_CRUD::get_contents_count( $search );
		$totalpages = ceil( $count / $this->per_page );
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'total_pages' => $totalpages,
				'per_page'    => ( $this->per_page ) ? $this->per_page : 100,
			)
		);
	}

	/**
	 * No items message
	 */
	public function no_items() {
		esc_html_e( 'No Items found.' );
	}

	/**
	 * Get views
	 */
	protected function get_views() {
		/*
				$view["view_all"] = sprintf( '<a href="%s">View all Codes</a>', add_query_arg( array( "group_id" => "all" ), remove_query_arg( array( "orderby", "order") ) ) );
				return $view;
		*/
	}

	/**
	 * Single row
	 *
	 * @param array $row Row data.
	 * @return string
	 */
	public function single_row( $row ) {
		$r = "<tr data-item_id='{$row['id']}'>";
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$r .= '<td>';

			if ( array_key_exists( $column_name, $this->columns ) ) {
				if ( isset( $this->columns[ $column_name ] ) && isset( $row[ $column_name ] ) ) {
					$r .= $row[ $column_name ];
				}
			}

			$r .= '</td>';
		}

		$r .= '</tr>';

		return $r;
	}

	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return $this->sortable_columns;
	}

	/**
	 * Display rows
	 */
	public function display_rows() {
		foreach ( $this->items as $group ) {
			echo "\n\t" . $this->single_row( $group ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Extra table navigation
	 *
	 * @param string $which Which tablenav.
	 */
	protected function extra_tablenav( $which ) {
		switch ( $which ) {
			case 'top':
				// Create proper container structure for flexbox layout
				$filter_html = '<div class="content-top-controls">';
				
				// Left side - Upload button container
				$filter_html .= '<div class="controls-left">';
				$filter_html .= '<a href="media-upload.php?type=snc&tab=upload&min-height=400&no_tab=1&TB_iframe=true" class="page-title-action thickbox">' . esc_html__( 'Upload Content', 'uncanny-learndash-reporting' ) . '</a>';
				$filter_html .= '</div>';
				
				// Right side - Search form container  
				$filter_html .= '<div class="controls-right">';
				$filter_html .= '<form id="content-filter" method="get">';
				$filter_html .= '<input type="hidden" name="page" value="manage-content"/>';
				$filter_html .= '<div class="search-box">';
				$filter_html .= '<input type="text" name="search_key" value="' . esc_attr( ultc_get_filter_var( 'search_key', '' ) ) . '" placeholder="Search content..."/>';
				$filter_html .= '<input type="submit" name="filter_action" id="post-query-submit" class="button" value="Search">';
				$filter_html .= '</div>';
				$filter_html .= '</form>';
				$filter_html .= '</div>';
				
				// Close main container
				$filter_html .= '</div>';
				
				// Output html.
				echo $filter_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;
			case 'bottom':
				echo '';
				break;
		}
	}
}
