var DDLayout = DDLayout || {};

DDLayout.EditTabsCell = function () {
    "use strict";
    var self = this,
        undefined,
        dialog_object = null,
        current_view = null,
        current_model = null,
        $navigation_style = null,
        $justified = null,
        $stacked = null,
        $stacked_wrap = null,
        $fade = null;

    self.justified = false;
    self.stacked = false;
    self.navigation_style = "tabs";
    self.fade = false;

    self.init = function () {
        jQuery(document).on('tabs-cell.dialog-open', self._dialog_open);
        jQuery(document).on('tabs-cell.dialog-close', self._dialog_close);
        Toolset.hooks.addFilter('ddl-layouts-before-cell-save', self._save_callback);
    };

    self.init_selectors = function(){
        $navigation_style = jQuery('input[name="ddl-layout-navigation_style"]', dialog_object.get_dialog_selector());
        $justified = jQuery('input[name="ddl-layout-justified"]', dialog_object.get_dialog_selector());
        $stacked = jQuery('input[name="ddl-layout-stacked"]', dialog_object.get_dialog_selector());
        $fade = jQuery('input[name="ddl-layout-fade"]', dialog_object.get_dialog_selector());
        $stacked_wrap = $stacked.closest('li.ddl-tabs-stacked');
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
            self.radio_set_val($navigation_style, 'tabs');
            self.radio_set_val($justified, 'text');
            self.radio_set_val($fade, 'no_fade');
            self.radio_set_val($stacked, 'horizontal');
            $stacked_wrap.hide();
        }
        self.init_events();
    };

    self._dialog_close = function () {
        dialog_object = null;
        current_view = null;
        current_model = null;
        self.navigation_style = 'tabs';
        self.justified = false;
        self.stacked = false;
        self.turn_off_events();
        self.radio_set_val($navigation_style, 'tabs');
        $justified.prop('checked', false).trigger('change');
        $stacked.prop('checked', false).trigger('change');
    };

    self._setColumn = function( col ){
        return col;
    };

    self.radio_set_val = function( $element, val ){
        $element.map(function(){
            if( jQuery(this).val() === val ){
                jQuery(this).prop('checked', true).trigger('change');
            } else {
                jQuery(this).prop('checked', false).trigger('change');
            }
        });
    };

    self._set_num_rows = function( rows, container ){
        if( container instanceof DDLayout.models.cells.Tabs ){
            rows = 1;
        }
        return rows;
    };

    self._set_max_cols = function( cols, container ){
        if( container instanceof DDLayout.models.cells.Tabs ){
            cols = 1;
        }
        return cols;
    };

    self._set_divider = function( divider, container ){
        if( container instanceof DDLayout.models.cells.Tabs ){
            divider = 12;
        }
        return divider;
    };

    self._save_callback = function( target_cell, container, dialog ){
        if( container instanceof DDLayout.models.cells.Tabs ){
            container.set('navigation_style', self.navigation_style );
            container.set('justified',  self.justified  );
            container.set('stacked', self.stacked  );
            container.set('fade', self.fade );
        }

        return target_cell;
    };

    self.turn_off_events = function(){
        $navigation_style.off('change', self.styles_callback);
        $justified.off('change', self.justified_callback);
        $stacked.off('change', self.stacked_callback);
        $fade.off('change', self.fade_callback);
        Toolset.hooks.removeFilter('ddl-container_columns_to_add', self._setColumn );
        Toolset.hooks.removeFilter( 'ddl-container_number_of_rows', self._set_num_rows );
        Toolset.hooks.removeFilter( 'ddl-container_container_columns', self._set_max_cols );
        Toolset.hooks.removeFilter( 'ddl-container_row_divider', self._set_divider );
    };

    self.init_events = function(){
        $navigation_style.on('change', self.styles_callback);
        $justified.on('change', self.justified_callback);
        $stacked.on('change', self.stacked_callback);
        $fade.on('change', self.fade_callback);
        jQuery('.js-ddl-question-mark').toolsetTooltip({
            additionalClass:'ddl-tooltip-info'
        });
    };

    self.init_elements = function( current_model ){
        var navigation_style = current_model.get('navigation_style'),
            justified = current_model.get('justified'),
            fade = current_model.get('fade'),
            stacked = current_model.get('stacked');

        self.radio_set_val($navigation_style, navigation_style);
        self.radio_set_val($justified, justified ? 'justified' : 'text');
        self.radio_set_val($fade, fade ? 'fade' : 'no_fade');
        self.radio_set_val($stacked, stacked ? 'vertical' : 'horizontal');

        if( navigation_style === 'tabs' ){
            $stacked_wrap.hide();
            jQuery('.tabs-style').text('tabs');
            jQuery('.tab-style').text('tab');
        } else {
            $stacked_wrap.show();
            jQuery('.tabs-style').text('buttons');
            jQuery('.tab-style').text('button');
        }

        self.navigation_style = navigation_style;
        self.justified = justified;
        self.stacked = stacked;
        self.fade = fade;
    };

    self.fade_callback = function(event){
        if( jQuery(this).is(':checked') === false ){
            return;
        }
        if( jQuery(this).val() === 'fade' ){
            self.fade = true;
        } else {
            self.fade = false;
        }
    }

    self.stacked_callback = function(event){
        if( jQuery(this).is(':checked') === false ){
            return;
        }
        if( jQuery(this).val() === 'vertical' ){
            self.stacked = true;
            $justified.prop( 'disabled', true );
            self.radio_set_val( $justified, 'text' );
            $justified.off('change', self.justified_callback);
        } else {
            self.stacked = false;
            $justified.prop( 'disabled', false );
            $justified.off('change', self.justified_callback);
            $justified.on('change', self.justified_callback);
        }
    };

    self.styles_callback = function(event){
        if( jQuery(this).is(':checked') === false ){
            return;
        }
        if( self.navigation_style === 'tabs' ){
            self.navigation_style = 'pills';
            $stacked_wrap.show();
            jQuery('.tabs-style').text('buttons');
            jQuery('.tab-style').text('button');
        } else {
            self.navigation_style = 'tabs';
            $stacked_wrap.hide();
            jQuery('.tabs-style').text('tabs');
            jQuery('.tab-style').text('tab');
            self.radio_set_val($stacked, 'horizontal');
        }
    };

    self.justified_callback = function(event){

        if( jQuery(this).is(':checked') === false ){
            return;
        }

        if( jQuery(this).val() === 'justified' ){
            self.justified = true;
            $stacked_wrap.hide();
        } else {
            self.justified = false;
            if( self.navigation_style !== 'tabs' ){
                $stacked_wrap.show();
            }
        }
    };

    self.init();
};

