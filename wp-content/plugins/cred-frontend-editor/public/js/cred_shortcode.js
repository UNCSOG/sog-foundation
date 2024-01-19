/**
 * API and helper functions for the GUI on Toolset Forms shortcodes.
 *
 * @since 1.9.3
 * @package Toolset Forms
 */

var Toolset = Toolset || {};

if ( typeof Toolset.CRED === "undefined" ) {
	Toolset.CRED = {};
}

/**
 * -------------------------------------
 * Shortcode GUI
 * -------------------------------------
 */

Toolset.CRED.shortcodeManager = function( $ ) {

	var self = this;

	self.i18n = cred_shortcode_i18n;

	/**
	 * Shortcodes GUI API version.
	 *
	 * Access to it using the API methods, from inside this object:
	 * - self.getShortcodeGuiApiVersion
	 *
	 * Access to it using the API hooks, from the outside world:
	 * - cred-filter-get-shortcode-gui-api-version
	 *
	 * @since 1.9.3
	 */
	self.apiVersion = 193000;

	/**
	 * Get the current shortcodes GUI API version.
	 *
	 * @see cred-filter-get-shortcode-gui-api-version
	 *
	 * @since 1.9.3
	 */
	self.getShortcodeGuiApiVersion = function( version ) {
		return self.apiVersion;
	};

	/**
	 * Register the canonical Toolset hooks, both API filters and actions.
	 *
	 * @since 1.9.3
	 */
	self.initHooks = function() {

		/**
		 * ###############################
		 * API filters
		 * ###############################
		 */

		/**
		 * Return the current shortcodes GUI API version.
		 *
		 * @since 1.9.3
		 */
		Toolset.hooks.addFilter( 'cred-filter-get-shortcode-gui-api-version', self.getShortcodeGuiApiVersion );

		/**
		 * ###############################
		 * API actions
		 * ###############################
		 */

		/**
		 * Set the right dialog buttonpane buttons labels, after the dialog is opened, based on the current GUI action.
		 *
		 * @since 1.9.3
		 */
		Toolset.hooks.addAction( 'cred-action-shortcode-dialog-preloaded', self.manageShortcodeDialogButtonpane );

		/**
		 * Initialize the dialog tabs, after the shortcode dialog is opened.
		 *
		 * @since 1.9.3
		 */
		Toolset.hooks.addAction( 'cred-action-shortcode-dialog-loaded', self.shortcodeDialogTabsInit, 1 );

		/**
		 * Maybe block the shortcodes dialog, on demand.
		 *
		 * @since 2.1.1
		 */
		Toolset.hooks.addAction( 'cred-action-shortcode-dialog-loaded', self.maybeBlockDialog, 5 );

		return self;

	};

	/**
	 * Init GUI templates.
	 *
	 * @uses wp.template
	 * @since 1.9.3
	 */
	self.templates = {};
	self.initTemplates = function() {
		self.templates = {
			credIncluded: {
				'cred_form': wp.template( 'cred-post-edit-form-template' ),
				'cred_user_form': wp.template( 'cred-user-edit-form-template' ),
				'cred_child_link_form': wp.template( 'cred-create-child-post-link-template' )
			},
			afw: {
				instructions: wp.template( 'cred-relationship-form-shortcode-wizard-instructions' ),
				insertForm: wp.template( 'cred-relationship-form-shortcode-wizard-insertForm' ),
				insertLink: wp.template( 'cred-relationship-form-shortcode-wizard-insertLink' ),
				roleSources: wp.template( 'cred-relationship-form-shortcode-wizard-roleSources' ),
				editSources: wp.template( 'cred-relationship-form-shortcode-wizard-editSources' ),
				purpose: wp.template( 'cred-relationship-form-shortcode-wizard-purpose' ),
				linkTargets: ( self.i18n.conditions.layoutsActive ) ? wp.template( 'cred-relationship-form-shortcode-wizard-linkTargetsLayouts' ) : wp.template( 'cred-relationship-form-shortcode-wizard-linkTargetsCt' ),
				linkOptions: wp.template( 'cred-relationship-form-shortcode-wizard-linkOptions' )
			}
		};

		self.templates = _.extend( Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-templates', {} ), self.templates );

		return self;
	}

	/**
	 * Init GUI dialogs.
	 *
	 * @uses jQuery.dialog
	 * @since 1.9.3
	 */
	self.dialogs = {};
	self.dialogs.main = null;
	self.dialogs.shortcode = null;

	self.shortcodeDialogSpinnerContent = $(
		'<div style="min-height: 150px;">' +
		'<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; ">' +
		'<p>' + '<span class="spinner is-active"></span>' +
		cred_shortcode_i18n.action.loading + '</p>' +
		'</div>' +
		'</div>'
	);

	self.initDialogs = function() {

		/**
		 * Main dialog to list the Toolset Forms available shortcodes.
		 *
		 * @since 1.9.3
		 */
		if ( ! $( '#js-cred-shortcode-gui-dialog-container-main' ).length ) {
			$( 'body' ).append( '<div id="js-cred-shortcode-gui-dialog-container-main" class="toolset-dialog__body toolset-shortcodes js-toolset-dialog__body js-cred-shortcode-gui-dialog-container js-cred-shortcode-gui-dialog-container-main"></div>' );
		}
		self.dialogs.main = $( '#js-cred-shortcode-gui-dialog-container-main' ).dialog({
				dialogClass: 'toolset-dialog',
				autoOpen:	false,
				modal:		true,
				width:		'90%',
				title:		cred_shortcode_i18n.title.dialog,
				resizable:	false,
				draggable:	false,
				show: {
					effect:		"blind",
					duration:	800
				},
				create: function( event, ui ) {
					$( event.target ).parent().css( 'position', 'fixed' );
				},
				open: function( event, ui ) {
					$( 'body' ).addClass('modal-open');
					self.repositionDialog();
				},
				close: function( event, ui ) {
					$( 'body' ).removeClass( 'modal-open' );
				}
			});

		/**
		 * Canonical dialog to insert Toolset Forms post and user edit form shortcodes.
		 *
		 * @since 1.9.3
		 */
		if ( ! $( '#js-cred-shortcode-gui-dialog-container-shortcode' ).length ) {
			$( 'body' ).append( '<div id="js-cred-shortcode-gui-dialog-container-shortcode" class="toolset-dialog__body toolset-shortcodes js-toolset-dialog__body js-cred-shortcode-gui-dialog-container js-cred-shortcode-gui-dialog-container-shortcode"></div>' );
		}
		self.dialogs.shortcode = $( "#js-cred-shortcode-gui-dialog-container-shortcode" ).dialog({
			dialogClass: 'toolset-dialog',
			autoOpen:	false,
			modal:		true,
			width:		'90%',
			resizable:	false,
			draggable:	false,
			show: {
				effect:		"blind",
				duration:	800
			},
			create: function( event, ui ) {
				$( event.target ).parent().css( 'position', 'fixed' );
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.repositionDialog();
			},
			close: function( event, ui ) {
				//$( document ).trigger( 'js_event_wpv_shortcode_gui_dialog_closed' );
				$( 'body' ).removeClass( 'modal-open' );
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-cred-shortcode-gui-button-craft',
					text: cred_shortcode_i18n.action.insert,
					click: function() {
						var shortcodeToInsert = Toolset.hooks.applyFilters( 'toolset-filter-get-crafted-shortcode', false, $( '#js-cred-shortcode-gui-dialog-container-shortcode' ) );
						// shortcodeToInsert will fail on validtion failure
						if ( shortcodeToInsert ) {
							$( this ).dialog( "close" );
							Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcodeToInsert );
						}
					}
				},
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-secondary toolset-shortcode-gui-dialog-button-back js-cred-shortcode-gui-button-back',
					text: cred_shortcode_i18n.action.back,
					click: function() {
						$( this ).dialog( "close" );
						self.openCredDialog();
					}
				},
				{
					class: 'button-secondary toolset-shortcode-gui-dialog-button-close js-cred-shortcode-gui-button-close',
					text: cred_shortcode_i18n.action.cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				}
			]
		});

		/**
		 * Canonical dialog to insert Toolset Forms shortcodes that demand a wizard.
		 *
		 * @since m2m
		 */
		if ( ! $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ).length ) {
			$( 'body' ).append( '<div id="js-cred-shortcode-gui-dialog-container-wizard-shortcode" class="toolset-shortcode-gui-dialog-container toolset-shortcode-gui-wizard-container js-toolset-shortcode-gui-dialog-container js-toolset-shortcode-gui-wizard-container js-cred-shortcode-gui-dialog-container js-cred-shortcode-gui-dialog-container-wizard-shortcode"></div>' );
		}
		self.dialogs.associationFormShortcodeWizard = $( "#js-cred-shortcode-gui-dialog-container-wizard-shortcode" ).dialog({
			autoOpen:	false,
			modal:		true,
			width:		'70%',
			resizable:	false,
			draggable:	false,
			show: {
				effect:		"blind",
				duration:	800
			},
			create: function( event, ui ) {
				$( event.target ).parent().css( 'position', 'fixed' );
			},
			open: function( event, ui ) {
				$( 'body' ).addClass( 'modal-open' );
				self.repositionDialog();
			},
			close: function( event, ui ) {
				$( 'body' ).removeClass( 'modal-open' );
				var dialogCompleteData = $( this ).dialog( "option", "wizardData" );
				Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-after-close', dialogCompleteData );
			},
			buttons:[
				{
					class: 'toolset-shortcode-gui-dialog-button-align-right button-primary js-cred-shortcode-gui-button-afw-craft',
					text: cred_shortcode_i18n.action.loading,
					click: function() {
						self.associationFormShortcodeWizardNext();
					}
				},
				{

					class: 'button-secondary toolset-shortcode-gui-dialog-button-close js-cred-shortcode-gui-button-close',
					text: cred_shortcode_i18n.action.cancel,
					click: function() {
						$( this ).dialog( "close" );
					}
				},
				{

					class: 'button-secondary js-cred-shortcode-gui-button-afw-previous',
					text: cred_shortcode_i18n.action.previous,
					click: function() {
						var dialogCompleteData = $( this ).dialog( "option", "wizardData" );
						Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-do-previous', dialogCompleteData );
					}
				}
			]
		});

		$( window ).resize( self.resizeWindowEvent );

		return self;

	}

	/**
	 * Callback for the window.resize event.
	 *
	 * @since m2m
	 */
	self.resizeWindowEvent = _.debounce( function() {
		self.repositionDialog();
	}, 200 );

	/**
	 * Reposition the Toolset Forms dialogs based on the current window size.
	 *
	 * @since m2m
	 */
	self.repositionDialog = function() {
		var winH = $( window ).height() - 100;

		_.each( self.dialogs, function( dialog, key, list ) {
			dialog.dialog( "option", "maxHeight", winH );
			dialog.dialog( "option", "position", {
				my:        "center top+50",
				at:        "center top",
				of:        window,
				collision: "none"
			});
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
	 * Open the main Toolset Forms dialog to offer Toolset Forms shortcodes.
	 *
	 * @since 1.9.3
	 */
	self.openCredDialog = function() {
		self.dialogs.main.dialog( 'open' );
	}

	/**
	 * Init the Admin Bar button, if any.
	 *
	 * @since 1.9.3
	 */
	self.initAdminBarButton = function() {
		if ( $( '.js-cred-shortcode-generator-node a' ).length > 0 ) {
			$( '.js-cred-shortcode-generator-node a' ).addClass( 'js-cred-in-adminbar' );
		}
	};

	/**
	 * Set the right active editor and action when clicking any Toolset Forms button, and open the main dialog.
	 *
	 * Acceptable selectors to trigger actions are:
	 * - Admin Bar: .js-cred-in-adminbar
	 * - Editor Toolbar: .js-cred-in-toolbar
	 *
	 * @since 1.9.3
	 */
	$( document ).on( 'click','.js-cred-in-adminbar', function( e ) {
		e.preventDefault();

		Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'create' );
		self.openCredDialog();

		return false;
	});
	$( document ).on( 'click', '.js-cred-in-toolbar', function( e ) {
		e.preventDefault();

		var credInToolbarButton = $( this );
		if ( credInToolbarButton.attr( 'data-editor' ) ) {
			window.wpcfActiveEditor = credInToolbarButton.data( 'editor' );
		}

		Toolset.hooks.doAction( 'toolset-action-set-shortcode-gui-action', 'insert' );
		self.openCredDialog();

		return false;
	});

	/**
	 * Insert a shortcode without attributes.
	 *
	 * @since 1.9.3
	 */
	$( document ).on( 'click', '.js-cred-shortcode-gui-no-attributes', function( e ) {
		e.preventDefault();

		if ( self.dialogs.main.dialog( "isOpen" ) ) {
			self.dialogs.main.dialog('close');
		}

		var shortcode = $( this ).data( 'shortcode' );
		Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcode );
	});

	self.shortcodeDoAction = function( data ) {
		if ( self.dialogs.main.dialog( "isOpen" ) ) {
			self.dialogs.main.dialog('close');
		}

		var shortcode = '[' + data.shortcode;
		if ( _.has( data, 'parameters' ) ) {
			_.each( data.parameters, function( parameterValue, parameterKey ) {
				shortcode += ' ' + parameterKey + '="' + parameterValue + '"';
			});
		}
		shortcode += ']';

		/**
		 * Filter the generated shortcode to support shortcodes with different format.
		 *
		 * As of Views 2.5.0, a new shortcode format is introduced that uses placeholders instead of brackets.
		 * This format is mainly used when building a Content Template using some page builder in order to
		 * avoid unwanted escaping.
		 *
		 * @param string  shortcode  The generated shortcode.
		 *
		 * @since 1.9.3
		 */
		shortcode = Toolset.hooks.applyFilters( 'wpv-filter-wpv-shortcodes-transform-format', shortcode );

		Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcode );
	};

	/**
	 * Close the main dialog when clicking on any of its items.
	 *
	 * @since 1.9.3
	 */
	$( document ).on( 'click', '.js-cred-shortcode-gui-group-list .js-cred-shortcode-gui', function( e ) {
		e.preventDefault();

		if ( self.dialogs.main.dialog( "isOpen" ) ) {
			self.dialogs.main.dialog('close');
		}
	});


	/**
	 * Open the dialog to insert an association form.
	 *
	 * @param object dialogData
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardDialogOpen = function( dialogData ) {
		var templateData = _.extend(
			dialogData,
			{
				templates:  self.templates,
				conditions: self.i18n.conditions,
				actionMode: 'insertForm'
			}
		);

		self.associationFormShortcodeWizardPrepare( dialogData );
		self.associationFormShortcodeWizardFire( templateData );

	};

	/**
	 * Open the dialog to insert an association form link.
	 *
	 * @param object dialogData
	 *
	 * @since m2m
	 */
	self.associationFormLinkShortcodeWizardDialogOpen = function( dialogData ) {
		_.defaults( dialogData, { parameters: {}, overrides: {} } );

		var templateData = _.extend(
			dialogData,
			{
				templates:  self.templates,
				conditions: self.i18n.conditions,
				actionMode: 'insertLink'
			}
		);

		self.associationFormShortcodeWizardPrepare( dialogData );
		self.associationFormShortcodeWizardFire( templateData );

	};

	/**
	 * Prepare the dialogs for inserting an association form or its related link.
	 *
	 * @param object dialogData
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardPrepare = function( dialogData ) {
		// Race condition:
		// We close the main dialog before opening the shortcode dialog,
		// so we can keep the .modal-open classname in the document body, to:
		// - avoid scrolling
		// - prevent positioning issues with toolset_select2
		if ( self.dialogs.main.dialog( "isOpen" ) ) {
			self.dialogs.main.dialog('close');
		}

		Toolset.hooks.doAction( 'cred-action-shortcode-wizard-dialog-requested', dialogData );
		Toolset.hooks.doAction( 'toolset-action-shortcode-wizard-dialog-requested', dialogData );

		// Show the "empty" dialog with a spinner while loading dialog content
		self.dialogs.associationFormShortcodeWizard.dialog( 'open' ).dialog({
			title: dialogData.title
		});

		self.dialogs.associationFormShortcodeWizard.html( self.shortcodeDialogSpinnerContent );
		$( '.js-cred-shortcode-gui-button-afw-craft' )
			.addClass( 'button-secondary' )
			.removeClass( 'button-primary' )
			.prop( 'disabled', true );

		$( '.js-cred-shortcode-gui-button-afw-previous' ).hide();

		Toolset.hooks.doAction( 'cred-action-shortcode-wizard-dialog-preloaded', dialogData );
		Toolset.hooks.doAction( 'toolset-action-shortcode-wizard-dialog-preloaded', dialogData );
	};

	/**
	 * Populate the dialogs for inserting an association form or its related link, and push it to the right wizard step.
	 *
	 * @param object templateData
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardFire = function( templateData ) {
		var ajaxData = {
			action: self.i18n.ajaxaction.get_association_form_data.action,
			wpnonce: self.i18n.ajaxaction.get_association_form_data.nonce,
			form: templateData.parameters.form
		};


		$.ajax({
			url:      self.i18n.ajaxurl,
			data:     ajaxData,
			dataType: 'json',
			type:     "GET",
			success:  function( originalResponse ) {
				var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
				if ( response.success ) {
					var dialogCompleteData = $.extend( true, {}, response.data, templateData );
					dialogCompleteData.currentStep = '';
					self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData", dialogCompleteData );
					self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardStep", '' );
					self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardAction", templateData.actionMode );

					var showAssociationFormInstructions = Toolset.hooks.applyFilters(
						'cred-filter-association-form-shortcode-wizard-show-instructions',
						self.i18n.conditions.associationFormInstructions,
						dialogCompleteData
					);

					if ( showAssociationFormInstructions ) {
						self.associationFormShortcodeWizardAction.instructions();
					} else if ( _.has( self.associationFormShortcodeWizardAction, templateData.actionMode ) ) {
						self.associationFormShortcodeWizardAction[ templateData.actionMode ]();
					}

				} else {
					self.associationFormShortcodeWizardAction.error( response.data );
				}
			},
			error: function ( ajaxContext ) {

			}
		});
	};

	/**
	 * Manage each of the association form wizard steps.
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardAction = {
		error: function( responseData ) {
			self.dialogs.associationFormShortcodeWizard.html( '<div class="toolset-alert toolset-alert-error"><p>' + responseData.message + '</p></div>' );
			self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.doContinue );
			$( '.js-cred-shortcode-gui-button-afw-craft' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );
		},
		instructions: function() {
			var dialogCompleteData = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData" );

			dialogCompleteData.currentStep = 'instructions';
			dialogCompleteData.shortcode = self.i18n.shortcodeHandle.association.form;
			self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData", dialogCompleteData );
			self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardStep", 'instructions' );
			self.dialogs.associationFormShortcodeWizard.html( self.templates.afw.instructions( dialogCompleteData ) );

			self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.doContinue );
			$( '.js-cred-shortcode-gui-button-afw-craft' )
				.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );

			Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-after-step', dialogCompleteData );
			Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-after-step-instructions', dialogCompleteData );
		},
		insertForm: function() {
			var $dialog = $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ),
				dialogCompleteData = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData" );

			dialogCompleteData.currentStep = 'insertForm';
			dialogCompleteData.shortcode = self.i18n.shortcodeHandle.association.form;
			self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData", dialogCompleteData );
			self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardStep", 'insertForm' );
			self.dialogs.associationFormShortcodeWizard.html( self.templates.afw.insertForm( dialogCompleteData ) );
			switch( Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-action', '' ) ) {
				case 'save':
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.save );
					break;
				case 'create':
				case 'append':
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.create );
					break;
				case 'edit':
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.update );
					break;
				case 'insert':
				default:
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.insert );
					break;
			}

			$( '.js-cred-shortcode-gui-button-afw-craft' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );

			$( '.js-toolset-shortcode-gui-wizard-option-extra', $dialog ).hide();

			Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-after-step', dialogCompleteData );
			Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-after-step-insertForm', dialogCompleteData );
		},
		insertLink: function() {
			var $dialog = $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ),
				dialogCompleteData = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData" );

			dialogCompleteData.currentStep = 'insertLink';
			dialogCompleteData.shortcode = self.i18n.shortcodeHandle.association.link;
			self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData", dialogCompleteData );
			self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardStep", 'insertLink' );
			self.dialogs.associationFormShortcodeWizard.html( self.templates.afw.insertLink( dialogCompleteData ) );
			switch( Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-action', '' ) ) {
				case 'save':
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.save );
					break;
				case 'create':
				case 'append':
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.create );
					break;
				case 'edit':
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.update );
					break;
				case 'insert':
				default:
					self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.insert );
					break;
			}

			$( '.js-cred-shortcode-gui-button-afw-craft' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );

			$( '.js-toolset-shortcode-gui-wizard-option-extra', $dialog ).hide();

			Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-after-step', dialogCompleteData );
			Toolset.hooks.doAction( 'cred-action-association-form-shortcode-wizard-after-step-insertLink', dialogCompleteData );
		}
	};

	/**
	 * Manage each of the association form wizard available outcomes.
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardOutcome = {
		basic: function( dialogCompleteData ) {
			var shortcodeToInsert = Toolset.hooks.applyFilters( 'toolset-filter-get-crafted-shortcode', false, $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ) );
			// shortcodeToInsert will fail on validation failure
			if ( shortcodeToInsert ) {
				dialogCompleteData.currentStep = 'completed';
				self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData", dialogCompleteData );
				self.dialogs.associationFormShortcodeWizard.dialog( 'close' );
                Toolset.hooks.doAction( 'cred-action-before-do-relationship-form-shortcode-gui-action', shortcodeToInsert, dialogCompleteData );
                Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcodeToInsert );
			}
		},
		createCt: function ( dialogCompleteData ) {
			var shortcodeCanDoAction = Toolset.hooks.applyFilters( 'toolset-filter-get-crafted-shortcode', false, $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' )  );
			if ( ! shortcodeCanDoAction ) {
				return;
			}

			self.setButtonText( $( '.js-cred-shortcode-gui-button-afw-craft' ), self.i18n.action.processing );
			$( '.js-cred-shortcode-gui-button-afw-craft' )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' )
				.prop( 'disabled', true );

			var $dialog = $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ),
				ajaxData = {
					action: self.i18n.ajaxaction.create_form_template.action,
					wpnonce: self.i18n.ajaxaction.create_form_template.nonce,
					ctTitle: $( '.js-cred-relationship-form-shortcode-wizard-link-target-createct-title', $dialog ).val(),
					shortcode: self.i18n.shortcodeHandle.association.form,
					formSlug: dialogCompleteData.parameters.form
				};

			$.ajax({
				url:      self.i18n.ajaxurl,
				data:     ajaxData,
				dataType: 'json',
				type:     "GET",
				success:  function( originalResponse ) {
					var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
					if ( response.success ) {
						var shortcodeToInsert = Toolset.hooks.applyFilters( 'toolset-filter-get-crafted-shortcode', false, $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ) );
						shortcodeToInsert = shortcodeToInsert.replace( dialogCompleteData.shortcode, dialogCompleteData.shortcode + ' content_template_slug="' + response.data.ctSlug + '"' );
						dialogCompleteData.currentStep = 'completed';
						self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData", dialogCompleteData );
						self.dialogs.associationFormShortcodeWizard.dialog( 'close' );
                        Toolset.hooks.doAction( 'cred-action-before-do-relationship-form-shortcode-gui-action', shortcodeToInsert, dialogCompleteData );
                        Toolset.hooks.doAction( 'toolset-action-do-shortcode-gui-action', shortcodeToInsert );
					} else {

					}
				},
				error: function ( ajaxContext ) {

				}
			});
		}
	};

	/**
	 * Dismiss the association form wizard instructions, per user.
	 *
	 * @since m2m
	 */
	self.associationFormInstructionsDismiss = function() {
		var ajaxData = {
				action: self.i18n.ajaxaction.dismiss_association_shortcode_instructions.action,
				wpnonce: self.i18n.ajaxaction.dismiss_association_shortcode_instructions.nonce
			};

		$.ajax({
			url:      self.i18n.ajaxurl,
			data:     ajaxData,
			dataType: 'json',
			type:     "POST",
			success:  function( originalResponse ) {
				var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
				if ( response.success ) {
					self.i18n.conditions.associationFormInstructions = false;
				} else {

				}
			},
			error: function ( ajaxContext ) {

			}
		});
	};

	/**
	 * Move the association form wizard to the next step, if any.
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardNext = function() {
		var $dialog = $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ),
			currentWizardStep = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardStep" ),
			dialogCompleteData = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData" ),
			wizardAction = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardAction" );

		switch( currentWizardStep ) {
			case 'instructions':
				var dismissAction = $( '.js-cred-relationship-form-wizard-dismiss:checked', $dialog ).val();
				if ( 'dismiss' == dismissAction ) {
					self.associationFormInstructionsDismiss();
				}
				if ( _.has( self.associationFormShortcodeWizardAction, wizardAction ) ) {
					self.associationFormShortcodeWizardAction[ wizardAction ]();
				}
				break;
			case 'insertForm':
				self.associationFormShortcodeWizardOutcome.basic( dialogCompleteData );
				break;
			case 'insertLink':
				var linkTarget = $( '.js-cred-relationship-form-shortcode-wizard-link-target:checked' ).val();

				if ( 'createct' != linkTarget ) {
					self.associationFormShortcodeWizardOutcome.basic( dialogCompleteData );
					return;
				}

				if ( '' == $( '.js-cred-relationship-form-shortcode-wizard-link-target-createct-title' ).val() ) {
					$( '.js-cred-relationship-form-shortcode-wizard-link-target-createct-title' )
						.addClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' );
				} else {
					self.associationFormShortcodeWizardOutcome.createCt( dialogCompleteData );
				}
				break;
		}
	};

	$( document ).on( 'change', '.js-cred-relationship-form-wizard-purpose', function() {
		var $dialog = $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' ),
			currentWizardStep = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardStep" ),
			dialogCompleteData = self.dialogs.associationFormShortcodeWizard.dialog( "option", "wizardData" ),
			$purposeOption = $( '.js-cred-relationship-form-wizard-purpose:checked' ),
			purpose = $purposeOption.val();

		$( '.js-cred-shortcode-gui-button-afw-craft' )
			.addClass( 'button-primary' )
			.removeClass( 'button-secondary' )
			.prop( 'disabled', false );

		$( '.js-toolset-shortcode-gui-wizard-option-extra', $dialog ).hide();
		$( '.js-cred-relationship-form-wizard-setRole', $dialog ).html( '' );
		$( '.js-cred-relationship-form-wizard-linkTargets', $dialog ).html( '' );
		$( '.js-cred-relationship-form-wizard-linkOptions', $dialog ).html( '' );

		switch( purpose ) {
			case 'edit':
				var editData = {
					shortcode: dialogCompleteData.shortcode,
					attribute: 'role_items',
					relationship: dialogCompleteData.relationship.slug,
					relationshipLabelSingular: dialogCompleteData.relationship.label,
					conditions: self.i18n.conditions,
					linkLabel: dialogCompleteData.relationship.addNew,
					postRelationshipFilterData: _.defaults(
						Toolset.hooks.applyFilters( 'wpv-filter-wpv-get-post-relationship-filter-data', {} ),
						{
							relationship: '',
							role: 'child',
							mode: ''
						}
					)
				};
				$( '.js-toolset-shortcode-gui-wizard-option-extra', $dialog ).show();
				$( '.js-cred-relationship-form-wizard-setRole', $dialog )
					.html( self.templates.afw.editSources( editData ) );
				if ( 'insertLink' == currentWizardStep ) {
					self.setLinkTargetsSection( $dialog, editData );
				}
				break;
			case 'createChild':
				var roleData = {
					shortcode: dialogCompleteData.shortcode,
					attribute: 'parent_item',
					relationship: dialogCompleteData.relationship.slug,
					relationshipLabelSingular: dialogCompleteData.relationship.label,
					fixedRole: 'parent',
					role: dialogCompleteData.relationship.child,
					oppositeRole: dialogCompleteData.relationship.parent,
					conditions: self.i18n.conditions,
					linkLabel: dialogCompleteData.relationship.child.addNew,
					postRelationshipFilterData: _.defaults(
						Toolset.hooks.applyFilters( 'wpv-filter-wpv-get-post-relationship-filter-data', {} ),
						{
							relationship: '',
							role: 'child',
							mode: ''
						}
					)
				};
				$( '.js-toolset-shortcode-gui-wizard-option-extra', $dialog ).show();
				$( '.js-cred-relationship-form-wizard-setRole', $dialog )
					.html( self.templates.afw.roleSources( roleData ) );
				self.associationFormShortcodeWizardSetRoleOption( roleData );
				var $selector = $( '.js-cred-relationship-form-shortcode-wizard-role', $dialog );
				self.associationFormShortcodeWizardSelect2( $selector, roleData.oppositeRole.type, 'ID' );
				if ( 'insertLink' == currentWizardStep ) {
					self.setLinkTargetsSection( $dialog, roleData );
				}
				break;
			case 'createParent':
				var roleData = {
					shortcode: dialogCompleteData.shortcode,
					attribute: 'child_item',
					relationship: dialogCompleteData.relationship.slug,
					relationshipLabelSingular: dialogCompleteData.relationship.label,
					fixedRole: 'child',
					role: dialogCompleteData.relationship.parent,
					oppositeRole: dialogCompleteData.relationship.child,
					conditions: self.i18n.conditions,
					linkLabel: dialogCompleteData.relationship.parent.addNew,
					postRelationshipFilterData: _.defaults(
						Toolset.hooks.applyFilters( 'wpv-filter-wpv-get-post-relationship-filter-data', {} ),
						{
							relationship: '',
							role: 'child',
							mode: ''
						}
					)
				};
				$( '.js-toolset-shortcode-gui-wizard-option-extra', $dialog ).show();
				$( '.js-cred-relationship-form-wizard-setRole', $dialog )
					.html( self.templates.afw.roleSources( roleData ) );
				self.associationFormShortcodeWizardSetRoleOption( roleData );
				Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-loaded' );
				var $selector = $( '.js-cred-relationship-form-shortcode-wizard-role', $dialog );
				self.associationFormShortcodeWizardSelect2( $selector, roleData.oppositeRole.type, 'ID' );
				if ( 'insertLink' == currentWizardStep ) {
					self.setLinkTargetsSection( $dialog, roleData );
				}
				break;
			default:
				break;
		};
	});

	self.setLinkTargetsSection = function( $dialog, data ) {
		$( '.js-cred-relationship-form-wizard-linkTargets', $dialog )
			.html( self.templates.afw.linkTargets( data ) );
		$( '.js-cred-relationship-form-wizard-linkOptions', $dialog )
			.html( self.templates.afw.linkOptions( data ) );
		if ( self.i18n.conditions.layoutsActive ) {
			var $select2Selector = $( '.js-cred-relationship-form-shortcode-wizard-link-target-existinglayout-selector', $dialog );
			self.associationFormShortcodeWizardSelect2( $select2Selector, 'dd_layouts', 'post_name' );
		} else {
			var $select2Selector = $( '.js-cred-relationship-form-shortcode-wizard-link-target-existingct-selector', $dialog );
			self.associationFormShortcodeWizardSelect2( $select2Selector, 'view-template', 'post_name' );
		}
	};

	/**
	 * Set the right role option for the association form wizard, depending on the current environment conditions.
	 *
	 * @param object roleSourcesData
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardSetRoleOption = function( roleSourcesData ) {
		var $dialog = $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode' );

		if ( self.i18n.conditions.editingView ) {
			if ( roleSourcesData.relationship != roleSourcesData.postRelationshipFilterData.relationship ) {
				$( '.js-cred-relationship-form-shortcode-wizard-role-source[value="toolsetCombo"]', $dialog )
					.prop( 'checked', true )
					.trigger( 'change' );
				return;
			}
			if ( roleSourcesData.fixedRole == roleSourcesData.postRelationshipFilterData.role ) {
				$( '.js-cred-relationship-form-shortcode-wizard-role-source[value="$current"]', $dialog )
					.prop( 'checked', true )
					.trigger( 'change' );
			} else {
				$( '.js-cred-relationship-form-shortcode-wizard-role-source[value="$fromfilter"]', $dialog )
					.prop( 'checked', true )
					.trigger( 'change' );
			}
		} else {
			$( '.js-cred-relationship-form-shortcode-wizard-role-source[value="$current"]', $dialog )
				.prop( 'checked', true )
				.trigger( 'change' );
		}
	};

	$( document ).on( 'change', '.js-cred-relationship-form-shortcode-wizard-link-target', function() {
		var $dialog = $( '#js-cred-shortcode-gui-dialog-container-wizard-shortcode'),
			linkTarget = $( '.js-cred-relationship-form-shortcode-wizard-link-target:checked', $dialog ).val();

		$( '.js-cred-relationship-form-shortcode-wizard-link-target-createct', $dialog ).hide();
		if ( 'createct' == linkTarget ) {
			$( '.js-cred-relationship-form-shortcode-wizard-link-target-createct', $dialog ).show();
		}
	});

	$( document ).on( 'change', '.js-cred-relationship-form-shortcode-wizard-link-target-createct-title', function() {
		$( this ).removeClass( 'toolset-shortcode-gui-invalid-attr js-toolset-shortcode-gui-invalid-attr' )
	});

	/**
	 * Initialize select2 instances on demand for the association form wizard, to search for posts by title.
	 *
	 * @param jQuery object $selector
	 * @param string postType The post type to search for
	 * @param string valueType The type of value that should be offered as option
	 *
	 * @since m2m
	 */
	self.associationFormShortcodeWizardSelect2 = function( $selector, postType, valueType ) {
		var $selectorParent = $selector.closest( '.js-toolset-shortcode-gui-dialog-container' );
		$selector
				.css( { width: '25em' } )
				.toolset_select2(
					{
						width:				'resolve',
						dropdownAutoWidth:	true,
						dropdownParent:		$selectorParent,
						placeholder:		$selector.data( 'placeholder' ),
						minimumInputLength:	2,
						ajax: {
							url: self.i18n.ajaxurl,
							dataType: 'json',
							delay: 250,
							type: 'post',
							data: function( params ) {
								return {
									action:    self.i18n.ajaxaction.select2_suggest_posts_by_title.action,
									s:         params.term,
									postType:  postType,
									valueType: valueType,
									page:      params.page,
									wpnonce:   self.i18n.ajaxaction.select2_suggest_posts_by_title.nonce
								};
							},
							processResults: function( originalResponse, params ) {
								var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
								params.page = params.page || 1;
								if ( response.success ) {
									return {
										results: response.data,
									};
								}
								return {
									results: [],
								};
							},
							cache: false
						}
					}
				)
				.data( 'toolset_select2' )
					.$dropdown
						.addClass( 'toolset_select2-dropdown-in-dialog' );
	};

	$( document ).on( 'click', '.js-wp-cred-shortcode-pointer', function() {
		var $tooltipTriggerer = $( this ),
			tooltipContent = $tooltipTriggerer.closest( 'li' ).find( '.js-wp-cred-shortcode-pointer-content' ).html();
			edge = ( $( 'html[dir="rtl"]' ).length > 0 ) ? 'top' : 'top';

		// hide this pointer if other pointer is opened.
		$( '.wp-toolset-pointer' ).fadeOut( 100 );

		$tooltipTriggerer.pointer({
			pointerClass: 'wp-toolset-pointer wp-toolset-cred-pointer js-wp-toolset-cred-pointer',
			pointerWidth: 400,
			content: tooltipContent,
			position: {
				edge: edge,
				align: 'center',
				offset: '15 0'
			},
			buttons: function( event, t ) {
				var button_close = $( '<button class="button button-primary-toolset alignright">' + self.i18n.action.close + '</button>' );
				button_close.on( 'click.pointer', function( e ) {
					e.preventDefault();
					t.element.pointer( 'close' );
				});
				return button_close;
			}
		}).pointer( 'open' );
		$( '.js-wp-toolset-cred-pointer:not(.js-wp-toolset-cred-pointer-indexed)' )
			.addClass( '.js-wp-toolset-cred-pointer-zindexed' )
			.css( 'z-index', '10000000' );
	});

	/**
	 * Display a dialog for inserting a generic Toolset Forms shortcode.
	 *
	 * @param object dialogData
	 *     shortcode	string	Shortcode name.
	 *     title 		string	Form title.
	 *     parameters	object	Optional. Hidden parameters to enforce as attributes for the resulting shortcode.
	 *     overrides	object	Optional. Attribute values to override/enforce, mainly when editing a shortcode.
	 *
	 * @since 1.9.3
	 */
	self.shortcodeDialogOpen = function( dialogData ) {
		// Race condition:
		// We close the main dialog before opening the shortcode dialog,
		// so we can keep the .modal-open classname in the document body, to:
		// - avoid scrolling
		// - prevent positioning issues with toolset_select2
		if ( self.dialogs.main.dialog( "isOpen" ) ) {
			self.dialogs.main.dialog('close');
		}

		_.defaults( dialogData, { parameters: {}, overrides: {} } );

		Toolset.hooks.doAction( 'cred-action-shortcode-dialog-requested', dialogData );
		Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-requested', dialogData );

		// Show the "empty" dialog with a spinner while loading dialog content
		self.dialogs.shortcode.dialog( 'open' ).dialog({
			title: dialogData.title
		});
		self.dialogs.shortcode.html( self.shortcodeDialogSpinnerContent );

		Toolset.hooks.doAction( 'cred-action-shortcode-dialog-preloaded', dialogData );
		Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-preloaded', dialogData );

		var templateData = _.extend(
			{
				templates: self.templates
			},
			dialogData
		);

		// Shortcodes registered within the GUI
		if ( _.has( cred_shortcode_i18n.shortcodesWithGui, dialogData.shortcode ) ) {
			templateData = _.extend( templateData, cred_shortcode_i18n.shortcodesWithGui[ dialogData.shortcode ] );
			self.dialogs.shortcode.html( self.templates.dialog( templateData ) );
			Toolset.hooks.doAction( 'cred-action-shortcode-dialog-loaded', dialogData );
			Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-loaded', dialogData );
			return;
		}

		// Shortcodes registered within the GUI in the legacy way
		// @todo Move the credIncluded shortcode templates to cred_shortcode_i18n.shortcodesWithGui
		if ( _.has( self.templates.credIncluded, dialogData.shortcode ) ) {
			self.dialogs.shortcode.html( self.templates.credIncluded[ dialogData.shortcode ]( templateData ) );
			Toolset.hooks.doAction( 'cred-action-shortcode-dialog-loaded', dialogData );
			Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-loaded', dialogData );
			return;
		}

		// Shortcodes registered within the GUI for delayed AJAX-gathered data
		if( _.has( cred_shortcode_i18n.shortcodesWithDelayedGui, dialogData.shortcode ) ) {
			self.shortcodeDialogOpenDelayed( dialogData, templateData );
			return;
		}

		self.dialogs.shortcode.dialog( 'close' );
		return;
	}

	/**
	 * Adjust the dialog buttons labels depending on the current GUI action.
	 *
	 * @since 1.9.3
	 */
	self.manageShortcodeDialogButtonpane = function( dialogData ) {
		switch ( Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-action', '' ) ) {
			case 'save':
				$( '.js-cred-shortcode-gui-button-back' ).hide();
				self.setButtonText( $( '.js-cred-shortcode-gui-button-craft' ), cred_shortcode_i18n.action.save );
				break;
			case 'create':
			case 'append':
				$( '.js-cred-shortcode-gui-button-back' ).show();
				self.setButtonText( $( '.js-cred-shortcode-gui-button-craft' ), cred_shortcode_i18n.action.create );
				break;
			case 'edit':
				$( '.js-cred-shortcode-gui-button-back' ).hide();
				self.setButtonText( $( '.js-cred-shortcode-gui-button-craft' ), cred_shortcode_i18n.action.update );
				break;
			case 'insert':
			default:
				$( '.js-cred-shortcode-gui-button-back' ).show();
				self.setButtonText( $( '.js-cred-shortcode-gui-button-craft' ), cred_shortcode_i18n.action.insert );
				break;
		}
	};

	/**
	 * Initialize the shortcode dialog tabs, if any, after the dialog is fully open.
	 *
	 * @since m2m
	 */
	self.shortcodeDialogTabsInit = function() {
		if ( self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs-list > li' ).length > 1 ) {
			self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs' )
				.tabs({
					beforeActivate: function( event, ui ) {
						if ( ! Toolset.hooks.applyFilters( 'toolset-filter-is-shortcode-attributes-container-valid', true, ui.oldPanel ) ) {
							event.preventDefault();
							ui.oldTab.focus().addClass( 'toolset-shortcode-gui-tabs-incomplete' );
							setTimeout( function() {
								ui.oldTab.removeClass( 'toolset-shortcode-gui-tabs-incomplete' );
							}, 1000 );
						}
					}
				})
				.addClass( 'ui-tabs-vertical ui-helper-clearfix' )
				.removeClass( 'ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all' );
			$( '#js-toolset-shortcode-gui-dialog-tabs ul, #js-toolset-shortcode-gui-dialog-tabs li' )
				.removeClass( 'ui-corner-top ui-corner-right ui-corner-bottom ui-corner-left ui-corner-all');
		} else {
			self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs-list' ).remove();
			self.dialogs.shortcode.find( '.js-toolset-shortcode-gui-tabs' ).addClass( 'toolset-shortcodes__tabs_single' );
		}
	};

	/**
	 * Maybe block the shortcodes dialog, on demand.
	 *
	 * Blocking the dialog means, by now, just disabling the button to perform the shortcode action.
	 *
	 * Block the dialogs to add an edit link when it just contains an information attribute:
	 * this means that there are no resources (CT or layout) to use in the link.
	 *
	 * @param object dialogData
	 *
	 * @since 2.1.1
	 */
	self.maybeBlockDialog = function( dialogData ) {
		if (
			_.contains( [ 'toolset-edit-post-link', 'toolset-edit-user-link' ], dialogData.shortcode )
			&& $( '.js-toolset-shortcode-gui-attribute-wrapper-for-instructions', '#js-cred-shortcode-gui-dialog-container-shortcode' ).length > 0
		) {
			$( '#js-cred-shortcode-gui-dialog-container-shortcode' )
				.siblings( '.ui-dialog-buttonpane' )
					.find( '.js-cred-shortcode-gui-button-craft' )
						.addClass( 'button-secondary' )
						.removeClass( 'button-primary' )
						.prop( 'disabled', true );
		}
	};

	/**
	 * Manage the dialog for shortcodes that require remote data.
	 *
	 * @param object dialogData
	 * @param object templateData
	 *
	 * @since m2m
	 */
	self.shortcodeDialogOpenDelayed = function( dialogData, templateData ) {
		var ajaxData = {
			action: self.i18n.ajaxaction.get_shortcode_attributes.action,
			wpnonce: self.i18n.ajaxaction.get_shortcode_attributes.nonce,
			credPagenow: cred_shortcode_i18n.pagenow,
			credPage: cred_shortcode_i18n.page,
			shortcode: dialogData.shortcode
		};

		$.ajax({
			url:      cred_shortcode_i18n.ajaxurl,
			data:     ajaxData,
			dataType: 'json',
			type:     "GET",
			success:  function( originalResponse ) {
				var response = WPV_Toolset.Utils.Ajax.parseResponse( originalResponse );
				if ( response.success ) {
					var templateCompleteData = $.extend( true, {}, response.data, templateData );
					self.dialogs.shortcode.html( self.templates.dialog( templateCompleteData ) );
					Toolset.hooks.doAction( 'cred-action-shortcode-dialog-loaded', dialogData );
					Toolset.hooks.doAction( 'toolset-action-shortcode-dialog-loaded', dialogData );
				} else {

				}
			},
			error: function ( ajaxContext ) {

			}
		});
	};

	//--------------------------------
    // Compatibility
    //--------------------------------

    /**
     * Handle the event that is triggered by Fusion Builder when creating the WP editor instance.	 *
	 * The event was added as per our request because Fusion Builder does not load the WP editor using
	 * the native PHP function "wp_editor". It creates the WP editor instance on JS, so no PHP actions
	 * to add custom media buttons like ours are available. It generates the media button plus the toolbar that
	 * contains it as javascript objects that it appends to its own template. It offers no way of adding our custom
	 * buttons to it.
	 *
	 * @param event			The actual event.
	 * @param editorId		The id of the editor that is being created.
     *
     * @since 1.9.4
     */
    $( document ).on( 'fusionButtons', function( event, editorId ) {
		self.addButtonToDynamicEditor( editorId );
    });

	/**
	 * Handle the event that is triggered by Toolset Types when creating a WP editor instance.
	 *
	 * The event is fired when a WYSIWYG field is dynamically initialized in the backend.
	 *
	 * @param event			The actual event.
	 * @param editorId		The id of the editor that is being created.
	 *
	 * @since 2.0
	 */
	$( document ).on( 'toolset:types:wysiwygFieldInited toolset:forms:wysiwygFieldInited', function( event, editorId ) {
		self.addButtonToDynamicEditor( editorId );
	});

    /**
	 * Add a Toolset Forms button dynamically to any native editor that contains a media toolbar, given its editor ID.
     *
     * @since 1.9.4
     */

    self.addButtonToDynamicEditor = function( editorId ) {
        var $mediaButtons = $( '#wp-' + editorId + '-media-buttons' ),
            button = '<span'
                + ' class="button js-cred-in-toolbar"'
                + ' data-editor="' + editorId + '">'
                + '<i class="icon-cred-logo fa fa-cred-custom ont-icon-18 ont-color-gray"></i>'
                + '<span class="button-label">' + cred_shortcode_i18n.title.button + '</span>'
                + '</span>',
            $credButton = $( button );

		if ( $mediaButtons.find( '.js-cred-in-toolbar' ).length == 0 ) {
			$credButton.appendTo( $mediaButtons );
		}
    };

	/**
	 * Init main method:
	 * - Init API hooks.
	 * - Init templates
	 * - Init dialogs.
	 * - Init the Admin Bar button.
	 *
	 * @since 1.9.3
	 */
	self.init = function() {

		self.initHooks()
			.initTemplates()
			.initDialogs()
			.initAdminBarButton();

	};

	self.init();

}

jQuery( function( $ ) {
	Toolset.CRED.shortcodeGUI = new Toolset.CRED.shortcodeManager( $ );
});
