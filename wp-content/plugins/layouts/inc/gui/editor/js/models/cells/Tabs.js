DDLayout.models.cells.Tabs = DDLayout.models.cells.Container.extend({
    defaults:{
        Rows:DDLayout.models.collections.Rows
        , kind:'Tabs'
        , cell_type:'tabs-cell'
        , navigation_style: 'tabs'
        , justified: false
        , stacked: false
        , fade: false
    },
    compound: '',
    addRows:function(amount, width, layout_type, row_divider )
    {
        var self = this,
            rows = new DDLayout.models.collections.Rows;

        rows.addRows(amount, width, layout_type, row_divider, undefined, 'spacer', 'Tab', 'tabs-tab');

        self.set( "Rows", rows );

        return self;
    }
});