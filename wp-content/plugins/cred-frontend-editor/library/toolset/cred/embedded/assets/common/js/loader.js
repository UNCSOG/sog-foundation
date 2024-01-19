(function(window, assets_path) {
/*
*   Dynamic js/css loader module
*
*/	
    // definitions here
    var assets = {
        js : {
            asset1:{
                path:'js/jquery.draggable.js',
                dependencies:['panel','linkbutton']
            }
        },
        css : {
            asset2:{
                path:'css/tabs.css',
                dependencies:['panel','linkbutton']
            }
        }
	};
	
	
	var queues = {}, _loader;
	
	// methods, private
    
    // load javascript
    function loadJs(url, callback)
    {
		var done = false, script = document.createElement('script');
		
        script.type = 'text/javascript';
		script.language = 'javascript';
		script.src = url;
		script.onload = script.onreadystatechange = function() {
			if (!done && (!script.readyState || script.readyState == 'loaded' || script.readyState == 'complete'))
            {
				done = true;
				script.onload = script.onreadystatechange = null;
				if (callback) 
                {
					callback.call(script);
				}
			}
		}
		// load it
        document.getElementsByTagName("head")[0].appendChild(script);
	}
	
	function runJs(url, callback)
    {
		loadJs(url, function() {
			document.getElementsByTagName("head")[0].removeChild(this);
			if (callback){
				callback();
			}
		});
	}
	
	// load css
    function loadCss(url, callback)
    {
		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.type = 'text/css';
		link.media = 'screen';
		link.href = url;
		// load it
        document.getElementsByTagName('head')[0].appendChild(link);
		if (callback)
        {
			callback.call(link);
		}
	}
	
	// load a single asset
    function loadSingle(qname, callback)
    {
		var parts = qname.split('/', 2), type=(parts[0])?parts[0]:'undefined', name=(parts[1])?parts[1]:'undefined', asset,
            jsStatus = 'loading', cssStatus = 'loading', url;
        
        if (assets[type] && assets[type][name])
        {
            queues[qname] = 'loading';
            asset=assets[type][name];
            
            if ('css'==type)
            {
                if (/^http/i.test(asset['path']))
                {
                    url = asset['path'];
                } 
                else 
                {
                    url = _loader.base + '/' + asset['path'];
                }
                loadCss(url, function() {
                    cssStatus = 'loaded';
                    if (/*jsStatus == 'loaded' &&*/ cssStatus == 'loaded') {
                        finish();
                    }
                });
            }
            else if ('js'==type)
            {
                if (/^http/i.test(asset['path']))
                {
                    url = asset['path'];
                } 
                else 
                {
                    url = _loader.base + '/' + asset['path'];
                }
                loadJs(url, function() {
                    jsStatus = 'loaded';
                    if (jsStatus == 'loaded' /*&& cssStatus == 'loaded'*/) {
                        finish();
                    }
                });
            }
            
            function finish()
            {
                queues[qname] = 'loaded';
                _loader.onProgress(qname);
                if (callback) 
                {
                    callback();
                }
            }
        }
	}
	
	function loadAsset(qname, callback)
    {
		var aa = new Array(), doLoad = false;
		
		if (typeof qname == 'string')
        {
			add(qname);
		} 
        else 
        {
			for(var i=0, l=qname.length; i<l; i++)
            {
				add(qname[i]);
			}
		}
		
		function add(qname)
        {
            var parts = qname.split('/', 2), type=(parts[0])?parts[0]:'undefined', name=(parts[1])?parts[1]:'undefined', asset, dep;
        
        if (!assets[type] || !assets[type][name]) return;
            
            asset=assets[type][name];
			dep = asset['dependencies'];
            
			if (dep)
            {
				for(var i=0, l=dep.length; i<l; i++)
                {
					add(dep[i]);
				}
			}
			aa.push(qname);
		}
		
		function finish()
        {
			if (callback)
            {
				callback();
			}
			_loader.onLoad(qname);
		}
		
		var time = 0;
        
		function loadNext()
        {
			if (aa.length)
            {
				var a = aa[0];	// the first module
				if (!queues[a])
                {
					doLoad = true;
					loadSingle(a, function(){
						aa.shift();
						loadNext();
					});
				} 
                else if (queues[a] == 'loaded')
                {
					aa.shift();
					loadNext();
				} 
                else 
                {
					if (time < _loader.timeout)
                    {
						time += 10;
						setTimeout(arguments.callee, 10);
					}
				}
			} 
            else 
            {
                finish();
            }
		}
		
		loadNext();
	}
	
	_loader = {
		base:assets_path,
        thisbase:'',
		timeout:2000,
	
		isLoaded : function(qname) {
            if (queues && queues[qname] && 'loaded'==queues[qname])
                return true;
            return false;
        },
        load: function(qname, callback) {
			if (/\.css$/i.test(qname))
            {
				if (/^http/i.test(qname))
                {
					loadCss(qname, callback);
				} 
                else 
                {
					loadCss(_loader.base + '/' + qname, callback);
				}
			} 
            else if (/\.js$/i.test(qname))
            {
				if (/^http/i.test(qname))
                {
					loadJs(qname, callback);
				} 
                else 
                {
					loadJs(_loader.base + '/' + qname, callback);
				}
			} 
            else 
            {
				loadAsset(qname, callback);
			}
		},
		
		onProgress: function(name){},
		onLoad: function(name){}
	};

	var scripts = document.getElementsByTagName('script');
	for(var i=0, l=scripts.length; i<l; i++)
    {
		var src = scripts[i].src;
		if (!src) continue;
		var m = src.match(/loader\.js(\W|$)/i);
		if (m)
        {
			_loader.thisbase = src.substring(0, m.index);
		}
	}

	window.using = _loader.load;
	
})(window, '.');
