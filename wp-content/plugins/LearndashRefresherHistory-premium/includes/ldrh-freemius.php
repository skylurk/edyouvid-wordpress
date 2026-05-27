<?php

if ( !function_exists( 'ea_fs' ) ) {
    // Create a helper function for easy SDK access.
    function ea_fs() {
        global $ea_fs;
        if ( !isset( $ea_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $ea_fs = fs_dynamic_init( array(
                'id'               => '9295',
                'slug'             => 'LearndashRefresherHistory',
                'type'             => 'plugin',
                'public_key'       => 'pk_f06d58a49871f9d775956f64949e9',
                'is_premium'       => true,
                'premium_suffix'   => 'Learndash Refresher History Premium',
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'is_org_compliant' => false,
                'menu'             => array(
                    'first-path' => 'plugins.php',
                    'support'    => false,
                ),
                'is_live'          => true,
            ) );
        }
        return $ea_fs;
    }

    // Init Freemius.
    ea_fs();
    // Signal that SDK was initiated.
    do_action( 'ea_fs_loaded' );
}
function ea_fs_custom_connect_message_on_update(
    $message,
    $user_first_name,
    $plugin_title,
    $user_login,
    $site_link,
    $freemius_link
) {
    return sprintf(
        __( 'Hey %1$s' ) . ',<br>' . __( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'LearndashRefresherHistory' ),
        $user_first_name,
        '<b>' . $plugin_title . '</b>',
        '<b>' . $user_login . '</b>',
        $site_link,
        $freemius_link
    );
}

ea_fs()->add_filter(
    'connect_message_on_update',
    'ea_fs_custom_connect_message_on_update',
    10,
    6
);