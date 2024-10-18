DDLayout_settings.DDL_JS.ns.ready(function () {

    if( typeof DDLayout.DialogView === 'undefined' ) return;

    DDLayout.ListingMain.DialogDuplicate = DDLayout.DialogView.extend({
        close: function (event, dom, view) {
            //console.log(event.type, arguments);
        },
        open: function (event, dom, view) {
            //console.log(event.type, arguments);
        },
        beforeOpen: function (event, dom, view) {
            //console.log(event.type, arguments);
        },
        beforeClose: function (event, dom, view) {
            // console.log(event.type, arguments);
        },
        create: function (event, dom, view) {
            // console.log(event.type, arguments);
        },
        focus: function (event, dom, view) {
            //  console.log(event.type, arguments);
        },
        refresh: function (event, dom, view) {
            //   console.log(event.type, arguments);
        }
    });


    DDLayout.ListingMain.ToolsetResourcesHandler = {
        layout: null,
        cells: [],
        dialog: null,
        data_obj: null,
        all_translated: true,
        init: function (layout, cells, data_obj) {
            this.layout = layout;
            this.cells = cells;
            this.data_obj = data_obj;

            this.init_dialog();
            this.init_events();

        },
        destroy: function () {
            this.turn_off_events();
            this.dialog.remove();

            this.layout = null;
            this.cells = [];
            this.dialog = null;
            this.data_obj = null;
        },
        cell_type_to_resource_label: function (cell_type) {
            return DDLayout_settings.DDL_JS.toolset_cells_data[cell_type].label
        },
        cell_type_to_content_field: function (cell_type) {
            return DDLayout_settings.DDL_JS.toolset_cells_data[cell_type].property
        },
        cell_type_to_resource_type: function (cell_type) {
            return DDLayout_settings.DDL_JS.toolset_cells_data[cell_type].type
        },
        select_deselect_handler: function (event) {
            var me = jQuery(this),
                select = me.data('select'),
                deselect = me.data('deselect');

            jQuery(deselect).each(function (i) {
                jQuery(this).prop('checked', false).trigger('change');
            });

            jQuery(select).each(function (i) {
                jQuery(this).prop('checked', true).trigger('change');
            });
        },
        init_events: function () {
            jQuery('.js-duplicate-select-all').on('click', this.select_deselect_handler);
            jQuery('.js-keep-original-select-all').on('click', this.select_deselect_handler);
            jQuery('.js-ddl-duplicate-input').on('mouseup', this.handle_radios_relationship);
        },
        turn_off_events: function () {

            jQuery('.js-duplicate-select-all').off('click', this.select_deselect_handler);
            jQuery('.js-keep-original-select-all').off('click', this.select_deselect_handler);
            jQuery('.js-ddl-duplicate-input').off('mouseup', this.handle_radios_relationship);

            this.layout.stopListening();
        },
        init_dialog: function () {
            var self = this;

            this.dialog = new DDLayout.ListingMain.DialogDuplicate({
                title: this.layout.get('name') + ' ' + DDLayout_settings.DDL_JS.strings.duplicate_dialog_title,
                selector: '#ddl-duplicate-template',
                template_object: {
                    layout_name: this.layout.name,
                    cells: this.cells
                },
                buttons: [
                    {
                        text: DDLayout_settings.DDL_JS.strings.cancel,
                        icons: {
                            secondary: ""
                        },
                        click: function () {
                            jQuery(this).ddldialog("close");
                        }
                    },
                    {
                        text: DDLayout_settings.DDL_JS.strings.duplicate,
                        'class': 'button button-primary primary-button js-duplicate-button',
                        icons: {
                            primary: ""
                        },
                        click: self.duplicate
                    },
                ]
            });

            this.dialog.$el.on('ddldialogclose', function (event) {
                self.destroy();
            });

            this.layout.listenTo(this.layout, 'ddl-duplicate-completed', this.dialog_event_listener);
        },
        dialog_event_listener: function (event, args) {
            DDLayout.ListingMain.ToolsetResourcesHandler.get_dialog().dialog_close();
        },
        duplicate: function (event, object) {
            var duplicate = [],
                message = '',
                $duplicate = jQuery('.js-duplicate-checkbox[value="1"]'),
                layout = DDLayout.ListingMain.ToolsetResourcesHandler.get_layout();

            $duplicate.each(function (i) {
                if (jQuery(this).is(':checked')) {
                    var type = jQuery(this).data('cell_type'),
                        id = jQuery(this).prop('name'),
                        cell = DDLayout.ListingMain.ToolsetResourcesHandler.get_cell_from_id_and_type(id, type);
                    duplicate.push({
                        id: id,
                        type: type,
                        cell: cell
                    });
                }
            });

            if ($duplicate.length === duplicate.length) {
                message = DDLayout_settings.DDL_JS.strings.duplicate_result_message_all;
            } else {
                message = DDLayout_settings.DDL_JS.strings.duplicate_result_message_some;
            }
            var data_obj = DDLayout.ListingMain.ToolsetResourcesHandler.get_data_obj();
            data_obj.duplicate_message = message;

            layout.trigger('ddl-duplicate-trigger', {
                duplicate: duplicate,
                data_obj: DDLayout.ListingMain.ToolsetResourcesHandler.get_data_obj()
            });
        },
        get_dialog: function () {
            return this.dialog;
        },
        get_layout: function () {
            return this.layout;
        },
        get_data_obj: function () {
            return this.data_obj;
        },
        get_cell_from_id_and_type: function (id, type) {
            var property = this.cell_type_to_content_field(type), cell;

            cell = _.find(this.cells, function (v) {
                return v.content && v.content[property] == id;
            });

            return _.isObject(cell) && cell.hasOwnProperty('content') ? cell : null;
        },
        handle_radios_relationship: function (event) {
            var me = this,
                other = jQuery(this).data('other'),
                me_class = jQuery(this).data('me'),
                all = jQuery(me_class),
                all_others = jQuery(other).toArray(),
                index = all.toArray().indexOf(me);


            if (jQuery(all_others[index]).prop('checked') === true) {
                jQuery(all_others[index]).prop('checked', false).trigger('change');
            } else {
                jQuery(all_others[index]).prop('checked', true).trigger('change');
            }
        }
    };
});


