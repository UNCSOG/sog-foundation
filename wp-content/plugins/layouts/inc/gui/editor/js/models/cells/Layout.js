DDLayout.models.cells.Layout = DDLayout.models.abstract.Element.extend({
	defaults:{
		  type:''
		, name:''
		, cssframework:''
		, template:''
		, parent:0
		, Rows:DDLayout.models.collections.Rows
		, width:12
        , cssClass:'span12'
        , id: 0
        , kind: 'Layout'
		, has_child: false
		, slug: ''
		, children_to_delete : null
		, child_delete_mode : null
		, has_loop:false
		, has_post_content_cell: false
		, column_prefix : DDLayout_settings.DDL_JS.column_prefix_default
		, no_default_prefix: false
	}
	, url:ajaxurl
	, layout_slug_cached:null
    , is_layout:function()
    {
        return true;
    }
	, numRows:function()
	{
		return this.get('Rows').length
	},
    isEmpty:function()
    {
        return _.isEmpty( this.get('Rows') );
    },
    getType:function()
    {
        return this.get('type');
    },
	get_slug: function () {
		return this.get('slug');
	},
    /**
     * @override
     */
	initialize:function( json )
	{

		var self = this, data;

        _.bindAll( self, 'after_removed_cell', 'after_removed_row');

        DDLayout.models.abstract.Element.prototype.initialize.call(self);

        data = self.parse(json);

        if( data )
        {
			// remove the children delete info.
			data.children_to_delete = null;
			
            self.populate_self_on_first_load( data );
        }

        self.listenTo(self, 'created_new_cell', self.create_new_cell_callback );
        self.listenTo( self, 'rows-collection-remove-rows', self.remove_row);
        self.listenTo( self, 'cells-collection-remove-cell', self.remove_cell);
        self.listenTo(self, 'rows-collection-reset-rows', self.reset_rows);
        self.listenTo(self, 'cells-collection-add-cell', self.add_cell);
		
		self._initialize_uniqueId();

        return self;
	},
    /**
     *
     * @param models
     * @param options - options.previousModels:Array (the collection before reset), options.removed:Object (the row removed)
     */
    add_cell:function(model, options){
        // do somthing when cell added
    },
    reset_rows:function( models, options ){
        if( options && options.hasOwnProperty('removed') && options.removed instanceof DDLayout.models.cells.Row ){
            this.trigger('rows-collection-after-remove-rows', options.removed, options );
        }
    },
    create_new_cell_callback:function( cell_model ){
        if( cell_model.get('displays-post-content')  )
        {
            this.set('has_post_content_cell', true );
        }
    },
    remove_row:function(model, options){
        _.defer( this.after_removed_row, model, options )
    },
    remove_cell:function(model, options){
        _.defer( this.after_removed_cell, model, options )
    },
    after_removed_cell: function (model, options) {
        if (
            ( 'cell-post-content' === model.get('cell_type') || "cell-content-template" === model.get('cell_type') ) &&
            ( this.has_cell_of_type("cell-post-content") === false || this.has_cell_of_type("cell-content-template") === false )
        ) {
            this.set('has_post_content_cell', false);
        }

        this.trigger('cells-collection-after-remove-cells', model, options );
    },
    after_removed_row:function(model, option){
        this.trigger('rows-collection-after-remove-rows', model, option );
    },
    populate_self_on_first_load: function( data )
    {
        var self = this;

        if( !data ) return self;

        _.each(data, function( item, index, object ){
              if( object.hasOwnProperty( index ) )
              {
                  self.set( index, item );
              }
        });

		if( DDLayout_settings.DDL_JS.layout_id ){
			self.set( 'id', DDLayout_settings.DDL_JS.layout_id );
		}

        return self;
    },
    /**
     * performs an ajax call to get json data
     */
    get_data_from_server: function () {
        var self = this;
        self.fetch({
                data: jQuery.param({action: 'get_layout_data', layout_id: DDLayout_settings.DDL_JS.layout_id}),
                type: 'POST',
                success: function (model) {
                    self.set('id', DDLayout_settings.DDL_JS.layout_id);
                    self.trigger("json_fetched", arguments[0]);
                },
                error: function () {
                console.error(arguments);
            }
        });
    }
    /**
     * @override
     */
	,parse:function( json, xhr )
	{
		try
		{
			var self = this, data = json;

			if( data !== null )
			{	
				if( !data.Rows ) return null;


				self.set('Rows', self._scan_json( data.Rows, {name:data.name, cssframerwork:data.cssframework, type:data.type, width:data.width} ) );
				delete data.Rows;
			}
		}
		catch( e )
		{
			console.error( e.message, e );
		}
		
		return data;
	}
    /**
     * @param:json - json data
     * @return:rows - model structure
     * @access:private
     */
	, _scan_json: function(json, properties)
	{
		var self = this, tmpRows = null, data = json, layout = properties;

        if( data )
        {
            tmpRows = new DDLayout.models.collections.Rows;
			
            _.each(data, function(r, i, rows){

                var tmp = undefined, row = r, row_model;
				
                if( row && row.Cells )
                {

	                tmp = new DDLayout.models.collections.Cells

                    _.each(row.Cells, function( element, j, cells ){
                        var cell = element, kind = cell.kind;

                        delete cell.kind;

                        if( cell.hasOwnProperty('Rows') )
                        {
                            var container, cell_tmp = _.extend({}, cell);
                                //we don't want wo make it copy twice one as an object and one as a model
                                delete cell_tmp.Rows;
                                container = new DDLayout.models.cells[kind](cell_tmp);

                            try
                            {

                                container.set('Rows', self._scan_json( cell.Rows, layout ) );
                                container.layout = layout;
                                tmp.push( container );
                            }
                            catch( e )
                            {
                                console.error( e.message );

                            }
                        }
                        else
                        {
                            try
                            {
                                if( cell.cell_type !== 'spacer' &&
											DDLayout_settings.DDL_JS.available_cell_types.indexOf( cell.cell_type ) === -1) {
									
                                    cell.ddl_missing_cell_type = cell.cell_type;
                                    cell.cell_type = 'ddl_missing_cell_type';
                                }
                                var cell = new DDLayout.models.cells[kind]( cell );
                                cell.layout = layout;
                                tmp.push( cell );
                            }
                            catch( e )
                            {
                                console.error( e.message );

                            }
                        }
                    });

		                tmp.layout = layout;
		                row_model = new DDLayout.models.cells.Row( {Cells:tmp} );
		                //remove cells
		                delete row.Cells;

                }
                else
                {
	                if( row.kind === 'ThemeSectionRow' )
	                {
		                row_model = new DDLayout.models.cells.ThemeSectionRow();

	                }
                }

	            row_model.layout = layout;

	            //override default attributes if necessary
	            _.extend(row_model.attributes, row);
	            //add it to the Layout rows collection
				try{
					tmpRows.push( row_model );
				} catch( e ){
					console.log( 'Exception: ', e, e.message, tmpRows, row_model  );
				}

				try{
					tmpRows.layout = layout;
				} catch( e ){
                    console.log( 'Exception: ', e, e.message, tmpRows, layout  );
				}

            });
        }

		return tmpRows;
	},
	
	toJSON: function () {
		this.set( 'has_child', this.has_cell_of_type('child-layout') );
		this.set( 'has_loop', this.has_cell_of_type("post-loop-cell") || this.has_cell_of_type("post-loop-views-cell") );
        this.set('has_post_content_cell', this.get('has_post_content_cell') || this.has_cell_of_type("cell-post-content") || this.has_cell_of_type("cell-content-template")  );
		this.set( 'layout_type', this.is_private() ? 'private' : 'normal' );
        return DDLayout.models.abstract.Element.prototype.toJSON.call(this);
	},
	
	is_parent:function(){
		return this.has_cell_of_type('child-layout');
	},
	is_private:function(){
		return this.get('layout_type') === 'private';
	},
	get_parent_width: function ( row ) {
		
		var rows = this.get('Rows');
		return rows.get_parent_width( row , this.get('width') );
		
	},
	
	get_empty_space_to_right_of_cell : function ( cell ) {

		var rows = this.get('Rows');
		return rows.get_empty_space_to_right_of_cell(cell);
		
	},
	
	has_cell_of_type : function ( cell_type ) {
		return this.find_cell_of_type( cell_type ) != false;
	},
	find_cell_of_type : function ( cell_type ) {
		
		var rows = this.get('Rows');
		
		return rows.find_cell_of_type( cell_type );
	},
	find_cell_by_property : function ( property, value ) {

        if( !property || !value ) return false;

		var rows = this.get('Rows');

		return rows.find_cell_by_property( property, value );
	},
    get_row_where_cell_has_property_value : function ( property, value ){

        if( !property || !value ) return false;

        var rows = this.get('Rows');

        return rows.get_row_where_cell_has_property_value( property, value );
    },
    find_cells_of_type : function ( cell_type ) {

        var rows = this.get('Rows');

        return rows.find_cells_of_type( cell_type );
    },
	
	has_view_target : function ( view_id ) {
		var cells = this.getLayoutCells();

		for (var i = 0; i < cells.length; i++) {
			var test_cell = cells[i];
			
			if (test_cell.get('cell_type') == 'views-content-grid-cell') {
				var content = test_cell.get('content');
				if (content.ddl_layout_view_id == view_id) {
					if (content.parametric_mode == 'full' || content.parametric_mode == 'results') {
						return true;
					}
				}
			}
		}
		
		return false;
	},
	
	get_views_needing_result_cells : function () {
		var views = Array();
		
		var cells = this.getLayoutCells();

		for (var i = 0; i < cells.length; i++) {
			var test_cell = cells[i];
			
			if (test_cell.get('cell_type') == 'views-content-grid-cell') {
				var content = test_cell.get('content');
				if (content.parametric_mode == 'form') {
					if (!this.has_view_target(content.ddl_layout_view_id)) {
						if (!_.contains(views, content.ddl_layout_view_id)) {
							views.push(content.ddl_layout_view_id);
						}
					}
				}
			}
		}
		
		return views;
		
	},
	
	set_parent_layout : function ( parent_layout ) {
		this.set('parent', parent_layout);
	},
	
	get_parent_layout : function ( ) {
		return this.get('parent');
    },
    getLayoutCells:function()
    {
        return DDLayout.models.cells.Layout.getCells( this );
    },
    getLayoutContainers:function( )
    {
        return DDLayout.models.cells.Layout.getContainers( this );
    },
    getLayoutSelected:function( )
    {
        var self = this,
            cells = self.getLayoutCells( );

        if( !cells || cells == null || cells == false ) return null;

        return _.filter(cells, function(item){
                return item.selected_cell === true;
        });
    },
	changeLayoutType : function (new_type)
	{
		
		var self = this;
		
		self.set('type', new_type);
		
		var rows = this.get('Rows');
		rows.changeLayoutType(new_type);

		if (new_type == 'fluid' && self.get('width') != 12) {
			self.changeWidth(12);
		}
	},
	getMinWidth : function ()
	{
		var rows = this.get('Rows');
		return rows.getMinWidth();
		
	},
	changeWidth : function (new_width)
	{
		var self = this;

		if (self.get('width') != new_width) {
			self.set('width', new_width);

			var rows = this.get('Rows');
			rows.changeWidth(new_width);
			DDLayout.ddl_admin_page.render_all();
		}
	},
	setChildrenToDelete: function( children, mode )
	{

		if( children === null )
		{
			this.set('children_to_delete', null);
			return;
		}

		children = JSON.parse( children );

		if( 'children_layouts' in children )
		{
			this.set('children_to_delete', children['children_layouts']);
		}
		else
		{
			this.set('children_to_delete', null);
		}
		this.set('child_delete_mode', mode);

	},
	getChildrenToDelete:function()
	{
		return this.get('children_to_delete');
	},
	
	_initialize_uniqueId : function () {
		// Make sure that underscore unique ID is going to return an
		// id that is greater than any we already use.

		var rows = this.get('Rows');
		var max_id = rows.get_max_id();
		
		// Keep getting the uniqueId until it's greater than the max id
		while (max_id > _.uniqueId()) {
			
		}
	},
    cells_of_types: function( cells_types ){
        var self = this, ret = [];

        if( _.isObject( cells_types ) ){

            var types = cells_types;

             _.each(types, function( type ){
                var find = self.find_cells_of_type( type );

                if( find && _.isArray(find) && find.length !== 0 ){
                        _.each(find, function(v,i,l){
                            ret.push( l[i].toJSON()  );
                        });
                }
            });
        }
        return ret;
    }
});


