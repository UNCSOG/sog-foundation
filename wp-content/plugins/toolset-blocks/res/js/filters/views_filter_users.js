/**
* Views Users Filter GUI - script
*
* Adds basic interaction for the users Filter
*
* @package Views
*
* @since 1.7.0
*/

var WPViews = WPViews || {};

WPViews.UsersFilterGUI = function( $, id ) {

	var self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.user_row = '.js-wpv-filter-row-users';
	self.user_options_container_selector = '.js-wpv-filter-users-options';
	self.user_summary_container_selector = '.js-wpv-filter-users-summary';
	self.user_edit_open_selector = '.js-wpv-filter-users-edit-open';
	self.user_close_save_selector = '.js-wpv-filter-users-edit-ok';

	self.user_current_options = $( self.user_options_container_selector + ' input, ' + self.user_options_container_selector + ' select' ).serialize();

	//--------------------
	// Functions for users
	//--------------------

	// @todo make this use select2

	self.wpv_users_suggest = function() {
		var text_noresult = $('.js-wpv-user-suggest-values').data('noresult'),
		text_hint = $('.js-wpv-user-suggest-values').data('hinttext'),
		text_search = $('.js-wpv-user-suggest-values').data('search'),
		users = $('.js-wpv-user-suggest-values').data('users');
		$(".js-users-suggest-id").tokenInput( wpv_filter_users_texts.ajaxurl + '&action=wpv_suggest_users&view_id='+self.view_id, {
			theme: "wpv",
			preventDuplicates: true,
			hintText: text_hint,
			noResultsText: text_noresult,
			searchingText: text_search,
			prePopulate: users,
			onAdd: function (item) {
				var tokens = $(this).tokenInput('get');
				var user_val = '';
				$.each(tokens, function (index, value) {
					user_val += value.name+', ';
				});
				user_val = user_val.substr(0,(user_val.length - 2));
				$('.js-users-suggest').val(user_val);

			 },
			 onDelete: function (item) {
				var tokens = $(this).tokenInput('get');
				var user_val = '';
				$.each(tokens, function (index, value) {
					user_val += value.name+', ';
				});
				user_val = user_val.substr(0,(user_val.length - 2));
				$('.js-users-suggest').val(user_val);
			 }
		});
	}

	//--------------------
	// Events for users
	//--------------------

	// Open the edit box and rebuild the current values; show the close/save button-primary
	// TODO maybe the show() could go to the general file

	$( document ).on( 'click', self.user_edit_open_selector, function() {
		self.post_current_options = $( self.user_options_container_selector + ' input, ' + self.user_options_container_selector + ' select' ).serialize();
		$( self.user_close_save_selector ).show();
		$( self.user_row ).addClass( 'wpv-filter-row-current' );
	});

	// Track changes in options

	$( document ).on( 'change keyup input cut paste', self.user_options_container_selector + ' input, ' + self.user_options_container_selector + ' select', function() {
		$( this ).removeClass( 'filter-input-error' );
		$( self.user_close_save_selector ).prop( 'disabled', false );
		WPViews.query_filters.clear_validate_messages( self.user_row );
		if ( self.user_current_options != $( self.user_options_container_selector + ' input, ' + self.user_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_users', action: 'add' } );
			$( self.user_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.user_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_users', action: 'remove' } );
			$( self.user_close_save_selector )
				.addClass('button-secondary')
				.removeClass('button-primary js-wpv-section-unsaved')
				.html(
					WPViews.query_filters.icon_edit + $( self.user_close_save_selector ).data('close')
				);
			$( self.user_close_save_selector )
				.parent()
					.find( '.unsaved' )
					.remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	});

	// Save filter options

	self.save_filter_users = function( event, propagate ) {
		var thiz = $( self.user_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.user_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_users', action: 'remove' } );

		if ( self.user_current_options == $( self.user_options_container_selector + ' input, ' + self.user_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.user_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-users' );
			if ( valid ) {
				var action = thiz.data( 'saveaction' ),
				nonce = thiz.data('nonce'),
				spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
				error_container = thiz
					.closest( '.js-filter-row' )
						.find( '.js-wpv-filter-toolset-messages' );
				self.user_current_options = $( self.user_options_container_selector + ' input, ' + self.user_options_container_selector + ' select' ).serialize();
				var data = {
					action:			action,
					id:				self.view_id,
					filter_options:	self.user_current_options,
					wpnonce:		nonce
				};
				$.post( wpv_filter_users_texts.ajaxurl, data, function( response ) {
					if ( response.success ) {
						$( self.user_close_save_selector )
							.addClass('button-secondary')
							.removeClass('button-primary js-wpv-section-unsaved')
							.html(
								WPViews.query_filters.icon_edit + $( self.user_close_save_selector ).data( 'close' )
							);
						$( self.user_summary_container_selector ).html( response.data.summary );
						WPViews.query_filters.close_and_glow_filter_row( self.user_row, 'wpv-filter-saved' );
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
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_users' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				})
				.always( function() {
					spinnerContainer.remove();
					thiz.hide();
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_users' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.user_close_save_selector, function() {
		self.save_filter_users( 'js_event_wpv_save_filter_users_completed', false );
	});

	// Remove filter from the save queue an clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'users' == filter_type ) {
			self.user_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_users', action: 'remove' } );
		}
	});

	// Initialize suggest if needed

	$( document ).on( 'click', self.user_edit_open_selector, function() {
		if ( typeof( $( '.token-input-list-wpv' ).html() ) === 'undefined' ) {
			self.wpv_users_suggest();
		}

	});

	// Content selection section saved event

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( 'users' == filter_type ) {
			self.wpv_users_suggest();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_users', action: 'add' } );
		}
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.UsersFilterGUI}
	 */
	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', self.user_close_save_selector );
		return self;
	}

	//--------------------
	// Init hooks
	//--------------------

	self.init_hooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_users',
			callback:	self.save_filter_users,
			event:		'js_event_wpv_save_filter_users_completed'
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
		self.wpv_users_suggest();
		self.init_hooks();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.users_filter_gui = new WPViews.UsersFilterGUI( $ );
});
