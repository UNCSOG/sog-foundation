(function (window, $, settings, utils, gui, undefined) {
    // public methods / properties
    var self = {
        // add the extra Modules as part of main Toolset Forms Module
        app: utils,
        gui: gui,
        settings: settings,
        route: function (path, params, raw)
        {
            return utils.route('cred', cred_settings.ajaxurl, path, params, raw);
        },
        getContents: function ()
        {
			// maybe DEPRECATED
            return {
                'content': utils.getContent($('#content')),
                'cred-extra-css-editor': utils.getContent($('#cred-extra-css-editor')),
                'cred-extra-js-editor': utils.getContent($('#cred-extra-js-editor'))
            };
        },
        posts: function () {
			// Keep for compatibility: some third parties initialized this on hard dependencies.
			return;
        }
    };

    $(function () {
        var cred_cred_instance = Toolset.hooks.applyFilters('cred_cred_cred_run', self, cred_settings, cred_utils, cred_gui);
    });

    // make public methods/properties available
    if (window.hasOwnProperty("cred_cred"))
        jQuery.extend(window.cred_cred, self);
    else
        window.cred_cred = self;

    return window.cred_cred;

})(window, jQuery, cred_settings, cred_utils, cred_gui);
