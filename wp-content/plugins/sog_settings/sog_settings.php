<?php
/**
 * Plugin Name: SOG Settings
 * Description: A simple interface to manage settings for various applications
 * Version: 0.08092024
 * Author: Darren Goroski
 */

 namespace sog_settings;

// Exit if accessed directly.
defined('ABSPATH') || exit;

date_default_timezone_set('America/New_York');

ini_set('display_errors', 0);
// error_reporting(E_ERROR);
// error_reporting(E_ALL);

if(!session_id()) {
    session_start();
}

require_once('sog_settings_functions.php');

add_action('init', 'sog_settings\create_db_creds');

if (is_pantheon()) {

} else {
    $_ENV['PANTHEON_SITE_NAME'] = 'sog-apps-wp';
}

// plugin activation
function activate()
{
    //create sql tables needed for plugin
    // create_sql_tables();
}
register_activation_hook(__FILE__, 'sog_settings\activate');

// plugin deactivation
function deactivate()
{

}
register_deactivation_hook(__FILE__, 'sog_settings\deactivate');

// plugin uninstallation
function uninstall()
{
    //delete tables from db
    global $wpdb;
    $wpdb->query('DROP TABLE IF EXISTS sog_settings');
}
register_uninstall_hook(__FILE__, 'sog_settings\uninstall');

function sql_scripts()
{
    wp_enqueue_style('sog_settings_sql_css',  plugin_dir_url(__FILE__) . 'inc/css/style.css', null, time());

    wp_enqueue_script('sog_settings_sql_js',  plugin_dir_url(__FILE__) . 'inc/js/js.js', ['jquery'], time());
    wp_localize_script('sog_settings_sql_js', 'sog_settings_vars', ['plugin_path' => plugin_dir_url(__FILE__), 'siteurl' => get_option('siteurl')]);
    wp_enqueue_style('sog_settings_bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
    wp_enqueue_script('sog_settings_bootstrap_js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js', [ 'jquery' ], '', true);

    wp_enqueue_style('sog_settings_font_awesome', 'https://pro.fontawesome.com/releases/v5.15.4/css/all.css');
}
add_action('wp_enqueue_scripts', 'sog_settings\sql_scripts');
add_action('admin_enqueue_scripts', 'sog_settings\sql_scripts');

function custom_menu_settings()
{

    add_menu_page(
        'SOG Settings', //page title
        'SOG Settings', //menu title
        'read', //capability
        'sog_settings_menu_slug', //menu slug
        'sog_settings\settings_page', //function to run
        'dashicons-palmtree'
    );

}
add_action('admin_menu', 'sog_settings\custom_menu_settings');

add_action('init', function () {
    $_SESSION['sog_settings_wp_user'] = wp_get_current_user();
    $_SESSION['sog_settings_user_login'] = $_SESSION['sog_settings_wp_user']->user_login;

});

//Debugging
if (isset($_GET['_COOKIE']) and $_GET['_COOKIE']) {
    echo '_COOKIE<pre>';
    print_r($_COOKIE);
    echo '</pre>';
}

if (isset($_GET['_POST']) and $_GET['_POST']) {
    echo '_POST<pre>';
    print_r($_POST);
    echo '</pre>';
}

if (isset($_GET['_SESSION']) and $_GET['_SESSION']) {
    echo '_SESSION<pre>';
    print_r($_SESSION);
    echo '</pre>';
}
if (isset($_GET['_ENV']) and $_GET['_ENV']) {
    echo '_ENV<pre>';
    print_r($_ENV);
    echo '</pre>';
}
