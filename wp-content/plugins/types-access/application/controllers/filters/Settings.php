<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

use Access_Cacher;
use OTGS\Toolset\Access\Controllers\AccessOutputTemplateRepository;
use OTGS\Toolset\Access\Models\Settings;

/**
 * Add a section to Toolset>Settings to manage Access settings
 *
 * Class Settings_Page
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 */
class SettingsPage {

	/**
	 * Class init
	 */
	public function init() {
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';//phpcs:ignore
		if ( ! empty( $page ) && 'toolset-settings' === $page ) {
			add_action( 'init', array( $this, 'database_erase_init' ), 999 );
			add_action( 'toolset_menu_admin_enqueue_scripts', array( $this, 'toolset_enqueue_scripts' ) );
		}
	}


	/**
	 * Init Access settings erase
	 */
	public function database_erase_init() {

		add_filter( 'toolset_filter_toolset_register_settings_section', array(
			$this,
			'register_settings_access_database_erase_section',
		), 201 );

		add_filter( 'toolset_filter_toolset_register_settings_access-database-erase_section', array(
			$this,
			'database_erase_section_content',
		) );

	}


	/**
	 * Register Toolset Settings tab
	 *
	 * @param array $sections
	 *
	 * @return mixed
	 */
	public function register_settings_access_database_erase_section( $sections ) {
		$sections['access-database-erase'] = array(
			'slug' => 'access-database-erase',
			'title' => __( 'Access', 'wpcf-access' ),
		);

		return $sections;
	}


	/**
	 * Register Toolset Access Settings sections
	 *
	 * @param array $sections
	 *
	 * @return mixed
	 */
	public function database_erase_section_content( $sections ) {

		global $wpcf_access;
		$settings = $wpcf_access->settings;
		$access_roles = $this->get_access_roles_info();
		if ( ! empty( $settings->types )
			|| ! empty( $settings->tax )
			|| ! empty( $settings->third_party )
			|| count( $access_roles['access_roles'] ) > 0 ) {
			$sections['access-database-erase-tool'] = array(
				'slug' => 'access-database-erase-tool',
				'title' => __( 'Reset Access settings', 'wpcf-access' ),
				'content' => $this->generate_erase_settings_section_content(),
			);
		}
		$sections['access-settings'] = array(
			'slug' => 'access-settings',
			'title' => __( 'User settings', 'wpcf-access' ),
			'content' => $this->generate_access_settings_content(),
		);

		return $sections;
	}


	/**
	 * Generate user settings section output
	 *
	 * @return mixed
	 */
	private function generate_access_settings_content() {
		$template_repository = AccessOutputTemplateRepository::get_instance();
		$output = $template_repository->render( $template_repository::USERS_FILTER_OPTION_TEMPLATE );
		return $output;
	}


	/**
	 * @return string
	 */
	public function generate_erase_settings_section_content() {
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$roles = $access_settings->wpcf_get_editable_roles();

		$access_roles_info = $this->get_access_roles_info();
		$template_repository = AccessOutputTemplateRepository::get_instance();
		$output = $template_repository->render( $template_repository::ERASE_DATABASE_OPTION_TEMPLATE,
			array(
				'access_roles' => $access_roles_info['access_roles'],
				'access_roles_names' => $access_roles_info['access_roles_names'],
				'roles' => $roles,
			)
		);

		return $output;
	}


	/**
	 * Generate an array of roles create by Access
	 *
	 * @param null $access_settings
	 *
	 * @return array
	 */
	public function get_access_roles_info( $access_settings = null ) {
		$access_roles_info = Access_Cacher::get( 'access_roles_info' );
		if ( false !== $access_roles_info ) {
			return $access_roles_info;
		}

		$access_settings = $access_settings ?: Settings::get_instance();

		$roles = $access_settings->wpcf_get_editable_roles();
		$access_roles_array = array();
		$access_roles_names = array();

		foreach ( $roles as $role => $role_data ) {
			if ( isset( $role_data['capabilities']['wpcf_access_role'] ) ) {
				$access_roles_array[] = $role;
				$access_roles_names[] = $role_data['name'];
			}
		}
		$output = array(
			'access_roles' => $access_roles_array,
			'access_roles_names' => $access_roles_names,
		);
		Access_Cacher::set( 'access_roles_info', $output );

		return $output;
	}


	/**
	 * Enqueue Script on Toolset Settings page
	 */
	public function toolset_enqueue_scripts() {
		\TAccess_Loader::loadAsset( 'STYLE/wpcf-access-dev', 'wpcf-access' );
		\TAccess_Loader::loadAsset( 'SCRIPT/wpcf-access-settings', 'wpcf-access' );
	}
}
