<?php
/**
 * Plugin loading.
 *
 * @package Toolset Forms
 */

/**
 * Set basic constant.
 */
define( 'CRED_ABSPATH', dirname( __FILE__ ) );

/**
 * Set constants.
 */
require_once CRED_ABSPATH . '/loader/constants.php';

/**
 * Require Toolset Common and other manual dependencies.
 */
require_once CRED_ABSPATH . '/loader/manual-dependencies.php';

/**
 * Set utils.
 */
require_once CRED_ABSPATH . '/loader/utils.php';

/*
 * Bootstrap Toolset Forms.
 */
require_once CRED_ABSPATH . '/application/bootstrap.php';
