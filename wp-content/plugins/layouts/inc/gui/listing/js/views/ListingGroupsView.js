DDLayout.listing.views.ListingGroupsView = DDLayout.listing.views.abstract.CollectionView.extend({
	tagName:'tbody',
	el:'.js-listing-table',
    activeGroup: 'post_types',
	initialize:function( options )
	{
		var self = this;
		self.options = options;
		self.$el.data( 'view', self );
        self.activeGroup = self.options.activeGroup;

		DDLayout.listing.views.abstract.CollectionView.prototype.initialize.call(self, options);


	},
	render: function (option) {
		var self = this,
			options = _.extend({}, option ),
		    activeGroup = self.activeGroup;

		self._cleanBeforeRender( self.$el );

		self.fragment = document.createDocumentFragment();

		self.appendModelElement( options );

        if( DDLayout_settings.DDL_JS.ddl_listing_status === 'trash' ){
            self.$el.find( 'thead' ).after( self.fragment );
		} else{
            self.$el.find( 'thead#'+activeGroup ).after( self.fragment );
        }

		return self;
	}
});
