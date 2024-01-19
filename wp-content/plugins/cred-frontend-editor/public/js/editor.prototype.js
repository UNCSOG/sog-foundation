/**
 * Manage the form editor page.
 *
 * @since 2.1
 * @package CRED
 */

var Toolset = Toolset || {};
var WPV_Toolset = WPV_Toolset || {};

Toolset.CRED = Toolset.CRED || {};

if ( typeof WPV_Toolset.CodeMirror_instance === "undefined" ) {
    WPV_Toolset.CodeMirror_instance = {};
}

Toolset.CRED.EditorPagePrototype = function( $ ) {

    this.editorSelector = 'content';
    this.editorMode = 'myshortcodes';
    this.editorInstance = {};

    this.prototype_i18n = window.cred_editor_prototype_i18n;

    this.editorJsSelector = 'cred-extra-js-editor';
    this.editorCssSelector = 'cred-extra-css-editor';
    this.editorActionMessageSelector = 'credformactionmessage';
    this.editorExtra = {
        js: {},
        css: {},
        actionMessage: {}
    };
    this.i18n = cred_post_form_content_editor_i18n;
    this.grid_enabled = this.i18n.panels.scaffold.options.grid_enabled;
    this.bootstrap_version = this.i18n.panels.scaffold.bootstrap_version;
    this.scaffold_field_id = this.i18n.data.scaffold.scaffold_field_id;

    this.templates = {};
    this.templates.scaffold = {};

    this.scaffold = new Toolset.CRED.ScaffoldEditor( this.i18n.data.shortcodes.form_container );

};

/**
 * Init the main editor:
 * - Make sure CodeMirror is fully available
 * - Define the Codemirror mode.
 * - Init the Codemirror editors.
 * - Add Quicktags.
 * - Add Bootstrap Grid buttons.
 * - Add hooks.
 * - Add events.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initIclEditor = function() {
	// Make sure that CodeMirror is fully available.
	// This is required because third party plugins
	// might load the WP bundled CodeMirror, and try to initialize it.
	// This bundled version packs a standalone runtime mode, which basically
	// overrides the global CodeMirror with a very limited set of properties and methods:
	// we need to make sure we get the full global CodeMirror object.
	if (
		_.has( window, 'CodeMirror')
		&& ! _.has( CodeMirror, 'fromTextArea' )
		&& _.has( window, 'wp' )
		&& _.has( wp, 'CodeMirror' )
	) {
		window.CodeMirror = jQuery.extend( true, window.CodeMirror, window.wp.CodeMirror );
	}
    CodeMirror.defineMode( this.editorMode, codemirror_shortcodes_overlay );

    this.initTemplates();
    this.initMainEditor();
    this.initExtraEditors();

};

/**
 * Init the fixed top bar for title plus save button.
 *
 * @since 2.3
 */
