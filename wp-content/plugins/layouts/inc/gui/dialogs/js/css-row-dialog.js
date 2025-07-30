DDLayout.CSSRowDialog = function($)
{
    var self = this;

    self.init = function() {

        self._dialog_defaults = {};

        jQuery(document).on('click', '.js-dialog-css-edit-save', {dialog: self}, function(event) {
            event.preventDefault();
            event.data.dialog._save();
        });
    };

    self.show = function(view) {

        self._clear_any_errors();

        jQuery('#wrapper-cell-css-editor-box').data('view', view);

        var view_model = view.model;

        jQuery('input[name="css-editor-box-name"]').val(view_model.get('name') );
        jQuery('input[name="css-editor-css-class"]').val(view_model.get('cssClass'));
        jQuery('input[name="css-editor-css-id"]').val( view_model.get('cssId') );
        jQuery('input[name="css-editor-additional-classes"]').val( view_model.get('additionalCssClasses') );

        self._show_colorbox();
    };

    self._show_colorbox = function () {
        jQuery.colorbox({
            href: '#wrapper-row-css-editor-box',
            closeButton:false,
            onComplete: function() {


            },
            onLoad:function()
            {


            },
            onCleanup: function () {

            }
        });

    };

    self._save = function () {

        var target_cell_view = jQuery('#wrapper-row-css-editor-box').data('view'),
            view_model = target_cell_view.model;

        view_model.set( 'name', jQuery('input[name="css-editor-box-name"]').val( ), {silent:true} );
        view_model.set( 'cssClass', jQuery('input[name="css-editor-css-class"]').val( ), {silent:true} );
        view_model.set( 'cssId', jQuery('input[name="css-editor-css-id"]').val( ), {silent:true} );
        view_model.set( 'additionalCssClasses', jQuery('input[name="css-editor-additional-classes"]').val( ), {silent:true} );

        jQuery.colorbox.close();
        target_cell_view.selected_cell = true;
        target_cell_view.eventDispatcher.trigger('re_render_all')
        return false;
    };

    self._clear_any_errors = function () {
        // disable this for now because we are showing a
        // message to say that the dialog is not complete.
        
        //jQuery('.ddl-dialog .toolset-alert').remove();
    };


    self.init();
};