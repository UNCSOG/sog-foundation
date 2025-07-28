<?php
/*
  Plugin Name: Toolset Forms
  Plugin URI: https://toolset.com/home/toolset-components/?utm_source=plugin&utm_medium=gui&utm_campaign=forms#forms
  Description: Create Edit Delete WordPress content (ie. posts, pages, custom posts) from the front end using fully customizable forms
  Version: 2.6.23
  Author: OnTheGoSystems
  Author URI: http://www.onthegosystems.com/
  License: GPLv2
 */



// Abort if called directly.
if ( ! function_exists( 'add_action' ) ) {
    die( 'Toolset Forms is a WordPress plugin and can not be called directly.' );
}


// Abort if the plugin is already loaded.
if ( defined( 'CRED_FE_VERSION' ) ) {
    return;
}


/*
 * ---------------------------------------------
 * CONSTANTS
 * ---------------------------------------------
 */

 /**
  * Plugin version
  */
define( 'CRED_FE_VERSION', '2.6.23' );

require_once dirname( __FILE__ ) . '/loader.php';
