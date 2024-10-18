<?php
class WPDDL_CSSEditor{
    private static $instance;

    private function __construct(){
        add_action('wp_ajax_save_layouts_css', array(&$this, 'save_layouts_css'));
        add_action('wp_ajax_save_layouts_js', array(&$this, 'save_layouts_js'));
        add_action( 'admin_head', array( $this, 'add_css_js_help_tab_in_admin_head' ) );
    }

    public static function print_layouts_css()
    {
        global $wpddlayout;
        echo stripslashes( $wpddlayout->get_layout_css() );
    }

    public static function print_layouts_js()
    {
        global $wpddlayout;
        echo  stripslashes($wpddlayout->get_layout_js()) ;
    }

    public function init_gui(){
        add_action( 'admin_enqueue_scripts', array(&$this, 'preload_scripts') );
        add_action( 'admin_enqueue_scripts', array(&$this, 'preload_styles') );
    }

    public function add_css_js_help_tab_in_admin_head(){
            
            $screen = get_current_screen();
            if ( is_null( $screen ) ) {
                return;
            }
            if(isset($screen->id) && ($screen->id === 'toolset_page_dd_layout_CSS_JS' || $screen->id === 'toolset_page_dd_layouts_edit')){

             /*   $screen->add_help_tab(
                    array(
                        'id'		=> 'dd_layout_CSS',
                        'title'		=> __('Layouts CSS', 'ddl-layouts'),
                        'content'	=> '<p>'.__('Need help with CSS styling?', 'ddl-layouts').'&nbsp;<a href="'.WPDDL_CSS_STYLING_LINK.'"target="_blank">'.__('Using HTML and CSS to style layout cells', 'ddl-layouts').' &raquo;</a></p>',
                    )
                ); */
                $screen->add_help_tab(
                    array(
                        'id'		=> 'dd_layout_JS',
                        'title'		=> __('Layouts JS', 'ddl-layouts'),
                        'content'	=> '<p>'.__('Need help with custom Javascript?', 'ddl-layouts').'&nbsp;<a href="'.WPDDL_GLOBAL_JS_LINK.'"target="_blank">'.__('Adding JavaScript Code Globally', 'ddl-layouts').' &raquo;</a></p>',
                    )
                );
            }
    }

