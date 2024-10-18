// dd-layouts-cred-support.js

var DDLayout = DDLayout || {};

DDLayout.layouts_cred_relationship_support = function($)
{
    var self = this;

	_.extend( self, new DDLayout.LayoutsToolsetSupport(jQuery));

    self.init = function( ) {
        // If this file is included then CRED will be running in an iframe
		// exit wizard
        $('#cred-association-form-exit-wizard').trigger('click');
        // Hide the admin menu.
        $('#adminmenuback').hide();
        $('#adminmenuwrap').hide();
        $('#wpadminbar').hide();
        $('#wpcontent').css({'margin-left' : '10px'});
        // hide the footer
        $('#wpfooter').hide();

		$('.add-new-h2').hide();
		$('#cred-submit').hide();

		$('#cred_add_forms_to_site_help').hide();

		var header = DDLayout_cred_settings.DDL_JS.cred_help_header;
		var message = $('#credformcontentdiv .cred-explain-text').html();

		if (DDLayout_cred_settings.DDL_JS.new_form) {
			// Start with the meta sections closed.
			$('#credposttypediv').addClass('closed');
			$('#credformtypediv').addClass('closed');
			$('#crednotificationdiv').addClass('closed');
			$('#credmessagesdiv').addClass('closed');

			message = DDLayout_cred_settings.DDL_JS.new_form_help;
		}

		$('#credformcontentdiv .cred-explain-text').first().replaceWith(

			'<div class="toolset-help js-info-box"> \
				<div class="toolset-help-content"> \
					<h2>' + header + '</h2> \
					<p>' + message + '</p>	\
				</div> \
				<div class="toolset-help-sidebar"> \
					<div class="toolset-help-sidebar-ico"></div> \
				</div>	\
			</div>'
			);

		self.operate_extra_controls( 'cred-layouts-div', '#postbox-container-2');

		$('#cred-layouts-div .desc').show();

        window.parent.DDLayout.cred_relationship_in_iframe.the_frame_ready();

        _.delay(function(){
            self.enable_disable_save_button();
            window.scrollTo(0, -1000);
		}, 500);



    };

	self.fetch_extra_controls = function( who ){
		return window.parent.DDLayout.cred_relationship_in_iframe.fetch_extra_controls(who);
	};

    self.save_form = function () {
        $('#publishing-action button').click();
    };

	self.get_css_settings = function () {
		return {
			'tag' : $('#cred-layouts-div .js-ddl-tag-name').val(),
			'id' : $('#cred-layouts-div .js-edit-css-id').val(),
			'css' : $('#cred-layouts-div select[name="ddl-default-edit-class-name"]').val(),
			'name' : $('#cred-layouts-div #ddl-default-edit-cell-name').val()
		};
	};

	self.get_form_settings = function () {

		var relFormModel = Toolset.CRED.AssociationFormsEditor.main.getBackboneModel();

		return {
			'id' : relFormModel.attributes.id,
			'post_type' : relFormModel.attributes.form_type,
			'model' : relFormModel.attributes
		};
	};

	self.get_form_name = function(){
		return jQuery('#form_name').val();
	};

	self.enable_disable_save_button = function(){
		var $select_relationship = jQuery( 'select[name="relationship"]'),
			$select_redirect = jQuery('select[name="redirect_to"]'),
			$button = jQuery(window.parent.document).find('button.js-close-toolset-iframe');


        if( $select_relationship.hasClass('error') || $select_redirect.hasClass('error') ){
            $button.prop( 'disabled', true );
        } else {
            $button.prop( 'disabled', false );
        }

        $select_relationship.each(function(){
        	jQuery(this).on( 'change', function(event){
                if( jQuery(this).hasClass('error') || $select_redirect.hasClass('error') ){
                    $button.prop( 'disabled', true );
                } else {
                    $button.prop( 'disabled', false );
                }
            });
        });

        $select_redirect.each(function(){
        	jQuery(this).on( 'change', function(event){
                if( jQuery(this).hasClass('error') || $select_relationship.hasClass('error') ){
                    $button.prop( 'disabled', true );
                } else {
                    $button.prop( 'disabled', false );
                }
            });
        });
	};

    _.defer(self.init); // Make sure it runs last

};

jQuery(function($) {
    DDLayout.layouts_cred = new DDLayout.layouts_cred_relationship_support($);
});

