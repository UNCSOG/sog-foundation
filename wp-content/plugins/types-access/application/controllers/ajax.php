<?php

namespace OTGS\Toolset\Access;
/**
 * Class Access_Ajax
 *
 * @since 2.7
 */
class Ajax extends \Toolset_Ajax {

	const HANDLER_CLASS_PREFIX = 'Access_Ajax_Handler_';

	const CALLBACK_CLEAN_UP_DATABASE = 'clean_up_database';
	const CALLBACK_SAVE_SETTINGS = 'save_settings';
	const CALLBACK_SUGGEST_USER = 'suggest_users';
	const CALLBACK_CLONE_ROLE = 'clone_role';
	const CALLBACK_ADD_ROLE = 'add_role';
	const CALLBACK_DELETE_ROLE = 'delete_role';
	const CALLBACK_SHOW_ERROR_LIST = 'show_error_list';
	const CALLBACK_ADD_NEW_GROUP = 'add_new_group';
	const CALLBACK_SEARCH_POSTS = 'search_posts';
	const CALLBACK_SAVE_SECTION_STATUS = 'save_section_status';
	const CALLBACK_ADD_NEW_GROUP_PROCESS = 'add_new_group_process';
	const CALLBACK_MODIFY_GROUP_PROCESS = 'modify_group_process';
	const CALLBACK_REMOVE_POST_GROUP_FORM = 'remove_post_group_form';
	const CALLBACK_REMOVE_POST_GROUP_PROCESS = 'remove_post_group_process';
	const CALLBACK_REMOVE_ASSIGNMENT_POST_GROUP = 'remove_assignment_post_group';
	const CALLBACK_SELECT_POST_GROUP_FOR_POST_FORM = 'select_post_group_for_post_form';
	const CALLBACK_SELECT_POST_GROUP_FOR_POST_PROCESS = 'select_post_group_for_post_process';
	const CALLBACK_CHANGE_ROLE_CAPS_FORM = 'change_role_caps_form';
	const CALLBACK_CHANGE_ROLE_CAPS_PROCESS = 'change_role_caps_process';
	const CALLBACK_SHOW_ROLE_CAPS = 'show_role_caps';
	const CALLBACK_ADD_NEW_CAP = 'add_new_cap';
	const CALLBACK_REMOVE_CUSTOM_CAP = 'remove_custom_cap';
	const CALLBACK_HIDE_MAX_FIELDS_MESSAGE = 'hide_max_fields_message';
	const CALLBACK_DELETE_ROLE_FORM = 'delete_role_form';
	const CALLBACK_ENABLE_ADVANCED_MODE = 'enable_advanced_mode';
	const CALLBACK_ADD_WPML_GROUP_FORM = 'add_wpml_group_form';
	const CALLBACK_ADD_WPML_GROUP_PROCESS = 'add_wpml_group_process';
	const CALLBACK_LOAD_PERMISSIONS_TABLE = 'load_permissions_table';
	const CALLBACK_SPECIFIC_USERS_FORM = 'specific_users_form';
	const CALLBACK_SPECIFIC_USERS_PROCESS = 'specific_users_process';
	const CALLBACK_IMPORT_EXPORT = 'import_export';
	const CALLBACK_UPDATE_SETTINGS = 'update_settings';


	private static $access_instance;

	private static $callbacks = array(
		self::CALLBACK_CLEAN_UP_DATABASE,
		self::CALLBACK_SAVE_SETTINGS,
		self::CALLBACK_SUGGEST_USER,
		self::CALLBACK_CLONE_ROLE,
		self::CALLBACK_ADD_ROLE,
		self::CALLBACK_DELETE_ROLE,
		self::CALLBACK_SHOW_ERROR_LIST,
		self::CALLBACK_ADD_NEW_GROUP,
		self::CALLBACK_SEARCH_POSTS,
		self::CALLBACK_SAVE_SECTION_STATUS,
		self::CALLBACK_ADD_NEW_GROUP_PROCESS,
		self::CALLBACK_MODIFY_GROUP_PROCESS,
		self::CALLBACK_REMOVE_POST_GROUP_PROCESS,
		self::CALLBACK_REMOVE_POST_GROUP_FORM,
		self::CALLBACK_REMOVE_ASSIGNMENT_POST_GROUP,
		self::CALLBACK_SELECT_POST_GROUP_FOR_POST_FORM,
		self::CALLBACK_SELECT_POST_GROUP_FOR_POST_PROCESS,
		self::CALLBACK_CHANGE_ROLE_CAPS_FORM,
		self::CALLBACK_CHANGE_ROLE_CAPS_PROCESS,
		self::CALLBACK_SHOW_ROLE_CAPS,
		self::CALLBACK_ADD_NEW_CAP,
		self::CALLBACK_REMOVE_CUSTOM_CAP,
		self::CALLBACK_HIDE_MAX_FIELDS_MESSAGE,
		self::CALLBACK_DELETE_ROLE_FORM,
		self::CALLBACK_ENABLE_ADVANCED_MODE,
		self::CALLBACK_ADD_WPML_GROUP_FORM,
		self::CALLBACK_ADD_WPML_GROUP_PROCESS,
		self::CALLBACK_LOAD_PERMISSIONS_TABLE,
		self::CALLBACK_SPECIFIC_USERS_FORM,
		self::CALLBACK_SPECIFIC_USERS_PROCESS,
		self::CALLBACK_IMPORT_EXPORT,
		self::CALLBACK_UPDATE_SETTINGS,
	);

	/**
	 * @return false|Ajax|\Toolset_Ajax
	 */
	public static function get_instance() {
		if ( null === self::$access_instance ) {
			self::$access_instance = new self();
		}

		return self::$access_instance;
	}

	/**
	 * @param bool $capitalized
	 *
	 * @return string
	 */
	protected function get_plugin_slug( $capitalized = false ) {
		return ( $capitalized ? 'Access' : 'access' );
	}

	protected function get_callback_names() {
		return self::$callbacks;
	}

}