    function save_layouts_css(){
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['ddl_css_nonce'], 'ddl_css_nonce')) {

            $css = $this->handle_layout_css( $_POST['css_string'] );

            if( $css ){
                $send = array( 'message' => __( 'The Layouts CSS was saved', 'ddl-layouts') );
            } else {
                $send = array( 'message' => __( 'Nothing to update', 'ddl-layouts') );
            }

        } else {

            $send = array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts'));

        }

        wp_send_json( $send );
    }

    function save_layouts_js(){
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['ddl_js_nonce'], 'ddl_js_nonce')) {

            $css = $this->handle_layout_js( $_POST['js_string'] );

            if( $css ){
                $send = array( 'message' => __( 'The Layouts JS was saved', 'ddl-layouts') );
            } else {
                $send = array( 'message' => __( 'Nothing to update', 'ddl-layouts') );
            }

        } else {

            $send = array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts'));

        }

        wp_send_json( $send );
    }

    private function handle_layout_css( $css )
    {
        global $wpddlayout;
        return $wpddlayout->css_manager->handle_layout_css_save( $css );
    }
    private function handle_layout_js($js){
        global $wpddlayout;
        return $wpddlayout->js_manager->handle_layout_js_save( $js );
    }

    function preload_styles(){
        global $wpddlayout;
        $wpddlayout->enqueue_styles(
            array(
                'toolset-notifications-css',
                'toolset-meta-html-codemirror-css' ,
                'toolset-meta-html-codemirror-css-hint-css',
                'ddl-dialogs-css',
                'ddl-dialogs-forms-css',
                'font-awesome',
                'wp-layouts-pages',
                'layouts-settings-admin-css',
                'layouts-css-admin-css',
                'toolset-common'
            )
        );
    }

    function preload_scripts(){
        global $wpddlayout;

        $wpddlayout->enqueue_scripts(
            array(
                #codemirror
                'toolset-codemirror-script',
                'toolset-meta-html-codemirror-overlay-script',
                'toolset-meta-html-codemirror-xml-script',
                'toolset-meta-html-codemirror-css-script',
                'toolset-meta-html-codemirror-js-script',
                'toolset-meta-html-codemirror-utils-search',
                'toolset-meta-html-codemirror-utils-search-cursor',
                'toolset-meta-html-codemirror-utils-hint',
                'toolset-meta-html-codemirror-utils-hint-css',
                'icl_editor-script',
                'icl_media-manager-js',
                #Controller
                'ddl-js-editor-main',
                'ddl-css-editor-main'
            )
        );

        $wpddlayout->localize_script('ddl-css-editor-main', 'DDLayout_settings', array(
            'DDL_JS' => array(
                'ddl_css_nonce' => wp_create_nonce('ddl_css_nonce'),
                'ddl_js_nonce' => wp_create_nonce('ddl_js_nonce'),
                'CSS_lib_path' => WPDDL_GUI_RELPATH . 'CSS/js/',
                'CSS_style_path' => WPDDL_GUI_RELPATH . 'CSS/css/',
                'res_path' => WPDDL_RES_RELPATH,
                'lib_path' => WPDDL_RES_RELPATH . '/js/external_libraries/',
                'common_rel_path' => WPDDL_TOOLSET_COMMON_RELPATH,
                'is_css_enabled' => $wpddlayout->css_manager->is_css_possible(),
                'is_js_enabled' => $wpddlayout->js_manager->is_js_possible(),
                'current_framework' => $wpddlayout->frameworks_options_manager->get_current_framework(),
                'cred_layout_css_text' => __('Layouts cell styling', 'ddl-layouts'),
                'user_can_delete' => user_can_delete_layouts(),
                'user_can_assign' => user_can_assign_layouts(),
                'user_can_edit' => user_can_edit_layouts(),
                'user_can_create' => user_can_create_layouts(),
                'strings' => $this->get_editor_js_strings(),
                'layouts_css_properties' => WPDDL_CSSEditor::get_all_css_names()
            )
        ));

        $wpddlayout->localize_script('ddl-js-editor-main', 'DDLayout_settings', array(
            'DDL_JS' => array(
                'ddl_css_nonce' => wp_create_nonce('ddl_css_nonce'),
                'ddl_js_nonce' => wp_create_nonce('ddl_js_nonce'),
                'CSS_lib_path' => WPDDL_GUI_RELPATH . 'CSS/js/',
                'CSS_style_path' => WPDDL_GUI_RELPATH . 'CSS/css/',
                'res_path' => WPDDL_RES_RELPATH,
                'lib_path' => WPDDL_RES_RELPATH . '/js/external_libraries/',
                'common_rel_path' => WPDDL_TOOLSET_COMMON_RELPATH,
                'is_css_enabled' => $wpddlayout->css_manager->is_css_possible(),
                'is_js_enabled' => $wpddlayout->js_manager->is_js_possible(),
                'current_framework' => $wpddlayout->frameworks_options_manager->get_current_framework(),
                'cred_layout_css_text' => __('Layouts cell styling', 'ddl-layouts'),
                'user_can_delete' => user_can_delete_layouts(),
                'user_can_assign' => user_can_assign_layouts(),
                'user_can_edit' => user_can_edit_layouts(),
                'user_can_create' => user_can_create_layouts(),
                'strings' => $this->get_editor_js_strings(),
                'layouts_css_properties' => WPDDL_CSSEditor::get_all_css_names()
            )
        ));
    }

    function get_editor_js_strings () {
        return array(
            'save_complete' => __('The layout has been saved.', 'ddl-layouts'),
            'ajax_error' => __('There was an error during the ajax request, make sure the data you send are in json format.', 'ddl-layouts'),
            'save_and_also_save_css' => __('Layouts CSS has been updated.', 'ddl-layouts'),
            'save_and_save_css_problem' => __('Layouts CSS has NOT been updated. Please retry or check write permissions for uploads directory.', 'ddl-layouts'),
            'css_file_loading_problem' => __('It is not possible to handle CSS loading in the front end. You should either make your uploads directory writable by the server, or use pretty permalinks.', 'ddl-layouts'),
            'user_no_caps' => __("You don't have permission to perform this action.", 'ddl-layouts'),
            'id_duplicate' => __("This id is already used in the current layout, please select a unique id for this element", 'ddl-layouts'),
        );
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new WPDDL_CSSEditor();
        }

        return self::$instance;
    }

    function load_template(){
        include_once  WPDDL_GUI_ABSPATH . "CSS/templates/wpddl.CSS-JS-page.tpl.php";
    }

    public static function get_all_css_names(){
        $layouts = WPDD_Utils::get_all_layouts_json_by_status();

	    $css = array();
	    $css['additionalCssClasses'] = array();
	    $css['cssId'] = array();

        if( count($layouts) === 0 ) return $css;

        foreach( $layouts as $layout ){
            $layout = json_decode($layout, true);

            if( isset( $layout['additionalCssClasses'] ) && $layout['additionalCssClasses'] ){
                $classes = explode( ' ', $layout['additionalCssClasses'] );
                if( is_array($classes) ){
                    foreach( $classes as $class ){
                        if( in_array( $class, $css['additionalCssClasses'] ) === false ){
                            if( $class !=="" ) {
	                            $css['additionalCssClasses'][] = $class;
                            }
                        }
                    }
                }
            }

            if( isset($layout['cssId']) && $layout['cssId'] && in_array( $layout['cssId'], $css['cssId'] ) === false ){
                $css['cssId'][] = $layout['cssId'];
            }

            if( isset( $layout['Rows'] ) && is_array($layout['Rows']) ){

                foreach ($layout['Rows'] as $row) {
                    if ($row['additionalCssClasses']) {
                        $classes = explode(' ', $row['additionalCssClasses']);
                        foreach ($classes as $class) {
                            if (in_array($class, $css['additionalCssClasses']) === false) {

	                            if( $class !=="" ) {
		                            $css['additionalCssClasses'][] = $class;
	                            }
                            }
                        }
                    }

                    if (isset($row['cssId']) && $row['cssId'] && in_array($row['cssId'], $css['cssId']) === false) {
                        $css['cssId'][] = $row['cssId'];
                    }

                    if (isset($row['Cells']) && is_array($row['Cells'])) {

                        foreach ($row['Cells'] as $cell) {
                            if (isset($cell['additionalCssClasses']) && $cell['additionalCssClasses'] && is_string($cell['additionalCssClasses'])) {
                                $classes = explode(' ', $cell['additionalCssClasses']);
                                foreach ($classes as $class) {
                                    if (in_array($class, $css['additionalCssClasses']) === false) {

	                                    if( $class !=="" ) {
		                                    $css['additionalCssClasses'][] = $class;
	                                    }

                                    }
                                }
                            }

							// if the cell has a "Rows" property it is a container, loop through them
                            if( isset( $cell['Rows'] ) ){
                                foreach($cell['Rows'] as $grid_of_cell_row){
	                                if ( isset($grid_of_cell_row['additionalCssClasses']) && $grid_of_cell_row['additionalCssClasses'] && is_string( $grid_of_cell_row['additionalCssClasses'] ) ) {
		                                $classes = explode( ' ', $grid_of_cell_row['additionalCssClasses'] );
		                                foreach ( $classes as $class ) {
			                                if ( in_array( $class, $css['additionalCssClasses'] ) === false ) {
				                                if ( $class !== "" ) {
					                                $css['additionalCssClasses'][] = $class;
				                                }
			                                }
		                                }
	                                }
                                }
                            }

                            if (isset($cell['cssId']) && $cell['cssId'] && in_array($cell['cssId'], $css['cssId']) === false) {
                                $css['cssId'][] = $cell['cssId'];
                            }
                        }
                    }
                }
            }

        }

        return $css;
    }
}