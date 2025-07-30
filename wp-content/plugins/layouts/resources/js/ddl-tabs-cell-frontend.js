var DDLayout = DDLayout || {};
DDLayout.DDL_TabsCellFrontend = function($){
    var self = this, $disabled;

    self.init = function(){
        $disabled = jQuery('ul.nav li.disabled a');
        self.handle_disabled_link();
        self.fixActiveClasses();
    };

    self.fixActiveClasses = function(){

        if( jQuery( '.ddl-tab-content.tab-content' ).length === 0 ){
            return;
        }

        jQuery.each( jQuery( '.ddl-tab-content.tab-content' ), function( key, value ) {
            jQuery(value).find( '.tab-pane' ).first().addClass( 'active' );
        });
    };

    self.handle_disabled_link = function(){
        $disabled.on('click', function(event){
            event.stopImmediatePropagation();
            return false;
        });
    };

    self.init();

};

(function ($) {
    jQuery(function ($) {
        new DDLayout.DDL_TabsCellFrontend($);
    });
}(jQuery));

