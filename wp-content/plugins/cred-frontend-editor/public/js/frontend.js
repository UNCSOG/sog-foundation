/**
 * Forms main frontend script.
 * @todo Bring some better practices to the taxonomy-related module.
 *
 * @package CRED
 */
var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.Frontend = Toolset.CRED.Frontend || {};

Toolset.CRED.Frontend.RecaptchaWidgetIds = {};

/**
 * Acts as callback for the recaptcha library.
 *
 * @see /toolset-common/toolset-forms/classes/class.recaptcha.php
 */
var onLoadRecaptcha = function () {
	//Init of all recaptcha
	jQuery.each(jQuery('.g-recaptcha'), function (i, recaptcha_selector) {
		var $current_form = jQuery(recaptcha_selector).closest('form');
		if ( typeof grecaptcha !== 'undefined' ) {
			var formID = $current_form.attr( 'id' );
			var $recaptcha_selector = $current_form.find( 'div.g-recaptcha' );
			if ( $recaptcha_selector.length ) {
				var _sitekey = $recaptcha_selector.data( 'sitekey' );
				if ( typeof _sitekey !== 'undefined' ) {
					var recaptcha_widget_id = grecaptcha.render( $recaptcha_selector.attr( 'id' ), { sitekey: _sitekey } );
					Toolset.CRED.Frontend.RecaptchaWidgetIds[formID] = recaptcha_widget_id;
				}
			}
		}
	});
};

/**
 * Taxonomy fields controller module.
 *
 * @param {jQuery} $
 */
