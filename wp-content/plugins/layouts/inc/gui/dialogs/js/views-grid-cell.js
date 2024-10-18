// This code handles the Views Content Grid and the Post Loop when Views is enabled

var DDLayout = DDLayout || {};

DDLayout.ViewsGrid = function ($) {
    var self = this;

    self._dialog = null;
    self._views_installed = ( $('.js-views-content-is_views_installed').val() == 1 );
    self._views_embedded = ( $('.js-views-content-is_views_embedded').val() == 1 );
    self._views_above_oneseven = ( $('.js-views-content-is_views_above_oneseven').val() == 1 );

    self.init = function () {
        // NOTE maybe move those definitions above, with all the other variables
        self._views_list_options = null;

        // Open, close and beyond events
        $(document).on('views-content-grid-cell.dialog-open post-loop-views-cell.dialog-open', self.dialog_open);
        $(document).on('views-content-grid-cell.dialog-close post-loop-views-cell.dialog-close', self.dialog_close);
    };



    self.dialog_open = function (event, content, dialog) {
        self.check_embedded_and_no_views();
        self._dialog = dialog;
        self._cell_type = dialog.get_cell_type();// views-content-grid-cell or post-loop-views-cell

        self._initialize_view_selector();
        self._existing_select = $('#ddl-default-edit .js-ddl-select-existing-view');
        self._purpose_settings = $('#ddl-default-edit .js-ddl-set-view-purpose');

        if (self._cell_type == 'views-content-grid-cell') {
            jQuery('.js-view-purpose[value=all]').attr('checked', true);
            self._purpose_settings.show();
        } else {
            self._purpose_settings.hide();
        }

        if (content.ddl_layout_view_id != '') {
            if (content.ddl_layout_view_id != $('#ddl-default-edit select[name="ddl-layout-ddl_layout_view_id"]').val()) {
                content.ddl_layout_view_id = '';
                $('#ddl-default-edit select[name="ddl-layout-ddl_layout_view_id"]').val('')
            }
        }

        if( dialog.is_new_cell() ){
            _.defer(self.handle_view_buttons);
        }
        if ( !self._views_embedded && self._views_installed ){
            jQuery('.js-no-views-message-views, .js-no-views-message-archives, .js-no-views-message').hide();
            jQuery('.js-ddl-view-select').show();
            if ( self._cell_type == 'views-content-grid-cell' && jQuery('.js-wpv-total-views').val() == 0 ){
                jQuery('.js-no-views-message,.js-no-views-message-views').show();
                jQuery('.js-ddl-view-select').hide();
            }
            if ( self._cell_type == 'post-loop-views-cell' && jQuery('.js-wpv-total-archives').val() == 0 ){
                jQuery('.js-no-views-message,.js-no-views-message-archives').show();
                jQuery('.js-ddl-view-select').hide();
            }
        }

        $('#ddl-default-edit .js-ddl-views-dialog-mode').off('click');
        $('#ddl-default-edit .js-ddl-views-dialog-mode').on('click', self.manage_dialog_mode);
		$('#ddl-default-edit #ddl-default-edit-cell-name').off('change keyup input cut paste');
		$('#ddl-default-edit #ddl-default-edit-cell-name').on('change keyup input cut paste', self._handle_cell_name_change);
        $('#ddl-default-edit .js-ddl-view-select').off('change');
        $('#ddl-default-edit .js-ddl-view-select').on('change', self._handle_view_change);
        $('#ddl-default-edit .js-ddl-create-edit-view').off('click');
        $('#ddl-default-edit .js-ddl-create-edit-view').on('click', self._handle_create_edit);

        // cleanup error messages from earlier opened dialogs
        $('.js-views-form-error').remove();


        //self._dialog_initialized = false;

        if (self._dialog.is_new_cell() && ddl_views_1_6_available) {
            $('#ddl-default-edit .js-ddl-views-grid-create').prop('checked', true);
            $('#ddl-default-edit .js-ddl-views-grid-existing').prop('checked', false);
            self._existing_select.hide();
            if (self._cell_type == 'views-content-grid-cell') {
                self._purpose_settings.show();
            }
        } else if (ddl_views_1_6_available) {

            self.edit_view();
            if(content.ddl_layout_view_id ===''){

                var missing_resource_message = '';
                if(self._cell_type == 'views-content-grid-cell'){
                    missing_resource_message = DDLayout_settings.DDL_JS.strings.views_resource_missing;
                } else {
                    missing_resource_message = DDLayout_settings.DDL_JS.strings.views_archive_resource_missing;
                }
                _.defer(self.handle_view_buttons);

                jQuery('.js-default-dialog-content').prepend('<div class="js-views-form-error toolset alert toolset-alert-error"><div class="ddl-center-align"><p class="ddl_error_message_padding">' + missing_resource_message + '</p></div></div>');
            }

        } else {
            $('#ddl-default-edit .js-ddl-views-grid-create').prop('checked', false);
            $('#ddl-default-edit .js-ddl-views-grid-existing').prop('checked', true);
        }

        self.manage_dialog_mode();

        //self._dialog_initialized = true;
        //self._save_required = false;

        if (!ddl_views_1_6_available) {
            jQuery('.js-dialog-edit-save').prop('disabled', true);
        }

		// Disable selecting exising View if there are no Views.
		$('#ddl-default-edit .js-ddl-views-grid-existing').prop('disabled',
																$('#ddl-default-edit [name="ddl-layout-ddl_layout_view_id"] option').length < 2);


		self._manage_parametric_mode();
    };

	self._manage_parametric_mode = function () {
		jQuery('#ddl-default-edit .js-view-result-missing').hide();

        if (self._cell_type == 'views-content-grid-cell' && self._dialog.is_new_cell()) {
			var views = DDLayout.ddl_admin_page.get_layout().get_views_needing_result_cells();
			if (views.length) {

				// allways use the first view found.
				var view_id = views[0];

				// Set the cell name
				var original_cell_name = jQuery('#ddl-default-edit-cell-name').val();

				var cell_name = jQuery('#ddl-default-edit .js-ddl-complete-search').data('cell-name-text');
				cell_name = cell_name.replace('%CELL_NAME%', self._get_view_name(view_id));

				jQuery('#ddl-default-edit-cell-name').val(cell_name);

				jQuery('#ddl-default-edit .js-ddl-complete-search').data('view-id', view_id);

				jQuery('#ddl-default-edit .js-ddl-complete-search').prop('checked', true);
				jQuery('#ddl-default-edit .js-ddl-different-view').prop('checked', false);

				jQuery('#ddl-default-edit .js-view-result-missing').show();
				jQuery('#ddl-default-edit .js-view-result-ok').hide();
				jQuery('#ddl-default-edit .js-ddl-set-view-purpose').hide();

				// Handle "Insert a different View" click
				jQuery('#ddl-default-edit .js-ddl-different-view').off('click');
				jQuery('#ddl-default-edit .js-ddl-different-view').on('click', function () {

					jQuery('#ddl-default-edit .js-ddl-complete-search').prop('checked', false);
					jQuery('#ddl-default-edit .js-view-result-ok').fadeIn('slow');
					jQuery('#ddl-default-edit .js-ddl-set-view-purpose').fadeIn('slow');

					if (jQuery('#ddl-default-edit-cell-name').val() == cell_name) {
						jQuery('#ddl-default-edit-cell-name').val(original_cell_name);
					}

				});

				// Handle "Complete the parametric search" click
				jQuery('#ddl-default-edit .js-ddl-complete-search').off('click');
				jQuery('#ddl-default-edit .js-ddl-complete-search').on('click', function () {

					jQuery('#ddl-default-edit .js-ddl-different-view').prop('checked', false);

					jQuery('#ddl-default-edit .js-view-result-ok').fadeOut('slow');
					jQuery('#ddl-default-edit .js-ddl-set-view-purpose').fadeOut('slow');

					if (jQuery('#ddl-default-edit-cell-name').val() == original_cell_name) {
						jQuery('#ddl-default-edit-cell-name').val(cell_name);
					}
				});

			}
		}
	}

	self._get_view_name = function (view_id) {
		var view_name = '';

        $('#ddl-default-edit [name="ddl-layout-ddl_layout_view_id"] option').each(function () {
			if ($(this).data('id') == view_id) {
				view_name = $(this).text();
			}
		});

		return view_name;

	}

    self.handle_view_buttons = function () {
        var after = jQuery('#ddl-default-edit .js-dialog-edit-save')[0];
        $('#ddl-default-edit .js-ddl-create-edit-view').insertAfter($(after)).show();
        $('#ddl-default-edit .js-dialog-edit-save').hide();

    };

    self.dialog_close = function (event, content, dialog) {
        $('.js-dialog-edit-save,.ui-tabs-nav').prop('disabled', false);
        //TODO: i removed following line not sure why we needed it
        jQuery(window).off('beforeunload.views-grid-cell');

        self._restore_view_selector();

        $( '#ddl-default-edit .js-save-toolset-iframe').remove();
    };

    // How/hide sections of the dialog depending on whether new or edit existng
    self.manage_dialog_mode = function ( e ) {
        self._purpose_settings.hide();
        self._existing_select.hide();
        if ($('#ddl-default-edit .js-ddl-views-grid-create').prop('checked')) {
            if (self._cell_type == 'views-content-grid-cell') {
                self._purpose_settings.show();
            }
			self.disable_save_button( $('#ddl-default-edit #ddl-default-edit-cell-name').val() == '' );
            $('#ddl-default-edit #ddl-default-edit-cell-name').focus();
        }
        if ($('#ddl-default-edit .js-ddl-views-grid-existing').prop('checked')) {
            self._existing_select.show();
            $('#ddl-default-edit .js-ddl-view-select').trigger('change');
			self.disable_save_button( $('#ddl-default-edit .js-ddl-view-select').val() == '' );
        }
    };

	self._handle_cell_name_change = function (event) {
		var cell_name = $('#ddl-default-edit #ddl-default-edit-cell-name').val();
		self.disable_save_button(cell_name == '');
	};

    self._handle_view_change = function (event) {
        var view_selected = $('#ddl-default-edit .js-ddl-view-select').val();
        self.disable_save_button(view_selected == '');
    };

    self.check_embedded_and_no_views = function(){
        var $alert = $('.js-data-embedded_no_views_at_all');

        if( $alert.is('div') &&
            typeof $alert.data('embedded_no_views_at_all') !== 'undefined' &&
            $alert.data('embedded_no_views_at_all') === 'yes'
        ){
            _.defer( (function(){
                    jQuery('.js-ddl-create-edit-view')
                        .prop('disabled', true)
                        .addClass('button-secondary')
                        .removeClass('button-primary');
            }));
        }
    };

	self.disable_save_button = function (state) {
		if ( state ) {
			$('#ddl-default-edit .js-ddl-create-edit-view')
				.prop('disabled', state)
				.addClass('button-secondary')
				.removeClass('button-primary');
		} else {
			$('#ddl-default-edit .js-ddl-create-edit-view')
				.prop('disabled', state)
				.removeClass('button-secondary')
				.addClass('button-primary');
		}
	}

    self._handle_create_edit = function (event) {

		// check if we need to complete a parametric search.

		if (jQuery('#ddl-default-edit .js-view-result-missing').is(":visible") &&
				jQuery('#ddl-default-edit .js-ddl-complete-search').prop('checked')) {

			jQuery('#ddl-default-edit .js-view-result-missing').hide();
			jQuery('#ddl-default-edit .js-view-result-ok').show();

			// Set it to use the existing View

			$('#ddl-default-edit .js-ddl-views-grid-create').prop('checked', false);
			$('#ddl-default-edit .js-ddl-views-grid-existing').prop('checked', true);

			var view_id = jQuery('#ddl-default-edit .js-ddl-complete-search').data('view-id');
			$('#ddl-default-edit select[name="ddl-layout-ddl_layout_view_id"]').val(view_id);

			self.edit_view();
			return;
		}

		if ($('#ddl-default-edit .js-ddl-views-grid-create').prop('checked')) {
			var cell_name = $('#ddl-default-edit #ddl-default-edit-cell-name').val();
			var current_cell = $('#ddl-default-edit').data('cell_view');
			var view_purpose = $('.js-view-purpose:checked').val();
			$('#ddl-default-edit').find('#ddl-default-edit .ddl_existing_views_content').show();

			var $me = $(this);

			var data = {
				action: 'ddl_create_new_view',
				wpnonce: $('#ddl_layout_view_nonce').attr('value'),
				cell_name: WPV_Toolset.Utils._strip_tags_and_preserve_text( cell_name ),
				purpose: view_purpose
			};

			var view_type = 'normal';

			if (self._cell_type == 'post-loop-views-cell') {
				data['is_archive'] = 1;
				view_type = 'layouts-loop';
                jQuery('.js-wpv-total-archives').val( (parseInt(jQuery('.js-wpv-total-archives').val())+1) );
			}else{
                jQuery('.js-wpv-total-views').val( (parseInt(jQuery('.js-wpv-total-views').val())+1) );
            }

			var spinner = self._dialog.insert_spinner_absolute( $me, {position:'absolute', 'right':'66px'} ).show();

			jQuery.ajax({
				url: ajaxurl,
				type: 'post',
				data: data,
				cache: false,
				async: false,
                dataType:'json',
				success: function (data) {
                    if( data.id ){
                        $('#ddl-default-edit select[name="ddl-layout-ddl_layout_view_id"]').append($("<option/>", {
                            value: data.id,
                            text: data.post_title,
                            'data-id': data.id,
                            'data-mode': view_type,
                            'data-purpose': view_purpose,
                            'checked': 'checked'
                        }));

                        self._views_list_options.push($('#ddl-default-edit .js-ddl-view-select option').last());

                        $('#ddl-default-edit select[name="ddl-layout-ddl_layout_view_id"]').val(data.id).trigger('change');
                        $('#ddl-default-edit .js-ddl-views-grid-existing').prop('checked', true).trigger('change');

                        spinner.remove();

                        self.edit_view_open(true);
                    } else if( data.error ) {
                        console.info( "There is an issue creating the View ", data.error );
                        spinner.remove();
                    }
				}
			});

		} else {
			self.edit_view();
		}
    };

    self.edit_view = function () {
        self.edit_view_open(false);
    };

    self.edit_view_open = function (new_view) {

        if (typeof DDLayout.views_in_iframe == 'undefined') {

            DDLayout.views_in_iframe = new DDLayout.ViewsInIfame($);
        }

        DDLayout.views_in_iframe.open_view_in_iframe(
            $('#ddl-default-edit [name="ddl-layout-ddl_layout_view_id"] option:checked').data('id'),
            self._cell_type,
            new_view,
            self._dialog
        );
    };

    self._get_view_selected = function () {
        return $('#ddl-default-edit [name="ddl-layout-ddl_layout_view_id"] option:checked').val();
    }

    self._initialize_view_selector = function () {
        // We need to only show Views for normal or loops depending on the cell type
        // hiding options don't work in ie so we need to remove and add options

        var selected = $('#ddl-default-edit .js-ddl-view-select option:checked').val();

        if (!self._views_list_options) {

            self._views_list_options = Array();

            $('#ddl-default-edit .js-ddl-view-select option').each(function () {
                self._views_list_options.push($(this));

                $(this).detach();
            });
        } else if ( self._views_list_options.length > 0 ) {
			// Make sure that Views and WordPress Archives get the right -- Select {object} --- default option
			self._views_list_options[0] = $('#ddl-default-edit .js-ddl-view-select option:first-child');
		}

        $('#ddl-default-edit .js-ddl-view-select option').each(function () {
            $(this).remove();
        });

        for (var i = 0; i < self._views_list_options.length; i++) {
            var mode = self._views_list_options[i].data('mode');

            if (self._cell_type == 'views-content-grid-cell' && mode != 'layouts-loop' && mode != 'archive') {
                $('#ddl-default-edit .js-ddl-view-select').append(self._views_list_options[i].clone());
            }

            if (self._cell_type == 'post-loop-views-cell' && mode != 'normal') {
                $('#ddl-default-edit .js-ddl-view-select').append(self._views_list_options[i].clone());
            }
        }

        $('#ddl-default-edit .js-ddl-view-select').val(selected);

    }

    self._restore_view_selector = function () {

        var selected = $('#ddl-default-edit .js-ddl-view-select option:checked').val();

        $('#ddl-default-edit .js-ddl-view-select option').each(function () {
            $(this).remove();
        });

        for (var i = 0; i < self._views_list_options.length; i++) {
            $('#ddl-default-edit .js-ddl-view-select').append(self._views_list_options[i].clone());
        }

        $('#ddl-default-edit .js-ddl-view-select').val(selected);

    }

    self.iframe_has_closed = function () {
        self._dialog.save_and_close_dialog();
        //$('#ddl-default-edit  .js-save-dialog-settings-and-close').trigger( 'click' );
    }

    self.init();
}

