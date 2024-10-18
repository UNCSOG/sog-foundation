<?php

return;

/**
 * Class WPDDL_Theme_Integration_Settings
 * @deprecated
 */
class WPDDL_Theme_Integration_Settings{
	protected $theme_name;
	protected $theme_slug;
	protected $options = null;
	protected $user_chosen_option = false;

	const SETTINGS_PREFIX = 'toolset_integration_type_for_';
	const OPTION_FULL = 'full';
	const OPTION_CONTENT = 'content';

	public function __construct( $theme_name, $theme_slug ) {
		$this->theme_slug = $theme_slug;
		$this->theme_name = $theme_name;

		$this->create_option( $this->user_chosen_option );
		$this->user_chosen_option = $this->get_options();

		if( ! $this->user_chosen_option ){
			add_filter( 'toolset-admin-notices-manager-show-notices', array( $this, 'toolset_notices_remove_run_installer_for_integration_plugin'), 11, 1 );
		}
	}

	function toolset_notices_remove_run_installer_for_integration_plugin( $notices ) {

		$is_integration_plugin_active = new Toolset_Condition_Theme_Layouts_Support_Plugin_Active();

		if( ! $is_integration_plugin_active->is_met() ) {
			// no theme itegration plugin active
			return $notices;
		}

		$theme_slug = sanitize_title( $is_integration_plugin_active->get_supported_theme_name() );

		unset( $notices['integration-run-installer-for-' . $theme_slug] );

		return $notices;
	}

	protected function __clone() {}

	protected function get_option_name(){
		return self::SETTINGS_PREFIX . $this->theme_slug;
	}

	protected function create_option( $default = false ){
		$this->options = new WPDDL_Options_Manager( $this->get_option_name(), $default );
	}

	protected function get_options(){
		return $this->options->get_options( $this->get_option_name() );
	}

	protected function update_options( $value ){
		return $this->options->update_options( $this->get_option_name(), $value );
	}

	protected function delete_options( ){
		return $this->options->delete_option( $this->get_option_name() );
	}
}