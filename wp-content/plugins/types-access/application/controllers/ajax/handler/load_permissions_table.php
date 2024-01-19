<?php

use OTGS\Toolset\Access\Viewmodels\PermissionsTablesPostTypes;
use OTGS\Toolset\Access\Viewmodels\PermissionsTablesTaxonomies;
use OTGS\Toolset\Access\Viewmodels\PermissionsTablesThirdParty;
use OTGS\Toolset\Access\Viewmodels\PermissionsTablesPostGroups;
use OTGS\Toolset\Access\Viewmodels\PermissionsTablesWpmlGroups;
use OTGS\Toolset\Access\Viewmodels\PermissionsTablesCustomRoles;


/**
 * Class Access_Ajax_Handler_Load_Permissions_Table
 * Load permission table
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Load_Permissions_Table extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Load_Permissions_Table constructor.
	 *
	 * @param \OTGS\Toolset\Access\Ajax $access_ajax
	 */
	public function __construct( \OTGS\Toolset\Access\Ajax $access_ajax ) {
		parent::__construct( $access_ajax );
	}


	/**
	 * @param mixed $arguments
	 *
	 * @return array
	 */
	function process_call( $arguments ) {

		$this->ajax_begin( array( 'nonce' => 'wpcf-access-error-pages' ) );

		$output = '';
		$section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : '';
		if ( $section == '' ) {
			$section = "post-type";
		}
		switch ( $section ) {
			case 'post-type';
				$output = PermissionsTablesPostTypes::get_instance()->get_permission_table_for_posts();
				break;
			case 'taxonomy';
				$output = PermissionsTablesTaxonomies::get_instance()->get_permission_table_for_taxonomies();
				break;
			case 'third-party';
				$output = PermissionsTablesThirdParty::get_instance()->get_permission_table_for_third_party();
				break;
			case 'custom-group';
				$output = PermissionsTablesPostGroups::get_instance()->get_permission_table_for_post_groups();
				break;
			case 'wpml-group';
				$output = PermissionsTablesWpmlGroups::get_instance()->get_permission_table_for_wpml();
				break;
			case 'custom-roles';
				$output = PermissionsTablesCustomRoles::get_instance()->get_permission_table_for_custom_roles();
				break;
			default;
				$extra_tabs = apply_filters( 'types-access-tab', array() );
				if ( isset( $extra_tabs[ $section ] ) ) {
					$output = PermissionsTablesThirdParty::get_instance()->get_permission_table_for_third_party( $section );
				}
				break;
		}

		$data = array(
			'output' => $output,
		);
		wp_send_json_success( $data );

	}
}
