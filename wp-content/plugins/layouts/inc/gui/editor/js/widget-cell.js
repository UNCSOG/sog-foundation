// widget-cell.js

var DDLayout = DDLayout || {};

jQuery(function($){

	DDLayout.WidgetCell = function($)
	{
		var self = this;
		self.widget_controls_cache = {};
		self.media_types = ['media_audio', 'media_video', 'media_image'];

		self.init = function() {
            jQuery(document).on('widget-cell.dialog-open', self._dialog_open);
			jQuery('select[name="ddl-layout-widget_type"]').on('change', self._widget_select_change);
			$(document).on('widget-cell.get-content-from-dialog', self._get_content_from_dialog);
			$(document).on('widget-cell.init-dialog-from-content', self._init_dialog_from_content);
		};

        self._dialog_open = function( event ){
        	var value = jQuery('select[name="ddl-layout-widget_type"]').val();
        	if( value ){
                jQuery('.js-widget-cell-fieldset').show();
			} else {
                jQuery('.js-widget-cell-fieldset').hide();
			}
		};

		self._widget_select_change = function ( event, args ) {

		    var callback = args && args.hasOwnProperty('callback') ? args.callback : null;
            var widget_model = args && args.hasOwnProperty('widget_model') ? args.widget_model : {};
			var $widget_select = jQuery('select[name="ddl-layout-widget_type"]');
			var $widget_fieldset = jQuery('.js-widget-cell-fieldset');
			var $widget_id = $widget_select.val();
			if( args && args.hasOwnProperty('widget_type') ){
                var widget_type = args.widget_type;
            } else {
                var widget_type = $widget_id.split('widget_');
                widget_type = widget_type[1];
            }


			if ( typeof self.widget_controls_cache[$widget_id] == "undefined" ) {

				var data = {
						widget : $widget_id,
						action : 'get_widget_controls',
						nonce : $widget_select.data('nonce')
				};

				var spinnerContainer = jQuery('<div class="spinner ajax-loader">').insertAfter($widget_select).show();

				jQuery.ajax({
						type:'post',
						url:ajaxurl,
						data:data,
						success:function(response){
							spinnerContainer.remove();
                            if( _.indexOf( self.media_types, widget_type ) !== -1 ){
                                    var $media_el = jQuery('<div class="ddl-media-element-wrap"></div>');
                                    jQuery('.js-widget-cell-controls').html(response);
                                    jQuery('.js-widget-cell-controls').prepend($media_el);
                                    self.build_media_controls( widget_type, widget_model, $media_el, jQuery('.js-widget-cell-controls') );
                                    self.widget_controls_cache[$widget_id] = jQuery('.js-widget-cell-controls').html();

							} else {
                                response = self.removeNoticesFromTextWidgetForm( widget_type, response );
                                jQuery('.js-widget-cell-controls').html(response);
                                self.widget_controls_cache[$widget_id] = response;
							}

                            if( $widget_id !== '0' && response != '') {
                                $widget_fieldset.show();
                            } else {
                                $widget_fieldset.hide();
                            }

                            if (callback) {
                                callback();
                            }

							$widget_select.trigger( 'js_event_ddl_widget_cell_widget_type_changed', [ $widget_id ] );
						}
					});

			} else {

                jQuery('.js-widget-cell-controls').html(self.widget_controls_cache[$widget_id]);

                if( _.indexOf( self.media_types, widget_type ) !== -1 ){
                    var $media_el = jQuery('.ddl-media-element-wrap');
                    self.build_media_controls( widget_type, widget_model, $media_el, jQuery('.js-widget-cell-controls') );
                }

				if( $widget_id !== '0' && self.widget_controls_cache[$widget_id] != '') {
					$widget_fieldset.show();
				} else {
					$widget_fieldset.hide();
				}

				if (callback) {
					callback();
				}

				$widget_select.trigger( 'js_event_ddl_widget_cell_widget_type_changed', [ $widget_id ] );

			}
		};


		self.build_media_controls = function( widget_type, widget_model, $media_element, $media_wrapper ){
            var model = new wp.mediaWidgets.modelConstructors[widget_type]( widget_model );
            var media = new wp.mediaWidgets.controlConstructors[widget_type]({model:model, el: $media_element , syncContainer : $media_wrapper });
            media.render();
            return media;
        };

		self.removeNoticesFromTextWidgetForm = function(widget_type, output){

			if(widget_type === 'widget_text'){
                output = '<div>'+output+'</div>';
                var response_object = $(output);
                response_object.find('.notice').remove();
                output = response_object.html();
                return output
            } else {
            	return output;
			}
		};

		self._get_content_from_dialog = function (event, content) {
			var field_prefix = self._get_field_prefix();
			var length = field_prefix.length;

			var widget = {};

			jQuery('#ddl-default-edit [name^="' + field_prefix + '"]').each( function (){
				var data = jQuery(this).attr('name');
				data = data.substr(length);
				data = data.substr(1, data.length - 2); // remove bracets [xxx]

				switch (jQuery(this).attr('type')) {
					case 'checkbox':
						widget[data] = jQuery(this).is(':checked');
						break;

					case 'radio':
						if (jQuery(this).is(':checked')) {
							widget[data] = jQuery('#ddl-default-edit [name="' + field_prefix + '\\[' + data + '\\]"]:checked').val();
						}
						break;

					default:
						widget[data] = jQuery(this).val();
						break;
				}

			});

			content['widget'] = widget;
		};

		self._get_field_prefix = function () {
			var name_ref = jQuery('#ddl-widget-name-ref').val();
			if (name_ref) {
				return name_ref.replace('[ddl-layouts]', '');
			} else {
				return '';
			}

		}

		self._init_dialog_from_content = function (event, content, dialog) {

		    if( !content || !content.hasOwnProperty('widget') || !content.hasOwnProperty('widget_type') ){
		        return;
            }

            var widget = content['widget'];
                widget_type = content['widget_type'].split('widget_');
                widget_type = widget_type[1];

            if( _.indexOf( self.media_types, widget_type ) === -1 ) {
                jQuery('select[name="ddl-layout-widget_type"]').trigger('change', { callback : function () {

                    if (typeof widget != 'undefined') {

                        var field_prefix = self._get_field_prefix();
                        var length = field_prefix.length;

                        jQuery('#ddl-default-edit [name^="' + field_prefix + '"]').each( function (){
                            var data = jQuery(this).attr('name');
                            data = data.substr(length);
                            data = data.substr(1, data.length - 2); // remove bracets [xxx]

                            dialog.set_element_value(this, widget[data]);
                        });
                    }
                } } );
            } else {
                jQuery('select[name="ddl-layout-widget_type"]').trigger('change', { widget_model:widget, widget_type : widget_type });
            }
		};

		self.get_widget_name = function (widget_slug) {
			var widget_name = widget_slug;

			jQuery('select[name="ddl-layout-widget_type"] option').each( function () {
				if (jQuery(this).val() == widget_slug) {
					widget_name = jQuery(this).html();
				}
			})
			return widget_name;
		}

		self.init();
	};


    DDLayout.widget_cell = new DDLayout.WidgetCell($);

});

