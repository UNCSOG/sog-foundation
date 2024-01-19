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

WPViews.TaxonomyTermFilterGUI = function( $, id ) {
	var self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.tax_row = '.js-wpv-filter-row-taxonomy-term';
	self.tax_options_container_selector = '.js-wpv-filter-taxonomy-term-options';
	self.tax_summary_container_selector = '.js-wpv-filter-taxonomy-term-summary';
	self.tax_edit_open_selector = '.js-wpv-filter-taxonomy-term-edit-open';
	self.tax_close_save_selector = '.js-wpv-filter-taxonomy-term-edit-ok';

	self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select').serialize();

	//--------------------
	// Functions for taxonomy term
	//--------------------

	self.show_hide_term_list = function() {
		var mode = $( '.js-wpv-taxonomy-term-mode:checked' ).val();
		if ( mode == 'THESE' ) {
			$( '.js-taxonomy-term-checklist' ).show();
		} else {
			$( '.js-taxonomy-term-checklist' ).hide();
		}
	};

	//--------------------
	// Events for taxonomy term
	//--------------------

	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file

	$( document ).on( 'click', self.tax_edit_open_selector, function() {
		self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize();
		$( self.tax_close_save_selector ).show();
		$( self.tax_row ).addClass( 'wpv-filter-row-current' );
	});

	// Track changes

	$( document ).on( 'change keyup input cut paste', self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select', function() {
		WPViews.query_filters.clear_validate_messages( self.tax_row );
		if ( self.tax_current_options != $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_term', action: 'add' } );
			$( self.tax_close_save_selector )
				.addClass( 'button-primary js-wpv-section-unsaved' )
				.removeClass( 'button-secondary' )
				.html(
					WPViews.query_filters.icon_save + $( self.tax_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_term', action: 'remove' } );
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

	self.save_filter_taxonomy_term = function( event, propagate ) {
		var thiz = $( self.tax_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.tax_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_term', action: 'remove' } );

		if ( self.tax_current_options == $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.tax_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-taxonomy-term' );
			if ( valid ) {
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
				}
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
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_taxonomy_term' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				})
				.always( function() {
					spinnerContainer.remove();
					thiz.hide();
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_taxonomy_term' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.tax_close_save_selector, function() {
		self.save_filter_taxonomy_term( 'js_event_wpv_save_filter_taxonomy_term_completed', false );
	});

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'taxonomy_term' ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_term', action: 'add' } );
		}
	});

	// Remove filter from the save queue an clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'taxonomy_term' == filter_type ) {
			self.tax_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_term', action: 'remove' } );
		}
	});

	// Show or hide the terms listStyleType
	$( document ).on( 'change', '.js-wpv-taxonomy-term-mode', function() {
		self.show_hide_term_list();
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.TaxonomyTermFilterGUI}
	 */
	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', self.tax_close_save_selector );
		return self;
	}

	//--------------------
	// Init hooks
	//--------------------

	self.init_hooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_taxonomy_term',
			callback:	self.save_filter_taxonomy_term,
			event:		'js_event_wpv_save_filter_taxonomy_term_completed'
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
		self.show_hide_term_list();
		self.init_hooks();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.taxonomy_term_filter_gui = new WPViews.TaxonomyTermFilterGUI( $ );
});
