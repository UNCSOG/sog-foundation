DDLayout.listing.views.ListingGroupView = Backbone.View.extend({
	tagName:'tbody',
    appended: [],
	initialize:function(options)
	{
		var self = this;

		_.bindAll( self, 'render', 'afterRender');

		self.render = _.wrap(self.render, function(render, args) {
			render(args);
			_.defer(self.afterRender, _.bind(self.afterRender, self) );
			return self;
		});


		self.fictious_parents = [];
		self.items_view = [];
		self.options = options;
		self.$el.data( 'view', self );
		self.$el.addClass('listing-page-table-list');
        if(self.model.get('id') === 0){
            self.$el.hide();
        }

		self.open = true;

		DDLayout_settings.DDL_JS.listing_open[self.model.get('id')] = false;

		if( DDLayout_settings.DDL_JS.listing_open[self.model.get('id')] )
		{
			self.$el.addClass('hidden');

			self.open = jQuery.jStorage.get( 'open_'+self.model.get('id') ) !== undefined ? jQuery.jStorage.get( 'open_'+self.model.get('id') ) : true;
		}

		self.listenTo(self.model.get('items'),'remove', self.render, self, options );

		self.collapse_group();
	},
	render: function( option )
	{
		var self = this,
			options = option,
			groups_view = null,
			doc = document.createDocumentFragment();

		self.template = _.template( jQuery('#table-listing-group').html() );

		options.how_many = self.model.get('items').length;
		options.status = DDLayout_settings.DDL_JS.ddl_listing_status;

		self.$el.html( self.template(_.extend( options, self.model.toJSON() ) ) );
        self.appended = [];
		//console.log( DDLayout_settings.DDL_JS.ddl_listing_status );

		if( self.model.get('items') && self.model.get('items').models.length > 0 )
		{
			self.model.get('items').each(function(v){

				var fragment = document.createDocumentFragment(),
                    children_here = v.collection.filter(function(model) {return model.get('parent') == v.get('id') });
					options = _.extend({}, {model:v, group:self.model.get('id')} );
					options.status = DDLayout_settings.DDL_JS.ddl_listing_status;

				if( options.status === 'publish' )
				{
				    // prevent duplication if layout already appended because found through its parent
				    if( self.appended.indexOf( v.get('id') ) !== -1 ) return;

					if( v.get('is_parent') && v.get('is_child') && v.has_active_children() === true )
					{
						return;
					}
					else if( (  ( v.get('is_parent') === false && v.get('is_child') === false )  ) )
					{

						groups_view = new DDLayout.listing.views.ListingItemView( options );

						fragment.appendChild( groups_view.el );
					}
					else if ( v.get('is_child') && typeof v.collection.get( v.get('parent') ) == 'undefined' ) {

						groups_view = new DDLayout.listing.views.ListingItemView( options );


						if( self.fictious_parents.indexOf( v.get('parent') ) === -1 )
						{
							var parent = self.nested_parents( v.get('parent') );

							if( null !== parent ) fragment.appendChild( parent );
							fragment.appendChild( groups_view.el );
						}
						else
						{
							var sibling = self.get_last_child_in_stranger_group(v.get_parent() );
							if( sibling ) jQuery( sibling.$el, self.$el).after( groups_view.$el );
						}

					}
					else if( v.get('is_parent') )
					{

						if( !v.get('is_child') )
						{
							groups_view = new DDLayout.listing.views.ListingItemView( options );
							fragment.appendChild( groups_view.el );
						}

						_.each(children_here, function(c){
							var child = new DDLayout.listing.views.ListingItemView( {model:c, group:self.model.get('id')});
                                child.$el.addClass('children-depth_'+ c.get('depth'));
                                fragment.appendChild( child.el )
                                fragment.appendChild( self.nested_children( c, options) );
                                self.appended.push( c.get('id') );
						});
					}
				}
				else if( options.status === 'trash' )
				{
					groups_view = new DDLayout.listing.views.ListingItemView( options );
					fragment.appendChild( groups_view.el );
				}

				if( null !== groups_view ) self.items_view.push( groups_view );

				doc.appendChild( fragment );
			});

			self.$el.append( doc );
		} else {
			self.$el.hide();
		}

		return self;
	},
	afterRender:function()
	{
		var self = this;
		if( DDLayout_settings.DDL_JS.listing_open[self.model.get('id')] ){
			self.setGroupInitialState( self.$el.find( 'i.js-collapse-group') );
		}
	},
	get_last_child_in_stranger_group:function( parent )
	{
		var self = this,
            ret;

		ret = _.filter(self.items_view, function(v){
			return  v.model.get_ancestors().indexOf(parent) !== -1;
		});

		return ret.length -1 >= 0 ? ret[ret.length -1] : null;
	},
	nested_parents: function( id )
	{
		var self = this,
			parent = DDLayout.listing.models.ListingTable.get_instance().get_by_id( id ),
			fragment = document.createDocumentFragment();

		if( !parent ) return null;

		if( !parent.get('parent') ) fragment.appendChild( new DDLayout.listing.views.ListingItemView({model:parent}).el );

		if( parent.get('parent') ){

			var parent_parent = self.nested_parents( parent.get('parent') );
			if( null !== parent_parent )
			fragment.appendChild( self.nested_parents( parent.get('parent') ) );
			fragment.appendChild( new DDLayout.listing.views.ListingItemView({model:parent}).el );
		}

		self.fictious_parents.push(id);

		return fragment;
	},
	nested_children:function( v, options )
	{
		var self = this,
			groups_view = null,
			fragment = document.createDocumentFragment(),
			children_here = v.collection.filter(function(model) {return model.get('parent') == v.get('id') });

		options = _.extend({}, { model:v, group:self.model.get('id')} );

			if( !v.get('is_child') )
			{
				groups_view = new DDLayout.listing.views.ListingItemView( options );
				fragment.appendChild( groups_view.el );
			}

			_.each(children_here, function(c){
				var child = new DDLayout.listing.views.ListingItemView( {model:c, group:self.model.get('id')});
				child.$el.addClass( 'children-depth_'+ c.get('depth') );
				fragment.appendChild( child.el );
				fragment.appendChild( self.nested_children(c) );
			});

		return fragment;
	},
	collapse_group:function()
	{
		var self = this, caret = jQuery( '.js-collapse-group', self.$el );

		self.$el.on('click', '.js-collapse-group', function(event){

			var $me = jQuery(this);

			if( self.open )
			{
				$me.removeClass('fa-caret-up').addClass('fa-caret-down');
				self.$el.find('tr').slice(1).slideUp('fast', function(event){
					self.open = false;
				});
			}
			else
			{
				$me.removeClass('fa-caret-down').addClass('fa-caret-up');
				self.$el.find('tr').slice(1).slideDown('fast', function(event){
					self.open = true;
				});
			}
			jQuery.jStorage.set( 'open_'+self.model.get('id'), self.open );
		});

	},
	setGroupInitialState:function(caret)
	{
		var self = this, $me = caret;

		if( self.open )
		{
			$me.removeClass('fa-caret-up').addClass('fa-caret-down');
			self.$el.find('tr').slice(1).css('display', 'none')
			self.open = false;
		}
		else
		{
			$me.removeClass('fa-caret-down').addClass('fa-caret-up');
			self.open = true;
		}

		DDLayout_settings.DDL_JS.listing_open[self.model.get('id')] = false;
		self.$el.removeClass('hidden')
	}
});