Toolset.CRED.EditorPagePrototype.prototype.initTopBar = function() {
    if ( jQuery( 'body' ).hasClass( 'cred-top-bar' ) ) {
        return;
    }

    jQuery( 'body' ).addClass( 'cred-top-bar' );

    jQuery( 'div#topbardiv > h2.hndle' ).remove();
    jQuery( 'div#topbardiv > button.handlediv' ).remove();
    jQuery( 'div#titlediv > div.inside' ).remove();
    jQuery( 'a.page-title-action' ).remove();

    // When exiting the wizard, we force a display:block here with .show()
    jQuery( 'div#titlediv' ).css( {'display': 'flex' } );

    jQuery( 'div#titlediv' ).prependTo( 'div#topbardiv > .inside' );

    jQuery( 'div#post-body-content' ).remove();

    jQuery( 'h1.wp-heading-inline' ).prependTo( 'div#titlediv' );

    jQuery( '#js-cred-save-form' ).appendTo( 'div#titlewrap' );

    // When loading a form without title (ie, when existing the wizard without setting a title)
    // hide the delete button: you cannot delete a fom that does not exist yet.
    if ( '' == jQuery( '#title' ).val() ) {
        jQuery( '.js-cred-delete-form' ).remove();
    } else {
        jQuery( '#title' ).hide();
        jQuery( '<span id="title-alt">' + jQuery( '#title' ).val() + '<i class="fa fa-pencil"></i></span>' ).prependTo( 'div#titlewrap' );
    }

    jQuery( 'div#topbardiv > *').show();
    jQuery( 'div#save-form-actions' ).show();

    var adminBarWidth = jQuery( 'div#wpbody-content > div.wrap' ).width(),
        adminBarHeight = jQuery( 'div#topbardiv' ).height(),
        adminBarTopOffset = 0,
        adjustControls = function() {
            if ( jQuery( window ).scrollTop() > 5 ) {
                jQuery( '#save-form-actions, .js-cred-delete-form', 'div#topbardiv' ).fadeOut( 'fast', function() {
                    jQuery( 'body' ).addClass( 'cred-top-bar-scroll' );
                });
            }
            else {
                jQuery( 'body' ).removeClass( 'cred-top-bar-scroll' );
                jQuery( '#save-form-actions, .js-cred-delete-form', 'div#topbardiv' ).fadeIn( 'fast', function() {

                });
            }
        };

    if (
        jQuery( '#wpadminbar' ).length !== 0
        // Do not add the top offset when on an iframe
        && window.location == window.parent.location
    ) {
        adminBarTopOffset = jQuery('#wpadminbar').height();
    }

    jQuery( 'div#topbardiv' ).css({
        'top':adminBarTopOffset,
        'width':adminBarWidth
    });

    jQuery( 'div#wpbody-content' ).css({
        'padding-top': adminBarHeight
    });

    jQuery( window ).on( 'scroll', adjustControls );

    jQuery( window ).on( 'resize', function() {
        var adminBarWidth = jQuery( 'div#wpbody-content > div.wrap' ).width(),
            adminBarHeight = jQuery( 'div#topbardiv' ).height();
        jQuery( 'div#topbardiv' ).width( adminBarWidth );
        jQuery( 'div#wpbody-content' ).css({
            'padding-top':( adminBarHeight )
        });
    });

    jQuery( document ).on( 'click', '#title-alt', function( e ) {
        e.preventDefault();
        jQuery( this ).hide();
        jQuery( '#title' ).show();
    });

    adjustControls();
};

