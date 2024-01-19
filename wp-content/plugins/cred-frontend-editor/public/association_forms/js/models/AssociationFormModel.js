var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.AssociationFormModel = Backbone.Model.extend({
    CREATE_ACTION: 'cred_association_form_add_new',
    EDIT_ACTION: 'cred_association_form_edit',
    DELETE_ACTION: 'cred_association_form_delete',
    DUPLICATE_ACTION: 'cred_association_form_duplicate',
    url:ajaxurl,
    defaults:{
        id : 0,
        form_name : '',
        slug : '',
        relationship : null,
        redirect_to : '',
		disable_comments : false,
		has_media_button: true,
		has_toolset_buttons: true,
		has_media_manager: true,
        form_content : null,
        form_style : '',
        form_script : '',
        messages : null,
        ajax_submission : false,
        wpnonce : null,
        isActive: false,
        action: null,
        form_type : Toolset.CRED.AssociationFormsEditor.form_type,
		post_status : 'draft',
		editor_origin: 'scaffold'
    },
    initialize: function( data ){
        var self = this;
        return self;
    },
    saveForm : function( callback, args, callback_scope ){
        var self = this;

        self.fetch({
            contentType:'application/x-www-form-urlencoded; charset=UTF-8',
            data: jQuery.param( self.toJSON() ),
            type: 'POST',
            success: function ( model, response, object ) {

                if( ! self.get('id') && response.hasOwnProperty('data') && response.data.hasOwnProperty('results') ){
                    if( response.data.results.id ){
                        self.set( 'id', response.data.results.id );
                    }
                    if( response.data.results.slug ){
                        self.set( 'slug', response.data.results.slug );
                    }
                }

                if( typeof callback != 'undefined' && typeof callback == 'function') {
                    callback.call( callback_scope || self, model, response, object, args );
                }

            },
            error: function () {
                console.log( 'There are problems with the AJAX response', arguments );
            }
        });
    },

    updateAllProperties : function( json ){
        var self = this;

        _.each( json, function( value, key, list ) {
                if( typeof value !== 'function' && typeof self.get( key ) !== 'undefined' && self.get( key ) !== value ){
                    self.set( key, value );
                }
        });

        return self;
    },
    /**
     * Overrride set method to make sure booleans are evaluated as such and not as strings
     * @param attributes
     * @param options
     */
    set:function(attributes, options){

        if( attributes.ajax_submission === "false" ){
            attributes.ajax_submission = false;
        } else if( attributes.ajax_submission === "true" ){
            attributes.ajax_submission = true;
        }

        if( attributes.disable_comments === "false" ){
            attributes.disable_comments = false;
        } else if( attributes.disable_comments === "true" ){
            attributes.disable_comments = true;
        }

        if( attributes.has_media_button === "false" ){
            attributes.has_media_button = false;
        } else if( attributes.has_media_button === "true" ){
            attributes.has_media_button = true;
        }

        if( attributes.has_toolset_buttons === "false" ){
            attributes.has_toolset_buttons = false;
        } else if( attributes.has_toolset_buttons === "true" ){
            attributes.has_toolset_buttons = true;
        }

        if( attributes.has_media_manager === "false" ){
            attributes.has_media_manager = false;
        } else if( attributes.has_media_manager === "true" ){
            attributes.has_media_manager = true;
        }

        if( attributes.isActive === "false" ){
            attributes.isActive = false;
        } else if( attributes.isActive === "true" ){
            attributes.isActive = true;
        }

        return Backbone.Model.prototype.set.call(this, attributes, options);
    }

});
