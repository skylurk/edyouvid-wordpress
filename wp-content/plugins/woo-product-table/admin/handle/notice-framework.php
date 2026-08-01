<?php
namespace WOO_PRODUCT_TABLE\Admin\Handle;

use WP;

class Notice_Framework
{
    public $framework;
    private $offer_prefix = 'wpt-2offer-apr-26';
    private $start_date = '2026-04-01';
    private $end_date = '2026-04-30';
    
    public function __construct()
    {
        require_once WPT_BASE_DIR . 'framework/framework.php';
        $this->framework = \CA_Framework::init( 'woo-product-table', WPT_PLUGIN_FILE_NAME );

    }

    public function run()
    {
        
    }

    public function plugins_recommendation(){

        //get plugin data like following from get_recommended_plugins()
        $plugins = $this->framework->recommended_plugins($this->get_recommended_plugins(true), 'wpt-recommend-plugins');
        $plugins->show_on_hook('wpt_plugin_recommend_here');//->show();
        
        $bizzview_plugin = $this->framework->recommended_plugins($this->get_recommended_bizzview(), 'wpt-recommend-plugins');
        $bizzview_plugin->show_on_hook('wpto_column_setting_form_quick');
        $bizzview_plugin->show_on_hook('wpto_column_setting_form_inside_thumbnails');//->show();
        $bizzview_plugin->show_on_hook('wpto_column_setting_form_inside_product_title');//->show();

        $bizzmudra_plugin = $this->framework->recommended_plugins($this->get_recommended_bizzmudra(), 'wpt-recommend-plugins');
        $bizzmudra_plugin->show_on_hook('wpto_column_setting_form_inside_price');//->show();

        $actions_col_plugin = $this->framework->recommended_plugins($this->get_recommended_actions_col(), 'wpt-recommend-plugins');
        $actions_col_plugin->show_on_hook('wpto_column_setting_form_inside_action');//
        
    }

    public function offer_in_premium(){
        $this->framework->create_offer($this->get_offer_args(
            array(
                'id'            => $this->offer_prefix . '-in_premium',
                // 'pages'         => [], //automatically on our selected pages
                'template'      => 'starter',
                'title'         => 'CodeAstrology PLUGINS',
                'description'   => 'Grab your exclusive discount for EXCLUSING WooCommerce products <b>Limited time offer</b> just for you.',
                'reshow_after'  => 25,
                'image_url'     => WPT_ASSETS_URL . 'images/offer.png',
                'reshow_unit'   => 'hours',
                'dismiss'       => false,
                'randomize'     => 15,
                'buttons'       => array(
                    array(
                        'text'  => 'Get 50% OFF',
                        'url'   => 'https://codeastrology.com/products/',
                        'class' => 'ca-fw-btn-primary',
                        'icon'  => 'dashicons-cart',
                    ),
                    array(
                        'text'  => 'Premium Demo',
                        'url'   => 'https://wpprincipal.xyz/',
                        'class' => 'ca-fw-btn-primary',
                        'icon'  => 'dashicons-visibility',
                    ),
                ),
            )
        ))
        ->show()
        ->show_on_hook('wpt_plugin_recommend_here', 2);
    }
    

