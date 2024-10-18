// ddl-types-views-popup.js

DDLayout.typesViewsPopup = function($)
{
	var self = this;

	self.init = function () {

	};

	// Public functions.

	self.start = function () {
		// Add special handling for Types popup.
		self._wpcfFieldsEditorCallback_function = window.wpcfFieldsEditorCallback;
		window.wpcfFieldsEditorCallback = self._wpcfFieldsEditorCallback;

        // We need special handling to install View forms as this uses colorbox.
		self._views_insert_form_function = window.wpv_insert_view_form_popup;
		window.wpv_insert_view_form_popup = self._wpv_insert_form_shortcode;

		// We need special handling for Views translatable strings as this uses colorbox.
		self._wpv_insert_translatable_string_popup = window.wpv_insert_translatable_string_popup;
		window.wpv_insert_translatable_string_popup = self._wpv_insert_translatable_string;

		// We need special handling for Views search terms as this uses colorbox.
		self._wpv_insert_search_term_function = window.wpv_insert_search_term_popup;
		window.wpv_insert_search_term_popup = self._wpv_insert_search_term_popup

        // We need special handling for Woocommerce Views as this uses colorbox.
        self._wcviews_insert_wpv_woo_product_image_function = window.wcviews_insert_wpv_woo_product_image;
		window.wcviews_insert_wpv_woo_product_image = self._wcviews_insert_wpv_woo_product_image;

        self._wcviews_insert_wpv_woo_buy_or_select_function = window.wcviews_insert_wpv_woo_buy_or_select;
		window.wcviews_insert_wpv_woo_buy_or_select = self._wcviews_insert_wpv_woo_buy_or_select;

        self._wcviews_insert_wpv_woo_buy_options_function = window.wcviews_insert_wpv_woo_buy_options;
		window.wcviews_insert_wpv_woo_buy_options = self._wcviews_insert_wpv_woo_buy_options;

        self._wcviews_insert_wpv_woo_productcategory_images_function = window.wcviews_insert_wpv_woo_productcategory_images;
		window.wcviews_insert_wpv_woo_productcategory_images = self._wcviews_insert_wpv_woo_productcategory_images;

	}

	self.end = function () {

		// restore original functions.

		window.wpv_insert_translatable_string_popup = self._wpv_insert_translatable_string_popup;
		window.wpv_insert_search_term_popup = self._wpv_insert_search_term_function;
		window.wpv_insert_view_form_popup = self._views_insert_form_function;
		window.wpcfFieldsEditorCallback = self._wpcfFieldsEditorCallback_function;
        window.wcviews_insert_wpv_woo_product_image = self._wcviews_insert_wpv_woo_product_image_function;
        window.wcviews_insert_wpv_woo_buy_or_select = self._wcviews_insert_wpv_woo_buy_or_select_function;
        window.wcviews_insert_wpv_woo_buy_options = self._wcviews_insert_wpv_woo_buy_options_function;
        window.wcviews_insert_wpv_woo_productcategory_images = self._wcviews_insert_wpv_woo_productcategory_images_function;
	}

	self.set_position_and_target = function (position_element, target_id) {
		self._positioning_element = position_element;
		self._target_id = target_id;
	}
	// Private functions

	self._wpv_insert_form_shortcode = function (id) {
		self._override_jquery_colorbox_functions();

		self._views_insert_form_function(id);
	}

	self._wpv_insert_translatable_string = function () {
		self._override_jquery_colorbox_functions();

		self._wpv_insert_translatable_string_popup();
	}

	self._wpv_insert_search_term_popup = function () {
		self._override_jquery_colorbox_functions();

		self._wpv_insert_search_term_function();
	}


	self._wpcfFieldsEditorCallback = function (fieldID , metaType, postID) {
		self._override_jquery_colorbox_functions();

		self._wpcfFieldsEditorCallback_function (fieldID , metaType, postID);
	}

	self._override_jquery_colorbox_functions = function () {

		// We're overriding colorbox so that it calls member functions
		// here instead. We then create our own colorbox.

		self._jquery_colorbox_close_function = jQuery.colorbox.close;
		self._jquery_colorbox_resize_function = jQuery.colorbox.resize;
		self._jquery_colorbox_function = jQuery.colorbox;

		jQuery.colorbox = self._colorbox;
		jQuery.colorbox.close = self._colorbox_close;
		jQuery.colorbox.resize = self._colorbox_resize;

		self._icl_editor_insert_function = icl_editor.insert;
		icl_editor.insert = self._insert_content;

	}

    // Woocommerce Views shortcodes
	self._wcviews_insert_wpv_woo_product_image = function () {
		self._override_jquery_colorbox_functions();

		self._wcviews_insert_wpv_woo_product_image_function ();
	}

    self._wcviews_insert_wpv_woo_buy_or_select = function () {
		self._override_jquery_colorbox_functions();

		self._wcviews_insert_wpv_woo_buy_or_select_function ();
	}

    self._wcviews_insert_wpv_woo_buy_options = function () {
		self._override_jquery_colorbox_functions();

		self._wcviews_insert_wpv_woo_buy_options_function ();
	}

    self._wcviews_insert_wpv_woo_productcategory_images = function () {
		self._override_jquery_colorbox_functions();

		self._wcviews_insert_wpv_woo_productcategory_images_function ();
	}
    // End Woocommerce Views shortcodes

	self._colorbox_resize = function (params) {
		if (params['innerHeight']) {
			jQuery('#ddl-colorbox-2 #ddl-types-popup').each (function () {
				jQuery(this).css({height : params['innerHeight']});

				// Make sure the dialog fits on the screen
				var dialog_bottom = jQuery(this).offset().top + jQuery(this).height();
				var window_height = jQuery(window).height();

				if (dialog_bottom > window_height) {
					jQuery(this).closest('.ddl-colorbox-2').animate({top : jQuery(this).offset().top - (dialog_bottom - window_height + 20)}, 500);
				}

			});
		}
	}

	self._colorbox = function(params) {

		if (params['iframe']) {

			self._create_color_box_elements(true);

			jQuery('#ddl-colorbox-2').html('<iframe title="Types fields" name="ddl-types-popup" id="ddl-types-popup" src="' + params['href'] + '" width="' + params['width'] + '"></iframe>');

			self._position_popup(params);

		} else if (params['href']){

			jQuery.ajax({
					type:'post',
					url:params['href'],
					success:function(response){

						self._create_color_box_elements(false);

						jQuery('#ddl-colorbox-2').html(response);

						jQuery('#ddl-colorbox-2 .js-dialog-close').on('click', self._colorbox_close)

						self._position_popup(params);

						if (params['onComplete']) {
							params['onComplete']();
						}
					},
				});
			}


	}

	self._create_color_box_elements = function (add_shadow) {
		jQuery('body').append('<div id="ddl-colorbox-2-overlay" class="ddl-colorbox-2-overlay">');
		jQuery('body').append('<div id="ddl-colorbox-2" class="ddl-colorbox-2">');

		var z_index = parseInt(jQuery('#colorbox').css('z-index')) + 1;
		jQuery('#ddl-colorbox-2-overlay').css({'z-index' : z_index});
		jQuery('#ddl-colorbox-2').css({'z-index' : z_index});

		if (add_shadow) {
			jQuery('#ddl-colorbox-2').css({	'box-shadow': '0 0 15px rgba(0, 0, 0, 0.4)' });
		}

		jQuery('#ddl-colorbox-2-overlay').on('click', function (event) {
			if (add_shadow) {
				jQuery('#ddl-colorbox-2').css({	'box-shadow': '0 0 15px #21759B' });
				_.delay(function () {jQuery('#ddl-colorbox-2').css({	'box-shadow': '0 0 15px rgba(0, 0, 0, 0.4)' });} , 500);
			} else {
				jQuery('#ddl-colorbox-2 .wpv-dialog').css({	'box-shadow': '0 0 15px #21759B' });
				_.delay(function () {jQuery('#ddl-colorbox-2 .wpv-dialog').css({	'box-shadow': '0 0 15px rgba(0, 0, 0, 0.4)' });} , 500);
			}
		})
	}

	self._position_popup = function (params) {
		var offset = jQuery(self._positioning_element).offset();

		jQuery('#ddl-colorbox-2').css({top : "7%",
									   left : "29%",
									   width: params['width']});
	}

	self._insert_content = function (content) {
		window.wpcfActiveEditor = self._target_id;
		self._icl_editor_insert_function(content);
	}

	self._colorbox_close = function () {
		self._close_popup();

		self._restore_overrides();
	}

	self._restore_overrides = function() {

		jQuery.colorbox = self._jquery_colorbox_function;
		jQuery.colorbox.close = self._jquery_colorbox_close_function;
		jQuery.colorbox.resize = self._jquery_colorbox_resize_function;

		icl_editor.insert = self._icl_editor_insert_function;
	}

	self._close_popup = function () {
		jQuery('#ddl-colorbox-2').remove();
		jQuery('#ddl-colorbox-2-overlay').remove();
	}


	self.init();
};

DDLayout.types_views_popup_manager = new DDLayout.typesViewsPopup($);
