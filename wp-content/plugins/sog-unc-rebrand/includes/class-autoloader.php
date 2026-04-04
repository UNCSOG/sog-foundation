<?php
/**
 * Simple class loader for the plugin namespace.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Autoloader {
	/**
	 * Register the autoloader callback.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Load plugin classes from the includes directory.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$path_parts     = array_map( 'strtolower', explode( '\\', $relative_class ) );
		$class_name     = 'class-' . str_replace( '_', '-', array_pop( $path_parts ) ) . '.php';
		$path_parts[]   = $class_name;
		$file_path      = trailingslashit( SOG_UNC_REBRAND_PATH . 'includes' ) . implode( '/', $path_parts );

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
}
