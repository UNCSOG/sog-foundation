var DDLayout = DDLayout || {};

DDLayout._templateSettings = DDLayout._templateSettings || {
    escape: /\{\{([^\}]+?)\}\}(?!\})/g,
    evaluate: /<#([\s\S]+?)#>/g,
    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
};

DDLayout.CreateLayoutForPages = function($)
{
    var self = this
        , $button_custom = $('.js-create-layout-for-post-custom');

    self.post = null;

    self.init = function(){
        self.set_post();
        self.handle_assign_to_post_type();
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'ddl-create-dialog-opened', self.handle_dialog_open_overrides);
        // don't show button untill everything is complete
        _.defer(function(){
                $('.create-layout-for-page-wrap').show();
        });
    };

    self.open_create_dialog = function( who, for_whom )
    {
        var undefined, data;

        if( who === undefined ) {
            data = undefined;
        } else {
            data = {
                who:who,
                for_whom:for_whom
            };
            data = _.extend( data, self.post );
        }
        DDLayout.new_layout_dialog.show_create_new_layout_dialog( undefined, null, null, data );
    };

    self.handle_dialog_open_overrides = function(dialog)
    {
        var $title = $('.js-new-layout-title');

            $title.val( DDLayout_settings_create.DDL_JS.new_layout_title_text );
            $('.js-create-new-layout').prop('disabled', false).removeClass('button-secondary').addClass('button-primary');

    };

    self.set_post = function()
    {
        self.post = DDLayout_settings_create.DDL_JS.post;
    };


    self.handle_assign_to_post_type = function () {

        if( $button_custom.is('a') === false ) return;

        if ( $button_custom.is('a') ) {
            $(document).on('click', '.js-create-layout-for-post-custom', function(event){
                event.preventDefault();

                self.open_create_dialog( 'all','one' );
            });
        }
    };

    self.init();
};

(function($){
    $(function(){
        _.defer(function($){
            var create_for_pages = {};
            DDLayout.CreateLayoutForPages.call(create_for_pages, $);
        }, $);
    });
}(jQuery));
