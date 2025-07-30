var DDLayout = DDLayout || {};


DDLayout_settings.DDL_JS.ns = head;

DDLayout_settings.DDL_JS.ns.js(
    DDLayout_settings.DDL_JS.lib_path + "he/he.min.js"
    , DDLayout_settings.DDL_JS.common_rel_path + "/res/lib/jstorage.min.js"
    , DDLayout_settings.DDL_JS.common_rel_path + "/utility/js/keyboard.min.js"
    , DDLayout_settings.DDL_JS.lib_path + "prototypes.js"
    , DDLayout_settings.DDL_JS.CSS_lib_path + 'css-editor/CssEditor.js'
);


(function($){
    DDLayout_settings.DDL_JS.ns.ready(function(){
            DDLayout.css_page = new DDLayout.LayoutsCSS($);
    });
}(jQuery));

DDLayout.LayoutsCSS = function($){
        var self = this,
            attrs = {},
            $message_container = jQuery(".js-css-editor-message-container"),
            $area = jQuery('.js-ddl-css-editor-area'),
            css_string = $area.val();

    _.defaults(attrs, {css_string: ""});

        self.cssEditor = null;

    self.init = function(){
        self.set('css_string', css_string);
        self.cssEditor = new DDLayout.CssEditor(self);
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

    self.getCssString = function(){
        return self.get('css_string');
    };

    self.setCssString = function( css_string ){

        self.set('css_string', WPV_Toolset.Utils._strip_scripts( css_string) );
        return self;
    };

    self.get_layout_as_JSON = function(){
        return {};
    };

    self.save = function( callback ){
        var params = {
            action : 'save_layouts_css',
            'ddl_css_nonce' : DDLayout_settings.DDL_JS.ddl_css_nonce,
            css_string : self.getCssString()
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

                        // it is necessary to set current css value also to texteditor to prevent
                        // issues when browser is re-started, this is edge case but it is good to be covered
                        $( '.js-ddl-css-editor-area' ).val( self.getCssString() );

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


