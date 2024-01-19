<?php

/*
* Access Cacher
*
* Access caching object, to avoid duplicating queries and calculations.
*
* @since 2.2 
*/

class Access_Cacher {
	
	static $stored_cache;
	
	public static function init() {
		self::$stored_cache = array();
		
		// Cache invalidation
		//Views
		add_action( 'wpv_action_wpv_save_item', array( 'Access_Cacher', 'delete_views_cache' ) );
		add_action( 'delete_post', array( 'Access_Cacher', 'delete_views_cache' ) );

		//Layouts
		add_action( 'ddl_action_layout_has_been_saved', array( 'Access_Cacher', 'delete_layouts_cache' ) );
		add_action( 'ddl_layout_has_been_deleted', array( 'Access_Cacher', 'delete_layouts_cache' ) );
	}
	
	public static function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		return Access_Cacher::set( $key, $data, $group, (int) $expire );
	}
	
	public static function delete( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		$stored_cache = self::$stored_cache;
		unset( $stored_cache[ $group ][ $key ] );
		self::$stored_cache = $stored_cache;
		return true;
	}
	
	public static function flush() {
		self::$stored_cache = array();
		return true;
	}
	
	public static function get( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		$stored_cache = self::$stored_cache;
		if ( isset( $stored_cache[ $group ][ $key ] ) ) {
			$found = true;
			if ( is_object( $stored_cache[ $group ][ $key ] ) ) {
				return clone $stored_cache[ $group ][ $key ];
			} else {
				return $stored_cache[ $group ][ $key ];
			}
		}
		return false;
	}
	
	public static function replace( $key, $data, $group = 'default', $expire = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		$stored_cache = self::$stored_cache;
		if ( isset( $stored_cache[ $group ][ $key ] ) ) {
			return false;
		}
		return Access_Cacher::set( $key, $data, $group, (int) $expire );
	}
	
	public static function set( $key, $data, $group = 'default', $expire = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		if ( is_object( $data ) ) {
			$data = clone $data;
		}
		$stored_cache = self::$stored_cache;
		$stored_cache[ $group ][ $key ] = $data;
		self::$stored_cache = $stored_cache;
		return true;
	}
	
	public static function delete_views_cache( $post_id, $post = null  ) {
		if ( is_null( $post ) ) {
			$post = get_post( $post_id );
		}
		$slugs = array( 'view', 'view-template' );
		if ( ! in_array( $post->post_type, $slugs ) ) {
			return;
		}
		switch ( $post->post_type ) {
			case 'view':
				Access_Cacher::delete( 'views_archives_available' );
				break;
			case 'view-template':
				Access_Cacher::delete( 'content_templates_available' );
				break;
		}
	}

	public static function delete_layouts_cache ( $status, $layout_id = '' ){
		if ( $status ) {
			Access_Cacher::delete( 'layouts_available' );
		}
	}


}