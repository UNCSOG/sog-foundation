<?php
add_action( 'plugins_loaded', 'ddl_embedded_load_or_deactivate', 1 );

if( !function_exists('ddl_embedded_load_or_deactivate') ){

    function ddl_embedded_load_or_deactivate() {
        if ( defined('WPDDL_DEVELOPMENT') || defined('WPDDL_PRODUCTION') ) {
            add_action( 'admin_init', 'ddl_embedded_deactivate' );
            add_action( 'admin_notices', 'ddl_embedded_deactivate_notice' );
        } else {
            if (!defined('WPDDL_IN_THEME_MODE')) { // This check is only needed when the plugin is being activated while the bootstrap theme is in use.
                require_once WPDDL_EMBEDDED_ROOT . 'ddl-loader.php';
            }
        }
    }

    function ddl_embedded_deactivate() {
        deactivate_plugins( WPDDL_EMBEDDED_PATH );
    }

    function ddl_embedded_deactivate_notice() {
        ?>
        <div class="error">
            <p>
                <?php _e( 'Layouts Embedded was <strong>deactivated</strong>! You are already running the complete Layouts plugin, so this one is not needed anymore.', 'ddl-layouts' ); ?>
            </p>
        </div>
    <?php
    }

}