(function ($) {
    'use strict';

    DDLayout.views.CellView = DDLayout.views.abstract.ElementView.extend({
        template: _.template($('#tpl-toolset-frontend-cell').html()),
        layout: null,
        context_menu: null,
        context_menu_opened: false,
        initialize: function (options) {

            var self = this;

            _.bindAll(self, 'load_edit_dialog', 'editElement', 'update_cell_content_in_page');

            DDLayout.views.abstract.ElementView.prototype.initialize.call(self, options);

            self.layout = options.layout;

            self.listenTo( self.model, 'cell-content-updated-in-dialog', self.update_cell_content_in_page);

            DDLayout.ddl_admin_page.trigger('hover-cell');
        },
        update_cell_content_in_page:function( content ){
            var self = this;
            self.$el.text( content );
        },
        handleClick: function (e) {
            // Close context menu if it opened and clicked inside cell overlay
            if (
                !jQuery.contains(this.$panel.get(0), e.target) &&
                e.target.dataset.action != 'list-settings' &&
                this.context_menu_opened
            ) {
                this.listSettings(e)
            }
        },
        // Stub for render element settings
        listSettings: function (e) {
            var self = this;
            self.$panel.toggleClass('active');

            if (self.context_menu_opened) {
                self.context_menu_opened = false;
                DDLayout.ddl_admin_page.trigger('context-menu-closed', e);
            } else {
                self.context_menu_opened = true;
                DDLayout.ddl_admin_page.trigger('context-menu-opened', e);
            }
        },
        render: function ( active ) {
            var self = this;

            self.close();

            self.model.listenTo(self.model, 'ddl_layouts_element_dialog_loads', self.load_edit_dialog);
            self.model.listenTo(self.model, 'ddl_layouts_element_open_dialog', self.editElement);

            $(self.el).append( this.template( self.model.toJSON() ) );

            self.context_menu = new DDLayout.views.ContextMenuView({
                id: self.model.get('id'),
                model:self.model
            });
            self.context_menu.setElement(self.$el.find('.ddl-frontend-editor-overlay'));
            self.context_menu.render();
            self.$panel = jQuery('.ddl-element-action-panel', self.$el);
            jQuery(self.$el).on('click', self.route_click_action.bind(self) );
            self.set_cell_offset();
            //console.log('Mouse over cell');
        },
        route_click_action: function (e){
            e.stopPropagation();
            var self = this;

            if (jQuery(e.target).data('action') === "list-settings") {
                self.listSettings(e);
            } else if (jQuery(e.target).data('action') === "edit-element") {
                self.editElement(e);
            } else {
                self.handleClick(e);
            }
        },
        close: function ( event ) {
            var self = this;
            self.undelegateEvents();
            self.$el.find('.ddl-frontend-editor-overlay').remove();
            self.$el.off('click');

            if( DDLayout.AdminPage.cell_reset_events_on_close ){
                self.model.stopListening(self.model, 'ddl_layouts_element_dialog_loads', self.load_edit_dialog);
            }

            self.model.stopListening(self.model, 'ddl_layouts_element_open_dialog', self.editElement);
            DDLayout.AdminPage.cell_reset_events_on_close = true;
        }
    })
})(jQuery)