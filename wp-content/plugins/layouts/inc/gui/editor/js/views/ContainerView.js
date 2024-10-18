DDLayout.views.ContainerView = DDLayout.views.CellView.extend({
	defaultCssClass:'box',
	rows:null,
	position_info:null,
	events:{
		'click':'_manageCellSelection',
	   // 'dblclick':'_deselectElement',
		'resizestart':'_resizeStart',
		'resize':'_resizeResize',
		'resizestop':'_resizeStop'
	},
	initialize: function (options) {
		var self = this;
        self.hover_target = null;
        _.bindAll( self, 'tooltip_show', 'tooltip_hide' );
		self.options = options;
		//call parent constructor
		DDLayout.views.CellView.prototype.initialize.call(self, options);

        // highlight container when its name changes
        self.model.listenTo(self.model, 'manage-deselect-element-name', function(event){
            DDLayout.ddl_admin_page.key_handler.init();
        });
	},
	render: function (selected) {
		var self = this, rows;

		if( self.options.invisibility )
		{
			self.$el.addClass("container-invisible-state");
		}

		if( selected )
		{
			self.model.selected_cell = true;
		}

		DDLayout.views.CellView.prototype.render.call(self, selected);

		self.$el.addClass("cell container"+self.model.get('width') );
		if ( self.model.selected_cell ) {
			self.$el.addClass('selected');
		}

		rows = self.$el.find('div.js-container-rows');

		self.rows = new DDLayout.views.RowsView( {el:rows, model:self.model.get("Rows"), compound:self.model.get('kind'), container:self, invisibility:self.options.invisibility, current:self.options.current, parentDOM:self.$el } );

		self._initializeRemoveContainerHandler();

		self._toggleRowHiglight();

		self._initializeEditContainerHandler();

		return self;
	},
	_doTemplate:function()
	{
		var self = this;
		self.$el.html( self.template( _.extend( self.model.toJSON(), {layout:self.model.layout, cid:self.model.cid, invisibility:self.options.invisibility} ) ) );
	},
	_manageCellSelection:function(event)
	{
		event.stopPropagation();

		if ( DDLayout.ddl_admin_page.is_in_editable_state ) {
			return true;
		}

		var self = this;

		if ( self.model.selected_cell === false )
		{
			self.eventDispatcher.trigger("deselect_element");
			self.model.selected_cell = true;
			self.eventDispatcher.trigger('cell_selection_changed', self);
			self.$el.addClass('selected');
		}
		else
		{
			self._deselectElement();
		}

		return true;
	},
	_initializeRemoveContainerHandler:function( )
	{
		var self = this;

		jQuery( '.js-container-remove', self.el )
			.on('click', function( event ) {

				event.stopImmediatePropagation();

				if ( self.model.selected_cell === false )
				{
					self.model.selected_cell = true;
					self.eventDispatcher.trigger('cell_selection_changed', self);
					self.$el.addClass('selected');
				}


				DDLayout.ddl_admin_page.delete_selected_cell(null);

			});

        jQuery( '.js-container-edit, .js-container-remove', self.el).toolsetTooltip()

        var pencil_icon = jQuery( '.js-container-edit', self.el), remove_icon = jQuery( '.js-container-remove', self.el );

        pencil_icon.on('tooltip_show', self.tooltip_show);

        pencil_icon.on('tooltip_hide', self.tooltip_hide);

        remove_icon.on('tooltip_show', self.tooltip_show);

        remove_icon.on('tooltip_hide', self.tooltip_hide);

	},
    tooltip_show:function(event){
        var self = this;
        event.stopImmediatePropagation();
        // console.log('show')
        self.hover_target = event;
        event.stopImmediatePropagation();
        jQuery(this)
            .closest('.js-container')
            .addClass('is-hovered row-actions-hovered');

            self.highlight_init(self.hover_target);

    },
    tooltip_hide:function(event){
        var self = this;
        event.stopImmediatePropagation();
        self.hover_target = null;
        self.highlight_destroy();
        jQuery(this)
            .closest('.js-container')
            .removeClass('is-hovered row-actions-hovered');
    },
	get_top_and_bottom_cell_positions : function () {
		var self = this;


		if (self.position_info === null) {

			self._setCellsHeightForPreview();

			var max_y = 0;

			jQuery( 'div.cell:not(.container)', self.$el ).each(function( n, i){
				var top = jQuery(this).offset().top;
				var bottom = top + jQuery(this).height();
				if (bottom > max_y) {
					max_y = bottom;
				}
			});

			self.position_info = {top : self.get_cell_top(),
									bottom : max_y};
		}
		return self.position_info;

	},

	get_cell_top : function () {
		var self = this;

		var top_row = self.rows.getElementView(0);
		return top_row.get_cells_top();
	},

	adjust_position_and_height : function (reference_top, height) {
		var self = this;

		var top_diff = reference_top - self.position_info.top;

		if (top_diff !== 0) {
			self.$el.css({marginTop : top_diff + "px" });
		}

		var container_height = self.position_info.bottom - self.position_info.top;

		if (height != container_height) {
			self._increase_cell_heights(height - container_height);
		}

		self._set_cells_height();
	},

	_set_cells_height : function () {
		var self = this;

		for (var i = 0; i < self.rows.get_row_count(); i++) {
			var row_view = self.rows.get_row(i);
			row_view.set_cells_height();
		}
	},

	_setCellsHeightForPreview : function () {
		var self = this;

		for (var i = 0; i < self.rows.get_row_count(); i++) {
			var row_view = self.rows.get_row(i);
			row_view.setCellsHeightForPreview();
		}
	},

	_increase_cell_heights : function ( height_to_add ) {
		var self = this;

		// Spread the height across all cells in rows.
		height_to_add /= self.get_total_rows();

		jQuery( 'div.cell:not(.container)', self.$el ).each(function( n, i){

			var height = jQuery(this).height(),
				row_cells =  jQuery(this).data('view').get_parent_cells_view();

			row_cells._cells_max_height = height + height_to_add;

			jQuery(this).height(height + height_to_add).data( 'computed_height', height + height_to_add );
		});

	},

	get_total_rows : function () {
		var self = this;

		var count = 0;
		for (var i = 0; i < self.rows.get_row_count(); i++) {
			var row_view = self.rows.get_row(i);
			count += row_view.get_cell_rows();
		}

		return count;

	},
	_initializeEditContainerHandler:function()
	{
		var self = this;
		jQuery( ".js-container-edit", self.el ).on('click', function(event){
			event.stopImmediatePropagation();

			DDLayout.ddl_admin_page.show_container_dialog('edit', self);
		});
		jQuery( '.js-container-css', self.el ).on('click', function(event){
			event.stopImmediatePropagation();
			DDLayout.ddl_admin_page.show_css_dialog(self);
		});
	},
	_toggleRowHiglight:function()
	{
		var self = this;

		jQuery('> div.js-row-toolbar .js-row-actions-container > i', self.$el )
			.hover(function (e) {
				e.stopPropagation();
				if (jQuery(this).hasClass('disabled') === false)
					self.$el.addClass('is-hovered');
			},
			function (e) {
				e.stopPropagation();
				if (jQuery(this).hasClass('disabled') === false)
					self.$el.removeClass('is-hovered');
			});

		jQuery(self.el).find('div.js-row-toolbar').eq(0)
		.on('mouseover',
			function(event){
				// mouse enter handler
				event.stopPropagation();
				self.$el.addClass('is-hovered');
				jQuery(event.target).parent().trigger('mouseleave');
			}).on('mouseout',
			function(event){
				// mouse leave handler
				event.stopPropagation();
				self.$el.removeClass('is-hovered');
				jQuery(event.target).parent().trigger('mouseenter');
			}
		);
	}
});