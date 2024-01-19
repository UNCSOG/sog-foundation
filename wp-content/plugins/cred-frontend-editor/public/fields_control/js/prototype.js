/**
 * Manage the fields control.
 *
 * @since 2.1
 * @package CRED
 */

var Toolset = Toolset || {};

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.FieldsControlPrototype = function( $ ) {

    /**
     * Localization skeleton, placeholders for missing pieces.
     *
     * @since 2.1
     */
	this.i18n = {
        action: {
            loading: '',
            saving: '',
            insert: '',
            cancel: '',
            back: ''
        },
        data: {
            ajaxurl: '',
            delete: {}
        },
        labels: {
            notSet: '',
            fieldOptions: ''
        },
        fields: {},
        parameters: {},
        attributes: {}
    };

    this.templates = {};

	this.dialogs = {};

};

/**
 * Get the domain for this fields control.
 *
 * Should be overriden by implementation objects.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.getDomain = function() {
    alert( 'You Toolset.CRED.FieldsControlPrototype instance should implementation the getDomain method' );
};

/**
 * Get the post type set for the current set of fields.
 *
 * Should be overriden by implementation objects.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.getPostType = function() {
    alert( 'You Toolset.CRED.FieldsControlPrototype instance should implementation the getPostType method' );
};

/**
 * Init localization.
 *
 * Should be overriden by implementation objects.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.initI18n = function() {
    alert( 'You Toolset.CRED.FieldsControlPrototype instance should implementation the initI18n method' );
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
Toolset.CRED.FieldsControlPrototype.prototype.initHooks = function() {
    Toolset.hooks.addAction( 'cred-action-fields-control-add-or-edit-dialog-loaded', this.initFieldsControlDialog, 10, this );
    Toolset.hooks.addAction( 'cred-action-fields-control-type-changed', this.initFieldsOptions, 10, this );

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
Toolset.CRED.FieldsControlPrototype.prototype.initTemplates = function() {
    this.templates.addEditDialog = wp.template( 'cred-fields-control-add-or-edit-dialog' );
    this.templates.optionsManualTable = wp.template( 'cred-editor-generic-fields-options-manual-table' );
    this.templates.optionsManualRow = wp.template( 'cred-editor-generic-fields-options-manual-row' );
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
Toolset.CRED.FieldsControlPrototype.prototype.initDialogs = function() {
    var currentInstance = this;

    if ( ! jQuery( '#js-cred-fields-control-add-or-edit-dialog-container' ).length ) {
		jQuery( 'body' ).append( '<div id="js-cred-fields-control-add-or-edit-dialog-container" class="toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-dialog-container js-cred-fields-control-add-or-edit-dialog-container"></div>' );
	}
	this.dialogs.addOrEdit = jQuery( '#js-cred-fields-control-add-or-edit-dialog-container' ).dialog({
		dialogClass: 'toolset-ui-dialog toolset-ui-dialog-responsive',
		autoOpen:	false,
		modal:		true,
		width:		'90%',
		title:		'',
		resizable:	false,
		draggable:	false,
		show: {
			effect:		"blind",
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
				class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-cred-fields-control-add-or-edit-action',
				text: currentInstance.i18n.action.save,
				click: function() {
					currentInstance.saveField();
				}
			},
			{
				class: 'button-secondary js-cred-fields-control-add-or-edit-close',
				text: currentInstance.i18n.action.cancel,
				click: function() {
					jQuery( this ).dialog( "close" );
				}
			}
		]
	});

    return currentInstance;
}

/**
 * Reposition the dialogs based on the current window size.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.repositionDialog = function() {
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
 * Init static variables.
 *
 * Might be overriden by implementation objects, which should invoque
 * this.constructor.prototype.initStatic.call( this );
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.initStatic = function() {
    this.shortcodeDialogSpinnerLoading = jQuery(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<div class="ajax-loader"></div>' +
		'<p>' + this.i18n.action.loading + '</p>' +
		'</div>' +
		'</div>'
    );

    this.shortcodeDialogSpinnerSaving = jQuery(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<p>' + this.i18n.action.saving + '<span class="spinner is-active"></span></p>' +
		'</div>' +
		'</div>'
    );

    this.tableRowSpinnerLoading = jQuery( '<span class="js-cred-field-table-row-spinner spinner is-active"></span>' );

    return this;
};

/**
 * Add events to this prototype.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.initEvents = function() {
    var currentInstance = this;

    jQuery( document ).on( 'click', '.js-cred-field-actions-set, .js-cred-field-actions-edit', function( e ) {
        e.preventDefault();
        var $button = jQuery( this ),
            $fieldRow = $button.closest( 'tr.js-cred-actions-row' ),
            metaKey = $fieldRow.data( 'field' ),
            metaData = $fieldRow.data( 'data' ),
            templateData = {
                fieldTypes: _.omit( currentInstance.i18n.fields, 'multiselect' ),
                selectedType: ''
            };

        currentInstance.dialogs.addOrEdit.dialog( 'open' ).dialog({
            title: metaKey
        });

        currentInstance.dialogs.addOrEdit.dialog( "option", "fieldMetaKey", metaKey );
        currentInstance.dialogs.addOrEdit.dialog( "option", "fieldMetaData", metaData );

        currentInstance.dialogs.addOrEdit.html( currentInstance.templates.addEditDialog( templateData ) );

        Toolset.hooks.doAction( 'cred-action-fields-control-add-or-edit-dialog-loaded', metaKey, metaData );
    });

    jQuery( document ).on( 'change', '.js-cred-fields-control-add-or-edit-type', function() {
        jQuery( this ).removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );

        var fieldType = jQuery( this ).val(),
            $fieldOptionsContainer = jQuery( '.js-cred-fields-control-add-or-edit-options' );

        if ( _.has( currentInstance.i18n.fields, fieldType ) ) {
            var templateData = jQuery.extend( true, {},
                {
                    shortcode: currentInstance.i18n.fields[ fieldType ].shortcode,
                    templates: currentInstance.templates.shortcodeGui,
                    parameters: currentInstance.i18n.parameters,
                    attributes: { attributesGroup: {
                        fields: currentInstance.filterValidFieldsAttributes( currentInstance.i18n.fields[ fieldType ].options )
                    } }
                }
            );
            $fieldOptionsContainer.html( templateData.templates.dialog( templateData ) );

            Toolset.hooks.doAction( 'cred-action-fields-control-type-changed', fieldType );
        }
    });

    jQuery( document ).on( 'click', '.js-cred-field-actions-remove', function( e ) {
        e.preventDefault();
        var $button = jQuery( this ),
            $fieldRow = $button.closest( 'tr.js-cred-actions-row' ),
            data = {
                action: currentInstance.i18n.data.delete.action,
                wpnonce: currentInstance.i18n.data.delete.nonce,
                domain: currentInstance.getDomain(),
                postType: currentInstance.getPostType(),
                metaKey: $fieldRow.data( 'field' )
            };

        $fieldRow.find( '.js-cred-field-actions' )
            .prop( 'disabled', true )
            .addClass( 'disabled' );

        $button.after( currentInstance.tableRowSpinnerLoading );

        jQuery.ajax({
            url:      currentInstance.i18n.data.ajaxurl,
            data:     data,
            dataType: 'json',
            type:     "POST",
            success:  function( originalResponse ) {
                var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
                if ( response.success ) {
                    $fieldRow.find('.cred-field-type').text( currentInstance.i18n.labels.notSet );
                    $fieldRow.find('.js-cred-field-actions-edit').hide();
                    $fieldRow.find('.js-cred-field-actions-set').show();
                    $fieldRow.data( 'data', '[]' );
                    $button.hide();
                } else {

                }
            },
            error: function( ajaxContext ) {

            },
            complete: function( ajaxContext ) {
                $fieldRow.find( '.js-cred-field-actions' )
                    .prop( 'disabled', false )
                    .removeClass( 'disabled' );
                $fieldRow.find( '.js-cred-field-table-row-spinner' ).remove();
            }
        });
    });

    jQuery( document ).on( 'click', '.js-cred-editor-generic-fields-options-manual-row-add', function( e ) {
        e.preventDefault();
        var fieldType = {
            type: jQuery( this ).data( 'type' )
        };
        currentInstance.addGenericFieldOptionManualRow( fieldType );
    });

    jQuery( document ).on( 'click', '.js-cred-editor-generic-fields-options-manual-row-remove', function( e ) {
        e.preventDefault();
        var row = jQuery( this ).closest( 'tr' );
        row.addClass( 'cred-editor-editable-list-item-deleted' );
		row.fadeOut( 400, function() {
            row.remove();
		});
    });

    return currentInstance;
};

/**
 * Remove unwanted attributes from the fields GUI.
 * Add the attribute to include in the scaffold or not.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.filterValidFieldsAttributes = function( attributes ) {
    var filteredAttributes = _.omit( attributes, [ 'class', 'sourceGroup', 'shortcode', 'label', 'class', 'generic_type' ] );
    _.each( this.i18n.attributes, function( attributeData, attributeKey, attributeList ) {
        filteredAttributes[ attributeKey ] = attributeData;
    });
    return filteredAttributes;
};

Toolset.CRED.FieldsControlPrototype.prototype.initFieldsControlDialog = function( metaKey, metaData ) {
    var fieldType = ( _.has( metaData, 'type' ) ) ? metaData.type : '';

    jQuery( '.js-cred-fields-control-add-or-edit-type' )
        .val( fieldType )
        .trigger( 'change' );
};

/**
 * Init the fields source options, for those field types that have it.
 *
 * @param {string} fieldType
 * @param {object} templateData
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.initFieldsOptions = function( fieldType ) {
    var currentInstance = this;

    // Get field data from the dialog
    var metaKey = currentInstance.dialogs.addOrEdit.dialog( "option", "fieldMetaKey" ),
        metaData = currentInstance.dialogs.addOrEdit.dialog( "option", "fieldMetaData" );

    // Set the field slug
    jQuery( '#cred_generic_field-field' )
        .val( metaKey )
        .prop( 'readonly', true );

    // Set the field default, if any
    if ( _.has( metaData, 'default' ) ) {
        jQuery( 'input#cred_generic_field-default' )
            .val( metaData.default )
            .trigger( 'change' );
    }

    // Set the required attribute, if any
    if (
        _.has( metaData, 'data' )
        && _.has( metaData.data, 'validate' )
        && _.has( metaData.data.validate, 'required' )
        && _.has( metaData.data.validate.required, 'active' )
        && metaData.data.validate.required.active
     ) {
        jQuery( 'input[name="cred_generic_field-required"][value="yes"]' )
            .prop( 'checked', true )
            .trigger( 'change' );
    }

    // Set the validate attribute, if any
    if (
        _.has( metaData, 'data' )
        && _.has( metaData.data, 'validate_format' )
        && metaData.data['validate_format']
    ) {
        jQuery( 'input[name="cred_generic_field-validate_format"][value="yes"]' )
            .prop( 'checked', true )
            .trigger( 'change' );
    }

    // Set the scaffold-include attribute, if any
    if ( _.has( metaData, '_cred_ignore' ) && metaData['_cred_ignore'] ) {
        jQuery( 'input[name="cred_generic_field-include_scaffold"][value="no"]' )
            .prop( 'checked', true )
            .trigger( 'change' );
    }

    // Further attributes are just supported by fields with options
    if ( ! _.contains( [ 'checkboxes', 'radio', 'select'  ], fieldType ) ) {
        return;
    }

    var $optionsManualTable = this.templates.optionsManualTable( { type: fieldType } );
    jQuery( '.js-toolset-shortcode-gui-attribute-wrapper-for-manual' ).append( $optionsManualTable );
    jQuery( '.js-cred-editor-generic-fields-options-manual-table tbody' )
        .addClass( 'js-cred-editor-generic-fields-options-manual-table-body-loaded' )
        .sortable({
            handle: ".js-cred-editor-generic-fields-options-manual-row-move-handle",
            axis: 'y',
            containment: ".js-cred-editor-generic-fields-options-manual-table",
            items: "> tr",
            helper: 'clone',
            tolerance: "pointer"
        });

    // Populate options, if any
    if (
        _.has( metaData, 'data' )
        && _.has( metaData.data, 'options' )
    ) {
        // Select and radio hold the default value in the same object as individual options,
        // while checkboxes sets a checked entry on every checked option.
        // Why oh why I do not want to understand, maybe comes from Types,
        // but this effectively vetoes options with a 'default' string as key.
        var options = _.omit( metaData.data.options, 'default' ),
            defaultOption = _.has( metaData.data.options, 'default' ) ? metaData.data.options.default : '',
            size = _.size( options ),
            $tableBody = jQuery( 'table.js-cred-editor-generic-fields-options-manual-table tbody' );

        if ( size > 0 ) {
            _.each( options, function( optionData, optionKey, optionList ) {
                currentInstance.addGenericFieldOptionManualRow( { type: fieldType } );
                var $tableRow = $tableBody.find( 'tr' ).last();
                $tableRow.find( '.js-cred-editor-generic-fields-options-manual-row-label input' ).val( optionData.title );
                $tableRow.find( '.js-cred-editor-generic-fields-options-manual-row-value input' ).val( optionKey );
                if ( 'checkboxes' == fieldType ) {
                    if ( _.has( optionData, 'checked' ) && optionData.checked ) {
                        $tableRow.find( '.js-cred-editor-generic-fields-options-manual-row-default input' ).prop( 'checked', true );
                    }
                } else {
                    if ( defaultOption == optionKey ) {
                        $tableRow.find( '.js-cred-editor-generic-fields-options-manual-row-default input' ).prop( 'checked', true );
                    }
                }
            } );
        } else {
            this.addGenericFieldOptionManualRow( { type: fieldType } );
        }
    } else {
        this.addGenericFieldOptionManualRow( { type: fieldType } );
    }

    jQuery( '#cred_generic_field-manual' )
        .before( '<h3>' + currentInstance.i18n.labels.fieldOptions + '</h3>' )
        .hide();
};

/**
 * Add a new row to the genric field options.
 *
 * @param {string} fieldType
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.addGenericFieldOptionManualRow = function( fieldType ) {
    var $optionsManualRow = this.templates.optionsManualRow( fieldType );
    jQuery( '.js-cred-editor-generic-fields-options-manual-table tbody' ).append( $optionsManualRow );
    jQuery( '.js-cred-editor-generic-fields-options-manual-table tbody' ).sortable( 'refresh' );
};

/**
 * Compose the options object for field types supporting it.
 *
 * @since 2.1
 * @todo Compose the actual object for the saving mechanism.
 */
