<?php

final class WPDD_Layouts_Cache_Singleton
{
    /**
     * Instance of this class
     *
     * @var object
     * @access private
     */
    private static $oInstance = false;

    /**
     * cache information
     *
     * @var object
     * @access private
     */
    private static $cache = array();

    /**
     * Return instance of this object
     *
     * @return Singleton
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if( self::$oInstance == false ) {
            self::$oInstance = new WPDD_Layouts_Cache_Singleton();
            self::$cache['by_id'] = array();
            self::$cache['by_name'] = array();
            //self::cache_published_layouts();
        }
        return self::$oInstance;
    }

    public static function get_id_by_name( $name )
    {
        if ( empty($name) ) {
            return 0;
        }
        /**
         * check in cache
         */
        if ( isset(self::$cache['by_name'][$name] ) ) {
            return self::$cache['by_name'][$name];
        }
        return self::get($name, 'post_name');
    }

    public static function get_name_by_id( $id, $is_private = false )
    {
        $id = intval($id);
        if ( empty($id) ) {
            return 0;
        }
        if ( isset(self::$cache['by_id'][$id] ) ) {
            return self::$cache['by_id'][$id];
        }
        /**
         * try to get from database
         */
        self::get($id, 'ID');

        return isset(self::$cache['by_id'][$id]) ? self::$cache['by_id'][$id] : null;

    }

    public static function get_published_layouts()
    {
        return self::cache_published_layouts(true);
    }

    private static function cache_published_layouts($return = false )
    {
        global $wpdb;

        $end_results = array();

        $results = $wpdb->get_results( $wpdb->prepare("SELECT ID, post_name, post_title FROM {$wpdb->posts} WHERE post_type=%s AND post_status in ('publish')", WPDDL_LAYOUTS_POST_TYPE) );
        if ( $results ) {
            foreach( $results as $one ) {
                $end_results[] = $one;
                self::set($one->ID, $one->post_name, $one->post_title);
            }
        }
        return $end_results;

    }

	public static function get_published_layouts_with_all_data()
	{
		global $wpdb;
		$end_results = array();
		$prepare_query = $wpdb->prepare("SELECT ID, post_name, post_title, 
										(SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND meta_key = %s  LIMIT 1) as layout_settings
										FROM {$wpdb->posts} WHERE post_type=%s AND post_status in ('publish')", WPDDL_LAYOUTS_SETTINGS, WPDDL_LAYOUTS_POST_TYPE);

		$results = $wpdb->get_results( $prepare_query );

		return $results;

	}

    private static function set( $id = false, $post_name = false, $post_title = false )
    {
        if ( $id && $post_name ) {
            self::$cache['by_name'][$post_name] = $id;
            self::$cache['by_id'][$id] = $post_name;
            if ( $post_title ) {
                self::$cache['by_name'][$post_title] = $id;
            }
        }
    }

    private static function get($value, $field_name)
    {
        global $wpdb;
        $query = sprintf(
            'SELECT ID, post_title, post_name FROM %s WHERE post_type = %%s AND %s = %s LIMIT 1',
            $wpdb->posts,
            $field_name,
            'ID' == $field_name? '%d':'%s'
        );
        $sql = $wpdb->prepare($query, WPDDL_LAYOUTS_POST_TYPE, $value);
        $results = $wpdb->get_row($sql);
        if ( $results ) {
            self::set($results->ID, $results->post_name, $results->post_title);
            return $results->ID;
        }
        return 0;
    }

    private function __construct() {}
}

