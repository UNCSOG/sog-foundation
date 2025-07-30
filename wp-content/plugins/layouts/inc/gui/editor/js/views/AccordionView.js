DDLayout.views.AccordionView = DDLayout.views.ContainerView.extend({
    initialize:function(options)
    {
        var self = this;
        //call parent constructor
        DDLayout.views.ContainerView.prototype.initialize.call(self, options);
        self.$el.addClass('container');
    },
    _initializeEditContainerHandler:function()
    {
        var self = this;
        jQuery( ".js-container-edit", self.el ).on('click', function(event){
            event.stopImmediatePropagation();
            DDLayout.ddl_admin_page.show_accordion_dialog('edit', self);
        });
    }
});