/**
 * Init a Codemirror editor on demand
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initCodemirror = function( editorId, editorSettings ) {
    var defaultSettings = {
        editorMode: this.editorMode,
        addQuicktags: true,
        addBootstrap: true
    };

    editorSettings = _.extend( defaultSettings, editorSettings );

    WPV_Toolset.CodeMirror_instance[ editorId ] = icl_editor.codemirror(
        editorId,
        true,
        editorSettings.editorMode
    );

    if ( editorSettings.addQuicktags ) {
        var editorQt = quicktags( { id: editorId, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
        WPV_Toolset.add_qt_editor_buttons( editorQt, WPV_Toolset.CodeMirror_instance[ editorId ] );
    }

    if ( editorSettings.addBootstrap ) {
        _.defer( function() {
            Toolset.hooks.doAction( 'toolset_text_editor_CodeMirror_init', editorId );
        });
    }
};

/**
 * Destroy a Codemirror editor on demand.
 *
 * @param {string} editorId
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.destroyCodemirror = function( editorId ) {
    WPV_Toolset.CodeMirror_instance[ editorId ] = null;
    window.iclCodemirror[ editorId ] = null;
};

/**
 * Refresh a Codemirror editor on demand.
 *
 * @param {string} editorId
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.refreshCodemirror = function( editorId ) {
    try {
        WPV_Toolset.CodeMirror_instance[ editorId ].refresh();
    } catch( e ) {
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

/**
 * Get the content of a Codemirror editor on demand.
 *
 * @param {string} editorId
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.getCodemirrorContent = function( editorId ) {
    var content = '';
    try {
        content = WPV_Toolset.CodeMirror_instance[ editorId ].getValue();
    } catch( e ) {
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
    return content;
};

/**
 * Callback for the Toolset.hooks filter
 * to get the content of a Codemirror editor on demand.
 *
 * @param {string} content
 * @param {string} editorId
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.filterGetCodemirrorContent = function( content, editorId ) {
    return this.getCodemirrorContent( editorId );
};

/**
 * Init the main Codemirror editor plus its JS/CSS extra editors.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initMainEditor = function() {

    var currentInstance = this;
    // Init main editor, with Quicktags and Bootstrap
    this.initCodemirror( this.editorSelector );
    this.editorInstance = WPV_Toolset.CodeMirror_instance[ this.editorSelector ];
    // Consider the initial editor content as always dirty
	// so when switching from it to the scaffold editor
	// a warning is displayed, but only if the HTML editor
	// is the initial mode.
	currentInstance.initEditorContent = '';

    // Init JS editor
    this.initCodemirror( this.editorJsSelector, { editorMode: 'javascript', addQuicktags: false, addBootstrap: false } );
    this.editorExtra.js = WPV_Toolset.CodeMirror_instance[ this.editorJsSelector ];

    // Init CSS editor
    this.initCodemirror( this.editorCssSelector, { editorMode: 'css', addQuicktags: false, addBootstrap: false } );
    this.editorExtra.css = WPV_Toolset.CodeMirror_instance[ this.editorCssSelector ];

};

/**
 * Init other Codemirror editors in the page, like:
 * - the message to display after submitting the form.
 * - the notifications body editor.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.initExtraEditors = function() {

    var currentInstance = this;

    this.initCodemirror( this.editorActionMessageSelector );
    this.editorExtra.actionMessage = WPV_Toolset.CodeMirror_instance[ this.editorActionMessageSelector ];

    jQuery( '.js-cred-notification-body' ).each( function() {
        var $notificationBodyEditor = jQuery( this ),
            notificationBodyId = $notificationBodyEditor.attr( 'id' );

            currentInstance.initCodemirror( notificationBodyId );
    });
};

/**
 * Init API hooks for the main editor.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.addHooks = function() {
    var self = this;
    Toolset.hooks.addAction( 'cred_editor_refresh_content_editor', this.refreshContentEditor, 10, this );
    Toolset.hooks.addAction( 'cred_editor_focus_content_editor', this.focusContentEditor, 10, this );
    Toolset.hooks.addFilter( 'cred_editor_get_content_editor', this.getContentEditor, 10, this );

    Toolset.hooks.addAction( 'cred_editor_init_codemirror', this.initCodemirror, 10, this );
    Toolset.hooks.addAction( 'cred_editor_init_scaffold', this.initScaffoldEditor, 10, this );
    Toolset.hooks.addAction( 'cred_editor_destroy_codemirror', this.destroyCodemirror, 10, this );
    Toolset.hooks.addAction( 'cred_editor_refresh_codemirror', this.refreshCodemirror, 10, this );
    Toolset.hooks.addFilter( 'cred_editor_get_codemirror_content', this.filterGetCodemirrorContent, 10, this );
    Toolset.hooks.addFilter( 'cred_editor_is_grid_enabled', function() { return self.grid_enabled; }, 10, this );
    Toolset.hooks.addFilter( 'cred_editor_get_bootstrap_version', function() { return self.bootstrap_version; }, 10, this );

    Toolset.hooks.addAction( 'cred_editor_init_top_bar', this.initTopBar, 10, this );
    Toolset.hooks.addAction( 'cred_editor_wizard_finished', this.initTopBar, 10, this );
    Toolset.hooks.addAction( 'cred_editor_insert_scaffold', this.insertScaffold, 10, this );
    Toolset.hooks.addAction( 'cred_editor_scaffold_init_sorting', this.handleItemsSorting, 10, this );
    Toolset.hooks.addAction( 'cred_editor_scaffold_pre_submit_form', this.preSubmitFormActions, 10, this );

    Toolset.hooks.addFilter( 'cred_editor_scaffold_scaffold_field_id_attribute_name', this.getScaffoldFieldIdAttributeName, 10, this );

};

/**
 * Refresh the main editor.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.refreshContentEditor = function() {
    try{
        this.editorInstance.refresh();
        this.editorInstance.focus();
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

/**
 * Focus on the main editor.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.focusContentEditor = function() {
    try{
        this.editorInstance.focus();
    } catch( e ){
        console.log( 'There is a problem with CodeMirror instance: ', e.message );
    }
};

/**
 * Get the main editor.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.getContentEditor = function() {
    return this.editorInstance;
};

/**
 * Manage the flag for the extra editors emptyness.
 *
 * @param {string} editorSlug
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.nonEmptyEditorFlag = function( editorSlug ) {
    if ( '' == this.editorExtra[ editorSlug ].getValue() ) {
        jQuery( '.js-cred-editor-container-' + editorSlug + ' .js-editor-nonempty-flag' ).fadeOut();
    } else {
        jQuery( '.js-cred-editor-container-' + editorSlug + ' .js-editor-nonempty-flag' ).fadeIn();
    }
};

/**
 * Get the current form ID.
 * Should be overriden by implementation objects.
 *
 * @since 2.2.1.1
 */
