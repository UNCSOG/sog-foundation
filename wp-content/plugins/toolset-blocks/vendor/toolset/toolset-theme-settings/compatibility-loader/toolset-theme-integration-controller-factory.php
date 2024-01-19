<?php

/**
 * Class Toolset_Theme_Integration_Settings_Controllers_Factory
 * @author: Riccardo
 * @since: 2.5
 * Singleton factory class to programmatically build Controllers for Theme Settings Integration
 */
class Toolset_Theme_Integration_Settings_Controllers_Factory{

	private static $instance;

	const CLASS_PREFIX = 'Toolset_Theme_Integration_Settings_%s_Controller';

	private $active_theme = null;
	private $theme_name = null;
	private $theme_slug = null;
	private $theme_parent_name = null;
	private $theme_parent_slug = null;

	public function __construct() {
		$this->set_up_active_theme();
	}

	private function set_up_active_theme() {

		$this->active_theme = wp_get_theme();

		if( ! $this->active_theme instanceof WP_Theme ) {
			// Something went wrong but we'll try to recover.
			$stylesheet = get_stylesheet();
			if( is_string( $stylesheet ) && ! empty( $stylesheet ) ) {
				$this->theme_name = $stylesheet;
				$this->theme_slug = str_replace('-', '_', sanitize_title( $this->theme_name ) );
			}
		} else {
			$this->theme_name = $this->active_theme->get( 'Name' );
			$this->theme_slug = str_replace('-', '_', sanitize_title( $this->theme_name ) );

			if (
				is_child_theme()
				&& $parent_theme = $this->active_theme->parent()
			) {
				$this->theme_parent_name = $parent_theme->get( 'Name' );
				$this->theme_parent_slug = str_replace('-', '_', sanitize_title( $this->theme_parent_name ) );
			}
		}

		// if theme usees Redux options framework to handle options, then use Redux compatibility class
		if( class_exists('ReduxFramework') ){
			$this->theme_slug = 'redux';
		}
	}


	/**
	 * @param string $name
	 * @param mixed $helper
	 * @param mixed $arg_one
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function build( $name, $helper = null, $arg_one = null ) {
		$class_name = $this->build_class_name( $name );

		if ( class_exists( $class_name . '_' . $this->theme_slug ) ) {
			$class_name = $class_name . '_' . $this->theme_slug;
			return new $class_name( $helper, $arg_one );
		}

		if (
			! is_null( $this->theme_parent_slug )
			&& class_exists( $class_name . '_' . $this->theme_parent_slug )
		) {
			$class_name = $class_name . '_' . $this->theme_parent_slug;
			return new $class_name( $helper, $arg_one );
		}

		if( class_exists( $class_name ) ) {
			return new $class_name( $helper, $arg_one );
		}

		throw new Exception( sprintf( '%s does not exist!', $class_name) );
	}

	/**
	 * @param $type
	 *
	 * @return string
	 */
	private function build_class_name( $name ){
		return sprintf(self::CLASS_PREFIX, $name );
	}

	/**
	 * @return Toolset_Theme_Integration_Settings_Collections_Factory
	 */
	public static function getInstance(  )
	{
		if (!self::$instance)
		{
			self::$instance = new Toolset_Theme_Integration_Settings_Controllers_Factory(  );
		}

		return self::$instance;
	}

}
