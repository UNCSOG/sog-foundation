/**
 * Scaffold editor functions that need to work along different Forms developments: (Post and Users) and Relationships
 * Tries to avoid code duplication
 *
 * @since 2.3
 */

var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.ScaffoldEditor = function( shortcode_container ) {
	this.checkedRadioAttributeInputsTrack = {};
    this.shortcode_container = shortcode_container;
    this.scaffold_field_id = Toolset.hooks.applyFilters( 'cred_editor_scaffold_scaffold_field_id_attribute_name', '' ) || cred_post_form_content_editor_i18n.data.scaffold.scaffold_field_id; // In case hook is not defined yet :(
    this.initEvents();
};


/**
 * Craft the scaffold output.
 *
 * @since 2.1
 */
Toolset.CRED.ScaffoldEditor.prototype.craftScaffoldOutput = function() {
    var prevColNumber = 0;
    var columnCounter = 0;
    var numColumns = 0;
    var bootstrapClass = '';

    var $container = arguments.length === 1 ? arguments[0] : null;
    if ( $container && typeof $container !== 'object' ) {
        $container = jQuery( '#' + $container );
    }
    var currentInstance = this,
        output = '',
        $scaffoldList = jQuery( '.js-cred-editor-scaffold-item-list', $container ),
        formSlug = jQuery( '#slug, #post_name' ).val();

    output = '[' + this.shortcode_container + ']';

    var addLabel = function( attributes, label, slug ) {
		let extra = '';
		if ( attributes.isRepetitive ) {
			extra += '0';
		}
		var labelHTML = '<label for="%%FORM_ID%%_' + _.escape( slug ) + extra + '">';
		var finalLabel = attributes.label ? attributes.label : label;
		if ( finalLabel.match( /\[cred_i18n/ ) ) {
			labelHTML += finalLabel;
		} else {
			labelHTML += "[cred_i18n name='" + _.escape( slug ) + "-label']";
			labelHTML += finalLabel;
			labelHTML += '[/cred_i18n]';
		}
        labelHTML += '</label>';
        delete attributes.label;
        return labelHTML;
    }

    var generateFieldShortcode = function( $this, bootstrapClass ) {
        var shortcodeOutput = '';
        if ( ! $this.data( 'include' ) || $this.hasClass( 'cred-scaffold-conditional-child' ) ) {
            return '';
        }

        var shortcode = $this.data( 'shortcode' ),
            fieldType = $this.data( 'fieldtype' ),
            label = $this.data( 'label' ),
			attributes = currentInstance.getScaffoldShortcodeAttributes( $this ),
			content = null;

		if ( _.has( attributes, 'content' ) ) {
			content = attributes.content;
			attributes = _.omit( attributes, 'content' );
		}
        if ( 'cred_generic_field' === shortcode ) {
            attributes.type = attributes[ currentInstance.scaffold_field_id ];
        }

		attributes.isRepetitive = $this.data( 'repetitive' );

        var extraTabs = bootstrapClass ? "\t" : '';
        var openingBootstraDiv = bootstrapClass
            ? "\n\t" + extraTabs + '<div class="' + bootstrapClass + '">'
            : '';
        var closingBootstraDiv = bootstrapClass
            ? "\n\t" + extraTabs + '</div>'
            : '';

        var extraTabs = bootstrapClass ? "\t" : '';
        var openingBootstraDiv = bootstrapClass
            ? "\n\t" + extraTabs + '<div class="' + bootstrapClass + '">'
            : '';
        var closingBootstraDiv = bootstrapClass
            ? "\n\t" + extraTabs + '</div>'
			: '';

		var fromGroupClassname = bootstrapClass
			? 'form-group ' + bootstrapClass
			: 'form-group';

        if ( 'media' === attributes[ currentInstance.scaffold_field_id ] ) {
            shortcodeOutput += openingBootstraDiv;
            shortcodeOutput += "\n\t" + extraTabs + ( attributes.value || '' );
            shortcodeOutput += closingBootstraDiv;
        } else if ( 'html' === attributes[ currentInstance.scaffold_field_id ] ) {
            shortcodeOutput += openingBootstraDiv;
            shortcodeOutput += "\n\t" + extraTabs + extraTabs;

			const htmlPrefix = `[cred_i18n type="html" name="${ currentInstance.scaffold_field_id }"]`;
			const htmlSuffix = '[/cred_i18n]';
			if ( attributes.value ) {
				shortcodeOutput += `${ htmlPrefix }${ attributes.value }${ htmlSuffix }`;
			} else {
				var $htmlTextarea = $this.find( 'textarea.cred-scaffold-html-content' ),
					htmlTextareaId = $htmlTextarea.attr( 'id' );
				shortcodeOutput += _.has( WPV_Toolset.CodeMirror_instance, htmlTextareaId ) ? `${ htmlPrefix }${ WPV_Toolset.CodeMirror_instance[ htmlTextareaId ].getValue() }${ htmlSuffix }` : '';
			}

			shortcodeOutput += closingBootstraDiv;
        } else if ( 'conditionals' === attributes[ currentInstance.scaffold_field_id ] ) {
            var $item = $this;
            var $list = [];
            // Copying 'child' elements in a new element to generate conditional shortcode content.
            while ( $item.next().hasClass( 'cred-editor-scaffold-item-wrapper-row' ) ) {
                $item = $item.next();
                if ( $item.find( '.cred-scaffold-conditional-child' ).length ) {
                    $row = $item.clone();
                    $row.find( '.js-cred-editor-scaffold-item-container' ).each( function() {
                        $list.push( jQuery( this ).removeClass( 'cred-scaffold-conditional-child' ) );
                    } );
                }
            }
            var outputConditional = output;
            var prevColNumberConditional = prevColNumber;
            var columnCounterConditional = columnCounter;
            var numColumnsConditional = numColumns;
            prevColNumber = 0;
            columnCounter = 0;
            numColumns = 0;
            output = '';
            jQuery( $list ).each( addBootstrapTags );
            prevColNumber = prevColNumberConditional;
            columnCounter = columnCounterConditional;
            numColumns = numColumnsConditional;

            $this.data( "contentToWrap", output );
            output = outputConditional;
            shortcodeOutput += openingBootstraDiv;
            shortcodeOutput += "\n\t" + Toolset.hooks.applyFilters( 'cred-action-get-conditional-shortcode', $this );
            shortcodeOutput += closingBootstraDiv;
        } else if ( 'submit' === attributes[ currentInstance.scaffold_field_id ] && !! attributes.label ) {
			const filteredAttributes = Object.keys( attributes )
				.filter( key => ! [ 'field', 'name' ].includes( key ) )
				.reduce( ( obj, key ) => {
			  		obj[ key ] = attributes[ key ];
			  		return obj;
				}, {} );
			shortcodeOutput += "\n\t" + currentInstance.craftShortcodeString( shortcode, filteredAttributes, content, $this );
        } else if ( [ 'formElement', 'form-elements' ].includes( fieldType ) || [ 'cred_generic_field' ].includes( shortcode ) ) {
            var hasLabel = ! openingBootstraDiv && attributes.label;
            if ( hasLabel ) {
                shortcodeOutput += "\n\t" + extraTabs + '<div class="' + fromGroupClassname + '">';
            } else {
                shortcodeOutput += openingBootstraDiv;
            }
            shortcodeOutput += "\n\t" + extraTabs + extraTabs;
            if ( !!attributes.label ) {
                shortcodeOutput += addLabel( attributes, label, attributes.field ) + "\n\t" + extraTabs + extraTabs;
            }
            shortcodeOutput += currentInstance.craftShortcodeString( shortcode, attributes, content, $this );
            if ( hasLabel ) {
                shortcodeOutput += "\n\t" + extraTabs + '</div>';
            } else {
                shortcodeOutput += closingBootstraDiv;
            }
        } else {
			shortcodeOutput += "\n\t" + extraTabs + '<div class="' + fromGroupClassname + '">';
			let slug = '';
			if ( attributes.role ) {
				slug = attributes.role;
			} else if ( attributes.field ) {
				slug = attributes.field;
			} else if ( attributes.name ) {
				slug = attributes.name;
			}
            shortcodeOutput += "\n\t\t" + extraTabs + addLabel( attributes, label, slug );
            shortcodeOutput += "\n\t\t" + extraTabs + currentInstance.craftShortcodeString( shortcode, attributes, content, $this );
            shortcodeOutput += "\n\t" + extraTabs + '</div>';
        }
        return shortcodeOutput;
	};

    var renderGrid = Toolset.hooks.applyFilters( 'cred_editor_is_grid_enabled' ) && jQuery( '.cred-editor-scaffold-item-wrapper-row[data-cred-cols!=1]' ).length;
    if ( renderGrid ) {
        output += "\n" + '<div class="container-fluid">';
    }
    var addBootstrapTags = function() {
        var $cell = jQuery( this );
        var $itemWrapper = $cell.closest( '.cred-editor-scaffold-item-wrapper-item' );
        if ( ! $itemWrapper.length ) {
            output += generateFieldShortcode( jQuery( this ), '' );
            return;
        }
        var sizeColumn = Number( $itemWrapper[0].dataset.credFieldCols || 1 );
        if ( renderGrid && ! $cell.hasClass( 'cred-scaffold-conditional-child' ) ) {
            numColumns = Number( $cell.closest( '.cred-editor-scaffold-item-wrapper-row' )[0].dataset.credCols || 1 );
            numColumns = Number( numColumns );
            if ( numColumns === columnCounter || numColumns !== prevColNumber ) {
                columnCounter = 0;
                output += "\n\t" + '<div class="row">';
            }
            var bootstrapVersion = Toolset.hooks.applyFilters( 'cred_editor_get_bootstrap_version' );
            bootstrapClass = ( 4 === bootstrapVersion ? 'col-md-' : 'col-sm-' ) + ( 12 * sizeColumn / numColumns );
        }
        output += generateFieldShortcode( jQuery( this ), bootstrapClass );
        if ( renderGrid && ! $cell.hasClass( 'cred-scaffold-conditional-child' ) ) {
            columnCounter += sizeColumn;
            if ( numColumns === columnCounter ) {
                output += "\n\t" + '</div>';
            }
            prevColNumber = numColumns;
        }
        bootstrapClass = '';
    };
    jQuery( '.js-cred-editor-scaffold-item-container:not(.cred-editor-scaffold-item-deleted)', $scaffoldList ).each( addBootstrapTags );
    if ( renderGrid ) {
        output += "\n" + '</div>';
    }

    output += "\n" + '[/' + this.shortcode_container + ']';

    return output;
};


/**
 * Get the attributes for a scaffold item.
 *
 * @param $scaffoldItem jQuery object
 *
 * @return object
 *
 * @since 2.1
 */
Toolset.CRED.ScaffoldEditor.prototype.getScaffoldShortcodeAttributes = function( $scaffoldItem ) {
    var attributes = $scaffoldItem.data( 'attributes' );
    var attrAttributes = $scaffoldItem.attr( 'data-attributes' ); // jQuery data can override attr data
    if ( attrAttributes ) {
        attributes = Object.assign( attributes, JSON.parse( attrAttributes ) );
    }
    var currentInstance = this;

    attributes = ( _.size( attributes ) == 0 ) ? {} : attributes;

    if ( $scaffoldItem.find( '.js-cred-editor-scaffold-item-options' ).length > 0 ) {
        jQuery( '.js-toolset-shortcode-gui-attribute-wrapper', $scaffoldItem ).each( function() {
			var attributeWrapper = jQuery( this ),
				$shortodeWrapper = attributeWrapper.closest( '.js-cred-editor-scaffold-item-options' ),
                shortcodeAttributeKey = attributeWrapper.data( 'attribute' ),
                shortcodeAttributeValue = '',
                shortcodeAttributeDefaultValue = attributeWrapper.data( 'default' );
            switch ( attributeWrapper.data('type') ) {
                case 'select':
                    shortcodeAttributeValue = jQuery('option:checked', attributeWrapper ).val();
                    break;
                case 'radio':
                    shortcodeAttributeValue = jQuery('input:checked', attributeWrapper ).val();
                    break;
                case 'checkbox':
                    shortcodeAttributeValue = jQuery('input:checked', attributeWrapper ).val();
                    break;
                case 'textarea':
				case 'content':
                    shortcodeAttributeValue = jQuery('textarea', attributeWrapper ).val();
                    break;
                case 'conditionals':
                    shortcodeAttributeValue = currentInstance.getConditionalAttributes( $scaffoldItem );
                    break;
				case 'html':
					var $htmlTextarea = attributeWrapper.find( 'textarea.cred-scaffold-html-content' ),
						htmlTextareaId = $htmlTextarea.attr( 'id' );
					shortcodeAttributeValue += _.has( WPV_Toolset.CodeMirror_instance, htmlTextareaId ) ? WPV_Toolset.CodeMirror_instance[ htmlTextareaId ].getValue() : '';
                    break;
                default:
                    shortcodeAttributeValue = jQuery('input', attributeWrapper ).val();
            };


            if ( shortcodeAttributeKey === 'manual' ) {
				shortcodeAttributeKey = 'options';
				var attributeSource = $shortodeWrapper.find( 'input[name="cred_generic_field-source"]:checked' ).val();
				switch ( attributeSource ) {
					case 'shortcode':
						shortcodeAttributeValue = $shortodeWrapper.find( '[id="cred_generic_field-shortcode"]' ).val();
						break;
					default:
						var defaultOptions = [];
						// Removing default values and moving to a new attribute
						if ( shortcodeAttributeValue ) {
							if ( shortcodeAttributeValue[0] === '{' ) {
								shortcodeAttributeValue = '[' + shortcodeAttributeValue + ']';
							}
							shortcodeAttributeValue = JSON.stringify(
								JSON.parse( shortcodeAttributeValue )
									.map( function( option ) {
										if ( option.default ) {
											defaultOptions.push( option.value );
										}
										delete option['default'];
										return option;
									})
								);
						}
						if ( defaultOptions.length ) {
							attributes[ 'default' ] = defaultOptions;
						}
						break;
				}
			}
			if (
				false === shortcodeAttributeValue
				|| null === shortcodeAttributeValue
				|| undefined === shortcodeAttributeValue
			) {
				// Remove broken attributes
				attributes = _.omit( attributes, shortcodeAttributeKey );
			} else if ( [ 'source', 'required', 'default' ].includes( shortcodeAttributeKey ) ) {
				// Force in not broken attributes for generic fields
				attributes[ shortcodeAttributeKey ] = shortcodeAttributeValue;
			} else if (
				// Exclude the rest of the default attribute values
				'' === shortcodeAttributeValue
				|| shortcodeAttributeValue === shortcodeAttributeDefaultValue
			) {
				attributes = _.omit( attributes, shortcodeAttributeKey );
			} else {
				// Add the remaining
				attributes[ shortcodeAttributeKey ] = shortcodeAttributeValue;
			}
        });
    }

    return attributes;
};


/**
 * Compose a shortcode given its handle and its attributs in an object, when inserting the scaffold.
 *
 * @param shortcode  string
 * @param rawAttributes object
 * @param content null|string
 * @param fieldType string
 * @param {Object} $container jQuery object that contains the shortcode generator GUI
 *
 * @return string
 *
 * @since 2.3
 * @since 2.3.2 Apply the canonical Toolset filters over the attributes and the generated shortcode.
 */
Toolset.CRED.ScaffoldEditor.prototype.craftShortcodeString = function( shortcode, rawAttributes, content, $container ) {
	var output = '[' + shortcode,
		// Normalize the attributes
		attributes = Toolset.hooks.applyFilters(
			'toolset-filter-shortcode-gui-' + shortcode + '-computed-attribute-values',
			jQuery.extend( true, {}, rawAttributes ),
			{
				shortcode: shortcode,
				rawAttributes: rawAttributes
			}
		);

    _.each( attributes, function( value, key, list ) {
        if ( value ) {
            output += ' ' + key + '=\'' + value + '\''; // JSON data uses double quotes
        }
    });
	output += ']';

	if ( null != content ) {
		output += content;
		output += '[/'+ shortcode + ']';
	}

	// Normalize the generated shortcodes
    output = Toolset.hooks.applyFilters( 'toolset-filter-shortcode-gui-' + shortcode + '-crafted-shortcode', output, { shortcode: shortcode, attributes: attributes, rawAttributes: rawAttributes, container: $container } );

    // Normalizing output.
    output = output.replace( new RegExp( '\\s' + this.scaffold_field_id + '=\'[^\']*\'' ), '' );
    // Removing shortcode attributes contained in the JSON data
    var jsonData = output
        .replace( /\[(\w|\/).*(\w|')]/g, '' ) // JSON, containing arrays, inside attributes can be confused with shorcodes
        .trim();
    if ( jsonData ) {
        try {
            jsonData.match(/"([^"]+?)":/g) // Gets "slug": | JSON parsing is more complicated due to getting the right JSON data
                .map( function( m ) {
                    return m.replace(/"([^"]+)":/, '$1');  // Gets the slug from the previous step
                })
                .concat( this.scaffold_field_id ) // Needs to be removed, it is only for internal use
                .forEach( function( key ) {
                    output = output.replace(new RegExp( '\\s' + key + '=\'[^\']*\'', 'g' ), '' ); // Remove the shortcode attribute
                });
        } catch ( e ) {}
    }

    return output;
};


/**
 * Craft the scaffold output.
 *
 * @since 2.3
 * @since 2.3.2 Add an optional container argument so we can craft the scaffold properly
 *     when leaving the wizard on relationship forms.
 */
Toolset.CRED.ScaffoldEditor.prototype.scaffoldToJSON = function() {
	var $container = arguments.length === 1 ? arguments[0] : jQuery( 'body' );

    var currentInstance = this,
        result = { fields: [], options: [] },
        $scaffoldList = jQuery( '.js-cred-editor-scaffold-item-list', $container );

    jQuery( '.js-cred-editor-scaffold-item-container', $scaffoldList ).each( function() {
        var $item = jQuery( this ),
			rawAttributes = currentInstance.getScaffoldShortcodeAttributes( $item ),
			attributes = jQuery.extend( true, {}, rawAttributes );

        attributes.isNested = $item.hasClass( 'cred-scaffold-conditional-child' );
        attributes.numColumns = $item.closest( '.cred-editor-scaffold-item-wrapper-row' ).data( 'credCols' ) || 1;
        attributes.sizeColumn = $item.closest( '.cred-editor-scaffold-item-wrapper-item' ).data( 'credFieldCols' ) || 1;
        attributes.fieldtype = $item.data( 'fieldtype' );
        result.fields.push( attributes );
    } );

    // Group nested items
    var conditionalItem = null;
    var organizedFields = []
    result.fields.forEach( function( field, index ) {
        if ( field[ currentInstance.scaffold_field_id ] === 'conditionals' ) {
            conditionalItem = field;
            conditionalItem.children = [];
            organizedFields.push( conditionalItem );
        } else if ( field.isNested ) {
            conditionalItem.children.push( Object.assign( {}, field ) );
            result.fields = result.fields.filter( function( elem, i ) {
                return i != index;
            });
        } else {
            organizedFields.push( field );
        }
    });
    result.fields = organizedFields;

    jQuery( '.js-cred-editor-wrap-scaffold-options input:checked', $container ).each( function() {
        var optionName = this.classList[0].replace(/.*options-/, '');
        result.options.push( optionName );
    } );

    return result;
}

/**
 * Set initially scaffold items
 * It is a static function so it can be called with creating a new instance
 *
 * Note that this is called every time that the editing mode is switched onto drag&drop:
 * we are not initializing the editor unless it is on the right mode, and the scaffold container is visible.
 *
 * @param {Array} fields List of fields saved
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.setInitialScaffoldItems = function( scaffoldData, fields ) {
	var currentInstance = this;
	var missedFields = [];
	var isExpertMode = jQuery( '#cred-editor-expert-mode-switcher' ).is( ':checked' );
    var $container = isExpertMode ? jQuery() : jQuery( '.cred-editor-scaffold-general-container' );
	var $fieldsContainer = isExpertMode ? jQuery() : jQuery('.js-cred-editor-scaffold-item-list');
    // This static method is called in cred.js before Toolset.CRED.ScaffoldEditor were instanced
    if ( !currentInstance.scaffold_field_id ) {
        currentInstance.scaffold_field_id = Toolset.hooks.applyFilters( 'cred_editor_scaffold_scaffold_field_id_attribute_name', '' ) || cred_post_form_content_editor_i18n.data.scaffold.scaffold_field_id;
    }
    var handleField = function( field, position ) {
        if ( !field[ currentInstance.scaffold_field_id ] ) {
            field[ currentInstance.scaffold_field_id ] = !!field.type
                ? field.type
                : field.field;
        }
        var fieldName = field[ currentInstance.scaffold_field_id ] || field.field || field.name || field.role;
        var fieldAttribute = field.role
            ? 'role'
            : currentInstance.scaffold_field_id;
        var fieldSelector = 'div[data-attributes*=\'"' + fieldAttribute + '":"' + fieldName + '"\'][data-fieldtype=' + field.fieldtype + ']:not([data-blocked="true"])';
        var $field;
        if ( [ 'media', 'html', 'conditionals' ].includes( field[ currentInstance.scaffold_field_id ] )
                || ( jQuery( '.cred-editor-scaffold-generic-list [data-' + currentInstance.scaffold_field_id + '="' + field[ currentInstance.scaffold_field_id ] +'"]' ).length
                    && field.fieldtype === 'generic' ) ) {
            // Media can be duplicated so it must be selected from the sidebar
            $field = $container.find( '.cred-editor-scaffold-sidebar ' + fieldSelector );
        } else {
            // It needs to find form-included elements before sidebar ones.
            $field = $container.find( '#js-cred-editor-scaffold-item-list-container ' + fieldSelector );
            if ( ! $field.length ) {
                $field = $container.find( fieldSelector );
            }
        }

        if ( $field.length ) {
            $field.attr('data-existing', 'true');
            if ( $field.data( 'permanent' ) ) {
                $field = $field.clone();

				/**
				 * Initialize tippy.js and make sure the tooltip really should be there (depends on context)
				 */
                $field.find( '.js-cred-editor-tippy' ).each( function() {
					OTGSUI.otgsPopoverTooltip.initSingle( this );
					this._tippy.set( {
						onShow: function( tip ) {
							Toolset.CRED.ScaffoldEditor.maybeShowTooltip( tip );
						}
					} );
                } );

                Toolset.hooks.doAction( 'cred_editor_scaffold_do_knockout_binding', $field[0] );
            }
            $fieldsContainer.append( $field );
        } else {
            missedFields.push( { field: field[ currentInstance.scaffold_field_id ], position: position } );
        }
        // Setting shortcode attributes
        Object.keys( field ).forEach( function( key ) {
            if ( key === 'options' ||  key === 'manual' ) {
                if ( !! field[ key ] ) {
                    var template = wp.template( 'cred-editor-generic-fields-options-manual-row' );
                    var $tableOptionsContainer = $field.find( '.js-cred-editor-generic-fields-options-manual-table tbody' );
					$tableOptionsContainer.html( '' );
					$field.find( '[id=cred_generic_field-manual]' ).val( field[ key ] );
					switch ( field.source ) {
						case 'shortcode':
							$field.find( '[id=cred_generic_field-shortcode]' ).val( field[ key ] );
							break;
						default:
							var options = typeof field[ key ] === 'string' ? JSON.parse( field[ key ] ) : field[ key ];
							options.forEach( function( option ) {
								$tableOptionsContainer.append( template( { type: field[ currentInstance.scaffold_field_id ] } ) );
								$newRow = $tableOptionsContainer.children().last();
								Object.keys( option ).forEach( function( optionAttribute ) {
									var $element = $newRow.find( '.js-cred-editor-generic-fields-options-manual-row-' + optionAttribute + ' input' );
									$element.prop( 'value', option[ optionAttribute ] ).trigger( 'change' );
								});
							});
							if ( !! field['default'] ) {
								var defaultOptions = typeof field['default'] === 'string' ? JSON.parse( field['default'] ) : field['default'];
								$tableOptionsContainer.find( '.js-cred-editor-generic-fields-options-manual-row-value input[type=text]' ).each( function() {
									var $inputElement = jQuery( this );
									if ( defaultOptions.includes( $inputElement.val() ) ) {
										$defaultElement = $inputElement.parents( 'tr' ).first().find( '.js-cred-editor-generic-fields-options-manual-row-default input' ).prop( 'checked', 'checked' ).trigger( 'change' );
									}
								})
							}
							break;
					}
                }
			} else if ( key === 'conditionals' ) {
				var conditionals = field[ key ];
				if ( !conditionals.useGui ) {
					$field.find( '.js-cred-editor-conditional-groups-expression-switcher').trigger( 'click' );
					$field.find( '[id=cred-editor-conditional-groups-custom-expressions]' ).val( conditionals.custom );
				} else {
					conditionals.conditions.forEach( function( condition, i ) {
						if ( i ) { // Needs more conditions, first is loaded
							Toolset.hooks.doAction( 'cred-action-add-conditional-row', $field );
						}
						Object.keys( condition ).forEach( function( conditionKey ) {
							$field.find( '.cred-editor-conditional-groups-' + conditionKey ).last().find( 'input, select').val( condition[ conditionKey ] );
						});
					});
				}
            } else if ( key === 'children' ) {
                field.children.forEach( handleField );
            } else if ( key === 'isNested' ) {
                $field.toggleClass( 'cred-scaffold-conditional-child', field[ key ] );
            } else if ( key === 'numColumns' ) {
                $field.data( 'numColumns', field[ key ] );
            } else if ( key === 'sizeColumn' ) {
                $field.data( 'sizeColumn', field[ key ] );
            } else if ( key === 'value' && 'html' === field[ currentInstance.scaffold_field_id ] ) {
				var $textarea = $field.find( 'textarea' );
                $textarea.val( field[ key ] );
            } else {
                var value = field[ key ];
                if ( ! value || typeof value !== 'string' ) {
                    return;
                }
                var escapedValue = value.replace(/"/g, '\\"');
                var $shortcodeInput = $field.find( '.toolset-shortcode-gui-attribute-wrapper[data-attribute="' + key + '"]').find('input[type=text], input[type=hidden], input[type=radio][value="' + escapedValue + '"], input[type=checkbox][value="' + escapedValue + '"], select, textarea' );
                if ( $shortcodeInput.length ) {
                    if ( $shortcodeInput.is(':not(:radio,:checkbox)') ) {
                        $shortcodeInput.val( value );
                    } else {
                        $shortcodeInput.prop( 'checked', 'checked' ).trigger( 'change' );
                    }
                }
            }
            if ( 'thumbnail' === key ) {
                $shortcodeInput.prev().attr( 'src', field[ key ] ).removeClass( 'hidden' );
            }
        });
    };

	// Include all fields in the editor
	(scaffoldData.fields || []).forEach(handleField);
	// Click out any field not existing in the parsed content
    $fieldsContainer.find( 'div:not([data-existing]) i.js-cred-editor-scaffold-item-include-remove' ).trigger( 'click' );

    var $optionsContainer = jQuery( '.js-cred-editor-wrap-scaffold-options' );
    // Fake a change event on options checkboxes to initialize their values
    $optionsContainer.find( 'input:checkbox' ).each( function() {
		jQuery( this ).trigger( 'change' );
    });

    Toolset.hooks.doAction( 'cred-action-after-manual-options-created', $container );
    Toolset.hooks.doAction( 'cred_editor_scaffold_init_sorting' );

    // Wrapping elements
    // Conditionals
    jQuery('[data-' + currentInstance.scaffold_field_id + '="conditionals"]').each(function() {
        var $conditional = jQuery(this);
        var $elements = [$conditional];
        $next = $conditional.next();
        var hasChildren = false;
        while( $next.hasClass( 'cred-scaffold-conditional-child' ) ) {
            hasChildren = true;
            $elements.push( $next );
            $next = $next.next();
        }
        var $wrapper = jQuery( '<div class="cred-editor-scaffold-item-wrapper-conditionals js-cred-editor-scaffold-item-wrapper-conditionals"></div>' ).insertBefore( $conditional );
        $wrapper.append( $elements );
        $conditional.closest( '.cred-editor-scaffold-item-wrapper-conditionals' ).toggleClass( 'cred-editor-scaffold-conditional-has-children', hasChildren );
    } );

    // It must to be instanced because this is object Window
    var scaffold = new Toolset.CRED.ScaffoldEditor();
    scaffold.addFieldItemsWrapperAndRow();

    // HTML content fields must have an ID for make them codemirror instances
    jQuery( '.cred-editor-scaffold-item-list .cred-scaffold-html-content' ).each( function() {
        var id = 'cred_scaffold_' + parseInt( Math.random() * 100000 )
        var $textarea = jQuery( this );
        $textarea.attr( 'id', id );
        if ( ! _.has( WPV_Toolset.CodeMirror_instance, id ) ) {
            WPV_Toolset.CodeMirror_instance[ id ] = icl_editor.codemirror( id, true, { name: 'htmlmixed', autoRefresh: true } );
		}
		WPV_Toolset.CodeMirror_instance[ id ].refresh();
    });
}


