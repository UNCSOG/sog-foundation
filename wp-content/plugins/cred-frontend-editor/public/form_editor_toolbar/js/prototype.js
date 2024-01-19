/**
 * Manage the form editor toolbar.
 *
 * @since 2.1
 * @package CRED
 */

var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.EditorToolbarPrototype = function( $ ) {

    /**
     * Localization skeleton, placeholders for missing pieces.
     *
     * @since 2.1
     */
    this.i18n = {
        action: {
            loading: '',
            insert: '',
            cancel: '',
            back: ''
        },
        dialog: {
            fields: {
                header: ''
            },
            genericFields: {
                header: '',
                fields: {}
            },
            shortcode: {
                header: '',
                group: {
                    header: ''
                }
            },
            conditionalGroups: {
                header: '',
                warning: '',
                edit_manually: '',
                edit_gui: ''
            },
            placeholders: {
                header: ''
            }
        },
        messages: {
            selection_missing: ''
        },
        data: {
            ajaxurl: '',
            requestObjectFields: {
                action: '',
                nonce: ''
            },
            shortcodes: {
                form_container: '',
            },
            fields: {
                labels: {},
                fields: {
                    formElements: {}
                }
            },
            scaffold: {
                fields: {
                    formElements: {}
                }
            },
            placeholders: {}
        }
    };

    this.fieldsCache = {};

    this.templates = {};
    this.templates.fields = {};
    this.templates.genericFields = {};
    this.templates.conditionalGroups = {};
    this.templates.placeholders = {};

    this.dialogs = {};

};

