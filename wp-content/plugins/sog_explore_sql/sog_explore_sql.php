<?php
/*
Plugin Name: SOG Explore WP Database
Description: To view/edit all tables in the WordPress database.  See SOG Explore DB menu item for instructions.
Version: 1.3
Author: Darren Goroski

*/

namespace sog_explore;

date_default_timezone_set("America/New_York");

ini_set('display_errors', 0);
// error_reporting(E_ERROR);
// error_reporting(E_ALL);


if( !session_id() ) {
	session_start();
}

require_once("sog_explore_functions.php");


if (is_pantheon()){

}else{
    $_ENV['PANTHEON_SITE_NAME']="sog-apps-wp";
}


// plugin activation
function activate() {
    //create sql tables needed for plugin
	// create_sql_tables();

    //create default page for db explorer
    // create_default_page();

}
register_activation_hook( __FILE__, 'sog_explore\activate' );

// plugin deactivation
function deactivate() {

}
register_deactivation_hook( __FILE__, 'sog_explore\deactivate' );

// plugin uninstallation
function uninstall() {
    //delete tables from db
    global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS sog_ex_report_tables" );
	$wpdb->query( "DROP TABLE IF EXISTS sog_ex_explore_filter" );
	$wpdb->query( "DROP TABLE IF EXISTS sog_ex_explore_user_choices" );
	$wpdb->query( "DROP TABLE IF EXISTS sog_ex_explore_log" );
}
register_uninstall_hook( __FILE__, 'sog_explore\uninstall' );


function sql_styles() {

}
add_action( 'wp_enqueue_scripts', 'sog_explore\sql_styles' );

function sql_scripts() {
    wp_enqueue_style( 'sog_explore_sql_css',  plugin_dir_url( __FILE__ ) . 'inc/css/style.css',null, time() );

    wp_enqueue_script( 'sog_explore_sql_js',  plugin_dir_url( __FILE__ ) . 'inc/js/js.js',array('jquery'),time() );
	wp_localize_script('sog_explore_sql_js', 'sog_explore_vars', array('plugin_path' => plugin_dir_url(__FILE__),"siteurl"=>get_option('siteurl')));

    if (isset($_ENV['PANTHEON_SITE_NAME']) and $_ENV['PANTHEON_SITE_NAME']=="sog-books") {
    }else{
	    //bootstrap 5.1.3 is already loaded by theme, loading here breaks popovers in books
        wp_enqueue_style('sog_explore_bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
        wp_enqueue_script( 'sog_explore_bootstrap_js','https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js', array( 'jquery' ),'',true );
    }

    wp_enqueue_style('sog_explore_font_awesome', 'https://pro.fontawesome.com/releases/v5.15.4/css/all.css');

    wp_enqueue_style('sog_explore_datatables', 'https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css');
    wp_enqueue_script( 'sog_explore_datatables_js','https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_bootstrap','https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_buttons','https://cdn.datatables.net/buttons/2.0.1/js/dataTables.buttons.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_bootstrap5_buttons','https://cdn.datatables.net/buttons/2.0.1/js/buttons.bootstrap5.min.js', array( 'jquery' ),'',true );
    wp_enqueue_script( 'sog_explore_datatables_js_bootstrap_buttons_html5','https://cdn.datatables.net/buttons/2.0.1/js/buttons.html5.min.js', array( 'jquery' ),'',true );

}
add_action( 'wp_enqueue_scripts', 'sog_explore\sql_scripts' );
add_action( 'admin_enqueue_scripts', 'sog_explore\sql_scripts' );


function custom_menu_settings() {

  add_menu_page(
      'SOG Explore DB', //page title
      'SOG Explore DB', //menu title
      'read', //capability
      'sog_explore_menu_slug', //menu slug
      'sog_explore\admin_page', //function to run
      'dashicons-database'
     );

}
add_action('admin_menu', 'sog_explore\custom_menu_settings');

function sql($atts){

    //check permission based on wp_option record with name save_explore_roles
    if (allow_access_to_sog_explore()){
        ob_start();
        display_report_builder($atts);
        $html = ob_get_clean();
        return $html;
    }else{
        return "Should you be here?";
    }
}
add_shortcode( 'sog_explore_sql', 'sog_explore\sql' );

function allow_access_to_sog_explore(){
    //check permission based on wp_option record with name save_explore_roles

    //get option data
    $save_explore_roles=get_option( $option="sog_ex_save_explore_roles");
    if (isset($save_explore_roles)){
        //turn unto array
        $allowed_roles=explode(",",$save_explore_roles);

        //get user roles
        $user = wp_get_current_user();
        $user_ID = $user->ID;
        $roles = ! empty( $user->roles ) ? $user->roles : array();

        //check if a user role is contained in the allowed roles
        if (array_intersect( $roles, $allowed_roles )){
            return true;
        }else{
            return false;
        }
    }

}

add_action('init',function(){
	$_SESSION['sog_explore_wp_user'] = wp_get_current_user();
	$_SESSION['sog_explore_user_login']=$_SESSION['sog_explore_wp_user']->user_login;
	// $_SESSION['db_to_use']=sog_explore_get_db_creds(null)['db_to_use'];

});

//create salt to be used later
if (!isset($_SESSION['salt'])) {
	$_SESSION['salt']=md5($_SESSION['user_login'] ?? "".time());
}

//Debugging
if (isset($_GET['_COOKIE']) and $_GET['_COOKIE']) {
    echo "_COOKIE<pre>";print_r($_COOKIE);echo "</pre>";
}

if (isset($_GET['_POST']) and $_GET['_POST']) {
    echo "_POST<pre>";print_r($_POST);echo "</pre>";
}

if (isset($_GET['_SESSION']) and $_GET['_SESSION']) {
    echo "_SESSION<pre>";print_r($_SESSION);echo "</pre>";
}
if (isset($_GET['_ENV']) and $_GET['_ENV']) {
    echo "_ENV<pre>";print_r($_ENV);echo "</pre>";
}
