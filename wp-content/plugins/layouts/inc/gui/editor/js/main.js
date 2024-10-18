var DDLayout = DDLayout || {};

DDLayout.local_settings = DDLayout.local_settings || {};
//Models namespace / paths
DDLayout.models = {};
DDLayout.models.abstract = {};
DDLayout.models.cells = {};
DDLayout.models.collections = {};

//Views namespaces / paths
DDLayout.views = {};
DDLayout.views.abstract = {};

//Messages namespace
WPV_Toolset.messages = {};

DDLayout.MINIMUM_CONTAINER_OFFSET = 69;
DDLayout.CELL_MIN_WIDTH = 50;
DDLayout.MARGIN_BETWEEN_CELLS = 16;
DDLayout.MAXIMUM_SPAN = 12;

DDLayout.utils = {};

DDLayout_settings.DDL_JS.ns = head;

DDLayout_settings.DDL_JS.ns.js(
    DDLayout_settings.DDL_JS.lib_path + "backbone_overrides.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.lib_path + "he/he.min.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.common_rel_path + "/res/lib/jstorage.min.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.common_rel_path + "/utility/js/keyboard.min.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.lib_path + "prototypes.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.lib_path +"imagesloaded.pkgd.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + 'ddl-saving-saved-box.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/abstract/Element.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Cell.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Spacer.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/collections/Cells.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Row.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/collections/Rows.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Container.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Tabs.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Tab.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Accordion.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Panel.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/Layout.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "models/cells/ThemeSectionRow.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/abstract/ElementView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/abstract/CollectionView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/CellsView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/RowsView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/RowView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/CellView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/ContainerRowView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/ContainerView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/TabsTabView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/TabsView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/AccordionPanelView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/AccordionView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/SpacerView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + 'parent-helper.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/LayoutView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/ThemeSectionRowView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/UndoRedo.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/KeyHandler.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/Breadcrumbs.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/RowTooltip.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/CellDropPlaceholder.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/AddCellHandler.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/SaveState.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-wpml-box.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-tree-filter.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-types-views-popup.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "preview-manager.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/TooltipsView.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "create-cell-helper.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "default-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "css-cell-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION // Remove
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "css-row-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION // Remove
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "row-edit-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "container-edit-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "dialog-yes-no-cancel.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "layout-settings-dialog.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "dialog-repeating-fields.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.dialogs_lib_path + "html-properties/HtmlAttributesHandler.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION // Remove
    , DDLayout_settings.DDL_JS.dialogs_lib_path +'theme-section-row-edit-dialog.js'
    , DDLayout_settings.DDL_JS.dialogs_lib_path + 'tab-edit-dialog.js'
    , DDLayout_settings.DDL_JS.dialogs_lib_path + 'panel-edit-dialog.js'
    , DDLayout_settings.DDL_JS.dialogs_lib_path +'child-layout-manager.js'
    , DDLayout_settings.DDL_JS.dialogs_lib_path +'toolset-in-iframe.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + 'ddl-bootstrap-size-settings.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + "views/ViewLayoutManager.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.res_path + "/js/ddl_change_layout_use_helper.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.editor_lib_path + "ddl-post-types-options.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.res_path + "/js/ddl-individual-assignment-manager.js?ver=" + DDLayout_settings.DDL_JS.WPDDL_VERSION
    , DDLayout_settings.DDL_JS.res_path + '/js/dd-layouts-parents-watcher.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + 'ddl-duplicator.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + 'ddl-edit-tabs.js'
    , DDLayout_settings.DDL_JS.editor_lib_path + 'ddl-edit-accordion.js'
    , function () {
        _.each(DDLayout.models.cells, function (item, key, list) {
            if (list.hasOwnProperty(key) ) {
                _.defaults(DDLayout.models.cells[key].prototype.defaults, DDLayout.models.abstract.Element.prototype.defaults);
            }
            else {
                console.info("Your model should inherit from Element object");
            }
        });
    }
);


(function ($) {
    WPV_Toolset.Utils.loader = WPV_Toolset.Utils.loader || new WPV_Toolset.Utils.Loader;
    DDLayout_settings.DDL_JS.ns.ready(function () {
        WPV_Toolset.messages.container = jQuery(".js-ddl-message-container");
        jQuery(document).trigger('DLLayout.admin.before.ready');
        DDLayout.ddl_admin_page = new DDLayout.AdminPage($);
        jQuery(document).trigger('DLLayout.admin.ready');
        WPV_Toolset.Utils.eventDispatcher.trigger('dd-layout-main-object-init');
    });
}(jQuery) );

