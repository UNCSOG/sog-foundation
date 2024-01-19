var ToolsetCommon = ToolsetCommon || {};

ToolsetCommon.ThemeIntegration = function ($) {
    var self = this;

    self.hintPointer = null;
    self.displayType = Toolset_Theme_Integrations_Settings.strings.assignment;

    self.makeAjaxCall = function ( data, successCallback, errorCallback ) {
        $.ajax({
            async: true,
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: successCallback,
            error: errorCallback
        });
    };


    self.renderVisibleSections = function ( display_type, keep_cred_message ) {

        if ( display_type === null || display_type === '' ) {
            $( '.js-toolset-theme-settings-form' ).hide();
            $( '.js-toolset-non-assigned-message' ).show();
            self.showArchiveOptions();
            self.showSingleOptions();
        } else if ( display_type == 'archive' ) {
            $('.js-toolset-theme-settings-form').show();
            $('.js-toolset-non-assigned-message').hide();
            self.hideSingleOptions();
            self.showArchiveOptions();
        } else if ( display_type == 'shared' ) {
            $( '.js-toolset-theme-settings-form' ).show();
            $( '.js-toolset-non-assigned-message' ).hide();
            self.showArchiveOptions();
            self.showSingleOptions();
        } else if ( display_type == 'archive-cred' ) {
            $( '.js-toolset-theme-settings-form' ).show();
            $( '.js-toolset-non-assigned-message' ).show();
            self.hideSingleOptions();
            self.showArchiveOptions();
        } else if( display_type == 'posts-cred' ){
            $( '.js-toolset-theme-settings-form' ).show();
            $( '.js-toolset-non-assigned-message' ).show();
            self.hideArchiveOptions();
            self.showSingleOptions();
        } else if ( display_type == 'posts' ) {
            $( '.js-toolset-theme-settings-form' ).show();
            self.hideArchiveOptions();
            self.showSingleOptions();
            if( ! keep_cred_message ) {
                $( '.js-toolset-non-assigned-message' ).hide();
            }
        }

        self.adjustVisibilityForTargetsLayouts();
        self.handleVisibilityForHiddenSections();
    };


    self.init = function () {
        self.optionsVisibilityOnLayoutsEditorLoad();
        self.adjustVisibilityForTargetsCT();
        self.initHints();
        self.eventsOn();
        self.addLayoutsEditPageHooks();
        self.toggleThemeOptionsBoxVisibility();
        self.CTChangeAssignmentEvent();
        self.createColorPickerInstance();
    };

    self.createColorPickerInstance = function () {
        jQuery( '.js-theme-settings-colorpicker' ).wpColorPicker({
            change: function(){
                jQuery( '.js-editor-toolbar input[name="save_layout"]' ).prop( 'disabled', false );
                if( self.validatePage( 'ct-editor' ) ){
                    self.content_template_debounce_update();
                }
                if( self.validatePage( 'view-archives-editor' ) ){
                    self.wordpress_archive_debounce_update();
                }
            },
            clear: function() {
                jQuery( '.js-editor-toolbar input[name="save_layout"]' ).prop( 'disabled', false );
            }
        });
    };

    self.validatePage = function( page ){
        if( Toolset_Theme_Integrations_Settings.strings.current_page !== page ){
             return false;
        }
        return true;
    };

    /*
     * Adjust visibility for targets on Layouts editor
     */

    self.adjustVisibilityForTargetsLayouts = function (){

        if(typeof DDLayout_settings !== 'object'){
            return;
        }

        var post_types = self.getLayoutsAssignedPostTypes();
        var options_with_target_exclude = document.querySelectorAll( '[data-target-exclude]' );
        var options_with_target_include = document.querySelectorAll( '[data-target-include]' );

        self.adjustVisibilityForExcludedTargetsLayouts( post_types, options_with_target_exclude );
        self.adjustVisibilityForIncludedTargetsLayouts( post_types, options_with_target_include );

    };

    self.adjustVisibilityForExcludedTargetsLayouts = function( post_types, options_with_target_exclude ){
        _.each( options_with_target_exclude, function( val, key ) {
            var current_item_exclude_targets = $( val ).data( 'target-exclude' );
            var targets_intersection_exclude = _.intersection( current_item_exclude_targets, post_types );

            if(
                targets_intersection_exclude.length > 0 &&
                targets_intersection_exclude.length === post_types.length
            ){
                $( val ).hide();
            }
        });
    };

    self.adjustVisibilityForIncludedTargetsLayouts = function( post_types, options_with_target_include ){
        _.each( options_with_target_include, function( val, key ) {
            var current_item_include_targets = $( val ).data( 'target-include' );
            var targets_intersection_include = _.intersection( current_item_include_targets, post_types );

            if( targets_intersection_include.length > 0 ){
                $( val ).show();
            } else {
                $( val ).hide();
            }
        });
    };

    /*
     * Adjust visibility for targets on Content Template editor
     */

    self.adjustVisibilityForTargetsCT = function(){

        // execute only for content template page
        if( Toolset_Theme_Integrations_Settings.strings.current_page !== 'ct-editor' ){
            return;
        }

        var post_types = self.CTSelectedTypes();

        var options_with_target_exclude = document.querySelectorAll( '[data-target-exclude]' );
        var options_with_target_include = document.querySelectorAll( '[data-target-include]' );

        self.adjustVisibilityForExcludedTargetsCT( post_types, options_with_target_exclude );
        self.adjustVisibilityForIncludedTargetsCT( post_types, options_with_target_include );

    };

    self.adjustVisibilityForExcludedTargetsCT = function( post_types, options_with_target_exclude ){
        _.each( options_with_target_exclude, function( val, key ) {
            var current_item_exclude_targets = $( val ).data( 'target-exclude' );
            var targets_intersection_exclude = _.intersection( current_item_exclude_targets, post_types );

            if(
                targets_intersection_exclude.length > 0 &&
                targets_intersection_exclude.length === post_types.length
            ){
                $( val ).hide();
            }else {
                $( val ).show();
            }
        });
    };

    self.adjustVisibilityForIncludedTargetsCT = function( post_types, options_with_target_include ){
        _.each( options_with_target_include, function( val, key ) {

            var current_item_include_targets = $(val).data( 'target-include' );
            var targets_intersection_include = _.intersection( current_item_include_targets, post_types );

            if( targets_intersection_include.length > 0 && post_types.length > 0 ){
                $( val ).show();
            } else {
                $( val ).hide();
            }

        });
    };


    self.CTSelectedTypes = function(){

        if( Toolset_Theme_Integrations_Settings.strings.current_page !== 'ct-editor' ){
            return;
        }

        if(
            typeof wpv_ct_editor_ct_data !== 'object' &&
            ! wpv_ct_editor_ct_data.assigned_single_post_types
        ){
            return;
        }

        return wpv_ct_editor_ct_data.assigned_single_post_types;

    };

    self.CTChangeAssignmentEvent = function(){

        if( Toolset_Theme_Integrations_Settings.strings.current_page !== 'ct-editor' ){
            return;
        }

        var $ct_options = jQuery( '.js-wpv-usage-section input[type=checkbox]' );


        $ct_options.on( 'click', function( event ){
            var selected_single_post_types = [];
            var checked_options = jQuery( '.js-wpv-usage-section input[type=checkbox][data-bind="checked: assignedSinglePostTypesAccepted"]:checked' );
            _.each( checked_options, function( val, key ) {
                selected_single_post_types.push( jQuery( val ).val() );
            });
            _.defer( function(){
                wpv_ct_editor_ct_data.assigned_single_post_types = selected_single_post_types;
                self.adjustVisibilityForTargetsCT();
            });

        });

    };

    self.getLayoutsAssignedPostTypes = function(){
        if( typeof DDLayout_settings !== 'object' ){
            return;
        }

        var post_types = [];

        if(
            DDLayout.local_settings.list_where_used !== null &&
            DDLayout.local_settings.list_where_used.hasOwnProperty( 'post_types' )
        ){
            var assigned_to_types = DDLayout.local_settings.list_where_used.post_types;

            _.each( assigned_to_types, function( val, key ) {
                if ( val.post_type ) {
                    post_types.push( val.post_type );
                }
            });
        }


        return post_types;
    };

    self.handleVisibilityForHiddenSections = function(){
        var all_sections = jQuery( '.theme-settings-section-content' );
        _.each( all_sections, function( val, key ) {
            if( jQuery( val ).children( '.js-toolset-theme-settings-single-option-wrap' ).not( ':hidden' ).length === 0 ){
                jQuery( val ).parent( '.theme-settings-section' ).hide();
            }
        });
    };


    self.optionsVisibilityOnLayoutsEditorLoad = function (){

		// execute only for layout page
        if( Toolset_Theme_Integrations_Settings.strings.current_page !== 'dd_layouts_edit' ){
            return;
        }

        if( typeof Toolset_Theme_Integrations_Settings !== 'object' ){
            return;
        }
        self.renderVisibleSections( Toolset_Theme_Integrations_Settings.strings.assignment );

    };

    self.showSingleOptions = function(){
        $('.js-target-single').show();
    };

    self.showArchiveOptions = function(){
        $('.js-target-archive').show();
    };

    self.hideArchiveOptions = function(){
        $('.js-target-archive').hide();
    };

    self.hideSingleOptions = function(){
        $('.js-target-single').hide();
    };

    self.removeHints = function() {
        $('.js-theme-options-hint').each(function () {
            $(this).pointer('destroy');
        });
    };

    self.initHints = function(hintMessage) {
        $('.js-theme-options-hint').each(function () {
            var hintContent = "";

            if(typeof hintMessage == 'undefined') {
                hintContent = jQuery(this).data('content');
            } else {
                hintContent = hintMessage;
            }

            var hint = this;
            self.hintPointer = $(this).pointer({
                pointerClass: jQuery(this).data('classes'),
                content: '<h3>' + jQuery(this).data('header') + '</h3> <p>' + hintContent + '</p>',
                position: {
                    edge: ( $('html[dir="rtl"]').length > 0 ) ? 'right' : 'left',
                    align: 'center',
                    offset: '15 0'
                },
                buttons: function( event, t ) {
                    var button_close = $('<button class="button button-primary-toolset alignright js-wpv-close-this">'+Toolset_Theme_Integrations_Settings.strings.close+'</button>');
                    button_close.on( 'click.pointer', function( e ) {
                        jQuery(hint).pointer('close');

                    });
                    return button_close;
                }
            });
        });
    };

	self.contentTemplateUpdateSettings = function() {
		$('.toolset-theme-settings-spinner').addClass('is-active');
		var data = {
			action: 'toolset_theme_integration_save_ct_settings',
			id: WPViews.ct_edit_screen.ct_data.id,
			wpnonce: WPViews.ct_edit_screen.update_nonce,
			theme_settings: $('#toolset_theme_settings_form').serialize()
		};
		self.makeAjaxCall(data,
			function (originalResponse) {
				$('.toolset-theme-settings-spinner').removeClass('is-active');
				WPViews.ct_edit_screen.showSuccessMessage('#js-wpv-general-actions-bar .js-wpv-message-container', WPViews.ct_edit_screen.l10n.editor.saved);
				WPViews.ct_edit_screen.highlight_action_bar('success');
				jQuery(document).trigger('ct_saved');
			}, function (ajaxContext) {
				$('.toolset-theme-settings-spinner').removeClass('is-active');
				console.log('Error:', ajaxContext.responseText);
			}
		);
	};

	self.content_template_debounce_update = _.debounce( self.contentTemplateUpdateSettings, 2000 );

	self.wordpressArchiveUpdateSettings = function() {
		$('.toolset-theme-settings-spinner').addClass('is-active');
		var data = {
			action: 'toolset_theme_integration_save_wpa_settings',
			id: WPViews.wpa_edit_screen.view_id,
			wpnonce: wpv_editor_strings.editor_nonce,
			theme_settings: $('#toolset_theme_settings_form').serialize()
		};
		self.makeAjaxCall(data,
			function (originalResponse) {
				WPViews.wpa_edit_screen.manage_action_bar_success(originalResponse.data);
				$('.toolset-theme-settings-spinner').removeClass('is-active');
				$(document).trigger('js_event_wpv_screen_options_saved');
			}, function (ajaxContext) {
				$('.toolset-theme-settings-spinner').removeClass('is-active');
				console.log('Error:', ajaxContext.responseText);
			}
		);
	};

	self.wordpress_archive_debounce_update = _.debounce( self.wordpressArchiveUpdateSettings, 2000 );

    self.eventsOn = function () {
        $(document).on('change', '.toolset_page_ct-editor #toolset_theme_settings_form', function () {
			self.content_template_debounce_update();
        });

        $(document).on('change', '.toolset_page_view-archives-editor #toolset_theme_settings_form', function () {
			self.wordpress_archive_debounce_update();
        });

        $(document).on('change', '.toolset_page_dd_layouts_edit #toolset_theme_settings_form', function (evt) {
            $('input[name="save_layout"]').prop('disabled', false);
        });

        $('.js-theme-options-hint').on('click', function () {
            $(this).pointer('open');
        });

        jQuery(document).on('change', '.js-layout-used-for-cred', function() {

            Toolset_Theme_Integrations_Settings.strings.assignment = $(this).val();
            if( $(this).val() === 'posts-cred' ) {
                self.renderVisibleSections( 'posts-cred', true );
            } else if( $(this).val() === 'archive-cred' ) {
                self.renderVisibleSections( 'archive-cred', true );
            } else {
                self.renderVisibleSections( null );
            }
        });

		$( document ).on( 'change', '.js-theme-option-switch-control', function() {
			var controlContainer = $( this ).closest( '.js-theme-option-switch-container' ),
				controlValue = controlContainer.find( '.js-theme-option-switch-control:checked' ).val(),
				controlInput = controlContainer.find( '.theme-option-switch-target-input' ),
			    colorPickerWrap = controlContainer.find( '.theme-options-color-picker-wrap' );

			if ( 'toolset_use_theme_setting' == controlValue ) {
				controlInput.prop( 'disabled', true );
                controlInput.val('');
                colorPickerWrap.hide();
			} else {
				controlInput.prop( 'disabled', false );
                colorPickerWrap.show();
			}
		});

		$( document ).on( 'click', '.js-toolset-theme-settings-toggle-settings', function() {
			var $toggler = $( this ),
				$toggling = $( '.js-toolset-theme-settings-toggling-settings' );

			$toggler.find( '.fa' ).toggleClass( 'fa-caret-down fa-caret-up' );
			$toggling.slideToggle();
		});
    }

    self.selfThroughLayoutsAjaxCall = function(){
        Toolset.hooks.addFilter('ddl_save_layout_params', function (save_params) {
            if ( $('#toolset_theme_settings_form') ) {
                save_params.theme_settings = $('#toolset_theme_settings_form .js-toolset-non-assigned-message input, #toolset_theme_settings_form fieldset.theme-settings-section:not(:hidden)').serialize();
            }
            return save_params;
        });
    };

    self.hookIntoLayoutAssignmentDialogClose = function(){
        jQuery(document).on('DLLayout.admin.ready', function () {
            if( !DDLayout || !DDLayout.changeLayoutUseHelper ) return;
            DDLayout.changeLayoutUseHelper.eventDispatcher.listenTo(
                DDLayout.changeLayoutUseHelper.eventDispatcher,
                'assignment_dialog_close',
                function () {
                    var data = {
                        action: 'toolset_theme_integration_get_section_display_type',
                        nonce: jQuery('#toolset-theme-display-type').val(),
                        id: DDLayout.individual_assignment_manager._current_layout
                    };

                    self.makeAjaxCall(data,
                        function (originalResponse) {
                            if (originalResponse.data.hasOwnProperty('display_type')) {
                                self.displayType = originalResponse.data.display_type;
                                self.renderVisibleSections( self.displayType );
                                self.adjustRadioOptionOnValueChange( self.displayType );
                            }

                            if( originalResponse.data.hasOwnProperty('tooltip_message') ) {
                                self.removeHints();
                                self.initHints(originalResponse.data.tooltip_message);
                            }
                        }
                    );
                }
            );
        });
    };

    self.adjustRadioOptionOnValueChange = function( value ){

        if(value === null){
            self.optionsVisibilityOnLayoutsEditorLoad();
        }

    };

    self.addLayoutsEditPageHooks = function(){
        if (jQuery('.toolset_page_dd_layouts_edit')[0]) {
            /**
             * Hooks to Layouts saving action and adds the theme settings object to the Layout object.
             */
            self.selfThroughLayoutsAjaxCall();
            self.hookIntoLayoutAssignmentDialogClose();
        }

        if( jQuery( "#toolset_theme_settings_form" )[0] ) {
            Toolset.hooks.addFilter('ddl-layout_preview_object', function(layout_model) {
                if(!layout_model.hasOwnProperty('toolset_theme_settings')) {
                    layout_model.toolset_theme_settings = {};
                }

                jQuery( "#toolset_theme_settings_form" ).serializeArray().map(function(theme_settings){layout_model.toolset_theme_settings[theme_settings.name] = theme_settings.value;});

                return layout_model;
            });
        }
    };

    self.toggleThemeOptionsBoxVisibility = function(){

		// execute only for layout page
        if( Toolset_Theme_Integrations_Settings.strings.current_page !== 'dd_layouts_edit' ){
            return;
        }

        jQuery(document).on( 'click', '.js-theme-settings-toggle', function( event ){
            var $me = jQuery( event.target ),
                closed = $me.data( 'closed' ),
                $target = jQuery( '#toolset_theme_settings_form' );

            if( !closed ){
                $target.slideUp( 'fast', function(event){
                    jQuery(this).addClass('hidden');
                    jQuery('.js-toolset-non-assigned-message').addClass('hidden').hide();
                    $me.data( 'closed', true );
                    $me.find('.fa').removeClass( 'fa-caret-up' ).addClass( 'fa-caret-down' );
                    $me.parent('.theme-settings-wrap').addClass('theme-settings-wrap-collapsed');
                });
            } else {
                $target.removeClass('hidden');
                $target.slideDown( 'fast', function(event){
                    jQuery(this).removeClass('hidden');
                    jQuery('.js-toolset-non-assigned-message').removeClass('hidden').show();
                    $me.data( 'closed', false );
                    $me.find('.fa').removeClass( 'fa-caret-down' ).addClass( 'fa-caret-up' );
					$me.parent('.theme-settings-wrap').removeClass('theme-settings-wrap-collapsed');

                        if( self.displayType ){
                            self.renderVisibleSections( self.displayType );
                            self.adjustRadioOptionOnValueChange( self.displayType );
                        }

                });
            }
        });
    };

    self.init();

    return self;
};

jQuery(function ($) {
    ToolsetCommon.theme_integration = new ToolsetCommon.ThemeIntegration($);
});
