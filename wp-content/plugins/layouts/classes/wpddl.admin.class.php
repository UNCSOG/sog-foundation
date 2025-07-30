<?php
class WPDDL_Admin{
    protected $layouts_editor_page = false;
    protected $layouts_settings = null;
    protected static $instance = null;
    protected $page;

    function __construct(){
        if( is_admin() ){
            $this->page = toolset_getget( 'page' );
            // common things to run upon init here
            if ( $this->page == WPDDL_LAYOUTS_POST_TYPE || $this->page == 'dd_layouts_edit'  ){
                do_action('ddl-wpml-switcher-scripts');
            } elseif ( $this->page == 'dd_layout_CSS_JS' ){
                add_action('admin_init', array(&$this, 'init_layouts_css_gui') );
            } elseif  ( $this->page == 'toolset-export-import' ) {
                add_action('admin_enqueue_scripts', array($this, 'import_export_enqueue_script'));
            }
            add_filter( 'toolset_filter_register_export_import_section', array(&$this,'add_layouts_import_export'), 30, 1 );
            add_filter( 'toolset_filter_toolset_register_settings_section', array(&$this, 'register_layouts_settings'), 32, 1 );
            add_filter( 'toolset_filter_toolset_register_settings_layouts_section',	array( $this, 'register_layouts_settings_items' ), 10, 2 );
            add_action( 'admin_head', array( $this, 'add_layouts_videos_help_tab_in_admin_head' ) );
            $this->init_layouts_css();
        }
        add_filter( 'toolset_filter_register_menu_pages', array(&$this, 'register_custom_pages_in_menu'), 51, 1 );
        add_action('wp_ajax_ddl_remove_layouts_loop_pagination_links', array($this,'remove_layouts_loop_pagination_links'));
    }

    function import_export_enqueue_script()
    {
        global $wpddlayout;
        $wpddlayout->enqueue_scripts('dd-layout-theme-import-export');

        $wpddlayout->localize_script('dd-layout-theme-import-export', 'DDLayout_settings', array(
            'DDL_JS' => array(
                'no_file_selected' => __('No file selected. Please select one file to import Layouts data from.', 'ddl-layouts'),
                'file_to_big' => __('File is bigger than maximum allowed in your php configuration.', 'ddl-layouts'),
                'file_type_wrong' => __('Only .zip, .ddl, .json and .css files can be imported.', 'ddl-layouts')
            )
        ));
    }

    public function register_layouts_settings( $sections ){
        $sections['layouts'] = array(
            'slug' => 'layouts',
            'title' => __('Layouts', 'ddl-layouts'),
            //'icon'      => '<i class="icon-layouts-logo ont-icon-16 ont-color-black"></i>',
        );
        return $sections;
    }

    public function register_layouts_settings_items( $sections, $settings ){

        $this->layouts_settings = WPDDL_Settings::getInstance();
        $this->layouts_settings->init();

       /* $sections['admin-bar-menu'] = array(
            'slug'		=> 'admin-bar-menu',
            'title'		=> __( 'Toolset Admin Bar Menu', 'ddl-layouts' ),
            'callback' => array($this->layouts_settings, 'ddl_show_hidden_toolset_admin_bar_menu' )
        );*/

        $sections['max-query-size'] = array(
            'slug'		=> 'max-query-size',
            'title'		=> __( 'Limit for number of pages to refresh after saving a layout', 'ddl-layouts' ),
            'callback' => array($this->layouts_settings, 'ddl_set_max_query_size' )
        );

        $sections['toolset-templates-settings'] = array(
            'slug'		=> 'toolset-templates-settings',
            'title'		=> __( 'What to display if no layout is assigned to content', 'ddl-layouts' ),
            'callback' => array(WPDDL_Templates_Settings::getInstance(), 'gui' )
        );

        $sections['toolset-parent-settings'] = array(
            'slug'		=> 'toolset-parent-settings',
            'title'		=> __( 'Default parent layout', 'ddl-layouts' ),
            'callback' => array($this->layouts_settings, 'default_parent_gui' )
        );

	    $sections['toolset-bootstrap-column-width'] = array(
		    'slug'		=> 'toolset-bootstrap-column-width',
		    'title'		=> __( 'Default Bootstrap Column Width', 'ddl-layouts' ),
		    'callback' => array($this->layouts_settings, 'default_column_gui' )
	    );

        $sections['toolset-show-cell-details'] = array(
            'slug'		=> 'toolset-show-cell-details',
            'title'		=> __( 'Show cell details', 'ddl-layouts' ),
            'callback' => array($this->layouts_settings, 'ddl_show_layout_cell_details' )
        );

	    $sections['toolset-css-js-settings'] = array(
		    'slug'		=> 'toolset-css-js-settings',
		    'title'		=> __( 'CSS and JS settings', 'ddl-layouts' ),
		    'callback' => array($this->layouts_settings, 'ddl_show_layout_scripts_options_gui' )
	    );

        return apply_filters( 'ddl-get_layouts_settings_sections_array', $sections, $this->layouts_settings );
    }

