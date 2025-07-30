(function (root, factory) {
	// Free to use & distribute under the MIT license
	// Wes Johnson (@SterlingWes)
	//
	// inspired by http://martin.ankerl.com/2009/12/09/how-to-create-random-colors-programmatically/
	if (typeof exports === 'object') {
		module.exports = factory;
	} else if (typeof define === 'function' && define.amd) {
		define(factory);
	} else {
		root.RColor = factory();
	}
}(this, function () {

		var RColor = function() {
			this.hue = Math.random(),
			this.goldenRatio = 0.618033988749895;
			this.hexwidth = 2;
		};

		RColor.prototype.hsvToRgb = function (h,s,v) {
			var h_i = Math.floor(h*6),
				f = h*6 - h_i,
				p = v * (1-s),
				q = v * (1-f*s),
				t = v * (1-(1-f)*s),
				r = 255,
				g = 255,
				b = 255;
			switch(h_i) {
				case 0: r = v, g = t, b = p; break;
				case 1: r = q, g = v, b = p; break;
				case 2: r = p, g = v, b = t; break;
				case 3: r = p, g = q, b = v; break;
				case 4: r = t, g = p, b = v; break;
				case 5: r = v, g = p, b = q; break;
			}
			return [Math.floor(r*256),Math.floor(g*256),Math.floor(b*256)];
		};

		RColor.prototype.padHex = function(str) {
			if (str.length > this.hexwidth) {
				return str;
			}
			return new Array(this.hexwidth - str.length + 1).join('0') + str;
		};

		RColor.prototype.get = function(hex,saturation,value) {
			this.hue += this.goldenRatio;
			this.hue %= 1;
			if (typeof saturation !== "number") {
				saturation = 0.5;
			}
			if (typeof value !== "number") {
				value = 0.95;
			}
			var rgb = this.hsvToRgb(this.hue,saturation,value);
			if (hex) {
				return "#" + this.padHex(rgb[0].toString(16)) + this.padHex(rgb[1].toString(16)) + this.padHex(rgb[2].toString(16));
			}
			else {
				return rgb;
			}

		};

		return RColor;

}));

(function ($) {

	$.fn.ddlDrawGrid = function( options ) {
		return this.each ( function() {

			var $el = $(this);
			var defaults = {
				rows: $el.data('rows') ? $el.data('rows') : 1,  // Initial number of rows
				cols: $el.data('cols') ? $el.data('cols') : 1, // Initial number of cols.
				minCols : $el.data('min-cols') ? $el.data('min-cols') : 1,
				maxCols : $el.data('max-cols') ? $el.data('max-cols') : 12, // Cols limit. Ignored for fluid
				maxRows : $el.data('max-rows') ? $el.data('max-rows') : 4, // Rows limit
				colWidth: $el.data('col-width') ? $el.data('col-width') : 1,
				sliderHorizontal : $el.data('slider-horizontal') ? $( $el.data('slider-horizontal') ) : null, // it have to be a valid jQuery selector
				sliderVertical : $el.data('slider-vertical') ? $( $el.data('slider-vertical') ) : null,
				infoContainer : $el.data('info-container') ? $( $el.data('info-container') ) : null,
				messageContainer : $el.data('message-container') ? $( $el.data('message-container') ) : null,
				fluid: $el.data('fluid') ? $( $el.data('fluid') ) : false,
			};
			var prms = $.extend( defaults, options );
			var values = [];

			var Grid = {
				cols: prms.cols, // Number of cols
				rows: prms.rows, // Number of rows
				colWidth: prms.colWidth,
				setColWidth: function( width ) {
					this.colWidth = width;
					$el.data('col-width', this.colWidth);
				},
				updateRows: function( num ) {
					this.rows = num;
					$el.data('rows', this.rows);
				},
				updateCols: function( num ) {
					if ( num <= prms.maxCols ) {
						this.cols = num;
						$el.data('cols', this.cols);
					}
				},
				draw: function() {
					$el.empty();

					for ( y = 0; y < this.rows ; y += 1 ) { // Draw rows

						var $row = $( '<div class="grid-row js-grid-row"></div>' );
						$row.appendTo( $el ) ;

						for ( x = 0; x < this.cols ; x += 1 ) { // Draw cells
							var $cell = $('<div class="grid-cell grid-cell-' + this.colWidth + ' js-grid-cell"></div>');
							$cell.appendTo( $row );
						}

					}

					Grid.displayGridInfo(this.cols + ' x ' + this.rows);
				},
				displayGridInfo: function( info ) {
					if ( prms.infoContainer !== null ) {
						prms.infoContainer.text( info );
					}
				},
				displayMessage: function( message ) {
					if ( prms.messageContainer !== null ) {
						prms.messageContainer.wpvToolsetMessage({
							text: message,
							type: 'info',
							stay: true,
							close: false
						});
					}
				},
				removeMessage: function() {
					if ( prms.messageContainer !== null ) {
						prms.messageContainer.wpvToolsetMessage('wpvMessageRemove');
					}
				},
				destroy: function() {
					Grid.displayGridInfo('');
					Grid.removeMessage();
					$el.empty();
					$el.replaceWith( $el.data('clone') );
				}
			};

			var init = function() {

				// Create a copy of $el, it will be needed for destroying the grid
				if ( !$el.data('clone') ) {
					$el.data( 'clone', $el.clone(true) );
				}

				// Create different arrays for fluid and fixed grids
				if ( prms.fluid ) {
					values = [1,2,3,4,6,12];

					Grid.setColWidth( (12 / prms.cols) ); // FIXME: There is something wrong in here, I'm not sure what. It works correct for all the values except when prms.cols === 6
				}
				else {
					for ( i = 0; i <= prms.maxCols ; i += 1 ) { // create an array [1 .. prms.maxCols]
						values[i] = i + 1;
					}
				}

				// Initialize horizontal jQuery UI slider
				if ( prms.sliderHorizontal !== null && prms.sliderHorizontal.length !== 0 ) {
					prms.sliderHorizontal.slider({
						orientation: 'horizontal',
						min: 1,
						step: 1,
						max: function() {
							if ( prms.fluid ) {
								return values.length;
							}
							return prms.maxCols;
						}(),
						value : function() {
                            //Force the value for the slider to be 5 when 6 is set
                            // to prevent display errors of the component
                            var val = +prms.cols;
                            if( val === 6 ) val = 5;
							return val;
						}(),
						slide: function(event, ui) {
							Grid.updateCols( values[ui.value - 1] );
							if ( prms.fluid ) {
								Grid.setColWidth( values.slice(0).reverse()[ui.value - 1] ); // slice(0) is used here just to clone the array and return the reference to the new array.
							}
							Grid.draw();
						}
					});
				}

				// Initialize vertical jQuery UI slider
				if ( prms.sliderVertical !== null && prms.sliderVertical.length !== 0 ) {
					prms.sliderVertical.slider({
						orientation: 'vertical',
						min: 1,
						step: 1,
						max: prms.maxRows,
						value: prms.maxRows - prms.rows + 1,
						slide: function(event, ui) {
							var numverOfRows = prms.maxRows - ui.value + 1;
							Grid.updateRows( numverOfRows );
							Grid.draw();
							if ( numverOfRows === prms.maxRows ) {
								Grid.displayMessage(DDLayout_settings.DDL_JS.strings.more_than_4_rows);
							}
							else {
								Grid.removeMessage();
							}
						}
					});
				}
			};

			// Initialize the grid
			if ( typeof(options) === 'undefined' || typeof(options) === 'object' ) {
				init();
				Grid.draw();
			}
			// Handle 'destroy' parameter
			else if ( typeof(options) === 'string' && options === 'destroy' && $el.data('clone') ) {

				// Destroy previously created slider
				if ( prms.sliderHorizontal !== null && prms.sliderHorizontal.data('uiSlider') ) {
					prms.sliderHorizontal.slider('destroy');
				}

				// Destroy previously created slider
				if ( prms.sliderVertical !== null && prms.sliderVertical.data('uiSlider') ) {
					prms.sliderVertical.slider('destroy');
				}

				// Restore DOM element
				Grid.destroy();

			}

		});
	};

})( jQuery );

