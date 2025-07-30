import './cookie-banner.scss'; // Import SCSS styles
import domReady from '@wordpress/dom-ready';

domReady( function () {
    function createCookieBanner() {
        var cookieBannerText = $('<div class="cookie-banner-text"></div>')
            .html(JSON.parse(cookieBannerSettings.text));

        var commonButtonClass = 'cookie-banner-button';

        var acceptButton = $('<button>', {
            'class': 'cookie-banner-button cookie-accept-button',
            'aria-label': 'Accept cookies and close the banner',
            'text': cookieBannerSettings.buttonText
        });

        if (document.cookie.indexOf('cookie_accepted=1') === -1) {
            var cookieBanner = $('<section id="cookie-banner" aria-label="Cookie Consent Banner"></section>');
            var containerDiv = $('<div id="container-cookie"></div>');


            $('body').prepend(cookieBanner);
            cookieBanner.append(containerDiv);
            containerDiv.append(cookieBannerText, acceptButton);
            cookieBanner.fadeIn(500);
        }

        acceptButton.on('click', function()  {
            cookieBanner.slideUp(500, function() {
                cookieBanner.remove();
            });
            var expirationDate = new Date();
            expirationDate.setDate(expirationDate.getDate() + 60);
            document.cookie = 'cookie_accepted=1; expires=' + expirationDate.toUTCString() + '; path=/';
        });

        var privacyPolicyURL = cookieBannerSettings.privacyPolicyURL || null;
        if (privacyPolicyURL) {
            var privacyPolicyButton = $('<button>', {
                'type': 'button',
                'class': commonButtonClass + ' privacy-policy-button',
                'text': 'Privacy Policy',
                'aria-label': 'Privacy Policy'
            });

            privacyPolicyButton.on('click', function() {
                window.open(privacyPolicyURL, '_blank');
            });

            cookieBannerText.append(privacyPolicyButton);
        }
    }

    createCookieBanner();
});