/**
 * Init localization.
 *
 * Should be overriden by implementation objects.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initI18n = function() {
    alert( 'You Toolset.CRED.EditorToolbarPrototype instance should implementation the initI18n method' );
    return this;
};

/**
 * Init cache. Maybe populate it with fields for the currenty selected object key.
 *
 * Should be overriden by implementation objects.
 *
 * @since 2.3.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initCache = function() {
    alert( 'You Toolset.CRED.EditorToolbarPrototype instance should implementation the initCache method' );
    return this;
};

/**
 * Init static variables.
 *
 * Might be overriden by implementation objects, which should invoque
 * this.constructor.prototype.initStatic.call( this );
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initStatic = function() {
    this.shortcodeDialogSpinnerContent = jQuery(
        '<div style="min-height: 150px;">' +
        '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
        '<div class="ajax-loader"></div>' +
        '<p>' + this.i18n.action.loading + '</p>' +
        '</div>' +
        '</div>'
    );

    return this;
};

/**
 * Init Toolset hooks.
 *
 * Might be overriden by implementation objects, which should invoque
 * this.constructor.prototype.initHooks.call( this );
 *
 * @uses Toolset.hooks
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initHooks = function() {
    Toolset.hooks.addAction( 'toolset_text_editor_CodeMirror_init', this.initScaffoldButton );
    Toolset.hooks.addAction( 'cred-action-toolbar-shortcode-dialog-loaded', this.initGenericFieldsSource, 10, this );
    Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-cred_generic_field-computed-attribute-values', this.adjustGenericFieldsAttributes, 10, this );
    Toolset.hooks.addFilter( 'toolset-filter-shortcode-gui-cred_generic_field-crafted-shortcode', this.adjustGenericFieldsShortcodes, 10, this );

    Toolset.hooks.addAction( 'cred-action-maybe-request-and-operate-on-object-fields', this.maybeRequestAndOperateOnObjectFields, 10, this );
    Toolset.hooks.addAction( 'cred-action-after-manual-options-created', this.doManualOptionsSortable, 10, this );
    Toolset.hooks.addAction( 'cred-action-init-conditional-groups', this.initConditionalGroupsEvents, 10, this );
    Toolset.hooks.addAction( 'cred-action-add-conditional-row', this.conditionalAddRow, 10, this );
	Toolset.hooks.addFilter( 'cred-action-get-conditional-shortcode', this.craftConditionalGroupsShortcode, 10, this );

	Toolset.hooks.addFilter( 'cred-filter-get-current-form-target', this.getObjectKey, 10, this );
    return this;
};

/**
 * Init underscore templates.
 *
 * Might be overriden by implementation objects, which should invoque
 * this.constructor.prototype.initTemplates.call( this );
 *
 * @uses wp.template
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initTemplates = function() {
    this.templates.fields.dialog = wp.template( 'cred-editor-fields-dialog' );
    this.templates.genericFields.dialog = wp.template( 'cred-editor-generic-fields-dialog' );
    this.templates.genericFields.optionsManualTable = wp.template( 'cred-editor-generic-fields-options-manual-table' );
    this.templates.genericFields.optionsManualRow = wp.template( 'cred-editor-generic-fields-options-manual-row' );
    this.templates.fields.item = wp.template( 'cred-editor-fields-item' );
    this.templates.conditionalGroups.dialog = wp.template( 'cred-editor-conditional-groups-dialog' );
    this.templates.conditionalGroups.row = wp.template( 'cred-editor-conditional-groups-row' );
    this.templates.placeholders.dialog = wp.template( 'cred-editor-placeholders-dialog' );
    this.templates.placeholders.item = wp.template( 'cred-editor-placeholders-item' );

    this.templates.shortcodeGui = Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-templates', {} );

    return this;
};

/**
 * Init GUI dialogs:
 * - Scaffold dialog.
 * - Fields dialog.
 * - Field shortcode dialog.
 *
 * Might be overriden by implementation objects, which should invoque
 * this.constructor.prototype.initDialogs.call( this );
 *
 * @uses jQuery.dialog
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initDialogs = function() {
    var currentInstance = this;

    if ( ! jQuery( '#js-cred-editor-scaffold-content-container' ).length ) {
        jQuery( 'body' ).append( '<div id="js-cred-editor-scaffold-content-container" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container js-cred-editor-scaffold-content-container"></div>' );
    }
    this.dialogs.scaffold = jQuery( '#js-cred-editor-scaffold-content-container' ).dialog({
        dialogClass: 'toolset-ui-dialog toolset-ui-dialog-responsive',
        autoOpen:	false,
        modal:    true,
        width:    '90%',
        title:    'currentInstance.i18n.dialog.scaffold.header',
        resizable:	false,
        draggable:	false,
        show: {
            effect:    "blind",
            duration:	800
        },
        open: function( event, ui ) {
            jQuery( 'body' ).addClass('modal-open');
            currentInstance.repositionDialog();
        },
        close: function( event, ui ) {
            jQuery( 'body' ).removeClass( 'modal-open' );
        },
        buttons:[
            {
                class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-cred-editor-scaffold-craft',
                text: currentInstance.i18n.action.insert,
                click: function() {
                    currentInstance.insertScaffold();
                }
            },
            {
                class: 'button-secondary js-cred-editor-scaffold-close',
                text: currentInstance.i18n.action.cancel,
                click: function() {
                    jQuery( this ).dialog( "close" );
                }
            }
        ]
    });

    if ( ! jQuery( '#js-cred-editor-fields-dialog-container' ).length ) {
        jQuery( 'body' ).append( '<div id="js-cred-editor-fields-dialog-container" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container js-cred-editor-fields-dialog-container"></div>' );
    }
    this.dialogs.fields = jQuery( '#js-cred-editor-fields-dialog-container' ).dialog({
        dialogClass: 'toolset-ui-dialog toolset-ui-dialog-responsive',
        autoOpen:	false,
        modal:    true,
        width:    '90%',
        title:    currentInstance.i18n.dialog.fields.header,
        resizable:	false,
        draggable:	false,
        show: {
            effect:    "blind",
            duration:	800
        },
        open: function( event, ui ) {
            jQuery( 'body' ).addClass('modal-open');
            currentInstance.repositionDialog();
        },
        close: function( event, ui ) {
            jQuery( 'body' ).removeClass( 'modal-open' );
        }
    });

    if ( ! jQuery( '#js-cred-editor-generic-fields-dialog-container' ).length ) {
        jQuery( 'body' ).append( '<div id="js-cred-editor-generic-fields-dialog-container" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container js-cred-editor-generic-fields-dialog-container"></div>' );
    }
    this.dialogs.genericFields = jQuery( '#js-cred-editor-generic-fields-dialog-container' ).dialog({
        dialogClass: 'toolset-ui-dialog toolset-ui-dialog-responsive',
        autoOpen:	false,
        modal:    true,
        width:    '90%',
        title:    currentInstance.i18n.dialog.genericFields.header,
        resizable:	false,
        draggable:	false,
        show: {
            effect:    "blind",
            duration:	800
        },
        open: function( event, ui ) {
            jQuery( 'body' ).addClass('modal-open');
            currentInstance.repositionDialog();
        },
        close: function( event, ui ) {
            jQuery( 'body' ).removeClass( 'modal-open' );
        }
    });

    if ( ! jQuery( '#js-cred-editor-shortcode-dialog-container' ).length ) {
        jQuery( 'body' ).append( '<div id="js-cred-editor-shortcode-dialog-container" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container js-cred-editor-shortcode-dialog-container"></div>' );
    }
    this.dialogs.shortcode = jQuery( '#js-cred-editor-shortcode-dialog-container' ).dialog({
        dialogClass: 'toolset-ui-dialog toolset-ui-dialog-responsive',
        autoOpen:	false,
        modal:    true,
        width:    '90%',
        title:    currentInstance.i18n.dialog.shortcode.header,
        resizable:	false,
        draggable:	false,
        show: {
            effect:    "blind",
            duration:	800
        },
        open: function( event, ui ) {
            jQuery( 'body' ).addClass('modal-open');
            currentInstance.repositionDialog();
        },
        close: function( event, ui ) {
            jQuery( 'body' ).removeClass( 'modal-open' );
        },
        buttons:[
            {
                class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-cred-editor-field-shortcode-craft',
                text: currentInstance.i18n.action.insert,
                click: function() {
                    var shortcodeToInsert = Toolset.hooks.applyFilters( 'toolset-filter-get-crafted-shortcode', false, jQuery( this ) );
                    // shortcodeToInsert will fail on validation failure
                    if ( shortcodeToInsert ) {
                        jQuery( this ).dialog( "close" );
                        Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcodeToInsert );
                    }
                }
            },
            {
                class: 'toolset-shortcode-gui-dialog-button-align-right button-secondary toolset-shortcode-gui-dialog-button-back js-cred-editor-field-shortcode-back',
                text: currentInstance.i18n.action.back,
                click: function() {
                    jQuery( this ).dialog( "close" );
                    var caller = jQuery( this ).dialog( "option", "caller" );
                    currentInstance.openDialog( caller );
                }
            },
            {
                class: 'button-secondary toolset-shortcode-gui-dialog-button-close js-cred-editor-field-shortcode-close',
                text: currentInstance.i18n.action.cancel,
                click: function() {
                    jQuery( this ).dialog( "close" );
                }
            }
        ]
    });

    if ( ! jQuery( '#js-cred-editor-conditional-groups-dialog-container' ).length ) {
        jQuery( 'body' ).append( '<div id="js-cred-editor-conditional-groups-dialog-container" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container js-cred-editor-conditional-groups-dialog-container"></div>' );
    }
    this.dialogs.conditionalGroups = jQuery( '#js-cred-editor-conditional-groups-dialog-container' ).dialog({
        dialogClass: 'toolset-ui-dialog toolset-ui-dialog-responsive',
        autoOpen:	false,
        modal:    true,
        width:    '90%',
        title:    currentInstance.i18n.dialog.conditionalGroups.header,
        resizable:	false,
        draggable:	false,
        show: {
            effect:    "blind",
            duration:	800
        },
        open: function( event, ui ) {
            jQuery( 'body' ).addClass('modal-open');
            currentInstance.repositionDialog();
        },
        close: function( event, ui ) {
            jQuery( 'body' ).removeClass( 'modal-open' );
        },
        buttons:[
            {
                class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-cred-editor-field-shortcode-craft',
                text: currentInstance.i18n.action.insert,
                click: function() {
                    var shortcodeToInsert = currentInstance.craftConditionalGroupsShortcode( jQuery( this ) );
                    jQuery( this ).dialog( "close" );
                    Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcodeToInsert );
                }
            },
            {
                class: 'button-secondary toolset-shortcode-gui-dialog-button-close js-cred-editor-field-shortcode-close',
                text: currentInstance.i18n.action.cancel,
                click: function() {
                    jQuery( this ).dialog( "close" );
                }
            }
        ]
    });

    if ( ! jQuery( '#js-cred-editor-placeholders-dialog-container' ).length ) {
        jQuery( 'body' ).append( '<div id="js-cred-editor-placeholders-dialog-container" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container js-cred-editor-placeholders-dialog-container"></div>' );
    }
    this.dialogs.placeholders = jQuery( '#js-cred-editor-placeholders-dialog-container' ).dialog({
        dialogClass: 'toolset-ui-dialog toolset-ui-dialog-responsive',
        autoOpen:	false,
        modal:    true,
        width:    '90%',
        title:    currentInstance.i18n.dialog.placeholders.header,
        resizable:	false,
        draggable:	false,
        show: {
            effect:    "blind",
            duration:	800
        },
        open: function( event, ui ) {
            jQuery( 'body' ).addClass('modal-open');
            currentInstance.repositionDialog();
        },
        close: function( event, ui ) {
            jQuery( 'body' ).removeClass( 'modal-open' );
        }
    });

    jQuery( window ).resize( currentInstance.resizeWindowEvent );

    return currentInstance;
};

/**
 * Open a registered dialog on demand.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.openDialog = function( dialogId ) {
    if ( _.has( this.dialogs, dialogId ) ) {
        this.dialogs[ dialogId ].dialog( 'open' );
    }
};

/**
 * Reposition the Types dialogs based on the current window size.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.repositionDialog = function() {
    var winH = jQuery( window ).height() - 100;

    _.each( this.dialogs, function( dialog, key, list ) {
        dialog.dialog( "option", "maxHeight", winH );
        dialog.dialog( "option", "position", {
            my:        "center top+50",
            at:        "center top",
            of:        window,
            collision: "none"
        });
    });

};

/**
 * Callback for the window.resize event.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.resizeWindowEvent = _.debounce( function() {
    Toolset.CRED.EditorToolbarPrototype.prototype.repositionDialog();
}, 200);

/**
 * Highlight the scaffold button when the editor is empty.
 *
 * @param editorId string
 *
 * @uses Codemirror
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initScaffoldButton = function( editorId ) {
    var $button = jQuery( '.js-cred-form-content-scaffold' ),
        buttonTargetId = $button.data( 'target' );

    if ( editorId != buttonTargetId ) {
        return;
    }

    if ( '' == icl_editor.codemirrorGet( editorId ).getValue() ) {
        $button.addClass( 'button-primary-toolset' );
    }
};

/**
 * Init the generic fields source options, for those generic field types that have it.
 *
 * Generic fields with source can define options manually by setting a value and a label.
 *
 * @param {string} shortcode
 * @param {object} data
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initGenericFieldsSource = function( shortcode, data ) {
    var $parentContainer = arguments.length === 3 ? arguments[2] : null;
    if ( 'cred_generic_field' !== shortcode ) {
        return;
    }
    if (
        ! _.has( data, 'parameters' )
        || ! _.has( data.parameters, 'type' )
        || ! _.contains( [ 'checkboxes', 'multiselect', 'radio', 'select'  ], data.parameters.type )
    ) {
        return;
    }

    jQuery( '[id=cred_generic_field-options]', $parentContainer ).hide();

    var currentInstance = this;
    var $optionsManualTable = this.templates.genericFields.optionsManualTable( { type: data.parameters.type } );

    jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-manual', $parentContainer ).append( $optionsManualTable );
    Toolset.hooks.doAction( 'cred-action-after-manual-options-created', $parentContainer );

    this.addGenericFieldOptionManualRow( { type: data.parameters.type }, $parentContainer );

    var $optionsContainer = jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-options', $parentContainer ).closest( 'li' );
    jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-shortcode', $parentContainer )
        .detach()
        .appendTo( $optionsContainer );
    jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-manual', $parentContainer )
        .detach()
        .appendTo( $optionsContainer );

    jQuery( '[id=cred_generic_field-manual]', $parentContainer ).hide();

    this.adjustGenericFieldSourceOptions( $parentContainer );

};

/**
 * Add a new row to the genric field options.
 *
 * @param {string} fieldType
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.addGenericFieldOptionManualRow = function( fieldType ) {
    var currentInstance = this;
    var $parentContainer = arguments.length === 2 ? arguments[1] : null;
    var $optionsManualRow = this.templates.genericFields.optionsManualRow( fieldType );

    var $manualTable = jQuery( '.js-cred-editor-generic-fields-options-manual-table tbody', $parentContainer );
    $manualTable.append( $optionsManualRow );
    if ( $manualTable.data( 'sortable' ) ) {
        Toolset.hooks.doAction( 'cred-action-after-manual-options-created', $parentContainer );
    } else {
        $manualTable.sortable();
    }
    $manualTable.sortable( 'refresh' );
};

/**
 * Show or hide some settings depending on where the generic field options should come from.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.adjustGenericFieldSourceOptions = function() {
    var $parentContainer = arguments.length === 1 ? arguments[0] : null;
    var source = jQuery( '#cred_generic_field-source', $parentContainer )
        .find( 'input[name="cred_generic_field-source"]:checked' )
            .val();

    switch ( source ) {
        case 'manual':
            jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-shortcode', $parentContainer ).hide()
            jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-manual', $parentContainer ).fadeIn();
            jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-options input', $parentContainer )
                .val( jQuery( '#cred_generic_field-manual', $parentContainer ).val() );
            break;
        case 'shortcode':
            jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-manual', $parentContainer ).hide();
            jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-shortcode', $parentContainer ).fadeIn();
            jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-options input', $parentContainer )
                .val( jQuery( '#cred_generic_field-shortcode', $parentContainer ).val() );
            break;
    }
};

/**
 * Push the chnages in generic fields manual options to the hidden input holding the final value.
 *
 * @param {Object} $parentContainer jQuery object of the container. Since the 'form' is included in several places, jQuery selectors must be scoped in a container.
 * @since 2.1
 * @since 2.2 new param introduced with the parent container
 */
