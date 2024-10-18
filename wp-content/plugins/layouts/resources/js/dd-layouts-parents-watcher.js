var DDLayout = DDLayout || {};

DDLayout.ParentsWatcher = function ($, scope) {

    var self = this;

    self.observed = null;

    _.extend(self, Backbone.Events);

    self.init = function (scope) {
        self.set_up_listeners(scope);
    };

    self.set_up_listeners = function(scope){
        var main = scope;

        if( main && DDLayout.AdminPage && main instanceof DDLayout.AdminPage ){
            self.observed = main.instance_layout_view ? main.instance_layout_view.model : null;
        } else if( main && main instanceof DDLayout.ListingMain ){
            self.observed = main.listing_table_view ? main.listing_table_view.model : null;
        }

        if( self.observed !== null ){
            self.listenTo( self.observed, 'items-collection-after-remove-item', self.manage_layout_removed );
        }

        self.listenTo( self, 'deleted_child_layout', self.reload_select_data_after_delete );
        self.listenTo( self, 'deleted_child_layout', self.purge_layout_list_in_child_layout_dialog );
        self.listenTo( self, 'created_child_layout', self.reload_select_data_after_create_parent );

    };

    self.purge_layout_list_in_child_layout_dialog = function( parent_removed ){
            var $list = jQuery( '.js-child-layout-list'),
                $items_ul = $list.find('ul.js-tree-category-items');

            $items_ul.empty();
            $list.hide();
    };

    self.reload_select_data_after_create_parent = function(parent_created){
        var $selects = jQuery('.js-layouts-list'), layout_id = parent_created.id;

        $selects.each (function () {

            var $select = jQuery(this), exists = false;
            
            $select.find('option').each(function(i,v){
                if( layout_id == jQuery(v).val() ){
                    exists = true;
                    return;
                }
            });
    
            if( exists === false )
            {
                var name = parent_created.name,
                    $new_option = jQuery('<option value="'+layout_id+'">'+name+'</option>');
    
                $select.append($new_option);
                self.show_hide_select_group($select);
                
            }
        });

    };

    self.reload_select_data_after_delete = function( parent_removed ){

        var $selects = jQuery('.js-layouts-list'),
            layout_id = parent_removed.id;
            
        $selects.each ( function () {
            var $select = jQuery(this),
                $option = $select.find('option[value="'+layout_id+'"]');
                
            if ($option.length) {
                $option.remove();
                self.show_hide_select_group($select);
            }
        });
    };
    
    self.show_hide_select_group = function ($select) {
        var group = $select.data('show-group');
        
        if (group) {
            if ($select.find('option').length > 1) {
                jQuery('.' + group).show();
            } else {
                jQuery('.' + group).hide();
            }
        }
    }

    self.manage_layout_removed = function( model, options ){

        self.trigger( 'deleted_child_layout', model.toJSON() );
    };


    self.init();
};