var WPViews = WPViews || {};

// @todo Proper naming conventions for the inline CT editor, CSS editor and JS editor,
// together with their WPV_Toolset.CodeMirror_instance keys,
// so we can properly use wpv-action-wpv-edit-screen-delete-codemirror-editor
// as it also includes deleting on window.iclCodemirror
// which has another entirely diferent set of keys :-(

WPViews.ViewEditScreenInlineCT = function( $ ) {

	var self = this;

	self.i18n = wpv_inline_templates_i18n;

	self.view_id				= $('.js-post_ID').val();
	self.current_ct_id			= 0;
	self.current_ct_container	= null;

	self.codemirror_highlight_options = {
		className: 'wpv-codemirror-highlight'
	};
	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.shortcodeDialogSpinnerContent = $(
        '<div style="min-height: 150px;">' +
            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
                '<div class="wpv-spinner ajax-loader"></div>' +
                '<p>' + wpv_inline_templates_i18n.dialog.loading + '</p>' +
            '</div>' +
        '</div>'
    );

	// ---------------------------------
	// Inline Content Template add dialog management
	// ---------------------------------

	// Open dialog

	$( document ).on( 'click', '.js-wpv-ct-assign-to-view', function() {
		var dialog_height = $( window ).height() - 100;
		self.dialog_assign_ct.dialog( "open" ).dialog({
			maxHeight:	dialog_height,
			draggable:	false,
			resizable:	false,
			position:	{
				my:			"center top+50",
				at:			"center top",
				of:			window,
				collision:	"none"
			}
		});

		self.dialog_assign_ct.html( self.shortcodeDialogSpinnerContent );

		var data = {
			action:		'wpv_assign_ct_to_view',
			view_id:	$( this ).data('id'),
			wpnonce:	$( '#wpv_inline_content_template' ).attr( 'value' )
		};
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					self.dialog_assign_ct.html( response.data.dialog_content );
					$( '.js-wpv-assign-ct-already, .js-wpv-assign-ct-existing, .js-wpv-assign-ct-new' ).hide();
					$( '.js-wpv-inline-template-type' )
						.first()
							.trigger( 'click' );
					$( '.js-wpv-assign-inline-content-template' )
						.prop( 'disabled', true )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' );
					}
				},
			error:		function ( ajaxContext ) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete:	function() {

			}
		});
	});

	// Manage changes

	$( document ).on( 'change', '.js-wpv-inline-template-type', function() {
		var thiz = $( this ),
		thiz_val = thiz.val();
		$( '.js-wpv-assign-ct-already, .js-wpv-assign-ct-existing, .js-wpv-assign-ct-new' ).hide();
		$( '.js-wpv-assign-ct-' + thiz_val ).fadeIn( 'fast' );

		switch ( thiz_val ) {
			case 'already':
				if ( $( '.js-wpv-inline-template-assigned-select' ).val() == 0 ) {
					$( '.js-wpv-assign-inline-content-template' )
						.prop( 'disabled', true )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' );
				} else {
					$( '.js-wpv-assign-inline-content-template' )
						.prop( 'disabled', false )
						.removeClass( 'button-secondary' )
						.addClass( 'button-primary' );
				}
				$( '.js-wpv-inline-template-insert' ).hide();
				break;
			case 'existing':
				if ( $( '.js-wpv-inline-template-existing-select').val() == 0 ) {
					$( '.js-wpv-assign-inline-content-template' )
						.prop( 'disabled', true )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' );
				} else {
					$( '.js-wpv-assign-inline-content-template' )
						.prop( 'disabled', false )
						.removeClass( 'button-secondary' )
						.addClass( 'button-primary' );
				}
				$( '.js-wpv-inline-template-insert' ).show();
				break;
			case 'new':
				if ( $( '.js-wpv-inline-template-new-name' ).val() == '' ) {
					$( '.js-wpv-assign-inline-content-template' )
						.prop( 'disabled', true )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' );
				} else {
					$('.js-wpv-assign-inline-content-template')
						.prop( 'disabled', false )
						.removeClass( 'button-secondary' )
						.addClass( 'button-primary' );
				}
				$( '.js-wpv-inline-template-insert' ).show();
				break;
		}
	});

	$( document ).on( 'change', '.js-wpv-inline-template-assigned-select', function() {
		if ( $( '.js-wpv-inline-template-assigned-select' ).val() == 0 ) {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		} else {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', false )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary' );
		}
	});

	$( document ).on( 'change', '.js-wpv-inline-template-existing-select', function() {
		if ( $( '.js-wpv-inline-template-existing-select').val() == 0 ) {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		} else {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', false )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary' );
		}
	});

	$( document ).on( 'change keyup input cut paste', '.js-wpv-inline-template-new-name', function() {
		$( '.js-wpv-add-new-ct-name-error-container .toolset-alert' ).remove();
		if ( $( '.js-wpv-inline-template-new-name' ).val() == '' ) {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		} else {
			$('.js-wpv-assign-inline-content-template')
				.prop( 'disabled', false )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary' );
		}
	});

	// Submit

	$( document ).on( 'click','.js-wpv-assign-inline-content-template', function() {
		// On AJAX, both #wpv_inline_content_template and #wpv-ct-inline-edit are valid nonces
		var thiz			= $( this ),
		send_ajax			= true,
		template_id			= false,
		template_name		= '',
		type				= $( '.js-wpv-inline-template-type:checked' ).val(),
		mode				= '',
		spinnerContainer	= $('<div class="wpv-spinner ajax-loader auto-update">').insertAfter( thiz ).show(),
		data = {
			action: self.i18n.ajax.action.add_inline_content_template,
			wpnonce: self.i18n.ajax.nonce.add_inline_content_template
		};

		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );

		switch ( type ) {
			case 'existing':
				if ( $( '.js-wpv-inline-template-existing-select' ).val() == '' ) {
					return;
				}
				template_id		= $( '.js-wpv-inline-template-existing-select' ).val();
				template_name	= $( '.js-wpv-inline-template-existing-select option:selected' ).data( 'ct-name' );
				mode			= 'assign';
				data = Object.assign(
					{
						mode: mode,
						view_id: $( '.js-wpv-ct-assign-to-view' ).data( 'id' ),
						template_id: template_id,
					},
					data
				);
				break;
			case 'new':
				if ( $( '.js-wpv-inline-template-new-name' ).val() == '' ) {
					return;
				}
				template_name	= $( '.js-wpv-inline-template-new-name' ).val();
				mode			= 'create';
				data = Object.assign(
					{
						mode: mode,
						view_id: $('.js-wpv-ct-assign-to-view').data('id'),
						template_name: template_name,
					},
					data
				);
				break;
			case 'already':
				send_ajax		= false;
				template_id		= $( '.js-wpv-inline-template-assigned-select' ).val();
				template_name	= $( '.js-wpv-inline-template-assigned-select option:selected' ).data( 'ct-name' );
				mode			= 'insert';
				break;
		}

		if ( send_ajax ) {
			$.ajax({
				type:		"POST",
				dataType:	"json",
				url:		ajaxurl,
				data:		data
			})
			.done( function( response ) {
				if ( response.success ) {
					$( '.js-wpv-settings-inline-templates' ).show();
					if ( $('#wpv-ct-listing-' + response.data.ct_id ).html() ) {
						$( '#wpv-ct-listing-' + response.data.ct_id ).removeClass( 'hidden' );
					} else {
						$( '.js-wpv-content-template-view-list > ul' )
							.first()
								.append( response.data.message );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-init-inline-content-template', response.data.ct_id );
					}
					self.add_content_template_shortcode( response.data.ct_name, response.data.ct_id );
					$( '.wpv_ct_inline_message' ).remove();
					spinnerContainer.remove();
					self.dialog_assign_ct.dialog( "close" );
				} else {
					if ( response.data.type == 'name' ) {
						var errorMessage = 'undefined' !== typeof response.data.message ?
							response.data.message :
							wpv_inline_templates_i18n.error.name_in_use;
						$( '.js-wpv-add-new-ct-name-error-container' ).wpvToolsetMessage({
							text: errorMessage,
							stay: true,
							close: false,
							type: ''
						});
						$( '.wpv_ct_inline_message' ).remove();
						return false;
					} else {
						console.log('Error: Content template not found in database');
						$('.wpv_ct_inline_message').remove();
						return false;
					}
				}
			})
			.fail( function() {

			})
			.always( function() {
				spinnerContainer.remove();
			});
		} else {
			self.add_content_template_shortcode( template_name, template_id );
			$( '.wpv_ct_inline_message' ).remove();
			spinnerContainer.remove();
			self.dialog_assign_ct.dialog( "close" );
		}
		return false;
	});

	// Insert shortcode into textarea

	self.add_content_template_shortcode = function( template_name, template_id ) {
		if ( $( '.js-wpv-add-to-editor-check' ).prop('checked') == true || $( '.js-wpv-inline-template-type:checked' ).val() == 'already' ) {
			var content = '[wpv-post-body view_template="' + template_name + '"]',
			current_cursor = WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].getCursor( true );
            WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].setSelection( current_cursor, current_cursor );
            WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].replaceSelection( content, 'end' );
			var end_cursor = WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].getCursor( true ),
			content_template_marker = WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].markText( current_cursor, end_cursor, self.codemirror_highlight_options ),
			pointer_content = $( '#js-wpv-inline-content-templates-dialogs .js-wpv-inserted-inline-content-template-pointer' );
			if ( pointer_content.hasClass( 'js-wpv-pointer-dismissed' ) ) {
				setTimeout( function() {
					  content_template_marker.clear();
				}, 2000);
			} else {
				var content_template_pointer = $('.layout-html-editor  .wpv-codemirror-highlight').first().pointer({
					pointerClass: 'wp-toolset-pointer wp-toolset-views-pointer',
					pointerWidth: 400,
					content: pointer_content.html(),
					position: {
						edge: 'bottom',
						align: 'left'
					},
					show: function( event, t ) {
						t.pointer.show();
						t.opened();
						var button_scroll = $('<button class="button button-primary-toolset alignright js-wpv-scroll-this">' + wpv_inline_templates_i18n.pointer.scroll_to_template + '</button>');
						button_scroll.on( 'click.pointer', function(e) {//We need to scroll there down
							e.preventDefault();
							content_template_marker.clear();
							if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
								var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
								$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
							}
							t.element.pointer('close');
							if ( template_id ) {
								$('html, body').animate({
									scrollTop: $( '#wpv-ct-listing-' + template_id ).offset().top - 100
								}, 1000);
							}
						});
						button_scroll.insertAfter(  t.pointer.find('.wp-pointer-buttons .js-wpv-close-this') );
					},
					buttons: function( event, t ) {
						var button_close = $('<button class="button button-secondary alignleft js-wpv-close-this">' + wpv_inline_templates_i18n.pointer.close + '</button>');
						button_close.on( 'click.pointer', function( e ) {
							e.preventDefault();
							content_template_marker.clear();
							if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
								var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
								$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
							}
							t.element.pointer('close');
							WPV_Toolset.CodeMirror_instance['wpv_layout_meta_html_content'].focus();
						});
						return button_close;
					}
				});
				content_template_pointer.pointer('open');
			}
		}
	};

	$( document ).on( 'click', '.js-wpv-layout-template-overlay-info-link', function( e ) {
		var $button = $( this ),
			queueLength = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-save-queue-length', 0 );
		if ( queueLength > 0 ) {
			$button.addClass( 'js-wpv-layout-template-overlay-info-link-pending' );
			var dialog_height = $( window ).height() - 100;
			self.dialogSaveBeforeGoToUserEditor.dialog( "open" ).dialog({
				maxHeight:	dialog_height,
				draggable:	false,
				resizable:	false,
				position: 	{
					my:			"center top+50",
					at:			"center top",
					of:			window,
					collision:	"none"
				}
			});
			return false;
		}
	});

	$( document ).on( 'js_wpv_save_section_queue_completed', function( event ) {
		if ( self.dialogSaveBeforeGoToUserEditor.dialog( 'isOpen' ) ) {
			self.dialogSaveBeforeGoToUserEditor.dialog( 'close' );
			var $button = $( '.js-wpv-layout-template-overlay-info-link.js-wpv-layout-template-overlay-info-link-pending' );
			$button.removeClass( 'js-wpv-layout-template-overlay-info-link-pending' );
			$button[0].click();
		}
	});

	// ---------------------------------
	// Inline Content Template change and update management
	// ---------------------------------

	/**
	* track_inline_content_template_changes
	*
	* Track changes to a given inline Content Template events, and set save buttons and unload states.
	*
	* @since 2.1
	*/

	self.track_inline_content_template_changes = function( template_id ) {
		if (
			WPV_Toolset.CodeMirror_instance_value["wpv_ct_inline_editor_" + template_id] !=  WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id].getValue()
			|| WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_css_editor_" + template_id] !=  WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + template_id].getValue()
			|| WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_js_editor_" + template_id] !=  WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + template_id].getValue()
		) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_section_inline_content_template', action: 'add', args: { template_id: template_id } } );
			$( '.js-wpv-ct-update-inline-' + template_id ).addClass( 'js-wpv-section-unsaved' );
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_section_inline_content_template', action: 'remove', args: { template_id: template_id } } );
			$( '.js-wpv-ct-update-inline-' + template_id ).removeClass( 'js-wpv-section-unsaved' );
			$( '.js-wpv-ct-update-inline-' + template_id )
				.parent()
					.find( '.toolset-alert-error' )
						.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};

	/**
	* set_inline_content_template_editor
	*
	* Init a given inline Content Template editor, including Codemirror and Quicktags.
	*
	* @since 2.3.0
	*/

	self.set_inline_content_template_editor = function( template_id ) {
		// Content editor
		WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id] = icl_editor.codemirror( 'wpv-ct-inline-editor-' + template_id, true );
		WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id].refresh();
		WPV_Toolset.CodeMirror_instance_value["wpv_ct_inline_editor_" + template_id] = WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id].getValue();
		//Add quicktags toolbar
		var wpv_inline_editor_qt = quicktags( { id: 'wpv-ct-inline-editor-'+template_id, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
		WPV_Toolset.add_qt_editor_buttons( wpv_inline_editor_qt, WPV_Toolset.CodeMirror_instance['wpv_ct_inline_editor_' + template_id] );

		_.defer( function() {
			// CSS Components compatibility
			Toolset.hooks.doAction( 'toolset_text_editor_CodeMirror_init', 'wpv-ct-inline-editor-' + template_id );
		});

		// Extra assets editors
		WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + template_id] = icl_editor.codemirror( 'wpv-ct-assets-inline-css-editor-' + template_id, true, 'css' );
		WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + template_id].setSize( "100%", 250 );
		WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_css_editor_" + template_id] = WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + template_id].getValue();

		WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + template_id] = icl_editor.codemirror( 'wpv-ct-assets-inline-js-editor-' + template_id, true, 'javascript' );
		WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + template_id].setSize( "100%", 250 );
		WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_js_editor_" + template_id] = WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + template_id].getValue();

		return self;
	};

	/**
	* set_inline_content_template_events
	*
	* Init a given inline Content Template events, especially to track change events.
	*
	* @since 2.1.0
	*/

	self.set_inline_content_template_events = function( template_id ) {
		if ( typeof WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id] !== "undefined" ) {
			WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id].on( 'change', function() {
				self.track_inline_content_template_changes( template_id );
			});
		}
		if ( typeof WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + template_id] !== "undefined" ) {
			WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + template_id].on( 'change', function() {
				self.track_inline_content_template_changes( template_id );
			});
		}
		if ( typeof WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + template_id] !== "undefined" ) {
			WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + template_id].on( 'change', function() {
				self.track_inline_content_template_changes( template_id );
			});
		}

		Toolset.hooks.doAction( 'wpv-action-wpv-set-inline-content-template-events', template_id );

	};

	/**
	* save_section_inline_content_template
	*
	* Save an inline Content Template.
	*
	* @since 2.1
	*/

	self.save_section_inline_content_template = function( event, propagate, args ) {
		var thiz = $( '.js-wpv-ct-update-inline-' + args.template_id ),
		thiz_container = thiz.closest('.js-wpv-ct-listing' ),
		messages_container = thiz_container
			.closest( '.js-wpv-content-template-view-list' )
				.find( '.js-wpv-message-container' ),
		ct_id = args.template_id,
		ct_value = WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + ct_id].getValue(),
		ct_css_value = WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + ct_id].getValue(),
		ct_js_value = WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + ct_id].getValue(),
		spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
		data = {
			action:			'wpv_ct_update_inline',
			ct_value:		ct_value,
			ct_css_value:	ct_css_value,
			ct_js_value:	ct_js_value,
			ct_id:			ct_id,
			wpnonce:		$( '#wpv_inline_content_template' ).attr( 'value' )
		};

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_section_inline_content_template', action: 'remove', args: { template_id: ct_id } } );

		$.post( ajaxurl, data, function( response ) {
			if ( response.success ) {
				thiz
					.parent()
						.find('.toolset-alert-error')
							.remove();
				thiz.removeClass( 'js-wpv-section-unsaved' );
				thiz_container.addClass( 'wpv-inline-content-template-saved' );
				setTimeout( function () {
					thiz_container.removeClass( 'wpv-inline-content-template-saved' );
				}, 500 );
				WPV_Toolset.CodeMirror_instance_value["wpv_ct_inline_editor_" + ct_id] = ct_value;
				WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_css_editor_" + ct_id] = ct_css_value;
				WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_js_editor_" + ct_id] = ct_js_value;
				$( document ).trigger( event );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				} else {
					$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
				}
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: messages_container} );
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_section_inline_content_template' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}, 'json' )
		.fail( function( jqXHR, textStatus, errorThrown ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_section_inline_content_template' );
			if ( propagate ) {
				$( document ).trigger( 'js_wpv_save_section_queue' );
			}
			//console.log( "Error: ", textStatus, errorThrown );
		})
		.always( function() {
			spinnerContainer.remove();
		});
	};

	/**
	 * Save a single inline Content Template, on demand.
	 *
	 * Required because before 2.7.3 each inline CT had its own save button
	 * and user editors saved individual inline CTs by firing a click event on them.
	 *
	 * @param int templateId
	 * @since 2.7.4
	 */
	self.save_inline_content_template = function ( templateId ) {
		if (
			WPV_Toolset.CodeMirror_instance_value["wpv_ct_inline_editor_" + templateId] !=  WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + templateId].getValue()
			|| WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_css_editor_" + templateId] !=  WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + templateId].getValue()
			|| WPV_Toolset.CodeMirror_instance_value["wpv_ct_assets_inline_js_editor_" + templateId] !=  WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + templateId].getValue()
		) {
			self.save_section_inline_content_template( 'js_event_wpv_save_section_inline_content_template_completed', false, { template_id: templateId } );
		}
	}

	// Open

	$( document ).on( 'click', '.js-wpv-content-template-open', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		template_id = thiz.data( 'target' ),
		li_container = $( '.js-wpv-inline-editor-container-' + template_id ),
		arrow = thiz.find( '.js-wpv-open-close-arrow' );

		arrow.removeClass( 'fa-caret-down fa-caret-up' );

		li_container.toggle( 400 ,function() {
			if ( li_container.is(':hidden') ) {
				arrow.addClass( 'fa-caret-down' );
				return;
			}
			if ( ! WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id] ) {
				// First time we open the inline CT, so we must get it
				thiz.prop( 'disabled', true );
				arrow.addClass( 'fa-circle-o-notch fa-spin' );
				data = {
					action:					'wpv_ct_loader_inline',
					id:						template_id,
					include_instructions:	'inline_content_template',
					wpnonce:				$( '#wpv-ct-inline-edit' ).attr( 'value' )
				};
				$.post( ajaxurl, data, function( response ) {
					if ( response == 'error' ) {
						console.log('Error, Content Template not found.');
					} else {
						$( '.js-wpv-inline-editor-container-' + template_id ).html( response );

						self.init_inline_template( template_id );

						thiz.prop( 'disabled', false );
						arrow
							.addClass( 'fa-caret-up' )
							.removeClass( 'fa-circle-o-notch fa-spin' )
					}
				});
			} else {
				WPV_Toolset.CodeMirror_instance["wpv_ct_inline_editor_" + template_id].refresh();
				WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_css_editor_" + template_id].refresh();
				WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_js_editor_" + template_id].refresh();
				arrow.addClass( 'fa-caret-up' );
			}
		});
		return;
	});

	// Remove dialog

	$( document ).on( 'click', '.js-wpv-ct-remove-from-view', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		messages_container = thiz.closest( '.js-wpv-content-template-view-list' ).find( '.js-wpv-message-container' );
		self.current_ct_container = thiz.parents('.js-wpv-ct-listing' );
		self.current_ct_id = self.current_ct_container.data( 'id' );
		if ( $( '#js-wpv-dialog-remove-content-template-from-view-dialog' ).hasClass( 'js-wpv-dialog-dismissed' ) ) {
			data = {
				action:			'wpv_remove_content_template_from_view',
				view_id:		self.view_id,
				template_id:	self.current_ct_id,
				dismiss:		'true',
				wpnonce:		$('#wpv_inline_content_template').attr( 'value' )
			};
			$.post( ajaxurl, data, function( response ) {
				if ( response.success ) {
					self.remove_inline_content_template( self.current_ct_id, self.current_ct_container );
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: messages_container} );
				}
				self.current_ct_container = null;
				self.current_ct_id = 0;
			});
		} else {
			var dialog_height = $( window ).height() - 100;
			self.dialog_unassign_ct.dialog( "open" ).dialog({
				maxHeight:	dialog_height,
				draggable:	false,
				resizable:	false,
				position: 	{
					my:			"center top+50",
					at:			"center top",
					of:			window,
					collision:	"none"
				}
			});
		}
		return false;
	});

	self.remove_inline_content_template = function( template_id, template_container ) {
		if (
			template_id == 0
			|| template_container == null
		) {
			return;
		}
		template_container
			.addClass( 'wpv-inline-content-template-deleted' )
			.animate({
				height:		"toggle",
				opacity:	"toggle"
			}, 400, function() {

				if ( _.has( WPV_Toolset.CodeMirror_instance, 'wpv_ct_inline_editor_' + template_id ) ) {
					WPV_Toolset.CodeMirror_instance[ 'wpv_ct_inline_editor_' + template_id ].focus();
					delete WPV_Toolset.CodeMirror_instance[ 'wpv_ct_inline_editor_' + template_id ];
					delete WPV_Toolset.CodeMirror_instance_value[ 'wpv_ct_inline_editor_' + template_id ];
					// Delete it from the iclCodeMirror collection
					delete window.iclCodemirror[ 'wpv-ct-inline-editor-' + template_id ];
					// Maybe delete de Quicktags instance
					if ( _.has( WPV_Toolset.CodeMirror_instance_qt, 'wpv_ct_inline_editor_' + template_id ) ) {
						delete WPV_Toolset.CodeMirror_instance_qt[ 'wpv_ct_inline_editor_' + template_id ];
					}
				}

				if ( _.has( WPV_Toolset.CodeMirror_instance, 'wpv_ct_assets_inline_css_editor_' + template_id ) ) {
					WPV_Toolset.CodeMirror_instance[ 'wpv_ct_assets_inline_css_editor_' + template_id ].focus();
					delete WPV_Toolset.CodeMirror_instance[ 'wpv_ct_assets_inline_css_editor_' + template_id ];
					delete WPV_Toolset.CodeMirror_instance_value[ 'wpv_ct_assets_inline_css_editor_' + template_id ];
					// Delete it from the iclCodeMirror collection
					delete window.iclCodemirror[ 'wpv-ct-assets-inline-css-editor-' + template_id ];
					// Maybe delete de Quicktags instance
					if ( _.has( WPV_Toolset.CodeMirror_instance_qt, 'wpv_ct_assets_inline_css_editor_' + template_id ) ) {
						delete WPV_Toolset.CodeMirror_instance_qt[ 'wpv_ct_assets_inline_css_editor_' + template_id ];
					}
				}

				if ( _.has( WPV_Toolset.CodeMirror_instance, 'wpv_ct_assets_inline_js_editor_' + template_id ) ) {
					WPV_Toolset.CodeMirror_instance[ 'wpv_ct_assets_inline_js_editor_' + template_id ].focus();
					delete WPV_Toolset.CodeMirror_instance[ 'wpv_ct_assets_inline_js_editor_' + template_id ];
					delete WPV_Toolset.CodeMirror_instance_value[ 'wpv_ct_assets_inline_js_editor_' + template_id ];
					// Delete it from the iclCodeMirror collection
					delete window.iclCodemirror[ 'wpv-ct-assets-inline-js-editor-' + template_id ];
					// Maybe delete de Quicktags instance
					if ( _.has( WPV_Toolset.CodeMirror_instance_qt, 'wpv_ct_assets_inline_js_editor_' + template_id ) ) {
						delete WPV_Toolset.CodeMirror_instance_qt[ 'wpv_ct_assets_inline_js_editor_' + template_id ];
					}
				}

				$( this ).remove();

				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_section_inline_content_template', action: 'remove', args: { template_id: template_id } } );

				if ( $( "ul.js-wpv-inline-content-template-listing > li" ).length < 1 ) {
					$( '.js-wpv-settings-inline-templates' ).hide();
				}

			});
	};

	// Manage pushpin for inline CT assets

	$( document ).on( 'js_event_wpv_editor_metadata_toggle_toggled', function( event, toggler ) {
		if ( toggler.hasClass( 'js-wpv-ct-assets-inline-editor-toggle' ) ) {
			var ct_inline_id = toggler.data( 'id' ),
			thiz_type = toggler.data( 'type' ),
			thiz_flag = toggler.find( '.js-wpv-textarea-full' ),
			this_toggler_icon = toggler.find( '.js-wpv-toggle-toggler-icon i' );
			thiz_flag.hide();
			if ( ! toggler.hasClass( 'js-wpv-ct-assets-inline-editor-toggle-refreshed' ) ) {
				WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_" + thiz_type + "_editor_" + ct_inline_id].refresh();
				toggler.addClass( 'js-wpv-ct-assets-inline-editor-toggle-refreshed' );
			}
			if (
				self.asset_needs_flag( ct_inline_id, thiz_type )
				&& (
					this_toggler_icon.hasClass( 'icon-caret-down' )
					|| this_toggler_icon.hasClass( 'fa-caret-down' )
				)
			) {
				thiz_flag.animate( {width: 'toggle'}, 200 );
			}
		}
	});

	self.asset_needs_flag = function( ct_id, type ) {
		var needed = false;
		if ( WPV_Toolset.CodeMirror_instance["wpv_ct_assets_inline_" + type + "_editor_" + ct_id].getValue() != '' ) {
			needed = true;
		}
		return needed;
	};

	self.init_dialogs = function() {
		$( 'body' ).append( '<div id="js-wpv-dialog-assign-content-template-to-view-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container js-wpv-shortcode-gui-dialog-container"></div>' );

		var query_mode				= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
		dialog_assign_ct_title		= wpv_inline_templates_i18n.dialog.assign.view_title,
		dialog_unassign_ct_title	= wpv_inline_templates_i18n.dialog.unassign.view_title;
		if ( query_mode == 'archive' ) {
			dialog_assign_ct_title		= wpv_inline_templates_i18n.dialog.assign.wpa_title;
			dialog_unassign_ct_title	= wpv_inline_templates_i18n.dialog.unassign.wpa_title;
		}

		self.dialog_assign_ct = $( "#js-wpv-dialog-assign-content-template-to-view-dialog" ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		dialog_assign_ct_title,
			minWidth:	600,
			show:		{
				effect:		"blind",
				duration:	800
			},
			open:		function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close:		function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class:	'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-assign-inline-content-template',
					text:	wpv_inline_templates_i18n.dialog.assign.action,
					click:	function() {

					}
				},
				{
					class:	'button-secondary',
					text:	wpv_inline_templates_i18n.dialog.cancel,
					click:	function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});

		self.dialog_unassign_ct = $( "#js-wpv-dialog-remove-content-template-from-view-dialog" ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		dialog_unassign_ct_title,
			minWidth:	600,
			show:		{
				effect:		"blind",
				duration:	800
			},
			open:		function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-wpv-remove-template-from-view' )
					.addClass( 'button-primary' )
					.removeClass( 'button-secondary' )
					.prop( 'disabled', false );
			},
			close:		function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class:	'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-remove-template-from-view',
					text:	wpv_inline_templates_i18n.dialog.unassign.action,
					click:	function() {
						var thiz = $( '.js-wpv-remove-template-from-view' ),
						thiz_dialog = $( this ),
						dismiss = 'false',
						spinnerContainer = $('<div class="wpv-spinner ajax-loader auto-update">').insertAfter( thiz ).show(),
						messages_container = $( '.js-wpv-content-template-view-list' ).find( '.js-wpv-message-container' );
						thiz
							.addClass( 'button-secondary' )
							.removeClass( 'button-primary' )
							.prop( 'disabled', true );
						if ( $( '.js-wpv-dettach-inline-content-template-dismiss' ).prop('checked') ) {
							dismiss = 'true';
						}
						var data = {
							action:			'wpv_remove_content_template_from_view',
							view_id:		self.view_id,
							template_id:	self.current_ct_id,
							dismiss:		dismiss,
							wpnonce:		$('#wpv_inline_content_template').attr( 'value' )
						};
						$.post( ajaxurl, data, function( response ) {
							if ( response.success ) {
								self.remove_inline_content_template( self.current_ct_id, self.current_ct_container );
								if ( dismiss == 'true' ) {
									$( '#js-wpv-dialog-remove-content-template-from-view-dialog' ).addClass( 'js-wpv-dialog-dismissed' );
								}
							} else {
								Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: messages_container} );
							}
							self.current_ct_container = null;
							self.current_ct_id = 0;
						})
						.fail( function( jqXHR, textStatus, errorThrown ) {
							//console.log( "Error: ", textStatus, errorThrown );
						})
						.always( function() {
							spinnerContainer.remove();
							thiz_dialog.dialog( "close" );
						});
					}
				},
				{
					class:	'button-secondary',
					text:	wpv_inline_templates_i18n.dialog.cancel,
					click:	function() {
						self.current_ct_container = null;
						self.current_ct_id = 0;
						$( this ).dialog( "close" );
					}
				}
			]
		});

		$( 'body' ).append( '<div id="js-wpv-dialog-ct-save-before-go-to-user-editor" class="toolset-shortcode-gui-dialog-container js-wpv-dialog-ct-save-before-go-to-user-editor"><p>' +
			'<i class="fa fa-4x fa-exclamation-circle" style="vertical-align:middle;padding:0 20px;color:#ed8027;"></i>' +
			wpv_inline_templates_i18n.dialog.saveAndGo.body +
			'</p></div>' );

		self.dialogSaveBeforeGoToUserEditor = $( "#js-wpv-dialog-ct-save-before-go-to-user-editor" ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		wpv_inline_templates_i18n.dialog.saveAndGo.title,
			minWidth:	600,
			show:		{
				effect:		"blind",
				duration:	800
			},
			open:		function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close:		function( event, ui ) {
				$( '.js-wpv-dialog-ct-save-before-go-to-user-editor-spinner' ).remove();
				var $button = $( '.js-wpv-dialog-ct-save-before-go-to-user-editor-apply' );
				$button.addClass( 'button-primary' ).removeClass( 'button-secondary' ).prop( 'disabled', false );
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class:	'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-dialog-ct-save-before-go-to-user-editor-apply',
					text:	wpv_inline_templates_i18n.dialog.saveAndGo.action,
					click:	function() {
						var $button = $( '.js-wpv-dialog-ct-save-before-go-to-user-editor-apply' );
						$button.addClass( 'button-secondary' ).removeClass( 'button-primary' ).prop( 'disabled', true );
						$( '<span class="spinner ajax-loader js-wpv-dialog-ct-save-before-go-to-user-editor-spinner">' ).insertBefore( $button ).css( { 'visibility': 'visible' } );
						$( '.js-wpv-view-save-all' ).trigger( 'click' );
					}
				},
				{
					class:	'button-secondary',
					text:	wpv_inline_templates_i18n.dialog.cancel,
					click:	function() {
						$( '.js-wpv-layout-template-overlay-info-link.js-wpv-layout-template-overlay-info-link-pending' )
							.removeClass( 'js-wpv-layout-template-overlay-info-link-pending' );
						$( this ).dialog( "close" );
					}
				}
			]
		});
	};

	/**
	* init_hooks
	*
	* Init all the relevant Toolset.hooks needed here.
	*
	* @since 2.1
	*/

	self.init_hooks = function() {
		// Register the inline Content Template saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_section_inline_content_template',
			callback:	self.save_section_inline_content_template,
			event:		'js_event_wpv_save_section_inline_content_template_completed'
		});
		// Action to init an inline Content Template
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-init-inline-content-template', self.init_inline_template );

		Toolset.hooks.addAction( 'wpv-action-wpv-save-inline-content-template', self.save_inline_content_template );
	};

	/**
	* init_open_inline_templates
	*
	* Init all open - on pageload - inline Content Templates.
	*
	* This avoids the need of inline scripts when a single inline Content Template exists on a loop,
	* hence needs to be rendered open.
	*
	* @since 2.3.0
	*/

	self.init_open_inline_templates = function() {
		$( '.js-wpv-ct-inline-editor-textarea' ).each( function() {
			var thiz = $( this ),
				thiz_template_id = thiz.data( 'id' );
				self.init_inline_template( thiz_template_id );
		});
	};

	/**
	* init_inline_template
	*
	* Init an inline Content Template given its ID.
	*
	* This avoids the need of inline scripts when a single inline Content Template exists on a loop,
	* hence needs to be rendered open.
	*
	* @since 2.3.0
	*/

	self.init_inline_template = function( template_id ) {
		self.set_inline_content_template_editor( template_id )
			.set_inline_content_template_events( template_id );
		if ( typeof cred_cred != 'undefined' ) {
			cred_cred.posts();// this should be an event!!!
		}
	};

	self.init = function() {
		self.init_dialogs();
		self.init_hooks();
		self.init_open_inline_templates();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.view_edit_screen_inline_content_templates = new WPViews.ViewEditScreenInlineCT( $ );
});
