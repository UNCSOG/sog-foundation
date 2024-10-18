// widget-area.js


jQuery(function($){

	DDLayout.WidgetArea = function($)
	{
		var self = this;

		self.loader = new WPV_Toolset.Utils.Loader;
		self.new_sidebar_message = $(".js-create-new-sidebar-message");
		self.widget_select_el = $(".js-widget-area-select-el");

		self.init = function() {

			jQuery(document).on('click', '.js-create-new-sidebar', {dialog: self}, function(event){
				event.stopImmediatePropagation();
				event.data.dialog.show_create_sidebar_controls( );
			});

			jQuery(document).on('click', '.js-cancel-create-new-sidebar', {dialog: self}, function(event){
				event.stopImmediatePropagation();
				event.data.dialog.hide_create_sidebar_controls( );
			});

			jQuery(document).on('change keyup input cut paste', '[name="ddl-sidebar-name"]', {dialog: self}, function(event) {
				var name = jQuery('[name="ddl-sidebar-name"]').val();
				jQuery('.js-create-the-new-sidebar').prop('disabled', name == '');

			});

			jQuery(document).on('ddl-default-dialog-open', '#ddl-default-edit', {dialog: self}, function(event) {
				// check if we have the right dialog.

				if (jQuery('#ddl-default-edit .js-create-new-sidebar-div').length) {
					event.data.dialog.hide_create_sidebar_controls();
			}
			});

			jQuery(document).on("click", ".js-edit-existing-area", {dialog:self}, function(event){
				event.preventDefault();
				if(!$(event.target).hasClass("disabled")){
					var selected_sidebar_id = jQuery(".js-widget-area-select-el option:selected").attr("val").replace("sidebar-", "");
					window.open(jQuery(event.target).attr("href").replace("{##}", selected_sidebar_id), '_blank');
				}
			});

			jQuery(document).on("change", ".js-widget-area-select-el", {dialog:self}, function(event){
				self.enabled_disable_edit_button();
			});

			jQuery(document).on("click", ".js-create-the-new-sidebar", {dialog:self}, function(event){

				self.loader.loadShow($(event.target), true);
				var create_sidebar_ajax_data = {action:"register_widget_area", sidebar_name: jQuery('[name="ddl-sidebar-name"]').val()};
				var passed_event = event;
				WPV_Toolset.Utils.do_ajax_post(create_sidebar_ajax_data,
				{
					success: function(response){
						//Add the newly created sidebar to the select list
						var sidebar_id = response.Data.data.new_sidebar_id;
						var sidebar_name = response.Data.data.new_sidebar_name;
						var sidebar_option_elm = "<option val='sidebar-"+sidebar_id+"' selected>"+sidebar_name+"</option>"
						jQuery(sidebar_option_elm).appendTo(self.widget_select_el);
						//Switch back to select view with the option selected and glow select box
						self.hide_create_sidebar_controls();
						self.glow_selectors(self.widget_select_el, "toolset-being-updated");
					},
					error: function(){
						jQuery(event.data.dialog.new_sidebar_message).wpvToolsetMessage({
                            text: response.message,
                            stay: false,
                            close: false,
                            type: 'error',
                            msPerCharacter: 50
                        });
					},
					fail: function(){
						jQuery(event.data.dialog.new_sidebar_message).wpvToolsetMessage({
                            text: "Error occurred, please try again!",
                            stay: false,
                            close: false,
                            type: 'error',
                            msPerCharacter: 50
                        });
					},
					always: function(){
						self.loader.loadHide();
					},

				});
			});

			jQuery(document).on("cell-widget-area.dialog-open", {dialog: self}, function(event){
				event.data.dialog.enabled_disable_edit_button();
			});

		};

		self.show_create_sidebar_controls = function() {
			jQuery('.js-create-new-sidebar-div input[type=text]').val('');
			jQuery('.js-create-the-new-sidebar').prop('disabled', true);

			// TODO: Review this. I don't know why to do it in such a strange way
			//jQuery('#ddl-default-edit .ddl-dialog-footer:not(.js-widget-area-footer)').hide();
			//jQuery('#ddl-default-edit .ddl-dialog-content-head').hide();
			jQuery('.js-create-new-widget-area-button').hide();
            jQuery('.existing-sidebars-div').hide();
            jQuery('.js-create-new-sidebar-div').removeClass('hidden').show();
			jQuery('.save-new-sidebar-buttons').removeClass('hidden').show();
		};

		self.hide_create_sidebar_controls = function() {
			jQuery('.js-create-new-widget-area-button').show();
			jQuery('.js-create-new-sidebar-div').addClass('hidden').hide();
			jQuery('.save-new-sidebar-buttons').addClass('hidden').hide();
			jQuery('.existing-sidebars-div').show();

		};

		self.glow_selectors = function( selectors, reason ) {
			$( selectors ).addClass( reason );
			setTimeout( function () {
				$( selectors ).removeClass( reason );
			}, 500 );
		};

		self.enabled_disable_edit_button = function(){
			if(jQuery(".js-widget-area-select-el").find("option:selected").text().indexOf("Layouts") == -1){
				jQuery(".js-edit-existing-area").addClass("disabled");
			}else{
				jQuery(".js-edit-existing-area").removeClass("disabled");
			}
		}

		self.init();
	};


    DDLayout.widget_area = new DDLayout.WidgetArea($);

});

