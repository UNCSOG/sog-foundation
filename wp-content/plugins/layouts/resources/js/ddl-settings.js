var DDLayout = DDLayout || {};

DDLayout.LayoutsSettingsScreen = function( $ ) {

	var self = this, amount_posts, $button_amount = $('.js-max-posts-num-save'), $input_amount = $('.js-ddl-max-posts-num');

	/**
	* --------------------
	* Toolset Admin Bar Menu
	* --------------------
	*/

	self.toolset_admin_bar_menu_state = ( $( '#js-wpv-toolset-admin-bar-menu' ).length > 0 ) ? $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' ) : false;

    self.handle_admin_bar_option_change = function(){
        $( '#js-wpv-toolset-admin-bar-menu' ).on( 'change', function() {
            var thiz = $( this ),
                thiz_container = thiz.closest( '.js-wpv-setting-container' ),
                thiz_save_button = thiz_container.find( '.js-wpv-toolset-admin-bar-menu-settings-save' );
            if ( thiz.prop( 'checked' ) == self.toolset_admin_bar_menu_state ) {
                thiz_save_button
                    .addClass( 'button-secondary' )
                    .removeClass( 'button-primary' )
                    .prop( 'disabled', true );
            } else {
                thiz_save_button
                    .addClass( 'button-primary' )
                    .removeClass( 'button-secondary' )
                    .prop( 'disabled', false );
            }
        });
    };

	self.handle_admin_bar_option_save = function(){
        $( '.js-wpv-toolset-admin-bar-menu-settings-save' ).on( 'click', function( e ) {
            e.preventDefault();
            var thiz = $( this ),
                spinnerContainer = $('<div class="wpv-spinner ajax-loader">').insertBefore( thiz ).show(),
                thiz_container = thiz.closest( '.js-wpv-setting-container' ),
                thiz_messages_container = thiz_container.find( '.js-wpv-messages' ),
                data = {
                    action: 'ddl_update_toolset_admin_bar_menu_status',
                    status: $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' ),
                    wpnonce: $('#ddl_toolset_admin_bar_menu_nonce').val()
                };
            $.ajax({
                async: false,
                type: "POST",
                dataType: "json",
                url: ajaxurl,
                data: data,
                success: function( response ) {
                    if ( response.success ) {
                        self.toolset_admin_bar_menu_state = $( '#js-wpv-toolset-admin-bar-menu' ).prop( 'checked' );
                        thiz
                            .addClass( 'button-secondary' )
                            .removeClass( 'button-primary' )
                            .prop( 'disabled', true );
                        thiz_messages_container
                            .wpvToolsetMessage({
                                text: DDL_Settings_JS.setting_saved,
                                type: 'success',
                                inline: true,
                                stay: false
                            });
                    }
                },
                error: function (ajaxContext) {
                    //console.log( "Error: ", ajaxContext.responseText );
                },
                complete: function() {
                    spinnerContainer.remove();
                }
            });
        });
    };

    /**
     * --------------------
     * WP_Query Limit
     * --------------------
     */

	self.init_post_amount = function(){
		amount_posts = +$input_amount.val();

        $input_amount.on('change', function(){
				if( +$(this).val() !== amount_posts ){
                    $button_amount.addClass( 'button-primary' )
						.removeClass( 'button-secondary' )
						.prop( 'disabled', false );
				} else {
                    $button_amount.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
				}
		});

        self.do_change_posts_amount();
	};

    self.do_change_posts_amount = function(){
        $input_amount.on('change', function(){
            var amount_nonce = $('#ddl_max-posts-num_nonce').val(),
                thiz = $( this );

            amount_posts = +$input_amount.val();

            var data = {
                'ddl_max-posts-num_nonce':amount_nonce,
                action:'ddl_set_max_posts_amount',
                amount_posts:amount_posts
            };

            $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

            WPV_Toolset.Utils.do_ajax_post(data, {
                success:function( response, params ){
                    var res = response.Data;

                    if( res.message ){

                        $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );

                    } else if( res.error){

                        $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

                    }
                    amount_posts = res.amount;
                },
                error:function( response, params ){

                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

                }
            })
        })
    };

    self.init_cell_details_settings = function(){
        $( 'input[type=radio][name=ddl-ddl-show-cell-details]' ).on( 'change', function(){

            var showCellDetails = $(this).val();
            var showCellDetailsNonce = $('#ddl_cell-details_nonce').val();

            var data = {
                'ddl_cell-details_nonce' : showCellDetailsNonce,
                action : 'ddl_set_cell_details_settings',
                show_cell_details : showCellDetails
            };


            $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

            WPV_Toolset.Utils.do_ajax_post(data, {
                success:function( response, params ){
                    var res = response.data.Data;
                    if( res.message ){
                        $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
                    } else if( res.error){
                        $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
                    }
                    amount_posts = res.amount;
                },
                error:function( response, params ){
                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
                }
            });

        })
    };

    self.handle_background_change = function(){
        if( /#toolset-admin-bar-settings$/.test( window.location.href ) ) {
            $( '#toolset-admin-bar-settings' ).parent().css( 'background-color', '#ffffca' );
        }
    };


	self.init = function() {
        self.handle_admin_bar_option_change();
        self.handle_admin_bar_option_save();
        self.init_post_amount();
        self.init_cell_details_settings();
        self.handle_background_change();
        DDLayout.ParentLayoutsSettings.call(self, $);
        DDLayout.BootstrapColumnWidthSettings.call(self, $);
        DDLayout.JSGlobalOptions.call( self, $ );
        DDLayout.CSSGlobalOptions.call( self, $ );
	};

	self.init();

};

