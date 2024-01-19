<?php

namespace OTGS\Toolset\Access\Controllers\Filters;


/**
 * Class Access_Menu_Permissions
 *
 * @package OTGS\Toolset\Access\Controllers\Filters
 * @since 2.7
 */
class Access_Menu_Permissions {

	private static $instance;

	private $posts;


	/**
	 * @return Access_Menu_Permissions
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		self::get_instance();
	}


	/**
	 * @param array $items
	 * @param mixed $menu
	 * @param mixed $args
	 *
	 * @return array
	 */
	function set_menu_permissions( $items, $menu, $args ) {
		foreach ( $items as $key => $item ) {
			if ( ! isset( $item->type ) || ! isset( $item->type ) || $item->type != 'post_type'
				|| ( $item->type
					== 'post_type'
					&& $item->object == 'nav_menu_item' ) ) {
				continue;
			}
			$has_read_permission = true;
			$has_read_permission = apply_filters( 'toolset_access_api_get_post_permissions', $has_read_permission, $item->object_id, 'read' );
			if ( ! $has_read_permission ) {
				unset( $items[ $key ] );
			}
		}

		return $items;
	}
}
