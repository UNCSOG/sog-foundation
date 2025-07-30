<?php
/*
Plugin Name: SOG Explore WP Database
Description: To view all tables in the WordPress database.  See SOG Explore DB menu item then add shortcode [sog_explore_sql] to any page.
Version: 1.0
Author: Darren Goroski

*/

if( !session_id() ) {
	session_start();
}

require_once("sog_explore_functions.php");


// plugin activation
function sog_explore_activate() {
	sog_ex_create_sql_tables();
}
register_activation_hook( __FILE__, 'sog_explore_activate' );

// plugin deactivation
function sog_explore_deactivate() {

}
register_deactivation_hook( __FILE__, 'sog_explore_deactivate' );

// plugin uninstallation
function sog_explore_uninstall() {
	
}
register_uninstall_hook( __FILE__, 'sog_explore_uninstall' );


function sog_explore_sql_styles() {

}
add_action( 'wp_enqueue_scripts', 'sog_explore_sql_styles' );

function sog_explore_sql_scripts() {
    wp_enqueue_style( 'sog_explore_sql_css',  plugin_dir_url( __FILE__ ) . 'inc/css/style.css' );                      

    wp_enqueue_script( 'sog_explore_sql_js',  plugin_dir_url( __FILE__ ) . 'inc/js/js.js',array('jquery'),time() );                      
	wp_localize_script('sog_explore_sql_js', 'sog_explore_vars', array('plugin_path' => plugin_dir_url(__FILE__),"siteurl"=>get_option('siteurl')));

    wp_enqueue_style('sog_explore_bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css');
    wp_enqueue_script( 'sog_explore_bootstrap_js','https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.min.js', array( 'jquery' ),'',true );

    wp_enqueue_style('sog_explore_font_awesome', 'https://pro.fontawesome.com/releases/v5.15.4/css/all.css');

    wp_enqueue_style('sog_explore_datatables', 'https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css');
    wp_enqueue_script( 'sog_explore_datatables_js','https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_bootstrap','https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_buttons','https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_bootstrap5_buttons','https://cdn.datatables.net/buttons/2.0.1/js/buttons.bootstrap5.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_bootstrap_buttons_html5','https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js', array( 'jquery' ),'',true );

}
add_action( 'wp_enqueue_scripts', 'sog_explore_sql_scripts' );
add_action( 'admin_enqueue_scripts', 'sog_explore_sql_scripts' );


function sog_explore_custom_menu_settings() { 

  add_menu_page( 
      'SOG Explore DB', //page title
      'SOG Explore DB', //menu title
      'read', //capability
      'sog_explore_menu_slug', //menu slug
      'sog_explore_admin_page', //function to run
      'dashicons-database' 
     );
	 
}
add_action('admin_menu', 'sog_explore_custom_menu_settings');

function sog_explore_sql($atts){
	ob_start();
		display_sog_explore_sql($atts);
	$html = ob_get_clean();
return $html;
}
add_shortcode( 'sog_explore_sql', 'sog_explore_sql' );

add_action('init',function(){
	$_SESSION['sog_explore_wp_user'] = wp_get_current_user();
	$_SESSION['sog_explore_user_login']=$_SESSION['sog_explore_wp_user']->user_login;
	$_SESSION['db_to_use']=sog_explore_get_db_creds(null)['db_to_use'];

});



