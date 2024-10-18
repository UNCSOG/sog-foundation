if( typeof DDL_Helper === 'undefined' )
{
	var DDL_Helper = {};
}

DDL_Helper.SanitizeHelper = function( $ )
{
	"use strict";

	var self = this;

	self.sanitize = null;

	if(!Sanitize.Config) {
		Sanitize.Config = {};
	}

	// do a placeholder replacement for this ones
	self.NEEDS_PLACEHOLDER = ['video', 'form', 'audio', 'textarea'];

	self.CLASSNAME_WHITELIST = ["icon-facetime-video", "icon-list-alt", "icon-music", "alignleft", "alignright", "aligncenter", "fa", "fa-video", "fa-music", "fa-list-alt"];

	// configuration object
	Sanitize.Config.CUSTOM = {
		elements: [
			'a', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'col',
			'colgroup', 'dd', 'dl', 'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
			'i', 'img', 'li', 'ol', 'p', 'pre', 'q', 'small', 'strike', 'strong',
			'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u',
			'ul','ol'
		],

		attributes: {
			'a'         : ['title'],
			'blockquote': ['cite'],
			'col'       : [],
			'colgroup'  : [],
			'img'       : ['alt', 'src', 'title', 'class'],
			'ol'        : ['start', 'type'],
			'q'         : ['cite'],
			'table'     : ['summary'],
			'td'        : ['abbr', 'axis', 'colspan', 'rowspan'],
			'th'        : ['abbr', 'axis', 'colspan', 'rowspan', 'scope'],
			'ul'        : ['type']
		},

		protocols: {
			'a'         : {'href': ['ftp', 'http', 'https', 'mailto', Sanitize.RELATIVE]},
			'blockquote': {'cite': ['http', 'https', Sanitize.RELATIVE]},
			'img'       : {'src' : ['http', 'https', Sanitize.RELATIVE]},
			'q'         : {'cite': ['http', 'https', Sanitize.RELATIVE]}
		},

		transformers: [transformer_default,
					   transformer_placeholders,
					   transformer_fix_our_classes,
					   transformer_safe_fix_style_attribute,
			           transformer_safe_fix_src_attribute]
	};

	self.init = function()
	{
		self.sanitize = new Sanitize( Sanitize.Config.CUSTOM );
	};

	self.stringToDom = function( htmlString )
	{
		var fragment = document.createDocumentFragment()
			, dummy = document.createElement('div')
			, append;

		dummy.innerHTML = _.unescape( htmlString );

		while( append = dummy.firstChild )
		{
			fragment.appendChild(append);
		}

		dummy.innerHTML = '';

		dummy.appendChild( self.sanitize.clean_node( fragment ).cloneNode(true) );

		return dummy;
	};

	self.getDummyElementHeight = function ( dummy )
	{
		var height;
		document.body.appendChild(dummy);
		height = $(dummy).height();
		document.body.removeChild(dummy);

		return height;
	};

    self.strip_srcset_attr = function(string){
          if( string.indexOf('srcset') === -1 ){
              return string;
          }

        string = string.replace(/srcset="[^"]*"/g, "");
        string = string.replace(/srcset='[^']*'/g, "");

        return string;
    };


	self.transform_caption_shortcode = function (content) {
		
		// Copied from _do_shcode function in WP editor_plugin.js 
		
		return content.replace(/(?:<p>)?\[(?:wp_)?caption([^\]]+)\]([\s\S]+?)\[\/(?:wp_)?caption\](?:<\/p>)?/g, function(a,b,c){
			var id, cls, w, cap, div_cls, img, trim = tinymce.trim;

			id = b.match(/id=['"]([^'"]*)['"] ?/);
			if ( id )
				b = b.replace(id[0], '');

			cls = b.match(/align=['"]([^'"]*)['"] ?/);
			if ( cls )
				b = b.replace(cls[0], '');

			w = b.match(/width=['"]([0-9]*)['"] ?/);
			if ( w )
				b = b.replace(w[0], '');

			c = trim(c);
			img = c.match(/((?:<a [^>]+>)?<img [^>]+>(?:<\/a>)?)([\s\S]*)/i);

			if ( img && img[2] ) {
				cap = trim( img[2] );
				img = trim( img[1] );
			} else {
				// old captions shortcode style
				cap = trim(b).replace(/caption=['"]/, '').replace(/['"]$/, '');
				img = c;
			}

			id = ( id && id[1] ) ? id[1] : '';
			cls = ( cls && cls[1] ) ? cls[1] : 'alignnone';
			w = ( w && w[1] ) ? w[1] : '';

			if ( !w || !cap )
				return c;

			div_cls = 'mceTemp';
			if ( cls == 'aligncenter' )
				div_cls += ' mceIEcenter';

			w = parseInt( w, 10 ) + 10;

			return '<div class="'+div_cls+'"><dl id="'+id+'" class="wp-caption '+cls+'">' +
					'<dt class="wp-caption-dt">'+img+'</dt><dd class="wp-caption-dd">'+cap+'</dd></dl></div>';
		});
	}

	//transformers callback functions
	function transformer_placeholders( options )
	{
		var opts = options,
		    dummy = null;

		if ( _.indexOf( self.NEEDS_PLACEHOLDER, opts.node_name ) !== -1 ) {

			var className = opts.node_name + '-placeholder';
			var cellContent = '';

			dummy = $('<div class="element-placeholder ' + className + '" />');

			if ( opts.node_name === 'video' ) {
				cellContent = $('<i class="icon-facetime-video fa fa-video-camera" />');
			}
			else if ( opts.node_name === 'form' ) {
				cellContent = $('<i class="fa fa-list-alt icon-list-alt" />');
			}
			else if ( opts.node_name === 'audio' ) {
				cellContent = $('<i class="fa fa-music icon-music" />');
			}

			dummy.append( cellContent ); // Is it a bug or a feature? I can't append <i class="fa fa-list-alt icon-list-alt" />. Class are removed from <i> element. Empty <i> element is appended

			return {
				attr_whitelist: ['class'],
				node: dummy[0],
				whitelist: true,
				whitelist_nodes: ['i']
			};
		}

		return null;
	}

	function transformer_default( options )
	{
		var opts = options,
			computedStyle,
			isComputedStyleSupported = "getComputedStyle" in window;

		if( opts.allowed_elements[opts.node_name] === true  ) return null;

		if ( _.indexOf( self.NEEDS_PLACEHOLDER, opts.node_name ) !== -1  ) return null;


		document.body.appendChild(opts.node);
		computedStyle = ( isComputedStyleSupported ? window.getComputedStyle(opts.node, "") : opts.node.currentStyle ).display;
		document.body.removeChild(opts.node);

		var dummy = null;

		if( opts.allowed_elements[opts.node_name] !== true && computedStyle === 'block' )
		{
			dummy = $('<div class="element-replacement block-element" />');

			dummy.text( $( opts.node ).text() );

			return {
				attr_whitelist:['class'],
				node:dummy[0],
				whitelist:true,
				whitelist_nodes:[]
			};
		}
		else if( opts.allowed_elements[opts.node_name] !== true && computedStyle === 'inline' )
		{
			dummy = $('<span class="element-replacement inline-element" />');

			dummy.text( $( opts.node ).text() );

			return {
				attr_whitelist:['class'],
				node:dummy[0],
				whitelist:true,
				whitelist_nodes:[]
			};
		}
		else
		{
			return null;
		}

		return null;
	}

	function transformer_fix_our_classes( options )
	{
		var opts = options;

		if( self.CLASSNAME_WHITELIST.indexOf( $(opts.node).attr('class') ) === -1 ) return null;

		return{
			attr_whitelist:['class'],
			node:opts.node,
			whitelist:true,
			whitelist_nodes:[]
		}
	}

	function transformer_safe_fix_style_attribute( options )
	{
		var opts = options;

		if( $(opts.node).attr('style') !== undefined && $(opts.node).attr('style').indexOf('text-align') !== -1 )
		{
			return{
				attr_whitelist:['style'],
				node:opts.node,
				whitelist:true,
				whitelist_nodes:[]
			}
		}
		else
		{
			return null;
		}

		return null;
	}

	function transformer_safe_fix_src_attribute( options ){
		var opts = options, undefined;

        if( opts.node_name !== "img" ) return null;

        var clone = $.extend( {}, $(opts.node) );

        var src = typeof clone.prop('src') === 'string' ? clone.prop('src') : undefined;

        if( src !== undefined && WPV_Toolset.Utils.has_shortcode( src ) === false  )
		{
			return{
				attr_whitelist:['src'],
				node:opts.node,
				whitelist:true,
				whitelist_nodes:[]
			}
		}
		else
		{
            $(opts.node).prop('src', DDLayout_settings.DDL_JS.default_img_url);

            return{
                attr_whitelist:['src'],
                node:opts.node,
                whitelist:true,
                whitelist_nodes:[]
            }
		}

		return null;
	}
    
    self.sanitizeOutput = function ( content ) {
        content = DDL_Helper.sanitizeHelper.stringToDom( content );
        return content.innerHTML;
    };

    self.get_field_sanitised_value = function( content ){
        var key = WPV_Toolset.Utils._strip_tags_and_preserve_text( content ),
        field = content.hasOwnProperty( key ) ? content.key : '';
        field = DDL_Helper.sanitizeHelper.strip_srcset_attr( field );
        return field;
    };
	
	self.init();
};

( function( $ ){
	DDL_Helper.sanitizeHelper = new DDL_Helper.SanitizeHelper( $ );
} ( jQuery ) );