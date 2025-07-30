DDLayout.views.ContainerRowView = DDLayout.views.RowView.extend({
    initialize: function ( options ) {
        var self = this;
        self.hover_target = null;
        _.bindAll(self, 'tooltip_show', 'tooltip_hide', 'handleCreateNewRow');
        self.options = options;
        self.cells = null;
        //call parent constructor
        DDLayout.views.abstract.ElementView.prototype.initialize.call(self, options);
        self.errors_div = jQuery(".js-ddl-message-container");
    },
    render: function ( args ) {
        DDLayout.views.RowView.prototype.render.call(this, args);
        return this;

    },
    makeSelfElement: function ( id, prefix, itemEditorCssBaseClass ) {

        var self = this;

        self.template = _.template(jQuery('#' + prefix + itemEditorCssBaseClass + '-template').html());

        self.$el.html(self.template(_.extend(self.model.toJSON(), {
            layout_type: self.model.getLayoutType(),
            invisibility: self.options.invisibility
        })));

        self.$el.removeClass('row');

        self.$el.addClass('row-container js-row-container');

        self.$el.prop('id', 'row-move-' + id);

        return self;
    },
    makeRowCellsViews: function ( rowCells, args ) {

        var self = this,
            options = _.extend({
                    model: self.model.get("Cells"),
                    el: rowCells,
                    compound: '',
                    container: self.options.container,
                    invisibility: self.options.invisibility,
                    current: self.options.current,
                    parentDOM: self.$el
                },
                args);

        //make sure we garbage collected previous instances of cells class
        if ( self.cells !== null ) {
            self.cells = null;
        }

        self.cells = new DDLayout.views.CellsView(options);

        return self;
    },
    addRow: function ( row_name, additional_css, layout_type, row_divider_in ) {
        var self = this,
            container = self.get_parent_view().get_parent_view_dom().data('view'),
            row_divider = row_divider_in ? row_divider_in : self.getRowDivider();

        DDLayout.ddl_admin_page.save_undo();

        var
            cells = new DDLayout.models.collections.Cells,
            row_width = (layout_type == 'fixed') ? container.model.get('width') : 12;

        cells.layout = self.model.layout;

        cells.addCells('Cell', 'spacer', row_width, layout_type, row_divider);

        self.model.collection.addRowAfterAnother(self.model, cells, row_name, additional_css, layout_type, row_divider, self.model.get('kind'), self.model.get('row_type'));

        self.eventDispatcher.trigger("re_render_all");
    },
    getRowContainer: function () {
        var self = this;

        try {
            return self.get_parent_view().get_parent_view_dom().data('view').model;
        }
        catch ( e ) {
            console.log(e.message);
            return null;
        }
    },
    is_top_level_row: function () {
        return false;
    }
});