/**
 * Views Embedded read-only screens - script
 *
 * @package Views
 *
 * @since unknown
 */

var WPViews = WPViews || {};

WPViews.ViewsCompatibilityWooCommerce = function( $ ) {

	var self = this;

	$( document ).on( 'click', '.js-wpv-wcv-missing-mandatory-dismiss', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
			spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show(),
			data = {
				action:		'wpv_wcv_missing_mandatory_dismiss',
				wpnonce:	wpv_wcv_i18n.nonce
			};
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					$( '.js-wpv-wcv-missing-mandatory' ).fadeOut( 'fast', function() {
						$( this ).remove();
					});
				}
			},
			error:		function ( ajaxContext ) {

			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	});

	$( document ).on( 'click', '.js-wpv-wcv-switch-single-template', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
			spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show();
		thiz
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary disabled' )
			.prop( 'disabled', true );
		var data = {
			action:		'wpv_wcv_switch_single_template',
			wpnonce:	wpv_wcv_i18n.nonce
		};
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					self.remove_switch_single_template_notice();
				}
			},
			error:		function ( ajaxContext ) {

			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	});

	$( document ).on( 'click', '.js-wpv-wcv-switch-single-template-dismiss', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
			spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show(),
			data = {
			action:		'wpv_wcv_switch_single_template_dismiss',
			wpnonce:	wpv_wcv_i18n.nonce
		};
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					self.remove_switch_single_template_notice()
						.show_switch_dismiss_template_hint();
				}
			},
			error:		function ( ajaxContext ) {

			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	});

	self.remove_switch_single_template_notice = function() {
		$( '.js-wpv-notice-wcv-switch-single-template' ).fadeOut( 'fast', function() {
			$( this ).remove();
		});
		return self;
	};

	$( document ).on( 'click', '.js-wpv-wcv-switch-archive-template', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
			spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show();
		thiz
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary disabled' )
			.prop( 'disabled', true );
		var data = {
			action:		'wpv_wcv_switch_archive_template',
			wpnonce:	wpv_wcv_i18n.nonce
		};
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					self.remove_switch_archive_template_notice();
				}
			},
			error:		function ( ajaxContext ) {

			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	});

	$( document ).on( 'click', '.js-wpv-wcv-switch-archive-template-dismiss', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
			spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertAfter( thiz ).show(),
			data = {
			action:		'wpv_wcv_switch_archive_template_dismiss',
			wpnonce:	wpv_wcv_i18n.nonce
		};
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					self.remove_switch_archive_template_notice()
						.show_switch_dismiss_template_hint();
				}
			},
			error:		function ( ajaxContext ) {

			},
			complete:	function() {
				spinnerContainer.remove();
			}
		});
	});

	self.remove_switch_archive_template_notice = function() {
		$( '.js-wpv-notice-wcv-switch-archive-template' ).fadeOut( 'fast', function() {
			$( this ).remove();
		});
		return self;
	};

	self.show_switch_dismiss_template_hint = function() {
		self.wpv_wcv_template_settings_hint_dialog.dialog( 'open' );
		return self;
	};

	self.init_dialogs = function() {

		$( 'body' ).append( '<div id="js-wpv-wcv-template-settings-hint" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container js-wpv-shortcode-gui-dialog-container"><div class="wpv-dialog"><p>' + wpv_wcv_i18n.dialog.text + '</p></div></div>' );
		self.wpv_wcv_template_settings_hint_dialog = $( '#js-wpv-wcv-template-settings-hint' ).dialog({
			autoOpen:	false,
			modal:		true,
			title:		wpv_wcv_i18n.dialog.title,
			minWidth:	600,
			show: {
				effect: "blind",
				duration: 800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-primary',
					text: wpv_wcv_i18n.dialog.button,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});

	};

	self.init = function() {
		self.init_dialogs();
	};

	self.init();

}

jQuery( function( $ ) {
	WPViews.views_compatibility_woocommerce = new WPViews.ViewsCompatibilityWooCommerce( $ );
});
