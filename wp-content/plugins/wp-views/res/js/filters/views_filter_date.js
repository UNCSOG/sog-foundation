/**
* Views Date Filter GUI - script
*
* Adds basic interaction for the Date Filter
*
* @package Views
*
* @since 1.8.0
*/

var WPViews = WPViews || {};

WPViews.DateFilterGUI = function( $, id ) {
	const self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner		= '<span class="wpv-spinner ajax-loader">';

	self.disabled_for_loop			= '<p class="js-wpv-archive-filter-post-date-disabled js-wpv-permanent-alert-error toolset-alert toolset-alert-error"><i class="fa fa-warning" aria-hidden"true"=""></i> ' + wpv_date_strings.archive.disable_post_date_filter + '</p>';

	self.post_row							= '.js-wpv-filter-row-post-date';
	self.post_options_container_selector	= '.js-wpv-filter-row-post-date .js-wpv-filter-edit';
	self.post_edit_open_selector			= '.js-wpv-filter-post-date-edit-open';
	self.post_close_save_selector			= '.js-wpv-filter-post-date-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	self.condition_template = false;

	self.single_operators = [ '=', '!=', '<', '<=', '>', '>=' ];
	self.group_operators = [ 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ];
	self.group_operators_with_buttons = [ 'IN', 'NOT IN' ];
	self.group_operators_without_buttons = [ 'BETWEEN', 'NOT BETWEEN' ];

	//--------------------
	// Functions
	//--------------------

	// Track filter changes

	self.manage_filter_changes = function() {
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_date', action: 'add' } );
			$( self.post_close_save_selector )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary js-wpv-section-unsaved' )
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data( 'save' )
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_date', action: 'remove' } );
			$( self.post_close_save_selector )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary js-wpv-section-unsaved' )
				.html(
					WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data( 'close' )
				);
			$( self.post_row ).find( '.unsaved' ).remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};

	// Manage extra buttons for grouped conditions - remove and add another value buttons

	self.date_condition_operator_show_settings = function( condition_row ) {
		const thiz_operator = condition_row.find( '.js-wpv-date-condition-operator' ).val(),
			thiz_single_settings = condition_row.find( '.js-wpv-date-condition-single' ),
			thiz_group_settings = condition_row.find( '.js-wpv-date-condition-group' );
		if ( $.inArray( thiz_operator, self.single_operators ) !== -1 ) {
			thiz_single_settings.fadeIn();
			thiz_group_settings.hide();
		} else if ( $.inArray( thiz_operator, self.group_operators ) !== -1 ) {
			thiz_single_settings.hide();
			thiz_group_settings.fadeIn();
			const thiz_group_buttons_add = condition_row.find( '.js-wpv-date-condition-group-value-add' ),
				thiz_group_buttons_delete = condition_row.find( '.js-wpv-date-condition-group-value-delete' );
			if ( $.inArray( thiz_operator, self.group_operators_with_buttons ) !== -1 ) {
				thiz_group_buttons_add.show();
				thiz_group_buttons_delete.prop( 'disable', false ).show();
				$( thiz_group_buttons_delete[ 0 ] ).hide();
			} else {
				thiz_group_buttons_add.hide();
				thiz_group_buttons_delete.hide();
				let cond_group_values = condition_row.find( '.js-wpv-filter-date-condition-group-value' ),
					cond_group_values_length = cond_group_values.length,
					cond_group_values_max = 2;
				if ( cond_group_values_length == 1 ) {
					const clone = $( cond_group_values[ 0 ] ).clone();
					clone.find( 'select.js-wpv-filter-date-origin' ).val( 'constant' );
					clone.find( 'input.js-wpv-filter-date-data' ).val( '' ).show();
					clone.insertAfter( cond_group_values[ 0 ] );
				} else if ( cond_group_values_length > 2 ) {
					cond_group_values.each( function() {
						if ( cond_group_values_max > 0 ) {
							$( this ).show();
						} else {
							$( this ).remove();
						}
						cond_group_values_max--;
					} );
				}
			}
		}
	};

	// Remove post date filter

	self.remove_post_date_filter = function() {
		$( self.post_close_save_selector ).removeClass( 'js-wpv-section-unsaved' );
		const nonce = $( '.js-wpv-filter-remove-post-date' ).data( 'nonce' ),
			taxonomy = [],
			spinnerContainer = $( self.spinner ).insertBefore( $( '.js-wpv-filter-remove-post-date' ) ).show(),
			error_container = $( self.post_row ).find( '.js-wpv-filter-multiple-toolset-messages' ),
			data = {
				action:	'wpv_filter_post_date_delete',
				id:	self.view_id,
				wpnonce:	nonce,
			};
		$.ajax( {
			type:	'POST',
			dataType:	'json',
			url:	ajaxurl,
			data:	data,
			success:	function( response ) {
				if ( response.success ) {
					$( self.post_row )
						.addClass( 'wpv-filter-deleted' )
						.animate( {
							height: 'toggle',
							opacity: 'toggle',
						}, 400, function() {
							$( this ).remove();
							self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
							$( document ).trigger( 'js_event_wpv_query_filter_deleted', [ 'date' ] );
						} );
				} else {
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container } );
				}
			},
			error:	function( ajaxContext ) {
				console.log( 'Error: ', ajaxContext.responseText );
			},
			complete:	function() {
				spinnerContainer.remove();
			},
		} );
	};

	// Manage show/hide the date conditions relationship box

	self.manage_date_relationship = function() {
		const items = $( '.js-wpv-filter-post-date-options .js-wpv-date-condition' ).length;
		if ( items > 1 ) {
			$( '.js-wpv-filter-post-date-relationship' ).show();
		} else if ( items == 0 ) {
			$( '.js-wpv-filter-row-post-date' ).remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		} else {
			$( '.js-wpv-filter-post-date-relationship' ).hide();
		}
	};

	// Resolve date condition values into a hidden field

	self.resolve_date_condition_values = function( condition_row ) {
		condition_row.find( '.js-wpv-date-condition-single .js-wpv-filter-date-condition-combo-value' ).each( function() {
			let thiz = $( this ),
				value_holder = thiz.find( 'input.js-wpv-filter-date-data' ),
				real_value_holder = thiz.find( '.js-wpv-filter-date-data-real' ),
				mode_holder = thiz.find( 'select.js-wpv-filter-date-origin' ),
				type = value_holder.data( 'combotype' ),
				value = value_holder.val();
			switch ( mode_holder.val() ) {
				case 'url':
					value = 'URL_PARAM(' + value + ')';
					break;
				case 'attribute':
					value = 'VIEW_PARAM(' + value + ')';
					break;
				case 'current_one':
					value = 'CURRENT_ONE()';
					break;
				case 'future_one':
					value = 'FUTURE_ONE(' + value + ')';
					break;
				case 'past_one':
					value = 'PAST_ONE(' + value + ')';
					break;
			}
			real_value_holder.val( value );
		} );
		let group_selected = condition_row.find( '.js-wpv-date-condition-group-selected' ).val(),
			group_resolved_value = '',
			group_real_value_holder = condition_row.find( '.js-wpv-date-condition-group .js-wpv-filter-date-data-real' );
		condition_row.find( '.js-wpv-date-condition-group .js-wpv-filter-date-condition-combo-value' ).each( function() {
			let thiz = $( this ),
				value = thiz.find( 'input.js-wpv-filter-date-data' ).val(),
				mode = thiz.find( 'select.js-wpv-filter-date-origin' ).val();
			switch ( mode ) {
				case 'url':
					value = 'URL_PARAM(' + value + ')';
					break;
				case 'attribute':
					value = 'VIEW_PARAM(' + value + ')';
					break;
				case 'current_one':
					value = 'CURRENT_ONE()';
					break;
				case 'future_one':
					value = 'FUTURE_ONE(' + value + ')';
					break;
				case 'past_one':
					value = 'PAST_ONE(' + value + ')';
					break;
			}
			if ( group_resolved_value != '' ) {
				group_resolved_value += ',';
			}
			group_resolved_value += value;
		} );
		group_real_value_holder.val( group_resolved_value );
	};

	// Manage validation flags for combo rows

	self.manage_combo_validation_flags = function( item ) {
		let thiz_combo = $( item ),
			thiz_combo_select = thiz_combo.find( 'select.js-wpv-filter-date-origin' ),
			thiz_combo_select_val = thiz_combo_select.val(),
			thiz_combo_input = thiz_combo.find( 'input.js-wpv-filter-date-data' ),
			thiz_combo_type = thiz_combo_input.data( 'combotype' );
		if ( thiz_combo_select_val == 'current_one' ) {
			thiz_combo_input.removeClass( 'js-wpv-filter-validate' );
		} else if ( thiz_combo_select_val == 'url' ) {
			thiz_combo_input
				.addClass( 'js-wpv-filter-validate' )
				.data( 'type', 'url' );
		} else if ( thiz_combo_select_val == 'attribute' ) {
			thiz_combo_input
				.addClass( 'js-wpv-filter-validate' )
				.data( 'type', 'shortcode' );
		} else if ( thiz_combo_select_val == 'constant' ) {
			if ( thiz_combo_type != 'group' ) {
				if ( thiz_combo_input.val() == '' ) {
					thiz_combo_input.removeClass( 'js-wpv-filter-validate' );
				} else {
					thiz_combo_input
						.addClass( 'js-wpv-filter-validate' )
						.data( 'type', thiz_combo_type );
				}
			} else {
				thiz_combo_type = thiz_combo.closest( '.js-wpv-date-condition' ).find( '.js-wpv-date-condition-group-selected' ).val();
				thiz_combo_input
					.addClass( 'js-wpv-filter-validate' )
					.data( 'type', thiz_combo_type );
			}
		} else {
			thiz_combo_input
				.addClass( 'js-wpv-filter-validate' )
				.data( 'type', 'numeric_natural' );
		}
	};

	//--------------------
	// Events
	//--------------------

	// Watch changes

	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select', function() {
		self.manage_filter_changes();
	} );

	// Add condition

	$( document ).on( 'click', '.js-wpv-date-filter-add-condition', function() {
		const thiz = $( this ),
			spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show(),
			query_mode = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
			data = {
				action:	'wpv_filter_post_date_add_condition',
				query_mode:	query_mode,
			//id: self.view_id,
			//wpnonce: nonce,
			};
		thiz.prop( 'disabled', true );
		if ( self.condition_template ) {
			$( self.condition_template ).insertBefore( thiz );
			thiz.prop( 'disabled', false );
			spinnerContainer.remove();
			self.manage_filter_changes();
			self.manage_date_relationship();
			return;
		}
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( typeof( response ) !== 'undefined' ) {
					self.condition_template = response;
					$( response ).insertBefore( thiz );
					self.manage_filter_changes();
					self.manage_date_relationship();
				} else {
					console.log( 'Error: AJAX returned ', response );
				}
			},
			error: function( ajaxContext ) {
				console.log( 'Error: ', ajaxContext.responseText );
			},
			complete: function() {
				thiz.prop( 'disabled', false );
				spinnerContainer.remove();
			},
		} );
	} );

	// Change condition operator

	$( document ).on( 'change', '.js-wpv-date-condition-operator', function() {
		const thiz = $( this ),
			thiz_condition = thiz.closest( '.js-wpv-date-condition' );
		self.date_condition_operator_show_settings( thiz_condition );
	} );

	// Change origin select

	$( document ).on( 'change', '.js-wpv-filter-date-condition-combo-value select.js-wpv-filter-date-origin', function() {
		const thiz = $( this ),
			thiz_val = thiz.val(),
			thiz_val_kind = thiz.find( ':selected' ).data( 'group' ),
			thiz_combo = thiz.closest( '.js-wpv-filter-date-condition-combo-value' ),
			thiz_input = thiz_combo.find( 'input.js-wpv-filter-date-data' );
		if ( thiz_val == 'current_one' ) {
			thiz_input.hide();
		} else {
			thiz_input.show();
		}
	} );

	// Add group value

	$( document ).on( 'click', '.js-wpv-date-condition-group-value-add', function() {
		const thiz = $( this ),
			thiz_condition_group = thiz.parents( '.js-wpv-date-condition-group' ),
			cond_group_values = thiz_condition_group.find( '.js-wpv-filter-date-condition-group-value' ),
			clone = $( cond_group_values[ 0 ] ).clone();
		clone.find( 'select.js-wpv-filter-date-origin' ).val( 'constant' );
		clone.find( 'input.js-wpv-filter-date-data' ).val( '' ).show();
		clone.find( '.js-wpv-date-condition-group-value-delete' ).prop( 'disable', false ).show();
		clone.insertBefore( thiz );
		self.manage_filter_changes();
	} );

	// Remove group value

	$( document ).on( 'click', '.js-wpv-date-condition-group-value-delete', function() {
		const thiz = $( this ),
			thiz_value_div = thiz.parents( '.js-wpv-filter-date-condition-group-value' );
		thiz_value_div.fadeOut( 400, function() {
			$( this ).remove();
			self.manage_filter_changes();
		} );
	} );

	// Save filter data

	self.save_filter_post_date = function( event, propagate ) {
		const thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_date', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( '.js-wpv-filter-row-post-date' );
		} else {
			let valid = true,
				filter_data = [];
			$( self.post_options_container_selector ).find( '.js-wpv-date-condition' ).each( function() {
				let thiz_condition			= $( this ),
					thiz_condition_operator		= thiz_condition.find( '.js-wpv-date-condition-operator' ).val(),
					thiz_condition_validating	= false,
					thiz_valid					= true,
					thiz_condition_kind			= 'single';
				if ( $.inArray( thiz_condition_operator, self.single_operators ) !== -1 ) {
					thiz_condition_validating = thiz_condition.find( '.js-wpv-date-condition-single' );
				} else if ( $.inArray( thiz_condition_operator, self.group_operators ) !== -1 ) {
					thiz_condition_validating = thiz_condition.find( '.js-wpv-date-condition-group' );
					thiz_condition_kind = 'group';
				}
				if ( thiz_condition_validating ) {
					thiz_condition_validating.find( '.js-wpv-filter-date-condition-combo-value' ).each( function() {
						self.manage_combo_validation_flags( this );
					} );
					thiz_valid = WPViews.query_filters.validate_filter_options( thiz_condition_validating );
				}
				if ( thiz_valid == false ) {
					valid = false;
				} else {
					self.resolve_date_condition_values( thiz_condition );
					const thiz_options = thiz_condition.find( 'select, input' ).not( '.js-wpv-element-not-serialize' ).serialize();
					filter_data.push( thiz_options );
				}
			} );
			if ( valid ) {
				self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
				const nonce = thiz.data( 'nonce' ),
					spinnerContainer = $( self.spinner ).insertBefore( thiz ),
					error_container = $( self.post_row ).find( '.js-wpv-filter-multiple-toolset-messages' ),
					data = {
						action:	'wpv_filter_post_date_update',
						id:	self.view_id,
						date_filter:	filter_data,
						date_relation:	$( '#js-wpv-filter-date-relation' ).val(),
						wpnonce:	nonce,
					};
				$.post( ajaxurl, data, function( response ) {
					if ( response.success ) {
						$( document ).trigger( 'js_event_wpv_query_filter_saved', [ 'post_date' ] );
						$( '.js-wpv-filter-post-date-summary' ).html( response.data.summary );
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
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_date' );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					} )
					.always( function() {
						spinnerContainer.remove();
					} );
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_date' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_date( 'js_event_wpv_save_filter_post_date_completed', false );
	} );

	// Delete all items

	$( document ).on( 'click', '.js-wpv-filter-remove-post-date', function() {
		self.remove_post_date_filter();
	} );

	// Delete a single date condition

	$( document ).on( 'click', '.js-wpv-date-condition-remove', function() {
		if ( $( self.post_row ).find( '.js-wpv-date-condition' ).length > 1 ) {
			const thiz_condition = $( this ).parents( 'div.js-wpv-date-condition' );
			thiz_condition
				.addClass( 'wpv-filter-multiple-element-removed' )
				.animate( {
					height: 'toggle',
					opacity: 'toggle',
				}, 400, function() {
					thiz_condition.remove();
					self.manage_filter_changes();
					self.manage_date_relationship();
				} );
		} else {
			self.remove_post_date_filter();
		}
	} );

	// On filter save

	$( document ).on( 'js_event_wpv_query_filter_saved', function( event, filter_type ) {
		if ( filter_type == 'post_date' ) {
			self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
			self.manage_filter_changes();
		}
		WPViews.query_filters.filters_exist();
	} );

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( filter_type == 'post_date' ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_date', action: 'remove' } );
		}
	} );

	//--------------------
	// Manage post date filter restrictions
	//--------------------

	/**
	* manage_post_date_filter_warning
	*
	* Add or remove the warning messages when:
	* 	- WPA - combining a query filter by post data and date-based archive loops
	*
	* @since 2.1
	*/

	self.manage_post_date_filter_warning = function() {
		let query_mode			= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
			post_date_filter_row	= $( self.post_row ),
			native_loops_selected	= [];

		if ( post_date_filter_row.length > 0 ) {
			if ( 'normal' == query_mode ) {

			} else {
				$( '.js-wpv-archive-filter-post-date-disabled' ).remove();
				native_loops_selected = $( '.js-wpv-settings-archive-loop input[data-type="native"]:checked' ).map( function() {
					return $( this ).data( 'name' );
				} ).get();
				if ( _.intersection( native_loops_selected, [ 'year', 'month', 'day' ] ).length > 0 ) {
					$( self.disabled_for_loop ).prependTo( self.post_row ).show();
				}
			}
		}
	};

	// Filter creation event

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_date' ) {
			self.manage_post_date_filter_warning();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_date', action: 'add' } );
		}
	} );

	$( document ).on( 'js_event_wpv_save_section_loop_selection_completed', function( event ) {
		self.manage_post_date_filter_warning();
	} );

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.DateFilterGUI}
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
			handle:	'save_filter_post_date',
			callback:	self.save_filter_post_date,
			event:	'js_event_wpv_save_filter_post_date_completed',
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
		self.manage_post_date_filter_warning();
		self.init_hooks();
	};

	self.init();
};

jQuery( function( $ ) {
	WPViews.date_filter_gui = new WPViews.DateFilterGUI( $ );
} );
