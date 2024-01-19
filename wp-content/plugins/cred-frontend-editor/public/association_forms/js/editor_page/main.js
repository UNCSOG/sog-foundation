/**
 * Manage the association form edit page.
 *
 * @since m2m
 * @package CRED
 */
var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

// the head.js object
Toolset.CRED.head = Toolset.CRED.head || head;

Toolset.CRED.AssociationFormsEditor = {};
Toolset.CRED.AssociationForms = {};
Toolset.CRED.AssociationFormsEditor.viewmodels = {};

Toolset.CRED.AssociationFormsEditor.Class = function( $ ) {
    // private variables in scope
    var self = this, model = null;

    // member variable editor
    self.editorSelector = 'cred_association_form_content';
    self.editorMode = 'myshortcodes';
    self.editor = self.editor || {};
    self.actions = null;
    self.wizardEnabled = false;

    // Extend the generic listing page controller.
    Toolset.Gui.AbstractPage.call(self);

    // Enable or disable
    self.setWizardStatus = function ( status ) {
        self.wizardEnabled = status;
    };
    self.displayWizard = function () {
        var modelData = self.getModelData();
        if( modelData.action === 'cred_association_form_edit' ){
            self.setWizardStatus( false ) ;
        } else {
            // TODO: check user preference

            self.setWizardStatus( true ) ;
        }
    };

    self.getBackboneModel = function(){
        return model;
    };

    self.initializeBackboneModel = function(){
        try{
            var data = Toolset.CRED.AssociationFormsEditor.formModelData;

            data.wpnonce = jQuery('input[name="'+Toolset.CRED.AssociationFormsEditor.wpnonce+'"]').val();

            var create = new Toolset.CRED.AssociationFormCreateEdit( Toolset.CRED.AssociationFormsEditor.action, data );
            var backboneModel = create.getModel();

            return backboneModel;

        } catch( error ){
            console.log( 'Cannot create or edit association form %s', error );
            return null;
        }
    };

    self.getMainViewModel = function() {
        model = self.initializeBackboneModel();
        var mainModel = self.getModelData();
        var viewModel = new Toolset.CRED.AssociationFormsEditor.viewmodels.AssociationFormViewModel( model, { 'has_relationships' : mainModel.has_relationships }, mainModel.formModelData, mainModel.scaffold, mainModel.form_container );
        ko.applyBindings(viewModel);
        return viewModel;
    };

    self.beforeInit = function() {
        Toolset.hooks.addAction( 'cred_editor_init_top_bar', self.initTopBar );

        var modelData = self.getModelData();
        //noinspection JSUnresolvedVariable
        Toolset.CRED.AssociationFormsEditor.toolsetFormsVersion = modelData.toolsetFormsVersion;
        Toolset.CRED.AssociationFormsEditor.jsPath = modelData.jsIncludePath;
        Toolset.CRED.AssociationFormsEditor.jsEditorPath = modelData.jsEditorIncludePath;
        Toolset.CRED.AssociationFormsEditor.action = modelData.action;
        Toolset.CRED.AssociationFormsEditor.selectedPost = modelData.selected_post;
        Toolset.CRED.AssociationFormsEditor.form_type = modelData.form_type;
        Toolset.CRED.AssociationFormsEditor.wpnonce = modelData.wpnonce;
        Toolset.CRED.AssociationFormsEditor.select2nonce = modelData.select2nonce;
        Toolset.CRED.AssociationFormsEditor.formModelData = modelData.formModelData;
        self.initStaticData( modelData );
        self.displayWizard();

        Toolset.hooks.addFilter( 'cred_editor_is_grid_enabled', function() { return modelData.scaffold.grid_enabled; } );
        Toolset.hooks.addFilter( 'cred_editor_get_bootstrap_version', function() { return modelData.scaffold.bootstrap_version; } );
    };

    self.fixEditorMenuItemUrl = function(){
        var $link = jQuery('a.current'), id = model.get('id'), url = $link.prop('href');
        if( id ){
            $link.prop( 'href', url + '&action=edit&id=' + id );
        }
    };


    self.afterInit = function() {
        self.initIclEditor();
        self.actions =  new Toolset.CRED.AssociationFormActions();
        self.fixEditorMenuItemUrl();

        Toolset.hooks.addAction( 'cred_editor_exit_wizard_mode', self.initTopBar );
        Toolset.hooks.addAction( 'cred_editor_exit_wizard_mode', self.reinitialiseContentEditor );
    };

    self.deleteForm = function( associationForm ) {

        var data = {
            'to_delete' : associationForm,
            'delete_type' : 'single'
        };

        var dialog = Toolset.CRED.AssociationForms.dialogs.DeleteForm( data, function(result) {
            self.actions.delete_single_form( function( updated_model, response ){
                if(response.success === true){
                    window.location.href = window.location.origin+window.location.pathname+'?page=cred_relationship_forms';
                } else {
                    associationForm.displayedMessage({text: response.data.message, type: 'error'});
                    associationForm.messageVisibilityMode('show')
                }
                associationForm.display.isSaving(false);
            }, result  );
        }, self);

        dialog.display();
    };

    self.reinitialiseContentEditor = function(){
        self.editorDestroy();
        self.initIclEditor();
        self.fixEditorMenuItemUrl();
    };


    self.initIclEditor = function () {
        self.buildCodeMirror();
        self.refreshContentEditor();
        self.addQtButtons();
        self.addHooks();
        _.defer( function() {
            self.addBootstrapGridButton();
        });
    };

    self.buildCodeMirror = function(){
        CodeMirror.defineMode( self.editorMode, codemirror_shortcodes_overlay );
        WPV_Toolset.CodeMirror_instance[self.editorSelector] = icl_editor.codemirror(
            self.editorSelector,
            true,
            self.editorMode
        );

        self.editor.codemirror = WPV_Toolset.CodeMirror_instance[self.editorSelector];
    };

    self.editorDestroy = function(){
        WPV_Toolset.CodeMirror_instance[self.editorSelector] = null;
        window.iclCodemirror[self.editorSelector] = null;
    };

    self.addQtButtons = function(){
        self._visual_editor_html_editor_qt = quicktags( { id: 'cred_association_form_content', buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
        WPV_Toolset.add_qt_editor_buttons( self._visual_editor_html_editor_qt, self.editor.codemirror );
    };

	self.addBootstrapGridButton = function() {
    Toolset.hooks.doAction( 'toolset_text_editor_CodeMirror_init', self.editorSelector );
	};

	self.addHooks = function() {
    Toolset.hooks.addAction( 'cred_editor_refresh_content_editor', self.refreshContentEditor );
    Toolset.hooks.addAction( 'cred_editor_focus_content_editor', self.focusContentEditor );
	};

    self.refreshContentEditor = function(){
        try{
            self.editor.codemirror.refresh();
        } catch( e ){
            console.log( 'There is a problem with CodeMirror instance: ', e.message );
        }
    };

	self.focusContentEditor = function(){
        try{
            self.editor.codemirror.focus();
        } catch( e ){
            console.log( 'There is a problem with CodeMirror instance: ', e.message );
        }
    };

    self.initStaticData = function( modelData ) {
        Toolset.CRED.AssociationFormsEditor.strings = modelData.strings || {};
        Toolset.CRED.AssociationFormsEditor.itemsPerPage = modelData.itemsPerPage || {};
        Toolset.CRED.AssociationFormsEditor.bulkActions = modelData.bulkActions || {};
    };

    self.initTopBar = function() {
        if ( jQuery( 'body' ).hasClass( 'cred-top-bar' ) ) {
            return;
        }

        jQuery( 'body' ).addClass( 'cred-top-bar' );

        jQuery( 'div#topbardiv > h2.hndle' ).remove();
        jQuery( 'div#topbardiv > button.handlediv' ).remove();
        jQuery( 'div#association_form_name' ).remove();

        jQuery( '.wrap > h1' ).prependTo( 'div#titlediv' );

        if ( '' != jQuery( '#form_name' ).val() ) {
            jQuery( '#form_name' ).hide();
            jQuery( '<span id="title-alt">' + jQuery( '#form_name' ).val() + '<i class="fa fa-pencil"></i></span>' ).prependTo( 'div#titlewrap' );
        }

        jQuery( 'div#save-form-actions' ).show();
        jQuery( 'div#status-form-actions' ).show();

        // Hide the delete button when creating a new form
        if ( ! model.get( 'id' ) ) {
            jQuery( '.js-cred-delete-form' ).css( { 'visibility': 'hidden' } );
        }

        var adminBarWidth = jQuery( 'div#wpbody-content > div.wrap' ).width(),
            adminBarHeight = jQuery( 'div#topbardiv' ).height(),
            adminBarTopOffset = 0,
            adjustControls = function() {
                if ( jQuery( window ).scrollTop() > 0 ) {
                    jQuery( '#contextual-help-link' ).hide();
                    jQuery( '#save-form-actions, #status-form-actions, .js-cred-delete-form', 'div#topbardiv' ).fadeOut( 'fast', function() {
                        jQuery( 'body' ).addClass( 'cred-top-bar-scroll' );
                    });
                }
                else {
                    jQuery( 'body' ).removeClass( 'cred-top-bar-scroll' );
                    jQuery( '#contextual-help-link' ).show();
                    jQuery( '#save-form-actions, #status-form-actions, .js-cred-delete-form', 'div#topbardiv' ).fadeIn( 'fast', function() {

                    });
                }
            };

        if (
			jQuery( '#wpadminbar' ).length !== 0
			// Do not add the top offset when on an iframe
			&& window.location == window.parent.location
		) {
            adminBarTopOffset = jQuery('#wpadminbar').height();
        }

        jQuery( 'div#topbardiv' ).css({
            'top':adminBarTopOffset,
            'width':adminBarWidth
        });

        jQuery( 'div#wpbody-content' ).css({
            'padding-top':( adminBarHeight + 9 )
        });

        jQuery( window ).on( 'scroll', adjustControls );

        jQuery( window ).on( 'resize', function() {
            var adminBarWidth = jQuery( 'div#wpbody-content > div.wrap' ).width();
            jQuery( 'div#topbardiv' ).width( adminBarWidth );
        });

        jQuery( document ).on( 'click', '#title-alt', function( e ) {
            e.preventDefault();
            jQuery( this ).hide();
            jQuery( '#form_name' ).show();
        });

        adjustControls();
    };

    self.loadDependencies = function( nextStep ) {
        // Continue after loading the view of the listing table.
        Toolset.CRED.head.load(
            Toolset.CRED.AssociationFormsEditor.jsPath + '/dialogs/DeleteForm.js?ver=' + Toolset.CRED.AssociationFormsEditor.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsEditor.jsPath + '/AssociationFormActions.js?ver=' + Toolset.CRED.AssociationFormsEditor.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsEditor.jsPath + '/models/AssociationFormModel.js?ver=' + Toolset.CRED.AssociationFormsEditor.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsEditor.jsEditorPath + '/AssociationForm_ExtraEditors.js?ver=' + Toolset.CRED.AssociationFormsEditor.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsEditor.jsEditorPath + '/AssociationFormViewModel.js?ver=' + Toolset.CRED.AssociationFormsEditor.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsEditor.jsEditorPath + '/AssociationFormCreateEdit.js?ver=' + Toolset.CRED.AssociationFormsEditor.toolsetFormsVersion,
            nextStep
        );
    };

    _.bindAll( self, 'initIclEditor' );
};

Toolset.CRED.AssociationFormsEditor.main = new Toolset.CRED.AssociationFormsEditor.Class( $ );
Toolset.CRED.head.ready( Toolset.CRED.AssociationFormsEditor.main.init );
