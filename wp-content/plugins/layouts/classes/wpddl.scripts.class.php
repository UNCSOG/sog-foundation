<?php

class WPDDL_style extends Toolset_Style
{

	public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $media = 'screen')
	{
		parent::__construct($handle, $path, $deps, $ver, $media);
	}
}

class WPDDL_script extends Toolset_Script
{
	public function __construct($handle, $path = 'wordpress_default', $deps = array(), $ver = false, $in_footer = false)
	{
		parent::__construct( $handle, $path, $deps, $ver, $in_footer);
	}
}

class WPDDL_scripts_manager extends Toolset_Assets_Manager
{

    protected function __construct() {
        parent::__construct();
        add_filter( 'style_loader_tag', array(&$this, 'add_property_layouts_css'), 10, 3 );
    }

    public function add_property_layouts_css( $html, $handle, $href ){
        if( 'wp_ddl_layout_fe_css' === $handle )
            $html = str_replace( "<link", "<link property='stylesheet'", $html );

        return $html;
    }

	protected function initialize_styles()
	{
        #common backend
		$this->styles['wp-layouts-pages'] = new WPDDL_style('wp-layouts-pages', WPDDL_RES_RELPATH . '/css/dd-general.css', null, WPDDL_VERSION );
		$this->styles['progress-bar-css'] = new WPDDL_style('progress-bar-css', WPDDL_RES_RELPATH . '/css/progress.css', null, WPDDL_VERSION );
		$this->styles['toolset-colorbox'] = new WPDDL_style('toolset-colorbox', WPDDL_RES_RELPATH . '/css/colorbox.css', null, WPDDL_VERSION );
		//$this->styles['font-awesome'] = new WPDDL_style('font-awesome', WPDDL_RES_RELPATH . '/css/external_libraries/font-awesome/css/font-awesome.min.css');
		$this->styles['wp-editor-layouts-css'] = new WPDDL_style('wp-editor-layouts-css', WPDDL_GUI_RELPATH . 'editor/css/editor.css', array('wp-jquery-ui-dialog'), WPDDL_VERSION );
		$this->styles['layouts-settings-admin-css'] = new WPDDL_style(  'layouts-settings-admin-css', WPDDL_RES_RELPATH . '/css/ddl-settings.css', null, WPDDL_VERSION );
		$this->styles['layouts-css-admin-css'] = new WPDDL_style(  'layouts-css-admin-css', WPDDL_GUI_RELPATH . 'CSS/css/css-editor-style.css', null, WPDDL_VERSION );

		# dialogs css
		$this->styles['wp-layouts-jquery-ui-slider'] = new WPDDL_style('wp-layouts-jquery-ui-slider', WPDDL_GUI_RELPATH . 'dialogs/css/jquery-ui-slider.css', null, WPDDL_VERSION );

		if (defined('WPV_URL_EMBEDDED')) {
			$this->styles['views-admin-dialogs-css'] = new WPDDL_style('views-admin-dialogs-css', WPV_URL_EMBEDDED . '/res/css/dialogs.css', array( 'wp-jquery-ui-dialog' ), WPV_VERSION);
		}

		# common
		if (defined('WPV_URL_EMBEDDED_FRONTEND')) {
			$this->styles['views-pagination-style'] = new WPDDL_style( 'views-pagination-style', WPV_URL_EMBEDDED_FRONTEND . '/res/css/wpv-pagination.css', array(), WPDDL_VERSION);
		}


		#listing pages

		$this->styles['dd-listing-page-style'] = new WPDDL_style('dd-listing-page-style', WPDDL_RES_RELPATH . '/css/dd-listing-page-style.css', array(), WPDDL_VERSION );

		#FE styles

        $this->styles['ddl-front-end'] = new WPDDL_style('ddl-front-end', WPDDL_RES_RELPATH . "/css/ddl-front-end.css", array( Toolset_Assets_Manager::STYLE_NOTIFICATIONS ), WPDDL_VERSION);
		$this->styles['menu-cells-front-end'] = new WPDDL_style('menu-cells-front-end', WPDDL_RES_RELPATH . "/css/cell-menu-css.css", array(), WPDDL_VERSION);
        $this->styles['ddl-frontend-editor-toolbar'] = new WPDDL_style('ddl-frontend-editor-toolbar', WPDDL_RELPATH . '/inc/gui/frontend-editor/css/toolbar.css',
            array('dashicons'), WPDDL_VERSION );
        $this->styles['ddl-frontend-editor-layout'] = new WPDDL_style('ddl-frontend-editor-layout', WPDDL_RELPATH . '/inc/gui/frontend-editor/css/layout.css',
            null, WPDDL_VERSION );
        $this->styles['ddl-frontend-editor-common'] = new WPDDL_style('ddl-frontend-editor-common', WPDDL_RELPATH . '/inc/gui/frontend-editor/css/common.css',
            null, WPDDL_VERSION );
        $this->styles['ddl-dialogs-overrides'] = new WPDDL_style('ddl-dialogs-overrides', WPDDL_RELPATH . '/inc/gui/frontend-editor/css/dialogs-overrides.css',
            null, WPDDL_VERSION );
        $this->styles['ddl-frontend-editor-wp-core-overrides'] = new WPDDL_style('ddl-frontend-editor-wp-core-overrides', WPDDL_RELPATH . '/inc/gui/frontend-editor/css/wp-core-overrides.css',
            null, WPDDL_VERSION );

		if( isset( $_GET['in-iframe-for-layout'] ) &&
				$_GET['in-iframe-for-layout'] == 1 ){
			$this->styles['ddl-iframe-styles-overrides'] = new WPDDL_style('ddl-iframe-styles-overrides', WPDDL_RES_RELPATH . "/css/ddl-iframe-styles-overrides.css", array('wp-layouts-pages'), WPDDL_VERSION);
			$this->enqueue_styles('ddl-iframe-styles-overrides');
		}

		/** OVERRIDES */
		$this->styles['ddl-forms-overrides'] = new WPDDL_style('ddl-forms-overrides', WPDDL_RELPATH . '/inc/gui/frontend-editor/css/ddl-forms-overrides.css',
			null, WPDDL_VERSION );
        //return parent::__initialize_styles();
    }