Toolset.CRED.EditorToolbarPrototype.prototype.adjustGenericFieldOptionManualValue = function( $parentContainer) {
    var value = '',
        rows = [],
        rowLabel = '',
        rowDefault = '',
        rowValue = '';

    $parentContainer.find( '.js-cred-editor-generic-fields-options-manual-table tbody tr' ).each( function() {
        rowLabel = jQuery( this ).find( '.js-cred-editor-generic-fields-options-manual-row-label input' ).val();
        rowValue = jQuery( this ).find( '.js-cred-editor-generic-fields-options-manual-row-value input' ).val();
        rowDefault = jQuery( this ).find( '.js-cred-editor-generic-fields-options-manual-row-default input' ).is(':checked');
        if ( rowLabel == '' && rowValue == '' ) {
            return true;
        }
        if ( $parentContainer.hasClass('ui-dialog-content') ) {
            rows.push( '{"value":"' + rowValue + '","label":"' + rowLabel + '"}' );
        } else {
            rows.push( '{"value":"' + rowValue + '","label":"' + rowLabel + '","default":' + rowDefault + '}' );
        }

    });

    value += rows.join( ',' );

    $parentContainer.find( '[id=cred_generic_field-manual]' ).val( value ).trigger( 'change' );
};

/**
 * Make sure that helper attributes do not make it into the final generic field shortcode.
 *
 * @param {object} rawAttributes
 * @param {object} data
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.adjustGenericFieldsAttributes = function( rawAttributes, data ) {
    var attributes = jQuery.extend( true, {}, rawAttributes );
    attributes.required = false;
    attributes.validate_format = false;
    attributes.checked = false;
    attributes.default = false;
    attributes.generic_type = false;
    attributes.label = false;
    attributes.source = false;
    attributes.shortcode = false;
    attributes.manual = false;
    attributes.options = false;
    return attributes;
};

/**
 * Adjust the generic field generated shortcode, based on its attributs and pseudoattributes.
 *
 * @param {string} shortcodeString
 * @param {object} data
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.adjustGenericFieldsShortcodes = function( shortcodeString, data ) {
    var shortcodeContent = '',
        shortcodeContentArgs = [],
        attributes = jQuery.extend( true, {}, data.rawAttributes ),
        processBooleanPseudoattribute = function( attributeName ) {
            if ( _.has( attributes, attributeName ) ) {
                var attributeValue = ( 'yes' == attributes[ attributeName ] ) ? '1' : '0';
                shortcodeContentArgs.push( '"' + attributeName + '":' + attributeValue );
            }
        };

    processBooleanPseudoattribute( 'required' );
    processBooleanPseudoattribute( 'validate_format' );
    processBooleanPseudoattribute( 'checked' );

    if ( _.has( attributes, 'default' ) ) {
		// It forces the default value in case it is not set and it is required
		const defaultValue = !! attributes.required && attributes.required === 'yes' && ! attributes.default ?
			'1' :
			attributes.default;
        shortcodeContentArgs.push( '"default":"' + defaultValue + '"' );
    } else if (
        _.has( attributes, 'source' )
        && _.has( attributes, 'type' )
        && 'manual' == attributes.source
        && _.contains( [ 'checkboxes', 'multiselect', 'radio', 'select' ], attributes.type )
    ) {
        var defaultSelected = [];
        jQuery( '.js-cred-editor-generic-fields-options-manual-row-default input:checked', data.container ).each( function() {
            defaultSelected.push( jQuery( this ).closest( 'tr' ).find( '.js-cred-editor-generic-fields-options-manual-row-value input' ).val() );
        });
        defaultSelected = defaultSelected.map( function( value ) {
            return '"' + value + '"';
        });
        shortcodeContentArgs.push( '"default":[' + defaultSelected.join( ',' ) + ']' );
    }

    if ( _.has( attributes, 'label' ) ) {
        shortcodeContentArgs.push( '"label":"' + attributes.label + '"' );
    }

    if ( _.has( attributes, 'options' ) ) {
        if  (
            _.has( attributes, 'source' )
            && 'shortcode' == attributes.source
        ) {
            // SHortcode source for options needs to separate the shortcode from the array delimiter
            shortcodeContentArgs.push( '"options":[ ' + attributes.options + ' ]' );
        } else {
            if ( attributes.options.match(/^\[.*\]$/) ) {
                shortcodeContentArgs.push( '"options":' + attributes.options );
            } else {
                shortcodeContentArgs.push( '"options":[' + attributes.options + ']' );
            }
        }
    }

    if (
        _.has( attributes, 'generic_type' )
        && '' != attributes.generic_type
    ) {
        shortcodeContentArgs.push( '"generic_type":"' + attributes.generic_type + '"' );
        if ( 'user_id' == attributes.generic_type ) {
            shortcodeContentArgs.push( '"persist":1' );
        }
    }

    shortcodeContent += "\n{\n" + shortcodeContentArgs.join( ",\n" ) + "\n}\n";

	return shortcodeString + shortcodeContent + '[/' + data.shortcode + ']';
};

/**
 * Generic method to request fields by a given key, and act upon the cache afterwards.
 *
 * @param {callable} callback
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.maybeRequestAndOperateOnObjectFields = function( callback ) {
    var currentInstance = this;

    var objectKey = currentInstance.getObjectKey(),
        objectCacheKey = _.isArray( objectKey ) ? currentInstance.getObjectCacheKey( objectKey ) : objectKey;

    if ( _.isEmpty( objectKey ) ) {
        return;
    }

    if ( _.has( currentInstance.fieldsCache, objectCacheKey ) ) {
		/**
		 * Adjust requested fields, in case we need to modify them on the fly.
		 *
		 * @param object fields
		 * @param object Extra attributes
		 * @since 2.5.2
		 */
		var adjustedFields = Toolset.hooks.applyFilters(
			'cred-filter-maybe-adjust-requested-object-fields',
			currentInstance.fieldsCache[ objectCacheKey ],
			{
				objectCacheKey: objectCacheKey
			}
		);
        callback.call( currentInstance, adjustedFields );
    } else {
        currentInstance.requestObjectFields( objectKey, function( newObjectCacheKey ) {
			/**
			 * Adjust requested fields, in case we need to modify them on the fly.
			 *
			 * @param object fields
			 * @param object Extra attributes
			 * @since 2.5.2
			 */
			var adjustedFields = Toolset.hooks.applyFilters(
				'cred-filter-maybe-adjust-requested-object-fields',
				currentInstance.fieldsCache[ objectCacheKey ],
				{
					objectCacheKey: objectCacheKey
				}
			);
            callback.call( currentInstance, adjustedFields );
        });
    }
};

