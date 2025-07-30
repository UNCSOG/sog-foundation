/**
 * plugin admin area javascript
 */
(function($) {
    $(function() {

        let wp_all_export_security = typeof wp_all_export_object !== 'undefined' ? wp_all_export_object.security : wp_all_export_security;
        let ajaxurl = typeof wp_all_export_object !== 'undefined' ? wp_all_export_object.ajaxurl : ajaxurl;

        $('.wpae-general-notice-dismiss').on('click', function() {
            let $parent = $(this).parent();
            let noticeId = $(this).attr('data-noticeId');

            let request = {
                action: 'dismiss_warnings',
                data: {
                    notice_id: noticeId
                },
                security: wp_all_export_security
            };

            $parent.slideUp();

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: request,
                success: function(response) {},
                dataType: "json"
            });
        });
    });
})(jQuery);
