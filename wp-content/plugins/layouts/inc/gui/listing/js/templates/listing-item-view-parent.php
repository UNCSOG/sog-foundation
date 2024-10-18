<script type="text/html" id="table-listing-item-parent">
    <# var indent = 0, used_on_class = '';

        if( ddl.parent )
        {
        for(var i=1;i
    <ddl.depth+1;i++)
    {

    indent = i;
    }
    }
    #>

    <td class="select-listed-item">
        <input disabled type="checkbox" name="items[]" class="js-selected-items" value="{{ ddl.ID }}"/>
    </td>
    <td class="post-id page-id column-id">
        <strong><a class="row-id" href="admin.php?page=dd_layouts_edit&layout_id={{{ ddl.ID }}}&action=edit"
                   title="">{{{
            ddl.ID }}}</a></strong>
    </td>
    <td class="post-title page-title column-title" style="padding-left:{{{ 16 * indent }}}px">
        <# if(ddl.post_status == 'publish' ){ #>
            <strong><a class="row-title" href="admin.php?page=dd_layouts_edit&layout_id={{{ ddl.ID }}}&action=edit"
                       title="<?php _e('Edit ', 'ddl-layouts'); ?>{{{ ddl.post_title.replace(/"/g, '&quot') }}}">{{{
                ddl.post_title }}}</a></strong>
            <# }else{ #>
                <strong>{{{ ddl.post_title }}}</strong>
                <# } #>

                    <div class="row-actions">
                        <# if( ddl.post_status == 'publish' ){
                            var data = {
                            layout_id : ddl.ID,
                            trash_nonce : "<?php echo wp_create_nonce('layout-select-trash-nonce'); ?>",
                            delete_nonce : "<?php echo wp_create_nonce('layout-delete-layout-nonce'); ?>",
                            duplicate_nonce : "<?php echo wp_create_nonce('layout-duplicate-layout-nonce'); ?>",
                            value: 'trash'
                            };#>
                            <span class="edit"><a
                                    href="admin.php?page=dd_layouts_edit&layout_id={{ ddl.ID }}&action=edit"
                                    title="<?php _e('Edit this layout', 'ddl-layouts'); ?>"><?php _e('Edit', 'ddl-layouts'); ?></a> | </span>

                                <span class="restore">
<#
    if(
    ( typeof DDLayout_settings_editor !== 'undefined' &&
    +DDLayout_settings_editor.is_embedded === 1 ) ||
    ( typeof DDLayout_settings_editor !== 'undefined' &&
    typeof DDLayout_settings_create.user_can_create !== 'undefined' &&
    +DDLayout_settings_create.user_can_create === 0 )
    )
    {
    #>

    <a class="toolset-disabled-link" data-object="{{ JSON.stringify(data)}}"><?php _e('Duplicate', 'ddl-layouts'); ?></a>
    | </span>

                            <#    } else { #>

                                <a
                                <# data.value = 'duplicate'#> class="select-layout-action-in-listing-page
                                    js-select-layout-action-in-listing-page" data-object="{{ JSON.stringify(data)
                                    }}"><?php _e('Duplicate', 'ddl-layouts'); ?></a> | </span>
                                    <#    }
                                        #>

                                        <span class="restore strike"><a <# data.value = "change"#>
                                                title="<?php _e('Change layout use', 'ddl-layouts'); ?>"
                                                class="select-layout-action-in-listing-page strike" data-object="{{
                                                JSON.stringify(data)
                                                }}"><?php _e('Change layout use', 'ddl-layouts'); ?></a></span> |
                                        <span class="restore strike"><a <# data.value = 'trash'#> href=""
                                                class="js-layout-listing-restore-link
                                                js-layout-listing-restore-link-trash strike" data-object="{{
                                                JSON.stringify(data) }}" title="<?php _e('Trash', 'ddl-layouts'); ?>
                                                "><?php _e('Trash', 'ddl-layouts'); ?></a>  </span>
                                        <#
                                            }
                                            else{
                                            var data = {
                                            layout_id : ddl.ID,
                                            trash_nonce : "<?php echo wp_create_nonce('layout-select-trash-nonce'); ?>",
                                            delete_nonce : "<?php echo wp_create_nonce('layout-delete-layout-nonce'); ?>",
                                            value: 'publish'
                                            };
                                            #>

                                            <span class="restore"><a href="" class="js-layout-listing-restore-link"
                                                                     data-object="{{ JSON.stringify(data) }}"
                                                                     title="<?php _e('Restore', 'ddl-layouts'); ?>"><?php _e('Restore', 'ddl-layouts'); ?></a> | </span>
                                            <span class="delete"><a href=""
                                                                    class="submitdelete js-layout-listing-delete-permanently-link"
                                                                    data-object="<# data.value = 'delete' #>{{ JSON.stringify(data) }}"
                                                                    title="<?php _e('Delete permanently', 'ddl-layouts'); ?>"><?php _e('Delete permanently', 'ddl-layouts'); ?></a></span>

                                            <# } #>

                    </div>
    </td>

    <td class="column-used-on {{{ used_on_class }}}"></td>

    <# var locale = ddl.locale ? ddl.locale : 'en-US'; #>

        <td class="post-title page-title column-date">{{{ ddl.date_formatted }}}</td>

</script>