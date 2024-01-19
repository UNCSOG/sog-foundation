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
class  PermissionsTablesWpmlGroups {

	/**
	 * @var PermissionsTablesWpmlGroups
	 */
	private static $instance;


	/**
	 * @return PermissionsTablesWpmlGroups
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


	/**
	 * @return string
	 */
	public function get_permission_table_for_wpml() {
		$output = '';
		$current_tab = 'wpml-group';

		if ( apply_filters( 'toolset_access_filter_is_wpml_installed', false ) ) {
			$access_settings = Settings::get_instance();
			$permissionGui = PermissionsGui::get_instance();
			$section_statuses = $permissionGui->get_section_statuses();
			$group_count = 0;

			$group_output = '<p class="toolset-access-align-right">
				<button " style="background-image: url('
				. ICL_PLUGIN_URL
				. '/res/img/icon_adv.png'
				. ')" class="button button-large button-secondary wpcf-add-new-access-group wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group-placeholder">'
				. __( 'Create permission for languages', 'wpcf-access' )
				. '</button></p>';
			//WPML groups

			$settings_access = $access_settings->get_types_settings( true, true );
			$roles = $access_settings->wpcf_get_editable_roles();
			$show_section_header = true;
			$enabled = true;
			if (
				is_array( $settings_access )
				&& ! empty( $settings_access )
			) {
				$_post_types = $access_settings->object_to_array( $access_settings->get_post_types() );
				foreach ( $settings_access as $group_slug => $group_data ) {
					if ( strpos( $group_slug, 'wpcf-wpml-group-' ) !== 0 ) {
						continue;
					}
					if ( ! isset( $_post_types[ $group_data['post_type'] ] ) ) {
						continue;
					}

					if ( ! apply_filters( 'wpml_is_translated_post_type', null, $group_data['post_type'] ) ) {
						$this->remove_wrong_wpml_group( $group_slug );
						continue;
					}

					if ( $show_section_header ) {
						$show_section_header = false;
					}
					$group_count ++;
					$wpml_active_languages = apply_filters( 'wpml_active_languages', '', array( 'skip_missing' => 0 ) );

					$languages = array();
					if ( isset( $group_data['languages'] ) ) {
						foreach ( $group_data['languages'] as $lang => $lang_data ) {
							if ( isset( $wpml_active_languages[ $lang ] ) ) {
								$languages[] = $wpml_active_languages[ $lang ]['native_name'];
							} else {
								$group_data['title'] = $this->rename_wpml_group( $group_slug );
							}
						}
					}

					$group_div_id = str_replace( '%', '', $group_slug );
					$group['id'] = $group_slug;
					$group['name'] = $group_data['title'];
					$is_section_opened = false;
					if ( isset( $section_statuses[ $group_div_id ] ) && $section_statuses[ $group_div_id ] == 1 ) {
						$is_section_opened = true;
					}
					$disabled_message = '';
					$is_group_active = true;
					if ( ! isset( $settings_access[ $group_data['post_type'] ] )
						|| $settings_access[ $group_data['post_type'] ]['mode'] == 'not_managed' ) {
						$is_group_active = false;
						$disabled_message = ' ('
							. sprintf( __( 'This WPML Group is inactive because "%s" post type is not managed by Access', 'wpcf-access' ), $_post_types[ $group_data['post_type'] ]['label'] )
							. ')';
						$is_section_opened = false;
					}


					$group_output .= '<div id="js-box-'
						. $group_div_id
						. '" class="'
						. ( ! $is_group_active
							? 'otg-access-settings-section-item-not-managed ' : '' )
						. 'otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item wpcf-access-custom-group">';
					$group_output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="'
						. esc_attr( $group_div_id )
						. '">'
						. $group['name']
						. $disabled_message
						. '</h4>';
					$group_output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-'
						. esc_attr( $group_div_id )
						. '" style="display:'
						. ( ! $is_section_opened ? 'none' : 'block' )
						. '">';

					$group_output .= '<div class="toolset-access-posts-group-info">
					<div class="toolset-access-posts-group-moddify-button">
						<input data-group="'
						. $group_slug
						. '" data-groupdiv="'
						. $group_div_id
						. '" type="button" value="'
						. __( 'Modify WPML Group', 'wpcf-access' )
						. '"  class="js-wpcf-add-new-wpml-group button-secondary" />
					</div>
				</div>';
					$caps = array();
					$saved_data = array();

					if ( ! empty( $group_data['permissions'] ) ) {
						$saved_data = array(
							'read' => $group_data['permissions']['read'],
							'edit_own' => $group_data['permissions']['edit_own'],
							'delete_own' => $group_data['permissions']['delete_own'],
							'edit_any' => $group_data['permissions']['edit_any'],
							'delete_any' => $group_data['permissions']['delete_any'],
							'publish' => $group_data['permissions']['publish'],
						);
					}

					$def = array(
						'read' => array(
							'title' => __( 'Read', 'wpcf-access' ),
							'role' => 'edit_posts',
							'predefined' => 'read',
							'cap_id' => 'group',
						),
						'edit_own' => array(
							'title' => __( 'Edit and translate own', 'wpcf-access' ),
							'role' => 'edit_posts',
							'predefined' => 'edit_own',
							'cap_id' => 'group',
						),
						'delete_own' => array(
							'title' => __( 'Delete own', 'wpcf-access' ),
							'role' => 'delete_posts',
							'predefined' => 'delete_own',
							'cap_id' => 'group',
						),
						'edit_any' => array(
							'title' => __( 'Edit and translate any', 'wpcf-access' ),
							'role' => 'edit_others_posts',
							'predefined' => 'edit_any',
							'cap_id' => 'group',
						),
						'delete_any' => array(
							'title' => __( 'Delete any', 'wpcf-access' ),
							'role' => 'delete_others_posts',
							'predefined' => 'delete_any',
							'cap_id' => 'group',
						),
						'publish' => array(
							'title' => __( 'Publish', 'wpcf-access' ),
							'role' => 'edit_published_posts',
							'predefined' => 'publish',
							'cap_id' => 'group',
						),
					);

					$group_output .= $permissionGui->permissions_table(
						$roles,
						$saved_data,
						$def,
						'types',
						$group['id'],
						$enabled,
						'permissions',
						$settings_access
					);

					$group_output .= '<p class="wpcf-access-buttons-wrap">';
					$group_output .= '<span class="ajax-loading spinner"></span>';
					$group_output .= $permissionGui->generate_submit_button( $enabled, true, '' );
					$group_output .= '</p>';
					$group_output .= '<div class="toolset-access-post-group-remove-group">
				<a href="#" data-group="'
						. $group_slug
						. '" data-target="wpml-group" data-section="'
						. base64_encode( 'wpml-group' )
						. '" data-groupdiv="'
						. $group_div_id
						. '"  class="js-wpcf-remove-group"><i class="fa fa-trash"></i> '
						. __( 'Remove Group', 'wpcf-access' )
						. '</a></div>';
					$group_output .= '</div>	<!-- .wpcf-access-mode  -->';
					$group_output .= '</div>	<!-- .wpcf-access-wpml-group -->';
				}
			}
			if ( $group_count > 0 ) {
				$output .= $group_output;
			} else {
				$output .= '<div class="otg-access-no-custom-groups js-otg-access-no-custom-groups">
				<p>' . __( 'No permission for languages found.', 'wpcf-access' )
					. '</p><p><a href="#" data-label="' . __( 'Add Group', 'wpcf-access' ) . '"
			class="button button-secondary js-wpcf-add-new-wpml-group js-wpcf-add-new-wpml-group-placeholder">'
					. '<i class="icon-plus fa fa-plus"></i>'
					. __( 'Create your first permission for languages', 'wpcf-access' ) . '</a></p></div>';
			}

		} else {
			$output .= '<p>'
				. __( 'WPML is not installed.', 'wpcf-access' )
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


	/**
	 * @param string $group_slug
	 */
	public function remove_wrong_wpml_group( $group_slug ) {
		$access_settings = Settings::get_instance();
		$settings_access = $access_settings->get_types_settings( true, true );

		if ( isset( $settings_access[ $group_slug ] ) ) {
			unset( $settings_access[ $group_slug ] );
		}
		if ( isset( $settings_access['_custom_read_errors'][ $group_slug ] ) ) {
			unset( $settings_access['_custom_read_errors'][ $group_slug ] );
		}
		if ( isset( $settings_access['_custom_read_errors_value'][ $group_slug ] ) ) {
			unset( $settings_access['_custom_read_errors_value'][ $group_slug ] );
		}

		$access_settings->updateAccessTypes( $settings_access );
	}


	/**
	 * Rename WPML group when one of languages was deactivated
	 *
	 * @param string $group_slug
	 *
	 * @return string
	 */
	public function rename_wpml_group( $group_slug ) {
		$access_settings = Settings::get_instance();
		$_post_types = $access_settings->object_to_array( $access_settings->get_post_types() );
		$languages = array();
		$title_languages_array = array();
		$wpml_active_languages = apply_filters( 'wpml_active_languages', '', array( 'skip_missing' => 0 ) );
		$settings_access = $access_settings->get_types_settings( true, true );


		if ( isset( $settings_access[ $group_slug ]['languages'] ) ) {
			foreach ( $settings_access[ $group_slug ]['languages'] as $lang_name => $lang_status ) {
				if ( isset( $wpml_active_languages[ $lang_name ] ) ) {
					$languages[ $lang_name ] = 1;
					$title_languages_array[] = $wpml_active_languages[ $lang_name ]['translated_name'];
				} else {
					unset( $settings_access[ $group_slug ]['languages'][ $lang_name ] );
				}
			}
		}
		if ( count( $title_languages_array ) > 1 ) {
			$title_languages = implode( ', ', array_slice( $title_languages_array, 0, count( $title_languages_array )
					- 1 ) ) . ' and ' . end( $title_languages_array );
		} else {
			$title_languages = implode( ', ', $title_languages_array );
		}
		$group_name = $title_languages
			. ' '
			. $_post_types[ $settings_access[ $group_slug ]['post_type'] ]['labels']['name'];
		$settings_access[ $group_slug ]['title'] = $group_name;
		$access_settings->updateAccessTypes( $settings_access );

		return $group_name;
	}

}
