// Media uploader
var DDLayout = DDLayout || {};

(function($){
    $(function ($) {

        DDLayout.mediaUploader = new DDLayout.MediaUploader($);

    });
}(jQuery));



DDLayout.MediaUploader = function ($) {

    var self = this, file_frame;

    self.init = function () {
        self.add_media();
    };

    self.add_media = function () {
        jQuery(document).on('click', '.js-ddl-add-media', function (e) {
            e.preventDefault();

            var $uploadBtn = $(this);

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({

                title: $(this).data('uploader-title'),			// Title of WP Media Uploader frame
                button: {text: $(this).data('uploader-button-text')},	// Button text
                library: {type: 'image'},
    //			frame: 'post',
    //			displaySettings: true,
    //	        displayUserSettings: true,
                multiple: false  // True allows multiple files to be selected
            });

            self.file_frame_set_callback($uploadBtn);
            // Open the modal
            file_frame.open();
        });
    };

    self.file_frame_set_callback = function($uploadBtn){
        // Callback for selected image
        file_frame.on('select', function () {

            // We set multiple to false so only get one image from the uploader
            var attachment = file_frame.state().get('selection').first().toJSON();

            // Set value for the URL field
            var $field = $uploadBtn.closest('.js-ddl-media-field').find('.js-ddl-media-url'), name;
            $field.val(attachment.url).trigger('change', attachment);

            // make sure we are on a simple field or a repeater
            if ($field.prop('name').indexOf('[]') === -1) {
                name = 'ddl-layout-attachment_' + $field.prop('name').split('ddl-layout-')[1];
            } else {
                var index = $field.prop('name').indexOf('[]');
                name = $field.prop('name').insertAtIndex(index, '_attachment');
            }

            // creates and populates an additional field to store the attachment $id for API usage
            if ( $('input[name="' + name + '"]', $field.parent()).is('input') ) {
                $('input[name="' + name + '"]', $field.parent()).val(attachment.id).trigger('change', attachment);
            } else {
                var $hide = $('<input type="hidden" name="' + name + '" />');
                $field.after($hide);
                $hide.val(attachment.id).trigger('change', attachment);
            }
        });
    };

    self.init();
};
