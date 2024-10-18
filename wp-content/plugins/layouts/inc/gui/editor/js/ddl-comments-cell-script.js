var DDLayout = DDLayout || {};

DDLayout.CommentsCell = function($)
{
    var self = this;

    self.init = function()
    {
        $(document).on('comments-cell.dialog-open', self.dialog_open);
        WPV_Toolset.Utils.eventDispatcher.listenTo(WPV_Toolset.Utils.eventDispatcher, 'color_box_closed', self.clean_up )
    };

    self.dialog_open = function(event)
    {
		jQuery('.js-dialog-edit-save').prop('disabled', true);
        jQuery(document).on('change', '#ddl-layout-title_one_comment,#ddl-layout-title_multi_comments,#ddl-layout-password_text,#ddl-layout-reply_text,#ddl-layout-comments_closed_text',self.check_comments_titles);
        self.check_comments_titles();
    };
	
    self.check_comments_titles = function(e){
        var disable_submit = false;
        
        if ( jQuery('#ddl-layout-title_one_comment').val() === '' ){
            disable_submit = true;
            $('#title_one_comment_message').wpvToolsetMessage({
                text:  DDLayout_settings.DDL_JS.strings.title_one_comment_text,
                tag:'p',
                stay: true,
                close: false,
                type: 'notice'
            });           
        }
        else{
            $('#title_one_comment_message').wpvToolsetMessage('destroy');
        }
        
        if ( jQuery('#ddl-layout-title_multi_comments').val() === '' ){        
            disable_submit = true;
            $('#title_multi_comments_message').wpvToolsetMessage({
                text:  DDLayout_settings.DDL_JS.strings.title_multi_comments_text,
                tag:'p',
                stay: true,
                close: false,
                type: 'notice'
            });
        }else{
            $('#title_multi_comments_message').wpvToolsetMessage('destroy');
        }
        
        if ( jQuery('#ddl-layout-comments_closed_text').val() === '' ){        
            disable_submit = true;
            $('#comments_closed_text_message').wpvToolsetMessage({
                text:  DDLayout_settings.DDL_JS.strings.this_field_is_required,
                tag:'p',
                stay: true,
                close: false,
                type: 'notice'
            });
        }else{
            $('#comments_closed_text_message').wpvToolsetMessage('destroy');
        }
        
        if ( jQuery('#ddl-layout-reply_text').val() === '' ){        
            disable_submit = true;
            $('#reply_text_message').wpvToolsetMessage({
                text:  DDLayout_settings.DDL_JS.strings.this_field_is_required,
                tag:'p',
                stay: true,
                close: false,
                type: 'notice'
            });
        }else{
            $('#reply_text_message').wpvToolsetMessage('destroy');
        }
        
        if ( jQuery('#ddl-layout-password_text').val() === '' ){        
            disable_submit = true;
            $('#password_text_message').wpvToolsetMessage({
                text:  DDLayout_settings.DDL_JS.strings.this_field_is_required,
                tag:'p',
                stay: true,
                close: false,
                type: 'notice'
            });
        }else{
            $('#password_text_message').wpvToolsetMessage('destroy');
        }
        
        
        if ( !disable_submit ){
            jQuery('.js-dialog-edit-save').prop('disabled', false);
        }else{
            jQuery('.js-dialog-edit-save').prop('disabled', true);
        }
    }
    
	self.hideCallback = function(event){
		if ( jQuery('.js-comments-list-style').val() == 'callback' ){
			jQuery('.js-comments-list-callback').show();
			jQuery('.js-comments-list-callback').focus();
		}else{
			jQuery('.js-comments-list-callback').hide();	
		}
		
		//self.positionStylingMessage();
	}

	self.positionStylingMessage = function () {
		var $message = jQuery('.js-comments-style-message');
		$message.css({'margin-top' : '0px'});
		var message_top = $message.offset().top;
		var top = jQuery('#ddl-default-edit [name="ddl-layout-avatar_size"]').offset().top;
		
		$message.css({
						width : '240px',
						'margin-top' : top - message_top
					});
	}
	
    
    self.init();
};


(function($){
     $(function(){
        DDLayout.comments_cell =  new DDLayout.CommentsCell($);
     });
}(jQuery));