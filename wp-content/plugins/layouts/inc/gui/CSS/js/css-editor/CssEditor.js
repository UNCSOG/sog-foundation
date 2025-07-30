var DDLayout = DDLayout || {};

DDLayout.CssEditor = function(main)
{
	var self = this,
		$message_container = jQuery(".js-css-editor-message-container"),
        parent = main,
        $area = jQuery('.js-ddl-css-editor-area'),
        $button = jQuery('.js-layout-css-save'),
        $area_wrap = jQuery('.js-code-editor'),
		layouts_properties = DDLayout_settings.DDL_JS.layouts_css_properties,
		layouts_classes = layouts_properties && layouts_properties.hasOwnProperty('additionalCssClasses')  ? layouts_properties.additionalCssClasses : [],
		layouts_ids = layouts_properties && layouts_properties.hasOwnProperty('cssId')  ? layouts_properties.cssId : [];


	self.cell = null;

	self.editor = icl_editor;
	self.codemirror = {};

	self.text_area_id = '';
	self._uid = 0;

	self.css_did_change = false;
	self._id_exists = false;

	self._id_init_val = '';

	self.bookmarks = [];

	self.events_set = false;
	
	self._css_edit_tab_initialized = false;

	self.is_css_enabled = DDLayout_settings.DDL_JS.is_css_enabled;


	self.init = function()
	{
		self._css_edit_tab_initialized = false;

			self.text_area_id = $area.attr('id');

			self._css_edit_tab_initialized = true;

			if(  self.text_area_id )
			{
				self.setCodeMirror();

				self.codemirrorManageCustomTokens();

				if( !self.is_css_enabled )
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

				makeAutoComplete();

				self.makeEditable();

				if( self.bookmarks[self._uid] === undefined )  {
					self.bookmarks[self._uid] = new DDLayout.CodemirrorBookmarks( jQuery('.js-codemirror-bookmarks'), self.getCodeMirrorInstance(), 'bookmarks_of_'+self._uid );
				}
			}

	};

    self.handle_save = function(){
        $button.on('click', function(){
            var css = self.getCodeMirrorInstance().getValue(), $me = jQuery(this);
            DDLayout.CssEditor.manageSpinner.addSpinner( $me );
            var css = css.replace(/<script[^>]*>(?:(?!<\/script>)[^])*<\/script>/g, "");
            parent.setCssString( css ).save(function(){
                DDLayout.CssEditor.manageSpinner.removeSpinner();
                $me.prop( 'disabled', true ).addClass( 'button-secondary' ).removeClass( 'button-primary' );
            });
            self.getCodeMirrorInstance().setValue( css );
        });
    };

    self.clean_up_display_options_screen = function( event, dialog )
    {
        if( typeof self.bookmarks[self._uid] !== 'undefined' )
        {
            self.bookmarks[self._uid].removeAllMarkers( false );
        }
    };

	self.codemirrorSetCustomHints = function( strings )
	{
		var orig = CodeMirror.hint.css;
		CodeMirror.hint.css = function(cm) {
			var inner = orig(cm) || {from: cm.getCursor(), to: cm.getCursor(), list: []},
			    cur = cm.getCursor(),
				token = cm.getTokenAt(cur),
			    word = token.string, start = token.start, end = token.end;

			_.each( strings, function( string ){
				if( string.lastIndexOf(word, 0) === 0 ) {
					inner.list.push( string );
					inner.from = CodeMirror.Pos(cur.line, start);
					inner.to = CodeMirror.Pos(cur.line, end);
					inner.list = _.unique( inner.list );
				}
			});

			return inner;
		};
	};

	self.codemirrorManageCustomTokens = function(  )
	{
		var content = self.getCodeMirrorInstance().getValue(),
			classes = self.getCssClassesArray(content, true),
			ids = self.getCssIdArrayFromEditor( content),
			tokens = _.union( _.map(classes, function(v){ return '.'+v; }), _.map(ids, function(v){ return '#'+v; }) );

		self.codemirrorSetCustomHints( tokens );
	};

	var makeAutoComplete = function()
	{
		var operation = self.codemirror[self.text_area_id].doc.history.lastOp ? self.codemirror[self.text_area_id].doc.history.lastOp : 0;

		self.codemirror[self.text_area_id].on("blur", function(cm) {

			self.codemirror[self.text_area_id].off('beforeChange', code_mirror_before_change_callback);

			if( operation < cm.doc.history.lastOp )
			{
				operation = cm.doc.history.lastOp;
			}
		});

	};
	
	var clean_all_up = function()
	{
		self.codemirror[self.text_area_id].off('beforeChange', code_mirror_before_change_callback);
		self.codemirror[self.text_area_id].off('beforeChange', code_mirror_focus_callback);
		
		self._css_edit_tab_initialized = false;
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
		var layout_css = parent.getCssString();

        $area_wrap.show();

		if( self.codemirror[self.text_area_id] === undefined )
		{
			self.codemirror[self.text_area_id] = self.editor.codemirror( self.text_area_id, true, 'css' );
			self.codemirror[self.text_area_id].setOption("extraKeys", {"Ctrl-Space": "autocomplete"})
		}

		if( !_.isEmpty( layout_css ) && self.codemirror[self.text_area_id] )
		{
			self.codemirror[self.text_area_id].setValue( layout_css );
		}

        self.codemirror[self.text_area_id].on('change', code_mirror_after_change_callback);
		// make sure we can type even if the instance was set to read only in another window
		self.codemirror[self.text_area_id].options.readOnly = false;
	};

    var code_mirror_after_change_callback = function( instance, object ){
            if( $button.prop( 'disabled') === false && instance.getValue() === parent.getCssString() ){
                $button.prop( 'disabled', true ).addClass( 'button-secondary' ).removeClass( 'button-primary' );

            } else if( $button.prop( 'disabled') === true && instance.getValue() !== parent.getCssString() ) {
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
		if (!self._css_edit_tab_initialized) return true;
		
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

	self.getCssClassesArray = function( $css_string )
	{
		var regexp = /\.(.*?){/gi,
			match = $css_string.match(regexp),
			res = [],
		//TODO: complete with a detailed list of characters to trim
			replace_list = ['.', ',', /\s+/];

		// clean
		_.each(match, function( value, key, list ){
			value = value.replace('{', '');
			// let's see if they cascade
			_.each(value.split(' '), function( v, k, l )
			{

				v = v.replace(/\s+/, "");

				if( v && v.indexOf('.') === 0 )
				{
					_.each( replace_list, function( item ){
						v = v.replace( item, '' );
					});

					res.push( v );
				}

			});

		});

        res = _.union(res, layouts_classes);

		return _.uniq( res.sort(), true );
	};

	self.getCssIdArrayFromEditor = function( $css_string )
	{
		var regexp = /\#(.*?){/gi,
			match = $css_string.match(regexp),
			res = [],
		//TODO: complete with a detailed list of characters to trim
			replace_list = ['#', '.', ',', /\s+/];

		// clean
		_.each(match, function( value, key, list ){
			value = value.replace('{', '');
			// let's see if they cascade
			_.each(value.split(' '), function( v, k, l )
			{
				v = v.replace(/\s+/, "");

				if( v && v.indexOf('#') === 0 )
				{
					_.each( replace_list, function( item ){
						v = v.replace( item, '' );
					});

					res.push( v );
				}

			});

		});

        res = _.union(res, layouts_ids);

		return _.unique( res.sort(), true );
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

DDLayout.CodemirrorBookmarks = function ($el, cm, name) {
	var self = this,
		$ = jQuery.noConflict(),
		colors = {},
		step = null;

	self.markers = null;
	self.linesNumber = null;
	self.cm = cm;
	self.name = name;

	$el.data( 'elements', [] );

	self.getPositions = function( classes, id )
	{
		var classesRawString = classes,
			idString = id;

		return _.extend( {}, self.getClassesPositions( classesRawString), self.getIdsPositions(idString) );
	};

	self.getSearchObject = function( string )
	{
		return self.cm.getSearchCursor(string, false, false);
	};

	self.getClassesPositions = function( string )
	{
		if( _.isEmpty(string) ) return {};

		var classes = string,
			stringsArray = classes.split(','),
			ret = {};

		_.each(stringsArray, function( value, key, list ){
			var v = '.'+value, cursor = self.getSearchObject( v );
			ret[v] = [];
			while ( cursor.findNext() ) {
				ret[v].push( cursor.pos.to.line );
			}
		});
		return ret;
	};

	self.getIdsPositions = function( string )
	{
		if( _.isEmpty(string) ) return {};

		var v = '#'+string,
			cursor = self.getSearchObject( v),
			ret = {};

		ret[v] = [];

		while ( cursor.findNext() ) {
			ret[v].push( cursor.pos.to.line );
		}

		return ret;
	};

	self.getRandomColor = function() {
		var randomColor = new RColor(); // see: dd_layouts_common_script.js
		var color = randomColor.get(true);
		return color;
	};

	self.addMarker = function (name, pos) {

		// Update 'self.markers' object if element doesn't exist
		if ( self.markers[name] === undefined ) {
			self.markers[name] = [];
			colors[name] = self.getRandomColor(); // Update colors object
		}

		if ($.inArray(pos, self.markers[name]) === -1) {
			self.markers[name].push(pos);
		}

		var $li = $('<li />');

		$li
			.data({
				'name': name,
				'pos': pos
			})
			.attr('data-tooltip', name + ' : ' + ( parseInt(pos, 10) + 1 ) ) // this is for CSS tooltips. It's intentional to use .attr() not .data();
			.text(' ')
			.css({
				'top': step * pos + 'px',
				'background': colors[name]
			})
			.appendTo($el);

		$el
			.data('elements')
			.push($li);

		self.bindEvents($li);

	};

	self.addMarkers = function( name, type )
	{
		var markers = null;

		if ( _.keys( self.markers ).indexOf( name ) === -1  )
		{
			var no_prefixed_name = name.slice(1);

			switch( type )
			{
				case 'id':
					markers = self.getIdsPositions( no_prefixed_name );
				break;
				case 'class':
					markers = self.getClassesPositions( no_prefixed_name );
				break;
			}

		}

		if ( markers !== null ) {
			_.each( markers[name], function( v, k, l ){

				self.addMarker( name, v );
			});

		}

	};

	self.removeMarker = function (name, pos, preserve_object) {

		if (name !== undefined && pos !== undefined) {

			$.each($el.data('elements'), function () {

				if (( $(this).data('pos') === pos ) && ( $(this).data('name') === name )) {

					$(this).remove();
					var elIndex = $.inArray(pos, self.markers[name]); // index of the property

					if( preserve_object !== true && self.markers.hasOwnProperty(name) )
					{
						self.markers[name].splice(elIndex, 1); // remove property from 'self.markers' object

						if (self.markers[name].length === 0) { // delete key if array is empty
							delete self.markers[name];
						}
					}
				}

			});

		}

	};

	self.removeMarkers = function (attr, preserve_object) { // should be string or number

		if (typeof(attr) === 'string' && self.markers[attr] !== undefined) { // If string is given then removeMarkers() will remove all occurance of given class or ID

			self.linesNumbers = self.markers[attr].slice(0); // Create a copy of original self.markers[attr], because original array will be modified during $.each loop!

			$.each(self.linesNumbers, function (key, val) {
				self.removeMarker(attr, val, preserve_object);
			});

		}
		else if (typeof(attr) === 'number') { // If number is given then removeMarkers() will remove all elements from given line number

			$.each(self.markers, function (name, lines) {

				var pos = $.inArray(attr, lines); // Index of marker line number in self.linesNumbers array
				if (pos !== -1) {
					self.removeMarker(name, self.markers[name][pos], preserve_object);
				}

			});

		}

	};

	self.removeAllMarkers = function( preserve_object ) {

		try{
			if( self.markers !== null )
			$.each(self.markers, function (name) {
				self.removeMarkers(name, preserve_object );
			});
		} catch( e )
		{
			console.log(  e.message );
		}


	};

	self.bindEvents = function ($li) {

		$li.on('click', function () {

			$.each($el.data('elements'), function () {
				$(this).removeClass('active');
			});

			$(this)
				.addClass('active')
				.data('active', true);

			self.scrollToLine( $(this).data('pos') );

		});

	};

	self.scrollToLine = function ( line_str ) {

		var line = Number( line_str );

		if ( line && !isNaN( line ) ) {
			self.cm.setCursor( line, 0 );
			self.cm.setSelection({ line:line, ch:0 },{ line:line+1, ch:0 });
			self.cm.focus();
		}

	};

	self.drawBookmarks = function ( classes, id ) {

		// clean dirt from other instances
		$el.empty();

		// clean my dirt and array as well
		self.removeAllMarkers(  );

		self.markers = _.extend( {}, self.markers, self.getPositions( classes, id ) );

		self.linesNumber = self.cm.doc.lineCount();

		if (self.linesNumber !== null || typeof(self.linesNumber) === 'number') {

			step = $el.height() / self.linesNumber; // Each step = 1% of $el height

			if ( !$.isEmptyObject(self.markers) ) {

				$.each(self.markers, function (name) {

					// Generate random color only when drawBookmark() is called for the first time. It prevent colors from being generating agani while re-drawing markers (for example when new line is added to the codemirror editor)
					if ( colors[name] === undefined ) {
						colors[name] = self.getRandomColor();
					}

					$.each(self.markers[name], function (key, pos) { // Draw all self.markers
						self.addMarker(name, pos);
					});

				});
			}

		}
		else {
			console.log('self.linesNumber not set');
		}

		//console.log(self.markers);

	};
};

DDLayout.CssEditor.manageSpinner = {
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