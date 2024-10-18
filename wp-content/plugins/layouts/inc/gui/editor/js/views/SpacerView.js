DDLayout.views.SpacerView = DDLayout.views.CellView.extend({
    defaultCssClass:'spacer',
    initialize: function (options) {
        var self = this;
	    self.options = options;
        //call parent constructor
        DDLayout.views.CellView.prototype.initialize.call(self);
        jQuery(self.el).addClass('cell');
    }
});