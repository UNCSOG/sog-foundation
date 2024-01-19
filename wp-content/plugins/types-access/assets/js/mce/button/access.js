/**
* TinyMCE plugin for the shortcodes generator
*
* @since 2.6
* @package Access
*/
( function() {
    tinymce.create( "tinymce.plugins.toolset_add_access_shortcode_button", {

        /**
		 * Initialize the editor button.
		 *
		 * @param object ed The tinymce editor
		 * @param string url The absolute url of our plugin directory
		 */
        init: function( ed, url ) {

            // Add new button
            ed.addButton( "toolset_access_shortcodes", {
                title: otg_access_shortcodes_gui_texts.mce.access.button,
                cmd: "toolset_access_shortcodes_command",
                icon: 'icon icon-access-logo ont-icon-23 ont-icon-block-classic-toolbar'
            });

            // Button command
            ed.addCommand( "toolset_access_shortcodes_command", function() {
				window.wpcfActiveEditor = ed.id;
				OTGAccess.shortcodes_gui.shortcode_gui_action = 'insert';
                OTGAccess.shortcodes_gui.openAccessDialog();
            });

        }
    });

    tinymce.PluginManager.add( "toolset_add_access_shortcode_button", tinymce.plugins.toolset_add_access_shortcode_button );
})();
