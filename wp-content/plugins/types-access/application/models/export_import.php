<?php

namespace OTGS\Toolset\Access\Models;

use OTGS\Toolset\Access\Models\Settings;

/**
 * Class ExportImport
 *
 * @package OTGS\Toolset\Access
 * @since 2.7
 */
class ExportImport {

	private static $instance;

	public $wp_roles;


	/**
	 * @return ExportImport
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		self::get_instance();
	}


	/**
	 * ExportImport constructor.
	 */
	function __construct() {
	}


	/**
	 * @param array $sections
	 *
	 * @return array
	 */
	public function register_export_import_section( $sections ) {
		$sections['access'] = array(
			'slug' => 'access',
			'title' => __( 'Access', 'wpcf-access' ),
			'icon' => '<i class="icon-access-logo ont-icon-16"></i>',
			'items' => array(
				'export' => array(
					'title' => __( 'Export Access Settings', 'wpcf-access' ),
					'callback' => array( $this, 'export_settings_template' ),
				),
				'import' => array(
					'title' => __( 'Import Access Settings', 'wpcf-access' ),
					'callback' => array( $this, 'import_settings_template' ),
				),
			),
		);

		return $sections;
	}


	/**
	 * Include export template
	 */
	public function export_settings_template() {
		include TACCESS_TEMPLATES_PATH . '/export-settings.tpl.php';
	}


	/**
	 * Include Import Template
	 */
	public function import_settings_template() {
		include TACCESS_TEMPLATES_PATH . '/import-settings.tpl.php';
	}


	/**
	 * @param string $current_page
	 */
	public function load_assets_in_shared_pages( $current_page ) {
		switch ( $current_page ) {
			case 'toolset-export-import':
				$this->wpcf_access_admin_import_export_load();
				break;
		}
	}


	/**
	 *
	 */
	public static function wpcf_access_admin_import_export_load() {
		\TAccess_Loader::loadAsset( 'SCRIPT/wpcf-access-dev', 'wpcf-access' );
		\TAccess_Loader::loadAsset( 'SCRIPT/wpcf-access-utils-dev', 'wpcf-access-utils' );
		\TAccess_Loader::loadAsset( 'STYLE/wpcf-access-dialogs-css', 'wpcf-access-dialogs-css' );
		\TAccess_Loader::loadAsset( 'STYLE/notifications', 'notifications' );
		add_thickbox();
	}

}
