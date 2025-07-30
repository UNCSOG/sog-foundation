(function ($) {
    'use strict';

    DDLayout.views.ContainerView = DDLayout.views.abstract.ElementView.extend({
        template: _.template($('#tpl-toolset-frontend-container').html()),
        initialize: function (options) {
            DDLayout.views.abstract.ElementView.prototype.initialize.call(this, options);
            if (options.mock) {
                return;
            }
            this.listenTo(DDLayout.ddl_admin_page, 'hover-cell', this.close);
        },
        events: {},

        render: function () {
            var self = this,
                data = this.model.toJSON();
            data.context_menu = '';

            self.model.listenTo(self.model, 'ddl_layouts_element_dialog_loads', self.load_edit_dialog);

            var compiled = this.template(data);
            $(this.el).append(compiled);
            self.set_cell_offset();
            //console.log('mouse over container ' + data.name, data.kind, data.cell_type);
        },

        close: function () {
            var self = this;
            this.undelegateEvents();
            $(this.el).children('.ddl-frontend-editor-overlay').remove();
            self.model.stopListening(self.model, 'ddl_layouts_element_dialog_loads', self.load_edit_dialog);
        },
        editElement: function (e) {
            var self = this;
            e.stopImmediatePropagation();

            DDLayout.ddl_admin_page.trigger('open-'+self.model.get('kind')+'-dialog', 'edit', self, e.target);

            self.close();
        }
    });
})(jQuery)