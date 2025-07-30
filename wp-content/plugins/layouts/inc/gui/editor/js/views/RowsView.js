DDLayout.views.RowsView = DDLayout.views.abstract.CollectionView.extend({
	el: '.js-layout-rows',
	initialize: function (options) {
		//call parent constructor
		var self = this;
		self.options = options;
		DDLayout.views.abstract.CollectionView.prototype.initialize.call(self, options);

		if (self.get_row_count() > 1) {
			self.$el.sortable({
				//handle:'.js-move-row',
				cursor: 'ns-resize',
				axis: 'y',
				placeholder: 'ui-custom-sortable-placeholder',
				forcePlaceholderSize: true,
			//	containment: 'parent',
				tolerance: 'pointer'
			});
		}

		return self;
	},
	render:function( options )
	{
		var self = this;
		DDLayout.views.abstract.CollectionView.prototype.render.call( self, options );

		if (self.get_row_count() == 1) {
			var row_view = self.get_row(0);
			row_view.disable_delete();
			row_view.disable_row_move();
		}
		return self;
	},
	appendModelElement:function( opt )
	{
		var self = this, options = _.extend( {invisibility:self.options.invisibility}, opt  );
		DDLayout.views.abstract.CollectionView.prototype.appendModelElement.call(self, options );
		return self;
	},
	get_parent_view : function () {
		if (this.$el.hasClass('js-container-rows')) {
			return this.$el.parent().parent().data('view');
		} else {
			return this.$el.parent().data('view');
		}
	},

	get_row_count : function () {
		return this.getElementCount();
	},

	get_row : function ( index ) {
		return this.getElementView(index);
	}
});