/**
 * Request the fields for a given post type.
 *
 * @param objectKey string.
 * @param callback callable
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.requestObjectFields = function( objectKey, callback ) {
    var currentInstance = this,
    objectCacheKey = _.isArray( objectKey ) ? currentInstance.getObjectCacheKey( objectKey ) : objectKey,
        data = {
            action:   currentInstance.i18n.data.requestObjectFields.action,
            wpnonce:  currentInstance.i18n.data.requestObjectFields.nonce,
            objectKey: objectKey,
        };

    jQuery.ajax({
        url:      currentInstance.i18n.data.ajaxurl,
        data:     data,
        dataType: 'json',
        type:     "GET",
        success:  function( originalResponse ) {
            var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
            if ( response.success ) {
                currentInstance.fieldsCache[ objectCacheKey ] = response.data;
                callback.call( currentInstance, objectCacheKey );
            } else {

            }
        },
        error: function ( ajaxContext ) {

        }
    });
};


/**
 * Get the current form type: new or edit. Where available.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.getFormType = function() {
    return jQuery( 'input[name="_cred[form][type]"]:checked' ).val();
};

/**
 * Get the current form slug.
 *
 * Should be overriden by implementation objects.
 *
 * @return string
 * @since 2.2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.getFormSlug = function() {
    console.error( 'You Toolset.CRED.EditorToolbarPrototype instance should implement the getFormSlug method' );
    return '';
};

/**
 * Get the object key to manipulate fields for.
 *
 * Should be overriden by implementation objects.
 *
 * @return string
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.getObjectKey = function() {
    console.error( 'You Toolset.CRED.EditorToolbarPrototype instance should implement the getObjectKey method' );
    return '';
};

/**
 * Get the object key to cache fields.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.getObjectCacheKey = function( objectKey ) {
    return objectKey.join( '|:|' );
};


/**
 * Generate the fields dialog content from a post type fields cache.
 *
 * @param objectCacheKey string
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.generateFieldsDialogContentFromCache = function( objectCacheKey ) {
    var templateData = jQuery.extend( true, {},
        this.fieldsCache[ objectCacheKey ],
        { templates:    this.templates },
        { labels:       this.i18n.data.fields.labels },
        { formElements: jQuery.extend( true, {},
            this.i18n.data.fields.fields.formElements,
            ( !! this.i18n.data.scaffold ? this.i18n.data.scaffold.fields.formElements : {} )
        ) },
        { environment: {
            formType: this.getFormType()
        } }
    );

    this.dialogs.fields.html( templateData.templates.fields.dialog( templateData ) );
};

/**
 * Insert a field shortcode without extra options.
 *
 * @param $fieldButton jQuery object
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.insertOptionlessField = function( $fieldButton ) {
    var shortcode = $fieldButton.data( 'shortcode' ),
		rawAttributes = $fieldButton.data( 'attributes' ),
        output = '';

	rawAttributes = ( _.size( rawAttributes ) == 0 ) ? {} : rawAttributes;

	var attributes = Toolset.hooks.applyFilters(
		'toolset-filter-shortcode-gui-' + shortcode + '-computed-attribute-values',
		jQuery.extend( true, {}, rawAttributes ),
		{
			shortcode: shortcode,
			rawAttributes: rawAttributes
		}
	);

    output = '[' + shortcode;
    _.each( attributes, function( value, key, list ) {
		if ( value ) {
			output += ' ' + key + '="' + value + '"';
		}
    });
    output += ']';

    if ( this.i18n.data.shortcodes.form_container == shortcode ) {
        output += "\n\n";
        output += '[/' + shortcode + ']';
    }

    this.dialogs.fields.dialog( 'close' );
    icl_editor.insert( output );
};

/**
 * Open the shortcode dialog to set options for a field.
 *
 * @param $fieldButton jQuery object
 *
 * @since 2.1
 * @todo Review the action executed at the end when porting the associations form script
 */
