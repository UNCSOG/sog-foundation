Toolset.CRED.AssociationFormsListing.viewmodels.AssociationFormViewModel = function( model, fieldActions ) {


    var self = this;
    // Apply the ItemViewModel constructor on this object.
    Toolset.Gui.ItemViewModel.call( self, model, fieldActions );

    // Data properties
    self.displayName = ko.observable( model.post_title );
    self.relationshipName = ko.observable( model.relationship_slug );
    self.relationshipLabel = ko.observable( model.relationship_label );
    self.lastModified = ko.observable( model.post_modified );
    self.lastModifiedVisible = ko.observable( model.post_modified_visible );
    self.form_type = ko.observable( model.type );
    self.id = ko.observable( model.ID );
    self.slug = ko.observable( model.post_name );

    // messages
    self.displayedMessage = ko.observable( {text: '', type: 'info'} );
    self.messageVisibilityMode = ko.observable('remove');
    self.messageNagClass = ko.pureComputed(function () {
        switch (self.displayedMessage().type) {
            case 'error':
                return 'notice-error';
            case 'warning':
                return 'notice-warning';
            case 'info':
            default:
                return 'notice-success';
        }
    });

    self.removeDisplayedMessage = function () {
        self.messageVisibilityMode('remove');
    };

    self.setID = function (id) {
        self.id( id );
    };

    self.setSlug = function ( slug ) {
        self.slug( slug );
    };

    self.onRedirectEditAction = function() {
        self.beginAction();
        window.location.href = "admin.php?page=cred_relationship_form&action=edit&id="+self.id();
    };

    self.onDeleteAction = function() {
        Toolset.CRED.AssociationFormsListing.main.deleteForm(self);
    };

    self.onDisplayNameClick = function() {
        self.onRedirectEditAction();
    };

    self.onDuplicateAction = function() {
        Toolset.CRED.AssociationFormsListing.main.duplicateForm( self );
    };

};
