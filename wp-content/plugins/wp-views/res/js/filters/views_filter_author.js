/**
* Views Author Filter GUI - script
*
* Adds basic interaction for the Author Filter
*
* @package Views
*
* @since 1.7.0
*/

var WPViews = WPViews || {};

WPViews.AuthorFilterGUI = function( $, id ) {
	var self = this;

	self.view_id = id || $( '.js-post_ID' ).val();
	if ( self.view_id === undefined ) {
		// this means we're inside the Gutenberg View editor, but doing initialization too early
		return;
	}

	self.spinner			= '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';
	self.disabled_for_loop	= '<p class="js-wpv-archive-filter-post-author-disabled js-wpv-permanent-alert-error toolset-alert toolset-alert-error"><i class="fa fa-warning" aria-hidden"true"=""></i> ' + wpv_filter_author_texts.archive.disable_author_filter + '</p>';

	self.post_row							= '.js-wpv-filter-row-post-author';
	self.post_options_container_selector	= '.js-wpv-filter-post-author-options';
	self.post_summary_container_selector	= '.js-wpv-filter-post-author-summary';
	self.post_edit_open_selector			= '.js-wpv-filter-post-author-edit-open';
	self.post_close_save_selector			= '.js-wpv-filter-post-author-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	//--------------------
	// Events for author
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
		$( this ).removeClass( 'filter-input-error' );
		$( self.post_close_save_selector ).prop( 'disabled', false );
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_author', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_author', action: 'remove' } );
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

	self.save_filter_post_author = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_author', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-post-author' );
			if ( valid ) {
				var action = thiz.data( 'saveaction' ),
				nonce = thiz.data('nonce'),
				spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
				error_container = thiz
					.closest( '.js-filter-row' )
						.find( '.js-wpv-filter-toolset-messages' );
				self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
				var data = {
					action:			action,
					id:				self.view_id,
					filter_options:	self.post_current_options,
					wpnonce:		nonce
				};
				$.post( wpv_filter_author_texts.ajaxurl, data, function( response ) {
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
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_author' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				})
				.always( function() {
					spinnerContainer.remove();
					thiz.hide();
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_author' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_author( 'js_event_wpv_save_filter_post_author_completed', false );
	});

	// Remove filter from the save queue an clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_author' == filter_type ) {
			self.clear_save_queue();
		}
	});

	/**
	 * Clear the save queue from traces of this filter.
	 *
	 * @since 2.4.0
	 */
	self.clear_save_queue = function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_author', action: 'remove' } );
	}

	//--------------------
	// Manage WordPress Archives restrictions
	//--------------------

	/**
	* manage_wordpress_archive_filter_warning
	*
	* Add or remove the warning messages when:
	* 	- WPA - combining a query filter by post author and an author archive loop
	*
	* @since 2.1
	*/

	self.manage_wordpress_archive_filter_warning = function() {
		var query_mode			= Toolset.hooks.applyFilters( 'wpv-filter-wpv-edit-screen-get-query-mode', 'normal' ),
		native_loops_selected	= [],
		author_filter_row		= $( self.post_row );
		if ( author_filter_row.length > 0 ) {
			if ( query_mode == 'normal' ) {

			} else {
				$( '.js-wpv-archive-filter-post-author-disabled' ).remove();
				var native_loops_selected = $( '.js-wpv-settings-archive-loop input[data-type="native"]:checked' ).map( function() {
					return $( this ).data( 'name' );
				}).get();
				if ( _.contains( native_loops_selected, 'author' ) ) {
					$( self.disabled_for_loop ).prependTo( self.post_row ).show();
				}
			}
		}
		return self;
	};

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_author' ) {
			self.manage_wordpress_archive_filter_warning();
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_author', action: 'add' } );
		}
	});

	$( document ).on( 'js_event_wpv_save_section_loop_selection_completed', function( event ) {
		self.manage_wordpress_archive_filter_warning();
	});

	//--------------------
	// Custom search filters
	//--------------------

	/**
	 * Adjust the dialog for inserting the wpv-control-post-author shortcode.
	 *
	 * @since WIP 2.4.0
	 */

	self.frontend_filter_dialog_open = function() {
		var type = $( '.js-wpv-shortcode-gui-attribute-wrapper-for-type #wpv-control-post-author-type' ).val();
		switch ( type ) {
			case 'select':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label' ).fadeIn( 'fast' );
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-label_style, .js-wpv-shortcode-gui-attribute-wrapper-for-label_class' ).hide();
				break;
			case 'multiselect':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label, .js-wpv-shortcode-gui-attribute-wrapper-for-label_style, .js-wpv-shortcode-gui-attribute-wrapper-for-label_class' ).hide();
				break;
			case 'radios':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label, .js-wpv-shortcode-gui-attribute-wrapper-for-label_style, .js-wpv-shortcode-gui-attribute-wrapper-for-label_class' ).fadeIn( 'fast' );
				break;
			case 'checkboxes':
				$( '.js-wpv-shortcode-gui-attribute-wrapper-for-default_label, .js-wpv-shortcode-gui-attribute-wrapper-for-label_style, .js-wpv-shortcode-gui-attribute-wrapper-for-label_class' ).hide();
				break;
		}
	};

	$( document ).on( 'change', '#wpv-control-post-author-display-options .js-wpv-shortcode-gui-attribute-wrapper-for-type', self.frontend_filter_dialog_open );

	//--------------------
	// Suggest
	//--------------------

	/**
	* init_suggest_on_input
	*
	* Init the suggest script on a given input field.
	*
	* @since 2.1
	*/

	self.init_suggest_on_input = function( input ) {
		input.suggest( wpv_filter_author_texts.ajaxurl + '&action=wpv_suggest_author', {
			resultsClass:	'ac_results wpv-suggest-results',
			onSelect:		function() {
				thevalue = this.value;
				thevalue = thevalue.split(' #');
				$( '.js-author-suggest' ).val( thevalue[0] );
				$( '.js-author-suggest-id' ).val( thevalue[1].substring(8).trim() );
			}
		});
		input.addClass( 'js-wpv-suggest-on' );
	};

	/**
	* init_suggest
	*
	* Init the suggest script for the query filter by post author.
	*
	* @since 2.1
	*/

	self.init_suggest = function() {
		$( '.js-author-suggest:not(.js-wpv-suggest-on)' ).each( function() {
			self.init_suggest_on_input( $( this ) );
		});
		return self;
	};

	$( document ).on( 'focus', '.js-author-suggest:not(.js-wpv-suggest-on)', function() {
		self.init_suggest_on_input( $( this ) );
	});

	/**
	 * Clears events and hooks.
	 *
	 * Put here all the events and hooks that need to be removed when the module is off-loaded.
	 *
	 * @returns {WPViews.AuthorFilterGUI}
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
			handle:		'save_filter_post_author',
			callback:	self.save_filter_post_author,
			event:		'js_event_wpv_save_filter_post_author_completed'
		});
		// Register a callback to remove the filter form the save queue on demand
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-clear-query-filter-save-queue', self.clear_save_queue );
		// Callback to show/hide the frontend filter dialog sections
		Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-after-open-wpv-control-post-author-shortcode-dialog', self.frontend_filter_dialog_open );

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
			.init_suggest()
			.manage_wordpress_archive_filter_warning();
	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.author_filter_gui = new WPViews.AuthorFilterGUI( $ );
});
