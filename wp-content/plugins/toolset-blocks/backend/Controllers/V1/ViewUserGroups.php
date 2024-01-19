<?php

namespace OTGS\Toolset\Views\Controllers\V1;

/**
 * Class ViewUserGroups
 * @package OTGS\Toolset\Views\Controllers\V1
 */
class ViewUserGroups extends Base {
	public function register_routes() {
		register_rest_route($this->namespace, '/user_groups', array(
			array(
				'methods' => \WP_REST_Server::READABLE,
				'callback' => array($this, 'get_items'),
				'permission_callback' => array( $this, 'can_edit_view' ),
			)
		));
	}

	public function get_items($request) {
		$result = array();
		global $wp_roles;
		foreach( $wp_roles->role_names as $role => $name ) {
			$result[] = array(
				'value' => $role,
				'label' => $name
			);
		}
		$result[] = array(
			'value' => 'any',
			'label' => __('Any role', 'wpv-views')
		);
		return new \WP_REST_Response($result, 200);
	}
}
