/**
 * Gutenberg integration for theme settings in Content Templates.
 *
 * Manages events and defaults for theme settings included
 * in the Block editor for a Content Template.
 *
 * @since 1.3.3
 */
var ToolsetCommon = ToolsetCommon || {};

ToolsetCommon.ThemeIntegrationGutenberg = function ($) {
    var self = this;

    self.init = function() {

        // Manage the saving event: serialize data to be properly posted.
        var editor = window.wp.data.select('core/editor');

        window.wp.data.subscribe( function() {
            var isSavingPost = editor.isSavingPost(),
                isAutosavingPost = editor.isAutosavingPost();
            if ( isSavingPost && ! isAutosavingPost ) {
                var serializedThemeSettings = $( '#js-toolset-theme-settings-form-wrap' )
                    .find( 'select, textarea, input' )
                    .serialize();
                $( '#js-toolset-theme-block-editor-serialized-data' ).val( serializedThemeSettings );
            }
        } );

        // Manage options combos.
        $( document ).on( 'change', '.js-theme-option-switch-control', function() {
            var controlContainer = $( this ).closest( '.js-theme-option-switch-container' ),
                controlValue = controlContainer.find( '.js-theme-option-switch-control:checked' ).val(),
                controlInput = controlContainer.find( '.theme-option-switch-target-input' ),
                colorPickerWrap = controlContainer.find( '.theme-options-color-picker-wrap' );

            if ( 'toolset_use_theme_setting' == controlValue ) {
                controlInput.prop( 'disabled', true );
                controlInput.val('');
                colorPickerWrap.hide();
            } else {
                controlInput.prop( 'disabled', false );
                colorPickerWrap.show();
            }
        });

        // Manage styles.
        $( '.js-toolset-theme-settings-single-option-wrap' ).addClass( 'components-panel__row' );
        $( '.js-theme-option-switch-container' )
            .closest( '.js-toolset-theme-settings-single-option-wrap' )
                .css( { 'margin-top': '5px' } )
                .removeClass( 'components-panel__row' );

        // Initialize colorpickers
        $( '.js-theme-settings-colorpicker' ).wpColorPicker();

        return self;
    };

    self.init();

    return self;
};

jQuery( function( $ ) {
    ToolsetCommon.theme_integration_gutenberg = new ToolsetCommon.ThemeIntegrationGutenberg( $ );
});
