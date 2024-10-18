var DDLayout = DDLayout || {};

DDLayout.Duplicator = {};

DDLayout.Duplicator.Storage = function () {
        var self = this, undefined;
    
        self.DUPLICATE_KEY = 'ddl-row-copy-paste-data';
        self.value = undefined;
        self.multiple_types = [];
        self.SPECIAL_KINDS = {
            'Tab' : 'Tab',
            'Row' : 'Row',
            'Panel' : 'Panel'
        };
    
        self.init = function(){
            self.setValue( self.get() );
            self.listen();
            self.multiple_types = self.getAllowMultipleTypes( self.getValue() );
        };

        self.get = function(){
            return jQuery.jStorage.get(self.DUPLICATE_KEY, undefined);
        };

        self.setValue = function(value){
            self.value = value;
        };

        self.isKindSpecial = function(){
            var value = self.getValue(),
                kind = value.kind;

            return _.keys( self.SPECIAL_KINDS ).indexOf( kind ) !== -1;
        };

        self.getSpecialKind = function(){
            if( self.isKindSpecial() === false ) return null;

            var value = self.getValue(),
                kind = value.kind;

            return self.SPECIAL_KINDS[kind];
        };

        self.getKind = function(){
            return self.getValue().kind;
        }

        self.getValue = function(){
            return self.value;
        };

        self.set = function(value){
            if( _.isEqual( value, self.getValue() ) === false ){
                jQuery.jStorage.set(self.DUPLICATE_KEY, value);
            }
        };

        self.reset = function(){
            jQuery.jStorage.deleteKey(self.DUPLICATE_KEY)
        };

        self.hasValue = function(){
            return self.value !== undefined && self.value !== null;
        };

        self.listen = function(){
            jQuery.jStorage.listenKeyChange(self.DUPLICATE_KEY, function(key, action){
                if( action === 'updated' ){
                    self.setValue( self.get() );
                    self.multiple_types = self.getAllowMultipleTypes( self.getValue() );
                }
                WPV_Toolset.Utils.eventDispatcher.trigger( key+'_'+action, self.getValue(), self.getKey(), action );
            });
        };

        self.getKey = function(){
            return self.DUPLICATE_KEY;
        };

        self.getAllowMultipleTypes = function( value ){
            if( _.isObject(value) === false ) return [];
            
            if( value.hasOwnProperty('Cells') === false || value.Cells.length === 0 ) return [];

            var ret = [];

            _.each(value.Cells, function(v,i,l){
                if( v.hasOwnProperty('Rows') ){
                    _.each(v.Rows, function(v,i,l){
                        ret = _.union(ret, self.getAllowMultipleTypes(v));
                    });
                }
                if( DDL_LayoutCellInfoObject[v['cell_type']] === 'no' && ret.indexOf( v['cell_type'] ) === -1 ){
                    ret.push( v['cell_type'] );
                }
            });
            
            return ret;
        };

        self.getCellsOfTypeNoMultipleInRow = function(){
            return self.multiple_types;
        };

        self.hasMultipleForbidden = function(){
            return self.multiple_types.length > 0;
        };

    /**
     *
     * @param json
     * @param properties {name:layout.name, cssframerwork:layout.cssframework, type:layout.type, width:layout.width}
     * @returns {*}
     * @private
     */
    self._scan_json = function(json, properties)
    {
        var self = this, tmpRows = [], data = json, layout = properties;

        if( data )
        {
            _.each(data, function(r, i, rows){

                var tmp = undefined, row = jQuery.extend(true, {}, r), row_model;

                if( row && row.Cells )
                {
                    delete row.id;
                    tmp = new DDLayout.models.collections.Cells

                    _.each(row.Cells, function( element, j, cells ){
                        var cell = element, kind = cell.kind;

                        // remove id and kind, we'll set them anew
                        delete cell.id;
                        delete cell.kind;

                        if( cell.hasOwnProperty('Rows') )
                        {
                            var container, cell_tmp = _.extend({}, cell);
                            //we don't want wo make it copy twice one as an object and one as a model
                            delete cell_tmp.Rows;
                            container = new DDLayout.models.cells[kind](cell_tmp);

                            try
                            {
                                var container_rows = new DDLayout.models.collections.Rows( self._scan_json( cell.Rows, layout )  );
                                    container.set('Rows', container_rows);
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

                    // remove id will be set anew
                    delete tmp.id;
                    tmp.layout = layout;
                    row_model = new DDLayout.models.cells.Row( {Cells:tmp, id:0} );
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
                    return [];
                }

            });
        }

        return tmpRows;
    };

    self.getCurrentLayoutProperties = function(){
        var layout = DDLayout.ddl_admin_page.instance_layout_view.model;

        try{
            return {name:layout.get('name'), cssframerwork:layout.get('cssframework'), type:layout.get('type'), width:layout.get('width')};
        } catch( e ){
            console.log( 'Something bad happened while creating current layout properties: ', e.message );
            return null;
        }

    };

    self.getValuesAsModelArray = function( ){
        if( !self.getValue() ) return null;

        try{
            return self._scan_json( [self.getValue()], self.getCurrentLayoutProperties() );
        } catch(e){
            console.log( 'Something bad happened while creating the new Row: ', e.message );
            return null;
        }
    };

    self.init();
};

DDLayout.Duplicator.DuplicateRow = function () {
    var self = this, caller, storage, $paste, $copy, COPY = 'copy', PASTE = 'paste';

    self.action = null;
    self.current = null;
    self.model = null;

    self.paste_enabled = false;
    self.forbidden_type = null;

    self.init = function () {
        storage = new DDLayout.Duplicator.Storage();
    };

    self.initElementsStatus = function(){
        $paste = jQuery('.js-paste-row');
        $copy = jQuery('.js-copy-row');

        if( storage.hasValue() ){
            $paste.removeClass('disabled')
        } else {
            $paste.addClass('disabled')
        }
    };

    self.handleAction = function (row, deed, caller) {
        self.action = deed;
        self.current = row;
        self.model = row.model;
        caller = caller;

        if( self.getAction() === COPY ){
            return self.handleCopy();
        } else if( self.getAction() === PASTE ){

            if( storage.hasValue() === false) return false;

            self.setPasteShouldBeEnabled();

            if( self.paste_enabled === false ){
                self.allow_multiple_false_dialog();
                return false;
            } else if( self.checkIfTabIsCopiedInATabbedStructure() === false ) {
                self.special_kind_dialog();
                return false;
            }
            else {
                return self.handlePaste();
            }

        } else {
            return false;
        }
    };

    self.setPasteShouldBeEnabled = function(){
        self.paste_enabled = self.currentLayoutHasAllowMultipleInCommonWithPasteData() === false;
    };

    self.getParentKind = function(){
        return self.current.get_parent_view();
    };

    self.currentLayoutHasAllowMultipleInCommonWithPasteData = function(){
        if( storage.hasMultipleForbidden() === false ) return false;

        var layout = DDLayout.ddl_admin_page.instance_layout_view.model,
            multiples = storage.getCellsOfTypeNoMultipleInRow(),
            bool = false,
            len = multiples.length;

        for( var i = 0; i < len;  i++ ){
            if( layout.has_cell_of_type( multiples[i] ) ){
                bool = true;
                self.forbidden_type = multiples[i];
                break;
            }
        }
        return bool;
    };

    self.checkIfTabIsCopiedInATabbedStructure = function(){

        var special_kind = storage.getSpecialKind();

        if( special_kind === null ) return true;

        return self.model.get('kind') === special_kind;

    };

    self.handleCopy = function(){
        return storage.set( self.toJSON() );
    };

    self.handlePaste = function(){
        var collection = self.getCollection(),
            index = self.getCurrentRowIndex(),
            new_rows = storage.getValuesAsModelArray();

        DDLayout.ddl_admin_page.save_undo();

        if( collection !== null && index !== null && new_rows !== null ){
            var len = new_rows.length;
            for( var i = 0; i < len; i++ ){
                collection.add(new_rows[i], {at:index+i+1});
            }
            DDLayout.ddl_admin_page.instance_layout_view.eventDispatcher.trigger('re_render_all', {callback:function(){
                if( new_rows[0] instanceof DDLayout.models.cells.Row && new_rows[0].find_cell_of_type('child-layout') ){
                    self.child_layout_dialog();
                }
            }});

            return true;
        }

        return false;
    };

    self.getAction = function( ){

        if( !self.action ) return '';

        return self.action.split('-row')[0];
    };

    self.toJSON = function(){
        return self.model.toJSON();
    };

    self.getCurrentRowIndex = function(){
        var collection = self.getCollection();
        try{
            return collection.indexOf(self.model);
        } catch( e ){
            console.log('No index value', e.message);
            return null;
        }
    };

    self.getCollection = function(){
        try{
            return self.model.collection;
        } catch( e ){
            console.log('No collection object', e.message);
            return null;
        }
    };

    self.allow_multiple_false_dialog = function( ){

        var dialog = new DDLayout.DialogView({
            title:  DDLayout_settings.DDL_JS.strings.forbidden_paste,
            modal:true,
            width: 400,
            resizable: false,
            draggable: false,
            position: {my: "center", at: "center", of: window},
            selector: '#ddl-paste-cell-forbidden-dialog-tpl',
            template_object: {
                layout_name: DDLayout.ddl_admin_page.instance_layout_view.model.get('name'),
                forbidden_type: self.forbidden_type
            },
            buttons: [
                {
                    text: DDLayout_settings.DDL_JS.strings.close,
                    icons: {
                        secondary: ""
                    },
                    click: function () {
                        jQuery(this).ddldialog("close");
                    }
                },
            ]
        });

        dialog.$el.on('ddldialogclose', function (event) {
            dialog.remove();
        });

        dialog.dialog_open();
    };

    self.special_kind_dialog = function(){
        var kind = storage.getKind(),
            dialog = new DDLayout.DialogView({
                title:  DDLayout_settings.DDL_JS.strings.forbidden_paste,
                modal:true,
                width: 400,
                position: {my: "center", at: "center", of: window},
                resizable: false,
                draggable: false,
                selector: '#ddl-paste-special-dialog-tpl',
                template_object: {
                    kind:kind
                },
                buttons: [
                    {
                        text: DDLayout_settings.DDL_JS.strings.close,
                        icons: {
                            secondary: ""
                        },
                        click: function () {
                            jQuery(this).ddldialog("close");
                        }
                    },
                ]
            });

        dialog.$el.on('ddldialogclose', function (event) {
            dialog.remove();
        });

        dialog.dialog_open();
    };

    self.child_layout_dialog = function( ){

        var dialog = new DDLayout.DialogView({
            title:  DDLayout_settings.DDL_JS.strings.contains_child,
            modal:true,
            resizable: false,
            draggable: false,
            position: {my: "center", at: "center", of: window},
            width: 400,
            selector: '#ddl-paste-child-layout-dialog-tpl',
            template_object: {
                layout_name: DDLayout.ddl_admin_page.instance_layout_view.model.get('name')
            },
            buttons: [
                {
                    text: DDLayout_settings.DDL_JS.strings.create_child,
                    icons: {
                        secondary: ""
                    },
                    click: function () {
                        jQuery(this).ddldialog("close");
                        _.defer(function(){
                            var view = jQuery('.cell-child-layout').data('view');
                            DDLayout.ddl_admin_page.show_default_dialog( 'edit', view );
                            jQuery(document).on('child-layout.dialog-open', function(){
                                jQuery('.js-save-dialog-settings').show();
                            })
                        });
                    }
                },
                {
                    text: DDLayout_settings.DDL_JS.strings.skip,
                    icons: {
                        secondary: ""
                    },
                    click: function () {
                        jQuery(this).ddldialog("close");
                    }
                }
            ]
        });

        dialog.$el.on('ddldialogclose', function (event) {
            dialog.remove();
        });

        dialog.dialog_open();
    };


    self.init( );
};