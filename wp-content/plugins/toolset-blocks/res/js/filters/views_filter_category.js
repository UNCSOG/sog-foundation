/**
* Views Taxonomy Filter GUI - script
*
* Adds basic interaction for the Taxonomy Filter
*
* @package Views
*
* @since 1.7.0
*/


var WPViews = WPViews || {};

WPViews.TaxonomyFilterGUI = function( $, id ) {
	var self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner					= '<span class="wpv-spinner ajax-loader">';

	self.disabled_for_loop			= '<p class="js-wpv-archive-filter-post-taxonomy-disabled js-wpv-permanent-alert-error toolset-alert toolset-alert-error"><i class="fa fa-warning" aria-hidden"true"=""></i> ' + wpv_category_filter_texts.archive.disable_post_taxonomy_filter + '</p>';

	self.post_row							= '.js-wpv-filter-row-taxonomy';
	self.post_options_container_selector	= '.js-wpv-filter-row-taxonomy .js-wpv-filter-edit';
	self.post_edit_open_selector			= '.js-wpv-filter-taxonomy-edit-open';
	self.post_close_save_selector			= '.js-wpv-filter-taxonomy-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	//--------------------
	// Functions
	//--------------------

	self.manage_filter_changes = function() {
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_taxonomy', action: 'add' } );
			$( self.post_close_save_selector )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_taxonomy', action: 'remove' } );
			$( self.post_close_save_selector )
			.addClass( 'button-secondary' )
			.removeClass( 'button-primary js-wpv-section-unsaved' )
			.html(
				WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data('close')
			);
			$( self.post_row  ).find('.unsaved').remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
		return self;
	};

	self.manage_taxonomy_mode = function( select ) {
		var single_element = $( select ).closest( '.js-wpv-filter-multiple-element' ),
		mode_value = $( select ).val();
		WPViews.query_filters.clear_validate_messages( self.post_row );
		single_element.find( '.js-taxonomy-checklist, .js-taxonomy-parameter, .js-taxonomy-framework' ).hide();
		if ( mode_value == 'FROM ATTRIBUTE' ) {
			single_element.find('.js-taxonomy-parameter').fadeIn();
			single_element.find('.js-taxonomy-param-label')
				.html(
					single_element
						.find('.js-taxonomy-param-label')
							.data('attribute')
				);
			single_element.find('.js-taxonomy-param').data('type', 'shortcode');
		} else if ( mode_value == 'FROM URL' ) {
			single_element.find('.js-taxonomy-parameter').fadeIn();
			single_element.find('.js-taxonomy-param-label')
				.html(
					single_element
						.find('.js-taxonomy-param-label')
							.data('parameter')
				);
			single_element.find('.js-taxonomy-param').data('type', 'url');
		} else if (
			mode_value == 'FROM PAGE' // @deprecated in 1.12.1
			|| mode_value == 'current_post_or_parent_post_view'
			|| mode_value == 'top_current_post'
			|| mode_value == 'FROM PARENT VIEW' // @deprecated in 1.12.1
			|| mode_value == 'current_taxonomy_view'
		) {
			// do nothing
		} else if ( mode_value == 'framework' ) {
			single_element.find( '.js-taxonomy-framework' ).fadeIn();
		} else if (
			mode_value == 'IN'
			|| mode_value == 'NOT IN'
			|| mode_value == 'AND'
		) {
			single_element.find('.js-taxonomy-checklist').fadeIn();
		}
	};

	self.remove_taxonomy_filters = function() {
		$( self.post_close_save_selector ).removeClass( 'js-wpv-section-unsaved' );
		var nonce = $( '.js-wpv-filter-remove-taxonomy' ).data( 'nonce' ),
		taxonomy = [],
		spinnerContainer = $( self.spinner ).insertBefore( $( '.js-wpv-filter-remove-taxonomy' ) ).show(),
		error_container = $( self.post_row ).find( '.js-wpv-filter-multiple-toolset-messages' );
		$('.js-wpv-filter-taxonomy-multiple-element .js-filter-remove').each( function() {
			taxonomy.push( $( this ).data( 'taxonomy' ) );
		});
		var data = {
			action:		'wpv_filter_taxonomy_delete',
			id:			self.view_id,
			taxonomy:	taxonomy,
			wpnonce:	nonce,
		};
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					$( self.post_row )
						.addClass( 'wpv-filter-deleted' )
						.animate({
							height: "toggle",
							opacity: "toggle"
						}, 400, function() {
							$( this ).remove();
							self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
							// We deleted the whole multiple filter, so we can clear the save queue
							self.clear_save_queue();
							$( document ).trigger( 'js_event_wpv_query_filter_deleted', [ 'taxonomy' ] );
							Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
						});
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
				}
			},
			error:		function( ajaxContext ) {

			},
			complete:	function() {

			}
		});
	};

	self.manage_taxonomy_relationship = function() {
		var items = $( '.js-wpv-filter-taxonomy-multiple-element' ).length;
		if ( items > 1 ) {
			$( '.js-wpv-filter-taxonomy-relationship' ).show();
		} else if ( items == 0 ) {
			$( '.js-wpv-filter-row-taxonomy' ).remove();
			if ( $( '.js-wpv-section-unsaved' ).length < 1 ) {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', false );
			}
		} else {
			$( '.js-wpv-filter-taxonomy-relationship' ).hide();
		}

		var readonlyItems = $( '.js-wpv-filter-taxonomy-multiple-element.js-wpv-filter-multiple-readonly-element' ).length;
		if ( readonlyItems > 0 ) {
			$( '.js-wpv-filter-remove-taxonomy' ).hide();
		} else {
			$( '.js-wpv-filter-remove-taxonomy' ).show();
		}

		return self;
	};

	//--------------------
	// Events
	//--------------------

	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select', function() {
		self.manage_filter_changes();
	});

	// Close and save

	self.save_filter_post_taxonomy = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_taxonomy', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row('.js-wpv-filter-row-taxonomy');
		} else {
			var valid = true;
			$( '.js-wpv-taxonomy-relationship' ).each( function() {
				var thiz_inner = $( this ),
				this_valid = true,
				tax_row = thiz_inner.parents('.js-wpv-filter-multiple-element').data('taxonomy');
				if ( thiz_inner.val() == 'FROM ATTRIBUTE' || thiz_inner.val() == 'FROM URL' ) {
					this_valid = WPViews.query_filters.validate_filter_options( '.js-wpv-filter-row-taxonomy-' + tax_row );
				} else {
					this_valid = WPViews.query_filters.validate_filter_options_value( 'select', thiz_inner );
					if ( this_valid == false ) {
						thiz_inner.addClass( 'filter-input-error' );
					}
				}
				if ( this_valid == false ) {
					valid = false;
				}
			});
			if ( valid ) {
				self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
				var nonce = thiz.data( 'nonce' ),
				spinnerContainer = $( self.spinner ).insertBefore( thiz ),
				error_container = $( self.post_row ).find( '.js-wpv-filter-multiple-toolset-messages' ),
				data = {
					action:				'wpv_filter_taxonomy_update',
					id:					self.view_id,
					filter_taxonomy:	self.post_current_options,
					wpnonce:			nonce
				};
				$.ajax( {
					type:		"POST",
					url:		ajaxurl,
					dataType:	"json",
					data:		data,
					success:	function( response ) {
						if ( response.success ) {
							$( document ).trigger( 'js_event_wpv_query_filter_saved', [ 'taxonomy' ] );
							$( '.js-wpv-filter-taxonomy-summary' ).html( response.data.summary );
							WPViews.query_filters.close_and_glow_filter_row( self.post_row, 'wpv-filter-saved' );
							Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
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
					},
					error:		function( ajaxContext ) {
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_taxonomy' );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					},
					complete:	function() {
						spinnerContainer.remove();
					}
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_taxonomy' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_taxonomy( 'js_event_wpv_save_filter_post_taxonomy_completed', false );
	});

	// Delete single items

	$( document ).on( 'click', '.js-wpv-filter-taxonomy-multiple-element .js-filter-remove', function() {
		var thiz = $( this ),
		row = thiz.parents( '.js-wpv-filter-taxonomy-multiple-element' ),
		li_item = thiz.parents( self.post_row ),
		taxonomy = thiz.data('taxonomy'),
		nonce = thiz.data('nonce'),
		spinnerContainer = $( self.spinner ).insertBefore( thiz ).hide(),
		error_container = row.find( '.js-wpv-filter-toolset-messages' ),
		data = {
			action:		'wpv_filter_taxonomy_delete',
			id:			self.view_id,
			taxonomy:	taxonomy,
			wpnonce:	nonce,
		};
		if ( li_item.find( '.js-wpv-filter-taxonomy-multiple-element' ).length == 1 ) {
			self.remove_taxonomy_filters();
		} else {
			spinnerContainer.show();
				$.ajax( {
				type:		"POST",
				url:		ajaxurl,
				dataType:	"json",
				data:		data,
				success:	function( response ) {
					if ( response.success ) {
						row
							.addClass( 'wpv-filter-multiple-element-removed' )
							.fadeOut( 500, function() {
								$( this ).remove();
								// We deleted just one filter instance, so we can not clear the save queue
								$( document ).trigger( 'js_event_wpv_query_filter_deleted', [ 'taxonomy' ] );
							});
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
					} else {
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
					}
				},
				error:		function( ajaxContext ) {

				},
				complete:	function() {
					spinnerContainer.remove();
				}
			});
		}
	});

	// Delete all items - open dialog if needed

	$( document ).on( 'click', '.js-wpv-filter-remove-taxonomy', function() {
		if ( $( self.post_row ).find( '.js-wpv-filter-taxonomy-multiple-element' ).length > 1 ) {
			var dialog_height = $(window).height() - 100;
			self.filter_dialog.dialog('open').dialog({
				maxHeight: dialog_height,
				draggable: false,
				resizable: false,
				position: { my: "center top+50", at: "center top", of: window }
			});
		} else {
			self.remove_taxonomy_filters();
		}
	});

	$( document ).on( 'change', '.js-wpv-taxonomy-relationship', function() {
		self.manage_taxonomy_mode( this );
	});

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if (
			filter_type == 'taxonomy'
			|| filter_type == 'post_category'
			|| filter_type.substr( 0, 9 ) == 'tax_input'
			|| filter_type == 'all'
		) {
			self.manage_filter_changes();
			self.manage_taxonomy_relationship();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_taxonomy', action: 'add' } );
		}

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-check-query-filter-list-existence' );
	});

	$( document ).on( 'js_event_wpv_query_filter_saved', function( event, filter_type ) {
		if (
			filter_type == 'taxonomy'
			|| filter_type == 'post_category'
			|| filter_type.substr( 0, 9 ) == 'tax_input'
			|| filter_type == 'all'
		) {
			self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
			self.manage_filter_changes();
			self.manage_taxonomy_relationship();
		}
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-check-query-filter-list-existence' );
	});

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if (
			filter_type == 'taxonomy'
			|| filter_type == 'post_category'
			|| filter_type.substr( 0, 9 ) == 'tax_input'
			|| filter_type == 'all'
		) {
			// As this is a multiple-instances filter, we do need to perform extra actions.
			// Note that we can not clear the save queue since the query filter, as a whole, has changed.
			self.manage_filter_changes()
				.manage_taxonomy_relationship();
		}
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-check-query-filter-list-existence' );
	});

	/**
	 * Clear the save queue from traces of this filter.
	 *
	 * @since 2.4.0
	 */
	self.clear_save_queue = function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_taxonomy', action: 'remove' } );
	}

	/**
	* init_dialogs
	*
	* Initialize the dialogs
	*
	* @since 1.10
	*/

	self.init_dialogs = function() {
		self.filter_dialog = $( "#js-wpv-filter-taxonomy-delete-filter-row-dialog" ).dialog({
			autoOpen: false,
			modal: true,
			title: wpv_category_filter_texts.dialog_title,
			minWidth: 600,
			show: {
				effect: "blind",
				duration: 800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-filters-taxonomy-delete-filter-row',
					text: wpv_category_filter_texts.delete_filters,
					click: function() {
						self.remove_taxonomy_filters();
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-secondary',
					text: wpv_category_filter_texts.edit_filters,
					click: function() {
						$( this ).dialog( "close" );
						WPViews.query_filters.open_filter_row( $( self.post_row ) );
					}
				},
				{
					class: 'button-secondary',
					text: wpv_category_filter_texts.cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});
	};

	//--------------------
	// Manage post taxonomy filter restrictions
	//--------------------

	/**
	* manage_post_taxonomy_filter_warning
	*
	* Add or remove the warning messages when:
	* 	- WPA - combining a query filter by post taxonomies and taxonomy archive loops
	*
	* @since 2.1
	*/

	self.manage_post_taxonomy_filter_warning = function() {
		var query_mode				= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
		post_taxonomy_filter_row	= $( self.post_row ),
		taxonomy_loops_selected		= [],
		taxonomy_query_filters		= [],
		taxonomy_issues				= [],
		auxiliar_string				= '',
		auxiliar_warning			= '';

		if ( post_taxonomy_filter_row.length > 0 ) {
			if ( 'normal' == query_mode ) {

			} else {
				$( '.js-wpv-archive-filter-post-taxonomy-disabled' ).remove();
				taxonomy_loops_selected = $( '.js-wpv-settings-archive-loop input[data-type="taxonomy"]:checked' ).map( function() {
					return $( this ).data( 'name' );
				}).get();
				taxonomy_query_filters = $( self.post_row + ' .js-wpv-filter-taxonomy-multiple-element' ).map( function() {
					return $( this ).data( 'taxonomy' );
				});
				taxonomy_issues = _.intersection( taxonomy_loops_selected, taxonomy_query_filters );
				if ( taxonomy_issues.length > 0 ) {
					auxiliar_string = taxonomy_issues.join( ', ' );
					auxiliar_warning = self.disabled_for_loop;
					auxiliar_warning = auxiliar_warning.replace( '%s', auxiliar_string );
					$( auxiliar_warning ).prependTo( self.post_row ).show();
				}
			}
		}
	};

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if (
			filter_type == 'post_category'
			|| filter_type.substr( 0, 9 ) == 'tax_input'
		) {
			self.manage_post_taxonomy_filter_warning();
		}
	});

	$( document ).on( 'js_event_wpv_save_section_loop_selection_completed', function( event ) {
		self.manage_post_taxonomy_filter_warning();
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.TaxonomyFilterGUI}
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
			handle:		'save_filter_post_taxonomy',
			callback:	self.save_filter_post_taxonomy,
			event:		'js_event_wpv_save_filter_post_taxonomy_completed'
		});
		// Register a callback to remove the filter form the save queue on demand
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-clear-query-filter-save-queue', self.clear_save_queue );

		/**
		 * Clears events and hooks.
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-filter-clear-events-and-hooks', self.clear_events_and_hooks );
	};

	//--------------------
	// Init
	//--------------------

	self.init = function() {
		self.manage_taxonomy_relationship();
		self.init_dialogs();
		self.manage_post_taxonomy_filter_warning();
		self.init_hooks();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.taxonomy_filter_gui = new WPViews.TaxonomyFilterGUI( $ );
});