/**
 TODO
 - Remove the trashing button!!!!
 */

DDLayout.ViewsInIfame = function ($) {
    _.extend(DDLayout.ViewsInIfame.prototype, new DDLayout.ToolsetInIfame($, this))

    var self = this;
    var _view_id = null;
    var $save_button = null;
    var iFrameWindow = null;

    self.open_view_in_iframe = function (view_id, cell_type, new_cell, dialog) {
        _view_id = view_id;
        self.set_dialog(dialog);
        if(view_id){
            self.open_in_iframe(cell_type, new_cell);
            self.handle_save_only_button();
        }
        $('#ddl-default-edit .js-close-toolset-iframe-no-save').show();

        iFrameWindow = document.getElementById("ddl-layout-toolset-iframe").contentWindow;
    };

    self.disable_save_button = function (state) {
        if ( state === false ) {
            $('#ddl-default-edit .js-save-toolset-iframe', $(window.top.document) )
                .prop( 'disabled', state === false )
                .addClass( 'button-secondary' )
                .removeClass( 'button-primary' );
        } else {
            $('#ddl-default-edit .js-save-toolset-iframe', $(window.top.document) )
                .prop( 'disabled', state === false )
                .removeClass( 'button-secondary' )
                .addClass( 'button-primary' );
        }
    };

    self.handle_save_only_button = function(){
        $save_button = $('<button disabled class="button button-secondary js-save-toolset-iframe">' + self.get_text('save_no_close') + '</button>')
            .appendTo('#ddl-default-edit .ddl-dialog-footer').css({'float':'right', 'marginRight' : '8px'}).prop('disabled', true);
        jQuery('#ddl-layout-toolset-iframe').on('ddl-layout-toolset-iframe-loaded', function( event, iFrameDoc ){
            var iFrameDocument = iFrameDoc, $button = $('.js-wpv-view-save-all', iFrameDocument );
            $button.hide();
            // we don't need actions bar in Views iFrame
            $("#js-wpv-general-actions-bar", $( iFrameDocument ) ).hide();
            //$save_button.prop( 'disabled', false );
            $save_button.on('click', function(event){
                $button.trigger('click');
            });
        });
    };

    self.get_url = function (cell_type, new_cell) {
        var views_editor_type = ddl_views_1_6_embedded_available ? 'views-embedded' : 'views-editor';
        if (cell_type == 'post-loop-views-cell') {
            views_editor_type = ddl_views_1_6_embedded_available ? 'view-archives-embedded' : 'view-archives-editor';
        }

		var search_result = '';
		if (jQuery('#ddl-default-edit .js-ddl-complete-search').data('view-id') == _view_id) {
			search_result = '&search_result=1';
		}
        return 'admin.php?page=' + views_editor_type + '&view_id=' + _view_id + '&in-iframe-for-layout=1' + search_result;
    }

    self.get_text = function (text_type) {
        switch (text_type) {
            case 'save':
                return DDLayout_settings.DDL_JS.strings.save_and_close_view_iframe;

            case 'close':
                return DDLayout_settings.DDL_JS.strings.close_view_iframe;

            case 'save_no_close':
                return DDLayout_settings.DDL_JS.strings.save_no_close_view_iframe;

            case 'close_no_save':
                return DDLayout_settings.DDL_JS.strings.close_view_iframe_without_save;

        }

        return 'UNDEFINED';
    }

    self.iframe_has_closed = function () {
        // delete the Views cache for peviews
        DDLayout.views_preview.clear_cache(_view_id);
        DDLayout.views_grid.iframe_has_closed();

    }

    self.close_iframe = function (callback) {

        var view_iframe = document.getElementById("ddl-layout-toolset-iframe").contentWindow.DDLayout.layouts_views,
            css = view_iframe.get_css_settings(),
		    parametric = view_iframe.get_parametric_settings(),
            view_name = view_iframe.get_view_name();

        if( typeof DDLayout_settings !== 'undefined' && DDLayout_settings.DDL_JS && DDLayout_settings.DDL_JS.layouts_css_properties ){
            var classes_to_save = _.union( DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses, css.css );
            DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses = classes_to_save;
        }


        if( null === css.css ){
            css.css = [];
        }
            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css.css );
            DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', css.css , jQuery("#ddl-default-edit") );


        jQuery('input[name="ddl-default-edit-css-id"]', jQuery('#ddl-default-edit') ).val(css.id);

        jQuery('#ddl-default-edit').find('select[name="ddl_tag_name"]').val(css.tag).trigger('change');
        jQuery('#ddl-default-edit #ddl-default-edit-cell-name').val(view_name);

		$('#ddl-default-edit input[name="ddl-layout-parametric_mode"], #ddl-default-edit input[name="ddl-layout-parametric_target"]').each( function() {
			$(this).prop( 'checked', false );
		});
		$('#ddl-default-edit input[name="ddl-layout-parametric_mode"][value=' + parametric.mode  + ']').prop( 'checked', true );
		$('#ddl-default-edit input[name="ddl-layout-parametric_mode_target"][value=' + parametric.target  + ']').prop( 'checked', true );
		$('input[name="ddl-layout-parametric_target_id"]').val( parametric.targetid );
		$('input[name="ddl-layout-parametric_target_title"]').val( parametric.targettitle );

        view_iframe.save_view(callback);

    }

    self.enable_ifame_close = function (state) {

        if (!ddl_views_1_6_available) {
            // Save is not required if Views editor is not available
            state = false;
        }

        if (state) {
            $('#ddl-default-edit .js-close-toolset-iframe').html(self.get_text('save'));
            self.disable_save_button( state );
        } else {
            $('#ddl-default-edit .js-close-toolset-iframe').html(self.get_text('close'));
            self.disable_save_button( state );
        }

    };

	self.manage_iframe_close_button = function(state) {
		if ( state ) {
			$('#ddl-default-edit .js-close-toolset-iframe')
				.addClass('button-primary')
				.removeClass('button-secondary')
				.prop('disabled', false);
		} else {
			$('#ddl-default-edit .js-close-toolset-iframe')
				.addClass('button-secondary')
				.removeClass('button-primary')
				.prop('disabled', true);
		}
	};

	// We need to override the default method, since we need a quite specific HTML structure

    self.fetch_extra_controls = function ( div_id ) {
        var data = {};

		data.tag = jQuery('#ddl-default-edit').find('select[name="ddl_tag_name"]').val();
		data.id = jQuery('input[name="ddl-default-edit-css-id"]').val();
		data.css = jQuery('select#ddl-default-edit-class-name.js-toolset-chosen-select').val();
        data.name = jQuery('#ddl-default-edit #ddl-default-edit-cell-name').val();

        var controls = '';
        controls += '<p>' + $('#ddl-default-edit-cell-name').parent().html() + '</p>';
       // controls += $('.js-css-styling-controls').html();
        controls += WPV_Toolset.Utils._template( jQuery('#ddl-styles-extra-controls').html(), data );

		var section = '';
		section += '<div id="views-layouts-div" class="wpv-setting-container js-wpv-settings-views-layouts-div ddl-setting-for-layouts-container-in-iframe not-hidden">';
		section += '<div class="wpv-settings-header"><h3><i class="icon-layouts-logo ont-color-orange ont-icon-24"></i> ' + DDLayout_settings.DDL_JS.strings.cred_layout_css_text + '</h3></div>';
		section += '<div class="wpv-setting js-wpv-setting ddl-form">';
		section += controls;
		section += '</div>';
		section += '</div>';

		data.controls = section;
        return data;
    }

	self.fetch_parametric_extra_controls = function() {
		var data = {};
		if ( $('#ddl-default-edit .js-wpv-ddl-parametric-mode:checked').length > 0 ) {
			data.mode = $('#ddl-default-edit .js-wpv-ddl-parametric-mode:checked').val();
		} else {
			data.mode = 'full';
		}
		if ( $('#ddl-default-edit .js-wpv-ddl-parametric-target:checked').length > 0 ) {
			data.target = $('#ddl-default-edit .js-wpv-ddl-parametric-target:checked').val();
		} else {
			data.target = 'self';
		}
		data.targettitle = $('#ddl-default-edit .js-wpv-widget-form-target-suggest-title').val();
		data.targetid = $('#ddl-default-edit .js-wpv-widget-form-target-id').val();

		data.controls = $('#ddl-default-edit .js-wpv-settings-views-layouts-parametric-extra').html();
		return data;
	};

}


