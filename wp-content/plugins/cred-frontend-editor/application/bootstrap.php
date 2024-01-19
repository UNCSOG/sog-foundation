<?php

/*
 * Toolset Common Library paths
 */
if ( ! defined( 'WPTOOLSET_COMMON_PATH' ) ) {
	define( 'WPTOOLSET_COMMON_PATH', CRED_ABSPATH . '/vendor/toolset/toolset-common' );
}

// Load legacy Toolset Forms
require_once CRED_ABSPATH . '/library/toolset/cred/plugin.php';


// Jumpstart new Toolset Forms
require_once CRED_ABSPATH . '/application/controllers/main.php';
$cred_main = new CRED_Main();
$cred_main->initialize();