    function add_layouts_videos_help_tab_in_admin_head() {
        $screen = get_current_screen();

        if ( is_null( $screen ) ) {
            return;
        }

        $allowed_screens = array(
            'toolset_page_dd_layouts',
            'toolset_page_dd_layouts_edit',
            'toolset_page_dd_layout_CSS_JS'
        );

        if ( isset( $screen->id ) && in_array( $screen->id, $allowed_screens ) ) {

            $screen->add_help_tab(
                array(
                    'id'      => 'dd_layout_video_tutorials',
                    'title'   => __( 'Video Tutorials', 'ddl-layouts' ),
                    'content' => sprintf( __( '%s Need help? Learn with the %svideo tutorials%s %s' ), '<p>', '<a href="' . admin_url() . 'admin.php?page=toolset_video_tutorials&toolset_help_video=layouts_template" target="_blank">', '</a>', '</p>' ),
                )
            );

        }
    }


    function register_custom_pages_in_menu( $pages ){


        $pages[] = array(
                'slug'                      => WPDDL_LAYOUTS_POST_TYPE,
                'menu_title'                => __('Layouts', 'ddl-layouts'),
                'page_title'                => __('Layouts', 'ddl-layouts'),
                'callback'                  => array($this, 'dd_layouts_list'),
                'capability'                => DDL_CREATE
            );

        if( $this->page === 'dd_layouts_edit' ){

            $cap = $this->change_cap_for_content_layout( $_GET['layout_id'] );

            $pages[] = array(
                'slug'                      => 'dd_layouts_edit',
                'menu_title'                => __('Edit layout', 'ddl-layouts'),
                'page_title'                => __('Edit layout', 'ddl-layouts'),
                'callback'                  => array($this, 'dd_layouts_edit'),
                'capability'                => $cap
            );

        }


            $pages[] = array(
                'slug' => 'dd_layout_CSS_JS',
                'menu_title' => __('Layouts CSS and JS', 'ddl-layouts'),
                'page_title' => __('Layouts CSS and JS', 'ddl-layouts'),
                'callback' => array($this, 'dd_layout_CSS_JS'),
                'capability' => DDL_EDIT
            );

            return $pages;
    }

    private function change_cap_for_content_layout( $layout_id ){

        if( ! $layout_id || ! WPDD_Utils::is_private( $layout_id ) ){
            return DDL_EDIT;
        }

        return DDL_EDIT_PRIVATE;
    }

    private function init_layouts_css(){
        include WPDDL_GUI_ABSPATH . 'CSS/wpddl.css-js-editor.class.php';
        WPDDL_CSSEditor::getInstance();
    }

    function init_layouts_css_gui(){
        WPDDL_CSSEditor::getInstance()->init_gui();
    }

    protected function is_layouts_admin_page(){
        return $this->page === 'dd_layouts_edit'
        || 'dd_layouts' === $this->page
        || $this->page === 'toolset-export-import'
        || $this->page === 'toolset-settings'
        || $this->page === 'dd_layout_CSS_JS';
    }

    public function create_layout_button()
    {
        if( user_can_create_layouts() ):
            ?>
            <a href="#" class="add-new-h2 js-layout-add-new-top"><?php _e('Add New', 'ddl-layouts');?></a>
            <?php

        else: ?>
            <button disabled class="add-new-disabled"><?php _e('Add New', 'ddl-layouts');?></button>
            <?php
        endif;

        do_action('ddl-add-gui-buttons-in-listing-page-top');
    }

    protected function add_tutorial_video()
    {
        return array('dd_tutorial_videos' => array(
            'title' => __('Help', 'ddl-layouts'),
            'function' => array($this, 'dd_layouts_help'),
            'subpages' => array(
                'dd_layouts_debug' => array(
                    'title' => __('Debug information', 'ddl-layouts'),
                    'function' => array(__CLASS__, 'dd_layouts_debug')
                ),
            ),
        ),);
    }

