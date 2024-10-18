// DDLayoutsFrontendEditor = localization object from wp_localize

(function ($) {
    'use strict';

    DDLayout.views.FrontendEditorToolbarView = Backbone.View.extend({
        el: $('body'),
        template: _.template($('#tpl-toolset-frontend-toolbar').html()),
        initialize: function () {
            this.close();
            this.render();
        },
        events: {
            'click [data-action="history-undo"]': 'historyUndo',
            'click [data-action="history-redo"]': 'historyRedo',
            //'click [data-action="cancel"]': 'reloadEditor',
            'click [data-action="save-layout"]': 'saveLayout',
        },
        reloadEditor:function( event ){
            if( jQuery(event.target).data('action') === 'cancel' ){
                event.preventDefault();
                window.location.href = window.location.href;
            } else{
                return true;
            }
        },
        // Stub for undo last action
        historyUndo: function (e) {
            console.log(e.currentTarget.dataset.action + ' event triggered');
        },
        // Stub for redo last action
        historyRedo: function (e) {
            console.log(e.currentTarget.dataset.action + ' event triggered');
        },
        // Stub for open back-end editor
        editBackend: function (e) {
            console.log(e.currentTarget.dataset.action + ' event triggered');
        },
        // Stub for saving the layout
        saveLayout: function (e) {
            //console.log(e.currentTarget.dataset.action + ' event triggered');
            DDLayout.ddl_admin_page.trigger( 'save_layout', this );
        },
        render: function () {
            var self = this,
                compiled = this.template({
                strings: DDLayout_settings.DDL_JS.strings.toolbar,
            })
            $(this.el).append(compiled);
            jQuery('.js-ddl-button-done-anchor').on('click', self.reloadEditor)
        },
        close: function () {
            $('.tl-builder-bar', this.el).remove();
        }
    })
})(jQuery)