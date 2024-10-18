/**
* Views Post Type Filter GUI - script
*
* Adds basic interaction for the Post Type Filter
*
* @package Views
*
* @since WIP 2.4.0
*/


var WPViews = WPViews || {};

WPViews.PostTypeFilterGUI = function( $ ) {

	var self = this;

	self.view_id = $('.js-post_ID').val();

	self.spinner			= '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.post_row							= '.js-wpv-filter-row-post-type';
	self.post_options_container_selector	= '.js-wpv-filter-post-type-options';
	self.post_summary_container_selector	= '.js-wpv-filter-post-type-summary';
	self.post_edit_open_selector			= '.js-wpv-filter-post-type-edit-open';
	self.post_close_save_selector			= '.js-wpv-filter-post-type-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	//--------------------
	// Events for post type
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
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_type', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_type', action: 'remove' } );
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

	self.save_filter_post_type = function( event, propagate ) {
		var thiz = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_type', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			thiz.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-wpv-filter-row-post-type' );
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
				$.post( wpv_filter_post_type_texts.ajaxurl, data, function( response ) {
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
					Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_type' );
					if ( propagate ) {
						$( document ).trigger( 'js_wpv_save_section_queue' );
					}
				})
				.always( function() {
					spinnerContainer.remove();
					thiz.hide();
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_type' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter_post_type( 'js_event_wpv_save_filter_post_type_completed', false );
	});

	$( document ).on( 'js_event_wpv_query_filter_created', function( event, filter_type ) {
		if ( filter_type == 'post_type' ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_type', action: 'add' } );
		}
	});

	// Remove filter from the save queue an clean cache

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_type' == filter_type ) {
			self.clear_save_queue();
		}
	});

	self.clear_save_queue = function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_type', action: 'remove' } );
	}

	//--------------------
	// Custom search filters
	//--------------------
	/*
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
	*/
	//--------------------
	// Init hooks
	//--------------------

	self.init_hooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle:		'save_filter_post_type',
			callback:	self.save_filter_post_type,
			event:		'js_event_wpv_save_filter_post_type_completed'
		});
		// Register a callback to remove the filter form the save queue on demand
		Toolset.hooks.addAction( 'wpv-action-wpv-edit-screen-clear-query-filter-save-queue', self.clear_save_queue );
		// Callback to show/hide the frontend filter dialog sections
		//Toolset.hooks.addAction( 'wpv-action-wpv-shortcodes-gui-after-open-wpv-control-post-type-shortcode-dialog', self.frontend_filter_dialog_open );

		return self;
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
    WPViews.post_type_filter_gui = new WPViews.PostTypeFilterGUI( $ );
});
