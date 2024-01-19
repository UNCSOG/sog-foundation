

Toolset.CRED.AssociationFormsListing.viewmodels.ListingViewModel = function( itemModels, defaults, itemSearchFunction ) {


    var self = this;

    self.has_relationships = defaults.has_relationships;

    // Apply the generic listing viewmodel.
    var parentListingViewModel = Toolset.Gui.ListingViewModel.call(
        self,
        itemModels,
        {
            sortBy: 'displayName',
            itemsPerPage: 10
        },
        function ( associationForm, searchString ) {
            return _.some( [ associationForm.slug(), associationForm.displayName() ], function ( value ) {
                return ( typeof(value) !== 'undefined' && value.toLowerCase().indexOf( searchString.toLowerCase() ) > -1 );
            });
        }
    );



    self.createItemViewModels = function( itemModels ) {
        self.items( _.map( itemModels.data, function( itemModel ) {
            return new Toolset.CRED.AssociationFormsListing.viewmodels.AssociationFormViewModel( itemModel, self.itemActions );
        }));
    };

    self.bulkActions = ko.observableArray([
        {
            value: '-1',
            displayName: Toolset.CRED.AssociationFormsListing.bulkActions.bulk_actions
        },
        {
            value: 'delete',
            displayName: Toolset.CRED.AssociationFormsListing.bulkActions.delete,
            handler: function( fieldGroups ) {
                Toolset.CRED.AssociationFormsListing.main.bulkDeleteForm( fieldGroups );
            }
        },
    ]);

    /**
     * Add actions here
     */
    self.itemActions = {

    };


    self.onAddNew = function( event ) {
        if( ! self.has_relationships ){
            return false;
        }

        return true;
    };

    self.removeFormItem = function(formDefinition) {
        self.items.remove( formDefinition );
    };

    self.duplicateFormItem = function (formDefinition, data) {

        var clonedFormModel = {
            'post_title' : data.post_object.post_title,
            'relationship_slug' : formDefinition.relationshipName(),
            'relationship_label' : formDefinition.relationshipLabel(),
            'post_modified' : formDefinition.lastModified(),
            'post_modified_visible' : formDefinition.lastModifiedVisible(),
            'type' : '',
            'ID' : data.post_object.ID,
            'slug' : data.post_object.post_name
        };

        var clonedFormDefinition = new Toolset.CRED.AssociationFormsListing.viewmodels.AssociationFormViewModel( clonedFormModel, self.itemActions );
        self.items.push( clonedFormDefinition );
    };


	self.init();

};