Toolset.CRED.EditorPagePrototype.prototype.getFormId = function() {
    alert( 'Your Toolset.CRED.EditorPagePrototype instance should implementation the getFormId method' );
};

/**
 * Get the current form type, as its post type slug.
 * Should be overriden by implementation objects.
 *
 * @since 2.2.1.1
 */
Toolset.CRED.EditorPagePrototype.prototype.getFormType = function() {
    alert( 'Your Toolset.CRED.EditorPagePrototype instance should implementation the getFormType method' );
};

/**
 * Init main editor events.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.addEvents = function() {

    var currentInstance = this;

    jQuery( document ).on( 'click', '#js-cred-delete-form', function( e ) {
        e.preventDefault();
        var confirmation = window.confirm( currentInstance.prototype_i18n.delete.confirmation );
        if( confirmation ) {
            var ajaxData = {
                action: currentInstance.prototype_i18n.delete.ajax.action,
                wpnonce: currentInstance.prototype_i18n.delete.ajax.nonce,
                formId: currentInstance.getFormId()
            };

            jQuery.ajax({
                url:      currentInstance.prototype_i18n.ajaxurl,
                data:     ajaxData,
                dataType: 'json',
                type:     "POST",
                success:  function( originalResponse ) {
                    var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
                    if ( response.success ) {
                        var formType = currentInstance.getFormType();
                        if ( _.has( currentInstance.prototype_i18n.listing, formType ) ) {
                            window.location.href = currentInstance.prototype_i18n.listing[ formType ];
                        }
                    }
                },
                error: function ( ajaxContext ) {

                }
            });
        };
    })

    /**
     * Toggle foldable items, like the extra CSS/JS editors
     * or the drag&drop sidebar sections.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-editor-toggler', function() {
        var $toggler = jQuery( this ),
            target = jQuery( this ).data( 'target' );

        $toggler
            .find( '.fa.fa-angle-down, .fa.fa-angle-up' )
                .toggleClass( 'fa-angle-down fa-angle-up' );

        jQuery( '.js-cred-editor-wrap-' + target ).slideToggle( 'fast', function() {
            if ( !! currentInstance.editorExtra[ target ] ) {
                currentInstance.editorExtra[ target ].refresh();
                currentInstance.editorExtra[ target ].focus();
            }
        });
    });

    /**
     * Track changes in the JS and CSS editors.
     *
     * @since 2.1
     */
    this.editorExtra.js.on( 'change', function() {
        currentInstance.nonEmptyEditorFlag( 'js' );
    });
    this.nonEmptyEditorFlag( 'js' );

    this.editorExtra.css.on( 'change', function() {
        currentInstance.nonEmptyEditorFlag( 'css' );
    });
    this.nonEmptyEditorFlag( 'css' );

    /**
     * Toggle open a notification.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-notification-edit', function( e ) {
        e.preventDefault();

        var $button = jQuery( this ),
            editorIndex = $button.data( 'index' ),
            $editorRow = jQuery( '#cred_notification_settings_row-' + editorIndex ),
            $editorPanel = jQuery( "#cred_notification_settings_panel-" + editorIndex );

        $button.hide();
        $editorRow
            .addClass( 'cred-notification-settings-row-open' )
            .find( '.js-cred-notification-close' ).show();
        $editorPanel
            .fadeIn( 'fast', function() {
                var editorId = $editorPanel
                    .find( '.js-cred-notification-body' )
                        .attr( 'id' );
                Toolset.hooks.doAction( 'cred_editor_refresh_codemirror', editorId );
            });
    });

    /**
     * Toggle close a notification.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'click', '.js-cred-notification-close', function( e ) {
        e.preventDefault();

        var $button = jQuery( this ),
            editorIndex = $button.data( 'index' ),
            $editorRow = jQuery( '#cred_notification_settings_row-' + editorIndex ),
            $editorPanel = jQuery( "#cred_notification_settings_panel-" + editorIndex );

        $button.hide();
        $editorRow
            .removeClass( 'cred-notification-settings-row-open' )
            .find( '.js-cred-notification-edit' ).show();
        $editorPanel.fadeOut( 'fast', function() {});
    });

    /**
     * Refresh the editor for the message after submitting the form when opening it.
     *
     * @since 2.1
     */
    jQuery( document ).on( 'change', '#cred_form_success_action', function() {
        Toolset.hooks.doAction( 'cred_editor_refresh_codemirror', currentInstance.editorActionMessageSelector );
    });


    /**
     * Switch betweens Form editors: drag and drop or expert (HTML)
     *
     * @since 2.3
     */
    jQuery( document ).on( 'change', '#cred-editor-expert-mode-switcher', function() {
        jQuery( '.js-cred-editor-wrap-content > div' ).toggle();
        var isHTMLSelected = jQuery('#cred-editor-html').is(':visible');
        if ( isHTMLSelected ) {
            Toolset.hooks.doAction( 'cred_editor_insert_scaffold' );
            WPV_Toolset.CodeMirror_instance[ currentInstance.editorSelector ].refresh();
            jQuery( '.js-cred-editor-notice-switcher' ).removeClass( 'hidden' );
        } else {
            jQuery( '.js-cred-editor-notice-switcher' ).addClass( 'hidden' );
        }
		jQuery( '#cred-editor-origin' ).val( isHTMLSelected ? 'html' : 'scaffold' );
		if ( 'html' == jQuery( '#cred-editor-origin' ).val() ) {
			currentInstance.initEditorContent = currentInstance.editorInstance.getValue();
		}
    })


    /**
     * Switch betweens Form editors: drag and drop or expert (HTML)
     *
     * @since 2.3
     */
    jQuery( document ).on( 'click', '#cred-editor-expert-mode-switcher', function() {
        var $this = jQuery(this);
        // Close options so changes are 'saved'
        jQuery( '.js-cred-editor-scaffold-item-options:visible .js-cred-editor-scaffold-options-close' ).click();
        if (
			! $this.is(':checked')
			&& currentInstance.initEditorContent != currentInstance.editorInstance.getValue()
		) {
            var dialog = jQuery( currentInstance.templates.scaffold.switchToDD() ).dialog( {
                modal: true,
                classes: { 'ui-dialog': 'toolset-ui-dialog' },
                buttons: [
                    {
                        text: currentInstance.i18n.panels.scaffold.no,
                        class: 'button-secondary',
                        click: function() {
                            dialog.dialog('close');
                        }
                    },
                    {
                        text: currentInstance.i18n.panels.scaffold.yes,
                        class: 'button-primary',
                        click: function() {
                            $this.removeAttr( 'checked' ).trigger( 'change' );
                            // Generates scaffold data from the HTML
                            var scaffoldString = jQuery( '#cred-scaffold-data' ).val();
                            var scaffoldData = scaffoldString ? JSON.parse( scaffoldString ) : {"options": [], "fields": []};
                            scaffoldData.fields = currentInstance.scaffold.parseHTMLtoScaffold( WPV_Toolset.CodeMirror_instance[ currentInstance.editorSelector ].getValue() );
                            // Initialize the editor
                            Toolset.hooks.doAction( 'cred_editor_init_scaffold', Toolset.CRED.ScaffoldEditor.setInitialScaffoldItems.bind( null, scaffoldData ) );
                            dialog.dialog('close');
                        }
                    }
                ]
            });
            return false;
        }
    });

	/**
	 * Refresh the Codemirror HTML editor when opening the postbox containing it
	 *
	 * @param {Event}  event
	 * @param {jQuery} postbox
	 */
	jQuery( document ).on( 'postbox-toggled', function( event, postbox ) {
		if (
			jQuery( postbox ).is( '#credformcontentdiv' )
			&& ! jQuery( postbox ).hasClass( 'closed' )
		) {
			currentInstance.editorInstance.refresh();
		}
	} );


    /**
     * Media button handler
     */
    jQuery( document ).on( 'click', '.js-shortcode-gui-field-input-media-button', function() {
        var $button = jQuery(this);
        var $input = $button.prev();
        var $thumbnail = $button.parent().next().find( 'img' );
        var $thumbnailInput = $thumbnail.next();
        var custom_media = true;
        window.cred_send_to_editor = function( tag ) {};
        wp.media.editor.send.attachment = function( props, imageObject ) {
            switch ( imageObject.type ) {
                case 'image':
                    $input.val( wp.media.string.image(imageObject) );
                    break;
                case 'video':
                    $input.val( wp.media.string.video(props, imageObject) );
                    break;
                case 'audio':
                    $input.val( wp.media.string.audio(props, imageObject) );
                    break;
                default:
                    $input.val( wp.media.string.link(props, imageObject) );
            }
            $thumbnail.attr( 'src', !!imageObject.sizes ? imageObject.sizes.thumbnail.url : imageObject.icon ).removeClass( 'hidden' );
            $thumbnailInput.val( $thumbnail.attr( 'src' ) );
        }

        wp.media.editor.open(1);
        return false;
    });

	/**
	 * Saving scaffold data to JSON object
	 *
	 * @since 2.3
	 */
	jQuery( '#post' ).on( 'submit', function( event ) {
		const result = currentInstance.preSubmitFormActions( event );
		if ( result === false ) { // It might be undefined
			return false;
		}
	} );

};


