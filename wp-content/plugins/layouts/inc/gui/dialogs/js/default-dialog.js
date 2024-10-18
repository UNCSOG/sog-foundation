var DDLayout = DDLayout || {};

// TODO: ADD JS PREFIXES
DDLayout.DefaultDialog = function($)
{
	var self = this, current_cell = null;

    _.extend( DDLayout.DefaultDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

	self.init = function() {
		self._cleanup_required = false;

		self._dialog_defaults = {};
		self._repeating_fields = new DDLayout.DialogRepeatingFields();

		jQuery(document).on('click', '.js-show-cell-dialog', {dialog: self}, function(event) {
			event.preventDefault();
			event.stopImmediatePropagation();

            if(jQuery(this).data("cell-type") === 'child-layout' && DDLayout.local_settings.is_layout_assigned === true){
                self.show_child_layout_pre_create_dialog(event, this, event.data.dialog );
            } else {
                // clean up all create dialog events
                DDLayout.create_cell_helper.show_new_dialog( event, this, event.data.dialog );
            }
		});
        jQuery(document).on('click', '#remove_assignments', function() {
			event.preventDefault();
			event.stopImmediatePropagation();
            self.remove_assignments_for_layout();
            jQuery(".js-where-used-ui").hide(400);
            jQuery("#remove_assignments").remove();
		});


		jQuery(document).on('click', '.js-dialog-edit-save', {dialog: self}, function(event) {
			event.preventDefault();
			event.stopImmediatePropagation();

			var $css_input_id = jQuery( 'input.js-edit-css-id', jQuery(this).parent().parent()),
				value = $css_input_id.val();

			if( DDLayout.ddl_admin_page.htmlAttributesHandler.check_id_exists( $css_input_id, value ) )
			{
				event.data.dialog._save(event.target);
			}

		});

		jQuery(document).on('change', '.js-layout-type-selector', {dialog: self}, function(event) {
			event.preventDefault();
			event.stopImmediatePropagation();
			self._manage_layout_selection();
		});

        // this trigger on any dialog when closes up.
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'color_box_closed', self.generic_dialog_close_callback);
	};

    self.remove_assignments_for_layout = function(){
        var params = {
            ddl_remove_all_layout_association_nonce:jQuery('#ddl_remove_all_layout_association_nonce').val()
            , layout_id: DDLayout_settings.DDL_JS.layout_id
            , action:'remove_all_layout_associations'
        };
        WPV_Toolset.Utils.do_ajax_post(params, {success:function(response){

            DDLayout.local_settings.list_where_used = null;
            DDLayout.PostTypes_Options.is_assigned  = false;
            jQuery( '.assignment-list-in-editor-wrap' ).empty();
        }});
    };


    self.show_child_layout_pre_create_dialog = function( event, handler, dialog ){

        var assignment_options_for_child_cell_dialog = new DDLayout.ViewLayoutManager.DialogView({
                title:  DDLayout_settings.DDL_JS.strings.remove_assignments,
                modal:true,
                dialogClass: 'remove_assignments_dialog',
                width: 300,
                selector: '#ddl-remove-assigments-dialog-tpl',
                buttons: [
                    {
                        class: 'button-primary pull-right',
                        text: DDLayout_settings.DDL_JS.strings.unassign_layout_and_create_child_button,
                        click: function () {
                            // unassign all items
                            self.remove_assignments_for_layout();
                            DDLayout.create_cell_helper.show_new_dialog( event, handler, dialog );
                            jQuery(this).ddldialog("close");
                        }
                    },
                    {
                        icons: {
                            secondary: ""
                        },
                        class: 'pull-left',
                        text: DDLayout_settings.DDL_JS.strings.dismiss_button,
                        click: function () {
                            jQuery(this).ddldialog("close");
                        }
                    },
                ]
            });
            jQuery('.remove_assignments_dialog .ui-dialog-buttonset').css('float','none');

            assignment_options_for_child_cell_dialog.$el.on('ddldialogclose', function (event) {

                assignment_options_for_child_cell_dialog.remove();
            });

            assignment_options_for_child_cell_dialog.dialog_open();


    };

    self.handle_wpml_preferences_change = function(){
			var $radio = jQuery('input[name="ddl-layout-wpml_strings_option"]', jQuery('#ddl-default-edit') ),
                $checked = jQuery('input[name="ddl-layout-wpml_strings_option"]:checked', jQuery('#ddl-default-edit') );

        $checked.prop('checked', true).trigger('change');

        $radio.on('change', function(event){
            if( jQuery(event.target).is(':checked')){
                Toolset.hooks.applyFilters( 'ddl-wpml-translatable-strings-option-changed', jQuery(event.target).val() );
            }
        });
	};

    self.check_if_child_layout_and_assigned = function( data )
    {
        var cell_type = data.cellType;

        if( cell_type !== 'child-layout' || DDLayout.local_settings.is_layout_assigned === false ) return false;

        if( cell_type === 'child-layout' && DDLayout.local_settings.is_layout_assigned === true )
        {
            return true;
        }
        return false;
    };


    self.generic_dialog_close_callback = function( event )
    {
        if( jQuery('.js-element-box-message-container').is('p') )
        jQuery('.js-element-box-message-container').wpvToolsetMessage('destroy');
    };

	self._show_new_dialog = function ( caller, dialog ) {

		self._clear_any_errors();

		var cell_type = jQuery(caller).data('cell-type'),
			main_layout = DDLayout.ddl_admin_page.get_layout(),
			target_cell_view = DDLayout.ddl_admin_page.get_new_target_cell(),
			allow_multiples = jQuery(caller).data('allow-multiple'),
			cell_name = self.create_default_cell_name(jQuery(caller).data('cell-name'), main_layout);

        target_cell_view.model.set( 'displays-post-content', jQuery(caller).data('displaysPostContent')  );

        jQuery('.js-element-box-message-container').wpvToolsetMessage('destroy');

		if (allow_multiples === false) {
			var layout = DDLayout.ddl_admin_page.get_layout();
			if (layout.has_cell_of_type(cell_type)) {
				jQuery('.js-element-box-message-container').wpvToolsetMessage({
					text: DDLayout_settings.DDL_JS.strings.only_one_cell,
					stay: true,
					close: true,
					type: 'info'
				});
				return;
			}
		}


		var dialog_title = jQuery(caller).data('dialog-title-create');
		var cellDescription = function() {
			var desc = jQuery(caller).data('cell-description');
			if ( desc === null ) {
				desc = '';
			}
			return desc;
		}();
		var $editWindow = jQuery('#ddl-default-edit');

        self.init_buttons( $editWindow,'create', jQuery(caller).data() )

		$editWindow
			.data('mode', 'new-cell')
			.data('cell-type', cell_type);

		$editWindow.find('.js-dialog-title').html(dialog_title);

		jQuery('input[name="ddl-default-edit-cell-name"]').val(cell_name);
		//jQuery('select[name="ddl-default-edit-class-name"]').val("").trigger("change");
		jQuery('input[name="ddl-default-edit-css-id"]').val("");
		$editWindow.find('select[name="ddl_tag_name"]').val( 'div' );
        var chosen_context = $editWindow.find('.ddl-fields-container');
		DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', [], chosen_context );

		//self._display_info_box( jQuery(caller).data('cell-name'), cellDescription, cell_type );

		self._set_dialog_content(cell_type);
		//TODO: remove me and change me with KIND
		if ( _.keys(Toolset.hooks.applyFilters('ddl-get_containers_elements') ).indexOf( cell_type )  !== -1 ) {
			self._initialize_container();
		} else {
			if (cell_type in self._dialog_defaults) {
				self._initialize_dialog_from_content(self._dialog_defaults[cell_type]);
			}
		}

		self._show_colorbox();

	};

	self.create_default_cell_name = function (cell_name, layout) {
		var cells = layout.getLayoutCells();

		cell_name = cell_name.split('(')[0]; // remove (...) from cell name
		cell_name = cell_name.trim();

		var test_name = cell_name;

		var count = 2;
		var found = false;

		do {
			found = false;
			for (var i = 0; i < cells.length; i++) {
				var cell = cells[i];
				if (cell.get('name') == test_name) {
					found = true;
					test_name = cell_name + ' ' + count;
					count++;
					break;
				}

			}
		} while (found)

		return test_name;
	};


	self._show_colorbox = function () {
        var windowWidth = jQuery(window).width(), dialog_title = '';

        if ( windowWidth < 1024 ){
            jQuery.colorbox.settings.left='10px';
        }else{
            jQuery.colorbox.settings.left = false;
        }
		jQuery.colorbox({
			href: '#ddl-default-edit',
			closeButton: false,
			escKey : false,
            overlayClose: false,
			onComplete: function() {

				self._cleanup_required = true;

				self._repeating_fields.initialize_events();

				if ( jQuery('#ddl-default-edit .js-toggle-front-end-options').data('expanded') ) {
					jQuery('#ddl-default-edit .js-toggle-front-end-options').trigger('click');
				}

				if (jQuery('#ddl-default-edit [name="ddl-layout-content"]').length) {
                    jQuery("#celltexteditor").css("visibility", "visible");
                } else if (jQuery('#ddl-default-edit [name="ddl-layout-post-content"]').length) {
                    jQuery("#cell-post-content-editor").css("visibility", "visible");
                }

				self.disable_save_button(false); // just in case it's disabled.

				// Apply 'button-primary' class, because in case of alone child cell, this class is removed and button is diabled.
				jQuery('#ddl-default-edit .js-dialog-edit-save').addClass('button-primary');

				jQuery('#ddl-default-edit').trigger('ddl-default-dialog-open');


				var val = 'cell';
                //TODO: eventually change next if with this:
               // _.keys(Toolset.hooks.applyFilters('ddl-get_containers_elements') ).indexOf( self._cell_type )  !== -1
				if ( self._cell_type == 'ddl-container') {
					val = 'row';
				}
				jQuery('.js-change-name').each(function(){
					var $this = jQuery(this);
					$this.text( $this.data( val ) );
				});

				// Tabs
				//jQuery('.js-popup-tabs').tabs( ); // Initialize tabs

				//jQuery('.js-popup-tabs').tabs( 'option', 'active', 0 ); // Activate the first tab

				jQuery.colorbox.resize();

				self._fire_event('dialog-open');
                if ( windowWidth < 1024 ){
                    var editSize = windowWidth;

                    jQuery('#ddl-default-edit').css({width: (editSize-20)+'px'});

                }

                self.handle_wpml_preferences_change();
			},
			onLoad: function()
			{
				if (jQuery('#ddl-default-edit [name="ddl-layout-content"]').length) {
					jQuery("#celltexteditor").focus();
				} else if (jQuery('#ddl-default-edit [name="ddl-layout-post-content"]').length) {
                    jQuery("#cell-post-content-editor").focus();
                }

                dialog_title = jQuery('#ddl-default-edit .js-dialog-title').text();

                if( current_cell && current_cell instanceof DDLayout.views.abstract.ElementView ){
                    current_cell.model.trigger( 'ddl_layouts_element_dialog_loads', current_cell );
                }
			},
			onCleanup: function () {
                jQuery('#ddl-default-edit .js-dialog-title').text( dialog_title );
				self.clean_up();
			},
            onClosed: function () {
                self._fire_event('dialog-closed');
            }
		});

	};

	self.clean_up = function () {
		if (self._cleanup_required) {

			//prevent tinyMCE to bother when dialog opens
			if (jQuery('#ddl-default-edit [name="ddl-layout-content"]').length) {
				if( typeof tinyMCE !== 'undefined' && tinyMCE.get("celltexteditor") ) {
					tinyMCE.get("celltexteditor").remove();
				}
			} else if (jQuery('#ddl-default-edit [name="ddl-layout-post-content"]').length) {
                if( typeof tinyMCE !== 'undefined' && tinyMCE.get("cell-post-content-editor") ) {
                    tinyMCE.get("cell-post-content-editor").remove();
                }
            }

			jQuery('#ddl-default-edit .ddl-dialog-content .js-default-dialog-content').children().appendTo('#ddl-cell-dialog-' + self._cell_type);

			self._fire_event('dialog-close');

			self._repeating_fields.close_events();
			self._cleanup_required = false;
			self.hide_save_button(false);

		}
	};

	self._set_dialog_content = function (cell_type) {
		if( typeof tinyMCE !== 'undefined' && tinyMCE.get("celltexteditor") ) {
			tinyMCE.get("celltexteditor").remove();
		} else if( typeof tinyMCE !== 'undefined' && tinyMCE.get("cell-post-content-editor") ){
            tinyMCE.get("cell-post-content-editor").remove();
		}

		self._cell_type = cell_type;

		jQuery('#ddl-default-edit .ddl-dialog-content .js-default-dialog-content').empty();

		jQuery('#ddl-cell-dialog-' + cell_type).children().appendTo('#ddl-default-edit .ddl-dialog-content .js-default-dialog-content');

		if (!(cell_type in self._dialog_defaults)) {
			self._dialog_defaults[cell_type] = self._get_content_from_dialog();
		}

	};



	self.show = function(mode, cell_view) {

		self._clear_any_errors();

		if ( mode === 'edit' ) {

            current_cell = cell_view;

			var row = cell_view.get_parent_view(),
				row_model = row ? row.model : null,
			    has_child_layout_alone = row_model ? self.row_doesnt_render_in_front_end( row_model ) : true;

			if( cell_view.model.get('cell_type') === 'child-layout' && has_child_layout_alone ){
				self.disable_enable_editing_elements_in_css_tab(true);
			} else {
				self.disable_enable_editing_elements_in_css_tab(false);
			}

			var $editWindow = jQuery('#ddl-default-edit'),
			   cellName = cell_view.model.get('name'),
			   cell_type = cell_view.model.get('cell_type'),
			   cellSettings = jQuery('[data-cell-type="' + cell_type + '"]'),
			   dialog_title = DDLayout_settings.DDL_JS.cells_data[cell_type]['dialog-title-edit'],
			   cellDescription = function() {
				var desc = cellSettings.data('cell-description');
				if ( desc === null ) {
					desc = '';
				}
				return desc;
			}();

			var content = cell_view.model.get('content');

                 self.setCachedElement( cell_view.model.toJSON() );

			$editWindow
				.data('mode', 'edit-cell')
				.data('cell_view', cell_view)
				.data('cell-type', cell_type);

            self.init_buttons( $editWindow, mode, cellSettings.data() );

			jQuery('input[name="ddl-default-edit-cell-name"]').val( cellName );

			jQuery('input[name="ddl-default-edit-css-id"]').val( cell_view.model.get('cssId') );
			$editWindow.find('select[name="ddl_tag_name"]').val( cell_view.model.get('tag') ).trigger('change');
			$editWindow.find('.js-dialog-title').html(dialog_title);


			self._set_dialog_content(cell_type);

			// initialize the content
			self._initialize_dialog_from_content(content);

			_.defer(function(){
				var $save_and_close_button = $editWindow.find('.js-save-dialog-settings-and-close');
                var chosen_context = $editWindow.find('.ddl-fields-container');

				if( has_child_layout_alone ) {
					$save_and_close_button.removeClass('button-primary').prop('disabled', true);
				} else {
					$save_and_close_button.addClass('button-primary').prop('disabled', false);
				}

				var saved_css_classes = cell_view.model.get('additionalCssClasses');
				var array_with_classes = ( saved_css_classes != null ? saved_css_classes.split(" ") : saved_css_classes );

				DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', array_with_classes );
				DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', array_with_classes, chosen_context );

			});

		}

		self._show_colorbox();
	};

    self._save = function (caller) {

        var cell_type = jQuery('#ddl-default-edit').data('cell-type');

        var target_cell_view = null;

        if (self.is_new_cell()) {

            target_cell_view = DDLayout.ddl_admin_page.get_new_target_cell();

            jQuery('#ddl-default-edit').data('cell_view', target_cell_view);

        } else if (jQuery('#ddl-default-edit').data('mode') == 'edit-cell') {

            target_cell_view = jQuery('#ddl-default-edit').data('cell_view');

        }

        if (target_cell_view) {

            var target_cell = target_cell_view.model;

            if ( _.keys(Toolset.hooks.applyFilters('ddl-get_containers_elements') ).indexOf( cell_type )  !== -1 ) {
                self._handle_container_save( target_cell_view, cell_type );

            } else {

                DDLayout.ddl_admin_page.save_undo();

                Toolset.hooks.applyFilters('ddl-layouts-before-cell-model-set', target_cell, self.cached_element, this);

                target_cell.set('cell_type', cell_type);
                target_cell.set('editorVisualTemplateID', cell_type + '-template');

                target_cell.set('name', jQuery('input[name="ddl-default-edit-cell-name"]').val());

                target_cell.set('cssId', jQuery('input[name="ddl-default-edit-css-id"]').val());
                target_cell.set('tag', jQuery('#ddl-default-edit select[name="ddl_tag_name"]').val());
                target_cell.set('row_divider', target_cell_view.model.get('row_divider'));

                var css_classes_tosave = jQuery('select[name="ddl-default-edit-class-name"]', jQuery("#ddl-default-edit")).val();
                _.delay(function(){
                    target_cell.set('additionalCssClasses', (css_classes_tosave != null ? css_classes_tosave.join(',') : ""));
                }, 20 );

				if( typeof DDLayout_settings !== 'undefined' &&
					css_classes_tosave !== null &&
					Array.isArray( css_classes_tosave ) === true &&
					DDLayout_settings.DDL_JS &&
					Array.isArray( DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses ) === true )
				{
					var all_classes = DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses.concat( css_classes_tosave );
					DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses = all_classes.filter(function (item, pos) {return all_classes.indexOf(item) == pos});
				}

				self._content = self._get_content_from_dialog();

                self._fire_event('get-content-from-dialog');

				//TODO:this is actually an experiment to be tested and retested
                if( target_cell.get('content') ){
                    target_cell.set('content', _.extend( target_cell.get('content'), self._content) );
                } else {
                    target_cell.set('content', self._content );
                }

                //target_cell_view.selectElement();
                target_cell.selected_cell = true;

                if (self.is_new_cell()) {
                    var width = DDLayout.ddl_admin_page._add_cell.getColumnsToAdd();
                    DDLayout.ddl_admin_page.replace_selected_cell(target_cell, width);

                    DDLayout.ddl_admin_page.instance_layout_view.model.trigger('created_new_cell', target_cell);

                } else {

                   if(  _.isEqual( self.cached_element, target_cell.toJSON() ) === false ) {
                       DDLayout.ddl_admin_page.replace_selected_cell( target_cell, 0, true );
                   }

                }

            }

            target_cell = Toolset.hooks.applyFilters('ddl-layouts-before-cell-save', target_cell, self.cached_element, this);
			DDLayout.ddl_admin_page.trigger( 'layout_element_model_changed_from_dialog', caller, target_cell_view, self.cached_element, false, self );
		}


        if ( self.is_save_and_close(caller) ) {
            jQuery.colorbox.close();
        }

        return false;
    };



	self.is_new_cell = function () {
		return jQuery('#ddl-default-edit').data('mode') == 'new-cell';
	};


	self._get_content_from_dialog = function () {
		var content = {};

		jQuery('#ddl-default-edit [name^="ddl-layout-"]').each( function (){
			var data = jQuery(this).attr('name');
			data = data.substr(11);

			var array_data = false;
			if (data.substr(data.length - 2, 2) == '[]') {
				array_data = true;
				var data_key = data.substr(0, data.length - 2);
				var group_name_match = /\[(.*?)\]/.exec(data_key);
				var array_data_name = group_name_match[1];
				data_key = data_key.substr(array_data_name.length + 2);

				if (typeof content[array_data_name] == 'undefined') {
					content[array_data_name] = Array();
				}
			}
			switch (jQuery(this).attr('type')) {
				case 'checkbox':
					if (array_data) {
						self._repeating_fields.get_array_content_from_dialog(content, array_data_name, data_key, jQuery(this).is(':checked'));
					} else {
						content[data] = jQuery(this).is(':checked');
					}
					break;

				case 'radio':
					if (jQuery(this).is(':checked')) {
						if (array_data) {
							self._repeating_fields.get_array_content_from_dialog(content, array_data_name, data_key, jQuery('#ddl-default-edit [name="ddl-layout-' + data + '"]:checked').val());
						} else {
							content[data] = jQuery('#ddl-default-edit [name="ddl-layout-' + data + '"]:checked').val();
						}
					}
					break;

				default:

                    // FIXME: Find the difference between hidden unique_id field and content field

                    if( 'unique_id' === data ) {
                        content[data] = jQuery(this).val();
                        break;
                    }

					var data_val = '';

                    if( DDLayout.text_cell
                            && DDLayout.text_cell.editor
                            && DDLayout.text_cell.editor.current ) {
                        // Get the content from Text Cell current editor (TinyMCE or CodeMirror)
                        data_val = DDLayout.text_cell.editor.get_content();
                    } else if ( jQuery(this).hasClass('wp-editor-area')
							&& typeof tinyMCE !== 'undefined'
                            && 'celltexteditor' in tinyMCE.editors ) {
                        // Get content from TinyMCE Visual editor
						if ( tinyMCE.editors['celltexteditor'].isHidden() ) {
							data_val = jQuery(this).val();
						} else {
							data_val = tinyMCE.editors['celltexteditor'].save();
						}
                    } else {
						data_val = jQuery(this).val();
					}

					data_val = _strip_scripts(data_val);

					if (array_data) {
						self._repeating_fields.get_array_content_from_dialog(content, array_data_name, data_key, data_val);
					} else {
						content[data] = data_val;
					}
					break;
			}
		});

		return content;
	};

    // http://stackoverflow.com/questions/6659351/removing-all-script-tags-from-html-with-js-regular-expression
	var _strip_scripts = function (data) {
        return WPV_Toolset.Utils._strip_scripts( data );
	};

	self._initialize_dialog_from_content = function (content) {

        if( typeof content === 'undefined' ) return;

		self._content = content;

		self._repeating_fields.initilize_from_content(content, self);

		jQuery('#ddl-default-edit [name^="ddl-layout-"]').each( function (){
			var data = jQuery(this).attr('name');
			data = data.substr(11);
			if (self._repeating_fields.not_repeating(data)) {
				self.set_element_value(this, content[data]);
			}
		});

        // Synchronize *disable_auto_p* and TinyMCE's *wpautop* on checkbox change
        jQuery( '#ddl-layout-disable_auto_p' ).off('change');
        jQuery( '#ddl-layout-disable_auto_p' ).change( function( ev ) {

            if( ! jQuery( this ).is( ':checked') ) {
                DDLayout.text_cell.editor.set_content( window.switchEditors.wpautop( window.switchEditors.pre_wpautop( DDLayout.text_cell.editor.get_content() ) ) );
            }

        } );

		self._fire_event('init-dialog-from-content');
	};

	self.set_element_value = function(element, value) {

		if (typeof value !== 'undefined') {
			switch (jQuery(element).attr('type') ) {
				case 'checkbox':
					if (((typeof value == 'string') && (value == 'true')) || value === true) {
						jQuery(element).prop('checked', true);
					}
					else if( ((typeof value == 'string') && (value == 'false')) || value === false )
					{
						jQuery(element).prop('checked', false);
					}
					break;

				case 'radio':
					if (jQuery(element).val() == value) {
						jQuery(element).prop('checked', true);
					}
					else
					{
						jQuery(element).prop('checked', false);
					}
					break;

				default:
					jQuery(element).val(value);
					break;
			}
		}

	};
	self._handle_container_save = function(target_cell_view, cell_type) {

		DDLayout.ddl_admin_page.save_undo();

		var number_of_rows = 1,
			layout_type = jQuery('.js-layout-type-selector:checked').val(),
			container_width = DDLayout.ddl_admin_page._add_cell.getColumnsToAdd(),
			row_divider = 1,
			models = Toolset.hooks.applyFilters('ddl-get_containers_elements'),
            model = models[cell_type],
            container = new DDLayout.models.cells[model]({
				name : jQuery('input[name="ddl-default-edit-cell-name"]').val(),
				cssClass : "",
				kind : model,
				width : container_width
			}),
			container_columns = container_width,
			$grid = null;

		container = Toolset.hooks.applyFilters('ddl-layouts-before-container-model-set', container, container_columns, container_columns, this);

		if (layout_type === 'fluid') {
			$grid = jQuery('#js-fluid-grid-designer');
			number_of_rows = Toolset.hooks.applyFilters( 'ddl-container_number_of_rows', $grid.data('rows'), container );
			container_columns = Toolset.hooks.applyFilters( 'ddl-container_container_columns', $grid.data('max-cols'), container );
			row_divider = Toolset.hooks.applyFilters( 'ddl-container_row_divider', $grid.data('max-cols') / $grid.data('cols'), container );
		}
		else if (layout_type === 'fixed') {
			$grid = jQuery('#js-fixed-grid-designer');
			number_of_rows = $grid.data('rows');
			container_columns *= target_cell_view.model.get('row_divider');
		}

		container.addRows(number_of_rows, container_columns, layout_type, row_divider);
		container.set('additionalCssClasses', jQuery('select[name="ddl-default-edit-class-name"]', jQuery("#ddl-default-edit")).val());
		container.set('cssId', jQuery('input[name="ddl-default-edit-css-id"]').val());
		container.set('tag', jQuery('#ddl-default-edit select[name="ddl_tag_name"]').val());
		container.set('row_divider', target_cell_view.model.get('row_divider'));


        var css_classes_tosave = jQuery('select[name="ddl-default-edit-class-name"]', jQuery("#ddl-default-edit")).val();
        setTimeout(function(){
            container.set('additionalCssClasses', (css_classes_tosave != null ? css_classes_tosave.join(',') : ""));
        }, 20);


		DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css_classes_tosave );

		var target_cell = Toolset.hooks.applyFilters('ddl-layouts-before-cell-save', target_cell_view.model, container, this);
		target_cell.selected_cell = true;

		DDLayout.ddl_admin_page.replace_selected_cell(container, container_width);
	};

	self._initialize_container = function() {

		var main_layout = DDLayout.ddl_admin_page.get_layout();
		var target_cell_view = DDLayout.ddl_admin_page.get_new_target_cell();

		var target_row_view = target_cell_view.get_parent_view();
		var allow_fixed = target_row_view.model.get('layout_type') == 'fixed';
		if (allow_fixed && !target_row_view.can_add_fixed_row_below_this()) {
			allow_fixed = false;
		}

		var layout_type_select = jQuery('.js-layout-type-selector');
		var $message = jQuery('.js-diabled-fixed-rows-info');

		layout_type_select.prop('checked', false); // reset selection

		if (allow_fixed) {
			layout_type_select.prop('disabled', false);
			jQuery('.js-layout-type-selector-fixed').prop('checked', true);
			$message.hide();
		}
		else {
			layout_type_select.prop('disabled', true);
			jQuery('.js-layout-type-selector-fluid').prop('checked', true);
            jQuery('.js-layout-type-selector').val('fluid');
			$message.show();
		}

		self._manage_layout_selection();
	};

	self._manage_layout_selection = function () {

		var layout_type = jQuery('.js-layout-type-selector:checked').val();
		var numberOfColumns = DDLayout.ddl_admin_page._add_cell.getColumnsToAdd();
		var $fluidGrid = jQuery('.js-fluid-grid-designer');
		var $fixedGrid = jQuery('.js-fixed-grid-designer');

		if (layout_type === 'fluid') {
			$fluidGrid.show();
			$fixedGrid.hide();
			// Do not asign #js-fluid-grid-designer' to a variable because 'destroy' method removes DOM element
			jQuery('#js-fluid-grid-designer').ddlDrawGrid('destroy');
			jQuery('#js-fluid-grid-designer').ddlDrawGrid();

			jQuery('.js-grid-fixed-message').hide();
		}
		else if (layout_type === 'fixed') {
			$fluidGrid.hide();
			$fixedGrid.show();
			jQuery('#js-fixed-grid-designer').ddlDrawGrid('destroy');
			jQuery('#js-fixed-grid-designer').ddlDrawGrid({
				cols: numberOfColumns,
				maxCols: numberOfColumns
			});

			jQuery('.js-grid-fixed-message').show();
		}

	};

	self._clear_any_errors = function () {
        //
    };

	self._fire_event = function (name) {
		var event_name = self._cell_type + '.' + name;
		jQuery(document).trigger(event_name, [self._content, self]);
	};

	self._display_info_box = function( header, content, type ) {
		jQuery("#js-info-box-container").html('');
		if ( jQuery.jStorage.get( 'info-box' + type ) !== 'disabled' ) {

			var template = jQuery("#js-info-box").html();
			jQuery("#js-info-box-container").html( WPV_Toolset.Utils._template( template, {
				header: header,
				content: content,
				type: type
			}));

			jQuery('.js-remove-info-box').on( 'click', function() {
				var $box = jQuery( '.js-info-box' ).filter( function() {
					return jQuery(this).data( 'cell-type' ) === type;
				});
				$box.fadeOut( 'fast', function() {
					$box.remove();
					jQuery.jStorage.set( 'info-box' + type, 'disabled' );
				});
			});

		}

	};

    self.get_target_cell_view = function () {
        return jQuery('#ddl-default-edit').data('cell_view');
    }

    self.get_cell_type = function () {
        return self._cell_type;
    }

	self.disable_save_button = function (state) {
		jQuery('#ddl-default-edit .js-dialog-edit-save').prop('disabled', state);
	}

	self.disable_cancel_button = function (state) {
		jQuery('#ddl-default-edit .js-edit-dialog-close').prop('disabled', state);
	}

	self.hide_save_button = function (state) {
		if (state) {
			jQuery('#ddl-default-edit .js-dialog-edit-save').hide();
		} else {
			jQuery('#ddl-default-edit .js-dialog-edit-save').show();
		}

	}

	self.insert_spinner_before = function (element) {
		return jQuery('<div class="spinner ajax-loader"></div>').insertBefore(element).show();
	};

	self.insert_spinner_after = function (element) {
		return jQuery('<div class="spinner ajax-loader"></div>').insertAfter(element).show();
	};

	self.insert_spinner_absolute = function (element, rules) {
		return jQuery('<div class="spinner ajax-loader"></div>').insertBefore(element).show().css(rules);
	};

	self.insert_spinner_after_absolute = function (element, rules) {
		return jQuery('<div class="spinner ajax-loader"></div>').insertAfter(element).show().css(rules);
	};

	self.save_and_close_dialog = function () {
		self.disable_save_button(false);
		jQuery('.js-dialog-edit-save').trigger('click');
	};

	self.init();
};

DDLayout.DefaultDialog.ManageParentCandidateAlreadyAssigned = function(model, dialog, caller)
{
    var self = this
        , $button = jQuery('.js-add-child-layout-and-remove-association')
        , layout = model, $assignment_box = jQuery('.js-where-used-ui');

        self.init = function()
        {
            jQuery(document).on('click', '.js-add-child-layout-and-remove-association', function(event){
                self.unassign_layout( )
            } );
        };

        self.unassign_layout = function(  )
        {
            var params = {
                ddl_remove_all_layout_association_nonce:jQuery('#ddl_remove_all_layout_association_nonce').val()
                , layout_id: model.get('id')
                , action:'remove_all_layout_associations'
            };

            WPV_Toolset.Utils.loader.loadShow( $button, true).addClass('float-left');

            WPV_Toolset.Utils.do_ajax_post(params, {success:function(response){
                WPV_Toolset.Utils.loader.loadHide();
                $assignment_box.fadeOut(400, function(e){
                    jQuery( this ).remove();
                    dialog._show_new_dialog( caller );
                });
            }});
        };

    self.init();
};
