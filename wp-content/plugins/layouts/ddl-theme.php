<?php
if(defined('WPDDL_VERSION')) return;

define( 'WPDDL_IN_THEME_MODE', true);

define( 'WPDDL_RELPATH', get_template_directory_uri() . '/' . basename( dirname( __FILE__ ) ) );
require_once dirname( __FILE__ ) . '/ddl-loader.php';

function ddl_import_layouts_from_theme_dir($theme_layouts_dir = '') {

	if (!$theme_layouts_dir) {
		$theme_layouts_dir = get_stylesheet_directory() . '/theme-dd-layouts';
	}

	return WPDD_Layouts_Theme::getInstance()->import_layouts_from_theme($theme_layouts_dir, true);
}

function ddl_update_theme_layouts( $layouts_dir, $import_args ){
    WPDD_Layouts_Theme::getInstance()->update_layouts( $layouts_dir, $import_args );
}

require_once dirname( __FILE__ ) . '/theme/wpddl.theme-support.class.php';