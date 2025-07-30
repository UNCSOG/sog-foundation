// Custom events for reference-cell.php

jQuery(function($) {

	// On reference cell dialog initialise
	$(document).on('reference-cell.init-dialog-from-content', function(event, content, dialog){
		console.log("init-dialog-from-content", arguments)
		alert(' "init-dialog-from-content" event has been triggered for Simple Text Cell example. See reference-cell.js file for more details');
	});

	// On reference cell cell dialog open
	$(document).on('reference-cell.dialog-open', function(event, content, dialog) {
		console.log("dialog-open", arguments)
		alert(' "dialog-open" event has been triggered for Simple Text Cell example. See reference-cell.js file for more details');
	});

	// On reference cell dialog save
	$(document).on('reference-cell.get-content-from-dialog', function(event, content, dialog){
		console.log("get-content-from-dialog", arguments)
		alert(' "get-content-from-dialog" event has been triggered for Simple Text Cell example. See reference-cell.js file for more details');
	});

	// On reference cell cell dialog close
	$(document).on('reference-cell.dialog-close', function(event, content, dialog) {
		console.log("dialog-close", arguments)
		alert(' "dialog-close" event has been triggered for Simple Text Cell example. See reference-cell.js file for more details');
	});

});