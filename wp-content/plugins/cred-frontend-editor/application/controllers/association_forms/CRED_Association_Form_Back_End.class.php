<?php

use OTGS\Toolset\CRED\Controller\AssociationForms\Editor\Content\Toolbar;

class CRED_Association_Form_Back_End extends CRED_Association_Form_Abstract{

	const JS_LISTING_HANDLE = 'toolset_cred_association_forms_back_end_listing_main_js';
	const JS_LISTING_REL_PATH = '/public/association_forms/js/listing_page/main.js';
	const JS_EDITOR_HANDLE = 'toolset_cred_association_forms_back_end_editor_main_js';
	const JS_EDITOR_REL_PATH = '/public/association_forms/js/editor_page/main.js';
	const CSS_ADMIN_MAIN_HANDLE = 'toolset_cred_association_forms_back_end_main_css';
	const CSS_ADMIN_REL_PATH = '/public/association_forms/css/backend_main.css';
	const CSS_EDITOR_HANDLE = 'toolset_cred_association_forms_editor_css';
	const JS_EDITOR_I18N_NAME = 'cred_post_form_content_editor_i18n';
	const CSS_EDITOR_REL_PATH = '/public/association_forms/css/editor.css';
	const CSS_WIZARD_HANDLE = 'toolset_cred_association_forms_wizard_css';
	const CSS_WIZARD_REL_PATH = '/public/association_forms/css/wizard.css';
	const CSS_EDITOR_BASE_HANDLE = 'toolset_cred_post_editor_base_css';
	const CCS_EDITOR_BASE_REL_PATH = '/public/form_editor/css/editor.css';

	private $assets_to_load_js = array();
	private $assets_to_load_css = array();

	private $page = null;

	public function __construct( CRED_Association_Form_Model_Factory $model_factory, CRED_Association_Form_Relationship_API_Helper $helper = null ) {
		$this->set_page();
		parent::__construct( $model_factory, $helper );
	}

	private function set_page(){
		$this->page = toolset_getget( 'page', null );
	}

	public function get_page(){
		return $this->page;
	}

	/**
	 * implementation for add_hooks method in abstract
	 */
	public function add_hooks(){
		add_filter( 'toolset_filter_register_menu_pages', array( $this, 'add_pages' ), 50 );
	}

	/**
	 * Initialize back-end
	 */
	public function initialize(){
		parent::initialize();
		if( $this->get_page() === self::LISTING_SLUG ){
			$this->init_scripts_and_styles();
			$this->init_listing();
		} elseif( $this->get_page() === self::EDITOR_SLUG ){
			$this->init_scripts_and_styles();
			$this->init_editor();
			$this->init_editor_toolbar();
		}
	}

	public function init_editor_toolbar() {
		$content_editor_toolbar = new Toolbar();
		$content_editor_toolbar->initialize();
		// Disable the Toolset Views conditional output quicktag from editors.
		add_filter( 'wpv_filter_wpv_disable_conditional_output_quicktag', '__return_true' );
		 // Force include the Quicktag link template.
		 add_action( 'admin_footer', array( $this, 'force_quicktag_link_template' ) );
	}

	/**
     * Force include the Quicktag link template so it works.
     *
     * @since 2.1
     */
    public function force_quicktag_link_template() {
        if ( ! class_exists( '_WP_Editors' ) ) {
			require( ABSPATH . WPINC . '/class-wp-editor.php' );
		}
		\_WP_Editors::wp_link_dialog();
    }

	private function init_scripts_and_styles(){
		$this->load_backend_assets();
		$toolset_gui_base = Toolset_Gui_Base::get_instance();
		$toolset_gui_base->init();
	}

	private function init_listing(){
		$this->model = $this->get_model('Collection' );
		$this->view = $this->get_view('Listing', $this->model, $this->helper, $this->get_repository_instance() );
	}

	private function init_editor(){
		$this->model = $this->get_model('Model' );
		$this->view = $this->get_view('Editor', $this->model, $this->helper );
	}

	private function get_repository_instance(){
		global $wpdb;
		return new CRED_Association_Form_Repository( $wpdb );
	}

	function add_pages( $pages ) {

		$pages[] = array(
			'slug' => self::LISTING_SLUG,
			'menu_title' => __('Relationship Forms', 'wp-cred'),
			'page_title' => __('Relationship Forms', 'wp-cred'),
			'callback' => array( $this->view, 'print_page' ),
			'capability' => CRED_CAPABILITY
		);

		if( $this->get_page() === self::EDITOR_SLUG ){
			$pages[] = array(
				'slug' => self::EDITOR_SLUG,
				'menu_title' => __('Relationship Forms Editor', 'wp-cred'),
				'page_title' => __('Relationship Forms Editor', 'wp-cred'),
				'callback' => array( $this->view, 'print_page' ),
				'capability' => CRED_CAPABILITY
			);
		}

		return $pages;
	}

	/**
	 * Load defined dependencies
	 */
	private function load_backend_assets(){
		$this->register_assets();
		$this->define_assets( $this->assets_to_load_js, $this->assets_to_load_css );
		$this->load_assets();
	}

