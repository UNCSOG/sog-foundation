/******/ (function() { // webpackBootstrap
/*!***********************************!*\
  !*** ./js/src/plugin_settings.js ***!
  \***********************************/
/* global jQuery, gform, grecaptcha, gforms_recaptcha_recaptcha_strings */

(function ($) {
  /**
   * Handles reCAPTCHA v2 plugin settings validation.
   *
   * @since 1.0
   *
   * @return {void}
   */
  var recaptchaV2Settings = function recaptchaV2Settings() {
    var v2Settings = {};

    /**
     * Initialize reCAPTCHA v2 settings.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v2Settings.init = function () {
      v2Settings.cacheElements();
      v2Settings.addEventListeners();
    };

    /**
     * Cache the fields used by this handler.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v2Settings.cacheElements = function () {
      v2Settings.container = $('div[id="gform_setting_reset_v2"]');
      v2Settings.fields = {
        siteKey: $('input[name="_gform_setting_site_key_v2"]'),
        secretKey: $('input[name="_gform_setting_secret_key_v2"]'),
        reset: $('input[name="_gform_setting_reset_v2"]'),
        type: $('input[name="_gform_setting_type_v2"]')
      };
    };

    /**
     * Add event listeners for this handler.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v2Settings.addEventListeners = function () {
      v2Settings.fields.siteKey.on('change', window.loadRecaptcha);
      v2Settings.fields.secretKey.on('change', window.loadRecaptcha);
      v2Settings.fields.type.on('change', function () {
        return window.loadRecaptcha();
      });
    };

    /**
     * Handles showing and hiding the reCAPTCHA itself.
     *
     * @since 1.0
     *
     * @return {void}
     */
    window.loadRecaptcha = function () {
      var self = {};

      /**
       * Initialize the reCAPTCHA rendering process.
       *
       * @since 1.0
       *
       * @return {void}
       */
      self.init = function () {
        v2Settings.recaptcha = $('#recaptcha');
        v2Settings.save = $('#gform-settings-save');
        self.flushExistingState();

        // Reset key status.
        // Note: recaptcha is misspelled here for legacy reasons.
        $('#recpatcha .gform-settings-field__feedback').remove();

        // If no public or private key is provided, exit.
        if (!self.canBeDisplayed()) {
          self.hideRecaptcha();
          return;
        }
        self.showSelectedRecaptcha();
      };

      /**
       * Renders the v2 reCAPTCHA.
       *
       * @since 1.0
       *
       * @param {string} typeValue The selected type to render.
       *
       * @return {void}
       */
      self.render = function (typeValue) {
        // Render reCAPTCHA.
        grecaptcha.render('recaptcha', {
          sitekey: v2Settings.fields.siteKey.val().trim(),
          size: typeValue === 'invisible' ? typeValue : '',
          badge: 'inline',
          'error-callback': function errorCallback() {},
          callback: function callback() {
            return v2Settings.save.prop('disabled', false);
          }
        });
      };

      /**
       * Flush the existing state of the reCAPTCHA handler.
       *
       * @since 1.0
       *
       * @return {void}
       */
      self.flushExistingState = function () {
        window.___grecaptcha_cfg.clients = {};
        window.___grecaptcha_cfg.count = 0;
        v2Settings.recaptcha.html('');
        v2Settings.fields.reset.val('1');
      };

      /**
       * Determines whether the reCAPTCHA can be shown.
       *
       * @since 1.0
       *
       * @return {boolean} Whether the reCAPTCHA can be shown.
       */
      self.canBeDisplayed = function () {
        return v2Settings.fields.siteKey.val() && v2Settings.fields.secretKey.val();
      };

      /**
       * Hides the reCAPTCHA element.
       *
       * @since 1.0
       *
       * @return {void}
       */
      self.hideRecaptcha = function () {
        v2Settings.save.prop('disabled', false);
        v2Settings.container.hide();
      };

      /**
       * Show the selected reCAPTCHA type.
       *
       * @since 1.0
       *
       * @return {void}
       */
      self.showSelectedRecaptcha = function () {
        var typeValue = $('input[name="_gform_setting_type_v2"]:checked').val();
        if (!typeValue) {
          return;
        }
        self.render(typeValue);
        switch (typeValue) {
          case 'checkbox':
            $('#gforms_checkbox_recaptcha_message, label[for="reset"]').show();
            break;
          case 'invisible':
            $('#gforms_checkbox_recaptcha_message, label[for="reset"]').hide();
            break;
          default:
            throw new Error('Unexpected type selected.');
        }
        v2Settings.container.show();
        if (typeValue === 'invisible') {
          grecaptcha.execute();
        }
      };
      self.init();
    };
    v2Settings.init();
  };

  /**
   * Handles reCAPTCHA v3 plugin settings validation.
   *
   * @since 1.0
   *
   * @return {void}
   */
  var recaptchaV3Settings = function recaptchaV3Settings() {
    var v3Settings = {};

    /**
     * Initializes the reCAPTCHA v3 settings handler.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v3Settings.init = function () {
      v3Settings.token = '';
      v3Settings.strings = gforms_recaptcha_recaptcha_strings;
      v3Settings.cacheElements();
      v3Settings.validateKeysV3();
      v3Settings.addEventListeners();
    };

    /**
     * Cache HTML elements for the v3 reCAPTCHA settings.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v3Settings.cacheElements = function () {
      v3Settings.fields = {
        siteKey: '#site_key_v3',
        secretKey: '#secret_key_v3',
        threshold: '#score_threshold_v3',
        disableBadge: '#disable_badge_v3',
        keysStatus: '#gform_setting_recaptcha_keys_status_v3'
      };
      v3Settings.cache = {
        siteKey: $(v3Settings.fields.siteKey),
        secretKey: $(v3Settings.fields.secretKey),
        keysStatus: $(v3Settings.fields.keysStatus),
        save: $('#gform-settings-save')
      };
    };

    /**
     * Setup event listeners for field validation.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v3Settings.addEventListeners = function () {
      if (!v3Settings.strings.site_key.length) {
        return;
      }
      $(v3Settings.fields.siteKey).on('keyup', function () {
        return v3Settings.clearValidationFeedback();
      });
      $(v3Settings.fields.secretKey).on('keyup', function () {
        return v3Settings.clearValidationFeedback();
      });
    };

    /**
     * Empty out the validation feedback if the fields are modified, as we can't yet know the status.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v3Settings.clearValidationFeedback = function () {
      v3Settings.unsetValid(v3Settings.cache.siteKey.closest('.gform-settings-input__container'));
      v3Settings.unsetValid(v3Settings.cache.secretKey.closest('.gform-settings-input__container'));
    };

    /**
     * Handles validation of the v3 site key.
     *
     * @since 1.0
     *
     * @return {Promise<unknown>} Returns a promise so this can be verified synchronously if checking the secret key.
     */
    v3Settings.getRecaptchaToken = function () {
      return new Promise(function (resolve, reject) {
        var siteKeyContainer = v3Settings.cache.siteKey.closest('.gform-settings-input__container');
        try {
          var siteKey = v3Settings.cache.siteKey;
          var siteKeyValue = siteKey.val().trim();
          if (0 === siteKeyValue.length) {
            v3Settings.unsetValid(siteKeyContainer);
            v3Settings.unsetValid(v3Settings.cache.keysStatus.closest('.gform-settings-input__container'));
            $(v3Settings.fields.keysStatus).find('input').val('0');
            return;
          }
          grecaptcha.ready(function () {
            try {
              grecaptcha.execute(siteKeyValue, {
                action: 'submit'
              }).then(function (token) {
                resolve(token);
              });
            } catch (error) {
              reject(error);
            }
          });
        } catch (error) {
          reject(error);
        }
      });
    };

    /**
     * Handles validation of the v3 site and secret keys.
     *
     * On page load, attempt to generate a reCAPTCHA token and immediately validate it on the server. If it's good,
     * we'll update the presentation of the keys to indicate success or failure.
     *
     * @since 1.0
     *
     * @return {void}
     */
    v3Settings.validateKeysV3 = function () {
      var siteKeyContainer = v3Settings.cache.siteKey.closest('.gform-settings-input__container');
      var secretKeyContainer = v3Settings.cache.secretKey.closest('.gform-settings-input__container');
      var keysStatusInput = $(v3Settings.fields.keysStatus).find('input');
      if (!$(v3Settings.fields.siteKey).val().trim().length) {
        v3Settings.unsetValid(siteKeyContainer);
        v3Settings.unsetValid(secretKeyContainer);
        keysStatusInput.val('0');
        return;
      }
      v3Settings.getRecaptchaToken().then(function (token) {
        v3Settings.token = token;
      }).catch(function () {
        v3Settings.setInvalid(siteKeyContainer);
        v3Settings.setInvalid(secretKeyContainer);
        keysStatusInput.val('0');
      }).finally(function () {
        $.ajax({
          method: 'POST',
          dataType: 'JSON',
          url: ajaxurl,
          data: {
            action: 'verify_secret_key',
            nonce: v3Settings.strings.nonce,
            token: v3Settings.token,
            site_key_v3: $(v3Settings.fields.siteKey).val(),
            secret_key_v3: $(v3Settings.fields.secretKey).val()
          }
        }).then(function (response) {
          switch (response.data.keys_status) {
            case '1':
              v3Settings.setValid(siteKeyContainer);
              v3Settings.setValid(secretKeyContainer);
              keysStatusInput.val('1');
              break;
            case '0':
              v3Settings.setInvalid(siteKeyContainer);
              v3Settings.setInvalid(secretKeyContainer);
              keysStatusInput.val('0');
              break;
            default:
              v3Settings.unsetValid(siteKeyContainer);
              v3Settings.unsetValid(secretKeyContainer);
              keysStatusInput.val('0');
          }
        });
      });
    };

    /**
     * Updates the text field to display no feedback.
     *
     * @since 1.0
     *
     * @param {Object} el The jQuery element.
     *
     * @return {void}
     */
    v3Settings.unsetValid = function (el) {
      el.removeClass('gform-settings-input__container--feedback-success');
      el.removeClass('gform-settings-input__container--feedback-error');
    };

    /**
     * Updates the text field to display the successful feedback.
     *
     * @since 1.0
     *
     * @param {Object} el The jQuery element.
     *
     * @return {void}
     */
    v3Settings.setValid = function (el) {
      el.addClass('gform-settings-input__container--feedback-success');
      el.removeClass('gform-settings-input__container--feedback-error');
    };

    /**
     * Updates the text field to display the error feedback.
     *
     * @since 1.0
     *
     * @param {Object} el The jQuery element.
     *
     * @return {void}
     */
    v3Settings.setInvalid = function (el) {
      el.removeClass('gform-settings-input__container--feedback-success');
      el.addClass('gform-settings-input__container--feedback-error');
    };
    v3Settings.init();
  };
  $(document).ready(function () {
    var $form = $('#gform-settings');
    var $action = $form.find('input[name="gf_recaptcha_action"]');
    if ('gf_recaptcha_v3_classic' === $action.val()) {
      recaptchaV3Settings();
    } else if ('gf_recaptcha_v2' === $action.val()) {
      recaptchaV2Settings();
    }
    if (gforms_recaptcha_recaptcha_strings.disable_badge) {
      var badge = document.querySelector('.grecaptcha-badge');
      if (badge) {
        badge.style.visibility = 'hidden';
      }
    }

    // Handle disconnects from the admin.
    var $disconnect = $('.gfrecaptcha-disconnect');
    if ($disconnect.length > 0) {
      $disconnect.on('click', function (e) {
        e.preventDefault();

        // check if we have any saved settings that are going to be deleted
        var $site_key_v3 = $('#site_key_v3');
        var $site_key_v2 = $('#site_key_v2');
        var hasSavedSettings = false;
        if ($site_key_v3.length > 0 && $site_key_v3.val() !== '') {
          hasSavedSettings = true;
        }
        if ($site_key_v2.length > 0 && $site_key_v2.val() !== '') {
          hasSavedSettings = true;
        }

        // Confirm deleting settings if you change connection type or disconnect
        if (hasSavedSettings || $disconnect.hasClass('gfrecaptcha-disconnect')) {
          if ($disconnect.hasClass('gfrecaptcha-disconnect')) {
            var title = gforms_recaptcha_recaptcha_strings.disconnect_title;
            var message = gforms_recaptcha_recaptcha_strings.disconnect_message;
          } else {
            var title = gforms_recaptcha_recaptcha_strings.change_connection_type_title;
            var message = gforms_recaptcha_recaptcha_strings.change_connection_type_message;
          }
          if (typeof gform.instances.dialogConfirmAsync !== 'function') {
            if (!confirm(message)) {
              return;
            }
          } else {
            gform.instances.dialogConfirmAsync(title, message).then(function (userConfirmed) {
              if (!userConfirmed) {
                return;
              }
            });
          }
        }
        if ($disconnect.hasClass('gfrecaptcha-changetype')) {
          $disconnect.html(gforms_recaptcha_recaptcha_strings.change_connection_type);
        } else {
          $disconnect.html(gforms_recaptcha_recaptcha_strings.disconnect);
        }
        var url_params = wpAjax.unserialize(e.target.href);
        var nonce_value = url_params.nonce;

        // Perform Ajax request.
        $.post(ajaxurl, {
          action: 'disconnect_recaptcha',
          nonce: nonce_value
        }, function (response) {
          window.location.href = window.location.href;
        });
      });
    }

    // Handle form submission on connect screen.
    $form.on('submit', function (e) {
      // Determining if we're just connecting for the first time.
      if ($form.find('input[value="recaptcha_setup"]').length === 1) {
        // is_postback
        e.preventDefault();

        // Set l18n.
        var $save_button = $form.find('#gform-settings-save');
        var $connection_type = $form.find('[name="_gform_setting_connection_type"]:checked').val();

        // Get nonce.
        var nonce_value = $form.find('input[name="recaptcha_nonce"]').val();
        if ($connection_type === 'enterprise') {
          // Perform Ajax request.
          $.post(ajaxurl, {
            action: 'perform_enterprise_oauth',
            nonce: nonce_value,
            mode: $connection_type
          }, function (response) {
            if (!response.data.errors) {
              window.location.href = response.data.redirect;
            }
          }, 'json');
        } else {
          // Perform Ajax request.
          $.post(ajaxurl, {
            action: 'update_reload_settings',
            nonce: nonce_value,
            connection_type: $connection_type
          }, function (response) {
            if (!response.data.errors) {
              var url = new URL(response.data.redirect);
              url.searchParams.set('connection_type', $connection_type);
              window.location.href = url;
            }
          }, 'json');
        }
        return;
      }
      if ('gf_recaptcha_enterprise' === $action.val()) {
        e.preventDefault();

        // Getting selected items from the account/property and data stream drop downs.
        var $selected_project = $form.find('select[name="recaptcha_project"]').find(':selected');
        var $selected_site_key = $form.find('#recaptcha-site-keys :selected');
        $.post(ajaxurl, {
          action: 'save_recaptcha_enterprise_data',
          project_number: $selected_project.val(),
          project_id: $selected_project.data('project-id'),
          project_name: $selected_project.data('project-name'),
          site_key_v3_enterprise: $selected_site_key.val(),
          site_key_display_name: $selected_site_key.data('site-key-display-name'),
          score_threshold_v3: $form.find('#score_threshold_v3').val(),
          disable_badge_v3: $form.find('input[name="_gform_setting_disable_badge_v3"]').val(),
          nonce: $form.find('input[name="recaptcha_nonce"]').val()
        }, function (response) {
          if (response.success) {
            window.location.href = response['data'];
          }
        });
      }
    });

    // Get keys available to the selected project.
    $('#recaptcha_project').on('change', function (e) {
      var $option = $(this).find(':selected');
      var nonce = $('body').find('input[name="recaptcha_nonce"]').val();
      $('#recaptcha-site-keys').html('<br /><img src="' + gforms_recaptcha_recaptcha_strings.spinner + '" />');
      $.post(ajaxurl, {
        action: 'get_enterprise_site_keys',
        project: $option.val(),
        nonce: nonce
      }, function (response) {
        if (response.success) {
          var siteKeys = response['data'];
          var select = document.createElement('select');
          select.name = 'recaptcha-site-keys';
          var label = document.createElement('label');
          label.textContent = 'Enterprise Site Key';
          label.setAttribute('for', 'recaptcha-site-keys');
          label.classList.add('gform-settings-label');
          var header = document.createElement('div');
          header.classList.add('gform-settings-field__header');
          header.appendChild(label);
          var defaultOption = document.createElement('option');
          defaultOption.value = '';
          defaultOption.textContent = 'Select a site key';
          select.appendChild(defaultOption);
          siteKeys.forEach(function (key) {
            var option = document.createElement('option');
            option.value = key.value;
            option.textContent = key.displayName;
            option.setAttribute('data-site-key-display-name', key.displayName);
            select.appendChild(option);
          });
          var container = document.querySelector('#recaptcha-site-keys');
          container.innerHTML = '';
          container.appendChild(header);
          container.appendChild(select);
        } else {
          $('#recaptcha-site-keys').html('');
        }
      });
    });
    gform.adminUtils.handleUnsavedChanges('#gform-settings');
  });
})(jQuery);
/******/ })()
;
//# sourceMappingURL=plugin_settings.js.map