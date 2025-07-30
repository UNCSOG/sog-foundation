// This code handles the CRED Post Form cell when CRED is enabled

var DDLayout = DDLayout || {};

DDLayout.CredCell = function($)
{
    var self = this,
        _dialog = null,
        messages_manager = null,
        _cred_forms_created = null;

    var _init = function( ) {
        $(document).on('cred-cell.dialog-open', _dialog_open);
        $(document).on('cred-cell.dialog-close', _dialog_close);
    };

    var _dialog_open = function(event, content, dialog) {

        _cred_forms_created = Array();

        if ( $('.js-ddl-cred-not-activated').length ) {
            dialog.disable_save_button(true);
            return;
        }

        $('.js-cred-form-create-error').hide();

        _dialog = dialog;

        messages_manager = new DDLayout.CredCellMessages();
        messages_manager.append_message( $('#ddl-default-edit .js-cred-new-mode option:selected').val() );

        $('#ddl-default-edit .js-ddl-edit-cred-link').off('click');
        $('#ddl-default-edit .js-ddl-edit-cred-link').on('click', _edit_cred_form);


        $('#ddl-default-edit .js-ddl-create-cred-form').off('click');
        $('#ddl-default-edit .js-ddl-create-cred-form').on('click', _create_and_open_cred_form);

        _create_create_and_edit_form_button();

        // Make sure that the cred form has been selected (it might have been deleted)

        if (content.ddl_layout_cred_id != '') {
            if (content.ddl_layout_cred_id != $('#ddl-default-edit .js-ddl-cred-select').val()) {
                content.ddl_layout_cred_id = '';
                $('#ddl-default-edit .js-ddl-cred-select').val('')
            }
        }


        if (!_is_cred_embedded() && content.ddl_layout_cred_id == '') {
            _switch_to_new_form_mode();
            _enable_edit_button(true);
        } else {
            _switch_to_existing_form_mode();
            _enable_edit_button(true);
        }

        $('#ddl-default-edit .js-ddl-cred-form-create').off('click');
        $('#ddl-default-edit .js-ddl-cred-form-create').on('click', _switch_to_new_form_mode);

        $('#ddl-default-edit .js-ddl-cred-form-existing').off('click');
        $('#ddl-default-edit .js-ddl-cred-form-existing').on('click', _switch_to_existing_form_mode);

        $('#ddl-default-edit .js-ddl-cred-select').off('change');
        $('#ddl-default-edit .js-ddl-cred-select').on('change', _handle_form_select_change);

        $('#ddl-default-edit .js-cred-new-mode').off('change');
        $('#ddl-default-edit .js-cred-new-mode').on('change', _set_cell_name);

        $('#ddl-default-edit .js-cred-post-type').off('change');
        $('#ddl-default-edit .js-cred-post-type').on('change', _set_cell_name);

        if (!_dialog.is_new_cell()) {
            if (_does_cred_for_exist(content)) {
                if (!_is_cred_embedded()) {
                    _edit_cred_form();
                }
            } else {
                jQuery('.js-default-dialog-content').prepend('<div class="js-cred-form-error toolset alert toolset-alert-error"><p class="ddl_error_message_padding">' +
                    jQuery('#ddl-cred-preview-cred-not-found').html() +
                    '</p></div>');
            }
        }

        _track_name_change();

    };


    var _track_name_change = function(){
        var $select = $('select[name="ddl-layout-ddl_layout_cred_id"]'),
            $text = $('input[name="ddl-default-edit-cell-name"]');

        $select.on('change', function(event){
            $text.val( $(this).find('option:selected').text() );
        });
    };

    var _is_cred_embedded = function () {
        return $('.js-ddl-cred-form-create').length == 0;
    };

    var _create_create_and_edit_form_button = function(){
        var after = jQuery('.js-dialog-edit-save')[0];

        $('.js-ddl-create-cred-user-form').hide();
        $('.js-ddl-edit-cred-user-link').hide();
        $('.js-ddl-create-cred-relationship-form').hide();
        $('.js-ddl-edit-cred-relationship-link').hide();

        var button_create = $('#ddl-default-edit .js-ddl-create-cred-form');
        $( button_create).insertAfter( $(after) );

        var button_edit = $('#ddl-default-edit .js-ddl-edit-cred-link');
        $(button_edit).insertAfter( $(after) );
    };

    var _dialog_close = function(event, content, dialog) {

        $('.js-cred-form-error').remove();

        $('#ddl-default-edit .js-ddl-edit-cred-link').insertAfter('.js-ddl-select-existing-cred');
        $('#ddl-default-edit .js-ddl-create-cred-form').insertAfter('.js-ddl-select-existing-cred');

        // we should clean up any CRED forms we created.
        if (_cred_forms_created.length) {
            for (var i = 0; i < _cred_forms_created.length; i++) {
                if (content.ddl_layout_cred_id == _cred_forms_created[i]) {
                    // delete from created list
                    _cred_forms_created.splice(i, 1);
                    break;
                }
            }
        }
        if (_cred_forms_created.length) {

            // remove from the select control

            for (var i = 0; i < _cred_forms_created.length; i++) {
                $('[name="ddl-layout-ddl_layout_cred_id"] option').each( function () {
                    if ($(this).val() == _cred_forms_created[i]) {
                        $(this).remove();
                    }
                })
            }

            // delete in the DB
            var data = {
                action : 'ddl_delete_cred_forms',
                wpnonce : $('#ddl_layout_cred_nonce').attr('value'),
                forms : _cred_forms_created
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
    };

    var _set_cell_name = function() {
        var mode = $('#ddl-default-edit .js-cred-new-mode option:checked').html();
        var post_type = $('#ddl-default-edit .js-cred-post-type option:checked').html();

        $('#ddl-default-edit-cell-name').val(mode + ' - ' + post_type);
        messages_manager.append_message( $('#ddl-default-edit .js-cred-new-mode').find('option:selected').val() );
    };

    var _edit_cred_form = function() {
        _cred_open(false);
    };

    var _cred_open = function(new_form) {

        if (typeof DDLayout.cred_in_iframe == 'undefined') {
            DDLayout.cred_in_iframe = new DDLayout.CredInIfame($);
        }

        DDLayout.cred_in_iframe.open_cred_in_iframe( $('#ddl-default-edit [name="ddl-layout-ddl_layout_cred_id"] option:checked').val(),
            _dialog.get_cell_type(),
            _dialog.is_new_cell(),
            new_form,
            _dialog);

    };

    var _switch_to_new_form_mode = function() {
        $('#ddl-default-edit .js-ddl-cred-form-create').prop('checked', true);
        $('#ddl-default-edit .js-ddl-cred-form-existing').prop('checked', false);

        $('#ddl-default-edit .js-ddl-newcred').show();
        $('#ddl-default-edit .js-ddl-select-existing-cred').hide();

        $('#ddl-default-edit .js-ddl-edit-cred-link').hide();
        $('#ddl-default-edit .js-ddl-create-cred-form').show();

        _dialog.hide_save_button(true);

        _set_cell_name();
    };

    var _switch_to_existing_form_mode = function() {
        $('#ddl-default-edit .js-ddl-cred-form-create').prop('checked', false);
        $('#ddl-default-edit .js-ddl-cred-form-existing').prop('checked', true);

        $('#ddl-default-edit .js-ddl-newcred').hide();
        $('#ddl-default-edit .js-ddl-select-existing-cred').show();

        $('#ddl-default-edit .js-ddl-edit-cred-link').show();
        $('#ddl-default-edit .js-ddl-create-cred-form').hide();

        // Select the first form if there is only one form.

        var options = $('#ddl-default-edit .js-ddl-cred-select option');
        if (options.length == 2) {
            $('#ddl-default-edit .js-ddl-cred-select').val($(options[1]).val());
        }

        _handle_form_select_change();

        var value = $('#ddl-default-edit .js-ddl-cred-select').find('option:selected').data('type');
        messages_manager.append_message( value ? value === 'create' ? 'new' : value : 'empty' );
    };

    var _enable_edit_button = function(state) {
        $('#ddl-default-edit .js-ddl-edit-cred-link').prop('disabled', !state);
    };

    var _handle_form_select_change = function(event) {
        var form_id = $('#ddl-default-edit .js-ddl-cred-select').val();

        _enable_edit_button(form_id != '');
        _dialog.disable_save_button(form_id == '');

        if( typeof event === 'undefined' ){
            return;
        }

        var type = jQuery(this).find('option:selected').data('type');

        if( type === 'create' ){
            type = 'new';
        }

        messages_manager.append_message( type );
    };

    var _create_and_open_cred_form = function () {
        var mode = $('#ddl-default-edit .js-cred-new-mode').val();
        var post_type = $('#ddl-default-edit .js-cred-post-type').val();
        var cell_name = $('#ddl-default-edit #ddl-default-edit-cell-name').val();
        cell_name = _get_unique_form_name(cell_name);

        var data = {
            action : 'ddl_create_cred_form',
            wpnonce : $('#ddl_layout_cred_nonce').attr('value'),
            mode : mode,
            post_type : post_type,
            name : WPV_Toolset.Utils._strip_tags_and_preserve_text( cell_name )
        };

        //var spinner = _dialog.insert_spinner_after('#ddl-default-edit .js-ddl-create-cred-form').show();
        $('#ddl-default-edit .js-ddl-create-cred-form').parent().css('position', 'relative');
        WPV_Toolset.Utils.loader.loadShow( $('#ddl-default-edit .js-ddl-create-cred-form').parent(), true ).css({
            'position':'relative',
            'right':'96px',
            'top':'-36px'
        });

        $('.js-cred-form-create-error').hide();

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function(data) {
                data = JSON.parse(data);

                //spinner.remove();

                WPV_Toolset.Utils.loader.loadHide();

                if (data.form_id) {
                    _cred_forms_created.push(data.form_id);
                    var select = $('#ddl-default-edit .js-ddl-cred-select');
                    select.append(data.option);
                    select.val(data.form_id);
                    _switch_to_existing_form_mode();
                    _cred_open(true);
                } else if (data.error) {
                    $('.js-cred-form-create-error').html(data.error).show();
                }

            }
        });

    };

    var _get_unique_form_name = function(cell_name) {
        var existing_names = Array();
        $('#ddl-default-edit .js-ddl-cred-select > option').each ( function () {
            existing_names.push($(this).data('form-title'))
        })

        var test_name = cell_name;
        var index = 1;
        while (_.contains(existing_names, test_name)) {
            test_name = cell_name + ' ' + index;
            index++;
        }
        return test_name;
    }

    self.preview = function ( content ) {
        var preview = jQuery('#ddl-cred-preview').html();

        // find what the cred for does.
        var found = false;
        var cred_id = content.ddl_layout_cred_id;
        $('.js-ddl-cred-select > option').each ( function () {
            if( $(this).val() == cred_id) {
                var type = $(this).data('type');
                var post_type = $(this).data('post-type');

                preview = preview.replace('%EDIT%', type);
                preview = preview.replace('%POST_TYPE%', post_type);

                found = true;
            }
        })

        if (!found) {
            preview = jQuery('#ddl-cred-preview-cred-not-found').html();
        }

        return preview;
    };

    var _does_cred_for_exist = function (content) {
        var cred_id = content.ddl_layout_cred_id;

        var found = false;
        if (cred_id) {
            $('.js-ddl-cred-select > option').each ( function () {
                if( $(this).val() == cred_id) {
                    found = true;
                }
            });
        }
        return found;

    };

    _init();
}


