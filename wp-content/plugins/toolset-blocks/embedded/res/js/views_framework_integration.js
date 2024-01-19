/**
* views_framework_integration.js
*
* Contains helper functions for the Views framework integration settings page
*
* @since 1.8.0
* @package Views
*/

var WPViews = WPViews || {};

WPViews.FrameworkIntegration = function( $ ) {

	var self = this;
	self.slug_pattern = /^[a-z0-9\-\_]+$/;

	self.auto_detected_selected = $( '.js-wpv-framework-auto:checked' ).data( 'id' );

	$( document ).on('input cut paste', '.js-wpv-add-item-settings-form-newname', function( e ) {
		var parent_form = $( this ).closest( '.js-wpv-add-item-settings-form' ),
		parent_container = $( this ).closest( '.js-wpv-add-item-settings-wrapper' ),
		action_button = parent_container.find( '.js-wpv-framework-slug-add' );
		$( '.js-wpv-cs-error, .js-wpv-cs-dup, .js-wpv-cs-ajaxfail', parent_form ).hide();
		if ( $( this ).val() != '' ) {
			action_button
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );
		} else {
			action_button
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		}
	});

	$( document ).on( 'submit', '.js-wpv-add-item-settings-form', function( e ) {
		var thiz = $( this ),
		parent_container = $( this ).closest( '.js-wpv-add-item-settings-wrapper' ),
		action_button = parent_container.find( '.js-wpv-framework-slug-add' );
		e.preventDefault();
		action_button.click();
		return false;
	});

	$( document ).on( 'click', '.js-wpv-framework-slug-add', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		parent_form = $( this ).closest( '.js-wpv-add-item-settings-form' ),
		parent_container = $( this ).closest( '.js-wpv-add-item-settings-wrapper' ),
		new_slug = $( '.js-wpv-add-item-settings-form-newname', parent_form );
		$( '.js-wpv-cs-error, .js-wpv-cs-dup, .js-wpv-cs-ajaxfail', parent_form ).hide();
		if ( self.slug_pattern.test( new_slug.val() ) == false ) {
			$( '.js-wpv-cs-error', parent_form ).show();
		} else if ( $( '.js-' + new_slug.val() + '-item', parent_container ).length > 0 ) {
			$( '.js-wpv-cs-dup', parent_form ).show();
		} else {
			var spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show();
			thiz
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
			var data = {
				action: 'wpv_update_framework_integration_keys',
				update_action: 'add',
				update_tag: new_slug.val(),
				wpv_framework_integration_nonce: views_framework_integration_texts.nonce
			};
			$.ajax({
				type:"POST",
				dataType: "json",
				url:ajaxurl,
				data:data,
				success:function( response ) {
					if ( response.success ) {
						$( '.js-wpv-add-item-settings-list', parent_container )
							.append( '<li class="js-' + new_slug.val() + '-item"><span class="">' + new_slug.val() + '</span> <i class="icon-remove-sign fa fa-times-circle js-wpv-framework-slug-delete" data-target="' + new_slug.val() + '"></i></li>' );
						new_slug.val( '' );
						$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
					} else {
						$( '.js-wpv-cs-ajaxfail', parent_form ).show();
					}
				},
				error: function ( ajaxContext ) {
					$( '.js-wpv-cs-ajaxfail', parent_form ).show();
				},
				complete: function() {
					spinnerContainer.remove();
				}
			});
		}
		return false;
	});

	// Delete additional inner shortcodes

	$( document ).on( 'click', '.js-wpv-framework-slug-delete', function( e ) {
		e.preventDefault();
		var thiz = $( this ).data( 'target' ),
		parent_container = $( this ).closest( '.js-wpv-add-item-settings-wrapper' ),
		action_button = parent_container.find( '.js-wpv-framework-slug-add' ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( action_button ).show();
		var data = {
			action: 'wpv_update_framework_integration_keys',
			update_action: 'delete',
			update_tag: thiz,
			wpv_framework_integration_nonce: views_framework_integration_texts.nonce
		};
		$.ajax({
			type:"POST",
			dataType: "json",
			url:ajaxurl,
			data:data,
			success:function( response ) {
				if ( response.success ) {
					$( 'li.js-' + thiz + '-item', parent_container )
						.addClass( 'remove' )
						.fadeOut( 'fast', function() {
							$( this ).remove();
						});
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( '.js-wpv-cs-ajaxfail', parent_container ).show();
				}
			},
			error: function ( ajaxContext ) {
				$( '.js-wpv-cs-ajaxfail', parent_container ).show();
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});
		return false;
	});

	$( document ).on( 'change', '.js-wpv-framework-auto', function() {
		self.manage_auto_detect_change();
	});

	self.manage_auto_detect_change = function() {
		var thiz = $( '.js-wpv-framework-auto:checked' ),
		thiz_id = thiz.data( 'id' ),
		message_container = $( '.js-wpv-framework-auto-detect-selection .js-wpv-message-container' );
		if ( thiz.length < 1 ) {
			return;
		}
		if ( thiz_id != self.auto_detected_selected ) {
			$( '.js-wpv-autodetect-frameworks-selection-warning' ).fadeIn( 'fast' );
		} else {
			$( '.js-wpv-autodetect-frameworks-selection-warning' ).fadeOut( 'fast' );
		}
	};

	$( document ).on( 'click', '.js-wpv-autodetect-frameworks-selection-warning-apply', function( e ) {
		e.preventDefault();
		$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

		var thiz = $( this ),
		selected_target_val = $( '.js-wpv-framework-auto:checked' ).val(),
		selected_target_id = $( '.js-wpv-framework-auto:checked' ).data( 'id' ),
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show(),
		data = {
			action: 'wpv_register_auto_detected_framework',
			framework: selected_target_val,
			wpv_framework_integration_nonce: views_framework_integration_texts.nonce
		};

		if ( self.auto_detected_selected == '' ) {
			$( '.js-toolset-views-manual-framework' ).fadeOut( 'fast ');
		} else {
			$( '.js-toolset-views-registered-framework-' + self.auto_detected_selected ).hide();
		}

		if ( selected_target_val == '' ) {
			$( '.js-toolset-views-registered-framework' ).fadeOut( 'fast', function() {
				$( '.js-toolset-views-manual-framework' ).fadeIn( 'fast ');
			});
		} else {
			// Hide the current settings and show a spinner if needed
			if ( $( '.js-toolset-views-registered-framework-' + selected_target_id ).length > 0 ) {
				$( '.js-toolset-views-registered-framework-' + selected_target_id ).fadeIn( 'fast' );
			} else {
				data['include_section'] = selected_target_val;
			}
		}

		$.ajax({
			type:"POST",
			dataType: "json",
			url:ajaxurl,
			data:data,
			success:function( response ) {
				if ( response.success ) {
					self.auto_detected_selected = selected_target_id;
					$( '.js-wpv-autodetect-frameworks-selection-warning' ).fadeOut( 'fast' );
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
					if ( selected_target_val != '' ) {
						if ( $( '.js-toolset-views-registered-framework-' + selected_target_id ).length == 0 ) {
							$( '.js-toolset-views-registered-framework' )
								.find( '.toolset-setting' )
									.prepend( response.data.section );
						}
						$( '.js-toolset-views-registered-framework' )
							.fadeIn( 'fast' );
					}
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
				}
			},
			error: function ( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			},
			complete: function() {
				spinnerContainer.remove();
			}
		});
		/**
		* AJAX call:
		* - Save the new autoregistered framework, even empty
		* - Get the current registered settings, if any and needed because not there yet, and show in the right container or add it if missing - both the container and content
		* - Get the manual registration example, if empty and needed because not there yet, and show it
		*/
	});

	$( document ).on( 'click', '.js-wpv-autodetect-frameworks-selection-warning-cancel', function( e ) {
		e.preventDefault();
		$( '.js-wpv-framework-auto[data-id="' + self.auto_detected_selected + '"' ).prop( 'checked', true );
		$( '.js-wpv-autodetect-frameworks-selection-warning' ).fadeOut( 'fast' );
	});

	self.init = function() {

	};

	self.init();

};

jQuery( function( $ ) {
    WPViews.framework_integration = new WPViews.FrameworkIntegration( $ );
});
