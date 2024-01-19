/**
 * shortcode.js
 *
 * Contains helper functions for the popup GUI used to set Access shortcode attributes
 *
 * @since 2.3.0
 * @package Toolset Access
 */

var OTGAccess = OTGAccess || {};

OTGAccess.ShortcodesGUI = function( $ ) {

    var self = this;

    /**
     * The current GUI API action to be performed. Can be 'insert', 'create', 'save', 'append', 'edit', 'skip'.
     *
     * Access to it using the API methods, from inside this object:
     * - self.getShortcodeGuiAction
     * - self.setShortcodeGuiAction
     *
     * Access to it using the API hooks, from the outside world:
     * - toolset-access-filter-get-shortcode-gui-action
     * - toolset-access-action-set-shortcode-gui-action
     *
     * @since 2.3.0
     */
    self.shortcode_gui_action = 'insert';
    self.validActions = [ 'insert', 'create', 'save', 'append', 'edit', 'skip' ];

    /**
     * Get the current shortcodes GUI action.
     *
     * @see toolset-access-filter-get-shortcode-gui-action
     *
     * @since 2.6
     */
    self.getShortcodeGuiAction = function( action ) {
        return self.shortcode_gui_action;
    };

    /**
     * Set the current shortcodes GUI action.
     *
     * @see toolset-access-action-set-shortcode-gui-action
     *
     * @since 2.6
     */
    self.setShortcodeGuiAction = function( action ) {
        if ( -1 !== $.inArray( action, self.validActions ) ) {
            self.shortcode_gui_action = action;
        }
    };

    /**
     * Store the shortcode to insert on the auxiliar dialog.
     *
     * @since 2.3.0
     */
    self.shortcode_to_insert_on_target_dialog	= '';

    /**
     * ----------------------
     * Dialogs defaults
     * ----------------------
     */
    self.shortcode_dialog						= null;
    self.textarea_target_dialog					= null;
    self.dialog_minWidth						= 870;

    /**
     * @since 2.3.0
     */
    self.calculate_dialog_maxWidth = function() {
        return ( $( window ).width() - 200 );
    };

    /**
     * @since 2.3.0
     */
    self.calculate_dialog_maxHeight = function() {
        return ( $( window ).height() - 100 );
    };

    /**
     * Init the admin bar button, if any.
     *
     * @since 2.3.0
     */
    self.init_admin_bar_button = function() {
        if ( $( '.js-otg-access-shortcode-generator-node a' ).length > 0 ) {
            $( '.js-otg-access-shortcode-generator-node a' ).addClass( 'js-otg-access-shortcode-gui-in-adminbar' );
        }
    };

    /**
     * Initi dialogs
     *
     * @since 2.3.0
     */
    self.init_dialogs = function() {

        self.shortcode_dialog = $( "#wpcf-access-shortcodes-dialog-tpl" ).dialog({
            autoOpen:	false,
            title:		otg_access_shortcodes_gui_texts.dialog_title,
            modal:		true,
            minWidth:	550,
            show:		{
                effect:		"blind",
                duration:	800
            },
            open:		function( event, ui ) {
                $( 'body' ).addClass( 'modal-open' );
                $( ".js-wpcf-access-list-roles" ).prop( "checked", false );
                $( ".js-wpcf-access-shortcode-operator" ).prop( 'checked', false );
                $( ".js-wpcf-access-shortcode-format" ).prop( 'checked', false );
                $( ".js-wpcf-access-conditional-message" ).val( '' );
                $( '.js-wpcf-access-craft-shortcode' )
                    .prop( 'disabled', true )
                    .addClass( 'button-secondary' )
                    .removeClass( 'button-primary' );
                self.manage_shortcode_dialog_button_labels();
            },
            close:		function (event, ui) {
                $( 'body' ).removeClass( 'modal-open' );
            },
            buttons: [
                {
                    class:	'button button-secondary toolset-shortcode-gui-dialog-button-close js-wpcf-access-discard-shortcode',
                    text:	otg_access_shortcodes_gui_texts.cancel,
                    click:	function () {
                        self.shortcode_gui_action = 'insert';
                        $( this ).dialog( "close" );
                    }
                },
                {
                    class:		'button button-secondary js-wpcf-access-craft-shortcode',
                    text:		otg_access_shortcodes_gui_texts.insert_shortcode,
                    click:		function () {
                        self.craft_shortcode();
                    },
                }
            ]
        });

        self.textarea_target_dialog = $( '#otg-access-shortcode-generator-target-dialog' ).dialog({
            autoOpen:	false,
            modal:		true,
            width:		self.dialog_minWidth,
            title:		otg_access_shortcodes_gui_texts.dialog_title_generated,
            resizable:	false,
            draggable:	false,
            show: {
                effect:		"blind",
                duration:	800
            },
            create: function( event, ui ) {
                $( event.target ).parent().css( 'position', 'fixed' );
            },
            buttons: [
                {
                    class: 'button-primary',
                    text: otg_access_shortcodes_gui_texts.close,
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                },
            ],
            open: function( event, ui ) {
                $('body').addClass( 'modal-open' );
                $( '#otg-access-shortcode-generator-target' )
                    .html( self.shortcode_to_insert_on_target_dialog )
                    .focus();
            },
            close: function( event, ui ) {
                $( 'body' ).removeClass( 'modal-open' );
                self.shortcode_gui_action = 'insert';
                self.shortcode_to_insert_on_target_dialog = '';
                $( this ).dialog( "close" );
            }
        });

	};

	self.setButtonText = function( $button, $text ) {
		if ( $button.find( '.ui-button-text' ).length > 0 ) {
			$( '.ui-button-text', $button ).html( $text );
		} else {
			$button.html( $text );
		}
	};

    /**
     * Adjusts the dialog button labels depending on self.shortcode_gui_action.
     *
     * @since 2.3.0
     */

    self.manage_shortcode_dialog_button_labels = function() {
        switch ( self.shortcode_gui_action ) {
            case 'create':
				self.setButtonText( $( '.js-wpcf-access-discard-shortcode' ), otg_access_shortcodes_gui_texts.cancel );
				self.setButtonText( $( '.js-wpcf-access-craft-shortcode' ), otg_access_shortcodes_gui_texts.create_shortcode );
                break;
            case 'insert':
            default:
				self.setButtonText( $( '.js-wpcf-access-discard-shortcode' ), otg_access_shortcodes_gui_texts.close );
				self.setButtonText( $( '.js-wpcf-access-craft-shortcode' ), otg_access_shortcodes_gui_texts.insert_shortcode );
                break;
        }
    };

    /**
     * Init Toolset.hooks callbacks.
     *
     * @since 2.3.0
     */
    self.init_hooks = function() {

        /**
         * Perform an action after crafting a shortcode, based on self.shortcode_gui_action.
         *
         * @since 2.3.0
         */
        Toolset.hooks.addAction( 'otg-access-action-perform-shortcode-gui-action', self.perform_shortcode_gui_action );


        /**
         * Set the action to perform after crafting a shortcode, based on self.shortcode_gui_action.
         *
         * @since 2.6
         */
        Toolset.hooks.addAction( 'toolset-access-action-do-shortcode-gui-action', self.perform_shortcode_gui_action );

        /**
         * Return the current shortcode GUI action: 'insert', 'create', 'save', 'append', 'edit', 'skip'.
         *
         * @since 2.6
         */
        Toolset.hooks.addFilter( 'toolset-access-filter-get-shortcode-gui-action', self.getShortcodeGuiAction );

        /**
         * Set the current shortcodes GUI action: 'insert', 'create', 'save', 'append', 'edit', 'skip'.
         *
         * @since 2.6
         */
        Toolset.hooks.addAction( 'toolset-access-action-set-shortcode-gui-action', self.setShortcodeGuiAction );

    };

    /**
     * Calback for the otg-access-action-perform-shortcode-gui-action action.
     *
     * @since 2.3.0
     */
    self.perform_shortcode_gui_action = function( shortcode ) {
        self.shortcode_dialog.dialog( 'close' );

        var action = self.shortcode_gui_action;

        /**
         * Custom action executed before performing the GUI action.
         *
         * @param string shortcode   The shortcode to action upon
         * @param string self.action The action to execute
         *
         * @since 2.6
         */
        Toolset.hooks.doAction( 'toolset-access-action-before-do-shortcode-gui-action', shortcode, action );

        switch ( action ) {
            case 'skip':
            case 'append':
            case 'edit':
            case 'save':
                /**
                 * Do the GUI skip|append|edit|save action, if there is a callback for that.
                 *
                 * @param shortcode string
                 *
                 * @since 2.6
                 */
                Toolset.hooks.doAction( 'toolset-access-action-do-shortcode-gui-action-' + action, shortcode );
                break;
            case 'create':
                self.shortcode_to_insert_on_target_dialog = shortcode;
                self.textarea_target_dialog.dialog( "open" ).dialog({
                    maxHeight:	self.calculate_dialog_maxHeight(),
                    maxWidth:	self.calculate_dialog_maxWidth(),
                    position:	{
                        my:			"center top+50",
                        at:			"center top",
                        of:			window,
                        collision:	"none"
                    }
                });
                break;
            case 'insert':
            default:
                window.icl_editor.insert( shortcode );
                break;
        }

        /**
         * Custom action executed after performing the GUI action.
         *
         * @param string shortcode   The shortcode to action upon
         * @param string self.action The action executed
         *
         * @since 2.6
         */
        Toolset.hooks.doAction( 'toolset-access-action-after-do-shortcode-gui-action', shortcode, action );

        self.shortcode_gui_action = 'insert';
    }

    /**
     * Craft the shortcode based on the dialog selected options.
     *
     * @since 2.3.0
     */
    self.craft_shortcode = function() {

        var shortcode = '[toolset_access role="';
        shortcode += $( '.js-wpcf-access-list-roles:checked' ).map( function () {
            return $(this).val();
        }).get().join(",") + '"';
        shortcode += ( $( 'input[name="wpcf-access-shortcode-operator"]:checked' ).length > 0 ) ? ' operator="' + $( 'input[name="wpcf-access-shortcode-operator"]:checked' ).val() + '"' : '';
        shortcode += ( $( '.js-wpcf-access-shortcode-format' ).prop('checked') === true ) ? ' raw="true"' : '';
        shortcode += ']' + $( '.js-wpcf-access-conditional-message' ).val() + '[/toolset_access]';

        Toolset.hooks.doAction( 'otg-access-action-perform-shortcode-gui-action', shortcode );

    };

    self.openAccessDialog = function() {
        self.shortcode_dialog.dialog( 'open' ).dialog({
            height:		self.calculate_dialog_maxHeight(),
            width:		self.calculate_dialog_maxWidth(),
            maxWidth:	self.calculate_dialog_maxWidth(),
            position: 	{
                my:			"center top+50",
                at:			"center top",
                of:			window,
                collision:	"none"
            }
        });
    };

    /**
     * ----------------------
     * Events
     * ----------------------
     */

    // Click on the Admin Bar entry for Access
    $( document ).on( 'click','.js-otg-access-shortcode-gui-in-adminbar', function( e ) {
        e.preventDefault();
        self.shortcode_gui_action = 'create';
        self.openAccessDialog();
        //self.manage_shortcode_dialog_button_labels();
        return false;
    });

    // Click on the Access editor toolbar button
    $( document ).on( 'click', '.js-wpcf-access-editor-button', function( e ) {
        e.preventDefault();
        window.wpcfActiveEditor = $( this ).data( 'editor' );
        self.shortcode_gui_action = 'insert';
        self.openAccessDialog();
        //self.manage_shortcode_dialog_button_labels();
        return false;
    });

    // Dialog management: enable craft shortocde button only when one or more roles selected
    $( document ).on( 'change', '.js-wpcf-access-list-roles', function () {
        $( '.js-wpcf-access-craft-shortcode' )
            .prop( 'disabled', true )
            .addClass( 'button-secondary' )
            .removeClass( 'button-primary' );
        if ( $( '.js-wpcf-access-list-roles:checked' ).length > 0 ) {
            $( '.js-wpcf-access-craft-shortcode' )
                .prop( 'disabled', false )
                .addClass( 'button-primary' )
                .removeClass( 'button-secondary' );
        }
    });

    //--------------------------------
    // Compatibility
    //--------------------------------

    /**
     * Handle the event that is triggered by Fusion Builder when creating the WP editor instance.
     *
     * The event was added as per our request because Fusion Builder does not load the WP editor using
     * the native PHP function "wp_editor". It creates the WP editor instance on JS, so no PHP actions
     * to add custom media buttons like ours are available. It generates the media button plus the toolbar that
     * contains it as javascript objects that it appends to its own template. It offers no way of adding our custom
     * buttons to it.
     *
     * @param event			The actual event.
     * @param editorId		The id of the editor that is being created.
     *
     * @since 2.5.2
     */
    $( document ).on( 'fusionButtons', function( event, editorId ) {
        self.addShortcodesButtonToDynamicEditor( editorId );
    });

    /**
     * Handle the event that is triggered by Toolset Types and Forms when creating a WP editor instance.
     *
     * The event is fired when a WYSIWYG field is dynamically initialized.
     *
     * @param event			The actual event.
     * @param editorId		The id of the editor that is being created.
     *
     * @since 2.5.2
     */
    $( document ).on( 'toolset:types:wysiwygFieldInited toolset:forms:wysiwygFieldInited', function( event, editorId ) {
        self.addShortcodesButtonToDynamicEditor( editorId );
    });

    /**
     * Add a shortcodes generator button dynamically to any native editor that contains a media toolbar, given its editor ID.
     *
     * @since 2.5.2
     */
    self.addShortcodesButtonToDynamicEditor = function( editor_id ) {
        var $mediaButtons = $( '#wp-' + editor_id + '-media-buttons' ),
            button = '<span'
                + ' class="button js-wpcf-access-editor-button"'
                + ' data-editor="' + editor_id + '">'
                + '<i class="icon-access-logo fa fa-wpv-custom ont-icon-18 ont-color-gray"></i>'
                + '<span class="button-label">' + otg_access_shortcodes_gui_texts.button_title + '</span>'
                + '</span>',
            $accessBbutton = $( button );

        if ( $mediaButtons.find( '.js-wpcf-access-editor-button' ).length == 0 ) {
            $accessBbutton.appendTo( $mediaButtons );
        }
    };

    self.init = function() {
        self.init_dialogs();
        self.init_hooks();
        self.init_admin_bar_button();
    };

    self.init();

};

jQuery( function( $ ) {
    OTGAccess.shortcodes_gui = new OTGAccess.ShortcodesGUI( $ );
});
