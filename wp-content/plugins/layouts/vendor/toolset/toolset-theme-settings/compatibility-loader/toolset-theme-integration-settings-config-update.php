<?php

/**
 * Class Toolset_Theme_Integration_Settings_Config_Update
 */
class Toolset_Theme_Integration_Settings_Config_Update{
	
	private $validator;
		
	private $active_theme = null;
	private $theme_name = null;
	private $theme_slug = null;
	private $theme_parent_name = null;
	private $theme_parent_slug = null;
	
	private $toolset_config_files = array();
	private $toolset_config_data = array();
	
	const CONFIG_FILE = 'toolset-config.json';
	
	const BUNDLES_REL_PATH = '/compatibility-modules/bundles/';

	/**
	 * Toolset_Theme_Integration_Settings_Config_Update constructor.
	 *
	 * @param Toolset_Theme_Integration_Settings_Config_Validator $validator
	 */
	public function __construct( Toolset_Theme_Integration_Settings_Config_Validator $validator ) {
		$this->validator = $validator;
	}
	
	public function setup() {
		add_action( 'update_toolset_theme_settings_config_data', array( $this, 'run' ) );
		add_action( 'after_switch_theme', array( $this, 'run' ) );
		add_action( 'activated_plugin', array( $this, 'run' ) );
		add_action( 'deactivated_plugin', array( $this, 'run' ) );
		add_action( 'upgrader_process_complete', array( $this, 'run' ) );
	}
	
	public function run() {
		$this->toolset_config_files = array();
		$this->toolset_config_data = array();
		
		$this->set_up_active_theme();
		
		$this->load_plugins_config();
		$this->load_theme_config();
		$this->cache_config();
		
		return $this->toolset_config_data;
	}
	
	private function get_plugin_config_file( $plugin_path ) {
		$plugin_files = array();
		$plugin_slug = dirname( $plugin_path );
		$config_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . TOOLSET_THEME_SETTINGS_CONFIG_FILE;
		if ( trim( $plugin_slug, '\/.' ) && file_exists( $config_file ) ) {
			$plugin_files[] = $config_file;
		}
		return $plugin_files;
	}
	
	private function load_plugins_config() {
		$plugins_files = array();
		
		if ( is_multisite() ) {
			$multisite_plugins = get_site_option( 'active_sitewide_plugins' );
			if ( ! empty( $multisite_plugins ) ) {
				foreach ( $multisite_plugins as $mplugin_path => $mplugin_timestamp ) {
					$mplugin_files = $this->get_plugin_config_file( $mplugin_path );
					if ( ! empty( $mplugin_files ) ) {
						$plugins_files = array_merge( $plugins_files, $mplugin_files );
					}
				}
			}
		}

		// Get single site or current blog active plugins
		$site_plugins = get_option( 'active_plugins' );
		if ( ! empty( $site_plugins ) ) {
			foreach ( $site_plugins as $splugin_path ) {
				$splugin_files = $this->get_plugin_config_file( $splugin_path );
				if ( ! empty( $splugin_files ) ) {
					$plugins_files = array_merge( $plugins_files, $splugin_files );
				}
			}
		}

		// Get the must-use plugins
		$mu_plugins = wp_get_mu_plugins();
		if ( !empty( $mu_plugins ) ) {
			foreach ( $mu_plugins as $mup ) {
				$plugin_dir_name  = dirname( $mup );
				$plugin_base_name = basename( $mup, ".php" );
				$plugin_sub_dir   = $plugin_dir_name . '/' . $plugin_base_name;
				$config_file = $plugin_sub_dir . '/' . TOOLSET_THEME_SETTINGS_CONFIG_FILE;
				if ( file_exists( $config_file ) ) {
					$plugins_files[] = $config_file;
				}
			}
		}
		
		$this->toolset_config_files = array_merge( $this->toolset_config_files, array( 'plugins' => $plugins_files ) );
	}
	
	private function get_theme_config_file_from_available_sources( $config_file_path, $theme_slug ) {
		$theme_files = array();
		if ( file_exists( $config_file_path ) ) {
			$theme_files[] = $config_file_path;
		
		} elseif( 
			! is_null( $theme_slug ) 
			&& file_exists( TOOLSET_THEME_SETTINGS_BUNDLED_PATH . '/' . $theme_slug . '-' . TOOLSET_THEME_SETTINGS_CONFIG_FILE ) 
		) {
			$theme_files[] = TOOLSET_THEME_SETTINGS_BUNDLED_PATH . '/' . $theme_slug . '-' . TOOLSET_THEME_SETTINGS_CONFIG_FILE;
		}
		return $theme_files;
	}
	
	private function load_theme_config() {
		$themes_files = array();

		if ( get_template_directory() != get_stylesheet_directory() ) {
			$config_file = get_template_directory() . '/' . TOOLSET_THEME_SETTINGS_CONFIG_FILE;
			$parent_files = $this->get_theme_config_file_from_available_sources( $config_file, $this->theme_parent_slug );
			$themes_files = array_merge( $themes_files, $parent_files );
		}

		$config_file = get_stylesheet_directory() . '/' . TOOLSET_THEME_SETTINGS_CONFIG_FILE;
		$child_files = $this->get_theme_config_file_from_available_sources( $config_file, $this->theme_slug );
		$themes_files = array_merge( $themes_files, $child_files );

		$this->toolset_config_files = array_merge( $this->toolset_config_files, array( 'themes' => $themes_files ) );
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
	
	private function load_config_files( $files = array() ) {
		$config_final_data = array();
		foreach ( $files as $config_file_candidate_path ) {
			$config_string = file_get_contents( $config_file_candidate_path );
			$content = $this->validator->validate_config_file_structure( $config_string );
			if ( $content ) {
				$config_final_data = array_merge( $config_final_data, $content->data );
			}
		}
		return $config_final_data;
	}
	
	private function cache_config() {
		$config_files = $this->toolset_config_files;
		
		$config_data_for_themes = $this->load_config_files( $config_files['themes'] );
		$config_data_for_plugins = $this->load_config_files( $config_files['plugins'] );
		
		$this->toolset_config_data = array_merge( $config_data_for_themes, $config_data_for_plugins );
		
		update_option( TOOLSET_THEME_SETTINGS_CACHE_OPTION, $this->toolset_config_data );
		update_option( TOOLSET_THEME_SETTINGS_SOURCES_OPTION, $this->toolset_config_files );
	}
	
}