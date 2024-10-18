DDLayout.listing.models.ListingItem = Backbone.Model.extend({
	url:ajaxurl,
	view_rendered:false,
	initialize: function(){
		// code here
        this.NUM_POSTS = DDLayout_settings.DDL_JS.NUMBER_OF_ITEMS;
	},
    parse:function(data){

        if( typeof data.layout !== 'undefined' ){

            this.set( 'layout', new DDLayout.models.cells.Layout( data.layout ) );
            delete data.layout;

        }

        if( typeof data.ID !== 'undefined' ){
            this.set( 'id', data.ID );
        }
        return data;
    },
	has_parent: function()
	{
		return this.get('parent');
	},
	is_parent:function()
	{
		return this.get('is_parent');
	},
	has_active_children:function()
	{
		var self = this;

		if( self.is_parent() && self.get('children') && self.get('children').length > 0 )
		{
			return true;
		}

		return false;
	},
	is_assigned:function()
	{
		return ( this.get('types') && this.get('types').length ) || ( this.get('posts') && this.get('posts').length ) || ( this.get('loops') && this.get('loops').length );
	},
    get_depth:function()
    {
        return this.get('depth');
    },
    get_name:function()
    {
        return this.get('post_name');
    },
    get_parent:function()
    {
        return this.get('parent');
    },
    get_ancestors:function()
    {
        return this.get('ancestors');
    },
    get:function( attribute )
    {
        if( attribute === 'post_name' ){
            try{
                this.attributes[attribute] = he.decode( this.attributes[attribute] );
            } catch(e){
                //console.log('Not a string');
            }
        }

        return Backbone.Model.prototype.get.call(this, attribute );
    },
    /**
     * performs an ajax call to get json data
     */
    get_data_from_server: function (params, callback, args, scope) {

        var self = this,
            defaults = {
                    action: 'get_all_layouts_posts',
                    nonce: DDLayout_settings.DDL_JS.ddl_listing_show_posts_nonce,
                    status: DDLayout_settings.DDL_JS.ddl_listing_status,
                    layout:self.toJSON()
                },
                defaults = _.extend({}, defaults, params), send = {};

        send.data = JSON.stringify(defaults);
        send.action = defaults.action;
        send.nonce = defaults.nonce;
        self.trigger('item_get_data_from_server');

        self.fetch({
            contentType:'application/x-www-form-urlencoded; charset=UTF-8',
            dataType:'json',
            type: 'POST',
            data: jQuery.param(send),
            success: function ( model, response, object ) {

                if( typeof callback != 'undefined' && typeof callback == 'function') {
                    callback.call( scope || self, model, response, object, args );
                }
            },
            error: function () {
                //console.error(arguments);
            }
        });
    },
    has_layout:function(){
        return this.get('layout') instanceof DDLayout.models.cells.Layout;
    }
});