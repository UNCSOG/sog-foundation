<?php
/**
 * Plugin manual dependencies, not autoload-able.
 *
 * @package Toolset Forms
 */

// Most of the dependency libraries here follow the same loading mechanism:
// - require once the library loader.
// - register the current relatives path and URL within the loader.
// - the loader will decide which instance to load based on version numbers.

// Load OTGS/UI
require_once CRED_ABSPATH . '/vendor/otgs/ui/loader.php';
otgs_ui_initialize( CRED_ABSPATH . '/vendor/otgs/ui', CRED_ABSURL . '/vendor/otgs/ui' );

// Load common resources
require_once CRED_ABSPATH . '/vendor/toolset/onthego-resources/loader.php';
onthego_initialize( CRED_ABSPATH . '/vendor/toolset/onthego-resources/', CRED_ABSURL . '/vendor/toolset/onthego-resources/' );

// Load Toolset Common Library
require_once CRED_ABSPATH . '/vendor/toolset/toolset-common/loader.php';
toolset_common_initialize( CRED_ABSPATH . '/vendor/toolset/toolset-common/', CRED_ABSURL . '/vendor/toolset/toolset-common/' );

// Load Toolset Common Library
require CRED_ABSPATH . '/vendor/toolset/common-es/loader.php';
