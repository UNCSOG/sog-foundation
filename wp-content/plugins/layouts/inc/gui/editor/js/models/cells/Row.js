DDLayout.models.cells.Row = DDLayout.models.abstract.Element.extend({
    defaults:{
        Cells: DDLayout.models.collections.Cells
        ,cssClass:'row-fixed'
        ,kind: 'Row'
        ,row_type:'row'
        ,layout_type:'fixed'
        ,mode:'normal'
    },
    compound: ''
    /**
     * @access: public
     * @return: int
     * returns the width in span of row instance
     */
    , getWidth:function()
    {
        var self = this, width = 0;

        if( !self.get('Cells') ) return 1;

        _.each( self.get('Cells').models, function( item, index, list ) {
            width += item.get('width') * item.get('row_divider');
        });

        return width;
    },
    isEmpty:function()
    {
        return _.isEmpty( this.get('Cells') );
    },
    setLayoutType:function( type )
    {
        this.set( 'layout_type', type );
    },
    getLayoutType:function()
    {
        return this.get('layout_type');
    },
    get_parent_width: function ( row ) {
        // see if the given row is inside a container in this row
        var self = this;

        var cells = self.get('Cells');

        if( !cells ) return 0;

        for (var i = 0; i < cells.length; i++) {
            var test_cell = cells.at(i);
            if ( test_cell.hasRows() ) {
                var rows_in_container = test_cell.get('Rows');
                var parent_width = test_cell.get('width');
                parent_width = rows_in_container.get_parent_width( row, parent_width);
                if (parent_width > 0) {
                    return parent_width;
                }
            }
        }

        return 0;
    },
    get_empty_space_to_right_of_cell: function ( cell ) {
        var self = this;

        var cells = self.get('Cells');

        if( !cells ) return -1;

        for (var i = 0; i < cells.length; i++) {
            var test_cell = cells.at(i);
            if (_.isEqual(test_cell, cell)) {
                var count = 0;
                for (var j = i + 1; j < cells.length; j++) {
                    test_cell = cells.at(j);
                    if (!test_cell.isEmpty()) {
                        return count;
                    }
                    count++;
                }
                return count;
            }
            if ( test_cell.hasRows() ) {
                var rows_in_container = test_cell.get('Rows');
                var space = rows_in_container.get_empty_space_to_right_of_cell(cell);
                if (space >= 0) {
                    return space;
                }
            }
        }

        return -1;
    },

    find_cell_of_type : function ( cell_type ) {
        var self = this;

        var cells = self.get('Cells');

        if( !cells ) return false;

        cells = cells.models;

        for (var i = 0; i < cells.length; i++) {
            var test_cell = cells[i];
            if (test_cell.get('cell_type') == cell_type) {
                return test_cell;
            }

            if ( test_cell.hasRows() ) {
                var rows_in_container = test_cell.get('Rows');

                if( typeof rows_in_container === 'undefined' ) {
                    return false;
                } else if( _.isArray( rows_in_container ) ){
                    rows_in_container = new DDLayout.models.collections.Rows( rows_in_container );
                }


                var cell = rows_in_container.find_cell_of_type(cell_type);
                if (cell) {
                    return cell;
                }
            }
        }
        return false;
    },

    find_cells_of_type : function ( cell_type ) {
        var self = this;

        var cells = self.get('Cells');

        if( !cells ) return false;

        var ret = [];

        _.each(cells, function( v, i, l ) {
            var test_cell = l.at(i);

            if (test_cell.get('cell_type') == cell_type) {
                ret.push( test_cell );
            }

            if ( test_cell.hasRows() ) {
                var rows_in_container = test_cell.get('Rows');

                if( typeof rows_in_container === 'undefined' ) {
                    return false;
                } else if( _.isArray( rows_in_container ) ){
                    rows_in_container = new DDLayout.models.collections.Rows( rows_in_container );
                }

                var cells = rows_in_container.find_cells_of_type(cell_type)
                if (cells) {
                    _.each(cells, function(cell){
                        ret.push( cell );
                    });
                }
            }
        });
        return ret.length === 0 ? false : ret;
    },

    changeWidth : function (new_width) {
        var self = this;

        var cells = self.get('Cells');
        var current_width = self.getWidth();

        if( !cells ) return;

        if (new_width > current_width) {
            var amount_to_add = new_width - current_width;
            cells.addCells( 'Cell', 'spacer', amount_to_add, self.getLayoutType(), 1);
        }
        if (new_width < current_width) {
            var width = 0;
            var cells_to_remove = [];
            for (var i = 0; i < cells.length; i++) {
                var test_cell = cells.at(i);
                if (width >= new_width) {
                    cells_to_remove.push(test_cell);
                }
                width += test_cell.get('width');
            }

            for (var i = 0; i < cells_to_remove.length; i++) {
                cells.remove(cells_to_remove[i]);
            }
        }
    },
    getMinWidth : function ()
    {
        var self = this;

        var cells = self.get('Cells');

        if( !cells ) return 1;

        for (var i = cells.length - 1; i >= 0; i--) {
            var test_cell = cells.at(i);
            if (test_cell.get('cell_type') != 'spacer') {
                var min_width = 0;
                for (var j = 0; j <= i; j++) {
                    var test_cell = cells.at(j);
                    min_width += test_cell.get('width');
                }
                return min_width;
            }
        }

        return 1;
    },
    is_only_row:function(){
        var self = this;
        if( typeof self.collection === 'undefined' ) return false;
        return self.collection.length === 1;
    },
    find_cell_by_property : function ( property, value ) {

        if( !property || !value ) return false;

        var self = this;

        var cells = self.get('Cells');

        if( !cells ) return false;

        cells = cells.models;

        var len = cells.length;

        for (var i = 0; i < len; i++) {
            var test_cell = cells[i];
            if (test_cell.get(property) == value) {
                return test_cell;
            }

            if ( test_cell.hasRows() ) {
                var rows_in_container = test_cell.get('Rows');

                if( typeof rows_in_container === 'undefined' ) {
                    return false;
                } else if( _.isArray( rows_in_container ) ){
                    rows_in_container = new DDLayout.models.collections.Rows( rows_in_container );
                }


                var cell = rows_in_container.find_cell_by_property(property, value);
                if (cell) {
                    return cell;
                }
            }
        }
        return false;
    },
    get_row_where_cell_has_property_value : function ( property, value ) {

        if( !property || !value ) return false;

        var self = this;

        var cells = self.get('Cells');

        if( !cells ) return false;

        cells = cells.models;

        var len = cells.length;

        for (var i = 0; i < len; i++) {
            var test_cell = cells[i];
            if (test_cell.get(property) == value) {
                return self;
            }

            if ( test_cell.hasRows() ) {

                var rows_in_container = test_cell.get('Rows');

                if( typeof rows_in_container === 'undefined' ) {
                    return false;
                } else if( _.isArray( rows_in_container ) ){
                    rows_in_container = new DDLayout.models.collections.Rows( rows_in_container );
                }


                var row = rows_in_container.get_row_where_cell_has_property_value(property, value);

                if ( row ) {
                    return row;
                }
            }
        }
        return false;
    },
});