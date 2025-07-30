DDLayout.views.abstract.CollectionView = Backbone.View.extend({
    el: null,
    tagName:'div',
    compound:'',
	elements: null,
    parentDOM:null,
    events: {
        'sortstart': '_handleSortStart',
        'sortupdate': '_handleSortUpdate',
        'sortactivate': '_handleSortActivate',
        'sortdeactivate': '_handleSortDeactivate',
        'sortout': '_handleSortOut',
        'sortchange': '_handleSortChange',
        'sort':'_handleSortAction',
        'sortover': '_handleSortOver',
        'sortreceive': '_handleSortReceive',
        'sortremove':'_handleSortRemove',
        'sortbeforestop': '_handleBeforeStop',
        'sortstop': '_handleSortStop'
    },
    initialize: function ( options ) {
        var self = this;

	    self.options = options;

        self.parentDOM = options.parentDOM;
        self.$el.data( 'view', self );

        _.bindAll( self, 'beforeRender', 'render', 'afterRender');

        self.render = _.wrap(self.render, function( render, args ) {
            self.beforeRender();
            render( args );
            //execute afterRender after everything else executes
            _.defer( _.bind( self.afterRender ) );
            return self;
        });

		self.elements = Array();
        self.compound = options && options.compound ? options.compound : '';

        self.render( options );
    },
    beforeRender:function()
    {
        ///
    },
    afterRender:function()
    {
        var self = this;
        self.$el.data( 'view', self );
    },
    render: function (option) {
        var self = this,
            options = _.extend({invisibility:self.options.invisibility}, option );

		self._cleanBeforeRender( self.$el );

       //self.$el.children().remove();

        self.fragment = document.createDocumentFragment();

		self.appendModelElement( options );

        self.$el.html( self.fragment );

        return self;
    }
    ,appendModelElement:function( opt )
    {

        var undefined, self = this, view, el, options, current = opt && opt.current ? opt.current : undefined;

		self.model.each(function(model){
			try{

                var container = undefined,
                    invisibility = undefined,
                    containers_elements = Toolset.hooks.applyFilters('ddl-get_containers_elements');

                if( _.values( containers_elements ).indexOf( self.compound + model.get('kind') ) !== -1 )
                {
                    container = self.options.container;
                }
               if( opt !== undefined  ) {
                   invisibility = opt.invisibility;
               }

                options = {
                           model:model,
                           compound:self.compound,
                           container:container,
                           invisibility:invisibility,
                           current:current,
                           parentDOM:self.$el
                          };

	            view = new DDLayout.views[ self.compound + model.get('kind') + 'View' ]( options );

	            el = view.render( current && current.cid === model.cid ).el;

	            self.fragment.appendChild( el );

				self.elements.push(view);

	        }
	        catch( e )
	        {
	            //TODO:do something here to handle API calls / errors
	            console.error( e.message );
	        }
		}, self)

        return this;
    },
    _handleSortStart: function (event, ui) {
        DDLayout.ddl_admin_page.take_undo_snapshot();

        event.stopImmediatePropagation();

		jQuery( '.layout-container' ).addClass("is-dragged");
    },
    _handleSortActivate: function (event, ui) {
        event.stopImmediatePropagation();
    },
    _handleSortUpdate: function (event, ui) {
        event.stopImmediatePropagation();

        DDLayout.ddl_admin_page.add_snapshot_to_undo();

    },

    _handleSortOut: function (event, ui) {
        event.stopImmediatePropagation();
    },
    _handleSortOver: function (event, ui) {
        event.stopImmediatePropagation();
    },
    _handleSortReceive: function (event, ui) {
        event.stopImmediatePropagation();
    },
    _handleSortChange: function (event, ui) {
        event.stopImmediatePropagation();
    },
    _handleSortAction:function(event, ui)
    {
        event.stopImmediatePropagation();
    },
    _handleSortRemove:function(event,ui)
    {
        event.stopImmediatePropagation();
    },
    _handleSortDeactivate: function (event, ui) {
        event.stopImmediatePropagation();
    },
    _handleBeforeStop:function(event, ui)
    {
        event.stopImmediatePropagation();
    },
    _handleSortStop:function(event, ui)
    {
        event.stopImmediatePropagation();

		jQuery( '.layout-container' ).removeClass("is-dragged");
		jQuery( '.js-row' ).removeClass('is-hovered');

        var self = this, view = jQuery( ui.item[0] ).data('view');
        self.model.remove( view.model, {silent: true} );
        self.model.add(view.model, {at: ui.item.index(), silent: true});
       // self.render();
        self.eventDispatcher.trigger("model_changed", "Elements resorted", view.model.get('name'), view.model.cid );

    },
    /*
    ** remove all the children view to clean event queue
     */
	_cleanBeforeRender:function( el )
	{
		var self = this;

		el.find('div').each(function( i, v ){

			if( jQuery(v).data('view') )
			{
				self._cleanBeforeRender( jQuery(v) );
				jQuery(v).data('view').remove();
			}

		});
	},

	getElementView : function (index) {
		return this.elements[index];
	},

	getElementCount : function () {
		return this.elements.length;
	},
    get_parent_view_dom : function () {
        // get the row view.
        return this.parentDOM;
    }
});