/**
 * MCE views for Toolset Forms
 *
 * @since 2.2
 */
var Toolset = Toolset || {};
Toolset.Forms = Toolset.Forms || {};
Toolset.Forms.Expiration = Toolset.Forms.Expiration || {};

Toolset.Forms.Expiration.SingularClass = function( $ ) {

	var self = this;

	self.i18n = cred_post_expiration_singular_i18n;

	self.datepicker_style_id = 'js-toolset-datepicker-style';
	self.is_datepicker_style_loaded = false;

	/**
	 * Dynamically load the Toolset datepicker style, only when needed.
	 *
	 * @since 2.2.1
	 */
	self.maybeLoadDatepickerStyle = function() {
		if ( ! self.is_datepicker_style_loaded ) {
			if ( document.getElementById( self.datepicker_style_id ) ) {
				self.is_datepicker_style_loaded = true;
			} else {
				var head = document.getElementsByTagName( 'head' )[0],
					link = document.createElement( 'link' );

				link.id = self.datepicker_style_id;
				link.rel = 'stylesheet';
				link.type = 'text/css';
				link.href = self.i18n.datepicker_style_url;
				link.media = 'all';
				head.appendChild( link );

				self.is_datepicker_style_loaded = true;
			}
		}
	};

	/**
	 * Initialize the metabox interaction regarding enable/disable post expiration.
	 *
	 * @since 2.2.1
	 */
	self.initMetabox = function() {
		if ( $( '#js-cred-post-expiration-switcher' ).length ) {
			$( '#js-cred-post-expiration-switcher' ).on( 'change', function( e ) {
				if ( $( this ).prop( 'checked' ) ) {
					$( '#js-cred-post-expiration-panel' ).fadeIn( 'fast' )
				} else {
					$( '#js-cred-post-expiration-panel' ).fadeOut( 'fast' );
				}
			}).trigger( 'change' );
		}
	};

	/**
	 * Initialize the datepicker in the post expiration metabox.
	 *
	 * @since 2.2.1
	 */
	self.initDatepicker = function() {
		if ( ! $('.js-cred-post-expiration-datepicker').length ) {
			return;
		}

		self.maybeLoadDatepickerStyle();

		$( '.js-cred-post-expiration-datepicker' ).datepicker({
			onSelect: function( dateText, inst ) {
				var dateToFormat = $( '.js-cred-post-expiration-datepicker' ).datepicker( "option", "dateFormat", "ddmmyy" ).val();
				// Restore the original date format
				$( '.js-cred-post-expiration-datepicker' ).datepicker( "option", "dateFormat", self.i18n.dateFormat );

				var data = {
					action: self.i18n.ajax.formatPostExpirationDate.action,
					wpnonce: self.i18n.ajax.formatPostExpirationDate.nonce,
					date: dateToFormat
				};
				$.ajax({
					type: "POST",
					dataType: "json",
					url: self.i18n.ajaxurl,
					data: data,
					success: function( response ) {
						if ( response.success ) {
							$( '.js-cred-post-expiration-datepicker-aux' ).val( response.data.timestamp );
						}
					},
					error: function( ajaxContext ) {},
					complete: function() {}
				});
			},
			showOn: "both",
			buttonImage: self.i18n.buttonImage,
			buttonImageOnly: true,
			buttonText: self.i18n.buttonText,
			dateFormat: self.i18n.dateFormat,
			changeMonth: true,
			changeYear: true
		});
	};

	/**
	 * Initialize the post expiration interaction.
	 *
	 * @since 2.2.1
	 */
	self.init = function() {
		self.initMetabox();
		self.initDatepicker();
	};

	self.init();

}

jQuery( function( $ ) {
	Toolset.Forms.Expiration.Singular = new Toolset.Forms.Expiration.SingularClass( $ );
});
