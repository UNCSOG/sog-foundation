<?php

/**
 * Administration
 *
 * @package Utility_Bar
 *
 */

namespace Utility_Bar;
// use \Utility_Bar\Storage as Storage;

class Network_Admin {

    public static $instance;

    function __construct() {
        add_action('network_admin_menu', array($this, 'network_settings_pages'));
        add_action('admin_enqueue_scripts', array($this, 'wp_network_admin_scripts'));
        add_action('admin_footer', array($this, 'admin_underscores_templates'), 25);
    }

    /**
     * add the menu page
     *
     * @return void
     */
    function network_settings_pages() {
        add_menu_page('Alert Service', 'Alert Service', 'manage_network_options', 'alert-service', array($this, 'menu_page_callback'), 'dashicons-rss');
    }

    function menu_page_callback() {
        //get the cap feed deets from the db
        $data = [];
        $data['alert'] = Alert_Banner_Storage::get_stored_alert();
        $data['status'] = Alert_Banner_Storage::get_published_status();

        //build the button urls for rest posts
        $generate_publish_url = \rest_url(Core::$rest_prefix . '/status/published');
        $data['publish_url'] = \wp_nonce_url($generate_publish_url, 'alert-service', Core::$nonce . '-nonce');
        $generate_unpublish_url = \rest_url(Core::$rest_prefix . '/status/unpublished');
        $data['unpublish_url'] = \wp_nonce_url($generate_unpublish_url, 'alert-service', Core::$nonce . '-nonce');

        //load the template
        $template_loader = new Template_Loader();
        $template_loader
            ->set_template_data($data)
            ->get_template_part('admin/network','admin');
    }

    function wp_network_admin_scripts($hook) {
        if ($hook != 'toplevel_page_alert-service') {
            return;
        }

        $version = Core::$version;
        $deps = [];

        $assets_path = SOG_UTILITY_BAR_PLUGIN_DIR . 'build/network-admin/index.asset.php';
        if (file_exists($assets_path)) {
            $assets_body = include_once($assets_path);
            $version = $assets_body['version'];
            $deps = $assets_body['dependencies'];
        }

        wp_enqueue_style('alert-service-admin-styles', SOG_UTILITY_BAR_PLUGIN_URL . 'build/network-admin/index.css', [], $version);
        wp_enqueue_script('alert-service-admin-scripts', SOG_UTILITY_BAR_PLUGIN_URL . 'build/network-admin/index.js', $deps, $version);
    }

    /**
     * templates used in the underscores template system
     * @return void
     */
    function admin_underscores_templates() {
        $screen = get_current_screen();
        if ('toplevel_page_alert-service-network' === $screen->id) {
            include(SOG_UTILITY_BAR_PLUGIN_DIR . 'templates/admin/admin-modal.php');
        }
    }

    /**
     * you only need this once
     *
     * @return UTILITY_BAR\Admin
     */
    public static function getInstance(): Network_Admin {
        static::$instance = static::$instance ?? new static();
        return static::$instance;
    }
}
