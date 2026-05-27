<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class defines ldrh settings for the plugin.
 */

if (!class_exists('LDRH_Settings')) {

    class LDRH_Settings {
        
        public function __construct() {
            // add settings page under learndash settings page in separate tab
            add_filter('learndash_admin_tabs', array($this, 'ldrh_add_setting_tab'), 10, 1);
            
            // add plugin setting page
            add_action('admin_menu', array($this, 'ldrh_custom_menu_page'), 999);
        }
        
        public function ldrh_add_setting_tab($admin_tabs) {
            $admin_tabs['ldrh'] = array(
                'link'  =>      'admin.php?page=ldrh-settings',
                'name'  =>      __('Refresher History', 'ldrh'),
                'id'    =>      'admin_page_ldrh-settings',
                'menu_link'     =>      'edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses',
            );
            return $admin_tabs;
        }
        
        public function ldrh_custom_menu_page() {
            add_submenu_page('learndash-lms-non-existant', __('Learndash Refresher History Settings', 'ldrh'), __('LDRH Settings', 'ldrh'), 'administrator', 'ldrh-settings', array($this, 'ldrh_settings_page_callback'));

            //call register settings function
            add_action('admin_init', array($this, 'ldrh_register_settings'));
        }

        public function ldrh_register_settings() {
            register_setting('ldrh-plugin-settings', 'ldrh_expiration_period');
            register_setting('ldrh-plugin-settings', 'ldrh_grace_period');
            register_setting('ldrh-plugin-settings', 'ldrh_email_admin');
            register_setting('ldrh-plugin-settings', 'ldrh_admin_email');
            register_setting('ldrh-plugin-settings', 'ldrh_admin_email_template');
            register_setting('ldrh-plugin-settings', 'ldrh_email_leaders');
            register_setting('ldrh-plugin-settings', 'ldrh_leaders_email_template');
            register_setting('ldrh-plugin-settings', 'ldrh_email_student');
            register_setting('ldrh-plugin-settings', 'ldrh_student_email_template');
        }

        public function ldrh_settings_page_callback() {
            ?>
            <div class="wrap">
                <div id="icon-tools" class="icon32"></div>
                <h1><?php _e('Learndash Refresher History Settings', 'ldrh') ?></h1>

                <form method="post" action="options.php">
                    <?php settings_fields('ldrh-plugin-settings'); ?>
                    <?php do_settings_sections('ldrh-plugin-settings'); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e('Expiration Period', 'ldrh') ?></th>
                            <td>
                                <input type="number" min="1" step="1" name="ldrh_expiration_period" value="<?php echo get_option('ldrh_expiration_period', '12'); ?>"/> <?php _e('Months', 'ldrh') ?>
                                <p class="description"><?php _e('Add expiration period number in months. Default is 12.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Grace Period', 'ldrh') ?></th>
                            <td>
                                <input type="number" min="1" step="1" name="ldrh_grace_period" value="<?php echo get_option('ldrh_grace_period', '30'); ?>"/> <?php _e('Days', 'ldrh') ?>
                                <p class="description"><?php _e('Add grace period number in days. Default is 30.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Email Admin', 'ldrh') ?></th>
                            <td>
                                <input type="checkbox" id="ldrh_email_admin" name="ldrh_email_admin" value="1" <?php if (get_option('ldrh_email_admin', 0)) { ?>checked<?php } ?> />
                                <p class="description"><?php _e('Check this if you want to send admin email with refreshed user courses. Default is unchecked.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top" class="ldrh_admin_section">
                            <th scope="row"><?php _e('Email', 'ldrh') ?></th>
                            <td>
                                <input type="email" name="ldrh_admin_email" value="<?php
                                if (get_option('ldrh_admin_email', '')) {
                                    echo get_option('ldrh_admin_email');
                                }
                                ?>"/>
                                <p class="description"><?php _e('Add the email address that will receive the email with refreshed user courses. If left empty, the email will be sent to admin email. (Note: if an email address is added, only it will receive the email.)', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top" class="ldrh_admin_section">
                            <th scope="row"><?php _e('Email Template', 'ldrh') ?></th>
                            <td>
                                <?php
                                $admin_settings = array(
                                    'textarea_name' => 'ldrh_admin_email_template'
                                );
                                $admin_content = get_option('ldrh_admin_email_template', 'User courses that needs to be refreshed<br/><br/>{table}');
                                wp_editor($admin_content, 'ldrh_admin_email_template', $admin_settings);
                                ?>
                                <p class="description"><?php _e('Add email body template that will be sent to admin. Use {table} as variable to be replaced later in email with table with user courses that needs to be refreshed.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Email Leaders', 'ldrh') ?></th>
                            <td>
                                <input type="checkbox" id="ldrh_email_leaders" name="ldrh_email_leaders" value="1" <?php if (get_option('ldrh_email_leaders', 0)) { ?>checked<?php } ?> />
                                <p class="description"><?php _e('Check this if you want to send leaders email with refreshed user courses. Default is unchecked.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top" class="ldrh_leaders_section">
                            <th scope="row"><?php _e('Email Template', 'ldrh') ?></th>
                            <td>
                                <?php
                                $leader_settings = array(
                                    'textarea_name' => 'ldrh_leaders_email_template'
                                );
                                $leader_content = get_option('ldrh_leaders_email_template', 'User courses that needs to be refreshed<br/><br/>{table}');
                                wp_editor($leader_content, 'ldrh_leaders_email_template', $leader_settings);
                                ?>
                                <p class="description"><?php _e('Add email body template that will be sent to leaders. Use {table} as variable to be replaced later in email with table with user courses that needs to be refreshed.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e('Email Student', 'ldrh') ?></th>
                            <td>
                                <input type="checkbox" id="ldrh_email_student" name="ldrh_email_student" value="1" <?php if (get_option('ldrh_email_student', 1)) { ?>checked<?php } ?> />
                                <p class="description"><?php _e('Check this if you want to send student email with refreshed courses. Default is checked.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <tr valign="top" class="ldrh_student_section">
                            <th scope="row"><?php _e('Email Template', 'ldrh') ?></th>
                            <td>
                                <?php
                                $student_settings = array(
                                    'textarea_name' => 'ldrh_student_email_template'
                                );
                                $student_content = get_option('ldrh_student_email_template', '{course} needs to be refreshed');
                                wp_editor($student_content, 'ldrh_student_email_template', $student_settings);
                                ?>
                                <p class="description"><?php _e('Add email body template that will be sent to student. Use {course} as variable to be replaced later in email with course name.', 'ldrh') ?></p>
                            </td>
                        </tr>
                        
                        <?php if(!get_option('ldrh_migrate', false)){ ?>
                        <tr valign="top" id="ldrh_migrate_row">
                            <th scope="row"><?php _e('Migrate', 'ldrh') ?></th>
                            <td>
                                <div id="ldrh_migrate_container">
                                    <button type="button" id="ldrh_migrate" class="button"><?php _e('Migrate', 'ldrh') ?></button>
                                    <div id="ldrh_migrate_message" style="display: none; color: red;">
                                    <?php _e('Loading ... please don\'t navigate away from this page until it is completed. Completed:', 'ldrh') ?> <span id="ldrh_completion_percent">0%</span>
                                    </div>
                                </div>
                                <p class="description"><?php _e('If you are using the plugin on an existing working site, use this button to migrate existing completions. Note: This is a one time migration, so make sure that you set all settings right before running the migration (ex: Enable Refresher).', 'ldrh') ?></p>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>

                    <?php submit_button(); ?>

                </form>
            </div>
            <?php
        }
        
    }
    
    $ldrh_settings = new LDRH_Settings();
}