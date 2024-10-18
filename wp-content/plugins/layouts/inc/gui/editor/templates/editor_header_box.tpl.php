<div class="js-ddl-message-container dd-message-container"></div>

<div class="dd-layouts-wrap ddl-editor-wrap">

    <div class="dd-layouts-header js-hide-for-private-layout">
        <h1 class="ddl-editor-title <?php if( $this->is_private_layout ):?> hidden<?php endif; ?>">
			<?php  _e('Edit Layout',  'ddl-layouts'); ?>
        </h1>
        <!--<span class="js-layout-title dd-layout-title"></span>-->
        <div class="layouts-title-meta-info">
			<?php if( $this->is_private_layout === false ):?>
				<?php $icon_object = $this->print_layout_icon();?>
                <div id="iconwrap" class="js-layouts-icon-wrap">
                    <img src="<?php echo WPDDL_RES_RELPATH;?>/images/<?php echo $icon_object->icon;?>" class="js-layouts-icon layouts-icon" data-tooltip-content="<?php esc_attr_e( $icon_object->text ); ?>" data-tooltip-header="<?php esc_attr_e( $icon_object->header ); ?>" />
                </div>

                <div id="titlediv" class="js-title-div">

                    <div id="titlewrap">
                        <span id="change_layout_name_message"><?php _e('Please enter a name for this layout','ddl-layouts'); ?></span>
                        <input name="layout-title-input" id="title" class="js-layout-title dd-layout-title layout-title-input" value="<?php echo esc_attr(get_the_title($post->ID)); ?>"/>
                    </div>

                    <div id="edit-slug-box" class="hide-if-no-js">
                        <label for="layout-slug"><strong><?php _e('Layout slug:','ddl-layouts'); ?></strong></label>
                        <span id="layout-slug" name="layout-slug" type="text" class="edit-layout-slug js-edit-layout-slug"><?php echo urldecode( $post->post_name ); ?></span>
                        <span id="edit-slug-buttons"><a href="#post_name" class="edit-slug button button-small hide-if-no-js js-edit-slug"><?php _e( 'Edit', 'ddl-layouts' ); ?></a></span>
                        <span id="edit-slug-buttons-active" class="js-edit-slug-buttons-active"><a href="#" class="save button button-small js-edit-slug-save">OK</a> <a class="cancel js-cancel-edit-slug" href="#">Cancel</a></span>
                        <!--   <i class="icon-gear fa fa-cog edit-layout-settings js-edit-layout-settings" title="<?php _e( 'Set parent layout', 'ddl-layouts' ); ?>"></i> -->
                        <?php
                        $disabled = user_can_delete_layouts() ? '' : 'disabled="disabled"';
                        $disabled_class = user_can_delete_layouts() ? '' : "disabled";
                        ?>
                        <button type="button" class="button button-small hide-if-no-js js-trash-layout trash-layout <?php echo $disabled_class;?>" <?php echo $disabled;?> ><i class="fa fa-trash-o" aria-hidden="true"></i><!--<span>Move to trash<span>--></button>
                    </div>

                </div>
			<?php endif; ?>
        </div>
    </div>

    <input id="toolset-edit-data" type="hidden" value="<?php echo $post->ID; ?>" data-plugin="layouts" />
    <div class="toolset-video-box-wrap"></div>

	<?php do_action( 'ddl-print-editor-additional-help-link', $this->get_layouts(), $this->get_layout(), WPDD_Layouts_Cache_Singleton::get_name_by_id( $this->get_layout() ) ); ?>
</div>

<script type="text/html" id="ddl-layout-not-assigned-to-any">

    <div class="ddl-dialog-header">
        <h2><?php printf(__('%s', 'ddl-layouts'), '{{{ layout_name }}}');?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
		<?php printf(__('%s', 'ddl-layouts'), '{{{ message }}}'); ?>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>

