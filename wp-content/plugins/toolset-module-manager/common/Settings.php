<?php
/**
*
*   Class of Common methods to handle WP functions/settings
*
**/
final class ModMan_Settings
{
    private static $_user_settings=array();
    private static $_global_settings=array();
    
    /**
     * Saves and restores user interface settings stored in a cookie.
     *
     * Checks if the current user-settings cookie is updated and stores it. When no
     * cookie exists (different browser used), adds the last saved cookie restoring
     * the settings.
     *
     * @package WordPress
     * @subpackage Option
     * @since 2.7.0
     */
    public static function userSettings($contexts) 
    {

        if ( ! is_admin() )
            return;

        if ( defined('DOING_AJAX') )
            return;

        if ( ! $user = wp_get_current_user() )
            return;

        if ( is_super_admin( $user->ID ) &&
            ! in_array( get_current_blog_id(), array_keys( get_blogs_of_user( $user->ID ) ) )
            )
            return;

        if (!empty($contexts) && is_array($contexts))
        {
            foreach ($contexts as $context)
            {
                $cookieName=$context . '-' . $user->ID;
                $contextTime=$context . '-time';
                $cookieTimeName=$contextTime . '-' . $user->ID;
                $settings = get_user_option( $context, $user->ID );

                if ( isset( $_COOKIE[$cookieName] ) ) {
                    $cookie = preg_replace( '/[^A-Za-z0-9=&_]/', '', $_COOKIE[$cookieName] );

                    if ( ! empty( $cookie ) && strpos( $cookie, '=' ) ) {
                        if ( $cookie == $settings )
                            return;

                        $last_time = (int) get_user_option( $contextTime, $user->ID );
                        $saved = isset( $_COOKIE[$cookieTimeName]) ? preg_replace( '/[^0-9]/', '', $_COOKIE[$cookieTimeName] ) : 0;

                        if ( $saved > $last_time ) {
                            update_user_option( $user->ID, $context, $cookie, false );
                            update_user_option( $user->ID, $contextTime, time() - 5, false );
                            return;
                        }
                    }
                }
                
                //WP 3.4.2 compatibility where this constant is not yet available                
                
                if (defined('YEAR_IN_SECONDS')){
                	
                	$year_in_seconds_constant=YEAR_IN_SECONDS;
                	
                } else {
                	
                	$year_in_seconds_constant='31536000';
                	
                }
	                setcookie( $cookieName, $settings, time() + $year_in_seconds_constant, SITECOOKIEPATH );
	                setcookie( $cookieTimeName, time(), time() + $year_in_seconds_constant, SITECOOKIEPATH );
 	               $_COOKIE[$cookieName] = $settings;
            }
        }
    }

    public static function globalSettings($contexts) 
    {

        if ( ! is_admin() )
            return;

        if ( defined('DOING_AJAX') )
            return;

        if ( ! $user = wp_get_current_user() )
            return;


        if (!empty($contexts) && is_array($contexts))
        {
            foreach ($contexts as $context)
            {
                $cookieName=$context;
                $contextTime=$context . '-time';
                $cookieTimeName=$contextTime;
                $settings = get_option( $context );

                if ( isset( $_COOKIE[$cookieName] ) ) {
                    $cookie = preg_replace( '/[^A-Za-z0-9=&_]/', '', $_COOKIE[$cookieName] );

                    if ( ! empty( $cookie ) && strpos( $cookie, '=' ) ) {
                        if ( $cookie == $settings )
                            return;

                        $last_time = (int) get_option( $contextTime );
                        $saved = isset( $_COOKIE[$cookieTimeName]) ? preg_replace( '/[^0-9]/', '', $_COOKIE[$cookieTimeName] ) : 0;

                        if ( $saved > $last_time ) {
                            update_option( $context, $cookie, false );
                            update_option( $contextTime, time() - 5, false );
                            return;
                        }
                    }
                }

                setcookie( $cookieName, $settings, time() + YEAR_IN_SECONDS, SITECOOKIEPATH );
                setcookie( $cookieTimeName, time(), time() + YEAR_IN_SECONDS, SITECOOKIEPATH );
                $_COOKIE[$cookieName] = $settings;
            }
        }
    }
    
