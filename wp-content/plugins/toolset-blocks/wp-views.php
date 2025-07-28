<?php
/*
Plugin Name: Toolset Blocks
Plugin URI: https://toolset.com/?utm_source=viewsplugin&utm_campaign=views&utm_medium=plugins-list-full-version&utm_term=Visit plugin site
Description: Toolset Blocks lets you create beautiful dynamic sites with ease. Design templates, archives and Views for any content type using a drag-and-drop interface.
Author: OnTheGoSystems
Author URI: https://toolset.com
Version: 1.6.20
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
