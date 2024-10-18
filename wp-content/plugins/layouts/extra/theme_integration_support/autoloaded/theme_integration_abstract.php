<?php

/**
 * Base class for main classes of integration plugins.
 *
 * It handles singleton functionality and executing the integration only when all conditions are met, which means
 * - the relevant theme is active
 * - no other integration was executed before
 *
 * IMPORTANT: Whenever you introduce any change to interface of this class, you need to increase Theme integration API
 * version, otherwise integration plugins might break. Please try to avoid these changes if possible.
 */
abstract class WPDDL_Theme_Integration_Abstract {

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
		$this->base_initialize();
	}


	protected function __clone() {}


	private function base_initialize() {
		// Abort if another integration is already active and deactivate
		if( defined( 'LAYOUTS_INTEGRATION_THEME_NAME' ) ) {
			$this->fail(
					sprintf(
							__( 'Another Layouts integration plugin is already active (integration with "%s").', 'ddl-layouts' ),
							sanitize_text_field( LAYOUTS_INTEGRATION_THEME_NAME )
					),
					true
			);
			return;
		}

		if( ! $this->is_theme_active() ) {
			return;
		}

		// Now it's official.
		define( 'LAYOUTS_INTEGRATION_THEME_NAME', $this->get_theme_name() );

		// Run plugin-specific initialization.
		$init_result = $this->initialize();

		// Check the result.
		if( is_wp_error( $init_result ) ) {
			/** @noinspection PhpUndefinedMethodInspection */
			$this->fail( $init_result->get_error_message(), true );
		}

	}


	/**
	 * Determine whether the expected theme is active and the integration can begin.
	 *
	 * @return bool
	 */
	abstract protected function is_theme_active();


	/**
	 * Name of the supported theme. It will be used as an unique identifier of the integration plugin.
	 *
	 * @return string Theme name
	 */
	abstract protected function get_theme_name();


	/**
	 * @var string Basename of the integration plugin.
	 */
	private $plugin_basename;

	/**
	 * @void set puglin basename
	 **/

	public function set_plugin_base_name( $path ){
		$this->plugin_basename = $path;
	}

	/**
	 * @string path to plugin folder
	 */
	public function get_plugin_base_name(  ){
		return $this->plugin_basename;
	}

	/**
	 * @return string Theme name that can be displayed to the user.
	 */
	protected function get_theme_display_name() {
		return $this->get_theme_name();
	}


	/**
	 * Theme-specific initialization.
	 *
	 * @return bool|WP_Error True when the integration was successful or a WP_Error with a sensible message
	 *     (which can be displayed to the user directly).
	 */
	abstract protected function initialize();


	/**
	 * Show an error message and deactivate the plugin.
	 *
	 * @param string $inner_message Specific description of the failure.
	 * @param bool $deactivate Should the plugin be deactivated? This works only in backend and if called before
	 *    admin_init. In frontend, nothing will happen.
	 *
	 * @deprecated 1.9
	 */
	protected function fail( $inner_message, $deactivate = false ) {
		return;
	}


	public function deactivate_plugin() {
		deactivate_plugins( $this->get_plugin_base_name(  ) );
	}

	protected function add_admin_notice( $type, $message, $wrap_p = true ){
		WPDDL_Messages::add_admin_notice( $type, $message, $wrap_p );
	}
}