/*
** @access: Static
** @return: Array
** @param: layout or container model
 */
DDLayout.models.cells.Layout.getContainers = function( check )
{
    if( check === undefined || check === null || check === false || !check || !check.has("Rows") ) return null;

    var
        rows = check.get("Rows"),
        containers = [];

	if( rows instanceof Backbone.Collection === false && _.isArray(rows) ){
		rows = new DDLayout.models.collections.Rows( rows );
	}

    rows.each(function(item){
	    var cells = item.get("Cells");

		if( cells )
		{
			cells.each(function( r ){

				if( r.hasRows() )
				{
					containers.push( r );
					containers = _.union( containers, DDLayout.models.cells.Layout.getContainers( r ) );
				}
			});
		}
    });

    return containers;
};
/*
 ** @access: Static
 ** @return: Array
 ** @param: layout or container model
 */
DDLayout.models.cells.Layout.getCells = function( check )
{
    if( check === undefined || check === null || check === false || !check || !check.has("Rows") ) return null;

    var
        rows = check.get("Rows"),
        cells = [];

    rows.each(function(item){
		var row_cells = item.get("Cells");

	    if( row_cells )
	    {
		    row_cells.map(function( r ){

			    if( r.hasRows() )
			    {
				    cells.push( r );
				    cells = _.union( cells, DDLayout.models.cells.Layout.getCells( r ) );
			    }
			    cells.push( r );
		    });
	    }

    });
    return cells;
};
