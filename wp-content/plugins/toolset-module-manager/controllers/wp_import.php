<?php

/**
 * Prepare data and run wp-importer class
 * Class Module_Manager_Wp_Import
 * @since 1.8
 */
class Module_Manager_Wp_Import {

	private static $wp_import;


	public static function get_instance() {
		if( null === self::$wp_import ) {
			self::$wp_import = new self();
		}
		return self::$wp_import;
	}

	function __construct () {
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define ( 'WP_LOAD_IMPORTERS', true );
		}
	}

	function import_posts( $file ) {
		if ( ! class_exists( 'Modman_Importer' ) ) {
			require_once MODMAN_PLUGIN_PATH . '/library/wordpress-importer/modman-wp-importer.php';
		}
		$site_url = get_bloginfo('url');
		$import = new Modman_Importer( $site_url );
		$result = $import->import( $file );
		return $result;
	}





}