var DDLayout = DDLayout || {};

DDLayout.SliderCell = function($)
{
    var self = this;
    
    jQuery(document).on('slider-cell.dialog-open', function(e, content, dialog) {
        if ( content.autoplay === false ){
            jQuery('input[name="ddl-layout-pause"]').prop( 'checked', false).prop('disabled',true);
        }else{
            jQuery('input[name="ddl-layout-pause"]').prop('disabled',false);
        }
        
        if (typeof content.image_size == 'undefined') {
            jQuery('#ddl-default-edit input[name="ddl-layout-image_size"]').each (function () {
                jQuery(this).prop( 'checked', jQuery(this).val() == 'cover');
            });
        }

        self.handle_scroll();
        self.init_pointer_event();
    });
    
    self.disable_hover_option = function(event){
        if (jQuery(this).prop('checked') === false ){
            jQuery('input[name="ddl-layout-pause"]').prop( 'checked', false).prop('disabled',true);
        }else{
            jQuery('input[name="ddl-layout-pause"]').prop('disabled',false);
        }
    };

    self.init_pointer_event = function(){
        jQuery('.js-ddl-question-mark').toolsetTooltip({
            additionalClass:'ddl-tooltip-info'
        });
    };



    self.handle_scroll = function(){
        var $button = jQuery('.js-ddl-repeat-field-button'),
            $content = jQuery('.js-ddl-dialog-content');

        $button.on('click', function(e){
            _.delay(function(){
                $content.animate({
                    scrollTop: $button[0].offsetTop,
                    duration: 100,
                    specialEasing: {
                        scrollTop: "easeInOutSine"
                    }
                });
            }, 200);
        });
    };

    jQuery('input[name="ddl-layout-autoplay"]').on('click', self.disable_hover_option);
};


(function($){
     $(function(){
        DDLayout.slider_cell =  new DDLayout.SliderCell($);
     });
}(jQuery));