/**
 * Init editor templates.
 *
 * @since 2.3
 */
Toolset.CRED.EditorPagePrototype.prototype.initTemplates = function() {
    this.templates.scaffold.content = wp.template( 'cred-editor-scaffold-content' );
    this.templates.scaffold.item = wp.template( 'cred-editor-scaffold-item' );
    this.templates.scaffold.itemOptions = wp.template( 'cred-editor-scaffold-itemOptions' );
    this.templates.scaffold.itemOptionsMedia = wp.template( 'cred-editor-scaffold-itemOptions-media' );
    this.templates.scaffold.itemOptionsHTMLContent = wp.template( 'cred-editor-scaffold-itemOptions-html-content' );
    this.templates.scaffold.needMoreData = wp.template( 'cred-editor-scaffold-need-more-data' );
    this.templates.scaffold.switchToDD = wp.template( 'cred-editor-scaffold-dialog-switch-to-dd' );
    this.templates.optionsManualTable = wp.template( 'cred-editor-generic-fields-options-manual-table' );
    this.templates.optionsManualRow = wp.template( 'cred-editor-generic-fields-options-manual-row' );
    this.templates.shortcodeGui = Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-templates', {} );
    this.templates.conditionalGroups = {};
    this.templates.conditionalGroups.container = wp.template( 'cred-editor-scaffold-itemOptions-conditionals' );
    this.templates.conditionalGroups.gui = wp.template( 'cred-editor-conditional-groups-dialog' );
    this.templates.byid = {};
    // Needed for getting item options by template
    this.templates.byid['cred-editor-scaffold-itemOptions-media'] = this.templates.scaffold.itemOptionsMedia;
    this.templates.byid['cred-editor-scaffold-itemOptions-conditionals'] = this.templates.conditionalGroups.container;
    this.templates.byid['cred-editor-scaffold-itemOptions-html-content'] = this.templates.scaffold.itemOptionsHTMLContent;
};

