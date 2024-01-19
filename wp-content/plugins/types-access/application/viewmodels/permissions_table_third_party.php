<?php

namespace OTGS\Toolset\Access\Viewmodels;

use OTGS\Toolset\Access\Controllers\AccessOutputTemplateRepository;
use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\GuiCommon;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;

/**
 * Generate Access permission tables
 *
 * Class PermissionsTablesThirdParty
 *
 * @package OTGS\Toolset\Access\Viewmodels
 * @since 2.8.4
 */
class PermissionsTablesThirdParty {

	/**
	 * @var object
	 */
	private static $instance;


	/**
	 * @return object|PermissionsGui
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Initialize class
	 */
	public static function initialize() {
		self::get_instance();
	}


	public static function get_permission_table_for_third_party( $current_tab = '' ) {
		global $wpcf_access;
		$output = '';

		$access_settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();

		$settings_access = $access_settings->get_types_settings( true, true );
		$third_party = $access_settings->get_third_party_asettings( true, true );
		$permissionGui = PermissionsGui::get_instance();

		$roles = $access_roles->get_editable_roles();

		$section_statuses = $permissionGui->get_section_statuses();

		$enabled = true;
		if ( empty( $current_tab ) ) {
			$current_tab = esc_attr( $permissionGui->get_current_tab() );
		}

		if ( $current_tab == 'third-party' ) {
			$areas = apply_filters( 'types-access-area', array() );
		} else {
			$areas = apply_filters( 'types-access-area-for-' . $current_tab, array() );
		}


		$has_output = false;

		foreach ( $areas as $area ) {
			// Do not allow Types IDs for post types or taxonomies
			if ( in_array( $area['id'], array( 'types', 'tax' ) ) ) {
				continue;
			}

			// make all groups of same area appear on same line in shortcuts
			$groups = apply_filters( 'types-access-group', array(), $area['id'] );

			if ( ! is_array( $groups ) || empty( $groups ) ) {
				continue;
			}
			$output .= '<h3>' . $area['name'] . '</h3>';
			$has_output = true;

			foreach ( $groups as $group ) {
				$is_section_opened = false;
				$group_div_id = str_replace( '%', '', $group['id'] );
				if ( isset( $section_statuses[ $group_div_id ] ) && $section_statuses[ $group_div_id ] == 1 ) {
					$is_section_opened = true;
				}

				$output .= '<div class="otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item">';
				$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="'
					. esc_attr( $group_div_id )
					. '">'
					. $group['name']
					. '</h4>';
				$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-'
					. esc_attr( $group_div_id )
					. '" style="display:'
					. ( ! $is_section_opened ? 'none' : 'block' )
					. '">';

				$caps = array();
				$caps_filter = apply_filters( 'types-access-cap', array(), $area['id'], $group['id'] );

				$saved_data = array();
				foreach ( $caps_filter as $cap_slug => $cap ) {
					$caps[ $cap['cap_id'] ] = $cap;
					if ( isset( $cap['default_role'] ) ) {
						// @since 2.2, convert minimal role to minimal capability
						$cap['default_role'] = $access_roles->maybe_translate_role_to_capability( $cap['default_role'] );
						$caps[ $cap['cap_id'] ]['role'] = $cap['role'] = $cap['default_role'];
					}
					$saved_data[ $cap['cap_id'] ] =
						isset( $third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap['cap_id'] ] ) ?
							$third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap['cap_id'] ]
							: array( 'roles' => $access_roles->get_roles_by_role( '', $cap['default_role'] ) );
				}

				// Add registered via other hook
				if ( ! empty( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'] ) ) {
					foreach (
						$wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'] as $cap_slug =>
						$cap
					) {
						// Don't allow duplicates
						if ( isset( $caps[ $cap['cap_id'] ] ) ) {
							unset( $wpcf_access->third_party[ $area['id'] ][ $group['id'] ]['permissions'][ $cap_slug ] );
							continue;
						}
						$saved_data[ $cap['cap_id'] ] = $cap['saved_data'];
						$caps[ $cap['cap_id'] ] = $cap;
					}
				}
				if (
					isset( $cap['style'] )
					&& $cap['style'] == 'dropdown'
				) {

				} else {
					$output .= $permissionGui->permissions_table(
						$roles,
						$saved_data,
						$caps,
						$area['id'],
						$group['id'],
						true,
						$settings_access,
						array(),
						array(),
						'third_party'
					);
				}

				$output .= '<p class="wpcf-access-buttons-wrap">';
				$output .= $permissionGui->generate_submit_button( $enabled, true, $group['name'] );
				$output .= '</p>';

				$output .= '</div>	<!-- .wpcf-access-mode -->';
				$output .= '</div>	<!-- .wpcf-access-type-item -->';
			}
		}

		if ( ! $has_output ) {
			$output .= '<p>'
				. __( 'There are no third party areas registered.', 'wpcf-access' )
				. '</p>';
		}

		$section_content = $output;

		$template_repository = AccessOutputTemplateRepository::get_instance();
		$output = $template_repository->render( $template_repository::PERMISSION_TABLE_HEADERS_TEMPLATE,
			array(
				'tab_slug' => $current_tab,
				'section_content' => $section_content,
			)
		);

		return $output;
	}

}
