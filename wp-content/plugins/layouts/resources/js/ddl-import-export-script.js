var DDLayout = DDLayout || {};

(function($){
    $(function(){
        DDLayout.import_export = new DDLayout.ImportLayouts($);
    });
}(jQuery))

DDLayout.ImportLayouts = function($)
{
    var self = this,
        $button = $('#ddl-import'),
        $message = $('.import-layouts-messages'),
        $file = $('#upload-layouts-file'),
        $max_upload_size = $('#import_max_upload_size').val(),
        $total_files = 0,
        $current_file = 0,
        $overwritten = 0,
        $deleted = 0,
        $saved_css = 0,
        $saved_js = 0,
        $saved_json = 0,
        $saved_layouts = 0,
        $skipped_layouts = 0,
        $show_log = false,
        $is_zip = 0,
        message_style = '',
        working_with_text = '',
        working_with_text_fail = '',
        $imported_layouts = [],
        wpddl_import_spinner = '<div class="spinner ajax-loader" style="float: none; display: inline-block; margin-top: -4px">',
        $import_files_array = '';



    self.init = function()
    {
        self.check_file_is_there();
        jQuery( document ).on( 'click', '.ddl-import-export-help-icon', self.icon_click_handler );
        jQuery( document ).on( 'click', self.dismiss_pointer );
    };

    self.icon_click_handler = function ( event ) {

        event.stopPropagation();

        if ( !jQuery(this).data('has-wppointer') ) {

            jQuery(this).ddlWpPointer('show', {
                edge: 'left',
                align: 'middle'
            });

        } else {
            jQuery(this).ddlWpPointer('hide');
        }
    };

    self.dismiss_pointer = function( event ){
        event.stopPropagation();

        var $el = jQuery( '.ddl-import-export-help-icon' );

            $el.each(function(element){
                if( jQuery(this).data('has-wppointer') ){
                    jQuery(this).ddlWpPointer('hide');
                }
            });

    };

    /*
     *  Generate message after import
     */
    self.show_final_message = function( message, message_style ){

        var out = '<p>'+ ddl_import_texts.import_finished +'</p>';
        if ( message !== '' ){
            out += '<p>'+ message +'</p>';
        }
        //if ( $saved_layouts != 0 ){
        out += '<br>' + ddl_import_texts.saved_layouts + ': ' + $saved_layouts;
        //}
        if ( $deleted != 0 ){
            out += '<br>' + ddl_import_texts.deleted_layouts + ': ' + $deleted;
        }
        if ( $saved_css != 0 ){
            out += '<br>' + ddl_import_texts.saved_css + ': ' + $saved_css;
        }
        if ( $saved_js != 0 ){
            out += '<br>' + ddl_import_texts.saved_js + ': ' + $saved_js;
        }
        if ( $saved_json != 0 ){
            out += '<br>' + ddl_import_texts.saved_json + ': ' + $saved_json;
        }
        if ( $overwritten != 0 ){
            out += '<br>' + ddl_import_texts.overwritten_layouts + ': ' + $overwritten;
        }
        if ( $skipped_layouts != 0 ){
            out += '<br>' + ddl_import_texts.skipped_layouts + ': ' + $skipped_layouts;
        }

        self.showmessage(out, message_style);
        $('.import-layouts-messages').append('<button class="button button-secondary js-wpddl-continue-import">'+ ddl_import_texts.upload_another_file +'</button>');
        $overwritten = 0;
        $deleted = 0;
        $saved_css = 0;
        $saved_js = 0;
        $saved_json = 0;
        $saved_layouts = 0;
        $imported_layouts = [];
        $is_zip = 0;
        $('.js-ddl-import-message-start').remove();

    };

    /*
     * Hide 'show log' checkbox
     */
    $(document).on('change','#upload-layouts-file' , function(){ self.handle_import_file_change(); });
    self.handle_import_file_change = function(){
        if( $file.val() === '' ){
            $('#layouts-show-log-label,#layouts-show-log').hide();
        }else{
            var extension = $file[0].files[0].name.substr( ($file[0].files[0].name.lastIndexOf('.') +1) );
            if ( extension === 'zip'){
                $('#layouts-show-log-label,#layouts-show-log').show();
            }else{
                $('#layouts-show-log-label,#layouts-show-log').hide();
            }
        }
    }
    self.handle_import_file_change();

    self.check_file_is_there = function()
    {


        /*
         * Upload file.
         */
        $(document).on('click', '#ddl-import', function(event){

            if( $file.val() === '' ){
                event.preventDefault();
                self.showmessage( DDLayout_settings.DDL_JS.no_file_selected, 'warning');
            }

            if( window.FormData === undefined ){
                if( $message.data('has_message') ) $message.wpvToolsetMessage('destroy');
            }
            else{
                var extension = '';

                event.preventDefault();
                if ( $file.val() !== ''){
                    extension = $file[0].files[0].name.substr( ($file[0].files[0].name.lastIndexOf('.') +1) );
                }

                if( $file.val() === '' )
                {
                    self.showmessage( DDLayout_settings.DDL_JS.no_file_selected, 'warning');
                }
                else if ( $file[0].files[0].size > $max_upload_size ){
                    self.showmessage( DDLayout_settings.DDL_JS.file_to_big, 'warning');
                }
                else if ( extension != 'zip' && extension != 'ddl' && extension != 'css' && extension != 'js' && extension != 'json' ){
                    self.showmessage( DDLayout_settings.DDL_JS.file_type_wrong, 'warning');
                }
                else
                {

                    if( $message.data('has_message') )
                        $message.wpvToolsetMessage('destroy');

                    $show_log = $('#layouts-show-log').prop('checked');

                    $('#import-layouts').hide();
                    $('.import-layouts-messages').html('<span class="js-ddl-import-message-start">'+ ddl_import_texts.start_import + wpddl_import_spinner + '</span>');



                    if ( extension == 'zip' ){
                        $is_zip = 1;
                        $('.import-layouts-messages').append('<div class="import_messages"></div>');
                    }


                    var params = new FormData( $('#import-layouts')[0]);
                    params.append('layouts_overwrite' , $('#layouts-overwrite').prop('checked'));
                    params.append('overwrite_layouts_assignment' , $('#overwrite-layouts-assignment').prop('checked'));
                    params.append('layouts_delete' , $('#layouts-delete').prop('checked'));
                    message_style = 'success';
                    working_with_text = ddl_import_texts.working_with;
                    working_with_text_fail = ddl_import_texts.working_with_fail;

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        timeout: 120000,
                        success: function(response)
                        {
                            if ( (typeof(response) !== 'undefined') ) {

                                var temp_res = JSON.parse(response);

                                if ( temp_res.status === 'error' ){
                                    message_style = 'error';
                                    self.showmessage(temp_res.message, message_style);
                                    $('.import-layouts-messages').append('<button class="button button-secondary js-wpddl-continue-import">'+ ddl_import_texts.upload_another_file +'</button>');
                                    $('.js-ddl-import-message-start').remove();
                                }
                                else{
                                    if ( $is_zip === 0 ){

                                        if ( temp_res.status === 'error' ){

                                            message_style = 'error';
                                            self.showmessage(temp_res.message, message_style);
                                            $('.import-layouts-messages').append('<button class="button button-secondary js-wpddl-continue-import">'+ ddl_import_texts.upload_another_file +'</button>');
                                            $('.js-ddl-import-message-start').remove();
                                        }else{
                                            if (  extension == 'css' ){
                                                self.showmessage(temp_res.message, message_style);
                                                $('.import-layouts-messages').append('<button class="button button-secondary js-wpddl-continue-import">'+ ddl_import_texts.upload_another_file +'</button>');
                                            }else{
                                                self.count_imported_files( temp_res );
                                                self.show_final_message( '', 'success' );
                                            }
                                        }
                                        $('.js-ddl-import-message-start').remove();

                                    }else{
                                        $import_files_array = temp_res.file_list;
                                        if ( $show_log ){
                                            jQuery.each($import_files_array, function(index, val) {
                                                $('.import-layouts-messages').append('<p class="ddl-import-file-'+index+'">'+ val +' <span><i class="icon fa fa-spinner icon-spinner icon-spin"></i></span>' + '</p>');
                                            });
                                        }
                                        $('html,body').animate({
                                                scrollTop: $("#import-layouts").offset().top},
                                            'fast');
                                        $total_files = temp_res.total_files;
                                        self.continue_import( temp_res );
                                    }
                                }
                            }
                            else{
                                self.showmessage( ddl_import_texts.incorrect_answer, 'error');
                            }
                        },
                        error: function(x, t, m) {
                            if(t==="timeout") {
                                self.show_final_message( ddl_import_texts.server_timeout, 'error');
                            }
                        },
                        data: params,
                        cache: false,
                        contentType: false,
                        processData: false
                    });
                }

            }
        });
    };


    /*
     * Unpack zip and upload each file separately
     */
    self.continue_import = function( res ){
        var params = {
            action : 'dll_import_layouts',
            layouts_overwrite : $('#layouts-overwrite').prop('checked'),
            overwrite_layouts_assignment : $('#overwrite-layouts-assignment').prop('checked'),
            layouts_delete : $('#layouts-delete').prop('checked'),
            'layouts-import-nonce' : $('#layouts-import-nonce').val(),
            file : $import_files_array[$current_file],
            file_name : res.file_name,
            imported_layouts : $imported_layouts
        };
        if ( ($current_file+1) == $total_files ){
            params.last_file = 1;
        }


        $('.import_messages').html( working_with_text.replace('{1}',($current_file)).replace('{2}',$total_files) );
        if ( ($current_file+1) > $total_files ){
            self.show_final_message( '', 'success' );
        }
        else{
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: params,
                timeout: 120000,
                error: function(x, t, m) {
                    if(t==="timeout") {
                        $skipped_layouts++;
                        if ( $show_log === true ){
                            $('.ddl-import-file-'+$current_file).find('span').html(' <i class="fa fa-remove icon-remove"></i>');
                        }
                        $current_file ++;
                        temp_res = {
                            file : $current_file,
                            file_name : res.file_name
                        };
                        self.continue_import( temp_res );
                    }
                },
                success: function(response){
                    if ( (typeof(response) !== 'undefined') ) {

                        var temp_res = JSON.parse(response);
                        $imported_layouts = temp_res.imported_layouts;
                        self.count_imported_files( temp_res );
                        $('.ddl-import-file-'+$current_file).find('span').html(' <i class="icon-ok fa fa-check "></i>');
                        $current_file ++;
                        self.continue_import( temp_res );
                    }
                    else{
                        self.showmessage( ddl_import_texts.incorrect_answer , 'error');
                    }
                }
            });
        }
    };






    self.showmessage = function ( message, type ){
        $message.wpvToolsetMessage({
            text: message,
            type: type,
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

    /*
     * Show import form
     */
    $(document).on('click', '.js-wpddl-continue-import', function() {
        $total_files = 0;
        $current_file = 0;
        $overwritten = 0;
        $deleted = 0;
        $saved_css = 0;
        $saved_js = 0;
        $saved_json = 0;
        $saved_layouts = 0;
        $('#import-layouts')[0].reset();
        $('.import-layouts-messages').html('');
        $('#import-layouts').show();
    } );

    self.count_imported_files = function( res ){
        if ( res.saved_layouts != 0 ){
            $saved_layouts += res.saved_layouts;
        }
        if ( res.deleted != 0 ){
            $deleted+= res.deleted;
        }
        if ( res.saved_css != 0 ){
            $saved_css+= res.saved_css;
        }
        if ( res.saved_js != 0 ){
            $saved_js+= res.saved_js;
        }
        if ( res.saved_json != 0 ){
            $saved_js+= res.saved_json;
        }
        if ( res.overwritten != 0 ){
            $overwritten+= res.overwritten;
        }
    };

    self.init();
};
