DDLayout.models.cells.Cell = DDLayout.models.abstract.Element.extend({
	defaults:{
		content:null
		, kind:'Cell'
		, cell_type:''
        , column_prefix : DDLayout_settings.DDL_JS.column_prefix_default
	},
    compound: '',
    isEmpty:function()
    {
        return this.get('cell_type') == 'spacer';
    },
    set:function(attributes, options)
    {
        if( attributes == 'width')
        {
            this.set( 'cssClass', 'span'+options.valueOf() );
        }
        return DDLayout.models.abstract.Element.prototype.set.call(this, attributes, options);
    }
});