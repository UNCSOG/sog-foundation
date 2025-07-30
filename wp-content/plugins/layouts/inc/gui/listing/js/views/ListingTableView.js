DDLayout.listing.views.ListingTableView = Backbone.View.extend({
	el: ".js-dd-layout-listing",
    $tabs: null,
    activeGroup: 'post_types',
    marked: {},
	initialize: function (options) {
		var self = this;

        self.errors_div = jQuery(".js-ddl-message-container");
        self.can_delete =   DDLayout_settings.DDL_JS.user_can_delete;
        self.can_assign = DDLayout_settings.DDL_JS.user_can_assign;
        self.can_edit = DDLayout_settings.DDL_JS.user_can_edit;
        self.can_create = DDLayout_settings.DDL_JS.user_can_create;

		_.bindAll( self, 'render', 'afterRender', 'tabActivate' );

		self.render = _.wrap(self.render, function(render, args) {
			render(args);
			_.defer(self.afterRender, _.bind(self.afterRender, self) );
			return self;
		});

		self.options = options;
		self.$el.data('view', self);
		self.model.is_searching = false;
		self.listenTo(self.model, 'sync', self.render, options);
        self.listenTo(self.eventDispatcher, 'changes_in_dialog_done', self.render, options);
		self.listenTo(self.model, 'changed_usage', self.render, options);
		self.listenTo(self.eventDispatcher, 'resort', self.render, options);
		self.listenTo(self.eventDispatcher, 'changeLayoutStatus', self.changeLayoutStatus);
		self.listenTo(self.eventDispatcher, 'delete_forever', self.deleteForever);
		self.listenTo(self.model, 'removed_batched_items', self.manage_count_items);
		self.listenTo(self.eventDispatcher, 'manage_count_items', self.manage_count_items);
		self.listenTo( self.model, 'done_searching', self.render );
		self.listenTo( self.eventDispatcher, 'do_what_you_have_to_on_scroll', self.highlight );

        self.model.listenTo(self.model, 'sync', function(event){
                WPV_Toolset.Utils.loader.loadHide();
        });

        self.model.listenTo(self.model, 'get_data_from_server', function(params){
        		if( params && params.action === "duplicate_layout" || "ddl_assign_layout_to_posts" === params.action || params.action.indexOf('js_change_layout_usage') !== -1 ) {
        			return;
                }
				jQuery('.js-do-bulk-action').parent().css( {'position':'relative', 'min-width' : '234px' } );
				var width = jQuery('.js-do-bulk-action').parent().width() - 50;
                WPV_Toolset.Utils.loader.loadShow( jQuery('.js-do-bulk-action'), true).css({
						'position' : 'absolute',
						'top' : '3px',
						'left' : width.toString() + 'px'
					});
        });

		self.manage_sort_icon_init();

		self.resort_by();
		self.manage_search();

		self.render(options);
	},
	render: function ( option ) {

		//console.log('render the full table');
		var self = this,
			options = option || {};

		self.model.set_depths_and_group();

		if( self.model.is_searching === false )
		{
			self.model.cache = self.model.get('Groups').toJSON();
		}
		else if( self.model.is_searching )
		{
			self.model.is_searching = false;
		}

		self._cleanBeforeRender(self.$el);

        if( DDLayout_settings.DDL_JS.ddl_listing_status === 'trash' ){
            options = _.extend(options, { model: self.model.get('Groups')});
            self.groups_view = new DDLayout.listing.views.ListingGroupsView(options);
        } else {
            self.initTabs();
            var renderGroup = option && option.renderGroup ? option.renderGroup : self.model.get('Groups').getActiveAsArray( self.activeGroup );

            if ( renderGroup.length > 0 ) {
                var groups = new DDLayout.listing.models.ListingGroups( renderGroup );
                options = _.extend(options, { model: groups, activeGroup: self.activeGroup });
                self.groups_view = new DDLayout.listing.views.ListingGroupsView(options);
            }
        }


		self.select_bulk_action();
		self.select_single_item_in_list();
		self.handle_bulk_action();

		return self;
	},
    initTabs: function () {
        var self = this,
            $tabs = jQuery('#layout-listing-tabs'),
            $a = jQuery('a[href="#tab-' + self.activeGroup + '"]'),
            index = $a.data('index');

        if( null !== self.$tabs ){
            self.$tabs.tabs('destroy');
        }

        self.$tabs = $tabs.tabs({
            active: index,
            cached: true,
            activate: self.tabActivate
        }).show();

    },
    tabActivate: function( event, ui ){
        var self = this,
            index = ui.newTab.index(),
            activeId = jQuery("#layout-listing-tabs ul>li a").eq( index ).attr("href"),
            active = activeId.split('#tab-')[1];

        self.activeGroup = active;
        self.model.activeGroup = self.activeGroup;

        var renderGroup = self.model.get('Groups').getActiveAsArray( self.activeGroup );

        if( renderGroup.length === 0 ){
            self.model.get_data_from_server( {'group_slug' : self.activeGroup});
        } else{
            self.render( { renderGroup: renderGroup } );
        }

        jQuery('.js-tab-alert-icon', jQuery("#layout-listing-tabs ul>li").eq( index ) ).hide( function(){
            jQuery(this).remove();
            self.marked[self.activeGroup] = false;
        });
    },
    forceActiveReload: function(){
	    var self = this;

	    self.model.get('Groups').removeActive( self.activeGroup );
    },
	afterRender:function()
	{
		var self = this;
		self.eventDispatcher.trigger( 'table_view_after_render', self );
	},
	manage_sort_icon_init:function()
	{
			var self = this, direction = jQuery.jStorage.get( 'reverseSortDirection' ) || false;

			if( direction )
			{
				jQuery( '.fa-sort-alpha-asc').removeClass('fa-sort-alpha-asc').addClass('fa-sort-alpha-desc');
				jQuery( '.fa-sort-amount-asc').removeClass('fa-sort-amount-asc').addClass('fa-sort-amount-desc');
				self.icon_title = 'fa-sort-alpha-desc';
				self.icon_date = 'fa-sort-amount-desc';
			}
			else
			{
				jQuery( '.fa-sort-alpha-desc').removeClass('fa-sort-alpha-desc').addClass('fa-sort-alpha-asc');
				jQuery( '.fa-sort-amount-desc').removeClass('fa-sort-amount-desc').addClass('fa-sort-amount-asc');
				self.icon_title = 'fa-sort-alpha';
				self.icon_date = 'fa-sort-amount';
			}

			if( jQuery.jStorage.get( 'sortKey') == 'post_title' ) {
				jQuery( '.js-icon-sort-title').addClass('sort-icon-active');
				jQuery( '.js-icon-sort-date').addClass('sort-icon-inactive');
			}
			else
			{
				jQuery( '.js-icon-sort-title').addClass('sort-icon-inactive');
				jQuery( '.js-icon-sort-date').addClass('sort-icon-active');
			}
	},
	/*
	 ** remove all the children view to clean event queue
	 */
	_cleanBeforeRender: function (el) {
		var self = this;

		el.find('tbody').each(function (i, v) {
			if (jQuery(v).data('view')) {
				self._cleanBeforeRender(jQuery(v));
				jQuery(v).data('view').remove();
			}

		});
	},
	resort_by: function () {
		var self = this,
			$button = jQuery('.js-views-list-sort');

		jQuery(document).on('click', '.js-views-list-sort', function (event) {
			event.preventDefault();
			var sort_by = jQuery(this).data('orderby')
				, order_by = 'post_title'
				, icon = '';

		//	console.log( jQuery(this).data(), sort_by );

			if (sort_by == 'title') {
				order_by = 'post_title';
				jQuery('a[data-orderby="date"]').find('i').removeClass('sort-icon-active').addClass('sort-icon-inactive');
				jQuery(this).find('i').removeClass('sort-icon-inactive').addClass('sort-icon-active');
				icon = self.icon_title;
			} else if( sort_by == 'id' ){
				order_by = 'id';
				jQuery('a[data-orderby="date"]').find('i').removeClass('sort-icon-active').addClass('sort-icon-inactive');
				jQuery(this).find('i').removeClass('sort-icon-inactive').addClass('sort-icon-active');
				icon = self.icon_date;
			}
			else if (order_by = 'date') {
				order_by = 'post_date';
				jQuery('a[data-orderby="title"]').find('i').removeClass('sort-icon-active').addClass('sort-icon-inactive');
				jQuery(this).find('i').removeClass('sort-icon-inactive').addClass('sort-icon-active');
				icon = self.icon_date;
			}

			self.resort(order_by, jQuery(this), icon)
		});
	},
	resort: function (by, target, icon) {
		var self = this, sort_by = by;

		self.model.get('Groups').each(function (v, k, l) {
			if (v.get('items')) {
				var collect = v.get('items');

				collect.sortKey = sort_by;

				if (collect.reverseSortDirection === false) {
					collect.reverseSortDirection = true;
				}
				else {
					collect.reverseSortDirection = false;
				}

				if( icon.indexOf('-desc') === -1 ){
					target.find('i').removeClass(icon+'-asc').addClass(icon + '-desc');
					self.icon_title = 'fa-sort-alpha-desc';
					self.icon_date = 'fa-sort-amount-desc';
				}
				else
				{
					var new_ico = icon.split('-desc');
					target.find('i').removeClass(icon).addClass(new_ico[0]+'-asc');
					self.icon_title = 'fa-sort-alpha';
					self.icon_date = 'fa-sort-amount';
				}

				jQuery.jStorage.set( 'sortKey', collect.sortKey );
				jQuery.jStorage.set( 'reverseSortDirection', collect.reverseSortDirection );
				collect.sort();
			}
		});
		self.eventDispatcher.trigger('resort');
	},
	select_bulk_action: function () {
		var self = this
			, select_all = jQuery('.js-select-all-layouts', self.$el)
			, checkboxes = jQuery('.js-selected-items', self.$el);

		jQuery(document).on('change', '.js-select-all-layouts', function (event) {
			if (jQuery(this).is(':checked') === true) {
				select_all.each(function (i) {
					jQuery(this).prop('checked', true);
				});

				checkboxes.each(function (i) {
					if(!jQuery(this).is(':disabled')) {
						jQuery(this).prop('checked', true);
					}
				});
			}
			else if (jQuery(this).is(':checked') === false) {
				select_all.each(function (i) {
					jQuery(this).prop('checked', false);
				});
				checkboxes.each(function (i) {
					jQuery(this).prop('checked', false);
				});
			}
		});
	},
	select_single_item_in_list: function () {
		var self = this
			, select_all = jQuery('.js-select-all-layouts', self.$el)
			, checkboxes = jQuery('.js-selected-items', self.$el);

		jQuery(document).on('change', '.js-selected-items', function (event) {
			var len = checkboxes.length;

			if (jQuery(this).is(':checked') === false) {
				if (select_all.prop('checked') === true) {
					select_all.prop('checked', false);
				}
			}
			else if (jQuery(this).is(':checked') === true && jQuery('.js-selected-items' + ':checked').length === len) {
				select_all.prop('checked', true);
			}
		});
	},
	changeLayoutStatus: function (data_obj, value, callaback) {
		var self = this;

		var params = {
			action: 'set_layout_status',
			'layout-select-trash-nonce': data_obj.trash_nonce,
			status: value,
			layout_id: data_obj.layout_id
		};

		self.model.trigger('make_ajax_call', params, callaback);
	},
	handle_bulk_action: function () {
		var self = this,
			select_bulk = jQuery('.js-select-bulk-action')
			, checkboxes = jQuery('.js-selected-items')
			, select_bulk = jQuery('.js-select-bulk-action')
			, apply_bulk = jQuery('.js-do-bulk-action');

		jQuery(document).on('click', '.js-do-bulk-action', function (event) {
			event.preventDefault();

			if (+select_bulk.val() === -1 || jQuery('.js-selected-items' + ':checked').length === 0) {
				return false;
			}

			else if ( ( select_bulk.val() === "trash" || select_bulk.val() === "publish" ) && self.can_delete ) {

				var data = jQuery(this).data('object'),
					to_delete = [];

				jQuery('.js-selected-items' + ':checked').each(function () {
					to_delete.push(+jQuery(this).val());
					jQuery('.js-new-layout-parent').find('option[value="'+jQuery(this).val()+'"]').remove();
				});

				data.layout_id = to_delete;
				data.value = select_bulk.val();

				self.changeLayoutStatus(data, select_bulk.val(), function (model, response) {
					if (response && response.message) {
						//self.model.remove_by_id( response.message, data )
						_.each(response.message, function (v) {
							self.model.trigger('removed_batched_items', data );
						});
                        self.eventDispatcher.trigger('changes_in_dialog_done');
                        jQuery('.js-select-all-layouts').prop('checked', false );
					}
				});
			}
			else if (select_bulk.val() === "delete" && self.can_delete ) {
				var data = jQuery(this).data('object'),
					to_delete = [];

				jQuery('.js-selected-items' + ':checked').each(function () {
					to_delete.push(+jQuery(this).val());
					jQuery('.js-new-layout-parent').find('option[value="'+jQuery(this).val()+'"]').remove();
				});

				data.layout_id = to_delete;
				data.value = select_bulk.val();

				self.deleteForever(data);
			} else {
                self.no_permission();
            }

			select_bulk.val(-1);
		});

	},
	deleteForever: function ( data_obj, callback ) {

		var self = this, params = {
			action: 'delete_layout_record',
			'layout-delete-layout-nonce': data_obj.delete_nonce,
			layout_id: data_obj.layout_id
		};

		self.model.trigger('make_ajax_call', params, function (model, response) {
			if (response && response.message) {
				//self.model.remove_by_id( response.message, data_obj )
				_.each(response.message, function (v) {
					self.model.trigger('removed_batched_items', data_obj );
				});
                self.eventDispatcher.trigger('changes_in_dialog_done');
                jQuery('.js-select-all-layouts').prop('checked', false );
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
	manage_count_items:function( data_object )
	{

		//console.log( 'should count ', data_object )

		var trash = jQuery('.count-trash')
			, publish =  jQuery('.count-published')
			, trash_count = +trash.text()
			, publish_count = +publish.text();

		if( data_object.value == 'trash' )
		{
			trash.text( trash_count + 1 );
			publish.text( publish_count -1 );
		}
		else if( data_object.value == 'publish' )
		{
			trash.text( trash_count - 1 );
			publish.text( publish_count + 1 );
		}
		else if( data_object.value == 'delete' )
		{
			trash.text( trash_count - 1 );
		}
		else if( data_object.value == 'duplicate' ){
			publish.text( publish_count + 1 );
		}
	},
	manage_search:function()
	{
		var self = this,
			$button= jQuery( '#search-submit'),
			$input = jQuery('#post-search-input', self.$el),
			$current = jQuery( 'a.current', self.$el),
			init_val = $input.val();

		self.$el.on('click', '#search-submit', function(event){
			event.preventDefault();
			self.model.is_searching = true;
			self.model.search( $input.val() );
			$input.val('');
		});

		$input.on('focus', function(){
			if( jQuery(this).val() == '' || jQuery(this).val() == init_val )
			jQuery(this).val('');
		});

		$input.on('blur', function(){
			if( jQuery(this).val() === '' )
			jQuery(this).val( init_val );
		});

		$input.on('keyup search', function() {

			if( jQuery(this).val() )
			{
				self.model.is_searching = true;
				self.model.search( jQuery(this).val() );
			}
			else
			{
				$current.trigger('click');
			}
		});

		jQuery( self.$el ).on('click', 'a.current', function(event){
			var $me = jQuery(event.target);
			if( $me.prop('name') == DDLayout_settings.DDL_JS.ddl_listing_status )
			{
				event.preventDefault();
                self.model.set( 'Groups', null );
				self.model.parse( {Data:self.model.cache} );
				self.model.trigger('done_searching');
                self.model.searchCache = null;
			}
		});
	},
	highlight:function( view, avoid )
	{
		var self = this;

		window.scrollTo( 0, view.$el.offset().top - ( view.$el.height() * 1.5 )  );

		view.$el.css({background:"#FFFFE0", opacity:0.2});
		view.$el.animate({
			opacity:1,
			specialEasing: {
				background: "easeOutBounce"
			}
		}, 1600, function() {
			view.$el.animate({
				opacity:0.9,
				specialEasing: {
					background: "linear"
				}
			}, 400, function(){
				view.$el.css({background:"#f9f9f9", opacity:1})
			});
			DDLayout.listing_manager.listing_table_view.current = null;
		});
	}
});
