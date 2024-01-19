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
 * @since 2.8.4
 */
class  PermissionsTablesCustomRoles {

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


	public function get_permission_table_for_custom_roles() {

		$access_roles = UserRoles::get_instance();
		$roles = $access_roles->get_editable_roles();
		$current_tab = 'custom-roles';
		$section_content = $this->admin_set_custom_roles_level_form( $roles );

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
	 * Custom roles form.
	 *
	 * @param array $roles
	 * @param bool $enabled
	 *
	 * @return string
	 */
	public function admin_set_custom_roles_level_form( $roles, $enabled = true ) {
		$output = '';

		$advanced_mode = get_option( 'otg_access_advaced_mode', 'false' );
		if ( $advanced_mode != 'true' ) {
			$advanced_mode_text = __( 'Enable advanced mode', 'wpcf-access' );
		} else {
			$advanced_mode_text = __( 'Disable advanced mode', 'wpcf-access' );
		}

		$output .= '<div id="wpcf-access-custom-roles-wrapper">';
		$output .= '<p class="toolset-access-align-right">
			<button class="button button-large button-secondary js-otg-access-add-new-role otg-access-add-new-role"><i class="icon-plus fa fa-plus"></i>'
			.
			__( 'Add a new role', 'wpcf-access' )
			. '</button></p>';

		$output .= '<div id="wpcf-access-custom-roles-table-wrapper">';
		$output .= '<table class="wpcf-access-custom-roles-table wp-list-table widefat fixed striped">
				<thead>
					<tr><th class="manage-column column-title column-primary">' . __( 'Role', 'wpcf-access' ) . '</th></tr>
				</thead>
				<tbody>';
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$ordered_roles = $access_settings->order_wp_roles();

		$users_count = count_users();
		$default_roles = array(
			'administrator' => 1,
			'editor' => 1,
			'author' => 1,
			'contributor' => 1,
			'subscriber' => 1,
		);
		foreach ( $ordered_roles as $role => $role_info ) {
			if ( $role == 'guest' ) {
				continue;
			}

			$output .= '<tr>';

			$role_link_class = 'wpcf-access-view-caps';
			if ( ( isset( $role_info['capabilities']['wpcf_access_role'] ) || $advanced_mode == 'true' )
				&& ! isset( $default_roles[ $role ] ) ) {
				$role_link_class = 'wpcf-access-change-caps';
			}

			$output .= '<td class="title column-title has-row-actions column-primary page-title">
						<div class="wpcf-access-roles wpcf-access-roles-custom">
						     <span><a href="#" class="'
				. $role_link_class
				. '" data-role="'
				. sanitize_title( $role )
				. '">'
				.
				taccess_t( $role_info['name'], $role_info['name'] )
				. '</a>'
				.
				( isset( $users_count['avail_roles'][ $role ] ) ? ' (' . $users_count['avail_roles'][ $role ] . ')'
					: ' (0)' )
				. '</span>
					 	</div>
					 	<div class="row-actions"><span class="edit">';

			if ( ( isset( $role_info['capabilities']['wpcf_access_role'] ) || $advanced_mode == 'true' )
				&& ! isset( $default_roles[ $role ] ) ) {
				//Change Caps link
				$output .= ' <span><a href="#" class="wpcf-access-change-caps" data-role="'
					. sanitize_title( $role )
					. '">'
					. __( 'Change permissions', 'wpcf-access' )
					. '</a></span> ';
				$output .= ' <span> | <a href="#" data-role="'
					. sanitize_title( $role )
					. '" class="wpcf-access-delete-role js-wpcf-access-delete-role">'
					.
					__( 'Delete role', 'wpcf-access' )
					. '</a></span> ';
			} elseif ( $advanced_mode == 'true' && isset( $default_roles[ $role ] ) ) {
				$output .= ' <span><a href="#" class="wpcf-access-change-caps" data-role="' . esc_attr( $role ) . '">'
					. esc_html( __( 'Change permissions', 'wpcf-access' ) ) . '</a></span> ';
			} else {
				$output .= ' <span><a href="#" class="wpcf-access-view-caps" data-role="'
					. sanitize_title( $role )
					. '">'
					. __( 'View permissions', 'wpcf-access' )
					. '</a></span> ';
			}
			$output .= ' <span> | <a href="users.php?role='
				. $role
				. '">'
				. __( 'View users', 'wpcf-access' )
				. '</a></span> ';
			$output .= '</div></td></tr>';
		}

		$output .= '</tbody>';
		$output .= '<tfoot>
					<tr class="manage-column column-title column-primary sortable desc"><td>'
			. __( 'Role', 'wpcf-access' )
			. '</td></tr>
				</tfoot></table>';
		$output .= '</div>';
		$output .= '<p>'
			. __( 'Advanced mode', 'wpcf-access' )
			. ': <button data-status="'
			. ( $advanced_mode == 'true'
				? 'true' : 'false' )
			. '" value="'
			. $advanced_mode_text
			.
			'" class="button button-large button-secondary js-otg_access_enable_advanced_mode"><i class="fa icon-'
			. ( $advanced_mode != 'true' ? 'lock fa-lock' : 'unlock fa-unlock' )
			. '"></i>'
			. $advanced_mode_text
			. '</button></p>';
		$output .= '</div>';
		$output .= '<div id="wpcf-access-new-role" class="wpcf-access-new-role-wrap js-otg-access-new-role-wrap">
		<table class="otg-access-new-role-extra js-otg-access-new-role-extra"  style="display:none">';
		$output .= '<tr>
						<td width="50%"><label for="otg-access-new-role-name">'
			. __( 'Role name (at least 5 characters)', 'wpcf-access' )
			. '</label></td>
						<td><input type="text" name="types_access[new_role]" class="js-otg-access-new-role-name" id="otg-access-new-role-name" value="" /></td>
						</tr>
						<tr>
						<td><label for="toolset-access-copy-caps-from">'
			. __( 'Copy privileges from', 'wpcf-access' )
			. ':</label></td>
						<td>
							<select id="toolset-access-copy-caps-from" class="js-toolset-access-copy-caps-from toolset-access-copy-caps-from">
							<option value="">'
			. __( 'None', 'wpcf-access' )
			. '</options>';
		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$ordered_roles = $access_settings->order_wp_roles();
		foreach ( $ordered_roles as $role => $role_info ) {
			$output .= '<option value="' . $role . '">' . ( isset( $role_info['name'] ) ? $role_info['name']
					: ucwords( $role ) ) . '</option>';
		}
		$output .= '</select>
						</td>
		</table>
		<div class="ajax-response js-otg-access-message-container"></div>
   		</div>	<!-- #wpcf-access-new-role -->';

		return $output;
	}
}
