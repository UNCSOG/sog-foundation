<?php

/**
 * Abstract parent for the singleton that handles the final setup of Layouts and the integrated theme.
 * This is used by Toolset Starter Theme to integrate transparently with Layouts
 */
abstract class WPDDL_Theme_Integration_Setup_Abstract {

    protected $page_template_default = 'template-page.php';
    protected $layouts_path;
    protected $message = null;
    /**
     * Singleton parent.
     *
     * @link http://stackoverflow.com/questions/3126130/extending-singletons-in-php
     * @return WPDDL_Theme_Integration_Abstract Instance of calling class.
     */
    final public static function get_instance() {
        static $instances = array();
        $called_class = get_called_class();
        if( !isset( $instances[ $called_class ] ) ) {
            $instances[ $called_class ] = new $called_class();
        }
        return $instances[ $called_class ];
    }

    protected function __construct(){

    }

    protected function __clone() { }


    /**
     * Run the integration.
     *
     * @return bool|WP_Error True when the integration was successful or a WP_Error with a sensible message
     *     (which can be displayed to the user directly).
     */
    public function run() {
        $this->handle_default_layouts_loader();
        $this->hook_enqueue_scripts();
        $this->add_layouts_support();
        $this->tell_layouts_about_theme();
        $this->add_layout_row_types();
        $this->add_layouts_cells();
        $this->modify_theme_settings();
	    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_layouts_loader_js' ) );
        add_action( 'wp_ajax_ddl_load_default_layouts', array(&$this, 'ddl_load_default_layouts') );
        add_filter( 'toolset_filter_force_unset_shortcode_generator_option', array(&$this, 'force_unset_shortcode_generator_option_to_disable' ) );
        return true;
    }

    function force_unset_shortcode_generator_option_to_disable( $state ) {
        if ( $state == 'unset' ) {
            $state = 'editor';
        }
        return $state;
    }

    /**
     * Hook into actions for enqueuing all required assets.
     *
     * This method is meant to be overriden to extend or disable this functionality.
     */
    protected function hook_enqueue_scripts() {
        $this->hook_enqueue_backend_scripts();
        $this->hook_enqueue_frontend_scripts();
    }


    /**
     * Enqueue assets needed on the backend.
     */
    protected function hook_enqueue_backend_scripts() {

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 1 );

