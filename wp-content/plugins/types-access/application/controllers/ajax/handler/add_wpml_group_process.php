<?php
/**
 * Class Access_Ajax_Handler_Add_Wpml_Group_Process
 * Add new WPML group process
 *
 * @since 2.7
 */

class Access_Ajax_Handler_Add_Wpml_Group_Process extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Access_Ajax_Handler_Add_Wpml_Group_Process constructor.
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

		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance();
		$settings_access = $access_settings->get_types_settings( true, true );

		$languages = array();
		$title_languages_array = array();
		$wpml_active_languages = apply_filters( 'wpml_active_languages', '', array( 'skip_missing' => 0 ) );

		if ( isset( $_POST['languages'] ) ) {
			for ( $i = 0, $count_lang = count( $_POST['languages'] ); $i < $count_lang; $i ++ ) {
				$languages[ $_POST['languages'][ $i ]['value'] ] = 1;
				$title_languages_array[] = $wpml_active_languages[ $_POST['languages'][ $i ]['value'] ]['translated_name'];
			}
		}
		if ( count( $title_languages_array ) > 1 ) {
			$title_languages = implode( ', ', array_slice( $title_languages_array, 0, count( $title_languages_array )
					- 1 ) ) . ' and ' . end( $title_languages_array );
		} else {
			$title_languages = implode( ', ', $title_languages_array );
		}

		$post_type = ( isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'post' );
		$post_type_object = get_post_type_object( $post_type );

		if ( ! empty( $_POST['group_nice'] ) ) {
			$nice = $_POST['group_nice'];
			$_POST['group_name'] = $title_languages . ' ' . $post_type_object->labels->name;
		} else {
			$_POST['group_name'] = $title_languages . ' ' . $post_type_object->labels->name;
			$nice = sanitize_title( 'wpcf-wpml-group-' . md5( $_POST['group_name'] ) );
		}

		$edit_any = [];
		$delete_any = [];
		$edit_own = [];
		$delete_own = [];
		$publish = [];

		if ( isset( $settings_access[$post_type]['permissions']['read']['roles'] ) ) {
			$post_type_settings = $settings_access[ $post_type ]['permissions'];
			$read = $post_type_settings['read']['roles'];
			$edit_any = $post_type_settings['edit_any']['roles'];
			$delete_any = $post_type_settings['delete_any']['roles'];
			$edit_own = $post_type_settings['edit_own']['roles'];
			$delete_own = $post_type_settings['delete_own']['roles'];
			$publish = $post_type_settings['publish']['roles'];
		} else {
			$ordered_roles = $access_settings->order_wp_roles();

			$read = array();
			$gui_common = \OTGS\Toolset\Access\Models\GuiCommon::get_instance();
			foreach ( $ordered_roles as $role => $roles_data ) {
				if ( $gui_common->check_for_cap( 'read', $roles_data ) ) {
					$read[] = $role;
				}

				if ( $gui_common->check_for_cap( 'edit_posts', $roles_data ) ) {
					$edit_own[] = $role;
				}

				if ( $gui_common->check_for_cap( 'edit_others_posts', $roles_data ) ) {
					$edit_any[] = $role;
				}

				if ( $gui_common->check_for_cap( 'delete_posts', $roles_data ) ) {
					$delete_own[] = $role;
				}

				if ( $gui_common->check_for_cap( 'delete_others_posts', $roles_data ) ) {
					$delete_any[] = $role;
				}

				if ( $gui_common->check_for_cap( 'edit_published_posts', $roles_data ) ) {
					$publish[] = $role;
				}
			}
		}
		if ( $_POST['form_action'] == 'add' ) {
			$groups[ $nice ] = array(
				'title' => sanitize_text_field( $_POST['group_name'] ),
				'mode' => 'permissions',
				'permissions' => array(
					'read' => array( 'roles' => $read ),
					'edit_any' => array( 'roles' => $edit_any ),
					'delete_any' => array( 'roles' => $delete_any ),
					'edit_own' => array( 'roles' => $edit_own ),
					'delete_own' => array( 'roles' => $delete_own ),
					'publish' => array( 'roles' => $publish ),
				),
				'languages' => $languages,
				'post_type' => $_POST['post_type'],
			);
		} else {
			$group_id = $_POST['group_id'];
			$settings_access[ $group_id ]['title'] = sanitize_text_field( $_POST['group_name'] );
			$settings_access[ $group_id ]['languages'] = $languages;
			$access_settings->updateAccessTypes( $settings_access );
			wp_send_json_success( sanitize_text_field( $_POST['group_name'] ) );
		}
		$process = true;
		if (
			! empty( $settings_access )
			&& isset( $settings_access[ $nice ] )
		) {
			$process = false;
		}

		if ( ! $process ) {
			wp_send_json_error( 'error' );
		}

		$settings_access = array_merge( $settings_access, $groups );

		$access_settings->updateAccessTypes( $settings_access );

		$wpml_active_languages = apply_filters( 'wpml_active_languages', '', array( 'skip_missing' => 0 ) );
		$languages_list = array();
		foreach ( $languages as $lang => $lang_data ) {
			$languages_list[] = $wpml_active_languages[ $lang ]['native_name'];
		}
		if ( $_POST['form_action'] == 'modify' ) {
			echo $_POST['group_name'];
			die();
		}
		$group['id'] = $nice;
		wp_send_json_success( $group['id'] );

	}
}
