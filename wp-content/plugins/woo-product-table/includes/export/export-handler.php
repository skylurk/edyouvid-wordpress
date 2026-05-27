<?php
/**
 * Export Handler for Woo Product Table
 * 
 * Handles export functionality for CSV, PDF, Excel, and HTML formats
 * 
 * @package WOO_PRODUCT_TABLE
 * @since 5.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WPT_Export_Handler
 * 
 * Handles all export operations for product tables
 */
class WPT_Export_Handler {

    /**
     * Instance of this class
     *
     * @var WPT_Export_Handler
     */
    private static $instance = null;

    /**
     * Table ID for export
     *
     * @var int
     */
    private $table_id;

    /**
     * Get instance of this class
     *
     * @return WPT_Export_Handler
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'wp_ajax_wpt_export_table', array( $this, 'handle_export' ) );
        add_action( 'wp_ajax_nopriv_wpt_export_table', array( $this, 'handle_export' ) );
    }

    /**
     * Handle export AJAX request
     *
     * @return void
     */
    public function handle_export() {
        // Verify nonce
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, WPT_PLUGIN_FOLDER_NAME ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'woo-product-table' ) ) );
            wp_die();
        }

        $this->table_id = isset( $_POST['table_id'] ) ? absint( $_POST['table_id'] ) : 0;
        $format = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : 'html';

        if ( ! $this->table_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid table ID.', 'woo-product-table' ) ) );
            wp_die();
        }

        // Check if export is enabled for this table (default to enabled for new tables)
        $meta_basics = get_post_meta( $this->table_id, 'basics', true );
        $export_enabled = isset( $meta_basics['export_enable'] ) ? $meta_basics['export_enable'] === 'on' : true;

        if ( ! $export_enabled ) {
            wp_send_json_error( array( 'message' => __( 'Export is not enabled for this table.', 'woo-product-table' ) ) );
            wp_die();
        }

        // All export formats are now available in free version

        // Get products data
        $products_data = $this->get_products_data();

        if ( empty( $products_data['products'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No products found to export.', 'woo-product-table' ) ) );
            wp_die();
        }

        // Process export based on format
        switch ( $format ) {
            case 'html':
                $this->export_html( $products_data );
                break;
            case 'csv':
                $this->export_csv( $products_data );
                break;
            case 'pdf':
                $this->export_pdf( $products_data );
                break;
            case 'excel':
                $this->export_excel( $products_data );
                break;
            case 'xml':
                $this->export_xml( $products_data );
                break;
            case 'json':
                $this->export_json( $products_data );
                break;
            case 'ods':
                $this->export_ods( $products_data );
                break;
            default:
                wp_send_json_error( array( 'message' => __( 'Invalid export format.', 'woo-product-table' ) ) );
        }

        wp_die();
    }

    /**
     * Columns to exclude from export
     * These columns don't make sense in exported files
     *
     * @var array
     */
    private $excluded_columns = array(
        'action',
        'check',
        'tick',
        'quantity',
        'total',
        'message',
        'quick_view',
        'quick',
        'wishlist',
        'quoterequest',
        'variations',
        'buy_link',
    );

    /**
     * Generate export filename
     * Format: TableName-SiteName-Date-Time.extension
     *
     * @param string $extension File extension (html, csv, pdf, xlsx, xml, json, ods)
     * @return string Sanitized filename
     */
    private function generate_export_filename( $extension ) {
        $table_post = get_post( $this->table_id );
        $table_name = $table_post ? $table_post->post_title : __( 'Product Table', 'woo-product-table' );
        $site_name = get_bloginfo( 'name' );
        $datetime = wp_date( 'Y-m-d-His' ); // Format: 2025-12-03-050500 (with seconds)
        
        // Build filename: TableName-SiteName-DateTime.extension
        $filename = $table_name . '-' . $site_name . '-' . $datetime . '.' . $extension;
        
        return sanitize_file_name( $filename );
    }

    /**
     * Get products data based on table configuration
     *
     * @return array
     */
    private function get_products_data() {
        $enabled_columns = get_post_meta( $this->table_id, 'enabled_column_array', true );
        $column_array = get_post_meta( $this->table_id, 'column_array', true );
        $column_settings = get_post_meta( $this->table_id, 'column_settings', true );
        $meta_basics = get_post_meta( $this->table_id, 'basics', true );
        $conditions = get_post_meta( $this->table_id, 'conditions', true );

        // Filter out excluded columns
        if ( is_array( $enabled_columns ) ) {
            $enabled_columns = array_diff_key( $enabled_columns, array_flip( $this->excluded_columns ) );
        }

        /**
         * Maximum number of products to export
         * Default is 5000 to prevent memory issues on shared hosting
         * Can be modified via filter
         * Default is 1000 to prevent memory issues on shared hosting
         * 
         * @param int $max_products Maximum products to export
         * @param int $table_id Table ID
         */
        $max_products = apply_filters( 'wpt_export_max_products', 1000, $this->table_id );

        // Build query args
        $args = array(
            'post_type'      => isset( $meta_basics['product_type'] ) && ! empty( $meta_basics['product_type'] ) ? $meta_basics['product_type'] : 'product',
            'posts_per_page' => $max_products,
            'post_status'    => 'publish',
        );

        // Apply taxonomy filters if set
        if ( ! empty( $meta_basics['args']['tax_query'] ) ) {
            $args['tax_query'] = $meta_basics['args']['tax_query'];
        }

        // Apply meta query if set
        if ( ! empty( $meta_basics['args']['meta_query'] ) ) {
            $args['meta_query'] = $meta_basics['args']['meta_query'];
        }

        // Apply orderby
        if ( ! empty( $conditions['orderby'] ) ) {
            $args['orderby'] = $conditions['orderby'];
        }

        if ( ! empty( $conditions['order'] ) ) {
            $args['order'] = $conditions['order'];
        }

        /**
         * Filter the export query args
         * 
         * @param array $args Query arguments
         * @param int $table_id Table ID
         */
        $args = apply_filters( 'wpt_export_query_args', $args, $this->table_id );

        $query = new WP_Query( $args );
        $products = array();
        $image_columns = array( 'thumbnails', 'image' ); // Columns that contain images

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $current_product = wc_get_product( get_the_ID() );
                
                if ( ! $current_product ) {
                    continue;
                }

                $product_data = array();
                $product_data_raw = array(); // Store raw data with column keys for image detection

                if ( is_array( $enabled_columns ) ) {
                    foreach ( $enabled_columns as $column_key => $column_value ) {
                        $column_label = isset( $column_array[ $column_key ] ) ? $column_array[ $column_key ] : $column_key;
                        $value = $this->get_column_value( $column_key, $current_product );
                        $product_data[ $column_label ] = $value;
                        $product_data_raw[ $column_key ] = $value;
                    }
                }

                // Always add product URL at the end
                $product_url = get_permalink( $current_product->get_id() );
                $product_data[ __( 'Details', 'woo-product-table' ) ] = $product_url;
                $product_data_raw['product_details_url'] = $product_url;

                $products[] = array(
                    'display' => $product_data,
                    'raw'     => $product_data_raw,
                );
            }
        }

        wp_reset_postdata();

        // Get column headers with keys
        $headers = array();
        $header_keys = array();
        if ( is_array( $enabled_columns ) ) {
            foreach ( $enabled_columns as $column_key => $column_value ) {
                $headers[ $column_key ] = isset( $column_array[ $column_key ] ) ? $column_array[ $column_key ] : $column_key;
                $header_keys[] = $column_key;
            }
        }

        // Always add Details header at the end
        $headers['product_details_url'] = __( 'Details', 'woo-product-table' );
        $header_keys[] = 'product_details_url';

        return array(
            'headers'       => $headers,
            'header_keys'   => $header_keys,
            'products'      => $products,
            'image_columns' => $image_columns,
        );
    }

    /**
     * Get column value for a product
     *
     * @param string $column_key Column key
     * @param WC_Product $product Product object
     * @return string
     */
    private function get_column_value( $column_key, $product ) {
        $value = '';

        switch ( $column_key ) {
            case 'product_title':
            case 'title':
                $value = $product->get_name();
                break;

            case 'thumbnails':
            case 'image':
                $image_id = $product->get_image_id();
                $value = $image_id ? wp_get_attachment_url( $image_id ) : '';
                break;

            case 'price':
                $value = wp_strip_all_tags( wc_price( $product->get_price() ) );
                break;

            case 'regular_price':
                $value = wp_strip_all_tags( wc_price( $product->get_regular_price() ) );
                break;

            case 'sale_price':
                $sale_price = $product->get_sale_price();
                $value = $sale_price ? wp_strip_all_tags( wc_price( $sale_price ) ) : '';
                break;

            case 'sku':
                $value = $product->get_sku();
                break;

            case 'stock':
            case 'stock_status':
                $value = $product->is_in_stock() ? __( 'In Stock', 'woo-product-table' ) : __( 'Out of Stock', 'woo-product-table' );
                break;

            case 'stock_quantity':
                $stock_qty = $product->get_stock_quantity();
                $value = is_numeric( $stock_qty ) ? $stock_qty : '';
                break;

            case 'category':
            case 'categories':
                $terms = get_the_terms( $product->get_id(), 'product_cat' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $value = implode( ', ', wp_list_pluck( $terms, 'name' ) );
                }
                break;

            case 'tags':
                $terms = get_the_terms( $product->get_id(), 'product_tag' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                    $value = implode( ', ', wp_list_pluck( $terms, 'name' ) );
                }
                break;

            case 'description':
            case 'short_description':
                $value = wp_strip_all_tags( $product->get_short_description() );
                break;

            case 'long_description':
                $value = wp_strip_all_tags( $product->get_description() );
                break;

            case 'weight':
                $value = $product->get_weight();
                break;

            case 'dimensions':
                $value = wc_format_dimensions( $product->get_dimensions( false ) );
                break;

            case 'rating':
                $value = $product->get_average_rating();
                break;

            case 'reviews':
            case 'review_count':
                $value = $product->get_review_count();
                break;

            case 'date':
            case 'date_created':
                $date = $product->get_date_created();
                $value = $date ? $date->format( get_option( 'date_format' ) ) : '';
                break;

            case 'product_id':
                $value = $product->get_id();
                break;

            case 'product_link':
            case 'link':
                $value = get_permalink( $product->get_id() );
                break;

            default:
                /**
                 * Filter to get custom column value
                 * 
                 * @param string $value Column value
                 * @param string $column_key Column key
                 * @param WC_Product $product Product object
                 * @param int $table_id Table ID
                 */
                $value = apply_filters( 'wpt_export_column_value', $value, $column_key, $product, $this->table_id );
                break;
        }

        return $value;
    }

    /**
     * Export as HTML
     *
     * @param array $data Products data
     * @return void
     */
    private function export_html( $data ) {
        $site_name = get_bloginfo( 'name' );
        $table_post = get_post( $this->table_id );
        $table_name = $table_post ? $table_post->post_title : __( 'Product Table', 'woo-product-table' );
        $image_columns = $data['image_columns'];
        $header_keys = $data['header_keys'];

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $table_name . ' - ' . $site_name ); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .export-container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .export-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .export-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .export-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .export-info {
            display: flex;
            justify-content: space-between;
            padding: 15px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            color: #666;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #343a40;
            color: #fff;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        tr:hover {
            background: #e9ecef;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        .details-link {
            display: inline-block;
            padding: 6px 14px;
            background: #667eea;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.2s ease;
        }
        .details-link:hover {
            background: #5a6fd6;
        }
        .export-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 12px;
            color: #888;
        }
        .export-footer a {
            color: #667eea;
            text-decoration: none;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .export-container {
                box-shadow: none;
            }
            .export-header {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="export-container">
        <div class="export-header">
            <h1><?php echo esc_html( $table_name ); ?></h1>
            <p><?php echo esc_html( $site_name ); ?></p>
        </div>
        <div class="export-info">
            <span><?php printf( esc_html__( 'Total Products: %d', 'woo-product-table' ), count( $data['products'] ) ); ?></span>
            <span><?php printf( esc_html__( 'Exported on: %s', 'woo-product-table' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></span>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <?php foreach ( $data['headers'] as $header ) : ?>
                            <th><?php echo esc_html( $header ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $data['products'] as $product ) : ?>
                        <tr>
                            <?php 
                            $col_index = 0;
                            foreach ( $product['raw'] as $col_key => $value ) : 
                                $is_image = in_array( $col_key, $image_columns, true );
                                $is_details_url = ( $col_key === 'product_details_url' );
                            ?>
                                <td>
                                    <?php if ( $is_details_url && ! empty( $value ) ) : ?>
                                        <a href="<?php echo esc_url( $value ); ?>" target="_blank" class="details-link"><?php esc_html_e( 'Details', 'woo-product-table' ); ?></a>
                                    <?php elseif ( $is_image && ! empty( $value ) ) : ?>
                                        <img src="<?php echo esc_url( $value ); ?>" alt="Product Image" class="product-image" />
                                    <?php else : ?>
                                        <?php echo esc_html( $value ); ?>
                                    <?php endif; ?>
                                </td>
                            <?php 
                                $col_index++;
                            endforeach; 
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="export-footer">
            <?php 
            $link_html = '<a href="' . esc_url( 'https://wooproducttable.com' ) . '" target="_blank">' . esc_html__( 'Woo Product Table', 'woo-product-table' ) . '</a>';
            /* translators: %s: Link to Woo Product Table website */
            echo wp_kses( 
                sprintf( __( 'Generated by %s', 'woo-product-table' ), $link_html ),
                array( 'a' => array( 'href' => array(), 'target' => array() ) )
            ); 
            ?>
        </div>
    </div>
</body>
</html>
        <?php
        $html_content = ob_get_clean();

        wp_send_json_success( array(
            'content'  => $html_content,
            'filename' => $this->generate_export_filename( 'html' ),
            'type'     => 'html',
        ) );
    }

    /**
     * Export as CSV
     *
     * @param array $data Products data
     * @return void
     */
    private function export_csv( $data ) {
        // Build CSV content
        $csv_content = '';

        // Add headers
        $headers = array_values( $data['headers'] );
        $csv_content .= '"' . implode( '","', array_map( 'esc_html', $headers ) ) . '"' . "\n";

        // Add product rows
        foreach ( $data['products'] as $product ) {
            $row_values = array();
            foreach ( $product['raw'] as $value ) {
                // Escape double quotes and wrap in quotes
                $escaped_value = str_replace( '"', '""', $value );
                $row_values[] = '"' . $escaped_value . '"';
            }
            $csv_content .= implode( ',', $row_values ) . "\n";
        }

        // Add UTF-8 BOM for Excel compatibility
        $csv_content = "\xEF\xBB\xBF" . $csv_content;

        wp_send_json_success( array(
            'content'  => $csv_content,
            'filename' => $this->generate_export_filename( 'csv' ),
            'type'     => 'csv',
        ) );
    }

    /**
     * Export as PDF
     * Uses HTML with print-friendly styling for PDF generation
     *
     * @param array $data Products data
     * @return void
     */
    private function export_pdf( $data ) {
        $site_name = get_bloginfo( 'name' );
        $table_post = get_post( $this->table_id );
        $table_name = $table_post ? $table_post->post_title : __( 'Product Table', 'woo-product-table' );
        $image_columns = $data['image_columns'];

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $table_name . ' - ' . $site_name ); ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .pdf-container {
            width: 100%;
        }
        .pdf-header {
            text-align: center;
            padding: 15px 0;
            border-bottom: 2px solid #333;
            margin-bottom: 15px;
        }
        .pdf-header h1 {
            font-size: 18pt;
            color: #333;
            margin-bottom: 5px;
        }
        .pdf-header p {
            font-size: 10pt;
            color: #666;
        }
        .pdf-info {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            margin-bottom: 10px;
            font-size: 9pt;
            color: #666;
            border-bottom: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #333;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 9pt;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border: 1px solid #ddd;
        }
        .details-link {
            display: inline-block;
            padding: 4px 10px;
            background: #333;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            font-size: 8pt;
        }
        .pdf-footer {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #999;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="pdf-header">
            <h1><?php echo esc_html( $table_name ); ?></h1>
            <p><?php echo esc_html( $site_name ); ?></p>
        </div>
        <div class="pdf-info">
            <span><?php printf( esc_html__( 'Total Products: %d', 'woo-product-table' ), count( $data['products'] ) ); ?></span>
            <span><?php printf( esc_html__( 'Date: %s', 'woo-product-table' ), wp_date( get_option( 'date_format' ) ) ); ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <?php foreach ( $data['headers'] as $header ) : ?>
                        <th><?php echo esc_html( $header ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $data['products'] as $product ) : ?>
                    <tr>
                        <?php foreach ( $product['raw'] as $col_key => $value ) : 
                            $is_image = in_array( $col_key, $image_columns, true );
                            $is_details_url = ( $col_key === 'product_details_url' );
                        ?>
                            <td>
                                <?php if ( $is_details_url && ! empty( $value ) ) : ?>
                                    <a href="<?php echo esc_url( $value ); ?>" target="_blank" class="details-link"><?php esc_html_e( 'Details', 'woo-product-table' ); ?></a>
                                <?php elseif ( $is_image && ! empty( $value ) ) : ?>
                                    <img src="<?php echo esc_url( $value ); ?>" alt="Product" class="product-image" />
                                <?php else : ?>
                                    <?php echo esc_html( $value ); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="pdf-footer">
            <?php 
            /* translators: %s: Website URL */
            printf( esc_html__( 'Generated from %s using Woo Product Table', 'woo-product-table' ), esc_html( site_url() ) ); 
            ?>
        </div>
    </div>
    <script>
        // Auto print when opened
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
        <?php
        $pdf_content = ob_get_clean();

        wp_send_json_success( array(
            'content'  => $pdf_content,
            'filename' => $this->generate_export_filename( 'pdf.html' ),
            'type'     => 'pdf',
        ) );
    }

    /**
     * Export as Excel (XLSX format)
     * Uses Office Open XML format compatible with modern Excel
     *
     * @param array $data Products data
     * @return void
     */
    private function export_excel( $data ) {
        $table_post = get_post( $this->table_id );
        $table_name = $table_post ? $table_post->post_title : __( 'Product Table', 'woo-product-table' );
        $sheet_name = substr( preg_replace( '/[^a-zA-Z0-9\s]/', '', $table_name ), 0, 30 );
        if ( empty( $sheet_name ) ) {
            $sheet_name = 'Products';
        }

        // Create the XLSX content using Office Open XML format
        $xlsx_content = $this->create_xlsx_content( $data, $sheet_name );

        wp_send_json_success( array(
            'content'  => base64_encode( $xlsx_content ),
            'filename' => $this->generate_export_filename( 'xlsx' ),
            'type'     => 'excel',
            'encoding' => 'base64',
        ) );
    }

    /**
     * Create XLSX content using Office Open XML format
     *
     * @param array $data Products data
     * @param string $sheet_name Sheet name
     * @return string Binary content of XLSX file
     */
    private function create_xlsx_content( $data, $sheet_name ) {
        // Create a temporary file
        $temp_file = tempnam( sys_get_temp_dir(), 'xlsx' );
        
        // Create ZIP archive
        $zip = new ZipArchive();
        $zip->open( $temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );

        // Add [Content_Types].xml
        $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';
        $zip->addFromString( '[Content_Types].xml', $content_types );

        // Add _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString( '_rels/.rels', $rels );

        // Add xl/_rels/workbook.xml.rels
        $workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        $zip->addFromString( 'xl/_rels/workbook.xml.rels', $workbook_rels );

        // Add xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="' . esc_attr( $sheet_name ) . '" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
        $zip->addFromString( 'xl/workbook.xml', $workbook );

        // Add xl/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF333333"/></patternFill></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
</styleSheet>';
        $zip->addFromString( 'xl/styles.xml', $styles );

        // Build shared strings and sheet data
        $shared_strings = array();
        $string_index = 0;
        
        // Add headers to shared strings
        foreach ( $data['headers'] as $header ) {
            $shared_strings[ $header ] = $string_index++;
        }
        
        // Add product data to shared strings
        foreach ( $data['products'] as $product ) {
            foreach ( $product['raw'] as $value ) {
                $str_value = (string) $value;
                if ( ! isset( $shared_strings[ $str_value ] ) ) {
                    $shared_strings[ $str_value ] = $string_index++;
                }
            }
        }

        // Create sharedStrings.xml
        $shared_strings_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count( $shared_strings ) . '" uniqueCount="' . count( $shared_strings ) . '">';
        foreach ( array_keys( $shared_strings ) as $string ) {
            $shared_strings_xml .= '<si><t>' . htmlspecialchars( $string, ENT_XML1, 'UTF-8' ) . '</t></si>';
        }
        $shared_strings_xml .= '</sst>';
        $zip->addFromString( 'xl/sharedStrings.xml', $shared_strings_xml );

        // Create sheet1.xml
        $sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';
        
        // Add header row
        $sheet_xml .= '<row r="1">';
        $col = 0;
        foreach ( $data['headers'] as $header ) {
            $col_letter = $this->get_column_letter( $col );
            $sheet_xml .= '<c r="' . $col_letter . '1" t="s" s="1"><v>' . $shared_strings[ $header ] . '</v></c>';
            $col++;
        }
        $sheet_xml .= '</row>';

        // Add data rows
        $row_num = 2;
        foreach ( $data['products'] as $product ) {
            $sheet_xml .= '<row r="' . $row_num . '">';
            $col = 0;
            foreach ( $product['raw'] as $value ) {
                $col_letter = $this->get_column_letter( $col );
                $str_value = (string) $value;
                $sheet_xml .= '<c r="' . $col_letter . $row_num . '" t="s"><v>' . $shared_strings[ $str_value ] . '</v></c>';
                $col++;
            }
            $sheet_xml .= '</row>';
            $row_num++;
        }

        $sheet_xml .= '</sheetData></worksheet>';
        $zip->addFromString( 'xl/worksheets/sheet1.xml', $sheet_xml );

        $zip->close();

        // Read the file content
        $content = file_get_contents( $temp_file );
        
        // Delete temp file
        unlink( $temp_file );

        return $content;
    }

    /**
     * Get column letter from column index (0-based)
     *
     * @param int $col Column index
     * @return string Column letter (A, B, C, ... Z, AA, AB, etc.)
     */
    private function get_column_letter( $col ) {
        $letter = '';
        while ( $col >= 0 ) {
            $letter = chr( $col % 26 + 65 ) . $letter;
            $col = intval( $col / 26 ) - 1;
        }
        return $letter;
    }

    /**
     * Export as XML
     *
     * @param array $data Products data
     * @return void
     */
    private function export_xml( $data ) {
        $table_post = get_post( $this->table_id );
        $table_name = $table_post ? $table_post->post_title : __( 'Product Table', 'woo-product-table' );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<products>' . "\n";
        $xml .= '    <meta>' . "\n";
        $xml .= '        <table_name>' . htmlspecialchars( $table_name, ENT_XML1, 'UTF-8' ) . '</table_name>' . "\n";
        $xml .= '        <export_date>' . wp_date( 'Y-m-d H:i:s' ) . '</export_date>' . "\n";
        $xml .= '        <total_products>' . count( $data['products'] ) . '</total_products>' . "\n";
        $xml .= '    </meta>' . "\n";

        foreach ( $data['products'] as $index => $product ) {
            $xml .= '    <product id="' . ( $index + 1 ) . '">' . "\n";
            foreach ( $data['headers'] as $key => $header ) {
                $safe_key = preg_replace( '/[^a-zA-Z0-9_]/', '_', $key );
                $value = isset( $product['raw'][ $key ] ) ? $product['raw'][ $key ] : '';
                $xml .= '        <' . $safe_key . '>' . htmlspecialchars( $value, ENT_XML1, 'UTF-8' ) . '</' . $safe_key . '>' . "\n";
            }
            $xml .= '    </product>' . "\n";
        }

        $xml .= '</products>';

        wp_send_json_success( array(
            'content'  => $xml,
            'filename' => $this->generate_export_filename( 'xml' ),
            'type'     => 'xml',
        ) );
    }

    /**
     * Export as JSON
     *
     * @param array $data Products data
     * @return void
     */
    private function export_json( $data ) {
        $table_post = get_post( $this->table_id );
        $table_name = $table_post ? $table_post->post_title : __( 'Product Table', 'woo-product-table' );

        $json_data = array(
            'meta' => array(
                'table_name'     => $table_name,
                'export_date'    => wp_date( 'Y-m-d H:i:s' ),
                'total_products' => count( $data['products'] ),
            ),
            'columns' => array_values( $data['headers'] ),
            'products' => array(),
        );

        foreach ( $data['products'] as $product ) {
            $product_data = array();
            foreach ( $data['headers'] as $key => $header ) {
                $product_data[ $header ] = isset( $product['raw'][ $key ] ) ? $product['raw'][ $key ] : '';
            }
            $json_data['products'][] = $product_data;
        }

        $json_content = wp_json_encode( $json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

        wp_send_json_success( array(
            'content'  => $json_content,
            'filename' => $this->generate_export_filename( 'json' ),
            'type'     => 'json',
        ) );
    }

    /**
     * Export as ODS (OpenDocument Spreadsheet)
     *
     * @param array $data Products data
     * @return void
     */
    private function export_ods( $data ) {
        $table_post = get_post( $this->table_id );
        $table_name = $table_post ? $table_post->post_title : __( 'Product Table', 'woo-product-table' );
        $sheet_name = substr( preg_replace( '/[^a-zA-Z0-9\s]/', '', $table_name ), 0, 30 );
        if ( empty( $sheet_name ) ) {
            $sheet_name = 'Products';
        }

        // Create the ODS content
        $ods_content = $this->create_ods_content( $data, $sheet_name );

        wp_send_json_success( array(
            'content'  => base64_encode( $ods_content ),
            'filename' => $this->generate_export_filename( 'ods' ),
            'type'     => 'ods',
            'encoding' => 'base64',
        ) );
    }

    /**
     * Create ODS content
     *
     * @param array $data Products data
     * @param string $sheet_name Sheet name
     * @return string Binary content of ODS file
     */
    private function create_ods_content( $data, $sheet_name ) {
        // Create a temporary file
        $temp_file = tempnam( sys_get_temp_dir(), 'ods' );
        
        // Create ZIP archive
        $zip = new ZipArchive();
        $zip->open( $temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE );

        // Add mimetype (must be first and uncompressed)
        $zip->addFromString( 'mimetype', 'application/vnd.oasis.opendocument.spreadsheet' );
        $zip->setCompressionName( 'mimetype', ZipArchive::CM_STORE );

        // Add META-INF/manifest.xml
        $manifest = '<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
    <manifest:file-entry manifest:full-path="/" manifest:media-type="application/vnd.oasis.opendocument.spreadsheet"/>
    <manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
    <manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>
</manifest:manifest>';
        $zip->addFromString( 'META-INF/manifest.xml', $manifest );

        // Add styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
    <office:styles>
        <style:style style:name="Header" style:family="table-cell">
            <style:text-properties fo:font-weight="bold" fo:color="#FFFFFF"/>
            <style:table-cell-properties fo:background-color="#333333"/>
        </style:style>
    </office:styles>
</office:document-styles>';
        $zip->addFromString( 'styles.xml', $styles );

        // Add content.xml
        $content = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
    xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
    xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
    xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
    xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
    <office:automatic-styles>
        <style:style style:name="ce1" style:family="table-cell">
            <style:text-properties fo:font-weight="bold" fo:color="#FFFFFF"/>
            <style:table-cell-properties fo:background-color="#333333"/>
        </style:style>
    </office:automatic-styles>
    <office:body>
        <office:spreadsheet>
            <table:table table:name="' . esc_attr( $sheet_name ) . '">';

        // Add header row
        $content .= '<table:table-row>';
        foreach ( $data['headers'] as $header ) {
            $content .= '<table:table-cell table:style-name="ce1" office:value-type="string"><text:p>' . htmlspecialchars( $header, ENT_XML1, 'UTF-8' ) . '</text:p></table:table-cell>';
        }
        $content .= '</table:table-row>';

        // Add data rows
        foreach ( $data['products'] as $product ) {
            $content .= '<table:table-row>';
            foreach ( $product['raw'] as $value ) {
                $content .= '<table:table-cell office:value-type="string"><text:p>' . htmlspecialchars( (string) $value, ENT_XML1, 'UTF-8' ) . '</text:p></table:table-cell>';
            }
            $content .= '</table:table-row>';
        }

        $content .= '</table:table></office:spreadsheet></office:body></office:document-content>';
        $zip->addFromString( 'content.xml', $content );

        $zip->close();

        // Read the file content
        $file_content = file_get_contents( $temp_file );
        
        // Delete temp file
        unlink( $temp_file );

        return $file_content;
    }
}

// Initialize the export handler
WPT_Export_Handler::get_instance();
