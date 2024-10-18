DDLayout.models.collections.Cells = Backbone.Collection.extend({
	model:DDLayout.models.abstract.Element
	, kind:'Cells'
    , max:0
	, initialize:function()
	{
		//console.log( "Welcome to this world Cells you are a collection", this.toJSON() );
	},
	addCells:function( kind, cell_type, amount, layout_type, row_divider )
	{
		var self = this,
			how_many = amount ? amount : 1,
			divider = row_divider ? row_divider : 1;

		how_many = how_many / divider;
		
		for( var i = how_many; i > 0; i-- )
		{
			try
			{
				var num = how_many - i + 1, temp_cell = new DDLayout.models.cells[kind]({name : num.toString(),
																		   cell_type : cell_type,
																		   row_divider : row_divider});
			}
			catch( e )
			{
				console.error( "The cell type you are trying to add do not exist", e.message )
			}
			
			self.add( temp_cell );
		}
		
		return self;
	},
    remove:function(models, options){
        DDLayout.ddl_admin_page.instance_layout_view.model.trigger('cells-collection-remove-cell', models, options );
        return Backbone.Collection.prototype.remove.call(this, models, options );
    },
    add:function(model, options){
        var undefined;
        if( DDLayout.ddl_admin_page !== undefined ){
            DDLayout.ddl_admin_page.instance_layout_view.model.trigger('cells-collection-add-cell', model, options );
        }
        return Backbone.Collection.prototype.add.call(this, model, options );
    },
	has_not_only_of_type:function( cell_type ){
		var self = this;

		if( self.kind !== 'Cells' ) return false;

		if( self.models.length === 0 ) return false;

		return _.some(self.models, function(item){
			return  item.get('cell_type') !== cell_type && item.get('cell_type') !== 'spacer';
		});
	},
	remove_of_type:function( type ){
		var self = this,
			child = self.where({cell_type:type});

		child = child.length ? child : [];
			var without = _.difference( self.models, child );

		self.reset( without, {removed:child} );
	}
});