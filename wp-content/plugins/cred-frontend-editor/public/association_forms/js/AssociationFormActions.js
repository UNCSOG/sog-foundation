/**
 * Association forms ajax actions handlers
 */
var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.AssociationFormActions = Backbone.Model.extend({
    url: ajaxurl,


    /**
     * Main delete function, it is calling ajax callback that actually removes form
     * @param callback
     * @param args
     * @param formObject
     */
    delete_form : function ( callback, args, formObject  ) {
        var self = this;

        var listingPageNonce = jQuery('#cred_associations_form_nonce').val();
        formObject.wpnonce = listingPageNonce;

        if( formObject.delete_type === 'single' ){
            args.formDefinition.beginAction();
        }

        self.fetch({
            contentType:'application/x-www-form-urlencoded; charset=UTF-8',
            data: jQuery.param( formObject ),
            type: 'POST',
            success: function ( model, response, object ) {
                if( typeof callback != 'undefined' && typeof callback == 'function') {
                    callback.call( self, model, response, object, args );
                }

                if( formObject.delete_type === 'single' ){
                    args.formDefinition.finishAction();
                }
            },
            error: function () {
                console.error( arguments );
            }
        });

    },

    /**
     * Prepare data for deleting single form
     * @param callback
     * @param args
     */
    delete_single_form : function ( callback, args ) {

        var formObject = {};
        formObject.id = args.formDefinition.id();
        formObject.action = 'cred_association_form_delete';
        formObject.delete_type = 'single';

        this.delete_form( callback, args, formObject );

    },

    /**
     * Prepare data to delete more than one for at once
     * @param callback
     * @param args
     */
    bulk_delete_forms : function ( callback, args ) {

        var formObject = {};

        var idsToDelete = [];
        _.each(args.formDefinition, function( forms ) {
            idsToDelete.push( forms.id() )
        });

        formObject.ids = idsToDelete;
        formObject.action = 'cred_association_form_delete';
        formObject.delete_type = 'bulk';

        this.delete_form( callback, args, formObject );

    },

    /**
     * Duplicate forms
     * @param callback
     * @param args
     */
    duplicate_form : function ( callback, formDefinition ) {
        var self = this;

        var listingPageNonce = jQuery('#cred_associations_form_nonce').val();

        var formObject = {};
        formObject.id = formDefinition.id();
        formObject.action = 'cred_association_form_duplicate';
        formObject.wpnonce = listingPageNonce;

        formDefinition.beginAction();
        self.fetch({
            contentType:'application/x-www-form-urlencoded; charset=UTF-8',
            data: jQuery.param( formObject ),
            type: 'POST',
            success: function ( model, response, object ) {
                if( typeof callback != 'undefined' && typeof callback == 'function') {
                    callback.call( self, model, response, object, formDefinition );
                }
                formDefinition.finishAction();
            },
            error: function () {
                console.error( arguments );
            }
        });
    }


});