jQuery(function($) {

	//
	// Add classes to <html> tag depending on screen height
	//

	var $htmlElement = $('html');
	var classes = {
		1100: 'h-lt-1100',
		1150: 'h-lt-1150',
		1000: 'h-lt-1000',
		950: 'h-lt-950',
		900: 'h-lt-900',
		850: 'h-lt-850',
		800: 'h-lt-800',
		750: 'h-lt-750',
		700: 'h-lt-700',
		650: 'h-lt-650',
		600: 'h-lt-600',
		550: 'h-lt-550',
		500: 'h-lt-500',
		450: 'h-lt-450',
		400: 'h-lt-400'
	};


	var addHeightClasses = _.debounce( function() {

		$.each( classes, function( res ) { // remove all classes
			$htmlElement.removeClass( classes[res] );
		});

		$.each( classes, function( res ) { // Add correct class depending on screen height
			if ( head.screen.innerHeight < res ) {
				$htmlElement.addClass( classes[res] );
			}
		});
	}, 200); // adding and removing body classes after the window has stopped being resized

	$(window).on('resize.addHTMLclasses', function() {
		addHeightClasses();
	});

	addHeightClasses(); // initialize on page load

	// -------------------------------------------------- //

	//
	// Fixed position of the editor toolbar
	//
	var $editorToolbar = $('.js-editor-toolbar');

	// prevent Uncaught TypeError: Cannot read property 'top' of undefined
	if ( $editorToolbar && $editorToolbar.offset() ) {

		var $toolbarPos = $editorToolbar.offset().top;
		var $html = $('html');
		var adminBarHeight = 0;

		if ( $('#wpadminbar').length !== 0 ) {
			adminBarHeight = $('#wpadminbar').height();
		}

		var setToolbarPos = function() {

			if ( $toolbarPos <= $(window).scrollTop() + adminBarHeight + 15) { /* Scroll position + admin bar height + editor toolbar padding top */
				$html.addClass('editor-toolbar-fixed');
			}
			else {
				$html.removeClass('editor-toolbar-fixed message-displayed');
			}

		};

		$(window).on('scroll', function() {
			setToolbarPos();
		});

		setToolbarPos(); // Initialize on DOM ready
	}
	// -------------------------------------------------- //

});
