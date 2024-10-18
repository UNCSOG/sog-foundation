<?php

/**
 * Class WPDD_Layouts
 * This is Layouts main class and application point of entry, it should be loaded by WPDD_LayoutsPlugin class only on
 * after_setup_theme / 997 it is strongly recommended to use it as global $wpddlayout; and avoid calling its getInstance method
 * wildly.
 */

class WPDD_Layouts {
	private static $instance;
	public static $raw_cache = null;
	public static $decoded_cache = null;

	public $header_added = false;
	private $layouts_editor_page = false;
	public $css_manager;
	public $js_manager;
	private $scripts_manager;
	public $post_types_manager;
	public $frameworks_options_manager;
	private $css_framework;
	public $listing_page;
	public $layout_post_loop_cell_manager;
	public $create_layout_for_pages_manager;
	private $registered_cells = array();
	private $rendered_layout_id = null;
	private $where_used_count = 0;
	private $ddl_caps;
	private $ddl_private_layout_caps;
	private $options;
	private $cells_factory = null;
	public $individual_assignment_manager;

	static $containers_elements = array();

	function __construct() {
		do_action( 'ddl-before_init_layouts_plugin' );
		$this->add_common_hooks();
		$this->setUp();
		$this->add_front_end_hooks();
		$this->add_admin_hooks();
		$this->add_iframe_hooks();
		do_action( 'ddl-after_init_layouts_plugin' );
	}

	function setUp(){
		self::set_containers_elements();
		$this->save_framework_default();
		$this->plugin_localization();

		WPDD_Layouts_RenderManager::getInstance();
		WPDDL_Layouts_WPML::getInstance();
		WPDDL_ModuleManagerSupport::getInstance();
		WDDL_ExtraModulesLoader::getInstance();

		$this->init_layouts_caps();
		$this->layout_post_loop_cell_manager = WPDD_layout_post_loop_cell_manager::getInstance();

		$this->cells_factory = WPDD_RegisteredCellTypesFactory::build();

		$this->scripts_manager = WPDDL_scripts_manager::getInstance();
		$this->post_types_manager = WPDD_Layouts_PostTypesManager::getInstance();
		$this->individual_assignment_manager = new WPDD_Layouts_IndividualAssignmentManager();

		$this->wpddl_init();

		$this->css_manager = WPDD_Layouts_CSSManager::getInstance();
		$this->js_manager  = WPDD_Layouts_JsManager::getInstance();

		$this->frameworks_options_manager = WPDD_Layouts_CSSFrameworkOptions::getInstance();
		$this->set_css_framework( $this->frameworks_options_manager->get_current_framework() );


		new WPDDL_Options();
		new WPDDL_OptionsImportExport();
		new WPDD_GUI_FRONTEND_EDITOR( $this );
		global $wpdd_gui_editor;

		if ( is_admin() ) {

			/*
			 * execute only in admin
			 */
			if ( class_exists( 'WPDDL_Admin_Pages' ) ) {
				WPDDL_Admin_Pages::getInstance();
			} else if ( class_exists( 'WPDDL_Admin_Pages_Embedded' ) ) {
				WPDDL_Admin_Pages_Embedded::getInstance();
			}

			$user_helper = $this->get_private_layout_user_helper();
			$user_helper->maybe_add_hooks();

			$wpdd_gui_editor = new WPDD_GUI_EDITOR( $this, $this->scripts_manager );
			$this->fix_up_views_slugs();

		} else {

			/*
			 * execute in front - end
			 */
			$this->show_wpddl_frontend_styles();
			WPDD_Layouts_Cache_Singleton::getInstance();
		}

	}

	private function init_layouts_caps(){
		global $wp_roles, $current_user;

		$this->ddl_caps = Toolset_Singleton_Factory::get( 'WPDD_Layouts_Users_Profiles', $wp_roles, $current_user );
		$this->ddl_caps->add_hooks();

		$this->ddl_private_layout_caps = Toolset_Singleton_Factory::get( 'WPDD_Layouts_Users_Profiles_Private', $wp_roles, $current_user );
		$this->ddl_private_layout_caps->add_hooks();
	}

