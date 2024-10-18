<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TB_VER', '1.6.13' );

define( 'TB_PATH', __DIR__ );
define( 'TB_PATH_CSS', __DIR__ . '/public/css' );
define( 'TB_PATH_JS', __DIR__ . '/public/js' );

define( 'TB_URL', plugin_dir_url( __FILE__ ) );

if( ! defined( 'TB_BUNDLED_SCRIPT_PATH' ) ) {
	define( 'TB_BUNDLED_SCRIPT_PATH', TB_URL . 'public/js' );
	define( 'TB_HMR_RUNNING', false );
} else {
	define( 'TB_HMR_RUNNING', true );
}

/* Register Autoloader */
require_once TB_PATH . '/psr4-autoload.php';

/* Bootstrap Toolset Blocks */
require_once TB_PATH . '/server/bootstrap.php';


