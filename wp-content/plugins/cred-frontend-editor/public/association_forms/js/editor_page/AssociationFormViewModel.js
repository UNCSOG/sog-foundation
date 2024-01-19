/**
 * Association Form View Model
 *
 * @param {Object} model
 * @param {Object} fieldActions "List" of fields
 * @param {Object} formModelData Form data
 * @param {Object} scaffoldDefaultData Fields and data
 * @param {String} shortcodeFormContainer Shorcode container
 */
Toolset.CRED.AssociationFormsEditor.viewmodels.AssociationFormViewModel = function( model, fieldActions, formModelData, scaffoldDefaultData, shortcodeFormContainer ) {

    // private properties
    var self = this, modelPropertyToSubscribableMap = [];
    // Apply the ItemViewModel constructor on this object.
    Toolset.Gui.ItemViewModel.call( self, model.toJSON(), fieldActions );

    var getModelSubObject = function(model, propertyNames) {
        // Accept a single property name as well.
        if(!_.isArray(propertyNames)) {
            propertyNames = [propertyNames];
        }

        if( propertyNames.length === 1) {
            // Same if we have an array with a single property name.
            return {
                lastModelPart: model,
                lastPropertyName: _.first(propertyNames)
            };
        } else {
            // For more than one nesting level, we'll traverse down to the last object.
            return {
                lastModelPart: _.reduce(_.initial(propertyNames), function(modelPart, propertyName) {
                    return modelPart[propertyName];
                }, model),
                lastPropertyName: _.last(propertyNames)
            };
        }
    };

    var createModelProperty = function(subscribableConstructor, model, propertyNames) {
        var modelSubObject = getModelSubObject(model, propertyNames);

        // Actually create the subscribable (observable).
        var currentValue = modelSubObject.lastModelPart[modelSubObject.lastPropertyName];

        // Beware: Sometimes, we may be passing arrays around. We need to make sure that
        // the value in subscribable and subscribable._lastPersistedValue are actually
        // two different objects. That's why JSON.parse(JSON.stringify(currentValue)).
        //
        // Details: https://stackoverflow.com/questions/597588/how-do-you-clone-an-array-of-objects-in-javascript
        if ( ! currentValue ) {
            currentValue = null; // Avoids JSON parse of undefined
        }
        var subscribable = subscribableConstructor(JSON.parse(JSON.stringify(currentValue)));

        // Make sure the subscribable will be synchronized with the model.
        Toolset.ko.synchronize(subscribable, modelSubObject.lastModelPart, modelSubObject.lastPropertyName);

        // Attach another subscribable of the same type to it, which will hold the last
        // value that was persisted to the databse.
        subscribable._lastPersistedValue = subscribableConstructor(JSON.parse(JSON.stringify(currentValue)));

        // When the subscribable changes (and only if it actually changes), update the array of changed properties
        // on this viewmodel. That will allow for sending only relevant changes to be persisted.
        subscribable.subscribe(function(newValue) {
            // We can't just use === because the value may be an array.
            if(!_.isEqual(subscribable._lastPersistedValue(), newValue)) {
                if(!_.contains(self.changedProperties(), propertyNames)) {
                    self.changedProperties.push(propertyNames);
                }
            } else {
                // If the value *became* equal again, we also need to indicate there's no need for saving anymore.
                self.changedProperties.remove(propertyNames);
            }
        });

        // When the last persisted value changes, we mirror the change in GUI (this allows the PHP part
        // to further change the stored data, e.g. generate an unique slug, etc.)
        subscribable._lastPersistedValue.subscribe(function(newPersistedValue) {
            subscribable(JSON.parse(JSON.stringify(newPersistedValue)));
            self.changedProperties.remove(propertyNames);
        });

        // This will be needed for applying the changes after persisting.
        modelPropertyToSubscribableMap.push({
            path: propertyNames,
            subscribable: subscribable
        });

        return subscribable;
    };

    var trimmedStringHasLength = function( string ){
		if( ! _.isString( string ) ){
            return false;
        }
        return string.trim().length > 0
    };

    var stringDontHasScript = function( string ){
        if( ! _.isString( string ) ){
            return false;
        }
        return string.match(/<script.*?>([\s\S]*?)/gmi) === null;
    };

    var stringIsValid = function( string ){
        return trimmedStringHasLength( string ) && stringDontHasScript( string );
    };

    /**
     * Initialize select2 when custom post option is selected
     * Use ajax to search for results
     */
    self.select2Init = function () {
        if( self.redirect_to() === 'custom_post' ){

            self.postSelectorRowVisiblity(true);

            jQuery('#select_post').toolset_select2({
                width: '300px',
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            s: params.term, // search term
                            action: 'toolset_select2_suggest_posts_by_title',
                            wpnonce: Toolset.CRED.AssociationFormsEditor.select2nonce
                        };
                    },
                    type: 'POST',
                    processResults: function ( results ) {
                        return {
                            results: ( results.data ) ? results.data : []
                        };
                    },
                    cache: true
                },
                placeholder: 'Search for a post',
                minimumInputLength: 3,
                templateResult: function (results) {
                    if (results.loading) {
                        return results.text;
                    }
                    return results.text;
                },
                templateSelection: function (results) {
                    return results.text;
                }
            }).on('change',function () {
                self.redirect_custom_post( this.value );

                var selectedPostData = {
                    id : this.value,
                    text : jQuery(this).find("option:selected").text()
                };

                Toolset.CRED.AssociationFormsEditor.selectedPost = selectedPostData;
            });

            // In case if value is saved in DB already, append it and select
            if( Toolset.CRED.AssociationFormsEditor.selectedPost !== null ){
                var selectedOption = new Option( Toolset.CRED.AssociationFormsEditor.selectedPost.text,  Toolset.CRED.AssociationFormsEditor.selectedPost.id, true, true);
                jQuery('#select_post').append(selectedOption).trigger('change');
            }

        }

    };


    // Extend Knockout field to be required
    ko.extenders.required = function(target, overrideMessage) {
        target.hasError = ko.observable();

        function validate(newValue) {
            target.hasError( stringIsValid( newValue ) ? false : true );
        }

        validate(target());

        target.subscribe(validate);

        return target;
    };


    // Add listener for enter key when Wizard is in finish stage
    jQuery(document).on('keypress', function ( event ) {
        if ( event.key === "Enter" && self.can_submit() && self.stepFormContentVisiblity() ) {
            self.onSave();
            return false;
        }
        return true;
    });

    self.handleMessagesGet = function(){
        var $container = jQuery('div#association_form_messages'),
            $inputs = $container.find('input'),
            messages = {};

        $inputs.each(function(i){
            messages[jQuery(this).prop('name')] = jQuery(this).val();
        });

        return messages;
    };

    self.handleMessagesSet = function( messages ){

        if( null === messages ) return;

        _.each(messages, function( value, key, list ){
            if( value && jQuery('input[name="'+key+'"]').is('input') && jQuery('input[name="'+key+'"]').val() !== value ){
                jQuery('input[name="'+key+'"]').val( value );
            }
        });
    };

    /**
     * Fields came from several sources and they need to hava a common identifier attribute.
     * In case of generic fields, the field id is stored in the `field` attribute..
     *
     * @since 2.2
     */
    var normalizeScaffoldDataAttributes = function ( data ) {
        if ( !data ) {
            return false;
        }
        data.fields.forEach( function( field ) {
            if ( !field[ scaffoldDefaultData.scaffold_field_id ] && !!field.field ) {
                field[ scaffoldDefaultData.scaffold_field_id ] = field.field;
            }
        } );
        return data;
    }

    // i18n
    self.i18n = cred_post_form_content_editor_i18n;

    // Data properties
    var modelData = model.toJSON();
    // @refactoring it has to use AdvancedItemViewModel.createModelProperty
    self.form_name = createModelProperty(ko.observable, modelData, 'form_name').extend({required : ""});
    self.relationship = createModelProperty(ko.observable, modelData, 'relationship').extend({required : ""});
    self.form_type = createModelProperty(ko.observable, modelData, 'form_type');
    self.id = createModelProperty(ko.observable, modelData, 'id');
    self.redirect_to = createModelProperty(ko.observable, modelData, 'redirect_to').extend({required : ""});
    self.ajax_submission = createModelProperty(ko.observable, modelData, 'ajax_submission');
    self.disable_comments = createModelProperty(ko.observable, modelData, 'disable_comments');
    self.has_media_button = createModelProperty(ko.observable, modelData, 'has_media_button');
    self.has_toolset_buttons = createModelProperty(ko.observable, modelData, 'has_toolset_buttons');
    self.has_media_manager = createModelProperty(ko.observable, modelData, 'has_media_manager');
    self.slug = createModelProperty(ko.observable, modelData, 'slug');
    self.form_content = createModelProperty(ko.observable, modelData, 'form_content');
    self.post_status = createModelProperty(ko.observable, modelData, 'post_status');
    self.form_style = createModelProperty(ko.observable, modelData, 'form_style');
    self.form_script = createModelProperty(ko.observable, modelData, 'form_script');
    self.isActive = createModelProperty(ko.observable, modelData, 'isActive');
    self.scaffold_data = createModelProperty(ko.observable, modelData, 'scaffold_data');
    self.scaffoldObject = normalizeScaffoldDataAttributes( JSON.parse( self.scaffold_data() ) );
    self.editor_origin = createModelProperty(ko.observable, modelData, 'editor_origin');
    self.changedProperties = ko.observableArray();
    self.mockOnOffSave = ko.observable(true);
    self.messages = createModelProperty(ko.observable, modelData, 'messages');
	self.currentStep = ko.observable("stepFormInstructions");

	// Consider the initial editor content as always dirty
	// so when switching from it to the scaffold editor
	// a warning is displayed, but only if the HTML editor
	// is the initial mode.
	self.initEditorContent = '';

    self.handleMessagesSet( self.messages() );

    self.redirect_custom_post = createModelProperty(ko.observable, modelData, 'redirect_custom_post').extend({required : ""});
    self.redirect_posts_options = ko.observableArray();

    self.can_submit = ko.computed(function() {

        // special case when custom post is selected, we need also check is post really selected
        if( self.redirect_to() === 'custom_post'){
            return stringIsValid( self.form_name() ) && self.relationship() && self.redirect_to() && self.mockOnOffSave() &&  self.redirect_custom_post();
        } else {
            return stringIsValid( self.form_name() ) && self.relationship() && self.redirect_to() && self.mockOnOffSave();
        }
    }, self);

    // title
    self.initialPageTitle = ko.observable();
    self.pageTitle = ko.computed( function(){
        var title = '';
        if( Toolset.CRED.AssociationFormsEditor.action === 'cred_association_form_edit' ){
            title = Toolset.CRED.AssociationFormsEditor.strings.pageEditTitle;
        } else {
            title = Toolset.CRED.AssociationFormsEditor.strings.pageTitle;
        }
        self.initialPageTitle(title);
    } );

    // Hidden rows
    self.postSelectorRowVisiblity = ko.observable( false );

    // title
    self.pageTitle = ko.observable( Toolset.CRED.AssociationFormsEditor.strings.currentPageTitle );

    // messages
    self.displayedMessage = ko.observable( {text: '', type: 'info'} );
    self.messageVisibilityMode = ko.observable('remove');
    self.messageNagClass = ko.pureComputed(function () {
        switch (self.displayedMessage().type) {
            case 'error':
                return 'notice-error';
            case 'warning':
                return 'notice-warning';
            case 'info':
            default:
                return 'notice-success';
        }
    });

    self.removeDisplayedMessage = function () {
        self.messageVisibilityMode('remove');
    };

    // enable or disable wizard
    self.wizardEnabled = ko.observable( Toolset.CRED.AssociationFormsEditor.main.wizardEnabled );

    self.extraEditors = new Toolset.CRED.AssociationFormsEditor.EditorFactory();

    self.initExtraEditor = function( data ){
        var id = data.id,
            mode = data.type;

        return self.extraEditors.setEditor( id, mode, 'cred_filter_meta_html_'+mode+'_'+id, false );
    };

    self.toggleExtraEditorsVisibility = function( object, event ){
        var $me = jQuery( event.currentTarget ),
            $open = $me.next('div.js-cred-assets-editor'),
            $caret = $me.find('i').eq(0);
            data = $me.data();

        if( !_.isObject( data ) ) return;

        if( data.open ){

            $me.data( 'open', false );

            $caret.removeClass('fa-angle-up').addClass('fa-angle-down');

            $open.slideUp(400, function(event){

            });

        } else {

            self.initExtraEditor( data );

            $me.data( 'open', true );

            $caret.removeClass('fa-angle-down').addClass('fa-angle-up');

            $open.slideDown(400, function(event){
                self.extraEditors.getEditor(data.id, data.type).refreshEditor();
            });
        }
    };


    /**
     * Redirect to options
     */

    self.checkRedirectSelection = ko.computed(function () {
        self.select2Init();
    });


    self.redirect_to.subscribe( function( selectedValue ) {
        // display post/page selector when custom_post value is selected
        if( selectedValue === 'custom_post' ){
            self.postSelectorRowVisiblity(true);
        } else {
            self.redirect_custom_post('');
            self.postSelectorRowVisiblity(false);
        }
    });



    // enable or disable wizard
    self.fullFormVisibility = ko.observable( false );
    self.wizardFormVisibility = ko.observable( false );
    self.fullFormActivate = ko.observable( false );
    self.wizardFormActivate = ko.observable( false );

    self.showCorrectTemplate = ko.computed(function(){
        if( Toolset.CRED.AssociationFormsEditor.main.wizardEnabled ){
            self.wizardFormActivate( true );
            self.fullFormVisibility( false );
            self.wizardFormVisibility( true );
        } else {
            self.fullFormActivate( true );
            self.fullFormVisibility( true );
            self.wizardFormVisibility( false );
            _.defer( function() {
                Toolset.hooks.doAction( 'cred_editor_init_top_bar' );
            });
        }
    });

    // Wizard steps
    self.stepFormNameClass = ko.observable();
    self.stepFormSettingsClass = ko.observable();
    self.stepFormContentClass = ko.observable();
    self.stepFormMessagesClass = ko.observable();
    self.stepFormInstructionsClass = ko.observable();

    self.stepFormNameVisiblity = ko.observable();
    self.stepFormSettingsVisiblity = ko.observable();
    self.stepFormContentVisiblity = ko.observable();
    self.stepFormMessagesVisiblity = ko.observable();
    self.stepFormInstructionsVisiblity = ko.observable( true );


    self.moveToStep = function ( step, removeActiveClass ) {
        self.currentStep( step );
        // Hide all steps first
        self.stepFormNameVisiblity(false);
        self.stepFormSettingsVisiblity(false);
        self.stepFormContentVisiblity(false);
        self.stepFormMessagesVisiblity(false);
        self.stepFormInstructionsVisiblity(false);

        // show only necessary step
        setTimeout(function(){

            // make sure that functions dynamically created actually exists
            if(
				typeof self[step+'Visiblity'] === "function" &&
				typeof self[step+'Class'] === "function"
            ){
                self[step+'Visiblity']( true );
                self[step+'Class']('active');

                // remove active class when going back
                if( removeActiveClass !== null && typeof self[removeActiveClass+'Class'] === "function" ){
                    self[removeActiveClass+'Class']('');
                }

            } else {
                // This is only preventing js error,
                // but if everything is fine we should not reach this point
                console.log("Function doesn't exists");
            }

        }, 700);

    };

    self.canGoToSettings = ko.computed(function(){
        if( self.currentStep( ) === 'stepFormName' ){
            return ! stringIsValid( self.form_name() );
        } else if( self.currentStep() === 'stepFormSettings' ){
            return ! self.redirect_to() && ! self.relationship()
        } else if(self.currentStep() === 'stepFormContent' ){
            return true;
        } else {
            return false;
        }

    }, self);

    self.canGoToFinish = ko.computed(function(){
        if( self.currentStep( ) === 'stepFormName' ){
            return stringIsValid( self.form_name() );
        } else if( self.currentStep() === 'stepFormSettings' ){

            // special case when custom post is selected, we need also check is post really selected
            if( self.redirect_to() === 'custom_post'){
                return self.redirect_to() && self.relationship() && self.redirect_custom_post();
            } else {
                return self.redirect_to() && self.relationship();
            }

        } else if( self.currentStep() === 'stepFormInstructions' ){
            return true;
        } else {
            return false;
        }
    }, self);

    // exit wizard
    self.showFullEditor = function ( ) {
        Toolset.CRED.AssociationFormsEditor.main.wizardEnabled = false;
        self.fullFormActivate( true );
        self.fullFormVisibility( true );
        self.wizardFormVisibility( false );
		self.updateExtraEditorsValues();
		self.updateContentFromCodeMirror('cred_association_form_content');
		self.scaffoldObject = self.scaffold.scaffoldToJSON( jQuery( '#association_form_wizard') );
		jQuery( '#association_form_wizard' ).remove();
		Toolset.hooks.doAction( 'cred_editor_exit_wizard_mode' );
		scaffoldIsLoaded = false;
        self.select2Init();
    };


    self.updateContentFromCodeMirror = function ( editor_id ) {
        var cm_editor_content = icl_editor.codemirrorGet( editor_id ).getValue();
        self.form_content( cm_editor_content );
    };

    self.updateExtraEditorsValues = function(){
        var changed = self.extraEditors.someHasChanged();

        if( changed ){
            self.updateExtraEditorsRelatedProperties();
            self.extraEditors.resetEditors();
        }
    };

    self.updateExtraEditorsRelatedProperties = function(){
        _.each( self.extraEditors.getEditors(), function( v, i, l ){
                if( v.slug === 'css' && v.has_changed ){
                    self.form_style( v.getEditorValue() )
                } else if( v.slug === 'js' && v.has_changed ){
                    self.form_script( v.getEditorValue() );
                }
        });
    };

    /**
     * Save / Update association form
     */
    self.onSave = function(){
        var valid = true;
        self.display.isSaving(true);
        self.mockOnOffSave(false);

        if( !valid ) return;

        // Close options to 'save' changes
        jQuery( '.js-cred-editor-scaffold-item-options:visible .js-cred-editor-scaffold-options-close' ).click();
        // update form_content manually since we are using codeMirror
        self.messages( self.handleMessagesGet() );
        self.updateContentFromCodeMirror('cred_association_form_content');
		self.updateExtraEditorsValues();
		self.scaffold_data( JSON.stringify( self.scaffold.scaffoldToJSON() ) );
		if ( ! self.expertModeActive() ) {
			self.insertScaffold();
		}
        var editor = icl_editor.codemirrorGet( 'cred_association_form_content' );
        self.form_content( editor.getValue() );
        model.updateAllProperties( JSON.parse( ko.toJSON( self ) ) );
        model.saveForm( function( updated_model, response, object, args ){

                if( updated_model.get('id') ){
                    self.id( updated_model.get('id') );
                    self.updateBrowserLocation( updated_model.get('id') );
                    self.showDeleteButton();
                }

                if( updated_model.get('slug') ){
                    var slug_for_model = (
                        _.has( response, 'data' )
                        && _.has( response.data, 'results' )
                        && _.has( response.data.results, 'slug' )
                    ) ? response.data.results.slug : updated_model.get('slug');
                    self.slug( slug_for_model );
                    self.handleMessagesSet( { slug: slug_for_model } );
                }

            self.display.isSaving(false);
            self.displayedMessage({text: response.data.results.message, type: 'info'});
            self.messageVisibilityMode('show');
            self.mockOnOffSave(true);

            // switch to full editor after saving from wizard
            if( Toolset.CRED.AssociationFormsEditor.main.wizardEnabled === true ){
                self.showFullEditor();
            }
        }, self );
    };

    /**
     * Use pushState to update browser location to edit page after new form is created
     * @param form_id
     */
    self.updateBrowserLocation = function ( form_id ) {

        var redirectUrl = window.location.origin+window.location.pathname+'?page=cred_relationship_form&action=edit&id='+form_id;

        if( typeof (window.history.pushState) !== 'function' ){
            window.location.href = redirectUrl;
        } else {
            window.history.pushState(null, "", redirectUrl);
            self.pageTitle( Toolset.CRED.AssociationFormsEditor.strings.pageEditTitle );
        }
    };

    self.saveButtonLabel = ko.computed( function() {
        return self.id() ? Toolset.CRED.AssociationFormsEditor.strings.updateForm : Toolset.CRED.AssociationFormsEditor.strings.saveForm;
    } );


    Toolset.hooks.addFilter( 'cred_editor_scaffold_scaffold_field_id_attribute_name', function() {
        return scaffoldDefaultData.scaffold_field_id;
    }, this, 10 );
    /**
     * Scaffold functions
     */
    self.scaffold = new Toolset.CRED.ScaffoldEditor( shortcodeFormContainer );

    self.expertModeActive = ko.observable( false );
    /**
     * Toggle switcher actions
     *
     * @param {Object} koObject ko Object (ignored)
     * @param {Event} event DOM Event
     */
    self.toggleEditorMode = function( koObject, event ) {
        var $target = jQuery( event.target );
        self.expertModeActive( $target.is( ':checked' ) );
        if ( self.expertModeActive() ) {
            self.insertScaffold();
        } else {
            if ( !scaffoldIsLoaded ) {
                jQuery('#relationship').trigger( 'change' );
                self.scaffoldObject = self.scaffold.scaffoldToJSON();
                self.scaffoldObject.fields = self.scaffold.parseHTMLtoScaffold( icl_editor.codemirrorGet( 'cred_association_form_content' ).getValue() );
                self.loadRelationshipData( Toolset.CRED.ScaffoldEditor.setInitialScaffoldItems );
            }
        }
        self.editor_origin( self.expertModeActive() ? 'html' : 'scaffold' );
        if ( self.editor_origin() === 'html' ) {
            var codeMirrorInstance = icl_editor.codemirrorGet( 'cred_association_form_content' );
            self.initEditorContent = codeMirrorInstance.getValue();
        }
    };

    /**
     * Returns if it has relationship selected
     */
    self.hasRelationship = ko.computed( function() {
        return self.relationship() !== '';
    });

    /**
     * Returns if the D&D editor is visible
     */
    self.isScaffoldVisible = ko.computed( function() {
        return self.hasRelationship() && ! self.expertModeActive();
    });

    /**
     * Options is an object and it has to be transformed to an array.
     * Each scaffold options needs a extra info for using in knockout
     *
     * @since 2.2
     */
    var formatScaffoldOptions = function( dataOptions ) {
        var options = [];
        Object.keys( dataOptions ).forEach( function( key ) {
            var itemOption = {
                label: dataOptions[ key ].label,
            }
            itemOption.className = 'cred-editor-scaffold-options-' + key + ' js-cred-editor-scaffold-options-' + key;
            itemOption.checked = !!self.scaffoldObject && self.scaffoldObject.options.includes( key );
            options.push( itemOption );
        })
        return options;
    }
    scaffoldDefaultData.options = formatScaffoldOptions( scaffoldDefaultData.options );
    self.formOptions = ko.observableArray( scaffoldDefaultData.options );
    self.extraFields = ko.observableArray([]);
    self.metaFields = ko.observableArray([]);
    self.mainFields = ko.observableArray([]);

    // Form has options
    self.hasOptions = ko.observable( self.formOptions().length > 0 )

	self.templates = null;

	/**
	 * Lazy load shared and own templates on demand.
	 * Required as Toolset Common templates might not be loaded yet by head.js ready.
	 */
	self.maybeLoadTemplates = function() {
		// Due to a race condition, the filter to get common templates might not have a callback on Toolset.CRED.head.ready.
		// So we delay loading templates to when we really need them.
		if ( null === self.templates ) {
			// WP templates
			self.templates = Toolset.hooks.applyFilters( 'toolset-filter-get-shortcode-gui-templates', {} );
			self.templates.itemOptionsMedia = wp.template( 'cred-editor-scaffold-itemOptions-media' );
			self.templates.itemOptionsHTMLContent = wp.template( 'cred-editor-scaffold-itemOptions-html-content' );
			self.templates.scaffold = {};
			self.templates.scaffold.switchToDD = wp.template( 'cred-editor-scaffold-dialog-switch-to-dd' );
		}
	}

    /**
     * Format fields to be displayed
     *
     * @param {Array} fields
     */
    var formatFields = function( fields, fieldType ) {
        var fieldsArray = []
		self.maybeLoadTemplates();
        Object.keys( fields ).forEach( function( key ) {
            fields[key].fieldType = !! fieldType ? fieldType : fields[key].fieldType;
            if ( ! Object.keys( fields[key].attributes ).length ) {
                fields[key].attributes = { field: key, name: key };
            }
            if ( !fields[key].attributes[ scaffoldDefaultData.scaffold_field_id ] && !! fields[key].attributes.field ) {
                fields[key].attributes[ scaffoldDefaultData.scaffold_field_id ] = fields[key].attributes.field;
            }
            fields[key].formattedAttributes = JSON.stringify( fields[key].attributes );
            fields[key].formattedOptions = JSON.stringify( fields[key].options );
            fields[key].containerInclude = !fields[key].blockedItem && !fields[key].autogeneratedItem;
            fields[key].blockedItem = !!fields[key].blockedItem;
            fields[key].autogeneratedItem = !!fields[key].autogeneratedItem;
            fields[key].requiredItem = !!fields[key].requiredItem;
            fields[key].permanent = !!fields[key].permanent;
            fields[key].removable = !fields[key].requiredItem && fields[key].containerInclude;
            if ( !fields[key].blockedReason ) {
                fields[key].blockedReason = false;
            }
            if ( !fields[key].blockedLink ) {
                fields[key].blockedLink = false;
            }
            fields[key].hasOptions = !! fields[key].options && Object.keys( fields[key].options ).length;
            fields[key].fieldTypeIcon = !! fields[key].icon_class ? 'cred-editor-scaffold-field-type-icon ' + fields[key].icon_class : false;
            fields[key].scaffoldFieldId = fields[key].attributes[ scaffoldDefaultData.scaffold_field_id ]

            fields[key].optionHTML = '';
            _.each( fields[key].options, function( attributeOptions, attributeKey ) {
                attributeOptions = _.defaults( attributeOptions, {
                    shortcode: fields[key].shortcode,
                    attribute: attributeKey,
                    templates: self.templates,
                    defaultValue: '',
                    required: false,
                    hidden: false,
                    placeholder: ''
                } );
                attributeOptions = _.defaults( attributeOptions, { defaultForceValue: attributeOptions.defaultValue } );

                if ( 'media' === fields[key].attributes[ scaffoldDefaultData.scaffold_field_id ] ) {
                    fields[key].optionHTML += self.templates.itemOptionsMedia( {} );
                } else if ( 'html' === fields[key].attributes[ scaffoldDefaultData.scaffold_field_id ] ) {
                    fields[key].optionHTML += self.templates.itemOptionsHTMLContent( {} );
                } else if ( 'group' == attributeOptions.type ) {
                    fields[key].optionHTML += self.templates.attributeGroupWrapper( attributeOptions );
                } else {
                    fields[key].optionHTML += self.templates.attributeWrapper( attributeOptions );
                }
            });

            fieldsArray.push( fields[key] );
        });
        return fieldsArray;
    }


    /**
     * Inserts scaffold content in the HTML editor
     */
    self.insertScaffold = function() {
        var scaffold = self.scaffold.craftScaffoldOutput();
        // Empty previous content
        var editor = icl_editor.codemirrorGet( 'cred_association_form_content' );
        editor.setValue( scaffold )
    }

    var scaffoldIsLoaded = false;
    /**
     * Loads fields data of the selected relationship
     */
    self.loadRelationshipData = function() {
        var callback = arguments.length ? arguments[0] : false;
        /**
         * Format and group fields from data get from Ajax call
         */
        Toolset.hooks.doAction( 'cred-action-maybe-request-and-operate-on-object-fields', function( _objectFields, objectKey ) {
            var objectFields = JSON.parse( JSON.stringify( _objectFields ) );
			var mainFields = objectFields.roles;

			// Include all fields by default on newly created forms
			// but extra fields, like HTML or Media
            var metaFields = Object.assign( {}, objectFields.meta );
            Object.keys( metaFields ).forEach( function( key ) {
                if ( _.has( scaffoldDefaultData.fields.extra, key ) && scaffoldDefaultData.fields.extra[ key ] ) {
                    delete metaFields[ key ];
                }
            });


            // I am not sure why it has to be done, but I suppose it is caused due to modifying node elements directly
            // What happen: if I modify the elements of the scaffold and add wrappers, it seems that knockout stop controlling them
            // and when I update or empty the list using `mainFields()`, fields moved to columns remain in the list
            // If I empty the list using knockout `mainFields([])`, the fields remain there
            // If I remove the children using jQuery, when updating the list using knockout `mainFields( list )` some fields are missing
            // It works only using both actions
            jQuery( '.js-cred-editor-scaffold-item-list:visible' ).children().remove();
            self.mainFields([]);

            self.mainFields(
                formatFields( mainFields )
                    .concat( formatFields( metaFields, 'meta' ), formatFields( scaffoldDefaultData.fields.formElements, 'form-elements' ) )
            );

			// Keep track of the existence of optional fields
			// so the sidebar group can be created
			var metaOptionalFields = Object.assign( {}, objectFields.meta );

            Object.keys( metaOptionalFields ).forEach( function( key ) {
                if ( ! ! metaOptionalFields[ key ].requiredItem ) {
                    delete metaOptionalFields[ key ];
                }
			});


            self.metaFields( formatFields( metaOptionalFields, 'meta' ) );

            self.extraFields( formatFields( scaffoldDefaultData.fields.extra, 'extra' ) );

            if ( typeof callback === 'function' ) {
                callback( self.scaffoldObject );
            }

            if ( ! jQuery( '.cred-editor-scaffold-item-wrapper-row' ).length ) {
                self.scaffold.addFieldItemsWrapperAndRow();
            }

            // jQuery.draggable#refreshPositions is a costly process, because it recalculates droppable zones sizes in every `mouseover` events
            // To avoid it, jQuery.ui.intersect must be adapted to our needs.
            // @link https://github.com/jquery/jquery-ui/blob/master/ui/widgets/droppable.js#L261
            var intersectOriginal = jQuery.ui.intersect;
            jQuery.ui.intersect = function( draggable, droppable, toleranceMode, event ) {
                // I don't know why drop event is fired twice, so I use this flag
                if ( !! jQuery.ui.credStopsDragging ) {
                    return false;
                }
                return document.elementsFromPoint( event.clientX, event.clientY )
                    .filter( function( element ) {
                        var maybeAllowConditional = draggable.element.data( scaffoldDefaultData.scaffold_field_id ) !== 'conditionals'
                            || ( draggable.element.data( scaffoldDefaultData.scaffold_field_id ) === 'conditionals' && !element.classList.contains('cred-editor-scaffold-dropping-zones-conditional') );

                        return maybeAllowConditional && element === droppable.element[0];
                    } )
                    .length;
            }

            self.scaffold.addDraggableItems();

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

			scaffoldIsLoaded = true;
        });
    }


    /**
     * Remove a item form the main list and move it to its block
     *
     * @param {Object} object KO Object
     * @param {Event} event DOM Event
     */
    self.removeField = function( object, event ) {
        event.preventDefault();
        var $control = jQuery( event.target ),
            $container = $control.closest( '.js-cred-editor-scaffold-item-container' ),
            $droppableRow = $container.closest( '.cred-editor-scaffold-item-wrapper-row' );
        $container.find('.cred-editor-scaffold-item-options-toggle.fa-angle-up').click();
        $container.slideUp( 'fast', function() {
            $container.closest( '.cred-editor-scaffold-item-wrapper-item' ).find( '.cred-editor-scaffold-item-wrapper-resizer' ).remove();
            var fieldType = $container.data('fieldtype');
            if ( [ 'basic', 'legacyParent', 'hierarchicalParent' ].includes( fieldType ) ) {
                fieldType = 'post-elements';
            }
            if ( fieldType === 'formElement' ) {
                fieldType = 'form-elements';
            }
            $target = jQuery( '.cred-editor-scaffold-' + fieldType + '-list' );
            if ( ! $container.data( 'permanent' ) ) {
                $target.prepend( $container );
                if ( $target.children().length ) {
                    $target.closest( 'cred-editor-scaffold-' + fieldType + '-container' ).removeClass('hidden');
                }
                $container.slideDown();
            } else {
                $container.remove();
            }
			// Refresh draggable instance
			if ( $container.draggable( 'instance' ) ) {
                $container.draggable( 'destroy' );
            }
            $container.draggable( self.scaffold.draggingOptions );

            self.scaffold.removeEmptyWrappers();
            self.scaffold.rearrangeColumns( $droppableRow );

        });
    }


    /**
     * Switching back to D&D editor may loose changes
     *
     * @param {Object} koObject ko Object (ignored)
     * @param {Event} event DOM Event
     */
    self.maybeCancelSwitchingEditors = function( object, event ) {
        // Close options to 'save' changes
        jQuery( '.js-cred-editor-scaffold-item-options:visible .js-cred-editor-scaffold-options-close' ).click();
        var codeMirrorInstance = icl_editor.codemirrorGet( 'cred_association_form_content' );

        if ( self.expertModeActive() ) {
            event.preventDefault();
            event.stopPropagation();
			self.maybeLoadTemplates();
            if ( self.initEditorContent !== codeMirrorInstance.getValue() ) {
                var dialog = jQuery( self.templates.scaffold.switchToDD() ).dialog( {
                    modal: true,
                    classes: { 'ui-dialog': 'toolset-ui-dialog' },
                    buttons: [
                        {
                            text: self.i18n.no,
                            class: 'button-secondary',
                            click: function() {
                                dialog.dialog('close');
                            }
                        },
                        {
                            text: self.i18n.yes,
                            class: 'button-primary',
                            click: function() {
                                jQuery('[id=cred-editor-expert-mode-switcher]:visible').prop( 'checked', false ).trigger( 'change' );
                                dialog.dialog('close');
                            }
                        }
                    ]
                } );
            } else {
                jQuery('[id=cred-editor-expert-mode-switcher]:visible').prop( 'checked', false ).trigger( 'change' );
            }
        } else {
            jQuery('[id=cred-editor-expert-mode-switcher]:visible').prop( 'checked', true ).trigger( 'change' );
        }
    }

    /**
     * Actions before template is rendered
     */
    self.afterRenderCallback = function() {
        if ( self.editor_origin() === 'html' ) {
            self.expertModeActive( true );
			jQuery( '#cred-editor-expert-mode-switcher' ).prop( 'checked', true );
        } else if ( self.hasRelationship() ) {
            _.defer( function() {
				self.expertModeActive( false );
				// Defer loading the d&d editor until the content template has been rendered
				// Otherwise, it will not be properly initialized
				self.loadRelationshipData( Toolset.CRED.ScaffoldEditor.setInitialScaffoldItems );
			});
        }

        /**
         * Toggle JS and CSS editors below the main content editor.
         * Content is rendered using wp templates so ko is not available
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
                var codeMirrorInstance = icl_editor.isCodeMirror( jQuery( '#' + target ) );
                if ( codeMirrorInstance ) {
                    codeMirrorInstance.refresh();
                    codeMirrorInstance.focus();
                }
            });
        });

        /**
         * Media button handler
         */
        jQuery( document )
            .off( '.scaffold', '.js-shortcode-gui-field-input-media-button' ) // There are 2 forms and it avoids duplicated binding
            .on( 'click.scaffold', '.js-shortcode-gui-field-input-media-button', function() {
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
    }

    /**
     * Make the button to delete the form visible.
     *
     * Note that we use the visibility property because the top bar uses show/hide on scroll
     */
    self.showDeleteButton = function() {
        jQuery( '.js-cred-delete-form' ).css( { 'visibility': 'visible' } );
    };

    /**
     * Delete
     */
    self.onDelete = function(){
        Toolset.CRED.AssociationFormsEditor.main.deleteForm(self);
    };

    self.display = {
        isActive: {
            isStatusMenuExpanded: ko.observable(false),
            lastInput: ko.observable(self.isActive()),
            applyLastInput: function() {
                self.isActive(self.display.isActive.lastInput());
                self.display.isActive.isStatusMenuExpanded(false);
                self.post_status( self.isActive() );
            },
            cancelLastInput: function() {
                self.display.isActive.lastInput(self.isActive());
                self.display.isActive.isStatusMenuExpanded(false);
            }
        },
        isSaving: ko.observable(false)
    };

    /**
     * After adding elements dynamically, it needs to be bound to knockout
     *
     * @since 2.2
     */
    self.applyBindingsToNode = function( htmlNode ) {
        var attributes = JSON.parse( htmlNode.dataset.attributes || {} );
        // A pseudomodel is needed because the new element that needs ko binding  is a child element and the model has to be adapted to it.
        var pseudoModel = Object.assign( { '$parent' : { removeField: self.removeField } }, scaffoldDefaultData.fields[ htmlNode.dataset.fieldtype ][ attributes[ scaffoldDefaultData.scaffold_field_id ] ] )
        ko.applyBindings( pseudoModel, htmlNode );

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
    }
    Toolset.hooks.addAction( 'cred_editor_scaffold_do_knockout_binding', self.applyBindingsToNode, 10, self );


    /**
     * Returns the warning notice HTML
     *
     * @since 2.2
     */
    self.warningNotice = self.i18n.notice;

};

Toolset.ko = Toolset.ko || {

    synchronize: function(subscribable, modelOrCallable, propertyName) {
        if(typeof modelOrCallable === 'function') {
            subscribable.subscribe(modelOrCallable);
        } else {
            var model = modelOrCallable;
            subscribable.subscribe(function(newValue) {
                model[propertyName] = newValue;
            });
        }
    }
};


/**
 * Run code after the fields elements are rendered, needed for setting external libraries to elements inside
 *
 * @since 2.2
 */
ko.bindingHandlers.afterFieldRendered = {
    update: function(element, valueAccessor, allBindings, viewModel, bindingContext) {

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
    }
};