	function add_front_end_hooks(){
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_js' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_css' ) );
			add_action( 'get_layout_id_for_render', array( &$this, 'get_layout_id_for_render_callback' ), 888, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_toolset_common_styles' ) );
		}
	}

	function add_admin_hooks(){
		if ( is_admin() ) {

			global $pagenow;

			// a little trick to have global $this available in post edit page upon construction
			add_action( 'init', array( &$this, 'init_create_layout_for_pages' ), 20 );
			// Action added in Gutenberg editor page specifically: https://github.com/WordPress/gutenberg/issues/1316 running late enough to have what we need and early enough so that scripts are not enqueued yet
			add_action( 'init', array( $this, 'init_gutenberg_overlay' ), 20 );


			if ( $pagenow == 'plugins.php' ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'plugin_page_styles' ) );
			}

			if ( isset( $_GET['page'] ) && ( $_GET['page'] == WPDDL_LAYOUTS_POST_TYPE || $_GET['page'] == 'toolset-export-import' || $_GET['page'] == 'dd_layouts_edit' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'preload_styles' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'preload_scripts' ) );
			}

			if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'dd_tutorial_videos' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'help_page_scripts' ) );
			}

			add_action( 'wp_ajax_ddl_create_layout', array( $this, 'create_layout_callback' ) );
			add_action( 'wp_ajax_ddl_create_private_layout', array( $this, 'create_private_layout_callback' ) );
			add_action( 'wp_ajax_ddl_dismiss_template_message', array( $this, 'ddl_dismiss_template_message' ) );
			add_action( 'wpml_register_string_packages', array( $this, 'register_all_strings_for_translation' ), 10, 0 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_toolset_common_styles' ) );

			add_filter( 'icl_get_extra_debug_info', array( $this, 'add_extra_debug_information' ) );
			add_filter( 'ddl-does_layout_with_this_name_exist', array( &$this, 'does_layout_with_this_name_exist' ), 10, 1 );
		}
	}


	function add_common_hooks(){

		add_action( 'wp_ajax_nopriv_' . WPDDL_LAYOUTS_CSS, array( &$this, 'handle_layout_css_from_db_print' ), 10 );
		add_action( 'init', array( &$this, 'init_listing_page' ), 20 );
		add_action( 'init', array( &$this, 'init_scripts' ), 20 );
		add_action( 'init', array( &$this, 'init_editor_page' ), 999 );
		add_action( 'before_delete_post', array( &$this, 'before_delete_post_action' ) );
		add_action( 'add_attachment', array( &$this, 'add_attachment_action' ) );
		add_action( 'ddl-enqueue_scripts', array( &$this, 'enqueue_scripts' ), 10, 1 );
		add_action( 'ddl-enqueue_styles', array( &$this, 'enqueue_styles' ), 10, 1 );
		add_action( 'ddl-localize_script', array( &$this, 'localize_script' ), 10, 3 );
		add_action( 'init', array($this, 'duplicate_layouts_settings_meta'), 10 );

		add_filter( 'ddl-get_layout_settings', array( __CLASS__, 'get_layout_settings' ), 10, 3 );
		add_filter( 'ddl-save_layout_settings', array( __CLASS__, 'save_layout_settings' ), 10, 2 );
		add_filter( 'ddl-containers_elements', array( __CLASS__, 'get_containers_elements' ), 10, 1 );
		add_filter( 'ddl-get_cell_types', array( $this, 'get_cell_types' ), 10, 1 );
		add_filter( 'ddl-get_available_parent_layouts', array($this, 'get_available_parent_layouts'), 10, 1 );
		add_filter( 'ddl-rendered_layout_id', array( $this, 'get_rendered_layout_id'), 10, 1 );

	}

	/**
	 * @return bool
	 * very early in the process check
	 */
	private function save_framework_default(){
		$framework_default = get_option( WPDDL_FRAMEWORK_OPTION_DEFAULT_KEY, null );

		if( $framework_default === null ){
			return update_option( WPDDL_FRAMEWORK_OPTION_DEFAULT_KEY, WPDDL_FRAMEWORK );
		}

		return false;
	}

	function add_iframe_hooks(){
		if ( is_admin() ) {

			global $pagenow;

			if ( isset( $_GET['in-iframe-for-layout'] ) && $_GET['in-iframe-for-layout'] == 1 ) {

				// remove emoji styles from iframe
				remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
				remove_action( 'wp_print_styles', 'print_emoji_styles' );
				remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
				remove_action( 'admin_print_styles', 'print_emoji_styles' );

				if ( defined( 'CRED_FORMS_CUSTOM_POST_NAME' ) && $pagenow == 'post.php' && isset( $_GET['post'] ) ) {
					$post_id = $_GET['post'];
					$post    = get_post( $post_id );
					if ( $post->post_type == CRED_FORMS_CUSTOM_POST_NAME ) {
						add_action( 'admin_enqueue_scripts', array( $this, 'cred_in_iframe_scripts' ) );
					}
				}

				if ( defined( 'CRED_USER_FORMS_CUSTOM_POST_NAME' ) && $pagenow == 'post.php' && isset( $_GET['post'] ) ) {
					$post_id = $_GET['post'];
					$post    = get_post( $post_id );
					if ( $post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME ) {
						add_action( 'admin_enqueue_scripts', array( $this, 'cred_user_in_iframe_scripts' ) );
					}
				}

				if (isset( $_GET['in-iframe-for-layout']) &&
				    $_GET['in-iframe-for-layout'] == 1 &&
				    class_exists('CRED_Association_Form_Main') &&
				    $pagenow == 'admin.php' &&
				    isset( $_GET['page'] ) && $_GET['page'] === 'cred_relationship_form' ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'cred_relationship_in_iframe_scripts' ) );
				}

				if ( isset( $_GET['page'] ) && ( ( 'views-editor' == $_GET['page'] ) || ( 'views-embedded' == $_GET['page'] ) || ( 'view-archives-embedded' == $_GET['page'] ) || ( 'view-archives-editor' == $_GET['page'] ) ) ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'views_in_iframe_scripts' ) );
				}
			}

		}
	}

	private function get_private_layout_user_helper(){
		$current_user = wp_get_current_user();
		return new WPDD_Private_Layout_User_Helper( $current_user );
	}

	function __destruct() {

	}

	function __clone() {
		// TODO: Implement __clone() method.
	}

	function show_wpddl_frontend_styles(){
		if ( isset( $_GET['ddl_style'] ) ) {
			header( 'Content-Type:text/css' );
			$this->wpddl_frontent_styles( $_GET['ddl_style'] );
			die();
		}
	}

	public function duplicate_layouts_settings_meta(){
		$meta_keys_updated = get_option( '_ddl_settings_updated_for_layouts_1.9', false );
		if( false === $meta_keys_updated ){
			global $wpdb;
			$wpdb->query( "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) SELECT post_id, '_dd_layouts_settings', meta_value FROM $wpdb->postmeta WHERE meta_key = 'dd_layouts_settings'" );
			if( '' === $wpdb->last_error ){
				update_option( '_ddl_settings_updated_for_layouts_1.9', true );
			}
		}
	}

	static function set_containers_elements() {
		self::$containers_elements = apply_filters( 'ddl-set_containers_elements', array(
			'ddl-container'   => 'Container',
			'row'             => 'ContainerRow',
			'tabs-cell'       => 'Tabs',
			'tabs-tab'        => 'Tab',
			'accordion-cell'  => 'Accordion',
			'accordion-panel' => 'Panel'
		) );
	}

	static function get_containers_elements( $array = array() ) {
		return self::$containers_elements;
	}

	function init_scripts(){}


	public function remove_wp_mediaelement_and_emojis(){
		wp_deregister_script('wp-mediaelement');
		wp_deregister_style('wp-mediaelement');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new WPDD_Layouts();
		}

		return self::$instance;
	}

	public function init_editor_page() {
		if ( isset( $_GET['page'] ) and $_GET['page'] == 'dd_layouts_edit' ) {
			wp_deregister_script( 'heartbeat' );
			wp_register_script( 'heartbeat', false );
		}
	}

	public static function views_available() {
		return class_exists( 'WP_Views' );
	}

	public static function set_toolset_edit_last( $layout_id, $force = true ) {
		if ( false === $force ) {
			return;
		}

		$now  = time();
		$last = self::get_toolset_edit_last( $layout_id );

		if ( $last >= $now ) {
			return;
		}

		return update_post_meta( $layout_id, TOOLSET_EDIT_LAST, $now, $last );
	}

	public static function reset_toolset_edit_last( $layout_id ) {
		delete_post_meta( $layout_id, TOOLSET_EDIT_LAST, self::get_toolset_edit_last( $layout_id ) );
	}

	public static function get_toolset_edit_last( $layout_id ) {
		return intval( get_post_meta( $layout_id, TOOLSET_EDIT_LAST, true ) );
	}

	public static function get_toolset_edit_last_in_readable_format( $layout_id ) {
		$last_edit = self::get_toolset_edit_last( $layout_id );

		return date( DATE_COOKIE, $last_edit );
	}

	public function is_embedded() {
		return defined( 'WPDDL_EMBEDDED' );
	}

	public function get_layout_id_for_render_callback( $id, $args ) {
		$this->rendered_layout_id = $id;
		return $id;
	}

	public function is_rendered_by_layout() {
		return $this->rendered_layout_id !== null;
	}

	public function init_create_layout_for_pages() {
		global $pagenow;
		$this->create_layout_for_pages_manager = WPDD_PostEditPageManager::getInstance( $this, $pagenow, new WPDD_json2layout() );
		$this->create_layout_for_pages_manager->add_hooks();
	}

	public function init_gutenberg_overlay(){
		global $pagenow;

		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
		$gutenberg_condition = new WPDD_Gutenberg_Editor_Condition_Layouts( $pagenow, $post_id );
		$request_mode = new OTGS\Toolset\Common\Utils\RequestMode();
		$gutenberg_overlay = new WPDD_Layouts_Gutenberg_Overlay( 'layouts', 'div.block-editor-block-list__layout.is-root-container', $gutenberg_condition, $request_mode, $post_id );
		$gutenberg_overlay->add_hooks();
		return $gutenberg_overlay;
	}

	public function init_listing_page() {
		$this->listing_page = WPDD_LayoutsListing::getInstance();
	}

	// Localization
	function plugin_localization() {
		$locale = ( function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale() );
		load_textdomain( 'ddl-layouts', WPDDL_ABSPATH . '/locale/layouts-' . $locale . '.mo' );
	}


	function set_css_framework( $framework ) {
		$this->css_framework = $framework;
	}

	function get_css_framework() {
		return $this->css_framework;
	}

	public function enqueue_scripts( $handles ) {
		$this->scripts_manager->enqueue_scripts( $handles );
	}

	public function enqueue_styles( $handles ) {
		$this->scripts_manager->enqueue_styles( $handles );
	}

	public function deregister_styles( $handles ) {
		$this->scripts_manager->deregister_styles( $handles );
	}

	public function deregister_scripts( $handles ) {
		$this->scripts_manager->deregister_styles( $handles );
	}

	public function localize_script( $handle, $object, $args ) {
		$this->scripts_manager->localize_script( $handle, $object, $args );
	}

	function preload_styles() {

		$this->enqueue_styles( array(
			'toolset-select2-css',
			'ddl-dialogs-forms-css',
			'ddl-dialogs-general-css',
			'ddl-dialogs-css',
			'wp-layouts-pages',
			'font-awesome',
			'toolset-colorbox',
			'toolset-common',
			'toolset-notifications-css',
			'views-admin-dialogs-css'
		) );
	}

	function help_page_scripts() {

		$this->enqueue_styles( array(
			'toolset-select2-css',
			'ddl-dialogs-forms-css',
			'ddl-dialogs-general-css',
			'ddl-dialogs-css',
			'wp-layouts-pages',
			'font-awesome',
			'toolset-colorbox',
			'toolset-common',
			'wp-mediaelement'
		) );

		$this->enqueue_scripts( array(
			'toolset_select2',
			'wp-layouts-colorbox-script',
			'wp-layouts-dialogs-script',
			'wp-mediaelement',
			'ddl_common_scripts',
			'wp-layouts-video-js',
			'wp-layouts-help-js'
		) );

		$this->localize_script( 'wp-layouts-help-js', 'DDLayout_settings', array(
			'DDL_JS' => array(
				'res_path'         => WPDDL_RES_RELPATH,
				'lib_path'         => WPDDL_RES_RELPATH . '/js/external_libraries/',
				'editor_lib_path'  => WPDDL_GUI_RELPATH . "editor/js/",
				'dialogs_lib_path' => WPDDL_GUI_RELPATH . "dialogs/js/",
				'DEBUG'            => WPDDL_DEBUG,
			)
		) );
	}

	function views_in_iframe_scripts() {
		wp_deregister_script( 'heartbeat' );

		$this->enqueue_scripts( array(
			'toolset_select2',
			'suggest',
			'ddl-layouts-views-support'
		) );

		$this->enqueue_styles( array(
			'ddl-dialogs-forms-css',
			'toolset-chosen-styles',
			'wp-layouts-pages'
		) );
	}

	function cred_in_iframe_scripts() {
		wp_deregister_script( 'heartbeat' );

		$this->enqueue_scripts( array(
			'toolset_select2',
			'ddl-layouts-cred-support'
		) );

		$data = array(
			'DDL_JS' => array(
				'cred_help_header' => __( 'Building your form', 'ddl-layouts' ),
				'new_form'         => isset( $_GET['new_layouts_form'] ) && $_GET['new_layouts_form']
			)
		);
		if ( $data['DDL_JS']['new_form'] ) {
			$data['DDL_JS']['new_form_help'] = __( 'Build the form using HTML and CRED shortcodes. Use the Add Post Fields button to add fields that belong to this post type, or Add Generic Fields to add any other inputs.', 'ddl-layouts' );
		}

		$this->localize_script( 'ddl-layouts-cred-support', 'DDLayout_cred_settings', $data );

		$this->enqueue_styles( array(
			'toolset-select2-css',
			'layouts-select2-overrides-css',
			'ddl-dialogs-forms-css',
			'toolset-chosen-styles'
		) );
	}

	function cred_user_in_iframe_scripts() {
		wp_deregister_script( 'heartbeat' );

		$this->enqueue_scripts( array(
			'toolset_select2',
			'ddl-layouts-cred-user-support'
		) );

		$data = array(
			'DDL_JS' => array(
				'cred_help_header' => __( 'Building your form', 'ddl-layouts' ),
				'new_form'         => isset( $_GET['new_layouts_form'] ) && $_GET['new_layouts_form']
			)
		);
		if ( $data['DDL_JS']['new_form'] ) {
			$data['DDL_JS']['new_form_help'] = __( 'Build the form using HTML and CRED shortcodes. Use the Add Post Fields button to add fields that belong to this post type, or Add Generic Fields to add any other inputs.', 'ddl-layouts' );
		}

		$this->localize_script( 'ddl-layouts-cred-user-support', 'DDLayout_cred_settings', $data );

		$this->enqueue_styles( array(
			'toolset-select2-css',
			'layouts-select2-overrides-css',
			'ddl-dialogs-forms-css',
			'toolset-chosen-styles'
		) );
	}

	function cred_relationship_in_iframe_scripts() {
		wp_deregister_script( 'heartbeat' );

		$this->enqueue_scripts( array(
			'toolset_select2',
			'ddl-layouts-cred-relationship-support'
		) );

		$data = array(
			'DDL_JS' => array(
				'cred_help_header' => __( 'Building your form', 'ddl-layouts' ),
				'new_form'         => isset( $_GET['new_layouts_form'] ) && $_GET['new_layouts_form']
			)
		);
		if ( $data['DDL_JS']['new_form'] ) {
			$data['DDL_JS']['new_form_help'] = __( 'Build the form using HTML and CRED shortcodes. Use the Add Post Fields button to add fields that belong to this post type, or Add Generic Fields to add any other inputs.', 'ddl-layouts' );
		}

		$this->localize_script( 'ddl-layouts-cred-relationship-support', 'DDLayout_cred_settings', $data );

		$this->enqueue_styles( array(
			'toolset-select2-css',
			'layouts-select2-overrides-css',
			'ddl-dialogs-forms-css',
			'toolset-chosen-styles'
		) );
	}

	function preload_scripts() {

		$this->enqueue_scripts( array(
			'toolset_select2',
			'wp-layouts-colorbox-script',
			'toolset-utils',
			'ddl_common_scripts',
			'wp-layouts-dialogs-script'
		) );
	}

	function layout_get_templates_options_object() {
		// Determine which templates support layouts.
		$ret = new stdClass();

		$template_option = $this->get_option( 'templates' );
		if ( $template_option ) {
			foreach ( $template_option as $file => $layout ) {
				$layout_templates[] = $file;
			}
		}

		$templates = wp_get_theme()->get_page_templates();
		// is integration plugin installed
		$current_theme = wp_get_theme();

		$all_templates = array();
		if ( defined( 'TOOLSET_INTEGRATION_PLUGIN_THEME_NAME' ) && TOOLSET_INTEGRATION_PLUGIN_THEME_NAME === $current_theme['Name'] ) {
			foreach ( $templates as $file_name => $template_name ) {
				$all_templates[] = $file_name;
			}
		}

		$ret->layout_templates = self::templates_have_layout( $templates );
		$ret->template_option  = $template_option;

		$ret->layout_templates = array_merge( $all_templates, $ret->layout_templates );

		return $ret;
	}

	public static function templates_have_layout( $templates ) {
		return WPDD_Utils::templates_have_layout( $templates );
	}

	public static function is_child_theme() {
		return get_stylesheet_directory() !== get_template_directory();
	}


	function template_have_layout( $file, $dir = '' ) {
		return WPDD_Utils::template_have_layout( $file, $dir );
	}

	function plugin_page_styles() {
		$this->enqueue_styles( array( 'toolset-common' ) );
	}

	/*
	 * this registers and enqueue those scripts to be used everywhere
	 */
	function register_and_enqueue_global_scripts() {
		if ( is_admin() ) {
			$this->enqueue_scripts( array(
				                        'headjs',
				                        'ddl_common_scripts'
			                        ) );
		}
		$this->enqueue_scripts( 'jquery' );
	}

	function wpddl_init() {

		// Check for editor page.
		$this->layouts_editor_page = false;
		if ( isset( $_GET['page'] ) and $_GET['page'] == 'dd_layouts_edit' ) {
			if ( isset( $_GET['layout_id'] ) and $_GET['layout_id'] > 0 ) {
				$this->layouts_editor_page = true;
			}
		}

		$this->wpddl_register_post_type_for_layouts();

		do_action( 'ddl-init_layouts_plugin' );

		$this->register_and_enqueue_global_scripts();
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function enqueue_cell_scripts() {
		$this->cells_factory->enqueue_cell_scripts();
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function enqueue_cell_styles() {
		$this->cells_factory->enqueue_cell_styles();
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	public function get_factory( $cell_type ) {
		return $this->cells_factory->get_factory( $cell_type );
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function register_dd_layout_cell_type( $cell_type, $data ) {
		return $this->cells_factory->register_dd_layout_cell_type( $cell_type, $data );
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function register_dd_layout_theme_section( $theme_section, $args ) {
		return $this->cells_factory->register_dd_layout_theme_section( $theme_section, $args );
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function has_theme_sections() {
		return $this->cells_factory->has_theme_sections();
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function get_current_cell_info() {
		return $this->cells_factory->get_current_cell_info();
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function create_cell( $cell_type, $name, $width, $css_class_name = '', $content = null, $cssId = '', $tag = 'div', $unique_id = '' ) {
		$this->cells_factory->create_cell( $cell_type, $name, $width, $css_class_name, $content, $cssId, $tag, $unique_id );
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function get_cell_templates() {
		return $this->cells_factory->get_cell_templates();
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function get_cell_types( $cell_types = null ) {
		return $this->cells_factory->get_cell_types( $cell_types );
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function get_cell_info( $cell_type, $print_dialog = false ) {
		return $this->cells_factory->get_cell_info( $cell_type, $print_dialog );
	}

	/**
	 * retrocompatibility
	 * @deprecated
	 */
	function get_cell_categories() {
		return $this->cells_factory->get_cell_categories();
	}


	function wpddl_frontent_styles( $post_id ) {
		$styles = get_post_meta( $post_id, 'dd_layouts_styles', true );
		echo $styles;
	}


	function dd_layouts_list() {
		$this->listing_page->init();
	}

	function dd_layouts_edit() {
		new WPDD_EDITOR();
	}

	//function dd_layouts_settings(){
	//	include WPDDL_GUI_ABSPATH . 'templates/layout_settings.tpl.php';
	//}

	function theme_has_page_templates() {
		return apply_filters( 'ddl-theme_has_page_templates', count( wp_get_theme()->get_page_templates() ) > 0 );
	}

	public static function get_theme_name() {
		$current_theme      = wp_get_theme();
		$current_theme_name = $current_theme->get( 'Name' );

		return $current_theme_name;
	}

	function wpddl_register_post_type_for_layouts() {
		$labels = array(
			'name'               => _x( 'Layouts', 'post type general name' ),
			'singular_name'      => _x( 'Layout', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'book' ),
			'add_new_item'       => __( 'Add new layout', 'ddl-layouts' ),
			'edit_item'          => __( 'Edit layout', 'ddl-layouts' ),
			'new_item'           => __( 'New layout', 'ddl-layouts' ),
			'view_item'          => __( 'View layouts', 'ddl-layouts' ),
			'search_items'       => __( 'Search layouts', 'ddl-layouts' ),
			'not_found'          => __( 'No layouts found', 'ddl-layouts' ),
			'not_found_in_trash' => __( 'No layouts found in Trash', 'ddl-layouts' ),
			'parent_item_colon'  => '',
			'menu_name'          => 'Layouts'
		);
		$args   = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => false,
			'can_export'         => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 90,
			'supports'           => array( 'title' )
		);
		register_post_type( WPDDL_LAYOUTS_POST_TYPE, $args );
	}

	function does_layout_with_this_name_exist( $layout_name ) {

		if ( $layout_name === 'New Layout' ) {
			return false;
		}

		$post_id = WPDD_Layouts_Cache_Singleton::get_id_by_name( $layout_name );

		return $post_id > 0;

	}


	function create_layout_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wp_nonce_create_layout' ) ) {
			$result = array(
				'error'         => 'error',
				'error_message' => __( 'Security check failed', 'ddl-layouts' )
			);
		} else {

			if ( isset( $_POST['single_data'] ) ) {
				$extras        = $_POST['single_data'];
				$extras_action = $extras['who'];
				if ( $extras_action === 'one' ) {
					$assign_post = $extras['post_id'];
				} elseif ( $extras_action === 'all' && isset( $extras['post_type'] ) ) {
					$types_to_batch = $extras['post_type'];
					$extras_who     = isset( $extras['for_whom'] ) ? $extras['for_whom'] : false;
				}
			}

			if ( isset( $extras_action ) && $extras_action === 'all' && isset( $types_to_batch ) && isset( $extras['post_type_label'] ) ) {
				$layout_name_raw = 'Layout for ' . $extras['post_type_label'];
			} else {
				$layout_name_raw = ( isset( $_POST['single_data']['post_title'] ) ) ? 'Layout for ' . $_POST['single_data']['post_title'] : 'New Layout';
			}

			if( apply_filters( 'ddl-is_integrated_theme', false ) ){
				$layout_preset = WPDDL_EMPTY_PRESET;
			} else {
				$layout_preset = WPDDL_PRIVATE_EMPTY_PRESET;
			}

			$layout_type = 'fluid';
			// Check for duplicate layout name.

			$layout_name = str_replace( '\\\\', '##DDL-SLASH##', $layout_name_raw );
			$layout_name = stripslashes_deep( $layout_name );
			$layout_name = str_replace( '##DDL-SLASH##', '\\\\', $layout_name );
			if ( apply_filters( 'ddl-layouts-unique-name', false ) && apply_filters( 'ddl-does_layout_with_this_name_exist', $layout_name ) ) {
				$result = array(
					'error'         => 'error',
					'error_message' => __( 'A layout with this name already exists. Please use a different name.', 'ddl-layouts' )
				);
			} else {


				$parent_post_name = '';

				if ( isset( $_POST['parent_layout_id'] ) && $_POST['parent_layout_id'] ) {
					$parent_ID = $_POST['parent_layout_id'];
				} else {
					$parent_ID = apply_filters( 'ddl-get-default-' . WPDDL_Options::PARENTS_OPTIONS, null, WPDDL_Options::PARENTS_OPTIONS );
				}

				if ( $parent_ID ) {
					$parent_post_name = WPDD_Layouts_Cache_Singleton::get_name_by_id( $parent_ID );
				}

				if ( isset( $_POST['width'] ) && $_POST['width'] ) {
					$layout['width'] = $_POST['width'];
				}

				$layout                 = self::load_layout( $layout_preset, $layout_type );
				$layout['type']         = $layout_type;
				$layout['cssframework'] = $this->get_css_framework();
				$layout['template']     = '';
				$layout['parent']       = $parent_post_name;
				$layout['name']         = $layout_name;

				$postarr = array(
					'post_title'   => $layout_name,
					'post_content' => '',
					'post_status'  => 'publish',
					'post_type'    => WPDDL_LAYOUTS_POST_TYPE
				);

				$post_id = wp_insert_post( $postarr );
				// force layout object to take right ID
				$layout_post    = get_post( $post_id );
				$layout['slug'] = $layout_post->post_name;
				$layout['id']   = $post_id;

				if ( isset( $extras_action ) && $extras_action === 'all' && isset( $types_to_batch ) ) {
					$post_types = array( $types_to_batch );
					if ( ! $extras_who || $extras_who === 'new' ) {
						$this->post_types_manager->handle_post_type_data_save( $post_id, $post_types );
					} else if ( $extras_who && $extras_who === 'all' ) {
						$this->post_types_manager->handle_set_option_and_bulk_at_once( $post_id, $post_types, null, true );
					} else {
						$this->post_types_manager->handle_post_type_data_save( $post_id, $post_types );
					}

				} else if ( isset( $extras_action ) && $extras_action === 'one' && isset( $assign_post ) ) {
					$this->post_types_manager->update_post_meta_for_post_type( array( $assign_post ), $post_id, null );
				}


				self::save_layout_settings( $post_id, $layout );

				$result['slug'] = $layout['slug'];
				$result['name'] = $layout['name'];
				$result['id'] = $post_id;
			}
		}

		die( wp_json_encode( $result ) );
	}

	function create_private_layout_processing($layout_data){

		if ( isset( $layout_data['width'] ) && $layout_data['width'] ) {
			$layout['width'] = $layout_data['width'];
		}

		$layout_type     = 'fluid';
		$layout_name_raw = $layout_data['title'];

		$layout_name = str_replace( '\\\\', '##DDL-SLASH##', $layout_name_raw );
		$layout_name = stripslashes_deep( $layout_name );
		$layout_name = str_replace( '##DDL-SLASH##', '\\\\', $layout_name );

		$layout                 = self::load_layout( WPDDL_PRIVATE_EMPTY_PRESET, 'fluid' );
		$layout['type']         = $layout_type;
		$layout['cssframework'] = $this->get_css_framework();
		$layout['template']     = '';
		$layout['parent']       = null;
		$layout['name']         = $layout_name;
		$layout['id']           = $_POST['private_layout_arguments']['content_id'];
		$layout['slug']         = get_post_field( 'post_name', $_POST['private_layout_arguments']['content_id'] );
		$layout['layout_type']  = 'private';
		$layout['owner_kind']   = $_POST['private_layout_arguments']['post_type'];
		$layout['field_kind']   = null;


		// get current post content, in case if content exists create new visual editor cell and place content there
		$post = get_post( $layout_data['private_layout_arguments']['content_id'] );

		if ( property_exists( $post, 'post_content' ) && $post->post_content != '' ) {
			$original_content = $post->post_content;
			$content = $this->remove_gutenberg_blocks_comments( $original_content );
			$layout['Rows'][0] = $this->append_visual_cell_with_content_to_private_layout( $layout_data['private_layout_arguments']['post_type'], $layout_data['private_layout_arguments']['content_id'], $content  );
			// keep original content inside custom field
			add_post_meta( $post->ID, WPDDL_PRIVATE_LAYOUTS_ORIGINAL_CONTENT_META_KEY, $original_content );
		}

		$save_status = self::save_layout_settings( $layout['id'], $layout );
		$layout['save_status'] = $save_status;

		return $layout;

	}

	private function remove_gutenberg_blocks_comments( $content ) {
		return preg_replace('/<!--(.|\s)*?-->/', '', $content );
	}

	function create_private_layout_callback() {

		global $post;
		$nonce = $_POST["wpnonce"];

		// check nonce
		if ( ! wp_verify_nonce( $nonce, 'wp_nonce_create_layout' ) ) {
			$result = array(
				'error'         => 'error',
				'error_message' => __( 'Security check failed', 'ddl-layouts' )
			);
			die( wp_json_encode( $result ) );
		}

		// check is post updated before creating content layout
		if( in_array( $_POST['unsaved_changes'], array( "true", 'draft', 'auto-draft' ) )  ){
			$result = array(
				'error'  => 'error',
				'status' => 'not_saved'
			);
			die( wp_json_encode( $result ) );
		}


		$layout = $this->create_private_layout_processing($_POST);
		die( wp_json_encode( array( 'Data' => $layout ) ) );


	}

	public function append_visual_cell_with_content_to_private_layout( $owner_kind, $owner_id, $content = null, $field_kind = null ) {

		return array(
			'kind'                   => "Row",
			'Cells'                  => array(
				WPDD_Utils::create_cell( 'Post Content Cell', 1, 'cell-text', array(
					'content' => array( 'content' => __( $content ) ),
					'width'   => 12
				) )
			),
			'cssClass'               => 'row-fluid',
			'name'                   => 'Post content row',
			'additionalCssClasses'   => '',
			'row_divider'            => 1,
			'layout_type'            => 'fluid',
			'mode'                   => 'full-width',
			'cssId'                  => '',
			'tag'                    => 'div',
			'width'                  => 1,
			'editorVisualTemplateID' => ''
		);
	}

	public static function create_layout( $width, $type ) {
		$layout = new WPDD_layout( $width );
		$row    = new WPDD_layout_row( '1', '', '', $type );
		for ( $i = 0; $i < $width; $i ++ ) {
			$cell = new WPDD_layout_spacer( null, '', 1 );
			$row->add_cell( $cell );
		}
		$layout->add_row( $row );

		$layout          = $layout->get_as_array();
		$layout['width'] = $width;

		return $layout;
	}

	function ddl_dismiss_template_message() {
		$nonce = $_POST["wpnonce"];

		if ( wp_verify_nonce( $nonce, 'wp_nonce_ddl_dismiss' ) ) {
			$this->save_option( array( 'dismiss_layout_message' => true ) );
		}

		die();
	}

	public static function load_layout( $preset_file, $layout_type = null ) {

		$layout_json = file_get_contents( $preset_file );

		$layout = json_decode( str_replace( '\\\"', '\"', $layout_json ), true );

		if ( $layout_type ) {
			$layout['type'] = $layout_type;
			for ( $i = 0; $i < sizeof( $layout['Rows'] ); $i ++ ) {
				$layout['Rows'][ $i ]['layout_type'] = $layout_type;
				$layout['Rows'][ $i ]['cssClass']    = 'row-' . $layout_type;

			}
		}

		return $layout;

	}

	function load_frontend_js() {
		if ( is_ddlayout_assigned() === false ) {
			return;
		}

		$this->enqueue_scripts( 'ddl-layouts-frontend' );
		$this->localize_script( 'ddl-layouts-frontend', 'DDLayout_fe_settings', array(
			                                              'DDL_JS' => array(
				                                              'css_framework' => $this->get_css_framework(),
				                                              'DEBUG'         => WPDDL_DEBUG,
			                                              )
		                                              ) );
	}

	function load_frontend_css() {
		global $post;
		if ( is_ddlayout_assigned() === false && apply_filters( 'ddl-is_private_layout_in_use', is_object( $post ) ? $post->ID : 0 ) !== 'yes' ) {
			return;
		}

		$this->enqueue_styles( 'menu-cells-front-end' );
		$this->enqueue_styles( 'ddl-front-end' );
	}


	public static function get_layout_settings( $post_id, $as_array = false, $clear_cache = false ) {

		if ( ! static::$raw_cache ) {
			static::$raw_cache     = new WPDDL_Cache( 'layouts_raw' );
			static::$decoded_cache = new WPDDL_Cache( 'layouts_decoded' );
		}
		$clear_cache	= apply_filters( 'ddl_force_clear_cache_settings', $clear_cache, $post_id, $as_array );
		$layout_settings = new WPDDL_Layout_Settings( $post_id, static::$raw_cache, static::$decoded_cache );
		$return = $layout_settings->get( $as_array, $clear_cache );
		return $return;
	}

	// I added this 'cause in ajax calls after saving the static property of self::get_layout_settings
	// is not updated so the settings you get are outdated
	/**
	 * @param $layout_id
	 * @param bool $as_array
	 *
	 * @return array|mixed|object
	 * @deprecated
	 */
	public static function get_layout_settings_raw_not_cached( $layout_id, $as_array = true) {

		$settings = get_post_meta( $layout_id, WPDDL_LAYOUTS_SETTINGS, $as_array );

		if ( $as_array ) {
			return json_decode( $settings );
		} else {
			return $settings;
		}
	}

	public static function get_layout_json_settings_encoded_64( $post_id, $cached = true ) {

		$clear_cache = false;
		$json = $cached ? self::get_layout_settings( $post_id, false, $clear_cache ) : self::get_layout_settings_raw_not_cached( $post_id, false );

		return base64_encode( $json );
	}

	public static function save_layout_settings( $post_id, $settings ) {

		if ( ! static::$raw_cache ) {
			static::$raw_cache     = new WPDDL_Cache( 'layouts_raw' );
			static::$decoded_cache = new WPDDL_Cache( 'layouts_decoded' );
		}

		$layout_settings = new WPDDL_Layout_Settings( $post_id, static::$raw_cache, static::$decoded_cache );
		$result = $layout_settings->update( $settings );

		return $result ? (int) $post_id : 0;
	}

	public static function get_layout_parent( $id, $layout_search = false ) {
		$layout = $layout_search ? $layout_search : self::get_layout_settings( $id, true );
		$parent = $layout->parent;

		if ( ! empty( $parent ) ) {
			$parent = WPDD_Layouts_Cache_Singleton::get_id_by_name( $parent );
		}
		if ( ! $parent ) {
			$parent = 0;
		}

		return $parent;
	}

	function get_layout( $layout_name ) {
		$layout = null;

		if ( empty( $layout_name ) ) {
			return $layout_name;
		}

		$id = WPDD_Layouts_Cache_Singleton::get_id_by_name( $layout_name );

		if ( $id ) {
			$layout_json = self::get_layout_settings( $id, false, false );
			$json_parser = new WPDD_json2layout();
			$layout      = $json_parser->json_decode( $layout_json );
			if ( ! $layout ) {
				return null;
			}
			$layout->set_post_id( $id );
			$layout->set_post_slug( $layout_name );
		}

		return $layout;
	}

	public static function get_layout_from_id( $id, $is_private = false, $clear_cache = false ) {
		$layout = null;

		if ( true === $is_private ) {
			return self::get_layout_as_php_object( $id );
		} else {
			$layout_name = WPDD_Layouts_Cache_Singleton::get_name_by_id( $id );

			if ( $layout_name ) {
				$layout_json = self::get_layout_settings( $id, false, $clear_cache );
				$json_parser = new WPDD_json2layout();
				$layout      = $json_parser->json_decode( $layout_json );
				if ( ! $layout ) {
					return null;
				}
				$layout->set_post_id( $id );
				$layout->set_post_slug( $layout_name );
			}
		}


		return $layout;
	}

	public static function get_layout_as_php_object( $id ) {
		$layout_json = self::get_layout_settings( $id );
		$json_parser = new WPDD_json2layout();
		$layout      = $json_parser->json_decode( $layout_json );
		$layout->set_post_id( $id );

		return $layout;
	}


	function get_layout_slug_for_post_object( $post_id ) {
		$meta = get_post_meta( $post_id, WPDDL_LAYOUTS_META_KEY, true );

		if ( ! $meta ) {
			return null;
		}

		return $meta;
	}

	function get_available_parent_layouts( $current = null ) {
		static $layouts = null;

		if ( $layouts === null ) {
			global $wpdb;

			$layouts  = array();
			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status='publish' AND ID != %s", WPDDL_LAYOUTS_POST_TYPE, $current ) );
			foreach ( $post_ids as $post_id ) {
				$layout = self::get_layout_settings_raw_not_cached( $post_id, true );
				if ( $layout && isset( $layout->has_child ) && ( $layout->has_child === 'true' || $layout->has_child === true ) ) {
					$parent = $this->get_layout_parent( $post_id );
					if ( ! isset( $layouts[ $parent ] ) ) {
						$layouts[ $parent ] = array();
					}
					$layouts[ $parent ][] = $post_id;
				}
			}
		}

		return $layouts;
	}

	function get_layout_list() {
		global $wpdb;

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s", WPDDL_LAYOUTS_POST_TYPE ) );

		return $results;
	}

	function save_option( $option ) {
		$options = get_option( 'ddlayout_settings' );
		if ( ! $options ) {
			$options = array();
		}
		$options = array_merge( $options, $option );
		update_option( 'ddlayout_settings', $options );
	}

	function get_option( $option, $default = false ) {
		$options = get_option( 'ddlayout_settings' );
		if ( $options && isset( $options[ $option ] ) ) {
			return $options[ $option ];
		} else {
			return $default;
		}
	}


	function record_render_error( $data ) {
		WPDD_Layouts_RenderManager::getInstance()->record_render_error( $data );
	}


	function layout_type_selector( $name ) {
		global $wpddl_features;

		?>


		<?php

	}

	public static function get_layout_id_by_slug( $slug ) {
		return WPDD_Layouts_Cache_Singleton::get_id_by_name( $slug );
	}

	function get_layout_css() {
		return $this->css_manager->get_layouts_css();
	}

	function get_layout_js() {
		return $this->js_manager->get_layouts_js();
	}

	function get_where_used( $layout_id, $slug = false, $group = false, $posts_per_page = - 1, $post_status = array( 'publish' ), $output = 'default', $post_types = 'any', $suppress_filters = true, $offset = 0 ) {
		// Get layout php object if $slug parameter is not provided
		if ( $slug === false ) {
			$layout = self::get_layout_from_id( $layout_id );
			if ( ! $layout ) {
				return null;
			}
			if ( ! method_exists( $layout, 'get_post_slug' ) ) {
				return null;
			}
			$slug = $layout->get_post_slug();
		}

		// We now include attachments
		/* */
		// don't include attachments
		$a                      = get_post_type_object( 'attachment' );
		$a->exclude_from_search = true;
		/* */

		$post_type = ! $post_types ? 'any' : $post_types;

		$args = array(
			'posts_per_page'         => $posts_per_page,
			// set offset - only in case if posts_per_page is not -1
			'offset'                 => $offset,
			'post_type'              => $post_type,
			// get only published posts
			'post_status'            => $post_status,
			//don't perform found posts query
			'no_found_rows'          => false,
			// leave the terms alone we don't need them
			'update_post_term_cache' => false,
			// leave the meta alone we don't need them
			'update_post_meta_cache' => false,
			// don't cache results
			'cache_results'          => false,
			'suppress_filters'       => $suppress_filters,
			'meta_query'             => array(
				array(
					'key'     => WPDDL_LAYOUTS_META_KEY,
					'value'   => $slug,
					'compare' => '=',
				)
			)
		);

		// set the output type, by default all posts, only ids in case we want to save memory
		$args['fields'] = $output;

		$new_query = new WP_Query( $args );

		if ( $group === true ) {
			add_filter( 'posts_orderby', array( &$this, 'order_by_post_type' ), 10, 2 );
			$new_query->group_posts_by_type = $group;
		}

		$posts = $new_query->posts;
		$this->set_where_used_count( $new_query->found_posts );
		// remove any reference to the query
		$new_query = null;

		// This is not needed anymore
		/* */
		// reset attachments to original status
		$a->exclude_from_search = false;
		/* */

		// flush cache to release memory
		wp_cache_flush();

		return $posts;
	}

	function set_where_used_count( $count ) {
		$this->where_used_count = intval( $count );
	}

	function get_where_used_count() {
		return $this->where_used_count;
	}

	function order_by_post_type( $orderby, $query ) {
		global $wpdb;
		if ( property_exists( $query, 'group_posts_by_type' ) && $query->group_posts_by_type === true ) {
			unset( $query->group_posts_by_type );
			$orderby = $wpdb->posts . '.post_type ASC';
		}

		// provide a default fallback return if the above condition is not true
		return $orderby;
	}

	public static function get_layout_children( $id ) {
		global $wpdb;

		if ( ! $id ) {
			return null;
		}

		$layout = self::get_layout_settings( $_GET['layout_id'], true );

		$children = array();

		if ( $layout && isset( $layout->has_child ) && ( $layout->has_child === 'true' || $layout->has_child === true ) ) {
			$layout_slug = $layout->slug;

			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s", WPDDL_LAYOUTS_POST_TYPE ) );
			foreach ( $post_ids as $post_id ) {
				$layout = self::get_layout_settings( $post_id, true );
				if ( $layout ) {
					if ( property_exists( $layout, 'parent' ) && $layout->parent == $layout_slug ) {
						$children[] = $post_id;
					}
				}
			}
		}

		return $children;
	}

	// maybe these are not needed
	public function get_layout_content_for_render( $layout, $args ) {
		return WPDD_Layouts_RenderManager::getInstance()->get_layout_content_for_render( $layout, $args );
	}

	public function get_queried_object() {
		return WPDD_Layouts_RenderManager::getInstance()->get_queried_object();
	}

	public function get_query_post_if_any( $queried_object ) {
		return WPDD_Layouts_RenderManager::getInstance()->get_query_post_if_any( $queried_object );
	}

	public static function flattenArray( $array ) {
		$ret_array = array();

		if ( is_array( $array ) ) {
			foreach ( new RecursiveIteratorIterator( new RecursiveArrayIterator( $array ) ) as $value ) {
				$ret_array[] = $value;
			}
		} else {
			$ret_array = array( 'error' => sprintf(
				/** translators: This is an error message, not seen by any user */
				__( 'Argument should be an array %s', 'ddl-layouts' ),
				__METHOD__ ) );
		}

		return $ret_array;
	}

	private function fix_up_views_slugs() {
		global $wpdb;

		$fixed = $this->get_option( 'views_and_template_slugs_fixed_0.9.2' );

		if ( ! $fixed ) {

			// From 0.9.2 we're using the View ID instead of the slug
			// We need to check all layouts and update them as required.
			$layout_tempates_available = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_name, post_title FROM {$wpdb->posts} WHERE post_type=%s", WPDDL_LAYOUTS_POST_TYPE ) );
			foreach ( $layout_tempates_available as $template ) {

				$layout = self::get_layout_settings( $template->ID );
				$found  = false;

				if ( preg_match_all( '/"ddl_layout_view_slug":"(.*?)"/', $layout, $matches ) ) {
					$found = true;
					for ( $i = 0; $i < sizeof( $matches[0] ); $i ++ ) {
						$slug = $matches[1][ $i ];
						$id   = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = '%s' AND post_type='view'", $slug ) );
						if ( $id > 0 ) {
							$new    = '"ddl_layout_view_id":"' . $id . '"';
							$layout = str_replace( $matches[0][ $i ], $new, $layout );
						}
					}

				}

				if ( preg_match_all( '/"view_template":"(.*?)"/', $layout, $matches ) ) {
					$found = true;
					for ( $i = 0; $i < sizeof( $matches[0] ); $i ++ ) {
						$slug = $matches[1][ $i ];
						$id   = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = '%s' AND post_type='view-template'", $slug ) );
						if ( $id > 0 ) {
							$new    = '"ddl_view_template_id":"' . $id . '"';
							$layout = str_replace( $matches[0][ $i ], $new, $layout );
						}
					}

				}

				if ( $found ) {
					self::save_layout_settings( $template->ID, $layout );
				}

			}

			$this->save_option( array( 'views_and_template_slugs_fixed_0.9.2' => true ) );

		}

	}

	public static function get_post_ID_by_slug( $slug, $post_type = 'post' ) {
		global $wpdb;

		if ( ! $slug ) {
			return null;
		}

		if ( WPDDL_LAYOUTS_POST_TYPE == $post_type ) {
			return WPDD_Layouts_Cache_Singleton::get_id_by_name( $slug );
		}

		return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_name=%s", $post_type, $slug ) );
	}


	public static function get_post_property_from_ID( $id, $property = 'post_name' ) {
		if ( is_nan( $id ) || ! $id ) {
			return null;
		}

		$post = get_post( $id );

		if ( is_object( $post ) === false ) {
			return null;
		}

		if ( get_class( $post ) !== 'WP_Post' ) {
			return null;
		}

		return $post->{$property};
	}

	/**
	 * add types configuration to debug
	 */

	function add_extra_debug_information( $extra_debug ) {
		$extra_debug['layouts'] = $this->frameworks_options_manager->get_options();

		return $extra_debug;
	}

	public static function register_strings_for_translation( $layout_id, $is_private = false, $clear_cache = false ) {
		$layout = self::get_layout_from_id( $layout_id, $is_private, $clear_cache );
		if ( ! $layout ) {
			return;
		}
		if ( ! method_exists( $layout, 'register_strings_for_translation' ) ) {
			return;
		}
		$layout->register_strings_for_translation( null, true );
	}

	function register_all_strings_for_translation() {
		global $wpdb;

		$layouts = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'", WPDDL_LAYOUTS_POST_TYPE ) );
		foreach ( $layouts as $layout_id ) {
			WPDD_Layouts::register_strings_for_translation( $layout_id );
		}
	}

	/**
	 * @deprecated since version 1.2
	 */
	function print_layout_edit_link( $wp_admin_bar ) {
		if ( $this->rendered_layout_id === null ) {
			return;
		}

		$layout = self::get_layout_from_id( $this->rendered_layout_id );

		if ( ! $layout ) {
			return;
		}

		if ( current_user_can( 'activate_plugins' ) === false ) {
			return;
		}

		$href = sprintf( '%sadmin.php?page=dd_layouts_edit&layout_id=%s&action=edit', admin_url(), $layout->get_post_id() );

		$args = array(
			'id'    => 'edit_layout',
			'title' => __( 'Edit layout', 'ddl-layouts' ),
			'href'  => $href,
			'meta'  => array( 'class' => 'layouts-edit-link' ),
		);
		$wp_admin_bar->add_menu( $args );
	}

	public static function get_available_parents() {

		$defaults = array(
			'post_type'        => WPDDL_LAYOUTS_POST_TYPE,
			'suppress_filters' => true,
			'order'            => 'DESC',
			'orderby'          => 'post_title',
			'post_status'      => 'publish',
			'posts_per_page'   => - 1
		);
		$layouts  = get_posts( $defaults );
		$parents  = array();
		foreach ( $layouts as $layout_post ):
			$layout_attr = self::get_layout_settings( $layout_post->ID, true );
			if ( is_object( $layout_attr ) && property_exists( $layout_attr, 'has_child' ) && $layout_attr->has_child ) {
				$layout_post->layout_attr = $layout_attr;
				$parents[]                = $layout_post;
			}
		endforeach;

		return $parents;
	}

	public function set_registered_cells( $cell ) {
		$bool = true;

		foreach ( $this->registered_cells as $existing ) {
			if ( $existing->get_cell_type() === $cell->get_cell_type() ) {
				$bool = false;
				break;
			}
		}

		if ( $bool ) {
			$this->registered_cells[] = $cell;
		}
	}

	public function get_registered_cells() {
		return $this->registered_cells;
	}

	public function get_rendered_layout_id( $id = null ) {
		return $this->rendered_layout_id;
	}

	function set_up_cell_fields_by_id( $cell_id, $layout_id, $args = array() ) {

		$layout = WPDD_Layouts_RenderManager::getInstance()->get_rendered_layout( $layout_id );

		if ( $layout instanceof WPDD_layout == false ) {
			return;
		}

		$cell = $layout->get_cell_by_id( $cell_id );

		if ( $cell instanceof WPDD_layout_element === false ) {
			return;
		}

		$target = WPDD_Layouts_RenderManager::getInstance()->get_layout_renderer( $layout, $args );

		if ( $target instanceof WPDD_layout_render === false ) {
			return;
		}

		$content = $cell->get_translated_content( $target->get_context() );

		global $ddl_fields_api;
		$ddl_fields_api->set_current_cell_content( $content );
	}

	function is_layout( $post_id ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID = %d", $post_id ) ) == WPDDL_LAYOUTS_POST_TYPE;
	}

	function before_delete_post_action( $post_id ) {
		if ( $this->is_layout( $post_id ) ) {
			$package_name = $post_id;
			$package_kind = 'layout';
			do_action( 'wpml_delete_package', $package_name, $package_kind );
		}
	}

	/**
	 * Assign the attachment post type layout to new attachment post
	 *
	 * @param string $attachment_id
	 */
	function add_attachment_action( $attachment_id ) {

		// Extract attachment post type layout
		$layout_object = $this->post_types_manager->get_layout_to_type_object( 'attachment' );
		if ( null === $layout_object ) {
			return;
		}
		$layout_id = $layout_object->layout_id;

		// WPDD_Layouts_PostTypesManager::set_layout_for_post_type_meta_callback() alike
		$posts = array( $attachment_id );
		$this->post_types_manager->update_post_meta_for_post_type( $posts, $layout_id );
		$this->post_types_manager->track_batched_post_types( 'attachment', $layout_id );
	}

	public function import_layouts_from_theme( $source_dir, $overwrite_assignment = false ) {
		return WPDD_Layouts_Theme::getInstance()->import_layouts_from_theme( $source_dir, $overwrite_assignment );
	}

	public function enqueue_toolset_common_styles() {
		$this->enqueue_styles( array( 'toolset-common' ) );
	}

	public function get_layout_id( $layout_slug ){
		return self::get_layout_id_by_slug( $layout_slug );
	}

}
