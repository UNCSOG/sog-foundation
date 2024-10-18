<div>
    <div id="toolset-templates-settings" class="wpv-setting-container js-wpv-setting-container">

        <div class="wpv-setting">
            <p>
                <?php _e( "You can set from here a default parent layout to be assigned to all layouts of your website.", 'ddl-layouts' ); ?>
            </p>

            <div>
                <div class="settings-item-wrap">
                    <label for="ddl-default-parent-layout"><?php _e( "Default parent layout", 'ddl-layouts' ); ?>
                        <select name="<?php echo WPDDL_Options::PARENTS_OPTIONS;?>" id="<?php echo WPDDL_Options::PARENTS_OPTIONS;?>" class="js-<?php echo WPDDL_Options::PARENTS_OPTIONS;?>">
                            <?php echo $this->parents_options();?>
                        </select>
                    </div>
                </div>
            <?php
            wp_nonce_field( 'ddl_template_settings_nonce', 'ddl_template_settings_nonce' );
            ?>

           <!-- <p class="update-button-wrap">
                <span class="js-wpv-messages"></span>
                <button class="js-parent-layout_settings-save button-secondary" disabled>
                    <?php _e( 'Save', 'ddl-layouts' ); ?>
                </button>
            </p> -->

            <div class="parent-settings-messages-wrap"></div>

        </div>

    </div>
</div>
