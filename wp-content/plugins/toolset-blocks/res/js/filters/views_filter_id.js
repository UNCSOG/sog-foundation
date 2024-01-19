/**
* Views ID Filter GUI - script
*
* Adds basic interaction for the ID Filter
*
* @package Views
*
* @since 1.7.0
*/

var WPViews = WPViews || {};

WPViews.IDFilterGUI = function( $, id ) {
	const self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.post_row = '.js-wpv-filter-row-post-id';
	self.post_options_container_selector = '.js-wpv-filter-post-id-options';
	self.post_summary_container_selector = '.js-wpv-filter-post-id-summary';
	self.post_edit_open_selector = '.js-wpv-filter-post-id-edit-open';
	self.post_close_save_selector = '.js-wpv-filter-post-id-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	//--------------------
	// Events for ID
	//--------------------

	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file

	$( document ).on( 'click', self.post_edit_open_selector, function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		$( self.post_close_save_selector ).show();
		$( self.post_row ).addClass( 'wpv-filter-row-current' );
	} );

	// Track changes in options

	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select', function() {
		$( this ).removeClass( 'filter-input-error' );
		$( self.post_close_save_selector ).prop( 'disabled', false );
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_id', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass( 'button-primary js-wpv-section-unsaved' )
				.removeClass( 'button-secondary' )
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data( 'save' )
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_id', action: 'remove' } );
			$( self.post_close_save_selector )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary js-wpv-section-unsaved' )
				.html(
					WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data( 'close' )
				);
			$( self.post_close_save_selector )
				.parent()
				.find( '.unsaved' )
				.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	} );

	// Save filter options

	self.save_filter_post_id = function( event, propagate ) {
		const thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_id', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
		} else {
			const valid = WPViews.query_filters.validate_filter_options( '.js-filter-post-id' );
			if ( valid ) {
				const action = thiz.data( 'saveaction' ),
					nonce = thiz.data( 'nonce' ),
					spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
					error_container = thiz
						.closest( '.js-filter-row' )
						.find( '.js-wpv-filter-toolset-messages' );
				self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
				const data = {
					action:	action,
					id:	self.view_id,
					filter_options:	self.post_current_options,
					wpnonce:	nonce,
				};
				$.post( ajaxurl, data, function( response ) {
					if ( response.success ) {
						$( self.post_close_save_selector )
							.addClass( 'button-secondary' )
							.removeClass( 'button-primary js-wpv-section-unsaved' )
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
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container } );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					}
				}, 'json' )
					.fail( function( jqXHR, textStatus, errorThrown ) {
						console.log( 'Error: ', textStatus, errorThrown );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_id' );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					} )
					.always( function() {
						spinnerContainer.remove();
						thiz.hide();
					} );
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_id' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_id( 'js_event_wpv_save_filter_post_id_completed', false );
	} );

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_id' ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_id', action: 'add' } );
		}
	} );

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.IDFilterGUI}
	 */
	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', self.post_close_save_selector );
		return self;
	}

	// Remove ID filter from the save queue and clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_id' == filter_type ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_id', action: 'remove' } );
		}
	} );

	//--------------------
	// Init hooks
	//--------------------

	self.init_hooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:	'save_filter_post_id',
			callback:	self.save_filter_post_id,
			event:	'js_event_wpv_save_filter_post_id_completed',
		} );

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
	WPViews.id_filter_gui = new WPViews.IDFilterGUI( $ );
} );
