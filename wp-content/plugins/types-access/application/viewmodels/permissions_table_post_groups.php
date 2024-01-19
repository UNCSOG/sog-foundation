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
 * Class PermissionsTablesPostGroups
 *
 * @package OTGS\Toolset\Access\Viewmodels
 * @since 2.8.4
 */
class PermissionsTablesPostGroups {

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


	public static function get_permission_table_for_post_groups() {
		$output = '';

		$access_settings = Settings::get_instance();
		$roles = $access_settings->wpcf_get_editable_roles();
		$permissionGui = PermissionsGui::get_instance();
		$enabled = true;
		$group_output = '';
		$section_statuses = $permissionGui->get_section_statuses();

		$settings_access = $access_settings->get_types_settings( true, true );
		$current_tab = 'custom-group';
		$show_section_header = true;
		$group_count = 0;
		if ( is_array( $settings_access ) ) {
			foreach ( $settings_access as $group_slug => $group_data ) {
				if ( strpos( $group_slug, 'wpcf-custom-group-' ) !== 0 ) {
					continue;
				}
				if ( ! isset( $group_data['title'] ) ) {
					$new_settings_access = $access_settings->get_types_settings( true, true );
					unset( $new_settings_access[ $group_slug ] );
					$access_settings->updateAccessTypes( $new_settings_access );
					continue;
				}
				if ( $show_section_header ) {
					$show_section_header = false;
				}

				$group_count ++;
				$group_div_id = str_replace( '%', '', $group_slug );
				$group['id'] = $group_slug;
				$group['name'] = $group_data['title'];
				$is_section_opened = false;
				if ( isset( $section_statuses[ $group_div_id ] ) && $section_statuses[ $group_div_id ] == 1 ) {
					$is_section_opened = true;
				}

				$group_output .= '<div id="js-box-'
					. $group_div_id
					. '" class="otg-access-settings-section-item is-enabled js-otg-access-settings-section-item wpcf-access-type-item js-wpcf-access-type-item wpcf-access-custom-group">';
				$group_output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="'
					. esc_attr( $group_div_id )
					. '">'
					. $group['name']
					. '</h4>';
				$group_output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-'
					. esc_attr( $group_div_id )
					. '" style="display:'
					. ( ! $is_section_opened ? 'none' : 'block' )
					. '">';

				$group_output .= '<div class="toolset-access-posts-group-info">
				<div class="toolset-access-posts-group-assigned-posts-list">';
				$_post_types = $access_settings->object_to_array( $access_settings->get_post_types() );
				$post_types_array = array();
				foreach ( $_post_types as $post_type ) {
					$post_types_array[] = $post_type['name'];
				}
				$args = array(
					'post_type' => $post_types_array,
					'posts_per_page' => 0,
					'meta_key' => '_wpcf_access_group',
					'meta_value' => $group['id'],
					'suppress_filters' => true,
				);
				$the_query = new \WP_Query( $args );
				if ( $the_query->have_posts() ) {
					$group_output .= '<strong>' . __( 'Posts in this Post Group', 'wpcf-access' ) . ':</strong> ';
					$posts_list = '';
					$show_assigned_posts = 4;
					while ( $the_query->have_posts() && $show_assigned_posts != 0 ) {
						$the_query->the_post();
						$posts_list .= esc_html( get_the_title() ) . ', ';
						$show_assigned_posts --;
					}
					$group_output .= substr( $posts_list, 0, - 2 );
					if ( $the_query->found_posts > 4 ) {
						$group_output .= sprintf( __( ' and %d more', 'wpcf-access' ), ( $the_query->found_posts
							- 2 ) );
					}
				}
				$group_output .= '</div>
				<div class="toolset-access-posts-group-moddify-button">
					<input data-group="'
					. $group_slug
					. '" data-groupdiv="'
					. $group_div_id
					. '" type="button" value="'
					. __( 'Modify Group', 'wpcf-access' )
					. '"  class="js-wpcf-modify-group button-secondary" />
				</div>
			</div>';

				$caps = array();
				$saved_data = array();

				// Add registered via other hook
				if ( ! empty( $group_data['permissions'] ) ) {
					$saved_data = $group_data['permissions'];
				}

				$def = array(
					'read' => array(
						'title' => __( 'Read', 'wpcf-access' ),
						'role' => 'edit_posts',
						'predefined' => 'read',
						'cap_id' => 'group',
					),
					'edit_any' => array(
						'title' => __( 'Edit', 'wpcf-access' ),
						'role' => 'edit_others_posts',
						'predefined' => 'edit_any',
						'cap_id' => 'group',
					),
					'delete_any' => array(
						'title' => __( 'Delete', 'wpcf-access' ),
						'role' => 'delete_others_posts',
						'predefined' => 'delete_any',
						'cap_id' => 'group',
					),
				);

				$group_output .= $permissionGui->permissions_table(
					$roles, $saved_data,
					$def, 'types', $group['id'],
					$enabled, 'permissions',
					$settings_access );

				$group_output .= '<p class="wpcf-access-buttons-wrap">';
				$group_output .= '<span class="ajax-loading spinner"></span>';
				$group_output .= $permissionGui->generate_submit_button( $enabled, true, $group['name'] );
				$group_output .= '</p>';
				$group_output .= '<input type="hidden" name="groupvalue-'
					. $group_slug
					. '" value="'
					. $group_data['title']
					. '">';
				$group_output .= '<div class="toolset-access-post-group-remove-group">
				<a href="#" data-group="'
					. $group_slug
					. '" data-target="custom-group" data-section="'
					. base64_encode( 'custom-group' )
					. '" data-groupdiv="'
					. $group_div_id
					. '"  class="js-wpcf-remove-group"><i class="fa fa-trash"></i> '
					. __( 'Remove Group', 'wpcf-access' )
					. '</a></div>';
				$group_output .= '</div>	<!-- .wpcf-access-mode  -->';

				$group_output .= '</div>	<!-- .wpcf-access-custom-group -->';

			}
		}

		if ( $group_count > 0 ) {
			$output .= '<p class="toolset-access-align-right">'
				. '<button data-label="'
				. esc_attr( __( 'Add Group', 'wpcf-access' ) )
				. '" value="'
				. esc_attr( __( 'Add Post Group', 'wpcf-access' ) )
				. '" class="button button-large button-secondary wpcf-add-new-access-group js-wpcf-add-new-access-group">'
				. '<i class="icon-plus fa fa-plus"></i>'
				. esc_html( __( 'Add Post Group', 'wpcf-access' ) )
				. '</button>'
				. '</p>';
			$output .= $group_output;
		} else {
			$output .= '<div class="otg-access-no-custom-groups js-otg-access-no-custom-groups"><p>'
				. __( 'No Post Groups found.', 'wpcf-access' )
				. '</p><p>'
				. '<a href="" data-label="'
				. __( 'Add Group', 'wpcf-access' )
				. '"
			class="button button-secondary js-wpcf-add-new-access-group">'
				. '<i class="icon-plus fa fa-plus"></i>'
				. __( 'Add your first Post Group', 'wpcf-access' )
				. '</a></p></div>';
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
