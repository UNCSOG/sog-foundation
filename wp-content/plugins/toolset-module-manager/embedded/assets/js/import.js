/**
 Toolset Import
 @since 1.8
 */
jQuery( function( $ ) {
    jQuery(document).on("click", ".js-toggle-module-section", function(){
        var $area = jQuery('.js-modules-list-area');
        if ( jQuery(this).data('area') == 'demo-content' ){
            $area = jQuery('.js-demo-content-list-area');
        }

        if ( $area.hasClass('hidden') ) {
            $area.removeClass('hidden');
        } else{
            $area.addClass('hidden');
        }
    });

    jQuery(document).on("change", ".js-modman-enable-demo-content", function(){
        if ( jQuery(this).prop('checked') ) {
            jQuery('.js-demo-content-list-area').find('input').val('1').prop('checked', true);
        } else {
            jQuery('.js-demo-content-list-area').find('input').val('0').prop('checked', false);
        }
    });


});
