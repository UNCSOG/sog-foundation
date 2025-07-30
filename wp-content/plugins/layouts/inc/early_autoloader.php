<?php

/**
 * A slightly modified copy of Toolset_Common_Autoloader.
 *
 * This is needed because Layouts load very very early and it was designed to autoload many of its
 * classes at that time. That makes it impossible to use Toolset_Common_Autoloader directly.
 *
 * @since 2.2
 */
final class WPDDL_Early_Autoloader {

	private static $instance;

	public static function get_instance() {
		if( self::$instance === null ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	private function __construct() { }

	private function __clone() { }


	private static $is_initialized = false;


	/**
	 * This needs to be called before any other autoloader features are used.
	 */
	public static function initialize() {

		if( self::$is_initialized ) {
			return;
		}

		$instance = self::get_instance();

		// Actually register the autoloader.
		spl_autoload_register( array( $instance, 'autoload' ), true );

		self::$is_initialized = true;
	}


	private $classmap = array();


	/**
	 * Register a classmap.
	 *
	 * Merges given classmap with the existing one.
	 *
	 * The one who is adding mappings is responsible for existence of the files.
	 *
	 * @param string[string] $classmap class name => absolute path to a file where this class is defined
	 * @param null|string $base_path
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public function register_classmap( $classmap, $base_path = null ) {

		if( ! is_array( $classmap ) ) {
			throw new InvalidArgumentException( 'The classmap must be an array.' );
		}

		if( is_string( $base_path ) ) {
			foreach( $classmap as $class_name => $relative_path ) {
				$classmap[ $class_name ] = "$base_path/$relative_path";
			}
		}

		$this->classmap = array_merge( $this->classmap, $classmap );

	}


	/**
	 * Try to autoload a class if it's in the classmap.
	 *
	 * @param string $class_name
	 * @return bool True if the file specified by the classmap was loaded, false otherwise.
	 * @since m2m
	 */
	public function autoload( $class_name ) {

		if( array_key_exists( $class_name, $this->classmap ) ) {
			$file_name = $this->classmap[ $class_name ];

			// If this causes an error, blame the one who filled the $classmap.
			require_once $file_name;

			return true;
		}

		return false;
	}



}