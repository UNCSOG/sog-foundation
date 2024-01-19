var ToolsetThemeSettings = ToolsetThemeSettings || {};
/**
 *
 * @param $
 * @constructor
 */
ToolsetThemeSettings.AvadaSettings = function ( $ ) {
    var self = this,
        $checkbox = $('input[name="toolset-fusion-compilers"]'),
        ACTION = 'save_fusion_compiler_option',
        NONCE = $('#toolset_fusion-compilers_nonce').val(),
        $messages = $('.js-toolset-fusion-compilers-message')

    self.init = function () {
        self.eventsOn();
    };

    self.makeAjaxCall = function ( data, successCallback, errorCallback ) {
        $.ajax({
            async: true,
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: successCallback,
            error: errorCallback
        });
    };

    self.eventsOn = function () {
        $checkbox.on('change', self.handleChange);
    };

    self.handleChange = function ( event ) {
        var data = {
            action: ACTION,
            nonce: NONCE

        };
        if ( $(this).is(':checked') ) {
            data.fusion_compilers_on = 1;
        } else {
            data.fusion_compilers_on = 0;
        }

        $(document).trigger('js-toolset-event-update-setting-section-triggered');

        self.makeAjaxCall(data, self.successCallback, self.errorCallback);
    };

    self.successCallback = function ( response ) {
        $(document).trigger('js-toolset-event-update-setting-section-completed');
        $messages.wpvToolsetMessage({
            text: response.data.message,
            type: 'success',
            inline: true,
            stay: false
        });
    };

    self.errorCallback = function ( response ) {
        $(document).trigger('js-toolset-event-update-setting-section-failed');
        $messages.wpvToolsetMessage({
            text: response.data.message,
            type: 'error',
            inline: true,
            stay: false
        });
    };

    self.init();

};

jQuery(function ( $ ) {
    ToolsetThemeSettings.avada_settings = new ToolsetThemeSettings.AvadaSettings($);
});
