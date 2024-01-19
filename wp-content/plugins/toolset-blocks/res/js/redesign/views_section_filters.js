
var WPViews = WPViews || {};

WPViews.QueryFilters = function( $, id ) {
	var self = this;

	self.view_id	= id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.icon_edit	= '<i class="icon-chevron-up fa fa-chevron-up"></i>&nbsp;&nbsp;';
	self.icon_save	= '<i class="icon-ok fa fa-check"></i>&nbsp;&nbsp;';

	self.query_filter_dialog = null;

	self.selector_delete_simple_filter			= '.js-filter-row-simple .js-filter-remove';
	self.selector_delete_multiple_filter_one	= '';
	self.selector_delete_multiple_filter_all	= '';

	self.url_pattern				= /^[a-z0-9\-\_]+$/;
	self.shortcode_pattern			= /^[a-z0-9]+$/;
	self.year_pattern				= /^([0-9]{4})$/;
	self.month_pattern				= /^([1-9]|1[0-2])$/;
	self.week_pattern				= /^([1-9]|[1234][0-9]|5[0-3])$/;
	self.day_pattern				= /^([1-9]|[12][0-9]|3[0-1])$/;
	self.hour_pattern				= /^([0-9]|[1][0-9]|2[0-3])$/;
	self.minute_pattern				= /^([0-9]|[1234][0-9]|5[0-9])$/;
	self.second_pattern				= /^([0-9]|[1234][0-9]|5[0-9])$/;
	self.dayofyear_pattern			= /^([1-9]|[1-9][0-9]|[12][0-9][0-9]|3[0-6][0-6])$/;
	self.dayofweek_pattern			= /^[1-7]+$/;
	self.numeric_natural_pattern	= /^[0-9]+$/;

	self.validation_patterns		= {
		url:				/^[a-z0-9\-\_]+$/,
		shortcode:			/^[a-z0-9]+$/,
		year:				/^([0-9]{4})$/,
		month:				/^([1-9]|1[0-2])$/,
		week:				/^([1-9]|[1234][0-9]|5[0-3])$/,
		day:				/^([1-9]|[12][0-9]|3[0-1])$/,
		hour:				/^([0-9]|[1][0-9]|2[0-3])$/,
		minute:				/^([0-9]|[1234][0-9]|5[0-9])$/,
		second:				/^([0-9]|[1234][0-9]|5[0-9])$/,
		dayofyear:			/^([1-9]|[1-9][0-9]|[12][0-9][0-9]|3[0-6][0-6])$/,
		dayofweek:			/^[1-7]+$/,
		numeric_natural:	/^[0-9]+$/
	};

	// ---------------------------------
	// Dialogs
	// ---------------------------------

	/**
	* Temporary dialog content to be displayed while the actual content is loading.
	*
	* It contains a simple spinner in the centre. I decided to implement styling directly, it will not be reused and
	* it would only bloat views-admin.css (jan).
	*
	* @type {HTMLElement}
	* @since 1.9
	*/
	self.query_filter_dialog_loading = $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="wpv-spinner ajax-loader"></div>' +
		'<p>' + wpv_filters_strings.add_filter_dialog.loading + '</p>' +
		'</div>' +
		'</div>'
	);

	self.init_dialogs = function() {
		var dialog_height = $( window ).height() - 100;
		$( 'body' ).append( '<div id="js-wpv-filter-add-query-filter-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container"></div>' );
		self.query_filter_dialog = $( "#js-wpv-filter-add-query-filter-dialog" ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		wpv_filters_strings.add_filter_dialog.title,
			minWidth:	550,
			maxHeight:	dialog_height,
			draggable:	false,
			resizable:	false,
			position:	{ my: "center top+50", at: "center top", of: window },
			show:		{
				effect:		"blind",
				duration:	800
			},
			open:		function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.manage_filter_insert_button( false );
				$( '.js-filter-add-select' ).val( '-1' );
				var group = $( ".js-filter-add-select" ).find( "optgroup" );
				$.each( group, function( i, v ) {
					if ( $( v ).children().length === 0 ) {
						$( this ).remove();
					}
				});
			},
			close:		function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-filters-insert-filter',
					text: wpv_filters_strings.add_filter_dialog.insert,
					click: function() {

					}
				},
				{
					class: 'button-secondary',
					text: wpv_filters_strings.add_filter_dialog.cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});
		return self;
	};

	// ---------------------------------
	// Functions
	// ---------------------------------

	/**
	 * Helper method to check whether there are query filters already defined.
	 *
	 * @since 2.9
	 */
	self.hasQueryFilters = function() {
		return ( $( '.js-filter-list' ).find( '.js-filter-row' ).length > 0 );
	};

	/**
	* open_add_filter_dialog
	*
	* Open the dialog to create a new query filter.
	*
	* @since 2.1
	*/

	self.open_add_filter_dialog = function() {
		var query_mode = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
		data = {
			id:			self.view_id,
			wpnonce:	wpv_filters_strings.add_filter_nonce,
		};
		switch ( query_mode ) {
			case 'archive':
				data.action = 'wpv_filters_add_archive_query_filter_dialog';
				break;
			case 'normal':
			default:
				data.action = 'wpv_filters_add_query_filter_dialog';
				break;
		}

		self.query_filter_dialog.dialog( 'open' );
		self.query_filter_dialog.html( self.query_filter_dialog_loading );

		$.ajax({
			url:		ajaxurl,
			data:		data,
			type:		"GET",
			dataType:	"json",
			success:	function( response ) {
				if ( response.success ) {
					$( 'body' ).addClass( 'modal-open' );
					self.query_filter_dialog.html( response.data.dialog );
				}
			},
			complete:	function() {
				$( '.js-wpv-filter-add-filter' ).prop( 'disabled', false );
			}
		});
	};

	self.filters_exist = function() {
		var filters_count = $( '.js-filter-list' ).find( '.js-filter-row' ).length;
		if ( 0 ==  filters_count ) {
			$( '.js-filter-list' ).hide();
			$( '.js-no-filters' ).show();
			$( '.js-wpv-filter-add-filter' ).val( $( '.js-wpv-filter-add-filter' ).data( 'empty' ) );
		} else {
			$( '.js-filter-list' ).show();
			$( '.js-no-filters' ).hide();
			$( '.js-wpv-filter-add-filter' ).val( $( '.js-wpv-filter-add-filter' ).data( 'nonempty' ) );
		}
		return self;
	};

	self.open_filter_row = function( row ) {
		row.find( '.js-wpv-filter-edit-open' ).hide();
		row.find( '.js-wpv-filter-summary' ).hide();
		row.find( '.js-wpv-filter-edit' ).fadeIn('fast');
		row.find( '.js-wpv-filter-edit-ok' ).show();
		row.addClass( 'wpv-filter-row-current' );
	};

	self.first_open_filter_row = function( row ) {
		var thiz_row = $( row ),
		save_text = thiz_row.find( '.js-wpv-filter-edit-ok' ).data( 'save' );
		thiz_row.find( '.js-wpv-filter-edit' ).show();
		thiz_row.find( '.js-wpv-filter-summary, .js-wpv-filter-edit-open' ).hide();
		thiz_row.find( '.js-wpv-filter-edit-ok' )
			.show()
			.html( self.icon_save + save_text )
			.addClass('button-primary js-wpv-section-unsaved')
			.removeClass('button-secundary');
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		thiz_row.addClass( 'wpv-filter-row-current' );
	};

	self.close_filter_row = function( row ) { // general close filters editor - just aesthetic changes & no actions
		var thiz_row = $( row );
		thiz_row.find( '.js-wpv-filter-edit, .js-wpv-filter-edit-ok' ).hide();
		thiz_row.find( '.js-wpv-filter-summary, .js-wpv-filter-edit-open' ).show();
		thiz_row.removeClass( 'wpv-filter-row-current' );
	};

	self.glow_filter_row = function( row, reason ) {
		$( row ).addClass( reason );
		setTimeout( function () {
			$( row ).removeClass( reason );
		}, 500 );
	};

	self.close_and_glow_filter_row = function( row, reason ) {
		self.close_filter_row( row );
		self.glow_filter_row( row, reason );
	};

	self.validate_filter_options = function( row ) {
		var valid = true,
		thiz,
		filter_options_values = $( row ).find( '.js-wpv-filter-validate' );
		$( filter_options_values ).each( function() {
			thiz = $( this );
			thiz.removeClass( 'filter-input-error' );
			if ( ! self.validate_filter_options_value( thiz.data( 'type' ), thiz ) ) {
				thiz.addClass( 'filter-input-error' );
				valid = false;
			}
		});

		return valid;
	};

	self.validate_filter_options_value = function( type, selector ) {
		var input_valid = true,
		value = selector.val(),
		message = '',
		filter_error_container = selector.closest( '.js-wpv-filter-multiple-element, .js-filter-row' ).find( '.js-wpv-filter-toolset-messages' );
		switch ( type ) {
			case 'select':
				if ( value == '' ) {
					message = wpv_filters_strings.add_filter_dialog.select_empty;
					input_valid = false;
				}
				break;
			case 'url':
				if ( value == '' ) {
					message = wpv_filters_strings.validation.param_missing;
					input_valid = false;
				} else if ( self.validation_patterns[ 'url' ].test( value ) == false ) {
					message = wpv_filters_strings.validation.param_ilegal[ 'url' ];
					input_valid = false;
				} else if ( $.inArray( value, wpv_forbidden_parameters.wordpress ) > -1 ) {
					message = wpv_filters_strings.validation.param_forbidden_wordpress;
					input_valid = false;
				} else if ( $.inArray( value, wpv_forbidden_parameters.toolset ) > -1 ) {
					message = wpv_filters_strings.validation.param_forbidden_toolset;
					input_valid = false;
				} else if ( $.inArray( value, wpv_forbidden_parameters.post_type ) > -1 ) {
					message = wpv_filters_strings.validation.param_forbidden_post_type;
					input_valid = false;
				} else if ( $.inArray( value, wpv_forbidden_parameters.taxonomy ) > -1 ) {
					message = wpv_filters_strings.validation.param_forbidden_taxonomy;
					input_valid = false;
				}
				break;
			case 'shortcode':
				if ( value == '' ) {
					message = wpv_filters_strings.validation.param_missing;
					input_valid = false;
				} else if ( self.validation_patterns[ 'shortcode' ].test( value ) == false ) {
					message = wpv_filters_strings.validation.param_ilegal[ 'shortcode' ];
					input_valid = false;
				} else if ( $.inArray( value, wpv_forbidden_parameters.toolset_attr ) > -1 ) {
					message = wpv_filters_strings.validation.param_forbidden_toolset_attr;
					input_valid = false;
				}
				break;
			case 'year':
			case 'month':
			case 'week':
			case 'day':
			case 'hour':
			case 'minute':
			case 'second':
			case 'dayofyear':
			case 'dayofweek':
			case 'numeric_natural':
				if ( self.validation_patterns[ type ].test( value ) == false ) {
					message = wpv_filters_strings.validation.param_ilegal[type];
					input_valid = false;
				}
				break;
		}
		if ( ! input_valid ) {
			filter_error_container
				.wpvToolsetMessage({
					text: message,
					type: 'error',
					inline: false,
					stay: true
				});
			// Hack to allow more than one error message per filter
			filter_error_container
				.data( 'message-box', null )
				.data( 'has_message', false );
		}
		return input_valid;
	};

	self.clear_validate_messages = function( row ) {
		$( row )
			.find('.toolset-alert-error').not( '.js-wpv-permanent-alert-error' )
			.each( function() {
				$( this ).remove();
			});
		$( row )
			.find( '.filter-input-error' )
			.each( function() {
				$( this ).removeClass( 'filter-input-error' );
			});
	};

	self.manage_filter_insert_button = function( state ) {
		if ( state ) {
			$( '.js-filters-insert-filter' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		} else {
			$( '.js-filters-insert-filter' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
		}
	};

	// ---------------------------------
	// Events
	// ---------------------------------

	// Adding a filter

	$( document ).on( 'change', '.js-filter-add-select', function() {
		self.manage_filter_insert_button( $( this ).val() != '-1' );
	});

	$( document ).on( 'click', '.js-wpv-filter-add-filter', function() {
		var thiz = $( this );
		thiz.prop( 'disabled', true );
		self.open_add_filter_dialog();
	});

	$( document ).on( 'click','.js-filters-insert-filter', function() {
		var thiz = $( this ),
		filter_type = $( '#js-wpv-filter-add-query-filter-dialog .js-filter-add-select' ).val(),
		nonce = thiz.data( 'nonce' ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertBefore( thiz ).show(),
		query_mode = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
		data = {
			id:				self.view_id,
			wpnonce:		wpv_filters_strings.add_filter_nonce,
			filter_type:	filter_type
		};

		switch ( query_mode ) {
			case 'archive':
				data.action = 'wpv_filters_add_archive_filter_row';
				break;
			case 'normal':
			default:
				data.action = 'wpv_filters_add_filter_row';
				break;
		}

		thiz
			.addClass( 'button-secondary' )
			.removeClass( 'button-primary' )
			.prop( 'disabled', true );
		$.post( ajaxurl, data, function( response ) {
			if ( ( typeof( response ) !== 'undefined' ) ) {
				if ( filter_type == 'post_category' || filter_type.substr( 0, 9 ) == 'tax_input' ) {
					if ( $( '.js-wpv-filter-row-taxonomy' ).length > 0 ) {
						var filter_type_fixed = filter_type.replace( '[', '_' ).replace( ']', '' ),
						responseRow = $( '<div></div>' ).append( response ),
						responseUsable = responseRow.find( '.js-wpv-filter-taxonomy-multiple-element' );
						$( '.js-wpv-filter-row-taxonomy .js-wpv-filter-row-tax-' + filter_type_fixed ).remove();
						$('.js-wpv-filter-taxonomy-edit').prepend( responseUsable );
					} else {
						var responseRow = $('.js-filter-list').append( response );
					}
					self.first_open_filter_row( '.js-wpv-filter-row-taxonomy' );
				} else if (filter_type.substr(0, 12) == 'custom-field') {
					if ( $( '.js-wpv-filter-row-custom-field' ).length > 0 ) {
						var responseRow = $( '<div></div>' ).append( response ),
						responseUsable = responseRow.find( '.js-wpv-filter-custom-field-multiple-element' );
						$( '.js-wpv-filter-row-custom-field .js-wpv-filter-row-' + filter_type ).remove();
						$('.js-wpv-filter-custom-field-edit').prepend( responseUsable );
					} else {
						var responseRow = $('.js-filter-list').append( response );
					}
					self.first_open_filter_row( '.js-wpv-filter-row-custom-field' );
				} else if (filter_type.substr(0, 14) == 'usermeta-field') {
					if ( $( '.js-wpv-filter-row-usermeta-field' ).length > 0 ) {
						var responseRow = $( '<div></div>' ).append( response ),
						responseUsable = responseRow.find( '.js-wpv-filter-usermeta-field-multiple-element' );
						$( '.js-wpv-filter-row-usermeta-field .js-wpv-filter-row-' + filter_type ).remove();
						$('.js-wpv-filter-usermeta-field-edit').prepend( responseUsable );
					} else {
						var responseRow = $('.js-filter-list').append( response );
					}
					self.first_open_filter_row( '.js-wpv-filter-row-usermeta-field' );
				} else if (filter_type.substr(0, 14) == 'termmeta-field') {
					if ( $( '.js-wpv-filter-row-termmeta-field' ).length > 0 ) {
						var responseRow = $( '<div></div>' ).append( response ),
						responseUsable = responseRow.find( '.js-wpv-filter-termmeta-field-multiple-element' );
						$( '.js-wpv-filter-row-termmeta-field .js-wpv-filter-row-' + filter_type ).remove();
						$('.js-wpv-filter-termmeta-field-edit').prepend( responseUsable );
					} else {
						var responseRow = $('.js-filter-list').append( response );
					}
					self.first_open_filter_row( '.js-wpv-filter-row-termmeta-field' );
				} else if ( filter_type == 'post_date' ) {
					$( '.js-filter-list .js-filter-row-post-date' ).remove();
					var responseRow = $( '.js-filter-list' ).append( response );
					self.first_open_filter_row( '.js-filter-list .js-filter-row-post-date' );
				} else {
					$( '.js-filter-list .js-filter-row-' + filter_type ).remove();
					var responseRow = $( '.js-filter-list' ).append( response );
					self.first_open_filter_row( '.js-filter-list .js-filter-row-' + filter_type );
				}
				$( document ).trigger( 'js_event_wpv_query_filter_created', [ filter_type ] );
			}
		})
		.fail( function( jqXHR, textStatus, errorThrown ) {

		})
		.always( function() {
			spinnerContainer.remove();
			self.query_filter_dialog.dialog( 'close' );
		});
	});

	// Count filters

	$( document ).on( 'js_event_wpv_query_filter_created js_event_wpv_query_filter_saved js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		self.filters_exist();
		$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
	});

	// Remove simple filter

	$( document ).on( 'click', self.selector_delete_simple_filter, function() {
		var thiz = $( this ),
		row = thiz.closest( 'li.js-filter-row' ),
		filter = row.attr( 'id' ).substring( 7 ),
		nonce = thiz.data( 'nonce' ),
		action = 'wpv_filter_' + filter + '_delete',
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertBefore( thiz ).show(),
		error_container = row.find( '.js-wpv-filter-toolset-messages' );
		data = {
			action:		action,
			id:			self.view_id,
			wpnonce:	nonce,
		};
		$.post( ajaxurl, data, function( response ) {
			if ( response.success ) {
				row.find( '.js-wpv-filter-edit-ok' ).removeClass( 'js-wpv-section-unsaved' );
				row
					.addClass( 'wpv-filter-deleted' )
					.fadeOut( 500, function() {
						$( this ).remove();
						$( document ).trigger( 'js_event_wpv_query_filter_deleted', [ filter ] );
					});
				$( '.js-filter-add-select' ).val( '-1' );
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container} );
			}
		}, 'json' )
		.fail( function( jqXHR, textStatus, errorThrown ) {

		})
		.always( function() {
			spinnerContainer.remove();
		});
	});

	$( document ).on( 'click', '.js-wpv-filter-edit-open', function() { // open filters editor - common for all filters
		var thiz = $( this ),
		row = thiz.parents( '.js-filter-row' );
		self.open_filter_row( row );
	});

	$( document ).on( 'js_event_wpv_query_type_options_saved', '.js-wpv-query-type-update', function() {
		self.filters_exist();
		// @todo the save queue might need to be cleared from query filters here, or some kind of cache of pending filters should be stored
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.QueryFilters}
	 */
	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', '.js-wpv-filter-add-filter' );
		$( document ).off( 'click', '.js-filters-insert-filter' );
		$( document ).off( 'click', self.selector_delete_simple_filter );
		return self;
	}

	// ---------------------------------
	// API for Query Filters
	// ---------------------------------

	/**
	 * Replace the query filters list on demand.
	 *
	 * @since 2.4.0
	 */

	self.replace_filters_list = function( new_list ) {
		$( '.js-filter-list' ).html( new_list );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-check-query-filter-list-existence' );
		/**
		* Clear the edit page save queue from query filter items, as the query filters list has been refreshed.
		* Each query filter should add a callback to this action to restart its tracking data and eventually remove from the save queue.
		*
		* @since 2.4.0
		*/
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-clear-query-filter-save-queue' );
		return self;
	}

	self.update_filters_list = function() {
		var data = {
			action: 'wpv_filter_update_filters_list',
			id: $( '.js-post_ID' ).val(),
			nonce: $( '.js-wpv-filter-update-filters-list-nonce' ).val()
		};
		$.post( ajaxurl, data, function( response ) {
			if ( ( typeof( response ) !== 'undefined' ) ) {
				decoded_response = JSON.parse( response );
				if ( decoded_response.success === data.id ) {
					$( '.js-filter-list' ).html( decoded_response.wpv_filter_update_filters_list );
					$( document ).trigger( 'js_event_wpv_query_filter_saved', [ 'all' ] );
				}
			} else {

			}
		})
		.fail(function(jqXHR, textStatus, errorThrown) {

		})
		.always(function() {

		});
	};

	// ---------------------------------
	// Hooks
	// ---------------------------------

	self.init_hooks = function() {

		/**
		* Refresh the query filters list existence.
		*
		* @since	2.4.0
		*/
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-check-query-filter-list-existence', self.filters_exist );

		/**
		* Refresh the query filters list with whatever string gets passed to this action.
		*
		* @note		After refreshing the list, the action wpv-action-wpv-edit-screen-check-query-filter-list-existence will be fired.
		* @note		After refreshing the list, the action wpv-action-wpv-edit-screen-clear-query-filter-save-queue will be fired.
		* @since	2.4.0
		*/
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-replace-query-filter-list', self.replace_filters_list );

		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-update-query-filter-list', self.update_filters_list );

		/**
		 * Clears events and hooks.
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-filter-clear-events-and-hooks', self.clear_events_and_hooks );

		return self;

	};

	// ---------------------------------
	// Init
	// ---------------------------------

	self.init = function() {
		// Fire init methods
		self.init_hooks()
			.init_dialogs();

		// Fire init actions
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-check-query-filter-list-existence' );

	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.query_filters = new WPViews.QueryFilters( $ );
});

//----------------------------
// Parametric search and results update settings
//----------------------------

WPViews.ParametricSearchGUI = function( $ ) {

	var self		= this;
	self.view_id	= $( '.js-post_ID' ).val();

	// Parametric search data
	self.helper_mode_val = $( '.js-wpv-dps-mode-helper:checked' ).val(),
	self.update_mode_val = $( '.js-wpv-dps-ajax-results:checked' ).val(),
	self.update_submit_action_val = $( '.js-wpv-ajax-results-submit:checked' ).val(),
	self.dps_mode_val = $( '.js-wpv-dps-enable:checked' ).val();

	$( document ).on( 'change', '.js-wpv-dps-mode-helper', function() {
		self.helper_mode_val = $( '.js-wpv-dps-mode-helper:checked' ).val();
		if ( self.helper_mode_val == 'custom' ) {
			$( '.js-wpv-ps-settings-custom' ).fadeIn();
		} else {
			$( '.js-wpv-ps-settings-custom' ).hide();
			self.wpv_dps_adjust_settings_by_mode( self.helper_mode_val );
		}
	});

	$( document ).on( 'change', '.js-wpv-dps-ajax-results', function() {
		self.update_mode_val = $( '.js-wpv-dps-ajax-results:checked' ).val();
		self.wpv_dps_showhide_javascript_settings();
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-parametric-filter-buttons-handle-flags' );
		$( '.js-wpv-dps-ajax-results-extra' ).hide();
		if ( self.update_mode_val == 'disable' ) {
			$( '.js-wpv-dps-ajax-results-extra-disable' ).fadeIn();
		}
	});

	$( document ).on( 'change', '.js-wpv-ajax-results-submit', function() {
		self.update_submit_action_val = $( '.js-wpv-ajax-results-submit:checked' ).val();
		self.wpv_dps_showhide_javascript_settings();
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-parametric-filter-buttons-handle-flags' );
	});

	$( document).on( 'change', '.js-wpv-dps-enable', function() {
		self.dps_mode_val = $( '.js-wpv-dps-enable:checked' ).val()
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-parametric-filter-buttons-handle-flags' );
		if ( self.dps_mode_val == 'disable' ) {
			$( '.js-wpv-dps-crossed-details' ).hide();
		} else {
			$( '.js-wpv-dps-crossed-details' ).fadeIn();
		}
	});

	$( document ).on( 'click', '.js-make-intersection-filters', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertBefore( thiz ).show(),
		data = {
			action: 'wpv_filter_make_intersection_filters',
			id: $( '.js-post_ID' ).val(),
			nonce: thiz.data( 'nonce' )
		};
		$.post( ajaxurl, data, function( response ) {
			if ( ( typeof( response ) !== 'undefined' ) ) {
				decoded_response = JSON.parse( response );
				if ( decoded_response.success === data.id ) {
					$( '.js-filter-list' ).html( decoded_response.wpv_filter_update_filters_list );
					$( '.js-wpv-dps-intersection-fail' ).hide();
					$( '.js-wpv-dps-intersection-ok' ).show();
					$( document ).trigger( 'js_event_wpv_query_filter_saved', [ 'all' ] );
				}
			} else {

			}
		})
		.fail(function(jqXHR, textStatus, errorThrown) {

		})
		.always(function() {
			spinnerContainer.remove();
		});
	});

	self.wpv_dps_adjust_settings_by_mode = function( mode ) {
		if ( mode == '' ) {
			// Only update on submit, reload page on submit, disablenable dependency
			$( '.js-wpv-dps-ajax-results-disable, .js-wpv-ajax-results-submit-reload, .js-wpv-dps-enable-disable' ).trigger( 'click' );
		} else if ( mode == 'fullrefreshonsubmit' ) {
			// Only update on submit, reload page on submit, enable dependency
			$( '.js-wpv-dps-ajax-results-disable, .js-wpv-ajax-results-submit-reload, .js-wpv-dps-enable-enable' ).trigger( 'click' );
			// Show only available options for each input - enable dependency
			$( '.js-wpv-dps-empty-select[value="hide"], .js-wpv-dps-empty-multi-select[value="hide"], .js-wpv-dps-empty-radios[value="hide"], .js-wpv-dps-empty-checkboxes[value="hide"]' ).prop( 'checked', true );
		} else if ( mode == 'ajaxrefreshonsubmit' ) {
			// Only update on submit, AJAX on submit, enable dependency
			$( '.js-wpv-dps-ajax-results-disable, .js-wpv-ajax-results-submit-ajaxed, .js-wpv-dps-enable-enable' ).trigger( 'click' );
			// Show only available options for each input - enable dependency
			$( '.js-wpv-dps-empty-select[value="hide"], .js-wpv-dps-empty-multi-select[value="hide"], .js-wpv-dps-empty-radios[value="hide"], .js-wpv-dps-empty-checkboxes[value="hide"]' ).prop( 'checked', true );
		} else if ( mode == 'ajaxrefreshonchange' ) {
			// Update on change, enable dependency - do not care about submit as it will be hidden
			$( '.js-wpv-dps-ajax-results-enable, .js-wpv-dps-enable-enable' ).trigger( 'click' );
			// Show only available options for each input
			$( '.js-wpv-dps-empty-select[value="hide"], .js-wpv-dps-empty-multi-select[value="hide"], .js-wpv-dps-empty-radios[value="hide"], .js-wpv-dps-empty-checkboxes[value="hide"]' ).prop( 'checked', true );
		}
	};

	self.wpv_dps_showhide_javascript_settings = function() {
		if ( self.update_mode_val == 'enable' || self.update_submit_action_val == 'ajaxed' ) {
			$( '.js-wpv-ajax-extra-callbacks' ).fadeIn();
		} else {
			$( '.js-wpv-ajax-extra-callbacks' ).hide();
		}
	}

	// ---------------------------------
	// Manage parametric search hints
	// ---------------------------------

	self.manage_parametric_search_hints = function( parametric_hints_data ) {
		var existence_container		= $( '.js-wpv-no-filters-container' ),
		intersection_container		= $( '.js-wpv-dps-intersection-fail' ),
		intersection_container_ok	= $( '.js-wpv-dps-intersection-ok' ),
		missing_container			= $( '.js-wpv-missing-filter-container' ),
		query_type					= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-type', 'posts' ),
		purpose						= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-purpose', '' );

		if ( parametric_hints_data.existence != '' ) {
			existence_container.html( parametric_hints_data.existence );
			if (
				query_type === 'posts'
				&& purpose === 'parametric'
			) {
				existence_container.fadeIn( 'fast' );
			} else {
				existence_container.hide();
			}
		} else {
			existence_container.hide();
		}
		if ( parametric_hints_data.intersection != '' ) {
			intersection_container.html( parametric_hints_data.intersection );
			if ( query_type === 'posts' ) {
				intersection_container.fadeIn( 'fast' );
				intersection_container_ok.hide();
			}
		} else {
			intersection_container.hide();
			intersection_container_ok.fadeIn( 'fast' );
		}
		if ( parametric_hints_data.missing != '' ) {
			missing_container.html( parametric_hints_data.missing );
			if ( query_type === 'posts' ) {
				missing_container.fadeIn( 'fast' );
			}
		} else {
			missing_container.hide();
		}

	};

	self.get_parametric_search_hints = function() {
		var data = {
			action:		'wpv_get_parametric_search_hints',
			id:			self.view_id,
			wpnonce:	wpv_filters_strings.nonce
		};
		$.ajax( {
			type:		"POST",
			url:		ajaxurl,
			dataType:	"json",
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					self.manage_parametric_search_hints( response.data.parametric );
				}
			},
			error:		function( ajaxContext ) {

			},
			complete:	function() {

			}
		});
	};

	$( document ).on( 'js_event_wpv_screen_options_saved js_event_wpv_query_type_saved', function() {
		self.get_parametric_search_hints();
	});

	$( document ).on( 'click', '.js-wpv-filter-missing-delete', function( e ) {
		e.preventDefault();
		var thiz			= $( this ),
		spinnerContainer	= $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
		missing_filters		= {
			cf:		[],
			tax:	[],
			rel:	[],
			search:	[]
		},
		thiz_type,
		thiz_name;

		thiz
			.addClass( 'button-secondary' )
			.removeClass( 'button-primary' )
			.prop( 'disabled', true );

		$( '.js-wpv-filter-missing' ).find( 'li' ).each( function() {
			thiz_type = $( this ).data( 'type' );
			this_name = $( this ).data( 'name' );
			missing_filters[ thiz_type ].push( this_name );
		});

		var data = {
			action:		'wpv_remove_filter_missing',
			id:			self.view_id,
			filters:	missing_filters,
			wpnonce:	wpv_filters_strings.nonce
		};

		$.ajax( {
			type:		"POST",
			url:		ajaxurl,
			dataType:	"json",
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					$( '.js-filter-list' ).html( response.data.updated_filters_list );
					$( document ).trigger( 'js_event_wpv_query_filter_deleted', [ 'all' ] );
					thiz
						.closest( '.js-wpv-missing-filter-container' )
							.html( '' )
							.hide();
					self.manage_parametric_search_hints( response.data.parametric );
				}
			},
			error:		function( ajaxContext ) {

			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	});

	$( document ).on( 'click', '.js-wpv-filter-missing-close', function( e ) {
		e.preventDefault();
		var thiz = $( this );
		thiz
			.closest( '.js-wpv-missing-filter-container' )
				.html('')
				.hide();
	});

	// ---------------------------------
	// Init hooks
	// ---------------------------------

	self.init_hooks = function() {

		// Manage parametric search hints
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-get-parametric-search-hints', self.get_parametric_search_hints );
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', self.manage_parametric_search_hints );

		return self;
	};

	// ---------------------------------
	// Init
	// ---------------------------------

	self.init = function() {
		self.init_hooks();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.parametric_search_gui = new WPViews.ParametricSearchGUI( $ );
});
