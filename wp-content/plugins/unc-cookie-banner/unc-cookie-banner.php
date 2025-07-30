<?php
/*
Plugin Name: UNC Cookie Banner
Description: Display a cookie banner on all sites. Can be used on both multisite and single site installs.
Author: ITS Digital Services - Michael Triplett
Version: 1.0.4
*/

namespace UNCcookiesPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class UNCCookieBanner {

    public $plugin_version = "1.0.3";

    public function __construct() {
        if (is_multisite()) {
            add_action('network_admin_menu', array($this, 'add_menu'));
        } else {
            add_action('admin_menu', array($this, 'add_menu_single_site'));
        }
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function add_menu_single_site() {
        add_menu_page(
            'Cookie Banner Settings',
            'Cookie Banner Settings',
            'manage_options',
            'cookie-banner-settings',
            array($this, 'settings_page')
        );
    }

    public function add_menu() {
        add_menu_page(
            'Cookie Banner Settings',
            'Cookie Banner Settings',
            'manage_network',
            'cookie-banner-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        $can_manage = is_multisite() ? current_user_can('manage_network') : current_user_can('manage_options');

        if ($can_manage) {
            if (isset($_POST['submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'unc_cookie_banner_settings_save')) {

                if (!$can_manage) {
                    wp_die('You do not have permission to save settings.');
                }
                $banner_text = wp_kses_post(wp_unslash($_POST['unc_cookie_banner_text']));
                if (empty($banner_text)) {
                    $banner_text = "This website uses cookies and similar technologies to understand visitor experiences. By using this website, you consent to UNC-Chapel Hill's cookie usage in accordance with their <a href='https://www.unc.edu/about/privacy-statement/'>Privacy Notice</a>.";
                }
                $button_text = sanitize_text_field($_POST['unc_cookie_banner_button_text']);
                if (empty($button_text)) {
                    $button_text = "I Accept";
                }
                $exclusion_list = sanitize_text_field($_POST['unc_cookie_banner_exclude_blogs']);

                $this->update_option('unc_cookie_banner_text', $banner_text);
                $this->update_option('unc_cookie_banner_button_text', $button_text);
                $this->update_option('unc_cookie_banner_exclude_blogs', $exclusion_list);
            }
        }

        $unc_cookie_banner_text = $this->get_option('unc_cookie_banner_text', "This website uses cookies and similar technologies to understand visitor experiences. By using this website, you consent to UNC-Chapel Hill's cookie usage in accordance with their <a href='https://www.unc.edu/about/privacy-statement/'>Privacy Notice</a>.");
        $unc_cookie_banner_button_text = $this->get_option('unc_cookie_banner_button_text', 'I Accept');
        $unc_cookie_banner_exclude_blogs = $this->get_option('unc_cookie_banner_exclude_blogs', '');

        $nonce_field = wp_nonce_field('unc_cookie_banner_settings_save', '_wpnonce', true, false);

        include(plugin_dir_path(__FILE__) . 'views/settings-page.php');
    }

    private function get_option($option_name, $default = false) {
        return is_multisite() ? get_site_option($option_name, $default) : get_option($option_name, $default);
    }

    private function update_option($option_name, $value) {
        return is_multisite() ? update_site_option($option_name, $value) : update_option($option_name, $value);
    }

    public function enqueue_scripts() {
        $current_blog_id = get_current_blog_id();
        // Getting the exclusion list and converting to an array.
        $unc_cookie_banner_exclude_blogs = $this->get_option('unc_cookie_banner_exclude_blogs', '');
        $exclusion_list = array_map('trim', explode(',', $unc_cookie_banner_exclude_blogs));
        // Checking if the current blog ID is in the exclusion list.
        if (in_array((string) $current_blog_id, $exclusion_list, true)) {
            return; // If the current blog is in the exclusion list, we don’t enqueue scripts and exit.
        }

        $unc_cookie_banner_text = $this->get_option('unc_cookie_banner_text', "This website uses cookies and similar technologies to understand visitor experiences. By using this website, you consent to UNC-Chapel Hill's cookie usage in accordance with their <a href='https://www.unc.edu/about/privacy-statement/'>Privacy Notice</a>.");
        $unc_cookie_banner_button_text = $this->get_option('unc_cookie_banner_button_text', 'I Accept');

        // removed in 1.0.3 for the public class prop $this->plugin_version
        // $plugin_version = '1.0'; // added to update version number hopefully to help caching on updates

        $script_path = 'dist/cookie-banner.js';
        $script_assets = $this->get_scripts_asset_defs($script_path);
        // error_log( var_export($script_assets,true));
        wp_enqueue_script('cookie-banner-script', plugins_url($script_path, __FILE__), $script_assets['dependencies'], $script_assets['version'], true);

        wp_localize_script('cookie-banner-script', 'cookieBannerSettings', array(
            'text' => wp_json_encode($unc_cookie_banner_text),
            'buttonText' => esc_js($unc_cookie_banner_button_text),
            'blogId' => get_current_blog_id(),
        ));

        wp_enqueue_style('cookie-banner-custom-style', plugins_url('./dist/cookie-banner.css', __FILE__), array(), $script_assets['version']);
    }

    public function enqueue_admin_styles($hook) {
        $page_hook_suffix = is_multisite() ? 'toplevel_page_cookie-banner-settings' : 'toplevel_page_cookie-banner-settings';

        if ($hook === $page_hook_suffix) {
            wp_enqueue_style('cookie-banner-admin-style', plugins_url('./dist/cookie-banner.css', __FILE__), array(), $this->plugin_version);
        }
    }

    /**
     * get the assets definitions from the assets.php for a script
     *
     * @param string $script_path relative path to the script
     * @return array
     */
    public function get_scripts_asset_defs($script_path): array {
        $assets_defs = [];
        $assets_path = str_replace('.js', '.asset.php', $script_path);
        // error_log('the path is ' . $assets_path);
        $assets_full_path = plugin_dir_path(__FILE__) . $assets_path;
        // error_log('the file exists:' . var_export(file_exists($file_path), true));

        // make sure the file exists
        if (file_exists($assets_full_path)) {
            $assets_body = include($assets_full_path);
            // error_log('$assets_body:' . var_export($assets_body, true));
        } else {
            $assets_body = NULL;
        }

        // get deps if set
        if ($assets_body && isset($assets_body['dependencies'])) {
            $assets_defs['dependencies'] = $assets_body['dependencies'];
        } else {
            $assets_defs['dependencies'] = [];
        }

        // get version if set
        if ($assets_body && isset($assets_body['version'])) {
            $assets_defs['version'] = $assets_body['version'];
        } else {
            $assets_defs['version'] = $this->plugin_version;
        }
        // error_log( var_export(  $assets_defs, true ) );
        return $assets_defs;
    }

    public function activate_plugin() {
        $can_activate = is_multisite() ? is_super_admin() : current_user_can('activate_plugins');

        if (!$can_activate) {
            wp_die('You do not have permission to activate this plugin. Please contact ITS - Digital Services.');
        }
        // Additional activation tasks could go here
    }

    public function deactivate_plugin() {
        $can_deactivate = is_multisite() ? is_super_admin() : current_user_can('deactivate_plugins');

        if (!$can_deactivate) {
            wp_die('You do not have permission to deactivate this plugin. Please contact ITS - Digital Services.');
        }
        // Additional deactivation tasks could go here
    }
}

new UNCCookieBanner();
