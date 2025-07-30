jQuery(function($) {
    var video_x = null;
    var video_y = null;
    var video_x_return = null;
    var video_y_return = null;

    if (document.addEventListener){
        document.addEventListener('webkitfullscreenchange', fsHandler, false);
        document.addEventListener('mozfullscreenchange', fsHandler, false);
        document.addEventListener('fullscreenchange', fsHandler, false);
        document.addEventListener('MSFullscreenChange', fsHandler, false);
        document.addEventListener('mousemove', onMouseUpdate, false);
    }


    function onMouseUpdate(e) {
        video_x = e.pageX;
        video_y = e.pageY;
    }

    function fsHandler(){
        if (document.webkitIsFullScreen || document.mozFullScreen || document.msFullscreenElement !== null){
            if (!window.screenTop && !window.screenY) {
                video_x_return = video_x;
                video_y_return = video_y;
            } else {

                if((document.body.scrollHeight-video_y-400) <= 0){
                    window.scroll(video_x,video_y);
                } else {
                    window.scroll(0,window.pageYOffset);
                }

                video_x_return = null;
                video_y_return = null;
            }
        }
    }
});
