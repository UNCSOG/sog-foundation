var DDLayout = DDLayout || {};

(function($){
	$(function(){
		DDLayout.post_types = new DDLayout.PostTypes($);
	});
}(jQuery))

DDLayout.PostTypes = function($){
		var self = this
			, warning = '.js-apply-layout-for-all-posts'
			, warning_js = '.js-apply-layout-for-all-posts-js'
			,batch_data = null;

		self.current_warn = null;

		self.init = function()
		{
			self.openAssignToPostTypesDialogJS();
			self.openAssignToPostTypesDialog();
			self.batchPostsOfPostType();
            self.handle_show_all_posts();
            DDLayout.PostTypes.LayoutHelper.ajaxSendNormalizeParams();
		};

	self.batchPostsOfPostType = function()
	{
		var button_batch = $('.js-ddl-update-posts-process');
		$(document).on('click', '.js-ddl-update-posts-process', function(event){

			var params = {
				'set-layout-for-cpt-nonce':batch_data.nonce,
				action:'set_layout_for_post_type_meta',
				post_type: batch_data.post_type,
				layout_id:batch_data.layout_id,
				post_list:batch_data.post_list,
				label:batch_data.label
			}, data;

			if( typeof jQuery(this).data('in-listing-page') != 'undefined' && jQuery(this).data('in-listing-page') == 'yes' )
			{
				params.in_listing_page = 'yes';
			}

			var spinnerContainer = jQuery('<div class="spinner ajax-loader">');

			jQuery(this).parent().insertAtIndex( 0, spinnerContainer.css({float:'none', display:'inline-block'}) );

			if( params.in_listing_page && params.in_listing_page == 'yes' )
			{
				DDLayout.listing_manager.listing_table_view.model.trigger('make_ajax_call',  params, function( model, response, object, args ){
					spinnerContainer.hide();
					data = response.message;

					self.current_warn.remove();
					self.current_warn = null;
                    self.openConfirmMessage( data, params.in_listing_page, params.layout_id );
				});

                WPV_Toolset.Utils.eventDispatcher.listenTo( WPV_Toolset.Utils.eventDispatcher, 'confirm-batch-dialog-closed',
					function()
					{
						if( data && data.label && _.isArray( data.results ) ){
                            DDLayout.listing_manager.listing_table_view.current = +params.layout_id;
                            DDLayout.listing_manager.listing_table_view.eventDispatcher.trigger('changes_in_dialog_done');
                            WPV_Toolset.Utils.eventDispatcher.stopListening( WPV_Toolset.Utils.eventDispatcher, 'confirm-batch-dialog-closed' );
						}
					}
				);
			}
			else
			{
				WPV_Toolset.Utils.do_ajax_post(params, {success:function(response){
					spinnerContainer.hide().remove();
					data = response.message;
					if( self.current_warn )
					self.current_warn.remove();
					self.current_warn = null;
					self.openConfirmMessage(data);

                    if( response && response.hasOwnProperty('where_used_html') )
                    {
                        var where_used_ui = jQuery('.js-where-used-ui');
                        where_used_ui.empty().html( response.where_used_html );
                    }
				}});
			}

		});
	};


    self.handle_show_all_posts = function () {
        var $link = jQuery('.js-show-all');

            jQuery(document).on('click', '.js-show-all', function (event) {
                event.preventDefault();
                WPV_Toolset.Utils.loader.loadShow( jQuery(this).css('float','left'),'yes').css('float','left');

                var layout_id = DDLayout_settings.DDL_JS.layout_id
                    , nonce = DDLayout_settings.DDL_JS.ddl_show_all_posts_nonce
                    , amount = jQuery(this).data('amount')
                    , params = {
                        ddl_show_all_posts_nonce: nonce,
                        layout_id: layout_id,
                        action: 'show_all_posts',
                        amount:'all',
                        per_page_amount: jQuery(this).data('amount')
                    };
                if( DDLayout.PostTypes.ajax_done )
                {
                    self.hanlde_show_all_post_no_ajax( jQuery(this), jQuery('.js-dd-layouts-where-used') );
                } else {

                    WPV_Toolset.Utils.do_ajax_post(params, {
                        success: function (response) {
                            WPV_Toolset.Utils.loader.loadHide()/*.css('float', 'right')*/;
                            if( response && response.Data && response.Data.where_used_html )
                            {
                                jQuery('.js-dd-layouts-where-used').empty().html( response.Data.where_used_html );
                                jQuery('.js-dd-layouts-where-used').show();
                                DDLayout.PostTypes.ajax_done = true;
                            }
                        }});
                    jQuery(this).data('amount_button', 'not_all');
                    jQuery(this).text( jQuery(this).data('textnotall') );
                }
            });

    };

    self.hanlde_show_all_post_no_ajax = function($button, $wrap)
    {

        var $ul = $wrap,
            what = $button.data('amount_button'),
            amount = DDLayout_settings.DDL_JS.AMOUNT_OF_POSTS_TO_SHOW;

        $ul.each(function(index, value){

            jQuery(value).find('li').each(function(i, v )
            {
                if( what === 'all' )
                {
                    if( i >= amount ){
                        jQuery( v ).show();
                    }
                }
                else if( what === 'not_all' )
                {
                    if( i >= amount ){
                        jQuery( v ).hide();
                    }
                }
            });
        });

        if( what === 'all'){
            $button.data('amount_button', 'not_all');
            $button.text( $button.data('textnotall') );
        }else{
            $button.data('amount_button', 'all');
            $button.text( $button.data('textall') );
        }
        WPV_Toolset.Utils.loader.loadHide();
    };


	self.openAssignToPostTypesDialogJS = function()
	{
		$(document).on('click', warning_js, function(event){
			batch_data = $(this).data('object');

			var template = $("#ddl-dialog-assign-layout-to-post-type").html();

			$("#ddl-dialog-assign-layout-to-post-type-wrap").html( WPV_Toolset.Utils._template( template, batch_data ) );

			self.current_warn = $(this);

			jQuery.colorbox({
				href: '#ddl-dialog-assign-layout-to-post-type-wrap',
				inline: true,
				open: true,
				closeButton:false,
				fixed: true,
				top: false,
                width:'400px',
				onComplete: function() {

				},
				onCleanup: function() {

				}
			});
		});
	};

	self.openAssignToPostTypesDialog = function()
	{
		$(document).on('click', warning, function(event){
			batch_data = $(this).data('object');
			var post_type = batch_data.post_type;

			self.current_warn = $(this);

			jQuery.colorbox({
				href: '#ddl-dialog-assign-layout-to-post-type-'+post_type,
				inline: true,
				open: true,
				closeButton:false,
				fixed: true,
				top: false,

				onComplete: function() {

				},
				onCleanup: function() {

				}
			});
		});
	};

	self.openConfirmMessage = function( data, in_listing_page, layout_id )
	{
		var template = $("#ddl-layout-to-meta-confirm-box").html();

		$("#ddl-layout-to-meta-confirm-box-wrap").html( WPV_Toolset.Utils._template( template, data ) );

		jQuery.colorbox({
			href: '#ddl-layout-to-meta-confirm-box-wrap',
			inline: true,
			open: true,
			closeButton:false,
			fixed: true,
			top: false,
			onComplete: function() {

			},
			onCleanup: function() {

			},
            onClosed:function(){

                WPV_Toolset.Utils.eventDispatcher.trigger('confirm-batch-dialog-closed');

            }
		});
	};

	self.init();
};

