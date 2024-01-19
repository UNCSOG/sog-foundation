<?php

namespace OTGS\Toolset\Access\Controllers\Filters;

use OTGS\Toolset\Access\Models\UserRoles;

/**
 * Class CommonFilters
 *
 * @package OTGS\Toolset\Access\Controllers
 *
 * @since 2.7
 */
class CommonFilters {

	private static $instance;


	/**
	 * @return CommonFilters
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
	 * Collect third-party hooks
	 *
	 * @return array
	 */
	public function wpcf_access_hooks_collect() {
		global $wpcf_access;
		$hooks_array = array();

		$extra_tabs = apply_filters( 'types-access-tab', array() );
		// Native Third Party areas
		$areas = apply_filters( 'types-access-area', array() );
		//Third Party areas coming from custom tabs
		foreach ( $extra_tabs as $tab_slug => $tab_name ) {
			$areas = apply_filters( 'types-access-area-for-' . $tab_slug, $areas );
		}

		if ( ! is_array( $areas ) ) {
			$areas = array();
		}

		foreach ( $areas as $area ) {
			if ( ! isset( $hooks_array[ $area['id'] ] ) ) {
				$hooks_array[ $area['id'] ] = array();
			}

			$groups = apply_filters( 'types-access-group', array(), $area['id'] );
			if ( ! is_array( $groups ) ) {
				$groups = array();
			}
			foreach ( $groups as $group ) {
				if ( ! isset( $hooks_array[ $area['id'] ][ $group['id'] ] ) ) {
					$hooks_array[ $area['id'] ][ $group['id'] ] = array();
				}

				$caps = apply_filters( 'types-access-cap', array(), $area['id'],
					$group['id'] );
				if ( ! is_array( $caps ) ) {
					$caps = array();
				}

				foreach ( $caps as $cap ) {
					$hooks_array[ $area['id'] ][ $group['id'] ][ $cap['cap_id'] ] = $cap;
					$cap['area'] = $area['id'];
					$cap['group'] = $group['id'];
					$cap_reg_data = $this->register_cap( $cap );
					$wpcf_access->third_party_caps[ $cap['cap_id'] ] = $cap_reg_data;
				}
			}
		}

		return $hooks_array;
	}


	/**
	 * Register caps general settings.
	 *
	 * @param array $args
	 *
	 * @return array|bool
	 */
	public function register_cap( $args ) {
		foreach ( array( 'area', 'group' ) as $check ) {
			if ( empty( $args[ $check ] ) ) {
				return false;
			}
		}
		if ( in_array( $args['area'], array( 'types', 'tax' ) ) ) {
			return false;
		}

		$cap_id = isset( $args['cap_id'] ) ? $args['cap_id'] : '';
		$title = isset( $args['title'] ) ? $args['title'] : '';
		$default_role = isset( $args['default_role'] ) ? $args['default_role'] : '';
		$area = isset( $args['area'] ) ? $args['area'] : '';
		$group = isset( $args['group'] ) ? $args['group'] : '';

		if ( empty( $cap_id ) || empty( $title ) || empty( $default_role ) ) {
			return false;
		}

		global $wpcf_access;
		$access_roles = UserRoles::get_instance();
		$wpcf_access->third_party[ $area ][ $group ]['permissions'][ $cap_id ] = array(
			'cap_id' => $cap_id,
			'title' => $title,
			'roles' => $access_roles->get_roles_by_role( $default_role ),
			'saved_data' => isset( $wpcf_access->settings->third_party[ $area ][ $group ]['permissions'][ $cap_id ] )
				? $wpcf_access->settings->third_party[ $area ][ $group ]['permissions'][ $cap_id ]
				: array( 'roles' => $access_roles->get_roles_by_role( $default_role ) ),
		);

		return $wpcf_access->third_party[ $area ][ $group ]['permissions'][ $cap_id ];
	}

}
