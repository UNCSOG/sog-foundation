var CREDFormSettingsBox = CREDFormSettingsBox || {};

/**
 * Set of methods that rule the template Toolset Post Form Settings Box.
 *
 * In particular they rule the option "custom_post" with label "Go To a specific post..."
 *
 * This setting option includes 2 select boxes that will be transformed in select2
 * if have many options to show.
 */
CREDFormSettingsBox.init = function () {
    var self = this;

    self.cred_first_loading = true;
    self.form_type = cred_form_settings_box.form_type;    self.default_redirect_custom_post_min_posts_count_for_select2 = cred_form_settings_box.default_redirect_custom_post_min_posts_count_for_select2;
    self.$wp_main_submit = jQuery('input[name="save"]', '#publishing-action');
    self.$action_custom_post = jQuery('#cred_form_action_custom_post', '#after_visitors_submit_this_form');
    self.$action_custom_post_loader = jQuery('#cred_form_action_ajax_loader', '#after_visitors_submit_this_form');
    self.$form_success_action = jQuery('#cred_form_success_action', '#credformtypediv');
    self.$cred_form_type = jQuery('input[name="_cred[form][type]"]', '#credformtypediv');
    self.$post_type_selector = jQuery('#cred_form_action_post_type', '#after_visitors_submit_this_form');
    self.post_type_selector_size = jQuery('#cred_form_action_post_type option', '#after_visitors_submit_this_form').length;

    self.$cred_form_user_role = jQuery('#cred_form_user_role', '#credformtypediv');
    self.user_roles = cred_form_settings_box.user_roles;
    self.selected_user_roles = cred_form_settings_box.selected_user_roles;
    self.settings_form_type = cred_form_settings_box.settings_form_type;

    /**
     * Try to transform post_type to select2 selector
     */
    self.checkPostTypeSelector4Select2 = function () {
        if (self.post_type_selector_size > 15) {
            self.$post_type_selector.toolset_select2({
                width: 'resolve',
                placeholder: cred_form_settings_box.default_empty_action_post_type,
                allowClear: true,
                minimumInputLength: 1,
            });
        }
    };

    self.tryGetPostsCountByPostType = function () {
        if ('custom_post' === jQuery(this).val()) {
            self.getPostsCountByPostType();
        }
    };

    /**
     * Function that executes pre-query with posts count
     * in order to decide to use select or select2
     *
     * TODO The AJAX call get_option_count_by_post_type should not get just a count, but the list of the last 16 available posts
     * If it returns less than 16, we compose a select dropdown; otherwise, we initialize a select2.
     * TODO The AJAX call should be cached: fired just once per post type.
     */
    self.getPostsCountByPostType = function () {
        if (self.$form_success_action.val() !== 'custom_post') {
            return;
        }

        var post_type = self.$post_type_selector.val();

        self.$action_custom_post.toolset_select2("destroy");
        self.$action_custom_post.empty();
        self.$action_custom_post.append('<option value="">' + cred_form_settings_box.default_empty_action_post + '</option>');

        if (post_type == '') {
            return;
        }

        self.$action_custom_post_loader.css('display', 'inline-block');
        self.$action_custom_post.hide();

        var url = ajaxurl + "?action=cred_ajax_Posts&_do_=get_option_count_by_post_type";
        jQuery.ajax({
            type: 'post',
            dataType: 'json',
            url: url,
            data: {'post_type': post_type},
            cache: false,
            placehholer: cred_form_settings_box.default_empty_action_post,
            minimumInputLength: 1,
            success: function (response) {
                if (response.success) {
                    self.whatSelectByPostsCountAndPostType(response.data.count, post_type);
                } else {
                    cred_gui.Popups.alert({message: response.data.message, class: 'cred-dialog'});
                }
            },
            error: function (ajaxContext) {
                cred_gui.Popups.alert({message: ajaxContext.responseText, class: 'cred-dialog'});
            },
            complete: function () {
            }
        }).fail(function () {
            self.$action_custom_post_loader.hide();
            self.$action_custom_post.show();
        });
    };

    /**
     * Create the correct select box depending by count and post_type
     *
     * @param post_type
     * @param count
     */
    self.whatSelectByPostsCountAndPostType = function (count, post_type) {
        if (count <= self.default_redirect_custom_post_min_posts_count_for_select2) {
            self.createSelectPostsByPostType(post_type);
        } else {
            self.createSelect2PostsByPostType(post_type);
        }
    };

    /**
     * Creates select2 ajax select box in order to search posts by post_type
     * @param post_type
     */
    self.createSelect2PostsByPostType = function (post_type) {
        var url = ajaxurl + "?action=cred_ajax_Posts&_do_=suggest_posts_by_title";

        self.$action_custom_post.toolset_select2({
            width: 'resolve',
            allowClear: true,
            placehholer: cred_form_settings_box.default_empty_action_post,
            minimumInputLength: 1,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: (params.term) ? params.term : '',
                        page: (params.page) ? params.page : 0,
                        post_type: post_type
                    };
                },
                processResults: function (response) {
                    return {results: response.data};
                },
                cache: false
            },
        });

        self.$action_custom_post_loader.hide();
        self.$action_custom_post.show();

        var currentData = {};
        if (cred_form_settings_box.has_current_action_post) {
            if (self.cred_first_loading) {
                self.cred_first_loading = false;
                currentData = {
                    value: cred_form_settings_box.form_current_action_post_id,
                    text: cred_form_settings_box.form_current_action_post_title
                };
                self.$action_custom_post.append('<option value="' + currentData.value + '">' + currentData.text + '</option>');
                self.$action_custom_post.toolset_select2('val', currentData.value, true);
            }
        }
    };

    /**
     * Creates classic select box containing posts by post_type
     * @param post_type
     */
    self.createSelectPostsByPostType = function (post_type) {
        var url = ajaxurl + "?action=cred_ajax_Posts&_do_=get_option_list_by_post_type";

        jQuery.ajax({
            type: 'post',
            dataType: 'json',
            url: url,
            data: {'post_type': post_type},
            cache: true,
            minimumInputLength: 1,
            success: function (response) {
                if (response.success) {
                    for (var i = 0; i < response.data.length; i++) {
                        self.$action_custom_post.append('<option value="' + response.data[i].id + '">' + response.data[i].text + '</option>');
                    }
                    if (cred_form_settings_box.has_current_action_post) {
                        if (self.cred_first_loading) {
                            self.cred_first_loading = false;
                            var current_val = cred_form_settings_box.form_current_action_post_id;
                            if (jQuery("#cred_form_action_custom_post option[value='" + current_val + "']").length > 0) {
                                self.$action_custom_post.val(current_val);
                            }
                        }
                    }
                } else {
                    cred_gui.Popups.alert({message: response.data.message, class: 'cred-dialog'});
                }
            },
            error: function (ajaxContext) {
                cred_gui.Popups.alert({message: ajaxContext.responseText, class: 'cred-dialog'});
            },
            complete: function () {
            }
        }).always(function () {
            self.$action_custom_post_loader.hide();
            self.$action_custom_post.show();
        });
    };

    self.initUserRoles = function () {
        if (self.settings_form_type === 'edit') {
            for (var user_role in self.user_roles) {
                if (Array.isArray(self.selected_user_roles)
                    && jQuery.inArray(user_role, self.selected_user_roles) !== -1) {
                    jQuery('#role_' + user_role).prop('checked', true).trigger( 'change' );
                }
            }
        } else {
            if (jQuery.isEmptyObject(self.selected_user_roles)) {
                jQuery('#cred_form_user_role option').first().prop('selected', 'selected');
            } else {
                for (var user_role in self.selected_user_roles) {
                    jQuery('#cred_form_user_role option[value=' + self.selected_user_roles[user_role] + ']').prop('selected', 'selected');
                    break;
                }
			}
			jQuery( '#cred_form_user_role' ).trigger( 'change' );
        }
    };

    self.roleSwitch = function () {
        var settings_form_type = jQuery("input[name='_cred[form][type]']:checked").val();
        if (settings_form_type === 'edit') {
            jQuery(".roles_checkboxes").prop("disabled", false);
            jQuery(".roles_checkboxes").show();
            jQuery(".roles_selectbox").prop("disabled", true);
            jQuery(".roles_selectbox").hide();
        } else {
            jQuery(".roles_checkboxes").prop("disabled", true);
            jQuery(".roles_checkboxes").hide();
            jQuery(".roles_selectbox").prop("disabled", false);
            jQuery(".roles_selectbox").show();

            if (jQuery.isEmptyObject(self.selected_user_roles)) {
                jQuery('#cred_form_user_role option').first().prop('selected', 'selected');
            } else {
                for (var user_role in self.selected_user_roles) {
                    jQuery('#cred_form_user_role option[value=' + self.selected_user_roles[user_role] + ']').prop('selected', 'selected');
                    break;
                }
            }
        }
    };

    self.$post_type_selector.on('change', self.getPostsCountByPostType);
    self.$form_success_action.on('change', self.tryGetPostsCountByPostType);
    self.$form_success_action.on('change', self.checkPostTypeSelector4Select2);

    if (self.form_type === 'user') {
        self.initUserRoles();
        self.roleSwitch();

        self.$cred_form_type.on("click", function () {
            self.roleSwitch();
        });

        if (cred_form_settings_box.fix_settings_action) {
            jQuery("select[name='_cred[form][action]']").val('form');
        }
    }

    self.getPostsCountByPostType();
    self.checkPostTypeSelector4Select2();
};

jQuery(function () {
    var credFormSettingsBox = new CREDFormSettingsBox.init();
});
