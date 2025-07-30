var DDLayout = DDLayout || {};
// post-content-cell.js

// Handles both post content and Views Content Template cells.

DDLayout.PostContentCell = function($)
{
    "use strict"
    var self = this,
        editor_selector = 'cell-post-content-editor',
        content_cached,
        textarea_name = 'ddl-layout-post-content';

    self.dialog_defaults = null;
    self.cell_type = 'cell-post-content';

    self.init = function() {

        _.bindAll(self, 'refresh_before', 'set_content_init', 'save_params_callback');

        self._cell_content = null;
        self._preview = {};

        _.delay( self.refresh_before, 1200 );

        jQuery(document).on('cell-post-content.dialog-open', self._dialog_open);
        jQuery(document).on('cell-post-content.dialog-close', self._dialog_close);
        jQuery(document).on('cell-post-content.get-content-from-dialog', self._get_content_from_dialog);
        Toolset.hooks.addFilter('ddl_save_layout_params', self.save_params_callback );
        Toolset.hooks.addFilter('ddl-save_layout_from_dialog_content_updated', self.save_response_callback );
    };

    self.update_dialog_defaults = function( content ){
        self.dialog_defaults = content;
    };

    self.update_content_cached = function( content ){
        content_cached = content;
    };

    self.save_response_callback = function( new_el, model ){
        if( model.get('cell_type') === self.cell_type ){
            self.update_dialog_defaults( content_cached );
            //new_el.html( content_cached );
        }
        return new_el;
    };

    self.save_params_callback = function ( save_params, layout_view ) {
        var post_id = DDLayout_post_content.current_post, post_content = save_params.post_content;

        if ( !post_id ) {
            delete save_params.post_content;
            return save_params;
        }

        if ( post_content === null ) {
            save_params.post_id = post_id;
            delete save_params.post_content;
            return save_params;
        }

        save_params.post_id = post_id;

        if ( _.isEqual(post_content, content_cached) ) {
            return save_params;
        }
        else if ( typeof post_content === 'undefined' ) {
            save_params.post_content = content_cached;
        } else {
            save_params.post_content = post_content;
            self.update_content_cached(post_content);
        }

        return save_params;

    };

    self.refresh_before = function(){
        if( typeof tinyMCE !== 'undefined' && tinyMCE.get(editor_selector) ){
            tinyMCE.get(editor_selector).remove();
        }
    }

    self._dialog_open = function( event, object, dialog ){

        if( null === self.dialog_defaults ){
            self.dialog_defaults = dialog._dialog_defaults['cell-post-content']['post-content'];
        }

        _.defer( self.set_content_init );
    };

    self._dialog_close = function(){
        // console.log('dialog close post content', arguments );
        _.without( tinyMCE.editors, editor_selector );
    };

    self._get_content_from_dialog = function(){
        //console.log('dialog get content', arguments );
    };

    self.set_content_init = function( ){

        window.wpcfActiveEditor = editor_selector;

        jQuery('#'+editor_selector+'-tmce').trigger('click');

        if( editor_selector in tinyMCE.editors ) {
            var tinymce_editor = tinyMCE.get( editor_selector );
            if( tinymce_editor.isHidden() ) {
                jQuery( '#'+editor_selector ).val( content );
            } else {
                try{
                    tinymce_editor.setContent( window.switchEditors.wpautop( window.switchEditors.pre_wpautop( self.dialog_defaults ) ) );
                } catch( e ){
                    console.log( e.message );
                }
            }
        }

        content_cached = self.dialog_defaults;
    };

    self.get_display_mode = function () {
        if (jQuery('#ddl-default-edit input[name="ddl-layout-page"]:checked').length) {
            return jQuery('#ddl-default-edit input[name="ddl-layout-page"]:checked').val();
        } else {
            return '';
        }
    };

    self.get_selected_post = function () {
        return jQuery('#ddl-default-edit #ddl-layout-selected_post').val();
    }


    self.get_preview = function ( content, current_text, specific_text, loading_text, preview_image, thiz){
        var width = thiz.model.get('width');

        if (preview_image) {
            preview_image = '<img src="' + preview_image + '" height="130px">';
        }

        if (content.page == 'current_page') {
            var image_size = 10;
            return '<div class="ddl-post-content-current-page-preview"><p>'+ current_text +'</p>'+
                preview_image+
                '</div>';
        } else {
            var post_id = content.selected_post;
            var divclass = 'js-post_content-' + post_id;
            if ( typeof(self._preview[post_id]) !== 'undefined' && self._preview[post_id] != null){
                var out = '<div class="ddl-post-content-current-page-preview '+ divclass +'">'+ self._preview[post_id] +'</div>';
                return out;
            }
            var out = '<div class="'+ divclass +'">'+ loading_text +'</div>';
            if (typeof(self._preview[post_id]) == 'undefined') {
                self._preview[post_id] = null;

                var data = {
                    action : 'ddl_post_content_get_post_content',
                    post_id: post_id,
                    wpnonce : jQuery('#ddl_layout_view_nonce').attr('value')
                };
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: data,
                    cache: false,
                    dataType: 'json',
                    success: function(data) {
                        //cache view id data
                        self._preview[post_id] = '<p>' + specific_text.replace('%s', '<strong>' + data.title + '</strong>')+ '</p>' + preview_image;
                        jQuery('.' + divclass).html(self._preview[post_id]);
                        DDLayout.ddl_admin_page.render_all();
                    }
                });
            }

            return out;
        }
    }

    self.init();
};

jQuery(document).on('DLLayout.admin.ready', function($){
    DDLayout.post_content_cell = new DDLayout.PostContentCell($);
});