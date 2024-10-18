<?php // phpcs:disable
/*
Plugin Name: Toolset Layouts
Plugin URI: https://toolset.com/
Description: Design entire WordPress sites using a drag-and-drop interface.
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version: 2.6.16
*/

/**
 * WPDDL_DEVELOPMENT -> default development, loads production files and leave embedded files alone
 *
 * WPDDL_EMBEDDED -> loads embedded files.
 *
 * WPDDL_PRODUCTION -> loads production files (not to be set manually)
 */



define('WPDDL_DEVELOPMENT', 'Layouts');

if (
    file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-embedded.class.php') &&
    defined('WPDDL_EMBEDDED') &&
    (defined('WPDDL_DEVELOPMENT') === false && defined('WPDDL_PRODUCTION') === false)
) {

    define('WPDDL_EMBEDDED_PATH', plugin_basename(__FILE__));
    define('WPDDL_EMBEDDED_ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ddl-embedded-loader.php';

} else if (
    file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'classes/wpddl.admin-plugin.class.php') &&
    (defined('WPDDL_DEVELOPMENT') || defined('WPDDL_PRODUCTION')) &&
    !function_exists('ddl_layouts_plugin_loader')
) {
    //add_action('plugins_loaded', 'ddl_layouts_plugin_loader', -3);

    function ddl_layouts_plugin_loader()
    {
        if (!defined('WPDDL_IN_THEME_MODE')) { // This check is only needed when the plugin is being activated while the bootstrap theme is in use.
            require_once dirname( __FILE__ ) . '/ddl-loader.php';
        }
    }
    ddl_layouts_plugin_loader();
}

if( !function_exists('wpddl_layout_deactivate_plugin') ){
    function wpddl_layout_deactivate_plugin(){
        global $current_user ;
        $user_id = $current_user->ID;
        delete_user_meta( $user_id, WPDDL_Messages::$release_option_name );
    }
    register_deactivation_hook( __FILE__, 'wpddl_layout_deactivate_plugin' );
}

if( !function_exists('ddl_cred_user_cell_disable_if_not_version') ){
  //  add_action('init', 'ddl_cred_user_cell_disable_if_not_version', 7);
    function ddl_cred_user_cell_disable_if_not_version(){
        $version = defined('CRED_FE_VERSION') ? (float) CRED_FE_VERSION : 0;
        if( $version < 1.4){
            remove_ddl_support('cred-user-cell');
        }
    }
}

if( !function_exists('layouts_plugin_plugin_row_meta') ){
    /* Plugin Meta */
    add_filter( 'plugin_row_meta', 'layouts_plugin_plugin_row_meta', 10, 4 );

    function layouts_plugin_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
        $this_plugin = basename( WPDDL_ABSPATH ) . '/dd-layouts.php';
        if ( $plugin_file == $this_plugin ) {
            $plugin_meta[] = '<a href="' . WPDDL_NOTES_URL . '" target="_blank">'
                . sprintf( __( 'Layouts %s release notes', 'wpcf' ), WPDDL_VERSION ) . '</a>';
        }
        return $plugin_meta;
    }
}

if( !function_exists('layouts_plugin_add_wpml_switcher_init') ){

    add_filter( 'toolset_filter_disable_wpml_lang_switcher_in_admin', 'layouts_plugin_add_wpml_switcher_init', 10, 1 );

    function layouts_plugin_add_wpml_switcher_init( $pages ) {
        $pages[] = 'dd_layouts_edit';
        return $pages;
    }
}

if( !function_exists('ddl_apply_mask_widget_area_post_type')){
	function ddl_apply_mask_widget_area_post_type(){
		add_filter("toolset_filter_exclude_own_post_types", 'ddl_apply_mask_widget_area_post_type_callback', 10, 1);
	}
}
if(!function_exists('ddl_apply_mask_widget_area_post_type_callback')){
	function ddl_apply_mask_widget_area_post_type_callback($post_types){
		$post_types[] = "widget-area";
		return $post_types;
	}
}
add_action("plugins_loaded", "ddl_apply_mask_widget_area_post_type", 10);

add_filter( 'toolset_is_layouts_available', '__ddl_return_true' );
if( !function_exists('__ddl_return_true') ){
    function __ddl_return_true(){
        return true;
    }
}