/**
 * Get the current form type: new or edit. Where available.
 *
 * @since 2.3
 */
Toolset.CRED.EditorPagePrototype.prototype.initScaffoldEditor = function() {
    var callbackData = arguments.length ? arguments[0] : false;
    var self = this;
    var hasData = Toolset.hooks.applyFilters( 'cred-filter-get-current-form-target', '' );
    var $switcher = jQuery('#cred-editor-expert-mode-switcher');

    if ( '' !== hasData ) {
        $switcher.removeAttr('disabled');
        Toolset.hooks.doAction( 'cred-action-maybe-request-and-operate-on-object-fields', function( _objectFields, objectKey ) {
            // Deep cloning. Object.assign can't be used because second level objects (example: fields.meta) are copied by reference
            var objectFields = JSON.parse( JSON.stringify( _objectFields ) );

            // Include all fields by default on newly created forms
            var basicFields = Object.assign( {}, objectFields );

            // Keep permanent fields in the sidebar too
            var meta = Object.assign( {}, objectFields.meta );
            objectFields = _.map( objectFields, function( value, key ) {
                if ( 'meta' === key ) {
                    return _.omit( meta, function( value, key, object ) {
                        return ( ! _.has( meta[ key ], 'permanent' ) || ! meta[ key ].permanent );
                    });
                }
                return {};
            });

            /**
             * Filter the elements by `blockedItem`. Depending on `negation` it returns the list with blockedItems or not
             *
             * @param {Object} object Object to be filtered
             * @param {boolean} negation boolean negation or not
             * @since 2.3
             */
            var filterByBlockedItem = function( object, negation ) {
                var res = Object.assign( {}, object );
                Object.keys( res ).forEach( function( key ) {
                    if ( (!negation) ^ !!res[ key ].blockedItem ) { // negation true = !element.blockedItem XNOR
                        delete res[ key ];
                    }
                } );
                return res;
            }

            objectFields.generic = self.i18n.panels.genericFields.fields;
            var extraFields = Object.assign( {}, objectFields, {
                extra: self.i18n.data.scaffold.fields.extra,
                generic: self.i18n.panels.genericFields.fields,
                blocked: filterByBlockedItem( self.i18n.data.scaffold.fields.formElements, false )
            } );

            var scaffoldTemplateData = jQuery.extend( true, {},
                basicFields,
                { templates:    self.templates },
                { dialog:       self.i18n.panels.scaffold },
                { formElements: filterByBlockedItem( self.i18n.data.scaffold.fields.formElements, true ) },
                { options: jQuery.extend( true, {}, self.i18n.panels.scaffold.options, self.i18n.data.scaffold.options ) },
                { environment: {
                    formType: jQuery( '[name="_cred[form][type]"]:checked' ).val()
                } },
                { extraFields: extraFields },
                { formType: jQuery('#post_type').val() },
                { scaffold_field_id: self.scaffold_field_id }
            );
            if ( scaffoldTemplateData.options.hasOwnProperty( 'grid_enabled' ) ) {
                delete scaffoldTemplateData.options.grid_enabled;
            }

            // Used for cheking is nested item is has been handled
            var isNestedItem = false;
            // Element that is dragging
            var nestedDraggingHelper = null;
            // Placeholder for dragging
            var nestedDraggingPlaceholder = null;
            /**
             * Handles placeholder for nested items, which are below conditionals or level-2 items
             *
             * @param {Event} event Returned by sortable event
             * @param {Object} ui Returned by sortable event
             */
            var handleNestedPlaceholders = function( event, ui ) {
                isNestedItem = ui.placeholder.prev().data( self.scaffold_field_id ) === 'conditionals'
                    || ui.placeholder.prev().hasClass( 'cred-scaffold-conditional-child' );
                ui.placeholder.toggleClass( 'cred-placeholder-level-2', isNestedItem );
                if ( isNestedItem ) {
                    nestedDraggingHelper = ui.helper;
                    nestedDraggingPlaceholder = ui.placeholder;
                } else {
                    nestedDraggingHelper = null;
                    nestedDraggingPlaceholder = null;
                }
            }

            jQuery( '#cred-editor-scaffold' ).html( self.templates.scaffold.content( scaffoldTemplateData ) );

            self.handleItemsSorting();

            Toolset.hooks.doAction( 'cred-action-toolbar-scaffold-content-loaded' );

            jQuery( '.js-cred-editor-scaffold-item-container' ).each( function() {
                var $itemContainer = jQuery( this );
                var attributes = $itemContainer.data( 'attributes' );
                var shortcode = $itemContainer.data( 'shortcode' );
                var options = $itemContainer.data( 'options' );
                var shortode = $itemContainer.data( 'shortode' );
                var templateData = jQuery.extend( true, {},
                    {
                        shortcode: shortcode,
                        templates: self.templates.shortcodeGui,
                        parameters: { type: attributes[ self.scaffold_field_id ] },
                        attributes: {
                            singleGroup: {
                                header: 'this.i18n.dialog.shortcode.group.header',
                                fields: options
                            }
                        }
                    }
                );
                Toolset.hooks.doAction( 'cred-action-toolbar-shortcode-dialog-loaded', shortcode, templateData, $itemContainer );
                if ( attributes[ self.scaffold_field_id ] === 'conditionals' ) {
                    Toolset.hooks.doAction( 'cred-action-init-conditional-groups' );
                    Toolset.hooks.doAction( 'cred-action-add-conditional-row', $itemContainer );
                }
            });

            if ( callbackData ) {
                callbackData( Object.assign( {}, _objectFields ) );
            }

            var $optionsContainer = jQuery( '.js-cred-editor-wrap-scaffold-options' );
            // Fake a change event on options checkboxes to initialize their values
            $optionsContainer.find( 'input:checkbox' ).each( function() {
                jQuery( this ).trigger( 'change' );
            });

            /**
             * Initialize tippy.js and make sure the tooltip really should be there (depends on context)
             */
            jQuery( '.js-cred-editor-tippy' ).each( function() {
                OTGSUI.otgsPopoverTooltip.initSingle( this );
                this._tippy.set( {
                    onShow: function( tip ) {
                        Toolset.CRED.ScaffoldEditor.maybeShowTooltip( tip );
                    }
                } );
            } );
		} );

		// Required generic fields need Default Values to be "required"
		jQuery( '#js-cred-editor-scaffold-item-list-container' ).on( 'change', 'input[type=radio][name*="-required"]', function( event ) {
			const $checkbox = jQuery( this );
			const attributes = jQuery( '.fa-caret-up' ).closest( '.js-cred-editor-scaffold-item-container' ).data( 'attributes' );
			if ( !! attributes && attributes.type === 'checkbox' ) {
				const $defaultValueElement = $checkbox.closest('form').find('input[id*="-default"]');
				if ( $checkbox.val() === 'yes' ) {
					$defaultValueElement.attr( 'required', 'required' );
				} else {
					$defaultValueElement.removeAttr( 'required' );
				}
			}
		} );
    } else {
        $switcher.attr('disabled', 'disabled');
        jQuery( '#cred-editor-scaffold' ).html( self.templates.scaffold.needMoreData(
            jQuery.extend( true, {}, self.i18n, { formType: jQuery('#post_type').val() } )
        ) );
    }

}


