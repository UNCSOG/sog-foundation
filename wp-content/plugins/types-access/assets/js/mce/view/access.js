/**
 * MCE views for Toolset Views
 *
 * @since 2.6
 */
var Toolset = Toolset || {};
Toolset.Access = Toolset.Access || {};
Toolset.Access.MCE = Toolset.Access.MCE || {};

Toolset.Access.MCE.ViewsClass = function( $, tinymce ) {
	var self = this;

	self.i18n = otg_access_shortcodes_gui_texts;

	self.CONST = {
		previewClass: 'toolset-access-shortcode-mce-view'
	};

	self.templates = {
		'toolset_access': wp.template( 'toolset-shortcode-toolset_access-mce-banner' )
	};

	self.toolbars = {};
	self.toolbarLinks = {
		'toolset_access':  null
	};

	/**
	 * Get an attribute value from a string.
	 *
	 * @param string s
	 * @param string n Attribute key
	 * @return string
	 * @since 2.6
	 */
	self.getAttr = function( s, n ) {
		n = new RegExp( n + '=\"([^\"]+)\"', 'g' ).exec( s );
		return n ?  window.decodeURIComponent( n[1] ) : '';
	};

	/**
	 * Restor the shortcodes before saving data.
	 *
	 * @param string content
	 * @return string
	 * @since 2.6
	 */
	self.restoreShortcodes = function( content ) {
		var rx = new RegExp( "<div class=\"" + self.CONST.previewClass + "\".*?>(.*?)</div>", "g" );
		return content.replace( rx, function( match ) {
			var tag = self.getAttr( match, 'data-tag' ),
				keymap = self.getAttr( match, 'data-keymap' ),
				innerContent = $('<div />',{
					html: match
				  }).find( '.js-toolset-access-shortcode-mce-view-content' ).html();// FIND THE SPAN INSIDE AND READ ITS CONTENT
			if ( keymap ) {
				var outcome = '[' + tag,
					keys = keymap.split( '|' );
				_.each( keys, function( attrKey, index, list ) {
					var attrValue = self.getAttr( match, attrKey );
					if ( attrValue ) {
						outcome += ' ' + attrKey + '="' + attrValue + '"';
					}
				});
				outcome += ']';
				outcome += innerContent;
				outcome += '[/' + tag + ']';
				return outcome;
			}
			return match;
		});
	};

	/**
	 * Replace shortcodes by their HTML views.
	 *
	 * @param string content
	 * @return string
	 * @since 2.6
	 */
	self.replaceShortcodes = function( content ) {
		// Manage the toolset_access shortcode
		content = content.replace( /\[toolset_access([^\]]*)\]([\s\S]*?)\[\/toolset_access\]/g, function( all, attr ) {
			var shortcodeData = wp.shortcode.next( 'toolset_access', all ),
				keymap = _.keys( shortcodeData.shortcode.attrs.named ).join( '|' );
				console.log(shortcodeData);
				// The shortcode content is getting escaped...
			return self.templates['toolset_access']({
				tag: shortcodeData.shortcode.tag,
				attributes: shortcodeData.shortcode.attrs.named,
				keymap: keymap,
				content: shortcodeData.shortcode.content
			});
		});

		return content;
	};

	// Define the Toolset Access shortcodes MCE view
	tinymce.PluginManager.add( 'toolset_access_shortcode_view', function( editor ) {
		// Restre shortcodes before saving data.
		editor.on( 'GetContent', function( event ) {
			event.content = self.restoreShortcodes( event.content );
		});

		// Replace shortcodes by their views when rendering the editor.
		editor.on( 'BeforeSetcontent', function( event ) {
			event.content = self.replaceShortcodes( event.content );
		});

		// Define the toolbars that our views will use.
		editor.on( 'preinit', function() {
			if ( editor.wp && editor.wp._createToolbar ) {
				self.toolbar = editor.wp._createToolbar( [
					'toolset_access_shortcode_remove'
				], true );
			}
		});

		// Custom button: remove this view and the underlying shortcode.
		editor.addButton( 'toolset_access_shortcode_remove', {
			tooltip: 'Remove',
			icon: 'dashicon dashicons-no',
			onclick: function() {
				editor.fire( 'cut' );
			}
		});

		// Set the right toolbar depending on the view.
		editor.on( 'wptoolbar', function( event ) {
			var linkNode = editor.dom.getParent( event.element, 'div' ),
				$linkNode, href, nodeClass;

			if ( linkNode ) {
				$linkNode = editor.$( linkNode );
				nodeClass = $linkNode.attr( 'class' );
				if ( nodeClass == self.CONST.previewClass ) {
					event.element = linkNode;
					event.toolbar = self.toolbar;
				}
			}
		});

	});
};

jQuery( function( $ ) {
	Toolset.Access.MCE.Views = new Toolset.Access.MCE.ViewsClass( $, window.tinymce );
});
