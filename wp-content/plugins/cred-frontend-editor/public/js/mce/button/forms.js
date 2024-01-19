/**
* TinyMCE plugin for the shortcodes generator
*
* @since 2.2
* @package CRED
*/
( function() {
    tinymce.create( "tinymce.plugins.toolset_add_forms_shortcode_button", {

        /**
		 * Initialize the editor button.
		 *
		 * @param object ed The tinymce editor
		 * @param string url The absolute url of our plugin directory
		 */
        init: function( ed, url ) {

            // Add new button
            ed.addButton( "toolset_forms_shortcodes", {
                title : cred_shortcode_i18n.mce.forms.button,
                cmd : "toolset_forms_shortcodes_command",
                icon : 'icon icon-cred-logo ont-icon-23 ont-icon-block-classic-toolbar'
            });

            // Button command
            ed.addCommand( "toolset_forms_shortcodes_command", function() {
				window.wpcfActiveEditor = ed.id;
				Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'insert' );
                Toolset.CRED.shortcodeGUI.openCredDialog();
            });

        }
    });

    tinymce.PluginManager.add( "toolset_add_forms_shortcode_button", tinymce.plugins.toolset_add_forms_shortcode_button );
})();
