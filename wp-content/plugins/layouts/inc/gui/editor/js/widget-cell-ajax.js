// widget-cell.js

var DDLayout = DDLayout || {};

jQuery(function($){

	DDLayout.WidgetCell = function($)
	{
		var self = this;
		self.widget_controls_cache = {};

		self.init = function() {
			jQuery('select[name="ddl-layout-widget_type"]').on('change', self._widget_select_change);

			$(document).on('widget-cell.get-content-from-dialog', self._get_content_from_dialog);
			$(document).on('widget-cell.init-dialog-from-content', self._init_dialog_from_content);
		};

		self._widget_select_change = function (event, callback) {

			var $widget_select = jQuery('select[name="ddl-layout-widget_type"]');
			var $widget_fieldset = jQuery('.js-widget-cell-fieldset');
			var $widget_id = $widget_select.val();

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
							jQuery('.js-widget-cell-controls').html(response);
							self.widget_controls_cache[$widget_id] = response;
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

			jQuery('select[name="ddl-layout-widget_type"]').trigger('change', function () {
				var widget = content['widget'];
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

			});
		}

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

