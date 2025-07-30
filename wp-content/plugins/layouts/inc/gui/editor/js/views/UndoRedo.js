// UndoRedo.js

DDLayout.UndoRedo = function($)
{
    var self = this;

    self.undo_list = null;
    self.redo_list = null;
    self.current_snap_shot = null;
    self.$undoButton = jQuery('.js-undo-button');
    self.$redoButton = jQuery('.js-redo-button');

    self.init = function()
    {
        self.undo_list = new Array();
        self.redo_list = new Array();
        self.current_snap_shot = null;

        self.$undoButton
            .prop('disabled',true)
            .show();
        self.$redoButton
            .prop('disabled',true)
            .show();

		jQuery(document).on('click', '.js-undo-button', function(event){
            event.preventDefault();
            DDLayout.ddl_admin_page.do_undo();
        });
		jQuery(document).on('click', '.js-redo-button', function(event){
            event.preventDefault();
            DDLayout.ddl_admin_page.do_redo();
        });
    };

    self.take_undo_snapshot = function(modelJSON) {
        self.current_snap_shot = modelJSON;
    }

    self.add_snapshot_to_undo = function() {
        if (self.current_snap_shot) {
            self.undo_list.push({
                'type' : 'snap_shot',
                'data' : self.current_snap_shot
                });

            self.redo_list = new Array();
            self.show_or_hide_buttons();

            self.current_snap_shot = null;
            DDLayout.ddl_admin_page.set_save_required();
        }
    };

    self.save_undo = function(modelJSON) {
        self.undo_list.push({
            'type' : 'snap_shot',
            'data' : modelJSON
            });

        self.redo_list = new Array();
        self.show_or_hide_buttons();

        self.current_snap_shot = null;
        DDLayout.ddl_admin_page.set_save_required();
    };

    self.show_or_hide_buttons = function() {
        if (self.undo_list.length) {
         /*   jQuery('.js-ddl-message-container').wpvToolsetMessage({
                text: DDLayout_settings.DDL_JS.strings.save_required,
                stay: true,
                close: false,
                inline: true,
                type: 'info'
            }); */
            self.$undoButton.prop('disabled',false);
        }
        else {
            self.$undoButton.prop('disabled',true);
        }

        if (self.redo_list.length) {
            self.$redoButton.prop('disabled',false);
        }
        else {
            self.$redoButton.prop('disabled',true);
        }

    };

    self.handle_undo =  function() {
        if (self.undo_list.length) {

			DDLayout.views_preview.clear_cache();
		
            var redo = DDLayout.ddl_admin_page.get_layout_as_JSON();
            self.redo_list.push({
                'type' : 'snap_shot',
                'data' : redo
                });

            var undo = self.undo_list.pop();
            switch (undo['type']) {
                case 'snap_shot':
					DDLayout.ddl_admin_page.set_layout(undo['data']);
					break;
            }

            self.show_or_hide_buttons();
            DDLayout.ddl_admin_page.set_save_required();
			
        }
    };

    self.handle_redo =  function() {
        if (self.redo_list.length) {

			DDLayout.views_preview.clear_cache();

            var undo = DDLayout.ddl_admin_page.get_layout_as_JSON();
            self.undo_list.push({
                'type' : 'snap_shot',
                'data' : undo
                });

            var redo = self.redo_list.pop();
            switch (redo['type']) {
                case 'snap_shot':
	                DDLayout.ddl_admin_page.set_layout(redo['data']);
	                break;
            }

            self.show_or_hide_buttons();
            DDLayout.ddl_admin_page.set_save_required();
			
        }
    };

    self.init();
};