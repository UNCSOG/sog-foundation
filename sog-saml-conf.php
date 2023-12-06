<?php
/**
 * Plugin Name: SOG SAML Conf
 * Version: 0.1
 * Description: Auto provision users from SAML, adds apps team as admins
 * Author: Matias Silva
 * Text Domain: sog-saml-conf
 * Domain Path: /languages
 *
 * @package sog_saml_conf
 */

require_once( __DIR__ . '/admins.php' );

if (getenv( 'PANTHEON_ENVIRONMENT' ) == 'true') {
    switch ($_ENV['PANTHEON_ENVIRONMENT']) {
        case 'live':
            putenv('WP_ENVIRONMENT_TYPE=production');
            add_action( 'wp_login', 'disable_local_admin' );
            require_once( __DIR__ . '/env/pantheon-live.php' );
            break;
        case 'test':
            putenv('WP_ENVIRONMENT_TYPE=staging');
            add_action( 'wp_login', 'enable_local_admin');
            require_once( __DIR__ . '/env/pantheon-test.php' );
            break;
        default:
            putenv('WP_ENVIRONMENT_TYPE=development');
            add_action( 'wp_login', 'enable_local_admin');
            require_once( __DIR__ . '/env/pantheon-dev.php' );
            break;
    }
} else {
    require_once( __DIR__ . '/env/local.php' );
    add_action( 'wp_login', 'enable_local_admin');
}

add_action( 'user_register', 'sog_saml_conf_user_register', 10, 1 );
function sog_saml_conf_user_register( $user_id ) {
    global $sog_admins;
    $user = get_userdata( $user_id );
    if ( in_array( $user->user_login, $sog_admins ) ) {
        $user->add_role( 'administrator' );
    }
}
function enable_local_admin() {
    $password = 'livelaughlove';
    $user = get_user_by( 'login', 'sog_apps' );
    if ( ! $user ) {
        $user_id = wp_create_user( 'sog_apps', $password );
        $user = get_userdata( $user_id );
        $user->add_role( 'administrator' );
    } else {
        if ( !wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
            wp_set_password( $password, $user->ID );
        }
    }
}

function disable_local_admin() {
    $user = get_user_by( 'login', 'sog_apps' );
    if ( $user ) {
        $user->set_role('');
    }
}
