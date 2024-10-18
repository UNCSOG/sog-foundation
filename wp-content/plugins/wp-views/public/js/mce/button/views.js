/**
* TinyMCE plugin for the Fields and Views shortcodes generator
*
* @since 2.7
* @package Views
*/
( function() {
    tinymce.create( "tinymce.plugins.toolset_add_views_shortcode_button", {

		/**
		 * Initialize the editor button.
		 *
		 * @param object ed The tinymce editor
		 * @param string url The absolute url of our plugin directory
		 */
        init: function( ed, url ) {

            // Add new button
            ed.addButton( "toolset_views_shortcodes", {
                title: wpv_shortcodes_gui_texts.mce.views.button,
                cmd: "toolset_views_shortcodes_command",
                icon: 'icon icon-views-logo ont-icon-23 ont-icon-block-classic-toolbar'
            });

            // Button command
            ed.addCommand( "toolset_views_shortcodes_command", function() {
				window.wpcfActiveEditor = ed.id;
				Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-set-gui-action', 'insert' );
                WPViews.shortcodes_gui.open_fields_and_views_dialog();
			});

        }
    });

    tinymce.PluginManager.add( "toolset_add_views_shortcode_button", tinymce.plugins.toolset_add_views_shortcode_button );
})();




