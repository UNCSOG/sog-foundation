<?php

namespace OTGS\Toolset\Access\Viewmodels;

use OTGS\Toolset\Access\Controllers\AccessOutputTemplateRepository;
use OTGS\Toolset\Access\Models\Capabilities;
use OTGS\Toolset\Access\Models\GuiCommon;
use OTGS\Toolset\Access\Models\Settings;
use OTGS\Toolset\Access\Models\UserRoles;

/**
 * Generate Access permission tables.
 *
 * @since 2.8.4
 */
class PermissionsGui {

	/**
	 * @var PermissionsGui
	 */
	private static $instance;

	const SECTION_CONTENT_TAB_POST_TYPE = 'post-type';

	const SECTION_CONTENT_TAB_TAXONOMY = 'taxonomy';

	const SECTION_CONTENT_TAB_THIRD_PARTY = 'third-party';

	const SECTION_CONTENT_TAB_CUSTOM_GROUP = 'custom-group';

	const SECTION_CONTENT_TAB_WPML_GROUP = 'wpml-group';

	const SECTION_CONTENT_TAB_CUSTOM_ROLES = 'custom-roles';

	const CUSTOM_ERROR_SINGLE_POST_TYPE = '_custom_read_errors';

	const CUSTOM_ERROR_SINGLE_POST_VALUE = '_custom_read_errors_value';

	const CUSTOM_ERROR_ARCHIVE_TYPE = '_archive_custom_read_errors';

	const CUSTOM_ERROR_ARCHIVE_VALUE = '_archive_custom_read_errors_value';


	/**
	 * @return PermissionsGui
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
	 * Admin page form.
	 *
	 * We are doing lots of things here we do not need at all
	 */
	public function print_access_control_page() {
		$output = '';

		list( $tabs, $extra_tabs, $custom_areas ) = $this->get_access_tabs();

		$current_tab = $this->get_current_tab();
		$nonce = wp_nonce_field( 'otg-access-edit-sections', 'otg-access-edit-sections', true, false );
		$nonce .= wp_nonce_field( 'wpcf-access-error-pages', 'wpcf-access-error-pages', true, false );

		$tab_items = '';
		foreach ( $tabs as $tab_section => $tab_title ) {
			$section_classname = array( 'nav-tab' );
			if ( $current_tab == $tab_section ) {
				$section_classname[] = 'nav-tab-active';
			}
			$section_classname[] = 'js-wpcf-access-shortcuts js-otg-access-nav-tab';
			$title = $this->get_tab_titile( $tab_section );
			$tab_items .= sprintf(
				'<a title="%s" href="%s" data-target="%s" class="%s">%s</a>',
				esc_attr( $title ),
				esc_attr( admin_url( 'admin.php?page=types_access&tab=' . $tab_section ) ),
				esc_attr( $tab_section ),
				esc_attr( implode( ' ', $section_classname ) ),
				esc_html( $tab_title )
			);
		}

		$section_content = $this->get_section_content( $current_tab, $extra_tabs );
		$template_repository = AccessOutputTemplateRepository::get_instance();
		$output = $template_repository->render( $template_repository::PERMISSION_MAIN_TEMPLATE,
			array(
				'nonce' => $nonce,
				'tab_items' => $tab_items,
				'section_content' => $section_content,
			)
		);
		echo $output;

		return;

	}


	/**
	 * @param string $current_tab
	 * @param string $extra_tabs
	 *
	 * @return mixed
	 */
	private function get_section_content( $current_tab, $extra_tabs = '' ) {
		$output = '';

		switch ( $current_tab ) {
			case self::SECTION_CONTENT_TAB_POST_TYPE;
				$output = PermissionsTablesPostTypes::get_instance()->get_permission_table_for_posts();
				break;
			case self::SECTION_CONTENT_TAB_TAXONOMY;
				$output = PermissionsTablesTaxonomies::get_instance()->get_permission_table_for_taxonomies();
				break;
			case self::SECTION_CONTENT_TAB_THIRD_PARTY;
				$output = PermissionsTablesThirdParty::get_instance()->get_permission_table_for_third_party();
				break;
			case self::SECTION_CONTENT_TAB_CUSTOM_GROUP;
				$output = PermissionsTablesPostGroups::get_instance()->get_permission_table_for_post_groups();
				break;
			case self::SECTION_CONTENT_TAB_WPML_GROUP;
				$output = PermissionsTablesWpmlGroups::get_instance()->get_permission_table_for_wpml();
				break;
			case self::SECTION_CONTENT_TAB_CUSTOM_ROLES;
				$output = PermissionsTablesCustomRoles::get_instance()->get_permission_table_for_custom_roles();
				break;
			default;
				if ( isset( $extra_tabs[ $current_tab ] ) ) {
					$output = PermissionsTablesThirdParty::get_instance()
						->get_permission_table_for_third_party( $current_tab );
				}
				break;
		}

		return $output;
	}


