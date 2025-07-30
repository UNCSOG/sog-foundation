DDLayout.ContainerDialog = function($)
{
	var self = this;

    _.extend( DDLayout.ContainerDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

	self.init = function() {

		jQuery(document).on('click', '.js-container-dialog-edit-save, .js-container-dialog-edit-add-container', {dialog: self}, function(event) {
			event.preventDefault();
			event.data.dialog._save( jQuery(this) );
		});
	};

	self._save = function(caller)
	{

		var target_container_view = jQuery('#ddl-container-edit').data('container_view');

		if (jQuery('#ddl-container-edit').data('mode') == 'edit-container') {

			DDLayout.ddl_admin_page.save_undo();

			var target_container = target_container_view.model;

			target_container.set('name', jQuery('input[name="ddl-container-edit-container-name"]').val());
			target_container.set('cssId', jQuery('input.js-edit-css-id', jQuery('#ddl-container-edit') ).val());
			target_container.set('tag', jQuery('select.js-ddl-tag-name', jQuery('#ddl-container-edit') ).val());

			var css_classes_tosave = jQuery('select[name="ddl-container-edit-class-name"]', jQuery("#ddl-container-edit")).val();
			setTimeout(function(){
				target_container.set('additionalCssClasses', (css_classes_tosave != null ? css_classes_tosave.join(',') : ""));
			}, 20);


			DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', css_classes_tosave );
            DDLayout.ddl_admin_page.trigger( 'layout_element_model_changed_from_dialog', caller, target_container_view, self.cached_element, false, self );

		}

        if ( self.is_save_and_close(caller) )  jQuery.colorbox.close();

		return false;
	};

	self.show = function(mode, container_view)
	{

        self.setCachedElement( container_view.model.toJSON() );

		if (mode == 'edit') {

			jQuery('#ddl-container-edit').data('mode', 'edit-container');
			jQuery('#ddl-container-edit').data('container_view', container_view);

			jQuery('#ddl-container-edit .js-dialog-edit-title').show();
			jQuery('#ddl-container-edit .js-container-dialog-edit-save').show();

			jQuery('#ddl-container-edit .js-dialog-add-title').hide();
			//jQuery('#ddl-container-edit .js-container-dialog-edit-add-container').hide();
            jQuery('.js-edit-dialog-close').css('float', 'left')

			jQuery('#ddl-container-edit #ddl-container-edit-layout-type').parent().hide();


            _.defer(function(){
				var chosen_context = jQuery('#ddl-container-edit').find('.ddl-fields-container');
                jQuery('input[name="ddl-container-edit-container-name"]').val( container_view.model.get('name') );
                jQuery('input.js-edit-css-id', jQuery('#ddl-container-edit') ).val( container_view.model.get('cssId') );
                jQuery('select.js-ddl-tag-name', jQuery('#ddl-container-edit') ).val( container_view.model.get('tag') );

				var saved_css_classes = container_view.model.get('additionalCssClasses');
				var array_with_classes = ( saved_css_classes != null ? saved_css_classes.split(" ") : saved_css_classes );

				DDLayout.ddl_admin_page.trigger( 'layout_update_additional_css_classes_array', array_with_classes );
				DDLayout.ddl_admin_page.trigger( 'layout_generate_chosen_selector', array_with_classes, chosen_context );

            });
		}

		jQuery.colorbox({
			href: '#ddl-container-edit',
			closeButton:false,
			onComplete: function() {
				
			}
		});

		self.init();
	};
};