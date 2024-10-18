<div class="js-toolset-non-assigned-message toolset-non-assigned-message <?php echo ! $visible ? 'hidden' : ''; ?>">
    <p><?php _e( 'The layout is not assigned to content, so you cannot control the theme settings. Once you assign this layout to content, you will see the theme options that are relevant for that kind of content.', 'wp-views' ); ?></p>
    <div class="theme-options-box-visibility-controls-wrap">
        <label>
            <input type="radio" name="toolset_layout_to_cred_form" class="js-layout-used-for-cred" value="" <?php checked( null, $saved_choice ); ?> />
            <?php _e( "I will assign this layout to content later", 'wp-views' ); ?>
        </label>
    </div>

    <div class="theme-options-box-visibility-controls-wrap">
        <label>
            <input type="radio" name="toolset_layout_to_cred_form" class="js-layout-used-for-cred" value="posts-cred" <?php checked( 'posts-cred', $saved_choice ); ?> />
            <?php _e( "I'm using this layout to display 'single' pages", 'wp-views' ); ?>
        </label>
    </div>

    <div class="theme-options-box-visibility-controls-wrap">
        <label>
            <input type="radio" name="toolset_layout_to_cred_form" class="js-layout-used-for-cred" value="archive-cred" <?php checked( 'archive-cred', $saved_choice ); ?> />
            <?php _e( "I'm using this layout to display an archive", 'wp-views' ); ?>
        </label>
    </div>
</div>