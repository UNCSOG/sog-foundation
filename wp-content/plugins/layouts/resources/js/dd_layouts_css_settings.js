var DDL_Layouts_Global = DDL_Layouts_Global || {};

jQuery(function(){
	var settings = new DDL_Layouts_Global.CssSettings();
});


DDL_Layouts_Global.CssSettings = function()
{
	var self = this,
		$batch_button = jQuery('.js-ddl-do-batch-css'),
		$box = jQuery('.js-css-batch-theme-help-box'),
		$errors = jQuery('.js-batch-css-error-holder');



	self.init = function()
	{
		self.manage_css_batch();

		if( DDLayout_settings_global.are_css_options_set == 'not-set'  ) self.check_system_credentials();
	}

	self.check_system_credentials = function()
	{
		var params = {
			action: 'check_system_credentials',
			check_system_credentials_nonce: DDLayout_settings_global.check_system_credentials_nonce,
			ddl_layout_css_settings_nonce: jQuery('input[name="ddl_layout_css_settings_nonce"]').val()
		};
		WPV_Toolset.Utils.do_ajax_post(params, {success:function( response ){

		}});
	};

	self.manage_css_batch = function () {
		jQuery(document).on('click', '.js-ddl-do-batch-css', function (event) {
			var data = jQuery(this).data('object');

			if (data.css_batch == 'ignore') $box.hide();

			WPV_Toolset.Utils.do_ajax_post(data, {success: function (response) {
				if( response.message.hasOwnProperty('db_error') )
				{
					$errors.wpvToolsetMessage({
						text: response.message.db_error,
						type: 'message',
						stay: true,
						close: true
					});
				}
				else if( response.message.hasOwnProperty('db_success') )
				{
					$errors.wpvToolsetMessage({
						text: response.message.db_success,
						type: 'info',
						stay: true,
						close: true
					});
				}
			},
				error: function (error) {
					$errors.wpvToolsetMessage({
						text: error.error,
						type: 'error',
						stay: true,
						close: true
					});
				}
			});
		});
	};

	self.init();
};
