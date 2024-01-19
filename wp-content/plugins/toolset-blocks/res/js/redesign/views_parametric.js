/**
 * Views Custom Search Backend Script
 *
 * @package Views
 *
 * @since 2.4.0
 *
 * @todo Generate a set of visual feedback items when AJAX calls are run to create the associated query filters,
 *       as some of the requested AJAX are async
 */

var WPViews		= WPViews || {};

if ( typeof WPViews.ShortcodesParser_instance === 'undefined' ) {
	WPViews.ShortcodesParser_instance = {};
}

if ( typeof WPViews.ParametricButtons === 'undefined' ) {
	WPViews.ParametricButtons = {};
}

WPViews.ParametricSearchFilters = function( $, id ) {
	const self										= this;

	self.view_id									= id || $( '.js-post_ID' ).val();

	self.cache										= {
		meta_fields: {
			postmeta: false,
			termmeta: false,
			usermeta: false,
		},
	};

	self.parametric_search_select_dialog			= null;
	self.dialog_minWidth							= 870;

	self.calculate_dialog_maxWidth					= function() {
		return ( $( window ).width() - 200 );
	};

	self.calculate_dialog_maxHeight					= function() {
		return ( $( window ).height() - 100 );
	};

	self.overlayContainer = $( '<div class=\'wpv-setting-overlay js-wpv-filter-editor-overlay\' style=\'top:0;\'><div class=\'wpv-transparency\'></div><i class=\'icon-lock fa fa-lock\'></i></div>' );

	self.parametric_search_select_dialog_loading	= $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="wpv-spinner ajax-loader"></div>' +
		'<p>' + wpv_parametric_i18n.dialogs.loading + '</p>' +
		'</div>' +
		'</div>'
	);

	self.set_view_id = function ( view_id ) {
		self.view_id = view_id || $( '.js-post_ID' ).val();
	};

	self.init_dialogs = function() {
		$( 'body' ).append( '<div id="js-wpv-parametric-search-select-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container"></div>' );
		self.parametric_search_select_dialog = $( '#js-wpv-parametric-search-select-dialog' ).dialog( {
			autoOpen:	false,
			modal:	true,
			title:	wpv_parametric_i18n.dialogs.dialog_select.title,
			minWidth:	self.calculate_dialog_maxWidth(),
			maxHeight:	self.calculate_dialog_maxHeight(),
			draggable:	false,
			resizable:	false,
			position:	{
				my:	'center top+50',
				at:	'center top',
				of:	window,
				collision:	'none',
			},
			show:	{
				effect:	'blind',
				duration:	800,
			},
			open:	function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-set-gui-action', 'insert' );
			},
			close:	function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
		} );
		return self;
	};

	// ---------------------------------
	// Parametric search buttons
	// ---------------------------------

	$( document ).on( 'click', '.js-wpv-parametric-search-filter-create', function() {
		const thiz		= $( this ),
			editor			= thiz.data( 'editor' );

		window.wpcfActiveEditor = editor;

		data = {
			action:	'wpv_parametric_search_filter_create_dialog',
			id:	Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-id', '' ),
			wpnonce:	'whatever',
		};

		self.parametric_search_select_dialog
			.dialog( 'open' )
			.dialog( {
				width:	self.calculate_dialog_maxWidth(),
				maxWidth:	self.calculate_dialog_maxWidth(),
			} );
		self.parametric_search_select_dialog.html( self.parametric_search_select_dialog_loading );
		$.ajax( {
			url:	ajaxurl,
			data:	data,
			type:	'GET',
			dataType:	'json',
			success:	function( response ) {
				if ( response.success ) {
					$( 'body' ).addClass( 'modal-open' );
					self.parametric_search_select_dialog.html( response.data.dialog );
				}
			},
		} );
	} );

	$( document ).on( 'click', '.js-wpv-parametric-search-filter-item-dialog', function() {
		self.parametric_search_select_dialog.dialog( 'close' );
	} );

	$( document ).on( 'js_event_wpv_shortcode_gui_dialog_triggered', function( event, $data ) {
		if ( ! _.has( $data, 'shortcode' ) ) {
			return;
		}
		var shortcode_name = $data.shortcode;

		if ( _.indexOf( wpv_parametric_i18n.form_filters_shortcodes, shortcode_name ) != -1 ) {
			self.parametric_search_select_dialog.dialog( 'close' );
		}
	});

	// Maybe put this on a quicktag button?
	$( document ).on( 'click', '.js-wpv-parametric-search-filter-edit', function() {
		const thiz		= $( this ),
			editor			= thiz.data( 'editor' );

		Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-maybe-edit-shortcode', editor );
	} );

	/** @var bool */
	self.refreshQueryFiltersOnFilterEditorSave = false;

	$( document ).on( 'js_event_wpv_shortcode_action_completed', function( event, shortcode_data ) {
		const shortcode_name = shortcode_data.name,
			shortcode_atts = shortcode_data.attributes,
			shortcode_raw_atts = shortcode_data.raw_attributes,
			shortcode_content = shortcode_data.content,
			shortcode_string = shortcode_data.shortcode;
		if ( _.indexOf( wpv_parametric_i18n.form_filters_shortcodes_with_define_callback, shortcode_name ) != -1 ) {
			// Those search filters require a companion query filter to be created.
			// We need to generate or update the query filter associated to this
			const data = {
				action:	'wpv_custom_search_define_query_filter',
				id:	Toolset.hooks.applyFilters( 'wpv-current-view-id', self.view_id ),
				shortcode:	shortcode_name,
				attributes:	shortcode_atts,
				attributes_raw: shortcode_raw_atts,
				//wpnonce:		wpv_editor_strings.screen_options.nonce
			};
			$.ajax( {
				type:	'POST',
				dataType: 'json',
				async:	false, // Block any other interaction until this is completed
				url:	ajaxurl,
				data:	data,
				success:	function( response ) {
					if ( response.success ) {
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-update-query-filter-list' );
						self.refreshQueryFiltersOnFilterEditorSave = true;
					}
				},
				error:	function( ajaxContext ) {

				},
				complete:	function() {

				},
			} );
		} else if ( _.indexOf( wpv_parametric_i18n.form_filters_shortcodes, shortcode_name ) != -1 ) {
			// A search filter shortcode was inserted.
			// Refresh the query filters section on filter editor update!
			self.refreshQueryFiltersOnFilterEditorSave = true;
		}
	} );

	$( document ).on( 'js_event_wpv_save_section_filter_completed', function() {
		if ( false === self.refreshQueryFiltersOnFilterEditorSave ) {
			return;
		}
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-update-query-filter-list' );
		self.refreshQueryFiltersOnFilterEditorSave = false;
	});

	/**
	 * Make sure that filters and form elements are inserted inside the [wpv-filter-controls] shortcode.
	 *
	 * @param object shortcode_data
	 *     shortcode      string The shortcode just processed.
	 *     name           string The name of the processed shortcode.
	 *     attributes     object A key => value set of attribute pairs.
	 *     raw_attributes object A key => value set of attribute pairs, as collected from the dialog.
	 *     content        string The shortcode content when it is not self-closing.
	 *
	 * @note When there are no wrapping shortcodes, insert at cursor.
	 *
	 * @since 2.4.0
	 */
	self.check_cursor_position = function( shortcode_data, shortcode_action ) {
		if ( 'insert' != shortcode_action ) {
			return;
		}

		if (
			'wpv_filter_meta_html_content' === window.wpcfActiveEditor &&
			_.has( WPV_Toolset.CodeMirror_instance, 'wpv_filter_meta_html_content' ) &&
			(
				_.indexOf( wpv_parametric_i18n.form_filters_shortcodes, shortcode_data.name ) != -1 ||
				_.indexOf( [ 'wpv-filter-search-box', 'wpv-filter-submit', 'wpv-filter-reset', 'wpv-filter-spinner' ], shortcode_data.name ) != -1
			)
		) {
			const current_cursor = WPV_Toolset.CodeMirror_instance[ 'wpv_filter_meta_html_content' ].getCursor( true ),
				text_before = WPV_Toolset.CodeMirror_instance[ 'wpv_filter_meta_html_content' ].getRange( { line: 0, ch: 0 }, current_cursor ),
				text_after = WPV_Toolset.CodeMirror_instance[ 'wpv_filter_meta_html_content' ].getRange( current_cursor, { line: WPV_Toolset.CodeMirror_instance[ 'wpv_filter_meta_html_content' ].lastLine(), ch: null } );

			if (
				text_before.search( /\[wpv-filter-controls\]/g ) == -1 ||
				text_after.search( /\[\/wpv-filter-controls\]/g ) == -1
			) {
				const insert_position = WPV_Toolset.CodeMirror_instance[ 'wpv_filter_meta_html_content' ].getSearchCursor( '[/wpv-filter-controls]', false );
				if ( insert_position.findNext() ) {
					WPV_Toolset.CodeMirror_instance[ 'wpv_filter_meta_html_content' ].setSelection( insert_position.from(), insert_position.from() );
				}
			}
		}
	};

	self.add_control_structure_on_insert = function( shortcode_data ) {
		let shortcode_name			= shortcode_data.name,
			shortcode_atts			= shortcode_data.attributes,
			shortcode_raw_atts		= shortcode_data.raw_attributes,
			shortcode_content		= shortcode_data.content,
			shortcode_string		= shortcode_data.shortcode,
			shortcode_gui_action	= Toolset.hooks.applyFilters( 'wpv-filter-wpv-shortcodes-gui-get-gui-action', 'insert' );

		var control_type = '',
			output_type = '';

		shortcode_string = Toolset.hooks.applyFilters(
			'wpv-filter-wpv-shortcodes-gui-add-control-structure-on-insert-for-' + shortcode_name,
			shortcode_string,
			shortcode_data,
			shortcode_gui_action
		);

		if ( _.contains( [ 'wpv-control-post-taxonomy', 'wpv-control-postmeta' ], shortcode_name ) ) {
			if ( 'insert' === shortcode_gui_action ) {

				if ( _.has( shortcode_raw_atts, 'type' ) ) {
					control_type = shortcode_raw_atts.type;
					if ( control_type.substr( 0, 8 ) == 'toolset-' ) {
						control_type = control_type.substr( 8 );
					}
				}
				if ( _.has( shortcode_raw_atts, 'output' ) ) {
					output_type = shortcode_raw_atts.output;
				}

				if (
					_.has( shortcode_raw_atts, 'value_compare' ) &&
					(
						'BETWEEN' === shortcode_raw_atts.value_compare||
						'NOT BETWEEN' === shortcode_raw_atts.value_compare
					)
				) {
					// Compose the lower shortcode, by using the url_param_min attribute in place of thr url_param one
					// Then somehow we need to compose the other one, maybe iterating over shortcode_atts
					// but replacing url_param with url_param_max
					// and returning everything in shortcode_string
					let shortcode_atts_min = shortcode_atts,
						shortcode_atts_min_string = '',
						shortcode_string_for_between_min = '',
						shortcode_atts_max = shortcode_atts,
						shortcode_atts_max_string = '',
						shortcode_string_for_between_max = '',
						skipped_atts = [ 'boundary_label_min', 'boundary_label_max' ];

					shortcode_atts_min.url_param = shortcode_raw_atts.url_param_min;
					_.each( shortcode_atts_min, function( value, key ) {
						if ( value && ! skipped_atts.includes( key ) ) {
							shortcode_atts_min_string += ' ' + key + '="' + value + '"';
						}
					} );
					shortcode_string_for_between_min += '[' + shortcode_name + shortcode_atts_min_string + ']';

					shortcode_atts_max.url_param = shortcode_raw_atts.url_param_max;

					_.each( shortcode_atts_max, function( value, key ) {
						if ( value && ! skipped_atts.includes( key ) ) {
							shortcode_atts_max_string += ' ' + key + '="' + value + '"';
						}
					} );
					shortcode_string_for_between_max += '[' + shortcode_name + shortcode_atts_max_string + ']';

					var shortcode_label_min = _.has( shortcode_raw_atts, 'boundary_label_min' ) ? ( '[wpml-string context="wpv-views"]' + shortcode_raw_atts.boundary_label_min + '[/wpml-string]' ) : '';
					var shortcode_label_max = _.has( shortcode_raw_atts, 'boundary_label_min' ) ? ( '[wpml-string context="wpv-views"]' + shortcode_raw_atts.boundary_label_max + '[/wpml-string]' ) : '';

					if ( 'bootstrap' === output_type ) {
						shortcode_string_for_between_min = '<div class="form-group">' + '\n\t' + '<label' + self.maybeGenerateForAttribute(shortcode_raw_atts, 'url_param_min') + '>' + shortcode_label_min + '</label>' + '\n\t' + shortcode_string_for_between_min + '\n' + '</div>';
						shortcode_string_for_between_max = '<div class="form-group">' + '\n\t' + '<label' + self.maybeGenerateForAttribute(shortcode_raw_atts, 'url_param_max') + '>' + shortcode_label_max + '</label>' + '\n\t' + shortcode_string_for_between_max + '\n' + '</div>';
					} else {
						shortcode_string_for_between_min = shortcode_label_min + '\n' + shortcode_string_for_between_min;
						shortcode_string_for_between_max = shortcode_label_max + '\n' + shortcode_string_for_between_max;
					}

					shortcode_string = shortcode_string_for_between_min + '\n' + shortcode_string_for_between_max;
				} else {
					if ( 'checkbox' === control_type ) {
						if ( 'bootstrap' === output_type ) {
							shortcode_string = '<div class="form-group">' + '\n\t' + shortcode_string + '\n' + '</div>';
						}
					} else {
						var shortcode_label = _.has( shortcode_raw_atts, 'shortcode_label' ) ? ( '[wpml-string context="wpv-views"]' + shortcode_raw_atts.shortcode_label + '[/wpml-string]' ) : '';
						if ( 'bootstrap' === output_type ) {
							shortcode_string = '<div class="form-group">' + '\n\t' + '<label' + self.maybeGenerateForAttribute(shortcode_raw_atts) + '>' + shortcode_label + '</label>' + '\n\t' + shortcode_string + '\n' + '</div>';
						} else {
							shortcode_string = shortcode_label + '\n' + shortcode_string;
						}
					}
				}
			} else if ( 'edit' === shortcode_gui_action ) {
				if (
					_.has( shortcode_raw_atts, 'value_compare' ) &&
					(
						'BETWEEN LOW' === shortcode_raw_atts.value_compare||
						'NOT BETWEEN LOW' === shortcode_raw_atts.value_compare ||
						'BETWEEN HIGH' === shortcode_raw_atts.value_compare ||
						'NOT BETWEEN HIGH' === shortcode_raw_atts.value_compare
					)
				) {
// ???
				}
			}
		}

		if (
			'wpv-filter-search-box' === shortcode_name&&
			'insert' === shortcode_gui_action
		) {
			output_type = '';
			shortcode_label = '';

			if ( _.has( shortcode_raw_atts, 'output' ) ) {
				output_type = shortcode_raw_atts.output;
			}
			if (
				_.has( shortcode_raw_atts, 'value_label' ) &&
				shortcode_raw_atts.value_label !== ''
			) {
				shortcode_label = '<label for="wpv-post-search">[wpml-string context="wpv-views"]' + shortcode_raw_atts.value_label + '[/wpml-string]</label>';
			}
			if ( 'bootstrap' === output_type ) {
				shortcode_label = '' !== shortcode_label ? shortcode_label + '\n\t' : shortcode_label;
				shortcode_string = '<div class="form-group">' + '\n\t' + shortcode_label + shortcode_string + '\n' + '</div>';
			} else {
				shortcode_label = '' !== shortcode_label ? shortcode_label + '\n' : shortcode_label;
				shortcode_string = shortcode_label + shortcode_string;
			}
		}

		shortcode_data.shortcode = shortcode_string;

		return shortcode_data;
	};

	self.edit_stored_custom_search_shortcodes_attributes = function( shortcode_data ) {
		if ( shortcode_data.tag === 'wpv-control-post-taxonomy' ) {
			if ( $( '.js-wpv-filter-row-taxonomy-' + shortcode_data.attrs.named.taxonomy ).length > 0 ) {
				const taxonomy_filter_row = $( '.js-wpv-filter-row-taxonomy-' + shortcode_data.attrs.named.taxonomy );
				shortcode_data.attrs.named.value_compare = taxonomy_filter_row.find( '#taxonomy-' + shortcode_data.attrs.named.taxonomy + '-attribute-operator' ).val();
			}
		} else if ( shortcode_data.tag == 'wpv-control-postmeta' ) {
			if ( $( '.js-filter-row-custom-field-' + shortcode_data.attrs.named.field ).length > 0 ) {
				const postmeta_filter_row = $( '.js-filter-row-custom-field-' + shortcode_data.attrs.named.field );
				shortcode_data.attrs.named.value_compare = postmeta_filter_row.find( '.js-wpv-custom-field-compare-select' ).val();
				shortcode_data.attrs.named.value_type = postmeta_filter_row.find( '.js-wpv-custom-field-type-select' ).val();
				shortcode_data.attrs.named.value_real = postmeta_filter_row.find( '.js-wpv-custom-field-values-real' ).val();
			}
		}
		return shortcode_data;
	};

	self.load_group_native_postmeta = function( native_postmeta_group_list ) {
		let thiz = $( '.js-wpv-parametric-search-filter-load-group-native-postmeta' ),
			thiz_group_list = thiz.closest( '.js-wpv-parametric-search-filter-group-native-postmeta' ),
			post_fields_section = '';
		_.each( native_postmeta_group_list, function( element, index, list ) {
			post_fields_section += '<button class="item button button-secondary button-small js-wpv-parametric-search-filter-item-dialog"';
			post_fields_section += ' onclick="WPViews.shortcodes_gui.wpv_insert_shortcode_dialog_open({ shortcode: \'wpv-control-postmeta\', title: \'' + element + '\', params: { attributes: { field: \'' + element + '\' } } }); return false;"';
			if ( $( '.js-filter-row-custom-field-' + element, '.js-wpv-filter-row-custom-field' ).length > 0 ) {
				post_fields_section += ' disabled="disabled"';
			}
			post_fields_section += ' style="margin:5px 5px 0 0;font-size:11px;">';
			post_fields_section += element;
			post_fields_section += '</button>';
		} );
		thiz_group_list
			.fadeOut( 'fast', function() {
				thiz_group_list
					.html( post_fields_section )
					.fadeIn( 'fast' );
			} );
	};

	$( document ).on( 'click', '.js-wpv-parametric-search-filter-load-group-native-postmeta', function() {
		let thiz = $( this ),
			spinnerContainer = $( '<span class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show();
		thiz.prop( 'disabled', true );
		if ( self.cache.meta_fields.postmeta ) {
			self.load_group_native_postmeta( self.cache.meta_fields.postmeta );
		} else {
			const native_postmeta_group = Toolset.hooks.applyFilters( 'wpv-filter-wpv-shortcodes-gui-get-post-fields-list', [] );
			if ( native_postmeta_group ) {
				self.cache.meta_fields.postmeta = native_postmeta_group;
				self.load_group_native_postmeta( self.cache.meta_fields.postmeta );
			} else {
				const url = wpv_parametric_i18n.ajaxurl + '&action=wpv_shortcodes_gui_load_post_fields_on_demand';
				$.ajax( {
					url: url,
					success: function( response ) {
						self.cache.meta_fields.postmeta = response.data.fields;
						Toolset.hooks.doAction( 'wpv-filter-wpv-shortcodes-gui-set-post-fields-list', self.cache.meta_fields.postmeta );
						self.load_group_native_postmeta( self.cache.meta_fields.postmeta );
					},
				} );
			}
		}
	} );

	self.manage_control_post_taxonomy_gui_per_type = function( type_value ) {
		switch ( type_value ) {
			case 'select':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				break;
			case 'radios':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideDown( 'fast' );
				break;
			case 'multi-select':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				$( 'js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				break;
			case 'checkboxes':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideDown( 'fast' );
				break;
		}
		return self;
	};

	$( document ).on( 'change', '#wpv-control-post-taxonomy-type', function() {
		self.manage_control_post_taxonomy_gui_per_type( $( this ).val() );
	} );

	self.adjust_control_post_taxonomy_dialog = function( data ) {
		self.manage_control_post_taxonomy_gui_per_type( $( '#wpv-control-post-taxonomy-type' ).val() );
	};

	self.filter_control_post_taxonomy_computed_attributes = function( attribute_pairs ) {
		if ( _.has( attribute_pairs, 'type' ) ) {
			switch ( attribute_pairs.type ) {
				case 'select':
					attribute_pairs.label_class = false;
					attribute_pairs.label_style = false;
					break;
				case 'multi-select':
					attribute_pairs.default_label = false;
					attribute_pairs.label_class = false;
					attribute_pairs.label_style = false;
					break;
				case 'radios':

					break;
				case 'checkboxes':
					attribute_pairs.default_label = false;
					break;
			}
		}
		attribute_pairs.value_compare = false;
		attribute_pairs.shortcode_label = false;
		return attribute_pairs;
	};

	self.adjust_control_postmeta_gui_per_source = function() {
		const type_value = $( '#wpv-control-postmeta-type' ).val(),
			source_value = $( '#wpv-control-postmeta-source' ).val();
		switch ( source_value ) {
			case 'custom':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-value_combo' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				break;
			case 'database':
			default:
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-value_combo' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideDown( 'fast' );
				if (
					'select' == type_value ||
					'radios' == type_value
				) {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideDown( 'fast' );
				} else {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				}
				break;
		}
		return self;
	};

	$( document ).on( 'change', '#wpv-control-postmeta-source', function() {
		self.adjust_control_postmeta_gui_per_source();
	} );

	self.adjust_control_postmeta_gui_per_type = function() {
		let type_value = $( '#wpv-control-postmeta-type' ).val(),
			source_value = $( '#wpv-control-postmeta-source' ).val();
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-value_combo' ).slideUp( 'fast' );
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-title' ).slideUp( 'fast' );
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-date_format' ).slideUp( 'fast' );
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_date' ).slideUp( 'fast' );
		$( '.js-wpv-shortcode-gui-attribute-wrapper-for-placeholder' ).slideUp( 'fast' );

		if ( type_value.substr( 0, 8 ) == 'toolset-' ) {
			type_value = type_value.substr( 8 );
		}
		switch ( type_value ) {
			case 'select':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-source' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-format' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				if ( 'database' == source_value ) {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideDown( 'fast' );
				} else {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				}
				self.adjust_control_postmeta_gui_per_source();
				break;
			case 'radios':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-source' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-format' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideDown( 'fast' );
				if ( 'database' == source_value ) {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideDown( 'fast' );
				} else {
					$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				}
				self.adjust_control_postmeta_gui_per_source();
				break;
			case 'multi-select':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-source' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-format' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				self.adjust_control_postmeta_gui_per_source();
				break;
			case 'checkboxes':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-source' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-format' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				self.adjust_control_postmeta_gui_per_source();
				break;
			case 'checkbox':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-title' ).slideDown( 'fast' );

				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-source' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-format' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideDown( 'fast' );
				break;
			case 'date':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-date_format' ).slideDown( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_date' ).slideDown( 'fast' );

				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-source' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-format' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				break;
			case 'textfield':
			default:
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-source' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-format' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-order' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-group-for-label_frontend_combo' ).slideUp( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-placeholder' ).slideDown( 'fast' );
				break;
		}
		return self;
	};

	$( document ).on( 'change', '#wpv-control-postmeta-type', function() {
		self.adjust_control_postmeta_gui_per_type();
	} );

	self.adjust_control_postmeta_gui_per_value_compare = function() {
		const compare_value = $( '#wpv-control-postmeta-value_compare' ).val();
		if (
			'BETWEEN' == compare_value ||
			'NOT BETWEEN' == compare_value
		) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-url_param' ).slideUp( 'fast' );
			$( '.js-wpv-shortcode-gui-attribute-group-for-url_param_combo' ).slideDown( 'fast' );
			$( '.js-wpv-shortcode-gui-attribute-group-for-boundary_label_combo' ).slideDown( 'fast' );
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-url_param' ).slideDown( 'fast' );
			$( '.js-wpv-shortcode-gui-attribute-group-for-url_param_combo' ).slideUp( 'fast' );
			$( '.js-wpv-shortcode-gui-attribute-group-for-boundary_label_combo' ).slideUp( 'fast' );
		}
		return self;
	};

	$( document ).on( 'change', '#wpv-control-postmeta-value_compare', function() {
		self.adjust_control_postmeta_gui_per_value_compare();
	} );

	self.update_control_postmeta_custom_source_management = function() {
		const options = $( '.js-wpv-frontend-filter-postmeta-custom-options-list tbody tr.js-wpv-frontend-filter-postmeta-custom-options-list-item' );
		if ( options.length > 0 ) {
			// Update handles for actions
			const option_first = options.first(),
				option_else = options.slice(1);
			option_first
				.find( '.js-wpv-frontend-filter-postmeta-custom-options-list-item-delete' )
				.hide();
			option_else
				.find( '.js-wpv-frontend-filter-postmeta-custom-options-list-item-delete' )
				.show();
		}
		return self;
	};

	self.add_control_postmeta_custom_source_option = function() {

		var table_body = $( '.js-wpv-frontend-filter-postmeta-custom-options-list tbody' ),
			new_option = '';

		new_option += '<tr class="wpv-editable-list-item js-wpv-frontend-filter-postmeta-custom-options-list-item">';
		new_option += '<td><i class="icon-move fa fa-arrows wpv-editable-list-item-move js-wpv-frontend-filter-postmeta-custom-options-list-item-move ui-sortable-handle"></i></td>';
		new_option += '<td><input class="js-wpv-frontend-filter-postmeta-custom-options-list-item-value" type="text" /></td>';
		new_option += '<td><input class="js-wpv-frontend-filter-postmeta-custom-options-list-item-display-value" type="text" /></td>';
		new_option += '<td>';
		new_option += '<button class="button button-secondary button-small wpv-editable-list-item-delete js-wpv-frontend-filter-postmeta-custom-options-list-item-delete">';
		new_option += '<i class="icon-remove fa fa-times"></i>';
		new_option += '</button>';
		new_option += '</td>';
		new_option += '</tr>';

		table_body.append( new_option );

		table_body.sortable( 'refresh' );

		self.update_control_postmeta_custom_source_management();
	};

	$( document ).on( 'click', '.js-wpv-frontend-filter-postmeta-custom-options-list-add', function( e ) {
		e.preventDefault();
		self.add_control_postmeta_custom_source_option();
	} );

	$( document ).on( 'click', '.js-wpv-frontend-filter-postmeta-custom-options-list-item-delete', function( e ) {
		e.preventDefault();
		const delete_button = $( this ),
			delete_item = delete_button.closest( '.js-wpv-frontend-filter-postmeta-custom-options-list-item' );

		delete_item.addClass( 'wpv-editable-list-item-deleted' );
		setTimeout( function() {
			delete_item.fadeOut( 'fast', function() {
				$( this ).remove();
				$( '.js-wpv-frontend-filter-postmeta-custom-options-list tbody' ).sortable( 'refresh' );
				self.update_control_postmeta_custom_source_management();
			} );
		}, 500 );
	} );

	self.adjust_control_postmeta_dialog = function( data ) {
		$( '.js-wpv-frontend-filter-postmeta-custom-options-list tbody' ).sortable( {
			handle: '.js-wpv-frontend-filter-postmeta-custom-options-list-item-move',
			axis: 'y',
			containment: '.js-wpv-frontend-filter-postmeta-custom-options-list tbody',
			items: '> tr',
			helper: function( e, ui ) {
				// Fix the collapse of the dragged row width
				// https://paulund.co.uk/fixed-width-sortable-tables
				ui.children().each( function() {
					$( this ).width( $( this ).width() );
				} );
				return ui;
			},
			tolerance: 'pointer',
			update: function( event, ui ) {
				self.update_control_postmeta_custom_source_management();
			},
		} );

		self.update_control_postmeta_custom_source_management();
		self.adjust_control_postmeta_gui_per_type();
		self.adjust_control_postmeta_gui_per_value_compare();

		if ( ! _.has( data, 'overrides' ) ) {
			data.overrides = {};
		}
		if ( ! _.has( data.overrides, 'attributes' ) ) {
			data.overrides.attributes = {};
		}

		if (
			_.has( data.overrides.attributes, 'values' ) &&
			_.has( data.overrides.attributes, 'display_values' )
		) {
			let values = data.overrides.attributes.values.split( ',' ),
				display_values = data.overrides.attributes.display_values.split( ',' ),
				values_length = Math.min( values.length, display_values.length );

			$( '#wpv-control-postmeta-source' )
				.val( 'custom' )
				.trigger( 'change' );

			values = values.slice( 0, values_length );
			display_values = display_values.slice( 0, values_length );

			values.forEach( function( current, index, all ) {
				self.add_control_postmeta_custom_source_option();
				let current_row = $( '.js-wpv-frontend-filter-postmeta-custom-options-list tbody tr.js-wpv-frontend-filter-postmeta-custom-options-list-item:last-child' ),
					current_dislay_value = display_values[ index ];

				current = current.replace( /\%\%COMMA\%\%/g, ',' );
				current_dislay_value = current_dislay_value.replace( /\%\%COMMA\%\%/g, ',' );
				current_row
					.find( '.js-wpv-frontend-filter-postmeta-custom-options-list-item-value' )
					.val( current );
				current_row
					.find( '.js-wpv-frontend-filter-postmeta-custom-options-list-item-display-value' )
					.val( current_dislay_value );
			} );
			// Fill as many table rows as needed, with the split things
			// We need a method for adding a new row, so we can generate the on JS directly
			// So remove the callback field definition for value_Combo.
		} else {
			self.add_control_postmeta_custom_source_option();
		}
		/*
		data_for_shortcode_dialog_requested_opened = {
				shortcode:	shortcode,
				title:		title,
				params:		params,
				overrides:	overrides,
				nonce:		wpv_shortcodes_gui_texts.wpv_editor_callback_nonce,
				dialog:		self.dialog_insert_shortcode
			};
		*/
	};

	self.compute_control_postmeta_custom_values = function( attribute_pairs ) {
		const values = [],
			display_values = [];
		$( '.js-wpv-frontend-filter-postmeta-custom-options-list tbody tr' ).each( function() {
			let option_row = $( this ),
				option_value = option_row.find( '.js-wpv-frontend-filter-postmeta-custom-options-list-item-value' ).val(),
				option_display_value = option_row.find( '.js-wpv-frontend-filter-postmeta-custom-options-list-item-display-value' ).val();
			option_value = option_value.replace( /,/g, '%%COMMA%%' );
			option_display_value = option_display_value.replace( /,/g, '%%COMMA%%' );
			values.push( option_value );
			display_values.push( option_display_value );
		} );
		attribute_pairs.values = values.join( ',' );
		attribute_pairs.display_values = display_values.join( ',' );
		return attribute_pairs;
	};

	self.filter_control_postmeta_computed_attributes = function( attribute_pairs ) {
		if ( 'custom' == attribute_pairs.source ) {
			attribute_pairs.order = false;
		}

		let actual_type = attribute_pairs.type;
		if ( attribute_pairs.type.substr( 0, 8 ) == 'toolset-' ) {
			actual_type = attribute_pairs.type.substr( 8 );
			attribute_pairs.type = false;
		}

		switch ( actual_type ) {
			case 'select':
			case 'radios':
				attribute_pairs.title = false;
				attribute_pairs.date_format = false;
				attribute_pairs.default_date = false;
				if ( 'custom' == attribute_pairs.source ) {
					attribute_pairs = self.compute_control_postmeta_custom_values( attribute_pairs );
				} else {
					attribute_pairs.values = false;
					attribute_pairs.display_values = false;
				}
				if ( 'select' == actual_type ) {
					attribute_pairs.label_class = false;
					attribute_pairs.label_style = false;
				}
				break;
			case 'multi-select':
			case 'checkboxes':
				attribute_pairs.title = false;
				attribute_pairs.date_format = false;
				attribute_pairs.default_date = false;
				attribute_pairs.default_label = false;

				if ( 'custom' == attribute_pairs.source ) {
					attribute_pairs = self.compute_control_postmeta_custom_values( attribute_pairs );
				} else {
					attribute_pairs.values = false;
					attribute_pairs.display_values = false;
				}
				if ( 'multi-select' == actual_type ) {
					attribute_pairs.label_class = false;
					attribute_pairs.label_style = false;
				}
				break;
			case 'checkbox':
				attribute_pairs.date_format = false;
				attribute_pairs.default_date = false;
				attribute_pairs.source = false;
				attribute_pairs.default_label = false;
				attribute_pairs.format = false;
				attribute_pairs.order = false;
				attribute_pairs.values = false;
				attribute_pairs.display_values = false;
				break;
			case 'date':
				attribute_pairs.title = false;
				attribute_pairs.source = false;
				attribute_pairs.default_label = false;
				attribute_pairs.format = false;
				attribute_pairs.order = false;
				attribute_pairs.label_class = false;
				attribute_pairs.label_style = false;
				attribute_pairs.values = false;
				attribute_pairs.display_values = false;
				break;
			case 'textfield':
			default:
				attribute_pairs.title = false;
				attribute_pairs.date_format = false;
				attribute_pairs.default_date = false;
				attribute_pairs.source = false;
				attribute_pairs.default_label = false;
				attribute_pairs.format = false;
				attribute_pairs.order = false;
				attribute_pairs.label_class = false;
				attribute_pairs.label_style = false;
				attribute_pairs.values = false;
				attribute_pairs.display_values = false;
				break;
		}

		attribute_pairs.value_compare = false;
		attribute_pairs.value_type = false;
		attribute_pairs.value_combo = false;
		attribute_pairs.value_real = false;

		attribute_pairs.url_param_min = false;
		attribute_pairs.url_param_max = false;

		attribute_pairs.shortcode_label = false;

		return attribute_pairs;
	};

	/**
     * Initialise the Filter Editor buttons in the case of 'posts' View query type and 'parametric' View purpose.
	 *
	 * @param editor_id The editor id.
	 *
     * @since 2.4.1
     */
	self.initFilterButtons = function( editor_id ) {
		if ( 'wpv_filter_meta_html_content' !== editor_id ) {
			return self;
		}

		const queryType = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-type', 'posts' ),
			purpose = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-purpose', '' );

		if ( queryType === 'posts' || purpose === 'parametric' ) {
			if ( $( '.js-wpv-settings-filter-extra .js-wpv-filter-editor-unlock' ).length > 0 ) {
				$( '.js-wpv-settings-filter-extra .js-code-editor-toolbar button:not( .js-wpv-parametric-search-filter-create )' ).prop( 'disabled', true );
				$( '.js-wpv-settings-filter-extra .quicktags-toolbar .button, .js-wpv-settings-filter-extra .quicktags-toolbar .js-code-editor-toolbar-button' ).prop( 'disabled', true );
				$( '.js-wpv-settings-filter-extra .js-wpv-parametric-search-filter-create' )
					.addClass( 'button-primary button-primary-toolset' )
					.removeClass( 'button-secondary' );
				if ( $( '.js-wpv-settings-filter-extra .js-wpv-filter-editor-overlay' ).length <= 0 ) {
					$( '.js-wpv-settings-filter-extra .CodeMirror-wrap ' ).prepend( self.overlayContainer );
				}

				// Unbinding the "mouseenter" event in order to disable the Toolset Tooltips when the editor buttons are disabled.
				// Of course we are binding the event again when the editor is unlocked.
				$( '.js-wpv-settings-filter-extra .js-code-editor-toolbar li' ).off( 'mouseenter' );
			} else {
				// Some browsers might keep buttons disabled on soft page reloads
				$( '.js-wpv-settings-filter-extra .js-code-editor-toolbar button:not( .js-wpv-parametric-search-filter-create )' ).prop( 'disabled', false );
				$( '.js-wpv-settings-filter-extra .quicktags-toolbar .button' ).prop( 'disabled', false );
			}
		}

		return self;
	};

	/**
     * Event handler for the editor quicktags initialization.
     * We needed to removed the faking of the "Show Bootstrap buttons" button and create a normal QT button instead.
     * Unfortunately, the special markup used for the Bootstrap buttons cannot be reproduced with the normal QT buttons,
     * so we still need to fake those, although for these to be faked, we now use the "quicktags-init" event that is
     * called every time a QT button is added.
     *
     * @since 2.4.1
     */
	jQuery( document ).on( 'quicktags-init', function( event, editor ) {
		_.defer( function() {
			self.initFilterButtons( editor.id );
		} );
	} );

	/**
     * Unlock the filter editor by removing the overlay div and by enabling all the buttons.
     *
     * @param unlockFilterControl The unlock control (normally the unlock hyperlink).
     *
     * @since 2.4.1
     */
	self.unlockFilterEditor = function( unlockFilterControl ) {
		const thizItem	= unlockFilterControl.closest( 'li' ),
			thizToolbar	= unlockFilterControl.closest( '.js-code-editor-toolbar' ),
			thizQuicktags	= $( '.js-wpv-settings-filter-extra .quicktags-toolbar' );
		thizItem.fadeOut( 'fast', function() {
			thizItem.remove();
			thizToolbar.find( '.button-secondary' ).prop( 'disabled', false );
			thizQuicktags.find( '.button, .js-code-editor-toolbar-button' ).prop( 'disabled', false );
			$( '.js-wpv-settings-filter-extra .js-wpv-parametric-search-filter-create' )
				.removeClass( 'button-primary-toolset button-primary' )
				.addClass( 'button-secondary' );
			$( '.js-wpv-settings-filter-extra .js-wpv-filter-editor-overlay' ).remove();

			// Reenabling the Toolset Tooltips that were disabled before when the editor buttons were disabled.
			$( '.js-wpv-settings-filter-extra .js-code-editor-toolbar li' ).each( function() {
            	const thiz = $( this ),
            		buttons_with_tooltips = [ '.js-wpv-parametric-search-text-filter-manage', '.js-wpv-parametric-search-submit-add', '.js-wpv-parametric-search-reset-add', '.js-wpv-parametric-search-spinner-add' ];
            	if ( thiz.find( 'button' ).is( buttons_with_tooltips.join() ) ) {
					thiz.toolsetTooltip();
				}
			} );
		} );
	};

	/**
     * Check if the editor is locked and if it is, unlock it.
     *
     * @since 2.4.1
     */
	self.maybeUnlockFilterEditor = function() {
    	const unlockFilterControl = $( '.js-wpv-settings-filter-extra .js-wpv-filter-editor-unlock' );
		if ( unlockFilterControl.length > 0 ) {
			self.unlockFilterEditor( unlockFilterControl );
		}
	};

	/**
     * Click handler for the "Unlock editor" hyperlinkCheck if the editor is locked and if it is, unlock it.
     *
     * @since 2.4.1
     */
	$( document ).on( 'click', '.js-wpv-filter-editor-unlock', function( e ) {
		e.preventDefault();
		self.maybeUnlockFilterEditor();
	} );

	/**
     * Click handler for the "New Filter" button with a callback that unlocks the filter editor.
     *
     * @since 2.4.1
     */
	$( document ).on( 'click', '.js-wpv-parametric-search-filter-create', function() {
		self.maybeUnlockFilterEditor();
	} );

	/**
     * Change handler for the "View purpose" dropdown that toggles the locking of the filter editor.
     *
     * @since 2.4.1
     */
	$( document ).on( 'js_event_wpv_screen_options_saved', function() {
		const purpose = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-purpose', '' );
		if ( purpose !== 'parametric' ) {
			self.maybeUnlockFilterEditor();
		}
	} );

	self.maybeGenerateForAttribute = function( rawShortcodeAttributes, parameterName ) {
		if( 'undefined' === typeof( parameterName ) ) {
			parameterName = 'url_param';
		}

		return _.has( rawShortcodeAttributes, parameterName )
			? ' for="' + rawShortcodeAttributes[parameterName] + '"'
			: '';
	};


	// ---------------------------------
	// Init hooks
	// ---------------------------------

	self.init_hooks = function() {
		/**
		 *
		 * @since 2.4.0
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-before-do-action', self.check_cursor_position, 1 );
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-before-do-action', self.add_control_structure_on_insert );
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-maybe-edit-shortcode-data', self.edit_stored_custom_search_shortcodes_attributes, 20 );

		Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-after-open-wpv-control-post-taxonomy-shortcode-dialog', self.adjust_control_post_taxonomy_dialog, 20 );
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv-control-post-taxonomy-computed-attributes-pairs', self.filter_control_post_taxonomy_computed_attributes );

		Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-after-open-wpv-control-postmeta-shortcode-dialog', self.adjust_control_postmeta_dialog, 20 );
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv-control-postmeta-computed-attributes-pairs', self.filter_control_postmeta_computed_attributes );

		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-query-type-changed', self.maybeUnlockFilterEditor, 10 );
		Toolset.hooks.addAction( 'toolset_text_editor_CodeMirror_init', self.initFilterButtons, 999 );

		return self;
	};

	// ---------------------------------
	// Init
	// ---------------------------------

	self.init = function() {
		self.init_hooks()
			.init_dialogs();
	};

	self.init();
};

jQuery( function( $ ) {
	WPViews.parametric_search_filters = new WPViews.ParametricSearchFilters( $ );
} );

WPViews.ParametricTextSearchButton = function( $ ) {
	const self = this;

	self.view_id = $( '.js-post_ID' ).val();

	self.button_action = 'create';

	self.button = $( '.js-wpv-parametric-search-text-filter-manage', '.js-wpv-filter-edit-toolbar' );
	self.button_container = self.button.closest( 'li' );
	self.editor_id = self.button.data( 'editor' );

	self.init_button = function() {
		self.button_container.toolsetTooltip();
		self.handle_flags();
	};

	self.has_filter = function() {
		return ( $( '.js-wpv-filter-post-search-options' ).length > 0 );
	};

	self.has_specific_filter = function() {
		return ( $( '.js-wpv-filter-post-search-options input#wpv-search-mode-specific' ).prop( 'checked' ) );
	};

	self.has_search = function() {
		const content = WPV_Toolset.CodeMirror_instance[ self.editor_id ].getValue();
		return (
			content.search( /\[wpv-filter-search-box/ ) == -1 &&
			content.search( /\url_param=\"wpv_post_search\"/ ) == -1
		) ? false : true;
	};

	self.is_button_disabled = function() {
		return (
			self.has_search() &&
			self.has_filter() &&
			! self.has_specific_filter()
		);
	};

	self.handle_flags = function() {
		self.button
			.removeClass( 'wpv-button-flagged' )
			.find( '.js-parametric-search-text-filter-button-complete, .js-parametric-search-text-filter-button-filter-missing' )
			.hide();
		if ( self.is_button_disabled() ) {
			self.button
				.addClass( 'disabled' )
				.addClass( 'wpv-button-flagged' )
				.find( '.js-parametric-search-text-filter-button-complete' )
				.show();
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.text_search.tooltip.complete );
			self.button_action = 'none';
		} else {
			self.button
				.removeClass( 'disabled' );
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.text_search.tooltip.original );
			self.button_action = 'create';
			if ( self.has_search() ) {
				if ( ! self.has_filter() ) {
					self.button
						.addClass( 'wpv-button-flagged' )
						.find( '.js-parametric-search-text-filter-button-filter-missing' )
						.show();
					self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.text_search.tooltip.missing );
					self.button_action = 'complete_missing';
				} else if ( self.has_specific_filter() ) {
					self.button
						.addClass( 'wpv-button-flagged' )
						.find( '.js-parametric-search-text-filter-button-filter-missing' )
						.show();
					self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.text_search.tooltip.wrong );
					self.button_action = 'complete_wrong';
				}
			}
		}
	};

	// @todo This will not be needed once we get the post search filter integrated with the other filters
	self.create_filter = function( shortcode_data ) {
		const data = {
			action:	'wpv_filter_post_search_update',
			id: self.view_id,
			filter_options: 'filter_by_search=1&post_search_value=&search_mode%5B%5D=manual&post_search_shortcode=search&post_search_content=' + shortcode_data.raw_attributes.value_where,
			update_query_filters_list: true,
			wpnonce:	wpv_parametric_i18n.toolbar_buttons.text_search.nonce,
		};
		$.ajax( {
			type:	'POST',
			dataType: 'json',
			async:	false, // Block any other interaction until this is completed
			url:	ajaxurl,
			data:	data,
			success:	function( response ) {
				if ( response.success ) {
					$( '.js-filter-list' ).html( response.data.query_filters );
					$( document ).trigger( 'js_event_wpv_query_filter_created', [ 'post_search' ] );
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'remove' } );
					$( document ).trigger( 'js_event_wpv_save_filter_post_search_completed' );
				}
			},
			error:	function( ajaxContext ) {

			},
			complete:	function() {
				self.handle_flags();
			},
		} );
	};

	self.maybe_edit_shortcode_data = function( shortcode_data ) {
		if (
			'wpv-filter-search-box' == shortcode_data.tag &&
			$( 'input[name="post_search_content"]:checked', '.js-wpv-filter-post-search-options' ).length > 0
		) {
			shortcode_data.attrs.named.value_where = $( 'input[name="post_search_content"]:checked', '.js-wpv-filter-post-search-options' ).val();
		}
		return shortcode_data;
	};

	self.extend_dialog_data = function( data ) {
		if ( 'wpv-filter-search-box' == data.shortcode ) {
			data.has_shortcode = ( self.has_search() ) ? 'true' : 'false';
		}
		return data;
	};

	self.adjust_dialog = function( data ) {
		let warning_message = '';
		if (
			self.has_search() ||
			self.has_filter()
		) {
			if ( self.has_filter() ) {
				if ( self.has_specific_filter() ) {
					warning_message = wpv_parametric_i18n.toolbar_buttons.text_search.warning.specific;
				} else if ( ! self.has_search() ) {
					warning_message = wpv_parametric_i18n.toolbar_buttons.text_search.warning.valid;
				}
			} else {
				warning_message = wpv_parametric_i18n.toolbar_buttons.text_search.warning.missing;
			}
		}

		if ( '' != warning_message ) {
			$( 'input.js-wpv-shortcode-gui-shortcode-name[value="wpv-filter-search-box"]' )
				.closest( '.js-toolset-dialog__body' )
					.prepend( '<p class="toolset-alert toolset-alert-info" style="margin:0 0 10px;">' + warning_message + '</p>' );
		}
	};

	self.filter_computed_attributes = function( attribute_pairs ) {
		attribute_pairs.value_where = false;
		attribute_pairs.value_label = false;
		if (
			_.has( attribute_pairs, 'output' ) &&
			'legacy' == attribute_pairs.output
		) {
			attribute_pairs.output = false;
		}
		return attribute_pairs;
	};

	self.before_insert_shortcode = function( shortcode_data ) {
		if ( 'wpv-filter-search-box' == shortcode_data.name ) {
			if (
				self.has_search() &&
				'insert' == Toolset.hooks.applyFilters( 'wpv-filter-wpv-shortcodes-gui-get-gui-action', 'insert' )
			) {
				Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-set-gui-action', 'skip' );
			}
			self.create_filter( shortcode_data );
		}
		return shortcode_data;
	};

	self.init_hooks = function() {
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-maybe-edit-shortcode-data', self.maybe_edit_shortcode_data );

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-extend-shortcode-dialog-data', self.extend_dialog_data );

		Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-after-open-wpv-filter-search-box-shortcode-dialog', self.adjust_dialog );

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv-filter-search-box-computed-attributes-pairs', self.filter_computed_attributes );

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-before-do-action', self.before_insert_shortcode );
	};

	$( document ).on( 'js_event_wpv_query_filter_created js_event_wpv_query_filter_deleted', function( event, filter ) {
		if ( filter == 'post_search' ) {
			self.handle_flags();
		}
	} );

	// ---------------------------------
	// Init
	// ---------------------------------

	self.init = function() {
		self.init_button();
		self.init_hooks();

		self.button.on( 'click', function() {
			if ( self.button.hasClass( 'disabled' ) ) {
				return false;
			}
			const dialog_data = {
				shortcode: 'wpv-filter-search-box',
			};
			switch ( self.button_action ) {
				case 'complete_missing':
				case 'complete_wrong':
					dialog_data.title = wpv_parametric_i18n.toolbar_buttons.text_search.dialog_title.complete;
					break;
				case 'create':
				default:
					dialog_data.title = wpv_parametric_i18n.toolbar_buttons.text_search.dialog_title.create;

					break;
			}
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-set-gui-action', 'insert' );
			if ( $( 'input[name="post_search_content"]:checked', '.js-wpv-filter-post-search-options' ).length > 0 ) {
				dialog_data.overrides = {
					attributes: {
						value_where: $( 'input[name="post_search_content"]:checked', '.js-wpv-filter-post-search-options' ).val(),
					},
				};
			}
			window.wpcfActiveEditor = self.editor_id;
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-open-shortcode-dialog', dialog_data );
		} );
	};

	self.init();
};

WPViews.ParametricSubmitButton = function( $ ) {
	const self = this;

	self.button = $( '.js-wpv-parametric-search-submit-add', '.js-wpv-filter-edit-toolbar' );
	self.button_container = self.button.closest( 'li' );
	self.editor_id = self.button.data( 'editor' );

	self.init_button = function() {
		self.button_container.toolsetTooltip();
		self.handle_flags();
	};

	self.handle_flags = function() {
		if ( ! self.needsSubmitControl() ) {
			self.button
				.addClass( 'disabled' )
				.removeClass( 'wpv-button-flagged' );
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.submit.tooltip.irrelevant );
			self.button
				.find( '.js-wpv-parametric-search-submit-button-irrelevant, .js-wpv-parametric-search-submit-button-incomplete, .js-wpv-parametric-search-submit-button-complete' )
				.hide();
			return;
		}

		self.button
			.removeClass( 'disabled' )
			.addClass( 'wpv-button-flagged' );

		let update_mode = '';
		if ( $( '.js-wpv-dps-ajax-results:checked' ).length > 0 ) {
			update_mode = $( '.js-wpv-dps-ajax-results:checked' ).val();
		}
		if ( update_mode == 'enable' ) {
			self.button
				.find( '.js-wpv-parametric-search-submit-button-irrelevant' )
				.show();
			self.button
				.find( '.js-wpv-parametric-search-submit-button-incomplete, .js-wpv-parametric-search-submit-button-complete' )
				.hide();
			if ( self.has_submit() ) {
				self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.submit.tooltip.irrelevant_added );
			} else {
				self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.submit.tooltip.irrelevant );
			}
		} else if ( update_mode != '' ) {
			self.button
				.find( '.js-wpv-parametric-search-submit-button-irrelevant, .js-wpv-parametric-search-submit-button-incomplete, .js-wpv-parametric-search-submit-button-complete' )
				.hide();
			if ( self.has_submit() ) {
				self.button.find( '.js-wpv-parametric-search-submit-button-complete' ).show();
				self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.submit.tooltip.complete );
			} else {
				self.button.find( '.js-wpv-parametric-search-submit-button-incomplete' ).show();
				self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.submit.tooltip.incomplete );
			}
		} else {
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.submit.tooltip.irrelevant );
		}
	};

	self.needsSubmitControl = function() {
		const content = WPV_Toolset.CodeMirror_instance[ self.editor_id ].getValue();
		return ( content.search( /\[wpv-control/ ) == -1 && content.search( /\[wpv-filter-search/ ) == -1 && content.search( /\[wpv-sort-order/ ) == -1 ) ? false : true;
	};

	self.has_submit = function( ) {
		const content = WPV_Toolset.CodeMirror_instance[ self.editor_id ].getValue();
		return ( content.search( /\[wpv-filter-submit/ ) == -1 ) ? false : true;
	};

	self.filter_computed_attributes = function( attribute_pairs ) {
		if (
			_.has( attribute_pairs, 'output' ) &&
			'legacy' == attribute_pairs.output
		) {
			attribute_pairs.output = false;
		}
		return attribute_pairs;
	};

	// ---------------------------------
	// Init
	// ---------------------------------

	self.init = function() {
		self.init_button();

		self.button.on( 'click', function() {
			const dialog_data = {
				shortcode:	'wpv-filter-submit',
				title:	wpv_parametric_i18n.toolbar_buttons.submit.dialog_title,
			};
			if ( self.button.hasClass( 'disabled' ) ) {
				return false;
			}
			window.wpcfActiveEditor = self.editor_id;
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-set-gui-action', 'insert' );
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-open-shortcode-dialog', dialog_data );
		} );

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv-filter-submit-computed-attributes-pairs', self.filter_computed_attributes );
	};

	self.init();
};

WPViews.ParametricResetButton = function( $ ) {
	const self = this;

	self.button = $( '.js-wpv-parametric-search-reset-add', '.js-wpv-filter-edit-toolbar' );
	self.button_container = self.button.closest( 'li' );
	self.editor_id = self.button.data( 'editor' );

	self.init_button = function() {
		self.button_container.toolsetTooltip();
		self.handle_flags();
	};

	self.handle_flags = function() {
		if ( ! self.needsResetControl() ) {
			self.button
				.addClass( 'disabled' )
				.removeClass( 'wpv-button-flagged' );
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.reset.tooltip.irrelevant );
			self.button
				.find( '.js-wpv-parametric-search-reset-button-complete' )
				.hide();
			return;
		}

		self.button.removeClass( 'disabled' );

		if ( self.has_reset() ) {
			self.button
				.addClass( 'wpv-button-flagged' )
				.find( '.js-wpv-parametric-search-reset-button-complete' )
				.show();
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.reset.tooltip.complete );
		} else {
			self.button
				.removeClass( 'wpv-button-flagged' )
				.find( '.js-wpv-parametric-search-reset-button-complete' )
				.hide();
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.reset.tooltip.original );
		}
	};

	self.needsResetControl = function() {
		const content = WPV_Toolset.CodeMirror_instance[ self.editor_id ].getValue();
		return ( content.search( /\[wpv-control/ ) == -1 && content.search( /\[wpv-filter-search/ ) == -1 ) ? false : true;
	};

	self.has_reset = function() {
		const content = WPV_Toolset.CodeMirror_instance[ self.editor_id ].getValue();
		return ( content.search( /\[wpv-filter-reset/ ) == -1 ) ? false : true;
	};

	self.filter_computed_attributes = function( attribute_pairs ) {
		if (
			_.has( attribute_pairs, 'output' ) &&
			'legacy' == attribute_pairs.output
		) {
			attribute_pairs.output = false;
		}
		return attribute_pairs;
	};

	// ---------------------------------
	// Init
	// ---------------------------------

	self.init = function() {
		self.init_button();

		self.button.on( 'click', function() {
			const dialog_data = {
				shortcode:	'wpv-filter-reset',
				title:	wpv_parametric_i18n.toolbar_buttons.reset.dialog_title,
			};
			if ( self.button.hasClass( 'disabled' ) ) {
				return false;
			}
			window.wpcfActiveEditor = self.editor_id;
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-set-gui-action', 'insert' );
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-open-shortcode-dialog', dialog_data );
		} );

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv-filter-reset-computed-attributes-pairs', self.filter_computed_attributes );
	};

	self.init();
};

WPViews.ParametricSpinnerButton = function( $ ) {
	const self = this;

	self.button = $( '.js-wpv-parametric-search-spinner-add', '.js-wpv-filter-edit-toolbar' );
	self.button_container = self.button.closest( 'li' );
	self.editor_id = self.button.data( 'editor' );

	self.init_button = function() {
		self.button_container.toolsetTooltip();
		self.handle_flags();
	};

	self.handle_flags = function() {
		const update_mode = $( '.js-wpv-dps-ajax-results:checked' ).val(),
			update_action = $( '.js-wpv-ajax-results-submit:checked' ).val(),
			dependency_mode = $( '.js-wpv-dps-enable:checked' ).val();

		if (
			dependency_mode != 'disable' ||
			(
				update_mode != 'disable' ||
				(
					update_mode == 'disable' &&
					update_action != 'reload'
				)
			)
		) {
			self.button.removeClass( 'disabled' );
			if ( self.has_spinner() ) {
				self.button
					.addClass( 'wpv-button-flagged' )
					.find( '.js-wpv-parametric-search-spinner-button-complete' )
					.show();
				self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.spinner.tooltip.complete );
			} else {
				self.button
					.removeClass( 'wpv-button-flagged' )
					.find( '.js-wpv-parametric-search-spinner-button-complete' )
					.hide();
				self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.spinner.tooltip.original );
			}
		} else {
			self.button
				.addClass( 'disabled' )
				.removeClass( 'wpv-button-flagged' )
				.find( '.js-wpv-parametric-search-spinner-button-complete' )
				.hide();
			self.button_container.data( 'tooltip-text', wpv_parametric_i18n.toolbar_buttons.spinner.tooltip.useless );
		}
	};

	self.has_spinner = function() {
		const content = WPV_Toolset.CodeMirror_instance[ self.editor_id ].getValue();
		return ( content.search( /\[wpv-filter-spinner/ ) == -1 ) ? false : true;
	};

	self.manage_spiner_image_gui_per_position = function() {
		const position = $( '#wpv-filter-spinner-position' ).val();
		if ( 'none' == position ) {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-spinner' ).slideUp( 'fast' );
		} else {
			$( '.js-wpv-shortcode-gui-attribute-wrapper-for-spinner' ).slideDown( 'fast' );
		}
	};

	$( document ).on( 'change', '#wpv-filter-spinner-position', function() {
		self.manage_spiner_image_gui_per_position();
	} );

	self.adjust_dialog = function( data ) {
		self.manage_spiner_image_gui_per_position();
	};

	self.filter_computed_attributes = function( attribute_pairs ) {
		if (
			_.has( attribute_pairs, 'position' ) &&
			'none' === attribute_pairs.position
		) {
			attribute_pairs.spinner = false;
		}
		return attribute_pairs;
	};

	// ---------------------------------
	// Init
	// ---------------------------------

	self.init = function() {
		self.init_button();

		self.button.on( 'click', function() {
			const dialog_data = {
				shortcode:	'wpv-filter-spinner',
				title:	wpv_parametric_i18n.toolbar_buttons.spinner.dialog_title,
			};
			if ( self.button.hasClass( 'disabled' ) ) {
				return false;
			}
			window.wpcfActiveEditor = self.editor_id;
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-set-gui-action', 'insert' );
			Toolset.hooks.doAction( 'wpv-action-wpv-shortcodes-gui-open-shortcode-dialog', dialog_data );
		} );

		Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-after-open-wpv-filter-spinner-shortcode-dialog', self.adjust_dialog, 20 );
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-wpv-filter-spinner-computed-attributes-pairs', self.filter_computed_attributes );
	};

	self.init();
};

WPViews.ParametricGenericButton = function( $ ) {
	const self = this;

	self.handle_flags = function() {
		WPViews.ParametricButtons.text_search.handle_flags();
		WPViews.ParametricButtons.submit.handle_flags();
		WPViews.ParametricButtons.reset.handle_flags();
		WPViews.ParametricButtons.spinner.handle_flags();
	};

	self.maybe_button_hide = function() {
		const query_type = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-type', 'posts' ),
			purpose = Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-purpose', '' );

		if ( 'posts' == query_type ) {
			$( '.js-wpv-parametric-search-filter-create', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.show();
			$( '.js-wpv-parametric-search-filter-edit', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.show();
			$( '.js-wpv-parametric-search-text-filter-manage', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.show();
			$( '.js-wpv-parametric-search-submit-add', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.show();
			$( '.js-wpv-parametric-search-reset-add', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.show();
			$( '.js-wpv-parametric-search-spinner-add', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.show();
			switch ( purpose ) {
				case 'slider':
					$( '.js-wpv-parametric-search-filter-create', '.js-wpv-filter-edit-toolbar' )
						.closest( 'li' )
						.hide();
					$( '.js-wpv-parametric-search-filter-edit', '.js-wpv-filter-edit-toolbar' )
						.closest( 'li' )
						.hide();
					$( '.js-wpv-parametric-search-text-filter-manage', '.js-wpv-filter-edit-toolbar' )
						.closest( 'li' )
						.hide();
					$( '.js-wpv-parametric-search-submit-add', '.js-wpv-filter-edit-toolbar' )
						.closest( 'li' )
						.hide();
					$( '.js-wpv-parametric-search-reset-add', '.js-wpv-filter-edit-toolbar' )
						.closest( 'li' )
						.hide();
					$( '.js-wpv-parametric-search-spinner-add', '.js-wpv-filter-edit-toolbar' )
						.closest( 'li' )
						.hide();
					break;
			}
		} else {
			$( '.js-wpv-parametric-search-filter-create', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.hide();
			$( '.js-wpv-parametric-search-filter-edit', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.hide();
			$( '.js-wpv-parametric-search-text-filter-manage', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.hide();
			$( '.js-wpv-parametric-search-submit-add', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.hide();
			$( '.js-wpv-parametric-search-reset-add', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.hide();
			$( '.js-wpv-parametric-search-spinner-add', '.js-wpv-filter-edit-toolbar' )
				.closest( 'li' )
				.hide();
		}
	};

	$( document ).on( 'js_event_wpv_screen_options_saved', self.maybe_button_hide );
	$( document ).on( 'js_event_wpv_query_type_saved', self.maybe_button_hide );

	self.init = function() {
		self.maybe_button_hide();
		WPV_Toolset.CodeMirror_instance[ 'wpv_filter_meta_html_content' ].on( 'change', function() {
			self.handle_flags();
		} );
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-parametric-filter-buttons-handle-flags', self.handle_flags );
	};

	self.init();
};

jQuery(function ($) {
    if ( wpv_parametric_i18n.is_views_lite ) {
		return;
	}
	if ( ! wpv_parametric_i18n.is_legacy_editor ) {
		return;
	}
	WPViews.ParametricButtons.text_search = new WPViews.ParametricTextSearchButton($);
	WPViews.ParametricButtons.submit = new WPViews.ParametricSubmitButton($);
	WPViews.ParametricButtons.reset = new WPViews.ParametricResetButton($);
	WPViews.ParametricButtons.spinner = new WPViews.ParametricSpinnerButton($);

	WPViews.ParametricButtons.generic = new WPViews.ParametricGenericButton($);
});

