var DDLayout = DDLayout || {};

DDLayout.TabDialog = function($, row_view)
{
    var self = this;

    self.row_view = row_view;

    _.extend( DDLayout.TabDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

    self.init = function() {

        jQuery(document).on('click', '.js-tab-dialog-edit-save', {dialog: self}, function(event) {
            event.preventDefault();

	        var $css_input_id = jQuery( 'input.js-edit-css-id', jQuery(this).parent().parent()),
		        value = $css_input_id.val();

	        if( DDLayout.ddl_admin_page.htmlAttributesHandler.check_id_exists( $css_input_id, value ) )
	        {
                event.data.dialog._save( jQuery(this) );
	        }
        });

        jQuery(document).on('click', '#ddl-tab-edit .js-ddl-show', {dialog: self}, function(event) {
            event.preventDefault();
            jQuery('.js-front-end-options').show();
            jQuery('.js-ddl-show').hide();
            jQuery('.js-ddl-hide').show();

        });

        jQuery(document).on('click', '#ddl-tab-edit .js-ddl-hide', {dialog: self}, function(event) {
            event.preventDefault();
            jQuery('.js-front-end-options').hide();
            jQuery('.js-ddl-show').show();
            jQuery('.js-ddl-hide').hide();

        });

    };

    // TODO: ADD JS PREFIXES
    // TODO: Assign repetitive elements to variables. for example
    // var $layoutType = jQuery('#ddl-tab-edit #ddl-tab-edit-layout-type');
    self.show = function( mode, row_view ) {

        self.setCachedElement( row_view.model.toJSON() );

        var has_child_layout_alone = self.row_doesnt_render_in_front_end( row_view.model );

        if( has_child_layout_alone ){
            jQuery('.ddl-tab-right').hide();
        } else {
            jQuery('.ddl-tab-right').show();
        }

        jQuery('.js-edit-dialog-close').css('float', 'left')


		if (row_view.is_top_level_row() && has_child_layout_alone === false ) {

            jQuery('#js-row-not-render-message').hide();

            jQuery('.js-css-styling-controls').removeClass('from-top-0').show();
            jQuery('.ddl-dialog-content').removeClass('pad-top-0');

		} else if( row_view.is_top_level_row() && has_child_layout_alone ) {

            jQuery('#js-row-not-render-message').show();
            jQuery('.js-css-styling-controls').hide();
            jQuery('.ddl-dialog-content').removeClass('pad-top-0');
         
            jQuery('.js-row-dialog-edit-save').removeClass('button-primary').prop('disabled', true);
        } else {

            jQuery('#js-row-not-render-message').hide();

            jQuery('.ddl-dialog-content').addClass('pad-top-0');
            jQuery('.js-css-styling-controls').addClass('from-top-0').css('border-top', 0).show();
            
		}

        if (mode == 'edit') {


            jQuery('#ddl-tab-edit').data('mode', 'edit-row');
            jQuery('#ddl-tab-edit').data('row_view', row_view);

            jQuery('input[name="ddl-tab-edit-tab-name"]').val(row_view.model.get('name'));

            _.defer(function(){
                var chosen_context = jQuery('#ddl-tab-edit').find('.ddl-fields-container');
                var saved_css_classes = row_view.model.get('additionalCssClasses');
                var array_with_classes = ( saved_css_classes != null ? saved_css_classes.split(" ") : saved_css_classes );

                DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', array_with_classes );
                DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', array_with_classes, chosen_context );

            });

            _.defer(function(){
                var chosen_context_tab = jQuery('#ddl-tab-edit').find('.js-ddl-form-tab');
                var saved_css_classes_tab = row_view.model.get('tabClasses');
                var array_with_classes_tab = ( saved_css_classes_tab != null ? saved_css_classes_tab.split(",") : saved_css_classes_tab );

                DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', array_with_classes_tab );
                DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', array_with_classes_tab, chosen_context_tab );

            });


	        jQuery('input.js-edit-css-id', jQuery('#ddl-tab-edit') ).val( row_view.model.get('cssId') );
            jQuery('input.js-ddl-tab-classes', jQuery('#ddl-tab-edit') ).val( row_view.model.get('tabClasses') );
            jQuery('#ddl-tab-edit select[name="ddl_tag_name"]').val( row_view.model.get('tag') ).trigger('change');
            if( row_view.model.get('disabled') ){
                jQuery('input[name="ddl-tab-edit-disabled"]').prop('checked', true);
                jQuery('input[name="ddl-tab-edit-enabled"]').prop('checked', false);
            } else {
                jQuery('input[name="ddl-tab-edit-disabled"]').prop('checked', false);
                jQuery('input[name="ddl-tab-edit-enabled"]').prop('checked', true);
            }

            jQuery('input[name="ddl-tab-edit-disabled"]').on('click', self.handle_change);
            jQuery('input[name="ddl-tab-edit-enabled"]').on('click', self.handle_change );

            jQuery('#ddl-tab-edit .js-dialog-edit-title').show();
            jQuery('#ddl-tab-edit .js-row-dialog-edit-save').show();

            jQuery('#ddl-tab-edit .js-dialog-add-title').hide();
            jQuery('#ddl-tab-edit .js-row-dialog-edit-add-row').hide();

	        jQuery('#ddl-tab-edit #ddl-tab-edit-layout-type').parent().hide();


        } else if (mode == 'add') {
            jQuery('#ddl-tab-edit').data('mode', 'add-row');
            jQuery('#ddl-tab-edit').data('row_view', row_view);

            jQuery('input[name="ddl-tab-edit-tab-name"]').val('');
            jQuery('input[name="ddl-tab-edit-row-class-name"]').val('');
			jQuery('input[name="ddl-tab-edit-css-id"]').val('');
            jQuery('#ddl-tab-edit select[name="ddl_tag_name"]').val('div');

            jQuery('#ddl-tab-edit .js-dialog-edit-title').hide();
            jQuery('#ddl-tab-edit .js-row-dialog-edit-save').hide();

            jQuery('#ddl-tab-edit .js-dialog-add-title').show();
            jQuery('#ddl-tab-edit .js-row-dialog-edit-add-row').show();


            jQuery('#ddl-tab-edit #ddl-tab-edit-layout-type').parent().show();

            if (!row_view.can_add_fixed_row_below_this()) {
                // disable layout selection
                jQuery('#ddl-tab-edit #ddl-tab-edit-layout-type').val('fluid');
                jQuery('#ddl-tab-edit #ddl-tab-edit-layout-type').prop('disabled', 'disabled');
                jQuery('.js-only-fluid-message').show();
            } else {
                jQuery('#ddl-tab-edit #ddl-tab-edit-layout-type').prop('disabled', false);
                jQuery('.js-only-fluid-message').hide();
            }

        }

        jQuery.colorbox({
            href: '#ddl-tab-edit',
            closeButton:false,
            onComplete: function() {
                Toolset.hooks.doAction(self.row_view.model.get('kind') +'.dialog_open', self.row_view.model);
                jQuery('.js-ddl-question-mark').toolsetTooltip({
                    additionalClass:'ddl-tooltip-info'
                });
            },
            onCleanup:function(){
                jQuery('input[name="ddl-tab-edit-disabled"]').off('click', self.handle_change);
                jQuery('input[name="ddl-tab-edit-enabled"]').off('click', self.handle_change );
                jQuery('.js-css-styling-controls').removeClass('from-top-0').show();
                jQuery('.ddl-dialog-content').removeClass('pad-top-0');
                jQuery('.js-ddl-form-tab').removeClass('ddl-zero');
                jQuery('.js-row-dialog-edit-save').addClass('button-primary').prop('disabled', false);
                Toolset.hooks.doAction(self.row_view.model.get('kind') +'.dialog_close', self.row_view.model);
            }
        });
    };


    self._save = function (caller) {

        var target_row_view = jQuery('#ddl-tab-edit').data('row_view');

        if (jQuery('#ddl-tab-edit').data('mode') == 'add-row') {

            var layout_type = jQuery('select[name="ddl-tab-edit-layout-type"]').val();
            target_row_view.addRow(jQuery('input[name="ddl-tab-edit-tab-name"]').val(),
                                   jQuery('input[name="ddl-tab-edit-row-class-name"]').val(),
                                   layout_type);

        } else if (jQuery('#ddl-tab-edit').data('mode') == 'edit-row') {


            DDLayout.ddl_admin_page.save_undo();

            var target_row = target_row_view.model;

            target_row.set('name', jQuery('input[name="ddl-tab-edit-tab-name"]').val() );
            target_row.set('cssId', jQuery('input.js-edit-css-id', jQuery('#ddl-tab-edit') ).val() );
            target_row.set('tag', jQuery('#ddl-tab-edit select[name="ddl_tag_name"]').val() );
            target_row.set('disabled', self.get_disabled() );

            var css_classes_tosave = jQuery('select[name="ddl-tab-edit-class-name"]', jQuery("#ddl-tab-edit")).val();
            var css_classes_tosave_tab = jQuery('select[name="ddl-tab-classes"]', jQuery("#ddl-tab-edit")).val();

            setTimeout(function(){
                target_row.set('additionalCssClasses', (css_classes_tosave != null ? css_classes_tosave.join(',') : ""));
                target_row.set('tabClasses', (css_classes_tosave_tab != null ? css_classes_tosave_tab.join(',') : ""));
            }, 20);


            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css_classes_tosave );
            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css_classes_tosave_tab );
            DDLayout.ddl_admin_page.trigger( 'layout_element_model_changed_from_dialog', caller, target_row_view, self.cached_element, false, self );

        }

        if( self.is_save_and_close(caller) ) jQuery.colorbox.close();

        return false;
    };

    self.get_disabled = function(){

        if( jQuery('input[name="ddl-tab-edit-disabled"]').is(':checked') ){
            return true;
        } else {
            return false;
        }
    };

    self.handle_change = function(event){
        if( jQuery(this).val() === 'disabled' ){
            jQuery(this).prop('checked', true).trigger('change');
            jQuery('input[name="ddl-tab-edit-enabled"]').prop('checked', false).trigger('change');
        } else{
            jQuery(this).prop('checked', true).trigger('change');
            jQuery('input[name="ddl-tab-edit-disabled"]').prop('checked', false).trigger('change');
        }
    };
    
    self.init();
};