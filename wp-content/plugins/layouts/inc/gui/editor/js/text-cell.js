// text-cell.js
DDLayout.TextCell = function($)
{
    var self = this;


    self.init = function() {

        self.editor = self.editor || {};
        jQuery(document).on('cell-text.dialog-open', self._dialog_open);
        jQuery(document).on('cell-text.dialog-close', self._dialog_close);
        jQuery(document).on('cell-text.get-content-from-dialog', self._get_content_from_dialog);
        Toolset.hooks.addFilter('ddl-preferred-editor', self.get_preferred_editor);
    };

    self._get_content_from_dialog = function (event, content, dialog) {
        content['visual_mode'] = 'tinymce' == self.editor.current;
    };

    self.get_preferred_editor = function(){
        return DDLayout.text_cell.editor.get_preferred();
    };

    self.cred_button_fixes = function(){
        var $icon = jQuery('i.icon-cred.ont-icon-22');

        $icon.removeClass('icon-cred').removeClass('ont-icon-22').addClass('icon-cred-logo ont-icon-18');
    };

    self._dialog_open = function (event, content, dialog) {

        self.cred_button_fixes();

        // disable full screen save.
        jQuery('#wp-fullscreen-save').hide();

        Toolset.hooks.doAction( 'toolset_text_editor_TinyMCE_init', 'visual-editor-html-editor' );

        // (Re)Initialize CodeMirror
        if( typeof self.editor.codemirror == 'undefined' ) {
            self.editor.codemirror = icl_editor.codemirror( 'visual-editor-html-editor', true );
            self._visual_editor_html_editor_qt = quicktags( { id: 'visual-editor-html-editor', buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
            // The object is called WPV_Toolset. No need to worry. @see icl_editor_addon_plugin.js
            WPV_Toolset.add_qt_editor_buttons( self._visual_editor_html_editor_qt, self.editor.codemirror );
        } else {
            // Avoid loading previous content through Ctrl+Z
            self.editor.codemirror.clearHistory();
        }

        Toolset.hooks.doAction( 'toolset_text_editor_CodeMirror_init', 'visual-editor-html-editor' );

        // Remove TinyMCE editing mode tabs
        jQuery( '#wp-celltexteditor-wrap #celltexteditor-html' ).hide();
        jQuery( '#wp-celltexteditor-wrap #celltexteditor-tmce' ).css( 'visibility', 'hidden' ); //Avoid cross-browser incompats

        // Configure editor switching buttons
        jQuery( '.js-visual-editor-toggle' ).off();

        jQuery('.js-visual-editor-toggle').click( function() {
            if (!jQuery(this).hasClass('active')) {
                var warning_text = DDLayout_settings.DDL_JS.strings.switch_editor_warning_message;

                if (window.confirm(warning_text)) {
                    var editor_type = jQuery(this).attr('data-editor');
                    if (editor_type == 'tinymce') {
                        self.editor.switch_to_tinymce();
                        self.editor.update_preferred('tinymce');
                    } else {
                        self.editor.switch_to_codemirror();
                        self.editor.update_preferred('codemirror');
                        self.editor.codemirror.setOption("scrollbarStyle", 'native');
                        setTimeout(function () {
                            var info = self.editor.codemirror.getScrollInfo();
                            self.editor.codemirror.scrollTo(info.left, info.height);
                            self.editor.codemirror.scrollTo(info.left, info.top);
                        }, 400);
                    }

                    jQuery('.js-visual-editor-toggle.active').removeClass('active');
                    jQuery(this).addClass('active');
                }
            } else {
                return false;
            }
        } );

        var visual_mode = content.visual_mode;

        if (typeof visual_mode  == 'undefined'){
            // Default editing mode (per user) or TinyMCE (first time)
            visual_mode = self.editor.get_preferred() == 'tinymce';
        }
        jQuery( '.js-visual-editor-toggle' ).show();
        self.editor.current = '';
        if (visual_mode) {
            self.editor.switch_to_tinymce();
            jQuery("#celltexteditor-tmce").trigger("click");
            jQuery( '.js-visual-editor-toggle[data-editor="codemirror"]').removeClass('active');
            jQuery( '.js-visual-editor-toggle[data-editor="tinymce"]' ).addClass('active');
        } else {
            jQuery( '#celltexteditor' ).val( content.content );
            self.editor.switch_to_codemirror();
            jQuery( '.js-visual-editor-toggle[data-editor="tinymce"]' ).removeClass('active');
            jQuery( '.js-visual-editor-toggle[data-editor="codemirror"]' ).addClass('active');
        }


        // We need special handling to install View forms as this uses colorbox.
        self._views_insert_form_function = window.wpv_insert_view_form_popup;
        window.wpv_insert_view_form_popup = self._wpv_insert_form_shortcode;

        // Add special handling for Types popup.
        self._wpcfFieldsEditorCallback_function = window.wpcfFieldsEditorCallback;
        window.wpcfFieldsEditorCallback = self._wpcfFieldsEditorCallback;

        //Check for Views and Types shortcodes
       // self.editorTimer = setInterval(self.check_for_shortcodes, 1000);
        if (dialog.is_new_cell()) {
            jQuery('[name="ddl-layout-responsive_images"]').prop('checked', true);
        }

    }

    self._wpv_insert_form_shortcode = function (id) {
        self._override_jquery_colorbox_functions();

        self._views_insert_form_function(id);
    }

    self._wpcfFieldsEditorCallback = function (fieldID , metaType, postID) {
        self._override_jquery_colorbox_functions();

        self._wpcfFieldsEditorCallback_function (fieldID , metaType, postID);
    }

    self._colorbox = function(params) {

        if (params['iframe']) {

            self._create_color_box_elements(true);

            jQuery('#ddl-colorbox-2').html('<iframe title="Types fields" id="ddl-types-popup" src="' + params['href'] + '" width="' + params['width'] + '"></iframe>');

            self._position_popup(params);

        } else if (params['href']){

            jQuery.ajax({
                type:'post',
                url:params['href'],
                success:function(response){

                    self._create_color_box_elements(false);

                    jQuery('#ddl-colorbox-2').html(response);

                    jQuery('#ddl-colorbox-2 .js-dialog-close').on('click', self._colorbox_close)

                    self._position_popup(params);

                    if (params['onComplete']) {
                        params['onComplete']();
                    }
                }
            });
        }
    };

    self._create_color_box_elements = function (add_shadow) {
        jQuery('body').append('<div id="ddl-colorbox-2-overlay" class="ddl-colorbox-2-overlay">');
        jQuery('body').append('<div id="ddl-colorbox-2" class="ddl-colorbox-2">');

        var z_index = parseInt(jQuery('#colorbox').css('z-index')) + 1;
        jQuery('#ddl-colorbox-2-overlay').css({'z-index' : z_index});
        jQuery('#ddl-colorbox-2').css({'z-index' : z_index});

        if (add_shadow) {
            jQuery('#ddl-colorbox-2').css({	'box-shadow': '0 0 15px rgba(0, 0, 0, 0.4)' });
        }

        jQuery('#ddl-colorbox-2-overlay').on('click', function (event) {
            if (add_shadow) {
                jQuery('#ddl-colorbox-2').css({	'box-shadow': '0 0 15px #21759B' });
                _.delay(function () {jQuery('#ddl-colorbox-2').css({	'box-shadow': '0 0 15px rgba(0, 0, 0, 0.4)' });} , 500);
            } else {
                jQuery('#ddl-colorbox-2 .wpv-dialog').css({	'box-shadow': '0 0 15px #21759B' });
                _.delay(function () {jQuery('#ddl-colorbox-2 .wpv-dialog').css({	'box-shadow': '0 0 15px rgba(0, 0, 0, 0.4)' });} , 500);
            }
        })
    }

    self._position_popup = function (params) {
        var offset = jQuery('#ddl-default-edit .js-wpv-shortcode-post-icon-wpv-views').offset();

        jQuery('#ddl-colorbox-2').css(
            {top : "7%",
            left : "29%",
            width: params['width']});
    }

    self._insert_content = function (content) {
        window.wpcfActiveEditor = 'celltexteditor';
        self._icl_editor_insert_function(content);
    };

    self._colorbox_close = function () {
        self._close_popup();
        self._restore_overrides();
    }

    self._restore_overrides = function() {

        jQuery.colorbox = self._jquery_colorbox_function;
        jQuery.colorbox.close = self._jquery_colorbox_close_function;
        jQuery.colorbox.resize = self._jquery_colorbox_resize_function;

       // icl_editor.insert = self._icl_editor_insert_function;
    };

    self._close_popup = function () {
        jQuery('#ddl-colorbox-2').remove();
        jQuery('#ddl-colorbox-2-overlay').remove();
    };

    self._dialog_close = function (event) {
        //Stop checking Views/Types shortcodes
        clearInterval(self.editorTimer);
        // enable full screen save.
        jQuery('#wp-fullscreen-save').show();

        window.wpv_insert_view_form_popup = self._views_insert_form_function;
        window.wpcfFieldsEditorCallback = self._wpcfFieldsEditorCallback_function;

        // let's prevent DDLayout.DefaultDialog._get_content_from_dialog switch to enter
        // tinyMCE all the time and bother other cells
        delete DDLayout.text_cell.editor.current;
        _.without(tinyMCE.editors, 'celltexteditor');

    };

    self._override_jquery_colorbox_functions = function () {

        // We're overriding colorbox so that it calls member functions
        // here instead. We then create our own colorbox.

        self._jquery_colorbox_close_function = jQuery.colorbox.close;
        self._jquery_colorbox_resize_function = jQuery.colorbox.resize;
        self._jquery_colorbox_function = jQuery.colorbox;

        jQuery.colorbox = self._colorbox;
        jQuery.colorbox.close = self._colorbox_close;
        jQuery.colorbox.resize = self._colorbox_resize;

        self._icl_editor_insert_function = icl_editor.insert;

        if( self.editor.current !== 'codemirror' ){
        //    icl_editor.insert = self._insert_content;
        }
    };

    self._colorbox_resize = function (params) {
        if (params['innerHeight']) {
            jQuery('#ddl-colorbox-2 #ddl-types-popup').each (function () {
                jQuery(this).css({height : params['innerHeight']});
            });
        }
    };

    self.check_for_shortcodes = function () {
        var editor_content = self.editor.get_content();

        //Check for views
        var check_views = /\[wpv-view/ig.exec(editor_content);
        jQuery(".js-visual-editor-views-shortcode-notification").html('');
        //Remove views from content variable
        if( editor_content ){
            editor_content = editor_content.replace(/\[wpv-view/g,"");
        }

        if ( check_views ){
            var template = jQuery("#js-info-box").html();
            jQuery(".js-visual-editor-views-shortcode-notification").html(
                '<div class="info-box info-box-warning js-info-box"><p>'+ jQuery(".js-visual-editor-views-shortcode-notification").data('view') +'</p></div>'
            );
        }

        //Check for Views and Types shortcodes
        var check_content_templates = /\[(wpv-[^ ]+|types )/ig.exec(editor_content);
        if ( check_content_templates ){
            var template = jQuery("#js-info-box").html();
            jQuery(".js-visual-editor-views-shortcode-notification").html(
                jQuery( ".js-visual-editor-views-shortcode-notification").html() +
                '<div class="info-box info-box-warning js-info-box"><p>'+ jQuery(".js-visual-editor-views-shortcode-notification").data('content-template') +'</p></div>'
            );
        }

        //Check for Cred shortcodes
        var check_content_templates = /\[cred_form[^ ]+/ig.exec(editor_content);
        if ( check_content_templates ){
            var template = jQuery("#js-info-box").html();
            jQuery(".js-visual-editor-views-shortcode-notification").html(
                jQuery( ".js-visual-editor-views-shortcode-notification").html() +
                '<div class="info-box info-box-warning js-info-box"><p>'+ jQuery(".js-visual-editor-views-shortcode-notification").data('cred') +'</p></div>'
            );
        }
        jQuery('.js-visual-editor-views-shortcode-notification .js-remove-info-box').remove();
    };

    self.editor = {
        current : null,

        switch_to_tinymce : function() {
            jQuery( '#celltexteditor-tmce' ).trigger( 'click' );
            if( typeof self.editor.codemirror == 'undefined' ) {
                return;
            }

            // Avoid re-setting
            if ( self.editor.current == 'tinymce' ) {
                return;
            }

            var editor_content = self.editor.get_content();

            if( 'celltexteditor' in tinyMCE.editors ) {
                var tinymce_editor = tinyMCE.get( 'celltexteditor' );
                if( tinymce_editor.isHidden() ) {
                    jQuery( '#celltexteditor' ).val( editor_content );
                } else {
                    _.delay(function(){
                        tinymce.activeEditor.setContent( window.switchEditors.wpautop( window.switchEditors.pre_wpautop( editor_content ) ) );
                    }, 500);
                }

                tinymce_editor.on('FullscreenStateChanged', function( event ){
                        if( event.state === true ){
                            jQuery('.ddl-markup-controls').hide();
                        } else {
                            jQuery('.ddl-markup-controls').show();
                        }
                });
            }

            jQuery('#js-visual-editor-codemirror').hide();
            self.editor.current = 'tinymce';
            window.wpcfActiveEditor = 'celltexteditor';
            jQuery('#js-visual-editor-tinymce').show(400, function(event){
                Toolset.hooks.removeFilter( 'toolset_get_icl_target' );
                Toolset.hooks.addFilter('toolset_get_icl_target', function(){
                    return jQuery( '#celltexteditor' );
                });
            });
            // WordPress editor.js adds 14px each time TinyMCE visual editor is shown
            // @see wpddl.cell_text.class.php for 300px height
            jQuery( '#celltexteditor_ifr' ).css( 'height', '300px' );
        },

        switch_to_codemirror : function() {
            if( typeof self.editor.codemirror == 'undefined' ) {
                return;
            }

            // Avoid re-setting
            if ( self.editor.current == 'codemirror' ) {
                return;
            }

            var apply_auto_p = function( $_ ) { return jQuery( '#ddl-layout-disable_auto_p' ).is( ':checked' ) ? $_ : window.switchEditors.wpautop( window.switchEditors.pre_wpautop( $_ ) ); }
            var the_content = self.editor.get_content();
            var editor_content = the_content ? apply_auto_p( the_content ) : '';

            jQuery('#js-visual-editor-tinymce').hide();
            self.editor.current = 'codemirror';
            window.wpcfActiveEditor = 'visual-editor-html-editor';
            jQuery('#js-visual-editor-codemirror').show(400, function(event){
                Toolset.hooks.removeFilter( 'toolset_get_icl_target' );
                Toolset.hooks.addFilter('toolset_get_icl_target', function(){
                    return jQuery("#"+window.wpcfActiveEditor);
                });
            });
            self.editor.codemirror.getDoc().setValue( editor_content );
        },

        get_content : function() {
            var content = '';
            if( self.editor.current == 'tinymce' ) {

                if( 'celltexteditor' in tinyMCE.editors ) {
                    var tinymce_editor = tinyMCE.get( 'celltexteditor' );
                    if( ! tinymce_editor.isHidden() ) {
                        content = tinymce_editor.save();
                    }
                } else{
                    content = jQuery( '#celltexteditor' ).val();
                }

            } else if( self.editor.current == 'codemirror' ) {
                content = self.editor.codemirror.getDoc().getValue();

            } else {
                content = jQuery( '#celltexteditor' ).val();
            }

            content = _.isString( content ) ? content.trim() : '';
            content = content == '<br />' || content == '&nbsp;' ? '' : content;
            return  content;
        },

        set_content : function( content ) {
            //console.log('set content', content)
            if( self.editor.current == 'tinymce' ) {

            if( 'celltexteditor' in tinyMCE.editors ) {
                var tinymce_editor = tinyMCE.get( 'celltexteditor' );
                if( tinymce_editor.isHidden() ) {
                    jQuery( '#celltexteditor' ).val( window.switchEditors.wpautop( window.switchEditors.pre_wpautop( content ) ) );
                } else {
                    tinymce_editor.setContent( window.switchEditors.wpautop( window.switchEditors.pre_wpautop( content ) ) );
                }
            }

            } else if( self.editor.current == 'codemirror' ) {

                self.editor.codemirror.getDoc().setValue( content );

            } else {

               jQuery( '#celltexteditor' ).val( content );

            }
        },

        _preferred : null,

        _preferred_loaded : false,

        get_preferred : function() {
            // current preferred editor
            if( self.editor._preferred_loaded || /^(codemirror|tinymce)$/.test( self.editor._preferred ) ) {
                return self.editor._preferred;
            } else {
                jQuery( '#preferred_editor' ).each( function(i, e) {
                    self.editor._preferred_loaded = true;
                    self.editor.update_preferred( jQuery(this).val() );
                } );
                return self.editor.get_preferred();
            }

            // tinyMCE by default
            return 'tinymce';
        },

        update_preferred : function( name ) {
            if( /^(codemirror|tinymce)$/.test( name ) ) {
                self.editor._preferred = name;
                return true;
            }

            // TinyMCE is always the preferred option
            self.editor._preferred = 'tinymce';
            return false;
        }
    };

    self.init();
};

jQuery(document).on('DLLayout.admin.ready', function($){
    DDLayout.text_cell = new DDLayout.TextCell($);
});
