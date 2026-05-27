<?php
/**
 * Tutorlms_Quiz_Import_Export_WP_List
 */
class WP_Logs_List extends WP_List_Table {

    /**
     * constructor.
     */
    public function __construct() {
        // Set parent defaults.
        parent::__construct( array(
            'singular' => 'ldrt_wp_log',      // Singular name of the listed records.
            'plural'   => 'ldrt_wp_log',      // Plural name of the listed records.
            'ajax'     => false,                // Does this table support ajax?
        ) );

    }

    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     *
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a `column_cb()` method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     *
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information.
     */
    public function get_columns() {
        $columns = array(
            // 'cb'                => '<input type="checkbox" />', // Render a checkbox instead of text.
            // 'Log ID'            => esc_html_x( 'ID',        'ld_retake_quiz' ),
            'title'             => esc_html_x( 'Title',        'ld_retake_quiz' ),
            'message'           => esc_html_x( 'Message',        'ld_retake_quiz' ),
            'date'              => esc_html_x( 'Date',       'ld_retake_quiz' ),
        );

        return $columns;
    }

   /**
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within `prepare_items()` and sort
     * your data accordingly (usually by modifying your query).
     *
     * @return array An associative array containing all the columns that should be sortable.
     */
    protected function get_sortable_columns() {
        $sortable_columns = array(
            // 'title'         => array( 'post_title', false ),
            'date'          => array( 'post_date', false ),
        );

        return $sortable_columns;
    }

    /**
     * Get default column value.
     *
     * For more detailed insight into how columns are handled, take a look at
     * WP_List_Table::single_row_columns()
     *
     * @param object $item        A singular item (one full row's worth of data).
     * @param string $column_name The name/slug of the column to be processed.
     * @return string Text or HTML to be placed inside the column <td>.
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            // case 'Log ID':
                // return $item['ID'];
            case 'title':
                return $item['post_title'];
            case 'message':
                return $item['post_content'];
            case 'date':
                return $item['post_date'];
            default:
                return 0; // Show the whole array for troubleshooting purposes.
        }
    }


    /**
     * Get value for checkbox column.
     *
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     *
     * @param object $item A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_cb( $item ) {
        // return sprintf(
        //     '<input type="checkbox" name="ldrt_wp_log[]" value="%s" />',
        //     $item['ID']   // The value of the checkbox should be the record's ID.
        // );
    }

    /**
     * Get title column value.
     *
     * @param object $item A singular item (one full row's worth of data).
     * @return string Text to be placed inside the column <td>.
     */
    protected function column_title( $item ) {
        // Return the title contents.
        return $item['post_title'];
    }

    /**
     * Get an associative array ( option_name => option_title ) with the list
     * @return array An associative array containing all the bulk actions.
     */
    protected function get_bulk_actions() {
        $actions = array(
            // 'bulk_delete'      => esc_html_x( 'Delete', 'ld_retake_quiz' ),
        );

        return $actions;
    }

    /**
     * Handle bulk actions.
     *
     * @see $this->prepare_items()
     */
    protected function process_bulk_action() {

    }

    /**
     * Prepares the list of items for displaying.
     *
     * @global wpdb $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     */
    function prepare_items() {
        /*
         * First, lets decide how many records per page to show
         */
        $per_page = 10;

        
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        
        $this->_column_headers = array( $columns, $hidden, $sortable );
        
        $data = $this->get_ldrt_wp_logs();
        
        usort( $data, array( $this, 'usort_reorder' ) );

        $current_page = $this->get_pagenum();

        $total_items = count( $data );

        $data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->items = $data;
        // $this->process_bulk_action();

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                     // WE have to calculate the total number of items.
            'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
            'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
        ) );

    }

    protected function get_ldrt_wp_logs() {
        global $wpdb;
        $ldrt_wp_logs = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_type = 'ldrt_wp_log' AND post_status = 'publish' ORDER BY `ID` DESC", ARRAY_A );
        return $ldrt_wp_logs;
    }

    /**
     * Callback to allow sorting of example data.
     *
     * @param string $a First value.
     * @param string $b Second value.
     *
     * @return int
     */
    protected function usort_reorder( $a, $b ) {
        // If no sort, default to title.
        $orderby = ! empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'post_title'; // WPCS: Input var ok.

        // If no order, default to asc.
        $order = ! empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.

        // Determine sort order.
        $result = strcmp( $a[ $orderby ], $b[ $orderby ] );

        return ( 'asc' === $order ) ? $result : - $result;
    }
}

// Create an instance of our package class.
$WP_Logs_List = new WP_Logs_List();

// Fetch, prepare, sort, and filter our data.
$WP_Logs_List->prepare_items();

?>
<div class="ldrt-log-table-wrap">
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="ldrt-retake-logs" method="get">
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="ld-retake-quiz-options" />
        <input type="hidden" name="tab" value="debug-logs" />
        <!-- <input class="button button-primary ld-cie-button" type="button" name="action" value="export" /> -->
        <!-- Now we can render the completed list table -->
        <?php $WP_Logs_List->display(); ?>
    </form>

</div>