    public function offer_4_premium_in_free(){
        $this->framework->create_offer($this->get_offer_args(
            array(
                'id' => $this->offer_prefix . '-in_free-exclude',
                // 'badge_text' => 'Limited Time Offer',
                'pages' => array(),
                'pages_exclude'          => array('wpt', 'plugins', 'tools'),
                'randomize' => 15
            )
        ))
        ->show();

        $this->framework->create_offer($this->get_offer_args(
            array(
                'id' => $this->offer_prefix . '-in_free-include',
                // 'badge_text' => 'Limited Time Offer',
                // 'pages'          => array('wpt', 'plugins', 'tools', 'dashboard'),
                'template'      => 'flash',
                'reshow_after'  => 25,
                'reshow_unit'   => 'hours',
                //disable dismiss
                'dismiss' => false,
                'randomize' => 20,
            )
        ))
        ->show()
        ->show_on_hook('wpt_plugin_recommend_here');

        //normal popup inside plugin
        $this->framework->create_popup($this->get_popup_args(
            array(
                'id' => $this->offer_prefix . '-popup-include',
                'reshow_after'  => 25,
                'reshow_unit'   => 'hours',
                // 'start_date'    => '2026-04-01',
                'image_url' => WPT_ASSETS_URL . 'images/logo.png',
                
            )
        ))->show();



        //normal for outside
        $this->framework->create_popup($this->get_popup_args(
            array(
                'id' => $this->offer_prefix . '-popup-exclude',
                'reshow_after'  => 5,
                'reshow_unit'   => 'days',
                'image_url' => WPT_ASSETS_URL . 'images/logo.png',
                'pages' => array(),
                'pages_exclude' => array('wpt', 'plugins', 'tools'),

            )
        ))->show();
        
        
    }
    private function get_popup_args($new_args = array())
    {
        $default_args = $this->get_offer_args(
            array(
                'id' => $this->offer_prefix,
                'badge_text' => 'FLAT 50% OFF',
                'description'  => '<p>Upgrade to the Pro version and get:</p>
                        <ul>
                            <li>✅ Access to all premium features</li>
                            <li>✅ Unlimited templates, Access to new features</li>
                            <li>✅ Priority support</li>
                            <li>✅ Advanced customization</li>
                            <li>✅ Regular updates</li>
                        </ul>
                        <p>30-day money-back guarantee!</p>',
                // 'pages'          => array('wpt', 'plugins', 'tools', 'dashboard'),
                'template'      => 'flash',
                'reshow_after'  => 5,
                'reshow_unit'   => 'days',
                'randomize' => 15,
                'image_url' => WPT_ASSETS_URL . 'images/logo.png',
                'buttons' => array(
                    array(
                        'text'  => 'Upgrade Now - 50% OFF',
                        'url'   => 'https://wooproducttable.com/pricing/',
                        'class' => 'ca-fw-btn-primary',
                        'icon'  => 'dashicons-cart',
                    ),
                    array(
                        'text'  => 'Premium Demo',
                        'url'   => 'https://wpprincipal.xyz/?demo=wpt',
                        'class' => 'ca-fw-btn-outline',
                        'icon'  => 'dashicons-visibility',
                    ),
                )
            )
        );
        //use wp parse args function
        $final_args = wp_parse_args( $new_args, $default_args );

        return $final_args;

    }
    private function get_offer_args($new_args = array())
    {
        $default_args = array(
            'id' => $this->offer_prefix,
            'title'          => '🎉 Special discount for <strong>Woo Product Table</strong>',
            'description'    => 'Get your special discount now! <b>Limited time offer</b> just for you. Maximize your savings with this exclusive deal. Claim your discount!',
            'highlight_text' => 'FLAT 50% OFF',
            'badge_text'     => 'FLASH SALE',
            'template'       => 'developer', //flash, starter, simple
            'start_date'     => $this->start_date,
            'dismiss_type'   => 'temporary',
            'end_date'       => $this->end_date,
            'pages'          => array('wpt', 'plugins', 'tools', 'dashboard'),
            // 'pages_exclude'  => array('wpt-settings'),
            // 'show_countdown' => true,
            'reshow_unit'    => 'days',
            'reshow_after'   => 5,
            'image_url'    => WPT_ASSETS_URL . 'images/logo.gif',
            'randomize'     => 15,
            'buttons' => $this->get_wpt_purchase_buttons(),
        );
        //use wp parse args function
        $final_args = wp_parse_args( $new_args, $default_args );

        return $final_args;

    }