/**
 * Craft and insert the scaffold into the editor.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.insertScaffold = function() {
    var scaffold = this.scaffold.craftScaffoldOutput();
    WPV_Toolset.CodeMirror_instance[ this.editorSelector ].setValue( scaffold );
};


/**
 * Handles every thing regarding to dragging, dropping, orderirng and columns
 *
 * @since 2.3
 */
Toolset.CRED.EditorPagePrototype.prototype.handleItemsSorting = function() {
    var currentInstance = this;

    // jQuery.draggable#refreshPositions is a costly process, because it recalculates droppable zones sizes in every `mouseover` events
    // To avoid it, $.ui.intersect must be adapted to our needs.
    // @link https://github.com/jquery/jquery-ui/blob/master/ui/widgets/droppable.js#L261
    var intersectOriginal = jQuery.ui.intersect;
    jQuery.ui.intersect = function( draggable, droppable, toleranceMode, event ) {
        // I don't know why drop event is fired twice, so I use this flag
        if ( !! jQuery.ui.credStopsDragging ) {
            return false;
        }
        return document.elementsFromPoint( event.clientX, event.clientY )
            .filter( function( element ) {
                var maybeAllowConditional = draggable.element.data( currentInstance.scaffold_field_id ) !== 'conditionals'
                    || ( draggable.element.data( currentInstance.scaffold_field_id ) === 'conditionals' && !element.classList.contains('cred-editor-scaffold-dropping-zones-conditional') );

                return maybeAllowConditional && element === droppable.element[0];
            } )
            .length;
    }

    currentInstance.scaffold.addDraggableItems();
}


