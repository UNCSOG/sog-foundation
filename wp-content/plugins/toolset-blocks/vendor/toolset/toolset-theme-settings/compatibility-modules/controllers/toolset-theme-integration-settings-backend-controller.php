<?php

class Toolset_Theme_Integration_Settings_Back_End_Controller extends Toolset_Theme_Integration_Settings_Abstract_Controller{
	/**
	 * @var array
	 * List of pages where we will load js and css files
	 */
	private $script_pages = null;

	public function __construct( Toolset_Theme_Integration_Settings_Helper $helper = null, $pages = null ){
		$this->script_pages = $pages;
		parent::__construct( $helper, $pages );
	}

	public function init(){
		parent::init();
	}

	public function admin_init(){
		parent::admin_init();
		$this->register_assets();
		$this->enqueue_assets();
	}

	public function add_hooks() {
		if ( ! $this->is_blocks_active ) {
			add_action( 'wpddl_after_render_editor', array( $this, 'render_layouts_settings_section' ) );
		}

		//Render the GUI in CT page when layout is deactivated
		if (
			$this->is_blocks_active
			|| (
				! $this->is_layouts_active
				&& $this->is_views_active
			)
		 ) {
			add_action( 'wpv_ct_editor_sections', array( $this, 'render_views_ct_settings_section' ), 40 );
			add_action( 'wpv_action_wpa_editor_section_extra', array( $this, 'render_views_wpa_settings_section' ), 40 );
			add_filter( 'wpv_filter_get_theme_settings', array( $this, 'get_theme_settings' ) );
			$this->maybe_init_metabox();
		}

		parent::add_hooks();
	}

	/**
	 * Gets information for a theme (name and slug) as well as the collection of settings that are intergated into Toolset.
	 *
	 * @param array $collections
	 *
	 * @return array
	 */
	public function get_theme_settings( $collections ) {
		$collections_raw = $this->collections->get_collections();
		$collections = $this->reformat_collections_for_gui( $collections_raw, 'archive' );

		return array(
			'theme_name' => $this->active_theme->Name,
			'theme_slug' => $this->theme_slug,
			'collections' => $collections,
		);
	}

	public function get_user_visibility_preference_for_gui(){
		return $this->helper->get_user_visibility_preference_for_gui();
	}

	function enqueue_assets(){
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
	}

	public function register_assets() {

		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();

		$toolset_assets_manager->register_script(
			'toolset_theme_integration_admin_script',
			TOOLSET_THEME_SETTINGS_URL . '/res/js/toolset-theme-integration.js',
			array(
				'jquery',
				'wp-pointer',
				'underscore',
				'wp-color-picker',
			),
			TOOLSET_THEME_SETTINGS_VERSION
		);

		$toolset_theme_integration_admin_script_i18n = array(
			'strings' => array(
				'close' => __( 'Close', 'wpv-views' ),
				'current_page' => isset( $_GET['page'] ) ? $_GET['page'] : '' ,
				'assignment' => $this->get_layout_assignment_type( $this->object_id ),
			),
		);
		$toolset_assets_manager->localize_script(
			'toolset_theme_integration_admin_script',
			'Toolset_Theme_Integrations_Settings',
			$toolset_theme_integration_admin_script_i18n
		);

		$toolset_assets_manager->register_style(
			'toolset_theme_integration_admin_style',
			TOOLSET_THEME_SETTINGS_URL . '/res/css/toolset-theme-integration.css',
			array( 'wp-color-picker' ),
			TOOLSET_THEME_SETTINGS_VERSION
		);

		$toolset_assets_manager->register_script(
			'toolset_theme_integration_admin_gutenberg_script',
			TOOLSET_THEME_SETTINGS_URL . '/res/js/toolset-theme-integration.gutenberg.js',
			array( 'jquery', 'wp-color-picker' ),
			TOOLSET_THEME_SETTINGS_VERSION
		);

		$toolset_assets_manager->register_style(
			'toolset_theme_integration_admin_gutenberg_style',
			TOOLSET_THEME_SETTINGS_URL . '/res/css/toolset-theme-integration.gutenberg.css',
			array( 'wp-color-picker' ),
			TOOLSET_THEME_SETTINGS_VERSION
		);
	}

