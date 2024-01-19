/**
 * Association form duplicate dialog
 */
Toolset.CRED.AssociationForms.dialogs = Toolset.CRED.AssociationForms.dialogs || {};
Toolset.CRED.AssociationForms.dialogs.DuplicateForm = function( data, closeCallback, mainObject) {

    var self = this;
    self.formDefinition = data;

    /**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el);
        };

        var dialogButtons = [];

        dialogButtons.push({
            text: 'Duplicate',
            click: function() {
                cleanup(dialog);
                self.buttonAction = 'duplicate';
                closeCallback( self );
            },
            'class': 'button toolset-danger-button'
        });
        dialogButtons.push({
            text: 'Cancel',
            click: function() {
                cleanup(dialog);
            },
            'class': 'button cred-association-form-cancel-button'
        });

        var dialog = mainObject.createDialog(
            'cred-duplicate-association-form',
            'Duplicate',
            {},
            dialogButtons
        );

        ko.applyBindings(self, dialog.el);
    };



    return self;

};