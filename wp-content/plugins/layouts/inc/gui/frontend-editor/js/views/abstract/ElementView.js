DDLayout.views.abstract.ElementView = Backbone.View.extend({
    tagName:'div',
    compound:'',
    parentDOM:null,
    initialize:function(options)
    {
        var self = this;

        self.$el.data('view', self);

        self.options = options;

        self.parentDOM = options && options.parentDOM ? options.parentDOM : null;

        _.bindAll( self, 'beforeRender', 'render', 'afterRender', 'load_edit_dialog');

        self.render = _.wrap(self.render, function( render, args ) {
            self.beforeRender();
            render( args );
            //execute afterRender after everything else executes
            //_.defer( _.bind( self.afterRender ) );
            self.afterRender();
            return self;
        });

        self.compound = options && options.compound ? options.compound : '';

        self.$el[0].className = 'js-'+ self.model.get('kind').toLowerCase() + " " + self.model.get('kind').toLowerCase();

        self.model.listenTo(self.model, 'layout_element_model_change', self.listen_model_change);

        return self;
    },
    listen_model_change:function( model, attributes, options ){
        //console.log( 'Model changed ', this, arguments );
    },
    beforeRender:function(){

    },
    afterRender:function(){

    },
    // Stub for element edit
    editElement: function (e) {
        var self = this;
        e.stopImmediatePropagation();

         if( self.model.get('is_not_editable') ){

             DDLayout.ddl_admin_page.trigger('open-info-dialog', {}, self.model);

         } else{

             DDLayout.ddl_admin_page.take_undo_snapshot();
             DDLayout.ddl_admin_page.trigger('open-'+self.model.get('kind')+'-dialog', 'edit', self);
         }


        self.close();
    },
    get_parent_view:function(){
        var self = this;
        var parent = self.mockParent();

        return parent;
    },
    mockParent:function( ){
        var self = this,
            model = self.model,
            id = model.get('id'),
            row;

        row = DDLayout.ddl_admin_page.instance_layout_view.model.get_row_where_cell_has_property_value( 'id', id );

        if( row ){
            return new DDLayout.views.RowView({model:row, mock : true});
        }

        return null;
    },
    get_cell_offset:function(){
        var self = this,
            classes = self.$el.prop('class'),
            prefix = DDLayout_settings.DDL_JS.strings.offset_prefix,
            offset = classes ? classes.split(prefix) : 0;

        if( _.isArray( offset ) && offset.length > 1 ){

            if( offset[1].length > 1 ){
                offset = offset[1].split(' ');
                offset = offset[0];
            } else {
                offset = offset[1];
            }

        } else {

            offset = 0;
        }

        return +offset;
    },
    set_cell_offset:function(){
        var self = this;
        self.model.set( 'offset', self.get_cell_offset() );
    },
    load_edit_dialog:function( me ){

        this.model.stopListening(this.model, 'ddl_layouts_element_dialog_loads', this.load_edit_dialog);

        if( _.isEqual(me.model, this.model) === false ) {
            return;
        }

        var self = this,
            json = me.model.toJSON(),
            url = DDLayout.ddl_admin_page.get_edit_url( json ),
            name = DDLayout.ddl_admin_page.toolset_resource_nice_name( json );
           // description = jQuery('#ddl-default-edit .ddl-fields-description').eq(0).text();

        if( url ){
            jQuery('#ddl-default-edit a.js-toolset-resource-link').prop('href', url);
            var new_text = jQuery('#ddl-default-edit a.js-toolset-resource-link span').text().replace('%NAME%', name);
            jQuery('#ddl-default-edit a.js-toolset-resource-link span').text( new_text );
        }

       // description = description.replace( json.cell_type, name);
      //  jQuery('#ddl-default-edit .ddl-fields-description').eq(0).text(description);

        if( self.model.get('cell-type') !== 'video-cell' ){
            _.delay(function(){
                jQuery('.js-dialog-edit-save').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
            }, 500);
        }

        self.build_cell_nice_name();
    },
    build_cell_nice_name: function(){
        var self = this,
            $title = jQuery('#ddl-default-edit .js-dialog-title'),
            title = $title.text(),
            chunk = title.split('cell');

        // Some cells use 'Cell' 
        if( chunk.length <= 1 ) {
            chunk = title.split('Cell');
        }

        $title.text( chunk[0] + ' Cell' );
    },
    setElement: function( el ){

        return Backbone.View.prototype.setElement.call( this, el );
    },
    _setElement:function( el ){

        return Backbone.View.prototype._setElement.call( this, el );
    }
});