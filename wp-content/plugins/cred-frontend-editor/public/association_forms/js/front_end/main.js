/* eslint-disable */

var Toolset = Toolset || {};
var ajaxurl = ajaxurl || CredAssociationsFormSettings.ajaxurl;

Toolset.CRED = Toolset.CRED || {};

Toolset.CRED.AssociationForms = Toolset.CRED.AssociationForms || {};

Toolset.CRED.AssociationForms.Select = function( $ ){
    var self = this,
        form_selector = 'form.cred_rel_form_class',
        role_selector = 'select.js-cred_association_select_role',
        $forms = null,
        WIDTH = '300px',
        DATA_TYPE = 'json',
        DELAY = 300,
        TYPE = 'POST',
        CACHE = true,
        INVALID_SEARCH = 'INVALID_SEARCH_STRING',
        MINIMUM_LENGTH = 3;

    self.select2element = null;
    self.otherCurrentValue = null;
    self.otherCurrentText = '';
    self.otherCurrentRole = null;
    self.currentRole = null;
    self.populated = false;
    self.formId = null;
    self.forceTitle = true;

    self.init = function(){
        self.setForms();
        self.setSelect2Elements();
    };

    self.getForms = function(){
        return $forms;
    };

    self.setForms = function(){
        $forms = $( form_selector );
    };

	self.setSelect2ElementsInForm = function( $form ) {
		$form.find( role_selector ).each( function() {
			var $select = $( this ),
				configObject = self.getSelect2ConfigObject( MINIMUM_LENGTH );
			    $select.toolset_select2(configObject)
                  .on('toolset_select2:opening', function ( e ) {
                      self.on_select2_opening( e.currentTarget );
                  })
                  .on('toolset_select2:open', function ( e ) {
                        //console.log( self.otherCurrentValue )
                      self.triggerInput();
                  })
                  .on('toolset_select2:close', function ( e ) {
                      self.on_select2_close( e.currentTarget, configObject );
                  });
		});
	};

    self.setSelect2Elements = function() {
		self.getForms().each( function() {
			self.setSelect2ElementsInForm( $( this ) );
		});
    };

    self.on_select2_opening = function( target ){
        // if the user opens the same select again then use the same values
        if( self.select2element === target ){
            return;
        }
        self.select2element = target;
        var $other = self.getOther( self.select2element );
        self.setOtherCurrentValue( $other );
        self.setOtherCurrentText( $other );
        self.otherCurrentRole = self.getElementRole( $other );
        self.currentRole = self.getElementRole( $( self.select2element ) );
        self.formId = self.getFormId( $( self.select2element ) );
    };

    self.triggerInput = function(){
        $('.toolset_select2-container--open').find('input').val(INVALID_SEARCH).trigger('input').val('');
    };

    self.on_select2_close = function( target, configObject ){
        var value = $( target ).val(),
            $other = self.getOther( target ),
			$form = $other.closest( form_selector );

        this.forceTitle = true;

        if( value ){
            $other.toolset_select2('destroy');
            $other.toolset_select2( self.getSelect2ConfigObject( MINIMUM_LENGTH ) );
        } else {
            $other.toolset_select2('destroy');
            $other.toolset_select2( self.getSelect2ConfigObject( MINIMUM_LENGTH ) );
        }
    };

	self.getForm = function( $element ){
        return $element.closest( form_selector );
    };

    self.getFormId = function( $element ){
        var $form = $element.closest( form_selector ),
            $input = $form.find( 'input[name="cred_form_id"]' );

        return $input.val();
    };

    self.getSelect2ConfigObject = function( minimumInput ) {
        var create = {
            width: WIDTH,
            ajax: {
                url: ajaxurl,
                dataType: DATA_TYPE,
                delay: DELAY,
                data: self.getAjaxdata,
                type: TYPE,
                processResults: self.processAjaxResults,
                cache: CACHE
            },
            placeholder: CredAssociationsFormSettings.strings.role_placeholder,
            templateResult: self.templateResult,
            templateSelection: self.templateSelection,
            language: {
                noResults: self.noResults
            },
            minimumInputLength: typeof minimumInput === 'undefined' ? MINIMUM_LENGTH : minimumInput,
        };

        return create;
    };

    self.getAjaxdata = function ( params ) {
            var otherValue = this.otherCurrentValue,
                forceTitle = this.forceTitle,
                search = params.term;

        if( otherValue && search !== INVALID_SEARCH ){
            return {
                s: search, // search term
                other_value: otherValue,
                other_current_role: self.otherCurrentRole,
                current_role : self.currentRole,
				orderBy: $( self.select2element ).data( 'orderby' ),
				order: $( self.select2element ).data( 'order' ),
				author: $( self.select2element ).data( 'author' ),
                action: CredAssociationsFormSettings.ajax_role_action,
                form_id: self.formId,
                cred_association_form_ajax_role_find_nonce: CredAssociationsFormSettings.ajax_role_nonce,
				current_language: CredAssociationsFormSettings.current_language,
            };
        } else if( otherValue && search === INVALID_SEARCH ){
            return {
                other_value: otherValue,
                other_current_role: self.otherCurrentRole,
                current_role : self.currentRole,
				orderBy: $( self.select2element ).data( 'orderby' ),
				order: $( self.select2element ).data( 'order' ),
				author: $( self.select2element ).data( 'author' ),
                action: CredAssociationsFormSettings.ajax_role_action,
                form_id: self.formId,
                cred_association_form_ajax_role_find_nonce: CredAssociationsFormSettings.ajax_role_nonce,
				current_language: CredAssociationsFormSettings.current_language,
            };
        } else if( forceTitle && search !== INVALID_SEARCH ) {
            return {
                s: search, // search term
                postType: $( self.select2element ).data( 'post_type' ), // only posts from the current role post_type
				orderBy: $( self.select2element ).data( 'orderby' ),
				order: $( self.select2element ).data( 'order' ),
				author: $( self.select2element ).data( 'author' ),
                action: CredAssociationsFormSettings.ajax_suggest_action_title,
                wpnonce: CredAssociationsFormSettings.select2nonce_title,
				current_language: CredAssociationsFormSettings.current_language,
            };
        } else {
            return {
                s: INVALID_SEARCH, // search term
                postType: $( self.select2element ).data( 'post_type' ), // only posts from the current role post_type
				orderBy: $( self.select2element ).data( 'orderby' ),
				order: $( self.select2element ).data( 'order' ),
				author: $( self.select2element ).data( 'author' ),
                action: CredAssociationsFormSettings.ajax_suggest_action_type,
                wpnonce: CredAssociationsFormSettings.select2nonce_type,
				current_language: CredAssociationsFormSettings.current_language,
            };
        }
    };

    self.processAjaxResults = function ( results ) {
        return {
            results: ( results.data ) ? results.data : []
        };
    };

    self.templateResult = function ( results ) {
        if (results.loading) {
            return results.text;
        }
        return results.text;
    };

    self.templateSelection = function( results ) {
        return results.text;
    };

    self.getOther = function( current ) {
        var me = current,
            $me = $( me ),
			$form = self.getForm( $me ),
            other = _.filter( $form.find( role_selector ), function( select ){
                return _.isEqual( select, $me[0] ) === false;
            }),
            $other = other.length ? $( other[0] ) : null;

        return $other;
    };

    self.setOtherCurrentValue = function( $other ){

        if( ! $other ) return;

        var value = $other.find('option:selected').val();

        self.otherCurrentValue = value;

        return self.otherCurrentValue;
    };

    self.setOtherCurrentText = function( $other ){

        if( ! $other ) return;

        var text = $other.find('option:selected').text();

        self.otherCurrentText = text.trim();

        return self.otherCurrentText;

    };

    self.getElementRole = function( $other ){

        if( ! $other ) return null;

        var id = $other.prop('id'),
            lastIndex = id.lastIndexOf('_'),
            role = id.substring( lastIndex + 1 );

        return role;
    };

	self.is_edit_form_request = function( $form ) {
		var isFormRequest = false;
		$form.find( role_selector ).each( function() {
			if ( $( this ).is(':disabled') ) {
				isFormRequest = true;
			}
		});
		return isFormRequest;
	};

	self.noResults = function( params ){
	    var postType = $( this.select2element ).data('post_type'),
            otherText = this.otherCurrentText,
            otherValue = this.otherCurrentValue,
            value = $('.toolset_select2-container--open').find('input').val(),
            replaceOne = CredAssociationsFormSettings.strings.role_no_matches,
            replaceTwo = CredAssociationsFormSettings.strings.role_no_search,
            returnText = '';

        if( otherValue ){
            returnText = replaceOne.replace( '%POST_TYPE_LABEL%', postType ).replace( '%OTHER_POST_TITLE%', otherText );
        } else {
            returnText = replaceTwo.replace( '%SEARCH%', value );
        }

        return returnText;
    };

    _.bindAll( self, 'getAjaxdata', 'processAjaxResults', 'templateResult', 'templateSelection', 'getOther', 'noResults');
};