/**
 * Gets the conditional data from a GUI as attributes
 *
 * @param {Object} $container jQuery object that contains the shortcode generator GUI
 *
 * @return {array}
 *
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.getConditionalAttributes = function( $container ) {
    var attributes = {};
    attributes.useGui = $container.find( '.js-cred-editor-conditional-groups-general-container' ).data( "useGui" ) !== false;
    if ( ! attributes.useGui ) {
        attributes.custom = $container.find( '[id=cred-editor-conditional-groups-custom-expressions]' ).val();
    } else {
        attributes.conditions = [];
        $container.find( '.cred-editor-conditional-groups-item' ).each( function() {
            var condition = {};
            jQuery( this ).find( 'input, select' ).each( function() {
                var $conditionElement = jQuery( this );
                var conditionPartId = $conditionElement.parent()[0].classList[0].replace(/.*-([^-]+)$/, '$1');
                condition[ conditionPartId ] = $conditionElement.val();
            });
            attributes.conditions.push( condition );
        });
    }
    return attributes;
}


/**
 * Wrap items with a new div for placing the droppable zones, and adds rows
 *
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.addFieldItemsWrapperAndRow = function( $container ) {
    var currentInstance = this;
    if ( Toolset.hooks.applyFilters( 'cred_editor_is_grid_enabled' ) ) {
        var prevColNumber = 0;
        var $prevRow = null;
        var columnCounter = 0;
        ( !! $container? $container : jQuery( '.cred-editor-scaffold-item-list' ) )
            .find( '.js-cred-editor-scaffold-item-wrapper-conditionals, .cred-editor-scaffold-item-container:not(.cred-editor-scaffold-item-deleted):not([data-' + currentInstance.scaffold_field_id + '=conditionals])' )
            .each( function() {
                var $field = jQuery(this);
                if ( ! $container && $field.hasClass( 'cred-scaffold-conditional-child' ) ) {
                    return;
                }
                // Grouping fields in columns
                var sizeColumn = Number( $field.hasClass( 'cred-editor-scaffold-item-wrapper-conditionals' )
                    ? $field.find( '.cred-editor-scaffold-item-container' ).data( 'sizeColumn' )
                    : $field.data('sizeColumn') );
                if ( ! sizeColumn ) {
                    sizeColumn = 1;
                }
                $field.wrap( '<div class="cred-editor-scaffold-item-wrapper-item" data-cred-field-cols="' + ( sizeColumn ) + '"></div>' );
                var $wrapper = $field.parent();
                var numColumns = Number( $field.hasClass( 'js-cred-editor-scaffold-item-wrapper-conditionals' )
                    ? $field.find( '.cred-editor-scaffold-item-container' ).data( 'numColumns' )
                    : $field.data('numColumns') );
                if ( ! numColumns ) {
                    numColumns = 1;
                }
                if ( numColumns === columnCounter || numColumns !== prevColNumber ) {
                    columnCounter = 0;
                    $prevRow = jQuery( '<div class="cred-editor-scaffold-item-wrapper-row" data-cred-cols="' + numColumns + '"></div>' );
                    $wrapper.before( $prevRow );
                }
                $wrapper.append( '<div class="cred-editor-scaffold-item-wrapper-resizer fas fa-arrows-alt-h"></div>' );
                $prevRow.append( $wrapper );
                prevColNumber = numColumns;
                columnCounter += sizeColumn;
                if ( $field.hasClass( 'js-cred-editor-scaffold-item-wrapper-conditionals' ) ) {
                    currentInstance.addFieldItemsWrapperAndRow( $field );
                }
            } );
    } else {
        jQuery( '.cred-editor-scaffold-item-list' )
            .find( '.js-cred-editor-scaffold-item-wrapper-conditionals, .js-cred-editor-scaffold-item-container:not([data-' + currentInstance.scaffold_field_id + '=conditionals])' )
            .wrap( '<div class="cred-editor-scaffold-item-wrapper-row" data-cred-cols="1"><div class="cred-editor-scaffold-item-wrapper-item"></div></div>' );
    }
}

/**
 * Store the checked radios of each dragging item, to restore them once dragging ends.
 *
 * Caused by a weird unexpected behavior of the draggable library, when cloning items:
 * the number of radios contained in each item doubles, and the original ones win the checked status,
 * hence cloned (so dragged) items are added without checked radios.
 *
 * @param object event
 * @param object ui
 * @since 2.3.2
 */
