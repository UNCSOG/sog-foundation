<?php
$theme = wp_get_theme();
$theme_name = empty( $theme->Name )
	? $theme->Name
	: __( 'this theme', 'wpcf' );
?>
<div>
    <div id="toolset-admin-bar-settings" class="wpv-setting-container js-wpv-setting-container">

        <div class="wpv-setting">
            <p class="toolset-list-title" style="margin-top: 0;"><?php _e( 'The installer will:', 'wpcf' ); ?></p>
            <ul class="toolset-list">
                <li>
			        <?php printf( __( 'Automatically install the Toolset plugins that are needed for %s', 'wpcf' ), $theme_name ); ?>
                </li>
                <li>
			        <?php printf( __( 'Set up layouts, template, archives and other site elements for %s', 'wpcf'), $theme_name ); ?>
                </li>
            </ul>

            <?php
            echo Toolset_Admin_Notices_Manager::tpl_link(
	            __( 'Run Installer', 'wpcf' ),
	            admin_url( 'index.php?page=toolset-site-installer' )
            );
            ?>
        </div>
    </div>
</div>
