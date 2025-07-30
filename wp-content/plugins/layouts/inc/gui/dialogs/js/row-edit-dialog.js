var DDLayout = DDLayout || {};

DDLayout.RowDialog = function($, row_view)
{
    var self = this;

    self.row_view = row_view;
	
	self.row_modes_by_layout_kind = {
		'normal': [],
		'private': [],
		'all': []
	};

    _.extend( DDLayout.RowDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

    self.init = function() {
		
		self.cache_row_modes_by_layout_kind();
         
        jQuery(document).on('click', '.js-row-dialog-edit-save,.js-row-dialog-edit-add-row', {dialog: self}, function(event) {
            event.preventDefault();

	        var $css_input_id = jQuery( 'input.js-edit-css-id', jQuery(this).parent().parent()),
		        value = $css_input_id.val();

	        if( DDLayout.ddl_admin_page.htmlAttributesHandler.check_id_exists( $css_input_id, value ) )
	        {
                event.data.dialog._save( jQuery(this) );
	        }
        });

        jQuery(document).on('click', '#ddl-row-edit .js-ddl-show', {dialog: self}, function(event) {
            event.preventDefault();
            jQuery('.js-front-end-options').show();
            jQuery('.js-ddl-show').hide();
            jQuery('.js-ddl-hide').show();

        });

        jQuery(document).on('click', '#ddl-row-edit .js-ddl-hide', {dialog: self}, function(event) {
            event.preventDefault();
            jQuery('.js-front-end-options').hide();
            jQuery('.js-ddl-show').show();
            jQuery('.js-ddl-hide').hide();

        });
		
		jQuery( document ).on('click', 'ul.presets-list.row-types figure', function(event) {
            
            var selected_row_type = jQuery(this).children('img').data('name'),
				layout_kind = self.get_layout_kind();
            if(selected_row_type === 'row-full-width'){
                jQuery('.default_container_padding_label').fadeOut(200);
				if ( layout_kind == 'private' ) {
					jQuery( '.js-ddl-container-private-warning' ).fadeOut(200);
				}
            } else {
                jQuery('.default_container_padding_label').fadeIn(200);
				if ( layout_kind == 'private' ) {
					jQuery( '.js-ddl-container-private-warning' ).fadeIn(200);
				}
            }
            
			jQuery(document).find( 'ul.presets-list.row-types figure' ).each( function () {
				jQuery(this).removeClass('selected');
			})
			jQuery(this).addClass('selected');

			var radio = jQuery(this).closest('li').find('input[name="row_type"]');
			radio.trigger('click');

		});

    };
	
	self.cache_row_modes_by_layout_kind = function() {
		jQuery( 'ul.presets-list.row-types li' ).each( function() {
			var row_type_li = jQuery( this );
			if ( typeof row_type_li.data('layout-type') !== 'undefined' ) {
				var row_type_li_for = row_type_li.data('layout-type');
				self.row_modes_by_layout_kind[ row_type_li_for ].push( '<li>' + row_type_li.html() + '</li>' );
			} else {
				self.row_modes_by_layout_kind['all'].push( '<li>' + row_type_li.html() + '</li>' );
			}
		});
		self.row_modes_by_layout_kind = Toolset.hooks.applyFilters( 'ddl-set-row-modes-by-layout-type', self.row_modes_by_layout_kind );
	};

    self.show = function(mode, row_view) {

        self.setCachedElement( row_view.model.toJSON() );

        var has_child_layout_alone = self.row_doesnt_render_in_front_end( row_view.model ),
			layout_kind = self.get_layout_kind();

        if( has_child_layout_alone ){
            jQuery('.ddl-tab-right').hide();
        } else {
            jQuery('.ddl-tab-right').show();
        }
		
		self._adjust_dialog_rows_for_layout_kind( layout_kind );

        jQuery('.js-edit-dialog-close').css('float', 'left');
		
		jQuery('.default_container_padding_label').hide();
		jQuery( '.js-ddl-container-private-warning' ).hide();

		if (row_view.is_top_level_row() && has_child_layout_alone === false ) {
			jQuery('#js-row-edit-mode').show();
            jQuery('#js-row-not-render-message').hide();

            jQuery('.js-css-styling-controls').removeClass('from-top-0').show();
            jQuery('.ddl-dialog-content').removeClass('pad-top-0');
            jQuery('.js-ddl-form-row').removeClass('ddl-zero');

		} else if( row_view.is_top_level_row() && has_child_layout_alone ) {
            jQuery('#js-row-edit-mode').hide();
            jQuery('#js-row-not-render-message').show();
            jQuery('.js-css-styling-controls').hide();
            jQuery('.ddl-dialog-content').removeClass('pad-top-0');
            jQuery('.js-ddl-form-row').removeClass('ddl-zero');
            jQuery('.js-row-dialog-edit-save').removeClass('button-primary').prop('disabled', true);
        } else {
			jQuery('#js-row-edit-mode').hide();
            jQuery('#js-row-not-render-message').hide();

            jQuery('.ddl-dialog-content').addClass('pad-top-0');
            jQuery('.js-css-styling-controls').addClass('from-top-0').css('border-top', 0).show();
            jQuery('.js-ddl-form-row').addClass('ddl-zero');
		}

        if (mode == 'edit') {

            // hide padding checkbox by default if selected row type is not "normal-row"
            if( row_view.model.get('mode') !== 'full-width' ) {
                jQuery('.default_container_padding_label').show();
				if ( layout_kind == 'private' ) {
					jQuery( '.js-ddl-container-private-warning' ).show();
				}
            }
            
            jQuery('#ddl-row-edit').data('mode', 'edit-row');
            jQuery('#ddl-row-edit').data('row_view', row_view);

            jQuery('input[name="ddl-row-edit-row-name"]').val(row_view.model.get('name'));
	        jQuery('input.js-edit-css-id', jQuery('#ddl-row-edit') ).val( row_view.model.get('cssId') );
            jQuery('#ddl-row-edit select[name="ddl_tag_name"]').val( row_view.model.get('tag') ).trigger('change');
            
            if(row_view.model.get('containerPadding') === true || typeof row_view.model.get('containerPadding') === 'undefined'){
                jQuery('#ddl-row-edit input[name="ddl_container_padding"]').prop('checked', true);
            } else {
                jQuery('#ddl-row-edit input[name="ddl_container_padding"]').prop('checked', false);
            }
            
            jQuery('#ddl-row-edit .js-dialog-edit-title').show();
            jQuery('#ddl-row-edit .js-row-dialog-edit-save').show();

            jQuery('#ddl-row-edit .js-dialog-add-title').hide();
            jQuery('#ddl-row-edit .js-row-dialog-edit-add-row').hide();

	        jQuery('#ddl-row-edit #ddl-row-edit-layout-type').parent().hide();


            var saved_css_classes = row_view.model.get('additionalCssClasses');
            var array_with_classes = ( saved_css_classes != null ? saved_css_classes.split(" ") : saved_css_classes );

            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', array_with_classes );
            DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', array_with_classes );



			self._set_row_mode(row_view.model.get('mode'));

        } else if (mode == 'add') {
            jQuery('#ddl-row-edit').data('mode', 'add-row');
            jQuery('#ddl-row-edit').data('row_view', row_view);

            jQuery('input[name="ddl-row-edit-row-name"]').val('');
            jQuery('input[name="ddl-row-edit-row-class-name"]').val('');
			jQuery('input[name="ddl-row-edit-css-id"]').val('');
            jQuery('#ddl-row-edit select[name="ddl_tag_name"]').val('div');

            jQuery('#ddl-row-edit .js-dialog-edit-title').hide();
            jQuery('#ddl-row-edit .js-row-dialog-edit-save').hide();

            jQuery('#ddl-row-edit .js-dialog-add-title').show();
            jQuery('#ddl-row-edit .js-row-dialog-edit-add-row').show();


            jQuery('#ddl-row-edit #ddl-row-edit-layout-type').parent().show();

            if (!row_view.can_add_fixed_row_below_this()) {
                // disable layout selection
                jQuery('#ddl-row-edit #ddl-row-edit-layout-type').val('fluid');
                jQuery('#ddl-row-edit #ddl-row-edit-layout-type').prop('disabled', 'disabled');
                jQuery('.js-only-fluid-message').show();
            } else {
                jQuery('#ddl-row-edit #ddl-row-edit-layout-type').prop('disabled', false);
                jQuery('.js-only-fluid-message').hide();
            }

			var default_mode = ( layout_kind == 'private' ) ? 'full-width' : 'normal';
			default_mode = Toolset.hooks.applyFilters( 'ddl-set-row-default-mode', default_mode, layout_kind );
			self._set_row_mode( default_mode );

        }

        jQuery.colorbox({
            href: '#ddl-row-edit',
            closeButton:false,
            onLoad:function(){

            },
            onComplete: function() {
                Toolset.hooks.doAction(self.row_view.model.get('kind') +'.dialog_open', self.row_view.model);
            },
            onCleanup:function(){
                jQuery('.js-css-styling-controls').removeClass('from-top-0').show();
                jQuery('.ddl-dialog-content').removeClass('pad-top-0');
                jQuery('.js-ddl-form-row').removeClass('ddl-zero');
                jQuery('.js-row-dialog-edit-save').addClass('button-primary').prop('disabled', false);
                Toolset.hooks.doAction(self.row_view.model.get('kind') +'.dialog_close', self.row_view.model);
            }
        });
    };
	
	self._adjust_dialog_rows_for_layout_kind = function( layout_kind ) {
		
		jQuery( 'ul.presets-list.row-types li' ).remove();
		
		switch ( layout_kind ) {
			case 'private':
				jQuery.each( self.row_modes_by_layout_kind['private'], function( index, item ) {
					jQuery( 'ul.presets-list.row-types' ).append( jQuery( item ) );
				});
				break;
			case 'normal':
			default:
				jQuery.each( self.row_modes_by_layout_kind['normal'], function( index, item ) {
					jQuery( 'ul.presets-list.row-types' ).append( jQuery( item ) );
				});
				break;
		}
		
		jQuery.each( self.row_modes_by_layout_kind['all'], function( index, item ) {
			jQuery( 'ul.presets-list.row-types' ).append( jQuery( item ) );
		});
		
	};


    self._save = function (caller) {

        var target_row_view = jQuery('#ddl-row-edit').data('row_view');

        if (jQuery('#ddl-row-edit').data('mode') == 'add-row') {

            var layout_type = jQuery('select[name="ddl-row-edit-layout-type"]').val();
            target_row_view.addRow(jQuery('input[name="ddl-row-edit-row-name"]').val(),
                                   jQuery('input[name="ddl-row-edit-row-class-name"]').val(),
                                   layout_type);

        } else if (jQuery('#ddl-row-edit').data('mode') == 'edit-row') {


            DDLayout.ddl_admin_page.save_undo();

            var target_row = target_row_view.model;
            
            target_row.set('name', jQuery('input[name="ddl-row-edit-row-name"]').val());
            var css_classes_tosave = ( jQuery('select.js-toolset-chosen-select', jQuery('#ddl-row-edit') ).val());
            target_row.set('additionalCssClasses', (css_classes_tosave != null ? css_classes_tosave.join(",") : "") );
            target_row.set('cssId', jQuery('input.js-edit-css-id', jQuery('#ddl-row-edit') ).val() );
            target_row.set('tag', jQuery('#ddl-row-edit select[name="ddl_tag_name"]').val() );
            target_row.set('containerPadding', jQuery('#ddl-row-edit input[name="ddl_container_padding"]').prop('checked') );
			target_row.set( 'mode', self._get_row_mode() );


            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css_classes_tosave );
            DDLayout.ddl_admin_page.trigger( 'layout_element_model_changed_from_dialog', caller, target_row_view, self.cached_element, false, self );

        }

        if( self.is_save_and_close(caller) ) jQuery.colorbox.close();
        return false;
    };

	self._set_row_mode = function (mode) {
		jQuery('#ddl-row-edit input[name="row_type"]').each( function () {
			var figure = jQuery(this).closest('li').find('figure');
			if (jQuery(this).val() == mode) {
				jQuery(this).prop('checked', true);
				figure.addClass('selected');
			} else {
				jQuery(this).prop('checked', false);
				figure.removeClass('selected');
			}
		});
	}
	
	self.get_layout_kind = function() {
		var layout_kind = 'normal';

        if( self.is_integrated_theme() === false ){
            return 'private';
        }

		try {
			layout_kind = DDLayout.ddl_admin_page.instance_layout_view.model.get('layout_type');
		} catch( e ) {
			layout_kind = 'normal';
		}

		return layout_kind;
	};

	self._get_row_mode = function () {
		var layout_kind = self.get_layout_kind(),
			mode = layout_kind == 'private' ? 'full-width' : 'normal';
		mode = Toolset.hooks.applyFilters( 'ddl-set-row-default-mode', mode, layout_kind );
		jQuery('#ddl-row-edit input[name="row_type"]').each( function () {
			if (jQuery(this).is(':checked')) {
				mode = jQuery(this).val();
			}
		});

		return mode;
	};

	self.is_integrated_theme = function(){
        return DDLayout.ddl_admin_page.is_integrated_theme();
    };

    self.init();
};