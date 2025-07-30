/**
 * This View is used to display element tooltips with details about additional css classes.
 * ids and etc inside Layout editor.
 */
DDLayout.views.Tooltip = Backbone.View.extend({
    tagName: 'div',
    template: _.template( jQuery( '#ddl-element-tooltip' ).html() ),
    click_open: false,
    initialize: function ( options ) {
        this.$el = options.element;
        this.model = options.model;
    },

    removeTooltip: function () {
        if( this.click_open === false ){
            this.toolset_pointer.pointer( 'close' );
        }
    },

    showAllTooltips: function () {
        jQuery( '.js-element-info' ).each(function ( index, value ) {
            jQuery( value ).trigger( 'mouseenter' );
        });
    },

    removeAllTooltips: function () {
        jQuery( '.js-element-info' ).each( function ( index, value ) {
            jQuery( value ).data( 'clicked', true );
            jQuery( value ).trigger( 'click' );
        });
    },
    render: function () {
        var self = this;

        self.toolset_pointer = this.$el.pointer({
            pointerClass: 'wp-toolset-pointer js-layouts-info-tooltip',
            content: this.template( this.model.attributes ),
            position: {
                edge: 'bottom'
            },
            buttons: function( event, t ) {
                var $button = jQuery( '<a class="close" href="#"></a>' );

                return $button.on( 'click.pointer', function(e) {
                    e.preventDefault();
                    t.element.pointer( 'close' );
                    jQuery('.js-element-info-' + self.model.get('id') ).data( 'clicked', false );
                    DDLayout.AdminPage.tooltips--;
                    DDLayout.AdminPage.infoButtonText();
                    self.model.trigger('ddl-markup-tooltip-closed');
                    if( DDLayout.AdminPage.tooltips === 0 ){
                        Toolset.hooks.doAction( 'ddl-stop-filters-hover');
                        Toolset.hooks.doAction( 'ddl-init-filters-hover');
                    }
                });
            },
            pointerWidth: 220,
            show: function ( event, t ) {
                t.pointer.show();
                t.opened();
            }
        }).css('z-index', '9998');
        this.toolset_pointer.pointer( 'open' );
    }
});
