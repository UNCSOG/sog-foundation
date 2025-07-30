// This code handles the CRED cell when CRED is enabled

var DDLayout = DDLayout || {};

DDLayout.CredUserCell = function ($)
{
    var self = this,
        _dialog = null,
        messages_manager = null,
        _cred_user_forms_created = null;

    var _init = function _init( ) {
        $(document).on('cred-user-cell.dialog-open', _dialog_open);
        $(document).on('cred-user-cell.dialog-close', _dialog_close);
    }

    var _dialog_open = function (event, content, dialog) {

        _cred_user_forms_created = Array();

        if ($('.js-ddl-cred-user-not-activated').length) {
            dialog.disable_save_button(true);
            return;
        }


        $('.js-cred-user-form-create-error').hide();

        _dialog = dialog;

        messages_manager = new DDLayout.CredUserCellMessages( );
        messages_manager.append_message( $('#ddl-default-edit .js-cred-user-new-mode option:selected').val() );

        $('#ddl-default-edit .js-ddl-edit-cred-user-link').off('click');
        $('#ddl-default-edit .js-ddl-edit-cred-user-link').on('click', _edit_cred_user_form);


        $('#ddl-default-edit .js-ddl-create-cred-user-form').off('click');
        $('#ddl-default-edit .js-ddl-create-cred-user-form').on('click', _create_and_open_cred_user_form);

        _create_create_and_edit_form_button();
        _handle_autogenerate_settings();

        // Make sure that the cred form has been selected (it might have been deleted)

        if (content.ddl_layout_cred_user_id != '') {
            if (content.ddl_layout_cred_user_id != $('#ddl-default-edit .js-ddl-cred-user-select').val()) {
                content.ddl_layout_cred_user_id = '';
                $('#ddl-default-edit .js-ddl-cred-user-select').val('')
            }
        }


        if (!_is_cred_user_embedded() && content.ddl_layout_cred_user_id == '') {
            _switch_to_new_form_mode();
            _enable_edit_button(true);
        } else {
            _switch_to_existing_form_mode();
            _enable_edit_button(true);
        }

        $('#ddl-default-edit .js-ddl-cred-user-form-create').off('click');
        $('#ddl-default-edit .js-ddl-cred-user-form-create').on('click', _switch_to_new_form_mode);

        $('#ddl-default-edit .js-ddl-cred-user-form-existing').off('click');
        $('#ddl-default-edit .js-ddl-cred-user-form-existing').on('click', _switch_to_existing_form_mode);

        $('#ddl-default-edit .js-ddl-cred-user-select').off('change');
        $('#ddl-default-edit .js-ddl-cred-user-select').on('change', _handle_form_select_change);

        $('#ddl-default-edit .js-cred-user-new-mode').off('change');
        $('#ddl-default-edit .js-cred-user-new-mode').on('change', _set_cell_name);

        $('#ddl-default-edit .js-cred-user_role').off('change');
        $('#ddl-default-edit .js-cred-user_role').on('change', _set_cell_name);

        if (!_dialog.is_new_cell()) {
            if (_does_cred_user_for_exist(content)) {
                if (!_is_cred_user_embedded()) {
                    _edit_cred_user_form();
                }
            } else {
                jQuery('.js-default-dialog-content').prepend('<div class="js-cred-user-form-error toolset alert toolset-alert-error"><p class="ddl_error_message_padding">' +
                        jQuery('#ddl-cred-user-preview-cred-user-not-found').html() +
                        '</p></div>');
            }
        }
        _track_name_change();
    }

    var _track_name_change = function(){
        var $select = $('select[name="ddl-layout-ddl_layout_cred_user_id"]'),
            $text = $('input[name="ddl-default-edit-cell-name"]');

        $select.on('change', function(event){
            $text.val( $(this).find('option:selected').text() );
        });
    };

    var _is_cred_user_embedded = function () {
        return $('.js-ddl-cred-user-form-create').length == 0;
    }

    var _create_create_and_edit_form_button = function () {
        var after = jQuery('.js-dialog-edit-save')[0];

        $('.js-ddl-create-cred-form').hide();
        $('.js-ddl-edit-cred-link').hide();
        $('.js-ddl-create-cred-relationship-form').hide();
        $('.js-ddl-edit-cred-relationship-link').hide();

        var button_create = $('#ddl-default-edit .js-ddl-create-cred-user-form');
        $(button_create).insertAfter($(after));

        var button_edit = $('#ddl-default-edit .js-ddl-edit-cred-user-link');
        $(button_edit).insertAfter($(after));
    };

    var _dialog_close = function(event, content, dialog) {

        $('.js-cred-user-form-error').remove();

        $('#ddl-default-edit .js-ddl-edit-cred-user-link').insertAfter('.js-ddl-select-existing-cred');
        $('#ddl-default-edit .js-ddl-create-cred-user-form').insertAfter('.js-ddl-select-existing-cred');

        // we should clean up any CRED forms we created.
        if (_cred_user_forms_created.length) {
            for (var i = 0; i < _cred_user_forms_created.length; i++) {
                if (content.ddl_layout_cred_user_id == _cred_user_forms_created[i]) {
                    // delete from created list
                    _cred_user_forms_created.splice(i, 1);
                    break;
                }
            }
        }
        if (_cred_user_forms_created.length) {

            // remove from the select control

            for (var i = 0; i < _cred_user_forms_created.length; i++) {
                $('[name="ddl-layout-ddl_layout_cred_user_id"] option').each(function () {
                    if ($(this).val() == _cred_user_forms_created[i]) {
                        $(this).remove();
                    }
                })
            }

            // delete in the DB
            var data = {
                action: 'ddl_delete_cred_user_forms',
                wpnonce: $('#ddl_layout_cred_user_nonce').attr('value'),
                forms: _cred_user_forms_created
            };

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                }
            });

        }
    };

    var _handle_autogenerate_settings = function(){

        $('#ddl-default-edit .js-cred-user-autogenerate_username').off('change');
        $('#ddl-default-edit .js-cred-user-autogenerate_username').val("1").prop('checked', true);
        $('#ddl-default-edit .js-cred-user-autogenerate_username').on('change', handle_checkboxes_changes);

        $('#ddl-default-edit .js-cred-user-autogenerate_password').off('change');
        $('#ddl-default-edit .js-cred-user-autogenerate_password').val("1").prop('checked', true);
        $('#ddl-default-edit .js-cred-user-autogenerate_password').on('change', handle_checkboxes_changes);

        $('#ddl-default-edit .js-cred-user-autogenerate_nickname').off('change');
        $('#ddl-default-edit .js-cred-user-autogenerate_nickname').val("1").prop('checked', true);
        $('#ddl-default-edit .js-cred-user-autogenerate_nickname').on('change', handle_checkboxes_changes);
    };

    var handle_checkboxes_changes = function( event ){
        var val = $(this).val();
        if( val === '0' ){
            $(this).val('1').prop('checked', true);
        } else if( val === '1' ){
            $(this).val('0').prop('checked', false);
        }
    };

    var _set_cell_name = function() {
        var mode = $('#ddl-default-edit .js-cred-user-new-mode option:checked').html();
        var user_role = $('#ddl-default-edit .js-cred-user_role option:checked').html();

        $('#ddl-default-edit-cell-name').val(mode + ' - ' + user_role);
        messages_manager.append_message( $('#ddl-default-edit .js-cred-user-new-mode').find('option:selected').val() );
    }

    var _edit_cred_user_form = function() {
        _cred_user_open(false);
    }

    var _cred_user_open = function(new_form) {

        if (typeof DDLayout.cred_user_in_iframe == 'undefined') {
            DDLayout.cred_user_in_iframe = new DDLayout.CredUserInIfame($);
        }

        DDLayout.cred_user_in_iframe.open_cred_user_in_iframe($('#ddl-default-edit [name="ddl-layout-ddl_layout_cred_user_id"] option:checked').val(),
                _dialog.get_cell_type(),
                _dialog.is_new_cell(),
                new_form,
                _dialog);

    }

    var _switch_to_new_form_mode = function() {
        $('#ddl-default-edit .js-ddl-cred-user-form-create').prop('checked', true);
        $('#ddl-default-edit .js-ddl-cred-user-form-existing').prop('checked', false);

        $('#ddl-default-edit .js-ddl-newcred').show();
        $('#ddl-default-edit .js-ddl-select-existing-cred').hide();

        $('#ddl-default-edit .js-ddl-edit-cred-user-link').hide();
        $('#ddl-default-edit .js-ddl-create-cred-user-form').show();

        _dialog.hide_save_button(true);

        _set_cell_name();
    }

    var _switch_to_existing_form_mode = function() {
        $('#ddl-default-edit .js-ddl-cred-user-form-create').prop('checked', false);
        $('#ddl-default-edit .js-ddl-cred-user-form-existing').prop('checked', true);

        $('#ddl-default-edit .js-ddl-newcred').hide();
        $('#ddl-default-edit .js-ddl-select-existing-cred').show();

        $('#ddl-default-edit .js-ddl-edit-cred-user-link').show();
        $('#ddl-default-edit .js-ddl-create-cred-user-form').hide();

        // Select the first form if there is only one form.

        var options = $('#ddl-default-edit .js-ddl-cred-user-select option');
        if (options.length == 2) {
            $('#ddl-default-edit .js-ddl-cred-user-select').val($(options[1]).val());
        }

        _handle_form_select_change();

        var value = $('#ddl-default-edit .js-ddl-cred-user-select').find('option:selected').data('type');
        messages_manager.append_message( value ? value === 'create' ? 'new' : value : 'empty' );
    }

    var _enable_edit_button = function(state) {
        $('#ddl-default-edit .js-ddl-edit-cred-user-link').prop('disabled', !state);
    }

    var _handle_form_select_change = function _handle_form_select_change(event) {
        var form_id = $('#ddl-default-edit .js-ddl-cred-user-select').val();

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
    }

    var _create_and_open_cred_user_form = function() {
        var mode = $('#ddl-default-edit .js-cred-user-new-mode').val(),
            post_type = $('#ddl-default-edit .js-cred-user-post-type').val(),
            user_role = $('#ddl-default-edit .js-cred-user_role').val(),
            cell_name = $('#ddl-default-edit #ddl-default-edit-cell-name').val(),
            autogenerate_user = $('#ddl-default-edit .js-cred-user-autogenerate_username').val(),
            autogenerate_nickname = $('#ddl-default-edit .js-cred-user-autogenerate_nickname').val(),
            autogenerate_password = $('#ddl-default-edit .js-cred-user-autogenerate_password').val();

        cell_name = _get_unique_form_name(cell_name);

        var data = {
            action: 'ddl_create_cred_user_form',
            type: 'cred-user-form',
            wpnonce: $('#ddl_layout_cred_user_nonce').attr('value'),
            mode: mode,
            autogenerate_user:autogenerate_user,
            autogenerate_password:autogenerate_password,
            autogenerate_nickname:autogenerate_nickname,
            post_type: post_type,
            user_role:user_role,
            name: WPV_Toolset.Utils._strip_tags_and_preserve_text(cell_name)
        };

        //var spinner = _dialog.insert_spinner_after('#ddl-default-edit .js-ddl-create-cred-user-form').show();
        $('#ddl-default-edit .js-ddl-create-cred-user-form').parent().css('position', 'relative');
        WPV_Toolset.Utils.loader.loadShow($('#ddl-default-edit .js-ddl-create-cred-user-form').parent(), true).css({
            'position': 'relative',
            'right': '96px',
            'top': '-36px'
        });

        $('.js-cred-user-form-create-error').hide();

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            cache: false,
            success: function (data) {
                data = JSON.parse(data);

                //spinner.remove();

                WPV_Toolset.Utils.loader.loadHide();

                if (data.form_id) {
                    _cred_user_forms_created.push(data.form_id);
                    var select = $('#ddl-default-edit .js-ddl-cred-user-select');
                    select.append(data.option);
                    select.val(data.form_id);
                    _switch_to_existing_form_mode();
                    _cred_user_open(true);
                } else if (data.error) {
                    $('.js-cred-user-form-create-error').html(data.error).show();
                }

            }
        });

    }

    var _get_unique_form_name = function _get_unique_form_name(cell_name) {
        var existing_names = Array();
        $('#ddl-default-edit .js-ddl-cred-user-select > option').each(function () {
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

    self.preview = function (content) {
        var preview = jQuery('#ddl-cred-user-preview').html();

        // find what the cred for does.
        var found = false;
        var cred_user_id = content.ddl_layout_cred_user_id;
        $('.js-ddl-cred-user-select > option').each(function () {
            if ($(this).val() == cred_user_id) {
                var type = $(this).data('type');
                var post_type = $(this).data('post-type');
                var level = $(this).data('user-level');

                preview = preview.replace('%EDIT%', type);
                preview = preview.replace('%POST_TYPE%', level || post_type);

                found = true;
            }
        })

        if (!found) {
            preview = jQuery('#ddl-cred-user-preview-cred-user-not-found').html();
        }

        return preview;
    }

    var _does_cred_user_for_exist = function (content) {
        var cred_user_id = content.ddl_layout_cred_user_id;

        var found = false;
        if (cred_user_id) {
            $('.js-ddl-cred-user-select > option').each(function () {
                if ($(this).val() == cred_user_id) {
                    found = true;
                }
            });
        }
        return found;

    }

    _init();
}


jQuery(function ($) {
    DDLayout.cred_user_cell = new DDLayout.CredUserCell($);
});


DDLayout.CredUserInIfame = function ($)
{
    _.extend(DDLayout.CredUserInIfame.prototype, new DDLayout.ToolsetInIfame($, this))

    var self = this;
    var _cred_user_id = null;
    var _new_form = false;
    var _dialog = null;

    self.open_cred_user_in_iframe = function (cred_user_id, cell_type, new_cell, new_form, dialog) {
        _cred_user_id = cred_user_id;
        _new_form = new_form;
        _dialog = dialog;

        self.open_in_iframe(cell_type, new_cell);

        $('#ddl-layout-toolset-iframe').on('ddl-layout-toolset-iframe-loaded', function (event, iFrameDocument) {

            _.defer(_dismiss_distraction, iFrameDocument);
        });

        $('#ddl-default-edit .js-close-toolset-iframe-no-save').show();

    }

    self.get_url = function (cell_type, new_cell) {
        var url = 'post.php?post=' + _cred_user_id + '&action=edit&&in-iframe-for-layout=1';
        if (_new_form) {
            url += '&new_layouts_form=1'
        }

        return url;
    }

    self.get_text = function (text_type) {
        switch (text_type) {
            case 'close':
                return $('#ddl-default-edit .js-ddl-edit-cred-user-link').data('close-cred-user-text');

            case 'close_no_save':
                return $('#ddl-default-edit .js-ddl-edit-cred-user-link').data('discard-cred-user-text');

        }

        return 'UNDEFINED';
    }

    self.iframe_has_closed = function () {

        _dialog.save_and_close_dialog();

    }

    self.close_iframe = function (callback) {
        /*jQuery("#ddl-layout-toolset-iframe").on('load', function () {
            // Wait for a reload.
            jQuery("#ddl-layout-toolset-iframe").off('load');
            self.remove_loading_overlay();
            callback();
        });*/

        self.add_loading_overlay();
        jQuery("#ddl-layout-toolset-iframe").hide();

        var cred_user_iframe = document.getElementById("ddl-layout-toolset-iframe").contentWindow.DDLayout.layouts_cred_user,
            form_name = cred_user_iframe.get_form_name(),
            css = cred_user_iframe.get_css_settings();

        if( typeof DDLayout_settings !== 'undefined' && DDLayout_settings.DDL_JS && DDLayout_settings.DDL_JS.layouts_css_properties ){
            DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses = _.union( DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses, css.css );
        }

        if( null === css.css ){
            css.css = [];
        }

            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css.css );
            DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', css.css , jQuery("#ddl-default-edit") );


        jQuery('input[name="ddl-default-edit-css-id"]').val(css.id);
        jQuery('#ddl-default-edit').find('select[name="ddl_tag_name"]').val(css.tag).trigger('change');
        jQuery('#ddl-default-edit #ddl-default-edit-cell-name').val(form_name);

        var form_settings = cred_user_iframe.get_form_settings();
        // update data for option
        $('.js-ddl-cred-user-select > option').each(function () {
            if ($(this).val() == _cred_user_id) {
                var type = form_settings.type == 'new' ? $('.js-ddl-cred-user-select').data('new') : $('.js-ddl-cred-user-select').data('edit');
                $(this).data('type', type);
                $(this).data('post-type', form_settings.post_type);
                if(form_settings.user_role !==''){
                    $(this).data('user-level', form_settings.user_role);
                }
            }
        });
        cred_user_iframe.save_form();

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

    var _dismiss_distraction = function (context) {
        if (!context)
            return;

        $pointer = $(context.body).find('div.wp-pointer');
        if ($pointer) {
            $pointer.remove();
        }
        ;
    };

};

// I know this is not DRY but the file is loaded in front-end editor where the messages are not used and not defined
// to have one only object would have been too complex in terms of existence control and it would have raised the possibility
// of type errors, so let's make 2 copies of the same handler.
DDLayout.CredUserCellMessages = function( ){
    var self = this,
        layout = DDLayout.ddl_admin_page.instance_layout_view.model,
        layout_type = layout.get('layout_type'),
        $message_wrap = jQuery('.js-ddl-cred-dialog-message');


    self.get_messages = function( form_type ){

        if( DDLayout_settings.DDL_JS.strings.hasOwnProperty('cred_create_user') === false ){
            return undefined;
        }

        if( form_type === 'empty' ){
            return '';
        }

        var messages = {
            normal : {
                new : DDLayout_settings.DDL_JS.strings.cred_create_user,
                edit : DDLayout_settings.DDL_JS.strings.cred_edit_user
            },
            private : {
                new : '',
                edit : DDLayout_settings.DDL_JS.strings.cred_edit_user_private
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

        if( layout_type !== 'private' && type === 'new' ){
            $el.removeClass( 'toolset-alert toolset-alert-info' );
            $el.addClass( 'toolset-alert toolset-alert-warning' );
        } else {
            $el.removeClass( 'toolset-alert toolset-alert-warning' );
            $el.addClass( 'toolset-alert toolset-alert-info' );
        }
    };
};
