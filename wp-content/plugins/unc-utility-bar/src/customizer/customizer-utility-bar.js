// (function ($) {
// 	"use strict";
// 	console.log('hello');
// 	wp.customize("utility_bar_display", function (value) {
// 		value.bind(function (to) {
// 			if (to == "") {
// 				var ub_script = $("#unc-ub-wrapper");
// 				if (ub_script !== null) {
// 					var s = '<script type="text/javascript" id="unc-ub-script" data-color="dark-gray" src="https://its.unc.edu/web-assets/utility-bar/utility-bar.min.js"></script>';
// 					$("head").append(s);
// 				} else {
// 					insertUtilityBar();
// 				}
// 			} else if (to == "none") {
// 				$("#unc-ub-wrapper").remove();
// 			}
// 		});
// 	});

// 	wp.customize("utility_bar_display_colors", function (value) {
// 		value.bind(function (newval) {
// 			$("#unc-ub-wrapper").removeClass().addClass(newval);
// 		});
// 	});
// })(jQuery);
"use strict";
import domReady from "@wordpress/dom-ready";
console.log("ready");
domReady(function () {
	console.log("dom ready");

	wp.customize("utility_bar_display", function (value) {
		value.bind(function (to) {
			var el = document.getElementById("unc-utility-bar");
			//console.log(to);
			if (to == "yes") {
				el.style.display = "flex";
				el.classList.add('show-utility-bar')
			} else if (to == "no") {
				el.style.display = "none";
				el.classList.add('show-utility-bar')
			}
		});
	});

	wp.customize("utility_bar_display_colors", function (value) {
		value.bind(function (newval) {
			var el = document.getElementById("unc-utility-bar");
			var oldval = el.dataset.color
			if( oldval ){
				el.classList.remove( oldval );
			}
			el.classList.add(newval);
			el.dataset.color = newval;
			
		});
	});
});