DDLayout.AdminPage = function($)
{
    var self = this,
        layout = null,
        view_layout = null,
        title_pointer_shown = false,
        storageDialog;

    _.extend( DDLayout.AdminPage.prototype, new DDLayout.AdminPageAbstract(jQuery) );

    self.init = function()
    {
        Toolset.hooks.addFilter('ddl-get_containers_elements', self.get_containers_elements, 10, 1 );
        Toolset.hooks.addFilter('ddl-is_private_layout', self.check_is_private_layout, 10 );
        Toolset.hooks.addFilter('ddl_save_layout_params', self.addExtraParamsToPrivateLayoutSaveData, 99, 2 );

        DDLayout.unique_id_created = false;

        // get the layout from the json textarea.
        var json = JSON.parse( WPV_Toolset.Utils.editor_decode64( jQuery('.js-hidden-json-textarea').text() ) );
        // private the main model itself
        layout = new DDLayout.models.cells.Layout( json );
        // private the preview layout manager
        view_layout = new DDLayout.ViewLayoutManager( layout.get('id'), layout.get('name') );

        DDLayout.parents_watcher = new DDLayout.ParentsWatcher($, self);
        // member propertie: editor helpers
        self.instance_layout_view = new DDLayout.views.LayoutView({model:layout});
        self.saving_saved = new DDLayout.SavingSaved( jQuery('.dd-layouts-breadcrumbs') );
        self.private_layout_save_button = jQuery("#js-private-layout-done-button");
        self.private_layout_only_save_button = jQuery( '#js-private-layout-only-save-button' );
        self.private_layout_cancel_button = jQuery("#js-private-layout-cancel-button");
        self.undo_redo = new DDLayout.UndoRedo();
        self.key_handler = new DDLayout.KeyHandler();
        self.breadcrumbs = new DDLayout.Breadcrumbs(layout);
        self.row_tooltip = new DDLayout.RowTooltip();
        self._default_dialog = new DDLayout.DefaultDialog();
        self.bootstrap_settings = new DDLayout.DDL_BootstrapSizeSettings(layout);
        //self._cssCellDialog = new DDLayout.CSSCellDialog;
        self._cssRowDialog = new DDLayout.CSSRowDialog;
        self._save_state = new DDLayout.SaveState();
        self._layout_settings_dialog = new DDLayout.LayoutSettingsDialog();
        self.htmlAttributesHandler = new DDLayout.HtmlAttributesHandler;
        self.wpml_handler = new DDLayout.WPMLBoxHandler();
        self.duplicator = new DDLayout.Duplicator.DuplicateRow();
        self.post_types_options_manager = new DDLayout.PostTypes_Options(self);
        self._add_cell = new DDLayout.AddCellHandler();
        self._tree_filter = new DDLayout.treeFilter();

        self.private_layout_handler(layout);

        self.change_layout_title();

        self.deselect_cell();

        self.is_new_layout();

        self.delete_layout();

        self.show_hide_styling_info();

        self.layoutStorageDialogHandler();

        self._new_cell_target = null;

        jQuery(self._fix_edit_layout_menu_link);

        self._initialize_post_edit();

        self.edit_tab_cell = new DDLayout.EditTabsCell();
        self.edit_accordion_cell = new DDLayout.EditAccordionCell();

        self.instance_layout_view.listenTo(self.instance_layout_view.eventDispatcher, 'ddl-remove-cell', self.remove_cell_callback );
        self.instance_layout_view.listenTo(self.instance_layout_view.eventDispatcher, 'ddl-delete-cell', self.delete_cell_callback );
        self.instance_layout_view.listenTo(self.instance_layout_view.eventDispatcher, 'ddl-remove-row', self.remove_row_callback );
        self.listenTo( self, 'layout_element_model_changed_from_dialog', self.re_render_all );
        self.listenTo( self, 'layout_update_additional_css_classes_array', self.update_css_classes_array );
        self.listenTo( self, 'layout_generate_chosen_selector', self.run_chosen_selector );
        self.listenTo( self, 'layout_show_cell_details', self.update_show_cell_details_option );
        self._save_state.eventDispatcher.listenTo(self._save_state.eventDispatcher, 'save_state_change', self.save_state_changed);
        self.instance_layout_view.listenTo( WPV_Toolset.Utils.eventDispatcher, 'layout_ajaxSynced_completed', self.after_main_model_re_render_callback);

        _.defer( self.init_wpml_vars, layout );
    };

    /**
     * Layout storage functionality
     */
    self.layoutStorageDialogHandler = function () {

        var layoutStorageButton = $( '.js-ddl-layout-storage' );

        layoutStorageButton.click( function( ){
            self.layoutStorageDialogInit();
        });
    };

    /**
     * Layout storage dialog init
     */
    self.layoutStorageDialogInit = function(){

        var storageDialogTemplate = '#ddl-layout-storage-tpl';

        storageDialog = new DDLayout.DialogView({
            title: DDLayout_settings.DDL_JS.strings.layout_storage.title,
            modal: true,
            resizable: false,
            draggable: false,
            position: { my: "center", at: "center", of: window },
            width: 600,
            autoOpen:false,
            selector: storageDialogTemplate,
            buttons: [
                {
                    text: DDLayout_settings.DDL_JS.strings.layout_storage.cancel,
                    icons: {},
                    class: 'cancel button button-secondary',
                    click: function () {
                        jQuery(this).ddldialog( 'close' );
                    }
                },
                {
                    text: DDLayout_settings.DDL_JS.strings.layout_storage.save,
                    icons: {},
                    class: 'button button-primary layout-storage-apply',
                    click: function () {}
                }
            ]
        });

        storageDialog.$el.parent().addClass( 'ddl-layouts-storage-dialog' );

        storageDialog.$el.on( 'ddldialogclose', function ( event ) {
            storageDialog.remove();
        });

        storageDialog.$el.on( 'ddldialogopen', self.storageDialogOpenCallback );

        storageDialog.dialog_open();
    };

    /**
     * Callback for dialog open
     */
    self.storageDialogOpenCallback = function(){

        var layoutStorageTextArea = $( '#js-layouts-storage-json-object' );

        // disable save button
        $( '.layout-storage-apply' ).attr('disabled', true);

        // put layout json in textarea
        try {
            var layoutJson = JSON.stringify( layout.toJSON() );
        } catch (e) {
            console.log( 'invalid json' );
        }
        layoutStorageTextArea.text( layoutJson );

        // enable button on textarea change
        layoutStorageTextArea.on( 'paste', function () {
            var $me = $(this);
            setTimeout(function(){
                var currentVal = $me.val();
                $me.val('').focus();
                document.execCommand('insertHTML', false, currentVal);
                $me.text( currentVal );
                $me.val( currentVal );
            },100);
            $( '.layout-storage-apply' ).attr( 'disabled', false);
        });

        // On save button click show warning button
        $( '.layout-storage-apply' ).click( function(){
            if( $( this ).text() === DDLayout_settings.DDL_JS.strings.layout_storage.save ){

                $( '.js-layout-storage-info-message' ).text( DDLayout_settings.DDL_JS.strings.layout_storage.info_message );
                $( '.js-layout-storage-confirmation-area' ).show();
                $( this ).text( DDLayout_settings.DDL_JS.strings.layout_storage.yes_save );
                $( this ).attr( 'disabled', true );

            } else {
                self.layoutStorageSaveHandler();
            }
        });

        // "I understand" checkbox changed
        $( '.js-confirm-layout-storage-update' ).change( function () {

            if ( $( '.js-confirm-layout-storage-update' ).attr( 'checked' ) ) {
                $( '.layout-storage-apply' ).attr( 'disabled', false );
            } else {
                $( '.layout-storage-apply' ).attr( 'disabled', true );
            }

        });

    };



    /**
     * Save new layout json, re-render layout editor, set correct name value...
     */
    self.layoutStorageSaveHandler = function () {

        var newLayoutJson = $( '#js-layouts-storage-json-object' ).val();

        try {
            var json = JSON.parse( newLayoutJson );
        } catch (e) {
            self.throwErrorForInvalidLayoutStorageJSON();
            return;
        }

        if ( json === null || !json.hasOwnProperty( 'name' ) ) {
            self.throwErrorForInvalidLayoutStorageJSON();
            return;
        }

        layout.parse( json );
        layout.set( 'name', json.name );
        self.re_render_all();
        self._save_state.set_save_required();
        storageDialog.dialog_close();
    };

    self.throwErrorForInvalidLayoutStorageJSON = function(){
        console.log( 'invalid json' );
        // set original valid JSON in textarea
        $( '.js-layout-storage-error' ).remove();
        $( '.js-layout-storage-confirmation-area' )
            .before('<div class="js-layout-storage-error notice notice-error notice-alt">'+DDLayout_settings.DDL_JS.strings.layout_storage.json_format_error+'</div>');

        $( '#js-layouts-storage-json-object' ).val( JSON.stringify( layout.toJSON() ) );
    };


    self.re_render_all = function(){
        self.instance_layout_view.eventDispatcher.trigger( 're_render_all' );
    };

    self.update_show_cell_details_option = function ( optionValue ) {

        var data = {
            action: 'ddl_update_show_cell_details_option',
            option_value: optionValue,
            wpnonce: DDLayout_settings.DDL_JS.change_cell_details_option_nonce
        };

        WPV_Toolset.Utils.do_ajax_post(data, {
            success: function ( response ) {
                if ( true === response.Data ) {
                    DDLayout_settings.DDL_JS.show_cell_details = optionValue;
                    jQuery( '.ddl-show-cell-details-option-wrap' ).hide( 'slow' );
                } else {
                    console.log( 'Error: Option is not updated ', response );
                }
            },
            error: function ( response ) {
                console.log( 'Error: ', response.error_message);
            },
            fail: function ( response ) {
                console.log( 'Error: WordPress AJAX returned ', response);
            },
        });

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


    self.show_hide_styling_info = function(){

        var show_hide_styling_info_button = jQuery( '.ddl-js-info-tooltip-button' );

        show_hide_styling_info_button.click( function( event ) {

            event.preventDefault();

            var all_tooltips = new DDLayout.views.Tooltip( {} ),
                status = jQuery( this ).data( 'status' ) === 'hidden' ? 'show' : 'hidden';

            if ( jQuery( this ).data( 'status' ) === 'hidden' ) {

                jQuery( '#ddl-js-info-tooltip-button-text', jQuery( this ) ).text( DDLayout_settings.DDL_JS.strings.toolbar.hide_styling_info );

                jQuery( this ).data( 'status', 'show' );

                all_tooltips.showAllTooltips();

                Toolset.hooks.doAction( 'ddl-stop-filters-hover');

            } else {

                jQuery( '#ddl-js-info-tooltip-button-text', jQuery( this ) ).text( DDLayout_settings.DDL_JS.strings.toolbar.show_styling_info );

                jQuery( this ).data( 'status', 'hidden' );

                Toolset.hooks.doAction( 'ddl-init-filters-hover');

                all_tooltips.removeAllTooltips();

            }
        });
    };

    self.run_chosen_selector = function( array_with_classes, $context ){
        var chosen_args = {
            'width': "555px",
            'no_results_text': 'Press Enter to add new entry:',
            'display_selected_options': false,
            'display_disabled_options': false
        };

        var availableClasses = [];
        var additionalCssClasses = self.getNestedProperty( DDLayout_settings, 'DDL_JS.layouts_css_properties.additionalCssClasses' );
        if( Array.isArray( additionalCssClasses ) === true ){
            availableClasses = additionalCssClasses;
        }

        if( $context ){
            jQuery('select.js-toolset-chosen-select', $context ).toolset_chosen_multiple_css_classes( chosen_args, availableClasses, array_with_classes );
        } else {
            jQuery('select.js-toolset-chosen-select', jQuery('#ddl-row-edit') ).toolset_chosen_multiple_css_classes( chosen_args, availableClasses, array_with_classes );
        }

    };

    /**
     * Validate and return value of last object property, return null in case if something is missing
     * @param obj
     * @param path
     * @returns property value
     */

    self.getNestedProperty = function(obj, path) {

        if(typeof path === 'undefined'){
            return null;
        }
        var parts = path.split( "." );
        if ( parts.length == 1 ){
            if( typeof obj[parts[0]] === 'undefined' ){
                return null;
            }
            return obj[parts[0]];
        }
        if( typeof obj[parts[0]] === 'undefined' ){
            return null;
        }
        return self.getNestedProperty( obj[parts[0]], parts.slice(1).join(".") );
    };

    self.after_main_model_re_render_callback = function( ){
        _.defer(function(){
            self.delete_layout();
        });
    };

    self.before_open_dialog = function(){
        return true;
    };

    self.get_containers_elements = function( elements ){
        return DDLayout_settings.DDL_JS.container_elements
    };

    self.check_is_private_layout = function( ){
        self.is_private_layout = DDLayout_settings.DDL_JS.is_private_layout;
        return self.is_private_layout;
    };

    self.addExtraParamsToPrivateLayoutSaveData = function( params, mainView ){

        if( ! self.check_is_private_layout() ){
            return params;
        } else {
            params.render_private = "ddl_update_post_content_for_private_layout"
        }

        return params;
    };

    /*
     * Check is this private layout, in case if it is, hide header, breadcrumbs and assignments area
     */
	self.private_layout_handler = function( layout ){
		// When editing a Content Template using Layouts, the URL for the "Save and Close" button as well as the URL for
		// the "Cancel" button need to be adjusted to redirect the user to the right place after the relevant action is
		// taken.
		var saveCloseReturnURL = "post.php?post="+layout.get('id')+"&action=edit&message=1",
			cancelReturnURL = "post.php?post="+layout.get('id')+"&action=edit";

		var sourcePage = self.getUrlParameter( 'source' );
		if ( 'undefined' !== typeof sourcePage ) {
			switch ( sourcePage ) {
				case 'views-editor':
					var viewID = self.getUrlParameter( 'view_id' );
					if ( 'undefined' !== viewID ) {
						saveCloseReturnURL = cancelReturnURL = 'admin.php?page=views-editor&view_id=' + viewID;
					}
					break;
				case 'ct-editor':
					saveCloseReturnURL = cancelReturnURL ='admin.php?page=ct-editor&ct_id=' + layout.get('id');
					break;
			}
		}

		self.private_layout_save_button.attr( 'href', saveCloseReturnURL );

        if(DDLayout_settings.DDL_JS.is_new_layout === false){
            self.private_layout_save_button.attr('disabled',true);
        }

        self.private_layout_cancel_button.attr( 'href', cancelReturnURL );
        self.private_layout_cancel_button.attr( 'data-button_label', 'close' );

        $(document).on('click', '#js-private-layout-done-button', function (event) {
            event.preventDefault();
            self.private_layout_save_button.text("Saving...");
            // Save all changes first
            self.save_layout(self.private_layout_redirect_back_to_content);

        });

        $(document).on('click', '#js-private-layout-only-save-button', function (event) {
            event.preventDefault();
            self.save_layout( self.privateLayoutSaveIndicator( ) );
        });



        /**
         * This is covering "edge case" when user disable content layout,
         * enable it again, and then click on the close button. In that case
         * it is necessary to update content with necessary css classes for
         * front-end editor.
         */
        self.private_layout_cancel_button.on( 'click', function (event) {
            event.preventDefault();
            if( 'close' === jQuery( this ).data( 'button_label' ) ){
                self.save_layout( self.private_layout_redirect_back_to_content );
            } else {
                document.location.href = self.private_layout_cancel_button.attr( 'href' );
            }
        });

    };

    self.privateLayoutSaveIndicator = function( ){
        jQuery( '.toolbar_for_private_layout' ).addClass( 'changes_saved_indicator', 150 );
        jQuery( '.toolbar_for_private_layout' ).removeClass( 'changes_saved_indicator', 400 );

        jQuery( '.editor-toolbar-private' ).addClass( 'changes_saved_indicator', 150 );
        jQuery( '.editor-toolbar-private' ).removeClass( 'changes_saved_indicator', 400 );

        self.private_layout_cancel_button.text( DDLayout_settings.DDL_JS.close );
        self.private_layout_cancel_button.attr( 'data-button_label', 'cancel' );
    };

    self.private_layout_redirect_back_to_content = function(){
        var content_edit_page = self.private_layout_save_button.attr('href');
        document.location.href = content_edit_page;
    };

    self.is_layout_assigned = function(){
        return Toolset.hooks.applyFilters('ddl-is_current_layout_assigned', DDLayout_settings.DDL_JS.is_layout_assigned );
    };

    self.button_trash_enable_disable = function( ){
        var disable_button = false;

        if( self.instance_layout_view.model.is_parent()  ){
            var children_string = jQuery('#js-layout-children').text(),
                children = JSON.parse( children_string );

            if( 'children_layouts' in children && children['children_layouts'].length ){
                disable_button = true;
            }
        }

        return disable_button;
    };

    self.delete_layout = function(){
        var $button = jQuery('.js-trash-layout');

        if( self.button_trash_enable_disable() ){
            jQuery('.js-trash-layout').prop('disabled', true);
            jQuery('.trash-layout i').fadeTo( 100 , 0.5);
            return;
        } else {
            jQuery('.js-trash-layout').prop('disabled', false);
            jQuery('.trash-layout i').fadeTo( 100 , 1);
        }

        jQuery(document).off( 'click', '.js-trash-layout', self.delete_layout_callback );
        jQuery(document).on( 'click', '.js-trash-layout', self.delete_layout_callback );
    };

    self.delete_layout_callback = function(event){
        event.preventDefault();
        event.stopPropagation();

        if( ! DDLayout_settings.DDL_JS.user_can_delete ) return false;

        if( self.is_layout_assigned() ){
            self.layout_assigned_dialog( self.instance_layout_view.model );
            return false;
        }

        var data = {
            action:"set_layout_status",
            status:"trash",
            'layout-select-trash-nonce': DDLayout_settings.DDL_JS.layout_trash_nonce,
            layout_id:self.instance_layout_view.model.get('id'),
            current_page_status:"publish",
            do_not_reload:"yes"
        };

        WPV_Toolset.Utils.loader.loadShow( jQuery(this), true ).css({
            position:'absolute',
            right:'60px',
            bottom:'2px'
        });

        WPV_Toolset.Utils.do_ajax_post( data, {
            success:function(response){
                location.href = DDLayout_settings.DDL_JS.trash_redirect
            },
            error:function( response ){

            },
            fail:function( response ){

            },
            always:function(){
                WPV_Toolset.Utils.loader.loadHide();
            }
        });
    };

    self.layout_assigned_dialog = function(layout_model){

        var dialog = new DDLayout.ViewLayoutManager.DialogView({
            title:  layout_model.get('name') + DDLayout_settings.DDL_JS.strings.layout_assigned,
            modal:true,
            width: 400,
            selector: '#ddl-delete-layout-dialog-tpl',
            template_object: {
                layout_name: layout_model.get('name'),
            },
            buttons: [
                {
                    text: DDLayout_settings.DDL_JS.strings.close,
                    icons: {
                        secondary: ""
                    },
                    click: function () {
                        jQuery(this).ddldialog("close");
                    }
                },
            ]
        });

        dialog.$el.on('ddldialogclose', function (event) {
            dialog.remove();
        });

        dialog.dialog_open();
    };

    self.enable_content_layout_buttons = function(){
        self.private_layout_save_button.attr('disabled',false);
        self.private_layout_cancel_button.text('Cancel');
        self.private_layout_cancel_button.attr( 'data-button_label', 'cancel' );
    };

    self.save_state_changed = function( state ){
        if( state ){
            self.enable_content_layout_buttons();
            self.saving_saved.remove();
        }
    }

    self.get_current_layout_id = function(){
        return DDLayout_settings.DDL_JS.layout_id;
    };

    self.initialize_where_used_ui = function (layout_id, include_spinner) {
        var where_used_ui = jQuery('.js-where-used-ui');

        if (where_used_ui.length) {

            if (include_spinner) {
                var child_div = where_used_ui.find('.dd-layouts-where-used');
                if (child_div.length) {
                    child_div.html('<div class="spinner ajax-loader" style="float:none; display:inline-block"></div>');
                }
            }

            var data = {
                action : 'ddl_get_where_used_ui',
                layout_id: layout_id,
                wpnonce : jQuery('#ddl_layout_view_nonce').val()
            };
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function(data) {
                    where_used_ui.empty().html(data);
                    // self.post_types_options_manager.openDialog();
                }
            });
        }
    };

    self._initialize_post_edit = function () {
        if (jQuery('#post').length) {
            jQuery('#post').submit(function (e) {
                jQuery('.js-hidden-json-textarea').text(JSON.stringify(self.get_layout_as_JSON()));
                self._save_state.clear_save_required();
            });
        }
    };

    self.remove_cell_callback = function( view, handler )
    {
        var model = view.model;

        if( model.get('cell_type') === "child-layout" ) {
            var child_dialog = new DDLayout.ChildLayoutManager( view, handler, 'ddl-delete-cell');
        } else if( model.get('cell_type') === "views-content-grid-cell" ) {
            DDLayout.views_preview.clear_cache();
            view.eventDispatcher.trigger( 'ddl-delete-cell' );
        } else {
            view.eventDispatcher.trigger( 'ddl-delete-cell' );
        }

        self.instance_layout_view.eventDispatcher.trigger('cell_removed', view.model, 'remove' );

    };

    self.remove_row_callback = function( row_view, handler )
    {
        if (row_view.hasChildLayoutCellAndChildren()) {
            var child_dialog = new DDLayout.ChildLayoutManager( row_view, handler, 'ddl-delete-row');
        } else {
            row_view.deleteTheRow();
        }
    }

    self.delete_cell_callback = function(model)
    {
        if( typeof model !== 'undefined' && model.get('cell_type') === "child-layout"){
            self.instance_layout_view.eventDispatcher.trigger( 'ddl-delete-child-layout-cell',  'delete', JSON.stringify( { children_layouts : [] } ) );
        }
        self.delete_selected_cell(null);
    };

    self.get_framework = function()
    {
        return DDLayout_settings.DDL_JS.current_framework;
    };

    self.deselect_cell_handler = function(event)
    {
        var rightclick = false,
            is_mouse_tooltip = jQuery( event.target ).closest('.wp-pointer').length > 0,
            is_text_edit = event.target.id == "celltexteditor-tmce",
            is_colorbox = jQuery("#colorbox").css("display") == "block";
        if (event.which) rightclick = (event.which == 3);
        else if (event.button) rightclick = (event.button == 2);

        if ( !rightclick && is_mouse_tooltip === false && is_text_edit === false && is_colorbox === false) {
            event.stopImmediatePropagation();
            event.data.self.instance_layout_view.eventDispatcher.trigger("deselect_element");
        }
    };
    self.deselect_cell = function()
    {
        var self = this;
        jQuery(document).on("click", {self:self}, self.deselect_cell_handler);
    };

    self.move_selected_cell_left = function(event) {
        self.instance_layout_view.eventDispatcher.trigger('move_selected_cell_left', event);
    };

    self.move_selected_cell_right = function(event) {
        self.instance_layout_view.eventDispatcher.trigger('move_selected_cell_right', event );
    };

    self.delete_selected_cell = function(event) {
        self.save_undo();
        self.instance_layout_view.eventDispatcher.trigger('delete_selected_cell', event);
    };


    self.set_new_target_cell = function (cell_view) {
        self._new_cell_target = cell_view;
    };

    self.get_new_target_cell = function () {
        return self._new_cell_target;
    };


    self.is_new_layout = function(){
        if(self.getUrlParameter("new") === "true"){
            if(jQuery(".js-layout-title").val() === 'New Layout'){
                jQuery(".layout-title-input").addClass('new_layout_alert_border');
                jQuery("#change_layout_name_message").css("display", 'block');
            }
            if(jQuery("#js-print_where_used_links li").length === 0){
                jQuery(".js-layout-content-assignment-button").addClass('new_layout_alert_border');
            }
        }

        jQuery(document).on('click', ".js-layout-content-assignment-button", function(){
            jQuery(".js-layout-content-assignment-button").removeClass('new_layout_alert_border');
        });

    };

    self.change_layout_title = function () {
        var self = this,
            el = jQuery('.js-edit-layout-slug')
            , edit_button = jQuery('.js-edit-slug')
            , $ok_button_wrap = jQuery('.js-edit-slug-buttons-active')
            , $ok_button = jQuery('.js-edit-slug-save')
            , $cancel_link = jQuery('.js-cancel-edit-slug');

        jQuery(document).on('click', '.js-edit-slug', function(event){
            event.preventDefault();
            event.stopPropagation();
            el.trigger('click');
        });

        jQuery(".js-layout-title").on( 'click', function(event){
            if(jQuery(".js-layout-title").val() === 'New Layout'){
                jQuery(".js-layout-title").val('');
            }
        });

        jQuery(".js-layout-title").on( "focusout", function(event){
            if(jQuery(".js-layout-title").val() === ''){
                jQuery(".js-layout-title").val('New Layout');
                return;
            }
        });

        jQuery(".js-layout-title").on( 'change', function (event) {
            event.preventDefault();
            jQuery(".layout-title-input").removeClass('new_layout_alert_border');
            jQuery("#change_layout_name_message").fadeOut( "slow" );

            if( layout.get('slug').indexOf('new-layout') === -1 ){
                return;
            }

            var parent = jQuery(this).parent(),
                input = jQuery('<input id="layout-slug" name="layout-slug" type="text" class="edit-layout-slug js-edit-layout-slug" />'),
                data = {
                    el: el,
                    self: self.instance_layout_view,
                    is_title: true,
                    input: input,
                    edit_button:edit_button,
                    ok_button_wrap:$ok_button_wrap
                };

            self.is_slug_edited = true;
            var new_val = jQuery(".js-layout-title").val();

            if( self.check_slug_is_not_empty( new_val ) )
            {
                self.edit_slug_server_call( new_val, data, event );
                jQuery(this).off('click');
            }
            jQuery("#layout-slug").text(jQuery(".js-layout-title").val());
        });


        el.on('click', function (event) {
            event.stopImmediatePropagation();

            if( self.is_slug_edited ) return false;

            DDLayout.ddl_admin_page.take_undo_snapshot();
            var parent = jQuery(this).parent(),
                old_title = jQuery(this).text(),
                index = jQuery(this).index(),
                input = jQuery('<input id="layout-slug" name="layout-slug" type="text" class="edit-layout-slug js-edit-layout-slug" />'),

                data = {
                    el: el,
                    input: input,
                    self: self.instance_layout_view,
                    is_title: true,
                    old_title:old_title,
                    edit_button:edit_button,
                    ok_button_wrap:$ok_button_wrap
                };

            DDLayout.AdminPage.setCaretPosition( input[0], old_title.length );

            edit_button.parent().hide();
            $ok_button_wrap.css('display', 'inline-block');

            $ok_button.on('click', function(event, not_call){
                event.preventDefault();
                self.is_slug_edited = false;
                var new_val = input.val();

                if( new_val === old_title )
                {
                    $cancel_link.trigger('click');
                    jQuery(this).off('click');
                    return;
                }

                if( self.check_slug_is_not_empty( new_val ) && typeof not_call === 'undefined' )
                {
                    self.edit_slug_server_call( new_val, data, event );
                    jQuery(this).off('click');
                }
                else if( not_call === true )
                {
                    event.data = data;
                    event.data.input.val( input.val() );
                    DDLayout.AdminPage.manageDeselectElementName( event, {not_call:not_call} );
                }

            });

            $cancel_link.on('click', function(event){
                event.preventDefault();
                self.is_slug_edited = false;
                event.data = data;
                event.data.original_value = old_title;
                DDLayout.AdminPage.manageDeselectElementName( event );
                jQuery(this).off('click');
            });

            input.val(old_title);

            jQuery(this).addClass('hidden');

            parent.insertAtIndex(index, input);

            parent.css("position", "relative");

            input.keydown(function (event) {
                // on enter, just save the new slug, don't save the post
                if ("Enter" == event.key) {
                    $ok_button.trigger('click');
                    return false;
                }
                if ("Escape" == event.key) {
                    $cancel_link.trigger('click');
                    return false;
                }

                setTimeout(function(){
                    if( event.target.value !== old_title )
                    {
                        jQuery('input[name="save_layout"]').prop('disabled', false);
                        self.is_slug_edited = true;
                    }
                    else
                    {
                        self.is_slug_edited = false;
                        jQuery('input[name="save_layout"]').prop('disabled', true);
                    }
                }, 1);

            }).focus()[0].setSelectionRange(0, 0);


            self.instance_layout_view.listenTo(self.instance_layout_view.eventDispatcher, 'layout-model-trigger-save', function(event, val){
                if( self.is_slug_edited ){
                    $ok_button.trigger('click', true);
                    self.is_slug_edited = false;
                }
            });

        });
    };

    self.edit_slug_server_call = function( new_slug, event_data, event )
    {

        WPV_Toolset.Utils.loader.loadShow( jQuery('.js-ddl-layout-storage'), true );

        var params = {
            edit_layout_slug_nonce : DDLayout_settings.DDL_JS.edit_layout_slug_nonce,
            slug : new_slug,
            layout_id : DDLayout_settings.DDL_JS.layout_id,
            action : 'edit_layout_slug'
        };
        WPV_Toolset.Utils.do_ajax_post(params, {success:function(response){
            WPV_Toolset.Utils.loader.loadHide();
            var data = response.Data;
            self.is_slug_edited = false;
            if( data && data.hasOwnProperty('slug') )
            {
                event.data = event_data;
                event.data.input.val( data.slug );
                DDLayout.AdminPage.manageDeselectElementName( event );
            }

        }});
    };

    self.check_slug_is_not_empty = function( new_val )
    {
        if( new_val == '' )
        {
            WPV_Toolset.messages.container.wpvToolsetMessage({
                text: DDLayout_settings.DDL_JS.strings.invalid_slug,
                type: 'error',
                stay: false,
                close: false,
                onOpen: function() {
                    jQuery('html').addClass('toolset-alert-active');
                },
                onClose: function() {
                    jQuery('html').removeClass('toolset-alert-active');
                }
            });

            return false;
        }
        else{
            return true;
        }
    };

    self._fix_edit_layout_menu_link = function() {
        var current_url = window.location.href;

        jQuery('a.current').each( function() {
            var link = jQuery(this).attr('href');
            if (link.indexOf('page=dd_layouts_edit') != -1) {
                jQuery(this).attr('href', current_url);
            }
        });
    };

    self.handle_add_cell_click = function (cell_view) {
        return self._add_cell.handle_click(cell_view);
    };

    self.handle_cell_enter = function (cell_view) {
        return self._add_cell.handle_enter(cell_view);
    };

    self.show_create_new_cell_dialog = function (cell_view, columns) {
        self._add_cell.show_create_new_cell_dialog(cell_view, columns);
    };

    self.switch_to_layout = function (post_id) {

        self.clear_save_required();

        var current_url = window.location.href;
        var post_pos = current_url.indexOf('layout_id=');
        var post_pos_end = current_url.indexOf('&', post_pos);
        if (post_pos_end == -1) {
            post_pos_end = current_url.length;
        }
        var post_data = current_url.substr(post_pos, post_pos_end - post_pos);
        current_url = current_url.replace(post_data, 'layout_id=' + post_id);


        window.location.href = current_url;
    };

    self.save_layout_from_dialog = function (caller, element, model_cached, css_saved, dialog_instance) {
        var model = element.model;
        DDLayout.ddl_admin_page.instance_layout_view.eventDispatcher.trigger('save_layout_to_server',
            DDLayout.ddl_admin_page.loader_target(caller),
            function (model, response) {

                if (element instanceof Backbone.View) {
                    dialog_instance.setCachedElement( element.model.toJSON() );
                }
            });
    };


    self.loader_target = function( $caller ){
        var $save = jQuery('input[name="save_layout"]'),
            close = jQuery($caller).data('close') === 'yes' ? true : false ;

        return close ? $save : jQuery($caller);
    };

    self.init();
};