Toolset.CRED.Frontend.Taxonomy = function( $ ) {

	var self = this;

	self._new_taxonomy = new Array();

	/**
	 * Fills hierarchical taxonomy parent select dropdown.
	 *
	 * @param object $form
	 */
	self.syncHierarchicalParentTermSelector = function ( taxonomy, $form ) {
		var $select = $form.find('select.js-taxonomy-parent[data-taxonomy="' + taxonomy + '"]' )

		// Remove all non-default options.
		$select.find('option').each( function () {
			if ( $( this ).val() != '-1' ) {
				$( this ).remove();
			}
		});

		// Copy all the checkbox values if it's checkbox mode.
		$ ('input[name="' + taxonomy + '\[\]"]', $form ).each(function () {
			var label = $( this ).data('value');
			var level = $( this ).closest( 'ul' ).data( 'level' );
			var prefix = '';
			if ( level ) {
				prefix = "\xA0\xA0" + Array( level ).join( "\xA0\xA0" );
			}
			$select.append( '<option value="' + $( this ).val() + '">' + prefix + label + '</option>' );
		});

		// Copy all the select option values if it's select mode.
		$( 'select[name="' + taxonomy + '\[\]"]', $form ).find( 'option' ).each( function() {
			var id = $( this ).val();
			var text = $( this ).text();
			$select.append( '<option value="' + id + '">' + text + '</option>' );
		});
	};

	/**
	 * Toggles the visibility of the container to add a new hierarchical term.
	 *
	 * @todo This should not depend on a $button object.
	 *
	 * @param {string} taxonomy
	 * @param {objct} $button
	 */
	self.toggleHierarchicalAddNewTerm = function( taxonomy, $button ) {
		var $form = $( $button ).closest( 'form' );
		var $add_wrap = $( ".js-wpt-hierarchical-taxonomy-add-new-" + taxonomy, $form );
		if ( $add_wrap.is( ":visible" ) ) {
			$add_wrap.hide();
		} else {
			$add_wrap.show();
		}

		// Enable/disable te parent term selector based on how many terms it shows.
		// Note the default, empty option.
		if ( $('[name="new_tax_select_' + taxonomy + '"] option', $form ).length > 1 ) {
			$('[name="new_tax_select_' + taxonomy + '"]', $form ).prop( 'disabled', false );
		} else {
			$( '[name="new_tax_select_' + taxonomy + '"]', $form ).prop( 'disabled', true );
		}
	};

	/**
	 * Adds a term to a hierarchical taxonomy.
	 *
	 * @todo This should not depend on a $button object.
	 *
	 * @param {string} taxonomy
	 * @param {object} $button
	 */
	self.setHierarchicalTaxonomyTerm = function( taxonomy, $button ) {
		var $form = $( $button ).closest( 'form' );
		var new_taxonomy = $( '[name="new_tax_text_' + taxonomy + '"]', $form ).val();
		new_taxonomy = new_taxonomy.trim();

		if ( new_taxonomy === '' ) {
			return;
		}

		var isBootstrap = ( 'bootstrap' === $( $button ).data( 'output' ) );
		var build_what = $( $button ).data( 'build_what' );

		// Check if we already have a trm with that name.
		var exists = false;
		$( 'input[name="' + taxonomy + '\[\]"]', $form ).each( function() {
			var label = $( this ).data( 'value' );
			if ( new_taxonomy === label ) {
				exists = true;
				self._flash_it( $( this ).parent( 'label' ) );
				return false;
			}
		});

		$('select[name="' + taxonomy + '\[\]"]', $form).find('option').each( function() {
			if ( new_taxonomy === $( this ).text() ) {
				exists = true;
				self._flash_it( $( this ) );
				return false;
			}
		});

		if ( exists ) {
			$( '[name="new_tax_text_' + taxonomy + '"]', $form ).val( '' );
			return;
		}

		// Build the term and add it to the right form element.
		var parent = $( '[name="new_tax_select_' + taxonomy + '"]', $form ).val(),
			add_position = null,
			add_before = true,
			$div_fields_wrap = $( 'div[data-item_name="taxonomyhierarchical-' + taxonomy + '"]', $form ),
			level = 0;

		if ( build_what === 'checkboxes' ) {
			//Fix add new leaf.
			$( 'div[data-item_name="taxonomyhierarchical-' + taxonomy + '"] li input[type=checkbox]', $form ).each( function() {
				if (
					this.value == parent
					|| this.value == new_taxonomy
				) {
					$div_fields_wrap = $( this ).parent();
				}
			});

			var new_checkbox = "";
			if ( isBootstrap ) {
				new_checkbox = '<li class="checkbox"><label class="wpt-form-label wpt-form-checkbox-label"><input data-parent="' + parent + '" class="wpt-form-checkbox form-checkbox checkbox" type="checkbox" name="' + taxonomy + '[]" data-value="' + new_taxonomy + '" checked="checked" value="' + new_taxonomy + '"></input>' + new_taxonomy + '</label></li>';
			} else {
				new_checkbox = '<li><input data-parent="' + parent + '" class="wpt-form-checkbox form-checkbox checkbox" type="checkbox" name="' + taxonomy + '[]" checked="checked" value="' + new_taxonomy + '"></input><label class="wpt-form-label wpt-form-checkbox-label">' + new_taxonomy + '</label></li>';
			}
			// Find the first checkbox sharing parent.
			var $first_checkbox = $( 'input[name="' + taxonomy + '\[\]"][data-parent="' + parent + '"]:first', $form );
			if ( $first_checkbox.length == 0 ) {
				// There are no existing brothers so we need to compose the ul wrapper and append to the parent li.
				level = $( 'input[name="' + taxonomy + '\[\]"][value="' + parent + '"]', $form ).closest( 'ul' ).data( 'level' );
				level++;
				new_checkbox = '<ul class="wpt-form-set-children" data-level="' + level + '">' + new_checkbox + '</ul>';
				if ( isBootstrap ) {
					$( new_checkbox ).insertAfter( $div_fields_wrap );
				} else {
					$( new_checkbox ).appendTo( $div_fields_wrap );
				}
			} else {
				// There are brothers so we need to insert before all of them.
				add_position = $first_checkbox.closest( 'li' );
				$( new_checkbox ).insertBefore( add_position );
			}
			$( '[name="new_tax_select_' + taxonomy + '"]', $form ).show();
		} else if ( build_what === 'select' ) {
			// Select control.
			$( 'select[name="' + taxonomy + '\[\]"]', $form ).show();

			var label = '';
			var indent = '';
			var $first_option = $( 'select[name="' + taxonomy + '\[\]"]', $form ).find( 'option[data-parent="' + parent + '"]:first' );
			if ( $first_option.length == 0 ) {
				// There a no children of this parent.
				$first_option = $( 'select[name="' + taxonomy + '\[\]"]', $form ).find( 'option[value="' + parent + '"]:first' );
				add_before = false;
				label = $first_option.text();
				for ( var i = 0; i < label.length; i++ ) {
					if ( label[ i ] == '\xA0') {
						indent += '\xA0';
					} else {
						break;
					}
				}
				indent += '\xA0';
				indent += '\xA0';
				add_position = $( 'select[name="' + taxonomy + '\[\]"]', $form );
			} else {
				add_position = $first_option;
				label = $first_option.text();
				for ( var i = 0; i < label.length; i++ ) {
					if ( label[ i ] == '\xA0' ) {
						indent += '\xA0';
					} else {
						break;
					}
				}
			}

			if ( add_position ) {
				var new_option = '<option value="' + new_taxonomy + '" selected>' + indent + new_taxonomy + '</option>';
				if ( add_before ) {
					$( new_option ).insertBefore( add_position );
				} else {
					$( new_option ).appendTo( add_position );
				}
			}
			$( '[name="new_tax_select_' + taxonomy + '"]', $form ).show()
		}

		// Store the hierarchy for the new taxonomy term.
		var $new_taxonomy_input = $( 'input[name="' + taxonomy + '_hierarchy"]', $form );
		if ( $new_taxonomy_input.length <= 0 ) {
			// Add a hidden field for the new terms hierarchy.
			$( '<input name="' + taxonomy + '_hierarchy" style="display:none" type="hidden">' ).insertAfter( $( '[name="new_tax_text_' + taxonomy + '"]', $form ) );
			$new_taxonomy_input = $( 'input[name="' + taxonomy + '_hierarchy"]', $form );
		}

		if ( typeof self._new_taxonomy[ taxonomy ] === 'undefined') {
			self._new_taxonomy[ taxonomy ] = new Array();
		}

		var parent = $( '[name="new_tax_select_' + taxonomy + '"]', $form ).val();
		self._new_taxonomy[ taxonomy ].push( parent + ',' + new_taxonomy );

		var value = '';
		for ( var i = 0; i < self._new_taxonomy[ taxonomy ].length; i++ ) {
			value += '{' + self._new_taxonomy[ taxonomy ][ i ] + '}';
		}
		value = $new_taxonomy_input.val() + value;
		$new_taxonomy_input.val( value );

		$( '[name="new_tax_text_' + taxonomy + '"]', $form ).val( '' );

		self.syncHierarchicalParentTermSelector( taxonomy, $form );
	};

	/**
	 * Flashes a node.
	 *
	 * @param {object} $element
	 */
	self._flash_it = function( $element ) {
		$element.fadeOut( 300 ).fadeIn( 300 ).fadeOut( 300 ).fadeIn( 300 );
	};

	/**
	 * Fills fields and hidden fields for flat taxonomies with the right values.
	 *
	 * @param {string} values
	 * @param {string} taxonomy
	 * @param {object} $form
	 */
	self.initFlatTaxonomies = function( values, taxonomy, $form ) {
		$( 'div.tagchecklist-' + taxonomy, $form ).html( values );
		$( 'input[name=' + taxonomy + ']', $form ).val( values );
		self.updateFlatTaxonomyTerms( taxonomy, $form );

		$( 'input[name=tmp_' + taxonomy + ']', $form ).suggest(
			wptoolset_forms_local.ajaxurl + '?action=wpt_suggest_taxonomy_term&taxonomy=' + taxonomy,
			{
				resultsClass: 'wpt-suggest-taxonomy-term',
				selectClass: 'wpt-suggest-taxonomy-term-select'
			}
		);

		if ( $( 'input[name=tmp_' + taxonomy + ']', $form ).val() !== "" ) {
			$( "input[name='new_tax_button_" + taxonomy + "']", $form ).trigger( 'click' );
		}
	};

	/**
	 * Toggles the button to show popular flat taxonomy terms.
	 *
	 * @param {sring} taxonomy
	 * @param {object} form
	 */
	self.toggleMostPopularFlatTaxonomyTermsButton = function( taxonomy, form ) {
		var $button = $( '[name="sh_' + taxonomy + '"]', form );
		var $taxonomy_box = $( '.shmpt-' + taxonomy, form );
		var $tag_list = $taxonomy_box.find( '.js-wpt-taxonomy-popular-add' );

		if ( ! $button.hasClass( 'js-wpt-taxonomy-popular-show-hide' ) ) {
			return true;
		}

		if ( $tag_list.length > 0 ) {
			$button.show();
			return true;
		} else {
			$button.hide();
			return false;
		}
	};

	/**
	 * Toggles the container for the popular flat taxonomy terms.
	 *
	 * @param {object} el
	 */
	self.togglePopularFlatTaxonomyTerms = function( el ) {
		var data_type_output = $( el ).data( 'output' );
		var taxonomy = $( el ).data( 'taxonomy' );
		var form = $( el ).closest( 'form' );
		$( '.shmpt-' + taxonomy, form ).toggle();

		if ( data_type_output == 'bootstrap' ) {
			var curr = $( el ).text();
			if ( curr == $( el ).data( 'show-popular-text' ) ) {
				$( el ).text( $( el ).data( 'hide-popular-text' ), form );
				$( el ).addClass( 'btn-cancel dashicons-dismiss')
					.removeClass( 'dashicons-plus-alt' );
			} else {
				$( el ).text( $( el ).data( 'show-popular-text' ), form );
				$( el ).removeClass( 'btn-cancel dashicons-dismiss' )
					.addClass( 'dashicons-plus-alt' );
			}
		} else {
			var curr = $( el ).val();
			if ( curr == $( el ).data( 'show-popular-text' ) ) {
				$( el ).val( $( el ).data( 'hide-popular-text' ), form )
					.addClass( 'btn-cancel' );
			} else {
				$( el ).val( $( el ).data( 'show-popular-text' ), form )
					.removeClass( 'btn-cancel' );
			}
		}
	};

	/**
	 * Adds a flat taxonomy term form the popular terms list.
	 *
	 * @param {string} slug
	 * @param {string} taxonomy
	 * @param {object} $el
	 */
	self.setFlatTaxonomyTermFromPopular = function( slug, taxonomy, $el ) {
		var $form = $( $el ).closest( 'form' );
		var tmp_tax = String( slug );
		if ( typeof tmp_tax === "undefined" || tmp_tax.trim() == '' ) {
			return;
		}
		var tax = $( 'input[name=' + taxonomy + ']', $form ).val();
		var arr = String( tax ).split( ',' );
		if ( $.inArray( tmp_tax, arr ) !== -1 ) {
			return;
		}
		var toadd = ( tax == '' ) ? tmp_tax : tax + ',' + tmp_tax;
		$( 'input[name=' + taxonomy + ']', $form ).val( toadd );
		self.updateFlatTaxonomyTerms( taxonomy, $form );
	};

	/**
	 * Adds a flat taxonomy term.
	 *
	 * @todo This can not depend on the $el button object.
	 *
	 * @param {string} taxonomy
	 * @param {object} $el
	 */
	self.setFlatTaxonomyTerm = function( taxonomy, $el ) {
		var $form = $( $el ).closest( 'form' );
		var tmp_tax = $( 'input[name=tmp_' + taxonomy + ']', $form ).val();
		var rex = /<\/?(a|abbr|acronym|address|applet|area|article|aside|audio|b|base|basefont|bdi|bdo|bgsound|big|blink|blockquote|body|br|button|canvas|caption|center|cite|code|col|colgroup|data|datalist|dd|del|details|dfn|dir|div|dl|dt|em|embed|fieldset|figcaption|figure|font|footer|form|frame|frameset|h1|h2|h3|h4|h5|h6|head|header|hgroup|hr|html|i|iframe|img|input|ins|isindex|kbd|keygen|label|legend|li|link|listing|main|map|mark|marquee|menu|menuitem|meta|meter|nav|nobr|noframes|noscript|object|ol|optgroup|option|output|p|param|plaintext|pre|progress|q|rp|rt|ruby|s|samp|script|section|select|small|source|spacer|span|strike|strong|style|sub|summary|sup|table|tbody|td|textarea|tfoot|th|thead|time|title|tr|track|tt|u|ul|var|video|wbr|xmp)\b[^<>]*>/ig;
		tmp_tax = _.escape( tmp_tax.replace( rex, "" ) ).trim();
		if ( tmp_tax.trim() == '' ) {
			return;
		}
		var tax = $( 'input[name=' + taxonomy + ']', $form ).val();
		var arr = tax.split( ',' );
		if ( $.inArray( tmp_tax, arr ) !== -1 ) {
			return;
		}
		var toadd = ( tax == '' ) ? tmp_tax : tax + ',' + tmp_tax;
		$( 'input[name=' + taxonomy + ']', $form ).val( toadd );
		$( 'input[name=tmp_' + taxonomy + ']', $form ).val( '' );
		self.updateFlatTaxonomyTerms( taxonomy, $form );
	};

	/**
	 * Updates the visible elements from the stored flat taxonomy assigned terms.
	 *
	 * @param {string} taxonomy
	 * @param {object} $form
	 */
	self.updateFlatTaxonomyTerms = function( taxonomy, $form ) {
		var $taxonomies_selector = $( 'input[name=' + taxonomy + ']', $form );
		var taxonomies = $taxonomies_selector.val();
		$( 'div.tagchecklist-' + taxonomy, $form ).html( '' );
		if ( ! taxonomies || (taxonomies && taxonomies.trim() == '' ) ) {
			return;
		}

		var toshow = taxonomies.split( ',' );
		var str = '';
		for ( var i = 0; i < toshow.length; i++ ) {
			var sh = toshow[ i ].trim();
			if ( $taxonomies_selector.data('output') == 'bootstrap' ) {
				str += '<a class=\'label label-default dashicons-before dashicons-no\' data-wpcf-i=\'' + i + '\' id=\'post_tag-check-num-' + i + '\'>' + sh + '</a> ';
			} else {
				str += '<span><a href="#" class=\'ntdelbutton\' data-wpcf-i=\'' + i + '\' id=\'post_tag-check-num-' + i + '\'>X</a>&nbsp;' + sh + '</span>';
			}
		}
		$( 'div.tagchecklist-' + taxonomy, $form ).html( str );
		$( 'div.tagchecklist-' + taxonomy + ' a', $form ).on( 'click', function() {
			$( 'input[name=' + taxonomy + ']', $form ).val( '' );
			var del = $( this ).data( 'wpcf-i' );
			var values = '';
			for ( i = 0; i < toshow.length; i++ ) {
				if ( del == i ) {
					continue;
				}
				if ( values ) {
					values += ',';
				}
				values += toshow[ i ];
			}
			$( 'input[name=' + taxonomy + ']', $form ).val( values );
			self.updateFlatTaxonomyTerms( taxonomy, $form );

			return false;
		});
	};

	/**
	 * Inits a specific form:
	 * - Init taxonomy buttons.
	 * - Init hierarchical taxonomy fields.
	 * - Init flat taxonomy fields.
	 *
	 * @param {object} $form
	 */
	self.initForm = function( $form ) {
		// Initialize taxonomy buttons.
		// Replace the taxonomy button placeholders with the actual buttons.
		// This makes little sense: we replace the placeholdr with a loop?
		$form.find( '.js-taxonomy-button-placeholder' ).each( function() {
			var $placeholder = $( this );
			var label = $( this ).attr( 'data-label' );
			var taxonomy = $( this ).data( 'taxonomy' );
			var $buttons = $( '[name="sh_' + taxonomy + '"]', $form );

			if ( $buttons.length ) {
				$buttons.each( function() {
					var $button = $( this );
					if ( label ) {
						$button.val( label );
					}

					$placeholder.replaceWith( $button );

					if ( $button.hasClass( 'js-wpt-taxonomy-popular-show-hide' ) ) {
						if ( self.toggleMostPopularFlatTaxonomyTermsButton( taxonomy, $form ) ) {
							$button.show();
						}
					} else {
						$button.show();
					}
				});
			}
		});

		// Init hierarchical taxonomies.
		$form.find( '.js-wpt-hierarchical-taxonomy-add-new-container' ).each( function() {
			var $addNewContainer = $( this ),
				$taxonomy = $addNewContainer.data( 'taxonomy' ),
				$addNewShowHide = $( '.js-wpt-hierarchical-taxonomy-add-new-show-hide[data-taxonomy="' + $taxonomy + '"]', $form ),
				$placeholder = $( '.js-taxonomy-hierarchical-button-placeholder[data-taxonomy="' + $taxonomy + '"]', $form );

			if ( $placeholder.length > 0 ) {
				$addNewShowHide
					.insertAfter( $placeholder )
					.show();
				$placeholder.replaceWith( $addNewContainer );
				self.syncHierarchicalParentTermSelector( $taxonomy, $form );
			} else {
				$addNewContainer.remove();
				$addNewShowHide.remove();
			}
		});

		// Init flat taxonomies.
		$form.find( '.js-wpt-new-taxonomy-title[data-taxtype="flat"]' ).each( function() {
			var taxInput = $( this );
			var taxonomy = taxInput.data( 'taxonomy' );
			var initialData = $form.find( 'input[name="' + taxonomy + '"]' ).data( 'initial' );

			self.initFlatTaxonomies( initialData.values, initialData.name, $form );
		});
	};

	/**
	 * Inits all forms ate once.
	 */
	self.initAllForms = function() {
		$( '.cred-form, .cred-user-form', document ).each( function() {
			self.initForm( $( this ) );
		});
	};

	/**
	 * Inits relevant events.
	 */
	self.initEvents = function() {
		// Flat taxonomies: toggle most popular terms.
		$( document ).on( 'click', '.js-wpt-taxonomy-popular-show-hide', function () {
			self.togglePopularFlatTaxonomyTerms(this);
		});

		// Flat taxonomies: add a term from the popular terms cloud.
		$( document ).on( 'click', '.js-wpt-taxonomy-popular-add', function () {
			var $thiz = $( this );
			var taxonomy = $thiz.data( 'taxonomy' );
			var _name = $thiz.data( 'name' );
			self.setFlatTaxonomyTermFromPopular( _name, taxonomy, this );
			return false;
		});

		// Flat taxonomies: add a new term by clicking the add button.
		$( document ).on( 'click', '.js-wpt-taxonomy-add-new', function() {
			var taxonomy = $( this ).data( 'taxonomy' );
			self.setFlatTaxonomyTerm( taxonomy, this );
		});

		// Flat and hierarchical taxonomies: add a new term by clicking Enter on the term title input.
		$( document ).on( 'keydown', '.js-wpt-new-taxonomy-title', function( e ) {
			if ( "Enter" === e.key ) {
				e.preventDefault();
				var $thiz = $( this ),
					taxonomy = $thiz.data('taxonomy'),
					taxtype = $thiz.data('taxtype');
				if ( taxtype == 'hierarchical' ) {
					var $button = $thiz
						.closest( '.js-wpt-hierarchical-taxonomy-add-new-container' )
							.find( '.js-wpt-hierarchical-taxonomy-add-new' );
					self.setHierarchicalTaxonomyTerm(taxonomy, $button);
				} else {
					self.setFlatTaxonomyTerm(taxonomy, this);
				}
			}
		});

		// Hierarchical taxonomy: add a new term.
		$( document ).on( 'click', '.js-wpt-hierarchical-taxonomy-add-new', function() {
			var $thiz = $( this ),
				taxonomy = $thiz.data( 'taxonomy' );
			self.setHierarchicalTaxonomyTerm( taxonomy, this );
		});

		// Hierarchical taxonomy: toggle the container to add a new term.
		$( document ).on( 'click', '.js-wpt-hierarchical-taxonomy-add-new-show-hide', function() {
			var $button = $( this ),
				$taxonomy = $button.data( 'taxonomy' ),
				$output = $button.data( 'output' );
			if ( $output == 'bootstrap' ) {
				// Dealing with an anchor button
				if ( $button.text() == $button.data( 'close' ) ) {
					$button
						.html( $button.data( 'open' ) )
						.removeClass( 'dashicons-dismiss' )
						.addClass( 'dashicons-plus-alt' );
				} else {
					$button
						.html( $button.data( 'close' ) )
						.removeClass( 'dashicons-plus-alt' )
						.addClass( 'dashicons-dismiss' );
				}
			} else {
				// Dealing with an input button
				if ( $button.val() == $button.data( 'close' ) ) {
					$button
						.val( $button.data( 'open' ) )
						.removeClass( 'btn-cancel' );
				} else {
					$button
						.val( $button.data( 'close' ) )
						.addClass( 'btn-cancel' );
				}
			}
			self.toggleHierarchicalAddNewTerm( $taxonomy, this );
		});
	};

	/**
	 * Inits the module.
	 */
	self.init = function() {
		self.initEvents();
	};

	self.init();

};

