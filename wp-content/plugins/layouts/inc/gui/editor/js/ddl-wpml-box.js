// ddl-wpml-box.js

DDLayout.WPMLBoxHandler = function()
{
	var self = this;

	var _init = function _init()
	{
        jQuery(_position_wpml_box);
        jQuery(window).resize(_position_wpml_box);
	};

    var _position_wpml_box = function _position_wpml_box () {
        var available_width = jQuery("#wpbody-content").width();
        var main_editor_width = jQuery('.main-ddl-editor').width();

        if (main_editor_width + 300 > available_width) {
            // needs to be at the bottom
            jQuery('#js-dd-layouts-lang-wrap').removeClass('dd-layouts-lang-wrap-side');
        } else {
            // can be at the side.
            jQuery('#js-dd-layouts-lang-wrap').addClass('dd-layouts-lang-wrap-side');

			if( jQuery('.js-title-div').is('div') ){
				var top = jQuery('.js-title-div').offset().top - jQuery('#wpbody').offset().top;
				jQuery('#js-dd-layouts-lang-wrap').css({'top' : top + 'px'});
			}
        }
    };

	self.update_wpml_state = function (layout_id, register_strings) {
		if (jQuery('#js-dd-layouts-lang-wrap').length || jQuery('#wpml-package-admin-bar-popup').length ) {
            var data = {
                action : 'ddl_update_wpml_state',
                layout_id: layout_id,
                register_strings : register_strings,
                wpnonce : jQuery('#ddl_layout_view_nonce').val()
            };
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: data,
                cache: false,
                success: function(data) {
					if ( jQuery('#js-dd-layouts-lang-wrap').length ) {
						jQuery('#js-dd-layouts-lang-wrap .dd-layouts-lang-wrap').html(data);
						if (data) {
							jQuery('#js-dd-layouts-lang-wrap').show();
						}
					} else {
						jQuery('#wpml-package-admin-bar-popup').html(data);
						WPML_Core.simple_language_selector.initialize_select2();
						if ( typeof WPML_Package_Translation.admin_language_switcher === "undefined" ) {
							WPML_Package_Translation.admin_language_switcher = new WPML_Package_Translation.AdminLanguageSwitcher();
						}
						WPML_Package_Translation.admin_language_switcher.init();
						if ( typeof WPML_Package_Translation.meta_box === "undefined" ) {
							WPML_Package_Translation.meta_box = new WPML_Package_Translation.MetaBox();
						}
						WPML_Package_Translation.meta_box.init();
					}
                }
            });

		}
	};

	_init();
};
