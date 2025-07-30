var DDLayout = DDLayout || {};

DDLayout.PrivateLayoutGutenbergHelper = function($){
    var self = this;

    self.init = function(){
        Toolset.hooks.addAction( 'toolset-lock-overlay-after-append', self.runDependencies );
    };

    self.runDependencies = function(){
        var stopUsingLayouts = new DDLayout.StopUsingLayoutsManager( $, $('.js-ddl-stop-using-layouts-button') );
    };

    _.bindAll( self, 'runDependencies' );
};

(function ($) {
    $(window).on('load', function(){
        DDLayout.privateLayoutGutenbergHelper = {};
        DDLayout.PrivateLayoutGutenbergHelper.call( DDLayout.privateLayoutGutenbergHelper, $ );
        DDLayout.privateLayoutGutenbergHelper.init();
    });
}(jQuery));