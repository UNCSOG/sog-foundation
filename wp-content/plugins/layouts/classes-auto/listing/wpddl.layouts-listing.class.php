<?php

class WPDD_LayoutsListing
{
	const DEFAULT_GROUP = 'post_types';
	const DEFAULT_STATUS = 'publish';

	private $group_slug = self::DEFAULT_GROUP;
	private $args = array();
	private $layouts_query = null;
	private $layouts_list = array();
	private $count_what = '';
	private $mod_url = array();
	private $column_active = '';
	private $column_sort_to = 'ASC';
	private $column_sort_now = 'ASC';
	private $column_sort_date_to = 'DESC';
	private $column_sort_date_now = 'DESC';
	private $count_published ;
	private $count_trash;

	public static $OPTIONS_ALERT_TEXT;

	private static $instance;

	private $get_all;

	private function __construct()
	{

		self::$OPTIONS_ALERT_TEXT = __('Some changes are not saved. If you don\'t see the \'update\' button, scroll the list.', 'ddl-layouts');

		add_action('wp_ajax_set_layout_status', array(&$this, 'set_layout_status_callback'));
		add_action('wp_ajax_delete_layout_record', array(&$this, 'delete_layout_record_callback'));
		add_action('wp_ajax_change_layout_usage_box', array(&$this, 'set_change_layout_usage_box'));

		add_action('wp_ajax_js_change_layout_usage_for_'.WPDD_Layouts_PostTypesManager::POST_TYPES_OPTION_NAME, array(&$this, 'set_layouts_post_types_on_usage_change_js'));
		add_action('wp_ajax_js_change_layout_usage_for_'.WPDD_layout_post_loop_cell_manager::POST_TYPES_LOOPS_NAME, array(&$this, 'set_layouts_archives_on_usage_change_js'));
		add_action('wp_ajax_js_change_layout_usage_for_'.WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION, array(&$this, 'set_layouts_others_on_usage_change_js'));


		add_action('wp_ajax_get_ddl_listing_data', array(&$this, 'get_ddl_listing_data'));

		if (isset($_GET['page']) && $_GET['page'] == WPDDL_LAYOUTS_POST_TYPE) {
			add_action('admin_enqueue_scripts', array($this, 'listing_scripts'));
		}

		$this->get_all = DDL_GroupedLayouts::getInstance();
		add_action('wp_ajax_get_all_layouts_posts', array(&$this->get_all, 'get_all_layouts_posts'));
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new WPDD_LayoutsListing();
		}

