DDLayout.listing.views.abstract.CollectionView = Backbone.View.extend({
	el: null,
	initialize:function(options)
	{
		var self = this;
		self.options = options;

		self.$el.data( 'view', self );

		self.render( options );
	},
	render: function (option) {
		var self = this,
			options = _.extend({}, option),
			append = option.append_to;

		self._cleanBeforeRender( self.$el );

		self.fragment = document.createDocumentFragment();

		self.appendModelElement( options );

		self.$el.append( self.fragment );

		return self;
	}
	,appendModelElement:function( opt )
	{

		var self = this, view, el, options;

		self.model.each(function(model){

			try{

				options = {
					model:model
				}

				view = new DDLayout.listing.views[ 'Listing' + model.get('kind') + 'View' ]( options );

				el = view.render( options ).el;

				self.fragment.appendChild( el );

			}
			catch( e )
			{
				//TODO:do something here to handle API calls / errors
				console.error( e.message );
			}
		}, self)

		return this;
	},
	/*
	 ** remove all the children view to clean event queue
	 */
	_cleanBeforeRender:function( el )
	{
		var self = this;

		el.find('tr', 'tbody').each(function( i, v ){
			if( jQuery(v).data('view') )
			{
				self._cleanBeforeRender( jQuery(v) );
				jQuery(v).data('view').remove();
			}

		});
	}
});