/**
 * MCE views for Toolset Views
 *
 * @since 2.7
 */
var Toolset = Toolset || {};
Toolset.Views = Toolset.Views || {};
Toolset.Views.MCE = Toolset.Views.MCE || {};

Toolset.Views.MCE.ViewsClass = function( $, tinymce ) {
	var self = this;

	self.i18n = wpv_shortcodes_gui_texts;

	self.CONST = {
		previewClass: 'toolset-views-shortcode-mce-view'
	};

	self.templates = {
		'wpv-post-body': wp.template( 'toolset-shortcode-wpv-post-body-mce-banner' ),
		'wpv-view': wp.template( 'toolset-shortcode-wpv-view-mce-banner' )
	};

	self.toolbars = {};
	self.toolbarLinks = {
		'wpv-post-body': null,
		'wpv-view':  null
	};

	// Define the button with the link to edit the View object when selecting the MCE view.
	tinymce.ui.Factory.add( 'ToolsetShortcodeWpvViewEdit', tinymce.ui.Control.extend( {
		url: '#',
		renderHtml: function() {
			return (
				'<div id="' + this._id + '" class="toolset-shortcode-wpv-view-preview wp-link-preview">' +
					'<a href="' + this.url + '" title="' + self.i18n.mce.views.editViewLabel + '" target="_blank" tabindex="-1">' + self.i18n.mce.views.editViewLabel + '</a>' +
				'</div>'
			);
		},
		setURL: function( url ) {
			if ( this.url !== url ) {
				this.url = url;
				tinymce.$( this.getEl().firstChild ).attr( 'href', this.url );
			}
		}
	}));

	// Define the button with the link to edit the Content Template object when selecting the MCE view.
	tinymce.ui.Factory.add( 'ToolsetShortcodeWpvPostBodyEdit', tinymce.ui.Control.extend( {
		url: '#',
		renderHtml: function() {
			return (
				'<div id="' + this._id + '" class="toolset-shortcode-wpv-view-preview wp-link-preview">' +
					'<a href="' + this.url + '" title="' + self.i18n.mce.views.editTemplateLabel + '" target="_blank" tabindex="-1">' + self.i18n.mce.views.editTemplateLabel + '</a>' +
				'</div>'
			);
		},
		setURL: function( url ) {
			if ( this.url !== url ) {
				this.url = url;
				tinymce.$( this.getEl().firstChild ).attr( 'href', this.url );
			}
		}
	}));

	// Define the button with the warning about the missing object when selecting the MCE view.
	tinymce.ui.Factory.add( 'ToolsetShortcodeWpvMissing', tinymce.ui.Control.extend( {
		url: '#',
		renderHtml: function() {
			return (
				'<div id="' + this._id + '" class="toolset-shortcode-wpv-view-preview wp-link-preview">' +
					self.i18n.mce.views.missingObject +
				'</div>'
			);
		}
	}));

	/**
	 * Get an attribute value from a string.
	 *
	 * @param string s
	 * @param string n Attribute key
	 * @return string
	 * @since 2.7
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
	 * @since 2.7
	 */
	self.restoreShortcodes = function( content ) {
		var rx = new RegExp( "<div class=\"" + self.CONST.previewClass + ".*?>(.*?)</div>", "g" );
		return content.replace( rx, function( match ) {
			var tag = self.getAttr( match, 'data-tag' ),
				keymap = self.getAttr( match, 'data-keymap' );
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
	 * @since 2.7
	 */
	self.replaceShortcodes = function( content ) {
		// Manage the wpv-post-body shortcode
		content = content.replace( /\[wpv-post-body([^\]]*)\]/g, function( all, attr ) {
			var shortcodeData = wp.shortcode.next( 'wpv-post-body', all ),
				keymap = _.keys( shortcodeData.shortcode.attrs.named ).join( '|' );
			return self.templates['wpv-post-body']({
				tag: shortcodeData.shortcode.tag,
				attributes: shortcodeData.shortcode.attrs.named,
				keymap: keymap
			});
		});

		// Manage the wpv-view shortcode
		content = content.replace( /\[wpv-view([^\]]*)\]/g, function( all, attr ) {
			var shortcodeData = wp.shortcode.next( 'wpv-view', all ),
				keymap = _.keys( shortcodeData.shortcode.attrs.named ).join( '|' );
			return self.templates['wpv-view']({
				tag: shortcodeData.shortcode.tag,
				attributes: shortcodeData.shortcode.attrs.named,
				keymap: keymap
			});
		});

		return content;
	};

	// Define the Toolset Views shortcodes MCE view
	tinymce.PluginManager.add( 'toolset_views_shortcode_view', function( editor ) {
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
				self.toolbars.editView = editor.wp._createToolbar( [
					'toolset_wpv_views_shortcode_edit',
					'toolset_views_shortcode_remove'
				], true );

				self.toolbars.editTemplate = editor.wp._createToolbar( [
					'toolset_wpv_post_body_shortcode_edit',
					'toolset_views_shortcode_remove'
				], true );

				self.toolbars.missing = editor.wp._createToolbar( [
					'toolset_views_shortcode_missing',
					'toolset_views_shortcode_remove'
				], true );

				self.toolbars.basic = editor.wp._createToolbar( [
					'toolset_views_shortcode_remove'
				], true );

			}
		});

		// Custom button: Views edit link.
		editor.addButton( 'toolset_wpv_views_shortcode_edit', {
			type: 'ToolsetShortcodeWpvViewEdit',
			onPostRender: function() {
				self.toolbarLinks['wpv-view'] = this;
			},
			tooltip: self.i18n.mce.views.editViewLabel,
			icon: 'dashicon dashicons-edit'
		});

		// Custom button: Content Template edit link.
		editor.addButton( 'toolset_wpv_post_body_shortcode_edit', {
			type: 'ToolsetShortcodeWpvPostBodyEdit',
			onPostRender: function() {
				self.toolbarLinks['wpv-post-body'] = this;
			},
			tooltip: self.i18n.mce.views.editTemplateLabel,
			icon: 'dashicon dashicons-edit'
		});

		// Custom button: the view belongs to an unknown object.
		editor.addButton( 'toolset_views_shortcode_missing', {
			type: 'ToolsetShortcodeWpvMissing',
			tooltip: self.i18n.mce.views.missingObject,
			icon: 'dashicon dashicons-edit'
		});

		// Custom button: remove this view and the underlying shortcode.
		editor.addButton( 'toolset_views_shortcode_remove', {
			tooltip: self.i18n.mce.views.removeLabel,
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
				nodeClass = ( $linkNode.attr( 'class' ) || '' ).split( ' ' );
				if ( _.contains( nodeClass, self.CONST.previewClass ) ) {
					event.element = linkNode;

					if ( ! self.i18n.mce.views.canEdit ) {
						event.element = linkNode;
						event.toolbar = self.toolbars.basic;
						return;
					}

					var tag = $linkNode.attr( 'data-tag' );
					switch ( tag ) {
						case 'wpv-view':
							var slug = $linkNode.attr( 'data-name' ),
								id = ( _.has( WPViews.dataCache.views, slug ) ) ? WPViews.dataCache.views[ slug ].id : 0,
								href = self.i18n.mce.views.editViewLink;
							if ( id > 0 ) {
								href += '&view_id=' + id;
								event.toolbar = self.toolbars.editView;
								self.toolbarLinks['wpv-view'].setURL( href );
							} else {
								event.toolbar = self.toolbars.missing
							}
							break;
						case 'wpv-post-body':
							var slug = $linkNode.attr( 'data-view_template' ),
								id = ( _.has( WPViews.dataCache.templates, slug ) ) ? WPViews.dataCache.templates[ slug ].id : 0,
								href = self.i18n.mce.views.editTemplateLink;
							if ( 'None' == slug ) {
								event.toolbar = self.toolbars.basic;
							} else if ( id > 0 ) {
								href += '&ct_id=' + id;
								event.toolbar = self.toolbars.editTemplate;
								self.toolbarLinks['wpv-post-body'].setURL( href );
							} else {
								event.toolbar = self.toolbars.missing
							}
							break;
					}
				}
			}
		});

	});
};

jQuery( document ).ready( function( $ ) {
	Toolset.Views.MCE.Views = new Toolset.Views.MCE.ViewsClass( $, window.tinymce );
});
