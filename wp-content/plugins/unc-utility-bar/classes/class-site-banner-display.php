<?php

/**
 * displays the banner
 * to edit the styles of the banner, edit the class style params
 * emergency and crime = emergenecy
 * 
 * @package Utility_Bar
 * 
 */

namespace Utility_Bar;

class Site_Banner_Display {

      /**
       * singleton instance
       *
       * @var [\Utility_Bar\Site_Banner_Display]
       */
      private static $instance;


      /**
       * the key for the metadata as it is stored in the database
       *
       * @var string
       */
      public static $stored_banner_meta_key = '_unc_utility_banner_msg_string';

      /**
       * the key for if the alert is published or not
       *
       * @var string
       */
      public static $published_banner_meta_key = '_unc_utility_banner_display_bool';


      function __construct() {
            add_action( 'init', array( $this, 'initialize_banner') );
      }


      function initialize_banner(){
            if (self::check_site_banner_is_displayed()) {
                  $hook = apply_filters('unc_utility_bar\site_banner_display_hook', 'wp_body_open');
                  //error_log('initialize_banner::hooking to ' . $hook );
                  add_action($hook, array($this, 'add_site_banner_to_wp_body_open'));
                  add_filter('body_class', array($this, 'add_body_class'), 10, 1);
            }
      }

      function add_site_banner_to_wp_body_open() {
            $show_alert_banner = apply_filters('unc_utility_bar\site_banner_display', true);
            if ($show_alert_banner) {
                  $this->generate_site_banner();
            } else {
                  return '';
            }
      }

      function generate_site_banner() {
            $data = array();
            $msg = get_option(self::$stored_banner_meta_key);
            $data['msg'] = $msg;

            //the template loader
            $template_loader = new Template_Loader();
            $template_loader
                  ->set_template_data($data)
                  ->get_template_part('site', 'banner');
      }

      /**
       * set a body class depending on if the alert is published
       *
       * @param [array] $classes
       * @return void
       */
      function add_body_class($classes) {

            $classes[] = 'utility-bar-site-banner-displayed';
            return $classes;
      }


      /**
       * check to see if the site banner should be displayed
       *
       * @return void
       */
      static function check_site_banner_is_displayed() {
            $display = get_option(self::$published_banner_meta_key);
            //error_log( var_export($display,true));
            return $display;
      }

      /**
       * you only need this once
       *
       * @return Site_Banner_Display
       */
      public static function getInstance(): Site_Banner_Display {
            static::$instance = static::$instance ?? new static();
            return static::$instance;
      }
}
