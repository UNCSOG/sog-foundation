/**
 * plugin javascript
 */
(function($){$(function () {

	if ( ! $('body.wpallimport-plugin').length) return; // do not execute any code if we are not on plugin page

	var hasActiveLicense = false;

		// Main accordion logic
	$(document).on('change', 'input[name="scheduling_enable"]', function () {
		var schedulingEnable = $('input[name="scheduling_enable"]:checked').val();
		if (schedulingEnable == 1) {
			$('#automatic-scheduling').slideDown();
			$('.manual-scheduling').slideUp();
			setTimeout(function () {
				$('.timezone-select').slideDown(275);
			}, 200);
		}
		else if (schedulingEnable == 2) {
			$('.timezone-select').slideUp(275);
			$('#automatic-scheduling').slideUp();
			$('.manual-scheduling').slideDown();
		} else {
			$('.timezone-select').hide();
			$('#automatic-scheduling').slideUp();
			$('.manual-scheduling').slideUp();
		}
		if(!window.pmxiHasSchedulingSubscription && parseInt(schedulingEnable) == 1) {
			$('.save-changes').addClass('disabled');
		} else {
			$('.save-changes').removeClass('disabled');
		}
	});

	window.handleHelpSchedulingClick = function() {
		$('input[name="scheduling_enable"][value="1"]').prop('checked', true).trigger('change');
	}

	function fetchSchedulingConnectionIcon() {
		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: 'wpai_get_scheduling_connection_icon',
				security: wp_all_import_security
			},
			dataType: "json",
			success: function (response) {
				let schedulingIcon = $('#scheduling-connection-icon');
				if (response.data.html) {
					schedulingIcon.html(response.data.html);
				} else {
					$('.unable-to-connect').html('Refresh page to see the current Scheduling connection status.');
				}
			},
			error: function (xhr, status, error) {
				$('.unable-to-connect').html('Refresh page to see the current Scheduling connection status.');
			}
		});
	}

	function openSchedulingAccordeonIfClosed() {
		if ($('.wpallimport-file-options').hasClass('closed')) {
			// Open accordion
			$('#scheduling-title').trigger('click');
		}
	}

	$(document).on('click', '.scheduling-subscribe-link', function () {
		openSchedulingSubscribeDialog(this)
	});

	window.openSchedulingSubscribeDialog = function(element) {
		// Display overlay and loader initially
		$('.wpallimport-overlay').show();
		$('.wpallimport-loader').show();

		var $self = $(element);

		$.ajax({
			type: "POST",
			url: ajaxurl,
			context: element,
			data: {
				action: 'wpai_scheduling_subscribe_dialog_content',
				security: wp_all_import_security
			},
			success: function (data) {
				$('.wpallimport-loader').hide();
				$(this).pointer({
					content: '<div id="scheduling-popup">' + data + '</div>',
					position: {
						edge: 'right',
						align: 'center'
					},
					pointerWidth: 815,
					show: function () {
						var $leftOffset = ($(window).width() - 715) / 2;
						var $topOffset = $(document).scrollTop() + 100;

						var $pointer = $('.wp-pointer').last();
						$pointer.css({'position': 'absolute', 'top': $topOffset + 'px', 'left': $leftOffset + 'px'});

						$pointer.find('a.close').remove();
						$pointer.find('.wp-pointer-buttons').append('<button class="close-pointer button button-primary button-hero wpallimport-large-button scheduling-cancel-button" style="float: right; background: #F1F1F1; text-shadow: 0 0 black; color: #777; margin-right: 10px;">Close</button>');

						$("#subscribe").unbind('click').on('click', function (event) {
							schedulingSubscribeHandler(event)
							$(".close-pointer").trigger('click');
						});

						// Close scheduling dialog
						$(".close-pointer, .wpallimport-overlay").unbind('click').on('click', function () {
							$self.pointer('close');
							if ($self.pointer) {
							$self.pointer('close');
							$self.pointer('destroy');
							}
						})

						$('#scheduling-subscribe-group').css('border', 'none').css('margin', 'auto');
					},
					close: function () {
						// Hide overlay on closing pointer dialog
						$('.wpallimport-overlay').hide();
					}
				}).pointer('open');
			},
			error: function () {
				alert('There was an issue retrieving the scheduling subscribe content.');
				$('.wpallimport-loader').hide();
			}
		});
	};

    window.openSchedulingDialog = function(itemId, element, preloaderSrc) {
        $('.wpallimport-overlay').show();
        $('.wpallimport-loader').show();

        var $self = element;
        $.ajax({
            type: "POST",
            url: ajaxurl,
            context: element,
            data: {
                'action': 'wpai_scheduling_dialog_content',
                'id': itemId,
                'security' : wp_all_import_security
            },
            success: function (data) {

                $('.wpallimport-loader').hide();
                $(this).pointer({
                    content: '<div id="scheduling-popup">' + data + '</div>',
                    position: {
                        edge: 'right',
                        align: 'center'
                    },
                    pointerWidth: 815,
                    show: function (event, t) {

                        $('.timepicker').timepicker();

                        var $leftOffset = ($(window).width() - 715) / 2;
						var $topOffset = $(document).scrollTop() + 100;

                        var $pointer = $('.wp-pointer').last();
                        $pointer.css({'position': 'absolute', 'top': $topOffset + 'px', 'left': $leftOffset + 'px'});

                        $pointer.find('a.close').remove();
                        $pointer.find('.wp-pointer-buttons').append('<button class="save-changes button button-primary button-hero wpallimport-large-button scheduling-save-button" style="float: right; background-image: none;">Save</button>');
                        $pointer.find('.wp-pointer-buttons').append('<button class="close-pointer button button-primary button-hero wpallimport-large-button scheduling-cancel-button" style="float: right; background: #F1F1F1 none;text-shadow: 0 0 black; color: #777; margin-right: 10px;">Cancel</button>');

                        $(".close-pointer, .wpallimport-overlay").unbind('click').on('click', function () {
                            $self.pointer('close');
                            $self.pointer('destroy');
                        });

                        if(!window.pmxiHasSchedulingSubscription && $('input[name="scheduling_enable"]:checked').val() == 1) {
                            $('.save-changes').addClass('disabled');
                        }

                        $(".save-changes").unbind('click').on('click', function () {
							var schedulingEnable = $('input[name="scheduling_enable"]:checked').val();

                            if($(this).hasClass('disabled')) {
                                return false;
                            }

                            var formValid = pmxiValidateSchedulingForm();

                            if (formValid.isValid) {

                                var formData = $('#scheduling-form :input').serializeArray();
                                formData.push({name: 'security', value: wp_all_import_security});
                                formData.push({name: 'action', value: 'save_import_scheduling'});
                                formData.push({name: 'element_id', value: itemId});
                                formData.push({name: 'scheduling_enable', value: schedulingEnable});

                                $('.close-pointer').hide();
                                $('.save-changes').hide();

                                $('.wp-pointer-buttons').append('<img id="pmxe_button_preloader" style="float:right" src="' + preloaderSrc + '" /> ');
                                $.ajax({
                                    type: "POST",
                                    url: ajaxurl,
                                    data: formData,
                                    dataType: "json",
                                    success: function (data) {
                                        $('#pmxe_button_preloader').remove();
                                        $('.close-pointer').show();
                                        $(".wpallimport-overlay").trigger('click');
                                    },
                                    error: function () {
                                        alert('There was a problem saving the schedule');
                                        $('#pmxe_button_preloader').remove();
                                        $('.close-pointer').show();
                                        $(".wpallimport-overlay").trigger('click');
                                    }
                                });

                            } else {
                                alert(formValid.message);
                            }
                            return false;
                        });
                    },
                    close: function () {
                        jQuery('.wpallimport-overlay').hide();
                    }
                }).pointer('open');
            },
            error: function () {
                alert('There was a problem saving the schedule');
                $('#pmxe_button_preloader').remove();
                $('.close-pointer').show();
                $(".wpallimport-overlay").trigger('click');
                $('.wpallimport-loader').hide();
            }
        });
	};

	window.pmxiValidateSchedulingForm = function () {

		var schedulingEnabled = $('input[name="scheduling_enable"]:checked').val() == 1;

		if (!schedulingEnabled) {
			return {
				isValid: true
			};
		}

		var runOn = $('input[name="scheduling_run_on"]:checked').val();

		// Validate weekdays
		if (runOn == 'weekly') {
			var weeklyDays = $('#weekly_days').val();

			if (weeklyDays == '') {
				$('#weekly li').addClass('error');
				return {
					isValid: false,
					message: 'Please select at least a day on which the import should run'
				}
			}
		} else if (runOn == 'monthly') {
			var monthlyDays = $('#monthly_days').val();

			if (monthlyDays == '') {
				$('#monthly li').addClass('error');
				return {
					isValid: false,
					message: 'Please select at least a day on which the import should run'
				}
			}
		}

		// Validate times
		var timeValid = true;
		var timeMessage = 'Please select at least a time for the import to run';
		var timeInputs = $('.timepicker');
		var timesHasValues = false;

		timeInputs.each(function (key, $elem) {

			if($(this).val() !== ''){
				timesHasValues = true;
			}

			if (!$(this).val().match(/^(0?[1-9]|1[012])(:[0-5]\d)[APap][mM]$/) && $(this).val() != '') {
				$(this).addClass('error');
				timeValid = false;
			} else {
				$(this).removeClass('error');
			}
		});

		if(!timesHasValues) {
			timeValid = false;
			$('.timepicker').addClass('error');
		}

		if (!timeValid) {
			return {
				isValid: false,
				message: timeMessage
			};
		}

		return {
			isValid: true
		};
	};

	$(document).on('click', '#weekly li', function () {

		$('#weekly li').removeClass('error');

		if ($(this).hasClass('selected')) {
			$(this).removeClass('selected');
		} else {
			$(this).addClass('selected');
		}

		$('#weekly_days').val('');

		$('#weekly li.selected').each(function () {
			var val = $(this).data('day');
			$('#weekly_days').val($('#weekly_days').val() + val + ',');
		});

		$('#weekly_days').val($('#weekly_days').val().slice(0, -1));

	});

	$(document).on('click', '#monthly li', function () {

		$('#monthly li').removeClass('error');
		$(this).parent().parent().find('.days-of-week li').removeClass('selected');
		$(this).addClass('selected');

		$('#monthly_days').val($(this).data('day'));
	});

	$(document).on('change', 'input[name="scheduling_run_on"]', function () {
		var val = $('input[name="scheduling_run_on"]:checked').val();
		if (val == "weekly") {

			$('#weekly').slideDown({
				queue: false
			});
			$('#monthly').slideUp({
				queue: false
			});

		} else if (val == "monthly") {

			$('#weekly').slideUp({
				queue: false
			});
			$('#monthly').slideDown({
				queue: false
			});
		}
	});

	$('.timepicker').timepicker();

	var selectedTimes = [];

	var onTimeSelected = function () {

		selectedTimes.push([$(this).val(), $(this).val() + 1]);

		var isLastChild = $(this).is(':last-child');
		if (isLastChild) {
			$(this).parent().append('<input class="timepicker" name="scheduling_times[]" style="display: none;" type="text" />');
			$('.timepicker:last-child').timepicker({
				'disableTimeRanges': selectedTimes
			});
			$('.timepicker:last-child').fadeIn('fast');
			$('.timepicker').on('changeTime', onTimeSelected);
		}
	};

	$(document).on('changeTime', '.timepicker', onTimeSelected);

	$('#timezone').chosen({width: '320px'});

	$(document).on('click', '.wpai-import-complete-save-button', function (e) {

		if($('.wpai-save-button').hasClass('disabled')) {
			return false;
		}

		var initialValue = $(this).find('.save-text').html();
		var schedulingEnable = $('input[name="scheduling_enable"]:checked').val() == 1;

		var validationResponse = pmxiValidateSchedulingForm();
		if (!validationResponse.isValid) {

			openSchedulingAccordeonIfClosed();
			e.preventDefault();
			return false;
		}

		var formData = $('#scheduling-form :input').serializeArray();

		formData.push({name: 'security', value: wp_all_import_security});
		formData.push({name: 'action', value: 'save_import_scheduling'});
		formData.push({name: 'element_id', value: import_id});
		formData.push({name: 'scheduling_enable', value: $('input[name="scheduling_enable"]:checked').val()});

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: formData,
			success: function (response) {
				var submitEvent = $.Event('wpae-scheduling-options-form:submit');
				$(document).trigger(submitEvent);
			},
			error: function () {
			}
		});
	});

	$(document).on('click', '#activate-license', function (e) {
		e.preventDefault();

		let license = $('#add-subscription-field').val();

		if (license) {
			$('.activate-button-group .loader').show();
			$('#activate-license').prop('disabled', true);

			$.ajax({
				url:ajaxurl+'?action=wp_all_import_api&q=schedulingLicense/saveSchedulingLicense&security=' + wp_all_import_security,
				type: "POST",
				data: {
					license: license
				},
				dataType: "json",
				success: function (response) {
					$('#activate-license .activate-license').css('background-color', '#425f9a');

					if (response.success) {
						hasActiveLicense = true;
						$('#scheduling-subscribe-group').hide();
						$('.activate-button-group .loader').hide();
						$('#activate-license').prop('disabled', false);
						$('#scheduling-schedule-input').show();
						$('#subscribe-button svg.success').show();
						$('#subscribe-button svg.success').fadeOut(3000, function () {
							$('.subscribe').hide({queue: false});
							$('#subscribe-filler').show({queue: false});
						});

						$('.save-changes').removeClass('disabled');
						window.pmxiHasSchedulingSubscription = true;

					} else {
						$subscriptionField = $('#add-subscription-field');
						$subscriptionField.val('');
						$subscriptionField.css('border-color', 'red');
						$('.activate-button-group .loader').hide();
						$('#activate-license').prop('disabled', false);
						let licenseResponse = response.license || 'invalid';

						// Check for "active sites limit reached" error
						if (licenseResponse === 'active sites limit reached') {
							openActiveSitesLimitModal(response); // Open additional modal
						} else {
							// Create dynamic overlay for license error message.
							let $overlay = $('<div class="license-overlay"></div>');
							$overlay.html('<span style="">License error:</span><span style="margin-left:5px;font-weight:500;">'+licenseResponse+'</span>');


							$subscriptionField.parent().css('position', 'relative');
							$subscriptionField.after($overlay);

							$overlay.css({
								position: 'absolute',
								top: $subscriptionField.position().top + 'px',
								left: $subscriptionField.position().left + 'px',
								width: $subscriptionField.outerWidth() - 20,
								height: $subscriptionField.outerHeight(),
								background: 'rgba(255, 255, 255, 0.9)',
								color: $subscriptionField.css('color'),
								fontSize: 14 + 'px',
								fontFamily: $subscriptionField.css('font-family'),
								fontStyle: $subscriptionField.css('font-style'),
								lineHeight: $subscriptionField.css('line-height'),
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'start',
								padding: '0 10px',
								border: '1px solid red',
								borderRadius: '4px',
								pointerEvents: 'none',
								zIndex: 9999999,
							});

							// Remove the overlay after 2.5 seconds.
							setTimeout(() => {
								$overlay.fadeOut(300, function () {
									$(this).remove();
								});
							}, 2500);
						}
					}
				}
			});

			return false;
		} else {
			$('#add-subscription-field').css('border-color', 'red');
		}
	});

	/**
	 * Check settings page for Scheduling license limit reached
	 */

	// Ensure this is the correct page for the plugin
	if ($('body').hasClass('wpallimport-plugin') && window.location.search.includes('page=pmxi-admin-settings')) {

		// Check if the license error div exists
		let $licenseStatus = $('.license-status.inline.error');
		let siteLimit = $('input[name="scheduling_license_limit"]').val(); // Fetch value of the hidden input

		// Ensure both the error div exists and contains "active sites limit reached"
		if ($licenseStatus.length && $licenseStatus.text().trim() === 'active sites limit reached' && siteLimit) {

			// Trigger the modal with the limit value
			openActiveSitesLimitModal({
				license_limit: parseInt(siteLimit, 10),
			});
		}
	}

	/**
	 * Function to display modal when active sites limit reached
	 */
	function openActiveSitesLimitModal(response) {
		let license_limit = response.license_limit;

		$('.active-sites-limit-modal-overlay').show();
		$('.active-sites-limit-modal').show();

		$('[id^="limit-"]').each(function () {
			const limitValue = parseInt($(this).attr('id').replace('limit-', ''), 10);

			if (license_limit >= limitValue) {
				$(this).hide();
			} else {
				$(this).show();
			}
		});
		
		// Center modal dynamically in case of resizing
		centerModal('.active-sites-limit-modal');

		// Add click event handlers
		$('.active-sites-limit-modal-overlay').on('click', function () {
			$('.active-sites-limit-modal-overlay').hide();
		});

		// Close the modal if clicking on the overlay
		$('.active-sites-limit-modal-overlay').on('click', function () {
			$('.active-sites-limit-modal-overlay').hide();
		});

		// Prevent closing the modal when clicking inside the modal itself
		$('.active-sites-limit-modal').on('click', function (e) {
			if (!$(e.target).is('#subscribe-upgrade')) {
				e.stopPropagation();
			}
		});

		$('.manage-license-button').on('click', function () {
			window.open('https://www.wpallimport.com/licenses', '_blank'); // Redirect to license management
		});
	}

	/**
	 * Function to dynamically center a modal on screen
	 */
	function centerModal(selector) {
		const $modal = $(selector);
		const windowWidth = $(window).width();
		const windowHeight = $(window).height();

		$modal.css({
			position: 'fixed',
			top: (windowHeight - $modal.outerHeight()) / 2 + 'px',
			left: (windowWidth - $modal.outerWidth()) / 2 + 'px'
		});
	}

	$(document).on('click', '#subscribe-upgrade', function(event){
		schedulingSubscribeHandler(event, 'input[name="upgrade_pricing_plan"]:checked', 'upgrade');
	});

	$(document).on('click', '#scheduling-already-licensed', function (event) {
		$('#subscribe-button-group').hide();
		$('#checkout-trust-group').hide();
		$('#register-site-group').removeClass('hidden');
		$('#add-subscription-field').show();
		$('.text-container p').fadeOut();
		event.stopPropagation();

	});

	$(document).on('click', '#scheduling-subscribe-group', function (event) {
		if (!$(event.target).is('button, input, a') && !$('#register-site-group').hasClass('hidden')) {
			$('#subscribe-button-group').show();
			$('#checkout-trust-group').show();
			$('#register-site-group').addClass('hidden');
			$('#add-subscription-field').hide().css('border-color', '');
			$('.text-container p').fadeIn();
		}
	});

	window.schedulingSubscribeHandler = function (event, target = 'input[name="pricing_plan"]:checked', type = 'subscribe') {
		let selectedPlan = $(target).val();
		let url = '';

		switch (selectedPlan) {
			case 'single_site':
				if(type === 'subscribe') {
					url = 'https://www.wpallimport.com/scheduling/1-site';
				}
				break;
			case 'three_sites':
				if(type === 'subscribe') {
					url = 'https://www.wpallimport.com/scheduling/3-sites';
				}else{
					url = 'https://www.wpallimport.com/scheduling/upgrade-3-sites';
				}
				break;
			case 'ten_sites':
				if(type === 'subscribe') {
					url = 'https://www.wpallimport.com/scheduling/10-sites';
				}else{
					url = 'https://www.wpallimport.com/scheduling/upgrade-10-sites';
				}
				break;
			case 'unlimited_sites':
				if(type === 'subscribe') {
					url = 'https://www.wpallimport.com/scheduling/unlimited-sites';
				}else{
					url = 'https://www.wpallimport.com/scheduling/upgrade-unlimited-sites';
				}
				break;
			default:
				break;
		}

		if (url) {
			$('#scheduling-already-licensed').trigger('click');
			window.open(url, '_blank');
			event.stopPropagation();
		}
	};

	$(document).on('click', '#subscribe', function(event){
		schedulingSubscribeHandler(event)
	});

	$(document).on('change', '.wpallimport-plugin .tiered-pricing-options .pricing-plans input[type="radio"]', function() {
		$('input[name="' + $(this).attr('name') + '"]').closest('label').removeClass('checked');
		if ($(this).is(':checked')) {
			$(this).closest('label').addClass('checked');
		}
	});


	function get_delete_missing_notice_type() {
		let $is_delete_missing = $('input#is_delete_missing');
		if (!$is_delete_missing.is(':checked') || $is_delete_missing.data('backups-prompt') === 'disabled') {
			return 0;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'import' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_send_removed_to_trash"]').is(':checked')) {
			return 1;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'import' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_change_post_status_of_removed"]').is(':checked')) {
			return 2;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'import' && $('input[name="delete_missing_action"]:checked').val() == 'remove') {
			return 3;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'all' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_send_removed_to_trash"]').is(':checked')) {
			return 4;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'all' && $('input[name="delete_missing_action"]:checked').val() == 'keep' && $('input[name="is_change_post_status_of_removed"]').is(':checked')) {
			return 5;
		}
		if ($('input[name="delete_missing_logic"]:checked').val() == 'all' && $('input[name="delete_missing_action"]:checked').val() == 'remove') {
			return 6;
		}
		return 0;
	}

	function is_valid_delete_missing_options() {
		let is_valid = true;
		if ( $('input[name="is_delete_missing"]').is(':checked') && $('input[name="delete_missing_action"]:checked').val() == 'keep' ) {
			if ( ! $('input[name="is_send_removed_to_trash"]').is(':checked')
				&& ! $('input[name="is_change_post_status_of_removed"]').is(':checked')
				&& ! $('input[name="is_update_missing_cf"]').is(':checked')
				&& ! $('input[name="missing_records_stock_status"]').is(':checked')
			) {
				is_valid = false;
			}
		}
		return is_valid;
	}

	let submit_import_settings = function($button) {

		var saveOnly = $button.hasClass('save_only');

		var hasActiveLicense = $('#scheduling_has_license').val();

		if(hasActiveLicense === '1') {
			hasActiveLicense = true;
		} else {
			hasActiveLicense = false;
		}

		var initialValue = $button.find('.save-text').html();
		var schedulingEnable = $('input[name="scheduling_enable"]:checked').val() == 1;
		if(!hasActiveLicense) {
			if (!$button.data('iunderstand') && schedulingEnable) {
				$('#no-subscription').slideDown();
				$button.find('.save-text').html('I Understand');
				$button.find('.save-text').addClass('wpai-iunderstand');
				$button.find('.save-text').css('left', '100px');
				$button.data('iunderstand', 1);

				openSchedulingAccordeonIfClosed();
				e.preventDefault();
				return;
			} else {
				if(saveOnly) {
					$('#save_only_field').prop('disabled', false);
				}
				$('#wpai-submit-confirm-form').submit();
				return;
			}
		}

		// Don't process scheduling
		if (!hasActiveLicense) {
			if(saveOnly) {
				$('#save_only_field').prop('disabled', false);
			}
			$('#wpai-submit-confirm-form').submit();

			return;
		}

		var validationResponse = pmxiValidateSchedulingForm();
		if (!validationResponse.isValid) {

			openSchedulingAccordeonIfClosed();
			$('html, body').animate({
				scrollTop: $("#scheduling-title").offset().top-100
			}, 500);
			e.preventDefault();
			return false;
		}

		var formData = $('#scheduling-form :input').serializeArray();

		formData.push({name: 'security', value: wp_all_import_security});
		formData.push({name: 'action', value: 'save_import_scheduling'});
		formData.push({name: 'element_id', value: $('#scheduling_import_id').val()});
		formData.push({name: 'scheduling_enable', value: $('input[name="scheduling_enable"]:checked').val()});

		$button.find('.easing-spinner').toggle();

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: formData,
			success: function (response) {
				$button.find('.easing-spinner').toggle();
				$button.find('.save-text').html(initialValue);
				$button.find('.save-text').removeClass('wpai-iunderstand');
				$button.find('svg').show();

				setTimeout(function(){
					if(saveOnly) {
						$('#save_only_field').prop('disabled', false);
					}
					$('#wpai-submit-confirm-form').submit();
				}, 1000);

			},
			error: function () {
				$button.find('.easing-spinner').toggle();
				$button.find('.save-text').html(initialValue);
				$button.find('.save-text').removeClass('wpai-iunderstand');
			}
		});
	}

    $('.wpai-save-scheduling-button, .wpai-save-scheduling-button-blue').on('click', function (e) {
		// Validate delete missing options.
		let notice_type = get_delete_missing_notice_type();

		if ( ! is_valid_delete_missing_options() ) {
			$('.delete-missing-error').removeClass('hidden');
			$('.switcher-target-delete_missing_action_keep').addClass('delete-missing-error-wrapper');
			return;
		}

		let $this = $(this);
		// Show notice if any.
		if (notice_type) {
			$('.confirmation-modal-' + notice_type).find('.status_of_removed').html($('select[name="status_of_removed"]').val());
			$('.confirmation-modal-' + notice_type).dialog({
				resizable: false,
				height: "auto",
				width: 550,
				modal: true,
				draggable: false,
				closeText: '',
				classes: {
					"ui-dialog": "wpai-warning-check"
				},
				buttons: {
					"Confirm": {
						click: function() {

							let confirm_field = $('#confirm-settings-' + notice_type);

							let confirm_text = confirm_field.val();

							if (confirm_text !== 'I HAVE BACKUPS') {

								if (confirm_text.length === 0) {
									alert('Please type the confirmation message.');
								} else {
									alert('Please double-check that the confirmation message has been typed as required.');
								}

								confirm_field.addClass('confirm-error');

								return false;
							}

							$( this ).dialog( "close" );

							submit_import_settings($this);
						},
						text: 'Confirm',
						class: 'wpai-warning-confirm-button'
					},
					"Cancel": {
						click: function() {
							$( this ).dialog( "close" );
						},
						text: 'Cancel',
						class: 'wpai-warning-cancel-button'
					}
				}
			});
		} else {
			submit_import_settings($this);
		}
    });

	window.toggleSection = function (faqId) {
		$('.wpallimport-plugin .tiered-pricing-options .faq-answer').each(function () {
			if ($(this).attr('id') !== faqId) {
				$(this).slideUp();
			}
		});

		$('.wpallimport-plugin .tiered-pricing-options .faq-section').each(function () {
			if ($(this).next('.faq-answer').attr('id') !== faqId) {
				$(this).removeClass('open')
					.addClass('closed')
					.attr('aria-expanded', 'false');
			}
		});

		const $faq = $('#' + faqId);
		const $section = $faq.prev('.faq-section');

		if ($faq.is(':hidden')) {
			$faq.slideDown();
			$section.removeClass('closed')
				.addClass('open')
				.attr('aria-expanded', 'true');
		} else {
			$faq.slideUp();
			$section.removeClass('open')
				.addClass('closed')
				.attr('aria-expanded', 'false');
		}
	}

});})(jQuery);
