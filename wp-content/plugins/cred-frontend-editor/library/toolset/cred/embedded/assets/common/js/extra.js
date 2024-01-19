/* Auxilliary plugins and functions */
(function (window, $, undefined) {
    var thisExportName = 'cred_extra';

    if (window[thisExportName])
        return;

    $.fn.slideFadeDown = function (speed, callback) {
        return this.each(function () {

            $(this).stop(true, true)
                    .animate({
                        height: "toggle",
                        opacity: "toggle"
                    }, speed, function () {
                        if (typeof callback === "function") {
                            callback.call(this);
                        }
                    });
        });
    };
    $.fn.slideFadeUp = function (speed, callback) {
        return this.each(function () {
            if ($(this).is(':hidden'))
                $(this).hide(); // makes element not lose height if already hidden (eg by parent element)
            else
            {
                $(this).stop(true, true)
                        .animate({
                            height: "hide",
                            opacity: "hide"
                        }, speed, function () {
                            if (typeof callback === "function") {
                                callback.call(this);
                            }
                        });
            }
        });
    };

    $.fn.__show = function ()
    {
        $(this).show(400, function (event) {
            var class_name = $(this).prop('class');
            Toolset.hooks.doAction(class_name.split(' ')[0] + '_show');
        });
    };

    $.fn.__hide = function ()
    {
        $(this).hide(400, function (event) {
            var class_name = $(this).prop('class');
            Toolset.hooks.doAction(class_name.split(' ')[0] + '_hide');
        });
    };

    /* Ajax File Download Plugin */
    $.extend({
        //
        //$.fileDownload('/path/to/url/', options)
        //  see directly below for possible 'options'
        fileDownload: function (fileUrl, options) {

            var defaultFailCallback = function (responseHtml, url) {
                alert("A file download error has occurred, please try again.");
            };

            //provide some reasonable defaults to any unspecified options below
            var settings = $.extend({
                //
                //Requires jQuery UI: provide a message to display to the user when the file download is being prepared before the browser's dialog appears
                //
                preparingMessageHtml: null,
                //
                //Requires jQuery UI: provide a message to display to the user when a file download fails
                //
                failMessageHtml: null,
                //
                //the stock android browser straight up doesn't support file downloads initiated by a non GET: http://code.google.com/p/android/issues/detail?id=1780
                //specify a message here to display if a user tries with an android browser
                //if jQuery UI is installed this will be a dialog, otherwise it will be an alert
                //
                androidPostUnsupportedMessageHtml: "Unfortunately your Android browser doesn't support this type of file download. Please try again with a different browser.",
                //
                //Requires jQuery UI: options to pass into jQuery UI Dialog
                //
                dialogOptions: {modal: true},
                //
                //a function to call after a file download dialog/ribbon has appeared
                //Args:
                //  url - the original url attempted
                //
                successCallback: function (url) { },
                beforeDownloadCallback: false,
                //
                //a function to call after a file download dialog/ribbon has appeared
                //Args:
                //  responseHtml    - the html that came back in response to the file download. this won't necessarily come back depending on the browser.
                //                      in less than IE9 a cross domain error occurs because 500+ errors cause a cross domain issue due to IE subbing out the
                //                      server's error message with a "helpful" IE built in message
                //  url             - the original url attempted
                //
                failCallback: false,
                //failBeforeDownloadCallback : false,

                //
                // the HTTP method to use. Defaults to "GET".
                //
                httpMethod: "GET",
                //
                // if specified will perform a "httpMethod" request to the specified 'fileUrl' using the specified data.
                // data must be an object (which will be $.param serialized) or already a key=value param string
                //
                data: null,
                //
                //a period in milliseconds to poll to determine if a successful file download has occured or not
                //
                checkInterval: 100,
                //
                //the cookie name to indicate if a file download has occured
                //
                cookieName: "__CREDExportDownload",
                //
                //the cookie value for the above name to indicate that a file download has occured
                //
                cookieValue: "true",
                //
                //the cookie path for above name value pair
                //
                cookiePath: "/",
                //
                //the title for the popup second window as a download is processing in the case of a mobile browser
                //
                popupWindowTitle: "Initiating file download...",
                //
                //Functionality to encode HTML entities for a POST, need this if data is an object with properties whose values contains strings with quotation marks.
                //HTML entity encoding is done by replacing all &,<,>,',",\r,\n characters.
                //Note that some browsers will POST the string htmlentity-encoded whilst others will decode it before POSTing.
                //It is recommended that on the server, htmlentity decoding is done irrespective.
                //
                encodeHTMLEntities: true
            }, options);


            //Setup mobile browser detection: Partial credit: http://detectmobilebrowser.com/
            var userAgent = (navigator.userAgent || navigator.vendor || window.opera).toLowerCase();

            var isIos = false;                  //has full support of features in iOS 4.0+, uses a new window to accomplish this.
            var isAndroid = false;              //has full support of GET features in 4.0+ by using a new window. POST will resort to a POST on the current window.
            var isOtherMobileBrowser = false;   //there is no way to reliably guess here so all other mobile devices will GET and POST to the current window.

            if (/ip(ad|hone|od)/.test(userAgent)) {

                isIos = true;

            } else if (userAgent.indexOf('android') != -1) {

                isAndroid = true;

            } else {

                isOtherMobileBrowser = /avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|playbook|silk|iemobile|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(userAgent) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i.test(userAgent.substr(0, 4));

            }

            var httpMethodUpper = settings.httpMethod.toUpperCase();

            if (isAndroid && httpMethodUpper != "GET") {
                //the stock android browser straight up doesn't support file downloads initiated by non GET requests: http://code.google.com/p/android/issues/detail?id=1780

                if ($().dialog) {
                    $("<div>").html(settings.androidPostUnsupportedMessageHtml).dialog(settings.dialogOptions);
                } else {
                    alert(settings.androidPostUnsupportedMessageHtml);
                }

                return;
            }

            //wire up a jquery dialog to display the preparing message if specified
            var $preparingDialog = null;
            /*if (settings.preparingMessageHtml) {

             $preparingDialog = $("<div>").html(settings.preparingMessageHtml).dialog(settings.dialogOptions);

             }*/

            if (settings.beforeDownloadCallback) {

                settings.beforeDownloadCallback();
            }

            var internalCallbacks = {
                onSuccess: function (url) {

                    //remove the perparing message if it was specified
                    /*if ($preparingDialog) {
                     $preparingDialog.dialog('close');
                     };*/

                    settings.successCallback(url);

                },
                onFail: function (responseHtml, url) {

                    //remove the perparing message if it was specified
                    if ($preparingDialog) {
                        $preparingDialog.dialog('close');
                    }
                    ;

                    //wire up a jquery dialog to display the fail message if specified
                    if (settings.failMessageHtml) {

                        $("<div>").html(settings.failMessageHtml).dialog(settings.dialogOptions);

                        //only run the fallcallback if the developer specified something different than default
                        //otherwise we would see two messages about how the file download failed
                        if (settings.failCallback && settings.failCallback != defaultFailCallback) {
                            settings.failCallback(responseHtml, url);
                        }

                    } else if (settings.failCallback) {

                        settings.failCallback(responseHtml, url);
                    }
                }
            };


            //make settings.data a param string if it exists and isn't already
            if (settings.data !== null && typeof settings.data !== "string") {
                settings.data = $.param(settings.data);
            }


            var $iframe,
                    downloadWindow,
                    formDoc,
                    $form;

            if (httpMethodUpper === "GET") {

                if (settings.data !== null) {
                    //need to merge any fileUrl params with the data object

                    var qsStart = fileUrl.indexOf('?');

                    if (qsStart != -1) {
                        //we have a querystring in the url

                        if (fileUrl.substring(fileUrl.length - 1) !== "&") {
                            fileUrl = fileUrl + "&";
                        }
                    } else {

                        fileUrl = fileUrl + "?";
                    }

                    fileUrl = fileUrl + settings.data;
                }

                if (isIos || isAndroid) {

                    downloadWindow = window.open(fileUrl);
                    downloadWindow.document.title = settings.popupWindowTitle;
                    window.focus();

                } else if (isOtherMobileBrowser) {

                    window.location(fileUrl);

                } else {

                    //create a temporary iframe that is used to request the fileUrl as a GET request
                    $iframe = $("<iframe>")
                            .hide()
                            .attr("src", fileUrl)
                            .appendTo("body");
                }

            } else {

                var formInnerHtml = "";

                if (settings.data !== null) {

                    $.each(settings.data.replace(/\+/g, ' ').split("&"), function () {

                        var kvp = this.split("=");

                        var key = settings.encodeHTMLEntities ? htmlSpecialCharsEntityEncode(decodeURIComponent(kvp[0])) : decodeURIComponent(kvp[0]);
                        if (!key)
                            return;
                        var value = kvp[1] || '';
                        value = settings.encodeHTMLEntities ? htmlSpecialCharsEntityEncode(decodeURIComponent(kvp[1])) : decodeURIComponent(kvp[1]);

                        formInnerHtml += '<input type="hidden" name="' + key + '" value="' + value + '" />';
                    });
                }

                if (isOtherMobileBrowser) {

                    $form = $("<form>").appendTo("body");
                    $form.hide()
                            .attr('method', settings.httpMethod)
                            .attr('action', fileUrl)
                            .html(formInnerHtml);

                } else {

                    if (isIos) {

                        downloadWindow = window.open("about:blank");
                        downloadWindow.document.title = settings.popupWindowTitle;
                        formDoc = downloadWindow.document;
                        window.focus();

                    } else {

                        $iframe = $("<iframe style='display: none' src='about:blank'></iframe>").appendTo("body");
                        formDoc = getiframeDocument($iframe);
                    }

                    formDoc.write("<html><head></head><body><form method='" + settings.httpMethod + "' action='" + fileUrl + "'>" + formInnerHtml + "</form>" + settings.popupWindowTitle + "</body></html>");
                    $form = $(formDoc).find('form');
                }

                $form.submit();
            }


            //check if the file download has completed every checkInterval ms
            setTimeout(checkFileDownloadComplete, settings.checkInterval);


            function checkFileDownloadComplete() {

                //has the cookie been written due to a file download occuring?
                if (document.cookie.indexOf(settings.cookieName + "=" + settings.cookieValue) != -1) {

                    //execute specified callback
                    internalCallbacks.onSuccess(fileUrl);

                    //remove the cookie and iframe
                    var date = new Date(1000);
                    document.cookie = settings.cookieName + "=; expires=" + date.toUTCString() + "; path=" + settings.cookiePath;

                    cleanUp(false);

                    return;
                }

                //has an error occured?
                //if neither containers exist below then the file download is occuring on the current window
                if (downloadWindow || $iframe) {

                    //has an error occured?
                    try {

                        var formDoc;
                        if (downloadWindow) {
                            formDoc = downloadWindow.document;
                        } else {
                            formDoc = getiframeDocument($iframe);
                        }

                        if (formDoc && formDoc.body != null && formDoc.body.innerHTML.length > 0) {

                            var isFailure = true;

                            if ($form && $form.length > 0) {
                                var $contents = $(formDoc.body).contents().first();

                                if ($contents.length > 0 && $contents[0] === $form[0]) {
                                    isFailure = false;
                                }
                            }

                            if (isFailure) {
                                internalCallbacks.onFail(formDoc.body.innerHTML, fileUrl);

                                cleanUp(true);

                                return;
                            }
                        }
                    } catch (err) {

                        //500 error less than IE9
                        internalCallbacks.onFail('', fileUrl);

                        cleanUp(true);

                        return;
                    }
                }


                //keep checking...
                setTimeout(checkFileDownloadComplete, settings.checkInterval);
            }

            //gets an iframes document in a cross browser compatible manner
            function getiframeDocument($iframe) {
                var iframeDoc = $iframe[0].contentWindow || $iframe[0].contentDocument;
                if (iframeDoc.document) {
                    iframeDoc = iframeDoc.document;
                }
                return iframeDoc;
            }

            function cleanUp(isFailure) {

                setTimeout(function () {

                    if (downloadWindow) {

                        if (isAndroid) {
                            downloadWindow.close();
                        }

                        if (isIos) {
                            if (isFailure) {
                                downloadWindow.focus(); //ios safari bug doesn't allow a window to be closed unless it is focused
                                downloadWindow.close();
                            } else {
                                downloadWindow.focus();
                            }
                        }
                    }

                }, 0);
            }

            function htmlSpecialCharsEntityEncode(str) {
                return str.replace(/&/gm, '&amp;')
                        .replace(/\n/gm, "&#10;")
                        .replace(/\r/gm, "&#13;")
                        .replace(/</gm, '&lt;')
                        .replace(/>/gm, '&gt;')
                        .replace(/"/gm, '&quot;')
                        .replace(/'/gm, '&apos;'); //single quotes just to be safe
            }
        }
    });


    /*
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

    $.cred_suggest = function (input, options) {
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
            }, 200);
        });

        $input.keydown(processKey);

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
            if ((/27$|38$|40$/.test(e.keyCode) && $results.is(':visible')) ||
                    (/^13$|^9$/.test(e.keyCode) && getCurrentResult())) {

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

            var q = $input.val().trim(), multipleSepPos, items;

            if (options.multiple) {
                multipleSepPos = q.lastIndexOf(options.multipleSep);
                if (multipleSepPos != -1) {
					q = q.substr(multipleSepPos + options.multipleSep.length);
					q = q.trim();
                }
            }
            if (q.length >= options.minchars) {

                cached = checkCache(q);

                if (cached) {

                    displayItems(cached['items']);

                } else {

                    if (options.onStart)
                    {
                        options.onStart.call(this);
                    }
                    $.get(options.source, {q: q}, function (txt) {

                        $results.hide();

                        items = parseTxt(txt, q);

                        displayItems(items);
                        addToCache(q, items, items.length /*txt.length*/);

                        if (options.onComplete)
                        {
                            options.onComplete.call(this);
                        }

                    });

                }

            } else {

                $results.hide();

            }

        }


        function checkCache(q) {
            var i;
            for (i = 0; i < cache.length; i++)
                if (cache[i]['q'] == q) {
                    cache.unshift(cache.splice(i, 1)[0]);
                    return cache[0];
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
            var html = '', i;
            if (!items)
                return;

            if (!items.length) {
                $results.hide();
                return;
            }

            resetPosition(); // when the form moves after the page has loaded

            for (i = 0; i < items.length; i++)
                html += '<li data-val="' + items[i].val + '">' + items[i].display + '</li>';

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
                        e._cred_specific = true;
                        selectCurrentResult();
                    });

        }

        function parseTxt(txt, q) {

            var items = [];//, tokens = txt.split(options.delimiter), i, token;
            items = JSON.parse(txt);
            return items;
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
                    if ($input.val().indexOf(options.multipleSep) != -1) {
                        $currentVal = $input.val().substr(0, ($input.val().lastIndexOf(options.multipleSep) + options.multipleSep.length));
                    } else {
                        $currentVal = "";
                    }
                    $input.val($currentVal + $currentResult.attr('data-val') + options.multipleSep);
                    $input.focus();
                } else {
                    $input.val($currentResult.attr('data-val'));
                    $input.focus();
                }
                $results.hide();

                if (options.onSelect)
                    options.onSelect.apply($input[0]);

            }

        }

        function nextResult() {

            $currentResult = getCurrentResult();

            if ($currentResult)
                $currentResult
                        .removeClass(options.selectClass)
                        .next()
                        .addClass(options.selectClass);
            else
                $results.children('li:first-child').addClass(options.selectClass);

        }

        function prevResult() {
            var $currentResult = getCurrentResult();

            if ($currentResult)
                $currentResult
                        .removeClass(options.selectClass)
                        .prev()
                        .addClass(options.selectClass);
            else
                $results.children('li:last-child').addClass(options.selectClass);

        }
    }

	// Probably deprecated, I find no usage.
    $.fn.cred_suggest = function (source, options) {

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
        options.maxCacheSize = options.maxCacheSize || 65536;

        this.each(function () {
            new $.cred_suggest(this, options);
        });

        return this;

    };

    // animation easing functions
    //;$.extend($.easing, {linear:function(a,b,c,d){return c+d*a},backEaseIn:function(a,b,c,d){var e=c+d,f=1.70158;return e*(a/=1)*a*((f+1)*a-f)+c},backEaseOut:function(a,b,c,d){var e=c+d,f=1.70158;return e*((a=a/1-1)*a*((f+1)*a+f)+1)+c},backEaseInOut:function(a,b,c,d){var e=c+d,f=1.70158;return(a/=.5)<1?e/2*a*a*(((f*=1.525)+1)*a-f)+c:e/2*((a-=2)*a*(((f*=1.525)+1)*a+f)+2)+c},bounceEaseIn:function(a,b,c,d){var e=c+d,f=this.bounceEaseOut(1-a,1,0,d);return e-f+c},bounceEaseOut:function(a,b,c,d){var e=c+d;return a<1/2.75?e*7.5625*a*a+c:a<2/2.75?e*(7.5625*(a-=1.5/2.75)*a+.75)+c:a<2.5/2.75?e*(7.5625*(a-=2.25/2.75)*a+.9375)+c:e*(7.5625*(a-=2.625/2.75)*a+.984375)+c},circEaseIn:function(a,b,c,d){var e=c+d;return-e*(Math.sqrt(1-(a/=1)*a)-1)+c},circEaseOut:function(a,b,c,d){var e=c+d;return e*Math.sqrt(1-(a=a/1-1)*a)+c},circEaseInOut:function(a,b,c,d){var e=c+d;return(a/=.5)<1?-e/2*(Math.sqrt(1-a*a)-1)+c:e/2*(Math.sqrt(1-(a-=2)*a)+1)+c},cubicEaseIn:function(a,b,c,d){var e=c+d;return e*(a/=1)*a*a+c},cubicEaseOut:function(a,b,c,d){var e=c+d;return e*((a=a/1-1)*a*a+1)+c},cubicEaseInOut:function(a,b,c,d){var e=c+d;return(a/=.5)<1?e/2*a*a*a+c:e/2*((a-=2)*a*a+2)+c},elasticEaseIn:function(a,b,c,d){var e=c+d;if(a==0)return c;if(a==1)return e;var f=.25,g,h=e;return h<Math.abs(e)?(h=e,g=f/4):g=f/(2*Math.PI)*Math.asin(e/h),-(h*Math.pow(2,10*(a-=1))*Math.sin((a*1-g)*2*Math.PI/f))+c},elasticEaseOut:function(a,b,c,d){var e=c+d;if(a==0)return c;if(a==1)return e;var f=.25,g,h=e;return h<Math.abs(e)?(h=e,g=f/4):g=f/(2*Math.PI)*Math.asin(e/h),-(h*Math.pow(2,-10*a)*Math.sin((a*1-g)*2*Math.PI/f))+e},expoEaseIn:function(a,b,c,d){var e=c+d;return a==0?c:e*Math.pow(2,10*(a-1))+c-e*.001},expoEaseOut:function(a,b,c,d){var e=c+d;return a==1?e:d*1.001*(-Math.pow(2,-10*a)+1)+c},expoEaseInOut:function(a,b,c,d){var e=c+d;return a==0?c:a==1?e:(a/=.5)<1?e/2*Math.pow(2,10*(a-1))+c-e*5e-4:e/2*1.0005*(-Math.pow(2,-10*--a)+2)+c},quadEaseIn:function(a,b,c,d){var e=c+d;return e*(a/=1)*a+c},quadEaseOut:function(a,b,c,d){var e=c+d;return-e*(a/=1)*(a-2)+c},quadEaseInOut:function(a,b,c,d){var e=c+d;return(a/=.5)<1?e/2*a*a+c:-e/2*(--a*(a-2)-1)+c},quartEaseIn:function(a,b,c,d){var e=c+d;return e*(a/=1)*a*a*a+c},quartEaseOut:function(a,b,c,d){var e=c+d;return-e*((a=a/1-1)*a*a*a-1)+c},quartEaseInOut:function(a,b,c,d){var e=c+d;return(a/=.5)<1?e/2*a*a*a*a+c:-e/2*((a-=2)*a*a*a-2)+c},quintEaseIn:function(a,b,c,d){var e=c+d;return e*(a/=1)*a*a*a*a+c},quintEaseOut:function(a,b,c,d){var e=c+d;return e*((a=a/1-1)*a*a*a*a+1)+c},quintEaseInOut:function(a,b,c,d){var e=c+d;return(a/=.5)<1?e/2*a*a*a*a*a+c:e/2*((a-=2)*a*a*a*a+2)+c},sineEaseIn:function(a,b,c,d){var e=c+d;return-e*Math.cos(a*(Math.PI/2))+e+c},sineEaseOut:function(a,b,c,d){var e=c+d;return e*Math.sin(a*(Math.PI/2))+c},sineEaseInOut:function(a,b,c,d){var e=c+d;return-e/2*(Math.cos(Math.PI*a)-1)+c}})

})(window, jQuery);