DDLayout.PostTypes.ajax_done = false;

DDLayout.PostTypes.handle_posts_where_reload = function(response)
{
    if( response && response.hasOwnProperty('where_used_html') )
    {

        jQuery('#js-print_where_used_links').empty().html( response.where_used_html );

        jQuery('.js-dd-layouts-where-used').show();

        if ( jQuery('.js-show-all').is('a') )
        {
            var $button = jQuery('.js-show-all'), what = $button.data('amount-button'), text = '' ;

            if( what === 'all'){
                text = $button.data('textall');
            }else{

                text = $button.text( $button.data('textnotall') );
            }
            $button.text( text );
        }
        DDLayout.PostTypes.ajax_done = false;
    }
};

DDLayout.PostTypes.LayoutHelper = {
	ajaxSendNormalizeParams : function(){
		jQuery(document).on("ajaxSend", function(event, xhr, request){
			var data = '?'+request.data,
					whitelist = [
                        'js_change_layout_usage_for_'+DDLayout_settings.DDL_OPN.POST_TYPES_OPTION,
                        'js_change_layout_usage_for_'+DDLayout_settings.DDL_OPN.ARCHIVES_OPTION,
                        'js_change_layout_usage_for_'+DDLayout_settings.DDL_OPN.OTHERS_OPTION,
                        'ddl_fetch_post_for_layout',
						'ddl_remove_layout_from_post',
						'ddl_assign_layout_to_posts',
						'ddl_get_individual_post_checkboxes'],
					new_data = {},
                    action = data.getParameterByName('action'),
					post_type = data.getParameterByName('post_type'),
					sort = data.getParameterByName('sort'),
					search = data.getParameterByName('search');

            if( whitelist.indexOf(action) === -1 ) return;


			if( _.isNull( post_type ) || 'any' == post_type){

				post_type = jQuery('#ddl-individual-post-type-page').is(':checked') ? 'page' : 'any';
				new_data.post_type = post_type;
			}

			if( _.isNull( sort ) ){
				sort = false;
				new_data.sort = sort;
			}

			if( _.isNull( search ) ){
				search = _.isNull( search ) ? jQuery('#js-individual-quick-search').val() : search;
				if( search ){
					new_data.search = search;
				}
			}

			request.data = request.data + '&' + jQuery.param(new_data);
		});
	}
};
