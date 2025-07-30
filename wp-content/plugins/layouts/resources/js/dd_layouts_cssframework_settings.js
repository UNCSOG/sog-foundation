var DDL_Layouts_Global = DDL_Layouts_Global || {};

(function($){
	jQuery(function(){
		var settings = new DDL_Layouts_Global.CssFrameworkSettings($);
	});
}(jQuery));



DDL_Layouts_Global.CssFrameworkSettings = function($)
{
	var self = this
		, $button_save = $('.js-save-layouts-css-framework-settings')
		, $radios = $('input[name="layouts-framework"]')
		, $form = $('.js-layouts-css-framework-settings-form')
		, $messages = $('.js-css-ajax-messages', $form);

	self._current_val = '';

	self.init = function()
	{
		self.initValue();
		self.handleChecked();
		self.handleSave();
	};

	self.initValue = function()
	{
		$radios.each(function(i,v){
			if( $(v).is(':checked') ) {
				self._current_val = $(v).val();
			}
		});
	}

	self.handleSave = function()
	{
		var params = $form.data('object');

		$button_save.on('click', function(event){
			event.preventDefault();
			params.css_framework = self._current_val;
			WPV_Toolset.Utils.do_ajax_post(params, {success:function( response ){
				if( response.message ){
					
						jQuery('#ddl-framework-warning').fadeOut(1000);
					
						$messages.wpvToolsetMessage({
							text: response.message.text,
							stay: false,
							close: false,
							type: 'info'
						});
					}
				}
			});
		});
	};

	self.handleChecked = function()
	{
		$radios.on('change', function(event){
			var $checked = $(this);
			$radios.each(function( i, v ){
				if( v !== $checked[0] )
				{
					$( v).attr('checked', false);
				}
			});
			$checked.attr('checked', true );
			self._current_val = $checked.val();
		});
	};

	self.init();
};