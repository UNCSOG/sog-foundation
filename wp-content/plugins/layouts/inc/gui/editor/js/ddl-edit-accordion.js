var DDLayout = DDLayout || {};

DDLayout.EditAccordionCell = function () {

    var self = this,
        undefined,
        dialog_object = null,
        current_view = null,
        current_model = null;

    self.init = function () {
        jQuery(document).on('accordion-cell.dialog-open', self._dialog_open);
        jQuery(document).on('accordion-cell.dialog-close', self._dialog_close);
        Toolset.hooks.addFilter('ddl-layouts-before-cell-save', self._save_callback);
    };

    self.init_selectors = function(){

    };

    self._dialog_open = function( event, content, dialog ){
        dialog_object = dialog;

        self.init_selectors();

        if( dialog_object.is_new_cell() == false ){
            current_view = dialog_object.get_target_cell_view();
            current_model = current_view.model;
            self.init_elements(current_model);
        } else {
            Toolset.hooks.addFilter('ddl-container_columns_to_add', self._setColumn );
            Toolset.hooks.addFilter( 'ddl-container_number_of_rows', self._set_num_rows );
            Toolset.hooks.addFilter( 'ddl-container_container_columns', self._set_max_cols );
            Toolset.hooks.addFilter( 'ddl-container_row_divider', self._set_divider );
        }
        self.init_events();
    };

    self._dialog_close = function () {
        dialog_object = null;
        current_view = null;
        current_model = null;
        self.turn_off_events();
    };

    self._setColumn = function( col ){
        return col;
    };

    self._set_num_rows = function( rows, container ){
        if( container instanceof DDLayout.models.cells.Accordion ){
            rows = 1;
        }
        return rows;
    };

    self._set_max_cols = function( cols, container ){
        if( container instanceof DDLayout.models.cells.Accordion ){
            cols = 1;
        }
        return cols;
    };

    self._set_divider = function( divider, container ){
        if( container instanceof DDLayout.models.cells.Accordion ){
            divider = 12;
        }
        return divider;
    };

    self._save_callback = function( target_cell, container, dialog ){
            if( container instanceof DDLayout.models.cells.Accordion ){
                    //
            }

        return target_cell;
    };

    self.turn_off_events = function(){
        Toolset.hooks.removeFilter('ddl-container_columns_to_add', self._setColumn );
        Toolset.hooks.removeFilter( 'ddl-container_number_of_rows', self._set_num_rows );
        Toolset.hooks.removeFilter( 'ddl-container_container_columns', self._set_max_cols );
        Toolset.hooks.removeFilter( 'ddl-container_row_divider', self._set_divider );
    };

    self.init_events = function(){
       //
    };

    self.init_elements = function( current_model ){
        //
    };

    self.init();
};

DDLayout.AccordionDialog = function($)
{
    var self = this;

    _.extend( DDLayout.AccordionDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

    self._cell_type = 'accordion-cell';

    self.init = function() {

        jQuery(document).on('click', '.js-accordion-dialog-edit-save', {dialog: self}, function(event) {
            event.preventDefault();
            event.data.dialog._save( jQuery(this) );
        });
    };

    self._save = function(caller)
    {

        var target_container_view = jQuery('#ddl-accordion-edit').data('container_view');

        if (jQuery('#ddl-accordion-edit').data('mode') == 'edit-container') {

            DDLayout.ddl_admin_page.save_undo();

            var target_container = target_container_view.model;

            target_container.set('name', jQuery('input[name="ddl-layout-edit-accordion-name"]').val());
            target_container.set('cssId', jQuery('input.js-edit-css-id', jQuery('#ddl-accordion-edit') ).val());
            target_container.set('tag', jQuery('select.js-ddl-tag-name', jQuery('#ddl-accordion-edit') ).val());


            var css_classes_tosave = jQuery('select[name="ddl-container-edit-class-name"]', jQuery("#ddl-accordion-edit")).val();

            setTimeout(function(){
                target_container.set('additionalCssClasses', (css_classes_tosave != null ? css_classes_tosave.join(',') : ""));
            }, 20);


            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css_classes_tosave );

            target_container_view.model = Toolset.hooks.applyFilters('ddl-layouts-before-cell-save', target_container_view.model, target_container_view.model, this);
            DDLayout.ddl_admin_page.trigger( 'layout_element_model_changed_from_dialog', caller, target_container_view, target_container, false, self );

        }

        if ( self.is_save_and_close(caller) )  jQuery.colorbox.close();

        return false;
    };

    self.show = function(mode, container_view)
    {
        self._cell_type = container_view.model.get('cell_type');

        self.setCachedElement( container_view.model.toJSON() );

        if (mode == 'edit') {
            jQuery('#ddl-accordion-edit').data('mode', 'edit-container');
            jQuery('#ddl-accordion-edit').data('container_view', container_view);


            _.delay(function(){
                jQuery('input[name="ddl-layout-edit-accordion-name"]').val( container_view.model.get('name') );
                jQuery('input.js-edit-css-id', jQuery('#ddl-accordion-edit') ).val( container_view.model.get('cssId') );
                jQuery('select.js-ddl-tag-name', jQuery('#ddl-accordion-edit') ).val( container_view.model.get('tag') );

                var saved_css_classes = container_view.model.get('additionalCssClasses');
                var array_with_classes = ( saved_css_classes != null ? saved_css_classes.split(" ") : saved_css_classes );
                var chosen_context = jQuery('#ddl-accordion-edit').find('.ddl-fields-container');

                DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', array_with_classes );
                DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', array_with_classes, chosen_context );

            }, 300);

            jQuery('#ddl-accordion-edit .js-dialog-edit-title').show();
            jQuery('#ddl-accordion-edit .js-accordion-dialog-edit-save').show();

            jQuery('#ddl-accordion-edit .js-dialog-add-title').hide();
            jQuery('.js-edit-dialog-close').css('float', 'left')

            jQuery('#ddl-accordion-edit #ddl-accordion-edit-layout-type').parent().hide();

        }

        jQuery.colorbox({
            href: '#ddl-accordion-edit',
            closeButton:false,
            onComplete: function() {
                self._fire_event('dialog-open');
            },
            onLoad: function()
            {

            },
            onCleanup: function () {
                self._fire_event('dialog-close');
            },
            onClosed: function () {
                self._fire_event('dialog-closed');
            }
        });
    };

    self.get_target_cell_view = function () {
        return jQuery('#ddl-accordion-edit').data('container_view');
    };

    self._fire_event = function (name) {
        var event_name = self._cell_type + '.' + name;
        jQuery(document).trigger(event_name, [{}, self]);
    };

    self.is_new_cell = function(){
        return jQuery('#ddl-accordion-edit').data('mode') !== 'edit-container';
    };

    self.init();
};

