// child-cell.js


jQuery(document).on('DLLayout.admin.ready', function($){

	DDLayout.ChildCell = function($)
	{
		var self = this;
	
		self.init = function() {
			
			self._initialize_existing_child_layouts();
			
			jQuery('.js-create-new-child-layout').on('click', function (event) {

				self._target_cell_view = jQuery('#ddl-default-edit').data('cell_view');
			
				if (DDLayout.ddl_admin_page.is_save_required()) {
		
					var dialog = DDLayout.DialogYesNoCancel(DDLayout_settings.DDL_JS.strings.save_required_new_child,
														DDLayout_settings.DDL_JS.strings.save_before_creating_new_child,
														{'yes' : DDLayout_settings.DDL_JS.strings.save_layout_yes,
														'no' : DDLayout_settings.DDL_JS.strings.save_layout_no},
														function(result) {
															if (result == 'yes') {
																DDLayout.ddl_admin_page.save_layout(self.create_new_child);
															} else if (result == 'no') {
																self.create_new_child()
															}
														});
		
				} else {
				
					self.create_new_child()
				}
				
			});
			
			jQuery(document).on('child-layout.dialog-open', function(event) {
				if (jQuery('#ddl-default-edit').data('mode') == 'new-cell') {
					jQuery('.js-create-new-child-layout').hide();
				} else {
					jQuery('.js-create-new-child-layout').show();
				}
				
				var layout = DDLayout.ddl_admin_page.get_layout();
				if (layout.getChildrenToDelete()) {
					// Don't show the existing children list as we are
					// going to delete them when the layout is saved.
					jQuery('.js-child-layout-list').hide();	
				}

			});
			
		};
		
		self.create_new_child = function () {
		    DDLayout.ddl_admin_page.clean_up_default_dialog();
			
			var layout = DDLayout.ddl_admin_page.get_layout();
			
			var row = self._target_cell_view.get_parent_view();
			try{
				var type = row.model.get('layout_type');
			} catch( e ){
				var type = 'fluid';
			}

			
			DDLayout.new_layout_dialog.show_create_new_layout_dialog(layout.id,
																   layout.find_cell_of_type('child-layout').get('width'),
																   type);
		};
		
		self._initialize_existing_child_layouts = function () {

			jQuery('.js-switch-to-layout').on('click', function (event) {
				var layout_id = jQuery(this).data('layout-id');
				
				if (DDLayout.ddl_admin_page.is_save_required()) {
		
					var dialog = DDLayout.DialogYesNoCancel(DDLayout_settings.DDL_JS.strings.save_required_edit_child,
														DDLayout_settings.DDL_JS.strings.save_before_edit_child,
														{'yes' : DDLayout_settings.DDL_JS.strings.save_layout_yes,
														'no' : DDLayout_settings.DDL_JS.strings.save_layout_no},
														function(result) {
															if (result == 'yes') {
																DDLayout.ddl_admin_page.save_layout(DDLayout.ddl_admin_page.switch_to_layout(layout_id));
															} else if (result == 'no') {
																DDLayout.ddl_admin_page.switch_to_layout(layout_id);
															}
														});
		
				} else {
				
					DDLayout.ddl_admin_page.switch_to_layout(layout_id);
				}
				
			});
			
		};
	
		self.init();
	};

	
    DDLayout.child_cell = new DDLayout.ChildCell($);

});

