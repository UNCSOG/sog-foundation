<?php

use OTGS\Toolset\Layouts\Classes_Auto\Cells\Preview as Preview;

class WPDD_GUI_EDITOR extends WPDD_Layouts_Editor{
    protected $layout_id = null;
	private $is_private_layout = false;
    private $removed_cells = null;

    private $preview_main;
    private $assets_manager;
    private $constants;

	private static $layout_icons;

	const VIDEOS_OPTION_KEY = 'ddl_help_videos_';

    public function __construct( &$main, Toolset_Assets_Manager $assets_manager = null, Preview\Main $preview_main = null, Toolset_Constants $constants = null) {

        parent::__construct( $main );

	    self::$layout_icons = $this->icons_array();

        self::$MAX_NUM_POSTS = WPDDL_Settings::get_option_max_num_posts();

        $this->layout_id = isset($_GET['layout_id']) ? $_GET['layout_id'] : null;
		$this->is_private_layout = (isset($_GET['layout_id']) && WPDD_Utils::is_private($_GET['layout_id']) === true) ? true : false;


        global $post;

        $post = $post ? $post : get_post($this->layout_id);
        $this->post = $post;

	    add_filter( 'ddl-get_editor_js_strings', array( $this, 'push_editor_strings' ) );

        if (isset($_GET['page']) and $_GET['page'] == 'dd_layouts_edit') {
			$this->constants = $constants ? $constants : $this->get_toolset_constants();
			$this->assets_manager = $assets_manager ? $assets_manager : $this->get_assets_manager();
			$this->preview_main = $preview_main ? $preview_main : $this->get_preview_main();
			$this->preview_main->add_hooks();

			$this->dialogs = new WPDD_GUI_DIALOGS( );

            //add_action('current_screen', array(&$this, 'load_dialog_boxes') );
            $this->load_dialog_boxes();

            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );

            if ($this->post === null) {
                add_action('admin_enqueue_scripts', array($this, 'preload_styles'));
                add_action('wpddl_layout_not_found', array($this, 'layout_not_found'), 10);
                return;
            }

            $this->clean_orphaned_cells(null);

            add_action('wpddl_pre_render_editor', array($this, 'pre_render_editor'), 10, 1);
            add_action('wpddl_render_editor', array($this, 'render_editor'), 10, 1);
            add_action('wpddl_after_render_editor', array($this, 'after_render_editor'), 10, 1);


            //add_action('wpddl_after_render_editor', array($this,'print_where_used_links'), 11, 1);
            add_action('wpddl_after_render_editor', array($this, 'add_empty_where_used_ui'), 11, 1);
            add_action('wpddl_after_render_editor', array($this, 'add_video_toolbar'), 11, 1);


            if (!has_action('wpml_show_package_language_admin_bar')) {
                // If WPML doesn't have action show language switcher in the admin bar then
                // show on editor screen.
                add_action('wpddl_after_render_editor', array($this, 'add_wpml_ui'), 12, 1);
            }


            add_action('wpddl_layout_actions', array($this, 'layout_actions'));

            add_action('admin_enqueue_scripts', array($this, 'preload_styles'));
            add_action('admin_enqueue_scripts', array($this, 'preload_scripts'));

            add_action('admin_init', array($this, 'init_editor'));
            //add_action('admin_enqueue_scripts', array($this, 'load_latest_backbone'), -1000);

            do_action('wpddl_layout_actions');

        }

		/**
		 * @args: $post_type:string
         * @args: $current:int
         * @args: $amount:int
		 */
		add_filter( 'ddl-get_x_posts_of_type', array(&$this,'get_x_posts_of_type'), 10, 4 );

        /**
         * @args: $post_type:string
         * @args: $post->ID:int
         */
        add_filter( 'ddl-ddl_get_post_type_batched_preview_permalink', array(&$this,'ddl_get_post_type_batched_preview_permalink'), 10, 2);
        //leave wp_ajax out of the **** otherwise it won't be fired
	    add_action( 'wp_ajax_save_layout_data', array(&$this, 'save_layout_data_callback') );
        add_action('wp_ajax_get_layout_data', array(&$this, 'get_layout_data_callback'));
        add_action('wp_ajax_get_layout_parents', array(&$this, 'get_layout_parents_callback'));
        add_action('wp_ajax_check_for_parents_loop', array(&$this, 'check_for_parents_loop_callback'));
        add_action('wp_ajax_check_for_parent_child_layout_width', array(&$this, 'check_for_parent_child_layout_width_callback'));
        add_action('wp_ajax_view_layout_from_editor', array(&$this, 'view_layout_from_editor_callback'));
        add_action('wp_ajax_show_all_posts', array(&$this, 'show_all_posts_callback'));

        add_action('wp_ajax_ddl_get_where_used_ui', array(&$this, 'get_where_used_ui_callback'));
	    add_action('wp_ajax_ddl_update_show_cell_details_option', array(&$this, 'update_show_cell_details_option'));

        add_action('wp_ajax_edit_layout_slug', array(&$this, 'edit_layout_slug_callback'));

        add_action('wp_ajax_remove_all_layout_associations', array(&$this, 'remove_all_layout_associations_callback'));

        add_action('wp_ajax_ddl_update_wpml_state', array(&$this, 'update_wpml_state'));
        add_action('wp_ajax_ddl_load_assign_dialog_editor', array(&$this, 'load_assign_dialog_callback'));