Toolset.CRED.ScaffoldEditor.prototype.storeCheckedRadios = function( event, ui ) {
	var currentInstance = this;
	ui.helper.find( 'input:radio:checked' ).each( function () {
		var name = jQuery( this ).attr( 'name' );
		currentInstance.checkedRadioAttributeInputsTrack[ name ] = jQuery( this ).val();
    });
}

/**
 * Restore the checked radios of each dragging item, once dragging ends.
 *
 * Caused by a weird unexpected behavior of the draggable library, when cloning items:
 * the number of radios contained in each item doubles, and the original ones win the checked status,
 * hence cloned (so dragged) items are added without checked radios.
 *
 * @param object event
 * @param object ui
 * @since 2.3.2
 */
Toolset.CRED.ScaffoldEditor.prototype.restoreCheckedRadios = function( event, ui ) {
	var currentInstance = this;
	_.each( currentInstance.checkedRadioAttributeInputsTrack, function( value, name, list ) {
		// jQuery 3 compatibility change:
		// jQuery( ui.helper.context ).find( 'input:radio[name="' + name + '"][value="' + value + '"]' ).prop( 'checked', true );
		ui.helper.find( 'input:radio[name="' + name + '"][value="' + value + '"]' ).prop( 'checked', true );
	});

	currentInstance.checkedRadioAttributeInputsTrack = {};
}

