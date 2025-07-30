var DDLayout = DDLayout || {};


DDLayout.IndividualAssignmentManager = function($)
{
    var self = this, per_page = DDLayout_settings.DDL_JS.max_num_posts, page = 1, scroll_reload = true, scroll_control = 0, page_search = 1, scroll_reload_search=true, scroll_control_search=0;

    self.selected_post_type = 'page';

    self.init = function() {
        DDLayout.changeLayoutUseHelper.eventDispatcher.listenTo(DDLayout.changeLayoutUseHelper.eventDispatcher, 'ddl-dialog-ass-open', self.dialog_open_complete );
        DDLayout.changeLayoutUseHelper.eventDispatcher.listenTo(DDLayout.changeLayoutUseHelper.eventDispatcher, 'change-layout-use-reload', self.dialog_open_complete );
        Toolset.hooks.addAction('ddl-reload-post-list-by-language', self.handle_language_reload);

    };

    self._refresh_where_used_ui = function (include_spinner) {
	    DDLayout.ddl_admin_page.initialize_where_used_ui(self._current_layout, include_spinner);
    };

    self.get_current_layout = function()
    {
        return self._current_layout;
    };

    self.set_current_layout = function( layout_id )
    {
        self._current_layout = layout_id;
    };



    self.populate_assigned_pages = function(layout_id){

	$('.individual-pages-list').empty();

	var data = {
	    action: 'ddl_return_assigned_layout_list_json',
	    layout_id: layout_id,
	    wpnonce: self._nonce,
	    ddl_lang: Toolset.hooks.applyFilters('ddl-js-apply-language', null),
	};

	$.post(ajaxurl, data, function (results) {
	    var obj = JSON.parse(results);
	    $.each(obj.data, function(key, value) {
		jQuery(".individual-pages-list").append('<li>'+value.post_title+' <input type="checkbox"  value="'+value.ID+'"  class="js-remove-individual-page-item hidden"></li>');
	    });
	    self._initialize_single_post_handlers();
	});

    };


    self.dialog_open_complete = function (dialog, data) {

	//self.set_radio_post_type_checked();
	page = 1;
	page_search = 1;

	self.set_current_layout(data);

	self._nonce = $('#wp_nonce_individual-pages-assigned').attr('value');
	jQuery('.js-individual-popup-tabs').ddltabs({
	    scrollTabs: [1, 2],
	    activate: function (event, ui) {
		var checkboxes = jQuery(ui.newPanel[0]).find('input[type="checkbox"]');

		checkboxes.each(function (i, v) {
		    jQuery(this).prop('checked', false);
		});

		DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('checkboxes_changed', jQuery('.js-individual-posts-update-wrap'), "individual_posts_assign", 0);
	    }
	});
	jQuery('.js-individual-popup-tabs').ddltabs('option', 'active', 0); // Activate the first tab

	self.handle_refresh_all_on_scroll();

	self._get_posts_for_layout();

	$('.js-connect-to-layout').off('click');
	$('.js-connect-to-layout').on('click', self._handle_content_to_layout);
	$('.js-connect-to-layout').prop('disabled', true);


	self._initialize_checkbox_handling();

	self._initialize_quick_search();

	$('#ddl-individual-post-type-page').prop('checked', true);

	$('#ddl-individual-post-type-page,#ddl-individual-post-type-any').off('click');
	$('#ddl-individual-post-type-page,#ddl-individual-post-type-any').on('click', self._handle_post_type_change);

    };

    self.handle_refresh_all_on_scroll = function(){
        var $scroller = jQuery("div[id^='js-ddl-individual-view-all']");
        self.reset_scroller_all();
        $scroller.on('ddl.scroll', self.handle_scroll_all);
    };

    self.handle_scroll_all = function(event, data){
        var height = data.height, pos = data.pos;
        if(  pos >= height && height > scroll_control && scroll_reload ){
            page++;
            scroll_reload = false;
            scroll_control = height;
            self._fill_view_all_tab( self.selected_post_type, function(){
                scroll_reload = true;
            }, true);
        }
    };

    self.reset_scroller_all = function(){
        var $scroller = jQuery("div[id^='js-ddl-individual-view-all']");
        scroll_reload = true;
        scroll_control = 0;
        $scroller.off('ddl.scroll', self.handle_scroll_all);
    };

    // scroll on search tab
    self.handle_refresh_search_on_scroll = function(){
        var $scroller_search = jQuery("div[id^='js-ddl-individual-search']");
        self.reset_scroller_search();
	$scroller_search.on('ddl.scroll', self.handle_scroll_search);
    };

    self.handle_scroll_search = function(event, data){
        var search_height = data.height, pos = data.pos;
        if(  pos >= search_height && search_height > scroll_control_search && scroll_reload_search ){
            page_search++;
            scroll_reload_search = false;
            scroll_control_search = search_height;
            self._update_quick_search_results( $('.js-individual-quick-search'), function(){
                scroll_reload_search = true;
            }, true);
        }
    };

    self.reset_scroller_search = function(){
        var $scroller_search = jQuery("div[id^='js-ddl-individual-search']");
        scroll_reload_search = true;
        scroll_control_search = 0;
        $scroller_search.off('ddl.scroll', self.handle_scroll_search);
    };
    // end scoll on search



    self._initialize_single_post_handlers = function () {
        $('.js-wpddl-remove-single-posts-enable').on('click', self._enable_single_post_remove);
        $('.js-remove-individual-page-item').on('change', self._single_post_remove_add_to_list);
        $('.js-wpddl-remove-single-posts-cancel').on('click', self._single_post_remove_cancel);
        $('.js-wpddl-remove-single-posts').on('click', self._handle_remove);
        self.single_posts_remove_array = [];

        $('.js-wpddl-remove-single-posts-buttons').css({'height':'0'});


        if ( $('.individual-pages-list input').length == 0 ){
            $('.js-wpddl-remove-single-posts-enable').addClass('hidden');
        } else {
	    $('.js-wpddl-remove-single-posts-enable').removeClass('hidden');
	}
        if( DDLayout_settings.DDL_JS.is_listing_page !== true && $('.individual-pages-list input').length > 0 ){
           $('.js-wpddl-remove-single-posts-buttons').css({'height':'41px'});
        }
        if( DDLayout_settings.DDL_JS.is_listing_page === true && $('.individual-pages-list input').length > 0 ){
           $('.js-wpddl-remove-single-posts-buttons').css({'height':'28px'});
        }
    };

    self._single_post_remove_cancel = function () {
        $('.js-wpddl-remove-single-posts-cancel, .js-wpddl-remove-single-posts').addClass('hidden');
        $('.js-wpddl-remove-single-posts-enable').removeClass('hidden');
        $('.individual-pages-list input').each( function () {
	    $(this).addClass('hidden').prop('checked', false);
	});
    };

    self._single_post_remove_add_to_list = function () {

        if ( $(this).prop('checked') === true ){
            self.single_posts_remove_array[$(this).val()] = 1;
        } else{
            delete self.single_posts_remove_array[$(this).val()];
        }
        if ( Object.keys(self.single_posts_remove_array).length > 0 ){
            $('.js-wpddl-remove-single-posts').prop('disabled', false).addClass('button-primary');
        } else{
            $('.js-wpddl-remove-single-posts').prop('disabled', true).removeClass('button-primary');
        }
    };

    self._enable_single_post_remove = function () {
        $('.js-wpddl-remove-single-posts-cancel, .js-wpddl-remove-single-posts').removeClass('hidden');
        $('.js-wpddl-remove-single-posts-enable').addClass('hidden');
        self.single_posts_remove_array = {};
        $('.individual-pages-list input').each( function () {
	    $(this).removeClass('hidden').prop('checked', false);
	});

    };

    self._initialize_checkbox_handling = function () {
	$('.js-ddl-individual-posts').off('change');
	$('.js-ddl-individual-posts').on('change', self._handle_post_checkbox_click);
	DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('ddl-posts-checkboxes-loaded-and-initialised');
    };


    self._handle_post_checkbox_click = function (event) {
	DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('checkboxes_changed', jQuery('.js-individual-posts-update-wrap'), jQuery(event.target).prop('name'), $('.js-ddl-individual-posts:checked').length );
    };

    self._get_posts_for_layout = function () {
	self._initialize_single_post_handlers();
    };

    self._handle_content_to_layout = function (event) {
	DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('before_sending_data', event);
	$('.js-connect-to-layout').prop('disabled', true);
	$('.js-connect-to-layout').addClass('button-secondary').removeClass('button-primary');

	var posts = Array();

	$('.js-ddl-individual-posts:checked').each(function () {
	    var post_id = $(this).val();
	    if (self._add_assigned_post(post_id, $(this).data('title'))) {
		posts.push(post_id);
	    }
	    $(this).prop('checked', false);
	})

	$('.js-individual-pages-assigned ul li').fadeIn(500);



	var data = {
	    action: 'ddl_assign_layout_to_posts',
	    wpnonce: self._nonce,
	    layout_id: self._current_layout,
	    posts: posts,
	    ddl_lang: Toolset.hooks.applyFilters('ddl-js-apply-language', null)
	};

	var spinner = $(self._get_spinner_code());
	spinner.insertBefore('.js-connect-to-layout');

	DDLayout.changeLayoutUseHelper.get_current_post_list_handler().set_amount(-1);
	DDLayout.changeLayoutUseHelper.get_current_post_list_handler().set_more_or_less(true);
	data['single_amount_to_show_in_dialog'] = DDLayout.changeLayoutUseHelper.get_current_post_list_handler().get_amount();


	if (DDLayout_settings && DDLayout_settings.DDL_JS && DDLayout_settings.DDL_JS.is_listing_page === true)
	{
	    data.in_listing_page = 'yes';
	    data.html = 'listing';

	    DDLayout.listing_manager.listing_table_view.model.trigger('make_ajax_call', data, function (model, response, object, args) {
		DDLayout.listing_manager.listing_table_view.current = +data.layout_id;
		spinner.remove();
		DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('data_sent_to_server', $(event.target).closest('div.js-change-wrap-box'), DDLayout_settings.DDL_OPN.INDIVIDUAL_POSTS_OPTION, response.message, function () {
		    self.set_radio_post_type_checked();
		});
			self.set_radio_post_type_checked();

			if( DDLayout.listing_manager.listing_table_view.model.activeGroup !== 'single' ){
                self.markUnassignedTabForChanges( 2 );
			}

	    });
	}
	else
	{
	    data.html = 'editor';
	    $.post(ajaxurl, data, function (response) {
		//self._refresh_where_used_ui(false);

		spinner.remove();
		DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('data_sent_to_server', $(event.target).closest('div.js-change-wrap-box'), DDLayout_settings.DDL_OPN.INDIVIDUAL_POSTS_OPTION, response.message, function () {
		    self.set_radio_post_type_checked();
		});
		self.set_radio_post_type_checked();
	    }, 'json');
	}
    };

    self.markUnassignedTabForChanges = function( group ){
        var $tab = jQuery('div#layout-listing-tabs ul li[data-group="'+group+'"]');
        $tab.append('<i class="fa fa-exclamation-triangle js-tab-alert-icon tab-alert-icon"></i>');
    };


    self._handle_remove = function (event) {
	$('.js-wpddl-remove-single-posts').prop('disabled', true).removeClass('button-primary');
	DDLayout.changeLayoutUseHelper.eventDispatcher.trigger( 'before_sending_data', event );
	var list_item = $(this).parent()
	    , $container = list_item.closest('div.js-change-wrap-box');

	    var data = {
		action : 'ddl_remove_layout_from_post',
		wpnonce : self._nonce,
		post_ids : JSON.stringify(self.single_posts_remove_array),
		layout_id:self.get_current_layout(),
		ddl_lang:Toolset.hooks.applyFilters('ddl-js-apply-language', null)
	    };

	    data['single_amount_to_show_in_dialog'] = DDLayout.changeLayoutUseHelper.get_current_post_list_handler().get_amount();
	    DDLayout.changeLayoutUseHelper.get_current_post_list_handler().show_loader();
	    $('.individual-pages-list input').each( function () {
		if (self.single_posts_remove_array.hasOwnProperty($(this).val())) {
		    $(this).parent().fadeOut(500, function () { $(this).remove()});
		}
	    });
	    if( DDLayout_settings && DDLayout_settings.DDL_JS && DDLayout_settings.DDL_JS.is_listing_page === true )
	    {
		data.in_listing_page = 'yes';
		data.html = 'listing';
		DDLayout.listing_manager.listing_table_view.model.trigger('make_ajax_call',  data, function( model, response, object, args ){
		    DDLayout.listing_manager.listing_table_view.current = +data.layout_id;
		    DDLayout.changeLayoutUseHelper.get_current_post_list_handler().hide_loader();
		    DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('data_sent_to_server', $container, DDLayout_settings.DDL_OPN.INDIVIDUAL_POSTS_OPTION, response.message, function(){
			self.set_radio_post_type_checked();
		    });

		});
	    }
	    else
	    {
		data.html = 'editor';
		$.post(ajaxurl, data, function(response) {
		    DDLayout.changeLayoutUseHelper.get_current_post_list_handler().hide_loader();
		    DDLayout.changeLayoutUseHelper.eventDispatcher.trigger('data_sent_to_server', $container, DDLayout_settings.DDL_OPN.INDIVIDUAL_POSTS_OPTION, response.message, function(){
			self.set_radio_post_type_checked();
		    });

		}, 'json');
	    }

    };

    self._add_assigned_post = function (post_id, post_title) {
	    var list = $('.js-individual-pages-assigned ul');
	    var found = false;
	    list.find('.js-remove-individual-page').each( function () {
		    if ($(this).data('id') == post_id) {
			    found = true;
		    }
	    })

	    return !found;
    };

    self._fill_view_all_tab = function (post_type, callback, is_scroll) {

	if (is_scroll !== true) {
	    page = 1;
	}

	var data = {
	    action: 'ddl_get_individual_post_checkboxes',
	    layout_id: self.get_current_layout(),
	    wpnonce: self._nonce,
	    post_type: post_type,
	    ddl_lang: Toolset.hooks.applyFilters('ddl-js-apply-language', null),
	    sort: false,
	    page: page,
	    count: per_page
	};

	if (page === 1) {
	    $('[id^=js-ddl-individual-view-all]').html(self._get_spinner_code());
	}


	$.post(ajaxurl, data, function (result) {

	    if (page === 1) {
		$('[id^=js-ddl-individual-view-all]').html(result);
		self.handle_refresh_all_on_scroll();
	    } else {
		$('[id^=js-ddl-individual-view-all]').find('ul').append(result);
	    }
	    if (typeof callback === 'function') {
		callback.call(self, arguments);
	    }
	    self._initialize_checkbox_handling();
	});
    };

    self._fill_most_recent_tab = function (post_type) {
	$('[id^=js-ddl-individual-most-recent]').html(self._get_spinner_code());

	var data = {
	    action: 'ddl_get_individual_post_checkboxes',
	    layout_id: self.get_current_layout(),
	    wpnonce: self._nonce,
	    post_type: post_type,
	    ddl_lang: Toolset.hooks.applyFilters('ddl-js-apply-language', null),
	    count: 12
	};

	$.post(ajaxurl, data, function (result) {
	    $('[id^=js-ddl-individual-most-recent]').html(result);
	    self._initialize_checkbox_handling();
	});
    };

    self._get_spinner_code = function () {
	return '<div class="spinner ajax-loader" style="float:none; display:inline-block"></div>';
    };

    self._initialize_quick_search = function() {
	self._search_depth = 0;
	self._search_spinner = null;

	self._searchTimer = null;

	$('.js-individual-quick-search').keyup(self._handle_search_change).attr('autocomplete','off');
    };

    self._handle_search_change = function(e) {
	var t = $(this);

	if( "Enter" == e.key ) {
	    page_search = 1;
	    self._update_quick_search_results( t, null, true );
	    return false;
	}

	if( self._searchTimer ) clearTimeout(self._searchTimer);

	self._searchTimer = setTimeout(function(){
	    page_search = 1;
	    self._update_quick_search_results( t, null, true );
	}, 400);
    };

    self._update_quick_search_results = function (text, callback, is_scroll) {


	text = text.val();

	if (is_scroll !== true) {
	    page_search = 1;
	}

	if (text) {
	    var post_type = $('[name="ddl-individual-post-type"]:checked').val();

	    self._search_depth++;

	    var data = {
		action: 'ddl_get_individual_post_checkboxes',
		layout_id: self.get_current_layout(),
		wpnonce: self._nonce,
		post_type: post_type,
		search: text,
		ddl_lang: Toolset.hooks.applyFilters('ddl-js-apply-language', null),
		page: page_search,
		count: per_page
	    };

	    $.post(ajaxurl, data, function (result) {

		if (page_search === 1) {
		    $('[id^=ddl-individual-search-results]').html(result);
		    self.handle_refresh_search_on_scroll();
		} else {
		    if (result.trim() != "No results found")
			$('[id^=ddl-individual-search-results]').find('ul').append(result);
		}


		if (typeof callback === 'function') {
		    callback.call(self, arguments);
		}

		self._initialize_checkbox_handling();

	    });
	} else {
	    $('[id^=ddl-individual-search-results]').html('');
	}

    };

    self._handle_post_type_change = function () {
	self.selected_post_type = $(this).val();
	self._fill_most_recent_tab($(this).val());
	self._fill_view_all_tab($(this).val());
	self._update_quick_search_results($('.js-individual-quick-search'));
    };

    self.handle_language_reload = function( event ){
        self._fill_most_recent_tab( self.selected_post_type );
        self._fill_view_all_tab( self.selected_post_type );
	self.populate_assigned_pages(self._current_layout);
        self._update_quick_search_results( $('.js-individual-quick-search') );
    };

    self.set_radio_post_type_checked = function(  ){
        $('#ddl-individual-post-type-page,#ddl-individual-post-type-any').map(function(){

                if( $(this).val() === self.selected_post_type ){
                    $(this).prop({"checked":true}).trigger('change');
                } else{
                    $(this).prop({"checked":false}).trigger('change');
                }
        });
    };

    self.init();
};


jQuery(function($){
    DDLayout.individual_assignment_manager = new DDLayout.IndividualAssignmentManager($);
});