	protected function initialize_scripts()
	{
		global $pagenow;

		//dependencies///////
        $this->scripts['layouts-prototypes'] = new WPDDL_script('layouts-prototypes', WPDDL_RES_RELPATH . "/js/external_libraries/prototypes.js", array('underscore', 'backbone'), WPDDL_VERSION, true);
		$this->scripts['ddl_common_scripts'] = new WPDDL_script('ddl_common_scripts', WPDDL_RES_RELPATH . "/js/dd_layouts_common_scripts.js", array('jquery', 'headjs', 'underscore'), WPDDL_VERSION, true);

        $this->scripts['jquery-ui-cell-sortable'] = new WPDDL_script('jquery-ui-cell-sortable', WPDDL_RES_RELPATH . '/js/external_libraries/jquery.ui.cell-sortable.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable'), WPDDL_VERSION, true);
		$this->scripts['jquery-ui-custom-sortable'] = new WPDDL_script('jquery-ui-custom-sortable', WPDDL_RES_RELPATH . '/js/jquery.ui.custom-sortable.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable'), WPDDL_VERSION, true);
        $this->scripts['parents-watcher'] = new WPDDL_script('parents-watcher', WPDDL_RES_RELPATH . '/js/dd-layouts-parents-watcher.js', array('jquery', 'backbone', 'underscore'), WPDDL_VERSION, true );

		//listing//////
		$this->scripts['ddl_create_new_layout'] = new WPDDL_script('ddl_create_new_layout', (WPDDL_RES_RELPATH . "/js/dd_create_new_layout.js"), array('jquery'), WPDDL_VERSION, true);
		$this->localize_script('ddl_create_new_layout', 'DDLayout_settings_create', array(
			'user_can_create' => user_can_create_layouts(),
			'user_can_create_private' => user_can_create_private_layouts(),
			'strings' => array(
				'associate_layout_to_page' => __('To create an association between this Layout and a single page open....', 'ddl-layouts')
			)
		) );

		$this->scripts['wp-layouts-colorbox-script'] = new WPDDL_script('wp-layouts-colorbox-script', WPDDL_RES_RELPATH . '/js/external_libraries/jquery.colorbox.js', array('jquery'), WPDDL_VERSION);


        // TODO: remove this, we are not using this script anymore
        $this->scripts['ddl_post_edit_page'] = new WPDDL_script('ddl_post_edit_page', (WPDDL_RES_RELPATH . "/js/dd_layouts_post_edit_page.js"), array('jquery', 'toolset-utils', 'toolset-event-manager'), WPDDL_VERSION, true);

        $this->scripts['ddl_private_layout'] = new WPDDL_script('ddl_private_layout', (WPDDL_RES_RELPATH . "/js/dd-layouts-private-layout.js"), array('jquery', 'toolset-utils', 'toolset-event-manager'), WPDDL_VERSION, true);

		$this->scripts['wp-layouts-dialogs-script'] = new WPDDL_script('wp-layouts-dialogs-script', WPDDL_GUI_RELPATH . 'dialogs/js/dialogs.js', array('jquery', 'toolset-utils', 'toolset_select2'), WPDDL_VERSION );

		$this->scripts['ddl-post-types'] = new WPDDL_script('ddl-post-types', WPDDL_RES_RELPATH . '/js/ddl-post-types.js', array('jquery', 'layouts-prototypes'), WPDDL_VERSION);


		// media
		$this->scripts['media_uploader_js'] = new WPDDL_script('ddl_media_uploader_js', WPDDL_RES_RELPATH . '/js/ddl-media-uploader.js', array('jquery'), WPDDL_VERSION, true);

		// settings page and scripts
		$this->scripts['ddl-cssframework-settings-script'] = new WPDDL_script('ddl-cssframework-settings-script', WPDDL_RES_RELPATH . '/js/dd_layouts_cssframework_settings.js',array('jquery','underscore'), WPDDL_VERSION, true);

        $this->scripts['ddl-wpml-switcher'] = new WPDDL_script('ddl-wpml-switcher', WPDDL_RES_RELPATH . '/js/ddl-wpml-switcher.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-selectmenu', 'toolset_select2'), WPDDL_VERSION, true);
		$this->scripts['layouts-settings-admin-js'] = new WPDDL_script( 'layouts-settings-admin-js',  WPDDL_RES_RELPATH . '/js/ddl-settings.js', array( 'jquery', 'toolset-utils', 'toolset_select2' ), WPDDL_VERSION, true );
		$this->scripts['ddl-css-editor-main'] = new WPDDL_script('ddl-css-editor-main', WPDDL_GUI_RELPATH . "CSS/js/main.js", array('headjs', 'jquery', 'toolset-utils', 'underscore', 'backbone'), WPDDL_VERSION, true);

	$this->scripts['ddl-js-editor-main-abstract'] = new WPDDL_script('ddl-js-editor-main-abstract', WPDDL_GUI_RELPATH . "editor/js/ddl-layouts-editor-abstract.js", array('headjs', 'jquery', 'toolset-utils', 'underscore', 'backbone','jquery-ui-tabs'), WPDDL_VERSION, true);

        $this->scripts['ddl-js-editor-main'] = new WPDDL_script('ddl-js-editor-main', WPDDL_GUI_RELPATH . "CSS/js/jsmain.js", array('headjs', 'jquery', 'toolset-utils', 'underscore', 'backbone','jquery-ui-tabs'), WPDDL_VERSION, true);
        #codemirror.js and related

        $backend_editor = isset( $_GET['page'] ) && 'dd_layouts_edit' == $_GET['page'];
        $frontend_editor = isset($_GET['toolset_editor']) && user_can_edit_private_layouts();

        if($backend_editor || $frontend_editor){
            $this->scripts['icl_media-manager-js'] = new WPDDL_script('icl_media-manager-js',
				WPDDL_TOOLSET_COMMON_RELPATH . '/visual-editor/res/js/icl_media_manager.js',
				array('toolset-codemirror-script'), '1.2');
			#editor
			$this->scripts['ddl-editor-main'] = new WPDDL_script('ddl-editor-main', (WPDDL_GUI_RELPATH . "editor/js/main.js"), array('headjs', 'jquery', 'backbone', 'toolset-utils','jquery-ui-tabs','icl_media-manager-js', 'jquery-effects-core', 'jquery-effects-highlight', 'jquery-effects-pulsate', 'jquery-ui-dialog', 'toolset-event-manager', 'ddl-js-editor-main-abstract'), null, true);

			$this->scripts['ddl-sanitize-html'] = new WPDDL_script('ddl-sanitize-html', WPDDL_RES_RELPATH . '/js/external_libraries/sanitize/sanitize.js', array(), WPDDL_VERSION );
			$this->scripts['ddl-sanitize-helper'] = new WPDDL_script('ddl-sanitize-helper', WPDDL_GUI_RELPATH . 'editor/js/ddl-sanitize-helper.js', array('underscore', 'ddl-sanitize-html', 'jquery'), WPDDL_VERSION );
		}
		// listing
		if( isset($_GET['page']) && $_GET['page'] === WPDDL_LAYOUTS_POST_TYPE )
		{
			$this->scripts['dd-listing-page-main'] = new WPDDL_script('dd-listing-page-main', (WPDDL_GUI_RELPATH . "listing/js/main.js"), array('headjs', 'jquery', 'backbone', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-dialog', 'jquery-ui-tooltip', 'jquery', 'jquery-ui-tabs', 'toolset-event-manager', 'toolset-utils'), WPDDL_VERSION, true);
		}

		// Common scripts
		if (defined('WPV_URL_EMBEDDED')) {
			// TODO this is also not useful as our localize_script method seems to require the script to be registered and enqueued
			// The native wp_localize_script function only requires it to be registered
			// We might want to change our method for consistency, in which case this will actually add the localization
			$this->localize_script(
				'toolset-utils',
				'wpv_help_box_texts',
				array(
					'wpv_dont_show_it_again' => __("Got it! Don't show this message again", 'ddl-layouts'),
					'wpv_close' => __("Close", 'ddl-layouts')
			));
		}

		// Front End Scripts
		$this->scripts['ddl-layouts-frontend'] = new WPDDL_script('ddl-layouts-frontend', WPDDL_RES_RELPATH . '/js/ddl-layouts-frontend.js', array('jquery'), WPDDL_VERSION);
		$this->scripts['ddl-layouts-toolset-support'] = new WPDDL_script('ddl-layouts-toolset-support', WPDDL_RES_RELPATH . '/js/ddl-layouts-toolset-support.js', array('jquery', 'suggest'), WPDDL_VERSION);

		$this->localize_script( 'ddl-layouts-toolset-support', 'TOOLSET_IN_IFRAME_SETTINGS', array(
			'layouts_css_properties' => class_exists( 'WPDDL_CSSEditor' ) ? WPDDL_CSSEditor::get_all_css_names() : ''
		) );

		// Views support
		if( isset( $_GET['in-iframe-for-layout']) && $_GET['in-iframe-for-layout'] == 1 &&
			(isset( $_GET['page'] ) && (('views-editor' == $_GET['page']) ||
										('views-embedded' == $_GET['page']) ||
										('view-archives-embedded' == $_GET['page']) ||
										('view-archives-editor' == $_GET['page']) ))) {
			$this->scripts['ddl-layouts-views-support'] = new WPDDL_script('ddl-layouts-views-support', WPDDL_RES_RELPATH . '/js/dd-layouts-views-support.js', array('jquery', 'suggest', 'ddl-layouts-toolset-support', 'toolset-chosen-wrapper' ), WPDDL_VERSION);
		}

        $this->scripts['ddl-frontend-editor-main'] = new WPDDL_script('ddl-frontend-editor-main', WPDDL_RELPATH . '/inc/gui/frontend-editor/js/main.js', array(
            'headjs',
            'ddl_common_scripts',
            'jquery',
            'underscore',
            'backbone',
            'toolset-utils',
            'toolset-event-manager',
            'jquery-ui-tabs',
            'wp-pointer',
            'toolset_select2',
            'wp-mediaelement',
            'ddl-sanitize-html',
            'ddl-sanitize-helper',
            'ddl-post-types',
            //'ddl-editor-main',
            'ddl_media_uploader_js',
            'icl_media-manager-js',
            'wp-layouts-dialogs-script',
            'toolset-colorbox',
	        'ddl-js-editor-main-abstract',
	        'toolset-chosen-wrapper'
        ), WPDDL_VERSION, true);


		// CRED support
		if (isset( $_GET['in-iframe-for-layout']) &&
					$_GET['in-iframe-for-layout'] == 1 &&
					defined('CRED_FORMS_CUSTOM_POST_NAME') &&
					$pagenow == 'post.php' &&
					isset($_GET['post'])) {

			$post_id = $_GET['post'];
			$post = get_post($post_id);
			if ($post->post_type == CRED_FORMS_CUSTOM_POST_NAME) {
				$this->scripts['ddl-layouts-cred-support'] = new WPDDL_script('ddl-layouts-cred-support', WPDDL_RES_RELPATH . '/js/dd-layouts-cred-support.js', array('jquery', 'ddl-layouts-toolset-support', 'toolset-chosen-wrapper'), WPDDL_VERSION);
			}
		}

        // CRED support
        if (isset( $_GET['in-iframe-for-layout']) &&
            $_GET['in-iframe-for-layout'] == 1 &&
            defined('CRED_USER_FORMS_CUSTOM_POST_NAME') &&
            $pagenow == 'post.php' &&
            isset($_GET['post'])) {

            $post_id = $_GET['post'];
            $post = get_post($post_id);
            if ($post->post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) {
                $this->scripts['ddl-layouts-cred-user-support'] = new WPDDL_script('ddl-layouts-cred-user-support', WPDDL_RES_RELPATH . '/js/dd-layouts-cred-user-support.js', array('jquery', 'ddl-layouts-toolset-support', 'toolset-chosen-wrapper' ), WPDDL_VERSION);
            }
        }

	        // CRED support
	        if (isset( $_GET['in-iframe-for-layout']) &&
	            $_GET['in-iframe-for-layout'] == 1 &&
	            class_exists('CRED_Association_Form_Main') &&
	            $pagenow == 'admin.php' &&
	            isset( $_GET['page'] ) && $_GET['page'] === 'cred_relationship_form' ) {

		        $post_id = $_GET['id'];
		        $post    = get_post( $post_id );
		        if ( $post->post_type == CRED_Relationship_Cell::FORM_POST_TYPE ) {
			        $this->scripts['ddl-layouts-cred-relationship-support'] = new WPDDL_script( 'ddl-layouts-cred-relationship-support', WPDDL_RES_RELPATH . '/js/dd-layouts-cred-relationship-support.js', array(
				        'jquery',
				        'ddl-layouts-toolset-support',
				        'toolset-chosen-wrapper'
			        ), WPDDL_VERSION );
		        }
	        }


        # import export
        if( isset($_GET['page']) && $_GET['page'] === 'toolset-export-import' )
        {

            $this->scripts['dd-layout-theme-import-export'] = new WPDDL_script('dd-layout-theme-import-export', WPDDL_RES_RELPATH . '/js/ddl-import-export-script.js', array( 'jquery', 'wp-pointer'), WPDDL_VERSION, true);
            $this->localize_script(
				'dd-layout-theme-import-export',
				'ddl_import_texts',
				array(
					'start_import' => __("Import started", 'ddl-layouts'),
					'upload_another_file' => __("Upload another file", 'ddl-layouts'),
                    'incorrect_answer' => __("Incorrect answer from server", 'ddl-layouts'),
                    'working_with' => __("Working with file {1} of {2}", 'ddl-layouts'),
                    'working_with_fail' => __("File {1} timeout", 'ddl-layouts'),
                    'saved_layouts' => __("Saved Layouts", 'ddl-layouts'),
                    'deleted_layouts' => __("Deleted Layouts", 'ddl-layouts'),
                    'saved_css' => __("Saved CSS", 'ddl-layouts'),
                    'saved_js' => __("Saved JS", 'ddl-layouts'),
					'saved_json' => __("Settings saved", 'ddl-layouts'),
                    'overwritten_layouts' => __("Overwritten Layouts", 'ddl-layouts'),
                    'server_timeout' => __("Server timeout, please try again later.", 'ddl-layouts'),
                    'import_finished' => __("Import finished", 'ddl-layouts'),
                    'in_queue' => __("in queue", 'ddl-layouts'),
                    'error_timeout' => __("Error, timeout", 'ddl-layouts'),
                    'skipped_layouts' => __("Skipped Layouts", 'ddl-layouts'),
                    'ok' => __("Ok", 'ddl-layouts'),
			));
        }

        #post edit page
    	$this->scripts['ddl-create-for-pages'] = new WPDDL_script('ddl-create-for-pages', WPDDL_RES_RELPATH . '/js/dd-layouts-create-for-single-pages.js', array('jquery',  'toolset-utils', /*'layouts-prototypes',*/ 'wp-layouts-dialogs-script', 'wp-layouts-colorbox-script'), WPDDL_VERSION, true);
		$this->scripts['ddl-comment-cell-front-end'] = new WPDDL_script('ddl-comment-cell-front-end', WPDDL_RES_RELPATH . '/js/ddl-comment-cell-front-end.js', array('jquery', 'toolset-utils', 'comment-reply'), WPDDL_VERSION, true);
        $this->scripts['ddl-post-editor-overrides'] = new WPDDL_script('ddl-post-editor-overrides', WPDDL_RES_RELPATH . '/js/ddl-post-editor-overrides.js', array('jquery', 'toolset-utils', 'jstorage', /*'layouts-prototypes',*/ 'wp-layouts-dialogs-script', 'wp-layouts-colorbox-script', 'toolset_select2'), WPDDL_VERSION, true);
        $this->scripts['ddl-menu-cell-front-end'] = new WPDDL_script('ddl-menu-cell-front-end', WPDDL_RES_RELPATH . '/js/ddl-menu-cell-front-end.js', array('jquery', 'toolset-utils'), WPDDL_VERSION, true);
        $this->scripts['ddl-menu-cell-front-end-bs3-dropdown-fallback'] = new WPDDL_script('ddl-menu-cell-front-end-bs3-dropdown-fallback', WPDDL_RES_RELPATH . '/js/external_libraries/dropdown.js', array('jquery'), WPDDL_VERSION, true);
        $this->scripts['ddl-video-cell-front-end'] = new WPDDL_script('ddl-video-cell-front-end', WPDDL_RES_RELPATH . '/js/ddl-video-cell-front-end.js', array('jquery'), WPDDL_VERSION, true);

        #embedded mode only
        $this->scripts['ddl-embedded-mode'] = new WPDDL_script('ddl-embedded-mode', WPDDL_RES_RELPATH . '/js/dd-layouts-embedded.js', array('jquery',  'toolset-utils', 'layouts-prototypes', 'wp-layouts-dialogs-script', 'wp-layouts-colorbox-script', 'toolset_select2' ), WPDDL_VERSION, true);

        //return parent::__initialize_scripts();
    }
}
