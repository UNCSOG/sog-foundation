var DDLayout = DDLayout || {};

//Models namespace / paths
DDLayout.models = {};
DDLayout.models.abstract = {};
DDLayout.models.cells = {};
DDLayout.models.collections = {};

//Views namespaces / paths
DDLayout.views = {};
DDLayout.views.abstract = {};

// AMD scripts loading
DDLayout_settings.DDL_JS.ns = head;
DDLayout_settings.DDL_JS.ns.js(
    // Dependecies
    DDLayout_settings.DDL_JS.lib_path + "backbone_overrides.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.lib_path + "he/he.min.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.common_rel_path + "/res/lib/jstorage.min.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.common_rel_path + "/utility/js/keyboard.min.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + 'parent-helper.js'
    // Models
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/abstract/Element.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + 'models/Parent.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/collections/Cells.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/collections/Rows.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Cell.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Row.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Spacer.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Layout.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Container.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Tabs.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Tab.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Accordion.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Panel.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/ThemeSectionRow.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    // Collections
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/collections/Rows.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/collections/Cells.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    // Views
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/FrontendEditorToolbarView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/abstract/ElementView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/abstract/CollectionView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/ContainerView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/ParentLayoutView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/ContextMenuView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/CellView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/CellsView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/RowsView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/RowView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/ContainerRowView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/TabsTabView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/TabsView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/AccordionPanelView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/AccordionView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/SpacerView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/LayoutView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.frontend_editor_lib_path + "views/ThemeSectionRowView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/UndoRedo.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/KeyHandler.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/SaveState.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-wpml-box.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-saving-saved-box.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    // Dialogs
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "default-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "dialog-repeating-fields.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "html-properties/HtmlAttributesHandler.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "css-cell-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "row-edit-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "container-edit-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "theme-section-row-edit-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "tab-edit-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "panel-edit-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-edit-tabs.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-edit-accordion.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION,
    // Backbone doesn't support inheritance of default attributes
    // let's tell all cells that should inherit defaults from element
    // and allow attributes override
    function () {
        _.each(DDLayout.models.cells, function (item, key, list) {
            if (list.hasOwnProperty(key)) {
                _.defaults(DDLayout.models.cells[key].prototype.defaults, DDLayout.models.abstract.Element.prototype.defaults);
            }
            else {
                console.info("Your model should inherit from Element object");
            }
        });
    }
)

