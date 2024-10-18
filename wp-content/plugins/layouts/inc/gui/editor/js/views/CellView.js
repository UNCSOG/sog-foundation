DDLayout.views.CellView = DDLayout.views.abstract.ElementView.extend({
	selected:false,
	events: {
		'click':'_manageCellSelection',
		'resizestart':'_resizeStart',
		'resize':'_resizeResize',
		'resizestop':'_resizeStop',
		'mouseenter' : '_manageMouseEnter',
		'mouseleave' : '_manageMouseLeave'
	},

	MIN_WIDTH:50,
	MIN_HEIGHT:50,
	defaultCssClass:'placeholder',
	_FLUID_CELL_MARGIN_FLOAT:2.0618556701030926,
	initialize:function(options)
	{
		var self = this;

        self._prevent_operations = false;
		self.options = options;

		if ( typeof self.model.selected_cell == 'undefined') {
			self.model.selected_cell = false;
		}

		self.domRowCellsArray = null;

		DDLayout.views.abstract.ElementView.prototype.initialize.call( self, options );

		try
		{
                self.addHooks();
		}
		catch( e )
		{
			console.log( e.message );
		}
	},
	addHooks: function(){
		var self = this;
        self.listenTo(self.model, "deselected_element", self._manageDeselection, self );
        self.listenTo(self.model, 'view_model_removed', self.remove, self );

        self.listenTo(self.eventDispatcher, 'cell_selection_changed', self._collectionsManageSelection, self);

        self.listenTo(self.model, 'hide_cell', self.hide_cell, self );
        self.listenTo(self.model, 'show_cell', self.show_cell, self );

        self.listenTo( self.model, 'display_this_is_not_suitable_position', self._thisIsNotAllowed, self );

        self.listenTo(self.eventDispatcher, 'move_selected_cell_left', self._move_selected_cell_left, self);
        self.listenTo(self.eventDispatcher, 'move_selected_cell_right', self._move_selected_cell_right, self);

        self.listenTo(self.eventDispatcher, 'deselect_element', self._deselectElement, self );
        //self.listenTo(self.eventDispatcher,'sortable_row_initialized', self._onCsutomSortableInitialized, self);
        return self;
	},
	render:function( selected )
	{
		var self = this;

		if( selected ){
            self.model.selected_cell = true;
		}

		self.cellAddClasses();
        self.cellHandleDropFail();

        DDLayout.views.abstract.ElementView.prototype.render.call(self);

		self._makeCellResizable();
		self._initializeElementTooltip();

		return self;
	},
	cellHandleDropFail:function (  ) {
	    var self = this;
        if (self.model.get('drop-failed')) {
            _.delay(function () {
                self.$el.addClass('js-drop-failed');
                var data = self.model.get('drop-failed');
                self.$el.data('drop-failed-drop-width', data.width);
                self.$el.data('drop-failed-drop-row-divider', data.row_divider);
                self.$el.data('drop-failed-target-row-divider', self.model.get('row_divider'));
                self.model.unset('drop-failed', {silent:true});
            }, 200);
        }
    },
	cellAddClasses : function (  ) {
        var self = this;

        if ( !self.model.isEmpty() ) {
            self.defaultCssClass = '';
            self.$el.removeClass('placeholder');
        } else {
            self.defaultCssClass = 'placeholder';
        }

        self.$el.addClass( 'cell' + (self.model.get('width') * self.model.get('row_divider')) );

        if( self.model.get('cell_type') === 'cell-text'){
            self.$el.addClass('clearfix');
        }

        if ( self.model.selected_cell && self.model instanceof DDLayout.models.cells.Container === false ) {
            self.$el.addClass('selected');
        }

        self.$el.addClass('cell-' + self.model.get('cell_type'));

        return self;
    },
	/*
	 ** @access:private
	 */
	_manageCellSelection:function(event)
	{
		var self = this, change = self.$el;
		
		event.stopPropagation();

		self.eventDispatcher.trigger('clear_drop_failed', self);
		
		//if the title is edited in place don't do nothing
		if( DDLayout.ddl_admin_page.is_in_editable_state ) return true;

		var left_click = false;
		if (event.which) left_click = (event.which == 1);
		else if (event.button) left_click = (event.button == 1);

		if (left_click) {
			if ( !DDLayout.ddl_admin_page.handle_add_cell_click(self) ) {

				if( self.model.selected_cell === false )
				{
					if (!jQuery(change).hasClass('placeholder')) {
						change.addClass('selected');

						self.model.selected_cell = true;

						jQuery('.not-allowed').removeClass('not-allowed');

					//		self.eventDispatcher.trigger( "deselected_element", self);
						self.eventDispatcher.trigger('cell_selection_changed', self);

					} else {
						// Clicking on an empty cell to add a cell type.
						event.stopImmediatePropagation();
					}
				}
			}
		}
	},
	cellOpenCreateDialog:function ()
	{
		var self = this;

		self.eventDispatcher.trigger('cell_selection_changed', self);

		DDLayout.ddl_admin_page.set_new_target_cell(self);

		jQuery.colorbox({
			href: '#wrapper-element-box-type',
			inline: true,
			open: true,
			closeButton:false,
            escKey:false,
			onOpen:function(){
                DDLayout.ddl_admin_page.is_creation_dialog_box = true;
				DDLayout.ddl_admin_page.instance_layout_view.model.creating_cell = true;
			},
            onLoad:function(){
                
            },
			onComplete: function() {
				jQuery(document).trigger('focus_search_input');

			},
			onCleanup: function() {
				jQuery(document).trigger('focus_search_input');
                DDLayout.ddl_admin_page.instance_layout_view.model.creating_cell = false;
			}
		});
	},
	_collectionsManageSelection:function( cell )
	{
		var self = this, selected = cell;


		if( !_.isEqual( self.model, selected.model ) )
		{
			if (self.model.selected_cell) {
				self.model.selected_cell = false;

				self.model.trigger( "deselected_element", self.model );
			}
		}
	},
	selectElement:function() {
		var self = this;

		if( self.model.selected_cell === false )
		{
			self.model.selected_cell = true;
			self.model.trigger( "selected_element", self.model );
			self.eventDispatcher.trigger('cell_selection_changed', self);
		}
	},
	/*
	 ** @access:private
	 */
	_deselectElement:function(  )
	{
		var self = this;

		if( self.model.selected_cell === true )
		{
			self.model.selected_cell = false;
			self.model.trigger( "deselected_element", self.model );
			jQuery('.not-allowed').removeClass('not-allowed');
			jQuery(self.el).removeAttr("style")
				.height( jQuery(self.el).data( "computed_height")-2 )
				.css({marginTop:jQuery(self.el).data( "computed_margin")+"px"});

		}
		if( self.model.selected_cell == false )
		{

			self.$el.ddlWpPointer('hide');
		}
	}
	/*
	 ** @access:private
	 */
	,_manageDeselection:function( model )
	{
		var self = this, change = self.$el;
		change.removeClass('selected');
	},
	/*
	 ** @access:private
	 */
	_destroyResizableObject:function()
	{
		var self = this;

		try
		{
			jQuery(self.el).resizable('destroy');
		}
		catch( e )
		{
			console.log( e.message );
		}
	}
	,hide_cell:function( model )
	{
		var self = this,
			change = self.$el;

		self.model.suitable_to_be_removed = true;

		change.hide();
	}

	,show_cell:function( model )
	{
		var self = this,
			change = self.$el;

		self.model.suitable_to_be_removed = false;
		change.show();
	}

	,is_selected: function () {
		var self = this;
		return self.model.selected_cell;
	},
	/*
	 ** @access:private
	 */
	_makeCellResizable:function() {
		try {
			var self = this;

			if (!self.model.isEmpty()) {
				jQuery(self.el).resizable({
					ghost: true,
					minHeight: self.MIN_HEIGHT,
					maxWidth: 800,
					minWidth: self.MIN_WIDTH,
					helper: "ui-resizable-helper",
					handles: "w,e",
					containment:"document"
				});
			}
			else
			{
				//do something with our empty cell...
			}
		}
		catch (e) {
			console.error(e.message);
		}
	},
	/*
	 ** @access:private
	 */
	_resizeStart:function( event, ui )
	{
		event.stopImmediatePropagation();

		var self = this,
			row = self.get_parent_view(),
			type = 'fixed',
			step = 0,
			width = DDLayout.CELL_MIN_WIDTH,
			info_box = jQuery('<div class="cell-width-info"></div>');

		self.minWidth = self.MIN_WIDTH;

		try{
				row = self.get_parent_view();
				type = row.model.get('layout_type');


			if( type == 'fluid' ){
					var row_width = row.$el.width(),
						divider = self.model.get('row_divider'),
						real_margin = row_width / 100 * self._FLUID_CELL_MARGIN_FLOAT,
						num_cells = DDLayout.MAXIMUM_SPAN / divider;

					self.minWidth = Math.ceil( ( row_width / num_cells) - ( real_margin ) + divider );
				}

				jQuery(event.target).resizable( "option", "minWidth",  self.minWidth );

				jQuery(event.target).resizable( "option", "maxWidth", row.$el.width() );

		}
		catch(e)
		{
			console.log( e.message );
		}

		self.steps= 1;
		self.lock_resize = false;

		self.domRowCellsArray = self._getDomRowCellsArray();

		self.removeCellsOnResize = [];
		self.cached_width = [];
		self.cached_width[0] = ui.size.width;
		self.original_span = self.model.get('width');

		self.dummy_manager = new DDLayout.ResizePlaceholderManager( self );

		self.resize_direction = 'right';
		self.last_element_threshold = 0;

		info_box.text( self.model.get('width') );
		jQuery('.ui-resizable-helper').find('.ui-resizable-ghost').prepend(info_box);

		DDLayout.ddl_admin_page.take_undo_snapshot();

	},
	_resizeResize:function( event, ui )
	{
		event.stopImmediatePropagation();
		var self = this,
			w = ui.size.width,
			direction = self._resizeDirection(ui),
			index = ui.element.index(),
			next_view,
			bool = false,
			reach_end = false,
			valid = false,
			cache_last = self.cached_width.length - 1,
			backwards = false,
			temp_cell = null;


		self.resize_direction = direction;

		if(  ui.size.width > ui.originalSize.width ) {

			backwards = ui.size.width < self.cached_width[cache_last];

			if( backwards )
			{
				self.cached_width.pop();
				self.steps--;
				self.original_span = self.original_span - 1;
				jQuery('.cell-width-info', '.ui-resizable-helper').text( self.original_span );
				temp_cell = self.removeCellsOnResize.pop();
				temp_cell.$el.removeClass('ui-custom-resizable-placeholder'); // remove blue dashed border
			}

			if( direction == 'left'){

				reach_end = index - self.steps < 0 ? true : false;

				if( reach_end ){
					var width_limit = self.cached_width[cache_last] + self.last_element_threshold;
					jQuery(event.target).resizable('option', {maxWidth: width_limit });
					return true;
				}

				next_view = jQuery(self.domRowCellsArray[ index-self.steps ]).data('view');

				if( next_view )
				{

					valid = self._checkNextIsValidTarget( event, ui, next_view, direction, self.cached_width[cache_last]);

					if( valid === false ) return true;

					self.last_element_threshold = ( next_view.el.offsetWidth / 2 ) - 1;

					var check_me = Math.abs( w - self.el.offsetLeft - ui.originalSize.width),
						check_next = next_view.el.offsetLeft + ( next_view.el.offsetWidth / 2 ) - 1;
						bool =  check_me <= check_next;
				}
				else
				{
					var width_limit = self.cached_width[self.cached_width.length-1] + self.last_element_threshold;
					jQuery(event.target).resizable('option', {maxWidth: width_limit });
				}
			}

			else if( direction == 'right')
			{

				reach_end = index+self.steps > self.domRowCellsArray.length - 1 ? true : false;

				if( reach_end ){
					var width_limit = self.cached_width[self.cached_width.length-1] + self.last_element_threshold;
					jQuery(event.target).resizable('option', {maxWidth: width_limit });
					return true;
				}

				next_view = jQuery(self.domRowCellsArray[ index+self.steps ]).data('view');

				if( next_view )
				{

					valid = self._checkNextIsValidTarget( event, ui, next_view, direction, self.cached_width[cache_last] );

					if( valid === false ) return true;

					self.last_element_threshold = next_view.el.offsetWidth / 2 + 1;

					var check_me = self.el.offsetLeft + w,
						check_next = next_view.el.offsetLeft + next_view.el.offsetWidth / 2 + 1;

					bool = check_me  >= check_next;
				}

				else
				{
					var width_limit = self.cached_width[self.cached_width.length-1] + self.last_element_threshold;
					jQuery(event.target).resizable('option', {maxWidth: width_limit });

				}
			}

			if( bool === true && reach_end === false && valid === true )
			{
				self.steps++;
				self.cached_width.push(w);
				self.original_span = self.original_span + 1;
				jQuery('.cell-width-info', '.ui-resizable-helper').text( self.original_span );
				if(  _.indexOf( self.removeCellsOnResize, next_view ) === -1 ){
					self.removeCellsOnResize.push( next_view );
					next_view.$el.addClass('ui-custom-resizable-placeholder'); // set blue dashed border
				}
			}
		}
		else if( ui.size.width < ui.originalSize.width )
		{
			var delta = ui.originalSize.width - ui.size.width,
				row = self.get_parent_view(),
				step = row.$el.width() / DDLayout.MAXIMUM_SPAN,
				unit_size = 0;

				if( self.get_parent_view().model.get('layout_type') == 'fixed' )
				{
					unit_size = Math.ceil( ui.originalSize.width / ( self.model.get('width') * self.model.get('row_divider') ) )
				}
				else if( self.get_parent_view().model.get('layout_type') == 'fluid' )
				{
					unit_size = step  * self.model.get('row_divider');
				}


			backwards = ui.size.width > self.cached_width[cache_last];

			if( backwards )
			{
				self.cached_width.pop();
				self.steps--;
				self.dummy_manager.remove_dummy( self.steps );
				self.removeCellsOnResize.pop();
				self.original_span = self.original_span + 1;
				jQuery('.cell-width-info', '.ui-resizable-helper').text( self.original_span );
			}

			if( self.model instanceof DDLayout.models.cells.Container )
			{
				valid = self._checkSelfInnerCellsIfSelfContainer( self.steps, direction );

				if( !valid )
				{
					if( delta <=  1 )
					{
						jQuery(event.target).resizable('option', {minWidth: self.cached_width[self.cached_width.length-1]});
					}
					else if( delta > 1  )
					{
						jQuery(event.target).resizable('option', {minWidth: self.cached_width[self.cached_width.length-1] - unit_size / 2 });
					}
				}
			}
			else
			{
				valid = true;
			}

			var check = ( unit_size * self.steps ) - unit_size / 2;


			if( delta >= check && valid )
			{
				self.dummy_manager.create_dummy( self.steps, direction );
				self.steps++;
				self.cached_width.push( w );
				self.original_span = self.original_span - 1;
				jQuery('.cell-width-info', '.ui-resizable-helper').text( self.original_span );
			}

		}

	},
	/*
	 ** @access:private
	 */
	_resizeStop:function( event, ui )
	{
		event.stopImmediatePropagation();

		var self = this,
			new_size = self.model.get('width'),
			old_size = self.model.get('width'),
			parent_row = self.$el.parent().parent().data('view'),
			layout_type = parent_row && parent_row.model ? parent_row.model.getLayoutType() : DDLayout.ddl_admin_page.get_layout();

		jQuery('.cell-width-info', self.$el).remove();

		if( ui.size.width > ui.originalSize.width && self.removeCellsOnResize.length > 0 )
		{
			DDLayout.ddl_admin_page.add_snapshot_to_undo();

			old_size *= self.model.get('row_divider');

			self._removeCellsOnResize();

			if( layout_type == 'fixed' )
			{

				if( self.model instanceof DDLayout.models.cells.Container  )
				{
					new_size = self.model.get('width') * self.model.get('row_divider');
					self._createEmptyCellsInsideContainer( self.model, new_size - old_size );
				}

			}

		}
		else if( ui.size.width < ui.originalSize.width )
		{
			DDLayout.ddl_admin_page.add_snapshot_to_undo();
			new_size = old_size - self.steps + 1;
			var old_columns = old_size * self.model.get('row_divider');

			self._createEmptyCellsOnResize( self.resize_direction, old_size, new_size );

			self.model.set('width', new_size);

			if( layout_type == 'fixed' )
			{
				if( self.model instanceof DDLayout.models.cells.Container )
				{

					var new_columns = self.model.get('width') * self.model.get('row_divider');
					self._removeCellsOnResizeInContainer( self.resize_direction, old_columns - new_columns + 1 );
				}
			}
		}
		else
		{
			self._deselectElement();
		}

		//tell cells collection view to render
		//self.model.trigger('rerender_cells_model_view');

		//garbage collect temporary properties
		self.garbage_collector();

		self.eventDispatcher.trigger('re_render_all');
        //self.get_parent_cells_view().model.trigger('rerender_cells_model_view');
	},
	garbage_collector:function()
	{
		var self = this;
		//garbage collect temporary properties
		self.domRowCellsArray = null;
		self.removeCellsOnResize = null;
		self.domRowCellsArray = null;
		self.cached_width = null;
		self.resize_direction = null;
		self.dummy_manager = null;
		self.original_span = null;
		self.last_element_threshold = null;
	},
	_checkSelfInnerCellsIfSelfContainer:function( steps, direction )
	{
		var self = this,
			rows = self.model.get("Rows"),
			check_at_index = direction == 'right' ? rows.models[0].getWidth() - steps : steps - 1,
			filter;

		filter = _.filter(rows.models, function( item, index, list) {

			if( item.get('layout_type') == 'fluid' ) return false;

			var cells = item.get("Cells").models, position = 0;

			for (var i = 0; i < cells.length; i++) {
				var width = cells[i].get('width')
				if (direction == 'right' && position + width > check_at_index) {
					if (!cells[i].isEmpty()) {
						cells[i].trigger( "display_this_is_not_suitable_position", direction );
						return true;
					}
				}
				else if (direction == 'left' && position >= check_at_index) {
					if (!cells[i].isEmpty()) {
						cells[i].trigger( "display_this_is_not_suitable_position", direction );
						return true;
					}
					break;
				}

				position += width;
			}
			return false;
		});

		return filter.length === 0;
	},
	_thisIsNotAllowed:function( direction )
	{
		var dir = direction == 'left' ? 'right' : 'left';
		this.$el.addClass( 'not-allowed-'+dir );
	},
	_checkNextIsValidTarget: function (event, ui, next, direction, last_width) {

		var self = this,
			next_model = next.model;

		if ( next_model && !next_model.isEmpty() && ui.size.width > ui.originalSize.width && self.lock_resize === false ) {
			self.lock_resize = true;
			next.$el.addClass('not-allowed-'+direction);
			jQuery(event.target).resizable('option', {maxWidth: last_width + self.last_element_threshold });
			return false;
		}

		return true;
	},
	_getDomRowCellsArray:function()
	{
		var self = this,
			parent = self.$el.parent(),
			cells = jQuery('> div', parent);
		return jQuery.makeArray( cells );
	},
	_resizeDirection:function(ui)
	{
		return ui.position.left != ui.originalPosition.left ? 'left' : 'right';
	},
	_removeCellsOnResize:function()
	{
		var self = this, temp_cells = [];
		var total_columns = self.model.getIntWidth() * self.model.get('row_divider');
		var min_row_divider = self.model.get('row_divider');

		_.each(self.removeCellsOnResize, function(item, index, list){
			total_columns += item.model.getIntWidth() * item.model.get('row_divider');
			min_row_divider = Math.min(min_row_divider, item.model.get('row_divider'));
			temp_cells.push( item.model );
		});

		self.model.collection.reset( _.difference( self.model.collection.models, temp_cells ) );

		var new_size = total_columns / min_row_divider;
		self.model.set('width', new_size);
		self.model.set('row_divider', min_row_divider);

	},
	_removeCellsOnResizeInContainer:function( direction, steps )
	{
		var self = this,
			rows = self.model.get("Rows"),
			cells,
			len;

		_.map( rows.models, function( item, index, list){
			if( item.getLayoutType() == 'fixed' )
			{
				cells = item.get('Cells'),  len = cells.models.length;

				if( direction == 'right' )
				{
					cells.reset( _.difference( cells.models, cells.models.slice( len-steps+1, len ) )  );
				}
				else if( direction == 'left' )
				{
					cells.reset( _.difference( cells.models, cells.models.slice( 0, steps-1 ) )  );
				}
			}
		});
	}
	,_createEmptyCellsOnResize:function( direction, old_size, new_size )
	{
		var self = this,
			index = self.model.collection.indexOf( self.model ),
			diff = old_size - new_size,
			row_divider = self.model.get('row_divider');

		for(var i = 0; i < diff; i++)
		{
			var append_at, spacer;

			if( direction == 'left')
			{
				append_at = index;
				spacer = new DDLayout.models.cells.Cell({
                    'name' : 'spacer:' + append_at,
					'cell_type' : 'spacer',
					'row_divider' : row_divider
                });

				self.model.collection.add(spacer, {at:append_at, merge:false});
				index++;
			}
			if( direction == 'right' )
			{
				append_at = index+i+1;
				spacer = new DDLayout.models.cells.Cell({
                    'name' : 'spacer:' + append_at,
					'cell_type' : 'spacer',
					'row_divider' : row_divider
                });

				self.model.collection.add(spacer, {at:append_at, merge:false});
			}
		}
	},
	_createEmptyCellsInsideContainer:function( model, new_size )
	{
		var self = this, cells;

		_.each(  model.get("Rows").models, function( item, index, list )
		{

			if( item.getLayoutType() == 'fixed' )
			{
				var push = [];

				for( var i = 0; i < new_size; i++ )
				{
					var spacer = new DDLayout.models.cells.Cell({
                        'name' : 'spacer:' + (index+1+i),
						'cell_type' : 'spacer'
                    });

					spacer.layout = model.layout;
					push.push( spacer );
				}

				cells = item.get('Cells');

				if( self.resize_direction == 'right' )
				{
					cells.reset( _.union( cells.models, push ), {merge: false} );
				}
				else if( self.resize_direction == 'left' )
				{
					cells.reset( _.union( push, cells.models ), {merge: false} );
				}
			}
		});
	},

	_manageMouseEnter: function (event) {
        event.stopPropagation();
		var self = this, parent_row = self.get_parent_view();

		if (!DDLayout.ddl_admin_page.handle_cell_enter(self)) {

			if (self.defaultCssClass === 'placeholder' && self.$el.hasClass('placeholder')) {
				self.$el.children().hide();
				self.$el.append('<i class="icon-plus fa fa-plus"></i>');
			}

			if (!self.$el.hasClass('placeholder')) {

				// Add edit icon
				var pencil_tooltip_text = DDLayout_settings.DDL_JS.strings.edit_cell;


				if ( self.model.get('cell_type') == 'ddl_missing_cell_type' && self.model.get('ddl_missing_cell_type') == 'undefined' ) {
					pencil_tooltip_text = DDLayout_settings.DDL_JS.strings.set_cell_type;
				}

				if ( self.model.get('ddl_missing_cell_type') == 'undefined' || 'ddl_missing_cell_type' != self.model.get('cell_type')  ) {

					self.$el.append('<i class="icon-pencil fa fa-pencil edit-icon-additional js-edit-cell" data-tooltip-text="' + pencil_tooltip_text + '"></i>');
					self.$el.append('<i class="icon-pencil fa fa-pencil edit-icon-main js-edit-cell" data-tooltip-text="' + pencil_tooltip_text + '"></i>');
				}
				
				self.$el.append('<i class="fa fa-remove icon-remove js-delete-cell" data-tooltip-text="' + DDLayout_settings.DDL_JS.strings.remove_cell + '"></i>');
				self.$el.append('<i class="fa fa-arrows icon-move"></i>');



				var remove_icon = self.$el.find('.js-delete-cell');

				remove_icon.on('click', function(event) {
					event.stopImmediatePropagation();
					self.$el.addClass('selected');
					self.model.selected_cell = true;
					self.eventDispatcher.trigger('cell_selection_changed', self);
					self.eventDispatcher.trigger( 'ddl-remove-cell', self, jQuery(this), 'remove' );
                    self.highlight_destroy(event);
				});

				if ( self.model.get('ddl_missing_cell_type') == 'undefined' || 'ddl_missing_cell_type' != self.model.get('cell_type') ) {
					var pencil_icon = self.$el.find('.js-edit-cell');
	
					pencil_icon.on('click', function(event) {
						event.stopImmediatePropagation();
                        self.highlight_destroy(event);
						self.eventDispatcher.trigger('clear_drop_failed', self);
						
						self.selectElement();
						self.$el.addClass('selected');
	
						var cell_type = self.model.get('cell_type');
						if ( self.model.get('ddl_missing_cell_type') == 'undefined' && 'ddl_missing_cell_type' == self.model.get('cell_type') ) {
						
							DDLayout.ddl_admin_page.show_create_new_cell_dialog(self, self.model.get('width'));
						} else {
						
							DDLayout.ddl_admin_page.show_default_dialog('edit', self);
						}
					});
	
					pencil_icon.on('mouseenter', function(event) {
                        self.highlight_init(event);
						var $this = jQuery(this);
						self.$el.on('mousedown', preventMouseDown);
						jQuery('.icon-move', self.$el).hide();
						jQuery('.js-delete-cell', self.$el).hide();
						jQuery('.js-element-info', self.$el).hide();
	
					});
					pencil_icon.on('mouseleave', function(event) {
                        self.highlight_destroy();
						self.$el.off('mousedown', preventMouseDown);
						jQuery('.icon-move', self.$el).show();
						jQuery('.js-delete-cell', self.$el).show();
						jQuery('.js-element-info', self.$el).show();
					});


                    remove_icon.on('mouseenter', function(event) {
                        self.highlight_init(event);
                    });
                    remove_icon.on('mouseleave', function(event) {
                        self.highlight_destroy();
                    });
				}
				
				// Display Tooltips

                self.init_tooltip();

				var preventMouseDown = function( event )
				{
					event.stopImmediatePropagation();
				};

			}

		}
	},

    init_tooltip:function(){
        if( this._prevent_operations ) return;
        var self = this, pencil_icon = self.$el.find('.js-edit-cell'), remove_icon = self.$el.find('.js-delete-cell');
     //   pencil_icon.data('tooltip-text', 'Edit '+self.model.get('kind')).toolsetTooltip();
		pencil_icon.toolsetTooltip();
        jQuery.each( [remove_icon], function() {
            var icon = jQuery(this);
            icon.data('tooltip-text', 'Remove '+self.model.get('kind')).toolsetTooltip();
        });

    },

	_manageMouseLeave: function (event) {
        event.stopPropagation();
		var self = this;

		self.$el.children().show();
		self.$el.find('.icon-plus').remove();

		if (!self.$el.hasClass('placeholder')) {
			self.$el.find('.icon-remove').remove();
		}

		var pencil_icon = self.$el.find('.js-edit-cell');
		pencil_icon.off('click');
		self.$el.find('.icon-pencil').remove();
		self.$el.find('.icon-move').remove();

	},
	_move_selected_cell_left: function ( event ) {
		var self = this, index = self.model.collection.indexOf( self.model ), collection = self.model.collection;

		if( !self.model.selected_cell || index <= 0  ) return false;

		if (index > 0) {
			DDLayout.ddl_admin_page.save_undo();

			var current = self.model;
			var previous = self.model.collection.at(index-1);
			collection.remove(current);
			collection.remove(previous);
			collection.add(current, {at:index-1});
			collection.add(previous, {at:index});
			//self.model.trigger('rerender_cells_model_view', {current:current} );

			self.eventDispatcher.trigger('re_render_all', {current:current} );
		}
		return true;
	},
	_move_selected_cell_right: function () {
		var self = this, index = self.model.collection.indexOf( self.model ), collection = self.model.collection;

		if( !self.model.selected_cell || index >= collection.length - 1 ) return false;

		if (index != -1 && index < collection.length - 1) {
			DDLayout.ddl_admin_page.save_undo();

			var current = self.model;
			var next = collection.at(index+1);
			collection.remove(current);
			collection.remove(next);
			collection.add(next, {at:index});
			collection.add(current, {at:index+1});
			//self.model.trigger( 'rerender_cells_model_view', {current:current} );
			self.eventDispatcher.trigger('re_render_all', {current:current} );
		}

		return true;
	},
	get_cell_top : function () {
		return this.$el.offset().top;
	},
	add_class : function (class_name) {
		this.$el.addClass(class_name);
	},
	remove_class : function (class_name) {
		this.$el.removeClass(class_name);
	},
    // implementation for cells
    appendMarkupIcon: function(){
        var self = this;
        self.$el
            .append( self.buildElementTooltip() );
    }

});

