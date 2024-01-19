<?php

namespace OTGS\Toolset\Access\Viewmodels;

use OTGS\Toolset\Access\Controllers\AccessOutputTemplateRepository;
use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\Settings;

/**
 * Generate Access permission tables
 *
 * Class PermissionsTablesPostTypes
 *
 * @package OTGS\Toolset\Access\Viewmodels
 * @since 2.8.4
 */
class PermissionsTablesPostTypes {

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

	/**
	 * @param string $type_slug
	 * @param string $is_managed
	 *
	 * @return string
	 */
	public function get_area_options( $type_slug, $is_managed, $mode ) {
		$output = '<p class="wpcf-access-mode-control">';
		if ( $type_slug == 'post' || $type_slug == 'page' ) {
			$output .= '<label><input type="checkbox" name="wpcf-enable-access['
				. $type_slug
				. ']" class="js-wpcf-enable-access" value="permissions"'
				. checked( $is_managed, true, false )
				. '>'
				. __( 'Managed by Access', 'wpcf-access' )
				. '</label>';
		} else {
			$output .= '<label><input type="radio" name="wpcf-enable-access['
				. $type_slug
				. ']" class="js-wpcf-enable-access" value="permissions"'
				. checked( $is_managed, true, false )
				. '>'
				. __( 'Managed by Access', 'wpcf-access' )
				. '</label>';

			$output .= '<br><label><input type="radio" name="wpcf-enable-access['
				. $type_slug
				. ']" class="js-wpcf-enable-access" value="follow"'
				. checked( $mode, 'follow', false )
				. '>'
				. __( 'Same read permission as posts', 'wpcf-access' )
				. '</label>';

			$output .= '<br><label><input type="radio" name="wpcf-enable-access['
				. $type_slug
				. ']" class="js-wpcf-enable-access" value="not_managed"'
				. checked( $mode, 'not_managed', false )
				. '>'
				. __( 'Use the default WordPress read permissions', 'wpcf-access' )
				. '</label>';
		}

		$output .= '<input type="hidden" class="js-wpcf-enable-set" '
			. 'name="types_access[types]['
			. $type_slug . '][mode]" value="'
			. $mode . '" />';
		$output .= '</p>';

		return $output;
	}