Toolset.CRED.AssociationForms.Main = function( $ ){
    var self = this,
        $form = null,
        $feedback = null,
		$form_class = '.cred_rel_form_class',
		parsleyConfig = {
			excluded: 'input[type=button], input[type=submit], input[type=reset], input[type=hidden]:not(".js-toolset-media-field-hidden")'
		}


    self.init = function(){
        self.initSelectHandler();
        self.handleIfAjax();
        self.initParsley();
        self.handle_feedback_visibility();
    };

    self.handle_feedback_visibility = function(){
        $message_wrap = $( '.cred-form-feedback-wrap' );

        if( $message_wrap.text() !== '' ){
            $message_wrap.show();
        }
    };

    self.initSelectHandler = function(){
        var handler = new Toolset.CRED.AssociationForms.Select($);
        handler.init();
    };

    self.initParsley = function () {
		window.Parsley
        .addValidator( 'credfilesize', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {
                var file = parsleyInstance.$element[0].files;

                if ( file.length == 0 ) {
                    return true;
                }

                return file.length === 1 && file[0].size <= requirement;

            },
            messages: {
                en: CredAssociationsFormSettings.strings.file_too_big
            }
		})
        .addValidator( 'credfiletype', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {
                var file = parsleyInstance.$element[0].files;

                if ( file.length == 0 ) {
                    return true;
				}

				if ( file.length > 1 ) {
					return false;
				}

				if ( 'file' === requirement ) {
					return true;
				}

				var fileData = file[0];

				return new RegExp('^' + requirement + '\/').test( fileData.type );

			},
			priority: 20,
            messages: {
                en: CredAssociationsFormSettings.strings.file_not_supported
            }
		})
        .addValidator( 'extension', {
            requirementType: 'string',
            validateString: function (value, requirement, parsleyInstance) {
                var file = parsleyInstance.$element[0].files;

                if ( file.length == 0 ) {
                    return true;
				}

				if ( file.length > 1 ) {
					return false;
				}

				var fileData = file[0];

				return new RegExp(".(" + requirement + ")$", "i").test( fileData.name );
			},
			priority: 10,
            messages: {
                en: CredAssociationsFormSettings.strings.file_not_supported
            }
		});

        $( $form_class ).parsley( parsleyConfig );
    };



    self.handleIfAjax = function(){
        var ajax = $('input[name="cred_ajax_submission"]').val();

        $(document).on('click', 'input[name="cred-form-submit"]', function(event){

            var is_valid = $( this ).closest( $form_class ).parsley( parsleyConfig ).validate();
            if( ! is_valid ){
                return false;
            }

            if( ajax === 'true' ){
                event.preventDefault();
                self.handleAjax( $(this) );

            }
        });
    };

    self.handleAjax = function( $button ) {
		$form = $button.closest( $form_class );
		$feedback = $form.find('div.cred-form-feedback-wrap').eq(0);

		if (
			_.has( window, 'tinyMCE' )
			&& _.has( window.tinyMCE, 'triggerSave' )
		) {
			// This will refresh the value of all tinyMCE instances of the page:
			// better too much than too little!
			window.tinyMCE.triggerSave();
		}

		$form.ajaxSubmit({
			url: cred_frontend_i18n.ajaxurl,
			data: {
				action: CredAssociationsFormSettings.ajax_action
				// Nonce is set in the form as a hidden input
			},
			dataType: 'json',
			success: function( response ) {
				if ( response.success ) {
                    self.successHandler( response.data || {} );
                } else {
                    self.failHandler( response.data || {} );
                }
			},
			error: function() {},
			complete: function (response) {}
		});
    };

    self.successHandler = function( responseData ){
        if( responseData && responseData.results && responseData.results.message ){
            $feedback.addClass( responseData.results.ok ? 'alert-success' : 'alert-warning' ).text( responseData.results.message );
            $feedback.show();
            // display the messages and then redirect
            _.delay( function(){
                self.redirect_handler();
            }, 2000 );

            self.handleRoleSelectorsReset( responseData.results );
        }
    };

    self.handleRoleSelectorsReset = function( results ) {
        if( ! _.has( results, 'availability' ) ){
            return;
        }

        var availabilityData = results.availability,
            relationshipSlug = jQuery('#cred_relationship_slug').val();

        _.each( availabilityData, function( data, role, list ){
            if( ! _.has( data, 'success' )|| data.success === false ){
                var selectId = '#cred_association_' + relationshipSlug + '_' + role;

                jQuery( selectId ).val('').trigger('change');
            }
        });
    };

    self.failHandler = function( responseData ) {
        var text = '';
        if( responseData && responseData.error ){
            text = responseData.error;
        } else if( responseData && responseData.results && responseData.results.message ){
            text = responseData.results.message;
        } else {
            text = CredAssociationsFormSettings.strings.fail_text;
        }
        $feedback.text( text );
        $feedback.addClass( 'alert-danger' ).show();
    };

    self.redirect_handler = function(){
        var redirect_url = $form.find('input[name="cred_redirect_url"]').eq(0).val();

        if( window.location.href !== redirect_url ){
            window.location.href = redirect_url;
        }
    };
};



jQuery( function(){
    Toolset.CRED.AssociationForms.mainInstance = new Toolset.CRED.AssociationForms.Main( jQuery );
    Toolset.CRED.AssociationForms.mainInstance.init();
});
