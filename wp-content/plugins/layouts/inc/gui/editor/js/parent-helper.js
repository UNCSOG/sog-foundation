var DDLayout = DDLayout || {};

DDLayout.ParentHelper = function( model ){
    var self = this,
        default_parent = DDLayout_settings.DDL_JS.default_parent,
        $button = null,
        $green = null,
        layout = model;

    self.init = function () {

        $button = jQuery('.js-set-as-parent-layout-button');
        $green = jQuery('.js-is-parent-layout-button');

        if( self.is_parent() === false ) return;

        self.set_default_visible();

        Toolset.hooks.addFilter( 'ddl-set-parent-layout-default', self.handle_parent_settings_changed);
        Toolset.hooks.addFilter( 'ddl-parent-layout-default-changed', self.get_default_parent);
    };

    self.is_default = function(){
        return  +default_parent === +self.layout_id();
    };

    self.set_default = function( id ){
        default_parent = id;
    };

    self.get_default_parent = function(){
        return default_parent;
    };

    self.layout_id = function(){
        return model.get('id');
    };

    self.handle_parent_settings_changed = function( parent_id ){
        self.set_default(parent_id);
        self.set_default_visible();
        return parent_id;
    };

    self.set_default_visible = function(){
        self.kill_button();
        if( self.is_default() ){
            $button.hide({
                duration:'fast',
                always:self.kill_button,
                complete:function(){
                    $green.show(function(event){
                        $button.hide('fast', self.kill_button);
                    });
                }
            });
        }  else {
            $green.hide('fast', function(){
                $button.show('fast', self.handle_button);
            });
        }
    };

    self.set_invisible = function(){
        $green.hide();
        $button.hide('fast', self.kill_button);
    };

    self.is_parent = function(){
        return model.is_parent();
    };

    self.handle_button = function(event){
        self.kill_button();
        jQuery(document).on('click', '.js-set-as-parent-layout-button', self.ajax);
    };

    self.kill_button = function(event){
        jQuery(document).off('click', '.js-set-as-parent-layout-button', self.ajax);
    };

    self.ajax = function(event){

        event.stopPropagation();

        var data = {
            action:DDLayout_settings.DDL_JS.parent_option_name,
            parents_options_nonce :DDLayout_settings.DDL_JS.parents_options_nonce,
            parents_options : self.layout_id()
        };

        DDLayout.ddl_admin_page.saving_saved.show_saving();

        WPV_Toolset.Utils.do_ajax_post(data, {
            success:function(response, params){
                if( response.Data && response.Data.value ){
                    self.set_default( response.Data.value );
                    DDLayout.ddl_admin_page.saving_saved.swap_to_saved();
                } else if( response.Data && response.Data.error){
                    DDLayout.ddl_admin_page.saving_saved.swap_to_no_changes()
                }
            },
            error:function(response, params){
                DDLayout.ddl_admin_page.saving_saved.swap_to_problem();
            },
            fail:function(textStatus, errorThrown, params){
                DDLayout.ddl_admin_page.saving_saved.swap_to_problem();
            },
            always:function(args, params){
                self.set_default_visible();
            }
        });
    };

    self.reset = function(){
        self.kill_button();
    };
};
