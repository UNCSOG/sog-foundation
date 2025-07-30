var DDLayout = DDLayout || {};

DDLayout.VideoCell = function($)
{
    var self = this;

        self.$input = '';
        self.$button = '' ;
        self.$message = $('#js-video-message');


    self.init = function()
    {
        $(document).on('video-cell.dialog-open', self.dialog_open);
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'color_box_closed', self.clean_up )
    };

    self.dialog_open = function(event)
    {
        self.$input = $('input[name="ddl-layout-video_url"]')
        self.$button = $('.js-dialog-edit-save');

        if( self.$input.val() == '' )
        {
            self.$button.prop('disabled', true );
        }

        $(document).on('change', 'input[name="ddl-layout-video_url"]', self.handle_change);

    };

    self.handle_change = function(event)
    {
        var video_url = $(this).val();

        if( validate_you_tube_video_url( video_url ) === false )
        {
            self.$message.wpvToolsetMessage({
                text:  DDLayout_settings.DDL_JS.strings.video_message_text,
                tag:'p',
                stay: true,
                close: false,
                type: 'notice'
            });
            self.$button.prop('disabled', true);
        }
        else
        {
            self.$message.wpvToolsetMessage('destroy');
            self.$button.prop('disabled', false);
        }
    };

    self.clean_up = function(event)
    {
        $(document).off('change', self.handle_change);
        self.$message.wpvToolsetMessage('destroy');
    };

     var validate_you_tube_video_url = function( video_url ){
        var regexp = /^(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?(?=.*v=((\w|-){11}))(?:\S+)?$/;

        return regexp.test( video_url );
    };


    self.init();

};


(function($){
     $(function(){
            DDLayout.video_cell =  new DDLayout.VideoCell($);
     });
}(jQuery));
