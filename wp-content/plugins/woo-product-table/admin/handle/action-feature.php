<?php
namespace WOO_PRODUCT_TABLE\Admin\Handle;

use WOO_PRODUCT_TABLE\Core\Base;

/**
 * Adding new Feature for Admin Action Column
 * 
 * 
 */
class Action_Feature extends Base 
{
    public function run()
    {
        //Any one can use
        // $this->action('wpto_column_setting_form_action',2, 10, 'third_party_switch' );
        add_action( 'wpto_column_setting_form_action', [$this, 'third_party_switch'], 10, 2 );

    }

    public function third_party_switch( $_device_name, $column_settings)
    {
        $third_party_plugin =  $column_settings['action']['third_party_plugin'] ?? '';
        if( empty($third_party_plugin) ){
            $third_party_plugin = 'advance_table';
        }

        $third_party_plugin_checkbox = $third_party_plugin == 'normal_table' ? 'checked="checked"' : '';

        ?>
        <label 
        title="<?php echo esc_attr__( 'Some feature may not work correctly when enabled.', 'woo-product-table' ); ?>"
        for="third_party_plugin<?php echo esc_attr( $_device_name ); ?>">
            <input id="third_party_plugin<?php echo esc_attr( $_device_name ); ?>" 
             
            name="column_settings<?php echo esc_attr( $_device_name ); ?>[action][third_party_plugin]" 
            id="third_party_plugin" 
            class="third_party_plugin" 
            value="normal_table"
            type="checkbox" <?php echo esc_attr( $third_party_plugin_checkbox ); ?>> 
            
            <?php echo esc_html__( 'Faster/Optimized (Optional - not recommended)', 'woo-product-table' ); ?>
        </label>
        <?php
        wpt_help_icon_render( __('Some features may not work correctly. But for optimized performance, you can use.', 'woo-product-table') );
        ?>
        <?php 
    }
}