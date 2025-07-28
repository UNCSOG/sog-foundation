(function($) {
    $(document).ready(function() {
        // Add the lightbox container to the body if it doesn't exist
        if ($('#wpcb-lightbox-container').length === 0) {
            $('body').append(`
                <div id="wpcb-lightbox-container" style="display:none;">
                    <div class="wpcb-lightbox-overlay"></div>
                    <div class="wpcb-lightbox-content">
                        <div class="wpcb-lightbox-close">&times;</div>
                        <div class="wpcb-lightbox-video-wrapper">
                            <div class="wpcb-lightbox-video-container"></div>
                        </div>
                    </div>
                </div>
            `);

            // Add responsive styles for the lightbox
            $('<style>')
                .text(`
                    #wpcb-lightbox-container {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 999999;
                    }
                    .wpcb-lightbox-overlay {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.8);
                    }
                    .wpcb-lightbox-content {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background-color: #000;
                        border-radius: 5px;
                        width: 90%;
                        max-width: 900px; /* Maximum width of the lightbox */
                    }
                    .wpcb-lightbox-close {
                        position: absolute;
                        top: -20px;
                        right: -20px;
                        color: white;
                        font-size: 24px;
                        cursor: pointer;
                        background: #122031;
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 1;
                    }
                    /* Responsive video wrapper with 16:9 aspect ratio */
                    .wpcb-lightbox-video-wrapper {
                        position: relative;
                        width: 100%;
                        padding-bottom: 56.25%; /* 16:9 aspect ratio (9/16 = 0.5625) */
                        height: 0;
                        overflow: hidden;
                    }
                    .wpcb-lightbox-video-container {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                    }
                    .wpcb-lightbox-video-container iframe {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        border: 0;
                    }
                `)
                .appendTo('head');
        }

        // Handle lightbox opening
        $('.wpcb-text-link[data-type="lightbox"]').on('click', function(e) {
            e.preventDefault();

            // Get the video URL and ensure it has the correct parameters for embedding
            let videoUrl = $(this).attr('href');

            // Add any necessary YouTube parameters
            if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                // Ensure we have autoplay and remove any size restrictions
                if (videoUrl.includes('?')) {
                    if (!videoUrl.includes('autoplay=1')) {
                        videoUrl += '&autoplay=1';
                    }
                } else {
                    videoUrl += '?autoplay=1';
                }

                // Add additional YouTube parameters for better embedding
                videoUrl += '&rel=0&showinfo=0';
            }

            // Create iframe for YouTube
            const iframe = $('<iframe>')
                .attr('src', videoUrl)
                .attr('frameborder', '0')
                .attr('allowfullscreen', 'true')
                .attr('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');

            // Add iframe to container and show lightbox
            $('.wpcb-lightbox-video-container').empty().append(iframe);
            $('#wpcb-lightbox-container').fadeIn();
        });

        // Handle lightbox closing
        $(document).on('click', '.wpcb-lightbox-close, .wpcb-lightbox-overlay', function() {
            // Empty the video container to stop video playback
            $('.wpcb-lightbox-video-container').empty();
            $('#wpcb-lightbox-container').fadeOut();
        });

        // Close on escape key
        $(document).keyup(function(e) {
            if (e.key === "Escape" && $('#wpcb-lightbox-container').is(':visible')) {
                $('.wpcb-lightbox-video-container').empty();
                $('#wpcb-lightbox-container').fadeOut();
            }
        });

        // Ad hide/close
        $(document).on('click', '.wpcb-ad-close', function(e) {
            e.preventDefault();

            const adContainer = $(this).closest('.wpcb-ad-container');
            const adId = adContainer.data('ad-id');

            // Fade out and remove the ad
            adContainer.fadeOut(300, function() {
                $(this).remove();
            });

            // Save the dismissal state via AJAX
            $.ajax({
                url: ajaxurl, // WordPress AJAX URL is already defined
                type: 'POST',
                data: {
                    action: 'wpae_dismiss_ad',
                    ad_id: adId,
                    nonce: wpaeAdsDismiss.nonce
                },
                success: function(response) {
                    if (response.success) {
                        //console.log('Ad dismissed successfully');
                    }
                }
            });
        });

    });
})(jQuery);