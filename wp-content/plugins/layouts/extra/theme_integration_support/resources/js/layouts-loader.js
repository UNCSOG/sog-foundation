var DDLayout = DDLayout || {};

DDLayout.ThemeIntegrations = DDLayout.ThemeIntegrations || {};

DDLayout.ThemeIntegrations.LayoutsLoader = function($){
    var self = this, $button = $('.js-ddl-layouts-loader-button'), $messages = $('.js-upload-layouts-message'), loader = new WPV_Toolset.Utils.Loader;

    self.init = function(){
        self.handle_click();
    };

    self.handle_click = function(){
        $button.on('click', function(event){
            event.preventDefault();
            self.do_ajax(event);
        });
    };

    self.do_ajax = function(event){
        var params = {
            'ddl_load_default_layouts':DDLayout_Theme.ThemeIntegrationsSettings.ddl_load_default_layouts,
            'action':'ddl_load_default_layouts'
        }, load;

        if( pagenow != 'toolset_page_toolset-settings' ){
            load = loader.loadShow( $(event.target), true);
            load.css({
                "top": "14px",
                "position": "absolute",
                "right": "16px"
            });
        } else {
            $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );
        }

        WPV_Toolset.Utils.do_ajax_post(params,{

            success:function(response){
                self.messages(event, 'info', DDLayout_Theme.ThemeIntegrationsSettings.layouts_loaded);
            },
            error:function(response){
                self.messages(event, 'error', response.error)
            },
            fail:function(errorThrown){
                self.messages(event, 'error', errorThrown)
            },
            always:function(args){
                loader.loadHide();
            }
        });
    };

    self.messages = function( event, type, message ){
        if( typeof $(event.target).data('settings') === 'undefined' ){

            if( type === 'info' ){
                window.location.href = DDLayout_Theme.ThemeIntegrationsSettings.redirect_to + "&layouts_loaded=true";
            } else {
                window.location.href = DDLayout_Theme.ThemeIntegrationsSettings.redirect_to + "&layouts_loaded=false";
            }
            return;

        } else if( $(event.target).data('settings') === 'yes' ){

            $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
            
            if( type === 'info' ) {
                var parent = $(event.target).parent();
                $(event.target).remove();
                parent.append('<button href="#" class="button button-secondary" disabled="disabled">' + DDLayout_Theme.ThemeIntegrationsSettings.create_layouts + '</button>');
            }
        }
    };

    self.init();
};

(function ($) {
    $(function () {
        DDLayout.ThemeIntegrations.LayoutsLoader.call({}, $);
    });
}(jQuery));