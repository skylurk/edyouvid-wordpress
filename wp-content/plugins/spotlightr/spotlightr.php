<?php
/*
Plugin Name: Spotlightr
Description: Video for Small Businesses & Big Ideas
Plugin URI:  https://docs.spotlightr.com/en/articles/3362389-wordpress-plugin-tutorial
Version:     0.1.13
Author:      Spotlightr
Author URI:  https://spotlightr.com/
Props:       Spotlightr
License:     GPL2
*/

/* Exit if accessed directly */
defined('ABSPATH') || exit;

class Spotlightr
{
    var $custom_aspects;
    var $galleries;
    var $group_list;
    var $namespace;
    var $playlists;
    var $plugin_base;
    var $plugin_dir;
    var $plugin_name;
    var $plugin_url;
    var $quizzes;

    function __construct()
    {
        $this->plugin_base = plugin_basename(__FILE__);
        $this->plugin_name = trim(dirname($this->plugin_base), '/');
        $this->plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_name;
        $this->plugin_url = get_site_url() . '/wp-content/plugins' . '/' . $this->plugin_name;
        $this->namespace = 'spotlightr';
        $this->custom_aspects = [
            [
                'value' => 1,
                'label' => 'Square',
                'ratio' => '1:1',
            ],
            [
                'value' => 1.3,
                'label' => 'Standard Definition',
                'ratio' => '4:3',
            ],
            [
                'value' => 1.78,
                'label' => 'HD Standard',
                'ratio' => '16:9',
            ],
            [
                'value' => 1.85,
                'label' => 'US Theatrical',
                'ratio' => '1.85:1',
            ],
            [
                'value' => 1.66,
                'label' => 'European Theatrical',
                'ratio' => '1.66:1',
            ],
            [
                'value' => 2.39,
                'label' => 'Wide Screen',
                'ratio' => '2.39:1',
            ],
            [
                'value' => 4,
                'label' => 'Ultra-Panavision',
                'ratio' => '4:1',
            ]
        ];

        add_action('admin_enqueue_scripts', array($this, 'call_spotlightr_script_and_style'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_spotlightr_ajax_login', array($this, 'spotlightr_ajax_login'));
        add_action('wp_ajax_spotlightr_ajax_logout', array($this, 'spotlightr_ajax_logout'));
        add_action('wp_ajax_spotlightr_ajax_sign_up', array($this, 'spotlightr_ajax_sign_up'));
        add_action('wp_ajax_spotlightr_ajax_add_group', array($this, 'spotlightr_ajax_add_group'));
        add_action('wp_ajax_spotlightr_ajax_get_videos', array($this, 'spotlightr_ajax_get_videos'));
        add_action('wp_ajax_spotlightr_ajax_get_groups', array($this, 'spotlightr_ajax_get_groups'));
        add_action('admin_enqueue_scripts', array($this, 'load_media_files'));
        add_action('init', array($this, 'spotlightr_oembed_provider'));
        add_action('wp_head', array($this, 'make_logged_in_user_available'));
        add_action('admin_enqueue_scripts', array($this, 'spotlightr_gutenberg_block_video'));
        add_action('admin_enqueue_scripts', array($this, 'spotlightr_gutenberg_block_playlist'));
        add_action('admin_enqueue_scripts', array($this, 'spotlightr_gutenberg_block_quiz'));
        add_action('admin_enqueue_scripts', array($this, 'spotlightr_gutenberg_block_gallery'));

        if ($this->is_learn_dash_enabled()) {
            add_filter('learndash_settings_fields', array($this, 'add_button_to_learn_dash'), 10, 2);
            add_action('admin_enqueue_scripts', array($this, 'add_spotlightr_learn_dash_script'));
        }



        register_block_type('spotlightr/video-block-get-videos', array(
                'render_callback' => array($this, 'spotlightr_video_block_get_videos'),
                'api_version' => 2,
                'attributes'      => [
                    'id'    => [
                        'type'      => 'string'
                    ]
                ]
            )
        );
        register_block_type('spotlightr/video-block', array(
                'render_callback' => array($this, 'spotlightr_video_block_renderer'),
                'api_version' => 2,
            )
        );
        register_block_type('spotlightr/playlist-block', array(
                'render_callback' => array($this, 'spotlightr_playlist_block_renderer'),
                'api_version' => 2,
            )
        );
        register_block_type('spotlightr/quiz-block', array(
                'render_callback' => array($this, 'spotlightr_quiz_block_renderer'),
                'api_version' => 2,
            )
        );
        register_block_type('spotlightr/gallery-block', array(
                'render_callback' => array($this, 'spotlightr_gallery_block_renderer'),
                'api_version' => 2,
            )
        );

    }

    public function add_button_to_learn_dash($settings, $meta_box_key)
    {
        if (!in_array($meta_box_key, ['learndash-lesson-display-content-settings', 'learndash-topic-display-content-settings'])) {
            return $settings;
        }
        $setting = [
            'lesson_use_spotlightr_video_button' => [
                'name'              => 'lesson_use_spotlightr_video_button',
                'label'             => esc_html__('Use Spotlightr Video', $this->namespace),
                'type'              => 'html',
                'value'             => 'Select video',
                'class'             => 'button-primary',
                'id'                => 'spotlight_video_select_button',
                'help_text'         => esc_html__('Use the Spotlightr Player video in your post content for video progression.', $this->namespace),
                'parent_setting'    => 'lesson_video_enabled',
            ]
        ];

        $settings = $this->learn_dash_array_insert($settings, $setting, 'lesson_video_url', 'before');

        return $settings;
    }

    function add_spotlightr_learn_dash_script()
    {
        wp_enqueue_script($this->namespace . 'spotlightr_learn_dash_js', $this->plugin_url . '/resources/js/videolist-ld.js');
        $info = [
            'is_user_logged_in' => get_option( 'spotlightr_token' ) !== '',
            'plugin_url'        => admin_url('admin.php?page=spotlightr'),
            'custom_aspects'    => $this->custom_aspects,
            'subdomain'         => get_option('spotlightr_subdomain'),
        ];
        wp_localize_script($this->namespace . 'spotlightr_learn_dash_js', 'spotlightrInfo', $info);
    }

    function admin_menu()
    {
        if (current_user_can('list_users')) {
            add_menu_page(__('Spotlightr', $this->namespace), __('Spotlightr', $this->namespace), 8, $this->namespace, null, $this->plugin_url . '/resources/img/icon.png');
            add_submenu_page($this->namespace, __('Settings', $this->namespace), __('Settings', $this->namespace), 8, $this->namespace, array($this, 'settings'));
            add_submenu_page($this->namespace, __('Videos', $this->namespace), __('Videos', $this->namespace), 8, $this->namespace . '_videolist', array($this, 'videos'));
            add_submenu_page($this->namespace, __('Gallery', $this->namespace), __('Gallery', $this->namespace), 8, $this->namespace . '_gallery', array($this, 'gallery'));
            add_submenu_page($this->namespace, __('Quizzes', $this->namespace), __('Quizzes', $this->namespace), 8, $this->namespace . '_quiz', array($this, 'quizzes'));
            add_submenu_page($this->namespace, __('Playlists', $this->namespace), __('Playlists', $this->namespace), 8, $this->namespace . '_playlist', array($this, 'playlists'));
        }
    }

    private function learn_dash_array_insert($array, $pairs, $key, $position = 'after')
    {
        $keyPosition = array_search($key, array_keys($array));

        if ($position == 'after')
            $keyPosition++;

        if ($keyPosition !== false) {
            $result = array_slice($array, 0, $keyPosition);
            $result = array_merge($result, $pairs);
            $result = array_merge($result, array_slice($array, $keyPosition));
        } else {
            $result = array_merge($array, $pairs);
        }

        return $result;
    }

    function call_spotlightr_page_script($page)
    {
        wp_enqueue_script($this->namespace . '_' . $page . '_js', $this->plugin_url . '/resources/js/' . $page . '.js');
    }

    function call_spotlightr_script_and_style()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script($this->namespace . '_axios_js', $this->plugin_url . '/resources/js/axios.min.js', array(), null, true);
        wp_enqueue_script($this->namespace . '_bootstrap_js', $this->plugin_url . '/resources/js/bootstrap.min.js', array(), null, true);
        wp_enqueue_style($this->namespace . '_css', $this->plugin_url . '/resources/css/style.css');
    }

    function gallery()
    {
        if (get_option('spotlightr_token') == '') {
            echo "<script>location.href = '" . esc_html(admin_url('admin.php?page=spotlightr')) . "';</script>";

        } else {
            add_action('admin_enqueue_scripts', $this->call_spotlightr_page_script('gallery'));
            $this->galleries = $this->get_galleries();
            include('gallery.php');
        }
    }

    function get_galleries()
    {
        $api_url_get_galleries = 'https://api.spotlightr.com/spotlight?';
        $galleries_request_data = [
            'Token' => get_option('spotlightr_token')
        ];
        $galleries = [];
        $galleries_response = $this->get_get_request($api_url_get_galleries, $galleries_request_data);
        $galleries_response = json_decode($galleries_response, true);
        if (count($galleries_response)) {
            foreach ($galleries_response as $item) {
                $number_of_videos = 0;
                $item['settings'] = json_decode($item['settings'], true);
                $item['content'] = json_decode($item['content'], true);
                foreach ($item['content'] as $section) {
                    $number_of_videos += count($section['videos']);
                }
                $item['numberOfVideos'] = $number_of_videos;
                $galleries[] = $item;
            }
        }

        return $galleries;
    }

    function get_get_request($api_url, $data = [])
    {
        $response = wp_remote_get($api_url.http_build_query($data));

        return wp_remote_retrieve_body($response);
    }

    function get_group_list()
    {
        $api_url_get_groups = 'https://api.spotlightr.com/groups?';

        $group_request_data = [
            'Token' => get_option('spotlightr_token')
        ];

        $group_list = [];
        $group_response =  $this->get_get_request($api_url_get_groups, $group_request_data);
        $group_response = json_decode($group_response, true);
        if (count($group_response)) {
            foreach ($group_response as $group_item) {
                $group_list[$group_item['id']] = ['name' => $group_item['name'], 'numberOfVideos' => $group_item['numberOfVideos'], 'videos' => []];
            }
        }

        return $group_list;
    }

    function get_playlist_lists()
    {
        $api_url_get_playlists = 'https://api.spotlightr.com/playlist?';
        $playlist_request_data = [
            'Token' => get_option('spotlightr_token')
        ];
        $playlists = [];
        $playlist_response =  $this->get_get_request($api_url_get_playlists, $playlist_request_data);
        $playlist_response = json_decode($playlist_response, true);
        if (count($playlist_response)) {
            foreach ($playlist_response as $item) {
                $item['data'] = json_decode($item['data'], true);
                $item['data']['thumbnail'] = $item['data']['videos'][0]['thumbnail'] ?? '';
                $item['data']['videos'] = $item['data']['videos'] ?? [];
                $playlists[] = $item;
            }
        }

        return $playlists;
    }

    function get_post_json_request($api_url, $data = [])
    {
        return json_decode($this->get_post_request($api_url, $data), true);
    }

    function get_post_request($api_url, $data = [])
    {
        $args = array(
            'method'      => 'POST',
            'body'        => $data,
            'httpversion' => '1.0',
        );
        $response = wp_remote_post($api_url, $args);

        return wp_remote_retrieve_body($response);;
    }

    function get_quizzes()
    {
        $api_url_get_quizzes = 'https://api.spotlightr.com/quiz?';
        $quizzes_request_data = [
            'Token' => get_option('spotlightr_token')
        ];
        $quizzes = [];
        $quizzes_response =  $this->get_get_request($api_url_get_quizzes, $quizzes_request_data);
        $quizzes_response = json_decode($quizzes_response, true);
        if (count($quizzes_response)) {
            foreach ($quizzes_response as $item) {
                $item['data'] = json_decode($item['data'], true);
                $item['data']['video']['thumbnail'] = $item['data']['video']['thumbnail'] ?? '';
                $quizzes[] = $item;
            }
        }

        return $quizzes;
    }

    function get_video_list($group_id)
    {
        $api_url_get_videos = 'https://api.spotlightr.com/videos?';
        $video_request_data = [
            'Token' => get_option('spotlightr_token'),
            'videoGroup' => $group_id
        ];
        $video_list = [];
        $video_response = $this->get_get_request($api_url_get_videos, $video_request_data);
        $video_response = json_decode($video_response, true);
        foreach ($video_response as $video) {

            $video['playerSettings'] = json_decode($video['playerSettings'], true);
            $video['customResolution'] = [];
            if ($video['playerSettings']['type'] === 'upload') {
                $video['optimizedUrls'] = json_decode($video['optimizedUrls'], true);
                if (is_array($video['optimizedUrls'])) {
                    foreach ($video['optimizedUrls'] as $item) {
                        foreach ($item as $key => $value) {
                            $video['customResolution'][] = $key;
                        }
                    }
                }
            }
            $video['customResolution'] = count($video['customResolution']) ? implode(',', $video['customResolution']) : 'empty';
            $video_list[] = $video;
        }

        return $video_list;
    }

    public function is_learn_dash_enabled()
    {
        return defined('LEARNDASH_VERSION');
    }

    function load_media_files()
    {
        wp_enqueue_media();
    }

    function make_logged_in_user_available()
    {
        $fullUser = wp_get_current_user();
        echo '<script> var loggedInUser; window.loggedInUser = '.json_encode($fullUser).'</script>';
    }

    function settings()
    {
        include('settings.php');
    }

    function spotlightr_ajax_add_group()
    {
        $name = sanitize_text_field($_REQUEST['name']);
        $api_url = 'https://api.spotlightr.com/groups';
        $data = [
            'Token' => get_option('spotlightr_token'),
            'name' => $name
        ];

        $response = $this->get_post_json_request($api_url, $data);

        if (is_int($response)) {
            $result = 'success';
        } else {
            $result = $response;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json($result);
        }
    }

    function spotlightr_ajax_get_groups()
    {
        $response = $this->get_group_list();

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json( count($response) ? json_encode($response) : 'empty');
            wp_die();
        }
    }

