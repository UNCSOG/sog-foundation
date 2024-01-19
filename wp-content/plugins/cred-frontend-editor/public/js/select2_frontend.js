/* eslint-disable */

var CREDFrontendSelect2 = CREDFrontendSelect2 || {};

/**
 * This new set of methods are needed to transform for each cred form select fields in select2
 * The elaboration starts from a select2FieldList that includes the list of
 * cred forms select fields that will have to be transformed in select2 Standard or Ajax
 */
CREDFrontendSelect2.init = function () {

    var self = this;

    self.ajaxurl = cred_select2_frontend_settings.ajaxurl;
    self.select2FieldsList = cred_select2_frontend_settings.select2_fields_list;
    self.lang = cred_select2_frontend_settings.cred_lang;

    /**
     * Execution of all signed select2
     */
    self.executeSelect2FieldsTransformation = function () {
        for (var htmlFormId in self.select2FieldsList) {
            for (var fieldName in self.select2FieldsList[htmlFormId]) {
                var action = self.select2FieldsList[htmlFormId][fieldName].action;
                var parameter = self.select2FieldsList[htmlFormId][fieldName].parameter;
                var fieldSettings = self.select2FieldsList[htmlFormId][fieldName].field_settings;
                var currentOption = (_.has(self.select2FieldsList[htmlFormId][fieldName], 'current_option')) ? self.select2FieldsList[htmlFormId][fieldName].current_option : null;

                //If there is action info means that is a ajax select2
                if (!action) {
                    self.transformSelectToSelect2(htmlFormId, fieldName, currentOption, parameter, fieldSettings);
                } else {
                    if (_.has(fieldSettings, 'is_relationship')) {
                        self.transformSelectToSelect2RelationshipAjax(htmlFormId, fieldName, currentOption, action, fieldSettings);
                    } else {
                        //Legacy Parent Back Compatibility
                        self.transformSelectToSelect2Ajax(htmlFormId, fieldName, currentOption, action, parameter, fieldSettings);
                    }
                }
            }
        }
    };

    /**
     * Transforms a classic select field to a select2 with Ajax feature specific for Relationship Fields
     *
     * @param htmlFormId
     * @param fieldName
     * @param currentOption
     * @param action
     * @param fieldSettings
     */
    self.transformSelectToSelect2RelationshipAjax = function (htmlFormId, fieldName, currentOption, action, fieldSettings) {
        var is_multiple = (fieldSettings['type'] === 'multiselect');
        if (is_multiple) {
            fieldName += '[]';
        }

        // setting placeholder empty in order to respect select_text shortcode default rule
        var placeholder = '';
        var hasPlaceholder = _.has(fieldSettings, 'placeholder');
        if (hasPlaceholder) {
            placeholder = fieldSettings['placeholder'];
        }
        var hasCurrentOption = (currentOption !== null);

        var $currentFieldSelector = jQuery('select[name="' + fieldName + '"]', '#' + htmlFormId);

		// Check if it is a readonly selector because the related post was already set
        var readonlySelector = (_.has(fieldSettings, 'readonly') && fieldSettings['readonly'] === true) ? true : false;

		// Our rendering library turns some readonly select dropdowns into text inputs,
		// and renders the select dropdown as an empty shell with a different, suffixed name attribute.
		if ( 0 == $currentFieldSelector.length ) {
			$currentFieldSelector = jQuery('select[name="' + fieldName + '_disabled"]', '#' + htmlFormId);
			if ( 0 == $currentFieldSelector.length ) {
				return;
			}
			readonlySelector = true;
		}

		// If the related post selector is readonly and has a value, set it
		// and initialize select2 without options: the value can not be changed.
		if ( readonlySelector && hasCurrentOption ) {
			$currentFieldSelector.find( 'option' ).remove();
            $currentFieldSelector.append('<option value="' + currentOption.value + '">' + currentOption.text + '</option>');
			$currentFieldSelector.toolset_select2();
			$currentFieldSelector.toolset_select2('val', currentOption.value, true);
			return;
        }

		// Otherwise, initialize select2 with AJAX search
        $currentFieldSelector.toolset_select2({
            width: 'resolve',
            allowClear: true,
            placeholder: placeholder,
            multiple: is_multiple,
            minimumInputLength: 0,
            maximumSelectionLength: 20,
            ajax: {
                url: self.ajaxurl,
                dataType: 'json',
                delay: 250,
                type: 'POST',
                cache: false,
                data: function (params) {
                    return {
                        q: (_.has(params, 'term')) ? params.term : '',
                        page: (params.page) ? params.page : 0,
                        action: action,
                        //relationship slug
                        slug: fieldSettings.slug,
                        //target item to associate to
                        role: fieldSettings.role,
                        //source item to connect to
                        cred_post_id: fieldSettings.post_id,
                        // language, if any
                        cred_lang: self.lang,
						orderBy: $currentFieldSelector.data( 'orderby' ),
						order: $currentFieldSelector.data( 'order' ),
						author: $currentFieldSelector.data( 'author' ),
						form_type: fieldSettings.form_type || null
                    };
                },
                processResults: function (response) {
                    if (response.success) {
                        return {
                            results: response.data
                        };
                    } else {
                        console.log(response.data.message);
                        return {
                            results: []
                        };
                    }
                },
                error: function (ajaxContext) {
                    console.log(ajaxContext);
                },
                complete: function () {
                }
            }
        });

        //Set current option in Edit Form if exists
        if ( hasCurrentOption ) {
            $currentFieldSelector.append('<option value="' + currentOption.value + '">' + currentOption.text + '</option>');
            $currentFieldSelector.toolset_select2('val', currentOption.value, true);
        }

    };

    /**
     * Transforms a classic select field to a select2 with Ajax feature
     *
     * @param htmlFormId
     * @param fieldName
     * @param currentOption
     * @param action
     * @param parameter
     * @param fieldSettings
     */
    self.transformSelectToSelect2Ajax = function (htmlFormId, fieldName, currentOption, action, parameter, fieldSettings) {
        var is_multiple = (fieldSettings['type'] === 'multiselect');
        if (is_multiple) {
            fieldName += '[]';
        }

        // setting placeholder empty in order to respect select_text shortcode default rule
        var placeholder = '';
        var hasPlaceholder = _.has(fieldSettings, 'placeholder');
        if (hasPlaceholder) {
            placeholder = fieldSettings['placeholder'];
        }
        var hasCurrentOption = (currentOption !== null);

        var $currentFieldSelector = jQuery('select[name="' + fieldName + '"]', '#' + htmlFormId);

		// Our rendering library turns some readonly select dropdowns into text inputs,
		// and renders the select dropdown as an empty shell with a different, suffixed name attribute.
		if ( 0 == $currentFieldSelector.length ) {
			$currentFieldSelector = jQuery('select[name="' + fieldName + '_disabled"]', '#' + htmlFormId);
			if ( 0 == $currentFieldSelector.length ) {
				return;
			}
			readonlySelector = true;
		}

        $currentFieldSelector.toolset_select2({
            width: 'resolve',
            allowClear: true,
            placeholder: placeholder,
            multiple: is_multiple,
            minimumInputLength: 0,
            maximumSelectionLength: 20,
            ajax: {
                url: self.ajaxurl,
                dataType: 'json',
                delay: 250,
                type: 'POST',
                cache: false,
                data: function (params) {
                    return {
                        q: (params.term) ? params.term : '',
                        page: (params.page) ? params.page : 0,
                        action: action,
                        parameter: parameter,
                        wpml_context: fieldSettings.wpml_context,
                        wpml_name: fieldSettings.slug,
                        // language, if any
                        cred_lang: self.lang,
						orderBy: $currentFieldSelector.data( 'orderby' ),
						order: $currentFieldSelector.data( 'order' ),
						author: $currentFieldSelector.data( 'author' )
                    };
                },
                processResults: function (response) {
                    if (response.success) {
                        return {
                            results: response.data
                        };
                    } else {
                        console.log(response.data.message);
                        return {
                            results: []
                        };
                    }
                },
                error: function (ajaxContext) {
                    console.log(ajaxContext);
                },
                complete: function () {
                }
            }
        });

        //Set current option in Edit Form if exists
        if (hasCurrentOption) {
            $currentFieldSelector.append('<option value="' + currentOption.value + '">' + currentOption.text + '</option>');
            $currentFieldSelector.toolset_select2('val', currentOption.value, true);
        }
    };

    /**
     * Transforms a classic select box to a standard select2 not ajax
     *
     * @param htmlFormId
     * @param fieldName
     * @param currentOption
     * @param parameter
     * @param fieldSettings
     */
    self.transformSelectToSelect2 = function (htmlFormId, fieldName, currentOption, parameter, fieldSettings) {
        var is_multiple = fieldSettings['type'] === 'multiselect';
        if (is_multiple) {
            fieldName += '[]';
        }

        var placeholder = '— Select —';
        var has_placeholder = _.has(fieldSettings, 'placeholder');
        if (has_placeholder) {
            placeholder = fieldSettings['placeholder'];
        }

        var hasCurrentOption = (currentOption !== null);

        var $currentFieldSelector = jQuery('select[name="' + fieldName + '"]', '#' + htmlFormId);

		// Our rendering library turns some readonly select dropdowns into text inputs,
		// and renders the select dropdown as an empty shell with a different, suffixed name attribute.
		if ( 0 == $currentFieldSelector.length ) {
			$currentFieldSelector = jQuery('select[name="' + fieldName + '_disabled"]', '#' + htmlFormId);
			if ( 0 == $currentFieldSelector.length ) {
				return;
			}
			readonlySelector = true;
		}

        $currentFieldSelector.toolset_select2({
            data: parameter,
            placeholder: placeholder,
            multiple: is_multiple,
            minimumInputLength: 1,
            allowClear: true
        });

        //Set current option in Edit Form if exists
        if (hasCurrentOption) {
            $currentFieldSelector.append('<option value="' + currentOption.value + '">' + currentOption.text + '</option>');
            $currentFieldSelector.toolset_select2('val', currentOption.value, true);
        }
    };

    self.executeSelect2FieldsTransformation();
};

jQuery(function () {
    var credSelect2FrontendSetting = new CREDFrontendSelect2.init();

    //bounding onValidatedSubmitAjaxForm
    var boundOnCREDFrontendSelect2Init = _.bind(CREDFrontendSelect2.init, CREDFrontendSelect2);
    //After cred form submit validation success
    Toolset.hooks.addAction('cred_form_ajax_completed', boundOnCREDFrontendSelect2Init);

});
