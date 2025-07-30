var DDLayout = DDLayout || {};

jQuery( function () {

   var video_embed = new DDLayout.EmbeddedManager(),
	   menu_fix = new DDLayout.DropDownMenuFix();
});

DDLayout.EmbeddedManager = function()
{
	var self = this;

	self.framework = DDLayout_fe_settings.DDL_JS.css_framework;

	self.init = function()
	{
		if( self.hasOwnProperty( self.framework ) ) self[self.framework]();
	};


	self['bootstrap-2'] = function()
	{
		jQuery("[class*='span'] video,iframe,.wp-video").each( function () {
			var span = jQuery(this).closest("[class*='span']");
            if( span.length > 0 )
            {
                self.ddl_fit_videos_to_cell_size(jQuery(this), span);
            }
		});
	};

	self['bootstrap-3'] = function()
	{
		jQuery("[class*='col-sm-'] video,iframe,.wp-video").each( function () {
			var span = jQuery(this).closest("[class*='col-sm-']");
            if( span.length > 0 )
            {
                self.ddl_fit_videos_to_cell_size(jQuery(this), span);
            }
		});
	};

	self['bootstrap-4'] = function() {
		// Match all column sizes. Make changes only for BS4, to prevent affecting existing sites in any way.
		jQuery( '[class*=\'col-\'] video,iframe,.wp-video' ).each( function() {
			var span = jQuery( this ).closest( '[class*=\'col-\']' );
			if (span.length > 0) {
				self.ddl_fit_videos_to_cell_size( jQuery( this ), span );
			}
		} );
	};

	self.ddl_fit_videos_to_cell_size = function(element, span) {

		var iframe_right = jQuery(element).offset().left + jQuery(element).width(),
		    span_right = span.offset().left + span.width();

		if ( iframe_right !== span_right ) {
			var diff = iframe_right - span_right,
			    new_iframe_width = jQuery(element).width() - diff,
			    scale = new_iframe_width / jQuery(element).width();

			if ( jQuery(element).attr('width') ) {

				jQuery(element).attr('width', '100%');
			} else {

				jQuery(element).width('100%');
			}

			jQuery(element).attr('height', jQuery(element).height() * scale);
		}
	};

	self.init();

};

DDLayout.DropDownMenuFix = function()
{
	var self = this;

	self.css_framework = DDLayout_fe_settings.DDL_JS.css_framework;

	self.init = function()
	{
		if( self.css_framework == 'bootstrap-3' )
		{
			self.fixClickOnSubmenu();
		}
	};

	self.fixClickOnSubmenu = function()
	{
		jQuery('ul.dropdown-menu [data-toggle=dropdown]').on('click', function(event) {
			// Avoid following the href location when clicking
			event.preventDefault();
			// Avoid having the menu to close when clicking
			event.stopPropagation();
			// If a menu is already open we close it
			jQuery('ul.dropdown-menu [data-toggle=dropdown]').parent().removeClass('open');
			// opening the one you clicked on
			jQuery(this).parent().addClass('open');

			var menu = jQuery(this).parent().find("ul");
			var menupos = jQuery(menu).offset();

			if (menupos.left + menu.width() > jQuery(window).width()) {
				var newpos = -jQuery(menu).width();
				menu.css({ left: newpos });
			} else {
				var newpos = jQuery(this).parent().width();
				menu.css({ left: newpos });
			}

		});
	};

	self.init();
};
