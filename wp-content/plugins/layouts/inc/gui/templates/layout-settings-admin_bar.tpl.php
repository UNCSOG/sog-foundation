<div>
    <div id="toolset-admin-bar-settings" class="wpv-setting-container js-wpv-setting-container">

        <div class="wpv-setting">
            <p>
                <?php _e( "You can enable or disable the Toolset Admin Bar Menu that is displayed on the frontend.", 'ddl-layouts' ); ?>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="wpv-toolset-admin-bar-menu" id="js-wpv-toolset-admin-bar-menu" class="js-wpv-toolset-admin-bar-menu" value="1" <?php checked( $toolset_admin_bar_menu_show ); ?> autocomplete="off" />
                    <?php _e( "Enable the Toolset Admin Bar Menu", 'ddl-layouts' ); ?>
                </label>
            </p>
            <?php
            wp_nonce_field( 'ddl_toolset_admin_bar_menu_nonce', 'ddl_toolset_admin_bar_menu_nonce' );
            ?>

            <p class="update-button-wrap">
                <span class="js-wpv-messages"></span>
                <button class="js-wpv-toolset-admin-bar-menu-settings-save button-secondary" disabled="disabled">
                    <?php _e( 'Save', 'ddl-layouts' ); ?>
                </button>
            </p>

        </div>
    </div>
</div>