/** @var {object} Backwards compatibility */
var credFrontEndViewModel = {};

/**
 * Main frontend forms management module.
 *
 * @param {jQuery} $
 */
Toolset.CRED.Frontend.Forms = function( $ ) {

	var self = this;

	/** @var mixed[] */
	self.i18n = cred_frontend_i18n;

	/**
	 * Generate an unique ID.
	 * @return string
	 */
	self.uniqueID = function() {
		return Math.floor( ( 1 + Math.random() ) * 0x10000 )
			.toString( 16 )
			.substring( 1 );
	};

	/**
	 * @param object $form
	 */
	self.initDatePicker = function( $form ) {
		if ( 0 == $form.find( '.js-wpt-date' ).length ) {
			return;
		}
		if ( typeof( wptDate ) !== 'undefined' ) {
			wptDate.init( $form );
		}
	};

	/**
	 * @param object $form
	 */
	self.initColorPicker = function( $form ) {
		if ( 0 == $form.find( '.js-wpt-colorpicker' ).length ) {
			return;
		}
		if ( typeof( wptColorpicker ) !== 'undefined' ) {
			wptColorpicker.init( $form );
		}
	};

	/**
	 * @param object $form
	 */
	self.initValidation = function( $form ) {
		if ( typeof( wptValidation ) !== 'undefined' ) {
			var $formID = $form.attr( 'id' );
			wptValidation._initValidation( '#' + $formID );
			wptValidation.applyRules( '#' + $formID );
		}
	};

	/**
	 * @param object $form
	 */
	self.initAccessibilityLabels = function( $form ) {
		var $cred_form_labels = $form.find( '.form-group label' );
		for ( var form_label_index in $cred_form_labels ) {
			if ( isNaN( form_label_index ) ) {
				break;
			}

			var $form_label = $( $cred_form_labels[ form_label_index ] );
			var accessibility_id = self.uniqueID();

			$input_array = [];

			$input_array.push( $form_label.parent().find( ':input:not(:button)' ) );
			$input_array.push( $form_label.parent().find( 'select' )[0] );
			$input_array.push( $form_label.parent().find( 'textarea' )[0] );

			if ( $input_array.length > 0 ) {
				for ( var input in $input_array ) {
					if ( $input_array[ input ] !== undefined ) {
						$input_array[ input ] = $( $input_array[ input ] );
						if (
							$input_array[ input ].attr( 'id' ) !== undefined
							&& $input_array[ input ].attr( 'id' ) !== null
							&& $input_array[ input ].attr( 'id' ) != ""
						) {
							$form_label.attr( 'for', $input_array[ input ].attr( 'id' ) );
						} else {
							$input_array[ input ].attr( 'id', accessibility_id );
							$form_label.attr( 'for', accessibility_id );
						}
					}
				}
			}
		}
	};

	/**
	 * Backwards compatibility: init accessibility labels for all forms.
	 */
	self.addAccessibilityIDs = function() {
		$( '.cred-form, .cred-user-form', document ).each( function() {
			self.initAccessibilityLabels( $( this ) );
		});
	};

	/**
	 * @param object $form
	 */
	self.maybeInitPreviewMode = function( $form ) {
		if (
			window.hasOwnProperty('cred_form_preview_mode')
			&& true == window.cred_form_preview_mode
		) {
			$form.find( '#insert-media-button' ).prop( 'disabled', true );
			$form.find( '.insert-media' ).prop( 'disabled', true );
			$form.find( 'input[type="file"]' ).attr( 'onclick', 'return false' );

			$( document ).on( 'toolset_repetitive_field_added', function() {
				$form.find( 'input[type="file"]' ).attr( 'onclick', 'return false' );
			});
		}
	};

	/**
	 * Backwards compatibility: init preview mode for all forms.
	 */
	self.activatePreviewMode = function() {
		if (
			window.hasOwnProperty('cred_form_preview_mode')
			&& true == window.cred_form_preview_mode
		) {
			$( '.cred-form, .cred-user-form', document ).each( function() {
				self.maybeInitPreviewMode( $( this ) );
			});
		}
	};

	/**
	 * @param $form
	 */
	 self.enableSubmitForm = function( $form ) {
		$form.find( '.wpt-form-submit' ).prop( 'disabled', false );
	};

	/** @var null|bool */
	self.isWpEditorAvailable = null;

	/**
	 * Check whether wp.editor is available.
	 *
	 * @return bool
	 */
	self.checkWpEditorAvailable = function() {
		if ( null == self.isWpEditorAvailable ) {
			self.isWpEditorAvailable = (
				_.has( window, 'wp' )
				&& _.has( window.wp, 'editor' )
				&& _.has( window.wp.editor, 'remove' )
				&& _.has( window.wp.editor, 'initialize' )
			);
		}
		return self.isWpEditorAvailable;
	};

	/** @var null|bool */
	self.isMceInitAvailable = null;

	/**
	 * Check whether window.tinyMCEPreInit.mceInit is available.
	 *
	 * @return bool
	 */
	self.checkMceInitAvailable = function() {
		if ( null === self.isMceInitAvailable ) {
			self.isMceInitAvailable = (
				_.has( window, 'tinyMCEPreInit' )
				&& _.has( window.tinyMCEPreInit, 'mceInit' )
			);
		}
		return self.isMceInitAvailable;
	};

	/** @var null|bool */
	self.isQInitAvailable = null;

	/**
	 * Check whether window.tinyMCEPreInit.qtInit is available.
	 *
	 * @return bool
	 */
	self.checkQInitAvailable = function() {
		if ( null === self.isQInitAvailable ) {
			self.isQInitAvailable = (
				_.has( window, 'tinyMCEPreInit' )
				&& _.has( window.tinyMCEPreInit, 'qtInit' )
			);
		}
		return self.isQInitAvailable;
	};

	/**
	 * @param object $form
	 */
	self.reloadTinyMCE = function( $form ) {
		if ( 0 == $( 'textarea.wpt-wysiwyg', $form ).length ) {
			return;
		}
		$( 'textarea.wpt-wysiwyg', $form ).each( function( index ) {
			var $area = $( this ),
				area_id = $area.prop('id');

			if ( self.checkWpEditorAvailable() ) {
				// WordPress over 4.8, hence wp.editor is available and included
				wp.editor.remove( area_id );
				var tinymceSettings = (
						self.checkMceInitAvailable()
						&& _.has( window.tinyMCEPreInit.mceInit, area_id )
					) ? window.tinyMCEPreInit.mceInit[ area_id ] : true,
					qtSettings = (
						self.checkQInitAvailable()
						&& _.has( window.tinyMCEPreInit.qtInit, area_id )
					) ? window.tinyMCEPreInit.qtInit[ area_id ] : true,
					hasMediaButton = ! $( 'textarea#' + area_id ).hasClass( 'js-toolset-wysiwyg-skip-media' ),
					hasToolsetButton = ! $( 'textarea#' + area_id ).hasClass( 'js-toolset-wysiwyg-skip-toolset' ),
					mediaButtonsSettings = ( hasMediaButton || hasToolsetButton );

				wp.editor.initialize( area_id, { tinymce: tinymceSettings, quicktags: qtSettings, mediaButtons: mediaButtonsSettings } );

				if ( mediaButtonsSettings ) {
					var $mediaButtonsContainer = $( '#wp-' + area_id + '-wrap .wp-media-buttons' );
					$mediaButtonsContainer.attr( 'id', 'wp-' + area_id + '-media-buttons' );

					if ( ! hasMediaButton ) {
						$mediaButtonsContainer.find( '.insert-media.add_media' ).remove();
					}
					if ( hasToolsetButton ) {
						/**
						 * Broadcasts that the WYSIWYG field initialization was completed,
						 * only if the WYSIWYG field should include Toolset buttons.
						 *
						 * @param {string} area_id The underlying textarea id attribute
						 *
						 * @event toolset:forms:wysiwygFieldInited
						 *
						 * @since 2.1.2
						 */
						$( document ).trigger( 'toolset:forms:wysiwygFieldInited', [ area_id ] );
					}
				}

			} else {
				// WordPress below 4.8, hence wp-editor is not available
				// so we turn those fields into simple textareas
				$( '#wp-' + area_id + '-editor-tools' ).remove();
				$( '#wp-' + area_id + '-editor-container' )
					.removeClass( 'wp-editor-container' )
					.find( '.mce-container' )
					.remove();
				$( '#qt_' + area_id + '_toolbar' ).remove();
				$( '#' + area_id )
					.removeClass( 'wp-editor-area' )
					.show()
					.css( { width: '100%' } );
			}
		});
	};

	/**
	 * @param object $form
	 */
	self.maybeReloadReCAPTCHA = function( $form ) {
		if ( typeof grecaptcha !== 'undefined' ) {
			var formID = $form.attr( 'id' );
			var $recaptcha_selector = $form.find( 'div.g-recaptcha' );
			if ( $recaptcha_selector.length ) {
				var _sitekey = $recaptcha_selector.data( 'sitekey' );
				if ( typeof _sitekey !== 'undefined' ) {
					var recaptcha_widget_id = grecaptcha.render( $recaptcha_selector.attr( 'id' ), { sitekey: _sitekey } );
					Toolset.CRED.Frontend.RecaptchaWidgetIds[ formID ] = recaptcha_widget_id;
				}
			}
		}
	};

	/**
	 * @param object $form
	 */
	self.handleReCAPTCHAErrorMessage = function( $form ) {
		if ( typeof grecaptcha !== 'undefined' ) {
			var $error_selector = $form.find( 'div.recaptcha_error' );
			var formID = $form.attr( 'id' );
			if ( typeof Toolset.CRED.Frontend.RecaptchaWidgetIds[ formID ] !== 'undefined' ) {
				if ( grecaptcha.getResponse( Toolset.CRED.Frontend.RecaptchaWidgetIds[ formID ] ) == '' ) {
					$error_selector.show();
					setTimeout( function () {
						$error_selector.hide();
					}, 5000 );
					return false;
				} else {
					//reset recapatcha widget_id
					Toolset.CRED.Frontend.RecaptchaWidgetIds[ formID ] = undefined;
				}
			}
			$error_selector.hide();
		}
		return true;
	};

	/**
	 * Restart the repetitive fields.
	 *
	 * Note that this has no form scope: this restarts all repetitive fields on the page :-/
	 *
	 * @param object $form
	 */
	self.maybeReloadRepetitive = function( $form ) {
		if ( typeof wptRep !== 'undefined' ) {
			wptRep.init();
		}
	};

	/**
	 * Restart conditional bindings.
	 *
	 * @param {object} $form
	 */
	self.maybeReloadConditionals = function( $form ) {
		if ( undefined == $form.attr( 'data-conditionals' ) ) {
			return;
		}

		if ( typeof( wptCond ) !== 'undefined' ) {
			wptCond.initPartial( $form.data( 'conditionals' ) );
		}
	};

	/**
	 * @param object $form
	 */
	self.destroyForm = function( $form ) {
		// Remove leftover validation marks, if any.
		$form.find( '.js-wpt-validate' ).removeClass( 'js-wpt-validate' );
	};

	/**
	 * Initialize a given form:
	 * - Cleanup leftovers from validatin, if any.
	 * - Init assets: datepicker, colorpicker.
	 * - Init validation, hence AJAX submit if needed.
	 * - Init taxonomy fields, if needed.
	 * - Init accessibility labels, if needed.
	 * - Make the form submit-able.
	 * - Check whether on preview mode.
	 *
	 * @param string $formId
	 */
	self.initForm = function( $formId ) {
		var $form = $( '#' + $formId );

		self.destroyForm( $form );

		self.initDatePicker( $form );
		self.initColorPicker( $form );
		self.initValidation( $form );
		self.taxonomyManager.initForm( $form );
		self.initAccessibilityLabels( $form );
		self.enableSubmitForm( $form );

		self.maybeInitPreviewMode();
	};

	/**
	 * Reload special elements after an AJAX event:
	 * - Reload TinyMCE instances.
	 * - Reload Recaptcha instances.
	 * - Reload repetitive fields management.
	 * - Reload conditionals management.
	 *
	 * @param string $formId
	 */
	self.reloadFormSelectedItems = function( $formId ) {
		var $form = $( '#' + $formId );

		self.reloadTinyMCE( $form );
		self.maybeReloadReCAPTCHA( $form );
		self.maybeReloadRepetitive( $form );
		self.maybeReloadConditionals( $form );
	};

	/**
	 * Tell the world that the form is ready.
	 *
	 * @param string $formId
	 */
	self.broadcastReadyForm = function( $formId ) {
		$( document ).trigger( 'cred_form_ready', {
			form_id: $formId
		});
	};

	/**
	 * When the AJAX form is submited:
	 * - Disable the submit button.
	 *
	 * @param string formIdSelector
	 * @param bool isValidForm
	 * @param object $formSettings
	 */
	 self.onAjaxFormSubmitted = function( formIdSelector, isValidForm, $formSettings ) {
		var $form = $( formIdSelector );
		if ( isValidForm ) {
			$form.find( '.wpt-form-submit' ).prop( 'disabled', true );
		}
	};

	/**
	 * When form validation succeded:
	 * - If it an AJAX form, submit it.
	 * - if it is not an AJAX form, disable the submit button.
	 *
	 * @param string formIdSelector
	 * @param bool isAjaxForm
	 * @param object $formSettings
	 * @since 1.9.3
	 * @since 2.4 Manage both AJAX and non AJAX submission for valid forms
	 */
	 self.onValidatedSubmitForm = function( formIdSelector, isAjaxForm, $formSettings ) {
		var $form = $( formIdSelector );

		if ( isAjaxForm ) {
			$( '<input value="true" name="form_submit">' )
				.attr( 'type', 'hidden' )
				.appendTo( formIdSelector );

			$( 'body' ).addClass( 'wpt-loading' );
			$form.find( '.wpt-form-submit' )
				.after( '<span class="loading-spinner js-toolset-forms-loading-spinner" style="margin-left:5px;"><img class="cred-form-loading-spinner-image" src="' + self.i18n.submit.spinner + '"></span>' );

			if (
				_.has( window, 'tinyMCE' )
				&& _.has( window.tinyMCE, 'triggerSave' )
			) {
				// This will refresh the value of all tinyMCE instances of the page:
				// better too much than too little!
				window.tinyMCE.triggerSave();
			}

			var $formData = $form.data( 'form' );

			$( formIdSelector ).ajaxSubmit({
				url: self.i18n.ajaxurl,
				data: {
					action: self.i18n.submit.action,
					wpnonce: self.i18n.submit.nonce,
					lang: self.i18n.lang,
					currentGet: self.i18n.currentGet,
					wpvPage: $formData.wpv_page
				},
				dataType: 'json',
				success: function( response ) {
					$form.replaceWith( response.data.output );
					if ( 'ok' === response.data.result ) {
						/**
						 * The AJAX form was successfully submitted.
						 *
						 * @param string formIdSelector
						 * @since 1.9.3
						 */
						Toolset.hooks.doAction( 'cred_form_ajax_success', formIdSelector );
					} else {
						/**
						 * The AJAX form failed to submit.
						 *
						 * @param string formIdSelector
						 * @since 1.9.3
						 */
						Toolset.hooks.doAction( 'cred_form_ajax_error', formIdSelector );
					}
				},
				error: function() {
					/**
					 * The AJAX form failed to submit.
					 *
					 * @param string formIdSelector
					 * @since 1.9.3
					 */
					Toolset.hooks.doAction( 'cred_form_ajax_error', formIdSelector );
				},
				complete: function (response) {
					$( 'body' ).removeClass( 'wpt-loading' );
					$( '.js-toolset-forms-loading-spinner' ).remove();
					/**
					 * The AJAX form submission was completed, either successfully or failing.
					 *
					 * @param string formIdSelector
					 * @since 1.9.3
					 */
					Toolset.hooks.doAction( 'cred_form_ajax_completed', formIdSelector );
				}
			});
		} else {
			// Non AJAX form already validated and ready to be submitted:
			// flag the submit button so it can not trigger the process again
			// and we avoid multiple form submission on fast clicks.
			$form
				.find( '.wpt-form-submit' )
				.addClass( 'js-wpt-form-submitting' );
		}
	};

	/**
	 * When AJAX submission completed:
	 * - Init the form.
	 * - Reload specific elements.
	 * - Tell the world that the form is ready.
	 *
	 * @param string formIdSelector
	 */
	self.onAjaxFormSubmitCompleted = function( formIdSelector ) {
		var $form = $( formIdSelector );
		var $formId = $form.attr( 'id' );
		self.initForm( $formId );
		self.reloadFormSelectedItems( $formId );
		self.broadcastReadyForm( $formId );
	};

	/**
	 * Init all forms.
	 */
	self.initAllForms = function() {
		var thiz = this;

		$( '.cred-form, .cred-user-form' ).each( function() {
			var $formId = $( this ).attr('id');
			thiz.initForm( $formId );
			thiz.broadcastReadyForm( $formId );
		});
	};

	/**
	 * Init Toolset hooks.
	 */
	self.initHooks = function() {
		Toolset.hooks.addAction( 'toolset-ajax-submit', self.onAjaxFormSubmitted );
		Toolset.hooks.addAction( 'toolset-form-onsubmit-validation-success', self.onValidatedSubmitForm );
		Toolset.hooks.addAction( 'cred_form_ajax_completed', self.onAjaxFormSubmitCompleted );
	};

	/**
	 * Init compatibility with Toolset Views.
	 */
	self.initViewsCompatibility = function() {
		$( document ).on( 'js_event_wpv_pagination_completed js_event_wpv_parametric_search_results_updated', function( event, data ) {
			$( '.cred-form, .cred-user-form', data.layout ).each( function() {
				var $form = jQuery( this );
				var $formId = $form.attr( 'id' );
				self.initForm( $formId );
				self.reloadFormSelectedItems( $formId );
				self.broadcastReadyForm( $formId );
			});
		});
	};

	/**
	 * Init events.
	 */
	self.initEvents = function() {
		/**
		 * Halts multiple clicks on validated non AJAX forms,
		 * by halting the submit button click.
		 *
		 * @since 2.4
		 */
		$( document ).on( 'click', '.js-wpt-form-submitting', function( e ) {
			e.preventDefault();
		});

		/**
		 * Sets the right post ID for the media related fields using the Media manager.
		 */
		$( document ).on( 'click', 'form.cred-form .wp-media-buttons > .button.insert-media.add_media, form.cred-user-form .wp-media-buttons > .button.insert-media.add_media', function () {
			if (
				wp
				&& wp.hasOwnProperty( 'media' )
			) {
				var $current_form = $( this ).closest( 'form' );
				var current_cred_form_post_id = $( "input[name='_cred_cred_prefix_post_id']", $current_form ).val();
				if (
					$current_form
					&& current_cred_form_post_id
					&& wp.media.model.settings.post.id !== current_cred_form_post_id
				) {
					wp.media.model.settings.post.id = current_cred_form_post_id;
				}
			}
		});

		// Actions after a form has been declared ready.
		$( document ).on( 'cred_form_ready', function ( evt, form_data ) {
			var $form = $( "#" + form_data.form_id );

			//uncheck generic checkboxes
			$form.find( 'input[type="checkbox"][cred_generic="1"]' ).each( function( index, checkbox ) {
				if ( $( checkbox ).attr('default_checked') != 1 ) {
					$(  checkbox ).prop( 'checked', false );
				} else {
					$( checkbox ).prop( 'checked', true );
				}
			});

			// This probably belongs to the form initialization, not here!
			$form.on( 'submit', function () {
				//If recaptcha is not valid stops the submit
				if ( ! self.handleReCAPTCHAErrorMessage( $( this ) ) ) {
					return false;
				}
			});

			/**
			 * Re-select flat taxonomy terms on forms that failed submission.
			 */
			if ( $form.hasClass( 'is_submitted' ) ) {
				$form.find( '.cred-taxonomy' ).each( function () {
					var $parent = $( this );
					setTimeout( function () {
						$( 'input.wpt-taxonomy-add-new', $parent ).trigger( 'click' );
					}, 50 );
				});
			}
		});

		/**
		 * Prevents some keyboard interaction for Enter and Backspace keys inside forms.
		 */
		$( document ).on( 'keydown', function( event ) {
			if (
				'Backspace' != event.key
				&& 'Enter' != event.key
			) {
				return true;
			}

			if ( 0 == $( event.target ).closest( 'form.cred-form, form.cred-user-form' ).length ) {
				return true;
			}

			var keyStop = {
				"Backspace": ":not(input:text, textarea,  input:file, input:password )", // stop backspace = back
				"Enter": "input:text, input:password" // stop enter = submit
			};

			if ( $( event.target ).is( keyStop[ event.key ] ) ) {
				event.preventDefault();
			}

			return true;
		});
	};

	/**
	 * Inits backwards compatibility: fills the global credFrontEndViewModel object.
	 */
	self.initBackwardsCompatibility = function() {
		credFrontEndViewModel.tryToReloadReCAPTCHA = self.maybeReloadReCAPTCHA;
		credFrontEndViewModel.handleReCAPTCHAErrorMessage = self.handleReCAPTCHAErrorMessage;
		credFrontEndViewModel.disableSubmitForm = self.onAjaxFormSubmitted;
		credFrontEndViewModel.enableSubmitForm = self.enableSubmitForm;
		credFrontEndViewModel.checkWpEditorAvailable = self.checkWpEditorAvailable;
		credFrontEndViewModel.checkMceInitAvailable = self.checkMceInitAvailable;
		credFrontEndViewModel.checkQInitAvailable = self.checkQInitAvailable;
		credFrontEndViewModel.reloadTinyMCE = self.reloadTinyMCE;
		credFrontEndViewModel.onValidatedSubmitForm = self.onValidatedSubmitForm;
		credFrontEndViewModel.startLoading = function() {};
		credFrontEndViewModel.stopLoading = function() {};
		credFrontEndViewModel.onAjaxFormSubmit = self.onAjaxFormSubmitCompleted;
		credFrontEndViewModel.getAllForms = function() {
			var formIds = [];
			jQuery('.cred-form, .cred-user-form', document ).each( function() {
				formIds.push( jQuery( this ).attr('id') );
			});
			return formIds;
		};
		credFrontEndViewModel.uniqueID = self.uniqueID;
		credFrontEndViewModel.addAccessibilityIDs = self.addAccessibilityIDs;
		credFrontEndViewModel.setFormsReady = self.initAllForms;
		credFrontEndViewModel.activatePreviewMode = self.activatePreviewMode;
		credFrontEndViewModel.initColorPicker = self.initColorPicker;
	};

	/**
	 * Forces some styles for taxonomy terms suggest results.
	 */
	self.initExtraStyles = function() {
		$( 'head' ).append( '<style>.wpt-suggest-taxonomy-term{position:absolute;display:none;min-width:100px;outline:#ccc solid 1px;padding:0;background-color:Window;overflow:hidden}.wpt-suggest-taxonomy-term li{margin:0;padding:2px 5px;cursor:pointer;display:block;width:100%;font:menu;font-size:12px;overflow:hidden}.wpt-suggest-taxonomy-term-select{background-color:Highlight;color:HighlightText}</style>' );
	}

	/** @var Toolset.CRED.Frontend.Taxonomy */
	self.taxonomyManager = null;

	/**
	 * Inits the module.
	 */
	self.init = function() {
		self.taxonomyManager = new Toolset.CRED.Frontend.Taxonomy( $ );
		self.initHooks();
		self.initViewsCompatibility();
		self.initEvents();
		self.initAllForms();
		self.initBackwardsCompatibility();
		self.initExtraStyles();
	};

	self.init();
};

