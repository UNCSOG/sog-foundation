<?php

class WPV_ViewsHelpVideos extends Toolset_HelpVideosFactoryAbstract{
    protected function define_toolset_videos(){
        $videos = array(
            'views_template' =>  array(
                'name' => 'views_template',
                'url' => 'https://www.youtube.com/watch?v=ylZD-sfXTbs&yt:cc=on',
                'screens' => array('toolset_page_ct-editor'),
                'element' => '.toolset-video-box-wrap',
                'title' => __('Creating a template with Views', 'wpv-views'),
                'width' => '900px',
                'height' => '506px'
            ),
            'archive_views' =>  array(
                'name' => 'views_archives',
                'url' => 'https://www.youtube.com/watch?v=JXKq95V-pJI&yt:cc=on',
                'screens' => array('toolset_page_view-archives-editor'),
                'element' => '.toolset-video-box-wrap',
                'title' => __('Creating an archive with WordPress Archive', 'wpv-views'),
                'width' => '900px',
                'height' => '506px'
            ),
            'views_view' =>  array(
                'name' => 'views_view',
                'url' => 'https://www.youtube.com/watch?v=xgOO_Lsfny4&yt:cc=on',
                'screens' => array('toolset_page_views-editor'),
                'element' => '.toolset-video-box-wrap',
                'title' => __('Creating and displaying a View', 'wpv-views'),
                'width' => '900px',
                'height' => '506px'
            )
        );

	    // Avada / Divi adjustments
        if( defined( 'AVADA_VERSION') || defined( 'ET_CORE' ) ) {
	        // disable CT and WPA video
        	unset( $videos['views_template'] );
        	unset( $videos['archive_views'] );
        }

        return $videos;
    }
}
add_action( 'init', array("WPV_ViewsHelpVideos", "getInstance"), 9 );