jQuery(function($) {
    DDLayout.cred_cell = new DDLayout.CredCell($);
});


DDLayout.CredInIfame = function($)
{
    _.extend(DDLayout.CredInIfame.prototype, new DDLayout.ToolsetInIfame($, this))

    var self = this;
    var _cred_id = null;
    var _new_form = false;
    var _dialog = null;

    self.open_cred_in_iframe = function (cred_id, cell_type, new_cell, new_form, dialog) {
        _cred_id = cred_id;
        _new_form = new_form;
        _dialog = dialog;

        self.open_in_iframe(cell_type, new_cell);

        $('#ddl-layout-toolset-iframe').on('ddl-layout-toolset-iframe-loaded', function (event, iFrameDocument) {
            _.defer(_dismiss_distraction, iFrameDocument);
        });

        $('#ddl-default-edit .js-close-toolset-iframe-no-save').show();

    }

    self.get_url = function (cell_type, new_cell) {
        var url = 'post.php?post=' + _cred_id + '&action=edit&&in-iframe-for-layout=1';
        if (_new_form) {
            url += '&new_layouts_form=1'
        }

        return url;
    }

    self.get_text = function (text_type) {
        switch (text_type) {
            case 'close':
                return $('#ddl-default-edit .js-ddl-edit-cred-link').data('close-cred-text');

            case 'close_no_save':
                return $('#ddl-default-edit .js-ddl-edit-cred-link').data('discard-cred-text');

        }

        return 'UNDEFINED';
    }

    self.iframe_has_closed = function () {

        _dialog.save_and_close_dialog();
    }

    self.close_iframe = function (callback) {


        self.add_loading_overlay();
        jQuery("#ddl-layout-toolset-iframe").hide();

        var cred_iframe = document.getElementById("ddl-layout-toolset-iframe").contentWindow.DDLayout.layouts_cred,
            form_name = cred_iframe.get_form_name(),
            css = cred_iframe.get_css_settings();

        if( typeof DDLayout_settings !== 'undefined' && DDLayout_settings.DDL_JS && DDLayout_settings.DDL_JS.layouts_css_properties ){
            DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses = _.union( DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses, css.css );
        }

        if( null === css.css ){
            css.css = [];
        }
        DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css.css );
        DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', css.css , jQuery("#ddl-default-edit") );


        jQuery('input[name="ddl-default-edit-css-id"]').val( css.id );
        jQuery('#ddl-default-edit').find('select[name="ddl_tag_name"]').val( css.tag ).trigger('change');
        jQuery('#ddl-default-edit #ddl-default-edit-cell-name').val( form_name );

        var form_settings = cred_iframe.get_form_settings();

        // update data for option
        $('.js-ddl-cred-select > option').each ( function () {
            if ( $(this).val() == _cred_id ) {
                var type = form_settings.type == 'new' ? $('.js-ddl-cred-select').data('new') : $('.js-ddl-cred-select').data('edit');
                $(this).data('type', type);
                $(this).data('post-type', form_settings.post_type);
            }
        });

        cred_iframe.save_form();

        /*_.defer(function(){
            self.remove_loading_overlay();
            if( typeof callback === 'function') {
                callback.call( this );
            }
        });*/
        _.delay(function(){
            self.remove_loading_overlay();
            if( typeof callback === 'function') {
                callback.call( this );
            }
        }, 500);
    };

    var _dismiss_distraction = function(context){
        if( !context ) return;


        var $pointer = $( context.body ).find('div.wp-pointer');
        if( $pointer ){
            $pointer.remove();
        };
    };
};

