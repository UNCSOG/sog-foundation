var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.AssociationForms = Toolset.CRED.AssociationForms || {};

Toolset.CRED.AssociationForms.Delete = function( $ ) {
	
    var self = this;
	
	self.i18n = toolset_cred_association_forms_front_end_script_delete_i18n;
	
	self.selector = '.js-cred-delete-relationship';
	
	$( document ).on( 'click', self.selector, function( e ) {
		e.preventDefault();
		
		var $button = $( this ),
			ajaxData = {
				action:       'cred_delete_association',
				wpnonce:      self.i18n.data.nonce,
				relationship: $button.data( 'relationship' ),
				related_item_one: $button.data( 'relateditemone' ),
				related_item_two: $button.data( 'relateditemtwo' ),
				redirect: $button.data( 'redirect' )
			};
		
		$.ajax({
			url:      self.i18n.data.ajaxurl,
			data:     ajaxData,
			dataType: 'json',
			type:     "POST",
			success:  function( originalResponse ) {
				var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
				if ( response.success ) {
					self.doRedirect( response.data.redirect );
				} else {
					
				}
			},
			error: function ( ajaxContext ) {
				
			}
		});
	});
	
	self.doRedirect = function( redirect ) {
		switch( redirect ) {
			case 'self':
				window.location.reload( true ); 
				break;
			case 'none':
			case '':
				break;
			default:
				window.location = redirect;
				break;
		}
	}

    self.init = function(){
        
    };

	
	self.init();
	
};

jQuery( function(){
    Toolset.CRED.AssociationForms.deleteInstance = new Toolset.CRED.AssociationForms.Delete( jQuery );
});