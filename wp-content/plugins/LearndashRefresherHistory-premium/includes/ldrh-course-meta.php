<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * This class defines ldrh course meta box
 */

if ( (class_exists('LearnDash_Settings_Metabox')) && (!class_exists('LDRH_Course_Meta')) ) {

    class LDRH_Course_Meta extends LearnDash_Settings_Metabox {
        
        public function __construct() {
                // What screen ID are we showing on.
                $this->settings_screen_id = 'sfwd-courses';

                // Used within the Settings API to uniquely identify this section.
                $this->settings_metabox_key = 'learndash-refresher-history-settings';

                // Section label/header.
                $this->settings_section_label = __('Refresher History Settings', 'ldrh');

                // Used to show the section description above the fields. Can be empty.
                $this->settings_section_description = __('Refresher History Settings', 'ldrh');

                add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

                // Map internal settings field ID to legacy field ID.
                $this->settings_fields_map = array(
                    'ldrh_is_course_refreshed'  => 'ldrh_is_course_refreshed',
                    'ldrh_expiration_period'    => 'ldrh_expiration_period',
                    'ldrh_grace_period'         => 'ldrh_grace_period',
                    'ldrh_course_reset'         => 'ldrh_course_reset'
                );

                parent::__construct();
        }
        
        /**
         * Initialize the metabox settings values.
         */
        public function load_settings_values() {
            global $sfwd_lms;

            parent::load_settings_values();
            if ( true === $this->settings_values_loaded ) {

                if ( !isset( $this->setting_option_values['ldrh_is_course_refreshed']) ) {
                    $this->setting_option_values['ldrh_is_course_refreshed'] = '';
                }

                if ( !isset( $this->setting_option_values['ldrh_course_reset']) ) {
                    $this->setting_option_values['ldrh_course_reset'] = 'progress';
                }

                if ( !isset( $this->setting_option_values['ldrh_grace_period']) ) {
                    $this->setting_option_values['ldrh_grace_period'] = '';
                }

                if ( !isset( $this->setting_option_values['ldrh_expiration_period']) ) {
                    $this->setting_option_values['ldrh_expiration_periodf'] = '';
                }
            }
        }
        
        /**
         * Initialize the metabox settings fields.
         */
        public function load_settings_fields() {
            global $sfwd_lms;
            
            $this->setting_option_fields = array(
                'ldrh_is_course_refreshed'  => array(
                    'name'      => 'ldrh_is_course_refreshed',
                    'type'      => 'checkbox-switch',
                    'label'     => __('Enable Refresher', 'ldrh'),
                    'help_text' => __('Enable this to enable refresher logic for his course', 'ldrh'),
                    'value'     => $this->setting_option_values['ldrh_is_course_refreshed'],
                    'default'   => '',
                    'options'   => array(
                            '1' => '',
                            ''   => '',

                    ),
                    'child_section_state' => ( '1' === $this->setting_option_values['ldrh_is_course_refreshed'] ) ? 'open' : 'closed',
                ),
                'ldrh_expiration_period'    => array(
                    'name'           => 'ldrh_expiration_period',
                    'type'           => 'number',
                    'class'          => 'small-text',
                    'label'          => __('Expiration Period', 'ldrh'),
                    'help_text'      => __('Add expiration period number in months. If period was "0", the value is taken from the general setting. Default is "0".', 'ldrh'),
                    'input_label'    => __('month(s)', 'ldrh'),
                    'value'          => $this->setting_option_values['ldrh_expiration_period']?$this->setting_option_values['ldrh_expiration_period']:'',
                    'default'        => '',
                    'attrs'          => array(
                        'step' => 1,
                        'min'  => 0,
                    ),
                    'parent_setting' => 'ldrh_is_course_refreshed',
                ),
                'ldrh_grace_period'         => array(
                    'name'           => 'ldrh_grace_period',
                    'type'           => 'number',
                    'class'          => 'small-text',
                    'label'          => __('Grace Period', 'ldrh'),
                    'help_text'      => __('Add grace period number in days. If period was "0", the value is taken from the general setting. Default is "0".', 'ldrh'),
                    'input_label'    => __('day(s)', 'ldrh'),
                    'value'          => $this->setting_option_values['ldrh_grace_period']?$this->setting_option_values['ldrh_grace_period']:'',
                    'default'        => '',
                    'attrs'          => array(
                        'step' => 1,
                        'min'  => 0,
                    ),
                    'parent_setting' => 'ldrh_is_course_refreshed',
                ),
                'ldrh_course_reset'         => array(
                    'name'           => 'ldrh_course_reset',
                    'type'           => 'radio',
                    'label'          => __('When Course Refreshed', 'ldrh'),
                    'help_text'      => __('Choose what action to take when course reaches grace period and being refreshed. Either remove progress only or remove progress and unenroll user.', 'ldrh'),
                    'value'          => $this->setting_option_values['ldrh_course_reset'],
                    'default'        => 'progress',
                    'options'   => array(
                        'progress'   => __('Remove Progress', 'ldrh'),
                        'enrollment' => __('Unenroll User', 'ldrh'),
                    ),
                    'parent_setting' => 'ldrh_is_course_refreshed',
                ),
            );
            
            //$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

            parent::load_settings_fields();
        }
        
        /**
         * Filter settings values for metabox before save to database.
         *
         * @param array $settings_values Array of settings values.
         * @param string $settings_metabox_key Metabox key.
         * @param string $settings_screen_id Screen ID.
         * @return array $settings_values.
         */
        public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
            if ( ($settings_screen_id === $this->settings_screen_id) && ($settings_metabox_key === $this->settings_metabox_key) ) {
                global $post;
                if ( isset($settings_values['ldrh_is_course_refreshed']) ) {
                    //$settings_values['ldrh_is_course_refreshed'] = '';
                    update_post_meta($post->ID, 'ldrh_is_course_refreshed', $settings_values['ldrh_is_course_refreshed']);
                }

                if ( isset($settings_values['ldrh_expiration_period']) ) {
                    //$settings_values['ldrh_expiration_period'] = 0;
                    if($settings_values['ldrh_expiration_period']){
                        update_post_meta($post->ID, 'ldrh_expiration_period', $settings_values['ldrh_expiration_period']);
                    }else{
                        update_post_meta($post->ID, 'ldrh_expiration_period', '');
                    }
                }
                
                if ( isset($settings_values['ldrh_grace_period']) ) {
                    //$settings_values['ldrh_grace_period'] = 0;
                    if($settings_values['ldrh_grace_period']){
                        update_post_meta($post->ID, 'ldrh_grace_period', $settings_values['ldrh_grace_period']);
                    }else{
                        update_post_meta($post->ID, 'ldrh_grace_period', '');
                    }
                }

                if ( isset($settings_values['ldrh_course_reset']) ) {
                    //$settings_values['ldrh_course_reset'] = 'progress';
                    update_post_meta($post->ID, 'ldrh_course_reset', $settings_values['ldrh_course_reset']);
                }

                apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
            }

            return $settings_values;
        }

    }

    add_filter(
        'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
        function( $metaboxes = array() ) {
            if ( ( ! isset( $metaboxes['LDRH_Course_Meta'] ) ) && ( class_exists( 'LDRH_Course_Meta' ) ) ) {
                    $metaboxes['LDRH_Course_Meta'] = LDRH_Course_Meta::add_metabox_instance();
            }

            return $metaboxes;
        },
        50,
        1
    );
}