/**
 * Init the editor page.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.init = function() {
    this.initIclEditor();
    this.addHooks();
    this.addEvents();
    this.initScaffoldEditor();
};


/**
 * Init the editor page.
 *
 * @since 2.1
 */
Toolset.CRED.EditorPagePrototype.prototype.preSubmitFormActions = function( event ) {
	const requiredEmptyInputs = Array.from( document.querySelectorAll(`#js-cred-editor-scaffold-item-list-container input[required]`) ).filter( input => ! input.value );
	if ( requiredEmptyInputs.length ) {
		const requiredNotice = document.querySelector( '.js-cred-editor-notice-required' );
		requiredNotice.classList.remove( 'hidden' );
		event.preventDefault();
		event.stopPropagation();
		jQuery( 'html, body' ).animate( {
			scrollTop: jQuery( requiredNotice ).offset().top - 90,
		}, 500 );
		return false;
	} else {
		document.querySelector( '.js-cred-editor-notice-required' ).classList.add( 'hidden' );
	}
    // Close options so changes are 'saved'
    jQuery( '.js-cred-editor-scaffold-item-options:visible .js-cred-editor-scaffold-options-close' ).click();
    var $credOrigin = jQuery( '#cred-editor-origin' );
    if ( ! $credOrigin.val() ) {
        var isHTMLSelected = jQuery('#cred-editor-html').is(':visible');
        $credOrigin.val( isHTMLSelected ? 'html' : 'scaffold' );
    }
    if ( jQuery( '#cred-editor-origin' ).val() === 'scaffold' ) {
        this.insertScaffold();
        WPV_Toolset.CodeMirror_instance[ this.editorSelector ].save();
    }
    jQuery( '#cred-scaffold-data' ).val( JSON.stringify( this.scaffold.scaffoldToJSON() ) );
}


/**
 * Get the name of the common identifier attribute for all the fields
 *
 * @since 2.3
 */
Toolset.CRED.EditorPagePrototype.prototype.getScaffoldFieldIdAttributeName = function() {
    return self.scaffold_field_id;
};
