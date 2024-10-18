// AddCellHandler.js

DDLayout.AddCellHandler = function($)
{
	var self = this;

	self.init = function()
	{
		self._first_cell = null;
		self._columns_to_add = 1;
		self._current_columns = 0;
	};

	self.handle_click = function (cell_view) {
		if (jQuery(cell_view.$el).hasClass('placeholder')) {

			jQuery('.placeholder').each(function() {
				jQuery(this).removeClass('placeholder');
				jQuery(this).addClass('disabled-placeholder');
			});
			cell_view.add_class('add-cell-first');

			if (self._highlight_potential_targets(cell_view)) {

				self._first_cell = cell_view;

				self._initialize_events();
				self._add_place_holder();

				self._current_columns = 1;
				setTimeout(self._flash_message, 1000);
			}

			return true;
		}

		if (jQuery(cell_view.$el).hasClass('add-cell-first')) {
			self._cancel();
			self.show_create_new_cell_dialog(cell_view, 1);
			return true;
		}

		if (jQuery(cell_view.$el).hasClass('add-target-cell')) {
			var columns = self._get_range(cell_view);
			if (columns > 0) {
				cell_view = self._first_cell;
			}
			self._cancel();
			self.show_create_new_cell_dialog(cell_view, Math.abs(columns) + 1);
			return true;
		}

		//var cell_type = cell_view.model.get('cell_type');
		//if (cell_type == 'undefined') {
		//	jQuery(cell_view.$el).addClass('selected');
		//	cell_view.model.selected_cell = true;
		//
		//	self.show_create_new_cell_dialog(cell_view, cell_view.model.get('width'));
		//	return true;
		//}

		return self._first_cell !== null;
	};

    self.show_create_new_cell_dialog = function (cell_view, columns) {
		self.setColumnsToAdd( columns );

		cell_view.cellOpenCreateDialog();
    }

	self._add_place_holder = function () {
		var offset = self._first_cell.$el.offset();

		jQuery('body').append('<div class="add-cell-placeholder"></div>');

		jQuery('.add-cell-placeholder').css({position : 'absolute',
												left: offset.left,
												top: offset.top,
												height : self._first_cell.$el.height(),
												width : self._first_cell.$el.width(),
												cursor : 'col-resize',
												});

		jQuery('.add-cell-placeholder').on('mousemove', function(event) {
			// find the under the placeholder cell
			jQuery('.add-target-cell,.add-cell-first').each(function () {
				var offset = jQuery(this).offset();
				if (event.pageX >= offset.left && event.pageX <= offset.left + jQuery(this).width()) {
					self._size_place_holder(jQuery(this));
				}
			});
		});

		jQuery('.add-cell-placeholder').on('click', function(event) {
			// find the under the placeholder cell
			jQuery('.add-target-cell,.add-cell-first').each(function () {
				var cell_offset = jQuery(this).offset();
				var x = event.pageX;
				var width = jQuery(this).width();
				if ((x >= cell_offset.left) && (x <= cell_offset.left + width)) {
					jQuery(this).trigger(event);
				} else {
					var test = 1;
				}
			});
		});

	};

	self._size_place_holder = function (current_cell) {
		var first_offset = self._first_cell.$el.offset();
		var offset = current_cell.offset();

		if (first_offset.left < offset.left) {

			jQuery('.add-cell-placeholder').css({left: first_offset.left,
													width : offset.left + current_cell.width() - first_offset.left
													});

		} else if (first_offset.left > offset.left) {
			jQuery('.add-cell-placeholder').css({left: offset.left,
													width : first_offset.left + self._first_cell.$el.width() - offset.left
													});

		} else {
			jQuery('.add-cell-placeholder').css({left: first_offset.left,
													width : self._first_cell.$el.width()
													});

		}

		var columns = Math.abs(self._get_range(current_cell.data('view'))) + 1;

		if (columns != self._current_columns) {
			
			self._current_columns = columns;
			
			self._set_message(columns);
			
		}
	};
	
	self._set_message = function (columns) {
		
		var message_element = jQuery('#add-cell-overlay-message');
		var message_width = message_element.width();
			
		if (columns == 1) {
			if (message_width < 313) {
				message_element.html(DDLayout_settings.DDL_JS.strings.select_range_one_column_short);
			} else {
				message_element.html(DDLayout_settings.DDL_JS.strings.select_range_one_column);
			}
			self._flash_message();
		} else {
			if (message_width < 247) {
				message_element.html(DDLayout_settings.DDL_JS.strings.select_range_more_columns_short.replace('%d', columns));
			} else {
				message_element.html(DDLayout_settings.DDL_JS.strings.select_range_more_columns.replace('%d', columns));
			}
			message_element.show();
		}
	}

	self._flash_message = function () {
		
		var message_element = jQuery('#add-cell-overlay-message');
		
		if (self._current_columns == 1) {
			if (message_element.is(':visible')) {
				message_element.fadeOut(400, function () {
					if (self._current_columns == 1) {
						self._flash_message();
					} else {
						message_element.show();
					}
				});	
			} else {
				message_element.fadeIn(400, function () {
				});	
			}
		} else {
			message_element.show();
		}
	};
	
	self.setColumnsToAdd = function( columns )
	{
		self._columns_to_add = columns;
	};

	self.getColumnsToAdd = function( )
	{
		return Toolset.hooks.applyFilters('ddl-container_columns_to_add', self._columns_to_add );
	};

	self._initialize_events = function () {
		jQuery('.add-cell-first,.add-target-cell').on('mousemove', function (event) {

			self._size_place_holder(jQuery(this));

		});
	};

	self._highlight_potential_targets = function (cell_view) {
		var row_view = cell_view.get_parent_view();
		var cells = row_view.cells.elements;

		var index = -1;
		var total_targets_available = 1;
		var top, bottom, left, right;
		// find the cell
		for (var i = 0; i < cells.length; i ++) {
			if (cells[i] == cell_view) {
				index = i;

				var offset = cell_view.$el.offset();
				top = offset.top;
				left = offset.left;
				bottom = top + cell_view.$el.height();
				right = left + cell_view.$el.width();
				break;
			}
		}

		// mark previous cells
		for (i = index - 1; i >=0; i--) {
			if (cells[i].$el.hasClass('disabled-placeholder')) {
				cells[i].$el.removeClass('disabled-placeholder');
				cells[i].$el.addClass('add-target-cell');
				left = cells[i].$el.offset().left;
				total_targets_available++;
			} else {
				break;
			}
		}

		// mark next cells
		for (i = index + 1; i < cells.length; i++) {
			if (cells[i].$el.hasClass('disabled-placeholder')) {
				cells[i].$el.removeClass('disabled-placeholder');
				cells[i].$el.addClass('add-target-cell');
				right = cells[i].$el.offset().left + cells[i].$el.width();
				total_targets_available++;
			} else {
				break;
			}
		}

		if (total_targets_available == 1) {
			// only one cell available so open popup directly
			self._cancel();
			self.show_create_new_cell_dialog(cell_view, 1);
			return false;
		} else {
			self._add_overlay(left, top, right, bottom);
			return true;
		}

	};

	self._get_range = function (cell_view_end) {
		var row_view = cell_view_end.get_parent_view();
		var cells = row_view.cells.elements;

		var index_start = -1;
		// find the cell
		for (var i = 0; i < cells.length; i ++) {
			if (cells[i] == self._first_cell) {
				index_start = i;
				break;
			}
		}

		var index_end = -1;
		// find the cell
		for (var i = 0; i < cells.length; i ++) {
			if (cells[i] == cell_view_end) {
				index_end = i;
				break;
			}
		}

		return index_end - index_start;
	};

	self._add_overlay = function (left, top, right, bottom) {
		jQuery('body').append('<div id="add-cell-overlay-above" class="add-cell-overlay"></div>');
		jQuery('#add-cell-overlay-above').css({
			left:'0',
			top: '0',
			height : top - 10,
			width : jQuery(document).width()
		});

		jQuery('body').append('<div id="add-cell-overlay-below" class="add-cell-overlay"></div>');
		jQuery('#add-cell-overlay-below').css({
			left:'0',
			top: bottom + 10,
			height : jQuery(document).height() - (bottom + 10),
			width : jQuery(document).width()
		});

		jQuery('body').append('<div id="add-cell-overlay-left" class="add-cell-overlay"></div>');
		jQuery('#add-cell-overlay-left').css({
			left:'0',
			top: top - 10,
			height : bottom - top + 20,
			width : left - 10
		});

		jQuery('body').append('<div id="add-cell-overlay-right" class="add-cell-overlay"></div>');
		jQuery('#add-cell-overlay-right').css({
			left: right + 10,
			top: top - 10,
			height : bottom - top + 20,
			width : jQuery(document).width - right - 10
		});

		var message_area_height = 30;
		
		jQuery('body').append('<div id="add-cell-overlay-message" class="add-cell-overlay-message"></div>');
		jQuery('#add-cell-overlay-message').css({
			left:left,
			top: top - message_area_height + 10,
			height : message_area_height,
			width : right - left,
		});

		jQuery('.add-cell-overlay, .add-cell-overlay-message').on('click', function(event) {
			self._cancel();
		});
		
		self._set_message(1);

	};

	self._cancel = function() {
		jQuery('.add-cell-first,.add-target-cell').off('mousemove');

		jQuery('.add-cell-overlay,.add-cell-overlay-message,.add-cell-placeholder').remove();

		jQuery('.disabled-placeholder,.add-cell-first,.add-target-cell').each( function() {
			jQuery(this).removeClass('disabled-placeholder add-cell-first add-target-cell').addClass('placeholder');
		});

		self._first_cell = null;

	};

	self.handle_enter = function (cell_view) {
		if (cell_view.$el.hasClass('add-target-cell')) {
			cell_view.$el.children().hide();
			cell_view.$el.append('<i class="icon-plus fa fa-plus"></i>');
			return true;
		} else {
			return false;
		}
	};

	self.init();
};
