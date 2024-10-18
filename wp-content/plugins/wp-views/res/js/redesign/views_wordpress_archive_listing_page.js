var WPViews = WPViews || {};

WPViews.WPAListingScreen = function( $ ) {

    var self = this;

	self.i18n_data = {
		create_wordpress_archive_action: wpa_listing_texts.ajax.action.create_wordpress_archive,
		create_wordpress_archive_nonce: wpa_listing_texts.ajax.nonce.create_wordpress_archive,
	};

	self.dialog_create_or_change_usage = '';
	self.deleting_id = 0;
	self.creating_archive_loop_title = '';
	self.creating_archive_loop = '';
	self.bulk_trashing_ids = [];
	self.bulk_deleting_ids = [];

	self.shortcodeDialogSpinnerContent = $(
        '<div style="min-height: 150px;">' +
            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
                '<div class="wpv-spinner ajax-loader"></div>' +
                '<p>' + wpa_listing_texts.loading_options + '</p>' +
            '</div>' +
        '</div>'
    );

	/**
	* -----------------
	* Search and pagination
	* -----------------
	*/

	$( '#posts-filter' ).submit( function( e ) {
		e.preventDefault();
		var url_params = decodeURIParams( $( this ).serialize() );
		if (
			typeof( url_params['s'] ) !== 'undefined'
			&& url_params['s'] == ''
		) {
			url_params['s'] = null;
		}
		navigateWithURIParams( url_params );
		return false;
	});

	$( document ).on( 'change', '.js-items-per-page', function() {
	    navigateWithURIParams(decodeURIParams('paged=1&items_per_page=' + $(this).val()));
    });

    $( document ).on( 'click', '.js-wpv-display-all-items', function(e){
	    e.preventDefault();
	    navigateWithURIParams(decodeURIParams('paged=1&items_per_page=-1'));
    });

    $( document ).on( 'click', '.js-wpv-display-default-items', function(e){
	    e.preventDefault();
	    navigateWithURIParams(decodeURIParams('paged=1&items_per_page=20'));
    });

	/* ****************************************************************************\
            Action links
    \* ****************************************************************************/

	/**
	 * Fires when user clicks on "trash" action.
	 *
	 * @since unknown
	 */
	$( document ).on( 'click','.js-list-views-action-trash', function( e ) {
		e.preventDefault();
		showSpinner();

		var thiz = $( this ),
		wpaId = thiz.data( 'view-id' ),
		nonce = thiz.data( 'viewactionnonce' );

		// Just act as if this was a bulk action.
		self.maybeTrashWPAs( [ wpaId ], nonce );
	});


	/**
	 * Fires when user clicks on "restore-from-trash" action.
	 */
	$( document ).on( 'click','.js-list-views-action-restore-from-trash', function( e ) {
		e.preventDefault();

		var thiz = $( this ),
		data_view_id = thiz.data('view-id'),
		view_listing_action_nonce = thiz.data('viewactionnonce'),
		data = {
			action: 'wpv_view_change_status',
			id: data_view_id,
			newstatus: 'publish',
			wpnonce : view_listing_action_nonce
		};

		showSpinner();

		$.ajax({
			async: false,
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( (typeof(response) !== 'undefined') && (response == data.id)) {
					var url_params = decodeURIParams();
					url_params['paged'] = updatePagedParameter( url_params, 1 );
					url_params['untrashed'] = 1;
					navigateWithURIParams(url_params);
				} else {
					//console.log( "Error: AJAX returned ", response );
				}
			},
			error: function (ajaxContext) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() { }
		});
	});


	/**
	 * Undo "trash" action when user clicks on the Undo link.
	 *
	 * @since unknown
	 *
	 * @see wpv_admin_view_listing_message_undo() in wpv-views-listing-page.php
	 */
	$( document ).on( 'click', '.js-wpv-untrash', function( e ) {
		e.preventDefault();

		var thiz = $( this ),
		nonce = thiz.data( 'nonce' ),
		viewIDs = decodeURIComponent( thiz.data( 'ids' ) ).split( ',' );

		showSpinnerAfter( thiz );

		untrashViews( viewIDs, nonce );
	});

	/**
	 * Fires when user clicks on "delete" action.
	 */
	$( document ).on( 'click', '.js-list-views-action-delete', function( e ) {
		e.preventDefault();

		var thiz = $( this ),
		data_view_id = thiz.data( 'view-id' ),
		dialog_height = $( window ).height() - 100;

		self.deleting_id = data_view_id;

		self.dialog_delete_wpa.dialog( "open" ).dialog({
            width: 770,
            maxHeight: dialog_height,
            draggable: false,
            resizable: false,
			position: { my: "center top+50", at: "center top", of: window }
        });

	});

	/**
	 * Delete action
	 */
	$( document ).on( 'click', '.js-wpv-remove-wpa-permanent', function( e ) {
		e.preventDefault();

		var thiz = $( this ),
		data = {
			action: 'wpv_delete_wpa_permanent',
			id: self.deleting_id,
			wpnonce : $('#wpv_remove_view_permanent_nonce').val()
        };

		showSpinnerBefore( thiz );
		disablePrimaryButton( thiz );

		$.ajax({
			async: false,
			type: "POST",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					var url_params = decodeURIParams();
					url_params['paged'] = updatePagedParameter( url_params, 1 );
					url_params['deleted'] = 1;
					navigateWithURIParams( url_params );
				}
			},
			error: function (ajaxContext) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {	}
		});
	});


    /* ****************************************************************************\
            Create new WPA dialog
    \* ****************************************************************************/


    /**
     * When a dialog for creating new WPA is open, this indicates whether user
     * has changed WPA name field in any way.
     *
     * If they didn't we feel free to suggest WPA name based on selected usage.
     *
     * @type {boolean}
     *
     * @since 1.9
     */
    var isWPANameCustomized = false;


    /**
     * WPA name field has changed.
     *
     * @since 1.9
     */
    $(document).on('change', '.js-wpv-new-archive-name', function() {
        isWPANameCustomized = true;
    });


    /**
     * Create Archive action from the Toolset Dashboard on Types
     *
     * @since 2.3.0
     */
    $( document ).on( 'click', '.js-toolset-dashboard-create-archive', function( e ) {
        e.preventDefault();

        var thiz = $( this ),
            dialog_height = $( window ).height() - 100;

        var thiz_button = $( '.js-wpv-add-wp-archive-for-loop' );
        enablePrimaryButton( thiz_button );

        $( '.js-wpv-add-wp-archive-for-loop' ).attr( 'data-redirect-url', thiz.data( 'redirect-url' ) );

        self.creating_archive_loop_title = thiz.data( 'forwhomtitle' );
        self.creating_archive_loop = thiz.data( 'forwhomloop' );

        self.dialog_create_wpa_for_archive_loop.dialog( "open" ).dialog({
            width: 770,
            maxHeight: dialog_height,
            draggable: false,
            resizable: false,
            position: { my: "center top+50", at: "center top", of: window }
        });

    });

	$( document ).on( 'click', '.js-wpv-views-archive-add-new', function( e ) {
        e.preventDefault();

        var thiz = $( this ),
		dialog_height = $( window ).height() - 100,
		dialog_width = $( window ).width() - 100,
        data = {
			action: 'wpv_create_wp_archive_popup',
            wpnonce: $('#work_views_listing').val()
        };

		self.dialog_create_or_change_usage = 'create';

		self.dialog_create_wpa.dialog( 'open' ).dialog({
            width:		550,
			title:		wpa_listing_texts.dialog_create_dialog_title,
            maxHeight:	dialog_height,
			maxWidth:	dialog_width,
            draggable:	false,
            resizable:	false,
			position:	{ my: "center top+50", at: "center top", of: window }
        });

		self.dialog_create_wpa.html( self.shortcodeDialogSpinnerContent );

		$.ajax({
			async: false,
			type: "GET",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					self.dialog_create_wpa.html( response.data.dialog_content );
					disablePrimaryButton( $( '.js-wpv-create-new-wpa' ) );
				}
			},
			error: function( ajaxContext ) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() { }
		});

    });

	$(document).on( 'keypress','.js-wpv-new-archive-name', function(event){
        if ( event.which == 13 ) {
            event.preventDefault();
        }
    });

    /**
     * Create Archive from the create dialog
     *
     * @since unknown
     */
    $( document ).on( 'click', '.js-wpv-create-new-wpa', function( e ) {
        e.preventDefault();
        var thiz = $( this ),
        thiz_container = $( '.js-wpv-dialog-wpa-manager' ),
        thiz_message_container = thiz_container.find( '.js-wpv-error-container' ),
		title,
		data;

        showSpinnerAfter( thiz );
        thiz_container.find('.toolset-alert').remove();
		title = $( '.js-wpv-new-archive-name' ).val();
		data = {
			action:		self.i18n_data.create_wordpress_archive_action,
			form:		$('#wpv-create-archive-view-form').serialize(),
			title:		title,
			purpose:	$( '.js-wpv-purpose:checked' ).val(),
			wpnonce:	self.i18n_data.create_wordpress_archive_nonce
		};
        $.ajax({
            async: false,
            type: "POST",
			dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
				if ( response.success ) {
					$( location ).attr( 'href', wpa_listing_texts.edit_url + response.data.id );
				} else {
                    $( '.js-wpv-new-archive-name' ).addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
					thiz_message_container
                        .wpvToolsetMessage({
                            text: response.data.message,
                            stay: true,
                            close: false,
                            type: 'error'
                        });
                    hideSpinner();
				}
            },
            error: function (ajaxContext) {
                //console.log( "Error: ", ajaxContext.responseText );
            },
            complete: function() {

            }
        });
    });

    /**
     * Controls the buttons in WP Archive creation popup
     *
     * @since unknown
     */
    $( document ).on( 'change input cut paste','.js-wpv-new-archive-name', function() {
        $( '.js-wpv-new-archive-name' ).removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
        $( '.js-wpv-dialog-wpa-manager .toolset-alert' ).remove();
        if ( $( this ).val() === "" ) {
			disablePrimaryButton( $( '.js-wpv-create-new-wpa' ) );
        } else {
			enablePrimaryButton( $( '.js-wpv-create-new-wpa' ) );
        }
    });

    /**
     * Create Archive for loop popup.
     *
     * @since unknown
     */
    $( document ).on( 'click', '.js-wpv-create-wpa-for-archive-loop', function( e ) {
        e.preventDefault();

		var thiz = $( this ),
		dialog_height = $( window ).height() - 100;

        var thiz_button = $( '.js-wpv-add-wp-archive-for-loop' );
        enablePrimaryButton( thiz_button );

		self.creating_archive_loop_title = thiz.data( 'forwhomtitle' );
		self.creating_archive_loop = thiz.data( 'forwhomloop' );

		self.dialog_create_wpa_for_archive_loop.dialog( "open" ).dialog({
            width: 770,
            maxHeight: dialog_height,
            draggable: false,
            resizable: false,
			position: { my: "center top+50", at: "center top", of: window }
        });

    });

	$( document ).on( 'change input cut paste', '.js-wpv-create-wpa-for-archive-loop-title', function() {
        $( '.js-wpv-create-wpa-for-archive-loop-title' ).removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
        $( '.js-wpv-dialog-wpa-manager .toolset-alert' ).remove();
		var thiz = $( this ),
		thiz_button = $( '.js-wpv-add-wp-archive-for-loop' );
		if ( thiz.val() == '' ) {
			disablePrimaryButton( thiz_button );
		} else {
			enablePrimaryButton( thiz_button );
		}
	});

	$( document ).on( 'click', '.js-wpv-add-wp-archive-for-loop', function( e ) {
		e.preventDefault();

		var thiz = $( this ),
			thiz_container = thiz.closest( '.ui-dialog' ),
			thiz_message_container = thiz_container.find( '.js-wpv-error-container' ),
			redirectUrl = typeof thiz.data( 'redirect-url' ) != 'undefined' ? thiz.data( 'redirect-url' ) : '',
			title = $( '.js-wpv-create-wpa-for-archive-loop-title input' ).val(),
			data = {
	            action:		'wpv_create_wpa_for_archive_loop',
				loop:		self.creating_archive_loop,
				purpose:	$( '.js-wpv-usage-purpose:checked' ).val(),
				title: title,
	            wpnonce:	$('#work_views_listing').val()
	        };

        showSpinnerBefore( thiz );
		thiz_message_container.html( '' );

        $.ajax({
            async: false,
            type: "POST",
			dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
				if ( response.success ) {
					$( location ).attr( 'href', wpa_listing_texts.edit_url + response.data.id + redirectUrl );
				} else {
                    $( '.js-wpv-create-wpa-for-archive-loop-title' ).addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
					thiz_message_container
						.wpvToolsetMessage({
                            text: response.data.message,
                            stay: true,
                            close: false,
                            type: 'error'
                        });
					// Added here to hide the spinner when the AJAX request is completed.
					hideSpinner();
				}
            },
            error: function( ajaxContext ) {
                //console.log( "Error: ", ajaxContext.responseText );
            },
            complete: function() {

            }
        });

	});


	self.manage_dialog_create_wpa_button_labels = function() {
		var $target = $( '.js-wpv-create-new-wpa' );
		if ( $target.find( '.ui-button-text' ).length > 0 ) {
			$target = $( '.js-wpv-create-new-wpa .ui-button-text' );
		}
		$target.html( wpa_listing_texts.dialog_create_action );
	};

	/**
	* -----------------
	* Bulk actions
	* -----------------
	*/

	/**
	 * Bulk action.
	 *
	 * Fires when user hits the Apply button near bulk action select field.
	 *
	 * @since 1.7
	 */
	$('.js-wpv-wpa-listing-bulk-action-submit').on('click', function(e) {
		e.preventDefault();

		showSpinner();

		// Get an array of checked WPA IDs.
		var checkedWPAs = $('.wpv-admin-listing-col-bulkactions input:checkbox:checked').map(function() {
			var value = $(this).val();
			// Filter out values of checkboxes in table header and footer rows.
			if( ! isNaN( parseFloat( value ) ) && isFinite( value ) ) {
				return value;
			}
		}).get();

		// If there are no items selected, do nothing.
		if( checkedWPAs.length == 0 ) {
			hideSpinner();
			return;
		}

		// nonce
		var nonce = $(this).data('viewactionnonce');

		// Get a position. That's important to determine which select field is relevant for us.
		var selectPosition = $(this).data('position');

		// Launch appropriate bulk action
		var action = $('.js-wpv-wpa-listing-bulk-action-select.position-' + selectPosition).val();
		switch(action) {
			case 'trash':
				self.maybeTrashWPAs( checkedWPAs, nonce );
				break;
			case 'restore-from-trash':
				untrashViews( checkedWPAs, nonce );
				break;
			case 'delete':
				self.deleteWPAConfirmation( checkedWPAs, nonce );
				break;
			default:
				// do nothing
				hideSpinner();
				return;
		}
	});


	/**
	 * Check whether WPAs are in use. If they are, show a confirmation before trashing them, otherwise trash them right away.
	 *
	 * @param array wpaIDs Array of WPA IDs that should be trashed.
	 * @param string nonce A valid nonce for the trashing action.
	 *
	 * @since 1.7
	 */
	self.maybeTrashWPAs = function( wpaIDs, nonce ) {

		var data = {
			action: 'wpv_archive_check_usage',
			ids: wpaIDs,
			wpnonce : nonce
		};

		$.ajax({
			async: false,
			type: "GET",
			dataType: "json",
			url: ajaxurl,
			data: data,
			success: function( response ) {
				if ( response.success ) {
					if ( response.data.used_wpa_ids.length == 0 ) {
						trashViews( wpaIDs, nonce, true );
					} else {
						var dialog_height = $( window ).height() - 100;
						self.bulk_trashing_ids = wpaIDs;
						self.dialog_bulk_trash_wpa.dialog( "open" ).dialog({
							width: 770,
							maxHeight: dialog_height,
							draggable: false,
							resizable: false,
							position: { my: "center top+50", at: "center top", of: window }
						});
						self.dialog_bulk_trash_wpa.html( response.data.dialog_content )
					}
					hideSpinner();
				}

			},
			error: function( ajaxContext ) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() { }
		});
	};


	/**
	 * Trash archives (and unassign them from archive loops) after confirmation.
	 *
	 * @since 1.7
	 *
	 * @todo comment
	 */
	$( document ).on( 'click', '.js-bulk-trash-wpa-confirm', function( e ) {
		e.preventDefault();

		var thiz = $( this );

		disablePrimaryButton( thiz );
		showSpinnerBefore( thiz );

		var wpaIDs = self.bulk_trashing_ids;
		var nonce = wpa_listing_texts.dialog_bulktrash_nonce;

		trashViews( wpaIDs, nonce, true );
	});


	/**
	 * Show a popup with confirmation message.
	 *
	 * Archives are deleted after clicking on .js-wpv-bulk-remove-wpa-permanent.
	 *
	 * @since 1.7
	 */
	self.deleteWPAConfirmation = function( wpaIDs, nonce ) {

		// Do AJAX call to generate popup code
		/*
		var data = {
			action: 'wpv_archive_bulk_delete_render_popup',
			ids: wpaIDs,
			wpnonce : nonce
		};
		*/

        var data = {
            action: 'wpv_archive_bulk_delete_render_popup',
            ids: wpaIDs,
            wpnonce : nonce
        };

        var dialog_height = $( window ).height() - 100;
        self.dialog_dummy.dialog( "open" ).dialog({
            width: 770,
            title: wpa_listing_texts.dialog_bulk_delete_dialog_title,
            maxHeight: dialog_height,
            draggable: false,
            resizable: false,
            position: { my: "center top+50", at: "center top", of: window }
        });

        $.ajax({
            async: false,
            type: "POST",
            dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {

                	var dialog_height = $( window ).height() - 100;

                    self.bulk_deleting_ids = response.data.wpa_ids;

                    // Close the dummy "Loading..." dialog
                    self.dialog_dummy.dialog( "close" );

                    if ( response.data.wpa_ids.length > 0 ) {
                        if (response.data.action == 'empty-trash') {
                            self.dialog_bulk_delete_wpa_empty_trash.dialog("open").dialog({
                                width: 770,
                                maxHeight: dialog_height,
                                draggable: false,
                                resizable: false,
                                position: {my: "center top+50", at: "center top", of: window}
                            });
                        }
                        else {
                            self.dialog_bulk_delete_wpa.dialog("open").dialog({
                                width: 770,
                                maxHeight: dialog_height,
                                draggable: false,
                                resizable: false,
                                position: {my: "center top+50", at: "center top", of: window}
                            });
                        }
                    }

                    hideSpinner();
                }
            },
            error: function( ajaxContext ) {
                //console.log( "Error: ", ajaxContext.responseText );
            },
            complete: function() { }
        });
	};


	/**
	 * Permanently delete given Archives and redirect to current page with 'deleted' message.
	 *
	 * @since 1.7
	 */
	$( document ).on( 'click', '.js-wpv-bulk-remove-wpa-permanent', function( e ) {
		e.preventDefault();

		var thiz = $( this ),
		data = {
			action: 'wpv_bulk_delete_views_permanent',
			ids: self.bulk_deleting_ids,
			wpnonce : wpa_listing_texts.dialog_bulkdel_nonce
		};

		disablePrimaryButton( thiz );
		showSpinnerBefore( thiz );

		$.ajax({
			async: false,
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function( response ){
				// response == 1 indicates success
				if ( ( typeof( response ) !== 'undefined' ) && ( 1 == response ) ) {
					// reload the page with "deleted" message
					var url_params = decodeURIParams();
					var affectedItemCount = self.bulk_deleting_ids.length;
					url_params['paged'] = updatePagedParameter( url_params, affectedItemCount );
					url_params['deleted'] = affectedItemCount;
					navigateWithURIParams( url_params );
				} else {
					//console.log( "Error: AJAX returned ", response );
				}
			},
			error: function (ajaxContext) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {	}
		});

	});

    /**
     * Empty trash. Show the confirmation popup.
     *
     * @since 2.3.0
     */
    $( '.js-wpv-views-empty-trash' ).on( 'click', function( e ) {
        e.preventDefault();

        showSpinner();

        var checkedWPAs = [-1];

        // nonce
        var nonce = $(this).data('viewactionnonce');

        self.deleteWPAConfirmation( checkedWPAs, nonce );
    });

	/**
	* -----------------
	* Init dialogs
	* -----------------
	*/

	self.init_dialogs = function() {

		$( 'body' ).append( '<div id="js-wpv-create-wpa-form-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container js-wpv-shortcode-gui-dialog-container"></div>' );

		self.dialog_create_wpa = $( "#js-wpv-create-wpa-form-dialog" ).dialog({
			autoOpen:	false,
			modal:		true,
			dialogClass: 'toolset-blocks-ui-dialog',
			title:		wpa_listing_texts.dialog_create_dialog_title,
			minWidth:	400,
			show: {
				effect: "blind",
				duration: 800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.manage_dialog_create_wpa_button_labels();
				disablePrimaryButton( $( '.js-wpv-create-new-wpa' ) );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
				self.dialog_create_or_change_usage = '';
                self.closePointerTooltip();
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-create-new-wpa',
					text: wpa_listing_texts.dialog_create_action,
					click: function() {

					}
				},
				{
					class: 'button-secondary',
					text: wpa_listing_texts.dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
                        self.closePointerTooltip();
					}
				}
			]
		});

		self.dialog_delete_wpa = $( "#js-wpv-delete-wpa-dialog" ).dialog({
			autoOpen: false,
			modal: true,
			title: wpa_listing_texts.dialog_delete_dialog_title,
			minWidth: 600,
			show: {
				effect: "blind",
				duration: 800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
				self.deleting_id = 0;
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-remove-wpa-permanent',
					text: wpa_listing_texts.dialog_delete_action,
					click: function() {

					}
				},
				{
					class: 'button-secondary',
					text: wpa_listing_texts.dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});

		self.dialog_create_wpa_for_archive_loop = $( "#js-wpv-create-wpa-for-archive-loop-dialog" ).dialog({
			dialogClass: 'toolset-blocks-ui-dialog',
			autoOpen: false,
			modal: true,
			title: wpa_listing_texts.dialog_create_wpa_for_archive_loop_dialog_title,
			minWidth: 600,
			show: {
				effect: "blind",
				duration: 800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				$( '.js-wpv-create-wpa-for-archive-loop-hint' ).html( self.creating_archive_loop_title );
				$( '.js-wpv-create-wpa-for-archive-loop-title' ).val( self.creating_archive_loop_title );
				$( '.js-wpv-usage-purpose' ).prop( 'checked', false );
				$( '.js-wpv-usage-purpose-all' ).prop( 'checked', true );
                var error_container = $('.wpv-shortcode-gui-content-wrapper').find('.js-wpv-error-container');
                error_container.html('');
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
				self.creating_archive_loop_title = '';
				self.creating_archive_loop = '';
                self.closePointerTooltip();
            },
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button button-primary js-wpv-add-wp-archive-for-loop',
					text: wpa_listing_texts.dialog_create_wpa_for_archive_loop_action,
					click: function() {

					}
				},
				{
					class: 'button button-secondary',
					text: wpa_listing_texts.dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
                        self.closePointerTooltip();
                    }
				}
			]
		});

		$( 'body' ).append( '<div id="js-wpv-bulk-trash-wpa-dialog" class="toolset-shortcode-gui-dialog-container wpv-shortcode-gui-dialog-container js-wpv-shortcode-gui-dialog-container"></div>' );

		self.dialog_bulk_trash_wpa = $( "#js-wpv-bulk-trash-wpa-dialog" ).dialog({
			autoOpen: false,
			modal: true,
			title: wpa_listing_texts.dialog_bulk_trash_dialog_title,
			minWidth: 600,
			show: {
				effect: "blind",
				duration: 800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
				self.bulk_trashing_ids = [];
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-bulk-trash-wpa-confirm',
					text: wpa_listing_texts.dialog_bulk_trash_action,
					click: function() {

					}
				},
				{
					class: 'button-secondary',
					text: wpa_listing_texts.dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});

		self.dialog_bulk_delete_wpa = $( "#js-wpv-bulk-delete-wpa-dialog" ).dialog({
			autoOpen: false,
			modal: true,
			title: wpa_listing_texts.dialog_bulk_delete_dialog_title,
			minWidth: 600,
			show: {
				effect: "blind",
				duration: 800
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
				self.bulk_deleting_ids = [];
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-bulk-remove-wpa-permanent',
					text: wpa_listing_texts.dialog_delete_action,
					click: function() {

					}
				},
				{
					class: 'button-secondary',
					text: wpa_listing_texts.dialog_cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});

        self.dialog_bulk_delete_wpa_empty_trash = $( "#js-wpv-bulk-delete-wpa-empty-trash-dialog" ).dialog({
            autoOpen: false,
            modal: true,
            title: wpa_listing_texts.dialog_bulk_delete_dialog_title,
            minWidth: 600,
            show: {
                effect: "blind",
                duration: 800
            },
            open: function( event, ui ) {
                $( 'body' ).addClass( 'modal-open' );
            },
            close: function( event, ui ) {
                $( 'body' ).removeClass( 'modal-open' );
                self.bulk_deleting_ids = [];
            },
            buttons:[
				{
                    class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-wpv-bulk-remove-wpa-permanent js-wpv-empty-trash-confirm',
                    text: wpa_listing_texts.dialog_delete_action,
                    click: function() {

                    }
                },
                {
                    class: 'button-secondary',
                    text: wpa_listing_texts.dialog_cancel,
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ]
        });

        self.dialog_dummy = $( '#js-dummy-dialog' ).dialog({
            autoOpen: false,
            modal: true,
            minWidth: 600,
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
                    class: 'button-secondary',
                    text: wpa_listing_texts.dialog_cancel,
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ]

        });

	};

	self.initPointerTooltipForDisabledOption = function () {

        $( document ).on( 'click', '#wpv_parametric_disabled_pointer', function( e ){

            var pricingOutput = '<p><a href="'+wpa_listing_texts.tooltipPriceLinkURL+'" target="_blank">'+wpa_listing_texts.tooltipPriceLinkTitle+'</a></p>';
            // set default
            var disabledPaginationTooltip = jQuery('#wpv_parametric_disabled_pointer').pointer({
                pointerClass: 'wp-toolset-pointer wpv-pointer-inside-dialog',
                content: '<h3>'+wpa_listing_texts.viewsLiteTooltipTitle+'</h3><p>'+wpa_listing_texts.tooltipParametricDisabled+'</p>'+pricingOutput,
                position: {
                    edge: 'left',
                    align: 'left',
                }
            });
            disabledPaginationTooltip.pointer('open');
            jQuery('.wpv-pointer-inside-dialog').css({"z-index": "999999"});
        });

	};

    self.closePointerTooltip = function () {
        if( wpa_listing_texts.is_views_lite ){
            jQuery('#wpv_parametric_disabled_pointer').pointer().pointer('close');
        }
    };

	self.init = function() {
		$('.js-list-views-action option').removeAttr('selected');
		self.init_dialogs();

        if( wpa_listing_texts.is_views_lite ){
            self.initPointerTooltipForDisabledOption();
        }
	};

	self.init();

};

jQuery( function( $ ) {

    WPViews.wpa_listing_screen = new WPViews.WPAListingScreen( $ );

});
