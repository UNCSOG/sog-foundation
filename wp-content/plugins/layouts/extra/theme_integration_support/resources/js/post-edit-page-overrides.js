var DDLayout = DDLayout || {};

DDLayout.ThemeIntegrations = DDLayout.ThemeIntegrations || {};

/**
 * This script expects to be enqueued on a post edit page when a theme integration plugin manually
 * adds support for some template files.
 *
 * It needs DDLayout_Settings.ThemeIntegrations.templates to be populated with an associative array of templates,
 * where key is a template file name and value is template name (that will be displayed).
 *
 * @param $
 * @constructor
 */
DDLayout.ThemeIntegrations.PostEditPageOverrides = function ($) {

    var self = this,
        theme_templates = DDLayout_Settings.ThemeIntegrations.theme_templates,
        template_default = DDLayout_Settings.ThemeIntegrations.default_template,
        themes = DDLayout_Settings.ThemeIntegrations.templates;

    self.init = function () {
        Toolset.hooks.addFilter('ddl-init-template-selector-on-ready', function() {
            return false;
        });
        self.populate_combined_select_box_if_empty();
    };

    self.populate_combined_select_box_if_empty = function() {
        var $after = $('#parent_id');

        Toolset.hooks.addFilter('ddl-default_template-template', function (template) {
            return template_default;
        });

        if ( $('#page_template').length === 0 || ( _.size( _.omit(themes, 'default') ) > 1 && _.size( theme_templates ) > 0 )   ) {
            self.create_wp_default_page_selector($after);
        }

        DDLayout.template_selector = new DDLayout.templateSelector($);
    };

    self.create_wp_default_page_selector = function ($after) {
        var just_created = false,
            $select = null,
            fragment = document.createDocumentFragment(),
            loop = _.size( themes ) === 1 ? themes : _.omit(themes, template_default);

        _.each(loop , function (value, key, list) {
            var option = '';
            if (key === 'default') {
                option = $('<option value="' + key + '" id="ddl-option-default">' + value + '</option>');
            } else {
                option = $('<option value="' + key + '">' + value + '</option>');
            }
            fragment.appendChild(option[0]);
        });

        if( $('#page_template').length ){
            $select = $('select[name="page_template"]');
        } else {
            $select = $('<select name="page_template" id="page_template" />');
            just_created = true;
        }

        $select.append(fragment);

        if( just_created === true ){
            $after.after($select);
        }

        return just_created ? $select : null;
    };

    self.init();
};

(function ($) {
    $(function () {
        // TODO: check what is this and is it necessary
        //DDLayout.ThemeIntegrations.PostEditPageOverrides.call({}, $);
    });
}(jQuery));