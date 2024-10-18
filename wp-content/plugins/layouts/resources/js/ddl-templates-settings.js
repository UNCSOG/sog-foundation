var DDLayout = DDLayout || {};

DDLayout.TemplatesSettings = function($){
        var self = this,
            default_option_name = TemplatesSettingsData.Data.default_option_name,
            $messages = $('.template-settings-messages-wrap'),
            default_message_name = TemplatesSettingsData.Data.default_message_name,
            default_layout_name = TemplatesSettingsData.Data.default_layout_name,
            $default_option_name = $('input[name="'+default_option_name+'"]'),
            $default_layout = $('select[name="'+default_layout_name+'"]'),
            $default_message = $('textarea[name="'+default_message_name+'"]'),
            default_value = TemplatesSettingsData.Data.default_value,
            default_option_user_name = TemplatesSettingsData.Data.default_option_user_name,
            $default_option_user = $('input[name="'+default_option_user_name+'"]'),
            default_user_value = TemplatesSettingsData.Data.default_user_value,
            $button = $('.js-template_settings-save'),
            default_message_value = TemplatesSettingsData.Data.default_message_value;

        self.option_value = default_value;

        self.init = function(){
            $default_layout.toolset_select2({'width' : '300px'});
            self.handle_change();
            $button.on('click', self.do_ajax);
        };

        self.handle_change = function(){
            $default_option_name.on('change', function(event){
                if( $(event.target).is(':checked') ){
                    self.option_value = $(this).val();
                }

                if( default_value == self.option_value ){
                    self.enable_button();
                } else {
                    if( self.option_value == 3 && $default_layout.val() == '' ){
                        self.disable_button();
                    } else {
                        self.enable_button();
                    }

                }

                if( self.option_value == 1 ){
                    $default_message.parent().show('slow');
                    $default_layout.parent().hide('slow');
                } else if( self.option_value == 2 ){
                    $default_message.parent().hide('slow');
                    $default_layout.parent().hide('slow');
                } else if( self.option_value == 3 ){
                    $default_layout.parent().show('slow');
                    $default_message.parent().hide('slow');
                }
            });

            $default_layout.on('change', function(){
                if( $(this).val() == '' ){
                    self.disable_button();
                } else {
                    self.enable_button();
                }
            });

            $default_option_user.on('change', function(){
                if( $(this).val() == default_user_value ){
                    self.disable_button();
                } else {
                    self.enable_button();
                }

            });

            $default_message.on('change', function(){
                if( $(this).val() === default_message_value ){
                    self.disable_button();
                } else {
                    self.enable_button();
                }
            });
        };

        self.enable_button = function(){
            $button.prop('disabled', false).removeClass('button-secondary').addClass('button-primary');
        };

        self.disable_button = function(){
            $button.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
        };

        self.do_ajax = function(event){
            var params = {
                action:default_option_name,
                ddl_templates_settings_nonce:TemplatesSettingsData.Data.ddl_templates_settings_nonce
            };

            $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

            params[default_option_name] = self.option_value;

            params[default_option_user_name] = $('input[name="'+default_option_user_name+'"]:checked').val();

            if( self.option_value == 1 ){
                params[default_message_name] = $default_message.val();
            } else if( self.option_value == 3 ){
                params[default_layout_name] = $default_layout.val();
            }


            WPV_Toolset.Utils.do_ajax_post(params, {
                success:function( response ){

                    if( response.Data.error  ){

                        $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );

                    } else {

                        $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );

                        default_value = self.option_value;

                        default_user_value = params[default_option_user_name];

                        if( params.hasOwnProperty('default_message_name') ){
                            default_message_value = params[default_message_name];
                        }

                        self.disable_button();
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

        self.init();

};

(function($){
    $(function(){
        DDLayout.TemplatesSettings.call({}, $);
    });
}(jQuery));