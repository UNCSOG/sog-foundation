(function ($) {
  'use strict';
  DDLayout.views.RowView = DDLayout.views.ContainerView.extend({
    is_top_level_row : function () {
      return this instanceof DDLayout.views.ContainerRowView === false;
    }
  });
})(jQuery)