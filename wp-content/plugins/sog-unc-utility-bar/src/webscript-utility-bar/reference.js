import './reference.scss';
import './index.js';//we need the utility bar code to run the demo

window.addEventListener('load', (event) => {
	const buttons = document.querySelectorAll('.color-btn');//get all the buttons
	const ub = document.getElementById('unc-utility-bar');//get the utility var

	//all the buttons
	const buttonList = [...buttons].map((el) => {
		
		//on button click
		el.addEventListener('click', (e) => {
			//set the color based on data-color
			const color = e?.target?.dataset?.color;
			if( !ub || !color ) return;

			//set the bar class to the color
			ub.className = color;
			
			//set the background color if the bar is white
			if ('white' == color) {
				document.body.classList.add('blue');
			} else {
				if (document.body.classList.contains('blue')) {
					document.body.classList.remove('blue');
				}
			}

			//set the pre for copying
			document.getElementById('ub-code').innerHTML =
				'&lt;script type="text/javascript" id="unc-ub-script" data-color="' +
				color +
				'" src="https://unc.edu/web-assets/utility-bar/utility-bar.min.js"&gt;&lt;/script&gt;';
		})
	});

	
	const copyButton = document.getElementById('copy-code');//get the copy button
	const pre = document.getElementById('ub-code')//get the pre
	//only if those are here
	if (copyButton && pre) {
		copyButton.addEventListener('click', (e) => {
			//get the text
			const textToCopy = pre.textContent;
			// Use the Clipboard API to write text
			navigator.clipboard
				.writeText(textToCopy)
				.then(function () {
					alert('utility bar embed code copied to clipboard!');
				})
				.catch(function (err) {
					console.error('Error copying text: ', err);
					alert('Failed to copy utility bar embed code.');
				});
		});
	}
});