    protected function add_troubleshoot_menu()
    {
        if( 'dd_layouts_troubleshoot' == $this->page ){
            return array('dd_layouts_troubleshoot' => array(
                'title' => __('Troubleshoot', 'ddl-layouts'),
                'function' => array(__CLASS__, 'dd_layouts_troubleshoot'),
            ));
        }
        return array();
    }

    function admin_init()
    {
        
        
        if ( 'dd_layouts_edit' == $this->page ) {
            if (isset($_GET['layout_id']) and $_GET['layout_id'] > 0) {
                $this->layouts_editor_page = true;
            }
        }
    }


    function dd_layouts_help(){
        include WPDDL_GUI_ABSPATH . 'templates/layout_help.tpl.php';
        include WPDDL_GUI_ABSPATH . 'dialogs/dialog_video_player.tpl.php';
    }

    function dd_layouts_list()
    {
        global $wpddlayout;
        $wpddlayout->listing_page->init();
    }

    function dd_layouts_edit()
    {
        global $wpddlayout;
        $wpddlayout->dd_layouts_edit();
    }

    function dd_layouts_theme_export(){
        include WPDDL_SUPPORT_THEME_PATH . 'templates/layout_theme_export.tpl.php';
    }

    function dd_layouts_theme_import(){
        include WPDDL_SUPPORT_THEME_PATH . 'templates/layout_theme_import.tpl.php';
    }


    function add_layouts_import_export( $sections ){
        $sections['dd_layout_import_export'] = array(
            'slug'      => 'dd_layout_import_export',
            'title'     => __('Layouts', 'ddl-layouts'),
            'icon'      => '<i class="icon-layouts-logo ont-icon-16 ont-color-black"></i>',
            'items'     => array(
                'export'    => array(
                    'slug'   => 'dd_layout_theme_export',
                    'title'  => __('Layouts export', 'ddl-layouts'),
                    'callback' => array(&$this, 'dd_layouts_theme_export')
                ),
                'import'    => array(
                    'slug'   => 'dd_layout_theme_export',
                    'title'  => __('Layouts import', 'ddl-layouts'),
                    'callback' => array(&$this, 'dd_layouts_theme_import')
                )
            )
        );
        return $sections;
    }

    public function dd_layout_CSS_JS(){
        WPDDL_CSSEditor::getInstance()->load_template();
    }

    /**
     * debug page render hook.
     */
    public static function dd_layouts_debug()
    {
        include_once WPDDL_TOOLSET_COMMON_ABSPATH . DIRECTORY_SEPARATOR.'debug/debug-information.php';
    }
    /**
     * troubleshoot page render hook
     */
    public static function dd_layouts_troubleshoot()
    {
        include WPDDL_GUI_ABSPATH . 'templates/layout_troubleshoot.tpl.php';
    }

    function remove_layouts_loop_pagination_links()
    {
        if( user_can_create_layouts() === false ){
            $data = array(
                'type' => 'capability',
                'message' => __( 'You do not have permissions for that.', 'ddl-layouts' )
            );
            wp_send_json_error($data);
        }
        if(	!isset($_POST["wpnonce"]) || !wp_verify_nonce($_POST["wpnonce"], 'ddl_remove_layouts_loop_pagination_links') ){
            $data = array(
                'type' => 'nonce',
                'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'ddl-layouts' )
            );
            wp_send_json_error($data);
        }
        if( function_exists('wpv_check_views_exists') ){
            $ddl_archive_loop_ids = wpv_check_views_exists( 'layouts-loop' );
            if( $ddl_archive_loop_ids ){
                $ddl_archive_loop_ids = array_map('esc_attr', $ddl_archive_loop_ids);
                $ddl_archive_loop_ids = array_map('trim', $ddl_archive_loop_ids);
                $ddl_archive_loop_ids = array_filter($ddl_archive_loop_ids, 'is_numeric');
                $ddl_archive_loop_ids = array_map('intval', $ddl_archive_loop_ids);
                if( count($ddl_archive_loop_ids) ){
                    global $wpdb;
                    $final_post_content = "[wpv-filter-meta-html]\n[wpv-layout-meta-html]";
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->posts}
							SET post_content = %s
							WHERE ID IN ('" . implode("','", $ddl_archive_loop_ids) . "')",
                            $final_post_content
                        )
                    );
                }
            }
            $data = array(
                'message' => __( 'Pagination links deleted.', 'ddl-layouts' )
            );
            wp_send_json_success( $data );
        } else {
            $data = array(
                'type' => 'missing',
                'message' => __( 'You need Views to perform this action.', 'ddl-layouts' )
            );
            wp_send_json_error($data);
        }
    }


    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_Admin();
        }

        return self::$instance;
    }

}