/**
 * Displays dropping zones in the Drag&Drop editor
 *
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.addDraggableItems = function() {
    var self = this;
    // I don't know why 'mouseup' event is triggered twice so I need to check event position
    var initialMousePosition = '';
    var draggingOptions = {
        start: function( event, ui ) {
			jQuery.ui.credStopsDragging = false;
			self.storeCheckedRadios( event, ui );
            self.addDroppingZones();
        },
        stop: function( event, ui ) {
			self.removeDroppingZones();
			self.restoreCheckedRadios( event, ui );
        },
        helper: 'clone',
        refreshPositions: true
    };
    self.draggingOptions = draggingOptions;
    jQuery( '.cred-editor-scaffold-item-list-container .js-cred-editor-scaffold-item-container' )
        .draggable( Object.assign( {}, draggingOptions ));
    // When mouseup in the container, remove the dropping zones. They can be displayed when clicking, but it there are not dragging, they are not removed.
    jQuery( '.cred-editor-scaffold-item-list-container .js-cred-editor-scaffold-item-container .js-cred-editor-scaffold-item-move' )
        // Showing dropping zones is handled when clicking and not when dragging.
        .on( 'mousedown', function( event ) {
            initialMousePosition = event.pageX + '-' + event.pageY;
            self.addDroppingZones();
        } )
    jQuery( '.cred-editor-scaffold-item-list-container' )
        .on( 'mouseup', function( event ) {
            if ( initialMousePosition === event.pageX + '-' + event.pageY ) {
                self.removeDroppingZones();
            }
        } );
    jQuery( '.cred-editor-scaffold-sidebar .js-cred-editor-scaffold-item-container:not([data-blocked="true"])' )
        .draggable( draggingOptions );
}


/**
 * Displays dropping zones in the Drag&Drop editor
 *
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.addDroppingZones = function() {
    if ( jQuery( '.js-cred-editor-scaffold-dropping-zones:visible' ).length ) {
        return;
    }
    var currentInstance = this;
    jQuery( '.cred-editor-scaffold-item-list-container .cred-editor-scaffold-item-list' ).addClass( 'cred-editor-scaffold-is-dragging' );
    jQuery( '.cred-editor-scaffold-item-wrapper-resizer' ).addClass( 'hidden' );
    // Removes a class using a regex
    var removeClassRegEx = function( droppableItem, regex ) {
        if ( !!droppableItem ) {
            Array.apply( [], droppableItem.classList ).forEach( function( className ) {
                if ( className.match( regex ) ) {
                    droppableItem.classList.remove( className );
                }
            } );
        }
    };
    // Horizontal zones: under each item
    var droppingZonePattern = '<div class="cred-editor-scaffold-dropping-zones js-cred-editor-scaffold-dropping-zones cred-editor-scaffold-dropping-zones-%TYPE% dropzonein"></div>',
        horizontalDroppingZone = droppingZonePattern.replace( '%TYPE%', 'horizontal' ),
        verticalDroppingZone = droppingZonePattern.replace( '%TYPE%', 'vertical' ),
        conditionalDroppingZoneClassName = 'cred-editor-scaffold-dropping-zones-conditional',
        horizontalConditionalDroppingZone = droppingZonePattern.replace( '%TYPE%', 'horizontal ' + conditionalDroppingZoneClassName );
    jQuery( '.cred-editor-scaffold-item-list > .cred-editor-scaffold-item-wrapper-row' ).before( horizontalDroppingZone );
    jQuery( '.cred-editor-scaffold-item-list > .cred-editor-scaffold-item-wrapper-row:last-child' ).after( horizontalDroppingZone );
    jQuery( '.cred-editor-scaffold-item-list-container .js-cred-editor-scaffold-item-container[data-' + currentInstance.scaffold_field_id + '=conditionals], .cred-editor-scaffold-item-wrapper-item .cred-editor-scaffold-item-wrapper-row' )
        .after( horizontalConditionalDroppingZone );

    // Vertical zones
    if ( Toolset.hooks.applyFilters( 'cred_editor_is_grid_enabled' ) ) {
        jQuery( '.cred-editor-scaffold-item-wrapper-item' )
            .before( verticalDroppingZone );
        jQuery( '.cred-editor-scaffold-item-wrapper-item:last-child' )
            .after( verticalDroppingZone );
        // Limit number of columns is 12. It is easier to remove them that to avoid when adding.
        jQuery( '.cred-editor-scaffold-item-wrapper-row[data-cred-cols=12] ' ).each( function() {
            var $row = jQuery( this );
            if ( $row.find( '> .cred-editor-scaffold-item-wrapper-item' ).length === 12 ) {
                $row.find( '> .cred-editor-scaffold-dropping-zones-vertical' ).remove();
            }
        })
        jQuery( '.cred-editor-scaffold-item-wrapper-item .cred-editor-scaffold-item-wrapper-row .cred-editor-scaffold-dropping-zones-vertical' ).addClass( conditionalDroppingZoneClassName );
    }

    jQuery( '.js-cred-editor-scaffold-dropping-zones' ).droppable({
        hoverClass: 'cred-editor-scaffold-drop-hover',
        drop: function( event, ui ) {
            jQuery.ui.credStopsDragging = true;
            var $droppable = jQuery( this ),
                $draggable = ui.draggable,
                $row = $draggable.closest( '.cred-editor-scaffold-item-wrapper-row' ),
                $droppableRow = $droppable.closest( '.cred-editor-scaffold-item-wrapper-row' );
            // Because of some dropping zones have an extended zone, jQuery droppable library trigger drop event twice and it causes problems with it
            $droppable.removeClass( 'cred-editor-scaffold-drop-hover' );
            $draggable.closest( '.cred-editor-scaffold-item-wrapper-item' ).find( '.cred-editor-scaffold-item-wrapper-resizer' ).remove();
            if ( $draggable.data( currentInstance.scaffold_field_id ) === 'conditionals' && $row.length ) {
                // Dragging an eisting conditional field => get the wrapper as draggable
                $draggable = $draggable.closest( '.cred-editor-scaffold-item-wrapper-conditionals' );
            } else if ( $draggable.data( 'permanent' ) && $row.length == 0 ) {
                // Dragging a new permanent field => clone it instead
                $draggable = $draggable.clone();
				/**
				 * Initialize tippy.js and make sure the tooltip really should be there (depends on context)
				 */
                $draggable.find( '.js-cred-editor-tippy' ).each( function() {
					OTGSUI.otgsPopoverTooltip.initSingle( this );
					this._tippy.set( {
						onShow: function( tip ) {
							Toolset.CRED.ScaffoldEditor.maybeShowTooltip( tip );
						}
					} );
                } );
            }

            var $blockContainer = jQuery( '.cred-editor-scaffold-' + currentInstance.getFieldTypeForSidebarBlocks( $draggable.data( 'fieldtype' ) ) + '-list' );
            $droppable.before( $draggable );

            if ( $blockContainer.length && ! $blockContainer.children( ':not(.ui-draggable-dragging)' ).length ) {
                $blockContainer.parent().parent().addClass( 'hidden' );
            }

            $draggable.removeClass( 'cred-scaffold-conditional-child' );
            $droppable.closest( '.js-cred-editor-scaffold-item-wrapper-conditionals' ).removeClass( 'cred-editor-scaffold-item-wrapper-conditionals-hover' );
            if ( $droppable.hasClass( 'cred-editor-scaffold-dropping-zones-conditional' ) ) {
                $draggable.addClass( 'cred-scaffold-conditional-child' );
                var $conditionalContainer = $droppable.closest( '.js-cred-editor-scaffold-item-wrapper-conditionals' );
                $conditionalContainer.addClass( 'cred-editor-scaffold-conditional-has-children' );
            }
            if ( $droppable.hasClass( 'cred-editor-scaffold-dropping-zones-horizontal' ) ) {
                $draggable.wrap( '<div class="cred-editor-scaffold-item-wrapper-row" data-cred-cols="1"><div class="cred-editor-scaffold-item-wrapper-item"></div></div>' );
            } else if ( $droppable.hasClass( 'cred-editor-scaffold-dropping-zones-vertical' ) ) {
                $draggable.wrap( '<div class="cred-editor-scaffold-item-wrapper-item"></div>' );
            }
            if ( $draggable.data( currentInstance.scaffold_field_id ) === 'conditionals' ) {
                $draggable.wrap( '<div class="cred-editor-scaffold-item-wrapper-conditionals js-cred-editor-scaffold-item-wrapper-conditionals"></div>' );
            }
            $draggable.parent().append( '<div class="cred-editor-scaffold-item-wrapper-resizer fas fa-arrows-alt-h"></div>' );

            if ( ! $draggable.draggable( 'instance' ) ) {
                $draggable.draggable( Object.assign( {}, currentInstance.draggingOptions ));
            }
            $droppableRow.attr( 'data-cred-cols-preview', '1' );
            $droppable.remove();

            // Needed because there is a item duplicated with class name ui-draggable-dragging
            if ( $row[0] !== $droppableRow[0] ) {
                currentInstance.rearrangeColumns( $droppableRow );
            }
            if ( $row.length ) {
                currentInstance.rearrangeColumns( $row );
            }

            // Generate unique IDs
            jQuery( '#js-cred-editor-scaffold-item-list-container .cred-scaffold-html-content:not([id])' ).each( function() {
                jQuery( this ).attr( 'id', 'cred_scaffold_' + parseInt( Math.random() * 100000 ) );
            });
            $draggable.find( '.cred-scaffold-html-content' ).each( function() {
				var $textarea = jQuery( this ),
					id = $textarea.attr( 'id' );
                if ( ! _.has( WPV_Toolset.CodeMirror_instance, id ) ) {
                    WPV_Toolset.CodeMirror_instance[ id ] = icl_editor.codemirror( id, true, { name: 'htmlmixed', autoRefresh: true } );
                }
            });

        },
        over: function ( event, ui ) {
            var $droppable = jQuery( this );
            if ( $droppable.hasClass( 'cred-editor-scaffold-dropping-zones-vertical' ) ) {
                var $row = $droppable.closest( '.cred-editor-scaffold-item-wrapper-row' ),
                    numCols = Number( $row.attr( 'data-cred-cols' ) );
                // preview css rule with higher value will override cred-cols value
                $row.attr( 'data-cred-cols-preview', numCols + 1 ); // prop is used for CSS rules
            }
            $droppable.closest( '.js-cred-editor-scaffold-item-wrapper-conditionals' ).addClass( 'cred-editor-scaffold-item-wrapper-conditionals-hover' );
        },
        out: function( event, ui ) {
            var $droppable = jQuery( this ),
                $row = $droppable.closest( '.cred-editor-scaffold-item-wrapper-row' );
            $row.attr( 'data-cred-cols-preview', '1' ); // prop is used for CSS rules
            $droppable.closest( '.js-cred-editor-scaffold-item-wrapper-conditionals' ).removeClass( 'cred-editor-scaffold-item-wrapper-conditionals-hover' );
        },
        tolerance: 'pointer'
    });

    jQuery( '.js-cred-editor-scaffold-options-close:visible' ).trigger( 'click' );
}


