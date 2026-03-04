import utilityBarTemplate from '../../templates/utility-bar.html';

function loadUtilityStyles() {
	const scriptSrc = document.currentScript.src;
	const cssSrc =  scriptSrc.replace('.js','.css',)
	console.log();
	var headId = document.getElementsByTagName('head')[0];
	var cssNode = document.createElement('link');
	cssNode.type = 'text/css';
	cssNode.rel = 'stylesheet';
	//cssNode.href = 'https://unc.edu/web-assets/utility-bar/utility-bar.min.css';
	//cssNode.href = 'https://sites.test/wp-content/plugins/unc-utility-bar/build/utility-bar/utility-bar.min.css';
	cssNode.href = cssSrc;
	cssNode.media = 'screen';

	headId.appendChild(cssNode);
}

function delayLoad(fn) {
	if (document.readyState != 'loading') {
		fn();
	} else if (document.addEventListener) {
		document.addEventListener('DOMContentLoaded', fn);
	} else {
		document.attachEvent('onreadystatechange', function () {
			if (document.readyState != 'loading') fn();
		});
	}
}

function insertUtilityBar(color) {
	switch (color) {
		case 'blue':
		case 'white':
		case 'navy':
		case 'black':
        case 'dark-gray':
		case 'gray':
			color = color;
			break;
		default:
			color = 'dark-gray';
	}

	var utilityBar = document.createElement('div');
	utilityBar.setAttribute('id', 'unc-utility-bar');
	utilityBar.setAttribute('class', color);
	//utilityBar.setAttribute('style', 'display:none;');
	utilityBar.innerHTML = utilityBarTemplate;
	//console.log(utilityBar.outerHTML);
	document.body.insertAdjacentHTML('afterbegin', utilityBar.outerHTML);
}

loadUtilityStyles();
delayLoad(function () {
	var script = document.getElementById('unc-ub-script');
	if (script) {
		var color = script.getAttribute('data-color');
	}
	insertUtilityBar(color);
});
