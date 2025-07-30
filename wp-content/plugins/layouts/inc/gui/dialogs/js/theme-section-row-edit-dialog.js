var DDLayout = DDLayout || {};

DDLayout.ThemeSectionRowDialog = function($)
{
    var self = this;

    _.extend( DDLayout.ThemeSectionRowDialog.prototype, new DDLayout.Dialogs.Prototype(jQuery) );

	self.ROW_KIND = 'ThemeSectionRow';

    self.init = function() {

        jQuery(document).on('click', '.js-theme-section-row-dialog-edit-add-row,.js-theme-section-row-dialog-edit-save', {dialog: self}, function(event) {
            event.preventDefault();
            event.data.dialog._save();
        });

        jQuery(document).on('click', '#ddl-theme-section-row-edit .js-ddl-show', {dialog: self}, function(event) {
            event.preventDefault();
            jQuery('.js-front-end-options').show();
            jQuery('.js-ddl-show').hide();
            jQuery('.js-ddl-hide').show();

        });

        jQuery(document).on('click', '#ddl-theme-section-row-edit .js-ddl-hide', {dialog: self}, function(event) {
            event.preventDefault();
            jQuery('.js-front-end-options').hide();
            jQuery('.js-ddl-show').show();
            jQuery('.js-ddl-hide').hide();

        });
    };

    // TODO: ADD JS PREFIXES
    // TODO: Assign repetitive elements to variables. for example
    // var $layoutType = jQuery('#ddl-theme-section-row-edit #ddl-theme-section-row-edit-type');
    self.show = function( mode, row_view, caller ) {

        if ( mode == 'edit' ) {

            jQuery('#ddl-theme-section-row-edit').data('mode', 'edit-row');
            jQuery('#ddl-theme-section-row-edit').data('row_view', row_view);

            jQuery('input[name="ddl-theme-section-row-edit-row-name"]').val(row_view.model.get('name'));
	        jQuery('select[name="ddl-theme-section-row-edit-type"]').val( row_view.model.get('type') );

            jQuery('#ddl-theme-section-row-edit .js-dialog-edit-title').show();
            jQuery('#ddl-theme-section-row-edit .js-row-dialog-edit-save').show();

            jQuery('#ddl-theme-section-row-edit .js-dialog-add-title').hide();
            jQuery('#ddl-theme-section-row-edit .js-theme-section-row-dialog-edit-add-row').hide();

            self.init_buttons( jQuery('#ddl-theme-section-row-edit'),'edit', jQuery(caller).data() );
            jQuery('.js-theme-section-row-dialog-edit-save').show();

        } else if (mode == 'add') {
            jQuery('#ddl-theme-section-row-edit').data('mode', 'add-row');


	        jQuery('#ddl-theme-section-row-edit').data('triggered_from_row_view', row_view);

            jQuery('input[name="ddl-theme-section-row-edit-row-name"]').val('');

            jQuery('#ddl-theme-section-row-edit .js-dialog-edit-title').hide();
            jQuery('#ddl-theme-section-row-edit .js-theme-section-row-dialog-edit-save').hide();

            jQuery('#ddl-theme-section-row-edit .js-dialog-add-title').show();
            jQuery('#ddl-theme-section-row-edit .js-row-dialog-edit-add-row').show();


            jQuery('#ddl-theme-section-row-edit #ddl-theme-section-row-edit-type').parent().show();

            self.init_buttons( jQuery('#ddl-theme-section-row-edit'),'create', jQuery(caller).data() )
            jQuery('.js-theme-section-row-dialog-edit-add-row').show();

        }

        jQuery.colorbox({
            href: '#ddl-theme-section-row-edit',
            closeButton:false,
            onComplete: function() {

	            if (mode == 'add')
	            {
		            jQuery('.js-dialog-add-title').show();
	            }
	            else if( mode == 'edit' )
	            {
		            jQuery('.js-dialog-edit-title').show();
	            }
	            self.get_selected_and_render_description();
               // jQuery('.js-popup-tabs').tabs(); // Initialize tabs
              //  jQuery('.js-popup-tabs').tabs( 'option', 'active', 0 ); // Activate the first tab

            }
        });
    };


	self.get_selected_and_render_description = function()
	{
		var template = jQuery('#theme-section-description-template').html(),
			holder = jQuery('#js-theme-section-description-container'),
			$option = jQuery('.js-ddl-theme-section-row-edit-type'),
			current_val = $option.val(),
			current_description = DDLayout.themeSectionsRow_data[current_val],
			shown = true;

			if( current_description )
			{
				holder.html( WPV_Toolset.Utils._template( template, {description:current_description} ) );
			}


		jQuery(document).on('change', '.js-ddl-theme-section-row-edit-type', function(event){
				var now_description =  DDLayout.themeSectionsRow_data[ jQuery( event.target).val() ];
				if( now_description )
				{
					holder.html( WPV_Toolset.Utils._template( template, {description:now_description} ) );
					if( shown === false ){
						holder.show();
						shown = true;
					}
				}
				else
				{
					holder.hide();
					shown = false;
				}
		});
	};

    self._save = function () {

        if (jQuery('#ddl-theme-section-row-edit').data('mode') == 'add-row') {

	        var target_row_view = jQuery('#ddl-theme-section-row-edit').data('triggered_from_row_view');

            target_row_view.addThemeSectionRow(jQuery('input[name="ddl-theme-section-row-edit-row-name"]').val(),
	                                           jQuery('#ddl-theme-section-row-edit select[name="ddl-theme-section-row-edit-type"]').val(),
	                                           self.ROW_KIND,
	                                           target_row_view.model.get('layout_type')
            );



        } else if (jQuery('#ddl-theme-section-row-edit').data('mode') == 'edit-row') {
	        var target_row_view = jQuery('#ddl-theme-section-row-edit').data('row_view');

            DDLayout.ddl_admin_page.save_undo();

            var target_row = target_row_view.model;

            target_row.set('name', jQuery('input[name="ddl-theme-section-row-edit-row-name"]').val());
            target_row.set('type', jQuery('#ddl-theme-section-row-edit select[name="ddl-theme-section-row-edit-type"]').val() );


            target_row_view.render();
        }


        DDLayout.ddl_admin_page.trigger( 'layout_element_model_changed_from_dialog', jQuery('input[name="save_layout"]'), target_row_view, null, false, self );


        jQuery.colorbox.close();
        return false;
    };

    self.init();
};