//maybe to be moved in utils library
DDLayout.AdminPage.setCaretPosition = function(elem, caretPos) {
    var el = elem;

    el.value = el.value;
    // ^ this is used to not only get "focus", but
    // to make sure we don't have it everything -selected-
    // (it causes an issue in chrome, and having it doesn't hurt any other browser)

    if (el !== null) {

        if (el.createTextRange) {
            var range = el.createTextRange();
            range.move('character', caretPos);
            try{
                range.select();
            } catch( e ){
                // silently do nothing without blocking the browser
            }

            return true;
        }

        else {
            // (el.selectionStart === 0 added for Firefox bug)
            if (el.selectionStart || el.selectionStart === 0) {
                el.focus();
                el.setSelectionRange(caretPos, caretPos);
                return true;
            }

            else  { // fail city, fortunately this never happens (as far as I've tested) :)
                el.focus();
                return false;
            }
        }
    }
};

// some static methods to be used everywehere regardless of the instance
DDLayout.AdminPage.manageDeselectElementName = function( event, args )
{
    event.stopPropagation();

    var self = event.data.self,
        input = event.data.input,
        el = event.data.el,
        old_title = event.data.old_title,
        new_val = input.val(),
        value = '';

    // this is for title editing only
    if ( event.target === input[0] ) {

        if(!event.data.is_title) DDLayout.AdminPage.setCaretPosition( input[0], self.mouse_caret );
        return true;
    }

    // this is for title editing only
    if ( args && args.cancel )
    {
        el.text( args.val ).show();
    }
    // slug editing
    else if( new_val !== old_title && typeof event.data.original_value === 'undefined'  )
    {
        DDLayout.ddl_admin_page.add_snapshot_to_undo();

        if(  new_val == '' && event.data.is_title )
        {
            input.val( old_title );
            value = old_title;

            WPV_Toolset.messages.container.wpvToolsetMessage({
                text: DDLayout_settings.DDL_JS.strings.invalid_slug,
                type: 'error',
                stay: false,
                close: false,
                onOpen: function() {
                    jQuery('html').addClass('toolset-alert-active');
                },
                onClose: function() {
                    jQuery('html').removeClass('toolset-alert-active');
                }
            });
        }
        else
        {
            if( event.data.is_title  )
            {
                if( typeof args === 'undefined' ) self.model.set( 'slug', new_val );
            }
            else
            {
                self.model.set( 'name', new_val );
            }
            value = new_val;
        }
    }
    else{
        value = old_title;
    }

    if( event.data.edit_button && event.data.ok_button_wrap)
    {
        event.data.edit_button.parent().show();
        event.data.ok_button_wrap.hide();
    }

    input.remove();


    if( typeof args === 'undefined' ){
        el.text(value)
    }

    el.removeClass('hidden')
        .css('visibility', 'visible');

    if ( event.data.is_title && typeof args === 'undefined' ) {
        jQuery(".js-edit-layout-slug").text( value );
    }

    DDLayout.ddl_admin_page.element_name_editable_now.pop();
    DDLayout.ddl_admin_page.is_in_editable_state = false;

    jQuery(document).not(input).off( "mouseup", DDLayout.AdminPage.manageDeselectElementName );

    if( self instanceof DDLayout.views.ContainerView )
    {
        self.model.trigger('manage-deselect-element-name');
    }

    return true;
};

