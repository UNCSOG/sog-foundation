<div class="dd-layouts-wrap">
    <div class="theme-settings-wrap theme-settings-wrap-collapsed">
        <span role="button" tabindex="0" class="theme-settings-toggle js-theme-settings-toggle">
            <i class="fa fa-caret-down" aria-hidden="true" data-closed="true"></i>
        </span>
        <h2 class="theme-settings-title"><?php _e( 'Theme Options', 'wpv-views' ); echo $this->render_help_tip(); ?></h2>

        <form id="toolset_theme_settings_form" class="hidden">
	        <?php $this->render_non_assigned_layout_message( $this->get_user_visibility_preference_for_gui(), $options_visible ); ?>
            <span class="js-toolset-theme-settings-form" style="<?php if( $options_visible ){ echo "display: none;";}?>">
                <?php foreach ( $collections as $group_name => $group_items ): ?>
                    <?php $targets_css = $this->prepare_target_css_classes( $this->get_targets_from_group( $group_items ) ); ?>
                    <fieldset class="theme-settings-section <?php echo $targets_css;?>" style="display:none;">
                        <h4 class="theme-settings-section-title"><?php _e( $group_name, $this->theme_domain ); ?></h4>
                        <div class="theme-settings-section-content">
                            <?php foreach ( $group_items as $single_group_item ): ?>
                                <?php echo $this->render_single_collection_item( $single_group_item ); ?>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </span>
            <input type="hidden" id="toolset-theme-display-type" name="toolset_display_type_nonce"
                   value="<?php echo wp_create_nonce( 'toolset_theme_display_type' ); ?>" />
        </form>

    </div>
</div>