/**
 * Removes dropping zones
 *
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.removeDroppingZones = function() {
    var currentInstance = this;
    jQuery( '.cred-editor-scaffold-item-wrapper-resizer' ).removeClass( 'hidden' );
    jQuery( '.cred-editor-scaffold-item-list-container .cred-editor-scaffold-item-list' ).removeClass( 'cred-editor-scaffold-is-dragging' );
    jQuery( '.js-cred-editor-scaffold-dropping-zones' ).removeClass( 'dropzonein' ).addClass( 'dropzoneout' );
    jQuery( '.cred-editor-scaffold-dropping' ).removeClass( 'cred-editor-scaffold-dropping' );
    setTimeout( function() {
        jQuery( '.js-cred-editor-scaffold-dropping-zones' ).remove();
        currentInstance.removeEmptyWrappers();
    }, 550 );

}

/**
 * Remove empty item wrappers
 */
Toolset.CRED.ScaffoldEditor.prototype.removeEmptyWrappers = function() {
    jQuery('.cred-editor-scaffold-item-wrapper-item:empty').each( function() {
        var $this = jQuery(this);
        var $parent = $this.parent();
        if ( $parent.children().length === 1 ) {
            $parent.remove();
        } else {
            $this.remove();
        }
    } );
}

/**
 * Returns the proper fieldtype for sidebar columns
 *
 * @param {String} fieldType
 * @return {String}
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.getFieldTypeForSidebarBlocks = function( fieldType ) {
    if ( [ 'basic', 'legacyParent', 'hierarchicalParent' ].includes( fieldType ) ) {
        fieldType = 'post-elements';
    }
    if ( [ 'formElement', 'form-elements' ].includes( fieldType ) ) {
        fieldType = 'extra';
    }
    return fieldType;
};


/**
 * Init javascript events
 *
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.initEvents = function() {
    var currentInstance = this;

    // Greatest Common Divisor. It is used to reduce the columns sizes to a simplest ones
    // For example 3x6x3 is reduced to 1x2x1
    // @link https://www.w3resource.com/javascript-exercises/javascript-math-exercise-9.php
    var gcdMoreThanTwoNumbers = function( input ) {
        if ( toString.call( input ) !== "[object Array]" ) {
            return  false;
        }
        var len, a, b;
        len = input.length;
        if ( !len ) {
            return null;
        }
        a = input[ 0 ];
        for ( var i = 1; i < len; i++ ) {
            b = input[ i ];
            a = gcdTwoNumbers( a, b );
        }
        return a;
    }

    var gcdTwoNumbers = function( x, y ) {
        if ( ( typeof x !== 'number' ) || ( typeof y !== 'number' ) ) {
            return false;
        }
        x = Math.abs(x);
        y = Math.abs(y);
        while( y ) {
            var t = y;
            y = x % y;
            x = t;
        }
        return x;
    }
    // Handles fields columns width
    // Gets the previous value, so it can be set if new value is not allowed
    // 'mousedown' must be used because number inputs have buttons for increasing/decresing the value, and clicking them triggers 'change' event but not 'focus'

    // This should not be needed anymore.
    if ( window.cred_scaffold_events_are_init ) {
        return;
    }

    var resizingHelper = null;
    var resizingInitialItemPosX = 0;
    var resizingGap = 50;

    jQuery( document ).on( 'mousedown', '.cred-editor-scaffold-item-wrapper-resizer', function( event ) {
        resizingHelper = this;
        var $resizedItem = jQuery( resizingHelper ).closest( '.cred-editor-scaffold-item-wrapper-item' );
        // 'dataset' is used because 'jQuery.data' use internal vars, not HTML attributes, needed for CSS rules.
        resizingHelper.dataset.previousNumberColumns = $resizedItem[0].dataset.credFieldCols;
        resizingInitialItemPosX = event.clientX;
        jQuery( '.cred-editor-scaffold-item-list' ).append( '<div class="cred-editor-scaffold-item-list-overlay"></div>' );
        resizingGap = $resizedItem.closest( '.cred-editor-scaffold-item-wrapper-row' ).width() / 12;
    } );

    jQuery( document ).on( 'mouseup', function( event ) {
        if ( resizingHelper ) {
            resizingHelper = null;
            jQuery( '.cred-editor-scaffold-item-list-overlay' ).remove();
        }
    });

    /**
     * When resizing the columns, it will take 1/12 part of the next column. So first, change the row to 12-columns is mandatory
     * Also, using Greatest Common Divisor helps to reduce the number of columns in the row: 6x6 is transformed in 1x1.
     * And it will be easier to handled when adding new items.
     */
    jQuery( document ).on( 'mousemove', _.throttle( function( event ) {
        if ( ! resizingHelper ) {
            return;
        }
        var diffX = event.clientX - resizingInitialItemPosX;
        if ( Math.abs( diffX ) < resizingGap ) {
            return;
        }
        var $resizingHelper = jQuery( resizingHelper );
        var previousNumberColumns = Number( resizingHelper.dataset.previousNumberColumns ) || 1;
        var $item = $resizingHelper.closest( '.cred-editor-scaffold-item-wrapper-item' );
        var $row = $resizingHelper.closest( '.cred-editor-scaffold-item-wrapper-row' );
        // Counting number of columns
        var numberColumns = [];
        var $rowItems = $row.find( '> .cred-editor-scaffold-item-wrapper-item' );
        var itemPosition = $rowItems.index( $item );
        $rowItems.each( function() {
            numberColumns.push( Number( this.dataset.credFieldCols ) || 1 );
        } );
        var totalNumberColumns = numberColumns.reduce( function( a, b ) {
            return a + b;
        } );
        var credCols = $row[0].dataset.credCols;
        // Convert it to a 12 columns model
        if ( credCols !== 12 ) {
            var multiplier = 12 / credCols;
            numberColumns = numberColumns.map( function( n ) {
                return n * multiplier;
            } );
        }
        if ( diffX > 0 && numberColumns[ itemPosition + 1 ] > 1 ) {
            numberColumns[ itemPosition ]++;
            numberColumns[ itemPosition + 1 ]--;
        } else if ( diffX < 0 && numberColumns[ itemPosition ] > 1 ) {
            numberColumns[ itemPosition ]--;
            numberColumns[ itemPosition + 1 ]++;
        } else {
            return;
        }
        // Reduce the columns to a simplest way
        var gcd = gcdMoreThanTwoNumbers( numberColumns );
        numberColumns = numberColumns.map( function( n ) {
            return n / gcd;
        } );
        totalNumberColumns = numberColumns.reduce( function( a, b ) {
            return a + b;
        } );

        if ( totalNumberColumns > 12  ) {
            return;
        }
        var i = 0;
        $rowItems.each( function() {
            this.dataset.credFieldCols = numberColumns[ i ];
            i++;
        } );
        $row[0].dataset.credCols = totalNumberColumns;
        resizingInitialItemPosX = event.clientX;
    }, 50, true ) );

    jQuery( document ).on( 'click', '.js-cred-editor-scaffold-sidebar-collapse', function( e ) {
        e.preventDefault();
        var $editorSidebar = jQuery( '#js-cred-editor-scaffold-sidebar' );

        $editorSidebar
            .find( '.fa-angle-up' )
                .trigger( 'click' );
        jQuery( '.js-cred-editor-scaffold-sidebar-expand, .js-cred-editor-scaffold-sidebar-collapse' ).toggleClass( 'hidden' );
    });

    jQuery( document ).on( 'click', '.js-cred-editor-scaffold-sidebar-expand', function( e ) {
        e.preventDefault();
        var $editorSidebar = jQuery( '#js-cred-editor-scaffold-sidebar' );

        $editorSidebar
            .find( '.fa-angle-down' )
                .trigger( 'click' );
        jQuery( '.js-cred-editor-scaffold-sidebar-expand, .js-cred-editor-scaffold-sidebar-collapse' ).toggleClass( 'hidden' );
    });

    jQuery( document ).on( 'click', '.js-cred-editor-scaffold-sidebar-toggle', function( e ) {
        e.preventDefault();
        var $toggler = jQuery( this ),
            $editorSidebar = jQuery( '#js-cred-editor-scaffold-sidebar' ),
            $editorListContainer = jQuery( '#js-cred-editor-scaffold-item-list-container' );

        $toggler.find( 'i' ).toggleClass( 'fa-angle-right fa-angle-left' );
        $editorSidebar.find( '.js-cred-editor-scaffold-sidebar-collapse' ).toggle();
        $editorSidebar.find( '.js-cred-editor-scaffold-sidebar-group' ).toggle();
        $editorSidebar.toggleClass( 'cred-editor-scaffold-sidebar-hidden' );

        $editorListContainer.toggleClass( 'cred-editor-scaffold-item-list-container-full-width', $editorSidebar.hasClass( 'cred-editor-scaffold-sidebar-hidden' ) );
    });


    /**
     * Removing elements from editor
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-editor-scaffold-item-include-remove', function( e ) {
        e.preventDefault();
        var $control = jQuery( this ),
            $container = $control.closest( '.js-cred-editor-scaffold-item-container' ),
            fieldId = $container.data( currentInstance.scaffold_field_id ),
            $maybeContainerWrapper = $container.closest( '.cred-editor-scaffold-item-wrapper-conditionals' ),
            $droppableRow = $container.closest( '.cred-editor-scaffold-item-wrapper-row' ),
            fieldType = currentInstance.getFieldTypeForSidebarBlocks( $container.data('fieldtype') ),
            $target = jQuery( '.cred-editor-scaffold-' + fieldType + '-list' );

        // Remove children
        $container.parent('.js-cred-editor-scaffold-item-wrapper-conditionals').find( '.cred-editor-scaffold-item-wrapper-row .js-cred-editor-scaffold-item-include-remove').trigger( 'click' );

        if ( $container.hasClass( 'js-cred-editor-scaffold-item-container-options-opened' ) ) {
            jQuery('.js-cred-editor-scaffold-options-close').trigger( 'click' );
        }

        if ( 'conditionals' != fieldId ) {
            $maybeContainerWrapper = $container;
        }

        $maybeContainerWrapper.addClass( 'cred-editor-scaffold-item-deleted' );
        $maybeContainerWrapper
            .find( '.js-cred-editor-scaffold-item-container' )
                .addClass( 'cred-editor-scaffold-item-deleted' );

        if ( $container.hasClass( 'js-cred-editor-scaffold-item-container-can-toggle' ) ) {
            $container.find( '.js-cred-editor-scaffold-item-options-toggle.fa-angle-up' ).trigger( 'click' );
        }

        $maybeContainerWrapper.fadeOut( 100, function() {
            $container.closest( '.cred-editor-scaffold-item-wrapper-item' ).find( '.cred-editor-scaffold-item-wrapper-resizer' ).remove();
            $container.removeClass( 'cred-scaffold-conditional-child' );
            var $conditionalContainer = $container.closest( '.js-cred-editor-scaffold-item-wrapper-conditionals' );
            if ( $conditionalContainer.find( '.cred-scaffold-conditional-child' ).length ) {
                $conditionalContainer.addClass( 'cred-editor-scaffold-conditional-has-children' );
            } else {
                $conditionalContainer.removeClass( 'cred-editor-scaffold-conditional-has-children' );
            }

            if ( ! $container.data('permanent') ) {
                $container
                    .addClass( 'cred-editor-scaffold-item-restored' )
                    .removeClass( 'cred-editor-scaffold-item-deleted' )
                // Cancel link buttons has a special behavior
                if ( 'cancel' === fieldId ) {
                    if ( ! $target.find( '[data-' + currentInstance.scaffold_field_id + '=cancel]' ).length ) {
                        $target.prepend( $container );
                    }
                } else {
                    $target.prepend( $container );
                }
                $container.fadeIn( 100, function() {
                    $container.removeClass( 'cred-editor-scaffold-item-restored' );
                });
                if ( $target.children().length ) {
                    $target.closest( '.cred-editor-scaffold-' + fieldType + '-container' ).removeClass( 'hidden' );
                }

				/**
				 * Initialize tippy.js and make sure the tooltip really should be there (depends on context)
				 */
				OTGSUI.otgsPopoverTooltip.initSingle( $container[0] );
				$container[0]._tippy.set( {
					onShow: function( tip ) {
						Toolset.CRED.ScaffoldEditor.maybeShowTooltip( tip );
					}
				} );
            } else {
                $maybeContainerWrapper.remove();
            }

            // Refresh draggable instance
            if ( $container.draggable( 'instance' ) ) {
                $container.draggable( 'destroy' );
            }
            $container.draggable( currentInstance.draggingOptions );

            Toolset.hooks.doAction( 'cred_editor_insert_scaffold' );
            currentInstance.removeEmptyWrappers();
            currentInstance.rearrangeColumns( $droppableRow );
            var numCols = 0;
            var $dropppableRowChildren = $droppableRow.children( '.cred-editor-scaffold-item-wrapper-item' );
            $dropppableRowChildren.each( function() {
                numCols += Number( this.dataset.credFieldCols ) || 1;
            } );
            if ( $dropppableRowChildren.length === 1 ) {
                numCols = 1;
            }
            $droppableRow.attr( 'data-cred-cols', numCols );

        });
    });


    /**
     * Adding elements to editor
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-editor-scaffold-item-push', function( e ) {
        e.preventDefault();
        var $container = jQuery( this ).closest( '.js-cred-editor-scaffold-item-container' ),
			$editorList = jQuery( '.js-cred-editor-scaffold-item-list' ),
			$blockContainer = jQuery( '.cred-editor-scaffold-' + currentInstance.getFieldTypeForSidebarBlocks( $container.data( 'fieldtype' ) ) + '-list' );

        if ( $container.data( 'permanent' ) ) {
            $container = $container.clone();

			/**
			 * Initialize tippy.js and make sure the tooltip really should be there (depends on context)
			 */
            $container.find( '.js-cred-editor-tippy' ).each( function() {
				OTGSUI.otgsPopoverTooltip.initSingle( this );
				this._tippy.set( {
					onShow: function( tip ) {
						Toolset.CRED.ScaffoldEditor.maybeShowTooltip( tip );
					}
				} );
            } );
        }

        $container.fadeOut( 750, function() {
			$container.addClass( 'cred-editor-scaffold-item-added' )
			$editorList.append( $container );

            if ( 0 == $blockContainer.children().length ) {
                $blockContainer.parent().parent().addClass( 'hidden' );
            }

            $container.wrap( '<div class="cred-editor-scaffold-item-wrapper-row" data-cred-cols="1"><div class="cred-editor-scaffold-item-wrapper-item"></div></div>' );
            if ( $container.data( currentInstance.scaffold_field_id ) === 'conditionals' ) {
                $container.wrap( '<div class="cred-editor-scaffold-item-wrapper-conditionals js-cred-editor-scaffold-item-wrapper-conditionals"></div>' );
            }
            $container.fadeIn( 750, function() {
                $container
                    .removeClass( 'cred-editor-scaffold-item-added' )
					.draggable( Object.assign( {}, currentInstance.draggingOptions ));

				// Relationship forms: apply knockout bindings
				if ( $container.data( 'permanent' ) ) {
					Toolset.hooks.doAction( 'cred_editor_scaffold_do_knockout_binding', $container[0] );
				}

				// Initialize Codemirror instance if adding an HTML block
				if ( 'html' === $container.data( currentInstance.scaffold_field_id ) ) {
					var $htmlTextarea = $container.find( 'textarea.cred-scaffold-html-content' ),
						htmlTextareaId = 'cred_scaffold_' + parseInt( Math.random() * 100000 );

					$htmlTextarea.attr( 'id', htmlTextareaId );
					if ( ! _.has( WPV_Toolset.CodeMirror_instance, htmlTextareaId ) ) {

						WPV_Toolset.CodeMirror_instance[ htmlTextareaId ] = icl_editor.codemirror( htmlTextareaId, true, { name: 'htmlmixed', autoRefresh: true } );
					}
					WPV_Toolset.CodeMirror_instance[ htmlTextareaId ].refresh();
				}
            });
        });
    });

    // Collapse Generic fields
    Toolset.hooks.addAction( 'cred-action-toolbar-scaffold-content-loaded', function() {
        jQuery( '.js-cred-editor-toggler[data-target="scaffold-generic"] .fa-angle-up' ).trigger( 'click' );
    } );

    // Avoids second calls
    window.cred_scaffold_events_are_init = true;
};