// Create a namespace for the views preview attached to the main object
DDLayout.ViewsGridCellPreview = function () {
	var self = this;

    var views_preview_cache = {};

	self.clear_cache = function (view_id) {
		if (!view_id) {
			views_preview_cache = {};
		} else {
			for (var key in views_preview_cache) {
				if (views_preview_cache.hasOwnProperty(key)) {
					if (key.indexOf(view_id + '-') == 0) {
						delete views_preview_cache[key];
					}
				}
			}
		}
	}

    self.get_preview = function (cell_name, content, error_text, loading_text, preview_image, type) {
        var self = this;
		var content_copy = _.extend({}, content);
		var view_id = content_copy.ddl_layout_view_id;

		var hash_key = view_id + '-' + content_copy.parametric_mode + view_id;

        self.error_text = error_text;
        self.loading_text = loading_text;
        self.preview_image = preview_image;
        self.type = type;

        if (view_id == '') {
            var missing_resource_message = '';
            if(self.type == 'view'){
                missing_resource_message = DDLayout_settings.DDL_JS.strings.views_resource_missing;
            } else {
                missing_resource_message = DDLayout_settings.DDL_JS.strings.views_archive_resource_missing;
            }
            return '<div class="ddl-center-align">'+missing_resource_message+'</div>';

        } else {
            var divclass = 'js-views-content-grid-' + hash_key;
            var divplaceholder = '.' + divclass;

            //Return if view data cached
            if (typeof(views_preview_cache[hash_key]) !== 'undefined' && views_preview_cache[hash_key] != null) {
                var out = '<div class="' + divclass + '">' + views_preview_cache[hash_key] + '</div>';
                return out;
            }

            //If view not cached, get data using Ajax

            var out = '<div class="' + divclass + '">' + self.loading_text + '</div>';

			// See if it's in local storage. We'll display it while we fetch from ajax
			var local_copy = jQuery.jStorage.get('view-preview-' + hash_key, '');
			if (local_copy) {
				out = '<div class="' + divclass + '">' + local_copy + '</div>';
			} else if (self.preview_image) {

				out = '<div class="' + divclass + '"><div class="ddl-views-grid-preview"><img src="' + self.preview_image + '"></div>' + self.loading_text + '</div>';
			}

            if (typeof(views_preview_cache[hash_key]) == 'undefined' || views_preview_cache[hash_key] == null) {

                _.defer( function () {
					views_preview_cache[hash_key] = null;

					var post_data = {
						action: 'ddl_views_content_grid_preview',
						view_id: view_id,
                        cell_type: self.type,
						cell_name: cell_name,
						content: content_copy,
						target_found: DDLayout.ddl_admin_page.get_layout().has_view_target(view_id) ? 'true' : 'false',
						wpnonce: jQuery('#ddl_layout_view_nonce').attr('value')
					};
					jQuery.ajax({
						url: ajaxurl,
						type: 'post',
						data: post_data,
						cache: false,
						success: function (data) {

                            try{
                                var data_object = JSON.parse(data);
                                if(typeof data_object =='object'){

                                    if(data_object.status = 'trash'){
                                        var missing_resource_message = '';
                                        if(data_object.cell_type == 'view'){
                                            missing_resource_message = DDLayout_settings.DDL_JS.strings.views_resource_missing;
                                        } else {
                                            missing_resource_message = DDLayout_settings.DDL_JS.strings.views_archive_resource_missing;
                                        }
                                        var data = '<div class="ddl-center-align">'+missing_resource_message+'</div>';
                                    }
                                }
                            } catch (error){}

							//cache view id data
							if (self.preview_image && data.trim().indexOf('<div class="ddl-parametric-search') != 0) {
								data = '<i class="icon-views-logo ont-color-gray ont-icon-24"></i>' + data;
							}
							views_preview_cache[hash_key] = data;

							var old_data = jQuery.jStorage.get('view-preview-' + hash_key, '');

							if (old_data != data) {

								jQuery.jStorage.set('view-preview-' + hash_key, data);

								jQuery(divplaceholder).html(data);

								// If we have received all the previews we need to refresh
								// the layout display to re-calculate the heights.

								var all_previews_ready = true;
								for (var key in views_preview_cache) {
									if (views_preview_cache.hasOwnProperty(key)) {
										if (views_preview_cache[key] == null) {
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
				});
            }

            return out;
        }
    }
};

jQuery(function ($) {
    DDLayout.views_grid = new DDLayout.ViewsGrid($);
	DDLayout.views_preview = new DDLayout.ViewsGridCellPreview();
});
