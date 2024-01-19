var Toolset_CRED = Toolset_CRED || {};

Toolset_CRED.SettingsScreen = function( $ ) {

	var self = this;

	self.DialogSpinnerContent = $(
        '<div style="min-height: 150px;">' +
            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
                '<div class="wpv-spinner ajax-loader"></div>' +
                '<p>Loading</p>' +
            '</div>' +
        '</div>'
    );

	self.init_dialogs = function() {
		if ( ! $('#cred-allowed-tags-dialog-container').length ) {
			$( 'body' ).append( '<div id="cred-allowed-tags-dialog-container" style="display: none;">' );
		}
		self.dialog_allowed_tags = $( "#cred-allowed-tags-dialog-container" ).dialog({
			autoOpen: false,
			modal: true,
			minWidth: 450,
			title: 'Select allowed HTML tags',
			show: {
				effect: "blind",
				duration: 800
			},
			create: function( event, ui ) {
				$( event.target ).parent().css( 'position', 'fixed' );

				if($('.js-cred-bootstrap-styling-setting').is(':checked')){
						$('.js-cred-legacy-styling-setting').attr('checked', false).attr('disabled', 'disabled');
				}
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-cred-allowed-tags-apply' )
					.show()
					.addClass( 'button-primary' )
					.removeClass( 'button-secondary' )
					.prop( 'disabled', false );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'button-secondary',
					text: "Cancel",
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{
					class: 'button-primary js-cred-allowed-tags-apply',
					text: "Apply",
					click: function() {
						self.dialog_allowed_tags_apply();
					}
				}
			]
		});

	}

	self.dialog_allowed_tags_apply = function() {
		var apply_button = $( '.js-cred-allowed-tags-apply' ),
		selected_fields = [],
		spinnerContainer = $( '<div class="wpv-spinner ajax-loader">' ).insertBefore( apply_button ).show();
		apply_button
			.toggleClass( 'button-primary button-secondary' )
			.prop( 'disabled', true );
		$( '.js-cred-allowed-tags-list' )
			.find( 'input:checked' )
			.each( function() {
				selected_fields.push( $( this ).val() );
			});
		var data = {
			action:		'cred_set_allowed_tags',
			fields:		selected_fields,
			wpnonce:	$( '#cred-manage-allowed-tags' ).val()
		};
		$.ajax({
			url: ajaxurl,
			data: data,
			type: "POST",
			dataType:"json",
			success: function( response ) {
				if ( response.success ) {
					$( '.js-cred-allowed-tags-summary' ).html( response.data.content );
					self.dialog_allowed_tags.dialog( "close" );
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				}
			},
			complete: function() {
				spinnerContainer.remove();
            }
		});
	};

	$( document ).on( 'click', '.js-cred-select-allowed-tags', function() {
		var dialog_height = $(window).height() - 100;
		self.dialog_allowed_tags.dialog('open').dialog({
				width: 770,
				maxHeight: dialog_height,
				draggable: false,
				resizable: false,
				position: {
					my: "center top+50",
					at: "center top",
					of: window,
					collision: "none"
				}
			});
		self.dialog_allowed_tags.html( self.DialogSpinnerContent );
		var data = {
			action:		'cred_get_allowed_tags',
			wpnonce:	$( '#cred-manage-allowed-tags' ).val()
		};
		$.ajax({
			url: ajaxurl,
			data: data,
			type: "GET",
			dataType:"json",
			success: function( response ) {
				if ( response.success ) {
					self.dialog_allowed_tags.html( response.data.content );
					self.manage_allowed_tags_select_all_switch();
				}
			}
		});
	});

	$( document ).on( 'change', '#js-cred-allowed-tags-select-all', function() {
		var thiz = $( this );
		if ( thiz.prop( 'checked' ) ) {
			$( '.js-cred-allowed-tags-list input' ).prop( 'checked', true );
		} else {
			$( '.js-cred-allowed-tags-list input' ).prop( 'checked', false );
		}
	});

	$( document ).on( 'change', '.js-cred-allowed-tags-list input', function() {
		self.manage_allowed_tags_select_all_switch();
	});

	self.manage_allowed_tags_select_all_switch = function() {
		if ( $( '.js-cred-allowed-tags-list input:checked' ).length == $( '.js-cred-allowed-tags-list input' ).length ) {
			$( '#js-cred-allowed-tags-select-all' ).prop( 'checked', true );
		} else {
			$( '#js-cred-allowed-tags-select-all' ).prop( 'checked', false );
		}
	};

	/**
	* Wizard
	*/

	self.cred_wizard_state = $( '.js-toolset-forms-wizard .js-cred-settings-wrapper input' ).serialize();

	$( document ).on( 'change', '.js-cred-wizard-setting', function() {
		if ( self.cred_wizard_state != $( '.js-toolset-forms-wizard .js-cred-settings-wrapper input' ).serialize() ) {
			self.cred_wizard_options_debounce_update();
		}
	});

	self.save_cred_wizard_options = function() {
		var data = $( '.js-toolset-forms-wizard .js-cred-settings-wrapper input' ).serialize(),
		nonce = $( '#cred-wizard-settings' ).val();
		self.save_settings_section( 'cred_save_wizard_settings', data, nonce )
			.done( function( response ) {
				if ( response.success ) {
					self.cred_wizard_state = $( '.js-toolset-forms-wizard .js-cred-settings-wrapper input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});

	};

	self.cred_wizard_options_debounce_update = _.debounce( self.save_cred_wizard_options, 1000 );

	/**
	* Export
	*/

	self.cred_export_state = $( '.js-toolset-forms-export .js-cred-settings-wrapper input' ).serialize();

	$( document ).on( 'change', '.js-cred-export-setting', function() {
		if ( self.cred_export_state != $( '.js-toolset-forms-export .js-cred-settings-wrapper input' ).serialize() ) {
			self.cred_export_options_debounce_update();
		}
	});

	self.save_cred_export_options = function() {
		var data = $( '.js-toolset-forms-export .js-cred-settings-wrapper input' ).serialize(),
		nonce = $( '#cred-export-settings' ).val();
		self.save_settings_section( 'cred_save_export_settings', data, nonce )
			.done( function( response ) {
				if ( response.success ) {
					self.cred_export_state = $( '.js-toolset-forms-export .js-cred-settings-wrapper input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
	};

	self.cred_export_options_debounce_update = _.debounce( self.save_cred_export_options, 1000 );

	/**
	* Styling
	*/

	self.cred_styling_state = $( '.js-toolset-forms-styling .js-cred-settings-wrapper input' ).serialize();

	$( document ).on( 'change', ['.js-cred-legacy-styling-setting', '.js-cred-bootstrap-styling-setting'], function() {
		if ( self.cred_styling_state != $( '.js-toolset-forms-styling .js-cred-settings-wrapper input' ).serialize() ) {
			self.cred_styling_options_debounce_update();
		}

		if($('.js-cred-bootstrap-styling-setting').is(':checked')){
			$('.js-cred-legacy-styling-setting').attr('checked', false).attr('disabled', 'disabled');
		}else{
            $('.js-cred-legacy-styling-setting').attr('disabled', false);
		}
	});

    $( document ).on( 'change', '.js-cred-legacy-styling-setting', function() {
        if ( self.cred_styling_state != $( '.js-toolset-forms-styling .js-cred-settings-wrapper input' ).serialize() ) {
            self.cred_styling_options_debounce_update();
        }
    });

	self.save_cred_legacy_styling_options = function() {
        var is_checked = $( '.js-toolset-forms-styling .js-cred-settings-wrapper input' ).is(":checked"),
            nonce      = $( '#cred-styling-settings' ).val(),
            data       = $( '.js-toolset-forms-styling .js-cred-settings-wrapper input' ).serialize();

		self.save_settings_section( 'cred_save_styling_settings', data, nonce )
			.done( function( response ) {
				if ( response.success ) {
					self.cred_styling_state = $( '.js-toolset-forms-styling .js-cred-settings-wrapper input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});

	};

	self.cred_styling_options_debounce_update = _.debounce( self.save_cred_legacy_styling_options, 1000 );

	/**
	* Other
	*/

	self.cred_other_state = $( '.js-toolset-forms-other .js-cred-settings-wrapper input, .js-toolset-forms-other .js-cred-settings-wrapper select' ).serialize();

	$( document ).on( 'change', '.js-cred-other-setting-enable-post-expiration', function() {
		var thiz = $( this );
		if ( thiz.prop( 'checked' ) ) {
			$( '.js-cred-other-setting-enable-post-expiration-extra' ).slideDown();
		} else {
			$( '.js-cred-other-setting-enable-post-expiration-extra' ).slideUp();
		}
	});

	$( document ).on( 'change', '.js-toolset-forms-other input, .js-toolset-forms-other select', function() {
		if ( self.cred_other_state != $( '.js-toolset-forms-other .js-cred-settings-wrapper input, .js-toolset-forms-other .js-cred-settings-wrapper select' ).serialize() ) {
			self.cred_other_options_debounce_update();
		}
	});

	self.save_cred_other_options = function() {
		var data = $( '.js-toolset-forms-other .js-cred-settings-wrapper input, .js-toolset-forms-other .js-cred-settings-wrapper select' ).serialize(),
		nonce = $( '#cred-other-settings' ).val();
		self.save_settings_section( 'cred_save_other_settings', data, nonce )
			.done( function( response ) {
				if ( response.success ) {
				self.cred_other_state = $( '.js-toolset-forms-other .js-cred-settings-wrapper input, .js-toolset-forms-other .js-cred-settings-wrapper select' ).serialize();
				$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});

	};

	self.cred_other_options_debounce_update = _.debounce( self.save_cred_other_options, 1000 );

	/**
	* Recaptcha
	*/

	self.cred_recaptcha_state = $( '.js-toolset-forms-recaptcha .js-cred-settings-wrapper input' ).serialize();

	$( document ).on( 'change cut click paste keyup', '.js-cred-recaptcha-setting', function() {
		if ( self.cred_recaptcha_state != $( '.js-toolset-forms-recaptcha .js-cred-settings-wrapper input' ).serialize() ) {
			self.cred_recaptcha_options_debounce_update();
		}
	});

	self.save_cred_recaptcha_options = function() {
		var data = $( '.js-toolset-forms-recaptcha .js-cred-settings-wrapper input' ).serialize(),
		nonce = $( '#cred-recaptcha-settings' ).val();
		self.save_settings_section( 'cred_save_recaptcha_settings', data, nonce )
			.done( function( response ) {
				if ( response.success ) {
					self.cred_recaptcha_state = $( '.js-toolset-forms-recaptcha .js-cred-settings-wrapper input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});

	};

	self.cred_recaptcha_options_debounce_update = _.debounce( self.save_cred_recaptcha_options, 1000 );

	/**
	* Post drafts
	*/

	$( document ).on( 'change click keyup', '#cred_post_drafts_cron_schedule', function () {
		if ( $( '#cred_post_drafts_cron_schedule' ).val() === 'never' ) {
			$( '#cred_post_drafts_cron_threshold_row' ).hide();
		} else {
			$( '#cred_post_drafts_cron_threshold_row' ).show();
		}
	});

	self.cred_post_drafts_state = $( '.js-toolset-forms-drafts select.js-cred-post-drafts-setting' ).serialize();

	$( document ).on( 'change click keyup', '.js-cred-post-drafts-setting', function() {
		if ( self.cred_post_drafts_state != $( '.js-toolset-forms-drafts select.js-cred-post-drafts-setting' ).serialize() ) {
			self.cred_post_drafts_options_debounce_update();
		}
	});

	self.save_cred_post_drafts_options = function() {
		var data = $( '.js-toolset-forms-drafts select.js-cred-post-drafts-setting' ).serialize(),
		nonce = $( '#cred-post-drafts-settings' ).val();
		self.save_settings_section( 'cred_save_post_drafts_settings', data, nonce )
			.done( function( response ) {
				if ( response.success ) {
					self.cred_post_drafts_state = $( '.js-toolset-forms-drafts select.js-cred-post-drafts-setting' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});

	};

	self.cred_post_drafts_options_debounce_update = _.debounce( self.save_cred_post_drafts_options, 1000 );

	/**
	* Helper method for saving settings
	*/

	self.save_settings_section = function( save_action, save_data, save_nonce ) {
		var data = {
			action:			save_action,
			settings:		save_data,
			wpnonce:		save_nonce
		};
		$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );
		return $.ajax({
			url: ajaxurl,
			data: data,
			type: "POST",
			dataType:"json"
		});
	};

	self.init = function() {
		self.init_dialogs();
	};

	self.init();

};

jQuery( function( $ ) {
    Toolset_CRED.settings_screen = new Toolset_CRED.SettingsScreen( $ );
});
