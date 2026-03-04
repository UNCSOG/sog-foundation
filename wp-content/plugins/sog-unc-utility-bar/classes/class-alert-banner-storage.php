<?php
/**
 * Utilities to store and manage the Alert
 * handled differently if it's on a multisite
 * 
 * 
 * @package Alert_Service
 * 
 */
namespace Utility_Bar;

Class Alert_Banner_Storage{

    /**
     * the key for the metadata as it is stored in the database
     *
     * @var string
     */
    public static $stored_alert_meta_key = 'alert_service_stored_alert';

    /**
     * the key for if the alert is published or not
     *
     * @var string
     */
    public static $published_meta_key = 'alert_service_alert_published';

    /**
     * store the alert 
     *
     * @return void
     */
    public static function store_alert( Cap $cap ){
        $cap_array = $cap->get_cap_array();
        $update = false;
        if( is_multisite() ){
            //error_log( 'is multisite' );
            $update = update_network_option( null, self::$stored_alert_meta_key,  $cap_array);
        } else {
            $update = update_option( self::$stored_alert_meta_key,  $cap_array );
        }

        if( $update ){
            //clear the cache
            if ( function_exists( 'pantheon_clear_edge_all' ) ) {
				\pantheon_clear_edge_all();
			}
			wp_cache_flush();
            //error_log( 'alert stored ' . self::$stored_alert_meta_key );
        } else {
            //error_log( 'alert not stored');
        }

        return $update;
    }

     /**
     * gets the current stored alart
     *
     * @return void
     */
    public static function get_stored_alert(){

        if( is_multisite() ){
            //error_log( 'is multisite' );
            $stored_alert = get_network_option( null, self::$stored_alert_meta_key,  'not initialized' );
        } else {
            $stored_alert = get_option( self::$stored_alert_meta_key,  'not initialized' );
        }


        if( 'not initialized' == $stored_alert ){
            //we need to initialize the option
            $add = false;
            if( is_multisite() ){
                //error_log( 'is multisite' );
                $add = add_network_option( null, self::$stored_alert_meta_key,  'empty' );
            } else {
                $add = add_option( self::$stored_alert_meta_key, 'empty' );
            }

            if( !$add ){
                //error_log( 'unable to initialize the option:' . self::$stored_alert_meta_key );
            }
        }

        return $stored_alert;
    }

    /**
     * set the published flag to true if it's not already true
     *
     * @return void
     */
    public static function publish_stored_alert(){
        return self::changed_published( true );
    }

    /**
     * set the published flag to false if it's not already false
     *
     * @return void
     */
    public static function unpublish_stored_alert(){
        return self::changed_published( false );
    }

    /**
     * change the published state to true or false
     *
     * @param [bool] $published
     * @return void
     */
    private static function changed_published( bool $published ){
        $update = false;

        if( false === $published ){
            $value = 'unpublished';
        } else if( true === $published ){
            $value = 'published';
        }

        if( $value === self::get_published_status() ){
            return true;
        }

        if( $value ){
            if( is_multisite() ){
                //error_log( 'is multisite' );
                $update = update_network_option( null, self::$published_meta_key,  $value );
            } else {
                $update = update_option( self::$published_meta_key,  $value );
            }
        } else {
            return false;
        }

        if( $update ){
            //error_log( 'published changed to ' . $value . ' on ' . self::$published_meta_key );
            //clear the cache
            if ( function_exists( 'pantheon_clear_edge_all' ) ) {
				\pantheon_clear_edge_all();
			}
			wp_cache_flush();
        } else {
            //error_log( 'published not changed');
        }

        return $update;

    }

   
    /**
     * get the current status of the alert
     *
     * @return [string] $published
     */
    public static function get_published_status(){

        if( is_multisite() ){
            //error_log( 'is multisite' );
            $published = get_network_option( null, self::$published_meta_key, 'not initialized' );
        } else {
            $published = get_option( self::$published_meta_key, 'not initialized' );
        }

        if( 'not initialized' == $published ){
            //we need to initialize the option
            $add = false;
            if( is_multisite() ){
                //error_log( 'is multisite' );
                $add = add_network_option( null, self::$published_meta_key,  'unpublished' );
            } else {
                $add = add_option( self::$published_meta_key, 'unpublished' );
            }

            if( !$add ){
                //error_log( 'unable to initialize the option:' . self::$published_meta_key );
            }

            $published = $add;
        }

        //error_log( 'published_status :' . var_export( $published, true ) );

        return $published;

    }

}