/**
 * Columns must fit 12 columns Bootstrap design
 *
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.rearrangeColumns = function( $row ) {
    var divisors = [1, 2, 3, 4, 6, 12];
    var $rowItems = $row.find( '> .cred-editor-scaffold-item-wrapper-item > .js-cred-editor-scaffold-item-container:not(.ui-draggable-dragging), > .cred-editor-scaffold-item-wrapper-item > .js-cred-editor-scaffold-item-wrapper-conditionals' );
    var numberOfColumns = $rowItems.length;
    var credCols = divisors.reduce( function( a, b ) {
        return a >= numberOfColumns ? a : b;
    } );
    var sizeColumn = Math.floor( credCols / numberOfColumns );
    var remainder = credCols % numberOfColumns;
    $row.attr( 'data-cred-cols', credCols );
    $rowItems.slice( 0, ( numberOfColumns - remainder ) ).each( function() {
        this.parentNode.dataset.credFieldCols = sizeColumn;
    } );
    $rowItems.slice( numberOfColumns - remainder  ).each( function() {
        this.parentNode.dataset.credFieldCols = sizeColumn + 1;
    } );
}


/**
 * Gets scaffold data equivalent from HTML content
 * It is not possible to parse an exact copy from HTML to
 *
 * @param {string} content
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.parseHTMLtoScaffold = function( content ) {
    var self = this;
    /**
     * Converts text into a HTMLNode
     *
     * @param {string} content
     */
    var convertToDom = function( content ) {
        var parserElement = document.createElement( 'parser' );
        parserElement.innerHTML = content;
        return parserElement;
    };

    /**
     * Parsers a string to gets the attributes of a shortcode
     *
     * @param {string} shortcodeContent
     */
    var getAttributesFromShortcode = function( shortcodeContent ) {
        var item = {};
		var match = shortcodeContent.match( /\[[^\[]*\]/g );
        if ( ! match ) {
            var field = {};
            field[ self.scaffold_field_id ] = 'html';
            field.value = shortcodeContent.trim();
            return field;
        }
        match.forEach( function( shortcode ) {
            var attributes = shortcode
                // Captures first quote (single or double) and continues until next instance of the quote captured
                .match(  /[\w_-]+=(['"])((?!\1).)+\1/gu );
            if ( attributes ) {
                attributes.forEach( function( attr ) {
                    attr = attr.split('=');
                    item[attr[0]] = attr[1].slice(1, -1);
                } );
            }
		} );
        return item;
    }


    /**
     * Get conditionals from string
     *
     * @param {string} conditionalText
     */
    var getConditionals = function( conditionalText ) {
        var conditionals = [];
        conditionalText.match( /([AND|OR]?)[^\(]*\([^\)]*\)[^\)]+\)/g )
            .forEach( function( matchedCondition ) {
                var conditionElements = matchedCondition.match( /(\w+)?\s*\(\s*\$\(([^\)]*)\)\s+(\w+)\s+'([^']+)'\s+\)/ );
                if ( conditionElements ) {
                    var condition = {};
                    if ( conditionElements[1] ) {
                        condition.connect = conditionElements[1];
                    }
                    condition.origin = conditionElements[2];
                    condition.operator = conditionElements[3];
                    condition.value = conditionElements[4];
                    conditionals.push( condition );
                }
            } );
        return conditionals;
    }

    /**
     * Gets the label text
     *
     * @param {HTMLNode} node
     * @return {string\null}
     */
    var getLabel = function ( node ) {
        if ( node.nodeType !== Node.ELEMENT_NODE ) {
            return null;
        }
        var label = node.querySelector( 'label' );
        if ( ! label ) {
            return null;
        }
        var i18nRegEx = /\[cred_i18n[^\[]*\]([^]*)\[\/cred_i18n\]/g;
        if ( match = i18nRegEx.exec( label.innerHTML ) ) {
            return match[1];
        }
        return label.innerHTML;
    }

    /**
     * Having a text, it finds shortcodes and parses them
     *
     * @param {string} shorcodeHTML
     */
    var parseShortcode = function( shortcodeHTML ) {
        if ( shortcodeHTML.match( /field="(category_add_new|post_tag_popular)"/ ) ) {
            return null;
        }
        var conditionalRegEx = /\[cred_show_group[^\[]*\]([^]*)\[\/cred_show_group\]/g;
        var genericFieldRegEx = /\[cred_generic_field[^\[]*\]([^]*)\[\/cred_generic_field\]/g;
        var i18nRegEx = /\[cred_i18n[^\[]*\]([^]*)\[\/cred_i18n\]/g;
        var match = conditionalRegEx.exec( shortcodeHTML );
        if ( match ) {
            do {
                var conditional = parseShortcode( match[0].match( /(\[cred_show_group[^\[]*\]).*/, '$1' )[0] );
                conditional.children = self.parseHTMLtoScaffold( '<div class="container-fluid">' + match[1] + '</div>' );
                conditional.children.forEach( function(child) {
                    child.isNested = true;
                } );
                conditional.conditionals = { conditions: [], useGui: true };
                conditional.conditionals.conditions = getConditionals( conditional.if );
                delete conditional.if;
                conditional[ self.scaffold_field_id ] = 'conditionals';
                conditional.fieldtype = 'extra';
                return conditional;
            } while ( match = conditionalRegEx.exec( shortcodeHTML ) );
        } else {
            var item = {};
            if ( ( match = genericFieldRegEx.exec( shortcodeHTML ) ) ) {
                item = getAttributesFromShortcode( match[0].match( /(\[cred_generic_field[^\[]*\]).*/, '$1' )[0] );
                try {
                    Object.assign( item, JSON.parse( match[1].trim() ) );
                } catch( e ) {
                    return null;
                }
                var htmlNode = convertToDom( shortcodeHTML );
                var label = htmlNode.querySelector( 'label' );
                if ( label ) {
                    item.label = label.innerHTML;
                }
                item.fieldtype = 'generic';
            } else if ( match = i18nRegEx.exec( shortcodeHTML ) ) {
				item = getAttributesFromShortcode( match[0].match( /(\[cred_i18n[^\[]*\]).*/, '$1' )[0] );
				item.fieldType = item.fieldtype = 'extra';
				item.value = match[1];
            } else {
                item = getAttributesFromShortcode( shortcodeHTML );
			}
            var htmlNode = convertToDom( shortcodeHTML );
            var label = getLabel( htmlNode );
            if ( label ) {
                item.label = label;
            }

            if ( !item[ self.scaffold_field_id ] ) {
                if ( !! item.role ) {
                    item[ self.scaffold_field_id ] = item.role;
                } else if ( !! item.type ) {
                    item[ self.scaffold_field_id ] = item.type;
                } else if ( !! item.name ) {
                    item[ self.scaffold_field_id ] = item.name;
                } else if ( !! item.field ) {
                    item[ self.scaffold_field_id ] = item.field;
                } else {
                    // Checks if there is a different form shortcode
                    var formShortcode = shortcodeHTML.match( /\[cred-form-(\w+)/ );
                    if ( formShortcode ) {
                        if ( [ 'feedback', 'submit', 'cancel' ].includes( formShortcode[1] ) ) {
                            item[ self.scaffold_field_id ] = formShortcode[1];
                        }
                    }
                }
            }
            if ( ! Object.keys(item).length ) {
                return null;
            }
            if ( ! item.fieldtype ) {
                item.fieldtype = self.getFieldTypeFromField( item );
            }
            item.isNested = false;
            return item;
        }
    };

    var items = [];
    // Checks if it uses Bootstrap
    var element = convertToDom( content );
    var rows = element.querySelectorAll( '.container-fluid > .row' );
    if ( rows.length ) {
        rows.forEach( function(row) {
            var numColumns = 0;
            Array.apply([], row.childNodes).filter( function(element) {
                return Array.apply([], element.classList).filter( function(className) {
                    return className.match(/col-/);
                }).length;
            }).forEach( function(item) {
                var label = getLabel( item );
                var sizeColumn = item.getAttribute('class').match( /col-\w{2}-(\d+)/ );
                // An item might have several shortcodes
                var shortcodes = item.innerHTML.match(wp.shortcode.regexp('[\\w-]+'));
                if ( shortcodes ) {
                    shortcodes.forEach( function( shortcodeHTML ) {
                        var itemParsed = parseShortcode( shortcodeHTML );
                        if ( itemParsed ) {
                            itemParsed.sizeColumn = sizeColumn.length? Number( sizeColumn[1] ) : 1;
                            numColumns += itemParsed.sizeColumn;
                            itemParsed.numColumns = -1; // initial value that will be changed
                            if ( label ) {
                                itemParsed.label = label;
                            }
                            if ( itemParsed ) {
                                items.push( itemParsed );
                            }
                        }
                    } );
                } else {
                    // HTML field
                    var htmlItem = {}
                    htmlItem[ self.scaffold_field_id ] = 'html';
                    htmlItem.fieldType = htmlItem.fieldtype = 'extra';
                    htmlItem.sizeColumn = Number( sizeColumn[1] );
                    htmlItem.numColumns = -1;
                    numColumns += htmlItem.sizeColumn;
                    htmlItem.value = item.innerHTML.trim();
                    if ( label ) {
                        htmlItem.label = label;
                    }
                    items.push( htmlItem );
                }
            } );
            items.forEach( function( item ) {
                if ( item.numColumns === -1 ) {
                    item.numColumns = numColumns;
                }
            } );
        } );
    } else {
        // Conditional groups have to be wrapped by a layer so childNodes gets the complete shortcode
        element.innerHTML = element.innerHTML
            .replace( '[cred_show_group', '<div>[cred_show_group' )
            .replace( '[/cred_show_group]', '[/cred_show_group]</div>' );
        Array.apply([], element.childNodes).filter( function(child) {
            if (child.nodeType === Node.TEXT_NODE) {
                return child.textContent.trim();
            }
            return true;
        } ).forEach( function( item ) {
            var label = getLabel( item );
            var itemHTML = item.nodeType === Node.TEXT_NODE
                ? item.textContent
                : item.innerHTML;
            // An item might have several shortcodes
            var shortcodes = itemHTML.match(wp.shortcode.regexp('[\\w-]+'))
            if ( shortcodes ) {
                shortcodes.forEach( function( shortcodeHTML ) {
					var itemParsed = parseShortcode( shortcodeHTML );
                    if ( itemParsed ) {
                        itemParsed.sizeColumn = 1;
                        itemParsed.numColumns = 1;
                        if ( label ) {
                            itemParsed.label = label;
                        }
                        items.push( itemParsed );
                    }
                } );
            }
        } );
    }
    return items;
};

/**
 * Returns the field type depending on the shortcode, necessary when parsing back to D&D scaffold
 *
 * @param {object} field
 * @return {string}
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.prototype.getFieldTypeFromField = function( field ) {
    if ( field.field === 'post_parent' ) {
        return 'hierarchicalParent';
    }
    if ( field.shortcode === 'cred_generic_field' ) {
        return 'generic';
    }
    if ( [ 'parent', 'child' ].includes( field[ this.scaffold_field_id ] ) ) {
        return 'relationship';
    }
    if ( [ 'post_content', '_featured_image', 'post_excerpt', 'post_title' ].includes( field[ this.scaffold_field_id ] ) ) {
        return 'basic';
    }
    if ( [ 'form_messages', 'form_submit' ].includes( field[ this.scaffold_field_id ] ) ) {
        return 'formElement';
    }
    if ( [ 'feedback', 'submit', 'cancel' ].includes( field[ this.scaffold_field_id ] ) ) {
        return 'form-elements';
    }
    if ( [ 'category', 'post_tag' ].includes( field[ this.scaffold_field_id ] ) ) {
        return 'taxonomy';
    }

    return 'meta';
}

/**
 * Displays the tooltip depending on some conditions
 *
 * @param {object} tip Tippy reference
 * @since 2.3
 */
Toolset.CRED.ScaffoldEditor.maybeShowTooltip = function( tip ) {
    var hide = tip.reference.classList.contains('cred-editor-scaffold-item-container') && ! jQuery( tip.reference ).closest( '.js-cred-editor-scaffold-sidebar' ).length;
    if ( !! tip.reference.dataset.tippyCondition ) {
        if ( 'text-not-visible' === tip.reference.dataset.tippyCondition ) {
            var titleNode = tip.reference.querySelector( '.js-cred-editor-scaffold-field-title' );
            if ( titleNode) {
                hide = titleNode.offsetWidth === titleNode.scrollWidth;
            }
        }
    }
    if ( ! tip.reference.dataset.tippyContent ) {
        hide = true;
    }
    // In relationship forms, due to ko, the content may be not set during object initialization.
    if ( ! tip.props.content && !! tip.reference.dataset.tippyContent ) {
        tip.setContent( tip.reference.dataset.tippyContent )
    }
    tip.popper.style.display = hide ? 'none' : null;
    if ( ! hide ) {
        document.querySelectorAll('.tippy-popper').forEach( function( popper ) {
            if (popper.style.display !== 'none' && popper.getAttribute( 'id' ) !== tip.id) {
            popper._tippy.hide();
            }
        } );
    }
}