/**
 * Manager for frontend delete links.
 */
Toolset.CRED.Frontend.Delete = function( $ ) {

	var self = this;

	self.i18n = cred_frontend_i18n;

	self.selector = '.js-cred-delete-post';

	$( document ).on( 'click', self.selector, function( e ) {
		e.preventDefault();

		var $handle = $( this ),
			ajaxData = {
				action: self.i18n.deletePost.action,
				wpnonce: self.i18n.deletePost.nonce,
				credPostId: $handle.data( 'postid' ),
				credAction: $handle.data( 'action' ),
				credOnSuccess: $handle.data( 'onsuccess' )
			};

		var $spinner = $( '<span class=""><img src="' + self.i18n.spinner + '" /></span>' );
		$handle.replaceWith( $spinner );

		$.ajax({
			url: self.i18n.ajaxurl,
			data: ajaxData,
			dataType: 'json',
			type: "POST",
			success:  function( originalResponse ) {
				var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
				if ( response.success ) {
					self.doSuccess( response.data.onsuccess, $spinner );
				} else {
					self.doError( $spinner );
				}
			},
			error: function ( ajaxContext ) {
				self.doError( $spinner );
			}
		});
	});

	/**
	 * Perform the success action after deleting.
	 *
	 * @param string onsuccess Action to perform.
	 * @param $handle Reference to the object to act upon, if needed.
	 */
	self.doSuccess = function( onsuccess, $handle ) {
		switch ( onsuccess ) {
			case 'self':
				window.location.reload( true );
				break;
			case 'none':
			case '':
				$handle.hide();
				break;
			default:
				window.location = onsuccess;
				break;
		}
	}

	/**
	 * Perform the error action after deleting failed.
	 *
	 * @param $handle Reference to the object to act upon.
	 */
	self.doError = function( $handle ) {
		var $error = $( '<span class="cred-delete-post-error">' + self.i18n.deletePost.messages.error + '</span>' );
		$handle.replaceWith( $error );
	}

};

jQuery( function(){
	Toolset.CRED.Frontend.formsInstance = new Toolset.CRED.Frontend.Forms( jQuery );
	Toolset.CRED.Frontend.deleteInstance = new Toolset.CRED.Frontend.Delete( jQuery );
});
