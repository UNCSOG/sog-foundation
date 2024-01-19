var Toolset = Toolset || {};

if ( typeof Toolset.CRED === "undefined" ) {
    Toolset.CRED = {};
}

// the head.js object
Toolset.CRED.head = Toolset.CRED.head || head;
Toolset.CRED.AssociationForms = {};
Toolset.CRED.AssociationFormsListing = {};

Toolset.CRED.AssociationFormsListing.viewmodels = {};

Toolset.CRED.AssociationFormsListing.Class = function( $ ) {

    var self = this;

    // Extend the generic listing page controller.
    Toolset.Gui.ListingPage.call( self );

    self.getMainViewModel = function() {
        return new Toolset.CRED.AssociationFormsListing.viewmodels.ListingViewModel( self.getModelData().items, { 'has_relationships' : self.getModelData().has_relationships } );
    };

    self.actions = null;

    self.beforeInit = function() {

        var modelData = self.getModelData();
        //noinspection JSUnresolvedVariable
        Toolset.CRED.AssociationFormsListing.toolsetFormsVersion = modelData.toolsetFormsVersion;
        Toolset.CRED.AssociationFormsListing.jsPath = modelData.jsIncludePath;
        Toolset.CRED.AssociationFormsListing.jsListingPath = modelData.jsListingIncludePath;
        self.initStaticData( modelData );

    };


    self.afterInit = function() {
        self.actions =  new Toolset.CRED.AssociationFormActions();
    };

    self.deleteForm = function( associationForm ) {

        var data = {
            'to_delete' : associationForm,
            'delete_type' : 'single'
        };

        var dialog = Toolset.CRED.AssociationForms.dialogs.DeleteForm( data, function( result ) {
            self.actions.delete_single_form( function( updated_model, response ){
                if(response.success === true){
                    self.viewModel.removeFormItem( associationForm );
                    associationForm.displayedMessage({text: response.data.results.message, type: 'info'});
                    associationForm.messageVisibilityMode('show')
                } else {
                    associationForm.displayedMessage({text: response.data.message, type: 'error'});
                    associationForm.messageVisibilityMode('show');
                }
            }, result  );
        }, self);

        dialog.display();
    };

    self.bulkDeleteForm = function ( associationForms ) {

        var data = {
            'to_delete' : associationForms,
            'delete_type' : 'bulk'
        };

        var dialog = Toolset.CRED.AssociationForms.dialogs.DeleteForm( data, function(result) {
            self.actions.bulk_delete_forms( function( updated_model, response ){
                if(response.success === true){

                    _.each( associationForms, function( associationForm ) {
                        self.viewModel.removeFormItem( associationForm );
                        associationForm.displayedMessage({text: response.data.results.message, type: 'info'});
                        associationForm.messageVisibilityMode('show')
                    });

                } else {
                    console.log('Unable to delete');
                }
            }, result  );
        }, self);

        dialog.display();
    };

    self.duplicateForm = function( associationForm ) {

        self.actions.duplicate_form( function( updated_model, response ){
            if( response.success === true ){
                self.viewModel.duplicateFormItem( associationForm, response.data.results );
            } else {
                associationForm.displayedMessage({text: response.data.message, type: 'error'});
                associationForm.messageVisibilityMode('show');
            }
        }, associationForm );

    };

    self.initStaticData = function( modelData ) {
        Toolset.CRED.AssociationFormsListing.strings = modelData.strings || {};
        Toolset.CRED.AssociationFormsListing.itemsPerPage = modelData.itemsPerPage || {};
        Toolset.CRED.AssociationFormsListing.bulkActions = modelData.bulkActions || {};
    };

    self.loadDependencies = function( nextStep ) {
        // Continue after loading the view of the listing table.
        Toolset.CRED.head.load(
            Toolset.CRED.AssociationFormsListing.jsPath + '/dialogs/DeleteForm.js?ver=' + Toolset.CRED.AssociationFormsListing.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsListing.jsPath + '/dialogs/DuplicateForm.js?ver=' + Toolset.CRED.AssociationFormsListing.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsListing.jsPath + '/AssociationFormActions.js?ver=' + Toolset.CRED.AssociationFormsListing.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsListing.jsListingPath + '/ListingViewModel.js?ver=' + Toolset.CRED.AssociationFormsListing.toolsetFormsVersion,
            Toolset.CRED.AssociationFormsListing.jsListingPath + '/AssociationFormViewModel.js?ver=' + Toolset.CRED.AssociationFormsListing.toolsetFormsVersion,
            nextStep
        );
    };

};

Toolset.CRED.AssociationFormsListing.main = new Toolset.CRED.AssociationFormsListing.Class( $ );
Toolset.CRED.head.ready( Toolset.CRED.AssociationFormsListing.main.init );
