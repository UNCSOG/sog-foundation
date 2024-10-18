DDLayout.models.cells.Accordion = DDLayout.models.cells.Container.extend({
    defaults:{
        Rows:DDLayout.models.collections.Rows
        , kind:'Accordion'
        , cell_type:'accordion-cell'
        , navigation_style: 'accordion'
        , additionalCssClasses: DDLayout_settings.DDL_JS.bootstrap_version === 4 ? 'accordion' : 'panel-group'
    },
    compound: '',
    addRows:function(amount, width, layout_type, row_divider )
    {
        var self = this,
            rows = new DDLayout.models.collections.Rows;

        rows.addRows(amount, width, layout_type, row_divider, undefined, 'spacer', 'Panel', 'accordion-panel');

        self.set( "Rows", rows );

        return self;
    }
});
