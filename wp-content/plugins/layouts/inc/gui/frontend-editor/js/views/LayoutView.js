'use strict';

(function ($) {

    DDLayout.views.LayoutView = DDLayout.views.abstract.ElementView.extend({
        el: 'div',
        active_element: null,
        active_parent: null,
        context_menu_opened: false,
        has_private: false,
        initialize: function () {
            var self = this;
            _.bindAll(self, 'clearOverlay', 'handleClick');
            self.listenTo(self.eventDispatcher, 'render-parent-overlay', this.renderParentOverlay);
            self.listenTo(self.eventDispatcher, 'clear-parent-overlay', this.clearParentOverlay);
            self.listenTo(self.model, 'save_layout_to_server', self.saveLayout);
            self.listenTo(self.model, 'sync', self.ajaxSynced, self);
            self.render();
            self.errors_div = jQuery('.js-ddl-message-container');
        },
        events: {},
        saveLayout:function( callback, caller, args )
        {
            var self = this,
                save_params = {};

            if( args.hasOwnProperty('action') ){
                save_params.action = args['action'];
            } else {
                return;
            }

            if( args.hasOwnProperty('woocommerce_archive_title') ){
                save_params.woocommerce_archive_title = args['woocommerce_archive_title'];
            }

            if( args.hasOwnProperty('element_model') && args['element_model'] instanceof DDLayout.models.abstract.Element ){
                save_params.element_model = JSON.stringify( args['element_model'].toJSON() )
            }

            if( args.hasOwnProperty('post_content') ){
                save_params.post_content = args['post_content'];
            }


            if( callback ){
                self.save_layout_callback = _.once( callback );
            } else {
                self.save_layout_callback = null;
            }

            self.show_loader( jQuery(caller) );

            var preferred_editor = Toolset.hooks.applyFilters( 'ddl-preferred-editor', false );

            if( preferred_editor ){
                save_params.preferred_editor = preferred_editor;
            }

            self.saveViaAjax( save_params );

        },
        saveViaAjax : function( save_params ) {
            var self = this, model = self.model.toJSON();

            save_params = _.extend({
                //action:'save_layout_data_front_end',
                toolset_editor:true,
                layout_id:self.model.get('id'),
                save_layout_nonce:DDLayout_settings.DDL_JS.save_layout_nonce,
                layout_model:JSON.stringify( model ),
                has_private: self.has_private,
                layout_type: self.model.get('layout_type')
            }, save_params);

            save_params = Toolset.hooks.applyFilters('ddl_save_layout_params', save_params, self);

            self.model.save({},{
                contentType:'application/x-www-form-urlencoded; charset=UTF-8',
                type:'post',
                dataType:'json',
                data:jQuery.param(save_params)
            });
        },
        ajaxSynced:function( model, response, xhr ){
            var self = this,
                has_error = false,
                index = DDLayout.ddl_admin_page.get_layouts_as_array().indexOf( self.model ),
                additional_class = 'layout_'+index,
                css_class = self.model.get('layout_type') ? self.model.get('layout_type') : 'normal' + ' '+additional_class;

            self.hide_loader();

            if( response.Data.error )
            {
                has_error = true;
                console.log( 'Error: ', response.Data.error );
                var message = self.model.get('name') + ' '+ response.Data.error;
                self.errors_div.append('<div class="ddl-messages ddl-error'+css_class+'"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i>'+message+'</div>');

            }
            else if( response.Data.message && response.Data.message.layout_changed )
            {
                if( response.Data.show_messages ){

                    var message = DDLayout_settings.DDL_JS.strings.save_complete.replace( '%NAME%', '<strong>'+self.model.get('name')+'</strong>' );
                    self.errors_div.append('<div class="ddl-messages '+css_class+'"><i class="fa fa-check" aria-hidden="true"></i>'+message+'</div>');

                }

                if( typeof self.save_layout_callback === 'function' ){
                    self.save_layout_callback.call(self, response, model, self.active_element);
                }

            } else if( response.Data.message && ! response.Data.message.layout_changed ){

                /*if( response.Data.show_messages ) {
                    var message = DDLayout_settings.DDL_JS.strings.no_changes_for.replace( '%NAME%', '<strong>'+self.model.get('name')+'</strong>' );
                    self.errors_div.append('<div class="ddl-messages '+css_class+'">'+message+'</div>');
                }*/

                if( response.Data.action === 'save_layout_data_front_end' ){
                    if( typeof self.save_layout_callback === 'function' ){
                        self.save_layout_callback.call(self, response, model, self.active_element);
                    }
                } else {
                    DDLayout.ddl_admin_page.reset_ajax(null);
                }

            } else{
                DDLayout.ddl_admin_page.reset_ajax();
                console.table( response );
            }

            DDLayout.ddl_admin_page.update_wpml_state(self.model.get('id'), false);

            if( has_error === false && response.Data.action === 'save_layout_data_front_end' ){
                DDLayout.ddl_admin_page.clear_save_required();
            }

            WPV_Toolset.Utils.eventDispatcher.trigger('layout_ajaxSynced_completed');
        },
        handleClick: function (e) {
            var self = this;
            self.contextMenuReset(e);
        },
        contextMenuReset:function(e){
            if(this.context_menu_opened && this.active_element !== null && !$.contains(this.active_element.$el.get(0), e.target)){
                this.closeContextMenu();
                this.clearOverlay();
                DDLayout.ddl_admin_page.trigger('ddl-events-over-on');
            }
        },
        openContextMenu: function () {
            this.context_menu_opened = true;
            DDLayout.ddl_admin_page.trigger('ddl-events-over-off');
        },
        closeContextMenu: function () {
            this.context_menu_opened = false;
        },
        renderOverlay: function (e) {

           // e.stopPropagation();
            if(this.context_menu_opened) return false;

            var id = e.currentTarget.dataset.id,
                slug = e.currentTarget.dataset.layout_slug,
                view = this.createElementView(id, e.currentTarget);

            if (null === view) return;

            this.active_element = view;
            jQuery(e.currentTarget).data('view', view);
            this.active_element.setElement(e.currentTarget);
            this.active_element.$el.addClass( this.give_class() );
            this.active_element.render( false );
        },
        renderParentOverlay: function (el, target_class, parent_parent) {

            var id = el.dataset.id,
                slug = el.dataset.layout_slug,
                view = this.createParentView(id, el, parent_parent);

            if (null === view) return;
            if (this.active_parent) return;

            $(el).addClass('ddl-frontend-editor-parent');

            this.active_parent = view;
            this.active_parent.setElement( el );
            this.active_parent.$el.addClass( target_class );
            jQuery(el).data('view', view);
            this.active_parent.render( );
        },
        get_element_model:function(id){
            var model = this.findNode(id, this.model);


            if( model instanceof DDLayout.models.abstract.Element ){
                model.set( 'is_not_editable', _.toArray( DDLayout_settings.DDL_JS.editable_cell_types ).indexOf( model.get('cell_type') ) === -1 );
            }

            return model;
        },
        createElementView: function (id, element) {
            var self = this, model = this.get_element_model(id);

            if (!model) return null;

            // Don't draw overlay for grid of cells

            self.has_private = self.has_private_layout(element);
            jQuery(element).data('has_private', self.has_private );

            if( model instanceof DDLayout.models.cells.Container ||
                ( model.get('cell_type') === 'cell-post-content' &&  self.has_private)
            ){
                if( element ){
                    jQuery(element).triggerHandler('mouseleave');
                }
                return null;
            }

            try{
                var kind = model.compound + model.get('kind');
            //    console.log( 'Creates element of',  kind + 'View')
                return new DDLayout.views[kind + 'View']({
                    model: model
                });
            } catch( e ){
                console.info( e.message );
                return null;
            }
        },
        createParentView: function (id, element, parent_parent ) {
            var self = this,
                undefined,
                parent_model = null,
                model = null;

            model = this.get_element_model(id);

            if (!model) return null;

            if( model.get('cell_type') === 'cell-post-content' ){
                self.has_private = self.has_private_layout(element);
                jQuery(element).data('has_private', self.has_private );
            }

            if( parent_parent && model instanceof DDLayout.models.cells.Row ){
                parent_model = this.get_element_model( parent_parent.dataset.id );
                if( parent_model instanceof DDLayout.models.cells.Container ){
                    model.compound = parent_model.get('kind');
                }
            }

            try{

                if( model instanceof DDLayout.models.cells.Cell || model instanceof DDLayout.models.cells.Parent ){
                    var kind = 'ParentLayout';
                } else {
                    var kind = model.compound + model.get('kind');
                }

                return new DDLayout.views[kind + 'View']({
                    model: model
                });

            } catch( e ){
                console.info( e.message );
                return null;
            }
        },
        has_private_layout:function( element ){
            return DDLayout_settings.DDL_JS.has_private_layout;
        },
        clearParentOverlay: function (el, target_class) {
            if (this.active_parent) {
                this.active_parent.$el.removeClass( target_class );
                $(el).removeClass('ddl-frontend-editor-parent');
                this.active_parent.close();
                this.active_parent = null;
            }
        },
        clearOverlay: function (e) {
            if (this.context_menu_opened) return false
            if (this.active_element) {
                this.context_menu_opened = false
                this.active_element.close()
            }
        },
        findNode: function (id, currentNode) {
            var result = false;

            if (currentNode instanceof Backbone.Model && id == currentNode.get('id')) {
                return currentNode
            }
            if (currentNode instanceof Backbone.Model && currentNode.get('Rows')) {
                result = this.findNode(id, currentNode.get('Rows'))
            }
            else if (currentNode instanceof Backbone.Model && currentNode.get('Cells')) {
                result = this.findNode(id, currentNode.get('Cells'))
            }
            else if (currentNode instanceof Backbone.Collection) {

                var len = currentNode.length;
                for (var i = 0; i < len; i++) {
                    result = this.findNode(id, currentNode.at(i))
                    if (result != false) return result

                }
                if (currentNode instanceof Backbone.Model) {
                    for (var key in currentNode) {
                        result = this.findNode(id, currentNode.get(key))
                        if (result != false) return result
                    }
                }
            }
            return result
        },
        render: function () {
            var self = this;
            jQuery( document ).on( 'click', self.handleClick.bind( self ) );
        },
        getLayoutModelToJs:function()
        {
            return this.model.toJSON();
        },
        show_loader:function(caller){
            var obj = caller ? caller.parent() : jQuery(document.body);

            if( caller && caller.data('close') === 'no' ){

                var right = "184px";

                obj.css('position', 'relative');

                WPV_Toolset.Utils.loader.loadShow( caller, true ).css({
                    'position':'absolute',
                    'right':right,
                    'top':'12px'
                });

            } else {
                return;
            }
        },
        hide_loader:function(){
            WPV_Toolset.Utils.loader.loadHide();
        },
        get_index:function(){
            var self = this, layouts = DDLayout.ddl_admin_page.get_layouts_as_array();
            return layouts.indexOf(self.model);
        },
        give_class:function(){
            var self = this;
            if( self.model.is_private() ){
                return 'private_layout';
            }

            return 'layout_'+self.get_index();
        }
    })
})(jQuery)