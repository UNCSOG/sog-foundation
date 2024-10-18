DDLayout.listing.models.ListingGroups = Backbone.Collection.extend({
	model: DDLayout.listing.models.ListingGroup
	,initialize: function(){
		//console.log('LISTING GROUPS')
	},
	parse:function(data)
	{
		return data;
	},
    getActiveAsArray: function( active ){
	    return this.where( { group_slug : active } );
    },
    removeActive: function( active ){
	    var self = this,
            asArray = self.where( { group_slug : active } ),
            activeGroup = asArray.length ? activeGroup[0] : null;

	    if( activeGroup ){
	        self.remove( activeGroup );
        }
    }
});