<?php 
$product_id      = $product->get_id();
if(shortcode_exists('bizzview')){
    echo do_shortcode( '[bizzview id="' . esc_attr( $product_id ) . '"]' );  
}elseif( is_user_logged_in() ){
    //Download linkk for admin if shortcode not exists
    echo '<a href="https://wordpress.org/plugins/ca-quick-view/" target="_blank" class="bizzview-upgrade-button" title="' . esc_attr__( 'Download Bizzview - Quick View Plugin', 'woo-product-table' ) . '">' . __( '⬇️ Tool', 'woo-product-table' ) . '</a>';
}
?>