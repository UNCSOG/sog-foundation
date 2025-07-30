// layout-settings-dialog.js

DDLayout.LayoutSettingsDialog = function($)
{
    var self = this, parent_default;

    self.init = function() {

        jQuery(document).on('click', '.js-edit-layout-settings', {}, function(event) {
            Toolset.hooks.doAction( 'ddl-parent-layout-settings-dialog-opens', event );
            parent_default = Toolset.hooks.applyFilters( 'ddl-parent-layout-default-changed', DDL_Settings_JS.parent_default);
            self.show();
        });
        
        jQuery('#ddl-layout-settings-dialog .js-save-dialog-settings').on('click', self._save_settings);

        jQuery('#ddl-layout-settings-dialog .js-item-name').on('click', self._select_parent);
        
    };

    self.show = function() {

        jQuery.colorbox({
            href: '#ddl-layout-settings-dialog',
            closeButton:false,
            onComplete: function() {
                jQuery('#ddl-layout-settings-dialog .js-diabled-fluid-rows-info').hide();
                
                self._layout = DDLayout.ddl_admin_page.get_layout();

                self._fill_width_select_with_available();
                
                // set the current layout type radio
                self._layout_type = self._layout.getType();
                jQuery('input[name="ddl-layout-settings-layout-type"]').each( function () {
					jQuery(this).prop('checked', jQuery(this).val() == self._layout_type);
                })
                
                // Disable changing to fluid if the layout has a container with fixed width.
                if (self._layout_type == 'fixed') {
                    jQuery('input[name="ddl-layout-settings-layout-type"]').prop('disabled', false);
                    var containers = self._layout.getLayoutContainers();
                    for (var i = 0; i < containers.length; i++) {
                        var container = containers[i];
                        if (container.hasRowsOfKind('fixed')) {
                            jQuery('input[name="ddl-layout-settings-layout-type"]').prop('disabled', true);
                            jQuery('#ddl-layout-settings-dialog .js-diabled-fluid-rows-info').show();
                            break;
                        }
                    }
                }
                
                self._handle_layout_type_change(self._layout_type);
                
                jQuery('input[name="ddl-layout-settings-layout-type"]').on('change', function () {
                    self._handle_layout_type_change(jQuery(this).val());
                });
                
                self._initialize_parent();
                
            },
            onCleanup: function() {
            }
        });
    };

    self._save_settings = function () {
        var selected_layout_type = jQuery('input[name="ddl-layout-settings-layout-type"]:checked').val();

        DDLayout.ddl_admin_page.take_undo_snapshot();
        
        var something_changed = false;
        
        // Save layout type
        if (selected_layout_type != self._layout_type) {
            
            self._layout.changeLayoutType(selected_layout_type);
            something_changed = true;
        }
        
        // Save width
        var new_width = jQuery('select[name="ddl-layout-width"]').val();
        if (selected_layout_type == 'fixed' && self._layout.get_width() != new_width) {
            
            self._layout.changeWidth(new_width);
            something_changed = true;
        }

        // Save parent
        var new_parent = jQuery('#ddl-layout-settings-dialog .js-item-name.selected').data('layout-slug');

        if (new_parent != self._layout.get_parent_layout()) {
            self._layout.set_parent_layout(new_parent);
            DDLayout.ddl_admin_page.render_all();
            something_changed = true;
        }
        
        // set default parent
        var new_default_parent_layout = jQuery('#set_as_default_layout');
        if(new_default_parent_layout.is(':checked') === true && new_default_parent_layout.is(':disabled') === false){
             var data = {
                action:DDL_Settings_JS.parent_option_name,
                parents_options_nonce :DDL_Settings_JS.parent_settings_nonce,
                parents_options : jQuery('#ddl-layout-settings-dialog .js-item-name.selected').data('layout-id')
            };
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function(data) {
                    parent_default = Toolset.hooks.applyFilters( 'ddl-set-parent-layout-default',  jQuery('#ddl-layout-settings-dialog .js-item-name.selected').data('layout-id') );
                }
            });
            something_changed = true;   
        }
        
        
        
        if (something_changed) {
            DDLayout.ddl_admin_page.add_snapshot_to_undo();
        }
        
        jQuery.colorbox.close();
    }
    
    self._handle_layout_type_change = function (layout_type) {
        
        if (layout_type == 'fluid') {
            jQuery('select[name="ddl-layout-width"]').val(12);
            jQuery('select[name="ddl-layout-width"]').prop('disabled', true);
            jQuery('.js-diabled-width').show();
        } else if (layout_type == 'fixed') {
            jQuery('select[name="ddl-layout-width"]').val(self._layout.get_width());
            jQuery('select[name="ddl-layout-width"]').prop('disabled', false);
            jQuery('.js-diabled-width').hide();
            self._fill_width_select_with_available();
        }
    }
    
    self._fill_width_select_with_available = function () {
        var min_width = self._layout.getMinWidth();
        jQuery('select[name="ddl-layout-width"] > option').each( function () {
            jQuery(this).prop('disabled', jQuery(this).val() < min_width);
        })
    }
    
    self._initialize_parent = function () {
        var parent = self._layout.get_parent_layout();
        var currently_selected_layout_id = '';
        
        jQuery('#ddl-layout-settings-dialog .js-item-name').each( function () {
            if (jQuery(this).data('layout-slug') == parent) {
                jQuery(this).addClass('selected');
                currently_selected_layout_id = jQuery(this).attr('data-layout-id');
            } else {
                jQuery(this).removeClass('selected');
            }
        });
        
        // enable or disable checkbox
        self._enable_disable_default_parent_option(currently_selected_layout_id);
        
        // Disable selecting the current layout as a parent.
        var slug = self._layout.get_slug();
        jQuery('#ddl-layout-settings-dialog .js-item-name').each( function () {
            if (jQuery(this).data('layout-slug') == slug) {
                    self._disable_layout_as_parent(this);
            }
        });
    }
    
	self._disable_layout_as_parent = function (parent) {

		// disable all the children to stop circulation
		jQuery(parent).closest('.js-tree-category-item').find('ul').each ( function () {
			jQuery(this).find('.js-item-name').each (function () {
				self._disable_layout_as_parent(this);
			})
		})
		
		jQuery(parent).parent().replaceWith(jQuery('<span>' + jQuery(parent).parent().html() + '</span>'));
		
	}
        
        
        self._enable_disable_default_parent_option = function(currently_selected_layout_id){
            // enable - disable checkbox for default layout
            jQuery("#set_as_default_layout").attr("checked", false);
            if(currently_selected_layout_id === ''){
                jQuery("#set_as_default_layout").attr("disabled", true);
                jQuery("#set_as_default_layout").attr("checked", false);
            } else {
                jQuery("#set_as_default_layout").removeAttr("disabled");
                // in case if user select already selected default layout
                if( parent_default == currently_selected_layout_id){
                    jQuery("#set_as_default_layout").attr("checked", true);
                    jQuery("#set_as_default_layout").attr("disabled", true);
                }
            }

            
        },
	
    self._select_parent = function (event) {
        
        var currently_selected_layout_id = jQuery(this).attr('data-layout-id');  
        self._enable_disable_default_parent_option(currently_selected_layout_id);
        jQuery('#ddl-layout-settings-dialog .js-item-name').removeClass('selected');
        jQuery(this).addClass('selected');

    }
    
    self.init();
};