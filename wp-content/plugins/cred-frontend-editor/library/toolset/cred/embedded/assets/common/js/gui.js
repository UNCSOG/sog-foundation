(function(window, $, undefined){
var thisExportName='cred_gui';

if (window[thisExportName]) return;

    /*
    *   GUI common framework/widgets
    *
    */

    function _confirm(params)
    {
        if (!params) params={};

        params=$.extend({
            'width': 400,
            'height': 'auto',
            'modal': true,
            'resizable': false,
            'callback': false,
            'class': '',
            'title': '',
            'message': 'Are you sure?',
            'buttons':[ 'Yes', 'No'],
            'primary': 'Yes'
        }, params);

        var content='<div class="_dialog_confirm">\
						<div class="ui-icon ui-icon-confirm">\
							<p>'+params['message']+'</p>\
						</div>\
                    </div>',
            $popup=$(content).appendTo($('body')), $widget;


        var buttons=[], i, l,
            button_handler=function(e) {

                var button=$(e.target).text();
                $(this).dialog( "close" );
                if (params['callback'])
                    params['callback'].call(this, button);
            };

        for (i=0, l=params['buttons'].length; i<l; i++ )
        {
            if (params['primary'] && params['buttons'][i]==params['primary'])
                buttons[i]={text: params['buttons'][i], click: button_handler, class: 'button button-large button-primary'};
            else
                buttons[i]={text: params['buttons'][i], click: button_handler, class: 'button button-large'};
        }

        $popup.dialog({
            autoOpen: false,
            closeOnEscape: false,
            position: 'center',
            resizable: params['resizable'],
            width:params['width'],
            height:params['height'],
            modal: params['modal'],
            title: params['title'],
            //show: {effect:'drop', duration:400, direction:'up'},
            //hide: 300,
            dialogClass: 'wp-dialog',
            zIndex: 300000,
            buttons: buttons
        });

        $widget=$popup.dialog('widget');
        if (params['class'] && ''!=params['class'])
            $widget.addClass(params['class']);
        $popup.dialog('open');
        return $popup;
    }

    function _alert(params)
    {
        if (!params) params={};

        params=$.extend({
            'width': 400,
            'height': 'auto',
            'modal': true,
            'resizable': false,
            'callback': false,
            'class': '',
            'title': '',
            'message': 'Alert',
            'buttons':[ 'OK'],
            'primary': 'OK'
        }, params);

        var content='<div class="_dialog_alert">\
						<div class="ui-icon ui-icon-alert">\
							<p>'+params['message']+'</p>\
						</div>\
                    </div>',
            $popup=$(content).appendTo($('body')), $widget;


        var buttons=[], i, l,
            button_handler=function(e) {

                var button=$(e.target).text();
                $(this).dialog( "close" );
                if (params['callback'])
                    params['callback'].call(this, button);
            };

        for (i=0, l=params['buttons'].length; i<l; i++ )
        {
            if (params['primary'] && params['buttons'][i]==params['primary'])
                buttons[i]={text: params['buttons'][i], click: button_handler, class: 'button button-large button-primary'};
            else
                buttons[i]={text: params['buttons'][i], click: button_handler, class: 'button button-large'};
        }

        $popup.dialog({
            autoOpen: false,
            closeOnEscape: false,
            position: 'center',
            resizable: params['resizable'],
            width:params['width'],
            height:params['height'],
            modal: params['modal'],
            title: params['title'],
            //show: {effect:'blind', duration:400},
            //hide: 300,
            dialogClass: 'wp-dialog',
            zIndex: 300000,
            buttons: buttons
        });

        $widget=$popup.dialog('widget');
        if (params['class'] && ''!=params['class'])
            $widget.addClass(params['class']);
        $popup.dialog('open');
        return $popup;
    }

    function _fadeOut(params)
    {
        if (!params) params={};

        params=$.extend({
            'duration': 1000,
            'width': 240,
            'height': 'auto',
            'resizable': false,
            'class': '',
            'title': '',
            'message': 'Info'
        }, params);

        var content='<div class="_dialog_fadeOut">\
						<div class="ui-icon ui-icon-info">\
							<p>'+params['message']+'</p>\
						</div>\
                    </div>',
            $popup=$(content).appendTo($('body')), $widget;


        $popup.dialog({
            autoOpen: false,
            closeOnEscape: true,
            draggable: false,
            modal: false,
            position: 'center',
            resizable: params['resizable'],
            width:params['width'],
            height:params['height'],
            title: params['title'],
            dialogClass: 'wp-dialog',
            zIndex: 300000,
            buttons: {}
        });

        $widget=$popup.dialog('widget');
        if (params['class'] && ''!=params['class'])
            $widget.addClass(params['class']);

        $popup.dialog('open');
        $widget.delay(params['duration']).animate({top:'-=70', opacity:0}, 'slow', 'linear', function(){
            $(this).remove();
        });
        return $popup;
    }

    // handle pointers
    function _pointer($el, params)
    {
        if (!$el || !$el.length)  return;

        if (!params) params={};

        params=$.extend({
            'class': false,
            'message': '',
            'callback': false,
            'position': {
                edge: 'left',
                align: 'center',
                offset: '15 0'
            }
        }, params);

        var $pointer;

        $el.pointer({
            content: params['message'],
            position: params['position'],

            close: (function($el, params) {
                // closure ;)
                return function() {
                    if (params['callback'] && typeof params['callback'] === "function" )
                        params['callback'].call($el);
                };
            })($el, params),

            open: function( event, t ) { // open with style
                t.pointer.hide().fadeIn('fast');
			}
        });
        $pointer=$el.pointer('widget');
        if (params['class'])
            $pointer.addClass(params['class']);

        $el.pointer('open');
        return $el;
    }

    // export it
    window[thisExportName] = {

        // popups
        Popups : {
            confirm : _confirm,
            alert : _alert,
            flash : _fadeOut,
            pointer: _pointer
        }
    };

})(window, jQuery);
