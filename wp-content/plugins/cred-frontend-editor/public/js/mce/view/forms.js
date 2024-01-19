/**
 * MCE views for Toolset Forms
 *
 * @since 2.2
 */
var Toolset = Toolset || {};
Toolset.Forms = Toolset.Forms || {};
Toolset.Forms.MCE = Toolset.Forms.MCE || {};

Toolset.Forms.MCE.ViewsClass = function( $, tinymce ) {
	var self = this;

	self.i18n = cred_shortcode_i18n;

	self.CONST = {
		previewClass: 'toolset-forms-shortcode-mce-view'
	};

	self.templates = {
		'cred_form': wp.template( 'toolset-shortcode-cred_form-mce-banner' ),
		'cred_user_form': wp.template( 'toolset-shortcode-cred_user_form-mce-banner' ),
		'cred-relationship-form': wp.template( 'toolset-shortcode-cred-relationship-form-mce-banner' )
	};

	self.toolbars = {};
	self.toolbarLink = null;

	// Define the button with the link to edit the View object when selecting the MCE view.
	tinymce.ui.Factory.add( 'ToolsetShortcodeCredFormEdit', tinymce.ui.Control.extend( {
		url: '#',
		renderHtml: function() {
			return (
				'<div id="' + this._id + '" class="toolset-shortcode-cred-form-preview wp-link-preview">' +
					'<a href="' + this.url + '" title="' + self.i18n.mce.forms.editLabel + '" target="_blank" tabindex="-1">' + self.i18n.mce.forms.editLabel + '</a>' +
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
				'<div id="' + this._id + '" class="toolset-shortcode-cred-form-preview wp-link-preview">' +
					self.i18n.mce.forms.missingObject +
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
		// Manage the cred_form shortcode
		content = content.replace( /\[cred_form([^\]]*)\]/g, function( all, attr ) {
			var shortcodeData = wp.shortcode.next( 'cred_form', all ),
				keymap = _.keys( shortcodeData.shortcode.attrs.named ).join( '|' );
			return self.templates['cred_form']({
				tag: shortcodeData.shortcode.tag,
				attributes: shortcodeData.shortcode.attrs.named,
				keymap: keymap
			});
		});

		// Manage the cred_user_form shortcode
		content = content.replace( /\[cred_user_form([^\]]*)\]/g, function( all, attr ) {
			var shortcodeData = wp.shortcode.next( 'cred_user_form', all ),
				keymap = _.keys( shortcodeData.shortcode.attrs.named ).join( '|' );
			return self.templates['cred_user_form']({
				tag: shortcodeData.shortcode.tag,
				attributes: shortcodeData.shortcode.attrs.named,
				keymap: keymap
			});
		});

		// Manage the cred-relationship-form shortcode
		content = content.replace( /\[cred-relationship-form([^\]]*)\]/g, function( all, attr ) {
			var shortcodeData = wp.shortcode.next( 'cred-relationship-form', all ),
				keymap = _.keys( shortcodeData.shortcode.attrs.named ).join( '|' );
			return self.templates['cred-relationship-form']({
				tag: shortcodeData.shortcode.tag,
				attributes: shortcodeData.shortcode.attrs.named,
				keymap: keymap
			});
		});

		return content;
	};

	// Define the Toolset Views shortcodes MCE view
	tinymce.PluginManager.add( 'toolset_forms_shortcode_view', function( editor ) {
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
				self.toolbars.editForms = editor.wp._createToolbar( [
					'toolset_forms_shortcode_edit',
					'toolset_forms_shortcode_remove'
				], true );

				self.toolbars.missing = editor.wp._createToolbar( [
					'toolset_forms_shortcode_missing',
					'toolset_forms_shortcode_remove'
				], true );

				self.toolbars.basic = editor.wp._createToolbar( [
					'toolset_forms_shortcode_remove'
				], true );

			}
		});

		// Custom button: Forms edit link.
		editor.addButton( 'toolset_forms_shortcode_edit', {
			type: 'ToolsetShortcodeCredFormEdit',
			onPostRender: function() {
				self.toolbarLink = this;
			},
			tooltip: self.i18n.mce.forms.editLabel,
			icon: 'dashicon dashicons-edit'
		});

		// Custom button: the view belongs to an unknown object.
		editor.addButton( 'toolset_forms_shortcode_missing', {
			type: 'ToolsetShortcodeWpvMissing',
			tooltip: self.i18n.mce.forms.missingObject,
			icon: 'dashicon dashicons-edit'
		});

		// Custom button: remove this view and the underlying shortcode.
		editor.addButton( 'toolset_forms_shortcode_remove', {
			tooltip: self.i18n.mce.forms.removeLabel,
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
				nodeClass = $linkNode.attr( 'class' ).split( ' ' );
				if ( _.contains( nodeClass, self.CONST.previewClass ) ) {
					event.element = linkNode;

					if ( ! self.i18n.mce.forms.canEdit ) {
						event.element = linkNode;
						event.toolbar = self.toolbars.basic;
						return;
					}

					var tag = $linkNode.attr( 'data-tag' );
					var slug = $linkNode.attr( 'data-form' ),
						id = 0,
						href = self.i18n.mce.forms.editLink;
					switch( tag ) {
						case 'cred_form':
							id = (
								// Form shortcode using slug
								_.has( Toolset.Forms.dataCache.forms.post, slug )
								? Toolset.Forms.dataCache.forms.post[ slug ].id
								: (
									// Form shortcode using title
									_.has( Toolset.Forms.dataCache.formsAlt.post, slug )
									? Toolset.Forms.dataCache.formsAlt.post[ slug ].id
									: id
								)
							);
							href += '&post=' + id;
							break;
						case 'cred_user_form':
							id = (
								// Form shortcode using slug
								_.has( Toolset.Forms.dataCache.forms.user, slug )
								? Toolset.Forms.dataCache.forms.user[ slug ].id
								: (
									// Form shortcode using title
									_.has( Toolset.Forms.dataCache.formsAlt.user, slug )
									? Toolset.Forms.dataCache.formsAlt.user[ slug ].id
									: id
								)
							);
							href += '&post=' + id;
							break;
						case 'cred-relationship-form':
							id = (
								// Form shortcode using slug
								_.has( Toolset.Forms.dataCache.forms.relationship, slug )
								? Toolset.Forms.dataCache.forms.relationship[ slug ].id
								: (
									// Form shortcode using title
									_.has( Toolset.Forms.dataCache.formsAlt.relationship, slug )
									? Toolset.Forms.dataCache.formsAlt.relationship[ slug ].id
									: id
								)
							);
							href = self.i18n.mce.forms.editRelationshipFormLink + '&id=' + id;
							break;
					}
					if ( id > 0 ) {
						event.toolbar = self.toolbars.editForms;
						self.toolbarLink.setURL( href );
					} else {
						event.toolbar = self.toolbars.missing
					}
				}
			}
		});

	});
};

jQuery( function( $ ) {
	Toolset.Forms.MCE.Views = new Toolset.Forms.MCE.ViewsClass( $, window.tinymce );
});
