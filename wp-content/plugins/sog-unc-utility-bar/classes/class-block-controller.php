<?php

/**
 * blocks
 *
 * @package Alert_Service
 *
 */

namespace Utility_Bar;

class Block_Controller {

    /**
     * singleton instance
     *
     * @var [\Alert_Service\Blocks]
     */
    private static $instance;

    public $using_blocks = false;

    public $blocks = [];

    function __construct() {
        add_action('init', array($this, 'register_blocks'));
        //add_filter('block_type_metadata_settings', array($this, 'debug_register_blocks'), 10, 2);


    }

    /**
     * Filters the settings determined from the block type metadata.
     *
     * @since 5.7.0
     *
     * @param array $settings Array of determined settings for registering a block type.
     * @param array $metadata Metadata provided for registering a block type.
     */
    function debug_register_blocks($settings, $metadata) {
        error_log('debug_register_blocks:$settings->' . var_export($settings, true));
        error_log('debug_register_blocks:$metadata->' . var_export($metadata, true));
        return $settings;
    }

    /**
     * register the block(s) and hook into their render_callbacks
     *
     * @return void
     */
    function register_blocks() {

        //register alert banner block
        $alert_banner = \Utility_Bar\Blocks\Alert_Banner::getInstance();
        register_block_type_from_metadata(
            SOG_UTILITY_BAR_PLUGIN_DIR . 'blocks/alert-banner',
            array('render_callback' => function ($atts) use ($alert_banner) {
                //$alert_banner = \Utility_Bar\Blocks\Alert_Banner::getInstance();
                return $alert_banner->render_callback($atts);
            })
        );

        //register utility bar block
        register_block_type_from_metadata(
            SOG_UTILITY_BAR_PLUGIN_DIR . 'blocks/utility-bar',
        );
    }

    function get_block_json_data($block_json_path) {
        if (file_exists($block_json_path)) {
            $contents = file_get_contents($block_json_path);
            $json_array = json_decode($contents, true);
            return $json_array ? $json_array : false;
        }
    }

    /**
     * registers a style
     * use enqueue_block_editor_assets or enqueue_block_assets to get here
     *
     * @param [type] $file_path relative to SOG_UTILITY_BAR_PLUGIN_URL
     * @return void
     */
    public static function register_block_script($path, $slug, $type = 'script') {
        //the path
        $url = SOG_UTILITY_BAR_PLUGIN_URL . $path;
        $dir = SOG_UTILITY_BAR_PLUGIN_DIR . $path;
        $version = Core::$version;
        $deps = [];
        //the assets file
        $assets_file = $dir . $slug . '.asset.php';
        if (file_exists($assets_file)) {
            $assets_body = include($assets_file);
            $version = $assets_body['version'];
            $deps = $assets_body['dependencies'];
            //error_log( $slug. ': ' .  var_export( $assets_body['dependencies']), true );
        }

        if ('script' == $type) {
            $js_file =  $url . $slug . '.js';
            //error_log(  $slug. '.js' . ': ' . var_export( $assets_body['dependencies'], true) );
            wp_register_script($slug . '-script',  $js_file, $deps, $version);
        } elseif ('style' == $type) {
            $css_file =  $url . $slug . '.css';
            wp_register_style($slug . '-style',  $css_file, [], $version);
        }
    }

    /**
     * Whether or not we are rendering a block on the backend (editor).
     * they are working on putting this in core https://github.com/WordPress/gutenberg/issues/23810
     *
     * @return boolean
     */
    public static function is_editing_block_on_backend() {
        return defined('REST_REQUEST') && true === REST_REQUEST && 'edit' === filter_input(INPUT_GET, 'context', FILTER_UNSAFE_RAW);
    }

    public static function block_is_using_style_variation($block) {
        //error_log( var_export( $block->parsed_block, true ));
        if( !isset( $block->parsed_block['attrs']['className'] ) ){
            return false;
        }

        $class_name = $block->parsed_block['attrs']['className'];

        if (is_string($class_name) && str_contains($class_name,  'is-style-')) {
            return true;
        }

        return false;
    }

    /**
     * you only need this once
     *
     * @return Block_Controller
     */
    public static function getInstance(): Block_Controller {
        static::$instance = static::$instance ?? new static();
        return static::$instance;
    }
}
