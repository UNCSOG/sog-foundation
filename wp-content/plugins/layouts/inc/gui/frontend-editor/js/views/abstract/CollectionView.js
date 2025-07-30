DDLayout.views.abstract.CollectionView = Backbone.View.extend({
    el: null,
    compound:'',
    tagName:'div',
    elements: null,
    parentDOM:null,
    initialize: function ( options ) {
        var self = this;

        self.options = options;

        self.parentDOM = options && options.parentDOM ? options.parentDOM : null;

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
    beforeRender:function(){

    },
    afterRender:function(){

    }
});