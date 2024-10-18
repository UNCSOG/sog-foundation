jQuery(function ($) {
    jQuery( "#editor_tabs" ).tabs();
});

var DDLayout = DDLayout || {};

DDLayout_settings.DDL_JS.ns = head;
DDLayout_settings.DDL_JS.ns.js(
    DDLayout_settings.DDL_JS.CSS_lib_path + 'js-editor/JsEditor.js'
);

(function($){
    DDLayout_settings.DDL_JS.ns.ready(function(){
        DDLayout.js_page = new DDLayout.LayoutsJS($);
    });
}(jQuery));

DDLayout.LayoutsJS = function($){
        var self = this,
            attrs = {},
            $message_container = jQuery(".js-js-editor-message-container"),
            $area = jQuery('.js-ddl-js-editor-area'),
            js_string = $area.val();
    _.defaults(attrs, {js_string: ""});

        self.JsEditor = null;


    self.init = function(){
        self.set('js_string', js_string);
        self.JsEditor = new DDLayout.JsEditor(self);
    };

    self.set = function (name, value) {
        attrs[name] = value;
        self.trigger('change', {
            name: name,
            value: value
        });
        return self;
    };

    self.get = function ( name ) {
        return attrs[name];
    };

    self.getJsString = function(){
        return self.get('js_string');
    };

    self.setJsString = function( js_string ){
        self.set('js_string', js_string );
        return self;
    };

    self.get_layout_as_JSON = function(){
        return {};
    };

    self.save = function( callback ){
        

        var params = {
            action : 'save_layouts_js',
            'ddl_js_nonce' : DDLayout_settings.DDL_JS.ddl_js_nonce,
            js_string : self.getJsString()
        };

        WPV_Toolset.Utils.do_ajax_post(params, {
                success:function(response){

                    if( response.message  ){

                        if( typeof callback === 'function' ) callback.call(self);

                        $message_container.wpvToolsetMessage({
                            text: response.message,
                            stay: false,
                            close: false,
                            type: 'info'
                        });
                    } else if( response.error ){

                        $message_container.wpvToolsetMessage({
                            text: response.error,
                            stay: true,
                            close: true,
                            type: 'error'
                        });

                    }
                },
                fail:function( errorThrown ){

                    $message_container.wpvToolsetMessage({
                        text: 'Ajax call failed ' + errorThrown,
                        stay: true,
                        close: true,
                        type: 'error'
                    });
                }
        });
    };

    _.extend(this, Backbone.Events);

    self.init();
};