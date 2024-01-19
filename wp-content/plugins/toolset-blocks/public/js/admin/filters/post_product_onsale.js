
/**
 * Adds basic interaction for the Post Product Onsale Filter
 *
 * @package Views
 * @todo Most of this logic can be abstracted to a general class that can be inherited here.
 */

var WPViews = WPViews || {};

/**
 * @param jQuery $
 * @param string id Optional, View ID pased only when in the blocks editor.
 */
WPViews.PostProductOnsaleFilterGUI = function( $, id ) {

	var self = this;
	self.view_id = id || $( '.js-post_ID' ).val();

	self.spinner = '<span class="wpv-spinner ajax-loader"></span>&nbsp;&nbsp;';

	self.post_row = '.js-wpv-filter-row-post_product_onsale';
	self.post_options_container_selector = '.js-wpv-filter-post_product_onsale-options';
	self.post_summary_container_selector = '.js-wpv-filter-post_product_onsale-summary';
	self.post_messages_container_selector = '.js-wpv-filter-row-post_product_onsale .js-wpv-filter-toolset-messages';
	self.post_edit_open_selector = '.js-wpv-filter-post_product_onsale-edit-open';
	self.post_close_save_selector = '.js-wpv-filter-post_product_onsale-edit-ok';

	self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();

	$( document ).on( 'click', self.post_edit_open_selector, function() {
		self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
		$( self.post_close_save_selector ).show();
		$( self.post_row ).addClass( 'wpv-filter-row-current' );
	});

	$( document ).on( 'change keyup input cut paste', self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select', function() {
		$( this ).removeClass( 'filter-input-error' );
		$( self.post_close_save_selector ).prop( 'disabled', false );
		WPViews.query_filters.clear_validate_messages( self.post_row );
		if ( self.post_current_options != $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_product_onsale', action: 'add' } );
			$( self.post_close_save_selector )
				.addClass('button-primary js-wpv-section-unsaved')
				.removeClass('button-secondary')
				.html(
					WPViews.query_filters.icon_save + $( self.post_close_save_selector ).data('save')
				);
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-set-confirm-unload', true );
		} else {
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_product_onsale', action: 'remove' } );
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

	self.save_filter = function( event, propagate ) {
		var $closeSaveButton = $( self.post_close_save_selector );
		WPViews.query_filters.clear_validate_messages( self.post_row );

		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_relationship', action: 'remove' } );

		if ( self.post_current_options == $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize() ) {
			WPViews.query_filters.close_filter_row( self.post_row );
			$closeSaveButton.hide();
		} else {
			var valid = WPViews.query_filters.validate_filter_options( '.js-filter-post_product_onsale' );
			if ( valid ) {
				var action = $closeSaveButton.data( 'saveaction' ),
				nonce = $closeSaveButton.data('nonce'),
				$spinnerContainer = $( self.spinner ).insertBefore( $closeSaveButton ).show(),
				error_container = $closeSaveButton
					.closest( '.js-filter-row' )
					.find( '.js-wpv-filter-toolset-messages' );
				self.post_current_options = $( self.post_options_container_selector + ' input, ' + self.post_options_container_selector + ' select' ).serialize();
				var data = {
					action: action,
					id: self.view_id,
					filter_options: self.post_current_options,
					wpnonce: nonce
				};
				$.ajax( {
					type: "POST",
					url: ajaxurl,
					dataType: "json",
					data: data,
					success: function( response ) {
						if ( response.success ) {
							$( document ).trigger( 'js_event_wpv_query_filter_saved', [ 'post_product_onsale' ] );
							$( self.post_close_save_selector )
								.addClass( 'button-secondary' )
								.removeClass( 'button-primary js-wpv-section-unsaved' )
								.html(
									WPViews.query_filters.icon_edit + $( self.post_close_save_selector ).data( 'close' )
								);
							$( self.post_summary_container_selector ).html( response.data.summary );
							WPViews.query_filters.close_and_glow_filter_row( self.post_row, 'wpv-filter-saved' );
							Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-parametric-search-hints', response.data.parametric );
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
					},
					error: function( ajaxContext ) {
						console.log( "Error: ", textStatus, errorThrown );
						Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_product_onsale' );
						if ( propagate ) {
							$( document ).trigger( 'js_wpv_save_section_queue' );
						}
					},
					complete: function() {
						$spinnerContainer.remove();
						$closeSaveButton
							.prop( 'disabled', false )
							.hide();
					}
				});
			} else {
				Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-fail-queue', 'save_filter_post_product_onsale' );
				if ( propagate ) {
					$( document ).trigger( 'js_wpv_save_section_queue' );
				}
			}
		}
	};

	$( document ).on( 'click', self.post_close_save_selector, function() {
		self.save_filter( 'js_event_wpv_save_filter_post_product_onsale_completed', false );
	});

	$( document ).on( 'js_event_wpv_query_filter_deleted', function( event, filter_type ) {
		if ( 'post_product_onsale' == filter_type ) {
			self.post_current_options = '';
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-manage-save-queue', { section: 'save_filter_post_product_onsale', action: 'remove' } );
			Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-get-parametric-search-hints' );
		}
	});

	self.clear_events_and_hooks = function() {
		$( document ).off( 'click', self.post_close_save_selector );
		return self;
	}

	self.add_control_structure_on_insert = function( shortcode_string, shortcode_data, shortcode_gui_action ) {
		if ( 'insert' === shortcode_gui_action ) {
			return '<div class="form-check">' + '\n\t' + shortcode_string + '\n' + '</div>';
		}

		return shortcode_string;
	};

	self.initHooks = function() {
		// Register the filter saving action
		Toolset.hooks.doAction( 'wpv-action-wpv-edit-screen-define-save-callbacks', {
			handle: 'save_filter_post_product_onsale',
			callback: self.save_filter,
			event: 'js_event_wpv_save_filter_post_product_onsale_completed'
		});

		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-add-control-structure-on-insert-for-wpv-control-post-product-onsale',
			self.add_control_structure_on_insert,
			10
		);

		/**
		 * Clears events and hooks.
		 */
		Toolset.hooks.addAction( 'wpv-action-wpv-filter-clear-events-and-hooks', self.clear_events_and_hooks );

		return self;
	};

	self.init = function() {
		self.initHooks();
	};

	self.init();

}

jQuery( function( $ ) {
    WPViews.post_product_onsale_filter_gui = new WPViews.PostProductOnsaleFilterGUI( $ );
});
