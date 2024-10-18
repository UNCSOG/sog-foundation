// CellDropPlaceholder.js

DDLayout.CellDropPlaceholder = function($)
{
    var self = this;

    self._visible = false;
    self._target_row = null;
    self._placeholder_element = null;
    self._source_item = null;
    self._best_index = -1;
    self._source_width = 0;
    self._helper = null;
	self._drop_x = 0;

    self.init = function() {
        self._visible = false;
        self._target_row = false;
        self._placeholder_element = null;
        self._source_item = null;
        self._best_index = -1;
        self._source_width = 0;
        self._helper = null;
	    self._sender = null;
    };

    self.destroy = function() {
        if (self._target_row) {
            jQuery(document).off('mousemove.ddl');
        }
        if (self._placeholder_element) {
            self._placeholder_element.remove();
        }
    };

    self.set_target_row = function(target_row, source_item, helper, has_good_index) {
        if ( self._target_row != target_row ) {
            self.hide();
        }

        self._target_row = target_row;
	    self._has_good_index = has_good_index;

        self._source_item = source_item;
        var view = jQuery(self._source_item).data('view');
        self._source_width = view.$el.width();
        
        self._helper = jQuery(helper);
        

        self._target_cells = self._target_row.get_cells_for_dropping();
        self._best_index = -1;

        jQuery(document).on('mousemove.ddl', self.update);
    };

    self.update = function(event) {
        if (self._target_row) {

            if (!self._placeholder_element) {
                jQuery('body').append('<div class="ui-custom-drop-placeholder"></div>');
                self._placeholder_element = jQuery('.ui-custom-drop-placeholder');
                self._placeholder_element.css({zIndex : 999});
                
            }

            var view = jQuery(self._source_item).data('view');
            var cell_width = view.model.get('width');

            // Use the center of the place holder for calculations.
            self._drop_x = self._helper.offset().left + self._source_width / 2;
            
            var closest = self.get_closest_empty_position(self._drop_x, cell_width);

            if ( closest['index'] >= 0 && self._has_good_index === true ) {

                self._placeholder_element.css({
                                                left : closest['min_x'],
                                                top : closest['min_y'],
                                                width : closest['max_x'] - closest['min_x'],
                                                height : closest['max_y'] - closest['min_y']
                                                });

                self.show();
            } else {
                self.hide();
            }

            self._best_index = closest['index'];

        }

    };

    self.get_closest_empty_position = function (x, cell_width) {

        var best_distance = 10000000;
        var best_cell = {min_x:0, max_x:0, min_y:0, max_y:0, index:-1};

        for(var i=0; i<self._target_cells.length;i++) {
            if (self._target_cells[i]['empty']) {
                var found_spaces = 0;
                var min_x = self._target_cells[i]['left'];
                for(var test = i; test < self._target_cells.length; test++) {

                    if ( self._target_cells[test]['empty'] ) {
                        found_spaces++;
                        if (found_spaces >= cell_width) {
                            var max_x = self._target_cells[test]['left'] + self._target_cells[test]['width'];
                            var distance = Math.abs((max_x + min_x) / 2 - x);

                            if (distance < 100 && distance < best_distance ) {
                                best_distance = distance;
                                best_cell['min_x'] = min_x;
                                best_cell['max_x'] = max_x;
                                best_cell['min_y'] = self._target_cells[test]['top'];
                                best_cell['max_y'] = best_cell['min_y'] + self._target_cells[test]['height'];
                                best_cell['index'] = i;
                            }
                            break;
                        }
                    } else {
                        break;
                    }
                }
            }

        }
        return best_cell;
    };

    self.get_drop_index = function () {
        return self._best_index;
    };

    self.set_drop_index = function( index )
    {
        self._best_index = index;
    }

    self.hide = function () {
        if (self._placeholder_element) {
            self._placeholder_element.hide();
            self._visible = false;
        }
    };

    self.show = function () {
        if (self._placeholder_element) {
            self._placeholder_element.show();
            self._visible = true;
        }
    };
	
	self.add_no_drop_message = function (width, row_divider) {
        var best_distance = 10000000;
		var best_cell = null
		var best_index = -1;

		// Find the closest empty cell to the drop point.
        for(var i = 0; i < self._target_cells.length; i++) {
            if (self._target_cells[i]['empty']) {
                var min_x = self._target_cells[i]['left'];
                var max_x = self._target_cells[i]['left'] + self._target_cells[i]['width'];
                var distance = Math.abs((max_x + min_x) / 2 - self._drop_x);

				if (distance < 100 && distance < best_distance ) {
					best_distance = distance;
					best_cell = self._target_cells[i];
					best_index = i;
				}
            }

        }
        
		if (best_index >= 0) {
			// Mark the cell as a drop-failed point.
			// Cells will be highlighed when we redraw.
			
			var data = {width : width, row_divider : row_divider};
	        self._target_cells[best_index].element.set('drop-failed', data, {silent:true});
			
			// Mark the empty cells before in the same way.
			for (i = best_index - 1; i >= 0; i--) {
				if (self._target_cells[i]['empty']) {
			        self._target_cells[i].element.set('drop-failed', data, {silent:true});
				} else {
					break;
				}
			}

			// Mark the empty cells after in the same way.
	        for(i = best_index + 1; i < self._target_cells.length; i++) {
				if (self._target_cells[i]['empty']) {
			        self._target_cells[i].element.set('drop-failed', data, {silent:true});
				} else {
					break;
				}
			}
		}
	}

    self.init();
};

