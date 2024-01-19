var wpcfAccess = wpcfAccess || {};
var OTGAccess = OTGAccess || {};

/**
* OTGAccess.AccessSettings
*
* @since 2.0
* @fix 2.2
*/

OTGAccess.AccessSettings = function( $ ) {

	// @todo add proper mesage management

	var self = this;
	var myHistory = [];
	self.spinner = '<span class="wpcf-loading ajax-loader js-otg-access-spinner"></span>';
	self.section_status = '';

	self.spinner_placeholder = $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="otg-access-spinner"><i class="fa fa-refresh fa-spin"></i></div>' +
		'</div>' +
		'</div>'
	);

	/*
    * Disable / Enable dialog button
     */
    self.toolset_access_disable_dialog_button = function( state ){
        $('.js-wpcf-access-process-button').show();
		if ( state == 'enable' ){
			$('.js-wpcf-access-process-button')
                    .addClass('button-primary')
                    .removeClass('button-secondary')
                    .prop('disabled', false);
		}else{
			$('.js-wpcf-access-process-button')
                    .addClass('button-secondary')
                    .removeClass('button-primary')
                    .prop('disabled', true);
		}
	}

	self.glow_container = function( container, reason ) {
		$( container ).addClass( reason );
		setTimeout( function () {
			$( container ).removeClass( reason );
		}, 500 );
	};

	self.setButtonText = function( $button, $text ) {
		if ( $button.find( '.ui-button-text' ).length > 0 ) {
			$( '.ui-button-text', $button ).html( $text );
		} else {
			$button.html( $text );
		}
	};

	/**
	* Tab management
	*
	* @since 2.0
	*/

	$( document ).on( 'click', '.js-otg-access-nav-tab', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		target = thiz.data( 'target' ),
		current = $( '.js-otg-access-nav-tab.nav-tab-active' ).data( 'target' );

		if ( ! thiz.hasClass( 'nav-tab-active' ) ) {
			myHistory.push("page_im_on_now");
    		window.history.replaceState(myHistory, "", $(this).attr('href'));
			$( '.js-otg-access-nav-tab.nav-tab-active' ).removeClass( 'nav-tab-active' );
			if ( $( '.js-otg-access-content .js-otg-access-settings-section-for-' + target ).length > 0 ) {
				$( '.js-otg-access-content .js-otg-access-settings-section-for-' + current ).fadeOut( 'fast', function() {
					thiz.addClass( 'nav-tab-active' );
					$( '.js-otg-access-content .js-otg-access-settings-section-for-' + target ).fadeIn( 'fast' );
				});
			} else {
				if ( ! thiz.hasClass( 'js-otg-access-nav-tab-loading' ) ) {
					$( '.js-otg-access-content .js-otg-access-settings-section-for-' + current ).fadeOut( 'fast' );
					$( '.js-otg-access-content .js-otg-access-settings-section-loading' ).fadeIn( 'fast' );
					$( '.js-otg-access-nav-tab' ).addClass( 'js-otg-access-nav-tab-loading' );
					var data = {
						action : 'access_load_permissions_table',
						section : target,
						wpnonce : jQuery('#wpcf-access-error-pages').attr('value')
					},
					data_for_events = {
						section: target
					};
					jQuery.ajax({
						url:		ajaxurl,
						type:		'POST',
						dataType:	"json",
						data:		data,
						success: 	function( response ) {
							if ( response.success ) {
								thiz.addClass( 'nav-tab-active' );
								$( '.js-otg-access-content .js-otg-access-settings-section-loading' ).fadeOut( 'fast', function() {
									jQuery( '.js-otg-access-content' ).append( response.data.output );
									toolset_access_fix_cred_permission_tables();
									jQuery( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
                                    window.document.dispatchEvent(new Event("DOMContentLoaded", {
                                        bubbles: true,
                                        cancelable: true
                                    }));
                                });
							}

						},
						complete:	function( object, status ) {
							$( '.js-otg-access-nav-tab' ).removeClass( 'js-otg-access-nav-tab-loading' );
						}
					});
				}
			}
		}
	});

	/**
	 * Make permission table scrollable and the first column sticky for CRED permission table
	 * when more than 8 CRED forms
	 */
	function toolset_access_fix_cred_permission_tables(){
		$.each( $( '.wpcf-access-mode' ), function( index, value ) {
			var areaElement = $(this);
			var credPermissionTable = areaElement.find( 'table.fixed' );
			if ( areaElement.find( '.toolset-access-specific-users-row td' ).length > 8 ){
				credPermissionTable.css( { 'table-layout' : 'auto' } );
				credPermissionTable.find( 'td' ).css( { 'min-width' : '125px' } );
			}
		});
	}

	toolset_access_fix_cred_permission_tables();

	$( document ).on( 'click', '.js-otg-access-manual-tab', function( e ) {
		e.preventDefault();
		var target = $( this ).data( 'target' ),
		target_tab = $( '.js-otg-access-nav-tab[data-target=' + target + ']' );
		target_tab.trigger( 'click' );
	});

	/**
	* load_permission_tables
	*
	* Load a tab content, mainly used when reloading a tab after some create/modify event.
	*
	* @param string		section
	*
	* @since unknown
	* @since 2.1		Renamed from otg_access_load_permission_tables and moved to a module method
	*/

	self.load_permission_tables = function( section ) {
		var data = {
			action: 'access_load_permissions_table',
			section: section,
			wpnonce: $('#wpcf-access-error-pages').attr('value')
		},
		data_for_events = {
			section: section
		};
		$.ajax({
			url:		ajaxurl,
			type:		'POST',
			dataType:	"json",
			data:		data,
			success: 	function( response ) {
				if ( response.success ) {
					$('.js-otg-access-content .js-otg-access-settings-section-for-' + section).replaceWith( response.data.output );
					$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
				}
			}
		});
	}

	/**
	* Invalidate tabs
	*
	* @since 2.1
	*/

	self.available_tabs = $( '.js-otg-access-nav-tab' ).map( function() {
		return $( this ).data( 'target' );
	}).get();

	$( document ).on( 'js_event_otg_access_settings_section_saved', function( event, section, tab ) {
		var tabs_to_invalidate = [];
		switch ( tab ) {
			case 'custom-roles':
				// This never happens as roles are saved in a different way, but leave for consistency
				tabs_to_invalidate = _.without( self.available_tabs, 'custom-roles' );
				break;
			case 'custom-group':
				tabs_to_invalidate.push( 'post-type' );
				break;
		}
		self.invalidate_tabs( tabs_to_invalidate );
	});

	$( document ).on( 'js_event_types_access_custom_group_updated js_event_types_access_wpml_group_updated', function() {
		var tabs_to_invalidate = [];
		tabs_to_invalidate.push( 'post-type' );
		self.invalidate_tabs( tabs_to_invalidate );
	});

	$( document ).on( 'js_event_types_access_custom_roles_updated', function() {
		var tabs_to_invalidate = _.without( self.available_tabs, 'custom-roles' );
		self.invalidate_tabs( tabs_to_invalidate );
	});

	self.invalidate_tabs = function( tabs ) {
		$.each( tabs, function( index, value ) {
			$( '.js-otg-access-content .js-otg-access-settings-section-for-' + value ).remove();
		});
	};

	/**
	* Sections toggle
	*
	* @since 2.0
	*/

	$( document ).on( 'click', '.js-otg-access-settings-section-item-toggle', function() {
		var thiz = $( this ),
		target = thiz.data( 'target' );
		thiz.find( '.js-otg-access-settings-section-item-managed' ).toggle();
		var status = 1;
		if ( $( '.js-otg-access-settings-section-item-toggle-target-' + target ).css('display') == 'block' ){
			status = 0;
		}
		$( '.js-otg-access-settings-section-item-toggle-target-' + target ).slideToggle();
		toolset_access_save_section_status( target, status );
	});

	var toolset_access_save_section_status = function( target, status ){
		var data = {
			action:		'access_save_section_status',
			target:		target,
			status:	status,
			wpnonce:	wpcf_access_dialog_texts.otg_access_general_nonce,
		};

		$.ajax({
            url:		ajaxurl,
            type:		'POST',
            dataType:	'json',
            data:		data,
            success:	function( response ) {}
        });
	}

	/**
	* Save settings section
	*
	* @since 2.0
	*/

	$( document ).on( 'click', '.js-otg-access-settings-section-save', function( e ) {
        e.preventDefault();
		var thiz			= $( this );
		thiz_section		= thiz.closest( '.js-otg-access-settings-section-item' ),
		thiz_tab			= thiz.closest( '.js-otg-access-settings-tab-section' ).data( 'tab' ),
		spinnerContainer	= $( self.spinner ).insertBefore( thiz ).show();
        $( '#wpcf_access_admin_form' )
			.find('.dep-message')
			.hide();
        $.ajax({
            url:		ajaxurl,
            type:		'POST',
            dataType:	'json',
            data:		thiz_section.find('input').serialize()
						+ '&wpnonce=' + $('#otg-access-edit-sections').val()
						+ '&_wp_http_referer=' + $('input[name=_wp_http_referer]').val()
						+ '&action=access_save_settings',
            success:	function( response ) {
				var container = thiz.closest('.wpcf-access-type-item');
				var message_container = container.find('.dep-message');
				message_container.html('').hide();
				if ( response.success ) {
					if ( '' != response.data.message ) {
						container.find( '.js-wpcf-follow-parent' ).prop( 'checked', false );
						container.find( '.js-wpcf-enable-access' ).click();
						message_container.html( response.data.message ).show();
					}
					$( document ).trigger( 'js_event_otg_access_settings_section_saved', [ thiz_section, thiz_tab ] );
				}
            },
			complete: function() {
				spinnerContainer.remove();
			}
        });
        return false;
    });

	$( document ).on( 'js_event_otg_access_settings_section_saved', function( event, section, tab ) {
		self.glow_container( section, 'otg-access-settings-section-item-saved' );
		var has_enable = section.find( '.js-wpcf-enable-access' );
		if ( has_enable.length > 0 ) {
			var is_enabled = has_enable.prop( 'checked' );
			if ( is_enabled ) {
				section
					.removeClass( 'otg-access-settings-section-item-not-managed' )
					.find( '.js-otg-access-settings-section-item-managed' )
						.text( wpcf_access_dialog_texts.otg_access_managed );
			} else {
				section
					.addClass( 'otg-access-settings-section-item-not-managed' )
					.find( '.js-otg-access-settings-section-item-managed' )
						.text( wpcf_access_dialog_texts.otg_access_not_managed );
			}
		}
	});

	/**
	* Custom Roles management
	*
	* @since 2.0
	*/

	$( document ).on( 'click', '.js-otg-access-add-new-role', function( e ) {
		e.preventDefault();

		$access_dialog_open(500);

		self.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
		self.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_ok );

		$('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.toolset_access_add_role);

		OTGAccess.access_settings.access_control_dialog.html(  $( '.js-otg-access-new-role-wrap' ).html() );
		$('.js-toolset-access-dialog').find( '.js-otg-access-new-role-name' ).val('').focus();
		$('.js-toolset-access-dialog').find( '.js-otg-access-new-role-extra' ).fadeIn( 'fast' );
		OTGAccess.access_settings.dialog_callback = $process_add_new_role;
    });

	/*
	* Process new custom role
	 */
	$process_add_new_role = function( ){

		var thiz = $('.js-wpcf-access-process-button'),
		data = {
			action:		'access_add_role',
			role:		$( '.js-toolset-access-dialog .js-otg-access-new-role-name' ).val(),
			copy_of:	$( '.js-toolset-access-dialog .js-toolset-access-copy-caps-from' ).val(),
			wpnonce:	wpcf_access_dialog_texts.otg_access_general_nonce,
		},
		data_for_events = {
			section: 'custom-roles'
		};

        OTGAccess.access_settings.toolset_access_disable_dialog_button();

        $( '.js-toolset-access-dialog .js-otg-access-message-container' ).html( '' );

		$.ajax({
            url:		ajaxurl,
            type:		'POST',
            dataType:	'json',
            data:		data,
            success:	function( response ) {
				if ( response.success ) {
					$( '.js-toolset-access-dialog .js-otg-access-new-role-name' ).val('');
					$( '.js-otg-access-settings-section-for-custom-roles' ).replaceWith( response.data.message );
					$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
					$( document ).trigger( 'js_event_types_access_custom_roles_updated' );
					OTGAccess.access_settings.load_permission_tables( 'custom-roles' );
				} else {
					$( '.js-toolset-access-dialog .js-otg-access-message-container' ).html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + response.data.message + '</p>');

				}
            },
			complete: function() {
				$( '.js-toolset-access-dialog .wpcf-loading').remove();
			}
        });
	}

    $( document ).on( 'keyup', '#js-wpcf-access-dialog-container .js-otg-access-new-role-name', function() {
        $( '.js-otg-access-new-role-wrap .js-otg-access-message-container' ).html( '' );
        if ( $(this).val().length > 4 ) {
            OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
        } else {
          	OTGAccess.access_settings.toolset_access_disable_dialog_button();
        }
    });



    // DELETE ROLE - NOT SURE WHERE THIS IS USED ???
    $( document ).on( 'click', '#wpcf-access-delete-role', function() {
        $(this).next().show();
    });

	/**
	* Initialize some data on tab load, like administrators checkboxes and taxonomies special inputs.
	*
	* @since 2.0
	*/

	self.init_inputs = function( container ) {
		// ADD DEPENDENCY MESSAGE - to review
		$( '.wpcf-access-type-item', container )
				.find('.wpcf-access-mode')
				.prepend('<div class="dep-message toolset-alert toolset-alert-info toolset-access-table-notification hidden"></div>');

		// Disable admin checkboxes
		$( 'input:checkbox[value="administrator"], .js-toolset-access-cred-user-forms-guests', container )
				.prop('disabled', true)
				.prop('readonly', true)
				.prop('checked', false);

		$( 'input:checkbox[value="administrator"]', container ).prop('checked', true);


		// Initialize  "same as parent" checkboxes properties
		$.each( $( '.js-wpcf-follow-parent', container ), function() {
			var $manageByAccessCheckbox = $(this)
						.closest('.js-wpcf-access-type-item')
						.find('.js-wpcf-enable-access');

			if ( ! $manageByAccessCheckbox.is(':checked') ) {
				$(this)
					.prop('disabled', true)
					.prop('readonly', true);
			}


			var $container = $(this).closest('.js-wpcf-access-type-item');
			var checked = $(this).is(':checked');
			var $tableInputs = $container.find('table :checkbox, table input[type=text]');

			$tableInputs = $tableInputs.filter(function() { // All elements except 'administrator' role checkboxes
				return ( $(this).val() !== 'administrator' );
			});
			if ( checked) {
				$container.find('.js-toolset-access-specific-user-link').addClass('js-toolset-access-specific-user-disabled');
				wpcfAccess.DisableTableInputs($tableInputs, $container);
				$container.find('.js-wpcf-access-reset').prop('disabled', true);
			}
		});
	};

	$( document ).on( 'js_event_types_access_permission_table_loaded', function( event, data ) {
		self.init_inputs( $( '.js-otg-access-settings-section-for-' + data.section ) );
		if ( self.access_control_dialog.dialog( 'isOpen' ) === true ) {
			self.access_control_dialog.dialog('close');
		}
	});

	/**
	* init_dialogs
	*
	* Init the Access Control page dialogs.
	*
	* @since 2.1
	*/

	self.init_dialogs = function() {
		$('body').append('<div id="js-wpcf-access-dialog-container" class="toolset-shortcode-gui-dialog-container wpcf-access-dialog-container js-wpcf-access-dialog-container"></div>');
		self.dialog_callback = '';
		self.dialog_callback_params = [];
		self.access_control_dialog = $("#js-wpcf-access-dialog-container").dialog({
			dialogClass   : 'toolset-ui-dialog js-toolset-access-dialog',
			autoOpen:	false,
			modal:		true,
			minWidth:	450,
			show: {
				effect:		"blind",
				duration:	800
			},
			open:		function( event, ui ) {
				$('body').addClass('modal-open');
				$('.js-wpcf-access-process-button ')
						.addClass('button-secondary')
						.removeClass('button-primary ui-button-disabled ui-state-disabled')
						.prop('disabled', true)
						.css({'marginLeft': '15px', 'marginRight': '0px', 'display': 'inline', 'float': 'right'});
				$('.js-wpcf-access-gui-close').css('display', 'inline');
				$('.js-wpcf-access-process-button').removeClass('js-wpcf-access-process-button-red');
				$('.js-otg-access-spinner').remove();
			},
			close:		function( event, ui ) {
				$('body').removeClass('modal-open');
				$('.js-otg-access-spinner').remove();
			},
			buttons: [
				{
					class: 'button-secondary js-wpcf-access-gui-close wpcf-access-gui-close ',
					text: wpcf_access_dialog_texts.wpcf_close,
					click: function () {
						$(this).dialog("close");
					}
				},
				{
					class: 'button-primary js-wpcf-access-process-button',
					text: '',
					click: function () {
						if ( self.dialog_callback != '' ) {
							self.dialog_callback.call( null, self.dialog_callback_params );
							$( self.spinner ).insertBefore( $( '.js-wpcf-access-process-button' ) ).show();
						}
					}
				}
			]
		});
	};

	self.init = function() {
		self.init_inputs( $( '.js-otg-access-content' ) );
		self.init_dialogs();
    };

	self.init();

};

