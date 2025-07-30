/**
* Views Status Filter GUI - script
*
* Adds basic interaction for the Status Filter
*
* @package Views
*
* @since 1.7.0
*/

var WPViews = WPViews || {};

WPViews.StatusFilterGUI = function( $, id ) {

	var self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.post_row = '.js-wpv-filter-row-post-status';
	self.post_options_container_selector = '.js-wpv-filter-post-status-options';
	self.post_summary_container_selector = '.js-wpv-filter-post-status-summary';
	self.post_edit_open_selector = '.js-wpv-filter-post-status-edit-open';
	self.post_close_save_selector = '.js-wpv-filter-post-status-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input' ).serialize();

	//--------------------
	// Events for status
	//--------------------

	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file

	$( document ).on( 'click', self.post_edit_open_selector, function() {
		self.post_current_options = $( self.post_options_container_selector + ' input' ).serialize();
		$( self.post_close_save_selector ).show();
		$( self.post_row ).addClass( 'wpv-filter-row-current' );
	});

	// Track changes in options

	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input', function() { // watch on inputs change
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_status', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_status', action: 'remove' } );
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

	self.save_filter_post_status = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_status', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
			// We need to set the action button to "Edit" because on newly added filters and no status selected there is no changes in options, hence no saving
			$( self.post_close_save_selector )
				.addClass('button-secondary')
				.removeClass('button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data('close')
				);
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		} else {
			var action = thiz.data( 'saveaction' ),
			nonce = thiz.data('nonce'),
			spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
			error_container = thiz
					.closest( '.js-filter-row' )
						.find( '.js-wpv-filter-toolset-messages' );
			self.post_current_options = $( self.post_options_container_selector + ' input' ).serialize();
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
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_status' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			})
			.always( function() {
				spinnerContainer.remove();
				thiz.hide();
			});
		}
	}

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_status( 'js_event_wpv_save_filter_post_status_completed', false );
	});

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_status' ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_status', action: 'add' } );
		}
	});

	// Remove filter from the save queue an clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_status' == filter_type ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_status', action: 'remove' } );
		}
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.StatusFilterGUI}
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
			handle:		'save_filter_post_status',
			callback:	self.save_filter_post_status,
			event:		'js_event_wpv_save_filter_post_status_completed'
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
		self.init_hooks();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.status_filter_gui = new WPViews.StatusFilterGUI( $ );
});