<script type="text/html" id="ddl-layout-children-assignment_display">

    <div class="ddl-dialog-header">
        <h2><?php printf(__('%s', 'ddl-layouts'), '{{{ layout_name }}}'); ?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
		<?php printf(__('%s', 'ddl-layouts'), '{{{ message }}}'); ?>
        <# if( typeof children !== 'undefined' ){#>
        <div class="children-box-preview">
            <ul>
                <#
                _.each(children, function(child, index, list){

                if( child.hasOwnProperty('id') && child.id !== 1 && child.hasOwnProperty('items') &&
                child.items.length ){
                #>


                <#
                _.each(child.items, function(item, i, l ){

                if( item.hasOwnProperty('posts') && item.posts.length ){
                #>

                <#
                _.each(item.posts, function(post){
                if( post.link ) {
                #>
                <li><a href="{{{post.link}}}" class="js-layout-preview-link" target="wp-preview-{{{post.ID}}}">{{{post.post_title}}}</a></li>
                <#
                }
                });
                #>

                <#
                }
                if( item.hasOwnProperty('types') && item.types.length ){
                #>

                <#
                _.each(item.types, function(post){
                if( post.link ) {
                #>
                <li><a href="{{{post.link}}}" class="js-layout-preview-link" target="wp-preview-{{{post.ID}}}">{{{post.singular}}}</a></li>
                <#
                }
                });
                #>

                <#
                }
                if( item.hasOwnProperty('loops') && item.loops.length ){

                #>

                <#
                _.each(item.loops, function(post){
                if( post.href ) {
                #>
                <li><a href="{{{post.href}}}" target="wp-preview-{{{post.ID}}}">{{{post.title}}}</a></li>
                <#
                }
                });
                #>
                <#
                }
                });
                #>


                <#    }
                });#>
            </ul>
        </div>
        <#
        }
        #>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>




<script type="text/html" id="ddl-layout-children-no-assignment_display">

    <div class="ddl-dialog-header">
        <h2><?php printf(__('%s', 'ddl-layouts'), '{{{ layout_name }}}'); ?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content">
		<?php printf(__('%s', 'ddl-layouts'), '{{{ message }}}'); ?>
        <#
        if( typeof children !== 'undefined' && children[0].hasOwnProperty('items') ){

        #>
        <div class="children-box-preview">
            <ul>
                <#
                _.each(children[0].items, function(child, index, list){
                var url = window.location.href.split(/[?#]/)[0],
                url = url+'?page=dd_layouts_edit&layout_id='+child.id+'&action=edit';
                #>
                <li><a href="{{{url}}}">{{{child.post_title}}}</a></li>
                <#   }); #>
            </ul>
        </div>
        <#
        }
        #>
    </div>
    <div class="ddl-dialog-footer">
        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>





<script type="text/html" id="ddl-layout-assigned-to-many">
    <div class="ddl-dialog-header">
        <h2><?php _e('Select which page or post to use to view this Layout', 'ddl-layouts');?></h2>
        <i class="fa fa-remove icon-remove js-edit-dialog-close"></i>
    </div>
    <div class="ddl-dialog-content ddl-layout-assigned-to-many">
        <ul>
            <#
            var type = '', count = 0;
            _.each(links, function(v){

            #>

            <#
            var padding_top = count > 0 ? 'padding-top' : '';
            if( type !== v.type ){

            type = v.type;

            #>
			<?php  printf(__('%s', 'ddl-layouts'), '<li class="post-type {{ padding_top }}">{{{ v.types }}}:</li>'); ?>

            <#

            }

            if( v.href != '' && v.href != '#'){

            #>
            <li><a href="{{ v.href }}" title="{{{ v.title }}}" target="_blank" class="js-layout-preview-link">
                    {{{ v.title }}}
                </a>
            </li>



            <#
            count++;
            }
            else if( v.href == '#' ){
            #>
            <li>
                {{{ v.title }}}
            </li>

            <#
            count++;
            }
            }); #>
        </ul>
    </div>
    <div class="ddl-dialog-footer">

        <button class="button js-edit-dialog-close"><?php _e('Close', 'ddl-layouts'); ?></button>
    </div>

</script>

<div class="ddl-dialogs-container">
    <div class="ddl-dialog auto-width" id="js-view-layout-dialog-container"></div>
</div>

<script type="text/html" id="js-virtual-form-tpl">
    <form method='post' action="{{ href }}" target="_blank" id="js-virtual-form-preview">
        <input type='hidden' name='{{{name_prefix}}}layout_preview' id="js-layout-preview-json" />
    </form>
</script>