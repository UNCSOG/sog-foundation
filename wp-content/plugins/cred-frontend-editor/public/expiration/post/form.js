/**
 * Post expiration interaction on the form editor.
 *
 * @since 2.3
 */
var Toolset = Toolset || {};
Toolset.Forms = Toolset.Forms || {};
Toolset.Forms.Expiration = Toolset.Forms.Expiration || {};

Toolset.Forms.Expiration.FormClass = function( $ ) {

	var self = this;

	self.i18n = cred_post_expiration_form_i18n;

	/**
	 * Hide the post expiration settings:
	 *   expiration period and action from the settings metabox
	 *   expiration trigger from notifications
	 *
	 * @since 2.3
	 */
	self.hidePostExpirationSettings = function() {
		$( '.cred_post_expiration_panel, .cred_post_expiration_options' ).fadeIn( 'fast' );
	};

	/**
	 * Show the post expiration settings:
	 *   expiration period and action from the settings metabox
	 *   expiration trigger from notifications
	 *
	 * @since 2.3
	 */
	self.showPostExpirationSettings = function() {
		$( '.cred_post_expiration_panel' ).fadeOut( 'fast' );
		$( '.cred_post_expiration_options' ).each( function( index, element ) {
			var $option = $( element );
			if ( $('input[type="radio"]', $option ).prop( 'checked' ) ) {
				$( 'input[type="radio"]:visible', $option.siblings() )
					.first()
					.prop( 'checked', true );
			}
		}).hide();
	};

	/**
	 * Check whether expiration settinhs should be visible or not based on the general switcher checkbox.
	 *
	 * @since 2.3
	 */
	self.initSettings = function() {
		$( '#js-cred-post-expiration-form-switcher' ).trigger( 'change' );
	};

	/**
	 * Track events to the general switcher and notifications addition to show or hide expiration settings.
	 *
	 * @since 2.3
	 */
	self.initEvents = function() {
		$( document ).on( 'change', '#js-cred-post-expiration-form-switcher', function() {
			if ( $( this ).prop( 'checked' ) ) {
				self.hidePostExpirationSettings();
			} else {
				self.showPostExpirationSettings();
			}
		});

		$( document ).on( 'toolset:forms:editor:afterAddItem', '#cred_notification_settings_panel_container', function() {
			if ( $( '#js-cred-post-expiration-form-switcher' ).prop( 'checked' ) ) {
				self.hidePostExpirationSettings();
			} else {
				self.showPostExpirationSettings();
			}
		});
	};

	/**
	 * Initialize the post expiration interaction.
	 *
	 * @since 2.2.1
	 */
	self.init = function() {
		self.initEvents();
		self.initSettings();
	};

	self.init();

}

jQuery( function( $ ) {
	Toolset.Forms.Expiration.Form = new Toolset.Forms.Expiration.FormClass( $ );
});
