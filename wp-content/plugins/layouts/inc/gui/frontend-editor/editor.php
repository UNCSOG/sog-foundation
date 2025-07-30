<?php

/**
 * Class WPDD_GUI_FE_EDITOR
 */
class WPDD_GUI_FRONTEND_EDITOR extends WPDD_Layouts_Editor {
	public $version = '0.9.9';
	protected $dialogs;
	protected $editable_cells;
	protected $user_can_edit_templates = false;
	protected $user_can_edit_content = false;
	protected $editor_can_load = false;
	protected $has_private_layout = false;
	protected $current_language  = false;
	protected $default_language  = false;

	public function __construct( &$main ) {
		$this->user_can_edit_templates = user_can_edit_layouts();
		$this->user_can_edit_content   = current_user_can( 'edit_others_pages' );
		$this->editor_can_load         = $this->user_can_edit_content || $this->user_can_edit_templates;
		$this->current_language        = apply_filters( 'wpml_current_language', null );
		$this->default_language        = apply_filters( 'wpml_default_language', null );

		parent::__construct( $main );

		$this->add_hooks();
		$this->initialise();

	}

	private function add_hooks(){
		add_filter( 'ddl-get_editor_js_strings', array( $this, 'push_editor_strings' ) );
		add_action( 'template_redirect', array( $this, 'prevent_editor_for_non_default_languages' ) );
		add_action( 'wp_ajax_save_layout_data_front_end', array( &$this, 'save_layout_data_callback' ) );
		add_action( 'wp_ajax_render_element_changed', array( &$this, 'render_layout_element_callback' ) );
		add_action( 'ddl_layout_data_saved', array( &$this, 'layout_saved_callback' ), 99, 3 );
		add_filter( 'remove_toolset_admin_bar', array( $this, 'disable_toolset_admin_bar' ) );
    }

	private function initialise(){
		if ( ( isset( $_POST['toolset_editor'] ) || isset( $_GET['toolset_editor'] ) ) && $this->editor_can_load ) {
			if ( isset( $_GET['toolset_editor'] ) ) {
				$this->init_sync();
			} elseif ( isset( $_POST['toolset_editor'] ) ) {
				$this->init_async();
			}


		} else if ( is_admin() && isset( $_POST['render_private'] ) && ( $_POST['render_private'] === 'ddl_update_post_content_for_private_layout' || $_POST['action'] === 'dll_import_layouts' ) ) {

			$this->init_async();

		} else if ( ! is_admin() && ! isset( $_GET['toolset_editor'] ) && ! isset( $_POST['toolset_editor'] ) && $this->editor_can_load ) {
			$this->init_editor();
		}
    }


	/**
	 * Disable toolset admin bar in case when Front end editor button is there
	 * @return bool
	 */
	public function disable_toolset_admin_bar() {

		if ( apply_filters( 'is_private_ddlayout_assigned', false ) === false && apply_filters( 'is_ddlayout_assigned', false ) === false ) {
			return false;
		}

		if ( $this->current_language !== $this->default_language ) {
			return false;
		}

		if (
			apply_filters( 'toolset_is_views_available', false )
			&& 'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' )
			&& (
				is_wpv_content_template_assigned()
				|| is_wpv_wp_archive_assigned()
			)
		) {
			return false;
		}