/**
 * Loads CRED Object and fixes issues with CRED dialog bugs with toolset_select2 in $.colorbox
 * @type {{show: DDLayout.AdminPage.handleCredIssuesEventually.show, hide: DDLayout.AdminPage.handleCredIssuesEventually.hide, init: DDLayout.AdminPage.handleCredIssuesEventually.init}}
 */
DDLayout.AdminPage.handleCredIssuesEventually = {
    registered:false,
    show:function(){
        var self = this;
        Toolset.hooks.addAction('cred-popup-box_show', function(){
            jQuery('.cred-popup-box').css('z-index', '1000000000000000000000000000000000000000000000');
            jQuery('.ddl-markup-controls').css('z-index', '-1');
            jQuery('.ddl-markup-controls').find('div').each(function(){
                jQuery(this).css('z-index', '-1');
                jQuery(this).find('input').each(function(){
                    jQuery(this).css('z-index', '-1')
                })
            });
            if( self.registered === false ){
                self.fix_option_radio_issue_on();
                self.registered = true;
            }
        });
    },
    hide: function(){
        var self = this;
        Toolset.hooks.addAction('cred_cred_short_code_dialog_close', function(){
            jQuery('.cred-popup-box').css('z-index', '1000');
            jQuery('.ddl-markup-controls').css('z-index', '9999');
            jQuery('.ddl-markup-controls').find('div').each(function(){
                jQuery(this).css('z-index', '999999999999');
                jQuery(this).find('input').each(function(){
                    jQuery(this).css('z-index', '99999999999999')
                })
            });
            self.fix_option_radio_issue_off();
        });
    },
    fix_option_radio_issue_on:function(){
        var self = this;
        jQuery(document).on('change', 'input[value="edit-other-user"], input[value="edit-current-user"]', self.handle_change_user);
        jQuery(document).on('change', 'input[value="edit-current-post"], input[value="edit-other-post"]', self.handle_change_post);
    },
    fix_option_radio_issue_off:function(){
        var self = this;
        self.set_defaults();
        jQuery(document).off('change', 'input[value="edit-other-user"], input[value="edit-current-user"]', self.handle_change_user);
        jQuery(document).off('change', 'input[value="edit-current-post"], input[value="edit-other-post"]', self.handle_change_post);
        self.registered = false;
    },
    handle_change_user:function (event) {
        event.stopImmediatePropagation();
        var $select = jQuery( 'select[name="cred_user_form-edit-shortcode-select-2"]' );
        var form_id = $select.eq($select.length-1).val();
        var form_name = jQuery("option:selected", jQuery(this)).text();
        var loader = jQuery('#cred-user-form-addtional-loader').show();
        jQuery.ajax({
            url: ajaxurl + '?action=cred_ajax_Posts&_do_=getUsers&form_id='+form_id,
            timeout: 10000,
            type: 'GET',
            data: '',
            dataType: 'html',
            success: function (result)
            {
                jQuery('.cred-edit-other-user-more2').show();
                jQuery('.cred-edit-user-toolset_select2').html(result);
                loader.hide();
            },
            error: function ()
            {
                loader.hide();
            }
        });
    },
    handle_change_post:function(event){
        event.stopImmediatePropagation();
        var $select = jQuery( 'select[name="cred_form-edit-shortcode-select-2"]' );
        var form_id = $select.eq($select.length-1).val();
        var form_name = jQuery("option:selected", jQuery(this)).text();
        var loader = jQuery('#cred-form-addtional-loader2').show();
        jQuery.ajax({
            url: ajaxurl + '?action=cred_ajax_Posts&_do_=getPosts&form_id='+form_id,
            timeout: 10000,
            type: 'GET',
            data: '',
            dataType: 'html',
            success: function (result)
            {
                jQuery('.cred-edit-other-post-more2').show();
                jQuery('.cred-edit-post-toolset_select2').html(result);
                loader.hide();
            },
            error: function ()
            {
                loader.hide();
            }
        });
    },
    set_defaults:function(){
        jQuery('input[value="edit-current-post"]').prop('checked', true).trigger('change');
        jQuery('input[value="edit-current-post"]').prop('checked', true).trigger('change');
        jQuery('input[value="insert-form"]').prop('checked', true).trigger('change');
    },
    init: function(){
        var self = this;

        Toolset.hooks.addFilter('cred_cred_cred_run', function(cred_cred, cred_settings, cred_utils, cred_gui){

            if( typeof cred_cred !== 'undefined' && cred_cred.hasOwnProperty('posts') ){
                return cred_cred.posts.call(window);
            }

            return null;
        });

        Toolset.hooks.addFilter('cred_cred_aux_reload_button_content_ajax', function( bool ){
                return false;
        });

        this.show();
        this.hide();
    }
};
DDLayout.AdminPage.handleCredIssuesEventually.init();

DDLayout.AdminPage.Rows = {};

DDLayout.AdminPage.tooltips = 0;

DDLayout.AdminPage.infoButtonText = function(  ){
    var $button = jQuery( '.ddl-js-info-tooltip-button' ),
        button_text = jQuery('#ddl-js-info-tooltip-button-text',  $button );

    if( DDLayout.AdminPage.tooltips === 0 ){
        button_text.text( DDLayout_settings.DDL_JS.strings.toolbar.show_styling_info );
        $button.data( 'status', 'hidden' );
    } else {
        button_text.text( DDLayout_settings.DDL_JS.strings.toolbar.hide_styling_info );
        $button.data( 'status', 'show' );
    }
};