Toolset.CRED.EditorToolbarPrototype.prototype.openFieldDialog = function( $fieldButton ) {
    var shortcode = $fieldButton.data( 'shortcode' ),
        label = $fieldButton.data( 'label' ),
        attributes = $fieldButton.data( 'attributes' ),
		options = $fieldButton.data( 'options' );

	// Remove the "label" option as it has no effect on fields added individually on Expert mode,
	// but keep it for genreic field types that might include it, like checkbox generic fields.
	if ( 'generic' != $fieldButton.data( 'fieldtype' ) ) {
		options = _.omit( options, 'label' );
	}

    var templateData = jQuery.extend( true, {},
        {
            shortcode: shortcode,
            templates:  this.templates.shortcodeGui,
            parameters: attributes,
            attributes: {
                singleGroup: {
                    header: this.i18n.dialog.shortcode.group.header,
                    fields: options
                }
            }
        }
    );

    switch( $fieldButton.data( 'fieldtype' ) ) {
        case 'generic':
            this.dialogs.genericFields.dialog( 'close' );
            this.dialogs.shortcode.dialog( "option", "caller", 'genericFields' );
            break;
        default:
            this.dialogs.fields.dialog( 'close' );
            this.dialogs.shortcode.dialog( "option", "caller", 'fields' );
            break;
    }

    this.dialogs.shortcode.dialog( 'open' ).dialog({
        title: label
    });
    this.dialogs.shortcode.html( this.shortcodeDialogSpinnerContent );

    this.dialogs.shortcode.html( templateData.templates.dialog( templateData ) );

    Toolset.hooks.doAction( 'cred-action-toolbar-shortcode-dialog-loaded', shortcode, templateData, this.dialogs.shortcode );
};

/**
 * Generate the fields dialog content from a post type fields cache.
 *
 * @param objectCacheKey string
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.generateConditionalGroupsDialogContentFromCache = function( objectCacheKey ) {
    var templateData = jQuery.extend( true, {},
            this.fieldsCache[ objectCacheKey ],
            { templates:    this.templates },
            { dialog:       this.i18n.dialog.conditionalGroups }
        ),
        $contentEditor = Toolset.hooks.applyFilters( 'cred_editor_get_content_editor', null ),
        contentToWrap = $contentEditor.somethingSelected() ? $contentEditor.getSelection() : '';

    this.dialogs.conditionalGroups.html( templateData.templates.conditionalGroups.dialog( templateData ) );
    this.dialogs.conditionalGroups.data( "objectCacheKey", objectCacheKey );
    this.dialogs.conditionalGroups.find( '.js-cred-editor-conditional-groups-general-container' ).data( "useGui", true );
    this.dialogs.conditionalGroups.data( "contentToWrap", contentToWrap );
    this.conditionalAddRow( this.dialogs.conditionalGroups );
};

/**
 * Add a new row to the conditional output dialog.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.conditionalAddRow = function( $container ) {
    var objectKey = this.getObjectKey(),
        objectCacheKey = _.isArray( objectKey ) ? this.getObjectCacheKey( objectKey ) : objectKey;
        cachedFields = this.fieldsCache[ objectCacheKey ],
        html = this.templates.conditionalGroups.row( cachedFields || {} );

    $container.find( '.js-cred-editor-conditional-groups-conditions-body' ).append( html );
    /**
     * remove operator for first row
     */
    this.conditionalDontRemoveFirstRow( $container );
};

