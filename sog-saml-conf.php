<?php

/**
 * Plugin Name: SOG SAML Conf
 * Version: 0.2
 * Description: Auto provision users from SAML, adds apps team as admins
 * Author: Matias Silva
 * Text Domain: sog-saml-conf
 * Requires Plugins: wp-saml-auth
 *
 * @package sog_saml_conf
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use SogSamlConf\Plugin;
use SogSamlConf\Admins;

add_action('plugins_loaded', function () {
    $plugin = new Plugin(Admins::get());
    $plugin->register_hooks();
});
