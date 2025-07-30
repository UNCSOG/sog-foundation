// dd-layouts-cred-support.js

var DDLayout = DDLayout || {};

DDLayout.layouts_cred_user_support = function($)
{
    var self = this;

	_.extend( self, new DDLayout.LayoutsToolsetSupport(jQuery));

    self.init = function( ) {
        // If this file is included then CRED will be running in an iframe

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

        window.parent.DDLayout.cred_user_in_iframe.the_frame_ready();

    };

	self.fetch_extra_controls = function( who ){
		return window.parent.DDLayout.cred_user_in_iframe.fetch_extra_controls(who);
	};

    self.save_form = function () {
		_.defer(function(){
			$('form[name="post"]').submit();
		});
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

        var $checked_user_roles = new Array();

        $(".roles_checkboxes:checked").each(function(){
            $checked_user_roles.push($(this).val());
        });
		return {
			'type' : $("input[name='_cred[form][type]']:checked").val(),
			'post_type' : $('#cred_post_type option:selected').text(),
            'user_role' : $checked_user_roles.join(', ')
		};
	};

    self.get_form_name = function(){
        return jQuery('#title').val();
    };

    _.defer(self.init); // Make sure it runs last

}

jQuery(function($) {
    DDLayout.layouts_cred_user = new DDLayout.layouts_cred_user_support($);
});

