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

WPViews.SearchFilterGUI = function( $, id ) {

	var self = this;

	self.view_id = id || $('.js-post_ID').val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.post_row							= '.js-wpv-filter-row-post-search';
	self.post_options_container_selector	= '.js-wpv-filter-post-search-options';
	self.post_summary_container_selector	= '.js-wpv-filter-post-search-summary';
	self.post_edit_open_selector			= '.js-wpv-filter-post-search-edit-open';
	self.post_close_save_selector			= '.js-wpv-filter-post-search-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	self.compatibility_data = {
		relevanssi:		{
			available:	wpv_search_strings.relevanssi.available,
			settings:	{
							'indexed_cpt':	wpv_search_strings.relevanssi.settings.indexed_post_types
						}
		}
	};

	self.warnings = {
		relevanssi:	{
			'cpt_not_indexed':			'<p class="js-wpv-filter-post-search-compatibility-relevanssi-indexed-cpt js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-exclamation-triangle" aria-hidden"true"=""></i> ' + wpv_search_strings.relevanssi.cpt_not_indexed + '</p>',
			'cpt_not_indexed_archive':	'<p class="js-wpv-filter-post-search-compatibility-relevanssi-indexed-cpt js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-exclamation-triangle" aria-hidden"true"=""></i> ' + wpv_search_strings.relevanssi.cpt_not_indexed_archive + '</p>',
			'sort_filter':				'<li class="js-wpv-filter-post-search-compatibility-relevanssi-sort-filter js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-exclamation-triangle" aria-hidden"true"=""></i> ' + wpv_search_strings.relevanssi.sort.filter + '</li>',
			'sort_archive':				'<li class="js-wpv-filter-post-search-compatibility-relevanssi-sort-archive js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-exclamation-triangle" aria-hidden"true"=""></i> ' + wpv_search_strings.relevanssi.sort.archive + '</li>',
			'not_only_sort_archive':	'<li class="js-wpv-filter-post-search-compatibility-relevanssi-not-only-sort-archive js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-exclamation-triangle" aria-hidden"true"=""></i> ' + wpv_search_strings.relevanssi.sort.not_only_archive + '</li>'
		},
		builtin:	{
			'filter_on_archive':		'<p class="js-wpv-filter-post-search-compatibility-builtin-filter-on-archive js-wpv-permanent-alert-error toolset-alert toolset-alert-warning"><i class="fa fa-exclamation-triangle" aria-hidden"true"=""></i> ' + wpv_search_strings.builtin.filter_on_archive + '</p>'
		}
	};

	self.post_sort							= '.js-wpv-settings-posts-order';
	self.relevanssi_sort_overlay			= $("<div class='wpv-setting-overlay js-wpv-relevanssi-search-and-sort-overlay' style='top:0'><div class='wpv-transparency'></div><i class='icon-lock fa fa-lock' style='padding:0 5px'></i></div>");

	self.tax_row							= '.js-wpv-filter-row-taxonomy-search';
	self.tax_options_container_selector		= '.js-wpv-filter-taxonomy-search-options';
	self.tax_summary_container_selector		= '.js-wpv-filter-taxonomy-search-summary';
	self.tax_edit_open_selector				= '.js-wpv-filter-taxonomy-search-edit-open';
	self.tax_close_save_selector			= '.js-wpv-filter-taxonomy-search-edit-ok';

	self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select').serialize();

	//--------------------
	// Events for search
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
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'remove' } );
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

	self.save_filter_post_search = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-post-search' );
			if ( valid ) {
				var action = thiz.data('saveaction'),
					nonce = thiz.data('nonce'),
					spinnerContainer = $(self.spinner).insertBefore(thiz).show(),
					error_container = thiz
						.closest('.js-filter-row')
						.find('.js-wpv-filter-toolset-messages');
				self.post_current_options = $(self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select').serialize();
				var data = {
					action: action,
					id: self.view_id,
					filter_options: self.post_current_options,
					wpnonce: nonce
				};
				$.ajax({
					type: "POST",
					url: ajaxurl,
					dataType: "json",
					data: data,
					success: function (response) {
						if (response.success) {
							$(self.post_close_save_selector)
								.addClass('button-secondary')
								.removeClass('button-primary js-wpv-section-unsaved')
								.html(
									WPViews.query_filters.icon_edit + $(self.post_close_save_selector).data('close')
								);
							$(self.post_summary_container_selector).html(response.data.summary);
							WPViews.query_filters.close_and_glow_filter_row(self.post_row, 'wpv-filter-saved');
							Toolset.hooks.doAction('wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric);
							Toolset.hooks.doAction('wpv-action-wpv-edit-screen-parametric-filter-buttons-handle-flags');
							$(document).trigger(event);
							// raise custom event for the QueryFilterSettingsPanel of Views for Gutenberg
							$( document ).trigger( 'wpvFilterSaveCompleted' );
							if (propagate) {
								$(document).trigger('js_wpv_save_section_queue');
							} else {
								$(document).trigger('js_event_wpv_set_confirmation_unload_check');
							}
						} else {
							Toolset.hooks.doAction('wpv-action-wpv-edit-screen-manage-ajax-fail', {
								data: response.data,
								container: error_container
							});
							if (propagate) {
								$(document).trigger('js_wpv_save_section_queue');
							}
						}
					},
					error: function (ajaxContext) {
						console.log("Error: ", textStatus, errorThrown);
						Toolset.hooks.doAction('wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_search');
						if (propagate) {
							$(document).trigger('js_wpv_save_section_queue');
						}
					},
					complete: function () {
						spinnerContainer.remove();
						thiz.hide();
					}
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_search' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_search( 'js_event_wpv_save_filter_post_search_completed', false );
	});

	// Remove filter from the save queue an clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_search' == filter_type ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'remove' } );
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-get-parametric-search-hints' );
			self.check_compatibility.relevanssi( self );
		}
	});

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_search' ) {
			self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_search', action: 'add' } );
			self.check_compatibility.relevanssi( self );
		}
	});

	//--------------------
	// Compatibility for post search
	//--------------------

	self.check_compatibility = {
		relevanssi:		function() {

			if ( self.compatibility_data.relevanssi.available == 'false' ) {
				return;
			}

			var query_mode							= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
				post_types_in_search_not_indexed	= [],
				post_types_selected_not_indexed		= [],
				auxiliar_warning					= '';

			switch ( query_mode ) {
				case 'normal':
					$( '.js-wpv-filter-post-search-compatibility-relevanssi-indexed-cpt, .js-wpv-filter-post-search-compatibility-relevanssi-sort-filter, .js-wpv-filter-post-search-compatibility-relevanssi-post-date' ).remove();
					if (
						$( self.post_row ).length > 0
						&& $( '#js-row-post_search input[name="post_search_content"]:checked' ).length > 0
						&& $( '#js-row-post_search input[name="post_search_content"]:checked' ).val() == 'content_extended'
					) {
						// Warning on sorting
						$( '.js-wpv-settings-posts-order select' ).prop( 'disabled', false );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-adjust-sorting-section', 'posts' );
						$( self.warnings.relevanssi['sort_filter'] ).prependTo( self.post_sort ).show();
						// View: check that the selected post types are indexed
						post_types_selected_not_indexed = $( '.js-wpv-query-post-type:checked' ).map( function() {
							if ( _.indexOf( self.compatibility_data.relevanssi.settings['indexed_cpt'], $( this ).val() ) == -1 ) {
								return $( this ).parent( 'li' ).find( 'label' ).html();
							}
						}).get();
						if ( post_types_selected_not_indexed.length > 0 ) {
							auxiliar_warning = self.warnings.relevanssi['cpt_not_indexed'];
							auxiliar_warning = auxiliar_warning.replace( '##CTPLIST##', '<strong>' + post_types_selected_not_indexed.join( '</strong>, <strong>' ) + '</strong>' );
							$( auxiliar_warning ).prependTo( self.post_row ).show();
						}
					}
					break;
				case 'archive':
					$( '.js-wpv-filter-post-search-compatibility-relevanssi-sort-archive, .js-wpv-filter-post-search-compatibility-relevanssi-sort-filter, .js-wpv-relevanssi-search-and-sort-overlay, .js-wpv-filter-post-search-compatibility-relevanssi-post-date, .js-wpv-filter-post-search-compatibility-relevanssi-indexed-cpt, .js-wpv-filter-post-search-compatibility-relevanssi-not-only-sort-archive' ).remove();
                    $( '.js-wpv-settings-posts-order select' ).prop( 'disabled', false );
					if ( $( '.js-wpv-settings-archive-loop #wpv-view-loop-search-page:checked' ).length > 0 ) {
						// WPA assigned to the search archive loop
						if( $( '.js-wpv-settings-archive-loop input:not(#wpv-view-loop-search-page):checked' ).length > 0 ) {
                            $( self.warnings.relevanssi['not_only_sort_archive'] ).prependTo( self.post_sort ).show();
						}
						else {
                            $( '.js-wpv-settings-posts-order select' ).prop( 'disabled', true );
                            $( self.relevanssi_sort_overlay ).prependTo( $( self.post_sort + ' li' ) ).show();
						}
                        $( self.warnings.relevanssi['sort_archive'] ).prependTo( self.post_sort ).show();
						if ( $( '.js-wpv-apply-post-types-to-archive-loop-tracker[data-name="search"][data-type="native"]' ).length > 0 ) {
							post_types_in_search_not_indexed = $( '.js-wpv-apply-post-types-to-archive-loop-tracker[data-name="search"][data-type="native"]' ).data( 'selected' );
							post_types_in_search_not_indexed = _.difference( post_types_in_search_not_indexed, self.compatibility_data.relevanssi.settings['indexed_cpt'] );
							if ( post_types_in_search_not_indexed.length > 0 ) {
								auxiliar_warning = self.warnings.relevanssi['cpt_not_indexed_archive'];
								auxiliar_warning = auxiliar_warning.replace( '##CTPLIST##', '<strong>' + post_types_in_search_not_indexed.join( '</strong>, <strong>' ) + '</strong>' );
								$( auxiliar_warning ).prependTo( $( '.js-wpv-settings-archive-loop .js-wpv-toolset-messages' ) );
							}
						}

					}
					if (
						$( self.post_row ).length > 0
						&& $( '#js-row-post_search input[name="post_search_content"]:checked' ).length > 0
						&& $( '#js-row-post_search input[name="post_search_content"]:checked' ).val() == 'content_extended'
					) {
						// Query filter needs warning on indexed post types
						$( '.js-wpv-apply-post-types-to-archive-loop-tracker' ).each( function() {
							var thiz_native_selected_post_types = $( this ).data( 'selected' );
							post_types_in_search_not_indexed = _.union( post_types_in_search_not_indexed, thiz_native_selected_post_types );
						});
						$( 'input.js-wpv-settings-archive-loop[data-type="post_type"]:checked' ).each( function() {
							post_types_in_search_not_indexed = _.union( post_types_in_search_not_indexed, [ $( this ).data( 'name' ) ] );
						});
						post_types_in_search_not_indexed = _.difference( post_types_in_search_not_indexed, self.compatibility_data.relevanssi.settings['indexed_cpt'] );
						if ( post_types_in_search_not_indexed.length > 0 ) {
							auxiliar_warning = self.warnings.relevanssi['cpt_not_indexed'];
							auxiliar_warning = auxiliar_warning.replace( '##CTPLIST##', '<strong>' + post_types_in_search_not_indexed.join( '</strong>, <strong>' ) + '</strong>' );
							$( auxiliar_warning ).prependTo( self.post_row );
						}
						if ( $( '.js-wpv-settings-archive-loop #wpv-view-loop-search-page:checked' ).length == 0 ) {
							$( self.warnings.relevanssi['sort_filter'] ).prependTo( self.post_sort ).show();
						}
					}
					// Maybe warning on Post date filter
					break;
			}

		},
		builtin:	function() {

			var query_mode						= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
				auxiliar_warning				= '';

			if ( query_mode == 'archive' ) {
				$( '.js-wpv-filter-post-search-compatibility-builtin-filter-on-archive' ).remove();
				if (
					$( '#wpv-view-loop-search-page' ).prop( 'checked' )
					&& $( self.post_row ).length > 0
				) {
					//Ops! A search filter on a WPA assigned to the search archive
					$( self.warnings.builtin['filter_on_archive'] ).prependTo( self.post_row ).show();
				}
			}

		}
	};

	self.init_compatibility = function() {
		self.check_compatibility.relevanssi();
		self.check_compatibility.builtin();
		return self;
	};

	$( document ).on( 'js_event_wpv_query_type_saved js_event_wpv_save_section_loop_selection_completed js_event_wpv_save_filter_post_search_completed js_event_wpv_save_section_sorting_completed', function( event ) {
		self.check_compatibility.relevanssi();
		self.check_compatibility.builtin();
	});

	$( document ).on( 'js_event_wpv_post_types_for_archive_loop_updated', function( event, data ) {
		if (
			data.type == 'native'
			&& data.name == 'search'
		) {
			self.check_compatibility.relevanssi();
			self.check_compatibility.builtin();
		}
	});

	$( document ).on( 'js_event_wpv_query_filter_created js_event_wpv_query_filter_saved', function( event, filter_type ) {
		if ( filter_type == 'post_date' ) {
			self.check_compatibility.relevanssi();
		}
	});

	//--------------------
	// Events for taxonomy search
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
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'add' } );
			$( self.tax_close_save_selector )
				.addClass( 'button-primary js-wpv-section-unsaved' )
				.removeClass( 'button-secondary' )
				.html(
					WPViews.query_filters.icon_save + $( self.tax_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'remove' } );
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

	self.save_filter_taxonomy_search = function( event, propagate ) {
		var thiz = $( self.tax_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.tax_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'remove' } );

		if ( self.tax_current_options == $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.tax_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-taxonomy-search' );
			if ( valid ) {
				var action = thiz.data('saveaction'),
					nonce = thiz.data('nonce'),
					spinnerContainer = $(self.spinner).insertBefore(thiz).show(),
					error_container = thiz
						.closest('.js-filter-row')
						.find('.js-wpv-filter-toolset-messages');
				self.tax_current_options = $(self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select').serialize();
				var data = {
					action: action,
					id: self.view_id,
					filter_options: self.tax_current_options,
					wpnonce: nonce
				};
				$.post(ajaxurl, data, function (response) {
					if (response.success) {
						$(self.tax_close_save_selector)
							.addClass('button-secondary')
							.removeClass('button-primary js-wpv-section-unsaved')
							.html(
								WPViews.query_filters.icon_edit + $(self.tax_close_save_selector).data('close')
							);
						$(self.tax_summary_container_selector).html(response.data.summary);
						WPViews.query_filters.close_and_glow_filter_row(self.tax_row, 'wpv-filter-saved');
						$(document).trigger(event);
						if (propagate) {
							$(document).trigger('js_wpv_save_section_queue');
						} else {
							$(document).trigger('js_event_wpv_set_confirmation_unload_check');
						}
					} else {
						Toolset.hooks.doAction('wpv-action-wpv-edit-screen-manage-ajax-fail', {
							data: response.data,
							container: error_container
						});
						if (propagate) {
							$(document).trigger('js_wpv_save_section_queue');
						}
					}
				}, 'json')
					.fail(function (jqXHR, textStatus, errorThrown) {
						console.log("Error: ", textStatus, errorThrown);
						Toolset.hooks.doAction('wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_taxonomy_search');
						if (propagate) {
							$(document).trigger('js_wpv_save_section_queue');
						}
					})
					.always(function () {
						spinnerContainer.remove();
						thiz.hide();
					});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_taxonomy_search' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.tax_close_save_selector, function() {
		self.save_filter_taxonomy_search( 'js_event_wpv_save_filter_taxonomy_search_completed', false );
	});

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'taxonomy_search' == filter_type ) {
			self.tax_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'remove' } );
		}
	});

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'taxonomy_search' ) {
			self.tax_current_options = $( self.tax_options_container_selector + ' input, ' + self.tax_options_container_selector + ' select' ).serialize();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_taxonomy_search', action: 'add' } );
		}
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.SearchFilterGUI}
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
			handle:		'save_filter_post_search',
			callback:	self.save_filter_post_search,
			event:		'js_event_wpv_save_filter_post_search_completed'
		});
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_taxonomy_search',
			callback:	self.save_filter_taxonomy_search,
			event:		'js_event_wpv_save_filter_taxonomy_search_completed'
		});

		/**
		 * Clears events and hooks.
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-filter-clear-events-and-hooks', self.clear_events_and_hooks );

		return self;
	};

	//--------------------
	// Init
	//--------------------

	self.init = function() {
		self.init_hooks()
			.init_compatibility();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.search_filter_gui = new WPViews.SearchFilterGUI( $ );
});
