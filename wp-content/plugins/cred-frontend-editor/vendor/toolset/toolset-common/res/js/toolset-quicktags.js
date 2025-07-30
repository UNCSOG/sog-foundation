var ToolsetCommon = ToolsetCommon || {};
ToolsetCommon.CodemirrorInstances	= ToolsetCommon.CodemirrorInstances || {};

if (typeof ToolsetCommon.initQuicktags !== 'function') {
	ToolsetCommon.initQuicktags = function( qtInstance, editorInstance ) {
		ToolsetCommon.CodemirrorInstances[ qtInstance.id ] = editorInstance;

		QTags._buttonsInit();

		var activeUrlEditor;

		for ( var buttonName in qtInstance.theButtons ) {

			if ( ! qtInstance.theButtons.hasOwnProperty( buttonName ) ) {
				return;
			}

			qtInstance.theButtons[buttonName].old_callback = qtInstance.theButtons[ buttonName ].callback;

			switch ( qtInstance.theButtons[ buttonName ].id ) {
				case 'img':
					qtInstance.theButtons[ buttonName ].callback = function( element, canvas, ed ) {
						var t = this,
							id = jQuery( canvas ).attr( 'id' ),
							selection = ToolsetCommon.CodemirrorInstances[ id ].getSelection(),
							e = "http://",
							g = prompt( quicktagsL10n.enterImageURL, e ),
							f = prompt( quicktagsL10n.enterImageDescription, "" );
						t.tagStart = '<img src="' + g + '" alt="' + f + '" />';
						selection = t.tagStart;
						t.closeTag(element, ed);
						ToolsetCommon.CodemirrorInstances[ id ].replaceSelection( selection, 'end' );
						ToolsetCommon.CodemirrorInstances[ id ].focus();
					}
					break;

				case 'wpv_conditional':
					qtInstance.theButtons[buttonName].callback = function( e, c, ed ) {
						if ( typeof WPViews === "undefined" ) {
							return;
						}
						if ( typeof WPViews.shortcodes_gui === "undefined" ) {
							return;
						}
						activeUrlEditor = ed;
						var id = jQuery( c ).attr( 'id' ),
							t = this;
							window.wpcfActiveEditor = id;
							WPV_Toolset.CodeMirror_instance[id].focus(),
							current_editor_object = {};
							selection = WPV_Toolset.CodeMirror_instance[id].getSelection();
						if ( selection ) {
								current_editor_object = {'e' : e, 'c' : c, 'ed' : ed, 't' : t, 'post_id' : '', 'close_tag' : true, 'codemirror' : id};
								WPViews.shortcodes_gui.wpv_insert_popup_conditional('wpv-conditional', icl_editor_localization_texts.wpv_insert_conditional_shortcode, {}, icl_editor_localization_texts.wpv_editor_callback_nonce, current_editor_object );
						} else if ( ed.openTags ) {
							// if we have an open tag, see if it's ours
							var ret = false, i = 0, t = this;
							while ( i < ed.openTags.length ) {
								ret = ed.openTags[i] == t.id ? i : false;
								i ++;
							}
							if ( ret === false ) {
								t.tagStart = '';
								t.tagEnd = false;
								if ( ! ed.openTags ) {
									ed.openTags = [];
								}
								ed.openTags.push(t.id);
								e.value = '/' + e.value;
								current_editor_object = {'e' : e, 'c' : c, 'ed' : ed, 't' : t, 'post_id' : '', 'close_tag' : false, 'codemirror' : id};
								WPViews.shortcodes_gui.wpv_insert_popup_conditional('wpv-conditional', icl_editor_localization_texts.wpv_insert_conditional_shortcode, {},icl_editor_localization_texts.wpv_editor_callback_nonce, current_editor_object );
							} else {
								// close tag
								ed.openTags.splice(ret, 1);
								t.tagStart = '[/wpv-conditional]';
								e.value = t.display;
								window.icl_editor.insert( t.tagStart );
							}
						} else {
							// last resort, no selection and no open tags
							// so prompt for input and just open the tag
							t.tagStart = '';
							t.tagEnd = false;
							if ( ! ed.openTags ) {
								ed.openTags = [];
							}
							ed.openTags.push(t.id);
							e.value = '/' + e.value;
							current_editor_object = {'e' : e, 'c' : c, 'ed' : ed, 't' : t, 'post_id' : '', 'close_tag' : false, 'codemirror' : id};
							WPViews.shortcodes_gui.wpv_insert_popup_conditional('wpv-conditional', icl_editor_localization_texts.wpv_insert_conditional_shortcode, {}, icl_editor_localization_texts.wpv_editor_callback_nonce, current_editor_object );
						}
					}
					break;

				case 'link':
					var t = this;
					qtInstance.theButtons[buttonName].callback = function( b, c, d, e ) {
						activeUrlEditor = c;
						var f, g = this;
						return "undefined" != typeof wpLink ? void wpLink.open(d.id) : (e || (e = "http://"), void(g.isOpen(d) === !1 ? (f = prompt(quicktagsL10n.enterURL, e), f && (g.tagStart = '<a href="' + f + '">', a.TagButton.prototype.callback.call(g, b, c, d))) : a.TagButton.prototype.callback.call(g, b, c, d)))
					};

					jQuery( '#wp-link-submit' ).off();
					jQuery( '#wp-link-submit' ).on(' click', function( event ) {
						event.preventDefault();
						if (wpLink.isMCE()) {
								wpLink.mceUpdate();
						} else {
							var id = jQuery( activeUrlEditor ).attr( 'id' ),
								selection = ToolsetCommon.CodemirrorInstances[ id ].getSelection(),
								inputs = {},
								attrs, text, title, html;

							inputs.wrap = jQuery( '#wp-link-wrap' );
							inputs.backdrop = jQuery( '#wp-link-backdrop' );
							if ( jQuery( '#link-target-checkbox' ).length > 0 ) {
									// Backwards compatibility - before WordPress 4.2
									inputs.text = jQuery( '#link-title-field' );
									attrs = wpLink.getAttrs();
									text = inputs.text.val();
									if ( ! attrs.href ) {
											return;
									}
									// Build HTML
									html = '<a href="' + attrs.href + '"';
									if ( attrs.target ) {
											html += ' target="' + attrs.target + '"';
									}
									if ( text ) {
											title = text.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
											html += ' title="' + title + '"';
									}
									html += '>';
									html += text || selection;
									html += '</a>';
									t.tagStart = html;
									selection = t.tagStart;
							} else {
								// WordPress 4.2+
								inputs.text = jQuery( '#wp-link-text' );
								attrs = wpLink.getAttrs();
								text = inputs.text.val();
								if ( ! attrs.href ) {
										return;
								}
								// Build HTML
								html = '<a href="' + attrs.href + '"';
								if (attrs.target) {
										html += ' target="' + attrs.target + '"';
								}
								html += '>';
								html += text || selection;
								html += '</a>';
								selection = html;
							}

							jQuery( document.body ).removeClass( 'modal-open' );
							inputs.backdrop.hide();
							inputs.wrap.hide();
							jQuery( document ).trigger( 'wplink-close', inputs.wrap );
							ToolsetCommon.CodemirrorInstances[ id ].replaceSelection( selection, 'end' );
							ToolsetCommon.CodemirrorInstances[ id ].focus();
							return false;
						}
					});
					break;

				default:
					qtInstance.theButtons[buttonName].callback = function( element, canvas, ed ) {
						var id = jQuery( canvas ).attr( 'id' ),
							t = this,
							selection = ToolsetCommon.CodemirrorInstances[ id ].getSelection();
						if ( selection.length > 0 ) {
								if ( ! t.tagEnd ) {
										selection = selection + t.tagStart;
								} else {
										selection = t.tagStart + selection + t.tagEnd;
								}
						} else {
								if ( ! t.tagEnd ) {
										selection = t.tagStart;
								} else if ( t.isOpen( ed ) === false ) {
										selection = t.tagStart;
										t.openTag( element, ed );
								} else {
										selection = t.tagEnd;
										t.closeTag( element, ed );
								}
						}
						ToolsetCommon.CodemirrorInstances[ id ].replaceSelection( selection, 'end' );
						ToolsetCommon.CodemirrorInstances[ id ].focus();
				}
				break;
			}
		}
	}
}