	/**
	 * Generate permission tables for post types
	 *
	 * @return string
	 */
	public function get_permission_table_for_posts() {
		$section_content = '';
		$tab_slug = 'post-type';
		$access_settings = Settings::get_instance();
		$post_types_settings = $access_settings->get_types_settings( true, true );

		$roles = $access_settings->wpcf_get_editable_roles();
		$post_types_available = $access_settings->get_post_types();
		$post_types_available = $access_settings->object_to_array( $post_types_available );
		$permissionGui = PermissionsGui::get_instance();
		$section_statuses = $permissionGui->get_section_statuses();

		$container_class = 'is-enabled';
		$enabled = true;

		list( $access_notices, $post_types_available ) = $permissionGui->filter_post_types( $post_types_available, $post_types_settings );
		$post_types_available = $permissionGui->order_post_types( $post_types_available );

		$post_type_exclude_list_object = new \Toolset_Post_Type_Exclude_List();
		$ignored_post_types = apply_filters( 'toolset-access-excluded-post-types', $post_type_exclude_list_object->get() );

		$capabilities = Capabilities::get_instance();
		$permission_array = $capabilities->get_types_predefined_caps();
		$post_types_with_custom_group = $access_settings->get_post_types_with_custom_groups();

		foreach ( $post_types_available as $type_slug => $type_data ) {
			$output = '';
			if ( $type_data['public'] === 'hidden' ) {
				continue;
			}
			if ( in_array( $type_slug, $ignored_post_types, true ) ) {
				continue;
			}

			// Set data
			$mode = isset( $type_data['_wpcf_access_capabilities']['mode'] )
				? $type_data['_wpcf_access_capabilities']['mode'] : 'not_managed';
			$is_managed = ( $mode === 'permissions' );
			$container_class = 'is-enabled';
			if ( ! $is_managed ) {
				$container_class = 'otg-access-settings-section-item-not-managed';
			}
			$is_section_opened = false;
			if ( isset( $section_statuses[ $type_slug ] ) && $section_statuses[ $type_slug ] == 1 ) {
				$is_section_opened = true;
			}


			$output .= '<h4 class="otg-access-settings-section-item-toggle js-otg-access-settings-section-item-toggle" data-target="'
				. esc_attr( $type_slug )
				. '">'
				. $type_data['labels']['name']
				. '<span class="otg-access-settings-section-item-managed js-otg-access-settings-section-item-managed" style="display:'
				. ( ! $is_section_opened ? 'block' : 'none' )
				. '">'
				. ( $is_managed ? __( '(Managed by Access)', 'wpcf-access' )
					: __( '(Not managed by Access)', 'wpcf-access' ) )
				. '</span>'
				. '</h4>';

			$output .= '<div class="otg-access-settings-section-item-content js-otg-access-settings-section-item-content wpcf-access-mode js-otg-access-settings-section-item-toggle-target-'
				. esc_attr( $type_slug )
				. '"
			style="display:'
				. ( ! $is_section_opened ? 'none' : 'block' )
				. '">';

			if ( $type_slug == 'attachment' ) {
				$output .= '<p class="otg-access-settings-section-description">' .
					__( 'This section controls access to media-element pages and not to media that is included in posts and pages.', 'wpcf-access' )
					. '</p>';
			}

			if (
				'page' === $type_slug
				&& $is_managed
				&& 'page' === get_option( 'show_on_front' )
				&& '' !== get_option( 'page_for_posts' )
			) {
				$output .= '<p class="otg-access-settings-section-description">' .
					sprintf(
						__( 'The %1$sPosts page%2$s is managed as an archive and will not be affected by those permissions.', 'wpcf-access' ),
						'<a href="' . admin_url( 'options-reading.php' ) . '" target="_blank">',
						'</a>'
					)
					. '</p>';
			}

			$output .= $this->get_area_options( $type_slug, $is_managed, $mode );

			$permissions = ! empty( $type_data['_wpcf_access_capabilities']['permissions'] )
				? $type_data['_wpcf_access_capabilities']['permissions'] : array();

			$output .= $permissionGui->permissions_table(
				$roles,
				$permissions,
				$permission_array,
				'types',
				$type_slug,
				$enabled,
				$is_managed,
				$post_types_settings,
				$type_data
			);

			if ( in_array( $type_slug, $post_types_with_custom_group ) ) {
				$message = sprintf(
					__( 'Some %1$s may have different read settings because they belong to a Custom Group. %2$sEdit Custom Groups%3$s', 'wpcf-access' ),
					$type_data['labels']['name'],
					'<a class="js-otg-access-manual-tab" data-target="custom-group" href="'
					. admin_url( 'admin.php?page=types_access&tab=custom-group' )
					. '">',
					'</a>'
				);
				$output .= '<div class="toolset-alert toolset-alert-info js-toolset-alert toolset-access-post-groups-info">'
					. $message
					. '</div>';
			}

			$output .= '<p class="wpcf-access-buttons-wrap">';
			$output .= $permissionGui->generate_submit_button( $enabled, $is_managed, $type_data['labels']['name'] );
			$output .= '</p>';
			$output .= '</div><!-- wpcf-access-mode -->';
			$section_content .= $permissionGui->generate_area_container( $output, $container_class, $type_slug );
		}


		$template_repository = AccessOutputTemplateRepository::get_instance();
		$output = $template_repository->render( $template_repository::PERMISSION_TABLE_HEADERS_TEMPLATE,
			array(
				'tab_slug' => $tab_slug,
				'section_content' => $section_content,
			)
		);

		return $output;
	}
}
