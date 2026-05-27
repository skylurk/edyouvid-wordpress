<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Retake_Log_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
                'singular' => 'bulk-delete',
		        'plural'   => 'bulk-deletes',
                'ajax'     => false
            )
        );
    }
        
    public function get_retake_log_data() {
        global $wpdb;
        $table_usermeta = $wpdb->prefix . 'usermeta';
        $table_users = $wpdb->prefix . 'users';
        if( learndash_is_group_leader_user() ) {
            $current_user = wp_get_current_user();
            $group_leaders_users = learndash_get_group_leader_groups_users( $current_user->ID );
            $user_ids = implode( ",", $group_leaders_users );
            $results_arr = $wpdb->get_results(
            "SELECT um.*, u.`user_email` FROM {$table_usermeta} um INNER JOIN {$table_users} u ON u.`ID` = um.`user_id` WHERE um.`meta_key` = '_ld_quiz_retake_user_attempts' AND um.`user_id` IN ({$user_ids}) ORDER BY um.`umeta_id` DESC"
                , 'ARRAY_A'
            );
        } else {
            $results_arr = $wpdb->get_results(
                "SELECT um.*, u.`user_email` FROM {$table_usermeta} um INNER JOIN {$table_users} u ON u.`ID` = um.`user_id` WHERE um.`meta_key`='_ld_quiz_retake_user_attempts' ORDER BY um.`umeta_id` DESC"
                , 'ARRAY_A'
            );
        }

        $results = array();
        foreach( $results_arr as $key => $result ) {
            
            $meta_value = maybe_unserialize( $result['meta_value'] );
            $is_deleted = isset( $meta_value['is_deleted'] ) ? $meta_value['is_deleted'] : null; 
            if( $is_deleted !== 1 ) {
                $meta_value['quiz_title'] = get_post_field( 'post_title', $meta_value['quiz_id'], 'raw' );
                $this->add_delete_key_to_all_records( $result, $table_usermeta, $result['umeta_id'] );
                $results[] = array_merge( $result, $meta_value );
            }
        }
        return $results;
    }

    function add_delete_key_to_all_records( $records, $table_usermeta, $umeta_id ) {

        foreach( $records as $key => $result ) {

            if( isset( $result['is_deleted'] ) && $result['is_deleted'] !== 1 ) {
                
                $result['is_deleted'] = 0;
                
                $wpdb->get_results( 
                    $wpdb->prepare( 
                        "UPDATE {$table_usermeta} SET meta_value = %s WHERE umeta_id = %d", 
                        serialize( $result ), 
                        absint( $umeta_id ) 
                    ) 
                );
            }
        }
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'user_email'    => 'User email',
            'quiz_title'    => 'Quiz',
            'datetime'      => 'DateTime'
        );
        return $columns;
    }

    function prepare_items() {
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = count($this->get_retake_log_data());

        $data = $this->get_retake_log_data();
        $data = array_slice($data, (($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        usort( $data, array( &$this, 'usort_reorder' ) );
        $this->items = $data;

        $this->delete_single_retake_log();
        $this->process_bulk_action();

    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'user_email':
            case 'quiz_title':
            case 'datetime':
            return $item[ $column_name ];
            default:
            return print_r( $item, true ) ;
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'user_email'    => array( 'user_email', false ),
            'quiz_title'    => array( 'quiz_title', false ),
            'datetime'      => array( 'datetime', false )
        );
        return $sortable_columns;
    }

    function usort_reorder( $a, $b ) {
        // If no sort, default to datetime
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'datetime';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }

    function column_user_email($item) {
        $actions = array(
            'delete'    => sprintf('<a href="?page=%s&tab=retake_quiz_logs&action=%s&log_id=%s">%s</a>',$_REQUEST['page'],'delete',$item['umeta_id'], __( 'Delete' )),
        );
        // return sprintf( '%1$s %2$s', $item['user_email'], $this->row_actions( $actions ) );
        return sprintf( '%1$s', $item['user_email'] );
    }

    function get_bulk_actions() {
        $actions = array(
            'bulk-delete' => __( 'Delete', 'ld_retake_quiz' ),
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="log_id[]" value="%s" />', $item['umeta_id']
        );    
    }

    function delete_single_retake_log() {
        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'delete' ) {
            if( isset( $_GET['log_id'] ) ) {
                global $wpdb;
                $log_id = absint( $_GET['log_id'] );

                $result = $wpdb->get_results( 
                        $wpdb->prepare( 
                            "SELECT * FROM $wpdb->usermeta WHERE umeta_id = %d", 
                            $log_id
                        ), 
                    ARRAY_A
                );

                $log = $result[0];
                $meta_value = maybe_unserialize( $log['meta_value'] );
                if( isset( $meta_value['is_deleted'] ) && $meta_value['is_deleted'] !== 1 ) {
                    $meta_value['is_deleted'] = 1;
                    $wpdb->get_results( 
                        $wpdb->prepare( 
                            "UPDATE $wpdb->usermeta SET meta_value = %s WHERE umeta_id = %d", 
                            serialize( $meta_value ), 
                            absint( $log['umeta_id'] ) 
                        ) 
                    );
                }

                if( isset( $_SERVER['HTTP_REFERER'] ) ) {
                    wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
                }
            }
        }
    }

    function process_bulk_action() {
        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'bulk-delete' ) {
            if( isset( $_GET['log_id'] ) && count( $_GET['log_id'] ) > 0 ) {
                $log_ids = $_GET['log_id'];
                global $wpdb;

                foreach( $log_ids as $log_id ) {
                    $result = $wpdb->get_results( 
                        $wpdb->prepare( "SELECT * FROM $wpdb->usermeta WHERE umeta_id = %d", 
                            $log_id
                        ),
                        ARRAY_A
                    );
                    foreach( $result as $log ) {
                        $meta_value = maybe_unserialize( $log['meta_value'] );
                        $meta_value['is_deleted'] = 1;
                        $wpdb->get_results( 
                            $wpdb->prepare( 
                                "UPDATE $wpdb->usermeta SET meta_value = %s WHERE umeta_id = %d", 
                                serialize( $meta_value ), 
                                absint( $log['umeta_id'] ) 
                            ) 
                        );
                    }
                }

                if( isset( $_SERVER['HTTP_REFERER'] ) ) {
                    wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
                }
            }
        }
    }

    function get_views(){
       $views = array();
       $current = ( ! empty( $_REQUEST['customvar'] ) ? $_REQUEST['customvar'] : 'all' );

       // All link
       $class = ( $current == 'all' ? ' class="current"' : '' );
       $all_url = remove_query_arg('customvar');
       $views['all'] = "<a href='{$all_url}' {$class} >All</a>";

       //Foo link
       $foo_url = add_query_arg('customvar','foo');
       $class = ($current == 'foo' ? ' class="current"' :'');
       $views['foo'] = "<a href='{$foo_url}' {$class} >Foo</a>";

       //Bar link
       $bar_url = add_query_arg('customvar','bar');
       $class = ($current == 'bar' ? ' class="current"' :'');
       $views['bar'] = "<a href='{$bar_url}' {$class} >Bar</a>";

       return $views;
    }

    protected function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }
       
        $count = count( $this->get_retake_log_data() );
        
        ?>
        <?php if ( $count ): ?>
        <!--Extra Nav-->
        <div class="alignleft ldat-extra-nav">
            <a href="javascript:void(0)" id="ldrt_clear_logs" data-clear_log_message="<?php echo __( 'Are you sure you want to clear logs?', 'ld_retake_quiz' ) ?>">Clear Logs</a>
        </div>
        <?php endif ?>
        <!--/Extra Nav-->
        <?php
    }
}

$retake_log_list_table = new retake_log_List_Table();
$retake_log_list_table->prepare_items(); 
?>
<div id="ldrt-log-table-wrap">
    <h2>Logs</h2>
    <form id="ldrt-retake-logs" method="get">
    <input type="hidden" name="tab" value="retake_quiz_logs" />
    <input type="hidden" name="page" value="ld-retake-quiz-options" />
        <?php $retake_log_list_table->display(); ?>
    </form>
</div>
<?php