    /**
     * Retrieve user interface setting value based on setting name.
     *
     * @package WordPress
     * @subpackage Option
     * @since 2.7.0
     *
     * @param string $name The name of the setting.
     * @param string $default Optional default value to return when $name is not set.
     * @return mixed the last saved user setting or the default value/false if it doesn't exist.
     */
    public static function getUserSetting( $context, $name, $default = false ) 
    {
        $all = self::getAllUserSettings($context);

        return isset($all[$name]) ? $all[$name] : $default;
    }

    public static function getGlobalSetting( $context, $name, $default = false ) 
    {
        $all = self::getAllGlobalSettings($context);

        return isset($all[$name]) ? $all[$name] : $default;
    }
    
    /**
     * Add or update user interface setting.
     *
     * Both $name and $value can contain only ASCII letters, numbers and underscores.
     * This function has to be used before any output has started as it calls setcookie().
     *
     * @package WordPress
     * @subpackage Option
     * @since 2.8.0
     *
     * @param string $name The name of the setting.
     * @param string $value The value for the setting.
     * @return bool true if set successfully/false if not.
     */
    public static function setUserSetting( $context, $name, $value ) 
    {
        if ( headers_sent() )
            return false;

        $all = self::getAllUserSettings($context);
        $name = preg_replace( '/[^A-Za-z0-9_]+/', '', $name );

        if ( empty($name) )
            return false;

        $all[$name] = $value;

        return self::setAllUserSettings($context, $all);
    }

    public static function setGlobalSetting( $context, $name, $value ) 
    {
        if ( headers_sent() )
            return false;

        $all = self::getAllGlobalSettings($context);
        $name = preg_replace( '/[^A-Za-z0-9_]+/', '', $name );

        if ( empty($name) )
            return false;

        $all[$name] = $value;

        return self::setAllGlobalSettings($context, $all);
    }
    
    /**
     * Delete user interface settings.
     *
     * Deleting settings would reset them to the defaults.
     * This function has to be used before any output has started as it calls setcookie().
     *
     * @package WordPress
     * @subpackage Option
     * @since 2.7.0
     *
     * @param mixed $names The name or array of names of the setting to be deleted.
     * @return bool true if deleted successfully/false if not.
     */
    public static function deleteUserSetting( $context, $names ) 
    {
        if ( headers_sent() )
            return false;

        $all = self::getAllUserSettings($context);
        $names = (array) $names;

        foreach ( $names as $name ) {
            if ( isset($all[$name]) ) {
                unset($all[$name]);
                $deleted = true;
            }
        }

        if ( isset($deleted) )
            return self::setAllUserSettings($context, $all);

        return false;
    }

    public static function deleteGlobalSetting( $context, $names ) 
    {
        if ( headers_sent() )
            return false;

        $all = self::getAllGlobalSettings($context);
        $names = (array) $names;

        foreach ( $names as $name ) {
            if ( isset($all[$name]) ) {
                unset($all[$name]);
                $deleted = true;
            }
        }

        if ( isset($deleted) )
            return self::setAllGlobalSettings($context, $all);

        return false;
    }
    
    /**
     * Retrieve all user interface settings.
     *
     * @package WordPress
     * @subpackage Option
     * @since 2.7.0
     *
     * @return array the last saved user settings or empty array.
     */
    public static function getAllUserSettings($context) 
    {
        if ( ! $user = wp_get_current_user() )
            return array();

        if ( isset(self::$_user_settings[$context]) && is_array(self::$_user_settings[$context]) )
            return self::$_user_settings[$context];

        $cookieName=$context . '-' . $user->ID;
        $contextTime=$context . '-time';
        $cookieTimeName=$contextTime . '-' . $user->ID;
        
        $all = array();
        if ( isset($_COOKIE[$cookieName]) ) {
            $cookie = preg_replace( '/[^A-Za-z0-9=&_]/', '', $_COOKIE[$cookieName] );

            if ( $cookie && strpos($cookie, '=') ) // the '=' cannot be 1st char
                parse_str($cookie, $all);

        } else {
            $option = get_user_option($context, $user->ID);
            if ( $option && is_string($option) )
                parse_str( $option, $all );
        }

        return $all;
    }