// I know this is not DRY but the file is loaded in front-end editor where the messages are not used and not defined
// to have one only object would have been too complex in terms of existence control and it would have raised the possibility
// of type errors, so let's make 2 copies of the same handler.
DDLayout.CredCellMessages = function( ){
    var self = this,
        layout = DDLayout.ddl_admin_page.instance_layout_view.model,
        layout_type = layout.get('layout_type'),
        $message_wrap = jQuery('.js-ddl-cred-dialog-message');


    self.get_messages = function( form_type ){

        if( DDLayout_settings.DDL_JS.strings.hasOwnProperty('cred_create') === false ){
            return undefined;
        }

        if( form_type === 'empty' ){
            return '';
        }

        var messages = {
            normal : {
                new : DDLayout_settings.DDL_JS.strings.cred_create,
                edit : DDLayout_settings.DDL_JS.strings.cred_edit
            },
            private : {
                new : '',
                edit : DDLayout_settings.DDL_JS.strings.cred_edit_private
            },
        };

        return messages[layout_type][form_type];
    };

    self.append_message = function( form_type ){

        if( typeof form_type !== 'undefined' ){
            $message_wrap.empty();
        } else {
            self.get_message_class( 'empty', $message_wrap );
            return;
        }

        var message = self.get_messages( form_type );

        if( message ){
            $message_wrap.append( message );
            self.get_message_class( form_type, $message_wrap );
        } else {
            $message_wrap.empty();
            self.get_message_class( 'empty', $message_wrap );
        }

    };

    self.get_message_class = function( type, $el ){

        if( type === 'empty' ){
            $el.removeClass( 'toolset-alert toolset-alert-warning' );
            $el.removeClass( 'toolset-alert toolset-alert-info' );
            return;
        }

        if( layout_type === 'private' && type === 'edit' || layout_type !== 'private' && type === 'new' ){
            $el.removeClass( 'toolset-alert toolset-alert-info' );
            $el.addClass( 'toolset-alert toolset-alert-warning' );
        } else {
            $el.removeClass( 'toolset-alert toolset-alert-warning' );
            $el.addClass( 'toolset-alert toolset-alert-info' );
        }
    };
};
