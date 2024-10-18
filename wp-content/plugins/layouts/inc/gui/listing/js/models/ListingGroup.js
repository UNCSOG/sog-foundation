DDLayout.listing.models.ListingGroup = Backbone.Model.extend({
	initialize: function(options){
		var self = this;
	//	console.log( self.get('id'), self.get('name'), self.get('items') );
	},
	parse: function (data) {
		var self = this;

		data.items = new DDLayout.listing.models.ListingItems(data.items, {parse:true});

		return data;
	},
	is_types:function()
	{
		return this.get('id') === 3;
	},
	is_single:function()
	{
		return this.get('id') === 2;
	},
	is_unassigned:function()
	{
		return this.get('id') === 1;
	},
	is_loops:function()
	{
		return this.get('id') === 4;
	}
});