<script type="text/html" id="tpl-toolset-frontend-toolbar">
    <div class="ddl-builder-bar wp-core-ui">
        <div class="ddl-builder-bar-content">
            <!--<div class="ddl-builder-bar-history">
                <span class="button" data-action="history-undo">
                    <span class="dashicons dashicons-image-rotate"></span>
                </span>
                <span class="button" data-action="history-redo">
                    <span class="dashicons dashicons-image-rotate flip-h"></span>
                </span>
            </div> -->
            <span class="ddl-builder-bar-title">
                <span>{{ strings.title }}</span>
            </span>
	        <span class="ddl-builder-bar-help">
		        <a href="<?php echo WPDLL_FRONT_EDITOR;?>" alt="<?php _e('Editor help', 'ddl-layouts') ?>" target="_blank"><i class="fa fa-question-circle" aria-hidden="true"></i><span class="ddl-builder-bar-help-text"><?php _e('Editor help', 'ddl-layouts') ?></a>
	        </span>

            <div class="ddl-builder-bar-actions">
                <?php if( count( $this->layouts ) === 1 ){ ?>
                    <span class="button ddl-button-edit"><a href="<?php echo $this->layouts[0]->edit_url;?>">{{ strings.edit }}</a></span>
                <?php }else{ ?>
                    <span class="button ddl-button-edit js-ddl-button-edit" data-action="edit-backend">{{ strings.edit }}</span>
                <?php } ?>
                <a alt="{{{ strings.close }}}" href="<?php echo esc_url( remove_query_arg( 'toolset_editor', false) );?>" class="js-ddl-button-done-anchor"><span class="button button-secondary ddl-button-done js-ddl-button-done">{{{ strings.close }}}</span></a>
	            <span class="button button-primary button-save-layout" data-action="save-layout"><input data-close="no" name="save_layout" value="<?php _e('Update','ddl-layouts'); ?>" type="submit"></span>
            </div>
            <div class="js-ddl-message-container dd-message-container-fe" data-action="saveLayout"></div>
        </div>
    </div>
</script>

<script type="text/html" id="tpl-toolset-frontend-etid-layouts-menu">
    <div class="ddl-element-action-panel js-edit-layout-menu edit-layout-menu">
        <div class="ddl-element-action-panel-content">
            <# _.each( layouts, function(layout){ #>
            <div class="title">
                <a href="{{{layout.edit_url}}}" class="js-edit-layout-menu-anchor notarget" target="_blank">{{{ layout.name }}}</a>
            </div>
        <# }); #>
        </div>
    </div>
</script>