DDLayout.ResizePlaceholder = function( target, step, direction )
{
	var self = this;

	self.dummy = jQuery('<div class="ui-custom-resizable-placeholder" />');

	self.target = target;
	self.step = step - 1;

	self.set_dummy_placeholder = function()
	{
		var height = self.target.get_parent_cells_view()._cells_max_height,
			margin_top = self.target instanceof DDLayout.views.ContainerView ? self.target.get_parent_cells_view()._cells_margin_top : 0,
			row = self.target.get_parent_view(),
			type = row.model.get('layout_type'),
			width = target.minWidth,
			step = width  + DDLayout.MARGIN_BETWEEN_CELLS;


		if( type == 'fluid' )
		{
			var real_margin = row.el.offsetWidth / 100 * target._FLUID_CELL_MARGIN_FLOAT,
				divider = target.model.get('row_divider');

			step = ( row.el.offsetWidth * divider / DDLayout.MAXIMUM_SPAN ) + ( real_margin / DDLayout.MAXIMUM_SPAN );

		}

		if( direction == 'left' )
		{
			self.dummy.css({
				position:'absolute',
				height:height,
				width:width,
				left:  self.step * step,
				top:margin_top
			});
		}
		else if( direction == 'right' )
		{
			self.dummy.css({
				position:'absolute',
				height:height,
				width:width,
				right:  self.step * step,
				top:margin_top
			});
		}


		self.target.$el.append( self.dummy );
	};

	self.remove_dummy_placeholder = function()
	{
		self.dummy.remove();
	};

};

DDLayout.ResizePlaceholderManager = function ( target )
{
	var self = this, dummies = [];

	self.target = target;

	self.target.$el.css({
		position:"relative"
	});

	self.create_dummy = function( step, direction )
	{
		dummies[step] = new DDLayout.ResizePlaceholder( self.target, step, direction );
		dummies[step].set_dummy_placeholder();
	};

	self.remove_dummy = function( step )
	{
		if( dummies[step] ) dummies[step].remove_dummy_placeholder();
	};
};

function ddl_html_encode(mystring) {
	return mystring.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
}

