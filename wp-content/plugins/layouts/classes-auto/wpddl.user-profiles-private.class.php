<?php

/**
 * Class WPDD_Layouts_Users_Profiles_Private
 * @since 2.4.3
 * Use it as a singleton with Toolset_Singleton_Factory::get( 'WPDD_Layouts_Users_Profiles_Private' );
 */
class WPDD_Layouts_Users_Profiles_Private extends WPDD_Layouts_Users_Profiles{

	protected $user_option = 'users_options_private';
	protected $default_cap = DDL_EDIT_PRIVATE;
	protected $wp_relative_cap = 'edit_others_pages';

	protected $perms_to_pages = array(
		'admin.php?page=dd_layouts&amp;new_layout=true' => DDL_CREATE_PRIVATE,
		'dd_layouts_edit' => DDL_EDIT_PRIVATE,
		'dd_layouts_debug' => DDL_EDIT_PRIVATE,
		'dd_tutorial_videos' => DDL_EDIT_PRIVATE,
		'dd_layouts_troubleshoot' => DDL_EDIT_PRIVATE,
	);

	public static function ddl_get_capabilities(){
		return array(
			DDL_CREATE_PRIVATE => "Create content layouts",
			DDL_EDIT_PRIVATE => "Edit content layouts",
			DDL_DELETE_PRIVATE => "Delete content layouts"
		);
	}

	public function get_label(){
		return __( 'Content Layout capabilities', 'wpcf_access' );
	}

	public static function user_can_create(){
		return current_user_can( DDL_CREATE_PRIVATE );
	}

	public static function user_can_assign(){
		return true;
	}

	public static function user_can_edit(){
		return current_user_can( DDL_EDIT_PRIVATE );
	}

	public static function user_can_delete(){
		return current_user_can( DDL_DELETE_PRIVATE );
	}
}