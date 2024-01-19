(function (window, $, settings, utils, gui, mvc, undefined) {
    // uses WordPress 3.3+ features of including jquery-ui effects

    // oonstants
    var KEYCODE_ENTER = 13, KEYCODE_ESC = 27, PREFIX = '_cred_cred_prefix_',
        PAD = '\t', NL = '\r\n';

    // private properties
    var form_id = 0,
        settingsPage = null,
        form_name = '',
        field_data = null,
        // used for MV framework, bindings and interaction
        _credModel, _credView;

    // auxilliary functions
    var aux = {

        _originalCredAutogenerateUsernameScaffold: "",
        _originalCredAutogenerateNicknameScaffold: "",
        _originalCredAutogeneratePasswordScaffold: "",

        getFormType: function () {
            return jQuery('input[name="_cred[form][type]"]:checked').val();
        },
        checkCredFormType: function () {
            var $type_form_selector = jQuery('input[name="_cred[form][type]"]');

            if ($type_form_selector) {

                var credFormTypeSelected = aux.getFormType();
                if ('new' == credFormTypeSelected) {
                    jQuery('.cred_notification_field_only_if_changed input[type=checkbox]').attr('disabled', 'disabled');
                    jQuery('.cred_notification_field_only_if_changed').hide();
                    if (jQuery('.when_submitting_form_text').length) {
                        jQuery('.when_submitting_form_text').html('When a new user is created by this form');
                    }

                    if (jQuery('#cred_post_status') && jQuery('#cred_post_status').length > 0) {
                        jQuery("#cred_post_status option").each(function () {
                            if ('original' === jQuery(this).val()) {
                                jQuery(this).remove();
                            }
                        });
                    }
                } else {
                    jQuery('.cred_notification_field_only_if_changed').show();
                    jQuery('.cred_notification_field_only_if_changed input[type=checkbox]').removeAttr('disabled');
                    if (jQuery('.when_submitting_form_text').length) {
                        jQuery('.when_submitting_form_text').html('When a user is updated by this form');
                    }


                    if (jQuery('#cred_post_status') && jQuery('#cred_post_status').length > 0
                        && jQuery("#cred_post_status option[value='original']").length <= 0) {
                        jQuery("#cred_post_status option").each(function () {
                            if ('' === jQuery(this).val()) {
                                jQuery( $toolsetFormsOriginalStatusOption ).insertAfter( jQuery( this ) );
                            }
                        });
                    }
                }
            }
        }
    };

    // public methods / properties
    var self = {
        // add the extra Modules as part of main Toolset Forms Module
        app: utils,
        gui: gui,
        mvc: mvc,
        settings: settings,
        route: function (path, params, raw) {
            return utils.route('cred', cred_settings.ajaxurl, path, params, raw);
        },
        isEmail: function (email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        },
        /**
         * Process fields gathered for this content type, and include generic fields in the form content.
         *
         * This will be later used to set notifications available targets.
         *
         * @param fields
         *
         * @return Object
         *
         * @since 2.1
         */
        processFieldsAndAddGeneric: function( fields ) {
            var content = utils.getContent().replace(/[\n\r]+/g, ' ');
            _.each ( fields, function( fieldsGroup, fieldsGroupKey, fieldsGroupedList  ) {
                _.each( fieldsGroup, function( fieldItem, fieldKey, fieldLInGroupist) {
                    fieldItem.persistent = true;
                    fieldItem.genericType = false;
                    var fieldString = new RegExp("field=\"" + fieldKey + "\""),
                        fieldStringAlt = new RegExp("field=\'" + fieldKey + "\'");
                    fieldItem.onForm = ( fieldString.test( content ) || fieldStringAlt.test( content ) );
                });
            });

            fields.generic = {};

            var genericFieldPattern = {
                rx: /\[cred[\-_]generic[\-_]field\b([^\[\]]*?)\](.+?)\[\/cred[\-_]generic[\-_]field\]/g,
                atts: 1,
                content: 2
            },
            match, field, type, genericType, persistent,
            field_type_rxp = /field=[\"\']([\d\w\-_]+?)[\"\'][^\[\]]*?\btype=[\"\']([\d\w\-_]+?)[\"\']/,
            type_field_rxp = /type=[\"\']([\d\w\-_]+?)[\"\'][^\[\]]*?\bfield=[\"\']([\d\w\-_]+?)[\"\']/,
            persist_rxp = /["']persist["']\s*\:\s*(\d)/,
            generic_field_type_rxp = /["']generic_type["']\s*\:\s*["']([\w_]+)["']/

            while ( match = genericFieldPattern.rx.exec( content ) ) {
                field = false;
                type = false;
                genericType = false;
                label = field;
                generic = true;
                persistent = false;

                var
                    match_field_type = (genericFieldPattern.atts && match[genericFieldPattern.atts]) ? field_type_rxp.exec(match[genericFieldPattern.atts]) : false,
                    match_type_field = (genericFieldPattern.atts && match[genericFieldPattern.atts]) ? type_field_rxp.exec(match[genericFieldPattern.atts]) : false,
                    match_persist = (genericFieldPattern.content && match[genericFieldPattern.content]) ? persist_rxp.exec(match[genericFieldPattern.content]) : false,
                    match_generic_field_type = (genericFieldPattern.content && match[genericFieldPattern.content]) ? generic_field_type_rxp.exec(match[genericFieldPattern.content]) : false;

                if (match_field_type) {
                    field = match_field_type[1];
                    type = match_field_type[2];
                    label = field;
                } else if (match_type_field) {
                    type = match_type_field[1];
                    field = match_type_field[2];
                    label = field;
                } else {
                    continue;
                }

                if (match_persist && match_persist[1] && '1' == match_persist[1]) {
                    persistent = true;
                } else {
                    persistent = false;
                }

                if (match_generic_field_type && match_generic_field_type[1] && '' != match_generic_field_type[1]) {
                    genericType = match_generic_field_type[1];
                }

                fields.generic[ field ] = {
                    label: field,
                    field: field,
                    type: type,
                    genericType: genericType,
                    repetitive: false,
                    onForm: true,
                    persistent: persistent
                };
            }
            return fields;
        },
        /**
         * Gather fields based on their persistent nature by type, to keep the GUI updated.
         *
         * This will be later used to set notifications available targets.
         *
         * @param fields
         *
         * @return Object
         *
         * @since 2.1
         */
        gatherPersistentFields: function( fields ) {
            var persistentFields = {
                userId: [],
                mail: [],
                text: [],
                select: [],
                checkbox: [],
                allMetaFields: []
            },
            basicInAllMetaFields = [
                'post_title', 'post_content', 'post_excerpt',
                'user_login', 'user_pass', 'user_email', 'nickname', 'first_name', 'last_name', 'description'
            ];

            _.each ( fields, function( fieldsGroup, fieldsGroupKey, fieldsGroupedList  ) {
                _.each( fieldsGroup, function( fieldItem, fieldKey, fieldLInGroupist) {
                    if ( ! _.has( fieldItem, 'persistent' ) || ! fieldItem.persistent  ) {
                        return;
                    }
                    if ( ! _.has( fieldItem, 'onForm' ) || ! fieldItem.onForm  ) {
                        return;
                    }

                    // Meta fields have a metaKey with the actual item key
                    var fieldActualKey = ( _.has( fieldItem, 'metaKey' ) ) ? fieldItem.metaKey : fieldKey,
                        formattedOption = { value: fieldActualKey, label: fieldItem.label };

                    if ( 'meta' == fieldsGroupKey ) {
                        persistentFields.allMetaFields.push( formattedOption );
                    }

                    // Some basic fields are also pushe to the allMetaFields entry
                    // so they can be used as conditions for notifications triggered
                    // when a "custom" field is modified.
                    if (
                        'basic' == fieldsGroupKey
                        && _.contains( basicInAllMetaFields, fieldKey )
                    ) {
                        persistentFields.allMetaFields.push( formattedOption );
                    }

                    if ( _.has( fieldItem, 'type' ) ) {
                        if ( _.contains( [ 'email', 'mail' ], fieldItem.type ) ) {
                            persistentFields.mail.push( formattedOption );
                        }
                        if ( _.contains( [ 'text', 'textfield', 'numeric', 'integer' ], fieldItem.type ) ) {
                            persistentFields.text.push( formattedOption );
                        }
                        if ( _.contains( [ 'select', 'radio' ], fieldItem.type ) ) {
                            persistentFields.select.push( formattedOption );
                        }
                        if ( _.contains( [ 'checkbox', 'checkboxes' ], fieldItem.type ) ) {
                            persistentFields.checkbox.push( formattedOption );
                        }
                    }

                    if (
                        _.has( fieldItem, 'shortcode')
                        && 'cred_field' == fieldItem.shortcode
                        && _.has( fieldItem, 'attributes' )
                        && _.has( fieldItem.attributes, 'field' )
                    ) {
                        if ( 'user_email' == fieldItem.attributes.field ) {
                            persistentFields.mail.push( formattedOption );
                        }
                        if ( 'first_name' == fieldItem.attributes.field ) {
                            persistentFields.text.push( formattedOption );
                        }
                        if ( 'last_name' == fieldItem.attributes.field ) {
                            persistentFields.text.push( formattedOption );
                        }
                    }

                    if (
                        _.has( fieldItem, 'genericType' )
                        && 'user_id' == fieldItem.genericType
                    ) {
                        persistentFields.userId.push( formattedOption );
                    }
                });
            });

            return persistentFields;
        },
        doCheck: function (step) {
            if (step === 1) {
                return true;
            }
            // title check
            var title = jQuery('#title').val();
            if (/[\#\@\[\]\'\"\!\/\\]/g.test(title) || title.length <= 0) {
                gui.Popups.alert({message: cred_settings.locale.invalid_title, class: 'cred-dialog'});
                return false;
            }

            //validate notifications emails
            var email_fields = jQuery('.notification-sender-email');
            for (var field in email_fields) {
                if (isNaN(field))
                    break;
                if (jQuery(email_fields[field]).val() != "" && !this.isEmail(jQuery(email_fields[field]).val())) {
                    gui.Popups.alert({
                        message: cred_settings.locale.invalid_notification_sender_email,
                        class: 'cred-dialog',
                        callback: function () {
                            jQuery(email_fields[field]).closest('.cred_notification_settings_panel').show();
                            jQuery(email_fields[field]).focus();
                        }
                    });
                    return false;
                }
            }

            return true;
        },
        /**
         * Keep, as it is used by the wizard.
         */
        getContents: function () {
            return {
                'content': utils.getContent(jQuery('#content')),
                'cred-extra-css-editor': utils.getContent(jQuery('#cred-extra-css-editor')),
                'cred-extra-js-editor': utils.getContent(jQuery('#cred-extra-js-editor'))
            };
        },
        getModel: function () {
            return _credModel
        },
        getView: function () {
            return _credView
        },
        forms: function () {
            var doinit = true,
                $_post = jQuery('#post');

            // init model with current form data (one model for all data)
            _credModel = new mvc.Model('_cred', window._credFormData);
            // can use multiple views per same model
            _credView = new mvc.View('cred', _credModel, {
                init: function () {
                    aux.checkCredFormType();
                    jQuery('input[name="_cred[form][type]"]').on('change', function () {
                        aux.checkCredFormType();
                    });

                    /**
                     * Adjust the GUI when the post form is set to create RFG instances.
                     *
                     * Triggered on document.ready and on optio change.
                     *
                     * @since 2.0
                     */
                    function maybeAdjustGuiForRfg() {
                        if (
                            ! cred_settings.is_m2m_enabled
                            || ! _.has( cred_settings, 'rfg_post_types' )
                        ) {
                            return;
                        }
                        var $postTypeSelect = $( '#cred_post_type' ),
                            $statusSelect = $( '#cred_post_status' ),
                            $actionSelect = $( '#cred_form_success_action' ),
                            postTypeSelected = $postTypeSelect.val(),
                            actionSelected = $actionSelect.val();
                        if ( _.contains( cred_settings.rfg_post_types, postTypeSelected ) ) {
                            $statusSelect
                                .find( 'option[value!="publish"]' )
                                .prop( 'disabled', true );
                            $statusSelect
                                .val( 'publish' )
                                .trigger( 'change' );
                            $actionSelect
                                .find( 'option[value="post"]' )
                                .prop( 'disabled', true );
                            if ( 'post' == actionSelected ) {
                                $actionSelect
                                    .val( 'form' )
                                    .trigger( 'change' );
                            }
                        } else {
                            $statusSelect
                                .find( 'option:disabled' )
                                .prop( 'disabled', false );
                            $actionSelect
                                .find( 'option:disabled' )
                                .prop( 'disabled', false );
                        }
                    };

                    /**
                     * Sets init scaffold data
                     * First it loads the default content, then removes the items from the scaffold that are not included
                     * in the list, and finally adds the items that are not included by default
                     *
                     * @since 2.3
                     */
                    function initScaffoldAndEvents() {
                        if ( ( !! window._credFormData.extra.editor_origin && window._credFormData.extra.editor_origin === 'html' ) ) {
                            jQuery( '#cred-editor-scaffold' ).hide();
                            jQuery( '#cred-editor-html' ).removeClass( 'hidden' ).show();
                            jQuery( '#cred-editor-expert-mode-switcher' ).prop( 'checked', true ).removeAttr('disabled');
                            WPV_Toolset.CodeMirror_instance[ 'content' ].refresh();
                            jQuery( '.js-cred-editor-notice-switcher' ).removeClass( 'hidden' );
                        }
                        if ( !! window._credFormData.extra.scaffold ) {
                            // Data is modified in the model and it produces a JSON parse error
                            var data = window._credFormData.extra.scaffold;
                            data = data.replace( /{"value":"([^"]+)","label":"([^"]+)"}/g, '{\\"value\\":\\"$1\\",\\"label\\":\\"$2\\"}' );
                            var scaffoldData = JSON.parse( data );
                            var selectedValue = Toolset.hooks.applyFilters( 'cred-filter-get-current-form-target', '' );
                            if ( '' !== selectedValue ) {
                                // Loads the fields and then rearrange the items and set the options.
                                Toolset.hooks.doAction( 'cred_editor_init_scaffold', Toolset.CRED.ScaffoldEditor.setInitialScaffoldItems.bind( null, scaffoldData ) );
                            }
                        } else {
                            var selectedValue = Toolset.hooks.applyFilters( 'cred-filter-get-current-form-target', '' );
                            if ( '' !== selectedValue ) {
                                // Loads the fields and then rearrange the items and set the options.
                                Toolset.hooks.doAction( 'cred_editor_init_scaffold', function() {
                                    var scaffold = new Toolset.CRED.ScaffoldEditor();
                                    scaffold.addFieldItemsWrapperAndRow();
                                } );
                            }
                        }

                        // Listen to form target changes to recreate the scaffold
                        // It is really important to binf this after document.ready,
                        // so we are already done with the MVC binding when loading the form editor;
                        // othrwise, edit user forms with several roles as target will get crazy
                        // and start restarting the form every time a role checkbox is initialized.
                        jQuery( document ).on( 'change', '#cred_post_type, [name="_cred[form][user_role][]"]', function( event ) {
                            // Debounce here: no need to fire this too often when doing fast changes
                            initScaffoldOnTargetChangeDebounced();
                        });
                    };

                    // Delay initializing the scaffold on target change by 1 second
                    // so when selecting multipele user roles to edit we do not
                    // overload the server with AJAX calls
                    var initScaffoldOnTargetChangeDebounced = _.debounce( initScaffoldOnTargetChange, 1000 );

                    function initScaffoldOnTargetChange() {
                        Toolset.hooks.doAction( 'cred_editor_init_scaffold', function() {
                            var scaffold = new Toolset.CRED.ScaffoldEditor();
                            scaffold.addFieldItemsWrapperAndRow();
                        } );
                    };

                    jQuery( document ).on('change', '#cred_post_type', function () {
                        maybeAdjustGuiForRfg();
                    });

                    jQuery( function() {
                        maybeAdjustGuiForRfg();
                        initScaffoldAndEvents();
                    });

                    var view = this,
                        model = this._model;

                    // assume View is valid initially
                    view.isValid = true;

                    window.cred_wizard.createWizardDisplayInstructionSelector('#advanced-sortables');

                    view.action('refreshFormFields', function ($el, data) {
                        refreshFromFormFields();
                        gui.Popups.flash({
                            message: cred_settings.locale.refresh_done,
                            class: 'cred-dialog'
                        });
                    })
                    .action('validateSection', function ($el, data) {
                        if ($el[0] && data && undefined !== data.validationResult)
                            $el[0].__isCredValid = data.validationResult;
                    })
                    .action('fadeSlide', function ($el, data) {
                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (data['domRef'])
                            $el = jQuery(data['domRef']);

                        if (data['condition']) {
                            data['condition'] = model.eval(data['condition']);
                            if (undefined !== data['condition']) {
                                (data['condition'])
                                    ? $el.slideFadeDown('slow')
                                    : $el.slideFadeUp('slow');
                            }
                        } else
                            $el.slideFadeDown('slow', 'quintEaseOut');
                    })
                    .action('fadeIn', function ($el, data) {
                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (data['domRef'])
                            $el = jQuery(data['domRef']);

                        if (data['condition']) {
                            data['condition'] = model.eval(data['condition']);
                            if (undefined !== data['condition'])
                                (data['condition'])
                                    ? $el.stop().fadeIn('slow')
                                    : $el.stop().fadeOut('slow', function () {
                                        jQuery(this).hide();
                                    });
                        } else
                            $el.stop().fadeIn('slow');
                    })
                    // custom confirm box
                    .func('confirm', function (msg, callback) {
                        gui.Popups.confirm({
                            message: msg,
                            class: 'cred-dialog',
                            buttons: [cred_settings.locale.Yes, cred_settings.locale.No],
                            primary: cred_settings.locale.Yes,
                            callback: function (button) {
                                if ($.isFunction(callback)) {
                                    if (button == cred_settings.locale.Yes)
                                        callback.call(view, true);
                                    else
                                        callback.call(view, false);
                                }
                            }
                        });
                    })
                    // add another hook when model changes
                    .event('model:change', function (e, data) {
                        data = _.extend( { key: '', value: {}, triggerer: '' }, data );
                        var notificationID = /^\[notification\]\[notifications\]\[(\d+)\]$/.exec( data.key );
                        if ( notificationID ) {
                            var notificationBodyEditorId = 'credmailbody' + notificationID[1];
                            switch ( data.triggerer ) {
                                case 'addItem':
                                    Toolset.hooks.doAction( 'cred_editor_init_codemirror', notificationBodyEditorId );
                                    Toolset.hooks.doAction( 'cred_editor_refresh_codemirror', notificationBodyEditorId );
                                    OTGSUI.otgsPopoverTooltip.initialize();
                                    break;
                                case 'removeItem':
                                    Toolset.hooks.doAction( 'cred_editor_destroy_codemirror', notificationBodyEditorId );
                                    break;
                            }
                        }
                        // display validation messages per section
                        validateView();
                    })
                    // add another hook when view changes
                    .event('view:change', function (e, data) {
                            // display validation messages per section
                            validateView();
                        });

                    function validateView() {
                        // display validation messages per section
                        view.isValid = true;
                        // use caching here
                        view.getElements('.cred_validation_section').each(function () {
                            var $this = jQuery(this);
                            isValid = true;

                            $this.find('input, select, textarea').each(function () {
                                var $this2 = jQuery(this);
                                if (undefined !== $this2[0].__isCredValid) {
                                    if (!$this2[0].__isCredValid)
                                        isValid = false;
                                }
                            });

                            $this.find('input').each(function () {
                                if (jQuery(this).val() == 'author' && jQuery(this).is(':checked')) {
                                    isValid = true;
                                }
                            });

                            if (!isValid) {
                                view.isValid = false;
                                //Attention: added delay action for preventing loss of click after change event processing (case of notifications alert messages)
                                jQuery('.cred-notification.cred-error.cred-section-validation-message', $this).delay(100).show(0);
                            } else {
                                //Attention: added delay action for preventing loss of click after change event processing (case of notifications alert messages)
                                jQuery('.cred-notification.cred-error.cred-section-validation-message', $this).delay(100).hide(0);
                            }
                        });
                    }

                    //Load fields when adding new notification
                    jQuery('#cred-notification-add-button').on('click', function () {
                        refreshFromFormFields();
                    });
                    //Load fields when notifications are loaded
                    jQuery('.cred-notification-edit').on('click', function () {
                        refreshFromFormFields();
                    });

                    /**
                     * Refresh the model with fields from the content.
                     *
                     * @since unknown
                     */
                    function refreshFromFormFields() {

                        // Refresh the editor content first if on d&d mode
                        var isHTMLSelected = jQuery( '#cred-editor-html' ).is( ':visible' );
                        if ( ! isHTMLSelected ) {
                            Toolset.hooks.doAction( 'cred_editor_insert_scaffold' );
                        }

                        Toolset.hooks.doAction( 'cred-action-maybe-request-and-operate-on-object-fields',
                            function( cachedFields ) {
                                var extendedFields = $.extend( true, {}, cachedFields );
                                extendedFields = self.processFieldsAndAddGeneric( extendedFields );

                                var persistentFields = self.gatherPersistentFields( extendedFields );

                                model
                                    .set( '[_persistent_mail_fields]', persistentFields.mail )
                                    .set( '[_persistent_user_id_fields]', persistentFields.userId )
                                    .set( '[_persistent_text_fields]', persistentFields.text )
                                    .set( '[_persistent_select_fields]', persistentFields.select )
                                    .set( '[_all_persistent_meta_fields]', [].concat( persistentFields.allMetaFields ) )
                                    .trigger( 'change' );
                        });
                    }

                    // add custom events callbacks
                    /**
                     * @todo Review this, as it is not triggered anymore
                     */
                    //utils.attach('cred.fieldsLoaded cred.insertField cred.insertScaffold', refreshFromFormFields);
                    utils.attach('cred.wizardFinished', function () {
                        // trigger scroll event to fix toolbar buttons being shown in wrong spot on chrome.
                        var _scroll_y = jQuery(window).scrollTop();
                        if (_scroll_y === 0) {
                            jQuery("html, body").animate({scrollTop: 1});
                        } else {
                            jQuery("html, body").animate({scrollTop: 0});
                        }

                        aux.checkCredFormType();

                    });

                    // handle tooltips with pointers
                    $_post
                        .on('paste keyup change', '.js-test-notification-to', function (e) {
                            //e.preventDefault();
                            var $el = jQuery(this), val = $el.val(), $but = jQuery($el.data('sendbutton'));

                            if ('' == val) {
                                $but.attr('disabled', 'disabled');
                            } else {
                                $but.removeAttr('disabled');
                            }
                        })
                        .on('click', '.js-send-test-notification', function (e) {

                            e.preventDefault();

                            var $el = jQuery(this),
                                xhr = null, notification = {}, data,
                                to = jQuery($el.data('addressfield')).val().trim(),
                                cancel = jQuery($el.data('cancelbutton')),
                                loader = jQuery($el.data('loader')),
                                resultsCont = jQuery($el.data('results')).empty().hide(),
                                form_id = jQuery('#post_ID').val(), fromCancel = false
                            ;

                            // nowhere to send
                            if ('' == to)
                                return false;

                            var doFinish = function () {
                                if (xhr) {
                                    xhr.abort();
                                    xhr = false;
                                }
                                cancel.off('click', doFinish);
                                $el.removeAttr('disabled');
                            };

                            var notificationIndex = $el.data('notification'),
                                notificationId = 'credmailbody' + notificationIndex,
                                notificationBody = Toolset.hooks.applyFilters( 'cred_editor_get_codemirror_content', '', notificationId );

                                model.set( '[notification][notifications][' + notificationIndex + '][mail][body]', notificationBody, true );

                            notification = $.extend(notification, model.get('[notification][notifications][' + notificationIndex + ']'));
                            delete notification['event'];
                            notification['to']['type'] = ['specific_mail'];
                            notification['to']['specific_mail']['address'] = to;
                            data = {
                                'cred_test_notification_data': notification,
                                'cred_test_notification_form_id': form_id
                            };

                            // send it
                            cancel.off('click', doFinish).on('click', doFinish);
                            $el.attr('disabled', 'disabled');
                            resultsCont.html('sending test notification to &quot;' + to + '&quot; ..').show();
                            loader.show();

                            xhr = $.ajax(self.route('/Forms/testNotification'), {
                                data: data,
                                dataType: 'json',
                                type: 'POST',
                                success: function (result) {
                                    if (result.error) {
                                        resultsCont.html('<div class="cred-error">' + result.error + '</div>');
                                    } else {
                                        resultsCont.html('<div class="cred-success">' + result.success + '</div>');
                                    }
                                    resultsCont.hide().fadeIn('slow');
                                    loader.hide();
                                    xhr = false;
                                    doFinish();
                                },
                                error: function (xhr1) {
                                    loader.hide();
                                    resultsCont.empty().hide();

                                    gui.Popups.alert({
                                        message: 'AJAX Request failed!<br /><br />Response Code: ' + xhr1.status + '<br /><br />Response Message: ' + xhr1.responseText,
                                        class: 'cred-dialog'
                                    });

                                    xhr = false;
                                    doFinish();
                                }
                            });
                        });

                    /**
                     * _do_submit_compatibility
                     *
                     * Compatibility solutions before submitting the Toolset Forms edit page form.
                     *
                     * @since 1.8.1
                     */

                    var _do_submit_compatibility = function () {

                        /**
                         * ACF compatibility
                         *
                         * ACF hijacks the post form submit, performs some validation, and then it should submit the form, but it does not
                         * Because of that, we need to disable the ACF validation on Toolset Forms edit pages.
                         *
                         * @since 1.8.1
                         */

                        acf = (typeof acf != 'undefined') ? acf : {};
                        acf.validation = acf.validation || {};
                        acf.validation.active = 0;

                    };


                    var _do_submit = function () {
                        var form_name_1 = jQuery('#title').val();
                        if (form_name_1.trim() == '') {
                            gui.Popups.alert({message: cred_settings.locale.set_form_title, class: 'cred-dialog'});
                            return false;
                        }

                        if (jQuery('input[name="_cred[form][type]"]') && jQuery('input[name="_cred[form][type]"]').length > 0) {
                            var form_form_type = aux.getFormType();
                            if (form_form_type.trim() == '') {
                                gui.Popups.alert({
                                    message: cred_settings.locale.post_type_missing,
                                    class: 'cred-dialog'
                                });
                                return false;
                            }
                        }

                        if (jQuery('#cred_post_type') && jQuery('#cred_post_type').length > 0) {
                            var form_post_type = jQuery('#cred_post_type').val();
                            if (form_post_type.trim() == '') {
                                gui.Popups.alert({
                                    message: cred_settings.locale.post_type_missing,
                                    class: 'cred-dialog'
                                });
                                return false;
                            }
                        }

                        if (jQuery('#cred_post_status') && jQuery('#cred_post_status').length > 0) {
                            var form_post_status = jQuery('#cred_post_status').val();
                            if (form_post_status.trim() == '') {
                                gui.Popups.alert({
                                    message: cred_settings.locale.post_status_missing,
                                    class: 'cred-dialog'
                                });
                                return false;
                            }
                        }

                        if (jQuery('#cred_form_success_action') && jQuery('#cred_form_success_action').length > 0) {
                            var form_post_action = jQuery('#cred_form_success_action').val();
                            if (form_post_action.trim() == '') {
                                gui.Popups.alert({
                                    message: cred_settings.locale.post_action_missing,
                                    class: 'cred-dialog'
                                });
                                return false;
                            }
                        }

                        if (self.doCheck()) {
                            var nbox = jQuery('#crednotificationdiv');

                            // notification metabox is closed
                            if (nbox.hasClass('closed')) {
                                // open it and save it
                                nbox.removeClass('closed');

                                // make view re-validate
                                view.forceValidation();
                                validateView();

                                if (view.isValid) {
                                    // close it and save it
                                    nbox.addClass('closed');
                                }
                            }

                            if (!view.isValid)
                                $_post.append('<input style="display:none" type="hidden" id="_cred_form_not_valid" name="_cred[validation][fail]" value="1" />');
                            else
                                jQuery('#_cred_form_not_valid').remove();
                            return true;
                        }
                        return false;
                    };

                    $_post.on('submit', function (event) {
                        _do_submit_compatibility();
                        return _do_submit();
                    });

                    // chain it
                    return this;
                }
            });
            //cred settings
            settingsPage = cred_settings.settingsurl;

            /*
             *
             *  ===================== init user interaction/bindings ================================
             *
             */
            // init View to handle user interaction and bindings
            _credView
                .init()
                .autobind(true)                      // autobind input fields, with model keys as names, to model
                .bind(['change', 'click'], '#post')  // bind view to 'change' and 'click' events to elements under '#post'
                .sync();                             // synchronize view to model

            doinit = false;
        }
    };

    // make public methods/properties available
    if (window.hasOwnProperty("cred_cred"))
        jQuery.extend(window.cred_cred, self);
    else
        window.cred_cred = self;

})(window, jQuery, cred_settings, cred_utils, cred_gui, cred_mvc);

function change_notification_to_info(item, data) {
    if (data["domRef"] == "#cred_notification_settings_panel_container") {
        var item = jQuery(item);
        var notification_message = item.find(".cred-notification");
        jQuery(notification_message).addClass("hidden");

        item.find(".fa .fa-warning").each(function () {
            jQuery(this).addClass("hidden");
        });
    }
}

function check_cred_form_type_for_notification() {
    var _cred_form_type_obj = jQuery('input[name="_cred[form][type]"]');
    if (_cred_form_type_obj) {
        var _cred_form_type_selected = jQuery('input[name="_cred[form][type]"]:checked').val();
        if ('new' == _cred_form_type_selected) {
            jQuery('.cred_notification_field_only_if_changed input[type=checkbox]').attr('disabled', 'disabled');
            jQuery('.cred_notification_field_only_if_changed').hide();
            if (jQuery('.when_submitting_form_text').length) {
                jQuery('.when_submitting_form_text').html('When a new user is created by this form');
            }
        } else {
            jQuery('.cred_notification_field_only_if_changed').show();
            jQuery('.cred_notification_field_only_if_changed input[type=checkbox]').removeAttr('disabled');
            if (jQuery('.when_submitting_form_text').length) {
                jQuery('.when_submitting_form_text').html('When a user is updated by this form');
            }
        }
    }
}


jQuery(function ($) {

    //add event listener for all cred-label clicks
    jQuery('.cred-label').click(function (evt) {
        if (jQuery(this).find('.cred-shortcode-container-radio').length > 0) {
            jQuery(this).find('.cred-shortcode-container-radio').trigger('click');
        }
    });

    //Expand toolset menu on form add/edit post
    if (window.hasOwnProperty('typenow') && window.typenow == 'cred-form' || window.typenow == 'cred-user-form') {
        jQuery('li.toplevel_page_toolset-dashboard').addClass('wp-has-current-submenu wp-menu-open menu-top menu-top-first').removeClass('wp-not-current-submenu');
    }

    //check if cred wizard is enabled and show or hide the preview button
    if (!window.hasOwnProperty('cred_wizard')) {
        jQuery('.cred-wizard-preview-button').remove();
    }
});
