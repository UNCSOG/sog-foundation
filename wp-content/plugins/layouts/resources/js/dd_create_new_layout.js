var DDLayout = DDLayout || {};

jQuery(function ($) {
    DDLayout.new_layout_dialog = new DDLayout.NewLayoutDialog($);
    if (typeof ddl_layouts_create_new_layout_trigger != 'undefined' && ddl_layouts_create_new_layout_trigger) {
        DDLayout.new_layout_dialog.show_create_new_layout_dialog(null, null, null);
    }
});

DDLayout.NewLayoutDialog = function ($) {
    var self = this
    // Change the WP menu for "Add a menu" to open the popup instead of redirecting
    // TODO: create new action for links with this class, since we will remove dialog completely.
    // Maybe bind click on this button directly to create layout ajax call
        , new_layout_menu_link = jQuery('a[href="admin.php?page=dd_layouts&new_layout=true"]');
    new_layout_menu_link.addClass('js-layout-add-new-top');

    self.init = function () {

        self.handle_create_layout_link_click();

        self.handle_create_private_layout_link_click();

        self.handle_title_change();

        self.handle_create_new_layout();

        self.handle_preset();

        self.handle_check_parents();

        self.postTypesHandler = new DDLayout.NewLayoutDialogPostTypesHandler($);
    };

    self.handle_preset = function () {
        $('.js-presets-list-item').on('click', function (event) {
            $('.js-presets-list-item')
                .data('selected', false)
                .removeClass('selected');

            $(this)
                .data('selected', true)
                .addClass('selected');
        });

        // add preset-container classes to the preset preview.
        $('.presets-list .row-fluid [class*="span-preset"]').each(function () {
            if ($(this).find('.row-fluid').length) {
                // it contains rows so it's a container.
                $(this).addClass('preset-container');
            }
        });

        $('.js-dd-layout-type').on('change', function (event) {
            var width = jQuery('.js-create-new-layout').data('width');
            self._show_presets(width);
        });
    };

    function show_layout_create_error_message(spinnerContainer, message) {
        spinnerContainer.remove();
        $('.js-create-new-layout')
            .addClass('button-primary')
            .removeClass('button-secondary')
            .prop('disabled', false);

        $('.js-ddl-message-container').wpvToolsetMessage({
            text: message,
            type: 'error',
            stay: true
        });
    }

    self.get_creation_extras = function () {
        var handler = DDLayout.new_layout_dialog.postTypesHandler
            , action = handler.get_creation_extra_action();

        if (action === 'none') {
            return null;
        }

        else if (action === 'one') {
            var post_id = handler.get_post_id();

            if (post_id === null) return null;

            return {
                action: action,
                post_id: post_id
            };
        }

        else if (action === 'all') {
            var post_types = _.intersection(handler.get_post_types_to_batch(), DDLayout.new_layout_dialog.postTypesHandler.getPostTypesArray());

            if (post_types.length > 0) {
                return {
                    action: action,
                    post_types: post_types
                };
            } else {
                return {
                    action: action
                };
            }
        }
    };


    self.handle_create_new_layout_directly = function (create_from_single_data, parent_layout_id, width, parent_layout_type, layout_type ) {

        layout_type = layout_type || 'default';

        $(this).parent().css('position', 'relative');
        var spinnerContainer = $('<div class="spinner ajax-loader">').appendTo($(this).parent()).show().css({
            float: 'right',
            position: 'absolute',
            top: '12px',
            'right': '175px'
        });


        var data = {
            action: 'ddl_create_layout',
            title: 'new layout',
            wpnonce: $('#wp_nonce_create_layout').attr('value')
        };

        if( width ){
            data.width = width;
        }
        if( parent_layout_id ){
            data.parent_layout_id = parent_layout_id;
        }
        if( parent_layout_type ){
            data.parent_layout_type = parent_layout_type;
        }

        var single_data = create_from_single_data;

        if (null !== single_data) {
            data.single_data = single_data;
        }

        $.post(ajaxurl, data, function (response) {
            if ((typeof (response) !== 'undefined')) {

                var temp_res = JSON.parse(response);
                if (temp_res.error == 'error') {
                    show_layout_create_error_message(spinnerContainer, temp_res.error_message);
                }
                else if (typeof temp_res.id !== 'undefined' && temp_res.id !== 0) {
                    var url = $('.js-layout-new-redirect').val();
                    $(location).attr('href', url + temp_res.id + '&new=true');
                }
                else {
                    console.log("Error: WordPress AJAX returned ", response);
                    show_layout_create_error_message(spinnerContainer, ddl_create_layout_error);
                }
            } else {
                $('<span class="updated">error</span>').insertAfter($('.js-create-new-layout')).hide().fadeIn(500).delay(1500).fadeOut(500, function () {
                    $(this).remove();
                });

            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
                show_layout_create_error_message(spinnerContainer, ddl_create_layout_error);
                console.log("Error: ", textStatus, errorThrown);
            })
            .always(function () {

            });
    };


    // call this function on add new layout button click
    self.handle_create_new_layout = function () {
        // handle creating a new layout
        $(document).on('click', '.js-create-new-layout', function (e) {
            e.preventDefault();
            $(this).parent().css('position', 'relative');
            var spinnerContainer = $('<div class="spinner ajax-loader">').appendTo($(this).parent()).show().css({
                float: 'right',
                position: 'absolute',
                top: '12px',
                'right': '175px'
            });

            var title = $('.js-new-layout-title').val();
            var layout_type = $('input[name="dd-layout-type"]:checked').val();
            var $layout_preset = $('.js-presets-list-item:visible').filter(function () {
                return $(this).data('selected');
            });
            var layout_parent = $('.js-create-new-layout').data('parent_layout_id');

            if (typeof layout_parent == 'undefined') {
                layout_parent = 0;
            }

            if (typeof $('.js-new-layout-parent').val() !== 'undefined') {
                layout_parent = $('.js-new-layout-parent').val();
            }
            var save_parent = 0;
            if ($('.js-make-this-default-parent').prop('checked')) {
                save_parent = 1;
            }

            var columns = $('.js-create-new-layout').data('width');

            var extras = self.get_creation_extras();

            var data = {
                action: 'ddl_create_layout',
                title: _.escape(title),
                layout_type: layout_type,
                layout_preset: $layout_preset,
                layout_parent: layout_parent,
                columns: columns,
                post_types: DDLayout.new_layout_dialog.postTypesHandler.getPostTypesArray(),
                save_parent: save_parent,
                wpnonce: $('#wp_nonce_create_layout').attr('value')
            };

            if (null !== extras) {
                data.extras = extras;
            }

            $('.js-create-new-layout')
                .addClass('button-secondary')
                .removeClass('button-primary');
            $(this).prop('disabled', true);

            $.post(ajaxurl, data, function (response) {
                    if ((typeof(response) !== 'undefined')) {

                        var temp_res = JSON.parse(response);
                        if (temp_res.error == 'error') {
                            show_layout_create_error_message(spinnerContainer, temp_res.error_message);
                        }
                        else if (typeof temp_res.id !== 'undefined' && temp_res.id !== 0) {
                            var url = $('.js-layout-new-redirect').val();

                            $(location).attr('href', url + temp_res.id + '&new=true');
                        }
                        else {
                            console.log("Error: WordPress AJAX returned ", response);
                            show_layout_create_error_message(spinnerContainer, ddl_create_layout_error);
                        }
                    } else {
                        $('<span class="updated">error</span>').insertAfter($('.js-create-new-layout')).hide().fadeIn(500).delay(1500).fadeOut(500, function () {
                            $(this).remove();
                        });

                    }
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    show_layout_create_error_message(spinnerContainer, ddl_create_layout_error);
                    console.log("Error: ", textStatus, errorThrown);
                })
                .always(function () {

                });
        });
    };

    self.handle_title_change = function () {
        // handle the title change in the new layout popup.

        $(document).on('change keyup input cut paste', '.js-new-layout-title', function () {
            $('.js-error-container').find('.toolset-alert').remove();
            var $newLayoutButton = $('.js-create-new-layout');
            if (
                self.postTypesHandler.get_creation_extra_action() == 'all'
            ) {

                if ($('input.js-new-layout-title').val() != '' && self.postTypesHandler.getPostTypesArray().length > 0) {
                    $newLayoutButton
                        .prop('disabled', false)
                        .addClass('button-primary')
                        .removeClass('button-secondary');
                } else {
                    $newLayoutButton
                        .prop('disabled', true)
                        .removeClass('button-primary')
                        .addClass('button-secondary');
                }

            } else {
                if ($('input.js-new-layout-title').val() != '') {
                    $newLayoutButton
                        .prop('disabled', false)
                        .addClass('button-primary')
                        .removeClass('button-secondary');
                } else {
                    $newLayoutButton
                        .prop('disabled', true)
                        .removeClass('button-primary')
                        .addClass('button-secondary');
                }
            }

        });
    };

    self.handle_check_parents = function () {
        function change_default_parent() {
            $('.js-make-this-default-parent').prop('checked', false);
            if ($('.js-new-layout-parent').val() == '') {
                $('.js-make-this-default-parent-label').html($('.js-make-this-default-parent').data('no-parent-text'));
            } else {
                $('.js-make-this-default-parent-label').html($('.js-make-this-default-parent').data('default-text'));
            }
        }

        change_default_parent();
        $(document).on('change', '.js-new-layout-parent', function () {
            change_default_parent();
        });
    };

    self.handle_create_layout_link_click = function () {
        $(document).on('click', '.js-layout-add-new-top', function (e, parent_layout_id) {
            e.preventDefault();
            self.show_create_new_layout_dialog(null, null, null, null);
        });
    };

    /*
     * This is used only in case when user want to create new private layout (on button click from post/page edit page)
     */
    self.handle_create_private_layout_link_click = function () {
        $( document ).on( 'click', 'a.js-layout-private-add-new-top', self.privateLayoutNewTop );
    };

	self.privateLayoutNewTop = function ( event ) {
		event.preventDefault();

		if( ! +DDLayout_settings_create.user_can_create_private ) return;

		var private_layout_args = {
			'post_type':  jQuery( event.target ).data( 'post_type' ) || jQuery( this ).data( 'post_type' ),
			'content_id': jQuery( event.target ).data( 'content_id' ) || jQuery( this ).data( 'content_id' ),
			'editor': jQuery( event.target ).data( 'editor' ) || jQuery( this ).data( 'editor' )
		};

		var data = {
			action: 'ddl_create_private_layout',
			title: 'Layout for '+ jQuery( event.target ).data( 'post_type' ) || jQuery( this ).data( 'post_type' ) +' '+ jQuery( event.target ).data( 'content_id' ) || jQuery( this ).data( 'content_id' ),
			parent_layout_id: null,
			unsaved_changes: window.wp.autosave ? window.wp.autosave.server.postChanged() : wp.data && wp.data.select( 'core/editor' ) ? wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' ) : null,
			width: null,
			parent_layout_type: null,
			private_layout_arguments: private_layout_args,
			layout_type: 'private',
			single_data: {
				'who': 'one',
				'post_id': jQuery( event.target ).data( 'content_id' ) || jQuery( this ).data( 'content_id' ),
				'post_title': 'Layout for '+ jQuery( event.target ).data( 'post_type' ) || jQuery( this ).data( 'post_type' )+' '+ jQuery( event.target ).data( 'content_id' ) || jQuery( this ).data( 'content_id' )
			},
			wpnonce: $( '#wp_nonce_create_layout' ).attr( 'value' )
		};

		WPV_Toolset.Utils.do_ajax_post( data, {
			success: function( response ){
				if (typeof response.Data.id !== 'undefined' && response.Data.id !== 0) {
					Toolset.hooks.doAction( 'ddl_private_layout_created', response.Data.id );
					var url = $('.js-layout-new-redirect').val();
					$(location).attr('href', url + response.Data.id + '&new=true&toolset_help_video=content_layout');
				}
			},
			error: function( response ) {
				if (response.status === 'not_saved') {
					self.content_not_saved_dialog();
				} else {
					console.log( "Error: ", response.error_message );
				}
			},
			fail: function( response ){
				console.log( "Error: WordPress AJAX returned ", response );
			},
		});
	};

    self.content_not_saved_dialog = function() {

        var unsaved_post_data_dialog = new DDLayout.DialogView({
            title: DDL_Private_layout.you_have_unsaved_changes,
            modal:true,
            dialogClass: 'toolset-ui-dialog .js-unsaved-post-data-dialog',
            resizable: false,
            draggable: false,
            position: {my: "center", at: "center", of: window},
            width: 250,
            selector: '#js-ddl-unsaved_data_dialog',
            buttons: [
                {
                    text: DDL_Private_layout.stop_using_layout_dialog_closeme,
                    class: "button-primary",
                    click: function(){
                        jQuery(this).ddldialog("close");
                    }
                }
            ]
        });
        jQuery('.js-unsaved-post-data-dialog .ui-dialog-buttonset').css('float','none');
        unsaved_post_data_dialog.$el.on('ddldialogclose', function (event) {
            unsaved_post_data_dialog.remove();
        });

        unsaved_post_data_dialog.dialog_open();

    };


    self.show_create_new_layout_dialog = function (parent_layout_id, width, parent_layout_type, create_from_single_data) {
        self.handle_create_new_layout_directly(create_from_single_data, parent_layout_id, width, parent_layout_type);
    };

    self._show_presets = function (width) {
        if (jQuery('.js-dd-layout-type:checked').val() == 'fluid') {
            width = 12; // Force width of 12 for fluid layouts
        }

        var any_visible = false;
        jQuery('.js-presets-list-item').each(function () {
            if (jQuery(this).data('width') == width) {
                jQuery(this).show();
                any_visible = true;
            } else {
                jQuery(this).hide();
            }
        });

        if (any_visible) {
            jQuery('.js-preset-layouts-items').show();

            // Make sure one is selected
            var $layout_preset = $('.js-presets-list-item:visible').filter(function () {
                return $(this).data('selected');
            });
            if (!$layout_preset.length) {
                $('.js-presets-list-item:visible').first().trigger('click');
            }

        } else {
            jQuery('.js-preset-layouts-items').hide();
        }
    };

    self.init();
};

DDLayout.NewLayoutDialogPostTypesHandler = function ($) {

    var self = this,
        open = true,
        dropdown_list = $('.ddl-post-types-dropdown-list'),
        $dont_assign = $('#js-dont-assign-to')
        , $assign = $('#js-assign-to')
        , $assign_to_one = $('#js-assign-only-to')
        , creation_data = null
        , $message_container = $('.js-ddl-for-post-types-messages')
        , dialog = null;


    DDLayout.NewLayoutDialogPostTypesHandler._checked = [];

    self._deselect = $('.js-dont-assign-post-type');

    self._open_close = $('.js-ddl-for-post-types-open');

    self._apply_to_all = $('.js-apply-layout-for-all-posts');

    self.set_dialog = function (d) {
        dialog = d;
    };

    self.get_dialog = function () {
        return dialog;
    };

    self.init = function () {
        self.handle_show_hide_post_types();
        self.manage_check_box_change();
        self.manage_batch_selection();
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'color_box_closed', self.handle_dialog_close)
    };

    self.handle_radio_initial_state = function () {

        self.hide_select_bulk_assign_option_by_default();

        if (typeof creation_data === 'undefined' || !creation_data) {
            self.dont_assign_state();
        }
        else if (_.isObject(creation_data) && creation_data.who === 'all') {
            self.assign_state();
            self.disable_ui();
            var event = jQuery.Event("change");
            event.creation_data = creation_data;
            $assign.trigger(event);
        }
        else if (_.isObject(creation_data) && creation_data.who === 'one') {
            self.assign_to_one_state();
            self.disable_ui();
        }

    };

    self.disable_ui = function () {
        self._deselect.prop('disabled', true);
        $('input[name="post_types"]').prop('disabled', true);
    };

    self.add_message = function () {
        $message_container.show();
    };

    self.remove_message = function () {
        $message_container.hide();
    };

    self.handle_show_hide_post_types = function (event, open_message) {
        var $uls = jQuery('.js-change-layout-use-section', dropdown_list);
        $uls.removeClass('hidden');

        if (open === false) {
            //  $('i.fa-caret-down').removeClass('fa-caret-down').addClass('fa-caret-up');
            dropdown_list.slideDown(function () {
                open = true;
            });
        }
        else if (open === true) {
            // $('i.fa-caret-up').removeClass('fa-caret-up').addClass('fa-caret-down')
            dropdown_list.slideUp(function () {
                open = false;
                self.enable_disable_button(false);
            });
        }

        if (open_message === true) {
            self.add_message();
        } else {
            self.remove_message();
        }


    };

    self.manage_check_box_change = function () {
        $('input[name="post_types"]').on('change', function (event) {

            if (jQuery(this).is(':checked') === true) {
                DDLayout.NewLayoutDialogPostTypesHandler._checked.push(jQuery(this).val());
                self.assign_state();
                self.make_select_bulk_assign_option_visible($(this), true);
            }
            else if (jQuery(this).is(':checked') === false) {
                DDLayout.NewLayoutDialogPostTypesHandler._checked = _.without(DDLayout.NewLayoutDialogPostTypesHandler._checked, jQuery(this).val());
                self.make_select_bulk_assign_option_visible($(this), false);

                if (DDLayout.NewLayoutDialogPostTypesHandler._checked.length === 0) {
                    self.dont_assign_state();
                    open = true;
                    self.handle_show_hide_post_types();
                }
            }
            self.enable_disable_button(true);
        });
    };

    self.make_select_bulk_assign_option_visible = function (target, what) {
        var $el = target.parent().next('label');

        if ($el.hasClass('do_not_show_at_all')) return;

        if (what) {
            $el.fadeIn('slow');
        }
        else {
            $el.fadeOut('slow');
        }
    };

    self.hide_select_bulk_assign_option_by_default = function () {
        $('input[name="post_types"]').each(function () {
            $(this).parent().next('label').hide();
        });
    };

    self.assign_state = function () {
        $dont_assign.prop('checked', false);
        $assign.prop('checked', true);
        $assign_to_one.prop('checked', false);
    };

    self.dont_assign_state = function () {
        $dont_assign.prop('checked', true);
        $assign.prop('checked', false);
        $assign_to_one.prop('checked', false);
    };

    self.assign_to_one_state = function () {
        $dont_assign.prop('checked', false);
        $assign.prop('checked', false);
        $assign_to_one.prop('checked', true);
    }

    self.manage_batch_selection = function () {
        var open_message = false;

        self._deselect.on('change', function (event) {
            event.stopImmediatePropagation();

            var check = +$(this).val();

            if (check === 0) {
                $('input[name="post_types"]').each(function (i) {
                    $(this).prop({checked: false});
                    $(this).parent().next('label').hide();
                    DDLayout.NewLayoutDialogPostTypesHandler._checked = [];
                });
                open = true;
                open_message = false;
                self.enable_disable_button(false);
            }
            else if (check === 1) {
                open = false;
                open_message = false;

                if (typeof event.creation_data !== 'undefined') {
                    var post_type = event.creation_data.post_type;

                    $('input[name="post_types"]').each(function (index, element) {

                        if ($(this).val() === post_type) {
                            $(element).prop({checked: true});
                            self.make_select_bulk_assign_option_visible($(element), true);
                            DDLayout.NewLayoutDialogPostTypesHandler._checked.push(post_type);
                        }

                    });
                }
                self.enable_disable_button(true);
            }
            else if (check === 2) {
                open = true;
                open_message = typeof event.creation_data === 'undefined';
                self.enable_disable_button(false);
            }

            self.handle_show_hide_post_types(event, open_message);
        });
    };

    self.enable_disable_button = function (checkboxes) {

        if (checkboxes) {
            if (jQuery('.js-new-layout-title').val() != '' && self.getPostTypesArray().length > 0) {
                jQuery('.js-create-new-layout').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
            } else {

                jQuery('.js-create-new-layout').prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
            }
        } else {
            if (jQuery('.js-new-layout-title').val() == '') {
                jQuery('.js-create-new-layout').prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
            } else {
                jQuery('.js-create-new-layout').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
            }
        }

    };

    self.setInitialState = function (parent, data) {
        var dialog = parent;

        creation_data = data;

        self.handle_radio_initial_state(creation_data);

        dialog.find('input.js-ddl-post-type-checkbox').each(function (i) {
            if (typeof creation_data === 'undefined' || !creation_data || creation_data.who === 'one') {
                $(this).prop('checked', false);
                $(this).parent().siblings('span.js-alret-icon-hide-post').each(function () {
                    $(this).remove();
                });
            }
        });
    };

    self.getPostTypesArray = function () {
        return DDLayout.NewLayoutDialogPostTypesHandler._checked;
    };

    self.get_post_types_to_batch = function () {
        var $bulk_types = $('.js-ddl-post-content-apply-all-checkbox', self.get_dialog()), $checked = $bulk_types.filter(function () {
            return $(this).is(':checked')
        }), ret = [];

        $checked.each(function () {
            ret.push($(this).val());
        });

        return ret;
    };

    self.get_creation_extra_action = function () {
        var $checked = self._deselect.filter(function () {
                return $(this).is(':checked')
            }),
            value = $checked.val(),
            check = {
                '0': 'none',
                '1': 'all',
                '2': 'one'
            };

        return check[value];
    };

    self.get_post_id = function () {
        if ($('#js-associate-post-upon-creation').is('input') === false) return null;

        return $('#js-associate-post-upon-creation').val();
    };

    self.handle_dialog_close = function () {
        open = true;
        self.handle_show_hide_post_types();
    };

    self.init();
};