    function spotlightr_ajax_get_videos()
    {
        $group = sanitize_text_field($_REQUEST['group']);

        $response = $this->get_video_list($group);

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json( count($response) ? json_encode($response) : 'empty');
            wp_die();
        }
    }

    function spotlightr_ajax_login()
    {
        $username = sanitize_email($_REQUEST['username']);
        $password = $_REQUEST['password'];
        $apiKey = $_REQUEST['apiKey'];
        $api_url = 'https://api.spotlightr.com/login';
        $data=[];
        if($apiKey){
            $data = [
                'apiKey' => $apiKey
            ];
        }
        else{
            $data = [
                'username' => $username,
                'password' => $password
            ];
        }

        $response = $this->get_post_json_request($api_url, $data);

        if (isset($response[0]['token'])) {
            $result = 'success';
            update_option('spotlightr_username', $response[0]['username']);
            update_option('spotlightr_token', $response[0]['token']);
            update_option('spotlightr_api_key', $response[0]['integrationsApiKey']);
            update_option('spotlightr_subdomain', $response[0]['subdomain']);
        } else {
            $result = $response;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json($result);
        }
    }

    function spotlightr_ajax_logout()
    {
        update_option('spotlightr_username', '');
        update_option('spotlightr_token', '');
        update_option('spotlightr_api_key', '');
        update_option('spotlightr_subdomain', '');
    }

    function spotlightr_ajax_sign_up()
    {
        $first_name = sanitize_text_field($_REQUEST['firstName']);
        $last_name = sanitize_text_field($_REQUEST['lastName']);
        $username = sanitize_email($_REQUEST['username']);
        $password = $_REQUEST['password'];
        $subdomain = sanitize_text_field($_REQUEST['subdomain']);
        $api_url = 'https://api.spotlightr.com/user/demo';
        $data = [
            'firstName'     => $first_name,
            'lastName'      => $last_name,
            'username'      => $username,
            'password'      => $password,
            'subdomain'     => $subdomain,
            'App-Token'     => '18763095f637119d787e88106a89cb64',
            'processorName' => 'force',
            'price'         => '0',
            'transID'       => 'demo',
            'qty'           => '1',
            'productID'     => '46',
            'signupQuery'   => '{}',
        ];

        $response = $this->get_post_json_request($api_url, $data);

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json($response);
        }
    }

    function spotlightr_gallery_block_renderer($block_attributes, $content){
        if (get_option('spotlightr_token') !== '') {
            $galleries = $this->get_galleries();

            return json_encode(
                [
                    'galleries' => $galleries,
                    'subdomain' => get_option('spotlightr_subdomain')
                ]
            );
        } else {

            return json_encode(
                [
                    'error_message' => true,
                    'error_url' => admin_url('admin.php?page=spotlightr'),
                ]
            );
        }
    }

    function spotlightr_gutenberg_block_gallery() {
        wp_enqueue_script(
            'gutenberg-spotlightr-gallery-block-editor',
            $this->plugin_url . '/src/blocks/block-gallery.js',
            array('wp-blocks',
                'wp-components',
                'wp-element',
                'wp-i18n',
                'wp-editor')
            ,
            '1.0.0',
            true);
    }

    function spotlightr_gutenberg_block_playlist() {
        wp_enqueue_script(
            'gutenberg-spotlightr-playlist-block-editor',
            $this->plugin_url . '/src/blocks/block-playlist.js',
            array('wp-blocks',
                'wp-components',
                'wp-element',
                'wp-i18n',
                'wp-editor')
            ,
            '1.0.0',
            true);
        wp_enqueue_script($this->namespace . '_playlist_block_js', $this->plugin_url . '/resources/js/playlist-block.js');
    }

    function spotlightr_gutenberg_block_quiz() {
        wp_enqueue_script(
            'gutenberg-spotlightr-quiz-block-editor',
            $this->plugin_url . '/src/blocks/block-quiz.js',
            array('wp-blocks',
                'wp-components',
                'wp-element',
                'wp-i18n',
                'wp-editor')
            ,
            '1.0.0',
            true);
        wp_enqueue_script($this->namespace . '_quiz_block_js', $this->plugin_url . '/resources/js/quiz-block.js');
    }

    function spotlightr_gutenberg_block_video() {
        wp_enqueue_script(
            'gutenberg-spotlightr-video-block-editor',
            $this->plugin_url . '/src/blocks/block-video.js',
            array('wp-blocks',
                'wp-components',
                'wp-element',
                'wp-i18n',
                'wp-editor')
            ,
            '1.0.0',
            true);
        wp_enqueue_script($this->namespace . '_videolist_block_js', $this->plugin_url . '/resources/js/videolist-block.js');
    }

    function spotlightr_oembed_provider()
    {
        wp_oembed_add_provider('https://*.cdn.spotlightr.com/*', 'https://api.spotlightr.com/getOEmbed', false);
        $current_user = wp_get_current_user();
        setcookie("vooPlayerContact", $current_user->user_email, time()+86400, "/");
    }

    function spotlightr_playlist_block_renderer($block_attributes, $content){
        if (get_option('spotlightr_token') !== '') {
            $playlists = $this->get_playlist_lists();

            return json_encode(
                [
                    'playlists' => $playlists,
                    'custom_aspects' => $this->custom_aspects,
                    'subdomain' => get_option('spotlightr_subdomain')
                ]
            );
        } else {

            return json_encode(
                [
                    'error_message' => true,
                    'error_url' => admin_url('admin.php?page=spotlightr'),
                ]
            );
        }
    }

    function spotlightr_video_block_get_videos($attributes, $content)
    {
        $videos = $this->get_video_list($attributes['id']);

        return json_encode(
            [
                'videos' => $videos,
                'subdomain' => get_option('spotlightr_subdomain')
            ]
        );
    }

    function spotlightr_quiz_block_renderer($block_attributes, $content){
        if (get_option('spotlightr_token') !== '') {
            $quizzes = $this->get_quizzes();

            return json_encode(
                [
                    'quizzes' => $quizzes,
                    'custom_aspects' => $this->custom_aspects,
                    'subdomain' => get_option('spotlightr_subdomain')
                ]
            );
        } else {

            return json_encode(
                [
                    'error_message' => true,
                    'error_url' => admin_url('admin.php?page=spotlightr'),
                ]
            );
        }
    }

    function spotlightr_video_block_renderer($block_attributes, $content){
        if (get_option('spotlightr_token') !== '') {
            $group_list = $this->get_group_list();

            return json_encode(
                [
                    'group_list' => $group_list,
                    'custom_aspects' => $this->custom_aspects,
                    'subdomain' => get_option('spotlightr_subdomain')
                ]
            );
        } else {

            return json_encode(
                [
                    'error_message' => true,
                    'error_url' => admin_url('admin.php?page=spotlightr'),
                ]
            );
        }
    }

    function playlists()
    {
        if (get_option('spotlightr_token') == '') {
            echo "<script>location.href = '" . esc_html(aadmin_url('admin.php?page=spotlightr')) . "';</script>";

        } else {
            add_action('admin_enqueue_scripts', $this->call_spotlightr_page_script('playlist'));
            $this->playlists = $this->get_playlist_lists();
            include('playlist.php');
        }
    }

    function quizzes()
    {
        if (get_option('spotlightr_token') == '') {
            echo "<script>location.href = '" . esc_html(aadmin_url('admin.php?page=spotlightr')) . "';</script>";

        } else {
            add_action('admin_enqueue_scripts', $this->call_spotlightr_page_script('quiz'));
            $this->quizzes = $this->get_quizzes();
            include('quiz.php');
        }
    }

    function videos()
    {
        if (get_option('spotlightr_token') == '') {
            echo "<script>location.href = '" . esc_html(admin_url('admin.php?page=spotlightr')) . "';</script>";

        } else {
            add_action('admin_enqueue_scripts', $this->call_spotlightr_page_script('videolist'));
            $this->group_list = $this->get_group_list();
            include('videolist.php');
        }
    }

    static function render_shortcode($atts, $content, $tag)
    {
        $atts = shortcode_atts( array(
            'subdomain' => '',
            'id' => '',
            's' => '',
            'e' => '',
            'r' => '',
            'a' => '',
        ), $atts );

        $fullUser = wp_get_current_user();
        $current_user_email = $fullUser->user_email ?? '';

        // check if $atts['subdomain'] is pure alphanumeric
        if (!ctype_alnum($atts['subdomain'])) {
            $atts['subdomain'] = 'videos';
        } else {
            $atts['subdomain'] = sanitize_text_field($atts['subdomain']);
        }

        $atts['id'] = sanitize_text_field($atts['id']);
        $atts['s'] = sanitize_text_field($atts['s']);
        $atts['e'] = sanitize_text_field($atts['e']);
        $atts['r'] = sanitize_text_field($atts['r']);
        $atts['a'] = sanitize_text_field($atts['a']);
        
        
        if ($tag == 'spotlightr-v') {
            $content = '<script src="' . esc_url('https://' . $atts['subdomain'] . '.cdn.spotlightr.com/assets/spotlightr.js') . '"></script><iframe allow="autoplay" class="video-player-container spotlightr" data-playerid="' . esc_attr($atts['id']) . '" allowtransparency="true" style="max-width:100%" name="videoPlayerframe" allowfullscreen="true" src="' . esc_url('https://' . $atts['subdomain'] . '.cdn.spotlightr.com/watch/' . $atts['id'] . '?s=' . $atts['s'] . '&e=' . $atts['e'] . '&aspect=' . $atts['a'] . '&resolution=' . $atts['r']) . '" watch-type="" url-params="' . esc_attr('s=' . $atts['s'] . '&e=' . $atts['e'] . '&aspect=' . $atts['a'] . '&resolution=' . $atts['r']) . '" frameborder="0" scrolling="no"></iframe>';
        } elseif ($tag == 'spotlightr-g') {
            $content = '<script src="' . esc_url('https://' . $atts['subdomain'] . '.cdn.spotlightr.com/assets/spotlightr.js') . '"></script><a class="channelIframe videoPlayer" href="" data-playerid="' . esc_attr($atts['id']) . '"></a>';
        } elseif ($tag == 'spotlightr-q') {
            $content = '<script src="' . esc_url('https://' . $atts['subdomain'] . '.cdn.spotlightr.com/assets/spotlightr.js') . '"></script><iframe allow="autoplay" class="video-player-container spotlightr" data-playerid="' . esc_attr($atts['id']) . '" allowtransparency="true" style="max-width:100%" name="videoPlayerframe" allowfullscreen="true" src="' . esc_url('https://' . $atts['subdomain'] . '.cdn.spotlightr.com/watch/quiz/' . $atts['id'] . '?aspect=' . $atts['a']) . '" url-params="' . esc_attr('aspect=' . $atts['a']) . '" frameborder="0" scrolling="no"></iframe>';
        } elseif ($tag == 'spotlightr-p') {
            $content = '<script src="' . esc_url('https://' . $atts['subdomain'] . '.cdn.spotlightr.com/assets/spotlightr.js') . '"></script><iframe allow="autoplay" class="video-player-container spotlightr" data-playerid="' . esc_attr($atts['id']) . '" allowtransparency="true" style="max-width:100%" name="videoPlayerframe" allowfullscreen="true" src="' . esc_url('https://' . $atts['subdomain'] . '.cdn.spotlightr.com/watch/playlist/' . $atts['id'] . '?aspect=' . $atts['a']) . '" url-params="' . esc_attr('aspect=' . $atts['a']) . '" frameborder="0" scrolling="no"></iframe>';
        }

        return $content;
    }
}

new Spotlightr();

add_shortcode( 'spotlightr-v', [ 'Spotlightr', 'render_shortcode' ] );
add_shortcode( 'spotlightr-g', [ 'Spotlightr', 'render_shortcode' ] );
add_shortcode( 'spotlightr-q', [ 'Spotlightr', 'render_shortcode' ] );
add_shortcode( 'spotlightr-p', [ 'Spotlightr', 'render_shortcode' ] );
