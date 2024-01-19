<?php


// Load OTGS/UI
require_once TACCESS_PLUGIN_PATH . '/vendor/otgs/ui/loader.php';
otgs_ui_initialize( TACCESS_PLUGIN_PATH . '/vendor/otgs/ui', TACCESS_PLUGIN_URL . '/vendor/otgs/ui' );


/**
 * Load onthego resources
 */
require TACCESS_PLUGIN_PATH . '/vendor/toolset/onthego-resources/loader.php';
onthego_initialize( TACCESS_PLUGIN_PATH . '/vendor/toolset/onthego-resources', TACCESS_PLUGIN_URL
	. '/vendor/toolset/onthego-resources/' );

/**
 * Load Toolset common resources
 */
require TACCESS_PLUGIN_PATH . '/vendor/toolset/toolset-common/loader.php';
toolset_common_initialize( TACCESS_PLUGIN_PATH . '/vendor/toolset/toolset-common', TACCESS_PLUGIN_URL
	. '/vendor/toolset/toolset-common/' );

// public functions
require_once( dirname( __FILE__ ) . '/functions.php' );

// Main Access class
require_once( dirname( __FILE__ ) . '/controllers/main.php' );

// Initialize legacy code
require_once( dirname( __FILE__ ) . '/../legacy/types-access.php' );
\OTGS\Toolset\Access\Main::initialize();
