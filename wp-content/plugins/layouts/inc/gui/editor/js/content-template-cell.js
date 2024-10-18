// content-template-cell.js
jQuery(function($){
    DDLayout.content_template_cell = new DDLayout.ContentTemplateCell($);
});

DDLayout.ContentTemplateCell = function($)
{
    var self = this, post_select_val = 0;

    //_.bindAll(self, self.loadContentViaAjaxCallback);

    self.init = function() {

        self._ct_editor = null;

        self._ct_code_mirror = null;

        self._preview_cache = {};

        self._preview = {};

        self._cell_content = null;

        self._wpv_inline_editor_qt = null;

        self._content_template_created = null;

        self._count_pages = 20;

        self.extra_editors = new DDLayout.ContentTemplateCell.EditorFactory();

        post_select_val = jQuery('#ddl-default-edit #ddl-layout-selected_post').val() || 0;

        self.turn_on_events = function(){
            jQuery('.js-ct-name').on('click', self._switch_to_edit_ct_name);

            jQuery('.js-create-new-ct').on('click', self._create_new_ct);

            jQuery('.js-ct-edit-name').on('blur', self._end_ct_name_edit);

            jQuery('.js-ct-edit-name').on('change', self.edit_name_callback);

            jQuery('.js-load-different-ct').on('click', self._switch_to_select_different_ct);

            jQuery('#post-content-view-template').on('change', self._handle_ct_change);
        };

        self.turn_on_events();

        jQuery(document).on('cell-content-template.dialog-open', function(e, content, dialog) {
            jQuery('.js-ct-edit').hide();


            self._dialog = dialog;
            self._content_template_created = Array();
            DDLayout.types_views_popup_manager.start();

            self._original_ct_name = '';
            self._original_ct_value = '';
            self._display_on_open = self.get_display_mode();

            // Check if Views Plugin is active.
            if ( $('.js-ddl-views-not-activated').length ) {
                dialog.disable_save_button(true);
                return;
            }

            if( !self._latest_selection_post_types ){
                self._latest_selection_post_types = jQuery('#ddl-default-edit .js-ddl-post-content-post-type').val();
            }


            if (!jQuery('#ddl-default-edit input[name="ddl-layout-page"]:checked').length) {
                jQuery('#ddl-default-edit input[name="ddl-layout-page"]').each( function () {
                    if (jQuery(this).val() == 'current_page') {
                        jQuery(this).prop('checked', true);
                    }
                });
            }


            jQuery('#ddl-default-edit .js-ddl-post-content-post-type').off('change');
            jQuery('#ddl-default-edit .js-ddl-post-content-post-type').on('change', self._handle_post_type_change);

            jQuery('#ddl-default-edit input[name="ddl-layout-page"]').off('change');
            jQuery('#ddl-default-edit input[name="ddl-layout-page"]').on('change', self.adjust_specific_page_state);

            self._initialize_post_selector();

            var select_post_type = jQuery('#ddl-default-edit .js-ddl-post-content-post-type').val();
            if ( select_post_type != jQuery('#ddl-default-edit #ddl-layout-selected_post').data('post-type') ) {
                self._cell_content = content;
               // if( self.is_other_page() ){
                  //  jQuery('#ddl-default-edit .js-ddl-post-content-post-type').trigger('change');
               // }
            }

            self.adjust_specific_page_state();

            if (dialog.is_new_cell()) {
               // self._create_new_ct();
                self.set_initial_state();
            } else {
                // Show the current Content Template
                jQuery('#post-content-view-template').trigger('change', 'disable');
            }

            if (!ddl_views_1_6_available) {
                jQuery('.js-dialog-edit-save').prop('disabled', true);
            }

        });

        jQuery(document).on('cell-content-template.dialog-close', function(e, content) {
            //Set origianl callbacks for quicktags
            if ( self._wpv_inline_editor_qt ) {
                self.remove_quicktags(self._wpv_inline_editor_qt);
            }

            //Remove temporary Content templates
            if (self._content_template_created.length) {
                for (var i = 0; i < self._content_template_created.length; i++) {
                    if (content.ddl_view_template_id == self._content_template_created[i]) {
                        self._content_template_created.splice(i, 1);
                        break;
                    }
                }
            }
            if (self._content_template_created.length) {
                var data = {
                    action : 'ddl_delete_content_templates',
                    wpnonce : $('#wpv-ct-inline-edit').attr('value'),
                    content_templates : self._content_template_created
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: data,
                    cache: false,
                    success: function(data) {
                    }
                });
            }

            self._close_codemirror();

            // remove help pointer if it's still visible.
            $('.ddl-ct-helper-pointer').remove();

            DDLayout.types_views_popup_manager.end();

            jQuery('#ddl-default-edit #ddl-layout-selected_post').toolset_select2('close');
            jQuery('#ddl-default-edit #ddl-layout-selected_post').toolset_select2('destroy');

            self.reset_name_highlight();

            self.extra_editors.destroy();
        });

        // Add 'wpv-filter-wpv-shortcodes-gui-wpv_post_body-exclude-content-template' filter
        Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv_post_body-exclude-content-template', self.wpv_filter_wpv_shortcodes_gui_exclude_content_template_callback );

        self.set_up_live_events( );
    };

    /**
     *  wpv_filter_wpv_shortcodes_gui_exclude_content_template_callback
     *
     *  Callback function for 'wpv-filter-wpv-shortcodes-gui-*-exclude-content-template' filter
     *  Returns the ID of currently editing content template, as an array.
     *  So it is not presented as a self-pointing CT for views short codes (i.e. wpv-post-body)
     */
    self.wpv_filter_wpv_shortcodes_gui_exclude_content_template_callback = function( excluded_cts ) {
        excluded_cts.push( self._ct_editor );
        return excluded_cts;
    }

    self.toggle_extra_editors_visibility = function( event ){
            var $me = $( this),
                $open = $me.next('div.wpv-ct-assets-inline-editor'),
                $caret = $me.find('i'),
                data = $(this).data();

            if( $( this).data('open') ){

                $( this).data( 'open', false );

                $caret.removeClass('fa-caret-up').addClass('fa-caret-down');

                $open.slideUp(400, function(event){

                });

            } else {

                $( this).data( 'open', true );

                $caret.removeClass('fa-caret-down').addClass('fa-caret-up');

                $open.slideDown(400, function(event){
                    // this is to nake CodeMirror happy and let it display content and line numbers correctly when wrap slides down
                    self.extra_editors.get_editor(data.id, data.type).refreshEditor();
                });
            }
            prevent_editors_caret_to_bother();
    };

    /* Views/Layouts JS incompatibility fix*/
    var prevent_editors_caret_to_bother = function(){
        $('.js-wpv-textarea-full').removeClass('fa-caret-down').removeClass('fa-caret-up');
    };

    self.init_extra_editor = function( data ){
           var id = data.id,
               mode = data.type;

        return self.extra_editors.set_editor( id, mode, 'wpv-ct-assets-inline-'+mode+'-editor-'+id, false, 'template'+ _.capitalize(mode)+'Accepted' );
    };

    self.set_up_live_events = function( ){

        jQuery(document).on('cell-content-template.get-content-from-dialog', function(e, content, dialog) {
            self._save_ct(content);
        });


        $( document ).on( 'click', '.js-wpv-editor-instructions-toggle', function() {
            var thiz = $( this );
            self.show_hide_formatting_help( thiz );
        });

        $( document ).on( 'click', '.js-wpv-ct-assets-inline-editor-toggle', self.toggle_extra_editors_visibility);
    };

    self.get_extra_editors_data = function(){
        var data = {};
        $('.js-wpv-ct-assets-inline-editor-toggle').each(function(i){
                var id = $(this).data('id'),
                    slug = $(this).data('type');

                data['ct_'+ slug +'_value'] = self.extra_editors.getEditorValue( id, slug );
        });

        return data;
    };

    self.init_extra_editors = function(){

        $('.js-wpv-ct-assets-inline-editor-toggle').each(function(i){
            var data = $(this).data( );
            self.init_extra_editor( data );
        });

       // self.editors_do_button();
    };

    self.editors_do_button = function(){
        _.each( self.extra_editors.get_editors(), function(v){
            var me = v;
            self._dialog.disable_save_button( me.getEditorValue() === me.value );

            v.get_extra_editor().on('change', function(instance, change){
                self._dialog.disable_save_button( instance.getValue() === me.value );
            });
        });
    };

    self.set_initial_state = function(  ){
        jQuery( '.js-ct-edit' ).hide();
        jQuery( '.js-ct-selector').show();
        jQuery('.js-ct-editor').hide();
        self._switch_to_select_different_ct();
    };

    self.edit_name_callback = function(event){
        if( jQuery(this).val() === '' ){
            self._dialog.disable_save_button(true);

            self._dialog.display_footer_message({
                message:DDLayout_settings.DDL_JS.strings.content_template_should_have_name,
                stay:true,
                stay_for:3000,
                close:true,
                'type':'warning'
            });

            jQuery('.ct-name-span').css({
                position:'relative',
                top:'6px',
                border:'1px solid red'
            });

        } else {
            self.reset_name_highlight();
            self._dialog.disable_save_button(false);
        }
    };

    self.reset_name_highlight = function(){
        if( self._dialog.close_footer_message() ){
            jQuery('.ct-name-span').css({
                position:'static',
                top:'0px',
                border:'none'
            });
        }
    };

    self.handle_ct_editor_change = function(event){
        if( !self._ct_code_mirror ) return;

        var val = self._ct_code_mirror.getValue();

        //self._dialog.disable_save_button( val === '' );

        self._ct_code_mirror.on('change', function(instance, changeObj){
                //self._dialog.disable_save_button( instance.getValue() === '' );
        });
    };

    self._handle_post_type_change = function(event) {
        var data = {
            post_type : jQuery(this).val(),
            action : 'get_posts_for_post_content',
            nonce : jQuery(this).data('nonce')
        };

        var spinnerContainer = jQuery('<div class="spinner ajax-loader">').css('visibility', 'visible').insertAfter(jQuery(this)).show();
        jQuery('#ddl-default-edit #ddl-layout-selected_post').hide();
        jQuery('#ddl-default-edit #ddl-layout-selected_post').toolset_select2('destroy');

        jQuery.ajax({
            type:'post',
            url:ajaxurl,
            data:data,
            success: function(response){ // TODO: success is deprecated http://api.jquery.com/jQuery.ajax/
                if( jQuery('li#js-post-content-specific-page').find('#ddl-layout-selected_post').length ){
                    jQuery('#ddl-default-edit #ddl-layout-selected_post').replaceWith(response);
                } else {

                    jQuery('li#js-post-content-specific-page').append( response );
                }

                if (self._cell_content) {
                    self._select_post(self._cell_content.selected_post);
                }

                spinnerContainer.css('visibility', 'hidden').remove();
                self._initialize_post_selector();
                jQuery('#ddl-default-edit #ddl-layout-selected_post').fadeIn(200);
                self._handle_post_select_change();
                self._latest_selection_post_types = data.post_type;
                self._count_pages = 20;
                self.pagination_page = 1;
                self.load_more_paginated_options( );
            }
        });
    };

    self.load_more_paginated_options = function (post_type, nonce) {
        jQuery( '#ddl-layout-selected_post' ).on('toolset_select2:select', {post_type:post_type, nonce:nonce}, self.toolset_select2_onSelect);
    };

    self.toolset_select2_onSelect = function(event) {
        var target = this.selectedOptions.length ? this.selectedOptions[0] : null,
            post_type=event.data.post_type, nonce=event.data.nonce;
        if ( target != null && event != null ) {
            event.target = target;
            target = jQuery(event.target);
        }

        if (target && target.hasClass('js-show-more-posts-options')) {
            event.preventDefault();
            do_select_pagination_call(event, post_type, nonce);
        } else {
            //return self.toolset_select2_onSelect.apply(this, arguments);
        }
    };

    var do_select_pagination_call = function ( event ) {

        self.pagination_page++;

        var select = jQuery('#ddl-default-edit #ddl-layout-selected_post'),
            selected = select.val(),
            params = {
                action: 'posts_for_post_content_json',
                nonce: jQuery('#ddl-default-edit .js-ddl-post-content-post-type').data('nonce'),
                post_type: jQuery('#ddl-default-edit .js-ddl-post-content-post-type').val(),
                page: self.pagination_page,
                selected: selected
            };

        var loader = jQuery('<i class="fa fa-spinner icon-spinner icon-spin load-posts-spinner"></i>');
        loader.css({
            float: 'right',
            visibility: 'visible',
            fontSize:'20px',
            position:'relative',
            top:'-2px',
            color:'white'
        });

        jQuery( event.target).append( loader );

        jQuery( event.target).mouseenter(function(){
                 jQuery(this).find('i').css('color', 'white');
        });

        jQuery( event.target).mouseleave(function(){
            jQuery(this).find('i').css('color', 'black');
        });

        WPV_Toolset.Utils.do_ajax_post(params,{
            success: function (response, params) {
                var total = response.Data.total,
                     show_more = jQuery('.js-show-more-posts-options').clone();

                self._count_pages += response.Data.count;

                loader.remove();

                jQuery('#ddl-default-edit #ddl-layout-selected_post').append(response.Data.html);

                jQuery('.js-show-more-posts-options').remove();

                if( self._count_pages < total ){
                    jQuery('#ddl-default-edit #ddl-layout-selected_post').append(show_more);
                }

                self.toolset_select2_element.close();
                self.toolset_select2_element.open();
            },
            error: function (response, params) {
                console.log( 'error', arguments );
                loader.remove();
            }
        });
    };

    self._initialize_post_selector = function () {

        var select = jQuery('#ddl-default-edit #ddl-layout-selected_post');

        self.toolset_select2_element = select.toolset_select2({
            'width' : 'resolve'
        }).css('visibility', 'hidden').data('toolset_select2');

        select.off('click');
        select.on('click', function(event){
            post_select_val = jQuery(this).val();
        });

        select.off('change');
        select.on('change', self._handle_post_select_change);
    };

    self._handle_post_select_change = function (event) {

        if (self.get_display_mode() == 'this_page') {
            if( self._dialog.get_cell_type() == 'cell-content-template' )
            {
                self.get_selected_post_title();
                self._dialog.disable_save_button(self.get_selected_post() == '' ||
                !DDLayout.content_template_cell.is_save_ok());
            }
            else
            {
                self._dialog.disable_save_button(self.get_selected_post() == '' );
            }
        }
    };

    self.adjust_specific_page_state = function ( event ) {

        if (self.get_display_mode() == 'current_page') {
            jQuery('#ddl-default-edit #js-post-content-specific-page').hide('fast', function(){
                jQuery('#ddl-default-edit #ddl-layout-selected_post').toolset_select2("close");
            });

            var disable_save = false;
            if (self._dialog.get_cell_type() == 'cell-content-template') {
                disable_save = !DDLayout.content_template_cell.is_save_ok();
                if( disable_save === false ){
                    disable_save = self._ct_code_mirror && self._ct_code_mirror.getValue() == '';
                }
            }

            self._dialog.disable_save_button(disable_save);

        } else {
            if( self._latest_selection_post_types != jQuery('#ddl-default-edit .js-ddl-post-content-post-type').val() || self.get_post_select_empty() ){
                jQuery('#ddl-default-edit .js-ddl-post-content-post-type').trigger('change');
            } else {
                if( event && self._display_on_open !== self.get_display_mode() ){
                    jQuery('#ddl-default-edit select[name="ddl-layout-selected_post"] option').each(function(i){
                        if( i == 0 ){
                            jQuery(this).prop('selected', true);
                            jQuery('#ddl-default-edit select[name="ddl-layout-selected_post"]').val( jQuery(this).val()).trigger("change");
                            jQuery('#ddl-default-edit #ddl-layout-selected_post').toolset_select2( 'val', jQuery(this).val() );
                        }
                    });
                }
                self.load_more_paginated_options( );
            }

            jQuery('#ddl-default-edit #js-post-content-specific-page').show();

            jQuery('#s2id_ddl-layout-selected_post').css('visibility', 'visible');

            self._handle_post_select_change();

            //self._dialog.disable_save_button( self._ct_code_mirror && self._ct_code_mirror.getValue() == '' );
        }
    };

    self.is_other_page = function(){
        return self.get_display_mode() === 'this_page';
    };

    self._select_post = function (selected_post) {
        var select = jQuery('#ddl-default-edit #ddl-layout-selected_post');

        if( self._display_on_open === 'current_page' ){
            select.find('option').eq(0).prop('selected', true).trigger('change');
            return;
        } else{

            select.val(selected_post);
            if (select.val() != selected_post) {
                select.find('option').eq(0).prop('selected', true).trigger('change');
            }
        }
    }


    self._handle_ct_change = function( event, disable ) {
        if ( jQuery(this).val() == 'None' ) {
            self._dialog.disable_save_button(true);
        } else {
            self._dialog.disable_save_button( disable === 'disable' || !self._is_post_selected_ok()  );

            var ct_id = jQuery(this).find('option:selected').data('ct-id');
            var ct_name = jQuery(this).find('option:selected').text();

            if (jQuery('.js-create-new-ct').length > 0) {

                // Only show CT editor if Views plugin is available.

                self._open_ct_editor(ct_id, ct_name);
                jQuery('.js-ct-selector').hide();
                jQuery('.js-ct-edit').hide();
            } else {
                self._show_ct_preview(ct_id, ct_name)
            }

        }
    };


    self._save_ct = function (content) {

        try{
            if (ddl_views_1_6_available && self._ct_editor) {

                var ct_title = jQuery('.js-ct-edit-name').val();
                var ct_value = self._ct_code_mirror.getValue();

                ct_value = WPV_Toolset.Utils._strip_scripts( ct_value )

                self._preview_cache[content.ddl_view_template_id] = ct_value;
                if ( ct_title == ''){
                    ct_title = self._original_ct_name;
                    //return false;
                }

                var extra_changed = self.extra_editors.someHasChanged();

                if (self._original_ct_name != ct_title || self._original_ct_value != ct_value || extra_changed ) {

                    var data = {
                        action : 'wpv_ct_update_inline',
                        ct_value : ct_value,
                        ct_id : self._ct_editor,
                        ct_title : WPV_Toolset.Utils._strip_tags_and_preserve_text(ct_title),
                        wpnonce : $('#wpv_inline_content_template').attr('value')
                    };

                    data = _.extend( data, self.get_extra_editors_data() );

                    $.post(ajaxurl, data, function(response) {

                        if( extra_changed ){
                            self.extra_editors.resetEditors();
                        }

                        if (self._original_ct_name != ct_title) {
                            // we need to refresh the ct drop down.
                            self._refresh_ct_dropdown(0);
                        }

                    });
                }
            }

        } catch (e){
            console.log( e.message );
        }

    }

    self._refresh_ct_dropdown = function (select_id) {
        var data = {
            action : 'dll_refresh_ct_list',
            wpnonce : $('#wpv-ct-inline-edit').attr('value')
        };
        $.post(ajaxurl, data, function(response) {

            jQuery('.js-ct-select-box').html(response);

            if (select_id) {
                jQuery('#post-content-view-template option').each( function () {
                    if (jQuery(this).data('ct-id') == select_id) {
                        jQuery('#post-content-view-template').val(jQuery(this).val());
                    }
                })
            }

            jQuery('#post-content-view-template').on('change', self._handle_ct_change);
        });
    }

    self._setup_ct_mode = function () {
        var no_ct_selected = jQuery('#post-content-view-template').val() == 0;

        if (no_ct_selected) {
            jQuery('.js-ct-edit').hide();
            jQuery('.js-ct-selector').show();
        }

        self._dialog.disable_save_button(no_ct_selected || !self._is_post_selected_ok());

        if (jQuery('#post-content-view-template option').length == 1) {
            // Only the "None" option
            // Create a new CT automatically

            self._create_new_ct();

        }

    }

    self._close_codemirror = function () {

        try{
            self._ct_value = '';
            if (self._ct_editor) {
                self._ct_value = self._ct_code_mirror.getValue();
                icl_editor.codemirror('wpv-ct-inline-editor-' + self._ct_editor, false);
                self._ct_editor = null;
            }

        } catch (e){
            console.log( e.message );
        }
    }

    self.display_post_content_info = function(content, current_text, specific_text, loading_text, preview_image, that) {
        var preview = '';

        if (content.ddl_view_template_id != 0) {
            preview += '<br />';

            var div_place_holder = 'js-content-template-preview-' + content.ddl_view_template_id;

            if (typeof (self._preview_cache[content.ddl_view_template_id]) !== 'undefined' && self._preview_cache[content.ddl_view_template_id] != null) {
                // get it from the cache.

                preview += '<div class="' + div_place_holder + '">' + self._preview_cache[content.ddl_view_template_id].replace(/\n/g,"<br />").replace(/\t/g,"&nbsp;&nbsp;&nbsp;&nbsp;") + '</div>';
                if ( (self._preview_cache[content.ddl_view_template_id].match(/\n/g) || []).length < 3) {
                    // Remove the fadeout if it's less than 3 lines of text in CT.
                    preview += '<script type="text/javascript">'+
                                'jQuery(".' + div_place_holder +'").closest(".cell-content").removeClass("cell-preview-fadeout").addClass("content-template-preview");'+
                                '</script>';
                }
                preview = '<i class="icon-views-logo ont-icon-24"></i>' + preview;

            } else {
                // create a place holder and fetch it.

                var local_copy = jQuery.jStorage.get('content-template-' + content.ddl_view_template_id, '');
                if (local_copy) {
                    preview += '<div class="' + div_place_holder + '">' + local_copy.replace(/\n/g,"<br />").replace(/\t/g,"&nbsp;&nbsp;&nbsp;&nbsp;") + '</div>';
                    preview = '<i class="icon-views-logo ont-color-gray ont-icon-24"></i>' + preview;
                } else {
                    preview += '<div class="' + div_place_holder + '">' + loading_text + '</div>';
                }

                if ( typeof (self._preview_cache[content.ddl_view_template_id]) == 'undefined' ) {
                    self._preview_cache[content.ddl_view_template_id] = null;

                    var data = {
                        action : 'ddl_content_template_preview',
                        view_template: content.ddl_view_template_id,
                        wpnonce : $('#wpv-ct-inline-edit').attr('value'),
                    };
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'post',
                        dataType:'json',
                        data: data,
                        cache: false,
                        success: function(response) {
                            //cache view id data
                            if(!response){
                                var response = {Data:{content: '<div class="ddl-center-align">'+DDLayout_settings.DDL_JS.strings.views_plugin_missing+'</div>'}};
                            }

                            if(!response.hasOwnProperty('Data') || !response.Data.hasOwnProperty('content')){
                                response = {Data:{content: '<div class="ddl-center-align">'+DDLayout_settings.DDL_JS.strings.views_plugin_missing+'</div>'}};
                            } else {

                                self._preview_cache[content.ddl_view_template_id] = response.Data.content;

                                var local_copy = jQuery.jStorage.get('content-template-' + content.ddl_view_template_id, '');

                                if (local_copy != response.Data.content) {

                                    jQuery.jStorage.set('content-template-' + content.ddl_view_template_id, response.Data.content);

                                    jQuery(div_place_holder).html(response.Data.content);

                                    // If we have received all the previews we need to refresh
                                    // the layout display to re-calculate the heights.

                                    var all_previews_ready = true;
                                    for (var key in self._preview_cache) {
                                        if (self._preview_cache.hasOwnProperty(key)) {
                                            if (self._preview_cache[key] == null) {
                                                all_previews_ready = false;
                                            }
                                        }
                                    }

                                    if (all_previews_ready) {
                                        DDLayout.ddl_admin_page.render_all();
                                    }
                                }
                            }
                        }
                    });

                }
            }
        }

        // Add the post content cell preview at the start.
        preview = self.get_preview(content, current_text, specific_text,  loading_text, preview_image, that).replace('ddl-post-content-current-page-preview', 'ddl-post-content-template-preview') + preview;

        return preview;
    };

    self._open_ct_editor = function (id, name) {
        $('<div class="spinner ajax-loader-bar js-ct-loading">').insertBefore($('.js-ct-selector').first()).show();
        self._dialog.disable_save_button(true);

        if (id == 0) {
            // we need to create a new one
            data = {
                action : 'dll_add_view_template',
                ct_name : name,
                wpnonce : $('#wpv-ct-inline-edit').attr('value'),
            };
            $.post(ajaxurl, data, function(response) {
                response = JSON.parse(response);
                id = response['id'];
                self._content_template_created.push(id);
                self._fetch_ct_and_show_editor(id, name, true, false);

                self._refresh_ct_dropdown(id);

                if (!jQuery.jStorage.get( 'ct_help_shown')) {
                    jQuery(document).on('ddl-ct-editor-loaded', function () {
                        _.delay(show_fields_button_helper, 1000);

                    });
                }

            });

        } else {
            self._fetch_ct_and_show_editor(id, name, false, false);
        }
    }

    var show_fields_button_helper = function () {
        if (jQuery.jStorage.get( 'ct_help_shown')) {
			return;
		}

		jQuery.jStorage.set( 'ct_help_shown', true);

        var fields_button = jQuery('#ddl-default-edit .js-code-editor-toolbar-button-v-icon').filter( ':visible' );

        var message = DDLayout_settings.DDL_JS.strings.new_ct_message;

        var toolset_pointer = fields_button.pointer({
            pointerClass: 'wp-toolset-pointer wp-toolset-layouts-pointer ddl-ct-helper-pointer',
            content: '<h3>' + DDLayout_settings.DDL_JS.strings.new_ct_message_title + '</h3><p>' + message + '</p>',
            position: {
                edge: 'bottom'
            },
            pointerWidth: 420,
            buttons: function( event, t ) {
                var close  = ( wpPointerL10n ) ? wpPointerL10n.dismiss : 'Dismiss';
                var button_close = jQuery('<button class="button button-primary-toolset alignright js-wpv-close-this">' + close + '</button>');

                return button_close.on( 'click.pointer', function( e ) {
                    e.preventDefault();
                    t.element.pointer('close');
                });
            },
            show: function( event, t ) {
                t.pointer.show();
                t.opened();
            }
        });
        toolset_pointer.pointer('open');

        var parent_dialog = jQuery('.js-ddl-dialog-content');
        parent_dialog.off( 'scroll' );
        parent_dialog.on( 'scroll', function( e ) {
            if( toolset_pointer && toolset_pointer.hasOwnProperty('pointer') && jQuery( toolset_pointer.pointer( 'widget' ) ).is( ':visible' ) ) {
                toolset_pointer.pointer( 'reposition' );
            }
        } );

        jQuery( window ).resize( function() {
            waitForFinalEvent( function() {
                if( jQuery( toolset_pointer.pointer( 'widget' ) ).is( ':visible' ) ) {
                    toolset_pointer.pointer( 'reposition' );
                }
            }, 405, "after resizing window" );
        } );
    }

    self._show_ct_preview = function (id, name) {
        self._fetch_ct_and_show_editor(id, name, false, true);
    }

    self._fetch_ct_and_show_editor = function (id, name, focus_on_name, preview_mode) {

        self._dialog.disable_cancel_button(true);

        var data = {
            action : preview_mode ? 'ddl_ct_loader_inline_preview' : 'wpv_ct_loader_inline',
            id : id,
            include_instructions : 'layouts_content_cell',
            wpnonce : $('#wpv-ct-inline-edit').attr('value')
        };


        $.post( ajaxurl, data,  function(response){
            _.defer(self.loadContentViaAjaxCallback, response, id, name, focus_on_name, preview_mode);
        });
    };

    self.loadContentViaAjaxCallback = function(response,id, name, focus_on_name, preview_mode) {

        self._dialog.disable_cancel_button(false);
        self._dialog.disable_save_button(!self._is_post_selected_ok());

        $('.js-wpv-ct-inline-edit').html(response).show().attr('id', "wpv_ct_inline_editor_" + id);
        $('.js-wpv-ct-inline-edit .js-wpv-ct-update-inline').remove();

        if( typeof cred_cred != 'undefined'){
            cred_cred.posts();
        }

        self._ct_editor = id;
        if (preview_mode) {
            self._ct_code_mirror = CodeMirror.fromTextArea(document.getElementById( 'wpv-ct-inline-editor-'+id ), {
                mode: "myshortcodes",
                lineNumbers: true,
                lineWrapping: true,
                //viewportMargin: Infinity
                readOnly: "nocursor"
            });
        } else {
            try{
                self._ct_code_mirror = icl_editor.codemirror('wpv-ct-inline-editor-'+id, true);
                self._wpv_inline_editor_qt = quicktags( { id: "wpv-ct-inline-editor-"+id, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
                WPV_Toolset.add_qt_editor_buttons( self._wpv_inline_editor_qt, self._ct_code_mirror );
                Toolset.hooks.doAction( 'toolset_text_editor_CodeMirror_init', "wpv-ct-inline-editor-"+id );
            } catch( e ){
                console.log( e.message );
            }
        }

        // Hide "CRED forms" button (it doesn't work at the moment)
        jQuery('.cred-form-shortcode-button2').hide();

        jQuery('.js-ct-edit-name').hide();
        jQuery('.js-ct-name').html(name);
        jQuery('.js-ct-edit-name').val(name);
        jQuery('.js-ct-edit').show();

        jQuery('.js-ct-loading').remove();

        self._original_ct_name = name;

        var editor_loaded = false;

        try{

            self._original_ct_value = self._ct_code_mirror.getValue();
            self._ct_code_mirror.refresh();
            editor_loaded = true;

        } catch (e){

            jQuery('.js-default-dialog-content').empty();

            jQuery('.js-default-dialog-content').wpvToolsetMessage({
                text: DDLayout_settings.DDL_JS.strings.user_no_caps_cell,
                type: 'warning',
                stay: true,
                stay_for:10000,
                close: true,
                onOpen: function() {
                    jQuery('html').addClass('toolset-alert-active');
                },
                onClose: function() {
                    jQuery('html').removeClass('toolset-alert-active');
                }
            });

            console.log( e.message );
        }

        if( editor_loaded ){

            DDLayout.types_views_popup_manager.set_position_and_target(
                jQuery('#ddl-default-edit .js-code-editor-toolbar-button-v-icon'),
                'wpv-ct-inline-editor-'+id );

            if (focus_on_name) {
                self._switch_to_edit_ct_name();
            }

            jQuery(document).trigger('ddl-ct-editor-loaded');
        }


    };

    jQuery(document).on('ddl-ct-editor-loaded', function(event){
        self.handle_ct_editor_change(event);
        self.init_extra_editors();
    });

    self.show_hide_formatting_help = function( thiz ) {
        $( '.' + thiz.data( 'target' ) ).slideToggle( 400, function() {
            thiz
                .find( '.js-wpv-toggle-toggler-icon i' )
                .toggleClass( 'fa-caret-down fa-caret-up' );
        });
    };

    self._switch_to_ct_select_mode = function () {
        jQuery('.js-ct-selector').show();
        jQuery('.js-ct-editor').hide();
    }

    self._create_new_ct = function () {
        if (ddl_views_1_6_available) {
            jQuery('.js-ct-selector').hide();
            var name = self._get_unique_name(ddl_new_ct_default_name);
            self._open_ct_editor(0, name);
        }
    }

    self._get_unique_name = function (name) {
        var count = 0;
        name = name.replace('%s', DDLayout.ddl_admin_page.get_layout().get_name());
        var test_name = name;

        do {
            in_use = false;

            jQuery('#post-content-view-template option').each(function () {
                if (jQuery(this).html() == test_name) {
                    in_use=true;
                }
            });

            if (in_use) {
                count++;
                test_name = name + ' - ' + count;
            }
        } while (in_use);

        return test_name;
    }

    self._switch_to_edit_ct_name = function () {
        jQuery('.js-ct-editing').hide();
        jQuery('.js-ct-edit-name').val( jQuery('.js-ct-name').html() );
        jQuery('#ddl-default-edit-cell-name').val( jQuery('.js-ct-edit-name').val() );
        jQuery('.js-ct-edit-name').show().focus();
    }

    self._end_ct_name_edit = function () {
        jQuery('.js-ct-edit-name').hide();
        jQuery('.js-ct-name').html( jQuery('.js-ct-edit-name').val() );
        jQuery('.js-ct-editing').show();
        jQuery('#ddl-default-edit-cell-name').val( jQuery('.js-ct-edit-name').val() );
    }

    self._switch_to_select_different_ct = function () {
        jQuery('.ddl-ct-helper-pointer').remove();
        jQuery('#post-content-view-template').val(0);
        self._dialog.disable_save_button(true);
        jQuery('.js-ct-edit').hide();
        self._close_codemirror();
        self._switch_to_ct_select_mode();
    }

    self.is_save_ok = function () {
        return jQuery('#post-content-view-template').val() != 0;
    }

    self._is_post_selected_ok = function () {
        return self.get_display_mode() == 'current_page' ||
            self.get_selected_post() != '';
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
    };

    self.get_selected_post_title = function(){
        var post_value = jQuery('#ddl-default-edit #ddl-layout-selected_post :selected').text();
        jQuery('#toolset_select2-ddl-layout-selected_post-container').text(post_value);
    };

    self.get_post_select_empty = function(){
        return jQuery('#ddl-default-edit select[name="ddl-layout-selected_post"] option').length === 0;
    };

    self.remove_quicktags = function( qt ){
        for (var button_name in qt.theButtons) {
            if (qt.theButtons.hasOwnProperty(button_name)) {
                qt.theButtons[button_name].callback = qt.theButtons[button_name].old_callback;
            }
        }
    }

    // DEPRECATED
    // @todo check and remove
    self.handle_quicktags = function (qt) {

        for (var button_name in qt.theButtons) {
            if (qt.theButtons.hasOwnProperty(button_name)) {
                qt.theButtons[button_name].old_callback = qt.theButtons[button_name].callback;
                if ( qt.theButtons[button_name].id == 'img' ){
                    qt.theButtons[button_name].callback = function (element, canvas, ed) {
                        var t = this;
                        var selection = self._ct_code_mirror.getSelection();
                        var e="http://";
                        var g=prompt(quicktagsL10n.enterImageURL,e);
                        var f=prompt(quicktagsL10n.enterImageDescription,"");
                        t.tagStart='<img src="'+g+'" alt="'+f+'" />';
                        selection = t.tagStart;
                        t.closeTag(element, ed);
                        self._ct_code_mirror.replaceSelection(selection, 'end');
                        self._ct_code_mirror.focus();
                    }
                }
                else if ( qt.theButtons[button_name].id == 'close' ){

                }
                else if ( qt.theButtons[button_name].id == 'link' ){
                    var t = this;
                    jQuery('#wp-link-submit').on('click', function(){
                        var selection = self._ct_code_mirror.getSelection();
                        var target = '';
                        if ( jQuery('#link-target-checkbox').prop('checked') ){
                            target = '_blank';
                        }
                        html = '<a href="' + jQuery('#url-field').val() + '"';
                        title = '';
                        if ( jQuery('#link-title-field').val() ) {
                            title = jQuery('#link-title-field').val().replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
                            html += ' title="' + title + '"';
                        }

                        if ( target ) {
                            html += ' target="' + target + '"';
                        }

                        html += '>';
                        if ( selection === ''){
                            html += title;
                        }else{
                            html += selection;
                        }
                        html += '</a>';
                        t.tagStart=html;
                        selection = t.tagStart;
                        self._ct_code_mirror.replaceSelection(selection, 'end');
                        self._ct_code_mirror.focus();
                        jQuery('#wp-link-backdrop,#wp-link-wrap').hide();
                        return false;
                    });
                }
                else{
                    qt.theButtons[button_name].callback = function (element, canvas, ed) {

                        var t = this;

                        var selection = self._ct_code_mirror.getSelection();

                        if ( selection.length > 0 ) {
                            if ( !t.tagEnd ) {
                                selection = selection + t.tagStart;
                            } else {
                                selection = t.tagStart + selection + t.tagEnd;
                            }
                        }
                        else {

                            if ( !t.tagEnd ) {
                                selection = t.tagStart;
                            } else if ( t.isOpen(ed) === false ) {
                                selection = t.tagStart;
                                t.openTag(element, ed);
                            } else {
                                selection = t.tagEnd;
                                t.closeTag(element, ed);
                            }
                        }

                        self._ct_code_mirror.replaceSelection(selection, 'end');
                        self._ct_code_mirror.focus();
                    }
                }
            }
        }
    };

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
    };

    self.init();
};


