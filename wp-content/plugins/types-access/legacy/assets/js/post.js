var wpcfAccess = wpcfAccess || {};
var OTGAccess = OTGAccess || {};

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
						.css({'marginLeft': '15px', 'display': 'inline'});
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
					class: 'button-secondary js-wpcf-access-gui-close',
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

	self.setButtonText = function( $button, $text ) {
		if ( $button.find( '.ui-button-text' ).length > 0 ) {
			$( '.ui-button-text', $button ).html( $text );
		} else {
			$button.html( $text );
		}
	};

	self.init = function() {
		self.init_dialogs();
    };

	self.init();

};

jQuery( function( $ ) {
    OTGAccess.access_settings = new OTGAccess.AccessSettings( $ );
});
(function (window, $, undefined) {
    $(function () {

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

        //Show popup from edit post page (assign post to group)
        $(document).on('click', '.js-wpcf-access-assign-post-to-group', function (e) {
            e.preventDefault();

            $access_dialog_open(500);

			OTGAccess.access_settings.setButtonText( $('.js-wpcf-access-gui-close'), wpcf_access_dialog_texts.wpcf_cancel );
			OTGAccess.access_settings.setButtonText( $('.js-wpcf-access-process-button'), wpcf_access_dialog_texts.wpcf_assign_group );
            $('div[aria-describedby="js-wpcf-access-dialog-container"] .ui-dialog-title').html(wpcf_access_dialog_texts.wpcf_access_group);

            OTGAccess.access_settings.access_control_dialog.html( OTGAccess.access_settings.spinner_placeholder );

            var data = {
                action: 'access_select_post_group_for_post_form',
                id: $(this).data('id'),
                wpnonce: $('#wpcf-access-error-pages').attr('value')
            };

            OTGAccess.access_settings.dialog_callback = $process_access_assign_post_to_group;
            OTGAccess.access_settings.dialog_callback_params['id'] = $(this).data('id');
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    OTGAccess.access_settings.access_control_dialog.html(response.data);
                    if ($('input[name="wpcf-access-group-method"]:checked').val() == 'existing_group') {
                        OTGAccess.access_settings.toolset_access_disable_dialog_button();
                        $('select[name="wpcf-access-existing-groups"]').removeClass('hidden').show();
                    }else{
                        $('input[name="wpcf-access-new-group"]').focus();
                    }
                }
            });

        });

        $process_access_assign_post_to_group = function (params) {
            id = params['id'];
            var data = {
                action: 'access_select_post_group_for_post_process',
                wpnonce: $('#wpcf-access-error-pages').attr('value'),
                id: id,
                methodtype: $('input[name="wpcf-access-group-method"]:checked').val(),
                group: $('select[name="wpcf-access-existing-groups"]').val(),
                new_group: $('input[name="wpcf-access-new-group"]').val()
            };
            OTGAccess.access_settings.toolset_access_disable_dialog_button();
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function (response) {
                    if (response.data != 'error') {
                        $('.js-wpcf-access-post-group').html(response.data);
                        OTGAccess.access_settings.access_control_dialog.dialog('close');
                        $( document ).trigger( 'js_event_types_access_custom_group_updated' );
                    } else {
                        $('.js-error-container').html('<p class="toolset-alert toolset-alert-error " style="display: block; opacity: 1;">' + wpcf_access_dialog_texts.wpcf_group_exists + '</p>');
                        $('.js-otg-access-spinner').remove();
                        OTGAccess.access_settings.toolset_access_disable_dialog_button();
                    }
                }
            });

            return false;
        };



        $(document).on('change', 'input[name="wpcf-access-group-method"]', function () {
            $('select[name="wpcf-access-existing-groups"],input[name="wpcf-access-new-group"]').hide();
            $('.js-wpcf-access-process-button ')
                    .addClass('button-secondary')
                    .removeClass('button-primary ui-button-disabled ui-state-disabled')
                    .prop('disabled', true);
            if ($(this).val() == 'existing_group') {
                $('select[name="wpcf-access-existing-groups"]').removeClass('hidden').show();
                if ($('select[name="wpcf-access-existing-groups"]').val() != '') {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
                }
            } else {
                $('input[name="wpcf-access-new-group"]').removeClass('hidden').show();
                $('input[name="wpcf-access-new-group"]').focus();
                if ($('input[name="wpcf-access-new-group"]').val() !== '') {
                    OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
                }
            }
        });

        $(document).on('change', 'select[name="wpcf-access-existing-groups"]', function () {
            OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
        });

        $(document).on('input', 'input[name="wpcf-access-new-group"]', function () {
            OTGAccess.access_settings.toolset_access_disable_dialog_button();
            $('.js-error-container').html('');
            if ($(this).val() != '') {
                OTGAccess.access_settings.toolset_access_disable_dialog_button('enable');
            }
        });
    });


    window.wpcfAccess = window.wpcfAccess || {};
    $.extend(window.wpcfAccess, wpcfAccess);
})(window, jQuery);
