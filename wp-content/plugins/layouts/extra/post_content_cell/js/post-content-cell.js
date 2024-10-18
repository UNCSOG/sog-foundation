// content-template-cell.js
jQuery(function($){
    DDLayout.content_template_cell = new DDLayout.PostContentCell($);
});

// Fallback since this is in common in icl_editor_addon_plugin.js
// But we can not know whether the common version loaded contains it or not
// @todo keep for a couple of join releases, make this script dependant on that one and then remove

var WPV_Toolset = WPV_Toolset  || {};
if ( typeof WPV_Toolset.CodeMirror_instance === "undefined" ) {
    WPV_Toolset.CodeMirror_instance = [];
}

if ( typeof WPV_Toolset.add_qt_editor_buttons !== 'function' ) {
    WPV_Toolset.add_qt_editor_buttons = function( qt_instance, editor_instance ) {
        var activeUrlEditor, html;
        QTags._buttonsInit();
        WPV_Toolset.CodeMirror_instance[qt_instance.id] = editor_instance;

        for ( var button_name in qt_instance.theButtons ) {
            if ( qt_instance.theButtons.hasOwnProperty( button_name ) ) {
                qt_instance.theButtons[button_name].old_callback = qt_instance.theButtons[button_name].callback;
                if ( qt_instance.theButtons[button_name].id == 'img' ){
                    qt_instance.theButtons[button_name].callback = function( element, canvas, ed ) {
                        var t = this,
                            id = jQuery( canvas ).attr( 'id' ),
                            selection = WPV_Toolset.CodeMirror_instance[id].getSelection(),
                            e = "http://",
                            g = prompt( quicktagsL10n.enterImageURL, e ),
                            f = prompt( quicktagsL10n.enterImageDescription, "" );
                        t.tagStart = '<img src="'+g+'" alt="'+f+'" />';
                        selection = t.tagStart;
                        t.closeTag( element, ed );
                        WPV_Toolset.CodeMirror_instance[id].replaceSelection( selection, 'end' );
                        WPV_Toolset.CodeMirror_instance[id].focus();
                    }
                } else if ( qt_instance.theButtons[button_name].id == 'close' ) {

                } else if ( qt_instance.theButtons[button_name].id == 'link' ) {
                    var t = this;
                    qt_instance.theButtons[button_name].callback =
                        function ( b, c, d, e ) {
                            activeUrlEditor = c;
                            var f,g=this;
                            return "undefined" != typeof wpLink ?void wpLink.open(d.id) : (e||(e="http://"), void(g.isOpen(d)===!1 ? (f=prompt(quicktagsL10n.enterURL,e), f && (g.tagStart='<a href="'+f+'">', a.TagButton.prototype.callback.call(g,b,c,d))) : a.TagButton.prototype.callback.call(g,b,c,d)))
                        };
                    jQuery( '#wp-link-submit' ).off();
					jQuery( '#wp-link-submit' ).on( 'click', function( event ) {
						event.preventDefault();
						if ( wpLink.isMCE() ) {
							wpLink.mceUpdate();
						} else {
							var id = jQuery( activeUrlEditor ).attr('id'),
							selection = WPV_Toolset.CodeMirror_instance[id].getSelection(),
							inputs = {},
							attrs, text, title, html;
							inputs.wrap = jQuery('#wp-link-wrap');
							inputs.backdrop = jQuery( '#wp-link-backdrop' );
							if ( jQuery( '#link-target-checkbox' ).length > 0 ) {
								// Backwards compatibility - before WordPress 4.2
								inputs.text = jQuery( '#link-title-field' );
								attrs = wpLink.getAttrs();
								text = inputs.text.val();
								if ( ! attrs.href ) {
									return;
								}
								// Build HTML
								html = '<a href="' + attrs.href + '"';
								if ( attrs.target ) {
									html += ' target="' + attrs.target + '"';
								}
								if ( text ) {
									title = text.replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
									html += ' title="' + title + '"';
								}
								html += '>';
								html += text || selection;
								html += '</a>';
								t.tagStart = html;
								selection = t.tagStart;
							} else {
								// WordPress 4.2+
								inputs.text = jQuery( '#wp-link-text' );
								attrs = wpLink.getAttrs();
								text = inputs.text.val();
								if ( ! attrs.href ) {
									return;
								}
								// Build HTML
								html = '<a href="' + attrs.href + '"';
								if ( attrs.target ) {
									html += ' target="' + attrs.target + '"';
								}
								html += '>';
								html += text || selection;
								html += '</a>';
								selection = html;
							}
							jQuery( document.body ).removeClass( 'modal-open' );
							inputs.backdrop.hide();
							inputs.wrap.hide();
							jQuery( document ).trigger( 'wplink-close', inputs.wrap );
							WPV_Toolset.CodeMirror_instance[id].replaceSelection( selection, 'end' );
							WPV_Toolset.CodeMirror_instance[id].focus();
							return false;
						}
					});
                } else {
                    qt_instance.theButtons[button_name].callback = function( element, canvas, ed ) {
                        var id = jQuery( canvas ).attr( 'id' ),
                            t = this,
                            selection = WPV_Toolset.CodeMirror_instance[id].getSelection();
                        if ( selection.length > 0 ) {
                            if ( !t.tagEnd ) {
                                selection = selection + t.tagStart;
                            } else {
                                selection = t.tagStart + selection + t.tagEnd;
                            }
                        } else {
                            if ( !t.tagEnd ) {
                                selection = t.tagStart;
                            } else if ( t.isOpen( ed ) === false ) {
                                selection = t.tagStart;
                                t.openTag( element, ed );
                            } else {
                                selection = t.tagEnd;
                                t.closeTag( element, ed );
                            }
                        }
                        WPV_Toolset.CodeMirror_instance[id].replaceSelection(selection, 'end');
                        WPV_Toolset.CodeMirror_instance[id].focus();
                    }
                }
            }
        }
    }
}

