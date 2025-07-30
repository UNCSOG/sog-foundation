DDLayout.views.ThemeSectionRowView = DDLayout.views.RowView.extend({
	events:{
		'mousedown':'_handleMouseDown'
	},
	initialize:function(options)
	{
		var self = this;
        _.bindAll( self, 'tooltip_show', 'tooltip_hide' );
		self.options = options;

		if( self.options.stopRender === true ) return;

		self.errors_div = jQuery(".js-ddl-message-container");
		//call parent constructor
		DDLayout.views.abstract.ElementView.prototype.initialize.call(self, options);

	},
	render:function(args)
	{

		var self = this,
			itemEditorCssBaseClass = self.model.get('kind').toLowerCase(),
			prefix = '';

		self.template = _.template( jQuery('#'+ prefix + itemEditorCssBaseClass + '-template').html() );

		self.$el.html( self.template( _.extend( self.model.toJSON(), {layout_type:self.model.getLayoutType()} ) ) );

		self.$el.removeClass('row');

		self.$el.addClass('row-container');

		self._toggleRowHiglight();

		self._initializeRemoveRowHandler( );

		self._initializeAddRowHandler( );

		self._initializeEditRowHandler( );

		self._makeElementNameEditable();

		self._displayRowPlaceholderOnHover();

		return self;
	},
	_initializeEditRowHandler:function()
	{
		var self = this;
		jQuery( '.js-row-edit', self.el ).on('click', function(event){
			event.stopImmediatePropagation();
			DDLayout.ddl_admin_page.show_theme_section_row_dialog('edit', self, this);
		});

        jQuery( '.js-row-edit, .js-row-remove', self.el).toolsetTooltip();
		jQuery( '.js-row-edit, .js-row-remove', self.el )
			.on('mouseenter', function(event) {
				event.stopImmediatePropagation();
				jQuery(this)
					.closest('.js-row')
					.addClass('is-hovered row-actions-hovered');


			})
			.on('mouseleave', function(event) {
				event.stopImmediatePropagation();
				jQuery(this)
					.closest('.js-row')
					.removeClass('is-hovered row-actions-hovered');

			});

	}
});