		return self::$instance;
	}

	/**
	 * @param $current
	 *
	 * @return mixed
	 * @deprecated
	 */
	public function print_single_posts_assigned_section($current)
	{
		global $wpddlayout;

		return $wpddlayout->individual_assignment_manager->return_assigned_layout_list_html($current);
	}

	public function init()
	{
		$this->set_mod_url();
		$this->set_args();
		$this->set_count_what();
		$this->set_count();
		$this->display_list();
	}

	public function set_change_layout_usage_box()
	{
		if( user_can_assign_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['layout-select-set-change-nonce'], 'layout-select-set-change-nonce')) {
			$nonce = wp_create_nonce('layout-set-change-post-types-nonce');

			$html = $this->print_dialog_checkboxes($_POST['layout_id'], false, '');
			$send = wp_json_encode(array('message' => array('html_data' => $html, 'nonce' => $nonce, 'layout_id' => $_POST['layout_id'])));
		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	public function print_dialog_checkboxes($current = false, $do_not_show = false, $id = "", $show_ui = true)
	{
		$current = $current ? (int)$current : null;
		$html = '';
		$html .= apply_filters( 'ddl_get_change_dialog_html', $html, $current, $do_not_show, $id, $show_ui );
		$html .= $this->print_single_posts_assign_section($current);
		return $html;
	}

	public function print_single_posts_assign_section($current)
	{
		ob_start();
		include WPDDL_GUI_ABSPATH . 'editor/templates/individual-posts.box.tpl.php';
		return ob_get_clean();
	}

	public function set_layouts_post_types_on_usage_change_js()
	{
		global $wpddlayout;

		if( user_can_assign_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['layout-set-change-post-types-nonce'], 'layout-set-change-post-types-nonce')) {
			$post_types = isset($_POST['post_types']) && is_array($_POST['post_types']) ? array_unique($_POST['post_types']) : array();

			if (isset($_POST['extras'])) {
				$extras = $_POST['extras'];

				if (isset($extras['post_types']) && count($extras['post_types']) > 0) {
					$types_to_batch = $extras['post_types'];
				}
			}

			if (isset($extras) && isset($types_to_batch)) {
				$wpddlayout->post_types_manager->handle_set_option_and_bulk_at_once($_POST['layout_id'], $post_types, $types_to_batch);

			} else {
				$wpddlayout->post_types_manager->handle_post_type_data_save($_POST['layout_id'], $post_types, count($post_types) === 0 );
			}

			$status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';
			//TODO: review with new method
			$send = $this->get_send($status, $_POST['html'], $_POST['layout_id'], array(), $_POST);

		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	public function set_layouts_archives_on_usage_change_js()
	{
		global $wpddlayout;

		if( user_can_assign_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['layout-set-change-post-types-nonce'], 'layout-set-change-post-types-nonce')) {

			$default_archives = isset($_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_DEFAULT_LOOPS_NAME]) ? $_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_DEFAULT_LOOPS_NAME] : array();

			$wpddlayout->layout_post_loop_cell_manager->handle_archives_data_save($default_archives, $_POST['layout_id']);

			$status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';
			//TODO: review with new method
			$send = $this->get_send($status, $_POST['html'], $_POST['layout_id'], array(), $_POST);

		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	public function set_layouts_others_on_usage_change_js()
	{
		global $wpddlayout;

		if( user_can_assign_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['layout-set-change-post-types-nonce'], 'layout-set-change-post-types-nonce')) {

			$others_section = isset($_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION]) ? $_POST[WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION] : array();

			$wpddlayout->layout_post_loop_cell_manager->handle_others_data_save($others_section, $_POST['layout_id']);

			$status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';
			//TODO: review with new method
			$send = $this->get_send($status, $_POST['html'], $_POST['layout_id'], array(), $_POST);
		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	/**
	 * @param $status
	 * @param bool $where
	 * @param null $layout_id
	 * @param array $message
	 * @param array $args
	 *
	 * @return bool|false|mixed|string
	 * TODO: review with new methods/classes
	 */
	public function get_send( $status, $where = false, $layout_id = null, $message = array(), $args = array() )
	{
		$send = $this->set_up_send_data($status, $where, $layout_id, $message, $args);
		return $send;
	}

	/**
	 * @param $status
	 * @param bool $where
	 * @param null $layout_id
	 * @param array $message
	 * @param array $args
	 *
	 * @return bool|false|mixed|string
	 * TODO: review with new methods/classes
	 */
	public function set_up_send_data($status, $where = false, $layout_id = null, $message = array(), $args = array())
	{
		if( $status === 'publish' ){
			$this->group_slug = isset( $args['group_slug'] ) ? $args['group_slug'] : 'free';
			$Data = array( 'Data' => $this->get_layout_group_list( $this->group_slug, $status ) );
		} else {
			$Data = array( 'Data' => $this->get_grouped_layouts($status, $args) );
		}


		if ($where === 'editor' || $where === 'listing') {

			$message['dialog'] = $this->print_dialog_checkboxes($layout_id);

			if ($where === 'editor') {
				global $wpdd_gui_editor;
				$message['list'] = $wpdd_gui_editor->get_where_used_output($layout_id);
			}

		}

		$Data['message'] = $message;

		$send = wp_json_encode($Data);

		return $send;
	}

	public function get_grouped_layouts($status, $args = array())
	{
		$this->get_all->init($status);
		$this->layouts_query = $this->get_all->get_query();
		$this->layouts_list = $this->get_all->get_layouts();
		return $this->get_all->get_groups($args);
	}

	public function listing_scripts()
	{
		global $wpddlayout;

		//speed up ajax calls sensibly
		wp_deregister_script('heartbeat');
		wp_register_script('heartbeat', false);

		$localization_array = array(
			'res_path' => WPDDL_RES_RELPATH,
			'listing_lib_path' => WPDDL_GUI_RELPATH . "listing/js/",
			'editor_lib_path' => WPDDL_GUI_RELPATH . "editor/js/",
			'common_rel_path' => WPDDL_TOOLSET_COMMON_RELPATH,
			'ddl_listing_nonce' => wp_create_nonce('ddl_listing_nonce'),
			'ddl_listing_show_posts_nonce' => wp_create_nonce('ddl_listing_show_posts_nonce'),
			'ddl_listing_status' => isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish',
			'lib_path' => WPDDL_RES_RELPATH . '/js/external_libraries/',
			'strings' => $this->get_listing_js_strings(),
			'is_listing_page' => true,
			'user_can_delete' => user_can_delete_layouts(),
			'user_can_assign' => user_can_assign_layouts(),
			'user_can_edit' => user_can_edit_layouts(),
			'user_can_create' => user_can_create_layouts(),
			'available_cell_types' => $wpddlayout->get_cell_types(),
			'toolset_cells_data' => WPDD_Utils::toolsetCellTypes(),
			'max_num_posts' => DDL_MAX_NUM_POSTS,
			'NUMBER_OF_ITEMS' => DDL_GroupedLayouts::NUMBER_OF_ITEMS,
			'WPDDL_VERSION' => WPDDL_VERSION
		);

		$wpddlayout->enqueue_scripts(array('dd-listing-page-main', 'ddl-post-types'));
		$wpddlayout->localize_script('dd-listing-page-main', 'DDLayout_settings', array(
			'DDL_JS' => $localization_array,
			'DDL_OPN' => self::change_layout_dialog_options_name(),
			'items_per_page' => DDL_ITEMS_PER_PAGE
		));
		$wpddlayout->enqueue_styles(array('views-pagination-style', 'dd-listing-page-style'));
	}

	public static function change_layout_dialog_options_name(){
		$dialog_option_names_array = apply_filters("ddl_change_dialog_options_names", array(
			'ARCHIVES_OPTION' => WPDD_layout_post_loop_cell_manager::POST_TYPES_LOOPS_NAME
		, 'POST_TYPES_OPTION' => WPDD_Layouts_PostTypesManager::POST_TYPES_OPTION_NAME
		, 'OTHERS_OPTION' => WPDD_layout_post_loop_cell_manager::WORDPRESS_OTHERS_SECTION
		, 'INDIVIDUAL_POSTS_OPTION' => WPDD_Layouts_IndividualAssignmentManager::INDIVIDUAL_POST_ASSIGN_CHECKBOXES_NAME
		, 'BULK_ASSIGN_POST_TYPES_OPTION' => WPDD_Layouts_PostTypesManager::POST_TYPES_APPLY_ALL_OPTION_NAME
		) );

		return $dialog_option_names_array;
	}

	private function get_listing_js_strings()
	{
		return array(
			'is_a_parent_layout' => __("This layout has children. It can't be deleted.", 'ddl-layouts'),
			'is_a_parent_layout_and_cannot_be_changed' => __("This layout has children. You should assign one of its children to content and not this parent layout.", 'ddl-layouts'),
			'to_a_post_type' => __("This layout is assigned to a post type. It can't be deleted.", 'ddl-layouts'),
			'to_an_archive' => __("This layout is assigned to an archive. It can't be deleted.", 'ddl-layouts'),
			'to_archives' => __("This layout is assigned to %s archives. It can't be deleted.", 'ddl-layouts'),
			'to_post_types' => __('This layout is assigned to %s post types. It can\'t be deleted.', 'ddl-layouts'),
			'to_a_post_item' => __('This layout is assigned to a post. It can\'t be deleted.', 'ddl-layouts'),
			'to_posts_items' => __("This layout is assigned to %s posts. It can't be deleted.", 'ddl-layouts'),
			'no_more_pages' => __("This layout is already assigned to all pages.", 'ddl-layouts'),
			'no_more_posts' => __("This layout is already assigned to all posts items.", 'ddl-layouts'),
			'no_more_pages_in_db' => __("No pages found.", 'ddl-layouts'),
			'no_more_posts_in_db' => __("No post items found.", 'ddl-layouts'),
			'user_no_caps' => __("You don't have permission to perform this action.", 'ddl-layouts'),
			'duplicate_dialog_title' => __("Toolset resources", 'ddl-layouts'),
			'duplicate_results_title' => __("Toolset duplicate resources summary", 'ddl-layouts'),
			'duplicate_result_message_all' => __("The duplicate that you created uses copies of Toolset elements. You can edit it freely. The original layout will not change when you edit the duplicate.", 'ddl-layouts'),
			'duplicate_result_message_some' => __("This duplicate layout uses some Toolset elements from the original layout. When you edit the layout, the original may change too, if you edit shared Toolset elements.", 'ddl-layouts'),
			'duplicate_anchor_text' => __("Show details of duplicate Toolset elements", 'ddl-layouts'),
			'duplicate_anchor_text_hide' => __("Hide details of duplicate Toolset elements", 'ddl-layouts'),
			'cancel' => __("Cancel", 'ddl-layouts'),
			'close' => __("Close", 'ddl-layouts'),
			'duplicate' => __("Duplicate", 'ddl-layouts'),
		);
	}

	private function set_args($args = array())
	{
		$defaults = array(
			'post_type' => WPDDL_LAYOUTS_POST_TYPE,
			'suppress_filters' => false,
			'posts_per_page' => DDL_ITEMS_PER_PAGE,
			'order' => 'ASC',
			'orderby' => 'title',
			'post_status' => isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish',
			'paged' => isset($_GET['paged']) ? $_GET['paged'] : 1
		);

		$this->args = wp_parse_args($args, $defaults);
	}

	private function set_mod_url($args = array())
	{
		$mod_url = array( // array of URL modifiers
			'orderby' => '',
			'order' => '',
			'search' => '',
			'items_per_page' => '',
			'paged' => '',
			'status' => ''
		);
		$this->mod_url = wp_parse_args($args, $mod_url);
	}

	private function found_posts()
	{
		return is_object($this->layouts_query) ? $this->layouts_query->found_posts : 0;
	}

	private function post_count()
	{
		return is_object($this->layouts_query) ? $this->layouts_query->post_count : 0;
	}

	private function get_layout_list()
	{
		return $this->layouts_list;
	}

	private function set_count()
	{
		global $wpdb;

		$this->count_published = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) from $wpdb->posts WHERE post_type = %s AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE));
		$this->count_trash = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) from $wpdb->posts WHERE post_type = %s AND post_status = 'trash'", WPDDL_LAYOUTS_POST_TYPE));
	}

	private function get_arg($arg)
	{
		return isset($this->args[$arg]) ? $this->args[$arg] : null;
	}

	private function get_count_published()
	{
		return $this->count_published;
	}

	private function get_count_trash()
	{
		return $this->count_trash;
	}

	private function get_count_what()
	{
		return $this->count_what;
	}

	private function set_count_what()
	{
		$this->count_what = $this->get_arg('post_status') == 'publish' ? 'trash' : 'publish';
	}

	private function load_js_templates()
	{
		WPDD_FileManager::include_files_from_dir(WPDDL_GUI_ABSPATH . "/listing/", "js/templates", $this);
	}

	public function set_layout_status_callback()
	{

		// Clear any errors that may have been rendered that we don't have control of.
		if (ob_get_length()) {
			ob_clean();
		}

		if( user_can_edit_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['layout-select-trash-nonce'], 'layout-select-trash-nonce')) {

			if( $_POST['status'] === 'trash' && user_can_delete_layouts() === false ){
				die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
			}

			$http_id = $_POST['layout_id'];
			$status = $_POST['status'];
			$current_page_status = isset($_POST['current_page_status']) ? $_POST['current_page_status'] : 'publish';

			if (is_array($http_id)) {
				$ids = $http_id;
			} else {
				$ids = array($http_id);
			}

			$message = array();

			foreach ($ids as $id) {
				$data = array(
					'ID' => $id,
					'post_status' => $status
				);

				$message[] = wp_update_post($data);
			}

			if( isset( $_POST['do_not_reload'] ) && $_POST['do_not_reload'] === 'yes' ){
				$send = wp_json_encode( array('Data' => array( 'message' => $message ) ) );
			} else {
				$send = $this->get_send($current_page_status, false, $http_id, $message, $_POST);
			}

		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	public function delete_layout_record_callback()
	{

		// Clear any errors that may have been rendered that we don't have control of.
		if (ob_get_length()) {
			ob_clean();
		}

		if( user_can_delete_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['layout-delete-layout-nonce'], 'layout-delete-layout-nonce')) {
			$layout_id = $_POST['layout_id'];
			$current_page_status = isset($_POST['current_page_status']) ? $_POST['current_page_status'] : 'trash';

			if (!is_array($layout_id)) {
				$layout_id = array($layout_id);
			}

			$message = self::delete_layout( $layout_id );

			$send = $this->get_send($current_page_status, false, $layout_id, $message, $_POST);

		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	public function get_all_layouts_posts()
	{
		$this->get_all->get_all_layouts_posts();
	}

	public static function delete_layout( $layout_id = array() ){

		if( empty( $layout_id ) ){
			return null;
		}

		global $wpddlayout;

		$message = array();

		foreach ($layout_id as $id) {
			$res = wp_delete_post($id, true);
			// if deleted clean from options
			if ($res !== false) {
				$wpddlayout->post_types_manager->clean_layout_post_type_option($id);
				$message[] = $res->ID;
			}
			do_action( 'ddl_layout_has_been_deleted', $res, $id);
		}

		return $message;
	}

	public function get_listing_store_controllers_factory( $group_slug ){
		return new WPDD_Listing_Store_Controller_Factory( $group_slug );
	}

	public function get_ddl_listing_data()
	{
		// Clear any errors that may have been rendered that we don't have control of.
		if (ob_get_length()) {
			ob_clean();
		}

		if( user_can_edit_layouts() === false ){
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}
		if ($_POST && wp_verify_nonce($_POST['ddl_listing_nonce'], 'ddl_listing_nonce')) {

			$this->group_slug = isset( $_POST['group_slug'] ) ? $_POST['group_slug'] : $this->group_slug;

			$data = $this->get_layout_group_list( $this->group_slug, $_POST['status'], $_POST );

			if (defined('JSON_UNESCAPED_UNICODE')) {
				// phpcs:ignore PHPCompatibility.Constants.NewConstants.json_unescaped_unicodeFound
				$send = wp_json_encode(array('Data' => $data), JSON_UNESCAPED_UNICODE);
			} else {
				$send = wp_json_encode(array('Data' => $data));
			}
		} else {
			$send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
		}

		die($send);
	}

	private function display_list()
	{
		$status = isset($_GET['status']) && $_GET['status'] === 'trash' ? $_GET['status'] : 'publish';

		if( 'trash' === $status ){
			$data = $this->get_grouped_layouts( $status );
		} else {
			$data = $this->get_layout_group_list( $this->group_slug, $status );
		}

		if (defined('JSON_UNESCAPED_UNICODE')) {
			// phpcs:ignore PHPCompatibility.Constants.NewConstants.json_unescaped_unicodeFound
			$init_json = wp_json_encode(array('Data' => $data), JSON_UNESCAPED_UNICODE);
		} else {
			$init_json = wp_json_encode(array('Data' => $data));
		}

		$init_json_listing = base64_encode($init_json);

		if( 'trash' === $status ){
			include WPDDL_GUI_ABSPATH . 'templates/listing/layouts_list_trash.tpl.php';
		} else {
			include WPDDL_GUI_ABSPATH . 'templates/listing/layouts_list_new.tpl.php';
		}

		$this->load_js_templates();
	}

	private function get_layout_group_list( $group_slug = self::DEFAULT_GROUP, $status = self::DEFAULT_STATUS, $args = array() ){

		$factory = $this->get_listing_store_controllers_factory( $group_slug , $status, $args );

		$controller = $factory->build();

		$data = $controller->get_layouts_list();

		return $data;
	}
}