	public function enqueue_scripts() {
		if ( ! isset ( $_GET['page'] ) ) {
			return;
		}

		if ( in_array( $_GET['page'], $this->script_pages ) ) {
			do_action( 'toolset_enqueue_scripts',	array( 'toolset_theme_integration_admin_script' ) );
			do_action( 'toolset_enqueue_styles',	array( 'toolset_theme_integration_admin_style' ) );
		}
	}

	public function enqueue_styles() {
		if ( ! isset ( $_GET['page'] ) ) {
			return;
		}

		if ( in_array( $_GET['page'], $this->script_pages ) ) {
			// TODO: 'toolset_enqueue_styles' action can be used also with styles and script registered natively by WP, since the class keeps track of any style or scrypt registered with wp_register_* also outside of Toolset
			wp_enqueue_style( 'wp-pointer' );
		}
	}

	/**
	 * Render theme settings on Layouts edit screen in case when it is not Content Layout
	 * @return bool
	 */
	public function render_layouts_settings_section(){

		$is_private = apply_filters( 'ddl-is_layout_private', false, $this->object_id );
		if ( ! $is_private ) {
			$this->render_layouts_settings_collections( );
			return true;
		}
		return false;
	}

	private function get_settings_section_summary( $resource ) {
		ob_start();
		?>
		<span class="js-toolset-theme-settings-toggle-settings" style="display:block;cursor:pointer;background:#cdcdcd;padding:5px 10px;margin:10px 0;">
			<i class="fa fa-caret-down" aria-hidden="true"></i>
			<?php echo sprintf(
				__( '%s settings for this %s', 'wpv-views' ),
				'<strong>' . $this->active_theme->Name . '</strong>',
				$resource
			);
			?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the template for rendering options in the CT editor.
	 *
	 * @return string
	 * @since 1.3.3
	 */
	private function get_views_ct_settings_template() {
		$collections_raw = $this->collections->get_collections();
		$collections = $this->reformat_collections_for_gui( $collections_raw, 'single' );

		ob_start();
		require_once ( TOOLSET_THEME_SETTINGS_PATH . '/compatibility-modules/templates/toolset-theme-integration-settings-ct.tpl.php' );
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Register the theme settings metabox, only if the current CT is using the Gutenberg editor.
	 *
	 * @since 1.3.3
	 */
	public function maybe_init_metabox() {
		add_action( 'wpv-action-content-template-add-gutenberg-editor-metabox', array( $this, 'setup_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ), 100, 2 );
	}

	/**
	 * Render the theme settings in the Gutenberg editor, as a metabox.
	 *
	 * @return string
	 * @since 1.3.3
	 */
	public function render_views_ct_settings_meta_box() {
		$content = $this->get_views_ct_settings_template();
		$content .= '<input type="hidden" id="js-toolset-theme-block-editor-serialized-data" name="toolset_theme_settings_serialized" value="" />';
		echo $content;
	}

	/**
	 * Setup the theme settings metabox in the Gutenberg editor for this CT.
	 * Also, enqueue the required assets.
	 *
	 * @since 1.3.3
	 */
	public function setup_meta_box() {
		add_meta_box(
			'toolset_theme_settings_meta_box',
			sprintf(
				__( 'Theme options (%s)', 'wpv-views' ),
				$this->active_theme->Name
			),
			array( $this, 'render_views_ct_settings_meta_box' ),
			'view-template',
			'side',
			'low'
		);

		do_action( 'toolset_enqueue_scripts', array( 'toolset_theme_integration_admin_gutenberg_script' ) );
		do_action( 'toolset_enqueue_styles', array( 'toolset_theme_integration_admin_gutenberg_style' ) );
	}

	/**
	 * Save the theme settings metabox data.
	 *
	 * @param int $post_id
	 * @param WP_Post $post_object
	 * @since 1.3.3
	 */
	public function save_meta_box( $post_id, $post_object ) {
		if ( 'view-template' !== $post_object->post_type ) {
			return;
		}

		// Checks save status.
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST['toolset_display_type_nonce'] ) && wp_verify_nonce( $_POST['toolset_display_type_nonce'], 'toolset_theme_display_type' ) ) ? true : false;

		// Exits script depending on save status.
		if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
			return;
		}

		if (
			! isset( $_POST['toolset_theme_settings_serialized'] )
			|| empty( $_POST['toolset_theme_settings_serialized'] )
		) {
			return;
		}

		parse_str( $_POST['toolset_theme_settings_serialized'], $theme_settings );

		$theme_settings = $this->unprefix_saving_settings( $theme_settings );

		$this->views_ct_update_theme_settings( $post_id, $theme_settings );
	}

	/**
	 * Renders relevant sections in the Views native CT Edit Page.
	 *
	 * @since 2.5
	 */
	public function render_views_ct_settings_section() {
		$collections_raw = $this->collections->get_collections();
		$collections = $this->reformat_collections_for_gui( $collections_raw, 'single' );

		$content = $this->get_settings_section_summary( __( 'Content Template', 'wpv-views' ) );
		$content .= '<div class="js-toolset-theme-settings-toggling-settings" style="display:none">';
		$content .= $this->get_views_ct_settings_template();
		$content .= '</div>';
		wpv_ct_editor_render_section( __( 'Theme Options ', 'wpv-views' ) . $this->render_help_tip(), 'js-wpv-theme-options-section', $content );
	}

	/**
	 * @since 2.5
	 * Renders relevant sections in Views WPA Edit Page
	 */
	public function render_views_wpa_settings_section() {

		$collections_raw = $this->collections->get_collections();
		$collections = $this->reformat_collections_for_gui( $collections_raw, 'archive' );

		require_once (TOOLSET_THEME_SETTINGS_PATH.'/compatibility-modules/templates/toolset-theme-integration-settings-views-archive.tpl.php');

	}

	/**
	 * @since 2.5
	 * Render entire Theme options section below Layouts editor
	 */
	public function render_layouts_settings_collections() {

		$collections_raw = $this->collections->get_collections();
		$collections = $this->reformat_collections_for_gui( $collections_raw );
		$assignment_type = $this->get_layout_assignment_type( $this->object_id );
		$options_visible = ( $assignment_type == null || $assignment_type == 'posts-cred' );

		require_once (TOOLSET_THEME_SETTINGS_PATH.'/compatibility-modules/templates/toolset-theme-integration-settings-layouts.tpl.php');
	}


	public function get_targets_from_group($items){
		foreach($items as $item){
			foreach($item->target as $single_target){
				$targets[] = $single_target;
			}
		}
		return array_unique($targets);
	}

	/**
	 * @since 2.5
	 * Get array with option targets and returns list of css classes necessary for option visibility
	 * @param $targets
	 *
	 * @return string
	 */
	public function prepare_target_css_classes($targets){

		if ( is_array( $targets ) && count( $targets ) > 0 ) {
			$css_class = '';
			foreach ( $targets as $target ) {
				$css_class .=' js-target-'.$target;
			}
			return trim($css_class);
		} else {
			return '';
		}
	}


	/**
	 * @since 2.5
	 * Render different option elements, depends on type
	 *
	 * @param $element string
	 *
	 * @return Element HTML
	 */
	public function render_single_collection_item( $element ) {
		$element_type     = $element->gui->type;
		$target_css_class = $this->prepare_target_css_classes( $element->target );

		$exclude_targets = ( property_exists( $element,'target_exclude') ) ? $element->target_exclude : null;
		$prepare_data_exclude = $this->prepare_item_include_exclude_targets( $exclude_targets, 'exclude' );

		$include_targets = ( property_exists( $element,'target_include') ) ? $element->target_include : null;
		$prepare_data_include = $this->prepare_item_include_exclude_targets( $include_targets, 'include' );

		$templates_path   = TOOLSET_THEME_SETTINGS_ELEMENT_TEMPLATES_PATH;
		$selected_value = ! is_null( $element->get_current_value() ) ? $element->get_current_value() : $element->get_default_value();

		ob_start();
		require( $templates_path . $element_type . '.tpl.php' );
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Prepare data-target-include and data-target-exclude for specific options
	 *
	 * @param $items
	 * @param $action
	 *
	 * @return string
	 */
	public function prepare_item_include_exclude_targets( $items, $action ) {

		$data = json_encode( $items );
		if ( null !== $items ) {
			return "data-target-" . $action . "='" . $data ."'";
		} else {
			return '';
		}

	}


	/**
	 * @since 2.5
	 * Reformat collection for easier printing in GUI
	 * @param $collections object
	 * @return array - reformatted collections
	 */
	public function reformat_collections_for_gui( $collections, $target = null ) {
		$collection_groups = array();

		foreach ( $collections as $one_collection ) {
			foreach ( $one_collection->getIterator() as $item ) {
				if ( ! isset( $collection_groups[ $item->group ] ) ) {
					$collection_groups[ $item->group ] = $this->collections->where( 'group', $item->group );
				}
			}
		}
		return $this->merge_options_with_different_type( $collection_groups, $target );
	}

	public function merge_options_with_different_type( $collection_groups, $target ) {

		foreach ( $collection_groups as $one_group_key => $one_group_values ) {
			foreach ( $one_group_values as $item_key => $item_data ) {


				if( null !== $target && ! in_array($target, $item_data->target) ){
					unset( $collection_groups[ $one_group_key ][ $item_key ] );
					continue;
				}

				if ( isset( $collection_groups[ $one_group_key ][ $item_data->name ] ) ) {
					$collection_groups[ $one_group_key ][ $item_data->name ]->type = array_merge( array( $item_data->type ), $collection_groups[ $one_group_key ][ $item_data->name ]->type );
				} else {
					$item_data->type                                         = array( $item_data->type );
					$collection_groups[ $one_group_key ][ $item_data->name ] = $item_data;
				}
				unset( $collection_groups[ $one_group_key ][ $item_key ] );
			}
		}



		return $collection_groups;
	}



	/**
	 * @since 2.5
	 * renders the message for non-assigned layouts
	 */

	public function render_non_assigned_layout_message( $saved_choice = null, $visible = true ) {
		require_once (TOOLSET_THEME_SETTINGS_PATH.'/compatibility-modules/templates/toolset-theme-integration-layout-not-assigned-msg.tpl.php');
	}

	/**
	 * @since 2.5
	 * renders the tooltip pointer
	 */
	public function render_help_tip() {
		$pointer_classes = "";
		$pointer_title   = "";
		$pointer_content = "";

		if ( $this->current_object_type == 'dd_layouts' ) {
			$layout_assignment = $this->get_layout_assignment_type( $this->object_id );
			$pointer_classes   = 'wp-toolset-pointer wp-toolset-layouts-pointer';
			$pointer_title     = __( "{$this->active_theme->Name} settings for this layout", 'wp-views' );
			$pointer_content   = $this->get_layouts_tip_message( $layout_assignment );
		}

		if ( $this->current_object_type == 'view' ) {
			$pointer_classes = 'wp-toolset-pointer wp-toolset-views-pointer  wp-pointer-left';
			$pointer_title   = __( "{$this->active_theme->Name} settings for this archive", 'wp-views' );
			$pointer_content = __( 'This section lets you control the settings of the theme for this archive page.', 'wp-views' );
		}

		if ( $this->current_object_type == 'view-template' ) {
			$pointer_classes = 'wp-toolset-pointer wp-toolset-views-pointer  wp-pointer-left';
			$pointer_title   = __( "{$this->active_theme->Name} settings for this template", 'wp-views' );
			$pointer_content = __( 'This section lets you control the settings of the theme for all the pages that use this Content Template.', 'wp-views' );
		}

		return "<i class='icon-question-sign fa fa-question-circle toolset-theme-options-hint js-theme-options-hint' data-header='{$pointer_title}' data-content='{$pointer_content}' data-classes='{$pointer_classes}'></i>";
	}
}
