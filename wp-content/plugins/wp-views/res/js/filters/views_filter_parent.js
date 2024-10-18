/**
* Views Search Filter GUI - script
*
* Adds basic interaction for the Search Filter
*
* @package Views
*
* @since 1.7.0
*/

var WPViews = WPViews || {};

WPViews.ParentFilterGUI = function( $, id ) {
	var self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner				= '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.warning_post_missing	= '<p class="js-wpv-filter-post-parent-missing js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + wpv_parent_strings.post.post_type_missing + '</p>';
	self.warning_post_flat		= '<p class="js-wpv-filter-post-parent-flat js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + wpv_parent_strings.post.post_type_flat + '</p>';
	self.warning_post_media		= '<p class="js-wpv-filter-post-parent-media js-wpv-permanent-alert-error toolset-alert toolset-alert-info"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + wpv_parent_strings.post.post_type_media + '</p>';
	self.warning_tax_missing	= '<p class="js-wpv-filter-tax-parent-missing js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + wpv_parent_strings.taxonomy.taxonomy_missing + '</p>';
	self.warning_tax_flat		= '<p class="js-wpv-filter-tax-parent-flat js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + wpv_parent_strings.taxonomy.taxonomy_flat + '</p>';
	self.warning_tax_changed	= '<p class="js-wpv-filter-tax-parent-changed js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + wpv_parent_strings.taxonomy.taxonomy_changed + '</p>';
	self.disabled_for_loop		= '<p class="js-wpv-archive-filter-post-parent-disabled js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> ' + wpv_parent_strings.archive.disable_post_parent_filter + '</p>';

	self.post_row							= '.js-wpv-filter-row-post-parent';
	self.post_options_container_selector	= '.js-wpv-filter-post-parent-options';
	self.post_summary_container_selector	= '.js-wpv-filter-post-parent-summary';
	self.post_messages_container_selector	= '.js-wpv-filter-row-post-parent .js-wpv-filter-toolset-messages';
	self.post_edit_open_selector			= '.js-wpv-filter-post-parent-edit-open';
	self.post_close_save_selector			= '.js-wpv-filter-post-parent-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	self.post_type_select = {};

	self.tax_row							= '.js-wpv-filter-row-taxonomy-parent';
	self.tax_options_container_selector		= '.js-wpv-filter-taxonomy-parent-options';
	self.tax_summary_container_selector		= '.js-wpv-filter-taxonomy-parent-summary';
	self.tax_messages_container_selector	= '.js-wpv-filter-row-taxonomy-parent .js-wpv-filter-toolset-messages';
	self.tax_edit_open_selector				= '.js-wpv-filter-taxonomy-parent-edit-open';
	self.tax_close_save_selector			= '.js-wpv-filter-taxonomy-parent-edit-ok';

	self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select').serialize();

	//--------------------
	// Functions for parent
	//--------------------

	//--------------------
	// Events for parent
	//--------------------

	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file

	$( document ).on( 'click', self.post_edit_open_selector, function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		$( self.post_close_save_selector ).show();
		$( self.post_row ).addClass( 'wpv-filter-row-current' );
	});

	// Track changes in options

	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select', function() { // watch on inputs change
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_parent', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_parent', action: 'remove' } );
			$( self.post_close_save_selector )
				.addClass('button-secondary')
				.removeClass('button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data('close')
				);
			$( self.post_close_save_selector )
				.parent()
					.find( '.unsaved' )
					.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	});

	// Save filter options

	self.save_filter_post_parent = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_parent', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-post-parent' );
			if ( valid ) {
				// update_message = thiz.data('success');
				// unsaved_message = thiz.data('unsaved');
				var action = thiz.data( 'saveaction' ),
				nonce = thiz.data('nonce'),
				spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
				error_container = thiz
					.closest( '.js-filter-row' )
						.find( '.js-wpv-filter-toolset-messages' );
				self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
				var data = {
					action:			action,
					id:				self.view_id,
					filter_options:	self.post_current_options,
					wpnonce:		nonce
				};
				$.post( ajaxurl, data, function( response ) {
					if ( response.success ) {
						$( self.post_close_save_selector )
							.addClass('button-secondary')
							.removeClass('button-primary js-wpv-section-unsaved')
							.html(
								WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data( 'close' )
							);
						$( self.post_summary_container_selector ).html( response.data.summary );
						WPViews.query_filters.close_and_glow_filter_row( self.post_row, 'wpv-filter-saved' );
						$( document ).trigger( event );
						// raise custom event for the QueryFilterSettingsPanel of Views for Gutenberg
						$( document ).trigger( 'wpvFilterSaveCompleted' );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						} else {
							$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
						}
					} else {
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					}
				}, 'json' )
				.fail( function( jqXHR, textStatus, errorThrown ) {
					console.log( "Error: ", textStatus, errorThrown );
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_parent' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				})
				.always( function() {
					spinnerContainer.remove();
					thiz.hide();
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_parent' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_parent( 'js_event_wpv_save_filter_post_parent_completed', false );
	});

	// Update posts selector when changing the specific option post type
	// Cache options to prevent multiple AJAX calls for the same post type

	$( document ).on( 'change', '.js-post-parent-post-type', function() {
		// Update the parents for the selected type.
		var post_type = $('.js-post-parent-post-type').val();
		$( 'select#post_parent_id' ).remove();
		if ( typeof self.post_type_select[post_type] == "undefined" ) {
			var data = {
				action : 'wpv_get_post_parent_post_select',
				post_type : post_type,
				wpnonce : $('.js-post-parent-post-type').data('nonce')
			};
			var spinnerContainer = $( self.spinner ).insertAfter( $(this) ).show();
			$.post( ajaxurl, data, function( response ) {
				if ( typeof( response ) !== 'undefined' ) {
					if ( response != 0 ) {
						self.post_type_select[post_type] = response;
						$( '.js-post-parent-post-type' ).after( self.post_type_select[post_type] );
						$( '#post_parent_id' ).trigger( 'change' );
					} else {
						console.log( "Error: WordPress AJAX returned " + response );
					}
				} else {
					console.log( "Error: AJAX returned ", response );
				}
			})
			.fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( "Error: ", textStatus, errorThrown );
			})
			.always( function() {
				spinnerContainer.hide();
			});
		} else {
			$( '.js-post-parent-post-type' ).after( self.post_type_select[post_type] );
			$( '#post_parent_id' ).trigger( 'change' );
		}
	});

	//--------------------
	// Functions for taxonomy parent
	//--------------------

	// Show an hide notices related to the content selection section

	self.show_hide_tax_parent_notice = function() {
		var show = false,
		tax_parent_message = '',
		list = '';
		if ( $( '.js-wpv-query-taxonomy-type:checked' ).length ) {
			$( '.js-wpv-query-taxonomy-type:checked' ).each( function() {
				if ( $( this ).data( 'hierarchical' ) == 'no' ) {
					show = true;
					if ( tax_parent_message == '' ) {
						tax_parent_message = wpv_parent_strings.taxonomy.taxonomy_flat;
					}
					if ( list != '' ) {
						list += ',';
					}
					list += ' ' + $( this ).parent( 'li' ).find( 'label' ).html();
				}
			});
			if ( list != '' ) {
				tax_parent_message += list;
			}
		} else {
			show = true;
			tax_parent_message = wpv_parent_strings.taxonomy.taxonomy_missing;
		}
		if ( show ) {
			$( '.js-wpv-filter-taxonomy-parent-notice' ).show();
			$( self.tax_messages_container_selector ).wpvToolsetMessage({
				text:tax_parent_message,
				type:'error',
				classname:'js-wpv-filter-taxonomy-parent-info',
				inline:false,
				stay:true,
				fadeIn: 10,
				fadeOut: 10
			});
		} else {
			$( '.js-wpv-filter-taxonomy-parent-notice' ).hide();
			$( self.tax_row ).find( '.js-wpv-filter-taxonomy-parent-info' ).remove();
		}
		self.update_taxonomy_parent_id_dropdown();
	};

	// Update the taxonomy_parent_id select dropdown when there are relevant changes in the Content Selection section

	self.update_taxonomy_parent_id_dropdown = function() {
		var taxonomy_parent_select = $( '.js-taxonomy-parent-id' ),
		old_taxonomy = taxonomy_parent_select.data( 'taxonomy' );
		if ( taxonomy_parent_select.length > 0 ) {
			var current_taxonomy = $( '.js-wpv-query-taxonomy-type:checked' ).val(),
			nonce = taxonomy_parent_select.data( 'nonce' );
			if ( old_taxonomy != current_taxonomy ) {
				var data = {
					action: 'update_taxonomy_parent_id_dropdown',
					taxonomy: current_taxonomy,
					wpnonce: nonce
				},
				spinnerContainer = $( self.spinner ).insertAfter( taxonomy_parent_select ).show();
				$.post( ajaxurl, data, function( response ) {
					if ( ( typeof( response ) !== 'undefined' ) ) {
						if ( response != 0 ) {
							$( taxonomy_parent_select ).replaceWith( response ).val( '0' ).trigger( 'change' );
							$( self.tax_messages_container_selector ).wpvToolsetMessage({
								text: wpv_parent_strings.taxonomy.taxonomy_changed,
								type: 'error',
								classname:'js-wpv-filter-taxonomy-parent-changed-info',
								inline: false,
								stay: true
							});
							$( '.js-wpv-filter-taxonomy-parent-notice' ).show();
						} else {
							console.log( "Error: WordPress AJAX returned " + response );
						}
					} else {
						console.log( "Error: AJAX returned ", response );
					}
				})
				.fail( function( jqXHR, textStatus, errorThrown ) {
					console.log( "Error: ", textStatus, errorThrown );
				})
				.always( function() {
					spinnerContainer.remove();
				});
			}
		}
	};

	//--------------------
	// Events for taxonomy parent
	//--------------------

	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file

	$( document ).on( 'click', self.tax_edit_open_selector, function() {
		self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize();
		$( self.tax_close_save_selector ).show();
		$( self.tax_row ).addClass( 'wpv-filter-row-current' );
		self.show_hide_tax_parent_notice();
	});

	// Track changes

	$( document ).on( 'change keyup input cut paste', self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select', function() {
		WPViews.query_filters.clear_validate_messages( self.tax_row );
		if ( self.tax_current_options != $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_parent', action: 'add' } );
			$( self.tax_close_save_selector )
				.addClass( 'button-primary js-wpv-section-unsaved' )
				.removeClass( 'button-secondary' )
				.html(
					WPViews.query_filters.icon_save + $( self.tax_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_parent', action: 'remove' } );
			$( self.tax_close_save_selector )
				.addClass( 'button-secondary' )
				.removeClass('button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_edit + $( self.tax_close_save_selector ).data('close')
				);
			$( self.tax_close_save_selector )
				.parent()
					.find( '.unsaved' )
					.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	});

	// Save options

	self.save_filter_taxonomy_parent = function( event, propagate ) {
		var thiz = $( self.tax_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.tax_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_parent', action: 'remove' } );

		if ( self.tax_current_options == $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.tax_row );
			thiz.hide();
		} else {
			// update_message = thiz.data('success');
			// unsaved_message = thiz.data('unsaved');
			var action = thiz.data( 'saveaction' ),
			nonce = thiz.data('nonce'),
			spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
			error_container = thiz
				.closest( '.js-filter-row' )
					.find( '.js-wpv-filter-toolset-messages' );
			self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize();
			var data = {
				action:			action,
				id:				self.view_id,
				filter_options:	self.tax_current_options,
				wpnonce:		nonce
			};
			$.post( ajaxurl, data, function( response ) {
				if ( response.success ) {
					$( self.tax_close_save_selector )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary js-wpv-section-unsaved' )
						.html(
							WPViews.query_filters.icon_edit + $( self.tax_close_save_selector ).data( 'close' )
						);
					$( self.tax_summary_container_selector ).html( response.data.summary );
					WPViews.query_filters.close_and_glow_filter_row( self.tax_row, 'wpv-filter-saved' );
					$( document ).trigger( event );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					} else {
						$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
					}
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				}
			}, 'json' )
			.fail( function( jqXHR, textStatus, errorThrown ) {
				console.log( "Error: ", textStatus, errorThrown );
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_taxonomy_parent' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			})
			.always( function() {
				spinnerContainer.remove();
				thiz.hide();
			});
		}
	};

	$( document ).on( 'click', self.tax_close_save_selector, function() {
		self.save_filter_taxonomy_parent( 'js_event_wpv_save_filter_taxonomy_parent_completed', false );
	});

	//--------------------
	// Manage post parent filter restrictions
	//--------------------

	/**
	* manage_post_parent_filter_warning
	*
	* Add or remove the warning messages when:
	* 	- Views- querying non-hierarchical post types
	* 	- WPA - combining a query filter by post parent and native, taxonomy or non-hierarchical post type archive loops
	*
	* @since 2.1
	*/

	self.manage_post_parent_filter_warning = function() {
		var query_mode				= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
		flat_post_types_selected	= [],
		media_post_type_selected	= false,
		flat_loops_selected			= [],
		post_parent_filter_row		= $( self.post_row ),
		auxiliar_string				= '',
		auxiliar_warning			= '',
		auxiliar_boolean			= false;

		if ( post_parent_filter_row.length > 0 ) {
			if ( 'normal' == query_mode ) {
				$( '.js-wpv-filter-post-parent-missing, .js-wpv-filter-post-parent-flat, .js-wpv-filter-post-parent-media' ).remove();
				if ( $('.js-wpv-query-post-type:checked').length ) {
					flat_post_types_selected = $('.js-wpv-query-post-type:checked').map( function() {
						if ( $( this ).data( 'hierarchical' ) == 'no' ) {
							return $( this ).parent( 'li' ).find( 'label' ).html();
						}
						if ( $( this ).val() == 'attachment' ) {
							media_post_type_selected = true;
						}
					}).get();
					if ( flat_post_types_selected.length > 0 ) {
						auxiliar_string = flat_post_types_selected.join( ', ' );
						auxiliar_warning = self.warning_post_flat;
						auxiliar_warning = auxiliar_warning.replace( '%s', auxiliar_string );
						$( auxiliar_warning ).prependTo( self.post_row ).show();
					}
					if ( media_post_type_selected ) {
						$( self.warning_post_media ).prependTo( self.post_row ).show();
					}
				} else {
					$( self.warning_post_missing ).prependTo( self.post_row ).show();
				}
			} else {
				$( '.js-wpv-archive-filter-post-parent-disabled' ).remove();
				flat_loops_selected = $( '.js-wpv-settings-archive-loop input:checked' ).map( function() {
					auxiliar_boolean = false;
					switch ( $( this ).data( 'type' ) ) {
						case 'native':
							auxiliar_boolean = true;
							break;
						case 'post_type' :
							if ( $( this ).data( 'hierarchical' ) == 'no' ) {
								auxiliar_boolean = true;
							}
							break;
						case 'taxonomy':
							auxiliar_boolean = true;
							break;
					}
					return auxiliar_boolean ? $( this ).data( 'name' ) : '';
				}).get();
				flat_loops_selected = _.compact( flat_loops_selected );
				if ( flat_loops_selected.length > 0 ) {
					$( self.disabled_for_loop ).prependTo( self.post_row ).show();
				}
			}
		}
	};

	$( document ).on( 'js_event_wpv_query_type_options_saved', '.js-wpv-query-type-update', function( event, query_type ) {
		self.manage_post_parent_filter_warning();
		self.show_hide_tax_parent_notice();
	});

	// Filter creation event

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_parent' ) {
			self.manage_post_parent_filter_warning();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_parent', action: 'add' } );
		}
		if ( filter_type == 'taxonomy_parent' ) {
			self.show_hide_tax_parent_notice();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_parent', action: 'add' } );
		}
	});

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_parent' == filter_type ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_parent', action: 'remove' } );
		}
		if ( filter_type == 'taxonomy_parent' ) {
			self.tax_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_parent', action: 'remove' } );
		}
	});

	$( document ).on( 'js_event_wpv_save_section_loop_selection_completed', function( event ) {
		self.manage_post_parent_filter_warning();
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.ParentFilterGUI}
	 */
	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', self.post_close_save_selector );
		return self;
	}

	//--------------------
	// Init hooks
	//--------------------

	self.init_hooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_post_parent',
			callback:	self.save_filter_post_parent,
			event:		'js_event_wpv_save_filter_post_parent_completed'
		});
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_taxonomy_parent',
			callback:	self.save_filter_taxonomy_parent,
			event:		'js_event_wpv_save_filter_taxonomy_parent_completed'
		});

		/**
		 * Clears events and hooks.
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-filter-clear-events-and-hooks', self.clear_events_and_hooks );
	};

	//--------------------
	// Init
	//--------------------

	self.init = function() {
		self.manage_post_parent_filter_warning();
		self.show_hide_tax_parent_notice();
		self.init_hooks();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.parent_filter_gui = new WPViews.ParentFilterGUI( $ );
});
