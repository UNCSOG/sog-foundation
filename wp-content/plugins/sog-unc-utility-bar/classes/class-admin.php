<?php

/**
 * Administration
 * Setting that are here
 * Banner Massage
 * 
 * @package Utility_Bar\Admin 
 * 
 */

namespace Utility_Bar;

class Admin {

      public static $instance;

      function __construct() {
            add_action('admin_init', array($this, 'settings_init'));
            add_action('admin_menu', array($this, 'settings_page'));
            add_action('admin_enqueue_scripts', array($this, 'wp_admin_scripts'));

            //for debugging
            //add_action('register_setting', array($this, 'debug_register_setting'), 10, 3);
      }

      /**
       * to see whats coming thru the register settings
       *
       * @param [type] $option_group
       * @param [type] $option_name
       * @param [type] $args
       * @return void
       */
      function debug_regsiter_setting($option_group, $option_name, $args) {
            if ('unc-utility-bar' == $option_group) {
                  //error_log(var_export($option_name, true));
                  //error_log(var_export($args, true));
            }
      }

      /**
       * make the page
       *
       * @return void
       */
      function settings_page() {
            add_submenu_page('options-general.php', 'Notifications and Site Banner', 'Site Banner', 'manage_options', 'site-banner', array($this, 'settings_page_callback'));
      }

      /**
       * render the page
       *
       * @return void
       */
      function settings_page_callback() {
            //get vars for the template

            //load the template
            $template_loader = new Template_Loader();
            $template_loader
                  ->get_template_part('admin/admin');
      }

      /**
       * adding settings
       *
       * @return void
       */
      function settings_init() {
            //error_log('hello world from Utility_Bar\Admin::settings_init');
            //this is for the site banner bits
            //register new settings for "site-banner" page

            //settings for the utility bar
            if( !\UTILITY_BAR\Core::legacy_has_utility_bar() ){
                  register_setting('unc_utility_bar', '_unc_utility_bar_display', array(
                        'type' => 'string',
                        'description' => 'display the utility bar',
                        'default' => 'yes',
                        'show_in_rest' => false
                  ));

                  add_settings_field(
                        'unc_utility_bar_display',
                        'Show Utility Bar',
                        function () {
                              $this->get_settings_template('utility-bar-display-checkbox');
                        },
                        'unc_utility_bar',
                        'unc_utility_bar_section'
                  );

                  // register a new section in the "site-banner" page
                  add_settings_section(
                        'unc_utility_bar_section',
                        'Utitlity Bar',
                        null, //no header is added
                        'unc_utility_bar'
                  );
            }

            //register the settings for the unc_utility_bar_site_banner_section
            register_setting('unc_utility_bar', '_unc_utility_banner_msg_string', array(
                  'type' => 'string',
                  'description' => 'the text message to put in the display area',
                  'default' => '',
                  'show_in_rest' => false
            ));

            add_settings_field( 
                  'banner_msg_wysiwyg', 
                  'Banner Message', 
                  array( $this, 'render_wysiwyg'),
                  'unc_utility_bar', 
                  'unc_utility_bar_site_banner_section' 
          );

            register_setting('unc_utility_bar', '_unc_utility_banner_display_bool', array(
                  'type' => 'string',
                  'description' => 'display the custom site banner',
                  'default' => 0,
                  'show_in_rest' => false
            ));

            //add the fields for the unc_utility_bar_site_banner_section
            // add_settings_field(
            //       'banner_msg_string',
            //       'Banner Message',
            //       // function () {
            //       //       $this->get_settings_template('banner-msg-textarea');
            //       // },
            //       array( $this, 'render_banner_msg_textbox'),
            //       'unc_utility_bar',
            //       'unc_utility_bar_site_banner_section'
            // );

            

            add_settings_field(
                  'banner_display_bool',
                  'Show Banner',
                  function () {
                        $this->get_settings_template('banner-display-checkbox');
                  },
                  'unc_utility_bar',
                  'unc_utility_bar_site_banner_section'
            );

            // register a new section in the "site-banner" page
            add_settings_section(
                  'unc_utility_bar_site_banner_section',
                  'Site Banner',
                  null, //no header is added
                  'unc_utility_bar'
            );
      }

      /**
       * Includes the field from the templates/settings-fields directory
       *
       * @param [type] $file_slug
       * @return void
       */
      function get_settings_template($file_slug) {
            include_once(UTILITY_BAR_PLUGIN_DIR . 'templates/admin/settings-fields/' . $file_slug . '.php');
      }


      /**
       * render the wysiwyg used for the 
       *
       * @return void
       */
      function render_wysiwyg(){
            $options = get_option('_unc_utility_banner_msg_string', "");
            $content = isset( $options ) ?  $options : false;
            wp_editor( $content, 'banner_msg_string', array( 
                  'media_buttons' => false,
                  'textarea_name' => '_unc_utility_banner_msg_string',
                  'textarea_rows' => '10',
                  'wpautop' => true,
                  
            ) );
              
      }


      function wp_admin_scripts($hook) {
            if ('settings_page_site-banner' !== $hook) {
                  return;
            }

            $version = Core::$version;
            $deps = [];

            $assets_path = UTILITY_BAR_PLUGIN_DIR . 'build/admin/index.asset.php';
            if (file_exists($assets_path)) {
                  $assets_body = include_once($assets_path);
                  $version = $assets_body['version'];
                  $deps = $assets_body['dependencies'];
            }

            wp_enqueue_style('alert-service-admin-styles', UTILITY_BAR_PLUGIN_URL . 'build/admin/index.css', [], $version);
      }

      /**
       * you only need this once
       *
       * @return UTILITY_BAR\Admin
       */
      public static function getInstance(): Admin {
            static::$instance = static::$instance ?? new static();
            return static::$instance;
      }
}