		return true;
	}

	public function prevent_editor_for_non_default_languages() {
		if ( $this->current_language !== $this->default_language && isset( $_GET['toolset_editor'] ) ) {
			$location = remove_query_arg( 'toolset_editor', wp_unslash( $_SERVER['REQUEST_URI'] ) );
			wp_redirect( $location );
			exit;
		}
	}

	public function layout_saved_callback( $send, $post_data, $class_object ) {

		if ( isset( $post_data['post_id'] ) && $post_data['post_id'] ) {
			global $post;
			$post = get_post( $post_data['post_id'] );
			if ( is_object( $post ) && $post_data['action'] === 'render_element_changed' && isset( $post_data['post_content'] ) ) {
				$post->post_content = stripslashes( $post_data['post_content'] );
			}
		}

		return $send;
	}

	private function init_sync() {
		add_action( 'wp_ajax_ddl_update_wpml_state', array( &$this, 'update_wpml_state' ) );
		// Load Layouts data
		add_action( 'get_header', array( $this, 'load_layouts' ) );
		$this->init_cells_attributes_overrides();
		$this->init_scripts_and_dialogs();
		$dialogs = new WPDDL_FrontEndEditorDialogs();
		$dialogs->init_screen_render();
	}

	private function init_cells_attributes_overrides() {
		// Add additional data-id attribute for each row
		add_filter( 'ddl-get_row_additional_attributes', array( $this, 'add_row_attributes' ), 999, 3 );

		// Add additional CSS class for each Row
		add_filter( 'ddl-get_row_additional_css_classes', array( $this, 'add_row_classes' ), 999, 2 );

		add_filter( 'ddl-accordion-get_panel_data_attributes', array( $this, 'add_row_attributes' ), 999, 3 );
		add_filter( 'ddl-accordion-get_panel_class', array( $this, 'add_row_classes' ), 999, 2 );

		add_filter( 'ddl-tab_get_tabpanel_data_attributes', array( $this, 'add_row_attributes' ), 999, 3 );
		add_filter( 'ddl-tab_get_tabpanel_class', array( $this, 'add_row_classes' ), 999, 2 );

		// Add additional data-id attribute for each cell
		add_filter( 'ddl-additional_cells_tag_attributes_render', array( $this, 'add_cell_attributes' ), 999, 3 );

		// Add additional CSS class for each Cell
		add_filter( 'ddl-get_cell_element_classes', array( $this, 'add_cell_classes' ), 999, 3 );
	}

	private function init_scripts_and_dialogs() {
		$this->dialogs = new WPDD_GUI_DIALOGS();
		// needs to run after Views
		$this->dialogs->render_in_front_end();

		// Add plugin-specific classes to HTML body tag
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );

		// Remove WP admin bar
		add_filter( 'show_admin_bar', '__return_false' );

		// Include Underscore templates
		add_action( 'wp_footer', array( $this, 'load_templates' ) );

		// Print Layouts data to hidden textarea
		add_action( 'wp_footer', array( $this, 'print_layouts' ) );

		// Enqueue scripts & styles
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles' ) );

		// Load ajaxurl to window global variable for Layout.js
		add_action( 'wp_footer', array( $this, 'load_ajax_url' ) );

		$this->init_layouts_css();
	}

	private function init_layouts_css() {
		include WPDDL_GUI_ABSPATH . 'CSS/wpddl.css-js-editor.class.php';
		WPDDL_CSSEditor::getInstance();
	}

	private function init_async() {
		$this->init_cells_attributes_overrides();
	}

	public function init_editor() {
		add_action( 'admin_bar_menu', array( &$this, 'init_editor_button' ), 99, 1 );
	}

	public function init_editor_button( $wp_admin_bar ) {
		if (
			'blocks' === apply_filters( 'toolset_views_flavour_installed', 'classic' )
			&& (
				is_wpv_content_template_assigned()
				|| is_wpv_wp_archive_assigned()
			)
		) {
			// Using Toolset Blocks in an archive with a CT assignment, skip Layouts.
			return;
		}

		if ( apply_filters( 'is_private_ddlayout_assigned', false ) === false && apply_filters( 'is_ddlayout_assigned', false ) === false ) {
			return;
		}

		if ( $this->current_language !== $this->default_language ) {
			return;
		}

        if( $_POST && isset( $_POST['layout_preview'] ) && $_POST['layout_preview'] ){
            return;
        }

		$page_id = get_the_ID();

        if( isset( $_REQUEST['private_layout_preview'] ) &&
            $_REQUEST['private_layout_preview'] &&
            ( WPDD_Utils::is_private( $page_id ) === true || apply_filters( 'tlm_private_layout_preview', false, $page_id ) )
        ){
            return;
        }

		$url   = $this->get_editor_link();
		$title = __( 'Front-end Layouts Editor' );

		$args = array(
			'id'    => 'ddl-front-end-editor',
			'title' => $title,
			'href'  => $url,
			'meta'  => array(
				'class' => 'ddl-front-end-editor'
			)
		);

		$wp_admin_bar->add_node( $args );


	}

	private function get_editor_link() {
		$current_url = add_query_arg( array( 'toolset_editor' => '' ), $_SERVER['REQUEST_URI'] );

		return $current_url;
	}

	function push_editor_strings( $strings ) {

		$new_strings = array(
			"cell_not_editable_in_front_end_title" => __( 'Edit the %CELL% cell', 'ddl-layouts' ),
			"cell_edit_back_end_button"            => __( 'Edit %CELL%', 'ddl-layouts' ),
			"offset_prefix"                        => WPDDL_Framework::getInstance()->get_column_prefix() . WPDDL_Framework::getInstance()->get_offset_prefix(),
			"this_cell"                            => __( 'This cell', 'ddl-layouts' ),
			'parent_layout_string'                 => __( 'Parent Layout', 'ddl-layouts' ),
			'content_layout_string'                => __( 'Content Layout', 'ddl-layouts' ),
			'template_layout_string'               => __( 'Template Layout', 'ddl-layouts' )
		);

		return wp_parse_args( $new_strings, $strings );
	}

	public function add_row_attributes( $attrs, $row, $renderer ) {

		if ( is_array( $row->context ) && isset( $row->context['slug'] ) ) {
			$layout_slug = $row->context['slug'];
		} else if ( $renderer && $renderer->is_private_layout === true ) {
			$layout_slug = $renderer->get_layout();
			$layout_slug = $layout_slug->get_post_slug();
		} else if ( $renderer && $renderer->is_private_layout === false ) {
			$context     = $renderer ? $renderer->get_context() : null;
			$layout_slug = $context['slug'];
		} else {
			$layout_slug = '';
		}
		$kind = $row->getKind();

		return $attrs . " data-id='{$row->get_id()}' data-name='{$row->get_name()}' data-type='row' data-layout_slug='{$layout_slug}' data-kind='{$kind}' ";

	}

	/**
	 * Additional HTML tags for rows & cells
	 *
	 */
	public function add_row_classes( $classes, $mode ) {
		return $classes . ' ddl-frontend-editor-row ddl-frontend-editor-editable';
	}

	public function add_cell_attributes( $out, $renderer, $cell ) {

		$layout_slug = $cell->get_post_slug();
		$id   = $cell->get_unique_id() ? $cell->get_unique_id() : $cell->get_id();
		$type = $cell->get_cell_type() ? $cell->get_cell_type() : strtolower( $cell->getKind() );
		$kind = $cell->getKind();

		return $out . " data-id='{$id}' data-name='{$cell->get_name()}' data-type='{$type}' data-layout_slug='{$layout_slug}' data-kind='{$kind}'";

	}

	public function add_cell_classes( $classes, $renderer, $cell ) {
		$js_selector = '';

		if ( ( $cell->get_cell_type() === 'cell-post-content' && $this->has_private_layout ) || $cell->get_cell_type() === 'child-layout' ) {
			$js_selector = '';
		} else {
			$js_selector = ' js-ddl-frontend-editor-cell';
		}

		return $classes . ' ddl-frontend-editor-cell ddl-frontend-editor-editable' . $js_selector;
	}

	/**
	 * Include Underscore templates for Backbone views
	 */
	public function load_templates() {
		include_once WPDDL_GUI_ABSPATH . 'frontend-editor/templates/toolbar.php';
		include_once WPDDL_GUI_ABSPATH . 'frontend-editor/templates/context_menu.php';
		include_once WPDDL_GUI_ABSPATH . 'frontend-editor/templates/container.php';
		include_once WPDDL_GUI_ABSPATH . 'frontend-editor/templates/cell.php';
	}

	/**
	 * Output hidden textarea with Layout settings in wp_footer
	 */
	public function print_layouts() {
		if ( count( $this->layouts ) > 0 ) {
			foreach ( $this->layouts as $index => $layout ) {
				echo $this->print_json_data( $index, $layout->json );
				unset( $this->layouts[ $index ]->json );
			}
		}
	}

	/* End of additional HTML tags for rows & cells */

	public function print_json_data( $index, $layout_json ) {

		ob_start();

		echo "<textarea id='ddl-layout-settings-encoded-{$index}'
                        name='layouts-hidden-content'
                        class='js-hidden-json-textarea hidden'>{$layout_json}</textarea>";

		return ob_get_clean();
	}

	/**
	 * @return void
	 * Loads Layouts data
	 **/
	public function load_layouts() {

		if ( $this->user_can_edit_templates  ) {
			$layout_id = $this->get_current_layout_id();
			$this->get_layouts( $layout_id );
		}

		if ( $this->user_can_edit_content ) {
			$id                      = get_the_id();
			$private_layout_in_use   = WPDD_Utils::is_private_layout_in_use( $id );
			$page_has_private_layout = WPDD_Utils::page_has_private_layout( $id );
			if ( $private_layout_in_use && $page_has_private_layout ) {
				$this->get_layouts( null, true );
			}
		}
	}

	/**
	 * @return mixed|void
	 */
	private function get_current_layout_id() {
		$id = WPDD_Layouts_RenderManager::getInstance()->get_layout_id_for_render( null, array() );

		return $id;
	}

	/**
	 * @param $id
	 *
	 * @return mixed|null
	 * @deprecated
	 */
	private function get_current_layout_slug( $id ) {
		$layout_slug = WPDD_Utils::get_post_property_from_ID( $id ); // it returns layout slug, actually

		return $layout_slug;
	}

	/**
	 * @param null $layout_id
	 * @param bool $is_private
	 *
	 * @return array
	 */
	protected function get_layouts( $layout_id = null /*php compatible declaration*/, $is_private = false ) {
		$layout = $this->get_layout( $layout_id, $is_private );

		if ( $layout ) {
			$this->layouts[] = $layout;

			if ( $is_private === true ) {

				$this->has_private_layout = true;

			}
		}

		return $this->layouts;
	}

	/**
	 * @param null $layout_id
	 * @param bool $is_private
	 *
	 * @return null|object
	 */
	protected function get_layout( $layout_id = null /*php compatible declaration*/, $is_private = false ) {

		$ret = null;

		if ( $is_private === false ) {

			$layout_post = get_post( $layout_id ); // but we need to get the ID

			if ( $layout_post ) {

				$layout_slug = $layout_post->post_name;

				$layout = WPDD_Layouts::get_layout_settings( $layout_id, true, false );

				if ( is_object( $layout ) && property_exists( $layout, 'parent' ) && $layout->parent ) {

					$parent_id = WPDD_Utils::get_layout_id_from_post_name( $layout->parent );
					$ret       = $this->get_layout( $parent_id );

					if ( $ret ) {
						$this->layouts[] = (object) $ret;
					}
				}
			}
		} else {
			$layout_id   = get_the_id();
			$layout_slug = get_post_field( 'post_name', $layout_id );
		}

		if ( ! isset( $layout_slug ) ) {
			return null;
		}

		$layout_json = WPDD_Layouts::get_layout_json_settings_encoded_64( $layout_id, true );

		if ( $layout_json ) {
			$ret = array(
				'id'       => $layout_id,
				'slug'     => $layout_slug,
				'json'     => $layout_json,
				'edit_url' => $this->build_admin_editor_url( $layout_id )
			);
			if ( $is_private ) {
				$ret['post_title'] = get_the_title( $layout_id );
			}
		}


		return $ret ? (object) $ret : null;
	}

	protected function build_admin_editor_url( $layout_id ) {
		$path = sprintf( 'admin.php?page=dd_layouts_edit&layout_id=%s&action=edit', $layout_id );

		return admin_url( $path );
	}

	/**
	 * Enqueue JS scripts
	 */
	public function load_scripts() {
		wp_enqueue_media();

		do_action( 'toolset_enqueue_scripts', array( 'ddl-frontend-editor-main' ) );

		$cell_types = apply_filters( 'ddl-get_cell_types', null );

		do_action( 'toolset_localize_script', 'ddl-frontend-editor-main', 'DDLayout_settings', array(
			'DDL_JS'  => array(
				'strings'                  => $this->get_editor_js_strings(),
				'available_cell_types'     => $cell_types,
				'editable_cell_types'      => $this->editable_cells_types( $cell_types ),
				'lib_path'                 => WPDDL_RES_RELPATH . '/js/external_libraries/',
				'dialogs_lib_path'         => WPDDL_GUI_RELPATH . "dialogs/js/",
				'editor_lib_path'          => WPDDL_GUI_RELPATH . "editor/js/",
				'common_rel_path'          => WPDDL_TOOLSET_COMMON_RELPATH,
				'frontend_editor_lib_path' => WPDDL_GUI_RELPATH . "frontend-editor/js/",
				'save_layout_nonce'        => wp_create_nonce( 'save_layout_nonce' ),
				'DEBUG'                    => WPDDL_DEBUG,
				'has_theme_sections'       => $this->main->has_theme_sections(),
				'AMOUNT_OF_POSTS_TO_SHOW'  => self::AMOUNT_OF_POSTS_TO_SHOW,
				'is_css_enabled'           => $this->main->css_manager->is_css_possible(),
				'current_framework'        => $this->main->frameworks_options_manager->get_current_framework(),
				'user_can_delete'          => user_can_delete_layouts(),
				'user_can_assign'          => user_can_assign_layouts(),
				'user_can_edit'            => user_can_edit_layouts(),
				'user_can_create'          => user_can_create_layouts(),
				'layouts_css_properties'   => WPDDL_CSSEditor::get_all_css_names(),
				'media_settings'           => WPDD_Utils::get_image_sizes( 'thumbnail' ),
				'site_url'                 => get_site_url(),
				'preview_width'            => self::PREVIEW_WIDTH,
				'preview_height'           => self::PREVIEW_HEIGHT,
				'default_img_url'          => DDL_ICONS_SVG_REL_PATH . 'image-box.svg',
				'get_shortcode_regex'      => get_shortcode_regex(),
				'max_num_posts'            => DDL_MAX_NUM_POSTS,
				'POPUP_MESSAGE_OPTION'     => self::POPUP_MESSAGE_OPTION,
				'layout_trash_nonce'       => wp_create_nonce( 'layout-select-trash-nonce' ),
				'container_elements'       => apply_filters( 'ddl-containers_elements', array() ),
				'wpml_is_active'           => defined( 'WPML_TM_VERSION' ),
				'layouts'                  => $this->layouts,
				'SPECIAL_CELLS_OPTIONS'    => self::special_cells_options(),
				'user_dismissed_dialogs'   => $this->user_dismissed_dialogs(),
				'cells_data'               => $this->get_cells_data(),
				'current_post'             => get_the_ID(),
				'has_private_layout'       => $this->has_private_layout,
				'post_title'               => $this->has_private_layout ? get_the_title() : '',
				'is_integrated_theme'      => apply_filters( 'ddl-is_integrated_theme', false ),
				'column_prefixes_data'     => $this->get_framework_prefixes_data(),
				'column_prefix_default'    => $this->settings->get_column_prefix(),
				'woocommerce_archive_title'    => $this->get_woocommerce_archive_title(),
			    'WPDDL_VERSION' => WPDDL_VERSION
			),
			'DDL_OPN' => WPDD_LayoutsListing::change_layout_dialog_options_name()
		), 10, 3 );

		do_action( 'toolset_localize_script', 'ddl-frontend-editor-main', 'window', array(
			'ajaxurl' => admin_url( 'admin-ajax.php', null )
		), 10, 3 );

		$this->main->enqueue_cell_scripts();
	}

	/**
	 * In front end, when rendering element change make sure to keep correct WooCommerce archive title
	 * since after ajax request it always returns "Archive", because it doesn't know on what page exactly we are
	 * @return string
	 */
	public function get_woocommerce_archive_title() {

		$woocommerce_archive_title = null;
		if (
			apply_filters( 'ddl-is_woocommerce_enabled', false ) === true &&
			apply_filters( 'ddl-is_woocommerce', false ) === true
		) {
			$woocommerce_archive_title = do_shortcode( '[wpv-archive-title]' );
		}

		return $woocommerce_archive_title;
	}

	protected function editable_cells_types( $all_types ) {

		$exclude_types = apply_filters( 'ddl-not_editable_cells_types', array(
			"ddl_missing_cell_type",
			"child-layout"
			/* "ddl-container",
			 "post-loop-views-cell",
			 "cred-cell",
			 "cred-user-cell",
			 "views-content-grid-cell",
			 "cell-content-template",
			 "accordion-cell",
			 "tabs-cell"*/
		) );

		return array_diff( $all_types, $exclude_types );
	}

	private static function special_cells_options() {
		return Layouts_toolset_based_cell::toolset_cells_options();
	}

	protected function user_dismissed_dialogs() {
		global $current_user;
		$user_id = $current_user->ID;

		return get_user_meta( $user_id, 'ddl_dismiss_dialog_message', true );
	}

	/**
	 * Load ajaxurl to window global variable
	 */
	function load_ajax_url() {
		echo '<script type="text/javascript">window.ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '"</script>';
	}

	/**
	 * Enqueue CSS styles
	 */
	public function load_styles() {

		do_action( 'toolset_enqueue_styles', array(
			'edit',
			'media',
			'ddl-forms-overrides',
			'buttons',
			'open-sans',
			'dashicons',
			'glyphicons',
			'toolset-dashicons',
			'editor-buttons',

			'editor_addon_menu',
			'editor_addon_menu_scroll',
			'progress-bar-css',
			'layouts-settings-admin-css',

			'font-awesome',
			'progress-bar-css',
			'toolset-notifications-css',
			'jq-snippet-css',
			'wp-jquery-ui-dialog',
			'wp-editor-layouts-css',
			'toolset-colorbox',
			'toolset-common',
			'wp-jquery-ui-dialog',
			'ddl-dialogs-general-css',
			'ddl-dialogs-css',
			'ddl-dialogs-forms-css',
			//TODO: I am not sure about this one is for UI
			'toolset-dialogs-overrides-css',
			//TODO: I am not sure about this one is for UI
			'wp-layouts-pages',
			'wp-pointer',
			'toolset-select2-css',
			'layouts-select2-overrides-css',
			'wp-mediaelement',
			'ddl-frontend-editor-toolbar',
			'ddl-frontend-editor-layout',
			'ddl-frontend-editor-common',
			'ddl-frontend-editor-wp-core-overrides',
			'ddl-dialogs-overrides',
			'toolset-chosen-styles'
		) );

		$this->main->enqueue_cell_styles();
	}

	/**
	 * Add CSS classes to HTML body tag
	 *
	 * @param $classes
	 *
	 * @return mixed
	 */
	public function add_body_classes( $classes ) {
		if ( ! isset( $classes['ddl-frontend-editor'] ) ) {
			array_push( $classes, 'ddl-frontend-editor' );
		}

		return $classes;
	}

	public function save_layout_data_callback() {
		if ( $this->editor_can_load === false ) {
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		if ( $_POST && isset( $_POST['save_layout_nonce'] ) && wp_verify_nonce( $_POST['save_layout_nonce'], 'save_layout_nonce' ) ) {

			$send                     = $this->save_layout_data( $_POST );
			$send                     = apply_filters( 'ddl_layout_data_saved', $send, $_POST, $this );
			$this->has_private_layout = isset( $_POST['has_private'] ) ? $_POST['has_private'] === 'true' ? true : false : false;

			$send['show_messages'] = 'yes';

			if ( isset( $send['message'] ) && isset( $send['message']['layout_changed'] ) && $send['message']['layout_changed'] ) {

				$layout_type = isset( $_POST['layout_type'] ) ? $_POST['layout_type'] : 'normal';
				$this->handle_private_layout_post_content_data_save( isset( $_POST['post_id'] ) ? $_POST['post_id'] : null, $layout_type );

			}

		} else {
			$send = array( 'error' => __( sprintf( 'Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts' ) );
		}

		$send['action']        = $_POST['action'];
		$send['show_messages'] = true;

		wp_send_json( ( array( 'Data' => $send ) ) );
	}

	public function render_layout_element_callback() {

		if ( $this->editor_can_load === false ) {
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		if ( $_POST && isset( $_POST['save_layout_nonce'] ) && wp_verify_nonce( $_POST['save_layout_nonce'], 'save_layout_nonce' ) ) {

			$send = apply_filters( 'ddl_layout_data_saved', array(), $_POST, $this );

			if ( ! isset( $send['message'] ) ) {
				$send['message'] = array();
			}

			$this->has_private_layout = isset( $_POST['has_private'] ) ? $_POST['has_private'] === 'true' ? true : false : false;

			$cell_html = $this->render_current_cell( isset( $_POST['element_model'] ) ? $_POST['element_model'] : null, $_POST['layout_model'] );

			if ( $cell_html ) {
				$send['current_element_html'] = do_shortcode($cell_html);
				$send['message']['layout_changed'] = true;
			} else {
				$send['message']['layout_changed'] = false;
			}

		} else {
			$send = array( 'error' => __( sprintf( 'Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts' ) );
		}

		$send['action']        = $_POST['action'];
		$send['show_messages'] = false;

		wp_send_json( ( array( 'Data' => $send ) ) );
	}

	private function render_current_cell( $cell, $layout ) {
		if ( null === $cell ) {
			return null;
		}

		$render = new WPDDL_FrontEndEditor_Renderer( $layout, $cell );

		return $render->get_element_rendered();
	}

	protected function handle_private_layout_post_content_data_save( $post_id, $layout_type ) {

		if ( ! $post_id || $layout_type !== 'private' ) {
			return;
		} elseif ( $post_id && $layout_type === 'private' ) {
			$layout_id      = $post_id;
			$private_layout = new WPDDL_Private_Layout();

			return $private_layout->update_post_content_for_private_layout( $layout_id );
		}

		return false;
	}
}

class WPDDL_FrontEndEditor_Renderer {

	private static $ROW_KINDS = array(
		'ThemeSectionRow',
		'Row',
		'Tab',
		'Panel'
	);
	private static $CONTAINER_KINDS = array(
		'Container',
		'Tabs',
		'Accordion'
	);
	private $layout = null;
	private $element = null;
	private $renderer = null;
	private $content = null;
	private $layout_type = 'normal';

	public function __construct( $layout = null, $element = null ) {
		add_action( 'ddl_before_frontend_render_element', array( $this, 'reset_context' ), 10, 2 );
		//add_filter( 'ddl-views_loop_cells_is_archive_get', array( $this, 'force_woocommerce_render_archives'), 10, 1 );

		$layout_array = $this->json_decode( $layout );

		$this->layout_type = isset( $layout_array['layout_type'] ) ? $layout_array['layout_type'] : $this->layout_type;

		$element = $this->json_decode( $element );

		$this->layout = $this->to_layout_php( $layout_array );

		$this->renderer = $this->renderer_setup( isset( $element['offset'] ) && $element['offset'] ? (int) $element['offset'] : 0 );

		$this->element = $this->get_element_php( $element );

		$this->set_sub_mode( $element );

		$this->prevent_WPML_translation();

		$this->content = $this->render_element();

	}

	protected function json_decode( $data ) {
		$raw  = stripslashes( $data );
		$json = json_decode( $raw, true );

		return $json;
	}

	private function to_layout_php( $layout_array ) {

		if ( $layout_array === null ) {
			return null;
		}

		$json_parser = new WPDD_json2layout();
		$layout      = $json_parser->json_decode( $layout_array, true );

		if ( $layout === null ) {
			return null;
		}

		$layout->set_post_id( $layout_array['id'] );
		$layout->set_post_slug( $layout_array['slug'] );

		return $layout;
	}

	private function renderer_setup( $offset = 0 ) {
		if ( $this->layout === null ) {
			return null;
		}
		$renderer = $this->get_renderer( $this->layout );

		if ( ! $renderer ) {
			return null;
		}

		$this->layout->set_context( $renderer );
		$renderer->spacer_start_callback( $offset );
		$renderer->run_content_filters = true;
		$renderer->is_private_layout   = $this->layout_type === 'private';

		return $renderer;
	}

	private function get_renderer( $layout_php ) {
		return WPDD_Layouts_RenderManager::getInstance()->get_layout_renderer( $layout_php, array() );
	}

	private function get_element_php( $element ) {

		if ( $this->layout === null ) {
			return null;
		}

		if ( in_array( $element['kind'], self::get_row_types() ) ) {

			return $this->layout->get_row_by_id( $element['id'] );

		} else {

			return $this->layout->get_cell_by_id( $element['id'] );

		}

		return null;
	}

	public static function get_row_types() {
		return apply_filters( 'ddl-get-row-kinds', self::$ROW_KINDS );
	}

	/**
	 * @param $element
	 * if it's a container let the render know and avoid printing container divs
	 */
	private function set_sub_mode( $element ) {

		if ( in_array( $element['kind'], self::get_container_types() ) ) {
			$this->renderer->push_row_mode( 'sub-row' );
		}
	}

	public static function get_container_types() {
		return apply_filters( 'ddl-get-container-kinds', self::$CONTAINER_KINDS );
	}

	private function prevent_WPML_translation() {
		remove_all_filters( 'wpml_translate_string' );
	}

	public function render_element() {
		if ( $this->element && $this->renderer ) {
			return $this->element->frontend_render( $this->renderer );
		}

		return null;
	}

	public function __destruct() {
		// TODO: Implement __destruct() method.
	}

	public function reset_context( $element, $target ) {
		if ( $element === $this->element ) {
			$target->set_context( null );
		}
	}

	public function get_element_rendered() {
		return WPDD_Utils::clean_html_output_from_extra_spaces( $this->content );
	}

	/**
	 * @param bool $bool
	 *
	 * @return bool
	 */
	public function force_woocommerce_render_archives( $bool = false ){
		if( WPDD_Utils::is_woocommerce_enabled() ){
			$shop_page_id = get_option( 'woocommerce_shop_page_id' );
			$layout_selected = get_post_meta( $shop_page_id, WPDDL_LAYOUTS_META_KEY, true );
		}

		if( $layout_selected && $layout_selected === $this->layout->get_post_slug() ){
			$bool = true;
		} else {
			$bool = false;
		}

		return $bool;
	}

}

class WPDDL_FrontEndEditorDialogs extends Toolset_DialogBoxes {
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_ddl_dialog_dismiss', array( &$this, 'dialog_dismiss' ) );
	}

	public function dialog_dismiss() {

		if ( user_can_edit_private_layouts() === 'false' ) {
			die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
		}

		if ( $_POST && isset( $_POST['ddl_dialog_dismiss_nonce'] ) && wp_verify_nonce( $_POST['ddl_dialog_dismiss_nonce'], 'ddl_dialog_dismiss_nonce' ) ) {
			global $current_user;

			$user_id = $current_user->ID;

			$user_updated = add_user_meta( $user_id, $_POST['dismiss_dialog_option'], $_POST['dismiss_dialog'], true );

			$send = wp_json_encode( array( "Data" => array( 'message' => __( sprintf( 'User %s preference updated: %s', $user_id, $user_updated ), 'ddl-layouts' ) ) ) );

		} else {
			$send = wp_json_encode( array( "Data" => array( 'error' => __( sprintf( 'Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts' ) ) ) );
		}

		echo wp_json_encode( array( 'Data' => $send ) );

		die();
	}

	public function template() {
		ob_start(); ?>
        <!-- PREVIEW -->
        <script type="text/html" id="ddl-info-dialog-tpl">
            <div id="js-dialog-dialog-container">
                <div class="ddl-dialog-content" id="js-dialog-content-dialog">
                    <div class="ddl-dialog-content-icon ddl-dialog-message-content">
                        <span class="fa-stack fa-lg">
                            <i class="fa fa-square-o fa-stack-2x"></i>
                            <i class="fa fa-pencil fa-stack-1x"></i>
                        </span>
                    </div>
                    <div
                            class="ddl-dialog-content-text ddl-dialog-message-content"><?php printf( __( 'You can edit %s %s only in the admin. Once you are done editing, save and close the window to return here.', 'ddl-layouts' ), '{{{cell_type}}}', '{{{name}}}' ); ?>
                        <p><label for="disable-popup-message"><input type="checkbox"
                                                                     name="<?php echo WPDD_GUI_EDITOR::POPUP_MESSAGE_OPTION; ?>"
                                                                     value="true"
                                                                     id="disable-popup-message"> <?php _e( 'Don\'t show this message again', 'ddl-layouts' ); ?>
                            </label></p></div>
                </div>
                <input type="hidden" name="ddl_dialog_dismiss_nonce" id="ddl_dialog_dismiss_nonce"
                       value="<?php wp_create_nonce( 'ddl_dialog_dismiss_nonce' ); ?>">
            </div>
        </script><?php
		echo ob_get_clean();
	}

	function init_screen_render() {

		if ( isset( $_GET['toolset_editor'] ) === false ) {
			return;
		}

		add_action( 'wp_print_scripts', array( &$this, 'enqueue_scripts' ), 999 );
		add_action( 'wp_footer', array( &$this, 'template' ) );
	}
}