DDLayout.AdminPage = function () {
    'use strict'
    var self = this,
        layout_views = {},
        dialog = null,
        layouts = {},
        response_count = 0;

    _.extend( DDLayout.AdminPage.prototype, new DDLayout.AdminPageAbstract(jQuery) );

    self.go_to_editor_menu_selector = '.js-edit-layout-menu';
    self.post_content = null;

    self.SPECIAL_CELLS_OPTIONS = DDLayout_settings.DDL_JS.SPECIAL_CELLS_OPTIONS;
    self.DONT_RE_RENDER = [
        'Panel',
        'Tab'
    ];

    self.RE_RE_RENDER_IN_PLACE = [
        'post-loop-views-cell',
        'views-content-grid-cell',
        'cred-cell',
        'cred-user-cell'
    ];

    self.init = function () {

        _.bindAll(self, 'open_info_dialog');
        self.toolbar = new DDLayout.views.FrontendEditorToolbarView();
        self.htmlAttributesHandler = new DDLayout.HtmlAttributesHandler;
        self.wpml_handler = new DDLayout.WPMLBoxHandler();
        self.dialog = new DDLayout.DefaultDialog();
        self.errors_div = jQuery('.js-ddl-message-container');
        self.undo_redo = new DDLayout.UndoRedo();
        self._save_state = new DDLayout.SaveState();

        self.init_mvc();

        self.open_edit_layouts_dropdown();
        self.add_listeners();


        Toolset.hooks.addFilter('ddl_save_layout_params', function(params, instance){
            params.post_id = DDLayout_settings.DDL_JS.current_post;
            return params;
        });

        self.add_colorbox_events_listeners();
        self.init_hover_events();

        _.defer( function(){
            _.each(layouts, function(layout){
                self.init_wpml_vars(layout);
            });
        });
    };

    self.init_mvc = function(){
        // init models
        var elements = jQuery('.js-hidden-json-textarea');
        elements.each(function(){
            var base64 = jQuery(this).text(),
                json = JSON.parse(WPV_Toolset.Utils.editor_decode64( base64) );

            layouts[json.slug] = new DDLayout.models.cells.Layout(json);

            // init views
            layout_views[json.slug] = new DDLayout.views.LayoutView({
                model: layouts[json.slug]
            });
        });
    };

    self.add_listeners = function(){
        self.listenTo(self, 'open-Cell-dialog', self.open_cell_dialog);
        self.listenTo(self, 'open-Row-dialog', self.show_row_dialog);
        self.listenTo(self, 'open-Tab-dialog', self.show_tab_dialog);
        self.listenTo(self, 'open-Accordion-dialog', self.show_accordion_dialog);
        self.listenTo(self, 'open-Panel-dialog', self.show_panel_dialog);
        self.listenTo(self, 'open-Tabs-dialog', self.show_tabs_dialog);
        self.listenTo(self, 'open-Container-dialog', self.show_container_dialog);
        self.listenTo(self, 'open-ThemeSectionRow-dialog', self.show_theme_section_row_dialog);

        self.listenTo(self, 'open-info-dialog', self.open_info_dialog);
        self.listenTo(self, 'ddl-events-over-on', self.events_over_on);
        self.listenTo(self, 'ddl-events-over-off', self.events_over_off);
        self.listenTo(self, 'context-menu-opened', self.openContextMenu);
        self.listenTo(self, 'context-menu-closed', self.closeContextMenu);
        // listen to chnages from every layouts element dialog
        self.listenTo( self, 'layout_element_model_changed_from_dialog', self.save_layout_from_dialog_callback );
        self.listenTo( self, 'save_layout', self.save_layout );

        self.listenTo( self, 'layout_update_additional_css_classes_array', self.update_css_classes_array );
        self.listenTo( self, 'layout_generate_chosen_selector', self.run_chosen_selector );
    };

    self.add_colorbox_events_listeners = function(){
        jQuery(document).on('cbox_closed', self.save_and_close_handler);
        jQuery(document).on('cbox_complete', self.dialog_complete_handler);
        jQuery(document).on('cbox_open', self.dialog_open_handler);
        jQuery(document).on('cbox_load', self.dialog_load_handler);
        jQuery(document).on('cbox_cleanup', self.dialog_close_handler);
    };

    self.update_css_classes_array = function(css_classes_tosave){

        if( typeof DDLayout_settings !== 'undefined' &&
            css_classes_tosave !== null &&
            Array.isArray( css_classes_tosave ) === true &&
            DDLayout_settings.DDL_JS &&
            Array.isArray( DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses ) === true )
        {
            var all_classes = DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses.concat( css_classes_tosave );
            DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses = all_classes.filter(function (item, pos) {return all_classes.indexOf(item) == pos});
        }

    };

    self.run_chosen_selector = function( array_with_classes, $context ){
        var chosen_args = {
            'width': "555px",
            'no_results_text': 'Press Enter to add new entry:',
            'display_selected_options': false,
            'display_disabled_options': false
        };

        if( $context ){
            jQuery('select.js-toolset-chosen-select', $context ).toolset_chosen_multiple_css_classes( chosen_args, DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses, array_with_classes );
        } else {
            jQuery('select.js-toolset-chosen-select', jQuery('#ddl-row-edit') ).toolset_chosen_multiple_css_classes( chosen_args, DDLayout_settings.DDL_JS.layouts_css_properties.additionalCssClasses, array_with_classes );
        }

    };



    self.resetContextMenu = function( event ){
        event.stopPropagation();

        if( !self.instance_layout_view ) return false;

        try{
            if( self.instance_layout_view.context_menu_opened === false ){
                return false;
            }

            self.instance_layout_view.contextMenuReset(event);

        } catch( e ){

            return false;

        }

        return false;
    };

    self.openContextMenu = function( event ){

        if( !self.instance_layout_view ) return false;

        try{

            if( self.instance_layout_view.context_menu_opened ){
                return false;
            }

            self.instance_layout_view.openContextMenu();

        } catch( e ){

            return false;

        }

        return false;
    };

    self.closeContextMenu = function( event ){

        if( !self.instance_layout_view ) return false;

        try{

            if( self.instance_layout_view.context_menu_opened === false ){
                return false;
            }
            self.instance_layout_view.closeContextMenu(  );
            self.instance_layout_view.clearOverlay(  );
            self.events_over_on( );

        } catch( e ){

            return false;

        }

        return false;

    };

    self.dialog_complete_handler = function(){

    };

    self.dialog_open_handler = function(){

    };

    self.dialog_close_handler = function(){
        if( self.is_saving === false ){
            self.init_hover_events();
        }
    };

    self.save_and_close_handler = function( event ){
        if( self.is_saving && self.dialog_will_close ){
            jQuery('body').loaderOverlay('hide');
            jQuery('body').loaderOverlay('show');
        }
    };

    self.dialog_load_handler = function(){
        jQuery('body').loaderOverlay('hide');
    };

    self.get_layouts = function(){
        return layouts;
    };

    self.get_layouts_as_array = function(){
        return _.toArray( layouts );
    };

    self.init_hover_events = function(){
        self.events_over_off();
        self.events_over_on();
    };

    self.events_over_on = function () {
        // Draw overlay & context menu vor Visual editor cells only
        jQuery(document).on('mouseenter', '.js-ddl-frontend-editor-cell', self.handle_over);
        // Draw overlay & context menu vor Visual editor cells only
        jQuery(document).on('mouseleave', '.js-ddl-frontend-editor-cell', self.handle_out);
    };
    self.events_over_off = function(){
        // Draw overlay & context menu vor Visual editor cells only
        jQuery(document).off('mouseenter', '.js-ddl-frontend-editor-cell',self.handle_over);
        // Draw overlay & context menu vor Visual editor cells only
        jQuery(document).off('mouseleave', '.js-ddl-frontend-editor-cell', self.handle_out);
    };
    self.handle_over = function(event){
        // console.log( 'check parent', jQuery(this).is(event.target), jQuery(this).prop('class'), jQuery(event.target).prop('class') );

        event.stopPropagation();
        event.stopImmediatePropagation();

        var slug = event.currentTarget.dataset.layout_slug,
            type = event.currentTarget.dataset.type;


        self.set_current(slug);


        try{
            self.get_current().renderOverlay(event);
        } catch( e ){
            console.log( e.message );
        }

    };
    self.handle_out = function(event){
        // console.log( 'check child leave', jQuery(this).is(event.target), jQuery(this).prop('class'), jQuery(event.target).prop('class') );

        event.stopPropagation();
        event.stopImmediatePropagation();

        try{
            self.get_current().clearOverlay(event);
        } catch( e ){
            console.log( e.message );
        }

        if( self.is_saving === false ){
            self.set_current(null);
        }
    };

    self.set_current = function( slug ){
        self.instance_layout_view = layout_views[slug];
    };

    self.get_current = function(){
        return self.instance_layout_view;
    };

    self.get_all_instances = function(){
        return layout_views;
    };

    self.before_open_dialog = function(){
        self.closeContextMenu();
        jQuery('body').loaderOverlay('hide');
        jQuery('body').loaderOverlay('show');
    };

    self.save_layout = function( caller ){
        var layout_len = _.toArray(layout_views).length;

        self.is_saving = true;
        jQuery('body').loaderOverlay('hide');
        jQuery('body').loaderOverlay('show');

        self.trigger('ddl-events-over-off');

        _.each(layout_views, function( layout_view ) {
            try{
                layout_view.saveLayout( function( response, model, el ){
                    //console.log( arguments );
                    response_count++;
                    if( response_count === layout_len ){
                        self.is_saving = false;
                        response_count = 0;
                        jQuery('body').loaderOverlay('hide');
                        self.trigger('ddl-events-over-on');
                        _.delay(function(){
                            self.errors_div.find('.ddl-messages').fadeOut(400, function(){

                            });
                        }, 6000);
                    }
                }, caller, {action:'save_layout_data_front_end'} );
            } catch( e ){
                console.log( e.message );
            }
        });

    };

    self.maybe_get_post_content = function(){
        var editor_selector = 'cell-post-content-editor', post_content = null;

        if( editor_selector in tinyMCE.editors ) {
            var tinymce_editor = tinyMCE.get( editor_selector );

            if( typeof tinymce_editor === 'undefined' ){
                return post_content;
            }

            if( tinymce_editor.isHidden() ) {
                post_content = jQuery('#'+editor_selector).val();
            } else {
                post_content = tinyMCE.editors[editor_selector].save();
            }
        }

        return post_content;
    };

    self.set_post_content_value_before_timer_runs = function( cell_type ){
        if( cell_type === 'cell-post-content' ){
            self.post_content = self.maybe_get_post_content();
        } else {
            self.post_content = null;
        }
    };

    // callback to element dialog changes properties value: save changes to model and preview in the page
    self.save_layout_from_dialog_callback = function (caller, element, model_cached, css_saved, dialog_instance) {
        self.set_post_content_value_before_timer_runs( element.model.get('cell_type') );
        // wait the end of the queue to give the cssClasses control to do its job
        _.delay( self.save_layout_from_dialog, 30, caller, element, model_cached, css_saved, dialog_instance );
    };

    // perform the changes in the Views and refresh elements and their events binding
    self.save_layout_from_dialog = function( caller, element, model_cached, css_saved, dialog_instance ){
        var element_model = element.model,
            current_view = element,
            element_layout = jQuery( element.el ).data('layout_slug');

        self.events_over_off();

        if( _.isString( element_layout ) && element_layout !== self.instance_layout_view.model.get('slug') ){
            self.set_current( element_layout );
        }

        self.is_saving = true;
        self.dialog_will_close = jQuery(caller).data('close') === 'yes';

        if( self.DONT_RE_RENDER.indexOf( element.model.get('kind') ) !== -1 ){
            self.is_saving = false;
            self.reset_ajax( null );
            return;
        }

        if( element.model.get('cell_type') && self.RE_RE_RENDER_IN_PLACE.indexOf( element.model.get('cell_type') ) !== -1 ){
            self.handle_re_render_in_place( element_model, element, element_layout, dialog_instance, model_cached );
            self.reset_ajax( caller );
            return;
        }

        self.make_ajax_re_render_call( caller, element_model, current_view, dialog_instance );
    };

    // if the element changes its content call the server and re-render the entire element
    self.make_ajax_re_render_call = function( caller, element_model, current_view, dialog_instance ){

        self.instance_layout_view.model.trigger('save_layout_to_server',

            function ( response, model, el ) {

                if( response && response.Data && response.Data.current_element_html ){

                    self.re_render_element_from_server_response( response, element_model, current_view, dialog_instance );

                }

                self.reset_ajax(caller);

            }, caller, {element_model : element_model, action : 'render_element_changed', woocommerce_archive_title : DDLayout_settings.DDL_JS.woocommerce_archive_title, post_content: self.post_content } );

    };

    // if it's a row change its content rather than itself
    self.re_render_element_row = function( current_view, $html ){
        var index = jQuery( current_view.el ).parent().index(),
            $parent = jQuery( current_view.el ).parent(),
            $new_parent = jQuery( current_view.el ).parent().parent();

        $parent.remove();

        return $new_parent.insertAtIndex( index, $html );
    };

    // when ajax response is available perform all the View and View.$el manipulation needed
    self.re_render_element_from_server_response = function( response, element_model, current_view, dialog_instance ){

        if( element_model.get('kind') === 'Row' && jQuery( current_view.el ).parent().hasClass('container') ){

            var $new_el = self.re_render_element_row( current_view, jQuery( response.Data.current_element_html ) );

        } else {
            var $new_el = jQuery( response.Data.current_element_html ).replaceAll( current_view.$el );
            jQuery('.nav-tabs').show();
            jQuery('.tab-content').show();
        }

        self.reset_element_after_re_render( current_view, element_model, $new_el, dialog_instance );
    };

    // reset View.el after property changes to avoid loosing event binding
    self.reset_element_after_re_render = function( current_view, element_model, $new_el, dialog_instance ){
        self.set_element_cached( element_model, current_view, dialog_instance );
        current_view.setElement( Toolset.hooks.applyFilters( 'ddl-save_layout_from_dialog_content_updated', $new_el, element_model )  );
        jQuery( current_view.el ).data( 'layout_slug', self.instance_layout_view.model.get('slug') );
        self.maybe_reset_maps( current_view.$el );
    };

    self.maybe_reset_maps = function( $container ){

        if( typeof WPViews === 'undefined') return;

        if( _.isUndefined( WPViews.view_addon_maps ) ) return;

        var affected_maps = WPViews.view_addon_maps.get_affected_maps( $container );

        if( affected_maps.length === 0 ) return;

        _.each( affected_maps, WPViews.view_addon_maps.reload_map );

    };

    // in case of a Toolset cell manipulate its DOM through JS, but avoid useless ajax calls
    self.handle_re_render_in_place = function( element_model, element, element_layout, dialog_instance, model_cached ){

        var model_json = element_model.toJSON();

        if( _.isEqual( model_json, model_cached ) ){
            return;
        }

        var $el = element.$el, $newElement;

        if( _.isEqual( model_json.additionalCssClasses, model_cached.additionalCssClasses ) === false ){
            $el.toggleClass( model_cached.additionalCssClasses );
            $el.toggleClass( model_json.additionalCssClasses );
        }

        if( _.isEqual( model_json.cssId, model_cached.cssId ) === false ){
            if( model_json.cssId ){
                $el.attr( 'id', model_json.cssId )
            } else {
                $el.removeAttr('id');
            }
        }

        if( _.isEqual( model_json.tag, model_cached.tag ) === false ){
            $el.each(function ( index, element ) {
                $newElement = jQuery('<' + model_json.tag + '/>');
                _.each( this.attributes, function(attribute ) {
                    $newElement.attr(attribute.name, attribute.value);
                });
                jQuery(this).wrapInner( $newElement ).children().first().unwrap();
            });
        }

        self.reset_element_after_re_render( element, element_model, $newElement ? $newElement : $el, dialog_instance );

    };

    // cache the element for later controls
    self.set_element_cached = function( element_model, element, dialog_instance ){
        if (element instanceof Backbone.View) {
            dialog_instance.setCachedElement( element_model.toJSON() );
        }
    };

    self.reset_ajax = function(caller){
        _.defer(function(){
            self.is_saving = false;
            if( self.dialog_will_close === true ){
                jQuery('body').loaderOverlay('hide');
                self.dialog_will_close = false;
                self.post_content = null;
                self.set_current(null);
                self.init_hover_events();
            }
        });
    };

    self.save_state_changed = function( state ){
        if( state ){
            // console.log('has been saved');
        }
    };

    /**
     * @deprecated
     * @param options
     * @param model
     */
    self.open_info_dialog = function( options, model ){

        var self = this, main_view = self.get_current(), model = model.toJSON();
        model.layout = main_view ? _.extend( {}, main_view.model.toJSON() ) : {};
        model.link = '';

        if( DDLayout_settings.DDL_JS.user_dismissed_dialogs ){
            self.redirect_user( model );
            return;
        }

        dialog = new DDLayout.DialogView({
            title:  DDLayout_settings.DDL_JS.strings.cell_not_editable_in_front_end_title.replace('%CELL%', model.cell_type),
            modal:false,
            resizable: false,
            draggable: false,
            position: {my: "center", at: "center", of: window},
            width: options.width ? options.width : 250,
            selector: '#ddl-info-dialog-tpl',
            template_object: model,
            dialogClass: 'ddl-dialogs-container wp-core-ui',
            buttons: [
                {
                    text: DDLayout_settings.DDL_JS.strings.dialog_cancel,
                    icons: {},
                    class: 'cancel button',
                    click: function() {

                        dialog.dialog_close();

                    }
                },
                {
                    text: DDLayout_settings.DDL_JS.strings.cell_edit_back_end_button.replace('%CELL%', model.cell_type),
                    icons: {},
                    class: 'backend button button-primary',
                    click: function() {

                        dialog.dialog_close();

                        _.defer(function () {
                            self.redirect_user(model);
                        });

                    }
                }
            ],
        });


        dialog.$el.on('ddldialogclose', function (event) {

            dialog.remove();

            if( options.callback instanceof Function ){
                options.callback.call( self, event, options, model, self );
            }

            self.handle_dismiss_dialog();

        });

        dialog.dialog_open();
    };

    /**
     * @deprecated
     * @param model
     */
    self.redirect_user = function( model ){
        var edit_url = self.get_edit_url( model ),
            win = window.open(edit_url, '_blank');
        win.focus();
    };

    /**
     * @deprecated
     */
    self.handle_dismiss_dialog = function(){

        var $checkbox = jQuery('input[name="ddl_popup_blocked_dismiss"]', this.$el );

        console.log( 'checkbox is checked ', $checkbox.is(':checked') );

        if( $checkbox.is(':checked') ){
            var params = {
                'action': 'ddl_dialog_dismiss',
                'ddl_dialog_dismiss_nonce' : jQuery('input[name="ddl_dialog_dismiss_nonce"]').val(),
                'dismiss_dialog' : true,
                'dismiss_dialog_option' : 'ddl_dismiss_dialog_message'
            };
            WPV_Toolset.Utils.do_ajax_post(params, {
                success:function( response, obj ){
                    console.log('success', arguments);
                },
                error: function( response, obj ){
                    console.log('error', arguments);
                },
                fail:function( response, obj ){
                    console.log('fail', arguments);
                }
            });
        }
    };

    self.get_edit_url = function( model ){
        var options = self.SPECIAL_CELLS_OPTIONS[model.cell_type];

        if( !options  ) return '';

        if( model.cell_type == 'child_layout' ){
            var post_id = model.layout.id;
        } else {
            var post_id = model.content[options.field];
        }

        return options.url.replace('%POST_ID%', post_id);
    };

    self.toolset_resource_nice_name = function( model ){
        var options = self.SPECIAL_CELLS_OPTIONS[model.cell_type];

        if( !options  ) return '';

        return options.nice_name;
    };

    self.open_edit_layouts_dropdown = function(){
        var $button = jQuery('.js-ddl-button-edit'),
            layouts_links_data = DDLayout_settings.DDL_JS.layouts;

        if( layouts_links_data.length < 2 ){
            return;
        }

        var template = _.template( jQuery('#tpl-toolset-frontend-etid-layouts-menu').html() );
        layouts_links_data = self.map_layouts_links( layouts_links_data);

        jQuery(document).on('mouseup', '*:not(.js-edit-layout-menu-anchor)', function(event){
            if( jQuery(event.target).is('a') ){
                return true;
            }

            event.stopPropagation();

            if( jQuery(event.target).hasClass('js-ddl-button-edit') ){
                return;
            } else if( jQuery(event.target).hasClass('js-ddl-button-edit') === false && self.go_to_editor_menu_visible ) {
                jQuery(self.go_to_editor_menu_selector).remove();
                self.go_to_editor_menu_visible = false;
            }
        });

        $button.on('click', function(event){

            if( self.go_to_editor_menu_visible === true ){
                jQuery(self.go_to_editor_menu_selector).remove();
                self.go_to_editor_menu_visible = false;
                return;
            }

            jQuery( this ).parent().append( template({layouts:layouts_links_data}) );

            jQuery('.js-edit-layout-menu', jQuery( this ).parent() ).css({
                opacity:1,
                right:'56px'
            }).show();

            self.go_to_editor_menu_visible = true;

        });

    };

    self.map_layouts_links = function( layouts_links_data ){

        var layouts_array = _.map(_.toArray(layouts), function(v){
            return v.toJSON()
        });

        _.each(layouts_links_data, function(layout, index, list){

            var match = _.filter(layouts_array, function(v){
                return +v.id === +layout.id;
            });
            if( match[0].layout_type === 'private' && DDLayout_settings.DDL_JS.post_title ){
                var name = match[0].name;
                name = name.replace( DDLayout_settings.DDL_JS.current_post, DDLayout_settings.DDL_JS.post_title );
                layouts_links_data[index].name = name;
            } else {
                layouts_links_data[index].name = match[0].name;
            }
        });

        return layouts_links_data;
    };

    _.bindAll( self, 'save_layout_from_dialog' );

    self.init();
};

DDLayout.AdminPage.cell_reset_events_on_close = true;

DDLayout_settings.DDL_JS.ns.ready(function () {
    WPV_Toolset.Utils.loader = WPV_Toolset.Utils.loader || new WPV_Toolset.Utils.Loader;
    DDLayout.AdminPage.Rows = {};
    jQuery(document).trigger('DLLayout.admin.before.ready');
    DDLayout.ddl_admin_page = new DDLayout.AdminPage();
    jQuery(document).trigger('DLLayout.admin.ready');
    WPV_Toolset.Utils.eventDispatcher.trigger('dd-layout-main-object-init');
});