    private function get_wpt_purchase_buttons(){
        return array(
            array(
                'text'  => 'Claim Discount',
                'url'   => 'https://wooproducttable.com/pricing/',
                'class' => 'ca-fw-btn-primary',
                'icon'  => 'dashicons-cart',
                'target' => '_blank',
            ),
            array(
                'text'   => 'View Features',
                'url'    => 'https://wooproducttable.com/',
                'class'  => 'ca-fw-btn-secondary',
                'target' => '_blank',
            ),
            //wp org link of plugins
            array(
                'text'  => 'WordPress.org',
                'url'   => 'https://wordpress.org/plugins/woo-product-table/',
                'class' => 'ca-fw-btn-secondary',
                'target' => '_blank',
            ),
        );
    }

    /**
	 * Get recommended plugins list.
	 *
	 * @param bool $shorted Whether to return a shuffled subset.
	 * @return array
	 */
	private function get_recommended_plugins( $shorted = false ) {
		$plugins = array(
			array(
				'slug'        => 'woo-product-table',
				'name'        => 'Product Table for WooCommerce',
				'description' => __( 'Display WooCommerce products in a table layout.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/woo-product-table/assets/icon-256x256.gif',
				'author'      => 'Bizzplugin',
				'path'        => 'woo-product-table/woo-product-table.php',
				'url'         => 'https://wordpress.org/plugins/woo-product-table/',
			),
			array(
				'slug'        => 'woo-min-max-quantity-step-control-single',
				'name'        => 'Min Max Control - Control Quantity for WooCommerce',
				'description' => __( 'Control minimum and maximum quantities and step values for WooCommerce products.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/woo-min-max-quantity-step-control-single/assets/icon-256x256.png',
				'author'      => 'Bizzplugin',
				'path'        => 'woo-min-max-quantity-step-control-single/wcmmq.php',
				'url'         => 'https://wordpress.org/plugins/woo-min-max-quantity-step-control-single/',
			),
			array(
				'slug'        => 'product-sync-master-sheet',
				'name'        => 'Sync Master Sheet - Sync with Google Sheet',
				'description' => __( 'Sync your product data with a google sheet.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/product-sync-master-sheet/assets/icon-256x256.gif',
				'author'      => 'Bizzplugin',
				'path'        => 'product-sync-master-sheet/product-sync-master-sheet.php',
				'url'         => 'https://wordpress.org/plugins/product-sync-master-sheet/',
			),
			array(
				'slug'        => 'ca-quick-view',
				'name'        => 'Bizzview - Quick View for WooCommerce',
				'description' => __( 'Add quick view functionality to your WooCommerce products.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/ca-quick-view/assets/icon-256x256.png?changed',
				'author'      => 'Bizzplugin',
				'path'        => 'ca-quick-view/ca-quick-view.php',
				'url'         => 'https://wordpress.org/plugins/ca-quick-view/',
			),
			array(
				'slug'        => 'bizzswatches',
				'name'        => 'Bizzswatches - Color and Image Swatches',
				'description' => __( 'Add color and image swatches to your WooCommerce products.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/bizzswatches/assets/icon-256x256.png',
				'author'      => 'Bizzplugin',
				'path'        => 'bizzswatches/bizzswatches.php',
				'url'         => 'https://wordpress.org/plugins/bizzswatches/',
			),
			array(
				'slug'        => 'wc-quantity-plus-minus-button',
				'name'        => 'Quantity Plus Minus Button for WooCommerce',
				'description' => __( 'Add plus and minus buttons to WooCommerce quantity fields.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/wc-quantity-plus-minus-button/assets/icon-256x256.png',
				'author'      => 'Bizzplugin',
				'path'        => 'wc-quantity-plus-minus-button/init.php',
				'url'         => 'https://wordpress.org/plugins/wc-quantity-plus-minus-button/',
			),
			array(
				'slug'        => 'bizzmudra',
				'name'        => 'Bizzmudra - Multi Currency Switcher',
				'description' => __( 'A multi currency switcher for WooCommerce.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/bizzmudra/assets/icon-256x256.png',
				'author'      => 'Bizzplugin',
				'path'        => 'bizzmudra/bizzmudra.php',
				'url'         => 'https://wordpress.org/plugins/bizzmudra/',
			),
			array(
				'slug'        => 'sheet-to-wp-table-for-google-sheet',
				'name'        => 'Sheet to Table Live Sync for Google Sheet',
				'description' => __( 'Display Google Sheet data in WordPress tables with live sync.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/sheet-to-wp-table-for-google-sheet/assets/icon-256x256.png',
				'author'      => 'Bizzplugin',
				'path'        => 'sheet-to-wp-table-for-google-sheet/sheet-to-wp-table-for-google-sheet.php',
				'url'         => 'https://wordpress.org/plugins/sheet-to-wp-table-for-google-sheet/',
			),
		);

		if ( $shorted ) {
			shuffle( $plugins );
			$plugins = array_slice( $plugins, 0, 6 );
		}

		return $plugins;
	}
	private function get_recommended_bizzview() {
		$plugins = array(
			
			array(
				'slug'        => 'ca-quick-view',
				'name'        => 'Bizzview - Quick View for WooCommerce',
				'description' => __( 'Add quick view functionality to your WooCommerce products.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/ca-quick-view/assets/icon-256x256.png?changed',
				'author'      => 'Bizzplugin',
				'path'        => 'ca-quick-view/ca-quick-view.php',
				'url'         => 'https://wordpress.org/plugins/ca-quick-view/',
			),
		);

		return $plugins;
	}

    private function get_recommended_bizzmudra() {
        $plugins = array(
            
            array(
                'slug'        => 'bizzmudra',
                'name'        => 'Bizzmudra - Multi Currency Switcher',
                'description' => __( 'A multi currency switcher for WooCommerce.', 'bizzswatches' ),
                'icon'   => 'https://ps.w.org/bizzmudra/assets/icon-256x256.png',
                'author'      => 'Bizzplugin',
                'path'        => 'bizzmudra/bizzmudra.php',
                'url'         => 'https://wordpress.org/plugins/bizzmudra/',
            ),
        );  
        return $plugins;
    }

    //bizzswatches recommend only for product title column setting page
    private function get_recommended_actions_col() {
        $plugins = array(
            
            array(
                'slug'        => 'bizzswatches',
                'name'        => 'Bizzswatches - Color and Image Swatches',
                'description' => __( 'Add color and image swatches to your WooCommerce products.', 'bizzswatches' ),
                'icon'   => 'https://ps.w.org/bizzswatches/assets/icon-256x256.png',
                'author'      => 'Bizzplugin',
                'path'        => 'bizzswatches/bizzswatches.php',
                'url'         => 'https://wordpress.org/plugins/bizzswatches/',
            ),
            array(
				'slug'        => 'woo-min-max-quantity-step-control-single',
				'name'        => 'Min Max Control - Control Quantity for WooCommerce',
				'description' => __( 'Control minimum and maximum quantities and step values for WooCommerce products.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/woo-min-max-quantity-step-control-single/assets/icon-256x256.png',
				'author'      => 'Bizzplugin',
				'path'        => 'woo-min-max-quantity-step-control-single/wcmmq.php',
				'url'         => 'https://wordpress.org/plugins/woo-min-max-quantity-step-control-single/',
			),
            array(
				'slug'        => 'wc-quantity-plus-minus-button',
				'name'        => 'Quantity Plus Minus Button for WooCommerce',
				'description' => __( 'Add plus and minus buttons to WooCommerce quantity fields.', 'bizzswatches' ),
				'icon'   => 'https://ps.w.org/wc-quantity-plus-minus-button/assets/icon-256x256.png',
				'author'      => 'Bizzplugin',
				'path'        => 'wc-quantity-plus-minus-button/init.php',
				'url'         => 'https://wordpress.org/plugins/wc-quantity-plus-minus-button/',
			),
        );  
        return $plugins;
    }
}