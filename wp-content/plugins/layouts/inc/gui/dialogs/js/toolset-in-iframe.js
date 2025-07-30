var DDLayout = DDLayout || {};

DDLayout.ToolsetInIfame = function ($, child)
{
    var self = child;

    var _dialog = null, iFrameDocument = null;

    self.open_in_iframe = function (cell_type, new_cell) {

        self._new_cell = new_cell;

        self.dialog_pos = $('#ddl-default-edit').parent().offset();
        self.dialog_width = $('#ddl-default-edit').width();

        $('#ddl-default-edit .ddl-dialog-header .js-edit-dialog-close ').hide();
        $('#ddl-default-edit .ddl-dialog-content').hide();
        $('#ddl-default-edit .ddl-dialog-footer button').hide();
        $('<i class="fa fa-remove icon-remove js-close-toolset-iframe-no-save"></i>')
            .appendTo('#ddl-default-edit .ddl-dialog-header');
        $('<button class="button button-primary js-close-toolset-iframe">' + self.get_text('close') + '</button>')
            .appendTo('#ddl-default-edit .ddl-dialog-footer').css('float', 'right');
        $('.js-close-toolset-iframe').prop('disabled', true);
		$('.js-close-toolset-iframe-no-save').prop('disabled', true);

		self.add_loading_overlay();

        $('<iframe title="Toolset cell" name="ddl-layout-toolset-iframe" id="ddl-layout-toolset-iframe" class="layouts-views-loading" style="display:none" width="100%" height="1200px" src="' + self.get_url(cell_type, new_cell) + '"></iframe>')
            .insertAfter('#ddl-default-edit .ddl-dialog-content');

        jQuery('#ddl-layout-toolset-iframe', top.document).on('load', function(event){

            if(document.getElementById("ddl-layout-toolset-iframe").contentWindow.document.body.innerHTML.trim() === '<p>Sorry, you are not allowed to access this page.</p>'){
                self.remove_loading_overlay();
                self.add_permission_message();
            } else {
                iFrameDocument = DDLayout.ToolsetInIfame.getIframeWindow(event.target).document;
                $(event.target).trigger('ddl-layout-toolset-iframe-loaded', DDLayout.ToolsetInIfame.getIframeWindow(event.target).document)
            }
        });


        $('#ddl-default-edit').parent().css({'left' : self.dialog_pos.left - (984 - self.dialog_width) / 2 + 'px'});
        $('#ddl-default-edit').css({'width' : '984px'});

        $('#ddl-default-edit .js-close-toolset-iframe').on('click', self._close_iframe);

        // Stop the enter key from closing the dialog
        $(document).off('keyup.colorbox');

    };

	self.add_loading_overlay = function () {

        var content_height = $('#ddl-default-edit .ddl-dialog-content').height();
		$('<div style="display:block; height:' + content_height + 'px;" class="js-layouts-views-loading"><div class="spinner ajax-loader-bar" style="display:block; height:100%"></div></div>').insertAfter($('#ddl-default-edit .ddl-dialog-content')).show();
	};
    self.add_permission_message = function () {
        self.the_frame_ready();
        $('.js-close-toolset-iframe').attr('disabled', true);
    };

	self.remove_loading_overlay = function () {
		$('.js-layouts-views-loading').remove();

	};

    self._close_iframe = function (e) {
        self._spinner = jQuery('<div class="spinner ajax-loader"></div>')
                                .insertAfter('#ddl-default-edit .js-close-toolset-iframe')
                                .show().css({
                                        position:'relative',
                                        top:'3px'
                                });
		self.close_iframe(self.save_toolset_complete);
	};

	self.close_iframe = function (callback) {
		return true;
	};

	self.save_toolset_complete = function () {

        self._spinner.remove();

        self._restore_dialog();
    };

    self._restore_dialog = function () {
        self.dialog_clean_up();
		self.iframe_has_closed();
    };


    self.get_iframe_doc = function(){
        return iFrameDocument;
    };

    self.dialog_clean_up = function(){
        var iFrameTagVal = jQuery('select[name="ddl_tag_name"]', jQuery(iFrameDocument) ).val();


        if( typeof iFrameTagVal !== 'undefined'){
            jQuery( 'select[name="ddl_tag_name"]', jQuery('#ddl-default-edit') ).val( iFrameTagVal ).trigger('change');
        }


        $('#ddl-layout-toolset-iframe').remove();
        $('#ddl-default-edit .js-close-toolset-iframe').off('click');
        $('#ddl-default-edit .js-close-toolset-iframe').remove();
        $('#ddl-default-edit .js-close-toolset-iframe-no-save').off('click');
        $('#ddl-default-edit .js-close-toolset-iframe-no-save').remove();

        $('#ddl-default-edit .ddl-dialog-header .js-edit-dialog-close ').show();
        $('#ddl-default-edit .ddl-dialog-content').show();
        $('#ddl-default-edit .ddl-dialog-footer button').show();

        $('#ddl-default-edit').parent().css({'left' : self.dialog_pos.left + 'px'});
        $('#ddl-default-edit').css({'width' : self.dialog_width + 'px'});
    };

	self.iframe_has_closed = function () {
		return true;
	};

    self._close_iframe_without_saving = function () {
        self._restore_dialog();
    };

    self.the_frame_ready = function () {

        $('#ddl-layout-toolset-iframe').show(400, function(event){

        });

        $('.js-layouts-views-loading').hide().remove();

        $('#ddl-default-edit .js-close-toolset-iframe').prop('disabled', false);
		$('#ddl-default-edit .js-close-toolset-iframe-no-save').prop('disabled', false);

        $('#ddl-default-edit .js-close-toolset-iframe-no-save').on('click', function(event){
            jQuery.colorbox.close();
            self.dialog_clean_up();
        });
    };

    self.is_new_cell = function () {
        return self._new_cell;
    };

    self.set_dialog = function( dialog ){
        _dialog = dialog;
    };

    self.get_dialog = function(){
        return _dialog;
    };

	self.fetch_extra_controls = function (div_id) {
		var data = {};

		data.tag = jQuery('#ddl-default-edit').find('select[name="ddl_tag_name"]').val();
		data.id = jQuery('input[name="ddl-default-edit-css-id"]').val();
		data.css = jQuery('select[name="ddl-default-edit-class-name"]').val();
		data.name = jQuery('#ddl-default-edit #ddl-default-edit-cell-name').val();

		var controls = '';
		controls += '<p>' + jQuery('#ddl-default-edit-cell-name').parent().html() + '</p><br />';
		controls += WPV_Toolset.Utils._template( jQuery('#ddl-styles-extra-controls').html(), data );
		controls = '<h3><i class="icon-layouts-logo ont-color-orange ont-icon-24"></i> ' + DDLayout_settings.DDL_JS.strings.cred_layout_css_text + '</h3><hr />' + controls;
        data.controls = '<div id="' + div_id + '" class="ddl-cred-css ddl-setting-for-layouts-container-in-iframe not-hidden"><div class="ddl-form">' + controls + '</div></div>';
		return data;
	};

};


/**
*
* @param: htmlNode iFrame
* @return: htmlDomDocument object
**/
DDLayout.ToolsetInIfame.getIframeWindow = function(iframe_object) {
  var doc;

  if (iframe_object.contentWindow) {
    return iframe_object.contentWindow;
  }

  if (iframe_object.window) {
    return iframe_object.window;
  }

  if (!doc && iframe_object.contentDocument) {
    doc = iframe_object.contentDocument;
  }

  if (!doc && iframe_object.document) {
    doc = iframe_object.document;
  }

  if (doc && doc.defaultView) {
   return doc.defaultView;
  }

  if (doc && doc.parentWindow) {
    return doc.parentWindow;
  }

  return undefined;
}

