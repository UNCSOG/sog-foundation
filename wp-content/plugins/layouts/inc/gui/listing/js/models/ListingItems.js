DDLayout.listing.models.ListingItems = Backbone.Collection.extend({
	model: DDLayout.listing.models.ListingItem,
	reverseSortDirection: false
	, initialize: function(){
			var self = this;
			self.sortKey = jQuery.jStorage.get( 'sortKey' ) || 'post_title';
			self.reverseSortDirection = jQuery.jStorage.get( 'reverseSortDirection' ) || false;
    },
    comparator: function(a, b) {
      var sampleDataA = a.get(this.sortKey),
          sampleDataB = b.get(this.sortKey);

          if( this.sortKey === 'post_title')
          {
             sampleDataA = sampleDataA.toLowerCase();
             sampleDataB = sampleDataB.toLowerCase();
          }

          if (this.reverseSortDirection) {
            if (sampleDataA > sampleDataB) { return -1; }
            if (sampleDataB > sampleDataA) { return 1; }
            return 0;
          } else {
            if (sampleDataA < sampleDataB) { return -1; }
            if (sampleDataB < sampleDataA) { return 1; }
            return 0;
          }
    },
    parse:function( data )
    {
        if( _.isObject( data ) )
        {
            data = _.toArray(data);
        }
        return data;
    },
    remove:function(models, options){
            DDLayout.listing_manager.listing_table_view.model.trigger('items-collection-remove-item', models, options );
            return Backbone.Collection.prototype.remove.call(this, models, options );
    }
});