DDLayout.views.AccordionPanelView = DDLayout.views.ContainerRowView.extend({
    initialize:function(options)
    {
        var self = this;
        //call parent constructor
        DDLayout.views.ContainerRowView.prototype.initialize.call(self, options);
        self.$el.addClass('accordion-panel js-accordion-panel');
    },
    _initializeEditRowHandler:function()
    {
        var self = this;
        jQuery( '.js-row-edit', self.el ).on('click', function(event){
            event.stopImmediatePropagation();
            DDLayout.ddl_admin_page.show_panel_dialog('edit', self);
        });

        var pencil_icon = self.$el.find( '.js-row-edit'),
            remove_icon = self.$el.find( '.js-row-remove');

        pencil_icon.toolsetTooltip();

        remove_icon.toolsetTooltip();

        pencil_icon.on('tooltip_show', self.tooltip_show);

        pencil_icon.on('tooltip_hide', self.tooltip_hide);

        remove_icon.on('tooltip_show', self.tooltip_show);

        remove_icon.on('tooltip_hide', self.tooltip_hide);

    },
    addRow: function( row_name, additional_css, layout_type, row_divider_in)
    {
        var self = this,
            container = self.get_parent_view().get_parent_view_dom().data('view'),
            row_divider = row_divider_in ? row_divider_in : 12;


        DDLayout.ddl_admin_page.save_undo();

        var
            cells = new DDLayout.models.collections.Cells,
            row_width = (layout_type == 'fixed') ? container.model.get('width') : 12;

        cells.layout = self.model.layout;

        cells.addCells( 'Cell', 'spacer', row_width , layout_type, row_divider);

        self.model.collection.addRowAfterAnother( self.model, cells, row_name, additional_css, layout_type, row_divider, self.model.get('kind'), self.model.get('row_type'));

        self.eventDispatcher.trigger("re_render_all");
    }
});