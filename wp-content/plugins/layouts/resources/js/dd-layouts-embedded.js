var DDL_Layouts_Embedded = DDL_Layouts_Embedded  || {};


DDL_Layouts_Embedded.Main = function($){
    var self = this;

    self.init = function(){
        _.defer(function(){
            $('.create-layout-for-page-wrap').show();
        });
    };

    self.init();

};

;(function($){
    DDL_Layouts_Embedded.Main.call( {}, $ );
}(jQuery));