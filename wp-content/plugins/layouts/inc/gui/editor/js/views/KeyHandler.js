// KeyHandler.js

DDLayout.KeyHandler = function($)
{
    var self = this;

    self.init = function()
    {
        jQuery(document).on( 'keydown', self.registerKeyPress );
    };

	self.registerKeyPress = function (event) {
        // don't handle key press if we have a popup.
        if (!jQuery('#cboxOverlay').is(':visible') && !jQuery('input').is(':focus')) {
            event.stopImmediatePropagation();
            self.handle_key_press(event);
        }
    };
    self.handle_key_press = function (event) {
		//event.preventDefault();

        switch(event.key) {
            case "z": // Ctrl-z
            case "Z": // Ctrl-Z
                if (event.ctrlKey) {
                    event.preventDefault();
                    DDLayout.ddl_admin_page.do_undo();
                }
                break;

            case "y": // Ctrl-y
            case "Y": // Ctrl-Y
                if (event.ctrlKey) {
                    //to avoid problems in Chrome since Ctrl-y is its shortcut for History
                    event.preventDefault();
                    DDLayout.ddl_admin_page.do_redo();
                }
                break;

        }

        switch(event.key) {
            case "ArrowLeft": // Left arrow
                DDLayout.ddl_admin_page.move_selected_cell_left(event);
                break;

            case "ArrowRight": // Right arrow
                DDLayout.ddl_admin_page.move_selected_cell_right(event);
                break;

            case "Delete": // Delete key
                DDLayout.ddl_admin_page.delete_selected_cell(event);
                break;
        }
    };

	self.destroy = function()
	{
		jQuery(document).off( 'keydown', self.registerKeyPress );
	}

    self.init();
};