DDLayout.TabsDialog = function($)
{
    "use strict";
    var self = this;

    _.extend( DDLayout.TabsDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

    self._cell_type = 'tabs-cell';

    self.init = function() {

        jQuery(document).on('click', '.js-tabs-dialog-edit-save', {dialog: self}, function(event) {
            event.preventDefault();
            event.data.dialog._save( jQuery(this) );
        });
    };

    self._save = function(caller)
    {

        var target_container_view = jQuery('#ddl-tabs-edit').data('container_view');

        if (jQuery('#ddl-tabs-edit').data('mode') == 'edit-container') {

            DDLayout.ddl_admin_page.save_undo();

            var target_container = target_container_view.model;

            target_container.set('name', jQuery('input[name="ddl-layout-edit-tabs-name"]').val());
            target_container.set('cssId', jQuery('input.js-edit-css-id', jQuery('#ddl-tabs-edit') ).val());
            target_container.set('tag', jQuery('select.js-ddl-tag-name', jQuery('#ddl-tabs-edit') ).val());


            var css_classes_tosave = jQuery('select[name="ddl-container-edit-class-name"]', jQuery("#ddl-tabs-edit")).val();
            setTimeout(function(){
                target_container.set('additionalCssClasses', (css_classes_tosave != null ? css_classes_tosave.join(',') : ""));
            }, 20);


            DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css_classes_tosave );

            target_container_view.model = Toolset.hooks.applyFilters('ddl-layouts-before-cell-save', target_container_view.model, target_container_view.model, this);
            DDLayout.ddl_admin_page.trigger( 'layout_element_model_changed_from_dialog', caller, target_container_view, target_container_view.model, false, self );
        }

        if ( self.is_save_and_close(caller) )  jQuery.colorbox.close();

        return false;
    };

    self.show = function(mode, container_view)
    {
        self._cell_type = container_view.model.get('cell_type');

        self.setCachedElement( container_view.model.toJSON() );

        if (mode == 'edit') {
            jQuery('#ddl-tabs-edit').data('mode', 'edit-container');
            jQuery('#ddl-tabs-edit').data('container_view', container_view);


            _.delay(function(){
                jQuery('input[name="ddl-container-edit-container-name"]').val( container_view.model.get('name') );
                jQuery('input.js-edit-css-id', jQuery('#ddl-tabs-edit') ).val( container_view.model.get('cssId') );
                jQuery('select.js-ddl-tag-name', jQuery('#ddl-tabs-edit') ).val( container_view.model.get('tag') );

                var saved_css_classes = container_view.model.get('additionalCssClasses');
                var array_with_classes = ( saved_css_classes != null ? saved_css_classes.split(" ") : saved_css_classes );
                var chosen_context = jQuery('#ddl-tabs-edit').find('.ddl-fields-container');
                DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', array_with_classes );
                DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', array_with_classes, chosen_context );

            }, 300);

            jQuery('#ddl-tabs-edit .js-dialog-edit-title').show();
            jQuery('#ddl-tabs-edit .js-tabs-dialog-edit-save').show();

            jQuery('#ddl-tabs-edit .js-dialog-add-title').hide();
            //jQuery('#ddl-tabs-edit .js-container-dialog-edit-add-container').hide();
            jQuery('.js-edit-dialog-close').css('float', 'left')

            jQuery('#ddl-tabs-edit #ddl-tabs-edit-layout-type').parent().hide();

        }

        jQuery.colorbox({
            href: '#ddl-tabs-edit',
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
        return jQuery('#ddl-tabs-edit').data('container_view');
    };

    self._fire_event = function (name) {
        var event_name = self._cell_type + '.' + name;
        jQuery(document).trigger(event_name, [{}, self]);
    };

    self.is_new_cell = function(){
        return jQuery('#ddl-tabs-edit').data('mode') !== 'edit-container';
    };

    self.init();
};