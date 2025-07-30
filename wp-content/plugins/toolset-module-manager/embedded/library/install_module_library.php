<?php
//Define WP root path
$module_man_root_path = get_wordpress_base_path();

// Load WordPress
if(file_exists($module_man_root_path  . '/wp-load.php')) {
	require_once($module_man_root_path  . '/wp-load.php');
}

require_once('Class_Install_Library.php');
$Class_Install_Library = new Class_Install_Library;

function get_wordpress_base_path() {
	$dir = dirname(__FILE__);
	do {		
		if( file_exists($dir."/wp-load.php") ) {
			return $dir;
		}
	} while( $dir = realpath("$dir/..") );
	return null;
}
?>