jQuery( function( $ ) {
    OTGAccess.access_settings = new OTGAccess.AccessSettings( $ );
});


(function (window, $, undefined) {


    $(function () {

        $(document).on('mouseover', '.otg-access-nav-caret', function (e) {
            $(this).parent().find('.otg-access-nav-submenu').show();
        });
        $(document).on('mouseout', '.otg-access-nav-caret', function (e) {
            $(this).parent().find('.otg-access-nav-submenu').hide();
        });

		// We do not use colorbox here, we need to review, deprecate, remove dependency and call it a day.
        $(document).on('click', '.js-dialog-close', function (e) {
            e.preventDefault();
            $.colorbox.close();
        });

        // Show tooltips
        $('.js-tooltip').hover(function () {
            var $this = $(this);
            var $tooltip = $('<div class="tooltip">' + $this.text() + '</div>');


            if ($this.children().outerWidth() < $this.children()[0].scrollWidth) {
                $tooltip
                        .appendTo($this)
                        .css({
                            'visibility': 'visible',
                            'left': -1 * ($tooltip.outerWidth() / 2) + $this.width() / 2
                        })
                        .hide()
                        .fadeIn('600');
            }
            ;
        }, function () {
            $(this)
                    .find('.tooltip')
                    .remove();
        });

        // Count table columns
        $.each($('.js-access-table'), function () {
            var columns = $(this).find('th').length;
            $(this).addClass('columns-' + columns);
        });

        //Enabled Access advanced mode
        $(document).on('click', '.js-otg_access_enable_advanced_mode', function (e) {
            e.preventDefault();

			$access_dialog_open(500);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_close );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_ok );

            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_advanced_mode);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            OTGAccess.access_settings.dialog_callback = $confirm_advaced_mode;
            OTGAccess.access_settings.dialog_callback_params[''] = '';
			var notification_message = '<p>'+wpcf_access_dialog_texts.wpcf_advanced_mode3 + '</p><p><strong>' + wpcf_access_dialog_texts.wpcf_advanced_mode2 + '</strong></p>';
            if ($(this).data('status') === false) {
				notification_message = '<p>'+wpcf_access_dialog_texts.wpcf_advanced_mode1 + '</p><p><strong>' + wpcf_access_dialog_texts.wpcf_advanced_mode2 + '</strong></p>';
            }
            var output = '<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>'+
					'<div class="toolset-access-alarm-wrap-right">'+ notification_message +'</div>';
			OTGAccess.access_settings.access_control_dialog.html(output);
            OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
        });

        $confirm_advaced_mode = function (params) {
            var data = {
                action:		'access_enable_advanced_mode',
                wpnonce:	wpcf_access_dialog_texts.otg_access_general_nonce
            },
            data_for_events = {
                section: 'custom-roles'
            };
            $.ajax({
                url:		ajaxurl,
                type:		'POST',
                dataType:	'json',
                data:		data,
                success:	function( response ) {
                    OTGAccess.access_settings.access_control_dialog.dialog('close');
                    $( '.js-otg-access-settings-section-for-custom-roles' ).replaceWith( response.data.message );
                    $( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
                    }
                });
        };

        /**
         * Confirmation dialog for delete role action
         */
        $(document).on('click', '.js-wpcf-access-delete-role', function (e) {
            e.preventDefault();

			$access_dialog_open(500);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_delete_role );

            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_delete_role);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_delete_role_form',
                role: $(this).data('role'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $confirm_remove_role;
            OTGAccess.access_settings.dialog_callback_params['role'] = $(this).data('role');
            $('.js-wpcf-access-process-button').addClass('js-wpcf-access-process-button-red');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
                }
            });
        });

        $confirm_remove_role =  function( params ) {
            var role = params['role'],
            data = {
                action:						'access_delete_role',
                wpcf_access_delete_role:	role,
                wpcf_reassign:				$('[name="wpcf_reassign"]').val(),
                wpnonce:					wpcf_access_dialog_texts.otg_access_general_nonce
            },
            data_for_events = {
                section: 'custom-roles'
            };
            $.ajax({
                url:		ajaxurl,
                type:		'POST',
                dataType:	'json',
                data:		data,
                success:	function( response ) {
                    if ( response.success ) {
                        OTGAccess.access_settings.load_permission_tables( 'custom-roles' );
                        OTGAccess.access_settings.access_control_dialog.dialog('close');
                        $( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
                        $( document ).trigger( 'js_event_types_access_custom_roles_updated' );
                    }
                }
            });
        };

        $(document).on('click', '.js-wpcf-access-import-button', function (e) {
            $('.toolset-alert').remove();
            if ($('.js-wpcf-access-import-file').val() === '') {
                $('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">' + $(this).data('error') + '</p>').insertAfter(".js-wpcf-access-import-button")
                return false;
            } else {
                return true;
            }
        });

        $(document).on('change', '.js-wpcf-access-import-file', function (e) {
            $('.toolset-alert').remove();
        });

        //Show Role caps (read only)
        $(document).on('click', '.wpcf-access-view-caps', function (e) {
            e.preventDefault();

            $access_dialog_open(400);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_close );
            $('.js-wpcf-access-process-button').css('display', 'none');
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_role_permissions);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_show_role_caps',
                role: $(this).data('role'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = '';
            OTGAccess.access_settings.dialog_callback_params = [];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);

                }
            });
        });

        $access_dialog_open = function (width) {
            var dialog_height = $(window).height() - 100;
            OTGAccess.access_settings.access_control_dialog.dialog('open').dialog({
                title: wpcf_access_dialog_texts.wpcf_change_perms,
                width: width,
                maxHeight: dialog_height,
                draggable: false,
                resizable: false,
                position: {my: "center top+50", at: "center top", of: window}
            });
        }

        //Show popup: change custom role permissions
        $(document).on('click', '.wpcf-access-change-caps', function (e) {
            e.preventDefault();

			$access_dialog_open(800);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_change_perms );

            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_change_perms);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_change_role_caps_form',
                role: $(this).data('role'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $role_caps_process;
            OTGAccess.access_settings.dialog_callback_params = [];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
				$('.js-otg-access-change-role-caps-tabs')
					.tabs({
						active: 0
					})
					.addClass('ui-tabs-vertical ui-helper-clearfix')
					.removeClass('ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
                }
            });
        });


        //Process: change custom role permissions
        $role_caps_process = function () {
            var caps = [];
            if (typeof $('input[name="assigned-posts"]') !== 'undefined') {
                $('input[name="current_role_caps[]"]:checked').each(function () {
                    caps.push($(this).val());
                });
            }
            var data = {
                action: 'access_change_role_caps_process',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                role: $('.js-wpcf-current-edit-role').val(),
                caps: caps
            };
            $('.js-wpcf-access-role-caps-process').prop('disabled', true);
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    OTGAccess.access_settings.access_control_dialog.dialog('close');
                }
            });

            return false;
        };


        //Open for for new custom cap
        $(document).on('click', '.js-wpcf-access-add-custom-cap', function () {
            $(this).hide();
            $('.js-wpcf-create-new-cap-form').show();
            $('#js-wpcf-new-cap-slug').focus();

            return false;
        });

        $(document).on('input', '#js-wpcf-new-cap-slug', function () {
            $('.js-wpcf-new-cap-add').prop('disabled', true).removeClass('button-primary');
            $('.toolset-alert').remove();
            if ($(this).val() !== '') {
                $('.js-wpcf-new-cap-add').prop('disabled', false).addClass('button-primary');
            }
        });

        $(document).on('click', '.js-wpcf-new-cap-cancel', function () {
            $('.js-wpcf-access-add-custom-cap').show();
            $('.js-wpcf-create-new-cap-form').hide();
            return false;
        });

        $(document).on('click', '.js-wpcf-remove-custom-cap a, .js-wpcf-remove-cap-anyway', function () {
            var div = $(this).data('object');
            var cap = $(this).data('cap');
            var remove = $(this).data('remove');
            var $thiz = $(this);
            var ajaxSpinner = $(this).parent().find('.spinner');
            ajaxSpinner.css('visibility', 'visible');
            var data = {
                action: 'access_remove_custom_cap',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                cap_name: cap,
                remove_div: div,
                remove: remove,
                edit_role: $('.js-wpcf-current-edit-role').val()
            };
            $thiz.hide();
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                	var data = response.data;
                    ajaxSpinner.css('visibility', 'hidden');
                    if (data == 1) {
                        $('#wpcf-custom-cap-' + cap).remove();
                        if ($('.js-wpcf-remove-custom-cap').length == 0) {
                            $('.js-wpcf-no-custom-caps').show();
                        }
                    } else {
                        $(data).insertAfter($thiz);
                    }

                }
            });
            return false;
        });

        $(document).on('click', '.js-wpcf-remove-cap-cancel', function () {
            $('.js-wpcf-remove-custom-cap_' + $(this).data('cap')).find('a').show();
            $('.js-removediv_' + $(this).data('cap')).remove();
            return false;
        });



        $(document).on('click', '.js-wpcf-new-cap-add', function (e) {
            var test_cap_name = /^[a-z0-9_-]*$/.test($('#js-wpcf-new-cap-slug').val());
            $('.js-wpcf-create-new-cap-form').find('.toolset-alert').remove();
            if (test_cap_name === false) {
                $('.js-wpcf-create-new-cap-form').append('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">' + $(this).data('error') + '</p>');
                return false;
            }

            var ajaxSpinner = $('.js-new-cap-spinner');
            ajaxSpinner.css('visibility', 'visible');
            var data = {
                action: 'access_add_new_cap',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                cap_name: $('#js-wpcf-new-cap-slug').val(),
                cap_description: $('#js-wpcf-new-cap-description').val()
            };
            $('.js-wpcf-new-cap-add').prop('disabled', true).removeClass('button-primary');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                dataType: 'json',
                success: function (response) {
                    ajaxSpinner.css('visibility', 'hidden');
					var data = response.data;
                    if (data[0] == 1) {
                        $('.js-wpcf-list-custom-caps').append(data[1]);
                        $('#js-wpcf-new-cap-slug,#js-wpcf-new-cap-description').val('');
                        $('.js-wpcf-access-add-custom-cap').show();
                        $('.js-wpcf-create-new-cap-form, .js-wpcf-no-custom-caps').hide();

                    } else {
                        $('.js-wpcf-create-new-cap-form').append('<p class="toolset-alert toolset-alert-error" style="display: block; opacity: 1;">' + data[1] + '</p>');
                    }
                }
            });
            return false;
        });





        $disable_languages = function () {
            var post_type = $('#wpcf-wpml-group-post-type').val(),
				isDisabled = $('#wpcf-access-wpml-group-disabled').val();
            if ( isDisabled == 1 ) {
                $('.js-wpcf-access-process-button').hide();
			} else {
                languages = JSON.parse($('#wpcf-wpml-group-disabled-languages').val());
                $('input[name="group_language_list"]').prop('disabled', false);
                if (typeof languages[post_type] !== 'undefined') {
                    $('input[name="group_language_list"]').each(function () {
                        if (languages[post_type][$(this).val()] == 1) {
                            $(this).prop('disabled', true);
                        } else {
                            $(this).prop('disabled', false);
                        }
                    });
                }
            }
        }

        $(document).on('change', '#wpcf-wpml-group-post-type', function (e) {
            $disable_languages();
        });

        //Create WPML group
        $(document).on('click', '.js-wpcf-add-new-wpml-group', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_save );

            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_add_wpml_settings);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );
            var group_id = '',
                    group_div_id = '';
            if (typeof $(this).data('group') !== 'undefined') {
                group_id = $(this).data('group');
                group_div_id = $(this).data('groupdiv');
				$('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_set_wpml_settings);
				OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_modify_group );
            }
            var data = {
                action: 'access_add_wpml_group_form',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                group_id: group_id,
                group_div_id: group_div_id
            };

            OTGAccess.access_settings.dialog_callback = $save_wpml_group;
            OTGAccess.access_settings.dialog_callback_params['divid'] = group_div_id;

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
                    check_errors_form();
                    $disable_languages();
                    if (data.group_id === '') {
                        OTGAccess.access_settings.toolset_access_disable_dialog_button();
                    }
                }
            });

        });



        $save_wpml_group = function (params) {

            var data = {
                action: 'access_add_wpml_group_process',
                //group_name : $('#wpcf-access-new-wpml-group-title').val(),
                group_nice: $('#wpcf-access-wpml-group-nice').val(),
                group_id: $('#wpcf-access-group-id').val(),
                languages: $('input[name="group_language_list"]').serializeArray(),
                form_action: $('#wpcf-access-group-action').val(),
                post_type: $('#wpcf-wpml-group-post-type').val(),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };
            OTGAccess.access_settings.toolset_access_disable_dialog_button();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                	var data = response.data;
                    if (data != 'error') {
                        if ($('#wpcf-access-group-action').val() == 'add') {
							OTGAccess.access_settings.load_permission_tables( 'wpml-group' );
							$( document ).trigger( 'js_event_types_access_wpml_group_updated' );
                        } else {
                            $('#js-box-' + params['divid'])
                                .find('h4')
                                    .html(data);
							OTGAccess.access_settings.access_control_dialog.dialog('close');
                        }
                    } else {
                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        $('.js-otg-access-spinner').remove();
                        OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
                    }
                }
            });


        };


        $(document).on('change', 'input[name="group_language_list"]', function () {
            if (jQuery('input[name="group_language_list"]:checked').length > 0) {
                OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
            } else {
                OTGAccess.access_settings.toolset_access_disable_dialog_button();
            }
        });

        $(document).on('change', '#wpcf-wpml-group-post-type', function () {
            jQuery('input[name="group_language_list"]').prop('checked', false);
            OTGAccess.access_settings.toolset_access_disable_dialog_button();
        });

		/**
		 * open custom error preview single post tab
		 */
		$( document ).on( 'click', '.js-toolset-access-preview-single', function () {

			var url = $( this ).data('url'),
				post_type = $( this ).data('posttype'),
				access_preview = 'single',
				error_type = $('input[name="error_type"]:checked').val(),
				role = $( this ).data('role'),
				id = '';

			switch ( error_type ) {
				case 'error_layouts':
					id = $('select[name="wpcf-access-layouts"]').val();
					break;
				case 'error_ct':
					id = $('select[name="wpcf-access-ct"]').val();
					break;
				case 'error_php':
					id = $('select[name="wpcf-access-php"]').val();
					break;
			}

			if ( ( error_type == 'error_layouts' || error_type ==  'error_ct' || error_type ==  'error_php' ) && id == '' ){
				return;
			}

			url = url + '&access_preview=' + access_preview + '&access_preview_post_type=' + post_type + '&error_type=' + error_type
				  + '&role=' + role + '&id=' + id;

			var win = window.open( url, '_blank');
			win.focus();

		});

		/**
		 * Open archive custom error tab
		 */
		$( document ).on( 'click', '.js-toolset-access-preview-archive', function () {

			var url = $( this ).data('url'),
				post_type = $( this ).data('posttype'),
				access_preview = 'archive',
				error_type = $('input[name="archive_error_type"]:checked').val(),
				role = $( this ).data('role'),
				id = '';
			switch ( error_type ) {
				case 'error_layouts':
					id = $('select[name="wpcf-access-archive-layouts"]').val();
					break;
				case 'error_ct':
					id = $('select[name="wpcf-access-archive-ct"]').val();
					break;
				case 'error_php':
					id = $('select[name="wpcf-access-archive-php"]').val();
					break;
			}

			if ( ( error_type == 'error_layouts' || error_type ==  'error_ct' || error_type == 'error_php' ) && id == '' ){
				return;
			}

			url = url + '&toolset_access_preview=1&access_preview=' + access_preview + '&access_preview_post_type=' + post_type + '&error_type=' + error_type
				+ '&role=' + role + '&id=' + id;

			var win = window.open( url, '_blank');
			win.focus();

		});

        $(document).on('click', '.js-wpcf-add-error-page', function (e) {
            e.preventDefault();

            $access_dialog_open(500);
			var error_data = $(this).data('custom_error');
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_set_errors );
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html('');
            if( error_data['archive'] == 1 ){
            	$('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html( '');
			}

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );


            var data = {
                action: 'access_show_error_list',
                access_type: error_data['typename'],
                access_value: error_data['valuename'],
                cur_type: error_data['curtype'],
                cur_value: error_data['curvalue'],
                access_archivetype: error_data['archivetypename'],
                access_archivevalue: error_data['archivevaluename'],
                cur_archivetype: error_data['archivecurtype'],
                cur_archivevalue: encodeURIComponent(error_data['archivecurvalue']),
                posttype: error_data['posttype'],
                is_archive: error_data['archive'],
                forall: error_data['forall'],
				role: error_data['role'],
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $set_error_page;
            OTGAccess.access_settings.dialog_callback_params['id'] = [];
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
                    check_errors_form();
					OTGAccess.access_settings.access_control_dialog.dialog('widget').position({ my: "center top+50", at: "center top", of: window });
				}
            });

        });

        // 'Set error page' popup
        $set_error_page = function () {
            var text = valname = typename = archivevalname = archivetypename = '';

            typename = $('input[name="error_type"]:checked').val();
            archivetypename = $('input[name="archive_error_type"]:checked').val();

            if ($('input[name="error_type"]:checked').val() === 'error_php') {
                text = wpcf_access_dialog_texts.wpcf_php_template + ': ' + $( 'select[name="wpcf-access-php"] option:selected' ).text();
                valname = $('select[name="wpcf-access-php"]').val();
                link_error = wpcf_access_dialog_texts.wpcf_error3 + valname;

            } else if ($('input[name="error_type"]:checked').val() === 'error_ct') {
                text = wpcf_access_dialog_texts.wpcf_text_template + ': ' + $( 'select[name="wpcf-access-ct"] option:selected' ).text();
                valname = $('select[name="wpcf-access-ct"]').val();
                link_error = wpcf_access_dialog_texts.wpcf_error2 + $('select[name="wpcf-access-ct"] option:selected').text();
            } else if ( $( 'input[name="error_type"]:checked' ).val() === 'error_layouts' ) {
                text = wpcf_access_dialog_texts.wpcf_layout_template + ': ' + $( 'select[name="wpcf-access-layouts"] option:selected' ).text();
                valname = $( 'select[name="wpcf-access-layouts"]' ).val();
                link_error = wpcf_access_dialog_texts.wpcf_error4 + $( 'select[name="wpcf-access-layouts"] option:selected' ).text();
            } else if ($('input[name="error_type"]:checked').val() === 'error_404') {
                text = '404';
                link_error = wpcf_access_dialog_texts.wpcf_error1;
                archivetypename = '';
            } else {
                text = '';
                typename = '';
                link_error = '';
            }


            if ($('input[name="archive_error_type"]').val() !== "undefined") {
                if ($('input[name="archive_error_type"]:checked').val() === 'error_php') {
                    archivetext = wpcf_access_dialog_texts.wpcf_php_archive + ': ' + $( 'select[name="wpcf-access-archive-php"] option:selected' ).text();
                    archivevalname = $('select[name="wpcf-access-archive-php"]').val();
                    archivetypename = $('input[name="archive_error_type"]:checked').val();

                } else if ( $( 'input[name="archive_error_type"]:checked' ).val() === 'error_ct') {
                    archivetext = wpcf_access_dialog_texts.wpcf_view_archive + ': ' + $('select[name="wpcf-access-archive-ct"] option:selected').text();
                    archivevalname = $('select[name="wpcf-access-archive-ct"]').val();
                    archivetypename = $('input[name="archive_error_type"]:checked').val();
                } else if ( $( 'input[name="archive_error_type"]:checked' ).val() === 'error_layouts' ) {
                    archivetext = wpcf_access_dialog_texts.wpcf_layout_template_archive + ': ' + $( 'select[name="wpcf-access-archive-layouts"] option:selected' ).text();
                    archivevalname = $( 'select[name="wpcf-access-archive-layouts"]' ).val();
                    archivetypename = $( 'input[name="archive_error_type"]:checked' ).val();
                } else if ($('input[name="archive_error_type"]:checked').val() === 'default_error') {
                    archivetext = wpcf_access_dialog_texts.wpcf_no_posts_found;
                    archivevalname = '';
                    archivetypename = 'default_error';
                } else {
                    archivetext = '';
                    archivetypename = '';
                }
            }

			var error_link_data = $( 'input[name="' + $( 'input[name="typename"]' ).val() + '"]' ).parent().find('a').data('custom_error');
            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('.js-error-page-name').html(text);
			error_link_data['curtype'] = typename;
			error_link_data['curvalue'] = valname;
            $('input[name="' + $('input[name="valuename"]').val() + '"]').val(valname);
            $('input[name="' + $('input[name="typename"]').val() + '"]').val(typename);
            $('input[name="' + $('input[name="typename"]').val() + '"]').parent().find('.js-wpcf-add-error-page').attr("title", link_error);
            if ($('input[name="archive_error_type"]').val() !== "undefined") {
                $('input[name="' + $('input[name="archivetypename"]').val() + '"]').parent().find('.js-archive_error-page-name').html(archivetext);
                $('input[name="' + $('input[name="archivevaluename"]').val() + '"]').val(archivevalname);
                $('input[name="' + $('input[name="archivetypename"]').val() + '"]').val(archivetypename);
				error_link_data['archivecurtype'] = archivetypename;
				error_link_data['archivecurvalue'] = archivevalname;
            }
			$( 'input[name="' + $( 'input[name="typename"]' ).val() + '"]' ).parent().find('a').data( 'custom_error', error_link_data );
            OTGAccess.access_settings.access_control_dialog.dialog('close');
        };

        function check_errors_form() {

            $( 'select[name="wpcf-access-layouts"], select[name="wpcf-access-ct"], select[name="wpcf-access-php"]' ).hide();
            OTGAccess.access_settings.toolset_access_disable_dialog_button();
            var check_archive_error = false;
			$( '.js-toolset-access-preview-single, .js-toolset-access-preview-archive' ).prop( 'disabled' , true ).addClass('toolset-access-disabled-link');
            if ($('input[name="error_type"]:checked').val() == 'error_php') {
                $('select[name="wpcf-access-php"]').show();
                if ($('select[name="wpcf-access-php"]').val() !== '') {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button( 'enable' );
					$('.js-toolset-access-preview-single').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                    check_archive_error= true;
                }
            } else if ($('input[name="error_type"]:checked').val() == 'error_ct') {
                $('select[name="wpcf-access-ct"]').show();
                if ($('select[name="wpcf-access-ct"]').val() !== '') {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button( 'enable' );
					$('.js-toolset-access-preview-single').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                    check_archive_error= true;
                }
            } else if ( $( 'input[name="error_type"]:checked' ).val() == 'error_layouts' ) {
                $( 'select[name="wpcf-access-layouts"]' ).show();
                if ( $( 'select[name="wpcf-access-layouts"]' ).val() !== '' ) {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button( 'enable' );
					$('.js-toolset-access-preview-single').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                    check_archive_error= true;
                }
            } else {
                OTGAccess.access_settings.toolset_access_disable_dialog_button( 'enable' );
				$('.js-toolset-access-preview-single').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                check_archive_error= true;
            }

			if ( ! check_archive_error ){
            	return;
			}
            $( 'select[name="wpcf-access-archive-ct"], select[name="wpcf-access-archive-layouts"], select[name="wpcf-access-archive-php"], .js-wpcf-error-php-value-info, .js-wpcf-error-ct-value-info' ).hide();
            OTGAccess.access_settings.toolset_access_disable_dialog_button();

            if ($('input[name="archive_error_type"]:checked').val() == 'error_php') {
                $('select[name="wpcf-access-archive-php"], .js-wpcf-error-php-value-info').show();
                if ($('select[name="wpcf-access-archive-php"]').val() !== '') {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
					$('.js-toolset-access-preview-archive').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                }
            } else if ($('input[name="archive_error_type"]:checked').val() == 'error_ct') {
                $('select[name="wpcf-access-archive-ct"], .js-wpcf-error-ct-value-info').show();
                if ($('select[name="wpcf-access-archive-ct"]').val() !== '') {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
					$('.js-toolset-access-preview-archive').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                }
            } else if ( $( 'input[name="archive_error_type"]:checked' ).val() == 'error_layouts' ) {
                $( 'select[name="wpcf-access-archive-layouts"]' ).show();
                if ( $( 'select[name="wpcf-access-archive-layouts"]' ).val() !== '' ) {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
					$('.js-toolset-access-preview-archive').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                }
            } else {
            	OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
				$('.js-toolset-access-preview-archive').prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
            }
        }

        $(document).on('change', '.js-wpcf-access-type-archive', function () {
            check_errors_form();
        });

        $(document).on('change', '.js-wpcf-access-type', function () {
            check_errors_form();
        });

        $(document).on( 'change', 'select[name="wpcf-access-layouts"], select[name="wpcf-access-php"], select[name="wpcf-access-ct"]', function () {
            OTGAccess.access_settings.toolset_access_disable_dialog_button();
			$( '.js-toolset-access-preview-single' ).prop( 'disabled' , true ).addClass('toolset-access-disabled-link');
            if ( $(this).val() !== '' ) {
				$( '.js-toolset-access-preview-single' ).prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
                OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
            }
        });

		$(document).on( 'change', 'select[name="wpcf-access-archive-php"], select[name="wpcf-access-archive-ct"], select[name="wpcf-access-archive-layouts"]', function () {
			OTGAccess.access_settings.toolset_access_disable_dialog_button();
			$( '.js-toolset-access-preview-archive' ).prop( 'disabled' , true ).addClass('toolset-access-disabled-link');
			if ( $(this).val() !== '' ) {
				$( '.js-toolset-access-preview-archive' ).prop( 'disabled' , false ).removeClass('toolset-access-disabled-link');
				OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
			}
		});

        $(document).on('click', '.js-wpcf-search-posts', function () {

            $('.js-wpcf-search-posts').prop('disabled', true);
            var data = {
                action: 'access_search_posts',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                title: $('#wpcf-access-suggest-posts').val()
            };
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    $('.js-use-search').hide();
                    $('.js-wpcf-suggested-posts ul').html(response.data);
                    $('.js-wpcf-search-posts').prop('disabled', false);
                }
            });

            return false;
        });

        $(document).on('click', '.js-wpcf-search-posts-clear', function () {
            $('#wpcf-access-suggest-posts').val('');
            $('.js-wpcf-suggested-posts ul li').remove();

            return false;
        });

        // Add posts
        $(document).on('click', '.js-wpcf-add-post-to-group', function () {
            var li = '.js-assigned-access-post-' + $(this).data('id');

            if (typeof $(li).html() === 'undefined') {
                $('.js-no-posts-assigned').hide();
                $(".js-wpcf-assigned-posts ul").append('<li class="js-assigned-access-post-' + $(this).data('id') + '">' +
                        $(this).data('title') + ' <a href="" class="js-wpcf-unassign-access-post" data-id="' + $(this).data('id') + '">Remove</a>' +
                        '<input type="hidden" value="' + $(this).data('id') + '" name="assigned-posts[]"></li>');

                $(this)
                        .parent()
                        .remove();
            }

            if ($('.js-wpcf-suggested-posts ul').is(':empty')) {
                $('.js-use-search').fadeIn('fast');
            }

            return false;
        });

        // Remove posts
        $(document).on('click', '.js-wpcf-unassign-access-post', function () {
            var div = '.js-assigned-access-post-' + $(this).data('id');

            var data = {
                action: 'access_remove_assignment_post_group',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                id: $(this).data('id'),
                group_name: $('#wpcf-access-group-slug').val()
            };
            $(div).animate({ opacity:0}, 100, function() {$(div).remove();});
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                }
            });
            return false;
        });


        $(document).on('click', '.js-wpcf-remove-group', function (e) {
            e.preventDefault();

            $access_dialog_open(400);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_remove_group );
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_delete_group);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_remove_post_group_form',
                group_id: $(this).data('group'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $delete_group_process;
            OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('group');
            OTGAccess.access_settings.dialog_callback_params['divid'] = $(this).data('groupdiv');
			//OTGAccess.access_settings.dialog_callback_params['section'] = $(this).data('section');
			OTGAccess.access_settings.dialog_callback_params['target'] = $(this).data('target');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
                    $('.js-wpcf-access-process-button').addClass('js-wpcf-access-process-button-red');
                    if ($('.js-wpcf-assigned-posts ul').is(':empty')) {
                        $('.js-no-posts-assigned').show();
                    }
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');

                }
            });

        });

        $delete_group_process = function (params) {
            group_id = params['id'];
            var data = {
                action: 'access_remove_post_group_process',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                group_id: group_id
            };

            OTGAccess.access_settings.toolset_access_disable_dialog_button();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
					OTGAccess.access_settings.load_permission_tables( params['target'] );
					$( document ).trigger( 'js_event_types_access_custom_group_updated' );
                }
            });

        };



        $(document).on('click', '.js-wpcf-add-new-access-group', function (e) {

            e.preventDefault();

            $access_dialog_open(500);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_add_group );
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_custom_access_group);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_add_new_group',
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $process_new_access_group;
            OTGAccess.access_settings.dialog_callback_params['id'] = [];

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
                    otgs_access_posts_group_select2( '.js-otgs-access-suggest-posts', 'access_search_posts' );
					OTGAccess.access_settings.toolset_access_disable_dialog_button();

                }
            });

        });

        $process_new_access_group = function () {
            var posts = [];

            $('.js-assigned-access-item').each(function () {
                 if ( typeof $(this).data('newitem') !== 'undefined' ){
                 	posts.push($(this).data('itemid'));
				 }
            });

            var data = {
                action: 'access_add_new_group_process',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                title: $('#wpcf-access-new-group-title').val(),
                add: $('#wpcf-access-new-group-action').val(),
                posts: posts
            };

            OTGAccess.access_settings.toolset_access_disable_dialog_button();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    if (response.data != 'error') {
						OTGAccess.access_settings.load_permission_tables( 'custom-group' );
						$( document ).trigger( 'js_event_types_access_custom_group_updated' );
                    } else {
                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        $('.js-otg-access-spinner').remove();
                        OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
                    }
                }
            });
        };

        $(document).on('input', '#wpcf-access-new-group-title', function () {
            $('.js-error-container').html('');

            if ($(this).val() !== '') {
                OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
            } else {
                OTGAccess.access_settings.toolset_access_disable_dialog_button();
            }
        });

        $(document).on('click', '.js-wpcf-modify-group', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_modify_group );
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_custom_access_group_modify);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_add_new_group',
                modify: $(this).data('group'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $process_modify_access_group;
            OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('group');
            OTGAccess.access_settings.dialog_callback_params['divid'] = $(this).data('groupdiv');

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);

					otgs_access_posts_group_select2( '.js-otgs-access-suggest-posts', 'access_search_posts' );
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
                }
            });
        });

        $process_modify_access_group = function (params) {
           var posts = [];
            $('.js-assigned-access-item').each(function () {
                 if ( typeof $(this).data('newitem') !== 'undefined' ){
                 	posts.push($(this).data('itemid'));
				 }
            });

            id = params['id'];
            var data = {
                action: 'access_modify_group_process',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                title: $('#wpcf-access-new-group-title').val(),
                add: $('#wpcf-access-new-group-action').val(),
                id: id,
                posts: posts
            };
            OTGAccess.access_settings.toolset_access_disable_dialog_button();
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (data) {
                    if (data.data != 'error') {
                        $('#js-box-' + params['divid'])
                                .find('h4')
                                .eq(0)
                                .html($('#wpcf-access-new-group-title').val());

                         $('#js-box-' + params['divid'])
                                .find('.toolset-access-posts-group-assigned-posts-list')
                                .html(data.data);
                        OTGAccess.access_settings.access_control_dialog.dialog('close');
						$( document ).trigger( 'js_event_types_access_custom_group_updated' );
                    } else {
                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error js-toolset-alert" style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
						$('.js-otg-access-spinner').remove();
                    }
                }
            });

            return false;
        };

        $(document).on('submit', '.wpcf-access-set_error_page, #wpcf-access-set_error_page', function (e) {
        	e.preventDefault();
            return false;
        });

    $( document ).on( 'change', 'select[name^="wpcf_access_bulk_set"]', function() {
            var value = $(this).val();
            if (value != '0') {

                $(this).parent().find('select').each(function () {
                    $(this).val(value);
                });
            }
        });



    $( document ).on( 'change', '.wpcf-access-reassign-role select', function() {
            $(this)
                    .parents('.wpcf-access-reassign-role')
                    .find('.confirm')
                    .removeAttr('disabled');
        });

    });

    wpcfAccess.ApplyLevels = function (object) {
		var data_for_events = {
			section: 'custom-roles'
		};
		$.ajax({
			url:		ajaxurl,
			type:		'POST',
			dataType:	'json',
			data:		object.closest('.js-access-custom-roles-selection').find('.wpcf-access-custom-roles-select').serialize() +
			'&wpnonce=' + wpcf_access_dialog_texts.otg_access_general_nonce + '&action=access_clone_role',
			beforeSend:	function() {
					$('#wpcf-access-custom-roles-table-wrapper').css('opacity', 0.5);
				},
			success:	function( response ) {
				if ( response.success ) {
					$( '.js-otg-access-settings-section-for-custom-roles' ).replaceWith( response.data.message );
					$( document ).trigger( 'js_event_types_access_permission_table_loaded', [ data_for_events ] );
					$( document ).trigger( 'js_event_types_access_custom_roles_updated' );
				}
			}
        });
        return false;
    };


    wpcfAccess.enableElement = function ($obj) {
        if ($obj.data('isPrimary')) {
            $obj.addClass('button-primary');
        }
        if ($obj.data('isSecondary')) {
            $obj.addClass('button-secondary');
        }
        $obj
                .prop('disabled', false)
                .prop('readonly', false);
    };

    wpcfAccess.disableElement = function ($obj) {
        if ($obj.data('isPrimary')) {
            $obj
                    .removeClass('button-primary')
                    .addClass('button-secondary');
        }
        $obj.prop('disabled', true);
    };

    wpcfAccess.EnableTableInputs = function ($inputs, $container) {
        $container.addClass('is-enabled');
        $.each($inputs, function () {
            wpcfAccess.enableElement($(this));
        });

    };

    wpcfAccess.DisableTableInputs = function ($inputs, $container) {
        $container.removeClass('is-enabled');
        $.each($inputs, function () {
            wpcfAccess.disableElement($(this));
        });
    };

	$(document).on("click", ".toolset-access-disabled-detector, .js-toolset-access-specific-user-link", function (e) {
		if ( $(this).hasClass('js-forms-disabled-checkbox') ) {
			return;
		}
		var status = $(this).closest('.js-wpcf-access-type-item').find('.js-wpcf-enable-access').prop('checked');
		if ( status ){
			return;
		}
		$access_dialog_open(500);

		OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
		OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_enable_manage_by_button );

		$('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(' ');
		OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');

		OTGAccess.access_settings.access_control_dialog.html( '<div class="toolset-access-alarm-wrap-left"><i class="fa fa-exclamation-triangle fa-5x"></i></div>'+
					'<div class="toolset-access-alarm-wrap-right">'+ wpcf_access_dialog_texts.wpcf_enable_manage_by_message +'</div>' );

		OTGAccess.access_settings.dialog_callback = $toolset_access_enable_area;
		OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('parent');
	});

	var $toolset_access_enable_area = function( params ){
		var id = params['id'];
		$('.'+id).find('.js-wpcf-enable-access').click();
		$("#js-wpcf-access-dialog-container").dialog("close");
	}

	// Enable/Disable inputs
    $(document).on('change', '.js-wpcf-enable-access, .js-wpcf-follow-parent', function () {
        var $container = $(this).closest('.js-wpcf-access-type-item');
        var checked = $(this).is(':checked');
        var $tableInputs = $container.find('table :checkbox, table input[type=text]');

        $tableInputs = $tableInputs.filter(function () { // All elements except 'administrator' role checkboxes
            return ($(this).val() !== 'administrator');
        });

        if ($(this).is('.js-wpcf-enable-access')) {
			var managed_status =  $( this ).val(),
				follow_status = false;
			if ( typeof  $container.find('.js-wpcf-follow-parent').prop('checked') !== 'undefined' ){
				follow_status =  $container.find('.js-wpcf-follow-parent').prop('checked');
			}
            if ( checked && ( 'permissions' == managed_status || 1 == managed_status )  ) {


                if( ! follow_status ) {
					$container.find('.js-toolset-access-specific-user-link').removeClass('js-toolset-access-specific-user-disabled');
					$container.find('.js-otg-access-settings-section-is-mamanged').hide();
					wpcfAccess.EnableTableInputs($tableInputs, $container);
					$.each($tableInputs, function () {
						var cap = $(this).data('wpcfaccesscap');
						if ($(this).val() == 'guest' && (
								cap == 'publish' || cap == 'delete_any' || cap == 'edit_any' || cap == 'delete_own' || cap == 'edit_own' || cap == 'read_private' ||
								cap == 'assign_terms' || cap == 'delete_terms' || cap == 'edit_terms' || cap == 'manage_terms'
							)) {
							$(this).prop('disabled', true);
						}

					});
				}
                wpcfAccess.enableElement($container.find('.js-wpcf-follow-parent'));
                $container.find('.js-wpcf-access-reset').prop('disabled', false);
            } else {
            	$container.find('.js-otg-access-settings-section-is-mamanged').show();
            	$container.find('.js-toolset-access-specific-user-link').addClass('js-toolset-access-specific-user-disabled');
                $container.find('.js-wpcf-access-reset').prop('disabled', true);
                wpcfAccess.DisableTableInputs($tableInputs, $container);
                wpcfAccess.disableElement($container.find('.js-wpcf-follow-parent'));

            }
        } else if ($(this).is('.js-wpcf-follow-parent')) {
            if (checked) {
				$container.find('.js-toolset-access-specific-user-link').addClass('js-toolset-access-specific-user-disabled');
                $container.find('.js-wpcf-access-reset').prop('disabled', true);
                wpcfAccess.DisableTableInputs($tableInputs, $container);
            } else {
				$container.find('.js-toolset-access-specific-user-link').removeClass('js-toolset-access-specific-user-disabled');
                $container.find('.js-wpcf-access-reset').prop('disabled', false);
                wpcfAccess.EnableTableInputs($tableInputs, $container);
            }
        }
    });

	// Set hidden input val and show/hide messages
    $(document).on('change', '.js-wpcf-enable-access', function () {
        var $container = $(this).closest('.js-wpcf-access-type-item');
        var checked = $(this).is(':checked');
        var $hiddenInput = $container.find('.js-wpcf-enable-set');
        var $message = $container.find('.js-warning-fallback');
        var $depMessage = $container.find('.dep-message');

        if (checked) {

            $hiddenInput.val($(this).val());
            $message.hide();
        } else {

            $hiddenInput.val('not_managed');
            $message.fadeIn('fast');
            $depMessage.hide();
        }
    });

    $(document).on('change', '.js-wpcf-enable-languageaccess', function () {
        var $container = $(this).closest('.js-wpcf-access-type-item');
        var checked = $(this).is(':checked');
        var $hiddenInput = $container.find('.js-wpcf-enable-wpml-language-permissions');

        if (checked) {
            $hiddenInput.val($(this).val());
        } else {

            $hiddenInput.val('disabled');
        }
    });


