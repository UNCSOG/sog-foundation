<?php
/*
Plugin Name: Toolset Views
Plugin URI: https://toolset.com/?utm_source=viewsplugin&utm_campaign=views&utm_medium=plugins-list-full-version&utm_term=Visit plugin site
Description: When you need to create lists of items, Views is the solution. Views will query the content from the database, iterate through it and let you display it with flair. You can also enable pagination, search, filtering and sorting by site visitors.
Author: OnTheGoSystems
Author URI: https://toolset.com
Version: 3.6.20
*/



if ( defined( 'WPV_VERSION' ) ) {
	require_once dirname( __FILE__ ) . '/deactivate/by-existing.php';
	wpv_force_deactivate_by_blocks( plugin_basename( __FILE__  ) );
} elseif ( defined( 'TB_VERSION' ) ) {
	// Check for Toolset Blocks as standalone plugin (early beta packages).
	require_once dirname( __FILE__ ) . '/deactivate/by-blocks-beta.php';
	wpv_force_deactivate_by_blocks_beta( plugin_basename( __FILE__  ) );
} else {
	define( 'WPV_VERSION', '3.6.20' );
	require_once dirname( __FILE__ ) . '/loader.php';
}
