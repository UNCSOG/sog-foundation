/**
 * Association form delete dialog
 * This will handle two different dialogs, one for bulk delete and one for single
 */
Toolset.CRED.AssociationForms.dialogs = Toolset.CRED.AssociationForms.dialogs || {};
Toolset.CRED.AssociationForms.dialogs.DeleteForm = function( data, closeCallback, mainObject) {

    var self = this;
    self.formDefinition = data.to_delete;
    self.deleteType = data.delete_type;

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
            text: 'Delete',
            click: function() {
                cleanup(dialog);
                self.buttonAction = 'delete';
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

        var dialog_template = 'cred-delete-association-form';
        if(self.deleteType === 'bulk'){
            dialog_template = 'cred-bulk-delete-association-form';
        }

        var dialog = mainObject.createDialog(
            dialog_template,
            'Delete',
            {},
            dialogButtons
        );

        ko.applyBindings(self, dialog.el);
    };



    return self;

};