	/**
	 * Register necessary java scripts and css for backend
	 */
	private function register_assets(){

		$this->assets_manager->register_style(
			self::CSS_ADMIN_MAIN_HANDLE,
			CRED_ABSURL . self::CSS_ADMIN_REL_PATH,
			array(
				'editor-buttons',
				'buttons',
				'cred_cred_style_dev',
				'cred_wizard_general_style',
				Toolset_Gui_Base::STYLE_GUI_BASE,
				OTGS_Assets_Handles::SWITCHER
			),
			CRED_FE_VERSION
		);

		// Load only for listing page
		if( $this->get_page() === self::LISTING_SLUG ){
			$this->assets_manager->register_script(
				self::JS_LISTING_HANDLE,
				CRED_ABSURL . self::JS_LISTING_REL_PATH,
				array(
					'jquery', 'backbone', 'underscore','ddl-abstract-dialog','ddl-dialog-boxes',
					Toolset_Gui_Base::SCRIPT_GUI_LISTING_PAGE_CONTROLLER,
					Toolset_Assets_Manager::SCRIPT_HEADJS,
					Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
					Toolset_Assets_Manager::SCRIPT_UTILS
				),
				CRED_FE_VERSION
			);
			$this->assets_to_load_js['listing_main'] = self::JS_LISTING_HANDLE;
			$this->assets_to_load_css['listing_main'] = self::CSS_ADMIN_MAIN_HANDLE;
		} elseif( $this->get_page() === self::EDITOR_SLUG ){
			$this->assets_manager->register_script(
				self::JS_EDITOR_HANDLE,
				CRED_ABSURL . self::JS_EDITOR_REL_PATH,
				array(
					'jquery',
					'backbone',
					'underscore',
					'quicktags',
					'ddl-abstract-dialog',
					'ddl-dialog-boxes',
					'jquery-ui-droppable',
					Toolset_Assets_Manager::SCRIPT_TOOLSET_EVENT_MANAGER,
					Toolset_Assets_Manager::SCRIPT_TOOLSET_SHORTCODE,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_CSS,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_HTMLMIXED,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_JS,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_OVERLAY,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_HINT,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_HINT_CSS,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_PANEL,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_SEARCH,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_UTILS_SEARCH_CURSOR,
					Toolset_Assets_Manager::SCRIPT_CODEMIRROR_XML,
					Toolset_Assets_Manager::SCRIPT_ICL_EDITOR,
					Toolset_Assets_Manager::SCRIPT_ICL_MEDIA_MANAGER,
					Toolset_Gui_Base::SCRIPT_GUI_LISTING_PAGE_CONTROLLER,
					Toolset_Assets_Manager::SCRIPT_HEADJS,
					Toolset_Assets_Manager::SCRIPT_KNOCKOUT,
					Toolset_Assets_Manager::SCRIPT_UTILS,
					Toolset_Assets_Manager::SCRIPT_SELECT2,
					OTGS_Assets_Handles::POPOVER_TOOLTIP,
					CRED_Asset_Manager::SCRIPT_CODEMIRROR_SHORTCODES_MODE,
					CRED_Asset_Manager::SCRIPT_EDITOR_SCAFFOLD,
				),
				CRED_FE_VERSION
			);

			$this->assets_manager->localize_script(
				self::JS_EDITOR_HANDLE,
				self::JS_EDITOR_I18N_NAME,
				$this->get_scaffold_localization()
			);

			// Wizard css for editor
			$this->assets_manager->register_style(
				self::CSS_WIZARD_HANDLE,
				CRED_ABSURL . self::CSS_WIZARD_REL_PATH,
				array(),
				CRED_FE_VERSION
			);

			$this->assets_manager->register_style(
				self::CSS_EDITOR_HANDLE,
				CRED_ABSURL . self::CSS_EDITOR_REL_PATH,
				array(
					self::CSS_ADMIN_MAIN_HANDLE,
					self::CSS_WIZARD_HANDLE,
					CRED_Asset_Manager::STYLE_EDITOR,
					Toolset_Assets_Manager::STYLE_CODEMIRROR,
					Toolset_Assets_Manager::STYLE_CODEMIRROR_CSS_HINT,
					Toolset_Assets_Manager::STYLE_SELECT2_CSS_OVERRIDES,
					OTGS_Assets_Handles::POPOVER_TOOLTIP,
					Toolset_Assets_Manager::STYLE_TOOLSET_DIALOGS_OVERRIDES,
					'wpcf-css-embedded',
				),
				CRED_FE_VERSION
			);

			$this->assets_manager->register_style(
				self::CSS_EDITOR_BASE_HANDLE,
				CRED_ABSURL . self::CCS_EDITOR_BASE_REL_PATH,
				array(),
				CRED_FE_VERSION
			);

			$this->assets_to_load_js['editor_main'] = self::JS_EDITOR_HANDLE;
			$this->assets_to_load_css['editor_main'] = self::CSS_EDITOR_HANDLE;
			$this->assets_to_load_css['editor_base'] = self::CSS_EDITOR_BASE_HANDLE;
		}
	}


	/**
	 * Scaffold i18n
	 *
	 * @return array
	 * @since 2.2
	 */
	private function get_scaffold_localization() {
		return array(
			// translators: Yes button.
			'yes' => __( 'Yes', 'wp-cred' ),
			// translators: No button.
			'no' => __( 'No', 'wp-cred' ),
			'notice' => $this->get_inline_notice_content(),
		);
	}


	/**
	 * Gets the content of a Toolset notice, adds a inline class a returns /**
	 *
	 * @since 2.2
	 */
	private function get_inline_notice_content() {
		ob_start();
		// translators: There are 2 kind of editors (visual and HTML), if the user switchs from html to editor, changes could be lost.
		$notice = new \Toolset_Admin_Notice_Dismissible( 'scaffold_html_editor', __( 'Changes that you make in the HTML editor will be lost if you switch back to the Visual editor.', 'wp-cred' ) );
		if ( \Toolset_Admin_Notices_Manager::is_notice_dismissed( $notice ) ) {
			return '';
		}
		$notice->set_type( 'warning' );
		$notice->set_inline_mode( true );
		$notice->render();
		$notice_content = ob_get_clean();

		return $notice_content;
	}
}
