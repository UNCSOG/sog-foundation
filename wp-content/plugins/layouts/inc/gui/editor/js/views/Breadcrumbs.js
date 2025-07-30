// Breadcrumbs.js

DDLayout.Breadcrumbs = function(current_layout)
{
	var self = this,
        layout = current_layout;
	self.parents = null;
    self._current_parent = '';

	self.init = function ( ) {

        self.parent_helper = new DDLayout.ParentHelper( layout );

        self._current_parent = '';
		self.parents = [];

        jQuery('#ddl-layout-settings-dialog .js-item-name').each( self.populate_parents_array );

        self.events_on();
    };

    self.events_on = function(){

        jQuery(document).on('click', '.js-layout-parent-parent, .js-layout-parent', {}, self.click_handler);

        jQuery( '.js-layouts-icon-wrap' ).mouseenter( self.showTooltipForIcon ).mouseleave( self.hideTooltipForIcon );

        var $icon = jQuery('.js-layouts-icon');

        jQuery( 'body' ).on( 'click', {icon:$icon}, self.document_click_handler);

        $icon.on('click', self.icon_click_handler);

        Toolset.hooks.addAction( 'ddl-parent-layout-settings-dialog-opens', self.settings_opens_callback);

        Toolset.hooks.addFilter( 'ddl-icon-template-conditions-changed', self.handle_icon_change );
    };

    self.events_off = function(){

        jQuery( 'body' ).off( 'click', self.document_click_handler);

        jQuery('.js-layouts-icon').off('click', self.icon_click_handler);

        jQuery(document).off('click', '.js-layout-parent-parent, .js-layout-parent', self.click_handler);

        Toolset.hooks.removeAction( 'ddl-parent-layout-settings-dialog-opens', self.settings_opens_callback);

        Toolset.hooks.removeFilter( 'ddl-icon-template-conditions-changed', self.handle_icon_change );
    };

    self.showTooltipForIcon = function () {
        jQuery('.js-layouts-icon-wrap').pointer({
            content: function () {
                return '<h3>'+DDLayout_settings.DDL_JS.strings.layout_hierarchy_settings_tooltip+'</h3>';
            },
            pointerClass: 'wp-toolset-pointer wp-toolset-layouts-pointer ddl-only-header-pointer',
            buttons: function () {
                return null;
            },
        }).pointer('open');
    };
    self.hideTooltipForIcon = function () {
        jQuery('.js-layouts-icon-wrap').pointer('close');
    };

    self.populate_parents_array = function () {
        if( jQuery(this).data('layout-id') )
        {
            self.parents[jQuery(this).data('layout-slug')] = jQuery(this).data('layout-id');
        }
    };

    self.settings_opens_callback = function( event ){
        jQuery('.js-layouts-icon').ddlWpPointer('hide', {onClose:self.handle_tooltip_close});
    };

    self.click_handler = function(event) {
        event.preventDefault();
        self._edit_parent_layout( this );
        return false;
    };

    self.handle_tooltip_open = function(){
        self.handle_set_parent_button();
    };

    self.handle_tooltip_close = function(){
        self._current_parent = '';
        self.parent_helper.reset();
    };

    self.layout_icon_template = function( type ){
        var data = DDLayout_settings.DDL_JS.layout_type_icons[type],
            template = jQuery('#js-ddl-template-icon-layout-type').html();
        return WPV_Toolset.Utils._template( template, data );
    };

    self.handle_icon_change = function( type ){
        if( !type ){
            return;
        }

        var $old_icon = jQuery('.js-layouts-icon-wrap'),
            new_icon = self.layout_icon_template( type ),
            $new_icon = null;

        $new_icon = jQuery( new_icon ).replaceAll( $old_icon );

        self.events_off();
        self.init();

        return type;
    };

    self.handle_tooltip_complete = function(){

            self.parent_helper.init()
    };

    _.bindAll(self, 'handle_tooltip_open', 'handle_tooltip_close', 'handle_tooltip_complete');

    self.document_click_handler = function( event ){

        var $icon = event.data.icon;

        if( !$icon || $icon.length === 0 ) return true;

        if( event.target === $icon[0] ){
            return true;
        }

        if( jQuery(event.target).closest('.wp-pointer-content').is('div') ){
            return true;
        }

        if( $icon.data('has-wppointer') ){
            $icon.ddlWpPointer('hide', {onClose:self.handle_tooltip_close});
            return true;
        }

        return true;
    };

    self.icon_click_handler = function ( event ) {

        event.stopPropagation();

        if ( !jQuery(this).data('has-wppointer') ) {

            var parent_layout = layout.get_parent_layout();

            if( parent_layout ){

                var link = '<span class="js-dd-layouts-breadcrumbs"></span>',
                    new_text = jQuery(this).data('tooltip-content').replace( '#PARENT_LAYOUT#', link );

                jQuery(this).data( 'tooltip-content', new_text );

            }

            jQuery(this).ddlWpPointer('show', {
                edge: 'top',
                align: 'left',
                onOpen:self.handle_tooltip_open,
                onComplete: self.handle_tooltip_complete
            });

            if( parent_layout ){
                self.display_breadcrumbs( layout );
            }

        } else {

            jQuery(this).ddlWpPointer('hide', {onClose:self.handle_tooltip_close});

        }
    };

    self.handle_set_parent_button = function(){
        var $button = jQuery('.js-edit-layout-settings');

        if( $button.length === 0 ){
            return;
        }

        if( layout.get_parent_layout() ){
            $button.text( DDLayout_settings.DDL_JS.strings.set_parent_layout );
        } else {
            $button.text( DDLayout_settings.DDL_JS.strings.change_parent_layout );
        }
    };

	self.display_breadcrumbs = function (layout) {

        jQuery('.js-layout-width-error').remove();

		var parent_layout = layout.get_parent_layout();
        if (parent_layout != self._current_parent) {

            self._current_parent = parent_layout;

            if (parent_layout != '') {
                var link = '<a href="#" class="js-layout-parent" data-layout-slug="' + parent_layout + '" data-post-name="' + parent_layout + '">' + self._get_post_title(parent_layout) + '</a>';
                jQuery('.js-dd-layouts-breadcrumbs').html(link);
                jQuery('.dd-layouts-breadcrumbs').show();

                // get the grandparents.

                var data = {
                        layout_name : parent_layout,
                        action : 'get_layout_parents'
                };

                jQuery.ajax({
                        type:'post',
                        url:ajaxurl,
                        data:data,
                        success:function(response){
                            response = JSON.parse(response);

                            var new_element = jQuery('.js-layout-parent');
                            for (var i = 0; i < response.length; i++) {
                                new_element.before('<a class="breadcrumbs-line layout-parent-parent js-layout-parent-parent" data-post-name="' + response[i] + '" >' + self._get_post_title(response[i]) + '</a> <span class="separator">&raquo;</span> ');
                                new_element = jQuery('.js-layout-parent-parent').first();
                            }


                        },
                    });

                // Check for correct number of cells in parent child layout cell

                if (parent_layout != '' && layout.getType() == 'fixed') {
                    data = {
                            layout_name : layout.get_name(),
                            parent_layout_name: parent_layout,
                            parent_layout_title: self._get_post_title(parent_layout),
                            width: layout.get_width(),
                            action : 'check_for_parent_child_layout_width'
                    };

                    jQuery.ajax({
                            type:'post',
                            url:ajaxurl,
                            data:data,
                            success:function(response){
                                response = JSON.parse(response);

                                if ( response.error !== '' ) {
                                    jQuery('.js-ddl-message-container').wpvToolsetMessage({
                                        text: response.error,
                                        classname: 'js-layout-width-error',
                                        type: 'error',
                                        stay: true,
                                        close: true,
                                        onOpen: function() {
                                            jQuery('html').addClass('toolset-alert-active');
                                        },
                                        onClose: function() {
                                            jQuery('html').removeClass('toolset-alert-active');
                                        }
                                    });
                                }

                            }
                        });
                }
            } else {
                jQuery('.dd-layouts-breadcrumbs').hide();
                jQuery('.js-dd-layouts-breadcrumbs').empty();
            }
        }
	};

	self._edit_parent = function ( ) {
		self.current_parent = jQuery('.js-layout-parent').data('layout-slug');

		if (DDLayout.ddl_admin_page.is_save_required()) {

			dialog = DDLayout.DialogYesNoCancel(DDLayout_settings.DDL_JS.strings.save_required,
												DDLayout_settings.DDL_JS.strings.save_before_edit_parent,
												{'yes' : DDLayout_settings.DDL_JS.strings.save_layout_yes,
												'no' : DDLayout_settings.DDL_JS.strings.save_layout_no},
												function(result) {
													if (result == 'yes') {
														DDLayout.ddl_admin_page.save_layout(self._switch_to_parent(self.current_parent));
													} else if (result == 'no') {
														self._switch_to_parent(self.current_parent);
													}
												});

		} else {
			jQuery.colorbox.close();

			self._switch_to_parent(self.current_parent);
		}
	}

	self._switch_to_parent = function (name) {

		DDLayout.ddl_admin_page.switch_to_layout(self.parents[name]);

	}

	self._edit_parent_layout = function ( item ) {
		var name = jQuery(item).data('post-name');

		if (DDLayout.ddl_admin_page.is_save_required()) {

			dialog = DDLayout.DialogYesNoCancel(DDLayout_settings.DDL_JS.strings.save_required,
												DDLayout_settings.DDL_JS.strings.save_before_edit_parent,
												{'yes' : DDLayout_settings.DDL_JS.strings.save_layout_yes,
												'no' : DDLayout_settings.DDL_JS.strings.save_layout_no},
												function(result) {
													if (result == 'yes') {
														DDLayout.ddl_admin_page.save_layout(self._switch_to_parent(name));
													} else if (result == 'no') {
														self._switch_to_parent(name);
													}
												});

		} else {
			self._switch_to_parent(name);
		}
	}

	self._get_post_title = function (post_name) {
		var post_title = '';
		jQuery('#ddl-layout-settings-dialog .js-item-name').each( function () {
			if (jQuery(this).data('layout-slug') == post_name)  {
				post_title = jQuery(this).text();
			}
		});

		return post_title;
	}

	self.init( );
};
