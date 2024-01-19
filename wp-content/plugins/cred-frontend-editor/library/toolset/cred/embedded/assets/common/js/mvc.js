CRED = {};
CRED.suggest_cache = false;

/**
 * Framework heavily inspired on, or base for, https://jsperf.com/js-mvc-frameworks
 */

(function (window, $, undefined) {
    var thisExportName = 'cred_mvc';

    if (window[thisExportName])
        return;

    /*
     *
     *   A micro-MV* (MVVM) framework for complex (form) screens
     *   ( depends on jQuery )
     *
     *   Uses concepts from various MV* frameworks like:
     *       knockoutjs,
     *       agility.js,
     *       angular.js
     *       and backbone.js
     *
     */


    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //
    // jQuery extensions
    //
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    /*  Modified version of
     *	jquery.suggest 1.1b - 2007-08-06
     * Patched by Mark Jaquith with Alexander Dick's "multiple items" patch to allow for auto-suggesting of more than one tag before submitting
     * See: http://www.vulgarisoip.com/2007/06/29/jquerysuggest-an-alternative-jquery-based-autocomplete-library/#comment-7228
     *
     *	Uses code and techniques from following libraries:
     *	1. http://www.dyve.net/jquery/?autocomplete
     *	2. http://dev.jquery.com/browser/trunk/plugins/interface/iautocompleter.js
     *
     *	All the new stuff written by Peter Vulgaris (www.vulgarisoip.com)
     *	Feel free to do whatever you want with this file
     *
     */
    $._mvvm__suggest = function (input, options) {
        var $input, $results, timeout, prevLength, cache, cacheSize;

        $input = $(input).attr("autocomplete", "off");
        $results = $(document.createElement("ul"));

        timeout = false;		// hold timeout ID for suggestion results to appear
        prevLength = 0;			// last recorded length of $input.val()
        cache = [];				// cache MRU list
        cacheSize = 0;			// size of cache in chars (bytes?)

        $results.addClass(options.resultsClass).appendTo('body');

        resetPosition();
        $(window).on( 'load resize', resetPosition );

        $input.blur(function () {
            setTimeout(function () {
                $results.hide()
            }, 100);
        });

        $input.keydown(processKey);

        // main methods here
        function resetPosition() {
            // requires jquery.dimension plugin
            var offset = $input.offset();
            $results.css({
                top: (offset.top + input.offsetHeight) + 'px',
                left: offset.left + 'px'
            });
        }

        function processKey(e) {
            // handling up/down/escape requires results to be visible
            // handling enter/tab requires that AND a result to be selected
            if (
                (/27$|38$|40$/.test(e.keyCode) && $results.is(':visible')) ||
                (/^13$|^9$/.test(e.keyCode) && getCurrentResult())
            ) {
                if (e.preventDefault)
                    e.preventDefault();
                if (e.stopPropagation)
                    e.stopPropagation();
                e.cancelBubble = true;
                e.returnValue = false;

                switch (e.keyCode) {
                    case 38: // up
                        prevResult();
                        break;
                    case 40: // down
                        nextResult();
                        break;
                    case 9:  // tab
                    case 13: // return
                        selectCurrentResult();
                        break;
                    case 27: //	escape
                        $results.hide();
                        break;
                }

            } else if ($input.val().length != prevLength) {
                if (timeout)
                    clearTimeout(timeout);
                timeout = setTimeout(suggest, options.delay);
                prevLength = $input.val().length;
            }
        }

        function suggest() {
            var param = (options.param) ? options.param : 'q', _data = {},
                q = $input.val().trim(), multipleSepPos, items;

            if (options.multiple) {
                multipleSepPos = q.lastIndexOf(options.multipleSep);
                if (multipleSepPos != -1)
					q = q.substr(multipleSepPos + options.multipleSep.length);
					q = q.trim();
            }
            if (q.length >= options.minchars) {
                cached = checkCache(q);
                if (cached)
                    displayItems(cached['items']);
                else {
                    if (options.onStart)
                        options.onStart.call(this, $input);

                    _data[param] = q;

                    $.post(options.source, _data, function (r) {
                        $results.hide();
                        items = parseRequest(r, q);
                        if (items) {
                            displayItems(items);
                            addToCache(q, items, items.length);
                        }
                        if (options.onComplete)
                            options.onComplete.call(this, $input);
                    });
                }

            } else
                $results.hide();
        }

        function checkCache(q) {
            if (CRED.suggest_cache === false)
                return false;
            var i, l;
            for (i = 0, l = cache.length; i < l; i++) {
                if (cache[i]['q'] == q) {
                    cache.unshift(cache.splice(i, 1)[0]);
                    return cache[0];
                }
            }
            return false;
        }

        function addToCache(q, items, size) {
            var cached;
            while (cache.length && (cacheSize + size > options.maxCacheSize)) {
                cached = cache.pop();
                cacheSize -= cached['size'];
            }

            cache.push({
                q: q,
                size: size,
                items: items
            });
            cacheSize += size;
        }

        function displayItems(items) {
            if (!items || !items.length) {
                $results.hide();
                return;
            }
            var html = '', i, l;
            resetPosition(); // when the form moves after the page has loaded

            for (i = 0, l = items.length; i < l; i++)
                html += '<li data-val="' + items[i].value + '">' + items[i].label + '</li>';

            $results.html(html).show();
            $results
                .children('li')
                .mouseover(function () {
                    $results.children('li').removeClass(options.selectClass);
                    $(this).addClass(options.selectClass);
                })
                .click(function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectCurrentResult();
                });
        }

        function parseRequest(r, q) {
            return JSON.parse(r);
        }

        function getCurrentResult() {
            var $currentResult;
            if (!$results.is(':visible'))
                return false;

            $currentResult = $results.children('li.' + options.selectClass);

            if (!$currentResult.length)
                $currentResult = false;

            return $currentResult;
        }

        function selectCurrentResult() {
            $currentResult = getCurrentResult();

            if ($currentResult) {
                if (options.multiple) {
                    if ($input.val().indexOf(options.multipleSep) != -1)
                        $currentVal = $input.val().substr(0, ($input.val().lastIndexOf(options.multipleSep) + options.multipleSep.length));
                    else
                        $currentVal = "";
                    $input.val($currentVal + $currentResult.attr('data-val') + options.multipleSep);
                    $input.focus();
                    $input.trigger('change');
                } else {
                    $input.val($currentResult.attr('data-val'));
                    $input.focus();
                    $input.trigger('change');
                }
                $results.hide();

                if (options.onSelect)
                    options.onSelect.apply($input[0]);
            }

        }

        function nextResult() {
            $currentResult = getCurrentResult();

            if ($currentResult)
                $currentResult.removeClass(options.selectClass).next().addClass(options.selectClass);
            else
                $results.children('li:first-child').addClass(options.selectClass);
        }

        function prevResult() {
            var $currentResult = getCurrentResult();

            if ($currentResult)
                $currentResult.removeClass(options.selectClass).prev().addClass(options.selectClass);
            else
                $results.children('li:last-child').addClass(options.selectClass);
        }
    }

    $.fn._mvvm__suggest = function (source, options) {
        if (!source)
            return;

        options = options || {};
        options.multiple = options.multiple || false;
        options.multipleSep = options.multipleSep || ", ";
        options.source = source;
        options.delay = options.delay || 100;
        options.resultsClass = options.resultsClass || 'ac_results';
        options.selectClass = options.selectClass || 'ac_over';
        options.matchClass = options.matchClass || 'ac_match';
        options.minchars = options.minchars || 2;
        options.delimiter = options.delimiter || '\n';
        options.onSelect = options.onSelect || false;
        options.onStart = options.onStart || false;
        options.onComplete = options.onComplete || false;
        options.maxCacheSize = options.maxCacheSize || 3000;

        this.each(function () {
            new $._mvvm__suggest(this, options);
        });

        return this;
    };


    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //
    // utility functions
    //
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    // taken fom agility.js , taken from jquery.js, and so on..
    // Modified from Douglas Crockford's Object.create()
    // The condition below ensures we override other manual implementations (most are not adequate)
    if (!Object.create || Object.create.toString().search(/native code/i) < 0) {
        Object.create = function (proto) {
            var C = function () {
            };
            // simply setting C.prototype = proto somehow messes with constructor, so getPrototypeOf wouldn't work in IE
            $.extend(C.prototype, proto);
            //return new C();
            return C;
        };
    }

    function isNumber(n) {
        return !isNaN(n - 0) && n !== null && n !== "" && n !== false;
    }

    // http://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
    var _specials = [
            // order matters for these
            "-"
            , "["
            , "]"
            // order doesn't matter for any of these
            , "/"
            , "{"
            , "}"
            , "("
            , ")"
            , "*"
            , "+"
            , "?"
            , "."
            , "\\"
            , "^"
            , "$"
            , "|"
        ],
        // I choose to escape every character with '\'
        // even though only some strictly require it when inside of []
        _escapeRegexp = new RegExp('[' + _specials.join('\\') + ']', 'g');

    function escapeRegExp(str) {
        // Referring to the table here:
        // https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/regexp
        // these characters should be escaped
        // \ ^ $ * + ? . ( ) | { } [ ]
        // These characters only have special meaning inside of brackets
        // they do not need to be escaped, but they MAY be escaped
        // without any adverse effects (to the best of my knowledge and casual testing)
        // : ! , =
        // my test "~!@#$%^&*(){}[]`/=?+\|-_;:'\",<.>".match(/[\#]/g)

        return str.replace(_escapeRegexp, "\\$&");
    }

    function isArrayIndex(n) {
        if (!isNaN(n - 0) && n !== null && n !== "" && n !== false) // is numeric
        {
            n = +n;  // make number if not already
            if (// and is integer
            (0 === n % 1) &&
            n >= 0
            )
                return true;
        }
        return false
    }

    function isObjectOrArray(o) {
        return 'object' == typeof (o) && null !== o;
    }

    // http://stackoverflow.com/questions/12017693/why-use-object-prototype-hasownproperty-callmyobj-prop-instead-of-myobj-hasow
    function hasOwn(o, p) {
        return o && p && Object.prototype.hasOwnProperty.call(o, p);
    }

    function count(o) {
        var cnt = 0;

        if (isObjectOrArray(o)) {
            for (var p in o) {
                if (hasOwn(o, p) && undefined !== o[p])
                    cnt++;
            }
        } else if (undefined !== o) {
            cnt = 1; //  is scalar value, set count to 1
        }
        return cnt;
    }

    function arrayFlip(a, useVal) {
        var _a = {};

        if (typeof (useVal) != 'undefined') {
            $(a).each(function (k, v) {
                _a[v] = useVal;
            });
        } else {
            $(a).each(function (k, v) {
                _a[v] = k;
            });
        }
        return _a;
    }

    function hasSubArray(a, b) {
        // is subarray of itself
        if (a === b)
            return true;
        // larger array is not part of smaller
        if (b.length > a.length)
            return false;

        var count = 0, inA = arrayFlip(a, true);

        $(b).each(function (k, v) {
            if (inA[v])
                count++;
        });

        return (count == b.length);
    }

    // http://stackoverflow.com/questions/6491463/accessing-nested-javascript-objects-with-string-key
    function parseKey(key) {
        if (!key.substring)
            return undefined;
        key = key.replace(/\[(\w*)\]/g, '.$1') // convert indexes to properties
        //       .replace(/\//g, '.')         // convert slashes to properties, allow parsing paths like /key1/key2/0/key3 etc..
            .replace(/^\./, '');           // strip a leading dot
        return key.split('.'); // get parts as path
    }

    // Adapted from Douglas Crockford's JSON.parse()
    function parseObj(s) {
        var cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
            escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
            meta = {// table of character substitutions
                '\b': '\\b',
                '\t': '\\t',
                '\n': '\\n',
                '\f': '\\f',
                '\r': '\\r',
                '"': '\\"',
                '\\': '\\\\'
            }, o; // output object

        s = String(s);
        cx.lastIndex = 0;
        if (cx.test(s)) {
            s = s.replace(cx, function (a) {
                return '\\u' +
                    ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
            });
        }

        // test before eval, that it is good as plain obj notation
        // we look to see that the remaining characters are only whitespace or ']' or
        // ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval
        if (/^[\],:{}\s]*$/.test(s
                // replace the JSON backslash pairs with '@' (a non-JSON character)
                    .replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@')
                    // allow single quotes, replace single quotes with double quotes (this is not strict JSON, it is plain JS Object Notation)
                    .replace(/'/g, '"')
                    // allow unquoted keys ie: foo_123 : ..etc.. (this is not strict JSON, it is plain JS Object Notation)
                    .replace(/[a-zA-Z_]+[a-zA-Z0-9_]*\s*:/g, ']:')
                    // allow string/number concatenation (used to escape keys for replacement form JS)(this is not strict JSON, it is plain JS Object Notation)
                    .replace(/\+/g, '')
                    // replace all simple value tokens with ']' characters
                    .replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']')
                    // delete all open brackets that follow a colon or comma or that begin the text
                    .replace(/(?:^|:|,)(?:\s*\[)+/g, '')
            )
        ) {
            o = eval('(' + s + ')');
            return o;
        }

        return false;
    }

    function removePrefix(pre, s) {
        var regex = new RegExp('^' + pre + '([\\.|\\[])'); // strict mode (after prefix, a key follows)
        return s.replace(regex, '$1');
    }

    function parseCond(c, options) {
        var m,
            rxp = /^([_\.\-\d\w\[\]]+?)((:[\w\d\_]+)?\s*( in | has | eq | ne | ge | le | gt | lt |=|>|<|<>|>=|<=)\s*([,_\.\-\d\w\[\]]+))?$/;
        var results = {
            name: false,
            key: false,
            val: false,
            func: false,
            arrayVal: false,
            isArray: false,
            op: false
        };
        if (c && c.substring && (m = c.match(rxp))) {
            results.name = m[1];
            results.key = (options && options.prefix)
                ? removePrefix(options.prefix, m[1])
                : m[1];
            if (m[2]) // has :func op val also
            {
                if (m[3]) // has function
                {
                    results.func = m[3].substring(1);
                }
                results.op = m[4].trim();
                results.val = m[5];
                // value is array of values
                if (
                    ']' == m[5].charAt(m[5].length - 1) &&
                    '[' == m[5].charAt(0)
                ) {
                    results.isArray = true;
                    results.arrayVal = m[5].substring(0, m[5].length - 1).substring(1).split(',');
                }
            }
        }
        return results;
    }

    function hasAtt($el, att) {
        if (undefined !== $el.attr(att))
            return true;
        return false;
    }

    function extendObj(obj, key, val) {
        if ('' == key) {
            obj = val;
            return obj;
        }

        var path = parseKey(key), p, o = obj;

        if (!path)
            return obj;

        while (path && path.length) {
            p = path.shift();
            if (isObjectOrArray(o) && hasOwn(o, p) && path.length > 0) {
                var pnext = path[0];
                if (!isObjectOrArray(o[p])) {
                    // removes previous "scalar" value
                    if (isArrayIndex(pnext)) // add as array
                    {
                        o[p] = [];
                    } else // add as object
                    {
                        o[p] = {};
                    }
                }
            } else if (path.length > 0) // construct
            {
                var pnext = path[0];
                if (!isObjectOrArray(o)) {
                    if (isArrayIndex(p)) // add as array
                    {
                        o = [];
                    } else // add as object
                    {
                        o = {};
                    }
                }
                if (isArrayIndex(pnext)) // add as array
                {
                    o[p] = [];
                } else// add as object
                {
                    o[p] = {};
                }
            }

            if (isObjectOrArray(val) && hasOwn(val, p)) {
                val = val[p];
            }
            if (path.length > 0) {
                o = o[p];
            }
        }
        o[p] = val;

        return obj;
    }

    function parseForm(dom, prefix) {
        var o = {};
        dom = $(dom);

        dom.find('input[name], textarea[name], select[name]').each(function () {
            var $el = $(this), name = $el.attr('name'), key = (prefix) ? removePrefix(prefix, name) : name,
                val = $el.val();

            if ($el.is(':checkbox') || $el.is(':radio')) {
                val = dom.find('[name="' + name + '"]').filter(':checked').val();
            }
            o = extendObj(o, key, val);
        });
        return o;
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //
    // Main Framework
    //
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    // Use a jQuery object as simple PubSub
    var pubSub = $({}),
        // prefix used for events, play nice with others ;)
        context = "_o_O_o_",
        _items = {
            'model': {},
            'view': {}
        },
        _ = {};


    // the logic behind this:
    //
    // when, what, who, why, how
    //
    // when -> event  (default on model change)
    // what -> action  (default update value)
    // who, why, how -> additional conditions/data

    // TODO: add some default validation (maybe use jQuery validate plugin) [DONE]
    // TODO: add embelishments lke ajax autosuggest, etc.. [DONE]
    // TODO: add some basic routing/ajax/persist/update (just an example how to integrate that easily)
    // TODO: add deep linking (pushState, eg pjax) integration/support

    /*! Modified from
     * jQuery Validation Plugin 1.12.0pre
     *
     * http://bassistance.de/jquery-plugins/jquery-plugin-validation/
     * http://docs.jquery.com/Plugins/Validation
     *
     * Copyright 2013 Jorn Zaefferer
     * Released under the MIT license:
     *   http://www.opensource.org/licenses/mit-license.php
     */
    var _validatorUtils = {
        isCheckable: function ($el) {
            return (/radio|checkbox/i).test($el[0].type);
        },
        findByName: function (name, dom) {
            dom = dom || $('body');
            return dom.find("[name='" + name + "']");
        },
        getLength: function ($el, v) {
            var el = $el[0];
            switch (el.nodeName.toLowerCase()) {
                case "select":
                    return $("option:selected", $el).length;
                case "input":
                    if (_validatorUtils.isCheckable($el)) {
                        return _validatorUtils.findByName(el.name).filter(":checked").length;
                    }
            }
            return v.length;
        },
        depend: function ($el, p) {
            return _validatorUtils.dependTypes[typeof p] ? _validatorUtils.dependTypes[typeof p]($el, p) : true;
        },
        dependTypes: {
            "boolean": function ($el, p) {
                return p;
            },
            "string": function ($el, p) {
                return !!$(p, $el[0].form).length;
            },
            "function": function ($el, p) {
                return p($el);
            }
        },
        getValue: function ($el) {
            var type = $el.attr("type"),
                val = $el.val();

            if (type === "radio" || type === "checkbox") {
                return $("input[name='" + $el.attr("name") + "']:checked").val();
            }

            if (typeof val === "string") {
                return val.replace(/\r/g, "");
            }
            return val;
        },
        isOptional: function ($el) {
            var val = _validatorUtils.getValue($el);
            return !_validators.required.call(this, $el, val) && "dependency-mismatch";
        }
    };

    // main _
    _ = {
        // expose utils here, with nice names
        utils: {
            isObjectOrArray: isObjectOrArray,
            isArrayIndex: isArrayIndex,
            hasOwn: hasOwn,
            removePrefix: removePrefix,
            extend: extendObj,
            validator: _validatorUtils,
            parse: {
                'key': parseKey,
                'object': parseObj,
                'form': parseForm,
                'condition': parseCond,
            }
        },
        Builder: {
            // allow plugins to extend/alter the default prototypes and actions

            Model: {
                Functions: {},
                Prototype: {
                    // allow chaining, return this;

                    // public (kind of)
                    init: function (pub) {
                        if (pub)
                            this.trigger('init', {target: this});
                        return this;
                    },
                    dispose: function (pub) {
                        this._data = null;
                        if (pub)
                            this.trigger('destroy', {target: this});
                    },
                    trigger: function (evt, o) {
                        o = o || {target: this, key: '', value: {}};
                        _.EventManager.publish(_.Event(this, evt), o);
                        return this;
                    },
                    func: function (name, callback) {
                        if (typeof callback === "function") {
                            this._function[name] = callback;
                            return this;
                        }
                        return this._functions[name];
                    },
                    data: function (d) {
                        if (d) {
                            this._data = d;
                            return this;
                        }
                        return this._data;
                    },
                    eval: function (cond) {
                        var undefined, key, originalkey, value;
                        var r = parseCond(cond, {prefix: this.id});

                        if (false !== r.name) {
                            if (false !== r.op) {
                                key = r.key;
                                originalkey = key;
                                key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                                value = this.get(key);

                                if (false !== r.func) {
                                    // allow user-defined functions
                                    if (this._functions && this._functions[r.func]) {
                                        value = this._functions[r.func].call(this, r, cond);
                                    } else {
                                        // built-in functions
                                        switch (r.func) {
                                            case 'count':
                                                value = this.count(key);
                                                break;
                                        }
                                    }
                                }

                                switch (r.op) {
                                    case 'has':
                                        if (r.isArray && isObjectOrArray(value))
                                            return hasSubArray(value, r.arrayVal)
                                        else if (!r.isArray && isObjectOrArray(value))
                                            return ($.inArray(r.val, value) > -1)
                                        else
                                            return false;
                                    case 'in':
                                        if (r.isArray && isObjectOrArray(value))
                                            return hasSubArray(r.arrayVal, value)
                                        if (r.isArray && !isObjectOrArray(value))
                                            return ($.inArray(value, r.arrayVal) > -1)
                                        else
                                            return false;
                                    case 'eq':
                                    case '=':
                                        if (isNumber(value)) {
                                            value = +value;
                                            r.val = +r.val;
                                        }
                                        return (value == r.val);
                                    case 'ne':
                                    case '<>':
                                        if (isNumber(value)) {
                                            value = +value;
                                            r.val = +r.val;
                                        }
                                        return (value != r.val);
                                    case 'gt':
                                    case '>':
                                        if (isNumber(value)) {
                                            value = +value;
                                            r.val = +r.val;
                                        }
                                        return (value > r.val);
                                    case 'lt':
                                    case '<':
                                        if (isNumber(value)) {
                                            value = +value;
                                            r.val = +r.val;
                                        }
                                        return (value < r.val);
                                    case 'ge':
                                    case '>=':
                                        if (isNumber(value)) {
                                            value = +value;
                                            r.val = +r.val;
                                        }
                                        return (value >= r.val);
                                    case 'le':
                                    case '<=':
                                        if (isNumber(value)) {
                                            value = +value;
                                            r.val = +r.val;
                                        }
                                        return (value <= r.val);
                                }
                            }
                        }
                        return undefined;
                    },
                    merge: function (key, data, pub) {
                        var originalkey = key;
                        key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                        this._data = extendObj(this._data, key, data);

                        if (pub)
                            this.trigger('change', {target: this, key: originalkey, value: data});

                        return this;
                    },
                    mergeDom: function (key, $dom, pub) {
                        return this.merge(key, parseForm($dom, this.id), pub);
                    },
                    count: function (key, val) {
                        var originalkey = key;
                        key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                        var o = (key) ? this.get(key) : val;
                        return count(o);
                    },
                    last: function (key) {
                        var o = this.get(key);
                        var last = -1, undefined;
                        if (Array.isArray(o)) {
                            for (var n in o) {
                                if (hasOwn(o, n) && isArrayIndex(n)) {
                                    n = +n;
                                    if (last < n)
                                        last = n;
                                }
                            }
                            return last;
                        }
                        return undefined;
                    },
                    next: function (key) {
                        var next = this.last(key);
                        if (undefined !== next)
                            next++;

                        return next || 0;
                    },
                    has: function (key) {
                        var originalkey = key;
                        key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                        var path = parseKey(key), p, o = this._data;
                        if (!path)
                            return false;

                        while (path.length) {
                            p = path.shift();
                            if (isObjectOrArray(o) && hasOwn(o, p) /*(p in o)*/)
                                o = o[p];
                            else
                                return false;
                        }
                        return true;
                    },
                    get: function (key) {
                        var originalkey = key;
                        key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                        var path = parseKey(key), p, o = this._data, undefined;
                        if (!path)
                            return undefined;

                        while (path.length) {
                            p = path.shift();
                            if (isObjectOrArray(o) && hasOwn(o, p) /*(p in o)*/)
                                o = o[p];
                            else
                                return undefined;
                        }

                        return o;
                    },
                    // it can add last node also if not there
                    set: function (key, val, pub) {
                        var originalkey = key;
                        key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                        var path = parseKey(key), p, o = this._data, undefined;
                        if (!path)
                            return this;

                        while (path.length) {

                            p = path.shift();

                            if (isObjectOrArray(o) && hasOwn(o, p)/*(p in o)*/ && path.length > 0)
                                o = o[p];
                            else if (path.length > 0)
                            // cannot add intermediate values
                                return this;
                        }
                        if (o !== undefined && isObjectOrArray(o))
                        // modify or add final node here
                            o[p] = val;

                        if (pub)
                            this.trigger('change', {target: this, key: originalkey, value: val});

                        return this;
                    },
                    // it can override both intermediate and last values also
                    add: function (key, val, pub) {
                        var originalkey = key;
                        key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                        var path = parseKey(key), p, pnext, o = this._data, undefined;
                        if (!path)
                            return this;

                        while (path.length) {
                            p = path.shift();
                            if (isObjectOrArray(o) && hasOwn(o, p)/*(p in o)*/ && path.length > 0) {
                                pnext = path[0];
                                if (!isObjectOrArray(o[p])) {
                                    // removes previous "scalar" value
                                    if (isArrayIndex(pnext) || '' == pnext) // add as array
                                        o[p] = [];
                                    else // add as object
                                        o[p] = {};
                                }
                                o = o[p];
                            } else if (path.length > 0) // construct
                            {
                                pnext = path[0];
                                if (!isObjectOrArray(o)) {
                                    if (isArrayIndex(p) || '' == p) // add as array
                                        o = [];
                                    else // add as object
                                        o = {};
                                }
                                if (isArrayIndex(pnext) || '' == pnext) // add as array
                                    o[p] = [];
                                else// add as object
                                    o[p] = {};
                                o = o[p];
                            }
                        }
                        o[p] = val;

                        if (pub) {
                            this.trigger('add', {target: this, key: originalkey, value: val});
                            this.trigger('change', {target: this, key: originalkey, value: val});
                        }
                        return this;
                    },
                    del: function (key, pub) {
                        var originalkey = key;
                        key = key.replace(/\[\]$/, ''); // allow arrays without specific indexes, eg checkboxes
                        var path = parseKey(key), p, o = this._data, undefined, val;
                        if (!path)
                            return this;
                        while (path.length) {
                            p = path.shift();
                            if (isObjectOrArray(o) && hasOwn(o, p)/*(p in o)*/ && path.length > 0)
                                o = o[p];
                            else if (path.length > 0)
                            // do not remove intermediate keys/values
                                return this;
                        }
                        val = o[p];
                        delete o[p]; // not re-arrange indexes

                        if (pub) {
                            this.trigger('remove', {target: this, key: originalkey, value: val});
                            this.trigger('change', {target: this, key: originalkey, value: val});
                        }
                        return this;
                    }
                }
            },
            View: {
                _CACHE_SIZE: 2000,
                _REFRESH_INTERVAL: 2000, // refresh cache every 2 secs if needed

                Actions: {
                    'suggest': function ($el, data) {
                        var view = this;
                        $el[0].__loader = (data.bind.loader) ? $(data.bind.loader) : false;
                        $el._mvvm__suggest(data.bind.url, {
                            param: data.bind.param,
                            delay: 100,
                            minchars: 3,
                            multiple: false,
                            multipleSep: '',
                            resultsClass: 'ac_results',
                            selectClass: 'ac_over',
                            matchClass: 'ac_match',
                            onStart: function ($el) {
                                if ($el[0].__loader)
                                    $el[0].__loader.show();
                            },
                            onComplete: function ($el) {
                                if ($el[0].__loader)
                                    $el[0].__loader.hide();
                            }
                        });
                    },
                    'requiredWarning': function( $el, data ) {
                        if ( '' == $el.val() ) {
                            $el.addClass( 'cred-required-warning' );
                        } else {
                            $el.removeClass( 'cred-required-warning' );
                        }
                    },
                    // show/hide validation messages according to binding/result
                    'validationMessage': function ($el, data) {
                        if (!data.bind)
                            return;
                        if (data.bind.domRef)
                            $el = $(data.bind.domRef);
                        if (undefined !== data.validationResult) {
                            if (data.validationResult)
                                $el.hide();
                            else
                                $el.show();
                        }
                    },
                    'validationMessage2': function ($el, data) {
                        if (!data.bind)
                            return;
                        if (data.bind.domRef) {
                            $el = $(data.bind.domRef);
                            if (undefined !== data.validationResult) {
                                if (data.validationResult)
                                    $el.hide();
                            }
                        }

                    },
                    // show/hide element(s) according to binding
                    'show': function ($el, data) {
                        var delay = false, action = false;

                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (data['domRef']) {
                            $el = $(data['domRef']);
                        }

                        if (data['delay'])
                            delay = parseInt(data['delay'], 10);

                        if (data['condition']) {
                            data['condition'] = this._model.eval(data['condition']);
                            if (undefined !== data['condition']) {
                                if (data['condition'])
                                    action = 'show';
                                else
                                    action = 'hide';
                            }
                        } else {
                            action = 'show'
                        }

                        if (delay) {
                            if ('show' == action)
                                setTimeout(function () {
                                    $el.show();
                                }, delay);
                            else if ('hide' == action)
                                setTimeout(function () {
                                    $el.hide();
                                }, delay);
                        } else {
                            if ('show' == action)
                                $el.show();
                            else if ('hide' == action)
                                $el.hide();
                        }
                    },
                    // hide/show element(s) according to binding
                    'hide': function ($el, data) {
                        var delay = false, action = false;

                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (data['domRef'])
                            $el = $(data['domRef']);

                        if (data['delay'])
                            delay = parseInt(data['delay'], 10);

                        if (data['condition']) {
                            data['condition'] = this._model.eval(data['condition']);
                            if (undefined !== data['condition']) {
                                if (data['condition'])
                                    action = 'hide';
                                else
                                    action = 'show';
                            }
                        } else {
                            action = 'hide';
                        }

                        if (delay) {
                            if ('show' == action)
                                setTimeout(function () {
                                    $el.show();
                                }, delay);
                            else if ('hide' == action)
                                setTimeout(function () {
                                    $el.hide();
                                }, delay);
                        } else {
                            if ('show' == action)
                                $el.show();
                            else if ('hide' == action)
                                $el.hide();
                        }
                    },
                    // toggle element(s) according to binding
                    'toggle': function ($el, data) {
                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (data['domRef'])
                            $el = $(data['domRef']);

                        if (data['condition']) {
                            data['condition'] = this._model.eval(data['condition']);
                            if (undefined !== data['condition'])
                                $el.toggle(data['condition']);
                        } else
                            $el.toggle();
                    },
                    // add item to view/model using a template for the item
                    'addItem': function ($el, data) {
                        if (!data.bind)
                            return;
                        data = data.bind;

                        var key = data['modelRef'], tmpl = data['tmplRef'], container = data['domRef'],
                            item, view = this, model = view.model(),
                            rr, rxp, key2, __i__, __repl__, ll, map = {};

                        if (key && tmpl && container) {
                            key = removePrefix(model.id, key);
                            container = $(container);
                            item = $(tmpl).html();         // create item using template
                            if (data['replace'] && isObjectOrArray(data['replace']))         // replace any counters, etc..
                            {
                                rr = data['replace'];
                                for (__i__ = 0, __repl__ = 1, ll = rr.length; __repl__ < ll; __i__ += 2, __repl__ = __i__ + 1) {
                                    if (rr[__repl__].next) {
                                        key2 = rr[__repl__].next;
                                        key2 = removePrefix(model.id, key2);
                                        rr[__repl__] = model.next(key2);
                                    }

                                    rxp = new RegExp(escapeRegExp('' + rr[__i__]), 'g');
                                    item = item.replace(rxp, '' + rr[__repl__]);
                                    // replace in key also
                                    key = key.replace(rxp, '' + rr[__repl__]);
                                }
                            }
                            item = $(item);                   // make it dom element
                            change_notification_to_info(item, data);
                            container.append(item.hide());  // append item to container
                            model.mergeDom(key, item);      // merge item into model, silently
                            view.clearCaches();
                            model.trigger('change', {key: key, value: {}, triggerer: 'addItem'});   // trigger events
                            view.sync(item);  // synchronize the view
							item.fadeIn('slow');    // show new item with effect
							container.trigger( 'toolset:forms:editor:afterAddItem' );
                            check_cred_form_type_for_notification();
                        }
                    },
                    // remove item from view/model
                    'removeItem': function ($el, data) {
                        if (!data.bind)
                            return;
                        data = data.bind;
                        var key = data['modelRef'], item = data['domRef'], row = data['domRow']
                        view = this, model = view.model();

                        if (key && item) {
                            if (data['confirm'] && view._functions['confirm']) {
                                view._functions['confirm'](data['confirm'], function (_yes) {
                                    if (!_yes)
                                        return;

                                    $(item).fadeOut('slow', function () { // go with style ;)
                                        $(this).remove();
                                        key = removePrefix(model.id, key);
                                        model.del(key);
                                        view.clearCaches();
                                        model.trigger('change', {key: key, value: {}, triggerer: 'removeItem'});
                                        $(row).fadeOut('slow', function () {
                                            $(row).remove();
                                            view.updateForm();
                                        });
                                    });
                                });

                            } else {
                                $(item).fadeOut('slow', function () { // go with style ;)
                                    $(this).remove();
                                    key = removePrefix(model.id, key);
                                    model.del(key);
                                    view.clearCaches();
                                    model.trigger('change', {key: key, value: {}, triggerer: 'removeItem'});
                                    $(row).fadeOut('slow', function () {
                                        $(row).remove();
                                        view.updateForm();
                                    });
                                });

                            }

                        }
                    },
                    // remove gui element(s) according to binding
                    'remove': function ($el, key, value) {
                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (data['domRef'])
                            $el = $(data['domRef']);

                        if (data['condition']) {
                            data['condition'] = this._model.eval(data['condition']);
                            if (undefined !== data['condition']) {
                                if (data['condition'])
                                    $el.remove();
                            }
                        } else
                            $el.remove();
                    },
                    "enable": function ($el, data) {
                        data = data.bind;
                        if (data["condition"]) {
                            data['condition'] = this._model.eval(data['condition']);
                            if (undefined !== data['condition']) {
                                if (data['condition']) {
                                    $el.prop("disabled", false);
                                } else {
                                    $el.prop("disabled", true);
                                }
                            }
                        } else {
                            $el.prop("disabled", true);
                        }
                    },
                    // remove gui element(s) according to binding using fadeOut
                    'removeFade': function ($el, data) {
                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (data['domRef'])
                            $el = $(data['domRef']);

                        if (data['condition']) {
                            data['condition'] = this._model.eval(data['condition']);
                            if (undefined !== data['condition']) {
                                if (data['condition']) {
                                    $el.fadeOut('slow', function () {
                                        $(this).remove();
                                    });
                                }
                            }
                        } else
                            $el.fadeOut('slow', function () {
                                $(this).remove();
                            });
                    },
                    // set element(s) attributes/properties according to binding
                    'set': function ($el, data) {
                        if (!data.bind)
                            return;
                        data = data.bind;
                        if (!data['what'])
                            return;

                        var model = this.model(), what = data['what'], attr, key, value, result;

                        if (data['domRef'])
                            $el = $(data['domRef']);

                        if (data['condition']) {
                            result = model.eval(data['condition']);
                        }

                        for (attr in what) {
                            if (hasOwn(what, attr)) {
                                key = (what[attr]) ? removePrefix(model.id, what[attr]) : false;
                                if (!key || !model.has(key))
                                    continue;
                                value = model.get(key);

                                switch (attr) {
                                    case 'options':
                                        if ($el.is('select') && Array.isArray(value)) {
                                            var group = $el.find('optgroup'), sel = $el.val(), _options = ''; // get selected value
                                            if (!group.length)
                                                group = $el;

                                            group.find('option').not('[data-dummy-option]').remove();

                                            for (var ii = 0; ii < value.length; ii++) {
                                                if (value[ii] && value[ii].label)
                                                    _options += '<option value="' + value[ii].value + '">' + value[ii].label + '</option>';
                                                else
                                                    _options += '<option value="' + value[ii] + '">' + value[ii] + '</option>';
                                            }
                                            group.append(_options);
                                            $el.val(sel);
                                        }
                                        break;
                                    case 'html':
                                        $el.html(value);
                                        break;
                                    case 'class':
                                        var _v_0_ = value.charAt(0);
                                        if (true === result) {
                                            if ('-' == _v_0_)
                                                $el.removeClass(value.substring(1));
                                            else if ('+' == _v_0_)
                                                $el.addClass(value.substring(1));
                                            else if (value && '' != value)
                                                $el.addClass(value);
                                        } else if (false === result) {
                                            if ('-' == _v_0_)
                                                $el.addClass(value.substring(1));
                                            else if ('+' == _v_0_)
                                                $el.removeClass(value.substring(1));
                                            else if (value && '' != value)
                                                $el.removeClass(value);
                                        } else {
                                            if ('-' == _v_0_)
                                                $el.removeClass(value.substring(1));
                                            else if ('+' == _v_0_)
                                                $el.addClass(value.substring(1));
                                            else if (value && '' != value)
                                                $el.addClass(value);
                                        }
                                        break;
                                    case 'text':
                                        $el.text(value);
                                        break;
                                    default:
                                        if (undefined === result) {
                                            if ('checked' == attr) {
                                                if ($el.val() == value)
                                                    $el.prop('checked', true);
                                                else
                                                    $el.prop('checked', false);
                                            } else if ('disabled' == attr) {
                                                if ($el.val() == value)
                                                    $el.prop('disabled', true);
                                                else
                                                    $el.prop('disabled', false);
                                            } else
                                                $el.attr(attr, value);
                                        } else if (true === result) {
                                            if ('checked' == attr) {
                                                $el.prop('checked', true);
                                            } else if ('disabled' == attr) {
                                                $el.prop('disabled', true);
                                            } else
                                                $el.attr(attr, value);
                                        } else if (false === result) {
                                            if ('checked' == attr) {
                                                $el.prop('checked', false);
                                            } else if ('disabled' == attr) {
                                                $el.prop('disabled', false);
                                            } else
                                                $el.attr(attr, '');
                                        }
                                        break;
                                }
                            }
                        }
                    },
                    // default bind/update element(s) values according to binding (usually) when model changes or elem has binding with no action
                    'bind': function ($el, data) {
                        var key, value, val, name, view = this, model = view._model;

                        if ($el.is("input, textarea, select")) {
                            name = $el.attr('name');
                            if (name) {
                                key = removePrefix(model.id, name);
                                value = model.get(key);
                                val = $el.val();
                                if ($el.is(':radio')) {
                                    if (value == val) {
                                        view.getElements('input[name="' + name + '"]').not($el).prop('checked', false);
                                        $el.prop('checked', true);
                                    }
                                } else if ($el.is(':checkbox')) {
                                    var thischeckbox = view.getElements('input[type="checkbox"][name="' + name + '"]'); //view.$dom.find('input[type="checkbox"][name="'+name+'"]');
                                    if (thischeckbox.length > 1 && Array.isArray(value)) {
                                        thischeckbox.each(function (i, v) {
                                            var $this = $(this);
                                            if ($.inArray($this.val(), value) > -1)
                                                $this.prop('checked', true);
                                            else
                                                $this.prop('checked', false);
                                        });
                                    } else if (value == val)
                                        $el.prop('checked', true);
                                    else
                                        $el.prop('checked', false);
                                } else
                                    $el.val(value);
                            }
                        } else {
                            if (data.value)
                                $el.html(data.value);
                            else if (data.bind && data.bind.key) {
                                key = removePrefix(model.id, data.bind.key);
                                model.has(key) && $el.html(model.get(key));
                            }
                        }
                    }
                },
                Validators: {
                    // http://docs.jquery.com/Plugins/Validation/Methods/required
                    required: function ($el, v, p) {
                        // check if dependency is met
                        if (p && !_validatorUtils.depend($el, p)) {
                            return "dependency-mismatch";
                        }
                        var el = $el[0];
                        if (el.nodeName.toLowerCase() === "select") {
                            // could be an array for select-multiple or a string, both are fine this way
                            var val = $el.val();
                            return val && val.length > 0;
                        }
                        if (_validatorUtils.isCheckable($el)) {
                            return _validatorUtils.getLength($el, v) > 0;
                        }
                        return v.trim().length > 0;
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/email
                    email: function ($el, v) {
                        // contributed by Scott Gonzalez: http://projects.scottsplayground.com/email_address_validation/
                        return _validatorUtils.isOptional($el) || /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(v);
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/url
                    url: function ($el, v) {
                        // contributed by Scott Gonzalez: http://projects.scottsplayground.com/iri/
                        return _validatorUtils.isOptional($el) || /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(v);
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/date
                    date: function ($el, v) {
                        return _validatorUtils.isOptional($el) || !/Invalid|NaN/.test(new Date(v).toString());
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/dateISO
                    dateISO: function ($el, v) {
                        return _validatorUtils.isOptional($el) || /^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(v);
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/number
                    number: function ($el, v) {
                        return _validatorUtils.isOptional($el) || /^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(v);
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/digits
                    digits: function ($el, v) {
                        return _validatorUtils.isOptional($el) || /^\d+$/.test(v);
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/creditcard
                    // based on http://en.wikipedia.org/wiki/Luhn
                    creditcard: function ($el, v) {
                        if (_validatorUtils.isOptional($el)) {
                            return "dependency-mismatch";
                        }
                        // accept only spaces, digits and dashes
                        if (/[^0-9 \-]+/.test(v)) {
                            return false;
                        }
                        var nCheck = 0,
                            nDigit = 0,
                            bEven = false;

                        v = v.replace(/\D/g, "");

                        for (var n = v.length - 1; n >= 0; n--) {
                            var cDigit = v.charAt(n);
                            nDigit = parseInt(cDigit, 10);
                            if (bEven) {
                                if ((nDigit *= 2) > 9) {
                                    nDigit -= 9;
                                }
                            }
                            nCheck += nDigit;
                            bEven = !bEven;
                        }
                        return (nCheck % 10) === 0;
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/minlength
                    minlength: function ($el, v, p) {
                        var length = Array.isArray(v) ? v.length : _validatorUtils.getLength($el, v.trim());
                        return _validatorUtils.isOptional($el) || length >= p;
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/maxlength
                    maxlength: function ($el, v, p) {
                        var length = Array.isArray(v) ? v.length : _validatorUtils.getLength($el, v.trim());
                        return _validatorUtils.isOptional($el) || length <= p;
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/rangelength
                    rangelength: function ($el, v, p) {
                        var length = Array.isArray(v) ? v.length : _validatorUtils.getLength($el, v.trim());
                        return _validatorUtils.isOptional($el) || (length >= p[0] && length <= p[1]);
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/min
                    min: function ($el, v, p) {
                        return _validatorUtils.isOptional($el) || v >= p;
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/max
                    max: function ($el, v, p) {
                        return _validatorUtils.isOptional($el) || v <= p;
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/range
                    range: function ($el, v, p) {
                        return _validatorUtils.isOptional($el) || (v >= p[0] && v <= p[1]);
                    },
                    // http://docs.jquery.com/Plugins/Validation/Methods/equalTo
                    equalTo: function ($el, v, p) {
                        return v === $(p).val();
                    }
                },
                Events: {
                    'view': {
                        // these are arrays
                        'change': [
                            function (e, data) {

                                var view = this, model = view._model, bindAttr = view.attribute('bind'),
                                    bindSelector = '[' + bindAttr + ']', name, key, val;


                                // update model and propagate to other elements of same view (via model publish hook)
                                if ('change' == e.type && view._autoBind && data.el.is('input, textarea, select')) {
                                    name = data.el.attr('name') || '';
                                    key = removePrefix(model.id, name);
                                    if (model.has(key)) {
                                        if (data.el.is(':checkbox')) {
                                            var thischeckbox = view.getElements('input[type="checkbox"][name="' + name + '"]'); //view.$dom.find('input[type="checkbox"][name="'+name+'"]');
                                            if (thischeckbox.length > 1) {
                                                val = [];
                                                thischeckbox.each(function () {
                                                    var $this = $(this);
                                                    if ($this.is(':checked'))
                                                        val.push($this.val());
                                                    else
                                                        val.push('');
                                                });
                                            } else if (thischeckbox.is(':checked'))
                                                val = thischeckbox.val();
                                            else
                                                val = '';
                                        } else
                                            val = data.el.val();

                                        model.set(key, val, true);
                                    }
                                }

                                // custom actions
                                if (data.el.attr(bindAttr)) {
                                    // do view bind action first
                                    view._doAction(data.el, {event: e, data: data});
                                }

                                // do validation actions
                                view._doValidationAction(view.getElements(bindSelector) /*view.$dom.find(bindSelector)*/, {
                                    event: e,
                                    data: data
                                });

                                // notify 3rd-party also
                                view.trigger('change', data);
                            }

                        ]
                    },
                    'model': {
                        // these are arrays
                        'change': [
                            function (e, data) {
                                var view = this, model = view._model, name = model.id + data.key,
                                    bindAttr = view.attribute('bind'), bindSelector = '[' + bindAttr + ']',
                                    autobindSelector = 'input[name="' + name + '"], textarea[name="' + name + '"], select[name="' + name + '"]';

                                // do actions ..
                                // do view bind action first
                                view._doAction(view.getElements(bindSelector) /*view.$dom.find(bindSelector)*/, {
                                    event: e,
                                    data: data
                                });

                                // do view autobind action to bind input elements that map to the model, afterwards
                                view._autoBind && view._doAutoBindAction(view.getElements(autobindSelector) /*view.$dom.find(autobindSelector)*/, {
                                    event: e,
                                    data: data
                                });
                            }

                        ]
                    }
                },
                Functions: {
                    'confirm': function (msg, callback) {
                        var result = confirm(msg);
                        if (typeof callback === "function")
                            callback.call(this, result);
                    }
                },
                Prototype: {
                    _doAction: function ($els, data) {
                        var view = this;

                        $els.each(function () {

                            var $el = $(this),
                                bind = view.get($el, 'bind');

                            // during sync, dont do any actions based on events
                            if (
                                data.sync &&
                                (
                                    !bind ||
                                    (bind && bind.event && bind.event != 'change')
                                )
                            )
                                return;

                            // element has a custom view bind action and the correct event is triggered
                            if (
                                bind &&
                                bind.action &&
                                view._actions[bind.action] &&
                                (
                                    (bind.event && data.event &&
                                    data.event.type == bind.event) ||
                                    !bind.event
                                )
                            ) {
                                // pass the cloned bind object
                                var bindclone = $.extend(true, {}, bind);
                                view._actions[bind.action].call(view, $el, {bind: bindclone});
                            }
                        });
                    },
                    _doValidationAction: function ($els, data) {
                        var view = this;

                        $els.each(function () {

                            var $el = $(this),
                                bind = view.get($el, 'bind');

                            // do validation if needed
                            if (bind.validate) {
                                var validators = bind.validate, vv, result = true, a, l,
                                    v = _validatorUtils.getValue($el);

                                for (vv in validators) {
                                    if (hasOwn(validators, vv) && view._validators[vv] && validators[vv].actions && validators[vv].actions.length) {
                                        if ($el.is(':hidden')) // hidden elements pass validation
                                            result = true;
                                        else
                                            result = view._validators[vv].call(view, $el, v, validators[vv].params);

                                        for (a = 0, l = validators[vv].actions.length; a < l; a++) {
                                            if (validators[vv].actions[a].action && view._actions[validators[vv].actions[a].action])
                                                view._actions[validators[vv].actions[a].action].call(view, $el, {
                                                    bind: validators[vv].actions[a],
                                                    validationResult: result
                                                });
                                        }
                                    }

                                    if (!result)
                                        return; // exit
                                }
                            }
                        });
                    },
                    _doInitAction: function ($els, data) {
                        var view = this;

                        $els.each(function () {

                            var $el = $(this),
                                bind = view.get($el, 'bind');

                            // no init action
                            if (!bind || !bind.event || 'init' != bind.event || !bind.action)
                                return;

                            for (var a in bind.action) {
                                if (hasOwn(bind.action, a) && view._actions[a]) {
                                    // pass the cloned bind object
                                    var bindclone = $.extend(true, {}, bind.action[a]);
                                    view._actions[a].call(view, $el, {bind: bindclone});
                                }
                            }
                        });
                    },
                    _doAutoBindAction: function ($els, data) {
                        var view = this, model = view._model;
                        if (view._actions['bind']) {
                            $els.each(function () {

                                var $el = $(this),
                                    name = $el.attr('name') || '',
                                    key = (data && data.key) ? data.key : removePrefix(model.id, name),
                                    value;

                                if (data && data.value) // action is called from model, so key value are already there
                                    value = data.value;
                                else if (model.has(key))
                                    value = model.get(key);
                                else
                                    return;  // nothing to do here

                                // call default action (eg: live update)
								view._actions['bind'].call(view, $el, {key: key, value: value});

								// Trigger an event to broadcast the binding
								$el.trigger( 'toolset:forms:editor:autobinded' );
                            });
                        }
                    },
                    // allow chaining, return this;

                    // public (kind of)
                    init: function (pub) {
                        if (pub)
                            this.trigger('init', {target: this});
                        return this;
                    },
                    dispose: function () {
                        if (this._model) {
                            this._model.dispose();
                            this._model = null;
                        }
                    },
                    model: function (m) {
                        if (m) {
                            this._model = m;
                            return this;
                        }
                        return this._model;
                    },
                    actions: function (acts) {
                        if (acts) {
                            this._actions = acts;
                            return this;
                        }
                        return this._actions;
                    },
                    action: function (act, callback) {
                        if (typeof callback === "function") {
                            this._actions[act] = callback;
                            return this;
                        }
                        return (this._actions[act]) ? this._actions[act] : undefined;
                    },
                    functions: function (funcs) {
                        if (funcs) {
                            this._functions = funcs;
                            return this;
                        }
                        return this._functions;
                    },
                    func: function (func, callback) {
                        if (typeof callback === "function") {
                            this._functions[func] = callback;
                            return this;
                        }
                        return (this._functions[func]) ? this._functions[func] : undefined;
                    },
                    validators: function (valids) {
                        if (valids) {
                            this._validators = valids;
                            return this;
                        }
                        return this._validators;
                    },
                    validator: function (valid, callback) {
                        if (typeof callback === "function") {
                            this._validators[valid] = callback;
                            return this;
                        }
                        return (this._validators[valid]) ? this._validators[valid] : undefined;
                    },
                    events: function (evts) {
                        if (evts) {
                            this._events = evts;
                            return this;
                        }
                        return this._events;
                    },
                    event: function (evt, callback) {
                        evt = (evt) ? evt.split(':') : [];
                        if (evt[0] && (evt[0] == 'view' || evt[0] == 'model')) {
                            if (evt[1]) {
                                if (typeof callback === "function") {
                                    if (!this._events[evt[0]])
                                        this._events[evt[0]] = {};
                                    if (!this._events[evt[0]][evt[1]])
                                        this._events[evt[0]][evt[1]] = [];

                                    this._events[evt[0]][evt[1]].push(callback);

                                    return this;
                                } else {
                                    if (this._events[evt[0]] & this._events[evt[0]][evt[1]])
                                        return this._events[evt[0]][evt[1]];
                                }
                            }
                        }
                        return false;
                    },
                    attributes: function (atts) {
                        if (atts) {
                            this._atts = atts;
                            return this;
                        }
                        return this._atts;
                    },
                    attribute: function (type, att) {
                        if (att) {
                            this._atts[type] = att;
                            return this;
                        }
                        return (this._atts[type]) ? this._atts[type] : undefined;
                    },
                    // cache jquery selectors for even faster performance
                    getElements: function (selector, dom, bypass) {
                        if (bypass) {
                            if (!dom)
                                dom = this.$dom;
                            else
                                dom = $(dom);
                            return dom.find(selector);
                        }

                        if (
                            undefined !== this._selectorsCacheTime &&
                            new Date().getTime() - this._selectorsCacheTime > this._REFRESH_INTERVAL
                        )
                            this.clearCaches(); // refresh cache;

                        if (!this._selectorsCache[selector]) {
                            if (!dom)
                                dom = this.$dom;
                            else
                                dom = $(dom);
                            this._selectorsCache[selector] = dom.find(selector);
                            if (undefined === this._selectorsCacheTime)
                                this._selectorsCacheTime = new Date().getTime();
                        }
                        return this._selectorsCache[selector];
                    },
                    clearCaches: function () {
                        this._selectorsCache = {}; // refresh cache;
                        delete this._selectorsCacheTime;
                        return this;
                    },
                    updateForm: function () {
                        jQuery.ajax({
                            url: document.location,
                            data: this.serialize('#post'),
                            type: 'post',
                            success: function () {}
                        });
                    },
                    serialize: function (what) {
                        var values;

                        // Get the parameters as an array
                        values = jQuery(what).serializeArray();

                        // Convert to URL-encoded string
                        return jQuery.param(values);
                    },
                    // http://stackoverflow.com/questions/10892322/javascript-hashtable-use-object-key
                    // http://stackoverflow.com/questions/2937120/how-to-get-javascript-object-references-or-reference-count
                    get: function ($el, att) {
                        // use memoization/caching
                        if (!this._memoize)
                            this._memoize = {};

                        if (
                            this._atts[att] &&
                            hasAtt($el, this._atts[att])
                        ) {
                            var attr = $el.attr(this._atts[att]);
                            if (!this._memoize[attr]) {
                                while (count(this._memoize) >= this._CACHE_SIZE) {
                                    for (var k in this._memoize) {
                                        if (hasOwn(this._memoize, k)) {
                                            delete this._memoize[k];
                                            break;
                                        }
                                    }
                                }
                                // parsing is expensive, use memoize cache
                                this._memoize[attr] = parseObj(attr);
                            }
                            return this._memoize[attr];
                        }

                        return false;
                    },
                    autobind: function (enable) {
                        if ('undefined' != typeof (enable)) {
                            this._autoBind = (enable) ? true : false;
                            return this;
                        }
                        return this._autoBind;
                    },
                    sync: function (dom) {
                        var view = this, model = view._model,
                            bindAttr = view.attribute('bind'),
                            bindSelector = '[' + bindAttr + ']',
                            autobindSelector = 'input[name^="' + model.id + '["], textarea[name^="' + model.id + '["], select[name^="' + model.id + '["]';

                        if (!dom)
                            dom = view.dom;
                        dom = $(dom);

                        view._doInitAction(view.getElements(bindSelector, dom, true) /*dom.find(bindSelector)*/, {sync: true});

                        view._doAction(view.getElements(bindSelector, dom, true) /*dom.find(bindSelector)*/, {sync: true});

                        view._autoBind && view._doAutoBindAction(view.getElements(autobindSelector, dom, true) /*dom.find(autobindSelector)*/, {sync: true});

                        view._doValidationAction(view.getElements(bindSelector, dom, true) /*dom.find(bindSelector)*/, {sync: true});

                        return this;
                    },
                    forceValidation: function () {
                        var view = this, model = view._model,
                            bindAttr = view.attribute('bind'),
                            bindSelector = '[' + bindAttr + ']',
                            autobindSelector = 'input[name^="' + model.id + '["], textarea[name^="' + model.id + '["], select[name^="' + model.id + '["]';
                        view._doValidationAction(view.getElements(bindSelector, false, true));
                        return this;
                    },
                    bind: function (events, dom) {
                        var view = this, model = view._model;

                        if (view._events) {
                            if (!dom)
                                dom = window.document;
                            view.dom = dom;
                            view.$dom = $(view.dom);

                            // model events
                            if (view._events['model']) {
                                for (var evt in view._events['model']) {
                                    if (hasOwn(view._events['model'], evt)) {
                                        _.EventManager.subscribe(_.Event(model, evt), function (e, o) {
                                            for (var i = 0, l = view._events['model'][evt].length; i < l; i++)
                                                view._events['model'][evt][i].apply(view, [e, o]);
                                        });
                                    }
                                }
                            }

                            if (!events)
                                events = ['change', 'click'];

                            if (view._events['view'] && view._events['view']['change'] && events && events.length) {
                                var bindSelector = '[' + view.attribute('bind') + ']';
                                var autobindSelector = 'input[name^="' + model.id + '["], textarea[name^="' + model.id + '["], select[name^="' + model.id + '["]';

                                // view/dom events
                                view.$dom.on(events.join(' '), bindSelector, function (e) {
                                    var $el = $(this);
                                    if (view._events['view']['change']) {
                                        var bind = view.get($el, 'bind');

                                        if (
                                            bind &&
                                            (
                                                (!bind.event && e.type == 'change') ||
                                                (bind.event && e.type == bind.event)
                                            )
                                        ) {
                                            for (var i = 0, l = view._events['view']['change'].length; i < l; i++)
                                                view._events['view']['change'][i].call(view, e, {el: $el, bind: bind});
                                        }
                                    }
                                });

                            }

                            if (view._events['view'] && view._events['view']['change'] && view._autoBind) {
                                view.$dom.on('change', autobindSelector, function (e) {
                                    var $el = $(this),
                                        name = $el.attr('name') || '',
                                        key = removePrefix(model.id, name),
                                        val;

                                    if (model.has(key)) {
                                        for (var i = 0, l = view._events['view']['change'].length; i < l; i++)
                                            view._events['view']['change'][i].call(view, e, {el: $el, key: key});
                                    }
                                });
                            }
                        }
                        return this;
                    },
                    trigger: function (evt, o) {
                        o = o || {target: this};
                        _.EventManager.publish(_.Event(this, evt), o);
                        return this;
                    }
                }
            },
            create: function (proto, props) {
                var object = {};
                proto = proto || {};
                props = props || {};

                $.extend(proto, props);
                object = Object.create(proto);
                return object;
            }
        },
        Event: function (obj, type) {
            return context + obj.id + ':' + type;
        },
        EventManager: {
            publish: function (message, data) {
                pubSub.trigger(message, data);
                return true;
            },
            subscribe: function (message, callback) {
                if (!callback)
                    return false;

                pubSub.on(message, callback);
                return true;
            },
            unsubscribe: function (message, callback) {
                if (callback)
                    pubSub.off(message, callback);
                else
                    pubSub.off(message);
                return true;
            }
        },
        Model: function (id, data /*, extend, .. */) {
            var self,
                proto = $.extend({}, _.Builder.Model.Prototype);

            extendArgs = Array.prototype.slice.call(arguments, 2);
            for (var ii = 0, ll = extendArgs.length; ii < ll; ii++)
                // extend here
                $.extend(proto, extendArgs[ii]);

            self = _.Builder.create(proto, {
                id: id,
                _data: data, //null,
                _functions: _.Builder.Model.Functions
            });
            return self;
        },
        View: function (id, model /*, extend, .. */) {
            var self,
                props = {
                    id: id,
                    dom: null,
                    $dom: null,
                    // protected (kind of)
                    _autoBind: false,
                    _actions: _.Builder.View.Actions,
                    _validators: _.Builder.View.Validators,
                    _events: _.Builder.View.Events,
                    _functions: _.Builder.View.Functions,
                    _atts: {'bind': 'data-' + id + '-bind'},
                    _model: model, //null,
                    _memoize: {},
                    _selectorsCache: {},
                    _REFRESH_INTERVAL: _.Builder.View._REFRESH_INTERVAL,
                    _CACHE_SIZE: _.Builder.View._CACHE_SIZE
                },
                proto = $.extend({}, _.Builder.View.Prototype);

            extendArgs = Array.prototype.slice.call(arguments, 2);
            for (var ii = 0, ll = extendArgs.length; ii < ll; ii++)
                // extend here
                $.extend(proto, extendArgs[ii]);

            self = _.Builder.create(proto, props);
            return self;
        },
        add: function (type, id, obj) {
            _items[type][id] = obj;
        },
        remove: function (type, id) {
            _items[type][id] = null;
            delete _items[type][id];
        },
        get: function (type, id) {
            if (_items[type] && _items[type][id])
                return _items[type][id];
            return;
        }
    };

    // export it
    window[thisExportName] = _;
})(window, jQuery, undefined);



