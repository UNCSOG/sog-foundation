(function (window, $, settings, cred, gui, undefined) {
    // private methods/properties
    var self, edit_url = cred.settings.editurl, form_controller_url = cred.settings.form_controller_url,
        wizard_url = cred.settings.wizard_url, _cred_wpnonce = cred.settings._cred_wpnonce, newform;
    var _current_page = cred.settings._current_page;

    var wizardNextB, wizardPrevB, wizardQuitB, wizardProgressbar, wizardProgressbarInner;

// public methods/properties
    self = {
        hasSidebar: false,
        step: 1,
        prevstep: 0,
        completed_step: 0,
        steps: [
            //step 1
            {
                title: cred.settings.locale.step_1_title,
                completed: false,
                execute: function (prev) {
                    // setup
                    $('#post-body-content, #titlediv').children(':not(.cred-not-hide)').hide();
                    $('#postbox-container-2 #normal-sortables').children(':not(.cred-not-hide)').hide();
                    wizardPrevB.hide();

                    self.removeWizardDisplayInstructionsSelector();
                    self.createWizardDisplayInstructionSelector('#post-body-content');
                    self.completeCurrentStep();
                }
            },
            // step 2
            {
                title: cred.settings.locale.step_2_title,
                completed: false,
                execute: function () {
                    self.removeWizardDisplayInstructionsSelector();

                    // setup
                    $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                    $('#titlediv').show().children().show();
                    wizardPrevB.show();

                    if (!self.steps[self.step - 1].completed) {
                        wizardNextB.attr('disabled', 'disabled');
                        self.checkClassButton(wizardNextB);
                        // keep checking
                        var _tim = setInterval(function () {
                            if (!self.steps[self.step - 1].completed) {
                                var $el = $('#title'), val = $el.val().trim();
                                if ('' !== val) {
                                    clearInterval(_tim);
                                    self.completeCurrentStep();
                                }
                            } else {
                                clearInterval(_tim);
                            }
                        }, 500);
                    } else {
                        self.completeCurrentStep();
                    }
                }
            },
            // step 3
            {
                title: cred.settings.locale.step_3_title,
                completed: false,
                execute: function (prev) {
                    // setup
                    self.removeWizardDisplayInstructionsSelector();
                    $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                    $('#credformtypediv').removeClass('closed').show();
                    wizardPrevB.show();

                    if (!self.steps[self.step - 1].completed) {
                        wizardNextB.attr('disabled', 'disabled');
                        self.checkClassButton(wizardNextB);
                        // keep checking
                        var _tim = setInterval(function () {
                            if (!self.steps[self.step - 1].completed) {
                                var val = '', val2 = '', val3 = '', val4 = '';
                                var $el = $('input[name="_cred[form][type]"]:checked'), val = $.trim($el.val());
                                var is_user_form = $('#cred_form_user_role').length;
                                var $el2;

                                if (is_user_form) {
                                    if (val === 'edit') {
                                        $('input[name="_cred[form][user_role][]"]:checked').each(function () {
                                            val2 += $(this).val() + ",";
                                        });
                                        val2 = val2.replace(/,(\s+)?$/, '');
                                    } else {
                                        $el2 = $('select[name="_cred[form][user_role][]"]'), val2 = $.trim($el2.val());
                                    }
                                    var $el3 = $('select[name="_cred[form][action]"]'), val3 = $.trim($el3.val());
                                } else {
                                    $el2 = $('select[name="_cred[post][post_type]"]'), val2 = $.trim($el2.val());
                                    var $el3 = $('select[name="_cred[post][post_status]"]'), val3 = $.trim($el3.val());
                                    var $el4 = $('select[name="_cred[form][action]"]'), val4 = $.trim($el4.val());
                                }

                                if (
                                    (!is_user_form && '' !== val && '' !== val2 && '' !== val3 && '' !== val4)
                                    || (is_user_form && '' !== val && '' !== val2 && '' !== val3)
                                ) {
                                    clearInterval(_tim);
                                    self.completeCurrentStep();
                                }
                            } else {
                                clearInterval(_tim);
                            }
                        }, 500);
                    } else {
                        var completed = true;
                        jQuery('select[name^=_cred]:visible').each( function() {
                            completed &= !! this.value;
                        } );
                        if ( ! completed ) {
                            self.steps[self.step - 1].completed = false;
                            self.steps[self.step - 1].execute();
                        } else {
                            self.completeCurrentStep();
                        }
                    }
                }
            },
            // step 4
            {
                title: cred.settings.locale.step_4_title,
                completed: false,
                execute: function (prev) {
                    self.removeWizardDisplayInstructionsSelector();
                    $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                    $('#credformcontentdiv').removeClass('closed').show();
                    wizardPrevB.show();
                    if (!self.steps[self.step - 1].completed) {
                        wizardNextB.removeAttr('disabled');
                        self.checkClassButton(wizardNextB);
                        // keep checking
                        var _tim = setInterval(function () {
                            if (!self.steps[self.step - 1].completed) {
                                var content = cred.getContents(), val = $.trim(content['content']);
                                if ('' != val) {
                                    clearInterval(_tim);
                                    self.completeCurrentStep();
                                }
                            } else {
                                clearInterval(_tim);
                            }
                        }, 500);
                    } else {
                        self.completeCurrentStep();
                    }
                }
            },
            // step 5
            {
                title: cred.settings.locale.step_5_title,
                completed: false,
                execute: function (prev) {
                    self.removeWizardDisplayInstructionsSelector();
                    $('#postbox-container-2 #normal-sortables, #post-body-content').children(':not(.cred-not-hide)').hide();
                    $('#crednotificationdiv').removeClass('closed').show();
                    wizardPrevB.show();

                    self.completeCurrentStep();
                }
            }
        ],
        prevStep: function () {
            self.goToStep(self.step - 1);
            self.removeActiveClass( self.step );
        },
        nextStep: function () {
			if ( 4 === self.step ) {
				// Stepping away from the editor step:
				// close options so changes are 'saved'
				jQuery( '.js-cred-editor-scaffold-item-options:visible .js-cred-editor-scaffold-options-close' ).click();
			}
            if (5 === self.step || newform && 2 === self.step && !self.steps[2].completed) {
                var form_id = $('#post_ID').val();
                if (cred.doCheck(self.step)) {
                    jQuery( '.cred-wizard-buttons .spinner' ).show().css({visibility: 'visible'}); // WP 4.9 needs css(), WP 5.0 show()
                    /**
                     * Before sending the form to the server, it needs to save scaffold data into the form.
                     * That actions are handled by this hook
                     *
                     * @since 2.2
                     */
                    Toolset.hooks.doAction( 'cred_editor_scaffold_pre_submit_form' );
                    $.ajax({
                        url: edit_url,
                        data: self.serialize('#post'),
                        type: 'post',
                        success: function (result) {
                            $.ajax({
                                url: cred.route(form_controller_url),
                                data: 'form_id=' + form_id + '&field=wizard' + '&value=' + self.completed_step + '&_wpnonce=' + _cred_wpnonce,
                                type: 'post',
                                success: function (result) {
                                    if ( 5 === self.step ) {
                                        // Submiting the form not using Ajax so saving notice will be displayed
                                        // @todo rebuild the whole wizard process
                                        var $form = jQuery( '#post' );
                                        var extraParameters = JSON.parse(
                                            '{"' + decodeURI(
                                                ( '_cred[wizard]=' + wizard_step + dataString ) // extra data serialized
                                                    .replace( /&/g, "\",\"" ) // group fields and values by quotes
                                                    .replace( /=/g, "\":\"" ) ) + // replace = by :
                                            '"}'
                                        );
                                        Object.keys( extraParameters ).forEach( function( key ) {
                                            $form.append( '<input type="hidden" name="' + key + '" value="' + extraParameters[ key ] + '" />' );
                                        } );
                                        $form.submit();
                                    } else {
                                        // Make sure that redirecting to the form edit page is not blocked
                                        $( window ).off( 'beforeunload' );
                                        document.location = edit_url + '?action=edit&post=' + form_id;
                                    }
                                }
                            });
                        }
                    });
                }
            } else {
                // save this step
                if (cred.doCheck(self.step)) {
                    var wizard_step = self.completed_step;
                    if (self.completed_step === self.steps.length) {
                        wizard_step = -1;
                    }

                    var dataString = '',
                        actionMessage = Toolset.hooks.applyFilters( 'cred_editor_get_codemirror_content', '', 'credformactionmessage' );

                    if ( actionMessage != '') {
                        dataString += '&_cred[form][action_message]=' + encodeURIComponent( actionMessage );
                    }

                    $( '.js-cred-notification-body' ).each( function() {
                        var $notiicationEditor = $( this ),
                            notificationEditorId = $notiicationEditor.attr( 'id' ),
                            notificationBody = Toolset.hooks.applyFilters( 'cred_editor_get_codemirror_content', '', notificationEditorId ),
                            notificationIndex = notificationEditorId.substring( 'credmailbody'.length );
                        if ( notificationBody != '' ) {
                            dataString += '&_cred[notification][notifications][' + notificationIndex + '][mail][body]=' + encodeURIComponent( notificationBody );
                        }
                    });

                    $.ajax({
                        url: document.location,
                        data: self.serialize('#post') + '&_cred[wizard]=' + wizard_step + dataString,
                        type: 'post',
                        success: function () {
                        }
                    });

                    self.goToStep(self.step + 1);
                }
            }
        },
        submithandler:function(event) {
            event.preventDefault();
            var post = $(this);
            var form_id = $('#post_ID').val();
            if (cred.doCheck(self.step)) {
                $.ajax({
                    url: cred.route(form_controller_url),
                    data: 'form_id=' + form_id + '&field=wizard' + '&value=' + self.completed_step + '&_wpnonce=' + _cred_wpnonce,
                    type: 'post',
                    success: function (result) {
                        post.off('submit', self.submithandler);
                        post.submit();
                        return true;
                    }
                });
            }
            return false;
        },
        serialize: function(what) {
            var values, index, newvals = cred.getContents();

            // Get the parameters as an array
            values = $(what).serializeArray();

            // Find and replace `content` if there
            for (index = 0; index < values.length; ++index) {
                if (newvals[values[index].name]) {
                    values[index].value = newvals[values[index].name];
                }
            }
            // Convert to URL-encoded string
            return $.param(values);
        },
        checkClassButton: function($button) {
            if ('disabled' === $button.attr('disabled')) {
                if ($button.hasClass('button-primary')) {
                    $button.removeClass('button-primary').addClass('button-secondary');
                }
            } else {
                if ($button.hasClass('button-secondary')) {
                    $button.removeClass('button-secondary').addClass('button-primary');
                }
            }
        },
        completeCurrentStep: function() {
            self.steps[self.step - 1].completed = true;
            self.completed_step = self.step;
            wizardNextB.removeAttr('disabled');
            self.checkClassButton(wizardNextB);
        },
        goToStep: function (step) {

            //Reinitialize tippy.js
            OTGSUI.otgsPopoverTooltip.initialize();

            if (undefined === step) {
                return;
            }
            step = parseInt(step, 10);
            if (step <= self.steps.length + 1
                && step >= 1
            ) {
                self.step = step;
            } else {
                return;
            }

            if (self.step === self.steps.length + 1) {
                self.finish( 'finish' );
                return;
            }

            if (self.steps[self.step - 1] && typeof self.steps[self.step - 1].execute === "function" ) {
                if (self.step === self.steps.length) {
                    wizardNextB.val(cred.settings.locale.finish_text).show();
                } else {
                    wizardNextB.val(cred.settings.locale.next_text).show();
                }
                self.steps[self.step - 1].execute();
                cred.app.dispatch('cred.wizardChangedStep');
                self.setTab(step-1);
                return;
            }
        },
        setTab: function(i) {
            self.setActiveClass( i );
        },
        resetTabs: function() {
            for (var i = 0, l = self.steps.length; i < l; i++) {
                self.removeActiveClass( i );
            }
        },
        start: function () {
            var $post_selector = $('#post');
            // setup
            $('#post-body').prepend('<div class="cred-not-hide cred-exit-wizard-button"> <input id="cred_wizard_quit_button" type="button" class="button button-small" value="' + cred.settings.locale.quit_wizard_text + '" /></div>');
            $('#postbox-container-2').append(
                '<div class="cred-not-hide cred-wizard-buttons">' +
                    '<input type="button" id="cred-wizard-button-prev" class="button-wizard button button-secondary button-wizard-prev" value="' + cred.settings.locale.prev_text + '" /> ' +
                    '<input type="button" id="cred-wizard-button-next" class="button-wizard button button-primary-toolset button-wizard-next" value="' + cred.settings.locale.next_text + '" /> ' +
                    '<span class="spinner"></span>' +
                '</div>'
            );
            // Wizard title must be wrapper in order to look like Relationship wizard
            var wrapperLayout = wp.template( 'cred-wizard-title-wrapper' )({});
            var $titleWrap = jQuery( '#titlewrap' );
            var $titleElements = $titleWrap.children();
            $titleWrap.append( wrapperLayout );
            $titleWrap.find( '#js-cred-wizard-title-wrapper-input' ).append( $titleElements );
            // progress bar
            //$('#post-body-content').prepend('<div class="cred-not-hide cred-progress"><div id="cred-progress-bar"><div id="cred-progress-bar-inner"></div></div></div>');
            $('#post-body-content').prepend('<div id="cred-wizard-association-forms-wrap" class="cred-not-hide cred-progress"><ol id="cred-progress-bar" class="cred-wizard-steps"></ol></div>');
            if ($('#post-body').hasClass('columns-2')) {
                self.hasSidebar = true;
                $('#postbox-container-1').hide();
                $('#post-body').removeClass('columns-2').addClass('columns-1');
            }
            $('#cred-submit, #cred_add_forms_to_site_help').hide();
            $post_selector.on('submit', self.submithandler);

            wizardNextB = $('#cred-wizard-button-next');
            wizardPrevB = $('#cred-wizard-button-prev');
            wizardQuitB = $('#cred_wizard_quit_button');
            //wizardProgressbar = $('#cred-progress-bar');
            wizardProgressbar = $('#cred-progress-bar');
            //wizardProgressbarInner = $('#cred-progress-bar-inner');

            /*$(wizardPrevB).click(function () {
                wizardProgressbarInner.css('width', (100 * (self.step - 1) / self.steps.length) + '%');
            });*/

            /*$(wizardNextB).click(function () {
                wizardProgressbarInner.css('width', (100 * (self.step + 1) / self.steps.length) + '%');
            });*/

            for (var i = 0, l = self.steps.length; i < l; i++) {
                var classActive = "";
                if (i === 0) {
                    classActive = "active";
                }

                //var progress_step = $('<span class="cred-progress-step">' + self.steps[i].title + '</span>');
                var progress_step = $('<li id="cred-wizard-step-' + i + '" data-content="cred-wizard-step-' + i + '" class="cred-progress-step ' + classActive + '" data-bind="css: stepFormNameClass" class="cred-progress-step">' + self.steps[i].title + '</li>');
                //progress_step.insertBefore(wizardProgressbar).css({
                wizardProgressbar.append(progress_step);
                /*.css({
                    'left': Math.floor(100 * (i + 1) / l) + '%',
                    'margin-left': -progress_step.width() + 'px',
                    'white-space': 'nowrap'
                });*/
            }

            $('#title').on("keydown", function (evt) {
                if ( evt.key === "Enter" ) {
                    evt.preventDefault();
                    self.nextStep();
                }
            });

            $post_selector.on('click', '#cred_wizard_quit_button', function () {
                var form_id = $('#post_ID').val();

                cred.gui.Popups.confirm({
                    'class': 'cred-dialog',
                    'message': cred.settings.locale.quit_wizard_confirm_text,
                    'buttons': [cred.settings.locale.quit_wizard_this_form, cred.settings.locale.quit_wizard_all_forms, cred.settings.locale.cancel_text],
                    'callback': function (result) {
                        if (result === cred.settings.locale.quit_wizard_all_forms) {
                            $.ajax({
                                url: cred.route(wizard_url),
                                type: 'POST',
                                data: 'cred_wizard=false',
                                dataType: 'html',
                                success: function () {
                                }
                            });
                        }
                        if (result === cred.settings.locale.quit_wizard_this_form) {
                            $.ajax({
                                url: cred.route(form_controller_url),
                                data: 'form_id=' + form_id + '&field=wizard' + '&value=-1&_wpnonce=' + _cred_wpnonce,
                                type: 'post',
                                success: function () {
                                }
                            });
                        }
                        if (result === cred.settings.locale.quit_wizard_all_forms
                            || result === cred.settings.locale.quit_wizard_this_form) {
                            $post_selector.off('submit', self.submithandler);
                            self.finish( 'exit' );
                        }
                    }
                });
            });

            $post_selector.on('click', '#cred-wizard-button-next', function () {
                self.nextStep();
            });
            $post_selector.on('click', '#cred-wizard-button-prev', function () {
                self.prevStep();
            });

            // go
            for (var i = 1; i <= self.step; i++) {
                if (self.steps[i - 1])
                    self.steps[i - 1].completed = true;
            }
            self.completed_step = self.step;
            //wizardProgressbarInner.css('width', (100 * ((self.step + 1) / self.steps.length)) + '%');
            if (self.step < self.steps.length) {
                self.goToStep(self.step + 1);
            }

            //show preview button on wizard
            $('.cred-wizard-preview-button').show();
        },
        finish: function ( finishFrom ) {
            self.completed_step = -1;
            self.showEverything();
            cred.app.dispatch('cred.wizardFinished');
            self.removeWizardDisplayInstructionsSelector();
            self.createWizardDisplayInstructionSelector('#advanced-sortables');

            // show success message only if when exiting from wizard by finishing last step
            if( finishFrom === 'finish' ){
                self.displaySuccessMessage();
            }

            Toolset.hooks.doAction( 'cred_editor_wizard_finished' );
        },
        displaySuccessMessage: function () {

            var form_id = $('#post_ID').val();

            jQuery('.wp-header-end').after(
                '<div class="notice is-dismissible notice-success js-wizard-success-msg">' +
                '<p>'+cred.settings.locale.form_created_using_wizard+' '+form_id+'</p>' +
                '<button class="notice-dismiss" id="js-remove-wizard-success-message"></button>' +
                '</div>'
            );

            jQuery('#js-remove-wizard-success-message').click(function(){
                jQuery('.js-wizard-success-msg').fadeOut("normal", function() {
                    $(this).remove();
                });
            });

        },
        showEverything: function() {
            $('#post').find('.cred-not-hide')/*.hide()*/.remove();
            if (self.hasSidebar) {
                $('#post-body').removeClass('columns-1').addClass('columns-2');
                $('#postbox-container-1').show();
                self.hasSidebar = false;
            }
            $('#postbox-container-2 #normal-sortables, #post-body-content').children().show();
            $('#postbox-container-2 #normal-sortables').show();
            $('#titlediv').children().show();
            $('#titlediv').show();
            $('#cred-submit, #cred_add_forms_to_site_help, .cred_related').show();
            if ($('#credformcontentdiv').hasClass('closed')) {
                $('h3.hndle', '#credformcontentdiv').triggerHandler('click');
            }
            //hide preview button on wizard
            jQuery('.cred-wizard-preview-button').remove();
        },
        //Try to remove wizard selector
        createWizardDisplayInstructionSelector: function ($after_where) {
            var $wizard_instructions_selector = $('#wizard-instructions');
            if ($wizard_instructions_selector && $wizard_instructions_selector.length) {
                return;
            }
            var is_user_form = (cred.settings._current_page === 'cred-user-form');
            var template_to_display = (is_user_form) ? cred.settings.wizard_instructions_user_content : cred.settings.wizard_instructions_post_content;

            var completed_step = parseInt(self.completed_step, 10)
            var steps_length = parseInt(self.steps.length, 10)
            var step = parseInt(self.step, 10)
            var is_wizard_enabled = parseInt(cred.settings.is_wizard_enabled, 10)
            if (0 === is_wizard_enabled
                || ((step === 1 || step === steps_length-1 || completed_step === -1) && is_wizard_enabled === 1)
            ) {
                $($after_where).after(template_to_display);
            }
        },
        setActiveClass: function ( stepID ) {
            var id = 'cred-wizard-step-'+stepID;
            $('#'+id).addClass('active');
        },
        removeActiveClass: function ( stepID ) {
            var id = 'cred-wizard-step-'+stepID;
            $('#'+id).removeClass('active');
        },
        //Remove the wizard selector if exists
        removeWizardDisplayInstructionsSelector: function () {
            var $wizard_instructions_selector = $('#wizard-instructions');
            if ($wizard_instructions_selector && $wizard_instructions_selector.length) {
                $wizard_instructions_selector.remove();
            }
        },
        init: function (step, new_form) {
            // save data
            newform = new_form;
            if (step >= 0) {
                self.step = step;
                self.start();
            }

            if(step === 2 && new_form === false){
                self.setActiveClass(1);
            }

            jQuery('select[required]').on( 'change', function() {
                if ( ! this.value ) {
                    self.steps[self.step - 1].completed = false;
                    self.steps[self.step - 1].execute();
                }
            } );
        }
    };

    // make it publicly available
    window.cred_wizard = self;

})(window, jQuery, cred_settings, cred_cred, cred_gui);