Toolset.CRED.FieldsControlPrototype.prototype.craftGenericFieldOptions = function() {
    var $table = jQuery( '#js-cred-fields-control-add-or-edit-dialog-container .js-cred-editor-generic-fields-options-manual-table' ),
        options = {
            value: [],
            label: [],
            option_default: []
        },
        rowLabel = '',
        rowValue = '';

    if ( $table.length == 0 ) {
        return options;
    }

    jQuery( '.js-cred-editor-generic-fields-options-manual-table tbody tr' ).each( function() {
        var $row = jQuery( this );
        rowLabel = $row.find( '.js-cred-editor-generic-fields-options-manual-row-label input' ).val();
        rowValue = $row.find( '.js-cred-editor-generic-fields-options-manual-row-value input' ).val();
        if ( rowLabel == '' && rowValue == '' ) {
            return true;
        }
        options.label.push( rowLabel );
        options.value.push( rowValue );
        if ( $row.find( '.js-cred-editor-generic-fields-options-manual-row-default input' ).prop( 'checked' ) ) {
            options.option_default.push( rowValue );
        }
    });

    return options;
};

Toolset.CRED.FieldsControlPrototype.prototype.saveField = function() {
    var currentInstance = this;

    var $dialogContainer = jQuery( '#js-cred-fields-control-add-or-edit-dialog-container' ),
        metaType = $dialogContainer.find( '.js-cred-fields-control-add-or-edit-type' ).val();

    // jQuery will return null for a select with a disabled selected option
    if ( '' == metaType || null == metaType ) {
        $dialogContainer.find( '.js-cred-fields-control-add-or-edit-type' )
            .addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
        return;
    }

    var $saveButton = jQuery( '.js-cred-fields-control-add-or-edit-action' ),
        $buttonSet = $saveButton.closest( '.ui-dialog-buttonset' ),
        metaKey = $dialogContainer.find( '#cred_generic_field-field' ).val(),
        $fieldRow = jQuery( 'tr.js-cred-actions-row[data-field="' + metaKey + '"]' ),
        data = {
            action: currentInstance.i18n.data.add.action,
            wpnonce: currentInstance.i18n.data.add.nonce,
            domain: currentInstance.getDomain(),
            postType: currentInstance.getPostType(),
            metaKey: metaKey,
            type: metaType,
            default: $dialogContainer.find( '#cred_generic_field-default' ).val(),
            required: $dialogContainer.find( 'input[name="cred_generic_field-required"][value="yes"]' ).prop( 'checked' ),
            validateFormat: $dialogContainer.find( 'input[name="cred_generic_field-validate_format"][value="yes"]' ).prop( 'checked' ),
            includeInScaffold: $dialogContainer.find( 'input[name="cred_generic_field-include_scaffold"][value="yes"]' ).prop( 'checked' ),
            options: currentInstance.craftGenericFieldOptions()
        };

    $buttonSet.find( 'button' ).prop( 'disabled', true );

    currentInstance.dialogs.addOrEdit.html( currentInstance.shortcodeDialogSpinnerSaving );

    jQuery.ajax({
        url:      currentInstance.i18n.data.ajaxurl,
        data:     data,
        dataType: 'json',
        type:     "POST",
        success:  function( originalResponse ) {
            var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
            if ( response.success ) {
                var fieldType = _.has( currentInstance.i18n.fields, metaType ) ? currentInstance.i18n.fields[ metaType ].label : currentInstance.i18n.labels.unknown;
                $fieldRow.find('.cred-field-type').text( fieldType );
                $fieldRow.find('.js-cred-field-actions-edit').show();
                $fieldRow.find('.js-cred-field-actions-remove').show();
                $fieldRow.find('.js-cred-field-actions-set').hide();
                $fieldRow.data( 'data', response.data.fieldData );

                currentInstance.dialogs.addOrEdit.dialog( 'close' );
            } else {

            }
        },
        error: function( ajaxContext ) {

        },
        complete: function( ajaxContext ) {
            $buttonSet.find( 'button' ).prop( 'disabled', false );
        }
    });
};

/**
 * Remove unwanted inputs added to the form by WordPress itself.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.cleanForm = function() {
    var currentInstance = this;
    jQuery( 'form#custom_fields > input[name="_wpnonce"]' ).remove();
    jQuery( 'form#custom_fields > input[name="_wp_http_referer"]' ).remove();
    return currentInstance;
};

/**
 * Initialize this prototype.
 *
 * @since 2.1
 */
Toolset.CRED.FieldsControlPrototype.prototype.init = function() {
    this.initI18n()
        .initStatic()
        .initHooks()
        .initTemplates()
        .initDialogs()
        .initEvents()
        .cleanForm();
};