DDLayout.ContentTemplateCell.ExtraEditor = function( id, slug, selector, allow_quicktags, propertyToUpdate ){
        var extra_type = {'js':'javascript', 'css':'css'};
        this.id = id;
        this.slug = slug;
        this.selector = selector;
        this.allow_quicktags = allow_quicktags;
        this.propertyToUpdate = propertyToUpdate;
        this.mode = extra_type[slug];
        this.editor = null;
        this.has_changed = false;

        this.value = null;

    _.bindAll( this, 'refreshEditor' );
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.build_extra_editor = function(){

    WPV_Toolset.CodeMirror_instance[this.selector] = icl_editor.codemirror(this.selector, true, this.mode);

    this.editor = WPV_Toolset.CodeMirror_instance[this.selector];

    this.editorReset();

    if(this.allow_quicktags) {
        var quicktags_slug = this.selector + '_quicktags';
        this[quicktags_slug] = quicktags( { id: this.selector, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
        WPV_Toolset.add_qt_editor_buttons( this[quicktags_slug], this.editor );
    }

    this.setChange();

    return this;
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.get_extra_editor = function(){
        return this.editor;
};


DDLayout.ContentTemplateCell.ExtraEditor.prototype.editorReset = function(){
    try{
        this.setEditorValue( document.getElementById( this.selector ).value );
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
        return this;
    }
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.editorResetValue = function(){
    try{
        this.value = this.get_extra_editor().getValue();
        this.has_changed = false;
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.refreshEditor = function(){
    try{
        this.get_extra_editor().refresh();
        this.get_extra_editor().focus();
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }

};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.getEditorValue = function(){

    var value = '';

    try{
        value = this.get_extra_editor().getValue();
    } catch( e ){

        console.log( e.message );
        value = '';
    }
    return value;
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.setEditorValue = function( value ){
    try{

        this.get_extra_editor().setValue( value );
        this.value = value;
        _.defer( this.refreshEditor );

    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.setChange  = function(){

    var self = this;

    this.get_extra_editor().on('update', function( instance, changeObj ){
            var current_val = instance.getValue();

            if( self.value === current_val ){
                self.has_changed = false;
            } else {
                self.has_changed = true;
            }
    });
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.hasChanged = function(){
        return this.has_changed;
};

DDLayout.ContentTemplateCell.ExtraEditor.prototype.destroy = function(){
    WPV_Toolset.CodeMirror_instance[this.selector] = null;
    window.iclCodemirror[this.selector] = null;
};



DDLayout.ContentTemplateCell.EditorFactory = function(){
        var self = this,
            editors = {};

        self.set_editor = function( id, slug, selector, allow_quicktags, propertyToUpdate ){
            /* Every editor is a singleton */
            if( self.editor_exists( id, slug ) === false ){
                var extra = new DDLayout.ContentTemplateCell.ExtraEditor( id, slug, selector, allow_quicktags, propertyToUpdate );
                editors[slug+'_'+id] = extra.build_extra_editor();
            }
            return editors[slug+'_'+id];
        };

        self.editor_exists = function( id, slug ){
            return typeof editors[slug+'_'+id] !== 'undefined' && editors[slug+'_'+id] !== null;
        };

        self.get_editor = function( id, slug ){
            return editors[slug+'_'+id];
        };

        self.get_editors = function(){
            return editors;
        };

        self.getEditorValue = function( id, slug ) {
            var value = '';
            try{
                value = self.get_editor( id, slug ).getEditorValue();
            } catch( e ){
                console.log(e.message);
                value =  '';
            }
            return value;
        };

        self.someHasChanged = function(){
            return _.some(self.get_editors(), function(editor){
                    return editor.hasChanged() === true;
            });
        };

        self.resetEditors = function(){
            _.map(self.get_editors(), function(v){
                v.editorResetValue();
            });
        };

    self.destroy = function(){
        _.map(editors, function(v){
            v.destroy();
        });
        editors = {};
    };
};