/**
 * Adjust the buttons to remove conditional rows so at least row can not be removed.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.conditionalDontRemoveFirstRow = function( $container ) {
    if ( $container.find( '.js-cred-editor-conditional-groups-item' ).length == 1) {
        $container.find( '.js-cred-editor-conditional-groups-item-remove' ).hide();
    } else {
        $container.find( '.js-cred-editor-conditional-groups-item-remove' ).show();
    }
    $container.find( '.js-cred-editor-conditional-groups-conditions-body .js-cred-editor-conditional-groups-item:first-child .js-cred-editor-conditional-groups-connect' ).html( '&nbsp;' );
}

/**
 * Craft the condition attribut based on the rows GUI, for different contexts.
 *
 * @param {string} mode
 * @param {Object} $container jQuery object
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.createConditionalAttributeFromGui = function( mode, $container ) {
    var value = '';
    $container.find( '.js-cred-editor-conditional-groups-item' ).each( function() {
        var tr = jQuery( this );
        if ( jQuery( '.js-cred-editor-conditional-groups-origin :selected', tr ).val() ) {
            if ( value ) {
                if ( 'multiline' == mode ) {
                    value += "\n";
                }
                value += ' ' + jQuery( '.js-cred-editor-conditional-groups-connect :checked', tr ).val() + ' ';
                if ( 'multiline' == mode ) {
                    value += "\n";
                }
            }
            value += '( ';
            value += '$(' + jQuery( '.js-cred-editor-conditional-groups-origin :selected', tr ).val() + ')';
            value += ' ';
            value += jQuery( '.js-cred-editor-conditional-groups-operator :selected', tr).val();
            value += ' \'';
            value += jQuery( '.js-cred-editor-conditional-groups-value input', tr ).val();
            value += '\' ';
            value += ')';
        }
    });
    return value;
};

/**
 * Craft the conditional output shortcode based o its dialog settings.
 *
 * @param {Object} $container jQuery object
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.craftConditionalGroupsShortcode = function( $container ) {
    var editUsingGui = $container.find( '.js-cred-editor-conditional-groups-general-container' ).data( "useGui" ),
        shortcode = '',
        shortcodeContent = $container.data( "contentToWrap" );
    if ( ! shortcodeContent ) {
        shortcodeContent = '';
    }
    shortcode += '[' + this.i18n.dialog.conditionalGroups.shortcode;
    if ( editUsingGui !== false ) { // It could be undefined :(
        shortcode += ' if="' + this.createConditionalAttributeFromGui( 'inline', $container ) + '"';
    } else {
        shortcode += ' if="' + $container.find( '[id=cred-editor-conditional-groups-custom-expressions]' ).val().replace(/\n/g, "") + '"';
    }
    shortcode += ' mode="' + $container.find( '.js-cred-editor-conditional-groups-options-effect' ).val() + '"';
    shortcode += ']' + shortcodeContent + '[/' + this.i18n.dialog.conditionalGroups.shortcode + ']';
    return shortcode;
};


/**
 * Add events to this prototype.
 *
 * @since 2.1
 * @todo Move to the editor.scaffold.js script
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initScaffoldItemEvents = function() {

    /**
	 * Toggle the visibility of the options for the scaffold elements that include them, on header click.
	 *
	 * @since 2.1
	 */
	jQuery( document ).on( 'click', '.js-cred-editor-scaffold-item-container-can-toggle .js-cred-editor-scaffold-item-header', function( e ) {
        e.preventDefault();
        var $itemContainer = jQuery( this ).closest( '.js-cred-editor-scaffold-item-container' ),
            $toggleControl = $itemContainer.find( '.js-cred-editor-scaffold-item-options-toggle' ),
            $toggleContainer = $itemContainer.find( '.js-cred-editor-scaffold-item-options' ),
            $targetRow = $itemContainer.closest( '.cred-editor-scaffold-item-wrapper-row' );

        if ( jQuery( this ).closest( '#js-cred-editor-scaffold-sidebar' ).length > 0 ) {
            // Do not open (hidden) options when on the drag&drop editor sidebar
            return;
		}
		if ( $itemContainer.hasClass( 'js-cred-editor-scaffold-item-container-disabled' ) ) {
            // Do not open (hidden) options for autogenerated fields
            return;
        }
        if ( $itemContainer.hasClass( 'cred-editor-scaffold-item-container-options-opened' ) ) {
            jQuery( '.js-cred-editor-scaffold-item-options:visible .js-cred-editor-scaffold-options-close' ).click();
            return;
        }

        // Close other options
        jQuery( '.js-cred-editor-scaffold-item-options:visible .js-cred-editor-scaffold-options-close' ).click();
        $targetRow.find( '.cred-editor-scaffold-item-wrapper-conditionals .cred-editor-scaffold-item-wrapper-row' ).addClass( 'hidden' );

        $toggleControl.toggleClass( 'fa-caret-down fa-caret-up' );
        $itemContainer.addClass( 'cred-editor-scaffold-item-container-options-opened js-cred-editor-scaffold-item-container-options-opened' );
        if ( $targetRow.length ) {
            $targetRow.after( $toggleContainer );
        } else {
            $itemContainer.after( $toggleContainer );
        }
        $toggleContainer.slideToggle( 'fast', function() {
            var $textarea = $toggleContainer.find( 'textarea' );
            var codeMirrorInstance = icl_editor.isCodeMirror( $textarea );
            if ( codeMirrorInstance ) {
                codeMirrorInstance.refresh();
                codeMirrorInstance.focus();
            }
		} );

		// If the attribute required is selected, I have to mark the Default Value input as required
		$itemContainer.closest( '.js-cred-editor-scaffold-item-list' ).find( 'input[type=radio][name*="-required"]:checked:visible' ).each( function() {
			const requiredItemType = $itemContainer.data( 'attributes' );
			if ( !! requiredItemType.type && requiredItemType.type === 'checkbox' ) {
				const $checkbox = jQuery( this );
				const $defaultValueElement = $checkbox.closest('form').find('input[id*="-default"]');
				if ( $checkbox.val() === 'yes' ) {
					$defaultValueElement.attr( 'required', 'required' );
				} else {
					$defaultValueElement.removeAttr( 'required' );
				}
			}
		} );
    });

    /**
     * Close drag&drop editor fields options
     *
     * @since 2.2
     */
    jQuery( document ).on( 'click', '.js-cred-editor-scaffold-options-close', function( event ) {
        event.stopPropagation();
        event.preventDefault();
        jQuery( '.cred-editor-scaffold-item-wrapper-conditionals .cred-editor-scaffold-item-wrapper-row.hidden' ).removeClass( 'hidden' );
        var $target = jQuery( '.js-cred-editor-scaffold-item-container-options-opened' );
        $target.removeClass( 'cred-editor-scaffold-item-container-options-opened js-cred-editor-scaffold-item-container-options-opened' );
        $target.find( '.js-cred-editor-scaffold-item-options-toggle' ).toggleClass( 'fa-caret-down fa-caret-up' );
        var $optionsContainer = jQuery( this ).closest( '.js-cred-editor-scaffold-item-options' );
        $optionsContainer.hide();
        $target.append( $optionsContainer );
    } );

    /**
     * Prevent icon clicks on the ite header from opening the related options.
     *
     * @since 2.2
     */
    jQuery( document ).on( 'click', '.js-cred-editor-scaffold-item-header i:not(.js-cred-editor-scaffold-item-options-toggle)', function( e ) {
        e.stopPropagation();
    });

};

