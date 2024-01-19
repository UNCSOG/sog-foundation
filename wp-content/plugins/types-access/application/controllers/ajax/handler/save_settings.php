<?php
/**
 * Class Access_Ajax_Handler_Access_Save_Settings
 * Saves Access settings by section
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Save_Settings extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Access_Save_Settings constructor.
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

		$this->ajax_begin( array( 'nonce' => 'otg-access-edit-sections' ) );

		$access_bypass_template = "<div class='error'><p>"
			. __( "<strong>Warning:</strong> The %s <strong>%s</strong> uses the same name for singular name and plural name. Access can't control access to this object. Please use a different name for the singular and plural names.", 'wpcf-access' )
			. "</p></div>";
		$access_conflict_template = "<div class='error'><p>"
			. __( "<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default Wordpress capabilities. Access can not manage this entity, try changing entity's name and / or slug", 'wpcf-access' )
			. "</p></div>";
		$access_notices = '';

		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$access_capabilities = \OTGS\Toolset\Access\Models\Capabilities::get_instance();
		$_post_types = $access_settings->object_to_array( $access_settings->get_post_types() );
		$_taxonomies = $access_settings->object_to_array( $access_settings->get_taxonomies() );

		// start empty
		$settings_access_types_previous = $access_settings->get_types_settings( true, true );
		$settings_access_taxs_previous = $access_settings->get_tax_settings( true, true );
		$settings_access_thirdparty_previous = $access_settings->get_third_party_asettings( true, true );

		//Update custom errors
		$custom_errors_keys = array(
			'types_access_error_type' => \OTGS\Toolset\Access\Viewmodels\PermissionsGui::CUSTOM_ERROR_SINGLE_POST_TYPE,
			'types_access_error_value' => \OTGS\Toolset\Access\Viewmodels\PermissionsGui::CUSTOM_ERROR_SINGLE_POST_VALUE,
			'types_access_archive_error_type' => \OTGS\Toolset\Access\Viewmodels\PermissionsGui::CUSTOM_ERROR_ARCHIVE_TYPE,
			'types_access_archive_error_value' => \OTGS\Toolset\Access\Viewmodels\PermissionsGui::CUSTOM_ERROR_ARCHIVE_VALUE,
		);

		foreach ( $custom_errors_keys as $key => $original_key ) {
			if ( isset( $_POST[ $key ]['types'] ) ) {
				$settings_access_types_previous = $access_settings->update_custom_error( $original_key, $_POST[ $key ]['types'], $settings_access_types_previous );
			}
		}

		$capabilities = \OTGS\Toolset\Access\Models\Capabilities::get_instance();
		// Post Types
		if ( ! empty( $_POST['types_access']['types'] ) ) {

			$caps = $capabilities->get_types_predefined_caps();

			foreach ( $_POST['types_access']['types'] as $type => $data ) {

				$mode = isset( $data['mode'] ) ? $data['mode'] : 'not_managed';
				wp_cache_delete( 'toolset_access_custom_errors_items_list' . $type, 'OTGS\Toolset\Access\Controllers\Filters\BackendFilters' );
				// Use saved if any and not_managed
				if (
					isset( $data['mode'] )
					&& ( $data['mode'] == 'not_managed' || $data['mode'] == 'follow' )
					&& isset( $settings_access_types_previous[ $type ] )
				) {
					$data = $settings_access_types_previous[ $type ];
				}

				$data['mode'] = $mode;
				if ( strpos( $type, 'wpcf-custom-group-' ) === 0 ) {
					$data['title'] = $settings_access_types_previous[ $type ]['title'];
				}

				if ( strpos( $type, 'wpcf-wpml-group-' ) === 0 ) {
					$data['title'] = $settings_access_types_previous[ $type ]['title'];
					$data['post_type'] = $settings_access_types_previous[ $type ]['post_type'];
					$data['languages'] = $settings_access_types_previous[ $type ]['languages'];
					wp_cache_delete( 'toolset_access_custom_errors_items_list' . $data['post_type'], 'OTGS\Toolset\Access\Viewmodels\ToolsetDashboard' );
				}
				if ( ! isset( $settings_access_types_previous[ $type ] ) ) {
					$settings_access_types_previous[ $type ] = array();
				}
				$data['permissions'] = $access_settings->parse_permissions( $data, $caps, false, $settings_access_types_previous[ $type ] );

				if (
					isset( $_post_types[ $type ]['__accessIsNameValid'] )
					&& ! $_post_types[ $type ]['__accessIsNameValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_bypass_template, __( 'Post Type', 'wpcf-access' ), $_post_types[ $type ]['labels']['singular_name'] );
				}

				if (
					isset( $_post_types[ $type ]['__accessIsCapValid'] )
					&& ! $_post_types[ $type ]['__accessIsCapValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_conflict_template, __( 'Post Type', 'wpcf-access' ), $_post_types[ $type ]['labels']['singular_name'] );
				}

				$settings_access_types_previous[ $type ] = $data;

			}
			// update settings
			$access_settings->updateAccessTypes( $settings_access_types_previous );
		}
		// Taxonomies
		$caps = $capabilities->get_tax_caps();
		// when a taxonomy is unchecked, no $_POST data exist, so loop over all existing taxonomies, instead of $_POST data
		foreach ( $_taxonomies as $tax => $_taxdata ) {
			if ( isset( $_POST['types_access']['tax'] ) && isset( $_POST['types_access']['tax'][ $tax ] ) ) {
				$data = $_POST['types_access']['tax'][ $tax ];

				if ( ! isset( $data['not_managed'] ) ) {
					$data['mode'] = 'not_managed';
				}

				if ( ! isset( $data['mode'] ) ) {
					$data['mode'] = 'permissions';
				}

				$data['mode'] = isset( $data['mode'] ) ? $data['mode'] : 'not_managed';


				// Prevent overwriting
				if ( $data['mode'] == 'not_managed' ) {
					if ( isset( $settings_access_taxs_previous[ $tax ] ) ) {
						$data = $settings_access_taxs_previous[ $tax ];
						$data['mode'] = 'not_managed';
					}
				} elseif ( $data['mode'] == 'follow' ) {
					if ( ! isset( $data['permissions'] ) ) {
						// add this here since it is needed elsewhere
						// and it is missing :P
						$data['permissions'] = $access_capabilities->get_taxs_caps_default();
					}
					$tax_post_type = '';
					if ( isset( $tax_post_type ) ) {
						$tax_arr = array_values( $_taxdata['object_type'] );
						if ( is_array( $tax_arr ) ) {
							//$tax_post_type = array_shift( $tax_arr );
							for ( $i = 0; $i < count( $tax_arr ); $i ++ ) {
								if (
									isset( $settings_access_types_previous[ $tax_arr[ $i ] ] )
									&& 'permissions' == $settings_access_types_previous [ $tax_arr[ $i ] ]['mode']
								) {
									$tax_post_type = $tax_arr[ $i ];
								}
							}
							if ( empty( $tax_post_type ) ) {
								$tax_post_type = $tax_post_type = array_shift( $tax_arr );
							}
						}
					}
					$follow_caps = array();
					// if parent post type managed by access, and tax is same as parent
					// translate and hardcode the post type capabilities to associated tax capabilties
					if ( isset( $settings_access_types_previous[ $tax_post_type ] )
						&& 'permissions' == $settings_access_types_previous [ $tax_post_type ]['mode'] ) {
						$follow_caps = $access_capabilities->types_to_tax_caps( $tax, $_taxdata, $settings_access_types_previous[ $tax_post_type ] );
					}
					//taccess_log(array($tax, $follow_caps));

					if ( ! empty( $follow_caps ) ) {
						$data['permissions'] = $follow_caps;
					} else {
						$data['mode'] = 'not_managed';
						if ( ! empty( $tax_post_type ) ) {
							$access_notices = sprintf(
								__( '%s cannot have same permissions as %s because at least one assigned post type should be managed by Access.', 'wpcf-access' ),
								$_taxonomies[ $tax ]['labels']['singular_name'],
								$_taxonomies['category']['labels']['singular_name']
							);
						} else {
							$access_notices = sprintf(
								__( '%s cannot have same permissions as %s because it should be assigned to at least one post type.', 'wpcf-access' ),
								$_taxonomies[ $tax ]['labels']['singular_name'],
								$_taxonomies['category']['labels']['singular_name']
							);
						}
					}

				}
				if ( ! isset( $settings_access_taxs_previous[ $tax ] ) ) {
					$settings_access_taxs_previous[ $tax ] = array();
				}
				$data['permissions'] = $access_settings->parse_permissions( $data, $caps, false, $settings_access_taxs_previous[ $tax ] );

				if (
					isset( $_taxonomies[ $tax ]['__accessIsNameValid'] )
					&& ! $_taxonomies[ $tax ]['__accessIsNameValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_bypass_template, __( 'Taxonomy', 'wpcf-access' ), $_taxonomies[ $tax ]['labels']['singular_name'] );
				}
				if (
					isset( $_taxonomies[ $tax ]['__accessIsCapValid'] )
					&& ! $_taxonomies[ $tax ]['__accessIsCapValid']
				) {
					$data['mode'] = 'not_managed';
					$access_notices .= sprintf( $access_conflict_template, __( 'Taxonomy', 'wpcf-access' ), $_taxonomies[ $tax ]['labels']['singular_name'] );
				}

				$settings_access_taxs_previous[ $tax ] = $data;

			}

		}
		// update settings
		$access_settings->updateAccessTaxonomies( $settings_access_taxs_previous );
		unset( $settings_access_taxs_previous );

		// 3rd-Party
		if ( ! empty( $_POST['types_access'] ) ) {
			$third_party = $settings_access_thirdparty_previous;
			if ( ! is_array( $third_party ) ) {
				$third_party = array();
			}
			foreach ( $_POST['types_access'] as $area_id => $area_data ) {
				// Skip Types
				if ( $area_id == 'types' || $area_id == 'tax' ) {
					continue;
				}
				if ( ! isset( $third_party[ $area_id ] ) || empty( $third_party[ $area_id ] ) ) {
					$third_party[ $area_id ] = array();
				}

				foreach ( $area_data as $group => $group_data ) {
					$group = esc_attr( $group );
					// Set user IDs
					if ( ! isset( $settings_access_thirdparty_previous[ $area_id ] ) ) {
						$settings_access_thirdparty_previous[ $area_id ] = array();
					}
					if ( ! isset( $settings_access_thirdparty_previous[ $area_id ][ $group ] ) ) {
						$settings_access_thirdparty_previous[ $area_id ][ $group ] = array();
					}
					$group_data['permissions'] = $access_settings->parse_permissions( $group_data, $caps, true, $settings_access_thirdparty_previous[ $area_id ][ $group ] );

					$third_party[ $area_id ][ $group ] = $group_data;
					$third_party[ $area_id ][ $group ]['mode'] = 'permissions';
				}
			}
			// update settings
			$access_settings->updateAccessThirdParty( $third_party );
		}

		// Roles
		if ( ! empty( $_POST['roles'] ) ) {
			$access_roles = $access_settings->getAccessRoles();
			foreach ( $_POST['roles'] as $role => $level ) {
				$role = sanitize_text_field( $role );
				$level = sanitize_text_field( $level );
				$role_data = get_role( $role );
				if ( ! empty( $role_data ) ) {
					$level = (int) $level;
					for ( $index = 0; $index < 11; $index ++ ) {
						if ( $index <= $level ) {
							$role_data->add_cap( 'level_' . $index, 1 );
						} else {
							$role_data->remove_cap( 'level_' . $index );
						}

						if ( isset( $access_roles[ $role ] ) ) {
							if ( isset( $access_roles[ $role ]['caps'] ) ) {
								if ( $index <= $level ) {
									$access_roles[ $role ]['caps'][ 'level_' . $index ] = true;
								} else {
									unset( $access_roles[ $role ]['caps'][ 'level_' . $index ] );
								}
							}
						}
					}
				}
			}
			$access_settings->updateAccessRoles( $access_roles );
		}

		do_action( 'types_access_save_settings' );

		$data = array(
			'message' => $access_notices,
		);

		return wp_send_json_success( $data );
	}
}

