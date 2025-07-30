<li data-layout-type="normal">
    <figure class="row-type">
        <img class="item-preview" data-name="row-normal" src="<?php echo WPDDL_GUI_RELPATH; ?>dialogs/img/tn-boxed.png" alt="<?php _e('Normal', 'ddl-layouts'); ?>">
        <span><?php _e('Row same as container width', 'ddl-layouts'); ?></span>
    </figure>
    <label class="radio" data-target="row-normal" for="row_type_normal" style="display:none">
        <input type="radio" name="row_type" id="row_type_normal" value="normal">
        <?php _e('Normal', 'ddl-layouts'); ?>
    </label>
</li>
<li data-layout-type="normal">
    <figure class="row-type">
        <img class="item-preview" data-name="row-full-fixed" src="<?php echo WPDDL_GUI_RELPATH; ?>dialogs/img/tn-full-fixed.png" alt="<?php _e('Full width background', 'ddl-layouts'); ?>">
        <span><?php _e('Row background extends to full width', 'ddl-layouts'); ?></span>
    </figure>
    <label class="radio" data-target="row-full-fixed" for="row_type_full_width_background" style="display:none">
        <input type="radio" name="row_type" id="row_type_full_width_background" value="full-width-background">
        <?php _e('Full width background', 'ddl-layouts'); ?>
    </label>
</li>
<li data-layout-type="normal">
    <figure class="row-type">
        <img class="item-preview" data-name="row-full-width" src="<?php echo WPDDL_GUI_RELPATH; ?>dialogs/img/tn-full-fluid.png" alt="<?php _e('Full width', 'ddl-layouts'); ?>">
        <span><?php _e('Cells extend to the full width', 'ddl-layouts'); ?></span>
    </figure>
    <label class="radio" data-target="row-full-width" for="row_type_full_width" style="display:none">
        <input type="radio" name="row_type" id="row_type_full_width" value="full-width">
        <?php _e('Full width', 'ddl-layouts'); ?>
    </label>
</li>
<li data-layout-type="private">
    <figure class="row-type">
        <img class="item-preview" data-name="row-full-width" src="<?php echo WPDDL_GUI_RELPATH; ?>dialogs/img/tn-private-full-fluid.png" alt="<?php _e('Full width', 'ddl-layouts'); ?>">
        <span><?php _e('Row as wide as post content (recommended)', 'ddl-layouts'); ?></span>
    </figure>
    <label class="radio" data-target="row-full-width" for="row_type_full_width" style="display:none">
        <input type="radio" name="row_type" id="row_type_full_width" value="full-width">
        <?php _e('Full width', 'ddl-layouts'); ?>
    </label>
</li>
<li data-layout-type="private">
    <figure class="row-type">
        <img class="item-preview" data-name="row-normal" src="<?php echo WPDDL_GUI_RELPATH; ?>dialogs/img/tn-private-boxed.png" alt="<?php _e('Normal', 'ddl-layouts'); ?>">
        <span><?php _e('Row as wide as Bootstrap <code>.container</code>', 'ddl-layouts'); ?></span>
    </figure>
    <label class="radio" data-target="row-normal" for="row_type_normal" style="display:none">
        <input type="radio" name="row_type" id="row_type_normal" value="normal">
        <?php _e('Normal', 'ddl-layouts'); ?>
    </label>
</li>
<li data-layout-type="private">
    <figure class="row-type">
        <img class="item-preview" data-name="row-full-fixed" src="<?php echo WPDDL_GUI_RELPATH; ?>dialogs/img/tn-private-full-fixed.png" alt="<?php _e('Full width background', 'ddl-layouts'); ?>">
        <span><?php _e('Row as wide as Bootstrap <code>.container</code>, its background extends to full width', 'ddl-layouts'); ?></span>
    </figure>
    <label class="radio" data-target="row-full-fixed" for="row_type_full_width_background" style="display:none">
        <input type="radio" name="row_type" id="row_type_full_width_background" value="full-width-background">
        <?php _e('Full width background', 'ddl-layouts'); ?>
    </label>
</li>