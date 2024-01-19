(function(window, $, undefined){
var thisExportName='cred_utils';

if (window[thisExportName]) return;
    
/*
*   Common functions
*
*/
    
    // php functions
    function stripslashes(str)
    {
        // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // +   improved by: Ates Goral (http://magnetiq.com)
        // +      fixed by: Mick@el
        // +   improved by: marrtins
        // +   bugfixed by: Onno Marsman
        // +   improved by: rezna
        // +   input by: Rick Waldron
        // +   reimplemented by: Brett Zamir (http://brett-zamir.me)
        // +   input by: Brant Messenger (http://www.brantmessenger.com/)
        // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
        // *     example 1: stripslashes('Kevin\'s code');
        // *     returns 1: "Kevin's code"
        // *     example 2: stripslashes('Kevin\\\'s code');
        // *     returns 2: "Kevin\'s code"
        return (str + '').replace(/\\(.?)/g, function (s, n1) {
            switch (n1) {
            case '\\':
                return '\\';
            case '0':
                return '\u0000';
            case '':
                return '';
            default:
                return n1;
            }
        });
    };

    function isNumber(n)
    {
        return !isNaN(parseFloat(n)) && isFinite(n);
    };
    
    // aux functions
    function isTinyMce($textarea)
    {
        var editor, ed=$textarea.attr('id');
        if (ed && ed.charAt(0)=='#') ed=ed.substring(1);
        
        // if tinyMCE
        if (
            window.tinyMCE && ed &&
            null != (editor=window.tinyMCE.get(ed)) && 
            false == editor.isHidden()
        )
            return editor;
        return false;
    };
    
    function isCodeMirror($textarea)
    {
        var textareaNext = null;
        if($textarea[0] !== undefined){
            textareaNext = $textarea[0].nextSibling;
        }
        // if CodeMirror
        if (
            textareaNext && $textarea.is('textarea')&&
            //$(textareaNext).is('textarea')&&
            textareaNext.CodeMirror &&
            $textarea[0]==textareaNext.CodeMirror.getTextArea()
        )
            return textareaNext.CodeMirror;
        return false;
    };
    
    function getContent($area)
    {
        if (!$area) $area=$('#content');
        //var tinymce=aux.isTinyMce($area);
        var codemirror=isCodeMirror($area);
        if (codemirror)
            return codemirror.getValue();
        return $area.val();
    };
    	
    function InsertAtCursor(myField, myValue1, myValue2)
    {
        var $myField=myField;
        var tinymce=isTinyMce($myField);
        var codemirror=isCodeMirror($myField);
        
        // if CodeMirror
        if (codemirror)
        {
            codemirror.focus()
			
            if (!codemirror.somethingSelected())
            {
                // set at current cursor
                var current_cursor=codemirror.getCursor(true);
                codemirror.setSelection(current_cursor, current_cursor);
            }
            if (typeof(myValue2)!='undefined' && myValue2) // wrap
                codemirror.replaceSelection(myValue1 + codemirror.getSelection() + myValue2);
            else
                codemirror.replaceSelection(myValue1);
            codemirror.setSelection(codemirror.getCursor(false),codemirror.getCursor(false));    				
        }
        // else if tinyMCE
        else if (tinymce)
        {
            tinymce.focus();
            if (typeof(myValue2)!='undefined' && myValue2) // wrap
                tinymce.execCommand("mceReplaceContent",false, myValue1 + tinymce.selection.getContent({format : 'raw'}) + myValue2);
            else
                tinymce.execCommand("mceInsertContent",false, myValue1);
        }
        // else other text fields
        else
        {
            myField=$myField[0]; //$(myField)[0];
            myField.focus();
            if (document.selection)
            {
                sel = document.selection.createRange();
                if (typeof(myValue2)!='undefined' && myValue2) // wrap
                    sel.text = myValue1 + sel.text + myValue2;
                else
                    sel.text = myValue1;
            }
            else if ((myField.selectionStart != null) && (myField.selectionStart != undefined)/* == 0 || myField.selectionStart == '0'*/)
            {
                var startPos = parseInt(myField.selectionStart);
                var endPos = parseInt(myField.selectionEnd);
                if (typeof(myValue2)!='undefined' && myValue2) // wrap
                {
                    var sel = myField.value.substring(startPos, endPos);
                    myField.value = myField.value.substring(0, startPos) + myValue1 + sel + myValue2 +
                                myField.value.substring(endPos, myField.value.length);
                }
                else
                    myField.value = myField.value.substring(0, startPos) + myValue1 +
                                myField.value.substring(endPos, myField.value.length);
            }
            else
            {
                if (typeof(myValue2)!='undefined' && myValue2) // wrap
                    myField.value += myValue1 + myValue2;
                else
                    myField.value += myValue1;
            }
        }
        $myField.trigger('paste');
    };
    
    function insertContent(content)
    {
        InsertAtCursor($('#'+wpActiveEditor), content);
    }
    
	function qt_TagButton_prototype_callback(element, canvas, ed) 
    {
		var t = this, startPos, endPos, cursorPos, scrollTop, v = canvas.value, l, r, i, sel, endTag = v ? t.tagEnd : '';

		if ( document.selection ) { // IE
			canvas.focus();
			sel = document.selection.createRange();
			if ( sel.text.length > 0 ) {
				if ( !t.tagEnd )
					sel.text = sel.text + t.tagStart;
				else
					sel.text = t.tagStart + sel.text + endTag;
			} else {
				if ( !t.tagEnd ) {
					sel.text = t.tagStart;
				} else if ( t.isOpen(ed) === false ) {
					sel.text = t.tagStart;
					t.openTag(element, ed);
				} else {
					sel.text = endTag;
					t.closeTag(element, ed);
				}
			}
			canvas.focus();
		} else if ( canvas.selectionStart || canvas.selectionStart == '0' ) { // FF, WebKit, Opera
			startPos = canvas.selectionStart;
			endPos = canvas.selectionEnd;
			cursorPos = endPos;
			scrollTop = canvas.scrollTop;
			l = v.substring(0, startPos); // left of the selection
			r = v.substring(endPos, v.length); // right of the selection
			i = v.substring(startPos, endPos); // inside the selection
			if ( startPos != endPos ) {
				if ( !t.tagEnd ) {
					canvas.value = l + i + t.tagStart + r; // insert self closing tags after the selection
					cursorPos += t.tagStart.length;
				} else {
					canvas.value = l + t.tagStart + i + endTag + r;
					cursorPos += t.tagStart.length + endTag.length;
				}
			} else {
				if ( !t.tagEnd ) {
					canvas.value = l + t.tagStart + r;
					cursorPos = startPos + t.tagStart.length;
				} else if ( t.isOpen(ed) === false ) {
					canvas.value = l + t.tagStart + r;
					t.openTag(element, ed);
					cursorPos = startPos + t.tagStart.length;
				} else {
					canvas.value = l + endTag + r;
					cursorPos = startPos + endTag.length;
					t.closeTag(element, ed);
				}
			}

			canvas.focus();
			canvas.selectionStart = cursorPos;
			canvas.selectionEnd = cursorPos;
			canvas.scrollTop = scrollTop;
		} else { // other browsers?
			if ( !endTag ) {
				canvas.value += t.tagStart;
			} else if ( t.isOpen(ed) !== false ) {
				canvas.value += t.tagStart;
				t.openTag(element, ed);
			} else {
				canvas.value += endTag;
				t.closeTag(element, ed);
			}
			canvas.focus();
		}
	};
    
    var id = 0;
    
    function getId(prefix)
    {
        if ('undefined'==typeof(prefix))
            prefix='id'
        return prefix + '_' + (++id);
    };
    
    function addEditorSwitchButton(title, editor_id)
    {
        if ('undefined'==typeof(editor_id))
            editor_id='content';
            
        var cont=$('#wp-' + editor_id + '-editor-tools'),
        media=$('#wp-' + editor_id + '-media-buttons'),
        button=$('<a class="wp-switch-editor">' + title + '</a>');
        
        if (media.length)
            button.insertBefore(media);
        else
            button.appendTo(cont);
        
        return button;
    };
    
    function swapEl($item, callback)
    {
        var props = { position: 'absolute', visibility: 'hidden', display: 'block' },
            //dim = { width:0, height:0, innerWidth: 0, innerHeight: 0,outerWidth: 0,outerHeight: 0 },
            $hiddenParents = $item.parents().add($item).not(':visible');
            //includeMargin = (!includeMargin)? false : includeMargin;

        var oldProps = [];
        $hiddenParents.each(function() {
            var old = {};

            for ( var name in props ) {
                old[ name ] = this.style[ name ];
                this.style[ name ] = props[ name ];
            }

            oldProps.push(old);
        });

        if (callback)
            callback.call($item);

        $hiddenParents.each(function(i) {
            var old = oldProps[i];
            for ( var name in props ) {
                this.style[ name ] = old[ name ];
            }
        });
    };
    
    function waitUntilElement(selector, callable, deep)
    {
        if (!callable) return;
        
        deep = deep || 50;
        var el=$(selector);
        if (el.length)
        {
            callable.call(el);
        }
        else if (deep>0)
        {
            setTimeout(function(){waitUntilElement(selector, callable, deep-1);}, 60);
        }
    };
    
    function doDelayed(callable, now)
    {
        if (!callable) return;
        
        if (!now)
            setTimeout(function(){
                callable();
            },50);
        else
        {
            callable();
        }
    };
    
    var _events = { };
    
    function dispatch(event)
    {
        var result=null;
        if (_events[event] && _events[event].length)
        {
            var args=Array.prototype.slice.call(arguments, 1);
            for (var i=0; i<_events[event].length; i++)
            {
                // call callbacks
                result = _events[event][i].apply(this, args);
            }
        }
        return result;
    };
    
    function attach(event, callback, unique)
    {
        if (callback===undefined) return false;
        
        if (unique===undefined)  unique=true;
        
        var events=event.split(/\s+/);
        for (var e=0, l=events.length; e<l; e++)
        {
            if (!_events[events[e]] || !_events[events[e]].length) _events[events[e]]=[];
            if (unique && $.inArray(callback, _events[events[e]])>-1) continue;
            _events[events[e]].push(callback);
        }
        return true;
    };
    
    function detach(event, callback)
    {
        if (!_events[event]) return false;
        if (callback===undefined)
        {
            _events[event]=[];  // reset
            return false;
        }
        
        var pos=$.inArray(callback, _events[event]);
        if (pos>-1)
        {
            _events[event].splice(pos,1);
            return true;
        }
    };
    
    function route(context, base_url, path, params, raw)
    {
        //var base_url=settings.ajaxurl;
        if (undefined===raw) raw=true;
            
        if (path && ''!=path && raw)
        {
            var parts=path.replace(/^\//,'').replace('?','&').split('/');
            path='';
            for (var ii=0; ii<parts.length; ii++)
            {
                if (0==ii)
                    path+='?action='+context+'_ajax_'+parts[ii];                
                else if (1==ii)
                    path+='&_do_='+parts[ii];
            }
        }
        if (path && ''!=path && params)
        {
            for (var p in params)
            {
                if (params.hasOwnProperty(p) && params[p])
                {
                    if (path.indexOf('?')>-1)
                        path+='&'+encodeURIComponent(p)+'='+encodeURIComponent(params[p]);
                    else
                        path+='?'+encodeURIComponent(p)+'='+encodeURIComponent(params[p]);
                }
            }
        }
        return (raw)?base_url+path:path;        
    };
        
    /*
    *
    *   utility functions, adapted from WP
    *
    */
    var Cookies = {
    // The following functions are from Cookie.js class in TinyMCE, Moxiecode, used under LGPL.

        each : function(obj, cb, scope) 
        {
            var n, l;

            if ( !obj )
                return 0;

            scope = scope || obj;

            if ( typeof(obj.length) != 'undefined' ) {
                for ( n = 0, l = obj.length; n < l; n++ ) {
                    if ( cb.call(scope, obj[n], n, obj) === false )
                        return 0;
                }
            } else {
                for ( n in obj ) {
                    if ( obj.hasOwnProperty(n) ) {
                        if ( cb.call(scope, obj[n], n, obj) === false ) {
                            return 0;
                        }
                    }
                }
            }
            return 1;
        },

        /**
         * Get a multi-values cookie.
         * Returns a JS object with the name: 'value' pairs.
         */
        getHash : function(name) 
        {
            var all = Cookies.get(name), ret;

            if ( all ) {
                Cookies.each( all.split('&'), function(pair) {
                    pair = pair.split('=');
                    ret = ret || {};
                    ret[pair[0]] = pair[1];
                });
            }
            return ret;
        },

        /**
         * Set a multi-values cookie.
         *
         * 'values_obj' is the JS object that is stored. It is encoded as URI in wpCookies.set().
         */
        setHash : function(name, values_obj, expires, path, domain, secure) 
        {
            var str = '';

            Cookies.each(values_obj, function(val, key) {
                str += (!str ? '' : '&') + key + '=' + val;
            });

            Cookies.set(name, str, expires, path, domain, secure);
        },

        /**
         * Get a cookie.
         */
        get : function(name) 
        {
            var cookie = document.cookie, e, p = name + "=", b;

            if ( !cookie )
                return;

            b = cookie.indexOf("; " + p);

            if ( b == -1 ) {
                b = cookie.indexOf(p);

                if ( b != 0 )
                    return null;

            } else {
                b += 2;
            }

            e = cookie.indexOf(";", b);

            if ( e == -1 )
                e = cookie.length;

            return decodeURIComponent( cookie.substring(b + p.length, e) );
        },

        /**
         * Set a cookie.
         *
         * The 'expires' arg can be either a JS Date() object set to the expiration date (back-compat)
         * or the number of seconds until expiration
         */
        set : function(name, value, expires, path, domain, secure) 
        {
            var d = new Date();

            if ( typeof(expires) == 'object' && expires.toGMTString ) {
                expires = expires.toGMTString();
            } else if ( parseInt(expires, 10) ) {
                d.setTime( d.getTime() + ( parseInt(expires, 10) * 1000 ) ); // time must be in miliseconds
                expires = d.toGMTString();
            } else {
                expires = '';
            }

            document.cookie = name + "=" + encodeURIComponent(value) +
                ((expires) ? "; expires=" + expires : "") +
                ((path) ? "; path=" + path : "") +
                ((domain) ? "; domain=" + domain : "") +
                ((secure) ? "; secure" : "");
        },

        /**
         * Remove a cookie.
         *
         * This is done by setting it to an empty value and setting the expiration time in the past.
         */
        remove : function(name, path) 
        {
            Cookies.set(name, '', -1000, path);
        }
    };

    // Returns the value as string. Second arg or empty string is returned when value is not set.
    function getUserSetting( context, name, def ) 
    {
        var obj = getAllUserSettings(context);

        if ( obj.hasOwnProperty(name) )
            return obj[name];

        if ( typeof def != 'undefined' )
            return def;

        return '';
    }

    // Both name and value must be only ASCII letters, numbers or underscore
    // and the shorter, the better (cookies can store maximum 4KB). Not suitable to store text.
    function setUserSetting( context, name, value, _del ) 
    {
        if ( 'object' !== typeof userSettings )
            return false;
        
        var cookieName=context + '-' + userSettings.uid,
            contextTime=context + '-time',
            cookieTimeName=contextTime + '-' + userSettings.uid;

        var cookie = cookieName, all = Cookies.getHash(cookie) || {}, path = userSettings.url,
        n = name.toString().replace(/[^A-Za-z0-9_]/, ''), v = value.toString().replace(/[^A-Za-z0-9_]/, '');

        if ( _del ) {
            delete all[n];
        } else {
            all[n] = v;
        }

        Cookies.setHash(cookie, all, 31536000, path);
        Cookies.set(cookieTimeName, userSettings.time, 31536000, path);

        return name;
    }

    function deleteUserSetting( context, name ) 
    {
        return setUserSetting( context, name, '', 1 );
    };

    // Returns all settings as js object.
    function getAllUserSettings(context) 
    {
        if ( 'object' !== typeof userSettings )
            return {};
        
        var cookieName=context + '-' + userSettings.uid,
            contextTime=context + '-time',
            cookieTimeName=contextTime + '-' + userSettings.uid;
        
        return Cookies.getHash(cookieName) || {};
    };
    
    function getUserSettingAjax(context, uri, name, def)
    {
    };
    
    function setUserSettingAjax(context, uri, name, value, _del)
    {
    };
    
    
    // export it
    window[thisExportName] = {
        
        // php functions
        stripslashes : stripslashes,
        isNumber : isNumber,
        
        // aux functions
        doDelayed : doDelayed,
        swapEl : swapEl,
        waitUntilElement : waitUntilElement,
        
        // custom events
        dispatch : dispatch,
        attach : attach,
        detach : detach,
        
        route : route,
        
        // cookie settings
        getUserSetting : getUserSetting,
        setUserSetting : setUserSetting,
        deleteUserSetting : deleteUserSetting,
        getUserSettingAjax : getUserSettingAjax,
        setUserSettingAjax : setUserSettingAjax,
        
        getId : getId,
        addEditorSwitchButton : addEditorSwitchButton,
        isTinyMce : isTinyMce,
        isCodeMirror : isCodeMirror,
        getContent : getContent,
        InsertAtCursor : InsertAtCursor,
        wrapOrPaste : function(before, after) { InsertAtCursor($('#content'), before, after); },
        insert : function(text) { InsertAtCursor($('#content'), text); }
    };

})(window, jQuery);