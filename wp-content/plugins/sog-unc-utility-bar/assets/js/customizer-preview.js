(function($){
	// Show/Hide Short Title
	wp.customize('utility_bar_show_short_title', function(value) {
		value.bind(function(newval) {
			var $shortTitle = $('#unc-utility-bar .unc-utility-bar-short-title');
			if (newval === 'no') {
				$shortTitle.hide();
			} else {
				$shortTitle.show();
			}
		});
	});

	// Short Title Text
	wp.customize('utility_bar_short_title_text', function(value) {
		value.bind(function(newval) {
			var $shortTitle = $('#unc-utility-bar .unc-utility-bar-short-title');
			$shortTitle.text(newval ? newval : 'UNC-CH');
		});
	});

	// Show/Hide Title
	wp.customize('utility_bar_show_title', function(value) {
		value.bind(function(newval) {
			var $title = $('#unc-utility-bar .unc-utility-bar-title');
			if (newval === 'no') {
				$title.hide();
			} else {
				$title.show();
			}
		});
	});

	// Title Text
	wp.customize('utility_bar_title_text', function(value) {
		value.bind(function(newval) {
			var $title = $('#unc-utility-bar .unc-utility-bar-title');
			$title.text(newval ? newval : 'The University of North Carolina at Chapel Hill');
		});
	});

	// Bar Color
	wp.customize('utility_bar_display_colors', function(value) {
		value.bind(function(newval) {
			var $bar = $('#unc-utility-bar');
			$bar.removeClass('dark-gray gray black navy blue white').addClass(newval);
			$bar.attr('data-color', newval);
		});
	});

	// Menu Alignment
	wp.customize('utility_bar_menu_alignment', function(value) {
		value.bind(function(newval) {
			var $bar = $('#unc-utility-bar');
			$bar.attr('data-menu-align', newval);
		});
	});

	// Bar Width
	wp.customize('utility_bar_width', function(value) {
		value.bind(function(newval) {
			var $bar = $('#unc-utility-bar');
			$bar.css('--unc-utility-bar-width', newval ? newval : '1170px');
		});
	});

	// Show/Hide Utility Bar
	wp.customize('utility_bar_display', function(value) {
		value.bind(function(newval) {
			var $bar = $('#unc-utility-bar');
			if (newval === 'no') {
				$bar.hide();
			} else {
				$bar.show();
			}
		});
	});
})(jQuery);