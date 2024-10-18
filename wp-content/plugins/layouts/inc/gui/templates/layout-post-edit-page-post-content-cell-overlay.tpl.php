<script type="text/html" id="js-ddl-post-content-message-in-post-editor-tpl">

    <div class="ddl-post-content-editor-layout-info">
        <p>
            <?php printf(__("You are using the %s%s%s layout to design this page"), '<span>', '{{{ _.escape(layout.name) }}}', '</span>'); ?>
        </p>

        <p class="ddl-post-content-editor-layout-info-buttons-wrap">
            <?php $href = sprintf('%sadmin.php?page=dd_layouts_edit&layout_id=%s&action=edit', admin_url(), '{{{ layout.id }}}'); ?>
            <a class="<?php printf('%s %s', 'button', user_can_edit_layouts() ? 'button-primary-toolset' : 'disabled' );?>" href="<?php echo user_can_edit_layouts() ? $href : '#'; ?>" title="Edit {{{ _.escape(layout.name) }}}">Edit layout</a> <button <?php disabled( user_can_assign_layouts() === false );?> href="#" title="Switch layout" class="js-ddl-switch-layout-button button button-secondary"><?php _e('Switch layout', 'ddl-layouts');?></button></p>
        </p>

    </div>
    <div class="ddl-post-content-show-post-post-editor-wrap js-ddl-post-content-show-post-post-editor-wrap">
        <?php printf(__("The content editor is not displayed because %s layout does not include any shortcodes or cells that display post content. %sShow editor anyway%s", 'ddl-layouts'), '{{{ _.escape(layout.name) }}}', '<p class="ddl-show-editor js-ddl-show-editor">', '</p>'); ?>
    </div>
</script>
<script type="text/html" id="js-ddl-post-content-message-in-post-editor-private-tpl">
    <div class="ddl-post-content-editor-layout-info">
        <p>
            <?php printf(__("You are using Layouts to design this page")); ?>
        </p>
        <p class="ddl-post-content-editor-layout-info-buttons-wrap">
            <button <?php disabled( user_can_delete_private_layouts() === false );?> title="<?php _e('Stop using Content Layout Editor', 'ddl-layouts');?>" class="js-ddl-stop-using-layouts-button button button-secondary" data-layout_type="private" data-layout_id="<?php echo $post->ID;?>" data-post_type="<?php echo $post_type;?>" data-content_id="<?php echo $post->ID;?>"><?php _e('Stop using Content Layout Editor', 'ddl-layouts');?></button>
            <?php $href = sprintf('%sadmin.php?page=dd_layouts_edit&layout_id=%s&action=edit', admin_url(), $post->ID); ?>
            <a class="<?php printf('%s %s', 'button', user_can_edit_private_layouts() ? 'button-primary-toolset' : 'disabled' );?>" href="<?php echo user_can_edit_private_layouts() ? $href : '#'; ?>" title="Edit {{{ _.escape(layout.name) }}}"><?php _e('Edit with Layouts', 'ddl-layouts');?></a>
        </p>

    </div>
    <?php if($has_private_layout === false || $private_layout_in_use !="yes"):?>
        <div class="ddl-post-content-show-post-post-editor-wrap js-ddl-post-content-show-post-post-editor-wrap">
            <?php printf(__("The content editor is not displayed because %s layout does not include any shortcodes or cells that display post content. %sShow editor anyway%s", 'ddl-layouts'), '{{{ _.escape(layout.name) }}}', '<p class="ddl-show-editor js-ddl-show-editor">', '</p>'); ?>
        </div>
    <?php else:?>
        <div class="ddl-post-content-show-post-post-editor-wrap js-ddl-post-content-show-post-post-editor-wrap">
            <?php printf(__("Since you are designing this page with Layouts, the standard WordPress editor is disabled.", 'ddl-layouts')); ?>
        </div>
    <?php endif;?>
</script>



<script type="text/html" id="js-ddl-post-content-switch-layout-dialog-html">
    <div class="ddl-dialog-header">
        <h2><?php printf(__('Switch Layout for %s', 'ddl-layouts'), '{{{post.post_title}}}'); ?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
        <!--<p><?php _e('This action will switch the Layout using the template already selected.', 'ddl-layouts'); ?></p> -->
        <label for="ddl-switch-layout">
            <?php printf(__('Layouts:', 'ddl-layouts'), ''); ?>
            <select id="ddl-switch-layout" name="ddl-switch-layout" class="js-ddl-switch-layout">
                <#

                    _.each(layouts, function(v){

                    if( +v.id === +current.id ){

                    #>
                    <option selected value="{{{v.slug}}}" class="{{{v.cell_post_content_type}}}">{{{v.name}}}</option>
                    <# } else { #>

                        <option value="{{{v.slug}}}" class="{{{v.cell_post_content_type}}}">{{{v.name}}}</option>
                        <#  }

                            });   #>
            </select>
        </label>

        <div class="switch-layout-message-container"></div>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button button-primary js-switch-layout-button-save switch-layout-button-save">
            <?php _e('Switch', 'ddl-layouts'); ?></button>
        <button class="button js-edit-dialog-close close-change-use"><?php _e('Cancel', 'ddl-layouts'); ?></button>
    </div>
</script>

<div class="ddl-dialogs-container">
    <div class="ddl-dialog auto-width" id="js-ddl-post-content-switch-layout-dialog-wrap"></div>
</div>


<script type="text/html" id="js-ddl-post-content-switch-layout-dialog-confirm-html">
    <div class="ddl-dialog-header">

        <h2><?php printf(__('Layout updated', 'ddl-layouts'), '{{{post.post_title}}}'); ?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>

    <div class="ddl-dialog-content">
        <?php printf(__('The Layout for %s was successfully updated to %s', 'ddl-layouts'), '<strong>{{{post.post_title}}}</strong>', '<strong>{{{current.name}}}</strong>'); ?>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button js-edit-dialog-close close-change-use"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>
</script>

<script type="text/html" id="js-ddl-post-content-message-in-post-editor-html">
    <?php printf(__('The "%s" layout does not include any shortcodes or cells that display post content, so your edits in the content area on this page will not appear anywhere. %sHide editor area%s'), '{{{_.escape(name)}}}', '<span class="ddl-hide-editor js-ddl-hide-editor">', '</span>');?>
</script>

<div class="ddl-dialogs-container">
    <div class="ddl-dialog auto-width" id="js-ddl-post-content-switch-layout-dialog-confirm-wrap"></div>
</div>