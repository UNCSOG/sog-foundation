<?php

/**
 * Load theme settings and all dependencies.
 *
 * @todo Check why this needs to be a singleton at all.
 * @todo Abstract and extract the logic to determine the curent theme and theme parent, and spread as a dependency.
 */
class Toolset_Theme_Settings_Bootstrap {

	/** @var \Toolset_Theme_Settings_Bootstrap */
	private static $instance;

	/**
	 * Component initialization:
	 * - Register inc and utils.
	 * - Broadcast that the feature is available.
	 */
	public function initialize() {
		$this->register_utils();
		$this->register_inc();

		add_filter( 'toolset_is_theme_settings_available', '__return_true' );

		/**
		 * Toolset Theme Settings is completely initialized.
		 *
		 * @param Toolset_Theme_Settings_Bootstrap instance
		 * @since 0.9.0
		 */
		do_action( 'toolset_theme_settings_loaded', $this );
	}


	/**
	 * Singleton.
	 *
	 * @return \Toolset_Theme_Settings_Bootstrap
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new Toolset_Theme_Settings_Bootstrap();
		}
		return self::$instance;
	}

	/**
	 * Register component utils: autoloader.
	 */
	public function register_utils() {
		// This needs to happen very very early
		require_once TOOLSET_THEME_SETTINGS_PATH . '/utils/autoloader.php';
		Toolset_Theme_Settings_Autoloader::initialize();
	}

	/**
	 * Register componenr inc:
	 * - autoloaded classes.
	 * - compatibility loader.
	 */
	public function register_inc(){
		$this->register_autoloaded_classes();
		$toolset_compatibility_loader = Toolset_Compatibility_Loader::get_instance();
		$toolset_compatibility_loader->initialize();
	}


	/**
	 * Register autoloadable classes from the classmap.
	 *
	 * @since 0.9.0
	 */
	private function register_autoloaded_classes() {
		$autoload_classmap_file = TOOLSET_THEME_SETTINGS_PATH . '/autoload_classmap.php';

		if ( ! is_file( $autoload_classmap_file ) ) {
			// abort if file does not exist
			return;
		}

		$autoload_classmap = include( $autoload_classmap_file );

		if ( is_array( $autoload_classmap ) ) {
			$autoloader = Toolset_Theme_Settings_Autoloader::get_instance();
			$autoloader->register_classmap( $autoload_classmap );
		}
	}

};
