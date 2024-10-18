<?php
/*
Plugin Name: Toolset Module Manager Embedded
Plugin URI: https://toolset.com
Description: Create reusable modules comprising of Types, Views and Forms parts that represent complete functionality.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version: 1.6.9
*/

/** THIS FILE SHOULD ONLY EXISTS IN THE /embedded/ directory */

require dirname(__FILE__) . '/onthego-resources/loader.php';
if ( ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) || is_ssl() ) {
	onthego_initialize(dirname(__FILE__) . '/onthego-resources/',
	rtrim( str_replace( 'http://', 'https://', plugins_url() ), '/' ) . '/' . basename( dirname( __FILE__ ) ) . '/onthego-resources/');
} else {
	onthego_initialize(dirname(__FILE__) . '/onthego-resources/',
	plugins_url() . '/' . basename( dirname( __FILE__ ) ) . '/onthego-resources/');
}

add_action( 'plugins_loaded', 'mm_embedded_load_or_deactivate' );

function mm_embedded_load_or_deactivate() {
	if ( defined('MODMAN_RUN_MODE') ) {

		//Full plugin mode detected!
		add_action( 'admin_init', 'mm_embedded_deactivate' );
		add_action( 'admin_notices', 'mm_embedded_deactivate_notice' );

	} else {
		//Run the embedded mode!
		define( 'MODULE_MANAGER_EMBEDDED_ALONE', true );
		require_once 'plugin.php';
	}
}

function mm_embedded_deactivate() {
	$plugin = plugin_basename( __FILE__ );
	deactivate_plugins( $plugin );
}

function mm_embedded_deactivate_notice() {
    ?>
    <div class="error">
        <p>
			<?php _e( 'Module Manager Embedded was <strong>deactivated</strong>! You are already running the complete Module Manager plugin, so this one is not needed anymore.', 'module-manager' ); ?>
		</p>
    </div>
    <?php
}
