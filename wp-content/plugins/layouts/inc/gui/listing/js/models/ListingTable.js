DDLayout.listing.models.ListingTable = Backbone.Model.extend({
	url:ajaxurl,
	Groups:null,
	ACTION_DEFAULT: 'get_ddl_listing_data',
	NOT_ASSIGNED:1,
	TO_PAGE:2,
	TO_TYPES:3,
    TO_OTHERS:4,
    PARENTS: 5,
    slugsToIds:{
	  'post_types' : 3,
        'archives' : 4,
        'single'   : 2,
        'parents'  : 5,
        'free' : 1
    },
    activeGroup: 'post_types'
	, initialize:function( json )
	{
		// very rough singleton implementation;
		if( DDLayout.listing.models.ListingTable.instance === this ) return this;

		var self = this,
			data = json;

        _.bindAll( self, 'after_removed_item');

		DDLayout.listing.models.ListingTable.instance = self;

		self.parse( data );

		self.listenTo(self, 'make_ajax_call', self.get_data_from_server, self);

        self.listenTo( self, 'items-collection-remove-item', self.remove_item);
	},
    remove_item: function(model, options){
        _.defer( this.after_removed_item, model, options );
    },
    after_removed_item:function(model, options){
        this.trigger('items-collection-after-remove-item', model, options );
    },
	/**
	 * performs an ajax call to get json data
     * @param params Object
     * @param callback Function
     * @param args Object
     * @param scope Object
	 */
	get_data_from_server: function (params, callback, args, scope) {

		var self = this,
			defaults =  {
				action: self.ACTION_DEFAULT,
				ddl_listing_nonce: DDLayout_settings.DDL_JS.ddl_listing_nonce,
				status: DDLayout_settings.DDL_JS.ddl_listing_status,
                group_slug: self.activeGroup
            }, send, show_posts = self.setUpShowPostsObject();

        send = _.extend( {}, defaults, params );

        self.trigger( 'get_data_from_server', send );

        send.current_page_status = DDLayout_settings.DDL_JS.ddl_listing_status;

        if( !_.isEmpty(show_posts) ){
            send.show_posts = show_posts;
        }

        // if we are not simply switching tabs, reset groups
        if( send.action !== self.ACTION_DEFAULT ){
        	self.set( 'Groups', null );
		}

		self.fetch({
            contentType:'application/x-www-form-urlencoded; charset=UTF-8',
			data: jQuery.param(send),
			type: 'POST',
			success: function ( model, response, object ) {

				if( typeof callback != 'undefined' && typeof callback == 'function') {
					callback.call( scope || self, model, response, object, args );
				}
			},
			error: function () {
				console.error(arguments);
			}
		});
	},
	parse:function( data, attrs )
	{
		if( ! this.get('Groups') && ! data.Data ) return null;
		if( this.get('Groups') && ! data.Data ) return this.get('Groups');

		var self = this;
		// do not overwrite Groups data but keep existing
        if( self.get('Groups') ){
        	_.each( data.Data, function( group ) {
                self.get('Groups').add( new DDLayout.listing.models.ListingGroup( group, {
                    parse: true
                } ) );
			});

        } else {
            self.set('Groups', new DDLayout.listing.models.ListingGroups(data.Data, {
                parse: true
            }) );
        }


		this.set('id', 0 );
		this.set('name', 'master');
		this.unset('Data');

		return data.Data;
	},
	get_by_id:function( value )
	{
		var self = this, ret = null, groups;

		groups = self.get('Groups');
		groups.each(function(v, k, l){
			if( v.get('items').get(value) )
			{
				ret = v.get('items').get(value);
			}
		});

		return ret;
	},
	remove_by_id:function( array_of_ids, data )
	{
		var self = this;
		_.each(array_of_ids, function (v) {
			var remove = self.get_by_id(v);
			if (remove && remove.hasOwnProperty('collection')) {
				remove.collection.remove(remove, {silent: true});
				self.trigger('removed_batched_items', data );
			}
		});
	},
	search:function(s)
	{
		if( s === '' || !s ) return;

		var self = this,
            search = s,
            push = [],
            parents = [],
            currentId = self.slugsToIds[self.activeGroup],
            currentParents = currentId * 10,
            searchMe = self.get('Groups').where( {id:currentId} ),
            searchMeParents = self.get('Groups').where( {id:currentParents} );

		var groupsSearch = [ searchMe[0], searchMeParents[0] ];

        if( ! self.searchCache ) self.searchCache = [ _.extend( {}, searchMe[0].toJSON() ), _.extend( {}, searchMeParents[0].toJSON() ) ];

		 _.each(groupsSearch, function(g,k,l){
			  var to_json = g.get('items')
				  , items
				  , term = search.toLocaleLowerCase(/*better explicitly pass locale as argument*/)
				  , cache = self.searchCache;

			 if( cache && cache[k] && cache[k].hasOwnProperty('items') ) to_json.reset(cache[k].items, {silent:true})
			// to be refines
			 items = to_json.filter(function(model) {
				 if( model && model.get('post_name') && model.get('post_title') )
				 return model.get('post_name').indexOf(term) === 0 ||
				        model.get('post_title').toLocaleLowerCase().indexOf(term) === 0 ||
				        model.get('post_title').indexOf(term) === 0 ||
				        model.get('post_title').toLocaleLowerCase().indexOf(term) !== -1
			 });

			 _.each(items, function(v){
				 if( v && v.get('parent') ) self.get_parents( v.get('parent'), parents );
			 });

			 push[k] = items;
		 });

        searchMe[0].get('items').reset( push[0], {silent:true} );
        searchMeParents[0].get('items').reset(parents, {silent:true});

        self.get('Groups').reset( [ searchMe[0], searchMeParents[0] ], {silent:true} );

		self.trigger('done_searching');
	},
	set_family: function (element) {
		var self = this,
			el = element,
            ret = {
                depth:0,
                ancestors:[]
            };

		if (!el.get('is_child')) {
            return ret;
		}

		var rec_depth = function (v) {
			var by_id = self.get_by_id( v.get('parent') );
			if( !v || !by_id ) {
                return ret;
            }
			if (v.get('is_child') && !by_id.get('is_parent')) {
                return ret;
			}
			else if (v.get('is_child') && by_id.get('is_parent')) {
                ret.depth++;
                ret.ancestors.push( by_id.get('id') );
				return rec_depth( by_id );
			}

			return ret;
		}

		return rec_depth(el);
	},
	set_depths_and_group:function()
	{
		var self = this;

		self.get('Groups').each(function(v, k, l){
			var group = v.get('id');
			v.get('items').group = group;
			v.get('items').map(function(i){
				i.set('group', group);
				i.set( 'depth', self.set_family( i).depth );
                i.set( 'ancestors', self.set_family( i).ancestors );
			});
		});
	},
	get_parents:function( id, parents )
	{
		var self = this, parent = self.get_by_id( id );

		if( null === parent ) return parents;

		if( !parent.get('parent') ) parents.push( parent );

		if( parent.get('parent') )
		{
			var next = self.get_by_id( parent.get('parent') );
			parents.push( parent );
			if( null != next )
			{
				parents.push( next );
				self.get_parents( next.get('parent'), parents );
			}
		}
		return parents;
	},
    getGroupItems:function(group_id){
        return this.get('Groups').get(group_id) ? this.get('Groups').get(group_id).get('items') : [];
    },
    setUpShowPostsObject:function(){
        var self = this, group_posts = self.getGroupItems(self.TO_PAGE), show = {};

        _.each(group_posts.models,function(v){
            show[v.get('ID')] = v.get('show_posts');
        });

        return show;
    },
    getPostTypesAssigned:function(){
        var self = this, group_types = self.getGroupItems(self.TO_TYPES);
        return _.flatten( group_types.pluck('types') );
    }
});

// make a singleton of the main model
DDLayout.listing.models.ListingTable.get_instance = function( json )
{

	if( !json && !DDLayout.listing.models.ListingTable.instance)
	{
		throw new ReferenceError("You should supply a valid json data object to ListingTable object singleton accessor.");
	}

	if( typeof DDLayout.listing.models.ListingTable.instance === 'undefined' || !DDLayout.listing.models.ListingTable.instance )
	{
		DDLayout.listing.models.ListingTable.instance = new DDLayout.listing.models.ListingTable( json );
	}
	return DDLayout.listing.models.ListingTable.instance;
};