        add_action('wp_ajax_ddl_compact_display_mode', array(&$this, 'compact_display_callback'));

    }

    public function get_preview_main(){
    	if( ! $this->preview_main ){
			$this->preview_main = new Preview\Main(
				$this->assets_manager ? $this->assets_manager : $this->get_assets_manager(),
				$this->constants ? $this->constants : $this->get_toolset_constants()
			);
		}
		return $this->preview_main;
	}

	public function get_assets_manager(){
		if( ! $this->assets_manager ){
			$this->assets_manager = Toolset_Assets_Manager::get_instance();
		}
		return $this->assets_manager;
	}

	public function get_toolset_constants(){
		if( ! $this->constants ){
			$this->constants = new Toolset_Constants();
		}
		return $this->constants;
	}


	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function print_parent_layout_controls(){
	    ob_start();
	    include 'templates/editor-layout-parent-layout-controls.tpl.php';
	    return ob_get_clean();
    }

    private function load_layout_box_content_template( $file ){
	    ob_start();
		include 'templates/editor-layout-type-'.$file.'_box.tpl.php';
	    $return = ob_get_clean();
	    return $return;
    }

	private function icons_array(){
		return array(
			'content' => (object) array(
				'icon' => 'layouts-icons/content-layout.png',
				'text' => $this->load_layout_box_content_template( 'template' ),
				'header' => __('This layout displays in \'the content\' area', 'ddl-layouts')
			),
            'content_layout' => (object) array(
                'icon' => 'layouts-icons/content-layout-no-link.png',
                'text' => $this->load_layout_box_content_template( 'content' ),
                'header' => __('This layout displays in \'the content\' area', 'ddl-layouts')
            ),
			'parent' => (object) array(
				'icon' => 'layouts-icons/parent-layout.png',
				'text' => $this->load_layout_box_content_template( 'parent' ),
				'header' => __('This is the parent layout for the site', 'ddl-layouts')
			),
			'template' => (object) array(
				'icon' => 'layouts-icons/template-layout.png',
				'text' => $this->load_layout_box_content_template( 'template' ),
				'header' => __('This layout lets you design the entire page', 'ddl-layouts')

			),
			'child' => (object) array(
				'icon' => 'layouts-icons/child-layout.png',
				'text' => $this->load_layout_box_content_template( 'child' ),
				'header' => __('This is a child layout', 'ddl-layouts')
			),
		);
	}



    protected function get_layouts( $layout_slug = null /*php compatible declaration*/ ){
        return $this->layouts;
    }

    protected function get_layout( $layout_slug = null /*php compatible declaration*/ ){
            return $this->layout_id;
    }

    private function clean_orphaned_cells( $layout_id = null ){
        $this->layout_id = is_null( $layout_id ) ? $this->layout_id : $layout_id;

        if( null !== $this->layout_id ){
            $clean_up = new WPDDL_LayoutsCleaner(
                $this->layout_id
            );
            $this->removed_cells = $clean_up->remove_cells_of_type_by_property('cell-content-template', 'ddl_view_template_id');
        }
    }

    function load_dialog_boxes(){
        $dialogs = array();
        $dialogs[] = new WPDDL_EditorDialogs(
        	array(
        		'toolset_page_dd_layouts_edit',
		        'toplevel_page_dd_layouts_edit'
	        )
        );
        foreach( $dialogs as &$dialog ){
            add_action('current_screen', array(&$dialog, 'init_screen_render') );
        }
        return $dialogs;
    }

	function __destruct(){
	}

	function init_editor(){
        if( false === $this->is_private_layout ){

            $this->layouts[$this->layout_id] = WPDD_Layouts::get_layout_from_id ( $this->layout_id );

            $this->list_where_used = $this->get_where_used_lists( $this->layout_id );

            if( isset( $this->layouts[$this->layout_id] ) && $this->layouts[$this->layout_id] instanceof WPDD_layout ){
	            do_action( 'wpml_show_package_language_admin_bar', $this->layouts[$this->layout_id]->get_string_context() );
            }

        } else {
            $this->list_where_used = null;
        }
	}

	private function print_layout_icon(){

		// if it is a parent regardless of his hierarchy level, display it as such
		if( $this->is_private_layout ){
			return self::$layout_icons['content_layout'];
		}
		else if( $this->layouts[$this->layout_id] instanceof WPDD_layout && $this->layouts[$this->layout_id]->get_row_with_child() !== null ){
			return self::$layout_icons['parent'];
		} else if( $this->layouts[$this->layout_id] instanceof WPDD_layout && $this->layouts[$this->layout_id]->get_parent_name()  ) {
			return self::$layout_icons['child'];
		} else if( apply_filters( 'ddl-is_integrated_theme', false ) === false || $this->is_private_layout ) {
			return self::$layout_icons['content'];
		} else {
			return self::$layout_icons['template'];
		}

		return self::$layout_icons['template'];
	}

	function layout_not_found(){
		include_once 'templates/editor-layout-does-not-exist.tpl.php';
	}

	public function compact_display_callback() {

		if( $_POST && wp_verify_nonce( $_POST['compact_display_nonce'], 'compact_display_nonce' ) ) {

			$this->main->save_option(array('compact_display' => $_POST['mode'] == 'true' ? true : false));
		}

		die();
	}

	public function edit_layout_slug_callback()
	{
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['edit_layout_slug_nonce'], 'edit_layout_slug_nonce' ) )
		{
		    $title = $this->remove_section_mark_from_slug( get_the_title( $_POST['layout_id'] ) );
		    $old_slug = $this->remove_section_mark_from_slug( $_POST['slug'] );
			$slug = get_sample_permalink( $_POST['layout_id'], $title, $old_slug );
			$send = wp_json_encode( array( 'Data' =>  array( 'slug' => $slug[1] ) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}

	private function remove_section_mark_from_slug( $string = '' ){
	    return str_replace( '§', '', $string );
    }

	public function remove_all_layout_associations_callback()
	{

        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['ddl_remove_all_layout_association_nonce'], 'ddl_remove_all_layout_association_nonce' ) )
		{
			$send = wp_json_encode( array( 'Data' => $this->editor_purge_all_associations( $_POST['layout_id'] ) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}

	private function editor_purge_all_associations( $layout_id )
	{

		$associations = $this->get_where_used_lists( $layout_id );

		$loops = is_object($associations) && property_exists($associations, 'loops') ? $associations->loops : false;
		$types = is_object($associations) && property_exists($associations, 'post_types') ? $associations->post_types : false;
		$posts = is_object($associations) && property_exists($associations, 'posts') ? $associations->posts : false;

		if( $loops && count($loops) > 0 )
		{
			$loops_manager = $this->main->layout_post_loop_cell_manager;
			$remove = array();

			foreach( $loops as  $loop )
			{
				$loop = (object) $loop;
				$remove[] = $loop->name;
			}
			$loops_manager->remove_archives_association( $remove, $layout_id );
		}

		if( ( $posts && count($posts) > 0 ) || ( $types && count($types) > 0 ) )
		{
			$this->main->post_types_manager->purge_layout_post_type_data( $layout_id );
		}

		return $associations;
	}

	public function add_empty_where_used_ui() {

		?>

		<div class="where-used-ui js-where-used-ui">
			<?php $this->add_select_post_types(); ?>
		</div>

	<?php

	}

	public function get_where_used_ui_callback() {

        if( user_can_assign_layouts() === false ){
            die( __("You don't have permission to perform this action!", 'ddl-layouts') );
        }

		if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
				'ddl_layout_view_nonce')) {
			die('verification failed');
		}

		echo $this->get_where_used_output( $_POST['layout_id'] );
		die();
	}

	public function update_show_cell_details_option(){

		$nonce = $_POST["wpnonce"];

		// check permission
		if ( user_can_assign_layouts() === false ) {
			$result = array(
				'error'         => 'error',
				'error_message' => __( 'You are not allowed to do changes on layouts', 'ddl-layouts' )
			);
			wp_send_json( $result );
		}

		// check nonce
		if ( ! wp_verify_nonce( $nonce, 'change_cell_details_option_nonce' ) ) {
			$result = array(
				'error'         => 'error',
				'error_message' => __( 'Security check failed', 'ddl-layouts' )
			);
			wp_send_json( $result );
		}

		// save changes
		$show_cell_details = isset( $_POST['option_value'] ) ? $_POST['option_value'] : 'no';
		$update_options = update_option( WPDDL_SHOW_CELL_DETAILS_ON_INSERT, $show_cell_details );
		wp_send_json( array( 'Data' => $update_options ) );
	}

	function get_where_used_output( $layout_id )
	{
        ob_start();
        $this->layout_id = $layout_id;
        do_action( 'wpml_switch_language', $this->lang );
        $this->list_where_used = $this->get_where_used_lists( $this->layout_id );
        $this->add_select_post_types();
        $output = ob_get_clean();
        return $output;
	}

	function add_wpml_ui () {

        if( !$this->is_private_layout ) {
	        $post   = get_post( $_GET['layout_id'] );
	        $layout = WPDD_Layouts::get_layout_from_id( $post->ID );

	        ob_start();
	        if( $layout instanceof WPDD_layout ){
		        do_action( 'wpml_show_package_language_ui', $layout->get_string_context() );
	        }
	        $lang_selector = ob_get_clean();

	        ?>

	        <div id="js-dd-layouts-lang-wrap" class="dd-layouts-wrap" <?php if ( ! $lang_selector ) { echo ' style="display:none"'; } ?>>
		        <div class="dd-layouts-lang-wrap">
			        <?php echo $lang_selector; ?>
		        </div>
	        </div>

	        <?php
        }

	}

	public function get_layout_data_callback()
	{
		echo WPDD_Layouts::get_layout_settings($_POST['layout_id'], false, false);
		die(  );
	}

	private function user_can_edit_content_layout(){

	}

	public function save_layout_data_callback()
	{

        if( user_can_edit_layouts() === false && user_can_edit_content_layouts( $_POST['layout_id'] ) === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && isset( $_POST['save_layout_nonce'] ) && wp_verify_nonce( $_POST['save_layout_nonce'], 'save_layout_nonce' ) )
		{
            $send = $this->save_layout_data( $_POST );
		}
		else
		{
			$send = wp_json_encode(array( "Data" => array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) ) );
		}

		echo wp_json_encode( array( 'Data' => $send ) );

		die();
	}

	private function handle_archives_data_save($archives, $layout_id)
	{
		$check = $this->main->layout_post_loop_cell_manager->get_layout_loops( $layout_id );

		if( $archives !== $check )
		{
			$this->main->layout_post_loop_cell_manager->handle_archives_data_save( $archives, $layout_id );
		}
	}


    /**
     * @param $css
     * @return mixed
     * @deprecated
     */
	private function handle_layout_css( $css )
	{
		return $this->main->css_manager->handle_layout_css_save( $css );
	}
	//TODO:this function is depracated
	private function handle_post_type_data_save( $post_types, $layout_id )
	{
		$save = $post_types['layout_'.$layout_id];
		$check = $this->main->post_types_manager->get_layout_post_types( $layout_id );

		if( $save === $check || $post_types === null || !$post_types )
		{
			return false;
		}

		return $this->main->post_types_manager->handle_post_type_data_save( $layout_id, $post_types );
	}

	public function get_layout_parents_callback() {

		if( !isset( $_POST['layout_name'] ) || is_null( $_POST['layout_name'] ) ){
			die();
		}

		$parents = array();

		$layout = $this->main->get_layout( $_POST['layout_name'] );

		if ($layout) {
			$parent_layout = $layout->get_parent_layout();


			while ($parent_layout) {
				$parents[] = $parent_layout->get_post_slug();

				$parent_layout = $parent_layout->get_parent_layout();
			}
		}

		echo wp_json_encode($parents);

		die();
	}

	public function check_for_parents_loop_callback () {
		$loop_found = false;

		$layout = $this->main->get_layout( $_POST['new_parent_layout_name'] );

		if ($layout) {
			$parent_layout = $layout->get_parent_layout();

			while ($parent_layout) {
				if ($_POST['layout_name'] == $parent_layout->get_name()) {
					$loop_found = true;
					break;
				}

				$parent_layout = $parent_layout->get_parent_layout();
			}
		}

		if ($loop_found) {
			echo wp_json_encode(array('error' => sprintf(__("You can't use %s as a parent layout as it or one of its parents has the current layout as a parent.", 'ddl-layouts'), '<strong>' . $_POST['new_parent_layout_name'] . '</strong>') ) );
		} else {
			echo wp_json_encode(array('error' => ''));
		}

		die();

	}

	public function check_for_parent_child_layout_width_callback () {

		$layout = $this->main->get_layout( $_POST['parent_layout_name'] );

		$result = wp_json_encode(array('error' => ''));

		if ($layout) {
			$child_layout_width = $layout->get_width_of_child_layout_cell();

			if ($child_layout_width != $_POST['width']) {
				$result = wp_json_encode(array('error' => sprintf(__("This layout width is %d and the child layout width in %s is %d. This layout may not display correctly.", 'ddl-layouts'), $_POST['width'], '<strong>' . $_POST['parent_layout_title'] . '</strong>', $child_layout_width) ) );
			}
		}

		echo $result;

		die();
	}

	function preload_styles(){
		$this->main->enqueue_styles(
			array(
				'progress-bar-css' ,
				'font-awesome',
				'toolset-notifications-css',
				'jq-snippet-css',
				'wp-jquery-ui-dialog',
				'wp-editor-layouts-css',
				'toolset-colorbox',
				'toolset-common',
				'ddl-dialogs-css',
				'wp-pointer' ,
				'toolset-select2-css',
				'layouts-select2-overrides-css',
				'wp-mediaelement',
				'toolset-chosen-styles'
			)
		);

		$this->main->enqueue_cell_styles();
	}

	function preload_scripts(){

		//speed up ajax calls sensibly

		$this->main->enqueue_scripts(
			array(
				'jquery-ui-cell-sortable',
				'jquery-ui-custom-sortable',
				'jquery-ui-resizable',
				'jquery-ui-tabs',
				'wp-pointer',
				'backbone',
				'toolset_select2',
				'toolset-utils',
				'wp-pointer',
				'wp-mediaelement',
				'ddl-sanitize-html',
				'ddl-sanitize-helper',
				'ddl-post-types',
				//'ddl-individual-assignment-manager',
				'ddl-editor-main',
				'media_uploader_js',
				'icl_media-manager-js',
				'toolset-chosen-wrapper'
				//'ddl-post-type-options-script'
			)
		);

		$this->main->localize_script('ddl-editor-main', 'icl_media_manager', array(
				'only_img_allowed_here' => __( "You can only use an image file here", 'ddl-layouts' )
			)
		);

		$this->main->localize_script('ddl-editor-main', 'DDLayout_settings', array(
				'DDL_JS' => array(
					'available_cell_types' => $this->main->get_cell_types(),
					'toolset_cells_data' => WPDD_Utils::toolsetCellTypes(),
					'res_path' => WPDDL_RES_RELPATH,
					'lib_path' => WPDDL_RES_RELPATH . '/js/external_libraries/',
					'editor_lib_path' => WPDDL_GUI_RELPATH."editor/js/",
					'common_rel_path' => WPDDL_TOOLSET_COMMON_RELPATH,
					'dialogs_lib_path' => WPDDL_GUI_RELPATH."dialogs/js/",
					'is_new_layout' => ( isset( $_GET['new'] ) && $_GET['new'] === 'true') ? true : false,
					'layout_id' => $this->layout_id,
					'is_private_layout' => $this->is_private_layout,
					'show_cell_details' => get_option( WPDDL_SHOW_CELL_DETAILS_ON_INSERT, 'yes' ),
					'create_layout_nonce' => wp_create_nonce('create_layout_nonce'),
					'save_layout_nonce' => wp_create_nonce('save_layout_nonce'),
					'ddl-view-layout-nonce' => wp_create_nonce('ddl-view-layout-nonce'),
					'ddl_show_all_posts_nonce' => wp_create_nonce('ddl_show_all_posts_nonce'),
					'edit_layout_slug_nonce' => wp_create_nonce('edit_layout_slug_nonce'),
					'change_cell_details_option_nonce' => wp_create_nonce('change_cell_details_option_nonce'),
					'compact_display_nonce' => wp_create_nonce('compact_display_nonce'),
                    'parents_options_nonce' => wp_create_nonce( WPDDL_Options::PARENTS_OPTIONS.'_nonce', WPDDL_Options::PARENTS_OPTIONS.'nonce' ),
                    'parent_option_name' => WPDDL_Options::PARENTS_OPTIONS,
                    'compact_display_mode' => $this->main->get_option('compact_display'),
					'DEBUG' => WPDDL_DEBUG,
					'strings' => $this->get_editor_js_strings(),
					'has_theme_sections' => $this->main->has_theme_sections(),
					'AMOUNT_OF_POSTS_TO_SHOW' => self::AMOUNT_OF_POSTS_TO_SHOW,
					'is_css_enabled' => $this->main->css_manager->is_css_possible()
				, 'current_framework' => $this->main->frameworks_options_manager->get_current_framework()
                , 'removed_cells' => $this->removed_cells,
					'user_can_delete' => user_can_delete_layouts(),
					'user_can_assign' => user_can_assign_layouts(),
					'user_can_edit' => user_can_edit_layouts(),
					'user_can_create' => user_can_create_layouts(),
                    'layouts_css_properties' => WPDDL_CSSEditor::get_all_css_names()
					,'media_settings' => WPDD_Utils::get_image_sizes('thumbnail')
					, 'site_url' => get_site_url()
                    , 'preview_width' => self::PREVIEW_WIDTH
                    , 'preview_height' => self::PREVIEW_HEIGHT
                    , 'default_img_url' => DDL_ICONS_SVG_REL_PATH . 'image-box.svg'
					, 'get_shortcode_regex' => get_shortcode_regex(),
						'max_num_posts' => DDL_MAX_NUM_POSTS
                    , 'POPUP_MESSAGE_OPTION'  => self::POPUP_MESSAGE_OPTION
                    , 'default_parent' => $this->get_default_parent()
					, 'layout_trash_nonce' => wp_create_nonce('layout-select-trash-nonce')
                    , 'trash_redirect' => isset($_GET['ref']) && $_GET['ref'] === 'dashboard' ? admin_url( 'admin.php?page=toolset-dashboard' ) : admin_url( 'admin.php?page=dd_layouts' )
				    , 'is_layout_assigned' => apply_filters( 'ddl-is_layout_assigned', false, $this->layout_id )
                    , 'container_elements' => apply_filters('ddl-containers_elements', array())
					, 'wpml_is_active' => defined( 'WPML_TM_VERSION' )
					, 'cells_data' => $this->get_cells_data()
					, 'is_integrated_theme' => apply_filters( 'ddl-is_integrated_theme', false )
					, 'layout_type_icons' => self::$layout_icons
                    , 'column_prefixes_data' => $this->get_framework_prefixes_data()
                    , 'column_prefix_default' => $this->settings->get_column_prefix()
                    , 'WPDDL_VERSION' => WPDDL_VERSION,
					'bootstrap_version' => Toolset_Settings::get_instance()->get_bootstrap_version_numeric(),
					'close' => __( 'Close','dd-layouts' ),
				),
                'DDL_OPN' => WPDD_LayoutsListing::change_layout_dialog_options_name()
			)
		);

		$this->main->enqueue_cell_scripts();

	}

	function push_editor_strings( $strings ) {

		$new_strings = array(
            'removed_cells_message' => sprintf( __('%d orphaned Content Template cell(s): %s have been deleted from this Layout since the associated Views Content Template was deleted outside the Layouts editor', 'ddl-layouts'), count($this->removed_cells), $this->removed_cells && count($this->removed_cells) > 0 ? implode(', ', $this->removed_cells) : '' ),
            'change_parent_layout' => __('Change parent layout', 'ddl-layouts'),
			'set_parent_layout' => __('Set parent layout', 'ddl-layouts'),
            'bootstrap_dialog_title' => __( 'Select the Bootstrap column width', 'ddl-layouts' ),
            'save_no_close_view_iframe' => __( 'Save View', 'ddl-layouts'),
            'cred_create' => __( 'Do you really want to add a form for creating new posts in a template? Usually, it makes more sense to include forms that create new content in a page.', 'ddl-layouts'),
			'cred_edit' => sprintf( __( 'You will need to link to this layout, to edit the current post or a post in a loop. %sInstructions%s', 'ddl-layouts'), '<a href="'.WPDDL_CRED_EDIT_FORMS.'" target="_blank" >', '</a>' ),
            'cred_edit_private' => sprintf( __( 'This form will edit the current page. Is this what you really want to do? %sLearn how to work with forms that edit the current post or a post in a loop.%s ', 'ddl-layouts'), '<a href="'.WPDDL_CRED_EDIT_FORMS.'" target="_blank" >', '</a>' ),
			'cred_edit_user_private' => __( 'This form will edit the current user. ', 'ddl-layouts'),
			'cred_edit_user' => sprintf( __( 'You will need to link to this layout, to edit the current user or a user in a loop. %sInstructions%s', 'ddl-layouts'), '<a href="'.WPDDL_CRED_EDIT_FORMS.'" target="_blank">', '</a>' ),
			'cred_create_user' =>  __( 'Do you really want to add a form for registering new users in a template? Usually, it makes more sense to include forms that create new users in a page. ', 'ddl-layouts'),
		);

		return wp_parse_args( $new_strings, $strings );
	}

	function load_latest_backbone() {
		// load our own version of backbone for the editor.
		wp_dequeue_script('backbone');
		wp_deregister_script('backbone');
		wp_register_script('backbone', WPDDL_RES_RELPATH . '/js/external_libraries/backbone-min.js', array('underscore','jquery'), '1.1.0');
		wp_enqueue_script('backbone');

	}

	function pre_render_editor($inline) {

        ?>

		<div class="wrap" id="js-dd-layout-editor">
        <?php if( WPDDL_DEBUG ): ?>
        Last edited at: <?php echo WPDD_Layouts::get_toolset_edit_last_in_readable_format($this->layout_id) ?>
            <?php endif; ?>
		<?php

		$post = $this->post;

		if (!$inline) {
			include_once 'templates/editor_header_box.tpl.php';
		}

	}

	function render_editor($inline){
		$this->ddl_render_editor($inline);
	}

    function ddl_render_editor($inline){


        // Get layout
        if ($inline) {
            $post = get_post($_GET['post']);
            $layout_json = WPDD_Layouts::get_layout_json_settings_encoded_64($post->ID, true);
            if (!$layout_json) {
                // This post doesn't have a layout so create an empty one
                $preset_dir = WPDDL_RES_ABSPATH . '/preset-layouts/';
                $layout = WPDD_Layouts::load_layout($preset_dir . '4-evenly-spaced-columns.ddl');

                // Force fluid when using in post editor.
                $layout['type'] = 'fluid';
                for ($i = 0; $i < sizeof($layout['Rows']); $i++) {
                    $layout['Rows'][$i]['layout_type'] = 'fluid';
                }
                $layout_json = wp_json_encode($layout);
            }
        } else {
            $post = get_post($_GET['layout_id']);
            // $layout_json_not_decoded = WPDD_Layouts::get_layout_settings($post->ID);
            $layout_json = WPDD_Layouts::get_layout_json_settings_encoded_64($post->ID, true);
        }


        ob_start();

        WPDD_GUI_EDITOR::load_js_templates('/js/templates');

        if($this->is_private_layout === true){

        	$get_private_layout_data = json_decode(WPDD_Utils::get_private_layout_data($post->ID));
			$post_title = get_the_title($post->ID);

            include_once 'templates/editor_box_private.tpl.php';
        } else {
            include_once 'templates/editor_box.tpl.php';
        }

        echo ob_get_clean();

    }

	/**
	 * Determines if a Content Template is currently being edited.
	 *
	 * @return bool
	 */
	private function is_editing_ct() {
		// The condition below is for the case where a Views Content Template is edited by the private layout Editor.
		// In this case there is no need to display the preview button because, there is no post to preview it with.
		if ( '' !== toolset_getget( 'source', '', array( 'ct-editor' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if an inline Content Template is currently being edited.
	 *
	 * @return bool
	 */
	private function is_editing_inline_ct() {
		// The condition below is for the case where a Views Content Template is edited by the private layout Editor.
		// In this case there is no need to display the preview button because, there is no post to preview it with.
		if ( '' !== toolset_getget( 'source', '', array( 'views-editor' ) ) ) {
			return true;
		}

		return false;
	}

    private function track_help_video_watched(){
	    $option = update_option( self::VIDEOS_OPTION_KEY . $this->get_current_user_id(), 'yes' );
		return $option;
    }

	private function check_help_video_watched(){

		$option = get_option( self::VIDEOS_OPTION_KEY . $this->get_current_user_id(), 'no' );

		if( $option === 'yes' ){
			return true;
		} else {
			return false;
		}
	}

	private function get_current_user_id(){
		global $current_user;
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		return $user_id;
	}

	private function current_is_default_parent(){
        return (int) $this->get_default_parent() === (int) $this->layout_id;
    }

    private function get_default_parent(){
        return $this->settings->get_default_parent();
    }

	function after_render_editor() {

		?>
		</div> <!-- .wrap -->

	<?php
	}

	function layout_actions(){
		if(isset($_REQUEST['action'])){
			switch ($_REQUEST['action']) {
				case 'trash':
					$this->delete_layout($_REQUEST['post']);
					break;
				default:
					break;
			}
		}
	}

	function delete_layout($layout_id){
		$post_id = $layout_id;
		$bool = wp_delete_post($post_id, true);
		delete_post_meta($post_id, WPDDL_LAYOUTS_SETTINGS);
		delete_post_meta($post_id, 'dd_layouts_header');
		delete_post_meta($post_id, 'dd_layouts_styles');
		do_action( 'ddl_layout_has_been_deleted', $bool, $post_id);
		$url = home_url( 'wp-admin').'/admin.php?page=dd_layouts';
		header("Location: $url", true, 302);
		die();
	}

	public static function load_js_templates( $tpls_dir )
	{
		global $wpddlayout;

		WPDD_FileManager::include_files_from_dir( dirname(__FILE__), $tpls_dir );

		echo apply_filters("ddl_print_cells_templates_in_editor_page", $wpddlayout->get_cell_templates() );
	}

	public static function print_layouts_css()
	{
	    global $wpddlayout;

		echo $$wpddlayout->get_layout_css();
	}

	public function add_where_used_links( $layout_id = false, $all = false, $offset = 0, $amount_per_page = self::AMOUNT_OF_POSTS_TO_SHOW ) {

		$get = $layout_id ? $layout_id :$_GET['layout_id'];
                $current = $layout_id;

                // get all posts
		$items = $this->get_where_used_x_amount_of_posts( $get, $all, $amount_per_page, $offset );
		$posts = $items->posts;

                // get posts count
                $number_of_posts = $this->main->get_where_used_count();


                // create new object for posts, post_types and loops
		$lists = new stdClass();

                // add posts
		if( count( $posts  ) > 0 ){
                    $lists->posts = $posts;
		}

                // get total posts count
                $total_count = count($lists->posts);

                // show output
		ob_start();
		include_once WPDDL_GUI_ABSPATH.'editor/templates/list-layouts-where_used.box.tpl.php';
		return ob_get_clean();
	}

	public function show_all_posts_callback()
	{
        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['ddl_show_all_posts_nonce'], 'ddl_show_all_posts_nonce' ) )
		{
			$amount = $_POST['amount'] == 'all' ? true : false;
                        $amount_per_page = (!$_POST['per_page_amount'])  ? -1 : $_POST['per_page_amount'];
                        $offset = empty($_POST['offset']) ? 0 : $_POST['offset'];
			$send = wp_json_encode( array( 'Data' => array( 'where_used_html' => $this->add_where_used_links( $_POST['layout_id'], $amount, $offset, $amount_per_page ) ) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}

	public function print_where_used_links()
	{
		echo '<div id="js-print_where_used_links dd-layouts-wrap">' . $this->add_where_used_links() . '</div>';
	}

	public function get_where_used_x_amount_of_posts( $layout_id, $all = false, $amount = self::AMOUNT_OF_POSTS_TO_SHOW, $offset = 0 )
	{
		$ret = new stdClass();
		$ret->posts = array();
		$temp = array();

		$post_types = $this->main->individual_assignment_manager->get_post_types( $layout_id );
		$post_types_query = array_diff( $this->main->post_types_manager->get_post_types_from_wp( 'names' ), $post_types );

		$posts = $this->main->get_where_used( $layout_id, false, true, $amount, array('publish', 'draft', 'private', 'future'), 'default', $post_types_query, false, $offset );

                $ret->found_posts = $this->main->get_where_used_count();
		$ret->shown_posts = 0;

		if( $all === true ) $amount = count( $posts );

		if( !is_array($posts) ) return $ret;

		if( isset( $posts ) ){
			foreach( $posts as $post )
			{
				if( !isset($temp[$post->post_type]) )
				{
					$temp[$post->post_type] = array();
				}

				$len = count( $temp[$post->post_type] );

				if( $len < $amount )
				{
					$item = new stdClass();
					$item->post_title = $post->post_title;
					$item->ID = $post->ID;
					$item->post_name = $post->post_name;
					$item->post_type = $post->post_type;
					$item->edit_link = get_edit_post_link( $post->ID);
					$item->permalink = get_permalink( $post->ID );
					$ret->posts[] = $item;
					$ret->shown_posts++;
				}

				$temp[$post->post_type][] = $post->ID;
			}
		}

		$keys = array_keys($temp);

		foreach( $keys as $key )
		{
			$ret->{$key} = count($temp[$key]);
		}

		return $ret;
	}

	public function view_layout_from_editor_callback( )
	{
        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }

		if( $_POST && wp_verify_nonce( $_POST['ddl-view-layout-nonce'], 'ddl-view-layout-nonce' ) )
		{

			$layout = WPDD_Layouts::get_layout_settings($_POST['layout_id'], true, false);
			if ($layout && isset($layout->has_child) && ($layout->has_child === 'true' || $layout->has_child === true)) {

                $children = apply_filters( 'ddl-parent_helper_get_create_children', null);

                $count_assignments = $children['count_assignments'];
                $count_children = $children['count_children'];

                if( $count_children > 0 ){

                    if( $count_assignments > 0 ){
                        $message = __( "This layout has child layouts, which are displayed inside it. To preview it, please select which child layout to display:", 'ddl-layouts');
                    } else {
                        $message = __( "This layout has child layouts, but none of them can be displayed, because they are not yet assigned to content. <br>Please edit one of the child layouts, assign it to content and you will be able to preview:", 'ddl-layouts');
                    }

                } else {
                    $message = sprintf( __( "This layout has no child layouts yet. To preview it, you should create a child first and assign it to content. %s %sLearn about hierarchical layouts%s ", 'ddl-layouts'), '<br>', '<a href="'.WPDLL_CHILD_LAYOUT_CELL.'" target="_blank" >', '</a>' );
                }

				$send = wp_json_encode( array( 'message' =>  $message,
                        'Data' => array(
                            'items' => $children,
                            'parent' => true,
                            'count_children' => $count_children,
                            'count_assignments' => $count_assignments
                        ),
                       'layout_type' => property_exists($layout, 'layout_type') ? $layout->layout_type : $this->layout_type,
				    )
                );

			} else {

				$items = $this->get_where_used_x_amount_of_posts( $_POST['layout_id'], false, 3 );
				$posts = $items->posts;
				$layout_post_types = $this->main->post_types_manager->get_layout_post_types( $_POST['layout_id'] );


				$loops = $this->main->layout_post_loop_cell_manager->get_layout_loops( $_POST['layout_id'] );

				if( count($posts) === 0 && count($loops) === 0 && count($layout_post_types) === 0 )
				{
					$send = wp_json_encode( array(
						'layout_type' => property_exists($layout, 'layout_type') ? $layout->layout_type : $this->layout_type,
						'post_url' => get_permalink($_POST['layout_id']),
						'message' =>  __( sprintf("This layout is not assigned to any content. %sFirst, assign it to content and then you can view it on the site's front-end. %sYou can assign this layout to content at the bottom of the layout editor.", '<br>', '<br>' ), 'ddl-layouts')
						)
					);
				}
				else
				{
					$items = array();

					foreach( $layout_post_types as $post_type ){
						$push = $this->get_x_posts_of_type($post_type, $_POST['layout_id'], 1, array( 'publish' ) );
						if( is_array( $push ) ){
							$posts = array_merge( $posts, $push );
						}
					}

					foreach( $posts as $post )
					{
						$post_types = $this->main->post_types_manager->get_post_types_from_wp();
						$label = $post_types[$post->post_type]->labels->singular_name;
						$labels = $post_types[$post->post_type]->labels->name;
						$item = array( 'href' => get_permalink( $post->ID ), 'title' => $post->post_title, 'type' => $label, 'types' => $labels  );
						if( in_array( $item, $items ) === false ){
							$items[] = $item;
						}
					}


					foreach( $loops as $loop )
					{
						$push = $this->main->layout_post_loop_cell_manager->get_loop_display_object( $loop );
						if( null !== $push  )
							array_push( $items, $push );
					}

					if ( count( $items ) === 0 && in_array( 'attachment', $layout_post_types ) ) {
						$send = wp_json_encode( array(
								'layout_type'        => 'normal',
								'message'            => __( sprintf( "This layout is assigned only to Media and preview is currently not available." ), 'ddl-layouts' ),
								'no_preview_message' => __( 'No previews available', 'ddl-layouts' )
							)
						);
					} else {
						$send = wp_json_encode( array(
								'Data'               => $items,
								'layout_type'        => property_exists( $layout, 'layout_type' ) ? $layout->layout_type : $this->layout_type,
								'message'            => __( sprintf( "This layout is not assigned to any content. %sFirst, assign it to content and then you can view it on the site's front-end. %sYou can assign this layout to content at the bottom of the layout editor.", '<br>', '<br>' ), 'ddl-layouts' ),
								'no_preview_message' => __( 'No previews available', 'ddl-layouts' )
							)
						);
					}



				}
			}
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);

	}

	public function add_video_toolbar()
	{
		include_once WPDDL_GUI_ABSPATH.'editor/templates/tutorial-video-bar.box.tpl.php';
	}

	private function get_where_used_lists( $layout_id = null )
	{
		$id = $layout_id ? $layout_id : $this->layout_id;

		$post_types = $this->main->post_types_manager->get_layout_post_types_object( $id );
		//	$post_types_assigned = $this->main->individual_assignment_manager->get_post_types( $layout_id );
		$amount = self::AMOUNT_OF_POSTS_TO_SHOW;


		$items = $this->get_where_used_x_amount_of_posts( $id, true, $amount );

		$posts = $items->posts;

		$loops = $this->main->layout_post_loop_cell_manager->get_layout_loops( $id );

		if( (!$post_types || count( $post_types ) === 0) && count( $posts  ) === 0 && count( $loops ) === 0 )
		{
			return null;
		}

		$ret = new stdClass();

		if( count( $posts  ) > 0 )
		{
			$ret->posts = $posts;
		}

		if( $post_types && count( $post_types ) )
		{
			$ret->post_types = $post_types;
		}

		if( count( $loops ) > 0 )
		{
			$loops_display = array();

			foreach( $loops as $loop )
			{
				$push = $this->main->layout_post_loop_cell_manager->get_loop_display_object( $loop );

				if( null !== $push  )
					$push['name'] =  $loop;
				array_push( $loops_display, $push );
			}

			$ret->loops = $loops_display;
		}

		return $ret;
	}

	public function get_x_posts_of_type( $post_type, $layout_id, $amount = self::AMOUNT_OF_POSTS_TO_SHOW, $post_status = null  )
	{

		$layout = WPDD_Layouts::get_layout_from_id ( $layout_id );

		$args = array(
			'posts_per_page' => $amount,
			'post_type' => $post_type,
            'post_status' => ( $post_status != null && is_array( $post_status ) && count( $post_status ) > 0 ) ? $post_status : array( 'publish', 'future', 'draft', 'pending', 'private', 'inherit' ),
			'meta_query' => array (
				array (
					'key' => WPDDL_LAYOUTS_META_KEY,
					'value' => $layout->get_post_slug(),
					'compare' => '=',
				)
			) );

		$new_query = new WP_Query( $args );

		$posts = $new_query->posts;

		return count( $posts ) > 0 && isset( $posts[0] ) ? $posts : null;
	}

	function ddl_get_post_type_batched_preview_permalink($post_type, $post_id){
		$id = $this->main->layout_post_loop_cell_manager->get_option( WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type);

		if( $id && $id == $this->layout_id  )
		{
			$loop = (object) $this->main->layout_post_loop_cell_manager->get_loop_display_object(WPDD_layout_post_loop_cell_manager::OPTION_TYPES_PREFIX.$post_type);
			return $loop->href;
		} else {
			return get_permalink( $post_id );
		}
	}

	public function add_select_post_types( )
	{
		$this->layout_id = $this->layout_id ? $this->layout_id : $_GET['layout_id'];
		$lists = $this->list_where_used;

                // count how many pages are assigned
                $count_pages = $this->main->get_where_used_count();


                // Remove item from object if item post type is already assigned to this layout.
				$current = $this->layout_id;
                if(is_object($lists) && property_exists($lists, 'posts')){
                    foreach ($lists->posts as $key=>$post){
                        if ($this->main->post_types_manager->post_type_is_in_layout($post->post_type, $current) === true){
                            unset($lists->posts[$key]);
                        }
                    }
                }
                // now get number of available posts
                $total_count = is_object($lists) && (property_exists($lists, 'posts')) ? count($lists->posts) : 0;
		?>
		<div class="dd-layouts-wrap js-hide-for-private-layout" <?php if($this->is_private_layout === true):?>style="display:none;"<?php endif;?>>
                    <div class="dd-layouts-where-used">
                        <?php include WPDDL_GUI_ABSPATH . 'editor/templates/layout-content-assignment.box.tpl.php'; ?>
                    </div>
		</div><!-- .dd-layouts-wrap -->

		<div class="ddl-dialog hidden layout-content-assignment-dialog js-layout-content-assignment-dialog ddl-change-layout-use-for-post-types-box-wrapper">
		</div>
	<?php
	}

	private function load_assign_dialog( $layout_id ){


		$this->layout_id = $this->layout_id ? $this->layout_id : $layout_id;
		ob_start();
		?>

		<div class="js-selected-post-types-in-layout-div">

			<div class="ddl-dialog-header">
				<h2 class="js-dialog-title"><?php _e('Assign to content', 'ddl-layouts'); ?></h2>
				<i class="fa fa-remove icon-remove js-edit-dialog-close js-remove-video"></i>
			</div>

			<div class="ddl-dialog-content js-ddl-dialog-content">
				<?php
				$html = $this->main->listing_page->print_dialog_checkboxes($this->layout_id, false, '', false);
				echo $html;
				?>
			</div>


			<div class="ddl-dialog-footer js-dialog-footer">
				<div class="dialog-change-use-messages" data-text="<?php echo WPDD_LayoutsListing::$OPTIONS_ALERT_TEXT; ?>"></div>
				<input type="button" class="button js-edit-dialog-close close-change-use"
					   value="<?php _e('Close', 'ddl-layouts'); ?>"
					   data-text-close="<?php _e('Close', 'ddl-layouts') ?>" data-text-cancel="<?php _e('Cancel', 'ddl-layouts') ?>">
			</div>
		</div>
		<?php wp_nonce_field('layout-set-change-post-types-nonce', 'layout-set-change-post-types-nonce'); ?>
		<?php wp_nonce_field('ddl_layout_view_nonce', 'ddl_layout_view_nonce'); ?>

		<?php
		return ob_get_clean();
	}

	public function load_assign_dialog_callback(){
		if( $_POST && wp_verify_nonce( $_POST['load-assign-dialog-nonce'], 'load-assign-dialog-nonce' ) )
		{
			$send = wp_json_encode( array( 'Data' =>  $this->load_assign_dialog( $_POST['layout_id'] ) ) );
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die($send);
	}
}

class WPDDL_EditorDialogs extends Toolset_DialogBoxes{

    public function template(){
        ob_start();
	    WPDD_FileManager::include_files_from_dir( dirname(__FILE__), '/templates/dialogs' );
        wp_nonce_field('ddl_remove_all_layout_association_nonce', 'ddl_remove_all_layout_association_nonce');
        echo ob_get_clean();
    }
}
