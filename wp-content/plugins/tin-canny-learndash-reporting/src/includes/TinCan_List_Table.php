<?php

/**
 *
 */
class TinCan_List_Table extends \WP_List_Table {
	/**
	 * @var
	 */
	/**
	 * @var
	 */
	/**
	 * @var
	 */
	public $site_id, $data, $count;
	/**
	 * @var int
	 */
	public $per_page = 100;

	/**
	 * @var array
	 */
	private $columns = [];
	/**
	 * @var array
	 */
	private $sortable_columns = [];

	/**
	 * @var bool
	 */
	private $extra_tablenav = false;

	/**
	 * @param $args
	 */
	public function __construct( $args = array() ) {
		parent::__construct();
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return void
	 */
	public function __set( $name, $value ) {
		switch( $name ) {
			case 'column' :
				if ( is_array( $value ) ) {
					foreach( $value as $val ) {
						$key = sanitize_title( $val );
						$this->columns[ $key ] = __( $val, 'uncanny-learndash-reporting' );
					}
				}
				break;

			case 'sortable_columns' :
				if ( is_array( $value ) ) {
					foreach( $value as $val ) {
						$key = sanitize_title( $val );
						$this->sortable_columns[ $key ] = array( $key, true );
					}
				}
				break;

			case 'extra_tablenav' :
				$this->extra_tablenav = $value;
				break;
		}
	}

	/**
	 * @return void
	 */
	public function prepare_items() {
		$paged = $this->get_pagenum();
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$data = '';
		$count = 0;

		if ( is_array( $this->data ) ) {
			if ( method_exists( $this->data[0], $this->data[1] ) ) {
				$data = call_user_func( $this->data );
			}
		} else {
			if ( function_exists( $this->data ) ) {
				$data = call_user_func( $this->data );
			}
		}

 		$this->items = $data;

		if ( is_array( $this->count ) ) {
			if ( method_exists( $this->count[0], $this->count[1] ) ) {
				$count = call_user_func( $this->count );
			}
		} else {
			if ( function_exists( $this->count ) ) {
				$count = call_user_func( $this->count );
			}
		}

		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page' => ( $this->per_page ) ? $this->per_page : 100
		) );
	}

	/**
	 * @return void
	 */
	public function no_items() {
		_e( 'No Items found.' );
	}

	/**
	 * @return void
	 */
	protected function get_views() {
/*
		$view["view_all"] = sprintf( '<a href="%s">View all Codes</a>', add_query_arg( array( "group_id" => "all" ), remove_query_arg( array( "orderby", "order") ) ) );
		return $view;
*/
	}


	/**
	 * @param $row
	 *
	 * @return string
	 */
	public function single_row( $row ) {
		$r = "<tr>";
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$r .= "<td>";

			if ( array_key_exists( $column_name, $this->columns ) ) {
				if ( isset( $this->columns[ $column_name ] ) && isset( $row[ $column_name ] ) )
					$r.= $row[ $column_name ];
			}

			$r .= "</td>";
		}

		$r.= "</tr>";

		return $r;
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return $this->sortable_columns;
	}

	/**
	 * @return void
	 */
	public function display_rows() {
		foreach ( $this->items as $group ) {
			echo "\n\t" . $this->single_row( $group );
		}
	}

	/**
	 * @param $which
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {
		if ( $this->extra_tablenav ) call_user_func( $this->extra_tablenav, $which );
	}
}
