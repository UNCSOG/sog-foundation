var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.AssociationFormCreateEdit = function ( action, args ) {
    var self = this, model;

    args.action = action;

    self.params = _.defaults(args, {
        id: 0,
        form_name: '',
        slug: '',
        relationship: null,
        redirect_to: '',
		disable_comments: false,
		has_media_button: true,
		has_toolset_buttons: true,
		has_media_manager: true,
        form_content: null,
        messages: null,
        action: null,
        wpnonce: null,
        form_type : Toolset.CRED.AssociationFormsEditor.form_type,
        post_status : 'draft',
		redirect_custom_post: '',
		editor_origin: 'scaffold'
    });

    self.init = function () {
        if ( null === self.params.action || null === self.params.wpnonce ) {
            throw new ReferenceError('Action and nonce parameters are mandatory for AssociationFormModel object');
        }
        model = new Toolset.CRED.AssociationFormModel( self.params );
    };

    self.getModel = function(){
        return model;
    };

    self.setModelProperty = function( property, value ){
        model.set( property, value );
        return model;
    };

    self.modelGetProperty = function( property ){
        return model.get( property );
    };



    self.init();
};
