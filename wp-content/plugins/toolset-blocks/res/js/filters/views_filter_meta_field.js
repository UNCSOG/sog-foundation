/**
* Views Meta Field Filter GUI - script
*
* Adds basic interaction for the Meta Field Filter
*
* One instance for each Meta filter: custom (postmeta), termmeta and usermeta
*
* @package Views
*
* @since 1.12
*/

var WPViews = WPViews || {};

WPViews.MetaFieldFilterGUI = function( $, meta, id ) {
	const self = this;

	self.meta = meta;
	self.meta_field = self.meta + '-field';

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner = '<span class="wpv-spinner ajax-loader">';

	self.filter_row							= '.js-wpv-filter-row-' + self.meta + '-field';
	self.filter_options_container_selector	= '.js-wpv-filter-row-' + self.meta + '-field .js-wpv-filter-edit';
	self.filter_edit_open_selector			= '.js-wpv-filter-' + self.meta + '-field-edit-open';
	self.filter_close_save_selector			= '.js-wpv-filter-' + self.meta + '-field-edit-ok';

	self.filter_current_options = $( self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select' ).serialize();

	//--------------------
	// Functions
	//--------------------

	self.manage_filter_changes = function() {
		WPViews.query_filters.clear_validate_messages( self.filter_row );
		if ( self.filter_current_options != $( self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_' + self.meta + '_field', action: 'add' } );
			$( self.filter_close_save_selector )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary js-wpv-section-unsaved' )
				.html(
					WPViews.query_filters.icon_save + $( self.filter_close_save_selector ).data( 'save' )
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_' + self.meta + '_field', action: 'remove' } );
			$( self.filter_close_save_selector )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary js-wpv-section-unsaved' )
				.html(
					WPViews.query_filters.icon_edit + $( self.filter_close_save_selector ).data( 'close' )
				);
			$( self.filter_row ).find( '.unsaved' ).remove();
			$( document ).trigger( 'js_event_wpv_set_confirmation_unload_check' );
		}
	};

	self.meta_field_initialize_compare = function() {
		let wpv_allowed_values = 0;
		WPViews.query_filters.clear_validate_messages( self.filter_row );
		$( '.js-wpv-' + self.meta + '-field-compare-select' ).each( function() {
			const wpv_single_row = $( this ).parents( '.js-wpv-filter-multiple-element' ),
				thiz_inner = $( this ),
				thiz_inner_item = thiz_inner.parents( '.js-wpv-filter-multiple-element' );
			if ( thiz_inner.val() == 'BETWEEN' || thiz_inner.val() == 'NOT BETWEEN' ) {
				wpv_allowed_values = 2;
				thiz_inner_item
					.find( '.js-wpv-' + self.meta + '-field-add-value, .js-wpv-' + self.meta + '-field-remove-value' )
					.hide();
				divs = thiz_inner_item.find( '.js-wpv-' + self.meta + '-field-value-div' );
				if ( divs.length < 2 ) {
					// add another one.
					const clone = $( divs[ 0 ] ).clone();
					clone.find( '.js-wpv-' + self.meta + '-field-value-text' ).val( '' );
					clone.insertAfter( divs[ 0 ] );
					self.meta_field_initialize_compare_mode();
				}
			} else if ( thiz_inner.val() == 'IN' || thiz_inner.val() == 'NOT IN' ) {
				wpv_allowed_values = 100000;
				thiz_inner_item
					.find( '.js-wpv-' + self.meta + '-field-add-value' )
					.show();
				thiz_inner_item
					.find( '.js-wpv-' + self.meta + '-field-value-div' )
					.each( function( index ) {
						if ( index > 0 ) {
							$( this )
								.find( '.js-wpv-' + self.meta + '-field-remove-value' )
								.show();
						} else {
							$( this )
								.find( '.js-wpv-' + self.meta + '-field-remove-value' )
								.hide();
						}
					} );
			} else {
				wpv_allowed_values = 1;
				thiz_inner_item
					.find( '.js-wpv-' + self.meta + '-field-add-value, .js-wpv-' + self.meta + '-field-remove-value' )
					.hide();
			}
			thiz_inner_item
				.find( '.js-wpv-' + self.meta + '-field-value-div' )
				.each( function() {
					if ( wpv_allowed_values > 0 ) {
						$( this ).show();
					} else {
						$( this ).remove();
					}
					wpv_allowed_values--;
				} );
		} );
	};

	self.meta_field_initialize_compare_mode = function() {
		$( '.js-wpv-' + self.meta + '-field-compare-mode' ).each( function() {
			self.meta_field_adjust_value_controls( this );
		} );
	};

	self.meta_field_adjust_value_controls = function( item ) {
		// Show the text control depending on the compare function.
		const mode = $( item ).val(),
			value_div = $( item ).parents( '.js-wpv-' + self.meta + '-field-value-div' ),
			value_input = value_div.find( '.js-wpv-' + self.meta + '-field-value-text' );
		value_input
			.removeClass( 'js-wpv-filter-validate' )
			.data( 'type', 'none' );
		value_div
			.find( '.js-wpv-' + self.meta + '-field-value-combo-input, .js-wpv-' + self.meta + '-field-value-combo-date, .js-wpv-' + self.meta + '-field-value-combo-framework' )
			.hide();
		switch ( mode ) {
			case 'constant':
			case 'future_day':
			case 'past_day':
			case 'future_month':
			case 'past_month':
			case 'future_year':
			case 'past_year':
			case 'seconds_from_now':
			case 'months_from_now':
			case 'years_from_now':
				value_div
					.find( '.js-wpv-' + self.meta + '-field-value-combo-input' )
					.show();
				break;
			case 'url':
				value_div
					.find( '.js-wpv-' + self.meta + '-field-value-combo-input' )
					.show();
				value_input
					.addClass( 'js-wpv-filter-validate' )
					.data( 'type', 'url' );
				break;
			case 'attribute':
				value_div
					.find( '.js-wpv-' + self.meta + '-field-value-combo-input' )
					.show();
				value_input
					.addClass( 'js-wpv-filter-validate' )
					.data( 'type', 'shortcode' );
				break;
			case 'date':
				value_div
					.find( '.js-wpv-' + self.meta + '-field-value-combo-date' )
					.show();
				break;
			case 'framework':
				value_div
					.find( '.js-wpv-' + self.meta + '-field-value-combo-framework' )
					.show();
				break;
			default:

				break;
		}
	};

	self.meta_field_initialize_relationship = function() {
		if ( $( '.js-wpv-filter-' + self.meta + '-field' ).length > 1 ) {
			$( '.js-wpv-filter-' + self.meta + '-field-relationship-container' ).show();
		} else if ( $( '.js-wpv-filter-' + self.meta + '-field-multiple-element' ).length == 0 ) {
			$( '.js-filter-' + self.meta + '-field' ).remove();
			if ( $( '.js-wpv-section-unsaved' ).length < 1 ) {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', false );
			}
		} else {
			$( '.js-wpv-filter-' + self.meta + '-field-relationship-container' ).hide();
		}

		var readonlyItems = $( '.js-wpv-filter-' + self.meta + '-field-multiple-element.js-wpv-filter-multiple-readonly-element' ).length;
		if ( readonlyItems > 0 ) {
			$( '.js-wpv-filter-remove-' + self.meta + '-field' ).hide();
		} else {
			$( '.js-wpv-filter-remove-' + self.meta + '-field' ).show();
		}
	};

	self.resolve_meta_field_value = function() {
		$( '.js-wpv-' + self.meta + '-field-values' ).each( function( index ) {
			let text_box = $( this ).find( '.js-wpv-' + self.meta + '-field-values-real' ),
				resolved_value = '';
			$( this ).find( '.js-wpv-' + self.meta + '-field-value-div' ).each( function( index ) {
				if ( resolved_value != '' ) {
					resolved_value += ',';
				}
				let value = $( this ).find( '.js-wpv-' + self.meta + '-field-value-text' ).val(),
					framework_value = $( this ).find( '.js-wpv-' + self.meta + '-field-framework-value' ).val(),
					mode = $( this ).find( '.js-wpv-' + self.meta + '-field-compare-mode' ).val();
				switch ( mode ) {
					case 'url':
						value = 'URL_PARAM(' + value + ')';
						break;
					case 'attribute':
						value = 'VIEW_PARAM(' + value + ')';
						break;
					case 'framework':
						value = 'FRAME_KEY(' + framework_value + ')';
						break;
					case 'now':
						value = 'NOW()';
						break;
					case 'today':
						value = 'TODAY()';
						break;
					case 'future_day':
						value = 'FUTURE_DAY(' + value + ')';
						break;
					case 'past_day':
						value = 'PAST_DAY(' + value + ')';
						break;
					case 'this_month':
						value = 'THIS_MONTH()';
						break;
					case 'future_month':
						value = 'FUTURE_MONTH(' + value + ')';
						break;
					case 'past_month':
						value = 'PAST_MONTH(' + value + ')';
						break;
					case 'this_year':
						value = 'THIS_YEAR()';
						break;
					case 'future_year':
						value = 'FUTURE_YEAR(' + value + ')';
						break;
					case 'past_year':
						value = 'PAST_YEAR(' + value + ')';
						break;
					case 'seconds_from_now':
						value = 'SECONDS_FROM_NOW(' + value + ')';
						break;
					case 'months_from_now':
						value = 'MONTHS_FROM_NOW(' + value + ')';
						break;
					case 'years_from_now':
						value = 'YEARS_FROM_NOW(' + value + ')';
						break;
					case 'date':
						var month = $( this ).find( '.js-wpv-' + self.meta + '-field-date select' ),
							mm = month.val(),
							jj = month.next().val(),
							aa = month.next().next().val();
						value = 'DATE(' + jj + ',' + mm + ',' + aa + ')';
						break;
				}
				resolved_value += value;
			} );
			text_box.val( resolved_value );
		} );
	};
	// button button-secondary button-small js-wpv-filter-remove js-filter-remove js-wpv-filter-remove-custom-field
	self.remove_meta_field_filters = function() {
		$( self.filter_close_save_selector ).removeClass( 'js-wpv-section-unsaved' );
		const nonce = $( '.js-wpv-filter-remove-' + self.meta + '-field' ).data( 'nonce' ),
			meta_field = [],
			spinnerContainer = $( self.spinner ).insertBefore( $( '.js-wpv-filter-remove-' + self.meta + '-field' ) ).show(),
			error_container = $( self.filter_row ).find( '.js-wpv-filter-multiple-toolset-messages' );
		$( '.js-wpv-filter-' + self.meta + '-field-multiple-element .js-filter-remove' ).each( function() {
			meta_field.push( $( this ).data( 'field' ) );
		} );
		const data = {
			action:	'wpv_filter_' + self.meta + '_field_delete',
			id:	self.view_id,
			field:	meta_field,
			wpnonce:	nonce,
		};
		$.ajax( {
			type:	'POST',
			dataType:	'json',
			url:	ajaxurl,
			data:	data,
			success:	function( response ) {
				if ( response.success ) {
					$( self.filter_row )
						.addClass( 'wpv-filter-deleted' )
						.animate( {
							height: 'toggle',
							opacity: 'toggle',
						}, 400, function() {
							$( this ).remove();
							self.filter_current_options = $( self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select' ).serialize();
							$( document ).trigger( 'js_event_wpv_query_filter_deleted', [ self.meta + '-field' ] );
							Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
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

	//--------------------
	// Events
	//--------------------

	$( document ).on( 'change.wpvQueryFilterEvent', '.js-wpv-' + self.meta + '-field-compare-select', function() {
		self.meta_field_initialize_compare();
	} );

	$( document ).on( 'change.wpvQueryFilterEvent', '.js-wpv-' + self.meta + '-field-compare-mode', function() {
		self.meta_field_adjust_value_controls( this );
	} );

	$( document ).on( 'change.wpvQueryFilterEvent', '.js-wpv-' + self.meta + '-field-type-select', function() {
		const val = $( this ).val();

		if ( 'DECIMAL' == val ) {
			$( this ).parent().find( '.js-wpv-' + self.meta + '-field-decimals-span' ).show();
		} else {
			$( this ).parent().find( '.js-wpv-' + self.meta + '-field-decimals-span' ).hide();
		}
	} );

	// Add another value

	$( document ).on( 'click.wpvQueryFilterEvent', '.js-wpv-' + self.meta + '-field-add-value', function() {
		const thiz_parent_item = $( this ).parents( '.js-wpv-filter-multiple-element' ),
			clone = thiz_parent_item
				.find( '.js-wpv-' + self.meta + '-field-value-div' ).last()
				.clone();
		clone
			.find( '.js-wpv-' + self.meta + '-field-value-text' )
			.val( '' );
		clone
			.find( '.js-wpv-' + self.meta + '-field-compare-mode' )
			.val( 'constant' );
		clone
			.find( '.js-wpv-' + self.meta + '-field-remove-value' )
			.show();
		clone
			.insertAfter(
				thiz_parent_item
					.find( '.js-wpv-' + self.meta + '-field-value-div' ).last()
			);
		self.meta_field_initialize_compare();
		self.meta_field_initialize_compare_mode();
		self.manage_filter_changes();
	} );

	// Remove value

	$( document ).on( 'click.wpvQueryFilterEvent', '.js-wpv-' + self.meta + '-field-remove-value', function() {
		$( this )
			.parents( '.js-wpv-' + self.meta + '-field-value-div' )
			.remove();
		self.manage_filter_changes();
	} );

	// Watch changes

	$( document ).on( 'change.wpvQueryFilterEvent keyup.wpvQueryFilterEvent input.wpvQueryFilterEvent cut.wpvQueryFilterEvent paste.wpvQueryFilterEvent', self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select', function() {
		self.resolve_meta_field_value();
		self.manage_filter_changes();
	} );

	self.save_filter_meta = function( event, propagate ) {
		const thiz = $( self.filter_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.filter_row );
		console.log( 'save_filter_meta' );
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_' + self.meta + '_field', action: 'remove' } );

		if ( self.filter_current_options == $( self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( '.js-filter-' + self.meta + '-field' );
		} else {
			let valid = true;
			valid = WPViews.query_filters.validate_filter_options( self.filter_row );
			if ( valid ) {
				self.resolve_meta_field_value();
				const nonce = thiz.data( 'nonce' ),
					spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
					error_container = $( self.filter_row ).find( '.js-wpv-filter-multiple-toolset-messages' ),
					data = {
						action:	'wpv_filter_' + self.meta + '_field_update',
						id:	self.view_id,
						fields:	$( '.js-filter-' + self.meta + '-field input, .js-filter-' + self.meta + '-field select' ).not( '.js-wpv-element-not-serialize' ).serialize(),
						wpnonce:	nonce,
					};
				self.filter_current_options = $( self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select' ).serialize();
				$.ajax( {
					type:	'POST',
					url:	ajaxurl,
					dataType:	'json',
					data:	data,
					success:	function( response ) {
						if ( response.success ) {
							$( document ).trigger( 'js_event_wpv_query_filter_saved', [ self.meta + '-field' ] );
							$( '.js-wpv-filter-' + self.meta + '-field-summary' ).html( response.data.summary );
							WPViews.query_filters.close_and_glow_filter_row( self.filter_row, 'wpv-filter-saved' );
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
							Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container } );
							if ( propagate ) {
								$( document ).trigger( 'js_wpv_save_section_queue' );
							}
						}
					},
					error:	function( ajaxContext ) {
						console.log( 'Error: ', ajaxContext.responseText );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_' + self.meta + '_field' );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					},
					complete:	function() {
						spinnerContainer.remove();
					},
				} );
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_' + self.meta + '_field' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click.wpvQueryFilterEvent', self.filter_close_save_selector, function() {
		console.log( 'close_save_selector' );
		self.save_filter_meta( 'js_event_wpv_save_filter_' + self.meta + '_field_completed', false );
	} );

	$( document ).on( 'click.wpvQueryFilterEvent', '.js-wpv-filter-' + self.meta + '-field-multiple-element .js-filter-remove', function() {
		const thiz = $( this ),
			row = thiz.parents( '.js-wpv-filter-' + self.meta + '-field-multiple-element' ),
			li_item = thiz.parents( self.filter_row ),
			field = thiz.data( 'field' ),
			nonce = thiz.data( 'nonce' ),
			spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertBefore( thiz ).hide(),
			error_container = row.find( '.js-wpv-filter-toolset-messages' ),
			data = {
				action:	'wpv_filter_' + self.meta + '_field_delete',
				id:	self.view_id,
				field:	field,
				wpnonce:	nonce,
			};
		if ( li_item.find( '.js-wpv-filter-' + self.meta + '-field-multiple-element' ).length == 1 ) {
			self.remove_meta_field_filters();
		} else {
			spinnerContainer.show();
			$.ajax( {
				type:	'POST',
				url:	ajaxurl,
				dataType:	'json',
				data:	data,
				success:	function( response ) {
					if ( response.success ) {
						row
							.addClass( 'wpv-filter-multiple-element-removed' )
							.fadeOut( 500, function() {
								$( this ).remove();
								$( document ).trigger( 'js_event_wpv_query_filter_deleted', [ self.meta + '-field' ] );
							} );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
					} else {
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-ajax-fail', { data: response.data, container: error_container } );
					}
				},
				error:	function( ajaxContext ) {

				},
				complete:	function() {

				},
			} );
		}
	} );

	$( document ).on( 'click.wpvQueryFilterEvent', '.js-filter-' + self.meta + '-field .js-wpv-filter-remove-' + self.meta + '-field', function( e ) {
		console.log( 'click' );
		if ( $( self.filter_row ).find( '.js-wpv-filter-' + self.meta + '-field-multiple-element' ).length > 1 ) {
			console.log( 'found row' );
			const dialog_height = $( window ).height() - 100;
			self.filter_dialog.dialog( 'open' ).dialog( {
				maxHeight: dialog_height,
				draggable: false,
				resizable: false,
				position: { my: 'center top+50', at: 'center top', of: window },
			} );
		} else {
			self.remove_meta_field_filters();
		}
	} );

	// Created, saved and deleted

	$( document ).on( 'js_event_wpv_query_filter_created.wpvQueryFilterEvent', function( event, filter_type ) {
		if (
			filter_type == self.meta + '-field' ||
			filter_type.substr( 0, self.meta_field.length ) == self.meta_field ||
			filter_type == 'all'
		) {
			self.manage_filter_changes();
			self.meta_field_initialize_compare();
			self.meta_field_initialize_compare_mode();// Might not be needed here
			self.meta_field_initialize_relationship();
		}
		if ( filter_type == 'parametric-all' ) {
			self.filter_current_options = $( self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select' ).serialize();
			self.manage_filter_changes();
			self.meta_field_initialize_compare();
			self.meta_field_initialize_compare_mode();// Might not be needed here
			self.meta_field_initialize_relationship();
		}
		WPViews.query_filters.filters_exist();
	} );

	$( document ).on( 'js_event_wpv_query_filter_saved.wpvQueryFilterEvent', function( event, filter_type ) {
		if (
			filter_type == self.meta + '-field' ||
			filter_type.substr( 0, self.meta_field.length ) == self.meta_field ||
			filter_type == 'all'
		) {
			self.filter_current_options = $( self.filter_options_container_selector + ' input, ' + self.filter_options_container_selector + ' select' ).serialize();
			self.manage_filter_changes();
			self.meta_field_initialize_compare();
			self.meta_field_initialize_compare_mode();// Might not be needed here
			self.meta_field_initialize_relationship();
		}
		WPViews.query_filters.filters_exist();
	} );

	$( document ).on( 'js_event_wpv_query_filter_deleted.wpvQueryFilterEvent', function( event, filter_type ) {
		if (
			filter_type == self.meta + '-field' ||
			filter_type.substr( 0, self.meta_field.length ) == self.meta_field ||
			filter_type == 'all'
		) {
			self.manage_filter_changes();
			self.meta_field_initialize_compare();
			self.meta_field_initialize_compare_mode();// Might not be needed here
			self.meta_field_initialize_relationship();
		}
		WPViews.query_filters.filters_exist();
	} );

	/**
	* init_dialogs
	*
	* Initialize the dialogs
	*
	* @since 1.10
	*/

	self.init_dialogs = function() {
		self.filter_dialog = $( '#js-wpv-filter-' + self.meta + '-field-delete-filter-row-dialog' ).dialog( {
			autoOpen: false,
			modal: true,
			title: wpv_meta_field_filter_texts[ self.meta ].dialog_title,
			minWidth: 600,
			show: {
				effect: 'blind',
				duration: 800,
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons: [
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-filters-meta-fields-delete-filter-row',
					text: wpv_meta_field_filter_texts[ self.meta ].delete_filters,
					click: function() {
						self.remove_meta_field_filters();
						$( this ).dialog( 'close' );
					},
				},
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-secondary',
					text: wpv_meta_field_filter_texts[ self.meta ].edit_filters,
					click: function() {
						$( this ).dialog( 'close' );
						WPViews.query_filters.open_filter_row( $( self.filter_row ) );
					},
				},
				{
					class: 'button-secondary',
					text: wpv_meta_field_filter_texts[ self.meta ].cancel,
					click: function() {
						$( this ).dialog( 'close' );
					},
				},
			],
		} );
	};

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.MetaFieldFilterGUI}
	 */
	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', self.filter_close_save_selector );
		return self;
	}

	//--------------------
	// Init hooks
	//--------------------

	self.init_hooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:	'save_filter_' + self.meta + '_field',
			callback:	self.save_filter_meta,
			event:	'js_event_wpv_save_filter_' + self.meta + '_field_completed',
		} );

		/**
		 * Clears events and hooks.
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-filter-clear-events-and-hooks', self.clear_events_and_hooks );
	};

	self.init = function() {
		self.meta_field_initialize_compare();
		self.meta_field_initialize_relationship();
		self.init_dialogs();
		self.init_hooks();
	};

	self.init();
};

jQuery( function( $ ) {
	WPViews.custom_field_filter_gui = new WPViews.MetaFieldFilterGUI( $, 'custom' );
	WPViews.usermeta_field_filter_gui = new WPViews.MetaFieldFilterGUI( $, 'usermeta' );
	WPViews.termmeta_field_filter_gui = new WPViews.MetaFieldFilterGUI( $, 'termmeta' );
} );
