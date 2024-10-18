<script type="text/html" id="js-child-layout-box-tpl">
	<div id="js-child-layout-box-{{ cid }}" class="child-layout-box js-child-layout-box">
		<div class="ddl-dialog-header">
			<h2 class="js-dialog-title"><?php _e('This layout has children', 'ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>
		<div class="ddl-dialog-content">
			<p><?php _e('If you delete the Child layout cell you will lose the association with the existing <br>child layouts', 'ddl-layouts'); ?></p>

            <# if(typeof DDLayout_settings_editor !== 'undefined' && +DDLayout_settings_editor.is_embedded === 1){ #>
                <p><?php printf(
                        __('In embedded mode this action couldn\'t be undone. %s', 'ddl-layouts'),
                        '<a href="#" class="ddl-open-promotional-message js-open-promotional-message">Enable creating layouts</a>'
                        ); ?></p>
            <# } #>

			<p class="child-layout-remove">
				<button class="button js-delete-child-layout-and-remove-association delete-child-layout-button"><?php _e('Delete the Child layout cell and remove the association with child layouts', 'ddl-layouts'); ?></button>
				<br />
				<span class="alert alert-child-layout"><i class="icon-warning-sign fa fa-exclamation-triangle"></i> <?php _e('The association will be updated when this layout is saved.', 'ddl-layouts'); ?></span>
			</p>
			
			<p class="child-layout-delete">
				<button class="button js-delete-child-layout-and-delete-association delete-child-layout-button"><?php _e('Delete the Child layout cell and delete all the child layouts', 'ddl-layouts'); ?></button>
				<br />
				<span class="alert alert-child-layout"><i class="icon-warning-sign fa fa-exclamation-triangle"></i> <?php _e('The child layouts will be deleted when this layout is saved.', 'ddl-layouts'); ?></span>
				<br />
				<span class="alert alert-child-layout"><i class="icon-warning-sign fa fa-exclamation-triangle"></i> <?php _e('Association of child layouts to posts and post types will be removed.', 'ddl-layouts'); ?></span>
			</p>
			
			<p><button class="button js-edit-dialog-close delete-child-layout-button"><?php _e('Cancel', 'ddl-layouts'); ?></button></p>
			<p class="js-element-box-message-container message-container"></p>
		</div>
		<div class="ddl-dialog-footer">
			<input type="hidden" id="ddl_remove_child_layout_nonce" name="ddl_remove_child_layout_nonce" value="<?php echo wp_create_nonce('ddl_remove_child_layout_nonce'); ?>">

		</div>
	</div>
</script>

<script type="text/html" id="js-child-layout-box-row-tpl">
	<div id="js-child-layout-box-row-{{ cid }}" class="child-layout-box js-child-layout-box">
		<div class="ddl-dialog-header">
			<h2 class="js-dialog-title"><?php _e('This row has a Child layout cell and the layout has children', 'ddl-layouts'); ?></h2>
			<i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
		</div>
		<div class="ddl-dialog-content">
			<p><?php _e('If you delete the row that contains a Child layout cell you will lose the association with the existing child layouts', 'ddl-layouts'); ?></p>

			<p class="child-layout-remove">
				<button class="button js-delete-child-layout-and-remove-association delete-child-layout-button"><?php _e('Delete the row and remove the association with child layouts', 'ddl-layouts'); ?></button>
				<br />
				<span class="alert alert-child-layout"><i class="icon-warning-sign fa fa-exclamation-triangle"></i> <?php _e('The association will be updated when this layout is saved.', 'ddl-layouts'); ?></span>
			</p>
			
			<p class="child-layout-delete">
				<button class="button js-delete-child-layout-and-delete-association delete-child-layout-button"><?php _e('Delete the row and delete all the child layouts', 'ddl-layouts'); ?></button>
				<br />
				<span class="alert alert-child-layout"><i class="icon-warning-sign fa fa-exclamation-triangle"></i> <?php _e('The child layouts will be deleted when this layout is saved.', 'ddl-layouts'); ?></span>
				<br />
				<span class="alert alert-child-layout"><i class="icon-warning-sign fa fa-exclamation-triangle"></i> <?php _e('Association of child layouts to posts and post types will be removed.', 'ddl-layouts'); ?></span>
			</p>
			
			<p><button class="button js-edit-dialog-close delete-child-layout-button"><?php _e('Cancel', 'ddl-layouts'); ?></button></p>
			<p class="js-element-box-message-container message-container"></p>
		</div>
		<div class="ddl-dialog-footer">
			<input type="hidden" id="ddl_remove_child_layout_nonce" name="ddl_remove_child_layout_nonce" value="<?php echo wp_create_nonce('ddl_remove_child_layout_nonce'); ?>">

		</div>
	</div>
</script>