	/**
	 * Renders dropdown with editable roles.
	 *
	 * @param array $roles
	 * @param string $name
	 * @param array $data
	 * @param bool $dummy
	 * @param bool $enabled
	 * @param array $exclude
	 *
	 * @return string
	 */
	public function admin_roles_dropdown( $roles, $name, $data = array(), $dummy = false, $enabled = true, $exclude = array() ) {
		$acess_capabilities = Capabilities::get_instance();
		$default_roles = $acess_capabilities->get_default_roles();
		$output = '';
		$output .= '<select name="' . $name . '"';
		$output .= isset( $data['predefined'] ) ? 'class="js-wpcf-reassign-role wpcf-access-predefied-'
			. $data['predefined']
			. '">' : '>';

		if ( $dummy ) {
			$output .= "\n\t<option";
			if ( empty( $data ) ) {
				$output .= ' selected="selected" disabled="disabled"';
			}
			$output .= ' value="0">' . $dummy . '</option>';
		}
		foreach ( $roles as $role => $details ) {
			if ( in_array( $role, $exclude ) ) {
				continue;
			}
			if ( in_array( $role, $default_roles ) ) {
				$title = translate_user_role( $details['name'] );
			} else {
				$title = taccess_t( $details['name'], $details['name'] );
			}

			$output .= "\n\t<option";
			if ( isset( $data['role'] ) && $data['role'] == $role ) {
				$output .= ' selected="selected"';
			}
			if ( ! $enabled ) {
				$output .= ' disabled="disabled"';
			}
			$output .= ' value="' . esc_attr( $role ) . '">' . esc_html( $title ) . '</option>';
		}

		// For now, let's add Guest only for read-only
		if ( isset( $data['predefined'] ) && 'read-only' === $data['predefined'] ) {
			$output .= "\n\t<option";
			if ( isset( $data['role'] ) && 'guest' === $data['role'] ) {
				$output .= ' selected="selected"';
			}
			if ( ! $enabled ) {
				$output .= ' disabled="disabled"';
			}
			$output .= ' value="guest">' . esc_html( __( 'Guest', 'wpcf-access' ) ) . '</option>';
		}
		$output .= '</select>';

		return $output;
	}


	/**
	 * @sinse 2.2
	 *
	 * return array of opened sections
	 *
	 * @return array
	 */
	public function get_section_statuses() {
		global $current_user;
		$user_id = $current_user->ID;
		$sections_array = get_user_meta( $user_id, 'wpcf_access_section_status', false );
		if ( isset( $sections_array[0] ) ) {
			$sections_array = $sections_array[0];
		}

		return $sections_array;
	}


	/**
	 * @param array $post_types_available
	 * @param array $post_types_settings
	 *
	 * @return array
	 */
	public function filter_post_types( $post_types_available, $post_types_settings ) {
		$access_bypass_template = '<div class="error">'
			. '<p>'
			. __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses the same word for singular name and plural name. Access can\'t control access to this object. Please use a different word for the singular and plural names.', 'wpcf-access' )
			. '</p>'
			. '</div>';
		$access_conflict_template = '<div class="error">'
			. '<p>'
			. __( '<strong>Warning:</strong> The %s <strong>%s</strong> uses capability names that conflict with default WordPress capabilities. Access can not manage this entity, try changing its name and / or slug', 'wpcf-access' )
			. '</p>'
			. '</div>';

		$access_notices = '';

		foreach ( $post_types_available as $type_slug => $type_data ) {
			// filter types, excluding types that do not have different plural and singular names
			if (
				isset( $type_data['__accessIsNameValid'] )
				&& ! $type_data['__accessIsNameValid']
			) {
				$access_notices .= sprintf( $access_bypass_template, __( 'Post Type', 'wpcf-access' ), $type_data['labels']['singular_name'] );
				unset( $post_types_available[ $type_slug ] );
				continue;
			}
			if (
				isset( $type_data['__accessIsCapValid'] )
				&& ! $type_data['__accessIsCapValid']
			) {
				$access_notices .= sprintf( $access_conflict_template, __( 'Post Type', 'wpcf-access' ), $type_data['labels']['singular_name'] );
				unset( $post_types_available[ $type_slug ] );
				continue;
			}

			if ( isset( $post_types_settings[ $type_slug ] ) ) {
				$post_types_available[ $type_slug ]['_wpcf_access_capabilities'] = $post_types_settings[ $type_slug ];
			}

			if ( ! empty( $type_data['_wpcf_access_inherits_post_cap'] ) ) {
				$post_types_available[ $type_slug ]['_wpcf_access_inherits_post_cap'] = 1;
			}
		}