/**
 * Add events to this prototype.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initFieldsButtonEvent = function() {

    var currentInstance = this;

    /**
     * Open the fields generator dialog on button click.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-form-content-fields', function( e ) {
        e.preventDefault();

        var objectKey = currentInstance.getObjectKey(),
            objectCacheKey = _.isArray( objectKey ) ? currentInstance.getObjectCacheKey( objectKey ) : objectKey;

        if ( _.isEmpty( objectKey ) ) {
            alert( currentInstance.i18n.messages.selection_missing );
            return;
        }

        window.wpcfActiveEditor = jQuery( this ).data( 'target' );
        Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'insert' );

        currentInstance.dialogs.fields.dialog( 'open' );

        if ( _.has( currentInstance.fieldsCache, objectCacheKey ) ) {
            currentInstance.generateFieldsDialogContentFromCache( objectCacheKey );
        } else {
            currentInstance.dialogs.fields.html( currentInstance.shortcodeDialogSpinnerContent );
            currentInstance.requestObjectFields( objectKey, currentInstance.generateFieldsDialogContentFromCache );
        }
    });

};

/**
 * Add events to this prototype.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initFieldsItemEvents = function() {

    var currentInstance = this;

    /**
     * Manage the click event on each element in the fields dialog.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-editor-fields-item', function() {
        var $fieldButton = jQuery( this ),
			options = $fieldButton.data( 'options' );

		// Remove the "label" option as it has no effect on fields added individually on Expert mode,
		// but keep it for genreic field types that might include it, like checkbox generic fields.
		if ( 'generic' != $fieldButton.data( 'fieldtype' ) ) {
			options = _.omit( options, 'label' );
		}

        if (
            options
            && ( _.size( options ) > 0 )
        ) {
            currentInstance.openFieldDialog( $fieldButton );
        } else {
            currentInstance.insertOptionlessField( $fieldButton );
        }
    });

};

/**
 * Init the button for managing generic fields.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initGenericFieldsButtonEvents = function() {
    var currentInstance = this;

    jQuery( document ).on( 'click', '.js-cred-form-content-generic-fields', function( e ) {
        e.preventDefault();

        window.wpcfActiveEditor = jQuery( this ).data( 'target' );
        Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'insert' );

        currentInstance.dialogs.genericFields.dialog( 'open' );

        var templateData = jQuery.extend( true, {},
            { templates:    currentInstance.templates },
            { dialog:       currentInstance.i18n.dialog.genericFields },
            { environment: {
                formType: currentInstance.getFormType()
            } }
        );

        currentInstance.dialogs.genericFields.html( templateData.templates.genericFields.dialog( templateData ) );

    });

};

/**
 * Init the interaction with the generic fields individual dialogs.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initGenericFieldsItemEvents = function() {
    var currentInstance = this;
    var parentSelector = '.cred-editor-scaffold-item-options, #js-cred-editor-shortcode-dialog-container';

    jQuery( document ).on( 'change', '[id=cred_generic_field-source] input[name="cred_generic_field-source"]', function() {
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        currentInstance.adjustGenericFieldSourceOptions( $parentContainer );
    });

    jQuery( document ).on( 'click', '.js-cred-editor-generic-fields-options-manual-row-add', function( e ) {
        e.preventDefault();
        var fieldType = {
            type: jQuery( this ).data( 'type' )
        };
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        currentInstance.addGenericFieldOptionManualRow( fieldType, $parentContainer );
    });

    jQuery( document ).on( 'click', '.js-cred-editor-generic-fields-options-manual-row-remove', function( e ) {
        e.preventDefault();
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        var row = jQuery( this ).closest( 'tr' );
        row.addClass( 'cred-editor-editable-list-item-deleted' );
        row.fadeOut( 400, function() {
            row.remove();
            currentInstance.adjustGenericFieldOptionManualValue( $parentContainer );
        });
    });

    // Unfortunatelly, ids (#) don't work when several DOM elements have the same ID
    jQuery( document ).on( 'change', '[id=cred_generic_field-manual]', _.debounce( function() {
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        $parentContainer.find( '[id=cred_generic_field-options]' )
            .val( jQuery( this ).val() );
    }, 200 ));

    jQuery( document ).on( 'keyup', '[id=cred_generic_field-shortcode]', _.debounce( function() {
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        $parentContainer.find( '[id=cred_generic_field-options]' )
            .val( jQuery( this ).val() );
    }, 200 ));

    jQuery( document ).on( 'keyup', '.js-cred-editor-generic-fields-options-manual-row-label input', _.debounce( function() {
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        currentInstance.adjustGenericFieldOptionManualValue( $parentContainer );
    }, 200 ));

    jQuery( document ).on( 'keyup', '.js-cred-editor-generic-fields-options-manual-row-value input', _.debounce( function() {
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        currentInstance.adjustGenericFieldOptionManualValue( $parentContainer );
    }, 200 ));

    jQuery( document ).on( 'change', '.js-cred-editor-generic-fields-options-manual-row-default input', _.debounce( function() {
        var $parentContainer = jQuery( this ).parents( parentSelector ).first();
        currentInstance.adjustGenericFieldOptionManualValue( $parentContainer );
    }, 200 ));

};

/**
 * Init the conditional groups events: button, dialog interacton.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initConditionalGroupsEvents = function() {
    var currentInstance = this;

    if ( !this.conditionalGroupsAreInitialized) {
        /**
         * Manage the click event on each element in the fields dialog.
         *
         * @since 2.1
         */
        jQuery( document ).on( 'click', '.js-cred-form-content-conditional-groups', function( e ) {
            e.preventDefault();

                var objectKey = currentInstance.getObjectKey(),
                    objectCacheKey = _.isArray( objectKey ) ? currentInstance.getObjectCacheKey( objectKey ) : objectKey;

                if ( _.isEmpty( objectKey ) ) {
                    alert( currentInstance.i18n.messages.selection_missing );
                    return;
                }

            window.wpcfActiveEditor = jQuery( this ).data( 'target' );
            Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'insert' );

            currentInstance.dialogs.conditionalGroups.dialog( 'open' );

            if ( _.has( currentInstance.fieldsCache, objectCacheKey ) ) {
                currentInstance.generateConditionalGroupsDialogContentFromCache( objectCacheKey );
            } else {
                currentInstance.dialogs.conditionalGroups.html( currentInstance.shortcodeDialogSpinnerContent );
                currentInstance.requestObjectFields( objectKey, currentInstance.generateConditionalGroupsDialogContentFromCache );
            }
        });

        jQuery( document ).on( 'click', '.js-cred-editor-conditional-groups-add-term', function( e ) {
            e.preventDefault();
            var $container = jQuery( this ).parents( '.ui-dialog, .cred-editor-scaffold-item-options' );
            currentInstance.conditionalAddRow( $container );
        });

        jQuery( document ).on( 'click', '.js-cred-editor-conditional-groups-item-remove', function() {
            var $container = jQuery( this ).parents( '.ui-dialog, .cred-editor-scaffold-item-options' );
            var row = jQuery( this ).closest( 'tr' );
            row.addClass( 'cred-editor-editable-list-item-deleted' );
            row.fadeOut( 400, function() {
                row.remove();
                currentInstance.conditionalDontRemoveFirstRow( $container );
            });
        });

        jQuery( document ).on( 'click', '.js-cred-editor-conditional-groups-expression-switcher', function( e ) {
            e.preventDefault();
            var thiz = jQuery( this ),
                thiz_container = thiz.closest( '.js-cred-editor-conditional-groups-general-container' ),
                thiz_container_gui = jQuery( '.js-cred-editor-conditional-groups-conditions-container', thiz_container ),
                thiz_container_manual = jQuery( '.js-cred-editor-conditional-groups-custom-expressions-container', thiz_container ),
                thiz_add_condition_button = jQuery( '.js-cred-editor-conditional-groups-add-term', thiz_container ),
                thiz_use_gui = thiz_container.data( "useGui" );

            if ( thiz_use_gui !== false ) { // It could be undefined :(
                thiz.fadeOut( 400 );
                thiz_add_condition_button.fadeOut( 400 );
                thiz_container_gui.fadeOut( 400, function() {
                    thiz_container.data( "useGui", false );
                    jQuery( '#cred-editor-conditional-groups-custom-expressions' )
                        .val( currentInstance.createConditionalAttributeFromGui( 'multiline', thiz_container ) )
                        .data( 'edited', false );
                    thiz.html( currentInstance.i18n.dialog.conditionalGroups.edit_gui ).fadeIn( 400 );
                    thiz_container_manual.fadeIn( 400, function() {

                    });
                });
            } else {
                if ( jQuery( '#cred-editor-conditional-groups-custom-expressions' ).data( 'edited' ) ) {
                    if ( ! confirm( currentInstance.i18n.dialog.conditionalGroups.warning ) ) {
                        return;
                    }
                }
                thiz.fadeOut( 400 );
                thiz_container_manual.fadeOut( 400, function() {
                    thiz_container.data( "useGui", true );
                    thiz.html( currentInstance.i18n.dialog.conditionalGroups.edit_manually ).fadeIn( 400 );
                    thiz_add_condition_button.fadeIn( 400 );
                    thiz_container_gui.fadeIn( 400, function() {

                });
            	});
            }
        });

        jQuery( document ).on( 'keyup', '#cred-editor-conditional-groups-custom-expressions', function() {
            if ( ! jQuery( this ).data( 'edited' ) ) {
                jQuery( this ).data( 'edited', true );
            }
        });
    }
    this.conditionalGroupsAreInitialized = true;
};

