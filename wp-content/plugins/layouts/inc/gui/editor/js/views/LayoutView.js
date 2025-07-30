DDLayout.views.LayoutView = Backbone.View.extend({
	//this is our document view it points to the root element of our page - .wrap
	el:"#js-dd-layout-editor",
	events: {
		'ajaxError': 'handleAjaxError'
	}
	,initialize: function(options)
	{
		var self = this;

        self.mouseY = 0;
        self.detect_scroll = true;
        self.rendered = false;

        self.layout_parent = self.model.get('parent');

        self.detect_mouse();

        self.removed_message_displayed = false;

        self.called_close = false;

		self.options = options;

		self.options.invisibility = false;

		self.scroll_position = 0;

		self.errors_div = jQuery(".js-ddl-message-container");

		self.model.set( "slug", jQuery(".js-edit-layout-slug", self.$el).text() );

		_.bindAll( self, 'beforeRender', 'render', 'afterRender', 'dropCellFails', 'listen_model_change_parent', 'toggleMarkupTooltipButtonVisibility' );

		self.render = _.wrap(self.render, function(render, args) {
			self.beforeRender(args);
			render(args);
			self.afterRender(args);
			return self;
		});

		self.$el.data('view', self);

		self.rows_view = null;

		self.listenTo(self.eventDispatcher, "save_layout_to_server", self.saveLayout, self );
		self.listenTo(self.eventDispatcher, 'clear_drop_failed', self.clearDropFailed, self );
		self.listenTo(self.eventDispatcher, 'deselect_element', self.clearDropFailed, self );


		self.listenToOnce(self.eventDispatcher, 're_render_all', function(event, model){
			 /// do something once on render
		});

		self.listenTo( self.eventDispatcher, 're_render_all', self.render );

		self.listenTo( self.eventDispatcher, 'ddl-delete-child-layout-cell', self.setChildrenToDelete );

        self.listenTo( self.eventDispatcher, 'ddl-delete-child-layout-cell', self.notify_icon_logic_changed );

        self.listenTo( self.model, 'cells-collection-remove-cell', self.cell_removed_callback );

        self.listenTo(self.model, 'cells-collection-add-cell', self.layout_cell_added);

		self.listenTo(self.model, 'request', self.ajaxSentRequest, self);

		self.listenTo(self.model, 'sync', self.ajaxSynced, self);

        self.model.listenTo(self.model, 'change:parent', self.listen_model_change_parent);

		self.hide_show_containers_button = self.hideContainerEdit();

		self.handleSave();

        self.change_layout_name();

		self.render();

		return self;
	},
    detect_mouse:function(){

        var self = this;

        jQuery( document ).on( "mousemove", function( event ) {
            if( jQuery(event.toElement).closest('#colorbox').is('div') || jQuery(event.toElement).closest('#cboxOverlay').is('div') ){
                self.detect_scroll = false;
                return;
            } else {
                self.detect_scroll = true;
                self.mouseY = event.pageY;
            }
        });
    },
    listen_model_change_parent: function (model, new_value, event_object) {
        var self = this, type = null;

        if ( self.model.is_private() ) {
            return;
        }

        if (self.layout_parent === new_value) {
            // nothing happens
            return;
        } else if ( ( self.layout_parent && new_value ) && self.layout_parent !== new_value ) {
            self.layout_parent = new_value;
            // nothing happens
            return;
        }
        else if ( self.layout_parent && !new_value ) {
            self.layout_parent = new_value;
            type = 'template';
            // becomes template
            return Toolset.hooks.applyFilters( 'ddl-icon-template-conditions-changed', type );
        } else if ( !self.layout_parent && new_value && self.model.is_parent() ) {
            self.layout_parent = new_value;
            type = 'parent';
            // becomes parent
            return Toolset.hooks.applyFilters( 'ddl-icon-template-conditions-changed', type );
        } else if ( !self.layout_parent && new_value && self.model.is_parent() === false ) {
            self.layout_parent = new_value;
            type = 'child';
            // becomes parent
            return Toolset.hooks.applyFilters( 'ddl-icon-template-conditions-changed', type );
        }

        return Toolset.hooks.applyFilters( 'ddl-icon-template-conditions-changed', type );
    },
    notify_icon_logic_changed: function( event_type, children ){
        var self = this;

        if( self.model.get_parent_layout() ){
            return Toolset.hooks.applyFilters( 'ddl-icon-template-conditions-changed', 'child' );
        } else {
             return Toolset.hooks.applyFilters( 'ddl-icon-template-conditions-changed', 'template' );
        }
    },
    layout_cell_added:function(model, options)
    {
        if( model instanceof DDLayout.models.abstract.Element === false ) return;

        if( 'child-layout' === model.get('cell_type') ){
            this.hide_where_used_box();
            return Toolset.hooks.applyFilters( 'ddl-icon-template-conditions-changed', 'parent' );
        }
    },
    cell_removed_callback:function( model, options){

        if( model instanceof DDLayout.models.abstract.Element === false ) return;

        if( 'child-layout' == model.get('cell_type') ){
            this.show_where_used_box();
        }
    },
    hide_where_used_box:function()
    {
        jQuery('.js-dd-layouts-where-used-editor-wrap').fadeOut(400, function(){

        });
    },
    show_where_used_box:function(){
        jQuery('.js-dd-layouts-where-used-editor-wrap').fadeIn(400, function(){

        });
    },
    adjustHeights:function(){
        var self = this;
        jQuery('body').addClass('noscroll');
        self.$el.css( 'min-height', self.$el.height() );
    },
	beforeRender:function(option)
	{
		var self = this;
        self.adjustHeights();

		jQuery(window).scroll(function(){
            if( self.detect_scroll ){
                self.scroll_position =  window.pageYOffset;
            } else {
                self.scroll_position = self.mouseY;
            }
		});

		self.show_div_after_self = false;

        self.maybe_display_removed_message();
	},
    maybe_display_removed_message:function(){
        var self = this,
            removed = DDLayout_settings.DDL_JS.removed_cells,
            message = DDLayout_settings.DDL_JS.strings.removed_cells_message;

        if( removed && removed.length && self.removed_message_displayed === false  ){

            self.errors_div.wpvToolsetMessage({
                text: message,
                type: 'info',
                stay: true,
                stay_for:10000,
                close: true,
                onOpen: function() {
                    jQuery('html').addClass('toolset-alert-active');
                },
                onClose: function() {
                    jQuery('html').removeClass('toolset-alert-active');
                    DDLayout_settings.DDL_JS.removed_cells = [];
                    DDLayout_settings.DDL_JS.strings.removed_cells_message = '';
                    self.removed_message_displayed = true;
                }
            });
        }
    },
    setSlugDisplay: function( slug )
    {
        this.$el.find('.js-edit-layout-slug').text( slug );
    },
    setTitleDisplay:function( title )
    {
        this.$el.find('.js-layout-title').val( title );
    }
	,render:function( option )
	{
		var self = this,
			options = null;

		DDLayout.preview_manager.reset();

		if( self.model.has("Rows") && self.model.numRows() )
		{

			self._hide_show_button_container_edit();

			options = _.extend({el:'div.js-layout-container', model:self.model.get("Rows"), compound:"", invisibility:self.options.invisibility}, option );

            self.setTitleDisplay(  self.model.get('name')  );

            self.setSlugDisplay( self.model.get('slug') );

            self.setBreadCrumbText();

			//make sure we garbage collected previuos instances
			if( self.rows_view !== null )
			{
				self.rows_view = null;
			}

			self.rows_view = new DDLayout.views.RowsView( options );

			if( DDLayout.ddl_admin_page === undefined )
			{
				self.show_div_after_self = true;
				jQuery( "> div", self.rows_view.$el ).hide();
			}

			if( self.options.invisibility === true )
			{
				jQuery('.js-layout-container').addClass('containers-toolbars-disabled');
			}
			else
			{
				jQuery('.js-layout-container').removeClass('containers-toolbars-disabled');
			}

		}

        if( option && option.callback && typeof option.callback === 'function'){
            option.callback.call(self);
        }

		return self;
	},
	afterRender:function(option)
	{
		var self = this;

        jQuery('.js-ddl-bootstrap-base-button').show();

        self.do_scroll();

		if( self.show_div_after_self )
		{
			jQuery( "> div", self.rows_view.$el ).fadeIn(300);
		}
		// set the main layout size.
		if ( self.model.get('width') != DDLayout.MAXIMUM_SPAN ) {
			var width = self.model.get('width') * (DDLayout.CELL_MIN_WIDTH + DDLayout.MARGIN_BETWEEN_CELLS) - DDLayout.MARGIN_BETWEEN_CELLS;
			width += 6; // allow room for shadows.
			jQuery('.js-layout-container > .row-container').css({ width : width });
		}

        self.eventDispatcher.trigger('layout_editor_view_after_render');

		// Delete any problem tooltips.

		jQuery('.toolset-tooltip').remove();

		// Show a popup if we failed to drop the cells.

		_.delay( self.dropCellFails, 500);

        jQuery('.debugging').html(JSON.stringify( this.model.toJSON() ) );

        if( option && option.ajax_save && option.ajax_save instanceof Function ){
                var save_params = option.save_params;
                option.ajax_save.call(self, save_params);
        }

        jQuery('body').removeClass('noscroll');
        _.delay( self.toggleMarkupTooltipButtonVisibility, 300 );

        self.rendered = true;

	},
    toggleMarkupTooltipButtonVisibility : function ( ) {
            var self = this,
                $button = jQuery( '.ddl-js-info-tooltip-button' );

            if( self.layoutHasMarkupIcons() ){
                $button.show();
            } else {
                $button.hide();
            }
    },
    layoutHasMarkupIcons:function(){
        var $icons = jQuery( 'i.js-element-info-icon' );
        return $icons.length > 0;
    },
    do_scroll:function(){

        if( !Toolset.hooks.applyFilters('ddl-creation-dialog-opened', false) ){
            return;
        }

        var self = this,
            adjust = parseInt( jQuery('#wpbody-content').css('padding-bottom') ),
            scroll = self.scroll_position + adjust + 50;

        if( self.scroll_position > 0 ){
            _.defer( function(){
                window.scrollTo( 0, scroll);
            }, self);
        }

        Toolset.hooks.removeFilter('ddl-creation-dialog-opened');
    },
	dropCellFails:	function () {

        var self = this, drop_element = jQuery('.js-drop-failed', self.rows_view.$el).first();
        if (drop_element.length) {

            var cell_width = drop_element.data('drop-failed-drop-width');
            var $targets = jQuery('.js-drop-failed', self.rows_view.$el);
            var target_width = $targets.length;

            if (cell_width > target_width) {

                $targets.addClass('drop-failed');

                var message = DDLayout_settings.DDL_JS.strings.no_drop_content;
                message = message.replace(/\%NN\%/g, cell_width);
                message = message.replace(/\%MM\%/g, target_width);

                if (drop_element.data('drop-failed-target-row-divider') > drop_element.data('drop-failed-drop-row-divider')) {
                    message = message.replace(/\%OO\%/, DDLayout_settings.DDL_JS.strings.no_drop_content_wider);
                } else {
                    message = message.replace(/\%OO\%/, '');
                }

                drop_element.pointer({
                    pointerClass: 'wp-toolset-pointer wp-toolset-layouts-pointer',
                    content: '<h3>' + DDLayout_settings.DDL_JS.strings.no_drop_title + '</h3><p>' + message + '</p>',
                    position: {
                        edge: 'bottom'
                    },
                    close: function () {self.clearDropFailed(true)},
                    open: function (event, data) {
                        var pointer = data.pointer;
                        var arrow = pointer.find('.wp-pointer-arrow');
                        arrow.css('left', (drop_element.width() - arrow.outerWidth()) / 2);
                    },
                    pointerWidth: 420,
                    buttons: function( event, t ) {
                        var close  = ( wpPointerL10n ) ? wpPointerL10n.dismiss : 'Dismiss';
                        var button_close = jQuery('<button class="button button-primary-toolset alignright js-wpv-close-this">' + close + '</button>');

                        return button_close.bind( 'click.pointer', function( e ) {
                            e.preventDefault();
                            t.element.pointer('close');
                        });
                    },


                }).pointer('open');
            } else {
                $targets.removeClass('js-drop-failed');
            }

        }
    },
	clearDropFailed:function( no_close )
	{

		if (no_close !== true) {
			try {
				jQuery('.drop-failed').first().pointer('close');
			} catch (err) {

			}
		}

		jQuery('.drop-failed').removeClass('drop-failed');

		var cells = this.model.getLayoutCells();

		for (var i = 0; i < cells.length; i++) {
			cells[i].unset('drop-failed', {silent:true});
		}

	},
	setChildrenToDelete:function( event_type, children )
	{
		this.model.setChildrenToDelete( children, event_type );
	},
	handleSave:function()
	{
		var self = this, button_save = jQuery('input[name="save_layout"]');
		button_save.on("click", function(event){

			self.clearDropFailed();

			self.eventDispatcher.trigger('save_layout_to_server', jQuery(event.target) );
            jQuery(this).prop('disabled', true );
			return false;
		});

	},
    handleBreadCrumbTitleChange:function( el, breadEl)
    {
        var input = el, bread = breadEl;

        input.keyup(function (e) {
            bread.text( input.val() );
        });
    },
    setBreadCrumbText:function ()
    {
       var  breadTitle = jQuery('.js-dd-layouts-breadcrumbs-wrap > .js-layout-title');

        if( breadTitle.is('span') )
        {
            breadTitle.text( this.model.get('name') );
        }
    },
    change_layout_name:function()
    {
        var self = this,
            title = jQuery('input.js-layout-title', self.$el)
            , breadTitle = jQuery('.js-dd-layouts-breadcrumbs-wrap > .js-layout-title');


        if( breadTitle.is('span') )
        {
            self.handleBreadCrumbTitleChange( title, breadTitle );
        }


        jQuery(document).on('focus', 'input.js-layout-title', function(event){
            DDLayout.ddl_admin_page.take_undo_snapshot();
        });

        jQuery(document).on('change', 'input.js-layout-title', function(event){
            DDLayout.ddl_admin_page.add_snapshot_to_undo();

            if( jQuery(this).val() === '' )
            {
                WPV_Toolset.messages.container.wpvToolsetMessage({
                    text: DDLayout_settings.DDL_JS.strings.title_not_empty_string,
                    type: 'error',
                    stay: false,
                    close: false,
                    onOpen: function() {
                        jQuery('html').addClass('toolset-alert-active');
                    },
                    onClose: function() {
                        jQuery('html').removeClass('toolset-alert-active');
                    }
                });

                jQuery(this).val( self.model.get('name') );
            }
            else
            {
                self.model.set('name', jQuery(this).val() );
            }
        });
    },

    show_loader:function(caller){
        var obj = caller ? caller.parent() : jQuery(document.body);

        if( caller && caller.data('close') === 'no' ){

            var width = 0, right = 0;

            obj.children().each( function( v ){
                    width += jQuery(this).outerWidth();
            });

            right = width;

            obj.css('position', 'relative');

            WPV_Toolset.Utils.loader.loadShow( caller, true ).css({
                'position':'absolute',
                'right':right,
                'top':'6px'
            });

        } else {
            return;
        }
    },
    hide_loader:function(){
        WPV_Toolset.Utils.loader.loadHide();
    },
    handleChildLayoutData:function(){

        if( this.model.toJSON().has_child === true )
        {
            DDLayout.parents_watcher.trigger( 'created_child_layout', this.model.toJSON() );
        }

        if( this.model.get('children_to_delete') !== null )
        {
            DDLayout.parents_watcher.trigger( 'deleted_child_layout', this.model.toJSON() );
        }
    },
	saveLayout:function( caller, callback, target )
	{
		var self = this,
			save_params = {};

        if( callback ){
            self.save_layout_callback = _.once( callback );
        } else {
            self.save_layout_callback = null;
        }

        self.show_loader(caller);
        DDLayout.ddl_admin_page.saving_saved.show_saving();

        if( DDLayout.ddl_admin_page.is_slug_edited )
        {
            self.model.set('slug', jQuery('#layout-slug').val() );
            self.eventDispatcher.trigger('layout-model-trigger-save', jQuery('#layout-slug').val() );
        }

        var preferred_editor = Toolset.hooks.applyFilters( 'ddl-preferred-editor', false );
        if( preferred_editor ){
            save_params.preferred_editor = preferred_editor;
        }

        self.handleChildLayoutData();

        //DDLayout.ddl_admin_page.instance_layout_view.eventDispatcher.trigger('re_render_all', {ajax_save:self.saveViaAjax, save_params:save_params});
        self.saveViaAjax( save_params );
	},

	saveViaAjax : function( save_params ) {
		var self = this, model = self.model.toJSON();

		save_params = _.extend({
			action:'save_layout_data',
			layout_id:self.model.get('id'),
			save_layout_nonce:DDLayout_settings.DDL_JS.save_layout_nonce,
			// layout_model: encodeURIComponent( JSON.stringify( self.model.toJSON() ) )
			layout_model:JSON.stringify( model )
		}, save_params);

        save_params = Toolset.hooks.applyFilters('ddl_save_layout_params', save_params, self);

		self.model.save({},{
			contentType:'application/x-www-form-urlencoded; charset=UTF-8',
			type:'post',
			dataType:'json',
			data:jQuery.param(save_params)
		});
	},

	ajaxSentRequest:function( model, response, xhr )
	{
		//console.log("Request", arguments);
	},

	ajaxSynced:function( model, response, xhr )
	{

		var self = this, has_error = false;
        self.called_close = false;

		self.hide_loader();
        DDLayout.ddl_admin_page.saving_saved.swap_to_saved();

		if( response.Data.error )
		{
            has_error = true;
            console.log( 'Error: ', response.Data.error );
			self.errors_div.wpvToolsetMessage({
				text: response.Data.error,
				type: 'error',
				stay: false,
                stay_for:10000,
				close: false,
				onOpen: function() {
					jQuery('html').addClass('toolset-alert-active');
				},
				onClose: function() {
					jQuery('html').removeClass('toolset-alert-active');
				}
			});
		}
		else if( response.Data.message && response.Data.message.layout_changed )
		{
            if( typeof self.save_layout_callback === 'function' ){
                self.save_layout_callback.call(self, model, response);
            }


            if( self.called_close === false ){
                self.display_cache_message( response.Data.message );
            }

		}

		else if (response && response.Data && response.Data.message && !response.Data.message.silent)
		{
			if (self.save_layout_callback && self.save_layout_callback instanceof Function ) {
				self.save_layout_callback.call(self, model, response);
			}
		} else {
            self.errors_div.hide();
        }

		if( response && response.Data )
		{
			// reset traking properties in Layout model
            if( response.Data.layout_children_deleted  )
            {
                this.setChildrenToDelete( undefined, null );
            }

            if( response.Data.slug )
            {
                self.setSlugDisplay( response.Data.slug );
            }

		}

		DDLayout.ddl_admin_page.update_wpml_state(self.model.get('id'), false);

        if( has_error === false ){
            DDLayout.ddl_admin_page.clear_save_required();
        }

        WPV_Toolset.Utils.eventDispatcher.trigger('layout_ajaxSynced_completed');
	},

    display_cache_message:function( data ){
        var self = this;
        self.called_close = true;

        if( data.display_cache_message !== true ){
            return;
        }

        if( jQuery.jStorage.get('cache_message_dont_show_again') === 'yes' ){
            return;
        }

        var self = this,
            expire = jQuery.jStorage.get('cache_message_expire'),
            now = new Date().getTime();

        if( expire && now < expire ){
             return;
        } else{

            self.errors_div.wpvToolsetMessage({
                text:DDLayout_settings.DDL_JS.strings.refresh_cache_message,
                type: 'warning',
                stay: true,
                //stay_for:10000,
                dontShowAgainText:DDLayout_settings.DDL_JS.strings.dont_show_again,
                close: true,
                dontShowAgain:function(object){
                    this.on('change', function(event){
                        if( jQuery(this).is(':checked') ){
                            jQuery.jStorage.set('cache_message_dont_show_again', 'yes' );
                        } else {
                            jQuery.jStorage.set('cache_message_dont_show_again', 'no' );
                        }
                    });
                },
                onOpen: function() {
                    jQuery('html').addClass('toolset-alert-active');
                },
                onClose: function() {
                    jQuery('html').removeClass('toolset-alert-active');
                    jQuery.jStorage.set('cache_message_expire', now + 86400000 );
                    self.called_close = false;
                }
            });

        }
    },
    handleAjaxError:function( event ){
        var self = this;

        self.hide_loader();

        self.errors_div.wpvToolsetMessage({
            text: DDLayout_settings.DDL_JS.strings.ajax_error,
            type: 'error',
            stay: false,
            stay_for:10000,
            close: false,
            onOpen: function() {
                jQuery('html').addClass('toolset-alert-active');
            },
            onClose: function() {
                jQuery('html').removeClass('toolset-alert-active');
            }
        });
    },
	getLayoutModelToJs:function()
	{
		return this.model.toJSON();
	},
	getLayoutType:function()
	{
		return this.model.getType();
	},
	hideContainerEdit:function()
	{
		var self = this, button = jQuery("input#hide-containers");

			if( self.options.invisibility )
			{
				button.val(DDLayout_settings.DDL_JS.strings.show_grid_edit);
			}
			else
			{
				button.val(DDLayout_settings.DDL_JS.strings.hide_grid_edit);
			}

			button.on("click", function(event){
				event.preventDefault();

				if( self.options.invisibility )
				{
					jQuery(this).val(DDLayout_settings.DDL_JS.strings.hide_grid_edit);
					self.options.invisibility = false;
				}
				else
				{
					jQuery(this).val(DDLayout_settings.DDL_JS.strings.show_grid_edit);
					self.options.invisibility = true;
				}

				self.render( );
			});

		   return button;
	},
	_hide_show_button_container_edit:function()
	{
		if( this._hasContainers() ){
			this.hide_show_containers_button.show();
		}
		else{
			this.hide_show_containers_button.hide();
		}
	},
	_hasContainers:function()
	{
		return this.model.getLayoutContainers( ).length > 0;
	}
});
