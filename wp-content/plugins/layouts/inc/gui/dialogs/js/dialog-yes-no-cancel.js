// dialog-yes-no-cancel.js

DDLayout.DialogYesNoCancel = function(header, text, buttons, callback)
{
    var self = this;

    self.init = function(header, text, buttons, callback) {

        DDLayout.ddl_admin_page.clean_up_default_dialog();

        self.callback = callback;

        jQuery.colorbox({
            href: '#ddl-yes-no-cancel',
            closeButton:false,
            top: '35%',
            onComplete: function() {

                self._disable_buttons(false);

                jQuery('#ddl-yes-no-cancel .js-dialog-title').text(header);
                jQuery('#ddl-yes-no-cancel .ddl-dialog-content').html(text);

                if (buttons) {
                    if ('yes' in buttons) {
                        jQuery('#ddl-yes-no-cancel .js-dialog-yes').text(buttons['yes']);
                    } else {
                        jQuery('#ddl-yes-no-cancel .js-dialog-yes').text(DDLayout_settings.DDL_JS.dialog_yes);
                    }
                    if ('no' in buttons) {
                        jQuery('#ddl-yes-no-cancel .js-dialog-no').text(buttons['no']);
                    } else {
                        jQuery('#ddl-yes-no-cancel .js-dialog-no').text(DDLayout_settings.DDL_JS.dialog_no);
                    }
                }

                jQuery(document).on('click', '#ddl-yes-no-cancel .js-dialog-close', {dialog : self}, function(event) {
                    jQuery.colorbox.close();
                    self.callback('cancel');
                });
                jQuery(document).on('click', '#ddl-yes-no-cancel .js-dialog-yes', {dialog : self}, function(event) {
                    //jQuery.colorbox.close(); // HACK: Closing the colorbox here can cause the next colorbox not to open.
                    self._disable_buttons(true);
                    self.callback('yes');
                });
                jQuery(document).on('click', '#ddl-yes-no-cancel .js-dialog-no', {dialog : self}, function(event) {
                    //jQuery.colorbox.close(); // HACK: Closing the colorbox here can cause the next colorbox not to open.
                    self._disable_buttons(true);
                    self.callback('no');
                });

                // Trigger save button click when ENTER key is pressed
                jQuery(document).on('keyup.colorbox', function(e) {
                    if (e.key === "Enter" || e.key === "Escape") {
                        jQuery('#cboxWrapper .js-dialog-close').trigger('click');
                    }
                });

            },
            onCleanup: function() {
                jQuery(document).off('click', '#ddl-yes-no-cancel .js-dialog-close');
                jQuery(document).off('click', '#ddl-yes-no-cancel .js-dialog-yes');
                jQuery(document).off('click', '#ddl-yes-no-cancel .js-dialog-no');
                jQuery(document).off('keyup.colorbox');
            }
        });

    };

    self._disable_buttons = function (state) {
        jQuery('#ddl-yes-no-cancel .js-dialog-close').prop('disabled', state);
        jQuery('#ddl-yes-no-cancel .js-dialog-yes').prop('disabled', state);
        jQuery('#ddl-yes-no-cancel .js-dialog-no').prop('disabled', state);

    }

    self.init(header, text, buttons, callback);
};