/**
 * Init the notiications placeholders events: button, dialog interacton.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initNotificationPlaceholdersEvents = function() {
    var currentInstance = this;

    /**
     * Manage the click event on toolbar buttons.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-form-notification-placeholders', function( e ) {
        e.preventDefault();

        window.wpcfActiveEditor = jQuery( this ).data( 'target' );
        Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'insert' );

        var $notificationContainer = jQuery( this ).closest( '.js-cred-notifications-settings-container' ),
            placeholderTargetKind = jQuery( this ).data( 'kind' ),
            dialogData = {
                itemTemplate: currentInstance.templates.placeholders.item,
                itemList: currentInstance.i18n.data.placeholders,
                target: placeholderTargetKind,
                type: $notificationContainer.find( '.js-cred-notification-trigger:checked' ).val()
            };

        currentInstance.dialogs.placeholders.dialog( 'open' );
        currentInstance.dialogs.placeholders.html( currentInstance.templates.placeholders.dialog( dialogData ) );
    });

    /**
     * Manage the click event on placeholders items.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-editor-placeholders-item', function( e ) {
        e.preventDefault();

        if ( jQuery( this ).hasClass( 'disabled' ) ) {
            return;
        }

        var placeholder = jQuery( this ).data( 'placeholder' );
        currentInstance.dialogs.placeholders.dialog( 'close' );
        Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', placeholder );
    });

};



/**
 * Make manual options list sortable
 *
 * @since 2.2
 */
Toolset.CRED.EditorToolbarPrototype.prototype.doManualOptionsSortable = function( $parentContainer ) {
    var currentInstance = this;
    jQuery( '.js-cred-editor-generic-fields-options-manual-table tbody', $parentContainer )
        .addClass( 'js-cred-editor-generic-fields-options-manual-table-body-loaded' )
        .sortable({
            stop: function( event, ui ) {
                currentInstance.adjustGenericFieldOptionManualValue( ui.item.parents( '.cred-editor-scaffold-item-container' ).first() );
            }
        });
}


/**
 * Add events to this prototype.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.initEvents = function() {

    this.initScaffoldItemEvents();
    this.initFieldsButtonEvent();
    this.initFieldsItemEvents();
    this.initGenericFieldsButtonEvents();
    this.initGenericFieldsItemEvents();
    this.initConditionalGroupsEvents();
    this.initNotificationPlaceholdersEvents();

    return this;
};

/**
 * Initialize this prototype.
 *
 * @since 2.1
 */
Toolset.CRED.EditorToolbarPrototype.prototype.init = function() {
	this.initI18n()
		.initCache()
        .initStatic()
        .initHooks()
        .initTemplates()
        .initDialogs()
        .initEvents();
};
