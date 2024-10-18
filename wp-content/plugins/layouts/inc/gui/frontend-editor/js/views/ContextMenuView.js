(function ($) {
    'use strict';

    DDLayout.views.ContextMenuView = Backbone.View.extend({
        template: _.template($('#tpl-toolset-frontend-context-menu').html()),
        prev_z_index:1,
        current_parent:null,
        events: {
            // Row hierarchy
            'mouseover [data-action="hover-parent"]': 'renderParentOverlay',
            'mouseout [data-action="hover-parent"]': 'clearParentOverlay',
            'click [data-action="hover-parent"]' : 'triggerClick'
        },
        initialize: function (options) {
            this.id = options.id;
            _.bindAll(this, 'current_on', 'current_out', 'trigger_this_cell_click');
        },
        getParent: function (el) {
            var id = el.dataset.targetId;
            var type = el.dataset.targetType;

            return $(el).parents('[data-type="' + type + '"][data-id="' + id + '"]').get(0);
        },
        renderParentOverlay: function (e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
            var parent = this.getParent(e.target),
                undefined;

            if( !parent ){
                return;
            }

            var parent_parent = null;

            if( $(e.target).prev().length ){
                parent_parent = this.getParent( $(e.target).prev()[0] );
            }

            try {
                DDLayout.ddl_admin_page.get_current().eventDispatcher.trigger('render-parent-overlay', parent, jQuery(e.target).data('target-class'), parent_parent );
                this.current_parent = parent;
            } catch (e) {
                console.log(e.message);
            }

        },
        clearParentOverlay: function (e) {
            e.stopPropagation();
            e.stopImmediatePropagation();
            var parent = this.getParent(e.target);

            if( !parent ){
                return;
            }

            try {
                DDLayout.ddl_admin_page.get_current().eventDispatcher.trigger( 'clear-parent-overlay', parent, jQuery(e.target).data('target-class'));
                this.current_parent = null;
            } catch (e) {
                console.log(e.message);
            }

        },
        triggerClick: function(event){
            // a comment
            event.stopPropagation();
            var self = this, view = null, model = null;

            if( jQuery(self.current_parent).data('view') instanceof DDLayout.views.abstract.ElementView === false ){
                return false;
            }

            view = jQuery(self.current_parent).data('view');
            view.editElement( event );
        },
        render: function () {
            var self = this,
                id = this.id,
                parents = this.$el.parents().toArray(),
                html = '',
                private_layout = null,
                current = DDLayout.ddl_admin_page.get_layout(),
                layouts = DDLayout.ddl_admin_page.get_layouts_as_array(),
                current_index = layouts.indexOf(current),
                css_class = '',
                layout_slugs = [],
                layout_names = [];

            if (current.is_private() && layouts.length > 1) {
                private_layout = DDLayout.ddl_admin_page.get_layout();
                _.each(layouts, function (layout, i) {
                    if (layout.is_private() === false && +current_index >= +i) {
                        var name = { title: layout.is_parent() ? DDLayout_settings.DDL_JS.strings.parent_layout_string : DDLayout_settings.DDL_JS.strings.template_layout_string, name : layout.get('name') };
                        layout_names.push( name );
                        layout_slugs.push( layout.get('slug') );
                    }
                });
            } else if (DDLayout.ddl_admin_page.get_layout().is_private() === false && layouts.length > 1) {
                _.each(layouts, function (layout, i) {
                    if (layout.is_private() === false && +current_index >= +i) {
                        var name = { title: layout.is_parent() ? DDLayout_settings.DDL_JS.strings.parent_layout_string   : DDLayout_settings.DDL_JS.strings.template_layout_string, name : layout.get('name') };
                        layout_names.push( name );
                        layout_slugs.push( layout.get('slug') );
                    }
                });
            } else {
                _.each(layouts, function (layout) {
                    var name = { title: layout.is_parent() ? DDLayout_settings.DDL_JS.strings.parent_layout_string   : DDLayout_settings.DDL_JS.strings.template_layout_string, name : layout.get('name') };
                    layout_names.push( name );
                    layout_slugs.push( layout.get('slug') );
                });
            }

            parents.reverse();

            parents.forEach(function (item, i) {
                if ($(item).hasClass('ddl-frontend-editor-row') || $(item).hasClass('ddl-frontend-editor-cell')) {
                    // if self
                    if (item.dataset.id == id) return;

                    if (item.dataset.type === '') {
                        item.dataset.type = 'element';
                    }

                    var index = layout_slugs.indexOf( item.dataset.layout_slug ),
                        display_name = item.dataset.name ? item.dataset.name : item.dataset.kind;
                    css_class = index !== -1 ? 'layout_'+index+' ' : '';

                    if( css_class ){
                        html += '<li  data-target-id="' + item.dataset.id + '"' +
                            ' data-target-class="' +css_class+ '"'+
                            ' data-target-type="' + item.dataset.type + '"' +
                            ' data-action="hover-parent" title="'+ item.dataset.kind +'" class="'+css_class+'ddl-dropdown-element-' + item.dataset.type + '">' + display_name + '</li>';
                    } else {
                        html += '<li  data-target-id="' + item.dataset.id + '"' +
                            ' data-target-class="private_layout"'+
                            ' data-target-type="' + item.dataset.type + '"' +
                            ' data-action="hover-parent" title="'+ item.dataset.kind +'" class="private_layout ddl-dropdown-element-' + item.dataset.type + '">' + display_name + '</li>';
                    }


                    if (null !== private_layout && item.dataset.type === 'cell-post-content') {
                        var private_data = _.where(DDLayout_settings.DDL_JS.layouts, {id: +private_layout.get('id')}),
                            title = private_data ? private_data[0].post_title : private_layout.get('name');
                        html += '<li  data-target-id="" ' +
                            ' data-target-type="" ' +
                            ' data-special="' + private_layout.get('slug') + '"' +
                            ' data-action="hover-parent" class="ddl-dropdown-element-private_layout ont-color-orange" title="'+ DDLayout_settings.DDL_JS.strings.content_layout_string+'">' + title + '</li>';
                    }
                }
            });

            if( null !== private_layout ){
                css_class = 'private_layout ';
            }

            html += '<li title="'+DDLayout_settings.DDL_JS.strings.this_cell+'" class="js-ddl-dropdown-element-current ddl-dropdown-element-current '+css_class+'" data-target-class="'+css_class+'">' +self.model.get("name")+ '</li>';

            this.$el.find('.ddl-block-overlay-actions').append(this.template({
                layout_names: layout_names
            }));

            this.$el.find('.js-hierarchy').html(html);

            this.handle_current_init_events();
        },
       trigger_this_cell_click:function(event){
            event.stopPropagation();
            var self = this;
            DDLayout.AdminPage.cell_reset_events_on_close = false;
            self.model.trigger('ddl_layouts_element_open_dialog', event );
       },
        handle_current_init_events: function () {
            var $el = jQuery('.ddl-dropdown-element-current', this.$el);

            $el.on('click', this.trigger_this_cell_click);
            $el.on('mouseover', this.current_on);
            $el.on('mouseout', this.current_out);
        },
        close: function () {
            var $el = jQuery('.ddl-dropdown-element-current', this.$el);
            $el.off('mouseover', this.current_on);
            $el.off('mouseout', this.current_out);
            $el.off('click', this.trigger_this_cell_click);
            this.undelegateEvents();
        },
        current_on:function (event) {
            event.stopPropagation();
            var self = this;
            self.prev_z_index = jQuery('.ddl-frontend-editor-overlay-cell').css('z-index');
            jQuery('.ddl-frontend-editor-overlay-cell .ddl-block-overlay-title').show();
            var rgba = "rgba(0, 0, 0, .2)";

            if( jQuery(event.target).data('target-class') == 'layout_0 ' ){
                rgba = "rgba(0, 153, 153, .2)";
                jQuery('.ddl-frontend-editor-overlay-cell .ddl-block-overlay-title').css('background', "rgb(0, 153, 153)");
            } else if( jQuery(event.target).data('target-class') == 'layout_1 ' ){
                rgba = "rgba(153, 153, 153, .2)";
                jQuery('.ddl-frontend-editor-overlay-cell .ddl-block-overlay-title').css('background', "rgb(153, 153, 153)");
            } else if( jQuery(event.target).data('target-class') == 'layout_2 ' || jQuery(event.target).data('target-class') == 'private_layout ' ){
                rgba = "rgba(240, 90, 40, .2)";
                jQuery('.ddl-frontend-editor-overlay-cell .ddl-block-overlay-title').css('background', "rgb(240, 90, 40)");
            }

            jQuery('.ddl-frontend-editor-overlay-cell').css({
                background: rgba
            });
        },
        current_out:function (event) {
            event.stopPropagation();
            var self = this;
            jQuery('.ddl-frontend-editor-overlay-cell .ddl-block-overlay-title').hide();
            jQuery('.ddl-frontend-editor-overlay-cell').css({
                background: 'none'
            });
        }
    })
})(jQuery)