<?php

/**
 * Plugin Name: SOG UNC Utility Bar
 * Description: The Unified UNC Utility bar with Alerts from Alert Carolina and a proper place to put your notifications on top of the page.  It is Network only. Modified for the School of Government by Lindsay Hoyt, SOG IT.
 * Author: H. Adam Lenz, UNC Digital Services
 * Co-Author: Lindsay Hoyt, SOG IT
 * Version: 0.0.2
 * License: GNU General Public License (Version 2 - GPLv2)
 */

namespace Utility_Bar;

use WP_CLI\Dispatcher\CommandFactory;

if (!defined('ABSPATH')) {
    exit;
}

define('SOG_UTILITY_BAR_PLUGIN_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('SOG_UTILITY_BAR_PLUGIN_URL', plugin_dir_url(__FILE__));


// If you see an error about missing src/vendor/autoload.php, run this command in the plugin directory:
// composer install
if (!class_exists('Utility_Bar\\Autoloader')) {
    include_once SOG_UTILITY_BAR_PLUGIN_DIR . 'classes/class-autoloader.php';
}

// Only include Composer autoload if not already loaded
if (!class_exists('ComposerAutoloaderInit')) {
    $autoload_path = SOG_UTILITY_BAR_PLUGIN_DIR . 'src/vendor/autoload.php';

    if (file_exists($autoload_path)) {
        include_once $autoload_path;
    }
}


class Core {

    /**
     * rest_prefix
     * prefix for custom rest api endpoints
     * @var string
     */
    public static $rest_prefix = 'utility-bar-alert-service/v1';

    /**
     * version
     *
     * @var string
     */
    public static $version = '2.2.1';

    /**
     * the nonce var
     *
     * @var string
     */
    public static $nonce = 'alert-service';

    function __construct() {
        $autoloader = new Autoloader(__NAMESPACE__, SOG_UTILITY_BAR_PLUGIN_DIR);

        spl_autoload_register(array($autoloader, 'autoloader'));

        //cli commands for the alert service
        if (defined('WP_CLI') && class_exists('WP_CLI')) {
            /**
             * Registers cli commands
             */
            $alert_service_cli = new CLI_Alert_Service();

            \WP_CLI::add_command('alert-service', $alert_service_cli);
        }

        add_action('after_setup_theme', function () {
            //  register block editor blocks if we are using a block theme
            // this check might need to be adjusted in the future because block these that are not FSE would need the admin menu
            if (self::using_blocks()) {
                $block_controller = Block_Controller::getInstance();
            } else { //if not using the block theme we go classic
                $admin = Admin::getInstance();
                //you can change stack order here
                //put the element in order of how you want them to appear
                //they all hook wp_open_body later if we are in a classic theme
                $utility_bar = Blocks\Utility_Bar::getInstance();
                $alert_banner = Blocks\Alert_Banner::getInstance();
                $site_banner = Site_Banner_Display::getInstance();

                add_action('customize_preview_init', array($this, 'customizer_preview_scripts'));
            }
        });

        // displays the banner and adds a ton of filters
        // Alert_Banner_Display::getInstance();

        // the rest endpoints should come after everything else is setup
        $rest = Rest_Endpoints::getInstance();
        $rest->check_passwords(true);

        // the admins
        // is_plugin_active_for_network();
        $network_admin = Network_Admin::getInstance();

        //add scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 1);

        //add a class if the bar is turned on
        add_filter('body_class', array($this, 'add_body_class'), 10, 1);

        //remove the default skip to content button
        remove_action('wp_footer', 'the_block_template_skip_link');
    }

    function enqueue_scripts($hook) {
        wp_enqueue_style('alert-service-frontend-style', SOG_UTILITY_BAR_PLUGIN_URL . 'build/frontend/index.css', [], self::$version);
        wp_enqueue_script('container-query-polyfill', 'https://cdn.jsdelivr.net/npm/container-query-polyfill@1/dist/container-query-polyfill.modern.js', [], self::$version);
        if ('no' != get_theme_mod('utility_bar_display') && !Core::using_blocks()) {
            wp_enqueue_style('utility-bar-utility-bar-styles', SOG_UTILITY_BAR_PLUGIN_URL . 'blocks/utility-bar/build/utility-bar-block.css', [], self::$version);
            wp_enqueue_style('alert-utility-bar-styles', SOG_UTILITY_BAR_PLUGIN_URL . 'blocks/alert-banner/build/frontend/alert-banner-frontend.css', [], self::$version);
        }
    }

    function customizer_preview_scripts() {
        //error_log('self::using_blocks():' . var_export(self::using_blocks(), true));
        if (!self::using_blocks() && !self::legacy_has_utility_bar()) {
            $script = 'build/customizer/customizer.js';
            $defs = self::get_scripts_asset_defs($script);
            $deps = array(...$defs['dependencies'], 'customize-preview');
            //error_log(var_export($deps, true));
            wp_enqueue_script('unc_customizer_preview', SOG_UTILITY_BAR_PLUGIN_URL . $script, $deps, $defs['version'], true);
        }
    }

    public static function get_scripts_asset_defs($script_path) {
        $assets_defs = [];
        $assets_path = SOG_UTILITY_BAR_PLUGIN_DIR . str_replace('.js', '.asset.php', $script_path);
        //error_log('the path is ' . $assets_path);
        //error_log('the file exists:' . var_export(file_exists($assets_path), true));

        if (file_exists($assets_path)) {
            $assets_body = include($assets_path);
            //error_log('$assets_body:' . var_export($assets_body, true));
        }

        if (isset($assets_body['dependencies'])) {
            $assets_defs['dependencies'] = $assets_body['dependencies'];
        } else {
            $assets_defs['dependencies'] = [];
        }

        if (isset($assets_body['version'])) {
            $assets_defs['version'] = $assets_body['version'];
        } else {
            $assets_defs['version'] = self::$version;
        }
        //error_log( var_export(  $assets_defs, true ) );
        return $assets_defs;
    }

    public function add_body_class($classes) {
        $classes[] = 'unc-utility-bar-plugin-enabled';
        return $classes;
    }

    /**
     *  see if we are using the gutenberg busting plugins
     *
     * @return [bool] if we are using the plugins, we are not using blocks
     */
    static function using_blocks() {
        // error_log( 'isblocktheme' . var_export( wp_is_block_theme(), true ) );
        // error_log( var_export($GLOBALS['wp_theme_directories'],true) );
        // if (class_exists('Classic_Editor') || class_exists('Gutenberg_Ramp')) {
        if (wp_is_block_theme()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * some themes that we know of have built the utility bar in.
     * we are ignoring those for now
     * we should remove the functionality from those themes to use the plugin
     *
     * @return [bool] if the theme is one of our problem children
     */
    static function legacy_has_utility_bar() {
        $has_utility_bar = false;
        // add or remove theme->template values here if you don't want the utility bar here
        $themes_with_utility_bar = array(
            'alert-carolina',
            'apsa',
            'aspsa',
            //'heelium',//and also white coat because it's a child
            //'student-aid-2',
            'unc-sites-base-theme',
            //'unc-ssw-theme',
            'unc-wordpress-theme',
            'unc-wilson', //its added in the templates, so if wilson is using classic it would show twice
            'womenscenter'
        );
        $theme = wp_get_theme();
        //error_log('\UTILITY_BAR\Core::legacy_has_utility_bar::$theme->template:' . var_export($theme->template, true));

        //filter the array so that the utility bar can be added to the body_open under other conditions
        $themes_with_utility_bar = \apply_filters('utility_bar_themes_with_utility_bar', $themes_with_utility_bar);

        if (in_array($theme->template, $themes_with_utility_bar)) {
            $has_utility_bar = true;
            //error_log('\UTILITY_BAR\Core::legacy_has_utility_bar::in_array:true');
        }
        //error_log('\UTILITY_BAR\Core::legacy_has_utility_bar::$has_utility_bar:' . var_export($has_utility_bar, true));
        return $has_utility_bar;
    }
}

new \UTILITY_BAR\Core();
