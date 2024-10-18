/**
 * makes small adjustments to make things compatible with Divi theme
 * in this case Video iframe dimensions for Video cell and embedded media
 */

( function( $ ) {
    $(function(){

        var settings = {
              customSelector: null,
              ignore: null
            };

         var selectors = [
            'iframe[src*="player.vimeo.com"]',
            'iframe[src*="youtube.com"]',
            'iframe[src*="youtube-nocookie.com"]',
            'iframe[src*="kickstarter.com"][src*="video.html"]',
            'object',
            'embed'
              ];

        if (settings.customSelector) {
            selectors.push(settings.customSelector);
              }

              var ignoreList = '.fitvidsignore';

              var $allVideos = $(this).find(selectors.join(','));
        $allVideos = $allVideos.not('object object'); // SwfObj conflict patch
              $allVideos = $allVideos.not(ignoreList); // Disable FitVids on this video.

        $allVideos.each(function(count){
            var $this = $(this);
            $this.unwrap();
            $this.wrap('<div class="fluid-width-video-wrapper"></div>').parent('.fluid-width-video-wrapper').css('padding-top', '56.25%');


        });

    });
} )( jQuery );