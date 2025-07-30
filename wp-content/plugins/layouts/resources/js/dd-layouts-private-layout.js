var DDLayout = DDLayout || {};

/*
 * Main class for private layout
 */
DDLayout.privateLayout = function($)
{
    var self = this;
    var $js_ddl_use_layouts_as_editor = null;

    self.init = function() {

        var use_layouts_as_page_builder;

        $js_ddl_use_layouts_as_editor = $('.js-layout-private-use-again');
        use_layouts_as_page_builder = new DDLayout.UseLayoutsAsPageBuilderManager($, $js_ddl_use_layouts_as_editor);


    };

    self.init();

};

/*
 * Handle "Return to standard editing" button, update post meta status on click
 * and show standard wp text editor
 */
DDLayout.UseLayoutsAsPageBuilderManager = function($, $button){
    var self = this;
    self.trigger = $button;

    self.init = function(){
        self.trigger.on('click', self.update_status);
    };

    self.update_status = function(event){
        event.preventDefault();

        if(!self.hasOwnProperty('did_update_in_use_layout_status')){
            self.did_update_in_use_layout_status = false;
        }else{
            if(!isNaN(self.did_update_in_use_layout_status)){
                document.location.href = 'admin.php?page=dd_layouts_edit&layout_id='+self.did_update_in_use_layout_status+'&action=edit&layout_type=private';
                return;
            }
        }

        // ddl_private_layout_in_use_status_update
        var data = {
			'action': 'ddl_private_layout_in_use_status_update',
			'content_id': $(event.target).data('content_id'),
            'layout_id': $(event.target).data('layout_id'),
            'layout_type': $(event.target).data('layout_type'),
            'wpnonce' : DDL_Private_layout.private_layout_nonce,
            'status' : 'yes'
		};

		jQuery.post(ajaxurl, data, function(response) {
            var json = response;
            if(json.status){
                self.did_update_in_use_layout_status = json.layout_id;
                document.location.href = 'admin.php?page=dd_layouts_edit&layout_id='+json.layout_id+'&action=edit&layout_type=private';
            } else {
                console.log("Unable to update layout meta");
            }
		}, 'json');
    };

    self.init();

};


/*
 * Handle dialog for stop using Layouts as page builder
 */
DDLayout.StopUsingLayoutsManager = function($, $button ){
    var self = this;
    var $dummy_container = jQuery('.js-ddl-dummy-container').length ? jQuery('.js-ddl-dummy-container') : jQuery('.toolset-lock-overlay-dummy-container');
    self.trigger = $button;

    self.init = function(){

        self.trigger.on('click', self.stop_using_dialog);
    };


    self.stop_using_dialog = function(event){

        event.preventDefault();

        if( ! +DDL_Private_layout.user_can_delete_private ) return;

        $(".switch-html").trigger('click');

        var event_data ={
            'content_id': $(this).data('content_id'),
            'layout_id': $(this).data('layout_id'),
            'layout_type': $(this).data('layout_type'),
        };

        var stop_use_dialog = new DDLayout.DialogView({
            title:  DDL_Private_layout.stop_using_layout_dialog_title,
            modal:true,
            dialogClass: 'toolset-ui-dialog js-stop-using-private-layouts',
            resizable: false,
            draggable: false,
            position: {my: "center", at: "center", of: window},
            width: 250,
            selector: '#js-ddl-private_layouts_switcher',
            buttons: [
                {
                    text: DDL_Private_layout.stop_using_layout_dialog_close,
                    class: "pull-left",
                    click: function(){
                        jQuery(this).ddldialog("close");
                    }
                },
                {
                    text: DDL_Private_layout.stop_using_layout_dialog_edit,
                    class: "button-primary js-pl-edit-button pull-right",
                    disabled: "disabled",
                    click: function () {
                        var selected_content_for_editing = jQuery("input[name=ddl-pl-what_to_edit]:checked").val();
                        $dummy_container.hide('slow');
                        jQuery('#js_show_template_layout_selector').show();
                        jQuery('#js-content-layout-in-use-message').hide();
                        DDLayout_settings.DDL_JS.post.private_layout_in_use = false;
                        self.update_status_and_editor(selected_content_for_editing, event_data);
                        jQuery(this).ddldialog("close");
                    }
                },
            ]
        });
        jQuery('.js-stop-using-private-layouts .ui-dialog-buttonset').css('float','none');
        stop_use_dialog.$el.on('ddldialogclose', function (event) {
            stop_use_dialog.remove();
        });

        stop_use_dialog.dialog_open();

        jQuery( 'input[type=radio][name=ddl-pl-what_to_edit]' ).change(function () {
            jQuery( '.js-pl-edit-button' )
                .attr( 'disabled', false )
                .removeClass( 'ui-button-disabled ui-state-disabled' );
        });
        jQuery( '.js-pl-tooltip' ).tooltip({
            position: {
                my: "top",
            },
        });

    };

    self.isGutenberg = function(){
        return ( jQuery('div.editor-block-list__block').length !== 0 || jQuery('div.block-editor-block-list__block').length !== 0 );
    };


    self.update_status_and_editor = function(what_to_edit, event_data){



        var data = {
			'action': 'ddl_private_layout_in_use_status_update',
			'content_id': event_data.content_id,
            'what_to_edit': what_to_edit,
            'layout_id': event_data.layout_id,
            'layout_type': event_data.layout_type,
            'wpnonce' : DDL_Private_layout.private_layout_nonce,
            'status' : false
		};

		jQuery.post(ajaxurl, data, function(response) {
            var json = response;
            if( json.status !== false && json.original_content ){
                if( self.isGutenberg() ){
                    self.updateGutenbergEditor( json.original_content, true );
                } else {
                    self.update_editor(json.original_content);
                }
            }
            $('.js-layout-private-use-again').show();
            // Fire an action when private layout has been disconnected from the page
			Toolset.hooks.doAction( 'ddl_private_layout_usage_stopped' );
		}, 'json');

    };

    self.update_editor = function(content){

        _.defer(function(){
            if( typeof tinymce != "undefined" ) {
                var editor = tinymce.get( 'content' );
                if( editor && editor instanceof tinymce.Editor ) {
                    editor.setContent( content, {format : 'html'} );
                    $('.wp-editor-area').val(content);
                } else {
                    $('.wp-editor-area').val(content);
                }
            }
        });
    };

    self.updateGutenbergEditor = function( content, forceReload ){
    	if( forceReload ) {
			window.location.reload(true);
		} else {
			_.defer( self.gutenbergTinyMCESetContent, content );
		}
    };

    self.gutenbergTinyMCESetContent = function( content ) {
		if( typeof tinymce !== "undefined" ) {
			var editor = tinymce.activeEditor;
			if( editor && editor instanceof tinymce.Editor ) {
				editor.setContent( content, {format : 'html'} );
				$('.editor-default-block-appender__content').val(content);
			} else {
				$('.editor-default-block-appender__content').val(content);
			}
		}
	};

    self.init();

};

jQuery(function($) {
    _.defer(function(){
        DDLayout.private_layout = new DDLayout.privateLayout($);
    });
});

jQuery(function($) {
    jQuery( ".js_bootstrap_not_loaded button[class=notice-dismiss]" ).click(function() {
        var data = {
            'action': 'ddl_dismiss_bootstrap_message',
            'wpnonce' : DDL_Private_layout.private_layout_nonce,
        };

        jQuery.post(ajaxurl, data, function(response) {

        });
    });
});