// END of fallback

DDLayout.PostContentCell = function($)
{
    var self = this;

    self.init = function() {

        self._ct_editor = null;

        self._ct_code_mirror = null;

        self._preview_cache = {};


        self._preview = {};

        self._cell_content = null;

        self._wpv_inline_editor_qt = null;

        self._content_template_created = null;

        jQuery('.js-ct-name').on('click', self._switch_to_edit_ct_name);

        jQuery('.js-create-new-ct').on('click', self._create_new_ct);

        jQuery('.js-ct-edit-name').on('blur', self._end_ct_name_edit);

        jQuery('.js-load-different-ct').on('click', self._switch_to_select_different_ct)

        jQuery('#post-content-view-template').on('change', self._handle_ct_change);

        // Handle the dialog open. (Common to post-content cell which is now obsolete )

        jQuery(document).on('cell-post-content.dialog-open cell-content-template.dialog-open', function(e, content, dialog) {

            self._dialog = dialog;

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
            if (select_post_type != jQuery('#ddl-default-edit #ddl-layout-selected_post').data('post-type')) {
                self._cell_content = content;
                jQuery('#ddl-default-edit .js-ddl-post-content-post-type').trigger('change');
            }

            self.adjust_specific_page_state();

        });
        jQuery(document).on('cell-post-content.dialog-close cell-content-template.dialog-close', function(e) {
            jQuery('#ddl-default-edit #ddl-layout-selected_post').select2('destroy');

        });


        // Handle the dialog open.

        jQuery(document).on('cell-content-template.dialog-open', function(e, content, dialog) {

            self._dialog = dialog;
            self._content_template_created = Array();
            DDLayout.types_views_popup_manager.start();

            self._original_ct_name = '';
            self._original_ct_value = ''

            jQuery('.js-ct-edit').hide();

            //jQuery('.js-post-content-ct').show();
            //jQuery('#post-content-view-template').trigger('change');

            if (dialog.is_new_cell()) {
                self._create_new_ct();
            } else {
                // Show the current Content Template
                jQuery('#post-content-view-template').trigger('change');
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

        });


        jQuery(document).on('cell-content-template.get-content-from-dialog', function(e, content, dialog) {
            self._save_ct(content);
        });


        $( document ).on( 'click', '.js-wpv-editor-instructions-toggle', function() {
            var thiz = $( this );
            self.show_hide_formatting_help( thiz );
        });

    };

    self._handle_post_type_change = function() {
        var data = {
            post_type : jQuery(this).val(),
            action : 'get_posts_for_post_content',
            nonce : jQuery(this).data('nonce')
        };

        var spinnerContainer = jQuery('<div class="spinner ajax-loader">').insertAfter(jQuery(this)).show();
        jQuery('#ddl-default-edit #ddl-layout-selected_post').hide();
        jQuery('#ddl-default-edit #ddl-layout-selected_post').select2('destroy');

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
                spinnerContainer.remove();
                self._initialize_post_selector();
                jQuery('#ddl-default-edit #ddl-layout-selected_post').fadeIn(200);
                self._handle_post_select_change();
            }
        });
    };

    self.adjust_specific_page_state = function () {
        if (self.get_display_mode() == 'current_page') {
            jQuery('#ddl-default-edit #js-post-content-specific-page').hide();

            var disable_save = false;
            if (self._dialog.get_cell_type() == 'cell-content-template') {
                disable_save = !DDLayout.content_template_cell.is_save_ok();
            }
            self._dialog.disable_save_button(disable_save);
        } else {
            jQuery('#ddl-default-edit #js-post-content-specific-page').show();

            self._handle_post_select_change();
        }
    };

    self._initialize_post_selector = function () {
        jQuery('#ddl-default-edit #ddl-layout-selected_post').off('change');
        jQuery('#ddl-default-edit #ddl-layout-selected_post').on('change', self._handle_post_select_change);

        jQuery('#ddl-default-edit #ddl-layout-selected_post').select2({
            'width' : 'resolve'
        }).css('visibility', 'hidden');
    }


    self._handle_post_select_change = function () {
        if (self.get_display_mode() == 'this_page') {
            if( self._dialog.get_cell_type() == 'cell-content-template' )
            {
                self._dialog.disable_save_button(self.get_selected_post() == '' ||
                !DDLayout.content_template_cell.is_save_ok());
            }
            else
            {
                self._dialog.disable_save_button(self.get_selected_post() == '' );
            }
        }
    }

    self._select_post = function (selected_post) {
        var select = jQuery('#ddl-default-edit #ddl-layout-selected_post');
        select.val(selected_post);
        if (select.val() != selected_post) {
            select.val('');
        }
    }


    self._handle_ct_change = function() {
        if (jQuery(this).val() == 'None') {
            self._dialog.disable_save_button(true);
        } else {
            self._dialog.disable_save_button(!self._is_post_selected_ok());

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
        if (ddl_views_1_6_available && self._ct_editor) {

            var ct_title = jQuery('.js-ct-edit-name').val();
            var ct_value = self._ct_code_mirror.getValue();

            self._preview_cache[content.ddl_view_template_id] = ct_value;
            if ( ct_title == ''){
                ct_title = self._original_ct_name;
                //return false;
            }
            if (self._original_ct_name != ct_title || self._original_ct_value != ct_value) {

                var data = {
                    action : 'wpv_ct_update_inline',
                    ct_value : ct_value,
                    ct_id : self._ct_editor,
                    ct_title : ct_title,
                    wpnonce : $('#wpv_inline_content_template').attr('value')
                };
                $.post(ajaxurl, data, function(response) {

                    if (self._original_ct_name != ct_title) {
                        // we need to refresh the ct drop down.
                        self._refresh_ct_dropdown(0);
                    }


                });
            }
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
        self._ct_value = '';
        if (self._ct_editor) {
            self._ct_value = self._ct_code_mirror.getValue();
            icl_editor.codemirror('wpv-ct-inline-editor-' + self._ct_editor, false);
            self._ct_editor = null;
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
                    preview += '<script type="text/javascript">jQuery(".' + div_place_holder +'").closest(".cell-content").removeClass("cell-preview-fadeout")</script>';
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
                        success: function(data) {
                            //cache view id data
                            self._preview_cache[content.ddl_view_template_id] = data.content;

                            var local_copy = jQuery.jStorage.get('content-template-' + content.ddl_view_template_id, '');

                            if (local_copy != data.content) {

                                jQuery.jStorage.set('content-template-' + content.ddl_view_template_id, data.content);

                                jQuery(div_place_holder).html(data.content);

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

        var fields_button = jQuery('#ddl-default-edit .js-code-editor-toolbar-button-v-icon');

        var message = DDLayout_settings.DDL_JS.strings.new_ct_message;

        fields_button.pointer({
            pointerClass: 'wp-toolset-pointer wp-toolset-layouts-pointer ddl-ct-helper-pointer',
            content: '<h3>' + DDLayout_settings.DDL_JS.strings.new_ct_message_title + '</h3><p>' + message + '</p>',
            position: {
                edge: 'bottom',
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
            },



        }).pointer('open');
    }

    self._show_ct_preview = function (id, name) {
        self._fetch_ct_and_show_editor(id, name, false, true);
    }

    self._fetch_ct_and_show_editor = function (id, name, focus_on_name, preview_mode) {

        self._dialog.disable_cancel_button(true);

        data = {
            action : preview_mode ? 'ddl_ct_loader_inline_preview' : 'wpv_ct_loader_inline',
            id : id,
            include_instructions : 'layouts_content_cell',
            wpnonce : $('#wpv-ct-inline-edit').attr('value')
        };

        $.post(ajaxurl, data, function(response) {

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
                self._ct_code_mirror = icl_editor.codemirror('wpv-ct-inline-editor-'+id, true);
                self._wpv_inline_editor_qt = quicktags( { id: "wpv-ct-inline-editor-"+id, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
                WPV_Toolset.add_qt_editor_buttons( self._wpv_inline_editor_qt, self._ct_code_mirror );
            }

            // Hide "CRED forms" button (it doesn't work at the moment)
            jQuery('.cred-form-shortcode-button2').hide();

            jQuery('.js-ct-edit-name').hide();
            jQuery('.js-ct-name').html(name);
            jQuery('.js-ct-edit-name').val(name);
            jQuery('.js-ct-edit').show();

            jQuery('.js-ct-loading').remove();

            self._original_ct_name = name;
            self._original_ct_value = self._ct_code_mirror.getValue();

            self._ct_code_mirror.refresh();

            DDLayout.types_views_popup_manager.set_position_and_target(
                jQuery('#ddl-default-edit .js-code-editor-toolbar-button-v-icon'),
                'wpv-ct-inline-editor-'+id);

            if (focus_on_name) {
                self._switch_to_edit_ct_name();
            }

            jQuery(document).trigger('ddl-ct-editor-loaded');

        });


    }

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
        jQuery('.js-ct-edit-name').val(jQuery('.js-ct-name').html());
        jQuery('.js-ct-edit-name').show().focus();
    }

    self._end_ct_name_edit = function () {
        jQuery('.js-ct-edit-name').hide();
        jQuery('.js-ct-name').html(jQuery('.js-ct-edit-name').val());
        jQuery('.js-ct-editing').show();
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
