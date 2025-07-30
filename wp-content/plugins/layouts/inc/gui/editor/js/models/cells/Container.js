DDLayout.models.cells.Container = DDLayout.models.abstract.Element.extend({
	defaults:{
		Rows:DDLayout.models.collections.Rows
		, kind:'Container'
	},
	compound: '',
    rowsNum:0
	, getWidth:function()
	{
		return this.get('row').get('cells').length
	},
    isEmpty:function()
    {
        return _.isEmpty( this.get('Rows') );
    },
	addRows:function(amount, width, layout_type, row_divider )
	{
		var self = this,
		rows = new DDLayout.models.collections.Rows;

		rows.addRows(amount, width, layout_type, row_divider, undefined, 'spacer', 'Row');
		
		self.set( "Rows", rows );
		
		return self;
	},
	hasRowsOfKind:function( kind )
	{
		return this.get('Rows').hasRowsOfKind( kind );
	}
	
});