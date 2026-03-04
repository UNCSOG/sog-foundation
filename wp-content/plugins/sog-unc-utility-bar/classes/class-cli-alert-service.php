<?php
/**
 * CLI Commands for working with the Alerts
 * 
 * 
 * @package Alert_Service
 * 
 */
namespace Utility_Bar;
use \WP_CLI;

class CLI_Alert_Service{

    
    /**
     * Publish the Current Alert Message
     * 
     * ## EXAMPLES
     *
     *      wp alert-service publish
     *
     * @when after_wp_load
     */

    public function publish( $args,  $flags ){
        $outcome = Alert_Banner_Storage::publish_stored_alert();

        if( $outcome ){
            WP_CLI::success(  "Alert Message is now Published" );
        } else {
            WP_CLI::warning( "error publishing the Alert message" );
        }
    }

    /**
     * Unpublish the Current Alert Message
     * 
     * ## EXAMPLES
     *
     *      wp alert-service unpublish
     *
     * @when after_wp_load
     */
    public function unpublish( $args,  $flags ){
        $outcome = Alert_Banner_Storage::unpublish_stored_alert();

        if( $outcome ){
            WP_CLI::success(  "Alert Message is now Unpublished" );
        } else {
            WP_CLI::warning( "error unpublishing the Alert message" );
        }
    }

    /**
     * Check the Publish Status of the Current Alert Message
     * 
     * ## EXAMPLES
     *
     *      wp alert-service status
     *
     * @when after_wp_load
     */
    public function status(){
        if( 'published' === Alert_Banner_Storage::get_published_status() ){
            WP_CLI::log( 'The Alert is Published' );
        } elseif( 'unpublished' === Alert_Banner_Storage::get_published_status() ){
            WP_CLI::log( 'The Alert is Not Published' );
        }
       
    }
}