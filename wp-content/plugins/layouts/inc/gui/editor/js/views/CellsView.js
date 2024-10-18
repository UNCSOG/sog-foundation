DDLayout.views.CellsView = DDLayout.views.abstract.CollectionView.extend({
    events: {
        'csortstart': '_handleSortStart',
        'csortout': '_handleSortOut',
        'csortchange': '_handleSortChange',
        'csortover': '_handleSortOver',
        'csortreceive': '_handleSortReceive',
        'csortstop': '_handleSortStop'
    },
    _is_resorting_itself: true,
    _dummy_view: null,
    _dummy_model: null,
    _cells_max_height: 50,
    _cells_margin_top: 0,
    _self_fix_my_first: false,
    _FLUID_CELL_MARGIN: '2.0618556701030926%',
    z: 0,
    initialize: function (options) {
        "use strict";
        var self = this;

        //call parent constructor
        self.options = options;

        DDLayout.views.abstract.CollectionView.prototype.initialize.call(this, options);

        _.bindAll(self, 'beforeRender', 'render', 'afterRender');

        self.render = _.wrap(self.render, function (render, args) {
            self.beforeRender(args);
            render(args);
            _.defer(_.bind(self.afterRender, self), args);
            return self;
        });

        self.$el.addClass('cells');

        self.listenTo(self.model, 'rerender_cells_model_view', self.render);

        self.listenTo(self.eventDispatcher, 'replace_selected_cell', self._replace_selected_cell);
        self.listenTo(self.eventDispatcher, 'delete_selected_cell', self._delete_selected_cell);

        return self;
    },
    beforeRender: function (option) {
        //console.log('cells before', arguments );
    },
    render: function (option) {
        //  console.log('cells render', arguments );
        var self = this, options = _.extend({}, option);

        DDLayout.views.abstract.CollectionView.prototype.render.call(self, options);

        self._cells_max_height = 50;
        self._cells_margin_top = 0;

        if (self._is_in_main_row()) {

            self.$el.css('visibility', 'hidden');

            self.$el.imagesLoaded()
                .always(function (instance) {

                    self._setCellsHeightForPreview();

                    if (DDLayout.ddl_admin_page === undefined) {
                        var debounce = _.debounce(_.bind(self.setCellsHeight, self), 600, false);
                        debounce();
                    }
                    else {
                       // _.defer(_.bind(self.setCellsHeight, self));
                        self.setCellsHeight();
                    }
                })
        }

        return this;
    },
    afterRender: function () {
        var self = this;
        jQuery(self.el).data( 'view', self );
    },
    _is_in_main_row: function () {
        var self = this;
        var row_view = self.get_parent_view();
        //	console.log( row_view );
        return row_view ? row_view.is_top_level_row() : false;
    },
    get_parent_view: function (event) {
        // get the row view.
        //console.log( this, this.$el.parent(), this.$el.parent().data() );
        return this.$el.parent().data('view');
    },

    get_cells_top: function () {
        var self = this;

        var top = 0;
        for (var i = 0; i < self.getElementCount(); i++) {
            var cell_view = self.getElementView(i);
            var cell_top = cell_view.get_cell_top();
            if (cell_top > top) {
                top = cell_top;
            }
        }

        return top;
    },

    get_cell_rows: function () {
        var self = this;

        var count = 1;
        for (var i = 0; i < self.getElementCount(); i++) {
            var cell_view = self.getElementView(i);
            if ( cell_view.model.hasRows() ) {
                var rows_in_container = cell_view.get_total_rows();
                if (rows_in_container > count) {
                    count = rows_in_container;
                }
            }
        }

        return count;

    },

    setCellsHeight: function () {
        var self = this;

        if ( typeof self.model.where('Rows', Backbone.Collection ) === 'undefined'  ) {
            self.$el.css('visibility', 'visible');
            return;
        }

        // find the top row and bottom of all containers.
        var containers = Array(),
            element_len = self.getElementCount();

        for (var i = 0; i < element_len; i++) {
            var cell_view = self.getElementView(i);
            if ( cell_view.model.hasRows() ) {
                var details = {};
                details['view'] = cell_view;
                details['pos'] = cell_view.get_top_and_bottom_cell_positions();
                containers.push(details);
            } else {
                var details = {};
                details['view'] = cell_view;
                details['pos'] = 'nopos';
                containers.push(details);
            }
        }

        // Find the lowest top row (max y) and the max height.
        var top = 0, height = 0, special_height = 0;


        for (var i = 0; i < containers.length; i++) {
            if (typeof containers[i]['pos'].top !== 'undefined') {
                if (containers[i]['pos'].top > top) {
                    top = containers[i]['pos'].top;
                }
            }
            if (containers[i]['pos'] != 'nopos') {
                var cell_height = containers[i]['pos'].bottom - containers[i]['pos'].top;
                if (cell_height > height) {
                    height = cell_height;
                }
                if (cell_height > special_height) {
                    special_height = cell_height;
                }

            }
            else {
                var cell_height = containers[i]['view'].computed_height;
                if (cell_height > height) {
                    height = cell_height;
                }
            }
        }


        var margin_top = top - self.$el.offset().top;

        // Set all direct child cells that are not containers.

        jQuery('> div.cell:not(.container)', self.el).map(function (n, e) {
            jQuery(this).height(height).css({marginTop: margin_top + "px"});
            jQuery(this).data("computed_height", height);
            jQuery(this).data("computed_margin", margin_top);
            jQuery(this).data('view').computed_height = height;

        });

        self._cells_max_height = Math.max(height, self._cells_max_height);
        self._cells_margin_top = margin_top;

        // adjust all the containers

        for (var i = 0; i < containers.length; i++) {
            /*
             ** apply the adjustment only on the Container Object
             */
            if (containers[i]['pos'] != 'aaa' && containers[i]['view'] instanceof DDLayout.views.ContainerView) {
                /*
                 ** anyway if something's wrong make sure the Type error is tracked but does not bother
                 */
                try {
                    containers[i]['view'].adjust_position_and_height(top, special_height);
                } catch (e) {
                    console.log(e.message, containers[i]);
                }

            }
        }

        // Show the row after calculating.
        self.$el.css('visibility', 'visible');
    },

    _setCellsHeightForPreview: function () {
        var self = this;

        var max_height = 0;

        for (var i = 0; i < self.getElementCount(); i++) {
            var cell_view = self.getElementView(i);
            if (cell_view.model.hasRows() === false ) {

                var height = DDLayout.preview_manager.get_preview_height(cell_view);
                if (height != undefined) {
                    if (height > 50 && height > max_height) {
                        max_height = height;
                    }
                }
            }
        }

        if (max_height > 0) {
            for (var i = 0; i < self.getElementCount(); i++) {
                var cell_view = self.getElementView(i);
                if ( cell_view.model.hasRows() === false ) {
                    cell_view.$el.height(max_height);
                    cell_view.$el.data("computed_height", max_height);
                    cell_view.computed_height = max_height;
                    cell_view.$el.addClass('enable-pencil-icon');
                }
            }

            self._cells_max_height = max_height;
        }
    },

    _handleSortStart: function (event, ui) {
        event.stopImmediatePropagation();
        DDLayout.ddl_admin_page.take_undo_snapshot();

        var self = this,
            placeholder = jQuery(ui.placeholder[0]),
            view = jQuery(ui.item[0]).data('view');

        self.eventDispatcher.trigger('clear_drop_failed', self);

        jQuery(ui.item[0]).data('original_index', ui.item.index());

        placeholder.css({
            'visibility': 'visible',
            'height': self._cells_max_height,
            'marginTop': self._cells_margin_top
        });

        jQuery('.layout-container').addClass("is-dragged");

        DDLayout.moved_from_row = null;
        DDLayout.target_row = null;

        self._element_to_fix = null;
        self._self_fix_my_first = false;
        self._fix_placeholder_first = false;
    },
    /*
     ** @param: an instance of DDLayout.views.RowView
     ** @return: null Row do not belong to container, instance of DDLayout.models.cells.Container otherwise
     */
    _row_belongs_to_container: function (row) {
        var self = this,
            parent = row.get_parent_view();

        if (parent instanceof DDLayout.views.ContainerRowView === false) return null;

        return parent.getRowContainer();
    },
    _is_in_first_position: function (model) {
        return this.model.models.indexOf(model) === 0;
    },
    _handleSortChange: function (event, ui) {
        event.stopImmediatePropagation();
        var self = this,
            view = jQuery(ui.item[0]).data('view'),
            index = jQuery(ui.item[0]).data('original_index'),
            placeholder = jQuery(ui.placeholder[0]),
            sender = ui.sender,
            sendView = sender ? jQuery(sender).data('view') : null,
            sendModel = sendView ? sendView.model : null,
            width = view.model.get('width'),
            hide_placeholder = false;


        self._fix_first_element_on_drop_for_fluid(view, placeholder);

        if (sendView instanceof DDLayout.views.abstract.CollectionView
            && !_.isEqual(self.model, sendModel)) {
            if (sendView._dummy_model == null) {
                sendView._createDummyViewModel(index, view, ui.helper[0], sendView._cells_margin_top);
            }
            placeholder.css({height: self._cells_max_height, marginTop: self._cells_margin_top});
            DDLayout.moved_from_row = sender;
        }

        if (DDLayout.moved_from_row) {
            if (self.cid != jQuery(DDLayout.moved_from_row).data('view').cid) {
                DDLayout.target_row = self;
            }

            if (DDLayout.target_row) {

                hide_placeholder = true;

                if (self._checkIfContainerRowsAreCompatible(view, jQuery(DDLayout.moved_from_row).data('view'), DDLayout.target_row)) {
                    self._handle_drop_to_other_row(DDLayout.target_row, ui.item[0], ui.helper[0], self._is_in_position(view.model) !== -1);
                }

                self._fix_first_element_drop_on_fluid_in_other_row();
            }
        }


        if (!hide_placeholder) {

            placeholder.css({'display': 'block', 'visibility': 'visible'});
        } else {

            placeholder.css({'display': 'none'});
        }

    },
    _checkIfContainerRowsAreCompatible: function (moving_element, sender, receiver) {
        // if we're not moving a container we're fine
        if (moving_element instanceof DDLayout.views.ContainerView === false) return true;

        var moving_element_has_fixed_rows = moving_element.model.get('Rows').hasRowsOfKind('fixed').length > 0,
            receiver_parent_row = receiver.get_parent_view_dom().data('view');

        // if container doesn't have fixed rows or receiver is fixed row then we're fine
        if (moving_element_has_fixed_rows === false || receiver_parent_row.model.get('layout_type') == 'fixed') return true;

        return false;
    },
    _fix_first_element_drop_on_fluid_in_other_row: function () {
        var self = this;

        if (DDLayout.moved_from_row !== null && jQuery(DDLayout.moved_from_row).data('view') && self.get_parent_view().model.get('layout_type') == 'fluid') {
            if (jQuery(DDLayout.moved_from_row).data('view')._element_to_fix) {
                var fix = jQuery(DDLayout.moved_from_row).data('view')._element_to_fix;
                jQuery(DDLayout.moved_from_row).data('view')._self_fix_my_first = false;
                fix.css('margin-left', fix.data('prev-margin'));
            }
            jQuery(":visible", self.$el).first().css('margin-left', 0);
        }
    },
    _fix_first_element_on_drop_for_fluid: function (view, placeholder) {
        var self = this;

        //if( DDLayout.ddl_admin_page.instance_layout_view.model.get('Rows').length === 1 ) return;

        // do something only for fluid row, for a valid index and if we're moving within same row
        if (self.get_parent_view().model.get('layout_type') == 'fluid' && self._is_in_position(view.model) !== -1 && DDLayout.moved_from_row === null) {
            var check_point_for_first = self._is_in_position(view.model) > 0 ? 0 : 1,
                placeholder_index = placeholder.index();

            // if cell being moved is first cell
            if (self._is_in_first_position(view.model)) {

                // register first element not placeholder
                var first = jQuery(":visible:not(.ui-sortable-placeholder)", self.$el).first();
                // fix first element not placeholder margin
                if (placeholder_index === 1 && self._element_to_fix && self._element_to_fix.data("prev-margin")) {

                    self._element_to_fix.css('margin-left', self._element_to_fix.data("prev-margin"));
                    self._element_to_fix.data("prev-margin", undefined);
                    self._self_fix_my_first = false;
                }
                // register margin to fix first not placeholder element
                else if (placeholder_index > 1 && self._self_fix_my_first == false) {

                    first.data("prev-margin", first.css('margin-left'));
                    self._element_to_fix = first;
                    first.css('margin-left', 0);
                    self._self_fix_my_first = true;
                }
                // if placeholder has wrong margin fix it on coming back to its original row
                if (self._fix_placeholder_first === true) {
                    if (placeholder_index > check_point_for_first) {
                        placeholder.css('margin-left', self._FLUID_CELL_MARGIN);
                    }
                    else {
                        placeholder.css('margin-left', 0);
                    }
                }
            }
            // if cell being moved is not first cell
            else if (self._is_in_first_position(view.model) === false) {
                //if( DDLayout.ddl_admin_page.instance_layout_view.model.get('Rows').length === 1 ) return;
                // if placeholder has wrong margin fix it on coming back to its original row when it's in first position
                if (placeholder_index <= check_point_for_first) {
                    placeholder.css('margin-left', 0);

                    if (self._fix_placeholder_first === true && DDLayout.ddl_admin_page.instance_layout_view.model.get('Rows').length > 1) {
                        placeholder.css('margin-right', self._FLUID_CELL_MARGIN);
                    }

                }
                // // if placeholder has wrong margin fix it on coming back to its original row when it's in position different from first
                else {
                    if (DDLayout.ddl_admin_page.instance_layout_view.model.get('Rows').length > 1) {
                        placeholder.css('margin-right', 0);
                    }

                    placeholder.css('margin-left', self._FLUID_CELL_MARGIN);

                }
            }
        }
    },
    _is_in_position: function (model) {
        return this.model.models.indexOf(model);
    },
    _handle_drop_to_other_row: function (target_row, item, helper, has_good_index) {
        if (typeof DDLayout.drop_placeholder === 'undefined') {
            DDLayout.drop_placeholder = new DDLayout.CellDropPlaceholder();
        }

        DDLayout.drop_placeholder.set_target_row(target_row, item, helper, has_good_index);

    },

    _destroy_drop_placeholder: function () {
        if (DDLayout.drop_placeholder) {
            DDLayout.drop_placeholder.destroy();
            DDLayout.drop_placeholder = undefined;
        }
    },

    get_cells_for_dropping: function () {
        var self = this;
        var cells = Array();

        self.$el.children('.cell').not('.ui-sortable-placeholder').each(function (i) {
            var cell = {};
            cell['empty'] = self.model.at(i).isEmpty();
            var offset = jQuery(this).offset();
            cell['left'] = offset.left;
            cell['top'] = offset.top;
            cell['width'] = jQuery(this).width();
            cell['height'] = self._cells_max_height;
            cell['element'] = self.model.at(i);
            cells.push(cell);
        });

        return cells;

    },

    _handleSortOver: function (event, ui) {
        event.stopImmediatePropagation();
        var self = this,
            view = jQuery(ui.item[0]).data('view'),
            sender = ui.sender,
            sendView = sender ? jQuery(sender).data('view') : null,
            placeholder = jQuery(ui.placeholder[0]);

        if (_.isEqual(self, sendView)) {
            // We're moving back to the original row.
            DDLayout.moved_from_row = null;
            self._removeDummyModelsAndViews();
            placeholder.css({
                height: self._cells_max_height,
                marginTop: self._cells_margin_top
            });

            self._fix_placeholder_first = true;
            self._fix_first_element_on_drop_for_fluid(view, placeholder);

            placeholder.show();

            self._is_resorting_itself = true;

            self._destroy_drop_placeholder();
        }

    },
    _handleSortOut: function (event, ui) {
        event.stopImmediatePropagation();
        var self = this,
            view = jQuery(ui.item[0]).data('view'),
            sender = ui.sender,
            sendView = sender ? jQuery(sender).data('view') : null,
            sendModel = sendView ? sendView.model : null;


        if (sender && !_.isEqual(sendModel, self.model)) {
            jQuery(sendView.el).data("sender", self);
        }

    },

    _handleSortReceive: function (event, ui) {
        event.stopImmediatePropagation();
        var self = this,
            view = jQuery(ui.item[0]).data('view'),
            sender = ui.sender,
            sendView = sender ? jQuery(sender).data('view') : null,
            sendModel = sendView ? sendView.model : null;

        sendView._is_resorting_itself = false;

        try {
            if (DDLayout.drop_placeholder.get_drop_index() >= 0) {
                sendModel.remove(view.model, {silent: true});
                self.model.add(view.model, {at: ui.item.index(), silent: true});
            }
        }
        catch (e) {
            console.log(e.message);
        }

    },
    _handleSortStop: function (event, ui) {
        event.stopImmediatePropagation();
        jQuery(this).css('zIndex', 2);
        var self = this,
            view = jQuery(ui.item[0]).data('view'),
            len = self._dummy_model ? self._dummy_model.length : 0,
            original_index = jQuery(ui.item[0]).data('original_index'),
            index = ui.item.index();

        jQuery('.layout-container').removeClass("is-dragged");

        //console.log('target row outside', DDLayout.target_row.cid, self.cid, drop_index );

        if (self._is_resorting_itself) {
            DDLayout.views.abstract.CollectionView.prototype._handleSortStop.call(self, event, ui);

            DDLayout.ddl_admin_page.add_snapshot_to_undo();

        }
        else {

            var drop_index = DDLayout.drop_placeholder.get_drop_index();

            if (drop_index >= 0) {

                // Permantely update the original row.
                for (var i = 0; i < len; i++) {
                    self.model.add(self._dummy_model[i], {at: original_index + i, silent: true});
                }

                // Remove the cell we are dropping from the target row
                var dropping_cell = DDLayout.target_row.model.at(index);

                DDLayout.target_row.model.remove(dropping_cell);

                // Set the row divider to be the same as the target row
                var row_divider = DDLayout.target_row.model.at(drop_index).get('row_divider');
                dropping_cell.set('row_divider', row_divider)

                // collect cells to be removed
                var cell_width = view.model.get('width'), to_be_removed = [];
                for (var i = drop_index + cell_width - 1; i >= drop_index; i--) {
                    //DDLayout.target_row.model.remove( DDLayout.target_row.model.at(i) );
                    to_be_removed.push(DDLayout.target_row.model.at(i));
                }

                //Remove the empty cells where we are dropping
                DDLayout.target_row.model.reset(_.difference(DDLayout.target_row.model.models, to_be_removed));

                // Add the cell we are dropping into the correct place
                DDLayout.target_row.model.add(dropping_cell, {at: drop_index, silent: true});

                self.eventDispatcher.trigger("model_changed", "Elements resorted", view.model.get('name'), view.model.cid);

                DDLayout.ddl_admin_page.add_snapshot_to_undo();

            } else {
                // Cancel the drag because there is no drop point.
                self._cancel_drag(view.model.get('width'), view.model.get('row_divider'));
            }
        }

        self._clean_variables_afterDrop_stops();

        self.eventDispatcher.trigger('re_render_all');
    },
    _cancel_drag: function (width, row_divider) {
        //console.log('cancel drag')
        var self = this;

        DDLayout.drop_placeholder.add_no_drop_message(width, row_divider);

        var sendView = jQuery(DDLayout.moved_from_row).data('view');
        self._destroy_drop_placeholder();
        sendView._removeDummyModelsAndViews();
        self._removeDummyModelsAndViews();
        DDLayout.moved_from_row.customSortable('cancel');

        return false;
    },
    _clean_variables_afterDrop_stops: function () {
        var self = this;
        self._dummy_model = null;
        self._dummy_view = null;
        self._is_resorting_itself = true;
        self._element_to_fix = null;
        self._fix_placeholder_first = null;
        self._destroy_drop_placeholder();
    },
    _createDummyViewModel: function (index, view, helper, margin_top) {
        var self = this, width = view.model.get('width'), tmp;
        var row_divider = self.model.at(index).get('row_divider');

        self._dummy_model = [];
        self._dummy_view = [];

        for (var i = 0; i < width; i++) {
            var spacer = new DDLayout.models.cells.Cell({
                'name': 'spacer:' + (index + 1 + i),
                'cell_type': 'spacer',
                'row_divider': row_divider
            });
            self._dummy_model.push(spacer);
            self._dummy_view.push(new DDLayout.views.CellView({model: self._dummy_model[0]}));
            tmp = self._dummy_view[i].render().el;

            jQuery(tmp)
                .height(jQuery(view.el).data('computed_height'))
                .css({marginTop: margin_top + "px"});

            self.$el.insertAtIndex(index, tmp);
        }

    },
    _removeDummyModelsAndViews: function () {
        var self = this;

        _.each(self._dummy_view, function (item, index, list) {

            item.remove();
        });
        self._dummy_view = null;
        self._dummy_model = null;
    },

    _delete_selected_cell: function () {
        var tooltip_icon = jQuery('.js-delete-cell,.js-edit-cell');
        tooltip_icon.each(function () {
            if (jQuery(this).data('tooltip')) {
                jQuery(this).data('tooltip').remove();
            }

        });

        var self = this, index = -1;

        for (var i = 0; i < self.model.length; i++) {
            if (self.model.at(i).selected_cell) {
                index = i;
                break;
            }
        }

        if (index != -1) {

            var width = self.model.at(index).get('width');
            var row_divider = self.model.at(index).get('row_divider');

            var spacers = [];
            for (var i = 1; i <= width; i++) {
                var spacer = new DDLayout.models.cells.Cell({
                    'name': 'spacer:' + ( index + i ),
                    'cell_type': 'spacer',
                    'row_divider': row_divider
                });
                spacers.push(spacer);
            }

            self.model.remove(self.model.at(index));
            self.model.add(spacers, {silent: true, at: index, merge: false});

            self.eventDispatcher.trigger('re_render_all');
        }

    },
    _replace_selected_cell: function (new_cell, new_width, avoid_render) {
        var self = this, index = -1, the_new_width = new_width || 0;

        new_cell.selected_cell = true;

        for (var i = 0; i < self.model.length; i++) {

            if (self.model.at(i).selected_cell) {
                index = i;
                break;
            }
        }

        if (index != -1) {

            var width = self.model.at(index).get('width');
            var row_divider = self.model.at(index).get('row_divider');
            var new_cell_width = new_width || new_cell.get('width');


            self.model.remove(self.model.at(index));
            self.model.add(new_cell, {at: index});
            if (new_width) {
                new_cell.set('width', new_width);
            }

            if (new_cell_width <= width) {
                // fill remaining space
                for (var i = new_cell_width; i < width; i++) {
                    var spacer = new DDLayout.models.cells.Cell({
                        'name': 'spacer:' + (index + i + 1),
                        'cell_type': 'spacer',
                        'row_divider': row_divider
                    });

                    self.model.add(spacer, {at: index + i, merge: false});
                }
            } else {

                // the cell is wider. Delete cells to the right.
                for (var i = new_cell_width - width - 1; i >= 0; i--) {
                    self.model.remove(self.model.at(index + i + 1));
                }
            }

            if (avoid_render === true) return new_cell;

            self.eventDispatcher.trigger('re_render_all');

            return new_cell
        }
    }
});
