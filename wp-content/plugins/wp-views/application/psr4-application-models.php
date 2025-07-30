<?php

/**
 * PSR4 Autoloader for ./application/models
 *
 * @since TB 1.3
 */
spl_autoload_register( function( $class ) {
	$prefix = 'OTGS\\Toolset\\Views\\Models\\';
	$root_dir  = __DIR__ . '/models/';

	// Check if $class having $root name.
	$len = strlen( $prefix );
	if( strncmp( $prefix, $class, $len ) !== 0 ) {
		// Foreign class.
		return;
	}

	// Get class without prefix.
	$class_without_prefix = substr( $class, $len );

	// Build class path.
	$class_path = $root_dir . str_replace('\\', '/', $class_without_prefix) . '.php';

	// Load file if exists.
	if ( file_exists( $class_path ) ) {
		require $class_path;
	}
});
