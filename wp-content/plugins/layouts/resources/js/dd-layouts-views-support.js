// dd-layouts-views-support.js

var DDLayout = DDLayout || {};

DDLayout.layouts_views_support = function($)
{
    var self = this;

    _.extend( self, new DDLayout.LayoutsToolsetSupport(jQuery) );

	self.view_id = $('.js-post_ID').val();

    self.init = function( ) {
        // If this file is included then Views will be running in an iframe

        // Hide the admin menu.

        $('#adminmenuback').hide();
        $('#adminmenuwrap').hide();
        $('#wpadminbar').hide();
		$( 'html').removeClass('wp-toolbar');
        $('#wpcontent').css({'margin-left' : '10px'});
        // hide the footer
        $('#wpfooter').hide();
		// Hide the trashing button
		$('.js-wpv-inline-trash').hide();

		// show the layouts help for parametric search.
		$('.js-layouts-search-help').show();

		self._handle_save_state();

        self.operate_extra_controls( 'views-layouts-div', '.wpv-settings-complete-output.js-wpv-settings-content');

		// Fix widths for form inputs to fit Views with.
		$('#views-layouts-div input').not(':radio').css('width' , '440px');
		$('#views-layouts-div .desc').css('width' , '440px');
		$('#views-layouts-div .desc').show();

        self.operate_parametric_controls();


		$('#wpv-ddl-parametric-mode-form-target-title').suggest(ajaxurl + '?action=wpv_suggest_form_targets', {
			onSelect: function() {
				var t_value = this.value,
				t_split_point = t_value.lastIndexOf(' ['),
				t_title = t_value.substr( 0, t_split_point ),
				t_extra = t_value.substr( t_split_point ).split('#'),
				t_id = t_extra[1].replace(']', '');
				$('#wpv-ddl-parametric-mode-form-target-title').val( t_title );
				t_edit_link = $('.js-wpv-ddl-insert-view-form-target-set-existing-link').data( 'editurl' );
				$( '.js-wpv-ddl-insert-view-form-target-set-existing-link' ).attr( 'href', t_edit_link + t_id + '&action=edit&completeview=' + self.view_id + '&origid=layout' );
				$( '#wpv-ddl-parametric-mode-form-target-id' ).val( t_id ).trigger( 'change' );
				$( '.js-wpv-check-target-setup-box' ).show();
			}
		});

		$(document).on('click', '.js-wpv-ddl-insert-view-form-target-set-existing-link, .js-wpv-ddl-discard-target-setup-link', function(e) {
			if ( $(this).hasClass('js-wpv-ddl-discard-target-setup-link') ) {
				e.preventDefault();
			}
			window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(true);
			$('#views-layouts-parametric-div .js-wpv-check-target-setup-box').hide();
		});

        window.parent.DDLayout.views_in_iframe.the_frame_ready();

		if ( typeof codemirror_views_query != "undefined" ) {
			self.manage_parametric_search_mode_target_options();
			codemirror_views_query.on( 'change', function() {
				self.manage_parametric_search_mode_target_options();
			});
		}

        if (window.location.href.indexOf('page=view-archives-editor') != -1) {

            // Add buttons to the toolbar for pagination.
			// Note: his might be no longer needed :-/
            var toolbar = $('.js-wpv-settings-content .js-code-editor-toolbar ul');
            toolbar.append('<li><button class="button-secondary js-code-editor-toolbar-button js-ddl-older-posts-button"><i class="icon-chevron-left fa fa-chevron-left"></i><span class="button-label">Older posts</span></button></li>');
            toolbar.append('<li><button class="button-secondary js-code-editor-toolbar-button js-ddl-newer-posts-button"><i class="icon-chevron-right fa fa-chevron-right"></i><span class="button-label">Newer posts</span></button></li>');

            $('.js-ddl-older-posts-button').on('click', self._add_prev_shortcode);
            $('.js-ddl-newer-posts-button').on('click', self._add_next_shortcode);
        }

        if ( window.location.href.indexOf('search_result=1') != -1 && ( window.parent.DDLayout.views_in_iframe.get_dialog().is_new_cell()|| ( window.parent.DDLayout.views_in_iframe.get_dialog().cached_element && window.parent.DDLayout.views_in_iframe.get_dialog().cached_element.content.parametric_mode === 'results' ) ) ) {
			// set the Cell to display the search results.
			$('#wpv-ddl-parametric-mode-full').prop('checked', false);
			$('#wpv-ddl-parametric-mode-form').prop('checked', false);
			$('#wpv-ddl-parametric-mode-results').prop('checked', true).trigger( 'change' );
		}

    };

    self.fetch_extra_controls = function( who ){
        return window.parent.DDLayout.views_in_iframe.fetch_extra_controls(who);
    };

    self.operate_parametric_controls = function(){
        var data_parametric_extra = window.parent.DDLayout.views_in_iframe.fetch_parametric_extra_controls();
        var parametric_controls = data_parametric_extra.controls;

        var param_controls = $(parametric_controls).insertAfter('.js-wpv-settings-filter-extra-parametric .js-wpv-dps-settings');

        $('#views-layouts-parametric-div #wpv-ddl-parametric-mode-' + data_parametric_extra.mode).eq(0).prop( 'checked', true ).trigger( 'change' );
        $('#views-layouts-parametric-div #wpv-filter-form-target-' + data_parametric_extra.target).eq(0).prop( 'checked', true ).trigger( 'change' );
        $('#views-layouts-parametric-div input[name="ddl-layout-parametric_target_title"]').eq(0).val(data_parametric_extra.targettitle);
        $('#views-layouts-parametric-div input[name="ddl-layout-parametric_target_id"]').eq(0).val(data_parametric_extra.targetid);

		// Hide the parametric search controls when dealing with a WordPress Archive, now that they do have parametric purposes
		if ( $('.js-wpv-settings-archive-loop').length > 0 ) {
			$('#views-layouts-parametric-div')
				.removeClass('js-wpv-display-for-purpose js-wpv-display-for-purpose-parametric')
				.hide();
		}
    };

	self._handle_save_state = function () {
        if ( window.parent.DDLayout.views_grid._views_above_oneseven ) {
			$(document).on('js_event_wpv_set_confirmation_unload_done', function( event, state ) {
				window.parent.DDLayout.views_in_iframe.enable_ifame_close( state );
			});
		} else {
			// Tell the main window when it's OK to close the iframe
			setInterval( function () {
				window.parent.DDLayout.views_in_iframe.enable_ifame_close( ! $( '.js-wpv-view-save-all' ).prop( 'disabled' ) );
			}, 500);
		}
    };

    self._add_prev_shortcode = function () {
        window.wpcfActiveEditor = 'wpv_content';
		window.icl_editor.insert('[ddl-pager-prev-page][wpml-string context="ddl-layouts"]Older posts[/wpml-string][/ddl-pager-prev-page]');
    }

    self._add_next_shortcode = function () {
        window.wpcfActiveEditor = 'wpv_content';
		window.icl_editor.insert('[ddl-pager-next-page][wpml-string context="ddl-layouts"]Newer posts[/wpml-string][/ddl-pager-next-page]');
    }

    self.save_view = function (callback) {
        if(!$('.js-wpv-view-save-all').prop('disabled')) {
            $('.js-wpv-view-save-all').click();

            var timer_id = setInterval( function () {

                if ($('.js-wpv-section-unsaved').length == 0) {
                    clearInterval(timer_id);
                    if(typeof callback === "function") {
						callback();
					}
                }
            }, 500);
        } else {
            if(typeof callback === "function") {
				callback();
			}
        }
    }

	self.get_css_settings = function () {
		return {
			'tag' : $('#views-layouts-div .js-ddl-tag-name').val(),
			'id' : $('#views-layouts-div .js-edit-css-id').val(),
			'css' : $('#views-layouts-div #ddl-default-edit-class-name').val(),
			'name' : $('#views-layouts-div #ddl-default-edit-cell-name').val()
		};
	}

	self.get_parametric_settings = function() {
		return {
			'mode' : $('#views-layouts-parametric-div .js-wpv-ddl-parametric-mode:checked').val(),
			'target' : $('#views-layouts-parametric-div .js-wpv-ddl-parametric-target:checked').val(),
			'targettitle' : $('#views-layouts-parametric-div #wpv-ddl-parametric-mode-form-target-title').val(),
			'targetid' : $('#views-layouts-parametric-div #wpv-ddl-parametric-mode-form-target-id').val()
		};
	};

    self.get_view_name = function(){
          return jQuery('.js-title').val();
    };

	self.manage_parametric_search_mode = function( mode ) {
		if ( mode == 'full' || mode == 'results' ) {
			$('#views-layouts-parametric-div .js-wpv-ddl-parametric-mode-form-settings').hide();
			$('#wpv-ddl-parametric-mode-form-target-title').val( '' );
			$('#wpv-ddl-parametric-mode-form-target-id').val( '' );
			window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(true);
		} else {
			var target = $('#views-layouts-parametric-div .js-wpv-ddl-parametric-target:checked').val();
			$('#views-layouts-parametric-div .js-wpv-ddl-parametric-mode-form-settings').fadeIn('fast');
			if ( target == 'other' && $('#views-layouts-parametric-div input[name="ddl-layout-parametric_target_id"]').val() == '' ) {
				window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(false);
			} else {
				window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(true);
			}
		}

		// Show / Hide the filter and output section depending on the parametric mode selected.

		if (mode == 'full') {
			self._show_filter_section(true);
			self._show_output_section(true);
		} else if (mode == 'form') {
			self._show_filter_section(true);
			self._show_output_section(false);
		} else if (mode == 'results') {
			self._show_filter_section(false);
			self._show_output_section(true);
		}
	};

	self.manage_parametric_search_mode_target_options = function() {
		if ( typeof codemirror_views_query != "undefined" ) {
			var codemirror_views_query_val = codemirror_views_query.getValue();
			if ( codemirror_views_query_val.indexOf("[wpv-filter-submit") < 0 ) {
				$('#views-layouts-parametric-div #wpv-filter-form-target-other' ).prop( 'checked', false ).prop( 'disabled', true );
				$('#views-layouts-parametric-div #wpv-filter-form-target-self' ).prop( 'checked', true ).trigger( 'change' );
				$('#views-layouts-parametric-div #wpv-ddl-target-other-forbidden' ).show();
			} else {
				$('#views-layouts-parametric-div #wpv-filter-form-target-other' ).prop( 'disabled', false );
				$('#views-layouts-parametric-div #wpv-ddl-target-other-forbidden' ).hide();
			}
		}
	};

	self.manage_parametric_search_mode_target = function( target ) {
		if ( target == 'self' ) {
			$('#views-layouts-parametric-div .js-wpv-ddl-parametric-target-other-div').hide();
			$('#wpv-ddl-parametric-mode-form-target-title').val( '' );
			$('#wpv-ddl-parametric-mode-form-target-id').val( '' );
			window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(true);
		} else {
			$('#views-layouts-parametric-div .js-wpv-ddl-parametric-target-other-div').fadeIn('fast');
			if ( $('#views-layouts-parametric-div input[name="ddl-layout-parametric_target_id"]').val() == '' ) {
				window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(false);
			} else {
				window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(true);
			}
		}
	};

	self._show_filter_section = function (state) {
		if (state) {
			$('.js-wpv-settings-filter-extra').show();
			$('.js-wpv-settings-filter-extra-hidden-message').hide();
		} else {
			$('.js-wpv-settings-filter-extra').hide();

			// Show a message to say what this section is hidden.

			if ($('.js-wpv-settings-filter-extra-hidden-message').length == 0) {
				var message_div = $('<div class="js-wpv-settings-filter-extra-hidden-message"></div>');

				self._build_parametric_message(message_div,
											   'notice-results',
											   'notice-form');

				message_div.insertBefore('.js-wpv-settings-filter-extra');
			}

			$('.js-wpv-settings-filter-extra-hidden-message').show();

		}
	};

	self._show_output_section = function (state) {
		if (state) {
			$('.wpv-layout-section').show();
			$('.js-wpv-layout-section-hidden-message').hide();
		} else {
			$('.wpv-layout-section').hide();

			// Show a message to say what this section is hidden.

			if ($('.js-wpv-layout-section-hidden-message').length == 0) {
				var message_div = $('<div class="wpv-layout-section-hidden-message js-wpv-layout-section-hidden-message"></div>');

				self._build_parametric_message(message_div,
											   'notice-form',
											   'notice-results');

				message_div.insertBefore('.wpv-layout-section');

				var section_title = $('.wpv-layout-section .wpv-section-title').text();
				$('.js-wpv-layout-section-hidden-message').prepend('<div class="wpv-layout-section-hidden-message"><h3 class="wpv-section-title">' + section_title + '</h3></div>');
			}

			$('.js-wpv-layout-section-hidden-message').show();

		}
	};

	self._build_parametric_message = function (message_div, nnn, mmm) {
		var parametric_div = $('#views-layouts-parametric-div');

		nnn = '<strong>' + parametric_div.data(nnn) + '</strong>';
		mmm = '<strong>' + parametric_div.data(mmm) + '</strong>';

		var message = parametric_div.data('notice-1');
		message = message.replace(/\%NNN\%/g, nnn);
		message = message.replace(/\%MMM\%/g, mmm);
		message_div.append('<div class="toolset-alert toolset-alert-warning"><p>' + message + '</p>' +
							'<ol>' +
							'<li>' + parametric_div.data('notice-2') + '</li>' +
							'<li>' + parametric_div.data('notice-3').replace(/\%MMM\%/g, mmm) + '</li>' +
							'</ol>' +
							'</div>' );

	}

	$( document ).on( 'change', '.js-wpv-ddl-parametric-mode', function() {
		var mode = $('#views-layouts-parametric-div .js-wpv-ddl-parametric-mode:checked').val();
		self.manage_parametric_search_mode( mode );
	});

	$( document ).on( 'change', '.js-wpv-ddl-parametric-target', function() {
		var target = $('#views-layouts-parametric-div .js-wpv-ddl-parametric-target:checked').val();
		self.manage_parametric_search_mode_target( target );
	});

	$( document ).on( 'change input cut paste', '#wpv-ddl-parametric-mode-form-target-title', function() {
		$( '.js-wpv-check-target-setup-box' ).hide();
		$('#wpv-ddl-parametric-mode-form-target-id').val( '' );
		window.parent.DDLayout.views_in_iframe.manage_iframe_close_button(false);
	});

    self.init();

    _.bindAll(self, 'operate_parametric_controls');

}

jQuery(function($) {
    DDLayout.layouts_views = new DDLayout.layouts_views_support($);
});