DDLayout.ParentLayoutsSettings = function($){
    var self = this,
        default_parent = DDL_Settings_JS.parent_default,
        $default_parent = $('select[name="'+DDL_Settings_JS.parent_option_name+'"]');

    self.init = function(){
        $default_parent.toolset_select2({'width' : '300px'});
        self.handle_change();
    };


    self.do_ajax = function(event){
        var params = {
            action:DDL_Settings_JS.parent_option_name,
            'parents_options_nonce' :DDL_Settings_JS.parent_settings_nonce,
            parents_options : $('select[name="'+DDL_Settings_JS.parent_option_name+'"]').val()
        };

        $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

        WPV_Toolset.Utils.do_ajax_post(params, {
            success:function( response ){

                if( response.Data.error  ){

                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

                } else {

                    $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );

                    default_parent = response.Data.value;
                }

            },
            error:function(response){


                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

            },
            fail:function(response){
                console.error( 'Fail', response );

                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
            }
        });
    };

    self.handle_change = function(){
        $default_parent.on('change', self.do_ajax);
    };

    self.init();
};

DDLayout.BootstrapColumnWidthSettings = function($){
    var self = this,
        default_column = DDL_Settings_JS.column_default,
        $default_column = $('input[name="'+DDL_Settings_JS.column_option_name+'"]');

    self.init = function(){
        self.handle_change();
    };


    self.do_ajax = function(event){
        var params = {
            action:DDL_Settings_JS.column_option_name,
            'column_prefix_nonce' :DDL_Settings_JS.column_settings_nonce,
            column_prefix : $('input[name="'+DDL_Settings_JS.column_option_name+'"]:checked').val()
        };

        $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

        WPV_Toolset.Utils.do_ajax_post(params, {
            success:function( response ){

                if( response.Data.error  ){

                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

                } else {

                    $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );

                    default_column = response.Data.value;
                }

            },
            error:function(response){


                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

            },
            fail:function(response){
                console.error( 'Fail', response );

                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
            }
        });
    };

    self.handle_change = function(){
        $default_column.on('change', self.do_ajax);
    };

    self.init();
};

DDLayout.JSGlobalOptions = function( $){
    var self = this,
        option_name = DDL_Settings_JS.js_settings_option_name,
        $input_element = $('input[name="' + option_name + '"]'),
        current_value = DDL_Settings_JS.js_settings_value;

    self.init = function(){
        self.handle_change();
    };


    self.do_ajax = function(event){
        var params = {
            action: option_name,
            'js_global_nonce' : DDL_Settings_JS.js_settings_nonce,
            js_global : $('input[name="'+option_name+'"]:checked').val()
        };

        $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

        WPV_Toolset.Utils.do_ajax_post(params, {
            success:function( response ){

                if( response.Data.error  ){

                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

                } else {

                    $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );

                    current_value = response.Data.value;
                }

            },
            error:function(response){


                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

            },
            fail:function(response){
                console.error( 'Fail', response );

                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
            }
        });
    };

    self.simplifyValuesToBeSent = function( originalValue ){

    };

    self.handle_change = function(){
        $input_element.on('change', self.do_ajax);
    };

    self.init();
};

DDLayout.CSSGlobalOptions = function( $){
    var self = this,
        option_name = DDL_Settings_JS.css_settings_option_name,
        $input_element = $('input[name="' + option_name + '"]'),
        current_value = DDL_Settings_JS.css_settings_value;

    self.init = function(){
        self.handle_change();
    };


    self.do_ajax = function(event){
        var params = {
            action: option_name,
            'css_global_nonce' : DDL_Settings_JS.css_settings_nonce,
            css_global : $('input[name="'+option_name+'"]:checked').val()
        };

        $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

        WPV_Toolset.Utils.do_ajax_post(params, {
            success:function( response ){

                if( response.Data.error  ){

                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

                } else {

                    $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );

                    current_value = response.Data.value;
                }

            },
            error:function(response){


                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

            },
            fail:function(response){
                console.error( 'Fail', response );

                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
            }
        });
    };

    self.simplifyValuesToBeSent = function( originalValue ){

    };

    self.handle_change = function(){
        $input_element.on('change', self.do_ajax);
    };

    self.init();
};

jQuery( function( $ ) {
    DDLayout.layouts_settings_screen = new DDLayout.LayoutsSettingsScreen( $ );
});
