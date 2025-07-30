<?php

/**
 * Abstract singleton for hooking into the template_include filter and selecting different page template for content
 * that has an Layout assigned. Plus related functionality.
 *
 * It's locate_template(), get_header() and get_footer() methods should be used instead of merely including/requiring
 * parts of templates.
 */
abstract class WPDDL_Integration_Theme_Template_Router_Abstract {

	/**
	 * Singleton parent.
	 *
	 * @link http://stackoverflow.com/questions/3126130/extending-singletons-in-php
	 * @return WPDDL_Theme_Integration_Abstract Instance of calling class.
	 */
	final public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}

		return $instances[ $called_class ];
	}


	protected function __construct() {
		add_filter( 'template_include', array( $this, 'template_include' ) );
	}


	protected function __clone() { }


	/**
	 * Hooked into the template_include filter.
	 *
	 * @param string $template Default template path.
	 * @return string Template path.
	 */
	public function template_include( $template ) {
		return $template;
	}


	/**
	 * This needs to be overridden by individual integration plugins.
	 *
	 * @return string Absolute path (without the final slash) to the directory where custom page template files are located.
	 */
	protected abstract function get_template_path();


	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * Copy of native WordPress locate_template() function that looks for the template in specified directory first.
	 * The directory with templates is defined via self::get_template_path() method. If the template file is not
	 * found there, the behaviour is identical to native locate_template().
	 *
	 * @link https://developer.wordpress.org/reference/functions/locate_template/
	 *
	 * @param string|array $template_names
	 * @param bool $load
	 * @param bool $require_once
	 * @return string The template filename if one is located.
	 */
	public function locate_template( $template_names, $load = false, $require_once = true ) {

		$located_file = '';
		$template_path = $this->get_template_path();
		foreach( (array)$template_names as $template_name ) {
			if( !$template_name ) {
				continue;
			}
			if( file_exists( $template_path . '/' . $template_name ) ) {
				$located_file = $template_path . '/' . $template_name;
				break;
			} else {
				// Look for the template in a native way without loading it (yet)
				$located_file = locate_template( $template_name, false, $require_once );
				if( !empty( $located_file ) ) {
					// We found something
					break;
				}
			}
		}

		if( $load && '' != $located_file ) {
			load_template( $located_file, $require_once );
		}

		return $located_file;
	}


	/**
	 * Copy of native WordPress function get_header() that uses self::locate_template() instead of it's native version.
	 *
	 * This is a convenience method, necessary for preserving the get_header action call.
	 *
	 * @param null|string $name
	 */
	public function get_header( $name = null ) {

		do_action( 'get_header', $name );

		$templates = array();
		$name = (string)$name;
		if( '' !== $name ) {
			$templates[] = "header-{$name}.php";
		}

		$templates[] = 'header.php';

		$located_template = self::locate_template( $templates, true );

		// Backward compat code will be removed in a future release
		if( '' ==  $located_template ) {
			load_template( ABSPATH . WPINC . '/theme-compat/header.php' );
		}
	}


	/**
	 * Copy of native WordPress function get_footer() that uses self::locate_template() instead of it's native version.
	 *
	 * This is a convenience method, necessary for preserving the get_footer action call.
	 *
	 * @param null|string $name
	 */
	public function get_footer( $name = null ) {

		do_action( 'get_footer', $name );

		$templates = array();
		$name = (string)$name;
		if( '' !== $name ) {
			$templates[] = "footer-{$name}.php";
		}

		$templates[] = 'footer.php';

		$located_template = self::locate_template( $templates, true );

		// Backward compat code will be removed in a future release
		if( '' == $located_template ) {
			load_template( ABSPATH . WPINC . '/theme-compat/footer.php' );
		}
	}

}