		return array( $access_notices, $post_types_available, $access_bypass_template, $access_conflict_template );
	}


	/**
	 * @param array $post_types_available
	 *
	 * @return array
	 */
	public function order_post_types( $post_types_available ) {
		$native_post_types = array( 'page', 'post' );
		foreach ( $native_post_types as $npt ) {
			if ( isset( $post_types_available[ $npt ] ) ) {
				$clone = array( $npt => $post_types_available[ $npt ] );
				unset( $post_types_available[ $npt ] );
				$post_types_available = $clone + $post_types_available;
			}
		}

		return $post_types_available;
	}


	/**
	 * @param string $content
	 * @param string $container_class
	 * @param string $type_slug
	 *
	 * @return string
	 */
	public function generate_area_container( $content, $container_class, $type_slug ) {
		$output = '<div class="otg-access-settings-section-item js-otg-access-settings-section-item wpcf-access-type-item '
			. $container_class
			. ' wpcf-access-post-type-name-'
			. $type_slug
			. ' js-wpcf-access-type-item">' . $content . '</div>';

		return $output;
	}


	/**
	 * @param array $settings
	 * @param int $id
	 * @param string $permission_slug
	 *
	 * @return array
	 */
	public function get_custom_error_values( $settings, $id, $permission_slug ) {
		if ( $permission_slug == 'read' ) {
			$custom_error_single_types = toolset_getnest( $settings, array(
				self::CUSTOM_ERROR_SINGLE_POST_TYPE,
				$id,
				'permissions',
				'read',
			), '' );
			$custom_error_single_values = toolset_getnest( $settings, array(
				self::CUSTOM_ERROR_SINGLE_POST_VALUE,
				$id,
				'permissions',
				'read',
			), '' );
			$custom_error_archive_types = toolset_getnest( $settings, array(
				self::CUSTOM_ERROR_ARCHIVE_TYPE,
				$id,
				'permissions',
				'read',
			), '' );
			$custom_error_archive_values = toolset_getnest( $settings, array(
				self::CUSTOM_ERROR_ARCHIVE_VALUE,
				$id,
				'permissions',
				'read',
			), '' );

			return array(
				$custom_error_single_types,
				$custom_error_single_values,
				$custom_error_archive_types,
				$custom_error_archive_values,
			);
		} else {
			return array( '', '', '', '' );
		}
	}


	/**
	 * @param array $custom_error_single_types
	 * @param array $custom_error_single_values
	 * @param array $custom_error_archive_types
	 * @param array $custom_error_archive_values
	 * @param array $type_data
	 * @param string $role
	 *
	 * @return array
	 */
	public function get_custom_error_info( $custom_error_single_types, $custom_error_single_values, $custom_error_archive_types, $custom_error_archive_values, $type_data, $role = 'everyone' ) {
		$custom_error_post_info = $custom_error_archive_info = $post_custom_error_type_value = $post_custom_error_value = $archive_csutom_error_type_value = $archive_custom_error_value = '';

		if ( isset( $custom_error_single_types[ $role ] ) && ! empty( $custom_error_single_types[ $role ] ) ) {
			$post_custom_error_type_value = $custom_error_single_types[ $role ];
			$post_custom_error_value = ( isset( $custom_error_single_values[ $role ] )
				? $custom_error_single_values[ $role ] : '' );
			if ( $post_custom_error_type_value == 'error_404' ) {
				$custom_error_post_info = '404';
			} elseif ( $post_custom_error_type_value == 'error_ct' ) {
				$custom_error_post_info = __( 'Template', 'wpcf-access' )
					. ': '
					. GuiCommon::get_instance()->get_content_template_name( $post_custom_error_value );
			} elseif ( $post_custom_error_type_value == 'error_layouts' ) {
				if ( class_exists( 'WPDD_Layouts' ) ) {
					$custom_error_post_info = __( 'Template Layout: ', 'wpcf-access' )
						. ': '
						. GuiCommon::get_instance()->get_layout_name( $post_custom_error_value );
				} else {
					$custom_error_post_info = $link_title = '';
				}
			} else {
				$custom_error_post_info = __( 'PHP Template', 'wpcf-access' ) . ': ' . $post_custom_error_value;
			}
		}

		if ( isset( $custom_error_archive_types[ $role ] )
			&& ! empty( $custom_error_archive_types[ $role ] )
			&& $this->is_archive( $type_data ) ) {
			$archive_csutom_error_type_value = $custom_error_archive_types[ $role ];
			$archive_custom_error_value = ( isset( $custom_error_archive_values[ $role ] )
				? $custom_error_archive_values[ $role ] : '' );
			if ( $archive_csutom_error_type_value == 'default_error' ) {
				$custom_error_archive_info = __( 'Display: \'No posts found\'', 'wpcf-access' );
			} elseif ( $archive_csutom_error_type_value == 'error_ct' ) {
				$custom_error_archive_info = __( 'View Archive', 'wpcf-access' )
					. ': '
					. GuiCommon::get_instance()->get_view_name( $archive_custom_error_value );
			} elseif ( $archive_csutom_error_type_value == 'error_layouts' ) {
				if ( class_exists( 'WPDD_Layouts' ) ) {
					$custom_error_archive_info = __( 'Layout Template Archive', 'wpcf-access' )
						. ': '
						. GuiCommon::get_instance()->get_layout_name( $archive_custom_error_value );
				} else {
					$custom_error_archive_info = '';
				}
			} elseif ( $archive_csutom_error_type_value == 'error_php' ) {
				$custom_error_archive_info = __( 'PHP Archive', 'wpcf-access' )
					. ': '
					. preg_replace( "/.*(\/.*\/)/", "$1", $archive_custom_error_value );
			} else {
				$custom_error_archive_info = '';
			}
		}

		return array(
			$custom_error_post_info,
			$custom_error_archive_info,
			$post_custom_error_type_value,
			$post_custom_error_value,
			$archive_csutom_error_type_value,
			$archive_custom_error_value,
		);
	}


	/**
	 * @param int $group_id
	 * @param int $id
	 * @param string $permission_slug
	 * @param string $role
	 *
	 * @return array
	 */
	public function get_custom_error_option_names( $group_id, $id, $permission_slug, $role = 'everyone' ) {
		$error_type = sprintf( 'types_access_error_type[%s][%s][permissions][%s][%s]', $group_id, $id, $permission_slug, $role );
		$error_value = sprintf( 'types_access_error_value[%s][%s][permissions][%s][%s]', $group_id, $id, $permission_slug, $role );
		$archive_error_type = sprintf( 'types_access_archive_error_type[%s][%s][permissions][%s][%s]', $group_id, $id, $permission_slug, $role );
		$archive_error_value = sprintf( 'types_access_archive_error_value[%s][%s][permissions][%s][%s]', $group_id, $id, $permission_slug, $role );

		return array( $error_type, $error_value, $archive_error_type, $archive_error_value );
	}


	/**
	 * @param array $settings
	 * @param array $custom_errors
	 * @param int $id
	 * @param int $group_id
	 * @param array $type_data
	 *
	 * @return string
	 */
	public function get_permission_options( $settings, $custom_errors, $id, $group_id, $type_data ) {
		$role_column_style = '';
		if ( count( $settings ) <= 2 ) {
			$role_column_style = ' style="width: 20%"';
		}
		$permission_options = '<th' . $role_column_style . '>&nbsp;</th>';
		foreach ( $settings as $permission_slug => $data ) {

			list( $current_custom_errors, $current_custom_errors_value, $current_archive_custom_errors, $current_archive_custom_errors_value ) = $this->get_custom_error_values( $custom_errors, $id, $permission_slug );
			$title = $data['title'];

			if ( $group_id == 'types' && $id != 'attachment' && $permission_slug == 'read' ) {

				list( $custom_error_info, $custom_error_archive_info, $post_error_type_input_value, $post_error_value_input_value, $archive_error_type_input_value, $archive_error_value_input_value )
					= $this->get_custom_error_info( $current_custom_errors, $current_custom_errors_value, $current_archive_custom_errors, $current_archive_custom_errors_value, $type_data, 'everyone' );

				$is_archive = $this->is_archive( $type_data );

				$link_title = ' title="'
					. sprintf( __( 'Choose what to display to people who don’t have read permission for %s', 'wpcf-access' ), $id )
					. '" ';

				list( $post_error_type_input_name, $post_error_value_input_name, $archive_error_type_input_name, $archive_error_value_input_name ) =
					$this->get_custom_error_option_names( $group_id, $id, $permission_slug );

				$custom_error_json_array = wp_json_encode(
					array(
						'typename' => $post_error_type_input_name,
						'role' => '',
						'valuename' => $post_error_value_input_name,
						'curtype' => $post_error_type_input_value,
						'curvalue' => $post_error_value_input_value,
						'archivetypename' => $archive_error_type_input_name,
						'archivevaluename' => $archive_error_value_input_name,
						'archivecurtype' => $archive_error_type_input_value,
						'archivecurvalue' => $archive_error_value_input_value,
						'posttype' => $id,
						'archive' => $is_archive,
						'forall' => 1,
					)
				);

				$addon = sprintf( '<a %s class="wpcf-add-error-page js-wpcf-add-error-page" data-custom_error="%s" href=""><i class="icon-edit fa fa-pencil-square-o"></i></a>',
					$link_title, esc_attr( $custom_error_json_array ) );

				//Labels
				$addon .= sprintf( '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-error-page-name">%s</span></p>'
					. '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-archive_error-page-name">%s</span></p>'
					. '<input type="hidden" name="%s" value="%s">
						<input type="hidden" name="%s" value="%s">', esc_html( $custom_error_info ), esc_html( $custom_error_archive_info ), esc_attr( $post_error_type_input_name ),
					esc_attr( $post_error_type_input_value ), esc_attr( $post_error_value_input_name ), esc_attr( $post_error_value_input_value ) );

				if ( $is_archive ) {
					$addon .= sprintf( '<input type="hidden" name="%s" value="%s">
							<input type="hidden" name="%s" value="%s">', esc_attr( $archive_error_type_input_name ), esc_attr( $archive_error_type_input_value ),
						esc_attr( $archive_error_value_input_name ), esc_attr( $archive_error_value_input_value ) );
				}
				$title .= $addon;
			}
			$permission_options .= '<th>' . $title;
			if (
				isset( $data['help_tip'] )
				&& ! empty( $data['help_tip'] )
			) {
				$permission_options .= '<span class="toolset-access-capability-tip js-otgs-popover-tooltip" data-tippy-content="'
					. $data['help_tip'] . '">'
					. '<i class="fa fa-question-circle"></i>'
					. '</span>';
			}
			$permission_options .= '</th>';
		}

		return $permission_options;
	}


	/**
	 * @param array $permissions
	 * @param string $permission_slug
	 * @param object $access_roles
	 * @param string $role
	 * @param array $settings
	 * @param array $roles_data
	 *
	 * @return array
	 */
	public function is_permission_option_enabled( $permissions, $permission_slug, $access_roles, $role, $settings, $roles_data ) {
		$option_enabled = false;

		if ( isset( $permissions[ $permission_slug ]['roles'] ) ) {
			if ( is_string( $permissions[ $permission_slug ]['roles'] ) ) {
				$permissions[ $permission_slug ]['roles'] = $access_roles->get_roles_by_role( $permissions[ $permission_slug ]['roles'] );
			}
			if ( in_array( $role, $permissions[ $permission_slug ]['roles'] ) !== false ) {
				$option_enabled = true;
			}
		} elseif ( isset( $settings[ $permission_slug ]['roles'] ) ) {
			if ( in_array( $role, $settings[ $permission_slug ]['roles'] ) !== false ) {
				$option_enabled = true;
			}
		} else {
			//Set permissions by predefined role capabilities
			if ( isset( $settings[ $permission_slug ]['role'] ) ) {
				$option_enabled = GuiCommon::get_instance()
					->check_for_cap( $settings[ $permission_slug ]['role'], $roles_data );
			}
		}

		return array( $permissions, $option_enabled );
	}


	/**
	 * HTML formatted permissions table.
	 *
	 * @param array $roles
	 * @param array $permissions
	 * @param array $settings
	 * @param string $group_id
	 * @param int $id
	 * @param bool $enabled
	 * @param bool $managed
	 * @param array $custom_errors
	 * @param array $type_data
	 * @param string $area
	 *
	 * @return string
	 */
	public function permissions_table(
		$roles, $permissions, $settings,
		$group_id, $id, $enabled = true, $managed = true, $custom_errors = array(), $type_data = array(), $area = 'types'
	) {
		$output = '';

		$access_settings = Settings::get_instance();
		$access_roles = UserRoles::get_instance();
		$ordered_roles = $access_settings->order_wp_roles();

		$ordered_roles['guest'] = array(
			'name' => __( 'Guest', 'wpcf-access' ),
			'permissions_group' => 6,
			'capabilities' => array( 'read' => 1 ),
		);

		$settings = array_reverse( (array) $settings );
		$default_roles = $access_roles->get_editable_roles();

		$permission_options = $this->get_permission_options( $settings, $custom_errors, $id, $group_id, $type_data );

		foreach ( $ordered_roles as $role => $roles_data ) {
			$output .= '<tr>';
			$output .= '<th class="wpcf-access-table-action-title js-toolset-access-sticky-column">';
			if ( in_array( $role, $default_roles ) ) {
				$output .= translate_user_role( $roles_data['name'] );
			} else {
				$output .= taccess_t( $roles_data['name'], $roles_data['name'] );
			}
			$output .= '</th>';

			foreach ( $settings as $permission_slug => $data ) {
				list( $current_custom_errors, $current_custom_errors_value, $current_archive_custom_errors, $current_archive_custom_errors_value ) = $this->get_custom_error_values( $custom_errors, $id, $permission_slug );

				// Change slug for 3rd party
				if ( ! in_array( $group_id, array( 'types', 'tax' ) ) ) {
					$permission_slug = $data['cap_id'];
					$managed = true;
				}

				list( $permissions, $option_enabled ) = $this->is_permission_option_enabled( $permissions, $permission_slug, $access_roles, $role, $settings, $roles_data );

				$name = sprintf( 'types_access[%s][%s][permissions][%s][roles][]', $group_id, $id, $permission_slug );

				$addon = '';
				if ( $permission_slug == 'read' && $role != 'administrator' && $id != 'attachment' ) {
					$addon_id = $group_id . '_' . $id . '_error_page_' . $permission_slug . '_' . $role . '_role';

					list( $custom_error_info, $custom_error_archive_info, $post_error_type_input_value, $post_error_value_input_value, $archive_error_type_input_value, $archive_error_value_input_value )
						= $this->get_custom_error_info( $current_custom_errors, $current_custom_errors_value, $current_archive_custom_errors, $current_archive_custom_errors_value, $type_data, $role );

					$is_archive = $this->is_archive( $type_data );

					$link_title = ' title="'
						. sprintf( __( 'Choose what to display to people who don’t have read permission for %s', 'wpcf-access' ), $id )
						. '" ';

					list( $post_error_type_input_name, $post_error_value_input_name, $archive_error_type_input_name, $archive_error_value_input_name ) =
						$this->get_custom_error_option_names( $group_id, $id, $permission_slug, $role );

					$custom_error_json_array = wp_json_encode(
						array(
							'typename' => $post_error_type_input_name,
							'role' => $role,
							'valuename' => $post_error_value_input_name,
							'curtype' => $post_error_type_input_value,
							'curvalue' => $post_error_value_input_value,
							'archivetypename' => $archive_error_type_input_name,
							'archivevaluename' => $archive_error_value_input_name,
							'archivecurtype' => $archive_error_type_input_value,
							'archivecurvalue' => $archive_error_value_input_value,
							'posttype' => $id,
							'archive' => $is_archive,
							'forall' => 0,
						)
					);

					$addon = sprintf( '<a %s class="wpcf-add-error-page js-wpcf-add-error-page" data-custom_error="%s" href=""><i class="icon-edit fa fa-pencil-square-o"></i></a>',
						$link_title, esc_attr( $custom_error_json_array ) );

					$addon .= sprintf( '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-error-page-name">%s</span></p>'
						. '<p class="error-page-name-wrap js-tooltip"><span class="error-page-name js-archive_error-page-name">%s</span></p>'
						. '<input type="hidden" name="%s" value="%s">
						<input type="hidden" name="%s" value="%s">', esc_html( $custom_error_info ), esc_html( $custom_error_archive_info ), esc_attr( $post_error_type_input_name ),
						esc_attr( $post_error_type_input_value ), esc_attr( $post_error_value_input_name ), esc_attr( $post_error_value_input_value ) );

					if ( $is_archive ) {
						$addon .= sprintf( '<input type="hidden" name="%s" value="%s">
							<input type="hidden" name="%s" value="%s">', esc_attr( $archive_error_type_input_name ), esc_attr( $archive_error_type_input_value ),
							esc_attr( $archive_error_value_input_name ), esc_attr( $archive_error_value_input_value ) );
					}

				}

				$is_disabled_cred_checkbox = ( $id == '__CRED_CRED_USER_GROUP'
					&& $role == 'guest'
					&& strpos( $name, 'create_users_with_cred' ) === false );

				$att_id = $group_id . '_' . $id . '_permissions_' . $permission_slug . '_' . $role . '_role';
				$attributes = $option_enabled && ! $is_disabled_cred_checkbox ? ' checked="checked" ' : '';
				$attributes .= ! $managed ? ' readonly="readonly" disabled="disabled" ' : '';
				$tooltip = $forms_disabled_checkbox_class = '';

				if ( $managed && $role == 'guest' && $permission_slug != 'read'
					&& ( $group_id == 'types'
						|| $group_id
						== 'tax' )
					|| $is_disabled_cred_checkbox ) {
					$attributes .= ' readonly="readonly" disabled="disabled" ';
					$tooltip = ' title="' . __( 'This option doesn\'t work for Guests', 'wpcf-access' ) . '"';
					if ( $is_disabled_cred_checkbox ) {
						$forms_disabled_checkbox_class = ' js-forms-disabled-checkbox';
					}
				}

				$output .= sprintf( '<td class="wpcf-access-table-option-cell"%s><div class="error-page-set-wrap"><input type="checkbox" name="%s" id="%s" value="%s" %s
					class="wpcf-access-check-left wpcf-access-%s" data-wpcfaccesscap="%s" data-wpcfaccessname="%s" onclick="wpcfAccess.AutoThick( jQuery(this), \'%s\', \'%s\');"', esc_attr( $tooltip ), esc_attr( $name ), esc_attr( $att_id ), esc_attr( $role ), $attributes,
					esc_attr( $permission_slug ), esc_attr( $permission_slug ), esc_attr( $name ), esc_attr( $permission_slug ), esc_attr( $name ) );
				if ( ! $enabled ) {
					$output .= ' disabled="disabled" readonly="readonly"';
				}
				$output .= '>';

				if ( $role == 'administrator' ) {
					$output .= '<input type="hidden" name="'
						. esc_attr( $name )
						. '" id="'
						. esc_attr( $att_id )
						. '" value="'
						. esc_attr( $role )
						. '"'
						. esc_attr( $attributes )
						. ' class="wpcf-access-check-left wpcf-access-'
						. esc_attr( $permission_slug )
						. '" data-wpcfaccesscap="'
						. esc_attr( $permission_slug )
						. '" data-wpcfaccessname="'
						. esc_attr( $name )
						. '>';
				}

				$output .= $addon
					. sprintf( '<span class="toolset-access-disabled-detector%s" data-parent="js-otg-access-settings-section-item-toggle-target-%s"></span></div></td>',
						esc_attr( $forms_disabled_checkbox_class ), esc_attr( $id ) );


			}
			$output .= '</tr>';
		}

		//Specific users row
		$output .= '<tr class="toolset-access-specific-users-row">';
		$output .= '<th>&nbsp;</th>';
		foreach ( $settings as $permission_slug => $data ) {
			$users_list = '';

			//Fix users array
			if ( isset( $permissions[ $permission_slug ]['users'] )
				&& ! empty( $permissions[ $permission_slug ]['users'] )
				&& is_string( $permissions[ $permission_slug ]['users'] )
				&& ! empty( $area ) ) {
				$permissions[ $permission_slug ]['users'] = explode( ',', $permissions[ $permission_slug ]['users'] );
				$_temp_settings_global = $access_settings->get_access_settings( true, true );
				$_temp_settings = $_temp_settings_global->$area;
				$_temp_settings[ $group_id ][ $id ]['permissions'][ $permission_slug ]['users'] = $permissions[ $permission_slug ]['users'];
				$_temp_settings_global->$area = $_temp_settings;
				$access_settings->updateAccessSettings( $_temp_settings_global );
			}

			if ( isset( $permissions[ $permission_slug ]['users'] )
				&& is_array( $permissions[ $permission_slug ]['users'] )
				&& count( $permissions[ $permission_slug ]['users'] ) > 0 ) {
				$args = array(
					'orderby' => 'user_login',
					'include' => array_slice( $permissions[ $permission_slug ]['users'], 0, 2 ),
				);
				$user_query = new \WP_User_Query( $args );
				foreach ( $user_query->results as $user ) {
					$users_list .= esc_html( $user->data->user_login ) . '<br>';
				}
				$users_list .= ( ( count( $permissions[ $permission_slug ]['users'] ) > 2 ) ? 'and '
					. ( count( $permissions[ $permission_slug ]['users'] ) - 2 )
					. ' more' : '' );
			}
			$link_disabled = ! $managed ? ' js-toolset-access-specific-user-disabled' : '';

			$output .= '<td>';
			$output .= sprintf( '<a href="#" title="%s" class="js-toolset-access-specific-user-link %s"  data-parent="js-otg-access-settings-section-item-toggle-target-%s" data-slugtitle="%s"
				data-option="%s" data-id="%s" data-groupid="%s"><i class="icon-user-plus fa fa-user-plus"></i></a>',
				__( 'Specific users', 'wpcf-access' ), $link_disabled, esc_attr( $id ), esc_attr( $data['title'] ), esc_attr( $permission_slug ), esc_attr( $id ), esc_attr( $group_id ) );
			$output .= sprintf( '<span class="js-access-toolset-specific-users-list js-access-toolset-specific-users-list-%s-%s-%s access-toolset-specific-users-list">%s</span>',
				esc_attr( $id ), esc_attr( $group_id ), esc_attr( $permission_slug ), $users_list );
			$output .= '</td>';
		}
		$output .= '</tr>';

		$permission_checkboxes = $output;
		$template_repository = AccessOutputTemplateRepository::get_instance();
		$output = $template_repository->render( $template_repository::PERMISSION_TABLE_CHECKBOXES,
			array(
				'permission_checkboxes' => $permission_checkboxes,
				'permission_options' => $permission_options,
			)
		);

		return $output;
	}


	/**
	 * Submit button.
	 *
	 * @param bool $enabled
	 * @param bool $managed
	 * @param string $id
	 * @return string
	 */
	public function generate_submit_button( $enabled = true, $managed = true, $id = '' ) {
		ob_start();
		?>
		<button
			class="wpcf-access-submit-section otg-access-settings-section-save button-primary js-wpcf-access-submit-section js-otg-access-settings-section-save">
			<?php echo esc_html( __( 'Save ', 'wpcf-access' ) ); ?>
		</button>
		<?php
		return ob_get_clean();
	}


	/**
	 * @param string $tab_section
	 *
	 * @return string
	 */
	private function get_tab_titile( $tab_section ) {
		$title = '';
		if ( $tab_section == 'post-type' ) {
			$title = __( 'Manage access control to posts, pages and custom post types', 'wpcf-access' );
		} elseif ( $tab_section == 'types-fields' ) {
			$title = __( 'Control who can view and edit custom fields  ', 'wpcf-access' );
		} elseif ( $tab_section == 'cred-forms' ) {
			$title = __( 'Choose who can use Toolset Forms on the front-end ', 'wpcf-access' );
		} elseif ( $tab_section == 'taxonomy' ) {
			$title = __( 'Manage access control to tags, categories and custom taxonomies ', 'wpcf-access' );
		} elseif ( $tab_section == 'custom-group' ) {
			$title = __( 'Manage read access to front-end pages ', 'wpcf-access' );
		} elseif ( $tab_section == 'wpml-group' ) {
			$title = __( 'Set up access control to content according to language ', 'wpcf-access' );
		} elseif ( $tab_section == 'custom-roles' ) {
			$title = __( 'Define custom user roles and set up their access to admin functions ', 'wpcf-access' );
		}

		return $title;
	}


	/**
	 * @return string
	 */
	public function get_current_tab() {
		list( $tabs, $extra_tabs, $custom_areas ) = $this->get_access_tabs();
		$current_tab = 'post-type';
		if ( isset( $_GET['tab'] ) ) {
			$current_tab_candidate = sanitize_text_field( $_GET['tab'] );
			if ( isset( $tabs[ $current_tab_candidate ] ) ) {
				$current_tab = $current_tab_candidate;
			}
		}

		return $current_tab;
	}


	/**
	 * @return array
	 */
	public function get_access_tabs() {
		$tabs = array(
			'post-type' => __( 'Post Types', 'wpcf-access' ),
			'taxonomy' => __( 'Taxonomies', 'wpcf-access' ),
			'custom-group' => __( 'Posts Groups', 'wpcf-access' ),
		);

		$extra_tabs = apply_filters( 'types-access-tab', array() );

		foreach ( $extra_tabs as $tab_slug => $tab_name ) {
			$tabs[ $tab_slug ] = $tab_name;
		}
		$custom_areas = apply_filters( 'types-access-area', array() );
		if ( count( $custom_areas ) > 0 ) {
			$tabs['third-party'] = __( 'Custom Areas', 'wpcf-access' );
		}

		if ( apply_filters( 'toolset_access_filter_is_wpml_installed', false ) ) {
			$tabs['wpml-group'] = __( 'WPML Groups', 'wpcf-access' );
		}

		$tabs['custom-roles'] = __( 'Custom Roles', 'wpcf-access' );

		return array( $tabs, $extra_tabs, $custom_areas );
	}


	/**
	 * @param array $type_data
	 *
	 * @return bool
	 */
	private function is_archive( $type_data = array() ){
		return ( ( isset( $type_data['name'] ) && 'post' === $type_data['name'] ) || ( isset( $type_data['has_archive'] ) && $type_data['has_archive'] == 1 ) ) ? 1 : '';
	}
}
