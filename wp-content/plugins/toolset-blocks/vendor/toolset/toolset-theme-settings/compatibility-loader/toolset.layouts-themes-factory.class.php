<?php

/**
 * Load bundled compatibility for specific themes.
 *
 * @todo Turn into a proper factory without class names guessing!
 */
class Toolset_Compatibility_Theme_Handler_Factory{
	private $active_theme = null;
	private $theme_name = null;
	private $theme_slug = null;
	private $theme_parent_name = null;
	private $theme_parent_slug = null;
	protected $running_instance = null;

	public function __construct() {
		$this->set_up_active_theme();
		$this->running_instance = $this->load_class();
	}

	private function set_up_active_theme() {
		$this->active_theme = wp_get_theme();

		if ( $this->active_theme instanceof WP_Theme ) {
			$this->theme_name = $this->active_theme->get( 'Name' );
			$this->theme_slug = str_replace('-', '_', sanitize_title( $this->theme_name ) );

			if (
				is_child_theme()
				&& $parent_theme = $this->active_theme->parent()
			) {
				$this->theme_parent_name = $parent_theme->get( 'Name' );
				$this->theme_parent_slug = str_replace('-', '_', sanitize_title( $this->theme_parent_name ) );
			}
		} else {
			// Something went wrong but we'll try to recover.
			$stylesheet = get_stylesheet();
			if( is_string( $stylesheet ) && ! empty( $stylesheet ) ) {
				$this->theme_name = $stylesheet;
				$this->theme_slug = str_replace('-', '_', sanitize_title( $this->theme_name ) );
			}
		}
	}

	private function is_theme_integration_active(){
		if( defined( 'TOOLSET_INTEGRATION_PLUGIN_THEME_NAME' ) && TOOLSET_INTEGRATION_PLUGIN_THEME_NAME === $this->theme_name ){
			return true;
		} else {
			return false;
		}
	}

	private function load_class(){
		$instance = null;

		if ( ! $this->is_theme_integration_active() ) {

			$helper = new Toolset_Theme_Integration_Settings_Helper();
			$helper->set_current_theme_data( array(
				'theme_name' => $this->theme_name,
				'theme_slug' => $this->theme_slug,
				'theme_parent_name' => $this->theme_parent_name,
				'theme_parent_slug' => $this->theme_parent_slug,
			) );
			$validator = new Toolset_Theme_Integration_Settings_Config_Validator();
			$update_manager = new Toolset_Theme_Integration_Settings_Config_Update( $validator );
			$update_manager->setup();
			$populate_manager = new Toolset_Theme_Integration_Settings_Config_Populate( Toolset_Theme_Integration_Settings_Models_Factory::getInstance(), Toolset_Theme_Integration_Settings_Collections_Factory::getInstance(), $update_manager );

			$class = $this->get_class_name_string();
			$parent_class = $this->get_parent_class_name_string();

			if ( class_exists( $class ) ) {
				$instance = new $class( $this->theme_name, $this->theme_slug, $populate_manager, $helper );
			} elseif ( class_exists( $parent_class ) ) {
				$instance = new $parent_class( $this->theme_parent_name, $this->theme_parent_slug, $populate_manager, $helper );
			} else {
				$instance = new Toolset_Compatibility_Theme_Generic( $this->theme_name, $this->theme_slug, $populate_manager, $helper );
			}
		}

		return $instance;
	}

	private function get_class_name_string(){
		return 'Toolset_Compatibility_Theme_' . $this->theme_slug;
	}

	private function get_parent_class_name_string(){
		return ( ! is_null( $this->theme_parent_slug ) ) ? 'Toolset_Compatibility_Theme_' . $this->theme_parent_slug : '';
	}
}
