var DDLayout = DDLayout || {};

DDLayout.WPMLSwitcher  = function($){
    var self = this,
        $lang_select = null,
        $dialog = null;

    self.default_language = DDLayout_LangSwitch_Settings.default_language;
    self.current_language = DDLayout_LangSwitch_Settings.current_language;
    self.post_id = null;

    self.init = function(){
        Toolset.hooks.addAction('ddl-wpml-init', self.dialog_before_load, 10, 3);
        Toolset.hooks.addAction('ddl-wpml-refresh', self.ajax_response_callback, 10, 3);
        Toolset.hooks.addAction('ddl-wpml-cleanup', self.clean_up_events, 10);
        Toolset.hooks.addFilter('ddl-js-apply-language', self.get_current_language);
    };

    self.dialog_before_load = function( dialog, post_id, args ){
        $dialog = dialog;
        self.post_id = post_id;
        $lang_select = $('.js-ddl-single-assignments-lang-select', $dialog);
        self.init_selector( $lang_select );
    };

    self.ajax_response_callback = function( container, post_id, args ){
        $lang_select = $('.js-ddl-single-assignments-lang-select', $dialog);
        self.init_selector( $lang_select );
    };

    self.get_current_language = function(lang){
        return self.current_language;
	};

	self.escapeMarkup = function( m ) {
		return $.fn.toolset_select2_original.defaults.defaults.escapeMarkup( m );
	}

    self.init_selector = function( $lang_select ){
        function format( option ) {
            if( $(option.element).data('languageIcon') === 'none' ) {
				return self.escapeMarkup( option.text );
			}

            var icon = $(option.element).data('languageIcon') ? '<i class="ddl-wpml-flag" style="background:url('+$(option.element).data('languageIcon')+') no-repeat bottom left"></i>' : '';
            return icon + self.escapeMarkup( option.text );
        }
	// show default language in case if "all language" is selected
	if(self.current_language === 'all'){
	    self.current_language = self.default_language;
	}
        $lang_select.toolset_select2({
            minimumResultsForSearch: Infinity,
            templateResult: format,
            templateSelection: format,
            width: '150px',
			val:self.current_language,
			// Disable escaping the markup because we need teplates for results and selection.
			// Note that we are escaping the data on origin, so it should be safe.
            escapeMarkup: function(m) { return m; }
        });

        $lang_select.toolset_select2('val', self.current_language );
        $lang_select.val(self.current_language).trigger('change');
        self.change_handler( $lang_select );

    };

    self.clean_up_events = function( ){
        self.current_language = DDLayout_LangSwitch_Settings.current_language;
        $lang_select.toolset_select2('close');
    };

    self.change_handler = function( $lang_select ){

        $lang_select.on('change', function( event ){
            self.current_language = $(this).val();
            Toolset.hooks.doAction('ddl-reload-post-list-by-language', event);
        });
    };

    self.init();
};

DDLayout.WPMLSwitcher.builder = function( $ ){
    DDLayout.wpmlSwitcher = new DDLayout.WPMLSwitcher($);
};
Toolset.hooks.addAction('ddl-wpml-language-switcher-build', DDLayout.WPMLSwitcher.builder, 10, 1);