// Auto check/uncheck checkboxes
    wpcfAccess.AutoThick = function (object, cap, name) {
        var thick = new Array();
        var thickOff = new Array();
        var active = object.is(':checked');
        var role = object.val();
        var cap_active = 'wpcf_access_dep_true_' + cap;
        var cap_inactive = 'wpcf_access_dep_false_' + cap;
        var message = new Array();

        if (active) {
            if (typeof window[cap_active] != 'undefined') {
                thick = thick.concat(window[cap_active]);
            }
        } else {
            if (typeof window[cap_inactive] != 'undefined') {
                thickOff = thickOff.concat(window[cap_inactive]);
            }
        }
        // FIND DEPENDABLES
        //
        // Check ONs
        $.each(thick, function (index, value) {
            object.parents('tr').find(':checkbox').each(function () {

                if ($(this).attr('id') != object.attr('id')) {

                    if ($(this).val() == role && $(this).hasClass('wpcf-access-' + value)) {
                        // Mark for message
                        if ($(this).is(':checked') == false) {
                            message.push($(this).data('wpcfaccesscap'));
                        }
                        // Set element form name
						$(this).prop('checked', true).attr('name', $(this).data('wpcfaccessname'));
                    }
                } else {
					$(this).prop('checked', true).attr('name', $(this).data('wpcfaccessname'));
				}
            });
        });

        // Check OFFs
        $.each(thickOff, function (index, value) {
            object.parents('tr').find(':checkbox').each(function () {

                if ($(this).attr('id') != object.attr('id')) {

                    if ( $(this).val() == role && $(this).hasClass('wpcf-access-' + value)) {

                        // Mark for message
                        if ($(this).is(':checked')) {
                            message.push($(this).data('wpcfaccesscap'));
                        }
						$(this).prop('checked', false).attr('name', 'dummy');
                    }
                }
            });
        });
        // Set true if admnistrator
        if (object.val() == 'administrator') {
            object
                    .attr('name', name)
					.prop('checked', true);
        }

        // Alert
        wpcfAccess.DependencyMessageShow(object, cap, message, active);
    }

    wpcfAccess.ThickTd = function (object, direction, checked) {
        if (direction == 'next') {
            var cbs = object
                    .closest('td')
                    .nextAll('td')
                    .find(':checkbox');
        } else {
            var cbs = object
                    .closest('td')
                    .prevAll('td')
                    .find(':checkbox');
        }
        if (checked) {
            cbs.each(function () {
                $(this)
                        .prop('checked', true)
                        .prop('name', 'dummy');
            });
        } else {
            cbs.each(function () {
                $(this)
                        .prop('checked', false)
                        .prop('name', 'dummy');
            });
        }
    };

    wpcfAccess.DependencyMessageShow = function (object, cap, caps, active) {
        var update_message = wpcfAccess.DependencyMessage(cap, caps, active);
        var update = object.parents('.wpcf-access-type-item').find('.dep-message');

        update.hide().html('');
        if (update_message != false) {
            update.html(update_message).show();
        }
    }

    wpcfAccess.DependencyMessage = function (cap, caps, active) {
        var active_pattern_singular = window['wpcf_access_dep_active_messages_pattern_singular'];
        var active_pattern_plural = window['wpcf_access_dep_active_messages_pattern_plural'];
        var inactive_pattern_singular = window['wpcf_access_dep_inactive_messages_pattern_singular'];
        var inactive_pattern_plural = window['wpcf_access_dep_inactive_messages_pattern_singular'];
        /*var no_edit_comments = window['wpcf_access_edit_comments_inactive'];*/
        var caps_titles = new Array();
        var update_message = false;

        $.each(caps, function (index, value) {
            if (active) {

                var key = window['wpcf_access_dep_true_' + cap].indexOf(value);
                caps_titles.push(window['wpcf_access_dep_true_' + cap + '_message'][key]);
            } else {

                var key = window['wpcf_access_dep_false_' + cap].indexOf(value);
                caps_titles.push(window['wpcf_access_dep_false_' + cap + '_message'][key]);
            }
        });

        if (caps.length > 0) {
            if (active) {
                if (caps.length < 2) {

                    var update_message = active_pattern_singular.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                } else {

                    var update_message = active_pattern_plural.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                }
            } else {
                if (caps.length < 2) {

                    var update_message = inactive_pattern_singular.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                } else {

                    var update_message = inactive_pattern_plural.replace('%cap', window['wpcf_access_dep_' + cap + '_title']);
                }
            }
            update_message = update_message.replace('%dcaps', caps_titles.join('\', \''));
        }
        return update_message;
    }

     $(document).on('click', '.js-toolset-access-specific-user-link', function (e) {

            e.preventDefault();
            var active_tab = $( '.js-otg-access-nav-tab.nav-tab-active' ).data( 'target' );
 			var status = $(this).closest('.js-otg-access-settings-section-item-content').find('.js-wpcf-enable-access').prop('checked');
		 	if ( active_tab == 'taxonomy' && typeof $(this).closest('.js-otg-access-settings-section-item-content').find('.js-wpcf-follow-parent') !== 'undefined' &&
				$(this).closest('.js-otg-access-settings-section-item-content').find('.js-wpcf-follow-parent').prop('checked') == true ){
				return;
			}

 			if ( !status && ( active_tab == 'post-type' || active_tab == 'taxonomy' ) ){
 				return;
			}
            $access_dialog_open(500);

			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-gui-close' ), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $( '.js-wpcf-access-process-button' ), wpcf_access_dialog_texts.wpcf_save );
            var title = '"'+$(this).data('slugtitle')+'"';
            if ( title.length > 15 ){
            	title = '';
			}
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.otg_access_manage_specific_users.replace('%s',title));

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_specific_users_form',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                id: $(this).data('id'),
                groupid: $(this).data('groupid'),
                option_name: $(this).data('option')
            };

            OTGAccess.access_settings.dialog_callback = $process_add_specific_users;
            OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('id');
            OTGAccess.access_settings.dialog_callback_params['groupid'] = $(this).data('groupid');
            OTGAccess.access_settings.dialog_callback_params['option'] = $(this).data('option');

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
                    otgs_access_posts_group_select2( '#toolset-access-user-suggest-field', 'access_suggest_users' );
					toolset_access_process_button_status(1);
                }
            });

	 });

	/*
	 Process add/remove specific users
	*/
    $process_add_specific_users = function( params ){
        var id = params['id'],
        groupid = params['groupid'],
        option_name = params['option'];

        if ( id == '' || groupid == '' || option_name == '' ){
            return;
        }
        var users = [];
        $('.js-assigned-access-item').each(function () {
                users.push($(this).data('itemid'))
        });
        var data = {
            action: 'access_specific_users_process',
			wpnonce: $('#wpcf-access-error-pages').attr('value'),
            id : id,
            groupid : groupid,
            option_name : option_name,
            users : users
        }
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: data,
            dataType: "json",
            cache: false,
            success: function (response) {
            	var data = response.data;
				var dep_message_show = false;
				$.each( data.options_texts, function( index, value ) {
					var usersSpan = $( document.getElementsByClassName( 'js-access-toolset-specific-users-list-' + id + '-' + groupid + '-' + index ) );
					usersSpan.html( value );
					if (!dep_message_show && typeof data.updated_sections !== 'undefined') {
						dep_message_show = true;
						var update = usersSpan.parents( '.wpcf-access-type-item' ).find( '.dep-message' );
						update.html( data.updated_sections ).show();
					}
				} );

				OTGAccess.access_settings.access_control_dialog.dialog('close');

            }
        });
	}

	 /*
		Fix select2 on ui dialogs
		https://github.com/select2/select2/issues/1246#issuecomment-71710835
	 */
	 var otgs_access_fix_select2_in_dialog = function(){
		 //Enable selet2 dropdown fix
		 if ($.ui && $.ui.dialog && $.ui.dialog.prototype._allowInteraction) {
			 var ui_dialog_interaction = $.ui.dialog.prototype._allowInteraction;
			 $.ui.dialog.prototype._allowInteraction = function(e) {
				 if ($(e.target).closest('.toolset_select2-dropdown').length) return true;
				 return ui_dialog_interaction.apply(this, arguments);
			 };
		 }
	 }

	// Remove items
	$(document).on('click', '.js-wpcf-unassign-access-item', function () {
		var div = '.js-assigned-access-item-' + $(this).data('id');
		$(div).animate({ opacity:0}, 1000, function() {$(div).remove();});
		return false;
	});

     /*
		* Enable select2 for posts group
	 */
		var otgs_access_posts_group_select2 = function( object, action ){
			var placeholder = wpcf_access_dialog_texts.otg_access_suggest_post_search_placeholder;
			if ( action == 'access_suggest_users' ){
				placeholder = wpcf_access_dialog_texts.otg_access_suggest_users_search_placeholder;
			}
			$(object).toolset_select2({
				ajax: {
					url: ajaxurl + '?action='+ action +'&wpnonce='+$('#wpcf-access-error-pages').attr('value'),
					dataType: 'json',
					delay: 250,
					type: 'post',
					data: function (params) {
						if ( action == 'access_suggest_users' ){
							var users = [];
							$('.js-assigned-access-item').each(function () {
									users.push($(this).data('itemid'));
							});
							return {
								q: params.term,
                                wpnonce: $('#wpcf-access-error-pages').attr('value'),
								assigned_users: users
							};
						}else{
							var posts = [];
							$('.js-assigned-access-post').each(function () {
								posts.push($(this).data('postid'));
							});
							return {
								q: params.term,
                                wpnonce: $('#wpcf-access-error-pages').attr('value'),
								post_type: $('.js-otgs-access-suggest-posts-types').val(),
								assigned_posts: posts
							};
						}
					},
					processResults: function (data, params) {
					  $(object).val('');
					  if ( typeof data.data.items === 'undefined' || data.data.items.length == 0 ){
					  	data.data.items = [];
					  }
					  return {
						results: data.data.items
					  };
					},
					cache: true
				  },
				  //dropdownCssClass: "js-toolset-access-select2-dropdown",
				  escapeMarkup: function (markup) { return markup; },
				  placeholder: placeholder,
				  minimumInputLength: 2,
				  triggerChange: true,
				  templateSelection: function(data, container){return placeholder},
				  //closeOnSelect: false,
				  templateResult: function(repo){
					  if (repo.loading) return wpcf_access_dialog_texts.otg_access_searching;
					  var markup = '<div class="select2-result-repository clearfix">'+repo.name+'</div>';
					  return markup;
				  },
			})
			//.toolset_select2('val', [])
			.on('toolset_select2:select', function( e ) {
				var data = e.params.data;
			    var div = '.js-assigned-access-item-' + data.id;
				if (typeof $(div).html() === 'undefined') {
					$('.js-no-posts-assigned').hide();
					$(".js-otgs-access-posts-listing").prepend('<div class="js-assigned-access-item js-assigned-access-item-' + data.id + '" data-newitem="1" data-itemid="' + data.id + '" style="opacity:0;">' +
							data.name + ' <a href="" class="js-wpcf-unassign-access-item" data-id="' + data.id + '"><i class="fa fa-times"></i></a></div>');
					$(div).animate({ opacity:1}, 500, function() {});
				}
			});

			otgs_access_fix_select2_in_dialog();
		}

		var toolset_access_process_button_status = function( status ){
			if ( status == '' ){
				OTGAccess.access_settings.toolset_access_disable_dialog_button();
			}else{
				OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
			}
		}


// export it
    window.wpcfAccess = window.wpcfAccess || {};
    $.extend(window.wpcfAccess, wpcfAccess);
})(window, jQuery);
