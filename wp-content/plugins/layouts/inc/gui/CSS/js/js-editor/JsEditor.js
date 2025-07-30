var DDLayout = DDLayout || {};

DDLayout.JsEditor = function(main)
{
	var self = this,
		$message_container = jQuery(".js-css-editor-message-container"),
        parent = main,
        $area = jQuery('.js-ddl-js-editor-area'),
        $button = jQuery('.js-layout-js-save'),
        $area_wrap = jQuery('.js-code-js-editor');

        
	self.cell = null;

	self.editor = icl_editor;
	self.codemirror = {};

	self.text_area_id = '';
	self._uid = 0;

	self.js_did_change = false;
	self._id_exists = false;

	self._id_init_val = '';

	self.bookmarks = [];

	self.events_set = false;
	
	self._js_edit_tab_initialized = false;

	self.is_js_enabled = DDLayout_settings.DDL_JS.is_js_enabled;


	self.init = function()
	{
		self._js_edit_tab_initialized = false;

        self.text_area_id = $area.attr('id');
        self._js_edit_tab_initialized = true;
        
        
        jQuery( "#editor_tabs" ).on( "tabsactivate", function( event, ui ) {

            if(  self.text_area_id ){

				self.setCodeMirror();

				if( !self.is_js_enabled )
				{
					$message_container.wpvToolsetMessage({
						text: DDLayout_settings.DDL_JS.strings.css_file_loading_problem,
						stay: true,
						close: true,
						type: 'notice'
					});

					self.set_codemirror_readonly(true);
					return;
				}

                self.handle_save();
				self.makeEditable();

			}
        });
	};

    self.handle_save = function(){
        $button.on('click', function(){
            var js = self.getCodeMirrorInstance().getValue(), $me = jQuery(this);
            DDLayout.JsEditor.manageSpinner.addSpinner( $me );
            parent.setJsString( js ).save(function(){
                DDLayout.JsEditor.manageSpinner.removeSpinner();
                $me.prop( 'disabled', true ).addClass( 'button-secondary' ).removeClass( 'button-primary' );
            });
        });
    };

	var clean_all_up = function()
	{
		self.codemirror[self.text_area_id].off('beforeChange', code_mirror_before_change_callback);
		self.codemirror[self.text_area_id].off('beforeChange', code_mirror_focus_callback);
		
		self._js_edit_tab_initialized = false;
	};

	var code_mirror_focus_callback = function( instance, event){
		self.codemirror[self.text_area_id].options.readOnly = false;
		self._did_check_is_editable = false;
		self.setCodeMirrorBeforeChange();
	};

	var code_mirror_before_change_callback = function( instance, object ){
        if( self._did_check_is_editable === false )
        {
            self.setEditable( object );
        }
	};


    self.setEditable = function( event_object )
    {
        self._did_check_is_editable = true;

        if( self._id_exists === true )
        {
            event_object.cancel();

            $message_container.wpvToolsetMessage({
                text: DDLayout_settings.DDL_JS.strings.id_duplicate,
                stay: true,
                close: true,
                type: 'notice'
            });
        }
        else
        {
            $message_container.wpvToolsetMessage('wpvMessageRemove');
        }
    };



	self.setCodeMirror = function( )
	{
		var layout_js = parent.getJsString();

        $area_wrap.show();

		if( self.codemirror[self.text_area_id] === undefined )
		{
			self.codemirror[self.text_area_id] = self.editor.codemirror( self.text_area_id, true, 'javascript' );
		}

		if( !_.isEmpty( layout_js ) && self.codemirror[self.text_area_id] )
		{
			self.codemirror[self.text_area_id].setValue( layout_js );
		}

        self.codemirror[self.text_area_id].on('change', code_mirror_after_change_callback);
		// make sure we can type even if the instance was set to read only in another window
		self.codemirror[self.text_area_id].options.readOnly = false;
	};

    var code_mirror_after_change_callback = function( instance, object ){
            if( $button.prop( 'disabled') === false && instance.getValue() === parent.getJsString() ){
                $button.prop( 'disabled', true ).addClass( 'button-secondary' ).removeClass( 'button-primary' );

            } else if( $button.prop( 'disabled') === true && instance.getValue() !== parent.getJsString() ) {
                $button.prop( 'disabled', false).addClass( 'button-primary' ).removeClass( 'button-secondary' );
            }
    };

	self.getCodeMirrorInstance = function()
	{
		return self.codemirror[self.text_area_id];
	};

	self.set_codemirror_readonly = function( bool )
	{
		var instance = self.getCodeMirrorInstance();
		instance.options.readOnly = bool;
	};

	self.setCodeMirrorBeforeChange = function()
	{
		self.codemirror[self.text_area_id].on('beforeChange', code_mirror_before_change_callback);
	};

	self.makeEditable = function()
	{
		self.codemirror[self.text_area_id].on('focus', code_mirror_focus_callback);
	};

	self.check_id_exists = function( $me, value )
	{
		if (!self._js_edit_tab_initialized) return true;
		
		if( $me === null || !$me || _.isEmpty( value ) || self._id_init_val === value ) return true;

		var find = WPV_Toolset.Utils.flatten_filter_by_key( parent.get_layout_as_JSON(), {}, false, 'cssId');

		if( value && find.indexOf( value ) !== -1 && self._id_init_val !== value )
		{
			$me.addClass('invalid');

			$message_container.wpvToolsetMessage({
				text: DDLayout_settings.DDL_JS.strings.id_duplicate,
				stay: true,
				close: true,
				type: 'info'
			});

			self._id_exists = true;
			return false;
		}
		else
		{
			self._id_exists = false;
			clean_up_message();
			return true;
		}


		return true;
	};

    var clean_up_message = function ()
    {
        $message_container.wpvToolsetMessage('destroy');
        self.$cssIdEl.removeClass('invalid');
        jQuery('.js-edit-css-class').removeClass('invalid');

    };

	self.isReadable = function()
	{
		return self.codemirror[self.text_area_id].options.readOnly;
	};

	self.setCurrentCell = function( cell )
	{
		self.cell = cell;
	};

	self.init();
};

DDLayout.JsEditor.manageSpinner = {
    spinnerContainer: jQuery('<div class="spinner ajax-loader">'),
    addSpinner: function (target) {
        var self = this;
        jQuery(target).parent().insertAtIndex(0,
            self.spinnerContainer.css({float: 'none', display: 'inline-block', marginTop: '0px'})
        );
    },
    removeSpinner: function () {
        this.spinnerContainer.hide().remove();
    }
};