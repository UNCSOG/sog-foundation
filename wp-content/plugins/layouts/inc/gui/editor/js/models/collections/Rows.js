DDLayout.models.collections.Rows = Backbone.Collection.extend({
	//model:DDLayout.models.cells.Row
	  kind:'Rows'
	, layout:DDLayout.models.cells.Layout
	, initialize:function()
	{
		//console.log( "Welcome to this wordls Rows, you are a collection ", this.toJSON() );
	},
    getWidth:function()
    {
        return this.collection.length;
    },
	addRowAfterAnother:function( prev_row, cells, row_name, additional_css, layout_type, row_divider, kind, row_type )
	{

		var layout_kind = 'normal';

		if( DDLayout.ddl_admin_page.is_integrated_theme() === false ){

			layout_kind = 'private';

		} else {

			try {
				layout_kind = DDLayout.ddl_admin_page.instance_layout_view.model.get('layout_type');
			} catch( e ) {
				layout_kind = 'normal';
			}

		}

		var row_kind = kind ? kind : 'Row';

		var mode = ( layout_kind == 'private' ) ?
			Toolset.hooks.applyFilters( 'ddl-set-row-default-mode', 'full-width', layout_kind ) :
			Toolset.hooks.applyFilters( 'ddl-set-row-default-mode', 'normal', layout_kind );

		if( row_kind !== 'Row' ){
			mode = row_kind.toLowerCase();
		}

		var self = this,
			index = self.indexOf( prev_row ),
			len = self.length,
			css_class = row_type +'-' + layout_type,
			row_type = row_type ? row_type : 'row',
			row = new DDLayout.models.cells[kind]( {kind : row_kind,
				Cells : cells,
				row_type: row_type,
				cssClass : css_class,
				name : row_name,
				mode : mode,
				additionalCssClasses: additional_css,
				row_divider: row_divider} );

		//row.layout = self.layout;

		row.setLayoutType( layout_type );

		self.add( row, {at:index+1} );

		return self;
	},
	addThemeSectionRowAfterAnother:function( prev_row, row_name, type, kind, layout_type )
	{
		var self = this,
			index = self.indexOf( prev_row ),
			row = null;

		switch( kind )
		{
			case 'ThemeSectionRow':
				row = new DDLayout.models.cells.ThemeSectionRow(
					{kind : kind,
					name : row_name,
					type:type,
					layout_type:layout_type
					} );
				break;
			default:
				row = new DDLayout.models.cells.ThemeSectionRow(
					{kind : kind,
						name : row_name,
						type:type,
						layout_type:layout_type
					} );
				break;
		}

		self.add( row, {at:index+1} );

		return self;
	},
	addRows:function( amount, width, layout_type, row_divider, cellKind, cellType, kind, row_type )
	{
		var self = this,
			row,
			cells,
			row_width = width,
			cell_kind = cellKind ? cellKind : 'Cell',
			cell_type = cellType ? cellType : 'undefined',
			row_kind = kind ? kind : 'Row',
            row_type = row_type ? row_type : 'row',
            layout = layout_type || DDLayout.ddl_admin_page.getLayoutType();
		
		for( var i = 1; i <= amount; i++)
		{
			
			cells = new DDLayout.models.collections.Cells;
			
			cells.layout = layout  || self.layout;

			cells.addCells( cell_kind, cell_type, row_width, layout_type, row_divider );
			
			row = new DDLayout.models.cells[row_kind]( {kind:row_kind,
													Cells:cells,
													row_type: row_type,
													cssClass: row_type ? row_type +'-' : 'row-'+layout,
													name:row_kind + ' ' + i,
													row_divider: row_divider} );
			
			row.setLayoutType( layout );
			
			self.push( row );
		}
		return self;
	},
    //FIXME:there is a problem it doesn't loop through all Rows
	get_parent_width : function ( row, parent_width ) {
		// If the row is in this "Rows" then return the parent width
		var self = this;

		// see if the row is in these rows
		for (var i = 0; i < self.length; i++) {
			var test_row = self.at(i);
			if (_.isEqual(test_row, row)) {
				return parent_width;
			}
		}
		
		// haven't found the row so we need to look deeper
		for (var i = 0; i < self.length; i++) {
			var test_row = self.at(i);
			var test_width = test_row.get_parent_width( row );
			if (test_width > 0) {
				return test_width;
			}
		}

		return parent_width;
	},
	
	get_empty_space_to_right_of_cell : function ( cell ) {
		var self = this;
		
		// see if the row is in these rows
		for (var i = 0; i < self.length; i++) {
			var test_row = self.at(i);
			var space = test_row.get_empty_space_to_right_of_cell(cell);
			if (space >= 0) {
				// found in this row
				return space;
			}
		}
			
		// not found in this row
		return -1;
		
	},
	find_cell_by_property : function ( property, value ){

        if( !property || !value ) return false;

		var self = this, len = self.length;

		// see if the row is in these rows
		for (var i = 0; i < len; i++) {
			var test_row = self.at(i);
			var cell = test_row.find_cell_by_property( property, value );
			if (cell) {
				return cell;
			}
		}

		return false;
	},
    get_row_where_cell_has_property_value : function ( property, value ){

        if( !property || !value ) return false;

        var self = this, len = self.length;

        // see if the row is in these rows
        for (var i = 0; i < len; i++) {
            var test_row = self.at(i);
            var row = test_row.get_row_where_cell_has_property_value( property, value );
            if ( row ) {
                return row;
            }
        }

        return false;
    },
	find_cell_of_type : function ( cell_type ) {
		var self = this;
		
		// see if the row is in these rows
		for (var i = 0; i < self.length; i++) {
			var test_row = self.at(i);
			var cell = test_row.find_cell_of_type( cell_type );
			if (cell) {
				return cell;
			}
		}

		return false;
	},
	find_cells_of_type : function ( cell_type ) {
		var self = this, ret = [];

		// see if the row is in these rows
		for (var i = 0; i < self.length; i++) {
			var test_row = self.at(i);
			var cells = test_row.find_cells_of_type( cell_type );
			if ( cells ) {
				_.each(cells, function(cell){
					ret.push( cell );
				});
			}
		}

		return ret.length === 0 ? false : ret;
	},
	hasRowsOfKind:function( kind )
	{
		var self = this;

		var found = _.filter(self.models, function( row ){
			return row.get('layout_type') == kind;
		});
		
		return found.length > 0;
	},
	changeLayoutType : function (new_type)
	{
		var self = this;
		for (var i = 0; i < self.length; i++) {
			var row = self.at(i);
			row.setLayoutType(new_type);
		}
		
	},
	changeWidth : function (new_width) {
		var self = this;
		for (var i = 0; i < self.length; i++) {
			var row = self.at(i);
			row.changeWidth(new_width);
		}
	},
	getMinWidth : function ()
	{
		var self = this;
		var min_width = 0;
		for (var i = 0; i < self.length; i++) {
			var row = self.at(i);
			var row_min_width = row.getMinWidth();
			min_width = Math.max(min_width, row_min_width);
		}
		
		return min_width;
	},
    remove:function(models, options){
        var undefined;
        if( DDLayout.ddl_admin_page !== undefined ) {
            DDLayout.ddl_admin_page.instance_layout_view.model.trigger('rows-collection-remove-rows', models, options);
        }
        return Backbone.Collection.prototype.remove.call(this, models, options );
    },
    reset:function(models, options){
        var undefined;
        if( DDLayout.ddl_admin_page !== undefined ) {
            DDLayout.ddl_admin_page.instance_layout_view.model.trigger('rows-collection-reset-rows', models, options);
        }
        return Backbone.Collection.prototype.reset.call(this, models, options );
    },
	get_max_id : function () {
		var self = this;
		var max_id = 0;

		for (var i = 0; i < self.length; i++) {
			var row = self.at(i);
			var max_id_for_row = row.get_max_id();
			
			if (max_id_for_row > max_id) {
				max_id = max_id_for_row;
			}
				
		}
		
		return max_id;
	},
	add:function(model, options){
		var undefined;
		if( DDLayout.ddl_admin_page !== undefined ){
			DDLayout.ddl_admin_page.instance_layout_view.model.trigger('rows-collection-add-row', model, options );
		}
		return Backbone.Collection.prototype.add.call(this, model, options );
	},
});