	    if( isset( $_GET['toolset_editor'] ) ){
		    add_action( 'wp_enqueue_scripts', array( $this, 'admin_enqueue' ), 1 );
	    }
    }

	/**
	 * Check is it necessary to load bootstrap again, in some cases it is loaded already from common
	 * @return bool
	 */
    protected function bootstrap_already_loaded(){
    	if( class_exists( 'Toolset_Settings' ) ){
		    $settings = Toolset_Settings::get_instance();
		    if( in_array($settings->toolset_bootstrap_version, array('3.toolset', '99')) ){
			    return true;
		    }
	    }
	    return false;
    }

    /**
     * Enqueue assets usually required on the frontend.
     */
    protected function hook_enqueue_frontend_scripts() {
        // make sure bootstrap is loaded before any theme css
	    if ( ! $this->bootstrap_already_loaded() ){
		    add_action( 'wp_enqueue_scripts', array( $this, 'add_bootstrap_support' ), -10 );
	    }
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue' ) );
    }


    /**
     * Configure Layouts to support the integrated theme.
     *
     * Basic setup common for all integration plugins.
     */
    protected function add_layouts_support() {

        // Put this cell category first
        add_filter( 'ddl-get_cell_categories', array( $this, 'override_cell_categories_order' ), 99, 1 );

        // Setup related to template files. See individual hooks for better descriptions.
        // Based on explanation provided by Riccardo: https://www.evernote.com/l/AR76enDk_cxAw7hQpLU1THS4AZeYH7rOU58
        add_filter( 'ddl_no_templates_at_all', array( &$this, 'override_templates_exist' ) );
        add_filter( 'ddl_templates_have_layout', array( $this, 'activate_layouts_select_menu_for_posts' ) );
        add_filter( 'ddl_check_layout_template_page_exists', array( &$this, 'override_compatible_templates_exists_for_post_type' ), 10, 2 );
        add_filter( 'ddl-theme_has_page_templates', array( &$this, 'override_compatible_template_exists_for_pages' ), 10, 1 );
        add_filter( 'ddl-determine_main_template', array( &$this, 'override_default_template' ), 10, 3 );
        add_filter( 'ddl_template_have_layout', array( &$this, 'set_if_template_is_layouts_compatible' ), 10, 2 );
        add_filter('ddl-page_templates_have_layout', array( $this, 'override_compatible_template_exists_for_pages' ), 10, 1 );
    }


    /**
     * Add custom cells to Layouts.
     */
    protected function add_layouts_cells() { }

    /**
     * Add custom row modes to Layouts.
     */
    protected function add_layout_row_types() { }


    /**
     * This method can be used to remove all theme settings which are obsolete with the use of Layouts
     * i.e. "Default Layout" in "Theme Settings"
     */
    protected function modify_theme_settings() { }


    /**
     * @return string Version of the supported theme.
     */
    protected abstract function get_supported_theme_version();


    /**
     * Build URL of an resource from path relative to plugin's root directory.
     *
     * This needs to be overridden because Layouts doesn't know the location of the integration plugin.
     *
     * @param string $relative_path Some path relative to the plugin's root directory.
     * @return string URL of the given path.
     */
    protected abstract function get_plugins_url( $relative_path );


    /**
     * @return string Path of CSS file that will be included on the frontend or an empty string if no such file is needed.
     * The path needs to be relative to the integration plugin root directory.
     */
    protected function get_custom_frontend_css_path() {
        return 'public/css/custom-frontend.css';
    }


    /**
     * @return string Path of CSS file that will be included on the backend or an empty string if no such file is needed.
     * The path needs to be relative to the integration plugin root directory.
     */
    protected function get_custom_backend_css_path() {
        return 'public/css/custom-backend.css';
    }


    /**
     * @return string Path of JS file that will be included on the backend or an empty string if no such file is needed.
     * The path needs to be relative to the integration plugin root directory.
     */
    protected function get_custom_backend_js_path() {
        return 'public/js/custom-backend.js';
    }


    /**
     * Load Bootstrap script and style from Layouts resources.
     */
    public function add_bootstrap_support() {

        $bootstrap_url = WPDDL_RELPATH . '/extra/theme_integration_support/resources/js/lib/bootstrap/dist';

        $bootstrap_css_url = $bootstrap_url . '/css/bootstrap.min.css';
		wp_register_style( 'bootstrap', $bootstrap_css_url, array(), '3.3.5' );

		$bootstrap_js_url = $bootstrap_url . '/js/bootstrap.js';
        wp_register_script( 'bootstrap', $bootstrap_js_url, array( 'jquery', 'jquery-migrate' ), '3.3.5', true );

        if ( is_ddlayout_assigned() ) {
			wp_enqueue_style( 'bootstrap' );
			wp_enqueue_script( 'bootstrap' );
        }
    }


    /**
     * Enqueue frontend assets.
     *
     * If get_custom_frontend_css_path() returns a path, that file will be enqueued.
     */
    public function frontend_enqueue() {
        $custom_css_relpath = $this->get_custom_frontend_css_path();

        if( !empty( $custom_css_relpath ) && is_ddlayout_assigned() ) {
            wp_register_style(
                'layouts-theme-integration-frontend',
                $this->get_plugins_url( $custom_css_relpath ),
                array(),
                $this->get_supported_theme_version()
            );

            wp_enqueue_style( 'layouts-theme-integration-frontend' );
        }
    }


    /**
     * Enqueue backend assets.
     *
     * - Custom CSS and JS files
     * - Post edit page overrides
     */
    public function admin_enqueue() {
        $custom_css_relpath = $this->get_custom_backend_css_path();

        if( !empty( $custom_css_relpath ) ) {
            wp_register_style(
                'layouts-theme-integration-backend',
                $this->get_plugins_url( $custom_css_relpath ),
                array(),
                $this->get_supported_theme_version()
            );

            wp_enqueue_style( 'layouts-theme-integration-backend' );
        }

        $this->enqueue_custom_backend_js();
        $this->enqueue_post_edit_page_overrides_js();
    }


    /**
     * Enqueue custom JS file on backend if get_custom_backend_js_path() returns a path.
     */
    protected function enqueue_custom_backend_js() {

        $custom_js_relpath = $this->get_custom_backend_js_path();

        if( !empty( $custom_js_relpath ) ) {
            wp_register_script(
                'layouts-theme-integration-backend',
                $this->get_plugins_url( $custom_js_relpath ),
                array( 'jquery', 'underscore' ),
                $this->get_supported_theme_version(),
                false
            );

        }
    }


    /**
     * Get list of templates supported by Layouts with this theme.
     *
     * @return array Associative array with template file names as keys and theme names as values.
     */
    protected function get_supported_templates() {
        return array();
    }


    /**
     * Layouts that the active theme supports Layouts.
     */
    private function tell_layouts_about_theme() {
        $theme = wp_get_theme();
        $options_manager = new WPDDL_Options_Manager( 'ddl_template_check' );
        $option_name = 'theme-' . $theme->get('Name');
        if( ! $options_manager->get_options( $option_name ) ) {
            $options_manager->update_options( $option_name, 1 );
        }
    }


    /**
     * If there is a category of Layouts cells for the integrated theme, put it on the last place in
     * the Add cell dialog.
     *
     * @param $categories
     * @return array
     */
    public function override_cell_categories_order( $categories ) {
        if( isset( $categories[ LAYOUTS_INTEGRATION_THEME_NAME ] ) ) {
            $tmp = $categories[ LAYOUTS_INTEGRATION_THEME_NAME ];
            unset( $categories[ LAYOUTS_INTEGRATION_THEME_NAME ] );
           // $categories = array_reverse( $categories, true );
            $categories[ LAYOUTS_INTEGRATION_THEME_NAME ] = $tmp;
          //  $categories = array_reverse( $categories, true );
        }

        return $categories;
    }


    /**
     * Hooked into ddl_templates_have_layout filter.
     *
     * Layouts searches for compatible template files (files with string "Template Name") to add them to the Layouts
     * Select menu (for example on page edit). As we cannot change files in the theme, we will add support through this
     * filter.
     *
     * @param array $templates Array of template file names that support layouts.
     * @return array Updated array of templates.
     */
    public function activate_layouts_select_menu_for_posts( $templates ) {
        return array_unique( array_merge( $templates, array_keys( $this->get_supported_templates() ) ) );
    }


    /**
     * Hooked into ddl_no_templates_at_all filter.
     *
     * Tells layouts that the Theme doesn’t have any custom templates so WP doesn’t even show the Page Template
     * selector.
     *
     * In this case Layouts doesn’t show its selector as well and provides a custom message to warn the user that the
     * theme doesn’t have any additional TPL (global templates in WP terms, custom template... anyway the ones with the
     * comment header "Template name:").
     *
     * It should return false to force WP and Layouts to show the selector.
     *
     * @param bool $are_there_no_templates_at_all Whether the theme supports Layouts templates (false if it does).
     * @return bool False.
     */
    public function override_templates_exist(
        /** @noinspection PhpUnusedParameterInspection */ $are_there_no_templates_at_all )
    {
        return false;
    }


    /**
     * Hooked into ddl_check_layout_template_page_exists filter.
     *
     * This filter callback takes a boolean as the first argument and a post type slug as the second, since it checks
     * if a compatible template exists for a given post type: we return generically true since we provide full
     * compatibility for any post type.
     *
     * @param bool $compatible_template_exists
     * @param string $post_type
     * @return bool True.
     */
    public function override_compatible_templates_exists_for_post_type(
        /** @noinspection PhpUnusedParameterInspection */ $compatible_template_exists, $post_type )
    {
        return true;
    }


    /**
     * Hooked into ddl-theme_has_page_templates filter.
     *
     * Practically does the same like override_compatible_templates_exists_for_post_type but only for page post type.
     * It is strategic since it makes the check to display page assignment features in post edit page and assignment
     * dialog. Should return true.
     *
     * @param bool $compatible_template_exists
     * @return bool True.
     */
    public function override_compatible_template_exists_for_pages(
        /** @noinspection PhpUnusedParameterInspection */ $compatible_template_exists )
    {
        return true;
    }


    /**
     * Hooked into ddl-determine_main_template.
     *
     * Layouts follows the template hierarchy logic - we implement our own version of it in WPDD_Layouts_PostTypesManager
     * class - so that page.php is the main/default template for pages, single.php is for posts single-product.php for
     * products and so on.
     *
     * This filter lets you override this setting and tell Layouts that another compatible template is the main
     * compatible template for a post type.
     *
     * It's pretty useful, we use it here to force the template selector for page post edit page to consider our own
     * template as the main template for page post type. Theoretically there is no need to override other post types
     * since their selector is not a combo but way more simple.
     *
     * @param string $default_template The default template (file name).
     * @param string $current_template Current template in the loop.
     * @param string $post_type The post type we're taking into consideration.
     * @return string Default template for given post type.
     */
    public function override_default_template(
        $default_template, /** @noinspection PhpUnusedParameterInspection */ $current_template, $post_type )
    {
        if( $post_type === 'page' && apply_filters( 'ddl-template_have_layout', 'page.php' ) === false ) {
            return $this->getPageDefaultTemplate();
        }

        return $default_template;
    }


    /**
     * Hooked into ddl_template_have_layout (not ddl_templates_have_layout, there is no cycle, don't worry).
     *
     * This filter runs at the time a template is assigned to a page to check if the file in question is
     * Layouts-compatible or not. Since our custom template files are not in the theme directory, Layouts is not able
     * to find it and returns false (not compatible), so we need to manually tell Layouts that the file is compatible
     * when it checks for compatibility.
     *
     * @param boolean $is_compatible
     * @param string $file Template file name.
     * @return boolean True if the template is compatible with Layouts, false otherwise.
     **/
    public function set_if_template_is_layouts_compatible( $is_compatible, $file ) {
        if( in_array( $file, apply_filters( 'ddl_templates_have_layout', array() ) ) ) {
            return true;
        }

        return $is_compatible;
    }



    /**
     * @return string An empty string.
     */
    public function clear_content() {
        return '';
    }


    protected function get_current_theme_slug() {
        $theme = wp_get_theme();
        $name = strtolower( $theme->get( 'Name' ) );
        return str_replace(' ', '_', $name);
    }

    protected function load_default_layouts( $layouts_path ){

        $done = false;

        if ( ! get_option( $this->message->get_integration_option_string() ) || WPDD_Utils::at_least_one_layout_exists() === false ) {

            $done = WPDD_Layouts_Theme::getInstance()->import_layouts_from_theme($layouts_path, true);

            if( $done ){
                update_option( $this->message->get_integration_option_string(), 'yes' );
                do_action('ddl_dismiss_dismissabe_notice');
            }
        }

        return $done;
    }

    function ddl_load_default_layouts(){
        if( user_can_assign_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
        if ($_POST && wp_verify_nonce($_POST['ddl_load_default_layouts'], 'ddl_load_default_layouts')) {

            $done = $this->load_default_layouts( $this->get_layouts_path() );

            $send = wp_json_encode( array( 'message' => array('done' => $done) ) );

        } else {
            $send = wp_json_encode(array('error' => __(sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__), 'ddl-layouts')));
        }

        die($send);
    }

    /**
     * Get version of currently active theme.
     *
     * @return false|string
     */
    protected function get_current_theme_version() {
        $theme = wp_get_theme();
        return $theme->get( 'Version' );
    }

    protected function get_current_theme_name() {
        $theme = wp_get_theme();
        return $theme->get( 'Name' );
    }

    public function setPageDefaultTemplate( $file ) {
        $this->page_template_default = $file;
    }


    public function getPageDefaultTemplate() {
        return $this->page_template_default;
    }

	/**
	 * @deprecated 1.9
	 */
    public function integration_setup_message(){
        return;
    }

    protected function get_layouts_path(){
        return $this->layouts_path;
    }

    protected function set_layouts_path( $path ){
        $this->layouts_path = $path;
    }

    protected function add_settings_section(){
        add_filter( 'toolset_filter_toolset_register_settings_layouts_section',	array( $this, 'register_layouts_settings_items' ), 50, 2 );
    }

    public function register_layouts_settings_items( $sections, $settings ){
	    if( ! class_exists( 'Toolset_Condition_Plugin_Layouts_No_Items' )
	        || ! class_exists( 'Toolset_Admin_Notices_Manager' )
	        || ! is_callable( 'Toolset_Admin_Notices_Manager', 'tpl_link' ) ) {
	    	// toolset common not available, outdated or broken
		    return $sections;
	    }

	    $condition = new Toolset_Condition_Plugin_Layouts_No_Items();
	    if( ! $condition->is_met() ) {
	    	// don't show installer if we already have layouts created
	    	return $sections;
	    }

        $sections['toolset-load-layouts'] = array(
            'slug'		=> 'toolset-load-layouts',
            'title'		=> sprintf(__( 'Prepare %s for quick editing with Toolset', 'ddl-layouts' ), $this->get_current_theme_name() ),
            'callback' => array( &$this, 'ddl_load_integration_layouts' )
        );

        return $sections;
    }

    function ddl_load_integration_layouts( ){
        ob_start();

        require_once WPDDL_THEME_INTEGRATION_ABS . '/resources/templates/layout-settings-layouts-loader.tpl.php';

        echo ob_get_clean();
    }

    protected function handle_default_layouts_loader(){

        // do not show in iFrame
        if( isset( $_GET['in-iframe-for-layout'] ) &&
        $_GET['in-iframe-for-layout'] == 1 ){
            return;
        }

        if( isset( $_GET['page'] ) && $_GET['page'] === 'dd_layouts' && isset( $_GET['layouts_loaded'] ) ){
            if( $_GET['layouts_loaded'] == true ){
                WPDDL_Messages::add_admin_notice( 'updated', __('Layouts successfuly loaded.', 'ddl-layouts') );
            } else{
                WPDDL_Messages::add_admin_notice( 'error', __('There was a problem loading default layouts.', 'ddl-layouts') );
            }
            return;
        }

        if( WPDD_Layouts_Theme::dir_has_import_files( $this->get_layouts_path() ) === false ){
            return;
        } else{
            $this->integration_setup_message();
            $this->add_settings_section();
        }
    }

	/**
	 * A simple API to load Layouts from theme on demand
	 */
    public function enqueue_layouts_loader_js()
    {
    	// add a button with class .js-ddl-layouts-loader-button using 'ddl-add-gui-buttons-in-listing-page-top' action and press it to load default layouts using this method

    	if( !isset( $_GET['page'] ) || !$_GET['page'] === 'page=dd_layouts' ) return;

        $script_handle = 'layouts-theme-integration-layouts_loader';

        $script_path = WPDDL_INTEGRATION_STATIC_REL . '/' . 'js' . '/' . 'layouts-loader.js';

        wp_register_script(
            $script_handle,
            $script_path,
            array('jquery', 'underscore', 'toolset-utils'),
            $this->get_supported_theme_version(),
            true
        );

        wp_localize_script($script_handle, 'DDLayout_Theme', array(
                'ThemeIntegrationsSettings' => array(
                    'ddl_load_default_layouts' => wp_create_nonce('ddl_load_default_layouts', 'ddl_load_default_layouts'),
                    'layouts_loaded' => __('Layouts have been loaded successfully', 'ddl-layouts'),
                    'redirect_to' => admin_url( 'admin.php?page=dd_layouts' ),
                    'create_layouts' => __('Create Layouts', 'ddl-layouts'),
                    'redirect_to' => admin_url( 'admin.php?page=dd_layouts' )
                )
            )
        );

        wp_enqueue_script($script_handle);
    }

    /**
     * Enqueue JS code to override template select box on post edit page and populate it with list of supported
     * templates (from get_supported_templates()).
     *
     * This is necessary for the template selector to work at all if there are no templates at all in the theme (no
     * matter if not compatible or not). PHP filters are not enough do to the job, so we need this JS code.
     */
    protected function enqueue_post_edit_page_overrides_js() {

        $script_handle = 'layouts-theme-integration-post-edit-page-overrides';

        $script_path = WPDDL_INTEGRATION_STATIC_REL . '/'. 'js' . '/' . 'post-edit-page-overrides.js';

        wp_register_script(
            $script_handle,
            $script_path,
            array( 'jquery', 'underscore', 'ddl_post_edit_page' ),
            $this->get_supported_theme_version(),
            true
        );

        global $pagenow, $post;

        $supported_templates = $this->get_supported_templates();

        // Enqueue only on post edit/new post page
        if ( ( $pagenow == 'post.php' || $pagenow == 'post-new.php' )
            && $post->post_type === 'page'
            && ( is_array( $supported_templates ) && !empty( $supported_templates ) )
        ) {
            wp_localize_script($script_handle, 'DDLayout_Settings', array(
                    'ThemeIntegrations' => array(
                        'templates' => $supported_templates,
                        'theme_templates' => array_flip( get_page_templates() ),
                        'default_template' => $this->getPageDefaultTemplate()
                    )
                )
            );
            wp_enqueue_script($script_handle);
        }
    }

	public function layouts_woocommerce_show_page_title( $title ) {
		if ( self::is_woocommerce_active() && is_woocommerce() ) {
			if ( is_shop() ) {
				/**
				 * WooCommerce shop plays dual; as a shop page and an archive.
				 * By default, Views short code for archive title output different stuff,
				 * while, theme shows Shop Page title.
				 *
				 * Here, the title is modified to return the title of Shop Page.
				 */
				$shop_page_id = get_option( 'woocommerce_shop_page_id' );
				$title = sprintf( __( '%s', 'ddl-layouts' ), get_the_title( $shop_page_id ) );
			} else if ( is_product_category() ) {
				/**
				 * Just like the above, we need to strip-off the stuff other than the category name, from the title.
				 */
				$title = sprintf( __( '%s', 'ddl-layouts' ), single_cat_title( '', false ) );
			}
		}

		return $title;

	}

	/**
	 * @return bool
	 * check if Wooccomerce is actice
	 */
	public static function is_woocommerce_active(){
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	public static function return_true( $bool = false /* php prevent warning */ ){
		return true;
	}
}