    public static function getAllGlobalSettings($context) 
    {
        if ( ! $user = wp_get_current_user() )
            return array();
        
        if ( isset(self::$_global_settings[$context]) && is_array(self::$_global_settings[$context]) )
            return self::$_global_settings[$context];

        $cookieName=$context;
        $contextTime=$context . '-time';
        $cookieTimeName=$contextTime;
        
        $all = array();
        if ( isset($_COOKIE[$cookieName]) ) {
            $cookie = preg_replace( '/[^A-Za-z0-9=&_]/', '', $_COOKIE[$cookieName] );

            if ( $cookie && strpos($cookie, '=') ) // the '=' cannot be 1st char
                parse_str($cookie, $all);

        } else {
            $option = get_option($context);
            if ( $option && is_string($option) )
                parse_str( $option, $all );
        }

        return $all;
    }
    
    /**
     * Private. Set all user interface settings.
     *
     * @package WordPress
     * @subpackage Option
     * @since 2.8.0
     *
     * @param unknown $all
     * @return bool
     */
    private static function setAllUserSettings($context, $all) 
    {
        if ( ! $user = wp_get_current_user() )
            return false;

        if ( is_super_admin( $user->ID ) &&
            ! in_array( get_current_blog_id(), array_keys( get_blogs_of_user( $user->ID ) ) )
            )
            return;

        $cookieName=$context . '-' . $user->ID;
        $contextTime=$context . '-time';
        $cookieTimeName=$contextTime . '-' . $user->ID;
        self::$_user_settings[$context] = $all;
        $settings = '';
        foreach ( $all as $k => $v ) {
            $v = preg_replace( '/[^A-Za-z0-9_]+/', '', $v );
            $settings .= $k . '=' . $v . '&';
        }

        $settings = rtrim($settings, '&');

        update_user_option( $user->ID, $context, $settings, false );
        update_user_option( $user->ID, $contextTime, time(), false );

        return true;
    }

    private static function setAllGlobalSettings($context, $all) 
    {
        if ( ! $user = wp_get_current_user() )
            return false;
        
        $cookieName=$context;
        $contextTime=$context . '-time';
        $cookieTimeName=$contextTime;
        self::$_global_settings[$context] = $all;
        $settings = '';
        foreach ( $all as $k => $v ) {
            $v = preg_replace( '/[^A-Za-z0-9_]+/', '', $v );
            $settings .= $k . '=' . $v . '&';
        }

        $settings = rtrim($settings, '&');

        update_option( $context, $settings, false );
        update_option( $contextTime, time(), false );

        return true;
    }
    
    /**
     * Delete the user settings of the current user.
     *
     * @package WordPress
     * @subpackage Option
     * @since 2.7.0
     */
    public static function deleteAllUserSettings($context) 
    {
        if ( ! $user = wp_get_current_user() )
            return;

        $cookieName=$context . '-' . $user->ID;
        $contextTime=$context . '-time';
        $cookieTimeName=$contextTime . '-' . $user->ID;
        update_user_option( $user->ID, $context, '', false );
        setcookie($cookieName, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH);
    }
    
    public static function deleteAllGlobalSettings($context) 
    {
        if ( ! $user = wp_get_current_user() )
            return;
        
        $cookieName=$context;
        $contextTime=$context . '-time';
        $cookieTimeName=$contextTime;
        update_option( $context, '', false );
        setcookie($cookieName, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH);
    }
}
