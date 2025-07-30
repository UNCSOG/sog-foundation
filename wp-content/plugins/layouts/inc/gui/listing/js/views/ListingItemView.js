DDLayout.listing.views.ListingItemView = Backbone.View.extend({
	tagName:'tr',
	initialize:function(options)
	{
		var self = this;

        self.errors_div = jQuery(".js-ddl-message-container");
        self.can_delete =   DDLayout_settings.DDL_JS.user_can_delete;
        self.can_assign = DDLayout_settings.DDL_JS.user_can_assign;
        self.can_edit = DDLayout_settings.DDL_JS.user_can_edit;
        self.can_create = DDLayout_settings.DDL_JS.user_can_create;

		_.bindAll( self, 'render', 'afterRender', 'duplicate_listener', 'markUnassignedTabForChanges');

        self.listenTo(self.eventDispatcher, 'ddl-duplicate-trigger', self.duplicate_listener);
        self.listenTo( self.eventDispatcher, 'tell-group-element-duplicated', self.markUnassignedTabForChanges );

		self.render = _.wrap(self.render, function(render, args) {
			render(args);
			_.defer(self.afterRender, _.bind(self.afterRender, self) );
			return self;
		});

		self.options = options;

		self.$el.data( 'view', self );

		self.$el.addClass("type-dd_layouts status-"+self.model.get('post_status')+" hentry alternate iedit");

		self.restore_or_trash_layout_from_link();
		self.delete_permanently_from_link();
		self.manageSelection();

        self.showMoreOrLess();

		self.render( options );
	},
	render: function( option )
	{
		var self = this,
			options = option || {};


		if( DDLayout_settings.DDL_JS.ddl_listing_status === 'publish')
		{
			if( self.model.get('is_parent') && self.model.has_active_children() )
			{
				self.template = _.template( jQuery('#table-listing-item-parent').html() );
			}
			else if( self.model.has_parent() )
			{
				self.template = _.template( jQuery('#table-listing-item').html() );
				self.$el.addClass('child-layout js-child-layout')
			}
			else
			{
				self.template = _.template( jQuery('#table-listing-item').html() );
			}

			if( self.model.get('types') )
			{
				options.post_types = self.model.get('types');
			}

			options.is_assigned = self.model.is_assigned() ? true : false;
		}
		else if( DDLayout_settings.DDL_JS.ddl_listing_status === 'trash' )
		{
			self.template = _.template( jQuery('#table-listing-item').html() );
		}

        var render = _.extend( self.model.toJSON(), options);

		self.$el.html( self.template( render ) );

		return self;
	},
	afterRender:function()
	{
		var self = this;
		self.manage_tooltip();
		self.highlight();
	},
	restore_or_trash_layout_from_link: function()
	{
	var self = this,
		restore_link = jQuery('.js-layout-listing-restore-link');

		self.$el.on('click', '.js-layout-listing-restore-link', function(event){
			event.preventDefault();

			var data_object = jQuery(this).data('object');

            if( self.can_delete === false && data_object.value == 'trash' ){
                self.no_permission();
                return false;
            }

            if( self.can_assign === false && data_object.value == 'change' ){
                self.no_permission();
                return false;
            }

			if( ( data_object.value == 'trash' || data_object.value == 'change') && jQuery( event.target, self.$el).hasClass('strike') )
			{
				return false;
			}
			else
			{
				self.eventDispatcher.trigger('manage_count_items', data_object, data_object.value );

				self.$el.fadeOut(200, function(){
					self.eventDispatcher.trigger('changeLayoutStatus', data_object, data_object.value, function(){
						self.model.collection.remove( self.model );
                        self.eventDispatcher.trigger('changes_in_dialog_done');
						jQuery('.js-new-layout-parent').find('option[value="'+self.model.get('id')+'"]').remove();
					});
				});
			}
		});
	},
    no_permission:function(){
        this.errors_div.wpvToolsetMessage({
            text: DDLayout_settings.DDL_JS.strings.user_no_caps,
            type: 'error',
            stay: false,
            stay_for:15000,
            close: false,
            onOpen: function() {
                jQuery('html').addClass('toolset-alert-active');
            },
            onClose: function() {
                jQuery('html').removeClass('toolset-alert-active');
            }
        });
    },

	manage_tooltip:function()
	{
		var self = this,
			el = jQuery('span.strike a', self.$el),
			message = '';

			el.tooltip({
				position:{ my: "left top+15", at: "left middle", collision: "flipfit" },
				open:function( e, ui )
				{
					var data = 	jQuery( e.target).data('object');

					if( self.model.is_parent() )
					{

						if( data && data.value == 'trash' && self.model.has_active_children() )
						{
							message = DDLayout_settings.DDL_JS.strings.is_a_parent_layout;
						}
						else
						{
							message = DDLayout_settings.DDL_JS.strings.is_a_parent_layout_and_cannot_be_changed;
						}
					}
					else
					{
						switch( self.model.get('group') )
						{
                            case 4:
                                var len = self.model.get('loops').length;
                                if( len === 1 )
                                {
                                    message = DDLayout_settings.DDL_JS.strings.to_an_archive;
                                }
                                else
                                {
                                    message = DDLayout_settings.DDL_JS.strings.to_archives.printf( len.toString() );
                                }
                                break;
							case 3:
								var len = self.model.get('types').length;
								if( len === 1 )
								{
									message = DDLayout_settings.DDL_JS.strings.to_a_post_type;
								}
								else
								{
									message = DDLayout_settings.DDL_JS.strings.to_post_types.printf( len.toString() );
								}
								break;
							case 2:
								var len = self.model.get('posts').length;
								if( len === 1 )
								{
									message = DDLayout_settings.DDL_JS.strings.to_a_post_item;
								}
								else
								{
									message = DDLayout_settings.DDL_JS.strings.to_posts_items.printf( len.toString() );
								}
								break;
						}

						message = message;
					}

					jQuery( e.target).tooltip( "option", "content", message );
				}
			});
	},

	delete_permanently_from_link: function()
	{
		var self = this,
			delete_permanently_link = jQuery('.js-layout-listing-delete-permanently-link');

		self.$el.on('click', '.js-layout-listing-delete-permanently-link', function(event){
			event.preventDefault();
			var data_object = jQuery(this).data('object');

            if( self.can_delete === false ){
                self.no_permission();
                return false;
            }

			self.eventDispatcher.trigger('delete_forever', data_object);
		})
	},

	manageSelection : function()
	{
		var self = this,
			select = jQuery('.js-select-layout-action-in-listing-page');

		self.$el.on('click', '.js-select-layout-action-in-listing-page', function(event){

			var data_object = jQuery(this).data('object');

			if( data_object.value === 'change' )
			{
                if( jQuery(this).data('do-not-click-me') === true || self.can_assign === false ) {
                    self.no_permission();
                    return;
                }

                jQuery(this).data('do-not-click-me', true);
				DDLayout.listing_manager.loadChangeUseDialog( data_object );

			}
			else if( ( data_object.value === 'trash' && self.can_delete ) || data_object.value === 'publish' )
			{
				jQuery( '.js-layout-listing-restore-link', self.$el ).trigger('click');
			}
			else if( data_object.value === 'delete' && self.can_delete )
			{
				jQuery( '.js-layout-listing-delete-permanently-link', self.$el ).trigger('click');
			}
			else if( data_object.value === 'duplicate' && self.can_create )
			{
				self.handle_duplication( data_object );
			}
            else{
                self.no_permission();
            }
		});

		self.$el.on('blur', '.js-select-layout-action-in-listing-page', function(event){
				jQuery(this).val("");
		});

        self.$el.on('mouseout', '.js-select-layout-action-in-listing-page', function(event){
                jQuery(this).data('do-not-click-me', false);
        });
	},
	duplicate:function( data_obj, toolset_assets )
	{
        var self = this;

		var params = {
			action: 'duplicate_layout',
			'layout-duplicate-layout-nonce':data_obj.duplicate_nonce,
			layout_id:data_obj.layout_id
		};

        if( toolset_assets ){
            params['toolset_assets'] = toolset_assets;
        }


		jQuery('#wpcontent').loaderOverlay('show', {class:'loader-overlay-high-z'});

		DDLayout.listing_manager.listing_table_view.model.trigger( 'make_ajax_call', params, function(model, response, object, args){

			DDLayout.listing_manager.listing_table_view.current = response.message.layout_id;

			jQuery('#wpcontent').loaderOverlay('hide');

			DDLayout.listing_manager.listing_table_view.manage_count_items( data_obj );

			if( response.message.hasOwnProperty('toolset_assets') && response.message.toolset_assets !== null ){
                    var dialog = new DDLayout.ListingMain.DialogDuplicate({
                        title: self.model.get('layout').get('name') +' '+ DDLayout_settings.DDL_JS.strings.duplicate_results_title,
                        selector: '#ddl-duplicate-response-template',
                        autoOpen:false,
                        template_object: {
                            layout_name:self.model.get('layout').get('name'),
                            duplicate: response.message.toolset_assets.duplicate,
                            remapped:response.message.toolset_assets.remap,
                            duplicate_message:data_obj.duplicate_message,
                            show_details_anchor_text:DDLayout_settings.DDL_JS.strings.duplicate_anchor_text
                        },
						buttons:[
							{
								text: DDLayout_settings.DDL_JS.strings.close,
								icons: {
									secondary: ""
								},
								click: function () {
									jQuery(this).ddldialog("close");
								}
							}
						]
                    });

                    dialog.$el.on('ddldialogclose', function(event){
                        DDLayout.listing_manager.listing_table_view.eventDispatcher.trigger('changes_in_dialog_done');
                        self.eventDispatcher.trigger( 'tell-group-element-duplicated', response.message );
                        jQuery('.js-ddl-duplicate-show-details').off('click', null);
                        dialog.remove();
                    });

                dialog.$el.on('ddldialogopen', function(event){
                        var open = false;

                        jQuery('.js-ddl-duplicate-show-details').on('click', function(event){
                                    event.preventDefault();

                                    var $me = jQuery(this);

                                    if( open === false ){
                                        jQuery('.js-ddl-duplicate-info-details').slideDown(400, function(){
                                                open = true;
                                                $me.text( DDLayout_settings.DDL_JS.strings.duplicate_anchor_text_hide );
                                        });
                                    } else {
                                        jQuery('.js-ddl-duplicate-info-details').slideUp(400, function(){
                                            open = false;
                                            $me.text( DDLayout_settings.DDL_JS.strings.duplicate_anchor_text );
                                        });
                                    }

                        }).css('cursor', 'pointer');
                });

                dialog.dialog_open();

			} else {

                DDLayout.listing_manager.listing_table_view.eventDispatcher.trigger('changes_in_dialog_done');
                self.eventDispatcher.trigger( 'tell-group-element-duplicated', response.message );

            }

		});
	},
	handle_duplication:function( data_obj ){
		var self = this,
			model = this.model;

		if( model.has_layout()  ){

            var cells = model.get('layout').cells_of_types( _.keys( DDLayout_settings.DDL_JS.toolset_cells_data ) );

            if( cells.length === 0 ){

                self.duplicate( data_obj );

            } else {

                DDLayout.ListingMain.ToolsetResourcesHandler.init( model.get('layout'), cells, data_obj );

                model.get('layout').listenTo(model.get('layout'),'ddl-duplicate-trigger', self.duplicate_listener);
            }

		} else {
			self.duplicate( data_obj )
		}
	},
    duplicate_listener:function(data){
        var self = this;

        if( data && data.hasOwnProperty('duplicate') && _.isArray(data.duplicate) && data.duplicate.length > 0 ){
            self.model.get('layout').trigger('ddl-duplicate-completed');
            self.duplicate( data.data_obj, data.duplicate );
        } else {
            self.model.get('layout').trigger('ddl-duplicate-completed');
            self.duplicate( data.data_obj );
        }

    },
	highlight:function()
	{
		var self = this;
		try
		{
			if( self.model.get( 'id' ) && DDLayout.listing_manager.listing_table_view.current && DDLayout.listing_manager.listing_table_view.current === self.model.get( 'id' ) )
			{
				self.eventDispatcher.trigger( 'do_what_you_have_to_on_scroll', self );
			}
		}
		catch( e )
		{
			console.log( e.message );
		}

	},
    showMoreOrLess:function(){
        var self = this,
            $span = jQuery('#js-all-posts-'+self.model.get('id'), self.$el),
            $a = $span.find('a');

        self.loader = new WPV_Toolset.Utils.Loader();

        self.$el.on('click', '#js-all-posts-'+self.model.get('id')+' a', function(event){
                event.preventDefault();
                event.stopImmediatePropagation();

            var show = self.model.get('show_posts'),
                $loader_host = jQuery(this).closest('tbody').find('td').eq(1).find('span');

            self.loader.loadShow( $loader_host).css({
                'position':'relative',
                'top' : '-3px'
            });

            if( show <= self.model.NUM_POSTS ){
                self.showMore('#js-all-posts-'+self.model.get('id')+' a');
            } else{
                self.showLess('#js-all-posts-'+self.model.get('id')+' a');
            }
        });
    },
    showMore:function(button){
        var self = this;
        self.model.set( 'show_posts', -1 );
        jQuery(button).find('i').removeClass('fa-caret-down').addClass('fa-caret-up');
        self.getPostsFromServer(function( model, response, object, args){
            if( response.hasOwnProperty('Data') && response.Data.hasOwnProperty('posts') && response.Data.posts.length ){
                self.model.set( 'posts', response.Data.posts );
                self.model.set( 'show_posts', response.Data.posts.length );
                self.loader.loadHide().css('float', 'right');
                self.render( self.options );
                jQuery(button).find('i').removeClass('fa-caret-down').addClass('fa-caret-up');
            }
        });
    },
    showLess:function(button){
        var self = this,
        less = self.model.get('posts').slice(0, self.model.NUM_POSTS);
        jQuery(button).find('i').removeClass('fa-caret-up').addClass('fa-caret-down');
        self.model.set( 'show_posts', self.model.NUM_POSTS );
        self.model.set('posts', less);
        self.render(self.options);
        _.defer(function(){
            self.loader.loadHide().css('float', 'right');
        });
    },
    getPostsFromServer:function(callback){
        var self = this,
            params = {post_types : DDLayout.listing_manager.listing_table_view.model.getPostTypesAssigned()};
        self.model.get_data_from_server(params, callback);
    },
    markUnassignedTabForChanges: function( ){
        DDLayout.listing_manager.markUnassignedTabForChanges( 1 );
    }
});
